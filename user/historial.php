<?php
// user/historial.php
require_once '../includes/init.php';
redirectIfNotLoggedIn();

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$mensaje = '';
$error = '';

// --- LÓGICA DE CANCELACIÓN OPTIMIZADA ---
if (isset($_GET['accion']) && $_GET['accion'] == 'cancelar' && isset($_GET['id'])) {
    $id_prestamo = intval($_GET['id']);
    
    // Intentamos el update
    $stmt_cancel = $conn->prepare("UPDATE prestamos SET estado = 'cancelado' WHERE id = ? AND usuario_id = ? AND estado = 'pendiente'");
    $stmt_cancel->bind_param("ii", $id_prestamo, $user_id);
    
    if ($stmt_cancel->execute() && $stmt_cancel->affected_rows > 0) {
        // ÉXITO: Redirigimos para limpiar la URL y evitar errores al refrescar
        header("Location: historial.php?res=success&id=" . $id_prestamo);
        exit();
    } else {
        // ERROR: Probablemente ya no esté pendiente
        $error = "No se pudo cancelar la solicitud. Es posible que ya haya sido procesada.";
    }
    $stmt_cancel->close();
}

// Capturar el mensaje de éxito tras la redirección
if (isset($_GET['res']) && $_GET['res'] == 'success') {
    $mensaje = "La solicitud #" . intval($_GET['id']) . " ha sido cancelada correctamente.";
}
// -----------------------------

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fas fa-history text-primary"></i> Mi Historial de Préstamos</h2>
                <p class="text-muted">Gestiona tus solicitudes y revisa su estado actual.</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
                <i class="fas fa-check-circle me-2"></i> <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow border-0 rounded-3">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-secondary">Mis Solicitudes</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $sql = "SELECT p.*, e.nombre as equipo_nombre, e.codigo as equipo_codigo 
                                FROM prestamos p 
                                JOIN equipos e ON p.equipo_id = e.id 
                                WHERE p.usuario_id = ? 
                                ORDER BY p.fecha_solicitud DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        $prestamos_data = [];
                        
                        if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">#ID</th>
                                            <th>Recurso</th>
                                            <th>Período</th>
                                            <th>Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($prestamo = $result->fetch_assoc()): 
                                            $prestamos_data[] = $prestamo; 
                                            $estado_class = '';
                                            switch($prestamo['estado']) {
                                                case 'pendiente': $estado_class = 'bg-warning text-dark'; break;
                                                case 'aprobado': $estado_class = 'bg-success'; break;
                                                case 'activo': $estado_class = 'bg-primary'; break;
                                                case 'completado': $estado_class = 'bg-secondary'; break;
                                                case 'rechazado': $estado_class = 'bg-danger'; break;
                                                case 'cancelado': $estado_class = 'bg-dark'; break;
                                            }
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-muted">#<?php echo $prestamo['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo $prestamo['equipo_nombre']; ?></div>
                                                <small class="text-muted"><?php echo $prestamo['equipo_codigo']; ?></small>
                                            </td>
                                            <td>
                                                <div class="small"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d/m/Y H:i', strtotime($prestamo['fecha_inicio'])); ?></div>
                                                <div class="small"><i class="fas fa-history me-1"></i> <?php echo date('d/m/Y H:i', strtotime($prestamo['fecha_fin'])); ?></div>
                                            </td>
                                            <td><span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($prestamo['estado']); ?></span></td>
                                            <td class="text-center">
                                                <div class="btn-group shadow-sm">
                                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $prestamo['id']; ?>" title="Ver Detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($prestamo['estado'] == 'pendiente'): ?>
                                                        <a href="?accion=cancelar&id=<?php echo $prestamo['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('¿Estás seguro de que deseas cancelar esta solicitud?')" 
                                                           title="Cancelar Solicitud">
                                                            <i class="fas fa-times-circle"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aún no has realizado ninguna solicitud de préstamo.</p>
                                <a href="solicitar.php" class="btn btn-primary">Hacer mi primera solicitud</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4 mb-5 g-3">
            <div class="col-md-3">
                <div class="card border-0 text-white bg-primary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Total Solicitudes</h6>
                        <h2 class="mb-0 fw-bold"><?php echo count($prestamos_data); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 text-white bg-success shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Aprobados</h6>
                        <?php
                        $aprobados = 0;
                        foreach($prestamos_data as $p) if($p['estado'] == 'aprobado' || $p['estado'] == 'activo') $aprobados++;
                        ?>
                        <h2 class="mb-0 fw-bold"><?php echo $aprobados; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 text-white bg-warning shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title opacity-75 text-dark">Pendientes</h6>
                        <?php
                        $pendientes = 0;
                        foreach($prestamos_data as $p) if($p['estado'] == 'pendiente') $pendientes++;
                        ?>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $pendientes; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 text-white bg-secondary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Otros (Rechazados/Cancelados)</h6>
                        <?php
                        $otros = 0;
                        foreach($prestamos_data as $p) if(in_array($p['estado'], ['rechazado', 'cancelado'])) $otros++;
                        ?>
                        <h2 class="mb-0 fw-bold"><?php echo $otros; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach($prestamos_data as $p): ?>
    <div class="modal fade" id="modalDetalle<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Solicitud #<?php echo $p['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small text-muted d-block">Recurso</label>
                        <span class="fs-5 fw-bold"><?php echo $p['equipo_nombre']; ?></span>
                        <code class="d-block text-primary"><?php echo $p['equipo_codigo']; ?></code>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small text-muted d-block">Desde</label>
                            <strong><?php echo date('d/m/Y H:i', strtotime($p['fecha_inicio'])); ?></strong>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted d-block">Hasta</label>
                            <strong><?php echo date('d/m/Y H:i', strtotime($p['fecha_fin'])); ?></strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block">Propósito</label>
                        <div class="p-2 bg-light rounded border-start border-4 border-info">
                            <?php echo nl2br(htmlspecialchars($p['proposito'])); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <small class="me-auto text-muted">Solicitado: <?php echo date('d/m/Y H:i', strtotime($p['fecha_solicitud'])); ?></small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <?php if ($p['estado'] == 'pendiente'): ?>
                        <a href="?accion=cancelar&id=<?php echo $p['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Cancelar esta solicitud?')">Cancelar Solicitud</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>