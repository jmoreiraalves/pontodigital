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

// Obter estatísticas
try {

    // Total de colaboradores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE empresa_id = {$empresaid} AND ativo = 1");
    // $stmt->execute([(int)$empresaid]);
    // $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE ativo = 1");
    $stmt->execute();
    $total_colaboradores = $stmt->fetch()['total'];
    // var_dump($empresaid);


    //echo 'Mostrando os dados dashboard : ' . $empresaid;    

    // Pontos hoje
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM registros_ponto 
                          WHERE empresa_id = {$empresaid}  AND data_registro = CURDATE()");
    $stmt->execute();
    $pontos_hoje = $stmt->fetch()['total'];

    // Colaboradores ativos hoje
    $stmt = $pdo->query("SELECT COUNT(DISTINCT colaborador_id) as total FROM registros_ponto 
                          WHERE empresa_id = {$empresaid}  AND data_registro = CURDATE()");
    $stmt->execute();
    $ativos_hoje = $stmt->fetch()['total'];

    // Últimos registros
    $stmt = $pdo->query("SELECT r.*, c.nome as colaborador_nome, c.codigo as colaborador_codigo 
                          FROM registros_ponto r 
                          JOIN colaboradores c ON r.colaborador_id = c.id 
                          WHERE r.empresa_id = {$empresaid} 
                          ORDER BY r.created_at DESC 
                          LIMIT 10");
    $stmt->execute();
    $ultimos_registros = $stmt->fetchAll();

    // Por turno
    $stmt = $pdo->query("SELECT c.turno, COUNT(*) as total 
                          FROM colaboradores c 
                          WHERE c.empresa_id = {$empresaid}  AND c.ativo = 1 
                          GROUP BY c.turno");
    $stmt->execute();
    $por_turno = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar estatísticas: ' . $e->getMessage();
}
?>


<!-- Estatísticas -->
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <div class="number">
                    <?= $total_colaboradores; ?>
                </div>
                <div class="label">Colaboradores</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body">
                <i class="fas fa-clock fa-3x text-success mb-3"></i>
                <div class="number">
                    <?php echo $pontos_hoje; ?>
                </div>
                <div class="label">Pontos Hoje</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body">
                <i class="fas fa-user-check fa-3x text-warning mb-3"></i>
                <div class="number">
                    <?php echo $ativos_hoje; ?>
                </div>
                <div class="label">Ativos Hoje</div>
            </div>
        </div>
    </div>

    <!-- <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <i class="fas fa-building fa-3x text-info mb-3"></i>
                    <div class="number"><?php echo $user['empresa_prefixo']; ?></div>
                    <div class="label">Empresa</div>
                </div>
            </div>
        </div> -->
</div>

<!-- Últimos Registros -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history"></i> Últimos Registros de Ponto
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_registros as $registro): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($registro['colaborador_nome']); ?>
                                    </td>
                                    <td><span class="badge bg-primary">
                                            <?php echo $registro['colaborador_codigo']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge 
                                                            <?php echo $registro['tipo'] == 'entrada' ? 'bg-success' :
                                                                ($registro['tipo'] == 'saida' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                            <?php echo getTiposPonto()[$registro['tipo']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($registro['data_registro'])); ?>
                                    </td>
                                    <td>
                                        <?php echo $registro['hora_registro']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribuição por Turno -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie"></i> Distribuição por Turno
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($por_turno as $turno): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php
                            $turnos = getTurnos();
                            echo $turnos[$turno['turno']] ?? $turno['turno'];
                            ?>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo $turno['total']; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>