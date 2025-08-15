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

// Consultas
$info_paciente = $modelo->obtenerInformacionPaciente($paciente_id);
$historial = $modelo->obtenerHistorialClinico($paciente_id);
$vacunas = $modelo->obtenerVacunas($paciente_id);
$citas = $modelo->obtenerCitasPorPaciente($paciente_id);

// Respuestas si no existen
$historial = $historial ?: "No se encontraron datos de historial clínico";
$vacunas = !empty($vacunas) ? $vacunas : "No se encontraron vacunas registradas";
$citas = !empty($citas) ? $citas : "No se encontraron citas registradas";

$response = [
    "estado" => "ok",
    "paciente" => $info_paciente,
    "historial" => $historial,
    "vacunas" => $vacunas,
    "citas" => $citas
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
