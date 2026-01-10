<?php
// auth.php

function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_rol'] == 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: user/dashboard.php');
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
    require_once 'database.php';
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
            $conn->close();
            return true;
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}
?>