<?php
// Incluye el modelo de roles
require_once 'models/Rol.php';    
require_once 'models/Menu.php';

class RolController {

    // Función para mostrar todos los roles
    public function listar() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        $rolModel = new Rol();
        $roles = $rolModel->obtenerTodos();
    
        require 'views/roles/listar.php';
    }


    //Crear un nuevo rol
    public function crear() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        // Verifica si el usuario está logueado
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        require_once 'models/Menu.php';
        $menuModel = new Menu();
        $estructura = $menuModel->obtenerJerarquiaCompleta(); // Menús → submenús → acciones
    
        // Si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['rol_nombre']);
            $permisos = $_POST['permisos'] ?? [];
    
            if (!empty($nombre)) {
                $rolModel = new Rol();
                $rolModel->crear($nombre);
                $nuevoRolId = $rolModel->obtenerUltimoId();
    
                // Guardar permisos seleccionados
                require_once 'models/Permiso.php';
                $permisoModel = new Permiso();
                foreach ($permisos as $tipo => $ids) {
                    foreach ($ids as $objetoId) {
                        $permisoModel->crearPermiso($nuevoRolId, $tipo, $objetoId);
                    }
                }
    
                // Redirección cifrada al listado y a la creación
                $urlListarroles = "index.php?vista=" . base64_encode('roles/listar.php');
                $urlListarpermisos = "index.php?vista=" . base64_encode('permisos/listar.php');

                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Rol creado!',
                            text: '¿Qué deseas hacer a continuación?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Listar roles',
                            cancelButtonText: 'Listar permisos',
                            confirmButtonColor: '#2ecc71',
                            cancelButtonColor: '#3498db'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '$urlListarroles';
                            } else {
                                window.location.href = '$urlListarpermisos';
                            }
                        });
                    });
                    </script>
                ";
                exit();

            } else {
                $error = "El nombre del rol es obligatorio.";
            }
        }
    
        require 'views/roles/crear.php';
    }
    
    
    

    public function editar($id = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        // Verificar que el usuario haya iniciado sesión
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        $rolModel = new Rol();
    
        // Si el formulario fue enviado por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Recuperamos el ID cifrado desde el formulario
            $idCifrado = $_POST['id'] ?? null;
            $id = $idCifrado ? base64_decode($idCifrado) : null;
    
            // Validamos que el ID sea correcto
            if (!$id || !ctype_digit($id)) {
                echo "ID no válido.";
                exit();
            }
    
            // Validar y actualizar el nombre del rol
            $nombre = trim($_POST['rol_nombre']);
    
            if (!empty($nombre)) {
                $rolModel->editar($id, $nombre);
    
                // Justo antes del echo, codifica la vista
                $urlRedireccion = 'index.php?vista=' . base64_encode('roles/listar.php');

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
            } else {
                $error = "El nombre no puede estar vacío.";
            }
        }
    
        // Si es GET, el ID viene como parámetro del método
        if (!$id) {
            echo "ID no válido.";
            exit();
        }
    
        // Buscar el rol por ID para mostrar en el formulario
        $rol = $rolModel->obtenerPorId($id);
    
        if (!$rol) {
            echo "Rol no encontrado.";
            exit();
        }
    
        // Mostrar el formulario
        require 'views/roles/editar.php';
    }
    
    

    

    public function eliminar() {
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
    
        // Eliminar el rol
        $rolModel = new Rol();
        $rolModel->eliminar($id);
    
        // Redirigir con SweetAlert2
        $urlRedireccion = 'index.php?vista=' . base64_encode('roles/listar.php');
    
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: '¡Rol eliminado!',
                    text: 'El rol fue eliminado correctamente.',
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
