<?php
// api/controllers/AuthController.php

// Incluir PHPMailer manualmente (sin Composer)
// Ajusta las rutas según dónde hayas colocado los archivos de PHPMailer
require_once __DIR__ . '/../../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AuthController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Maneja las peticiones según la acción (endpoint)
     */
    public function handleRequest($action, $method = 'POST') {
        switch ($action) {
            case 'login':
                if ($method === 'POST') $this->login();
                else $this->methodNotAllowed();
                break;
            case 'solicitar-recuperacion':
                if ($method === 'POST') $this->solicitarRecuperacion();
                else $this->methodNotAllowed();
                break;
            case 'resetear-password':
                if ($method === 'POST') $this->resetearPassword();
                else $this->methodNotAllowed();
                break;
            default:
                http_response_code(404);
                echo json_encode(["error" => "Acción no válida"]);
        }
    }
    
    /**
     * Inicio de sesión
     * POST: { email, password }
     * Responde: { status, message, user? }
     */
    private function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "success" => false, "message" => "Correo y contraseña son obligatorios"]);
            return;
        }
        
        // Buscar usuario por email
        $stmt = $this->db->prepare("SELECT idUsuario, nombre, apellido, email, contrasenia, rol FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user || !password_verify($password, $user['contrasenia'])) {
            http_response_code(401);
            echo json_encode(["status" => "error", "success" => false, "message" => "Credenciales incorrectas"]);
            return;
        }
        
        // Iniciar sesión (guardar datos en $_SESSION)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['usuario_id'] = $user['idUsuario'];
        $_SESSION['usuario_nombre'] = $user['nombre'] . ' ' . $user['apellido'];
        $_SESSION['usuario_email'] = $user['email'];
        $_SESSION['usuario_rol'] = $user['rol'];
        
        echo json_encode([
            "status" => "success",
            "success" => true,
            "message" => "Inicio de sesión exitoso",
            "user" => [
                "id" => $user['idUsuario'],
                "nombre" => $user['nombre'],
                "apellido" => $user['apellido'],
                "email" => $user['email'],
                "rol" => $user['rol']
            ]
        ]);
    }
    
    /**
     * Solicitar recuperación de contraseña
     * POST: { email }
     * Envía un correo con enlace para restablecer la contraseña
     */
    private function solicitarRecuperacion() {
        error_log('AuthController::solicitarRecuperacion called. REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '') );
        $data = json_decode(file_get_contents("php://input"), true);
        $email = trim($data['email'] ?? '');
        
        error_log('AuthController::solicitarRecuperacion email=' . $email);
        if (empty($email)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "success" => false, "message" => "El correo electrónico es obligatorio"]);
            return;
        }
        
        // Verificar si el email existe (por seguridad, respondemos siempre el mismo mensaje)
        $stmt = $this->db->prepare("SELECT idUsuario, nombre, email FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            // Responder éxito falso pero sin revelar que el email no existe
            echo json_encode(["status" => "success", "success" => true, "message" => "Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña"]);
            return;
        }
        
        // Generar token único y fecha de expiración (1 hora)
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Guardar token en la base de datos
        $stmt = $this->db->prepare("UPDATE usuario SET token_recuperacion = ?, token_expira = ? WHERE idUsuario = ?");
        $stmt->bind_param("ssi", $token, $expira, $user['idUsuario']);
        if (!$stmt->execute()) {
            error_log('AuthController::solicitarRecuperacion failed to save token for user id ' . $user['idUsuario']);
            http_response_code(500);
            echo json_encode(["status" => "error", "success" => false, "message" => "Error al procesar la solicitud"]);
            return;
        }
        
        // Construir enlace de restablecimiento
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        // El endpoint se ejecuta bajo /api, pero el archivo de reset está en la raíz del proyecto.
        $basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
        // Ajusta la ruta a tu archivo resetear_contrasena.php
        $resetUrl = $protocol . "://" . $host . $basePath . "/resetear_contrasena.php?token=" . $token;
        
// Leer configuración SMTP de config/settings.php
        $settingsFile = __DIR__ . '/../../config/settings.php';
        $smtpConfig = [];
        if (file_exists($settingsFile)) {
            $cfg = @include $settingsFile;
            if (is_array($cfg) && isset($cfg['smtp']) && is_array($cfg['smtp'])) {
                $smtpConfig = $cfg['smtp'];
            }
        }

        if (empty($smtpConfig['username']) || empty($smtpConfig['password']) || empty($smtpConfig['host'])) {
            error_log('AuthController::solicitarRecuperacion missing SMTP config in ' . $settingsFile);
            http_response_code(500);
            echo json_encode(["status" => "error", "success" => false, "message" => "No se pudo enviar el correo. Configuración SMTP incompleta."]);
            return;
        }
        if (empty($smtpConfig['enabled'])) {
            error_log('AuthController::solicitarRecuperacion SMTP config disabled');
            http_response_code(500);
            echo json_encode(["status" => "error", "success" => false, "message" => "No se pudo enviar el correo. Configuración SMTP deshabilitada."]);
            return;
        }

        // Enviar correo usando PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuración SMTP desde settings.php
            $mail->isSMTP();
            $mail->Host       = $smtpConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpConfig['username'];
            $mail->Password   = $smtpConfig['password'];
            $secure = strtolower($smtpConfig['secure'] ?? 'tls');
            $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = intval($smtpConfig['port'] ?? 587);

            // Remitente y destinatario
            $mail->setFrom($smtpConfig['username'], $smtpConfig['from'] ?? 'AutoGestión');
            $mail->addAddress($user['email'], $user['nombre']);
            
            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Recupera tu contraseña - AutoGestión';
            $mail->Body = "
                <html>
                <head><style>body{font-family:Arial,sans-serif}</style></head>
                <body>
                    <h2>Hola {$user['nombre']}</h2>
                    <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para crear una nueva:</p>
                    <p><a href='{$resetUrl}' style='background:#3b82f6; color:white; padding:10px 20px; text-decoration:none; border-radius:40px;'>Restablecer contraseña</a></p>
                    <p>Este enlace expirará en 1 hora. Si no solicitaste este cambio, ignora este mensaje.</p>
                    <p>Saludos,<br>Equipo de AutoGestión</p>
                </body>
                </html>
            ";
            $mail->AltBody = "Hola {$user['nombre']}, accede al siguiente enlace para restablecer tu contraseña: {$resetUrl}";
            
            $mail->send();
            echo json_encode(["status" => "success", "success" => true, "message" => "Se ha enviado un enlace a tu correo electrónico"]);
        } catch (Exception $e) {
            // Registrar error en log del servidor
            error_log("Error al enviar correo: " . $mail->ErrorInfo . " | exception=" . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "success" => false, "message" => "No se pudo enviar el correo. Intenta más tarde."]);
        }
    }
    
    /**
     * Restablecer contraseña usando token
     * POST: { token, nueva_password }
     */
    private function resetearPassword() {
        $data = json_decode(file_get_contents("php://input"), true);
        $token = trim($data['token'] ?? '');
        $nuevaPassword = $data['password'] ?? '';
        
        if (empty($token) || empty($nuevaPassword)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "success" => false, "message" => "Token y nueva contraseña son obligatorios"]);
            return;
        }
        
        if (strlen($nuevaPassword) < 6) {
            http_response_code(400);
            echo json_encode(["status" => "error", "success" => false, "message" => "La contraseña debe tener al menos 6 caracteres"]);
            return;
        }
        
        // Buscar usuario con token válido y no expirado
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT idUsuario FROM usuario WHERE token_recuperacion = ? AND token_expira > ?");
        $stmt->bind_param("ss", $token, $now);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            http_response_code(400);
            echo json_encode(["status" => "error", "success" => false, "message" => "El enlace es inválido o ha expirado"]);
            return;
        }
        
        // Hashear nueva contraseña
        $hashed = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        
        // Actualizar contraseña y limpiar token
        $stmt = $this->db->prepare("UPDATE usuario SET contrasenia = ?, token_recuperacion = NULL, token_expira = NULL WHERE idUsuario = ?");
        $stmt->bind_param("si", $hashed, $user['idUsuario']);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "success" => true, "message" => "Contraseña actualizada correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "success" => false, "message" => "Error al actualizar la contraseña"]);
        }
    }
    
    private function methodNotAllowed() {
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
    }
}
?>