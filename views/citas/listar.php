<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

// Verificamos si viene una señal de éxito desde el guardar
$exito = $_GET['exito'] ?? null;

include 'views/layouts/header.php';
?>

<!-- Cargamos SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<h2 style="margin-bottom: 20px;">Listado de Citas</h2>

<a href="index.php?vista=<?= base64_encode('citas/crear.php') ?>" class="btn btn-crear">
    + Crear Nueva Cita
</a><br><br>

<!-- Aquí iría tu tabla de citas -->

<?php if ($exito === '1'): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        icon: 'success',
        title: 'Cita guardada correctamente',
        showConfirmButton: false,
        timer: 2000
    });
});
</script>
<?php endif; ?>


