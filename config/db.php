<?php
class Database {
    private $host = "localhost";
    private $db_name = "sistema_vehiculos_update";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Usamos MySQLi con reporte de errores para debug profesional
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            $this->conn->set_charset("utf8mb4"); // Vital para las tildes y ñ
        } catch (Exception $e) {
            // No mostramos el error al usuario, lo guardamos en el log
            error_log("Falla de conexión: " . $e->getMessage());
            exit("Error interno del servidor.");
        }
        return $this->conn;
    }
}