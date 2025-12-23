<?php
if (empty($_SESSION['menuativo'])) {
    $_SESSION['menuativo'] = "dashbaord";
}

// Exemplo: o nível vem da sessão
$nivelAcesso = $_SESSION['nivel_acesso'] ?? 'usuario'; // default: usuario

// Definição do array de menus com níveis permitidos
$menus = [
    ["href" => "dashbaord", "icon" => "fas fa-tachometer-alt", "text" => "Dashboard", "roles" => ["admin","gerente","usuario"]],
    ["href" => "jurisprudence-list", "icon" => "fas fa-gavel", "text" => "Jurisprudência", "roles" => ["admin","gerente"]],
    ["href"=> "procuracao-manage", "icon"=> "fas fa-gavel", "text" => "Procurações", "roles" => ["admin","gerente"]],
    ["href" => "contracts-list", "icon" => "fas fa-file-contract", "text" => "Contratos", "roles" => ["admin","gerente","usuario"]],
    //["href" => "contracts-add", "icon" => "fas fa-plus-circle", "text" => "Novo Contrato", "roles" => ["admin","gerente","usuario"]],    
    ["href" => "people-list", "icon" => "fas fa-users", "text" => "Pessoas", "roles" => ["admin","gerente"]],
    ["href" => "companies-list", "icon" => "fas fa-building", "text" => "Empresas", "roles" => ["admin"]],
    ["href" => "users-list", "icon" => "fas fa-user-friends", "text" => "Usuários", "roles" => ["admin"]],
    ["href" => "reports-expiring", "icon" => "fas fa-clock", "text" => "Próximos Vencimentos", "roles" => ["admin","gerente","usuario"]],
    ["href" => "reports-pending", "icon" => "fas fa-exclamation-triangle", "text" => "Pendências", "roles" => ["admin","gerente"]],
    ["href" => "workflow-approve", "icon" => "fas fa-plus-circle", "text" => "WorkFlow", "roles" => ["admin","gerente","usuario"]],
    ["href" => "settings-general", "icon" => "fas fa-cog", "text" => "Configurações", "roles" => ["admin","gerente"]],
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
