<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (authenticate($email, $password)) {
        // Redirigir según rol
        if ($_SESSION['user_rol'] == 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit();
    } else {
        $error = 'Credenciales incorrectas. Intenta de nuevo.';
        // Redirigir de vuelta al login con error
        header('Location: index.php?error=' . urlencode($error));
        exit();
    }
}
?>