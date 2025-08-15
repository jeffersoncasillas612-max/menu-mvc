<?php
// api/especialidades.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Cita.php';

try {
    // Verificar método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'msg' => 'Método no permitido']);
        exit;
    }

    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();
    $citaModel = new Cita($conn);

    // Obtener especialidades
    $especialidades = $citaModel->obtenerEspecialidades();

    echo json_encode([
        'estado' => 'ok',
        'especialidades' => $especialidades
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'msg' => 'Error interno: ' . $e->getMessage()]);
}
