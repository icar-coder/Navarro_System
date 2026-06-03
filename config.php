<?php
// Configuración centralizada de URL base para entornos local y producción.
if (!defined('URL_BASE')) {
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    if ($serverName === 'localhost' || $serverName === '127.0.0.1') {
        define('URL_BASE', 'http://localhost/navarro_update/');
    } else {
        define('URL_BASE', 'https://navarro-system.onrender.com/');
    }
}
