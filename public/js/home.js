(function () {
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

  // GSAP home animations
  if (typeof gsap === "undefined") return;
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

})();
