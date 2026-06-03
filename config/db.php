<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // 1. Intentar leer el archivo .env local
        $envFile = __DIR__ . '/../.env'; // Ajusta la ruta si tu .env está en otra carpeta
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

        // 2. Asignar valores cruzando el archivo local con getenv() de la nube
        $this->host     = $env['DB_HOST']     ?? getenv('DB_HOST')     ?: 'localhost';
        $this->db_name  = $env['DB_NAME']     ?? getenv('DB_NAME')     ?: 'sistema_vehiculos_update';
        $this->username = $env['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
        $this->password = $env['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
        $this->port     = intval($env['DB_PORT'] ?? getenv('DB_PORT')  ?: 3306);
    }

    public function getConnection() {
        $this->conn = null;
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Pasamos también el puerto al constructor de mysqli por si el servidor externo usa uno diferente a 3306
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);
            $this->conn->set_charset("utf8mb4"); 
            
        } catch (Exception $e) {
            error_log("Falla de conexión: " . $e->getMessage());
            http_response_code(500);
            if (ini_get('display_errors')) {
                echo json_encode(["error" => "Falla de conexión", "message" => $e->getMessage()]);
            } else {
                echo json_encode(["error" => "Error interno del servidor."]);
            }
            exit;
        }
        return $this->conn;
    }
}
?>