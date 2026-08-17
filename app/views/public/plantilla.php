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
        <p class="text-white-50 mb-1 text-uppercase small fw-semibold">Club ID <?= htmlspecialchars($clubId) ?></p>
        <h1 class="titulo-banner mb-2" style="font-size: clamp(2rem, 6vw, 3.5rem);">Plantilla</h1>
        <p id="clubNameLabel" class="mb-0 fs-5"><?= htmlspecialchars($clubName) ?></p>
    </div>
</section>

<main class="container py-5">
    <div id="plantillaStatus" class="alert alert-secondary">Cargando jugadores desde EA Clubs…</div>
    <div id="positionCounts" class="row g-3 mb-4 d-none"></div>
    <div id="playersGrid" class="row g-4"></div>
</main>

<script>
(function () {
  const CLUB_ID = <?= json_encode($clubId) ?>;
  const PLATFORM = <?= json_encode($platform) ?>;
  const BASE = <?= json_encode(App::basePath()) ?>;
  const EA_URL = `https://proclubs.ea.com/api/fc/members/stats?platform=${encodeURIComponent(PLATFORM)}&clubId=${encodeURIComponent(CLUB_ID)}`;
  const PROXY_URL = `${BASE}/api/ea/members`;

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
                  <h5 class="fw-bold mb-1">${escapeHtml(String(nombre))}</h5>
                  <span class="badge rounded-pill" style="background: var(--color-primary);">${escapeHtml(posLabel(pos))}</span>
                </div>
                <div class="text-end">
                  <div class="fs-3 fw-bold text-accent lh-1">${escapeHtml(String(rating))}</div>
                  <small class="text-muted">Rating</small>
                </div>
              </div>
              <div class="row text-center g-2">
                <div class="col-3"><div class="fw-bold">${escapeHtml(String(pj))}</div><small class="text-muted">PJ</small></div>
                <div class="col-3"><div class="fw-bold">${escapeHtml(String(goles))}</div><small class="text-muted">Goles</small></div>
                <div class="col-3"><div class="fw-bold">${escapeHtml(String(asist))}</div><small class="text-muted">Asist.</small></div>
                <div class="col-3"><div class="fw-bold">${escapeHtml(String(mom))}</div><small class="text-muted">MOTM</small></div>
              </div>
              ${winRate != null && winRate !== '' ? `<div class="mt-3 small text-muted">Win rate: ${escapeHtml(String(winRate))}%</div>` : ''}
            </div>
          </div>
        </div>`;
    }).join('');

    if (members.length === 0) {
      setStatus('info', 'La API respondió sin miembros.');
      return;
    }

    let note = `Se cargaron <strong>${members.length}</strong> jugadores.`;
    if (meta.source === 'ea') note += ' Fuente: EA (navegador).';
    if (meta.source === 'proxy') note += ' Fuente: proxy PHP.';
    if (meta.cached) note += ' (caché)';
    setStatus('success', note);
  };

  const escapeHtml = (str) => str
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  async function fetchEaDirect() {
    const res = await fetch(EA_URL, {
      method: 'GET',
      mode: 'cors',
      credentials: 'omit',
      headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) throw new Error(`EA HTTP ${res.status}`);
    return res.json();
  }

  async function fetchProxy() {
    const res = await fetch(PROXY_URL, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });
    const json = await res.json();
    if (!json.exito) throw new Error(json.mensaje || 'Proxy falló');
    return { data: json.data, meta: { source: 'proxy', cached: !!json.cached } };
  }

  (async () => {
    try {
      const data = await fetchEaDirect();
      renderPlayers(data, { source: 'ea' });
    } catch (errDirect) {
      console.warn('EA directo falló (CORS/red):', errDirect);
      setStatus('warning', 'No se pudo leer EA directo desde el navegador (CORS). Probando proxy PHP…');
      try {
        const proxied = await fetchProxy();
        renderPlayers(proxied.data, proxied.meta);
      } catch (errProxy) {
        console.error(errProxy);
        setStatus(
          'danger',
          `<strong>No se pudo cargar la plantilla.</strong><br>
           Directo: ${escapeHtml(String(errDirect.message || errDirect))}<br>
           Proxy: ${escapeHtml(String(errProxy.message || errProxy))}<br>
           <small class="text-muted">Abre la API en otra pestaña para confirmar JSON, luego recarga.</small>`
        );
      }
    }
  })();
})();
</script>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
