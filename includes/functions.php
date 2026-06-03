<?php
// includes/functions.php
// Utilidades y validadores reutilizables para entradas del sistema

function sanitize_input($value) {
    if (is_array($value)) return array_map('sanitize_input', $value);
    $v = trim($value ?? '');
    $v = strip_tags($v);
    // normalizar espacios y caracteres invisibles
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', '', $v);
    return $v;
}

function validate_required($value) {
    return !(is_null($value) || $value === '' || (is_array($value) && empty($value)));
}

function validate_email($email) {
    if (!$email) return false;
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_phone($phone) {
    if (!$phone) return false;
    // Permitir dígitos, espacios, guiones, plus
    return preg_match('/^[0-9+\-()\s]{6,20}$/', $phone);
}

function validate_date($date, $format = 'Y-m-d') {
    if (!$date) return false;
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validate_integer($val) {
    return filter_var($val, FILTER_VALIDATE_INT) !== false;
}

function validate_plate($plate) {
    if (!$plate) return false;
    // Acepta letras, números y guiones, entre 3 y 12 caracteres
    return preg_match('/^[A-Za-z0-9\-]{3,12}$/', strtoupper($plate));
}

function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function json_success($data = []) {
    echo json_encode($data);
    exit;
}

?>
