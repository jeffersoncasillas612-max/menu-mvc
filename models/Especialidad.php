<?php
require_once 'config/database.php';

class Especialidad {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function obtenerTodas() {
        $sql = "SELECT * FROM especialidad ORDER BY nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
