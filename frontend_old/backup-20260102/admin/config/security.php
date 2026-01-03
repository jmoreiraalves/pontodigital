<?php

// config/security.php
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos

// Proteção contra XSS
function escapeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Proteção contra CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Validação de arquivos upload
function validateUploadedFile($file) {
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Verificar se é realmente um PDF
    if ($file['type'] == 'application/pdf') {
        $content = file_get_contents($file['tmp_name']);
        if (strpos($content, '%PDF') !== 0) {
            return false;
        }
    }
    
    return true;
}
