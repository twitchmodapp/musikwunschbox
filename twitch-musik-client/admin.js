(() => {
  'use strict';
  const themeButton = document.getElementById('tmc-theme-toggle');
  const wrap = themeButton && themeButton.closest('.wrap');
  if (themeButton && wrap) {
    const key = 'tmc-admin-darkmode';
    let dark = false;
    try { dark = localStorage.getItem(key) === 'yes'; } catch (_) {}
    const applyTheme = () => {
      wrap.classList.toggle('tmc-dark', dark);
      themeButton.textContent = dark ? 'Darkmode ausschalten' : 'Darkmode einschalten';
      themeButton.setAttribute('aria-pressed', String(dark));
    };
    applyTheme();
    themeButton.addEventListener('click', () => {
      dark = !dark;
      try { localStorage.setItem(key, dark ? 'yes' : 'no'); } catch (_) {}
      applyTheme();
    });
  }

  // TMC 0.7.11: Scrollposition und Tastaturfokus erhalten.
  const scrollKey = 'tmc-admin-scroll-position-v1';

  function formIdentity(form) {
    const data = new FormData(form);
    return [
      data.get('action') || '',
      data.get('song_id') || '',
      data.get('tmc_list_action') || '',
      data.has('tmc_save') ? 'settings' : ''
    ].join('|');
  }

  document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) ||
        event.defaultPrevented) return;

    const data = new FormData(form);
    const action = data.get('action');

    if (!data.has('tmc_save') &&
        !['tmc_quick_action', 'tmc_list_control',
          'tmc_sync_reward'].includes(action)) return;

    const button = event.submitter;

    try {
      sessionStorage.setItem(scrollKey, JSON.stringify({
        x: window.scrollX,
        y: window.scrollY,
        time: Date.now(),
        form: formIdentity(form),
        buttonName: button?.name || '',
        buttonValue: button?.value || '',
        buttonId: button?.id || ''
      }));
    } catch (_) {}
  });

  try {
    const raw = sessionStorage.getItem(scrollKey);
    sessionStorage.removeItem(scrollKey);

    if (raw) {
      const saved = JSON.parse(raw);

      if (Date.now() - saved.time < 300000 &&
          Number.isFinite(saved.y) &&
          Number.isFinite(saved.x)) {

        const restorePosition = () => {
          window.scrollTo({
            top: saved.y,
            left: saved.x,
            behavior: 'instant'
          });
        };

        let interrupted = false;

        const cancelRestore = () => {
          interrupted = true;
        };

        ['wheel', 'touchstart', 'pointerdown', 'keydown']
          .forEach(type => {
            window.addEventListener(type, cancelRestore, {
              once: true,
              passive: true
            });
          });

        const restore = () => {
          if (interrupted) return;

          if (document.activeElement === document.body) {
            const form = Array.from(document.forms).find(
              f => formIdentity(f) === saved.form
            );

            if (form) {
              const buttons = Array.from(form.elements).filter(
                e => e.type === 'submit'
              );

              const button = buttons.find(e =>
                (saved.buttonId && e.id === saved.buttonId) ||
                (e.name === saved.buttonName &&
                 e.value === saved.buttonValue)
              ) || buttons[0];

              if (button) {
                button.focus({preventScroll: true});
              }
            }
          }

          restorePosition();
        };

        requestAnimationFrame(restore);

        if (document.readyState !== 'complete') {
          window.addEventListener('load', () => {
            if (!interrupted) restorePosition();
          }, {once: true});
        }
      }
    }
  } catch (_) {}

  const body = document.getElementById('tmc-queue-body');
  const status = document.getElementById('tmc-refresh-state');
  const cfg = window.TMCAdminQueue;
  if (!body || !cfg || !cfg.url || !cfg.nonce) return;
  let busy = false;
  let pendingHtml = null;
  let paused = false;
  // Controls are server-rendered so they remain visible if JavaScript fails.
  const toggle = document.getElementById('tmc-refresh-toggle');
  const manual = document.getElementById('tmc-refresh-manual');
  if (!toggle || !manual) return;
  function readingTable() {
    const selection = window.getSelection();
    const anchor = selection && selection.anchorNode;
    return body.contains(document.activeElement) ||
      !!(anchor && body.contains(anchor) && !selection.isCollapsed) ||
      body.matches(':hover');
  }
  function apply(html, force) {
    // Keine unnötigen DOM-Neuaufbauten: Screenreader-Leseposition erhalten.
    if (html === body.innerHTML) { pendingHtml = null; return; }
    if (readingTable() || (!force && paused)) { pendingHtml = html; return; }

    const previousX = window.scrollX;
    const previousY = window.scrollY;

    body.innerHTML = html;

    window.scrollTo({
      top: previousY,
      left: previousX,
      behavior: 'instant'
    });

    pendingHtml = null;
  }
  async function refresh(force = false) {
    if (busy || (document.hidden && !force) || (paused && !force)) return;
    busy = true;
    try {
      const params = new URLSearchParams({action: 'tmc_refresh_queue', nonce: cfg.nonce});
      const response = await fetch(cfg.url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: params.toString(), cache: 'no-store'});
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const data = await response.json();
      if (!data.success || typeof data.data?.html !== 'string') throw new Error('Ungültige Antwort');
      apply(data.data.html, force);
      if (status && !force && !paused) status.textContent = 'Automatische Aktualisierung alle 20 Sekunden aktiv.';
    } catch (error) {
      if (status) status.textContent = 'Aktualisierung fehlgeschlagen – bitte Seite neu laden.';
    } finally { busy = false; }
  }
  toggle.addEventListener('click', () => {
    paused = !paused;
    toggle.textContent = paused ? 'Automatische Aktualisierung fortsetzen' : 'Automatische Aktualisierung pausieren';
    toggle.setAttribute('aria-pressed', String(paused));
    if (status) status.textContent = paused ? 'Automatische Aktualisierung pausiert.' : 'Automatische Aktualisierung alle 20 Sekunden aktiv.';
    if (!paused) refresh();
  });
  manual.addEventListener('click', () => {
    refresh(true).then(() => { if (status && !readingTable()) status.textContent = 'Warteschlange geprüft. Aktualisierung abgeschlossen.'; });
  });
  window.setInterval(() => refresh(), 20000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
