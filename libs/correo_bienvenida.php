<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'libs/PHPMailer/PHPMailer.php';
require_once 'libs/PHPMailer/SMTP.php';
require_once 'libs/PHPMailer/Exception.php';

function enviarCorreoBienvenida($correoDestino, $nombreCompleto, $cedula) {
    $primerNombre = explode(' ', $nombreCompleto)[0];
    $primerApellido = explode(' ', $_POST['apellido'])[0]; // Puedes pasarlo también como parámetro si prefieres

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';         // Cambiar si usas otro proveedor
        $mail->SMTPAuth   = true;
        $mail->Username = 'incitec13@gmail.com';
        $mail->Password = 'lfrkhywtrygiqnzw'; // 👈 clave de aplicación copiada
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom('TUCORREO@gmail.com', 'Sistema MenuMVC');
        $mail->addAddress($correoDestino, $nombreCompleto);

        // Contenido del mensaje
        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido al Sistema';

        $mail->Body = '
        <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <div style="background-color: #2c3e50; color: white; padding: 20px; border-radius: 10px 10px 0 0;">
                <h2 style="margin: 0;">¡Bienvenido a MenuMVC!</h2>
            </div>

            <div style="padding: 20px; background-color: white;">
                <p>Hola <strong>' . $primerNombre . ' ' . $primerApellido . '</strong>,</p>
                <p>Nos complace darte la bienvenida al sistema <strong>MenuMVC</strong>.</p>

                <p><strong>Tus credenciales de acceso:</strong></p>
                <ul style="list-style: none; padding-left: 0;">
                    <li><strong>Correo:</strong> ' . $correoDestino . '</li>
                    <li><strong>Contraseña:</strong> ' . $cedula . '</li>
                </ul>

                <p style="color: #e74c3c;"><strong>Importante:</strong> Debes cambiar tu contraseña al ingresar por primera vez para proteger tu cuenta.</p>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="http://localhost:4060/MenuMVC/" target="_blank"
                    style="background-color: #3498db; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Acceder al sistema
                    </a>
                </div>
            </div>

            <div style="background-color: #ecf0f1; padding: 10px 20px; border-radius: 0 0 10px 10px; font-size: 12px; color: #7f8c8d;">
                <p>Este correo fue generado automáticamente. Por favor no lo respondas.</p>
                <p>&copy; ' . date('Y') . ' Sistema MenuMVC</p>
            </div>
        </div>
        ';


        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
