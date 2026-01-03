<?php
// empresas-ajax.php
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

// Criar uma nova empresa (action=create)
if ($action === 'create') {
    try {
        $inputnome = escapeString(trim($_POST['nome'] ?? ''));
        $inputcnpj = escapeString(trim($_POST['cnpj'] ?? ''));
        $inputprefixo = escapeString(trim($_POST['prefixo'] ?? ''));
        $inputendereco = escapeString(trim($_POST['endereco'] ?? ''));
        $inputtelefone = escapeString(trim($_POST['telefone'] ?? ''));
        $inputemail = escapeString(trim($_POST['email'] ?? ''));
        $inputativa = isset($_POST['ativa']) ? (int)$_POST['ativa'] : 1;

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
            if (strlen($inputcnpj) !== 14) {
                $errors[] = 'CNPJ deve ter 14 dígitos';
            }
        }
        
        if (empty($inputprefixo)) {
            $errors[] = 'Prefixo é obrigatório';
        }
        
        // Validar email se fornecido
        if (!empty($inputemail) && !filter_var($inputemail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => implode(', ', $errors)
            ]);
            exit;
        }

        // Verificar se CNPJ já existe
        $stmtCheckCnpj = $pdo->prepare("SELECT id FROM empresas WHERE cnpj = :cnpj");
        $stmtCheckCnpj->execute([':cnpj' => $inputcnpj]);
        
        if ($stmtCheckCnpj->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'CNPJ já cadastrado'
            ]);
            exit;
        }

        // Verificar se prefixo já existe
        $stmtCheckPrefixo = $pdo->prepare("SELECT id FROM empresas WHERE prefixo = :prefixo");
        $stmtCheckPrefixo->execute([':prefixo' => $inputprefixo]);
        
        if ($stmtCheckPrefixo->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Prefixo já cadastrado'
            ]);
            exit;
        }

        // Inserção no banco
        $stmt = $pdo->prepare("INSERT INTO empresas 
                              (nome, cnpj, prefixo, endereco, telefone, email, ativa) 
                              VALUES (:nome, :cnpj, :prefixo, :endereco, :telefone, :email, :ativa)");
        
        $result = $stmt->execute([
            ':nome' => $inputnome,
            ':cnpj' => $inputcnpj,
            ':prefixo' => $inputprefixo,
            ':endereco' => $inputendereco,
            ':telefone' => $inputtelefone,
            ':email' => $inputemail,
            ':ativa' => $inputativa
        ]);

        if ($result) {
            $lastId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Empresa cadastrada com sucesso.',
                'id' => $lastId,
                'data' => [
                    'id' => $lastId,
                    'nome' => $inputnome,
                    'cnpj' => $inputcnpj,
                    'prefixo' => $inputprefixo
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Falha ao cadastrar empresa.'
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

// Listar todas as empresas (action=list)
elseif ($action === 'list') {
    try {
        // Se usuário tem empresa_id no cookie, pode filtrar apenas sua empresa
        $filter_by_user = isset($cookie['empresa_id']) ? $cookie['empresa_id'] : null;
        
        if ($filter_by_user) {
            $stmt = $pdo->prepare("SELECT id, nome, cnpj, prefixo, endereco, telefone, email, ativa, created_at, updated_at 
                                 FROM empresas 
                                 WHERE id = :empresa_id 
                                 ORDER BY nome ASC");
            $stmt->execute([':empresa_id' => $filter_by_user]);
        } else {
            // Usuário admin pode ver todas
            $stmt = $pdo->prepare("SELECT id, nome, cnpj, prefixo, endereco, telefone, email, ativa, created_at, updated_at 
                                 FROM empresas 
                                 ORDER BY nome ASC");
            $stmt->execute();
        }
        
        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatar dados para exibição
        foreach ($empresas as &$empresa) {
            $empresa['cnpj_formatado'] = formatCnpj($empresa['cnpj']);
            $empresa['status'] = $empresa['ativa'] ? 'Ativa' : 'Inativa';
            $empresa['created_at_formatado'] = date('d/m/Y H:i', strtotime($empresa['created_at']));
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Empresas carregadas com sucesso.',
            'empresas' => $empresas,
            'total' => count($empresas)
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao carregar empresas: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Buscar uma empresa específica (action=get)
elseif ($action === 'get') {
    try {
        $empresa_id = $_GET['id'] ?? 0;
        
        if (!$empresa_id) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID da empresa não informado.'
            ]);
            exit;
        }
        
        // Verificar se usuário tem permissão
        $whereClause = "WHERE id = :id";
        $params = [':id' => $empresa_id];
        
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
        
        $stmt = $pdo->prepare("SELECT id, nome, cnpj, prefixo, endereco, telefone, email, ativa, created_at, updated_at 
                             FROM empresas 
                             {$whereClause} 
                             LIMIT 1");
        $stmt->execute($params);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empresa) {
            // Formatar CNPJ para exibição
            $empresa['cnpj_formatado'] = formatCnpj($empresa['cnpj']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Empresa encontrada.',
                'empresa' => $empresa
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Empresa não encontrada.'
            ]);
        }
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao buscar empresa: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Atualizar uma empresa (action=update)
elseif ($action === 'update') {
    try {
        $empresa_id = $_POST['update_id'] ?? 0;
        $inputnome = escapeString(trim($_POST['update_nome'] ?? ''));
        $inputcnpj = escapeString(trim($_POST['update_cnpj'] ?? ''));
        $inputprefixo = escapeString(trim($_POST['update_prefixo'] ?? ''));
        $inputendereco = escapeString(trim($_POST['update_endereco'] ?? ''));
        $inputtelefone = escapeString(trim($_POST['update_telefone'] ?? ''));
        $inputemail = escapeString(trim($_POST['update_email'] ?? ''));
        $inputativa = isset($_POST['update_ativa']) ? (int)$_POST['update_ativa'] : 1;

        // Validações
        if (!$empresa_id) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID da empresa não informado.'
            ]);
            exit;
        }
        
        $errors = [];
        
        if (empty($inputnome)) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($inputcnpj)) {
            $errors[] = 'CNPJ é obrigatório';
        } else {
            $inputcnpj = preg_replace('/[^0-9]/', '', $inputcnpj);
            if (strlen($inputcnpj) !== 14) {
                $errors[] = 'CNPJ deve ter 14 dígitos';
            }
        }
        
        if (empty($inputprefixo)) {
            $errors[] = 'Prefixo é obrigatório';
        }
        
        if (!empty($inputemail) && !filter_var($inputemail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }
        
        if (!empty($errors)) {
            echo json_encode([
                'success' => false, 
                'message' => implode(', ', $errors)
            ]);
            exit;
        }

        // Verificar permissão
        if (isset($cookie['empresa_id']) && $cookie['empresa_id'] != $empresa_id) {
            if ($cookie['perfil'] !== 'admin') {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Sem permissão para editar esta empresa.'
                ]);
                exit;
            }
        }

        // Verificar se CNPJ já existe (excluindo a própria empresa)
        $stmtCheckCnpj = $pdo->prepare("SELECT id FROM empresas WHERE cnpj = :cnpj AND id != :id");
        $stmtCheckCnpj->execute([
            ':cnpj' => $inputcnpj,
            ':id' => $empresa_id
        ]);
        
        if ($stmtCheckCnpj->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'CNPJ já cadastrado para outra empresa'
            ]);
            exit;
        }

        // Verificar se prefixo já existe (excluindo a própria empresa)
        $stmtCheckPrefixo = $pdo->prepare("SELECT id FROM empresas WHERE prefixo = :prefixo AND id != :id");
        $stmtCheckPrefixo->execute([
            ':prefixo' => $inputprefixo,
            ':id' => $empresa_id
        ]);
        
        if ($stmtCheckPrefixo->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Prefixo já cadastrado para outra empresa'
            ]);
            exit;
        }

        // Atualizar empresa
        $stmt = $pdo->prepare("UPDATE empresas 
                             SET nome = :nome, 
                                 cnpj = :cnpj, 
                                 prefixo = :prefixo, 
                                 endereco = :endereco, 
                                 telefone = :telefone, 
                                 email = :email, 
                                 ativa = :ativa 
                             WHERE id = :id");
        
        $result = $stmt->execute([
            ':nome' => $inputnome,
            ':cnpj' => $inputcnpj,
            ':prefixo' => $inputprefixo,
            ':endereco' => $inputendereco,
            ':telefone' => $inputtelefone,
            ':email' => $inputemail,
            ':ativa' => $inputativa,
            ':id' => $empresa_id
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Empresa atualizada com sucesso.',
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
            'message' => 'Erro ao atualizar empresa: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Deletar uma empresa (action=delete) - Soft delete ou hard delete
elseif ($action === 'delete') {
    try {
        $empresa_id = $_POST['delete_id'] ?? $_GET['id'] ?? 0;
        $hard_delete = isset($_GET['hard']) ? (bool)$_GET['hard'] : false;
        
        if (!$empresa_id) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID da empresa não informado.'
            ]);
            exit;
        }
        
        // Verificar permissão (apenas admin pode deletar)
        if (!isset($cookie['perfil']) || $cookie['perfil'] !== 'admin') {
            echo json_encode([
                'success' => false, 
                'message' => 'Apenas administradores podem excluir empresas.'
            ]);
            exit;
        }

        // Verificar se empresa existe
        $stmtCheck = $pdo->prepare("SELECT id FROM empresas WHERE id = :id");
        $stmtCheck->execute([':id' => $empresa_id]);
        
        if (!$stmtCheck->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Empresa não encontrada.'
            ]);
            exit;
        }

        if ($hard_delete) {
            // Hard delete - remover completamente
            $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = :id");
        } else {
            // Soft delete - marcar como inativa
            $stmt = $pdo->prepare("UPDATE empresas SET ativa = 0 WHERE id = :id");
        }
        
        $result = $stmt->execute([':id' => $empresa_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            $message = $hard_delete ? 'Empresa excluída permanentemente.' : 'Empresa desativada com sucesso.';
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'id' => $empresa_id
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao excluir empresa.'
            ]);
        }
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao excluir empresa: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Buscar empresas por termo (action=search) - para autocomplete
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
        
        $stmt = $pdo->prepare("SELECT id, nome, cnpj, prefixo 
                             FROM empresas 
                             WHERE (nome LIKE :termo OR cnpj LIKE :termo OR prefixo LIKE :termo)
                             AND ativa = 1
                             ORDER BY nome 
                             LIMIT :limit");
        
        $stmt->bindValue(':termo', $termoBusca, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Busca realizada.',
            'resultados' => $empresas,
            'total' => count($empresas)
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