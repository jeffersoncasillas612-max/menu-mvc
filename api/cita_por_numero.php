<?php
// api/cita_por_numero.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Cita.php';

try {
    // 1) Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    // 2) Validar cita_id
    $cita_id = isset($_GET['cita_id']) ? (int)$_GET['cita_id'] : 0;
    if ($cita_id <= 0) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Parámetro cita_id es obligatorio y numérico']);
        exit;
    }

    // 3) Buscar detalle en el modelo (ya lo tienes)
    $model = new Cita();
    $cita  = $model->obtenerDetalleCita($cita_id);

    // 4) No existe
    if (!$cita) {
        http_response_code(404);
        echo json_encode(['estado'=>'no_encontrada','mensaje'=>"No existe la cita N° $cita_id"]);
        exit;
    }

    // 5) Validación mínima de consistencia (paciente y médico deben existir)
    if (empty($cita['paciente']) || empty($cita['medico'])) {
        http_response_code(422);
        echo json_encode([
            'estado'  => 'error',
            'mensaje' => 'Cita inconsistente: no se encontró médico o paciente asociado'
        ]);
        exit;
    }

    // 6) Normalizar fecha y hora desde c.fecha
    $ts = strtotime($cita['fecha'] ?? '');
    $fecha_iso = $ts ? date('Y-m-d', $ts) : null;
    $hora_iso  = $ts ? date('H:i:s', $ts) : null;

    // 7) Respuesta SOLO con nombres (sin IDs), como pediste
    echo json_encode([
        'estado' => 'ok',
        'cita'   => [
            'numero'       => (int)$cita['cita_id'],
            'fecha'        => $fecha_iso,
            'hora'         => $hora_iso,
            'motivo'       => $cita['motivo'] ?? null,

            'paciente'     => $cita['paciente'], // CONCAT(nombre, ' ', apellido) ya viene del modelo
            'medico'       => $cita['medico'],   // idem
            'tipo_cita'    => $cita['tipo_cita'] ?? null,
            'especialidad' => $cita['especialidad'] ?? null,
            'prioridad'    => $cita['prioridad'] ?? null,
            'origen'       => $cita['origen'] ?? null,
            'estado'       => $cita['estado'] ?? null
        ]
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
