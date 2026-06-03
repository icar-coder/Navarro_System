<?php
class ModelosController {
    private $db;
    public function __construct($db) { $this->db = $db; }
    
    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET': 
                if ($id) $this->obtenerModelo($id); 
                else $this->listarModelos(); 
                break;
            case 'POST': 
                $this->crearModelo(); 
                break;
            case 'PUT': 
                $this->actualizarModelo($id); 
                break;
            case 'DELETE': 
                $this->eliminarModelo($id); 
                break;
            default: 
                http_response_code(405);
        }
    }
    
    private function listarModelos() {
        $marcaId = $_GET['marca'] ?? null;
        if ($marcaId) {
            $stmt = $this->db->prepare("SELECT * FROM modelos WHERE idMarca = ? ORDER BY nombre_modelo");
            $stmt->bind_param("i", $marcaId);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $this->db->query("SELECT m.*, ma.nombre_marca FROM modelos m JOIN marcas ma ON m.idMarca = ma.idMarca ORDER BY ma.nombre_marca, m.nombre_modelo");
        }
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    }
    
    private function obtenerModelo($id) {
        $stmt = $this->db->prepare("SELECT * FROM modelos WHERE idModelo = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    }
    
    private function crearModelo() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['nombre_modelo']) || empty($data['idMarca'])) {
            http_response_code(400);
            echo json_encode(["message" => "Nombre y marca son requeridos"]);
            return;
        }
        $stmt = $this->db->prepare("INSERT INTO modelos (idMarca, nombre_modelo) VALUES (?, ?)");
        $stmt->bind_param("is", $data['idMarca'], $data['nombre_modelo']);
        $stmt->execute();
        echo json_encode(["status" => "success", "id" => $stmt->insert_id]);
    }
    
    private function actualizarModelo($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->db->prepare("UPDATE modelos SET idMarca = ?, nombre_modelo = ? WHERE idModelo = ?");
        $stmt->bind_param("isi", $data['idMarca'], $data['nombre_modelo'], $id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    }
    
    private function eliminarModelo($id) {
        // Verificar si hay vehículos usando este modelo
        $check = $this->db->prepare("SELECT COUNT(*) as total FROM vehiculo WHERE idModelo = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row['total'] > 0) {
            http_response_code(409);
            echo json_encode(["message" => "No se puede eliminar porque hay vehículos con este modelo"]);
            return;
        }
        $stmt = $this->db->prepare("DELETE FROM modelos WHERE idModelo = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    }
}
?>