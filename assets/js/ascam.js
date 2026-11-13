/* AS CAM — ziyaretçi etkileşimleri (bağımlılıksız) */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const isReduced = () => reduceMotion.matches;
  const pad = (n) => String(n).padStart(2, '0');

  /* ---------- Header: kaydırma durumu ---------- */
  const hdr = $('[data-hdr]');
  if (hdr) {
    let lastY = window.scrollY;
    let ticking = false;
    const onScroll = () => {
      const y = window.scrollY;
      hdr.classList.toggle('is-scrolled', y > 24);
      const megaOpen = !$('[data-mega]')?.hidden;
      const navOpen = document.body.classList.contains('nav-open');
      hdr.classList.toggle('is-hidden', y > 420 && y > lastY + 4 && !megaOpen && !navOpen);
      if (y < lastY - 4) hdr.classList.remove('is-hidden');
      lastY = y;
      ticking = false;
    };
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    onScroll();
  }

  /* ---------- Mega menü (ürün grupları) ---------- */
  const mega = $('[data-mega]');
  const megaToggle = $('[data-mega-toggle]');
  if (mega && megaToggle) {
    const item = megaToggle.closest('.hdr__item');
    let closeTimer = 0;
    const setOpen = (open) => {
      clearTimeout(closeTimer);
      mega.hidden = !open;
      megaToggle.setAttribute('aria-expanded', String(open));
    };
    megaToggle.addEventListener('click', () => setOpen(mega.hidden));
    const hoverCapable = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (hoverCapable) {
      [item, mega].forEach((el) => {
        el.addEventListener('mouseenter', () => setOpen(true));
        el.addEventListener('mouseleave', () => { closeTimer = setTimeout(() => setOpen(false), 180); });
      });
    }
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !mega.hidden) { setOpen(false); megaToggle.focus(); }
    });
    document.addEventListener('click', (e) => {
      if (!mega.hidden && !mega.contains(e.target) && !item.contains(e.target)) setOpen(false);
    });
    mega.addEventListener('focusout', (e) => {
      if (!mega.contains(e.relatedTarget) && !item.contains(e.relatedTarget)) setOpen(false);
    });
  }

  /* ---------- Mobil menü ---------- */
  const mnav = $('[data-mnav]');
  const mnavToggle = $('[data-mnav-toggle]');
  if (mnav && mnavToggle) {
    const labelOpen = mnavToggle.getAttribute('aria-label');
    const setNav = (open) => {
      mnav.hidden = !open;
      mnavToggle.setAttribute('aria-expanded', String(open));
      document.body.classList.toggle('nav-open', open);
      mnavToggle.setAttribute('aria-label', open ? (document.documentElement.lang.startsWith('en') ? 'Close menu' : 'Menüyü kapat') : labelOpen);
      if (open) { const first = mnav.querySelector('a'); first && first.focus({ preventScroll: true }); }
    };
    mnavToggle.addEventListener('click', () => setNav(mnav.hidden));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !mnav.hidden) { setNav(false); mnavToggle.focus(); }
      if (e.key === 'Tab' && !mnav.hidden) {
        const focusables = [mnavToggle, ...$$('a, button', mnav)];
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    mnav.addEventListener('click', (e) => { if (e.target.closest('a')) setNav(false); });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (m) => { if (m.matches) setNav(false); });
  }

  /* ---------- Hero: cam panellerde hafif paralaks ---------- */
  const hero = $('[data-hero]');
  const panes = $('[data-hero-panes]');
  if (hero && panes && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    hero.addEventListener('pointermove', (e) => {
      if (isReduced()) return;
      const r = hero.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - 0.5;
      panes.style.setProperty('--px', (x * -24).toFixed(1) + 'px');
    });
  }

  /* ---------- İmza bölümü: numaralı keşif alanı ---------- */
  const explorer = $('[data-explorer]');
  if (explorer) {
    const tabs = $$('[data-explorer-tab]', explorer);
    const panels = $$('[data-explorer-panel]', explorer);
    const sweep = $('[data-explorer-sweep]', explorer);
    const current = $('[data-explorer-current]', explorer);
    const stage = $('.explore__stage', explorer);
    let active = 0;
    let hoverTimer = 0;

    const activate = (idx, { focus = false } = {}) => {
      idx = (idx + tabs.length) % tabs.length;
      if (idx === active) { if (focus) tabs[idx].focus(); return; }
      const prev = panels[active];
      const next = panels[idx];
      active = idx;
      tabs.forEach((t, i) => {
        const sel = i === idx;
        t.setAttribute('aria-selected', String(sel));
        t.tabIndex = sel ? 0 : -1;
      });
      if (focus) tabs[idx].focus();
      if (current) current.textContent = pad(idx + 1);

      if (isReduced()) {
        prev.hidden = true; prev.classList.remove('is-active');
        next.hidden = false; next.classList.add('is-active');
      } else {
        prev.classList.remove('is-active');
        prev.classList.add('is-leaving');
        next.hidden = false;
        next.classList.add('is-active', 'is-entering');
        if (sweep) { sweep.classList.remove('is-run'); void sweep.offsetWidth; sweep.classList.add('is-run'); }
        setTimeout(() => {
          prev.classList.remove('is-leaving');
          if (!prev.classList.contains('is-active')) prev.hidden = true;
          next.classList.remove('is-entering');
        }, 900);
      }
      // yatay sekme şeridinde seçili olanı görünür tut (mobil)
      const list = tabs[idx].parentElement;
      if (list && list.scrollWidth > list.clientWidth) {
        list.scrollTo({ left: tabs[idx].offsetLeft - 16, behavior: isReduced() ? 'auto' : 'smooth' });
      }
    };

    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => activate(i));
      tab.addEventListener('focus', () => { if (tab.matches(':focus-visible')) activate(i); });
      tab.addEventListener('mouseenter', () => {
        if (!window.matchMedia('(hover: hover)').matches) return;
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => activate(i), 140);
      });
      tab.addEventListener('mouseleave', () => clearTimeout(hoverTimer));
      tab.addEventListener('keydown', (e) => {
        const k = e.key;
        if (k === 'ArrowDown' || k === 'ArrowRight') { e.preventDefault(); activate(active + 1, { focus: true }); }
        else if (k === 'ArrowUp' || k === 'ArrowLeft') { e.preventDefault(); activate(active - 1, { focus: true }); }
        else if (k === 'Home') { e.preventDefault(); activate(0, { focus: true }); }
        else if (k === 'End') { e.preventDefault(); activate(tabs.length - 1, { focus: true }); }
        else if (k === 'Enter') {
          const link = panels[active].querySelector('a');
          if (link) { e.preventDefault(); link.click(); }
        }
      });
    });

    // Dokunmatik kaydırma (görsel üzerinde yatay hareket)
    if (stage) {
      let sx = 0, sy = 0, tracking = false;
      stage.addEventListener('touchstart', (e) => { const t = e.touches[0]; sx = t.clientX; sy = t.clientY; tracking = true; }, { passive: true });
      stage.addEventListener('touchend', (e) => {
        if (!tracking) return; tracking = false;
        const t = e.changedTouches[0];
        const dx = t.clientX - sx, dy = t.clientY - sy;
        if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.4) activate(active + (dx < 0 ? 1 : -1));
      }, { passive: true });
    }
  }

  /* ---------- Galeri: daha fazla göster + ışık kutusu ---------- */
  const lb = $('[data-lightbox]');
  const lbState = { items: [], idx: 0, title: '', opener: null };
  const lbImg = lb && $('[data-lb-img]', lb);
  const lbCap = lb && $('[data-lb-cap]', lb);
  const lbCount = lb && $('[data-lb-count]', lb);
  const lbTitle = lb && $('[data-lb-title]', lb);

  const lbShow = (i) => {
    const n = lbState.items.length;
    lbState.idx = (i + n) % n;
    const it = lbState.items[lbState.idx];
    lbImg.classList.remove('is-swap');
    lbImg.src = it.src;
    lbImg.alt = it.alt;
    if (!isReduced()) { void lbImg.offsetWidth; lbImg.classList.add('is-swap'); }
    lbCap.textContent = it.alt;
    lbCount.textContent = `${pad(lbState.idx + 1)} / ${pad(n)}`;
    // komşu görselleri önceden yükle
    [1, -1].forEach((d) => { const p = lbState.items[(lbState.idx + d + n) % n]; if (p) { const im = new Image(); im.src = p.src; } });
  };
  const lbOpen = (gallery, i, opener) => {
    if (!lb || typeof lb.showModal !== 'function') {
      const it = $$('[data-gal-open] img', gallery)[i];
      if (it) window.open(it.dataset.full || it.src, '_blank', 'noopener');
      return;
    }
    lbState.items = $$('[data-gal-open] img', gallery).map((im) => ({ src: im.dataset.full || im.currentSrc || im.src, alt: im.alt }));
    lbState.title = gallery.dataset.galleryTitle || '';
    lbState.opener = opener;
    lbTitle.textContent = lbState.title;
    lbShow(i);
    lb.showModal();
    document.body.style.overflow = 'hidden';
  };
  if (lb) {
    $('[data-lb-close]', lb).addEventListener('click', () => lb.close());
    $('[data-lb-prev]', lb).addEventListener('click', () => lbShow(lbState.idx - 1));
    $('[data-lb-next]', lb).addEventListener('click', () => lbShow(lbState.idx + 1));
    lb.addEventListener('close', () => { document.body.style.overflow = ''; lbState.opener && lbState.opener.focus(); });
    lb.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { e.preventDefault(); lbShow(lbState.idx + 1); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); lbShow(lbState.idx - 1); }
    });
    lb.addEventListener('click', (e) => { if (e.target === lb || e.target.classList.contains('lb__stage')) lb.close(); });
    let sx = 0, sy = 0;
    lb.addEventListener('touchstart', (e) => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
    lb.addEventListener('touchend', (e) => {
      const dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) lbShow(lbState.idx + (dx < 0 ? 1 : -1));
      else if (dy > 90 && Math.abs(dy) > Math.abs(dx) * 1.5) lb.close();
    }, { passive: true });
  }

  $$('[data-gallery]').forEach((gallery) => {
    gallery.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-gal-open]');
      if (btn) lbOpen(gallery, Number(btn.dataset.galOpen), btn);
    });
    const more = $('[data-gal-more]', gallery);
    if (more) {
      const step = Number(gallery.dataset.initial) || 24;
      const counter = $('[data-gal-count]', gallery);
      const items = $$('.gal__item', gallery);
      more.addEventListener('click', () => {
        const hidden = items.filter((li) => li.hidden);
        const firstNew = hidden[0];
        hidden.slice(0, step).forEach((li) => { li.hidden = false; });
        const shown = items.filter((li) => !li.hidden).length;
        if (counter) counter.textContent = `${shown} / ${items.length}`;
        if (shown >= items.length) more.parentElement.remove();
        firstNew && firstNew.querySelector('button')?.focus({ preventScroll: true });
      });
    }
  });

  /* ---------- İletişim formu (AJAX, JS yoksa normal POST) ---------- */
  const form = $('[data-contact-form]');
  if (form) {
    const status = $('[data-form-status]', form);
    const submit = form.querySelector('button[type="submit"]');
    const label = submit.innerHTML;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      let firstInvalid = null;
      $$('input, select, textarea', form).forEach((f) => {
        if (f.name === 'website') return;
        const ok = f.checkValidity();
        f.setAttribute('aria-invalid', ok ? 'false' : 'true');
        if (!ok && !firstInvalid) firstInvalid = f;
      });
      if (firstInvalid) { firstInvalid.focus(); firstInvalid.reportValidity && firstInvalid.reportValidity(); return; }
      submit.disabled = true;
      submit.textContent = submit.dataset.labelSending || '…';
      status.className = 'form__status';
      status.textContent = '';
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin' });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
          status.classList.add('is-ok');
          status.textContent = data.message || '✓';
          form.reset();
          $$('[aria-invalid]', form).forEach((f) => f.removeAttribute('aria-invalid'));
        } else {
          status.classList.add('is-err');
          status.textContent = data.message || data.error || 'Hata';
        }
      } catch (_) {
        status.classList.add('is-err');
        status.textContent = document.documentElement.lang.startsWith('en') ? 'Connection problem. Please try again.' : 'Bağlantı sorunu. Lütfen tekrar deneyin.';
      } finally {
        submit.disabled = false;
        submit.innerHTML = label;
      }
    });
  }

  /* ---------- Kaydırınca görünme ---------- */
  const reveals = $$('.reveal');
  if (reveals.length) {
    if (isReduced() || !('IntersectionObserver' in window)) {
      reveals.forEach((el) => el.classList.add('is-in'));
    } else {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
      reveals.forEach((el) => io.observe(el));
    }
  }
})();
