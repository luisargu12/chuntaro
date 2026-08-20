(function () {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const animateDynamicContent = (container, selector) => {
    if (reducedMotion || typeof gsap === "undefined") return;

    const elements = container.querySelectorAll(selector);
    if (elements.length === 0) return;

    gsap.fromTo(
      elements,
      { y: 24, opacity: 0 },
      {
        y: 0,
        opacity: 1,
        duration: 0.65,
        stagger: 0.08,
        ease: "power2.out",
        clearProps: "transform,opacity",
        scrollTrigger: typeof ScrollTrigger !== "undefined"
          ? { trigger: container, start: "top 88%", once: true }
          : undefined,
      }
    );
  };

  const nav = document.getElementById("mainNav");
  if (nav) {
    const onScroll = () => {
      if (window.scrollY > 50) {
        nav.classList.add("navbar-solid", "shadow");
        nav.classList.remove("navbar-transparent");
      } else {
        nav.classList.add("navbar-transparent");
        nav.classList.remove("navbar-solid", "shadow");
      }
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  // Typewriter footer
  const typeEl = document.getElementById("typewriterText");
  if (typeEl) {
    const words = ["CHUNTARO FC", "BULLDOGS"];
    let wordIndex = 0;
    let charIndex = 0;
    let deleting = false;

    const tick = () => {
      const word = words[wordIndex];
      if (deleting) {
        charIndex--;
        typeEl.textContent = word.substring(0, charIndex);
      } else {
        charIndex++;
        typeEl.textContent = word.substring(0, charIndex);
      }

      let speed = deleting ? 50 : 150;
      if (!deleting && charIndex === word.length) {
        speed = 2000;
        deleting = true;
      } else if (deleting && charIndex === 0) {
        deleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        speed = 500;
      }
      setTimeout(tick, speed);
    };
    tick();
  }

  // Últimos partidos almacenados en MySQL
  const matchesEl = document.getElementById("recentMatches");
  if (matchesEl) {
    const endpoint = matchesEl.dataset.endpoint;
    const clubId = String(matchesEl.dataset.clubId || "");
    const ownCrestUrl = matchesEl.dataset.ownCrest || "";
    const crestBase = String(matchesEl.dataset.crestBase || "").replace(/\/+$/, "");
    const crestFallback = matchesEl.dataset.crestFallback || "";

    const escapeHtml = (value) =>
      String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

    const matchDate = (timestamp) => {
      const numericTimestamp = Number(timestamp);
      if (!numericTimestamp) return "Partido finalizado";

      const milliseconds =
        numericTimestamp < 1000000000000 ? numericTimestamp * 1000 : numericTimestamp;

      return new Intl.DateTimeFormat("es-MX", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      }).format(new Date(milliseconds));
    };

    const scoreValue = (club) =>
      Number.parseInt(club?.goals ?? club?.score ?? 0, 10) || 0;

    const rivalCrestUrl = (club) => {
      const crestId =
        club?.details?.customKit?.crestAssetId ??
        club?.customKit?.crestAssetId ??
        club?.TEAM ??
        "";

      return /^\d+$/.test(String(crestId)) && crestBase
        ? `${crestBase}/l${crestId}.png`
        : crestFallback;
    };

    const renderMatches = (matches, cached = false) => {
      if (!Array.isArray(matches) || matches.length === 0) {
        matchesEl.innerHTML =
          '<div class="col-12"><div class="alert alert-info text-center">EA no devolvió partidos de liga recientes.</div></div>';
        return;
      }

      matchesEl.innerHTML = matches.slice(0, 3).map((match) => {
        const clubs = match?.clubs && typeof match.clubs === "object" ? match.clubs : {};
        const ownClub = clubs[clubId] || {};
        const rivalEntry = Object.entries(clubs).find(([id]) => String(id) !== clubId);
        const rivalClub = rivalEntry?.[1] || {};

        const ownScore = scoreValue(ownClub);
        const rivalScore = scoreValue(rivalClub);
        const rivalName =
          rivalClub?.details?.name || rivalClub?.name || "Equipo rival";
        const rivalImage = rivalCrestUrl(rivalClub);

        let resultClass = "text-bg-secondary";
        let resultLabel = "Empate";
        let resultTone = "draw";
        if (ownScore > rivalScore) {
          resultClass = "text-bg-success";
          resultLabel = "Victoria";
          resultTone = "win";
        } else if (ownScore < rivalScore) {
          resultClass = "text-bg-danger";
          resultLabel = "Derrota";
          resultTone = "loss";
        }

        return `
          <div class="col-lg-4">
            <article class="fc-match-card fc-match-card--${resultTone}">
              <div class="fc-match-meta">
                <p class="mb-0">
                  ${escapeHtml(matchDate(match?.timestamp))}
                </p>
                <span class="badge ${resultClass}">${resultLabel}</span>
              </div>
              <div class="fc-match-body">
                <div class="d-flex justify-content-between align-items-center gap-3">
                  <div class="match-team flex-fill">
                    <img
                      class="match-crest mb-2"
                      src="${escapeHtml(ownCrestUrl)}"
                      data-fallback="${escapeHtml(crestFallback)}"
                      alt="Escudo Chuntaro FC"
                    >
                    <h5 class="fw-bold mb-0">Chuntaro FC</h5>
                  </div>
                  <div class="flex-shrink-0">
                    <span class="fc-match-score">
                      ${ownScore}
                      <span>:</span>
                      ${rivalScore}
                    </span>
                  </div>
                  <div class="match-team flex-fill">
                    <img
                      class="match-crest mb-2"
                      src="${escapeHtml(rivalImage)}"
                      data-fallback="${escapeHtml(crestFallback)}"
                      alt="Escudo ${escapeHtml(rivalName)}"
                    >
                    <h5 class="fw-bold mb-0">${escapeHtml(rivalName)}</h5>
                  </div>
                </div>
              </div>
            </article>
          </div>`;
      }).join("");

      matchesEl.querySelectorAll(".match-crest").forEach((image) => {
        image.addEventListener("error", () => {
          const fallback = image.dataset.fallback;
          if (fallback && image.src !== fallback) {
            image.src = fallback;
          }
        });
      });

      animateDynamicContent(matchesEl, ".fc-match-card");

      if (cached) {
        matchesEl.insertAdjacentHTML(
          "beforeend",
          '<div class="col-12"><p class="text-muted small text-center mb-0">Resultados servidos desde caché.</p></div>'
        );
      }
    };

    fetch(endpoint, {
      headers: { Accept: "application/json" },
      credentials: "same-origin",
    })
      .then(async (response) => {
        const payload = await response.json();
        if (!response.ok || !payload.exito) {
          throw new Error(payload.mensaje || `HTTP ${response.status}`);
        }
        return payload;
      })
      .then((payload) => renderMatches(payload.data, payload.cached))
      .catch((error) => {
        console.error("No se pudieron cargar los partidos:", error);
        matchesEl.innerHTML = `
          <div class="col-12">
            <div class="alert alert-warning text-center">
              No se pudieron cargar los partidos guardados:
              ${escapeHtml(error.message || error)}
            </div>
          </div>`;
      });
  }

  // Resumen histórico del club desde MySQL
  const overviewEl = document.getElementById("clubOverview");
  if (overviewEl) {
    const escapeOverview = (value) =>
      String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

    const endpoint = overviewEl.dataset.endpoint;
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
      .then((stats) => {
        const decided = Math.max(
          Number(stats.wins || 0) + Number(stats.draws || 0) + Number(stats.losses || 0),
          1
        );
        const winWidth = (Number(stats.wins || 0) / decided) * 100;
        const drawWidth = (Number(stats.draws || 0) / decided) * 100;
        const lossWidth = (Number(stats.losses || 0) / decided) * 100;
        const achievements = Array.isArray(stats.playoffAchievements)
          ? stats.playoffAchievements.slice(0, 3)
          : [];
        const streakCount = Number(stats.currentStreak?.count || 0);
        const streakNames = {
          victoria: streakCount === 1 ? "victoria" : "victorias",
          empate: streakCount === 1 ? "empate" : "empates",
          derrota: streakCount === 1 ? "derrota" : "derrotas",
        };
        const streakText = streakCount > 0
          ? `${streakCount} ${streakNames[stats.currentStreak?.result] || "partidos"}`
          : "Sin partidos";

        overviewEl.innerHTML = `
          <div class="fc-overview-main">
            <article class="fc-overview-rating">
              <p>Skill rating</p>
              <strong>${escapeOverview(stats.skillRating)}</strong>
              <span>Reputación · Nivel ${escapeOverview(stats.reputationTier ?? "—")}</span>
            </article>

            <article class="fc-overview-record">
              <div class="fc-overview-record-head">
                <div>
                  <strong>${escapeOverview(stats.gamesPlayed)}</strong>
                  <span>Partidos históricos</span>
                </div>
                <div>
                  <strong>${escapeOverview(stats.winRate)}%</strong>
                  <span>Porcentaje de victoria</span>
                </div>
              </div>
              <div class="fc-record-bar" aria-label="Distribución de resultados">
                <i class="fc-record-win" style="width:${winWidth}%"></i>
                <i class="fc-record-draw" style="width:${drawWidth}%"></i>
                <i class="fc-record-loss" style="width:${lossWidth}%"></i>
              </div>
              <div class="fc-record-legend">
                <span><i class="fc-record-win"></i>${escapeOverview(stats.wins)} victorias</span>
                <span><i class="fc-record-draw"></i>${escapeOverview(stats.draws)} empates</span>
                <span><i class="fc-record-loss"></i>${escapeOverview(stats.losses)} derrotas</span>
              </div>
            </article>
          </div>

          <div class="fc-overview-kpis">
            <div><strong>${escapeOverview(stats.goalsFor)}</strong><span>Goles a favor</span></div>
            <div><strong>${escapeOverview(stats.goalsAgainst)}</strong><span>Goles en contra</span></div>
            <div><strong>${Number(stats.goalDifference) >= 0 ? "+" : ""}${escapeOverview(stats.goalDifference)}</strong><span>Diferencia de goles</span></div>
            <div><strong>${escapeOverview(stats.bestDivision ?? "—")}</strong><span>Mejor división</span></div>
            <div><strong>${escapeOverview(stats.promotions)}</strong><span>Ascensos</span></div>
            <div><strong>${escapeOverview(stats.relegations)}</strong><span>Descensos</span></div>
          </div>

          <div class="fc-overview-foot">
            <div class="fc-overview-streaks">
              <div>
                <span>Racha actual</span>
                <strong>${escapeOverview(streakText)}</strong>
              </div>
              <div>
                <span>Equipo némesis</span>
                <strong>${escapeOverview(stats.nemesis?.name || "—")}</strong>
                <small>${stats.nemesis
                  ? `${escapeOverview(stats.nemesis.matches)} ${Number(stats.nemesis.matches) === 1 ? "partido" : "partidos"}`
                  : "Sin registros"}</small>
              </div>
              <div>
                <span>Mejor victoria</span>
                <strong>${stats.bestVictory
                  ? `${escapeOverview(stats.bestVictory.goalsFor)} – ${escapeOverview(stats.bestVictory.goalsAgainst)}`
                  : "—"}</strong>
                <small>${stats.bestVictory
                  ? `vs ${escapeOverview(stats.bestVictory.name)}`
                  : "Sin registros"}</small>
              </div>
            </div>
            <div class="fc-playoff-achievements">
              <p class="fc-card-kicker mb-3">Logros recientes de playoffs</p>
              ${achievements.length > 0
                ? achievements.map((achievement) => `
                    <div>
                      <strong>Temporada ${escapeOverview(achievement.seasonId)}</strong>
                      <span>División ${escapeOverview(achievement.bestDivision ?? "—")} · Grupo ${escapeOverview(achievement.bestFinishGroup ?? "—")}</span>
                    </div>
                  `).join("")
                : '<span class="text-muted">Sin logros de playoffs registrados.</span>'}
            </div>
          </div>`;

        animateDynamicContent(
          overviewEl,
          ".fc-overview-main, .fc-overview-kpis, .fc-overview-foot"
        );
      })
      .catch((error) => {
        overviewEl.innerHTML = `
          <div class="fc-overview-error">
            No se pudo cargar la historia del club: ${escapeOverview(error.message || error)}
          </div>`;
      });
  }

  // GSAP home animations
  if (reducedMotion || typeof gsap === "undefined") return;
  if (typeof ScrollTrigger !== "undefined") {
    gsap.registerPlugin(ScrollTrigger);
  }

  gsap.to(".hero-bg", { scale: 1, duration: 3, ease: "power2.out" });

  const tl = gsap.timeline();
  tl.fromTo(
    ".titulo-banner, .titulo-stats",
    { y: "100%", opacity: 0 },
    { y: "0%", opacity: 1, duration: 1, ease: "power4.out", delay: 0.2 }
  );
  tl.fromTo(
    ".linea",
    { scaleX: 0 },
    { scaleX: 1, duration: 1.2, ease: "power3.out" },
    "-=0.6"
  );

  if (typeof ScrollTrigger === "undefined") return;

  const revealGroup = (trigger, targets, options = {}) => {
    const elements = gsap.utils.toArray(targets);
    if (elements.length === 0) return;

    gsap.fromTo(
      elements,
      {
        y: options.y ?? 32,
        opacity: 0,
      },
      {
        y: 0,
        opacity: 1,
        duration: options.duration ?? 0.75,
        stagger: options.stagger ?? 0.1,
        ease: "power2.out",
        clearProps: "transform,opacity",
        scrollTrigger: {
          trigger,
          start: options.start ?? "top 84%",
          once: true,
        },
      }
    );
  };

  revealGroup(
    ".fc-intro",
    ".fc-intro .col-lg-7, .fc-intro .fc-identity-card",
    { stagger: 0.15 }
  );
  revealGroup(
    ".home-manifesto",
    ".home-manifesto .col-lg-7, .home-manifesto .col-lg-5",
    { stagger: 0.15 }
  );

  gsap.utils.toArray(".fc-section-heading, .home-section-heading").forEach((heading) => {
    revealGroup(heading, heading, { y: 22, duration: 0.65 });
  });

  revealGroup(
    ".home-kalcho-section",
    ".home-kalcho-section .kalcho-coming-soon"
  );
  revealGroup(
    ".fc-explore-section",
    ".fc-explore-section .fc-feature-card",
    { stagger: 0.12 }
  );
  revealGroup(
    ".home-explore-section",
    ".home-explore-section .home-explore-card, .home-explore-section .home-tournament-cta",
    { stagger: 0.1 }
  );

})();
