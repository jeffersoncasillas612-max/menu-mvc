<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php include 'views/layouts/header.php'; ?>

<style>
    .form-container {
        max-width: 800px;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        margin: 0 auto;
    }

    .form-container h2 {
        font-size: 24px;
        color: #2c3e50;
        margin-bottom: 25px;
    }

    .form-container label {
        font-weight: bold;
        color: #34495e;
    }

    .menu-card {
        border-left: 5px solid #3498db;
        background: #f7f9fa;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
    }

    .menu-card label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-weight: 600;
        font-size: 16px;
    }

    .submenu-list, .accion-list {
        margin-left: 25px;
        margin-top: 8px;
        display: none;
    }

    .submenu-list label, .accion-list label {
        font-weight: normal;
        font-size: 15px;
        margin-top: 5px;
        display: flex;
        align-items: center;
    }

    .btn-crear, .btn-cancelar {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        margin-right: 10px;
        cursor: pointer;
    }

    .btn-crear {
        background-color: #f39c12;
        color: white;
    }

    .btn-cancelar {
        background-color: #e74c3c;
        color: white;
    }

    .btn-crear:hover {
        background-color: #d78b0c;
    }

    .btn-cancelar:hover {
        background-color: #c0392b;
    }
</style>

<div class="form-container">
    <h2><i class="fa-solid fa-pen-to-square"></i> Editar Permisos de Rol: <?= htmlspecialchars($rol['rol_nombre']) ?></h2>

    <form method="POST">
        <?php foreach ($estructura as $menu): ?>
            <?php
                $checkedMenu = in_array($menu['menu_id'], $permisosExistentes['menu']) ? 'checked' : '';
            ?>
            <div class="menu-card">
                <label>
                    <input type="checkbox" class="menu-checkbox" data-id="<?= $menu['menu_id'] ?>" name="permisos[menu][]" value="<?= $menu['menu_id'] ?>" <?= $checkedMenu ?> style="margin-right: 8px;">
                    <i class="fa-solid fa-bars-staggered" style="margin-right: 8px;"></i> <?= $menu['menu_nombre'] ?>
                </label>

                <div class="submenu-list" id="submenu-m<?= $menu['menu_id'] ?>" style="<?= $checkedMenu ? 'display:block;' : '' ?>">
                    <?php foreach ($menu['submenus'] as $submenu): ?>
                        <?php
                            $checkedSub = in_array($submenu['submenu_id'], $permisosExistentes['submenu']) ? 'checked' : '';
                        ?>
                        <label>
                            <input type="checkbox" class="submenu-checkbox" data-id="<?= $submenu['submenu_id'] ?>" data-parent="<?= $menu['menu_id'] ?>" name="permisos[submenu][]" value="<?= $submenu['submenu_id'] ?>" <?= $checkedSub ?> style="margin-right: 8px;">
                            <i class="fa-solid fa-folder-open" style="margin-right: 6px; color:#34495e;"></i> <?= $submenu['submenu_nombre'] ?>
                        </label>

                        <div class="accion-list" id="accion-s<?= $submenu['submenu_id'] ?>" style="<?= $checkedSub ? 'display:block;' : '' ?>">
                            <?php foreach ($submenu['acciones'] as $accion): ?>
                                <?php
                                    $checkedAcc = in_array($accion['subsubmenu_id'], $permisosExistentes['accion']) ? 'checked' : '';
                                ?>
                                <label>
                                    <input type="checkbox" class="accion-checkbox" data-parent="<?= $submenu['submenu_id'] ?>" name="permisos[accion][]" value="<?= $accion['subsubmenu_id'] ?>" <?= $checkedAcc ?> style="margin-right: 8px;">
                                    <i class="fa-solid fa-circle-dot" style="font-size: 11px; color: #7f8c8d; margin-right: 5px;"></i> <?= $accion['subsubmenu_nombre'] ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <br>
        <button type="submit" class="btn btn-crear"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>
        <a href="index.php?vista=<?= base64_encode('permisos/listar.php') ?>" class="btn btn-cancelar"> Cancelar</a>
    </form>
</div>

<script>
    document.querySelectorAll('.menu-checkbox').forEach(menu => {
        menu.addEventListener('change', function () {
            const id = this.dataset.id;
            const submenus = document.getElementById('submenu-m' + id);
            if (this.checked) {
                submenus.style.display = 'block';
            } else {
                submenus.style.display = 'none';
                submenus.querySelectorAll('input[type="checkbox"]').forEach(c => {
                    c.checked = false;
                    if (c.classList.contains('submenu-checkbox')) {
                        const acc = document.getElementById('accion-s' + c.dataset.id);
                        if (acc) {
                            acc.style.display = 'none';
                            acc.querySelectorAll('input[type="checkbox"]').forEach(a => a.checked = false);
                        }
                    }
                });
            }
        });
    });

    document.querySelectorAll('.submenu-checkbox').forEach(sub => {
        sub.addEventListener('change', function () {
            const id = this.dataset.id;
            const acciones = document.getElementById('accion-s' + id);
            if (this.checked) {
                acciones.style.display = 'block';
            } else {
                acciones.style.display = 'none';
                acciones.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
            }
        });
    });
</script>

<?php include 'views/layouts/footer.php'; ?>
