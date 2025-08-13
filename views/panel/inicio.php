<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'views/layouts/header.php';

$nombre = $_SESSION['usuario']['usu_nombre'];
$apellido = $_SESSION['usuario']['usu_apellido'];
?>

<script src="https://unpkg.com/lottie-web@5.10.2/build/player/lottie.min.js"></script>

<!-- ESTILO PROFESIONAL -->
<style>
    body {
        background-color: #f9f9f9;
        font-family: 'Segoe UI', sans-serif;
    }

    .bienvenida-container {
        max-width: 900px;
        margin: 80px auto;
        padding: 40px;
        background: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        text-align: center;
        animation: fadeIn 1.5s ease-in-out;
    }

    .bienvenida-icon svg {
        width: 90px;
        height: 90px;
        margin-bottom: 20px;
        animation: pulse 2.5s infinite;
    }

    .home-title {
        font-size: 32px;
        color: #004d40;
        margin-bottom: 10px;
    }

    .welcome-msg {
        font-size: 18px;
        color: #555;
    }

    /* Animaciones */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.08); }
        100% { transform: scale(1); }
    }
</style>

<div class="bienvenida-container">
    <div id="animacion-lottie" style="width: 200px; height: 200px; margin: 0 auto;"></div>

    <h1 class="home-title">¡Bienvenido, <?= $nombre . ' ' . $apellido ?>!</h1>
    <p class="welcome-msg">Accede y gestiona la plataforma médica con base en tus permisos y responsabilidades.</p>
</div>

<script>
    lottie.loadAnimation({
        container: document.getElementById('animacion-lottie'),
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: '/MenuMVC/animaciones/Medical app.json' // 👈 ajusta según profundidad real
    });
</script>



<!-- <?php include 'views/layouts/footer.php'; ?> -->
