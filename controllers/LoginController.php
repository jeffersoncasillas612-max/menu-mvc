<?php
require_once 'models/Usuario.php';

class LoginController {
    public function verificar() {
        session_start();

        if (isset($_POST['correo']) && isset($_POST['contrasena'])) {
            $correo = $_POST['correo'];
            $clave = $_POST['contrasena'];

            $usuario = new Usuario();
            $resultado = $usuario->verificar($correo, $clave);

            if ($resultado) {
                $_SESSION['usuario'] = $resultado;
            
                if ($resultado['usu_primera_vez'] == 1) {
                    // Redirigir al formulario de cambio obligatorio
                    $vista = base64_encode('usuarios/cambiar_contrasena.php');
                    header("Location: index.php?vista=$vista");
                    return;
                }
            
                // Redirección normal si no es primera vez
                // Redirección según rol
                switch ($resultado['rol_id']) {
                    case 1: // Super Admin
                        $vista = 'index.php?c=' . base64_encode('panel') . '&a=' . base64_encode('inicio');
                        break;
                    case 2: // Admin
                        $vista = 'index.php?c=' . base64_encode('panel') . '&a=' . base64_encode('inicio');
                        break;

                    case 30: // Paciente
                        $vista = 'index.php?vista=' . base64_encode('citas/mis_citas.php');
                        break;

                    case 31: // Médico
                        $vista = 'index.php?vista=' . base64_encode('citas/calendario.php');
                        break;

                    case 32: // Recepcionista
                        $vista = 'index.php?vista=' . base64_encode('facturas/listar.php');
                        break;

                    default:
                        // Redirección genérica
                        $vista = 'index.php?c=' . base64_encode('panel') . '&a=' . base64_encode('inicio');
                        break;
                }

                header("Location: $vista");

            }else {
                // Justo antes del echo, codifica la vista
                $urlRedireccion = 'index.php?vista=' . base64_encode('login.php');

                // Luego en el JS, inserta la variable PHP
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Contraseña o correo incorrectos!',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = '$urlRedireccion';
                        });
                    });
                    </script>
                ";
            }
        }
    }
}
