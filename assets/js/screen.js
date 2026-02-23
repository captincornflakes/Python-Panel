(function () {
  let screenRefreshTimer = null;

  function loadScreenOutput(screen, name, id) {
    const metaEl = document.getElementById('screen-modal-meta');
    const outEl = document.getElementById('screen-modal-output');
     const autoScrollToggle = document.getElementById('screen-auto-scroll-toggle');
    if (!outEl || !screen) return;

    if (metaEl) metaEl.textContent = 'Project: ' + (name || id) + ' | Screen: ' + screen;
    // Only show the loading text if there's nothing yet, so refreshes don't flicker
    if (!outEl.textContent) {
      outEl.textContent = 'Loading...';
    }

    fetch('assets/php/screen_output.php?screen=' + encodeURIComponent(screen), {
      credentials: 'same-origin'
    })
      .then(r => r.text().then(text => ({ ok: r.ok, status: r.status, text })))
      .then(result => {
        if (!result.ok) {
          if (result.status === 404) {
            // When logging is disabled or no log exists, show the backend message
            outEl.textContent = result.text || 'No screen log is available for this session.';
            return;
          }
          throw new Error('HTTP_' + result.status);
        }

        outEl.textContent = result.text;
        try {
          if (!autoScrollToggle || !autoScrollToggle.checked) {
            outEl.scrollTop = outEl.scrollHeight;
          }
        } catch (e) {
          // ignore scroll errors
        }
      })
      .catch(err => {
        if (!err) return;
        outEl.textContent = 'Failed to load screen output.';
        console.error(err);
      });
  }

  function clearScreenRefreshTimer() {
    if (screenRefreshTimer !== null) {
      clearInterval(screenRefreshTimer);
      screenRefreshTimer = null;
    }
  }

  function bindScreenActions() {
    const body = document.getElementById('projects-body');
    const modalEl = document.getElementById('screenModal');
    const autoScrollToggle = document.getElementById('screen-auto-scroll-toggle');
    const outEl = document.getElementById('screen-modal-output');
    if (!body) return;

    // Stop refreshing when the modal is fully hidden
    if (modalEl && window.bootstrap) {
      modalEl.addEventListener('hidden.bs.modal', () => {
        clearScreenRefreshTimer();
      });
    }

    // Auto-pause auto-scroll when user scrolls up, re-enable when back at bottom
    if (outEl && autoScrollToggle) {
      outEl.addEventListener('scroll', () => {
        const distanceFromBottom = outEl.scrollHeight - outEl.clientHeight - outEl.scrollTop;
        const atBottom = distanceFromBottom <= 5;
        if (atBottom) {
          if (autoScrollToggle.checked) autoScrollToggle.checked = false;
        } else {
          if (!autoScrollToggle.checked) autoScrollToggle.checked = true;
        }
      });
    }

    body.addEventListener('click', e => {
      const viewBtn = e.target.closest('.js-view-screen');
      if (!viewBtn) return;

      const tr = viewBtn.closest('tr');
      if (!tr) return;

      const id = tr.dataset.projectId;
      const name = tr.dataset.projectName || '';
      const screen = tr.dataset.screenName || '';

      if (!screen) {
        if (window.showFeedback) window.showFeedback('No screen session for this project yet.', 'warning');
        return;
      }

      // Reset auto-scroll to enabled each time the modal is opened
      if (autoScrollToggle) {
        autoScrollToggle.checked = false;
      }

      // Initial load
      loadScreenOutput(screen, name, id);

      // Show the modal
      if (modalEl && window.bootstrap) {
        const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        instance.show();
        // Ensure we start at the bottom on first show even before the first refresh completes
        if (outEl) {
          try {
            outEl.scrollTop = outEl.scrollHeight;
          } catch (e) {
            // ignore scroll errors
          }
        }
      }

      // Start/refresh the 3-second polling while the modal is open
      clearScreenRefreshTimer();
      screenRefreshTimer = setInterval(() => {
        loadScreenOutput(screen, name, id);
      }, 3000);
    });
  }

  document.addEventListener('DOMContentLoaded', bindScreenActions);
})();
