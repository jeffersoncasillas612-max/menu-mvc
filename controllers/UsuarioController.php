<?php
require_once 'models/Usuario.php';
require_once 'libs/correo_bienvenida.php'; // Incluye la función para enviar el correo

class UsuarioController {
    public function guardar() {
        $usuario = new Usuario();

        $nombre          = $_POST['nombre'];
        $apellido        = $_POST['apellido'];
        $correo          = $_POST['correo'];
        $cedula          = $_POST['cedula'];
        $rol_id          = $_POST['rol_id'];
        $especialidad_id = !empty($_POST['especialidad_id']) ? $_POST['especialidad_id'] : null;

        $urlRedireccion = 'index.php?vista=' . base64_encode('usuarios/crear.php');

        // Validar duplicados
        if ($usuario->existeCedulaOCorreo($cedula, $correo)) {
            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <title>Duplicado</title>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Ya existe',
                        text: 'La cédula o correo ya están registrados.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.href = '$urlRedireccion';
                    });
                </script>
            </body>
            </html>";
            return;
        }

        // Crear usuario
        $resultado = $usuario->crear($nombre, $apellido, $correo, $cedula, $rol_id, $especialidad_id);

        if ($resultado) {
            // 👇 IMPORTANTE: pasar 4 parámetros en este orden
            // (correo, nombre, apellido, contraseñaTemporal=cedula)
            enviarCorreoBienvenida($correo, $nombre, $apellido, $cedula);

            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <title>Guardado</title>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuario creado',
                        text: 'El nuevo usuario fue registrado y se envió un correo de bienvenida.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.href = '$urlRedireccion';
                    });
                </script>
            </body>
            </html>";
        } else {
            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <title>Error</title>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo registrar el usuario.'
                    });
                </script>
            </body>
            </html>";
        }
    }



    public function cambiarClave() {
        session_start();
    
        $nueva = $_POST['nueva_contrasena'];
        $confirmar = $_POST['confirmar_contrasena'];
        $token = $_POST['token'] ?? null;
    
        if ($nueva !== $confirmar) {
            $this->mostrarAlerta('error', 'Las contraseñas no coinciden.', true);
            return;
        }
    
        $usuario = new Usuario();
        $hash = hash('sha256', $nueva);
    
        if ($token) {
            // Cambio por recuperación
            $datos = $usuario->obtenerPorToken($token);
            if (!$datos) {
                $this->mostrarAlerta('error', 'El token no es válido o ha expirado.');
                return;
            }
    
            $usuario->actualizarClave($datos['usu_id'], $hash);
            $usuario->limpiarToken($datos['usu_id']);
    
            $this->mostrarAlerta('success', 'Contraseña actualizada correctamente.');
        } elseif (isset($_SESSION['usuario'])) {
            // Cambio por primera vez
            $id = $_SESSION['usuario']['usu_id'];
            $usuario->actualizarClave($id, $hash);
            $_SESSION['usuario']['usu_primera_vez'] = 0;
            session_destroy();
    
            $this->mostrarAlerta('success', 'Contraseña cambiada. Inicia sesión.');
        } else {
            $this->mostrarAlerta('error', 'No se pudo procesar la solicitud.');
        }
    }
    
    
    
    

    public function procesarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = trim($_POST['correo']);
    
            $usuario = new Usuario();
            $datosUsuario = $usuario->obtenerPorCorreo($correo);
    
            if (!$datosUsuario) {
                $this->mostrarAlerta('error', 'No existe un usuario registrado con ese correo.', true);
                return;
            }
    
            // 🔐 Generar token único y vencimiento
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    
            // 💾 Guardar token en BD
            $usuario->guardarTokenRecuperacion($datosUsuario['usu_id'], $token, $expira);
    
            // 📬 Preparar envío de correo
            $nombreCompleto = $datosUsuario['usu_nombre'] . ' ' . $datosUsuario['usu_apellido'];
            $linkRecuperacion = "http://localhost:4060/MenuMVC/index.php?vista=" . base64_encode("usuarios/cambiar_contrasena.php") . "&token=$token";
    
            require_once 'libs/correo_recuperacion.php';
            $enviado = enviarCorreoRecuperacion($correo, $nombreCompleto, $linkRecuperacion);
    
            if ($enviado) {
                $this->mostrarAlerta('success', 'Se ha enviado un enlace de recuperación a tu correo.');
            } else {
                $this->mostrarAlerta('error', 'No se pudo enviar el correo. Inténtalo más tarde.', true);
            }
        }
    }
    
    

    private function mostrarAlerta($icon, $mensaje, $volver = false) {
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <title>Alerta</title>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: '$icon',
                    title: '$mensaje'
                }).then(() => {
                    " . ($volver ? "window.history.back();" : "window.location.href = 'index.php?vista=" . base64_encode("login.php") . "';") . "
                });
            </script>
        </body>
        </html>";
    }
    








}
