(() => {
  const header = document.getElementById("site-header");
  const revealItems = document.querySelectorAll("[data-liga-reveal]");
  const tabButtons = document.querySelectorAll("[data-liga-tab-target]");
  const sponsorsTrack = document.querySelector("[data-liga-marquee]");

  if (header) {
    const toggleHeaderState = () => {
      if (window.scrollY > 12) {
        header.classList.add("is-scrolled");
      } else {
        header.classList.remove("is-scrolled");
      }
    };

    window.addEventListener("scroll", toggleHeaderState, { passive: true });
    toggleHeaderState();
  }

  if (tabButtons.length > 0) {
    tabButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const target = button.getAttribute("data-liga-tab-target");
        if (!target) {
          return;
        }

        tabButtons.forEach((item) => {
          item.classList.remove("is-active");
          item.setAttribute("aria-selected", "false");
        });
        button.classList.add("is-active");
        button.setAttribute("aria-selected", "true");

        document.querySelectorAll("[data-liga-tab-panel]").forEach((panel) => {
          const panelTarget = panel.getAttribute("data-liga-tab-panel");
          panel.classList.toggle("is-active", panelTarget === target);
        });
      });
    });
  }

  if (revealItems.length > 0 && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }
          entry.target.classList.add("is-revealed");
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.15 }
    );

    revealItems.forEach((item) => observer.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add("is-revealed"));
  }

  if (sponsorsTrack) {
    let offset = 0;
    const speed = 0.35;
    const animate = () => {
      offset -= speed;
      if (Math.abs(offset) > sponsorsTrack.scrollWidth / 2) {
        offset = 0;
      }
      sponsorsTrack.style.transform = `translateX(${offset}px)`;
      window.requestAnimationFrame(animate);
    };
    window.requestAnimationFrame(animate);
  }
})();
