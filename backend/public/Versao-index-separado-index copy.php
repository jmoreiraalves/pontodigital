<?php
// /public/index.php (Versão com Router separado)

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

class Router {
    private $routes = [];
    
    public function addRoute($method, $path, $handler, $requireAuth = false) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'requireAuth' => $requireAuth
        ];
    }
    
    public function dispatch($method, $uri) {
        $path = parse_url($uri, PHP_URL_PATH);~

        // print_r( $method);
        // print_r($uri);
        
        // Remover base path se existir
        $base_path = '/public';
        if (strpos($path, $base_path) === 0) {
            $path = substr($path, strlen($base_path));
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
             //var_dump($route);
            // Converter padrão de rota (ex: /users/:id) para regex
            $pattern = $this->convertPatternToRegex($route['path']);
            
            if (preg_match($pattern, $path, $matches)) {
                // Verificar autenticação se necessário
                if ($route['requireAuth']) {
                    AuthMiddleware::handle();
                }
                
                // Extrair parâmetros da URL
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Chamar o handler
                call_user_func_array($route['handler'], array_values($params));
                return;
            }
        }
        
        // Nenhuma rota encontrada
        Response::error('Rota não encontrada: '.$path, 404);
    }
    
    private function convertPatternToRegex($pattern) {
        // Converter :param para regex
        $regex = preg_replace('/:([a-zA-Z]+)/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}

// Configurar rotas
$router = new Router();

// Rotas públicas
$router->addRoute('POST', '/auth/login', function() {
    $controller = new AuthController();
    $controller->login();
});

$router->addRoute('POST', '/auth/register', function() {
    $controller = new AuthController();
    $controller->register();
});

$router->addRoute('POST', '/auth/forgot-password', function() {
    $controller = new AuthController();
    $controller->forgotPassword();
});

$router->addRoute('POST', '/auth/reset-password', function() {
    $controller = new AuthController();
    $controller->resetPassword();
});

// Rotas protegidas de autenticação
$router->addRoute('GET', '/auth/validate', function() {
    $controller = new AuthController();
    $controller->validateToken();
}, true);

$router->addRoute('POST', '/auth/change-password', function() {
    $controller = new AuthController();
    $controller->changePassword();
}, true);

// CRUD Usuários (protegido)
$router->addRoute('GET', '/users', function() {
    $controller = new UserController();
    $controller->index();
}, true);

$router->addRoute('GET', '/users/:id', function($id) {
    $controller = new UserController();
    $controller->show($id);
}, true);

$router->addRoute('POST', '/users', function() {
    $controller = new UserController();
    $controller->store();
}, true);

$router->addRoute('PUT', '/users/:id', function($id) {
    $controller = new UserController();
    $controller->update($id);
}, true);

$router->addRoute('DELETE', '/users/:id', function($id) {
    $controller = new UserController();
    $controller->destroy($id);
}, true);

// CRUD Funcionários (protegido)
$router->addRoute('GET', '/employees', function() {
    $controller = new EmployeeController();
    $controller->index();
}, true);

$router->addRoute('GET', '/employees/:id', function($id) {
    $controller = new EmployeeController();
    $controller->show($id);
}, true);

$router->addRoute('POST', '/employees', function() {
    $controller = new EmployeeController();
    $controller->store();
}, true);

$router->addRoute('PUT', '/employees/:id', function($id) {
    $controller = new EmployeeController();
    $controller->update($id);
}, true);

$router->addRoute('DELETE', '/employees/:id', function($id) {
    $controller = new EmployeeController();
    $controller->destroy($id);
}, true);

// Executar router
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Exception $e) {
    Response::error('Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>