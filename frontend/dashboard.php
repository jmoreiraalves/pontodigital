<?php
require_once './config/database.php';
require_once './config/config.php';
require_once './includes/functions.php';
requireLogin();
checkSessionTimeout();


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SISTEMA_NOME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .sidebar {
            background: #1b2e1b;
            min-height: calc(100vh - 120px);
            padding: 20px 0;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
        }

        .sidebar a {
            color:#ecf0f1;
            padding: 10px 20px;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar a:hover {
            background: #2e4d2e;
            color: #fff;
            padding-left: 25px;
        }

        .sidebar a.active {
            background: #27ae60;
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        /* .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        } */
        .navbar {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); /* gradiente verde */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        /* .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        } */
        .stat-card {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); /* gradiente verde */
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
   

        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        /* footer {
            background: #2c3e50;
            color: white;
            padding: 15px 0;
            margin-top: 30px;
        } */
        
        .footer {
            background: #1b2e1b; /* verde escuro sólido */
            color: white;
            padding: 15px 0;
            margin-top: 30px;
        }
    

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="btn btn-dark d-lg-none" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand">
                <i class="fas fa-file-contract"></i> <?php echo SISTEMA_NOME; ?>
            </span>
            <div class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['nome']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?menu=profile">
                                <i class="fas fa-user-cog"></i> Meu Perfil
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="./logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </a></li>
                    </ul>
                </li>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php
            require_once './includes/menu.php';
            ?>
            <!-- Main Content -->
            <main class="col-lg-10 main-content" id="mainContent">
                <?php
                  include './inc/' . $menuAtivo . '.php';
                ?>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">
                <?php echo EMPRESA_NOME; ?> &copy; <?php echo date('Y'); ?> |
                Sistema desenvolvido por: João Carlos Moreira Alves Junior
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    

    <script>
        // Toggle sidebar em mobile
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Atualizar tempo de sessão
        setInterval(function () {
            fetch('includes/session_ping.php')
                .catch(error => console.log('Ping de sessão falhou'));
        }, 60000); // A cada 1 minuto

        // Auto logout após inatividade
        let timeout;
        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                window.location.href = '../logout.php?timeout=1';
            }, <?php echo SESSION_TIMEOUT * 1000; ?>);
        }

        document.addEventListener('mousemove', resetTimer);
        document.addEventListener('keypress', resetTimer);
        resetTimer();

        $(document).ready(function () {
            $("#sidebar a").on("click", function (e) {
                e.preventDefault();
                let href = $(this).data("href");

                $.post("dashboard.php", { menuativo: href }, function (data) {
                    $("#conteudo").html(data);
                });
            });
        });

    <?php
      include './inc/js/'. $menuAtivo .'.js';
    ?>
    </script>

    

</body>

</html>