<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']['usu_id'])) {
    echo json_encode([]); // No hay sesión → no enviamos nada
    exit;
}

require_once '../config/database.php';

$medico_id = $_SESSION['usuario']['usu_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "SELECT cita_id, fecha, motivo 
            FROM cita 
            WHERE medico_id = ? AND estado_id = 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$medico_id]);

    $eventos = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $eventos[] = [
            'id'    => $row['cita_id'],
            'title' => $row['motivo'],
            'start' => $row['fecha'],
            'color' => '#007bff'
        ];
    }

    echo json_encode($eventos);
} catch (Exception $e) {
    echo json_encode([]); // En error, mejor devolver vacío
}
