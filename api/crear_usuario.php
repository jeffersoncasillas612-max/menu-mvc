<?php
// 📄 API para crear un usuario (paciente, médico, etc.)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../models/Usuario.php';
require_once '../libs/correo_bienvenida.php'; // ✅ Importante

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["estado" => "error", "msg" => "Método no permitido"]);
    exit;
}

// 📥 Leer datos del cuerpo JSON
$datos = json_decode(file_get_contents("php://input"), true);

// 📌 Validar campos obligatorios
$nombre = trim($datos['nombre'] ?? '');
$apellido = trim($datos['apellido'] ?? '');
$correo = trim($datos['correo'] ?? '');
$cedula = trim($datos['cedula'] ?? '');
$rol_id = intval($datos['rol_id'] ?? 0);
$especialidad_id = $datos['especialidad_id'] ?? null;

// 🔍 Validación básica
if (!$nombre || !$apellido || !$correo || !$cedula || !$rol_id) {
    http_response_code(400);
    echo json_encode(["estado" => "error", "msg" => "Faltan campos requeridos"]);
    exit;
}

// 👨‍⚕️ Validar especialidad si es médico
if ($rol_id == 31 && empty($especialidad_id)) {
    http_response_code(400);
    echo json_encode(["estado" => "error", "msg" => "La especialidad es obligatoria para médicos"]);
    exit;
}

// 👮 Verificar duplicado
$modelo = new Usuario();
if ($modelo->existeCedulaOCorreo($cedula, $correo)) {
    http_response_code(409); // conflicto
    echo json_encode(["estado" => "error", "msg" => "La cédula o correo ya están registrados"]);
    exit;
}

// 🛠️ Crear usuario
$exito = $modelo->crear($nombre, $apellido, $correo, $cedula, $rol_id, $especialidad_id);

if ($exito) {
    // 📧 Enviar correo de bienvenida
    enviarCorreoBienvenida($correo, $nombre, $cedula);

    echo json_encode([
        "estado" => "ok",
        "msg" => "Usuario creado correctamente"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "estado" => "error",
        "msg" => "No se pudo registrar el usuario"
    ]);
}
