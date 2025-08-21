<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// RUTAS ABSOLUTAS (no cambies si tienes esta estructura)
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

/**
 * $tele (opcional): [
 *   'meeting_url' => 'https://meet.jit.si/...',
 *   'triage_url'  => 'https://tu-dominio/tele/triage.php?token=...'
 * ]
 */
function enviarCorreoCita($correoDestino, $nombrePaciente, $datosCita, $creadoPor, $tele = null) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'incitec13@gmail.com';
        $mail->Password   = 'lfrkhywtrygiqnzw'; // Clave de aplicación
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('incitec13@gmail.com', 'Sistema Hospital');
        $mail->addAddress($correoDestino, $nombrePaciente);
        $mail->isHTML(true);
        $mail->Subject = 'Cita médica registrada';

        // Variables con valores seguros
        $fechaHora    = isset($datosCita['fecha']) ? date("d/m/Y H:i", strtotime($datosCita['fecha'])) : 'S/F';
        $especialidad = $datosCita['especialidad'] ?? 'No especificado';
        $medico       = $datosCita['medico'] ?? 'No asignado';
        $tipoCita     = $datosCita['tipo_cita'] ?? 'No especificado';
        $prioridad    = $datosCita['prioridad'] ?? 'No especificada';
        $motivo       = $datosCita['motivo'] ?? 'Sin motivo registrado';

        // Bloque adicional SOLO si es telecita
        $bloqueTele = '';
        if (is_array($tele)) {
            $meeting = htmlspecialchars($tele['meeting_url'] ?? '', ENT_QUOTES, 'UTF-8');
            $triage  = htmlspecialchars($tele['triage_url']  ?? '', ENT_QUOTES, 'UTF-8');

            if ($meeting || $triage) {
                $bloqueTele = "
                <div style='margin-top:20px;padding:15px;background:#eef7ff;border-radius:8px;border:1px solid #cfe8ff'>
                    <h3 style='margin:0 0 10px 0;'>Consulta en línea</h3>"
                    . ($triage ? "<p style='margin:6px 0;'>📝 Completa tu triaje aquí: <a href='{$triage}' target='_blank'>Formulario de triaje</a></p>" : "") .
                    ($meeting ? "<p style='margin:6px 0;'>🎥 Videollamada: <a href='{$meeting}' target='_blank'>{$meeting}</a></p>" : "") .
                    "<p style='font-size:12px;color:#555;margin-top:8px'>
                      Recomendación: entra 5 minutos antes, y verifica micrófono y cámara con antelación.
                    </p>
                </div>";
            }
        }

        // Diseño
        $mail->Body = "
        <div style='max-width:600px;margin:0 auto;font-family:Arial,sans-serif;background:#f4f6f9;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);'>
            <div style='background:#2c3e50;color:#fff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>📅 Cita médica registrada</h2>
            </div>

            <div style='padding:30px;background:#ffffff;'>
                <p style='font-size:16px;margin-bottom:10px;'><strong>👤 Paciente:</strong> {$nombrePaciente}</p>
                <p style='font-size:16px;margin-bottom:10px;'><strong>🏥 Especialidad:</strong> {$especialidad}</p>
                <p style='font-size:16px;margin-bottom:10px;'><strong>👨‍⚕️ Médico:</strong> {$medico}</p>
                <p style='font-size:16px;margin-bottom:10px;'><strong>🕒 Fecha y hora:</strong> {$fechaHora}</p>
                <p style='font-size:16px;margin-bottom:10px;'><strong>📄 Tipo:</strong> {$tipoCita}</p>
                <p style='font-size:16px;margin-bottom:10px;'><strong>🚦 Prioridad:</strong> {$prioridad}</p>
                <p style='font-size:16px;margin-bottom:20px;'><strong>📝 Motivo:</strong> {$motivo}</p>

                {$bloqueTele}

                <div style='border-top:1px solid #ccc;padding-top:15px;margin-top:20px;'>
                    <p style='font-size:14px;color:#555;'><strong>✉️ Registrado por:</strong> {$creadoPor}</p>
                </div>
            </div>

            <div style='background:#ecf0f1;padding:15px;text-align:center;font-size:13px;color:#7f8c8d;'>
                Este correo ha sido generado automáticamente. No respondas a este mensaje.<br>
                &copy; " . date('Y') . " Sistema Hospital
            </div>
        </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de cita: " . $mail->ErrorInfo);
        return false;
    }
}
