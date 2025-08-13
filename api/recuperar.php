<?php
header("Content-Type: application/json");
require_once '../config/database.php';
require_once '../models/Usuario.php';
require_once '../libs/correo_recuperacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["estado" => "error", "mensaje" => "Método no permitido"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$correo = trim($data['correo'] ?? '');

if (empty($correo)) {
    http_response_code(400);
    echo json_encode(["estado" => "error", "mensaje" => "El correo es obligatorio"]);
    exit;
}

$usuario = new Usuario();
$datosUsuario = $usuario->obtenerPorCorreo($correo);

if (!$datosUsuario) {
    http_response_code(404);
    echo json_encode(["estado" => "error", "mensaje" => "No se encontró un usuario con ese correo"]);
    exit;
}

// 🔐 Generar token y expiración
$token = bin2hex(random_bytes(32));
$expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// 💾 Guardar token en la base de datos
$usuario->guardarTokenRecuperacion($datosUsuario['usu_id'], $token, $expira);

// 📬 Preparar correo
$nombreCompleto = $datosUsuario['usu_nombre'] . ' ' . $datosUsuario['usu_apellido'];
$linkRecuperacion = "https://tusitio.com/restablecer.php?token=$token"; // AJUSTA esto

$enviado = enviarCorreoRecuperacion($correo, $nombreCompleto, $linkRecuperacion);

if ($enviado) {
    echo json_encode(["estado" => "ok", "mensaje" => "Se ha enviado un correo con instrucciones"]);
} else {
    http_response_code(500);
    echo json_encode(["estado" => "error", "mensaje" => "Error al enviar el correo"]);
}
