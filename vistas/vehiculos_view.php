<?php include "../includes/navbar.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestión de Vehículos | AutoGestión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos_globales.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/vehiculos.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/validators.js"></script>
</head>
<body>
<main class="container">
    <header class="page-header">
        <div>
            <h1><i class="fas fa-car"></i> Parque Automotor</h1>
            <p style="color: #4f6f8f;">Administra los vehículos registrados y su historial de servicio.</p>
        </div>
        <button class="btn-primary" id="btn-nuevo-vehiculo">
            <i class="fas fa-plus"></i> Nuevo Vehículo
        </button>
    </header>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-car"></i></div>
            <div class="stat-info">
                <h3 id="total-vehiculos">0</h3>
                <p>Total Vehículos</p>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3 id="vehiculos-activos">0</h3>
                <p>Activos</p>
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon"><i class="fas fa-wrench"></i></div>
            <div class="stat-info">
                <h3 id="vehiculos-servicio">0</h3>
                <p>Con servicio registrado</p>
            </div>
        </div>
    </div>

    <!-- Barra de filtros -->
    <section class="toolbar-filters">
        <div class="search-field">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Buscar por placa, VIN, cliente...">
        </div>
        <div class="filter-select-wrapper">
            <select id="filtro-marca">
                <option value="">Todas las marcas</option>
            </select>
            <i class="fas fa-chevron-down select-icon"></i>
        </div>
        <div class="filter-select-wrapper">
            <select id="filtro-estado-vehiculo">
                <option value="todos">Todos los estados</option>
                <option value="activo">Activos</option>
                <option value="inactivo">Inactivos</option>
            </select>
            <i class="fas fa-chevron-down select-icon"></i>
        </div>
        <button class="btn-clear" id="btn-limpiar-filtros">
            <i class="fas fa-undo"></i> Limpiar
        </button>
        <button class="btn-secondary" id="btn-exportar-pdf">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </button>
    </section>

    <!-- Tabla de vehículos -->
    <section class="table-container">
        <table id="tabla-vehiculos">
            <thead>
                <tr>
                    <th><div class="sort-header" data-sort="placa">Placa</div></th>
                    <th><div class="sort-header" data-sort="cliente">Cliente</div></th>
                    <th><div class="sort-header" data-sort="marca">Marca</div></th>
                    <th><div class="sort-header" data-sort="modelo">Modelo</div></th>
                    <th><div class="sort-header" data-sort="anio">Año</div></th>
                    <th><div class="sort-header" data-sort="kilometraje">Kilometraje</div></th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbody-vehiculos">
                <tr><td colspan="8" class="skeleton-row">Cargando...</td></tr>
            </tbody>
        </table>
    </section>
</main>

<!-- Modal para crear/editar vehículo -->
<div id="modal-vehiculo" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modal-title">Registrar Vehículo</h2>
        <form id="form-vehiculo">
            <input type="hidden" id="idVehiculo">
            <div class="form-grid">
                <input type="text" id="placa" placeholder="Placa *" required>
                <select id="idCliente" required>
                    <option value="">Seleccione cliente *</option>
                </select>
                <select id="marca" required>
                    <option value="">Seleccione marca *</option>
                </select>
                <select id="modelo" required>
                    <option value="">Primero seleccione marca</option>
                </select>
                <input type="number" id="anio" placeholder="Año" min="1900" max="2026">
                <input type="text" id="vin" placeholder="VIN (17 caracteres)" maxlength="17">
                <input type="number" id="kilometraje" placeholder="Kilometraje">
                <input type="date" id="fechaUltimoServicio" placeholder="Último servicio">
                <select id="estadoVehiculo">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn-save">Guardar</button>
                <button type="button" class="btn-secondary" id="btn-cancelar-modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Panel lateral de detalle -->
<div id="panel-lateral" class="panel-lateral">
    <div class="panel-header">
        <h3><i class="fas fa-info-circle"></i> Detalles del vehículo</h3>
        <button id="cerrar-panel" class="close-panel">&times;</button>
    </div>
    <div id="panel-contenido" class="panel-contenido">
        <!-- Carga dinámica -->
    </div>
</div>

<script src="../assets/js/vehiculos.js"></script>
</body>
</html>