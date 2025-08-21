<?php
// api/horarios_medico.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Turno.php';
require_once __DIR__ . '/../models/Usuario.php';

session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    $medico_id = isset($_GET['medico_id']) ? (int)$_GET['medico_id'] : 0;
    $diaParam  = isset($_GET['dia']) ? trim((string)$_GET['dia']) : '';
    $formato   = strtolower((string)($_GET['formato'] ?? 'agrupado')); // agrupado|plano

    if ($medico_id <= 0) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'medico_id es obligatorio y numérico']);
        exit;
    }

    // (Opcional) regla: si el que consulta es médico, solo puede ver lo suyo
    $rolSesion = (int)($_SESSION['usuario']['rol_id'] ?? 0);
    $idSesion  = (int)($_SESSION['usuario']['usu_id'] ?? 0);
    if ($rolSesion === 31 && $idSesion !== $medico_id) {
        http_response_code(403);
        echo json_encode(['estado'=>'error','mensaje'=>'No autorizado']);
        exit;
    }

    // Validar que exista y sea médico
    $u = new Usuario();
    $med = $u->obtenerPorId($medico_id);
    if (!$med) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>"Médico con ID $medico_id no existe"]);
        exit;
    }
    if ((int)$med['rol_id'] !== 31) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'El usuario indicado no tiene rol de médico']);
        exit;
    }

    // Normalizar día (opcional)
    $mapDia = [
        'lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','miércoles'=>'Miércoles',
        'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'Sábado','sábado'=>'Sábado','domingo'=>'Domingo',
        '1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'
    ];
    $diaFiltro = '';
    if ($diaParam !== '') {
        $k = strtolower($diaParam);
        if (!isset($mapDia[$k])) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>"Día inválido: $diaParam"]);
            exit;
        }
        $diaFiltro = $mapDia[$k];
    }

    // Reutiliza tu función existente 👇
    $t = new Turno();
    $rows = $t->obtenerTurnosPorMedico($medico_id);

    // Filtrar por día si se pidió (sin tocar el modelo)
    if ($diaFiltro !== '') {
        $rows = array_values(array_filter($rows, fn($r) => ($r['dia_semana'] ?? '') === $diaFiltro));
    }

    if ($formato === 'plano') {
        echo json_encode([
            'estado' => 'ok',
            'total'  => count($rows),
            'medico' => [
                'id' => (int)$med['usu_id'],
                'nombre' => $med['usu_nombre'] ?? null,
                'apellido' => $med['usu_apellido'] ?? null,
                'correo' => $med['usu_correo'] ?? null,
            ],
            'turnos' => array_map(fn($r) => [
                'turno_id'   => (int)($r['turno_id'] ?? 0),
                'dia_semana' => $r['dia_semana'],
                'hora_inicio'=> $r['hora_inicio'],
                'hora_fin'   => $r['hora_fin'],
            ], $rows)
        ]);
        exit;
    }

    // Formato agrupado por día (default)
    $orden = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
    $agr = []; foreach ($orden as $d) $agr[$d] = [];
    foreach ($rows as $r) {
        $d = $r['dia_semana'];
        if (!isset($agr[$d])) $agr[$d] = [];
        $agr[$d][] = [
            'turno_id'    => (int)($r['turno_id'] ?? 0),
            'hora_inicio' => $r['hora_inicio'],
            'hora_fin'    => $r['hora_fin'],
        ];
    }
    if ($diaFiltro !== '') $agr = [$diaFiltro => $agr[$diaFiltro] ?? []];

    echo json_encode([
        'estado'  => 'ok',
        'total'   => count($rows),
        'medico'  => [
            'id' => (int)$med['usu_id'],
            'nombre' => $med['usu_nombre'] ?? null,
            'apellido' => $med['usu_apellido'] ?? null,
            'correo' => $med['usu_correo'] ?? null,
        ],
        'horarios'=> $agr
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
