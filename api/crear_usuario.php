<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../models/Usuario.php';
require_once '../libs/correo_bienvenida.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'msg' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $correo = trim($input['correo'] ?? '');
    $cedula = trim($input['cedula'] ?? '');
    $rol_id = $input['rol_id'] ?? null;
    $especialidad_id = $input['especialidad_id'] ?? null;

    // Validación básica
    if (!$nombre || !$apellido || !$correo || !$cedula || !$rol_id) {
        echo json_encode(['estado' => 'error', 'msg' => 'Todos los campos son obligatorios']);
        exit;
    }

    // Validar duplicado
    $usuario = new Usuario();
    if ($usuario->existeCedulaOCorreo($cedula, $correo)) {
        echo json_encode(['estado' => 'error', 'msg' => 'La cédula o correo ya están registrados.']);
        exit;
    }

    // Validar lógica de especialidad
    if ($rol_id != 31 && !empty($especialidad_id)) {
        echo json_encode([
            'estado' => 'error',
            'msg' => 'Solo los usuarios con rol de médico (rol_id = 31) pueden tener especialidad.'
        ]);
        exit;
    }



    // Crear usuario
    $creado = $usuario->crear($nombre, $apellido, $correo, $cedula, $rol_id, $especialidad_id);

    if ($creado) {
        // Enviar correo de bienvenida
        $correoEnviado = enviarCorreoBienvenida($correo, "$nombre $apellido", $cedula);

        echo json_encode([
            'estado' => 'ok',
            'msg' => 'Usuario creado correctamente',
            'correo_enviado' => $correoEnviado
        ]);
    } else {
        echo json_encode(['estado' => 'error', 'msg' => 'No se pudo crear el usuario']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'msg' => 'Error interno: ' . $e->getMessage()]);
}
