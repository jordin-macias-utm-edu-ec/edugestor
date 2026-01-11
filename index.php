<?php
require_once 'includes/init.php';

// Si ya está logueado, mandarlo a su panel
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_rol'] == 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}

$error = '';
$success_message = '';

if (isset($_GET['registro']) && $_GET['registro'] == 'exitoso') {
    $success_message = '¡Registro exitoso! Ahora puedes iniciar sesión.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (authenticate($email, $password)) {
        if ($_SESSION['user_rol'] == 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit();
    } else {
        $error = 'Credenciales incorrectas.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --glass-bg: rgba(255, 255, 255, 0.92);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }

        .login-card {
            background: var(--glass-bg);
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            background: rgba(13, 110, 253, 0.05);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .brand-logo {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 10px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #444;
            margin-left: 5px;
        }

        /* Uso de input-group de Bootstrap  */
        .input-group-custom {
            background: white;
            border: 1px solid #ced4da;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .input-group-custom:focus-within {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
        }

        .input-group-custom .form-control {
            border: none !important;
            box-shadow: none !important;
            padding: 12px 15px;
            background: transparent;
        }

        .input-group-icon {
            padding-left: 15px;
            color: #6c757d;
        }

        .password-toggle {
            padding: 0 15px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #0d6efd;
        }

        .btn-login {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            background: #0d6efd;
            border: none;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <div class="brand-logo">
                    <i class="fas fa-tools"></i>
                </div>
                <h4 class="mb-0 fw-bold"><?php echo APP_NAME; ?></h4>
                <p class="text-muted small mb-0">Gestión de Préstamos Académicos</p>
            </div>
            
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small border-0 shadow-sm">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success py-2 small border-0 shadow-sm">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <div class="input-group-custom">
                            <i class="fas fa-envelope input-group-icon"></i>
                            <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock input-group-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            <div class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-login shadow-sm">
                        Ingresar <i class="fas fa-sign-in-alt ms-2"></i>
                    </button>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0 small text-muted">¿No tienes cuenta? 
                        <a href="register.php" class="text-primary fw-bold text-decoration-none">Regístrate aquí</a>
                    </p>
                </div>
            </div>
        </div>
        
        <p class="text-center text-white-50 mt-4 small">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v1.0
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lógica visualización de contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>