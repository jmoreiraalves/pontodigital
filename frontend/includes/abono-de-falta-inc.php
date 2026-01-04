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

$empresa_id = $_SESSION['empresa_id'];

// Conectar ao banco de dados
$database = new Database();
$pdo = $database->getConnection();

// Processar registro de abono
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_abono'])) {
    $colaborador_id = filter_input(INPUT_POST, 'colaborador_id', FILTER_VALIDATE_INT);
    $data_abono = filter_input(INPUT_POST, 'data_abono', FILTER_SANITIZE_STRING);
    $motivo = filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_STRING);

    if ($colaborador_id && $data_abono) {
        try {
            // Buscar informações do colaborador
            $stmt = $pdo->prepare("SELECT codigo, nome FROM colaboradores WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$colaborador_id, $empresa_id]);
            $colaborador = $stmt->fetch();

            if ($colaborador) {
                // Registrar abono (marcar registros do dia como abonados)
                $pdo->beginTransaction();

                // Atualizar todos os registros do colaborador na data especificada
                $sqlUpdate = "UPDATE registros_ponto 
                             SET abonado_atestado = 1, 
                                 atestado_id = NULL 
                             WHERE colaborador_id = ? 
                             AND empresa_id = ? 
                             AND data_registro = ?";

                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([$colaborador_id, $empresa_id, $data_abono]);

                // Registrar o abono em uma tabela de histórico (se existir)
                // Se não existir, podemos criar um registro de log
                $sqlLog = "INSERT INTO abonos_faltas 
                          (colaborador_id, empresa_id, data_abono, motivo, registrado_por, registrado_em) 
                          VALUES (?, ?, ?, ?, ?, NOW())";

                // Verificar se a tabela existe, senão criar um log alternativo
                try {
                    $stmtLog = $pdo->prepare($sqlLog);
                    $stmtLog->execute([
                        $colaborador_id,
                        $empresa_id,
                        $data_abono,
                        $motivo,
                        $_SESSION['usuario_id']
                    ]);
                } catch (Exception $e) {
                    // Tabela não existe, apenas continue
                    error_log("Tabela abonos_faltas não existe: " . $e->getMessage());
                }

                $pdo->commit();

                $mensagem_sucesso = "Abono registrado com sucesso para " . htmlspecialchars($colaborador['nome']) . " na data " . date('d/m/Y', strtotime($data_abono));
            } else {
                $mensagem_erro = "Colaborador não encontrado ou não pertence a esta empresa.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_erro = "Erro ao registrar abono: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $mensagem_erro = "Preencha todos os campos obrigatórios.";
    }
}

// Buscar últimos 10 abonos (considerando registros marcados como abonados)
$sqlAbonos = "SELECT 
                rp.colaborador_id,
                c.nome as colaborador_nome,
                c.codigo as colaborador_codigo,
                rp.data_registro,
                COUNT(rp.id) as total_registros,
                MIN(rp.hora_registro) as primeira_marcacao,
                MAX(rp.hora_registro) as ultima_marcacao
              FROM registros_ponto rp
              INNER JOIN colaboradores c ON rp.colaborador_id = c.id
              WHERE rp.empresa_id = :empresa_id 
              AND rp.abonado_atestado = 1
              GROUP BY rp.colaborador_id, rp.data_registro
              ORDER BY rp.data_registro DESC, rp.created_at DESC
              LIMIT 10";

$stmtAbonos = $pdo->prepare($sqlAbonos);
$stmtAbonos->execute([':empresa_id' => $empresa_id]);
$abonos = $stmtAbonos->fetchAll();

// Buscar colaboradores ativos para o modal
$sqlColaboradores = "SELECT id, codigo, nome, cpf 
                     FROM colaboradores 
                     WHERE empresa_id = :empresa_id 
                     AND ativo = 1
                     ORDER BY nome";

$stmtColaboradores = $pdo->prepare($sqlColaboradores);
$stmtColaboradores->execute([':empresa_id' => $empresa_id]);
$colaboradores = $stmtColaboradores->fetchAll();
?>


<!-- Mensagens de feedback -->
<?php if (isset($mensagem_sucesso)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo htmlspecialchars($mensagem_sucesso); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($mensagem_erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($mensagem_erro); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


<div class="row">
    <div class="col-lg-4">
        <button type="button" class="btn btn-primary " data-bs-toggle="modal" data-bs-target="#modalRegistrarAbono">
            Registrar abono
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover" id="colaboradoresTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CÓDIGO</th>
                <th>DATA</th>
                <th>TOTAL</th>
                <th>PRIMEIRA</th>
                <th>ÚLTIMA</th>
                <!-- <th>Ações</th> -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($abonos as $colaborador): ?>
                <tr>
                    <td><?php echo $colaborador['colaborador_id']; ?></td>
                    <td><?php echo $colaborador['colaborador_nome']; ?></td>
                    <td><?php echo $colaborador['colaborador_codigo']; ?></td>
                    <td><?php echo $colaborador['data_registro']; ?></td>
                    <td><?php echo $colaborador['total_registros']; ?></td>
                    <td><?php echo $colaborador['primeira_marcacao']; ?></td>
                    <td><?php echo $colaborador['ultima_marcacao']; ?></td>
                    <!-- <td>
                        <button type="button" class="btn btn-sm btn-warning btneditColaboradorModals"                              
                            data-id=<?= $colaborador['id'] ?>
                            >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btndeleteColaboradorModals"
                            data-id=<?= $colaborador['id'] ?>
                            >
                            <i class="bi bi-trash"></i>
                        </button>
                    </td> -->
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalRegistrarAbono" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Registro de Abono de falta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formRegistrarAbono" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="colaborador_id" class="form-label">Colaborador *</label>
                            <select class="form-select" id="colaborador_id" name="colaborador_id" required>
                                <option value="">Selecione um colaborador...</option>
                                <?php foreach ($colaboradores as $colaborador): ?>
                                    <option value="<?php echo htmlspecialchars($colaborador['id']); ?>">
                                        <?php echo htmlspecialchars($colaborador['codigo']); ?> -
                                        <?php echo htmlspecialchars($colaborador['nome']); ?>
                                        (<?php echo htmlspecialchars($colaborador['cpf']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione o colaborador que terá a falta abonada</div>
                        </div>
                        <div class="col-md-6">
                            <label for="data_abono" class="form-label">Data do Abono *</label>
                            <input type="date" class="form-control" id="data_abono" name="data_abono"
                                value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            <div class="form-text">Selecione a data da falta a ser abonada</div>
                        </div>
                        <div class="col-12">
                            <label for="motivo" class="form-label">Motivo do Abono</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3"
                                placeholder="Descreva o motivo do abono (opcional)"></textarea>
                            <div class="form-text">Ex: Atestado médico, problemas pessoais, etc.</div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Atenção:</strong> Ao registrar o abono, todos os registros de ponto do
                                colaborador na data selecionada serão marcados como abonados.
                            </div>
                        </div>

                    </div> <!-- row g-3 -->
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