<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($acciones)) {
    require_once 'controllers/AccionController.php';
    $controller = new AccionController();
    $controller->listar();
    exit();
}

include 'views/layouts/header.php';
?>

<h2 style="margin-bottom: 20px;">Listado de Acciones</h2>

<a href="index.php?c=<?= base64_encode('accion')?>&a=<?= base64_encode('crear') ?>" class="btn btn-crear">
    + Crear Nueva Acción
</a><br><br>

<table class="table">
    <thead style="background-color: #2c3e50; color: white;">
        <tr>
            <th>ID</th>            
            <th>Menú / Submenú</th>
            <th>Nombre de la Acción</th>
            <th>URL</th>          
            <th>Estado</th>            
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($acciones as $accion): ?>
            <tr style="border-bottom: 1px solid #ccc;">
                <td><?= $accion['subsubmenu_id'] ?></td>
                <td><?= $accion['menu_nombre'] . ' / ' . $accion['submenu_nombre'] ?></td>
                <td><?= $accion['subsubmenu_nombre'] ?></td>
                <td><?= $accion['url'] ?></td>
                <td><?= $accion['estado'] == 1 ? 'Activo' : 'Inactivo' ?></td>
                <td class="td-acciones">
                    <a href="index.php?c=<?= base64_encode('accion') ?>&a=<?= base64_encode('editar') ?>&id=<?= base64_encode($accion['subsubmenu_id']) ?>" class="btn btn-editar">
                        Editar
                    </a>
                    <a href="#" onclick="eliminarAccion(<?= $accion['subsubmenu_id'] ?>)" class="btn btn-eliminar">
                        Eliminar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function eliminarAccion(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?c=<?= base64_encode('accion') ?>&a=<?= base64_encode('eliminar') ?>&id=${btoa(id.toString())}`;
        }
    });
}
</script>

<?php
require_once 'views/layouts/footer.php';
?>
