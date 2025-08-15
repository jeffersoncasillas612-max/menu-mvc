<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["estado" => "error", "msg" => "Método no permitido"]);
    exit;
}

require_once '../models/Cita.php';

$input = json_decode(file_get_contents("php://input"), true);
$cedula = trim($input['cedula'] ?? '');

if (empty($cedula)) {
    echo json_encode(["estado" => "error", "msg" => "La cédula es obligatoria"]);
    exit;
}

$modelo = new Cita();
$paciente = $modelo->buscarPacientePorCedula($cedula);

if (!$paciente) {
    echo json_encode(["estado" => "error", "msg" => "Paciente no encontrado"]);
    exit;
}

$paciente_id = $paciente['usu_id'];

$response = [
    "estado" => "ok",
    "paciente" => $modelo->obtenerInformacionPaciente($paciente_id),
    "historial" => $modelo->obtenerHistorialClinico($paciente_id),
    "vacunas" => $modelo->obtenerVacunas($paciente_id),
    "citas" => $modelo->obtenerCitasPorPaciente($paciente_id)
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
