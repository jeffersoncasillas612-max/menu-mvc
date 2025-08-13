<?php if (session_status() === PHP_SESSION_NONE) session_start(); 
$esRecuperacion = isset($_GET['recuperar']) && $_GET['recuperar'] == 1;
$esPrimeraVez = isset($_GET['primeravez']) && $_GET['primeravez'] == 1;
$token = isset($_GET['token']) ? $_GET['token'] : '';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: #eef1f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .form-container {
        background: white;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow: 0 0 12px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
    }

    h2 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 25px;
    }

    .form-group {
        position: relative;
        margin-bottom: 20px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 40px 12px 12px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 8px;
        outline: none;
        transition: border-color 0.3s;
        box-sizing: border-box;
    }

    .form-group input:focus {
        border-color: #007bff;
    }

    .form-group i {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
    }

    .strength {
        font-size: 0.9em;
        color: #777;
        text-align: left;
        margin-bottom: 10px;
    }

    .strength span {
        display: block;
        margin-bottom: 3px;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #28a745;
        color: white;
        font-size: 16px;
        font-weight: bold;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

    button:hover {
        background-color: #218838;
    }
</style>
</head>
<body>
<div class="form-container">
    <h2>Crear nueva contraseña</h2>
    <form id="formCambio" method="POST" action="index.php?c=<?= base64_encode('usuario') ?>&a=<?= base64_encode('cambiarClave') ?>">
        <?php if (isset($_GET['token'])): ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
        <?php endif; ?>


        <div class="form-group">
            <input type="password" name="nueva_contrasena" id="nueva_contrasena" placeholder="Nueva contraseña" required>
            <i class="fa-solid fa-eye toggle" id="toggleNueva"></i>
        </div>

        <div class="strength" id="indicador">
            <span id="longitud">🔴 Mínimo 8 caracteres</span>
            <span id="mayus">🔴 1 Mayúscula</span>
            <span id="numero">🔴 1 Número</span>
            <span id="especial">🔴 1 Símbolo</span>
        </div>

        <div class="form-group">
            <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" placeholder="Confirmar contraseña" required>
            <i class="fa-solid fa-eye toggle" id="toggleConfirmar"></i>
        </div>

        <button type="submit">Guardar nueva contraseña</button>
    </form>
</div>

<script>
const nueva = document.getElementById("nueva_contrasena");
const confirmar = document.getElementById("confirmar_contrasena");
const toggleNueva = document.getElementById("toggleNueva");
const toggleConfirmar = document.getElementById("toggleConfirmar");

const longitud = document.getElementById("longitud");
const mayus = document.getElementById("mayus");
const numero = document.getElementById("numero");
const especial = document.getElementById("especial");

toggleNueva.addEventListener("click", () => {
    nueva.type = nueva.type === "password" ? "text" : "password";
    toggleNueva.classList.toggle("fa-eye");
    toggleNueva.classList.toggle("fa-eye-slash");
});

toggleConfirmar.addEventListener("click", () => {
    confirmar.type = confirmar.type === "password" ? "text" : "password";
    toggleConfirmar.classList.toggle("fa-eye");
    toggleConfirmar.classList.toggle("fa-eye-slash");
});

nueva.addEventListener("input", () => {
    const val = nueva.value;
    longitud.innerText = val.length >= 8 ? "🟢 Mínimo 8 caracteres" : "🔴 Mínimo 8 caracteres";
    mayus.innerText = /[A-Z]/.test(val) ? "🟢 1 Mayúscula" : "🔴 1 Mayúscula";
    numero.innerText = /\d/.test(val) ? "🟢 1 Número" : "🔴 1 Número";
    especial.innerText = /[^A-Za-z0-9]/.test(val) ? "🟢 1 Símbolo" : "🔴 1 Símbolo";
});

document.getElementById("formCambio").addEventListener("submit", function (e) {
    if (nueva.value !== confirmar.value) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Contraseñas no coinciden',
            text: 'Ambas contraseñas deben ser iguales.'
        });
    }
});
</script>
<?php include 'views/layouts/footer.php'; ?>
</body>
</html>
