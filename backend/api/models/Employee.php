<?php
// /api/models/Employee.php

require_once __DIR__ . '/../config/Database.php';

class Employee {
    private $conn;
    private string $table = 'employees';
    
    public ?int $id = null;
    public ?string $name = null;
    public ?string $cpf = null;
    public ?string $rg = null;
    public ?string $cpts = null;
    public ?string $pis = null;
    public ?string $cel = null;
    public ?string $contato = null;
    public ?string $phone_contact = null;
    public ?string $turno = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $position = null;
    public ?float $salary = null;
    public ?string $hire_date = null;
    public ?int $user_id = null;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function create(): bool {
        $query = "INSERT INTO {$this->table}
                  SET name = :name, cpf = :cpf, rg = :rg, cpts = :cpts, pis = :pis,
                      cel = :cel, contato = :contato, phone_contact = :phone_contact, turno = :turno,
                      email = :email, phone = :phone, address = :address, position = :position,
                      salary = :salary, hire_date = :hire_date, user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);

        // Sanitização
        foreach (get_object_vars($this) as $attr => $value) {
            if ($attr !== 'conn' && $attr !== 'table') {
                $this->$attr = $value !== null ? htmlspecialchars(strip_tags((string)$value)) : null;
            }
        }
        
        // Bind
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':cpf', $this->cpf);
        $stmt->bindParam(':rg', $this->rg);
        $stmt->bindParam(':cpts', $this->cpts);
        $stmt->bindParam(':pis', $this->pis);
        $stmt->bindParam(':cel', $this->cel);
        $stmt->bindParam(':contato', $this->contato);
        $stmt->bindParam(':phone_contact', $this->phone_contact);
        $stmt->bindParam(':turno', $this->turno);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':position', $this->position);
        $stmt->bindParam(':salary', $this->salary);
        $stmt->bindParam(':hire_date', $this->hire_date);
        $stmt->bindParam(':user_id', $this->user_id);

        return $stmt->execute();
    }
    
    public function read() {
        $query = "SELECT e.*, u.name as created_by
                  FROM {$this->table} e
                  LEFT JOIN users u ON e.user_id = u.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    public function readOne(): bool {
        $query = "SELECT e.*, u.name as created_by
                  FROM {$this->table} e
                  LEFT JOIN users u ON e.user_id = u.id
                  WHERE e.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            foreach ($row as $attr => $value) {
                if (property_exists($this, $attr)) {
                    $this->$attr = $value;
                }
            }
            return true;
        }
        return false;
    }
    
    public function update(): bool {
        $query = "UPDATE {$this->table}
                  SET name = :name, cpf = :cpf, rg = :rg, cpts = :cpts, pis = :pis,
                      cel = :cel, contato = :contato, phone_contact = :phone_contact, turno = :turno,
                      email = :email, phone = :phone, address = :address, position = :position,
                      salary = :salary, hire_date = :hire_date, user_id = :user_id
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        foreach (get_object_vars($this) as $attr => $value) {
            if ($attr !== 'conn' && $attr !== 'table') {
                $this->$attr = $value !== null ? htmlspecialchars(strip_tags((string)$value)) : null;
            }
        }

        // Bind
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':cpf', $this->cpf);
        $stmt->bindParam(':rg', $this->rg);
        $stmt->bindParam(':cpts', $this->cpts);
        $stmt->bindParam(':pis', $this->pis);
        $stmt->bindParam(':cel', $this->cel);
        $stmt->bindParam(':contato', $this->contato);
        $stmt->bindParam(':phone_contact', $this->phone_contact);
        $stmt->bindParam(':turno', $this->turno);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':position', $this->position);
        $stmt->bindParam(':salary', $this->salary);
        $stmt->bindParam(':hire_date', $this->hire_date);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }
    
    public function delete(): bool {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags((string)$this->id));
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}
?>