<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

// Si no viene desde el controlador, lo ejecutamos (por ejemplo desde ?vista=menus/listar.php)
if (!isset($menus)) {
    require_once 'controllers/MenuController.php';
    $controller = new MenuController();
    $controller->listar(); // Esto recargará esta vista con los datos desde el controlador
    exit(); // Evita doble carga de vista
}

// Incluir solo si ya viene con datos del controlador
include 'views/layouts/header.php';
?>



<h2 style="margin-bottom: 20px;">Listado de Menús</h2>

<a href="index.php?c=<?= base64_encode('menu')?>&a=<?= base64_encode('crear') ?>" class="btn btn-crear">
    + Crear Nuevo Menú
</a><br><br>


<table class="table">
    <thead style="background-color: #2c3e50; color: white;">
        <tr>
            <th>ID</th>
            <th>Nombre del Menú</th>
            <th>Estado</th>            
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($menus as $menu): ?>
            <tr style="border-bottom: 1px solid #ccc;">
                <td><?= $menu['menu_id'] ?></td>
                <td><?= $menu['menu_nombre'] ?></td>
                <td>
                    <?= $menu['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                </td>
                <td class="td-acciones">
                    <a href="index.php?c=<?= base64_encode('menu') ?>&a=<?= base64_encode('editar') ?>&id=<?= base64_encode($menu['menu_id']) ?>" class="btn btn-editar">
                        Editar
                    </a>

                    <a href="#" onclick="eliminarMenu(<?= $menu['menu_id'] ?>)" class="btn btn-eliminar">
                        Eliminar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function eliminarMenu(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const idCifrado = btoa(id); // Codificamos el ID a base64 en JS
            window.location.href = `index.php?c=<?= base64_encode('menu') ?>&a=<?= base64_encode('eliminar') ?>&id=${idCifrado}`;
        }
    });
}
</script>


<?php include 'views/layouts/footer.php'; ?>

