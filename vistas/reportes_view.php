<?php include "../includes/navbar.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes y Estadísticas | AutoGestión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos_globales.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/reportes.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="../assets/js/config.js"></script>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Reportes y Estadísticas</h1>
        <button class="btn-primary" id="btn-exportar-pdf">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </button>
    </div>

    <!-- Filtros -->
    <div class="filter-bar">
        <div class="filter-group">
            <label>Fecha inicio</label>
            <input type="date" id="fecha-inicio">
        </div>
        <div class="filter-group">
            <label>Fecha fin</label>
            <input type="date" id="fecha-fin">
        </div>
        <div class="filter-group checkbox-group">
            <label><input type="checkbox" id="chk-incluir-graficas" checked> Incluir gráficos</label>
        </div>
        <div class="filter-group checkbox-group">
            <label><input type="checkbox" id="chk-incluir-ordenes" checked> Incluir órdenes recientes</label>
        </div>
        <button class="btn-secondary" id="btn-aplicar-filtros">Aplicar</button>
        <button class="btn-clear" id="btn-limpiar-filtros">Limpiar</button>
    </div>

    <!-- Tarjetas de KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="kpi-info">
                <h3 id="total-ingresos">$0</h3>
                <p>Ingresos totales (facturados)</p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="kpi-info">
                <h3 id="total-ordenes">0</h3>
                <p>Órdenes de trabajo</p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-car"></i></div>
            <div class="kpi-info">
                <h3 id="total-vehiculos">0</h3>
                <p>Vehículos registrados</p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-info">
                <h3 id="total-clientes">0</h3>
                <p>Clientes activos</p>
            </div>
        </div>
    </div>

    <!-- Gráficos principales -->
    <div class="charts-row">
        <div class="chart-card">
            <h3>Ingresos mensuales</h3>
            <canvas id="chart-ingresos" width="400" height="250"></canvas>
        </div>
        <div class="chart-card">
            <h3>Órdenes por estado</h3>
            <canvas id="chart-estados" width="400" height="250"></canvas>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-card">
            <h3>Top 5 servicios más solicitados</h3>
            <canvas id="chart-servicios" width="400" height="250"></canvas>
        </div>
        <div class="chart-card">
            <h3>Top 5 vehículos con más servicios</h3>
            <canvas id="chart-vehiculos" width="400" height="250"></canvas>
        </div>
    </div>

    <!-- Tabla de órdenes recientes -->
    <div class="table-card">
        <h3>Últimas órdenes de trabajo</h3>
        <div class="table-container">
            <table id="tabla-ordenes-recientes">
                <thead>
                    <tr><th>Código</th><th>Cliente</th><th>Vehículo</th><th>Fecha</th><th>Estado</th><th>Total</th></tr>
                </thead>
                <tbody id="tbody-ordenes">
                    <tr><td colspan="6">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../assets/js/reportes.js"></script>
</body>
</html>