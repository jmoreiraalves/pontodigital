<?php
// src/helpers.php
declare(strict_types=1);

/**
 * Exibe dados formatados para debug
 */
function debugPrint($data, bool $exit = false): void {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($exit) exit;
}

/**
 * Exibe dados com var_dump formatado
 */
function debugDump($data, bool $exit = false): void {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if ($exit) exit;
}


/**
 * Escapa string para uso seguro em SQL
 */
function escapeString(string $str): string {
    return addslashes(trim($str));
}

function formatMoney(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function formatFloatBR(float $value): string {
    return  number_format($value, 2, ',', '.');
}

function formatarMoedaParaDecimal($valor) {
    // Remove o símbolo de moeda e espaços
    $valor = str_replace(['R$', ' '], '', $valor);
    
    // Substitui o ponto por vazio (separador de milhar)
    $valor = str_replace('.', '', $valor);
    
    // Substitui a vírgula por ponto (separador decimal)
    $valor = str_replace(',', '.', $valor);
    
    return $valor;
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


