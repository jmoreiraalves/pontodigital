<?php
declare(strict_types=1);

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$empresaid = $_SESSION['empresa_id'];

$pdo = new Database();


// Ações CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// Listar empresas
try {
    $stmt = $pdo->query("SELECT * FROM empresas ORDER BY nome");
    $stmt->execute();
    $empresas = $stmt->fetchAll();
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
    } elseif (!validar_cnpj($cnpj)) {
        $errors[] = 'CNPJ inválido';
    } elseif (strlen($prefixo) > 10) {
        $errors[] = 'Prefixo deve ter no máximo 10 caracteres';
    } else {
        try {
            // Verificar se CNPJ já existe
            $stmt = $pdo->prepare("SELECT id FROM empresas WHERE cnpj = ?");
            $stmt->execute([$cnpj]);
            if ($stmt->fetch()) {
                $errors[] = 'CNPJ já cadastrado';
            } else {
                // Verificar se prefixo já existe
                $stmt = $pdo->prepare("SELECT id FROM empresas WHERE prefixo = ?");
                $stmt->execute([$prefixo]);
                if ($stmt->fetch()) {
                    $errors[] = 'Prefixo já em uso';
                } else {
                    // Inserir empresa
                    $stmt = $pdo->prepare("INSERT INTO empresas (nome, cnpj, prefixo, endereco, telefone, email, ativa) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nome, formatar_cnpj($cnpj), $prefixo, $endereco, $telefone, $email, $ativa]);

                    $success = 'Empresa cadastrada com sucesso!';
                    registrar_log('CADASTRO_EMPRESA', "Nova empresa: $nome", $user['id']);

                    // Recarregar lista
                    $stmt = $pdo->prepare("SELECT * FROM empresas ORDER BY nome");
                    $stmt->execute();
                    $empresas = $stmt->fetchAll();
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao cadastrar empresa: ' . $e->getMessage();
        }
    }
}

// Editar empresa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_empresa'])) {
    $id = $_POST['id'] ?? 0;
    $nome = toUpperCase($_POST['nome'] ?? '');
    $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
    $prefixo = toUpperCase($_POST['prefixo'] ?? '');
    $endereco = $_POST['endereco'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $ativa = isset($_POST['ativa']) ? 1 : 0;

    if (empty($nome) || empty($cnpj) || empty($prefixo)) {
        $errors[] = 'Preencha todos os campos obrigatórios';
    } else {
        try {
            // Verificar se CNPJ já existe (excluindo a própria empresa)
            $stmt = $pdo->prepare("SELECT id FROM empresas WHERE cnpj = ? AND id != ?");
            $stmt->execute([$cnpj, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'CNPJ já cadastrado em outra empresa';
            } else {
                // Verificar se prefixo já existe (excluindo a própria empresa)
                $stmt = $pdo->prepare("SELECT id FROM empresas WHERE prefixo = ? AND id != ?");
                $stmt->execute([$prefixo, $id]);
                if ($stmt->fetch()) {
                    $errors[] = 'Prefixo já em uso por outra empresa';
                } else {
                    // Atualizar empresa
                    $stmt = $pdo->prepare("UPDATE empresas 
                                          SET nome = ?, cnpj = ?, prefixo = ?, endereco = ?, 
                                              telefone = ?, email = ?, ativa = ?, updated_at = NOW() 
                                          WHERE id = ?");
                    $stmt->execute([$nome, formatar_cnpj($cnpj), $prefixo, $endereco, $telefone, $email, $ativa, $id]);

                    $success = 'Empresa atualizada com sucesso!';
                    registrar_log('EDITAR_EMPRESA', "Empresa ID $id atualizada", $user['id']);

                    // Recarregar lista
                    $stmt = $pdo->prepare("SELECT * FROM empresas ORDER BY nome");
                    $stmt->execute();
                    $empresas = $stmt->fetchAll();
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao atualizar empresa: ' . $e->getMessage();
        }
    }
}

// Excluir empresa
if ($action == 'delete' && $id > 0) {
    try {
        // Verificar se empresa tem usuários ou colaboradores
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE empresa_id = ?");
        $stmt->execute([$id]);
        $total_usuarios = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM colaboradores WHERE empresa_id = ?");
        $stmt->execute([$id]);
        $total_colaboradores = $stmt->fetch()['total'];

        if ($total_usuarios > 0 || $total_colaboradores > 0) {
            $errors[] = 'Não é possível excluir empresa com usuários ou colaboradores vinculados';
        } else {
            $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = ?");
            $stmt->execute([$id]);

            $success = 'Empresa excluída com sucesso!';
            registrar_log('EXCLUIR_EMPRESA', "Empresa ID $id excluída", $user['id']);

            // Recarregar lista
            $stmt = $pdo->prepare("SELECT * FROM empresas ORDER BY nome");
            $stmt->execute();
            $empresas = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $errors[] = 'Erro ao excluir empresa: ' . $e->getMessage();
    }
}

// Obter empresa para edição
$empresa_edit = null;
if ($action == 'edit' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmt->execute([$id]);
        $empresa_edit = $stmt->fetch();

        if (!$empresa_edit) {
            $errors[] = 'Empresa não encontrada';
            $action = '';
        }
    } catch (PDOException $e) {
        $errors[] = 'Erro ao carregar empresa: ' . $e->getMessage();
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
                                    <a href="?action=edit&id=<?php echo $empresa['id']; ?>" class="btn btn-sm btn-warning"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $empresa['id']; ?>"
                                        class="btn btn-sm btn-danger btn-delete" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>


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

<!-- Modal Editar Empresa -->
<?php if ($action == 'edit' && $empresa_edit): ?>
    <div class="modal fade show" id="editEmpresaModal" tabindex="-1" aria-labelledby="editEmpresaModalLabel"
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
                    <input type="hidden" name="id" value="<?php echo $empresa_edit['id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_nome" class="form-label">Nome da Empresa *</label>
                                <input type="text" class="form-control uppercase" id="edit_nome" name="nome"
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
    </div>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- JavaScript Personalizado -->
<script src="assets/js/script.js"></script>

<script>
    <?php if ($action == 'edit' && $empresa_edit): ?>
        // Mostrar modal de edição automaticamente
        document.addEventListener('DOMContentLoaded', function () {
            var modal = new bootstrap.Modal(document.getElementById('editEmpresaModal'));
            modal.show();
        });
    <?php endif; ?>
</script>
</body>

</html>