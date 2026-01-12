<?php
// includes/auth.php

function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        // Redirige siempre a la raíz del proyecto usando la URL absoluta
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_rol'] == 'admin') {
            header('Location: ' . APP_URL . '/admin/index.php');
        } else {
            header('Location: ' . APP_URL . '/user/dashboard.php');
        }
        exit();
    }
}

function checkPassword($inputPassword, $hashedPassword) {
    return password_verify($inputPassword, $hashedPassword);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function authenticate($email, $password) {
    // Ya no necesitamos require_once aquí porque init.php lo carga todo
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT id, email, password, nombre, rol FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_nombre'] = $row['nombre'];
            $_SESSION['user_rol'] = $row['rol'];
            $stmt->close();
            return true;
        }
    }
    
    $stmt->close();
    return false;
}
?>