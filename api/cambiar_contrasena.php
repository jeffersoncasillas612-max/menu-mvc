<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config/database.php';
require_once '../models/Usuario.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar datos
if (!isset($data['usu_id'], $data['nueva_contrasena'])) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Faltan datos obligatorios.']);
    exit;
}

$usu_id = $data['usu_id'];
$nuevaHash = hash('sha256', $data['nueva_contrasena']);

// Conexión a la base
$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

// Actualizar contraseña y marcar como "no es primera vez"
if ($usuario->actualizarContrasenaYPrimeraVez($usu_id, $nuevaHash, 0)) {
    echo json_encode([
        'estado' => 'ok',
        'mensaje' => 'Contraseña actualizada correctamente.'
    ]);
} else {
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'No se pudo actualizar la contraseña.'
    ]);
}
?>
