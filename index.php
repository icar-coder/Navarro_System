<?php
// Redirige al dashboard principal (ruta absoluta)
include_once __DIR__ . '/config.php';
header('Location: ' . URL_BASE . 'vistas/dashboard_view.php', true, 302);
exit();

