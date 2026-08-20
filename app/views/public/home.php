<?php

use App\Config\App;

require __DIR__ . '/../layouts/public_header.php';
?>

<header class="hero-section d-flex align-items-end justify-content-start text-start pb-5"
        style="--hero-image: url('<?= htmlspecialchars(App::asset('img/hero-clubs.jpg')) ?>')">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="container position-relative z-2">
        <div style="overflow: hidden">
            <h2 class="titulo-banner mb-0">GANEN O MUERAN</h2>
        </div>
        <div class="linea mt-3"></div>
    </div>
</header>

<main class="fc-clubs-page">
    <section class="fc-intro">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <p class="fc-eyebrow mb-3">EA FC 26 · Pro Clubs</p>
                    <h2 class="fc-display mb-4">Los alergicos a la primera división.</h2>
                    <p class="fc-lead mb-4">
                        Sigue cada resultado, conoce a la plantilla y revisa el rendimiento
                        de Chuntaro FC en su camino por la liga EA FC 26.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= htmlspecialchars(App::url('/fc-clubs/plantilla')) ?>"
                           class="btn fc-btn-primary">Conoce la plantilla</a>
                        <a href="#ultimos-partidos" class="btn fc-btn-ghost">Últimos resultados</a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <article class="fc-identity-card">
                        <span class="fc-live-badge"><i></i></span>
                        <img src="<?= htmlspecialchars(App::asset('img/escudo.png')) ?>"
                             alt="Escudo de Chuntaro FC"
                             class="fc-identity-crest">
                        <div>
                            <p class="fc-card-kicker mb-1">Club virtual</p>
                            <h3 class="mb-1">Chuntaro FC</h3>
                            <p class="mb-0">Club ID <?= htmlspecialchars((string) App::env('EA_CLUB_ID', '2043111')) ?></p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="ultimos-partidos" class="fc-matches-section">
        <div class="container">
            <div class="fc-section-heading">
                <div>
                    <p class="fc-eyebrow mb-2">Jornada reciente</p>
                    <h2 class="fc-display fc-display-light mb-0">Últimos partidos</h2>
                </div>
            </div>

            <div
                id="recentMatches"
                class="row g-4"
                data-endpoint="<?= htmlspecialchars(App::url('/fc-clubs/api/ea/matches?type=leagueMatch&limit=3')) ?>"
                data-club-id="<?= htmlspecialchars((string) App::env('EA_CLUB_ID', '2043111')) ?>"
                data-own-crest="<?= htmlspecialchars(App::asset('img/escudo.png')) ?>"
                data-crest-base="<?= htmlspecialchars((string) App::env(
                    'EA_CREST_CDN_BASE',
                    'https://eafc26.content.easports.com/fc/fltOnlineAssets/26E4D4D6-8DBB-4A9A-BD99-9C47D3AA341D/2026/fcweb/crests/256x256'
                )) ?>"
                data-crest-fallback="https://media.contentapi.ea.com/content/dam/eacom/fc/pro-clubs/notfound-crest.png"
            >
                <div class="col-12">
                    <div class="fc-loading">Cargando resultados desde EA Clubs…</div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-4">
                    <a href="<?= htmlspecialchars(App::url('/fc-clubs/partidos')) ?>"
                       class="btn fc-btn-secondary">Ver todos los partidos</a>
                </div>
        </div>
    </section>

    <section class="fc-explore-section">
        <div class="container">
            <div class="fc-section-heading fc-section-heading-dark">
                <div>
                    <p class="fc-eyebrow mb-2">Explora el club</p>
                    <h2 class="fc-display mb-0">Más que un marcador</h2>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <a href="<?= htmlspecialchars(App::url('/fc-clubs/plantilla')) ?>"
                       class="fc-feature-card fc-feature-card-primary">
                        <span class="fc-feature-index">01</span>
                        <div>
                            <p class="fc-card-kicker mb-2">Jugadores</p>
                            <h3>La plantilla completa</h3>
                            <p>Posiciones, goles, asistencias, rating y rendimiento acumulado.</p>
                        </div>
                        <span class="fc-feature-arrow" aria-hidden="true">↗</span>
                    </a>
                </div>
                <div class="col-lg-5">
                    <a href="<?= htmlspecialchars(App::url('/fc-clubs/partidos')) ?>"
                       class="fc-feature-card fc-feature-card-muted">
                        <span class="fc-feature-index">02</span>
                        <div>
                            <p class="fc-card-kicker mb-2">Partidos</p>
                            <h3>Historial completo</h3>
                            <p>Resultados por mes, comparativas y rendimiento individual.</p>
                        </div>
                        <span class="fc-feature-arrow" aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
