<?php
// includes/flash.php

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

function showFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        
        $alertClass = ($flash['type'] == 'error') ? 'alert-danger' : 'alert-' . $flash['type'];
        
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show shadow-sm" role="alert">';
        echo '  <i class="fas ' . ($flash['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') . ' me-2"></i>';
        echo    $flash['message'];
        echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}
?>