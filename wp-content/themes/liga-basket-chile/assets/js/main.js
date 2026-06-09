/**
 * Home UX interactions for Liga home:
 * 1) Mobile menu
 * 2) Accessible tabs
 * 3) Sticky header state
 * 4) Lightweight reveal on scroll
 */
(() => {
  "use strict";

  const MOBILE_MAX_WIDTH = 992;

  const onReady = (callback) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback, { once: true });
      return;
    }
    callback();
  };

  const qs = (selector, scope = document) => scope.querySelector(selector);
  const qsa = (selector, scope = document) =>
    Array.from(scope.querySelectorAll(selector));

  const escapeId = (value) => {
    if (window.CSS && typeof window.CSS.escape === "function") {
      return window.CSS.escape(value);
    }
    return value.replace(/([^\w-])/g, "\\$1");
  };

  const isDesktop = () =>
    window.matchMedia(`(min-width: ${MOBILE_MAX_WIDTH + 1}px)`).matches;

  onReady(() => {
    document.documentElement.classList.add("liga-js-ready");
    initMobileMenu();
    initHeaderSearch();
    initTabs();
    initStickyHeader();
    initHeroSliders();
    initSponsorsCarousel();
    initRevealOnScroll();
  });

  /**
   * 1) Mobile menu:
   * - Toggle from hamburger
   * - Close on outside click
   * - Close on Escape
   * - Keep aria-expanded in sync
   */
  function initMobileMenu() {
    const menuButton = qs(".liga-header-menu-button");
    const menuPanel = qs(".liga-header-nav");

    if (!menuButton || !menuPanel) {
      return;
    }

    const openClass = "liga-nav-open";
    const bodyOpenClass = "liga-menu-open";
    const menuLinks = qsa("a", menuPanel);

    const setMenuState = (isOpen) => {
      menuPanel.classList.toggle(openClass, isOpen);
      menuButton.setAttribute("aria-expanded", String(isOpen));
      document.body.classList.toggle(
        bodyOpenClass,
        isOpen && !isDesktop()
      );
    };

    const isMenuOpen = () => menuPanel.classList.contains(openClass);

    // Normalize initial state
    setMenuState(false);

    menuButton.addEventListener("click", (event) => {
      event.stopPropagation();
      setMenuState(!isMenuOpen());
    });

    document.addEventListener("click", (event) => {
      if (!isMenuOpen() || isDesktop()) {
        return;
      }

      const target = event.target;
      if (!(target instanceof Element)) {
        return;
      }

      if (menuPanel.contains(target) || menuButton.contains(target)) {
        return;
      }

      setMenuState(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || !isMenuOpen()) {
        return;
      }

      setMenuState(false);
      menuButton.focus();
    });

    menuLinks.forEach((link) => {
      link.addEventListener("click", () => {
        if (!isDesktop()) {
          setMenuState(false);
        }
      });
    });

    let resizeTimer = null;
    window.addEventListener(
      "resize",
      () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
          if (isDesktop()) {
            setMenuState(false);
          }
        }, 120);
      },
      { passive: true }
    );
  }

  /**
   * Header search:
   * - Toggle panel from search button
   * - Close on outside click / Escape
   * - Keep aria-expanded and hidden in sync
   */
  function initHeaderSearch() {
    const searchButton = qs(".liga-header-search-button");
    const searchPanel = qs("#liga-header-search-panel");

    if (!searchButton || !searchPanel) {
      return;
    }

    const openClass = "is-open";
    const searchInput = qs('input[type="search"]', searchPanel);

    const setSearchState = (isOpen) => {
      searchPanel.classList.toggle(openClass, isOpen);
      searchPanel.hidden = !isOpen;
      searchButton.setAttribute("aria-expanded", String(isOpen));

      if (isOpen && searchInput) {
        searchInput.focus();
      }
    };

    const isSearchOpen = () => searchPanel.classList.contains(openClass);

    setSearchState(false);

    searchButton.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      setSearchState(!isSearchOpen());
    });

    document.addEventListener("click", (event) => {
      if (!isSearchOpen()) {
        return;
      }

      const target = event.target;
      if (!(target instanceof Element)) {
        return;
      }

      if (searchPanel.contains(target) || searchButton.contains(target)) {
        return;
      }

      setSearchState(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || !isSearchOpen()) {
        return;
      }

      setSearchState(false);
      searchButton.focus();
    });
  }

  /**
   * 2) Division tabs:
   * - Active tab state
   * - Show/Hide related panel if present
   * - Keyboard navigation (arrow/home/end)
   */
  function initTabs() {
    const tabLists = qsa('[role="tablist"], .liga-division-tabs');
    if (tabLists.length === 0) {
      return;
    }

    tabLists.forEach((tabList, listIndex) => {
      const tabs = qsa('[role="tab"], .liga-division-tab, .liga-tab', tabList);
      if (tabs.length === 0) {
        return;
      }

      tabList.setAttribute("role", "tablist");
      const panelsByTab = new Map();

      tabs.forEach((tab, tabIndex) => {
        if (!tab.id) {
          tab.id = `liga-tab-${listIndex + 1}-${tabIndex + 1}`;
        }

        tab.setAttribute("role", "tab");
        tab.setAttribute("tabindex", "-1");

        const panelSelector = getPanelSelector(tab);
        if (!panelSelector) {
          return;
        }

        const panel = resolvePanel(panelSelector);
        if (!panel) {
          return;
        }

        if (!panel.id) {
          panel.id = `liga-tab-panel-${listIndex + 1}-${tabIndex + 1}`;
        }

        tab.setAttribute("aria-controls", panel.id);
        panel.setAttribute("role", "tabpanel");
        panel.setAttribute("aria-labelledby", tab.id);
        panelsByTab.set(tab, panel);
      });

      const initialTab =
        tabs.find(
          (tab) =>
            tab.classList.contains("liga-tab--active") ||
            tab.classList.contains("liga-tab-active") ||
            tab.getAttribute("aria-selected") === "true"
        ) || tabs[0];

      activateTab(initialTab, false);
      ensureAtLeastOneVisiblePanel();

      tabs.forEach((tab) => {
        tab.addEventListener("click", () => activateTab(tab, true));

        tab.addEventListener("keydown", (event) => {
          const currentIndex = tabs.indexOf(tab);
          let nextIndex = null;

          if (event.key === "ArrowRight" || event.key === "ArrowDown") {
            nextIndex = (currentIndex + 1) % tabs.length;
          } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
            nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
          } else if (event.key === "Home") {
            nextIndex = 0;
          } else if (event.key === "End") {
            nextIndex = tabs.length - 1;
          }

          if (nextIndex === null) {
            return;
          }

          event.preventDefault();
          activateTab(tabs[nextIndex], true);
        });
      });

      function activateTab(activeTab, shouldFocus) {
        tabs.forEach((tab) => {
          const isActive = tab === activeTab;

          tab.classList.toggle("liga-tab--active", isActive);
          tab.classList.toggle("liga-tab-active", isActive);
          tab.setAttribute("aria-selected", String(isActive));
          tab.setAttribute("tabindex", isActive ? "0" : "-1");

          const panel = panelsByTab.get(tab);
          if (panel) {
            panel.hidden = !isActive;
            panel.classList.toggle("is-visible", isActive);
            panel.classList.toggle("is-revealed", isActive);
          }
        });

        if (shouldFocus) {
          activeTab.focus();
        }
      }

      function ensureAtLeastOneVisiblePanel() {
        const panels = tabs
          .map((tab) => panelsByTab.get(tab))
          .filter((panel) => panel instanceof HTMLElement);

        if (panels.length === 0) {
          return;
        }

        const hasVisiblePanel = panels.some((panel) => !panel.hidden);
        if (hasVisiblePanel) {
          return;
        }

        const fallbackTab = tabs[0];
        const fallbackPanel = panelsByTab.get(fallbackTab);
        if (!fallbackTab || !fallbackPanel) {
          return;
        }

        fallbackTab.classList.add("liga-tab--active", "liga-tab-active");
        fallbackTab.setAttribute("aria-selected", "true");
        fallbackTab.setAttribute("tabindex", "0");
        fallbackPanel.hidden = false;
        fallbackPanel.classList.add("is-visible", "is-revealed");
      }
    });

    function getPanelSelector(tab) {
      const ariaControls = tab.getAttribute("aria-controls");
      if (ariaControls) {
        return `#${escapeId(ariaControls)}`;
      }

      const dataTarget =
        tab.getAttribute("data-panel-target") || tab.getAttribute("data-target");
      if (dataTarget) {
        return dataTarget.startsWith("#")
          ? dataTarget
          : `#${escapeId(dataTarget)}`;
      }

      const href = tab.getAttribute("href");
      if (href && href.startsWith("#")) {
        return href;
      }

      return null;
    }

    function resolvePanel(selector) {
      try {
        return qs(selector);
      } catch (_error) {
        return null;
      }
    }
  }

  /**
   * 3) Sticky header:
   * - Adds a subtle scrolled state after threshold
   */
  function initStickyHeader() {
    const header =
      qs(".liga-header") || qs("#site-header") || qs(".liga-site-header");

    if (!header) {
      return;
    }

    const scrolledClass = "liga-header--scrolled";
    const bodyScrolledClass = "liga-is-scrolled";
    const threshold = 24;
    let ticking = false;

    const updateState = () => {
      const isScrolled = window.scrollY > threshold;
      header.classList.toggle(scrolledClass, isScrolled);
      document.body.classList.toggle(bodyScrolledClass, isScrolled);
      ticking = false;
    };

    updateState();

    window.addEventListener(
      "scroll",
      () => {
        if (ticking) {
          return;
        }
        window.requestAnimationFrame(updateState);
        ticking = true;
      },
      { passive: true }
    );
  }

  /**
   * 4) Home hero slider:
   * - Soporta banners multiples activos
   * - Flechas + dots + teclado
   * - Autoplay configurable con pausa por hover/interaccion
   */
  function initHeroSliders() {
    const sliderLayouts = qsa(".liga-hero-layout[data-liga-hero-slider]");
    if (sliderLayouts.length === 0) {
      return;
    }

    sliderLayouts.forEach((layout) => {
      const slides = qsa(".liga-hero-slide", layout);
      if (slides.length === 0) {
        return;
      }

      const sliderEnabled =
        layout.getAttribute("data-liga-hero-slider") === "1" &&
        slides.length > 1;
      const autoplayEnabled =
        sliderEnabled && layout.getAttribute("data-liga-autoplay") === "1";
      const autoplayInterval = Math.max(
        2500,
        Number.parseInt(
          layout.getAttribute("data-liga-autoplay-interval") || "5000",
          10
        ) || 5000
      );

      const prevButton = qs(".liga-hero-slider__arrow--prev", layout);
      const nextButton = qs(".liga-hero-slider__arrow--next", layout);
      const dots = qsa(".liga-hero-slider__dot", layout);

      let currentIndex = slides.findIndex((slide) =>
        slide.classList.contains("is-active")
      );
      if (currentIndex < 0) {
        currentIndex = 0;
      }

      let autoplayTimer = null;
      let isHovering = false;
      let userPaused = false;

      const stopAutoplay = () => {
        if (!autoplayTimer) {
          return;
        }
        window.clearInterval(autoplayTimer);
        autoplayTimer = null;
      };

      const startAutoplay = () => {
        if (!autoplayEnabled || userPaused || isHovering) {
          return;
        }
        stopAutoplay();
        autoplayTimer = window.setInterval(() => {
          goToSlide(currentIndex + 1, false);
        }, autoplayInterval);
      };

      const normalizeIndex = (index) => {
        if (slides.length <= 0) {
          return 0;
        }
        const modulo = index % slides.length;
        return modulo < 0 ? modulo + slides.length : modulo;
      };

      const syncSlideState = () => {
        slides.forEach((slide, index) => {
          const isActive = index === currentIndex;
          slide.classList.toggle("is-active", isActive);
          slide.setAttribute("aria-hidden", String(!isActive));
        });

        dots.forEach((dot, index) => {
          const isActive = index === currentIndex;
          dot.classList.toggle("is-active", isActive);
          dot.setAttribute("aria-current", String(isActive));
        });
      };

      const goToSlide = (index, fromUser) => {
        if (!sliderEnabled) {
          return;
        }
        currentIndex = normalizeIndex(index);
        syncSlideState();

        if (fromUser) {
          userPaused = true;
          stopAutoplay();
        }
      };

      syncSlideState();

      if (!sliderEnabled) {
        return;
      }

      if (prevButton) {
        prevButton.addEventListener("click", (event) => {
          event.preventDefault();
          goToSlide(currentIndex - 1, true);
        });
      }

      if (nextButton) {
        nextButton.addEventListener("click", (event) => {
          event.preventDefault();
          goToSlide(currentIndex + 1, true);
        });
      }

      dots.forEach((dot) => {
        dot.addEventListener("click", (event) => {
          event.preventDefault();
          const targetIndex = Number.parseInt(
            dot.getAttribute("data-liga-slide-to") || "",
            10
          );
          if (Number.isNaN(targetIndex)) {
            return;
          }
          goToSlide(targetIndex, true);
        });
      });

      layout.addEventListener("keydown", (event) => {
        if (!layout.contains(document.activeElement)) {
          return;
        }

        if (event.key === "ArrowLeft") {
          event.preventDefault();
          goToSlide(currentIndex - 1, true);
          return;
        }

        if (event.key === "ArrowRight") {
          event.preventDefault();
          goToSlide(currentIndex + 1, true);
          return;
        }

        if (event.key === "Home") {
          event.preventDefault();
          goToSlide(0, true);
          return;
        }

        if (event.key === "End") {
          event.preventDefault();
          goToSlide(slides.length - 1, true);
        }
      });

      layout.addEventListener("mouseenter", () => {
        isHovering = true;
        stopAutoplay();
      });

      layout.addEventListener("mouseleave", () => {
        isHovering = false;
        startAutoplay();
      });

      layout.addEventListener("focusin", () => {
        stopAutoplay();
      });

      layout.addEventListener("focusout", () => {
        window.setTimeout(() => {
          if (!layout.contains(document.activeElement)) {
            startAutoplay();
          }
        }, 0);
      });

      document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
          stopAutoplay();
          return;
        }
        startAutoplay();
      });

      startAutoplay();
    });
  }

  /**
   * Sponsors carousel:
   * - Native horizontal scroll with prev/next controls
   * - Lightweight autoplay loop
   * - Pause on hover/focus and resume on leave
   */
  function initSponsorsCarousel() {
    const carousels = qsa("[data-liga-sponsors-carousel]");
    if (carousels.length === 0) {
      return;
    }

    const prefersReducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)"
    ).matches;

    carousels.forEach((carousel) => {
      const track = qs("[data-liga-sponsors-track]", carousel);
      const previousButton = qs(".liga-sponsors-nav--prev", carousel);
      const nextButton = qs(".liga-sponsors-nav--next", carousel);

      if (!track || !previousButton || !nextButton) {
        return;
      }

      let autoplayTimer = null;
      let resizeTimer = null;
      let interactionPaused = false;

      const isOverflowing = () => track.scrollWidth - track.clientWidth > 2;
      const isAtStart = () => track.scrollLeft <= 2;
      const isAtEnd = () =>
        track.scrollLeft >= track.scrollWidth - track.clientWidth - 2;

      const getStep = () => {
        const firstItem = qs(".liga-sponsors-item", track);
        if (!firstItem) {
          return track.clientWidth;
        }

        const styles = window.getComputedStyle(track);
        const gapRaw = styles.columnGap || styles.gap || "0";
        const gap = Number.parseFloat(gapRaw);
        return firstItem.getBoundingClientRect().width + (Number.isNaN(gap) ? 0 : gap);
      };

      const stopAutoplay = () => {
        if (!autoplayTimer) {
          return;
        }
        window.clearInterval(autoplayTimer);
        autoplayTimer = null;
      };

      const goNext = (smooth = true) => {
        if (!isOverflowing()) {
          return;
        }

        if (isAtEnd()) {
          track.scrollTo({ left: 0, behavior: smooth ? "smooth" : "auto" });
          return;
        }

        track.scrollBy({ left: getStep(), behavior: smooth ? "smooth" : "auto" });
      };

      const goPrevious = (smooth = true) => {
        if (!isOverflowing()) {
          return;
        }

        if (isAtStart()) {
          track.scrollTo({
            left: track.scrollWidth,
            behavior: smooth ? "smooth" : "auto",
          });
          return;
        }

        track.scrollBy({
          left: getStep() * -1,
          behavior: smooth ? "smooth" : "auto",
        });
      };

      const startAutoplay = () => {
        if (prefersReducedMotion || interactionPaused || !isOverflowing()) {
          return;
        }

        stopAutoplay();
        autoplayTimer = window.setInterval(() => {
          if (document.hidden || interactionPaused) {
            return;
          }
          goNext(true);
        }, 4200);
      };

      const syncCarouselState = () => {
        const canScroll = isOverflowing();
        carousel.setAttribute("data-can-scroll", canScroll ? "true" : "false");
        previousButton.disabled = !canScroll;
        nextButton.disabled = !canScroll;

        if (!canScroll) {
          stopAutoplay();
          return;
        }

        if (!interactionPaused) {
          startAutoplay();
        }
      };

      previousButton.addEventListener("click", (event) => {
        event.preventDefault();
        stopAutoplay();
        goPrevious(true);
        if (!carousel.contains(document.activeElement)) {
          interactionPaused = false;
          startAutoplay();
        }
      });

      nextButton.addEventListener("click", (event) => {
        event.preventDefault();
        stopAutoplay();
        goNext(true);
        if (!carousel.contains(document.activeElement)) {
          interactionPaused = false;
          startAutoplay();
        }
      });

      const pauseFromInteraction = () => {
        interactionPaused = true;
        stopAutoplay();
      };

      const resumeFromInteraction = () => {
        interactionPaused = false;
        startAutoplay();
      };

      carousel.addEventListener("mouseenter", pauseFromInteraction);
      carousel.addEventListener("mouseleave", resumeFromInteraction);
      carousel.addEventListener("focusin", pauseFromInteraction);
      carousel.addEventListener("focusout", () => {
        window.setTimeout(() => {
          if (!carousel.contains(document.activeElement)) {
            resumeFromInteraction();
          }
        }, 0);
      });

      track.addEventListener("touchstart", pauseFromInteraction, { passive: true });
      track.addEventListener("touchend", resumeFromInteraction, { passive: true });

      document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
          stopAutoplay();
          return;
        }
        if (!interactionPaused) {
          startAutoplay();
        }
      });

      window.addEventListener(
        "resize",
        () => {
          window.clearTimeout(resizeTimer);
          resizeTimer = window.setTimeout(syncCarouselState, 140);
        },
        { passive: true }
      );

      syncCarouselState();
      startAutoplay();
    });
  }

  /**
   * 5) Reveal on scroll:
   * - Uses IntersectionObserver when available
   * - Falls back gracefully
   */
  function initRevealOnScroll() {
    const prefersReducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)"
    ).matches;

    const targets = [
      ...qsa(".liga-home > section"),
      ...qsa(".liga-home .liga-card"),
      ...qsa(".liga-footer .liga-footer-block"),
      ...qsa(".liga-sponsors-item"),
    ];

    const uniqueTargets = [...new Set(targets)];
    if (uniqueTargets.length === 0) {
      return;
    }

    const revealNow = (element) => {
      element.classList.remove("is-reveal-pending");
      element.classList.add("is-visible", "is-revealed");
    };

    uniqueTargets.forEach((element) => element.classList.add("is-reveal-pending"));

    if (prefersReducedMotion || !("IntersectionObserver" in window)) {
      uniqueTargets.forEach(revealNow);
      return;
    }

    const observer = new IntersectionObserver(
      (entries, instance) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }
          revealNow(entry.target);
          instance.unobserve(entry.target);
        });
      },
      {
        threshold: 0.14,
        rootMargin: "0px 0px -8% 0px",
      }
    );

    uniqueTargets.forEach((element) => observer.observe(element));
  }
})();
