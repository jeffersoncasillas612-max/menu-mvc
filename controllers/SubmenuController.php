<?php
require_once 'models/Submenu.php';

class SubmenuController {


    public function listar() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        $submenuModel = new Submenu();
        $submenus = $submenuModel->obtenerTodos();

        require 'views/submenus/listar.php';
    }

    //Crear
    public function crear() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        // Lógica al enviar formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['submenu_nombre']);
            $menu_id = $_POST['menu_id'];
    
            if (!empty($nombre) && !empty($menu_id)) {
                $submenuModel = new Submenu();
                $submenuModel->crear($nombre, $menu_id);
    
                $urlRedireccion = 'index.php?vista=' . base64_encode('submenus/listar.php');
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Submenú creado!',
                            text: 'Se ha guardado correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = '$urlRedireccion';
                        });
                    });
                    </script>
                ";
                exit();
            } else {
                $error = "Todos los campos son obligatorios.";
            }
        }
    
        // ⚠️ Aquí obtenemos los menús activos para llenar el select
        require_once 'models/Menus.php';
        $menuModel = new Menus();
        $menus = $menuModel->obtenerActivos();
    
        require 'views/submenus/crear.php'; // Esta vista ya tienes lista
    }
    

    public function editar($id = null) {
        
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Verificar que el usuario haya iniciado sesión
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        $submenuModel = new Submenu();

        // Si el formulario viene por POST
        if ($_SERVER ['REQUEST_METHOD'] === 'POST') {
            // Recuperamos el ID cifrado desde el formulario
            $idCifrado = $_POST['id'] ?? null;
            $id = $idCifrado ? base64_decode($idCifrado) : null;

            // Validamos que el ID sea correcto
            if (!$id || !ctype_digit($id)) {
                echo "ID no valido.";
                exit();
            }

            // Validar y actualizar el nombre del submenu
            $submenu_nombre = trim($_POST['submenu_nombre']);
            $menu_id = $_POST['menu_id'];

            if(!empty($submenu_nombre) && !empty($menu_id)) {
                $submenuModel->editar($id, $submenu_nombre, $menu_id);
                $urlRedireccion = 'index.php?vista=' . base64_encode('submenus/listar.php');
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Submenú actualizado!',
                            text: 'Se ha guardado correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = '$urlRedireccion';
                        });
                    });
                    </script>
                ";
                exit();
            }else{
                $error = "Todos los campos son obligatorios.";
            }
        }

        // ⚠️ Aquí obtenemos los menús activos para llenar el select
        require_once 'models/Menus.php';
        $menuModel = new Menus();
        $menus = $menuModel->obtenerActivos();

        // Si es GET 
        if (!$id){
            echo "ID no valido.";
            exit();
        }

        $submenu = $submenuModel->obtenerPorId($id);

        if (!$submenu) {
            echo "Submenu no encontrado.";
            exit();
        }

        require 'views/submenus/editar.php';
    }


    public function eliminar(){
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        // Validar sesión
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        // Recuperar y decodificar el ID cifrado
        $idCifrado = $_GET['id'] ?? null;
        $id = $idCifrado ? base64_decode($idCifrado) : null;

        // Validar el ID
        if (!$id || !ctype_digit($id)) {
            echo "ID no válido.";
            exit();
        }

        // Eliminar el submenu
        $submenuModel = new Submenu();
        $submenuModel->eliminar($id);

        // Redirigir con SweetAlert2
        $urlRedireccion = 'index.php?vista=' . base64_encode('submenus/listar.php');

        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: '¡Submenú eliminado!',
                    text: 'Se ha eliminado correctamente.',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = '$urlRedireccion';
                });
            });
            </script>
        ";
        exit();
    }


}


?>