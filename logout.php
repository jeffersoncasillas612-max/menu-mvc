<?php
session_start();
session_unset();
session_destroy();

// Evita caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

// Redirigir al login con parámetro de alerta
$loginURL = 'index.php?c=' . base64_encode('login') . '&a=' . base64_encode('form');

echo "
    <script>
        window.location.href = '$loginURL';
    </script>
";

exit();

