<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']['usu_id'])) {
    echo json_encode([]);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$cita_id = intval($_GET['id']);

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "SELECT 
    c.cita_id,
    CONCAT(p.usu_nombre, ' ', p.usu_apellido) AS paciente,
    CONCAT(m.usu_nombre, ' ', m.usu_apellido) AS medico,
    esp.nombre AS especialidad,
    tc.nombre AS tipo_cita,
    pr.nombre AS prioridad,
    ori.nombre AS origen,
    ec.nombre AS estado,
    c.motivo,
    c.fecha AS fecha_cita,
    DATE_FORMAT(c.fecha, '%r') AS hora_cita -- formato 12h con AM/PM
FROM cita c
INNER JOIN usuarios p ON c.paciente_id = p.usu_id
INNER JOIN usuarios m ON c.medico_id = m.usu_id
LEFT JOIN especialidad esp ON c.especialidad_id = esp.especialidad_id
LEFT JOIN tipo_cita tc ON c.tipo_cita_id = tc.tipo_cita_id
LEFT JOIN prioridad pr ON c.prioridad_id = pr.prioridad_id
LEFT JOIN origen_cita ori ON c.origen_id = ori.origen_id
LEFT JOIN estado_cita ec ON c.estado_id = ec.estado_id
WHERE c.cita_id = ?;

";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$cita_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($detalle ?: []);
} catch (Exception $e) {
    echo json_encode([]);
}
