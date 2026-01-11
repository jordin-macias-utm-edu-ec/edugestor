<?php
// admin/usuarios.php
require_once '../includes/init.php';
redirectIfNotLoggedIn();
if ($_SESSION['user_rol'] != 'admin') { header('Location: ../user/dashboard.php'); exit(); }

$conn = getConnection();

// --- 1. EXPORTAR CSV  ---
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    ob_end_clean(); // Limpiar el búfer para que el CSV sea puro
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=usuarios_edugestor_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Bom para UTF-8 (compatibilidad con Excel)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['ID', 'Nombre', 'Email', 'Telefono', 'Rol', 'Estado']);
    
    $query_csv = "SELECT id, nombre, email, telefono, rol, IF(activo=1, 'Activo', 'Inactivo') FROM usuarios";
    $res_csv = $conn->query($query_csv);
    while ($row = $res_csv->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// --- 2. ACCIONES: ELIMINAR Y CAMBIAR ESTADO ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM usuarios WHERE id = $id AND id != " . $_SESSION['user_id']);
    header("Location: usuarios.php?msg=deleted");
    exit();
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE usuarios SET activo = NOT activo WHERE id = $id");
    header("Location: usuarios.php?msg=updated");
    exit();
}

// --- 3. LÓGICA PARA INSERTAR NUEVO USUARIO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_guardar'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $email = $conn->real_escape_string($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $conn->real_escape_string($_POST['rol']);
    $tel = $conn->real_escape_string($_POST['telefono']);
    $conn->query("INSERT INTO usuarios (nombre, email, password, rol, telefono, activo) VALUES ('$nombre', '$email', '$pass', '$rol', '$tel', 1)");
    header("Location: usuarios.php?msg=created");
    exit();
}

// --- 4. BÚSQUEDA Y FILTROS ---
$busqueda = isset($_GET['busqueda']) ? $conn->real_escape_string(trim($_GET['busqueda'])) : '';
$filtro_rol = isset($_GET['rol']) ? $conn->real_escape_string($_GET['rol']) : '';
$where = " WHERE 1=1 ";
if (!empty($busqueda)) { $where .= " AND (nombre LIKE '%$busqueda%' OR email LIKE '%$busqueda%')"; }
if (!empty($filtro_rol)) { $where .= " AND rol = '$filtro_rol'"; }

$res = $conn->query("SELECT * FROM usuarios $where ORDER BY fecha_registro DESC");
$stats = $conn->query("SELECT COUNT(*) as total, SUM(rol='admin') as admins, SUM(rol='docente') as docentes, SUM(rol='estudiante') as estudiantes FROM usuarios")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - EduGestor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-area { flex-grow: 1; padding: 20px; }
        .page-header { background: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .search-card { background: white; border: none; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stat-card { border: none; border-radius: 10px; color: white; padding: 15px; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h2 class="mb-0"><i class="fas fa-users-cog text-primary"></i> Gestión de Usuarios</h2>
            <div class="header-actions">
                <a href="?exportar=csv" class="btn btn-outline-success btn-sm me-2"><i class="fas fa-file-csv"></i> Exportar CSV</a>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3"><div class="stat-card bg-primary"><h5>Total: <?php echo $stats['total']; ?></h5></div></div>
            <div class="col-md-3"><div class="stat-card bg-danger"><h5>Admins: <?php echo $stats['admins']; ?></h5></div></div>
            <div class="col-md-3"><div class="stat-card bg-warning"><h5>Docentes: <?php echo $stats['docentes']; ?></h5></div></div>
            <div class="col-md-3"><div class="stat-card bg-success"><h5>Estudiantes: <?php echo $stats['estudiantes']; ?></h5></div></div>
        </div>

        <div class="search-card">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">BUSCAR USUARIO</label>
                    <input type="text" name="busqueda" class="form-control bg-light" placeholder="Nombre o email..." value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">ROL</label>
                    <select name="rol" class="form-select bg-light">
                        <option value="">Todos los roles</option>
                        <option value="admin" <?php echo $filtro_rol == 'admin' ? 'selected' : ''; ?>>Admins</option>
                        <option value="docente" <?php echo $filtro_rol == 'docente' ? 'selected' : ''; ?>>Docentes</option>
                        <option value="estudiante" <?php echo $filtro_rol == 'estudiante' ? 'selected' : ''; ?>>Estudiantes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $res->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?php echo htmlspecialchars($user['nombre']); ?></div>
                            <div class="text-muted small"><?php echo $user['email']; ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?php echo ucfirst($user['rol']); ?></span></td>
                        <td>
                            <a href="?toggle=<?php echo $user['id']; ?>" class="text-decoration-none">
                                <?php if($user['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalVer<?php echo $user['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar usuario?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>

                            <div class="modal fade" id="modalVer<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content text-start">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detalles: <?php echo $user['nombre']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                                            <p><strong>Teléfono:</strong> <?php echo $user['telefono'] ?: 'No registrado'; ?></p>
                                            <p><strong>Rol:</strong> <?php echo strtoupper($user['rol']); ?></p>
                                            <p><strong>Registro:</strong> <?php echo $user['fecha_registro']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control"></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="rol" class="form-select">
                                <option value="estudiante">Estudiante</option>
                                <option value="docente">Docente</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="btn_guardar" class="btn btn-primary w-100">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>