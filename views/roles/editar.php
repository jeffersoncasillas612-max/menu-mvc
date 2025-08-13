<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'views/layouts/header.php';
?>

<h2 style="margin-bottom: 20px;">Editar Rol</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" style="max-width: 400px;">
    <!-- Campo oculto con el ID cifrado -->
    <input type="hidden" name="id" value="<?= base64_encode($rol['rol_id']) ?>">

    <label>Nombre del rol:</label>
    <input type="text" name="rol_nombre" value="<?= $rol['rol_nombre'] ?>" required>

    <br><br>

    <button type="submit" class="btn btn-editar">Guardar Cambios</button>
    <a href="index.php?vista=<?= base64_encode('roles/listar.php') ?>" class="btn btn-cancelar">Cancelar</a>

</form>


<?php include 'views/layouts/footer.php'; ?>
