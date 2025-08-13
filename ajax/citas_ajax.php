<?php
require_once '../models/Cita.php';
header('Content-Type: application/json');
$modelo = new Cita();

// 1. Buscar paciente
if (isset($_GET['accion']) && $_GET['accion'] === 'buscar_paciente' && isset($_GET['cedula'])) {
    $cedula = $_GET['cedula'];
    $paciente = $modelo->buscarPacientePorCedula($cedula);

    if ($paciente) {
        echo json_encode([
            'success' => true,
            'id' => $paciente['usu_id'],
            'nombre' => ucwords(strtolower($paciente['usu_nombre'])),
            'apellido' => ucwords(strtolower($paciente['usu_apellido']))
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// 2. Médicos por especialidad
if (isset($_GET['accion']) && $_GET['accion'] === 'medicos_por_especialidad' && isset($_GET['especialidad_id'])) {
    $esp = intval($_GET['especialidad_id']);
    $medicos = $modelo->obtenerMedicosPorEspecialidad($esp);

    $opciones = [];
    foreach ($medicos as $m) {
        $nombre = ucwords(strtolower($m['usu_nombre'] . ' ' . $m['usu_apellido']));
        $opciones[] = ['id' => $m['usu_id'], 'nombre' => $nombre];
    }

    echo json_encode($opciones);
    exit;
}

// 3. Horarios disponibles
if (
    isset($_GET['accion']) &&
    $_GET['accion'] === 'horarios_disponibles' &&
    isset($_GET['medico_id']) &&
    isset($_GET['fecha'])
) {
    header('Content-Type: application/json'); // ✅ Imprescindible para evitar errores de parseo
    try {
        $medico_id = intval($_GET['medico_id']);
        $fecha = $_GET['fecha'];

        $dia_php = date('l', strtotime($fecha));
        $dias_es = [
            'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
        ];
        $dia_es = $dias_es[$dia_php] ?? $dia_php;

        $turnos = $modelo->obtenerTurnoPorMedicoYDia($medico_id, $dia_es);

        if (!$turnos || count($turnos) === 0) {
            echo json_encode([]);
            exit;
        }

        $ocupadas = $modelo->obtenerHorasOcupadas($medico_id, $fecha);
        $horas_disponibles = [];

        foreach ($turnos as $turno) {
            $inicio = strtotime($turno['hora_inicio']);
            $fin = strtotime($turno['hora_fin']);

            while ($inicio < $fin) {
                $hora = date('H:i', $inicio);
                if (!in_array($hora, $ocupadas)) {
                    $formato_12h = date('g:i A', strtotime($hora));
                    $horas_disponibles[] = $formato_12h;

                }
                $inicio = strtotime('+30 minutes', $inicio);
            }
        }

        echo json_encode($horas_disponibles);
        exit;

    } catch (Exception $e) {
        echo json_encode(["error" => "Error interno", "debug" => $e->getMessage()]);
        exit;
    }
}


// ❗ Esta línea debe ir al final SI NINGUNA CONDICIÓN SE CUMPLE
echo json_encode(['success' => false, 'error' => 'Acción inválida']);
exit;
