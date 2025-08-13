<?php  

//Incluimos el metood de menus
require_once 'models/Menus.php';

class MenuController{

    //Funcion para mostrar todos los menus
    public function listar(){
        if(session_status() === PHP_SESSION_NONE) session_start();
    
        if(!isset($_SESSION['usuario'])){
            header("Location: index.php");
            exit();
        }
    
        $menuModel = new Menus();
        $menus = $menuModel->obtenerTodos();
    
        // 👇 Esto asegura que la vista reciba la variable
        require 'views/menus/listar.php';
    }
    


    //Crear un nuevo menu

    public function crear(){
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Verifica si el usuario está logueado
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        // Si se envia el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            $nombre = trim($_POST['menu_nombre']);
            $icono = trim($_POST['menu_icono']);

            if(!empty($nombre)){
                $menuModel = new Menus();
                $menuModel->crear($nombre, $icono);

                // URL cifrada para redirigir al listado de menus
                $vistaCifrada = base64_encode('menus/listar.php');
                $urlRedireccion = "index.php?vista={$vistaCifrada}";

                // Alerta y redireccion
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Menu creado!',
                            text: 'El nuevo menu ha sido guardado.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(function () {
                            window.location.href = '{$urlRedireccion}';
                        });
                    });
                    </script>
                ";
                exit();
            } else{
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Error!',
                            text: 'Todos los campos son obligatorios.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    });
                    </script>
                ";
                exit();
            }
        }

        require_once 'views/menus/crear.php';


    }


    // Funcion para editar

    public function editar($id = null){
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Verificar que el usuario haya iniciado sesión
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        $menuModel = new Menus();

        // Si el formulario fue enviado por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Recuperamos el ID cifrado desde el formulario
            $idCifrado = $_POST['id'] ?? null;
            $id = $idCifrado ? base64_decode($idCifrado) : null;

            // Validamos que el ID sea correcto
            if (!$id || !ctype_digit($id)) {
                echo "ID no válido.";
                exit();
            }

            // Validar y actualizar el nombre del menu
            $nombre = trim($_POST['menu_nombre']);
            $icono = trim($_POST['menu_icono']);

            if (!empty($nombre)) {
                $menuModel->editar($id, $nombre, $icono);

                // Justo antes del echo, codifica la vista
                $urlRedireccion = 'index.php?vista=' . base64_encode('menus/listar.php');

                // Luego en el JS, inserta la variable PHP
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Rol actualizado!',
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
            }else{
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Error!',
                            text: 'Todos los campos son obligatorios.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    });
                    </script>
                ";
                exit();
            }
        }

        //Si es GET, el ID viene como parametro del metodo
        if(!$id){
            echo "ID no válido.";
            exit();
        }

        $menu = $menuModel->obtenerPorId($id);

        if (!$menu) {
            echo "Menu no encontrado.";
            exit();
        }

        require_once 'views/menus/editar.php';
    }



    //Funcion de eliminar

    public function eliminar() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Verificar que el usuario haya iniciado sesión
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        // Recuperar y decodificar el ID cifrado
        $idCifrado = $_GET['id'] ?? null;
        $id = $idCifrado ? base64_decode($idCifrado) : null;

        // Validar el ID
        if (!$id || !ctype_digit($id)) {
            echo "ID no válido.";
            exit();
        }

        // Eliminar el menu
        $menuModel = new Menus();
        $menuModel->eliminar($id);

        // Redirigir con SweetAlert2
        $urlRedireccion = 'index.php?vista=' . base64_encode('menus/listar.php');

        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: '¡Menu Eliminado!',
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
    }







}




?>