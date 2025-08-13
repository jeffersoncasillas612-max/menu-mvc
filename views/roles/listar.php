<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Si no viene desde el controlador, lo ejecutamos
if (!isset($roles)) {
    require_once 'controllers/RolController.php';
    $controller = new RolController();
    $controller->listar(); // Esto recargará esta vista con datos
    exit(); // Evita doble renderizado
}

// Incluir solo si viene del controlador (una sola vez)
$tituloPagina = 'Listado de Roles';
include 'views/layouts/header.php';
?>


<div class="permiso-header">
    <h2><i class="fa-solid fa-lock"></i> Listado de Roles</h2>
    <div>
        <a href="index.php?c=<?= base64_encode('permiso') ?>&a=<?= base64_encode('listar') ?>" class="btn-navegar">
            <i class="fa-solid fa-list"></i> Listar Permisos
        </a>
        <a href="index.php?c=<?= base64_encode('rol')?>&a=<?= base64_encode('crear') ?>" class="btn-navegar" style="background-color: #27ae60;">
            <i class="fa-solid fa-plus"></i> Crear Rol
        </a>
    </div>
</div>




<table class="table">
    <thead style="background-color: #2c3e50; color: white;">
        <tr>
            <th>ID</th>
            <th>Nombre del Rol</th>
            <th>Estado</th>            
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($roles as $rol): ?>
            <tr style="border-bottom: 1px solid #ccc;">
                <td><?= $rol['rol_id'] ?></td>
                <td><?= $rol['rol_nombre'] ?></td>
                <td>
                    <?= $rol['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                </td>
                <td class="td-acciones">
                    <a href="index.php?c=<?= base64_encode('rol') ?>&a=<?= base64_encode('editar') ?>&id=<?= base64_encode($rol['rol_id']) ?>" class="btn btn-editar">
                        Editar
                    </a>
                    <a href="#" onclick="eliminarRol(<?= $rol['rol_id'] ?>)" class="btn btn-eliminar">
                        Eliminar
                    </a>

                </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function eliminarRol(id) {
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
            // Codificamos el ID en base64 en JavaScript
            const idCifrado = btoa(id); // base64 encode de JS
            window.location.href = `index.php?c=<?= base64_encode('rol')?>&a=<?= base64_encode('eliminar') ?>&id=${idCifrado}`;
        }
    });
}
</script>



<?php include 'views/layouts/footer.php'; ?>
