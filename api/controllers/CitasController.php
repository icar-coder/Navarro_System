<?php
class CitasController {
    private $db;
    public function __construct($db) { $this->db = $db; }
    
    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) $this->obtenerCita($id);
                else $this->listarCitas();
                break;
            case 'POST':
                $this->crearCita();
                break;
            case 'PUT':
                $this->actualizarCita($id);
                break;
            case 'DELETE':
                $this->eliminarCita($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido"]);
        }
    }
    
    private function listarCitas() {
        $filtro = $_GET['filtro'] ?? 'todas';
        $sql = "SELECT c.*, CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre, v.placa
                FROM citas c
                JOIN cliente cl ON c.idCliente = cl.idCliente
                LEFT JOIN vehiculo v ON c.idVehiculo = v.idVehiculo";
        if ($filtro === 'pendientes') {
            $sql .= " WHERE c.estado = 'Pendiente'";
        } elseif ($filtro === 'hoy') {
            $sql .= " WHERE DATE(c.fechaHora) = CURDATE()";
        }
        $sql .= " ORDER BY c.fechaHora ASC";
        $res = $this->db->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    }
    
    private function obtenerCita($id) {
        $stmt = $this->db->prepare("SELECT * FROM citas WHERE idCita = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    }
    
    private function crearCita() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['idCliente']) || empty($data['fechaHora'])) {
            http_response_code(400);
            echo json_encode(["message" => "Cliente y fecha/hora son obligatorios"]);
            return;
        }
        $stmt = $this->db->prepare("INSERT INTO citas (idCliente, idVehiculo, fechaHora, motivo, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?)");
        $estado = $data['estado'] ?? 'Pendiente';
        $stmt->bind_param("iissss", $data['idCliente'], $data['idVehiculo'], $data['fechaHora'], $data['motivo'], $estado, $data['observaciones']);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "id" => $stmt->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear cita"]);
        }
    }
    
    private function actualizarCita($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->db->prepare("UPDATE citas SET idCliente=?, idVehiculo=?, fechaHora=?, motivo=?, estado=?, observaciones=? WHERE idCita=?");
        $stmt->bind_param("iissssi", $data['idCliente'], $data['idVehiculo'], $data['fechaHora'], $data['motivo'], $data['estado'], $data['observaciones'], $id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    }
    
    private function eliminarCita($id) {
        $stmt = $this->db->prepare("DELETE FROM citas WHERE idCita = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    }
}
?>