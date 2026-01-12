<?php
// includes/init.php

// 1. Forzar visualización de errores para saber qué falla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Cargar archivos base
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';   // <-- AGREGA ESTA LÍNEA
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/auth.php';

// Solo cargar correo si el archivo existe para evitar que el script muera
if (file_exists(__DIR__ . '/email_config.php')) {
    require_once __DIR__ . '/email_config.php';
}

// 3. Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}