<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar que el usuario esté autenticado
$usuarioId = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
if (!$usuarioId) {
    // Redirigir a login si no está autenticado
    header('Location: /navarro_update/vistas/login.php');
    exit();
}

// Obtener rol desde varias posibles claves de sesión y normalizar
$rolRaw = $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? $_SESSION['role'] ?? '';
$rol = strtolower(trim($rolRaw));

// Roles válidos que tienen acceso a la configuración
$allowedRoles = ['administrador', 'admin', 'superadmin'];
if (!in_array($rol, $allowedRoles, true)) {
    header('Location: /navarro_update/vistas/dashboard_view.php');
    exit();
}

include "../includes/navbar.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Configuración | AutoGestión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos_globales.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/configuracion.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/validators.js"></script>
</head>
<body>
<main class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-cogs"></i> Configuración del Sistema</h1>
            <p style="color: #4f6f8f;">Administra servicios, marcas y modelos del catálogo.</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="servicios"><i class="fas fa-wrench"></i> Servicios</button>
        <button class="tab-btn" data-tab="marcas"><i class="fas fa-trademark"></i> Marcas</button>
        <button class="tab-btn" data-tab="modelos"><i class="fas fa-car"></i> Modelos</button>
        <button class="tab-btn" data-tab="correo"><i class="fas fa-envelope"></i> Correo</button>
    </div>

    <!-- Tab Servicios -->
    <div id="tab-servicios" class="tab-content active">
        <!-- Estadísticas Servicios -->
        <div class="stats-row" id="stats-servicios">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-list"></i></div><div class="stat-info"><h3 id="total-servicios">0</h3><p>Total servicios</p></div></div>
            <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3 id="servicios-activos">0</h3><p>Activos</p></div></div>
            <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-ban"></i></div><div class="stat-info"><h3 id="servicios-inactivos">0</h3><p>Inactivos</p></div></div>
        </div>
        <div class="toolbar">
            <div class="search-field">
                <i class="fas fa-search"></i>
                <input type="text" id="search-servicios" placeholder="Buscar por código o nombre...">
            </div>
            <button class="btn-primary" id="btnNuevoServicio"><i class="fas fa-plus"></i> Nuevo Servicio</button>
            <button class="btn-secondary" id="btnExportarServiciosPDF"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
        </div>
        <div class="table-container">
            <table id="tablaServicios">
                <thead><tr><th>ID</th><th>Código</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyServicios"><tr><td colspan="7" class="skeleton-row">Cargando...</td></tr></tbody>
            </table>
        </div>
    </div>

    <!-- Tab Correo -->
    <div id="tab-correo" class="tab-content">
        <h2>Configuración de correo (PHPMailer)</h2>
        <p>Configura el SMTP para enviar correos de recuperación de contraseña y notificaciones.</p>

        <div class="alert-info" style="background:#e8f0fe; border-left:4px solid #1a73e8; padding:12px; margin-bottom:20px; border-radius:8px;">
            <i class="fas fa-info-circle"></i> <strong>Para Gmail:</strong> Debes usar una <strong>Contraseña de aplicación</strong> (no tu contraseña normal). 
            <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener">¿Cómo generar una? <i class="fas fa-external-link-alt"></i></a>
        </div>

        <form id="formCorreo" class="form-grid">
            <div class="form-group">
                <label>Habilitar envío</label>
                <select id="correoEnabled">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="form-group">
                <label>Servidor SMTP</label>
                <input type="text" id="smtpHost" placeholder="smtp.gmail.com">
            </div>
            <div class="form-group">
                <label>Puerto</label>
                <input type="number" id="smtpPort" value="587">
            </div>
            <div class="form-group">
                <label>Encriptación</label>
                <select id="smtpSecure">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                </select>
            </div>
            <div class="form-group">
                <label>Cuenta de correo (usuario)</label>
                <input type="email" id="smtpUser" placeholder="tucorreo@gmail.com">
                <small>Tu dirección de Gmail completa</small>
            </div>
            <div class="form-group">
                <label>Contraseña de aplicación (App Password)</label>
                <div style="display: flex; gap: 8px;">
                    <input type="password" id="smtpPass" placeholder="xxxx xxxx xxxx xxxx" style="flex:1">
                    <button type="button" id="togglePassVisibility" class="btn-secondary" style="padding: 0 12px;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small>Código de 16 caracteres generado desde <strong>Cuenta de Google → Seguridad → Contraseñas de aplicación</strong></small>
            </div>
            <div class="form-group">
                <label>Nombre del remitente</label>
                <input type="text" id="smtpFrom" placeholder="AutoGestión">
            </div>
            <div class="form-actions">
                <button type="button" id="btnGuardarCorreo" class="btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <button type="button" id="btnProbarCorreo" class="btn-secondary"><i class="fas fa-paper-plane"></i> Enviar prueba</button>
            </div>
        </form>
    </div>

    <!-- Tab Marcas -->
    <div id="tab-marcas" class="tab-content">
        <div class="stats-row" id="stats-marcas">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-trademark"></i></div><div class="stat-info"><h3 id="total-marcas">0</h3><p>Total marcas</p></div></div>
        </div>
        <div class="toolbar">
            <div class="search-field">
                <i class="fas fa-search"></i>
                <input type="text" id="search-marcas" placeholder="Buscar por nombre...">
            </div>
            <button class="btn-primary" id="btnNuevaMarca"><i class="fas fa-plus"></i> Nueva Marca</button>
            <button class="btn-secondary" id="btnExportarMarcasPDF"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
        </div>
        <div class="table-container">
            <table id="tablaMarcas">
                <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyMarcas"><tr><td colspan="3">Cargando...</td></tr></tbody>
            </table>
        </div>
    </div>

    <!-- Tab Modelos -->
    <div id="tab-modelos" class="tab-content">
        <div class="stats-row" id="stats-modelos">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-car"></i></div><div class="stat-info"><h3 id="total-modelos">0</h3><p>Total modelos</p></div></div>
        </div>
        <div class="toolbar">
            <div class="search-field">
                <i class="fas fa-search"></i>
                <input type="text" id="search-modelos" placeholder="Buscar por marca o modelo...">
            </div>
            <button class="btn-primary" id="btnNuevoModelo"><i class="fas fa-plus"></i> Nuevo Modelo</button>
            <button class="btn-secondary" id="btnExportarModelosPDF"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
        </div>
        <div class="table-container">
            <table id="tablaModelos">
                <thead><tr><th>ID</th><th>Marca</th><th>Modelo</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyModelos"><tr><td colspan="4">Cargando...</td></tr></tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal genérico para formularios -->
<div id="modalConfig" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalConfigTitle">Título</h2>
        <form id="formConfig">
            <input type="hidden" id="itemId">
            <div id="dynamicFields"></div>
            <div class="form-buttons">
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" id="btnCancelarModal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Panel lateral de detalle -->
<div id="panelLateral" class="panel-lateral">
    <div class="panel-header">
        <h3><i class="fas fa-info-circle"></i> Detalles</h3>
        <button id="cerrarPanel" class="close-panel">&times;</button>
    </div>
    <div id="panelContenido" class="panel-contenido"></div>
</div>

<script src="../assets/js/configuracion.js"></script>
</body>
</html>