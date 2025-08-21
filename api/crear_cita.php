<?php
// api/crear_cita.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../libs/correo_cita.php'; // tu helper existente

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'JSON inválido']);
        exit;
    }

    // 1) Datos de entrada
    $cedula            = trim((string)($body['cedula_paciente'] ?? ''));  // opcional si no mandan paciente_id
    $paciente_id       = (int)($body['paciente_id'] ?? 0);
    $medico_id         = (int)($body['medico_id'] ?? 0);
    $especialidad_id   = (int)($body['especialidad_id'] ?? 0);
    $tipo_cita_id      = (int)($body['tipo_cita_id'] ?? 0);
    $prioridad_id      = (int)($body['prioridad_id'] ?? 0);
    $origen_id         = (int)($body['origen_id'] ?? 3); // 3 = Web (ajústalo si corresponde)
    $motivo            = trim((string)($body['motivo'] ?? ''));
    $fecha             = trim((string)($body['fecha'] ?? '')); // YYYY-MM-DD
    $hora              = trim((string)($body['hora'] ?? ''));  // HH:MM
    $estado_id         = (int)($body['estado_id'] ?? 1);       // 1 = Programada
    $creado_por_label  = trim((string)($body['creado_por'] ?? 'Registrada vía API'));

    // 2) Validaciones básicas
    if (!$medico_id || !$especialidad_id || !$tipo_cita_id || !$prioridad_id || !$origen_id || !$motivo || !$fecha || !$hora) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Campos obligatorios faltantes']);
        exit;
    }

    // Fecha y hora
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Formato de fecha inválido (use YYYY-MM-DD)']);
        exit;
    }
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Formato de hora inválido (use HH:MM 24h)']);
        exit;
    }
    $fecha_hora = $fecha . ' ' . $hora . ':00';

    // 3) Modelo y conexión
    $citaModel = new Cita();
    $pdo = $citaModel->conn;

    // 4) Resolver paciente: por cédula o por id
    if ($paciente_id <= 0) {
        if ($cedula === '') {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Debe enviar paciente_id o cedula_paciente']);
            exit;
        }
        $pac = $citaModel->buscarPacientePorCedula($cedula); // ya valida rol=30 y activo=1
        if (!$pac) {
            http_response_code(404);
            echo json_encode(['estado'=>'error','mensaje'=>'Paciente no encontrado por cédula']);
            exit;
        }
        $paciente_id = (int)$pac['usu_id'];
    } else {
        // validar paciente_id existe, rol 30 y activo 1
        $st = $pdo->prepare("SELECT usu_id, usu_nombre, usu_apellido, usu_correo FROM usuarios WHERE usu_id = :id AND rol_id = 30 AND usu_estado = 1");
        $st->execute([':id' => $paciente_id]);
        $pac = $st->fetch(PDO::FETCH_ASSOC);
        if (!$pac) {
            http_response_code(404);
            echo json_encode(['estado'=>'error','mensaje'=>'Paciente no válido']);
            exit;
        }
    }

    // 5) Validar médico (rol=31, activo)
    $st = $pdo->prepare("SELECT usu_id, usu_nombre, usu_apellido FROM usuarios WHERE usu_id = :id AND rol_id = 31 AND usu_estado = 1");
    $st->execute([':id' => $medico_id]);
    $med = $st->fetch(PDO::FETCH_ASSOC);
    if (!$med) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'Médico no válido']);
        exit;
    }

    // 6) Validar existencia de catálogos (especialidad, tipo, prioridad, origen)
    $catReqs = [
        ['sql' => 'SELECT 1 FROM especialidad WHERE especialidad_id = :id', 'id' => $especialidad_id, 'msg' => 'Especialidad no válida'],
        ['sql' => 'SELECT 1 FROM tipo_cita WHERE tipo_cita_id = :id',      'id' => $tipo_cita_id,    'msg' => 'Tipo de cita no válido'],
        ['sql' => 'SELECT 1 FROM prioridad WHERE prioridad_id = :id',      'id' => $prioridad_id,    'msg' => 'Prioridad no válida'],
        ['sql' => 'SELECT 1 FROM origen_cita WHERE origen_id = :id',       'id' => $origen_id,       'msg' => 'Origen no válido']
    ];
    foreach ($catReqs as $c) {
        $chk = $pdo->prepare($c['sql']);
        $chk->execute([':id' => $c['id']]);
        if (!$chk->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>$c['msg']]);
            exit;
        }
    }

    // 7) Validar que la hora esté dentro del turno del médico ese día
    $dias_es = [
        'Monday' => 'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
        'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo'
    ];
    $dia_es = $dias_es[date('l', strtotime($fecha))] ?? date('l', strtotime($fecha));

    $turnos = $citaModel->obtenerTurnoPorMedicoYDia($medico_id, $dia_es); // [['hora_inicio'=>'08:00:00','hora_fin'=>'12:00:00'], ...]
    if (!$turnos) {
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'El médico no atiende el día seleccionado']);
        exit;
    }

    $hhmm = date('H:i', strtotime($hora));
    $enTurno = false;
    foreach ($turnos as $t) {
        $ini = date('H:i', strtotime($t['hora_inicio']));
        $fin = date('H:i', strtotime($t['hora_fin']));
        if ($hhmm >= $ini && $hhmm < $fin) { $enTurno = true; break; }
    }
    if (!$enTurno) {
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'La hora no está dentro del turno del médico']);
        exit;
    }

    // 8) Validar que la hora no esté ocupada ya
    $ocupadas = $citaModel->obtenerHorasOcupadas($medico_id, $fecha); // ['08:00','10:30',...]
    if (in_array($hhmm, $ocupadas, true)) {
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'La hora ya está reservada para ese médico']);
        exit;
    }

    // 9) Iniciar transacción y revalidar slot (anti carrera)
    $pdo->beginTransaction();
    $check = $pdo->prepare("SELECT 1 FROM cita WHERE medico_id = :m AND DATE(fecha) = :f AND TIME(fecha) = :h LIMIT 1");
    $check->execute([':m'=>$medico_id, ':f'=>$fecha, ':h'=>$hhmm.':00']);
    if ($check->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'La hora fue tomada por otra operación. Intente otra.']);
        exit;
    }

    // 10) Determinar turno_id (puede ser null)
    $turno_id = $citaModel->obtenerTurnoId($medico_id, $fecha);

    // 11) Guardar cita
    $ok = $citaModel->guardarCita([
        'paciente_id'     => $paciente_id,
        'medico_id'       => $medico_id,
        'fecha'           => $fecha_hora,
        'tipo_cita_id'    => $tipo_cita_id,
        'especialidad_id' => $especialidad_id,
        'prioridad_id'    => $prioridad_id,
        'origen_id'       => $origen_id,
        'motivo'          => $motivo,
        'estado_id'       => $estado_id,
        'turno_id'        => $turno_id
    ]);

    if (!$ok) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['estado'=>'error','mensaje'=>'No se pudo guardar la cita']);
        exit;
    }

    $cita_id = (int)$pdo->lastInsertId();
    $pdo->commit();

    // 12) Traer detalle para respuesta y correo (reutiliza tu SELECT con nombres)
    $detalle = $citaModel->obtenerDetalleCita($cita_id);
    // Asegurar motivo (tu SELECT no lo trae como alias, pero viene en c.*)
    if (!isset($detalle['motivo'])) { $detalle['motivo'] = $motivo; }

    // 13) Enviar correo al paciente (no romper si falla)
    $correoEnviado = false;
    if (!empty($detalle['paciente_correo'])) {
        $correoEnviado = enviarCorreoCita(
            $detalle['paciente_correo'],
            $detalle['paciente'] ?? 'Paciente',
            $detalle,
            $creado_por_label
        );
    }

    // 14) Responder
    $ts = strtotime($detalle['fecha'] ?? $fecha_hora);
    echo json_encode([
        'estado' => 'ok',
        'mensaje'=> 'Cita creada exitosamente',
        'cita' => [
            'id'           => $cita_id,
            'fecha'        => $ts ? date('Y-m-d', $ts) : $fecha,
            'hora'         => $ts ? date('H:i:s', $ts) : ($hora . ':00'),
            'paciente'     => $detalle['paciente'] ?? ($pac['usu_nombre'].' '.$pac['usu_apellido']),
            'medico'       => $detalle['medico'] ?? (($med['usu_nombre'] ?? '').' '.($med['usu_apellido'] ?? '')),
            'especialidad' => $detalle['especialidad'] ?? null,
            'tipo_cita'    => $detalle['tipo_cita'] ?? null,
            'prioridad'    => $detalle['prioridad'] ?? null,
            'origen'       => $detalle['origen'] ?? null,
            'estado'       => $detalle['estado'] ?? 'Programada',
            'motivo'       => $detalle['motivo'] ?? $motivo
        ],
        'correo_enviado' => $correoEnviado
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
