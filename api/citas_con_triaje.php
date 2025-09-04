<?php
// api/citas_con_triaje.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Cita.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $citaModel = new Cita();
    $pdo = $citaModel->conn;

    $cita_id     = isset($_GET['cita_id']) ? (int)$_GET['cita_id'] : 0;
    $medico_id   = isset($_GET['medico_id']) ? (int)$_GET['medico_id'] : 0;
    $paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;
    $desde       = isset($_GET['desde']) ? trim($_GET['desde']) : null; // YYYY-MM-DD
    $hasta       = isset($_GET['hasta']) ? trim($_GET['hasta']) : null; // YYYY-MM-DD

    // Traer todas (opcional) + paginado
    $todas  = isset($_GET['todas']) ? (int)$_GET['todas'] : 0;
    $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit  = max(1, min(500, $limit));
    $offset = max(0, $offset);

    // SELECT base (sin JOIN a triaje; usamos EXISTS para evitar duplicados)
    $baseSelect = "
        SELECT
            c.cita_id                                   AS id,
            DATE(c.fecha)                                AS fecha,
            TIME(c.fecha)                                AS hora,
            c.motivo                                     AS motivo,
            CONCAT(p.usu_nombre, ' ', p.usu_apellido)    AS paciente,
            CONCAT(m.usu_nombre, ' ', m.usu_apellido)    AS medico,
            tc.nombre                                    AS tipo_cita,
            esp.nombre                                   AS especialidad,
            o.nombre                                     AS origen,
            ec.nombre                                    AS estado,
            CASE 
                WHEN EXISTS (SELECT 1 FROM triaje t WHERE t.cita_id = c.cita_id) 
                THEN 1 ELSE 0 
            END                                          AS tiene_triaje
        FROM cita c
        INNER JOIN usuarios p     ON p.usu_id = c.paciente_id
        INNER JOIN usuarios m     ON m.usu_id = c.medico_id
        LEFT  JOIN tipo_cita tc   ON tc.tipo_cita_id    = c.tipo_cita_id
        LEFT  JOIN especialidad esp ON esp.especialidad_id = c.especialidad_id
        LEFT  JOIN origen_cita o  ON o.origen_id        = c.origen_id
        LEFT  JOIN estado_cita ec ON ec.estado_id       = c.estado_id
    ";

    // --- Modo 1: una sola cita por id
    if ($cita_id > 0) {
        $sql = $baseSelect . " WHERE c.cita_id = :id LIMIT 1";
        $st  = $pdo->prepare($sql);
        $st->execute([':id' => $cita_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['estado'=>'error','mensaje'=>'Cita no encontrada']);
            exit;
        }

        echo json_encode(['estado'=>'ok','data'=>$row]);
        exit;
    }

    // --- Modo 2: por médico + rango
    if ($medico_id > 0 && $desde && $hasta) {
        $d1 = DateTime::createFromFormat('Y-m-d', $desde);
        $d2 = DateTime::createFromFormat('Y-m-d', $hasta);
        if (!$d1 || !$d2) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Rango de fechas inválido']);
            exit;
        }

        $sql = $baseSelect . "
            WHERE c.medico_id = :m
              AND c.fecha BETWEEN :d1 AND :d2
            ORDER BY c.fecha DESC
        ";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':m'  => $medico_id,
            ':d1' => $desde.' 00:00:00',
            ':d2' => $hasta.' 23:59:59'
        ]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['estado'=>'ok','data'=>$rows]);
        exit;
    }

    // --- Modo 3: por paciente (+ rango opcional)
    if ($paciente_id > 0) {
        $where = " WHERE c.paciente_id = :p ";
        $args  = [':p' => $paciente_id];

        if ($desde && $hasta) {
            $d1 = DateTime::createFromFormat('Y-m-d', $desde);
            $d2 = DateTime::createFromFormat('Y-m-d', $hasta);
            if (!$d1 || !$d2) {
                http_response_code(400);
                echo json_encode(['estado'=>'error','mensaje'=>'Rango de fechas inválido']);
                exit;
            }
            $where .= " AND c.fecha BETWEEN :d1 AND :d2 ";
            $args[':d1'] = $desde.' 00:00:00';
            $args[':d2'] = $hasta.' 23:59:59';
        }

        $sql = $baseSelect . $where . " ORDER BY c.fecha DESC";
        $st = $pdo->prepare($sql);
        $st->execute($args);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['estado'=>'ok','data'=>$rows]);
        exit;
    }

    // --- Modo 4: todas (opcionalmente por rango) + paginado
    if ($todas === 1 || ($cita_id === 0 && $medico_id === 0 && $paciente_id === 0)) {
        $where = " WHERE 1=1 ";
        $args  = [];

        if ($desde && $hasta) {
            $d1 = DateTime::createFromFormat('Y-m-d', $desde);
            $d2 = DateTime::createFromFormat('Y-m-d', $hasta);
            if (!$d1 || !$d2) {
                http_response_code(400);
                echo json_encode(['estado'=>'error','mensaje'=>'Rango de fechas inválido']);
                exit;
            }
            $where .= " AND c.fecha BETWEEN :d1 AND :d2 ";
            $args[':d1'] = $desde.' 00:00:00';
            $args[':d2'] = $hasta.' 23:59:59';
        }

        $sql = $baseSelect . $where . " ORDER BY c.fecha DESC LIMIT :limit OFFSET :offset";
        $st = $pdo->prepare($sql);
        foreach ($args as $k => $v) { $st->bindValue($k, $v); }
        $st->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'estado' => 'ok',
            'meta'   => ['limit'=>$limit, 'offset'=>$offset, 'count'=>count($rows)],
            'data'   => $rows
        ]);
        exit;
    }

    // Si no coincide ningún modo
    http_response_code(400);
    echo json_encode([
        'estado'  => 'error',
        'mensaje' => 'Envía cita_id o (medico_id + desde + hasta) o paciente_id, o usa todas=1'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
