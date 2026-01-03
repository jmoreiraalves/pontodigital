<?php
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

if ($action === 'get' && isset($cookie['empresa_id'])) {
    try {
        $empresa_id = $cookie['empresa_id'];
        $categoria_id = $_GET['id'] ?? 0;
        
        if (!$categoria_id) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID da categoria não informado.'
            ]);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id, name as nome, code, ativo, empresa_id 
                               FROM tec_categories 
                               WHERE id = :id 
                               AND empresa_id = :empresa_id 
                               AND ativo = 'ativo' 
                               LIMIT 1");
        $stmt->execute([
            ':id' => $categoria_id,
            ':empresa_id' => $empresa_id
        ]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($categoria) {
            echo json_encode([
                'success' => true, 
                'message' => 'Categoria encontrada.',
                'categoria' => $categoria
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Categoria não encontrada ou não pertence a esta empresa.'
            ]);
        }
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao buscar categoria: ' . $e->getMessage()
        ]);
        exit;
    }
}
//Atualizar uma categoria (action=update)
elseif ($action === 'update' && isset($cookie['empresa_id'])) {
    try {
        //var_dump($_POST);
        $empresa_id = $cookie['empresa_id'];
        $categoria_id = $_POST['update_id'] ?? 0;
        $inputname = escapeString($_POST['update_nome'] ?? '');
        $inputcode = escapeString($_POST['update_code'] ?? '');
        //var_dump($categoria_id);
        // Validações
        if (!$categoria_id) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID da categoria não informado.'
            ]);
            exit;
        }
        
        if (empty(trim($inputname)) || empty(trim($inputcode))) {
            echo json_encode([
                'success' => false, 
                'message' => 'Nome e código são obrigatórios.'
            ]);
            exit;
        }
        
        // Verificar se categoria pertence à empresa e existe
        $stmtCheck = $pdo->prepare("SELECT id FROM tec_categories 
                                   WHERE id = :id 
                                   AND empresa_id = :empresa_id 
                                   AND ativo = 'ativo'");
        $stmtCheck->execute([
            ':id' => $categoria_id,
            ':empresa_id' => $empresa_id
        ]);
        
        if (!$stmtCheck->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Categoria não encontrada ou não pertence a esta empresa.'
            ]);
            exit;
        }
        
        // Verificar se novo código já existe (excluindo a própria categoria)
        $stmtCheckCode = $pdo->prepare("SELECT id FROM tec_categories 
                                       WHERE empresa_id = :empresa_id 
                                       AND code = :code 
                                       AND id != :id 
                                       AND ativo = 'ativo'");
        $stmtCheckCode->execute([
            ':empresa_id' => $empresa_id,
            ':code' => $inputcode,
            ':id' => $categoria_id
        ]);
        
        if ($stmtCheckCode->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Código já existe para outra categoria.'
            ]);
            exit;
        }
        
        // Atualizar categoria
        $stmt = $pdo->prepare("UPDATE tec_categories 
                               SET name = :name, code = :code 
                               WHERE id = :id 
                               AND empresa_id = :empresa_id");
        
        $result = $stmt->execute([
            ':name' => $inputname,
            ':code' => $inputcode,
            ':id' => $categoria_id,
            ':empresa_id' => $empresa_id
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Categoria atualizada com sucesso.',
                'id' => $categoria_id
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
            'message' => 'Erro ao atualizar categoria: ' . $e->getMessage()
        ]);
        exit;
    }
}