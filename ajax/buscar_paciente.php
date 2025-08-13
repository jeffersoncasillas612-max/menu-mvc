<?php
require_once '../models/Cita.php';

if (isset($_GET['cedula'])) {
    $cedula = $_GET['cedula'];

    $modelo = new Cita();
    $paciente = $modelo->buscarPacientePorCedula($cedula);

    header('Content-Type: application/json');
    if ($paciente) {
        echo json_encode([
            'success' => true,
            'id' => $paciente['usu_id'],
            'nombre' => $paciente['usu_nombre'],
            'apellido' => $paciente['usu_apellido']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}
