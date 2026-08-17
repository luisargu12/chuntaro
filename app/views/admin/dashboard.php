<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
        </h2>
        <small class="text-muted">
            Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?>
        </small>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-5 text-center">
            <h4>Panel de administración (prueba)</h4>
            <p class="text-muted mt-3 mb-0">
                Login listo. Aquí después van torneos, plantilla y datos de EA FC.
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
