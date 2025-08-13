<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'views/layouts/header.php';
?>

<h2 style="margin-bottom: 20px;">Crear Nueva Acción</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form action="index.php?c=<?= base64_encode('accion') ?>&a=<?= base64_encode('crear') ?>" method="POST" style="max-width: 500px;">
    <label>Nombre de la Acción:</label>
    <input type="text" name="subsubmenu_nombre" required style="width: 100%; padding: 8px; margin-bottom: 10px;">

    <label>URL:</label>
    <input type="text" name="url" required placeholder="ej: usuarios/crear.php" style="width: 100%; padding: 8px; margin-bottom: 10px;">

    <label>Submenú al que pertenece:</label>
    <select name="submenu_id" required style="width: 100%; padding: 8px; margin-bottom: 20px;">
        <option value="">Seleccione un submenú</option>
        <?php foreach ($menus as $item): ?>
            <option value="<?= $item['submenu_id'] ?>">
                <?= $item['menu_nombre'] ?> → <?= $item['submenu_nombre'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-crear">Guardar</button>
    <a href="index.php?vista=<?= base64_encode('acciones/listar.php') ?>" class="btn btn-cancelar">Cancelar</a>
</form>

<?php
require_once 'views/layouts/footer.php';
?>