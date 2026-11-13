/* ZEYNART DİJİTAL STÜDYO v4.0 — Lazy Modül: media_design */
(function () {
  'use strict';
  if (typeof window.__ZS_REGISTER_CHUNK__ !== 'function') return;
  window.__ZS_REGISTER_CHUNK__('media_design', function (CTX) {
  const { CFG, FONT_PAIRING_PRESETS, ROOT, S, api, applyLiveAdminBrandState, buildUnifiedPagesCatalog, copyTextSafe, esc, fmtBytes, getDefaultMenuTypography, humanizeAltFromFilename, rememberMediaMtimes, renderFontSelectOptions, resolveAdminFontPreview, resolvePreviewUrl, resolveUrl, runStudioUploadQueue, syncAdminUrl, toast, uid, updateSidebarBadges } = CTX;

  /* ======================== MODULE 3: MEDIA LIBRARY (/uploads & /images) ======================== */
  function isDeletableUpload(url) {
    return typeof url === 'string' && url.startsWith('/uploads/');
  }

  function syncMediaSelectionDom() {
    const gridEl = document.getElementById('mediaGridEl');
    if (gridEl) {
      const hasAny = S.media.bulkMode || S.media.selectedUrls.length > 0;
      gridEl.classList.toggle('has-bulk', hasAny);
      const selUrl = S.media.selectedItem ? S.media.selectedItem.url : null;
      const checkedSet = new Set(S.media.selectedUrls);

      gridEl.querySelectorAll('[data-media-idx]').forEach(card => {
        const idx = Number(card.getAttribute('data-media-idx'));
        const item = S.media.items[idx];
        if (!item) return;
        const isCurrent = selUrl === item.url;
        const isChecked = checkedSet.has(item.url);
        card.classList.toggle('selected', isCurrent);
        card.classList.toggle('bulk-checked', isChecked);
        const chk = card.querySelector('.zs-media-check');
        if (chk) {
          chk.textContent = isChecked ? '✓' : '';
          chk.setAttribute('aria-pressed', isChecked ? 'true' : 'false');
        }
      });
    }
    paintMediaBulkBar();
  }

  function paintMediaBulkBar() {
    const barSlot = document.getElementById('mediaBulkBarSlot');
    if (!barSlot) return;

    const deletableOnPage = S.media.items.filter(it => isDeletableUpload(it.url));
    const checkedSet = new Set(S.media.selectedUrls);
    const allPageSelected = deletableOnPage.length > 0 && deletableOnPage.every(it => checkedSet.has(it.url));
    const count = S.media.selectedUrls.length;

    let selectedBytes = 0;
    S.media.items.forEach(it => {
      if (checkedSet.has(it.url)) selectedBytes += (Number(it.size) || 0);
    });

    barSlot.innerHTML = `
      <div class="zs-media-bulk-bar ${count > 0 ? 'active-selection' : ''}">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <button type="button" class="zs-btn zs-btn-sm ${S.media.bulkMode ? 'zs-btn-primary' : ''}" id="mediaToggleBulkModeBtn">
            ${S.media.bulkMode ? '✓ Toplu Seçim Açık' : '☐ Toplu Seçim Modu'}
          </button>
          <button type="button" class="zs-btn zs-btn-sm" id="mediaSelectPageBtn" ${deletableOnPage.length === 0 ? 'disabled' : ''}>
            ${allPageSelected ? 'Sayfa Seçimini Kaldır' : `Bu Sayfadakileri Seç (${deletableOnPage.length})`}
          </button>
          ${count > 0 ? `
            <button type="button" class="zs-btn zs-btn-sm" id="mediaClearSelectionBtn">Seçimi Temizle</button>
            <span style="font-size:12.5px;color:var(--accent-bright);font-weight:700;margin-left:4px">
              ${count} görsel seçildi${selectedBytes > 0 ? ' · ' + fmtBytes(selectedBytes) : ''}
            </span>
          ` : `
            <span style="font-size:12px;color:var(--text-muted)">
              İpucu: Kartların sol üst kutucuğundan veya <strong>Shift + Tık</strong> ile çoklu seçim yapabilirsiniz.
            </span>
          `}
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          ${count > 0 ? `
            <button type="button" class="zs-btn zs-btn-danger zs-btn-sm" id="mediaBulkDeleteBtn">
              Seçilen ${count} Görseli Sil
            </button>
          ` : ''}
        </div>
      </div>
    `;

    document.getElementById('mediaToggleBulkModeBtn').onclick = () => {
      S.media.bulkMode = !S.media.bulkMode;
      syncMediaSelectionDom();
    };

    document.getElementById('mediaSelectPageBtn').onclick = () => {
      if (allPageSelected) {
        const pageSet = new Set(deletableOnPage.map(i => i.url));
        S.media.selectedUrls = S.media.selectedUrls.filter(u => !pageSet.has(u));
      } else {
        const merged = new Set(S.media.selectedUrls);
        deletableOnPage.forEach(i => merged.add(i.url));
        S.media.selectedUrls = Array.from(merged);
      }
      syncMediaSelectionDom();
    };

    const clearBtn = document.getElementById('mediaClearSelectionBtn');
    if (clearBtn) {
      clearBtn.onclick = () => {
        S.media.selectedUrls = [];
        S.media.lastClickedIdx = null;
        syncMediaSelectionDom();
      };
    }

    const bulkDelBtn = document.getElementById('mediaBulkDeleteBtn');
    if (bulkDelBtn) {
      bulkDelBtn.onclick = handleBulkDeleteSelected;
    }
  }

  function ensureLightboxModal() {
    let lb = document.getElementById('zsLightboxModal');
    if (lb) return lb;
    lb = document.createElement('div');
    lb.id = 'zsLightboxModal';
    lb.className = 'zs-lightbox-backdrop';
    document.body.appendChild(lb);

    lb.addEventListener('click', ev => {
      if (ev.target === lb) closeMediaLightbox();
    });

    document.addEventListener('keydown', ev => {
      if (!lb.classList.contains('open')) return;
      const tag = (ev.target && ev.target.tagName) ? ev.target.tagName.toLowerCase() : '';
      if (tag === 'input' || tag === 'textarea') {
        if (ev.key === 'Escape') ev.target.blur();
        return;
      }
      if (ev.key === 'Escape') {
        ev.preventDefault();
        closeMediaLightbox();
      } else if (ev.key === 'ArrowLeft') {
        ev.preventDefault();
        stepMediaLightbox(-1);
      } else if (ev.key === 'ArrowRight') {
        ev.preventDefault();
        stepMediaLightbox(1);
      }
    });

    return lb;
  }

  function closeMediaLightbox() {
    const lb = document.getElementById('zsLightboxModal');
    if (lb) {
      lb.classList.remove('open');
      lb.innerHTML = '';
    }
    S.media.selectedItem = null;
    syncMediaSelectionDom();
  }

  function stepMediaLightbox(delta) {
    if (!S.media.selectedItem || !S.media.items.length) return;
    const curIdx = S.media.items.findIndex(i => i.url === S.media.selectedItem.url);
    if (curIdx < 0) return;
    const nextIdx = curIdx + delta;
    if (nextIdx >= 0 && nextIdx < S.media.items.length) {
      openMediaLightbox(nextIdx);
    }
  }

  /* ======================== STUDIO IMAGE CANVAS & BRAND / DOMAIN TEXT OVERLAY EDITOR ======================== */
  function drawRoundedCanvasRect(ctx, x, y, w, h, r) {
    const rad = Math.max(0, Math.min(r, w / 2, h / 2));
    ctx.beginPath();
    ctx.moveTo(x + rad, y);
    ctx.arcTo(x + w, y, x + w, y + h, rad);
    ctx.arcTo(x + w, y + h, x, y + h, rad);
    ctx.arcTo(x, y + h, x, y, rad);
    ctx.arcTo(x, y, x + w, y, rad);
    ctx.closePath();
  }

  function hexToRgbaStr(hex, alpha) {
    const clean = String(hex || '#16b7c5').replace('#', '').trim();
    const full = clean.length === 3 ? clean.split('').map(c => c + c).join('') : clean;
    const num = /^[0-9a-fA-F]{6}$/.test(full) ? parseInt(full, 16) : 0x16b7c5;
    const r = (num >> 16) & 255;
    const g = (num >> 8) & 255;
    const b = num & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  function openImageCanvasStudio(item, onSaved) {
    if (!item || !item.url) return;
    const modal = document.getElementById('zsImageStudioModal');
    const body = document.getElementById('zsImgStudioBody');
    const titleEl = document.getElementById('zsImgStudioTitle');
    if (!modal || !body) return;

    closeMediaLightbox();

    const siteName = (S.store?.site?.name || 'ZEYNART').trim();
    let domainHost = 'zeynart.com';
    try {
      const u = new URL(S.store?.seo?.canonical_domain || CFG.siteUrl || window.location.origin);
      if (u.hostname && !['localhost', '127.0.0.1'].includes(u.hostname)) domainHost = u.hostname.replace(/^www\./i, '');
    } catch (_) {}
    const brandColor = S.store?.theme_brand?.primary_color || '#16b7c5';
    const canOverwrite = isDeletableUpload(item.url) && /\.(webp|jpe?g|png)$/i.test(item.url);
    const defaultHeadline = (item.alt || humanizeAltFromFilename(item.name) || siteName).trim();

    if (titleEl) {
      titleEl.textContent = `🎨 Görsel Üzerine Yazı & Marka Editörü — ${item.name}`;
    }

    const st = {
      scrimPct: 0,
      altText: defaultHeadline,
      selectedIdx: 0,
      layers: [
        {
          id: uid('lyr'),
          type: 'text',
          label: 'Marka & Domain İmzası',
          text: `${siteName} · ${domainHost}`,
          font: 'Manrope',
          weight: '800',
          sizePct: 2.8,
          color: '#ffffff',
          accent: brandColor,
          style: 'logo-glass-pill',
          opacity: 94,
          rotation: 0,
          xPct: 80,
          yPct: 91,
          bounds: null
        }
      ],
      baseImg: null,
      logoImg: null,
      dragging: false,
      dragOffsetX: 0,
      dragOffsetY: 0
    };

    body.innerHTML = `
      <div class="zs-canvas-studio-grid">
        <div>
          <div class="zs-canvas-stage-card">
            <canvas id="zsImgCanvas" class="zs-canvas-el" width="1200" height="800"></canvas>
            <div class="zs-canvas-hint-bar">
              <span>💡 <strong>İpucu:</strong> Katmanı görsel üzerinde fareyle tutup dilediğiniz yere sürükleyebilirsiniz.</span>
              <span id="zsCanvasDimInfo" style="color:var(--accent-bright);font-weight:700">Yükleniyor...</span>
            </div>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:12px;padding:12px 15px;border-radius:13px;background:rgba(6,16,22,.88);border:1px solid var(--border-subtle)">
            <div style="flex:1;min-width:220px">
              <label style="display:block;font-size:11.5px;color:var(--text-muted);margin-bottom:4px">SEO Görsel Alt Etiketi (Kaydedilecek Görsel İçin)</label>
              <input id="zsCanvasAltInp" class="zs-input" value="${esc(st.altText)}" placeholder="Görsel SEO alt açıklaması..."/>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
              ${canOverwrite
                ? `<button type="button" class="zs-btn zs-btn-primary" id="zsCanvasOverwriteBtn">✓ Mevcut Görselin Üzerine Kaydet (Sitede Güncelle)</button>
                   <button type="button" class="zs-btn zs-btn-ai" id="zsCanvasSaveCopyBtn">+ Yeni .webp Kopya Kaydet (/uploads/)</button>`
                : `<button type="button" class="zs-btn zs-btn-primary" id="zsCanvasSaveCopyBtn">✦ Yeni .webp Kopya Kaydet (/uploads/)</button>`
              }
            </div>
          </div>
        </div>

        <div class="zs-canvas-side">
          <div class="zs-card" style="padding:14px;margin:0">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
              <strong style="font-size:13px;color:#5eead4">+ Hızlı Katman Ekle</strong>
              <span style="font-size:11px;color:var(--text-dim)" id="zsLayerCountLbl">1 Katman</span>
            </div>
            <div class="zs-layer-toolbar">
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="zsAddHeadlineLayerBtn">+ Başlık Yazısı</button>
              <button type="button" class="zs-btn zs-btn-sm" id="zsAddDomainLayerBtn">+ Marka / Domain</button>
              <button type="button" class="zs-btn zs-btn-sm" id="zsAddBadgeLayerBtn">+ Üst Rozet</button>
              <button type="button" class="zs-btn zs-btn-sm" id="zsAddLogoLayerBtn">+ Site Logosu</button>
            </div>
            <div class="zs-layer-list" id="zsCanvasLayerList" style="margin-top:10px"></div>
          </div>

          <div class="zs-card" style="padding:14px;margin:0" id="zsLayerInspectorCard"></div>

          <div class="zs-card" style="padding:14px;margin:0">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
              <strong style="font-size:12.5px"> Fotoğraf Alt Karartma (Kontrast Vinyet)</strong>
              <span id="zsScrimValLbl" style="font-size:12px;color:var(--accent-bright);font-weight:700">%${st.scrimPct}</span>
            </div>
            <input type="range" id="zsCanvasScrimRange" min="0" max="80" step="5" value="${st.scrimPct}" style="width:100%"/>
          </div>
        </div>
      </div>
    `;

    modal.classList.add('open');

    const canvas = document.getElementById('zsImgCanvas');
    const ctx = canvas.getContext('2d');

    const renderCanvas = (exportClean) => {
      if (!st.baseImg) return;
      const W = canvas.width;
      const H = canvas.height;
      ctx.clearRect(0, 0, W, H);
      ctx.drawImage(st.baseImg, 0, 0, W, H);

      if (st.scrimPct > 0) {
        const grad = ctx.createLinearGradient(0, H * 0.35, 0, H);
        grad.addColorStop(0, 'rgba(4, 10, 14, 0)');
        grad.addColorStop(1, `rgba(4, 10, 14, ${(st.scrimPct / 100).toFixed(2)})`);
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);
      }

      const baseDim = Math.min(W, H);

      st.layers.forEach((lyr, idx) => {
        ctx.save();
        ctx.globalAlpha = Math.max(0.1, Math.min(1, (lyr.opacity || 90) / 100));
        const cx = (lyr.xPct / 100) * W;
        const cy = (lyr.yPct / 100) * H;

        if (lyr.type === 'logo' && st.logoImg) {
          const logoW = Math.max(48, Math.round(W * ((lyr.sizePct || 16) / 100)));
          const ratio = (st.logoImg.naturalHeight || 100) / Math.max(1, st.logoImg.naturalWidth || 240);
          const logoH = Math.round(logoW * ratio);
          ctx.translate(cx, cy);
          if (lyr.rotation) ctx.rotate((lyr.rotation * Math.PI) / 180);
          ctx.drawImage(st.logoImg, -logoW / 2, -logoH / 2, logoW, logoH);
          lyr.bounds = { x: cx - logoW / 2 - 8, y: cy - logoH / 2 - 8, w: logoW + 16, h: logoH + 16 };
        } else {
          const rawText = String(lyr.text || '').trim();
          if (!rawText) {
            ctx.restore();
            return;
          }
          const fontPx = Math.max(13, Math.round(baseDim * ((lyr.sizePct || 3.2) / 100)));
          ctx.font = `${lyr.weight || '800'} ${fontPx}px "${lyr.font || 'Manrope'}", system-ui, sans-serif`;
          ctx.textBaseline = 'middle';
          const lines = rawText.split('\n');
          const lineH = Math.round(fontPx * 1.28);
          let maxLineW = 0;
          lines.forEach(ln => {
            const mw = ctx.measureText(ln).width;
            if (mw > maxLineW) maxLineW = mw;
          });
          const padX = Math.round(fontPx * 0.82);
          const padY = Math.round(fontPx * 0.48);
          const hasLogoPill = lyr.style === 'logo-glass-pill' && st.logoImg && st.logoImg.naturalWidth > 0;
          const logoPillH = hasLogoPill ? Math.max(16, Math.round(fontPx * 1.15)) : 0;
          const logoPillW = hasLogoPill
            ? Math.min(Math.round(fontPx * 3.8), Math.max(16, Math.round(logoPillH * (st.logoImg.naturalWidth / Math.max(1, st.logoImg.naturalHeight)))))
            : 0;
          const dotOffset = hasLogoPill
            ? (logoPillW + Math.round(fontPx * 0.85))
            : ((lyr.style === 'glass-pill' || lyr.style === 'logo-glass-pill') ? Math.round(fontPx * 0.85) : 0);
          const totalTextH = lines.length * lineH;
          const boxW = Math.round(maxLineW + padX * 2 + dotOffset);
          const boxH = Math.round(Math.max(totalTextH, logoPillH) + padY * 2);

          if (lyr.style === 'bottom-bar') {
            const barH = Math.max(boxH, Math.round(fontPx * 2.2));
            const by = Math.max(0, Math.min(H - barH, cy - barH / 2));
            ctx.fillStyle = 'rgba(5, 13, 18, 0.84)';
            ctx.fillRect(0, by, W, barH);
            ctx.fillStyle = lyr.accent || brandColor;
            ctx.fillRect(0, by, W, Math.max(3, Math.round(H * 0.005)));
            ctx.fillStyle = lyr.color || '#ffffff';
            ctx.textAlign = 'left';
            lines.forEach((ln, li) => {
              const ly = by + (barH - totalTextH) / 2 + li * lineH + lineH / 2;
              ctx.fillText(ln, Math.round(W * 0.04), ly);
            });
            lyr.bounds = { x: 0, y: by, w: W, h: barH };
          } else {
            ctx.translate(cx, cy);
            if (lyr.rotation) ctx.rotate((lyr.rotation * Math.PI) / 180);
            const bx = -boxW / 2;
            const by = -boxH / 2;

            if (lyr.style === 'glass-pill' || lyr.style === 'logo-glass-pill') {
              drawRoundedCanvasRect(ctx, bx, by, boxW, boxH, Math.min(28, boxH / 2));
              ctx.fillStyle = 'rgba(6, 15, 21, 0.82)';
              ctx.fill();
              ctx.lineWidth = Math.max(1.5, fontPx * 0.07);
              ctx.strokeStyle = hexToRgbaStr(lyr.accent || brandColor, 0.72);
              ctx.stroke();
              if (hasLogoPill) {
                ctx.drawImage(st.logoImg, bx + padX, -logoPillH / 2, logoPillW, logoPillH);
                const sepX = bx + padX + logoPillW + Math.round(fontPx * 0.42);
                ctx.beginPath();
                ctx.moveTo(sepX, -Math.round(fontPx * 0.5));
                ctx.lineTo(sepX, Math.round(fontPx * 0.5));
                ctx.lineWidth = Math.max(1.2, fontPx * 0.06);
                ctx.strokeStyle = hexToRgbaStr(lyr.accent || brandColor, 0.55);
                ctx.stroke();
              } else {
                const dotR = Math.max(4, Math.round(fontPx * 0.22));
                ctx.beginPath();
                ctx.arc(bx + padX + dotR / 2, 0, dotR, 0, Math.PI * 2);
                ctx.fillStyle = lyr.accent || brandColor;
                ctx.fill();
              }
            } else if (lyr.style === 'brand-pill') {
              drawRoundedCanvasRect(ctx, bx, by, boxW, boxH, Math.min(28, boxH / 2));
              ctx.fillStyle = lyr.accent || brandColor;
              ctx.fill();
            }

            ctx.textAlign = 'left';
            const textStartX = bx + padX + dotOffset;
            lines.forEach((ln, li) => {
              const ly = -totalTextH / 2 + li * lineH + lineH / 2;
              if (lyr.style === 'clean-shadow') {
                ctx.shadowColor = 'rgba(2, 8, 12, 0.85)';
                ctx.shadowBlur = Math.max(8, Math.round(fontPx * 0.35));
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = Math.max(2, Math.round(fontPx * 0.1));
              } else if (lyr.style === 'outline') {
                ctx.lineWidth = Math.max(3, Math.round(fontPx * 0.16));
                ctx.strokeStyle = 'rgba(4, 12, 16, 0.92)';
                ctx.lineJoin = 'round';
                ctx.strokeText(ln, textStartX, ly);
              }
              ctx.fillStyle = lyr.color || '#ffffff';
              ctx.fillText(ln, textStartX, ly);
              ctx.shadowBlur = 0;
            });

            lyr.bounds = { x: cx - boxW / 2, y: cy - boxH / 2, w: boxW, h: boxH };
          }
        }

        if (!exportClean && idx === st.selectedIdx && lyr.bounds) {
          ctx.restore();
          ctx.save();
          ctx.strokeStyle = '#5eead4';
          ctx.lineWidth = Math.max(2, Math.round(baseDim * 0.003));
          ctx.setLineDash([8, 6]);
          ctx.strokeRect(lyr.bounds.x - 4, lyr.bounds.y - 4, lyr.bounds.w + 8, lyr.bounds.h + 8);
        }
        ctx.restore();
      });
    };

    const paintLayerListAndInspector = () => {
      const listEl = document.getElementById('zsCanvasLayerList');
      const countEl = document.getElementById('zsLayerCountLbl');
      const inspEl = document.getElementById('zsLayerInspectorCard');
      if (countEl) countEl.textContent = `${st.layers.length} Katman`;
      if (listEl) {
        listEl.innerHTML = st.layers.map((lyr, idx) => `
          <div class="zs-layer-chip ${idx === st.selectedIdx ? 'is-active' : ''}" data-lyr-idx="${idx}">
            <span class="zs-layer-chip-title">${lyr.type === 'logo' ? '🖼️ Site Logosu' : ('🔤 ' + esc((lyr.text || lyr.label || 'Yazı').replace(/\n/g, ' ')))}</span>
            <div style="display:flex;gap:4px;flex-shrink:0">
              ${st.layers.length > 1 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" style="padding:2px 7px;font-size:11px" data-lyr-del="${idx}" title="Katmanı Sil">✕</button>` : ''}
            </div>
          </div>
        `).join('');

        listEl.querySelectorAll('[data-lyr-idx]').forEach(el => {
          el.onclick = ev => {
            if (ev.target.closest('[data-lyr-del]')) return;
            st.selectedIdx = Number(el.getAttribute('data-lyr-idx'));
            paintLayerListAndInspector();
            renderCanvas(false);
          };
        });
        listEl.querySelectorAll('[data-lyr-del]').forEach(btn => {
          btn.onclick = ev => {
            ev.stopPropagation();
            const dIdx = Number(btn.getAttribute('data-lyr-del'));
            st.layers.splice(dIdx, 1);
            st.selectedIdx = Math.max(0, Math.min(st.selectedIdx, st.layers.length - 1));
            paintLayerListAndInspector();
            renderCanvas(false);
          };
        });
      }

      const active = st.layers[st.selectedIdx];
      if (!inspEl || !active) return;

      if (active.type === 'logo') {
        inspEl.innerHTML = `
          <strong style="display:block;font-size:13px;color:#5eead4;margin-bottom:10px">🖼️ Logo Katmanı Ayarları</strong>
          <div class="zs-field">
            <label>Logo Boyutu (%${active.sizePct})</label>
            <input type="range" id="lyrSizeRange" min="6" max="42" step="1" value="${active.sizePct || 16}" style="width:100%"/>
          </div>
          <div class="zs-field">
            <label>Saydamlık (%${active.opacity})</label>
            <input type="range" id="lyrOpRange" min="20" max="100" step="2" value="${active.opacity || 92}" style="width:100%"/>
          </div>
          <div class="zs-field" style="margin-bottom:0">
            <label>9 Noktalı Hızlı Konumlandırma</label>
            <div class="zs-pos-9grid">
              <button type="button" class="zs-pos-9btn" data-snap="12:10">↖ Sol Üst</button>
              <button type="button" class="zs-pos-9btn" data-snap="50:10">↑ Üst Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="88:10">↗ Sağ Üst</button>
              <button type="button" class="zs-pos-9btn" data-snap="14:50">← Sol Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="50:50">• Tam Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="86:50">→ Sağ Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="12:90">↙ Sol Alt</button>
              <button type="button" class="zs-pos-9btn" data-snap="50:90">↓ Alt Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="88:90">↘ Sağ Alt</button>
            </div>
          </div>
        `;
      } else {
        inspEl.innerHTML = `
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <strong style="font-size:13px;color:#5eead4">🔤 Seçili Yazı Katmanı</strong>
            <div class="zs-token-chips">
              <button type="button" class="zs-token-chip" data-ins-txt="${esc(siteName)}">+ Marka</button>
              <button type="button" class="zs-token-chip" data-ins-txt="${esc(domainHost)}">+ Domain</button>
            </div>
          </div>
          <div class="zs-field">
            <label>Görsel Üzerindeki Metin (Alt satır için Enter)</label>
            <textarea id="lyrTextInp" class="zs-textarea" style="min-height:56px">${esc(active.text || '')}</textarea>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field">
              <label>Kutu / Görünüm Stili</label>
              <select id="lyrStyleSel" class="zs-select">
                <option value="logo-glass-pill" ${active.style === 'logo-glass-pill' ? 'selected' : ''}>Logo + Buzlu Cam Kapsül</option>
                <option value="glass-pill" ${active.style === 'glass-pill' ? 'selected' : ''}>Buzlu Cam Kapsül (Nokta)</option>
                <option value="brand-pill" ${active.style === 'brand-pill' ? 'selected' : ''}>Marka Renkli Kapsül</option>
                <option value="clean-shadow" ${active.style === 'clean-shadow' ? 'selected' : ''}>Temiz Gölgeli Yazı</option>
                <option value="outline" ${active.style === 'outline' ? 'selected' : ''}>Konturlu Başlık</option>
                <option value="bottom-bar" ${active.style === 'bottom-bar' ? 'selected' : ''}>Tam Alt Bant</option>
              </select>
            </div>
            <div class="zs-field">
              <label>Yazı Tipi</label>
              <select id="lyrFontSel" class="zs-select">
                ${['Manrope', 'Montserrat', 'Plus Jakarta Sans', 'Space Grotesk', 'Playfair Display', 'Georgia'].map(fn => `<option value="${fn}" ${active.font === fn ? 'selected' : ''}>${fn}</option>`).join('')}
              </select>
            </div>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field">
              <label>Yazı Boyutu (${active.sizePct})</label>
              <input type="range" id="lyrSizeRange" min="1.4" max="11" step="0.2" value="${active.sizePct || 3}" style="width:100%"/>
            </div>
            <div class="zs-field">
              <label>Saydamlık (%${active.opacity})</label>
              <input type="range" id="lyrOpRange" min="15" max="100" step="5" value="${active.opacity || 92}" style="width:100%"/>
            </div>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field">
              <label>Yazı Rengi</label>
              <div class="zs-color-pair">
                <input type="color" id="lyrColPick" class="zs-color-picker" value="${esc(active.color || '#ffffff')}"/>
                <input type="text" id="lyrColHex" class="zs-input" value="${esc(active.color || '#ffffff')}"/>
              </div>
            </div>
            <div class="zs-field">
              <label>Vurgu / Kapsül Rengi</label>
              <div class="zs-color-pair">
                <input type="color" id="lyrAccPick" class="zs-color-picker" value="${esc(active.accent || brandColor)}"/>
                <input type="text" id="lyrAccHex" class="zs-input" value="${esc(active.accent || brandColor)}"/>
              </div>
            </div>
          </div>
          <div class="zs-field">
            <label>Eğim / Açı (${active.rotation || 0}°)</label>
            <input type="range" id="lyrRotRange" min="-35" max="35" step="1" value="${active.rotation || 0}" style="width:100%"/>
          </div>
          <div class="zs-field" style="margin-bottom:0">
            <label>9 Noktalı Hızlı Hizalama</label>
            <div class="zs-pos-9grid">
              <button type="button" class="zs-pos-9btn" data-snap="18:9">↖ Sol Üst</button>
              <button type="button" class="zs-pos-9btn" data-snap="50:9">↑ Üst Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="82:9">↗ Sağ Üst</button>
              <button type="button" class="zs-pos-9btn" data-snap="20:50">← Sol Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="50:50">• Tam Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="80:50">→ Sağ Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="18:91">↙ Sol Alt</button>
              <button type="button" class="zs-pos-9btn" data-snap="50:91">↓ Alt Orta</button>
              <button type="button" class="zs-pos-9btn" data-snap="82:91">↘ Sağ Alt</button>
            </div>
          </div>
        `;

        const txtInp = document.getElementById('lyrTextInp');
        txtInp?.addEventListener('input', () => {
          active.text = txtInp.value;
          renderCanvas(false);
          const chipTitle = document.querySelector(`#zsCanvasLayerList [data-lyr-idx="${st.selectedIdx}"] .zs-layer-chip-title`);
          if (chipTitle) chipTitle.textContent = '🔤 ' + (active.text || 'Yazı').replace(/\n/g, ' ');
        });
        inspEl.querySelectorAll('[data-ins-txt]').forEach(tb => {
          tb.onclick = () => {
            const add = tb.getAttribute('data-ins-txt') || '';
            active.text = active.text ? `${active.text} · ${add}` : add;
            if (txtInp) txtInp.value = active.text;
            paintLayerListAndInspector();
            renderCanvas(false);
          };
        });
        document.getElementById('lyrStyleSel')?.addEventListener('change', e => {
          active.style = e.target.value;
          renderCanvas(false);
        });
        document.getElementById('lyrFontSel')?.addEventListener('change', e => {
          active.font = e.target.value;
          renderCanvas(false);
        });
        const colPick = document.getElementById('lyrColPick');
        const colHex = document.getElementById('lyrColHex');
        colPick?.addEventListener('input', () => { active.color = colPick.value; if (colHex) colHex.value = colPick.value; renderCanvas(false); });
        colHex?.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(colHex.value.trim())) { active.color = colHex.value.trim(); if (colPick) colPick.value = active.color; renderCanvas(false); } });

        const accPick = document.getElementById('lyrAccPick');
        const accHex = document.getElementById('lyrAccHex');
        accPick?.addEventListener('input', () => { active.accent = accPick.value; if (accHex) accHex.value = accPick.value; renderCanvas(false); });
        accHex?.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(accHex.value.trim())) { active.accent = accHex.value.trim(); if (accPick) accPick.value = active.accent; renderCanvas(false); } });

        document.getElementById('lyrRotRange')?.addEventListener('input', e => {
          active.rotation = Number(e.target.value || 0);
          renderCanvas(false);
        });
      }

      document.getElementById('lyrSizeRange')?.addEventListener('input', e => {
        active.sizePct = Number(e.target.value || 3);
        renderCanvas(false);
      });
      document.getElementById('lyrOpRange')?.addEventListener('input', e => {
        active.opacity = Number(e.target.value || 90);
        renderCanvas(false);
      });
      inspEl.querySelectorAll('[data-snap]').forEach(sb => {
        sb.onclick = () => {
          const [sx, sy] = sb.getAttribute('data-snap').split(':').map(Number);
          active.xPct = sx;
          active.yPct = sy;
          renderCanvas(false);
        };
      });
    };

    // Preload Site Emblem / Logo for logo-glass-pill & logo layers
    const preloadLogoCandidates = [
      resolveUrl('/icon.png'),
      resolveUrl(S.store?.site?.logo || '/icon.png')
    ];
    const tryPreloadLogo = cIdx => {
      if (cIdx >= preloadLogoCandidates.length) return;
      const lImg = new Image();
      lImg.crossOrigin = 'anonymous';
      lImg.onload = () => {
        st.logoImg = lImg;
        if (st.baseImg) renderCanvas(false);
      };
      lImg.onerror = () => tryPreloadLogo(cIdx + 1);
      lImg.src = preloadLogoCandidates[cIdx];
    };
    tryPreloadLogo(0);

    // Load Base Image
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
      st.baseImg = img;
      const nw = Math.max(240, img.naturalWidth || 1200);
      const nh = Math.max(180, img.naturalHeight || 800);
      const scaleDown = nw > 2400 ? (2400 / nw) : 1;
      canvas.width = Math.round(nw * scaleDown);
      canvas.height = Math.round(nh * scaleDown);
      const dimLbl = document.getElementById('zsCanvasDimInfo');
      if (dimLbl) dimLbl.textContent = `${canvas.width} × ${canvas.height} px · Yüksek Kalite Kanvas`;
      paintLayerListAndInspector();
      renderCanvas(false);
    };
    img.onerror = () => {
      toast('Görsel kanvasa yüklenemedi.', true);
    };
    img.src = resolveUrl(item.url) + (item.url.includes('?') ? '&' : '?') + '_cv=' + Date.now();

    // Quick Add Layer Buttons
    document.getElementById('zsAddHeadlineLayerBtn').onclick = () => {
      st.layers.push({
        id: uid('lyr'),
        type: 'text',
        label: 'Başlık',
        text: defaultHeadline,
        font: 'Manrope',
        weight: '800',
        sizePct: 5.2,
        color: '#ffffff',
        accent: brandColor,
        style: 'clean-shadow',
        opacity: 98,
        rotation: 0,
        xPct: 50,
        yPct: 76,
        bounds: null
      });
      st.selectedIdx = st.layers.length - 1;
      paintLayerListAndInspector();
      renderCanvas(false);
    };

    document.getElementById('zsAddDomainLayerBtn').onclick = () => {
      st.layers.push({
        id: uid('lyr'),
        type: 'text',
        label: 'Domain',
        text: `${siteName} · ${domainHost}`,
        font: 'Manrope',
        weight: '800',
        sizePct: 2.8,
        color: '#ffffff',
        accent: brandColor,
        style: 'glass-pill',
        opacity: 92,
        rotation: 0,
        xPct: 82,
        yPct: 91,
        bounds: null
      });
      st.selectedIdx = st.layers.length - 1;
      paintLayerListAndInspector();
      renderCanvas(false);
    };

    document.getElementById('zsAddBadgeLayerBtn').onclick = () => {
      st.layers.push({
        id: uid('lyr'),
        type: 'text',
        label: 'Üst Rozet',
        text: `${siteName} SEÇKİSİ`,
        font: 'Manrope',
        weight: '800',
        sizePct: 2.4,
        color: '#031518',
        accent: brandColor,
        style: 'brand-pill',
        opacity: 96,
        rotation: 0,
        xPct: 18,
        yPct: 10,
        bounds: null
      });
      st.selectedIdx = st.layers.length - 1;
      paintLayerListAndInspector();
      renderCanvas(false);
    };

    document.getElementById('zsAddLogoLayerBtn').onclick = () => {
      const logoSrc = resolveUrl(S.store?.site?.logo || '/icon.png');
      const finishLogoAdd = () => {
        st.layers.push({
          id: uid('lyr'),
          type: 'logo',
          label: 'Site Logosu',
          sizePct: 16,
          opacity: 94,
          rotation: 0,
          xPct: 16,
          yPct: 12,
          bounds: null
        });
        st.selectedIdx = st.layers.length - 1;
        paintLayerListAndInspector();
        renderCanvas(false);
      };
      if (st.logoImg) {
        finishLogoAdd();
        return;
      }
      const lImg = new Image();
      lImg.crossOrigin = 'anonymous';
      lImg.onload = () => {
        st.logoImg = lImg;
        finishLogoAdd();
      };
      lImg.onerror = () => toast('Site logosu yüklenemedi; lütfen Sistem Ayarlarından PNG/WebP logo seçin.', true);
      lImg.src = logoSrc;
    };

    document.getElementById('zsCanvasScrimRange').oninput = e => {
      st.scrimPct = Number(e.target.value || 0);
      const lbl = document.getElementById('zsScrimValLbl');
      if (lbl) lbl.textContent = `%${st.scrimPct}`;
      renderCanvas(false);
    };

    // Pointer Dragging on Canvas
    const toCanvasCoords = ev => {
      const rect = canvas.getBoundingClientRect();
      return {
        x: ((ev.clientX - rect.left) / Math.max(1, rect.width)) * canvas.width,
        y: ((ev.clientY - rect.top) / Math.max(1, rect.height)) * canvas.height
      };
    };

    canvas.onpointerdown = ev => {
      const pt = toCanvasCoords(ev);
      let hitIdx = -1;
      for (let i = st.layers.length - 1; i >= 0; i--) {
        const b = st.layers[i].bounds;
        if (b && pt.x >= b.x - 10 && pt.x <= b.x + b.w + 10 && pt.y >= b.y - 10 && pt.y <= b.y + b.h + 10) {
          hitIdx = i;
          break;
        }
      }
      if (hitIdx >= 0) {
        st.selectedIdx = hitIdx;
        paintLayerListAndInspector();
      }
      const cur = st.layers[st.selectedIdx];
      if (!cur) return;
      st.dragging = true;
      st.dragOffsetX = pt.x - (cur.xPct / 100) * canvas.width;
      st.dragOffsetY = pt.y - (cur.yPct / 100) * canvas.height;
      try { canvas.setPointerCapture(ev.pointerId); } catch (_) {}
      renderCanvas(false);
    };

    canvas.onpointermove = ev => {
      if (!st.dragging) return;
      const cur = st.layers[st.selectedIdx];
      if (!cur) return;
      const pt = toCanvasCoords(ev);
      cur.xPct = Math.max(2, Math.min(98, ((pt.x - st.dragOffsetX) / canvas.width) * 100));
      cur.yPct = Math.max(3, Math.min(97, ((pt.y - st.dragOffsetY) / canvas.height) * 100));
      renderCanvas(false);
    };

    const endDrag = () => { st.dragging = false; };
    canvas.onpointerup = endDrag;
    canvas.onpointercancel = endDrag;

    const executeSaveCanvas = async (saveMode, triggerBtn) => {
      if (!st.baseImg) return;
      const origTxt = triggerBtn.textContent;
      triggerBtn.disabled = true;
      triggerBtn.textContent = '⏳ İşleniyor & Kaydediliyor...';
      try {
        renderCanvas(true);
        let dataUri = canvas.toDataURL('image/webp', 0.92);
        if (!dataUri.startsWith('data:image/webp')) {
          dataUri = canvas.toDataURL('image/png');
        }
        renderCanvas(false);
        const altVal = (document.getElementById('zsCanvasAltInp')?.value || st.altText || '').trim();
        const res = await api('media_canvas_save', {
          source_url: item.url,
          data_uri: dataUri,
          save_mode: saveMode,
          alt: altVal
        });
        toast(res.message);
        if (res.item?.url) {
          S.mediaMtimes = S.mediaMtimes || {};
          S.mediaMtimes[res.item.url] = res.item.mtime || Date.now();
          S.media.recentUploadedUrls = [res.item.url, ...(S.media.recentUploadedUrls || [])].slice(0, 12);
        }
        if (res.summary) S.summary = res.summary;
        modal.classList.remove('open');
        if (S.view === 'media') {
          await refreshMediaGridOnly();
          if (typeof onSaved !== 'function' && res.item?.url) {
            const newIdx = S.media.items.findIndex(x => x.url === res.item.url);
            if (newIdx >= 0) openMediaLightbox(newIdx);
          }
        }
        if (typeof onSaved === 'function') onSaved(res.item);
      } catch (err) {
        renderCanvas(false);
        toast(err.message, true);
        triggerBtn.disabled = false;
        triggerBtn.textContent = origTxt;
      }
    };

    document.getElementById('zsCanvasSaveCopyBtn').onclick = function () {
      executeSaveCanvas('copy', this);
    };
    const ovBtn = document.getElementById('zsCanvasOverwriteBtn');
    if (ovBtn) {
      ovBtn.onclick = function () {
        executeSaveCanvas('overwrite', this);
      };
    }
  }

  function openMediaLightbox(idx) {
    const item = S.media.items[idx];
    if (!item) return;

    S.media.selectedItem = item;
    syncMediaSelectionDom();

    const lb = ensureLightboxModal();
    const totalOnPage = S.media.items.length;
    const isChecked = S.media.selectedUrls.includes(item.url);
    const deletable = isDeletableUpload(item.url);
    const fullImgUrl = resolvePreviewUrl(item.url, item.mtime || Date.now());

    lb.innerHTML = `
      <div class="zs-lightbox-dialog" role="dialog" aria-modal="true">
        <div class="zs-lightbox-stage">
          <div class="zs-lightbox-topbar">
            <div style="display:flex;align-items:center;gap:10px;min-width:0">
              <span class="zs-badge-pill">${idx + 1} / ${totalOnPage}</span>
              <strong style="color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(item.name)}</strong>
              <span id="lbDimBadge" style="color:var(--accent-bright);font-weight:600">${esc(item.ext.toUpperCase())} · ${fmtBytes(item.size)}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
              <button type="button" class="zs-btn zs-btn-sm" id="lbZoomBtn" title="Gerçek Boyut (1:1) / Ekrana Sığdır">🔍 Yakınlaştır</button>
              <a href="${esc(fullImgUrl)}" target="_blank" rel="noopener" class="zs-btn zs-btn-sm">Tam Boyut Aç ↗</a>
              <button type="button" class="zs-btn zs-btn-sm" id="lbCloseTopBtn">Kapat ✕</button>
            </div>
          </div>

          <button type="button" class="zs-lightbox-nav prev" id="lbPrevBtn" ${idx <= 0 ? 'disabled' : ''} title="Önceki Görsel (←)">‹</button>
          <div class="zs-lightbox-canvas" id="lbCanvas">
            <img id="lbMainImg" src="${esc(fullImgUrl)}" alt="${esc(item.alt || item.name)}"/>
          </div>
          <button type="button" class="zs-lightbox-nav next" id="lbNextBtn" ${idx >= totalOnPage - 1 ? 'disabled' : ''} title="Sonraki Görsel (→)">›</button>
        </div>

        <div class="zs-lightbox-side">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:15.5px">Görsel Detayı & SEO</h3>
            <span style="font-size:11px;color:var(--text-dim)">← → ile gezin</span>
          </div>

          <div style="padding:12px;border-radius:11px;background:rgba(5,12,16,0.75);border:1px solid var(--border-subtle);font-size:12px;color:var(--text-muted);display:grid;gap:5px">
            <div><strong style="color:var(--text-main)">Dosya:</strong> <span style="word-break:break-all">${esc(item.name)}</span></div>
            <div><strong style="color:var(--text-main)">Klasör:</strong> /${esc(item.folder)}</div>
            <div><strong style="color:var(--text-main)">Dosya Boyutu:</strong> ${fmtBytes(item.size)}</div>
            <div><strong style="color:var(--text-main)">Çözünürlük:</strong> <span id="lbDimText">Ölçülüyor...</span></div>
          </div>

          <div class="zs-field" style="margin:0">
            <label>Görsel Bağlantısı &amp; Hazır Kod</label>
            <div class="zs-input-group">
              <input id="mediaSelUrl" class="zs-input" readonly value="${esc(item.url)}"/>
              <button type="button" class="zs-btn zs-btn-sm" id="mediaCopyUrlBtn">URL</button>
              <button type="button" class="zs-btn zs-btn-sm" id="mediaCopyImgTagBtn" title="Hazır &lt;img&gt; HTML etiketini kopyala">&lt;img&gt;</button>
            </div>
          </div>

          <div class="zs-field" style="margin:0">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;margin-bottom:5px">
              <label style="margin:0">Görsel Alt Etiketi (SEO)</label>
              <button type="button" class="zs-upload-mini-btn" id="mediaAutoAltBtn" title="Dosya adından temiz Türkçe SEO alt etiketi üret">✦ Otomatik Üret</button>
            </div>
            <input id="mediaSelAlt" class="zs-input" placeholder="Görseli tanımlayan açıklama..." value="${esc(item.alt || '')}"/>
          </div>

          <button type="button" class="zs-btn zs-btn-primary" id="mediaSaveAltBtn" style="width:100%">✓ Alt Metni Kaydet</button>

          ${item.ext !== 'svg' ? `
            <div style="padding:11px;border-radius:11px;background:rgba(20,184,166,0.09);border:1px solid rgba(94,234,212,0.3);display:grid;gap:7px">
              <strong style="font-size:12px;color:#5eead4">🎨 Görsel Üzerine Marka &amp; Başlık Yaz</strong>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="lbOpenCanvasStudioBtn" style="width:100%">🎨 Görsel Kanvas Editöründe Aç</button>
              ${deletable ? `<button type="button" class="zs-btn zs-btn-sm" id="lbQuickStampBtn" style="width:100%">🏷️ Tek Tıkla Otomatik Marka Damgala</button>` : ''}
            </div>
          ` : ''}

          ${deletable ? `
            <div style="margin-top:auto;padding-top:14px;border-top:1px solid var(--border-subtle);display:grid;gap:8px">
              <button type="button" class="zs-btn zs-btn-sm" id="lbToggleBulkBtn" style="width:100%">
                ${isChecked ? '✓ Çoklu Seçimde (Çıkar)' : '☐ Çoklu Seçime Ekle'}
              </button>
              <button type="button" class="zs-btn zs-btn-danger" id="mediaDeleteBtn" style="width:100%">
                Görseli Kalıcı Olarak Sil
              </button>
            </div>
          ` : `
            <div style="margin-top:auto;padding:10px 12px;border-radius:10px;background:rgba(20,184,166,0.08);border:1px solid var(--border-subtle);font-size:11.5px;color:var(--text-muted)">
              Bu dosya çekirdek site tasarımına (/images/) ait olduğu için silmeye karşı korumalıdır.
            </div>
          `}
        </div>
      </div>
    `;

    lb.classList.add('open');

    const imgEl = document.getElementById('lbMainImg');
    const canvasEl = document.getElementById('lbCanvas');
    const zoomBtn = document.getElementById('lbZoomBtn');

    imgEl.onload = () => {
      const dimStr = `${imgEl.naturalWidth} × ${imgEl.naturalHeight} px`;
      const dimText = document.getElementById('lbDimText');
      const dimBadge = document.getElementById('lbDimBadge');
      if (dimText) dimText.textContent = dimStr;
      if (dimBadge) dimBadge.textContent = `${dimStr} · ${item.ext.toUpperCase()} · ${fmtBytes(item.size)}`;
    };

    const toggleZoom = () => {
      const z = canvasEl.classList.toggle('zoomed');
      zoomBtn.textContent = z ? '↙ Ekrana Sığdır' : '🔍 Yakınlaştır';
    };
    imgEl.onclick = toggleZoom;
    zoomBtn.onclick = toggleZoom;

    document.getElementById('lbCloseTopBtn').onclick = closeMediaLightbox;
    document.getElementById('lbPrevBtn').onclick = () => stepMediaLightbox(-1);
    document.getElementById('lbNextBtn').onclick = () => stepMediaLightbox(1);

    document.getElementById('mediaCopyUrlBtn').onclick = () => {
      copyTextSafe(item.url, 'Görsel adresi panoya kopyalandı: ' + item.url);
    };

    document.getElementById('mediaCopyImgTagBtn').onclick = () => {
      const altVal = (document.getElementById('mediaSelAlt')?.value || item.alt || humanizeAltFromFilename(item.name)).trim();
      const wh = (imgEl.naturalWidth && imgEl.naturalHeight) ? ` width="${imgEl.naturalWidth}" height="${imgEl.naturalHeight}"` : '';
      const snippet = `<img src="${item.url}" alt="${altVal.replace(/"/g, '&quot;')}"${wh} loading="lazy" decoding="async"/>`;
      copyTextSafe(snippet, 'Hazır <img> HTML etiketi kopyalandı!');
    };

    document.getElementById('mediaAutoAltBtn').onclick = () => {
      const altInp = document.getElementById('mediaSelAlt');
      if (altInp) {
        altInp.value = humanizeAltFromFilename(item.name);
        altInp.focus();
      }
    };

    document.getElementById('mediaSaveAltBtn').onclick = async () => {
      try {
        const alt = document.getElementById('mediaSelAlt').value.trim();
        const res = await api('media_meta_save', { url: item.url, alt });
        item.alt = alt;
        toast(res.message);
        refreshMediaGridOnly();
      } catch (err) {
        toast(err.message, true);
      }
    };

    document.getElementById('lbOpenCanvasStudioBtn')?.addEventListener('click', () => {
      openImageCanvasStudio(item);
    });

    document.getElementById('lbQuickStampBtn')?.addEventListener('click', async function () {
      const btn = this;
      btn.disabled = true;
      btn.textContent = '⏳ Damgalanıyor...';
      try {
        const res = await api('media_apply_stamp', { url: item.url });
        toast(res.message);
        const newMtime = res.mtime || Date.now();
        item.mtime = newMtime;
        if (res.size) item.size = res.size;
        S.mediaMtimes = S.mediaMtimes || {};
        S.mediaMtimes[item.url] = newMtime;
        if (imgEl) {
          imgEl.src = resolvePreviewUrl(item.url, newMtime);
        }
        await refreshMediaGridOnly();
      } catch (err) {
        toast(err.message, true);
      } finally {
        btn.disabled = false;
        btn.textContent = '🏷️ Tek Tıkla Otomatik Marka Damgala';
      }
    });

    const bulkToggleBtn = document.getElementById('lbToggleBulkBtn');
    if (bulkToggleBtn) {
      bulkToggleBtn.onclick = () => {
        toggleMediaBulkItem(idx, false);
        const nowChecked = S.media.selectedUrls.includes(item.url);
        bulkToggleBtn.textContent = nowChecked ? '✓ Çoklu Seçimde (Çıkar)' : '☐ Çoklu Seçime Ekle';
      };
    }

    const delBtn = document.getElementById('mediaDeleteBtn');
    if (delBtn) {
      delBtn.onclick = async () => {
        if (!confirm(`"${item.name}" dosyasını /uploads klasöründen kalıcı olarak silmek istediğinize emin misiniz?`)) return;
        delBtn.disabled = true;
        delBtn.textContent = 'Siliniyor...';
        try {
          const res = await api('media_delete', { url: item.url });
          toast(res.message);
          const nextIdxCandidate = idx;
          removeUrlsFromMediaStateInPlace([item.url]);
          if (S.media.items.length > 0) {
            openMediaLightbox(Math.min(nextIdxCandidate, S.media.items.length - 1));
          } else {
            closeMediaLightbox();
          }
        } catch (err) {
          delBtn.disabled = false;
          delBtn.textContent = 'Görseli Kalıcı Olarak Sil';
          toast(err.message, true);
        }
      };
    }
  }

  function paintMediaSidePanel() {
    // Full-screen Lightbox handles preview without shrinking or shifting the 8-column media grid
  }

  function toggleMediaBulkItem(idx, shiftKey) {
    const item = S.media.items[idx];
    if (!item || !isDeletableUpload(item.url)) {
      toast('Çekirdek tema görselleri (/images/) güvenlik gereği silinemez; yalnızca /uploads/ görselleri seçilebilir.');
      return;
    }

    const set = new Set(S.media.selectedUrls);
    const shouldSelect = !set.has(item.url);

    if (shiftKey && S.media.lastClickedIdx !== null && S.media.lastClickedIdx !== idx) {
      const start = Math.min(S.media.lastClickedIdx, idx);
      const end = Math.max(S.media.lastClickedIdx, idx);
      for (let i = start; i <= end; i++) {
        const rangeItem = S.media.items[i];
        if (rangeItem && isDeletableUpload(rangeItem.url)) {
          if (shouldSelect) set.add(rangeItem.url);
          else set.delete(rangeItem.url);
        }
      }
    } else {
      if (shouldSelect) set.add(item.url);
      else set.delete(item.url);
    }

    S.media.lastClickedIdx = idx;
    S.media.selectedUrls = Array.from(set);
    syncMediaSelectionDom();
  }

  async function handleBulkDeleteSelected() {
    const count = S.media.selectedUrls.length;
    if (count === 0) return;
    if (!confirm(`Seçilen ${count} adet görseli /uploads klasöründen kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`)) {
      return;
    }

    const btn = document.getElementById('mediaBulkDeleteBtn');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Siliniyor...';
    }

    try {
      const res = await api('media_bulk_delete', { urls: S.media.selectedUrls });
      toast(res.message);
      const deleted = res.deleted_urls || [];
      removeUrlsFromMediaStateInPlace(deleted);
    } catch (err) {
      if (btn) {
        btn.disabled = false;
        btn.textContent = `Seçilen ${count} Görseli Sil`;
      }
      toast(err.message, true);
    }
  }

  function removeUrlsFromMediaStateInPlace(deletedUrls) {
    if (!deletedUrls || !deletedUrls.length) return;
    const delSet = new Set(deletedUrls);
    const removedCount = deletedUrls.length;

    S.media.items = S.media.items.filter(it => !delSet.has(it.url));
    S.media.selectedUrls = S.media.selectedUrls.filter(u => !delSet.has(u));
    S.media.lastClickedIdx = null;
    S.media.total = Math.max(0, (S.media.total || 0) - removedCount);

    if (S.media.folders) {
      if (typeof S.media.folders.all === 'number') {
        S.media.folders.all = Math.max(0, S.media.folders.all - removedCount);
      }
      deletedUrls.forEach(u => {
        const folderKey = u.replace(/^\//, '').split('/').slice(0, -1).join('/');
        if (folderKey && typeof S.media.folders[folderKey] === 'number') {
          S.media.folders[folderKey] = Math.max(0, S.media.folders[folderKey] - 1);
        }
      });
    }

    if (S.summary) {
      S.summary.media_total = Math.max(0, (S.summary.media_total || 0) - removedCount);
      updateSidebarBadges();
    }

    if (S.media.selectedItem && delSet.has(S.media.selectedItem.url)) {
      S.media.selectedItem = null;
    }

    // If current page became empty after bulk delete and more items exist, fetch seamlessly
    if (S.media.items.length === 0 && S.media.total > 0) {
      if (S.media.page > 1 && S.media.page > Math.ceil(S.media.total / 48)) {
        S.media.page = Math.max(1, S.media.page - 1);
      }
      refreshMediaGridOnly();
      return;
    }

    paintMediaGridDom();
    paintMediaSidePanel();
    paintMediaFolderOptions();
  }

  function paintMediaFolderOptions() {
    const folderSel = document.getElementById('mediaFolderSel');
    if (!folderSel) return;
    folderSel.innerHTML = Object.keys(S.media.folders || {}).map(f =>
      `<option value="${esc(f)}" ${S.media.folder === f ? 'selected' : ''}>${f === 'all' ? 'Tüm Klasörler (/uploads + /images)' : esc(f)} (${S.media.folders[f]})</option>`
    ).join('');
  }

  function paintMediaStatsStrip() {
    const slot = document.getElementById('mediaStatsSlot');
    if (!slot) return;
    const st = S.media.stats || {};
    const totalFiles = Number(st.total_files ?? S.media.folders?.all ?? S.media.total ?? 0);
    const totalBytes = Number(st.total_bytes || 0);
    const uploadsCnt = Number(st.uploads_count || 0);
    const imagesCnt = Number(st.images_count || 0);
    const webpCnt = Number(st.webp_avif_count || 0);
    const missingAltCnt = Number(st.missing_alt_count || 0);
    const webpPct = totalFiles > 0 ? Math.round((webpCnt / totalFiles) * 100) : 100;

    slot.innerHTML = `
      <div class="zs-media-stats-strip">
        <div class="zs-media-stat-tile">
          <div>
            <small>TOPLAM MEDYA ARŞİVİ</small>
            <strong>${totalFiles.toLocaleString('tr-TR')} Görsel</strong>
            <span class="sub">/uploads: ${uploadsCnt} · /images: ${imagesCnt}</span>
          </div>
          <span class="zs-pill zs-pill-cat">▣</span>
        </div>
        <div class="zs-media-stat-tile">
          <div>
            <small>TOPLAM DİSK BOYUTU</small>
            <strong>${fmtBytes(totalBytes)}</strong>
            <span class="sub">Optimize bulut medya deposu</span>
          </div>
          <span class="zs-pill zs-pill-cat">⚡</span>
        </div>
        <div class="zs-media-stat-tile">
          <div>
            <small>YENİ NESİL WEBP / AVIF</small>
            <strong style="color:#34d399">%${webpPct}</strong>
            <span class="sub">${webpCnt.toLocaleString('tr-TR')} yeni nesil hızlı görsel</span>
          </div>
          <span class="zs-pill zs-pill-pub">HIZ</span>
        </div>
        <div class="zs-media-stat-tile">
          <div>
            <small>GÖRSEL SEO (ALT ETİKETİ)</small>
            <strong style="color:${missingAltCnt === 0 ? '#34d399' : '#fbbf24'}">${missingAltCnt === 0 ? 'Tamamı Dolu ✓' : (missingAltCnt + ' Eksik')}</strong>
            <span class="sub">${missingAltCnt === 0 ? 'Google Görseller %100 uyumlu' : 'Yüklenen görsellerde alt metni eksik'}</span>
          </div>
          ${missingAltCnt > 0
            ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="mediaBulkAutoAltBtn" title="Eksik tüm görsellerin alt etiketini dosya adından otomatik üret">✦ Doldur</button>`
            : `<span class="zs-pill zs-pill-pub">SEO</span>`
          }
        </div>
      </div>
    `;

    const autoBtn = document.getElementById('mediaBulkAutoAltBtn');
    if (autoBtn) {
      autoBtn.onclick = async () => {
        autoBtn.disabled = true;
        autoBtn.textContent = '⏳ Üretiliyor...';
        try {
          const res = await api('media_bulk_auto_alt', {});
          toast(res.message);
          await refreshMediaGridOnly();
        } catch (err) {
          toast(err.message, true);
          autoBtn.disabled = false;
          autoBtn.textContent = '✦ Doldur';
        }
      };
    }
  }

  function paintMediaGridDom() {
    const gridSlot = document.getElementById('mediaGridSlot');
    if (!gridSlot) return;

    rememberMediaMtimes(S.media.items);
    const sel = S.media.selectedItem;
    const checkedSet = new Set(S.media.selectedUrls);
    const recentSet = new Set(S.media.recentUploadedUrls || []);
    const hasBulk = S.media.bulkMode || checkedSet.size > 0;
    const vMode = S.media.viewMode || 'grid';

    if (S.media.items.length === 0) {
      gridSlot.innerHTML = `<div style="padding:36px 16px;text-align:center;color:var(--text-muted)">Bu filtreye uygun görsel bulunamadı.</div>`;
    } else if (vMode === 'list') {
      gridSlot.innerHTML = `
        <div class="zs-table-wrap" id="mediaGridEl">
          <table class="zs-table">
            <thead>
              <tr>
                <th style="width:42px">Seç</th>
                <th style="width:72px">Önizleme</th>
                <th>Dosya Adı &amp; Klasör</th>
                <th>Çözünürlük</th>
                <th>Format &amp; Boyut</th>
                <th>SEO Alt Etiketi</th>
                <th style="text-align:right">İşlemler</th>
              </tr>
            </thead>
            <tbody>
              ${S.media.items.map((it, idx) => {
                const isChecked = checkedSet.has(it.url);
                const deletable = isDeletableUpload(it.url);
                const isNew = recentSet.has(it.url);
                const dimStr = (it.width && it.height) ? `${it.width} × ${it.height}` : (it.ext === 'svg' ? 'Vektör' : '—');
                return `
                  <tr data-media-idx="${idx}" style="cursor:pointer">
                    <td>
                      ${deletable
                        ? `<button type="button" class="zs-media-check" style="position:static;opacity:1;${isChecked ? 'background:#10b981;border-color:#6ee7b7;color:#021410' : ''}" data-check-idx="${idx}">${isChecked ? '✓' : ''}</button>`
                        : `<span class="zs-pill zs-pill-cat" style="font-size:9.5px">TEMA</span>`
                      }
                    </td>
                    <td>
                      <img src="${esc(resolvePreviewUrl(it.url, it.mtime))}" alt="${esc(it.alt || it.name)}" loading="lazy" style="width:58px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--border-subtle)"/>
                    </td>
                    <td>
                      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <strong style="color:#fff;font-size:13px">${esc(it.name)}</strong>
                        ${isNew ? `<span class="zs-pill zs-pill-pub" style="font-size:9.5px;padding:1px 6px">YENİ</span>` : ''}
                      </div>
                      <span style="font-size:11px;color:var(--text-dim)">/${esc(it.folder)}</span>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">${dimStr}</td>
                    <td><span class="zs-pill zs-pill-cat">${esc(it.ext.toUpperCase())}</span> <span style="font-size:12px;color:var(--text-muted);margin-left:4px">${fmtBytes(it.size)}</span></td>
                    <td style="font-size:12px;color:${it.alt ? 'var(--text-main)' : '#fbbf24'}">${it.alt ? esc(it.alt) : '⚠ Alt metni boş'}</td>
                    <td style="text-align:right;white-space:nowrap">
                      ${it.ext !== 'svg' ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-quick-canvas="${idx}">🎨 Yazı Yaz</button>` : ''}
                      <button type="button" class="zs-btn zs-btn-sm" data-quick-copy="${idx}">🔗 Kopyala</button>
                      <button type="button" class="zs-btn zs-btn-sm" data-quick-zoom="${idx}">🔍 İncele</button>
                    </td>
                  </tr>
                `;
              }).join('')}
            </tbody>
          </table>
        </div>
      `;
    } else {
      const densityCls = vMode === 'large' ? 'density-large' : (vMode === 'compact' ? 'density-compact' : '');
      gridSlot.innerHTML = `
        <div class="zs-media-grid ${densityCls} ${hasBulk ? 'has-bulk' : ''}" id="mediaGridEl">
          ${S.media.items.map((it, idx) => {
            const isChecked = checkedSet.has(it.url);
            const isCurrent = sel && sel.url === it.url;
            const deletable = isDeletableUpload(it.url);
            const isNew = recentSet.has(it.url);
            const dimStr = (it.width && it.height) ? `${it.width}×${it.height} · ` : '';
            return `
              <div class="zs-media-card ${isCurrent ? 'selected' : ''} ${isChecked ? 'bulk-checked' : ''}" data-media-idx="${idx}">
                ${deletable
                  ? `<button type="button" class="zs-media-check" data-check-idx="${idx}" title="Çoklu seçime ekle / çıkar (Shift+Tık ile aralık seç)">${isChecked ? '✓' : ''}</button>`
                  : `<span class="zs-media-lock-badge" title="Çekirdek tema görseli korunmaktadır">TEMA</span>`
                }
                ${isNew ? `<span class="zs-media-new-badge">YENİ</span>` : ''}
                <div class="zs-media-card-actions">
                  ${it.ext !== 'svg' ? `<button type="button" class="zs-media-quick-btn" data-quick-canvas="${idx}" title="Görsel Üzerine Başlık / Marka / Domain Yaz">🎨</button>` : ''}
                  <button type="button" class="zs-media-quick-btn" data-quick-copy="${idx}" title="Görsel URL Adresini Kopyala">🔗</button>
                  <button type="button" class="zs-media-quick-btn" data-quick-zoom="${idx}" title="Tam Ekran Büyüt &amp; SEO">🔍</button>
                </div>
                <div class="zs-media-thumb"><img src="${esc(resolvePreviewUrl(it.url, it.mtime))}" alt="${esc(it.alt || it.name)}" loading="lazy" decoding="async"/></div>
                <div class="zs-media-meta">
                  <strong title="${esc(it.name)}">${esc(it.name)}</strong>
                  <span>${dimStr}${esc(it.ext.toUpperCase())} · ${fmtBytes(it.size)}</span>
                </div>
              </div>
            `;
          }).join('')}
        </div>
      `;
    }

    gridSlot.querySelectorAll('[data-check-idx]').forEach(chkBtn => {
      chkBtn.onclick = ev => {
        ev.stopPropagation();
        const idx = Number(chkBtn.getAttribute('data-check-idx'));
        toggleMediaBulkItem(idx, ev.shiftKey);
      };
    });

    gridSlot.querySelectorAll('[data-quick-copy]').forEach(cpBtn => {
      cpBtn.onclick = ev => {
        ev.stopPropagation();
        const idx = Number(cpBtn.getAttribute('data-quick-copy'));
        const item = S.media.items[idx];
        if (item) copyTextSafe(item.url, 'Görsel adresi kopyalandı: ' + item.url);
      };
    });

    gridSlot.querySelectorAll('[data-quick-zoom]').forEach(zmBtn => {
      zmBtn.onclick = ev => {
        ev.stopPropagation();
        const idx = Number(zmBtn.getAttribute('data-quick-zoom'));
        openMediaLightbox(idx);
      };
    });

    gridSlot.querySelectorAll('[data-quick-canvas]').forEach(cvBtn => {
      cvBtn.onclick = ev => {
        ev.stopPropagation();
        const idx = Number(cvBtn.getAttribute('data-quick-canvas'));
        const item = S.media.items[idx];
        if (item) openImageCanvasStudio(item);
      };
    });

    gridSlot.querySelectorAll('[data-media-idx]').forEach(card => {
      card.onclick = ev => {
        const idx = Number(card.getAttribute('data-media-idx'));
        if (S.media.bulkMode || ev.shiftKey || ev.ctrlKey || ev.metaKey) {
          toggleMediaBulkItem(idx, ev.shiftKey);
          return;
        }
        openMediaLightbox(idx);
      };
    });

    const pagSlot = document.getElementById('mediaPaginationSlot');
    if (pagSlot) {
      pagSlot.innerHTML = `
        <span style="font-size:12.5px;color:var(--text-muted)">Filtrelenen ${Number(S.media.total).toLocaleString('tr-TR')} görsel · Sayfa ${S.media.page} / ${S.media.totalPages}</span>
        <div style="display:flex;gap:8px">
          <button type="button" class="zs-btn zs-btn-sm" id="mediaPrevBtn" ${S.media.page <= 1 ? 'disabled' : ''}>← Önceki</button>
          <button type="button" class="zs-btn zs-btn-sm" id="mediaNextBtn" ${S.media.page >= S.media.totalPages ? 'disabled' : ''}>Sonraki →</button>
        </div>
      `;
      document.getElementById('mediaPrevBtn').onclick = () => { S.media.page--; refreshMediaGridOnly(); };
      document.getElementById('mediaNextBtn').onclick = () => { S.media.page++; refreshMediaGridOnly(); };
    }

    paintMediaBulkBar();
  }

  async function refreshMediaGridOnly() {
    const gridSlot = document.getElementById('mediaGridSlot');
    if (gridSlot) {
      gridSlot.style.opacity = '0.55';
      gridSlot.style.pointerEvents = 'none';
    }
    try {
      const data = await api('media_list', {
        folder: S.media.folder,
        type_filter: S.media.typeFilter || 'all',
        sort: S.media.sort || 'newest',
        search: S.media.search,
        page: S.media.page,
        per_page: 48
      });
      S.media.items = data.items || [];
      S.media.total = data.total || 0;
      S.media.page = data.page || 1;
      S.media.totalPages = data.total_pages || 1;
      S.media.folders = data.folders || {};
      if (data.stats) S.media.stats = data.stats;
      S.media.lastClickedIdx = null;
      if (S.summary) S.summary.media_total = S.media.folders.all || S.media.total;
      updateSidebarBadges();
      paintMediaStatsStrip();
      paintMediaFolderOptions();
      paintMediaGridDom();
      paintMediaSidePanel();
    } catch (err) {
      toast(err.message, true);
    } finally {
      if (gridSlot) {
        gridSlot.style.opacity = '1';
        gridSlot.style.pointerEvents = '';
      }
    }
  }

  async function triggerMainMediaUpload(fileList) {
    const deckSlot = document.getElementById('mediaUploadDeckSlot');
    if (!deckSlot) return;
    await runStudioUploadQueue(fileList, deckSlot, {
      pickerMode: false,
      onInspectUploaded: uploadedItem => {
        const idx = S.media.items.findIndex(x => x.url === uploadedItem.url);
        if (idx >= 0) openMediaLightbox(idx);
      },
      onComplete: async results => {
        if (results.length > 0) {
          S.media.page = 1;
          S.media.sort = 'newest';
          const sortSel = document.getElementById('mediaSortSel');
          if (sortSel) sortSel.value = 'newest';
          await refreshMediaGridOnly();
        }
      }
    });
  }

  async function renderMediaView() {
    syncAdminUrl('replace');
    const _nd = new Date();
    const targetFolderLabel = `/uploads/${_nd.getFullYear()}/${String(_nd.getMonth() + 1).padStart(2, '0')}/${String(_nd.getDate()).padStart(2, '0')}/`;
    const curType = S.media.typeFilter || 'all';
    const curSort = S.media.sort || 'newest';
    const curView = S.media.viewMode || 'grid';

    ROOT.innerHTML = `
      <div id="mediaStatsSlot"></div>

      <div class="zs-card">
        <div class="zs-upload-studio-hero" id="mediaMainDrop">
          <input type="file" id="mediaMainFileInp" multiple accept="image/*" style="display:none"/>
          <div class="zs-upload-hero-left">
            <div class="zs-upload-orb" aria-hidden="true">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="zs-upload-hero-copy">
              <div class="zs-upload-hero-badges">
                <span class="zs-badge-pill">${targetFolderLabel}</span>
                <span class="zs-kbd-pill">📋 Ctrl + V Panodan Yapıştır</span>
                <span class="zs-kbd-pill">⚡ Paralel Çoklu Yükleme &amp; Canlı Bar</span>
              </div>
              <h2>Görselleri Sürükleyip Bırakın veya Çoklu Olarak Seçin</h2>
              <p>Aynı anda 5, 10 veya 20 görsel seçebilirsiniz — her görsel önizlemesi, canlı aktarım barı ve WebP küçültme oranıyla işlenir (Maks. 15 MB / dosya).</p>
            </div>
          </div>
          <div class="zs-upload-hero-cta">
            <button type="button" class="zs-btn zs-btn-primary" id="mediaBrowseFilesBtn">+ Bilgisayardan Görsel Seç</button>
          </div>
        </div>

        <div class="zs-upload-tuning-bar">
          <div class="zs-upload-tuning-group">
            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="mediaWebpToggle" ${S.media.optimizeWebp ? 'checked' : ''}/>
              <span>Otomatik <strong>WebP Dönüştürme</strong></span>
            </label>
            <span class="zs-tuning-chip">
              <span>Kalite:</span>
              <select id="mediaQualitySel">
                <option value="86" ${S.media.webpQuality === 86 ? 'selected' : ''}>%86 Dengeli (Önerilen)</option>
                <option value="92" ${S.media.webpQuality === 92 ? 'selected' : ''}>%92 Ultra Net</option>
                <option value="76" ${S.media.webpQuality === 76 ? 'selected' : ''}>%76 Maks. Sıkıştırma</option>
              </select>
            </span>
            <span class="zs-tuning-chip">
              <span>Maks. Genişlik:</span>
              <select id="mediaMaxWidthSel">
                <option value="1920" ${S.media.maxWidth === 1920 ? 'selected' : ''}>1920px (Full HD)</option>
                <option value="2560" ${S.media.maxWidth === 2560 ? 'selected' : ''}>2560px (2K Retina)</option>
                <option value="1280" ${S.media.maxWidth === 1280 ? 'selected' : ''}>1280px (Hızlı Blog)</option>
                <option value="0" ${S.media.maxWidth === 0 ? 'selected' : ''}>Orijinal Boyut</option>
              </select>
            </span>
            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="mediaAutoAltToggle" ${S.media.autoAlt !== false ? 'checked' : ''}/>
              <span>Oto-SEO Alt Etiketi</span>
            </label>
          </div>
        </div>

        ${(() => {
          const ms = Object.assign({
            enabled: false,
            text: `${S.store?.site?.name || 'ZEYNART'} · zeynart.com`,
            position: 'bottom-right',
            style: 'glass-pill',
            opacity: 88,
            scale: 100
          }, S.store?.media_stamp || {});
          S.store.media_stamp = ms;
          return `
            <div class="zs-stamp-bar">
              <div class="zs-stamp-bar-head">
                <label class="zs-switch" style="margin:0">
                  <input type="checkbox" id="msEnabledChk" ${ms.enabled ? 'checked' : ''}/>
                  <span>🏷️ <strong>Yüklenen Her Görsele Otomatik Marka / Domain / Başlık Yaz</strong> (Sunucu GD Motoru)</span>
                </label>
                <div class="zs-token-chips">
                  <span style="font-size:11px;color:var(--text-dim)">Dinamik Etiket Ekle:</span>
                  <button type="button" class="zs-token-chip" data-ms-token="{marka}">+ {marka}</button>
                  <button type="button" class="zs-token-chip" data-ms-token="{domain}">+ {domain}</button>
                  <button type="button" class="zs-token-chip" data-ms-token="{baslik}">+ {baslik} (Dosya Başlığı)</button>
                </div>
              </div>
              <div class="zs-stamp-controls">
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11px">Görsel Üzerine Yazılacak İmza / Başlık Metni</label>
                  <input id="msTextInp" class="zs-input" placeholder="{marka} · {domain}" value="${esc(ms.text || '')}"/>
                </div>
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11px">Konum</label>
                  <select id="msPosSel" class="zs-select">
                    <option value="bottom-right" ${ms.position === 'bottom-right' ? 'selected' : ''}>↘ Sağ Alt Köşe</option>
                    <option value="bottom-left" ${ms.position === 'bottom-left' ? 'selected' : ''}>↙ Sol Alt Köşe</option>
                    <option value="top-right" ${ms.position === 'top-right' ? 'selected' : ''}>↗ Sağ Üst Köşe</option>
                    <option value="top-left" ${ms.position === 'top-left' ? 'selected' : ''}>↖ Sol Üst Köşe</option>
                    <option value="bottom-bar" ${ms.position === 'bottom-bar' ? 'selected' : ''}>▬ Tam Alt Bant</option>
                    <option value="center-watermark" ${ms.position === 'center-watermark' ? 'selected' : ''}>• Orta Filigran</option>
                  </select>
                </div>
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11px">Görünüm Stili</label>
                  <select id="msStyleSel" class="zs-select">
                    <option value="glass-pill" ${ms.style === 'glass-pill' ? 'selected' : ''}>Buzlu Cam Kapsül</option>
                    <option value="brand-pill" ${ms.style === 'brand-pill' ? 'selected' : ''}>Marka Renkli Kapsül</option>
                    <option value="clean-shadow" ${ms.style === 'clean-shadow' ? 'selected' : ''}>Gölgeli Düz Yazı</option>
                    <option value="watermark" ${ms.style === 'watermark' ? 'selected' : ''}>Çapraz Filigran</option>
                  </select>
                </div>
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11px">Boyut Ölçeği</label>
                  <select id="msScaleSel" class="zs-select">
                    <option value="80" ${Number(ms.scale) === 80 ? 'selected' : ''}>%80 Zarif Küçük</option>
                    <option value="100" ${Number(ms.scale || 100) === 100 ? 'selected' : ''}>%100 Dengeli</option>
                    <option value="130" ${Number(ms.scale) === 130 ? 'selected' : ''}>%130 Belirgin Büyük</option>
                  </select>
                </div>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="msSaveStampBtn" style="height:38px">✓ Damga Ayarını Kaydet</button>
              </div>
            </div>
          `;
        })()}

        <div id="mediaUploadDeckSlot"></div>
      </div>

      <div class="zs-media-workspace" id="mediaWorkspaceEl">
        <div class="zs-card">
          <div class="zs-media-filter-bar">
            <div class="zs-media-type-pills" id="mediaTypePills">
              <button type="button" class="zs-type-pill ${curType === 'all' ? 'active' : ''}" data-mtype="all">Tümü</button>
              <button type="button" class="zs-type-pill ${curType === 'uploads' ? 'active' : ''}" data-mtype="uploads">Yüklenenler (/uploads)</button>
              <button type="button" class="zs-type-pill ${curType === 'images' ? 'active' : ''}" data-mtype="images">Tema (/images)</button>
              <button type="button" class="zs-type-pill ${curType === 'webp' ? 'active' : ''}" data-mtype="webp">WebP &amp; AVIF</button>
              <button type="button" class="zs-type-pill ${curType === 'raster' ? 'active' : ''}" data-mtype="raster">JPG &amp; PNG</button>
              <button type="button" class="zs-type-pill ${curType === 'svg' ? 'active' : ''}" data-mtype="svg">SVG</button>
              <button type="button" class="zs-type-pill ${curType === 'missing_alt' ? 'active' : ''}" data-mtype="missing_alt">⚠ Alt Eksik</button>
            </div>

            <div class="zs-view-mode-group" id="mediaViewModeGroup">
              <button type="button" class="zs-view-mode-btn ${curView === 'grid' ? 'active' : ''}" data-mview="grid" title="Standart Izgara">⊞ Izgara</button>
              <button type="button" class="zs-view-mode-btn ${curView === 'large' ? 'active' : ''}" data-mview="large" title="Büyük Galeri Kartları">▣ Galeri</button>
              <button type="button" class="zs-view-mode-btn ${curView === 'compact' ? 'active' : ''}" data-mview="compact" title="Yoğun Kompakt Görünüm">∷ Yoğun</button>
              <button type="button" class="zs-view-mode-btn ${curView === 'list' ? 'active' : ''}" data-mview="list" title="Detaylı Tablo Listesi">☰ Liste</button>
            </div>
          </div>

          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
            <select id="mediaFolderSel" class="zs-select" style="max-width:250px"></select>
            <select id="mediaSortSel" class="zs-select" style="max-width:190px">
              <option value="newest" ${curSort === 'newest' ? 'selected' : ''}>Sıralama: En Yeni</option>
              <option value="oldest" ${curSort === 'oldest' ? 'selected' : ''}>Sıralama: En Eski</option>
              <option value="largest" ${curSort === 'largest' ? 'selected' : ''}>Boyut: Büyük → Küçük</option>
              <option value="smallest" ${curSort === 'smallest' ? 'selected' : ''}>Boyut: Küçük → Büyük</option>
              <option value="name" ${curSort === 'name' ? 'selected' : ''}>İsim: A → Z</option>
            </select>
            <input id="mediaSearchInp" class="zs-input" style="flex:1;min-width:220px" placeholder="Görsel adı, klasör veya SEO alt metni ara..." value="${esc(S.media.search)}"/>
            <button type="button" class="zs-btn" id="mediaSearchBtn">Ara</button>
          </div>

          <div id="mediaBulkBarSlot"></div>
          <div id="mediaGridSlot">
            <p style="color:var(--text-muted);padding:18px 4px">Medya arşivi yükleniyor...</p>
          </div>
          <div id="mediaPaginationSlot" style="display:flex;justify-content:space-between;align-items:center;margin-top:18px"></div>
        </div>
        <div id="mediaSidePanelSlot"></div>
      </div>
    `;

    const drop = document.getElementById('mediaMainDrop');
    const browseBtn = document.getElementById('mediaBrowseFilesBtn');
    const inp = document.getElementById('mediaMainFileInp');

    document.getElementById('mediaWebpToggle').onchange = e => { S.media.optimizeWebp = e.target.checked; };
    document.getElementById('mediaQualitySel').onchange = e => { S.media.webpQuality = Number(e.target.value || 86); };
    document.getElementById('mediaMaxWidthSel').onchange = e => { S.media.maxWidth = Number(e.target.value ?? 1920); };
    document.getElementById('mediaAutoAltToggle').onchange = e => { S.media.autoAlt = e.target.checked; };

    const syncLocalMediaStamp = () => {
      S.store.media_stamp = Object.assign(S.store.media_stamp || {}, {
        enabled: Boolean(document.getElementById('msEnabledChk')?.checked),
        text: (document.getElementById('msTextInp')?.value || '').trim(),
        position: document.getElementById('msPosSel')?.value || 'bottom-right',
        style: document.getElementById('msStyleSel')?.value || 'glass-pill',
        scale: Number(document.getElementById('msScaleSel')?.value || 100)
      });
    };
    ['msEnabledChk', 'msTextInp', 'msPosSel', 'msStyleSel', 'msScaleSel'].forEach(id => {
      document.getElementById(id)?.addEventListener('change', syncLocalMediaStamp);
      document.getElementById(id)?.addEventListener('input', syncLocalMediaStamp);
    });
    ROOT.querySelectorAll('[data-ms-token]').forEach(chip => {
      chip.onclick = () => {
        const inpEl = document.getElementById('msTextInp');
        if (!inpEl) return;
        const tok = chip.getAttribute('data-ms-token') || '';
        inpEl.value = inpEl.value.trim() ? `${inpEl.value.trim()} · ${tok}` : tok;
        syncLocalMediaStamp();
        inpEl.focus();
      };
    });
    document.getElementById('msSaveStampBtn')?.addEventListener('click', async () => {
      syncLocalMediaStamp();
      try {
        const res = await api('media_stamp_save', { media_stamp: S.store.media_stamp });
        if (res.media_stamp) S.store.media_stamp = res.media_stamp;
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      }
    });

    drop.onclick = ev => {
      if (ev.target === browseBtn || browseBtn.contains(ev.target)) return;
      inp.click();
    };
    browseBtn.onclick = ev => { ev.stopPropagation(); inp.click(); };
    drop.ondragover = e => { e.preventDefault(); drop.classList.add('drag-over'); };
    drop.ondragleave = () => drop.classList.remove('drag-over');
    drop.ondrop = e => {
      e.preventDefault();
      drop.classList.remove('drag-over');
      if (e.dataTransfer.files.length) triggerMainMediaUpload(e.dataTransfer.files);
    };
    inp.onchange = () => {
      if (inp.files.length) triggerMainMediaUpload(inp.files);
      inp.value = '';
    };

    ROOT.querySelectorAll('[data-mtype]').forEach(btn => {
      btn.onclick = () => {
        S.media.typeFilter = btn.getAttribute('data-mtype') || 'all';
        S.media.page = 1;
        ROOT.querySelectorAll('[data-mtype]').forEach(b => b.classList.toggle('active', b === btn));
        refreshMediaGridOnly();
      };
    });

    ROOT.querySelectorAll('[data-mview]').forEach(btn => {
      btn.onclick = () => {
        S.media.viewMode = btn.getAttribute('data-mview') || 'grid';
        ROOT.querySelectorAll('[data-mview]').forEach(b => b.classList.toggle('active', b === btn));
        paintMediaGridDom();
      };
    });

    document.getElementById('mediaFolderSel').onchange = e => {
      S.media.folder = e.target.value;
      S.media.page = 1;
      refreshMediaGridOnly();
    };
    document.getElementById('mediaSortSel').onchange = e => {
      S.media.sort = e.target.value || 'newest';
      S.media.page = 1;
      refreshMediaGridOnly();
    };
    const runMediaSearch = () => {
      S.media.search = document.getElementById('mediaSearchInp').value.trim();
      S.media.page = 1;
      refreshMediaGridOnly();
    };
    document.getElementById('mediaSearchBtn').onclick = runMediaSearch;
    document.getElementById('mediaSearchInp').onkeydown = e => { if (e.key === 'Enter') runMediaSearch(); };

    await refreshMediaGridOnly();
  }

  function renderMenusView() {
    syncAdminUrl('replace');
    const menus = S.store.menus || { header: { items: [] }, footer: { columns: [], legal_links: [] }, typography: {} };
    const hdr = menus.header || { items: [] };
    const ftr = menus.footer || { columns: [], legal_links: [] };
    const typo = Object.assign(getDefaultMenuTypography(), menus.typography || {});
    menus.typography = typo;
    hdr.nav_align = ['left', 'center', 'right'].includes(hdr.nav_align) ? hdr.nav_align : 'left';
    hdr.element_order = Array.isArray(hdr.element_order) && hdr.element_order.length === 3 ? hdr.element_order : ['brand', 'nav', 'actions'];
    ftr.block_order = Array.isArray(ftr.block_order) && ftr.block_order.length === 3 ? ftr.block_order : ['intro', 'menus', 'contact'];
    const cta = S.store.cta_global || {};
    const tab = S.tabs.menus || 'header';
    const fontPrev = tab === 'typography' ? resolveAdminFontPreview(typo) : { body: { family: 'Manrope', stack: '"Manrope", sans-serif' }, heading: { family: 'Manrope', stack: '"Manrope", sans-serif' } };

    const allSitePages = buildUnifiedPagesCatalog();
    const quickRoutes = allSitePages.map(p => ({ label: p.title, url: p.url, desc: `${p.title} (${p.url})` }));

    const hdrBlockLabels = {
      brand: 'Marka Logosu',
      nav: 'Üst Menü Linkleri',
      actions: 'Sağ Buton & Dil'
    };
    const ftrBlockLabels = {
      intro: 'Marka & Slogan Bloğu',
      menus: `Menü Sütunları (${(ftr.columns || []).length} Grup)`,
      contact: 'İletişim & Adres Bloğu'
    };

    ROOT.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        <div class="zs-tabs" style="margin-bottom:0">
          <button type="button" class="zs-tab ${tab === 'header' ? 'active' : ''}" data-mtab="header">🧭 Üst Menü &amp; Yerleşim (Header)</button>
          <button type="button" class="zs-tab ${tab === 'footer' ? 'active' : ''}" data-mtab="footer">📑 Alt Menü Grupları &amp; Yer Değiştir (Footer)</button>
          <button type="button" class="zs-tab ${tab === 'typography' ? 'active' : ''}" data-mtab="typography">🔤 Google Fonts, Yazı Tipleri &amp; Boyutlar</button>
          <button type="button" class="zs-tab ${tab === 'cta' ? 'active' : ''}" data-mtab="cta">📣 Global Çağrı Bandı (CTA)</button>
        </div>
        <button type="button" class="zs-btn zs-btn-primary" id="saveAllMenusBtn">✓ Menü & Font Ayarlarını Kaydet</button>
      </div>

      ${tab === 'header' ? `
        <div class="zs-card" style="margin-bottom:18px">
          <div class="zs-grid-2">
            <div>
              <h3 style="margin:0 0 6px">Üst Menü Hizalaması</h3>
              <p class="zs-card-sub" style="margin-bottom:12px">Üst menü linklerinin header şeridindeki konumunu tek tıkla belirleyin.</p>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="button" class="zs-btn zs-btn-sm ${hdr.nav_align === 'left' ? 'zs-btn-primary' : ''}" data-hdr-align="left">⇤ Logoya Yakın (Sol)</button>
                <button type="button" class="zs-btn zs-btn-sm ${hdr.nav_align === 'center' ? 'zs-btn-primary' : ''}" data-hdr-align="center">⇔ Tam Ortalanmış (Merkez)</button>
                <button type="button" class="zs-btn zs-btn-sm ${hdr.nav_align === 'right' ? 'zs-btn-primary' : ''}" data-hdr-align="right">⇥ Butona Yakın (Sağ)</button>
              </div>
            </div>
            <div>
              <h3 style="margin:0 0 6px">Header Blok Dizilimi (Sol ⇄ Sağ Yer Değiştir)</h3>
              <p class="zs-card-sub" style="margin-bottom:12px">Logo, Üst Menü ve Sağ Aksiyon Butonu bloklarının sırasını değiştirebilirsiniz.</p>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                ${hdr.element_order.map((ek, eIdx) => `
                  <div style="display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:10px;background:rgba(12,28,35,.9);border:1px solid var(--border-strong);font-size:12.5px">
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-hel-move="${eIdx}:-1" ${eIdx === 0 ? 'disabled' : ''}>←</button>
                    <strong>${esc(hdrBlockLabels[ek] || ek)}</strong>
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-hel-move="${eIdx}:1" ${eIdx === hdr.element_order.length - 1 ? 'disabled' : ''}>→</button>
                  </div>
                `).join('')}
              </div>
            </div>
          </div>
        </div>

        <div class="zs-grid-2">
          <div class="zs-card">
            <div class="zs-card-head">
              <div>
                <h3>Üst Header Menü Bağlantıları (${(hdr.items || []).length})</h3>
                <p class="zs-card-sub">Satırları sürükleyip bırakarak veya ↑/↓ oklarıyla yer değiştirin, yeni menü ekleyin.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="hdrAddItemBtn">+ Yeni Menü Ekle</button>
            </div>

            <div style="display:flex;gap:8px;align-items:center;padding:10px 12px;margin-bottom:14px;border-radius:11px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.22);flex-wrap:wrap">
              <span style="font-size:12px;font-weight:700;color:var(--accent-bright)">📄 Sitedeki Sayfalardan Hızlı Ekle:</span>
              <select id="hdrQuickPageSel" class="zs-select" style="flex:1;min-width:190px;padding:6px 10px">
                ${quickRoutes.map((r, rIdx) => `<option value="${rIdx}">${esc(r.desc)}</option>`).join('')}
              </select>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="hdrQuickPageAddBtn">+ Üst Menüye Ekle</button>
            </div>

            <div id="hdrItemsWrap">
              ${(hdr.items || []).map((it, idx) => `
                <div class="zs-sort-row zs-menu-dnd-row" draggable="true" data-hdr-idx="${idx}">
                  <span class="zs-drag-grip" title="Sürükleyerek yer değiştir">⋮⋮</span>
                  <div style="display:flex;flex-direction:column;gap:2px">
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-hdr-move="${idx}:-1" ${idx === 0 ? 'disabled' : ''}>↑</button>
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-hdr-move="${idx}:1" ${idx === hdr.items.length - 1 ? 'disabled' : ''}>↓</button>
                  </div>
                  <input class="zs-input hdr-lbl" style="max-width:160px" placeholder="Menü Başlığı" value="${esc(it.label)}"/>
                  <input class="zs-input hdr-url" list="quickRoutesList" placeholder="/sayfa-adresi/" value="${esc(it.url)}"/>
                  <select class="zs-select hdr-target" style="max-width:120px">
                    <option value="_self" ${it.target !== '_blank' ? 'selected' : ''}>Aynı Sekme</option>
                    <option value="_blank" ${it.target === '_blank' ? 'selected' : ''}>Yeni Sekme</option>
                  </select>
                  <label class="zs-switch" title="Göster / Gizle"><input type="checkbox" class="hdr-enabled" ${it.enabled !== false ? 'checked' : ''}/></label>
                  <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-hdr-del="${idx}" title="Menüden Kaldır">✕</button>
                </div>
              `).join('')}
            </div>
            <datalist id="quickRoutesList">
              ${quickRoutes.map(r => `<option value="${esc(r.url)}">${esc(r.desc)}</option>`).join('')}
            </datalist>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Sağ Aksiyon Butonu, Dil Rozeti & Mobil Menü</h3>
              <button type="button" class="zs-btn zs-btn-sm" id="hdrJumpTypoBtn">🔤 Yazı Boyutlarını Büyüt/Küçült →</button>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Sağ CTA Buton Metni</label><input id="hdrCtaLabel" class="zs-input" value="${esc(hdr.cta_label || 'Birlikte çalışalım')}"/></div>
              <div class="zs-field"><label>Sağ CTA Buton Linki</label><input id="hdrCtaUrl" list="quickRoutesList" class="zs-input" value="${esc(hdr.cta_url || '/iletisim/')}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Dil Rozeti Metni</label><input id="hdrLangBadge" class="zs-input" value="${esc(hdr.language_badge || 'TR')}"/></div>
              <div class="zs-field"><label>Logo Erişilebilirlik Etiketi</label><input id="hdrLogoAria" class="zs-input" value="${esc(hdr.logo_aria || '')}"/></div>
            </div>
            <div class="zs-field">
              <label>Mobil Menü Üst Başlığı</label>
              <input id="hdrMobileEyebrow" class="zs-input" value="${esc(hdr.mobile_eyebrow || '')}"/>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px">
              <label class="zs-switch"><input type="checkbox" id="hdrShowCta" ${hdr.show_cta !== false ? 'checked' : ''}/><span>Sağ üst aksiyon butonunu göster</span></label>
              <label class="zs-switch"><input type="checkbox" id="hdrShowLang" ${hdr.show_language !== false ? 'checked' : ''}/><span>Dil rozetini (TR) göster</span></label>
            </div>
          </div>
        </div>
      ` : ''}

      ${tab === 'footer' ? `
        <div class="zs-card" style="margin-bottom:18px">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
            <div>
              <h3 style="margin:0 0 4px">Footer Ana Bölüm Sıralaması (Sol ⇄ Sağ Yer Değiştir)</h3>
              <p class="zs-card-sub">Footer üst alanındaki Marka Bloğu, Menü Sütunları ve İletişim Bloğunun yerlerini değiştirebilirsiniz.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              ${ftr.block_order.map((bk, bIdx) => `
                <div style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:10px;background:rgba(12,28,35,.92);border:1px solid var(--border-strong);font-size:12.5px">
                  <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 7px" data-fblk-move="${bIdx}:-1" ${bIdx === 0 ? 'disabled' : ''}>←</button>
                  <strong>${esc(ftrBlockLabels[bk] || bk)}</strong>
                  <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 7px" data-fblk-move="${bIdx}:1" ${bIdx === ftr.block_order.length - 1 ? 'disabled' : ''}>→</button>
                </div>
              `).join('')}
            </div>
          </div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Footer Menü Grupları / Sütunları (${(ftr.columns || []).length} Menü Grubu)</h3>
              <p class="zs-card-sub">İstediğiniz kadar yeni footer menü grubu ekleyebilir, sütunların ve içindeki linklerin yerini sürükleyerek veya oklarla değiştirebilirsiniz.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-sm" id="ftrJumpTypoBtn">🔤 Footer Yazı Boyutları</button>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="ftrAddColBtn">+ Yeni Footer Menü Grubu Ekle</button>
            </div>
          </div>

          <div class="zs-grid-2" id="ftrColsWrap">
            ${(ftr.columns || []).map((col, cIdx) => `
              <div class="zs-card zs-menu-dnd-row" draggable="true" style="background:rgba(6,13,17,.82);margin-bottom:0;border-color:rgba(94,234,212,.2)" data-fcol="${cIdx}">
                <div style="display:flex;gap:6px;align-items:center;margin-bottom:12px;flex-wrap:wrap">
                  <span class="zs-drag-grip" title="Sütunu sürükleyerek taşı">⋮⋮</span>
                  <button type="button" class="zs-btn zs-btn-sm" style="padding:4px 8px" data-fcol-move="${cIdx}:-1" ${cIdx === 0 ? 'disabled' : ''} title="Sütunu Sola Taşı">←</button>
                  <button type="button" class="zs-btn zs-btn-sm" style="padding:4px 8px" data-fcol-move="${cIdx}:1" ${cIdx === ftr.columns.length - 1 ? 'disabled' : ''} title="Sütunu Sağa Taşı">→</button>
                  <input class="zs-input fcol-title" style="font-weight:700;flex:1;min-width:130px" placeholder="Menü Grubu Başlığı" value="${esc(col.title)}"/>
                  <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-fcol-add-link="${cIdx}">+ Yeni Link</button>
                  <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-fcol-del="${cIdx}" title="Bu Menü Grubunu Sil">Sil</button>
                </div>

                <div style="display:flex;gap:6px;align-items:center;margin-bottom:10px">
                  <select class="zs-select" data-fcol-page-sel="${cIdx}" style="flex:1;font-size:12px;padding:5px 8px">
                    ${quickRoutes.map((r, rIdx) => `<option value="${rIdx}">${esc(r.desc)}</option>`).join('')}
                  </select>
                  <button type="button" class="zs-btn zs-btn-sm" data-fcol-add-page="${cIdx}">📄 Sayfayı Ekle</button>
                </div>

                <div>
                  ${(col.links || []).length === 0 ? `<div style="padding:12px;border-radius:9px;background:rgba(255,255,255,.03);color:var(--text-dim);font-size:12px">Henüz link yok. Yukarıdan sayfa seçip ekleyin veya "+ Yeni Link"e basın.</div>` : ''}
                  ${(col.links || []).map((lnk, lIdx) => `
                    <div class="zs-sort-row zs-menu-dnd-row" draggable="true" style="padding:8px 10px" data-flink="${cIdx}:${lIdx}">
                      <span class="zs-drag-grip" title="Linki sürükle">⋮⋮</span>
                      <div style="display:flex;flex-direction:column;gap:2px">
                        <button type="button" class="zs-btn zs-btn-sm" style="padding:1px 5px;font-size:10px" data-flink-move="${cIdx}:${lIdx}:-1" ${lIdx === 0 ? 'disabled' : ''}>↑</button>
                        <button type="button" class="zs-btn zs-btn-sm" style="padding:1px 5px;font-size:10px" data-flink-move="${cIdx}:${lIdx}:1" ${lIdx === col.links.length - 1 ? 'disabled' : ''}>↓</button>
                      </div>
                      <input class="zs-input flnk-lbl" placeholder="Başlık" value="${esc(lnk.label)}"/>
                      <input class="zs-input flnk-url" list="quickRoutesList" placeholder="/adres/" value="${esc(lnk.url)}"/>
                      <select class="zs-select flnk-target" style="max-width:92px;padding:6px 6px;font-size:11.5px">
                        <option value="_self" ${lnk.target !== '_blank' ? 'selected' : ''}>Aynı</option>
                        <option value="_blank" ${lnk.target === '_blank' ? 'selected' : ''}>Yeni ↗</option>
                      </select>
                      ${ftr.columns.length > 1 ? `<button type="button" class="zs-btn zs-btn-sm" style="padding:4px 7px" data-flink-shiftcol="${cIdx}:${lIdx}" title="Diğer Footer Sütununa Aktar">⇄</button>` : ''}
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" style="padding:4px 7px" data-flink-del="${cIdx}:${lIdx}">✕</button>
                    </div>
                  `).join('')}
                </div>
              </div>
            `).join('')}
          </div>
          <datalist id="quickRoutesList">
            ${quickRoutes.map(r => `<option value="${esc(r.url)}">${esc(r.desc)}</option>`).join('')}
          </datalist>
        </div>

        <div class="zs-grid-2">
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Footer Marka Sloganı & İletişim Bloğu</h3>
            <div class="zs-field"><label>Tanıtım Sloganı</label><textarea id="ftrIntro" class="zs-textarea" style="min-height:66px">${esc(ftr.intro_text || '')}</textarea></div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Yeşil Müsaitlik Rozeti</label><input id="ftrAvail" class="zs-input" value="${esc(ftr.availability_text || '')}"/></div>
              <div class="zs-field" style="justify-content:flex-end"><label class="zs-switch"><input type="checkbox" id="ftrShowAvail" ${ftr.show_availability !== false ? 'checked' : ''}/><span>Rozeti Göster</span></label></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>İletişim Başlığı</label><input id="ftrContactH" class="zs-input" value="${esc(ftr.contact_heading || '')}"/></div>
              <div class="zs-field"><label>İletişim Link Metni</label><input id="ftrContactLbl" class="zs-input" value="${esc(ftr.contact_cta_label || '')}"/></div>
            </div>
            <div class="zs-field"><label>İletişim Link Adresi</label><input id="ftrContactUrl" list="quickRoutesList" class="zs-input" value="${esc(ftr.contact_cta_url || '/iletisim/')}"/></div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">Telif Satırı, Alt Yasal Menü & Dev İmza</h3>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Telif Metni</label><input id="ftrCopy" class="zs-input" value="${esc(ftr.copyright || '')}"/></div>
              <div class="zs-field"><label>🔒 Ajans İmzası (Sabit — zeynart.com)</label><input id="ftrCrafted" class="zs-input" value="Powered &amp; Designed By Zeynart®" readonly disabled style="opacity:.75;cursor:not-allowed"/></div>
              <div class="zs-field"><label>Dev İmza Yazısı</label><input id="ftrWordmark" class="zs-input" value="${esc(ftr.wordmark || 'ZEYNART')}"/></div>
            </div>
            <div class="zs-card-head" style="margin-top:8px;margin-bottom:8px">
              <label style="font-size:12px;font-weight:700;color:var(--text-muted)">En Alt Yasal & Ek Menü Linkleri (${(ftr.legal_links || []).length})</label>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="ftrAddLegalBtn">+ Alt Menü Linki Ekle</button>
            </div>
            <div id="ftrLegalWrap">
              ${(ftr.legal_links || []).map((ll, idx) => `
                <div class="zs-sort-row" style="padding:8px 10px" data-flegal="${idx}">
                  <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-flegal-move="${idx}:-1" ${idx === 0 ? 'disabled' : ''}>↑</button>
                  <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-flegal-move="${idx}:1" ${idx === ftr.legal_links.length - 1 ? 'disabled' : ''}>↓</button>
                  <input class="zs-input fleg-lbl" placeholder="Başlık (örn: KVKK)" value="${esc(ll.label)}"/>
                  <input class="zs-input fleg-url" list="quickRoutesList" placeholder="/kvkk/" value="${esc(ll.url)}"/>
                  <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-flegal-del="${idx}">✕</button>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      ` : ''}

      ${tab === 'typography' ? `
        <div class="zs-card" style="margin-bottom:18px;border-color:rgba(45,212,191,.36)">
          <div class="zs-card-head">
            <div>
              <div style="display:flex;align-items:center;gap:8px">
                <span class="zs-pill zs-pill-pub">GOOGLE FONTS & YERLEŞİK FONT STÜDYOSU</span>
                <h3 style="margin:0">Site Yazı Tipi (Font Ailesi) & Hazır Ajans Eşleşmeleri</h3>
              </div>
              <p class="zs-card-sub" style="margin-top:4px">Tüm sitedeki gövde metinleri ve başlıklar için Google Fonts kütüphanesinden dilediğiniz yazı tipini seçebilir, hazır eşleşmeleri kullanabilir veya 1.600+ Google Font arasından istediğiniz fontun adını yazabilirsiniz.</p>
            </div>
            <a href="https://fonts.google.com/?subset=latin-ext" target="_blank" rel="noopener" class="zs-btn zs-btn-sm">Google Fonts Kataloğunu Gör ↗</a>
          </div>

          <div style="margin-bottom:16px">
            <label style="display:block;font-size:11.5px;font-weight:700;color:var(--text-muted);margin-bottom:8px;letter-spacing:.04em">✨ 1-TIKLA HAZIR AJANS FONT EŞLEŞMELERİ</label>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px">
              ${FONT_PAIRING_PRESETS.map(pr => {
                const isActive = (typo.body_font || 'Manrope') === pr.body_font && (typo.heading_font || 'same') === pr.heading_font;
                return `
                  <button type="button" data-font-pair="${esc(pr.id)}" style="text-align:left;padding:11px 13px;border-radius:12px;border:1px solid ${isActive ? '#2dd4bf' : 'var(--border-subtle)'};background:${isActive ? 'rgba(20,184,166,.14)' : 'rgba(5,12,16,.75)'};cursor:pointer;transition:border-color .2s,background .2s">
                    <strong style="display:block;font-size:12.5px;color:${isActive ? '#5eead4' : '#f1f5f9'}">${isActive ? '✓ ' : ''}${esc(pr.name)}</strong>
                    <span style="display:block;font-size:11px;color:var(--text-muted);margin-top:3px">${esc(pr.sub)}</span>
                  </button>
                `;
              }).join('')}
            </div>
          </div>

          <div class="zs-grid-2">
            <div style="padding:14px;border-radius:12px;background:rgba(4,10,14,.72);border:1px solid var(--border-subtle)">
              <div class="zs-field" style="margin-bottom:${typo.body_font === 'custom' ? '10px' : '0'}">
                <label>Ana Gövde Yazı Tipi (Paragraflar, Menüler, Butonlar & Formlar)</label>
                <select id="typoBodyFont" class="zs-select">
                  ${renderFontSelectOptions(typo.body_font || 'Manrope', false)}
                </select>
              </div>
              <div id="typoCustomBodyWrap" style="display:${typo.body_font === 'custom' ? 'block' : 'none'}">
                <label style="font-size:11.5px;color:#5eead4;display:block;margin-bottom:5px">Özel Google Font Adı veya Linki (Örn: <code>Raleway</code>, <code>IBM Plex Sans</code>, <code>Chakra Petch</code>)</label>
                <input id="typoCustomBodyFont" class="zs-input" placeholder="Google Font adını veya <link> kodunu yapıştırın..." value="${esc(typo.custom_body_font || '')}"/>
              </div>
            </div>

            <div style="padding:14px;border-radius:12px;background:rgba(4,10,14,.72);border:1px solid var(--border-subtle)">
              <div class="zs-field" style="margin-bottom:${typo.heading_font === 'custom' ? '10px' : '0'}">
                <label>Başlık Yazı Tipi (H1, H2, H3, H4 & Vitrin Başlıkları)</label>
                <select id="typoHeadingFont" class="zs-select">
                  ${renderFontSelectOptions(typo.heading_font || 'same', true)}
                </select>
              </div>
              <div id="typoCustomHeadingWrap" style="display:${typo.heading_font === 'custom' ? 'block' : 'none'}">
                <label style="font-size:11.5px;color:#5eead4;display:block;margin-bottom:5px">Özel Başlık Google Font Adı veya Linki (Örn: <code>Oswald</code>, <code>Cinzel</code>, <code>Albert Sans</code>)</label>
                <input id="typoCustomHeadingFont" class="zs-input" placeholder="Başlık için Google Font adını veya linkini girin..." value="${esc(typo.custom_heading_font || '')}"/>
              </div>
            </div>
          </div>

          <div class="zs-grid-3" style="margin-top:12px">
            <div class="zs-typo-row">
              <div class="zs-typo-row-head"><strong>Site Genel Gövde Yazı Boyutu (Body)</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="base_body_size">${typo.base_body_size || 14}</span> px</span></div>
              <div class="zs-typo-controls">
                <input type="range" class="zs-typo-range" min="12" max="19" step="0.5" data-typo-slider="base_body_size" value="${typo.base_body_size || 14}"/>
                <input type="number" class="zs-input" min="12" max="20" step="0.5" data-typo-num="base_body_size" value="${typo.base_body_size || 14}"/>
              </div>
            </div>
            <div class="zs-typo-row">
              <div class="zs-typo-row-head"><strong>Başlık Boyutu Ölçeği (H1/H2/H3)</strong><span class="zs-pill zs-pill-cat">%<span data-typo-val="heading_scale">${typo.heading_scale || 100}</span></span></div>
              <div class="zs-typo-controls">
                <input type="range" class="zs-typo-range" min="75" max="135" step="2" data-typo-slider="heading_scale" value="${typo.heading_scale || 100}"/>
                <input type="number" class="zs-input" min="75" max="140" step="1" data-typo-num="heading_scale" value="${typo.heading_scale || 100}"/>
              </div>
            </div>
            <div class="zs-typo-row">
              <div class="zs-typo-row-head"><strong>Büyük Başlık Harf Aralığı (Letter-Spacing)</strong><span class="zs-pill zs-pill-cat">${esc(typo.heading_tracking || 'tight')}</span></div>
              <select id="typoHeadingTracking" class="zs-select">
                <option value="tight" ${(typo.heading_tracking || 'tight') === 'tight' ? 'selected' : ''}>Sıkı & Modern (-3.8px — Manrope/Inter/Outfit için ideal)</option>
                <option value="balanced" ${typo.heading_tracking === 'balanced' ? 'selected' : ''}>Dengeli Stüdyo (-0.025em — Syne/Space Grotesk/Poppins için ideal)</option>
                <option value="normal" ${typo.heading_tracking === 'normal' ? 'selected' : ''}>Normal & Ferah (0px — Playfair/Serif ve geniş fontlar için ideal)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="zs-card" style="margin-bottom:18px">
          <div class="zs-card-head">
            <div>
              <h3>Canlı Gerçek Zamanlı Font & Boyut Önizleme Sahnesi</h3>
              <p class="zs-card-sub">Aktif Gövde Fontu: <strong id="lpvActiveBodyBadge" style="color:#5eead4">${esc(fontPrev.body.family)}</strong> · Aktif Başlık Fontu: <strong id="lpvActiveHeadBadge" style="color:#5eead4">${esc(fontPrev.heading.family)}</strong></p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-sm" data-typo-preset="compact">Kompakt (14 / 13px)</button>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-typo-preset="balanced">✨ Okunaklı Standart (16 / 14.5px)</button>
              <button type="button" class="zs-btn zs-btn-sm" data-typo-preset="large">Büyük & Ferah (18 / 16.5px)</button>
            </div>
          </div>

          <div class="zs-live-menu-stage" id="zsTypoLiveStage" style="font-family:${esc(fontPrev.body.stack)}">
            <div style="padding:18px 26px;border-bottom:1px solid rgba(255,255,255,.09);display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
              <strong class="lpv-heading-el" style="letter-spacing:4px;font-size:17px;color:#fff;font-family:${esc(fontPrev.heading.stack)}">ZEYNART</strong>
              <div id="lpvHdrNav" style="display:flex;align-items:center;flex-wrap:wrap;gap:${Number(typo.header_gap)}px">
                ${(hdr.items || []).slice(0, 6).map((it, idx) => `<span class="lpv-hlink" style="font-size:${Number(typo.header_link_size)}px;font-weight:${Number(typo.header_link_weight)};color:${idx === 0 ? '#fff' : '#d7e3e8'}">${esc(it.label)}</span>`).join('')}
              </div>
              <span id="lpvHdrCta" style="padding:9px 18px;border-radius:99px;border:1px solid rgba(255,255,255,.32);font-size:${Number(typo.header_cta_size)}px;font-weight:600;color:#fff">${esc(hdr.cta_label || 'Birlikte çalışalım')} ↗</span>
            </div>

            <div style="padding:28px 26px;border-bottom:1px solid rgba(255,255,255,.08);background:radial-gradient(circle at 15% 20%,rgba(22,183,197,.14),transparent 65%),#071014">
              <div style="font-size:10px;letter-spacing:2.4px;color:#5eead4;margin-bottom:10px;font-weight:600">DİJİTAL TASARIM & YAZILIM STÜDYOSU</div>
              <div id="lpvHeroHeading" class="lpv-heading-el" style="font-family:${esc(fontPrev.heading.stack)};font-size:clamp(28px,3.2vw,42px);font-weight:650;line-height:1.12;color:#f5f7fa;letter-spacing:${typo.heading_tracking === 'normal' ? '0px' : (typo.heading_tracking === 'balanced' ? '-0.025em' : '-1.8px')}">Siz hayal edin, <span style="color:#16b7c5">Zeynart tasarlasın.</span></div>
              <p id="lpvBodySample" style="margin:12px 0 0;max-width:680px;color:#b8c7d0;line-height:1.8;font-size:${Number(typo.base_body_size || 14)}px">Markanız için yüksek hızlı kurumsal web tasarım, özel yazılım, e-ticaret ve bulut hosting çözümleri üretiyoruz. Türkçe karakter uyumu: <strong>ğ, ü, ş, ı, İ, ö, ç — Ğ, Ü, Ş, İ, Ö, Ç.</strong></p>
            </div>

            <div style="padding:24px 26px;display:grid;grid-template-columns:1.3fr repeat(${Math.max(1, (ftr.columns || []).length)},1fr) 1.1fr;gap:24px;background:#060d10">
              <div>
                <strong class="lpv-heading-el" style="letter-spacing:3px;font-size:14px;color:#fff;font-family:${esc(fontPrev.heading.stack)}">ZEYNART</strong>
                <p id="lpvFtrText" style="margin:12px 0 0;color:#9fb4be;font-size:${Number(typo.footer_text_size)}px;line-height:1.7">İyi fikirlerin geleceğini birlikte tasarlıyoruz.</p>
              </div>
              ${(ftr.columns || []).map(c => `
                <div>
                  <div class="lpv-ftitle lpv-heading-el" style="color:#eef6f8;font-weight:600;margin-bottom:10px;font-size:${Number(typo.footer_title_size)}px;font-family:${esc(fontPrev.heading.stack)}">${esc(c.title)}</div>
                  ${(c.links || []).slice(0, 4).map(l => `<div class="lpv-flink" style="color:#a6bac4;margin-bottom:8px;font-size:${Number(typo.footer_link_size)}px">${esc(l.label)}</div>`).join('')}
                </div>
              `).join('')}
              <div>
                <div class="lpv-ftitle lpv-heading-el" style="color:#eef6f8;font-weight:600;margin-bottom:10px;font-size:${Number(typo.footer_title_size)}px;font-family:${esc(fontPrev.heading.stack)}">${esc(ftr.contact_heading || 'Bir merhaba ile başlar.')}</div>
                <div class="lpv-flink" style="color:#5eead4;font-weight:600;margin-bottom:8px;font-size:${Number(typo.footer_link_size) + 1}px">${esc(ftr.contact_cta_label || 'Fikrinizi konuşalım')} ↗</div>
              </div>
            </div>
            <div id="lpvFtrLegal" style="padding:14px 26px;border-top:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;color:#95abb6;font-size:${Number(typo.footer_legal_size)}px;background:#050b0e">
              <span>${esc(ftr.copyright || '© 2026 ZEYNART')}</span>
              <div style="display:flex;gap:18px">${(ftr.legal_links || []).map(ll => `<span>${esc(ll.label)}</span>`).join('')}</div>
            </div>
          </div>
        </div>

        <div class="zs-grid-2">
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Üst Menü (Header) Yazı Boyutları</h3>
            <div style="display:grid;gap:12px">
              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Header Menü Link Boyutu</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="header_link_size">${typo.header_link_size}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="11" max="26" step="0.5" data-typo-slider="header_link_size" value="${typo.header_link_size}"/>
                  <input type="number" class="zs-input" min="11" max="28" step="0.5" data-typo-num="header_link_size" value="${typo.header_link_size}"/>
                </div>
              </div>

              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Header Menü Yazı Kalınlığı</strong><span class="zs-pill zs-pill-cat">${typo.header_link_weight}</span></div>
                <select id="typoHdrWeight" class="zs-select">
                  <option value="400" ${Number(typo.header_link_weight) === 400 ? 'selected' : ''}>400 — İnce / Zarif</option>
                  <option value="500" ${Number(typo.header_link_weight) === 500 ? 'selected' : ''}>500 — Dengeli Kurumsal (Varsayılan)</option>
                  <option value="600" ${Number(typo.header_link_weight) === 600 ? 'selected' : ''}>600 — Yarı Kalın / Belirgin</option>
                  <option value="700" ${Number(typo.header_link_weight) === 700 ? 'selected' : ''}>700 — Kalın / Güçlü</option>
                </select>
              </div>

              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Header Menü Linkler Arası Boşluk</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="header_gap">${typo.header_gap}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="12" max="60" step="1" data-typo-slider="header_gap" value="${typo.header_gap}"/>
                  <input type="number" class="zs-input" min="12" max="64" step="1" data-typo-num="header_gap" value="${typo.header_gap}"/>
                </div>
              </div>

              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Header Sağ Aksiyon Butonu Yazısı</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="header_cta_size">${typo.header_cta_size}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="10" max="20" step="0.5" data-typo-slider="header_cta_size" value="${typo.header_cta_size}"/>
                  <input type="number" class="zs-input" min="10" max="22" step="0.5" data-typo-num="header_cta_size" value="${typo.header_cta_size}"/>
                </div>
              </div>

              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Mobil Açılır Menü Yazı Boyutu</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="mobile_link_size">${typo.mobile_link_size}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="18" max="42" step="1" data-typo-slider="mobile_link_size" value="${typo.mobile_link_size}"/>
                  <input type="number" class="zs-input" min="18" max="44" step="1" data-typo-num="mobile_link_size" value="${typo.mobile_link_size}"/>
                </div>
              </div>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">Alt Menü (Footer) Yazı Boyutları</h3>
            <div style="display:grid;gap:12px">
              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Footer Sütun Başlıkları Boyutu</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="footer_title_size">${typo.footer_title_size}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="10" max="24" step="0.5" data-typo-slider="footer_title_size" value="${typo.footer_title_size}"/>
                  <input type="number" class="zs-input" min="10" max="26" step="0.5" data-typo-num="footer_title_size" value="${typo.footer_title_size}"/>
                </div>
              </div>

              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Footer Menü Linkleri Boyutu</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="footer_link_size">${typo.footer_link_size}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="10" max="22" step="0.5" data-typo-slider="footer_link_size" value="${typo.footer_link_size}"/>
                  <input type="number" class="zs-input" min="10" max="24" step="0.5" data-typo-num="footer_link_size" value="${typo.footer_link_size}"/>
                </div>
              </div>

              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Footer Tanıtım & Adres Metni Boyutu</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="footer_text_size">${typo.footer_text_size}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="10" max="20" step="0.5" data-typo-slider="footer_text_size" value="${typo.footer_text_size}"/>
                  <input type="number" class="zs-input" min="10" max="22" step="0.5" data-typo-num="footer_text_size" value="${typo.footer_text_size}"/>
                </div>
              </div>

              <div class="zs-typo-row">
                <div class="zs-typo-row-head"><strong>Footer Alt Telif & KVKK/Gizlilik Linkleri</strong><span class="zs-pill zs-pill-cat"><span data-typo-val="footer_legal_size">${typo.footer_legal_size}</span> px</span></div>
                <div class="zs-typo-controls">
                  <input type="range" class="zs-typo-range" min="9" max="19" step="0.5" data-typo-slider="footer_legal_size" value="${typo.footer_legal_size}"/>
                  <input type="number" class="zs-input" min="9" max="20" step="0.5" data-typo-num="footer_legal_size" value="${typo.footer_legal_size}"/>
                </div>
              </div>
            </div>
          </div>
        </div>
      ` : ''}

      ${tab === 'cta' ? `
        <div class="zs-card">
          <h3 style="margin:0 0 14px">Sayfa Altı Ortak Teklif Çağrı Alanı (Global CTA)</h3>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Üst Küçük Başlık (Eyebrow)</label><input id="ctaEyebrow" class="zs-input" value="${esc(cta.eyebrow || '')}"/></div>
            <div class="zs-field"><label>Yan Dikey Not</label><input id="ctaSideNote" class="zs-input" value="${esc(cta.side_note || '')}"/></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Başlık 1. Satır</label><input id="ctaH1" class="zs-input" value="${esc(cta.heading_line1 || '')}"/></div>
            <div class="zs-field"><label>Başlık 2. Satır</label><input id="ctaH2" class="zs-input" value="${esc(cta.heading_line2 || '')}"/></div>
            <div class="zs-field"><label>Başlık Turkuaz Vurgu</label><input id="ctaAccent" class="zs-input" value="${esc(cta.heading_accent || '')}"/></div>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Buton Metni</label><input id="ctaBtnLbl" class="zs-input" value="${esc(cta.button_label || '')}"/></div>
            <div class="zs-field"><label>Buton Adresi</label><input id="ctaBtnUrl" class="zs-input" value="${esc(cta.button_url || '/iletisim/')}"/></div>
          </div>
        </div>
      ` : ''}
    `;

    const syncMenuDomToState = () => {
      if (tab === 'header' && document.getElementById('hdrCtaLabel')) {
        hdr.items = Array.from(ROOT.querySelectorAll('[data-hdr-idx]')).map((row, idx) => ({
          id: hdr.items[idx]?.id || ('nav-' + (idx + 1)),
          label: row.querySelector('.hdr-lbl').value.trim(),
          url: row.querySelector('.hdr-url').value.trim() || '/',
          target: row.querySelector('.hdr-target').value,
          enabled: row.querySelector('.hdr-enabled').checked
        }));
        hdr.cta_label = document.getElementById('hdrCtaLabel').value.trim();
        hdr.cta_url = document.getElementById('hdrCtaUrl').value.trim();
        hdr.language_badge = document.getElementById('hdrLangBadge').value.trim();
        hdr.logo_aria = document.getElementById('hdrLogoAria').value.trim();
        hdr.mobile_eyebrow = document.getElementById('hdrMobileEyebrow').value.trim();
        hdr.show_cta = document.getElementById('hdrShowCta').checked;
        hdr.show_language = document.getElementById('hdrShowLang').checked;
      } else if (tab === 'footer' && document.getElementById('ftrIntro')) {
        ftr.columns = Array.from(ROOT.querySelectorAll('[data-fcol]')).map((colEl, cIdx) => ({
          id: ftr.columns[cIdx]?.id || ('fcol-' + (cIdx + 1)),
          title: colEl.querySelector('.fcol-title').value.trim() || ('Menü ' + (cIdx + 1)),
          links: Array.from(colEl.querySelectorAll('[data-flink]')).map(lRow => ({
            label: lRow.querySelector('.flnk-lbl').value.trim(),
            url: lRow.querySelector('.flnk-url').value.trim() || '/',
            target: lRow.querySelector('.flnk-target')?.value === '_blank' ? '_blank' : '_self'
          }))
        }));
        ftr.intro_text = document.getElementById('ftrIntro').value;
        ftr.availability_text = document.getElementById('ftrAvail').value.trim();
        ftr.show_availability = document.getElementById('ftrShowAvail').checked;
        ftr.contact_heading = document.getElementById('ftrContactH').value.trim();
        ftr.contact_cta_label = document.getElementById('ftrContactLbl').value.trim();
        ftr.contact_cta_url = document.getElementById('ftrContactUrl').value.trim();
        ftr.copyright = document.getElementById('ftrCopy').value.trim();
        ftr.crafted_note = 'Powered & Designed By Zeynart®';
        ftr.credit_label = 'Powered & Designed By Zeynart®';
        ftr.credit_url = 'https://zeynart.com/';
        ftr.wordmark = document.getElementById('ftrWordmark').value.trim();
        ftr.legal_links = Array.from(ROOT.querySelectorAll('[data-flegal]')).map(r => ({
          label: r.querySelector('.fleg-lbl').value.trim(),
          url: r.querySelector('.fleg-url').value.trim() || '/'
        }));
      } else if (tab === 'typography') {
        const bfSel = document.getElementById('typoBodyFont');
        if (bfSel) typo.body_font = bfSel.value;
        const cbfInp = document.getElementById('typoCustomBodyFont');
        if (cbfInp) typo.custom_body_font = cbfInp.value.trim();
        const hfSel = document.getElementById('typoHeadingFont');
        if (hfSel) typo.heading_font = hfSel.value;
        const chfInp = document.getElementById('typoCustomHeadingFont');
        if (chfInp) typo.custom_heading_font = chfInp.value.trim();
        const trkSel = document.getElementById('typoHeadingTracking');
        if (trkSel) typo.heading_tracking = trkSel.value;
        const wSel = document.getElementById('typoHdrWeight');
        if (wSel) typo.header_link_weight = Number(wSel.value || 500);
        ROOT.querySelectorAll('[data-typo-num]').forEach(inp => {
          const k = inp.getAttribute('data-typo-num');
          typo[k] = Number(inp.value || typo[k]);
        });
      } else if (tab === 'cta' && document.getElementById('ctaEyebrow')) {
        cta.eyebrow = document.getElementById('ctaEyebrow').value.trim();
        cta.side_note = document.getElementById('ctaSideNote').value.trim();
        cta.heading_line1 = document.getElementById('ctaH1').value.trim();
        cta.heading_line2 = document.getElementById('ctaH2').value.trim();
        cta.heading_accent = document.getElementById('ctaAccent').value.trim();
        cta.button_label = document.getElementById('ctaBtnLbl').value.trim();
        cta.button_url = document.getElementById('ctaBtnUrl').value.trim();
      }
      S.store.menus = { header: hdr, footer: ftr, typography: typo };
      S.store.cta_global = cta;
    };

    ROOT.querySelectorAll('[data-mtab]').forEach(b => {
      b.onclick = () => {
        syncMenuDomToState();
        S.tabs.menus = b.getAttribute('data-mtab');
        renderMenusView();
      };
    });

    if (tab === 'header') {
      document.getElementById('hdrJumpTypoBtn').onclick = () => { syncMenuDomToState(); S.tabs.menus = 'typography'; renderMenusView(); };

      ROOT.querySelectorAll('[data-hdr-align]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          hdr.nav_align = b.getAttribute('data-hdr-align');
          renderMenusView();
        };
      });

      ROOT.querySelectorAll('[data-hel-move]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [i, dir] = b.getAttribute('data-hel-move').split(':').map(Number);
          const t = i + dir;
          if (t >= 0 && t < hdr.element_order.length) {
            [hdr.element_order[i], hdr.element_order[t]] = [hdr.element_order[t], hdr.element_order[i]];
            renderMenusView();
          }
        };
      });

      document.getElementById('hdrAddItemBtn').onclick = () => {
        syncMenuDomToState();
        hdr.items.push({ id: uid('nav'), label: 'Yeni Menü', url: '/', target: '_self', enabled: true });
        renderMenusView();
      };

      document.getElementById('hdrQuickPageAddBtn').onclick = () => {
        syncMenuDomToState();
        const r = quickRoutes[Number(document.getElementById('hdrQuickPageSel').value || 0)];
        if (r) {
          hdr.items.push({ id: uid('nav'), label: r.label.replace(/^—\s*/, ''), url: r.url, target: '_self', enabled: true });
          toast(`"${r.label}" üst menüye eklendi.`);
          renderMenusView();
        }
      };

      ROOT.querySelectorAll('[data-hdr-move]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [i, dir] = b.getAttribute('data-hdr-move').split(':').map(Number);
          const target = i + dir;
          if (target >= 0 && target < hdr.items.length) {
            [hdr.items[i], hdr.items[target]] = [hdr.items[target], hdr.items[i]];
            renderMenusView();
          }
        };
      });

      ROOT.querySelectorAll('[data-hdr-del]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          hdr.items.splice(Number(b.getAttribute('data-hdr-del')), 1);
          renderMenusView();
        };
      });

      // Drag & Drop reordering for Header items
      let hdrDragIdx = null;
      ROOT.querySelectorAll('[data-hdr-idx]').forEach(row => {
        const idx = Number(row.getAttribute('data-hdr-idx'));
        row.ondragstart = e => {
          if (['INPUT', 'SELECT'].includes(e.target.tagName)) { e.preventDefault(); return; }
          hdrDragIdx = idx;
          e.dataTransfer.effectAllowed = 'move';
        };
        row.ondragover = e => {
          if (hdrDragIdx === null || hdrDragIdx === idx) return;
          e.preventDefault();
          row.classList.add('drag-over');
        };
        row.ondragleave = () => row.classList.remove('drag-over');
        row.ondrop = e => {
          e.preventDefault();
          row.classList.remove('drag-over');
          if (hdrDragIdx === null || hdrDragIdx === idx) return;
          syncMenuDomToState();
          const [moved] = hdr.items.splice(hdrDragIdx, 1);
          hdr.items.splice(idx, 0, moved);
          hdrDragIdx = null;
          renderMenusView();
        };
      });
    }

    if (tab === 'footer') {
      document.getElementById('ftrJumpTypoBtn').onclick = () => { syncMenuDomToState(); S.tabs.menus = 'typography'; renderMenusView(); };

      ROOT.querySelectorAll('[data-fblk-move]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [i, dir] = b.getAttribute('data-fblk-move').split(':').map(Number);
          const t = i + dir;
          if (t >= 0 && t < ftr.block_order.length) {
            [ftr.block_order[i], ftr.block_order[t]] = [ftr.block_order[t], ftr.block_order[i]];
            renderMenusView();
          }
        };
      });

      document.getElementById('ftrAddColBtn').onclick = () => {
        syncMenuDomToState();
        ftr.columns.push({
          id: uid('fcol'),
          title: 'Yeni Menü Grubu',
          links: [{ label: 'Ana Sayfa', url: '/', target: '_self' }]
        });
        toast('Yeni Footer menü grubu eklendi.');
        renderMenusView();
      };

      ROOT.querySelectorAll('[data-fcol-move]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [cIdx, dir] = b.getAttribute('data-fcol-move').split(':').map(Number);
          const t = cIdx + dir;
          if (t >= 0 && t < ftr.columns.length) {
            [ftr.columns[cIdx], ftr.columns[t]] = [ftr.columns[t], ftr.columns[cIdx]];
            renderMenusView();
          }
        };
      });

      ROOT.querySelectorAll('[data-fcol-add-link]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const cIdx = Number(b.getAttribute('data-fcol-add-link'));
          ftr.columns[cIdx].links.push({ label: 'Yeni Bağlantı', url: '/', target: '_self' });
          renderMenusView();
        };
      });

      ROOT.querySelectorAll('[data-fcol-add-page]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const cIdx = Number(b.getAttribute('data-fcol-add-page'));
          const sel = ROOT.querySelector(`[data-fcol-page-sel="${cIdx}"]`);
          const r = quickRoutes[Number(sel?.value || 0)];
          if (r) {
            ftr.columns[cIdx].links.push({ label: r.label.replace(/^—\s*/, ''), url: r.url, target: '_self' });
            toast(`"${r.label}" footer menüsüne eklendi.`);
            renderMenusView();
          }
        };
      });

      ROOT.querySelectorAll('[data-fcol-del]').forEach(b => {
        b.onclick = () => {
          if (!confirm('Bu footer menü grubunu silmek istediğinize emin misiniz?')) return;
          syncMenuDomToState();
          ftr.columns.splice(Number(b.getAttribute('data-fcol-del')), 1);
          renderMenusView();
        };
      });

      ROOT.querySelectorAll('[data-flink-move]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [cIdx, lIdx, dir] = b.getAttribute('data-flink-move').split(':').map(Number);
          const arr = ftr.columns[cIdx].links;
          const t = lIdx + dir;
          if (t >= 0 && t < arr.length) {
            [arr[lIdx], arr[t]] = [arr[t], arr[lIdx]];
            renderMenusView();
          }
        };
      });

      ROOT.querySelectorAll('[data-flink-shiftcol]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [cIdx, lIdx] = b.getAttribute('data-flink-shiftcol').split(':').map(Number);
          if (ftr.columns.length < 2) return;
          const nextCol = (cIdx + 1) % ftr.columns.length;
          const [moved] = ftr.columns[cIdx].links.splice(lIdx, 1);
          if (moved) {
            ftr.columns[nextCol].links.push(moved);
            toast(`Bağlantı "${ftr.columns[nextCol].title}" grubuna taşındı.`);
            renderMenusView();
          }
        };
      });

      ROOT.querySelectorAll('[data-flink-del]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [cIdx, lIdx] = b.getAttribute('data-flink-del').split(':').map(Number);
          ftr.columns[cIdx].links.splice(lIdx, 1);
          renderMenusView();
        };
      });

      // Drag & drop for Footer Columns & Links
      let ftrDrag = null;
      ROOT.querySelectorAll('[data-flink]').forEach(lEl => {
        const [cIdx, lIdx] = lEl.getAttribute('data-flink').split(':').map(Number);
        lEl.ondragstart = e => {
          if (['INPUT', 'SELECT'].includes(e.target.tagName)) { e.preventDefault(); return; }
          e.stopPropagation();
          ftrDrag = { kind: 'link', cIdx, lIdx };
        };
        lEl.ondragover = e => {
          if (!ftrDrag || ftrDrag.kind !== 'link') return;
          e.preventDefault();
          e.stopPropagation();
          lEl.classList.add('drag-over');
        };
        lEl.ondragleave = () => lEl.classList.remove('drag-over');
        lEl.ondrop = e => {
          if (!ftrDrag || ftrDrag.kind !== 'link') return;
          e.preventDefault();
          e.stopPropagation();
          lEl.classList.remove('drag-over');
          syncMenuDomToState();
          const [moved] = ftr.columns[ftrDrag.cIdx].links.splice(ftrDrag.lIdx, 1);
          if (moved) ftr.columns[cIdx].links.splice(lIdx, 0, moved);
          ftrDrag = null;
          renderMenusView();
        };
      });

      ROOT.querySelectorAll('[data-fcol]').forEach(colEl => {
        const cIdx = Number(colEl.getAttribute('data-fcol'));
        colEl.ondragstart = e => {
          if (e.target.closest('[data-flink]') || ['INPUT', 'SELECT'].includes(e.target.tagName)) return;
          ftrDrag = { kind: 'col', cIdx };
        };
        colEl.ondragover = e => {
          if (!ftrDrag) return;
          e.preventDefault();
          colEl.classList.add('drag-over');
        };
        colEl.ondragleave = () => colEl.classList.remove('drag-over');
        colEl.ondrop = e => {
          if (!ftrDrag) return;
          e.preventDefault();
          colEl.classList.remove('drag-over');
          syncMenuDomToState();
          if (ftrDrag.kind === 'col' && ftrDrag.cIdx !== cIdx) {
            const [movedCol] = ftr.columns.splice(ftrDrag.cIdx, 1);
            ftr.columns.splice(cIdx, 0, movedCol);
          } else if (ftrDrag.kind === 'link' && ftrDrag.cIdx !== cIdx) {
            const [movedLnk] = ftr.columns[ftrDrag.cIdx].links.splice(ftrDrag.lIdx, 1);
            if (movedLnk) ftr.columns[cIdx].links.push(movedLnk);
          }
          ftrDrag = null;
          renderMenusView();
        };
      });

      document.getElementById('ftrAddLegalBtn').onclick = () => {
        syncMenuDomToState();
        ftr.legal_links.push({ label: 'Yeni Alt Link', url: '/iletisim/' });
        renderMenusView();
      };
      ROOT.querySelectorAll('[data-flegal-move]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const [idx, dir] = b.getAttribute('data-flegal-move').split(':').map(Number);
          const t = idx + dir;
          if (t >= 0 && t < ftr.legal_links.length) {
            [ftr.legal_links[idx], ftr.legal_links[t]] = [ftr.legal_links[t], ftr.legal_links[idx]];
            renderMenusView();
          }
        };
      });
      ROOT.querySelectorAll('[data-flegal-del]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          ftr.legal_links.splice(Number(b.getAttribute('data-flegal-del')), 1);
          renderMenusView();
        };
      });
    }

    if (tab === 'typography') {
      const applyLivePreview = () => {
        const prev = resolveAdminFontPreview(typo);
        const stage = document.getElementById('zsTypoLiveStage');
        if (stage) stage.style.fontFamily = prev.body.stack;
        ROOT.querySelectorAll('.lpv-heading-el').forEach(el => {
          el.style.fontFamily = prev.heading.stack;
        });
        const heroH = document.getElementById('lpvHeroHeading');
        if (heroH) {
          heroH.style.letterSpacing = typo.heading_tracking === 'normal' ? '0px' : (typo.heading_tracking === 'balanced' ? '-0.025em' : '-1.8px');
          const hScale = (Number(typo.heading_scale || 100) / 100);
          heroH.style.fontSize = `calc(clamp(28px, 3.2vw, 42px) * ${hScale.toFixed(2)})`;
        }
        const bSample = document.getElementById('lpvBodySample');
        if (bSample) bSample.style.fontSize = (typo.base_body_size || 14) + 'px';
        const bBadge = document.getElementById('lpvActiveBodyBadge');
        if (bBadge) bBadge.textContent = prev.body.family;
        const hBadge = document.getElementById('lpvActiveHeadBadge');
        if (hBadge) hBadge.textContent = prev.heading.family;

        const navWrap = document.getElementById('lpvHdrNav');
        if (navWrap) navWrap.style.gap = typo.header_gap + 'px';
        ROOT.querySelectorAll('.lpv-hlink').forEach(el => {
          el.style.fontSize = typo.header_link_size + 'px';
          el.style.fontWeight = String(typo.header_link_weight);
        });
        const ctaEl = document.getElementById('lpvHdrCta');
        if (ctaEl) ctaEl.style.fontSize = typo.header_cta_size + 'px';
        ROOT.querySelectorAll('.lpv-ftitle').forEach(el => { el.style.fontSize = typo.footer_title_size + 'px'; });
        ROOT.querySelectorAll('.lpv-flink').forEach(el => { el.style.fontSize = typo.footer_link_size + 'px'; });
        const fTxt = document.getElementById('lpvFtrText');
        if (fTxt) fTxt.style.fontSize = typo.footer_text_size + 'px';
        const fLeg = document.getElementById('lpvFtrLegal');
        if (fLeg) fLeg.style.fontSize = typo.footer_legal_size + 'px';
      };

      const bindPair = key => {
        const sl = ROOT.querySelector(`[data-typo-slider="${key}"]`);
        const nm = ROOT.querySelector(`[data-typo-num="${key}"]`);
        const lbl = ROOT.querySelector(`[data-typo-val="${key}"]`);
        if (!sl || !nm) return;
        sl.oninput = () => {
          typo[key] = Number(sl.value);
          nm.value = sl.value;
          if (lbl) lbl.textContent = sl.value;
          applyLivePreview();
        };
        nm.oninput = () => {
          typo[key] = Number(nm.value || typo[key]);
          sl.value = nm.value;
          if (lbl) lbl.textContent = nm.value;
          applyLivePreview();
        };
      };

      ['base_body_size', 'heading_scale', 'header_link_size', 'header_gap', 'header_cta_size', 'mobile_link_size', 'footer_title_size', 'footer_link_size', 'footer_text_size', 'footer_legal_size'].forEach(bindPair);

      const bfSel = document.getElementById('typoBodyFont');
      if (bfSel) {
        bfSel.onchange = () => {
          syncMenuDomToState();
          renderMenusView();
        };
      }
      const hfSel = document.getElementById('typoHeadingFont');
      if (hfSel) {
        hfSel.onchange = () => {
          syncMenuDomToState();
          renderMenusView();
        };
      }
      const cbfInp = document.getElementById('typoCustomBodyFont');
      if (cbfInp) {
        cbfInp.oninput = () => {
          typo.custom_body_font = cbfInp.value.trim();
          applyLivePreview();
        };
      }
      const chfInp = document.getElementById('typoCustomHeadingFont');
      if (chfInp) {
        chfInp.oninput = () => {
          typo.custom_heading_font = chfInp.value.trim();
          applyLivePreview();
        };
      }
      const trkSel = document.getElementById('typoHeadingTracking');
      if (trkSel) {
        trkSel.onchange = () => {
          syncMenuDomToState();
          renderMenusView();
        };
      }

      ROOT.querySelectorAll('[data-font-pair]').forEach(btn => {
        btn.onclick = () => {
          syncMenuDomToState();
          const pr = FONT_PAIRING_PRESETS.find(x => x.id === btn.getAttribute('data-font-pair'));
          if (!pr) return;
          typo.body_font = pr.body_font;
          typo.heading_font = pr.heading_font;
          typo.heading_tracking = pr.heading_tracking;
          S.store.menus.typography = typo;
          toast(`"${pr.name}" font eşleşmesi önizlemeye uygulandı — kaydetmeyi unutmayın.`);
          renderMenusView();
        };
      });

      const wSel = document.getElementById('typoHdrWeight');
      if (wSel) {
        wSel.onchange = () => {
          typo.header_link_weight = Number(wSel.value || 500);
          renderMenusView();
        };
      }

      ROOT.querySelectorAll('[data-typo-preset]').forEach(b => {
        b.onclick = () => {
          syncMenuDomToState();
          const p = b.getAttribute('data-typo-preset');
          if (p === 'compact') {
            Object.assign(typo, { base_body_size: 13.5, heading_scale: 92, header_link_size: 14, header_link_weight: 500, header_gap: 28, header_cta_size: 12, mobile_link_size: 26, footer_title_size: 13, footer_link_size: 13, footer_text_size: 13, footer_legal_size: 11.5 });
          } else if (p === 'large') {
            Object.assign(typo, { base_body_size: 16, heading_scale: 112, header_link_size: 18, header_link_weight: 600, header_gap: 36, header_cta_size: 14.5, mobile_link_size: 32, footer_title_size: 16.5, footer_link_size: 16, footer_text_size: 15.5, footer_legal_size: 14 });
          } else {
            const defSizes = getDefaultMenuTypography();
            Object.assign(typo, {
              base_body_size: defSizes.base_body_size,
              heading_scale: defSizes.heading_scale,
              header_link_size: defSizes.header_link_size,
              header_link_weight: defSizes.header_link_weight,
              header_gap: defSizes.header_gap,
              header_cta_size: defSizes.header_cta_size,
              mobile_link_size: defSizes.mobile_link_size,
              footer_title_size: defSizes.footer_title_size,
              footer_link_size: defSizes.footer_link_size,
              footer_text_size: defSizes.footer_text_size,
              footer_legal_size: defSizes.footer_legal_size
            });
          }
          S.store.menus.typography = typo;
          renderMenusView();
        };
      });
    }

    document.getElementById('saveAllMenusBtn').onclick = async () => {
      syncMenuDomToState();
      try {
        const res = await api('menus_save', {
          header: S.store.menus.header,
          footer: S.store.menus.footer,
          typography: S.store.menus.typography,
          cta_global: S.store.cta_global
        });
        S.store.menus = res.menus;
        S.store.cta_global = res.cta_global;
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      }
    };
  }

  /* ======================== MODULE 11.2: WHITE-LABEL THEME, PROMO BAR & MAINTENANCE STUDIO ======================== */
  function renderAppearanceStudioView() {
    syncAdminUrl('replace');
    const tab = S.tabs.appearance || 'theme';
    const presets = S.store.theme_presets || {
      zeynart: { label: 'Zeynart Okyanus & Turkuaz (Varsayılan)', primary_color: '#16b7c5', accent_color: '#14b8a6', bright_color: '#5eead4', bg_color: '#070f12', surface_color: '#0d1b20', button_style: 'pill' },
      royal_blue: { label: 'Kurumsal Kraliyet Mavisi', primary_color: '#3b82f6', accent_color: '#2563eb', bright_color: '#93c5fd', bg_color: '#070d19', surface_color: '#0f172a', button_style: 'rounded' },
      emerald_gold: { label: 'Prestij Zümrüt & Altın', primary_color: '#10b981', accent_color: '#d97706', bright_color: '#fbbf24', bg_color: '#06110e', surface_color: '#0c1f1a', button_style: 'pill' },
      violet_tech: { label: 'Siber Mor & Teknoloji', primary_color: '#8b5cf6', accent_color: '#7c3aed', bright_color: '#c4b5fd', bg_color: '#090814', surface_color: '#131126', button_style: 'rounded' },
      crimson_corp: { label: 'Endüstriyel Kırmızı & Antrasit', primary_color: '#ef4444', accent_color: '#dc2626', bright_color: '#fca5a5', bg_color: '#0c0a0b', surface_color: '#181315', button_style: 'sharp' },
      amber_industrial: { label: 'Mimari Kehribar & Koyu Arduvaz', primary_color: '#f59e0b', accent_color: '#d97706', bright_color: '#fde68a', bg_color: '#0b0d10', surface_color: '#151921', button_style: 'rounded' }
    };
    const tb = Object.assign({
      preset: 'zeynart',
      primary_color: '#16b7c5',
      accent_color: '#14b8a6',
      bright_color: '#5eead4',
      bg_color: '#070f12',
      surface_color: '#0d1b20',
      button_style: 'pill',
      sync_admin_accent: true,
      studio_badge_text: 'DİJİTAL STÜDYO'
    }, S.store.theme_brand || {});

    const pb = Object.assign({
      enabled: false,
      badge: 'DUYURU',
      text: 'Yeni nesil kurumsal web tasarım ve yapay zeka SEO çözümlerimizle tanışın.',
      cta_label: 'Ücretsiz Teklif Alın →',
      cta_url: '/iletisim/',
      bg_style: 'glass_teal',
      dismissible: true,
      countdown_until: ''
    }, S.store.promo_bar || {});

    const mnt = Object.assign({
      enabled: false,
      badge: 'PLANLI GÜNCELLEME',
      headline: (S.store.site?.name || 'ZEYNART') + ' Dijital Deneyimini Yeniliyoruz.',
      message: 'Sitemizde kısa süreli altyapı ve tasarım güncellemesi yapılmaktadır. Çok yakında yayındayız; acil proje ve destek talepleriniz için aşağıdaki kanallardan bize doğrudan ulaşabilirsiniz.',
      estimated_return: '',
      show_contact_info: true,
      http_code: 503
    }, S.store.maintenance || {});

    const btnRadiusCss = tb.button_style === 'rounded' ? '12px' : (tb.button_style === 'sharp' ? '4px' : '100px');

    ROOT.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        <div class="zs-tabs" style="margin-bottom:0">
          <button type="button" class="zs-tab ${tab === 'theme' ? 'active' : ''}" data-apptab="theme">🎨 White-Label Renk &amp; Tema Sihirbazı</button>
          <button type="button" class="zs-tab ${tab === 'promo' ? 'active' : ''}" data-apptab="promo">📣 Üst Kampanya Çubuğu ${pb.enabled ? '· AKTİF' : ''}</button>
          <button type="button" class="zs-tab ${tab === 'maintenance' ? 'active' : ''}" data-apptab="maintenance">🚧 Tek Tıkla Bakım Modu ${mnt.enabled ? '· AÇIK' : ''}</button>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a href="${esc(resolveUrl('/'))}" target="_blank" class="zs-btn">Siteyi Önizle ↗</a>
          <button type="button" class="zs-btn zs-btn-primary" id="saveAppearanceBtn">✓ Değişiklikleri Kaydet &amp; Yayınla</button>
        </div>
      </div>

      ${tab === 'theme' ? `
        <div class="zs-card" style="margin-bottom:16px">
          <div class="zs-card-head">
            <div>
              <span class="zs-badge-pill">AJANS ÇOKLU PROJE MOTORU</span>
              <h3 style="margin-top:6px">Hazır Kurumsal Renk Paletleri (Tek Tıkla Uygula)</h3>
              <p class="zs-card-sub">Bu yönetim panelini farklı sektörlerdeki müşteri sitelerinde kullanırken CSS dosyasına dokunmadan tüm site ve stüdyo renk kimliğini saniyeler içinde dönüştürün.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm" id="wlResetDefaultBtn">↺ Varsayılan Zeynart Temasına Sıfırla</button>
          </div>
          <div class="zs-preset-grid">
            ${Object.keys(presets).map(pKey => {
              const pr = presets[pKey];
              const isAct = tb.preset === pKey;
              return `
                <button type="button" class="zs-preset-card ${isAct ? 'active' : ''}" data-wl-preset="${esc(pKey)}" style="text-align:left">
                  <div class="zs-preset-swatches">
                    <span style="background:${esc(pr.primary_color)}" title="Ana Marka Rengi"></span>
                    <span style="background:${esc(pr.accent_color)}" title="Vurgu Rengi"></span>
                    <span style="background:${esc(pr.bright_color)}" title="Parlak Işıma"></span>
                    <span style="background:${esc(pr.surface_color)}" title="Kart Zemini"></span>
                  </div>
                  <strong style="display:block;font-size:13px;color:#fff">${esc(pr.label)}</strong>
                  <span style="font-size:11.5px;color:var(--text-muted)">Buton: ${pr.button_style === 'pill' ? 'Kapsül' : (pr.button_style === 'sharp' ? 'Keskin' : 'Yuvarlatılmış')}</span>
                </button>
              `;
            }).join('')}
          </div>
        </div>

        <div class="zs-grid-2" style="align-items:start">
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Özel Renk Seçici, Buton Geometrisi &amp; Stüdyo Rozeti</h3>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Ana Marka &amp; Buton Rengi</label>
                <div class="zs-color-pair">
                  <input type="color" id="wlPrimaryPick" class="zs-color-picker" value="${esc(tb.primary_color)}"/>
                  <input id="wlPrimaryHex" class="zs-input" value="${esc(tb.primary_color)}"/>
                </div>
              </div>
              <div class="zs-field">
                <label>İkincil Vurgu &amp; Hover Rengi</label>
                <div class="zs-color-pair">
                  <input type="color" id="wlAccentPick" class="zs-color-picker" value="${esc(tb.accent_color)}"/>
                  <input id="wlAccentHex" class="zs-input" value="${esc(tb.accent_color)}"/>
                </div>
              </div>
              <div class="zs-field">
                <label>Parlak Metin / İkon Işıması</label>
                <div class="zs-color-pair">
                  <input type="color" id="wlBrightPick" class="zs-color-picker" value="${esc(tb.bright_color)}"/>
                  <input id="wlBrightHex" class="zs-input" value="${esc(tb.bright_color)}"/>
                </div>
              </div>
              <div class="zs-field">
                <label>Ana Sayfa Arka Plan Tonu</label>
                <div class="zs-color-pair">
                  <input type="color" id="wlBgPick" class="zs-color-picker" value="${esc(tb.bg_color)}"/>
                  <input id="wlBgHex" class="zs-input" value="${esc(tb.bg_color)}"/>
                </div>
              </div>
              <div class="zs-field">
                <label>Kart &amp; Yüzey Arka Plan Tonu</label>
                <div class="zs-color-pair">
                  <input type="color" id="wlSurfacePick" class="zs-color-picker" value="${esc(tb.surface_color)}"/>
                  <input id="wlSurfaceHex" class="zs-input" value="${esc(tb.surface_color)}"/>
                </div>
              </div>
              <div class="zs-field">
                <label>Site Buton Köşe Stili</label>
                <select id="wlBtnStyle" class="zs-select">
                  <option value="pill" ${tb.button_style === 'pill' ? 'selected' : ''}>Tam Kapsül (Pill · 100px)</option>
                  <option value="rounded" ${tb.button_style === 'rounded' ? 'selected' : ''}>Modern Yuvarlatılmış (12px)</option>
                  <option value="sharp" ${tb.button_style === 'sharp' ? 'selected' : ''}>Kurumsal Keskin Köşe (4px)</option>
                </select>
              </div>
            </div>

            <div class="zs-field">
              <label>Sol Üst Yönetim Paneli Alt Etiketi (White-Label Stüdyo Rozeti)</label>
              <input id="wlBadgeText" class="zs-input" maxlength="36" placeholder="DİJİTAL STÜDYO" value="${esc(tb.studio_badge_text || 'DİJİTAL STÜDYO')}"/>
            </div>

            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="wlSyncAdmin" ${tb.sync_admin_accent !== false ? 'checked' : ''}/>
              <span><strong>Yönetim paneli vurgu ve buton renklerini de seçilen marka rengiyle eşitle</strong></span>
            </label>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 12px">Canlı Bileşen &amp; Kontrast Önizlemesi (0ms)</h3>
            <div id="wlLiveStage" class="zs-wl-preview-stage" style="background:${esc(tb.bg_color)};border-color:rgba(255,255,255,.14)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <span id="wlPrevBadge" style="font-size:11px;font-weight:800;letter-spacing:.12em;padding:4px 10px;border-radius:999px;background:rgba(255,255,255,.07);color:${esc(tb.bright_color)}">${esc(tb.studio_badge_text || 'DİJİTAL STÜDYO')}</span>
                <span style="font-size:11.5px;color:rgba(255,255,255,.55)">Canlı Önizleme</span>
              </div>
              <div id="wlPrevCard" style="padding:20px;border-radius:16px;background:${esc(tb.surface_color)};border:1px solid rgba(255,255,255,.1)">
                <h4 style="margin:0 0 8px;font-size:20px;color:#fff">${esc(S.store.site?.name || 'ZEYNART')} Kurumsal Mimari</h4>
                <p style="margin:0 0 18px;font-size:13.5px;color:rgba(255,255,255,.74);line-height:1.6">Seçtiğiniz marka renkleri ve buton köşe yapısı hem ziyaretçi arayüzüne hem stüdyo bileşenlerine otomatik uygulanır.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                  <span id="wlPrevBtn" style="display:inline-flex;align-items:center;padding:10px 20px;font-weight:700;font-size:13px;color:#fff;background:${esc(tb.primary_color)};border-radius:${btnRadiusCss}">Projeyi Başlatın →</span>
                  <span id="wlPrevLink" style="font-weight:700;font-size:13px;color:${esc(tb.bright_color)}">Hizmetleri İncele ↗</span>
                </div>
              </div>
            </div>
            <p style="margin:12px 0 0;font-size:12px;color:var(--text-muted)">💡 <strong>Not:</strong> Varsayılan Zeynart Okyanus paleti seçiliyken ekstra CSS üretilmez (%100 orijinal görünüm korunur).</p>
          </div>
        </div>
      ` : ''}

      ${tab === 'promo' ? `
        <div class="zs-grid-2" style="align-items:start">
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <h3 style="margin:0">📣 Üst Kampanya &amp; Duyuru Çubuğu</h3>
                <p class="zs-card-sub">Sitenin en üstünde tüm sayfalarda görünen, KVKK sıfır-çerez (<code>sessionStorage</code>) uyumlu şık duyuru şeridi.</p>
              </div>
              <span class="zs-pill ${pb.enabled ? 'zs-pill-pub' : 'zs-pill-cat'}">${pb.enabled ? 'YAYINDA' : 'KAPALI'}</span>
            </div>

            <label class="zs-switch" style="margin-bottom:16px">
              <input type="checkbox" id="pbEnabled" ${pb.enabled ? 'checked' : ''}/>
              <span><strong>Üst Kampanya / Duyuru Çubuğunu Sitede Aktif Et</strong></span>
            </label>

            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Sol Rozet Etiketi</label>
                <input id="pbBadge" class="zs-input" placeholder="FIRSAT / YENİ / DUYURU" value="${esc(pb.badge)}"/>
              </div>
              <div class="zs-field">
                <label>Renk &amp; Cam Teması</label>
                <select id="pbStyle" class="zs-select">
                  <option value="glass_teal" ${pb.bg_style === 'glass_teal' ? 'selected' : ''}>Okyanus &amp; Turkuaz Cam</option>
                  <option value="gradient_emerald" ${pb.bg_style === 'gradient_emerald' ? 'selected' : ''}>Zümrüt Yeşili Gradyan</option>
                  <option value="amber_gold" ${pb.bg_style === 'amber_gold' ? 'selected' : ''}>Kehribar &amp; Altın Vurgu</option>
                  <option value="royal_blue" ${pb.bg_style === 'royal_blue' ? 'selected' : ''}>Gece Mavisi &amp; İndigo</option>
                </select>
              </div>
            </div>

            <div class="zs-field">
              <label>Duyuru / Kampanya Metni</label>
              <input id="pbText" class="zs-input" value="${esc(pb.text)}"/>
            </div>

            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Aksiyon Butonu Yazısı (Boşsa gizlenir)</label>
                <input id="pbCtaLabel" class="zs-input" placeholder="Teklif Alın →" value="${esc(pb.cta_label)}"/>
              </div>
              <div class="zs-field">
                <label>Aksiyon Butonu Adresi (URL)</label>
                <input id="pbCtaUrl" class="zs-input" placeholder="/iletisim/" value="${esc(pb.cta_url)}"/>
              </div>
            </div>

            <div class="zs-grid-2">
              <div class="zs-field" style="margin-bottom:0">
                <label>Canlı Geri Sayım Bitiş Tarihi (İsteğe Bağlı)</label>
                <input type="datetime-local" id="pbCountdown" class="zs-input" value="${esc((pb.countdown_until || '').slice(0, 16))}"/>
              </div>
              <div class="zs-field" style="margin-bottom:0;display:flex;align-items:flex-end">
                <label class="zs-switch" style="margin:0">
                  <input type="checkbox" id="pbDismissible" ${pb.dismissible !== false ? 'checked' : ''}/>
                  <span>Ziyaretçi ✕ ile kapatabilsin (Çerezsiz)</span>
                </label>
              </div>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 12px">Canlı Duyuru Çubuğu Önizlemesi</h3>
            <div id="pbLivePreview" style="padding:12px 16px;border-radius:12px;background:linear-gradient(90deg,#09242b,#0f3b44,#09242b);border:1px solid rgba(94,234,212,.35);display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;color:#f0fdfa;font-size:13px">
              <span id="pbPrevBadge" style="padding:2px 9px;border-radius:999px;background:rgba(255,255,255,.15);font-size:10.5px;font-weight:800;letter-spacing:.08em">${esc(pb.badge || 'DUYURU')}</span>
              <span id="pbPrevText">${esc(pb.text)}</span>
              <span id="pbPrevCta" style="padding:4px 12px;border-radius:999px;background:#fff;color:#062026;font-weight:800;font-size:12px">${esc(pb.cta_label || 'Detaylar →')}</span>
            </div>
            <ul class="zs-check-list" style="margin-top:16px">
              <li><span class="zs-check-dot ok">✓</span><span><strong>Sabit Header Uyumu:</strong> Aktif edildiğinde üst menü ve Hero boşluğunu otomatik aşağı kaydırır; hiçbir başlığın üstünü kapatmaz.</span></li>
              <li><span class="zs-check-dot ok">✓</span><span><strong>KVKK Zero-Cookie:</strong> Kapatıldığında tarayıcıya çerez (<code>Set-Cookie</code>) yazmaz, yalnızca sekme belleğini kullanır.</span></li>
            </ul>
          </div>
        </div>
      ` : ''}

      ${tab === 'maintenance' ? `
        <div class="zs-grid-2" style="align-items:start">
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <h3 style="margin:0">🚧 Tek Tıkla Akıllı Bakım Modu (Çok Yakında)</h3>
                <p class="zs-card-sub">Yeni bir müşteri sitesini yayına hazırlarken veya güncelleme yaparken dış ziyaretçilere şık Apple Cam karşılama ekranı gösterir.</p>
              </div>
              <span class="zs-pill ${mnt.enabled ? 'zs-pill-draft' : 'zs-pill-pub'}">${mnt.enabled ? 'BAKIM MODU AÇIK' : 'SİTE HERKESE AÇIK'}</span>
            </div>

            <div style="padding:14px;border-radius:12px;background:${mnt.enabled ? 'rgba(245,158,11,.14)' : 'rgba(20,184,166,.07)'};border:1px solid ${mnt.enabled ? 'rgba(251,191,36,.45)' : 'rgba(94,234,212,.22)'};margin-bottom:16px">
              <label class="zs-switch" style="margin:0">
                <input type="checkbox" id="mntEnabled" ${mnt.enabled ? 'checked' : ''}/>
                <span><strong>Bakım Modunu Aktif Et (Ziyaretçilere "Yenileniyoruz / Çok Yakında" Ekranı Göster)</strong></span>
              </label>
            </div>

            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Üst Rozet Etiketi</label>
                <input id="mntBadge" class="zs-input" value="${esc(mnt.badge)}"/>
              </div>
              <div class="zs-field">
                <label>Arama Motoru HTTP Durum Kodu</label>
                <select id="mntHttpCode" class="zs-select">
                  <option value="503" ${Number(mnt.http_code || 503) === 503 ? 'selected' : ''}>503 Geçici Bakım (SEO Korumalı — Önerilen)</option>
                  <option value="200" ${Number(mnt.http_code) === 200 ? 'selected' : ''}>200 Çok Yakında Lansman Sayfası</option>
                </select>
              </div>
            </div>

            <div class="zs-field">
              <label>Ana Başlık</label>
              <input id="mntHeadline" class="zs-input" value="${esc(mnt.headline)}"/>
            </div>

            <div class="zs-field">
              <label>Açıklama Mesajı</label>
              <textarea id="mntMessage" class="zs-textarea" style="min-height:84px">${esc(mnt.message)}</textarea>
            </div>

            <div class="zs-grid-2">
              <div class="zs-field" style="margin-bottom:0">
                <label>Tahmini Açılış / Geri Sayım Tarihi (İsteğe Bağlı)</label>
                <input type="datetime-local" id="mntReturn" class="zs-input" value="${esc((mnt.estimated_return || '').slice(0, 16))}"/>
              </div>
              <div class="zs-field" style="margin-bottom:0;display:flex;align-items:flex-end">
                <label class="zs-switch" style="margin:0">
                  <input type="checkbox" id="mntShowContact" ${mnt.show_contact_info !== false ? 'checked' : ''}/>
                  <span>WhatsApp, Telefon ve E-posta butonlarını göster</span>
                </label>
              </div>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 10px">Akıllı Yönetici Geçişi (Admin Bypass) Nasıl Çalışır?</h3>
            <ul class="zs-check-list">
              <li><span class="zs-check-dot ok">✓</span><span><strong>Siz Çalışmaya Devam Edersiniz:</strong> Yönetim panelinde oturumunuz açık olduğu sürece sitenin tüm sayfalarını normal şekilde görür ve canlı düzenleyiciyle düzenleyebilirsiniz.</span></li>
              <li><span class="zs-check-dot ok">✓</span><span><strong>Dış Ziyaretçi Koruması:</strong> Oturum açmamış ziyaretçiler (veya gizli sekmeden girdiğinizde) doğrudan markalı Apple Cam bakım ekranını görür.</span></li>
              <li><span class="zs-check-dot ok">✓</span><span><strong>Google Sıralama Koruması:</strong> <code>503 Service Unavailable</code> + <code>Retry-After</code> başlığı sayesinde Google mevcut sayfa sıralamalarınızı silmez.</span></li>
            </ul>
          </div>
        </div>
      ` : ''}
    `;

    const syncAppearanceDom = () => {
      if (tab === 'theme') {
        tb.primary_color = (document.getElementById('wlPrimaryHex')?.value || tb.primary_color).trim();
        tb.accent_color = (document.getElementById('wlAccentHex')?.value || tb.accent_color).trim();
        tb.bright_color = (document.getElementById('wlBrightHex')?.value || tb.bright_color).trim();
        tb.bg_color = (document.getElementById('wlBgHex')?.value || tb.bg_color).trim();
        tb.surface_color = (document.getElementById('wlSurfaceHex')?.value || tb.surface_color).trim();
        tb.button_style = document.getElementById('wlBtnStyle')?.value || 'pill';
        tb.studio_badge_text = (document.getElementById('wlBadgeText')?.value || 'DİJİTAL STÜDYO').trim();
        tb.sync_admin_accent = Boolean(document.getElementById('wlSyncAdmin')?.checked);
      } else if (tab === 'promo') {
        pb.enabled = Boolean(document.getElementById('pbEnabled')?.checked);
        pb.badge = (document.getElementById('pbBadge')?.value || '').trim();
        pb.bg_style = document.getElementById('pbStyle')?.value || 'glass_teal';
        pb.text = (document.getElementById('pbText')?.value || '').trim();
        pb.cta_label = (document.getElementById('pbCtaLabel')?.value || '').trim();
        pb.cta_url = (document.getElementById('pbCtaUrl')?.value || '').trim();
        pb.countdown_until = (document.getElementById('pbCountdown')?.value || '').trim();
        pb.dismissible = Boolean(document.getElementById('pbDismissible')?.checked);
      } else if (tab === 'maintenance') {
        mnt.enabled = Boolean(document.getElementById('mntEnabled')?.checked);
        mnt.badge = (document.getElementById('mntBadge')?.value || '').trim();
        mnt.http_code = Number(document.getElementById('mntHttpCode')?.value || 503);
        mnt.headline = (document.getElementById('mntHeadline')?.value || '').trim();
        mnt.message = (document.getElementById('mntMessage')?.value || '').trim();
        mnt.estimated_return = (document.getElementById('mntReturn')?.value || '').trim();
        mnt.show_contact_info = Boolean(document.getElementById('mntShowContact')?.checked);
      }
      S.store.theme_brand = tb;
      S.store.promo_bar = pb;
      S.store.maintenance = mnt;
    };

    ROOT.querySelectorAll('[data-apptab]').forEach(btn => {
      btn.onclick = () => {
        syncAppearanceDom();
        S.tabs.appearance = btn.getAttribute('data-apptab');
        renderAppearanceStudioView();
      };
    });

    if (tab === 'theme') {
      const paintThemeStage = () => {
        syncAppearanceDom();
        const stg = document.getElementById('wlLiveStage');
        const crd = document.getElementById('wlPrevCard');
        const btn = document.getElementById('wlPrevBtn');
        const lnk = document.getElementById('wlPrevLink');
        const bdg = document.getElementById('wlPrevBadge');
        const rad = tb.button_style === 'rounded' ? '12px' : (tb.button_style === 'sharp' ? '4px' : '100px');
        if (stg) stg.style.background = tb.bg_color;
        if (crd) crd.style.background = tb.surface_color;
        if (btn) {
          btn.style.background = tb.primary_color;
          btn.style.borderRadius = rad;
        }
        if (lnk) lnk.style.color = tb.bright_color;
        if (bdg) {
          bdg.style.color = tb.bright_color;
          bdg.textContent = tb.studio_badge_text || 'DİJİTAL STÜDYO';
        }
      };

      const bindColorPair = (pickId, hexId) => {
        const pk = document.getElementById(pickId);
        const hx = document.getElementById(hexId);
        if (!pk || !hx) return;
        pk.oninput = () => { tb.preset = 'custom'; hx.value = pk.value; paintThemeStage(); };
        hx.oninput = () => {
          if (/^#[0-9a-fA-F]{6}$/.test(hx.value.trim())) {
            tb.preset = 'custom';
            pk.value = hx.value.trim();
            paintThemeStage();
          }
        };
      };
      bindColorPair('wlPrimaryPick', 'wlPrimaryHex');
      bindColorPair('wlAccentPick', 'wlAccentHex');
      bindColorPair('wlBrightPick', 'wlBrightHex');
      bindColorPair('wlBgPick', 'wlBgHex');
      bindColorPair('wlSurfacePick', 'wlSurfaceHex');
      document.getElementById('wlBtnStyle')?.addEventListener('change', () => { tb.preset = 'custom'; paintThemeStage(); });
      document.getElementById('wlBadgeText')?.addEventListener('input', paintThemeStage);

      ROOT.querySelectorAll('[data-wl-preset]').forEach(card => {
        card.onclick = () => {
          const pKey = card.getAttribute('data-wl-preset');
          const pr = presets[pKey];
          if (!pr) return;
          S.store.theme_brand = Object.assign({}, tb, {
            preset: pKey,
            primary_color: pr.primary_color,
            accent_color: pr.accent_color,
            bright_color: pr.bright_color,
            bg_color: pr.bg_color,
            surface_color: pr.surface_color,
            button_style: pr.button_style
          });
          renderAppearanceStudioView();
        };
      });

      const resetBtn = document.getElementById('wlResetDefaultBtn');
      if (resetBtn) {
        resetBtn.onclick = () => {
          const pr = presets.zeynart;
          S.store.theme_brand = {
            preset: 'zeynart',
            primary_color: pr.primary_color,
            accent_color: pr.accent_color,
            bright_color: pr.bright_color,
            bg_color: pr.bg_color,
            surface_color: pr.surface_color,
            button_style: pr.button_style,
            sync_admin_accent: true,
            studio_badge_text: 'DİJİTAL STÜDYO'
          };
          renderAppearanceStudioView();
        };
      }
    }

    if (tab === 'promo') {
      const updatePromoPrev = () => {
        syncAppearanceDom();
        const bEl = document.getElementById('pbPrevBadge');
        const tEl = document.getElementById('pbPrevText');
        const cEl = document.getElementById('pbPrevCta');
        if (bEl) bEl.textContent = pb.badge || 'DUYURU';
        if (tEl) tEl.textContent = pb.text || '';
        if (cEl) {
          cEl.textContent = pb.cta_label || '';
          cEl.style.display = pb.cta_label ? 'inline-flex' : 'none';
        }
      };
      ['pbBadge', 'pbText', 'pbCtaLabel', 'pbStyle'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updatePromoPrev);
      });
    }

    document.getElementById('saveAppearanceBtn').onclick = async () => {
      syncAppearanceDom();
      const saveBtn = document.getElementById('saveAppearanceBtn');
      saveBtn.disabled = true;
      saveBtn.textContent = '⏳ Kaydediliyor...';
      try {
        const res = await api('appearance_save', {
          theme_brand: S.store.theme_brand,
          promo_bar: S.store.promo_bar,
          maintenance: S.store.maintenance
        });
        S.store.theme_brand = res.theme_brand;
        S.store.promo_bar = res.promo_bar;
        S.store.maintenance = res.maintenance;
        if (res.summary) S.summary = res.summary;
        if (res.revisions) S.revisions = res.revisions;
        applyLiveAdminBrandState(res.admin_theme_css || '', res.theme_brand, S.store.site);
        toast(res.message);
        renderAppearanceStudioView();
      } catch (err) {
        toast(err.message, true);
        saveBtn.disabled = false;
        saveBtn.textContent = '✓ Değişiklikleri Kaydet & Yayınla';
      }
    };
  }


  return {
    syncMediaSelectionDom,
    openImageCanvasStudio,
    triggerMainMediaUpload,
    renderMediaView,
    renderMenusView,
    renderAppearanceStudioView
  };
  });
})();
