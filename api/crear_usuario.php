<?php
// api/crear_usuario.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../libs/correo_bienvenida.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["estado" => "error", "mensaje" => "Método no permitido"]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(["estado" => "error", "mensaje" => "JSON inválido o vacío"]);
        exit;
    }

    $nombre          = trim($data['nombre'] ?? '');
    $apellido        = trim($data['apellido'] ?? '');
    $correo          = trim($data['correo'] ?? '');
    $cedula          = trim($data['cedula'] ?? '');
    $rol_id          = $data['rol_id'] ?? null;
    $especialidad_id = $data['especialidad_id'] ?? null;

    if ($nombre === '' || $apellido === '' || $correo === '' || $cedula === '' || empty($rol_id)) {
        http_response_code(400);
        echo json_encode(["estado" => "error", "mensaje" => "Todos los campos son obligatorios"]);
        exit;
    }

    // Regla: solo médicos (31) pueden tener especialidad
    if ((int)$rol_id !== 31) {
        $especialidad_id = null;
    }

    $usuario = new Usuario();

    // Duplicados
    if ($usuario->existeCedulaOCorreo($cedula, $correo)) {
        http_response_code(409);
        echo json_encode(["estado" => "error", "mensaje" => "La cédula o correo ya están registrados."]);
        exit;
    }

    // (Opcional) Si quieres transacción para revertir si falla correo:
    // $pdo = Database::getConnection(); // según tu config
    // $pdo->beginTransaction();

    $creado = $usuario->crear($nombre, $apellido, $correo, $cedula, $rol_id, $especialidad_id);

    if (!$creado) {
        // $pdo->rollBack(); // si usas transacción
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "No se pudo crear el usuario"]);
        exit;
    }

    // Enviar correo (igual que recuperación: si falla → 500)
    $enviado = enviarCorreoBienvenida($correo, $nombre, $apellido, $cedula);
    if (!$enviado) {
        // $pdo->rollBack(); // si quieres revertir creación cuando falle el correo
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error al enviar el correo de bienvenida"]);
        exit;
    }

    // $pdo->commit(); // si usas transacción
    echo json_encode(["estado" => "ok", "mensaje" => "Usuario creado y correo enviado"]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["estado" => "error", "mensaje" => "Error interno: " . $e->getMessage()]);
}
