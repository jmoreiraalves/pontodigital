<?php

declare(strict_types=1);

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    $url = 'logout.php';
    echo '<script>';
    echo 'window.location.href = "' . $url . '";';
    echo '</script>';
    exit();
}

$empresaid = $_SESSION['empresa_id'];

// Conectar ao banco de dados
$database = new Database();
$pdo = $database->getConnection();

// Processar o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_empresa'])) {
    
    // Coletar e sanitizar os dados do formulário
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cnpj = filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_STRING);
    $prefixo = filter_input(INPUT_POST, 'prefixo', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $endereco = filter_input(INPUT_POST, 'endereco', FILTER_SANITIZE_STRING);
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($cnpj) || empty($prefixo)) {
        echo '<div class="alert alert-danger">Preencha todos os campos obrigatórios!</div>';
    } else {
        try {
            // Preparar e executar a query de UPDATE
            $sql = "UPDATE empresas SET 
                    nome = :nome,
                    cnpj = :cnpj,
                    prefixo = :prefixo,
                    email = :email,
                    telefone = :telefone,
                    endereco = :endereco,
                    updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => mb_strtoupper($nome, 'UTF-8'),
                ':cnpj' => $cnpj,
                ':prefixo' => mb_strtoupper($prefixo, 'UTF-8'),
                ':email' => $email,
                ':telefone' => $telefone,
                ':endereco' => $endereco,
                ':id' => $empresaid
            ]);
            
            // Verificar se a atualização foi bem-sucedida
            if ($stmt->rowCount() > 0) {
                echo '<div class="alert alert-success">Empresa atualizada com sucesso!</div>';
                
                // Atualizar os dados na variável $empresa para mostrar no formulário
                $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $empresaid]);
                $empresa = $stmt->fetch();
            } else {
                echo '<div class="alert alert-warning">Nenhuma alteração foi realizada.</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Erro ao atualizar empresa: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Buscar dados da empresa (se ainda não foi buscado ou se precisamos atualizar após o POST)
if (!isset($empresa) || empty($empresa)) {
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE ativa = 1 AND id = :id LIMIT 1");
    $stmt->execute([':id' => $empresaid]);
    $empresa = $stmt->fetch();
}

?>

<form method="POST" id="formAlterarEmpresa" class="needs-validation" novalidate>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nome" class="form-label">Nome da Empresa *</label>
                <input type="text" class="form-control uppercase" id="nome" name="nome" value="<?= htmlspecialchars($empresa['nome'] ?? '') ?>" required>
                <div class="invalid-feedback">Campo obrigatório</div>
            </div>

            <div class="col-md-6 mb-3">
                <label for="cnpj" class="form-label">CNPJ *</label>
                <input type="text" class="form-control cnpj-mask" id="cnpj" name="cnpj" value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>" required>
                <div class="invalid-feedback">CNPJ inválido</div>
            </div>

            <div class="col-md-6 mb-3">
                <label for="prefixo" class="form-label">Prefixo *</label>
                <input type="text" class="form-control uppercase" id="prefixo" name="prefixo" maxlength="10" value="<?= htmlspecialchars($empresa['prefixo'] ?? '') ?>" required>
                <div class="invalid-feedback">Máximo 10 caracteres</div>
                <small class="text-muted">Ex: EMP, ABC, XYZ</small>
            </div>

            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" class="form-control tel-mask" id="telefone" name="telefone" value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>">
            </div>

            <div class="col-12 mb-3">
                <label for="endereco" class="form-label">Endereço</label>
                <textarea class="form-control" id="endereco" name="endereco" rows="3"><?= htmlspecialchars($empresa['endereco'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" name="add_empresa" class="btn btn-primary">
            <i class="fas fa-save"></i> Alterar
        </button>
    </div>
</form>

