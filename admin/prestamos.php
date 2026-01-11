<?php
// admin/prestamos.php
require_once '../includes/init.php';

redirectIfNotLoggedIn();
if ($_SESSION['user_rol'] != 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}

$conn = getConnection();
$mensaje = '';
$error = '';

/**
 * Función auxiliar para la identidad visual del administrador
 */
function getAdminCategoryStyle($categoria) {
    switch ($categoria) {
        case 'Tecnología':
            return ['icon' => 'fa-laptop', 'color' => 'text-primary'];
        case 'Laboratorio':
            return ['icon' => 'fa-flask', 'color' => 'text-success'];
        case 'Espacios':
            return ['icon' => 'fa-building', 'color' => 'text-info'];
        default:
            return ['icon' => 'fa-box', 'color' => 'text-secondary'];
    }
}

// Procesar acciones sobre préstamos (Aprobar, Rechazar, Completar)
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $accion = $_GET['accion'];
    
    if (in_array($accion, ['aprobar', 'rechazar', 'completar'])) {
        $estado_nuevo = ($accion == 'aprobar') ? 'aprobado' : 
                       (($accion == 'rechazar') ? 'rechazado' : 'completado');
        
        if ($accion == 'aprobar') {
            $stmt = $conn->prepare("SELECT equipo_id FROM prestamos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $prestamo = $result->fetch_assoc();
            $stmt->close();
            
            if ($prestamo) {
                $stmt2 = $conn->prepare("UPDATE equipos SET estado = 'prestado' WHERE id = ?");
                $stmt2->bind_param("i", $prestamo['equipo_id']);
                $stmt2->execute();
                $stmt2->close();
            }
        } elseif ($accion == 'completar') {
            $stmt = $conn->prepare("SELECT equipo_id FROM prestamos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $prestamo = $result->fetch_assoc();
            $stmt->close();
            
            if ($prestamo) {
                $stmt2 = $conn->prepare("UPDATE equipos SET estado = 'disponible' WHERE id = ?");
                $stmt2->bind_param("i", $prestamo['equipo_id']);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        $stmt = $conn->prepare("UPDATE prestamos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado_nuevo, $id);
        
        if ($stmt->execute()) {
            $mensaje = "El préstamo #$id ha sido " . ($accion == 'aprobar' ? 'aprobado' : ($accion == 'rechazar' ? 'rechazado' : 'completado')) . " correctamente.";
        } else {
            $error = "Error al actualizar el estado.";
        }
        $stmt->close();
    }
}

// Obtener todos los préstamos incluyendo la CATEGORÍA del equipo
$sql = "SELECT p.*, e.nombre as equipo_nombre, e.codigo as equipo_codigo, e.categoria, u.nombre as usuario_nombre, u.email as usuario_email 
        FROM prestamos p 
        JOIN equipos e ON p.equipo_id = e.id 
        JOIN usuarios u ON p.usuario_id = u.id 
        ORDER BY p.fecha_solicitud DESC";
$result = $conn->query($sql);

$prestamos_data = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Préstamos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-exchange-alt text-primary"></i> Gestión de Préstamos</h1>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-0">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="ps-4">ID</th>
                                            <th>Usuario</th>
                                            <th>Recurso</th>
                                            <th>Período</th>
                                            <th>Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($prestamo = $result->fetch_assoc()): 
                                            $prestamos_data[] = $prestamo;
                                            $style = getAdminCategoryStyle($prestamo['categoria']);
                                            $badge_class = [
                                                'pendiente' => 'bg-warning text-dark',
                                                'aprobado' => 'bg-success',
                                                'rechazado' => 'bg-danger',
                                                'completado' => 'bg-secondary',
                                                'activo' => 'bg-primary'
                                            ];
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold">#<?php echo $prestamo['id']; ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"><?php echo htmlspecialchars($prestamo['usuario_nombre']); ?></span>
                                                    <small class="text-muted"><?php echo htmlspecialchars($prestamo['usuario_email']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2 <?php echo $style['color']; ?>" style="width: 25px; text-align: center;">
                                                        <i class="fas <?php echo $style['icon']; ?> fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-light text-dark border-0 small" style="font-size: 0.65rem;">
                                                            <?php echo htmlspecialchars($prestamo['equipo_codigo']); ?>
                                                        </span><br>
                                                        <span class="fw-bold"><?php echo htmlspecialchars($prestamo['equipo_nombre']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="mb-1 text-nowrap"><i class="far fa-calendar-check text-success me-1"></i> <?php echo date('d/m H:i', strtotime($prestamo['fecha_inicio'])); ?></div>
                                                    <div class="text-nowrap"><i class="far fa-calendar-times text-danger me-1"></i> <?php echo date('d/m H:i', strtotime($prestamo['fecha_fin'])); ?></div>
                                                </div>
                                            </td>
                                            <td><span class="badge <?php echo $badge_class[$prestamo['estado']] ?? 'bg-info'; ?>"><?php echo ucfirst($prestamo['estado']); ?></span></td>
                                            <td class="text-center">
                                                <div class="btn-group shadow-sm">
                                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $prestamo['id']; ?>" title="Ver Detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($prestamo['estado'] == 'pendiente'): ?>
                                                        <a href="?accion=aprobar&id=<?php echo $prestamo['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Aprobar solicitud?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="?accion=rechazar&id=<?php echo $prestamo['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Rechazar solicitud?')">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php elseif ($prestamo['estado'] == 'aprobado'): ?>
                                                        <a href="?accion=completar&id=<?php echo $prestamo['id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('¿Marcar como completado?')">
                                                            <i class="fas fa-undo"></i>
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
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No se encontraron préstamos registrados.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php foreach($prestamos_data as $p): 
        $styleModal = getAdminCategoryStyle($p['categoria']);
    ?>
    <div class="modal fade" id="modalDetalle<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Solicitud #<?php echo $p['id']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="small text-muted d-block">Solicitante</label>
                            <strong><?php echo htmlspecialchars($p['usuario_nombre']); ?></strong>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted d-block">Estado</label>
                            <span class="badge bg-info"><?php echo ucfirst($p['estado']); ?></span>
                        </div>
                        <div class="col-12 border-top pt-2">
                            <label class="small text-muted d-block">Recurso Solicitado</label>
                            <i class="fas <?php echo $styleModal['icon']; ?> <?php echo $styleModal['color']; ?> me-1"></i>
                            <strong><?php echo htmlspecialchars($p['equipo_nombre']); ?></strong> 
                            <span class="text-muted">(<?php echo htmlspecialchars($p['equipo_codigo']); ?>)</span>
                        </div>
                        <div class="col-12 bg-light p-3 rounded">
                            <label class="small text-muted d-block mb-1">Horario Reservado</label>
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-clock text-success"></i> <?php echo date('d/m/Y H:i', strtotime($p['fecha_inicio'])); ?></span>
                                <i class="fas fa-arrow-right text-muted mx-2"></i>
                                <span><?php echo date('d/m/Y H:i', strtotime($p['fecha_fin'])); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted d-block">Motivo del Préstamo</label>
                            <div class="p-2 border rounded bg-white mt-1"><?php echo nl2br(htmlspecialchars($p['proposito'])); ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <small class="me-auto text-muted">Solicitado: <?php echo date('d/m H:i', strtotime($p['fecha_solicitud'])); ?></small>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$result->free();
$conn->close(); 
?>