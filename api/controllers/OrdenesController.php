<?php
class OrdenesController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->obtenerOrden($id);
                } else {
                    $this->listarOrdenes();
                }
                break;
            case 'POST':
                $this->guardarOrden();
                break;
            case 'PUT':
                $this->actualizarOrden($id);
                break;
            case 'DELETE':
                $this->eliminarOrden($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido"]);
        }
    }

    // Listar órdenes con datos relacionados (cliente, vehículo, total)
    private function listarOrdenes() {
        $search = $_GET['search'] ?? '';
        $estado = $_GET['estado'] ?? '';

        $sql = "SELECT ot.*, 
                       v.placa, 
                       c.nombre as cliente_nombre, 
                       c.apellido as cliente_apellido,
                       COALESCE(SUM(d.precioAplicado * d.cantidad), 0) as total_calculado
                FROM orden_de_trabajo ot
                JOIN vehiculo v ON ot.idVehiculo = v.idVehiculo
                JOIN cliente c ON v.idCliente = c.idCliente
                LEFT JOIN detalle_ot_servicio d ON ot.idOT = d.idOT
                WHERE 1=1";

        $params = [];
        $types = "";

        if (!empty($search)) {
            $sql .= " AND (v.placa LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR ot.codigo_ot LIKE ?)";
            $like = "%$search%";
            $params = [$like, $like, $like, $like];
            $types = "ssss";
        }
        if (!empty($estado) && $estado != 'todos') {
            $sql .= " AND ot.estado = ?";
            $params[] = $estado;
            $types .= "s";
        }

        $sql .= " GROUP BY ot.idOT ORDER BY ot.fechaCreacion DESC";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $ordenes = [];
        while ($row = $result->fetch_assoc()) {
            $row['cliente_completo'] = $row['cliente_nombre'] . ' ' . $row['cliente_apellido'];
            $row['total_presupuesto'] = $row['presupuesto_total'] ?? $row['total_calculado'];
            $ordenes[] = $row;
        }
        echo json_encode($ordenes);
    }

    // Obtener una orden con sus detalles (servicios)
    private function obtenerOrden($id) {
        $stmt = $this->db->prepare("SELECT * FROM orden_de_trabajo WHERE idOT = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        if (!$orden) {
            http_response_code(404);
            echo json_encode(["message" => "Orden no encontrada"]);
            return;
        }

        // Obtener servicios asociados
        $stmtDet = $this->db->prepare("SELECT d.*, s.nombre, s.codigo, s.precioUnitario 
                                        FROM detalle_ot_servicio d
                                        JOIN servicio s ON d.idServicio = s.idServicio
                                        WHERE d.idOT = ?");
        $stmtDet->bind_param("i", $id);
        $stmtDet->execute();
        $detalles = $stmtDet->get_result()->fetch_all(MYSQLI_ASSOC);
        $orden['detalles'] = $detalles;

        echo json_encode($orden);
    }

    // Crear nueva orden
private function guardarOrden() {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['idVehiculo'])) {
        http_response_code(400);
        echo json_encode(["message" => "El vehículo es obligatorio"]);
        return;
    }

    $codigo = $this->generarCodigoOT();
    $this->db->begin_transaction();
    try {
        // Solo 7 campos con marcadores (fechaCreacion y estado se asignan directamente)
        $stmt = $this->db->prepare("INSERT INTO orden_de_trabajo 
            (codigo_ot, idVehiculo, idUsuario, problemaReportado, diagnostico, observacionesMecanico, presupuesto_total) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $idUsuario = $data['idUsuario'] ?? null;
        $problema = $data['problemaReportado'] ?? '';
        $diagnostico = $data['diagnostico'] ?? '';
        $observaciones = $data['observacionesMecanico'] ?? '';
        $presupuestoTotal = $data['presupuesto_total'] ?? 0;
        
        // s=string, i=integer, d=double (o s para decimal)
        $stmt->bind_param("siisssd", 
            $codigo, 
            $data['idVehiculo'], 
            $idUsuario, 
            $problema, 
            $diagnostico, 
            $observaciones, 
            $presupuestoTotal
        );
        $stmt->execute();
        $idOT = $stmt->insert_id;

        // Insertar detalles (servicios)
        if (!empty($data['detalles']) && is_array($data['detalles'])) {
            $stmtDet = $this->db->prepare("INSERT INTO detalle_ot_servicio (idOT, idServicio, cantidad, precioAplicado) VALUES (?, ?, ?, ?)");
            foreach ($data['detalles'] as $det) {
                $stmtDet->bind_param("iiid", $idOT, $det['idServicio'], $det['cantidad'], $det['precioAplicado']);
                $stmtDet->execute();
            }
        }

        $this->registrarHistorial($idOT, null, 'Recepcionada', 'Orden creada');
        $this->db->commit();
        echo json_encode(["status" => "success", "message" => "Orden creada con éxito", "idOT" => $idOT]);
    } catch (Exception $e) {
        $this->db->rollback();
        http_response_code(500);
        echo json_encode(["message" => "Error al crear orden: " . $e->getMessage()]);
    }
}   
    // Actualizar orden (editar datos generales y servicios)
    private function actualizarOrden($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->db->begin_transaction();
        try {
            // Actualizar campos principales
            $stmt = $this->db->prepare("UPDATE orden_de_trabajo SET idVehiculo=?, problemaReportado=?, diagnostico=?, observacionesMecanico=?, presupuesto_total=? WHERE idOT=?");
            $stmt->bind_param("isssdi", $data['idVehiculo'], $data['problemaReportado'], $data['diagnostico'], $data['observacionesMecanico'], $data['presupuesto_total'], $id);
            $stmt->execute();

            // Eliminar detalles anteriores y volver a insertar
            $del = $this->db->prepare("DELETE FROM detalle_ot_servicio WHERE idOT = ?");
            $del->bind_param("i", $id);
            $del->execute();

            if (!empty($data['detalles'])) {
                $stmtDet = $this->db->prepare("INSERT INTO detalle_ot_servicio (idOT, idServicio, cantidad, precioAplicado) VALUES (?, ?, ?, ?)");
                foreach ($data['detalles'] as $det) {
                    $stmtDet->bind_param("iiid", $id, $det['idServicio'], $det['cantidad'], $det['precioAplicado']);
                    $stmtDet->execute();
                }
            }

            $this->db->commit();
            echo json_encode(["status" => "success", "message" => "Orden actualizada"]);
        } catch (Exception $e) {
            $this->db->rollback();
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar: " . $e->getMessage()]);
        }
    }

    // Eliminar orden (solo si está en estado Recepcionada o Cancelada)
    private function eliminarOrden($id) {
        $check = $this->db->prepare("SELECT estado FROM orden_de_trabajo WHERE idOT = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $estado = $check->get_result()->fetch_assoc()['estado'] ?? '';
        if (!in_array($estado, ['Recepcionada', 'Cancelada'])) {
            http_response_code(409);
            echo json_encode(["message" => "Solo se pueden eliminar órdenes en estado Recepcionada o Cancelada"]);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM orden_de_trabajo WHERE idOT = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Orden eliminada"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al eliminar"]);
        }
    }

    // Cambiar estado de la orden (con historial)
    public function cambiarEstado($id, $nuevoEstado, $comentario = null) {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("SELECT estado FROM orden_de_trabajo WHERE idOT = ? FOR UPDATE");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $estadoActual = $stmt->get_result()->fetch_assoc()['estado'];

            $update = $this->db->prepare("UPDATE orden_de_trabajo SET estado = ? WHERE idOT = ?");
            $update->bind_param("si", $nuevoEstado, $id);
            $update->execute();

            // Si se marca como Terminada o Facturada, actualizar fechaCierre
            if (in_array($nuevoEstado, ['Terminada', 'Facturada'])) {
                $cierre = $this->db->prepare("UPDATE orden_de_trabajo SET fechaCierre = NOW() WHERE idOT = ?");
                $cierre->bind_param("i", $id);
                $cierre->execute();
            }

            $this->registrarHistorial($id, $estadoActual, $nuevoEstado, $comentario);
            $this->db->commit();
            echo json_encode(["status" => "success", "message" => "Estado actualizado"]);
        } catch (Exception $e) {
            $this->db->rollback();
            http_response_code(500);
            echo json_encode(["message" => "Error al cambiar estado: " . $e->getMessage()]);
        }
    }

    private function registrarHistorial($idOT, $estadoAnterior, $estadoNuevo, $comentario) {
        $stmt = $this->db->prepare("INSERT INTO historial_estados_orden (idOT, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $idOT, $estadoAnterior, $estadoNuevo, $comentario);
        $stmt->execute();
    }

    private function generarCodigoOT() {
        $result = $this->db->query("SELECT MAX(idOT) as max_id FROM orden_de_trabajo");
        $row = $result->fetch_assoc();
        $nextId = ($row['max_id'] ?? 0) + 1;
        return "OT" . str_pad($nextId, 3, "0", STR_PAD_LEFT);
    }
}