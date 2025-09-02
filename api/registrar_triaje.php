<?php
// api/registrar_triaje.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Cita.php';

function numOrNull($v) {
    if ($v === null) return null;
    if ($v === '')   return null;
    if (!is_numeric($v)) return null;
    return $v + 0;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'JSON inválido']);
        exit;
    }

    $cita_id = (int)($data['cita_id'] ?? 0);
    $modo    = strtolower(trim((string)($data['modo'] ?? 'upsert')));
    $marcarTeleOK = isset($data['marcar_telecita_completada']) ? (bool)$data['marcar_telecita_completada'] : true;

    if ($cita_id <= 0) { http_response_code(400); echo json_encode(['estado'=>'error','mensaje'=>'cita_id es obligatorio']); exit; }

    $citaModel = new Cita();
    $pdo = $citaModel->conn;

    // 1) Validar cita existente
    $st = $pdo->prepare("SELECT c.cita_id, c.estado_id FROM cita c WHERE c.cita_id = :id LIMIT 1");
    $st->execute([':id' => $cita_id]);
    $cita = $st->fetch(PDO::FETCH_ASSOC);
    if (!$cita) { http_response_code(404); echo json_encode(['estado'=>'error','mensaje'=>'Cita no encontrada']); exit; }

    // 2) Leer si ya hay triaje
    $st = $pdo->prepare("SELECT triaje_id FROM triaje WHERE cita_id = :c LIMIT 1");
    $st->execute([':c' => $cita_id]);
    $triajeRow = $st->fetch(PDO::FETCH_ASSOC);
    $existe = (bool)$triajeRow;

    if ($modo === 'insert' && $existe) { http_response_code(409); echo json_encode(['estado'=>'error','mensaje'=>'Ya existe triaje para esta cita']); exit; }
    if ($modo === 'update' && !$existe) { http_response_code(404); echo json_encode(['estado'=>'error','mensaje'=>'No existe triaje para actualizar']); exit; }

    // 3) Preparar datos (con validaciones básicas)
    $peso   = numOrNull($data['peso'] ?? null);                 // 1..400 (validación suave)
    $talla  = numOrNull($data['talla_cm'] ?? null);             // 20..250
    $temp   = numOrNull($data['temperatura_c'] ?? null);        // 30..45
    $ps     = numOrNull($data['presion_sistolica'] ?? null);    // 50..250
    $pd     = numOrNull($data['presion_diastolica'] ?? null);   // 30..150
    $fc     = numOrNull($data['fc_lpm'] ?? null);               // 30..220
    $fr     = numOrNull($data['fr_rpm'] ?? null);               // 5..80
    $sato2  = numOrNull($data['sato2_pct'] ?? null);            // 50..100

    $sintomas          = trim((string)($data['sintomas'] ?? ''));
    $alergias          = trim((string)($data['alergias'] ?? ''));
    $antecedentes      = trim((string)($data['antecedentes'] ?? ''));
    $medicacion_actual = trim((string)($data['medicacion_actual'] ?? ''));
    $otros             = trim((string)($data['otros'] ?? ''));

    // 4) Insert/Update
    $pdo->beginTransaction();

    if ($existe) {
        $sql = "
            UPDATE triaje SET
                peso = :peso, talla_cm = :talla, temperatura_c = :temp,
                presion_sistolica = :ps, presion_diastolica = :pd,
                fc_lpm = :fc, fr_rpm = :fr, sato2_pct = :sato2,
                sintomas = :sintomas, alergias = :alergias, antecedentes = :antecedentes,
                medicacion_actual = :medicacion, otros = :otros
            WHERE cita_id = :cita
        ";
    } else {
        $sql = "
            INSERT INTO triaje
                (cita_id, peso, talla_cm, temperatura_c, presion_sistolica, presion_diastolica,
                 fc_lpm, fr_rpm, sato2_pct, sintomas, alergias, antecedentes, medicacion_actual, otros)
            VALUES
                (:cita, :peso, :talla, :temp, :ps, :pd, :fc, :fr, :sato2, :sintomas, :alergias, :antecedentes, :medicacion, :otros)
        ";
    }

    $ok = $pdo->prepare($sql)->execute([
        ':cita'       => $cita_id,
        ':peso'       => $peso,
        ':talla'      => $talla,
        ':temp'       => $temp,
        ':ps'         => $ps,
        ':pd'         => $pd,
        ':fc'         => $fc,
        ':fr'         => $fr,
        ':sato2'      => $sato2,
        ':sintomas'   => $sintomas,
        ':alergias'   => $alergias,
        ':antecedentes' => $antecedentes,
        ':medicacion' => $medicacion_actual,
        ':otros'      => $otros
    ]);

    if (!$ok) { $pdo->rollBack(); http_response_code(500); echo json_encode(['estado'=>'error','mensaje'=>'No se pudo guardar el triaje']); exit; }

    // 5) Si existe telecita y se pidió marcar completado
    if ($marcarTeleOK) {
        $pdo->prepare("UPDATE telecita SET triage_status = 'completado' WHERE cita_id = :c")
            ->execute([':c' => $cita_id]);
    }

    $pdo->commit();

    // 6) Calcular IMC (opcional)
    $imc = null;
    if ($peso && $talla) {
        $m = $talla / 100;
        if ($m > 0) $imc = round($peso / ($m * $m), 2);
    }

    $detalle = [
        'cita_id'            => $cita_id,
        'peso'               => $peso,
        'talla_cm'           => $talla,
        'temperatura_c'      => $temp,
        'presion'            => ['sistolica'=>$ps, 'diastolica'=>$pd],
        'fc_lpm'             => $fc,
        'fr_rpm'             => $fr,
        'sato2_pct'          => $sato2,
        'sintomas'           => $sintomas,
        'alergias'           => $alergias,
        'antecedentes'       => $antecedentes,
        'medicacion_actual'  => $medicacion_actual,
        'otros'              => $otros,
        'imc'                => $imc,
        'modo'               => $existe ? 'update' : 'insert',
        'telecita'           => ['triage_status' => $marcarTeleOK ? 'completado' : 'sin_cambio']
    ];

    http_response_code($existe ? 200 : 201);
    echo json_encode(['estado'=>'ok','mensaje'=>'Triaje guardado','triaje'=>$detalle]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
