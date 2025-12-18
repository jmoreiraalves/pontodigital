<?php
// /public/index.php (Versão super simplificada)

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

// Obter a URI da requisição
$request_uri = $_SERVER['REQUEST_URI'];

// Remover query string se existir
if (($pos = strpos($request_uri, '?')) !== false) {
    $request_uri = substr($request_uri, 0, $pos);
}

// Normalizar a URI
$request_uri = trim($request_uri, '/');

// Se estiver acessando pela pasta public, ajustar
if (strpos($request_uri, 'public/') === 0) {
    $request_uri = substr($request_uri, 7); // Remove "public/"
}

if (strpos($request_uri, 'backend/public/') === 0) {
    $request_uri = substr($request_uri, 15); // Remove "backend/public/"
}

if (strpos($request_uri, 'pontodigital/backend/public/') === 0) {
    $request_uri = substr($request_uri, 28); // Remove "pontodigital/backend/public/"
}

// Dividir a URI em partes
$uri_parts = explode('/', $request_uri);
// var_dump($uri_parts);
$resource = $uri_parts[2] ?? '';
$action = $uri_parts[3] ?? '';
$id = $uri_parts[4] ?? null;

// Debug (remova em produção)
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("Processed URI: $request_uri");
error_log("Resource: $resource");
error_log("Action: $action");
error_log("ID: " . ($id ?: 'null'));

// Método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// var_dump($resource);
// var_dump($action);

// Roteamento principal
try {
    // ROTAS PÚBLICAS
    // POST /auth/login
    if ($method == 'POST' && $resource == 'auth' && $action == 'login') {
        $controller = new AuthController();
        $controller->login();
        exit();
    }
    
    // POST /auth/register
    if ($method == 'POST' && $resource == 'auth' && $action == 'register') {
        $controller = new AuthController();
        $controller->register();
        exit();
    }
    
    // POST /auth/forgot-password
    if ($method == 'POST' && $resource == 'auth' && $action == 'forgot-password') {
        $controller = new AuthController();
        $controller->forgotPassword();
        exit();
    }
    
    // POST /auth/reset-password
    if ($method == 'POST' && $resource == 'auth' && $action == 'reset-password') {
        $controller = new AuthController();
        $controller->resetPassword();
        exit();
    }
    
    // ROTAS PROTEGIDAS
    // Todas as rotas abaixo exigem autenticação
    $authData = AuthMiddleware::handle();
    
    // GET /auth/validate
    if ($method == 'GET' && $resource == 'auth' && $action == 'validate') {
        $controller = new AuthController();
        $controller->validateToken();
        exit();
    }
    
    // POST /auth/change-password
    if ($method == 'POST' && $resource == 'auth' && $action == 'change-password') {
        $controller = new AuthController();
        $controller->changePassword();
        exit();
    }
    
    // CRUD USUÁRIOS
    if ($resource == 'users') {
        $controller = new UserController();
        
        // GET /users
        if ($method == 'GET' && $action == '') {
            $controller->index();
            exit();
        }
        
        // GET /users/{id}
        if ($method == 'GET' && is_numeric($action)) {
            $controller->show($action);
            exit();
        }
        
        // POST /users
        if ($method == 'POST' && $action == '') {
            $controller->store();
            exit();
        }
        
        // PUT /users/{id}
        if ($method == 'PUT' && is_numeric($action)) {
            $controller->update($action);
            exit();
        }
        
        // DELETE /users/{id}
        if ($method == 'DELETE' && is_numeric($action)) {
            $controller->destroy($action);
            exit();
        }
    }
    
    // CRUD FUNCIONÁRIOS
    if ($resource == 'employees') {
        $controller = new EmployeeController();
        
        // GET /employees
        if ($method == 'GET' && $action == '') {
            $controller->index();
            exit();
        }
        
        // GET /employees/{id}
        if ($method == 'GET' && is_numeric($action)) {
            $controller->show($action);
            exit();
        }
        
        // POST /employees
        if ($method == 'POST' && $action == '') {
            $controller->store();
            exit();
        }
        
        // PUT /employees/{id}
        if ($method == 'PUT' && is_numeric($action)) {
            $controller->update($action);
            exit();
        }
        
        // DELETE /employees/{id}
        if ($method == 'DELETE' && is_numeric($action)) {
            $controller->destroy($action);
            exit();
        }
    }
    
    // Se chegou aqui, rota não encontrada
    Response::error('Rota não encontrada: ' . $request_uri, 404);
    
} catch (Exception $e) {
    Response::error('Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>