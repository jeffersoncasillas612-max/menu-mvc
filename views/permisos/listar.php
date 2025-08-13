<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($permisosPorRol)) {
    require_once 'controllers/PermisoController.php';
    $controller = new PermisoController();
    $controller->listar();
    exit();
}

include 'views/layouts/header.php';
?>

<style>
    
    

    .card-rol {
        background: #ffffff;
        border-radius: 12px;
        padding: 25px 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: 0.3s;
    }

    .card-rol:hover {
        transform: translateY(-2px);
    }

    .rol-titulo {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #2c3e50;
        border-left: 4px solid #3498db;
        padding-left: 10px;
    }

    .menu-nombre {
        font-size: 16px;
        font-weight: bold;
        color: #34495e;
        margin-top: 15px;
        display: flex;
        align-items: center;
    }

    .menu-nombre i {
        margin-right: 8px;
        color: #3498db;
    }

    .submenu-nombre {
        margin-left: 20px;
        font-size: 15px;
        color: #2d3436;
        margin-top: 8px;
    }

    .accion-nombre {
        margin-left: 40px;
        font-size: 14px;
        color: #636e72;
        margin-top: 4px;
        display: flex;
        align-items: center;
    }

    .accion-nombre i {
        font-size: 11px;
        margin-right: 5px;
        color: #7f8c8d;
    }

    .sin-permisos {
        color: #aaa;
        margin-left: 10px;
        font-style: italic;
    }

    .editar-permisos {
        text-align: right;
        margin-top: 10px;
    }
</style>

<div class="permiso-header">
    <h2><i class="fa-solid fa-lock"></i> Permisos por Rol</h2>
    <div>
        <a href="index.php?c=<?= base64_encode('rol') ?>&a=<?= base64_encode('listar') ?>" class="btn-navegar">
            <i class="fa-solid fa-list"></i> Listar Roles
        </a>
        <a href="index.php?c=<?= base64_encode('rol')?>&a=<?= base64_encode('crear') ?>" class="btn-navegar" style="background-color: #27ae60;">
            <i class="fa-solid fa-plus"></i> Crear Rol
        </a>
    </div>
</div>

<?php foreach ($roles as $rol): ?>
    <div class="card-rol">
        <div class="rol-titulo">
            <i class="fa-solid fa-user-tag"></i> <?= htmlspecialchars($rol['rol_nombre']) ?>
        </div>

        <div class="editar-permisos">
            <a href="index.php?c=<?= base64_encode('permiso') ?>&a=<?= base64_encode('editar') ?>&id=<?= base64_encode($rol['rol_id']) ?>"
               class="btn-navegar" style="background-color: #f39c12;">
                <i class="fa-solid fa-pen-to-square"></i> Editar Permisos
            </a>
        </div>

        <?php if (!empty($permisosPorRol[$rol['rol_id']])): ?>
            <?php foreach ($permisosPorRol[$rol['rol_id']] as $menu): ?>
                <div class="menu-nombre">
                    <i class="fa-solid fa-bars-staggered"></i> <?= htmlspecialchars($menu['menu_nombre']) ?>
                </div>

                <?php if (!empty($menu['submenus'])): ?>
                    <?php foreach ($menu['submenus'] as $submenu): ?>
                        <div class="submenu-nombre">
                            <i class="fa-solid fa-folder-open" style="margin-right: 6px; color:#2c3e50;"></i>
                            <?= htmlspecialchars($submenu['submenu_nombre']) ?>
                        </div>
                        <?php if (!empty($submenu['acciones'])): ?>
                            <?php foreach ($submenu['acciones'] as $accion): ?>
                                <div class="accion-nombre">
                                    <i class="fa-solid fa-circle-dot"></i> <?= htmlspecialchars($accion['subsubmenu_nombre']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sin-permisos">Este menú no tiene submenús asignados.</div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="sin-permisos">Este rol aún no tiene permisos asignados.</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php include 'views/layouts/footer.php'; ?>
