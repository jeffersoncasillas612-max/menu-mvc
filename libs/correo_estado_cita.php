<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

/**
 * Envía correo cuando cambia el estado de una cita.
 * $datosCita = [
 *   'fecha','especialidad','medico','tipo_cita','prioridad','origen',
 *   'estado_anterior','estado_nuevo'
 * ]
 */
function enviarCorreoCambioEstadoCita(string $correoDestino, string $nombrePaciente, array $datosCita, string $motivo = ''): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'incitec13@gmail.com';
        $mail->Password   = 'lfrkhywtrygiqnzw'; // Clave de app
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('incitec13@gmail.com', 'Sistema Hospital');
        $mail->addAddress($correoDestino, $nombrePaciente);
        $mail->isHTML(true);

        $estadoNuevo = $datosCita['estado_nuevo'] ?? '—';
        $estadoAntes = $datosCita['estado_anterior'] ?? '—';
        $mail->Subject = "Actualización de estado de tu cita: {$estadoNuevo}";

        $fechaHora    = !empty($datosCita['fecha']) ? date("d/m/Y H:i", strtotime($datosCita['fecha'])) : '—';
        $especialidad = $datosCita['especialidad'] ?? '—';
        $medico       = $datosCita['medico'] ?? '—';
        $tipoCita     = $datosCita['tipo_cita'] ?? '—';
        $prioridad    = $datosCita['prioridad'] ?? '—';
        $origen       = $datosCita['origen'] ?? '—';

        $motivoHtml = $motivo !== '' ? "<p style='margin:8px 0'><strong>Motivo:</strong> ".htmlentities($motivo)."</p>" : "";

        $mail->Body = "
        <div style='max-width:600px;margin:0 auto;font-family:Arial,sans-serif;background:#f4f6f9;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);'>
            <div style='background:#34495e;color:#fff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>🔄 Actualización de estado de tu cita</h2>
            </div>

            <div style='padding:24px;background:#ffffff;font-size:15px;'>
                <p>Hola <strong>{$nombrePaciente}</strong>,</p>
                <p>Tu cita cambió de estado <strong>{$estadoAntes}</strong> → <strong>{$estadoNuevo}</strong>.</p>
                {$motivoHtml}
                <div style='margin-top:12px;border-top:1px solid #eee;padding-top:12px;'>
                    <p style='margin:6px 0'><strong>Fecha y hora:</strong> {$fechaHora}</p>
                    <p style='margin:6px 0'><strong>Médico:</strong> {$medico}</p>
                    <p style='margin:6px 0'><strong>Especialidad:</strong> {$especialidad}</p>
                    <p style='margin:6px 0'><strong>Tipo:</strong> {$tipoCita}</p>
                    <p style='margin:6px 0'><strong>Prioridad:</strong> {$prioridad}</p>
                    <p style='margin:6px 0'><strong>Origen:</strong> {$origen}</p>
                </div>
            </div>

            <div style='background:#ecf0f1;padding:14px;text-align:center;font-size:12px;color:#7f8c8d;'>
                Este correo ha sido generado automáticamente. No respondas a este mensaje.<br>
                &copy; ".date('Y')." Sistema Hospital
            </div>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error correo cambio estado: " . $mail->ErrorInfo);
        return false;
    }
}
