<?php
// /api/middlewares/AuthMiddleware.php

class AuthMiddleware {
    public static function handle() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : null;
        
        if (!$authHeader) {
            Response::error('Token de autenticação não fornecido', 401);
        }
        
        $tokenParts = explode(' ', $authHeader);
        
        if (count($tokenParts) != 2 || $tokenParts[0] != 'Bearer') {
            Response::error('Formato do token inválido', 401);
        }
        
        $jwt = $tokenParts[1];
        $payload = JWT::validate($jwt);
        
        if (!$payload) {
            Response::error('Token inválido ou expirado', 401);
        }
        
        // Adicionar informações do usuário à requisição
        $_SERVER['USER_ID'] = $payload['user_id'];
        $_SERVER['USER_ROLE'] = $payload['role'];
        
        return $payload;
    }
}
?>