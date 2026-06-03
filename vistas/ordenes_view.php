<?php include "../includes/navbar.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes de Trabajo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos_globales.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/ordenes.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/validators.js"></script>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-list"></i> Órdenes de Trabajo</h1>
        <div style="display:flex; gap: 10px; align-items:center;">
            <button class="btn-secondary" id="btn-exportar-pdf"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
            <button class="btn-primary" id="btnNuevaOrden"><i class="fas fa-plus"></i> Nueva Orden</button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="toolbar-filters">
        <div class="search-field">
            <i class="fas fa-search"></i>
            <input type="text" id="searchOrden" placeholder="Buscar por cliente, placa o código...">
        </div>
        <div class="filter-select-wrapper">
            <select id="filtroEstado">
                <option value="todos">Todos los estados</option>
                <option value="Recepcionada">Recepcionada</option>
                <option value="EnProceso">En Proceso</option>
                <option value="Terminada">Terminada</option>
                <option value="Facturada">Facturada</option>
                <option value="Cancelada">Cancelada</option>
            </select>
            <i class="fas fa-chevron-down select-icon"></i>
        </div>
        <button class="btn-clear" id="btnLimpiarFiltros"><i class="fas fa-undo"></i> Limpiar</button>
    </div>

    <!-- Tabla -->
    <div class="table-container">
        <table id="tablaOrdenes">
            <thead>
                <tr>
                    <th>Código</th><th>Cliente</th><th>Vehículo</th><th>Fecha</th><th>Estado</th><th>Total</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyOrdenes"><tr><td colspan="7" class="text-center">Cargando órdenes...</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Modal para crear/editar orden -->
<div id="modalOrden" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Nueva Orden de Trabajo</h2>
        <form id="formOrden">
            <input type="hidden" id="idOT">
            <div class="form-group">
                <label>Vehículo *</label>
                <select id="idVehiculo" required>
                    <option value="">Seleccione un vehículo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Problema reportado</label>
                <textarea id="problemaReportado" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Diagnóstico</label>
                <textarea id="diagnostico" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Observaciones del mecánico</label>
                <textarea id="observacionesMecanico" rows="2"></textarea>
            </div>

            <h3>Servicios</h3>
            <div id="serviciosContainer">
                <div class="servicio-row">
                    <select class="servicio-select" style="width: 60%;">
                        <option value="">Seleccione servicio</option>
                    </select>
                    <input type="number" class="servicio-cantidad" placeholder="Cant." value="1" style="width: 15%;">
                    <input type="number" class="servicio-precio" placeholder="Precio" step="0.01" style="width: 20%;">
                    <button type="button" class="btn-remove-servicio"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <button type="button" class="btn-secondary" id="btnAgregarServicio"><i class="fas fa-plus"></i> Agregar servicio</button>

            <div class="form-group" style="margin-top: 15px;">
                <label>Presupuesto total (calculado automáticamente)</label>
                <input type="number" id="presupuesto_total" readonly step="0.01" style="background:#f0f0f0;">
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Guardar Orden</button>
                <button type="button" class="btn-secondary" id="btnCancelarModal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div id="modalEstado" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-estado">&times;</span>
        <h2>Cambiar estado de la orden</h2>
        <select id="nuevoEstado" class="form-control" style="width:100%; padding:10px;">
            <option value="Recepcionada">Recepcionada</option>
            <option value="EnProceso">En Proceso</option>
            <option value="Terminada">Terminada</option>
            <option value="Facturada">Facturada</option>
            <option value="Cancelada">Cancelada</option>
        </select>
        <textarea id="comentarioEstado" placeholder="Comentario (opcional)" rows="2" style="width:100%; margin-top:10px;"></textarea>
        <div class="form-buttons" style="margin-top:20px;">
            <button class="btn-primary" id="btnConfirmarEstado">Cambiar</button>
            <button class="btn-secondary" id="btnCancelarEstado">Cancelar</button>
        </div>
    </div>
</div>

<script src="../assets/js/ordenes.js"></script>
</body>
</html>