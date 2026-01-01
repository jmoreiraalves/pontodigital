<?php
declare(strict_types=1);

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$empresaid = $_SESSION['empresa_id'];

$database = new Database();
$pdo = $database->getConnection();

// Ações CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

try {
    $empresas = $database->fetchAll("SELECT * FROM empresas ORDER BY nome");
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar empresas: ' . $e->getMessage();
}

// Adicionar empresa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_empresa'])) {
    $nome = toUpperCase($_POST['nome'] ?? '');
    $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
    $prefixo = toUpperCase($_POST['prefixo'] ?? '');
    $endereco = $_POST['endereco'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $ativa = isset($_POST['ativa']) ? 1 : 0;

    // Validações
    if (empty($nome) || empty($cnpj) || empty($prefixo)) {
        $errors[] = 'Preencha todos os campos obrigatórios';
    } else {
        try {
            // Verificar se CNPJ já existe usando o método exists
            if ($database->exists('empresas', 'cnpj', formatar_cnpj($cnpj))) {
                $errors[] = 'CNPJ já cadastrado';
            } else {
                // Verificar se prefixo já existe usando fetchOne
                $prefixoExistente = $database->fetchOne(
                    "SELECT id FROM empresas WHERE prefixo = :prefixo",
                    [':prefixo' => $prefixo]
                );

                if ($prefixoExistente) {
                    $errors[] = 'Prefixo já em uso';
                } else {
                    // Inserir empresa usando parâmetros nomeados
                    $sql = "INSERT INTO empresas (nome, cnpj, prefixo, endereco, telefone, email, ativa) 
                            VALUES (:nome, :cnpj, :prefixo, :endereco, :telefone, :email, :ativa)";

                    $params = [
                        ':nome' => $nome,
                        ':cnpj' => formatar_cnpj($cnpj),
                        ':prefixo' => $prefixo,
                        ':endereco' => $endereco,
                        ':telefone' => $telefone,
                        ':email' => $email,
                        ':ativa' => $ativa
                    ];

                    $database->query($sql, $params);

                    $success = 'Empresa cadastrada com sucesso!';
                    registrar_log('CADASTRO_EMPRESA', "Nova empresa: $nome", $user['user_id']);

                    // Recarregar lista
                    $empresas = $database->fetchAll("SELECT * FROM empresas ORDER BY nome");
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao cadastrar empresa: ' . $e->getMessage();
        }
    }
}

?>


<!-- Conteúdo Principal -->
<main class="col-md-9 col-lg-10 ">
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-building"></i> Gerenciar Empresas
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmpresaModal">
                <i class="fas fa-plus-circle"></i> Nova Empresa
            </button>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p class="mb-0"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Lista de Empresas -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> Empresas Cadastradas
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="empresasTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Prefixo</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                            <tr>
                                <td><?php echo $empresa['id']; ?></td>
                                <td><?php echo htmlspecialchars($empresa['nome']); ?></td>
                                <td><?php echo $empresa['cnpj']; ?></td>
                                <td><span class="badge bg-primary"><?php echo $empresa['prefixo']; ?></span></td>
                                <td><?php echo htmlspecialchars($empresa['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $empresa['ativa'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $empresa['ativa'] ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- <button type="button" id="btneditEmpresaModal" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editEmpresaModal"
                                        data-id = <?= $empresa['id']?>
                                        data-nome = <?= $empresa['nome']?>
                                        data-cnpj = <?= $empresa['cnpj']?>
                                        data-prefixo = <?= $empresa['prefixo']?>
                                        data-endereco = <?= $empresa['endereco']?>
                                        data-email = <?= $empresa['email']?>
                                        >
                                        <i class="fas fa-edit"></i> 
                                    </button>-->
                                    <!-- <a href="?action=edit&id=<?php echo $empresa['id']; ?>" class="btn btn-sm btn-warning"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $empresa['id']; ?>"
                                        class="btn btn-sm btn-danger btn-delete" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</div> <!-- Fecha a div do conteúdo principal -->
</div> <!-- Fecha a row principal -->

<!-- Modal Adicionar Empresa -->
<div class="modal fade" id="addEmpresaModal" tabindex="-1" aria-labelledby="addEmpresaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addEmpresaModalLabel">
                    <i class="fas fa-plus-circle"></i> Nova Empresa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control uppercase" id="nome" name="nome" required>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="cnpj" class="form-label">CNPJ *</label>
                            <input type="text" class="form-control cnpj-mask" id="cnpj" name="cnpj" required>
                            <div class="invalid-feedback">CNPJ inválido</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="prefixo" class="form-label">Prefixo *</label>
                            <input type="text" class="form-control uppercase" id="prefixo" name="prefixo" maxlength="10"
                                required>
                            <div class="invalid-feedback">Máximo 10 caracteres</div>
                            <small class="text-muted">Ex: EMP, ABC, XYZ</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control tel-mask" id="telefone" name="telefone">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="ativa" class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="ativa" name="ativa" value="1"
                                    checked>
                                <label class="form-check-label" for="ativa">Ativa</label>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="endereco" class="form-label">Endereço</label>
                            <textarea class="form-control" id="endereco" name="endereco" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="add_empresa" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal  Editar Empresa -->
<!-- <div class="modal fade" id="editEmpresaModal" tabindex="-1" aria-labelledby="editEmpresaModalLabel"
    style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editEmpresaModalLabel">
                    <i class="fas fa-edit"></i> Editar Empresa
                </h5>
                <a href="empresas.php" class="btn-close"></a>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="id" id="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nome" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control uppercase" id="edit_nome" name="edit-nome"
                                value="<?php echo htmlspecialchars($empresa_edit['nome']); ?>" required>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_cnpj" class="form-label">CNPJ *</label>
                            <input type="text" class="form-control cnpj-mask" id="edit_cnpj" name="cnpj"
                                value="<?php echo $empresa_edit['cnpj']; ?>" required>
                            <div class="invalid-feedback">CNPJ inválido</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_prefixo" class="form-label">Prefixo *</label>
                            <input type="text" class="form-control uppercase" id="edit_prefixo" name="prefixo"
                                value="<?php echo $empresa_edit['prefixo']; ?>" maxlength="10" required>
                            <div class="invalid-feedback">Máximo 10 caracteres</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="edit_email" name="email"
                                value="<?php echo htmlspecialchars($empresa_edit['email']); ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control tel-mask" id="edit_telefone" name="telefone"
                                value="<?php echo htmlspecialchars($empresa_edit['telefone']); ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_ativa" class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_ativa" name="ativa" value="1"
                                    <?php echo $empresa_edit['ativa'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="edit_ativa">Ativa</label>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="edit_endereco" class="form-label">Endereço</label>
                            <textarea class="form-control" id="edit_endereco" name="endereco"
                                rows="3"><?php echo htmlspecialchars($empresa_edit['endereco']); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="empresas.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" name="edit_empresa" class="btn btn-warning">
                        <i class="fas fa-save"></i> Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> -->