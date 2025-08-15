<?php
// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../config/database.php';


class Cita {

    public $conn;

    public function __construct() {
        // Creamos la conexión al instanciar la clase
        $db = new Database();
        $this->conn = $db->getConnection();
    }


    public function buscarPacientePorCedula($cedula) {
        $sql = "SELECT usu_id, usu_nombre, usu_apellido 
                FROM usuarios 
                WHERE usu_cedula = :cedula AND rol_id = 30 AND usu_estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($paciente) {
            // Formatear nombre y apellido (primera letra en mayúscula, resto en minúscula)
            $paciente['usu_nombre'] = ucwords(strtolower($paciente['usu_nombre']));
            $paciente['usu_apellido'] = ucwords(strtolower($paciente['usu_apellido']));
        }
    
        return $paciente;
    }

    // Obtener médicos por especialidad
    public function obtenerMedicosPorEspecialidad($especialidad_id) {
        $sql = "SELECT usu_id, usu_nombre, usu_apellido 
                FROM usuarios 
                WHERE rol_id = 31 AND usu_estado = 1 AND especialidad_id = :esp";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':esp', $especialidad_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEspecialidades() {
        $sql = "SELECT * FROM especialidad";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function obtenerTiposCita() {
        $sql = "SELECT * FROM tipo_cita";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPrioridades() {
        $sql = "SELECT * FROM prioridad";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerOrigenes() {
        $sql = "SELECT * FROM origen_cita";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    // Obtener turno por médico y día
    public function obtenerTurnoPorMedicoYDia($medico_id, $dia_semana) {
        $sql = "SELECT hora_inicio, hora_fin FROM turno 
                WHERE medico_id = ? AND dia_semana = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$medico_id, $dia_semana]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // 🔴 MUY IMPORTANTE
    }



    // Obtener horas ocupadas por fecha
    public function obtenerHorasOcupadas($medico_id, $fecha) {
        $sql = "SELECT TIME(fecha) as hora FROM cita WHERE medico_id = :medico_id AND DATE(fecha) = :fecha";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        $horas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatear a 'H:i' (ej: '08:30') para que coincida con los horarios generados
        return array_map(function($h) {
            return date('H:i', strtotime($h['hora']));
        }, $horas);
    }


    public function guardarCita($data) {
        $sql = "INSERT INTO cita 
            (paciente_id, medico_id, fecha, tipo_cita_id, especialidad_id, prioridad_id, origen_id, motivo, estado_id, turno_id)
            VALUES
            (:paciente_id, :medico_id, :fecha, :tipo_cita_id, :especialidad_id, :prioridad_id, :origen_id, :motivo, :estado_id, :turno_id)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }


    public function obtenerTurnoId($medico_id, $fecha) {
        $dia_semana = date('l', strtotime($fecha));
        $dias_es = [
            'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
        ];
        $dia = $dias_es[$dia_semana] ?? $dia_semana;

        $sql = "SELECT turno_id FROM turno WHERE medico_id = :medico_id AND dia_semana = :dia";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':dia', $dia);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['turno_id'] : null;
    }

        
        
    public function obtenerCitasPorPaciente($paciente_id) {
        $sql = "SELECT 
        c.cita_id,
        c.fecha,
        TIME(c.fecha) AS hora,
        c.motivo,
        tc.nombre AS tipo_cita,
        esp.nombre AS especialidad,
        pr.nombre AS prioridad,
        o.nombre AS origen,
        CONCAT(m.usu_nombre, ' ', m.usu_apellido) AS medico,
        ec.nombre AS estado_nombre,         -- nuevo campo
        c.estado_id                         -- opcional si quieres aplicar lógica por ID
    FROM cita c
    INNER JOIN tipo_cita tc ON c.tipo_cita_id = tc.tipo_cita_id
    LEFT JOIN especialidad esp ON c.especialidad_id = esp.especialidad_id
    LEFT JOIN prioridad pr ON c.prioridad_id = pr.prioridad_id
    LEFT JOIN origen_cita o ON c.origen_id = o.origen_id
    INNER JOIN usuarios m ON m.usu_id = c.medico_id
    INNER JOIN estado_cita ec ON c.estado_id = ec.estado_id -- nuevo JOIN
    WHERE c.paciente_id = ?
    ORDER BY c.fecha ASC;
    ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

    public function actualizarCitasPerdidas()
    {
        $db = $this->conn;
        $sql = "UPDATE cita 
                SET estado_id = 5
                WHERE estado_id = 1 
                AND TIMESTAMPDIFF(MINUTE, fecha, NOW()) > 5";
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }
    


    public function marcarCitaComoAtendida($cita_id)
    {
        $db = $this->conn;
        $sql = "UPDATE cita SET estado_id = 3 WHERE cita_id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$cita_id]);
    }



    public function obtenerDetalleCita($cita_id)
    {
        $sql = "SELECT 
                    c.*, 
                    CONCAT(p.usu_nombre, ' ', p.usu_apellido) AS paciente,
                    CONCAT(m.usu_nombre, ' ', m.usu_apellido) AS medico,
                    esp.nombre AS especialidad,
                    pr.nombre AS prioridad,
                    o.nombre AS origen,
                    tc.nombre AS tipo_cita,
                    ec.nombre AS estado
                FROM cita c
                INNER JOIN usuarios p ON c.paciente_id = p.usu_id
                INNER JOIN usuarios m ON c.medico_id = m.usu_id
                LEFT JOIN especialidad esp ON c.especialidad_id = esp.especialidad_id
                LEFT JOIN prioridad pr ON c.prioridad_id = pr.prioridad_id
                LEFT JOIN origen_cita o ON c.origen_id = o.origen_id
                LEFT JOIN tipo_cita tc ON c.tipo_cita_id = tc.tipo_cita_id
                LEFT JOIN estado_cita ec ON c.estado_id = ec.estado_id
                WHERE c.cita_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cita_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerInformacionPaciente($paciente_id)
    {
        $sql = "SELECT 
                    u.usu_id,
                    u.usu_cedula,
                    u.usu_nombre,
                    u.usu_apellido,
                    u.usu_correo,
                    r.rol_nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.rol_id
                WHERE u.usu_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerHistorialClinico($paciente_id) {
        $sql = "SELECT
                    hc.antecedentes,
                    hc.enfermedades_cronicas,
                    hc.alergias,
                    hc.observaciones
                FROM historial_clinico hc
                WHERE hc.usuario_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerVacunas($paciente_id) {
        $sql = "SELECT 
                    v.nombre,
                    v.fecha_aplicacion,
                    v.dosis
                FROM vacuna v
                WHERE v.usuario_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obtenerConsultas($paciente_id) {
        $sql = "SELECT 
                    c.diagnostico,
                    c.tratamiento,
                    c.fecha
                FROM consulta c
                WHERE c.cita_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function agregarVacuna($data)
    {
        $sql = "INSERT INTO vacuna (usuario_id, nombre, dosis, fecha_aplicacion)
                VALUES (:usuario_id, :nombre, :dosis, :fecha_aplicacion)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }


    public function registrarConsulta($data)
    {
        $sql = "INSERT INTO consulta (cita_id, diagnostico, tratamiento, fecha)
                VALUES (:cita_id, :diagnostico, :tratamiento, :fecha)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function guardarFactura($paciente_id, $cita_id, $total)
    {
        $sql = "INSERT INTO factura (paciente_id, cita_id, total, fecha)
                VALUES (:paciente_id, :cita_id, :total, NOW())";
    
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->bindParam(':cita_id', $cita_id);
        $stmt->bindParam(':total', $total);
        return $stmt->execute();
    }
    

    public function obtenerCitasPorMedicoYRango($medico_id, $inicio, $fin) {
        $sql = "SELECT c.*, 
                    u.usu_nombre AS nombre_paciente, 
                    u.usu_apellido AS apellido_paciente,
                    ec.nombre AS estado_nombre,
                    tc.nombre AS tipo_cita,
                    es.nombre AS especialidad,
                    p.nombre AS prioridad,
                    o.nombre AS origen
                FROM cita c
                INNER JOIN usuarios u ON c.paciente_id = u.usu_id
                INNER JOIN estado_cita ec ON c.estado_id = ec.estado_id
                INNER JOIN tipo_cita tc ON c.tipo_cita_id = tc.tipo_cita_id
                INNER JOIN especialidad es ON c.especialidad_id = es.especialidad_id
                INNER JOIN prioridad p ON c.prioridad_id = p.prioridad_id
                INNER JOIN origen_cita o ON c.origen_id = o.origen_id
                WHERE c.medico_id = :medico_id 
                AND c.fecha BETWEEN :inicio AND :fin
                ORDER BY c.fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id, PDO::PARAM_INT);
        $stmt->bindParam(':inicio', $inicio);
        $stmt->bindParam(':fin', $fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCitasPorEspecialidadYMedico($especialidad_id, $medico_id) {
        $sql = "SELECT c.*, 
                    u.usu_nombre AS nombre_paciente, 
                    u.usu_apellido AS apellido_paciente,
                    ec.nombre AS estado_nombre,
                    tc.nombre AS tipo_cita,
                    e.nombre AS especialidad,
                    p.nombre AS prioridad,
                    o.nombre AS origen
                FROM cita c
                INNER JOIN usuarios u ON c.paciente_id = u.usu_id
                INNER JOIN estado_cita ec ON c.estado_id = ec.estado_id
                INNER JOIN tipo_cita tc ON c.tipo_cita_id = tc.tipo_cita_id
                INNER JOIN especialidad e ON c.especialidad_id = e.especialidad_id
                INNER JOIN prioridad p ON c.prioridad_id = p.prioridad_id
                INNER JOIN origen_cita o ON c.origen_id = o.origen_id
                WHERE c.medico_id = :medico_id AND c.especialidad_id = :especialidad_id
                ORDER BY c.fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id, PDO::PARAM_INT);
        $stmt->bindParam(':especialidad_id', $especialidad_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




}
