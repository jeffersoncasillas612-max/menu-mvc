<?php
// api/citas_medico_desde_hoy.php
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

    // Parámetros
    $medico_id = isset($_GET['medico_id']) ? (int)$_GET['medico_id'] : 0;
    $desde     = isset($_GET['desde']) ? trim($_GET['desde']) : date('Y-m-d'); // por defecto: hoy
    $hasta     = isset($_GET['hasta']) ? trim($_GET['hasta']) : null;          // opcional

    // Paginación (opcional)
    $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit  = max(1, min(500, $limit));
    $offset = max(0, $offset);

    if ($medico_id <= 0) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'medico_id es obligatorio']);
        exit;
    }

    // Validar médico existe y es rol 31 (médico)
    $st = $pdo->prepare("SELECT usu_id FROM usuarios WHERE usu_id = :id AND rol_id = 31 AND usu_estado = 1");
    $st->execute([':id'=>$medico_id]);
    if (!$st->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'Médico no válido']);
        exit;
    }

    // Validar fechas
    $d1 = DateTime::createFromFormat('Y-m-d', $desde);
    if (!$d1 || $d1->format('Y-m-d') !== $desde) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Formato de "desde" inválido (use YYYY-MM-DD)']);
        exit;
    }
    $desdeIni = $desde . ' 00:00:00';

    $args = [
        ':m'  => $medico_id,
        ':d1' => $desdeIni
    ];

    $rangoSQL = " c.fecha >= :d1 ";
    if (!empty($hasta)) {
        $d2 = DateTime::createFromFormat('Y-m-d', $hasta);
        if (!$d2 || $d2->format('Y-m-d') !== $hasta) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Formato de "hasta" inválido (use YYYY-MM-DD)']);
            exit;
        }
        $args[':d2'] = $hasta . ' 23:59:59';
        $rangoSQL = " c.fecha BETWEEN :d1 AND :d2 ";
    }

    // SELECT (sin JOIN a triaje; usamos EXISTS para bandera 0/1)
    $sql = "
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
        INNER JOIN usuarios p       ON p.usu_id = c.paciente_id
        INNER JOIN usuarios m       ON m.usu_id = c.medico_id
        LEFT  JOIN tipo_cita tc     ON tc.tipo_cita_id    = c.tipo_cita_id
        LEFT  JOIN especialidad esp ON esp.especialidad_id = c.especialidad_id
        LEFT  JOIN origen_cita o    ON o.origen_id        = c.origen_id
        LEFT  JOIN estado_cita ec   ON ec.estado_id       = c.estado_id
        WHERE c.medico_id = :m
          AND $rangoSQL
        ORDER BY c.fecha ASC
        LIMIT :limit OFFSET :offset
    ";

    $st = $pdo->prepare($sql);
    foreach ($args as $k=>$v) { $st->bindValue($k, $v); }
    $st->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $st->bindValue(':offset', $offset, PDO::PARAM_INT);
    $st->execute();

    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'estado' => 'ok',
        'meta'   => [
            'medico_id' => $medico_id,
            'desde'     => $desde,
            'hasta'     => $hasta,
            'limit'     => $limit,
            'offset'    => $offset,
            'count'     => count($rows)
        ],
        'data'   => $rows
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
