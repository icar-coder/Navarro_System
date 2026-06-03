<?php
class ReportesController {
    private $db;
    public function __construct($db) { $this->db = $db; }
    
    // Ingresos mensuales basados en facturas pagadas (o presupuestos de órdenes terminadas)
    public function ingresosMensuales() {
        // Usamos las facturas pagadas para mayor precisión
        $sql = "SELECT DATE_FORMAT(f.fechaEmision, '%Y-%m') as mes, SUM(f.totalFactura) as total
                FROM factura f
                WHERE f.estadoPago = 'Pagado' AND f.esta_activa = 1
                GROUP BY mes
                ORDER BY mes DESC
                LIMIT 12";
        $res = $this->db->query($sql);
        $datos = $res->fetch_all(MYSQLI_ASSOC);
        // Si no hay facturas, mostrar 0
        if (empty($datos)) {
            $datos = [];
        }
        echo json_encode($datos);
    }
    // Ingresos totales en un rango de fechas (basado en facturas pagadas)
public function ingresosTotales() {
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $sql = "SELECT SUM(totalFactura) as total FROM factura WHERE estadoPago = 'Pagado' AND esta_activa = 1";
    if ($fechaInicio) $sql .= " AND fechaEmision >= '$fechaInicio'";
    if ($fechaFin) $sql .= " AND fechaEmision <= '$fechaFin'";
    $res = $this->db->query($sql);
    echo json_encode($res->fetch_assoc());
}

// Total de órdenes en rango de fechas
public function totalOrdenes() {
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $sql = "SELECT COUNT(*) as total FROM orden_de_trabajo";
    if ($fechaInicio) $sql .= " WHERE fechaCreacion >= '$fechaInicio'";
    if ($fechaFin) $sql .= (strpos($sql, 'WHERE') !== false ? " AND" : " WHERE") . " fechaCreacion <= '$fechaFin'";
    $res = $this->db->query($sql);
    echo json_encode($res->fetch_assoc());
}

// Total de vehículos
public function totalVehiculos() {
    $res = $this->db->query("SELECT COUNT(*) as total FROM vehiculo");
    echo json_encode($res->fetch_assoc());
}

// Total clientes activos (si no hay columna estado, devuelve todos)
public function totalClientesActivos() {
    $check = $this->db->query("SHOW COLUMNS FROM cliente LIKE 'estado'");
    if ($check->num_rows) {
        $res = $this->db->query("SELECT COUNT(*) as total FROM cliente WHERE estado = 'activo'");
    } else {
        $res = $this->db->query("SELECT COUNT(*) as total FROM cliente");
    }
    echo json_encode($res->fetch_assoc());
}

// Top 5 servicios más solicitados (conteo en detalle_ot_servicio)
public function topServicios() {
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $sql = "SELECT s.nombre, COUNT(d.idServicio) as total FROM detalle_ot_servicio d
            JOIN servicio s ON d.idServicio = s.idServicio
            JOIN orden_de_trabajo ot ON d.idOT = ot.idOT
            WHERE 1=1";
    if ($fechaInicio) $sql .= " AND ot.fechaCreacion >= '$fechaInicio'";
    if ($fechaFin) $sql .= " AND ot.fechaCreacion <= '$fechaFin'";
    $sql .= " GROUP BY d.idServicio ORDER BY total DESC LIMIT 5";
    $res = $this->db->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

// Top 5 vehículos con más servicios
public function topVehiculos() {
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $sql = "SELECT v.placa, COUNT(d.idServicio) as total_servicios FROM vehiculo v
            JOIN orden_de_trabajo ot ON v.idVehiculo = ot.idVehiculo
            JOIN detalle_ot_servicio d ON ot.idOT = d.idOT
            WHERE 1=1";
    if ($fechaInicio) $sql .= " AND ot.fechaCreacion >= '$fechaInicio'";
    if ($fechaFin) $sql .= " AND ot.fechaCreacion <= '$fechaFin'";
    $sql .= " GROUP BY v.idVehiculo ORDER BY total_servicios DESC LIMIT 5";
    $res = $this->db->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

// Órdenes recientes (últimas 10)
public function ordenesRecientes() {
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $sql = "SELECT ot.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, v.placa
            FROM orden_de_trabajo ot
            JOIN vehiculo v ON ot.idVehiculo = v.idVehiculo
            JOIN cliente c ON v.idCliente = c.idCliente
            WHERE 1=1";
    if ($fechaInicio) $sql .= " AND ot.fechaCreacion >= '$fechaInicio'";
    if ($fechaFin) $sql .= " AND ot.fechaCreacion <= '$fechaFin'";
    $sql .= " ORDER BY ot.fechaCreacion DESC LIMIT 10";
    $res = $this->db->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

public function ordenesDetalladas() {
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $sql = "SELECT ot.codigo_ot, ot.fechaCreacion, ot.estado, ot.presupuesto_total, v.placa, CONCAT(c.nombre, ' ', c.apellido) as cliente
            FROM orden_de_trabajo ot
            JOIN vehiculo v ON ot.idVehiculo = v.idVehiculo
            JOIN cliente c ON v.idCliente = c.idCliente
            WHERE 1=1";
    if ($fechaInicio) $sql .= " AND ot.fechaCreacion >= '$fechaInicio'";
    if ($fechaFin) $sql .= " AND ot.fechaCreacion <= '$fechaFin'";
    $sql .= " ORDER BY ot.fechaCreacion DESC";
    $res = $this->db->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

    // Cantidad de órdenes por estado
    public function ordenesPorEstado() {
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;
        $sql = "SELECT estado, COUNT(*) as cantidad FROM orden_de_trabajo WHERE 1=1";
        if ($fechaInicio) $sql .= " AND fechaCreacion >= '$fechaInicio'";
        if ($fechaFin) $sql .= " AND fechaCreacion <= '$fechaFin'";
        $sql .= " GROUP BY estado";
        $res = $this->db->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    }
    
    // Top 5 vehículos con más servicios realizados (suma de cantidades en detalle)
    public function vehiculosMasServicios() {
        $sql = "SELECT v.placa, v.idVehiculo, COUNT(d.idServicio) as total_servicios
                FROM vehiculo v
                JOIN orden_de_trabajo ot ON v.idVehiculo = ot.idVehiculo
                JOIN detalle_ot_servicio d ON ot.idOT = d.idOT
                GROUP BY v.idVehiculo
                ORDER BY total_servicios DESC
                LIMIT 5";
        $res = $this->db->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    }
}
?>