<?php
class Collaborator {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Retorna todos os colaboradores ativos de uma empresa
     */
    public function getColaboradoresAtivosByEmpresa(int $empresaId): array {
        $sql = "SELECT u.* 
                FROM colaboradores u
                WHERE u.empresa_id = ? AND u.ativo = 1
                ORDER BY u.nome ASC";
        return $this->db->query($sql, [$empresaId])->fetchAll();
    }

    /**
     * Retorna estatísticas de colaboradores
     */
    public function getEstatisticascolaboradores(int $empresaId): array {
        $sql = "SELECT 
                    COUNT(*) as total_colaboradoress,
                    SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
                    SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos,
                    SUM(CASE WHEN turno = 'matutino' THEN 1 ELSE 0 END) as matutino,
                    SUM(CASE WHEN turno = 'vespertino' THEN 1 ELSE 0 END) as vespertino,
                    SUM(CASE WHEN turno = 'notruno' THEN 1 ELSE 0 END) as noturno
                FROM colaboradores
                WHERE empresa_id = ?";
        return $this->db->query($sql, [$empresaId])->fetch();
    }

    /**
     * Cadastra um novo colaborador
     */
    public function cadastrarUsuario(array $dados): int {
        $sql = "INSERT INTO colaboradores (
                    empresa_id, codigo, nome, cpf, senha, turno, ativo, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $dados['empresa_id'],
            $this->gerarCodigoColaborador($dados['empresa_id']),
            $dados['nome'],
            $dados['cpf'],
            password_hash($dados['senha'], PASSWORD_DEFAULT),
            $dados['turno'] ?? $dados['turno'],
            $dados['ativo'] ?? 1
        ];

        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * Atualiza dados de um colaborador
     */
    public function atualizarColaborador(int $id, array $dados): bool {
        $sql = "UPDATE colaboradores SET 
                    nome = ?, cpf = ?, foto = ?, turno = ?, ativo = ?, updated_at = NOW()
                WHERE id = ?";
        return $this->db->query($sql, [
            $dados['nome'],
            $dados['cpf'],
            $dados['foto'],
            $dados['turno'],
            $dados['ativo'],
            $id
        ]) !== false;
    }

    /**
     * Atualiza senha do colaborador
     */
    public function atualizarSenha(int $id, string $novaSenha): bool {
        $sql = "UPDATE colaboradores SET senha = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [
            password_hash($novaSenha, PASSWORD_DEFAULT),
            $id
        ]) !== false;
    }

    
    /**
     * Busca colabordor por cpf
     */
    public function getUsuarioByCpf(string $cpf): ?array {
        $sql = "SELECT * FROM colaboradores WHERE cpf = ?";
        return $this->db->query($sql, [$cpf])->fetch() ?: null;
    }

    /**
     * Registra batida de ponto
     */
    public function setRegistroPonto(string $cpf, string $senha, string $token): array {
        $sql = "SELECT * FROM colaboradores c
                INNER JOIN empresas e ON e.id = c.empresa_id 
                WHERE c.ativo = 1 AND e.prefixo = ? AND c.cpf = ?";
        $colaborador = $this->db->query($sql, [$token,$cpf])->fetch() ?: null;

        if ($colaborador && password_verify($senha, $colaborador['senha'])) {

            // Determinar tipo de registro baseado no último ponto
            $sqlregistro = "SELECT tipo FROM registros_ponto 
                            WHERE colaborador_id = ? AND data_registro = CURDATE() 
                            ORDER BY hora_registro DESC LIMIT 1";
            $ultimo_tipo = $this->db->query($sqlregistro, [$colaborador['id']])->fetch() ?: null;

            if (!$ultimo_tipo) {
                $tipo = 'entrada';
            } else {
                switch ($ultimo_tipo['tipo']) {
                    case 'entrada':
                        $tipo = 'entrada_intervalo';
                        break;
                    case 'entrada_intervalo':
                        $tipo = 'retorno_intervalo';
                        break;
                    case 'retorno_intervalo':
                        $tipo = 'saida';
                        break;
                    default:
                        $tipo = 'entrada';
                }
            }

            // Verificar se já registrou este tipo hoje
            $hoje = date('Y-m-d');
            $hora_atual = date('H:i:s');

            if (ponto_ja_registrado($colaborador['id'], $tipo, $hoje, $hora_atual)) {
                return [
                    'registro_sucesso' => false,
                    'ultimo_registro' => [
                        'mensagem' => 'Ponto já registrado para este horário',
                        'data' => date('d/m/Y'),
                        'hora' => date('H:i:s')
                    ]
                ];
            } else {
                // Registrar ponto
                $sqlinsert = "INSERT INTO registros_ponto 
                            (colaborador_id, empresa_id, tipo, data_registro, hora_registro, ip_address, user_agent) 
                            VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)";
                $params = [
                    $colaborador['id'],
                    $colaborador['empresa_id'],
                    $tipo,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ];

                $this->db->query($sqlinsert, $params);
                $last_registro = $this->db->lastInsertId();

                // Renovar cookie de ponto (1 dia)
                setcookie(COOKIE_PONTO, $_COOKIE[COOKIE_PONTO], time() + COOKIE_PONTO_DURATION, '/');

                // Registrar log (se necessário)
                // registrar_log('REGISTRO_PONTO', "Registro de ponto: $tipo", null, $colaborador['id']);

                return [
                    'registro_sucesso' => true,
                    'ultimo_registro' => [
                        'tipo' => $tipo,
                        'data' => date('d/m/Y'),
                        'hora' => date('H:i:s'),
                        'mensagem' => 'Ponto registrado com sucesso'
                    ]
                ];
            }
        }

        // Caso colaborador não encontrado ou senha inválida
        return [
            'registro_sucesso' => false,
            'ultimo_registro' => [
                'mensagem' => 'Não foi possível registrar o ponto',
                'data' => date('d/m/Y'),
                'hora' => date('H:i:s')
            ]
        ];
    }

    // // public function setRegistroPonto(string $cpf, string $senha): array {
        
    // //     $sql = "SELECT * FROM colaboradores WHERE ativo = 1 AND cpf = ?";
    // //     $colaborador = $this->db->query($sql, [$cpf])->fetch() ?: null;
       
    // //     if ($colaborador && password_verify($senha, $colaborador['senha'])) {
            
    // //          // Determinar tipo de registro baseado no último ponto
    // //             $sqlregistro = "SELECT tipo FROM registros_ponto 
    // //                                   WHERE colaborador_id = ? AND data_registro = CURDATE() 
    // //                                   ORDER BY hora_registro DESC LIMIT 1";
    // //             $ultimo_tipo = $this->db->query($sqlregistro, [$colaborador['id']])->fetch() ?: null;
                
    // //             if (!$ultimo_tipo) {
    // //                 $tipo = 'entrada';
    // //             } else {
    // //                 switch ($ultimo_tipo['tipo']) {
    // //                     case 'entrada':
    // //                         $tipo = 'entrada_intervalo';
    // //                         break;
    // //                     case 'entrada_intervalo':
    // //                         $tipo = 'retorno_intervalo';
    // //                         break;
    // //                     case 'retorno_intervalo':
    // //                         $tipo = 'saida';
    // //                         break;
    // //                     default:
    // //                         $tipo = 'entrada';
    // //                 }
    // //             }   
    // //         // Verificar se já registrou este tipo hoje
    // //         $hoje = date('Y-m-d');
    // //         $hora_atual = date('H:i:s');
            
    // //         if (ponto_ja_registrado($colaborador['id'], $tipo, $hoje, $hora_atual)) {
    // //             $errors[] = 'Ponto já registrado para este horário';
    // //         } else {
    // //             // Registrar ponto
    // //             $sqlinsert ="INSERT INTO registros_ponto 
    // //                                     (colaborador_id, empresa_id, tipo, data_registro, hora_registro, ip_address, user_agent) 
    // //                                     VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)";
    // //             $params = [
    // //                 $colaborador['id'],
    // //                 $colaborador['empresa_id'],
    // //                 $tipo,
    // //                 $_SERVER['REMOTE_ADDR'],
    // //                 $_SERVER['HTTP_USER_AGENT']
    // //             ];

    // //             $this->db->query($sqlinsert, $params);
    // //             $last_registro = $this->db->lastInsertId();                        
                
                
    // //             // Renovar cookie de ponto (1 dia)
    // //             setcookie(COOKIE_PONTO, $_COOKIE[COOKIE_PONTO], time() + COOKIE_PONTO_DURATION, '/');
                
    // //             // Registrar log
    // //             //registrar_log('REGISTRO_PONTO', "Registro de ponto: $tipo", null, $colaborador['id']);
                
    // //             $registro_sucesso = true;
    // //             $ultimo_registro = [
    // //                 'tipo' => $tipo,
    // //                 'data' => date('d/m/Y'),
    // //                 'hora' => date('H:i:s')
    // //             ];

    // //             return 'Ponto registrado com sucesso';   
                
    // //             // // Buscar último registro para exibição
    // //             // $stmt = $pdo->prepare("SELECT * FROM registros_ponto 
    // //             //                         WHERE colaborador_id = ? 
    // //             //                         ORDER BY created_at DESC LIMIT 1");
    // //             // $stmt->execute([$colaborador['id']]);
    // //             // $ultimo_registro_db = $stmt->fetch();
    // //         }

    // //         return 'Não foi possível registrar o ponto';

    // //     } //fim do teste de existencia do colaborador
        
    // // }//// fim do método reigstro de ponto  
    

    /**
     * Gera código único para usuário
     */
    private function gerarCodigoColaborador(int $empresaId): string {
        $ano = date('Y');
        $sql = "SELECT COUNT(*) as total 
                FROM colaboradores 
                WHERE empresa_id = ? AND YEAR(created_at) = ?";
        $result = $this->db->query($sql, [$empresaId, $ano])->fetch();
        $sequencial = str_pad($result['total'] + 1, 4, '0', STR_PAD_LEFT);
        return "USR-{".str_pad($empresaId, 6, '0', STR_PAD_LEFT)."}-{$ano}-{$sequencial}";
    }
    
    /**
     * Função interna que valida se o ponto j´pa foi registrado na data atual
     */
    private function ponto_ja_registrado($colaborador_id, $tipo, $data, $hora) {
        
        
        $sql = "SELECT COUNT(*) as total 
                            FROM registros_ponto 
                            WHERE colaborador_id = ? 
                            AND tipo = ? 
                            AND data_registro = ? 
                            AND HOUR(hora_registro) = HOUR(?)                           
                            AND MINUTE(hora_registro) = MINUTE(?)";

        $result = $this->db->query($sql, [$colaborador_id, $tipo, $data, $hora, $hora])->fetch() ?: null;                          
        // $stmt->execute([$colaborador_id, $tipo, $data, $hora, $hora]);
        // $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }

}