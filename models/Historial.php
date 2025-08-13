<?php
require_once __DIR__ . '/../config/database.php';

class Historial {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function obtenerHistorialCompleto($cedula) {
        // Primero obtenemos los datos del paciente
        $sqlPaciente = "SELECT usu_id, usu_nombre, usu_apellido FROM usuarios 
                        WHERE usu_cedula = :cedula AND rol_id = 30 AND usu_estado = 1";
        $stmt = $this->conn->prepare($sqlPaciente);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) return false;

        $usuario_id = $paciente['usu_id'];

        // Luego obtenemos el historial clínico
        $sqlHistorial = "SELECT antecedentes, enfermedades_cronicas, alergias, observaciones 
                         FROM historial_clinico WHERE usuario_id = :usuario_id";
        $stmt2 = $this->conn->prepare($sqlHistorial);
        $stmt2->bindParam(':usuario_id', $usuario_id);
        $stmt2->execute();
        $historial = $stmt2->fetch(PDO::FETCH_ASSOC);

        return [
            'nombre' => ucwords(strtolower($paciente['usu_nombre'] . ' ' . $paciente['usu_apellido'])),
            'antecedentes' => $historial['antecedentes'] ?? '',
            'enfermedades_cronicas' => $historial['enfermedades_cronicas'] ?? '',
            'alergias' => $historial['alergias'] ?? '',
            'observaciones' => $historial['observaciones'] ?? ''
        ];
    }
}
