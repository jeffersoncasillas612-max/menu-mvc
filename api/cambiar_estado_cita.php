<?php
// api/cambiar_estado_cita.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Cita.php';     // Reutilizamos tu modelo
require_once __DIR__ . '/../config/database.php'; // Por si quieres PDO aparte (no estrictamente necesario)
require_once __DIR__ . '/../libs/correo_estado_cita.php';

try {
    // Permitimos PUT o POST
    if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT','POST'])) {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    // Leer JSON
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'JSON inválido']);
        exit;
    }

    $cita_id      = (int)($body['cita_id']   ?? 0);
    $estado_id    = (int)($body['estado_id'] ?? 0); // nuevo estado
    $motivo       = trim((string)($body['motivo'] ?? '')); // opcional (p.ej. cancelación)

    if ($cita_id <= 0 || $estado_id <= 0) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'cita_id y estado_id son obligatorios']);
        exit;
    }

    // Reusar tu modelo
    $citaModel = new Cita();
    $pdo       = $citaModel->conn; // tu Cita tiene $conn público

    // Traer detalle actual (incluye c.* y alias 'estado' como nombre)
    $det = $citaModel->obtenerDetalleCita($cita_id);
    if (!$det) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'La cita no existe']);
        exit;
    }

    // Validar que el estado nuevo exista y obtener su nombre
    $qEstado = $pdo->prepare("SELECT nombre FROM estado_cita WHERE estado_id = :id");
    $qEstado->execute([':id' => $estado_id]);
    $rowEstado = $qEstado->fetch(PDO::FETCH_ASSOC);
    if (!$rowEstado) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'El estado indicado no existe']);
        exit;
    }
    $estadoNuevoNombre = $rowEstado['nombre'];

    // Si ya está en ese estado, no hacemos nada
    $estadoActualId   = isset($det['estado_id']) ? (int)$det['estado_id'] : null;
    $estadoActualName = $det['estado'] ?? null;

    if ($estadoActualId !== null && $estadoActualId === $estado_id) {
        // Igual devolvemos info útil
        $ts = strtotime($det['fecha'] ?? '');
        echo json_encode([
            'estado' => 'ok',
            'mensaje' => 'La cita ya estaba en ese estado',
            'cita' => [
                'numero'        => (int)$det['cita_id'],
                'fecha'         => $ts ? date('Y-m-d', $ts) : null,
                'hora'          => $ts ? date('H:i:s', $ts) : null,
                'paciente'      => $det['paciente'] ?? null,
                'medico'        => $det['medico'] ?? null,
                'tipo_cita'     => $det['tipo_cita'] ?? null,
                'especialidad'  => $det['especialidad'] ?? null,
                'prioridad'     => $det['prioridad'] ?? null,
                'origen'        => $det['origen'] ?? null,
                'estado_anterior'=> $estadoActualName,
                'estado_actual'  => $estadoActualName,
                'motivo'         => $motivo ?: null
            ]
        ]);
        exit;
    }

    // (Opcional) Matriz de transiciones válidas — si no quieres restringir, comenta este bloque
    /*
    $validas = [
        1 => [2,4,5],   // Programada -> Confirmada, Cancelada, Perdida
        2 => [3,4,5],   // Confirmada -> Atendida, Cancelada, Perdida
        6 => [2,4,3,5], // Reprogramada -> Confirmada, Cancelada, Atendida, Perdida
        // etc.
    ];
    if (isset($validas[$estadoActualId]) && !in_array($estado_id, $validas[$estadoActualId], true)) {
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'Transición de estado no permitida']);
        exit;
    }
    */

    // Actualizar estado dentro de transacción
    $pdo->beginTransaction();

    $upd = $pdo->prepare("UPDATE cita SET estado_id = :estado WHERE cita_id = :id");
    $upd->execute([':estado' => $estado_id, ':id' => $cita_id]);

    // (Opcional) si tienes tabla historial:
    // $ins = $pdo->prepare("INSERT INTO cita_estado_historial (cita_id, estado_anterior, estado_nuevo, motivo) VALUES (?,?,?,?)");
    // $ins->execute([$cita_id, $estadoActualId, $estado_id, $motivo]);

    $pdo->commit();

    //Envio de correo
    $nuevo = $citaModel->obtenerDetalleCita($cita_id); // ya trae paciente_correo

    $datosCita = [
        'fecha'           => $nuevo['fecha'] ?? null,
        'especialidad'    => $nuevo['especialidad'] ?? null,
        'medico'          => $nuevo['medico'] ?? null,
        'tipo_cita'       => $nuevo['tipo_cita'] ?? null,
        'prioridad'       => $nuevo['prioridad'] ?? null,
        'origen'          => $nuevo['origen'] ?? null,
        'estado_anterior' => $estadoActualName,
        'estado_nuevo'    => $estadoNuevoNombre
    ];

    $correoOk = false;
    if (!empty($nuevo['paciente_correo'])) {
        $correoOk = enviarCorreoCambioEstadoCita(
            $nuevo['paciente_correo'],
            $nuevo['paciente'] ?? 'Paciente',
            $datosCita,
            $motivo // opcional
        );
    }

    // Volver a traer detalle para responder con nombres actualizados
    $nuevo = $citaModel->obtenerDetalleCita($cita_id);

    $ts = strtotime($nuevo['fecha'] ?? '');
    echo json_encode([
        'estado' => 'ok',
        'mensaje' => 'Estado actualizado correctamente',
        'cita' => [
            'numero'         => (int)$nuevo['cita_id'],
            'fecha'          => $ts ? date('Y-m-d', $ts) : null,
            'hora'           => $ts ? date('H:i:s', $ts) : null,
            'paciente'       => $nuevo['paciente'] ?? null,
            'medico'         => $nuevo['medico'] ?? null,
            'tipo_cita'      => $nuevo['tipo_cita'] ?? null,
            'especialidad'   => $nuevo['especialidad'] ?? null,
            'prioridad'      => $nuevo['prioridad'] ?? null,
            'origen'         => $nuevo['origen'] ?? null,
            'estado_anterior'=> $estadoActualName,
            'estado_actual'  => $nuevo['estado'] ?? $estadoNuevoNombre,
            'motivo'         => $motivo ?: null
        ],
        'correo_enviado' => $correoOk
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
