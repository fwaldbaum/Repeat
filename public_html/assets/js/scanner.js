// Scanner and customer actions in the panel. QR via BarcodeDetector when the browser has it, else jsQR.
(() => {
  // Vibration only works after the person has touched the page.
  const buzz = (pattern) => navigator.userActivation?.hasBeenActive && navigator.vibrate?.(pattern);
  const root = document.querySelector('[data-scanner]');
  if (!root) return;
  const $ = (sel) => root.querySelector(sel);
  const api = root.dataset.api;
  const csrf = document.querySelector('meta[name="csrf"]').content;
  const CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
  let current = null;
  let lastEvent = null;
  let busy = false;

  const toast = (text, ok = true) => {
    document.querySelector('.toast')?.remove();
    const t = document.createElement('div');
    t.className = `toast ${ok ? 'ok' : 'err'}`;
    t.innerHTML = `<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">${ok ? '<path d="M20 6 9 17l-5-5"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>'}</svg><span></span>`;
    t.querySelector('span').textContent = text;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3200);
  };
  const initials = (name) => name.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();

  const show = (c, fresh = 0) => {
    const prev = current && current.token === c.token ? current.stamps : null;
    current = c;
    $('[data-result-empty]').hidden = true;
    $('[data-result-body]').hidden = false;
    $('[data-r-initials]').textContent = initials(c.name);
    $('[data-r-name]').textContent = c.name;
    $('[data-r-meta]').textContent = `${c.phone} · ${c.visits} ${c.visits === 1 ? 'visita' : 'visitas'} · última ${c.last_visit}`;
    const holder = $('[data-r-card]');
    if (!holder.firstElementChild) holder.appendChild(document.getElementById('card-template').content.firstElementChild.cloneNode(true));
    const card = holder.firstElementChild;
    card.style.maxWidth = '100%';
    card.querySelector('.lcard-kicker').textContent = c.name;
    card.querySelector('[data-card-stamps]').innerHTML = Array.from({ length: c.required }, (_, i) => {
      const on = i < c.stamps;
      const pop = on && prev !== null && i >= prev && c.stamps > prev;
      return `<span class="stamp${on ? ' on' : ''}${pop ? ' pop' : ''}">${on ? (card.dataset.stampSvg || CHECK) : ''}</span>`;
    }).join('');
    const reward = $('[data-r-reward]');
    reward.hidden = c.rewards === 0;
    $('[data-r-reward-text]').textContent = c.rewards === 1 ? '1 premio listo para canjear' : `${c.rewards} premios listos para canjear`;
    root.querySelector('[data-act="redeem"]').disabled = c.rewards === 0;
    if (fresh) buzz([50, 40, 50]);
  };

  const post = async (path, body) => {
    const res = await fetch(`${api}/${path}`, {
      method: 'POST',
      headers: { 'X-CSRF': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(body),
    });
    const data = await res.json().catch(() => ({ error: 'No pudimos conectar. Revisa tu internet.' }));
    if (!res.ok) throw new Error(data.error || 'Algo salió mal.');
    return data;
  };

  root.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-act]');
    if (!btn || !current || busy) return;
    busy = true;
    btn.disabled = true;
    try {
      const act = btn.dataset.act;
      if (act === 'stamp' || act === 'stamp2') {
        const n = act === 'stamp2' ? 2 : 1;
        const data = await post('sello', { token: current.token, count: n });
        lastEvent = data.event;
        show(data.customer, data.earned);
        root.querySelector('[data-act="undo"]').hidden = false;
        const what = data.added === 1 ? 'Sello sumado' : `${data.added} sellos sumados`;
        toast(data.earned
          ? `${data.customer.name} completó su tarjeta y ganó su premio`
          : `${what} a ${data.customer.name}${data.doubled ? ' (hora de sellos dobles)' : ''}`);
      } else if (act === 'redeem') {
        const data = await post('canjear', { token: current.token });
        show(data.customer);
        toast(`Premio canjeado por ${data.customer.name}`);
      } else if (act === 'undo' && lastEvent) {
        const data = await post('deshacer', { event: lastEvent });
        lastEvent = null;
        btn.hidden = true;
        show(data.customer);
        toast('Sello deshecho');
      }
    } catch (err) {
      toast(err.message, false);
    } finally {
      busy = false;
      btn.disabled = btn.dataset.act === 'redeem' && current?.rewards === 0;
    }
  });

  const preload = root.dataset.preload;
  if (preload) show(JSON.parse(preload));

  // ---- Search by name or phone ------------------------------------------------------------
  const search = $('[data-search]');
  const results = $('[data-results]');
  let timer;
  search?.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      const q = search.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }
      const res = await fetch(`${api}/buscar?q=${encodeURIComponent(q)}`);
      const { results: list = [] } = await res.json();
      results.innerHTML = list.length ? '' : '<div class="empty">Sin resultados.</div>';
      list.forEach((c) => {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'list-row';
        row.style.cssText = 'width:100%;background:none;border:0;border-top:1px solid var(--line);text-align:left;cursor:pointer;color:inherit';
        row.innerHTML = '<span class="avatar"></span><span class="grow"><span class="t" style="display:block"></span><span class="m"></span></span>';
        row.querySelector('.avatar').textContent = initials(c.name);
        row.querySelector('.t').textContent = c.name;
        row.querySelector('.m').textContent = `${c.phone} · ${c.stamps} de ${c.required} sellos`;
        row.addEventListener('click', () => { lastEvent = null; root.querySelector('[data-act="undo"]').hidden = true; show(c); });
        results.appendChild(row);
      });
    }, 220);
  });

  // ---- Camera ---------------------------------------------------------------------------------
  const startBtn = $('[data-cam-start]');
  if (!startBtn) return;
  const video = $('video');
  const errorText = $('[data-cam-error]');
  let detector = null;
  let lastScan = '';
  let lastScanAt = 0;
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d', { willReadFrequently: true });

  const found = async (text) => {
    const now = Date.now();
    if (text === lastScan && now - lastScanAt < 4000) return;
    lastScan = text;
    lastScanAt = now;
    buzz(30);
    const token = (text.match(/\/t\/([\w-]{8,32})/) || [null, text])[1];
    try {
      const res = await fetch(`${api}/cliente/${encodeURIComponent(token)}`);
      const data = await res.json();
      if (!res.ok) throw new Error(data.error);
      lastEvent = null;
      root.querySelector('[data-act="undo"]').hidden = true;
      show(data.customer);
      toast(`Tarjeta de ${data.customer.name}`);
    } catch (err) {
      toast(err.message || 'No reconocimos ese código.', false);
    }
  };

  const tick = async () => {
    if (video.readyState >= 2) {
      try {
        if (detector) {
          const codes = await detector.detect(video);
          if (codes[0]) await found(codes[0].rawValue);
        } else if (window.jsQR) {
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          ctx.drawImage(video, 0, 0);
          const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const code = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
          if (code) await found(code.data);
        }
      } catch {}
    }
    setTimeout(() => requestAnimationFrame(tick), 180);
  };

  startBtn.addEventListener('click', async () => {
    errorText.hidden = true;
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
      video.srcObject = stream;
      await video.play();
      if ('BarcodeDetector' in window) {
        try { detector = new window.BarcodeDetector({ formats: ['qr_code'] }); } catch { detector = null; }
      }
      video.hidden = false;
      root.querySelector('.frame').hidden = false;
      root.querySelector('.laser').hidden = false;
      $('[data-cam-placeholder]').hidden = true;
      tick();
    } catch (err) {
      errorText.hidden = false;
      errorText.textContent = err.name === 'NotAllowedError'
        ? 'Permite el acceso a la cámara en tu navegador, o busca al cliente por su teléfono.'
        : 'No pudimos abrir la cámara. Busca al cliente por su nombre o teléfono.';
    }
  });
})();
