<?php
header("Content-Type: application/json");
require_once '../config/database.php';
require_once '../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $correo = $input['correo'] ?? '';
    $clave = $input['contrasena'] ?? '';

    $usuario = new Usuario();
    $resultado = $usuario->verificar($correo, $clave);

    if ($resultado) {
        echo json_encode([
            'estado' => $resultado['usu_primera_vez'] == 1 ? 'primer_ingreso' : 'ok',
            'usuario' => $resultado
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Credenciales inválidas'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
