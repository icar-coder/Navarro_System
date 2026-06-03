<?php
// api/controllers/VehiculosController.php

class VehiculosController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->obtenerVehiculo($id);
                } else {
                    $this->listarVehiculos();
                }
                break;
            case 'POST':
                $this->guardarVehiculo();
                break;
            case 'PUT':
                $this->actualizarVehiculo($id);
                break;
            case 'DELETE':
                $this->eliminarVehiculo($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido"]);
                break;
        }
    }

    /**
     * Listar todos los vehículos con datos relacionados (marca, modelo, cliente)
     */
    private function listarVehiculos() {
        $search = $_GET['search'] ?? null;
        $marca = $_GET['marca'] ?? null;
        $estado = $_GET['estado'] ?? null;

        $sql = "SELECT v.*, 
                       m.nombre_marca,
                       md.nombre_modelo,
                       c.nombre as cliente_nombre,
                       c.apellido as cliente_apellido
                FROM vehiculo v
                LEFT JOIN marcas m ON v.idMarca = m.idMarca
                LEFT JOIN modelos md ON v.idModelo = md.idModelo
                LEFT JOIN cliente c ON v.idCliente = c.idCliente";

        $conditions = [];
        $params = [];
        $types = "";

        if ($search) {
            $conditions[] = "(v.placa LIKE ? OR v.vin LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR m.nombre_marca LIKE ? OR md.nombre_modelo LIKE ?)";
            $likeSearch = "%$search%";
            $params = array_merge($params, [$likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch]);
            $types .= "ssssss";
        }
        if ($marca) {
            $conditions[] = "v.idMarca = ?";
            $params[] = (int)$marca;
            $types .= "i";
        }
        if ($estado && $estado !== 'todos') {
            $conditions[] = "v.estado = ?";
            $params[] = $estado;
            $types .= "s";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
            $stmt = $this->db->prepare($sql . " ORDER BY v.fechaUltimoServicio DESC");
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql . " ORDER BY v.fechaUltimoServicio DESC");
        }

        $vehiculos = [];
        while ($row = $result->fetch_assoc()) {
            $row['cliente_completo'] = $row['cliente_nombre'] . ' ' . $row['cliente_apellido'];
            $vehiculos[] = $row;
        }

        echo json_encode($vehiculos);
    }
// Obtener órdenes de trabajo de un vehículo específico
public function getOrdenesByVehiculo($idVehiculo) {
    $stmt = $this->db->prepare("
        SELECT ot.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido
        FROM orden_de_trabajo ot
        JOIN vehiculo v ON ot.idVehiculo = v.idVehiculo
        JOIN cliente c ON v.idCliente = c.idCliente
        WHERE v.idVehiculo = ?
        ORDER BY ot.fechaCreacion DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $idVehiculo);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}

// Obtener citas de un vehículo (si existe tabla citas)
public function getCitasByVehiculo($idVehiculo) {
    $stmt = $this->db->prepare("
        SELECT c.*, CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre
        FROM citas c
        JOIN cliente cl ON c.idCliente = cl.idCliente
        WHERE c.idVehiculo = ?
        ORDER BY c.fechaHora DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $idVehiculo);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}

// Obtener estadísticas para el dashboard de vehículos
public function getEstadisticas() {
    $total = $this->db->query("SELECT COUNT(*) as total FROM vehiculo")->fetch_assoc()['total'];
    $activos = $this->db->query("SELECT COUNT(*) as activos FROM vehiculo WHERE estado = 'activo'")->fetch_assoc()['activos'];
    $conServicio = $this->db->query("SELECT COUNT(DISTINCT v.idVehiculo) as con_servicio FROM vehiculo v JOIN orden_de_trabajo ot ON v.idVehiculo = ot.idVehiculo")->fetch_assoc()['con_servicio'];
    echo json_encode(['total' => (int)$total, 'activos' => (int)$activos, 'conServicio' => (int)$conServicio]);
}
    /**
     * Obtener un vehículo por su ID
     */
    private function obtenerVehiculo($id) {
        $stmt = $this->db->prepare("SELECT * FROM vehiculo WHERE idVehiculo = ?");
        $stmt->bind_param("i", $id);  // idVehiculo es entero
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Vehículo no encontrado"]);
        }
    }

    /**
     * Registrar un nuevo vehículo
     */
    private function guardarVehiculo() {
        $data = json_decode(file_get_contents("php://input"), true);

        // Sanitizar y validar entradas
        $placa = sanitize_input($data['placa'] ?? '');
        $idCliente = isset($data['idCliente']) ? (int)$data['idCliente'] : 0;
        $marca = isset($data['marca']) ? (int)$data['marca'] : 0;
        $modelo = isset($data['modelo']) ? (int)$data['modelo'] : 0;
        $anio = sanitize_input($data['anio'] ?? null);
        $vin = sanitize_input($data['vin'] ?? null);
        $kilometraje = sanitize_input($data['kilometraje'] ?? null);
        $fechaUltimoServicio = sanitize_input($data['fechaUltimoServicio'] ?? null);

        if (!validate_required($placa) || !$idCliente || !$marca || !$modelo) {
            json_error("Placa, cliente, marca y modelo son obligatorios", 400);
        }
        if (!validate_plate($placa)) {
            json_error("Placa inválida: solo letras, números y guiones (3-12 caracteres)", 400);
        }

        // Validar placa duplicada
        $check = $this->db->prepare("SELECT idVehiculo FROM vehiculo WHERE placa = ?");
        $check->bind_param("s", $placa);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            json_error("La placa {$placa} ya está registrada", 400);
        }

        // Validar que el cliente exista
        $checkCliente = $this->db->prepare("SELECT idCliente FROM cliente WHERE idCliente = ?");
        $checkCliente->bind_param("i", $idCliente);
        $checkCliente->execute();
        if ($checkCliente->get_result()->num_rows === 0) {
            json_error("El cliente especificado no existe", 400);
        }

        // Validar que la marca exista
        $checkMarca = $this->db->prepare("SELECT idMarca FROM marcas WHERE idMarca = ?");
        $checkMarca->bind_param("i", $marca);
        $checkMarca->execute();
        if ($checkMarca->get_result()->num_rows === 0) {
            json_error("La marca especificada no existe", 400);
        }

        // Validar que el modelo exista y pertenezca a la marca
        $checkModelo = $this->db->prepare("SELECT idModelo FROM modelos WHERE idModelo = ? AND idMarca = ?");
        $checkModelo->bind_param("ii", $modelo, $marca);
        $checkModelo->execute();
        if ($checkModelo->get_result()->num_rows === 0) {
            json_error("El modelo especificado no es válido para la marca seleccionada", 400);
        }

        // Insertar vehículo (idVehiculo es autoincremental)
        $stmt = $this->db->prepare("INSERT INTO vehiculo (placa, idMarca, idModelo, anio, vin, kilometraje, fechaUltimoServicio, idCliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siissssi",
            $placa,
            $marca,
            $modelo,
            $anio,
            $vin,
            $kilometraje,
            $fechaUltimoServicio,
            $idCliente
        );

        if ($stmt->execute()) {
            $nuevoId = $stmt->insert_id;
            echo json_encode([
                "status" => "success",
                "message" => "Vehículo registrado con éxito",
                "id" => $nuevoId
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error interno al registrar: " . $stmt->error]);
        }
    }

    /**
     * Actualizar un vehículo existente
     */
    private function actualizarVehiculo($id) {
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar que el vehículo existe
        $checkExist = $this->db->prepare("SELECT idVehiculo FROM vehiculo WHERE idVehiculo = ?");
        $checkExist->bind_param("i", $id);
        $checkExist->execute();
        if ($checkExist->get_result()->num_rows === 0) {
            json_error("Vehículo no encontrado", 404);
        }

        // Sanitizar y validar
        $placa = sanitize_input($data['placa'] ?? '');
        $idCliente = isset($data['idCliente']) ? (int)$data['idCliente'] : 0;
        $marca = isset($data['marca']) ? (int)$data['marca'] : 0;
        $modelo = isset($data['modelo']) ? (int)$data['modelo'] : 0;
        $anio = sanitize_input($data['anio'] ?? null);
        $vin = sanitize_input($data['vin'] ?? null);
        $kilometraje = sanitize_input($data['kilometraje'] ?? null);
        $fechaUltimoServicio = sanitize_input($data['fechaUltimoServicio'] ?? null);
        $estado = sanitize_input($data['estado'] ?? 'activo');

        if (!validate_required($placa) || !$idCliente || !$marca || !$modelo) {
            json_error("Placa, cliente, marca y modelo son obligatorios", 400);
        }
        if (!validate_plate($placa)) json_error("Placa inválida", 400);

        // Validar placa duplicada (excluyendo el propio vehículo)
        $checkPlaca = $this->db->prepare("SELECT idVehiculo FROM vehiculo WHERE placa = ? AND idVehiculo != ?");
        $checkPlaca->bind_param("si", $placa, $id);
        $checkPlaca->execute();
        if ($checkPlaca->get_result()->num_rows > 0) {
            json_error("La placa {$placa} ya está registrada en otro vehículo", 400);
        }

        // Validar cliente
        $checkCliente = $this->db->prepare("SELECT idCliente FROM cliente WHERE idCliente = ?");
        $checkCliente->bind_param("i", $idCliente);
        $checkCliente->execute();
        if ($checkCliente->get_result()->num_rows === 0) {
            json_error("El cliente especificado no existe", 400);
        }

        // Validar marca
        $checkMarca = $this->db->prepare("SELECT idMarca FROM marcas WHERE idMarca = ?");
        $checkMarca->bind_param("i", $marca);
        $checkMarca->execute();
        if ($checkMarca->get_result()->num_rows === 0) {
            json_error("La marca especificada no existe", 400);
        }

        // Validar modelo
        $checkModelo = $this->db->prepare("SELECT idModelo FROM modelos WHERE idModelo = ? AND idMarca = ?");
        $checkModelo->bind_param("ii", $modelo, $marca);
        $checkModelo->execute();
        if ($checkModelo->get_result()->num_rows === 0) {
            json_error("El modelo especificado no es válido para la marca seleccionada", 400);
        }

        // Actualizar vehículo
        $stmt = $this->db->prepare("UPDATE vehiculo SET placa=?, idCliente=?, idMarca=?, idModelo=?, anio=?, vin=?, kilometraje=?, fechaUltimoServicio=?, estado=? WHERE idVehiculo=?");
        $stmt->bind_param("siissssisi",
            $placa,
            $idCliente,
            $marca,
            $modelo,
            $anio,
            $vin,
            $kilometraje,
            $fechaUltimoServicio,
            $estado,
            $id
        );

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Vehículo actualizado"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar"]);
        }
    }

    /**
     * Eliminar un vehículo (si no tiene órdenes de trabajo asociadas)
     */
    private function eliminarVehiculo($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(["message" => "ID no proporcionado"]);
            return;
        }

        // Verificar si tiene órdenes de trabajo asociadas
        $checkOt = $this->db->prepare("SELECT idOT FROM orden_de_trabajo WHERE idVehiculo = ? LIMIT 1");
        $checkOt->bind_param("i", $id);
        $checkOt->execute();
        if ($checkOt->get_result()->num_rows > 0) {
            http_response_code(409); // Conflict
            echo json_encode(["message" => "No se puede eliminar el vehículo porque tiene órdenes de trabajo asociadas"]);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM vehiculo WHERE idVehiculo = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Vehículo eliminado correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al eliminar: " . $stmt->error]);
        }
    }
}
?>