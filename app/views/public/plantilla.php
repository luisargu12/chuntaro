<?php

use App\Config\App;

/** @var string $clubId */
/** @var string $platform */
/** @var string $clubName */

$navSolid = true;
$pageTitle = 'Plantilla';

require __DIR__ . '/../layouts/public_header.php';
?>

<section class="roster-hero">
    <div class="container position-relative">
        <div class="row align-items-end g-4">
            <div class="col-lg-8 position-relative z-2">
                <a href="<?= htmlspecialchars(App::url('/fc-clubs')) ?>"
                   class="roster-back-link">← FC Clubs</a>
                <p class="fc-eyebrow mb-3">El vestidor virtual</p>
                <h1 class="roster-title mb-3">Plantilla</h1>
                <p id="clubNameLabel" class="roster-subtitle mb-0">
                    <?= htmlspecialchars($clubName) ?> · EA FC 26
                </p>
            </div>
            <div class="col-lg-4 d-none d-lg-flex justify-content-end">

            </div>
        </div>
    </div>
</section>

<main class="roster-page">
    <div class="container">
        <section class="roster-toolbar">
            <div>
                <p class="fc-eyebrow mb-2">Primer equipo</p>
                <h2 class="roster-section-title mb-2">Conoce a los jugadores</h2>
                <p class="text-muted mb-0">Filtra por posición y compara su rendimiento.</p>
            </div>
            <div id="positionCounts" class="roster-filters d-none"></div>
        </section>

        <div id="plantillaStatus" class="roster-status">Cargando jugadores…</div>
        <div id="playersGrid" class="row g-4"></div>
    </div>
</main>

<script>
(function () {
  const DATA_URL = <?= json_encode(App::url('/fc-clubs/api/ea/members')) ?>;

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
    statusEl.className = `roster-status roster-status-${type}`;
    statusEl.innerHTML = html;
    statusEl.classList.remove('d-none');
  };

  const initials = (name) => String(name || 'J')
    .split(/[\s_-]+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase();

  const renderPlayers = (data) => {
    const members = Array.isArray(data.members) ? [...data.members] : [];
    const positionCount = data.positionCount || {};

    members.sort((a, b) => {
      const ra = parseFloat(a.ratingAve || 0);
      const rb = parseFloat(b.ratingAve || 0);
      if (rb !== ra) return rb - ra;
      return parseInt(b.goals || 0, 10) - parseInt(a.goals || 0, 10);
    });

    const countMap = [
      ['all', 'Todos', members.length],
      ['forward', 'Delanteros'],
      ['midfielder', 'Medios'],
      ['defender', 'Defensas'],
      ['goalkeeper', 'Porteros'],
    ];

    countsEl.innerHTML = countMap.map(([key, label, total]) => `
      <button type="button"
              class="roster-filter${key === 'all' ? ' active' : ''}"
              data-position="${key}">
        <span>${escapeHtml(label)}</span>
        <strong>${parseInt(total ?? positionCount[key] ?? 0, 10)}</strong>
      </button>
    `).join('');
    countsEl.classList.remove('d-none');

    const renderGrid = (position = 'all') => {
      const filtered = position === 'all'
        ? members
        : members.filter((member) => String(member.favoritePosition || '').toLowerCase() === position);

      gridEl.innerHTML = filtered.map((j, index) => {
        const nombre = j.name || j.proName || 'Jugador';
        const proName = j.proName && j.proName !== nombre ? j.proName : '';
        const pos = j.favoritePosition || j.proPos || '';
        const rating = j.ratingAve ?? '—';
        const goles = j.goals ?? '0';
        const asist = j.assists ?? '0';
        const pj = j.gamesPlayed ?? '0';
        const mom = j.manOfTheMatch ?? '0';
        const winRate = Number.parseFloat(j.winRate || 0);
        const overall = j.proOverall ?? '—';

        return `
          <div class="col-md-6 col-xl-4">
            <article class="roster-player-card">
              <div class="roster-player-top">
                <span class="roster-card-number">${String(index + 1).padStart(2, '0')}</span>
                <span class="roster-position">${escapeHtml(posLabel(pos))}</span>
              </div>

              <div class="roster-player-identity">
                <div class="roster-avatar">${escapeHtml(initials(nombre))}</div>
                <div class="flex-grow-1 min-w-0">
                  <h3>${escapeHtml(nombre)}</h3>
                  <p>${proName ? escapeHtml(proName) : `Overall ${escapeHtml(overall)}`}</p>
                </div>
                <div class="roster-rating">
                  <strong>${escapeHtml(rating)}</strong>
                  <span>Rating</span>
                </div>
              </div>

              <div class="roster-stat-grid">
                <div><strong>${escapeHtml(pj)}</strong><span>Partidos</span></div>
                <div><strong>${escapeHtml(goles)}</strong><span>Goles</span></div>
                <div><strong>${escapeHtml(asist)}</strong><span>Asistencias</span></div>
                <div><strong>${escapeHtml(mom)}</strong><span>MOTM</span></div>
              </div>

              <div class="roster-win-rate">
                <div><span>Win rate</span><strong>${escapeHtml(winRate)}%</strong></div>
                <div class="roster-progress"><i style="width: ${Math.max(0, Math.min(winRate, 100))}%"></i></div>
              </div>
            </article>
          </div>`;
      }).join('');

      if (filtered.length === 0) {
        gridEl.innerHTML = '<div class="col-12"><div class="roster-empty">No hay jugadores en esta posición.</div></div>';
      }
    };

    countsEl.querySelectorAll('.roster-filter').forEach((button) => {
      button.addEventListener('click', () => {
        countsEl.querySelectorAll('.roster-filter').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        renderGrid(button.dataset.position || 'all');
      });
    });

    renderGrid();

    if (members.length === 0) {
      setStatus('info', 'No hay jugadores activos guardados.');
      return;
    }

    let note = `Se cargaron <strong>${members.length}</strong> jugadores.`;
    setStatus('success', note);
  };

  (async () => {
    try {
      const res = await fetch(DATA_URL, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (e) {
        throw new Error(`El endpoint no devolvió JSON (HTTP ${res.status}). URL: ${DATA_URL}`);
      }

      if (!res.ok || !json.exito) {
        throw new Error(json.mensaje || `HTTP ${res.status}`);
      }

      renderPlayers(json.data);
    } catch (err) {
      console.error(err);
      setStatus(
        'danger',
        `<strong>No se pudo cargar la plantilla.</strong><br>${escapeHtml(err.message || err)}
         <hr>
         <small>Prueba abrir en el navegador: <code>${escapeHtml(DATA_URL)}</code></small>`
      );
    }
  })();
})();
</script>

<?php require __DIR__ . '/../layouts/public_footer.php'; ?>
