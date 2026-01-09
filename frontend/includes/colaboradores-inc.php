<?php

declare(strict_types=1);

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    // header('Location: ../logout.php');
    $url = 'logout.php';
    echo '<script>';
    echo 'window.location.href = "' . $url . '";';
    echo '</script>';
    exit();
}

$empresaid = $_SESSION['empresa_id'];

////conectar o bando de dados
$database = new Database();
$pdo = $database->getConnection();

$stmt = $pdo->query("SELECT * FROM colaboradores WHERE ativo = 1 AND empresa_id = {$empresaid}  ORDER BY nome ASC");
$stmt->execute();
$colaboradores = $stmt->fetchAll();

//var_dump($colaboradores);

?>

<div class="row">
    <div class="col-md-4 col-sm-12">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addColaboradorModals">
            Novo colaborador
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover" id="colaboradoresTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Código</th>
                <th>Turno</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colaboradores as $colaborador): ?>
                <tr>
                    <td><?php echo $colaborador['id']; ?></td>
                    <td><?php echo htmlspecialchars($colaborador['nome']); ?></td>
                    <td><?php echo $colaborador['cpf']; ?></td>
                    <td><span class="badge bg-warning"><?php echo $colaborador['codigo']; ?></span></td>
                    <td><?php echo htmlspecialchars($colaborador['turno']); ?></td>
                    <td>
                        <span class="badge <?php echo $colaborador['ativo'] ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $colaborador['ativo'] ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning btneditColaboradorModals"
                            data-id=<?= $colaborador['id'] ?>>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btndeleteColaboradorModals"
                            data-id=<?= $colaborador['id'] ?>>
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addColaboradorModals" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Novo Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formNovaColaborador" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome do Colaborador *</label>
                            <input type="text" class="form-control uppercase" id="nome" name="nome" required>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="cpf" class="form-label">CPF *</label>
                            <input type="text" class="form-control cpf-mask" id="cpf" name="cpf" required>
                            <div class="invalid-feedback">CPF inválido</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="codigo" class="form-label">Código *</label>
                            <input type="text" class="form-control uppercase" id="codigo" name="codigo" maxlength="10"
                                required>
                            <div class="invalid-feedback">Máximo 10 caracteres</div>
                            <small class="text-muted">Ex: EMP, ABC, XYZ</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Turno</label>
                            <select class="form-control" id="turno" name="turno">
                                <option value="">Selecione o Turno</option>
                                <option value="matutino">Matutino</option>
                                <option value="vespertino">Vespertino</option>
                                <option value="Noturno">Noturno</option>
                                <option value="flexivel">Flexível</option>
                            </select>
                        </div>


                        <div class="col-md-6 mb-3">
                            <label for="ativa" class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1"
                                    checked>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="add_colaborador" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
            <!-- ./modal-content -->
        </div>
    </div>
</div>

<div class="modal fade" id="editColaboradorModals" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Editar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formeditColaborador" class="needs-validation" novalidate>
                <input type="hidden" name="update_id" id="update_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome do Colaborador *</label>
                            <input type="text" class="form-control uppercase" id="update_nome" name="update_nome"
                                required>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="cpf" class="form-label">CPF *</label>
                            <input type="text" class="form-control cpf-mask" id="update_cpf" name="update_cpf" required>
                            <div class="invalid-feedback">CPF inválido</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="codigo" class="form-label">Código *</label>
                            <input type="text" class="form-control uppercase" id="update_codigo" name="update_codigo"
                                maxlength="10" required>
                            <div class="invalid-feedback">Máximo 10 caracteres</div>
                            <small class="text-muted">Ex: EMP, ABC, XYZ</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="update_turno" class="form-label">Turno</label>
                            <select class="form-control" id="update_turno" name="update_turno">
                                <option value="">Selecione o Turno</option>
                                <option value="matutino">Matutino</option>
                                <option value="vespertino">Vespertino</option>
                                <option value="Noturno">Noturno</option>
                                <option value="flexivel">Flexível</option>
                            </select>
                        </div>


                        <div class="col-md-6 mb-3">
                            <label for="update_ativo" class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="update_ativo" name="update_ativo"
                                    value="1" checked>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="edit_colaborador" class="btn btn-warning">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
            <!-- ./modal-content -->
        </div>
    </div>
</div>

<div class="modal fade" id="deleteColaboradorModals" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Excluir colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formdeleteColaborador" class="needs-validation" novalidate>
                <input type="hidden" name="delete_id" id="delete_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nome" class="form-label">Nome do Colaborador *</label>
                            <input type="text" class="form-control uppercase" id="delete_nome" name="delete_nome"
                                required>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="cpf" class="form-label">CPF *</label>
                            <input type="text" class="form-control cpf-mask" id="delete_cpf" name="delete_cpf" required>
                            <div class="invalid-feedback">CPF inválido</div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="codigo" class="form-label">Código *</label>
                            <input type="text" class="form-control uppercase" id="delete_codigo" name="delete_codigo"
                                maxlength="10" required>
                            <div class="invalid-feedback">Máximo 10 caracteres</div>
                            <small class="text-muted">Ex: EMP, ABC, XYZ</small>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="del_colaborador" class="btn btn-danger">
                        <i class="fas fa-save"></i> Excluir
                    </button>
                </div>
            </form>
            <!-- ./modal-content -->
        </div>
    </div>
</div>