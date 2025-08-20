<?php
// api/crear_usuario.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../libs/correo_bienvenida.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'msg' => 'Método no permitido']);
        exit;
    }

    // Aseguramos JSON válido
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'msg' => 'JSON inválido o vacío']);
        exit;
    }

    $nombre          = trim($input['nombre'] ?? '');
    $apellido        = trim($input['apellido'] ?? '');
    $correo          = trim($input['correo'] ?? '');
    $cedula          = trim($input['cedula'] ?? '');
    $rol_id          = $input['rol_id'] ?? null;
    $especialidad_id = $input['especialidad_id'] ?? null;

    // Validación básica
    if ($nombre === '' || $apellido === '' || $correo === '' || $cedula === '' || empty($rol_id)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'msg' => 'Todos los campos son obligatorios']);
        exit;
    }

    // (Opcional) Reglas de especialidad: solo médicos (rol_id = 31) pueden tener especialidad
    if ((int)$rol_id !== 31) {
        $especialidad_id = null;
    }

    // Validar duplicado
    $usuario = new Usuario();
    if ($usuario->existeCedulaOCorreo($cedula, $correo)) {
        echo json_encode(['estado' => 'error', 'msg' => 'La cédula o correo ya están registrados.']);
        exit;
    }

    // Crear usuario
    $creado = $usuario->crear($nombre, $apellido, $correo, $cedula, $rol_id, $especialidad_id);

    if (!$creado) {
        echo json_encode(['estado' => 'error', 'msg' => 'No se pudo crear el usuario']);
        exit;
    }

    // Intentar enviar correo, pero NO romper si falla
    $correoEnviado = enviarCorreoBienvenida($correo, $nombre, $apellido, $cedula);

    echo json_encode([
        'estado'          => 'ok',
        'msg'             => 'Usuario creado correctamente',
        'correo_enviado'  => $correoEnviado
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'msg' => 'Error interno: ' . $e->getMessage()]);
}
