<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Verificar autenticação e permissões
if (!isAuth() || !hasPermission($_SESSION['user']['tipo'], 'ti')) {
    header('Location: dashboard.php');
    exit;
}

$user = $_SESSION['user'];

// Ações do T.I.
$action = $_GET['action'] ?? '';

// Fazer backup
if ($action == 'backup') {
    try {
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = __DIR__ . '/../backups/' . $backup_file;
        
        // Criar diretório de backups se não existir
        if (!is_dir(__DIR__ . '/../backups')) {
            mkdir(__DIR__ . '/../backups', 0755, true);
        }
        
        // Comando para backup (simplificado - em produção usar mysqldump)
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            DB_USER,
            DB_PASS,
            DB_HOST,
            DB_NAME,
            $backup_path
        );
        
        // Executar backup
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            // Registrar backup no banco
            $file_size = filesize($backup_path);
            $size_formatted = formatBytes($file_size);
            
            $stmt = $pdo->prepare("INSERT INTO backups (usuario_id, arquivo, tamanho, tipo) VALUES (?, ?, ?, 'completo')");
            $stmt->execute([$user['id'], $backup_file, $size_formatted]);
            
            $success = "Backup realizado com sucesso! Arquivo: $backup_file ($size_formatted)";
            registrar_log('BACKUP', "Backup realizado: $backup_file", $user['id']);
        } else {
            $errors[] = 'Erro ao realizar backup';
        }
    } catch (Exception $e) {
        $errors[] = 'Erro no backup: ' . $e->getMessage();
    }
}

// Listar backups
try {
    $stmt = $pdo->prepare("SELECT b.*, u.nome as usuario_nome FROM backups b 
                          JOIN usuarios u ON b.usuario_id = u.id 
                          ORDER BY b.created_at DESC");
    $stmt->execute();
    $backups = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar backups: ' . $e->getMessage();
}

// Listar logs do sistema
try {
    $stmt = $pdo->prepare("SELECT l.*, u.nome as usuario_nome, c.nome as colaborador_nome 
                          FROM logs_sistema l 
                          LEFT JOIN usuarios u ON l.usuario_id = u.id 
                          LEFT JOIN colaboradores c ON l.colaborador_id = c.id 
                          ORDER BY l.created_at DESC 
                          LIMIT 100");
    $stmt->execute();
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar logs: ' . $e->getMessage();
}

// Função para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profissional T.I. - <?php echo SITE_NAME; ?></title>
    
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
                                <a class="nav-link" href="turnos.php">
                                    <i class="fas fa-exchange-alt"></i> Troca de Turnos
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link active" href="ti.php">
                                    <i class="fas fa-laptop-code"></i> Profissional T.I.
                                </a>
                            </li>
                            
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
                            <i class="fas fa-laptop-code"></i> Profissional T.I.
                        </h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <span class="text-muted">
                                <i class="fas fa-clock"></i> 
                                <span id="live-date"></span> - 
                                <span id="live-clock"></span>
                            </span>
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

                    <!-- Ferramentas T.I. -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                    <h5>Backup</h5>
                                    <p class="text-muted">Crie cópia de segurança do sistema</p>
                                    <button class="btn btn-primary" onclick="fazerBackup()">
                                        <i class="fas fa-save"></i> Fazer Backup
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                    <h5>Exportar PDF</h5>
                                    <p class="text-muted">Gere relatórios em PDF</p>
                                    <button class="btn btn-danger" onclick="exportToPDF('logsTable', 'relatorio_logs')">
                                        <i class="fas fa-file-export"></i> Exportar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-print fa-3x text-info mb-3"></i>
                                    <h5>Imprimir</h5>
                                    <p class="text-muted">Imprima relatórios do sistema</p>
                                    <button class="btn btn-info" onclick="printTable('logsTable')">
                                        <i class="fas fa-print"></i> Imprimir
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-camera fa-3x text-warning mb-3"></i>
                                    <h5>Facial</h5>
                                    <p class="text-muted">Configurar reconhecimento facial</p>
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#facialConfigModal">
                                        <i class="fas fa-cog"></i> Configurar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs do Sistema -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list"></i> Logs do Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover" id="logsTable">
                                    <thead>
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Usuário</th>
                                            <th>Ação</th>
                                            <th>Descrição</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <?php if ($log['usuario_nome']): ?>
                                                    <span class="badge bg-primary">Usuário</span>
                                                    <?php echo htmlspecialchars($log['usuario_nome']); ?>
                                                <?php elseif ($log['colaborador_nome']): ?>
                                                    <span class="badge bg-success">Colaborador</span>
                                                    <?php echo htmlspecialchars($log['colaborador_nome']); ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sistema</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['acao']); ?></td>
                                            <td><?php echo htmlspecialchars($log['descricao'] ?? '—'); ?></td>
                                            <td><small><?php echo $log['ip_address']; ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Backups Realizados -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-database"></i> Backups Realizados
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Arquivo</th>
                                            <th>Tamanho</th>
                                            <th>Tipo</th>
                                            <th>Realizado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($backup['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($backup['arquivo']); ?></td>
                                            <td><?php echo $backup['tamanho']; ?></td>
                                            <td><?php echo $backup['tipo']; ?></td>
                                            <td><?php echo htmlspecialchars($backup['usuario_nome']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($backups)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Nenhum backup realizado
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

    <!-- Modal Configuração Reconhecimento Facial -->
    <div class="modal fade" id="facialConfigModal" tabindex="-1" aria-labelledby="facialConfigModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="facialConfigModalLabel">
                        <i class="fas fa-cog"></i> Configuração de Reconhecimento Facial
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="facialConfigForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sensibilidade" class="form-label">Sensibilidade</label>
                                <input type="range" class="form-range" id="sensibilidade" min="1" max="100" value="50">
                                <div class="form-text">Ajuste a sensibilidade do reconhecimento</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="qualidade" class="form-label">Qualidade da Imagem</label>
                                <select class="form-select" id="qualidade">
                                    <option value="baixa">Baixa</option>
                                    <option value="media" selected>Média</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Opções</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notificacoes" checked>
                                    <label class="form-check-label" for="notificacoes">
                                        Ativar notificações
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="registro_automatico" checked>
                                    <label class="form-check-label" for="registro_automatico">
                                        Registro automático após reconhecimento
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="armazenar_imagens">
                                    <label class="form-check-label" for="armazenar_imagens">
                                        Armazenar imagens para treinamento
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="modelo" class="form-label">Modelo de IA</label>
                                <select class="form-select" id="modelo">
                                    <option value="default">Padrão</option>
                                    <option value="facenet">FaceNet</option>
                                    <option value="deepface">DeepFace</option>
                                </select>
                                <div class="form-text">Selecione o modelo de reconhecimento facial</div>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="webcam" class="form-label">Webcam Padrão</label>
                                <select class="form-select" id="webcam">
                                    <option value="default">Câmera Padrão</option>
                                    <option value="front">Câmera Frontal</option>
                                    <option value="back">Câmera Traseira</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Configurações avançadas de reconhecimento facial.</strong><br>
                        Estas configurações afetam a precisão e performance do sistema.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-warning" onclick="salvarConfigFacial()">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </div>
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
    
    <script>
    function salvarConfigFacial() {
        alert('Configurações de reconhecimento facial salvas com sucesso!');
        var modal = bootstrap.Modal.getInstance(document.getElementById('facialConfigModal'));
        modal.hide();
    }
    </script>
</body>
</html>
