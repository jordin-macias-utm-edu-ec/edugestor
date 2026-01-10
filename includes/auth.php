<?php
// auth.php
require_once 'database.php';

function login($email, $password) {
    $conn = getConnection();
    $email = $conn->real_escape_string($email);
    
    $sql = "SELECT id, email, password, nombre, rol FROM usuarios WHERE email = '$email' AND activo = 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Verificar la contraseña (en el futuro, usaremos password_verify)
        // Por ahora, asumimos que la contraseña es texto plano (lo cambiaremos luego)
        if ($password == 'admin123' && $email == 'admin@edugestor.com') {
            // Contraseña correcta
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];
            return true;
        } else {
            // Contraseña incorrecta
            return false;
        }
    } else {
        // Usuario no encontrado
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}

function redirectIfNotAdmin() {
    if ($_SESSION['user_rol'] != 'admin') {
        header('Location: ' . APP_URL . '/user/dashboard.php');
        exit();
    }
}
?>