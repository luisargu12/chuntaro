<?php

use App\Config\App;

/** @var array<string,mixed> $player */

$stats = $player['stats'];
$form = $player['form'];
$rankings = $player['rankings'];
$matches = $player['matches'];

$photo = $player['photoUrl'];
if (!$photo && $player['photoPath']) {
    $photo = App::asset(ltrim((string) $player['photoPath'], '/'));
}
if (!$photo) {
    $photo = App::asset('img/player-default.svg');
}

$positionLabels = [
    'goalkeeper' => 'Portero',
    'defender' => 'Defensa',
    'midfielder' => 'Medio',
    'forward' => 'Delantero',
    'any' => 'Polivalente',
];
$position = $positionLabels[strtolower((string) $player['favoritePosition'])]
    ?? $player['favoritePosition']
    ?? 'Jugador';

$monthLabels = [
    1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr',
    5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago',
    9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
];
$formatDate = static function (int $timestamp) use ($monthLabels): string {
    return gmdate('d', $timestamp)
        . ' ' . $monthLabels[(int) gmdate('n', $timestamp)]
        . ' ' . gmdate('Y', $timestamp);
};

$typeLabels = [
    'leagueMatch' => 'Liga',
    'playoffMatch' => 'Playoff',
    'friendlyMatch' => 'Amistoso',
];

$crestBase = rtrim((string) App::env(
    'EA_CREST_CDN_BASE',
    'https://eafc26.content.easports.com/fc/fltOnlineAssets/26E4D4D6-8DBB-4A9A-BD99-9C47D3AA341D/2026/fcweb/crests/256x256'
), '/');
$crestFallback = 'https://media.contentapi.ea.com/content/dam/eacom/fc/pro-clubs/notfound-crest.png';
$rivalCrest = static function (?string $assetId) use ($crestBase, $crestFallback): string {
    return $assetId && preg_match('/^\d+$/', $assetId)
        ? $crestBase . '/l' . $assetId . '.png'
        : $crestFallback;
};

require __DIR__ . '/../layouts/public_header.php';
?>

<section class="player-profile-hero">
    <div class="container">
        <a href="<?= htmlspecialchars(App::url('/fc-clubs/plantilla')) ?>"
           class="roster-back-link">← Volver a la plantilla</a>

        <div class="row align-items-end g-5">
            <div class="col-lg-5">
                <div class="player-profile-photo-wrap">
                    <img src="<?= htmlspecialchars((string) $photo) ?>"
                         alt="<?= htmlspecialchars((string) $player['gamertag']) ?>"
                         class="player-profile-photo">
                    <span><?= $player['active'] ? 'Plantilla activa' : 'Jugador inactivo' ?></span>
                </div>
            </div>
            <div class="col-lg-7">
                <p class="fc-eyebrow mb-3"><?= htmlspecialchars((string) $position) ?></p>
                <h1 class="player-profile-name mb-3">
                    <?= htmlspecialchars((string) $player['gamertag']) ?>
                </h1>
                <?php if ($player['proName']): ?>
                    <p class="player-profile-proname mb-4">
                        Pro: <?= htmlspecialchars((string) $player['proName']) ?>
                    </p>
                <?php endif; ?>
                <div class="player-profile-meta">
                    <div><span>Overall</span><strong><?= htmlspecialchars((string) ($player['overall'] ?? '—')) ?></strong></div>
                    <div><span>Altura</span><strong><?= $player['heightCm'] ? (int) $player['heightCm'] . ' cm' : '—' ?></strong></div>
                    <div><span>Rating</span><strong><?= htmlspecialchars((string) ($stats['rating'] ?? '—')) ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="player-profile-page">
    <section class="container">
        <div class="player-kpi-grid">
            <div><strong><?= (int) $stats['gamesPlayed'] ?></strong><span>Partidos</span></div>
            <div><strong><?= (int) $stats['goals'] ?></strong><span>Goles</span></div>
            <div><strong><?= (int) $stats['assists'] ?></strong><span>Asistencias</span></div>
            <div><strong><?= (int) $stats['manOfTheMatch'] ?></strong><span>MVP</span></div>
            <div><strong><?= htmlspecialchars((string) $stats['winRate']) ?>%</strong><span>Win rate</span></div>
        </div>

        <div class="row g-4 player-profile-main">
            <div class="col-lg-7">
                <article class="player-panel h-100">
                    <p class="fc-eyebrow mb-2">Rendimiento acumulado</p>
                    <h2>Precisión y producción</h2>

                    <div class="player-rate-list">
                        <?php foreach ([
                            ['Efectividad de pases', $stats['passSuccessRate']],
                            ['Efectividad de tackles', $stats['tackleSuccessRate']],
                            ['Efectividad de tiros', $stats['shotSuccessRate']],
                        ] as [$label, $value]): ?>
                            <div class="player-rate-row">
                                <div><span><?= htmlspecialchars($label) ?></span><strong><?= htmlspecialchars((string) $value) ?>%</strong></div>
                                <div class="player-rate-track">
                                    <i style="width: <?= max(0, min((float) $value, 100)) ?>%"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="player-secondary-stats">
                        <div><strong><?= htmlspecialchars((string) $stats['goalsPerGame']) ?></strong><span>Goles / partido</span></div>
                        <div><strong><?= htmlspecialchars((string) $stats['assistsPerGame']) ?></strong><span>Asist. / partido</span></div>
                        <div><strong><?= (int) $stats['passesCompleted'] ?></strong><span>Pases completos</span></div>
                        <div><strong><?= (int) $stats['tacklesCompleted'] ?></strong><span>Tackles completos</span></div>
                    </div>
                </article>
            </div>

            <div class="col-lg-5">
                <article class="player-panel player-form-panel h-100">
                    <p class="fc-eyebrow mb-2">Momento actual</p>
                    <h2>Forma reciente</h2>

                    <div class="player-form-rating">
                        <strong><?= htmlspecialchars((string) ($form['recentRating'] ?? '—')) ?></strong>
                        <span>Rating · Últimos 5</span>
                    </div>

                    <div class="player-form-bars">
                        <?php foreach ($form['lastFiveRatings'] as $index => $rating): ?>
                            <div>
                                <i style="height: <?= max(10, min(((float) $rating / 10) * 100, 100)) ?>%"></i>
                                <span><?= htmlspecialchars((string) $rating) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($form['bestMatch']): ?>
                        <div class="player-best-match">
                            <span>Mejor partido reciente</span>
                            <strong>
                                <?= htmlspecialchars((string) $form['bestMatch']['rating']) ?>
                                vs <?= htmlspecialchars((string) $form['bestMatch']['rivalName']) ?>
                            </strong>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </div>

        <section class="player-rankings-section">
            <div class="player-section-heading">
                <div>
                    <p class="fc-eyebrow mb-2">Dentro del equipo</p>
                    <h2>Posición en la plantilla</h2>
                </div>
            </div>
            <div class="player-ranking-grid">
                <div><span>Goleadores</span><strong>#<?= (int) $rankings['goals'] ?></strong></div>
                <div><span>Asistidores</span><strong>#<?= (int) $rankings['assists'] ?></strong></div>
                <div><span>Mejor rating</span><strong>#<?= (int) $rankings['rating'] ?></strong></div>
                <div><span>Tarjetas rojas</span><strong><?= (int) $stats['redCards'] ?></strong></div>
            </div>
        </section>

        <section class="player-matches-section">
            <div class="player-section-heading">
                <div>
                    <p class="fc-eyebrow mb-2">Partido a partido</p>
                    <h2>Actuaciones recientes</h2>
                </div>
                <span><?= count($matches) ?> registros</span>
            </div>

            <?php if ($matches === []): ?>
                <div class="roster-empty">Este jugador todavía no tiene partidos detallados.</div>
            <?php else: ?>
                <div class="player-match-list">
                    <?php foreach (array_slice($matches, 0, 20) as $match): ?>
                        <article class="player-match-row">
                            <div class="player-match-rival">
                                <img src="<?= htmlspecialchars($rivalCrest($match['rivalCrestAssetId'])) ?>"
                                     alt=""
                                     onerror="this.src='<?= htmlspecialchars($crestFallback) ?>'">
                                <div>
                                    <strong><?= htmlspecialchars((string) $match['rivalName']) ?></strong>
                                    <span>
                                        <?= htmlspecialchars($formatDate((int) $match['timestamp'])) ?>
                                        · <?= htmlspecialchars($typeLabels[$match['type']] ?? $match['type']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="player-match-score player-match-score-<?= htmlspecialchars((string) $match['result']) ?>">
                                <?= (int) $match['goalsFor'] ?> : <?= (int) $match['goalsAgainst'] ?>
                            </div>
                            <div class="player-match-numbers">
                                <div><strong><?= htmlspecialchars((string) ($match['rating'] ?? '—')) ?></strong><span>Rating</span></div>
                                <div><strong><?= (int) $match['goals'] ?></strong><span>Goles</span></div>
                                <div><strong><?= (int) $match['assists'] ?></strong><span>Asist.</span></div>
                                <div><strong><?= (int) $match['shots'] ?></strong><span>Tiros</span></div>
                                <div><strong><?= (int) $match['passesCompleted'] ?>/<?= (int) $match['passAttempts'] ?></strong><span>Pases</span></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
