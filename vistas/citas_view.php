<?php include "../includes/navbar.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Citas | AutoGestión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos_globales.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/citas.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/validators.js"></script>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-calendar-alt"></i> Citas Programadas</h1>
        <div style="display:flex; gap: 10px; align-items:center;">
            <button class="btn-secondary" id="btn-exportar-pdf"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
            <button class="btn-primary" id="btnNuevaCita"><i class="fas fa-plus"></i> Nueva Cita</button>
        </div>
    
    <div class="filter-bar">
        <select id="filtroCitas">
            <option value="todas">Todas las citas</option>
            <option value="pendientes">Pendientes</option>
            <option value="hoy">De hoy</option>
        </select>
        <button class="btn-clear" id="btnRefrescar"><i class="fas fa-sync-alt"></i> Refrescar</button>
    </div>
    
    <div class="table-container">
        <table id="tablaCitas">
            <thead><tr><th>ID</th><th>Cliente</th><th>Vehículo</th><th>Fecha y Hora</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tbodyCitas"><tr><td colspan="7">Cargando...</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Modal Cita -->
<div id="modalCita" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalCitaTitle">Nueva Cita</h2>
        <form id="formCita">
            <input type="hidden" id="idCita">
            <div class="form-group">
                <label>Cliente *</label>
                <select id="idCliente" required><option value="">Seleccione cliente</option></select>
            </div>
            <div class="form-group">
                <label>Vehículo (opcional)</label>
                <select id="idVehiculoCita"><option value="">Ninguno</option></select>
            </div>
            <div class="form-group">
                <label>Fecha y Hora *</label>
                <input type="datetime-local" id="fechaHora" required>
            </div>
            <div class="form-group">
                <label>Motivo</label>
                <textarea id="motivo" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select id="estadoCita">
                    <option value="Pendiente">Pendiente</option>
                    <option value="Confirmada">Confirmada</option>
                    <option value="Completada">Completada</option>
                    <option value="Cancelada">Cancelada</option>
                </select>
            </div>
            <div class="form-group">
                <label>Observaciones</label>
                <textarea id="observaciones" rows="2"></textarea>
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" id="btnCancelarCita">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/citas.js"></script>
</body>
</html>