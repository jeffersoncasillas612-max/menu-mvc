<?php
// api/asignar_horario_medico.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Turno.php';
require_once __DIR__ . '/../models/Usuario.php';

session_start();

try {
    // Aceptar solo POST y PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    // Leer body JSON
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body) || !isset($body['medico_id'])) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Falta medico_id en el body']);
        exit;
    }

    $medico_id = (int)$body['medico_id'];
    $dias      = $body['dias'] ?? null;

    if ($medico_id <= 0 || !is_array($dias) || empty($dias)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'medico_id y dias son obligatorios']);
        exit;
    }

    // Validar existencia del médico
    $usuarioModel = new Usuario();
    $medico = $usuarioModel->obtenerPorId($medico_id);

    if (!$medico) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>"El médico con ID $medico_id no existe"]);
        exit;
    }

    if ((int)$medico['rol_id'] !== 31) { // Ajusta el rol_id según tu BD
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
    $isHora = function(string $h): bool {
        return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $h);
    };
    $normHora = function(string $h): string {
        return preg_match('/^\d{2}:\d{2}$/', $h) ? $h.':00' : $h;
    };

    $turnoModel = new Turno();
    $pdo = (new Database())->getConnection();
    $pdo->beginTransaction();

    try {
        $creados = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // POST = eliminar TODO el horario y volver a crear
            $turnoModel->eliminarTodosPorMedico($medico_id);
        }

        foreach ($dias as $d) {
            $diaRaw = strtolower(trim((string)($d['dia'] ?? $d['dia_semana'] ?? '')));
            if (!isset($mapDia[$diaRaw])) {
                throw new Exception("Día inválido: ".($d['dia'] ?? $d['dia_semana'] ?? ''));
            }
            $dia = $mapDia[$diaRaw];

            // PUT = reemplazar solo ese día
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $turnoModel->eliminarTurnosPorDia($medico_id, $dia);
            }

            // Recolectar rangos
            $rangos = [];
            if (isset($d['rangos']) && is_array($d['rangos'])) {
                foreach ($d['rangos'] as $r) {
                    $rangos[] = ['hi' => $r['hora_inicio'] ?? '', 'hf' => $r['hora_fin'] ?? ''];
                }
            }
            if (isset($d['manana'])) {
                $m = $d['manana'];
                $rangos[] = ['hi' => $m['desde'] ?? '', 'hf' => $m['hasta'] ?? ''];
            }
            if (isset($d['tarde'])) {
                $t = $d['tarde'];
                $rangos[] = ['hi' => $t['desde'] ?? '', 'hf' => $t['hasta'] ?? ''];
            }

            if (!$rangos) throw new Exception("Sin rangos para el día $dia");

            foreach ($rangos as $r) {
                $hi = $normHora(trim((string)$r['hi']));
                $hf = $normHora(trim((string)$r['hf']));

                if (!$isHora($hi) || !$isHora($hf) || $hi >= $hf) {
                    throw new Exception("Rango inválido ($hi - $hf) en $dia");
                }

                $ok = $turnoModel->crearTurno($medico_id, $dia, $hi, $hf);
                if ($ok) $creados++;
            }
        }

        $pdo->commit();
        echo json_encode(['estado'=>'ok','creados'=>$creados]);
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
