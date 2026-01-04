<?php
// colaboradores-ajax.php
// Adicione no início do arquivo para melhor debug
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Adicione headers para CORS se necessário
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/functions.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$pdo = $database->getConnection();

$action = $_GET['action'] ?? '';

$cookie = lerCookieLogin(false);
$empresaid = $cookie['empresa_id'];

// Criar uma nova empresa (action=create)
if ($action === 'create') {
    try {
        
         var_dump($empresaid);  

        $inputnome = escapeString(trim($_POST['nome'] ?? ''));
        $inputcnpj = escapeString(trim($_POST['cpf'] ?? ''));
        $inputprefixo = escapeString(trim($_POST['codigo'] ?? ''));
        $inputsenha = 'colab123';
        $inputturno = escapeString(trim($_POST['turno'] ?? ''));
        $inputativa = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

        // Validação
        $errors = [];

        if (empty($inputnome)) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($inputcnpj)) {
            $errors[] = 'CNPJ é obrigatório';
        } else {
            // Formatar CNPJ (remover caracteres não numéricos)
            $inputcnpj = preg_replace('/[^0-9]/', '', $inputcnpj);
            if (strlen($inputcnpj) !== 11) {
                $errors[] = 'CPF deve ter 11 dígitos';
            }
        }
        
        if (empty($inputprefixo)) {
            $errors[] = 'Código é obrigatório';
        }
        
        // Validar turrn se fornecido
        if (empty($inputturno) ) {
            $errors[] = 'Turno é obrigatório';
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => implode(', ', $errors)
            ]);
            exit;
        }

        // Verificar se CNPJ já existe
        $stmtCheckCnpj = $pdo->prepare("SELECT id FROM colaboradores WHERE cpf = :cnpj");
        $stmtCheckCnpj->execute([':cnpj' => $inputcnpj]);
        
        if ($stmtCheckCnpj->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'CPF já cadastrado'
            ]);
            exit;
        }

        // Verificar se prefixo já existe
        $stmtCheckPrefixo = $pdo->prepare("SELECT id FROM Colaboradores WHERE codigo = :prefixo");
        $stmtCheckPrefixo->execute([':prefixo' => $inputprefixo]);
        
        if ($stmtCheckPrefixo->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Código já cadastrado'
            ]);
            exit;
        }
        // Inserção no banco
        $stmt = $pdo->prepare("INSERT INTO colaboradores 
                              (empresa_id, nome, cpf, codigo, senha, turno, ativo) 
                              VALUES (:empresaid, :nome, :cnpj, :prefixo, :senha, :turno, :ativa)");
        
        $result = $stmt->execute([
            ':empresaid' => $empresaid,
            ':nome' => $inputnome,
            ':cnpj' => $inputcnpj,
            ':prefixo' => $inputprefixo,
            ':senha' => password_hash($inputsenha, PASSWORD_DEFAULT),
            ':turno' => $inputturno,
            ':ativa' => $inputativa
        ]);

        if ($result) {
            $lastId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Colaborador cadastrado com sucesso.',
                'id' => $lastId,
                'data' => [
                    'id' => $lastId,
                    'nome' => $inputnome,
                    'cpf' => $inputcnpj,
                    'codigoo' => $inputprefixo
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Falha ao cadastrar colaborador.'
            ]);
        }
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Listar todas as colaboradores (action=list)
elseif ($action === 'list') {
    try {
        // Se usuário tem empresa_id no cookie, pode filtrar apenas sua empresa
        $filter_by_user = isset($cookie['empresa_id']) ? $cookie['empresa_id'] : null;
        
        if ($filter_by_user) {
            $stmt = $pdo->prepare("SELECT id, nome, cpf, codigo, turno, ativo, created_at, updated_at 
                                 FROM colaboradores 
                                 WHERE id = :empresa_id 
                                 ORDER BY nome ASC");
            $stmt->execute([':empresa_id' => $filter_by_user]);
        } else {
            // Usuário admin pode ver todas
            $stmt = $pdo->prepare("SELECT id, nome, cpf, codigo, turno, ativo, created_at, updated_at 
                                 FROM colaboradores 
                                 ORDER BY nome ASC");
            $stmt->execute();
        }
        
        $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // // Formatar dados para exibição
        // foreach ($colaboradores as &$empresa) {
        //     $empresa['cnpj_formatado'] = formatCnpj($empresa['cnpj']);
        //     $empresa['status'] = $empresa['ativa'] ? 'Ativa' : 'Inativa';
        //     $empresa['created_at_formatado'] = date('d/m/Y H:i', strtotime($empresa['created_at']));
        // }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Colaboradores carregados com sucesso.',
            'colaboradores' => $colaboradores,
            'total' => count($colaboradores)
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao carregar colaboradores: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Buscar uma empresa específica (action=get)
elseif ($action === 'get') {
    try {
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID da colaborador não informado.'
            ]);
            exit;
        }
        
        // Verificar se usuário tem permissão
        $whereClause = "WHERE id = :id AND empresa_id = :empresaid";
        $params = [':id' => $id, ':empresaid' => $empresaid];
        
        // if (isset($cookie['empresa_id']) && $cookie['empresa_id'] != $empresa_id) {
        //     // Se não for admin, só pode ver sua própria empresa
        //     if ($cookie['perfil'] !== 'admin') {
        //         echo json_encode([
        //             'success' => false, 
        //             'message' => 'Sem permissão para acessar esta empresa.'
        //         ]);
        //         exit;
        //     }
        // }
        
        $stmt = $pdo->prepare("SELECT id, nome, cpf, codigo, turno, ativo, created_at, updated_at 
                             FROM colaboradores 
                             {$whereClause} 
                             LIMIT 1");
        $stmt->execute($params);
        $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($colaborador) {
            // Formatar CNPJ para exibição
           // $colaborador['cnpj_formatado'] = formatCnpj($colaborador['cnpj']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Colabordor encontrado.',
                'empresa' => $colaborador
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'colaborador não encontrado.'
            ]);
        }
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao buscar colaborador: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Atualizar uma empresa (action=update)
elseif ($action === 'update') {
    try {
        $inputid = $_POST['update_id'] ?? 0;
        $inputnome = escapeString(trim($_POST['update_nome'] ?? ''));
        $inputcnpj = escapeString(trim($_POST['update_cpf'] ?? ''));
        $inputprefixo = escapeString(trim($_POST['update_codigo'] ?? ''));
        $inputurno = escapeString(trim($_POST['update_turno'] ?? ''));
        $inputativa = isset($_POST['update_ativa']) ? (int)$_POST['update_ativa'] : 1;

        // Validações
        if (!$inputid) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID da colaborador não informado.'
            ]);
            exit;
        }
        
        $errors = [];
        
        if (empty($inputnome)) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($inputcnpj)) {
            $errors[] = 'CPF é obrigatório';}
        // } else {
        //     $inputcnpj = preg_replace('/[^0-9]/', '', $inputcnpj);
        //     if (strlen($inputcnpj) !== 14) {
        //         $errors[] = 'CNPJ deve ter 14 dígitos';
        //     }
        // }
        
        if (empty($inputprefixo)) {
            $errors[] = 'Código é obrigatório';
        }
        
        if (empty($inputturno)) {
            $errors[] = 'Turno é obrigatório';
        }
        
        if (!empty($errors)) {
            echo json_encode([
                'success' => false, 
                'message' => implode(', ', $errors)
            ]);
            exit;
        }

        // // Verificar permissão
        // if (isset($cookie['empresa_id']) && $cookie['empresa_id'] != $empresa_id) {
        //     if ($cookie['perfil'] !== 'admin') {
        //         echo json_encode([
        //             'success' => false, 
        //             'message' => 'Sem permissão para editar esta empresa.'
        //         ]);
        //         exit;
        //     }
        // }

        // Verificar se CNPJ já existe (excluindo a própria empresa)
        $stmtCheckCnpj = $pdo->prepare("SELECT id FROM colaboradores WHERE cpf = :cnpj AND id != :id");
        $stmtCheckCnpj->execute([
            ':cnpj' => $inputcnpj,
            ':id' => $inputid
        ]);
        
        if ($stmtCheckCnpj->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'CPF já cadastrado para outra empresa'
            ]);
            exit;
        }

        // Verificar se prefixo já existe (excluindo a própria empresa)
        $stmtCheckPrefixo = $pdo->prepare("SELECT id FROM colaboradores WHERE codigo = :prefixo AND id != :id");
        $stmtCheckPrefixo->execute([
            ':prefixo' => $inputprefixo,
            ':id' => $inputid
        ]);
        
        if ($stmtCheckPrefixo->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Código já cadastrado para outra empresa'
            ]);
            exit;
        }

        // Atualizar empresa
        $stmt = $pdo->prepare("UPDATE colaboradores 
                             SET nome = :nome, 
                                 cpf = :cnpj, 
                                 codigo = :prefixo, 
                                 turno = :turno
                             WHERE id = :id");
        
        $result = $stmt->execute([
            ':nome' => $inputnome,
            ':cnpj' => $inputcnpj,
            ':codigo' => $inputprefixo,
            ':turno' => $inputturno,
            ':id' => $empresa_id
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Colaborador atualizado com sucesso.',
                'id' => $empresa_id
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Nenhuma alteração realizada.'
            ]);
        }
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao atualizar colaborador: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Deletar uma empresa (action=delete) - Soft delete ou hard delete
elseif ($action === 'delete') {
    try {
        $id = $_POST['delete_id'] ?? $_GET['id'] ?? 0;
        $hard_delete = false ; ////isset($_GET['hard']) ? (bool)$_GET['hard'] : false;
        
        if (!$id) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID do colaborador não informado.'
            ]);
            exit;
        }
        
        // // Verificar permissão (apenas admin pode deletar)
        // if (!isset($cookie['perfil']) || $cookie['perfil'] !== 'admin') {
        //     echo json_encode([
        //         'success' => false, 
        //         'message' => 'Apenas administradores podem excluir colaboradores.'
        //     ]);
        //     exit;
        // }

        // Verificar se empresa existe
        $stmtCheck = $pdo->prepare("SELECT id FROM colaboradores WHERE id = :id");
        $stmtCheck->execute([':id' => $empresa_id]);
        
        if (!$stmtCheck->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Colaborador não encontrado.'
            ]);
            exit;
        }

        // if ($hard_delete) {
        //     // Hard delete - remover completamente
        //     $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE id = :id");
        // } else {
            // Soft delete - marcar como inativa
            $stmt = $pdo->prepare("UPDATE colaboradores SET ativo = 0 WHERE id = :id AND empresa_id = :empresaid");
        // }
        
        $result = $stmt->execute([':id' => $id, ":empresaid" => $empresaid]);
        
        if ($result && $stmt->rowCount() > 0) {
            $message = $hard_delete ? 'Colaborador excluído permanentemente.' : 'Colaborador desativado com sucesso.';
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'id' => $id
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao excluir colaborador.'
            ]);
        }
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao excluir colaborador: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Buscar colaboradores por termo (action=search) - para autocomplete
elseif ($action === 'search') {
    try {
        $termo = $_GET['q'] ?? '';
        $limit = $_GET['limit'] ?? 10;
        
        if (empty($termo)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Termo de busca não informado.'
            ]);
            exit;
        }
        
        $termoBusca = "%{$termo}%";
        
        $stmt = $pdo->prepare("SELECT id, nome, cpf, codigo 
                             FROM colaboradores 
                             WHERE (nome LIKE :termo OR cpf LIKE :termo OR codigo LIKE :termo)
                             AND ativa = 1
                             ORDER BY nome 
                             LIMIT :limit");
        
        $stmt->bindValue(':termo', $termoBusca, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Busca realizada.',
            'resultados' => $colaboradores,
            'total' => count($colaboradores)
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro na busca: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Ação não reconhecida
else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ação não especificada ou inválida.'
    ]);
    exit;
}

// Função auxiliar para formatar CNPJ
function formatCnpj($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }
    return $cnpj;
}
?>