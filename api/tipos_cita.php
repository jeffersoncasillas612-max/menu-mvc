<?php
// api/tipos_cita.php
header('Content-Type: application/json');
// Si consumirás desde otro dominio, puedes habilitar CORS:
// header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../models/Cita.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $cita = new Cita();
    $lista = $cita->obtenerTiposCita(); // SELECT * FROM tipo_cita

    // Normalizamos salida a { id, nombre }
    $data = array_map(function($row) {
        return [
            'id'     => isset($row['tipo_cita_id']) ? (int)$row['tipo_cita_id'] : null,
            'nombre' => $row['nombre'] ?? null,
        ];
    }, $lista);

    // Filtro opcional por nombre: ?q=texto
    if (isset($_GET['q']) && $_GET['q'] !== '') {
        $q = mb_strtolower(trim($_GET['q']));
        $data = array_values(array_filter($data, function($r) use ($q) {
            return $r['nombre'] !== null && mb_stripos($r['nombre'], $q) !== false;
        }));
    }

    // Obtener uno por id: ?id=#
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        $uno = null;
        foreach ($data as $r) {
            if ((int)$r['id'] === $id) { $uno = $r; break; }
        }
        if ($uno === null) {
            http_response_code(404);
            echo json_encode(['estado' => 'error', 'mensaje' => 'Tipo de cita no encontrado']);
            exit;
        }
        echo json_encode(['estado' => 'ok', 'data' => $uno]);
        exit;
    }

    echo json_encode(['estado' => 'ok', 'total' => count($data), 'data' => $data]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno: ' . $e->getMessage()]);
}
