// Repeat: small progressive enhancements. Every page works without JavaScript except the camera scanner.
(() => {
  // Vibration only works after the person has touched the page.
  const buzz = (pattern) => navigator.userActivation?.hasBeenActive && navigator.vibrate?.(pattern);
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

  // Top bar gets a backdrop once the page scrolls.
  const topbar = $('.topbar');
  if (topbar) {
    const onScroll = () => topbar.classList.toggle('scrolled', scrollY > 8);
    onScroll();
    addEventListener('scroll', onScroll, { passive: true });
  }

  // Reveal on scroll, staggered within each group.
  const reveals = $$('.reveal');
  if ('IntersectionObserver' in window && !reduced) {
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (!en.isIntersecting) return;
      const siblings = [...en.target.parentElement.children].filter((n) => n.classList.contains('reveal'));
      en.target.style.transitionDelay = `${Math.min(siblings.indexOf(en.target), 6) * 70}ms`;
      en.target.classList.add('in');
      io.unobserve(en.target);
    }), { rootMargin: '0px 0px -6% 0px' });
    reveals.forEach((n) => io.observe(n));
  } else {
    reveals.forEach((n) => n.classList.add('in'));
  }

  // QR codes (qrcode-generator, loaded where needed).
  const drawQr = () => {
    if (!window.qrcode) return false;
    $$('[data-qr]').forEach((box) => {
      const qr = window.qrcode(0, 'M');
      qr.addData(box.dataset.qr);
      qr.make();
      box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    });
    return true;
  };
  if ($('[data-qr]') && !drawQr()) addEventListener('load', drawQr);

  // Copy link and download QR (share page).
  $('[data-copy]')?.addEventListener('click', async (e) => {
    const input = $('[data-copy-source]');
    try { await navigator.clipboard.writeText(input.value); } catch { input.select(); document.execCommand('copy'); }
    const btn = e.currentTarget;
    const label = btn.innerHTML;
    btn.innerHTML = 'Copiado';
    setTimeout(() => { btn.innerHTML = label; }, 1600);
  });
  $('[data-download-qr]')?.addEventListener('click', (e) => {
    const svg = $('.qr-box svg');
    if (!svg) return;
    const img = new Image();
    const data = new XMLSerializer().serializeToString(svg);
    img.onload = () => {
      const size = 1200;
      const c = document.createElement('canvas');
      c.width = c.height = size + 120;
      const ctx = c.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, c.width, c.height);
      ctx.imageSmoothingEnabled = false;
      ctx.drawImage(img, 60, 60, size, size);
      const a = document.createElement('a');
      a.href = c.toDataURL('image/png');
      a.download = e.currentTarget.dataset.downloadQr;
      a.click();
    };
    img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(data);
  });

  // ---- Card editor: live preview -------------------------------------------------------------
  const form = $('[data-card-form]');
  const card = $('.preview [data-card]');
  if (form && card) {
    const set = (name, value) => {
      if (name === 'name') {
        $('[data-card-name]', card).textContent = value || 'Tu negocio';
        const logo = $('[data-card-logo]', card);
        if (!logo.querySelector('img')) logo.textContent = (value || 'T').trim().charAt(0).toUpperCase();
      }
      if (name === 'reward') $('[data-card-reward]', card).textContent = value || 'Tu premio';
      if (name === 'card_bg') card.style.setProperty('--bg', value);
      if (name === 'card_accent') card.style.setProperty('--accent', value);
      if (name === 'card_fg') card.style.setProperty('--fg', value);
      if (name === 'stamps_required' || name === 'welcome_stamps') renderStamps();
    };
    const renderStamps = () => {
      const req = Number(form.stamps_required.value);
      const filled = form.dataset.preview === 'welcome' ? Number(form.welcome_stamps.value) : Math.max(1, Math.round(req * 0.6));
      const box = $('[data-card-stamps]', card);
      box.style.setProperty('--cols', req <= 6 ? req : req <= 10 ? Math.ceil(req / 2) : Math.ceil(req / 3));
      card.style.setProperty('--cols', box.style.getPropertyValue('--cols'));
      box.innerHTML = Array.from({ length: req }, (_, i) => `<span class="stamp${i < filled ? ' on' : ''}">${i < filled ? CHECK : ''}</span>`).join('');
      $('[data-card-required]', card).textContent = req;
    };
    form.addEventListener('input', (e) => e.target.dataset.live && set(e.target.dataset.live, e.target.value));
    $$('[data-stepper]', form).forEach((st) => {
      const input = $('input', st);
      const out = $('output', st);
      st.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-step]');
        if (!btn) return;
        const v = Math.min(Number(input.dataset.max), Math.max(Number(input.dataset.min), Number(input.value) + Number(btn.dataset.step)));
        input.value = out.textContent = v;
        set(input.dataset.live, v);
      });
    });
    $$('[data-preset]', form).forEach((btn) => btn.addEventListener('click', () => {
      const [bg, accent] = btn.dataset.preset.split(',');
      form.card_bg.value = bg;
      form.card_accent.value = accent;
      form.card_fg.value = '#ffffff';
      ['card_bg', 'card_accent', 'card_fg'].forEach((k) => set(k, form[k].value));
    }));
    $('[data-logo-input]', form)?.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) return;
      $('[data-card-logo]', card).innerHTML = `<img src="${URL.createObjectURL(file)}" alt="">`;
    });
    if (form.action.endsWith('/panel/nuevo')) form.dataset.preview = 'welcome';
    renderStamps();
  }

  // ---- Chart tooltips ---------------------------------------------------------------------
  $$('[data-chart]').forEach((chart) => {
    const tip = document.createElement('div');
    tip.className = 'tip';
    tip.hidden = true;
    chart.appendChild(tip);
    chart.addEventListener('pointerover', (e) => {
      const hit = e.target.closest('.hit');
      if (!hit) return;
      const n = Number(hit.dataset.value);
      tip.innerHTML = `<b>${n} ${n === 1 ? 'sello' : 'sellos'}</b>${hit.dataset.tip}`;
      const box = chart.getBoundingClientRect();
      const r = hit.getBoundingClientRect();
      tip.style.left = `${r.left - box.left + r.width / 2}px`;
      tip.style.top = `${Math.max(0, r.top - box.top + r.height * 0.35)}px`;
      tip.hidden = false;
      hit.nextElementSibling?.classList.add('hover');
    });
    chart.addEventListener('pointerout', (e) => {
      const hit = e.target.closest('.hit');
      if (hit) hit.nextElementSibling?.classList.remove('hover');
      tip.hidden = true;
    });
  });

  // ---- Customer card page: live stamps and celebration --------------------------------------
  const page = $('[data-card-page]');
  if (page) {
    const lcard = $('[data-card]', page);
    let updated = Number(page.dataset.updated);
    let last = { stamps: $$('.stamp.on', lcard).length, rewards: Number($('[data-reward-text]')?.textContent.match(/\d+/)?.[0] || 0) };
    const apply = (s) => {
      const box = $('[data-card-stamps]', lcard);
      box.innerHTML = Array.from({ length: s.required }, (_, i) => {
        const on = i < s.stamps;
        const fresh = on && i >= last.stamps && s.stamps > last.stamps;
        return `<span class="stamp${on ? ' on' : ''}${fresh ? ' pop' : ''}">${on ? CHECK : ''}</span>`;
      }).join('');
      $('[data-bar]').style.setProperty('--w', `${Math.round((s.stamps / s.required) * 100)}%`);
      const left = s.required - s.stamps;
      $('[data-left]').textContent = left === 1 ? 'Te falta 1 sello para tu premio.' : `Te faltan ${left} sellos para tu premio.`;
      const reward = $('[data-reward]');
      reward.hidden = s.rewards === 0;
      $('[data-reward-text]').textContent = s.rewards === 1 ? 'Tienes 1 premio listo' : `Tienes ${s.rewards} premios listos`;
      if (s.rewards > last.rewards) celebrate();
      else if (s.stamps > last.stamps) buzz(40);
      last = { stamps: s.stamps, rewards: s.rewards };
    };
    const poll = async () => {
      if (document.hidden) return;
      try {
        const res = await fetch(page.dataset.stateUrl, { cache: 'no-store' });
        const s = await res.json();
        if (s.updated !== updated) {
          updated = s.updated;
          apply(s);
        }
      } catch {}
    };
    setInterval(poll, 4000);
    document.addEventListener('visibilitychange', poll);

    $('[data-save-link]')?.addEventListener('click', async () => {
      if (navigator.share) {
        try { await navigator.share({ title: document.title, url: location.href.split('?')[0] }); return; } catch {}
      }
      $('[data-save-hint]').hidden = false;
    });
    if (new URLSearchParams(location.search).has('nueva')) setTimeout(() => celebrate(true), 500);
  }

  function celebrate(small = false) {
    buzz([60, 40, 60]);
    const canvas = $('.celebrate');
    if (!canvas || reduced) return;
    const ctx = canvas.getContext('2d');
    const dpr = devicePixelRatio || 1;
    canvas.width = innerWidth * dpr;
    canvas.height = innerHeight * dpr;
    ctx.scale(dpr, dpr);
    const accent = getComputedStyle($('[data-card]')).getPropertyValue('--accent').trim() || '#8b5cf6';
    const colors = [accent, '#ffffff', '#c4b5fd', '#60a5fa', '#fde68a'];
    const parts = Array.from({ length: small ? 70 : 170 }, () => ({
      x: innerWidth / 2 + (Math.random() - 0.5) * 120, y: innerHeight * 0.35,
      vx: (Math.random() - 0.5) * 14, vy: -Math.random() * 14 - 4, s: 4 + Math.random() * 6,
      r: Math.random() * 6, vr: (Math.random() - 0.5) * 0.3, c: colors[Math.floor(Math.random() * colors.length)],
    }));
    const t0 = performance.now();
    const frame = (now) => {
      ctx.clearRect(0, 0, innerWidth, innerHeight);
      parts.forEach((p) => {
        p.vy += 0.32; p.vx *= 0.99; p.x += p.vx; p.y += p.vy; p.r += p.vr;
        ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.fillStyle = p.c;
        ctx.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2); ctx.restore();
      });
      if (now - t0 < 3500) requestAnimationFrame(frame);
      else ctx.clearRect(0, 0, innerWidth, innerHeight);
    };
    requestAnimationFrame(frame);
  }
})();
