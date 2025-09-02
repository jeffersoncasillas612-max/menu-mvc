<?php
// api/registrar_diagnostico.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Cita.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'JSON inválido']);
        exit;
    }

    // -------- Helpers
    $str = fn($v) => is_string($v) ? trim($v) : '';
    $int = fn($v) => ($v === '' || $v === null) ? null : (int)$v;

    // Acepta: "Y-m-d", "Y-m-d H:i", "Y-m-d H:i:s"
    $parseDateTime = function (?string $in): ?string {
        if (!$in) return null;
        $in = trim($in);
        if ($in === '') return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $in))            $in .= ' 00:00:00';
        elseif (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $in)) $in .= ':00';
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $in)) return null;

        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $in);
        return $dt ? $dt->format('Y-m-d H:i:s') : null;
    };

    // Acepta solo fecha "Y-m-d"
    $parseDate = function (?string $in): ?string {
        if (!$in) return null;
        $in = trim($in);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $in)) return null;
        $dt = DateTime::createFromFormat('Y-m-d', $in);
        return $dt ? $dt->format('Y-m-d') : null;
    };

    // --------- Entradas obligatorias
    $cita_id     = (int)($body['cita_id'] ?? 0);
    $diagnostico = $str($body['diagnostico'] ?? '');

    // --------- Entradas opcionales comunes
    $tratamiento_text = $str($body['tratamiento'] ?? '');        // Texto libre (compatibilidad)
    $fecha_sql        = $parseDateTime($body['fecha'] ?? null) ?? date('Y-m-d H:i:s');

    // --------- Tratamiento estructurado (nuevos campos en `consulta`)
    $t_nombre        = $str($body['trat_nombre'] ?? '');
    $t_desc          = $str($body['trat_descripcion'] ?? '');
    $t_inicio        = $parseDate($body['trat_fecha_inicio'] ?? null);
    $t_frec_text     = $str($body['trat_frecuencia_text'] ?? '');
    $t_ses_tot       = $int($body['trat_sesiones_totales'] ?? null);
    $t_ses_real      = $int($body['trat_sesiones_realizadas'] ?? 0);
    $t_duracion_dias = $int($body['trat_duracion_dias'] ?? null);
    $t_dosis         = $str($body['trat_dosis'] ?? '');
    $t_via           = $str($body['trat_via_administracion'] ?? '');
    $t_obs           = $str($body['trat_observaciones'] ?? '');
    $t_estado        = $str($body['trat_estado'] ?? 'activo'); // activo|completado|suspendido

    // --------- Validaciones mínimas
    if ($cita_id <= 0 || $diagnostico === '') {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'cita_id y diagnostico son obligatorios']);
        exit;
    }

    // Si el cliente envía cualquier campo de tratamiento estructurado,
    // exigimos mínimos: nombre, descripción, fecha_inicio y al menos
    // (frecuencia_text o sesiones_totales o duracion_dias)
    $hay_trat_estruct = (
        $t_nombre !== '' || $t_desc !== '' || $t_inicio !== null ||
        $t_frec_text !== '' || $t_ses_tot !== null || $t_duracion_dias !== null ||
        $t_dosis !== '' || $t_via !== '' || $t_obs !== '' || ($t_estado !== '' && $t_estado !== 'activo') ||
        $t_ses_real !== 0
    );

    if ($hay_trat_estruct) {
        if ($t_nombre === '' || $t_desc === '' || $t_inicio === null) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Tratamiento: nombre, descripción y fecha_inicio son obligatorios cuando se registra tratamiento.']);
            exit;
        }
        if ($t_frec_text === '' && $t_ses_tot === null && $t_duracion_dias === null) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Tratamiento: especifica frecuencia_text, o sesiones_totales, o duracion_dias (al menos uno).']);
            exit;
        }
        if (!in_array($t_estado, ['activo','completado','suspendido'], true)) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'trat_estado inválido (usa: activo|completado|suspendido)']);
            exit;
        }
        if ($t_ses_tot !== null && $t_ses_tot < 0) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'trat_sesiones_totales no puede ser negativo']);
            exit;
        }
        if ($t_ses_real !== null && $t_ses_real < 0) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'trat_sesiones_realizadas no puede ser negativo']);
            exit;
        }
        if ($t_duracion_dias !== null && $t_duracion_dias < 0) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'trat_duracion_dias no puede ser negativo']);
            exit;
        }
    }

    $citaModel = new Cita();
    $pdo = $citaModel->conn;

    // 1) Verificar existencia de la cita
    $st = $pdo->prepare("SELECT cita_id, estado_id FROM cita WHERE cita_id = :id LIMIT 1");
    $st->execute([':id'=>$cita_id]);
    $cita = $st->fetch(PDO::FETCH_ASSOC);
    if (!$cita) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'Cita no encontrada']);
        exit;
    }

    $pdo->beginTransaction();

    // 2) ¿Ya existe consulta?
    $st = $pdo->prepare("SELECT consulta_id FROM consulta WHERE cita_id = :id LIMIT 1");
    $st->execute([':id' => $cita_id]);
    $existe = $st->fetch(PDO::FETCH_ASSOC);

    // Bind comunes
    $bind = [
        ':diag' => $diagnostico,
        ':trat' => ($tratamiento_text !== '' ? $tratamiento_text : null),
        ':fec'  => $fecha_sql,

        ':tnom' => ($hay_trat_estruct ? $t_nombre : null),
        ':tdes' => ($hay_trat_estruct ? $t_desc : null),
        ':tini' => ($hay_trat_estruct ? $t_inicio : null),
        ':tfre' => ($hay_trat_estruct ? ($t_frec_text !== '' ? $t_frec_text : null) : null),
        ':tset' => ($hay_trat_estruct ? $t_ses_tot : null),
        ':tser' => ($hay_trat_estruct ? $t_ses_real : 0),
        ':tdia' => ($hay_trat_estruct ? $t_duracion_dias : null),
        ':tdos' => ($hay_trat_estruct ? ($t_dosis !== '' ? $t_dosis : null) : null),
        ':tvia' => ($hay_trat_estruct ? ($t_via !== '' ? $t_via : null) : null),
        ':tobs' => ($hay_trat_estruct ? ($t_obs !== '' ? $t_obs : null) : null),
        ':test' => ($hay_trat_estruct ? $t_estado : 'activo'),
    ];

    if ($existe) {
        // UPDATE
        $sql = "
            UPDATE consulta
               SET diagnostico = :diag,
                   tratamiento = :trat,
                   fecha       = :fec,
                   trat_nombre              = :tnom,
                   trat_descripcion         = :tdes,
                   trat_fecha_inicio        = :tini,
                   trat_frecuencia_text     = :tfre,
                   trat_sesiones_totales    = :tset,
                   trat_sesiones_realizadas = :tser,
                   trat_duracion_dias       = :tdia,
                   trat_dosis               = :tdos,
                   trat_via_administracion  = :tvia,
                   trat_observaciones       = :tobs,
                   trat_estado              = :test,
                   updated_at = NOW()
             WHERE consulta_id = :cid
        ";
        $bind[':cid'] = (int)$existe['consulta_id'];

        $ok = $pdo->prepare($sql)->execute($bind);
        $consulta_id = (int)$existe['consulta_id'];
        $accion = 'actualizado';
    } else {
        // INSERT
        $sql = "
            INSERT INTO consulta (
                cita_id, diagnostico, tratamiento, fecha,
                trat_nombre, trat_descripcion, trat_fecha_inicio, trat_frecuencia_text,
                trat_sesiones_totales, trat_sesiones_realizadas, trat_duracion_dias,
                trat_dosis, trat_via_administracion, trat_observaciones, trat_estado
            ) VALUES (
                :cita, :diag, :trat, :fec,
                :tnom, :tdes, :tini, :tfre,
                :tset, :tser, :tdia,
                :tdos, :tvia, :tobs, :test
            )
        ";
        $bindIns = $bind + [ ':cita' => $cita_id ];
        $ok = $pdo->prepare($sql)->execute($bindIns);
        $consulta_id = (int)$pdo->lastInsertId();
        $accion = 'creado';
    }

    if (!$ok) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['estado'=>'error','mensaje'=>'No se pudo registrar la consulta']);
        exit;
    }

    // 3) Marcar la cita como atendida (3) si aún no lo está
    if ((int)$cita['estado_id'] !== 3) {
        $pdo->prepare("UPDATE cita SET estado_id = 3 WHERE cita_id = :id")->execute([':id'=>$cita_id]);
    }

    $pdo->commit();

    echo json_encode([
        'estado'       => 'ok',
        'mensaje'      => "Consulta $accion correctamente",
        'consulta_id'  => $consulta_id,
        'cita_id'      => $cita_id,
        'fecha'        => $fecha_sql,
        'tratamiento'  => $hay_trat_estruct ? [
            'nombre'               => $t_nombre,
            'descripcion'          => $t_desc,
            'fecha_inicio'         => $t_inicio,
            'frecuencia_text'      => $t_frec_text ?: null,
            'sesiones_totales'     => $t_ses_tot,
            'sesiones_realizadas'  => $t_ses_real,
            'duracion_dias'        => $t_duracion_dias,
            'dosis'                => $t_dosis ?: null,
            'via_administracion'   => $t_via ?: null,
            'observaciones'        => $t_obs ?: null,
            'estado'               => $t_estado,
        ] : null
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
