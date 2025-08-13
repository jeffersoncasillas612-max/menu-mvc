<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #eef2f5;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: white;
            padding: 35px 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .link-volver {
            display: block;
            margin-top: 15px;
            text-align: center;
        }

        .link-volver a {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9em;
        }

        .link-volver a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>¿Olvidaste tu contraseña?</h2>
    <form method="POST" action="index.php?c=<?= base64_encode('usuario') ?>&a=<?= base64_encode('procesarRecuperacion') ?>" onsubmit="return validarCorreo()">
        <input type="email" name="correo" id="correo" placeholder="Ingresa tu correo" required>
        <button type="submit">Enviar enlace de recuperación</button>
    </form>
    <div class="link-volver">
        <a href="index.php?vista=<?= base64_encode('login.php') ?>">← Volver al inicio de sesión</a>
    </div>
</div>

<script>
    function validarCorreo() {
        const correo = document.getElementById("correo").value.trim();
        if (correo === "") {
            Swal.fire({
                icon: 'warning',
                title: 'Correo requerido',
                text: 'Por favor ingresa tu correo para continuar.'
            });
            return false;
        }
        return true;
    }
</script>
<?php
require_once 'views/layouts/footer.php';
?>

</body>
</html>
