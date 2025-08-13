<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'models/Cita.php';



class CitaController {

    public function crear() {
        $modelo = new Cita();

        $pacientes = $modelo->obtenerPacientes();
        $especialidades = $modelo->obtenerEspecialidades();
        $tipos_cita = $modelo->obtenerTiposCita();
        $prioridades = $modelo->obtenerPrioridades();
        $origenes = $modelo->obtenerOrigenes();

        include 'views/citas/crear.php';
    }




    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'models/Cita.php';
            require_once 'libs/correo_cita.php'; // ⚠️ asegurarse que este archivo exista
    
            $modelo = new Cita();
    
            $paciente_id       = $_POST['paciente_id'] ?? null;
            $medico_id         = $_POST['medico_id'] ?? null;
            $especialidad_id   = $_POST['especialidad_id'] ?? null;
            $tipo_cita_id      = $_POST['tipo_cita_id'] ?? null;
            $prioridad_id      = $_POST['prioridad_id'] ?? null;
            $motivo            = trim($_POST['motivo'] ?? '');
            $rol               = $_SESSION['usuario']['rol_id'] ?? null;
            $origen_id         = ($rol == 30) ? 3 : ($_POST['origen_id'] ?? null);
            $estado_id         = $_POST['estado_id'] ?? 1;
    
            $fecha             = $_POST['fecha_cita'] ?? null;
            $hora              = $_POST['hora_cita'] ?? null;
    
            if ($paciente_id && $medico_id && $especialidad_id && $tipo_cita_id &&
                $prioridad_id && $origen_id && $fecha && $hora && $motivo) {
    
                $formato_24h = date('H:i', strtotime($hora));
                $fecha_hora  = $fecha . ' ' . $formato_24h . ':00';
                $turno_id    = $modelo->obtenerTurnoId($medico_id, $fecha);
    
                $resultado = $modelo->guardarCita([
                    'paciente_id'     => $paciente_id,
                    'medico_id'       => $medico_id,
                    'especialidad_id' => $especialidad_id,
                    'tipo_cita_id'    => $tipo_cita_id,
                    'prioridad_id'    => $prioridad_id,
                    'origen_id'       => $origen_id,
                    'fecha'           => $fecha_hora,
                    'motivo'          => $motivo,
                    'estado_id'       => $estado_id,
                    'turno_id'        => $turno_id
                ]);
    
                if ($resultado) {
                    // 🔔 Enviar correo directamente
                    $cita_id = $modelo->conn->lastInsertId();
                    $detalle = $modelo->obtenerDetalleCita($cita_id);
                    $paciente = $modelo->obtenerInformacionPaciente($paciente_id);
    
                    $nombrePaciente = $paciente['usu_nombre'] . ' ' . $paciente['usu_apellido'];
                    $correoPaciente = $paciente['usu_correo'];
    
                    $creadoPor = ($rol == 30)
                        ? 'Cita registrada por usted mismo desde la plataforma web.'
                        : 'Cita registrada por el personal: ' . $_SESSION['usuario']['usu_nombre'] . ' ' . $_SESSION['usuario']['usu_apellido'];
    
                    enviarCorreoCita($correoPaciente, $nombrePaciente, $detalle, $creadoPor);
    
                    $urlRedireccion = ($rol == 30)
                        ? 'index.php?vista=' . base64_encode('citas/mis_citas.php')
                        : 'index.php?vista=' . base64_encode('citas/listar.php');
    
                    echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                title: '¡Cita guardada correctamente!',
                                text: 'La cita fue registrada y se envió un correo de confirmación.',
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                window.location.href = '$urlRedireccion';
                            });
                        });
                    </script>";
                    exit;
                } else {
                    $_SESSION['error'] = 'No se pudo guardar la cita.';
                }
            }
    
            $_SESSION['error'] = 'Faltan datos para registrar la cita.';
            header("Location: index.php?vista=" . base64_encode('citas/listar.php'));
            exit;
        }
    
        header('Location: index.php?c=' . base64_encode('cita') . '&a=' . base64_encode('crear'));
        exit;
    }
    
    




    public function misCitas() {
        session_start();
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php?vista=" . base64_encode('login.php'));
            exit;
        }
    
        $paciente_id = $_SESSION['usuario']['usu_id'];
    
        require_once 'models/Cita.php';
        $modelo = new Cita();
        $citas = $modelo->obtenerCitasPorPaciente($paciente_id);
    
        include 'views/citas/mis_citas.php';
    }
    

    public function atender() {
        if (!isset($_GET['id'])) {
            echo "<div class='alert alert-danger'>ID de cita no proporcionado</div>";
            return;
        }
    
        $cita_id = $_GET['id'];
        $modelo = new Cita();
    
        if ($modelo->marcarCitaComoAtendida($cita_id)) {
            header("Location: index.php?vista=" . base64_encode('citas/atencion.php') . "&id=" . base64_encode($cita_id));
            exit;
        } else {
            echo "<div class='alert alert-danger'>No se pudo actualizar la cita</div>";
        }
    }

    
    public function guardarVacuna()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuario_id = $_POST['usuario_id'] ?? null;
        $cita_id    = $_POST['cita_id'] ?? null;
        $nombre     = trim($_POST['nombre'] ?? '');
        $dosis      = trim($_POST['dosis'] ?? '');

        if ($usuario_id && $nombre && $dosis && $cita_id) {
            $modelo = new Cita();
            $modelo->agregarVacuna([
                'usuario_id' => $usuario_id,
                'nombre' => $nombre,
                'dosis' => $dosis,
                'fecha_aplicacion' => date('Y-m-d')
            ]);

            $vistaRedirigida = 'index.php?vista=' . base64_encode('citas/atencion.php') . '&id=' . base64_encode($cita_id);

            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Vacuna registrada!',
                            text: 'La vacuna fue añadida correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = '$vistaRedirigida';
                        });
                    });
                </script>
            ";
            exit();
        } else {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: 'Error',
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

    // Seguridad: si no es POST, redirigir
    header("Location: index.php");
    exit();
}

    
    

public function guardarConsulta()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cita_id     = $_POST['cita_id'] ?? null;
        $usuario_id  = $_POST['usuario_id'] ?? null;
        $diagnostico = trim($_POST['diagnostico'] ?? '');
        $tratamiento = trim($_POST['tratamiento'] ?? '');

        if ($cita_id && $diagnostico && $tratamiento) {
            $modelo = new Cita();

            // Guardar consulta
            $modelo->registrarConsulta([
                'cita_id'     => $usuario_id,
                'diagnostico' => $diagnostico,
                'tratamiento' => $tratamiento,
                'fecha'       => date('Y-m-d')
            ]);

            // Marcar como atendida
            $modelo->marcarCitaComoAtendida($cita_id);

            // Redirigir a misma vista pero con ?id=XYZ&factura=1
            $idCifrado = base64_encode($cita_id);
            $url = "index.php?vista=" . base64_encode("citas/atencion.php") . "&id={$idCifrado}&factura=1";

            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: 'Consulta guardada',
                            text: 'Ahora debe generar la factura.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = '$url';
                        });
                    });
                </script>
            ";
            exit();
        } else {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Todos los campos son obligatorios.'
                    });
                </script>
            ";
            exit();
        }
    }

    header("Location: index.php");
    exit();
}


    

public function guardarFactura()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $paciente_id = $_POST['usuario_id'] ?? null;
        $cita_id     = $_POST['cita_id'] ?? null;
        $total       = $_POST['total'] ?? null;

        if ($paciente_id && $cita_id && $total) {
            $modelo = new Cita();

            $modelo->guardarFactura($paciente_id, $cita_id, $total);

            $urlRedireccion = 'index.php?vista=' . base64_encode('citas/calendario.php');

            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        title: 'Factura registrada',
                        text: 'La cita ha sido finalizada correctamente.',
                        icon: 'success'
                    }).then(() => {
                        window.location.href = '$urlRedireccion';
                    });
                });
                </script>
            ";
            exit();
        } else {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Todos los campos son obligatorios.'
                    });
                </script>
            ";
        }
    }
}


}
