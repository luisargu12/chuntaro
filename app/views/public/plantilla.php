<?php

use App\Config\App;

/** @var string $clubId */
/** @var string $platform */
/** @var string $clubName */

$navSolid = true;
$pageTitle = 'Plantilla';

require __DIR__ . '/../layouts/public_header.php';
?>

<section class="page-hero text-white d-flex align-items-end">
    <div class="container position-relative z-2 pb-4">

        <h1 class="titulo-banner mb-2" style="font-size: clamp(2rem, 6vw, 3.5rem); color: var(--color-primary)">Plantilla</h1>
        <p id="clubNameLabel" class="mb-0 fs-5" style="color: var(--color-primary)"><?= htmlspecialchars($clubName) ?></p>
    </div>
</section>

<main class="container py-5">
    <div id="plantillaStatus" class="alert alert-secondary">Cargando jugadores…</div>
    <div id="positionCounts" class="row g-3 mb-4 d-none"></div>
    <div id="playersGrid" class="row g-4"></div>
</main>

<script>
(function () {
  // Solo same-origin: evita CORS. El servidor PHP consulta EA.
  const PROXY_URL = <?= json_encode(App::url('/fc-clubs/api/ea/members')) ?>;

  const statusEl = document.getElementById('plantillaStatus');
  const gridEl = document.getElementById('playersGrid');
  const countsEl = document.getElementById('positionCounts');

  const posLabel = (pos) => {
    const map = {
      goalkeeper: 'Portero',
      defender: 'Defensa',
      midfielder: 'Medio',
      forward: 'Delantero',
      any: 'Polivalente',
    };
    const key = String(pos || '').toLowerCase();
    return map[key] || pos || '—';
  };

  const escapeHtml = (str) => String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const setStatus = (type, html) => {
    statusEl.className = `alert alert-${type}`;
    statusEl.innerHTML = html;
    statusEl.classList.remove('d-none');
  };

  const renderPlayers = (data, meta = {}) => {
    const members = Array.isArray(data.members) ? [...data.members] : [];
    const positionCount = data.positionCount || {};

    members.sort((a, b) => {
      const ra = parseFloat(a.ratingAve || 0);
      const rb = parseFloat(b.ratingAve || 0);
      if (rb !== ra) return rb - ra;
      return parseInt(b.goals || 0, 10) - parseInt(a.goals || 0, 10);
    });

    const countMap = [
      ['forward', 'Delanteros'],
      ['midfielder', 'Medios'],
      ['defender', 'Defensas'],
      ['goalkeeper', 'Porteros'],
    ];

    countsEl.innerHTML = countMap.map(([key, label]) => `
      <div class="col-6 col-md-3">
        <div class="stat-card text-white text-center p-3" style="background: var(--color-primary);">
          <div class="display-6 fw-bold text-accent">${parseInt(positionCount[key] || 0, 10)}</div>
          <div class="small text-white-50">${label}</div>
        </div>
      </div>
    `).join('');
    countsEl.classList.remove('d-none');

    gridEl.innerHTML = members.map((j) => {
      const nombre = j.name || j.proName || 'Jugador';
      const pos = j.favoritePosition || j.proPos || '';
      const rating = j.ratingAve ?? '—';
      const goles = j.goals ?? '0';
      const asist = j.assists ?? '0';
      const pj = j.gamesPlayed ?? '0';
      const mom = j.manOfTheMatch ?? '0';
      const winRate = j.winRate;

      return `
        <div class="col-md-6 col-lg-4">
          <div class="card player-card h-100 border-0 shadow-sm">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="fw-bold mb-1">${escapeHtml(nombre)}</h5>
                  <span class="badge rounded-pill" style="background: var(--color-primary);">${escapeHtml(posLabel(pos))}</span>
                </div>
                <div class="text-end">
                  <div class="fs-3 fw-bold text-accent lh-1">${escapeHtml(rating)}</div>
                  <small class="text-muted">Rating</small>
                </div>
              </div>
              <div class="row text-center g-2">
                <div class="col-3"><div class="fw-bold">${escapeHtml(pj)}</div><small class="text-muted">PJ</small></div>
                <div class="col-3"><div class="fw-bold">${escapeHtml(goles)}</div><small class="text-muted">Goles</small></div>
                <div class="col-3"><div class="fw-bold">${escapeHtml(asist)}</div><small class="text-muted">Asist.</small></div>
                <div class="col-3"><div class="fw-bold">${escapeHtml(mom)}</div><small class="text-muted">MOTM</small></div>
              </div>
              ${winRate != null && winRate !== '' ? `<div class="mt-3 small text-muted">Win rate: ${escapeHtml(winRate)}%</div>` : ''}
            </div>
          </div>
        </div>`;
    }).join('');

    if (members.length === 0) {
      setStatus('info', 'La API respondió sin miembros.');
      return;
    }

    let note = `Se cargaron <strong>${members.length}</strong> jugadores.`;
    if (meta.cached) note += ' (caché servidor)';
    setStatus('success', note);
  };

  (async () => {
    try {
      const res = await fetch(PROXY_URL, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (e) {
        throw new Error(`El proxy no devolvió JSON (HTTP ${res.status}). ¿BASE_PATH / document root correctos? URL: ${PROXY_URL}`);
      }

      if (!res.ok || !json.exito) {
        throw new Error(json.mensaje || `HTTP ${res.status}`);
      }

      renderPlayers(json.data, { cached: !!json.cached });
    } catch (err) {
      console.error(err);
      setStatus(
        'danger',
        `<strong>No se pudo cargar la plantilla.</strong><br>${escapeHtml(err.message || err)}
         <hr>
         <small>Prueba abrir en el navegador: <code>${escapeHtml(PROXY_URL)}</code></small>`
      );
    }
  })();
})();
</script>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
