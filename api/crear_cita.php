<?php
// api/crear_cita.php
header('Content-Type: application/json');

// Ajusta el timezone del backend (evita sorpresas en funciones como date()/NOW())
date_default_timezone_set('America/Guayaquil');

require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../libs/correo_cita.php'; // enviarCorreoCita($correo,$nombre,$detalle,$creadoPor,$tele=null)

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

    // -------- Entrada
    $cedula            = trim((string)($body['cedula_paciente'] ?? '')); // opcional si mandan paciente_id
    $paciente_id       = (int)($body['paciente_id'] ?? 0);
    $medico_id         = (int)($body['medico_id'] ?? 0);
    $especialidad_id   = (int)($body['especialidad_id'] ?? 0);
    $tipo_cita_id      = (int)($body['tipo_cita_id'] ?? 0);
    $prioridad_id      = (int)($body['prioridad_id'] ?? 0);
    $origen_id         = (int)($body['origen_id'] ?? 3); // 3 = Web, ajusta si quieres
    $motivo            = trim((string)($body['motivo'] ?? ''));
    $fecha             = trim((string)($body['fecha'] ?? ''));  // YYYY-MM-DD
    $hora              = trim((string)($body['hora'] ?? ''));   // HH:MM (24h)
    $estado_id         = (int)($body['estado_id'] ?? 1);        // 1 = Programada
    $creado_por_label  = trim((string)($body['creado_por'] ?? 'Registrada vía API'));
    $modalidad         = strtolower(trim((string)($body['modalidad'] ?? 'presencial'))); // 'presencial' | 'online'

    // -------- Validaciones MIN sin conversiones de tz
    if (!$medico_id || !$especialidad_id || !$tipo_cita_id || !$prioridad_id || !$origen_id || !$motivo || !$fecha || !$hora) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Campos obligatorios faltantes']);
        exit;
    }

    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Formato de fecha inválido (use YYYY-MM-DD)']);
        exit;
    }

    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora, $m)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Formato de hora inválido (use HH:MM 24h)']);
        exit;
    }
    $hhmm       = $m[1] . ':' . $m[2];          // Ej: "07:00"
    $fecha_hora = $fecha . ' ' . $hhmm . ':00'; // Ej: "2025-08-27 07:00:00"

    $citaModel = new Cita();
    $pdo = $citaModel->conn;

    // -------- Resolver paciente (por id o cédula)
    if ($paciente_id <= 0) {
        if ($cedula === '') {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Debe enviar paciente_id o cedula_paciente']);
            exit;
        }
        $pac = $citaModel->buscarPacientePorCedula($cedula); // ya valida rol=30 y activo=1
        if (!$pac) {
            http_response_code(404);
            echo json_encode(['estado'=>'error','mensaje'=>'Paciente no encontrado']);
            exit;
        }
        $paciente_id = (int)$pac['usu_id'];
    } else {
        $st = $pdo->prepare("SELECT usu_id, usu_nombre, usu_apellido, usu_correo 
                             FROM usuarios 
                             WHERE usu_id = :id AND rol_id = 30 AND usu_estado = 1");
        $st->execute([':id'=>$paciente_id]);
        $pac = $st->fetch(PDO::FETCH_ASSOC);
        if (!$pac) {
            http_response_code(404);
            echo json_encode(['estado'=>'error','mensaje'=>'Paciente no válido']);
            exit;
        }
    }

    // -------- Validar médico
    $st = $pdo->prepare("SELECT usu_id, usu_nombre, usu_apellido 
                         FROM usuarios 
                         WHERE usu_id = :id AND rol_id = 31 AND usu_estado = 1");
    $st->execute([':id'=>$medico_id]);
    $med = $st->fetch(PDO::FETCH_ASSOC);
    if (!$med) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'Médico no válido']);
        exit;
    }

    // -------- Validar catálogos
    $catReqs = [
        ['sql'=>'SELECT 1 FROM especialidad WHERE especialidad_id = :id','id'=>$especialidad_id,'msg'=>'Especialidad no válida'],
        ['sql'=>'SELECT 1 FROM tipo_cita    WHERE tipo_cita_id     = :id','id'=>$tipo_cita_id   ,'msg'=>'Tipo de cita no válido'],
        ['sql'=>'SELECT 1 FROM prioridad    WHERE prioridad_id     = :id','id'=>$prioridad_id   ,'msg'=>'Prioridad no válida'],
        ['sql'=>'SELECT 1 FROM origen_cita  WHERE origen_id        = :id','id'=>$origen_id      ,'msg'=>'Origen no válido'],
    ];
    foreach ($catReqs as $c) {
        $q = $pdo->prepare($c['sql']);
        $q->execute([':id'=>$c['id']]);
        if (!$q->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>$c['msg']]);
            exit;
        }
    }

    // -------- Validar turno y disponibilidad
    $dias_es = [
        'Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
        'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo'
    ];
    $dia_es = $dias_es[date('l', strtotime($fecha))] ?? date('l', strtotime($fecha));

    $turnos = $citaModel->obtenerTurnoPorMedicoYDia($medico_id, $dia_es);
    if (!$turnos) {
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'El médico no atiende el día seleccionado']);
        exit;
    }

    $enTurno = false;
    foreach ($turnos as $t) {
        // Aseguramos comparar "HH:MM" vs "HH:MM"
        $ini = substr($t['hora_inicio'], 0, 5);
        $fin = substr($t['hora_fin'], 0, 5);
        if ($hhmm >= $ini && $hhmm < $fin) { $enTurno = true; break; }
    }
    if (!$enTurno) {
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'La hora no está dentro del turno del médico']);
        exit;
    }

    $ocupadas = $citaModel->obtenerHorasOcupadas($medico_id, $fecha); // ['08:00','10:30',...]
    if (in_array($hhmm, $ocupadas, true)) {
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'La hora ya está reservada']);
        exit;
    }

    // -------- Crear cita (y telecita si aplica)
    $pdo->beginTransaction();

    // Anti carrera: re-chequeo
    $check = $pdo->prepare("SELECT 1 FROM cita 
                            WHERE medico_id = :m AND DATE(fecha)=:f AND TIME(fecha)=:h 
                            LIMIT 1");
    $check->execute([':m'=>$medico_id, ':f'=>$fecha, ':h'=>$hhmm.':00']);
    if ($check->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['estado'=>'error','mensaje'=>'La hora fue tomada por otra operación.']);
        exit;
    }

    $turno_id = $citaModel->obtenerTurnoId($medico_id, $fecha);

    $ok = $citaModel->guardarCita([
        'paciente_id'     => $paciente_id,
        'medico_id'       => $medico_id,
        'fecha'           => $fecha_hora,            // <-- EXACTO lo enviado
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

    // Telecita (solo si modalidad == 'online')
    $teleData = null;
    if ($modalidad === 'online') {
        // Base del enlace de triaje (dominio de producción que indicaste)
        $triageBase = 'https://menu-mvc.onrender.com/views/tele/triage.php';

        $triage_token = bin2hex(random_bytes(16));
        $meeting_url  = 'https://meet.jit.si/hospital-' . bin2hex(random_bytes(6));
        $triage_url   = $triageBase . '?token=' . $triage_token;

        // Guardar telecita
        $insTele = $pdo->prepare("
            INSERT INTO telecita (cita_id, meeting_url, triage_token)
            VALUES (:c, :m, :t)
        ");
        $insTele->execute([':c'=>$cita_id, ':m'=>$meeting_url, ':t'=>$triage_token]);

        $teleData = [
            'meeting_url' => $meeting_url,
            'triage_url'  => $triage_url
        ];
    }

    $pdo->commit();

    // -------- Detalle para respuesta/correo
    $detalle = $citaModel->obtenerDetalleCita($cita_id);
    if (!isset($detalle['motivo'])) { $detalle['motivo'] = $motivo; }

    // -------- Enviar correo (pasa $teleData como 5.º parámetro)
    $correoEnviado = false;
    if (!empty($detalle['paciente_correo'])) {
        $correoEnviado = enviarCorreoCita(
            $detalle['paciente_correo'],
            $detalle['paciente'] ?? ($pac['usu_nombre'].' '.$pac['usu_apellido']),
            $detalle,
            $creado_por_label,
            $teleData
        );
    }

    // -------- Respuesta (devolvemos lo mismo que se guardó)
    echo json_encode([
        'estado'  => 'ok',
        'mensaje' => 'Cita creada exitosamente',
        'cita' => [
            'id'           => $cita_id,
            'fecha'        => $fecha,          // exactamente lo recibido
            'hora'         => $hhmm . ':00',   // exactamente lo recibido
            'paciente'     => $detalle['paciente'] ?? ($pac['usu_nombre'].' '.$pac['usu_apellido']),
            'medico'       => $detalle['medico'] ?? (($med['usu_nombre'] ?? '').' '.($med['usu_apellido'] ?? '')),
            'especialidad' => $detalle['especialidad'] ?? null,
            'tipo_cita'    => $detalle['tipo_cita'] ?? null,
            'prioridad'    => $detalle['prioridad'] ?? null,
            'origen'       => $detalle['origen'] ?? null,
            'estado'       => $detalle['estado'] ?? 'Programada',
            'motivo'       => $detalle['motivo'] ?? $motivo,
            'modalidad'    => $modalidad,
            'tele'         => $teleData
        ],
        'correo_enviado' => $correoEnviado
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
