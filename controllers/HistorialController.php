<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'models/Cita.php';

class HistorialController {

    public function buscar() {
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php");
            exit();
        }

        $modelo = new Cita();
        $paciente = null;
        $citas = [];
        $historial = null;
        $vacunas = [];
        $consultas = [];
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cedula = trim($_POST['cedula'] ?? '');

            if (!empty($cedula)) {
                $p = $modelo->buscarPacientePorCedula($cedula);
                if ($p) {
                    $_SESSION['cedula_historial_actual'] = $cedula;
                    $paciente_id = $p['usu_id'];
                    $paciente = $modelo->obtenerInformacionPaciente($paciente_id);
                    $citas = $modelo->obtenerCitasPorPaciente($paciente_id);
                    $historial = $modelo->obtenerHistorialClinico($paciente_id);
                    $vacunas = $modelo->obtenerVacunas($paciente_id);
                    $consultas = $modelo->obtenerConsultas($paciente_id);
                } else {
                    $error = "Paciente no encontrado.";
                }
            } else {
                $error = "Ingrese una cédula válida.";
            }
        }

        include 'views/citas/buscar_historial.php';
    }
}
