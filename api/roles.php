<?php
// api/roles.php
header('Content-Type: application/json');
// (Opcional) CORS si la consumirás desde otro dominio:
// header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $db  = new Database();
    $pdo = $db->getConnection();

    // Búsqueda opcional por nombre: ?q=medi
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    $sql  = "SELECT rol_id AS id, rol_nombre AS nombre FROM roles";
    $args = [];

    if ($q !== '') {
        $sql .= " WHERE rol_nombre LIKE :q";
        $args[':q'] = "%{$q}%";
    }

    $sql .= " ORDER BY nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);

    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['estado' => 'ok', 'data' => $roles]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno: '.$e->getMessage()]);
}
