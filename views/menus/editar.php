<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include 'views/layouts/header.php';
?>

<h2 style="margin-bottom: 20px;">Editar Menú</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" style="max-width: 400px;">
    <!-- Campo oculto con el ID cifrado -->
    <input type="hidden" name="id" value="<?= base64_encode($menu['menu_id']) ?>">

    <label>Nombre del Menú:</label>
    <input type="text" name="menu_nombre" value="<?= $menu['menu_nombre'] ?>" required>

    <label>Icono:</label>
    <input type="text" name="menu_icono" value="<?= $menu['menu_icono'] ?>">

    <br><br>

    <button type="submit" class="btn btn-editar">Guardar Cambios</button>
    <a href="index.php?vista=<?= base64_encode('menus/listar.php') ?>" class="btn btn-cancelar">Cancelar</a>

</form>


<?php include 'views/layouts/footer.php'; ?>