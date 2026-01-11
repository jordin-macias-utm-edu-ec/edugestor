<?php
// admin/equipos.php
require_once '../includes/init.php';

redirectIfNotLoggedIn();
if ($_SESSION['user_rol'] != 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}

$conn = getConnection();
$mensaje = '';
$error = '';

// 1. Procesar creación de equipo (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear'])) {
    $codigo = $_POST['codigo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';

    // Validar campos obligatorios
    if (empty($codigo) || empty($nombre) || empty($categoria) || empty($estado)) {
        $error = "Por favor, completa todos los campos obligatorios.";
    } else {
        // Verificar si el código ya existe
        $stmt_check = $conn->prepare("SELECT id FROM equipos WHERE codigo = ?");
        $stmt_check->bind_param("s", $codigo);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "El código '$codigo' ya está en uso. Por favor, elige otro.";
            $stmt_check->close();
        } else {
            $stmt_check->close();
            // Insertar el nuevo equipo
            $stmt = $conn->prepare("INSERT INTO equipos (codigo, nombre, descripcion, categoria, estado, ubicacion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $codigo, $nombre, $descripcion, $categoria, $estado, $ubicacion);

            if ($stmt->execute()) {
                $mensaje = "Equipo creado exitosamente.";
            } else {
                $error = "Error al crear el equipo: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// 2. Procesar eliminación (GET)
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    // Verificar que el equipo exista
    $stmt_check = $conn->prepare("SELECT id FROM equipos WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        $stmt = $conn->prepare("DELETE FROM equipos WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensaje = "Equipo eliminado exitosamente.";
        } else {
            $error = "Error al eliminar el equipo.";
        }
        $stmt->close();
    } else {
        $error = "El equipo que intentas eliminar no existe.";
        $stmt_check->close();
    }
}

// 3. Mostrar mensaje de edición (si viene en la URL)
if (isset($_GET['mensaje'])) {
    $mensaje = urldecode($_GET['mensaje']);
}

// Obtener la lista de equipos para mostrar
$result = $conn->query("SELECT * FROM equipos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-laptop"></i> Gestión de Equipos</h1>
                </div>

                <!-- Mostrar mensajes -->
                <?php if ($mensaje): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulario para agregar equipo -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Agregar Nuevo Equipo</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="codigo" class="form-label">Código *</label>
                                    <input type="text" class="form-control" id="codigo" name="codigo" required>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="categoria" class="form-label">Categoría *</label>
                                    <select class="form-select" id="categoria" name="categoria" required>
                                        <option value="audiovisual">Audiovisual</option>
                                        <option value="computacion">Computación</option>
                                        <option value="laboratorio">Laboratorio</option>
                                        <option value="otros">Otros</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="disponible">Disponible</option>
                                        <option value="mantenimiento">En Mantenimiento</option>
                                        <option value="dañado">Dañado</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="ubicacion" class="form-label">Ubicación</label>
                                    <input type="text" class="form-control" id="ubicacion" name="ubicacion">
                                </div>
                            </div>
                            <button type="submit" name="crear" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Equipo
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lista de equipos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Equipos (<?php echo $result->num_rows; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Categoría</th>
                                            <th>Estado</th>
                                            <th>Ubicación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($equipo = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($equipo['codigo']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($equipo['nombre']); ?></td>
                                            <td>
                                                <?php 
                                                $categorias = [
                                                    'audiovisual' => '<span class="badge bg-info">Audiovisual</span>',
                                                    'computacion' => '<span class="badge bg-primary">Computación</span>',
                                                    'laboratorio' => '<span class="badge bg-warning">Laboratorio</span>',
                                                    'otros' => '<span class="badge bg-secondary">Otros</span>'
                                                ];
                                                echo $categorias[$equipo['categoria']] ?? $categorias['otros'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $estados = [
                                                    'disponible' => '<span class="badge bg-success">Disponible</span>',
                                                    'prestado' => '<span class="badge bg-danger">Prestado</span>',
                                                    'mantenimiento' => '<span class="badge bg-warning">Mantenimiento</span>',
                                                    'dañado' => '<span class="badge bg-dark">Dañado</span>'
                                                ];
                                                echo $estados[$equipo['estado']] ?? $estados['disponible'];
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($equipo['ubicacion']); ?></td>
                                            <td>
                                                <a href="editar_equipo.php?id=<?php echo $equipo['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="equipos.php?eliminar=<?php echo $equipo['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('¿Estás seguro de eliminar este equipo?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No hay equipos registrados. Agrega el primero usando el formulario superior.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>