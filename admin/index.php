<?php
// admin/index.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if ($_SESSION['user_rol'] != 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}

$conn = getConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h4 class="text-white text-center mb-4">
                        <i class="fas fa-tools"></i> <?php echo APP_NAME; ?>
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="equipos.php">
                                <i class="fas fa-laptop"></i> Equipos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="prestamos.php">
                                <i class="fas fa-exchange-alt"></i> Préstamos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
                                <i class="fas fa-users"></i> Usuarios
                            </a>
                        </li>
                        <hr class="bg-light">
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Navbar superior -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Panel de Administración</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_nombre']; ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card disponible">
                            <div class="card-body">
                                <h5 class="card-title">Disponibles</h5>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM equipos WHERE estado = 'disponible'";
                                $result = $conn->query($sql);
                                $row = $result->fetch_assoc();
                                ?>
                                <h2 class="text-success"><?php echo $row['total']; ?></h2>
                                <p class="card-text">Equipos disponibles para préstamo</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card prestado">
                            <div class="card-body">
                                <h5 class="card-title">Prestados</h5>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM equipos WHERE estado = 'prestado'";
                                $result = $conn->query($sql);
                                $row = $result->fetch_assoc();
                                ?>
                                <h2 class="text-danger"><?php echo $row['total']; ?></h2>
                                <p class="card-text">Equipos actualmente prestados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card mantenimiento">
                            <div class="card-body">
                                <h5 class="card-title">Pendientes</h5>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM prestamos WHERE estado = 'pendiente'";
                                $result = $conn->query($sql);
                                $row = $result->fetch_assoc();
                                ?>
                                <h2 class="text-warning"><?php echo $row['total']; ?></h2>
                                <p class="card-text">Solicitudes por revisar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Equipos</h5>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM equipos";
                                $result = $conn->query($sql);
                                $row = $result->fetch_assoc();
                                ?>
                                <h2><?php echo $row['total']; ?></h2>
                                <p class="card-text">Equipos en inventario</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Préstamos recientes -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history"></i> Préstamos Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $sql = "SELECT p.id, u.nombre as usuario, e.nombre as equipo, 
                                               p.fecha_inicio, p.fecha_fin, p.estado
                                        FROM prestamos p
                                        JOIN usuarios u ON p.usuario_id = u.id
                                        JOIN equipos e ON p.equipo_id = e.id
                                        ORDER BY p.fecha_solicitud DESC LIMIT 5";
                                $result = $conn->query($sql);
                                
                                if ($result->num_rows > 0) {
                                    echo '<table class="table table-hover">';
                                    echo '<thead><tr><th>Usuario</th><th>Equipo</th><th>Fecha</th><th>Estado</th></tr></thead>';
                                    echo '<tbody>';
                                    while($row = $result->fetch_assoc()) {
                                        $estado_class = '';
                                        switch($row['estado']) {
                                            case 'pendiente': $estado_class = 'warning'; break;
                                            case 'aprobado': $estado_class = 'success'; break;
                                            case 'rechazado': $estado_class = 'danger'; break;
                                        }
                                        echo '<tr>';
                                        echo '<td>' . $row['usuario'] . '</td>';
                                        echo '<td>' . $row['equipo'] . '</td>';
                                        echo '<td>' . $row['fecha_inicio'] . ' al ' . $row['fecha_fin'] . '</td>';
                                        echo '<td><span class="badge bg-' . $estado_class . '">' . $row['estado'] . '</span></td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody></table>';
                                } else {
                                    echo '<p class="text-muted">No hay préstamos registrados.</p>';
                                }
                                ?>
                                <a href="prestamos.php" class="btn btn-outline-primary">Ver todos los préstamos</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>