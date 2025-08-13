<?php
require_once __DIR__ . '/../config/database.php';

class Factura {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Obtener todas las facturas
    public function obtenerTodas() {
        $sql = "SELECT 
                    f.factura_id,
                    f.fecha,
                    f.total,
                    f.estado,
                    CONCAT(u.usu_nombre, ' ', u.usu_apellido) AS paciente,
                    u.usu_cedula
                FROM factura f
                INNER JOIN usuarios u ON f.usuario_id = u.usu_id
                ORDER BY f.fecha DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar facturas por cédula del paciente
    public function buscarPorCedula($cedula)
{
    // 1. Buscar al paciente por cédula
    $sqlPaciente = "SELECT usu_id, usu_cedula, CONCAT(usu_nombre, ' ', usu_apellido) AS nombre_completo 
                    FROM usuarios 
                    WHERE usu_cedula = ?";
    $stmt = $this->conn->prepare($sqlPaciente);
    $stmt->execute([$cedula]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paciente) {
        return []; // No existe paciente con esa cédula
    }

    $paciente_id = $paciente['usu_id'];

    // 2. Buscar facturas del paciente
    $sqlFacturas = "SELECT 
                        f.factura_id,
                        f.fecha,
                        f.total AS valor,
                        f.estado,
                        :nombre_completo AS nombre_completo,
                        :cedula AS cedula
                    FROM factura f 
                    WHERE f.paciente_id = :paciente_id AND f.estado = 1";

    $stmt = $this->conn->prepare($sqlFacturas);
    $stmt->bindParam(':nombre_completo', $paciente['nombre_completo']);
    $stmt->bindParam(':cedula', $paciente['usu_cedula']);
    $stmt->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function cambiarEstadoPagada($factura_id)
{
    $sql = "UPDATE factura SET estado = 0 WHERE factura_id = ?";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([$factura_id]);
}


public function obtenerUltimosDosMeses()
{
    $sql = "SELECT 
                f.factura_id,
                f.fecha,
                f.total AS valor,
                f.estado,
                CONCAT(u.usu_nombre, ' ', u.usu_apellido) AS nombre_completo,
                u.usu_cedula AS cedula
            FROM factura f
            INNER JOIN usuarios u ON f.paciente_id = u.usu_id
            WHERE f.fecha >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
            ORDER BY f.fecha DESC";
    
    $stmt = $this->conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    

}
