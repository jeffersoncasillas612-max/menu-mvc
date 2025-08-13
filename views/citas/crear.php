<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'models/Cita.php';
$modelo = new Cita();
$especialidades = $modelo->obtenerEspecialidades();
$tipos_cita = $modelo->obtenerTiposCita();
$prioridades = $modelo->obtenerPrioridades();
$origenes = $modelo->obtenerOrigenes();
$urlRegistrar = 'index.php?vista=' . base64_encode('usuarios/crear.php');
include 'views/layouts/header.php';
?>

<!-- ESTILOS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
body {
    background-color: #f3f5f9;
}
h2 {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 30px;
}
.container-cita {
    max-width: 1200px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    gap: 30px;
}
.card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    flex: 1;
}
label {
    font-weight: bold;
}
input, select, textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid #ccc;
}
textarea {
    resize: vertical;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}
.btn-primary {
    background-color: #2c3e50;
    color: white;
}
.btn-secondary {
    background-color: #ccc;
    color: #333;
    margin-left: 10px;
}
.horas-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}
.horas-container button {
    padding: 8px 15px;
    border: 1px solid #2c3e50;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    color: #2c3e50;
}
.horas-container button.selected {
    background: #2c3e50;
    color: white;
}
</style>

<h2>Registrar Nueva Cita</h2>

<div class="container-cita">
    <!-- Formulario -->
    <form class="card" id="form-cita" method="POST" action="index.php?c=<?= base64_encode('cita') ?>&a=<?= base64_encode('guardar') ?>">
        <?php if ($_SESSION['usuario']['rol_id'] == 30): ?>
            <!-- PACIENTE EN SESIÓN -->
            <div style="margin-bottom:10px;">
                <strong>Paciente:</strong> <?= $_SESSION['usuario']['usu_nombre'] . ' ' . $_SESSION['usuario']['usu_apellido'] ?>
                <input type="hidden" name="paciente_id" value="<?= $_SESSION['usuario']['usu_id'] ?>" />
            </div>
        <?php else: ?>
            <!-- ADMIN O RECEPCIONISTA -->
            <label>Cédula del Paciente:</label>
            <input type="text" id="cedula_paciente" name="cedula_paciente" maxlength="10" required />

            <div id="info_paciente" style="margin-bottom:10px; display:none;">
                <strong>Paciente:</strong> <span id="nombre_paciente"></span>
                <input type="hidden" name="paciente_id" id="paciente_id" />
            </div>
        <?php endif; ?>


        <label>Especialidad:</label>
        <select name="especialidad_id" id="especialidad" required>
            <option value="">Seleccione</option>
            <?php foreach ($especialidades as $esp): ?>
                <option value="<?= $esp['especialidad_id'] ?>"><?= $esp['nombre'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Médico:</label>
        <select name="medico_id" id="medico" required>
            <option value="">Seleccione una especialidad primero</option>
        </select>

        <label>Tipo de cita:</label>
        <select name="tipo_cita_id" required>
            <option value="">Seleccione</option>
            <?php foreach ($tipos_cita as $tipo): ?>
                <option value="<?= $tipo['tipo_cita_id'] ?>"><?= $tipo['nombre'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Prioridad:</label>
        <select name="prioridad_id" required>
            <option value="">Seleccione</option>
            <?php foreach ($prioridades as $pri): ?>
                <option value="<?= $pri['prioridad_id'] ?>"><?= $pri['nombre'] ?></option>
            <?php endforeach; ?>
        </select>

        <?php if ($_SESSION['usuario']['rol_id'] == 30): ?>
            <input type="hidden" name="origen_id" value="3" />
        <?php else: ?>
            <label>Origen:</label>
            <select name="origen_id" required>
                <option value="">Seleccione</option>
                <?php foreach ($origenes as $org): ?>
                    <option value="<?= $org['origen_id'] ?>"><?= $org['nombre'] ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>


        <label>Motivo:</label>
        <textarea name="motivo" required rows="3"></textarea>

        <input type="hidden" name="fecha_cita" id="fecha_cita">
        <input type="hidden" name="hora_cita" id="hora_cita">
        <input type="hidden" name="estado_id" value="1" />

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Guardar Cita</button>
            <a href="index.php?vista=<?= base64_encode('citas_medicas/listar') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

    <!-- Calendario y Horas -->
    <div class="card">
        <label>Seleccione una fecha:</label>
        <input type="text" id="fecha" class="form-control" placeholder="Seleccione una fecha" />

        <div style="margin-top: 20px;">
            <strong>Horarios disponibles:</strong>
            <div id="contenedor-horas" class="horas-container">
                <span style="color: gray;">Seleccione un médico y día</span>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
flatpickr("#fecha", {
    inline: true,
    minDate: "today",
    locale: "es",
    dateFormat: "Y-m-d",
    onChange: function (selectedDates, dateStr) {
        document.getElementById("fecha_cita").value = dateStr;
        obtenerHorasDisponibles();
    }
});

document.getElementById('especialidad').addEventListener('change', function () {
    const esp = this.value;
    const medicoSelect = document.getElementById('medico');
    if (esp !== '') {
        fetch(`ajax/citas_ajax.php?accion=medicos_por_especialidad&especialidad_id=${esp}`)
            .then(res => res.json())
            .then(data => {
                medicoSelect.innerHTML = '<option value="">Seleccione</option>';
                data.forEach(medico => {
                    const option = document.createElement('option');
                    option.value = medico.id;
                    option.textContent = medico.nombre;
                    medicoSelect.appendChild(option);
                });
            });
    } else {
        medicoSelect.innerHTML = '<option value="">Seleccione una especialidad primero</option>';
    }
    document.getElementById("fecha_cita").value = "";
    document.getElementById("hora_cita").value = "";
    document.getElementById("contenedor-horas").innerHTML = '<span style="color: gray;">Seleccione un médico y día</span>';
});

document.getElementById("medico").addEventListener("change", obtenerHorasDisponibles);

function obtenerHorasDisponibles() {
    const medicoId = document.getElementById("medico").value;
    const fecha = document.getElementById("fecha_cita").value;
    const contenedor = document.getElementById("contenedor-horas");
    const horaInput = document.getElementById("hora_cita");

    if (medicoId && fecha) {
        fetch(`ajax/citas_ajax.php?accion=horarios_disponibles&medico_id=${medicoId}&fecha=${fecha}`)
            .then(res => res.json())
            .then(data => {
                contenedor.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(hora => {
                        const btn = document.createElement('button');
                        btn.textContent = hora;
                        btn.type = 'button';
                        btn.onclick = () => {
                            horaInput.value = hora;
                            document.querySelectorAll(".horas-container button").forEach(b => b.classList.remove("selected"));
                            btn.classList.add("selected");
                        };
                        contenedor.appendChild(btn);
                    });
                } else {
                    contenedor.innerHTML = '<span style="color: red;">No hay horarios disponibles.</span>';
                }
            });
    }
}

<?php if ($_SESSION['usuario']['rol_id'] != 30): ?>
document.getElementById('cedula_paciente').addEventListener('input', function () {
    const cedula = this.value.trim();
    if (cedula.length === 10) {
        if (this.dataset.lastSearch === cedula) return;
        this.dataset.lastSearch = cedula;
        fetch(`ajax/citas_ajax.php?accion=buscar_paciente&cedula=${cedula}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('info_paciente').style.display = 'block';
                    document.getElementById('nombre_paciente').textContent = data.nombre + ' ' + data.apellido;
                    document.getElementById('paciente_id').value = data.id;
                } else {
                    document.getElementById('info_paciente').style.display = 'none';
                    document.getElementById('paciente_id').value = '';
                    Swal.fire({
                        title: 'Paciente no encontrado',
                        text: 'Primero debe registrar al paciente.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Registrar Paciente',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "<?= $urlRegistrar ?>";
                        }
                    });

                }
            });
    } else {
        document.getElementById('info_paciente').style.display = 'none';
        document.getElementById('paciente_id').value = '';
        this.dataset.lastSearch = '';
    }
});
<?php endif; ?>

document.getElementById('form-cita').addEventListener('submit', function(e) {
    const cedula = document.getElementById("cedula_paciente").value.trim();
    const pacienteId = document.getElementById("paciente_id").value.trim();
    const especialidad = document.getElementById("especialidad").value.trim();
    const medico = document.getElementById("medico").value.trim();
    const fecha = document.getElementById("fecha_cita").value.trim();
    const hora = document.getElementById("hora_cita").value.trim();

    const tipo = document.querySelector("[name='tipo_cita_id']").value.trim();
    const prioridad = document.querySelector("[name='prioridad_id']").value.trim();
    const origen = document.querySelector("[name='origen_id']").value.trim();
    const motivo = document.querySelector("[name='motivo']").value.trim();

    if (!cedula || !pacienteId || !especialidad || !medico || !fecha || !hora || !tipo || !prioridad || !origen || !motivo) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor complete todos los campos antes de guardar la cita.'
        });
    }
});

</script>

<?php include 'views/layouts/footer.php'; ?>