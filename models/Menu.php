<?php
require_once 'config/database.php';

class Menu {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function obtenerMenusCompletos($rol_id) {
        $menus = $this->conn->prepare("SELECT * FROM menus WHERE estado = 1 AND menu_id IN (
            SELECT objeto_id FROM permisos WHERE rol_id = ? AND tipo = 'menu'
        )");
        $menus->execute([$rol_id]);
        $menus = $menus->fetchAll(PDO::FETCH_ASSOC);
    
        foreach ($menus as &$menu) {
            $submenus = $this->conn->prepare("SELECT * FROM submenus WHERE menu_id = ? AND estado = 1 AND submenu_id IN (
                SELECT objeto_id FROM permisos WHERE rol_id = ? AND tipo = 'submenu'
            )");
            $submenus->execute([$menu['menu_id'], $rol_id]);
            $submenus = $submenus->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($submenus as &$submenu) {
                $acciones = $this->conn->prepare("SELECT * FROM sub_submenus WHERE submenu_id = ? AND estado = 1 AND subsubmenu_id IN (
                    SELECT objeto_id FROM permisos WHERE rol_id = ? AND tipo = 'accion'
                )");
                $acciones->execute([$submenu['submenu_id'], $rol_id]);
                $submenu['acciones'] = $acciones->fetchAll(PDO::FETCH_ASSOC);
            }
    
            $menu['submenus'] = $submenus;
        }
    
        return $menus;
    }


    public function obtenerJerarquiaCompleta() {
        $sqlMenus = "SELECT * FROM menus WHERE estado = 1";
        $stmtMenus = $this->conn->prepare($sqlMenus);
        $stmtMenus->execute();
        $menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);

        foreach ($menus as &$menu) {
            // Obtener submenús del menú actual
            $sqlSubmenus = "SELECT * FROM submenus WHERE estado = 1 AND menu_id = ?";
            $stmtSub = $this->conn->prepare($sqlSubmenus);
            $stmtSub->execute([$menu['menu_id']]);
            $submenus = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

            foreach ($submenus as &$submenu) {
                // Obtener acciones (sub-submenús) del submenú actual
                $sqlAcciones = "SELECT * FROM sub_submenus WHERE estado = 1 AND submenu_id = ?";
                $stmtAcc = $this->conn->prepare($sqlAcciones);
                $stmtAcc->execute([$submenu['submenu_id']]);
                $acciones = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

                $submenu['acciones'] = $acciones;
            }

            $menu['submenus'] = $submenus;
        }

        return $menus;
    }
    
}
