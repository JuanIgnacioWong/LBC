(function () {
  var DELAY_MS = 5000;

  function showPopup() {
    var overlay = document.getElementById('swish-popup-overlay');
    if (overlay) {
      overlay.style.display = 'flex';
      overlay.classList.add('is-visible');
    }
  }

  function closePopup() {
    var overlay = document.getElementById('swish-popup-overlay');
    if (overlay) {
      overlay.classList.remove('is-visible');
      overlay.style.display = 'none';
    }
  }

  function bindOverlayClose() {
    var overlay = document.getElementById('swish-popup-overlay');
    if (!overlay) return;

    overlay.addEventListener('click', function (e) {
      if (e.target === this) closePopup();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePopup();
  });

  setTimeout(bindOverlayClose, 0);
  setTimeout(showPopup, DELAY_MS);

  window.SwishPopup = { show: showPopup, close: closePopup };
})();
