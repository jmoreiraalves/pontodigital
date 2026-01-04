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
$inputempresaid = $cookie['empresa_id'];

if ($action === 'cadastrar') {
    // Validação dos dados
    $required_fields = ['colaborador_id', 'data_emissao', 'data_inicio', 'data_fim', 'dias_afastamento'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            die(json_encode(['success' => false, 'message' => "Campo $field é obrigatório"]));
        }
    }

    try {
        $pdo->beginTransaction();

        // Inserir atestado
        $sql = "INSERT INTO atestados_medicos (
            colaborador_id, 
            empresa_id, 
            cid, 
            diagnostico, 
            data_emissao, 
            data_inicio, 
            data_fim, 
            dias_afastamento, 
            horas_afastamento, 
            observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Buscar empresa_id do colaborador
        $stmt_colab = $pdo->prepare("SELECT empresa_id FROM colaboradores WHERE id = ?");
        $stmt_colab->execute([$_POST['colaborador_id']]);
        $colaborador = $stmt_colab->fetch();
        
        if (!$colaborador) {
            throw new Exception("Colaborador não encontrado");
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['colaborador_id'],
            $colaborador['empresa_id'],
            $_POST['cid'] ?? null,
            $_POST['diagnostico'] ?? null,
            $_POST['data_emissao'],
            $_POST['data_inicio'],
            $_POST['data_fim'],
            $_POST['dias_afastamento'],
            $_POST['horas_afastamento'] ?? 0,
            $_POST['observacoes'] ?? null
        ]);

        $atestado_id = $pdo->lastInsertId();

        // Processar upload de arquivo (se houver)
        if (isset($_FILES['arquivo_anexo']) && $_FILES['arquivo_anexo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/atestados/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $extensao = pathinfo($_FILES['arquivo_anexo']['name'], PATHINFO_EXTENSION);
            $nome_arquivo = "atestado_{$atestado_id}_" . time() . ".{$extensao}";
            $caminho_completo = $upload_dir . $nome_arquivo;
            
            if (move_uploaded_file($_FILES['arquivo_anexo']['tmp_name'], $caminho_completo)) {
                $pdo->prepare("UPDATE atestados_medicos SET arquivo_anexo = ? WHERE id = ?")
                    ->execute([$nome_arquivo, $atestado_id]);
            }
        }

        // Registrar dias no ponto
        registrarDiasAtestado($pdo, $atestado_id, $_POST['colaborador_id'], 
            $colaborador['empresa_id'], $_POST['data_inicio'], $_POST['data_fim']);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Atestado cadastrado com sucesso!', 'id' => $atestado_id]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
} elseif ($action === 'excluir') {
    $id = $_POST['id'] ?? 0;
    
    try {
        $pdo->beginTransaction();
        
        // Remover registros de ponto vinculados
        $pdo->prepare("UPDATE registros_ponto SET atestado_id = NULL, abonado_atestado = 0 WHERE atestado_id = ?")
            ->execute([$id]);
        
        // Excluir atestado
        $pdo->prepare("DELETE FROM atestados_medicos WHERE id = ?")
            ->execute([$id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Atestado excluído com sucesso']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
    }
}

function registrarDiasAtestado($pdo, $atestado_id, $colaborador_id, $empresa_id, $data_inicio, $data_fim) {
    $data_inicio_obj = new DateTime($data_inicio);
    $data_fim_obj = new DateTime($data_fim);
    
    // Gerar todos os dias do período
    $periodo = new DatePeriod(
        $data_inicio_obj,
        new DateInterval('P1D'),
        $data_fim_obj->modify('+1 day')
    );
    
    foreach ($periodo as $data) {
        $data_str = $data->format('Y-m-d');
        
        // Verificar se já existe registro para esse dia
        $stmt = $pdo->prepare("
            SELECT id FROM registros_ponto 
            WHERE colaborador_id = ? 
            AND data_registro = ? 
            AND tipo = 'entrada'
        ");
        $stmt->execute([$colaborador_id, $data_str]);
        $existe = $stmt->fetch();
        
        if ($existe) {
            // Atualizar registro existente
            $pdo->prepare("
                UPDATE registros_ponto 
                SET atestado_id = ?, abonado_atestado = 1 
                WHERE id = ?
            ")->execute([$atestado_id, $existe['id']]);
        } else {
            // Inserir novo registro
            $pdo->prepare("
                INSERT INTO registros_ponto (
                    colaborador_id, 
                    empresa_id, 
                    tipo, 
                    data_registro, 
                    hora_registro, 
                    atestado_id, 
                    abonado_atestado,
                    metodo
                ) VALUES (?, ?, 'entrada', ?, '00:00:00', ?, 1, 'web')
            ")->execute([$colaborador_id, $empresa_id, $data_str, $atestado_id]);
        }
    }
}
