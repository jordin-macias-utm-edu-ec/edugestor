<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de Sistema</h1>";

echo "1. Probando inclusión de init.php... ";
if (file_exists('includes/init.php')) {
    require_once 'includes/init.php';
    echo "<span style='color:green'>OK</span><br>";
} else {
    die("<span style='color:red'>ERROR: No se encuentra includes/init.php</span>");
}

echo "2. Probando conexión a base de datos... ";
try {
    $conn = getConnection();
    echo "<span style='color:green'>OK</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>ERROR: " . $e->getMessage() . "</span><br>";
}

echo "3. Verificando variables de sesión... <br>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Si llegaste aquí, el sistema base funciona. El error es un punto y coma o una llave en el archivo que sale en blanco.</h2>";
?>