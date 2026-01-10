<?php
// includes/init.php

// Incluir configuraciones
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>