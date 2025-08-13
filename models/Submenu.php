<?php
// Incluimos la conexión a la base de datos
require_once 'config/database.php';

class Submenu {
    public $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }


    //Funcion para obtener todos 
    public function obtenerTodos() {
        $sql = "SELECT s.*, m.menu_nombre 
                FROM submenus s
                INNER JOIN menus m ON s.menu_id = m.menu_id
                WHERE s.estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }   

    //Crear
    public function crear($submenu_nombre, $menu_id) {
        $sql = "INSERT INTO submenus (submenu_nombre, menu_id, estado) VALUES (:submenu_nombre, :menu_id, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':submenu_nombre', $submenu_nombre);
        $stmt->bindParam(':menu_id', $menu_id);
        return $stmt->execute();
    }

    //Submenu por id
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM submenus WHERE submenu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //Editar
    public function editar($id, $submenu_nombre, $menu_id) {
        $sql = "UPDATE submenus SET submenu_nombre = :submenu_nombre, menu_id = :menu_id WHERE submenu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':submenu_nombre', $submenu_nombre);
        $stmt->bindParam(':menu_id', $menu_id);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    //Eliminar
    public function eliminar($id) {
        $sql = "UPDATE submenus SET estado = 0 WHERE submenu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }


    public function obtenerMenusYSubmenus() {
        $sql = "SELECT s.submenu_id, s.submenu_nombre, m.menu_nombre 
                FROM submenus s 
                JOIN menus m ON s.menu_id = m.menu_id
                WHERE s.estado = 1 AND m.estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    


}


?>