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
            <h2 class="titulo-banner mb-0">GANEN O MUERAN</h2>
        </div>
        <div class="linea mt-3"></div>
    </div>
</header>

<section class="estadisticas py-5 text-white">
    <div class="container text-center">
        <h3 class="fw-bold mb-4 py-3 titulo-stats">
            La élite del fútbol para personas retiradas o con autismo
        </h3>
        <p class="text-white-50 fs-5 mb-5">Líderes en:</p>
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="stat-card p-4">
                    <h2 id="stat-lesiones" class="display-3 fw-bold text-accent">0</h2>
                    <p class="mb-0 text-white-50">Lesiones acumuladas</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card p-4">
                    <h2 id="stat-inactivos" class="display-3 fw-bold text-accent">0</h2>
                    <p class="mb-0 text-white-50">Jugadores inactivos</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card p-4">
                    <h2 id="stat-expulsiones" class="display-3 fw-bold text-accent">0</h2>
                    <p class="mb-0 text-white-50">Expulsiones</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card p-4">
                    <h2 id="stat-derrotas" class="display-3 fw-bold text-accent">0</h2>
                    <p class="mb-0 text-white-50">Derrotas</p>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="container py-5 my-5">
    <div class="row text-center mb-5">
        <div class="col-12">
            <h2 class="fw-bold mb-3">Sigue la Liga Oficial</h2>
            <p class="text-muted">Últimos resultados oficiales de Chuntaro FC.</p>
        </div>
    </div>

    <div
        id="recentMatches"
        class="row g-4 mt-4"
        data-endpoint="<?= htmlspecialchars(App::url('/api/ea/matches?type=leagueMatch&limit=3')) ?>"
        data-club-id="<?= htmlspecialchars((string) App::env('EA_CLUB_ID', '2043111')) ?>"
    >
        <div class="col-12">
            <div class="alert alert-secondary text-center mb-0">
                Cargando últimos partidos desde EA Clubs…
            </div>
        </div>
    </div>

    <div class="row g-4 mt-5">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0 card-hover">
                <div class="card-body text-center p-5">
                    <h3 class="card-title mb-3">Torneo Express</h3>
                    <p class="card-text text-muted mb-4">
                        Escribe nombres, revuelve jugadores y genera llaves al instante.
                    </p>
                    <a href="<?= htmlspecialchars(App::url('/')) ?>" class="btn btn-primary w-100 btn-lg disabled">Próximamente</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0 bg-light card-hover">
                <div class="card-body text-center p-5">
                    <h3 class="card-title mb-3">Liga Semiprofesional</h3>
                    <p class="card-text text-muted mb-4">
                        Tabla general, estadísticas y próximos partidos oficiales.
                    </p>
                    <a href="<?= htmlspecialchars(App::url('/')) ?>" class="btn btn-dark w-100 btn-lg disabled">Próximamente</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
