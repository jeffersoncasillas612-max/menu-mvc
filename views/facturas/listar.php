<?php if (session_status() === PHP_SESSION_NONE) session_start(); 


require_once 'models/Factura.php';
$modelo = new Factura();

$facturasRecientes = $modelo->obtenerUltimosDosMeses();
?>
<?php include 'views/layouts/header.php'; ?>

<div class="container mt-4">
    <h4><i class="fas fa-file-invoice-dollar me-2"></i> Facturas pendientes</h4>

    <!-- Buscador por cédula -->
    <form action="index.php?c=<?= base64_encode('factura') ?>&a=<?= base64_encode('buscar') ?>" method="POST" class="row g-3 my-3">
        <div class="col-md-4">
            <input type="text" name="cedula" class="form-control" placeholder="Buscar por cédula" required>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Buscar</button>
        </div>
    </form>

    <?php if (isset($facturas) && count($facturas) > 0): ?>
        <div class="row">
            <?php foreach ($facturas as $f): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-user me-1"></i> <?= htmlspecialchars($f['nombre_completo']) ?></h5>
                            <p class="card-text mb-1"><strong>Cédula:</strong> <?= htmlspecialchars($f['cedula']) ?></p>
                            <p class="card-text mb-1"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($f['fecha'])) ?></p>
                            <p class="card-text mb-1"><strong>Total:</strong> $<?= number_format($f['valor'], 2) ?></p>
                            <p class="card-text">
                                <strong>Estado:</strong>
                                <?= $f['estado'] == 1 
                                    ? '<span class="badge bg-warning text-dark">Pendiente</span>' 
                                    : '<span class="badge bg-success">Pagada</span>' ?>
                            </p>
                        </div>
                        <?php if ($f['estado'] == 1): ?>
                            <div class="card-footer text-end">
                                <a href="index.php?c=<?= base64_encode('factura') ?>&a=<?= base64_encode('marcarPagada') ?>&id=<?= base64_encode($f['factura_id']) ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-check-circle me-1"></i> Marcar como pagada
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (isset($facturas)): ?>
        <div class="alert alert-info">No se encontraron facturas con esa cédula.</div>
    <?php endif; ?>




    
    <!-- Facturas recientes (2 meses) -->
    <h5 class="mt-5">🕒 Últimos 2 meses</h5>
        <div class="row">
            <?php if (isset($facturasRecientes) && count($facturasRecientes) > 0): ?>
                <?php foreach ($facturasRecientes as $f): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm border <?= $f['estado'] == 1 ? 'border-warning' : 'border-success' ?>">
                            <div class="card-body">
                                <h6 class="card-title mb-2"><?= htmlspecialchars($f['nombre_completo']) ?></h6>
                                <p class="mb-1"><strong>Cédula:</strong> <?= htmlspecialchars($f['cedula']) ?></p>
                                <p class="mb-1"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($f['fecha'])) ?></p>
                                <p class="mb-1"><strong>Valor:</strong> $<?= number_format($f['valor'], 2) ?></p>
                                <span class="badge <?= $f['estado'] == 1 ? 'bg-warning text-dark' : 'bg-success' ?>">
                                    <?= $f['estado'] == 1 ? 'Pendiente' : 'Pagada' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-secondary">No hay facturas recientes registradas.</div>
                </div>
            <?php endif; ?>
        </div>
</div>


<?php include 'views/layouts/footer.php'; ?>
