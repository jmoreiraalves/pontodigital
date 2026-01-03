<?php
require_once './config/database.php';
require_once './config/config.php';
require_once './includes/functions.php';
requireLogin();
checkSessionTimeout();

$user = [
        "empresa_id"    =>  $_SESSION['empresa_id'],
        "user_id"       =>  $_SESSION['user_id'] ,
        "username"      =>  $_SESSION['username'],
        "nome"          =>  $_SESSION['nome'],
        "email"         =>  $_SESSION['email'],
        "nivel_acesso"  =>  $_SESSION['nivel_acesso'],
        "ultimo_acesso" =>  $_SESSION['LAST_ACTIVITY']
];    

$includesPermitidos = [
    'dashboard',
    'empresas',
    'empresa',
    'acessonegado-inc',
    'colaboradores',
    'troca-turno',
    'profissional-ti'
];

$pagina = (isset($_GET['submenu']) ? $_GET['submenu'] : 'dashboard');
$menuprincipal = (isset($_GET['menu']) ? $_GET['menu'] : 'dashboard');

$include = $pagina;
if ($include !== 'dashboard') {
    $include = slugify($include) ;
}

// Se não estiver na lista, força o dashboard
if (!in_array($include, $includesPermitidos, true)) {
    $include = 'dashboard';
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Ponto Digital</title>

    <!-- Custom fonts for this template-->
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
   

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-success sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" style="color: black;" href="?menu=dashboard">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-check"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Ponto <sup>Digital</sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?= $menuprincipal === 'dashboard' ? 'active' : '' ?>">
                <a class="nav-link" href="?menu=dashboard&submenu=dashboard" style="color: black;">
                    <i class="fas fa-fw fa-tachometer-alt" style="color: black;"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading" style="color: black;">
                Gestão
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item <?= $menuprincipal === 'cadastro' ? 'active' : '' ?>">
                <a class="nav-link collapsed"  style="color: black;" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-cog" style="color: black;"></i>
                    <span>Cadastros</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <!-- <h6 class="collapse-header">Custom Components:</h6> -->
                        <a class="collapse-item <?= $include === 'empresa' ? 'active' : '' ?>" href="?menu=cadastros&submenu=empresa">Empresa</a>
                        <a class="collapse-item <?= $include === 'empresas' ? 'active' : '' ?>" href="?menu=cadastros&submenu=empresas">Empresas</a>
                         <a class="collapse-item <?= $include === 'colaboradores' ? 'active' : '' ?>" href="?menu=cadastros&submenu=colaboradores">Colaboradores</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Utilities Collapse Menu -->
            <li class="nav-item <?= $menuprincipal === 'movimentacao' ? 'active' : '' ?>">
                <a class="nav-link collapsed" style="color: black;" href="#" data-toggle="collapse" data-target="#collapseUtilities"
                    aria-expanded="true" aria-controls="collapseUtilities">
                    <i class="fas fa-fw fa-wrench" style="color: black;"></i>
                    <span>Movimentação</span>
                </a>
                <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <!-- <h6 class="collapse-header">Custom Utilities:</h6> -->
                        <a class="collapse-item <?= $include === 'troca-turno' ? 'active' : '' ?>" href="?menu=movimentacao&submenu=troca-turno">Torca de Turno</a>
                        <a class="collapse-item<?= $include === 'profissional-ti' ? 'active' : '' ?>" href="?menu=movimentacao&submenu=profissional-ti">Profissional de T.I.</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading" style="color: black;">
                Relatórios
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item <?= $menuprincipal === 'relatorios' ? 'active' : '' ?>">
                <a class="nav-link" style="color: black;" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true"
                    aria-controls="collapsePages">
                    <i class="fas fa-fw fa-folder" style="color: black;"></i>
                    <span>Relatórios</span>
                </a>
                <div id="collapsePages" class="collapse show" aria-labelledby="headingPages"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Registros:</h6>
                        <a class="collapse-item<?= $include === '' ? 'active' : '' ?>" href="?menu=relatorios&submenu=">Pontos em andamento</a>
                        <a class="collapse-item<?= $include === '' ? 'active' : '' ?>" href="?menu=relatorios&submenu=">Pontos atrasados</a>
                        <a class="collapse-item<?= $include === '' ? 'active' : '' ?>" href="?menu=relatorios&submenu=">Faltas</a>
                        <a class="collapse-item<?= $include === '' ? 'active' : '' ?>" href="?menu=relatorios&submenu=">Atestados Médicos</a>
                        <div class="collapse-divider"></div>
                        <h6 class="collapse-header">Espelho de ponto:</h6>
                        <a class="collapse-item<?= $include === '' ? 'active' : '' ?>" href="?menu=relatorios&submenu=">Mensal</a>
                        <a class="collapse-item<?= $include === '' ? 'active' : '' ?>" href="?menu=relatorios&submenu=">Anual</a>
                        <a class="collapse-item<?= $include === '' ? 'active' : '' ?>" href="?menu=relatorios&submenu=">Holerite</a>
                    </div>
                </div>
            </li>            

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <!-- <form
                        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form> -->

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Douglas McGee</span>
                                <img class="img-profile rounded-circle"
                                    src="img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Meu Perfil
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Alterar senha
                                </a>                               
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <!-- <h1 class="h3 mb-4 text-gray-800">Blank Page</h1> -->
                     <?php
                        include './inc/'.$include.'.php'
                     ?>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2020</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="login.html">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="assets/js/sb-admin-2.min.js"></script>

</body>

</html>