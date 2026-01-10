<?php
// database.php
require_once 'config.php';

function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }
        $conn->set_charset("utf8");
        return $conn;
    } catch(Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>