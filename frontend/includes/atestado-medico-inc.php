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

// Processar pesquisa
$filtro_colaborador = $_GET['colaborador_id'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

// Construir query base
$query = "SELECT a.*, c.nome as colaborador_nome, c.codigo as colaborador_codigo 
          FROM atestados_medicos a
          INNER JOIN colaboradores c ON a.colaborador_id = c.id
          WHERE 1=1";

$params = [];

if (!empty($filtro_colaborador)) {
    $query .= " AND a.colaborador_id = ?";
    $params[] = $filtro_colaborador;
}

if (!empty($filtro_data_inicio)) {
    $query .= " AND a.data_fim >= ?";
    $params[] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $query .= " AND a.data_inicio <= ?";
    $params[] = $filtro_data_fim;
}

$query .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$atestados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar colaboradores para o select
$colaboradores = $pdo->query("SELECT id, nome, codigo FROM colaboradores WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-funnel"></i> Filtros de Pesquisa
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="colaborador_id" class="form-label">Colaborador</label>
                        <select class="form-select" id="colaborador_id" name="colaborador_id">
                            <option value="">Todos os colaboradores</option>
                            <?php foreach ($colaboradores as $colab): ?>
                                <option value="<?= $colab['id'] ?>" <?= $filtro_colaborador == $colab['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($colab['nome']) ?> (<?= $colab['codigo'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="data_inicio" class="form-label">Data Início (do atestado)</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $filtro_data_inicio ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="data_fim" class="form-label">Data Fim (do atestado)</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $filtro_data_fim ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Pesquisar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Atestados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-list-ul"></i> Lista de Atestados
                    <span class="badge bg-secondary ms-2"><?= count($atestados) ?> registros</span>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCadastroAtestado">
                    <i class="bi bi-plus-circle"></i> Novo Atestado
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tabelaAtestados">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Colaborador</th>
                                <th>Período</th>
                                <th>Dias</th>
                                <th>CID</th>
                                <th>Data Emissão</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atestados as $atestado): 
                                $data_atual = date('Y-m-d');
                                $status = '';
                                if ($atestado['data_inicio'] > $data_atual) {
                                    $status = 'badge-futuro';
                                    $status_text = 'Futuro';
                                } elseif ($atestado['data_fim'] < $data_atual) {
                                    $status = 'badge-encerrado';
                                    $status_text = 'Encerrado';
                                } else {
                                    $status = 'badge-vigente';
                                    $status_text = 'Vigente';
                                }
                            ?>
                            <tr>
                                <td><?= $atestado['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($atestado['colaborador_nome']) ?></strong><br>
                                    <small class="text-muted"><?= $atestado['colaborador_codigo'] ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($atestado['data_inicio'])) ?> 
                                    a <br>
                                    <?= date('d/m/Y', strtotime($atestado['data_fim'])) ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $atestado['dias_afastamento'] ?> dias</span>
                                    <?php if ($atestado['horas_afastamento'] > 0): ?>
                                        <br><small><?= $atestado['horas_afastamento'] ?> horas</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($atestado['cid'] ?? 'Não informado') ?></td>
                                <td><?= date('d/m/Y', strtotime($atestado['data_emissao'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $status ?>"><?= $status_text ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="visualizarAtestado(<?= $atestado['id'] ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="exportar_atestado.php?id=<?= $atestado['id'] ?>" class="btn btn-sm btn-warning" target="_blank">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $atestado['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro -->
    <div class="modal fade" id="modalCadastroAtestado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-medical"></i> Cadastrar Novo Atestado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formCadastroAtestado" action="#" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="cadastrar">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cad_colaborador_id" class="form-label">Colaborador *</label>
                                <select class="form-select" id="cad_colaborador_id" name="colaborador_id" required>
                                    <option value="">Selecione um colaborador</option>
                                    <?php foreach ($colaboradores as $colab): ?>
                                        <option value="<?= $colab['id'] ?>">
                                            <?= htmlspecialchars($colab['nome']) ?> (<?= $colab['codigo'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cid" class="form-label">CID (opcional)</label>
                                <input type="text" class="form-control" id="cid" name="cid" maxlength="10" placeholder="Ex: A00.0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="data_emissao" class="form-label">Data de Emissão *</label>
                                <input type="date" class="form-control" id="data_emissao" name="data_emissao" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="data_inicio" class="form-label">Data Início Afastamento *</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="data_fim" class="form-label">Data Fim Afastamento *</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="dias_afastamento" class="form-label">Dias de Afastamento *</label>
                                <input type="number" class="form-control" id="dias_afastamento" name="dias_afastamento" min="1" max="365" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="horas_afastamento" class="form-label">Horas de Afastamento (opcional)</label>
                                <input type="number" class="form-control" id="horas_afastamento" name="horas_afastamento" min="0" max="24">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="diagnostico" class="form-label">Diagnóstico / Observações</label>
                            <textarea class="form-control" id="diagnostico" name="diagnostico" rows="3" placeholder="Descreva o diagnóstico ou observações..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="arquivo_anexo" class="form-label">Anexar Atestado (opcional)</label>
                            <input type="file" class="form-control" id="arquivo_anexo" name="arquivo_anexo" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Formatos permitidos: PDF, JPG, PNG (máx. 5MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Atestado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Visualização -->
    <div class="modal fade" id="modalVisualizarAtestado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Detalhes do Atestado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalhesAtestado">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
