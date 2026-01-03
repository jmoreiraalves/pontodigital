<?php
require_once './config/database.php';
require_once './config/config.php';
require_once './includes/functions.php';
require_once 'config/session.php';

// function slugify(string $texto): string
// {
//     // 1. Normaliza para UTF-8 e remove acentos
//     $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);

//     // 2. Remove caracteres não alfanuméricos (mantém espaço e traço)
//     $texto = preg_replace('/[^a-zA-Z0-9\s-]/', '', $texto);

//     // 3. Substitui espaços em branco por traços
//     $texto = preg_replace('/[\s]+/', '-', $texto);

//     // 4. Converte para minúsculas
//     $texto = strtolower($texto);

//     // 5. Remove traços duplicados
//     $texto = preg_replace('/-+/', '-', $texto);

//     // 6. Remove traços no início/fim
//     return trim($texto, '-');
// }

verificarLogin();

//var_dump($_SESSION);



$user = $_SESSION;

$includesPermitidos = [
    'dashboard-inc',
    'empresas-inc',
    'usuario-inc',
    'acessonegado-inc',
    'importar-categorias-inc',
    'pdv-inc',
    'listar-produtos-inc',
    //'grupo-de-impostos-inc',
    'importar-produtos-inc',
    'imprimir-etiquetas-inc',
    'gerar-etiquetas-html-inc',
    'listar-compras-inc',
    'listar-fornecedores-inc'
];

$pagina = (isset($_GET['page']) ? $_GET['page'] : 'dashboard');

$include = $pagina;
if ($include !== 'dashbaord-inc') {
    $include = slugify($include) . '-inc';
}

// Se não estiver na lista, força o dashboard
if (!in_array($include, $includesPermitidos, true)) {
    $include = 'dashboard-inc';
}

//var_dump($pagina);

$icons = [
    "Home" => "fas fa-home",
    "Pedidos" => "fas fa-drumstick-bite",
    "Clientes" => "fas fa-utensils",
    //"Produtos" => "fas fa-wine-bottle",
    "Relatórios" => "fas fa-tools"
];

$menuList = array(
    // "Home" => array(
    //      array("nome" => "Abertura", "detalhe" => "Visão geral de pedidos com carrinho confirmado")
    // ),
    "Cadastro" => array(
        array("nome" => "Atestado Médico", "detalhe" => ""),
        array("nome" => "Colaboradores", "detalhe" => ""),
        array("nome" => "Empresa", "detalhe" => "")
    ),
    "Movimentação" => array(
        array("nome" => "Abono de falta", "detalhe" => ""),
        array("nome" => "Profissional de TI", "detalhe" => ""),
        array("nome" => "Troca de Turno", "detalhe" => "")
    ),
    // "Categorias" => array(
    //     array("nome" => "Listar categorias", "detalhe" => "s"),
    //     array("nome" => "Importar categorias", "detalhe" => ""),
    // ),
    // "compras" => array(
    //     array("nome" => "Listar compras", "detalhe" => ""),
    //     array("nome" => "Listar fornecedores", "detalhe" => ""),
    //     //array("nome" => "Estoque", "detalhe" => "Lançamento de compras estoque baixo"),
    //     // array("nome" => "Kit Churrasco", "detalhe" => "Criação e manutenção de kit churrasco"),
    //     // array("nome" => "Pagamentos", "detalhe" => "Movimentação de pagamentos"),
    //     // array("nome" => "Produtos", "detalhe" => "Cadastro de produtos"),
    // ),
    // "Pessoas" => array(
    //     array("nome" => "Listar usuários", "detalhe" => ""),
    //     array("nome" => "Listar clientes", "detalhe" => ""),
    //     array("nome" => "Estoque por Kit", "detalhe" => ""),
    //     array("nome" => "Carrinho abandonado", "detalhe" => ""),
    //     // array("nome" => "Estoque por Kit", "detalhe" => ""),
    //     // array("nome" => "Carrinho abandonado", "detalhe" => ""),
    //     array("nome" => "Pedidos entregues", "detalhe" => ""),
    //     //array("nome" => "Cartão loja", "detalhe" => "Extrato de cartão da loja")
    // ),
    "Relatórios" => array(
        array("nome" => "Pontos em andamento", "detalhe" => ""),
        array("nome" => "Pontos atrasados", "detalhe" => ""),
        array("nome" => "Vendas", "detalhe" => ""),
        array("nome" => "Faltas", "detalhe" => ""),
        array("nome" => "Atestados Médicos", "detalhe" => ""),
        array("nome" => "Espelho de ponto", "detalhe" => "")
    )
);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ponto Digital</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <!-- <link href="assets/css/sweetalert2.scss" rel="stylesheet"> -->


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar " style="background-color: forestgreen;">
                <div class="position-sticky pt-3">
                    <div class="sidebar-header text-center text-white p-3">
                        <h5>Ponto Digital</h5>
                        <small>Bem-vindo, <?php echo $_SESSION['nome']; ?></small>
                    </div>

                    <ul class="nav flex-column" id="menuAccordion">
                        <!-- Item fixo Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>

                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="?page=usuario">
                                    <i class="bi bi-people"></i> Usuários
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($_SESSION['nivel_acesso'] === 'super'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="?page=empresas">
                                    <i class="bi bi-people"></i> Empresas
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php foreach ($menuList as $menu => $submenus): ?>
                            <?php
                            $iconClass = $icons[$menu] ?? 'bi bi-folder';
                            $collapseId = 'collapse' . preg_replace('/\s+/', '', $menu);
                            ?>
                            <li class="nav-item">
                                <!-- Botão que abre/fecha submenu -->
                                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#<?= $collapseId ?>" role="button" aria-expanded="false"
                                    aria-controls="<?= $collapseId ?>">
                                    <span><i class="<?= $iconClass ?>"></i> <?= htmlspecialchars($menu) ?></span>
                                    <i class="bi bi-caret-down-fill"></i>
                                </a>

                                <!-- Submenu -->
                                <div class="collapse" id="<?= $collapseId ?>" data-bs-parent="#menuAccordion">
                                    <ul class="nav flex-column ms-3">
                                        <?php foreach ($submenus as $submenu): ?>
                                            <li class="nav-item">
                                                <a class="nav-link text-white"
                                                    href="?page=<?= htmlspecialchars($submenu['nome']) ?>">
                                                    <?= htmlspecialchars($submenu['nome']) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="mt-5 p-3">
                        <a href="logout.php" class="btn btn-danger btn-sm w-100">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>
                </div>
            </nav>


            <!-- Conteúdo Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2" id="titulo-pagina"><?= strtoupper($pagina); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-primary"><?= $_SESSION['nivel_acesso']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Conteúdo dinâmico -->
                <div id="conteudo">
                    <?php include 'includes/' . $include . '.php'; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para ações -->
    <!-- <div class="modal fade" id="modalAcao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" id="modal-conteudo"> -->
    <!-- Conteúdo carregado via AJAX -->
    <!-- </div>
        </div>
    </div> -->

    <!-- Scripts necessários -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- SweetAlert2 JS -->
    <!-- <script src="assets/js/sweetalert2.js"></script> -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <!-- Função utilitária -->
    <script src="assets/js/alerts.js"></script>


    <script src="includes/js/<?= $include; ?>.js"></script>
    <!-- <script src="assets/js/script.js"></script> -->
</body>

</html>