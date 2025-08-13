<?php
require_once 'config/database.php';

class Permiso {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Obtiene todos los permisos agrupados por rol en estructura jerárquica
    public function obtenerTodosPermisosAgrupados() {
        // Obtener roles activos
        $sqlRoles = "SELECT * FROM roles WHERE estado = 1";
        $rolesStmt = $this->conn->prepare($sqlRoles);
        $rolesStmt->execute();
        $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];

        foreach ($roles as $rol) {
            $rolId = $rol['rol_id'];

            // Obtener menús que tiene el rol
            $sqlMenus = "SELECT * FROM menus WHERE estado = 1 AND menu_id IN (
                            SELECT objeto_id FROM permisos WHERE rol_id = ? AND tipo = 'menu'
                         )";
            $menusStmt = $this->conn->prepare($sqlMenus);
            $menusStmt->execute([$rolId]);
            $menus = $menusStmt->fetchAll(PDO::FETCH_ASSOC);

            // Recorremos cada menú y buscamos sus submenús
            foreach ($menus as &$menu) {
                $sqlSubmenus = "SELECT * FROM submenus WHERE estado = 1 AND menu_id = ? AND submenu_id IN (
                                    SELECT objeto_id FROM permisos WHERE rol_id = ? AND tipo = 'submenu'
                                )";
                $submenusStmt = $this->conn->prepare($sqlSubmenus);
                $submenusStmt->execute([$menu['menu_id'], $rolId]);
                $submenus = $submenusStmt->fetchAll(PDO::FETCH_ASSOC);

                // Por cada submenú, obtenemos sus acciones
                foreach ($submenus as &$submenu) {
                    $sqlAcciones = "SELECT * FROM sub_submenus WHERE estado = 1 AND submenu_id = ? AND subsubmenu_id IN (
                                        SELECT objeto_id FROM permisos WHERE rol_id = ? AND tipo = 'accion'
                                    )";
                    $accionesStmt = $this->conn->prepare($sqlAcciones);
                    $accionesStmt->execute([$submenu['submenu_id'], $rolId]);
                    $acciones = $accionesStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Agregar acciones al submenú
                    $submenu['acciones'] = $acciones;
                }

                // Agregar submenús al menú
                $menu['submenus'] = $submenus;
            }

            // Asignar la jerarquía completa al rol
            $resultado[$rolId] = $menus;
        }

        return $resultado;
    }

    public function crearPermiso($rolId, $tipo, $objetoId) {
        $sql = "INSERT INTO permisos (rol_id, tipo, objeto_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$rolId, $tipo, $objetoId]);
    }




    public function obtenerIdsDePermisosPorRol($rolId) {
        $sql = "SELECT tipo, objeto_id FROM permisos WHERE rol_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$rolId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $permisos = ['menu' => [], 'submenu' => [], 'accion' => []];
        foreach ($result as $row) {
            $permisos[$row['tipo']][] = $row['objeto_id'];
        }
    
        return $permisos;
    }
    
    public function eliminarPermisosPorRol($rolId) {
        $sql = "DELETE FROM permisos WHERE rol_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$rolId]);
    }
    
    
}
