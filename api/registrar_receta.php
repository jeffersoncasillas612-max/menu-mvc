<?php
// api/registrar_receta.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Receta.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'JSON inválido']);
        exit;
    }

    // --------- Entradas obligatorias
    $cita_id      = (int)($data['cita_id'] ?? 0);
    $medicamentos = $data['medicamentos'] ?? [];   // array de objetos
    // --------- Entradas opcionales
    $fecha        = trim((string)($data['fecha'] ?? ''));          // Y-m-d
    $indicaciones = trim((string)($data['indicaciones'] ?? ''));   // texto libre
    $diag_opt     = trim((string)($data['diagnostico'] ?? ''));    // si no hay consulta, lo usamos
    $trat_opt     = trim((string)($data['tratamiento'] ?? ''));    // idem

    if ($cita_id <= 0 || !is_array($medicamentos) || count($medicamentos) === 0) {
        http_response_code(400);
        echo json_encode(['estado'=>'error','mensaje'=>'cita_id y al menos un medicamento son obligatorios']);
        exit;
    }

    // Normalizar fecha (DATE)
    if ($fecha === '') {
        $fecha_sql = date('Y-m-d');
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>'Formato de fecha inválido (use YYYY-MM-DD)']);
            exit;
        }
        $fecha_sql = $fecha;
    }

    $recetaModel = new Receta();
    $pdo = $recetaModel->conn;

    // 1) Validar que la cita exista (y obtener paciente opcionalmente)
    $st = $pdo->prepare("
        SELECT c.cita_id, c.paciente_id, u.usu_nombre, u.usu_apellido
        FROM cita c
        INNER JOIN usuarios u ON u.usu_id = c.paciente_id
        WHERE c.cita_id = :id
        LIMIT 1
    ");
    $st->execute([':id' => $cita_id]);
    $cita = $st->fetch(PDO::FETCH_ASSOC);
    if (!$cita) {
        http_response_code(404);
        echo json_encode(['estado'=>'error','mensaje'=>'Cita no encontrada']);
        exit;
    }

    // 2) Validar medicamentos (cada item debe tener medicamento_id o nombre; y dosis/frecuencia/duracion)
    foreach ($medicamentos as $i => $m) {
        if (!is_array($m)) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>"Medicamento #".($i+1).": formato inválido"]);
            exit;
        }
        $hasId   = isset($m['medicamento_id']) && (int)$m['medicamento_id'] > 0;
        $hasName = isset($m['nombre']) && trim((string)$m['nombre']) !== '';

        if (!$hasId && !$hasName) {
            http_response_code(400);
            echo json_encode(['estado'=>'error','mensaje'=>"Medicamento #".($i+1).": envía medicamento_id o nombre"]);
            exit;
        }
        foreach (['dosis','frecuencia','duracion'] as $req) {
            if (!isset($m[$req]) || trim((string)$m[$req]) === '') {
                http_response_code(400);
                echo json_encode(['estado'=>'error','mensaje'=>"Medicamento #".($i+1).": '$req' es requerido"]);
                exit;
            }
        }
    }

    $pdo->beginTransaction();

    // 3) Asegurar consulta_id para la cita
    $consulta_id = $recetaModel->getConsultaIdPorCita($cita_id);
    if (!$consulta_id) {
        // Creamos una consulta mínima si aún no se registró diagnóstico
        $diag = ($diag_opt !== '') ? $diag_opt : 'Diagnóstico no especificado';
        $tra  = ($trat_opt !== '') ? $trat_opt : ($indicaciones ?: 'Tratamiento no especificado');
        $consulta_id = $recetaModel->crearConsultaPlaceholder($cita_id, $diag, $tra);

        // (Opcional) marcar la cita como atendida (estado_id = 3) si aún no lo está
        $pdo->prepare("UPDATE cita SET estado_id = 3 WHERE cita_id = :id")->execute([':id'=>$cita_id]);
    }

    // 4) Crear receta
    $receta_id = $recetaModel->crearReceta($consulta_id, $fecha_sql, $indicaciones ?: null);

    // 5) Procesar medicamentos
    $salida_meds = [];
    foreach ($medicamentos as $m) {
        $medicamento_id = (int)($m['medicamento_id'] ?? 0);
        $nombre         = trim((string)($m['nombre'] ?? ''));
        $descripcion    = isset($m['descripcion']) ? trim((string)$m['descripcion']) : null;

        // Resolver ID del medicamento
        if ($medicamento_id > 0) {
            if (!$recetaModel->existeMedicamentoPorId($medicamento_id)) {
                throw new RuntimeException("El medicamento_id {$medicamento_id} no existe");
            }
        } else {
            // Buscar por nombre; si no existe, crear
            $id_exist = $recetaModel->buscarMedicamentoPorNombre($nombre);
            $medicamento_id = $id_exist ?: $recetaModel->crearMedicamento($nombre, $descripcion);
        }

        // Insertar línea
        $ok = $recetaModel->agregarMedicamentoAReceta(
            $receta_id,
            $medicamento_id,
            trim((string)$m['dosis']),
            trim((string)$m['frecuencia']),
            trim((string)$m['duracion'])
        );
        if (!$ok) {
            throw new RuntimeException("No se pudo agregar medicamento a la receta");
        }

        $salida_meds[] = [
            'medicamento_id' => $medicamento_id,
            'nombre'         => $nombre ?: null,
            'dosis'          => trim((string)$m['dosis']),
            'frecuencia'     => trim((string)$m['frecuencia']),
            'duracion'       => trim((string)$m['duracion']),
        ];
    }

    $pdo->commit();

    echo json_encode([
        'estado'   => 'ok',
        'mensaje'  => 'Receta registrada correctamente',
        'receta'   => [
            'receta_id'   => $receta_id,
            'consulta_id' => $consulta_id,
            'cita_id'     => $cita_id,
            'paciente'    => trim(($cita['usu_nombre'] ?? '').' '.($cita['usu_apellido'] ?? '')),
            'fecha'       => $fecha_sql,
            'indicaciones'=> $indicaciones ?: null,
            'medicamentos'=> $salida_meds
        ]
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['estado'=>'error','mensaje'=>'Error interno: '.$e->getMessage()]);
}
