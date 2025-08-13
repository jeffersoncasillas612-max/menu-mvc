<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'views/layouts/header.php';
?>

<h2 style="margin-bottom: 20px;">Editar Acción</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form action="index.php?c=<?= base64_encode('accion') ?>&a=<?= base64_encode('editar') ?>" method="POST" style="max-width: 500px;">
    <!-- ID cifrado para proteger -->
    <input type="hidden" name="id" value="<?= base64_encode($accion['subsubmenu_id']) ?>">

    <label>Nombre de la Acción:</label>
    <input type="text" name="subsubmenu_nombre" value="<?= $accion['subsubmenu_nombre'] ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px;">

    <label>URL:</label>
    <input type="text" name="url" value="<?= $accion['url'] ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px;">

    <label>Submenú:</label>
    <select name="submenu_id" required style="width: 100%; padding: 8px; margin-bottom: 20px;">
        <option value="">Seleccione un Submenú</option>
        <?php foreach ($submenus as $submenu): ?>
            <option value="<?= $submenu['submenu_id'] ?>" <?= ($submenu['submenu_id'] == $accion['submenu_id']) ? 'selected' : '' ?>>
                <?= $submenu['submenu_nombre'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-crear">Guardar Cambios</button>
    <a href="index.php?vista=<?= base64_encode('acciones/listar.php') ?>" class="btn btn-cancelar">Cancelar</a>
</form>

<?php
require_once 'views/layouts/footer.php';
?>
