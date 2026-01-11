<?php
// admin/includes/sidebar.php
?>
<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" style="background: linear-gradient(180deg, #2c3e50 0%, #1a2530 100%);">
    <div class="position-sticky pt-3">
        <h4 class="text-white text-center mb-4">
            <i class="fas fa-tools"></i> <?php echo APP_NAME; ?>
        </h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'equipos.php' ? 'active' : ''; ?>" href="equipos.php">
                    <i class="fas fa-laptop"></i> Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'prestamos.php' ? 'active' : ''; ?>" href="prestamos.php">
                    <i class="fas fa-exchange-alt"></i> Préstamos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                    <i class="fas fa-users"></i> Usuarios
                </a>
            </li>
            <hr class="bg-light">
            <li class="nav-item">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>
</nav>