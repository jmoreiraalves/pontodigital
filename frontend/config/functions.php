<?php
require_once 'database.php';

function sanitizar($dados) {
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    return htmlspecialchars(strip_tags(trim($dados)));
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function formatarData($data, $formato = 'd/m/Y H:i:s') {
    $date = new DateTime($data);
    return $date->format($formato);
}

function enviarEmailRecuperacao($email, $token) {
    // Configurações do email
    $to = $email;
    $subject = "Recuperação de Senha - Sistema de Vendas";
    
    $url = "http://" . $_SERVER['HTTP_HOST'] . "/sistema-vendas/redefinir-senha.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Recuperação de Senha</title>
    </head>
    <body>
        <h2>Recuperação de Senha</h2>
        <p>Você solicitou a recuperação de senha.</p>
        <p>Clique no link abaixo para redefinir sua senha:</p>
        <p><a href='$url'>Redefinir Senha</a></p>
        <p>Este link expira em 1 hora.</p>
        <p>Se você não solicitou esta recuperação, ignore este email.</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: sistema@sistema-vendas.com' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function getEstatisticas() {
    $database = new Database();
    $db = $database->getConnection();
    
    $estatisticas = [];
    
    // Total de usuários
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE ativo = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estatisticas['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de produtos
    $query = "SELECT COUNT(*) as total FROM tec_products WHERE ativo = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estatisticas['total_produtos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // // Total de vendas do mês
    // $query = "SELECT COUNT(*) as total, COALESCE(SUM(valor_total), 0) as valor_total 
    //           FROM vendas 
    //           WHERE MONTH(data_venda) = MONTH(CURRENT_DATE()) 
    //           AND YEAR(data_venda) = YEAR(CURRENT_DATE())";
    // $stmt = $db->prepare($query);
    // $stmt->execute();
    // $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estatisticas['vendas_mes'] = 10; // $result['total'];
    $estatisticas['valor_mes'] = 100.00 ;//$result['valor_total'];
    
    return $estatisticas;
}
?>
