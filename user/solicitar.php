<?php
// user/solicitar.php
require_once '../includes/init.php';
redirectIfNotLoggedIn();

$conn = getConnection();

/**
 * NOTA: Esta función se podría mover a includes/functions.php 
 * para ser 100% reutilizable en todo el sitio.
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

// Obtener equipos disponibles
$equipos_result = $conn->query("SELECT * FROM equipos WHERE estado = 'disponible' ORDER BY categoria, nombre");

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar'])) {
    $equipo_id = intval($_POST['equipo_id']);
    $fecha_inicio = $_POST['fecha_inicio']; 
    $fecha_fin = $_POST['fecha_fin'];
    $proposito = trim($_POST['proposito']);
    $hoy = date('Y-m-d H:i');
    
    if ($fecha_inicio < $hoy) {
        setFlash('error', "La fecha de inicio no puede ser anterior a la actual.");
    } elseif ($fecha_fin <= $fecha_inicio) {
        setFlash('error', "La fecha de entrega debe ser posterior al inicio.");
    } else {
        $stmt_check = $conn->prepare("SELECT estado FROM equipos WHERE id = ?");
        $stmt_check->bind_param("i", $equipo_id);
        $stmt_check->execute();
        $stmt_check->bind_result($estado_equipo);
        $stmt_check->fetch();
        $stmt_check->close();
        
        if ($estado_equipo != 'disponible') {
            setFlash('error', "El recurso seleccionado ya no está disponible.");
        } else {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO prestamos (usuario_id, equipo_id, fecha_inicio, fecha_fin, proposito, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmt->bind_param("iisss", $user_id, $equipo_id, $fecha_inicio, $fecha_fin, $proposito);
            
            if ($stmt->execute()) {
                // Preparar correo
                $asunto = "Confirmación: Solicitud de reserva recibida";
                $cuerpo = "
                <html>
                <body style='font-family: sans-serif;'>
                    <h2 style='color: #0d6efd;'>Hola " . htmlspecialchars($_SESSION['user_nombre']) . ",</h2>
                    <p>Tu solicitud de préstamo ha sido registrada exitosamente.</p>
                    <ul>
                        <li><strong>Recurso ID:</strong> $equipo_id</li>
                        <li><strong>Inicio:</strong> $fecha_inicio</li>
                        <li><strong>Entrega:</strong> $fecha_fin</li>
                    </ul>
                    <p>Te notificaremos cuando el administrador revise tu solicitud.</p>
                </body>
                </html>";

                enviarCorreoNotificacion($_SESSION['user_email'], $_SESSION['user_nombre'], $asunto, $cuerpo);
                
                // Éxito: Guardamos mensaje y redirigimos al historial
                setFlash('success', "¡Solicitud enviada! El administrador la revisará pronto.");
                header("Location: historial.php"); 
                exit();
            } else {
                setFlash('error', "Error en la base de datos: " . $conn->error);
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Préstamo - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .equipo-card { transition: all 0.3s; cursor: pointer; border-radius: 12px; border: 2px solid transparent; }
        .equipo-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .equipo-card.selected { border-color: #0d6efd; background-color: #f0f7ff; }
        .category-header { background: #f8f9fa; padding: 10px; border-radius: 8px; margin-top: 20px; font-weight: bold; color: #495057; border-left: 4px solid #0d6efd; }
        .recursos-scroll::-webkit-scrollbar { width: 6px; }
        .recursos-scroll::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/navbar.php'; // Usamos el Navbar global ?>
    
    <div class="container py-5">
        <h2 class="fw-bold mb-4"><i class="fas fa-calendar-plus text-primary"></i> Nueva Solicitud</h2>

        <?php showFlash(); // Aquí se mostrarán los errores o éxitos automáticamente ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <form method="POST" id="formSolicitud">
                            <input type="hidden" name="equipo_id" id="equipo_id" required>
                            
                            <div id="infoSeleccion" class="mb-4">
                                <div class="alert alert-info py-3 border-0 rounded-3">
                                    <i class="fas fa-mouse-pointer me-2"></i> Selecciona un recurso de la lista
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">FECHA INICIO</label>
                                    <input type="datetime-local" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">FECHA ENTREGA</label>
                                    <input type="datetime-local" class="form-control" name="fecha_fin" id="fecha_fin" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">PROPÓSITO</label>
                                <textarea class="form-control" name="proposito" rows="3" placeholder="¿Para qué necesitas el recurso?" required></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="solicitar" class="btn btn-primary btn-lg fw-bold shadow-sm" id="btnSolicitar" disabled>
                                    Enviar Solicitud
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <h5 class="fw-bold mb-3">Recursos Disponibles</h5>
                <div class="recursos-scroll" style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                    <?php 
                    $current_cat = "";
                    if ($equipos_result && $equipos_result->num_rows > 0): 
                        while($equipo = $equipos_result->fetch_assoc()): 
                            $style = getCategoryStyle($equipo['categoria']);
                            if ($current_cat != $equipo['categoria']): 
                                $current_cat = $equipo['categoria'];
                                echo "<div class='category-header mb-2'><i class='fas fa-tag me-2 opacity-50'></i>" . htmlspecialchars($current_cat) . "</div>";
                            endif;
                    ?>
                    <div class="card equipo-card border-0 shadow-sm mb-2" 
                         data-id="<?php echo $equipo['id']; ?>" 
                         data-nombre="<?php echo htmlspecialchars($equipo['nombre']); ?>">
                        <div class="card-body d-flex align-items-center p-2">
                            <div class="<?php echo $style['bg']; ?> <?php echo $style['color']; ?> p-3 rounded-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fas <?php echo $style['icon']; ?> fa-lg"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($equipo['nombre']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($equipo['codigo']); ?> | <?php echo htmlspecialchars($equipo['ubicacion']); ?></small>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-success-subtle text-success rounded-pill">Disponible</span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                            <p class="text-muted">No hay recursos disponibles.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function seleccionar(card) {
            document.querySelectorAll('.equipo-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('equipo_id').value = card.dataset.id;
            document.getElementById('btnSolicitar').disabled = false;
            
            document.getElementById('infoSeleccion').innerHTML = `
                <div class="alert alert-primary py-3 mb-0 border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <small class="d-block text-uppercase fw-bold opacity-75">Seleccionado</small>
                            <span class="fs-5 fw-bold">${card.dataset.nombre}</span>
                        </div>
                    </div>
                </div>`;
        }

        document.querySelectorAll('.equipo-card').forEach(card => {
            card.addEventListener('click', () => seleccionar(card));
        });

        // Configuración de fechas mínimas
        window.addEventListener('load', () => {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            const nowStr = now.toISOString().slice(0,16);
            document.getElementById('fecha_inicio').min = nowStr;
            document.getElementById('fecha_fin').min = nowStr;
        });
    </script>
</body>
</html>