<?php
// includes/email_config.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// __DIR__ es la carpeta 'includes'. 
require __DIR__ . '/../libs/PHPMailer/Exception.php';
require __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require __DIR__ . '/../libs/PHPMailer/SMTP.php';

function enviarCorreoNotificacion($destinatario, $nombreUsuario, $asunto, $cuerpoHtml) {
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURACIÓN DEL SERVIDOR SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // CORREO DE GMAIL
        $mail->Username   = 'jordin1517@gmail.com'; 
        
        // CLAVE DE 16 LETRAS
        $mail->Password   = 'cpgk ndim lhyh rvwm'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- QUIÉN ENVÍA EL CORREO ---
        $mail->setFrom('jordin1517@gmail.com', 'EduGestor - Sistema de Préstamos');
        
        // --- QUIÉN RECIBE ---
        $mail->addAddress($destinatario, $nombreUsuario);

        // --- CONTENIDO DEL CORREO ---
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Si hay un error, lo guarda en un log para que lo revises
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}