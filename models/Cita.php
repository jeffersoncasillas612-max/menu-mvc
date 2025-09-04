<?php
// Conexión
require_once __DIR__ . '/../config/database.php';

class Cita
{
    public $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /* ===== Catálogos y utilitarios ===== */

    public function obtenerPacientes()
    {
        $sql = "SELECT usu_id, usu_nombre, usu_apellido, usu_cedula, usu_correo
                FROM usuarios
                WHERE rol_id = 30 AND usu_estado = 1
                ORDER BY usu_apellido, usu_nombre";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPacientePorCedula($cedula)
    {
        $sql = "SELECT usu_id, usu_nombre, usu_apellido
                FROM usuarios
                WHERE usu_cedula = :cedula AND rol_id = 30 AND usu_estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($paciente) {
            $paciente['usu_nombre']   = ucwords(strtolower($paciente['usu_nombre']));
            $paciente['usu_apellido'] = ucwords(strtolower($paciente['usu_apellido']));
        }
        return $paciente;
    }

    public function obtenerMedicosPorEspecialidad($especialidad_id)
    {
        $sql = "SELECT usu_id, usu_nombre, usu_apellido
                FROM usuarios
                WHERE rol_id = 31 AND usu_estado = 1 AND especialidad_id = :esp";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':esp', $especialidad_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEspecialidades()
    {
        $stmt = $this->conn->query("SELECT * FROM especialidad");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTiposCita()
    {
        $stmt = $this->conn->query("SELECT * FROM tipo_cita");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPrioridades()
    {
        $stmt = $this->conn->query("SELECT * FROM prioridad");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOrigenes()
    {
        $stmt = $this->conn->query("SELECT * FROM origen_cita");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMedicamentos()
    {
        $sql = "SELECT medicamento_id, nombre FROM medicamento ORDER BY nombre";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ===== Turnos y disponibilidad ===== */

    public function obtenerTurnoPorMedicoYDia($medico_id, $dia_semana)
    {
        $sql = "SELECT hora_inicio, hora_fin FROM turno WHERE medico_id = ? AND dia_semana = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$medico_id, $dia_semana]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerHorasOcupadas($medico_id, $fecha)
    {
        $sql = "SELECT TIME(fecha) as hora FROM cita WHERE medico_id = :medico_id AND DATE(fecha) = :fecha";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        $horas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($h) {
            return date('H:i', strtotime($h['hora']));
        }, $horas);
    }

    public function obtenerTurnoId($medico_id, $fecha)
    {
        $dia_semana_en = date('l', strtotime($fecha));
        $dias_es = [
            'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
        ];
        $dia = $dias_es[$dia_semana_en] ?? $dia_semana_en;

        $sql = "SELECT turno_id FROM turno WHERE medico_id = :medico_id AND dia_semana = :dia";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':dia', $dia);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['turno_id'] : null;
    }

    /* ===== Citas ===== */

    public function guardarCita($data)
    {
        $sql = "INSERT INTO cita
            (paciente_id, medico_id, fecha, tipo_cita_id, especialidad_id, prioridad_id, origen_id, motivo, estado_id, turno_id)
            VALUES
            (:paciente_id, :medico_id, :fecha, :tipo_cita_id, :especialidad_id, :prioridad_id, :origen_id, :motivo, :estado_id, :turno_id)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function obtenerCitasPorPaciente($paciente_id)
    {
        $sql = "SELECT 
                    c.cita_id,
                    c.fecha,
                    TIME(c.fecha) AS hora,
                    c.motivo,
                    tc.nombre AS tipo_cita,
                    esp.nombre AS especialidad,
                    pr.nombre AS prioridad,
                    o.nombre  AS origen,
                    CONCAT(m.usu_nombre, ' ', m.usu_apellido) AS medico,
                    ec.nombre AS estado_nombre,
                    c.estado_id
                FROM cita c
                INNER JOIN tipo_cita tc   ON c.tipo_cita_id   = tc.tipo_cita_id
                LEFT JOIN especialidad esp ON c.especialidad_id = esp.especialidad_id
                LEFT JOIN prioridad pr     ON c.prioridad_id    = pr.prioridad_id
                LEFT JOIN origen_cita o    ON c.origen_id       = o.origen_id
                INNER JOIN usuarios m      ON m.usu_id          = c.medico_id
                INNER JOIN estado_cita ec  ON c.estado_id       = ec.estado_id
                WHERE c.paciente_id = ?
                ORDER BY c.fecha ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarCitasPerdidas()
    {
        $sql = "UPDATE cita 
                SET estado_id = 5
                WHERE estado_id = 1 
                  AND TIMESTAMPDIFF(MINUTE, fecha, NOW()) > 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
    }

    public function marcarCitaComoAtendida($cita_id)
    {
        $sql = "UPDATE cita SET estado_id = 3 WHERE cita_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$cita_id]);
    }

    public function obtenerDetalleCita($cita_id)
    {
        $sql = "SELECT 
                    c.*,
                    CONCAT(p.usu_nombre, ' ', p.usu_apellido) AS paciente,
                    p.usu_correo AS paciente_correo,
                    CONCAT(m.usu_nombre, ' ', m.usu_apellido) AS medico,
                    m.usu_correo AS medico_correo,
                    esp.nombre AS especialidad,
                    pr.nombre  AS prioridad,
                    o.nombre   AS origen,
                    tc.nombre  AS tipo_cita,
                    ec.nombre  AS estado
                FROM cita c
                INNER JOIN usuarios p ON c.paciente_id = p.usu_id
                INNER JOIN usuarios m ON c.medico_id   = m.usu_id
                LEFT  JOIN especialidad esp ON c.especialidad_id = esp.especialidad_id
                LEFT  JOIN prioridad pr     ON c.prioridad_id    = pr.prioridad_id
                LEFT  JOIN origen_cita o    ON c.origen_id       = o.origen_id
                LEFT  JOIN tipo_cita tc     ON c.tipo_cita_id    = tc.tipo_cita_id
                LEFT  JOIN estado_cita ec   ON c.estado_id       = ec.estado_id
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

    public function obtenerHistorialClinico($paciente_id)
    {
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

    public function obtenerVacunas($paciente_id)
    {
        $sql = "SELECT nombre, fecha_aplicacion, dosis
                FROM vacuna
                WHERE usuario_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerConsultas($paciente_id)
    {
        $sql = "SELECT 
                    con.diagnostico,
                    con.tratamiento,
                    con.fecha
                FROM consulta con
                INNER JOIN cita c ON con.cita_id = c.cita_id
                WHERE c.paciente_id = ?
                ORDER BY con.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ===== Acciones ===== */

    public function agregarVacuna($data)
    {
        $sql = "INSERT INTO vacuna (usuario_id, nombre, dosis, fecha_aplicacion)
                VALUES (:usuario_id, :nombre, :dosis, :fecha_aplicacion)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function registrarConsulta($data)
    {
        $sql = "INSERT INTO consulta (
                    cita_id, diagnostico, tratamiento, fecha,
                    trat_nombre, trat_descripcion, trat_fecha_inicio, trat_frecuencia_text,
                    trat_sesiones_totales, trat_sesiones_realizadas, trat_duracion_dias,
                    trat_dosis, trat_via_administracion, trat_observaciones, trat_estado
                ) VALUES (
                    :cita_id, :diagnostico, :tratamiento, :fecha,
                    :trat_nombre, :trat_descripcion, :trat_fecha_inicio, :trat_frecuencia_text,
                    :trat_sesiones_totales, :trat_sesiones_realizadas, :trat_duracion_dias,
                    :trat_dosis, :trat_via_administracion, :trat_observaciones, :trat_estado
                )";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':cita_id'                  => $data['cita_id'],
            ':diagnostico'              => $data['diagnostico']              ?? null,
            ':tratamiento'              => $data['tratamiento']              ?? null,
            ':fecha'                    => $data['fecha']                    ?? date('Y-m-d'),
            ':trat_nombre'              => $data['trat_nombre']              ?? null,
            ':trat_descripcion'         => $data['trat_descripcion']         ?? null,
            ':trat_fecha_inicio'        => $data['trat_fecha_inicio']        ?? null,
            ':trat_frecuencia_text'     => $data['trat_frecuencia_text']     ?? null,
            ':trat_sesiones_totales'    => $data['trat_sesiones_totales']    ?? null,
            ':trat_sesiones_realizadas' => $data['trat_sesiones_realizadas'] ?? 0,
            ':trat_duracion_dias'       => $data['trat_duracion_dias']       ?? null,
            ':trat_dosis'               => $data['trat_dosis']               ?? null,
            ':trat_via_administracion'  => $data['trat_via_administracion']  ?? null,
            ':trat_observaciones'       => $data['trat_observaciones']       ?? null,
            ':trat_estado'              => $data['trat_estado']              ?? 'activo',
        ]);
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

    /* ===== Reportes médicos ===== */

    public function obtenerCitasPorMedicoYRango($medico_id, $inicio, $fin)
    {
        $sql = "SELECT c.*,
                       u.usu_nombre AS nombre_paciente,
                       u.usu_apellido AS apellido_paciente,
                       ec.nombre AS estado_nombre,
                       tc.nombre AS tipo_cita,
                       es.nombre AS especialidad,
                       p.nombre AS prioridad,
                       o.nombre AS origen
                FROM cita c
                INNER JOIN usuarios u   ON c.paciente_id = u.usu_id
                INNER JOIN estado_cita ec ON c.estado_id  = ec.estado_id
                INNER JOIN tipo_cita tc   ON c.tipo_cita_id = tc.tipo_cita_id
                INNER JOIN especialidad es ON c.especialidad_id = es.especialidad_id
                INNER JOIN prioridad p     ON c.prioridad_id    = p.prioridad_id
                INNER JOIN origen_cita o   ON c.origen_id       = o.origen_id
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

    public function obtenerCitasPorEspecialidadYMedico($especialidad_id, $medico_id)
    {
        $sql = "SELECT c.*,
                       u.usu_nombre AS nombre_paciente,
                       u.usu_apellido AS apellido_paciente,
                       ec.nombre AS estado_nombre,
                       tc.nombre AS tipo_cita,
                       e.nombre  AS especialidad,
                       p.nombre  AS prioridad,
                       o.nombre  AS origen
                FROM cita c
                INNER JOIN usuarios u   ON c.paciente_id = u.usu_id
                INNER JOIN estado_cita ec ON c.estado_id  = ec.estado_id
                INNER JOIN tipo_cita tc   ON c.tipo_cita_id = tc.tipo_cita_id
                INNER JOIN especialidad e ON c.especialidad_id = e.especialidad_id
                INNER JOIN prioridad p     ON c.prioridad_id    = p.prioridad_id
                INNER JOIN origen_cita o   ON c.origen_id       = o.origen_id
                WHERE c.medico_id = :medico_id AND c.especialidad_id = :especialidad_id
                ORDER BY c.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':medico_id', $medico_id, PDO::PARAM_INT);
        $stmt->bindParam(':especialidad_id', $especialidad_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ===== Telecita ===== */

    public function crearTelecita($cita_id, $meeting_url, $triage_token)
    {
        $sql = "INSERT INTO telecita (cita_id, meeting_url, triage_token)
                VALUES (:cita_id, :meeting_url, :triage_token)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':cita_id'      => $cita_id,
            ':meeting_url'  => $meeting_url,
            ':triage_token' => $triage_token
        ]);
    }

    public function obtenerTelecitaPorToken($token)
    {
        $sql = "SELECT t.*, c.fecha, c.paciente_id, c.medico_id
                FROM telecita t
                INNER JOIN cita c ON c.cita_id = t.cita_id
                WHERE t.triage_token = :token";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function marcarTriajeCompletado($cita_id)
    {
        $sql = "UPDATE telecita SET triage_status = 'completado', updated_at = NOW()
                WHERE cita_id = :cita_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':cita_id' => $cita_id]);
    }

    /* ===== Recetas ===== */

    public function guardarReceta($paciente_id, $cita_id, array $items, ?string &$error = null)
{
    try {
        // Si tu Database no lo hace, forzamos excepciones
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validación mínima en servidor
        if (!$paciente_id || !$cita_id || empty($items)) {
            throw new \Exception('Datos de receta incompletos.');
        }

        $this->conn->beginTransaction();

        // 1) Cabecera
        // AJUSTA si tu tabla/columnas difieren (por ejemplo "id" en lugar de "receta_id", etc.)
        $sql = "INSERT INTO receta (paciente_id, cita_id, fecha) VALUES (:p, :c, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':p' => (int)$paciente_id, ':c' => (int)$cita_id]);
        $receta_id = (int)$this->conn->lastInsertId();

        // 2) Detalle
        // AJUSTA nombres de tabla/columnas si en tu DB se llaman distinto
        $sqlDet = "INSERT INTO receta_detalle
                    (receta_id, medicamento_id, dosis, frecuencia, dias, indicaciones)
                   VALUES
                    (:r, :m, :d, :f, :di, :i)";
        $det = $this->conn->prepare($sqlDet);

        foreach ($items as $i => $it) {
            $medicamento_id = isset($it['medicamento_id']) ? (int)$it['medicamento_id'] : 0;
            $dosis          = trim($it['dosis']       ?? '');
            $frecuencia     = trim($it['frecuencia']  ?? '');
            $dias           = isset($it['dias']) ? (int)$it['dias'] : 0;
            $indicaciones   = trim($it['indicaciones'] ?? '');

            if ($medicamento_id <= 0 || $dosis === '' || $frecuencia === '' || $dias < 1) {
                throw new \Exception("Ítem #".($i+1)." incompleto (medicamento/dosis/frecuencia/días).");
            }

            $det->execute([
                ':r'  => $receta_id,
                ':m'  => $medicamento_id,
                ':d'  => $dosis,
                ':f'  => $frecuencia,
                ':di' => $dias,
                ':i'  => ($indicaciones !== '') ? $indicaciones : null,
            ]);
        }

        $this->conn->commit();
        return true;

    } catch (\Throwable $e) {
        $this->conn->rollBack();
        $error = $e->getMessage();              // ← Lo subimos al controlador
        error_log('guardarReceta(): '.$error);   // ← Log en servidor
        return false;
    }
}

}
