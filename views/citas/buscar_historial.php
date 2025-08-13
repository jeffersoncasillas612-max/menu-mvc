<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'views/layouts/header.php';

// ⚠️ Evitar errores si aún no se ha definido alguna variable
$paciente = $paciente ?? null;
$historial = $historial ?? null;
$vacunas = $vacunas ?? [];
$citas = $citas ?? [];
$error = $error ?? null;
?>


<style>
    .contenedor-formulario {
        display: flex;
        justify-content: center;
        margin: 30px 0;
    }

    .formulario-busqueda {
        display: flex;
        align-items: center;
        gap: 10px;
        background-color: #f9f9f9;
        padding: 15px 20px;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }

    .campo-cedula {
        position: relative;
        display: flex;
        align-items: center;
        margin-top: 15px;
    }

    .campo-cedula input[type="text"] {
        padding: 10px 40px 10px 14px;
        border: 1px solid #ccc;
        border-radius: 10px;
        font-size: 16px;
        height: 45px;
        width: 300px;
    }

    .campo-cedula button#btn-limpiar {
        position: absolute;
        right: 8px;
        top: 40%;
        transform: translateY(-50%);
        background: #eee;
        border: none;
        height: 26px;
        width: 26px;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #btn-buscar {
        height: 45px;
        padding: 0 20px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    @media screen and (max-width: 600px) {
        .formulario-busqueda {
            flex-direction: column;
            align-items: stretch;
        }

        .campo-cedula,
        #btn-buscar {
            width: 100%;
        }

        .campo-cedula input {
            width: 100%;
        }
    }
</style>


<h3><i class="fa-solid fa-notes-medical me-2"></i> Buscar historial clínico</h3>
<hr>

<div class="contenedor-formulario">
    <form method="POST" action="index.php?c=<?= base64_encode('historial') ?>&a=<?= base64_encode('buscar') ?>" class="formulario-busqueda">
        <div class="campo-cedula">
            <input type="text" name="cedula" id="cedula" placeholder="Ingrese la cédula" value="<?= htmlspecialchars($_POST['cedula'] ?? '') ?>">
            <button type="button" id="btn-limpiar" title="Limpiar">×</button>
        </div>

        <button type="submit" id="btn-buscar">
            <i class="fa-solid fa-magnifying-glass"></i> Buscar
        </button>

        <?php if ($paciente): ?>
            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalEnviarPDF">
                <i class="fa-solid fa-paper-plane"></i> Enviar PDF
            </button>
        <?php endif; ?>


    </form>
</div>




<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($paciente): ?>
    <div class="row row-cols-1 row-cols-md-2 g-4">

        <!-- Información del paciente -->
        <div class="col">
            <div class="card border-info h-100">
                <div class="card-header bg-info text-white fw-bold">🧍 Información del paciente</div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> <?= $paciente['usu_nombre'] ?> <?= $paciente['usu_apellido'] ?></p>
                    <p><strong>Cédula:</strong> <?= htmlspecialchars($_POST['cedula']) ?></p>
                </div>
            </div>
        </div>

        <!-- Historial clínico -->
        <div class="col">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white fw-bold">🩺 Historial clínico</div>
                <div class="card-body">
                    <p><strong>Antecedentes:</strong> <?= nl2br($historial['antecedentes'] ?? 'N/A') ?></p>
                    <p><strong>Enfermedades crónicas:</strong> <?= nl2br($historial['enfermedades_cronicas'] ?? 'N/A') ?></p>
                    <p><strong>Alergias:</strong> <?= nl2br($historial['alergias'] ?? 'N/A') ?></p>
                    <p><strong>Observaciones:</strong> <?= nl2br($historial['observaciones'] ?? 'N/A') ?></p>
                </div>
            </div>
        </div>

        <!-- Vacunas -->
        <div class="col-md-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark fw-bold">💉 Vacunas registradas</div>
                <div class="card-body">
                    <?php if (!empty($vacunas)): ?>
                        <ul class="mb-0">
                            <?php foreach ($vacunas as $v): ?>
                                <li><strong><?= $v['nombre'] ?></strong> - <?= $v['dosis'] ?> dosis (<?= date('d/m/Y', strtotime($v['fecha_aplicacion'])) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No hay vacunas registradas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Citas médicas -->
        <div class="col-md-12">
            <div class="card border-dark">
                <div class="card-header bg-dark text-white fw-bold">📅 Citas médicas</div>
                <div class="card-body table-responsive">
                    <?php if (!empty($citas)): ?>
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Médico</th>
                                    <th>Especialidad</th>
                                    <th>Tipo</th>
                                    <th>Prioridad</th>
                                    <th>Origen</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citas as $c): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                                        <td><?= date('H:i', strtotime($c['hora'])) ?></td>
                                        <td><?= $c['medico'] ?></td>
                                        <td><?= $c['especialidad'] ?></td>
                                        <td><?= $c['tipo_cita'] ?></td>
                                        <td><?= $c['prioridad'] ?></td>
                                        <td><?= $c['origen'] ?></td>
                                        <td><?= $c['motivo'] ?></td>
                                        <td><?= $c['estado_nombre'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No hay citas médicas registradas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
<?php endif; ?>


<!-- Modal para Enviar PDF -->
<div class="modal fade" id="modalEnviarPDF" tabindex="-1" aria-labelledby="modalEnviarPDFLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalEnviarPDFLabel">Enviar historial clínico</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Vas a enviar un correo con el historial clínico.</p>
        <p>¿El destinatario está registrado en nuestro sistema?</p>

        <!-- Opciones -->
        <div class="form-check">
        <input class="form-check-input" type="radio" name="opcion_destino" id="opcion_si" value="si">
        <label class="form-check-label" for="opcion_si">Sí</label>
        </div>
        <div class="form-check mb-3">
        <input class="form-check-input" type="radio" name="opcion_destino" id="opcion_no" value="no">
        <label class="form-check-label" for="opcion_no">No</label>
        </div>

        <!-- Cedula o correo -->
        <div id="campo_cedula" style="display: none;">
        <label for="cedula_destinatario" class="form-label">Cédula del destinatario:</label>
        <input type="text" class="form-control" id="cedula_destinatario" placeholder="Ingrese la cédula">
        </div>

        <div id="campo_correo" style="display: none;">
        <label for="correo_destinatario" class="form-label">Correo electrónico:</label>
        <input type="email" class="form-control" id="correo_destinatario" placeholder="Ingrese el correo">
        </div>
        <input type="hidden" id="cedula_historial_actual" value="<?= $_SESSION['cedula_historial_actual'] ?? '' ?>">


      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnEnviarCorreoPDF">
          <i class="fa-solid fa-envelope"></i> Enviar PDF
        </button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('btnEnviarCorreoPDF').addEventListener('click', function () {
    const opcion = document.querySelector('input[name="opcion_destino"]:checked');
    if (!opcion) {
        Swal.fire('Seleccione una opción', 'Debe indicar si el destinatario está registrado o no.', 'warning');
        return;
    }

    let data = new FormData();
    data.append('accion', 'enviarPDF');

    if (opcion.value === 'si') {
        const cedula = document.getElementById('cedula_destinatario').value.trim();
        if (cedula === '') {
            Swal.fire('Falta la cédula', 'Ingrese la cédula del destinatario.', 'warning');
            return;
        }
        data.append('tipo', 'cedula');
        data.append('valor', cedula);
    } else {
        const correo = document.getElementById('correo_destinatario').value.trim();
        if (correo === '') {
            Swal.fire('Falta el correo', 'Ingrese el correo del destinatario.', 'warning');
            return;
        }
        data.append('tipo', 'correo');
        data.append('valor', correo);
    }

    const cedula_historial = document.getElementById('cedula_historial_actual')?.value;
    data.append('cedula_historial', cedula_historial); // 👈 Aquí

    Swal.fire({
        title: 'Enviando historial...',
        html: 'Por favor espere unos segundos.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('pdf/HistorialPDF.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(resp => {
        Swal.close();
                if (resp.status === 'ok') {
                    Swal.fire({
            icon: 'success',
            title: '¡Correo enviado!',
            html: 'Historial clínico enviado a:<br><b>' + resp.msg + '</b>',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            // Redirige a la misma vista 'buscar historial' codificada
            const url = 'index.php?vista=' . base64_encode('citas/buscar_historial.php');
            window.location.href = url;
        });


        } else {
            Swal.fire('Error', resp.msg || 'No se pudo enviar el correo.', 'error');
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Swal.fire('Error de red', 'No se pudo completar la solicitud.', 'error');
    });
});

</script>



<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('input[name="opcion_destino"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      const mostrarCedula = this.value === 'si';
      document.getElementById('campo_cedula').style.display = mostrarCedula ? 'block' : 'none';
      document.getElementById('campo_correo').style.display = mostrarCedula ? 'none' : 'block';
    });
  });
});
</script>




<script>
    document.getElementById('btn-limpiar').addEventListener('click', function () {
        const input = document.getElementById('cedula');
        input.value = '';
        input.focus();
    });
</script>

<!-- <?php require_once 'views/layouts/footer.php'; ?> -->
