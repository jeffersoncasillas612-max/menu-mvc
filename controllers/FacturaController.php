<?php
require_once 'models/Factura.php';

class FacturaController {

    public function listar() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
    
        $modelo = new Factura();
        $facturas = $modelo->obtenerUltimosDosMeses();
    
        require 'views/facturas/listar.php';
    }
    

    public function buscar() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }
        
        $modelo = new Factura();

        $facturasTotales = $modelo->obtenerUltimosDosMeses(); // Mostrar igual

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cedula = trim($_POST['cedula'] ?? '');
            if (!empty($cedula)) {
                $facturas = $modelo->buscarPorCedula($cedula);
            } else {
                $facturas = [];
            }

            require 'views/facturas/listar.php';
        } else {
            header("Location: index.php?vista=" . base64_encode('facturas/listar.php'));
            exit();
        }
    }

    public function marcarPagada()
{
    if (!isset($_GET['id'])) {
        echo "ID no proporcionado.";
        return;
    }

    $factura_id = base64_decode($_GET['id']);
    $modelo = new Factura();
    $modelo->cambiarEstadoPagada($factura_id);

    $urlRedireccion = 'index.php?vista=' . base64_encode('facturas/listar.php');

    echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: 'Pago registrado',
                    text: 'La factura ha sido marcada como pagada.',
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
