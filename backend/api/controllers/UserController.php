<?php
// /api/controllers/UserController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class UserController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    public function index() {
        // Verificar se é admin
        if ($_SERVER['USER_ROLE'] != 'admin') {
            Response::error('Acesso negado', 403);
        }
        
        $stmt = $this->user->read();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        Response::success($users);
    }
    
    public function show($id) {
        // Verificar se é admin ou o próprio usuário
        if ($_SERVER['USER_ROLE'] != 'admin' && $_SERVER['USER_ID'] != $id) {
            Response::error('Acesso negado', 403);
        }
        
        $this->user->id = $id;
        
        if ($this->user->readOne()) {
            Response::success([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role,
                'status' => $this->user->status
            ]);
        } else {
            Response::error('Usuário não encontrado', 404);
        }
    }
    
    public function store() {
        // Verificar se é admin
        if ($_SERVER['USER_ROLE'] != 'admin') {
            Response::error('Acesso negado', 403);
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        $this->user->name = $data->name;
        $this->user->email = $data->email;
        $this->user->password = $data->password;
        $this->user->role = $data->role ?? 'user';
        
        if ($this->user->create()) {
            Response::success(null, 'Usuário criado com sucesso', 201);
        } else {
            Response::error('Não foi possível criar o usuário');
        }
    }
    
    public function update($id) {
        // Verificar se é admin
        if ($_SERVER['USER_ROLE'] != 'admin') {
            Response::error('Acesso negado', 403);
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        $this->user->id = $id;
        $this->user->name = $data->name;
        $this->user->email = $data->email;
        $this->user->role = $data->role;
        $this->user->status = $data->status;
        
        if ($this->user->update()) {
            Response::success(null, 'Usuário atualizado com sucesso');
        } else {
            Response::error('Não foi possível atualizar o usuário');
        }
    }
    
    public function destroy($id) {
        // Verificar se é admin
        if ($_SERVER['USER_ROLE'] != 'admin') {
            Response::error('Acesso negado', 403);
        }
        
        // Não permitir deletar a si mesmo
        if ($_SERVER['USER_ID'] == $id) {
            Response::error('Não é possível deletar seu próprio usuário');
        }
        
        $this->user->id = $id;
        
        if ($this->user->delete()) {
            Response::success(null, 'Usuário deletado com sucesso');
        } else {
            Response::error('Não foi possível deletar o usuário');
        }
    }
}
?>