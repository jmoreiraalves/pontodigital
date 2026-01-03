<?php
class User {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Retorna todos os usuários ativos de uma empresa
     */
    public function getUsuariosAtivosByEmpresa(int $empresaId): array {
        $sql = "SELECT u.* 
                FROM usuarios u
                WHERE u.empresa_id = ? AND u.ativo = 1
                ORDER BY u.nome ASC";
        return $this->db->query($sql, [$empresaId])->fetchAll();
    }

    /**
     * Retorna estatísticas de usuários
     */
    public function getEstatisticasUsuarios(int $empresaId): array {
        $sql = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
                    SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos,
                    SUM(CASE WHEN tipo = 'super' THEN 1 ELSE 0 END) as super,
                    SUM(CASE WHEN tipo = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN tipo = 'ti' THEN 1 ELSE 0 END) as ti,
                    SUM(CASE WHEN tipo = 'gestor' THEN 1 ELSE 0 END) as gestores
                FROM usuarios
                WHERE empresa_id = ?";
        return $this->db->query($sql, [$empresaId])->fetch();
    }

    /**
     * Cadastra um novo usuário
     */
    public function cadastrarUsuario(array $dados): int {
        $sql = "INSERT INTO usuarios (
                    empresa_id, codigo, nome, cpf, email, senha, tipo, ativo, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $dados['empresa_id'],
            $this->gerarCodigoUsuario($dados['empresa_id']),
            $dados['nome'],
            $dados['cpf'],
            $dados['email'],
            password_hash($dados['senha'], PASSWORD_DEFAULT),
            $dados['tipo'] ?? 'admin',
            $dados['ativo'] ?? 1
        ];

        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * Atualiza dados de um usuário
     */
    public function atualizarUsuario(int $id, array $dados): bool {
        $sql = "UPDATE usuarios SET 
                    nome = ?, cpf = ?, email = ?, tipo = ?, ativo = ?, updated_at = NOW()
                WHERE id = ?";
        return $this->db->query($sql, [
            $dados['nome'],
            $dados['cpf'],
            $dados['email'],
            $dados['tipo'],
            $dados['ativo'],
            $id
        ]) !== false;
    }

    /**
     * Atualiza senha do usuário
     */
    public function atualizarSenha(int $id, string $novaSenha): bool {
        $sql = "UPDATE usuarios SET senha = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [
            password_hash($novaSenha, PASSWORD_DEFAULT),
            $id
        ]) !== false;
    }

    /**
     * Registra último login
     */
    public function registrarLogin(int $id): bool {
        $sql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?";
        return $this->db->query($sql, [$id]) !== false;
    }

    /**
     * Busca usuário por email (para login)
     */
    public function getUsuarioByEmail(string $email): ?array {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        return $this->db->query($sql, [$email])->fetch() ?: null;
    }

    /**
     * Gera código único para usuário
     */
    private function gerarCodigoUsuario(int $empresaId): string {
        $ano = date('Y');
        $sql = "SELECT COUNT(*) as total 
                FROM usuarios 
                WHERE empresa_id = ? AND YEAR(created_at) = ?";
        $result = $this->db->query($sql, [$empresaId, $ano])->fetch();
        $sequencial = str_pad($result['total'] + 1, 4, '0', STR_PAD_LEFT);
        return "USR-{$empresaId}-{$ano}-{$sequencial}";
    }

    /**
     * Registra batida de ponto
     */

    public function setRegistroPonto(string $cpf, string $senha): string {
        
    }
}