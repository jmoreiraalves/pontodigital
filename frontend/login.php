<?php
require_once './config/database.php';
require_once './config/config.php';
require_once './includes/functions.php';

// $hash = password_hash("admin123", PASSWORD_DEFAULT);
//   var_dump($hash); 

// // Verificar se já está logado
// if (isset($_SESSION['user_id'])) {
//     header("Location: dashboard.php");
//     exit();
// }

// // Redirecionar se já tiver sessão de ponto
// if (hasPontoSession()) {
//     header('Location: registrar_ponto.php');
//     exit;
// }

// // Redirecionar se já tiver sessão auth
// if (isAuth()) {
//     header('Location: dashboard.php');
//     exit;
// }


$error = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Buscar usuário
        // $stmt = $db->query(
        //     "SELECT * FROM usuarios WHERE email = '{$username}' AND ativo = 1"
        // );

        // $user = $stmt->fetch(); 
 
        $query = "SELECT id, empresa_id, codigo, nome, cpf, email, senha, tipo, ativo FROM usuarios 
                  WHERE email = :email AND ativo = '1'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // var_dump($query);

         var_dump($user);

        if ($user && password_verify($password, $user['senha'])) {
            // Criar sessão
            $_SESSION['empresa_id'] = $user['empresa_id'];
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['username'] = $user['codigo'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nivel_acesso'] = $user['tipo'];
            $_SESSION['LAST_ACTIVITY'] = time();

            $cookie_value = base64_encode($usuario['id'] . ':' . $usuario['email'] . ':' . $usuario['empresa_id']);
            setcookie('pontodigital_login', $cookie_value, time() + (86400 * 30), "/");

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Usuário ou senha inválidos!";
        }
    } catch (Exception $e) {
        $error = "Erro no sistema: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SISTEMA_NOME; ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #97db91ff 0%, #5eb169ff 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            padding: 30px 0 20px;
        }

        .logo h2 {
            color: #333;
            font-weight: 600;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #66eaa1ff 0%, #378561ff 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: white;
        }
    </style>
</head>

<body>
    <div class="login-card p-4">
        <div class="logo">
            <h2><i class="fas fa-file-contract"></i> Ponto Eletronico</h2>
            <p class="text-muted">Gerenciamento da frequência dos colaboradores</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                Sessão expirada por inatividade. Faça login novamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i> Usuário
                </label>
                <input type="text" class="form-control" id="username" name="username" required autofocus
                    placeholder="Digite seu usuário">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Senha
                </label>
                <input type="password" class="form-control" id="password" name="password" required
                    placeholder="Digite sua senha">
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </div>

            <div class="d-grid gap-2 mb-3">
                <!-- <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button> -->
                <a href="index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-clock"></i> Registrar ponto
                </a>
            </div>

            <div class="text-center">
                <small class="text-muted">Sistema desenvolvido por: João Carlos Moreira Alves Junior</small>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Limpar alertas após 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>