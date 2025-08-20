<?php
// libs/correo_bienvenida.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Ajuste CLAVE: __DIR__ ya apunta a /var/www/html/libs
 * Por eso la ruta correcta a PHPMailer es SIN repetir "/libs".
 */
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

/**
 * Envía correo de bienvenida.
 *
 * @param string $correoDestino
 * @param string $nombre      Primer nombre o nombre completo (se usa el primer token)
 * @param string $apellido    Primer apellido (pásalo desde la API, no usar $_POST)
 * @param string $cedula      Contraseña temporal
 * @return bool
 */
function enviarCorreoBienvenida($correoDestino, $nombre, $apellido, $cedula) {
    $primerNombre   = explode(' ', trim($nombre))[0] ?: $nombre;
    $primerApellido = explode(' ', trim($apellido))[0] ?: $apellido;

    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // ⚠️ Usa el MISMO remitente real que autenticas
        $mail->Username   = 'incitec13@gmail.com';
        $mail->Password   = 'lfrkhywtrygiqnzw'; // app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Remitente/Destinatario
        $mail->setFrom('incitec13@gmail.com', 'Sistema MenuMVC');
        $mail->addAddress($correoDestino, $primerNombre . ' ' . $primerApellido);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido a MenuMVC';

        $mail->Body = '
        <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <div style="background-color: #2c3e50; color: white; padding: 20px; border-radius: 10px 10px 0 0;">
                <h2 style="margin: 0;">¡Bienvenido a MenuMVC!</h2>
            </div>

            <div style="padding: 20px; background-color: white;">
                <p>Hola <strong>' . htmlspecialchars($primerNombre . ' ' . $primerApellido) . '</strong>,</p>
                <p>Nos complace darte la bienvenida al sistema <strong>MenuMVC</strong>.</p>

                <p><strong>Tus credenciales de acceso:</strong></p>
                <ul style="list-style: none; padding-left: 0;">
                    <li><strong>Correo:</strong> ' . htmlspecialchars($correoDestino) . '</li>
                    <li><strong>Contraseña temporal:</strong> ' . htmlspecialchars($cedula) . '</li>
                </ul>

                <p style="color: #e74c3c;"><strong>Importante:</strong> Debes cambiar tu contraseña al ingresar por primera vez.</p>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="https://menu-mvc.onrender.com/" target="_blank"
                    style="background-color: #3498db; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Acceder al sistema
                    </a>
                </div>
            </div>

            <div style="background-color: #ecf0f1; padding: 10px 20px; border-radius: 0 0 10px 10px; font-size: 12px; color: #7f8c8d;">
                <p>Este correo fue generado automáticamente. Por favor no lo respondas.</p>
                <p>&copy; ' . date('Y') . ' Sistema MenuMVC</p>
            </div>
        </div>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Loguea sin romper el flujo de la API
        error_log("Error al enviar correo de bienvenida: " . $mail->ErrorInfo);
        return false;
    }
}
