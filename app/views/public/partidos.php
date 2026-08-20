<?php

use App\Config\App;

/** @var string $clubId */
/** @var string $clubName */

require __DIR__ . '/../layouts/public_header.php';
?>

<section class="roster-hero matches-hero">
    <div class="container position-relative">
        <div class="row align-items-end g-4">
            <div class="col-lg-8 position-relative z-2">
                <a href="<?= htmlspecialchars(App::url('/fc-clubs')) ?>"
                   class="roster-back-link">← FC Clubs</a>
                <p class="fc-eyebrow mb-3">El camino en la liga</p>
                <h1 class="roster-title mb-3">Partidos</h1>
                <p class="roster-subtitle mb-0">
                    <?= htmlspecialchars($clubName) ?> · Historial oficial
                </p>
            </div>
        </div>
    </div>
</section>

<main class="match-history-page">
    <div class="container">
        <div class="match-history-heading">
            <div>
                <p class="fc-eyebrow mb-2">Archivo de temporada</p>
                <h2 class="roster-section-title mb-2">Todos los resultados</h2>
                <p class="text-muted mb-0">
                    Abre un mes y consulta el detalle estadístico de cada encuentro.
                </p>
            </div>
            <div id="matchHistoryTotal" class="match-history-total">—</div>
        </div>

        <div
            id="matchHistory"
            class="accordion match-history-accordion"
            data-endpoint="<?= htmlspecialchars(App::url('/fc-clubs/api/partidos')) ?>"
            data-detail-endpoint="<?= htmlspecialchars(App::url('/fc-clubs/api/partidos/detalle')) ?>"
            data-club-id="<?= htmlspecialchars($clubId) ?>"
            data-own-crest="<?= htmlspecialchars(App::asset('img/escudo.png')) ?>"
            data-crest-base="<?= htmlspecialchars(rtrim((string) App::env(
                'EA_CREST_CDN_BASE',
                'https://eafc26.content.easports.com/fc/fltOnlineAssets/26E4D4D6-8DBB-4A9A-BD99-9C47D3AA341D/2026/fcweb/crests/256x256'
            ), '/')) ?>"
            data-crest-fallback="https://media.contentapi.ea.com/content/dam/eacom/fc/pro-clubs/notfound-crest.png"
        >
            <div class="match-history-loading">Cargando historial desde la base de datos…</div>
        </div>
    </div>
</main>

<div class="modal fade match-detail-modal" id="matchDetailModal" tabindex="-1"
     aria-labelledby="matchDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="fc-eyebrow mb-1">Reporte del encuentro</p>
                    <h2 class="modal-title" id="matchDetailTitle">Estadísticas</h2>
                </div>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="matchDetailBody">
                <div class="match-detail-loading">Cargando estadísticas…</div>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(App::asset('js/partidos.js')) ?>"></script>
<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
