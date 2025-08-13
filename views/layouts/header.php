<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/database.php';
require_once 'models/Menu.php';

$rol_id = $_SESSION['usuario']['rol_id'];
$sidebarModel = new Menu();
$sidebarMenus = $sidebarModel->obtenerMenusCompletos($rol_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= isset($tituloPagina) ? $tituloPagina . ' - Panel de Administración' : 'Panel de Administración' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Iconos y estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <!-- Lottie -->
    <script src="https://unpkg.com/lottie-web@5.10.2/build/player/lottie.min.js"></script>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; display: flex; }

        .sidebar {
            width: 260px;
            background-color: #2c3e50;
            color: white;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            transition: left 0.3s ease-in-out;
            z-index: 998;
        }

        .sidebar h2 {
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 10px;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            display: block;
            padding: 8px 15px;
            border-radius: 6px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .submenu-toggle {
            cursor: pointer;
            display: block;
            padding: 8px 15px;
            color: #ecf0f1;
            transition: 0.3s;
        }

        .submenu-toggle:hover {
            background-color: #34495e;
        }

        .sidebar ul ul {
            padding-left: 20px;
            display: none;
        }

        .sidebar ul ul li a {
            font-size: 14px;
            color: #bdc3c7;
        }

        .main {
            margin-left: 260px;
            padding: 40px;
            width: 100%;
            transition: margin-left 0.3s ease;
        }

        .btn-navegar {
            background-color: #2980b9;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s;
            margin-left: 8px;
        }

        .btn-navegar:hover {
            background-color: #1f6391;
        }

        /* Botón hamburguesa */
        .toggle-sidebar {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px 14px;
            font-size: 20px;
            z-index: 999;
            border-radius: 5px;
        }

        /* Fondo oscuro cuando el menú está activo */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 997;
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            .sidebar {
                left: -260px;
            }

            .sidebar.active {
                left: 0;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .toggle-sidebar {
                display: block;
            }

            .sidebar.active ~ .sidebar-overlay {
                display: block;
            }
        }

        .cerrar-sesion-wrapper {
    margin-top: 40px;
}

.btn-cerrar-sesion {
    background-color: #e74c3c;
    width: 100%;
    display: block;
    text-align: center;
}

@media (min-width: 769px) {
    .cerrar-sesion-wrapper {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: 220px;
    }
}

    </style>
</head>
<body>

<!-- Botón hamburguesa -->
<button class="toggle-sidebar" onclick="toggleSidebar()" id="btnToggleSidebar">
    <i class="fa-solid fa-bars" id="iconToggle"></i>
</button>


<!-- Overlay (solo móvil) -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar">
    <!-- Lottie -->
    <div id="robot-medico" style="width: 140px; height: 140px; margin: 0 auto 0px;"></div>

    <ul>
        <li>
            <a href="index.php?c=<?= base64_encode('panel') ?>&a=<?= base64_encode('inicio') ?>">
                <i class="fa-solid fa-house icon"></i> Casa
            </a>
        </li>

        <?php foreach ($sidebarMenus as $sidebarMenu): ?>
            <li>
                <span class="submenu-toggle" onclick="toggleSubmenu('menu<?= $sidebarMenu['menu_id'] ?>')">
                    <i class="fa-solid fa-bars icon"></i>
                    <?= htmlspecialchars($sidebarMenu['menu_nombre']) ?>
                    <i class="fa fa-chevron-down" style="float: right;"></i>
                </span>
                <ul id="menu<?= $sidebarMenu['menu_id'] ?>">
                    <?php foreach ($sidebarMenu['submenus'] as $sidebarSubmenu): ?>
                        <li>
                            <span class="submenu-toggle" onclick="toggleSubmenu('submenu<?= $sidebarSubmenu['submenu_id'] ?>')">
                                <i class="fa-solid fa-folder icon"></i>
                                <?= htmlspecialchars($sidebarSubmenu['submenu_nombre']) ?>
                                <i class="fa fa-chevron-right" style="float: right;"></i>
                            </span>
                            <ul id="submenu<?= $sidebarSubmenu['submenu_id'] ?>">
                                <?php foreach ($sidebarSubmenu['acciones'] as $sidebarAccion): ?>
                                    <li>
                                        <a href="index.php?vista=<?= base64_encode($sidebarAccion['url']) ?>">
                                            <i class="fa-solid fa-circle-dot icon"></i>
                                            <?= htmlspecialchars($sidebarAccion['subsubmenu_nombre']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Botón cerrar sesión -->
    <div class="cerrar-sesion-wrapper">
        <a href="logout.php" class="btn-navegar btn-cerrar-sesion">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
        </a>
    </div>

</div>

<!-- Contenido principal -->
<div class="main">

<script>
    function toggleSubmenu(id) {
        const element = document.getElementById(id);
        element.style.display = element.style.display === "block" ? "none" : "block";
    }

    function toggleSidebar() {
        const sidebar = document.querySelector(".sidebar");
        const overlay = document.querySelector(".sidebar-overlay");
        const icon = document.getElementById("iconToggle");

        sidebar.classList.toggle("active");

        // Cambiar ícono según el estado del menú
        if (sidebar.classList.contains("active")) {
            icon.classList.remove("fa-bars");
            icon.classList.add("fa-xmark"); // ícono de cerrar
        } else {
            icon.classList.remove("fa-xmark");
            icon.classList.add("fa-bars");
        }

        // Mostrar u ocultar fondo oscuro (opcional)
        if (overlay) {
            overlay.style.display = sidebar.classList.contains("active") ? "block" : "none";
        }
    }

    // Lottie: robot médico
    lottie.loadAnimation({
        container: document.getElementById('robot-medico'),
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: '/MenuMVC/animaciones/Live chatbot.json' // Ajusta la ruta si es necesario
    });
</script><?php include 'views/layouts/footer.php'; ?>