<?php
// api/actualizar_horario_dia.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Turno.php';
require_once __DIR__ . '/../models/Usuario.php';

session_start();

try {
    // Solo PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    // (Opcional) bloquear a médicos
    $rolSesion = (int)($_SESSION['usuario']['rol_id'] ?? 0);
    if ($rolSesion === 31) {
        http_response_code(403);
        echo json_encode(['estado'=>'error','mensaje'=>'No autorizado']);
        exit;
    }

    // Body
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'JSON inválido']);
        exit;
    }

    $medico_id = (int)($body['medico_id'] ?? 0);
    $diaInput  = (string)($body['dia'] ?? $body['dia_semana'] ?? '');
    $rangos    = $body['rangos'] ?? null; // [{hora_inicio, hora_fin}, ...]  (también aceptamos mañana/tarde abajo)

    if ($medico_id <= 0 || $diaInput === '') {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'medico_id y dia son obligatorios']);
        exit;
    }

    // Validar médico
    $usuarioModel = new Usuario();
    $medico = $usuarioModel->obtenerPorId($medico_id);
    if (!$medico) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>"El médico con ID $medico_id no existe"]);
        exit;
    }
    if ((int)$medico['rol_id'] !== 31) { // ajusta si tu rol de médico es otro
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'El usuario no tiene rol de médico']);
        exit;
    }

    // Normalizadores
    $mapDia = [
        'lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','miércoles'=>'Miércoles',
        'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'Sábado','sábado'=>'Sábado','domingo'=>'Domingo',
        '1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'
    ];
    $diaKey = strtolower(trim($diaInput));
    if (!isset($mapDia[$diaKey])) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>"Día inválido: $diaInput"]);
        exit;
    }
    $dia = $mapDia[$diaKey];

    $isHora = function(string $h): bool {
        return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $h);
    };
    $normHora = function(string $h): string {
        return preg_match('/^\d{2}:\d{2}$/', $h) ? $h.':00' : $h;
    };

    // También aceptamos mañana/tarde
    if ((!is_array($rangos) || empty($rangos)) && (isset($body['manana']) || isset($body['tarde']))) {
        $rangos = [];
        if (isset($body['manana'])) {
            $m = $body['manana'];
            $rangos[] = ['hora_inicio'=>$m['desde'] ?? '', 'hora_fin'=>$m['hasta'] ?? ''];
        }
        if (isset($body['tarde'])) {
            $t = $body['tarde'];
            $rangos[] = ['hora_inicio'=>$t['desde'] ?? '', 'hora_fin'=>$t['hasta'] ?? ''];
        }
    }

    if (!is_array($rangos) || empty($rangos)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Debes enviar rangos (o mañana/tarde)']);
        exit;
    }

    // Validar rangos y normalizar
    $rangosLimpios = [];
    foreach ($rangos as $r) {
        $hi = $normHora(trim((string)($r['hora_inicio'] ?? '')));
        $hf = $normHora(trim((string)($r['hora_fin'] ?? '')));

        if (!$isHora($hi) || !$isHora($hf) || $hi >= $hf) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>"Rango inválido ($hi - $hf)"]);
            exit;
        }
        $rangosLimpios[] = [$hi, $hf];
    }

    // Transacción: borrar el día y re-crear
    $turnoModel = new Turno();
    $pdo = (new Database())->getConnection();
    $pdo->beginTransaction();

    try {
        $turnoModel->eliminarTurnosPorDia($medico_id, $dia);

        $creados = 0;
        foreach ($rangosLimpios as [$hi, $hf]) {
            if ($turnoModel->crearTurno($medico_id, $dia, $hi, $hf)) {
                $creados++;
            }
        }

        $pdo->commit();
        echo json_encode(['estado'=>'ok','mensaje'=>"Día $dia actualizado", 'creados'=>$creados]);
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>$e->getMessage()]);
        exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
