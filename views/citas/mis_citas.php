<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol_id'] != 30) {
    echo "<div class='alert alert-danger'>Acceso no autorizado</div>";
    exit;
}

require_once 'models/Cita.php';
$modelo = new Cita();
$modelo->actualizarCitasPerdidas();
$paciente_id = $_SESSION['usuario']['usu_id'];
$citas = $modelo->obtenerCitasPorPaciente($paciente_id);

// Agrupar por fecha
$agrupadas = [];

foreach ($citas as $cita) {
    // clave de agrupación ordenable
    $claveOrden = date('Y-m-d', strtotime($cita['fecha']));
    // etiqueta visual
    $claveVisual = date('d/m/Y', strtotime($cita['fecha']));

    // agrupamos por clave ordenable, pero guardamos la visual
    $agrupadas[$claveOrden]['fecha'] = $claveVisual;
    $agrupadas[$claveOrden]['citas'][] = $cita;
}

ksort($agrupadas); // 🔁 ordena descendente


include 'views/layouts/header.php';
?>

<style>
    .fecha-separador {
        font-weight: 600;
        background: #eef5ff;
        padding: 10px 25px;
        border-left: 5px solid #1e88e5;
        margin: 40px 0 20px;
        border-radius: 6px;
        font-size: 17px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cita-card {
        background: #ffffff;
        border-left: 4px solid #1e88e5;
        border-radius: 10px;
        padding: 15px 18px;
        margin-bottom: 20px;
        box-shadow: 0 0 8px rgba(0,0,0,0.05);
        font-size: 14px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .cita-card h5 {
        font-size: 16px;
        font-weight: 600;
        color: #1e88e5;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .cita-icon {
        font-size: 18px;
    }

    .cita-info p {
        margin: 3px 0;
        font-size: 13.5px;
    }

    @media (max-width: 767px) {
        .cita-card h5 {
            font-size: 15px;
        }
        .fecha-separador {
            font-size: 15px;
        }
    }

    .estado-label {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: bold;
        border-radius: 15px;
        color: white;
        z-index: 2;
    }

    .estado-1 { background-color: #f39c12; } /* Pendiente - naranja */
    .estado-2 { background-color: #3498db; } /* Confirmada - azul */
    .estado-3 { background-color: #2ecc71; } /* Atendida - verde */
    .estado-4 { background-color: #e74c3c; } /* Cancelada - rojo */
    .estado-5 { background-color: #9b59b6; } /* Perdida - morado */
    .estado-6 { background-color: #34495e; } /* Reprogramada - gris oscuro */

    /* Asegúrate que .cita-card tenga posición relativa */
    .cita-card {
        position: relative;
        padding-top: 30px; /* para que el label no se superponga */
    }


</style>
<div class="container mt-4 mb-5">
    <h4 class="text-center mb-4">📋 Mis Citas Programadas</h4>

    <?php if (empty($agrupadas)): ?>
        <div class="alert alert-secondary text-center">No tienes citas registradas.</div>
    <?php else: ksort($agrupadas);?>
    <?php foreach ($agrupadas as $grupo): ?>
        <div class="fecha-separador">
            <i class="fas fa-calendar-alt text-primary"></i> <?= $grupo['fecha'] ?>
        </div>
        <div class="row">
            <?php foreach ($grupo['citas'] as $cita): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="cita-card">
                        <div class="estado-label estado-<?= $cita['estado_id'] ?>">
                            <?= htmlspecialchars($cita['estado_nombre']) ?>
                        </div>
                        <div class="cita-info">
                            <h5><i class="fas fa-user-md cita-icon"></i> <?= htmlspecialchars($cita['especialidad']) ?></h5>
                            <p><strong>Médico:</strong> <?= htmlspecialchars($cita['medico']) ?></p>
                            <p><strong>Hora:</strong> <?= date('h:i A', strtotime($cita['hora'])) ?></p>
                            <p><strong>Motivo:</strong> <?= htmlspecialchars($cita['motivo']) ?></p>
                            <p><strong>Tipo:</strong> <?= htmlspecialchars($cita['tipo_cita']) ?></p>
                            <p><strong>Prioridad:</strong> <?= htmlspecialchars($cita['prioridad']) ?></p>
                            <p><strong>Origen:</strong> <?= htmlspecialchars($cita['origen']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php include 'views/layouts/footer.php'; ?>
