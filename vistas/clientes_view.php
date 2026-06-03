<?php include "../includes/navbar.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestión de Clientes | Panel Moderno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos_globales.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/clientes.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/validators.js"></script>
</head>
<body>
    <main class="container">
        <!-- Header con estadísticas rápidas -->
        <div class="stats-row">
            <div class="stat-card-mini">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number" id="stat-total-clientes">0</div>
                <div class="stat-label">Total Clientes</div>
            </div>
            <div class="stat-card-mini success">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-number" id="stat-clientes-activos">0</div>
                <div class="stat-label">Activos</div>
            </div>
            <div class="stat-card-mini info">
                <div class="stat-icon"><i class="fas fa-car"></i></div>
                <div class="stat-number" id="stat-total-vehiculos">0</div>
                <div class="stat-label">Vehículos</div>
            </div>
            <div class="stat-card-mini warning">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-number" id="stat-total-ordenes">0</div>
                <div class="stat-label">Órdenes</div>
            </div>
        </div>

        <header class="page-header">
            <div>
                <h1><i class="fas fa-users"></i> Directorio de Clientes</h1>
                <p style="color: #4f6f8f; margin-top: 6px;">Administra la información de contacto y vehículos vinculados.</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button id="btn-exportar-pdf" class="btn-secondary">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
                <button class="btn-primary" id="btn-nuevo-cliente">
                    <i class="fas fa-plus"></i> Nuevo Cliente
                </button>
            </div>
        </header>

        <section class="toolbar-filters">
            <div class="search-field">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="buscar-cliente" placeholder="Buscar por nombre, cédula, teléfono...">
            </div>
            <div class="filter-select-wrapper">
                <select id="filtro-estado">
                    <option value="todos">Todos los estados</option>
                    <option value="activo">Activos</option>
                    <option value="inactivo">Inactivos</option>
                </select>
                <i class="fas fa-chevron-down select-icon"></i>
            </div>
            <div class="filter-date">
                <input type="date" id="fecha-inicio" placeholder="Desde">
                <span>—</span>
                <input type="date" id="fecha-fin" placeholder="Hasta">
            </div>
            <button class="btn-clear" id="btn-limpiar-filtros">
                <i class="fas fa-undo"></i> Limpiar
            </button>
        </section>

        <section class="table-container">
            <div class="table-wrapper">
                <table id="tabla-clientes">
                    <thead>
                        <tr>
                            <th><div class="sort-header" data-sort="identificacion">ID / Cédula<span class="sort-icon"><i class="fas fa-arrow-up"></i></span></div></th>
                            <th><div class="sort-header" data-sort="nombre">Nombre Completo<span class="sort-icon"><i class="fas fa-arrow-up"></i></span></div></th>
                            <th><div class="sort-header" data-sort="telefono">Teléfono<span class="sort-icon"><i class="fas fa-arrow-up"></i></span></div></th>
                            <th><div class="sort-header" data-sort="estado">Estado<span class="sort-icon"><i class="fas fa-arrow-up"></i></span></div></th>
                            <th><div class="sort-header" data-sort="fechaRegistro">Registro<span class="sort-icon"><i class="fas fa-arrow-up"></i></span></div></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-clientes-body">
                        <!-- Skeleton loading -->
                        <tr><td colspan="6"><div class="skeleton-row"><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Modal para formulario -->
    <div id="modal-cliente" class="modal">
        <div class="modal-content">
            <span class="close" id="close-modal-btn">&times;</span>
            <h2 id="modal-title">Registrar Cliente</h2>
            <form id="form-cliente">
                <div class="form-grid">
                    <input type="text" name="identificacion" id="cliente-cedula" placeholder="Cédula / Identificación *" required>
                    <input type="text" name="nombre" id="cliente-nombre" placeholder="Nombre *" required>
                    <input type="text" name="apellido" id="cliente-apellido" placeholder="Apellido *" required>
                    <input type="tel" name="telefono" id="cliente-telefono" placeholder="Teléfono">
                    <input type="email" name="email" id="cliente-email" placeholder="Correo Electrónico">
                    <textarea name="direccion" id="cliente-direccion" placeholder="Dirección completa" rows="2"></textarea>
                </div>
                <button type="submit" class="btn-save">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- Panel lateral de detalles del cliente -->
    <div id="cliente-drawer" class="drawer">
        <div class="drawer-overlay"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3><i class="fas fa-user-circle"></i> Detalles del Cliente</h3>
                <button class="drawer-close">&times;</button>
            </div>
            <div class="drawer-body" id="drawer-body">
                <div class="skeleton-drawer"></div>
            </div>
        </div>
    </div>

    <script src="../assets/js/clientes.js"></script>
</body>
</html>