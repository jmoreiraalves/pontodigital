<?php
// Configurações do Sistema
define('SISTEMA_NOME', 'Gestão de fequência');
define('EMPRESA_NOME', 'Gestão Ltda.');
define('SISTEMA_VERSAO', '1.0.0');
define('SESSION_TIMEOUT', 1200); // 20 minutos em segundos
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('SITE_URL', 'http://localhost/pontodigital/frontend');

define('DEBUG_MODE', true);

// Configurações de cookies
define('COOKIE_PONTO', 'ponto_session');
define('COOKIE_AUTH', 'auth_session');
define('COOKIE_PONTO_DURATION', 86400); // 1 dia
define('COOKIE_AUTH_DURATION', 3600);   // 1 hora

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Inicialização de variáveis
$errors = [];
$success = '';
$user = null;
$colaborador = null;
$empresa = null;

// Função para debug
function debug($data) {
    if (DEBUG_MODE) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

// Função para registrar logs
function registrar_log($acao, $descricao = '', $usuario_id = null, $colaborador_id = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, colaborador_id, acao, descricao, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $usuario_id, 
        $colaborador_id, 
        $acao, 
        $descricao, 
        $_SERVER['REMOTE_ADDR'], 
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

// Função para verificar se está autenticado
function isAuth() {
    if (isset($_COOKIE[COOKIE_AUTH]) && !empty($_COOKIE[COOKIE_AUTH])) {
        return verificar_sessao_auth($_COOKIE[COOKIE_AUTH]);
    }
    return false;
}

// Função para verificar se tem sessão de ponto
function hasPontoSession() {
    return isset($_COOKIE[COOKIE_PONTO]) && !empty($_COOKIE[COOKIE_PONTO]);
}

// Função para limpar sessões
function logout() {
    setcookie(COOKIE_AUTH, '', time() - 3600, '/');
    setcookie(COOKIE_PONTO, '', time() - 3600, '/');
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

