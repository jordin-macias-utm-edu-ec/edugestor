<?php
echo "Directorio actual: " . __DIR__ . "<br>";
$ruta = __DIR__ . '/includes/database.php';
echo "Buscando archivo en: " . $ruta . "<br>";

if(file_exists($ruta)) {
    require_once $ruta;
    $conn = getConnection();
    echo "✅ Conexión exitosa a la base de datos!<br>";
    echo "✅ Base de datos: " . DB_NAME . " conectada.";
    $conn->close();
} else {
    echo "❌ El archivo NO existe.<br>";
    echo "Por favor, verifica que los archivos estén en:<br>";
    echo "1. C:/xampp/htdocs/edugestor/includes/config.php<br>";
    echo "2. C:/xampp/htdocs/edugestor/includes/database.php";
}
?>