<?php

use App\Config\App;

$rutaActual = trim((string) ($_GET['ruta'] ?? ''), '/');
$esTorneo = $rutaActual === 'torneo';
$pageTitle = $esTorneo ? 'Torneo' : 'Martes Botanero';
$navSolid = true;
$descripcion = $esTorneo
    ? 'Aquí estará la herramienta y la información de los torneos.'
    : 'Aquí seguiremos al equipo amateur, sus partidos y estadísticas.';

require __DIR__ . '/../layouts/public_header.php';
?>

<section class="page-hero text-white d-flex align-items-end">
    <div class="container position-relative z-2 pb-4">
        <h1 class="titulo-banner mb-2"
            style="font-size: clamp(2rem, 6vw, 3.5rem); color: var(--color-primary)">
            <?= htmlspecialchars($pageTitle) ?>
        </h1>
    </div>
</section>

<main class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-5 text-center">
            <h2 class="fw-bold mb-3">Próximamente</h2>
            <p class="text-muted fs-5 mb-4"><?= htmlspecialchars($descripcion) ?></p>
            <a href="<?= htmlspecialchars(App::url('/')) ?>" class="btn btn-dark">
                Volver al inicio
            </a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
