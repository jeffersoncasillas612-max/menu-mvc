<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol_id'] != 30) {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}

require_once 'models/Cita.php';
$modelo = new Cita();

$usuario_id = $_SESSION['usuario']['usu_id'];
$paciente   = $modelo->obtenerInformacionPaciente($usuario_id);
$historial  = $modelo->obtenerHistorialClinico($usuario_id);
$vacunas    = $modelo->obtenerVacunas($usuario_id);
$consultas  = $modelo->obtenerConsultas($usuario_id);

include 'views/layouts/header.php';
?>

<div class="container mt-4 mb-5">
    <h4 class="mb-4"><i class="fas fa-file-medical me-2"></i>Historial Médico</h4>

    <!-- Datos personales -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">🧍 Datos del paciente</div>
        <div class="card-body">
            <p><strong>Nombre:</strong> <?= $paciente['usu_nombre'] . ' ' . $paciente['usu_apellido'] ?></p>
            <p><strong>Cédula:</strong> <?= $paciente['usu_cedula'] ?></p>
            <p><strong>Correo:</strong> <?= $paciente['usu_correo'] ?></p>
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
        <div class="card-header bg-warning text-dark">💉 Vacunas</div>
        <div class="card-body">
            <?php if ($vacunas): ?>
                <ul>
                    <?php foreach ($vacunas as $v): ?>
                        <li><?= $v['nombre'] ?> - <?= $v['dosis'] ?> dosis (<?= date('d/m/Y', strtotime($v['fecha_aplicacion'])) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No hay vacunas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Consultas -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">📑 Consultas médicas</div>
        <div class="card-body">
            <?php if ($consultas): ?>
                <ul>
                    <?php foreach ($consultas as $c): ?>
                        <li><strong><?= date('d/m/Y', strtotime($c['fecha'])) ?>:</strong> Diagnóstico: <?= $c['diagnostico'] ?> | Tratamiento: <?= $c['tratamiento'] ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No hay consultas anteriores registradas.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
