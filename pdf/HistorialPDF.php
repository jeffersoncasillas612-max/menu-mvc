<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../models/Usuario.php';
require_once '../models/Historial.php';
require_once '../models/Cita.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$tipo = $_POST['tipo'] ?? '';
$valor = $_POST['valor'] ?? '';
$cedula_historial = $_POST['cedula_historial'] ?? '';

if (empty($tipo) || empty($valor) || empty($cedula_historial)) {
    echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
    exit;
}

// 📧 Obtener el correo de destino
if ($tipo === 'cedula') {
    $modeloUsuario = new Usuario();
    $correo = $modeloUsuario->obtenerCorreoPorCedula($valor);
    if (!$correo) {
        echo json_encode(['status' => 'error', 'msg' => 'No se encontró el correo para esta cédula.']);
        exit;
    }
} else {
    $correo = $valor;
}

// 📋 Obtener información completa del paciente
$modeloHistorial = new Historial();
$modeloCita = new Cita();

$datosPaciente = $modeloHistorial->obtenerHistorialCompleto($cedula_historial);
$infoPaciente = $modeloCita->buscarPacientePorCedula($cedula_historial);
if (!$datosPaciente || !$infoPaciente) {
    echo json_encode(['status' => 'error', 'msg' => 'No se encontró historial para enviar.']);
    exit;
}

$usuario_id = $infoPaciente['usu_id'];
$vacunas = $modeloCita->obtenerVacunas($usuario_id);
$citas   = $modeloCita->obtenerCitasPorPaciente($usuario_id);

// 🧾 Generar contenido HTML del PDF
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 14px; }
    h3 { color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>

<h3>Historial clínico</h3>
<p><strong>Paciente:</strong> ' . $datosPaciente['nombre'] . '</p>
<p><strong>Cédula:</strong> ' . htmlspecialchars($cedula_historial) . '</p>
<p><strong>Antecedentes:</strong> ' . ($datosPaciente['antecedentes'] ?: 'N/A') . '</p>
<p><strong>Enfermedades crónicas:</strong> ' . ($datosPaciente['enfermedades_cronicas'] ?: 'N/A') . '</p>
<p><strong>Alergias:</strong> ' . ($datosPaciente['alergias'] ?: 'N/A') . '</p>
<p><strong>Observaciones:</strong> ' . ($datosPaciente['observaciones'] ?: 'N/A') . '</p>
';

// 💉 Vacunas
$html .= '<h3>Vacunas registradas</h3>';
if (!empty($vacunas)) {
    $html .= '<ul>';
    foreach ($vacunas as $v) {
        $html .= '<li><strong>' . $v['nombre'] . '</strong> - ' . $v['dosis'] . ' dosis (' . date('d/m/Y', strtotime($v['fecha_aplicacion'])) . ')</li>';
    }
    $html .= '</ul>';
} else {
    $html .= '<p>No hay vacunas registradas.</p>';
}

// 📅 Citas médicas
$html .= '<h3>Citas médicas</h3>';
if (!empty($citas)) {
    $html .= '<table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Médico</th>
                <th>Especialidad</th>
                <th>Tipo</th>
                <th>Prioridad</th>
                <th>Origen</th>
                <th>Motivo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($citas as $c) {
        $html .= '<tr>
            <td>' . date('d/m/Y', strtotime($c['fecha'])) . '</td>
            <td>' . date('H:i', strtotime($c['hora'])) . '</td>
            <td>' . $c['medico'] . '</td>
            <td>' . $c['especialidad'] . '</td>
            <td>' . $c['tipo_cita'] . '</td>
            <td>' . $c['prioridad'] . '</td>
            <td>' . $c['origen'] . '</td>
            <td>' . $c['motivo'] . '</td>
            <td>' . $c['estado_nombre'] . '</td>
        </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<p>No hay citas registradas.</p>';
}

// 🖨️ Generar PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->render();
$pdf = $dompdf->output();

// 📤 Enviar correo
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'incitec13@gmail.com';
    $mail->Password = 'lfrkhywtrygiqnzw';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('incitec13@gmail.com', 'Clínica');
    $mail->addAddress($correo);
    $mail->CharSet = 'UTF-8'; // ✅ CORRIGE caracteres latinos
    $mail->Subject = '🩺 Historial Clínico - Clínica Digital';

    $mail->isHTML(true); // ✅ Activar contenido HTML

    $mail->Body = '
        <div style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
            <h2 style="color: #2c3e50;">📋 Historial Clínico</h2>
            <p>Hola,</p>
            <p>Adjunto encontrarás el PDF con tu historial clínico solicitado desde la plataforma <strong>Clínica Digital</strong>.</p>
            
            <p style="margin: 20px 0; background-color: #f8f8f8; padding: 15px; border-left: 5px solid #28a745;">
                ✔️ Este documento contiene:<br>
                - Información del paciente<br>
                - Historial clínico<br>
                - Vacunas registradas<br>
                - Citas médicas
            </p>

            <p>Gracias por confiar en nosotros.</p>

            <hr style="margin: 30px 0;">
            <p style="font-size: 13px; color: #888;">
                Este correo fue generado automáticamente por el sistema de gestión médica de Clínica Digital. Si tienes dudas, contáctanos a incitec13@gmail.com
            </p>
        </div>
    ';

    $mail->addStringAttachment($pdf, 'Historial_' . str_replace(' ', '_', $datosPaciente['nombre']) . '_' . $cedula_historial . '.pdf');

    $mail->send();
    echo json_encode(['status' => 'ok', 'msg' => $correo]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
