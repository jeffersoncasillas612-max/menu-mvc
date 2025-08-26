<?php
// api/origenes.php
header('Content-Type: application/json');
// Si la consumirás desde otro dominio, descomenta:
// header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../models/Cita.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $cita = new Cita();
    $lista = $cita->obtenerOrigenes(); // SELECT * FROM origen_cita

    // Normalizamos la salida a { id, nombre }
    $data = array_map(function($row) {
        return [
            'id'     => isset($row['origen_id']) ? (int)$row['origen_id'] : null,
            'nombre' => $row['nombre'] ?? null,
        ];
    }, $lista);

    // Filtro opcional por ?q=texto
    if (isset($_GET['q']) && $_GET['q'] !== '') {
        $q = mb_strtolower(trim($_GET['q']));
        $data = array_values(array_filter($data, function($r) use ($q) {
            return $r['nombre'] !== null && mb_stripos($r['nombre'], $q) !== false;
        }));
    }

    // Si mandan ?id=#
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        $uno = null;
        foreach ($data as $r) {
            if ((int)$r['id'] === $id) { $uno = $r; break; }
        }
        if ($uno === null) {
            http_response_code(404);
            echo json_encode(['estado' => 'error', 'mensaje' => 'Origen no encontrado']);
            exit;
        }
        echo json_encode(['estado' => 'ok', 'data' => $uno]);
        exit;
    }

    echo json_encode(['estado' => 'ok', 'total' => count($data), 'data' => $data]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno: '.$e->getMessage()]);
}
