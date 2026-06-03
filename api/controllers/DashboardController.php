<?php
class DashboardController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }



        public function getResumen() {
            // Total clientes
            $res = $this->db->query("SELECT COUNT(*) as total FROM cliente");
            $total = $res ? $res->fetch_assoc()['total'] : 0;

            // Validación para evitar el error de la columna 'estado'
            $checkColumn = $this->db->query("SHOW COLUMNS FROM cliente LIKE 'estado'");
            $activos = 0;
            if ($checkColumn->num_rows > 0) {
                $resActivos = $this->db->query("SELECT COUNT(*) as activos FROM cliente WHERE estado = 'activo'");
                $activos = $resActivos ? $resActivos->fetch_assoc()['activos'] : 0;
            } else {
                // Si no existe la columna 'estado', asumimos que todos son el total o 0
                $activos = $total; 
            }

            $recientes = [];
            $resRecientes = $this->db->query("SELECT nombre, apellido, fechaRegistro FROM cliente ORDER BY fechaRegistro DESC LIMIT 5");
            if ($resRecientes) {
                while($row = $resRecientes->fetch_assoc()) {
                    $recientes[] = $row;
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                "totalClientes" => (int)$total,
                "clientesActivos" => (int)$activos,
                "recientes" => $recientes
            ]);
        }

    public function getTendencia() {
        // Agrupamos por mes (Año-Mes)
        $sql = "SELECT DATE_FORMAT(fechaRegistro, '%Y-%m') as mes, COUNT(*) as cantidad 
                FROM cliente 
                GROUP BY mes 
                ORDER BY mes ASC 
                LIMIT 6";
        
        $res = $this->db->query($sql);
        $datos = [];
        if ($res) {
            while($row = $res->fetch_assoc()) {
                $datos[] = $row;
            }
        }
        echo json_encode($datos);
    }
    // Añadir al final del DashboardController existente

public function getResumenCompleto() {
    // Clientes
    $res = $this->db->query("SELECT COUNT(*) as total FROM cliente");
    $totalClientes = $res->fetch_assoc()['total'];
    
    // Vehículos
    $res = $this->db->query("SELECT COUNT(*) as total FROM vehiculo");
    $totalVehiculos = $res->fetch_assoc()['total'];
    
    // Órdenes de trabajo
    $res = $this->db->query("SELECT COUNT(*) as total FROM orden_de_trabajo");
    $totalOrdenes = $res->fetch_assoc()['total'];
    $res = $this->db->query("SELECT COUNT(*) as total FROM orden_de_trabajo WHERE estado = 'Terminada' OR estado = 'Facturada'");
    $ordenesCompletadas = $res->fetch_assoc()['total'];
    
    // Ingresos del mes actual (facturas pagadas)
    $mesActual = date('Y-m');
    $stmt = $this->db->prepare("SELECT SUM(totalFactura) as total FROM factura WHERE estadoPago = 'Pagado' AND DATE_FORMAT(fechaEmision, '%Y-%m') = ?");
    $stmt->bind_param("s", $mesActual);
    $stmt->execute();
    $ingresosMes = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Citas próximas (próximos 7 días)
    $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM citas WHERE fechaHora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) AND estado != 'Cancelada'");
    $stmt->execute();
    $citasProximas = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    echo json_encode([
        "totalClientes" => (int)$totalClientes,
        "totalVehiculos" => (int)$totalVehiculos,
        "totalOrdenes" => (int)$totalOrdenes,
        "ordenesCompletadas" => (int)$ordenesCompletadas,
        "ingresosMes" => (float)$ingresosMes,
        "citasProximas" => (int)$citasProximas
    ]);
}

public function getUltimasOrdenes($limite = 5) {
    $sql = "SELECT ot.idOT, ot.codigo_ot, ot.fechaCreacion, ot.estado, 
                   CONCAT(c.nombre, ' ', c.apellido) as cliente, v.placa
            FROM orden_de_trabajo ot
            JOIN vehiculo v ON ot.idVehiculo = v.idVehiculo
            JOIN cliente c ON v.idCliente = c.idCliente
            ORDER BY ot.fechaCreacion DESC
            LIMIT ?";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    $ordenes = [];
    while ($row = $result->fetch_assoc()) {
        $ordenes[] = $row;
    }
    echo json_encode($ordenes);
}

public function getCitasProximas($limite = 5) {
    $sql = "SELECT c.idCita, c.fechaHora, c.motivo, c.estado,
                   CONCAT(cl.nombre, ' ', cl.apellido) as cliente,
                   v.placa
            FROM citas c
            JOIN cliente cl ON c.idCliente = cl.idCliente
            LEFT JOIN vehiculo v ON c.idVehiculo = v.idVehiculo
            WHERE c.fechaHora >= NOW() AND c.estado != 'Cancelada'
            ORDER BY c.fechaHora ASC
            LIMIT ?";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = $row;
    }
    echo json_encode($citas);
}

public function getOrdenesPorEstado() {
    $sql = "SELECT estado, COUNT(*) as cantidad FROM orden_de_trabajo GROUP BY estado";
    $res = $this->db->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
}