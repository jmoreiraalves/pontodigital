<?php
// /api/controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->email) || !isset($data->password)) {
            Response::error('Email e senha são obrigatórios');
        }
        
        $result = $this->user->login($data->email, $data->password);
        
        if ($result['success']) {
            Response::success([
                'token' => $result['token'],
                'user' => $result['user']
            ], 'Login realizado com sucesso');
        } else {
            Response::error($result['message']);
        }
    }
    
    public function register() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->name) || !isset($data->email) || !isset($data->password)) {
            Response::error('Nome, email e senha são obrigatórios');
        }
        
        $this->user->name = $data->name;
        $this->user->email = $data->email;
        $this->user->password = $data->password;
        $this->user->role = $data->role ?? 'user';
        
        if ($this->user->create()) {
            Response::success(null, 'Usuário criado com sucesso');
        } else {
            Response::error('Não foi possível criar o usuário');
        }
    }
    
    public function forgotPassword() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->email)) {
            Response::error('Email é obrigatório');
        }
        
        if ($this->user->requestPasswordReset($data->email)) {
            Response::success(null, 'Email de recuperação enviado');
        } else {
            Response::error('Não foi possível processar a solicitação');
        }
    }
    
    public function resetPassword() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->token) || !isset($data->password)) {
            Response::error('Token e nova senha são obrigatórios');
        }
        
        if ($this->user->resetPassword($data->token, $data->password)) {
            Response::success(null, 'Senha redefinida com sucesso');
        } else {
            Response::error('Token inválido ou expirado');
        }
    }
    
    public function changePassword() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->current_password) || !isset($data->new_password)) {
            Response::error('Senha atual e nova senha são obrigatórias');
        }
        
        // Obter usuário do token (simulado - na prática viria do middleware)
        if (!isset($_SERVER['USER_ID'])) {
            Response::error('Usuário não autenticado', 401);
        }
        
        $userId = $_SERVER['USER_ID'];
        
        if ($this->user->changePassword($userId, $data->current_password, $data->new_password)) {
            Response::success(null, 'Senha alterada com sucesso');
        } else {
            Response::error('Senha atual incorreta');
        }
    }
    
    public function validateToken() {
        // Esta rota é protegida por middleware
        // Se chegou aqui, o token é válido
        Response::success([
            'valid' => true,
            'user_id' => $_SERVER['USER_ID'],
            'role' => $_SERVER['USER_ROLE']
        ], 'Token válido');
    }
}
?>