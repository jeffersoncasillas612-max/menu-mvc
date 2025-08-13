<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'views/layouts/header.php';

?>

<h2 style="margin-bottom: 20px;">Crear Nuevo Sub-Menú</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form action="index.php?c=<?= base64_encode('submenu')?>&a=<?= base64_encode('crear') ?>" method="POST" style="max-width: 400px;">
    <label>Nombre del Sub-Menú:</label>
    <input type="text" name="submenu_nombre" required style="width: 100%; padding: 8px; margin-bottom: 10px;">

    <label>Nombre del Menú:</label>
    <select name="menu_id" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
        <option value="">-- Elija un menú --</option>
        <?php foreach ($menus as $menu): ?>
            <option value="<?= $menu['menu_id'] ?>"><?= $menu['menu_nombre'] ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-crear">Guardar</button>
    <a href="index.php?vista=<?= base64_encode('submenus/listar.php') ?>" class="btn btn-cancelar">Cancelar</a>
</form>


<?php include 'views/layouts/footer.php'; ?>

