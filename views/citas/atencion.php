<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de cita no proporcionado</div>";
    exit;
}

require_once 'models/Cita.php';
$modelo = new Cita();

// ID de cita desencriptado
$cita_id = base64_decode($_GET['id']);
$detalle = $modelo->obtenerDetalleCita($cita_id);
$paciente = $modelo->obtenerInformacionPaciente($detalle['paciente_id']);
$historial = $modelo->obtenerHistorialClinico($detalle['paciente_id']);
$vacunas = $modelo->obtenerVacunas($detalle['paciente_id']);
$consultas = $modelo->obtenerConsultas($detalle['paciente_id']);

include 'views/layouts/header.php';
?>

<div class="container mt-4">
    <h4 class="mb-4"><i class="fas fa-user-md me-2"></i>Atención médica - Paciente <?= htmlspecialchars($paciente['usu_nombre'] . ' ' . $paciente['usu_apellido']) ?></h4>

    <!-- Datos del paciente -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">🧍 Datos del paciente</div>
        <div class="card-body">
            <p><strong>Cédula:</strong> <?= $paciente['usu_cedula'] ?></p>
            <p><strong>Correo:</strong> <?= $paciente['usu_correo'] ?></p>
        </div>
    </div>

    <!-- Detalles de la cita -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">📋 Detalle de la cita</div>
        <div class="card-body">
            <p><strong>Fecha y hora:</strong> <?= date('d/m/Y h:i A', strtotime($detalle['fecha'])) ?></p>
            <p><strong>Especialidad:</strong> <?= $detalle['especialidad'] ?></p>
            <p><strong>Motivo:</strong> <?= $detalle['motivo'] ?></p>
            <p><strong>Tipo de cita:</strong> <?= $detalle['tipo_cita'] ?></p>
            <p><strong>Prioridad:</strong> <?= $detalle['prioridad'] ?></p>
            <p><strong>Origen:</strong> <?= $detalle['origen'] ?></p>
        </div>
    </div>

    <!-- Historial clínico -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">🩺 Historial clínico</div>
        <div class="card-body">
            <?php if ($historial): ?>
                <p><strong>Antecedentes:</strong> <?= nl2br($historial['antecedentes']) ?></p>
                <p><strong>Alergias:</strong> <?= nl2br($historial['alergias']) ?></p>
                <p><strong>Enfermedades crónicas:</strong> <?= nl2br($historial['enfermedades_cronicas']) ?></p>
                <p><strong>Observaciones:</strong> <?= nl2br($historial['observaciones']) ?></p>
            <?php else: ?>
                <p class="text-muted">No hay historial clínico registrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Vacunas -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            💉 Vacunas registradas
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalVacuna">
                <i class="fas fa-plus"></i> Agregar nueva
            </button>
        </div>
        <div class="card-body">
            <?php if ($vacunas): ?>
                <ul>
                    <?php foreach ($vacunas as $v): ?>
                        <li><?= $v['nombre'] ?> (<?= $v['dosis'] ?> dosis) - <?= date('d/m/Y', strtotime($v['fecha_aplicacion'])) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No hay vacunas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para nueva vacuna -->
    <div class="modal fade" id="modalVacuna" tabindex="-1" aria-labelledby="modalVacunaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardarVacuna') ?>" method="POST">
            <input type="hidden" name="usuario_id" value="<?= $paciente['usu_id'] ?>">
            <input type="hidden" name="cita_id" value="<?= $cita_id ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVacunaLabel">Registrar nueva vacuna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la vacuna</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="dosis" class="form-label">Dosis</label>
                        <input type="text" class="form-control" name="dosis" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
    </div>


    <!-- Consultas anteriores -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">📑 Consultas anteriores</div>
        <div class="card-body">
            <?php if ($consultas): ?>
                <ul>
                    <?php foreach ($consultas as $c): ?>
                        <li>
                            <strong><?= date('d/m/Y', strtotime($c['fecha'])) ?>:</strong> 
                            Diagnóstico: <?= $c['diagnostico'] ?> | Tratamiento: <?= $c['tratamiento'] ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">Sin consultas previas registradas.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- Botón para finalizar consulta -->
<div class="text-end mb-4">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalFinalizarConsulta">
        <i class="fas fa-check-circle me-2"></i> Finalizar Consulta
    </button>
</div>

<!-- Modal para registrar consulta -->
<div class="modal fade" id="modalFinalizarConsulta" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardarConsulta') ?>" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-notes-medical me-2"></i> Finalizar Consulta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($paciente['usu_id']) ?>">
        <input type="hidden" name="cita_id" value="<?= htmlspecialchars($cita_id) ?>">

        <div class="mb-3">
          <label for="diagnostico" class="form-label">Diagnóstico</label>
          <textarea name="diagnostico" id="diagnostico" class="form-control" required rows="3"></textarea>
        </div>
        <div class="mb-3">
          <label for="tratamiento" class="form-label">Tratamiento</label>
          <textarea name="tratamiento" id="tratamiento" class="form-control" required rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar y Finalizar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal para factura -->
<div class="modal fade" id="modalFactura" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardarFactura') ?>" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i> Registrar Factura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="usuario_id" value="<?= $paciente['usu_id'] ?>">
        <input type="hidden" name="cita_id" value="<?= $cita_id ?>">
        <div class="mb-3">
          <label class="form-label">Total a pagar ($)</label>
          <input type="number" name="total" class="form-control" step="0.01" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar Factura</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>



</div>

<?php if (isset($_GET['factura']) && $_GET['factura'] == 1): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalFactura = new bootstrap.Modal(document.getElementById('modalFactura'));
        modalFactura.show();
    });
</script>
<?php endif; ?>


<?php include 'views/layouts/footer.php'; ?>
