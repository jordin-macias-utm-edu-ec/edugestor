<?php
// user/historial.php
require_once '../includes/init.php';
redirectIfNotLoggedIn();

$conn = getConnection();
$user_id = $_SESSION['user_id'];
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
                <h2><i class="fas fa-history"></i> Mi Historial de Préstamos</h2>
                <p class="text-muted">Aquí puedes ver todos los préstamos que has solicitado.</p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Mis Solicitudes</h5>
                    </div>
                    <div class="card-body">
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
                        
                        // Guardamos los datos en un array para usarlos después en los modales
                        $prestamos_data = [];
                        
                        if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>#ID</th>
                                            <th>Equipo</th>
                                            <th>Período</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($prestamo = $result->fetch_assoc()): 
                                            $prestamos_data[] = $prestamo; // Guardamos cada fila para el modal
                                            $estado_class = '';
                                            switch($prestamo['estado']) {
                                                case 'pendiente': $estado_class = 'bg-warning'; break;
                                                case 'aprobado': $estado_class = 'bg-success'; break;
                                                case 'activo': $estado_class = 'bg-primary'; break;
                                                case 'completado': $estado_class = 'bg-secondary'; break;
                                                case 'rechazado': $estado_class = 'bg-danger'; break;
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $prestamo['id']; ?></td>
                                            <td>
                                                <strong><?php echo $prestamo['equipo_nombre']; ?></strong><br>
                                                <small class="text-muted"><?php echo $prestamo['equipo_codigo']; ?></small>
                                            </td>
                                            <td>
                                                <small>Desde: <?php echo $prestamo['fecha_inicio']; ?></small><br>
                                                <small>Hasta: <?php echo $prestamo['fecha_fin']; ?></small>
                                            </td>
                                            <td><span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($prestamo['estado']); ?></span></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalDetalle<?php echo $prestamo['id']; ?>">
                                                    <i class="fas fa-eye"></i> Detalles
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Aún no has realizado ninguna solicitud de préstamo.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4 mb-5">
            <div class="col-md-3">
                <div class="card text-white bg-primary shadow">
                    <div class="card-body">
                        <h5 class="card-title">Total Solicitudes</h5>
                        <h2><?php echo count($prestamos_data); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success shadow">
                    <div class="card-body">
                        <h5 class="card-title">Aprobados</h5>
                        <?php
                        $aprobados = 0;
                        foreach($prestamos_data as $p) if($p['estado'] == 'aprobado') $aprobados++;
                        ?>
                        <h2><?php echo $aprobados; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning shadow">
                    <div class="card-body">
                        <h5 class="card-title">Pendientes</h5>
                        <?php
                        $pendientes = 0;
                        foreach($prestamos_data as $p) if($p['estado'] == 'pendiente') $pendientes++;
                        ?>
                        <h2><?php echo $pendientes; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info shadow">
                    <div class="card-body">
                        <h5 class="card-title">Activos</h5>
                        <?php
                        $activos = 0;
                        foreach($prestamos_data as $p) if($p['estado'] == 'activo') $activos++;
                        ?>
                        <h2><?php echo $activos; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach($prestamos_data as $p): ?>
    <div class="modal fade" id="modalDetalle<?php echo $p['id']; ?>" tabindex="-1" aria-labelledby="label<?php echo $p['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="label<?php echo $p['id']; ?>">Detalles del Préstamo #<?php echo $p['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Equipo:</strong> <?php echo $p['equipo_nombre']; ?> (<?php echo $p['equipo_codigo']; ?>)</p>
                    <p><strong>Período:</strong> Del <?php echo $p['fecha_inicio']; ?> al <?php echo $p['fecha_fin']; ?></p>
                    <p><strong>Estado:</strong> <span class="badge bg-secondary"><?php echo ucfirst($p['estado']); ?></span></p>
                    <hr>
                    <p><strong>Propósito:</strong><br><?php echo nl2br(htmlspecialchars($p['proposito'])); ?></p>
                    <hr>
                    <p class="text-muted small">Solicitado el: <?php echo $p['fecha_solicitud']; ?></p>
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
$stmt->close();
$conn->close(); 
?>