<?php
require_once 'models/Permiso.php';
require_once 'models/Rol.php'; // <- Añadimos esta línea para cargar los roles

class PermisoController
{
    public function listar()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        $permisoModel = new Permiso();
        $rolModel = new Rol(); // <- Nueva instancia para obtener todos los roles

        // Obtenemos permisos organizados por rol
        $permisosPorRol = $permisoModel->obtenerTodosPermisosAgrupados();

        // Obtenemos todos los roles activos
        $roles = $rolModel->obtenerTodos();

        // Mostramos la vista
        require 'views/permisos/listar.php';
    }


    public function editar($id = null) {


        if (session_status() === PHP_SESSION_NONE) session_start();
    
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        require_once 'models/Rol.php';
        require_once 'models/Menu.php';
        require_once 'models/Permiso.php';
    
        $rolModel = new Rol();
        $menuModel = new Menu();
        $permisoModel = new Permiso();
    
        // Si el formulario fue enviado por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idCifrado = $_POST['id'] ?? null;
        } else {
            $idCifrado = $id ?? null;
        }
    
    
        if (!$id || !ctype_digit($id)) {
            echo "ID no válido.";
            exit();
        }
    
        $rol = $rolModel->obtenerPorId($id);
        if (!$rol) {
            echo "Rol no encontrado.";
            exit();
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $permisos = $_POST['permisos'] ?? [];
    
            $permisoModel->eliminarPermisosPorRol($id);
    
            foreach ($permisos as $tipo => $ids) {
                foreach ($ids as $objetoId) {
                    $permisoModel->crearPermiso($id, $tipo, $objetoId);
                }
            }
    
            $urlRedireccion = 'index.php?vista=' . base64_encode('permisos/listar.php');
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        title: '¡Permisos actualizados!',
                        text: 'Los permisos fueron guardados correctamente.',
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
    
        $estructura = $menuModel->obtenerJerarquiaCompleta();
        $permisosExistentes = $permisoModel->obtenerIdsDePermisosPorRol($id);
    
        require 'views/permisos/editar.php';
    }
    
    


}
