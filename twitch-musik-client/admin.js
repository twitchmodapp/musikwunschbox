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
  const body = document.getElementById('tmc-queue-body');
  const status = document.getElementById('tmc-refresh-state');
  const cfg = window.TMCAdminQueue;
  if (!body || !cfg || !cfg.url || !cfg.nonce) return;
  let busy = false;
  let pendingHtml = null;
  let paused = false;
  const controls = document.createElement('span');
  controls.style.marginLeft = '12px';
  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.className = 'button button-secondary';
  toggle.textContent = 'Automatische Aktualisierung pausieren';
  const manual = document.createElement('button');
  manual.type = 'button';
  manual.className = 'button button-secondary';
  manual.textContent = 'Jetzt aktualisieren';
  manual.style.marginLeft = '8px';
  controls.append(toggle, manual);
  if (status) {
    status.removeAttribute('aria-live'); // Screenreader nicht alle 10 Sekunden unterbrechen
    status.textContent = 'Automatische Aktualisierung alle 10 Sekunden (nur bei Änderungen).';
    status.after(controls);
  }
  function readingTable() {
    const selection = window.getSelection();
    const anchor = selection && selection.anchorNode;
    return body.contains(document.activeElement) ||
      !!(anchor && body.contains(anchor) && !selection.isCollapsed) ||
      body.matches(':hover');
  }
  function apply(html, force) {
    // Keine unnötigen DOM-Neuaufbauten: Screenreader-Leseposition erhalten.
    if (html === body.innerHTML) return;
    if (!force && (paused || readingTable())) { pendingHtml = html; return; }
    body.innerHTML = html;
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
    } catch (error) {
      if (status) status.textContent = 'Aktualisierung fehlgeschlagen – bitte Seite neu laden.';
    } finally { busy = false; }
  }
  toggle.addEventListener('click', () => {
    paused = !paused;
    toggle.textContent = paused ? 'Automatische Aktualisierung fortsetzen' : 'Automatische Aktualisierung pausieren';
    if (status) status.textContent = paused ? 'Automatische Aktualisierung pausiert.' : 'Automatische Aktualisierung alle 10 Sekunden (nur bei Änderungen).';
    if (!paused) refresh();
  });
  manual.addEventListener('click', () => refresh(true));
  window.setInterval(() => refresh(), 10000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
