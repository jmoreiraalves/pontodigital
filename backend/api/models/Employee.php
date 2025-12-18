<?php
// /api/models/Employee.php

require_once __DIR__ . '/../config/Database.php';

class Employee {
    private $conn;
    private $table = 'employees';
    
    public $id;
    public $name;
    public $email;
    public $phone;
    public $address;
    public $position;
    public $salary;
    public $hire_date;
    public $user_id;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET name = :name, email = :email, phone = :phone, 
                      address = :address, position = :position, salary = :salary,
                      hire_date = :hire_date, user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->position = htmlspecialchars(strip_tags($this->position));
        $this->salary = htmlspecialchars(strip_tags($this->salary));
        $this->hire_date = htmlspecialchars(strip_tags($this->hire_date));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        
        // Bind values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':position', $this->position);
        $stmt->bindParam(':salary', $this->salary);
        $stmt->bindParam(':hire_date', $this->hire_date);
        $stmt->bindParam(':user_id', $this->user_id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function read() {
        $query = "SELECT e.*, u.name as created_by 
                  FROM " . $this->table . " e 
                  LEFT JOIN users u ON e.user_id = u.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    public function readOne() {
        $query = "SELECT e.*, u.name as created_by 
                  FROM " . $this->table . " e 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->position = $row['position'];
            $this->salary = $row['salary'];
            $this->hire_date = $row['hire_date'];
            $this->user_id = $row['user_id'];
            return true;
        }
        
        return false;
    }
    
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, email = :email, phone = :phone, 
                      address = :address, position = :position, salary = :salary,
                      hire_date = :hire_date, user_id = :user_id
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->position = htmlspecialchars(strip_tags($this->position));
        $this->salary = htmlspecialchars(strip_tags($this->salary));
        $this->hire_date = htmlspecialchars(strip_tags($this->hire_date));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':position', $this->position);
        $stmt->bindParam(':salary', $this->salary);
        $stmt->bindParam(':hire_date', $this->hire_date);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>