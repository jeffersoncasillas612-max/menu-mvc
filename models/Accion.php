<?php 
// Incluimos la conexión a la base de datos
require_once 'config/database.php';

class Accion {

    public $conn;

    public function __construct() {
        // Creamos la conexión al instanciar la clase
        $db = new Database();
        $this->conn = $db->getConnection();    
    }


    //Funcion para obtener todos los menus activos
    public function obtenerTodos() {
        $sql = "
            SELECT a.*, s.submenu_nombre, m.menu_nombre
            FROM sub_submenus a
            JOIN submenus s ON a.submenu_id = s.submenu_id
            JOIN menus m ON s.menu_id = m.menu_id
            WHERE a.estado = 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear($nombre, $url, $submenu_id) {
        $sql = "INSERT INTO sub_submenus (subsubmenu_nombre, url, submenu_id, estado)
                VALUES (:nombre, :url, :submenu_id, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':submenu_id', $submenu_id);
        return $stmt->execute();
    }
    

    // Obtener una acción (subsubmenu) por su ID
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM sub_submenus WHERE subsubmenu_id = :id AND estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Editar una acción
    public function editar($id, $nombre, $url, $submenu_id) {
        $sql = "UPDATE sub_submenus 
                SET subsubmenu_nombre = :nombre, url = :url, submenu_id = :submenu_id 
                WHERE subsubmenu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':submenu_id', $submenu_id);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Eliminar unaacción
    public function eliminar($id) {
        $sql = "UPDATE sub_submenus SET estado = 0 WHERE subsubmenu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    


}


?>