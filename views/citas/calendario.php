<?php
include 'views/layouts/header.php';

// Verifica que haya sesión activa del médico
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red'>No hay sesión activa</p>";
    exit;
}

$medico_id = $_SESSION['usuario']['usu_id'];
$nombre    = $_SESSION['usuario']['usu_nombre'];
$apellido  = $_SESSION['usuario']['usu_apellido'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario de Citas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Estilos y scripts necesarios para FullCalendar y Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    <!-- Estilos personalizados -->
    <style>
        body {
            background-color: #f4f6f9;
        }
        #calendario {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        #panel-lateral {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            height: 100%;
        }
        .card-evento {
            background: #ffffff;
            border-left: 5px solid #007bff;
            border-radius: 6px;
            box-shadow: 0 0 6px rgba(0,0,0,0.1);
            padding: 10px 15px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .card-evento small {
            display: block;
            color: #888;
        }
    </style>
</head>
<body>

<!-- Contenedor principal -->
<div class="container-fluid mt-4">
    <h4 class="text-center mb-4">📅 Calendario de citas - <?= $nombre . ' ' . $apellido ?></h4>
    <div class="row">
        <!-- Panel lateral con resumen -->
        <div class="col-md-3" id="panel-lateral">
            <h5>Resumen de eventos</h5>
            <div>
                <h6 class="text-primary">🟢 Próximos eventos</h6>
                <div id="eventos-futuros"></div>
            </div>
            <hr>
            <div>
                <h6 class="text-secondary">🔴 Eventos pasados</h6>
                <div id="eventos-pasados"></div>
            </div>
        </div>

        <!-- Calendario -->
        <div class="col-md-9">
            <div id="calendario"></div>
        </div>
    </div>
</div>

<!-- Modal para mostrar detalles de la cita -->
<div class="modal fade" id="modalDetalleCita" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalTituloCita">
          <i class="fas fa-calendar-check me-2"></i>Detalle de Cita
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalCuerpoCita">
        <!-- Aquí se inyectará el contenido dinámico -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button id="btnAtenderCita" class="btn btn-success">
            <i class="fas fa-stethoscope"></i> Atender
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendario');

    // Inicializar FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'hoy',
            month: 'mes',
            week: 'semana',
            day: 'día'
        },
        height: 650,

        // Fuente de eventos
        events: 'ajax/citas_eventos.php',

        // Al hacer clic en un evento
        eventClick: function(info) {
            const cita_id = info.event.id;

            fetch('ajax/cita_detalle.php?id=' + cita_id)
                .then(res => res.json())
                .then(data => {
                    if (data && data.cita_id) {
                        document.getElementById('modalTituloCita').textContent = `Cita del ${new Date(data.fecha_cita).toLocaleDateString('es-EC')}`;
                        document.getElementById('modalCuerpoCita').innerHTML = `
                            <p><strong>Paciente:</strong> ${data.paciente}</p>
                            <p><strong>Motivo:</strong> ${data.motivo}</p>
                            <p><strong>Especialidad:</strong> ${data.especialidad}</p>
                            <p><strong>Prioridad:</strong> ${data.prioridad}</p>
                            <p><strong>Origen:</strong> ${data.origen}</p>
                            <p><strong>Hora:</strong> ${data.hora_cita}</p>
                        `;
                        // Asignar ID al botón
                        document.getElementById('btnAtenderCita').onclick = () => {
                            window.location.href = `index.php?vista=${btoa('citas/atencion.php')}&id=${btoa(data.cita_id)}`;
                        };

                        new bootstrap.Modal(document.getElementById('modalDetalleCita')).show();
                    } else {
                        alert("No se pudo cargar el detalle de la cita.");
                    }
                })
                .catch(() => {
                    alert("Error al obtener los datos de la cita.");
                });
        },

        // Cuando los eventos estén cargados, actualizar resumen lateral
        eventDidMount: function () {
            actualizarResumenEventos();
        }
    });

    calendar.render();

    // Actualiza los eventos en el panel lateral
    function actualizarResumenEventos() {
        const hoy = new Date();
        const eventos = calendar.getEvents();
        const vista = calendar.view;
        const inicioRango = vista.currentStart;
        const finRango = vista.currentEnd;

        const futuros = [];
        const pasados = [];

        eventos.forEach(evento => {
            const fecha = new Date(evento.start);
            if (fecha >= inicioRango && fecha <= finRango) {
                if (fecha >= hoy) {
                    futuros.push(evento);
                } else {
                    pasados.push(evento);
                }
            }
        });

        mostrarEventos('eventos-futuros', futuros);
        mostrarEventos('eventos-pasados', pasados);
    }

    // Muestra los eventos (futuros o pasados) en el panel lateral
    function mostrarEventos(id, eventos) {
        const contenedor = document.getElementById(id);
        contenedor.innerHTML = '';

        if (eventos.length === 0) {
            contenedor.innerHTML = '<p class="text-muted">Sin eventos</p>';
            return;
        }

        eventos
            .sort((a, b) => new Date(a.start) - new Date(b.start))
            .forEach(ev => {
                const fecha = new Date(ev.start);
                const textoFecha = fecha.toLocaleDateString('es-EC', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });

                contenedor.innerHTML += `
                    <div class="card-evento" style="border-left-color: ${ev.backgroundColor || '#007bff'}">
                        <strong>${ev.title}</strong>
                        <small>${textoFecha}</small>
                    </div>
                `;
            });
    }

    // Detectar cambios de vista para refrescar los eventos del lateral
    calendar.on('datesSet', actualizarResumenEventos);
    calendar.on('eventAdd', actualizarResumenEventos);
    calendar.on('eventChange', actualizarResumenEventos);
    calendar.on('eventRemove', actualizarResumenEventos);
});
</script>

<?php
require_once 'views/layouts/footer.php';
?>

</body>
</html>
