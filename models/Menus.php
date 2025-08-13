<?php 
// Incluimos la conexión a la base de datos
require_once 'config/database.php';

class Menus{

    private $conn;

    public function __construct() {
        // Creamos la conexión al instanciar la clase
        $db = new Database();
        $this->conn = $db->getConnection();
    }


    //Función para obtener todos los menus activos
    public function obtenerTodos() {
        $sql = "SELECT * FROM menus WHERE estado = 1 ORDER BY menu_id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve lista de menus
    }

    //Crear un nuevo menu
    public function crear($nombre, $icono) {
        $sql = "INSERT INTO menus (menu_nombre,menu_icono, estado) VALUES (:nombre, :icono, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':icono', $icono);
        return $stmt->execute();
    }


    //Traer un menu por ID
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM menus WHERE menu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //Actualizar menu
    public function editar($id, $nombre, $icono) {
        $sql = "UPDATE menus SET menu_nombre = :nombre, menu_icono = :icono WHERE menu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':icono', $icono);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }


    //Eliminar menu
    public function eliminar($id) {
        $sql = "UPDATE menus SET estado = 0 WHERE menu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function obtenerActivos() {
        $sql = "SELECT menu_id, menu_nombre FROM menus WHERE estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

}



?>