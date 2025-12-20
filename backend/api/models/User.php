<?php
// /api/models/User.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/JWT.php';

class User {
    private $conn;
    private $table = 'users';
    
    public $id;
    public $name;
    public $email;
    public $cpf;
    public $password;
    public $role;
    public $status;
    public $reset_token;
    public $reset_token_expiry;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET name = :name, email = :email, cpf = :cpf, password = :password, role = :role";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->cpf = htmlspecialchars(strip_tags($this->cpf));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // Hash password
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Bind values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':cpf', $this->cpf);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function read() {
        $query = "SELECT id, name, email, cpf, role, status, created_at FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    public function readOne() {
        $query = "SELECT id, name, email, cpf, role, status, created_at FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->cpf = $row['cpf'];
            $this->status = $row['status'];
            return true;
        }
        
        return false;
    }
    
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, email = :email, role = :role, status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function delete() {
        $query = "UPDATE " . $this->table . " SET status = 'inactive' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function login($email, $password) {
        $query = "SELECT id, name, email, password, role, status FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            //var_dump($row);
            
            if ($row['status'] != 'active') {
                return ['success' => false, 'message' => 'Conta inativa'];
            }
            
            if (password_verify($password, $row['password'])) {
                // Gerar token JWT
                $payload = [
                    'user_id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'role' => $row['role'],
                    'iat' => time(),
                    'exp' => time() + (60 * 60 * 24) // 24 horas
                ];
                
                $token = JWT::encode($payload);
                
                return [
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'role' => $row['role']
                    ]
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Email ou senha inválidos'];
    }

    public function loginMobile($cpf, $password) {
        $query = "SELECT id, name, email, password, role, status FROM " . $this->table . " WHERE cpf = :cpf LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        $cpf = htmlspecialchars(strip_tags($cpf));
        $stmt->bindParam(':cpf', $cpf);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            //var_dump($row);
            
            if ($row['status'] != 'active') {
                return ['success' => false, 'message' => 'Conta inativa'];
            }
            
            if (password_verify($password, $row['password'])) {
                // Gerar token JWT
                $payload = [
                    'user_id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'role' => $row['role'],
                    'iat' => time(),
                    'exp' => time() + (60 * 60 * 24) // 24 horas
                ];
                
                $token = JWT::encode($payload);
                
                return [
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'role' => $row['role']
                    ]
                ];
            }
        }
        
        return ['success' => false, 'message' => 'CPF ou senha inválidos'];
    }
    
    public function requestPasswordReset($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Gerar token
            $token = PasswordReset::generateToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Salvar token no banco
            $updateQuery = "UPDATE " . $this->table . " 
                           SET reset_token = :token, reset_token_expiry = :expiry 
                           WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':token', $token);
            $updateStmt->bindParam(':expiry', $expiry);
            $updateStmt->bindParam(':id', $row['id']);
            
            if ($updateStmt->execute()) {
                // Enviar email (simulado)
                PasswordReset::sendResetEmail($email, $token);
                return true;
            }
        }
        
        return false;
    }
    
    public function resetPassword($token, $newPassword) {
        $query = "SELECT id, reset_token_expiry FROM " . $this->table . " 
                  WHERE reset_token = :token AND reset_token_expiry > NOW() LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        $token = htmlspecialchars(strip_tags($token));
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Atualizar senha
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            
            $updateQuery = "UPDATE " . $this->table . " 
                           SET password = :password, reset_token = NULL, reset_token_expiry = NULL 
                           WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $hashedPassword);
            $updateStmt->bindParam(':id', $row['id']);
            
            if ($updateStmt->execute()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        $query = "SELECT password FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($currentPassword, $row['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                $updateQuery = "UPDATE " . $this->table . " SET password = :password WHERE id = :id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':id', $userId);
                
                if ($updateStmt->execute()) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
?>