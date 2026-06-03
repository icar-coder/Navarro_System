

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once 'controllers/ClientesController.php';
require_once 'controllers/DashboardController.php';
require_once 'controllers/VehiculosController.php';   // <-- NUEVO
require_once 'controllers/OrdenesController.php';
require_once 'controllers/MarcasController.php';
require_once 'controllers/ModelosController.php';
require_once 'controllers/ServiciosController.php';
require_once 'controllers/ReportesController.php';
require_once 'controllers/CitasController.php';
require_once 'controllers/AuthController.php';

$urlData = $_GET['resource'] ?? '';
$parts = explode('/', rtrim($urlData, '/'));
$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
$db = (new Database())->getConnection();

switch ($resource) {
// Nuevos endpoints para cliente expandido
    case 'clientes':
        if (isset($parts[1]) && isset($parts[2])) {
            $id = $parts[1];
            $subrecurso = $parts[2];
            $controller = new ClientesController($db);
            if ($subrecurso === 'vehiculos') {
                $controller->getVehiculosByCliente($id);
            } elseif ($subrecurso === 'ordenes') {
                $controller->getOrdenesByCliente($id);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Subrecurso no válido"]);
            }
        } else {
            $controller = new ClientesController($db);
            $controller->handleRequest($method, $id);
        }
        break;
    case 'dashboard':
        $controller = new DashboardController($db);
        $action = $id;
        if ($action === 'resumen') {
            $controller->getResumen();
        } elseif ($action === 'resumen-completo') {
            $controller->getResumenCompleto();
        } elseif ($action === 'tendencia') {
            $controller->getTendencia();
        } elseif ($action === 'ordenes-por-estado') {
            $controller->getOrdenesPorEstado();
        } elseif ($action === 'ultimas-ordenes') {
            $limite = $_GET['limite'] ?? 5;
            $controller->getUltimasOrdenes($limite);
        } elseif ($action === 'citas-proximas') {
            $limite = $_GET['limite'] ?? 5;
            $controller->getCitasProximas($limite);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Acción de dashboard no válida"]);
        }
        break;
        
    case 'vehiculos':
        $controller = new VehiculosController($db);
        if ($id === 'estadisticas') {
            $controller->getEstadisticas();
        } elseif (isset($parts[1]) && isset($parts[2])) {
            $id = $parts[1];
            $subrecurso = $parts[2];
            if ($subrecurso === 'ordenes') {
                $controller->getOrdenesByVehiculo($id);
            } elseif ($subrecurso === 'citas') {
                $controller->getCitasByVehiculo($id);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Subrecurso no válido"]);
            }
        } else {
            $controller->handleRequest($method, $id);
        }
        break;
        
    case 'ordenes':
        if ($method === 'PUT' && isset($parts[2]) && $parts[2] === 'estado') {
            $controller = new OrdenesController($db);
            $data = json_decode(file_get_contents("php://input"), true);
            $controller->cambiarEstado($id, $data['estado'], $data['comentario'] ?? null);
        } else {
            $controller = new OrdenesController($db);
            $controller->handleRequest($method, $id);
        }
        break;
        
    case 'marcas':
        $controller = new MarcasController($db);
        $controller->handleRequest($method, $id);
        break;
        
    case 'modelos':
        $controller = new ModelosController($db);
        $controller->handleRequest($method, $id);
        break;
        
    case 'citas':
        $controller = new CitasController($db);
        $controller->handleRequest($method, $id);
        break;

    // ========== NUEVOS MÓDULOS ==========
    
    // CRUD completo de servicios (reemplaza el case 'servicios' anterior)
    case 'servicios':
        $controller = new ServiciosController($db);
        $controller->handleRequest($method, $id);
        break;
    
    // CRUD de marcas (admin)
    case 'marcas-admin':
        $controller = new MarcasController($db);
        $controller->handleRequest($method, $id);
        break;
    
    // CRUD de modelos (admin)
    case 'modelos-admin':
        $controller = new ModelosController($db);
        $controller->handleRequest($method, $id);
        break;
    case 'auth':
        $controller = new AuthController($db);
        $controller->handleRequest($id, $method);
        break;
    // Reportes
    case 'reportes':
        $controller = new ReportesController($db);
        $action = $id ?? '';
        switch ($action) {
            case 'ingresos-mensuales':
                $controller->ingresosMensuales();
                break;
            case 'ordenes-por-estado':
                $controller->ordenesPorEstado();
                break;
            case 'vehiculos-mas-servicios':
                $controller->vehiculosMasServicios();
                break;
            // NUEVOS:
            case 'ingresos-totales':
                $controller->ingresosTotales();
                break;
            case 'total-ordenes':
                $controller->totalOrdenes();
                break;
            case 'total-vehiculos':
                $controller->totalVehiculos();
                break;
            case 'total-clientes-activos':
                $controller->totalClientesActivos();
                break;
            case 'top-servicios':
                $controller->topServicios();
                break;
            case 'top-vehiculos':
                $controller->topVehiculos();
                break;
            case 'ordenes-recientes':
                $controller->ordenesRecientes();
                break;
            case 'ordenes-detalladas':
                $controller->ordenesDetalladas();
                break;
            default:
                http_response_code(404);
                echo json_encode(["error" => "Acción no válida"]);
        }
        break;
    
    // Citas
    case 'citas':
        $controller = new CitasController($db);
        $controller->handleRequest($method, $id);
        break;

    // Mantener los endpoints auxiliares existentes (marcas, modelos, etc. para selects)
    case 'marcas':
        $result = $db->query("SELECT idMarca, nombre_marca FROM marcas ORDER BY nombre_marca");
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        break;
    
    case 'modelos':
        $marcaId = $_GET['marca'] ?? 0;
        $stmt = $db->prepare("SELECT idModelo, nombre_modelo FROM modelos WHERE idMarca = ? ORDER BY nombre_modelo");
        $stmt->bind_param("i", $marcaId);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Recurso '$resource' no encontrado"]);
        break;
}
?>