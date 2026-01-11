<?php
// admin/usuarios.php
require_once '../includes/init.php';

redirectIfNotLoggedIn();
if ($_SESSION['user_rol'] != 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}

$conn = getConnection();
$mensaje = '';
$error = '';

// Configuración de paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Parámetros de búsqueda y filtro
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_rol = isset($_GET['rol']) ? $_GET['rol'] : '';
$filtro_activo = isset($_GET['activo']) ? $_GET['activo'] : '';

// Procesar acciones sobre usuarios
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $accion = $_GET['accion'];
    
    if (in_array($accion, ['activar', 'desactivar'])) {
        $activo = $accion == 'activar' ? 1 : 0;
        $stmt = $conn->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $activo, $id);
        
        if ($stmt->execute()) {
            $mensaje = "Usuario " . ($activo ? "activado" : "desactivado") . " correctamente.";
        } else {
            $error = "Error al actualizar el usuario.";
        }
        $stmt->close();
    }
    
    // Eliminar usuario (solo si no es el propio admin)
    if ($accion == 'eliminar' && $id != $_SESSION['user_id']) {
        // Verificar si el usuario tiene préstamos activos
        $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM prestamos WHERE usuario_id = ? AND estado IN ('pendiente', 'aprobado', 'activo')");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        $stmt_check->close();
        
        if ($row_check['total'] > 0) {
            $error = "No se puede eliminar el usuario porque tiene préstamos activos o pendientes.";
        } else {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $mensaje = "Usuario eliminado correctamente.";
            } else {
                $error = "Error al eliminar el usuario.";
            }
            $stmt->close();
        }
    }
}

// Procesar cambio de rol
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_rol'])) {
    $id = intval($_POST['id']);
    $nuevo_rol = $_POST['rol'];
    
    if (in_array($nuevo_rol, ['admin', 'docente', 'estudiante'])) {
        // Evitar que un admin se quite a sí mismo los privilegios
        if ($id == $_SESSION['user_id'] && $nuevo_rol != 'admin') {
            $error = "No puedes cambiar tu propio rol de administrador.";
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_rol, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Rol actualizado correctamente.";
            } else {
                $error = "Error al actualizar el rol.";
            }
            $stmt->close();
        }
    }
}

// Construir consulta con filtros
$condiciones = [];
$params = [];
$tipos = '';

if (!empty($busqueda)) {
    $condiciones[] = "(nombre LIKE ? OR email LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $tipos .= 'ss';
}

if (!empty($filtro_rol)) {
    $condiciones[] = "rol = ?";
    $params[] = $filtro_rol;
    $tipos .= 's';
}

if ($filtro_activo !== '') {
    $condiciones[] = "activo = ?";
    $params[] = (int)$filtro_activo;
    $tipos .= 'i';
}

$where = '';
if (!empty($condiciones)) {
    $where = 'WHERE ' . implode(' AND ', $condiciones);
}

// Obtener total de usuarios para paginación
$sql_total = "SELECT COUNT(*) as total FROM usuarios $where";
$stmt_total = $conn->prepare($sql_total);
if (!empty($params)) {
    $stmt_total->bind_param($tipos, ...$params);
}
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_usuarios = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_usuarios / $por_pagina);

// Obtener usuarios con paginación
$sql = "SELECT * FROM usuarios $where ORDER BY fecha_registro DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

$params_paginacion = $params;
$params_paginacion[] = $por_pagina;
$params_paginacion[] = $offset;
$tipos_paginacion = $tipos . 'ii';

if (!empty($params_paginacion)) {
    $stmt->bind_param($tipos_paginacion, ...$params_paginacion);
} else {
    $stmt->bind_param('ii', $por_pagina, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

// Obtener estadísticas
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN rol = 'docente' THEN 1 ELSE 0 END) as docentes,
    SUM(CASE WHEN rol = 'estudiante' THEN 1 ELSE 0 END) as estudiantes,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos
    FROM usuarios";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .user-card:hover {
            transform: translateY(-2px);
            transition: transform 0.2s;
        }
        .badge-online {
            background-color: #28a745;
        }
        .badge-offline {
            background-color: #dc3545;
        }
        .stats-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            color: white;
        }
        .filter-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-users"></i> Gestión de Usuarios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="usuarios.php?exportar=csv" class="btn btn-sm btn-outline-success me-2">
                            <i class="fas fa-download"></i> Exportar CSV
                        </a>
                        <a href="registro_masivo.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user-plus"></i> Registro Masivo
                        </a>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stats-card bg-primary">
                            <h5 class="card-title">Total</h5>
                            <h2><?php echo $stats['total']; ?></h2>
                            <small>Usuarios</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-danger">
                            <h5 class="card-title">Admins</h5>
                            <h2><?php echo $stats['admins']; ?></h2>
                            <small>Usuarios</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-warning">
                            <h5 class="card-title">Docentes</h5>
                            <h2><?php echo $stats['docentes']; ?></h2>
                            <small>Usuarios</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-success">
                            <h5 class="card-title">Estudiantes</h5>
                            <h2><?php echo $stats['estudiantes']; ?></h2>
                            <small>Usuarios</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-info">
                            <h5 class="card-title">Activos</h5>
                            <h2><?php echo $stats['activos']; ?></h2>
                            <small>Usuarios</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-secondary">
                            <h5 class="card-title">Inactivos</h5>
                            <h2><?php echo $stats['inactivos']; ?></h2>
                            <small>Usuarios</small>
                        </div>
                    </div>
                </div>

                <!-- Filtros de búsqueda -->
                <div class="filter-card">
                    <h5><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="busqueda" class="form-label">Buscar por nombre o email</label>
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Nombre o email...">
                        </div>
                        <div class="col-md-3">
                            <label for="rol" class="form-label">Filtrar por rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="">Todos los roles</option>
                                <option value="admin" <?php echo $filtro_rol == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                <option value="docente" <?php echo $filtro_rol == 'docente' ? 'selected' : ''; ?>>Docente</option>
                                <option value="estudiante" <?php echo $filtro_rol == 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="activo" class="form-label">Filtrar por estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="">Todos los estados</option>
                                <option value="1" <?php echo $filtro_activo === '1' ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?php echo $filtro_activo === '0' ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                    <?php if ($busqueda || $filtro_rol || $filtro_activo !== ''): ?>
                    <div class="mt-3">
                        <a href="usuarios.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </a>
                        <small class="text-muted ms-2">
                            Mostrando <?php echo $result->num_rows; ?> de <?php echo $total_usuarios; ?> usuarios
                        </small>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Lista de usuarios -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Usuarios</h5>
                        <span class="badge bg-primary">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>Contacto</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($usuario = $result->fetch_assoc()): 
                                            $iniciales = strtoupper(substr($usuario['nombre'], 0, 2));
                                            $es_yo = $usuario['id'] == $_SESSION['user_id'];
                                        ?>
                                        <tr class="user-card">
                                            <td><?php echo $usuario['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar">
                                                        <?php echo $iniciales; ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo $usuario['nombre']; ?></strong>
                                                        <?php if ($es_yo): ?>
                                                            <span class="badge bg-info ms-1">Tú</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-envelope me-1"></i><?php echo $usuario['email']; ?><br>
                                                    <?php if ($usuario['telefono']): ?>
                                                    <small><i class="fas fa-phone me-1"></i><?php echo $usuario['telefono']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                    <div class="input-group input-group-sm">
                                                        <select name="rol" class="form-select form-select-sm" 
                                                                onchange="this.form.submit()" <?php echo $es_yo ? 'disabled' : ''; ?>>
                                                            <option value="estudiante" <?php echo $usuario['rol'] == 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                                                            <option value="docente" <?php echo $usuario['rol'] == 'docente' ? 'selected' : ''; ?>>Docente</option>
                                                            <option value="admin" <?php echo $usuario['rol'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        </select>
                                                        <?php if (!$es_yo): ?>
                                                        <button type="submit" name="cambiar_rol" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <?php if ($usuario['activo']): ?>
                                                    <span class="badge badge-online">Activo</span>
                                                    <?php if (!$es_yo): ?>
                                                        <a href="usuarios.php?accion=desactivar&id=<?php echo $usuario['id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning ms-1" 
                                                           onclick="return confirm('¿Desactivar este usuario?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge badge-offline">Inactivo</span>
                                                    <a href="usuarios.php?accion=activar&id=<?php echo $usuario['id']; ?>" 
                                                       class="btn btn-sm btn-outline-success ms-1">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($usuario['fecha_registro'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalUsuario<?php echo $usuario['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (!$es_yo): ?>
                                                    <a href="usuarios.php?accion=eliminar&id=<?php echo $usuario['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('¿Estás SEGURO de eliminar a <?php echo addslashes($usuario['nombre']); ?>?\n\nEsta acción NO se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal para ver detalles del usuario -->
                                        <div class="modal fade" id="modalUsuario<?php echo $usuario['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-user me-2"></i>
                                                            Detalles de <?php echo $usuario['nombre']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-3 text-center">
                                                                <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 24px;">
                                                                    <?php echo $iniciales; ?>
                                                                </div>
                                                                <h5 class="mt-3"><?php echo $usuario['nombre']; ?></h5>
                                                                <span class="badge bg-<?php echo $usuario['activo'] ? 'success' : 'danger'; ?>">
                                                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                                </span>
                                                            </div>
                                                            <div class="col-md-9">
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <h6><i class="fas fa-id-card me-2"></i>Información Personal</h6>
                                                                        <p><strong>ID:</strong> <?php echo $usuario['id']; ?></p>
                                                                        <p><strong>Email:</strong> <?php echo $usuario['email']; ?></p>
                                                                        <p><strong>Teléfono:</strong> <?php echo $usuario['telefono'] ?: 'No registrado'; ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6><i class="fas fa-briefcase me-2"></i>Información Académica</h6>
                                                                        <p><strong>Rol:</strong> 
                                                                            <span class="badge bg-<?php 
                                                                                echo $usuario['rol'] == 'admin' ? 'danger' : 
                                                                                     ($usuario['rol'] == 'docente' ? 'warning' : 'success');
                                                                            ?>">
                                                                                <?php echo ucfirst($usuario['rol']); ?>
                                                                            </span>
                                                                        </p>
                                                                        <p><strong>Departamento:</strong> <?php echo $usuario['departamento'] ?: 'No especificado'; ?></p>
                                                                        <p><strong>Registrado:</strong> <?php echo date('d/m/Y H:i:s', strtotime($usuario['fecha_registro'])); ?></p>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <h6><i class="fas fa-history me-2"></i>Estadísticas de Préstamos</h6>
                                                                <?php
                                                                $sql_prestamos = "SELECT 
                                                                    COUNT(*) as total,
                                                                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                                                                    SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                                                                    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados
                                                                    FROM prestamos WHERE usuario_id = ?";
                                                                $stmt_prestamos = $conn->prepare($sql_prestamos);
                                                                $stmt_prestamos->bind_param("i", $usuario['id']);
                                                                $stmt_prestamos->execute();
                                                                $result_prestamos = $stmt_prestamos->get_result();
                                                                $prestamos = $result_prestamos->fetch_assoc();
                                                                $stmt_prestamos->close();
                                                                ?>
                                                                <div class="row text-center">
                                                                    <div class="col-md-3">
                                                                        <div class="card bg-light">
                                                                            <div class="card-body">
                                                                                <h5 class="card-title"><?php echo $prestamos['total']; ?></h5>
                                                                                <p class="card-text"><small>Total Préstamos</small></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="card bg-warning text-white">
                                                                            <div class="card-body">
                                                                                <h5 class="card-title"><?php echo $prestamos['pendientes']; ?></h5>
                                                                                <p class="card-text"><small>Pendientes</small></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="card bg-success text-white">
                                                                            <div class="card-body">
                                                                                <h5 class="card-title"><?php echo $prestamos['aprobados']; ?></h5>
                                                                                <p class="card-text"><small>Aprobados</small></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="card bg-info text-white">
                                                                            <div class="card-body">
                                                                                <h5 class="card-title"><?php echo $prestamos['completados']; ?></h5>
                                                                                <p class="card-text"><small>Completados</small></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-primary">
                                                            <i class="fas fa-edit"></i> Editar Usuario
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if ($total_paginas > 1): ?>
                            <nav aria-label="Paginación de usuarios">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $pagina_actual == 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="usuarios.php?pagina=<?php echo $pagina_actual - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&rol=<?php echo $filtro_rol; ?>&activo=<?php echo $filtro_activo; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                        <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                                            <a class="page-link" href="usuarios.php?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&rol=<?php echo $filtro_rol; ?>&activo=<?php echo $filtro_activo; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $pagina_actual == $total_paginas ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="usuarios.php?pagina=<?php echo $pagina_actual + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&rol=<?php echo $filtro_rol; ?>&activo=<?php echo $filtro_activo; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-user-slash fa-3x mb-3"></i>
                                <h5>No se encontraron usuarios</h5>
                                <p>Intenta con otros criterios de búsqueda o 
                                   <a href="usuarios.php" class="alert-link">muestra todos los usuarios</a>.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirmación antes de acciones importantes
        function confirmarAccion(accion, nombre) {
            return confirm(`¿Estás seguro de ${accion} a ${nombre}?`);
        }
        
        // Auto-submit del formulario de cambio de rol
        document.querySelectorAll('select[name="rol"]').forEach(select => {
            select.addEventListener('change', function() {
                if (!this.disabled && !this.closest('form').querySelector('button[type="submit"]').disabled) {
                    this.form.submit();
                }
            });
        });
        
        // Filtro rápido con tecla Enter en búsqueda
        document.getElementById('busqueda').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
<?php 
$stmt->close();
$conn->close();
?>