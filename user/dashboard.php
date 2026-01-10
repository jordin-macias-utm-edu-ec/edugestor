<?php
// user/dashboard.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
$conn = getConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tools"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="solicitar.php">
                            <i class="fas fa-plus-circle"></i> Nuevo Préstamo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="historial.php">
                            <i class="fas fa-history"></i> Mi Historial
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_nombre']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Bienvenido, <?php echo $_SESSION['user_nombre']; ?></h2>
                <p class="text-muted">Desde aquí puedes gestionar tus préstamos de equipos.</p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-laptop"></i> Equipos Disponibles</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT * FROM equipos WHERE estado = 'disponible' LIMIT 5";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            echo '<div class="row">';
                            while($row = $result->fetch_assoc()) {
                                echo '<div class="col-md-6 mb-3">';
                                echo '<div class="card h-100">';
                                echo '<div class="card-body">';
                                echo '<h6 class="card-title">' . $row['nombre'] . '</h6>';
                                echo '<p class="card-text"><small>Código: ' . $row['codigo'] . '</small></p>';
                                echo '<p class="card-text"><small>Ubicación: ' . $row['ubicacion'] . '</small></p>';
                                echo '</div>';
                                echo '<div class="card-footer">';
                                echo '<a href="solicitar.php?equipo_id=' . $row['id'] . '" class="btn btn-sm btn-primary">Solicitar</a>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<p class="text-muted">No hay equipos disponibles en este momento.</p>';
                        }
                        ?>
                        <a href="solicitar.php" class="btn btn-outline-primary mt-3">Ver todos los equipos</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock"></i> Mis Préstamos Activos</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $user_id = $_SESSION['user_id'];
                        $sql = "SELECT p.*, e.nombre as equipo_nombre 
                                FROM prestamos p 
                                JOIN equipos e ON p.equipo_id = e.id 
                                WHERE p.usuario_id = $user_id 
                                AND p.estado IN ('aprobado', 'activo')
                                ORDER BY p.fecha_inicio DESC";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            echo '<ul class="list-group">';
                            while($row = $result->fetch_assoc()) {
                                echo '<li class="list-group-item">';
                                echo '<h6>' . $row['equipo_nombre'] . '</h6>';
                                echo '<small>Del ' . $row['fecha_inicio'] . ' al ' . $row['fecha_fin'] . '</small><br>';
                                echo '<span class="badge bg-success">' . $row['estado'] . '</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-muted">No tienes préstamos activos.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>