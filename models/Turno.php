<?php
require_once 'config/database.php';

class Turno {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function obtenerTurnosPorMedico($medico_id) {
        $sql = "SELECT * FROM turno WHERE medico_id = :id ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), hora_inicio";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $medico_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function crearTurno($medico_id, $dia, $hora_inicio, $hora_fin) {
        $sql = "INSERT INTO turno (medico_id, dia_semana, hora_inicio, hora_fin)
                VALUES (:medico_id, :dia, :hora_inicio, :hora_fin)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':dia', $dia);
        $stmt->bindParam(':hora_inicio', $hora_inicio);
        $stmt->bindParam(':hora_fin', $hora_fin);
        return $stmt->execute();
    }

    public function eliminarTurnosPorDia($medico_id, $dia) {
        $sql = "DELETE FROM turno WHERE medico_id = :medico_id AND dia_semana = :dia";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':dia', $dia);
        return $stmt->execute();
    }
    
    
}
