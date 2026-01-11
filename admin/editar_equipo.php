<?php
// admin/editar_equipo.php
require_once '../includes/init.php';

redirectIfNotLoggedIn();
if ($_SESSION['user_rol'] != 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}

$conn = getConnection();
$mensaje = '';
$error = '';

// Verificar que se proporciona un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: equipos.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos actuales del equipo
$stmt = $conn->prepare("SELECT * FROM equipos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // No existe el equipo
    header('Location: equipos.php');
    exit();
}

$equipo = $result->fetch_assoc();
$stmt->close();

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar'])) {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $categoria = $_POST['categoria'];
    $estado = $_POST['estado'];
    $ubicacion = $_POST['ubicacion'];
    
    // Validar que el código no esté duplicado (excepto para este mismo equipo)
    $stmt = $conn->prepare("SELECT id FROM equipos WHERE codigo = ? AND id != ?");
    $stmt->bind_param("si", $codigo, $id);
    $stmt->execute();
    $stmt->store_result();
    
    
    if ($stmt->num_rows > 0) {
        $error = "El código '$codigo' ya está siendo usado por otro equipo.";
    } else {
        $stmt->close();
        $stmt = $conn->prepare("UPDATE equipos SET codigo = ?, nombre = ?, descripcion = ?, categoria = ?, estado = ?, ubicacion = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $codigo, $nombre, $descripcion, $categoria, $estado, $ubicacion, $id);
        
        if ($stmt->execute()) {
            // ✅ REDIRIGIR AUTOMÁTICAMENTE A LA LISTA
            header("Location: equipos.php?mensaje=Equipo+actualizado+correctamente");
            exit();
        } else {
            $error = "Error al actualizar el equipo: " . $conn->error;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-edit"></i> Editar Equipo</h1>
                    <a href="equipos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Editando: <?php echo $equipo['nombre']; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="codigo" class="form-label">Código *</label>
                                    <input type="text" class="form-control" id="codigo" name="codigo" value="<?php echo htmlspecialchars($equipo['codigo']); ?>" required>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($equipo['nombre']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="categoria" class="form-label">Categoría *</label>
                                    <select class="form-select" id="categoria" name="categoria" required>
                                        <option value="audiovisual" <?php echo $equipo['categoria'] == 'audiovisual' ? 'selected' : ''; ?>>Audiovisual</option>
                                        <option value="computacion" <?php echo $equipo['categoria'] == 'computacion' ? 'selected' : ''; ?>>Computación</option>
                                        <option value="laboratorio" <?php echo $equipo['categoria'] == 'laboratorio' ? 'selected' : ''; ?>>Laboratorio</option>
                                        <option value="otros" <?php echo $equipo['categoria'] == 'otros' ? 'selected' : ''; ?>>Otros</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?php echo htmlspecialchars($equipo['descripcion']); ?></textarea>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="disponible" <?php echo $equipo['estado'] == 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                        <option value="prestado" <?php echo $equipo['estado'] == 'prestado' ? 'selected' : ''; ?>>Prestado</option>
                                        <option value="mantenimiento" <?php echo $equipo['estado'] == 'mantenimiento' ? 'selected' : ''; ?>>En Mantenimiento</option>
                                        <option value="dañado" <?php echo $equipo['estado'] == 'dañado' ? 'selected' : ''; ?>>Dañado</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="ubicacion" class="form-label">Ubicación</label>
                                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($equipo['ubicacion']); ?>">
                                </div>
                            </div>
                            <button type="submit" name="actualizar" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Equipo
                            </button>
                            <a href="equipos.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>