<?php
class MarcasController {
    private $db;
    public function __construct($db) { $this->db = $db; }
    
    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET': 
                if ($id) $this->obtenerMarca($id); 
                else $this->listarMarcas(); 
                break;
            case 'POST': 
                $this->crearMarca(); 
                break;
            case 'PUT': 
                $this->actualizarMarca($id); 
                break;
            case 'DELETE': 
                $this->eliminarMarca($id); 
                break;
            default: 
                http_response_code(405);
        }
    }
    
    private function listarMarcas() {
        $res = $this->db->query("SELECT * FROM marcas ORDER BY nombre_marca");
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    }
    
    private function obtenerMarca($id) {
        $stmt = $this->db->prepare("SELECT * FROM marcas WHERE idMarca = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    }
    
    private function crearMarca() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['nombre_marca'])) {
            http_response_code(400);
            echo json_encode(["message" => "Nombre de marca requerido"]);
            return;
        }
        $stmt = $this->db->prepare("INSERT INTO marcas (nombre_marca) VALUES (?)");
        $stmt->bind_param("s", $data['nombre_marca']);
        $stmt->execute();
        echo json_encode(["status" => "success", "id" => $stmt->insert_id]);
    }
    
    private function actualizarMarca($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->db->prepare("UPDATE marcas SET nombre_marca = ? WHERE idMarca = ?");
        $stmt->bind_param("si", $data['nombre_marca'], $id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    }
    
    private function eliminarMarca($id) {
        // Verificar si hay modelos asociados
        $check = $this->db->prepare("SELECT COUNT(*) as total FROM modelos WHERE idMarca = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row['total'] > 0) {
            http_response_code(409);
            echo json_encode(["message" => "No se puede eliminar porque tiene modelos asociados"]);
            return;
        }
        $stmt = $this->db->prepare("DELETE FROM marcas WHERE idMarca = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    }
}
?>