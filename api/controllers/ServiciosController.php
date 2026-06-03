<?php
class ServiciosController {
    private $db;
    public function __construct($db) { $this->db = $db; }
    
    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET': 
                if ($id) $this->obtenerServicio($id); 
                else $this->listarServicios(); 
                break;
            case 'POST': 
                $this->crearServicio(); 
                break;
            case 'PUT': 
                $this->actualizarServicio($id); 
                break;
            case 'DELETE': 
                $this->eliminarServicio($id); 
                break;
            default: 
                http_response_code(405); 
                echo json_encode(["message" => "Método no permitido"]);
        }
    }
    
    private function listarServicios() {
        $activos = $_GET['activos'] ?? null;
        if ($activos === '1') {
            $result = $this->db->query("SELECT * FROM servicio WHERE estaActivo = 1 ORDER BY nombre");
        } else {
            $result = $this->db->query("SELECT * FROM servicio ORDER BY nombre");
        }
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }
    
    private function obtenerServicio($id) {
        $stmt = $this->db->prepare("SELECT * FROM servicio WHERE idServicio = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        echo json_encode($res ?: ["error" => "No encontrado"]);
    }
    
    private function crearServicio() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['codigo']) || empty($data['nombre']) || !isset($data['precioUnitario'])) {
            http_response_code(400);
            echo json_encode(["message" => "Código, nombre y precio son obligatorios"]);
            return;
        }
        $stmt = $this->db->prepare("INSERT INTO servicio (codigo, nombre, descripcion, precioUnitario, estaActivo) VALUES (?, ?, ?, ?, ?)");
        $estaActivo = $data['estaActivo'] ?? 1;
        $stmt->bind_param("sssdi", $data['codigo'], $data['nombre'], $data['descripcion'], $data['precioUnitario'], $estaActivo);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "id" => $stmt->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al crear: " . $stmt->error]);
        }
    }
    
    private function actualizarServicio($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $this->db->prepare("UPDATE servicio SET codigo=?, nombre=?, descripcion=?, precioUnitario=?, estaActivo=? WHERE idServicio=?");
        $stmt->bind_param("sssdii", $data['codigo'], $data['nombre'], $data['descripcion'], $data['precioUnitario'], $data['estaActivo'], $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar"]);
        }
    }
    
    private function eliminarServicio($id) {
        // Verificar si tiene detalles asociados
        $check = $this->db->prepare("SELECT COUNT(*) as total FROM detalle_ot_servicio WHERE idServicio = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row['total'] > 0) {
            http_response_code(409);
            echo json_encode(["message" => "No se puede eliminar porque tiene órdenes de trabajo asociadas"]);
            return;
        }
        $stmt = $this->db->prepare("DELETE FROM servicio WHERE idServicio = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al eliminar"]);
        }
    }
}
?>