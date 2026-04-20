(() => {
  const toggle = document.querySelector("[data-liga-menu-toggle]");
  const menu = document.querySelector("[data-liga-menu]");
  const searchToggle = document.querySelector("[data-liga-search-toggle]");
  const searchPanel = document.querySelector("[data-liga-search]");

  const closeMenu = () => {
    if (!toggle || !menu) {
      return;
    }
    toggle.setAttribute("aria-expanded", "false");
    menu.classList.remove("is-open");
  };

  if (toggle && menu) {
    toggle.addEventListener("click", () => {
      const expanded = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", String(!expanded));
      menu.classList.toggle("is-open");
    });
  }

  if (searchToggle && searchPanel) {
    searchToggle.addEventListener("click", () => {
      const expanded = searchToggle.getAttribute("aria-expanded") === "true";
      searchToggle.setAttribute("aria-expanded", String(!expanded));
      searchPanel.classList.toggle("is-open");
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
      return;
    }

    closeMenu();
    if (searchToggle && searchPanel) {
      searchToggle.setAttribute("aria-expanded", "false");
      searchPanel.classList.remove("is-open");
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 992) {
      closeMenu();
    }
  });
})();
