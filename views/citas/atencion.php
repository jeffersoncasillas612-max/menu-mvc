<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de cita no proporcionado</div>";
    exit;
}

require_once 'models/Cita.php';
$modelo = new Cita();

// ID de cita desencriptado y validado
$cita_id = base64_decode($_GET['id']);
$cita_id = ctype_digit((string)$cita_id) ? (int)$cita_id : 0;
if ($cita_id <= 0) {
    echo "<div class='alert alert-danger'>ID de cita inválido</div>";
    exit;
}

// Carga de datos
$detalle    = $modelo->obtenerDetalleCita($cita_id);
$paciente   = $modelo->obtenerInformacionPaciente($detalle['paciente_id']);
$historial  = $modelo->obtenerHistorialClinico($detalle['paciente_id']);
$vacunas    = $modelo->obtenerVacunas($detalle['paciente_id']);
$consultas  = $modelo->obtenerConsultas($detalle['paciente_id']);
$medicamentos = $modelo->obtenerMedicamentos(); // catálogo
if (!is_array($medicamentos)) $medicamentos = [];

include 'views/layouts/header.php';
?>
<div class="container mt-4">
    <h4 class="mb-4">
        <i class="fas fa-user-md me-2"></i>
        Atención médica — Paciente <?= htmlspecialchars($paciente['usu_nombre'] . ' ' . $paciente['usu_apellido']) ?>
    </h4>

    <!-- Datos del paciente -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">🧍 Datos del paciente</div>
        <div class="card-body">
            <p><strong>Cédula:</strong> <?= htmlspecialchars($paciente['usu_cedula']) ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($paciente['usu_correo']) ?></p>
        </div>
    </div>

    <!-- Detalle de la cita -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">📋 Detalle de la cita</div>
        <div class="card-body">
            <p><strong>Fecha y hora:</strong> <?= date('d/m/Y h:i A', strtotime($detalle['fecha'])) ?></p>
            <p><strong>Especialidad:</strong> <?= htmlspecialchars($detalle['especialidad']) ?></p>
            <p><strong>Motivo:</strong> <?= htmlspecialchars($detalle['motivo']) ?></p>
            <p><strong>Tipo de cita:</strong> <?= htmlspecialchars($detalle['tipo_cita']) ?></p>
            <p><strong>Prioridad:</strong> <?= htmlspecialchars($detalle['prioridad']) ?></p>
            <p><strong>Origen:</strong> <?= htmlspecialchars($detalle['origen']) ?></p>
        </div>
    </div>

    <!-- Historial clínico -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">🩺 Historial clínico</div>
        <div class="card-body">
            <?php if ($historial): ?>
                <p><strong>Antecedentes:</strong> <?= nl2br(htmlspecialchars($historial['antecedentes'])) ?></p>
                <p><strong>Alergias:</strong> <?= nl2br(htmlspecialchars($historial['alergias'])) ?></p>
                <p><strong>Enfermedades crónicas:</strong> <?= nl2br(htmlspecialchars($historial['enfermedades_cronicas'])) ?></p>
                <p><strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($historial['observaciones'])) ?></p>
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
            <?php if (!empty($vacunas)): ?>
                <ul class="mb-0">
                    <?php foreach ($vacunas as $v): ?>
                        <li>
                            <?= htmlspecialchars($v['nombre']) ?>
                            (<?= htmlspecialchars($v['dosis']) ?> dosis) —
                            <?= date('d/m/Y', strtotime($v['fecha_aplicacion'])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">No hay vacunas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal: Nueva vacuna -->
    <div class="modal fade" id="modalVacuna" tabindex="-1" aria-labelledby="modalVacunaLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardarVacuna') ?>" method="POST" class="modal-content needs-validation" novalidate>
          <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($paciente['usu_id']) ?>">
          <input type="hidden" name="cita_id" value="<?= htmlspecialchars($cita_id) ?>">
          <div class="modal-header">
            <h5 class="modal-title" id="modalVacunaLabel">Registrar nueva vacuna</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nombre de la vacuna <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="nombre" required>
              <div class="invalid-feedback">Ingresa el nombre de la vacuna.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Dosis <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="dosis" required>
              <div class="invalid-feedback">Ingresa la dosis aplicada.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Consultas anteriores -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">📑 Consultas anteriores</div>
        <div class="card-body">
            <?php if (!empty($consultas)): ?>
                <ul class="mb-0">
                    <?php foreach ($consultas as $c): ?>
                        <li>
                            <strong><?= date('d/m/Y', strtotime($c['fecha'])) ?>:</strong>
                            Diagnóstico: <?= htmlspecialchars($c['diagnostico']) ?>
                            <?php if (!empty($c['tratamiento'])): ?>
                                | Tratamiento: <?= htmlspecialchars($c['tratamiento']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">Sin consultas previas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botón: finalizar consulta -->
    <div class="text-end mb-4">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalFinalizarConsulta">
        <i class="fas fa-check-circle me-2"></i> Finalizar consulta
      </button>
    </div>

    <!-- Modal: Finalizar consulta (con plan de tratamiento) -->
    <div class="modal fade" id="modalFinalizarConsulta" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <form method="POST" action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardarConsulta') ?>" class="modal-content needs-validation" novalidate>
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-notes-medical me-2"></i> Finalizar consulta</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($paciente['usu_id']) ?>">
            <input type="hidden" name="cita_id" value="<?= htmlspecialchars($cita_id) ?>">

            <div class="mb-3">
              <label class="form-label">Diagnóstico <span class="text-danger">*</span></label>
              <textarea name="diagnostico" class="form-control" required rows="3" minlength="5" maxlength="2000" placeholder="Ej.: Faringitis aguda por estreptococo."></textarea>
              <div class="invalid-feedback">Ingresa un diagnóstico (mínimo 5 caracteres).</div>
            </div>

            <div class="mb-4">
              <label class="form-label">Tratamiento (texto libre, opcional)</label>
              <textarea name="tratamiento" class="form-control" rows="2" maxlength="2000" placeholder="Libre/compatibilidad con versiones anteriores."></textarea>
            </div>

            <hr class="my-3">
            <h6 class="mb-3"><i class="fas fa-prescription-bottle-alt me-2"></i> Plan de tratamiento</h6>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nombre del tratamiento <span class="text-danger">*</span></label>
                <input type="text" name="trat_nombre" class="form-control" required maxlength="200" placeholder="Ej.: Amoxicilina + Ibuprofeno">
                <div class="invalid-feedback">Ingresa el nombre del tratamiento.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Fecha de inicio <span class="text-danger">*</span></label>
                <input type="date" name="trat_fecha_inicio" class="form-control" required value="<?= date('Y-m-d') ?>">
                <div class="invalid-feedback">Selecciona la fecha de inicio.</div>
              </div>

              <div class="col-12">
                <label class="form-label">Descripción / Indicaciones</label>
                <textarea name="trat_descripcion" class="form-control" rows="2" maxlength="2000" placeholder="Indicaciones detalladas para el paciente."></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label">Frecuencia</label>
                <select name="trat_frecuencia_text" id="trat_frecuencia_text" class="form-select">
                  <option value="">— Selecciona —</option>
                  <option>Cada 8 horas</option>
                  <option>Cada 12 horas</option>
                  <option>Diario</option>
                  <option>Semanal</option>
                  <option value="otro">Otro (especificar)</option>
                </select>
              </div>

              <div class="col-md-6 d-none" id="wrap_frec_otro">
                <label class="form-label">Frecuencia (otro)</label>
                <input type="text" id="frec_otro" class="form-control" maxlength="100" placeholder="Ej.: Cada 36 horas">
              </div>

              <div class="col-md-4">
                <label class="form-label">Sesiones totales</label>
                <input type="number" name="trat_sesiones_totales" class="form-control" min="1" step="1" placeholder="Ej.: 6">
              </div>

              <div class="col-md-4">
                <label class="form-label">Duración (días)</label>
                <input type="number" name="trat_duracion_dias" class="form-control" min="1" step="1" placeholder="Ej.: 7">
              </div>

              <div class="col-md-4">
                <label class="form-label">Estado <span class="text-danger">*</span></label>
                <select name="trat_estado" class="form-select" required>
                  <option value="activo" selected>Activo</option>
                  <option value="en_progreso">En progreso</option>
                  <option value="completado">Completado</option>
                  <option value="suspendido">Suspendido</option>
                  <option value="cancelado">Cancelado</option>
                </select>
                <div class="invalid-feedback">Selecciona el estado del tratamiento.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Dosis</label>
                <input type="text" name="trat_dosis" class="form-control" maxlength="100" placeholder="Ej.: 500 mg">
              </div>

              <div class="col-md-6">
                <label class="form-label">Vía de administración</label>
                <select name="trat_via_administracion" class="form-select">
                  <option value="">— Selecciona —</option>
                  <option>Oral</option>
                  <option>Intramuscular</option>
                  <option>Intravenosa</option>
                  <option>Subcutánea</option>
                  <option>Tópica</option>
                  <option>Inhalada</option>
                  <option>Sublingual</option>
                  <option>Ótica</option>
                  <option>Óftalmica</option>
                  <option>Rectal</option>
                  <option>Vaginal</option>
                  <option>Otra</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Observaciones</label>
                <textarea name="trat_observaciones" class="form-control" rows="2" maxlength="2000" placeholder="Notas adicionales (reacciones, advertencias, etc.)."></textarea>
              </div>
            </div>

            <input type="hidden" name="trat_sesiones_realizadas" value="0">
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Guardar y finalizar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Registrar receta -->
    <div class="modal fade" id="modalReceta" tabindex="-1" aria-labelledby="modalRecetaLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form method="POST"
              action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardarReceta') ?>"
              class="modal-content needs-validation receta-form" novalidate>

          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalRecetaLabel">
              <i class="fas fa-prescription me-2"></i>Registrar receta
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <input type="hidden" name="paciente_id" value="<?= htmlspecialchars($paciente['usu_id']) ?>">
            <input type="hidden" name="cita_id" value="<?= htmlspecialchars($cita_id) ?>">
            <input type="hidden" name="return" value="index.php?vista=<?= base64_encode('citas/atencion.php') ?>&id=<?= base64_encode($cita_id) ?>&factura=1">

            <div id="items-wrap">
              <div class="card mb-3 item">
                <div class="card-body row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Medicamento <span class="text-danger">*</span></label>
                    <select name="items[0][medicamento_id]" class="form-select" required>
                      <option value="">— Selecciona —</option>
                      <?php foreach ($medicamentos as $m): ?>
                        <option value="<?= htmlspecialchars($m['medicamento_id']) ?>">
                            <?= htmlspecialchars($m['nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Selecciona un medicamento.</div>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Dosis <span class="text-danger">*</span></label>
                    <input type="text" name="items[0][dosis]" class="form-control" required placeholder="Ej.: 500 mg">
                    <div class="invalid-feedback">Ingresa la dosis.</div>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Frecuencia <span class="text-danger">*</span></label>
                    <input type="text" name="items[0][frecuencia]" class="form-control" required placeholder="Ej.: cada 8 horas">
                    <div class="invalid-feedback">Ingresa la frecuencia.</div>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">Días <span class="text-danger">*</span></label>
                    <input type="number" name="items[0][dias]" class="form-control" min="1" required>
                    <div class="invalid-feedback">Ingresa días (≥ 1).</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Indicaciones</label>
                    <textarea name="items[0][indicaciones]" class="form-control" rows="2" placeholder="Indicaciones para el paciente (opcional)"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <button type="button" class="btn btn-outline-primary" id="btnAddItem">
              <i class="fas fa-plus"></i> Agregar medicamento
            </button>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Guardar receta</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>

        </form>
      </div>
    </div>

    <!-- Modal: Factura -->
    <div class="modal fade" id="modalFactura" tabindex="-1">
      <div class="modal-dialog">
        <form method="POST" action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardarFactura') ?>" class="modal-content needs-validation" novalidate>
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i> Registrar factura</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($paciente['usu_id']) ?>">
            <input type="hidden" name="cita_id" value="<?= htmlspecialchars($cita_id) ?>">
            <div class="mb-3">
              <label class="form-label">Total a pagar ($) <span class="text-danger">*</span></label>
              <input type="number" name="total" class="form-control" step="0.01" min="0" required>
              <div class="invalid-feedback">Ingresa el total a pagar.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Guardar factura</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
</div>

<!-- Scripts UI -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Validación Bootstrap + resumen con SweetAlert2 (para TODOS los formularios con .needs-validation)
  (function () {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');

    Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {

        // Si en Consulta se selecciona "otro", volcamos el texto al select antes de enviar
        const sel = form.querySelector('#trat_frecuencia_text');
        const frecOtro = form.querySelector('#frec_otro');
        if (sel && sel.value === 'otro' && frecOtro && frecOtro.value.trim() !== '') {
          const tmp = document.createElement('option');
          tmp.value = frecOtro.value.trim();
          tmp.textContent = frecOtro.value.trim();
          tmp.selected = true;
          sel.appendChild(tmp);
        }

        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();

          const invalids = Array.from(form.querySelectorAll('[required]')).filter(el => !el.checkValidity());
          const pretty = invalids.map(el => {
            let labelText = '';
            if (el.labels && el.labels.length > 0) {
              labelText = el.labels[0].innerText.trim();
            } else {
              const wrap = el.closest('.mb-3, .col, .col-12, .col-md-6, .col-md-4, .col-md-3, .col-md-2');
              if (wrap) {
                const lab = wrap.querySelector('label');
                if (lab) labelText = lab.innerText.trim();
              }
            }
            if (!labelText) labelText = el.getAttribute('placeholder') || el.name || 'Campo';
            return '• ' + labelText.replace('*', '').trim();
          });

          Swal.fire({
            icon: 'warning',
            title: 'Completa los campos obligatorios',
            html: pretty.join('<br>'),
            confirmButtonText: 'Entendido'
          });
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Mostrar/ocultar campo "otro" de Frecuencia
  document.getElementById('trat_frecuencia_text')?.addEventListener('change', function () {
    const wrap = document.getElementById('wrap_frec_otro');
    const input = document.getElementById('frec_otro');
    if (this.value === 'otro') {
      wrap.classList.remove('d-none');
      input.focus();
    } else {
      wrap.classList.add('d-none');
      if (input) input.value = '';
    }
  });

  // Dinámica de ítems de receta
  let recetaIndex = 1;
  const itemsWrap = document.getElementById('items-wrap');
  const btnAddItem = document.getElementById('btnAddItem');
  btnAddItem?.addEventListener('click', () => {
    const last = itemsWrap.querySelector('.item:last-of-type');
    const clone = last.cloneNode(true);
    clone.querySelectorAll('select, input, textarea').forEach(el => {
      el.value = '';
      el.name = el.name.replace(/\[\d+\]/, '[' + recetaIndex + ']');
    });
    // Botón quitar en clones
    const body = clone.querySelector('.card-body');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-danger btn-remove ms-2';
    btn.innerHTML = '<i class="fas fa-trash"></i> Quitar';
    btn.addEventListener('click', () => clone.remove());
    body.appendChild(btn);

    itemsWrap.appendChild(clone);
    recetaIndex++;
  });
</script>

<!-- Autoabrir modales por parámetro -->
<?php if (isset($_GET['receta']) && $_GET['receta'] == 1): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('modalReceta')).show();
  });
</script>
<?php endif; ?>

<?php if (isset($_GET['factura']) && $_GET['factura'] == 1): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('modalFactura')).show();
  });
</script>
<?php endif; ?>

<?php include 'views/layouts/footer.php'; ?>
