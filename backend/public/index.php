<?php
// /public/index.php

// Configurações
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Lidar com preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir arquivos necessários
require_once __DIR__ . '/../api/utils/Response.php';
require_once __DIR__ . '/../api/controllers/AuthController.php';
require_once __DIR__ . '/../api/controllers/UserController.php';
require_once __DIR__ . '/../api/controllers/EmployeeController.php';
require_once __DIR__ . '/../api/middlewares/AuthMiddleware.php';

// Obter método e URI da requisição
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$path = str_replace('/public', '', $path); // Ajustar para o caminho base

// Dividir o caminho
$path_parts = explode('/', trim($path, '/'));
$resource = $path_parts[0] ?? '';
$id = $path_parts[1] ?? null;

// Roteamento
try {
    switch ("$method $resource") {
        // Rotas públicas
        case 'POST auth/login':
            $controller = new AuthController();
            $controller->login();
            break;
            
        case 'POST auth/register':
            $controller = new AuthController();
            $controller->register();
            break;
            
        case 'POST auth/forgot-password':
            $controller = new AuthController();
            $controller->forgotPassword();
            break;
            
        case 'POST auth/reset-password':
            $controller = new AuthController();
            $controller->resetPassword();
            break;
            
        // Rotas protegidas
        case 'GET auth/validate':
            AuthMiddleware::handle();
            $controller = new AuthController();
            $controller->validateToken();
            break;
            
        case 'POST auth/change-password':
            AuthMiddleware::handle();
            $controller = new AuthController();
            $controller->changePassword();
            break;
            
        case 'GET users':
            AuthMiddleware::handle();
            $controller = new UserController();
            $controller->index();
            break;
            
        case 'GET users/byid' when $id:
            AuthMiddleware::handle();
            $controller = new UserController();
            $controller->show($id);
            break;
            
        case 'POST users':
            AuthMiddleware::handle();
            $controller = new UserController();
            $controller->store();
            break;
            
        case 'PUT users' when $id:
            AuthMiddleware::handle();
            $controller = new UserController();
            $controller->update($id);
            break;
            
        case 'DELETE users' when $id:
            AuthMiddleware::handle();
            $controller = new UserController();
            $controller->destroy($id);
            break;
            
        case 'GET employees':
            AuthMiddleware::handle();
            $controller = new EmployeeController();
            $controller->index();
            break;
            
        case 'GET employees' when $id:
            AuthMiddleware::handle();
            $controller = new EmployeeController();
            $controller->show($id);
            break;
            
        case 'POST employees':
            AuthMiddleware::handle();
            $controller = new EmployeeController();
            $controller->store();
            break;
            
        case 'PUT employees' when $id:
            AuthMiddleware::handle();
            $controller = new EmployeeController();
            $controller->update($id);
            break;
            
        case 'DELETE employees' when $id:
            AuthMiddleware::handle();
            $controller = new EmployeeController();
            $controller->destroy($id);
            break;
            
        default:
            Response::error('Rota não encontrada', 404);
            break;
    }
} catch (Exception $e) {
    Response::error('Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>  