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

// Procesar acciones sobre préstamos (Aprobar, Rechazar, Completar)
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $accion = $_GET['accion'];
    
    if (in_array($accion, ['aprobar', 'rechazar', 'completar'])) {
        $estado_nuevo = ($accion == 'aprobar') ? 'aprobado' : 
                       (($accion == 'rechazar') ? 'rechazado' : 'completado');
        
        // Lógica de actualización de equipos según la acción
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

// Obtener todos los préstamos de la base de datos
$sql = "SELECT p.*, e.nombre as equipo_nombre, e.codigo as equipo_codigo, u.nombre as usuario_nombre, u.email as usuario_email 
        FROM prestamos p 
        JOIN equipos e ON p.equipo_id = e.id 
        JOIN usuarios u ON p.usuario_id = u.id 
        ORDER BY p.fecha_solicitud DESC";
$result = $conn->query($sql);

// Guardaremos los datos para los modales al final
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Préstamos</h1>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>Equipo</th>
                                            <th>Período</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($prestamo = $result->fetch_assoc()): 
                                            $prestamos_data[] = $prestamo; // Guardamos para los modales
                                            $badge_class = [
                                                'pendiente' => 'bg-warning',
                                                'aprobado' => 'bg-success',
                                                'rechazado' => 'bg-danger',
                                                'completado' => 'bg-secondary',
                                                'activo' => 'bg-primary'
                                            ];
                                        ?>
                                        <tr>
                                            <td>#<?php echo $prestamo['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($prestamo['usuario_nombre']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($prestamo['usuario_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($prestamo['equipo_nombre']); ?></td>
                                            <td>
                                                <small>Del: <?php echo $prestamo['fecha_inicio']; ?></small><br>
                                                <small>Al: <?php echo $prestamo['fecha_fin']; ?></small>
                                            </td>
                                            <td><span class="badge <?php echo $badge_class[$prestamo['estado']] ?? 'bg-info'; ?>"><?php echo ucfirst($prestamo['estado']); ?></span></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $prestamo['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($prestamo['estado'] == 'pendiente'): ?>
                                                        <a href="?accion=aprobar&id=<?php echo $prestamo['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Aprobar?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="?accion=rechazar&id=<?php echo $prestamo['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Rechazar?')">
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
                            <div class="alert alert-info">No se encontraron préstamos.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php foreach($prestamos_data as $p): ?>
    <div class="modal fade" id="modalDetalle<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Detalles del Préstamo #<?php echo $p['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($p['usuario_nombre']); ?></p>
                    <p><strong>Correo:</strong> <?php echo htmlspecialchars($p['usuario_email']); ?></p>
                    <p><strong>Equipo:</strong> <?php echo htmlspecialchars($p['equipo_nombre']); ?> (<?php echo htmlspecialchars($p['equipo_codigo']); ?>)</p>
                    <p><strong>Estado:</strong> <span class="badge bg-secondary"><?php echo ucfirst($p['estado']); ?></span></p>
                    <hr>
                    <p><strong>Propósito:</strong><br><?php echo nl2br(htmlspecialchars($p['proposito'])); ?></p>
                    <hr>
                    <small class="text-muted">Solicitado el: <?php echo $p['fecha_solicitud']; ?></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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