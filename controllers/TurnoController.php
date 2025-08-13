<?php
require_once 'models/Turno.php';

class TurnoController {
    public function miHorario() {
        if (!isset($_SESSION['usuario'])) {
            echo "Acceso no autorizado";
            return;
        }

        $medico_id = $_SESSION['usuario']['usu_id'];
        $turnoModel = new Turno();
        $turnos = $turnoModel->obtenerTurnosPorMedico($medico_id);

        include 'views/citas/mihorario.php';
    }



    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $medico_id     = $_POST['medico_id'] ?? null;
            $dia           = $_POST['dia'] ?? null;
            $hora_inicio_m = $_POST['hora_inicio_m'] ?? null;
            $hora_fin_m    = $_POST['hora_fin_m'] ?? null;
            $hora_inicio_t = $_POST['hora_inicio_t'] ?? null;
            $hora_fin_t    = $_POST['hora_fin_t'] ?? null;
    
            if ($medico_id && $dia && $hora_inicio_m && $hora_fin_m && $hora_inicio_t && $hora_fin_t) {
                require_once 'models/Turno.php';
                $turnoModel = new Turno();
    
                // Registrar turno de la mañana
                $turnoModel->crearTurno($medico_id, $dia, $hora_inicio_m, $hora_fin_m);
    
                // Registrar turno de la tarde
                $turnoModel->crearTurno($medico_id, $dia, $hora_inicio_t, $hora_fin_t);
    
                // Redirigir de vuelta a mihorario.php
                // Justo antes del echo, codifica la vista
                $urlRedireccion = 'index.php?vista=' . base64_encode('citas/mihorario.php');

                // Luego en el JS, inserta la variable PHP
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: '¡Horario guardado exitosamente!',
                            text: 'Su nuevo horario se ha guardado exitosamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = '$urlRedireccion';
                        });
                    });
                    </script>
                ";
                exit;
            } else {
                echo "Faltan campos obligatorios.";
            }
        }
    }



    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $medico_id = $_POST['medico_id'] ?? null;
            $dia       = $_POST['dia'] ?? null;
    
            if ($medico_id && $dia) {
                $turnoModel = new Turno();
                $turnoModel->eliminarTurnosPorDia($medico_id, $dia);
    
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            title: 'Horario eliminado',
                            text: 'Se eliminó correctamente el día $dia.',
                            icon: 'success'
                        }).then(() => {
                            window.location.href = 'index.php?vista=" . base64_encode('citas/mihorario.php') . "';
                        });
                    });
                </script>";
                exit;
            } else {
                echo "Datos incompletos para eliminar.";
            }
        }
    }
    



    
}
