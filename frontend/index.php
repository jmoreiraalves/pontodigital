<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se tem sessão de ponto ativa
// if (!hasPontoSession()) {
//     header('Location: index.php');
//     exit;
// }

// Processar registro de ponto
$registro_sucesso = false;
$ultimo_registro = null;

 if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_ponto'])) {
     $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
     $senha = $_POST['senha'] ?? '';
    
    if (empty($cpf) || empty($senha)) {
        $errors[] = 'Preencha CPF e senha';
    } elseif (!validar_cpf($cpf)) {
        $errors[] = 'CPF inválido';
    } else {
        try {
           //instanciar o banco
           require 'classes/Database.php';
           $db = new Database();

           require 'classes/Collaborator.php';
           $collaborator = new Collaborator();
           
           $retorno = $collaborator->setRegistroPonto($cpf, $senha);

           //innstanciar o objeto collaborator


        } catch (PDOException $e) {
            $errors[] = 'Erro no sistema: ' . $e->getMessage();
        }
    }
 }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Ponto - <?php echo SISTEMA_NOME; ?></title>
    
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
        <nav class="navbar navbar-expand-lg navbar-dark bg-success">
            <div class="container">
                <a class="navbar-brand" href="registrar_ponto.php">
                    <i class="fas fa-clock"></i> <?php echo SISTEMA_NOME; ?>
                </a>
                <div class="navbar-text text-white">
                    <small id="ultimoPonto">
                        <?php if ($ultimo_registro): ?>
                            Último registro: <?php echo $ultimo_registro['data']; ?> <?php echo $ultimo_registro['hora']; ?>
                        <?php else: ?>
                            Sistema de Ponto Eletrônico
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </nav>

        <!-- Conteúdo Principal -->
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card ponto-card">
                        <div class="card-header text-center bg-success text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-fingerprint"></i> Registro de Ponto
                            </h4>
                        </div>
                        
                        <?php if ($registro_sucesso): ?>
                            <div class="alert alert-success m-3">
                                <h5><i class="fas fa-check-circle"></i> Ponto Registrado com Sucesso!</h5>
                                <p class="mb-0">
                                    Tipo: <strong><?php echo getTiposPonto()[$ultimo_registro_db['tipo']]; ?></strong><br>
                                    Data: <?php echo date('d/m/Y', strtotime($ultimo_registro_db['data_registro'])); ?><br>
                                    Hora: <?php echo $ultimo_registro_db['hora_registro']; ?><br>
                                    Colaborador: <?php echo $colaborador['nome']; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger m-3">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="cpf" class="form-label">
                                        <i class="fas fa-id-card"></i> CPF
                                    </label>
                                    <input type="text" class="form-control cpf-mask" id="cpf" name="cpf" 
                                           placeholder="000.000.000-00" required>
                                    <div class="invalid-feedback">
                                        Por favor, informe seu CPF.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="senha" class="form-label">
                                        <i class="fas fa-key"></i> Senha
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="senha" name="senha" required>
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('senha')">
                                            <i class="fas fa-eye" id="toggleSenha"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, informe sua senha.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="registrar_ponto" class="btn btn-success btn-lg">
                                        <i class="fas fa-check-circle"></i> Registrar Ponto
                                    </button>
                                    
                                    <!-- <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal" 
                                            data-bs-target="#facialModal">
                                        <i class="fas fa-camera"></i> Reconhecimento Facial
                                    </button> -->
                                    
                                    <!-- <a href="index.php" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-home"></i> Voltar para Início
                                    </a> -->
                                </div>
                            </form>
                        </div>
                        
                        <div class="card-footer">
                            <a href="login.php" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-home"></i> ACesso ao administrativo
                                    </a>
                            <!-- <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="ponto-status entrada">
                                        <i class="fas fa-sign-in-alt"></i><br>
                                        Entrada
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="ponto-status">
                                        <i class="fas fa-coffee"></i><br>
                                        Intervalo
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="ponto-status">
                                        <i class="fas fa-undo"></i><br>
                                        Retorno
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="ponto-status saida">
                                        <i class="fas fa-sign-out-alt"></i><br>
                                        Saída
                                    </div>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer mt-4">
            <div class="container">
                <p class="mb-0 text-center">
                    <small>
                        <i class="fas fa-clock"></i> 
                        <span id="live-date"></span> - 
                        <span id="live-clock"></span> |
                        Sistema de Ponto Eletrônico
                    </small>
                </p>
            </div>
        </footer>
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
