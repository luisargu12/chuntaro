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

  const counters = {
    lesiones: document.getElementById("stat-lesiones"),
    inactivos: document.getElementById("stat-inactivos"),
    expulsiones: document.getElementById("stat-expulsiones"),
    derrotas: document.getElementById("stat-derrotas"),
  };

  const state = { lesiones: 0, inactivos: 0, expulsiones: 0, derrotas: 0 };
  const targets = { lesiones: 12, inactivos: 7, expulsiones: 4, derrotas: 15 };

  gsap.to(state, {
    ...targets,
    duration: 2,
    ease: "power1.out",
    scrollTrigger: {
      trigger: ".estadisticas",
      start: "top 80%",
      toggleActions: "play none none none",
    },
    onUpdate: () => {
      Object.keys(counters).forEach((key) => {
        if (counters[key]) counters[key].textContent = Math.round(state[key]);
      });
    },
  });
})();
