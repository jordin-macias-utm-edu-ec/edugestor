<?php
require_once 'includes/database.php';
$conn = getConnection();
echo "✅ Conexión exitosa a la base de datos!";
$conn->close();
?>