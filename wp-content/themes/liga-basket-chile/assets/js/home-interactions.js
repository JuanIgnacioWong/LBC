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
    initMobileMenu();
    initTabs();
    initStickyHeader();
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
   * 4) Reveal on scroll:
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
