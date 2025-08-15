<?php
// api/medicos_por_especialidad.php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Cita.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'msg' => 'Método no permitido']);
        exit;
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    
    if (empty($datos['especialidad_id'])) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'msg' => 'Falta el ID de especialidad']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();
    $modelo = new Cita($conn);

    $medicos = $modelo->obtenerMedicosPorEspecialidad($datos['especialidad_id']);

    echo json_encode(['estado' => 'ok', 'medicos' => $medicos]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'msg' => 'Error interno: ' . $e->getMessage()]);
}
