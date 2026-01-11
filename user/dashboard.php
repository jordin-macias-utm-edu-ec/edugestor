<?php
// user/dashboard.php
require_once '../includes/init.php'; 
redirectIfNotLoggedIn();

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];

/**
 * Función auxiliar para obtener el icono y color según categoría
 */
function getCategoryStyle($categoria) {
    switch ($categoria) {
        case 'Tecnología':
            return ['icon' => 'fa-laptop', 'color' => 'text-primary', 'bg' => 'bg-primary-subtle'];
        case 'Laboratorio':
            return ['icon' => 'fa-flask', 'color' => 'text-success', 'bg' => 'bg-success-subtle'];
        case 'Espacios':
            return ['icon' => 'fa-building', 'color' => 'text-info', 'bg' => 'bg-info-subtle'];
        default:
            return ['icon' => 'fa-box', 'color' => 'text-secondary', 'bg' => 'bg-light'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .equip-card { border-radius: 12px; border: 1px solid #eee; transition: all 0.3s; }
        .equip-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: #0d6efd; }
        .category-icon-box { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; margin-bottom: 10px; }
        .category-pill { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
        .list-group-item { transition: background 0.2s; }
        .list-group-item:hover { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-0">Hola, <?php echo htmlspecialchars($user_nombre); ?> 👋</h2>
                    <p class="text-muted">¿Qué recurso necesitas utilizar hoy?</p>
                </div>
                <a href="solicitar.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>Nueva Solicitud
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-th-large text-primary me-2"></i>Recursos Disponibles</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT * FROM equipos WHERE estado = 'disponible' ORDER BY id DESC LIMIT 4";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0): ?>
                            <div class="row row-cols-1 row-cols-md-2 g-3">
                                <?php while($row = $result->fetch_assoc()): 
                                    $style = getCategoryStyle($row['categoria']);
                                ?>
                                <div class="col">
                                    <div class="card equip-card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div class="category-icon-box <?php echo $style['bg']; ?> <?php echo $style['color']; ?>">
                                                    <i class="fas <?php echo $style['icon']; ?> fa-lg"></i>
                                                </div>
                                                <span class="badge bg-success-subtle text-success align-self-start" style="font-size: 0.6rem;">DISPONIBLE</span>
                                            </div>
                                            
                                            <span class="category-pill text-muted small"><?php echo htmlspecialchars($row['categoria']); ?></span>
                                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($row['nombre']); ?></h6>
                                            
                                            <p class="text-muted small mb-3">
                                                <i class="fas fa-map-marker-alt me-1 text-danger"></i> <?php echo htmlspecialchars($row['ubicacion']); ?>
                                            </p>
                                            
                                            <a href="solicitar.php?equipo_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary w-100 rounded-3">
                                                Reservar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="text-center mt-4">
                                <a href="solicitar.php" class="btn btn-link text-decoration-none small fw-bold">Ver catálogo completo <i class="fas fa-arrow-right"></i></a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay recursos libres por ahora.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-check text-warning me-2"></i>Mis Reservas</h5>
                    </div>
                    <div class="card-body px-0">
                        <?php
                        $stmt = $conn->prepare("SELECT p.*, e.nombre as equipo_nombre, e.categoria 
                                              FROM prestamos p 
                                              JOIN equipos e ON p.equipo_id = e.id 
                                              WHERE p.usuario_id = ? AND p.estado IN ('aprobado', 'activo', 'pendiente')
                                              ORDER BY p.id DESC");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($row = $result->fetch_assoc()): 
                                    $style = getCategoryStyle($row['categoria']);
                                    $status_class = ($row['estado'] == 'pendiente') ? 'bg-warning text-dark' : 'bg-primary';
                                ?>
                                <div class="list-group-item border-0 px-4 mb-2">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas <?php echo $style['icon']; ?> <?php echo $style['color']; ?> me-3"></i>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 fw-bold small"><?php echo htmlspecialchars($row['equipo_nombre']); ?></h6>
                                            <small class="text-muted" style="font-size: 0.7rem;">
                                                Expira: <?php echo date('d/m H:i', strtotime($row['fecha_fin'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge <?php echo $status_class; ?> rounded-pill" style="font-size: 0.55rem;"><?php echo strtoupper($row['estado']); ?></span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted small px-3">No tienes actividades pendientes.</p>
                                <a href="historial.php" class="btn btn-sm btn-light border text-muted">Ver historial</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>