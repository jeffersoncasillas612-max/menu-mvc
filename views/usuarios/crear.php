<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'models/Rol.php';
$rolModel = new Rol();
$roles = $rolModel->obtenerTodos();

require_once 'models/Especialidad.php';
$espModel = new Especialidad();
$especialidades = $espModel->obtenerTodas();



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'])) {
    $cedula = $_POST['cedula'];
    $url = "http://156.244.32.23:8080/api/Cedula/$cedula";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    header('Content-Type: application/json');
    echo $response;
    exit;
}

include 'views/layouts/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .form-container {
        max-width: 600px;
        margin: 40px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    h2 {
        margin-bottom: 25px;
        color: #333;
    }

    label {
        font-weight: bold;
        margin-top: 10px;
        display: block;
    }

    input, select {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        margin-bottom: 20px;
        border-radius: 6px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    input[readonly] {
        background-color: #f3f3f3;
    }

    .btn-guardar {
        background-color: #28a745;
        color: white;
        padding: 12px;
        font-weight: bold;
        width: 100%;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn-guardar:hover {
        background-color: #218838;
    }
</style>

<div class="form-container">
    <h2>Crear Nuevo Usuario</h2>

    <label>Cédula:</label>
    <input type="text" id="cedula" maxlength="10" placeholder="Ingresa la cédula" />

    <form action="index.php?c=<?= base64_encode('usuario') ?>&a=<?= base64_encode('guardar') ?>" method="POST" id="formUsuario">
        <input type="hidden" name="cedula" id="f_cedula" />

        <label>Nombre:</label>
        <input type="text" name="nombre" id="nombre" required readonly />

        <label>Apellido:</label>
        <input type="text" name="apellido" id="apellido" required readonly />

        <label>Correo:</label>
        <input type="email" name="correo" required />

        <label>Rol:</label>
        <select name="rol_id" id="rol_id" required onchange="mostrarEspecialidades()">
            <option value="" disabled selected>--- Seleccione un rol ---</option>
            <?php foreach ($roles as $rol): ?>
                <option value="<?= $rol['rol_id'] ?>">
                    <?= htmlspecialchars($rol['rol_nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="esp_container" style="display:none;">
            <label>Especialidad:</label>
            <select name="especialidad_id" id="especialidad_id">
                <option value="">--- Selecciona una especialidad ---</option>
                <?php foreach ($especialidades as $esp): ?>
                    <option value="<?= $esp['especialidad_id'] ?>"><?= $esp['nombre'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>


        <button type="submit" class="btn-guardar">Guardar</button>
    </form>
</div>

<script>
document.getElementById('cedula').addEventListener('input', async function () {
    const cedula = this.value;
    if (cedula.length === 10) {
        Swal.fire({
            title: 'Consultando...',
            text: 'Verificando datos del registro civil...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ cedula: cedula })
            });

            const data = await res.json();

            if (data.datos) {
                Swal.fire({
                    icon: 'success',
                    title: 'Datos encontrados',
                    text: 'Los campos han sido completados automáticamente.'
                });

                document.getElementById('nombre').value = data.datos.nombres || '';
                document.getElementById('apellido').value = data.datos.apellidos || '';
                document.getElementById('f_cedula').value = cedula;
                document.getElementById('nombre').readOnly = true;
                document.getElementById('apellido').readOnly = true;
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No encontrado',
                    text: 'No se encontraron datos. Puedes completar manualmente.'
                });

                document.getElementById('nombre').value = '';
                document.getElementById('apellido').value = '';
                document.getElementById('f_cedula').value = cedula;
                document.getElementById('nombre').readOnly = false;
                document.getElementById('apellido').readOnly = false;
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un problema al consultar la cédula.'
            });

            document.getElementById('nombre').readOnly = false;
            document.getElementById('apellido').readOnly = false;
        }
    }
});



function mostrarEspecialidades() {
    const rol = document.getElementById('rol_id').value;
    const espContainer = document.getElementById('esp_container');
    const espSelect = document.getElementById('especialidad_id');

    if (rol === '31') {
        espContainer.style.display = 'block';
        espSelect.required = true;
    } else {
        espContainer.style.display = 'none';
        espSelect.value = '';
        espSelect.required = false;
    }
}
</script>

<?php include 'views/layouts/footer.php'; ?>
