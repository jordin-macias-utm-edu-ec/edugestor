<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edugestor');

// Configuración de la aplicación
define('APP_NAME', 'EduGestor');
define('APP_URL', 'http://localhost/edugestor');
define('APP_DEBUG', true);

// Iniciar sesión
session_start();

// Zona horaria
date_default_timezone_set('America/Bogota');

// Función para mostrar errores en desarrollo
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>