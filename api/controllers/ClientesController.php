<?php
// api/controllers/ClientesController.php

class ClientesController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Añadimos $id como parámetro para capturar lo que viene del index.php
    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->obtenerCliente($id);
                } else {
                    $this->listarClientes();
                }
                break;
            case 'POST':
                $this->guardarCliente();
                break;
            case 'PUT':
                $this->actualizarCliente($id);
                break;
            case 'DELETE':
                $this->eliminarCliente($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido"]);
                break;
        }
    }

    private function listarClientes() {
        $search = $_GET['search'] ?? null;
        $estado = $_GET['estado'] ?? null;
        $fechaInicio = $_GET['fechaInicio'] ?? null;
        $fechaFin = $_GET['fechaFin'] ?? null;

        $sql = "SELECT * FROM cliente";
        $conditions = [];
        $params = [];
        $types = "";

        if ($search) {
            $conditions[] = "(nombre LIKE ? OR apellido LIKE ? OR identificacion LIKE ? OR telefono LIKE ? OR email LIKE ?)";
            $likeSearch = "%$search%";
            $params = array_merge($params, [$likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch]);
            $types .= "sssss";
        }
        if ($estado && $estado !== 'todos') {
            $conditions[] = "estado = ?";
            $params[] = $estado;
            $types .= "s";
        }
        if ($fechaInicio) {
            $conditions[] = "fechaRegistro >= ?";
            $params[] = $fechaInicio;
            $types .= "s";
        }
        if ($fechaFin) {
            $conditions[] = "fechaRegistro <= ?";
            $params[] = $fechaFin;
            $types .= "s";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
            $sql .= " ORDER BY fechaRegistro DESC";
            $stmt = $this->db->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query("SELECT * FROM cliente ORDER BY fechaRegistro DESC");
        }

        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }

    private function obtenerCliente($id) {
        $stmt = $this->db->prepare("SELECT * FROM cliente WHERE idCliente = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Cliente no encontrado"]);
        }
    }

    private function guardarCliente() {
        $data = json_decode(file_get_contents("php://input"), true);

        $identificacion = sanitize_input($data['identificacion'] ?? '');
        $nombre = sanitize_input($data['nombre'] ?? '');
        $apellido = sanitize_input($data['apellido'] ?? '');
        $telefono = sanitize_input($data['telefono'] ?? '');
        $email = sanitize_input($data['email'] ?? '');
        $direccion = sanitize_input($data['direccion'] ?? '');

        if (!validate_required($identificacion) || !validate_required($nombre) || !validate_required($apellido)) {
            json_error("Cédula, nombre y apellido son obligatorios", 400);
        }

        if ($email && !validate_email($email)) {
            json_error("Correo electrónico inválido", 400);
        }
        if ($telefono && !validate_phone($telefono)) {
            json_error("Teléfono inválido", 400);
        }

        // --- VALIDACIÓN DE DUPLICADOS ---
        $check = $this->db->prepare("SELECT idCliente FROM cliente WHERE identificacion = ?");
        $check->bind_param("s", $identificacion);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            json_error("La cédula {$identificacion} ya se encuentra registrada.", 400);
        }

        $stmt = $this->db->prepare("INSERT INTO cliente (identificacion, nombre, apellido, telefono, email, direccion, fechaRegistro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss",
            $identificacion,
            $nombre,
            $apellido,
            $telefono,
            $email,
            $direccion
        );

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Cliente registrado con éxito"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error interno al registrar"]);
        }
    }

    private function actualizarCliente($id) {
        $data = json_decode(file_get_contents("php://input"), true);

        $nombre = sanitize_input($data['nombre'] ?? '');
        $apellido = sanitize_input($data['apellido'] ?? '');
        $telefono = sanitize_input($data['telefono'] ?? '');
        $email = sanitize_input($data['email'] ?? '');
        $direccion = sanitize_input($data['direccion'] ?? '');

        if (!validate_required($nombre) || !validate_required($apellido)) {
            json_error("Nombre y apellido son obligatorios", 400);
        }
        if ($email && !validate_email($email)) json_error("Correo electrónico inválido", 400);
        if ($telefono && !validate_phone($telefono)) json_error("Teléfono inválido", 400);

        $stmt = $this->db->prepare("UPDATE cliente SET nombre=?, apellido=?, telefono=?, email=?, direccion=? WHERE idCliente=?");
        $stmt->bind_param("ssssss", $nombre, $apellido, $telefono, $email, $direccion, $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Cliente actualizado"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar"]);
        }
    }
public function getVehiculosByCliente($idCliente) {
    $stmt = $this->db->prepare("
        SELECT v.*, m.nombre_marca, md.nombre_modelo 
        FROM vehiculo v
        LEFT JOIN marcas m ON v.idMarca = m.idMarca
        LEFT JOIN modelos md ON v.idModelo = md.idModelo
        WHERE v.idCliente = ?
        ORDER BY v.idVehiculo DESC
    ");
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}

public function getOrdenesByCliente($idCliente) {
    $stmt = $this->db->prepare("
        SELECT ot.*, v.placa
        FROM orden_de_trabajo ot
        JOIN vehiculo v ON ot.idVehiculo = v.idVehiculo
        WHERE v.idCliente = ?
        ORDER BY ot.fechaCreacion DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}
    private function eliminarCliente($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(["message" => "ID no proporcionado"]);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM cliente WHERE idCliente = ?");
        $stmt->bind_param("s", $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Cliente eliminado correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al eliminar"]);
        }
    }
}   