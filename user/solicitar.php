<?php
// user/solicitar.php
require_once '../includes/init.php';
redirectIfNotLoggedIn();

$conn = getConnection();
$mensaje = '';
$error = '';

// Obtener equipos disponibles
$equipos_result = $conn->query("SELECT * FROM equipos WHERE estado = 'disponible' ORDER BY nombre");

// Procesar solicitud de préstamo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar'])) {
    $equipo_id = intval($_POST['equipo_id']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $proposito = $_POST['proposito'];
    
    // Validar fechas
    $hoy = date('Y-m-d');
    if ($fecha_inicio < $hoy) {
        $error = "La fecha de inicio no puede ser anterior a hoy.";
    } elseif ($fecha_fin <= $fecha_inicio) {
        $error = "La fecha de fin debe ser posterior a la fecha de inicio.";
    } else {
        // Verificar que el equipo sigue disponible
        $stmt_check = $conn->prepare("SELECT estado FROM equipos WHERE id = ?");
        $stmt_check->bind_param("i", $equipo_id);
        $stmt_check->execute();
        $stmt_check->bind_result($estado_equipo);
        $stmt_check->fetch();
        $stmt_check->close();
        
        if ($estado_equipo != 'disponible') {
            $error = "El equipo seleccionado ya no está disponible.";
        } else {
            // Insertar solicitud
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO prestamos (usuario_id, equipo_id, fecha_inicio, fecha_fin, proposito) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $user_id, $equipo_id, $fecha_inicio, $fecha_fin, $proposito);
            
            if ($stmt->execute()) {
                $mensaje = "Solicitud de préstamo enviada correctamente. Espera la aprobación del administrador.";
            } else {
                $error = "Error al enviar la solicitud: " . $conn->error;
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .equipo-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .equipo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .equipo-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fas fa-plus-circle"></i> Solicitar Nuevo Préstamo</h2>
                <p class="text-muted">Selecciona un equipo disponible y especifica las fechas de préstamo.</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulario de solicitud -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Formulario de Solicitud</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formSolicitud">
                            <!-- Equipo seleccionado (oculto) -->
                            <input type="hidden" name="equipo_id" id="equipo_id" required>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="proposito" class="form-label">Propósito / Justificación *</label>
                                <textarea class="form-control" id="proposito" name="proposito" 
                                          rows="3" placeholder="Describe para qué necesitas el equipo..." required></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Nota:</strong> Tu solicitud será revisada por un administrador. 
                                Recibirás una notificación cuando sea aprobada o rechazada.
                            </div>
                            
                            <button type="submit" name="solicitar" class="btn btn-primary" id="btnSolicitar" disabled>
                                <i class="fas fa-paper-plane"></i> Enviar Solicitud
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al Dashboard
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Lista de equipos disponibles -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Equipos Disponibles (<?php echo $equipos_result->num_rows; ?>)</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if ($equipos_result->num_rows > 0): ?>
                            <div class="row" id="listaEquipos">
                                <?php while($equipo = $equipos_result->fetch_assoc()): ?>
                                <div class="col-12 mb-3">
                                    <div class="card equipo-card" 
                                         data-id="<?php echo $equipo['id']; ?>"
                                         data-nombre="<?php echo htmlspecialchars($equipo['nombre']); ?>"
                                         data-codigo="<?php echo htmlspecialchars($equipo['codigo']); ?>">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $equipo['nombre']; ?></h6>
                                            <p class="card-text mb-1">
                                                <small>Código: <?php echo $equipo['codigo']; ?></small><br>
                                                <small>Categoría: 
                                                    <?php 
                                                    $categorias = [
                                                        'audiovisual' => 'Audiovisual',
                                                        'computacion' => 'Computación',
                                                        'laboratorio' => 'Laboratorio',
                                                        'otros' => 'Otros'
                                                    ];
                                                    echo $categorias[$equipo['categoria']] ?? 'Otros';
                                                    ?>
                                                </small><br>
                                                <small>Ubicación: <?php echo $equipo['ubicacion']; ?></small>
                                            </p>
                                            <div class="text-end">
                                                <span class="badge bg-success">Disponible</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No hay equipos disponibles en este momento.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Seleccionar equipo
        document.querySelectorAll('.equipo-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remover selección anterior
                document.querySelectorAll('.equipo-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Seleccionar esta tarjeta
                this.classList.add('selected');
                
                // Actualizar campo oculto
                document.getElementById('equipo_id').value = this.dataset.id;
                
                // Habilitar botón de solicitud
                document.getElementById('btnSolicitar').disabled = false;
                
                // Actualizar título del formulario opcional
                const equipoNombre = document.createElement('small');
                equipoNombre.className = 'text-muted';
                equipoNombre.textContent = `Equipo seleccionado: ${this.dataset.nombre} (${this.dataset.codigo})`;
                
                const formTitle = document.querySelector('.card-header h5');
                if (!formTitle.querySelector('.equipo-seleccionado')) {
                    const existing = formTitle.querySelector('.equipo-seleccionado');
                    if (existing) existing.remove();
                    
                    const newInfo = equipoNombre.cloneNode(true);
                    newInfo.className = 'equipo-seleccionado d-block mt-1 text-muted';
                    formTitle.appendChild(newInfo);
                }
            });
        });
        
        // Validar fechas
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            const finInput = document.getElementById('fecha_fin');
            finInput.min = this.value;
            
            if (finInput.value && finInput.value < this.value) {
                finInput.value = this.value;
            }
        });
        
        // Validar formulario antes de enviar
        document.getElementById('formSolicitud').addEventListener('submit', function(e) {
            const equipoId = document.getElementById('equipo_id').value;
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const proposito = document.getElementById('proposito').value.trim();
            
            if (!equipoId) {
                e.preventDefault();
                alert('Por favor, selecciona un equipo.');
                return false;
            }
            
            if (!fechaInicio || !fechaFin) {
                e.preventDefault();
                alert('Por favor, completa las fechas.');
                return false;
            }
            
            if (!proposito) {
                e.preventDefault();
                alert('Por favor, describe el propósito del préstamo.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>