<?php
include 'views/layouts/header.php';
require_once 'models/Turno.php';

$medico_id = $_SESSION['usuario']['usu_id'] ?? null;
$turnos = [];

if ($medico_id) {
    $modeloTurno = new Turno();
    $turnos = $modeloTurno->obtenerTurnosPorMedico($medico_id);
}

function agruparTurnosPorDia($turnos) {
    $agrupados = [];
    foreach ($turnos as $t) {
        $dia = $t['dia_semana'];
        if (!isset($agrupados[$dia])) {
            $agrupados[$dia] = [];
        }
        $agrupados[$dia][] = $t;
    }
    return $agrupados;
}

function obtenerIntervalosMediaHora($turnos) {
    $min = '23:59'; $max = '00:00';
    foreach ($turnos as $t) {
        $inicio = date('H:i', strtotime($t['hora_inicio']));
        $fin = date('H:i', strtotime($t['hora_fin']));
        if ($inicio < $min) $min = $inicio;
        if ($fin > $max) $max = $fin;
    }

    $intervalos = [];
    $inicio = strtotime($min);
    $fin = strtotime($max);

    while ($inicio < $fin) {
        $intervalos[] = date('H:i', $inicio);
        $inicio = strtotime('+30 minutes', $inicio);
    }
    return $intervalos;
}

$turnosPorDia = agruparTurnosPorDia($turnos);
$intervalos = obtenerIntervalosMediaHora($turnos);
$diasSemana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

$diasConTurno = array_keys($turnosPorDia ?? []);
$diasDisponibles = array_diff($diasSemana, $diasConTurno);

function hayAtencion($turnosDia, $hora) {
    foreach ($turnosDia ?? [] as $t) {
        $inicio = strtotime($t['hora_inicio']);
        $fin = strtotime($t['hora_fin']);
        $actual = strtotime($hora);
        if ($actual >= $inicio && $actual < $fin) return true;
    }
    return false;
}
?>

<style>
    .tabla-horario {
        table-layout: fixed;
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .tabla-horario th, .tabla-horario td {
        border: 1px solid #dee2e6;
        padding: 10px;
        text-align: center;
        vertical-align: middle;
    }

    .tabla-horario th {
        background-color: #1f2d3d;
        color: white;
    }

    .hora-columna {
        background-color: #fbeeff;
        font-weight: bold;
        width: 75px;
    }

    .celda-atencion {
        background-color: #198754;
        color: white;
        font-weight: bold;
    }

    .celda-descanso {
        background-color: #0d6efd;
        color: white;
        font-weight: bold;
    }

    .form-eliminar {
        display: inline-block;
        margin-left: 5px;
    }

    .form-eliminar button {
        font-size: 12px;
        padding: 4px 8px;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-calendar-alt me-2"></i>Horario Semanal</h4>
        <?php if (!empty($diasDisponibles)): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalHorario">
                <i class="fas fa-plus"></i> Registrar horario
            </button>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-ban"></i> Días completos
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="tabla-horario">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <?php foreach ($diasSemana as $dia): ?>
                            <th class="text-center">
    <span class="d-flex justify-content-center align-items-center gap-2">
        <strong><?= $dia ?></strong>
        <?php if (!empty($turnosPorDia[$dia])): ?>
            <form id="formEliminar<?= $dia ?>" method="POST" action="index.php?c=<?= base64_encode('turno') ?>&a=<?= base64_encode('eliminar') ?>" style="display: inline;">
                <input type="hidden" name="medico_id" value="<?= $medico_id ?>">
                <input type="hidden" name="dia" value="<?= $dia ?>">
                <button type="button"
                    class="btn btn-sm btn-link text-danger p-0"
                    onclick="confirmarEliminacion('<?= $dia ?>')"
                    title="Eliminar horario del día <?= $dia ?>">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        <?php endif; ?>
    </span>
</th>

                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intervalos as $hora): ?>
                        <tr>
                            <td class="hora-columna"><?= date('g:i A', strtotime($hora)) ?></td>
                            <?php foreach ($diasSemana as $dia): ?>
                                <?php
                                    $estado = hayAtencion($turnosPorDia[$dia] ?? [], $hora);
                                    $clase = $estado ? 'celda-atencion' : 'celda-descanso';
                                    $texto = $estado ? 'Atención' : 'Descanso';
                                ?>
                                <td class="<?= $clase ?>"><?= $texto ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL PARA REGISTRAR HORARIO -->
<div class="modal fade" id="modalHorario" tabindex="-1" aria-labelledby="modalHorarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="index.php?c=<?= base64_encode('turno') ?>&a=<?= base64_encode('guardar') ?>">
            <input type="hidden" name="medico_id" value="<?= $medico_id ?>">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalHorarioLabel"><i class="fas fa-plus-circle me-2"></i>Nuevo Horario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Día de la semana</label>
                        <select name="dia" class="form-select" required>
                            <option value="">-- Selecciona --</option>
                            <?php foreach ($diasDisponibles as $dia): ?>
                                <option value="<?= $dia ?>"><?= $dia ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Mañana: Desde</label>
                            <input type="text" name="hora_inicio_m" class="form-control hora" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Mañana: Hasta (descanso)</label>
                            <input type="text" name="hora_fin_m" class="form-control hora" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tarde: Desde (fin descanso)</label>
                            <input type="text" name="hora_inicio_t" class="form-control hora" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tarde: Hasta</label>
                            <input type="text" name="hora_fin_t" class="form-control hora" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>

                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
flatpickr(".hora", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i", // Formato de 24 horas
    time_24hr: true
});


function confirmarEliminacion(dia) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminarán todos los turnos del día ${dia}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formEliminar' + dia).submit();
        }
    });
}
</script>


<?php include 'views/layouts/footer.php'; ?>
