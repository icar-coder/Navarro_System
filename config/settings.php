<?php
// 1. Intentar leer el archivo .env local
$envFile = __DIR__ . '/../.env';
$env = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        $env[$name] = str_replace('\\n', "\n", $value);
    }
}

// 2. Evaluar 'SMTP_ENABLED' de forma segura para ambos entornos
$smtpEnabled = true; // Valor por defecto
if (array_key_exists('SMTP_ENABLED', $env)) {
    // Si existe en el archivo .env local
    $smtpEnabled = filter_var($env['SMTP_ENABLED'], FILTER_VALIDATE_BOOLEAN);
} else {
    // Si no está en el .env local, buscar en el sistema (Render)
    $renderEnv = getenv('SMTP_ENABLED');
    if ($renderEnv !== false) {
        $smtpEnabled = filter_var($renderEnv, FILTER_VALIDATE_BOOLEAN);
    }
}

// 3. Armar la configuración final cruzando el arreglo local con getenv()
$smtp = [
    'host'     => $env['SMTP_HOST']     ?? getenv('SMTP_HOST')     ?: 'smtp.gmail.com',
    'port'     => intval($env['SMTP_PORT'] ?? getenv('SMTP_PORT')  ?: 587),
    'secure'   => $env['SMTP_SECURE']   ?? getenv('SMTP_SECURE')   ?: 'tls',
    'username' => $env['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: '',
    'password' => $env['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '',
    'from'     => $env['SMTP_FROM']     ?? getenv('SMTP_FROM')     ?: 'AutoGestión',
    'enabled'  => $smtpEnabled,
];

// 4. Retornar los datos organizados
return ['smtp' => $smtp];