(function () {
  'use strict';

  const cfgEl = document.getElementById('zeynart-live-studio-cfg');
  if (!cfgEl) return;

  let CFG = {};
  try {
    CFG = JSON.parse(cfgEl.textContent || '{}');
  } catch (_) {
    return;
  }

  const GOOGLE_FONTS = [
    'Manrope', 'Inter', 'Plus Jakarta Sans', 'DM Sans', 'Outfit', 'Poppins',
    'Montserrat', 'Urbanist', 'Space Grotesk', 'Syne', 'Sora', 'Rubik',
    'Nunito Sans', 'Work Sans', 'Playfair Display', 'Cormorant Garamond', 'Lora', 'Merriweather'
  ];

  const HOME_SECTION_LABELS = {
    hero: 'Üst Tanıtım (Hero Slayt)',
    intro: 'Stüdyo Tanıtım (2014)',
    services: 'Hizmet Kartları Vitrini',
    projects_showcase: 'Seçili Projeler Vitrini',
    brand_scene: 'Zeynart Marka Sahnesi',
    process: 'Nasıl Çalışıyoruz (4 Adım)',
    clients_strip: 'Referans Markalar Şeridi',
    google_reviews: 'Google Müşteri Yorumları',
    site_audit: 'Ücretsiz Site Analiz Aracı',
    latest_blog: 'Son Blog Yazıları',
    builder_blocks: 'Görsel Tasarımcı Blokları',
    cta: 'Alt Teklif Çağrı Bandı'
  };

  const STUDIO_WIDGETS = [
    { type: 'heading_text', icon: '📝', name: 'Başlık & Metin', desc: 'Üst etiket, H2 başlık, açıklama ve buton', defaults: { eyebrow: 'ZEYNART STÜDYO', title: 'Net strateji, güçlü tasarım.', muted: 'Uzun vadeli dijital değer.', copy: 'İş hedeflerinizi anlayan, hızlı açılan ve kolay yönetilen modern çözümler geliştiriyoruz.', btn_label: 'İletişime geçin', btn_url: '/iletisim/', card_style: true } },
    { type: 'hero_banner', icon: '🖼️', name: 'Kapak & Tanıtım', desc: 'Arka plan görselli büyük vitrin bloğu', defaults: { eyebrow: 'ÖZEL ÇÖZÜM', title: 'Yeni nesil dijital deneyim.', subtitle: 'Markanızın hedeflerine özel strateji, tasarım ve yazılım mimarisi.', btn_label: 'Teklif alın', btn_url: '/iletisim/', image: '/images/banners/services.webp' } },
    { type: 'rich_text', icon: '📄', name: 'Zengin İçerik Bloğu', desc: 'Serbest biçimli metin ve makale alanı', defaults: { html: '<h3>Özgün Dijital Çözümler</h3><p>Bu alandaki başlık ve paragrafları doğrudan sayfa üzerinde tıklayarak veya sol panelden düzenleyebilirsiniz.</p>' } },
    { type: 'image_showcase', icon: '📸', name: 'Görsel Vitrin', desc: 'Tam genişlik görsel ve alt etiket', defaults: { src: '/images/zeynart-identity-still-life.webp', alt: 'Zeynart Görsel Seçkisi', caption: 'ZEYNART STÜDYO SEÇKİSİ' } },
    { type: 'gallery_grid', icon: '🎞️', name: 'Çoklu Görsel Galeri', desc: 'Izgara düzeninde çoklu görsel vitrini', defaults: { images: [{ src: '/images/zeynart-identity-still-life.webp', alt: 'Görsel 1' }, { src: '/images/zeynart-emblem-gallery.webp', alt: 'Görsel 2' }, { src: '/images/zeynart-brand-art.webp', alt: 'Görsel 3' }] } },
    { type: 'stats_bar', icon: '📊', name: 'Rakamlarla Güç', desc: '4’lü istatistik ve başarı göstergesi', defaults: { items: [{ value: '2014', label: 'Kuruluş Yılı' }, { value: '62+', label: 'Mutlu Marka' }, { value: '%99,9', label: 'Kesintisiz Uptime' }, { value: '7/24', label: 'Teknik Destek' }] } },
    { type: 'service_cards', icon: '⚡', name: 'Hizmet Kartları', desc: 'Canlı hizmet vitrini ızgarası', defaults: { limit: 6 } },
    { type: 'project_showcase', icon: '🚀', name: 'Proje Vitrini', desc: 'Seçili ürün ve vaka kartları', defaults: {} },
    { type: 'process_steps', icon: '🧭', name: 'Süreç Adımları', desc: 'Numaralı çalışma adımları dizisi', defaults: { steps: [] } },
    { type: 'pricing_packages', icon: '💎', name: 'Paket & Fiyat Kartları', desc: 'Karşılaştırmalı paket kutuları', defaults: { packages: [] } },
    { type: 'faq_accordion', icon: '❓', name: 'Sıkça Sorulan Sorular', desc: 'Açılır-kapanır soru cevap akordeonu', defaults: { heading: 'Sıkça sorulan sorular.', items: [{ q: 'Proje süreci nasıl ilerliyor?', a: 'Keşif, tasarım, yazılım geliştirme ve test aşamalarını şeffaf takvimle yönetiyoruz.' }, { q: 'Yayın sonrası destek var mı?', a: 'Evet, sunucu izleme, yedekleme ve güncelleme desteğimiz kesintisiz sürer.' }] } },
    { type: 'client_logos', icon: '🤝', name: 'Referans Logoları', desc: 'Çalışılan markaların logo ızgarası', defaults: { limit: 12 } },
    { type: 'reviews_grid', icon: '⭐', name: 'Müşteri Yorumları', desc: 'Doğrulanmış müşteri geri bildirimleri', defaults: {} },
    { type: 'cta_banner', icon: '🎯', name: 'Teklif Çağrı Bandı', desc: 'Büyük aksiyon ve dönüşüm alanı', defaults: { eyebrow: 'BİR FİKRİNİZ Mİ VAR?', line1: 'Birlikte', line2: 'hayata', accent: 'geçirelim.', btn_label: 'Projenizi anlatın', btn_url: '/iletisim/' } },
    { type: 'contact_form_embed', icon: '✉️', name: 'Canlı İletişim Formu', desc: 'Sayfa içi proje talep formu', defaults: {} },
    { type: 'spacer_divider', icon: '➖', name: 'Boşluk & Ayırıcı Çizgi', desc: 'Bölümler arası dikey mesafe', defaults: { height: 48, show_line: true } },
    { type: 'custom_html', icon: '🧩', name: 'Özel HTML Bloğu', desc: 'Güvenli özel bileşen veya tablo', defaults: { html: '<div class="zb-card"><h3>Özel Bölüm</h3><p>Özel HTML içeriğinizi buraya ekleyebilirsiniz.</p></div>' } }
  ];

  const S = {
    open: false,
    initialized: false,
    dirty: false,
    tab: 'inspect', // 'inspect' | 'widgets' | 'style'
    device: 'desktop',
    newRowLayout: '1',
    store: null,
    post: null,
    media: [],
    selectedEl: null,
    selectedOriginalText: '',
    selectedOriginalSrc: '',
    activeBuilderSecIdx: -1,
    activeClientIdx: -1,
    clientSearch: ''
  };

  function esc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function resolveUrl(p) {
    const s = String(p || '');
    if (!s || /^https?:\/\/|^mailto:|^tel:|^#/i.test(s)) return s;
    const base = String(CFG.basePath || '').replace(/\/$/, '');
    return base + (s.startsWith('/') ? s : '/' + s);
  }

  function stripBasePath(p) {
    const s = String(p || '').split('?')[0].split('#')[0];
    const base = String(CFG.basePath || '').replace(/\/$/, '');
    if (base && s.startsWith(base + '/')) return s.slice(base.length);
    try {
      const u = new URL(s, window.location.origin);
      if (u.origin === window.location.origin) {
        return base && u.pathname.startsWith(base + '/') ? u.pathname.slice(base.length) : u.pathname;
      }
    } catch (_) {}
    return s;
  }

  function resolvePreviewUrl(p, explicitMtime) {
    const raw = String(p || '');
    if (!raw || /^https?:\/\/|^data:/i.test(raw)) return raw;
    if (raw.includes('?')) return resolveUrl(raw);
    const clean = stripBasePath(raw);
    let mt = Number(explicitMtime || 0);
    if (!mt && Array.isArray(S.media)) {
      const found = S.media.find(x => x && x.url === clean);
      if (found && found.mtime) mt = Number(found.mtime);
    }
    const resolved = resolveUrl(clean);
    return mt > 0 ? `${resolved}?v=${mt}` : resolved;
  }

  function showToast(msg, isError) {
    document.querySelectorAll('.zvs-toast').forEach(t => t.remove());
    const el = document.createElement('div');
    el.className = 'zvs-toast' + (isError ? ' error' : '');
    el.textContent = (isError ? '⚠ ' : '✓ ') + msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3400);
  }

  function encodeStudioPayloadB64(obj) {
    const bytes = new TextEncoder().encode(JSON.stringify(obj || {}));
    let bin = '';
    const step = 0x8000;
    for (let i = 0; i < bytes.length; i += step) {
      bin += String.fromCharCode.apply(null, bytes.subarray(i, i + step));
    }
    return btoa(bin);
  }

  async function studioApi(action, payload) {
    const bodyParams = new URLSearchParams();
    bodyParams.set('csrf_token', CFG.csrfToken || '');
    bodyParams.set('_zb64', encodeStudioPayloadB64(payload || {}));
    const res = await fetch(CFG.apiUrl + '?action=' + encodeURIComponent(action), {
      method: 'POST',
      cache: 'no-store',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-Token': CFG.csrfToken
      },
      body: bodyParams.toString()
    });
    const rawText = await res.text();
    let json = null;
    try {
      json = JSON.parse(rawText);
    } catch (_) {
      throw new Error(`Sunucu yanıtı okunamadı (HTTP ${res.status}).`);
    }
    if (!json.ok) {
      throw new Error(json.error || 'İşlem başarısız oldu.');
    }
    if (json && json.store && typeof json.store === 'object') {
      const toDict = v => (v && typeof v === 'object' && !Array.isArray(v)) ? v : {};
      ['page_titles', 'page_slugs', 'page_states', 'page_menu_labels', 'page_typography', 'legal_pages', 'core_page_blocks', 'page_live_patches'].forEach(dk => {
        json.store[dk] = toDict(json.store[dk]);
      });
    }
    return json;
  }

  let savedBadgeTimer = null;

  function markDirty() {
    S.dirty = true;
    const btn = document.getElementById('zvsSaveBtn');
    if (btn) {
      if (savedBadgeTimer) { clearTimeout(savedBadgeTimer); savedBadgeTimer = null; }
      btn.classList.remove('is-saved');
      if (!btn.disabled) btn.textContent = '✓ Kaydet & Yayınla';
      btn.classList.add('is-dirty');
    }
  }

  /* ==================== BUILDER SECTIONS ACCESSOR PER PAGE ==================== */
  function getPageBuilderSections() {
    if (!S.store) return [];
    const k = CFG.pageKey || 'anasayfa';
    if (k.startsWith('service:')) {
      const srv = (S.store.services || []).find(x => x.slug === k.slice(8));
      if (!srv) return [];
      srv.builder_blocks = Array.isArray(srv.builder_blocks) ? srv.builder_blocks : [];
      return srv.builder_blocks;
    }
    if (k.startsWith('project:')) {
      const prj = (S.store.projects || []).find(x => x.slug === k.slice(8));
      if (!prj) return [];
      prj.builder_blocks = Array.isArray(prj.builder_blocks) ? prj.builder_blocks : [];
      return prj.builder_blocks;
    }
    if (k.startsWith('custom:')) {
      const cp = (S.store.custom_pages || []).find(x => x.id === k.slice(7));
      if (!cp) return [];
      cp.sections = Array.isArray(cp.sections) ? cp.sections : [];
      return cp.sections;
    }
    const coreKey = k.startsWith('legal:') ? k.slice(6) : k;
    S.store.core_page_blocks = S.store.core_page_blocks || {};
    S.store.core_page_blocks[coreKey] = Array.isArray(S.store.core_page_blocks[coreKey]) ? S.store.core_page_blocks[coreKey] : [];
    return S.store.core_page_blocks[coreKey];
  }

  function setPageBuilderSections(nextArr) {
    if (!S.store) return;
    const k = CFG.pageKey || 'anasayfa';
    if (k.startsWith('service:')) {
      const srv = (S.store.services || []).find(x => x.slug === k.slice(8));
      if (srv) srv.builder_blocks = nextArr;
      return;
    }
    if (k.startsWith('project:')) {
      const prj = (S.store.projects || []).find(x => x.slug === k.slice(8));
      if (prj) prj.builder_blocks = nextArr;
      return;
    }
    if (k.startsWith('custom:')) {
      const cp = (S.store.custom_pages || []).find(x => x.id === k.slice(7));
      if (cp) cp.sections = nextArr;
      return;
    }
    const coreKey = k.startsWith('legal:') ? k.slice(6) : k;
    S.store.core_page_blocks = S.store.core_page_blocks || {};
    S.store.core_page_blocks[coreKey] = nextArr;
  }

  /* ==================== SMART STORE + DOM PATCH PERSISTENCE ==================== */
  function replaceExactStringInObject(obj, oldStr, newStr) {
    if (!obj || typeof obj !== 'object' || !oldStr || oldStr === newStr) return false;
    let matched = false;
    Object.keys(obj).forEach(k => {
      if (k === 'id' || k === 'slug' || k === 'layout_type' || k === 'visual_type' || k === 'status') return;
      const v = obj[k];
      if (typeof v === 'string') {
        if (v.trim() === oldStr.trim()) {
          obj[k] = newStr;
          matched = true;
        }
      } else if (Array.isArray(v)) {
        v.forEach((item, idx) => {
          if (typeof item === 'string' && item.trim() === oldStr.trim()) {
            v[idx] = newStr;
            matched = true;
          } else if (item && typeof item === 'object') {
            if (replaceExactStringInObject(item, oldStr, newStr)) matched = true;
          }
        });
      } else if (v && typeof v === 'object') {
        if (replaceExactStringInObject(v, oldStr, newStr)) matched = true;
      }
    });
    return matched;
  }

  function getActiveStoreTargets() {
    if (!S.store) return [];
    const k = CFG.pageKey || 'anasayfa';
    const targets = [];
    if (k === 'anasayfa') targets.push(S.store.home);
    else if (k === 'hakkimizda') targets.push(S.store.about);
    else if (k === 'hizmetler') targets.push(S.store.services_page, S.store.services, S.store.home?.process);
    else if (k.startsWith('service:')) targets.push((S.store.services || []).find(x => x.slug === k.slice(8)));
    else if (k === 'projeler') targets.push(S.store.projects_page, S.store.projects);
    else if (k.startsWith('project:')) targets.push((S.store.projects || []).find(x => x.slug === k.slice(8)));
    else if (k === 'blog') targets.push(S.store.blog_settings);
    else if (k === 'iletisim') targets.push(S.store.contact, S.store.site);
    else if (k.startsWith('legal:')) targets.push(S.store.legal_pages?.[k.slice(6)]);
    else if (k.startsWith('custom:')) targets.push((S.store.custom_pages || []).find(x => x.id === k.slice(7)));
    targets.push(S.store.cta_global, S.store.menus?.header, S.store.menus?.footer, S.store.site);
    return targets.filter(Boolean);
  }

  function logicalChildren(el) {
    const out = [];
    if (!el || !el.children) return out;
    for (let i = 0; i < el.children.length; i++) {
      const ch = el.children[i];
      if (ch.classList.contains('zvs-sec-bar') || ch.classList.contains('zb-canvas-empty') || ch.classList.contains('zvs-add-client-card') || ch.classList.contains('zvs-client-card-actions')) continue;
      if (ch.classList.contains('zvs-home-sec-wrap')) {
        const sub = logicalChildren(ch);
        for (let j = 0; j < sub.length; j++) out.push(sub[j]);
      } else {
        out.push(ch);
      }
    }
    return out;
  }

  function setSmartElementText(el, txt) {
    const leadDecor = (el.firstElementChild && el.firstElementChild.textContent === '' && (el.firstElementChild.tagName === 'SPAN' || el.firstElementChild.tagName === 'I'))
      ? el.firstElementChild.cloneNode(true) : null;
    const trailSvg = (el.lastElementChild && el.lastElementChild.tagName && el.lastElementChild.tagName.toUpperCase() === 'SVG')
      ? el.lastElementChild.cloneNode(true) : null;
    el.textContent = txt;
    if (leadDecor) el.insertBefore(leadDecor, el.firstChild);
    if (trailSvg) {
      el.appendChild(document.createTextNode(' '));
      el.appendChild(trailSvg);
    }
  }

  function computeDomPath(el) {
    const headerEl = document.querySelector('header.site-header');
    const footerEl = document.querySelector('footer.footer');
    const mainEl = document.getElementById('main');
    const inHeader = headerEl && headerEl.contains(el);
    const inFooter = footerEl && footerEl.contains(el);
    const rootEl = inHeader ? headerEl : (inFooter ? footerEl : mainEl);
    if (!rootEl || !rootEl.contains(el) || el === rootEl) return null;
    // Skip elements inside dynamic .zb-canvas or store-backed .client-logo-grid
    if (el.closest('[data-zvs-builder-canvas], .client-logo-grid')) return null;

    const path = [];
    let cur = el;
    while (cur && cur !== rootEl) {
      let parent = cur.parentElement;
      while (parent && parent.classList.contains('zvs-home-sec-wrap')) {
        parent = parent.parentElement;
      }
      if (!parent) return null;
      const children = logicalChildren(parent);
      const idx = children.indexOf(cur);
      if (idx < 0) return null;
      path.unshift(idx);
      cur = parent;
    }
    return { root: inHeader ? 'header' : (inFooter ? 'footer' : 'main'), path };
  }

  function upsertLivePatch(el, changes) {
    if (!S.store) return;
    const loc = computeDomPath(el);
    if (!loc) return;
    const k = CFG.pageKey || 'anasayfa';
    S.store.page_live_patches = S.store.page_live_patches || {};
    const list = Array.isArray(S.store.page_live_patches[k]) ? S.store.page_live_patches[k] : [];
    const sig = loc.root + ':' + loc.path.join('.');
    let entry = list.find(x => (x.root + ':' + (x.path || []).join('.')) === sig);
    if (!entry) {
      entry = { root: loc.root, path: loc.path };
      list.push(entry);
    }
    Object.keys(changes).forEach(ck => {
      if (ck === 'style') {
        entry.style = Object.assign({}, entry.style || {}, changes.style);
      } else {
        entry[ck] = changes[ck];
      }
    });
    S.store.page_live_patches[k] = list;
  }

  function applyTextChange(el, newText) {
    const cleanNew = String(newText ?? '').trim();
    if (S.post && CFG.postSlug) {
      if (el.matches('.journal-article-header h1, .article-page h1')) S.post.title = cleanNew;
      else if (el.matches('.journal-article-lead, .article-lead')) S.post.excerpt = cleanNew;
      const proseWrap = el.closest('.journal-prose-html');
      if (proseWrap) {
        S.post.content_html = proseWrap.innerHTML;
        markDirty();
        return;
      }
    }
    // Check custom page rich body
    if ((CFG.pageKey || '').startsWith('custom:')) {
      const richWrap = el.closest('.page-rich-prose');
      if (richWrap) {
        const cp = (S.store.custom_pages || []).find(x => x.id === CFG.pageKey.slice(7));
        if (cp) cp.content_html = richWrap.innerHTML;
        markDirty();
        return;
      }
    }

    // Try direct store match first
    let matchedInStore = false;
    if (S.selectedOriginalText) {
      getActiveStoreTargets().forEach(tgt => {
        if (replaceExactStringInObject(tgt, S.selectedOriginalText, cleanNew)) {
          matchedInStore = true;
        }
      });
      // Also check builder blocks if inside .zb-section
      const zbSec = el.closest('.zb-section');
      if (zbSec) {
        const bIdx = Number(zbSec.getAttribute('data-zb-idx'));
        const bSecs = getPageBuilderSections();
        if (bSecs[bIdx]) {
          replaceExactStringInObject(bSecs[bIdx], S.selectedOriginalText, cleanNew);
          matchedInStore = true;
        }
      }
    }
    S.selectedOriginalText = cleanNew;

    if (!matchedInStore) {
      upsertLivePatch(el, { text: cleanNew });
    }
    markDirty();
  }

  function applyImageChange(imgEl, newRelUrl, newAlt, explicitMtime) {
    const cleanRel = stripBasePath(newRelUrl);
    const resolved = resolvePreviewUrl(cleanRel, explicitMtime || Date.now());
    imgEl.setAttribute('src', resolved);
    if (typeof newAlt === 'string') imgEl.setAttribute('alt', newAlt);

    if (S.post && CFG.postSlug && imgEl.closest('.journal-article-cover')) {
      const prevImg = stripBasePath(S.post.image || '');
      S.post.image = cleanRel;
      if (typeof newAlt === 'string') S.post.image_alt = newAlt;
      S.post.seo = (S.post.seo && typeof S.post.seo === 'object') ? S.post.seo : {};
      if (!S.post.seo.og_image || stripBasePath(S.post.seo.og_image) === prevImg) {
        S.post.seo.og_image = cleanRel;
      }
      markDirty();
      return;
    }

    let matched = false;
    if (S.selectedOriginalSrc) {
      getActiveStoreTargets().forEach(tgt => {
        if (replaceExactStringInObject(tgt, S.selectedOriginalSrc, cleanRel)) matched = true;
      });
      const zbSec = imgEl.closest('.zb-section');
      if (zbSec) {
        const bIdx = Number(zbSec.getAttribute('data-zb-idx'));
        const bSecs = getPageBuilderSections();
        if (bSecs[bIdx] && replaceExactStringInObject(bSecs[bIdx], S.selectedOriginalSrc, cleanRel)) {
          matched = true;
        }
      }
    }
    S.selectedOriginalSrc = cleanRel;
    if (!matched) {
      upsertLivePatch(imgEl, { src: resolveUrl(cleanRel), alt: typeof newAlt === 'string' ? newAlt : (imgEl.getAttribute('alt') || '') });
    }
    markDirty();
  }

  /* ==================== SECTION OVERLAY BARS (HOMEPAGE & BUILDER) ==================== */
  async function refreshBuilderCanvasDom() {
    let canvas = document.querySelector('[data-zvs-builder-canvas]');
    if (!canvas) {
      const main = document.getElementById('main');
      if (!main) return;
      canvas = document.createElement('div');
      canvas.className = 'zb-canvas zb-canvas-empty';
      canvas.setAttribute('data-zvs-builder-canvas', '1');
      const cta = main.querySelector('.cta-section');
      if (cta && cta.parentElement === main) main.insertBefore(canvas, cta);
      else main.appendChild(canvas);
    }
    const secs = getPageBuilderSections();
    const res = await studioApi('live_render_sections', { sections: secs });
    const tmp = document.createElement('div');
    tmp.innerHTML = res.html || '<div class="zb-canvas zb-canvas-empty" data-zvs-builder-canvas="1"></div>';
    const nextCanvas = tmp.firstElementChild;
    if (nextCanvas) {
      canvas.replaceWith(nextCanvas);
    }
    decorateCanvasSections();
  }

  function decorateCanvasSections() {
    if (!S.open) return;
    // 1. Homepage sections
    document.querySelectorAll('.zvs-home-sec-wrap').forEach(wrap => {
      wrap.querySelector(':scope > .zvs-sec-bar')?.remove();
      const secKey = wrap.getAttribute('data-zvs-home-sec') || '';
      const bar = document.createElement('div');
      bar.className = 'zvs-sec-bar';
      bar.innerHTML = `
        <span class="zvs-sec-bar-label">${esc(HOME_SECTION_LABELS[secKey] || secKey)}</span>
        <button type="button" data-hsec-act="up" title="Yukarı Taşı">↑</button>
        <button type="button" data-hsec-act="down" title="Aşağı Taşı">↓</button>
        <button type="button" class="zvs-danger" data-hsec-act="hide" title="Bu Bölümü Gizle">👁 Gizle</button>
      `;
      bar.querySelectorAll('button').forEach(btn => {
        btn.onclick = ev => {
          ev.preventDefault();
          ev.stopPropagation();
          const act = btn.getAttribute('data-hsec-act');
          moveOrToggleHomeSection(secKey, act, wrap);
        };
      });
      wrap.prepend(bar);
    });

    // 2. Builder sections (.zb-section)
    const zbCanvas = document.querySelector('[data-zvs-builder-canvas]');
    if (zbCanvas) {
      if (zbCanvas.classList.contains('zb-canvas-empty')) {
        zbCanvas.onclick = () => {
          S.tab = 'widgets';
          renderDock();
        };
      }
      zbCanvas.ondragover = ev => {
        ev.preventDefault();
        zbCanvas.classList.add('zvs-drag-over');
      };
      zbCanvas.ondragleave = () => zbCanvas.classList.remove('zvs-drag-over');
      zbCanvas.ondrop = ev => {
        ev.preventDefault();
        zbCanvas.classList.remove('zvs-drag-over');
        const wType = ev.dataTransfer?.getData('text/zvs-widget');
        if (wType) addBuilderWidgetSection(wType);
      };
    }

    document.querySelectorAll('.zb-section').forEach(secEl => {
      secEl.querySelector(':scope > .zvs-sec-bar')?.remove();
      const idx = Number(secEl.getAttribute('data-zb-idx') || 0);
      if (idx === S.activeBuilderSecIdx) secEl.classList.add('zvs-active-sec');
      const bar = document.createElement('div');
      bar.className = 'zvs-sec-bar';
      bar.innerHTML = `
        <span class="zvs-sec-bar-label">Bölüm #${idx + 1}</span>
        <button type="button" data-bsec-act="edit">✏️ Düzenle</button>
        <button type="button" data-bsec-act="up" title="Yukarı Taşı">↑</button>
        <button type="button" data-bsec-act="down" title="Aşağı Taşı">↓</button>
        <button type="button" data-bsec-act="dup" title="Kopyala">⧉</button>
        <button type="button" class="zvs-danger" data-bsec-act="del" title="Sil">🗑</button>
      `;
      bar.querySelectorAll('button').forEach(btn => {
        btn.onclick = async ev => {
          ev.preventDefault();
          ev.stopPropagation();
          const act = btn.getAttribute('data-bsec-act');
          const secs = getPageBuilderSections();
          if (act === 'edit') {
            S.activeBuilderSecIdx = idx;
            S.selectedEl = null;
            S.tab = 'inspect';
            document.querySelectorAll('.zb-section').forEach(s => s.classList.remove('zvs-active-sec'));
            secEl.classList.add('zvs-active-sec');
            renderDock();
            return;
          }
          if (act === 'up' && idx > 0) {
            const t = secs[idx - 1]; secs[idx - 1] = secs[idx]; secs[idx] = t;
            S.activeBuilderSecIdx = idx - 1;
          } else if (act === 'down' && idx < secs.length - 1) {
            const t = secs[idx + 1]; secs[idx + 1] = secs[idx]; secs[idx] = t;
            S.activeBuilderSecIdx = idx + 1;
          } else if (act === 'dup') {
            const clone = JSON.parse(JSON.stringify(secs[idx]));
            clone.id = 'sec-' + Math.random().toString(36).slice(2, 9);
            secs.splice(idx + 1, 0, clone);
            S.activeBuilderSecIdx = idx + 1;
          } else if (act === 'del') {
            secs.splice(idx, 1);
            S.activeBuilderSecIdx = -1;
          }
          setPageBuilderSections(secs);
          markDirty();
          await refreshBuilderCanvasDom();
          renderDock();
        };
      });
      secEl.prepend(bar);
    });

    decorateClientLogoGrids();
  }

  function ensureAboutClients() {
    if (!S.store) return [];
    S.store.about = (S.store.about && typeof S.store.about === 'object') ? S.store.about : {};
    S.store.about.clients = Array.isArray(S.store.about.clients) ? S.store.about.clients : [];
    return S.store.about.clients;
  }

  function humanizeBrandNameFromUrl(rawUrl, fallbackAlt) {
    const cleanAlt = String(fallbackAlt || '').trim();
    if (cleanAlt && !/\.(webp|png|jpe?g|svg|avif|gif)$/i.test(cleanAlt)) return cleanAlt;
    const base = String(rawUrl || '').split('/').pop().replace(/\.[a-z0-9]+$/i, '').replace(/[-_]+/g, ' ').trim();
    return base || 'yenimarka.com';
  }

  function syncLiveClientLogoGrids() {
    if (!S.store) return;
    const clients = ensureAboutClients();
    const arrowSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>`;
    const renderCards = list => list.map(cl => `
      <a href="${esc(cl.url || '#')}" target="_blank" rel="noopener noreferrer" class="client-logo-card" aria-label="${esc((cl.name || '') + ' web sitesini aç')}">
        <img alt="${esc(cl.alt || cl.name || '')}" loading="lazy" width="400" height="220" decoding="async" src="${esc(resolveUrl(cl.image || ''))}"/>
        <span>${esc(cl.name || '')}${arrowSvg}</span>
      </a>
    `).join('');

    document.querySelectorAll('.company-clients .client-logo-grid').forEach(grid => {
      grid.innerHTML = renderCards(clients);
    });
    document.querySelectorAll('.studio-home-clients .client-logo-grid').forEach(grid => {
      grid.innerHTML = renderCards(clients.slice(0, 18));
    });

    const metaCnt = document.querySelector('.company-client-meta span:last-child');
    if (metaCnt) metaCnt.textContent = `${clients.length} marka, farklı hikâyeler`;
    const homeCnt = document.querySelector('.studio-home-clients h2 .muted');
    if (homeCnt) homeCnt.textContent = `${clients.length} marka, farklı hikâyeler`;

    if (S.open) decorateClientLogoGrids();
  }

  function openAddClientLogoPicker() {
    openStudioMediaPicker((url, alt) => {
      const clients = ensureAboutClients();
      const brandName = humanizeBrandNameFromUrl(url, alt);
      clients.unshift({
        id: 'client-' + Math.random().toString(36).slice(2, 9),
        name: brandName,
        alt: brandName,
        url: 'https://',
        image: url
      });
      clearSelectedElement();
      S.activeBuilderSecIdx = -1;
      S.activeClientIdx = 0;
      S.clientSearch = '';
      S.tab = 'inspect';
      markDirty();
      syncLiveClientLogoGrids();
      renderDock();
    }, {
      allowMultiple: true,
      title: '🤝 Yeni Müşteri Logosu Yükle veya Seç (/uploads)'
    });
  }

  function decorateClientLogoGrids() {
    if (!S.open || !S.store) return;
    document.querySelectorAll('.client-logo-grid').forEach(grid => {
      grid.querySelectorAll('.zvs-add-client-card, .zvs-client-card-actions').forEach(n => n.remove());

      const cards = Array.from(grid.querySelectorAll(':scope > .client-logo-card'));
      cards.forEach((card, idx) => {
        card.setAttribute('data-zvs-client-idx', String(idx));
        card.classList.toggle('zvs-active-client', idx === S.activeClientIdx);
        const acts = document.createElement('span');
        acts.className = 'zvs-client-card-actions';
        acts.innerHTML = `
          <button type="button" data-cl-act="edit" title="Marka Adı & Linki Düzenle">✏️</button>
          <button type="button" data-cl-act="logo" title="Logoyu Değiştir">📸</button>
          <button type="button" class="zvs-cl-del" data-cl-act="del" title="Bu Markayı Sil">✕</button>
        `;
        acts.querySelectorAll('button').forEach(b => {
          b.onclick = ev => {
            ev.preventDefault();
            ev.stopPropagation();
            const act = b.getAttribute('data-cl-act');
            const clients = ensureAboutClients();
            if (!clients[idx]) return;
            if (act === 'edit') {
              clearSelectedElement();
              S.activeBuilderSecIdx = -1;
              S.activeClientIdx = idx;
              S.tab = 'inspect';
              decorateClientLogoGrids();
              renderDock();
            } else if (act === 'logo') {
              openStudioMediaPicker((newUrl, newAlt) => {
                clients[idx].image = newUrl;
                if (!clients[idx].name || clients[idx].name === 'yenimarka.com') {
                  const g = humanizeBrandNameFromUrl(newUrl, newAlt);
                  clients[idx].name = g;
                  clients[idx].alt = g;
                }
                S.activeClientIdx = idx;
                markDirty();
                syncLiveClientLogoGrids();
                renderDock();
              }, { title: '📸 Müşteri Logosunu Değiştir' });
            } else if (act === 'del') {
              clients.splice(idx, 1);
              if (S.activeClientIdx === idx) S.activeClientIdx = -1;
              else if (S.activeClientIdx > idx) S.activeClientIdx--;
              markDirty();
              syncLiveClientLogoGrids();
              renderDock();
              showToast('Müşteri logosu listeden kaldırıldı (Kaydet ile yayınlayın).');
            }
          };
        });
        card.appendChild(acts);
      });

      const addCard = document.createElement('button');
      addCard.type = 'button';
      addCard.className = 'client-logo-card zvs-add-client-card';
      addCard.setAttribute('data-zvs-add-client', '1');
      addCard.innerHTML = `
        <i class="zvs-add-client-orb" aria-hidden="true">+</i>
        <strong>Yeni Müşteri Logosu Ekle</strong>
        <small>Bilgisayardan yükle veya seç</small>
      `;
      addCard.onclick = ev => {
        ev.preventDefault();
        ev.stopPropagation();
        openAddClientLogoPicker();
      };
      grid.prepend(addCard);
    });
  }

  function moveOrToggleHomeSection(secKey, act, wrapEl) {
    if (!S.store?.home) return;
    S.store.home.section_order = Array.isArray(S.store.home.section_order)
      ? S.store.home.section_order
      : Object.keys(HOME_SECTION_LABELS);
    S.store.home.section_visibility = S.store.home.section_visibility || {};
    const ord = S.store.home.section_order;
    const i = ord.indexOf(secKey);

    if (act === 'hide') {
      S.store.home.section_visibility[secKey] = false;
      if (wrapEl) wrapEl.style.display = 'none';
      markDirty();
      renderDock();
      showToast('Bölüm gizlendi (Sol panelden tekrar açabilirsiniz).');
      return;
    }

    const visibleWraps = Array.from(document.querySelectorAll('.zvs-home-sec-wrap')).filter(w => w.style.display !== 'none');
    const domIdx = visibleWraps.indexOf(wrapEl);
    if (act === 'up' && domIdx > 0) {
      const prevWrap = visibleWraps[domIdx - 1];
      const prevKey = prevWrap.getAttribute('data-zvs-home-sec');
      const pi = ord.indexOf(prevKey);
      if (i >= 0 && pi >= 0) { ord[pi] = secKey; ord[i] = prevKey; }
      prevWrap.parentNode.insertBefore(wrapEl, prevWrap);
      markDirty();
      renderDock();
    } else if (act === 'down' && domIdx >= 0 && domIdx < visibleWraps.length - 1) {
      const nextWrap = visibleWraps[domIdx + 1];
      const nextKey = nextWrap.getAttribute('data-zvs-home-sec');
      const ni = ord.indexOf(nextKey);
      if (i >= 0 && ni >= 0) { ord[ni] = secKey; ord[i] = nextKey; }
      nextWrap.parentNode.insertBefore(nextWrap, wrapEl);
      markDirty();
      renderDock();
    }
  }

  async function addBuilderWidgetSection(widgetType) {
    const def = STUDIO_WIDGETS.find(w => w.type === widgetType) || STUDIO_WIDGETS[0];
    const layout = S.newRowLayout || '1';
    const colCount = layout === '1' ? 1 : layout.split('-').length;
    const cols = [];
    for (let c = 0; c < colCount; c++) {
      cols.push({
        widgets: c === 0
          ? [{ id: 'w-' + Math.random().toString(36).slice(2, 8), type: def.type, props: JSON.parse(JSON.stringify(def.defaults || {})) }]
          : [{ id: 'w-' + Math.random().toString(36).slice(2, 8), type: 'heading_text', props: { eyebrow: 'SÜTUN ' + (c + 1), title: 'Başlık alanı.', muted: '', copy: 'Bu sütun içeriğini sol panelden düzenleyebilirsiniz.', btn_label: '', btn_url: '/iletisim/', card_style: true } }]
      });
    }
    const secs = getPageBuilderSections();
    secs.push({
      id: 'sec-' + Math.random().toString(36).slice(2, 9),
      layout,
      style: { padding_y: 56, gap: 28, container: 'boxed', visibility: 'all', bg_color: '', bg_image: '' },
      columns: cols
    });
    setPageBuilderSections(secs);
    S.activeBuilderSecIdx = secs.length - 1;
    S.selectedEl = null;
    S.tab = 'inspect';
    markDirty();
    await refreshBuilderCanvasDom();
    renderDock();
    const addedEl = document.querySelector(`.zb-section[data-zb-idx="${S.activeBuilderSecIdx}"]`);
    if (addedEl) addedEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    showToast(`"${def.name}" bölümü sayfaya eklendi!`);
  }

  /* ==================== MEDIA UPLOADER & GALLERY MODAL ==================== */
  function openStudioMediaPicker(onSelect, opts) {
    const options = Object.assign({ allowMultiple: false, title: '📸 Görsel Seç veya Yeni Yükle (/uploads)' }, opts || {});
    document.getElementById('zvsMediaModalBackdrop')?.remove();
    const wrap = document.createElement('div');
    wrap.id = 'zvsMediaModalBackdrop';
    wrap.className = 'zvs-modal-backdrop';
    wrap.innerHTML = `
      <div class="zvs-modal">
        <div class="zvs-modal-head">
          <strong>${esc(options.title)}</strong>
          <button type="button" class="zvs-btn" id="zvsCloseMediaBtn">✕ Kapat</button>
        </div>
        <div class="zvs-modal-body">
          <div class="zvs-upload-dropzone" id="zvsUploadDropzone">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
              <input type="file" id="zvsFileInput" ${options.allowMultiple ? 'multiple' : ''} accept="image/*" style="display:none"/>
              <button type="button" class="zvs-btn zvs-btn-save" id="zvsTriggerUploadBtn">⬆️ Bilgisayardan Görsel Yükle (Otomatik WebP)</button>
              <input type="text" id="zvsManualImgUrl" class="zvs-input" style="flex:1;min-width:190px" placeholder="veya görsel adresi yapıştırın (/uploads/...)"/>
              <button type="button" class="zvs-btn" id="zvsApplyManualUrlBtn">Uygula</button>
            </div>
            <div style="font-size:11px;color:#9cb5bf;margin-top:7px">Görsel dosyasını bu alana sürükleyip bırakabilir veya yukarıdaki butonla bilgisayarınızdan seçebilirsiniz.</div>
            <div class="zvs-upload-progress" id="zvsUploadProgWrap"><div class="zvs-upload-progress-bar" id="zvsUploadProgBar"></div></div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;margin-top:12px">
            <input type="search" id="zvsMediaSearchInput" class="zvs-input" placeholder="Medya arşivinde logo veya görsel ara..."/>
          </div>
          <div class="zvs-media-grid" id="zvsMediaGrid"></div>
        </div>
      </div>
    `;
    document.body.appendChild(wrap);

    const close = () => wrap.remove();
    wrap.querySelector('#zvsCloseMediaBtn').onclick = close;
    wrap.onclick = e => { if (e.target === wrap) close(); };

    const gridEl = wrap.querySelector('#zvsMediaGrid');
    const paintMediaItems = list => {
      if (!list || list.length === 0) {
        gridEl.innerHTML = `<div style="grid-column:1/-1;padding:24px;text-align:center;color:#9cb5bf;font-size:12px">Eşleşen görsel bulunamadı. Yukarıdan yeni görsel yükleyebilirsiniz.</div>`;
        return;
      }
      gridEl.innerHTML = list.map(m => `
        <div class="zvs-media-item" data-murl="${esc(m.url)}" data-malt="${esc(m.alt || m.name || '')}" data-mmtime="${Number(m.mtime || 0)}">
          <img src="${esc(resolvePreviewUrl(m.url, m.mtime))}" alt="" loading="lazy"/>
          <span>${esc(m.name || m.url)}</span>
        </div>
      `).join('');
      gridEl.querySelectorAll('[data-murl]').forEach(card => {
        card.onclick = () => {
          onSelect(
            card.getAttribute('data-murl'),
            card.getAttribute('data-malt') || '',
            Number(card.getAttribute('data-mmtime') || 0)
          );
          close();
        };
      });
    };
    paintMediaItems(S.media || []);

    let searchTimer = null;
    const searchInput = wrap.querySelector('#zvsMediaSearchInput');
    searchInput.oninput = () => {
      clearTimeout(searchTimer);
      const q = searchInput.value.trim();
      searchTimer = setTimeout(async () => {
        if (!q) { paintMediaItems(S.media || []); return; }
        try {
          const res = await studioApi('media_list', { search: q, page: 1, per_page: 48, type_filter: 'all', sort: 'newest' });
          paintMediaItems(res.items || []);
        } catch (_) {}
      }, 220);
    };

    wrap.querySelector('#zvsApplyManualUrlBtn').onclick = () => {
      const v = wrap.querySelector('#zvsManualImgUrl').value.trim();
      if (v) { onSelect(v, ''); close(); }
    };

    const fileInput = wrap.querySelector('#zvsFileInput');
    const upBtn = wrap.querySelector('#zvsTriggerUploadBtn');
    const dropzone = wrap.querySelector('#zvsUploadDropzone');
    const progWrap = wrap.querySelector('#zvsUploadProgWrap');
    const progBar = wrap.querySelector('#zvsUploadProgBar');

    const uploadFilesNow = fileList => {
      const files = Array.from(fileList || []).filter(Boolean);
      if (!files.length) return;
      upBtn.disabled = true;
      upBtn.textContent = '⏳ Yükleniyor (%0)...';
      progWrap.classList.add('active');
      progBar.style.width = '4%';

      const fd = new FormData();
      if (files.length === 1) {
        fd.append('file', files[0]);
      } else {
        files.forEach(f => fd.append('files[]', f));
      }
      fd.append('csrf_token', CFG.csrfToken);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', CFG.apiUrl + '?action=media_upload', true);
      xhr.setRequestHeader('X-CSRF-Token', CFG.csrfToken);
      xhr.upload.onprogress = ev => {
        if (ev.lengthComputable && ev.total > 0) {
          const pct = Math.min(98, Math.max(5, Math.round((ev.loaded / ev.total) * 100)));
          progBar.style.width = pct + '%';
          upBtn.textContent = `⏳ Yükleniyor (%${pct})...`;
        }
      };
      xhr.onload = () => {
        try {
          const j = JSON.parse(xhr.responseText || '{}');
          if (!j.ok) throw new Error(j.error || 'Yükleme hatası');
          progBar.style.width = '100%';
          const uploadedList = Array.isArray(j.uploaded) && j.uploaded.length
            ? j.uploaded
            : (j.file ? [j.file] : (j.url ? [{ url: j.url, name: files[0].name }] : []));
          if (!uploadedList.length) throw new Error('Sunucudan görsel adresi alınamadı.');
          uploadedList.forEach(u => {
            if (u && u.url) {
              S.media.unshift({ url: u.url, name: u.name || u.url, alt: u.alt || '' });
            }
          });
          if (options.allowMultiple) {
            uploadedList.forEach(u => {
              if (u && u.url) onSelect(u.url, u.alt || u.name || '');
            });
          } else {
            const first = uploadedList[0];
            onSelect(first.url, first.alt || first.name || '');
          }
          showToast(uploadedList.length > 1
            ? `${uploadedList.length} görsel yüklendi ve sayfaya eklendi!`
            : 'Görsel yüklendi ve sayfaya uygulandı!');
          close();
        } catch (err) {
          showToast(err.message || 'Yükleme başarısız oldu.', true);
          upBtn.disabled = false;
          upBtn.textContent = '⬆️ Bilgisayardan Görsel Yükle (Otomatik WebP)';
          progWrap.classList.remove('active');
        }
      };
      xhr.onerror = () => {
        showToast('Ağ bağlantısı hatası oluştu.', true);
        upBtn.disabled = false;
        upBtn.textContent = '⬆️ Bilgisayardan Görsel Yükle (Otomatik WebP)';
        progWrap.classList.remove('active');
      };
      xhr.send(fd);
    };

    upBtn.onclick = () => fileInput.click();
    fileInput.onchange = () => {
      if (fileInput.files?.length) uploadFilesNow(fileInput.files);
      fileInput.value = '';
    };
    dropzone.ondragover = ev => { ev.preventDefault(); dropzone.classList.add('is-dragover'); };
    dropzone.ondragleave = () => dropzone.classList.remove('is-dragover');
    dropzone.ondrop = ev => {
      ev.preventDefault();
      dropzone.classList.remove('is-dragover');
      if (ev.dataTransfer?.files?.length) uploadFilesNow(ev.dataTransfer.files);
    };
  }

  /* ==================== LIVE GOOGLE FONT & TYPOGRAPHY ENGINE ==================== */
  function applyLivePageTypographyPreview() {
    if (!S.store) return;
    const k = CFG.pageKey || 'anasayfa';
    const globalT = S.store.menus?.typography || {};
    const pgT = (S.store.page_typography || {})[k] || {};

    const bodyFam = (pgT.body_font && pgT.body_font !== 'inherit')
      ? pgT.body_font
      : (globalT.body_font || 'Manrope');
    const headRaw = (pgT.heading_font && pgT.heading_font !== 'inherit')
      ? pgT.heading_font
      : (globalT.heading_font || 'same');
    const headFam = (headRaw === 'same' || !headRaw) ? bodyFam : headRaw;

    const bodySz = Number(pgT.base_body_size || 0) > 0 ? Number(pgT.base_body_size) : Number(globalT.base_body_size || 14);
    const headSc = Number(pgT.heading_scale || 0) > 0 ? Number(pgT.heading_scale) : Number(globalT.heading_scale || 100);
    const track = (pgT.heading_tracking && pgT.heading_tracking !== 'inherit')
      ? pgT.heading_tracking
      : (globalT.heading_tracking || 'tight');

    [bodyFam, headFam].forEach(fam => {
      if (fam && fam !== 'Manrope' && fam !== 'Inter') {
        const linkId = 'zvs-gfont-' + fam.replace(/\s+/g, '-').toLowerCase();
        if (!document.getElementById(linkId)) {
          const lnk = document.createElement('link');
          lnk.id = linkId;
          lnk.rel = 'stylesheet';
          lnk.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(fam).replace(/%20/g, '+') + ':wght@300;400;500;600;700;800&display=swap';
          document.head.appendChild(lnk);
        }
      }
    });

    const bStack = `"${bodyFam}", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif`;
    const hStack = `"${headFam}", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif`;
    const root = document.documentElement;
    root.style.setProperty('--font-manrope', bStack);
    root.style.setProperty('--default-font-family', bStack);
    root.style.setProperty('--zf-body-font', bStack);
    root.style.setProperty('--zf-heading-font', hStack);
    root.style.setProperty('--zb-body-size', bodySz + 'px');
    root.style.setProperty('--zb-scale', (bodySz / 14).toFixed(4));
    root.style.setProperty('--zh-scale', (headSc / 100).toFixed(4));
    document.body.setAttribute('data-heading-tracking', track);
  }

  /* ==================== LEFT STUDIO DOCK RENDERER ==================== */
  function renderBuilderSectionEditor(secIdx) {
    const secs = getPageBuilderSections();
    const sec = secs[secIdx];
    if (!sec) return '';
    const st = sec.style || {};

    let colsHtml = '';
    (sec.columns || []).forEach((col, cIdx) => {
      (col.widgets || []).forEach((w, wIdx) => {
        const p = w.props || {};
        const wMeta = STUDIO_WIDGETS.find(x => x.type === w.type);
        colsHtml += `<div class="zvs-card" style="background:rgba(4,10,14,.75);margin-top:8px">
          <h4>${esc(wMeta ? (wMeta.icon + ' ' + wMeta.name) : w.type)} (Sütun ${cIdx + 1})</h4>`;

        if (w.type === 'heading_text') {
          colsHtml += `
            <div class="zvs-field"><label>Üst Etiket</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:eyebrow" value="${esc(p.eyebrow || '')}"/></div>
            <div class="zvs-field"><label>Ana Başlık (H2)</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:title" value="${esc(p.title || '')}"/></div>
            <div class="zvs-field"><label>Vurgulu / Soluk Alt Satır</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:muted" value="${esc(p.muted || '')}"/></div>
            <div class="zvs-field"><label>Açıklama Metni</label><textarea class="zvs-textarea" data-wp="${cIdx}:${wIdx}:copy">${esc(p.copy || '')}</textarea></div>
            <div class="zvs-field"><label>Buton Yazısı (Boşsa gizlenir)</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:btn_label" value="${esc(p.btn_label || '')}"/></div>
            <div class="zvs-field"><label>Buton Linki</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:btn_url" value="${esc(p.btn_url || '')}"/></div>
          `;
        } else if (w.type === 'hero_banner') {
          colsHtml += `
            <div class="zvs-field"><label>Üst Etiket</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:eyebrow" value="${esc(p.eyebrow || '')}"/></div>
            <div class="zvs-field"><label>Vitrin Başlığı</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:title" value="${esc(p.title || '')}"/></div>
            <div class="zvs-field"><label>Alt Açıklama</label><textarea class="zvs-textarea" data-wp="${cIdx}:${wIdx}:subtitle">${esc(p.subtitle || '')}</textarea></div>
            <div class="zvs-field"><label>Buton Metni &amp; Linki</label>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:btn_label" value="${esc(p.btn_label || '')}"/>
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:btn_url" value="${esc(p.btn_url || '')}"/>
              </div>
            </div>
            <div class="zvs-field"><label>Arka Plan Görseli</label>
              <div style="display:flex;gap:6px">
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:image" value="${esc(p.image || '')}"/>
                <button type="button" class="zvs-btn" data-wp-pick="${cIdx}:${wIdx}:image">Seç</button>
              </div>
            </div>
          `;
        } else if (w.type === 'rich_text' || w.type === 'custom_html') {
          colsHtml += `<div class="zvs-field"><label>İçerik (HTML)</label><textarea class="zvs-textarea" style="min-height:110px" data-wp="${cIdx}:${wIdx}:html">${esc(p.html || '')}</textarea></div>`;
        } else if (w.type === 'image_showcase') {
          colsHtml += `
            <div class="zvs-field"><label>Görsel Adresi</label>
              <div style="display:flex;gap:6px">
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:src" value="${esc(p.src || '')}"/>
                <button type="button" class="zvs-btn" data-wp-pick="${cIdx}:${wIdx}:src">Seç</button>
              </div>
            </div>
            <div class="zvs-field"><label>Alt Etiket (SEO)</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:alt" value="${esc(p.alt || '')}"/></div>
            <div class="zvs-field"><label>Görsel Alt Yazısı</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:caption" value="${esc(p.caption || '')}"/></div>
          `;
        } else if (w.type === 'cta_banner') {
          colsHtml += `
            <div class="zvs-field"><label>Üst Etiket</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:eyebrow" value="${esc(p.eyebrow || '')}"/></div>
            <div class="zvs-field"><label>1. Satır / 2. Satır / Vurgu</label>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:5px">
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:line1" value="${esc(p.line1 || '')}"/>
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:line2" value="${esc(p.line2 || '')}"/>
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:accent" value="${esc(p.accent || '')}"/>
              </div>
            </div>
            <div class="zvs-field"><label>Buton Yazısı &amp; Link</label>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:btn_label" value="${esc(p.btn_label || '')}"/>
                <input class="zvs-input" data-wp="${cIdx}:${wIdx}:btn_url" value="${esc(p.btn_url || '')}"/>
              </div>
            </div>
          `;
        } else if (w.type === 'spacer_divider') {
          colsHtml += `<div class="zvs-field"><label>Boşluk Yüksekliği (px)</label><input type="number" class="zvs-input" data-wp-num="${cIdx}:${wIdx}:height" value="${Number(p.height || 48)}"/></div>`;
        } else if (w.type === 'service_cards' || w.type === 'client_logos') {
          colsHtml += `<div class="zvs-field"><label>Gösterilecek Adet</label><input type="number" class="zvs-input" data-wp-num="${cIdx}:${wIdx}:limit" value="${Number(p.limit || 6)}"/></div>`;
        } else if (w.type === 'faq_accordion') {
          colsHtml += `<div class="zvs-field"><label>SSS Başlığı</label><input class="zvs-input" data-wp="${cIdx}:${wIdx}:heading" value="${esc(p.heading || '')}"/></div>`;
        }
        colsHtml += `</div>`;
      });
    });

    return `
      <div class="zvs-card" style="border-color:rgba(45,212,191,.45)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <h4 style="margin:0;color:#5eead4">🧩 Bölüm #${secIdx + 1} Ayarları</h4>
          <button type="button" class="zvs-btn" id="zvsCloseSecEditBtn" style="padding:3px 8px;font-size:11px">Kapat</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
          <div class="zvs-field" style="margin:0">
            <label>Dikey Boşluk (${Number(st.padding_y ?? 56)}px)</label>
            <input type="range" min="0" max="160" step="4" id="zvsSecPy" value="${Number(st.padding_y ?? 56)}"/>
          </div>
          <div class="zvs-field" style="margin:0">
            <label>Genişlik</label>
            <select id="zvsSecCont" class="zvs-select">
              <option value="boxed" ${(st.container || 'boxed') === 'boxed' ? 'selected' : ''}>Kutu (Container)</option>
              <option value="full" ${st.container === 'full' ? 'selected' : ''}>Tam Genişlik</option>
            </select>
          </div>
        </div>
        <div class="zvs-field">
          <label>Arka Plan Rengi (İsteğe Bağlı, Örn: #0b1920)</label>
          <input id="zvsSecBg" class="zvs-input" placeholder="Şeffaf" value="${esc(st.bg_color || '')}"/>
        </div>
        ${colsHtml}
      </div>
    `;
  }

  function renderDock() {
    const bodyEl = document.getElementById('zvsDockBody');
    if (!bodyEl || !S.store) return;

    document.querySelectorAll('.zvs-tab').forEach(tb => {
      tb.classList.toggle('active', tb.getAttribute('data-zvs-tab') === S.tab);
    });

    if (S.tab === 'inspect') {
      let selHtml = '';
      if (S.selectedEl) {
        const el = S.selectedEl;
        const isImg = el.tagName === 'IMG';
        const linkAnchor = el.tagName === 'A' ? el : el.closest('a');
        if (isImg) {
          const cleanSelSrc = stripBasePath(el.getAttribute('src') || '');
          const canStampUpload = cleanSelSrc.startsWith('/uploads/') && /\.(webp|jpe?g|png)$/i.test(cleanSelSrc);
          const canvasHref = resolveUrl('/admin/?view=media&canvas_url=' + encodeURIComponent(cleanSelSrc));
          selHtml = `
            <div class="zvs-card" style="border-color:rgba(245,158,11,.5)">
              <h4>🖼️ Seçili Görsel Düzenleyici</h4>
              <div style="border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,.12);margin-bottom:10px;background:#03080b;max-height:150px">
                <img id="zvsDockSelImgPrev" src="${esc(el.getAttribute('src') || '')}" alt="" style="width:100%;height:140px;object-fit:cover;display:block"/>
              </div>
              <button type="button" class="zvs-btn zvs-btn-save" id="zvsPickImgBtn" style="width:100%;margin-bottom:8px">📸 Görseli Değiştir / Yeni Yükle</button>
              ${canStampUpload ? `
                <div style="display:grid;gap:6px;margin-bottom:10px;padding:9px;border-radius:9px;background:rgba(20,184,166,.1);border:1px solid rgba(94,234,212,.3)">
                  <button type="button" class="zvs-btn" id="zvsQuickStampImgBtn" style="width:100%;border-color:rgba(94,234,212,.45);color:#99f6e4">🏷️ Tek Tıkla Marka / Filigran Damgala</button>
                  <a href="${esc(canvasHref)}" target="_blank" rel="noopener" class="zvs-btn" style="width:100%;text-align:center;text-decoration:none;box-sizing:border-box">🎨 Görsel Kanvas Editöründe Aç ↗</a>
                </div>
              ` : ''}
              <div class="zvs-field">
                <label>Görsel Alt Metni (SEO Açıklaması)</label>
                <input id="zvsImgAltInput" class="zvs-input" value="${esc(el.getAttribute('alt') || '')}"/>
              </div>
            </div>
          `;
        } else {
          const curTxt = el.textContent.trim();
          const comp = window.getComputedStyle(el);
          const curFs = Math.round(parseFloat(comp.fontSize || '16'));
          selHtml = `
            <div class="zvs-card" style="border-color:rgba(245,158,11,.5)">
              <h4>✏️ Seçili Metin &amp; Öğe (${esc(el.tagName.toLowerCase())})</h4>
              <div class="zvs-field">
                <label>Metin İçeriği (Sayfa üzerinde de yazabilirsiniz)</label>
                <textarea id="zvsSelTextInput" class="zvs-textarea">${esc(curTxt)}</textarea>
              </div>
              ${linkAnchor ? `
                <div class="zvs-field">
                  <label>🔗 Tıklama Bağlantısı (URL / tel: / mailto:)</label>
                  <input id="zvsSelHrefInput" class="zvs-input" value="${esc(linkAnchor.getAttribute('href') || '')}"/>
                </div>
              ` : ''}
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
                <div class="zvs-field" style="margin:0">
                  <label>Yazı Boyutu (${curFs}px)</label>
                  <input type="range" id="zvsSelFontSize" min="10" max="96" value="${curFs}"/>
                </div>
                <div class="zvs-field" style="margin:0">
                  <label>Özel Renk</label>
                  <input type="color" id="zvsSelColor" value="#5eead4" style="width:100%;height:31px;border:0;border-radius:6px;background:transparent;cursor:pointer"/>
                </div>
              </div>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" class="zvs-btn" data-zvs-align="left" style="flex:1">⬅ Sol</button>
                <button type="button" class="zvs-btn" data-zvs-align="center" style="flex:1">⬌ Orta</button>
                <button type="button" class="zvs-btn" data-zvs-align="right" style="flex:1">➡ Sağ</button>
                <button type="button" class="zvs-btn" id="zvsHideElBtn" style="color:#fca5a5">👁 Gizle</button>
              </div>
            </div>
          `;
        }
      } else if (S.activeBuilderSecIdx >= 0) {
        selHtml = renderBuilderSectionEditor(S.activeBuilderSecIdx);
      } else if (S.activeClientIdx >= 0 && ensureAboutClients()[S.activeClientIdx]) {
        const cl = ensureAboutClients()[S.activeClientIdx];
        const totalCl = ensureAboutClients().length;
        selHtml = `
          <div class="zvs-card" style="border-color:rgba(16,185,129,.55);background:linear-gradient(155deg,rgba(16,185,129,.12),rgba(6,16,22,.94))">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
              <h4 style="margin:0;color:#6ee7b7">🤝 Marka Logosu Düzenle (#${S.activeClientIdx + 1})</h4>
              <button type="button" class="zvs-btn" id="zvsCloseClientEditBtn" style="padding:3px 8px;font-size:11px">Kapat</button>
            </div>
            <div style="border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,.2);margin-bottom:10px;background:#f8fafc;height:94px;display:grid;place-items:center;padding:10px">
              <img src="${esc(resolveUrl(cl.image || ''))}" alt="${esc(cl.name || '')}" style="max-width:100%;max-height:74px;object-fit:contain;display:block"/>
            </div>
            <button type="button" class="zvs-btn zvs-btn-save" id="zvsPickClientLogoBtn" style="width:100%;margin-bottom:10px">📸 Logoyu Değiştir / Bilgisayardan Yükle</button>
            <div class="zvs-field">
              <label>Marka / Firma Adı</label>
              <input id="zvsClientNameInput" class="zvs-input" placeholder="Örn: ornekfirma.com" value="${esc(cl.name || '')}"/>
            </div>
            <div class="zvs-field">
              <label>🔗 Web Sitesi Linki (https://...)</label>
              <input id="zvsClientUrlInput" class="zvs-input" placeholder="https://..." value="${esc(cl.url || '')}"/>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px">
              <button type="button" class="zvs-btn" id="zvsClientMoveUpBtn" ${S.activeClientIdx <= 0 ? 'disabled' : ''} style="flex:1">↑ Öne Al</button>
              <button type="button" class="zvs-btn" id="zvsClientMoveDownBtn" ${S.activeClientIdx >= totalCl - 1 ? 'disabled' : ''} style="flex:1">↓ Sona Al</button>
              <button type="button" class="zvs-btn" id="zvsClientDeleteBtn" style="color:#fca5a5;border-color:rgba(248,113,113,.35)">🗑 Sil</button>
            </div>
            <button type="button" class="zvs-btn zvs-btn-save" id="zvsClientQuickSaveBtn" style="width:100%">✓ Değişikliği Kaydet &amp; Yayınla</button>
          </div>
        `;
      } else {
        selHtml = `
          <div class="zvs-card" style="background:linear-gradient(145deg,rgba(20,184,166,.12),rgba(8,20,26,.9));border-color:rgba(94,234,212,.3)">
            <h4>🎯 Canlı Tıkla &amp; Düzenle Modu Aktif</h4>
            <p class="zvs-hint">Sağdaki canlı sayfa üzerinde <strong>herhangi bir başlığa, paragrafa, butona veya görsele</strong> doğrudan tıklayarak anında değiştirebilirsiniz.</p>
          </div>
        `;
      }

      // Client Logo Studio Card on /hakkimizda/ (or whenever .client-logo-grid is on page)
      let clientsManagerHtml = '';
      if (CFG.pageKey === 'hakkimizda' || document.querySelector('.client-logo-grid')) {
        const clients = ensureAboutClients();
        const cq = (S.clientSearch || '').trim().toLowerCase();
        const filteredClients = clients
          .map((cl, idx) => ({ cl, idx }))
          .filter(({ cl }) => !cq || (String(cl.name || '') + ' ' + String(cl.url || '')).toLowerCase().includes(cq));
        clientsManagerHtml = `
          <div class="zvs-card" style="border-color:rgba(94,234,212,.34)">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px">
              <h4 style="margin:0;color:#5eead4">🤝 Müşteri Logoları (${clients.length})</h4>
              <button type="button" class="zvs-btn" id="zvsAddBlankClientBtn" style="padding:3px 8px;font-size:11px">+ Boş Kart</button>
            </div>
            <button type="button" class="zvs-btn zvs-btn-save" id="zvsAddClientMediaBtn" style="width:100%;margin-bottom:9px">+ Yeni Müşteri Logosu Yükle / Seç</button>
            <div class="zvs-field" style="margin-bottom:8px">
              <input type="search" id="zvsDockClientSearch" class="zvs-input" placeholder="Markalarda ara..." value="${esc(S.clientSearch || '')}"/>
            </div>
            <div class="zvs-dock-client-list">
              ${filteredClients.slice(0, 60).map(({ cl, idx }) => `
                <div class="zvs-dock-client-row ${idx === S.activeClientIdx ? 'active' : ''}" data-zvs-pick-client="${idx}">
                  <div class="zvs-dock-client-thumb"><img src="${esc(resolveUrl(cl.image || ''))}" alt="" loading="lazy"/></div>
                  <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:700;color:#ffffff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(cl.name || 'İsimsiz Marka')}</div>
                    <div style="font-size:10.5px;color:#9cb5bf;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(cl.url || '#')}</div>
                  </div>
                  <span style="font-size:11px;color:#5eead4;font-weight:700">✏️</span>
                </div>
              `).join('')}
              ${filteredClients.length === 0 ? `<div style="padding:10px;text-align:center;font-size:11.5px;color:#9cb5bf">Eşleşen marka bulunamadı.</div>` : ''}
            </div>
          </div>
        `;
      }

      // Homepage quick section visibility manager if on /
      let homeSecHtml = '';
      if (CFG.pageKey === 'anasayfa' && S.store.home) {
        const ord = S.store.home.section_order || Object.keys(HOME_SECTION_LABELS);
        const vis = S.store.home.section_visibility || {};
        homeSecHtml = `
          <div class="zvs-card">
            <h4>⇅ Ana Sayfa Bölümleri (Sıra &amp; Görünürlük)</h4>
            <div style="display:grid;gap:5px">
              ${ord.map(sk => {
                const isVis = vis[sk] !== false;
                return `<div style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;border-radius:8px;background:rgba(4,10,14,.7);font-size:11.5px">
                  <span style="color:${isVis ? '#e6f4f7' : '#64748b'}">${esc(HOME_SECTION_LABELS[sk] || sk)}</span>
                  <input type="checkbox" data-zvs-home-vis="${esc(sk)}" ${isVis ? 'checked' : ''} style="accent-color:#14b8a6;cursor:pointer"/>
                </div>`;
              }).join('')}
            </div>
          </div>
        `;
      }

      const bSecs = getPageBuilderSections();
      const blocksListHtml = `
        <div class="zvs-card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <h4 style="margin:0">🧩 Eklenen Görsel Bölümler (${bSecs.length})</h4>
            <button type="button" class="zvs-btn" id="zvsJumpAddWidgetBtn" style="padding:3px 9px;font-size:11px">+ Blok Ekle</button>
          </div>
          ${bSecs.length === 0 ? `<p class="zvs-hint">Bu sayfaya henüz ek görsel blok eklenmedi. "+ Blok Ekle" ile 17 hazır bileşenden dilediğinizi ekleyebilirsiniz.</p>` : `
            <div style="display:grid;gap:6px">
              ${bSecs.map((bs, idx) => {
                const firstType = bs.columns?.[0]?.widgets?.[0]?.type || 'heading_text';
                const wm = STUDIO_WIDGETS.find(x => x.type === firstType);
                return `<div style="display:flex;align-items:center;justify-content:space-between;padding:7px 9px;border-radius:8px;background:rgba(4,10,14,.75);border:1px solid ${idx === S.activeBuilderSecIdx ? '#2dd4bf' : 'rgba(255,255,255,.07)'};font-size:12px">
                  <span style="cursor:pointer;font-weight:600" data-zvs-open-bsec="${idx}">#${idx + 1} · ${esc(wm ? wm.name : firstType)}</span>
                  <button type="button" class="zvs-btn" data-zvs-open-bsec="${idx}" style="padding:2px 7px;font-size:11px">Düzenle</button>
                </div>`;
              }).join('')}
            </div>
          `}
        </div>
      `;

      bodyEl.innerHTML = selHtml + clientsManagerHtml + homeSecHtml + blocksListHtml;
      bindInspectTabEvents(bodyEl);
      return;
    }

    if (S.tab === 'widgets') {
      const layouts = [
        { id: '1', lbl: '1 Sütun' },
        { id: '1-1', lbl: '1/2 + 1/2' },
        { id: '1-2', lbl: '1/3 + 2/3' },
        { id: '2-1', lbl: '2/3 + 1/3' },
        { id: '1-1-1', lbl: '3 Sütun' },
        { id: '1-1-1-1', lbl: '4 Sütun' }
      ];
      bodyEl.innerHTML = `
        <div class="zvs-card">
          <h4>📐 Eklenecek Satırın Sütun Düzeni</h4>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px">
            ${layouts.map(l => `<button type="button" class="zvs-btn" data-zvs-layout="${l.id}" style="${S.newRowLayout === l.id ? 'border-color:#2dd4bf;background:rgba(45,212,191,.16);color:#5eead4' : ''}">${l.lbl}</button>`).join('')}
          </div>
        </div>
        <div class="zvs-card">
          <h4>➕ Hazır Görsel Bileşenler (Tıklayın veya Sayfaya Sürükleyin)</h4>
          <div class="zvs-widget-grid">
            ${STUDIO_WIDGETS.map(w => `
              <button type="button" class="zvs-widget-tile" draggable="true" data-zvs-add-widget="${esc(w.type)}">
                <strong>${w.icon} ${esc(w.name)}</strong>
                <span>${esc(w.desc)}</span>
              </button>
            `).join('')}
          </div>
        </div>
      `;
      bodyEl.querySelectorAll('[data-zvs-layout]').forEach(btn => {
        btn.onclick = () => {
          S.newRowLayout = btn.getAttribute('data-zvs-layout');
          renderDock();
        };
      });
      bodyEl.querySelectorAll('[data-zvs-add-widget]').forEach(tile => {
        const wt = tile.getAttribute('data-zvs-add-widget');
        tile.onclick = () => addBuilderWidgetSection(wt);
        tile.ondragstart = ev => {
          ev.dataTransfer?.setData('text/zvs-widget', wt);
        };
      });
      return;
    }

    if (S.tab === 'style') {
      const k = CFG.pageKey || 'anasayfa';
      const globalT = S.store.menus?.typography || {};
      S.store.page_typography = S.store.page_typography || {};
      const pt = S.store.page_typography[k] || {
        body_font: 'inherit', heading_font: 'inherit', base_body_size: 0, heading_scale: 0, heading_tracking: 'inherit'
      };
      const effBodySz = pt.base_body_size > 0 ? pt.base_body_size : Number(globalT.base_body_size || 14);
      const effHeadSc = pt.heading_scale > 0 ? pt.heading_scale : Number(globalT.heading_scale || 100);

      // Page SEO object
      S.store.seo = S.store.seo || {};
      S.store.seo.pages = S.store.seo.pages || {};
      const seoObj = S.post
        ? (S.post.seo || S.post)
        : (k.startsWith('service:')
            ? ((S.store.services || []).find(x => x.slug === k.slice(8))?.seo || {})
            : (k.startsWith('project:')
                ? ((S.store.projects || []).find(x => x.slug === k.slice(8))?.seo || {})
                : (S.store.seo.pages[k] || {})));

      bodyEl.innerHTML = `
        <div class="zvs-card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <h4 style="margin:0">🔤 Canlı Sayfa Font &amp; Boyut Stüdyosu</h4>
            <button type="button" class="zvs-btn" id="zvsResetTypoBtn" style="padding:2px 8px;font-size:10.5px">Genel Ayara Sıfırla</button>
          </div>
          <div class="zvs-field">
            <label>Sayfa Yazı Tipi (Google Fonts)</label>
            <select id="zvsBodyFont" class="zvs-select">
              <option value="inherit" ${(pt.body_font || 'inherit') === 'inherit' ? 'selected' : ''}>🔗 Site Geneli (${esc(globalT.body_font || 'Manrope')})</option>
              ${GOOGLE_FONTS.map(f => `<option value="${esc(f)}" ${pt.body_font === f ? 'selected' : ''}>${esc(f)}</option>`).join('')}
            </select>
          </div>
          <div class="zvs-field">
            <label>Başlık Yazı Tipi (H1 / H2 / H3)</label>
            <select id="zvsHeadFont" class="zvs-select">
              <option value="inherit" ${(pt.heading_font || 'inherit') === 'inherit' ? 'selected' : ''}>🔗 Site Genel Başlık Fontu</option>
              <option value="same" ${pt.heading_font === 'same' ? 'selected' : ''}>Metin Fontuyla Aynı</option>
              ${GOOGLE_FONTS.map(f => `<option value="${esc(f)}" ${pt.heading_font === f ? 'selected' : ''}>${esc(f)}</option>`).join('')}
            </select>
          </div>
          <div class="zvs-field">
            <label>Metin Yazı Boyutu: <strong id="zvsBodySzVal" style="color:#5eead4">${effBodySz} px</strong></label>
            <input type="range" id="zvsBodySzRange" min="12" max="20" step="0.5" value="${effBodySz}"/>
          </div>
          <div class="zvs-field">
            <label>Başlık Boyutu Ölçeği: <strong id="zvsHeadScVal" style="color:#5eead4">%${effHeadSc}</strong></label>
            <input type="range" id="zvsHeadScRange" min="75" max="140" step="5" value="${effHeadSc}"/>
          </div>
          <div class="zvs-field">
            <label>Başlık Harf Aralığı</label>
            <select id="zvsTrackSel" class="zvs-select">
              <option value="inherit" ${(pt.heading_tracking || 'inherit') === 'inherit' ? 'selected' : ''}>Site Geneli</option>
              <option value="tight" ${pt.heading_tracking === 'tight' ? 'selected' : ''}>Sıkı &amp; Modern</option>
              <option value="balanced" ${pt.heading_tracking === 'balanced' ? 'selected' : ''}>Dengeli Stüdyo</option>
              <option value="normal" ${pt.heading_tracking === 'normal' ? 'selected' : ''}>Normal &amp; Ferah (0px)</option>
            </select>
          </div>
        </div>

        <div class="zvs-card">
          <h4>🔍 Hızlı Sayfa SEO Ayarları</h4>
          <div class="zvs-field">
            <label>SEO Başlığı (&lt;title&gt;)</label>
            <input id="zvsSeoTitle" class="zvs-input" value="${esc(seoObj.meta_title || '')}" placeholder="${esc(document.title)}"/>
          </div>
          <div class="zvs-field">
            <label>Meta Açıklaması (Google Özeti)</label>
            <textarea id="zvsSeoDesc" class="zvs-textarea">${esc(seoObj.meta_desc || '')}</textarea>
          </div>
        </div>
      `;

      const ensurePt = () => {
        S.store.page_typography[k] = S.store.page_typography[k] || {
          body_font: 'inherit', custom_body_font: '', heading_font: 'inherit', custom_heading_font: '', base_body_size: 0, heading_scale: 0, heading_tracking: 'inherit'
        };
        return S.store.page_typography[k];
      };

      bodyEl.querySelector('#zvsBodyFont').onchange = e => {
        ensurePt().body_font = e.target.value;
        applyLivePageTypographyPreview();
        markDirty();
      };
      bodyEl.querySelector('#zvsHeadFont').onchange = e => {
        ensurePt().heading_font = e.target.value;
        applyLivePageTypographyPreview();
        markDirty();
      };
      bodyEl.querySelector('#zvsBodySzRange').oninput = e => {
        const v = Number(e.target.value);
        bodyEl.querySelector('#zvsBodySzVal').textContent = v + ' px';
        ensurePt().base_body_size = v;
        applyLivePageTypographyPreview();
        markDirty();
      };
      bodyEl.querySelector('#zvsHeadScRange').oninput = e => {
        const v = Number(e.target.value);
        bodyEl.querySelector('#zvsHeadScVal').textContent = '%' + v;
        ensurePt().heading_scale = v;
        applyLivePageTypographyPreview();
        markDirty();
      };
      bodyEl.querySelector('#zvsTrackSel').onchange = e => {
        ensurePt().heading_tracking = e.target.value;
        applyLivePageTypographyPreview();
        markDirty();
      };
      bodyEl.querySelector('#zvsResetTypoBtn').onclick = () => {
        delete S.store.page_typography[k];
        applyLivePageTypographyPreview();
        markDirty();
        renderDock();
      };

      const writeSeo = () => {
        const mt = bodyEl.querySelector('#zvsSeoTitle').value.trim();
        const md = bodyEl.querySelector('#zvsSeoDesc').value.trim();
        if (S.post) {
          S.post.seo = (S.post.seo && typeof S.post.seo === 'object') ? S.post.seo : {};
          S.post.seo.meta_title = mt;
          S.post.seo.meta_desc = md;
          S.post.meta_title = mt;
          S.post.meta_desc = md;
        }
        else if (k.startsWith('service:')) {
          const srv = (S.store.services || []).find(x => x.slug === k.slice(8));
          if (srv) { srv.seo = srv.seo || {}; srv.seo.meta_title = mt; srv.seo.meta_desc = md; }
        } else if (k.startsWith('project:')) {
          const prj = (S.store.projects || []).find(x => x.slug === k.slice(8));
          if (prj) { prj.seo = prj.seo || {}; prj.seo.meta_title = mt; prj.seo.meta_desc = md; }
        } else {
          S.store.seo.pages[k] = Object.assign(S.store.seo.pages[k] || {}, { meta_title: mt, meta_desc: md });
        }
        if (mt) document.title = mt;
        markDirty();
      };
      bodyEl.querySelector('#zvsSeoTitle').oninput = writeSeo;
      bodyEl.querySelector('#zvsSeoDesc').oninput = writeSeo;
    }
  }

  function bindInspectTabEvents(bodyEl) {
    const jumpAdd = bodyEl.querySelector('#zvsJumpAddWidgetBtn');
    if (jumpAdd) jumpAdd.onclick = () => { S.tab = 'widgets'; renderDock(); };

    bodyEl.querySelectorAll('[data-zvs-open-bsec]').forEach(el => {
      el.onclick = () => {
        S.activeBuilderSecIdx = Number(el.getAttribute('data-zvs-open-bsec'));
        S.selectedEl = null;
        renderDock();
        const targetSec = document.querySelector(`.zb-section[data-zb-idx="${S.activeBuilderSecIdx}"]`);
        if (targetSec) targetSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
      };
    });

    bodyEl.querySelectorAll('[data-zvs-home-vis]').forEach(chk => {
      chk.onchange = () => {
        const sk = chk.getAttribute('data-zvs-home-vis');
        S.store.home.section_visibility = S.store.home.section_visibility || {};
        S.store.home.section_visibility[sk] = chk.checked;
        const wrap = document.querySelector(`.zvs-home-sec-wrap[data-zvs-home-sec="${sk}"]`);
        if (wrap) wrap.style.display = chk.checked ? '' : 'none';
        markDirty();
        if (chk.checked && !wrap) {
          showToast('Bölüm aktif edildi. Kaydettiğinizde sayfada görünecektir.');
        }
      };
    });

    // Selected Element bindings
    const txtIn = bodyEl.querySelector('#zvsSelTextInput');
    if (txtIn && S.selectedEl) {
      txtIn.oninput = () => {
        const el = S.selectedEl;
        setSmartElementText(el, txtIn.value);
        applyTextChange(el, txtIn.value);
      };
    }

    const hrefIn = bodyEl.querySelector('#zvsSelHrefInput');
    if (hrefIn && S.selectedEl) {
      hrefIn.oninput = () => {
        const a = S.selectedEl.tagName === 'A' ? S.selectedEl : S.selectedEl.closest('a');
        if (a) {
          a.setAttribute('href', hrefIn.value.trim());
          upsertLivePatch(S.selectedEl, { href: hrefIn.value.trim() });
          markDirty();
        }
      };
    }

    const fsIn = bodyEl.querySelector('#zvsSelFontSize');
    if (fsIn && S.selectedEl) {
      fsIn.oninput = () => {
        const px = fsIn.value + 'px';
        S.selectedEl.style.fontSize = px;
        fsIn.previousElementSibling.textContent = `Yazı Boyutu (${px})`;
        upsertLivePatch(S.selectedEl, { style: { fontSize: px } });
        markDirty();
      };
    }

    const colIn = bodyEl.querySelector('#zvsSelColor');
    if (colIn && S.selectedEl) {
      colIn.oninput = () => {
        S.selectedEl.style.color = colIn.value;
        upsertLivePatch(S.selectedEl, { style: { color: colIn.value } });
        markDirty();
      };
    }

    bodyEl.querySelectorAll('[data-zvs-align]').forEach(btn => {
      btn.onclick = () => {
        if (!S.selectedEl) return;
        const al = btn.getAttribute('data-zvs-align');
        S.selectedEl.style.textAlign = al;
        upsertLivePatch(S.selectedEl, { style: { textAlign: al } });
        markDirty();
      };
    });

    const hideBtn = bodyEl.querySelector('#zvsHideElBtn');
    if (hideBtn && S.selectedEl) {
      hideBtn.onclick = () => {
        S.selectedEl.style.display = 'none';
        upsertLivePatch(S.selectedEl, { style: { hidden: true } });
        S.selectedEl.removeAttribute('data-zvs-selected');
        S.selectedEl = null;
        markDirty();
        renderDock();
      };
    }

    const pickImgBtn = bodyEl.querySelector('#zvsPickImgBtn');
    if (pickImgBtn && S.selectedEl && S.selectedEl.tagName === 'IMG') {
      pickImgBtn.onclick = () => {
        openStudioMediaPicker((newUrl, newAlt, pickedMtime) => {
          const curAlt = bodyEl.querySelector('#zvsImgAltInput')?.value || newAlt || '';
          applyImageChange(S.selectedEl, newUrl, curAlt, pickedMtime || Date.now());
          renderDock();
        });
      };
    }

    const quickStampImgBtn = bodyEl.querySelector('#zvsQuickStampImgBtn');
    if (quickStampImgBtn && S.selectedEl && S.selectedEl.tagName === 'IMG') {
      quickStampImgBtn.onclick = async function () {
        const cleanSelSrc = stripBasePath(S.selectedEl.getAttribute('src') || '');
        if (!cleanSelSrc.startsWith('/uploads/')) return;
        const origTxt = this.textContent;
        this.disabled = true;
        this.textContent = '⏳ Filigran İşleniyor...';
        try {
          const res = await studioApi('media_apply_stamp', { url: cleanSelSrc });
          const bustUrl = res.cache_busted_url || (cleanSelSrc + '?v=' + Date.now());
          const fullBust = resolveUrl(bustUrl);
          S.selectedEl.setAttribute('src', fullBust);
          const dockPrev = bodyEl.querySelector('#zvsDockSelImgPrev');
          if (dockPrev) dockPrev.setAttribute('src', fullBust);
          if (Array.isArray(S.media)) {
            const mItem = S.media.find(x => x && x.url === cleanSelSrc);
            if (mItem) mItem.mtime = res.mtime || Math.floor(Date.now() / 1000);
          }
          showToast(res.message || 'Marka / domain filigranı görsele işlendi!');
        } catch (err) {
          showToast(err.message || 'Filigran uygulanamadı.', true);
        } finally {
          this.disabled = false;
          this.textContent = origTxt;
        }
      };
    }

    const altIn = bodyEl.querySelector('#zvsImgAltInput');
    if (altIn && S.selectedEl && S.selectedEl.tagName === 'IMG') {
      altIn.oninput = () => {
        S.selectedEl.setAttribute('alt', altIn.value);
        if (S.post && CFG.postSlug && S.selectedEl.closest('.journal-article-cover')) {
          S.post.image_alt = altIn.value;
          markDirty();
          return;
        }
        upsertLivePatch(S.selectedEl, { alt: altIn.value });
        markDirty();
      };
    }

    // Builder section editor bindings
    const closeSecBtn = bodyEl.querySelector('#zvsCloseSecEditBtn');
    if (closeSecBtn) {
      closeSecBtn.onclick = () => {
        S.activeBuilderSecIdx = -1;
        document.querySelectorAll('.zb-section').forEach(s => s.classList.remove('zvs-active-sec'));
        renderDock();
      };
    }

    let renderDebounce = null;
    const triggerSecRefresh = () => {
      markDirty();
      clearTimeout(renderDebounce);
      renderDebounce = setTimeout(() => refreshBuilderCanvasDom(), 220);
    };

    const pyRange = bodyEl.querySelector('#zvsSecPy');
    if (pyRange && S.activeBuilderSecIdx >= 0) {
      pyRange.oninput = () => {
        const secs = getPageBuilderSections();
        if (!secs[S.activeBuilderSecIdx]) return;
        secs[S.activeBuilderSecIdx].style = secs[S.activeBuilderSecIdx].style || {};
        secs[S.activeBuilderSecIdx].style.padding_y = Number(pyRange.value);
        pyRange.previousElementSibling.textContent = `Dikey Boşluk (${pyRange.value}px)`;
        triggerSecRefresh();
      };
    }
    const contSel = bodyEl.querySelector('#zvsSecCont');
    if (contSel && S.activeBuilderSecIdx >= 0) {
      contSel.onchange = () => {
        const secs = getPageBuilderSections();
        if (!secs[S.activeBuilderSecIdx]) return;
        secs[S.activeBuilderSecIdx].style = secs[S.activeBuilderSecIdx].style || {};
        secs[S.activeBuilderSecIdx].style.container = contSel.value;
        triggerSecRefresh();
      };
    }
    const bgIn = bodyEl.querySelector('#zvsSecBg');
    if (bgIn && S.activeBuilderSecIdx >= 0) {
      bgIn.oninput = () => {
        const secs = getPageBuilderSections();
        if (!secs[S.activeBuilderSecIdx]) return;
        secs[S.activeBuilderSecIdx].style = secs[S.activeBuilderSecIdx].style || {};
        secs[S.activeBuilderSecIdx].style.bg_color = bgIn.value.trim();
        triggerSecRefresh();
      };
    }

    bodyEl.querySelectorAll('[data-wp]').forEach(inp => {
      inp.oninput = () => {
        const [cIdx, wIdx, prop] = inp.getAttribute('data-wp').split(':');
        const secs = getPageBuilderSections();
        const w = secs[S.activeBuilderSecIdx]?.columns?.[Number(cIdx)]?.widgets?.[Number(wIdx)];
        if (w) {
          w.props = w.props || {};
          w.props[prop] = inp.value;
          triggerSecRefresh();
        }
      };
    });
    bodyEl.querySelectorAll('[data-wp-num]').forEach(inp => {
      inp.oninput = () => {
        const [cIdx, wIdx, prop] = inp.getAttribute('data-wp-num').split(':');
        const secs = getPageBuilderSections();
        const w = secs[S.activeBuilderSecIdx]?.columns?.[Number(cIdx)]?.widgets?.[Number(wIdx)];
        if (w) {
          w.props = w.props || {};
          w.props[prop] = Number(inp.value || 0);
          triggerSecRefresh();
        }
      };
    });
    bodyEl.querySelectorAll('[data-wp-pick]').forEach(btn => {
      btn.onclick = () => {
        const [cIdx, wIdx, prop] = btn.getAttribute('data-wp-pick').split(':');
        openStudioMediaPicker(newUrl => {
          const secs = getPageBuilderSections();
          const w = secs[S.activeBuilderSecIdx]?.columns?.[Number(cIdx)]?.widgets?.[Number(wIdx)];
          if (w) {
            w.props = w.props || {};
            w.props[prop] = newUrl;
            markDirty();
            refreshBuilderCanvasDom();
            renderDock();
          }
        });
      };
    });

    // Client Logo Studio bindings
    const addClientMediaBtn = bodyEl.querySelector('#zvsAddClientMediaBtn');
    if (addClientMediaBtn) {
      addClientMediaBtn.onclick = () => openAddClientLogoPicker();
    }
    const addBlankClientBtn = bodyEl.querySelector('#zvsAddBlankClientBtn');
    if (addBlankClientBtn) {
      addBlankClientBtn.onclick = () => {
        const clients = ensureAboutClients();
        clients.unshift({
          id: 'client-' + Math.random().toString(36).slice(2, 9),
          name: 'yenimarka.com',
          alt: 'yenimarka.com',
          url: 'https://',
          image: '/images/zeynart-logo.avif'
        });
        clearSelectedElement();
        S.activeBuilderSecIdx = -1;
        S.activeClientIdx = 0;
        S.clientSearch = '';
        markDirty();
        syncLiveClientLogoGrids();
        renderDock();
        setTimeout(() => document.getElementById('zvsClientNameInput')?.select(), 40);
      };
    }
    const dockClientSearch = bodyEl.querySelector('#zvsDockClientSearch');
    if (dockClientSearch) {
      dockClientSearch.oninput = () => {
        S.clientSearch = dockClientSearch.value;
        const pos = dockClientSearch.selectionStart || S.clientSearch.length;
        renderDock();
        const reInp = document.getElementById('zvsDockClientSearch');
        if (reInp) { reInp.focus(); reInp.setSelectionRange(pos, pos); }
      };
    }
    bodyEl.querySelectorAll('[data-zvs-pick-client]').forEach(row => {
      row.onclick = () => {
        clearSelectedElement();
        S.activeBuilderSecIdx = -1;
        S.activeClientIdx = Number(row.getAttribute('data-zvs-pick-client'));
        decorateClientLogoGrids();
        renderDock();
        const cardOnPage = document.querySelector(`.client-logo-grid .client-logo-card[data-zvs-client-idx="${S.activeClientIdx}"]`);
        if (cardOnPage) cardOnPage.scrollIntoView({ behavior: 'smooth', block: 'center' });
      };
    });
    const closeClientBtn = bodyEl.querySelector('#zvsCloseClientEditBtn');
    if (closeClientBtn) {
      closeClientBtn.onclick = () => {
        S.activeClientIdx = -1;
        decorateClientLogoGrids();
        renderDock();
      };
    }
    const pickClientLogoBtn = bodyEl.querySelector('#zvsPickClientLogoBtn');
    if (pickClientLogoBtn && S.activeClientIdx >= 0) {
      pickClientLogoBtn.onclick = () => {
        openStudioMediaPicker((newUrl, newAlt) => {
          const clients = ensureAboutClients();
          const cl = clients[S.activeClientIdx];
          if (!cl) return;
          cl.image = newUrl;
          if (!cl.name || cl.name === 'yenimarka.com') {
            const g = humanizeBrandNameFromUrl(newUrl, newAlt);
            cl.name = g;
            cl.alt = g;
          }
          markDirty();
          syncLiveClientLogoGrids();
          renderDock();
        }, { title: '📸 Marka Logosunu Seç veya Bilgisayardan Yükle' });
      };
    }
    const clNameInp = bodyEl.querySelector('#zvsClientNameInput');
    if (clNameInp && S.activeClientIdx >= 0) {
      clNameInp.oninput = () => {
        const cl = ensureAboutClients()[S.activeClientIdx];
        if (!cl) return;
        cl.name = clNameInp.value.trim();
        cl.alt = cl.name;
        markDirty();
        syncLiveClientLogoGrids();
      };
    }
    const clUrlInp = bodyEl.querySelector('#zvsClientUrlInput');
    if (clUrlInp && S.activeClientIdx >= 0) {
      clUrlInp.oninput = () => {
        const cl = ensureAboutClients()[S.activeClientIdx];
        if (!cl) return;
        cl.url = clUrlInp.value.trim();
        markDirty();
        syncLiveClientLogoGrids();
      };
    }
    const clUpBtn = bodyEl.querySelector('#zvsClientMoveUpBtn');
    if (clUpBtn && S.activeClientIdx > 0) {
      clUpBtn.onclick = () => {
        const clients = ensureAboutClients();
        const i = S.activeClientIdx;
        [clients[i - 1], clients[i]] = [clients[i], clients[i - 1]];
        S.activeClientIdx = i - 1;
        markDirty();
        syncLiveClientLogoGrids();
        renderDock();
      };
    }
    const clDownBtn = bodyEl.querySelector('#zvsClientMoveDownBtn');
    if (clDownBtn && S.activeClientIdx >= 0) {
      clDownBtn.onclick = () => {
        const clients = ensureAboutClients();
        const i = S.activeClientIdx;
        if (i < clients.length - 1) {
          [clients[i + 1], clients[i]] = [clients[i], clients[i + 1]];
          S.activeClientIdx = i + 1;
          markDirty();
          syncLiveClientLogoGrids();
          renderDock();
        }
      };
    }
    const clDelBtn = bodyEl.querySelector('#zvsClientDeleteBtn');
    if (clDelBtn && S.activeClientIdx >= 0) {
      clDelBtn.onclick = () => {
        const clients = ensureAboutClients();
        clients.splice(S.activeClientIdx, 1);
        S.activeClientIdx = -1;
        markDirty();
        syncLiveClientLogoGrids();
        renderDock();
        showToast('Müşteri logosu silindi (Kaydet ile yayınlayın).');
      };
    }
    const clQuickSaveBtn = bodyEl.querySelector('#zvsClientQuickSaveBtn');
    if (clQuickSaveBtn) {
      clQuickSaveBtn.onclick = () => saveAllStudioChanges();
    }
  }

  /* ==================== SAVE ALL CHANGES TO SERVER ==================== */
  async function saveAllStudioChanges() {
    if (!S.store) return;
    const saveBtn = document.getElementById('zvsSaveBtn');
    if (saveBtn) {
      saveBtn.disabled = true;
      saveBtn.textContent = '⏳ Kaydediliyor...';
    }
    try {
      if (S.post && CFG.postSlug) {
        await studioApi('blog_save', {
          post: S.post,
          old_slug: CFG.postSlug,
          auto_translate: false
        });
      }
      const res = await studioApi('unified_pages_save', {
        home: S.store.home,
        about: S.store.about,
        services_page: S.store.services_page,
        services: S.store.services,
        projects_page: S.store.projects_page,
        projects: S.store.projects,
        blog_settings: S.store.blog_settings,
        contact: S.store.contact,
        site_contact: {
          phone: S.store.site?.phone || '',
          phone_href: S.store.site?.phone_href || '',
          email: S.store.site?.email || '',
          address: S.store.site?.address || '',
          city: S.store.site?.city || '',
          show_contact_socials: S.store.site?.show_contact_socials !== false
        },
        cta_global: S.store.cta_global || {},
        legal_pages: S.store.legal_pages,
        custom_pages: S.store.custom_pages,
        core_page_blocks: S.store.core_page_blocks,
        page_typography: S.store.page_typography || {},
        page_live_patches: S.store.page_live_patches || {},
        seo_pages: S.store.seo?.pages || {},
        menus: S.store.menus,
        toast_message: 'Tüm görsel değişiklikler kaydedildi ve canlı yayına alındı!'
      });
      if (res.store) S.store = res.store;
      S.dirty = false;
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.classList.remove('is-dirty');
        saveBtn.classList.add('is-saved');
        saveBtn.textContent = '✓ Kaydedildi & Yayında!';
        try { saveBtn.blur(); } catch (_) {}
        if (savedBadgeTimer) clearTimeout(savedBadgeTimer);
        savedBadgeTimer = setTimeout(() => {
          const b = document.getElementById('zvsSaveBtn');
          if (b && !S.dirty) {
            b.classList.remove('is-saved');
            b.textContent = '✓ Kaydet & Yayınla';
          }
        }, 2400);
      }
      showToast('Tüm değişiklikler kaydedildi ve canlı yayına alındı!');
      return;
    } catch (err) {
      showToast(err.message, true);
    }
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.textContent = '✓ Kaydet & Yayınla';
    }
  }

  /* ==================== ON-CANVAS INTERACTION HANDLERS ==================== */
  const EDITABLE_SELECTOR = 'h1, h2, h3, h4, p, .eyebrow, .button, .play-link, .text-link, .desktop-nav a, .footer-menu-col a, .contact-channel-copy strong, .contact-channel-copy small, blockquote, summary, figcaption, li > span, img';

  function clearSelectedElement() {
    if (S.selectedEl) {
      S.selectedEl.removeAttribute('data-zvs-selected');
      if (S.selectedEl.getAttribute('contenteditable') === 'true') {
        S.selectedEl.removeAttribute('contenteditable');
      }
      S.selectedEl = null;
    }
  }

  function findEditableTarget(rawTarget) {
    if (!rawTarget || rawTarget.closest('.zvs-topbar, .zvs-dock, .zvs-modal-backdrop, .zvs-sec-bar, .zeynart-adminbar, [data-zvs-locked]')) {
      return null;
    }
    const inCanvas = rawTarget.closest('header.site-header') || rawTarget.closest('#main') || rawTarget.closest('footer.footer');
    if (!inCanvas) return null;
    if (rawTarget.tagName === 'IMG') return rawTarget;
    const directMatch = rawTarget.closest(EDITABLE_SELECTOR);
    if (directMatch && (
      document.querySelector('header.site-header')?.contains(directMatch) ||
      document.getElementById('main')?.contains(directMatch) ||
      document.querySelector('footer.footer')?.contains(directMatch)
    )) {
      return directMatch;
    }
    return null;
  }

  function bindGlobalCanvasListeners() {
    let lastHover = null;

    document.addEventListener('mouseover', ev => {
      if (!S.open) return;
      const tgt = findEditableTarget(ev.target);
      if (lastHover && lastHover !== tgt) {
        lastHover.removeAttribute('data-zvs-hover');
      }
      if (tgt && tgt !== S.selectedEl) {
        tgt.setAttribute('data-zvs-hover', '1');
        lastHover = tgt;
      }
    }, true);

    document.addEventListener('click', ev => {
      if (!S.open) return;
      if (ev.target.closest('.zvs-topbar, .zvs-dock, .zvs-modal-backdrop, .zvs-sec-bar, .zvs-add-client-card, .zvs-client-card-actions')) return;

      const clientCard = ev.target.closest('.client-logo-grid .client-logo-card[data-zvs-client-idx]');
      if (clientCard) {
        ev.preventDefault();
        ev.stopPropagation();
        clearSelectedElement();
        S.activeBuilderSecIdx = -1;
        S.activeClientIdx = Number(clientCard.getAttribute('data-zvs-client-idx'));
        S.tab = 'inspect';
        decorateClientLogoGrids();
        renderDock();
        return;
      }

      const tgt = findEditableTarget(ev.target);
      if (!tgt) return;

      // Prevent navigation on links & buttons while in visual editor
      if (ev.target.closest('a, button:not([type="button"])')) {
        ev.preventDefault();
      }
      ev.stopPropagation();

      if (S.selectedEl === tgt) return;
      clearSelectedElement();

      S.selectedEl = tgt;
      S.activeBuilderSecIdx = -1;
      S.activeClientIdx = -1;
      decorateClientLogoGrids();
      tgt.removeAttribute('data-zvs-hover');
      tgt.setAttribute('data-zvs-selected', '1');

      if (tgt.tagName === 'IMG') {
        S.selectedOriginalSrc = stripBasePath(tgt.getAttribute('src') || '');
      } else {
        S.selectedOriginalText = tgt.textContent.trim();
        tgt.setAttribute('contenteditable', 'true');
        tgt.focus();
        tgt.oninput = () => {
          const inp = document.getElementById('zvsSelTextInput');
          if (inp) inp.value = tgt.textContent.trim();
          applyTextChange(tgt, tgt.textContent);
        };
      }

      S.tab = 'inspect';
      renderDock();
    }, true);

    document.addEventListener('keydown', ev => {
      if (!S.open) return;
      if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 's') {
        ev.preventDefault();
        saveAllStudioChanges();
      } else if (ev.key === 'Escape' && S.selectedEl) {
        clearSelectedElement();
        renderDock();
      }
    });
  }

  /* ==================== OPEN / CLOSE STUDIO SHELL ==================== */
  async function openLiveStudio() {
    if (S.open) return;

    // Remove preloader if still around
    document.getElementById('zeynart-preloader')?.remove();

    if (!S.initialized) {
      try {
        const initRes = await studioApi('live_studio_init', { post_slug: CFG.postSlug || '' });
        S.store = initRes.store || {};
        S.post = initRes.post || null;
        S.media = initRes.media || [];
      } catch (err) {
        showToast('Canlı düzenleyici yüklenemedi: ' + err.message, true);
        return;
      }

      // Mount Top Bar + Left Dock
      const topBar = document.createElement('div');
      topBar.id = 'zvsTopBar';
      topBar.className = 'zvs-topbar';
      topBar.innerHTML = `
        <div class="zvs-topbar-left">
          <span class="zvs-logo-badge"><i class="zvs-logo-dot"></i> Zeynart Görsel Stüdyo</span>
          <span class="zvs-page-chip">${esc(CFG.routePath || '/')}</span>
        </div>
        <div class="zvs-topbar-center">
          <div class="zvs-device-group">
            <button type="button" class="zvs-device-btn active" data-zvs-dev="desktop">🖥️ Masaüstü</button>
            <button type="button" class="zvs-device-btn" data-zvs-dev="tablet">📱 Tablet</button>
            <button type="button" class="zvs-device-btn" data-zvs-dev="mobile">📲 Mobil</button>
          </div>
        </div>
        <div class="zvs-topbar-right">
          <button type="button" class="zvs-btn" id="zvsClearPatchesBtn" title="Sayfa içi anlık yamaları sıfırla">↺ Sıfırla</button>
          <a href="${esc(CFG.panelEditUrl || CFG.adminUrl)}" class="zvs-btn">⊞ Panelde Aç</a>
          <button type="button" class="zvs-btn zvs-btn-save" id="zvsSaveBtn">✓ Kaydet &amp; Yayınla</button>
          <button type="button" class="zvs-btn" id="zvsCloseStudioBtn">✕ Kapat</button>
        </div>
      `;

      const dock = document.createElement('aside');
      dock.id = 'zvsDock';
      dock.className = 'zvs-dock';
      dock.innerHTML = `
        <button type="button" class="zvs-collapse-handle" id="zvsCollapseBtn" title="Paneli Daralt / Aç">◀</button>
        <div class="zvs-tabs">
          <button type="button" class="zvs-tab active" data-zvs-tab="inspect">✏️ İçerik</button>
          <button type="button" class="zvs-tab" data-zvs-tab="widgets">➕ Bileşenler</button>
          <button type="button" class="zvs-tab" data-zvs-tab="style">🎨 Font &amp; SEO</button>
        </div>
        <div class="zvs-dock-body" id="zvsDockBody"></div>
      `;

      document.body.appendChild(topBar);
      document.body.appendChild(dock);

      topBar.querySelectorAll('[data-zvs-dev]').forEach(btn => {
        btn.onclick = () => {
          S.device = btn.getAttribute('data-zvs-dev');
          document.body.setAttribute('data-zvs-device', S.device);
          topBar.querySelectorAll('[data-zvs-dev]').forEach(b => b.classList.toggle('active', b === btn));
        };
      });

      topBar.querySelector('#zvsSaveBtn').onclick = saveAllStudioChanges;
      topBar.querySelector('#zvsCloseStudioBtn').onclick = closeLiveStudio;
      topBar.querySelector('#zvsClearPatchesBtn').onclick = () => {
        if (!S.store?.page_live_patches?.[CFG.pageKey]) {
          showToast('Bu sayfada sıfırlanacak ek yama bulunmuyor.');
          return;
        }
        delete S.store.page_live_patches[CFG.pageKey];
        markDirty();
        showToast('Sayfa yamaları temizlendi. Kaydedip yenileyebilirsiniz.');
      };

      const colBtn = dock.querySelector('#zvsCollapseBtn');
      colBtn.onclick = () => {
        const collapsed = document.body.classList.toggle('zvs-dock-collapsed');
        colBtn.textContent = collapsed ? '▶' : '◀';
      };

      dock.querySelectorAll('[data-zvs-tab]').forEach(tb => {
        tb.onclick = () => {
          S.tab = tb.getAttribute('data-zvs-tab');
          renderDock();
        };
      });

      bindGlobalCanvasListeners();
      S.initialized = true;
    }

    S.open = true;
    document.body.classList.add('zvs-editing');
    document.getElementById('zvsTopBar').style.display = 'flex';
    document.getElementById('zvsDock').style.display = 'flex';
    decorateCanvasSections();
    renderDock();
  }

  function closeLiveStudio() {
    clearSelectedElement();
    S.activeClientIdx = -1;
    S.open = false;
    document.body.classList.remove('zvs-editing', 'zvs-dock-collapsed');
    document.body.removeAttribute('data-zvs-device');
    const tb = document.getElementById('zvsTopBar');
    const dk = document.getElementById('zvsDock');
    if (tb) tb.style.display = 'none';
    if (dk) dk.style.display = 'none';
    document.querySelectorAll('[data-zvs-hover]').forEach(el => el.removeAttribute('data-zvs-hover'));
    document.querySelectorAll('.zvs-sec-bar, .zvs-add-client-card, .zvs-client-card-actions').forEach(el => el.remove());
    document.querySelectorAll('.zvs-active-client').forEach(el => el.classList.remove('zvs-active-client'));
  }

  const openTrigger = document.getElementById('zvsOpenStudioBtn');
  if (openTrigger) {
    openTrigger.addEventListener('click', ev => {
      ev.preventDefault();
      openLiveStudio();
    });
  }

  if (window.location.search.includes('canli_duzenle=1')) {
    try {
      const cleanUrl = window.location.pathname + window.location.hash;
      window.history.replaceState({}, '', cleanUrl);
    } catch (_) {}
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', openLiveStudio);
    } else {
      openLiveStudio();
    }
  }
})();
