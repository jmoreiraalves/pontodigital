<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function verificarLogin()
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin()
{
    return isset($_SESSION['perfil']) && $_SESSION['perfil'] == 'admin';
}

function gerarToken($tamanho = 32)
{
    return bin2hex(random_bytes($tamanho));
}


// Função para ler e decodificar o cookie
function lerCookieLogin(bool $redirectOnFail = false): ?array
{
    if (!isset($_COOKIE['pdvv20_login'])) {
        if ($redirectOnFail) {
            header("Location: logout.php");
            exit;
        }
        return null;
    }

    $decoded = base64_decode($_COOKIE['pdvv20_login'], true);

    if ($decoded === false) {
        if ($redirectOnFail) {
            header("Location: logout.php");
            exit;
        }
        return null;
    }

    $parts = explode(':', $decoded);

    if (count($parts) !== 3) {
        if ($redirectOnFail) {
            header("Location: logout.php");
            exit;
        }
        return null;
    }

    return [
        'id' => $parts[0],
        'email' => $parts[1],
        'empresa_id' => $parts[2],
    ];
}

// Função para revalidar se o usuário está ativo
function revalidarUsuario(PDO $pdo, bool $redirectOnFail = false): bool
{
    $dados = lerCookieLogin($redirectOnFail);

    if ($dados === null) {
        return false; // Cookie inválido ou inexistente
    }

    $stmt = $pdo->prepare("SELECT ativo FROM usuarios WHERE id = :id AND email = :email AND empresa_id = :empresa_id");
    $stmt->execute([
        ':id' => $dados['id'],
        ':email' => $dados['email'],
        ':empresa_id' => $dados['empresa_id'],
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || $usuario['ativo'] != 1) {
        // Usuário inativo → realizar logout
        setcookie('pdvv20_login', '', time() - 3600, "/");
        if ($redirectOnFail) {
            header("Location: logout.php");
            exit;
        }
        return false;
    }

    // Usuário ativo → revalidar cookie (renovar expiração)
    $cookie_value = base64_encode($dados['id'] . ':' . $dados['email'] . ':' . $dados['empresa_id']);
    setcookie('pdvv20_login', $cookie_value, time() + (86400 * 30), "/");

    return true;
}

?>