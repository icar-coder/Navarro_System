<?php
// Iniciar sesión si no está iniciada (para mostrar nombre de usuario)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config.php';
// Datos de usuario de ejemplo (ajusta según tu lógica de autenticación)
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$rolUsuario = $_SESSION['rol'] ?? 'Administrador';
$avatarIniciales = strtoupper(substr($nombreUsuario, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>assets/css/navbar.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo / Marca -->
        <div class="navbar-brand">
            <a href="<?php echo URL_BASE; ?>vistas/dashboard_view.php">
                <i class="fas fa-car-side"></i>
                <span>AutoGestión</span>
            </a>
        </div>

        <!-- Botón hamburguesa (móvil) -->
        <button class="navbar-toggler" id="navbarToggler">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Menú principal -->
        <div class="navbar-menu" id="navbarMenu">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="<?php echo URL_BASE; ?>vistas/dashboard_view.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URL_BASE; ?>vistas/clientes_view.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URL_BASE; ?>vistas/vehiculos_view.php" class="nav-link">
                        <i class="fas fa-car"></i>
                        <span>Vehículos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URL_BASE; ?>vistas/ordenes_view.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Órdenes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URL_BASE; ?>vistas/citas_view.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Citas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URL_BASE; ?>vistas/reportes_view.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URL_BASE; ?>vistas/configuracion_view.php" class="nav-link">
                        <i class="fas fa-cogs"></i>
                        <span>Configuración</span>
                    </a>
                </li>
            </ul>

            <!-- Perfil de usuario -->
            <div class="navbar-user">
                <div class="user-avatar">
                    <?php echo $avatarIniciales; ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
                </div>
                <a href="<?php echo URL_BASE; ?>logout.php" class="logout-btn" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    // Toggle menú móvil
    const toggler = document.getElementById('navbarToggler');
    const menu = document.getElementById('navbarMenu');
    if (toggler && menu) {
        toggler.addEventListener('click', () => {
            menu.classList.toggle('active');
        });
    }
</script>
</body>
</html>