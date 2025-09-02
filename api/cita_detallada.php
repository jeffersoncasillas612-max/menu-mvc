<?php
// api/cita_detallada.php
header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *'); // <-- habilita CORS si lo necesitas

require_once __DIR__ . '/../models/Cita.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    $cita_id = isset($_GET['cita_id']) ? (int)$_GET['cita_id'] : 0;
    if ($cita_id <= 0) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Parámetro cita_id inválido']);
        exit;
    }

    $TRIAGE_BASE = 'https://menu-mvc.onrender.com/views/tele/triage.php'; // <-- tu base actual

    $citaModel = new Cita();
    $pdo = $citaModel->conn;

    // ========== 1) CITA + PACIENTE + MÉDICO + CATÁLOGOS ==========
    $st = $pdo->prepare("
        SELECT
            c.cita_id, c.paciente_id, c.medico_id, c.fecha, c.motivo,
            c.tipo_cita_id, c.especialidad_id, c.prioridad_id, c.origen_id, c.estado_id, c.turno_id,
            tc.nombre  AS tipo_cita,
            esp.nombre AS especialidad,
            pr.nombre  AS prioridad,
            o.nombre   AS origen,
            ec.nombre  AS estado,
            p.usu_nombre AS pac_nombre,  p.usu_apellido AS pac_apellido,  p.usu_correo AS pac_correo,  p.usu_cedula AS pac_cedula,
            m.usu_nombre AS med_nombre,  m.usu_apellido AS med_apellido,  m.usu_correo AS med_correo
        FROM cita c
        INNER JOIN usuarios p ON p.usu_id = c.paciente_id
        INNER JOIN usuarios m ON m.usu_id = c.medico_id
        LEFT JOIN tipo_cita    tc  ON tc.tipo_cita_id    = c.tipo_cita_id
        LEFT JOIN especialidad esp ON esp.especialidad_id= c.especialidad_id
        LEFT JOIN prioridad    pr  ON pr.prioridad_id    = c.prioridad_id
        LEFT JOIN origen_cita  o   ON o.origen_id        = c.origen_id
        LEFT JOIN estado_cita  ec  ON ec.estado_id       = c.estado_id
        WHERE c.cita_id = :id
        LIMIT 1
    ");
    $st->execute([':id'=>$cita_id]);
    $cita = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cita) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'Cita no encontrada']);
        exit;
    }

    // Normalización de fecha / hora
    $ts   = strtotime($cita['fecha']);
    $fecha = $ts ? date('Y-m-d', $ts) : null;
    $hora  = $ts ? date('H:i:s', $ts) : null;

    // ========== 2) TRIAJE (último si hubiese más de uno) ==========
    $st = $pdo->prepare("
        SELECT triaje_id, cita_id, peso, talla_cm, temperatura_c,
               presion_sistolica, presion_diastolica, fc_lpm, fr_rpm, sato2_pct,
               sintomas, alergias, antecedentes, medicacion_actual, otros,
               created_at
        FROM triaje
        WHERE cita_id = :id
        ORDER BY triaje_id DESC
        LIMIT 1
    ");
    $st->execute([':id'=>$cita_id]);
    $triaje = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    // ========== 3) TELECITA (si aplica) ==========
    $st = $pdo->prepare("
        SELECT meeting_url, triage_token, triage_status, created_at, updated_at
        FROM telecita
        WHERE cita_id = :id
        LIMIT 1
    ");
    $st->execute([':id'=>$cita_id]);
    $tele = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($tele && !empty($tele['triage_token'])) {
        $tele['triage_url'] = $TRIAGE_BASE . '?token=' . $tele['triage_token'];
    }

    // ========== 4) CONSULTA (última si hubiese más de una) ==========
    $st = $pdo->prepare("
        SELECT consulta_id, cita_id, diagnostico, tratamiento, fecha
        FROM consulta
        WHERE cita_id = :id
        ORDER BY consulta_id DESC
        LIMIT 1
    ");
    $st->execute([':id'=>$cita_id]);
    $consulta = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    // ========== 5) RECETAS (de esa consulta) + MEDICAMENTOS ==========
    $recetas = [];
    if ($consulta) {
        $stR = $pdo->prepare("
            SELECT receta_id, consulta_id, fecha, indicaciones
            FROM receta
            WHERE consulta_id = :con
            ORDER BY receta_id ASC
        ");
        $stR->execute([':con' => $consulta['consulta_id']]);
        $recetasRows = $stR->fetchAll(PDO::FETCH_ASSOC);

        if ($recetasRows) {
            // Traemos todas las líneas de medicamentos y agrupamos por receta
            $ids = array_map(fn($r)=> (int)$r['receta_id'], $recetasRows);
            $in  = implode(',', array_fill(0, count($ids), '?'));

            $stRM = $pdo->prepare("
                SELECT rm.receta_id, rm.medicamento_id, m.nombre,
                       rm.dosis, rm.frecuencia, rm.duracion
                FROM receta_medicamento rm
                INNER JOIN medicamento m ON m.medicamento_id = rm.medicamento_id
                WHERE rm.receta_id IN ($in)
                ORDER BY rm.receta_id ASC, rm.medicamento_id ASC
            ");
            $stRM->execute($ids);
            $lineas = $stRM->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($lineas as $ln) {
                $rid = (int)$ln['receta_id'];
                $map[$rid][] = [
                    'medicamento_id' => (int)$ln['medicamento_id'],
                    'nombre'         => $ln['nombre'],
                    'dosis'          => $ln['dosis'],
                    'frecuencia'     => $ln['frecuencia'],
                    'duracion'       => $ln['duracion'],
                ];
            }

            foreach ($recetasRows as $r) {
                $rid = (int)$r['receta_id'];
                $recetas[] = [
                    'receta_id'   => $rid,
                    'fecha'       => $r['fecha'],
                    'indicaciones'=> $r['indicaciones'],
                    'medicamentos'=> $map[$rid] ?? []
                ];
            }
        }
    }

    // ========== 6) FACTURA (si existe) ==========
    $st = $pdo->prepare("
        SELECT factura_id, total, fecha
        FROM factura
        WHERE cita_id = :id
        ORDER BY factura_id DESC
        LIMIT 1
    ");
    $st->execute([':id'=>$cita_id]);
    $factura = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    // Modalidad (infiero en base a telecita)
    $modalidad = $tele ? 'online' : 'presencial';

    // ========== RESPUESTA UNIFICADA ==========
    echo json_encode([
        'estado' => 'ok',
        'cita' => [
            'id'            => (int)$cita['cita_id'],
            'modalidad'     => $modalidad,
            'fecha_hora'    => $cita['fecha'],
            'fecha'         => $fecha,
            'hora'          => $hora,
            'motivo'        => $cita['motivo'],
            'turno_id'      => $cita['turno_id'] ? (int)$cita['turno_id'] : null,
            'estado'        => ['id'=>(int)$cita['estado_id'],        'nombre'=>$cita['estado']],
            'tipo_cita'     => ['id'=>(int)$cita['tipo_cita_id'],     'nombre'=>$cita['tipo_cita']],
            'especialidad'  => ['id'=>(int)$cita['especialidad_id'],  'nombre'=>$cita['especialidad']],
            'prioridad'     => ['id'=>(int)$cita['prioridad_id'],     'nombre'=>$cita['prioridad']],
            'origen'        => ['id'=>(int)$cita['origen_id'],        'nombre'=>$cita['origen']],
            'paciente' => [
                'id'       => (int)$cita['paciente_id'],
                'nombres'  => $cita['pac_nombre'],
                'apellidos'=> $cita['pac_apellido'],
                'nombre_completo' => trim($cita['pac_nombre'].' '.$cita['pac_apellido']),
                'correo'   => $cita['pac_correo'],
                'cedula'   => $cita['pac_cedula']
            ],
            'medico' => [
                'id'       => (int)$cita['medico_id'],
                'nombres'  => $cita['med_nombre'],
                'apellidos'=> $cita['med_apellido'],
                'nombre_completo' => trim($cita['med_nombre'].' '.$cita['med_apellido']),
                'correo'   => $cita['med_correo']
            ],
        ],
        'triaje' => [
            'tiene_triaje' => (bool)$triaje,
            'datos'        => $triaje
        ],
        'telecita' => [
            'tiene_telecita' => (bool)$tele,
            'datos'          => $tele
        ],
        'consulta' => [
            'tiene_consulta' => (bool)$consulta,
            'datos'          => $consulta
        ],
        'recetas' => [
            'tiene_recetas' => count($recetas) > 0,
            'items'         => $recetas
        ],
        'factura' => [
            'tiene_factura' => (bool)$factura,
            'datos'         => $factura
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
