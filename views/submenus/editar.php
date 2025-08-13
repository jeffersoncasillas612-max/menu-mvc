<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include 'views/layouts/header.php';
?>

<h2 style="margin-bottom: 20px;">Editar Sub-Menú</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" style="max-width: 400px;">
    <!-- Campo oculto con el ID cifrado -->
    <input type="hidden" name="id" value="<?= base64_encode($submenu['submenu_id']) ?>">

    <label>Nombre del Sub-Menú:</label>
    <input type="text" name="submenu_nombre" value="<?= $submenu['submenu_nombre'] ?>" required>

    <label>Nombre del Menú:</label>
    <select name="menu_id" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
        <option value="">-- Elija un menú --</option>
        <?php foreach ($menus as $menu): ?>
            <option value="<?= $menu['menu_id'] ?>" <?= $submenu['menu_id'] == $menu['menu_id'] ? 'selected' : '' ?>><?= $menu['menu_nombre'] ?></option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <button type="submit" class="btn btn-editar">Guardar Cambios</button>
    <a href="index.php?vista=<?= base64_encode('submenus/listar.php') ?>" class="btn btn-cancelar">Cancelar</a>

</form>


<?php include 'views/layouts/footer.php'; ?>
