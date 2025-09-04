<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'models/Cita.php';

class CitaController
{
    public function crear()
    {
        $modelo = new Cita();

        // Estos métodos deben existir en el modelo
        $pacientes       = $modelo->obtenerPacientes();
        $especialidades  = $modelo->obtenerEspecialidades();
        $tipos_cita      = $modelo->obtenerTiposCita();
        $prioridades     = $modelo->obtenerPrioridades();
        $origenes        = $modelo->obtenerOrigenes();

        include 'views/citas/crear.php';
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'models/Cita.php';
            require_once 'libs/correo_cita.php'; // Debe existir

            $modelo = new Cita();

            $paciente_id     = $_POST['paciente_id']     ?? null;
            $medico_id       = $_POST['medico_id']       ?? null;
            $especialidad_id = $_POST['especialidad_id'] ?? null;
            $tipo_cita_id    = $_POST['tipo_cita_id']    ?? null;
            $prioridad_id    = $_POST['prioridad_id']    ?? null;
            $motivo          = trim($_POST['motivo']      ?? '');
            $rol             = $_SESSION['usuario']['rol_id'] ?? null;
            $origen_id       = ($rol == 30) ? 3 : ($_POST['origen_id'] ?? null);
            $estado_id       = $_POST['estado_id']       ?? 1;

            $fecha           = $_POST['fecha_cita']      ?? null;
            $hora            = $_POST['hora_cita']       ?? null;

            // Opcional: cita en línea
            $es_online       = isset($_POST['es_online']) && $_POST['es_online'] == '1';

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
                    $cita_id  = $modelo->conn->lastInsertId();
                    $detalle  = $modelo->obtenerDetalleCita($cita_id);
                    $paciente = $modelo->obtenerInformacionPaciente($paciente_id);

                    $nombrePaciente = $paciente['usu_nombre'] . ' ' . $paciente['usu_apellido'];
                    $correoPaciente = $paciente['usu_correo'];

                    $creadoPor = ($rol == 30)
                        ? 'Cita registrada por usted mismo desde la plataforma web.'
                        : 'Cita registrada por el personal: ' . $_SESSION['usuario']['usu_nombre'] . ' ' . $_SESSION['usuario']['usu_apellido'];

                    // Si es cita online, intenta crear telecita (no detiene el flujo si falla)
                    $tele = null;
                    if ($es_online) {
                        try {
                            $room     = 'hospital-' . $cita_id . '-' . bin2hex(random_bytes(5));
                            $meetUrl  = 'https://meet.jit.si/' . $room;
                            $token    = bin2hex(random_bytes(24));
                            if (method_exists($modelo, 'crearTelecita')) {
                                $modelo->crearTelecita($cita_id, $meetUrl, $token);
                            }
                            $tele = [
                                'meeting_url' => $meetUrl,
                                'triage_url'  => 'https://menu-mvc.onrender.com/views/tele/triage.php?token=' . $token
                            ];
                        } catch (\Throwable $e) {
                            error_log('Telecita no creada: ' . $e->getMessage());
                        }
                    }

                    // Correo de confirmación
                    enviarCorreoCita($correoPaciente, $nombrePaciente, $detalle, $creadoPor, $tele);

                    $urlRedireccion = ($rol == 30)
                        ? 'index.php?vista=' . base64_encode('citas/mis_citas.php')
                        : 'index.php?vista=' . base64_encode('citas/listar.php');

                    echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                title: '¡Cita guardada correctamente!',
                                text: 'Se envió un correo de confirmación.',
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

    public function misCitas()
    {
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php?vista=" . base64_encode('login.php'));
            exit;
        }

        $paciente_id = $_SESSION['usuario']['usu_id'];

        $modelo = new Cita();
        $citas  = $modelo->obtenerCitasPorPaciente($paciente_id);

        include 'views/citas/mis_citas.php';
    }

    public function atender()
    {
        if (!isset($_GET['id'])) {
            echo "<div class='alert alert-danger'>ID de cita no proporcionado</div>";
            return;
        }

        $cita_id = $_GET['id'];
        $modelo  = new Cita();

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
            $cita_id    = $_POST['cita_id']    ?? null;
            $nombre     = trim($_POST['nombre'] ?? '');
            $dosis      = trim($_POST['dosis']  ?? '');

            if ($usuario_id && $nombre && $dosis && $cita_id) {
                $modelo = new Cita();
                $modelo->agregarVacuna([
                    'usuario_id'       => $usuario_id,
                    'nombre'           => $nombre,
                    'dosis'            => $dosis,
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
                                text: 'Todos los campos de la vacuna son obligatorios.',
                                icon: 'error',
                                confirmButtonText: 'Aceptar'
                            });
                        });
                    </script>
                ";
                exit();
            }
        }

        header("Location: index.php");
        exit();
    }

    public function guardarConsulta()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // IDs
            $cita_id    = $_POST['cita_id']    ?? null;
            $usuario_id = $_POST['usuario_id'] ?? null; // Paciente

            // Campos base
            $diagnostico = trim($_POST['diagnostico'] ?? '');
            $tratamiento = trim($_POST['tratamiento'] ?? ''); // campo libre (compat)

            // Plan de tratamiento (nuevos)
            $trat_nombre              = trim($_POST['trat_nombre'] ?? '');
            $trat_descripcion         = trim($_POST['trat_descripcion'] ?? '');
            $trat_fecha_inicio        = $_POST['trat_fecha_inicio'] ?? null;
            $trat_frecuencia_text     = trim($_POST['trat_frecuencia_text'] ?? '');
            $trat_sesiones_totales    = $_POST['trat_sesiones_totales'] !== '' ? (int)$_POST['trat_sesiones_totales'] : null;
            $trat_sesiones_realizadas = $_POST['trat_sesiones_realizadas'] !== '' ? (int)$_POST['trat_sesiones_realizadas'] : 0;
            $trat_duracion_dias       = $_POST['trat_duracion_dias'] !== '' ? (int)$_POST['trat_duracion_dias'] : null;
            $trat_dosis               = trim($_POST['trat_dosis'] ?? '');
            $trat_via_administracion  = trim($_POST['trat_via_administracion'] ?? '');
            $trat_observaciones       = trim($_POST['trat_observaciones'] ?? '');
            $trat_estado              = trim($_POST['trat_estado'] ?? 'activo');

            // Validaciones en servidor
            $errores = [];
            if (!$cita_id)                                $errores[] = 'Falta el ID de la cita.';
            if (strlen($diagnostico) < 5)                 $errores[] = 'El diagnóstico es obligatorio (mínimo 5 caracteres).';
            if ($trat_nombre === '')                      $errores[] = 'El nombre del tratamiento es obligatorio.';
            if (!$trat_fecha_inicio)                      $errores[] = 'La fecha de inicio del tratamiento es obligatoria.';
            if ($trat_estado === '')                      $errores[] = 'El estado del tratamiento es obligatorio.';
            if (!empty($trat_sesiones_totales) && $trat_sesiones_totales < 1)
                $errores[] = 'Las sesiones totales deben ser un entero ≥ 1.';
            if (!empty($trat_duracion_dias) && $trat_duracion_dias < 1)
                $errores[] = 'La duración en días debe ser un entero ≥ 1.';

            if ($errores) {
                $msg = implode("\n• ", $errores);
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo guardar',
                                html: 'Por favor corrige:<br>• " . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "'
                            }).then(() => { history.back(); });
                        });
                    </script>
                ";
                exit();
            }

            // Payload para el modelo
            $payload = [
                'cita_id'                  => $cita_id,
                'diagnostico'              => $diagnostico,
                'tratamiento'              => ($tratamiento !== '') ? $tratamiento : null,
                'fecha'                    => date('Y-m-d'),
                'trat_nombre'              => $trat_nombre,
                'trat_descripcion'         => ($trat_descripcion !== '') ? $trat_descripcion : null,
                'trat_fecha_inicio'        => $trat_fecha_inicio,
                'trat_frecuencia_text'     => ($trat_frecuencia_text !== '') ? $trat_frecuencia_text : null,
                'trat_sesiones_totales'    => $trat_sesiones_totales,
                'trat_sesiones_realizadas' => $trat_sesiones_realizadas ?? 0,
                'trat_duracion_dias'       => $trat_duracion_dias,
                'trat_dosis'               => ($trat_dosis !== '') ? $trat_dosis : null,
                'trat_via_administracion'  => ($trat_via_administracion !== '') ? $trat_via_administracion : null,
                'trat_observaciones'       => ($trat_observaciones !== '') ? $trat_observaciones : null,
                'trat_estado'              => $trat_estado ?: 'activo',
            ];

            $modelo     = new Cita();
            $okConsulta = $modelo->registrarConsulta($payload);

            if ($okConsulta) {
                // Marcar cita como atendida
                $modelo->marcarCitaComoAtendida($cita_id);

                // Todo en la misma vista con modales
                $idCifrado   = base64_encode($cita_id);
                $urlAtencion = "index.php?vista=" . base64_encode("citas/atencion.php") . "&id={$idCifrado}";
                $urlReceta   = $urlAtencion . "&receta=1";
                $urlFactura  = $urlAtencion . "&factura=1";

                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                title: '¿Deseas registrar una receta ahora?',
                                text: 'Puedes seleccionar medicamentos y dosis antes de facturar.',
                                icon: 'question',
                                showDenyButton: true,
                                confirmButtonText: 'Sí, registrar receta',
                                denyButtonText: 'No, ir a factura'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '$urlReceta';
                                } else {
                                    window.location.href = '$urlFactura';
                                }
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
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo registrar la consulta.'
                            }).then(() => { history.back(); });
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
            $cita_id     = $_POST['cita_id']    ?? null;
            $total       = $_POST['total']      ?? null;

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
                            text: 'Todos los campos de la factura son obligatorios.'
                        });
                    </script>
                ";
            }
        }
    }

    public function guardarReceta()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php");
            exit;
        }

        $paciente_id = $_POST['paciente_id'] ?? null;
        $cita_id     = $_POST['cita_id']     ?? null;
        $returnUrl   = $_POST['return']      ?? null; // vuelve con ?factura=1
        $items       = $_POST['items']       ?? [];

        $errores = [];
        if (!$paciente_id) $errores[] = 'Falta el paciente.';
        if (!$cita_id)     $errores[] = 'Falta la cita.';

        if (empty($items) || !is_array($items)) {
            $errores[] = 'Debes agregar al menos un medicamento.';
        } else {
            foreach ($items as $i => $it) {
                $med = trim($it['medicamento_id'] ?? '');
                $dos = trim($it['dosis']          ?? '');
                $fre = trim($it['frecuencia']     ?? '');
                $dia = isset($it['dias']) ? (int)$it['dias'] : 0;

                if ($med === '' || $dos === '' || $fre === '' || $dia < 1) {
                    $errores[] = "Ítem #" . ($i + 1) . ": completa Medicamento, Dosis, Frecuencia y Días (≥ 1).";
                }
            }
        }

        $idCifrado  = base64_encode($cita_id);
        $urlReceta  = "index.php?vista=" . base64_encode("citas/atencion.php") . "&id={$idCifrado}&receta=1";
        $urlFactura = $returnUrl ?: ("index.php?vista=" . base64_encode("citas/atencion.php") . "&id={$idCifrado}&factura=1");

        if ($errores) {
            $msg = implode("<br>• ", array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $errores));
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campos incompletos',
                            html: 'Corrige lo siguiente:<br>• $msg'
                        }).then(() => {
                            window.location.href = '$urlReceta';
                        });
                    });
                </script>
            ";
            exit();
        }

        // ←———— CAMBIA DESDE AQUÍ
        $modelo = new Cita();
        $error  = null;
        $ok     = $modelo->guardarReceta($paciente_id, $cita_id, $items, $error);

    if ($ok) {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            icon: 'success',
                            title: 'Receta guardada',
                            text: 'Ahora continúa con la factura.'
                        }).then(() => {
                            window.location.href = '$urlFactura';
                        });
                    });
                </script>
            ";
            exit();
        } else {
            // Mostramos el error de PDO (útil para ajustar nombres de tabla/columna)
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo guardar la receta',
                            html: 'Detalle:<br><small>".htmlspecialchars($error ?? 'Error desconocido', ENT_QUOTES, 'UTF-8')."</small>'
                        }).then(() => {
                            window.location.href = '$urlReceta';
                        });
                    });
                </script>
            ";
            exit();
        }
        // ←———— HASTA AQUÍ
    }
}
