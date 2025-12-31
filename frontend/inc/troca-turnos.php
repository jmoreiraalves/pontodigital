<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Verificar autenticação
if (!isAuth()) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Listar colaboradores da empresa para substituição
try {
    $stmt = $pdo->prepare("SELECT id, codigo, nome, turno FROM colaboradores 
                          WHERE empresa_id = ? AND ativo = 1 
                          ORDER BY nome");
    $stmt->execute([$user['empresa_id']]);
    $colaboradores = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar colaboradores: ' . $e->getMessage();
}

// Listar trocas de turno
try {
    $stmt = $pdo->prepare("SELECT t.*, 
                          cs1.nome as substituido_nome, cs1.codigo as substituido_codigo,
                          cs2.nome as substituto_nome, cs2.codigo as substituto_codigo,
                          u.nome as aprovador_nome
                          FROM trocas_turno t
                          JOIN colaboradores cs1 ON t.colaborador_substituido_id = cs1.id
                          JOIN colaboradores cs2 ON t.colaborador_substituto_id = cs2.id
                          LEFT JOIN usuarios u ON t.aprovado_por = u.id
                          WHERE t.empresa_id = ?
                          ORDER BY t.data_troca DESC, t.created_at DESC");
    $stmt->execute([$user['empresa_id']]);
    $trocas = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar trocas de turno: ' . $e->getMessage();
}

// Registrar nova troca de turno
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_troca'])) {
    $colaborador_substituido_id = $_POST['colaborador_substituido_id'] ?? 0;
    $colaborador_substituto_id = $_POST['colaborador_substituto_id'] ?? 0;
    $data_troca = $_POST['data_troca'] ?? '';
    $periodo = $_POST['periodo'] ?? '';
    $motivo = $_POST['motivo'] ?? '';
    
    // Validações
    if (empty($colaborador_substituido_id) || empty($colaborador_substituto_id) || 
        empty($data_troca) || empty($periodo)) {
        $errors[] = 'Preencha todos os campos obrigatórios';
    } elseif ($colaborador_substituido_id == $colaborador_substituto_id) {
        $errors[] = 'O colaborador substituído não pode ser o mesmo que o substituto';
    } else {
        try {
            // Verificar disponibilidade do substituto na data
            $stmt = $pdo->prepare("SELECT id FROM trocas_turno 
                                  WHERE empresa_id = ? 
                                  AND colaborador_substituto_id = ? 
                                  AND data_troca = ? 
                                  AND status = 'aprovado'");
            $stmt->execute([$user['empresa_id'], $colaborador_substituto_id, $data_troca]);
            if ($stmt->fetch()) {
                $errors[] = 'Colaborador substituto já está escalado para outra troca nesta data';
            } else {
                // Inserir troca de turno
                $stmt = $pdo->prepare("INSERT INTO trocas_turno 
                                      (empresa_id, colaborador_substituido_id, colaborador_substituto_id, 
                                       data_troca, periodo, motivo, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, 'pendente')");
                $stmt->execute([
                    $user['empresa_id'],
                    $colaborador_substituido_id,
                    $colaborador_substituto_id,
                    $data_troca,
                    $periodo,
                    $motivo
                ]);
                
                $success = 'Troca de turno registrada com sucesso! Aguardando aprovação.';
                registrar_log('TROCA_TURNO', "Nova troca de turno registrada", $user['id']);
                
                // Recarregar lista
                $stmt = $pdo->prepare("SELECT t.*, 
                                      cs1.nome as substituido_nome, cs1.codigo as substituido_codigo,
                                      cs2.nome as substituto_nome, cs2.codigo as substituto_codigo,
                                      u.nome as aprovador_nome
                                      FROM trocas_turno t
                                      JOIN colaboradores cs1 ON t.colaborador_substituido_id = cs1.id
                                      JOIN colaboradores cs2 ON t.colaborador_substituto_id = cs2.id
                                      LEFT JOIN usuarios u ON t.aprovado_por = u.id
                                      WHERE t.empresa_id = ?
                                      ORDER BY t.data_troca DESC, t.created_at DESC");
                $stmt->execute([$user['empresa_id']]);
                $trocas = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao registrar troca de turno: ' . $e->getMessage();
        }
    }
}

// Aprovar/Recusar troca
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    
    if (in_array($status, ['aprovado', 'recusado'])) {
        try {
            $stmt = $pdo->prepare("UPDATE trocas_turno 
                                  SET status = ?, aprovado_por = ?, updated_at = NOW() 
                                  WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$status, $user['id'], $id, $user['empresa_id']]);
            
            $success = 'Troca de turno ' . $status . ' com sucesso!';
            registrar_log('APROVAR_TROCA', "Troca ID $id $status", $user['id']);
            
            // Recarregar lista
            $stmt = $pdo->prepare("SELECT t.*, 
                                  cs1.nome as substituido_nome, cs1.codigo as substituido_codigo,
                                  cs2.nome as substituto_nome, cs2.codigo as substituto_codigo,
                                  u.nome as aprovador_nome
                                  FROM trocas_turno t
                                  JOIN colaboradores cs1 ON t.colaborador_substituido_id = cs1.id
                                  JOIN colaboradores cs2 ON t.colaborador_substituto_id = cs2.id
                                  LEFT JOIN usuarios u ON t.aprovado_por = u.id
                                  WHERE t.empresa_id = ?
                                  ORDER BY t.data_troca DESC, t.created_at DESC");
            $stmt->execute([$user['empresa_id']]);
            $trocas = $stmt->fetchAll();
        } catch (PDOException $e) {
            $errors[] = 'Erro ao atualizar troca de turno: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Troca de Turnos - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-clock"></i> <?php echo SITE_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $user['nome']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">
                                    <i class="fas fa-building"></i> <?php echo $user['empresa_nome']; ?>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Sidebar e Conteúdo -->
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 sidebar d-md-block">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            
                            <?php if (hasPermission($user['tipo'], 'super')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="empresas.php">
                                    <i class="fas fa-building"></i> Empresas
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="empresa.php">
                                    <i class="fas fa-edit"></i> Minha Empresa
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="colaboradores.php">
                                    <i class="fas fa-users"></i> Colaboradores
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link active" href="turnos.php">
                                    <i class="fas fa-exchange-alt"></i> Troca de Turnos
                                </a>
                            </li>
                            
                            <?php if (hasPermission($user['tipo'], 'ti')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="ti.php">
                                    <i class="fas fa-laptop-code"></i> Profissional T.I.
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="nav-item mt-3">
                                <a class="nav-link btn btn-success" href="registrar_ponto.php">
                                    <i class="fas fa-fingerprint"></i> Registrar Ponto
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <button class="nav-link btn btn-warning mt-2" data-bs-toggle="modal" 
                                        data-bs-target="#facialModal">
                                    <i class="fas fa-camera"></i> Reconhecimento Facial
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Conteúdo Principal -->
                <main class="col-md-9 col-lg-10 main-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">
                            <i class="fas fa-exchange-alt"></i> Troca de Turnos
                        </h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                    data-bs-target="#addTrocaModal">
                                <i class="fas fa-exchange-alt"></i> Nova Troca
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

                    <!-- Nova Troca de Turno -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plus-circle"></i> Registrar Nova Troca de Turno
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="colaborador_substituido_id" class="form-label">
                                            Colaborador Substituído *
                                        </label>
                                        <select class="form-select" id="colaborador_substituido_id" 
                                                name="colaborador_substituido_id" required>
                                            <option value="">Selecione o colaborador...</option>
                                            <?php foreach ($colaboradores as $colab): ?>
                                            <option value="<?php echo $colab['id']; ?>">
                                                <?php echo htmlspecialchars($colab['nome']); ?> 
                                                (<?php echo $colab['codigo']; ?> - <?php echo $colab['turno']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="colaborador_substituto_id" class="form-label">
                                            Colaborador Substituto *
                                        </label>
                                        <select class="form-select" id="colaborador_substituto_id" 
                                                name="colaborador_substituto_id" required>
                                            <option value="">Selecione o colaborador...</option>
                                            <?php foreach ($colaboradores as $colab): ?>
                                            <option value="<?php echo $colab['id']; ?>">
                                                <?php echo htmlspecialchars($colab['nome']); ?> 
                                                (<?php echo $colab['codigo']; ?> - <?php echo $colab['turno']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="data_troca" class="form-label">Data da Troca *</label>
                                        <input type="date" class="form-control" id="data_troca" name="data_troca" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="periodo" class="form-label">Período *</label>
                                        <select class="form-select" id="periodo" name="periodo" required>
                                            <option value="manha">Manhã</option>
                                            <option value="tarde">Tarde</option>
                                            <option value="noite">Noite</option>
                                            <option value="dia_inteiro">Dia Inteiro</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <label for="motivo" class="form-label">Motivo da Troca</label>
                                        <textarea class="form-control" id="motivo" name="motivo" rows="3" 
                                                  placeholder="Descreva o motivo da troca de turno..."></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="d-grid">
                                            <button type="submit" name="add_troca" class="btn btn-primary btn-lg">
                                                <i class="fas fa-exchange-alt"></i> Registrar Troca
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Trocas -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history"></i> Histórico de Trocas de Turno
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Substituído</th>
                                            <th>Substituto</th>
                                            <th>Período</th>
                                            <th>Status</th>
                                            <th>Aprovador</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trocas as $troca): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($troca['data_troca'])); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $troca['substituido_codigo']; ?></span>
                                                <?php echo htmlspecialchars($troca['substituido_nome']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $troca['substituto_codigo']; ?></span>
                                                <?php echo htmlspecialchars($troca['substituto_nome']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $periodos = [
                                                    'manha' => 'Manhã',
                                                    'tarde' => 'Tarde',
                                                    'noite' => 'Noite',
                                                    'dia_inteiro' => 'Dia Inteiro'
                                                ];
                                                echo $periodos[$troca['periodo']] ?? $troca['periodo'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($troca['status'] == 'pendente'): ?>
                                                    <span class="badge bg-warning">Pendente</span>
                                                <?php elseif ($troca['status'] == 'aprovado'): ?>
                                                    <span class="badge bg-success">Aprovado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Recusado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $troca['aprovador_nome'] ?? '—'; ?>
                                            </td>
                                            <td>
                                                <?php if ($troca['status'] == 'pendente'): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=aprove&id=<?php echo $troca['id']; ?>&status=aprovado" 
                                                       class="btn btn-success" title="Aprovar">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="?action=reject&id=<?php echo $troca['id']; ?>&status=recusado" 
                                                       class="btn btn-danger" title="Recusar">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                                <?php else: ?>
                                                <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($trocas)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Nenhuma troca de turno registrada
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Modal Reconhecimento Facial -->
    <div class="modal fade" id="facialModal" tabindex="-1" aria-labelledby="facialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="facialModalLabel">
                        <i class="fas fa-camera"></i> Reconhecimento Facial
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="camera-preview mb-3" style="width: 100%; height: 300px; background: #000; border-radius: 10px; overflow: hidden;">
                            <div class="text-white d-flex align-items-center justify-content-center h-100">
                                <div>
                                    <i class="fas fa-camera fa-5x mb-3"></i>
                                    <p>Área de captura facial</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted">
                            Posicione seu rosto dentro da área destacada e aguarde o reconhecimento.
                        </p>
                    </div>
                    
                    <div id="facialStatus"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="iniciarReconhecimentoFacial()">
                        <i class="fas fa-play"></i> Iniciar Reconhecimento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript Personalizado -->
    <script src="assets/js/script.js"></script>
</body>
</html>
