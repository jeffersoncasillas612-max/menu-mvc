<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'views/layouts/header.php';
?>

<h2 style="margin-bottom: 20px;">Crear Nuevo Menú</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form action="index.php?c=<?= base64_encode('menu')?>&a=<?= base64_encode('crear') ?>" method="POST" style="max-width: 400px;">
    <label>Nombre del Menú:</label>
    <input type="text" name="menu_nombre" required style="width: 100%; padding: 8px; margin-bottom: 10px;">

    <label>Icono:</label>
    <input type="text" name="menu_icono" style="width: 100%; padding: 8px; margin-bottom: 10px;">

    <button type="submit" class="btn btn-crear">Guardar</button>
    <a href="index.php?vista=<?= base64_encode('menus/listar.php') ?>" class="btn btn-cancelar">Cancelar</a>
</form>

<?php include 'views/layouts/footer.php'; ?>