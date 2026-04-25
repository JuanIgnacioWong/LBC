(() => {
  "use strict";

  const fields = Array.from(document.querySelectorAll("[data-liga-banner-media]"));
  if (fields.length === 0 || typeof window.wp === "undefined" || !window.wp.media) {
    return;
  }

  const createImageElement = (src, alt) => {
    const image = document.createElement("img");
    image.className = "liga-banner-media-field__preview-image";
    image.src = String(src || "");
    image.alt = String(alt || "");
    image.loading = "lazy";
    image.decoding = "async";
    return image;
  };

  fields.forEach((field) => {
    const hiddenInput = field.querySelector('input[type="hidden"]');
    const preview = field.querySelector("[data-liga-banner-preview]");
    const selectButton = field.querySelector("[data-liga-banner-select]");
    const removeButton = field.querySelector("[data-liga-banner-remove]");

    if (!hiddenInput || !preview || !selectButton || !removeButton) {
      return;
    }

    const selectLabel = String(field.getAttribute("data-select-label") || "Seleccionar imagen");
    const changeLabel = String(field.getAttribute("data-change-label") || "Cambiar imagen");
    const emptyText = String(field.getAttribute("data-empty-text") || "No hay imagen seleccionada.");
    const modalTitle = String(field.getAttribute("data-modal-title") || "Seleccionar imagen del banner");
    const modalButton = String(field.getAttribute("data-modal-button") || "Usar esta imagen");

    let mediaFrame = null;

    const hasImage = () => {
      const parsed = Number.parseInt(hiddenInput.value || "", 10);
      return Number.isFinite(parsed) && parsed > 0;
    };

    const renderEmptyState = () => {
      preview.innerHTML = "";
      const empty = document.createElement("p");
      empty.className = "liga-banner-media-field__empty";
      empty.textContent = emptyText;
      preview.appendChild(empty);
    };

    const syncButtons = () => {
      const imageSelected = hasImage();
      selectButton.textContent = imageSelected ? changeLabel : selectLabel;
      removeButton.classList.toggle("is-hidden", !imageSelected);
    };

    const setSelection = (attachment) => {
      const id = Number.parseInt(String(attachment?.id || ""), 10);
      const url = String(
        attachment?.sizes?.medium?.url || attachment?.sizes?.large?.url || attachment?.url || ""
      );
      const alt = String(attachment?.alt || attachment?.title || "");

      if (!Number.isFinite(id) || id <= 0 || url === "") {
        return;
      }

      hiddenInput.value = String(id);
      preview.innerHTML = "";
      preview.appendChild(createImageElement(url, alt));
      syncButtons();
    };

    const clearSelection = () => {
      hiddenInput.value = "";
      renderEmptyState();
      syncButtons();
    };

    const getMediaFrame = () => {
      if (mediaFrame) {
        return mediaFrame;
      }

      mediaFrame = window.wp.media({
        title: modalTitle,
        button: { text: modalButton },
        library: { type: "image" },
        multiple: false,
      });

      mediaFrame.on("select", () => {
        const selection = mediaFrame.state().get("selection");
        const first = selection && selection.first ? selection.first() : null;
        if (!first) {
          return;
        }

        setSelection(first.toJSON());
      });

      return mediaFrame;
    };

    selectButton.addEventListener("click", (event) => {
      event.preventDefault();
      getMediaFrame().open();
    });

    removeButton.addEventListener("click", (event) => {
      event.preventDefault();
      clearSelection();
    });

    if (!hasImage()) {
      renderEmptyState();
    }

    syncButtons();
  });
})();
