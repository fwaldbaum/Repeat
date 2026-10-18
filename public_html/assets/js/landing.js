// Repeat landing page: the self-filling hero card, the phone story and the playable demo.
(() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- Hero headline: rotate the highlighted word ----------------------------------------
  const word = $('[data-words]');
  if (word && !reduced) {
    const words = word.dataset.words.split('|');
    let i = 0;
    setInterval(() => {
      if (document.hidden) return;
      word.classList.add('out');
      setTimeout(() => {
        i = (i + 1) % words.length;
        word.textContent = words[i];
        word.classList.remove('out');
      }, 350);
    }, 3200);
  }

  // ---- Hero card: tilt with the pointer ------------------------------------------------
  const tiltBox = $('[data-tilt]');
  if (tiltBox && !reduced && matchMedia('(pointer: fine)').matches) {
    const tilt = $('.tilt', tiltBox);
    tiltBox.addEventListener('pointermove', (e) => {
      const r = tiltBox.getBoundingClientRect();
      tilt.style.setProperty('--ry', `${((e.clientX - r.left) / r.width - 0.5) * 16}deg`);
      tilt.style.setProperty('--rx', `${-((e.clientY - r.top) / r.height - 0.5) * 12}deg`);
    });
    tiltBox.addEventListener('pointerleave', () => {
      tilt.style.setProperty('--ry', '0deg');
      tilt.style.setProperty('--rx', '0deg');
    });
  }

  // ---- Hero card: fills itself, one customer at a time -----------------------------------
  const live = $('[data-live-card]');
  if (live && !reduced) {
    const card = $('.lcard', live);
    const box = $('[data-card-stamps]', card);
    const req = box.children.length;
    const toast = $('[data-live-toast]');
    const text = $('[data-toast-text]', toast);
    const dot = $('[data-toast-icon]', toast);
    const names = ['Matías', 'Camila', 'Diego', 'Javiera', 'Tomás'];
    let n = Number(live.dataset.start);
    let who = 0;
    let hold = 0;
    const say = (html, gift) => {
      toast.classList.add('swap');
      setTimeout(() => {
        text.innerHTML = html;
        dot.innerHTML = $(gift ? '[data-gift-icon]' : '[data-check-icon]').innerHTML;
        toast.classList.toggle('gift', gift);
        toast.classList.remove('swap');
      }, 250);
    };
    setInterval(() => {
      if (document.hidden) return;
      if (n >= req) {
        if (hold++ < 1) return;
        hold = 0;
        n = 0;
        who = (who + 1) % names.length;
        live.classList.remove('full');
        [...box.children].forEach((s) => { s.className = 'stamp'; s.innerHTML = ''; });
        return;
      }
      const s = box.children[n++];
      s.className = 'stamp on pop';
      s.innerHTML = card.dataset.stampSvg;
      const left = req - n;
      if (left === 0) {
        live.classList.add('full');
        say(`<b>${names[who]}</b> ganó su corte gratis<br><span class="sub">Premio listo para canjear</span>`, true);
      } else {
        say(`<b>${names[who]}</b> sumó un sello<br><span class="sub">${left === 1 ? 'Le falta 1' : `Le faltan ${left}`} para su corte gratis</span>`, false);
      }
    }, 1700);
  }

  // ---- How it works: the phone shows the active step --------------------------------------
  const story = $('[data-story]');
  if (story) {
    const steps = $$('[data-step]', story);
    const screens = $$('[data-screen]', story);
    let active = 0;
    const show = (i) => {
      if (i === active) return;
      active = i;
      steps.forEach((s, j) => s.classList.toggle('on', j === i));
      screens.forEach((s, j) => s.classList.toggle('on', j === i));
    };
    const desktop = matchMedia('(min-width: 901px)');
    let timer = null;
    const autoplay = () => {
      clearInterval(timer);
      if (!desktop.matches && !reduced) timer = setInterval(() => !document.hidden && show((active + 1) % steps.length), 5200);
    };
    steps.forEach((s, i) => s.addEventListener('click', () => {
      show(i);
      autoplay();
    }));
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => entries.forEach((en) => {
        if (en.isIntersecting && desktop.matches) show(steps.indexOf(en.target));
      }), { rootMargin: '-45% 0px -45% 0px' });
      steps.forEach((s) => io.observe(s));
    }
    desktop.addEventListener?.('change', autoplay);
    autoplay();

    // Step 1: the card cycles through color presets.
    const cycleCard = $('[data-cycle-card] .lcard', story);
    const swatches = $$('.ph-swatches i', story);
    let c = 0;
    if (cycleCard && !reduced) {
      setInterval(() => {
        if (active !== 0 || document.hidden) return;
        c = (c + 1) % swatches.length;
        const [bg, accent] = swatches[c].dataset.preset.split(',');
        cycleCard.style.setProperty('--bg', bg);
        cycleCard.style.setProperty('--accent', accent);
        swatches.forEach((s, j) => s.classList.toggle('on', j === c));
      }, 1600);
    }
  }

  // ---- Playable demo -------------------------------------------------------------------
  const demo = $('[data-demo]');
  if (demo) {
    const wrap = $('[data-demo-card]', demo);
    const card = $('.lcard', wrap);
    const box = $('[data-card-stamps]', card);
    const req = box.children.length;
    const status = $('[data-demo-status]', demo);
    const btn = $('[data-demo-stamp]', demo);
    const label = $('span', btn);
    const double = $('[data-demo-double]', demo);
    let n = $$('.stamp.on', box).length;

    const render = (from = -1) => {
      const svg = card.dataset.stampSvg;
      [...box.children].forEach((s, i) => {
        const on = i < n;
        s.className = `stamp${on ? ' on' : ''}${on && from >= 0 && i >= from ? ' pop' : ''}`;
        s.innerHTML = on ? svg : '';
      });
      const left = req - n;
      wrap.classList.toggle('full', left === 0);
      status.innerHTML = left === 0
        ? '<b>Premio listo.</b> Tu cliente ganó su café gratis.'
        : left === 1 ? 'Le falta 1 sello para su café gratis.' : `Le faltan ${left} sellos para su café gratis.`;
      label.textContent = left === 0 ? 'Canjear premio' : double.checked ? 'Sumar sello doble' : 'Sumar sello';
    };

    btn.addEventListener('click', () => {
      if (n >= req) {
        n = 0;
        render();
        status.textContent = 'Premio canjeado. La tarjeta vuelve a empezar.';
        return;
      }
      const before = n;
      n = Math.min(req, n + (double.checked ? 2 : 1));
      render(before);
      wrap.classList.add('bump');
      setTimeout(() => wrap.classList.remove('bump'), 220);
      if (n === req) confetti(getComputedStyle(card).getPropertyValue('--accent').trim());
    });
    $('[data-demo-reset]', demo).addEventListener('click', () => {
      n = 0;
      render();
    });
    double.addEventListener('change', () => render());
    $('[data-demo-brand]', demo).addEventListener('change', (e) => wrap.classList.toggle('no-brand', e.target.checked));
    $$('[data-colors]', demo).forEach((b) => b.addEventListener('click', () => {
      const [bg, accent] = b.dataset.colors.split(',');
      card.style.setProperty('--bg', bg);
      card.style.setProperty('--accent', accent);
      $$('[data-colors]', demo).forEach((x) => x.classList.toggle('on', x === b));
    }));
    $$('input[name="demo_icon"]', demo).forEach((input) => input.addEventListener('change', () => {
      card.dataset.stampSvg = input.dataset.svg;
      render();
    }));
  }

  function confetti(accent) {
    const canvas = $('.confetti');
    if (!canvas || reduced) return;
    const ctx = canvas.getContext('2d');
    const dpr = devicePixelRatio || 1;
    canvas.width = innerWidth * dpr;
    canvas.height = innerHeight * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    const origin = $('[data-demo-card]').getBoundingClientRect();
    const colors = [accent || '#8b5cf6', '#ffffff', '#c4b5fd', '#60a5fa', '#fde68a'];
    const parts = Array.from({ length: 150 }, () => ({
      x: origin.left + origin.width / 2 + (Math.random() - 0.5) * 140, y: origin.top + origin.height * 0.4,
      vx: (Math.random() - 0.5) * 15, vy: -Math.random() * 14 - 4, s: 4 + Math.random() * 6,
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
      if (now - t0 < 3200) requestAnimationFrame(frame);
      else ctx.clearRect(0, 0, innerWidth, innerHeight);
    };
    requestAnimationFrame(frame);
  }
})();
