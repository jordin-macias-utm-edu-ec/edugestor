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
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    // categoría del select o del campo "Nueva"
    $categoria = !empty($_POST['nueva_categoria']) ? $_POST['nueva_categoria'] : $_POST['categoria'];
    $categoria = $conn->real_escape_string($categoria);
    
    $estado = $conn->real_escape_string($_POST['estado']);
    $ubicacion = $conn->real_escape_string($_POST['ubicacion']);

    if (empty($codigo) || empty($nombre) || empty($categoria)) {
        $error = "Por favor, completa los campos obligatorios.";
    } else {
        $check = $conn->query("SELECT id FROM equipos WHERE codigo = '$codigo'");
        if ($check->num_rows > 0) {
            $error = "El código '$codigo' ya existe.";
        } else {
            $sql = "INSERT INTO equipos (codigo, nombre, descripcion, categoria, estado, ubicacion) 
                    VALUES ('$codigo', '$nombre', '$descripcion', '$categoria', '$estado', '$ubicacion')";
            if ($conn->query($sql)) {
                $mensaje = "Equipo registrado correctamente.";
            } else {
                $error = "Error al guardar.";
            }
        }
    }
}

// 2. Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conn->query("DELETE FROM equipos WHERE id = $id");
    header("Location: equipos.php?mensaje=" . urlencode("Equipo eliminado"));
    exit();
}

if (isset($_GET['mensaje'])) { $mensaje = urldecode($_GET['mensaje']); }

// Obtener categorías únicas para el buscador y el formulario
$res_cats = $conn->query("SELECT DISTINCT categoria FROM equipos ORDER BY categoria ASC");
$categorias_existentes = [];
while($cat = $res_cats->fetch_assoc()) { $categorias_existentes[] = $cat['categoria']; }

// Obtener lista de equipos
$result = $conn->query("SELECT * FROM equipos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Equipos - EduGestor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-area { flex-grow: 1; padding: 20px; overflow-x: hidden; }
        .page-header { background: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .card-custom { border: none; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .badge-cat { text-transform: uppercase; font-size: 0.7rem; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h2 class="mb-0"><i class="fas fa-boxes text-primary"></i> Inventario de Equipos</h2>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formNuevoEquipo">
                <i class="fas fa-plus"></i> Nuevo Equipo
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $mensaje; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="collapse mb-4" id="formNuevoEquipo">
            <div class="card card-custom">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Código</label>
                            <input type="text" name="codigo" class="form-control" placeholder="Ej: LAP-001" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Nombre del Equipo</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Nombre descriptivo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Categoría</label>
                            <div class="input-group">
                                <select name="categoria" class="form-select">
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach($categorias_existentes as $c): ?>
                                        <option value="<?php echo $c; ?>"><?php echo ucfirst($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="nueva_categoria" class="form-control" placeholder="O escribir nueva...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Estado Inicial</label>
                            <select name="estado" class="form-select">
                                <option value="disponible">Disponible</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="dañado">Dañado</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ubicación / Aula</label>
                            <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Laboratorio 1">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Descripción Corta</label>
                            <input type="text" name="descripcion" class="form-control">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="crear" class="btn btn-success px-4"><i class="fas fa-save"></i> Registrar Equipo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Código</th>
                            <th>Recurso / Nombre</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Ubicación</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($e = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?php echo $e['codigo']; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($e['nombre']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($e['descripcion']); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border badge-cat">
                                    <?php echo $e['categoria']; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $badge = ['disponible'=>'bg-success', 'prestado'=>'bg-danger', 'mantenimiento'=>'bg-warning', 'dañado'=>'bg-dark'];
                                $color = $badge[$e['estado']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $color; ?>"><?php echo ucfirst($e['estado']); ?></span>
                            </td>
                            <td><i class="fas fa-map-marker-alt text-muted me-1"></i> <?php echo $e['ubicacion']; ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="editar_equipo.php?id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <a href="?eliminar=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar equipo?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
Audiovisual 