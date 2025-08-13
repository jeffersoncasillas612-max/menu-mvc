<?php
require_once 'models/Menu.php';

class PanelController {
    public function inicio() {
        session_start();
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        $menuModel = new Menu();
        $rol_id = $_SESSION['usuario']['rol_id'];
        $menus = $menuModel->obtenerMenusCompletos($rol_id);

        require_once 'views/panel/inicio.php';
    }
}
