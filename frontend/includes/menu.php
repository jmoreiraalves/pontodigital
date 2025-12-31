<?php
if (empty($_SESSION['menuativo'])) {
    $_SESSION['menuativo'] = "dashbaord";
}

// Exemplo: o nível vem da sessão
$nivelAcesso = $_SESSION['nivel_acesso'] ?? 'usuario'; // default: usuario

// Definição do array de menus com níveis permitidos
$menus = [
    ["href" => "dashbaord", "icon" => "fas fa-tachometer-alt", "text" => "Dashboard", "roles" => ["super","admin","gestor","user", "ti"]],
    ["href" => "empresas", "icon" => "fas fa-gavel", "text" => "Empresas", "roles" => ["super"]],
    ["href" => "empresa", "icon"=> "fas fa-gavel", "text" => "Minha Empresa", "roles" => ["super","admin","gestor"]],
    ["href" => "colaboradores", "icon" => "fas fa-file-contract", "text" => "Colaboradores", "roles" => ["super","admin","gestor"]],
    ["href" => "troca-turno", "icon" => "fas fa-plus-circle", "text" => "Troca de Turno", "roles" => ["admin","gestor","usuario"]],    
    ["href" => "profissional-ti", "icon" => "fas fa-users", "text" => "Profissional de T.I.", "roles" => ["super","ti"]],
    // ["href" => "companies-list", "icon" => "fas fa-building", "text" => "Empresas", "roles" => ["super","admin"]],
    // ["href" => "users-list", "icon" => "fas fa-user-friends", "text" => "Usuários", "roles" => ["super","admin"]],
    // ["href" => "reports-expiring", "icon" => "fas fa-clock", "text" => "Próximos Vencimentos", "roles" => ["super","admin","gestor","usuario"]],
    // ["href" => "reports-pending", "icon" => "fas fa-exclamation-triangle", "text" => "Pendências", "roles" => ["super","admin","gestor"]],
    // ["href" => "workflow-approve", "icon" => "fas fa-plus-circle", "text" => "WorkFlow", "roles" => ["super","admin","gestor","usuario"]],
    // ["href" => "settings-general", "icon" => "fas fa-cog", "text" => "Configurações", "roles" => ["super","admin","gestor"]],
];

// Verifica qual menu está ativo (via POST ou default)
// $menuAtivo = $_POST['menuativo'] ?? "dashboard";
if (isset($_GET['menu'])) {
    $_SESSION['menuativo'] = $_GET['menu'];
}

$menuAtivo = $_SESSION['menuativo'];

?>

<nav class="col-lg-2 d-none d-lg-block sidebar" id="sidebar">
    <div class="position-sticky">
        <ul class="nav flex-column">
            <?php foreach ($menus as $menu): ?>
                <?php if (in_array($nivelAcesso, $menu['roles'])): ?>
                    <li>
                         <a href="?menu=<?= $menu['href']; ?>" 
                           class="<?= ($menuAtivo === $menu['href']) ? 'active' : ''; ?>">
                            <i class="<?= $menu['icon']; ?>"></i> <?= $menu['text']; ?>
                        </a>

                        <!-- <a href="<?= $menu['href']; ?>" 
                           class="<?= ($menuAtivo === $menu['href']) ? 'active' : ''; ?>" 
                           data-href="<?= $menu['href']; ?>">
                            <i class="<?= $menu['icon']; ?>"></i> <?= $menu['text']; ?>
                        </a> -->
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
