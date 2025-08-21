<?php
// views/tele/triage.php
require_once __DIR__.'/../../models/Cita.php';
$model = new Cita();

$token = $_GET['token'] ?? '';
$tele  = $model->obtenerTelecitaPorToken($token); // Debe devolver al menos cita_id

if (!$tele) { http_response_code(404); echo "Token inválido"; exit; }

// Evitar doble llenado: si ya existe triaje para esa cita, mostramos aviso
$yaExisteStmt = $model->conn->prepare("SELECT 1 FROM triaje WHERE cita_id = ? LIMIT 1");
$yaExisteStmt->execute([$tele['cita_id']]);
$triajeYaEnviado = (bool)$yaExisteStmt->fetchColumn();

// Traemos info bonita de la cita para mostrar en cabecera
$detalleCita = $model->obtenerDetalleCita($tele['cita_id']); // usa tu método ya existente

$exito = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$triajeYaEnviado) {
    // Validación básica del lado servidor (números y rangos)
    $num = fn($k,$min,$max,$step=1) => isset($_POST[$k]) && is_numeric($_POST[$k]) && $_POST[$k] >= $min && $_POST[$k] <= $max ? $_POST[$k] : null;

    $peso  = $num('peso', 2, 500, 0.1);
    $talla = $num('talla_cm', 30, 250, 0.1);
    $temp  = $num('temperatura_c', 30, 45, 0.1);
    $ps    = $num('presion_sistolica', 60, 250);
    $pd    = $num('presion_diastolica', 40, 150);
    $fc    = $num('fc_lpm', 30, 220);
    $fr    = $num('fr_rpm', 8, 40);
    $sato2 = $num('sato2_pct', 50, 100);

    $sintomas     = trim($_POST['sintomas'] ?? '');
    $alergias     = trim($_POST['alergias'] ?? '');
    $antecedentes = trim($_POST['antecedentes'] ?? '');
    $medicacion   = trim($_POST['medicacion_actual'] ?? '');
    $otros        = trim($_POST['otros'] ?? '');

    if ($peso && $talla && $temp && $ps && $pd && $fc && $fr && $sato2 && $sintomas !== '') {
        $ok = $model->conn->prepare("
            INSERT INTO triaje
            (cita_id,peso,talla_cm,temperatura_c,presion_sistolica,presion_diastolica,fc_lpm,fr_rpm,sato2_pct,sintomas,alergias,antecedentes,medicacion_actual,otros)
            VALUES
            (:cita_id,:peso,:talla,:temp,:ps,:pd,:fc,:fr,:sato2,:sintomas,:alergias,:antecedentes,:medicacion,:otros)
        ")->execute([
            ':cita_id'      => $tele['cita_id'],
            ':peso'         => $peso,
            ':talla'        => $talla,
            ':temp'         => $temp,
            ':ps'           => $ps,
            ':pd'           => $pd,
            ':fc'           => $fc,
            ':fr'           => $fr,
            ':sato2'        => $sato2,
            ':sintomas'     => $sintomas,
            ':alergias'     => $alergias,
            ':antecedentes' => $antecedentes,
            ':medicacion'   => $medicacion,
            ':otros'        => $otros
        ]);

        if ($ok) {
            $model->marcarTriajeCompletado($tele['cita_id']); // tu helper existente
            $exito = true;
            $triajeYaEnviado = true;
        } else {
            $error = 'No se pudo guardar el triaje. Intenta nuevamente.';
        }
    } else {
        $error = 'Revisa los campos obligatorios y sus rangos.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Triaje preconsulta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f7fb; }
    .card { border: 0; box-shadow: 0 10px 25px rgba(0,0,0,.06); border-radius: 1rem; }
    .section-title { font-weight: 700; color: #1f2d3d; }
    .form-section { margin-bottom: 1.25rem; }
    .required::after { content:" *"; color:#dc3545; }
    .maxw-700 { max-width: 760px; }
    .pill { display:inline-block; background:#eef2ff; color:#3949ab; padding:.25rem .6rem; border-radius:999px; font-size:.85rem; }
  </style>
</head>
<body>
<div class="container py-4">

  <!-- Cabecera con resumen de cita -->
  <div class="row justify-content-center mb-3">
    <div class="col-12 col-lg-10">
      <div class="card p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
          <div>
            <h4 class="mb-1">Triaje preconsulta</h4>
            <div class="text-secondary">
              <?php
              $pac = $detalleCita['paciente'] ?? 'Paciente';
              $med = $detalleCita['medico'] ?? 'Médico asignado';
              $esp = $detalleCita['especialidad'] ?? 'Especialidad';
              $fh  = isset($detalleCita['fecha']) ? date('d/m/Y H:i', strtotime($detalleCita['fecha'])) : '';
              ?>
              <div><span class="pill me-2">Paciente</span><?= htmlspecialchars($pac) ?></div>
              <div><span class="pill me-2 mt-1">Médico</span><?= htmlspecialchars($med) ?> — <?= htmlspecialchars($esp) ?></div>
              <div><span class="pill me-2 mt-1">Fecha</span><?= htmlspecialchars($fh) ?></div>
            </div>
          </div>
          <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg/1f468-200d-2695-fe0f.svg" width="56" height="56" alt="doctor">
        </div>
      </div>
    </div>
  </div>

  <?php if ($triajeYaEnviado): ?>
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="alert alert-success border-0 shadow-sm">
          <strong>¡Gracias!</strong> Tu triaje ya fue enviado para esta cita. No es necesario volver a completarlo.
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($error) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($exito): ?>
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="card p-4 text-center">
          <h5 class="mb-2">✅ Triaje enviado</h5>
          <p class="mb-0 text-secondary">Gracias por completar la información. El médico revisará tus datos antes de la consulta.</p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Formulario -->
  <div class="row justify-content-center <?= ($triajeYaEnviado ? 'd-none' : '') ?>">
    <div class="col-12 col-lg-10">
      <form method="post" class="card p-3 p-md-4 needs-validation" novalidate id="formTriaje">
        <!-- Signos vitales -->
        <h5 class="section-title mb-3">Signos vitales</h5>
        <div class="row g-3 form-section">
          <div class="col-6 col-md-4">
            <label class="form-label required">Peso (kg)</label>
            <input type="number" class="form-control" name="peso" min="2" max="500" step="0.1" required>
            <div class="invalid-feedback">Entre 2 y 500 kg.</div>
          </div>
          <div class="col-6 col-md-4">
            <label class="form-label required">Talla (cm)</label>
            <input type="number" class="form-control" name="talla_cm" min="30" max="250" step="0.1" required>
            <div class="invalid-feedback">Entre 30 y 250 cm.</div>
          </div>
          <div class="col-6 col-md-4">
            <label class="form-label required">Temperatura (°C)</label>
            <input type="number" class="form-control" name="temperatura_c" min="30" max="45" step="0.1" required>
            <div class="invalid-feedback">Entre 30 y 45 °C.</div>
          </div>

          <div class="col-6 col-md-4">
            <label class="form-label required">PA Sistólica (mmHg)</label>
            <input type="number" class="form-control" name="presion_sistolica" min="60" max="250" required>
            <div class="invalid-feedback">Entre 60 y 250 mmHg.</div>
          </div>
          <div class="col-6 col-md-4">
            <label class="form-label required">PA Diastólica (mmHg)</label>
            <input type="number" class="form-control" name="presion_diastolica" min="40" max="150" required>
            <div class="invalid-feedback">Entre 40 y 150 mmHg.</div>
          </div>
          <div class="col-6 col-md-4">
            <label class="form-label required">Frecuencia cardiaca (lpm)</label>
            <input type="number" class="form-control" name="fc_lpm" min="30" max="220" required>
            <div class="invalid-feedback">Entre 30 y 220 lpm.</div>
          </div>

          <div class="col-6 col-md-4">
            <label class="form-label required">Frecuencia respiratoria (rpm)</label>
            <input type="number" class="form-control" name="fr_rpm" min="8" max="40" required>
            <div class="invalid-feedback">Entre 8 y 40 rpm.</div>
          </div>
          <div class="col-6 col-md-4">
            <label class="form-label required">SatO₂ (%)</label>
            <input type="number" class="form-control" name="sato2_pct" min="50" max="100" required>
            <div class="invalid-feedback">Entre 50 y 100 %.</div>
          </div>
        </div>

        <!-- Antecedentes y síntomas -->
        <h5 class="section-title mb-3">Antecedentes y síntomas</h5>
        <div class="row g-3 form-section">
          <div class="col-12">
            <label class="form-label required">Síntomas actuales</label>
            <textarea class="form-control" name="sintomas" rows="3" maxlength="1000" required></textarea>
            <div class="form-text">Describe lo que sientes (dolor, fiebre, tos, etc.).</div>
            <div class="invalid-feedback">Este campo es obligatorio.</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Alergias</label>
            <textarea class="form-control" name="alergias" rows="2" maxlength="600" placeholder="Medicamentos, alimentos, etc."></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Antecedentes médicos</label>
            <textarea class="form-control" name="antecedentes" rows="2" maxlength="600" placeholder="Enfermedades crónicas, cirugías, etc."></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Medicación actual</label>
            <textarea class="form-control" name="medicacion_actual" rows="2" maxlength="600" placeholder="Nombre y dosis si aplica"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Otros datos relevantes</label>
            <textarea class="form-control" name="otros" rows="2" maxlength="600" placeholder="Algo que debamos saber antes de la consulta"></textarea>
          </div>
        </div>

        <div class="d-grid d-sm-flex gap-2">
          <button class="btn btn-primary px-4" type="submit" id="btnEnviar">
            <span class="spinner-border spinner-border-sm me-2 d-none" id="spn"></span>
            Enviar triaje
          </button>
          <a class="btn btn-outline-secondary" href="javascript:history.back()">Volver</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(() => {
  const form = document.getElementById('formTriaje');
  if (!form) return;

  // Bootstrap validation + chequeos adicionales simples
  form.addEventListener('submit', (e) => {
    // Evitar envío si falla HTML5
    if (!form.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }

    // Validación cruzada de PA (sistólica > diastólica)
    const ps = parseFloat(form.presion_sistolica.value || 0);
    const pd = parseFloat(form.presion_diastolica.value || 0);
    if (ps && pd && ps <= pd) {
      e.preventDefault();
      e.stopPropagation();
      form.presion_sistolica.classList.add('is-invalid');
      form.presion_diastolica.classList.add('is-invalid');
      alert('La presión sistólica debe ser mayor que la diastólica.');
    }

    form.classList.add('was-validated');

    // Spinner + bloqueo de botón si todo OK
    if (form.checkValidity() && ps > pd) {
      const btn = document.getElementById('btnEnviar');
      const spn = document.getElementById('spn');
      btn.disabled = true;
      spn.classList.remove('d-none');
    }
  }, false);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
