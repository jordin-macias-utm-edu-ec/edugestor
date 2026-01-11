<?php
// includes/flash.php

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function showFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        
        $alert_class = '';
        switch($flash['type']) {
            case 'success': $alert_class = 'alert-success'; break;
            case 'error': $alert_class = 'alert-danger'; break;
            case 'warning': $alert_class = 'alert-warning'; break;
            case 'info': $alert_class = 'alert-info'; break;
        }
        
        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}
?>