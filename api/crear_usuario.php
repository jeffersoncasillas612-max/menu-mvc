<?php
// api/crear_usuario.php
header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *'); // si lo necesitas

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
    $rol_id          = isset($data['rol_id']) ? (int)$data['rol_id'] : null;
    $especialidad_id = isset($data['especialidad_id']) ? (int)$data['especialidad_id'] : null;

    // Validaciones básicas
    if ($nombre === '' || $apellido === '' || $correo === '' || $cedula === '' || empty($rol_id)) {
        http_response_code(400);
        echo json_encode(["estado" => "error", "mensaje" => "Todos los campos son obligatorios"]);
        exit;
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["estado" => "error", "mensaje" => "Correo inválido"]);
        exit;
    }
    // Solo médicos (31) pueden tener especialidad
    if ($rol_id !== 31) {
        $especialidad_id = null;
    }

    $db  = new Database();
    $pdo = $db->getConnection();
    $usuario = new Usuario($pdo); // asumiendo que el modelo acepta pdo opcionalmente

    // Duplicados
    if ($usuario->existeCedulaOCorreo($cedula, $correo)) {
        http_response_code(409);
        echo json_encode(["estado" => "error", "mensaje" => "La cédula o correo ya están registrados."]);
        exit;
    }

    // Transacción para que crear + correo sea atómico (si lo deseas así)
    $pdo->beginTransaction();

    // Crear usuario: haz que el modelo retorne el ID (lastInsertId) o true/false + getter
    $usuario_id = $usuario->crearYRetornarId($nombre, $apellido, $correo, $cedula, $rol_id, $especialidad_id);
    // Ejemplo dentro del modelo:
    // public function crear(...) { ... $this->pdo->prepare(...)->execute(...); return (int)$this->pdo->lastInsertId(); }

    if (!$usuario_id) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "No se pudo crear el usuario"]);
        exit;
    }

    // Enviar correo
    if (!enviarCorreoBienvenida($correo, $nombre, $apellido, $cedula)) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error al enviar el correo de bienvenida"]);
        exit;
    }

    $pdo->commit();

    // ⬅️ DEVUELVE EL ID PARA USARLO EN LA APP
    echo json_encode([
        "estado"       => "ok",
        "mensaje"      => "Usuario creado y correo enviado",
        "usuario_id"   => (int)$usuario_id,
        "rol_id"       => (int)$rol_id,
        "especialidad" => $especialidad_id // null si no aplica
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["estado" => "error", "mensaje" => "Error interno: " . $e->getMessage()]);
}
