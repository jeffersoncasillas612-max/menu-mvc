<?php
// api/medico_por_cedula.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $cedula = isset($_GET['cedula']) ? trim((string)$_GET['cedula']) : '';
    if ($cedula === '') {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'El parámetro cedula es obligatorio']);
        exit;
    }

    // Consulta usando tu método existente
    $u   = new Usuario();
    $row = $u->obtenerPacientePorCedula($cedula); // trae cualquier usuario por cédula

    if (!$row) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'No se encontró un usuario con esa cédula']);
        exit;
    }

    // Validar que sea médico
    if ((int)$row['rol_id'] !== 31) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'El usuario encontrado no tiene rol de médico']);
        exit;
    }

    // OK
    echo json_encode([
        'estado'     => 'ok',
        'medico_id'  => (int)$row['usu_id'],
        'medico'     => [
            'id'       => (int)$row['usu_id'],
            'nombre'   => $row['usu_nombre']  ?? null,
            'apellido' => $row['usu_apellido']?? null,
            'correo'   => $row['usu_correo']  ?? null,
            'cedula'   => $row['cedula']      ?? $cedula,
        ]
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
