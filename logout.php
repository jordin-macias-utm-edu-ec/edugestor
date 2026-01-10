<?php
require_once 'includes/config.php';
session_destroy();
header('Location: ' . APP_URL . '/index.php');
exit();
?>