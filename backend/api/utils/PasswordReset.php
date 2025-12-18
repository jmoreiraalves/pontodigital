<?php
// /api/utils/PasswordReset.php

class PasswordReset {
    public static function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    public static function sendResetEmail($email, $token) {
        // Em produção, implemente o envio real de email
        $resetLink = "http://seusite.com/reset-password?token=" . $token;
        
        // Simulação de envio de email
        // mail($email, "Redefinir Senha", "Clique no link para redefinir sua senha: " . $resetLink);
        
        return true;
    }
}
?>