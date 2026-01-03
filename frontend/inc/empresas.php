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

try {
    $empresas = $database->fetchAll("SELECT * FROM empresas ORDER BY nome");
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar empresas: ' . $e->getMessage();
}

?>
<h1 class="h3 mb-4 text-gray-800">Empresas</h1>

<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEmpresaModals">
    Nova Empresa
</button>

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
                    <td><span class="badge bg-default"><?php echo $empresa['prefixo']; ?></span></td>
                    <td><?php echo htmlspecialchars($empresa['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $empresa['ativa'] ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $empresa['ativa'] ? 'Ativa' : 'Inativa'; ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" id="btneditEmpresaModal" class="btn btn-sm btn-warning" data-toggle="modal"
                            data-target="#editEmpresaModals" data-id=<?= $empresa['id'] ?> data-nome=<?= $empresa['nome'] ?>
                            data-cnpj=<?= $empresa['cnpj'] ?> data-prefixo=<?= $empresa['prefixo'] ?>
                            data-endereco=<?= $empresa['endereco'] ?> data-email=<?= $empresa['email'] ?>>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" id="btndeleteEmpresaModals" class="btn btn-sm btn-danger" data-toggle="modal"
                            data-target="#deleteEmpresaModals" data-id=<?= $empresa['id'] ?> data-nome=<?= $empresa['nome'] ?>
                            data-cnpj=<?= $empresa['cnpj'] ?> data-prefixo=<?= $empresa['prefixo'] ?>
                            data-endereco=<?= $empresa['endereco'] ?> data-email=<?= $empresa['email'] ?>>
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addEmpresaModals" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Nova Empresa</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="add_empresa" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
            <!-- ./modal-content -->
        </div>
    </div>
</div>

<div class="modal fade" id="editEmpresaModals" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Modal de Teste</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Teste do modal
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="add_empresa" class="btn btn-warning">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
            <!-- ./modal-content -->
        </div>
    </div>
</div>

<div class="modal fade" id="deleteEmpresaModals" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Modal de Teste</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Teste do modal
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="add_empresa" class="btn btn-danger">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
            <!-- ./modal-content -->
        </div>
    </div>
</div>