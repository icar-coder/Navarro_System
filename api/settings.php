<?php
// api/settings.php
header('Content-Type: application/json; charset=utf-8');
// Evitar que warnings/notices se impriman en HTML y rompan JSON.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
$rol = $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? '';
if ($rol !== 'Administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$configFile = __DIR__ . '/../config/settings.php';
$envFile = __DIR__ . '/../.env';
$method = $_SERVER['REQUEST_METHOD'];

// GET: devolver configuración (ocultando password real)
if ($method === 'GET') {
    if (file_exists($configFile)) {
        $cfg = @include $configFile;
        if (!is_array($cfg)) $cfg = ['smtp' => []];
        if (isset($cfg['smtp']['password']) && !empty($cfg['smtp']['password'])) {
            $cfg['smtp']['password'] = '********';
        }
        echo json_encode($cfg);
    } else {
        echo json_encode(['smtp' => []]);
    }
    exit();
}

// POST?action=save -> guarda la configuración
// POST?action=test -> prueba envío (body: { to })
$action = $_GET['action'] ?? 'save';
$body = json_decode(file_get_contents('php://input'), true) ?? [];

if ($action === 'save') {
    $allowed = ['host','port','secure','username','password','from','enabled'];
    $smtp = [];
    $old = [];
    if (file_exists($configFile)) {
        $old = @include $configFile;
        if (!is_array($old)) $old = ['smtp' => []];
    }
    $oldSmtp = $old['smtp'] ?? [];

    foreach ($allowed as $k) {
        if (array_key_exists($k, $body) && $body[$k] !== '') {
            $smtp[$k] = $body[$k];
        } elseif (array_key_exists($k, $oldSmtp)) {
            $smtp[$k] = $oldSmtp[$k];
        }
    }

    $smtp['port'] = intval($smtp['port'] ?? 587);
    $smtp['enabled'] = !empty($smtp['enabled']);

    $quoteValue = function ($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        $value = (string) $value;
        if (preg_match('/[\s#"\'=]/', $value)) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            return '"' . $escaped . '"';
        }
        return $value;
    };

    $envData = [
        'SMTP_HOST' => $smtp['host'] ?? 'smtp.gmail.com',
        'SMTP_PORT' => $smtp['port'],
        'SMTP_SECURE' => $smtp['secure'] ?? 'tls',
        'SMTP_USERNAME' => $smtp['username'] ?? '',
        'SMTP_PASSWORD' => $smtp['password'] ?? '',
        'SMTP_FROM' => $smtp['from'] ?? 'AutoGestión',
        'SMTP_ENABLED' => $smtp['enabled'] ? 'true' : 'false',
    ];

    $lines = [];
    foreach ($envData as $key => $value) {
        $lines[] = $key . '=' . $quoteValue($value);
    }

    if (file_put_contents($envFile, implode("\n", $lines) . "\n") === false) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo guardar la configuración']);
    } else {
        echo json_encode(['success' => true]);
    }
    exit();
}

if ($action === 'test') {
    // Cargar configuración preferiblemente de body, si no, del archivo
    $smtp = $body['smtp'] ?? null;
    if (!$smtp) {
        if (!file_exists($configFile)) { http_response_code(400); echo json_encode(['error'=>'No hay configuración']); exit(); }
        $cfg = include $configFile;
        $smtp = $cfg['smtp'] ?? null;
    }
    if (!$smtp || empty($smtp['username']) || empty($smtp['password'])) {
        http_response_code(400); echo json_encode(['error'=>'Faltan credenciales SMTP']); exit();
    }

    require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
    require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
    // No usar 'use' dentro de bloques ejecutables: usaremos nombres totalmente calificados (FQCN) a continuación.

    $to = $body['to'] ?? $_SESSION['usuario_email'] ?? $smtp['username'] ?? null;
    if (!$to) { http_response_code(400); echo json_encode(['error'=>'Email destinatario requerido']); exit(); }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtp['host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];
        $secure = strtolower($smtp['secure'] ?? 'tls');
        if ($secure === 'ssl') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        else $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = intval($smtp['port'] ?? 587);

        $from = $smtp['from'] ?? 'AutoGestión';
        $mail->setFrom($smtp['username'], $from);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Prueba de correo - AutoGestión';
        $mail->Body = '<p>Este es un correo de prueba enviado desde AutoGestión.</p>';
        $mail->AltBody = 'Este es un correo de prueba desde AutoGestión.';
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Correo de prueba enviado a ' . $to]);
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al enviar: ' . ($mail->ErrorInfo ?? $e->getMessage())]);
    }
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
