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
 * Función auxiliar para la identidad visual
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

// --- PROCESAR ACCIONES (Aprobar, Rechazar, Completar) ---
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $accion = $_GET['accion'];
    
    if (in_array($accion, ['aprobar', 'rechazar', 'completar'])) {
        $estado_nuevo = ($accion == 'aprobar') ? 'aprobado' : 
                        (($accion == 'rechazar') ? 'rechazado' : 'completado');
        
        // Primero actualizamos el estado en la base de datos
        $stmt = $conn->prepare("UPDATE prestamos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado_nuevo, $id);

        if ($stmt->execute()) {
            // Si la actualización fue exitosa, buscamos datos para enviar el correo
            $info_sql = "SELECT u.email, u.nombre as usuario_nombre, e.nombre as equipo_nombre 
                         FROM prestamos p 
                         JOIN usuarios u ON p.usuario_id = u.id 
                         JOIN equipos e ON p.equipo_id = e.id 
                         WHERE p.id = ?";
            
            $stmt_info = $conn->prepare($info_sql);
            $stmt_info->bind_param("i", $id);
            $stmt_info->execute();
            $resultado = $stmt_info->get_result();
            $datos = $resultado->fetch_assoc();

            if ($datos) {
                $destinatario = $datos['email'];
                $nombre = $datos['usuario_nombre'];
                $equipo = $datos['equipo_nombre'];

                // Personalizamos el mensaje según la acción
                if ($accion == 'aprobar') {
                    $asunto = "¡Préstamo Aprobado! - " . APP_NAME;
                    $mensaje_body = "Hola $nombre, tu solicitud para el recurso <b>$equipo</b> ha sido <b>APROBADA</b>. Ya puedes pasar a recogerlo.";
                } elseif ($accion == 'rechazar') {
                    $asunto = "Actualización de tu solicitud - " . APP_NAME;
                    $mensaje_body = "Hola $nombre, lamentamos informarte que tu solicitud para el recurso <b>$equipo</b> ha sido <b>RECHAZADA</b>.";
                }

                // Solo enviamos correo si es aprobar o rechazar (en completar no suele ser necesario)
                if ($accion != 'completar') {
                    enviarCorreoNotificacion($destinatario, $nombre, $asunto, $mensaje_body);
                }
            }
            $mensaje = "El estado se actualizó correctamente.";
        } else {
            $error = "Error al actualizar el estado.";
        }
        $stmt->close();
    }
}

// Obtener préstamos para la tabla
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
    <title>Gestión de Préstamos - EduGestor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-area { flex-grow: 1; padding: 20px; overflow-x: hidden; }
        .page-header { 
            background: white; 
            padding: 15px 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-table { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: none; }
        .badge-status { font-size: 0.8rem; padding: 5px 10px; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-area">
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $mensaje; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="page-header">
            <h2 class="mb-0"><i class="fas fa-exchange-alt text-primary"></i> Gestión de Préstamos</h2>
            <div class="header-actions">
                <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()"><i class="fas fa-sync"></i> Actualizar</button>
            </div>
        </div>

        <div class="card card-table overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
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
                        <?php while($p = $result->fetch_assoc()): 
                            $prestamos_data[] = $p; // Guardar para los modals
                            $style = getAdminCategoryStyle($p['categoria']);
                            $badge_class = [
                                'pendiente' => 'bg-warning text-dark',
                                'aprobado' => 'bg-success',
                                'rechazado' => 'bg-danger',
                                'completado' => 'bg-secondary'
                            ];
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#<?php echo $p['id']; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($p['usuario_nombre']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($p['usuario_email']); ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas <?php echo $style['icon']; ?> <?php echo $style['color']; ?> me-2 fa-lg"></i>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($p['equipo_nombre']); ?></div>
                                        <div class="small badge bg-light text-dark border"><?php echo $p['equipo_codigo']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="far fa-calendar-check text-success me-1"></i> <?php echo date('d/m H:i', strtotime($p['fecha_inicio'])); ?></div>
                                    <div><i class="far fa-calendar-times text-danger me-1"></i> <?php echo date('d/m H:i', strtotime($p['fecha_fin'])); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-status <?php echo $badge_class[$p['estado']] ?? 'bg-info'; ?>">
                                    <?php echo ucfirst($p['estado']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $p['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($p['estado'] == 'pendiente'): ?>
                                        <a href="?accion=aprobar&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-success" title="Aprobar"><i class="fas fa-check"></i></a>
                                        <a href="?accion=rechazar&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" title="Rechazar"><i class="fas fa-times"></i></a>
                                    <?php elseif ($p['estado'] == 'aprobado'): ?>
                                        <a href="?accion=completar&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary" title="Finalizar"><i class="fas fa-undo"></i></a>
                                    <?php endif; ?>
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

<?php foreach($prestamos_data as $p): 
    $styleM = getAdminCategoryStyle($p['categoria']);
?>
<div class="modal fade" id="modalDetalle<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Solicitud #<?php echo $p['id']; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="text-muted small d-block">Usuario Solicitante:</label>
                    <span class="fw-bold"><?php echo $p['usuario_nombre']; ?></span>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Equipo/Espacio:</label>
                    <i class="fas <?php echo $styleM['icon']; ?> <?php echo $styleM['color']; ?> me-1"></i>
                    <strong><?php echo $p['equipo_nombre']; ?></strong> (<?php echo $p['equipo_codigo']; ?>)
                </div>
                <div class="p-3 bg-light rounded mb-3">
                    <label class="text-muted small d-block mb-1">Motivo del préstamo:</label>
                    <p class="mb-0 italic"><?php echo nl2br(htmlspecialchars($p['proposito'])); ?></p>
                </div>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <small class="text-muted d-block">Inicio</small>
                        <span class="fw-bold text-success"><?php echo date('d/m/Y H:i', strtotime($p['fecha_inicio'])); ?></span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Fin esperado</small>
                        <span class="fw-bold text-danger"><?php echo date('d/m/Y H:i', strtotime($p['fecha_fin'])); ?></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>