<?php

use App\Config\App;

require __DIR__ . '/../layouts/public_header.php';
?>

<header class="hero-section d-flex align-items-end justify-content-start text-start pb-5"
        style="--hero-image: url('<?= htmlspecialchars(App::asset('img/hero.jpg')) ?>')">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="container position-relative z-2">
        <div style="overflow: hidden">
            <h1 class="titulo-banner mb-0">CHUNTARO FC</h1>
        </div>
        <div class="linea mt-3"></div>
    </div>
</header>

<main class="container py-5 my-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold mb-3">Dos canchas, un mismo equipo</h2>
        <p class="text-muted fs-5">
            Sigue al equipo amateur de los martes y al club virtual de EA FC.
        </p>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-md-6">
            <article class="card h-100 border-0 shadow-sm card-hover">
                <div class="card-body p-5 text-center">
                    <h2 class="fw-bold mb-3">Martes Botanero</h2>
                    <p class="text-muted mb-4">
                        Partidos, plantilla y estadísticas del equipo amateur.
                    </p>
                    <a href="<?= htmlspecialchars(App::url('/martes-botanero')) ?>"
                       class="btn btn-outline-dark btn-lg w-100">Ver equipo amateur</a>
                </div>
            </article>
        </div>

        <div class="col-md-6">
            <article class="card h-100 border-0 shadow-sm card-hover">
                <div class="card-body p-5 text-center">
                    <h2 class="fw-bold mb-3">FC Clubs</h2>
                    <p class="text-muted mb-4">
                        Resultados y estadísticas sincronizadas desde EA FC.
                    </p>
                    <a href="<?= htmlspecialchars(App::url('/fc-clubs')) ?>"
                       class="btn btn-dark btn-lg w-100">Ver club virtual</a>
                </div>
            </article>
        </div>
    </div>

    <div class="text-center mt-5">
        <a href="<?= htmlspecialchars(App::url('/torneo')) ?>" class="btn btn-primary btn-lg">
            Ir al torneo
        </a>
    </div>
</main>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
