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

<main class="home-landing">
    <section class="home-manifesto">
        <div class="container">
            <div class="row align-items-end g-5">
                <div class="col-lg-7">
                    <p class="fc-eyebrow mb-3">Un equipo · Dos versiones</p>
                    <h2 class="home-display mb-0">Dos canchas.<br>Un solo escudo.</h2>
                </div>
                <div class="col-lg-5">
                    <p class="home-manifesto-copy">
                        Chuntaro vive cada semana entre el futbol amateur y la competencia
                        virtual. Aquí reunimos resultados, jugadores y todas las historias
                        que ocurren dentro y fuera de la cancha.
                    </p>
                    <div class="home-quick-links">
                        <a href="<?= htmlspecialchars(App::url('/martes-botanero')) ?>">Equipo amateur ↗</a>
                        <a href="<?= htmlspecialchars(App::url('/fc-clubs')) ?>">Club virtual ↗</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-kalcho-section">
        <div class="container">
            <div class="home-section-heading">
                <div>
                    <p class="fc-eyebrow mb-2">La cancha de los martes</p>
                    <h2 class="home-section-title mb-0">
                        Nuestros últimos partidos en<br>Kalcho Martes Botanero
                    </h2>
                </div>
                <span class="home-section-index" aria-hidden="true">01</span>
            </div>

            <article class="kalcho-coming-soon">
                <div class="kalcho-day">
                    <span>Cada</span>
                    <strong>MAR</strong>
                    <small>Noche</small>
                </div>
                <div class="kalcho-coming-copy">
                    <span class="kalcho-status"><i></i> Preparando temporada</span>
                    <h3>Los resultados del equipo amateur vivirán aquí.</h3>
                    <p>
                        Partidos, rivales, marcadores y estadísticas de nuestras noches en Kalcho.
                    </p>
                </div>
                <div class="kalcho-soon-label">Próximamente</div>
            </article>
        </div>
    </section>

    <section class="home-fc-section">
        <div class="container">
            <div class="home-section-heading">
                <div>
                    <p class="fc-eyebrow mb-2">La cancha virtual</p>
                    <h2 class="home-section-title mb-0">Síguenos en FC Clubs</h2>
                </div>
                <a href="<?= htmlspecialchars(App::url('/fc-clubs/partidos')) ?>"
                   class="home-section-link">Ver todos los partidos ↗</a>
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
                    <div class="home-match-loading">Cargando últimos resultados…</div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-explore-section">
        <div class="container">
            <div class="home-section-heading">
                <div>
                    <p class="fc-eyebrow mb-2">Dentro del club</p>
                    <h2 class="home-section-title mb-0">Explora Chuntaro</h2>
                </div>
                <span class="home-section-index home-section-index-dark" aria-hidden="true">02</span>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(App::url('/fc-clubs/plantilla')) ?>"
                       class="home-explore-card home-explore-card-primary">
                        <span>FC Clubs</span>
                        <h3>Conoce a la plantilla virtual</h3>
                        <p>Goles, asistencias, rating y rendimiento de cada jugador.</p>
                        <i aria-hidden="true">↗</i>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(App::url('/fc-clubs/partidos')) ?>"
                       class="home-explore-card home-explore-card-light">
                        <span>Historial</span>
                        <h3>Revive todos los partidos</h3>
                        <p>Marcadores y estadísticas generales e individuales.</p>
                        <i aria-hidden="true">↗</i>
                    </a>
                </div>
            </div>

            <a href="<?= htmlspecialchars(App::url('/torneo')) ?>" class="home-tournament-cta">
                <div>
                    <span>Herramienta de la comunidad</span>
                    <h3>Organiza el próximo torneo</h3>
                </div>
                <strong>Entrar ↗</strong>
            </a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
