<?php
// API: citas_medico_rango.php
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/Cita.php';

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'msg' => 'Método no permitido']);
        exit;
    }

    // Obtener y decodificar JSON
    $datos = json_decode(file_get_contents('php://input'), true);

    // Validar campos
    if (
        empty($datos['medico_id']) ||
        empty($datos['fecha_inicio']) ||
        empty($datos['fecha_fin'])
    ) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'msg' => 'Datos incompletos']);
        exit;
    }

    // Conectar BD
    $modeloCita = new Cita(); // ✅ SIN pasar parámetros


    // Obtener datos
    $citas = $modeloCita->obtenerCitasPorMedicoYRango(
        $datos['medico_id'],
        $datos['fecha_inicio'],
        $datos['fecha_fin']
    );

    echo json_encode([
        'estado' => 'ok',
        'citas' => $citas
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'msg' => 'Error interno: ' . $e->getMessage()]);
}
