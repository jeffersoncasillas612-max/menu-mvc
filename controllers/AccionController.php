<?php
require_once 'models/Accion.php';
require_once 'models/Submenu.php';

class AccionController {
    public function listar() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        $accionModel = new Accion();
        $acciones = $accionModel->obtenerTodos();

        require 'views/acciones/listar.php';
    }


    public function crear() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        // Instanciamos el modelo
        $accionModel = new Accion(); // o Subsubmenu según como se llame tu clase
        $submenuModel = new Submenu(); // Para traer la lista de submenús disponibles
        $menus = $submenuModel->obtenerMenusYSubmenus(); // método auxiliar que vamos a crear
    
        // Si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['subsubmenu_nombre']);
            $url = trim($_POST['url']);
            $submenu_id = $_POST['submenu_id'];
    
            if (!empty($nombre) && !empty($url) && ctype_digit($submenu_id)) {
                $accionModel->crear($nombre, $url, $submenu_id);
    
                $vistaRedireccion = base64_encode('acciones/listar.php');
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                title: '¡Acción creada!',
                                text: 'La nueva acción se ha guardado correctamente.',
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                window.location.href = 'index.php?vista={$vistaRedireccion}';
                            });
                        });
                    </script>";
                exit();
            } else {
                $error = "Todos los campos son obligatorios y válidos.";
            }
        }
    
        require 'views/acciones/crear.php';
    }
    

    public function editar($id = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        require_once 'models/Accion.php';
        require_once 'models/Submenu.php';
    
        $accionModel = new Accion();
        $submenuModel = new Submenu();
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idCifrado = $_POST['id'] ?? null;
            $id = $idCifrado ? base64_decode($idCifrado) : null;
    
            if (!$id || !ctype_digit($id)) {
                echo "ID no válido.";
                exit();
            }
    
            $nombre = trim($_POST['subsubmenu_nombre']);
            $url = trim($_POST['url']);
            $submenu_id = $_POST['submenu_id'];
    
            if (!empty($nombre) && !empty($url) && ctype_digit($submenu_id)) {
                $accionModel->editar($id, $nombre, $url, $submenu_id);
    
                $urlRedireccion = 'index.php?vista=' . base64_encode('acciones/listar.php');
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Acción actualizada!',
                            text: 'Los cambios fueron guardados.',
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
    
        if (!$id) {
            echo "ID no válido.";
            exit();
        }
    
        $accion = $accionModel->obtenerPorId($id);
        $submenus = $submenuModel->obtenerTodos(); // Para llenar el select
    
        if (!$accion) {
            echo "Acción no encontrada.";
            exit();
        }
    
        require 'views/acciones/editar.php';
    }


    public function eliminar($id = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        // Recuperar y decodificar
        $idCifrado = $_GET['id'] ?? null;
        $id = $idCifrado ? base64_decode($idCifrado) : null;

        // Validar el ID
        if (!$id || !ctype_digit($id)) {
            echo "ID no válido.";
            exit();
        }

        $accionModel = new Accion();
        $accionModel->eliminar($id);

        $urlRedireccion = 'index.php?vista=' . base64_encode('acciones/listar.php');
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: '¡Acción eliminada!',
                    text: 'La acción se ha eliminado correctamente.',
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