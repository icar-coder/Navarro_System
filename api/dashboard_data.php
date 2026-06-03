<?php
// 1. Cabeceras profesionales: indicamos que la respuesta es JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // Permite peticiones desde otros dominios

// 2. Carga de dependencias (Usando tu nueva estructura)
require_once '../config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("No se pudo establecer la conexión con la base de datos.");
    }

    // 3. Consultas optimizadas (Basadas en tu SQL)
    
    // Total Clientes
    $resClientes = $db->query("SELECT COUNT(*) as total FROM cliente");
    $totalClientes = $resClientes->fetch_assoc()['total'];

    // Total Vehículos
    $resVehiculos = $db->query("SELECT COUNT(*) as total FROM vehiculo");
    $totalVehiculos = $resVehiculos->fetch_assoc()['total'];

    // Clientes nuevos este mes
    $resNuevos = $db->query("SELECT COUNT(*) as total FROM cliente 
                             WHERE MONTH(fechaRegistro) = MONTH(CURRENT_DATE()) 
                             AND YEAR(fechaRegistro) = YEAR(CURRENT_DATE())");
    $nuevosMes = $resNuevos->fetch_assoc()['total'];

    // 4. Respuesta Exitosa
    http_response_code(200); // OK
    echo json_encode([
        "status" => "success",
        "data" => [
            "total_clientes" => (int)$totalClientes,
            "total_vehiculos" => (int)$totalVehiculos,
            "clientes_mes" => (int)$nuevosMes
        ]
    ]);

} catch (Exception $e) {
    // 5. Gestión de errores profesional
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "status" => "error",
        "message" => "Error interno del servidor",
        "debug" => $e->getMessage() // Quitar esto en producción
    ]);
}