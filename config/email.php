<?php
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// FUNCIÓN PARA VERIFICACIÓN DE EMAIL (REGISTRO)
function enviarEmailVerificacion($email, $token, $nombre) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aurasport.mc@gmail.com';
        $mail->Password = 'mxfa yaiw nlzc byur';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Configuración de caracteres
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom('aurasport.mc@gmail.com', 'Aura Sport');
        $mail->addAddress($email, $nombre);
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu cuenta - Aura Sport';
        
        // URL CORREGIDA
        $enlace_verificacion = "http://" . $_SERVER['HTTP_HOST'] . "/registro.php?token=" . $token;
        
        $mail->Body = "
        <html>
        <body>
            <h2>¡Bienvenido/a, $nombre!</h2>
            <p>Gracias por registrarte en Aura Sport.</p>
            <p>Para activar tu cuenta, haz clic en el siguiente enlace:</p>
            <p><a href='$enlace_verificacion' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verificar Cuenta</a></p>
            <p><strong>Este enlace expirará en 24 horas.</strong></p>
            <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
            <p>$enlace_verificacion</p>
            <p><em>Si no te registraste en nuestro sitio, por favor ignora este email.</em></p>
        </body>
        </html>
        ";
        
        // Versión alternativa en texto plano
        $mail->AltBody = "Verifica tu cuenta en Aura Sport\n\nHola $nombre,\n\nPara activar tu cuenta, visita: $enlace_verificacion\n\nEste enlace expira en 24 horas.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $mail->ErrorInfo);
        return false;
    }
}

// FUNCIÓN PARA RECUPERACIÓN DE CONTRASEÑA
function enviarEmailRecuperacion($email, $token, $nombre) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aurasport.mc@gmail.com';
        $mail->Password = 'mxfa yaiw nlzc byur';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Configuración de caracteres
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom('aurasport.mc@gmail.com', 'Aura Sport');
        $mail->addAddress($email, $nombre);
        
        // Contenido del email de recuperación
        $mail->isHTML(true);
        $mail->Subject = 'Restablecer tu Contraseña - Aura Sport';
        
        // URL CORREGIDA para recuperación
        $enlace_recuperacion = "http://" . $_SERVER['HTTP_HOST'] . "/restablecer.php?token=" . $token;
        
        $mail->Body = "
        <html>
        <body>
            <h2>Hola, $nombre</h2>
            <p>Recibimos una solicitud para restablecer tu contraseña en Aura Sport.</p>
            <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
            <p><a href='$enlace_recuperacion' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Restablecer Contraseña</a></p>
            <p><strong>⚠️ Este enlace expirará en 1 hora.</strong></p>
            <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
            <p>$enlace_recuperacion</p>
            <p><em>Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.</em></p>
        </body>
        </html>
        ";
        
        // Versión alternativa en texto plano
        $mail->AltBody = "Restablecer contraseña - Aura Sport\n\nHola $nombre,\n\nPara restablecer tu contraseña, visita: $enlace_recuperacion\n\nEste enlace expira en 1 hora.\n\nSi no solicitaste este cambio, ignora este email.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar email recuperación: " . $mail->ErrorInfo);
        return false;
    }
}
?>