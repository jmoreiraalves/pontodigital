<?php
// /api/controllers/EmployeeController.php

require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class EmployeeController {
    private $employee;
    
    public function __construct() {
        $this->employee = new Employee();
    }
    
    public function index() {
        $stmt = $this->employee->read();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        Response::success($employees);
    }
    
    public function show($id) {
        $this->employee->id = $id;
        
        if ($this->employee->readOne()) {
            Response::success([
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'email' => $this->employee->email,
                'phone' => $this->employee->phone,
                'address' => $this->employee->address,
                'position' => $this->employee->position,
                'salary' => $this->employee->salary,
                'hire_date' => $this->employee->hire_date,
                'user_id' => $this->employee->user_id
            ]);
        } else {
            Response::error('Funcionário não encontrado', 404);
        }
    }
    
    public function store() {
        $data = json_decode(file_get_contents("php://input"));
        
        $this->employee->name = $data->name;
        $this->employee->email = $data->email;
        $this->employee->phone = $data->phone;
        $this->employee->address = $data->address;
        $this->employee->position = $data->position;
        $this->employee->salary = $data->salary;
        $this->employee->hire_date = $data->hire_date;
        $this->employee->user_id = $_SERVER['USER_ID'];
        
        if ($this->employee->create()) {
            Response::success(null, 'Funcionário criado com sucesso', 201);
        } else {
            Response::error('Não foi possível criar o funcionário');
        }
    }
    
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"));
        
        $this->employee->id = $id;
        $this->employee->name = $data->name;
        $this->employee->email = $data->email;
        $this->employee->phone = $data->phone;
        $this->employee->address = $data->address;
        $this->employee->position = $data->position;
        $this->employee->salary = $data->salary;
        $this->employee->hire_date = $data->hire_date;
        
        if ($this->employee->update()) {
            Response::success(null, 'Funcionário atualizado com sucesso');
        } else {
            Response::error('Não foi possível atualizar o funcionário');
        }
    }
    
    public function destroy($id) {
        $this->employee->id = $id;
        
        if ($this->employee->delete()) {
            Response::success(null, 'Funcionário deletado com sucesso');
        } else {
            Response::error('Não foi possível deletar o funcionário');
        }
    }
}
?>