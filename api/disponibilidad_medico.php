<?php
// api/disponibilidad_medico.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Usuario.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    $medico_id = isset($_GET['medico_id']) ? (int)$_GET['medico_id'] : 0;
    $fecha     = trim((string)($_GET['fecha'] ?? '')); // YYYY-MM-DD
    $duracion  = isset($_GET['duracion']) ? (int)$_GET['duracion'] : 30; // minutos (15/30/60)
    $horaCheck = trim((string)($_GET['hora'] ?? ''));  // HH:MM opcional
    $excluirPasado = isset($_GET['excluir_pasado']) ? filter_var($_GET['excluir_pasado'], FILTER_VALIDATE_BOOLEAN) : true;

    if ($medico_id <= 0 || $fecha === '') {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'medico_id y fecha (YYYY-MM-DD) son obligatorios']);
        exit;
    }
    // Validar fecha
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Formato de fecha inválido (usa YYYY-MM-DD)']);
        exit;
    }
    // Validar duracion
    if (!in_array($duracion, [15,30,60], true)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'La duración debe ser 15, 30 o 60 minutos']);
        exit;
    }

    // Validar médico existe y es rol médico
    $u = new Usuario();
    if (!method_exists($u, 'obtenerPorId')) {
        http_response_code(500);
        echo json_encode(['estado'=>'error','mensaje'=>'Falta método Usuario::obtenerPorId']);
        exit;
    }
    $med = $u->obtenerPorId($medico_id);
    if (!$med) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'Médico no existe']);
        exit;
    }
    if ((int)($med['rol_id'] ?? 0) !== 31) { // ajusta si tu rol médico es otro
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'El usuario no tiene rol de médico']);
        exit;
    }

    // Mapa de día en español según fecha
    $dias_es = [
        'Monday'    => 'Lunes',
        'Tuesday'   => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday'  => 'Jueves',
        'Friday'    => 'Viernes',
        'Saturday'  => 'Sábado',
        'Sunday'    => 'Domingo'
    ];
    $dia_en = date('l', strtotime($fecha));
    $dia_es = $dias_es[$dia_en] ?? $dia_en;

    $citaModel = new Cita();

    // 1) Rangos del turno del médico para ese día
    $turnos = $citaModel->obtenerTurnoPorMedicoYDia($medico_id, $dia_es); // [['hora_inicio'=>'08:00:00','hora_fin'=>'12:00:00'], ...]
    if (!$turnos || count($turnos) === 0) {
        echo json_encode([
            'estado' => 'ok',
            'mensaje'=> 'El médico no atiende este día',
            'disponibles' => []
        ]);
        exit;
    }

    // 2) Horas ocupadas para esa fecha (array de 'H:i')
    $ocupadas = $citaModel->obtenerHorasOcupadas($medico_id, $fecha); // p.ej ['08:00','10:30']

    // 3) Generar slots disponibles a partir de los rangos
    $generarSlots = function(array $turnos, int $durMin) {
        $slots = [];
        foreach ($turnos as $t) {
            $ini = strtotime($t['hora_inicio']);
            $fin = strtotime($t['hora_fin']);
            // incluir si el slot completo cabe dentro del rango
            for ($s = $ini; $s + ($durMin*60) <= $fin; $s += $durMin*60) {
                $slots[] = date('H:i', $s);
            }
        }
        // quitar duplicados por si hay solapamiento de rangos
        return array_values(array_unique($slots));
    };

    $todosSlots = $generarSlots($turnos, $duracion);

    // 4) Excluir ocupadas
    $disponibles = array_values(array_filter($todosSlots, function($h) use ($ocupadas) {
        return !in_array($h, $ocupadas, true);
    }));

    // 5) Si es hoy y se pidió excluir pasado, quita horas anteriores a 'ahora'
    if ($excluirPasado) {
        $hoy = date('Y-m-d');
        if ($fecha === $hoy) {
            $ahora = date('H:i');
            $disponibles = array_values(array_filter($disponibles, fn($h) => $h > $ahora));
        }
    }

    // 6) Si mandaron una hora específica, validar contra los disponibles
    if ($horaCheck !== '') {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaCheck)) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Formato de hora inválido (usa HH:MM en 24h)']);
            exit;
        }
        $ok = in_array($horaCheck, $disponibles, true);
        echo json_encode([
            'estado' => 'ok',
            'medico' => [
                'id' => (int)$medico_id,
                'nombre' => ($med['usu_nombre'] ?? '') . ' ' . ($med['usu_apellido'] ?? '')
            ],
            'fecha'  => $fecha,
            'duracion_min' => $duracion,
            'hora_consultada' => $horaCheck,
            'disponible' => $ok
        ]);
        exit;
    }

    // 7) Respuesta con todas las horas disponibles
    echo json_encode([
        'estado' => 'ok',
        'medico' => [
            'id' => (int)$medico_id,
            'nombre' => ($med['usu_nombre'] ?? '') . ' ' . ($med['usu_apellido'] ?? '')
        ],
        'fecha'  => $fecha,
        'dia'    => $dia_es,
        'duracion_min' => $duracion,
        'total'  => count($disponibles),
        'disponibles' => array_map(function($h) {
            return ['hora' => $h, 'label' => date('g:i A', strtotime($h))];
        }, $disponibles)
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
