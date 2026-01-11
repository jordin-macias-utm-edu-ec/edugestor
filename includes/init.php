<?php
// includes/init.php


// 2. Cargar archivos base
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
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