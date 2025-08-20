<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

function enviarCorreoRecuperacion($correoDestino, $nombreCompleto, $linkRecuperacion) {
    $primerNombre = explode(' ', $nombreCompleto)[0];

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'incitec13@gmail.com';       // Tu correo
        $mail->Password   = 'lfrkhywtrygiqnzw';           // Tu clave de aplicación
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('incitec13@gmail.com', 'Sistema MenuMVC');
        $mail->addAddress($correoDestino, $nombreCompleto);
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña - Sistema MenuMVC';

        $mail->Body = '
        <div style="max-width:600px;margin:0 auto;font-family:Arial;background:#f9f9f9;padding:30px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);">
            <div style="background-color:#e67e22;color:white;padding:20px;border-radius:10px 10px 0 0;">
                <h2 style="margin:0;">Recuperación de contraseña</h2>
            </div>

            <div style="padding:20px;background:white;">
                <p>Hola <strong>' . $primerNombre . '</strong>,</p>
                <p>Hemos recibido una solicitud para restablecer tu contraseña en el sistema <strong>MenuMVC</strong>.</p>

                <div style="text-align:center;margin:30px 0;">
                    <a href="' . $linkRecuperacion . '" target="_blank" style="background-color:#3498db;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;font-weight:bold;">Cambiar mi contraseña</a>
                </div>

                <p>Si tú no solicitaste este cambio, puedes ignorar este mensaje.</p>
                <p>Este enlace es válido solo por <strong>15 minutos</strong>.</p>
            </div>

            <div style="background-color:#ecf0f1;padding:10px 20px;border-radius:0 0 10px 10px;font-size:12px;color:#7f8c8d;">
                <p>Este correo fue generado automáticamente. Por favor, no respondas.</p>
                <p>&copy; ' . date('Y') . ' Sistema MenuMVC</p>
            </div>
        </div>
        ';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de recuperación: " . $mail->ErrorInfo);
        return false;
    }
}
