<?php

if (session_status() === PHP_SESSION_NONE) session_start();

// Si no viene desde el controlador, lo ejecutamos
if (!isset($submenus)) {
    require_once 'controllers/SubmenuController.php';
    $controller = new SubmenuController();
    $controller->listar(); // Esto recargará esta vista con los datos desde el controlador
    exit(); // Evita doble carga de vista
}

// Incluir solo si viene del controlador (una sola vez)
include 'views/layouts/header.php';
?>


<h2 style="margin-bottom: 20px;">Listado de Sub-Menú</h2>

<a href="index.php?c=<?= base64_encode('submenu')?>&a=<?= base64_encode('crear') ?>" class="btn btn-crear">
    + Crear Nuevo Sub-Menú
</a><br><br>


<table class="table">
    <thead style="background-color: #2c3e50; color: white;">
        <tr>
            <th>ID</th>
            <th>Nombre del Sub-Menú</th>
            <th>Nombre del Menú</th>
            <th>Estado</th>            
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($submenus as $submenu): ?>
            <tr style="border-bottom: 1px solid #ccc;">
                <td><?= $submenu['submenu_id'] ?></td>
                <td><?= $submenu['submenu_nombre'] ?></td>
                <td><?= $submenu['menu_nombre'] ?></td>
                <td>
                    <?= $submenu['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                </td>
                <td class="td-acciones">
                    <a href="index.php?c=<?= base64_encode('submenu') ?>&a=<?= base64_encode('editar') ?>&id=<?= base64_encode($submenu['submenu_id']) ?>" class="btn btn-editar">
                        Editar
                    </a>
                    <a href="#" onclick="eliminarSubmenu(<?= $submenu['submenu_id'] ?>)" class="btn btn-eliminar">
                        Eliminar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function eliminarSubmenu(submenuId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'No podrás revertir esta acción.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const idCifrado = btoa(submenuId); // Codificar en base64 desde JS
                window.location.href = `index.php?c=<?= base64_encode('submenu') ?>&a=<?= base64_encode('eliminar') ?>&id=${idCifrado}`;
            }
        });
    }
</script>


<?php include 'views/layouts/footer.php'; ?>