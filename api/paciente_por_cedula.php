<?php
// api/paciente_por_cedula.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado'=>'error','mensaje'=>'Método no permitido']);
        exit;
    }

    $cedula = trim((string)($_GET['cedula'] ?? ''));
    if ($cedula === '') {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Parámetro cedula es obligatorio']);
        exit;
    }
    // Regla básica (ajústala si tu formato es distinto)
    if (!preg_match('/^\d{10,13}$/', $cedula)) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'Cédula inválida (Debe tener 10 dígitos)']);
        exit;
    }

    $usuario = new Usuario();
    $pac = $usuario->obtenerPacientePorCedula($cedula);

    if (!$pac) {
        http_response_code(404);
        echo json_encode(['estado'=>'no_encontrado','mensaje'=>'No existe un paciente con esa cédula']);
        exit;
    }

    // Valida rol de paciente (AJUSTA este valor al real)
    $ROL_PACIENTE = 30;   // <-- cámbialo al ID real de “paciente” en tu tabla
    if ((int)$pac['rol_id'] !== $ROL_PACIENTE) {
        http_response_code(404);
        echo json_encode(['estado'=>'no_encontrado','mensaje'=>'No existe un paciente con esa cédula']);
        exit;
    }

    echo json_encode([
        'estado'   => 'ok',
        'paciente' => [
            'id'        => (int)$pac['usu_id'],
            'nombre'    => $pac['usu_nombre'] ?? null,
            'apellido'  => $pac['usu_apellido'] ?? null,
            'correo'    => $pac['usu_correo'] ?? null,
            'cedula'    => $pac['cedula'],
        ]
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
