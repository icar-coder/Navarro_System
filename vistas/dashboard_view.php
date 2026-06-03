<?php include "../includes/navbar.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Panel de Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos_globales.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/config.js"></script>
</head>
<body>
<div class="container">
    <!-- Header con fecha y botón refresh -->
    <div class="dashboard-header">
        <h1><i class="fas fa-chart-line"></i> Panel de Control</h1>
        <div class="header-actions">
            <span id="fechaActual" class="fecha-badge"></span>
            <button id="btnRefresh" class="btn-refresh"><i class="fas fa-sync-alt"></i> Actualizar</button>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3 id="total-clientes">0</h3>
                <p>Clientes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-car"></i></div>
            <div class="stat-info">
                <h3 id="total-vehiculos">0</h3>
                <p>Vehículos</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-info">
                <h3 id="total-ordenes">0</h3>
                <p>Órdenes de Trabajo</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3 id="ordenes-completadas">0</h3>
                <p>Órdenes Completadas</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-info">
                <h3 id="ingresos-mes">$0</h3>
                <p>Ingresos del mes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-info">
                <h3 id="citas-proximas">0</h3>
                <p>Citas próximas (7d)</p>
            </div>
        </div>
    </div>

    <!-- Gráficas y tablas -->
    <div class="dashboard-grid-main">
        <div class="card chart-card">
            <h3><i class="fas fa-chart-bar"></i> Tendencia de Nuevos Clientes</h3>
            <div id="grafica-tendencia" style="height: 300px;"></div>
        </div>
        <div class="card chart-card">
            <h3><i class="fas fa-chart-pie"></i> Órdenes por Estado</h3>
            <canvas id="chart-ordenes-estado" width="400" height="250" style="max-height: 250px;"></canvas>
        </div>
    </div>

    <div class="dashboard-grid-secondary">
        <div class="card">
            <h3><i class="fas fa-clock"></i> Últimas Órdenes de Trabajo</h3>
            <div class="table-responsive">
                <table class="mini-table">
                    <thead>
                        <tr><th>Código</th><th>Cliente</th><th>Vehículo</th><th>Fecha</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="ultimas-ordenes"></tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <h3><i class="fas fa-calendar-alt"></i> Próximas Citas</h3>
            <div class="table-responsive">
                <table class="mini-table">
                    <thead>
                        <tr><th>Cliente</th><th>Vehículo</th><th>Fecha/Hora</th><th>Motivo</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="proximas-citas"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/dashboard.js"></script>
</body>
</html>