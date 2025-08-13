<?php
// Incluimos la conexión a la base de datos
require_once 'config/database.php';

class Rol {
    private $conn;

    public function __construct() {
        // Creamos la conexión al instanciar la clase
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Función para obtener todos los roles activos
    public function obtenerTodos() {
        $sql = "SELECT * FROM roles WHERE estado = 1 ORDER BY rol_id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve lista de roles
    }

    // Crear un nuevo rol
    public function crear($nombre) {
        $sql = "INSERT INTO roles (rol_nombre, estado) VALUES (:nombre, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }

    // Traer un rol por ID
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM roles WHERE rol_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar rol
    public function editar($id, $nombre) {
        $sql = "UPDATE roles SET rol_nombre = :nombre WHERE rol_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }


    // Eliminar rol
    public function eliminar($id) {
        $sql = "UPDATE roles SET estado = 0 WHERE rol_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    


    public function obtenerUltimoId() {
        return $this->conn->lastInsertId();
    }
    

}
