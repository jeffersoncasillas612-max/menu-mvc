<?php
// models/Receta.php
require_once __DIR__ . '/../config/database.php';

class Receta {
    public $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /** Devuelve consulta_id de una cita o null si no existe */
    public function getConsultaIdPorCita(int $cita_id): ?int {
        $st = $this->conn->prepare("SELECT consulta_id FROM consulta WHERE cita_id = :c LIMIT 1");
        $st->execute([':c' => $cita_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['consulta_id'] : null;
    }

    /** Crea una consulta placeholder (si no existe) y devuelve su ID */
    public function crearConsultaPlaceholder(int $cita_id, string $diagnostico = '', string $tratamiento = ''): int {
        $st = $this->conn->prepare("
            INSERT INTO consulta (cita_id, diagnostico, tratamiento, fecha)
            VALUES (:c, :d, :t, NOW())
        ");
        $st->execute([
            ':c' => $cita_id,
            ':d' => $diagnostico,
            ':t' => $tratamiento
        ]);
        return (int)$this->conn->lastInsertId();
    }

    /** Crea la receta y devuelve receta_id */
    public function crearReceta(int $consulta_id, string $fecha, string $indicaciones = null): int {
        $st = $this->conn->prepare("
            INSERT INTO receta (consulta_id, fecha, indicaciones)
            VALUES (:con, :f, :i)
        ");
        $st->execute([
            ':con' => $consulta_id,
            ':f'   => $fecha,       // 'Y-m-d'
            ':i'   => $indicaciones
        ]);
        return (int)$this->conn->lastInsertId();
    }

    /** Busca medicamento por nombre (match exacto) y devuelve su ID o null */
    public function buscarMedicamentoPorNombre(string $nombre): ?int {
        $st = $this->conn->prepare("SELECT medicamento_id FROM medicamento WHERE nombre = :n LIMIT 1");
        $st->execute([':n' => $nombre]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['medicamento_id'] : null;
    }

    /** Comprueba que exista un medicamento por ID */
    public function existeMedicamentoPorId(int $id): bool {
        $st = $this->conn->prepare("SELECT 1 FROM medicamento WHERE medicamento_id = :id");
        $st->execute([':id' => $id]);
        return (bool)$st->fetchColumn();
    }

    /** Crea medicamento y devuelve su ID */
    public function crearMedicamento(string $nombre, ?string $descripcion = null): int {
        $st = $this->conn->prepare("INSERT INTO medicamento (nombre, descripcion) VALUES (:n, :d)");
        $st->execute([':n' => $nombre, ':d' => $descripcion]);
        return (int)$this->conn->lastInsertId();
    }

    /** Agrega una línea a receta_medicamento */
    public function agregarMedicamentoAReceta(int $receta_id, int $medicamento_id, string $dosis, string $frecuencia, string $duracion): bool {
        $st = $this->conn->prepare("
            INSERT INTO receta_medicamento (receta_id, medicamento_id, dosis, frecuencia, duracion)
            VALUES (:r, :m, :d, :f, :du)
        ");
        return $st->execute([
            ':r'  => $receta_id,
            ':m'  => $medicamento_id,
            ':d'  => $dosis,
            ':f'  => $frecuencia,
            ':du' => $duracion
        ]);
    }
}
