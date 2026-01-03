<?php
session_start();

// Auto carregamento de classes
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/../classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Verificar timeout da sessão
function checkSessionTimeout() {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: ./index.php?timeout=1");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Sanitizar inputs
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Redirecionar se não estiver logado
function requireLogin() {
    if (!isset($_SESSION['user_id']) || !isset( $_SESSION['nome'])) {
        header("Location: ./index.php");
        exit();
    }
}

// Funções auxiliares PHP
function getStatusClass($status) {
    $classes = [
        'ativo' => 'success',
        'pendente' => 'warning',
        'em_analise' => 'info',
        'concluido' => 'primary',
        'cancelado' => 'secondary',
        'vencido' => 'danger',
        'rascunho' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}

function getVencimentoClass($dias) {
    if ($dias === null) return 'secondary';
    if ($dias <= 0) return 'danger';
    if ($dias <= 7) return 'warning';
    if ($dias <= 30) return 'info';
    return 'success';
}

function formatTipoContrato($tipo) {
    $tipos = [
        'prestacao_servicos' => 'Prest. Serviços',
        'locacao' => 'Locação',
        'compra_venda' => 'Compra/Venda',
        'parceria' => 'Parceria',
        'trabalho' => 'Trabalho',
        'fornecimento' => 'Fornecimento',
        'outro' => 'Outro'
    ];
    return $tipos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
}

function getSortIcon($column) {
    global $filtro_ordenar, $filtro_direcao;
    
    if ($filtro_ordenar != $column) {
        return '<i class="fas fa-sort text-muted"></i>';
    }
    
    return $filtro_direcao == 'asc' 
        ? '<i class="fas fa-sort-up"></i>' 
        : '<i class="fas fa-sort-down"></i>';
}

function buildOrderLink($column) {
    global $filtro_ordenar, $filtro_direcao;
    
    $params = $_GET;
    $params['ordenar'] = $column;
    
    if ($filtro_ordenar == $column) {
        $params['direcao'] = $filtro_direcao == 'asc' ? 'desc' : 'asc';
    } else {
        $params['direcao'] = 'asc';
    }
    
    unset($params['pagina']); // Manter na primeira página ao ordenar
    
    return http_build_query($params);
}

function buildPageLink($page) {
    $params = $_GET;
    $params['pagina'] = $page;
    return http_build_query($params);
}
 
// Função para formatar nível de acesso 22/12/2025
function formatNivelAcesso($nivel) {
    $niveis = [
        'admin' => 'Admin',
        'gerente' => 'Gerente',
        'usuario' => 'Usuário'
    ];
    return $niveis[$nivel] ?? $nivel;
}

// Função para obter classe CSS do nível de acesso
function getNivelAcessoClass($nivel) {
    $classes = [
        'admin' => 'danger',
        'gerente' => 'warning',
        'usuario' => 'info'
    ];
    return $classes[$nivel] ?? 'secondary';
}

// Função para validar CPF
function validar_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Validação do CPF
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

// Função para formatar CPF
function formatar_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Função para formatar CNPJ
function formatar_cnpj($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
}

// Função para converter para maiúsculo
function toUpperCase($string) {
    return mb_strtoupper($string, 'UTF-8');
}

// Função para verificar permissões
function hasPermission($tipo, $required) {
    $hierarquia = [
        'super' => 4,
        'admin' => 3,
        'ti' => 2,
        'gestor' => 1
    ];
    
    if (!isset($hierarquia[$tipo]) || !isset($hierarquia[$required])) {
        return false;
    }
    
    return $hierarquia[$tipo] >= $hierarquia[$required];
}

// Função para obter turnos
function getTurnos() {
    return [
        'matutino' => 'Matutino (06:00 - 12:00)',
        'vespertino' => 'Vespertino (12:00 - 18:00)',
        'noturno' => 'Noturno (18:00 - 00:00)',
        'flexivel' => 'Flexível'
    ];
}

// Função para obter tipos de ponto
function getTiposPonto() {
    return [
        'entrada' => 'Entrada',
        'saida' => 'Saída',
        'entrada_intervalo' => 'Entrada Intervalo',
        'retorno_intervalo' => 'Retorno Intervalo'
    ];
}

// Função para calcular horas trabalhadas
function calcular_horas_trabalhadas($colaborador_id, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT tipo, hora_registro 
                          FROM registros_ponto 
                          WHERE colaborador_id = ? AND data_registro = ?
                          ORDER BY hora_registro");
    $stmt->execute([$colaborador_id, $data]);
    $registros = $stmt->fetchAll();
    
    $horas = 0;
    $entrada = null;
    
    foreach ($registros as $registro) {
        if ($registro['tipo'] == 'entrada') {
            $entrada = new DateTime($registro['hora_registro']);
        } elseif ($registro['tipo'] == 'saida' && $entrada) {
            $saida = new DateTime($registro['hora_registro']);
            $intervalo = $entrada->diff($saida);
            $horas += $intervalo->h + ($intervalo->i / 60);
            $entrada = null;
        }
    }
    
    return number_format($horas, 2);
}

function slugify(string $texto): string {
    // 1. Normaliza para UTF-8 e remove acentos
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);

    // 2. Remove caracteres não alfanuméricos (mantém espaço e traço)
    $texto = preg_replace('/[^a-zA-Z0-9\s-]/', '', $texto);

    // 3. Substitui espaços em branco por traços
    $texto = preg_replace('/[\s]+/', '-', $texto);

    // 4. Converte para minúsculas
    $texto = strtolower($texto);

    // 5. Remove traços duplicados
    $texto = preg_replace('/-+/', '-', $texto);

    // 6. Remove traços no início/fim
    return trim($texto, '-');
}
