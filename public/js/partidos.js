document.addEventListener("DOMContentLoaded", () => {
  const historyEl = document.getElementById("matchHistory");
  const totalEl = document.getElementById("matchHistoryTotal");
  const modalEl = document.getElementById("matchDetailModal");
  const modalBody = document.getElementById("matchDetailBody");
  const modalTitle = document.getElementById("matchDetailTitle");

  if (!historyEl || !modalEl || typeof bootstrap === "undefined") return;

  const endpoint = historyEl.dataset.endpoint;
  const detailEndpoint = historyEl.dataset.detailEndpoint;
  const clubId = String(historyEl.dataset.clubId || "");
  const ownCrest = historyEl.dataset.ownCrest || "";
  const crestBase = String(historyEl.dataset.crestBase || "").replace(/\/+$/, "");
  const crestFallback = historyEl.dataset.crestFallback || "";
  const detailModal = new bootstrap.Modal(modalEl);

  const escapeHtml = (value) =>
    String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const crestUrl = (club, isOwn = false) => {
    if (isOwn && ownCrest) return ownCrest;
    const crestId =
      club?.crestAssetId ??
      club?.details?.customKit?.crestAssetId ??
      club?.TEAM ??
      "";
    return /^\d+$/.test(String(crestId)) && crestBase
      ? `${crestBase}/l${crestId}.png`
      : crestFallback;
  };

  const scoreValue = (club) =>
    Number.parseInt(club?.goals ?? club?.score ?? 0, 10) || 0;

  const typeLabel = (type) => ({
    leagueMatch: "Liga",
    playoffMatch: "Playoff",
    friendlyMatch: "Amistoso",
  })[type] || type || "Partido";

  const fullDate = (timestamp) => {
    const milliseconds = Number(timestamp) * 1000;
    return new Intl.DateTimeFormat("es-MX", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    }).format(new Date(milliseconds));
  };

  const monthData = (timestamp) => {
    const date = new Date(Number(timestamp) * 1000);
    const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
    const label = new Intl.DateTimeFormat("es-MX", {
      month: "long",
      year: "numeric",
    }).format(date);
    return {
      key,
      label: label.charAt(0).toUpperCase() + label.slice(1),
    };
  };

  const matchCard = (match) => {
    const clubs = match?.clubs && typeof match.clubs === "object" ? match.clubs : {};
    const ownClub = clubs[clubId] || {};
    const rivalEntry = Object.entries(clubs).find(([id]) => String(id) !== clubId);
    const rivalClub = rivalEntry?.[1] || {};
    const ownScore = scoreValue(ownClub);
    const rivalScore = scoreValue(rivalClub);
    const rivalName = rivalClub?.details?.name || rivalClub?.name || "Equipo rival";

    let resultLabel = "Empate";
    let resultTone = "draw";
    if (ownScore > rivalScore) {
      resultLabel = "Victoria";
      resultTone = "win";
    } else if (ownScore < rivalScore) {
      resultLabel = "Derrota";
      resultTone = "loss";
    }

    return `
      <div class="col-lg-4 col-md-6">
        <article class="fc-match-card history-match-card fc-match-card--${resultTone}">
          <div class="fc-match-meta">
            <p class="mb-0">${escapeHtml(fullDate(match.timestamp))}</p>
            <span>${escapeHtml(typeLabel(match.type))} · ${resultLabel}</span>
          </div>
          <div class="fc-match-body">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div class="match-team flex-fill">
                <img class="match-crest mb-2"
                     src="${escapeHtml(crestUrl(ownClub, true))}"
                     data-fallback="${escapeHtml(crestFallback)}"
                     alt="Escudo Chuntaro FC">
                <h5 class="fw-bold mb-0">Chuntaro FC</h5>
              </div>
              <div class="history-score-center">
                <span class="fc-match-score">
                  ${ownScore}<span>:</span>${rivalScore}
                </span>
                <button type="button"
                        class="match-stats-button"
                        data-match-id="${escapeHtml(match.matchId)}">
                  Estadísticas
                </button>
              </div>
              <div class="match-team flex-fill">
                <img class="match-crest mb-2"
                     src="${escapeHtml(crestUrl(rivalClub))}"
                     data-fallback="${escapeHtml(crestFallback)}"
                     alt="Escudo ${escapeHtml(rivalName)}">
                <h5 class="fw-bold mb-0">${escapeHtml(rivalName)}</h5>
              </div>
            </div>
          </div>
        </article>
      </div>`;
  };

  const attachImageFallbacks = (container) => {
    container.querySelectorAll(".match-crest").forEach((image) => {
      image.addEventListener("error", () => {
        const fallback = image.dataset.fallback;
        if (fallback && image.src !== fallback) image.src = fallback;
      });
    });
  };

  const renderHistory = (matches) => {
    if (!Array.isArray(matches) || matches.length === 0) {
      historyEl.innerHTML = '<div class="roster-empty">Todavía no hay partidos guardados.</div>';
      totalEl.textContent = "0";
      return;
    }

    totalEl.innerHTML = `<strong>${matches.length}</strong><span>Partidos</span>`;
    const groups = new Map();
    matches.forEach((match) => {
      const month = monthData(match.timestamp);
      if (!groups.has(month.key)) groups.set(month.key, { ...month, matches: [] });
      groups.get(month.key).matches.push(match);
    });

    historyEl.innerHTML = [...groups.values()].map((group, index) => {
      const collapseId = `month-${group.key}`;
      const open = index === 0;
      return `
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button${open ? "" : " collapsed"}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#${collapseId}"
                    aria-expanded="${open ? "true" : "false"}">
              <span>${escapeHtml(group.label)}</span>
              <strong>${group.matches.length} partidos</strong>
            </button>
          </h2>
          <div id="${collapseId}"
               class="accordion-collapse collapse${open ? " show" : ""}"
               data-bs-parent="#matchHistory">
            <div class="accordion-body">
              <div class="row g-4">
                ${group.matches.map(matchCard).join("")}
              </div>
            </div>
          </div>
        </div>`;
    }).join("");

    attachImageFallbacks(historyEl);
  };

  const comparisonValue = (team, key, attemptedKey = null) => {
    if (!team) return "—";
    if (attemptedKey) return `${team[key] ?? 0} / ${team[attemptedKey] ?? 0}`;
    return team[key] ?? "—";
  };

  const renderDetail = (detail) => {
    const match = detail.match || {};
    const teams = Array.isArray(detail.teams) ? detail.teams : [];
    const players = Array.isArray(detail.players) ? detail.players : [];
    const ownTeam = teams.find((team) => team.isPrincipal) || null;
    const rivalTeam = teams.find((team) => !team.isPrincipal) || null;
    const rivalName = rivalTeam?.name || match.rivalName || "Equipo rival";
    modalTitle.textContent = "Estadísticas del partido";

    const rows = [
      ["Tiros", "shots"],
      ["Pases completados", "passesCompleted", "passAttempts"],
      ["Tackles completados", "tacklesCompleted", "tackleAttempts"],
      ["Atajadas", "saves"],
      ["Tarjetas rojas", "redCards"],
      ["Rating promedio", "rating"],
    ];

    const comparison = teams.length > 0
      ? `
        <section class="match-comparison">
          <div class="match-detail-scoreboard">
            <div>
              <img src="${escapeHtml(crestUrl(ownTeam, true))}" alt="">
              <strong>Chuntaro FC</strong>
            </div>
            <span>${match.goalsFor ?? 0}<i>:</i>${match.goalsAgainst ?? 0}</span>
            <div>
              <img src="${escapeHtml(crestUrl(rivalTeam))}" alt="">
              <strong>${escapeHtml(rivalName)}</strong>
            </div>
          </div>
          <div class="match-comparison-grid">
            <div class="match-comparison-rows">
              ${rows.map(([label, key, attemptedKey]) => `
                <div>
                  <strong>${escapeHtml(comparisonValue(ownTeam, key, attemptedKey))}</strong>
                  <span>${escapeHtml(label)}</span>
                  <strong>${escapeHtml(comparisonValue(rivalTeam, key, attemptedKey))}</strong>
                </div>
              `).join("")}
            </div>
          </div>
        </section>`
      : '<div class="match-detail-empty">Este partido aún no tiene estadísticas generales sincronizadas.</div>';

    const playerRows = players.length > 0
      ? players.map((player) => `
          <tr>
            <td>
              <strong>${escapeHtml(player.gamertag)}</strong>
              <small>${escapeHtml(player.position || "—")}</small>
            </td>
            <td class="player-rating-cell">${escapeHtml(player.rating ?? "—")}</td>
            <td>${player.goals}</td>
            <td>${player.assists}</td>
            <td>${player.shots}</td>
            <td>${player.passesCompleted} / ${player.passAttempts}</td>
            <td>${player.tacklesCompleted} / ${player.tackleAttempts}</td>
            <td>${player.saves}</td>
          </tr>`).join("")
      : '<tr><td colspan="8" class="text-center py-4 text-muted">Sin detalle individual sincronizado.</td></tr>';

    modalBody.innerHTML = `
      ${comparison}
      <section class="match-player-detail">
        <div class="match-detail-section-title">
          <p class="fc-eyebrow mb-1">Rendimiento individual</p>
          <h3>Jugadores de Chuntaro FC</h3>
        </div>
        <div class="table-responsive">
          <table class="table match-player-table align-middle mb-0">
            <thead>
              <tr>
                <th>Jugador</th>
                <th>Rating</th>
                <th>G</th>
                <th>A</th>
                <th>Tiros</th>
                <th>Pases</th>
                <th>Tackles</th>
                <th>Atajadas</th>
              </tr>
            </thead>
            <tbody>${playerRows}</tbody>
          </table>
        </div>
      </section>`;
  };

  const openDetail = async (matchId) => {
    modalTitle.textContent = "Estadísticas";
    modalBody.innerHTML = '<div class="match-detail-loading">Cargando estadísticas…</div>';
    detailModal.show();

    try {
      const response = await fetch(`${detailEndpoint}?matchId=${encodeURIComponent(matchId)}`, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });
      const payload = await response.json();
      if (!response.ok || !payload.exito) {
        throw new Error(payload.mensaje || `HTTP ${response.status}`);
      }
      renderDetail(payload.data);
    } catch (error) {
      modalBody.innerHTML = `<div class="match-detail-error">${escapeHtml(error.message || error)}</div>`;
    }
  };

  historyEl.addEventListener("click", (event) => {
    const button = event.target.closest(".match-stats-button");
    if (button) openDetail(button.dataset.matchId);
  });

  fetch(endpoint, {
    headers: { Accept: "application/json" },
    credentials: "same-origin",
  })
    .then(async (response) => {
      const payload = await response.json();
      if (!response.ok || !payload.exito) {
        throw new Error(payload.mensaje || `HTTP ${response.status}`);
      }
      return payload.data;
    })
    .then(renderHistory)
    .catch((error) => {
      historyEl.innerHTML = `<div class="match-detail-error">${escapeHtml(error.message || error)}</div>`;
    });
});
