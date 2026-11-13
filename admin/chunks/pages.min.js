/* ZEYNART DİJİTAL STÜDYO v4.0 — Lazy Modül: pages */
(function () {
  'use strict';
  if (typeof window.__ZS_REGISTER_CHUNK__ !== 'function') return;
  window.__ZS_REGISTER_CHUNK__('pages', function (CTX) {
  const { CFG, DEFAULT_PAGE_SLUGS, HOME_LAYOUT_PRESETS, ROOT, S, VIEW_TITLES, WIDGET_CATALOG, api, bindHomeSectionCardsEvents, buildUnifiedPagesCatalog, computeHomeLayoutSnap, ensureNormalizedHomeSections, esc, getDefaultMenuTypography, getHomeSectionMeta, getResolvedPageSlug, getResolvedPageUrl, humanizeAltFromFilename, markHistoryPush, mountProgrammaticSolutionsPanel, navigateTo, openMediaPicker, openSocialShareStudio, renderFontSelectOptions, renderHomeSectionCardsHtml, resolveUrl, runStudioUploadQueue, setPageMenuPlacement, slugifyClient, syncAdminUrl, syncSidebarActiveTree, toast, uid, updateSidebarBadges } = CTX;

  function renderHomeLayoutView() {
    syncAdminUrl('replace');
    const { order, vis } = ensureNormalizedHomeSections();
    if (!S.homeLayoutSavedSnap) {
      S.homeLayoutSavedSnap = computeHomeLayoutSnap();
    }
    const isDirty = computeHomeLayoutSnap() !== S.homeLayoutSavedSnap;
    const activeSections = order.filter(k => vis[k] !== false);
    const hiddenCount = order.length - activeSections.length;

    ROOT.innerHTML = `
      ${isDirty ? `
        <div class="zs-unsaved-bar">
          <div style="display:flex;align-items:center;gap:10px">
            <span style="width:10px;height:10px;border-radius:50%;background:#fbbf24;box-shadow:0 0 10px #fbbf24;display:inline-block"></span>
            <div>
              <strong style="color:#fef3c7;font-size:13.5px">Kaydedilmemiş sıralama veya görünürlük değişikliği var</strong>
              <div style="font-size:12px;color:var(--text-muted)">Değişikliklerin canlı ana sayfaya yansıması için sağdaki "Sıralamayı Kaydet" butonuna basın.</div>
            </div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-sm" id="hlDiscardBtn">↺ Değişiklikleri Geri Al</button>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="hlBannerSaveBtn">✓ Sıralamayı Kaydet ve Yayınla</button>
          </div>
        </div>
      ` : ''}

      <div class="zs-card" style="margin-bottom:18px;background:linear-gradient(135deg,rgba(20,184,166,0.12),rgba(8,19,25,0.92));border-color:rgba(94,234,212,0.32)">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
          <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <span class="zs-pill zs-pill-pub">SÜRÜKLE &amp; BIRAK STÜDYOSU</span>
              <span class="zs-pill zs-pill-cat">${activeSections.length} Bölüm Yayında · ${hiddenCount} Gizli</span>
            </div>
            <h2 style="margin:8px 0 4px;font-size:20px">Ana Sayfa Bölümlerini Sırala &amp; Yönet</h2>
            <p class="zs-card-sub">Bölümleri <strong>⋮⋮ tutamacından sürükleyip bırakarak</strong>, <strong>⤒ / ↑ / ↓ / ⤓ oklarıyla</strong> veya <strong>sıra numarası seçerek</strong> dilediğiniz yere taşıyabilir; istediğiniz vitrini tek tıkla açıp kapatabilirsiniz.</p>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="${esc(resolveUrl('/'))}" target="_blank" rel="noopener" class="zs-btn">Ana Sayfayı Gör ↗</a>
            <button type="button" class="zs-btn zs-btn-ai" id="hlOpenHomeEditorBtn">✎ Ana Sayfa Metin &amp; Slaytları</button>
            <button type="button" class="zs-btn zs-btn-primary" id="hlMainSaveBtn">✓ Sıralamayı Kaydet ve Canlıya Al</button>
          </div>
        </div>
      </div>

      <div class="zs-home-layout-grid">
        <div>
          <div class="zs-card" style="margin-bottom:18px">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <h3 style="margin:0">⚡ 1-Tıkla Hazır Ana Sayfa Dizilim Şablonları</h3>
                <p class="zs-card-sub">Hedefinize göre hazır akış seçip ardından dilediğiniz gibi özelleştirebilirsiniz.</p>
              </div>
            </div>
            <div class="zs-preset-grid">
              ${HOME_LAYOUT_PRESETS.map(pr => `
                <button type="button" class="zs-preset-card" data-hl-preset="${esc(pr.id)}">
                  <strong>${esc(pr.name)}</strong>
                  <span>${esc(pr.desc)}</span>
                </button>
              `).join('')}
            </div>
          </div>

          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:14px">
              <div>
                <h3 style="margin:0">Ana Sayfa Bölüm Listesi (Yukarıdan Aşağıya Akış)</h3>
                <p class="zs-card-sub">Kartları sürükleyerek yer değiştirin. "Yayında / Gizli" anahtarıyla bölümü açıp kapatabilir, "✎ Düzenle" ile içeriğine gidebilirsiniz.</p>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="button" class="zs-btn zs-btn-sm" id="hlEnableAllBtn">Tümünü Aç</button>
                <button type="button" class="zs-btn zs-btn-sm" id="hlResetDefaultBtn">Varsayılana Dön</button>
              </div>
            </div>

            ${renderHomeSectionCardsHtml(order, vis)}
          </div>
        </div>

        <aside class="zs-home-sticky-side">
          <div class="zs-card" style="margin-bottom:0">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px">
              <div>
                <h3 style="margin:0;font-size:15px">🖥️ Canlı Ana Sayfa Akış Haritası</h3>
                <p class="zs-card-sub" style="font-size:11.5px">Ziyaretçinin göreceği gerçek yukarıdan aşağıya sıra (buradan da sürükleyebilirsiniz)</p>
              </div>
            </div>

            <div class="zs-wireframe-browser">
              <div class="zs-wf-chrome">
                <span class="zs-wf-dots"><i></i><i></i><i></i></span>
                <span class="zs-wf-url">https://zeynart.com/</span>
              </div>
              <div class="zs-wf-nav-mock">
                <strong>ZEYNART</strong>
                <span>Üst Menü · TR/EN/DE/RU</span>
              </div>

              <div class="zs-wf-stack" id="zsWfStack">
                ${activeSections.length === 0 ? `
                  <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:12px">Şu an aktif bölüm yok. Soldan en az bir bölümü "Yayında" durumuna getirin.</div>
                ` : activeSections.map((sk, vIdx) => {
                  const meta = getHomeSectionMeta(sk);
                  const realIdx = order.indexOf(sk);
                  return `
                    <div class="zs-wf-block" draggable="true" data-wf-idx="${realIdx}" data-wf-key="${esc(sk)}" style="--wf-accent:${esc(meta.accent)}">
                      <div style="display:flex;align-items:center;gap:7px;min-width:0">
                        <span style="font-weight:800;font-size:10.5px;color:#5eead4">${String(vIdx + 1).padStart(2, '0')}</span>
                        <span>${meta.icon}</span>
                        <strong style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(meta.title)}</strong>
                      </div>
                      <span style="font-size:11px;color:var(--text-dim);flex-shrink:0">⋮⋮</span>
                    </div>
                  `;
                }).join('')}
              </div>

              <div class="zs-wf-foot-mock">
                <span>Alt Footer Menüler</span>
                <span>⚡ Hızlı İletişim Barı</span>
              </div>
            </div>

            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
              <button type="button" class="zs-btn zs-btn-primary" style="width:100%;justify-content:center" id="hlSideSaveBtn">✓ Sıralamayı Kaydet ve Canlıya Al</button>
              <button type="button" class="zs-btn zs-btn-sm" style="width:100%;justify-content:center" id="hlGoVisualBuilderBtn">🧩 Ana Sayfa Görsel Tasarımcıyı Aç →</button>
            </div>
          </div>
        </aside>
      </div>
    `;

    bindHomeSectionCardsEvents(ROOT, () => renderHomeLayoutView());

    // Mini-map Drag & Drop + click-to-highlight
    let wfDragIdx = null;
    const wfBlocks = ROOT.querySelectorAll('[data-wf-idx]');
    wfBlocks.forEach(blk => {
      blk.addEventListener('click', () => {
        const key = blk.getAttribute('data-wf-key');
        const targetCard = ROOT.querySelector(`[data-hsec-card="${key}"]`);
        if (targetCard) {
          targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
          targetCard.style.boxShadow = '0 0 0 2px #2dd4bf, 0 12px 30px rgba(45,212,191,.35)';
          setTimeout(() => { targetCard.style.boxShadow = ''; }, 1100);
        }
      });
      blk.addEventListener('dragstart', e => {
        wfDragIdx = Number(blk.getAttribute('data-wf-idx'));
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
          try { e.dataTransfer.setData('text/plain', String(wfDragIdx)); } catch (_) {}
        }
      });
      blk.addEventListener('dragover', e => {
        if (wfDragIdx === null) return;
        e.preventDefault();
        const rect = blk.getBoundingClientRect();
        const topHalf = (e.clientY - rect.top) < (rect.height / 2);
        blk.classList.toggle('drag-over-top', topHalf);
        blk.classList.toggle('drag-over-bottom', !topHalf);
      });
      blk.addEventListener('dragleave', () => {
        blk.classList.remove('drag-over-top', 'drag-over-bottom');
      });
      blk.addEventListener('drop', e => {
        e.preventDefault();
        const wasTop = blk.classList.contains('drag-over-top');
        blk.classList.remove('drag-over-top', 'drag-over-bottom');
        if (wfDragIdx === null) return;
        const targetIdx = Number(blk.getAttribute('data-wf-idx'));
        let dest = wasTop ? targetIdx : (targetIdx + 1);
        if (wfDragIdx < dest) dest -= 1;
        const arr = S.store.home.section_order;
        if (wfDragIdx !== dest && wfDragIdx >= 0 && wfDragIdx < arr.length) {
          const [moved] = arr.splice(wfDragIdx, 1);
          arr.splice(Math.max(0, Math.min(arr.length, dest)), 0, moved);
          wfDragIdx = null;
          updateSidebarBadges();
          renderHomeLayoutView();
        }
      });
      blk.addEventListener('dragend', () => {
        wfDragIdx = null;
        wfBlocks.forEach(b => b.classList.remove('drag-over-top', 'drag-over-bottom'));
      });
    });

    // Presets
    ROOT.querySelectorAll('[data-hl-preset]').forEach(btn => {
      btn.onclick = () => {
        const pid = btn.getAttribute('data-hl-preset');
        const pr = HOME_LAYOUT_PRESETS.find(x => x.id === pid);
        if (!pr) return;
        S.store.home.section_order = pr.order.slice();
        S.store.home.section_visibility = Object.assign({}, S.store.home.section_visibility || {}, pr.vis);
        ensureNormalizedHomeSections();
        updateSidebarBadges();
        toast(`"${pr.name}" dizilimi uygulandı. Kaydet butonuna basarak yayınlayabilirsiniz.`);
        renderHomeLayoutView();
      };
    });

    document.getElementById('hlEnableAllBtn').onclick = () => {
      const { order: ord, vis: v } = ensureNormalizedHomeSections();
      ord.forEach(k => { v[k] = true; });
      updateSidebarBadges();
      renderHomeLayoutView();
    };

    document.getElementById('hlResetDefaultBtn').onclick = () => {
      const def = HOME_LAYOUT_PRESETS[0];
      S.store.home.section_order = def.order.slice();
      S.store.home.section_visibility = Object.assign({}, def.vis);
      ensureNormalizedHomeSections();
      updateSidebarBadges();
      toast('Varsayılan kurumsal ana sayfa dizilimi yüklendi.');
      renderHomeLayoutView();
    };

    const discardBtn = document.getElementById('hlDiscardBtn');
    if (discardBtn) {
      discardBtn.onclick = () => {
        try {
          const parsed = JSON.parse(S.homeLayoutSavedSnap);
          if (parsed && Array.isArray(parsed.order)) {
            S.store.home.section_order = parsed.order;
            S.store.home.section_visibility = parsed.vis || {};
            ensureNormalizedHomeSections();
            updateSidebarBadges();
            renderHomeLayoutView();
            toast('Değişiklikler geri alındı.');
          }
        } catch (_) {}
      };
    }

    document.getElementById('hlOpenHomeEditorBtn').onclick = () => {
      S.pages.subTab = 'hero';
      openUnifiedPageEditor('anasayfa', 'content');
    };
    document.getElementById('hlGoVisualBuilderBtn').onclick = () => {
      openUnifiedPageEditor('anasayfa', 'builder');
    };

    const doSaveHomeLayout = async () => {
      const { order: ord, vis: v } = ensureNormalizedHomeSections();
      const btns = ['hlMainSaveBtn', 'hlSideSaveBtn', 'hlBannerSaveBtn']
        .map(id => document.getElementById(id))
        .filter(Boolean);
      btns.forEach(b => { b.disabled = true; b.textContent = 'Kaydediliyor...'; });
      try {
        const res = await api('home_layout_save', {
          section_order: ord,
          section_visibility: v
        });
        if (res.store) {
          S.store = res.store;
        } else if (res.home) {
          S.store.home = res.home;
        }
        ensureNormalizedHomeSections();
        S.homeLayoutSavedSnap = computeHomeLayoutSnap();
        updateSidebarBadges();
        toast(res.message || 'Ana sayfa bölüm sıralaması kaydedildi!');
        renderHomeLayoutView();
      } catch (err) {
        toast(err.message, true);
        btns.forEach(b => { b.disabled = false; b.textContent = '✓ Sıralamayı Kaydet ve Canlıya Al'; });
      }
    };

    document.getElementById('hlMainSaveBtn').onclick = doSaveHomeLayout;
    document.getElementById('hlSideSaveBtn').onclick = doSaveHomeLayout;
    const bannerSave = document.getElementById('hlBannerSaveBtn');
    if (bannerSave) bannerSave.onclick = doSaveHomeLayout;
  }

  /* ======================== MODULE 5: VISUAL PAGE & SECTION BUILDER ======================== */
  function colCountForLayout(layout) {
    if (layout === '1-1' || layout === '1-2' || layout === '2-1') return 2;
    if (layout === '1-1-1') return 3;
    if (layout === '1-1-1-1') return 4;
    return 1;
  }

  function createBuilderSection(layout, initialWidgetsPerCol) {
    const count = colCountForLayout(layout);
    const cols = [];
    for (let i = 0; i < count; i++) {
      cols.push({ widgets: (initialWidgetsPerCol && initialWidgetsPerCol[i]) ? initialWidgetsPerCol[i] : [] });
    }
    return {
      id: uid('sec'),
      layout: layout || '1',
      anchor_id: '',
      disabled: false,
      style: {
        padding_y: 64,
        gap: 28,
        bg_color: '',
        bg_image: '',
        container: 'boxed',
        visibility: 'all'
      },
      columns: cols
    };
  }

  function createBuilderWidget(type) {
    const def = WIDGET_CATALOG.find(w => w.type === type) || WIDGET_CATALOG[0];
    return {
      id: uid('w'),
      type: def.type,
      props: JSON.parse(JSON.stringify(def.defaults || {}))
    };
  }

  async function refreshBuilderPreview() {
    try {
      const res = await api('builder_preview_html', { sections: S.builder.sections });
      S.builder.previewHtml = res.html || '<div style="padding:48px;text-align:center;color:#8ea9ae">Henüz bu sayfaya görsel bölüm eklenmedi.</div>';
      const iframe = document.getElementById('zsBuilderPreviewFrame');
      if (iframe) {
        iframe.srcdoc = `<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/><link rel="stylesheet" href="${esc(CFG.siteCssUrl)}"/></head><body style="background:#070f12;color:#edf6f7;margin:0;padding:12px 0">${S.builder.previewHtml}</body></html>`;
      }
    } catch (err) {
      toast(err.message, true);
    }
  }

  function renderBuilderStageMarkup(secs) {
    const selSec = secs[S.builder.selectedSecIdx] || null;
    const selCol = selSec && selSec.columns ? selSec.columns[S.builder.selectedColIdx] : null;
    const selWidget = (selCol && S.builder.selectedWidgetIdx !== null && selCol.widgets)
      ? (selCol.widgets[S.builder.selectedWidgetIdx] || null)
      : null;

    if (S.builder.stageMode === 'preview') {
      return `
        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Gerçek Zamanlı Duyarlı (Responsive) Önizleme</h3>
              <p class="zs-card-sub">Birebir canlı Zeynart tema stilleriyle masaüstü, tablet ve mobil görünümü test edin.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-sm ${S.builder.previewDevice === '100%' ? 'zs-btn-primary' : ''}" data-bdev="100%">🖥 Masaüstü</button>
              <button type="button" class="zs-btn zs-btn-sm ${S.builder.previewDevice === '768px' ? 'zs-btn-primary' : ''}" data-bdev="768px">📱 Tablet (768px)</button>
              <button type="button" class="zs-btn zs-btn-sm ${S.builder.previewDevice === '390px' ? 'zs-btn-primary' : ''}" data-bdev="390px">📲 Mobil (390px)</button>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="bBackToCanvasBtn">⊞ Tasarım Tuvaline Dön</button>
            </div>
          </div>
          <div class="zs-preview-frame-wrap">
            <iframe id="zsBuilderPreviewFrame" class="zs-preview-iframe" style="width:${S.builder.previewDevice}"></iframe>
          </div>
        </div>
      `;
    }

    return `
      <div class="zs-builder-workspace">
        <div class="zs-builder-palette">
          <div style="margin-bottom:14px">
            <strong style="display:block;font-size:13px;margin-bottom:4px">Bileşen Kütüphanesi</strong>
            <span style="font-size:11.5px;color:var(--text-muted)">Sütuna sürükleyin veya tıklayarak seçili sütuna ekleyin.</span>
          </div>
          <div class="zs-widget-grid">
            ${WIDGET_CATALOG.map(w => `
              <div class="zs-widget-tile" draggable="true" data-palette-type="${esc(w.type)}">
                <strong>${esc(w.name)}</strong>
                <span>${esc(w.sub)}</span>
              </div>
            `).join('')}
          </div>

          <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--border-subtle)">
            <strong style="display:block;font-size:12.5px;margin-bottom:8px">Hazır Bölüm Şablonları (1-Tık)</strong>
            <div style="display:grid;gap:6px">
              <button type="button" class="zs-btn zs-btn-sm" style="justify-content:flex-start" data-preset="hero">+ Kapak Tanıtım Bölümü</button>
              <button type="button" class="zs-btn zs-btn-sm" style="justify-content:flex-start" data-preset="story2col">+ 2 Sütun: Metin + Görsel Vitrin</button>
              <button type="button" class="zs-btn zs-btn-sm" style="justify-content:flex-start" data-preset="stats">+ 4’lü Rakamlarla Güç Bandı</button>
              <button type="button" class="zs-btn zs-btn-sm" style="justify-content:flex-start" data-preset="packages">+ Paket / Fiyat Karşılaştırma</button>
              <button type="button" class="zs-btn zs-btn-sm" style="justify-content:flex-start" data-preset="faqContact">+ 2 Sütun: SSS + İletişim Formu</button>
            </div>
          </div>
        </div>

        <div class="zs-builder-stage">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <strong style="font-size:14px">Sayfa Satır & Sütun Mimarisi (${secs.length} Bölüm)</strong>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="bStagePreviewBtn">👁 Canlı Cihaz Önizlemesi</button>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-sm" data-add-row="1">+ Tek Sütun [1]</button>
              <button type="button" class="zs-btn zs-btn-sm" data-add-row="1-1">+ İki Sütun [1:1]</button>
              <button type="button" class="zs-btn zs-btn-sm" data-add-row="1-2">+ [1:2]</button>
              <button type="button" class="zs-btn zs-btn-sm" data-add-row="2-1">+ [2:1]</button>
              <button type="button" class="zs-btn zs-btn-sm" data-add-row="1-1-1">+ Üç Sütun [1:1:1]</button>
              <button type="button" class="zs-btn zs-btn-sm" data-add-row="1-1-1-1">+ Dört Sütun</button>
            </div>
          </div>

          ${secs.length === 0 ? `
            <div class="zs-dropzone" style="padding:48px 24px;cursor:default">
              <h3 style="margin:0 0 6px">Bu sayfada henüz ek görsel blok bulunmuyor</h3>
              <p class="zs-card-sub">Yukarıdan bir sütun düzeni seçin veya sol menüdeki Hazır Bölüm Şablonlarından birine tıklayarak sayfaya dilediğiniz kadar görsel bölüm ekleyin.</p>
            </div>
          ` : secs.map((sec, sIdx) => {
            const isSecSel = sIdx === S.builder.selectedSecIdx && S.builder.selectedWidgetIdx === null;
            return `
              <div class="zs-sec-block ${isSecSel ? 'selected' : ''}" data-sec-idx="${sIdx}">
                <div class="zs-sec-bar">
                  <div style="display:flex;align-items:center;gap:8px;cursor:pointer" data-select-sec="${sIdx}">
                    <span class="zs-pill zs-pill-cat">Bölüm #${sIdx + 1} · Düzen [${esc(sec.layout)}]</span>
                    ${sec.disabled ? `<span class="zs-pill zs-pill-draft">Gizli</span>` : ''}
                    ${sec.anchor_id ? `<span style="color:var(--text-dim)">#${esc(sec.anchor_id)}</span>` : ''}
                  </div>
                  <div style="display:flex;gap:5px">
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:3px 8px" data-sec-act="up:${sIdx}" ${sIdx === 0 ? 'disabled' : ''}>↑</button>
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:3px 8px" data-sec-act="down:${sIdx}" ${sIdx === secs.length - 1 ? 'disabled' : ''}>↓</button>
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:3px 8px" data-sec-act="dup:${sIdx}" title="Bölümü Kopyala">⧉</button>
                    <button type="button" class="zs-btn zs-btn-sm" style="padding:3px 8px" data-sec-act="vis:${sIdx}">${sec.disabled ? 'Göster' : 'Gizle'}</button>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" style="padding:3px 8px" data-sec-act="del:${sIdx}">✕</button>
                  </div>
                </div>
                <div class="zs-sec-cols cols-${esc(sec.layout)}">
                  ${(sec.columns || []).map((col, cIdx) => {
                    const isTargetCol = sIdx === S.builder.selectedSecIdx && cIdx === S.builder.selectedColIdx;
                    return `
                      <div class="zs-col-dropzone" style="${isTargetCol ? 'border-color:rgba(94,234,212,.45)' : ''}" data-drop-col="${sIdx}:${cIdx}">
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--text-dim)">
                          <span>Sütun ${cIdx + 1}</span>
                          ${isTargetCol ? `<span style="color:var(--accent-bright)">● Aktif Sütun</span>` : ''}
                        </div>
                        ${(col.widgets || []).map((w, wIdx) => {
                          const catInfo = WIDGET_CATALOG.find(x => x.type === w.type) || { name: w.type };
                          const isWSel = sIdx === S.builder.selectedSecIdx && cIdx === S.builder.selectedColIdx && wIdx === S.builder.selectedWidgetIdx;
                          const previewTxt = w.props?.title || w.props?.heading || w.props?.eyebrow || w.props?.src || '';
                          return `
                            <div class="zs-canvas-widget ${isWSel ? 'selected' : ''}" draggable="true" data-wpos="${sIdx}:${cIdx}:${wIdx}">
                              <div style="min-width:0">
                                <strong style="display:block;font-size:12.5px">${esc(catInfo.name)}</strong>
                                <span style="display:block;font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(previewTxt)}</span>
                              </div>
                              <div style="display:flex;gap:4px;flex-shrink:0">
                                <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-wmove="${sIdx}:${cIdx}:${wIdx}:-1" ${wIdx === 0 ? 'disabled' : ''}>↑</button>
                                <button type="button" class="zs-btn zs-btn-sm" style="padding:2px 6px" data-wmove="${sIdx}:${cIdx}:${wIdx}:1" ${wIdx === col.widgets.length - 1 ? 'disabled' : ''}>↓</button>
                                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" style="padding:2px 6px" data-wdel="${sIdx}:${cIdx}:${wIdx}">✕</button>
                              </div>
                            </div>
                          `;
                        }).join('')}
                      </div>
                    `;
                  }).join('')}
                </div>
              </div>
            `;
          }).join('')}
        </div>

        <div class="zs-builder-inspector" id="zsBuilderInspector">
          ${renderBuilderInspectorHtml(selSec, selWidget)}
        </div>
      </div>
    `;
  }

  function renderBuilderInspectorHtml(selSec, selWidget) {
    if (!selSec) {
      return `<div style="color:var(--text-muted);font-size:13px">Düzenlemek için orta alandan bir bölüm veya bileşen seçin.</div>`;
    }

    if (!selWidget) {
      const st = selSec.style || {};
      return `
        <div class="zs-card-head" style="margin-bottom:14px">
          <h3>Bölüm #${S.builder.selectedSecIdx + 1} Ayarları</h3>
          <span class="zs-pill zs-pill-cat">Satır Düzeni</span>
        </div>
        <div class="zs-field">
          <label>Sütun Düzeni</label>
          <select id="insSecLayout" class="zs-select">
            ${['1', '1-1', '1-2', '2-1', '1-1-1', '1-1-1-1'].map(l => `<option value="${l}" ${selSec.layout === l ? 'selected' : ''}>Düzen [${l}]</option>`).join('')}
          </select>
        </div>
        <div class="zs-field">
          <label>Bölüm Bağlantı Kimliği (Anchor #id)</label>
          <input id="insSecAnchor" class="zs-input" placeholder="orn: kampanya-detay" value="${esc(selSec.anchor_id || '')}"/>
        </div>
        <div class="zs-grid-2">
          <div class="zs-field">
            <label>Dikey Boşluk (px)</label>
            <input type="number" id="insSecPy" class="zs-input" min="0" max="220" value="${Number(st.padding_y ?? 64)}"/>
          </div>
          <div class="zs-field">
            <label>Sütun Aralığı (px)</label>
            <input type="number" id="insSecGap" class="zs-input" min="0" max="96" value="${Number(st.gap ?? 28)}"/>
          </div>
        </div>
        <div class="zs-grid-2">
          <div class="zs-field">
            <label>Genişlik Modu</label>
            <select id="insSecContainer" class="zs-select">
              <option value="boxed" ${st.container !== 'full' ? 'selected' : ''}>Standart Hizalı</option>
              <option value="full" ${st.container === 'full' ? 'selected' : ''}>Tam Genişlik</option>
            </select>
          </div>
          <div class="zs-field">
            <label>Cihaz Görünürlüğü</label>
            <select id="insSecVis" class="zs-select">
              <option value="all" ${(st.visibility || 'all') === 'all' ? 'selected' : ''}>Tüm Cihazlar</option>
              <option value="desktop" ${st.visibility === 'desktop' ? 'selected' : ''}>Yalnızca Masaüstü</option>
              <option value="mobile" ${st.visibility === 'mobile' ? 'selected' : ''}>Yalnızca Mobil</option>
            </select>
          </div>
        </div>
        <div class="zs-field">
          <label>Arka Plan Rengi (İsteğe Bağlı)</label>
          <input id="insSecBg" class="zs-input" placeholder="örn: #0b181e veya rgba(20,184,166,0.06)" value="${esc(st.bg_color || '')}"/>
        </div>
        <div class="zs-field">
          <label>Arka Plan Görseli</label>
          <div class="zs-input-group">
            <input id="insSecBgImg" class="zs-input" value="${esc(st.bg_image || '')}"/>
            <button type="button" class="zs-btn zs-btn-sm" id="insSecPickBg">Seç</button>
          </div>
        </div>
      `;
    }

    const catInfo = WIDGET_CATALOG.find(x => x.type === selWidget.type) || { name: selWidget.type };
    const p = selWidget.props || {};
    let fieldsHtml = '';

    switch (selWidget.type) {
      case 'hero_banner':
        fieldsHtml = `
          <div class="zs-field"><label>Üst Etiket</label><input data-wp="eyebrow" class="zs-input" value="${esc(p.eyebrow || '')}"/></div>
          <div class="zs-field"><label>Ana Başlık</label><textarea data-wp="title" class="zs-textarea" style="min-height:64px">${esc(p.title || '')}</textarea></div>
          <div class="zs-field"><label>Alt Açıklama</label><textarea data-wp="subtitle" class="zs-textarea">${esc(p.subtitle || '')}</textarea></div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Buton Metni</label><input data-wp="btn_label" class="zs-input" value="${esc(p.btn_label || '')}"/></div>
            <div class="zs-field"><label>Buton Linki</label><input data-wp="btn_url" class="zs-input" value="${esc(p.btn_url || '')}"/></div>
          </div>
          <div class="zs-field"><label>Arka Plan Görseli</label><div class="zs-input-group"><input data-wp="image" class="zs-input" value="${esc(p.image || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-wp-pick="image">Seç</button></div></div>
        `;
        break;

      case 'heading_text':
        fieldsHtml = `
          <div class="zs-field"><label>Üst Etiket</label><input data-wp="eyebrow" class="zs-input" value="${esc(p.eyebrow || '')}"/></div>
          <div class="zs-field"><label>Başlık (H2)</label><input data-wp="title" class="zs-input" value="${esc(p.title || '')}"/></div>
          <div class="zs-field"><label>İkinci Satır Soluk Başlık</label><input data-wp="muted" class="zs-input" value="${esc(p.muted || '')}"/></div>
          <div class="zs-field"><label>Açıklama Metni</label><textarea data-wp="copy" class="zs-textarea">${esc(p.copy || '')}</textarea></div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Buton Metni</label><input data-wp="btn_label" class="zs-input" value="${esc(p.btn_label || '')}"/></div>
            <div class="zs-field"><label>Buton Linki</label><input data-wp="btn_url" class="zs-input" value="${esc(p.btn_url || '')}"/></div>
          </div>
          <label class="zs-switch"><input type="checkbox" data-wp-bool="card_style" ${p.card_style ? 'checked' : ''}/><span>Cam Kart Çerçevesi Kullan</span></label>
        `;
        break;

      case 'rich_text':
      case 'custom_html':
        fieldsHtml = `
          <div class="zs-field"><label>HTML / Zengin İçerik</label><textarea data-wp="html" class="zs-textarea" style="min-height:200px;font-family:Consolas,monospace">${esc(p.html || '')}</textarea></div>
        `;
        break;

      case 'image_showcase':
        fieldsHtml = `
          <div class="zs-field"><label>Görsel Adresi</label><div class="zs-input-group"><input data-wp="src" class="zs-input" value="${esc(p.src || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-wp-pick="src">Seç</button></div></div>
          <div class="zs-field"><label>Görsel Alt Metni (SEO)</label><input data-wp="alt" class="zs-input" value="${esc(p.alt || '')}"/></div>
          <div class="zs-field"><label>Görsel Alt Başlığı</label><input data-wp="caption" class="zs-input" value="${esc(p.caption || '')}"/></div>
        `;
        break;

      case 'gallery_grid': {
        const imgs = Array.isArray(p.images) ? p.images : [];
        fieldsHtml = `
          <div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
            <label style="font-size:12px;color:var(--text-muted)">Galeri Görselleri (${imgs.length})</label>
            <button type="button" class="zs-btn zs-btn-sm" id="wGalAddBtn">+ Görsel Ekle</button>
          </div>
          ${imgs.map((im, i) => `
            <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-gal-idx="${i}">
              <div style="display:flex;gap:6px">
                <input class="zs-input wgal-src" value="${esc(im.src || '')}" placeholder="/uploads/..."/>
                <button type="button" class="zs-btn zs-btn-sm" data-gal-pick="${i}">Seç</button>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-gal-del="${i}">✕</button>
              </div>
              <input class="zs-input wgal-alt" value="${esc(im.alt || '')}" placeholder="Alt açıklama"/>
            </div>
          `).join('')}
        `;
        break;
      }

      case 'stats_bar': {
        const items = Array.isArray(p.items) ? p.items : [];
        fieldsHtml = `
          <div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
            <label style="font-size:12px;color:var(--text-muted)">İstatistik Kutuları</label>
            <button type="button" class="zs-btn zs-btn-sm" id="wStatAddBtn">+ Kutu Ekle</button>
          </div>
          ${items.map((stIt, i) => `
            <div class="zs-sort-row" data-stat-idx="${i}">
              <input class="zs-input wstat-val" style="max-width:95px" value="${esc(stIt.value || '')}" placeholder="62+"/>
              <input class="zs-input wstat-lbl" value="${esc(stIt.label || '')}" placeholder="Açıklama"/>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-stat-del="${i}">✕</button>
            </div>
          `).join('')}
        `;
        break;
      }

      case 'faq_accordion': {
        const fqItems = Array.isArray(p.items) ? p.items : [];
        fieldsHtml = `
          <div class="zs-field"><label>Bölüm Başlığı</label><input data-wp="heading" class="zs-input" value="${esc(p.heading || '')}"/></div>
          <div style="margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
            <label style="font-size:12px;color:var(--text-muted)">Sorular (${fqItems.length})</label>
            <button type="button" class="zs-btn zs-btn-sm" id="wFaqAddBtn">+ Soru Ekle</button>
          </div>
          ${fqItems.map((fq, i) => `
            <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-wfaq-idx="${i}">
              <div style="display:flex;gap:6px">
                <input class="zs-input wfq-q" value="${esc(fq.q || '')}" placeholder="Soru"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-wfaq-del="${i}">✕</button>
              </div>
              <textarea class="zs-textarea wfq-a" style="min-height:56px" placeholder="Cevap">${esc(fq.a || '')}</textarea>
            </div>
          `).join('')}
        `;
        break;
      }

      case 'service_cards':
      case 'client_logos':
        fieldsHtml = `<div class="zs-field"><label>Gösterilecek Kart Sayısı</label><input type="number" data-wp-num="limit" class="zs-input" min="1" max="62" value="${Number(p.limit || 6)}"/></div>`;
        break;

      case 'cta_banner':
        fieldsHtml = `
          <div class="zs-field"><label>Üst Etiket</label><input data-wp="eyebrow" class="zs-input" value="${esc(p.eyebrow || '')}"/></div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Satır 1</label><input data-wp="line1" class="zs-input" value="${esc(p.line1 || '')}"/></div>
            <div class="zs-field"><label>Satır 2</label><input data-wp="line2" class="zs-input" value="${esc(p.line2 || '')}"/></div>
          </div>
          <div class="zs-field"><label>Vurgulu Kelime</label><input data-wp="accent" class="zs-input" value="${esc(p.accent || '')}"/></div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Buton Metni</label><input data-wp="btn_label" class="zs-input" value="${esc(p.btn_label || '')}"/></div>
            <div class="zs-field"><label>Buton Linki</label><input data-wp="btn_url" class="zs-input" value="${esc(p.btn_url || '')}"/></div>
          </div>
        `;
        break;

      case 'spacer_divider':
        fieldsHtml = `
          <div class="zs-field"><label>Boşluk Yüksekliği (px)</label><input type="number" data-wp-num="height" class="zs-input" min="8" max="180" value="${Number(p.height || 40)}"/></div>
          <label class="zs-switch"><input type="checkbox" data-wp-bool="show_line" ${p.show_line ? 'checked' : ''}/><span>İnce Ayırıcı Çizgi Göster</span></label>
        `;
        break;

      default:
        fieldsHtml = `<div style="padding:12px;border-radius:10px;background:rgba(20,184,166,.08);font-size:12.5px;color:var(--text-muted)">Bu bileşen verilerini doğrudan ilgili modülden (Projeler, Süreç, Paketler, Yorumlar veya İletişim Formu) dinamik olarak çeker.</div>`;
    }

    return `
      <div class="zs-card-head" style="margin-bottom:14px">
        <div>
          <h3>${esc(catInfo.name)}</h3>
          <span style="font-size:11px;color:var(--accent-bright)">Bölüm #${S.builder.selectedSecIdx + 1} · Sütun ${S.builder.selectedColIdx + 1}</span>
        </div>
        <button type="button" class="zs-btn zs-btn-sm" id="insBackToSec">Bölüm Ayarı</button>
      </div>
      ${fieldsHtml}
    `;
  }

  function bindSharedBuilderCanvasEvents(reRenderFn) {
    const backCanvasBtn = document.getElementById('bBackToCanvasBtn');
    if (backCanvasBtn) backCanvasBtn.onclick = () => { S.builder.stageMode = 'canvas'; reRenderFn(); };

    const stagePreviewBtn = document.getElementById('bStagePreviewBtn');
    if (stagePreviewBtn) stagePreviewBtn.onclick = () => { S.builder.stageMode = 'preview'; reRenderFn(); };

    ROOT.querySelectorAll('[data-bdev]').forEach(btn => {
      btn.onclick = () => {
        S.builder.previewDevice = btn.getAttribute('data-bdev');
        reRenderFn();
      };
    });

    if (S.builder.stageMode === 'preview') {
      refreshBuilderPreview();
      return;
    }

    ROOT.querySelectorAll('[data-add-row]').forEach(btn => {
      btn.onclick = () => {
        const layout = btn.getAttribute('data-add-row');
        S.builder.sections.push(createBuilderSection(layout));
        S.builder.selectedSecIdx = S.builder.sections.length - 1;
        S.builder.selectedColIdx = 0;
        S.builder.selectedWidgetIdx = null;
        reRenderFn();
      };
    });

    ROOT.querySelectorAll('[data-preset]').forEach(btn => {
      btn.onclick = () => {
        const key = btn.getAttribute('data-preset');
        let sec = null;
        if (key === 'hero') sec = createBuilderSection('1', [[createBuilderWidget('hero_banner')]]);
        if (key === 'story2col') sec = createBuilderSection('1-1', [[createBuilderWidget('heading_text')], [createBuilderWidget('image_showcase')]]);
        if (key === 'stats') sec = createBuilderSection('1', [[createBuilderWidget('stats_bar')]]);
        if (key === 'packages') sec = createBuilderSection('1', [[createBuilderWidget('pricing_packages')]]);
        if (key === 'faqContact') sec = createBuilderSection('1-1', [[createBuilderWidget('faq_accordion')], [createBuilderWidget('contact_form_embed')]]);
        if (sec) {
          S.builder.sections.push(sec);
          S.builder.selectedSecIdx = S.builder.sections.length - 1;
          S.builder.selectedColIdx = 0;
          S.builder.selectedWidgetIdx = 0;
          toast('Hazır bölüm şablonu sayfaya eklendi.');
          reRenderFn();
        }
      };
    });

    let dragPayload = null;
    ROOT.querySelectorAll('[data-palette-type]').forEach(tile => {
      const wType = tile.getAttribute('data-palette-type');
      tile.onclick = () => {
        if (S.builder.sections.length === 0) {
          S.builder.sections.push(createBuilderSection('1'));
          S.builder.selectedSecIdx = 0;
          S.builder.selectedColIdx = 0;
        }
        const sec = S.builder.sections[S.builder.selectedSecIdx] || S.builder.sections[0];
        const col = sec.columns[S.builder.selectedColIdx] || sec.columns[0];
        col.widgets.push(createBuilderWidget(wType));
        S.builder.selectedWidgetIdx = col.widgets.length - 1;
        reRenderFn();
      };
      tile.ondragstart = e => {
        dragPayload = { kind: 'new', type: wType };
        e.dataTransfer.effectAllowed = 'copy';
      };
    });

    ROOT.querySelectorAll('[data-wpos]').forEach(el => {
      const [sIdx, cIdx, wIdx] = el.getAttribute('data-wpos').split(':').map(Number);
      el.onclick = e => {
        if (e.target.closest('button')) return;
        e.stopPropagation();
        S.builder.selectedSecIdx = sIdx;
        S.builder.selectedColIdx = cIdx;
        S.builder.selectedWidgetIdx = wIdx;
        reRenderFn();
      };
      el.ondragstart = e => {
        dragPayload = { kind: 'move', sIdx, cIdx, wIdx };
        e.dataTransfer.effectAllowed = 'move';
      };
    });

    ROOT.querySelectorAll('[data-drop-col]').forEach(zone => {
      const [sIdx, cIdx] = zone.getAttribute('data-drop-col').split(':').map(Number);
      zone.onclick = e => {
        if (e.target.closest('[data-wpos]') || e.target.closest('button')) return;
        S.builder.selectedSecIdx = sIdx;
        S.builder.selectedColIdx = cIdx;
        S.builder.selectedWidgetIdx = null;
        reRenderFn();
      };
      zone.ondragover = e => { e.preventDefault(); zone.classList.add('drag-over'); };
      zone.ondragleave = () => zone.classList.remove('drag-over');
      zone.ondrop = e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        if (!dragPayload) return;
        const targetCol = S.builder.sections[sIdx].columns[cIdx];
        if (dragPayload.kind === 'new') {
          targetCol.widgets.push(createBuilderWidget(dragPayload.type));
          S.builder.selectedSecIdx = sIdx;
          S.builder.selectedColIdx = cIdx;
          S.builder.selectedWidgetIdx = targetCol.widgets.length - 1;
        } else if (dragPayload.kind === 'move') {
          const srcCol = S.builder.sections[dragPayload.sIdx].columns[dragPayload.cIdx];
          const [moved] = srcCol.widgets.splice(dragPayload.wIdx, 1);
          if (moved) {
            targetCol.widgets.push(moved);
            S.builder.selectedSecIdx = sIdx;
            S.builder.selectedColIdx = cIdx;
            S.builder.selectedWidgetIdx = targetCol.widgets.length - 1;
          }
        }
        dragPayload = null;
        reRenderFn();
      };
    });

    ROOT.querySelectorAll('[data-wmove]').forEach(btn => {
      btn.onclick = e => {
        e.stopPropagation();
        const [sIdx, cIdx, wIdx, dir] = btn.getAttribute('data-wmove').split(':').map(Number);
        const arr = S.builder.sections[sIdx].columns[cIdx].widgets;
        const target = wIdx + dir;
        if (target >= 0 && target < arr.length) {
          [arr[wIdx], arr[target]] = [arr[target], arr[wIdx]];
          S.builder.selectedWidgetIdx = target;
          reRenderFn();
        }
      };
    });

    ROOT.querySelectorAll('[data-wdel]').forEach(btn => {
      btn.onclick = e => {
        e.stopPropagation();
        const [sIdx, cIdx, wIdx] = btn.getAttribute('data-wdel').split(':').map(Number);
        S.builder.sections[sIdx].columns[cIdx].widgets.splice(wIdx, 1);
        S.builder.selectedWidgetIdx = null;
        reRenderFn();
      };
    });

    ROOT.querySelectorAll('[data-select-sec]').forEach(el => {
      el.onclick = () => {
        S.builder.selectedSecIdx = Number(el.getAttribute('data-select-sec'));
        S.builder.selectedWidgetIdx = null;
        reRenderFn();
      };
    });

    ROOT.querySelectorAll('[data-sec-act]').forEach(btn => {
      btn.onclick = () => {
        const [act, rawIdx] = btn.getAttribute('data-sec-act').split(':');
        const idx = Number(rawIdx);
        const secs = S.builder.sections;
        if (act === 'up' && idx > 0) {
          [secs[idx - 1], secs[idx]] = [secs[idx], secs[idx - 1]];
          S.builder.selectedSecIdx = idx - 1;
        } else if (act === 'down' && idx < secs.length - 1) {
          [secs[idx + 1], secs[idx]] = [secs[idx], secs[idx + 1]];
          S.builder.selectedSecIdx = idx + 1;
        } else if (act === 'dup') {
          const clone = JSON.parse(JSON.stringify(secs[idx]));
          clone.id = uid('sec');
          secs.splice(idx + 1, 0, clone);
          S.builder.selectedSecIdx = idx + 1;
        } else if (act === 'vis') {
          secs[idx].disabled = !secs[idx].disabled;
        } else if (act === 'del') {
          secs.splice(idx, 1);
          S.builder.selectedSecIdx = Math.max(0, secs.length - 1);
          S.builder.selectedWidgetIdx = null;
        }
        reRenderFn();
      };
    });

    const selSec = S.builder.sections[S.builder.selectedSecIdx];
    if (!selSec) return;
    const selCol = selSec.columns?.[S.builder.selectedColIdx];
    const selWidget = (selCol && S.builder.selectedWidgetIdx !== null) ? selCol.widgets?.[S.builder.selectedWidgetIdx] : null;

    if (!selWidget) {
      const laySel = document.getElementById('insSecLayout');
      if (laySel) {
        laySel.onchange = e => {
          const newLay = e.target.value;
          const needed = colCountForLayout(newLay);
          selSec.layout = newLay;
          while (selSec.columns.length < needed) selSec.columns.push({ widgets: [] });
          if (selSec.columns.length > needed) {
            const overflow = selSec.columns.splice(needed);
            overflow.forEach(oc => selSec.columns[needed - 1].widgets.push(...(oc.widgets || [])));
          }
          reRenderFn();
        };
      }
      const bindSecField = (id, cb) => {
        const el = document.getElementById(id);
        if (el) el.oninput = () => cb(el.value);
      };
      selSec.style = selSec.style || {};
      bindSecField('insSecAnchor', v => { selSec.anchor_id = v.trim(); });
      bindSecField('insSecPy', v => { selSec.style.padding_y = Number(v || 0); });
      bindSecField('insSecGap', v => { selSec.style.gap = Number(v || 0); });
      bindSecField('insSecContainer', v => { selSec.style.container = v; });
      bindSecField('insSecVis', v => { selSec.style.visibility = v; });
      bindSecField('insSecBg', v => { selSec.style.bg_color = v.trim(); });
      bindSecField('insSecBgImg', v => { selSec.style.bg_image = v.trim(); });
      const pickBg = document.getElementById('insSecPickBg');
      if (pickBg) {
        pickBg.onclick = () => openMediaPicker(url => {
          selSec.style.bg_image = url;
          reRenderFn();
        });
      }
    } else {
      const backBtn = document.getElementById('insBackToSec');
      if (backBtn) backBtn.onclick = () => { S.builder.selectedWidgetIdx = null; reRenderFn(); };

      selWidget.props = selWidget.props || {};
      ROOT.querySelectorAll('[data-wp]').forEach(inp => {
        inp.oninput = () => { selWidget.props[inp.getAttribute('data-wp')] = inp.value; };
      });
      ROOT.querySelectorAll('[data-wp-num]').forEach(inp => {
        inp.oninput = () => { selWidget.props[inp.getAttribute('data-wp-num')] = Number(inp.value || 0); };
      });
      ROOT.querySelectorAll('[data-wp-bool]').forEach(inp => {
        inp.onchange = () => { selWidget.props[inp.getAttribute('data-wp-bool')] = inp.checked; };
      });
      ROOT.querySelectorAll('[data-wp-pick]').forEach(btn => {
        btn.onclick = () => {
          const prop = btn.getAttribute('data-wp-pick');
          openMediaPicker(url => {
            selWidget.props[prop] = url;
            reRenderFn();
          });
        };
      });

      const galAdd = document.getElementById('wGalAddBtn');
      if (galAdd) {
        const syncGal = () => {
          selWidget.props.images = Array.from(ROOT.querySelectorAll('[data-gal-idx]')).map(r => ({
            src: r.querySelector('.wgal-src').value.trim(),
            alt: r.querySelector('.wgal-alt').value.trim()
          }));
        };
        ROOT.querySelectorAll('[data-gal-idx] input').forEach(i => { i.oninput = syncGal; });
        galAdd.onclick = () => {
          syncGal();
          openMediaPicker((url, alt) => {
            selWidget.props.images.push({ src: url, alt: alt || '' });
            reRenderFn();
          }, { allowMultiple: true });
        };
        ROOT.querySelectorAll('[data-gal-pick]').forEach(b => {
          b.onclick = () => {
            syncGal();
            const idx = Number(b.getAttribute('data-gal-pick'));
            openMediaPicker((url, alt) => {
              selWidget.props.images[idx].src = url;
              if (!selWidget.props.images[idx].alt) selWidget.props.images[idx].alt = alt;
              reRenderFn();
            });
          };
        });
        ROOT.querySelectorAll('[data-gal-del]').forEach(b => {
          b.onclick = () => { syncGal(); selWidget.props.images.splice(Number(b.getAttribute('data-gal-del')), 1); reRenderFn(); };
        });
      }

      const statAdd = document.getElementById('wStatAddBtn');
      if (statAdd) {
        const syncStat = () => {
          selWidget.props.items = Array.from(ROOT.querySelectorAll('[data-stat-idx]')).map(r => ({
            value: r.querySelector('.wstat-val').value.trim(),
            label: r.querySelector('.wstat-lbl').value.trim()
          }));
        };
        ROOT.querySelectorAll('[data-stat-idx] input').forEach(i => { i.oninput = syncStat; });
        statAdd.onclick = () => { syncStat(); selWidget.props.items.push({ value: '100%', label: 'Yeni Metrik' }); reRenderFn(); };
        ROOT.querySelectorAll('[data-stat-del]').forEach(b => {
          b.onclick = () => { syncStat(); selWidget.props.items.splice(Number(b.getAttribute('data-stat-del')), 1); reRenderFn(); };
        });
      }

      const faqAdd = document.getElementById('wFaqAddBtn');
      if (faqAdd) {
        const syncWFaq = () => {
          selWidget.props.items = Array.from(ROOT.querySelectorAll('[data-wfaq-idx]')).map(r => ({
            q: r.querySelector('.wfq-q').value.trim(),
            a: r.querySelector('.wfq-a').value.trim()
          }));
        };
        ROOT.querySelectorAll('[data-wfaq-idx] input, [data-wfaq-idx] textarea').forEach(i => { i.oninput = syncWFaq; });
        faqAdd.onclick = () => { syncWFaq(); selWidget.props.items.push({ q: 'Yeni soru?', a: 'Yanıt metni.' }); reRenderFn(); };
        ROOT.querySelectorAll('[data-wfaq-del]').forEach(b => {
          b.onclick = () => { syncWFaq(); selWidget.props.items.splice(Number(b.getAttribute('data-wfaq-del')), 1); reRenderFn(); };
        });
      }
    }
  }

  // ASCAM: Kayıt galerisi (Ürün grubu / Proje) — geriye uyumlu `gallery: [{src, alt}]` alanı
  function renderRecordGalleryCard(gallery) {
    const imgs = Array.isArray(gallery) ? gallery : [];
    return `
      <div class="zs-card" id="recGalleryCard">
        <div class="zs-card-head">
          <div>
            <h3 style="margin:0">🖼️ Galeri (${imgs.length} görsel)</h3>
            <p class="zs-card-sub">Detay sayfasındaki galeri bu sırayla gösterilir. Çoklu seçim ve yükleme desteklenir. Banner boşsa ilk görsel kapak olarak kullanılır.</p>
          </div>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="recGalAddBtn">+ Görsel Ekle / Yükle</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px">
          ${imgs.map((im, i) => `
            <div class="zs-sort-row" style="flex-direction:column;align-items:stretch;gap:6px;margin:0" data-recgal-idx="${i}">
              <div style="aspect-ratio:4/3;border-radius:8px;overflow:hidden;background:#0b1418"><img src="${esc(resolveUrl(im.src || ''))}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover"/></div>
              <input class="zs-input recgal-src" value="${esc(im.src || '')}" placeholder="/uploads/..."/>
              <input class="zs-input recgal-alt" value="${esc(im.alt || '')}" placeholder="Alt metin (SEO)"/>
              <div style="display:flex;gap:4px">
                <span class="zs-badge-pill" style="margin-right:auto">${String(i + 1).padStart(2, '0')}</span>
                <button type="button" class="zs-btn zs-btn-sm" data-recgal-move="${i}:-1" ${i === 0 ? 'disabled' : ''} title="Yukarı">↑</button>
                <button type="button" class="zs-btn zs-btn-sm" data-recgal-move="${i}:1" ${i === imgs.length - 1 ? 'disabled' : ''} title="Aşağı">↓</button>
                <button type="button" class="zs-btn zs-btn-sm" data-recgal-pick="${i}" title="Değiştir">Seç</button>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-recgal-del="${i}" title="Kaldır">✕</button>
              </div>
            </div>
          `).join('')}
        </div>
      </div>`;
  }

  function collectRecordGalleryFromDom() {
    if (!document.getElementById('recGalleryCard')) return null;
    return Array.from(ROOT.querySelectorAll('[data-recgal-idx]')).map(r => ({
      src: r.querySelector('.recgal-src').value.trim(),
      alt: r.querySelector('.recgal-alt').value.trim()
    })).filter(x => x.src);
  }

  function bindRecordGalleryCard(getRecord, syncFn, rerenderFn) {
    if (!document.getElementById('recGalleryCard')) return;
    const withRec = (fn) => {
      syncFn();
      const rec = getRecord();
      if (!rec) return;
      rec.gallery = Array.isArray(rec.gallery) ? rec.gallery : [];
      fn(rec);
    };
    const addBtn = document.getElementById('recGalAddBtn');
    if (addBtn) addBtn.onclick = () => withRec(rec => {
      openMediaPicker((url, alt) => { rec.gallery.push({ src: url, alt: alt || '' }); rerenderFn(); }, { allowMultiple: true });
    });
    ROOT.querySelectorAll('[data-recgal-pick]').forEach(b => { b.onclick = () => withRec(rec => {
      const idx = Number(b.getAttribute('data-recgal-pick'));
      openMediaPicker((url, alt) => {
        if (rec.gallery[idx]) { rec.gallery[idx].src = url; if (!rec.gallery[idx].alt) rec.gallery[idx].alt = alt || ''; }
        rerenderFn();
      });
    }); });
    ROOT.querySelectorAll('[data-recgal-del]').forEach(b => { b.onclick = () => withRec(rec => {
      rec.gallery.splice(Number(b.getAttribute('data-recgal-del')), 1);
      rerenderFn();
    }); });
    ROOT.querySelectorAll('[data-recgal-move]').forEach(b => { b.onclick = () => withRec(rec => {
      const [i, d] = b.getAttribute('data-recgal-move').split(':').map(Number);
      const j = i + d;
      if (j < 0 || j >= rec.gallery.length) return;
      [rec.gallery[i], rec.gallery[j]] = [rec.gallery[j], rec.gallery[i]];
      rerenderFn();
    }); });
  }

  function openUnifiedPageEditor(pageKey, editorTab, subTab) {
    markHistoryPush();
    const nextKey = pageKey || 'anasayfa';
    if (typeof subTab === 'string') {
      S.pages.subTab = subTab;
    } else if (S.pages.activeKey !== nextKey) {
      S.pages.subTab = '';
    }
    S.view = 'pages';
    S.pages.mode = 'edit';
    S.pages.activeKey = nextKey;
    S.pages.editorTab = editorTab || 'content';
    S.pages.sourceMode = false;
    S.builder.selectedSecIdx = 0;
    S.builder.selectedColIdx = 0;
    S.builder.selectedWidgetIdx = null;
    S.builder.stageMode = 'canvas';
    document.querySelectorAll('#zsNav .zs-nav-item-wrap').forEach(w => {
      w.classList.remove('is-peek-open');
      if (w.getAttribute('data-nav-wrap') === 'pages') {
        w.classList.remove('is-collapsed');
      }
    });
    syncSidebarActiveTree();
    const titleEl = document.getElementById('zsViewTitle');
    if (titleEl) {
      titleEl.textContent = nextKey === 'iletisim'
        ? VIEW_TITLES.contact
        : (nextKey === 'anasayfa' ? VIEW_TITLES.home : VIEW_TITLES.pages);
    }
    document.getElementById('zsSidebar')?.classList.remove('open');
    renderPagesView();
  }

  function startNewUnifiedCustomPage() {
    S.pages.draftCustom = {
      id: '',
      slug: '',
      title: '',
      eyebrow: 'ZEYNART STÜDYO',
      excerpt: '',
      banner_image: '',
      status: 'published',
      show_page_header: true,
      show_cta: true,
      content_html: '<h2>Sayfa Alt Başlığı</h2>\n<p>Sayfanızın içeriğini buraya yazabilir, üstteki araç çubuğundan başlıklar, listeler, bağlantılar ve görseller ekleyebilir veya Görsel Sürükle-Bırak Tasarımcı sekmesinden özel bloklar oluşturabilirsiniz.</p>',
      sections: [],
      faqs: [],
      seo: {
        meta_title: '',
        meta_desc: '',
        focus_keyword: '',
        robots: 'index, follow',
        og_title: '',
        og_desc: '',
        og_image: '',
        ai_summary: ''
      }
    };
    openUnifiedPageEditor('custom:__new__', 'content');
  }

  async function createNewUnifiedServiceSubpage() {
    const services = S.store.services || [];
    const num = String(services.length + 1).padStart(2, '0');
    const newSlug = 'yeni-hizmet-' + num + '-' + Math.random().toString(36).slice(2, 5);
    services.push({
      slug: newSlug,
      num,
      title: 'Yeni Hizmet Alanı',
      eyebrow: 'UZMANLIK ALANI',
      icon: 'sparkles',
      card_class: 'studio-service-web-tasarim',
      short: 'Markanıza özel yeni nesil dijital çözüm.',
      card_tag: 'Strateji / Teknoloji',
      headline: 'İşletmeniz için değer üreten yaklaşım.',
      description: 'Kapsamı ve teslimatları markanıza özel olarak şekillendirilen hizmet alanı.',
      banner_image: '/images/banners/services.webp',
      layout_type: 'standard',
      deliverables: ['Analiz ve planlama', 'Tasarım ve geliştirme', 'Yayın ve teknik destek'],
      approach_copy: 'İhtiyacınızı birlikte netleştiriyor ve adım adım hayata geçiriyoruz.',
      stack: ['Strateji', 'Tasarım', 'Yazılım'],
      faqs: [],
      builder_blocks: [],
      seo: { meta_title: 'Yeni Hizmet Alanı | ZEYNART', meta_desc: '', focus_keyword: '', ai_summary: '' }
    });
    try {
      const res = await api('unified_pages_save', { services, toast_message: 'Yeni hizmet alt sayfası oluşturuldu.' });
      S.store = res.store;
      updateSidebarBadges();
      toast(res.message);
      openUnifiedPageEditor('service:' + newSlug, 'content');
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function createNewUnifiedProjectSubpage() {
    const projects = S.store.projects || [];
    const newSlug = 'yeni-proje-' + (projects.length + 1) + '-' + Math.random().toString(36).slice(2, 5);
    projects.push({
      slug: newSlug,
      name: 'Yeni Proje Konsepti',
      trademark: '®',
      eyebrow: 'PROJE VAKA ÇALIŞMASI',
      tag_label: 'PROJE VİTRİNİ',
      visual_type: 'orbit',
      image: '',
      scope: 'Web Tasarım · Yazılım',
      sector: 'Kurumsal',
      year: String(new Date().getFullYear()),
      work_type: 'Dijital ürün çalışması',
      headline: 'Markaya özel modern dijital deneyim.',
      summary: 'Projenin hedeflerini ve ürettiği değeri özetleyen tanıtım metni.',
      problem: '',
      approach: '',
      solution: '',
      result: '',
      stack: ['PHP 8', 'UI/UX', 'SEO'],
      related_service_url: '/hizmetler/web-tasarim/',
      interface_brand: 'zeynart',
      interface_greeting: 'Dijital dönüşüm.',
      interface_blocks: ['Strateji', 'Tasarım', 'Performans'],
      interface_footer: 'Zeynart Stüdyo',
      builder_blocks: [],
      seo: { meta_title: 'Yeni Proje Konsepti | ZEYNART', meta_desc: '' }
    });
    try {
      const res = await api('unified_pages_save', { projects, toast_message: 'Yeni proje alt sayfası oluşturuldu.' });
      S.store = res.store;
      updateSidebarBadges();
      toast(res.message);
      openUnifiedPageEditor('project:' + newSlug, 'content');
    } catch (err) {
      toast(err.message, true);
    }
  }

  function makeUniqueCloneSlug(baseRaw, usedSet) {
    const cleanBase = slugifyClient((baseRaw || 'sayfa').replace(/-kopya(-\d+)?$/, '')) || 'sayfa';
    let cand = cleanBase + '-kopya';
    let n = 2;
    while (usedSet.has(cand)) {
      cand = cleanBase + '-kopya-' + (n++);
    }
    usedSet.add(cand);
    return cand;
  }

  function synthesizeCustomCloneFromCoreOrLegal(pg, usedSlugs) {
    const key = pg.key;
    const coreKey = key.startsWith('legal:') ? key.slice(6) : key;
    const newId = 'page-' + Math.random().toString(36).slice(2, 10);
    const cloneSlug = makeUniqueCloneSlug(pg.slug || coreKey, usedSlugs);
    const builderClone = JSON.parse(JSON.stringify(S.store.core_page_blocks?.[coreKey] || []));
    let eyebrow = 'ZEYNART STÜDYO';
    let excerpt = pg.metaDesc || '';
    let contentHtml = '';

    if (key.startsWith('legal:')) {
      const doc = S.store.legal_pages?.[coreKey] || {};
      eyebrow = doc.eyebrow || 'ŞEFFAFLIK';
      excerpt = doc.lead || excerpt;
      const parts = [];
      if (doc.content_html && String(doc.content_html).trim()) {
        parts.push(String(doc.content_html).trim());
      }
      (doc.sections || []).forEach(sec => {
        if (sec.heading || sec.body) {
          parts.push(`<h2>${esc(sec.heading || '')}</h2>\n<p>${esc(sec.body || '').replace(/\n/g, '<br/>')}</p>`);
        }
      });
      contentHtml = parts.join('\n\n') || `<p>${esc(excerpt || pg.title)}</p>`;
    } else if (key === 'hakkimizda') {
      const ab = S.store.about || {};
      eyebrow = ab.eyebrow || 'KURUMSAL';
      excerpt = ab.lead || excerpt;
      const paras = (ab.story_paragraphs || []).map(p => `<p>${esc(p)}</p>`).join('\n');
      const vals = (ab.values || []).map(v => `<li><strong>${esc(v.title || '')}:</strong> ${esc(v.desc || '')}</li>`).join('\n');
      contentHtml = [
        ab.story_h2_1 ? `<h2>${esc((ab.story_h2_1 + ' ' + (ab.story_h2_muted || '')).trim())}</h2>` : '',
        paras,
        vals ? `<h3>Temel İlkeler</h3>\n<ul>\n${vals}\n</ul>` : ''
      ].filter(Boolean).join('\n\n') || `<p>${esc(excerpt || pg.title)}</p>`;
    } else if (key === 'iletisim') {
      const ct = S.store.contact || {};
      eyebrow = ct.eyebrow || 'İLETİŞİM';
      excerpt = ct.lead || excerpt;
      const stepsHtml = (ct.steps || []).map(s => `<li><strong>${esc(s.num || '')} — ${esc(s.title || '')}:</strong> ${esc(s.desc || '')}</li>`).join('\n');
      contentHtml = [
        `<h2>${esc(((ct.heading_line1 || '') + ' ' + (ct.heading_muted || '')).trim() || pg.title)}</h2>`,
        ct.sidebar_copy ? `<p>${esc(ct.sidebar_copy)}</p>` : '',
        stepsHtml ? `<ul>\n${stepsHtml}\n</ul>` : ''
      ].filter(Boolean).join('\n\n');
    } else if (key === 'hizmetler') {
      const sp = S.store.services_page || {};
      eyebrow = sp.eyebrow || 'HİZMETLERİMİZ';
      excerpt = sp.lead || excerpt;
      contentHtml = `<h2>${esc(((sp.heading_line1 || '') + ' ' + (sp.heading_muted || '')).trim() || pg.title)}</h2>\n<p>${esc(excerpt)}</p>`;
    } else if (key === 'projeler') {
      const pp = S.store.projects_page || {};
      eyebrow = pp.eyebrow || 'SEÇİLİ ÇALIŞMALAR';
      excerpt = pp.lead || excerpt;
      contentHtml = `<h2>${esc(((pp.heading_line1 || '') + ' ' + (pp.heading_muted || '')).trim() || pg.title)}</h2>\n<p>${esc(excerpt)}</p>`;
    } else if (key === 'blog') {
      const bs = S.store.blog_settings || {};
      eyebrow = bs.eyebrow || 'YAZILAR';
      excerpt = bs.lead || bs.subheading || excerpt;
      contentHtml = `<h2>${esc(pg.title)}</h2>\n<p>${esc(excerpt)}</p>`;
    } else {
      const hi = S.store.home?.intro || {};
      eyebrow = hi.eyebrow || 'ZEYNART';
      excerpt = hi.lead || excerpt;
      contentHtml = `<h2>${esc(pg.title)}</h2>\n<p>${esc(excerpt || 'Bu sayfa ana sayfa şablonundan çoğaltılmıştır. İçeriği dilediğiniz gibi düzenleyebilirsiniz.')}</p>`;
    }

    return {
      id: newId,
      slug: cloneSlug,
      title: pg.title + ' (Kopya)',
      eyebrow,
      excerpt,
      banner_image: pg.bannerImage || '',
      status: 'draft',
      show_page_header: true,
      show_cta: true,
      content_html: contentHtml,
      sections: builderClone,
      faqs: [],
      seo: {
        meta_title: pg.metaTitle ? (pg.metaTitle.replace(/\s*\|\s*ZEYNART$/i, '') + ' (Kopya) | ZEYNART') : (pg.title + ' (Kopya) | ZEYNART'),
        meta_desc: pg.metaDesc || '',
        focus_keyword: pg.focusKeyword || '',
        robots: pg.robots || 'index, follow',
        og_title: pg.ogTitle || '',
        og_desc: pg.ogDesc || '',
        og_image: pg.ogImage || '',
        ai_summary: pg.aiSummary || ''
      }
    };
  }

  async function duplicateUnifiedPages(pageKeys) {
    const keys = Array.isArray(pageKeys) ? pageKeys : [pageKeys];
    if (!keys.length) return;
    const allCat = buildUnifiedPagesCatalog({ includeTrash: true });
    const usedSlugs = new Set([
      ...Object.values(DEFAULT_PAGE_SLUGS),
      ...Object.values(S.store.page_slugs || {}),
      ...(S.store.custom_pages || []).map(c => c.slug),
      ...(S.store.services || []).map(s => s.slug),
      ...(S.store.projects || []).map(p => p.slug)
    ].filter(Boolean));

    const payload = {};
    let clonedCount = 0;

    try {
      keys.forEach(pageKey => {
        if (pageKey.startsWith('custom:')) {
          const id = pageKey.slice(7);
          const orig = (S.store.custom_pages || []).find(p => p.id === id);
          if (!orig) return;
          const clone = JSON.parse(JSON.stringify(orig));
          clone.id = 'page-' + Math.random().toString(36).slice(2, 10);
          clone.title = (orig.title || 'Sayfa').replace(/\s*\(Kopya\)$/i, '') + ' (Kopya)';
          clone.slug = makeUniqueCloneSlug(orig.slug || orig.title, usedSlugs);
          clone.status = 'draft';
          delete clone.trashed_at;
          S.store.custom_pages = S.store.custom_pages || [];
          S.store.custom_pages.push(clone);
          payload.custom_pages = S.store.custom_pages;
          clonedCount++;
        } else if (pageKey.startsWith('service:')) {
          const slug = pageKey.slice(8);
          const orig = (S.store.services || []).find(s => s.slug === slug);
          if (!orig) return;
          const clone = JSON.parse(JSON.stringify(orig));
          clone.title = (orig.title || 'Hizmet').replace(/\s*\(Kopya\)$/i, '') + ' (Kopya)';
          clone.slug = makeUniqueCloneSlug(orig.slug || orig.title, usedSlugs);
          clone.num = String((S.store.services || []).length + 1).padStart(2, '0');
          clone.status = 'draft';
          S.store.services = S.store.services || [];
          S.store.services.push(clone);
          payload.services = S.store.services;
          clonedCount++;
        } else if (pageKey.startsWith('project:')) {
          const slug = pageKey.slice(8);
          const orig = (S.store.projects || []).find(p => p.slug === slug);
          if (!orig) return;
          const clone = JSON.parse(JSON.stringify(orig));
          clone.name = (orig.name || 'Proje').replace(/\s*\(Kopya\)$/i, '') + ' (Kopya)';
          clone.slug = makeUniqueCloneSlug(orig.slug || orig.name, usedSlugs);
          clone.status = 'draft';
          S.store.projects = S.store.projects || [];
          S.store.projects.push(clone);
          payload.projects = S.store.projects;
          clonedCount++;
        } else {
          const pg = allCat.find(x => x.key === pageKey);
          if (!pg) return;
          const synth = synthesizeCustomCloneFromCoreOrLegal(pg, usedSlugs);
          S.store.custom_pages = S.store.custom_pages || [];
          S.store.custom_pages.push(synth);
          payload.custom_pages = S.store.custom_pages;
          clonedCount++;
        }
      });

      if (!clonedCount) return;
      payload.toast_message = clonedCount === 1
        ? 'Sayfa başarıyla çoğaltıldı (Taslak olarak eklendi).'
        : `${clonedCount} sayfa başarıyla çoğaltıldı (Taslak olarak eklendi).`;
      const res = await api('unified_pages_save', payload);
      S.store = res.store;
      S.pages.selectedKeys = [];
      updateSidebarBadges();
      toast(res.message);
      if (S.pages.mode === 'edit') S.pages.mode = 'list';
      renderPagesView();
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function duplicateUnifiedPage(pageKey) {
    return duplicateUnifiedPages([pageKey]);
  }

  async function trashUnifiedPages(pageKeys) {
    const keys = (Array.isArray(pageKeys) ? pageKeys : [pageKeys]).filter(k => k && k !== 'anasayfa');
    if (!keys.length) {
      toast('Ana sayfa (/) kök dizin olduğu için çöp kutusuna taşınamaz.', true);
      return;
    }
    const allCat = buildUnifiedPagesCatalog({ includeTrash: true });
    const nowIso = new Date().toISOString();
    const payload = { menus: S.store.menus };
    let movedCount = 0;

    keys.forEach(pageKey => {
      const item = allCat.find(x => x.key === pageKey);
      if (!item || item.status === 'trash') return;
      const prevMenu = {
        in_header: Boolean(item.inHeaderMenu),
        in_footer: Boolean(item.inFooterMenu),
        footer_col: Number(item.footerColIdx || 0),
        menu_label: item.menuLabel || item.title
      };
      setPageMenuPlacement(item.url, item.title, false, false, 0);

      if (pageKey.startsWith('custom:')) {
        const cp = (S.store.custom_pages || []).find(p => p.id === pageKey.slice(7));
        if (cp) {
          cp.status = 'trash';
          cp.trashed_at = nowIso;
          cp.prev_menu = prevMenu;
          payload.custom_pages = S.store.custom_pages;
          movedCount++;
        }
      } else if (pageKey.startsWith('service:')) {
        const sv = (S.store.services || []).find(s => s.slug === pageKey.slice(8));
        if (sv) {
          sv.status = 'trash';
          sv.trashed_at = nowIso;
          sv.prev_menu = prevMenu;
          payload.services = S.store.services;
          movedCount++;
        }
      } else if (pageKey.startsWith('project:')) {
        const pj = (S.store.projects || []).find(p => p.slug === pageKey.slice(8));
        if (pj) {
          pj.status = 'trash';
          pj.trashed_at = nowIso;
          pj.prev_menu = prevMenu;
          payload.projects = S.store.projects;
          movedCount++;
        }
      } else {
        const cleanKey = pageKey.startsWith('legal:') ? pageKey.slice(6) : pageKey;
        S.store.page_states = S.store.page_states || {};
        S.store.page_states[cleanKey] = {
          status: 'trash',
          trashed_at: nowIso,
          prev_menu: prevMenu
        };
        payload.page_states = S.store.page_states;
        movedCount++;
      }
    });

    if (!movedCount) return;
    payload.menus = S.store.menus;
    payload.toast_message = movedCount === 1
      ? 'Sayfa çöp kutusuna taşındı. İstediğiniz zaman Çöp Kutusu sekmesinden geri yükleyebilirsiniz.'
      : `${movedCount} sayfa çöp kutusuna taşındı.`;

    try {
      const res = await api('unified_pages_save', payload);
      S.store = res.store;
      S.pages.selectedKeys = S.pages.selectedKeys.filter(k => !keys.includes(k));
      if (keys.includes(S.pages.activeKey)) {
        S.pages.mode = 'list';
        S.pages.activeKey = 'anasayfa';
      }
      updateSidebarBadges();
      toast(res.message);
      renderPagesView();
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function restoreUnifiedPages(pageKeys) {
    const keys = (Array.isArray(pageKeys) ? pageKeys : [pageKeys]).filter(Boolean);
    if (!keys.length) return;
    const allCat = buildUnifiedPagesCatalog({ includeTrash: true });
    const payload = {};
    let restoredCount = 0;

    keys.forEach(pageKey => {
      const item = allCat.find(x => x.key === pageKey);
      let prevMenu = null;
      if (pageKey.startsWith('custom:')) {
        const cp = (S.store.custom_pages || []).find(p => p.id === pageKey.slice(7));
        if (cp) {
          prevMenu = cp.prev_menu || null;
          cp.status = 'published';
          delete cp.trashed_at;
          delete cp.prev_menu;
          payload.custom_pages = S.store.custom_pages;
          restoredCount++;
        }
      } else if (pageKey.startsWith('service:')) {
        const sv = (S.store.services || []).find(s => s.slug === pageKey.slice(8));
        if (sv) {
          prevMenu = sv.prev_menu || null;
          sv.status = 'published';
          delete sv.trashed_at;
          delete sv.prev_menu;
          payload.services = S.store.services;
          restoredCount++;
        }
      } else if (pageKey.startsWith('project:')) {
        const pj = (S.store.projects || []).find(p => p.slug === pageKey.slice(8));
        if (pj) {
          prevMenu = pj.prev_menu || null;
          pj.status = 'published';
          delete pj.trashed_at;
          delete pj.prev_menu;
          payload.projects = S.store.projects;
          restoredCount++;
        }
      } else {
        const cleanKey = pageKey.startsWith('legal:') ? pageKey.slice(6) : pageKey;
        S.store.page_states = S.store.page_states || {};
        prevMenu = S.store.page_states[cleanKey]?.prev_menu || null;
        S.store.page_states[cleanKey] = { status: 'published' };
        payload.page_states = S.store.page_states;
        restoredCount++;
      }

      if (item && prevMenu && (prevMenu.in_header || prevMenu.in_footer)) {
        setPageMenuPlacement(
          item.url,
          prevMenu.menu_label || item.menuLabel || item.title,
          Boolean(prevMenu.in_header),
          Boolean(prevMenu.in_footer),
          Number(prevMenu.footer_col || 0),
          pageKey
        );
      }
    });

    if (!restoredCount) return;
    payload.menus = S.store.menus;
    payload.page_menu_labels = S.store.page_menu_labels || {};
    payload.toast_message = restoredCount === 1
      ? 'Sayfa çöp kutusundan geri yüklendi ve yayına alındı.'
      : `${restoredCount} sayfa çöp kutusundan geri yüklendi.`;

    try {
      const res = await api('unified_pages_save', payload);
      S.store = res.store;
      S.pages.selectedKeys = S.pages.selectedKeys.filter(k => !keys.includes(k));
      if (S.pages.filter === 'trash' && !buildUnifiedPagesCatalog({ includeTrash: true }).some(p => p.status === 'trash')) {
        S.pages.filter = 'all';
      }
      updateSidebarBadges();
      toast(res.message);
      renderPagesView();
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function permanentlyDeleteUnifiedPages(pageKeys, skipConfirm) {
    const keys = (Array.isArray(pageKeys) ? pageKeys : [pageKeys]).filter(k => k && k !== 'anasayfa');
    if (!keys.length) return;
    const msg = keys.length === 1
      ? 'Bu sayfayı çöp kutusundan KALICI OLARAK silmek istediğinize emin misiniz? Bu işlem geri alınamaz.'
      : `Seçilen ${keys.length} sayfayı çöp kutusundan KALICI OLARAK silmek istediğinize emin misiniz?`;
    if (!skipConfirm && !confirm(msg)) return;

    const payload = {};
    const keySet = new Set(keys);
    let purgedCount = 0;

    if (keys.some(k => k.startsWith('custom:'))) {
      const before = (S.store.custom_pages || []).length;
      S.store.custom_pages = (S.store.custom_pages || []).filter(cp => !keySet.has('custom:' + cp.id));
      purgedCount += (before - S.store.custom_pages.length);
      payload.custom_pages = S.store.custom_pages;
    }
    if (keys.some(k => k.startsWith('service:'))) {
      const before = (S.store.services || []).length;
      S.store.services = (S.store.services || []).filter(sv => !keySet.has('service:' + sv.slug));
      purgedCount += (before - S.store.services.length);
      payload.services = S.store.services;
    }
    if (keys.some(k => k.startsWith('project:'))) {
      const before = (S.store.projects || []).length;
      S.store.projects = (S.store.projects || []).filter(pj => !keySet.has('project:' + pj.slug));
      purgedCount += (before - S.store.projects.length);
      payload.projects = S.store.projects;
    }
    keys.forEach(k => {
      if (!k.startsWith('custom:') && !k.startsWith('service:') && !k.startsWith('project:')) {
        const cleanKey = k.startsWith('legal:') ? k.slice(6) : k;
        S.store.page_states = S.store.page_states || {};
        S.store.page_states[cleanKey] = { status: 'deleted' };
        payload.page_states = S.store.page_states;
        purgedCount++;
      }
    });

    if (!purgedCount) return;
    payload.toast_message = purgedCount === 1
      ? 'Sayfa kalıcı olarak silindi.'
      : `${purgedCount} sayfa kalıcı olarak silindi.`;

    try {
      const res = await api('unified_pages_save', payload);
      S.store = res.store;
      S.pages.selectedKeys = S.pages.selectedKeys.filter(k => !keySet.has(k));
      if (S.pages.filter === 'trash' && !buildUnifiedPagesCatalog({ includeTrash: true }).some(p => p.status === 'trash')) {
        S.pages.filter = 'all';
      }
      updateSidebarBadges();
      toast(res.message);
      renderPagesView();
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function setUnifiedPagesStatus(pageKeys, targetStatus) {
    const keys = (Array.isArray(pageKeys) ? pageKeys : [pageKeys]).filter(Boolean);
    const payload = {};
    let changed = 0;

    keys.forEach(pageKey => {
      if (pageKey === 'anasayfa' && targetStatus !== 'published') return;
      if (pageKey.startsWith('custom:')) {
        const cp = (S.store.custom_pages || []).find(p => p.id === pageKey.slice(7));
        if (cp) { cp.status = targetStatus; payload.custom_pages = S.store.custom_pages; changed++; }
      } else if (pageKey.startsWith('service:')) {
        const sv = (S.store.services || []).find(s => s.slug === pageKey.slice(8));
        if (sv) { sv.status = targetStatus; payload.services = S.store.services; changed++; }
      } else if (pageKey.startsWith('project:')) {
        const pj = (S.store.projects || []).find(p => p.slug === pageKey.slice(8));
        if (pj) { pj.status = targetStatus; payload.projects = S.store.projects; changed++; }
      } else if (pageKey !== 'anasayfa') {
        const cleanKey = pageKey.startsWith('legal:') ? pageKey.slice(6) : pageKey;
        S.store.page_states = S.store.page_states || {};
        S.store.page_states[cleanKey] = { status: targetStatus };
        payload.page_states = S.store.page_states;
        changed++;
      }
    });

    if (!changed) return;
    payload.toast_message = targetStatus === 'published'
      ? `Seçilen ${changed} sayfa yayına alındı.`
      : `Seçilen ${changed} sayfa taslağa alındı.`;
    try {
      const res = await api('unified_pages_save', payload);
      S.store = res.store;
      S.pages.selectedKeys = [];
      updateSidebarBadges();
      toast(res.message);
      renderPagesView();
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function deleteUnifiedPage(pageKey) {
    return trashUnifiedPages([pageKey]);
  }

  function renderPagesView() {
    syncAdminUrl('replace');
    if (S.pages.mode === 'edit') {
      return renderUnifiedSinglePageEditor();
    }
    return renderUnifiedPagesList();
  }

  function renderUnifiedPagesList() {
    const allPages = buildUnifiedPagesCatalog({ includeTrash: true });
    const activePages = allPages.filter(p => p.status !== 'trash');
    const trashedPages = allPages.filter(p => p.status === 'trash');
    const q = (S.pages.search || '').trim().toLowerCase();
    const flt = S.pages.filter || 'all';

    const counts = {
      all: activePages.length,
      published: activePages.filter(p => p.status !== 'draft').length,
      draft: activePages.filter(p => p.status === 'draft').length,
      root: activePages.filter(p => p.group === 'root').length,
      services: activePages.filter(p => p.group === 'services').length,
      projects: activePages.filter(p => p.group === 'projects').length,
      legal: activePages.filter(p => p.group === 'legal').length,
      custom: activePages.filter(p => p.group === 'custom').length,
      trash: trashedPages.length
    };

    const sourceList = flt === 'trash' ? trashedPages : activePages;
    const filtered = sourceList.filter(pg => {
      if (flt === 'published' && pg.status === 'draft') return false;
      if (flt === 'draft' && pg.status !== 'draft') return false;
      if (['root', 'services', 'projects', 'legal', 'custom'].includes(flt) && pg.group !== flt) return false;
      if (q) {
        const hay = `${pg.title} ${pg.url} ${pg.typeLabel} ${pg.stateTag} ${pg.metaTitle} ${pg.metaDesc}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });

    const selectedSet = new Set(S.pages.selectedKeys || []);
    const allFilteredSelected = filtered.length > 0 && filtered.every(p => selectedSet.has(p.key));
    const ftrCols = S.store?.menus?.footer?.columns || [];

    ROOT.innerHTML = `
      <div class="zs-card" style="padding:20px 22px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:14px">
          <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <h2 style="margin:0;font-size:21px">Sayfalar</h2>
              <button type="button" class="zs-btn zs-btn-primary zs-btn-sm" id="pgAddCustomBtn">+ Yeni Sayfa Ekle</button>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgAddServiceSubBtn">+ Yeni Hizmet Alt Sayfası</button>
              <button type="button" class="zs-btn zs-btn-sm" id="pgAddProjectSubBtn">+ Yeni Proje Alt Sayfası</button>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgOpenSolutionGenBtn">🌍 Sektörel &amp; Bölgesel SEO Sayfası Üret</button>
            </div>
            <p class="zs-card-sub" style="margin-top:6px">Sitedeki tüm ana sayfalar, hizmet &amp; proje alt sayfaları, iletişim, yasal politikalar ve özel sayfalar tek merkezden yönetilir. Silinen sayfalar güvenli şekilde Çöp Kutusu'na taşınır.</p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgJumpHomeLayoutBtn">⇅ Ana Sayfa Bölümlerini Sırala</button>
            <button type="button" class="zs-btn zs-btn-sm" id="pgJumpMenusBtn">☰ Header / Footer Menüler &amp; Yazı Boyutu</button>
          </div>
        </div>
        ${S.pages.showSolutionGen ? `<div id="pgSolutionGenHost" style="margin-bottom:16px"></div>` : ''}

        <!-- SUBNAV FILTERS -->
        <div class="zs-pages-subnav" style="margin-bottom:14px">
          <button type="button" class="${flt === 'all' ? 'active' : ''}" data-pg-flt="all">Tümü <span class="cnt">(${counts.all})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'published' ? 'active' : ''}" data-pg-flt="published">Yayında <span class="cnt">(${counts.published})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'draft' ? 'active' : ''}" data-pg-flt="draft">Taslaklar <span class="cnt">(${counts.draft})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'root' ? 'active' : ''}" data-pg-flt="root">Ana Sayfalar <span class="cnt">(${counts.root})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'services' ? 'active' : ''}" data-pg-flt="services">Hizmet Alt Sayfaları <span class="cnt">(${counts.services})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'projects' ? 'active' : ''}" data-pg-flt="projects">Proje Alt Sayfaları <span class="cnt">(${counts.projects})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'legal' ? 'active' : ''}" data-pg-flt="legal">Yasal Politikalar <span class="cnt">(${counts.legal})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'custom' ? 'active' : ''}" data-pg-flt="custom">Özel Sayfalar <span class="cnt">(${counts.custom})</span></button>
          <span class="sep">|</span>
          <button type="button" class="${flt === 'trash' ? 'active' : ''}" data-pg-flt="trash" style="${counts.trash > 0 ? 'color:#f87171;font-weight:700' : ''}">🗑 Çöp Kutusu <span class="cnt">(${counts.trash})</span></button>
        </div>

        <!-- BULK ACTIONS + SEARCH -->
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <select id="pgBulkActionSel" class="zs-select" style="width:245px;padding:7px 11px">
              <option value="">Toplu İşlemler (${selectedSet.size} seçili)</option>
              ${flt === 'trash' ? `
                <option value="restore">↩ Seçilenleri Geri Yükle</option>
                <option value="duplicate">⧉ Seçilenleri Çoğalt</option>
                <option value="purge">✕ Seçilenleri Kalıcı Olarak Sil</option>
              ` : `
                <option value="duplicate">⧉ Seçilenleri Çoğalt</option>
                <option value="add_header">Üst Menüye (Header) Ekle</option>
                <option value="add_footer">Alt Menüye (Footer) Ekle</option>
                <option value="publish">Yayına Al</option>
                <option value="draft">Taslağa Al</option>
                <option value="trash">🗑 Seçilenleri Sil (Çöp Kutusu)</option>
              `}
            </select>
            <button type="button" class="zs-btn zs-btn-sm" id="pgApplyBulkBtn">Uygula</button>
            ${flt === 'trash' && counts.trash > 0 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="pgEmptyTrashBtn">🗑 Çöp Kutusunu Boşalt (${counts.trash})</button>` : ''}
            ${selectedSet.size > 0 ? `<button type="button" class="zs-btn zs-btn-sm" id="pgClearSelBtn">Seçimi Temizle</button>` : ''}
          </div>
          <div style="display:flex;gap:8px;align-items:center;min-width:260px;flex:1;max-width:380px">
            <input id="pgSearchInput" class="zs-input" style="padding:7px 12px" placeholder="Sayfa başlığı veya /url/ ara..." value="${esc(S.pages.search)}"/>
            ${S.pages.search ? `<button type="button" class="zs-btn zs-btn-sm" id="pgClearSearchBtn">✕</button>` : ''}
          </div>
        </div>

        <!-- HIERARCHICAL PAGES TABLE -->
        <div class="zs-table-wrap">
          <table class="zs-table zs-pages-table">
            <thead>
              <tr>
                <th style="width:36px"><input type="checkbox" id="pgCheckAll" ${allFilteredSelected ? 'checked' : ''}/></th>
                <th>Başlık</th>
                <th>Kalıcı Bağlantı (URL)</th>
                <th>Sayfa Türü</th>
                <th>Menü Konumu</th>
                <th>Bloklar &amp; SEO</th>
                <th>Durum</th>
              </tr>
            </thead>
            <tbody>
              ${filtered.length === 0 ? `
                <tr><td colspan="7" style="padding:36px;text-align:center;color:var(--text-muted)">${flt === 'trash' ? 'Çöp kutusu şu an boş.' : 'Aramanızla eşleşen sayfa bulunamadı.'}</td></tr>
              ` : filtered.map(pg => {
                const isChecked = selectedSet.has(pg.key);
                const isQuickOpen = pg.status !== 'trash' && S.pages.quickEditKey === pg.key;
                const slugVal = pg.slug || '';
                const canEditSlug = pg.key !== 'anasayfa';
                const qeSlugPrefix = pg.key.startsWith('service:')
                  ? ('/' + getResolvedPageSlug('hizmetler') + '/')
                  : (pg.key.startsWith('project:') ? ('/' + getResolvedPageSlug('projeler') + '/') : '/');
                const statusBadge = pg.status === 'trash'
                  ? `<span class="zs-pill" style="background:rgba(248,113,113,.15);color:#fca5a5;border:1px solid rgba(248,113,113,.32)">Çöp Kutusu</span>`
                  : `<span class="zs-pill ${pg.status === 'draft' ? 'zs-pill-draft' : 'zs-pill-pub'}">${pg.status === 'draft' ? 'Taslak' : 'Yayında'}</span>`;
                return `
                  <tr class="${isChecked ? 'row-selected' : ''}">
                    <td><input type="checkbox" data-pg-chk="${esc(pg.key)}" ${isChecked ? 'checked' : ''}/></td>
                    <td>
                      <div style="${pg.isChild ? 'padding-left:18px' : ''}">
                        <button type="button" class="zs-page-title-link" data-pg-edit="${esc(pg.key)}">
                          ${pg.isChild ? '<span style="color:var(--accent-bright);margin-right:4px">—</span>' : ''}${esc(pg.title)}
                        </button>
                        ${pg.stateTag ? `<span class="zs-page-state-tag">— ${esc(pg.stateTag)}</span>` : ''}
                        ${pg.status === 'trash' ? `
                          <div class="zs-row-actions" style="opacity:1">
                            <button type="button" style="color:#34d399;font-weight:700" data-pg-restore="${esc(pg.key)}">↩ Geri Yükle</button>
                            <span class="act-sep">|</span>
                            <button type="button" style="color:#fbbf24;font-weight:700" data-pg-dup="${esc(pg.key)}">Çoğalt</button>
                            <span class="act-sep">|</span>
                            <button type="button" data-pg-edit="${esc(pg.key)}">İncele / Düzenle</button>
                            <span class="act-sep">|</span>
                            <button type="button" class="act-danger" data-pg-purge="${esc(pg.key)}">Kalıcı Olarak Sil</button>
                          </div>
                        ` : `
                          <div class="zs-row-actions">
                            <button type="button" data-pg-edit="${esc(pg.key)}">Düzenle</button>
                            ${pg.key === 'anasayfa' ? `<span class="act-sep">|</span><button type="button" style="color:#34d399;font-weight:700" data-pg-homelayout="1">⇅ Bölümleri Sırala</button>` : ''}
                            <span class="act-sep">|</span>
                            <button type="button" data-pg-qedit="${esc(pg.key)}">Hızlı Düzenle</button>
                            <span class="act-sep">|</span>
                            <button type="button" style="color:#5eead4;font-weight:700" data-pg-share="${esc(pg.key)}">📣 Sosyal Paylaş</button>
                            <span class="act-sep">|</span>
                            <button type="button" data-pg-builder="${esc(pg.key)}">Görsel Tasarımcı</button>
                            <span class="act-sep">|</span>
                            <a href="${esc(resolveUrl(pg.url))}" target="_blank" rel="noopener">Görüntüle ↗</a>
                            ${pg.duplicatable ? `<span class="act-sep">|</span><button type="button" style="color:#fbbf24;font-weight:700" data-pg-dup="${esc(pg.key)}">Çoğalt</button>` : ''}
                            ${pg.deletable ? `<span class="act-sep">|</span><button type="button" class="act-danger" data-pg-del="${esc(pg.key)}">Sil</button>` : ''}
                          </div>
                        `}
                      </div>
                    </td>
                    <td>
                      ${pg.status === 'trash'
                        ? `<span style="font-family:Consolas,monospace;font-size:12px;color:var(--text-dim);text-decoration:line-through">${esc(pg.url)}</span>`
                        : `<a href="${esc(resolveUrl(pg.url))}" target="_blank" rel="noopener" style="font-family:Consolas,monospace;font-size:12px;color:var(--accent-bright)">${esc(pg.url)}</a>`
                      }
                    </td>
                    <td>
                      <span class="zs-pill zs-pill-cat">${esc(pg.typeLabel)}</span>
                    </td>
                    <td>
                      <div style="display:flex;gap:5px;flex-wrap:wrap">
                        ${pg.inHeaderMenu ? `<span class="zs-pill zs-pill-pub" title="Üst Header Menüsünde Yayında">Header</span>` : ''}
                        ${pg.inFooterMenu ? `<span class="zs-pill zs-pill-cat" title="Alt Footer Menüsünde Yayında">Footer</span>` : ''}
                        ${!pg.inHeaderMenu && !pg.inFooterMenu ? `<span style="font-size:11.5px;color:var(--text-dim)">Menü dışı</span>` : ''}
                      </div>
                    </td>
                    <td>
                      <div style="font-size:12px;color:var(--text-muted)">
                        ${pg.blockCount > 0 ? `<strong style="color:#5eead4">${pg.blockCount} görsel blok</strong> · ` : ''}
                        <span title="${esc(pg.metaTitle)}">${esc((pg.metaTitle || '').slice(0, 34))}${(pg.metaTitle || '').length > 34 ? '…' : ''}</span>
                      </div>
                    </td>
                    <td>
                      ${statusBadge}
                    </td>
                  </tr>
                  ${isQuickOpen ? `
                    <tr>
                      <td colspan="7" class="zs-quick-edit-cell">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                          <strong style="color:var(--accent-bright);font-size:13.5px">⚡ Hızlı Düzenle — ${esc(pg.title)} (${esc(pg.url)})</strong>
                          <span style="font-size:11.5px;color:var(--text-muted)">Sayfadan ayrılmadan temel bilgileri, SEO ve menü konumunu güncelleyin</span>
                        </div>
                        <div class="zs-grid-3">
                          <div class="zs-field" style="margin-bottom:10px">
                            <label>Sayfa Başlığı *</label>
                            <input id="qeTitle" class="zs-input" value="${esc(pg.title)}"/>
                          </div>
                          <div class="zs-field" style="margin-bottom:10px">
                            <label>Menüde Görünecek Başlık</label>
                            <input id="qeMenuLabel" class="zs-input" value="${esc(pg.menuLabel || pg.title)}"/>
                          </div>
                          <div class="zs-field" style="margin-bottom:10px">
                            <label>${canEditSlug ? `Kalıcı Bağlantı (${esc(qeSlugPrefix)}slug/)` : 'Kalıcı Bağlantı (Kök Dizin)'}</label>
                            ${canEditSlug ? `
                              <div class="zs-input-group">
                                <span style="display:inline-flex;align-items:center;padding:0 9px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">${esc(qeSlugPrefix)}</span>
                                <input id="qeSlug" class="zs-input" value="${esc(slugVal)}"/>
                                <span style="display:inline-flex;align-items:center;padding:0 9px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">/</span>
                              </div>
                            ` : `
                              <input id="qeSlug" class="zs-input" value="/" disabled style="opacity:.65" title="Ana sayfa kök dizin (/) olarak sabittir"/>
                            `}
                          </div>
                        </div>
                        <div class="zs-field" style="margin-bottom:10px">
                          <label>Yayın Durumu &amp; Menü Görünürlüğü</label>
                          <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding-top:2px">
                            ${pg.key !== 'anasayfa' ? `
                              <select id="qeStatus" class="zs-select" style="max-width:135px;padding:6px 10px">
                                <option value="published" ${pg.status !== 'draft' ? 'selected' : ''}>Yayında</option>
                                <option value="draft" ${pg.status === 'draft' ? 'selected' : ''}>Taslak</option>
                              </select>
                            ` : ''}
                            <label class="zs-switch"><input type="checkbox" id="qeInHeader" ${pg.inHeaderMenu ? 'checked' : ''}/><span>Üst Menüde (Header) Göster</span></label>
                            <label class="zs-switch"><input type="checkbox" id="qeInFooter" ${pg.inFooterMenu ? 'checked' : ''}/><span>Alt Menüde (Footer) Göster</span></label>
                          </div>
                        </div>
                        <div class="zs-grid-2">
                          <div class="zs-field" style="margin-bottom:10px">
                            <label>SEO Meta Başlık (Google Başlığı)</label>
                            <input id="qeMetaTitle" class="zs-input" value="${esc(pg.metaTitle)}"/>
                          </div>
                          <div class="zs-field" style="margin-bottom:10px">
                            <label>SEO Meta Açıklama</label>
                            <input id="qeMetaDesc" class="zs-input" value="${esc(pg.metaDesc)}"/>
                          </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                          <div style="display:flex;gap:8px;align-items:center">
                            ${ftrCols.length > 0 ? `
                              <span style="font-size:12px;color:var(--text-muted)">Footer Sütunu:</span>
                              <select id="qeFooterCol" class="zs-select" style="width:190px;padding:5px 9px;font-size:12px">
                                ${ftrCols.map((fc, fcIdx) => `<option value="${fcIdx}" ${pg.footerColIdx === fcIdx ? 'selected' : ''}>${esc(fc.title || ('Grup ' + (fcIdx + 1)))}</option>`).join('')}
                              </select>
                            ` : ''}
                          </div>
                          <div style="display:flex;gap:8px">
                            <button type="button" class="zs-btn zs-btn-sm" id="qeCancelBtn">Vazgeç</button>
                            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-pg-edit="${esc(pg.key)}">Tam Editörü Aç →</button>
                            <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="qeSaveBtn" data-qe-key="${esc(pg.key)}">✓ Hızlı Güncelle</button>
                          </div>
                        </div>
                      </td>
                    </tr>
                  ` : ''}
                `;
              }).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;

    // Bind Top Actions
    document.getElementById('pgAddCustomBtn').onclick = () => startNewUnifiedCustomPage();
    document.getElementById('pgAddServiceSubBtn').onclick = () => createNewUnifiedServiceSubpage();
    document.getElementById('pgAddProjectSubBtn').onclick = () => createNewUnifiedProjectSubpage();
    const openSolGenBtn = document.getElementById('pgOpenSolutionGenBtn');
    if (openSolGenBtn) {
      openSolGenBtn.onclick = () => {
        S.pages.showSolutionGen = !S.pages.showSolutionGen;
        renderPagesView();
      };
    }
    if (S.pages.showSolutionGen) {
      mountProgrammaticSolutionsPanel(document.getElementById('pgSolutionGenHost'), { compact: true });
    }
    document.getElementById('pgJumpHomeLayoutBtn').onclick = () => navigateTo('home_layout');
    document.getElementById('pgJumpMenusBtn').onclick = () => navigateTo('menus');

    // Filters & Search
    ROOT.querySelectorAll('[data-pg-flt]').forEach(btn => {
      btn.onclick = () => {
        S.pages.filter = btn.getAttribute('data-pg-flt');
        S.pages.quickEditKey = null;
        S.pages.selectedKeys = [];
        renderPagesView();
      };
    });

    const searchInp = document.getElementById('pgSearchInput');
    searchInp.oninput = e => {
      S.pages.search = e.target.value;
      renderPagesView();
      const el = document.getElementById('pgSearchInput');
      if (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); }
    };
    const clearSearchBtn = document.getElementById('pgClearSearchBtn');
    if (clearSearchBtn) {
      clearSearchBtn.onclick = () => { S.pages.search = ''; renderPagesView(); };
    }

    // Checkboxes & Bulk Actions
    const chkAll = document.getElementById('pgCheckAll');
    if (chkAll) {
      chkAll.onchange = () => {
        if (chkAll.checked) {
          const merged = new Set(S.pages.selectedKeys);
          filtered.forEach(p => merged.add(p.key));
          S.pages.selectedKeys = Array.from(merged);
        } else {
          const removeSet = new Set(filtered.map(p => p.key));
          S.pages.selectedKeys = S.pages.selectedKeys.filter(k => !removeSet.has(k));
        }
        renderPagesView();
      };
    }

    ROOT.querySelectorAll('[data-pg-chk]').forEach(chk => {
      chk.onchange = () => {
        const k = chk.getAttribute('data-pg-chk');
        if (chk.checked) {
          if (!S.pages.selectedKeys.includes(k)) S.pages.selectedKeys.push(k);
        } else {
          S.pages.selectedKeys = S.pages.selectedKeys.filter(x => x !== k);
        }
        renderPagesView();
      };
    });

    const clearSelBtn = document.getElementById('pgClearSelBtn');
    if (clearSelBtn) clearSelBtn.onclick = () => { S.pages.selectedKeys = []; renderPagesView(); };

    document.getElementById('pgOpenSolutionGenBtn')?.addEventListener('click', () => {
      S.pages.showSolutionGen = !S.pages.showSolutionGen;
      renderPagesView();
    });
    if (S.pages.showSolutionGen && document.getElementById('pgSolutionGenHost')) {
      mountProgrammaticSolutionsPanel(document.getElementById('pgSolutionGenHost'), { compact: true });
    }

    const emptyTrashBtn = document.getElementById('pgEmptyTrashBtn');
    if (emptyTrashBtn) {
      emptyTrashBtn.onclick = () => permanentlyDeleteUnifiedPages(trashedPages.map(p => p.key), false);
    }

    document.getElementById('pgApplyBulkBtn').onclick = async () => {
      const act = document.getElementById('pgBulkActionSel').value;
      if (!act) { toast('Lütfen uygulanacak toplu işlemi seçin.', true); return; }
      if (!S.pages.selectedKeys.length) { toast('Lütfen tablodan en az bir sayfa seçin.', true); return; }

      const selItems = allPages.filter(p => S.pages.selectedKeys.includes(p.key));
      if (!selItems.length) { toast('Seçili sayfa bulunamadı.', true); return; }

      if (act === 'duplicate') {
        return duplicateUnifiedPages(selItems.map(p => p.key));
      }
      if (act === 'trash' || act === 'delete') {
        const trashableKeys = selItems.filter(p => p.deletable && p.key !== 'anasayfa').map(p => p.key);
        if (!trashableKeys.length) {
          toast('Ana sayfa (/) kök dizin olduğu için çöp kutusuna taşınamaz.', true);
          return;
        }
        return trashUnifiedPages(trashableKeys);
      }
      if (act === 'restore') {
        return restoreUnifiedPages(selItems.map(p => p.key));
      }
      if (act === 'purge') {
        return permanentlyDeleteUnifiedPages(selItems.map(p => p.key), false);
      }
      if (act === 'publish' || act === 'draft') {
        return setUnifiedPagesStatus(selItems.map(p => p.key), act === 'publish' ? 'published' : 'draft');
      }
      if (act === 'add_header' || act === 'add_footer') {
        selItems.forEach(p => {
          setPageMenuPlacement(
            p.url,
            p.menuLabel || p.title,
            act === 'add_header' ? true : p.inHeaderMenu,
            act === 'add_footer' ? true : p.inFooterMenu,
            p.footerColIdx || 0,
            p.key
          );
        });
        try {
          const res = await api('unified_pages_save', {
            menus: S.store.menus,
            page_menu_labels: S.store.page_menu_labels || {},
            toast_message: `Seçilen ${selItems.length} sayfa menüye eklendi.`
          });
          S.store = res.store;
          S.pages.selectedKeys = [];
          toast(res.message);
          renderPagesView();
        } catch (err) {
          toast(err.message, true);
        }
      }
    };

    // Row Actions
    ROOT.querySelectorAll('[data-pg-homelayout]').forEach(btn => {
      btn.onclick = () => navigateTo('home_layout');
    });
    ROOT.querySelectorAll('[data-pg-edit]').forEach(btn => {
      btn.onclick = () => openUnifiedPageEditor(btn.getAttribute('data-pg-edit'), 'content');
    });
    ROOT.querySelectorAll('[data-pg-share]').forEach(btn => {
      btn.onclick = () => openSocialShareStudio({
        kind: 'page',
        targetKey: btn.getAttribute('data-pg-share'),
        onSaved: () => renderPagesView()
      });
    });
    ROOT.querySelectorAll('[data-pg-builder]').forEach(btn => {
      btn.onclick = () => openUnifiedPageEditor(btn.getAttribute('data-pg-builder'), 'builder');
    });
    ROOT.querySelectorAll('[data-pg-dup]').forEach(btn => {
      btn.onclick = () => duplicateUnifiedPage(btn.getAttribute('data-pg-dup'));
    });
    ROOT.querySelectorAll('[data-pg-del]').forEach(btn => {
      btn.onclick = () => deleteUnifiedPage(btn.getAttribute('data-pg-del'));
    });
    ROOT.querySelectorAll('[data-pg-restore]').forEach(btn => {
      btn.onclick = () => restoreUnifiedPages([btn.getAttribute('data-pg-restore')]);
    });
    ROOT.querySelectorAll('[data-pg-purge]').forEach(btn => {
      btn.onclick = () => permanentlyDeleteUnifiedPages([btn.getAttribute('data-pg-purge')], false);
    });
    ROOT.querySelectorAll('[data-pg-qedit]').forEach(btn => {
      btn.onclick = () => {
        const k = btn.getAttribute('data-pg-qedit');
        S.pages.quickEditKey = (S.pages.quickEditKey === k) ? null : k;
        renderPagesView();
      };
    });

    const qeCancel = document.getElementById('qeCancelBtn');
    if (qeCancel) qeCancel.onclick = () => { S.pages.quickEditKey = null; renderPagesView(); };

    const qeTitleInput = document.getElementById('qeTitle');
    const qeMenuInput = document.getElementById('qeMenuLabel');
    if (qeTitleInput && qeMenuInput) {
      let prevQeTitle = qeTitleInput.value;
      qeTitleInput.oninput = () => {
        if (qeMenuInput.value === prevQeTitle) {
          qeMenuInput.value = qeTitleInput.value;
        }
        prevQeTitle = qeTitleInput.value;
      };
    }

    const qeSave = document.getElementById('qeSaveBtn');
    if (qeSave) {
      qeSave.onclick = async () => {
        const k = qeSave.getAttribute('data-qe-key');
        const pg = allPages.find(x => x.key === k);
        if (!pg) return;

        const newTitle = document.getElementById('qeTitle').value.trim() || pg.title;
        const menuTitle = document.getElementById('qeMenuLabel')?.value.trim() || newTitle;
        const rawSlug = document.getElementById('qeSlug').value.trim();
        const metaTitle = document.getElementById('qeMetaTitle').value.trim();
        const metaDesc = document.getElementById('qeMetaDesc').value.trim();
        const inHdr = document.getElementById('qeInHeader').checked;
        const inFtr = document.getElementById('qeInFooter').checked;
        const ftrCol = Number(document.getElementById('qeFooterCol')?.value || 0);
        const nextStatus = (k !== 'anasayfa' && document.getElementById('qeStatus'))
          ? document.getElementById('qeStatus').value
          : 'published';

        let finalUrl = pg.url;
        let finalKey = k;
        const urlRenames = [];
        const payload = { toast_message: `"${newTitle}" hızlı düzenleme ile güncellendi.` };

        if (k.startsWith('custom:')) {
          const cp = (S.store.custom_pages || []).find(x => x.id === k.slice(7));
          if (cp) {
            setPageMenuPlacement(pg.url, pg.title, false, false, 0);
            cp.title = newTitle;
            cp.slug = slugifyClient(rawSlug || newTitle);
            cp.status = nextStatus;
            cp.seo = Object.assign(cp.seo || {}, { meta_title: metaTitle, meta_desc: metaDesc });
            finalUrl = '/' + cp.slug + '/';
            if (finalUrl !== pg.url) urlRenames.push({ from: pg.url, to: finalUrl });
            payload.custom_pages = S.store.custom_pages;
          }
        } else if (k.startsWith('service:')) {
          const sv = (S.store.services || []).find(x => x.slug === k.slice(8));
          if (sv) {
            setPageMenuPlacement(pg.url, pg.title, false, false, 0);
            sv.title = newTitle;
            sv.slug = slugifyClient(rawSlug || newTitle);
            sv.status = nextStatus;
            sv.seo = Object.assign(sv.seo || {}, { meta_title: metaTitle, meta_desc: metaDesc });
            finalUrl = '/' + getResolvedPageSlug('hizmetler') + '/' + sv.slug + '/';
            if (finalUrl !== pg.url) urlRenames.push({ from: pg.url, to: finalUrl });
            finalKey = 'service:' + sv.slug;
            payload.services = S.store.services;
          }
        } else if (k.startsWith('project:')) {
          const pj = (S.store.projects || []).find(x => x.slug === k.slice(8));
          if (pj) {
            setPageMenuPlacement(pg.url, pg.title, false, false, 0);
            pj.name = newTitle;
            pj.slug = slugifyClient(rawSlug || newTitle);
            pj.status = nextStatus;
            pj.seo = Object.assign(pj.seo || {}, { meta_title: metaTitle, meta_desc: metaDesc });
            finalUrl = '/' + getResolvedPageSlug('projeler') + '/' + pj.slug + '/';
            if (finalUrl !== pg.url) urlRenames.push({ from: pg.url, to: finalUrl });
            finalKey = 'project:' + pj.slug;
            payload.projects = S.store.projects;
          }
        } else if (k.startsWith('legal:')) {
          const lk = k.slice(6);
          const nextSlug = slugifyClient(rawSlug || lk) || lk;
          S.store.page_slugs = S.store.page_slugs || {};
          S.store.page_slugs[lk] = nextSlug;
          S.store.page_states = S.store.page_states || {};
          S.store.page_states[lk] = Object.assign(S.store.page_states[lk] || {}, { status: nextStatus });
          S.store.legal_pages = S.store.legal_pages || {};
          S.store.legal_pages[lk] = Object.assign(S.store.legal_pages[lk] || { slug: nextSlug, sections: [] }, {
            slug: nextSlug,
            title: newTitle,
            meta_title: metaTitle,
            meta_desc: metaDesc
          });
          finalUrl = '/' + nextSlug + '/';
          if (finalUrl !== pg.url) {
            setPageMenuPlacement(pg.url, pg.title, false, false, 0);
            urlRenames.push({ from: pg.url, to: finalUrl });
          }
          payload.page_slugs = S.store.page_slugs;
          payload.page_states = S.store.page_states;
          payload.legal_pages = { [lk]: S.store.legal_pages[lk] };
          payload.seo_pages = { [lk]: { meta_title: metaTitle, meta_desc: metaDesc } };
        } else {
          S.store.page_titles = S.store.page_titles || {};
          S.store.page_titles[k] = newTitle;
          payload.page_titles = S.store.page_titles;
          if (k !== 'anasayfa') {
            S.store.page_states = S.store.page_states || {};
            S.store.page_states[k] = Object.assign(S.store.page_states[k] || {}, { status: nextStatus });
            payload.page_states = S.store.page_states;
          }
          if (k !== 'anasayfa' && DEFAULT_PAGE_SLUGS[k]) {
            const nextSlug = slugifyClient(rawSlug || DEFAULT_PAGE_SLUGS[k]) || DEFAULT_PAGE_SLUGS[k];
            S.store.page_slugs = S.store.page_slugs || {};
            S.store.page_slugs[k] = nextSlug;
            finalUrl = '/' + nextSlug + '/';
            if (finalUrl !== pg.url) {
              setPageMenuPlacement(pg.url, pg.title, false, false, 0);
              urlRenames.push({ from: pg.url, to: finalUrl });
            }
            payload.page_slugs = S.store.page_slugs;
          }
          if (k === 'blog') {
            S.store.blog_settings = S.store.blog_settings || {};
            S.store.blog_settings.heading = newTitle;
            payload.blog_settings = S.store.blog_settings;
          }
          payload.seo_pages = { [k]: { meta_title: metaTitle, meta_desc: metaDesc } };
        }
        if (urlRenames.length) payload.url_renames = urlRenames;

        setPageMenuPlacement(finalUrl, menuTitle, inHdr, inFtr, ftrCol, finalKey);
        payload.menus = S.store.menus;
        payload.page_menu_labels = S.store.page_menu_labels || {};

        try {
          const res = await api('unified_pages_save', payload);
          S.store = res.store;
          S.pages.quickEditKey = null;
          updateSidebarBadges();
          toast(res.message);
          renderPagesView();
        } catch (err) {
          toast(err.message, true);
        }
      };
    }
  }

  function getUnifiedPageBuilderArrayRef(key) {
    if (key === 'custom:__new__') {
      S.pages.draftCustom = S.pages.draftCustom || { title: '', slug: '', sections: [], faqs: [], seo: {} };
      if (!Array.isArray(S.pages.draftCustom.sections)) S.pages.draftCustom.sections = [];
      return S.pages.draftCustom.sections;
    }
    if (key.startsWith('custom:')) {
      const cp = (S.store.custom_pages || []).find(x => x.id === key.slice(7));
      if (!cp) return [];
      if (!Array.isArray(cp.sections)) cp.sections = [];
      return cp.sections;
    }
    if (key.startsWith('service:')) {
      const sv = (S.store.services || []).find(x => x.slug === key.slice(8));
      if (!sv) return [];
      if (!Array.isArray(sv.builder_blocks)) sv.builder_blocks = [];
      return sv.builder_blocks;
    }
    if (key.startsWith('project:')) {
      const pj = (S.store.projects || []).find(x => x.slug === key.slice(8));
      if (!pj) return [];
      if (!Array.isArray(pj.builder_blocks)) pj.builder_blocks = [];
      return pj.builder_blocks;
    }
    const coreKey = key.startsWith('legal:') ? key.slice(6) : key;
    S.store.core_page_blocks = S.store.core_page_blocks || {};
    if (!Array.isArray(S.store.core_page_blocks[coreKey])) S.store.core_page_blocks[coreKey] = [];
    return S.store.core_page_blocks[coreKey];
  }

  function getUnifiedPageSeoObject(key) {
    if (key === 'custom:__new__') {
      S.pages.draftCustom.seo = S.pages.draftCustom.seo || {};
      return S.pages.draftCustom.seo;
    }
    if (key.startsWith('custom:')) {
      const cp = (S.store.custom_pages || []).find(x => x.id === key.slice(7));
      if (!cp) return {};
      cp.seo = cp.seo || {};
      return cp.seo;
    }
    if (key.startsWith('service:')) {
      const sv = (S.store.services || []).find(x => x.slug === key.slice(8));
      if (!sv) return {};
      sv.seo = sv.seo || {};
      return sv.seo;
    }
    if (key.startsWith('project:')) {
      const pj = (S.store.projects || []).find(x => x.slug === key.slice(8));
      if (!pj) return {};
      pj.seo = pj.seo || {};
      return pj.seo;
    }
    const seoKey = key.startsWith('legal:') ? key.slice(6) : key;
    S.store.seo = S.store.seo || { pages: {} };
    S.store.seo.pages = S.store.seo.pages || {};
    if (!S.store.seo.pages[seoKey]) {
      const lpDoc = key.startsWith('legal:') ? (S.store.legal_pages?.[seoKey] || {}) : {};
      S.store.seo.pages[seoKey] = {
        meta_title: lpDoc.meta_title || '',
        meta_desc: lpDoc.meta_desc || lpDoc.lead || '',
        focus_keyword: '',
        robots: 'index, follow',
        og_image: '',
        ai_summary: ''
      };
    }
    return S.store.seo.pages[seoKey];
  }

  function renderRichEditorWidget(editorId, htmlValue) {
    const srcMode = !!S.pages.sourceMode;
    return `
      <div class="zs-field">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
          <label style="margin:0">Sayfa Gövde İçeriği (Zengin Metin & Görsel Alanı)</label>
          <button type="button" class="zs-btn zs-btn-sm ${srcMode ? 'zs-btn-ai' : ''}" id="pgToggleRichSrcBtn">${srcMode ? '👁 Görsel Editöre Dön' : '</> HTML Kaynak Kodu'}</button>
        </div>
        ${srcMode ? `
          <textarea id="${editorId}Source" class="zs-textarea" style="min-height:300px;font-family:Consolas,monospace;font-size:12.5px">${esc(htmlValue || '')}</textarea>
        ` : `
          <div class="zs-editor-toolbar" style="display:flex;gap:6px;flex-wrap:wrap;padding:8px 10px;background:rgba(6,14,18,.9);border:1px solid var(--border-subtle);border-bottom:none;border-radius:12px 12px 0 0">
            <button type="button" class="zs-btn zs-btn-sm" data-rcmd="formatBlock:H2">H2 Başlık</button>
            <button type="button" class="zs-btn zs-btn-sm" data-rcmd="formatBlock:H3">H3 Alt Başlık</button>
            <button type="button" class="zs-btn zs-btn-sm" data-rcmd="formatBlock:P">Paragraf</button>
            <button type="button" class="zs-btn zs-btn-sm" data-rcmd="bold"><strong>B</strong></button>
            <button type="button" class="zs-btn zs-btn-sm" data-rcmd="italic"><em>I</em></button>
            <button type="button" class="zs-btn zs-btn-sm" data-rcmd="insertUnorderedList">• Liste</button>
            <button type="button" class="zs-btn zs-btn-sm" data-rcmd="formatBlock:BLOCKQUOTE">❝ Alıntı</button>
            <button type="button" class="zs-btn zs-btn-sm" id="pgRichLinkBtn">🔗 Link</button>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgRichImgBtn">🖼 Görsel Ekle</button>
          </div>
          <div id="${editorId}" contenteditable="true" class="zs-rich-editable" style="min-height:280px;padding:18px;background:rgba(5,12,16,.82);border:1px solid var(--border-subtle);border-radius:0 0 12px 12px;line-height:1.75;font-size:14.5px;outline:none">${htmlValue || ''}</div>
        `}
      </div>
    `;
  }

  function renderUnifiedContentLeftHtml(key, pgInfo) {
    const renderCorePermalinkBar = (coreKey) => {
      const curSlug = pgInfo.slug || getResolvedPageSlug(coreKey);
      return `
        <div class="zs-card" style="padding:14px 16px;margin-bottom:14px;border-color:rgba(94,234,212,.24)">
          <div class="zs-grid-2">
            <div class="zs-field" style="margin-bottom:0">
              <label>Sayfa Başlığı (Liste &amp; Breadcrumb Adı) *</label>
              <input id="corePageTitleInp" class="zs-input" value="${esc(pgInfo.title || '')}"/>
            </div>
            <div class="zs-field" style="margin-bottom:0">
              <label>Kalıcı Bağlantı Uzantısı (/slug/)</label>
              <div class="zs-input-group">
                <span style="display:inline-flex;align-items:center;padding:0 10px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">/</span>
                <input id="corePageSlugInp" class="zs-input" placeholder="${esc(DEFAULT_PAGE_SLUGS[coreKey] || coreKey)}" value="${esc(curSlug)}"/>
                <span style="display:inline-flex;align-items:center;padding:0 10px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">/</span>
              </div>
            </div>
          </div>
        </div>
      `;
    };

    // 1. Custom Page (New or Existing)
    if (key === 'custom:__new__' || key.startsWith('custom:')) {
      const cp = key === 'custom:__new__'
        ? S.pages.draftCustom
        : ((S.store.custom_pages || []).find(x => x.id === key.slice(7)) || {});
      const faqs = Array.isArray(cp.faqs) ? cp.faqs : [];
      return `
        <div class="zs-card">
          <div class="zs-field">
            <label>Sayfa Başlığı (H1) *</label>
            <input id="cpTitle" class="zs-input" style="font-size:18px;font-weight:700;padding:12px 14px" placeholder="Örn: Kurumsal Kimlik Danışmanlığı" value="${esc(cp.title || '')}"/>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field">
              <label>Kalıcı Bağlantı Uzantısı (/slug/)</label>
              <div class="zs-input-group">
                <span style="display:inline-flex;align-items:center;padding:0 10px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">/</span>
                <input id="cpSlug" class="zs-input" placeholder="ornek-sayfa-adresi" value="${esc(cp.slug || '')}"/>
                <span style="display:inline-flex;align-items:center;padding:0 10px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">/</span>
              </div>
            </div>
            <div class="zs-field">
              <label>Üst Etiket (Eyebrow)</label>
              <input id="cpEyebrow" class="zs-input" placeholder="ZEYNART STÜDYO" value="${esc(cp.eyebrow || '')}"/>
            </div>
          </div>
          <div class="zs-field">
            <label>Kısa Özet / Giriş Açıklaması (Başlık altında görünür)</label>
            <textarea id="cpExcerpt" class="zs-textarea" style="min-height:68px" placeholder="Sayfanın amacını özetleyen kısa giriş cümlesi...">${esc(cp.excerpt || '')}</textarea>
          </div>
          ${renderRichEditorWidget('cpRichBody', cp.content_html || '')}
        </div>

        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Sıkça Sorulan Sorular (SSS & Otomatik Google FAQ Şeması)</h3>
              <p class="zs-card-sub">Eklediğiniz sorular sayfa altında akordeon olarak görünür ve arama motoru şemasına işlenir.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="cpAddFaqBtn">+ Soru Ekle</button>
          </div>
          ${faqs.length === 0 ? `<div style="font-size:12.5px;color:var(--text-muted)">Henüz soru eklenmedi. İhtiyaç duyarsanız "+ Soru Ekle" butonunu kullanabilirsiniz.</div>` : faqs.map((fq, idx) => `
            <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-cp-faq="${idx}">
              <div style="display:flex;gap:8px">
                <input class="zs-input cpfq-q" placeholder="Soru başlığı..." value="${esc(fq.q || '')}"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-cp-faq-del="${idx}">Sil</button>
              </div>
              <textarea class="zs-textarea cpfq-a" style="min-height:60px" placeholder="Yanıt metni...">${esc(fq.a || '')}</textarea>
            </div>
          `).join('')}
        </div>
      `;
    }

    // 2. Ana Sayfa (anasayfa)
    if (key === 'anasayfa') {
      const { order, vis } = ensureNormalizedHomeSections();
      const home = S.store.home || {};
      const hero = home.hero || { slides: [] };
      const intro = home.intro || {};
      const bs = home.brand_scene || {};
      const proc = home.process || { steps: [] };
      const sub = ['order', 'hero', 'intro', 'process'].includes(S.pages.subTab) ? S.pages.subTab : 'order';

      return `
        <div class="zs-tabs" style="margin-bottom:14px">
          <button type="button" class="zs-tab ${sub === 'order' ? 'active' : ''}" data-pg-sub="order">⇅ Bölüm Sıralaması &amp; Görünürlük (${order.filter(k => vis[k] !== false).length}/${order.length})</button>
          <button type="button" class="zs-tab ${sub === 'hero' ? 'active' : ''}" data-pg-sub="hero">🎬 Hero &amp; Slaytlar (${(hero.slides || []).length})</button>
          <button type="button" class="zs-tab ${sub === 'intro' ? 'active' : ''}" data-pg-sub="intro">🏛️ Hikâye Girişi &amp; Üretim Sahnesi</button>
          <button type="button" class="zs-tab ${sub === 'process' ? 'active' : ''}" data-pg-sub="process">🧭 Değerler / Süreç Kartları</button>
        </div>

        ${sub === 'order' ? `
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:14px">
              <div>
                <h3 style="margin:0">Ana Sayfa Bölüm Sıralaması &amp; Görünürlük</h3>
                <p class="zs-card-sub">Bölümleri <strong>⋮⋮ tutamacından sürükleyerek</strong> veya ok/sıra menüsüyle dilediğiniz yere taşıyın, ardından üstteki <strong>Sayfayı Kaydet</strong> butonuna basın.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgOpenFullHomeLayoutBtn">🖥️ Tam Ekran Canlı Akış &amp; Hazır Şablonlar →</button>
            </div>
            ${renderHomeSectionCardsHtml(order, vis)}
          </div>
        ` : ''}

        ${sub === 'hero' ? `
          <div class="zs-card">
            <h3 style="margin:0 0 12px">Hero Arka Plan Görselleri & Aksiyon Butonları</h3>
            <div class="zs-grid-2">
              <div class="zs-field"><label>1. Arka Plan Görseli (LCP)</label><div class="zs-input-group"><input id="hhBg1" class="zs-input" value="${esc(hero.bg_image_1 || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="hhBg1">Seç</button></div></div>
              <div class="zs-field"><label>2. Arka Plan Görseli</label><div class="zs-input-group"><input id="hhBg2" class="zs-input" value="${esc(hero.bg_image_2 || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="hhBg2">Seç</button></div></div>
              <div class="zs-field"><label>Birincil Buton Metni</label><input id="hhBtn1L" class="zs-input" value="${esc(hero.primary_btn_label || '')}"/></div>
              <div class="zs-field"><label>Birincil Buton Linki</label><input id="hhBtn1U" class="zs-input" value="${esc(hero.primary_btn_url || '')}"/></div>
              <div class="zs-field"><label>İkincil Buton Metni</label><input id="hhBtn2L" class="zs-input" value="${esc(hero.secondary_btn_label || '')}"/></div>
              <div class="zs-field"><label>Alt Uzmanlık Şeridi</label><input id="hhDomains" class="zs-input" value="${esc(hero.domains_bar || '')}"/></div>
            </div>
          </div>
          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Hero Slayt Metinleri</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="hhAddSlideBtn">+ Yeni Slayt Ekle</button>
            </div>
            <div class="zs-grid-2">
              ${(hero.slides || []).map((sl, idx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.78);margin-bottom:0" data-hslide="${idx}">
                  <div style="display:flex;justify-content:space-between;margin-bottom:10px">
                    <span class="zs-badge-pill">SLAYT 0${idx + 1}</span>
                    ${hero.slides.length > 1 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-hslide-del="${idx}">Sil</button>` : ''}
                  </div>
                  <div class="zs-field"><label>Üst Etiket</label><input class="zs-input hsl-lbl" value="${esc(sl.label || '')}"/></div>
                  <div class="zs-grid-2">
                    <div class="zs-field"><label>Başlık Satırları (Satır satır)</label><textarea class="zs-textarea hsl-lines" style="min-height:62px">${esc((sl.lines || []).join('\n'))}</textarea></div>
                    <div class="zs-field"><label>İtalik Vurgulu Son Kelime</label><input class="zs-input hsl-accent" value="${esc(sl.accent || '')}"/><label style="margin-top:8px">Sağ Yan Notlar</label><textarea class="zs-textarea hsl-note" style="min-height:54px">${esc((sl.note || []).join('\n'))}</textarea></div>
                  </div>
                  <div class="zs-field" style="margin-bottom:0"><label>Açıklama</label><textarea class="zs-textarea hsl-copy" style="min-height:60px">${esc(sl.copy || '')}</textarea></div>
                </div>
              `).join('')}
            </div>
          </div>
        ` : ''}

        ${sub === 'intro' ? `
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Hikâye / Giriş Bölümü</h3>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Üst Etiket</label><input id="hiEyebrow" class="zs-input" value="${esc(intro.eyebrow || '')}"/></div>
              <div class="zs-field"><label>Kuruluş Yılı</label><input id="hiYear" class="zs-input" value="${esc(intro.year || '')}"/></div>
            </div>
            <div class="zs-field"><label>Sol Alt Slogan</label><input id="hiTagline" class="zs-input" value="${esc(intro.tagline || '')}"/></div>
            <div class="zs-field"><label>Üst Cümle</label><input id="hiLead" class="zs-input" value="${esc(intro.lead || '')}"/></div>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Başlık Başlangıç</label><input id="hiBefore" class="zs-input" value="${esc(intro.heading_before || '')}"/></div>
              <div class="zs-field"><label>İtalik Vurgu</label><input id="hiEm" class="zs-input" value="${esc(intro.heading_em || '')}"/></div>
              <div class="zs-field"><label>Başlık Devamı</label><input id="hiAfter" class="zs-input" value="${esc(intro.heading_after || '')}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Alt İlkeler</label><input id="hiPillars" class="zs-input" value="${esc(intro.pillars || '')}"/></div>
              <div class="zs-field"><label>Link Metni</label><input id="hiLinkLbl" class="zs-input" value="${esc(intro.link_label || '')}"/></div>
            </div>
          </div>
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Üretim Sahne Paneli</h3>
            <div class="zs-field"><label>Arka Plan Görseli</label><div class="zs-input-group"><input id="hbsImg" class="zs-input" value="${esc(bs.image || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="hbsImg">Seç</button></div></div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Üst Etiket</label><input id="hbsEyebrow" class="zs-input" value="${esc(bs.eyebrow || '')}"/></div>
              <div class="zs-field"><label>Sağ Üst Not</label><input id="hbsTopNote" class="zs-input" value="${esc(bs.top_note || '')}"/></div>
            </div>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Başlık 1</label><input id="hbsH1" class="zs-input" value="${esc(bs.heading_line1 || '')}"/></div>
              <div class="zs-field"><label>Başlık 2</label><input id="hbsH2" class="zs-input" value="${esc(bs.heading_line2 || '')}"/></div>
              <div class="zs-field"><label>Vurgu</label><input id="hbsEm" class="zs-input" value="${esc(bs.heading_em || '')}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field" style="margin-bottom:0"><label>Açıklama Satır 1</label><input id="hbsC1" class="zs-input" value="${esc(bs.copy_line1 || '')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Açıklama Satır 2</label><input id="hbsC2" class="zs-input" value="${esc(bs.copy_line2 || '')}"/></div>
            </div>
          </div>
        ` : ''}

        ${sub === 'process' ? `
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Değerler / Süreç Kartları</h3>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Üst Etiket</label><input id="hpEyebrow" class="zs-input" value="${esc(proc.eyebrow || '')}"/></div>
              <div class="zs-field"><label>Başlık</label><input id="hpH1" class="zs-input" value="${esc(proc.heading_line1 || '')}"/></div>
              <div class="zs-field"><label>Soluk Alt Başlık</label><input id="hpMuted" class="zs-input" value="${esc(proc.heading_muted || '')}"/></div>
            </div>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Sağ Giriş Açıklaması</label><textarea id="hpIntro" class="zs-textarea" style="min-height:58px">${esc(proc.intro || '')}</textarea></div>
              <div class="zs-field"><label>Alt Şerit Sol Not</label><input id="hpFLeft" class="zs-input" value="${esc(proc.footer_left || 'Her aşamada açık iletişim.')}"/></div>
              <div class="zs-field"><label>Alt Şerit Sağ Not</label><input id="hpFRight" class="zs-input" value="${esc(proc.footer_right || 'FİKİRDEN YAYINA, BİRLİKTE.')}"/></div>
            </div>
            <div class="zs-grid-2">
              ${(proc.steps || []).map((st, idx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.78);margin-bottom:0" data-hpstep="${idx}">
                  <div class="zs-grid-2">
                    <div class="zs-field"><label>Adım No</label><input class="zs-input hps-num" value="${esc(st.num || '')}"/></div>
                    <div class="zs-field"><label>Başlık</label><input class="zs-input hps-title" value="${esc(st.title || '')}"/></div>
                  </div>
                  <div class="zs-field"><label>Açıklama</label><textarea class="zs-textarea hps-desc" style="min-height:60px">${esc(st.desc || '')}</textarea></div>
                  <div class="zs-field" style="margin-bottom:0"><label>Somut Çıktı Etiketi</label><input class="zs-input hps-out" value="${esc(st.output || '')}"/></div>
                </div>
              `).join('')}
            </div>
          </div>
        ` : ''}
      `;
    }

    // 3. Hakkımızda (hakkimizda)
    if (key === 'hakkimizda') {
      const ab = S.store.about || { clients: [], reviews: [], story_paragraphs: [], software_items: [], values: [] };
      const abVals = Array.isArray(ab.values) ? ab.values : [];
      const sub = ['story', 'clients', 'reviews'].includes(S.pages.subTab) ? S.pages.subTab : 'story';
      const cq = (S.clientSearch || '').toLowerCase();
      return `
        ${renderCorePermalinkBar('hakkimizda')}
        <div class="zs-tabs" style="margin-bottom:14px">
          <button type="button" class="zs-tab ${sub === 'story' ? 'active' : ''}" data-pg-sub="story">🏛️ Kurumsal Hikâye, İlkeler &amp; Yazılımlar</button>
          <button type="button" class="zs-tab ${sub === 'clients' ? 'active' : ''}" data-pg-sub="clients">🤝 Müşteri &amp; Referans Logoları (${(ab.clients || []).length})</button>
          <button type="button" class="zs-tab ${sub === 'reviews' ? 'active' : ''}" data-pg-sub="reviews">⭐ Müşteri Yorumları (${(ab.reviews || []).length})</button>
        </div>

        ${sub === 'story' ? `
          <div class="zs-card" style="padding:14px 18px;border-color:rgba(94,234,212,.32);background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(10,23,32,.85))">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
              <div>
                <strong style="color:#ffffff;font-size:13.5px">🤝 Hakkımızda Sayfası Müşteri &amp; Referans Logoları (${(ab.clients || []).length} Marka)</strong>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Yeni müşteri logosu yüklemek, marka adı/linki düzenlemek veya sıralamak için doğrudan logo stüdyosuna geçin.</div>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="abJumpClientsSubBtn">+ Yeni Müşteri Logosu Ekle / Yönet →</button>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">1. Üst Banner &amp; İkili İmza Şeridi</h3>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Üst Etiket</label><input id="abEyebrow" class="zs-input" value="${esc(ab.eyebrow || '')}"/></div>
              <div class="zs-field"><label>Başlık 1. Satır</label><input id="abH1" class="zs-input" value="${esc(ab.heading_line1 || '')}"/></div>
              <div class="zs-field"><label>Başlık Vurgulu 2. Satır</label><input id="abAcc" class="zs-input" value="${esc(ab.heading_accent || '')}"/></div>
            </div>
            <div class="zs-field"><label>Giriş Metni</label><textarea id="abLead" class="zs-textarea" style="min-height:68px">${esc(ab.lead || '')}</textarea></div>
            <div class="zs-grid-2">
              <div class="zs-field" style="margin-bottom:0"><label>İmza Şeridi Sol Metin</label><input id="abSigLeft" class="zs-input" value="${esc(ab.signature_left || 'BAĞIMSIZ FİKİRLER. BÜTÜNSEL ÇÖZÜMLER.')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>İmza Şeridi Sağ Metin</label><input id="abSigRight" class="zs-input" value="${esc(ab.signature_right || 'TASARIM VE TEKNOLOJİ BİR ARADA')}"/></div>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">2. Marka Sahnesi (“Fikirden Dijital Etkiye”)</h3>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Sahne Arka Plan Görseli</label><div class="zs-input-group"><input id="abBrandImg" class="zs-input" value="${esc(ab.brand_hero_image || '/images/zeynart-emblem-gallery.webp')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="abBrandImg">Seç</button></div></div>
              <div class="zs-field"><label>Üst Etiket</label><input id="abBrandEye" class="zs-input" value="${esc(ab.brand_hero_eyebrow || 'ZEYNART WEB BİLİŞİM')}"/></div>
            </div>
            <div class="zs-grid-3">
              <div class="zs-field" style="margin-bottom:0"><label>Sahne Başlık 1</label><input id="abBrandH1" class="zs-input" value="${esc(ab.brand_hero_h2_1 || 'Fikirden')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Sahne Vurgulu Başlık 2</label><input id="abBrandSpan" class="zs-input" value="${esc(ab.brand_hero_h2_span || 'dijital etkiye.')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Sahne Alt Açıklama</label><textarea id="abBrandCopy" class="zs-textarea" style="min-height:54px">${esc(ab.brand_hero_copy || 'Tasarımın karakteri.\nTeknolojinin gücü.')}</textarea></div>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">3. Kurumsal Hikâye &amp; Görsel Alanı</h3>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Hikâye Üst Etiket</label><input id="abStoryEye" class="zs-input" value="${esc(ab.story_eyebrow || 'HİKÂYEMİZ')}"/></div>
              <div class="zs-field"><label>Hikâye Başlık 1</label><input id="abStoryH1" class="zs-input" value="${esc(ab.story_h2_1 || 'Dijitalleşmeye adanmış')}"/></div>
              <div class="zs-field"><label>Hikâye Soluk Başlık 2</label><input id="abStoryMuted" class="zs-input" value="${esc(ab.story_h2_muted || 'yaratıcı bir ekip.')}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Hikâye Görseli</label><div class="zs-input-group"><input id="abStoryImg" class="zs-input" value="${esc(ab.story_image || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="abStoryImg">Seç</button></div></div>
              <div class="zs-field"><label>Görsel Alt İmzası</label><input id="abStoryCap" class="zs-input" value="${esc(ab.story_caption || 'ZEYNART / KENDİ HİKÂYEMİZ, KENDİ İMZAMIZ.')}"/></div>
            </div>
            <div class="zs-field" style="margin-bottom:0"><label>Hikâye Paragrafları (Paragrafları boş satırla ayırın)</label><textarea id="abStoryP" class="zs-textarea" style="min-height:155px">${esc((ab.story_paragraphs || []).join('\n\n'))}</textarea></div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <h3>4. Çalışma İlkelerimiz Kartları (${abVals.length})</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="abAddValBtn">+ İlke Kartı Ekle</button>
            </div>
            <div class="zs-grid-3">
              ${abVals.map((vl, vIdx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.8);margin-bottom:0;padding:12px" data-ab-val="${vIdx}">
                  <div style="display:flex;justify-content:space-between;gap:6px;margin-bottom:8px">
                    <input class="zs-input abv-title" placeholder="İlke Başlığı" value="${esc(vl.title || '')}"/>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-ab-val-del="${vIdx}">✕</button>
                  </div>
                  <input type="hidden" class="abv-icon" value="${esc(vl.icon || 'compass')}"/>
                  <textarea class="zs-textarea abv-desc" style="min-height:70px" placeholder="İlke açıklaması...">${esc(vl.desc || '')}</textarea>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">5. 2014 Vizyon Bloğu &amp; Özel Yazılım Çözümleri</h3>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Vizyon Üst Etiket</label><input id="abVisEye" class="zs-input" value="${esc(ab.vision_eyebrow || '2014’TEN BUGÜNE')}"/></div>
              <div class="zs-field"><label>Vizyon Başlık 1</label><input id="abVisH1" class="zs-input" value="${esc(ab.vision_h2_1 || 'Teknoloji değişir.')}"/></div>
              <div class="zs-field"><label>Vizyon Vurgulu Başlık 2</label><input id="abVisSpan" class="zs-input" value="${esc(ab.vision_h2_span || 'İyi bir iş ortaklığı kalır.')}"/></div>
            </div>
            <div class="zs-field"><label>Vizyon Açıklaması</label><textarea id="abVisCopy" class="zs-textarea" style="min-height:68px">${esc(ab.vision_copy || '')}</textarea></div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Vizyon Görseli</label><div class="zs-input-group"><input id="abVisImg" class="zs-input" value="${esc(ab.vision_image || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="abVisImg">Seç</button></div></div>
              <div class="zs-field"><label>Vizyon Görsel Alt Etiketi</label><input id="abVisCap" class="zs-input" value="${esc(ab.vision_caption || 'ZEYNART / CREATIVE VISION')}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Yazılımlar Üst Etiket</label><input id="abSoftEye" class="zs-input" value="${esc(ab.software_eyebrow || 'ÖZEL YAZILIM ÇÖZÜMLERİ')}"/></div>
              <div class="zs-field"><label>Yazılımlar Başlığı (H2)</label><input id="abSoftH2" class="zs-input" value="${esc(ab.software_h2 || 'İş süreçlerinize göre şekillenen yazılımlar.')}"/></div>
            </div>
            <div class="zs-field" style="margin-bottom:0"><label>Liste Maddeleri — Distribütörlükler (Her satır 1 madde)</label><textarea id="abSoftItems" class="zs-textarea" style="min-height:130px">${esc((ab.software_items || []).join('\n'))}</textarea></div>
          </div>
        ` : ''}

        ${sub === 'clients' ? `
          <div class="zs-card">
            <h3 style="margin:0 0 12px">Referanslar Bölümü Üst Başlık &amp; Açıklama</h3>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Üst Etiket</label><input id="abClEye" class="zs-input" value="${esc(ab.clients_eyebrow || 'BİRLİKTE DEĞER ÜRETİYORUZ')}"/></div>
              <div class="zs-field"><label>Başlık 1. Satır</label><input id="abClH1" class="zs-input" value="${esc(ab.clients_h2_1 || 'Farklı sektörler.')}"/></div>
              <div class="zs-field"><label>Başlık Turkuaz 2. Satır</label><input id="abClAcc" class="zs-input" value="${esc(ab.clients_h2_accent || 'Ortak bir güven.')}"/></div>
            </div>
            <div class="zs-field" style="margin-bottom:0"><label>Bölüm Açıklaması</label><input id="abClLead" class="zs-input" value="${esc(ab.clients_lead || 'Her markanın hikâyesi farklı. Dijital dünyada birlikte yol aldığımız müşterilerimizle tanışın.')}"/></div>
          </div>
          <div class="zs-card">
            <div class="zs-card-head">
              <div>
                <h3>Birlikte Çalıştığımız Markalar (${(ab.clients || []).length} Referans)</h3>
                <p class="zs-card-sub">Bilgisayardan sürükle-bırak ile anında logo yükleyin, arşivden seçin veya manuel marka kartı ekleyin.</p>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <input id="abClientSearch" class="zs-input" style="max-width:190px" placeholder="Marka ara..." value="${esc(S.clientSearch)}"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="abAddClientBtn">📁 Arşivden / Yükleyerek Ekle</button>
                <button type="button" class="zs-btn zs-btn-sm" id="abAddBlankClientBtn">+ Boş Kart Ekle</button>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="abQuickSaveClientsBtn">✓ Kaydet &amp; Yayınla</button>
              </div>
            </div>

            <div class="zs-upload-studio-hero compact" id="abDirectClientDrop" style="margin-bottom:14px">
              <input type="file" id="abDirectClientFileInput" multiple accept="image/*" style="display:none"/>
              <div class="zs-upload-hero-left">
                <div class="zs-upload-orb" aria-hidden="true">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div class="zs-upload-hero-copy">
                  <h3>Yeni Müşteri Logolarını Buraya Sürükleyin veya Bilgisayardan Seçin</h3>
                  <p>Yüklediğiniz her logo otomatik WebP formatına çevrilip en üste marka kartı olarak eklenir; ardından <strong>✓ Kaydet &amp; Yayınla</strong> butonuna basmanız yeterlidir.</p>
                </div>
              </div>
              <div class="zs-upload-hero-cta">
                <button type="button" class="zs-btn zs-btn-primary zs-btn-sm" id="abDirectClientBrowseBtn">⬆️ Logo Dosyası Seç</button>
              </div>
            </div>
            <div id="abDirectClientDeckSlot" style="margin-bottom:12px"></div>

            <div class="zs-grid-2">
              ${(ab.clients || []).map((cl, idx) => {
                if (cq && !(String(cl.name || '') + ' ' + String(cl.url || '')).toLowerCase().includes(cq)) return '';
                return `
                  <div class="zs-card ${cl._isNew ? 'zs-client-card-new' : ''}" style="background:rgba(6,13,17,.8);margin-bottom:0;padding:12px" data-cl-idx="${idx}">
                    ${cl._isNew ? `<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px"><span class="zs-pill zs-pill-pub" style="font-size:10px">YENİ EKLENDİ — Kaydetmeyi Unutmayın</span></div>` : ''}
                    <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
                      <div style="width:68px;height:44px;border-radius:8px;background:#040a0d;border:1px solid var(--border-subtle);display:grid;place-items:center;overflow:hidden;flex-shrink:0">
                        <img src="${esc(resolveUrl(cl.image || ''))}" alt="" style="max-width:100%;max-height:100%;object-fit:contain"/>
                      </div>
                      <div style="flex:1;min-width:0">
                        <input class="zs-input cl-name" style="padding:5px 8px;font-size:12.5px;margin-bottom:4px" placeholder="Marka Adı (Örn: ornekfirma.com)" value="${esc(cl.name || '')}"/>
                        <input class="zs-input cl-url" style="padding:5px 8px;font-size:11.5px" placeholder="https://..." value="${esc(cl.url || '')}"/>
                      </div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center">
                      <input class="zs-input cl-img" style="padding:5px 8px;font-size:11px" value="${esc(cl.image || '')}"/>
                      <button type="button" class="zs-btn zs-btn-sm" data-cl-pick="${idx}" title="Logo Seç / Yükle">Logo</button>
                      <button type="button" class="zs-btn zs-btn-sm" data-cl-move="${idx}:-1" ${idx <= 0 ? 'disabled' : ''} title="Yukarı Taşı">↑</button>
                      <button type="button" class="zs-btn zs-btn-sm" data-cl-move="${idx}:1" ${idx >= (ab.clients || []).length - 1 ? 'disabled' : ''} title="Aşağı Taşı">↓</button>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-cl-del="${idx}" title="Sil">✕</button>
                    </div>
                  </div>
                `;
              }).join('')}
            </div>
          </div>
        ` : ''}

        ${sub === 'reviews' ? `
          <div class="zs-card" style="border-color:rgba(94,234,212,.3)">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
              <div>
                <strong style="color:#5eead4">★ Otomatik Google Yorumları & Puan Widget Stüdyosu</strong>
                <div style="font-size:12px;color:var(--text-muted);margin-top:3px">Canlı Google Haritalar senkronizasyonu ve karusel tasarım ayarları için Google Yorumları Stüdyosunu açabilirsiniz.</div>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="abGoGoogleStudioBtn">Google Yorumları Stüdyosu →</button>
            </div>
          </div>
          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Müşteri Yorumları Listesi</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="abAddRevBtn">+ Yorum Ekle</button>
            </div>
            ${(ab.reviews || []).map((rv, idx) => `
              <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-rv-idx="${idx}">
                <div style="display:flex;gap:8px">
                  <input class="zs-input rv-author" placeholder="Müşteri Adı" value="${esc(rv.author || '')}"/>
                  <input class="zs-input rv-date" placeholder="Tarih · Google" value="${esc(rv.date || '')}"/>
                  <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-rv-del="${idx}">Sil</button>
                </div>
                <textarea class="zs-textarea rv-text" style="min-height:64px">${esc(rv.text || '')}</textarea>
              </div>
            `).join('')}
          </div>
        ` : ''}
      `;
    }

    // 4. Hizmetler Ana Sayfa (hizmetler)
    if (key === 'hizmetler') {
      const sp = S.store.services_page || {};
      const services = S.store.services || [];
      const proc = S.store.home?.process || { steps: [] };
      return `
        ${renderCorePermalinkBar('hizmetler')}
        <div class="zs-card">
          <h3 style="margin:0 0 14px">Hizmetler Ana Sayfası Başlık & Giriş Alanı</h3>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Üst Etiket</label><input id="spEyebrow" class="zs-input" value="${esc(sp.eyebrow || '')}"/></div>
            <div class="zs-field"><label>Başlık Satır 1</label><input id="spH1" class="zs-input" value="${esc(sp.heading_line1 || '')}"/></div>
            <div class="zs-field"><label>Başlık Satır 2 (Soluk)</label><input id="spMuted" class="zs-input" value="${esc(sp.heading_muted || '')}"/></div>
          </div>
          <div class="zs-field" style="margin-bottom:0"><label>Giriş Açıklaması</label><textarea id="spLead" class="zs-textarea" style="min-height:70px">${esc(sp.lead || '')}</textarea></div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Hizmet Alt Sayfaları & Kart Sıralaması (${services.length} Alt Sayfa)</h3>
              <p class="zs-card-sub">Sıralamayı değiştirebilir veya herhangi bir hizmet alt sayfasının düzenleyicisine geçebilirsiniz.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="spCreateChildSrvBtn">+ Yeni Hizmet Alt Sayfası</button>
          </div>
          ${services.map((s, idx) => `
            <div class="zs-sort-row" style="justify-content:space-between">
              <div>
                <strong>/${esc(s.num || String(idx + 1).padStart(2, '0'))} — ${esc(s.title)}</strong>
                <div style="font-size:11.5px;color:var(--text-muted)">/hizmetler/${esc(s.slug)}/ · ${esc(s.short || '')}</div>
              </div>
              <div style="display:flex;gap:6px;align-items:center">
                <button type="button" class="zs-btn zs-btn-sm" data-sp-srv-move="${idx}:-1" ${idx === 0 ? 'disabled' : ''}>↑</button>
                <button type="button" class="zs-btn zs-btn-sm" data-sp-srv-move="${idx}:1" ${idx === services.length - 1 ? 'disabled' : ''}>↓</button>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" data-pg-jump="service:${esc(s.slug)}">Sayfayı Düzenle →</button>
              </div>
            </div>
          `).join('')}
        </div>

        <div class="zs-card">
          <h3 style="margin:0 0 12px">Nasıl Çalışıyoruz? — Sayfa Altı 4 Adımlı Süreç Bloğu</h3>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Üst Etiket</label><input id="spProcEye" class="zs-input" value="${esc(proc.eyebrow || 'NASIL ÇALIŞIYORUZ?')}"/></div>
            <div class="zs-field"><label>Başlık 1</label><input id="spProcH1" class="zs-input" value="${esc(proc.heading_line1 || 'Karmaşık olanı çözüyoruz.')}"/></div>
            <div class="zs-field"><label>Soluk Alt Başlık</label><input id="spProcMuted" class="zs-input" value="${esc(proc.heading_muted || 'Süreci sade tutuyoruz.')}"/></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Sağ Giriş Cümlesi</label><textarea id="spProcIntro" class="zs-textarea" style="min-height:56px">${esc(proc.intro || '')}</textarea></div>
            <div class="zs-field"><label>Alt Şerit Sol Not</label><input id="spProcFLeft" class="zs-input" value="${esc(proc.footer_left || 'Her aşamada açık iletişim.')}"/></div>
            <div class="zs-field"><label>Alt Şerit Sağ Not</label><input id="spProcFRight" class="zs-input" value="${esc(proc.footer_right || 'FİKİRDEN YAYINA, BİRLİKTE.')}"/></div>
          </div>
          <div class="zs-grid-2">
            ${(proc.steps || []).map((st, idx) => `
              <div class="zs-card" style="background:rgba(6,13,17,.78);margin-bottom:0" data-sp-pstep="${idx}">
                <div class="zs-grid-2">
                  <div class="zs-field"><label>Adım No</label><input class="zs-input spps-num" value="${esc(st.num || '')}"/></div>
                  <div class="zs-field"><label>Başlık</label><input class="zs-input spps-title" value="${esc(st.title || '')}"/></div>
                </div>
                <div class="zs-field"><label>Açıklama</label><textarea class="zs-textarea spps-desc" style="min-height:56px">${esc(st.desc || '')}</textarea></div>
                <div class="zs-field" style="margin-bottom:0"><label>Somut Çıktı Etiketi</label><input class="zs-input spps-out" value="${esc(st.output || '')}"/></div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    }

    // 5. Hizmet Alt Sayfası (service:<slug>)
    if (key.startsWith('service:')) {
      const srv = (S.store.services || []).find(s => s.slug === key.slice(8)) || {};
      const faqs = Array.isArray(srv.faqs) ? srv.faqs : [];
      const gd = srv.graphic_data || {};
      const hd = srv.hosting_data || {};
      return `
        <div class="zs-card">
          <div class="zs-grid-3">
            <div class="zs-field"><label>Hizmet Adı *</label><input id="svTitle" class="zs-input" value="${esc(srv.title || '')}"/></div>
            <div class="zs-field"><label>URL Uzantısı (/${esc(getResolvedPageSlug('hizmetler'))}/slug/)</label><input id="svSlug" class="zs-input" value="${esc(srv.slug || '')}"/></div>
            <div class="zs-field">
              <label>Sayfa Şablonu</label>
              <select id="svLayout" class="zs-select">
                <option value="standard" ${(srv.layout_type || 'standard') === 'standard' ? 'selected' : ''}>Standart Hizmet Sayfası</option>
                <option value="graphic" ${srv.layout_type === 'graphic' ? 'selected' : ''}>Grafik Tasarım Özel Stüdyo</option>
                <option value="hosting" ${srv.layout_type === 'hosting' ? 'selected' : ''}>Hosting & Paketler Düzeni</option>
              </select>
            </div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Üst Etiket</label><input id="svEyebrow" class="zs-input" value="${esc(srv.eyebrow || '')}"/></div>
            <div class="zs-field"><label>Kart Kısa Sloganı</label><input id="svShort" class="zs-input" value="${esc(srv.short || '')}"/></div>
            <div class="zs-field"><label>Kart Alt Etiketi</label><input id="svCardTag" class="zs-input" value="${esc(srv.card_tag || '')}"/></div>
          </div>
          <div class="zs-field"><label>Detay Sayfası Ana Başlığı (H1)</label><input id="svHeadline" class="zs-input" value="${esc(srv.headline || '')}"/></div>
          <div class="zs-field"><label>Detay Açıklaması</label><textarea id="svDesc" class="zs-textarea">${esc(srv.description || '')}</textarea></div>
          <div class="zs-grid-2">
            <div class="zs-field" style="margin-bottom:0"><label>Teslimatlar / Kapsam (Her satır 1 madde)</label><textarea id="svDeliv" class="zs-textarea" style="min-height:110px">${esc((srv.deliverables || []).join('\n'))}</textarea></div>
            <div class="zs-field" style="margin-bottom:0"><label>Teknoloji & Etiketler (Virgülle ayırın)</label><input id="svStack" class="zs-input" value="${esc((srv.stack || []).join(', '))}"/><label style="margin-top:10px">Yaklaşım Metni</label><textarea id="svApproach" class="zs-textarea" style="min-height:64px">${esc(srv.approach_copy || '')}</textarea></div>
          </div>
          ${(srv.layout_type || 'standard') === 'standard' ? `
            <div class="zs-grid-3" style="margin-top:12px">
              <div class="zs-field" style="margin-bottom:0"><label>Sol Sütun Üst Etiket</label><input id="svDelivEye" class="zs-input" value="${esc(srv.deliv_eyebrow || 'NELER YAPIYORUZ?')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Sol Sütun Başlık 1</label><input id="svDelivH1" class="zs-input" value="${esc(srv.deliv_h2_1 || 'İhtiyacınıza özel.')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Sol Sütun Soluk Başlık 2</label><input id="svDelivMuted" class="zs-input" value="${esc(srv.deliv_h2_muted || 'Her detay düşünülmüş.')}"/></div>
            </div>
            <div class="zs-grid-3" style="margin-top:10px">
              <div class="zs-field" style="margin-bottom:0"><label>Sağ Sütun Üst Etiket</label><input id="svAppEye" class="zs-input" value="${esc(srv.approach_eyebrow || 'YAKLAŞIMIMIZ')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Sağ Sütun Başlık 1</label><input id="svAppH1" class="zs-input" value="${esc(srv.approach_h2_1 || 'Doğru araçlar.')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Sağ Sütun Soluk Başlık 2</label><input id="svAppMuted" class="zs-input" value="${esc(srv.approach_h2_muted || 'Sürdürülebilir sonuçlar.')}"/></div>
            </div>
          ` : ''}
        </div>

        ${renderRecordGalleryCard(srv.gallery)}

        ${srv.layout_type === 'graphic' ? `
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Grafik Tasarım Vitrin Görselleri &amp; Renk Kimliği</h3>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Üst Vitrin Görseli</label><div class="zs-input-group"><input id="gdShowImg" class="zs-input" value="${esc(gd.showcase_image || '/images/zeynart-identity-still-life.webp')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="gdShowImg">Seç</button></div></div>
              <div class="zs-field"><label>Amblem &amp; Sanat Yönetimi Görseli</label><div class="zs-input-group"><input id="gdEmbImg" class="zs-input" value="${esc(gd.emblem_image || '/images/zeynart-emblem-gallery.webp')}"/><button type="button" class="zs-btn zs-btn-sm" data-mpick="gdEmbImg">Seç</button></div></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Vitrin Başlığı (H2)</label><input id="gdShowTitle" class="zs-input" value="${esc(gd.showcase_title || 'Bir logodan çok daha fazlası.')}"/></div>
              <div class="zs-field"><label>Marka Renk Etiketleri (Virgülle ayırın)</label><input id="gdPalette" class="zs-input" value="${esc((gd.palette || ['Petrol', 'Turkuaz', 'Buz beyazı']).join(', '))}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field" style="margin-bottom:0"><label>Vitrin Açıklaması</label><textarea id="gdShowCopy" class="zs-textarea" style="min-height:60px">${esc(gd.showcase_copy || '')}</textarea></div>
              <div class="zs-field" style="margin-bottom:0"><label>Vitrin Alt Vurgusu</label><textarea id="gdShowSub" class="zs-textarea" style="min-height:60px">${esc(gd.showcase_sub || '')}</textarea></div>
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Tasarım Hizmet Alanları Kartları (${(gd.offerings || []).length})</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="svAddGdOffBtn">+ Kart Ekle</button>
            </div>
            <div class="zs-grid-2">
              ${(gd.offerings || []).map((off, oIdx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.8);margin-bottom:0" data-gd-off="${oIdx}">
                  <div style="display:flex;gap:8px;margin-bottom:8px">
                    <input class="zs-input gdo-num" style="max-width:70px" value="${esc(off.num || String(oIdx + 1).padStart(2, '0'))}"/>
                    <input class="zs-input gdo-title" placeholder="Kart Başlığı" value="${esc(off.title || '')}"/>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-gd-off-del="${oIdx}">✕</button>
                  </div>
                  <input type="hidden" class="gdo-icon" value="${esc(off.icon || 'pen-tool')}"/>
                  <div class="zs-field"><label>Açıklama</label><textarea class="zs-textarea gdo-desc" style="min-height:56px">${esc(off.desc || '')}</textarea></div>
                  <div class="zs-field" style="margin-bottom:0"><label>Alt Maddeler (Satır satır)</label><textarea class="zs-textarea gdo-items" style="min-height:74px">${esc((off.items || []).join('\n'))}</textarea></div>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Tasarım Süreci Adımları (${(gd.process || []).length})</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="svAddGdProcBtn">+ Adım Ekle</button>
            </div>
            <div class="zs-grid-2">
              ${(gd.process || []).map((pr, pIdx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.8);margin-bottom:0" data-gd-proc="${pIdx}">
                  <div style="display:flex;gap:8px;margin-bottom:8px">
                    <input class="zs-input gdp-num" style="max-width:70px" value="${esc(pr.num || String(pIdx + 1).padStart(2, '0'))}"/>
                    <input class="zs-input gdp-title" placeholder="Adım Başlığı" value="${esc(pr.title || '')}"/>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-gd-proc-del="${pIdx}">✕</button>
                  </div>
                  <textarea class="zs-textarea gdp-desc" style="min-height:58px">${esc(pr.desc || '')}</textarea>
                </div>
              `).join('')}
            </div>
          </div>
        ` : ''}

        ${srv.layout_type === 'hosting' ? `
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Hosting Genel Bakış Alanı</h3>
            <div class="zs-grid-3">
              <div class="zs-field"><label>Üst Etiket</label><input id="hdOvEye" class="zs-input" value="${esc(hd.overview_eyebrow || 'HOSTING VE SUNUCU HİZMETLERİ')}"/></div>
              <div class="zs-field"><label>Başlık 1</label><input id="hdOvH1" class="zs-input" value="${esc(hd.overview_h2_1 || 'Önceliğimiz hız.')}"/></div>
              <div class="zs-field"><label>Turkuaz Başlık 2</label><input id="hdOvAcc" class="zs-input" value="${esc(hd.overview_h2_accent || 'Temelimiz güven.')}"/></div>
            </div>
            <div class="zs-field" style="margin-bottom:0"><label>Genel Bakış Açıklaması</label><textarea id="hdOvCopy" class="zs-textarea" style="min-height:66px">${esc(hd.overview_copy || '')}</textarea></div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Neler Yapıyoruz? — Sunucu Çözümleri (${(hd.solutions || []).length})</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="svAddHdSolBtn">+ Çözüm Ekle</button>
            </div>
            <div class="zs-grid-2">
              ${(hd.solutions || []).map((sol, sIdx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.8);margin-bottom:0" data-hd-sol="${sIdx}">
                  <div style="display:flex;gap:8px;margin-bottom:8px">
                    <input class="zs-input hds-num" style="max-width:70px" value="${esc(sol.num || String(sIdx + 1).padStart(2, '0'))}"/>
                    <input class="zs-input hds-title" placeholder="Çözüm Başlığı" value="${esc(sol.title || '')}"/>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-hd-sol-del="${sIdx}">✕</button>
                  </div>
                  <textarea class="zs-textarea hds-desc" style="min-height:60px">${esc(sol.desc || '')}</textarea>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Hosting Paketleri (Basic / Eko / Pro)</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="svAddHostPkg">+ Yeni Paket Ekle</button>
            </div>
            <div class="zs-grid-3">
              ${((hd.packages) || []).map((pk, pIdx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.8);margin-bottom:0" data-hpkg="${pIdx}">
                  <div class="zs-field"><label>Paket Adı</label><input class="zs-input hpk-name" value="${esc(pk.name || '')}"/></div>
                  <div class="zs-grid-2">
                    <div class="zs-field"><label>Disk / Fiyat</label><input class="zs-input hpk-disk" value="${esc(pk.disk || '')}"/></div>
                    <div class="zs-field"><label>Alt Etiket</label><input class="zs-input hpk-dlbl" value="${esc(pk.disk_label || 'Disk alanı')}"/></div>
                  </div>
                  <div class="zs-field"><label>Özellikler (Satır satır)</label><textarea class="zs-textarea hpk-feat" style="min-height:110px">${esc((pk.features || []).join('\n'))}</textarea></div>
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <label class="zs-switch"><input type="checkbox" class="hpk-pop" ${pk.featured ? 'checked' : ''}/><span>Öne Çıkar</span></label>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-hpkg-del="${pIdx}">Sil</button>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <h3>Neden Zeynart? — Altyapı Avantajları (${(hd.benefits || []).length})</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="svAddHdBenBtn">+ Avantaj Ekle</button>
            </div>
            <div class="zs-grid-2">
              ${(hd.benefits || []).map((bn, bIdx) => `
                <div class="zs-card" style="background:rgba(6,13,17,.8);margin-bottom:0" data-hd-ben="${bIdx}">
                  <div style="display:flex;gap:8px;margin-bottom:8px">
                    <input class="zs-input hdb-title" placeholder="Avantaj Başlığı" value="${esc(bn.title || '')}"/>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-hd-ben-del="${bIdx}">✕</button>
                  </div>
                  <textarea class="zs-textarea hdb-desc" style="min-height:56px">${esc(bn.desc || '')}</textarea>
                </div>
              `).join('')}
            </div>
          </div>
        ` : ''}

        <div class="zs-card">
          <div class="zs-card-head">
            <h3>Hizmet Sıkça Sorulan Sorular (${faqs.length})</h3>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="svAddFaqBtn">+ Soru Ekle</button>
          </div>
          ${faqs.map((fq, fIdx) => `
            <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-sv-faq="${fIdx}">
              <div style="display:flex;gap:8px">
                <input class="zs-input svfq-q" placeholder="Soru" value="${esc(fq.q || '')}"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-sv-faq-del="${fIdx}">Sil</button>
              </div>
              <textarea class="zs-textarea svfq-a" style="min-height:58px" placeholder="Cevap">${esc(fq.a || '')}</textarea>
            </div>
          `).join('')}
        </div>
      `;
    }

    // 6. Projeler Ana Sayfa (projeler)
    if (key === 'projeler') {
      const pp = S.store.projects_page || {};
      const projects = S.store.projects || [];
      return `
        ${renderCorePermalinkBar('projeler')}
        <div class="zs-card">
          <h3 style="margin:0 0 14px">Projeler Ana Sayfası Başlık & Giriş Alanı</h3>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Üst Etiket</label><input id="ppEyebrow" class="zs-input" value="${esc(pp.eyebrow || '')}"/></div>
            <div class="zs-field"><label>Başlık 1</label><input id="ppH1" class="zs-input" value="${esc(pp.heading_line1 || '')}"/></div>
            <div class="zs-field"><label>Başlık 2 (Soluk)</label><input id="ppMuted" class="zs-input" value="${esc(pp.heading_muted || '')}"/></div>
          </div>
          <div class="zs-field"><label>Giriş Açıklaması</label><textarea id="ppLead" class="zs-textarea" style="min-height:68px">${esc(pp.lead || '')}</textarea></div>
          <div class="zs-field" style="margin-bottom:0"><label>Alt Bilgilendirme Notu (İsteğe Bağlı)</label><input id="ppDisclaimer" class="zs-input" value="${esc(pp.disclaimer || '')}"/></div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Proje Alt Sayfaları (${projects.length} Vaka Çalışması)</h3>
              <p class="zs-card-sub">Projelerin sırasını değiştirebilir veya detay sayfasını düzenleyebilirsiniz.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="ppCreateChildPrjBtn">+ Yeni Proje Alt Sayfası</button>
          </div>
          ${projects.map((p, idx) => `
            <div class="zs-sort-row" style="justify-content:space-between">
              <div>
                <strong>${esc(p.name)}</strong> <span style="font-size:12px;color:var(--text-dim)">· ${esc(p.sector || '')} (${esc(p.year || '')})</span>
                <div style="font-size:11.5px;color:var(--text-muted)">/projeler/${esc(p.slug)}/</div>
              </div>
              <div style="display:flex;gap:6px;align-items:center">
                <button type="button" class="zs-btn zs-btn-sm" data-pp-prj-move="${idx}:-1" ${idx === 0 ? 'disabled' : ''}>↑</button>
                <button type="button" class="zs-btn zs-btn-sm" data-pp-prj-move="${idx}:1" ${idx === projects.length - 1 ? 'disabled' : ''}>↓</button>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" data-pg-jump="project:${esc(p.slug)}">Sayfayı Düzenle →</button>
              </div>
            </div>
          `).join('')}
        </div>
      `;
    }

    // 7. Proje Alt Sayfası (project:<slug>)
    if (key.startsWith('project:')) {
      const prj = (S.store.projects || []).find(p => p.slug === key.slice(8)) || {};
      const ba = Object.assign({
        enabled: true,
        before_label: 'ÖNCESİ (Eski Mimari)',
        after_label: 'SONRASI (Zeynart %100 Performans)',
        before_image: '',
        after_image: ''
      }, prj.before_after || {});
      const metricsArr = Array.isArray(prj.metrics) && prj.metrics.length
        ? prj.metrics
        : [
            { value: '100/100', label: 'Google PageSpeed Skoru' },
            { value: '< 0.4sn', label: 'İlk Yanıt Süresi (TTFB)' },
            { value: '+240%', label: 'Organik Dönüşüm Artışı' },
            { value: '0 Çerez', label: 'KVKK & LSCache Uyumu' }
          ];
      return `
        <div class="zs-card">
          <div class="zs-grid-3">
            <div class="zs-field"><label>Proje Adı *</label><input id="pjName" class="zs-input" value="${esc(prj.name || '')}"/></div>
            <div class="zs-field"><label>URL Uzantısı (/${esc(getResolvedPageSlug('projeler'))}/slug/)</label><input id="pjSlug" class="zs-input" value="${esc(prj.slug || '')}"/></div>
            <div class="zs-field"><label>Yıl & Sektör</label><div style="display:flex;gap:6px"><input id="pjYear" class="zs-input" style="max-width:88px" value="${esc(prj.year || '2026')}"/><input id="pjSector" class="zs-input" value="${esc(prj.sector || '')}"/></div></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Üst Etiket (Eyebrow)</label><input id="pjEyebrow" class="zs-input" value="${esc(prj.eyebrow || 'PROJE VAKA ÇALIŞMASI')}"/></div>
            <div class="zs-field"><label>Kapsam Özeti</label><input id="pjScope" class="zs-input" value="${esc(prj.scope || '')}"/></div>
            <div class="zs-field">
              <label>Görsel Vitrin Türü</label>
              <select id="pjVisType" class="zs-select">
                <option value="orbit" ${prj.visual_type === 'orbit' ? 'selected' : ''}>Orbit Sanatsal Kart</option>
                <option value="forma" ${prj.visual_type === 'forma' ? 'selected' : ''}>Forma Sanatsal Sahne</option>
                <option value="nexus" ${prj.visual_type === 'nexus' ? 'selected' : ''}>Nexus SaaS Sahnesi</option>
                <option value="image" ${prj.visual_type === 'image' ? 'selected' : ''}>Özel Yüklenen Görsel (/uploads)</option>
              </select>
            </div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Çalışma Türü Etiketi</label><input id="pjWorkType" class="zs-input" value="${esc(prj.work_type || 'Özgün ürün konsepti')}"/></div>
            <div class="zs-field"><label>İlgili Hizmet Linki</label><input id="pjRelSrv" class="zs-input" placeholder="/hizmetler/web-tasarim/" value="${esc(prj.related_service_url || '')}"/></div>
            <div class="zs-field"><label>Canlı Site Önizleme Linki (Opsiyonel)</label><input id="pjLiveUrl" class="zs-input" placeholder="https://ornekmarka.com" value="${esc(prj.live_url || '')}"/></div>
          </div>
          <div class="zs-field"><label>Detay Sayfası Ana Başlığı (H1)</label><input id="pjHeadline" class="zs-input" value="${esc(prj.headline || '')}"/></div>
          <div class="zs-field"><label>Kısa Özet</label><textarea id="pjSummary" class="zs-textarea" style="min-height:64px">${esc(prj.summary || '')}</textarea></div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Problem / İhtiyaç</label><textarea id="pjProb" class="zs-textarea" style="min-height:72px">${esc(prj.problem || '')}</textarea></div>
            <div class="zs-field"><label>Yaklaşım</label><textarea id="pjApp" class="zs-textarea" style="min-height:72px">${esc(prj.approach || '')}</textarea></div>
            <div class="zs-field"><label>Çözüm</label><textarea id="pjSol" class="zs-textarea" style="min-height:72px">${esc(prj.solution || '')}</textarea></div>
            <div class="zs-field"><label>Sonuç & Çıktı</label><textarea id="pjRes" class="zs-textarea" style="min-height:72px">${esc(prj.result || '')}</textarea></div>
          </div>
          <div class="zs-field"><label>Kullanılan Teknolojiler (Virgülle ayırın)</label><input id="pjStack" class="zs-input" value="${esc((prj.stack || []).join(', '))}"/></div>
          <div class="zs-grid-3">
            <div class="zs-field" style="margin-bottom:0"><label>Arayüz Önizleme Başlığı</label><input id="pjIfGreeting" class="zs-input" value="${esc(prj.interface_greeting || 'Her şey, daha net.')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Arayüz Kartları (Virgülle 3 başlık)</label><input id="pjIfBlocks" class="zs-input" value="${esc((prj.interface_blocks || ['Genel görünüm', 'Detaylar', 'Özet']).join(', '))}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Arayüz Alt İmzası</label><input id="pjIfFooter" class="zs-input" value="${esc(prj.interface_footer || 'Arayüz görünümü')}"/></div>
          </div>
        </div>

        <div class="zs-card" style="margin-top:14px;border-color:rgba(94,234,212,.28)">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
            <div>
              <span class="zs-badge-pill">VAKA ANALİZİ DÖNÜŞÜM PERDESİ</span>
              <h3 style="margin:6px 0 2px">🪄 Öncesi / Sonrası (Before-After) Karşılaştırma Perdesi &amp; Kazanım Metrikleri</h3>
              <p class="zs-card-sub">Görsel seçmezseniz yerleşik etkileşimli mimari karşılaştırma perdesi otomatik gösterilir; dilerseniz /uploads arşivinden ekran görüntüsü seçebilirsiniz.</p>
            </div>
            <label class="zs-switch" style="margin:0"><input type="checkbox" id="pjBaEnabled" ${ba.enabled !== false ? 'checked' : ''}/><span>Perde Aktif</span></label>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Sol Taraf (Öncesi) Başlığı</label><input id="pjBaBeforeLbl" class="zs-input" value="${esc(ba.before_label)}"/></div>
            <div class="zs-field"><label>Sağ Taraf (Sonrası) Başlığı</label><input id="pjBaAfterLbl" class="zs-input" value="${esc(ba.after_label)}"/></div>
            <div class="zs-field">
              <label>Öncesi Görseli (Boş bırakılırsa yerleşik tel kafes görünür)</label>
              <div class="zs-input-group">
                <input id="pjBaBeforeImg" class="zs-input" placeholder="/uploads/..." value="${esc(ba.before_image || '')}"/>
                <button type="button" class="zs-btn zs-btn-sm" data-mpick="pjBaBeforeImg">Seç</button>
              </div>
            </div>
            <div class="zs-field">
              <label>Sonrası Görseli (Boş bırakılırsa yerleşik Zeynart sahnesi görünür)</label>
              <div class="zs-input-group">
                <input id="pjBaAfterImg" class="zs-input" placeholder="/uploads/..." value="${esc(ba.after_image || '')}"/>
                <button type="button" class="zs-btn zs-btn-sm" data-mpick="pjBaAfterImg">Seç</button>
              </div>
            </div>
          </div>
          <div style="margin-top:6px;padding-top:12px;border-top:1px solid var(--border-subtle)">
            <label style="display:block;font-size:12px;font-weight:600;color:#5eead4;margin-bottom:8px">📊 4'lü Ölçülebilir Kazanım Metrikleri (Kart &amp; Detay Şeridi)</label>
            <div class="zs-grid-2">
              ${[0, 1, 2, 3].map(mi => {
                const mRow = metricsArr[mi] || { value: '', label: '' };
                return `<div style="display:flex;gap:8px" data-pj-metric-row="${mi}">
                  <input class="zs-input pjm-val" style="max-width:120px;font-weight:700" placeholder="100/100" value="${esc(mRow.value || '')}"/>
                  <input class="zs-input pjm-lbl" placeholder="Kazanım açıklaması" value="${esc(mRow.label || '')}"/>
                </div>`;
              }).join('')}
            </div>
          </div>
        </div>

        ${renderRecordGalleryCard(prj.gallery)}
      `;
    }

    // 8. Blog Vitrin Sayfası (blog)
    if (key === 'blog') {
      const bs = S.store.blog_settings || {};
      const blogCats = (bs.categories || bs.categories_order || []).filter(c => c !== 'TÜMÜ').join(', ');
      return `
        ${renderCorePermalinkBar('blog')}
        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>${esc(pgInfo.title || 'Blog')} Vitrin Sayfası (${esc(pgInfo.url)})</h3>
              <p class="zs-card-sub">Blog ana sayfasının sayfa adını, üst vitrin başlıklarını, sayfa başı makale sayısını ve kategorilerini düzenleyin.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgGoBlogPostsBtn">✎ Blog Yazılarını Yönet →</button>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Sayfa Adı (Liste &amp; Breadcrumb)</label><input id="bsHeading" class="zs-input" value="${esc(bs.heading || pgInfo.title || 'Blog')}"/></div>
            <div class="zs-field"><label>Sol Üst Etiket</label><input id="bsEyebrow" class="zs-input" value="${esc(bs.kicker_left || bs.eyebrow || 'ZEYNART JOURNAL')}"/></div>
            <div class="zs-field"><label>Sayfa Başı Yazı Adedi</label><input type="number" id="bsPerPage" class="zs-input" min="6" max="96" value="${Number(bs.per_page || 24)}"/></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Vitrin H1 Başlığı (1. Satır)</label><input id="bsHeroH1" class="zs-input" value="${esc(bs.heading_line1 || 'İyi fikirler,')}"/></div>
            <div class="zs-field"><label>Vitrin H1 Vurgusu (2. Satır)</label><input id="bsHeroSpan" class="zs-input" value="${esc(bs.heading_span || 'paylaştıkça büyür.')}"/></div>
            <div class="zs-field"><label>Sağ Üst Etiket</label><input id="bsKickerRight" class="zs-input" value="${esc(bs.kicker_right || 'TASARIM. TEKNOLOJİ. BİR ADIM ÖTESİ.')}"/></div>
          </div>
          <div class="zs-field"><label>Giriş Açıklaması</label><textarea id="bsSubheading" class="zs-textarea" style="min-height:72px">${esc(bs.lead ?? bs.subheading ?? '')}</textarea></div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Öne Çıkan Kategoriler (Virgülle ayırın)</label><input id="bsCats" class="zs-input" value="${esc(blogCats)}"/></div>
            <div class="zs-field"><label>Sağ Rozet Notu</label><input id="bsBadgeRight" class="zs-input" value="${esc(bs.badge_right || 'KEŞFET / ÖĞREN / İLHAM AL')}"/></div>
          </div>
          <div style="padding:12px 14px;margin-bottom:10px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
              <strong style="color:#5eead4;font-size:13px">🎧 "Makaleyi Sesli Dinle" Podcast Oynatıcısı</strong>
              <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Her blog makalesinin başında 1x/1.25x/1.5x hız kontrollü ve paragraf vurgulu sesli dinleme çubuğunu gösterir (0 çerez).</div>
            </div>
            <label class="zs-switch" style="margin:0"><input type="checkbox" id="bsAudioPlayer" ${bs.audio_player_enabled !== false ? 'checked' : ''}/><span>Aktif</span></label>
          </div>
          <div style="padding:12px 14px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
              <strong style="color:#5eead4;font-size:13px">Okuyucu Sosyal Paylaşım Çubuğu</strong>
              <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Her blog yazısının altında WhatsApp, X, LinkedIn, Facebook, Telegram, E-posta ve Bağlantıyı Kopyala çubuğunu gösterir (0 çerez / KVKK uyumlu).</div>
            </div>
            <label class="zs-switch" style="margin:0"><input type="checkbox" id="bsShowShareBar" ${bs.show_share_bar !== false ? 'checked' : ''}/><span>Aktif</span></label>
          </div>
        </div>
      `;
    }

    // 9. İletişim Sayfası (iletisim) — Tam Kapsamlı İletişim Yönetimi
    if (key === 'iletisim') {
      const ct = S.store.contact || {};
      const st = S.store.site || {};
      const socList = Array.isArray(S.store.seo?.social_profiles) ? S.store.seo.social_profiles : [];
      const steps = Array.isArray(ct.steps) ? ct.steps : [];
      const showPhone = ct.show_phone !== false;
      const showEmail = ct.show_email !== false;
      const showAddress = ct.show_address !== false;
      const showSocials = (ct.show_socials ?? st.show_contact_socials ?? true) !== false;
      const showSteps = ct.show_steps !== false;
      const showCta = ct.show_cta === true;
      return `
        ${renderCorePermalinkBar('iletisim')}
        <div class="zs-card" style="border-color:rgba(45,212,191,.36)">
          <div class="zs-card-head">
            <div>
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span class="zs-pill zs-pill-pub">DOĞRUDAN İLETİŞİM KANALLARI</span>
                <h3 style="margin:0">1. Telefon, E-Posta, Ofis Adresi, Sosyal Medya &amp; Harita</h3>
              </div>
              <p class="zs-card-sub" style="margin-top:4px">İletişim sayfasındaki (<code>/iletisim/</code>) telefon, e-posta, açık adres, sosyal medya kartları ve Google Harita gömme alanını buradan doğrudan düzenleyin.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm" id="ctGoMessagesBtn">📩 Gelen Proje Taleplerini Aç →</button>
          </div>

          <div class="zs-grid-2">
            <div style="padding:14px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <strong style="color:#5eead4;font-size:13px">📞 Telefon Kartı</strong>
                <label class="zs-switch" style="margin:0"><input type="checkbox" id="ctShowPhone" ${showPhone ? 'checked' : ''}/><span>Göster</span></label>
              </div>
              <div class="zs-grid-2">
                <div class="zs-field"><label>Görünür Telefon Numarası</label><input id="ctSitePhone" class="zs-input" placeholder="0850 305 0 533" value="${esc(st.phone || '')}"/></div>
                <div class="zs-field"><label>Tıklanınca Aranacak Numara</label><input id="ctSitePhoneHref" class="zs-input" placeholder="+908503050533" value="${esc(st.phone_href || '')}"/></div>
              </div>
              <div class="zs-field"><label>Faks / İkinci Numara (boşsa gizlenir)</label><input id="ctSiteFax" class="zs-input" placeholder="(+90) 216 000 00 00" value="${esc(st.fax || '')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Kart Üst Etiketi</label><input id="ctPhoneLbl" class="zs-input" placeholder="TELEFON" value="${esc(ct.phone_label || 'TELEFON')}"/></div>
            </div>

            <div style="padding:14px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <strong style="color:#5eead4;font-size:13px">✉️ E-Posta Kartı</strong>
                <label class="zs-switch" style="margin:0"><input type="checkbox" id="ctShowEmail" ${showEmail ? 'checked' : ''}/><span>Göster</span></label>
              </div>
              <div class="zs-field"><label>İletişim E-Posta Adresi</label><input id="ctSiteEmail" class="zs-input" placeholder="info@zeynart.com" value="${esc(st.email || '')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Kart Üst Etiketi</label><input id="ctEmailLbl" class="zs-input" placeholder="E-POSTA" value="${esc(ct.email_label || 'E-POSTA')}"/></div>
            </div>
          </div>

          <div class="zs-grid-2" style="margin-top:12px">
            <div style="padding:14px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <strong style="color:#5eead4;font-size:13px">📍 Ofis Adresi &amp; Yol Tarifi Kartı</strong>
                <label class="zs-switch" style="margin:0"><input type="checkbox" id="ctShowAddr" ${showAddress ? 'checked' : ''}/><span>Göster</span></label>
              </div>
              <div class="zs-grid-2">
                <div class="zs-field"><label>Şehir / Bölge</label><input id="ctSiteCity" class="zs-input" placeholder="İzmir" value="${esc(st.city || '')}"/></div>
                <div class="zs-field"><label>Kart Üst Etiketi</label><input id="ctAddrLbl" class="zs-input" placeholder="OFİS & ADRES" value="${esc(ct.address_label || 'OFİS & ADRES')}"/></div>
              </div>
              <div class="zs-field"><label>Açık Ofis Adresi</label><input id="ctSiteAddr" class="zs-input" placeholder="Mahalle, Sokak, No..." value="${esc(st.address || '')}"/></div>
              <div class="zs-field" style="margin-bottom:0"><label>Google Haritalar Yol Tarifi Linki (Boşsa adresten otomatik oluşur)</label><input id="ctMapsUrl" class="zs-input" placeholder="https://maps.google.com/..." value="${esc(ct.maps_url || S.store.google_reviews?.maps_url || '')}"/></div>
            </div>

            <div style="padding:14px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <strong style="color:#5eead4;font-size:13px">🌐 Sosyal Medya Kanalları &amp; Harita</strong>
                <label class="zs-switch" style="margin:0"><input type="checkbox" id="ctShowSocials" ${showSocials ? 'checked' : ''}/><span>Sosyal Kartları Göster</span></label>
              </div>
              <div class="zs-field">
                <label>Sosyal Medya Adresleri (Her satıra 1 URL: Instagram, Facebook, LinkedIn, X, YouTube)</label>
                <textarea id="ctSocialProfiles" class="zs-textarea" style="min-height:84px" placeholder="https://www.instagram.com/zeynartweb&#10;https://www.facebook.com/zeynartweb">${esc(socList.join('\n'))}</textarea>
              </div>
              <div class="zs-field" style="margin-bottom:0">
                <label>Sayfa Altı Gömülü Google Harita (Embed URL veya &lt;iframe&gt; kodu yapıştırın — İsteğe bağlı)</label>
                <input id="ctMapEmbed" class="zs-input" placeholder="https://www.google.com/maps/embed?pb=... veya <iframe> kodu" value="${esc(ct.map_embed_url || '')}"/>
              </div>
            </div>
          </div>
        </div>

        <div class="zs-card">
          <h3 style="margin:0 0 14px">2. İletişim Üst Banner &amp; “Birlikte Çalışalım” Sol Sütun Metinleri</h3>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Banner Üst Etiket</label><input id="ctEyebrow" class="zs-input" value="${esc(ct.eyebrow || '')}"/></div>
            <div class="zs-field"><label>Banner Başlık 1. Satır</label><input id="ctH1" class="zs-input" value="${esc(ct.heading_line1 || '')}"/></div>
            <div class="zs-field"><label>Banner Başlık 2. Satır (Soluk)</label><input id="ctMuted" class="zs-input" value="${esc(ct.heading_muted || '')}"/></div>
          </div>
          <div class="zs-field"><label>Banner Giriş Açıklaması</label><textarea id="ctLead" class="zs-textarea" style="min-height:64px">${esc(ct.lead || '')}</textarea></div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Sol Sütun Üst Etiket (Örn: BİRLİKTE ÇALIŞALIM)</label><input id="ctSideEye" class="zs-input" value="${esc(ct.sidebar_eyebrow || 'BİRLİKTE ÇALIŞALIM')}"/></div>
            <div class="zs-field"><label>Sol Sütun Başlığı (Örn: Doğrudan iletişim.)</label><input id="ctSideH" class="zs-input" value="${esc(ct.sidebar_heading || 'Doğrudan iletişim.')}"/></div>
          </div>
          <div class="zs-field" style="margin-bottom:0"><label>Sol Sütun Açıklama Paragrafı</label><textarea id="ctSideCopy" class="zs-textarea" style="min-height:62px">${esc(ct.sidebar_copy || '')}</textarea></div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:14px">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
              <h3 style="margin:0">3. Proje Talep Formu Başlıkları, Alan Etiketleri &amp; Bütçeler</h3>
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="ctReqPhone" ${ct.require_phone !== false ? 'checked' : ''}/><span>Cep Telefonu Zorunlu Olsun</span></label>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="ctJumpSmtpBtn">✉️ SMTP Mail Ayarları &amp; Test →</button>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Form Üst Etiketi</label><input id="ctFormEye" class="zs-input" value="${esc(ct.form_eyebrow || 'PROJE TALEBİ')}"/></div>
            <div class="zs-field"><label>Form Başlığı</label><input id="ctFormH" class="zs-input" value="${esc(ct.form_heading || 'Projenizden bahsedin.')}"/></div>
            <div class="zs-field"><label>Gönder Butonu Metni</label><input id="ctSubmitLbl" class="zs-input" value="${esc(ct.submit_label || 'Mesajı gönderin')}"/></div>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field"><label>Form Üst Açıklaması</label><input id="ctFormCopy" class="zs-input" value="${esc(ct.form_copy || '')}"/></div>
            <div class="zs-field"><label>Buton Altı Yan Not</label><input id="ctFormHint" class="zs-input" value="${esc(ct.form_hint || 'Doğrudan e-posta ile de ulaşabilirsiniz.')}"/></div>
          </div>
          <div class="zs-grid-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:10px">
            <div class="zs-field" style="margin-bottom:0"><label>Ad Soyad Etiketi</label><input id="ctLblName" class="zs-input" value="${esc(ct.lbl_name || 'Ad soyad *')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Ad Soyad İpucu</label><input id="ctPhName" class="zs-input" value="${esc(ct.ph_name || 'Adınız ve soyadınız')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Cep Telefonu Etiketi</label><input id="ctLblPhone" class="zs-input" value="${esc(ct.lbl_phone || 'Cep telefonu *')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Cep Telefonu İpucu</label><input id="ctPhPhone" class="zs-input" value="${esc(ct.ph_phone || '05XX XXX XX XX')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>E-Posta Etiketi</label><input id="ctLblEmail" class="zs-input" value="${esc(ct.lbl_email || 'E-posta adresiniz *')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>E-Posta İpucu</label><input id="ctPhEmail" class="zs-input" value="${esc(ct.ph_email || 'siz@sirketiniz.com')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Marka / Şirket Etiketi</label><input id="ctLblComp" class="zs-input" value="${esc(ct.lbl_company || 'Şirket / kurum')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Marka İpucu</label><input id="ctPhComp" class="zs-input" value="${esc(ct.ph_company || 'Şirket veya kurum adı')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Hizmet Seçimi Etiketi</label><input id="ctLblSrv" class="zs-input" value="${esc(ct.lbl_service || 'Hizmet alanı *')}"/></div>
            <div class="zs-field" style="margin-bottom:0"><label>Hizmet Varsayılan Seçenek</label><input id="ctPhSrv" class="zs-input" value="${esc(ct.ph_service || 'İlgilendiğiniz alanı seçin')}"/></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Bütçe Alanı Başlığı</label><input id="ctLblBudget" class="zs-input" value="${esc(ct.lbl_budget || 'Tahmini bütçe aralığı (isteğe bağlı)')}"/></div>
            <div class="zs-field"><label>Proje Özeti Başlığı</label><input id="ctLblMsg" class="zs-input" value="${esc(ct.lbl_message || 'Proje özeti *')}"/></div>
            <div class="zs-field"><label>Proje Özeti İpucu</label><input id="ctPhMsg" class="zs-input" value="${esc(ct.ph_message || 'Hedefiniz, kapsam ve takvim beklentiniz...')}"/></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>KVKK Link Başlığı</label><input id="ctConsLbl" class="zs-input" value="${esc(ct.consent_link_label || 'KVKK Aydınlatma Metni')}"/></div>
            <div class="zs-field"><label>KVKK Link Adresi</label><input id="ctConsUrl" class="zs-input" value="${esc(ct.consent_link_url || '/kvkk/')}"/></div>
            <div class="zs-field"><label>KVKK Onay Cümlesi Devamı</label><input id="ctConsTxt" class="zs-input" value="${esc(ct.consent_text || 'kapsamında iletişim bilgilerimin talebimle ilgili dönüş yapılması amacıyla işlenmesini kabul ediyorum.')}"/></div>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field" style="margin-bottom:0"><label>Bütçe Aralıkları (Her satıra 1 seçenek — Boş bırakırsanız bütçe alanı gizlenir)</label><textarea id="ctBudgets" class="zs-textarea" style="min-height:95px">${esc((ct.budgets || []).join('\n'))}</textarea></div>
            <div class="zs-field" style="margin-bottom:0"><label>Form Gönderildiğinde Teşekkür Mesajı</label><textarea id="ctSuccessMsg" class="zs-textarea" style="min-height:95px">${esc(ct.success_message || 'Proje talebiniz başarıyla alındı. Ekibimiz en kısa sürede sizinle iletişime geçecektir.')}</textarea></div>
          </div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
              <h3 style="margin:0">4. İletişim Yol Haritası Adımları (${steps.length})</h3>
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="ctShowSteps" ${showSteps ? 'checked' : ''}/><span>Adımları Göster</span></label>
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="ctShowCta" ${showCta ? 'checked' : ''}/><span>Alt Global Çağrı Bandını Göster</span></label>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="ctAddStepBtn">+ Adım Ekle</button>
          </div>
          <div class="zs-field"><label>Adımlar Bölümü Başlığı</label><input id="ctStepsH" class="zs-input" value="${esc(ct.steps_heading || 'İlk görüşmeden yol haritasına.')}"/></div>
          ${steps.map((stItem, idx) => `
            <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-ct-step="${idx}">
              <div style="display:flex;gap:8px">
                <input class="zs-input cts-num" style="max-width:74px" value="${esc(stItem.num || String(idx + 1).padStart(2, '0'))}"/>
                <input class="zs-input cts-title" placeholder="Adım Başlığı" value="${esc(stItem.title || '')}"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-ct-step-del="${idx}">Sil</button>
              </div>
              <input class="zs-input cts-desc" placeholder="Adım açıklaması..." value="${esc(stItem.desc || '')}"/>
            </div>
          `).join('')}
        </div>
      `;
    }

    // 10. Yasal Politika Sayfaları (legal:kvkk, legal:gizlilik, legal:cerez-politikasi)
    if (key.startsWith('legal:')) {
      const lk = key.slice(6);
      const doc = S.store.legal_pages?.[lk] || { slug: lk, eyebrow: 'ŞEFFAFLIK', title: pgInfo.title, lead: '', content_html: '', sections: [] };
      const secs = Array.isArray(doc.sections) ? doc.sections : [];
      const curLegalSlug = pgInfo.slug || doc.slug || getResolvedPageSlug(lk);
      return `
        <div class="zs-card">
          <div class="zs-grid-3">
            <div class="zs-field"><label>Sayfa Başlığı (H1) *</label><input id="lpTitle" class="zs-input" value="${esc(doc.title || '')}"/></div>
            <div class="zs-field"><label>Üst Etiket</label><input id="lpEyebrow" class="zs-input" value="${esc(doc.eyebrow || 'ŞEFFAFLIK')}"/></div>
            <div class="zs-field">
              <label>Kalıcı Bağlantı (/slug/)</label>
              <div class="zs-input-group">
                <span style="display:inline-flex;align-items:center;padding:0 10px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">/</span>
                <input id="lpSlug" class="zs-input" placeholder="${esc(lk)}" value="${esc(curLegalSlug)}"/>
                <span style="display:inline-flex;align-items:center;padding:0 10px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:12px;color:var(--text-dim)">/</span>
              </div>
            </div>
          </div>
          <div class="zs-field"><label>Giriş Açıklaması (Özet)</label><textarea id="lpLead" class="zs-textarea" style="min-height:64px">${esc(doc.lead || '')}</textarea></div>
          ${renderRichEditorWidget('lpRichBody', doc.content_html || '')}
        </div>

        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Maddeli Alt Bölümler (${secs.length} Madde)</h3>
              <p class="zs-card-sub">Yukarıdaki serbest metin alanına ek olarak numaralı/başlıklı yasal maddeler tanımlayabilirsiniz.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="lpAddSecBtn">+ Madde Ekle</button>
          </div>
          ${secs.map((sc, idx) => `
            <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-lp-sec="${idx}">
              <div style="display:flex;gap:8px">
                <input class="zs-input lps-h" placeholder="Bölüm / Madde Başlığı" value="${esc(sc.heading || '')}"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-lp-del="${idx}">Sil</button>
              </div>
              <textarea class="zs-textarea lps-b" style="min-height:72px">${esc(sc.body || '')}</textarea>
            </div>
          `).join('')}
        </div>
      `;
    }

    return `<div class="zs-card">Sayfa bulunamadı.</div>`;
  }

  function renderUnifiedSinglePageEditor() {
    const allPages = buildUnifiedPagesCatalog({ includeTrash: true });
    let key = S.pages.activeKey || 'anasayfa';
    const switchablePages = allPages.filter(p => p.status !== 'trash' || p.key === key);
    const isNewCustom = (key === 'custom:__new__');

    if (isNewCustom && !S.pages.draftCustom) {
      startNewUnifiedCustomPage();
      return;
    }

    let pgInfo = isNewCustom ? {
      key: 'custom:__new__',
      title: S.pages.draftCustom.title || 'Yeni Sayfa',
      menuLabel: S.pages.draftCustom.menu_label || S.pages.draftCustom.title || 'Yeni Sayfa',
      url: '/' + (S.pages.draftCustom.slug || 'yeni-sayfa') + '/',
      group: 'custom',
      typeLabel: 'Yeni Özel Sayfa',
      stateTag: 'Yeni Sayfa',
      status: S.pages.draftCustom.status || 'published',
      deletable: false,
      duplicatable: false,
      inHeaderMenu: false,
      inFooterMenu: false,
      footerColIdx: 0,
      blockCount: (S.pages.draftCustom.sections || []).length,
      bannerImage: S.pages.draftCustom.banner_image || '',
      metaTitle: S.pages.draftCustom.seo?.meta_title || '',
      metaDesc: S.pages.draftCustom.seo?.meta_desc || '',
      focusKeyword: S.pages.draftCustom.seo?.focus_keyword || '',
      aiSummary: S.pages.draftCustom.seo?.ai_summary || '',
      robots: S.pages.draftCustom.seo?.robots || 'index, follow',
      ogTitle: S.pages.draftCustom.seo?.og_title || '',
      ogDesc: S.pages.draftCustom.seo?.og_desc || '',
      ogImage: S.pages.draftCustom.seo?.og_image || ''
    } : allPages.find(p => p.key === key);

    if (!pgInfo) {
      S.pages.activeKey = 'anasayfa';
      key = 'anasayfa';
      pgInfo = allPages[0];
    }

    const edTab = ['content', 'builder', 'seo'].includes(S.pages.editorTab) ? S.pages.editorTab : 'content';
    S.builder.sections = getUnifiedPageBuilderArrayRef(key);
    const seoObj = getUnifiedPageSeoObject(key);
    const ftrCols = S.store?.menus?.footer?.columns || [];

    // Cover/banner image support per page
    const supportsBanner = !['projeler', 'blog'].includes(key);
    const currentBanner = (() => {
      if (isNewCustom) return S.pages.draftCustom.banner_image || '';
      if (key.startsWith('custom:')) return ((S.store.custom_pages || []).find(x => x.id === key.slice(7))?.banner_image) || '';
      if (key === 'anasayfa') return S.store.home?.hero?.bg_image_1 || '';
      if (key === 'hakkimizda') return S.store.about?.banner_image || '';
      if (key === 'hizmetler') return S.store.services_page?.banner_image || '';
      if (key.startsWith('service:')) return ((S.store.services || []).find(x => x.slug === key.slice(8))?.banner_image) || '';
      if (key.startsWith('project:')) return ((S.store.projects || []).find(x => x.slug === key.slice(8))?.image) || '';
      if (key === 'iletisim') return S.store.contact?.banner_image || '';
      if (key.startsWith('legal:')) return S.store.legal_pages?.[key.slice(6)]?.banner_image || '';
      return '';
    })();

    const cpRef = isNewCustom
      ? S.pages.draftCustom
      : (key.startsWith('custom:') ? (S.store.custom_pages || []).find(x => x.id === key.slice(7)) : null);

    const globalTypo = Object.assign(getDefaultMenuTypography(), S.store?.menus?.typography || {});
    const pgTypoSrc = isNewCustom ? (S.pages.draftCustom?.page_typography || {}) : ((S.store?.page_typography || {})[key] || {});
    const pgTypo = {
      body_font: pgTypoSrc.body_font || 'inherit',
      custom_body_font: pgTypoSrc.custom_body_font || '',
      heading_font: pgTypoSrc.heading_font || 'inherit',
      custom_heading_font: pgTypoSrc.custom_heading_font || '',
      base_body_size: Number(pgTypoSrc.base_body_size || 0),
      heading_scale: Number(pgTypoSrc.heading_scale || 0),
      heading_tracking: pgTypoSrc.heading_tracking || 'inherit'
    };
    const hasCustomPgTypo = pgTypo.body_font !== 'inherit' || pgTypo.heading_font !== 'inherit' || pgTypo.base_body_size > 0 || pgTypo.heading_scale > 0 || pgTypo.heading_tracking !== 'inherit';
    const globalBodyFamLabel = globalTypo.body_font === 'custom' ? (globalTypo.custom_body_font || 'Manrope') : (globalTypo.body_font || 'Manrope');
    const bodySizeOpts = [12, 13, 13.5, 14, 14.5, 15, 15.5, 16, 16.5, 17, 18, 19, 20];
    const headScaleOpts = [80, 85, 90, 95, 100, 105, 110, 115, 120, 125, 130, 135];

    ROOT.innerHTML = `
      <!-- STICKY UNIFIED PAGE EDITOR TOP BAR -->
      <div class="zs-card" style="padding:14px 18px;margin-bottom:16px;border-color:var(--border-strong)">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-sm" id="pgBackToListBtn">← Tüm Sayfalara Dön</button>
            <select id="pgQuickSwitchSel" class="zs-select" style="width:240px;padding:6px 10px;font-weight:600">
              ${isNewCustom ? `<option value="custom:__new__" selected>+ Yeni Sayfa (Kaydedilmedi)</option>` : ''}
              ${switchablePages.map(p => `<option value="${esc(p.key)}" ${p.key === key ? 'selected' : ''}>${p.isChild ? '— ' : ''}${esc(p.title)} (${esc(p.url)})</option>`).join('')}
            </select>
            ${!isNewCustom ? `
              <a href="${esc(resolveUrl(pgInfo.url))}" target="_blank" rel="noopener" style="font-size:12px;color:var(--accent-bright);font-family:Consolas,monospace">
                ${esc(pgInfo.url)} ↗
              </a>
            ` : ''}
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-sm" id="pgTopNewPageBtn">+ Yeni Sayfa</button>
            ${!isNewCustom ? `<button type="button" class="zs-btn zs-btn-sm" id="pgTopDupPageBtn">⧉ Çoğalt</button>` : ''}
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgShareStudioBtn">📣 Sosyal Medyada Paylaş</button>
            ${!isNewCustom ? `<a href="${esc(resolveUrl(pgInfo.url) + '?canli_duzenle=1')}" target="_blank" rel="noopener" class="zs-btn zs-btn-sm zs-btn-ai">✨ Canlı Görsel Düzenle ↗</a>` : ''}
            ${!isNewCustom ? `<a href="${esc(resolveUrl(pgInfo.url))}" target="_blank" rel="noopener" class="zs-btn zs-btn-sm">Sayfayı Görüntüle ↗</a>` : ''}
            <button type="button" class="zs-btn zs-btn-primary" id="pgMainSaveBtn">✓ ${isNewCustom ? 'Sayfayı Yayınla' : 'Değişiklikleri Güncelle'}</button>
          </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid var(--border-subtle)">
          <div class="zs-tabs" style="margin-bottom:0">
            <button type="button" class="zs-tab ${edTab === 'content' ? 'active' : ''}" data-ed-tab="content">📝 Sayfa İçeriği & Alanları</button>
            <button type="button" class="zs-tab ${edTab === 'builder' ? 'active' : ''}" data-ed-tab="builder">🧩 Görsel Sürükle-Bırak Tasarımcı (${S.builder.sections.length})</button>
            <button type="button" class="zs-tab ${edTab === 'seo' ? 'active' : ''}" data-ed-tab="seo">🔍 SEO & Yapay Zeka (GEO)</button>
          </div>
          <span class="zs-pill zs-pill-cat">${esc(pgInfo.typeLabel)}</span>
        </div>
      </div>

      ${edTab === 'content' ? `
        <div class="zs-page-editor-layout">
          <div>
            ${renderUnifiedContentLeftHtml(key, pgInfo)}
          </div>

          <aside style="display:grid;gap:16px;position:sticky;top:84px">
            <!-- PUBLISH CARD -->
            <div class="zs-card" style="margin-bottom:0">
              <div class="zs-card-head" style="margin-bottom:12px">
                <h3>Yayınla & Durum</h3>
                ${pgInfo.status === 'trash'
                  ? `<span class="zs-pill" style="background:rgba(248,113,113,.15);color:#fca5a5;border:1px solid rgba(248,113,113,.32)">Çöp Kutusu</span>`
                  : `<span class="zs-pill ${pgInfo.status === 'draft' ? 'zs-pill-draft' : 'zs-pill-pub'}">${pgInfo.status === 'draft' ? 'Taslak' : 'Yayında'}</span>`
                }
              </div>
              ${key !== 'anasayfa' ? `
                <div class="zs-field" style="margin-bottom:10px">
                  <label>Sayfa Yayın Durumu</label>
                  <select id="sbStatus" class="zs-select">
                    <option value="published" ${(cpRef ? cpRef.status : pgInfo.status) === 'published' ? 'selected' : ''}>Yayında (Herkese Açık)</option>
                    <option value="draft" ${(cpRef ? cpRef.status : pgInfo.status) === 'draft' ? 'selected' : ''}>Taslak (Yalnızca Yönetici Önizler)</option>
                    ${pgInfo.status === 'trash' ? `<option value="trash" selected>Çöp Kutusu (Yayında Değil)</option>` : ''}
                  </select>
                </div>
              ` : ''}
              ${cpRef ? `
                <div style="display:grid;gap:8px;margin-bottom:12px">
                  <label class="zs-switch"><input type="checkbox" id="sbShowHeader" ${cpRef.show_page_header !== false ? 'checked' : ''}/><span>Üst Sayfa Başlık Bandını Göster</span></label>
                  <label class="zs-switch"><input type="checkbox" id="sbShowCta" ${cpRef.show_cta !== false ? 'checked' : ''}/><span>Alt Teklif Çağrı Bandını Göster</span></label>
                </div>
              ` : key === 'anasayfa' ? `
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;line-height:1.55">
                  <strong>Kalıcı Adres:</strong> <code style="color:var(--accent-bright)">/</code> (Kök Dizin)<br/>
                  Bu sayfa aktif olarak yayındadır. Yaptığınız tüm değişiklikler kaydettiğiniz anda canlı siteye yansır.
                </div>
              ` : `
                <div class="zs-field" style="margin-bottom:10px">
                  <label>Kalıcı Bağlantı (URL Uzantısı)</label>
                  <div class="zs-input-group">
                    <span style="display:inline-flex;align-items:center;padding:0 8px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:11.5px;color:var(--text-dim)">${esc(key.startsWith('service:') ? ('/' + getResolvedPageSlug('hizmetler') + '/') : (key.startsWith('project:') ? ('/' + getResolvedPageSlug('projeler') + '/') : '/'))}</span>
                    <input id="sbPageSlug" class="zs-input" style="padding:7px 9px;font-size:12.5px" value="${esc(pgInfo.slug || '')}"/>
                    <span style="display:inline-flex;align-items:center;padding:0 8px;background:rgba(6,13,17,.8);border:1px solid var(--border-subtle);border-radius:10px;font-size:11.5px;color:var(--text-dim)">/</span>
                  </div>
                </div>
                <div style="font-size:11.5px;color:var(--text-muted);margin-bottom:12px;line-height:1.5">
                  Eski bağlantılar otomatik olarak <code>301</code> ile yeni adrese yönlendirilir.
                </div>
              `}
              <button type="button" class="zs-btn zs-btn-primary" id="pgSideSaveBtn" style="width:100%">✓ ${isNewCustom ? 'Sayfayı Yayınla' : 'Sayfayı Güncelle'}</button>
              ${!isNewCustom ? `
                <button type="button" class="zs-btn zs-btn-sm" id="pgSideDupBtn" style="width:100%;margin-top:8px">⧉ Bu Sayfayı Çoğalt</button>
              ` : ''}
              ${pgInfo.status === 'trash' ? `
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pgSideRestoreBtn" style="width:100%;margin-top:8px">↩ Çöp Kutusundan Geri Yükle</button>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="pgSidePurgeBtn" style="width:100%;margin-top:8px">✕ Kalıcı Olarak Sil</button>
              ` : pgInfo.deletable ? `
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="pgSideDeleteBtn" style="width:100%;margin-top:8px">🗑 Çöp Kutusuna Taşı (Sil)</button>
              ` : ''}
            </div>

            <!-- MENU VISIBILITY CARD -->
            <div class="zs-card" style="margin-bottom:0">
              <div class="zs-card-head" style="margin-bottom:10px">
                <h3>Menü Görünürlüğü</h3>
                <button type="button" class="zs-btn zs-btn-sm" id="sbJumpMenusBtn" style="padding:3px 8px;font-size:11px">Menü Ayarları →</button>
              </div>
              <div style="display:grid;gap:10px">
                <label class="zs-switch"><input type="checkbox" id="sbInHeader" ${pgInfo.inHeaderMenu ? 'checked' : ''}/><span><strong>Üst Menüde (Header) Göster</strong></span></label>
                <label class="zs-switch"><input type="checkbox" id="sbInFooter" ${pgInfo.inFooterMenu ? 'checked' : ''}/><span><strong>Alt Menüde (Footer) Göster</strong></span></label>
                <div class="zs-field" style="margin:2px 0 0">
                  <label>Menüde Görünecek Başlık</label>
                  <input id="sbMenuLabel" class="zs-input" style="padding:7px 10px;font-size:12.5px" value="${esc(pgInfo.menuLabel || pgInfo.title)}"/>
                </div>
                ${ftrCols.length > 0 ? `
                  <div class="zs-field" style="margin:0">
                    <label>Eklenecek Footer Menü Grubu</label>
                    <select id="sbFooterCol" class="zs-select" style="padding:7px 10px;font-size:12.5px">
                      ${ftrCols.map((fc, fcIdx) => `<option value="${fcIdx}" ${pgInfo.footerColIdx === fcIdx ? 'selected' : ''}>${esc(fc.title || ('Grup ' + (fcIdx + 1)))}</option>`).join('')}
                    </select>
                  </div>
                ` : ''}
              </div>
            </div>

            <!-- PER-PAGE TYPOGRAPHY & FONT SIZE CARD -->
            <div class="zs-card" style="margin-bottom:0;border-color:${hasCustomPgTypo ? 'rgba(45,212,191,.48)' : 'var(--border-subtle)'}">
              <div class="zs-card-head" style="margin-bottom:10px">
                <h3 style="font-size:14px">🔤 Sayfa Font &amp; Yazı Boyutu</h3>
                ${hasCustomPgTypo ? `<button type="button" class="zs-btn zs-btn-sm" id="sbResetTypoBtn" style="padding:2px 7px;font-size:10.5px">Sıfırla</button>` : `<span class="zs-pill zs-pill-cat" style="font-size:10px">Genel</span>`}
              </div>
              <div style="display:grid;gap:9px">
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11.5px">Bu Sayfanın Yazı Tipi (Body)</label>
                  <select id="sbTypoBodyFont" class="zs-select" style="padding:6px 9px;font-size:12px">
                    ${renderFontSelectOptions(pgTypo.body_font, false, `🔗 Site Geneli (${globalBodyFamLabel})`)}
                  </select>
                </div>
                <div id="sbTypoCustomBodyWrap" style="display:${pgTypo.body_font === 'custom' ? 'block' : 'none'}">
                  <input id="sbTypoCustomBody" class="zs-input" style="padding:6px 9px;font-size:12px" placeholder="Özel Google Font Adı (Örn: Poppins)" value="${esc(pgTypo.custom_body_font)}"/>
                </div>
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11.5px">Başlık Yazı Tipi (H1/H2/H3)</label>
                  <select id="sbTypoHeadFont" class="zs-select" style="padding:6px 9px;font-size:12px">
                    ${renderFontSelectOptions(pgTypo.heading_font, true, '🔗 Site Genel Başlık Fontu')}
                  </select>
                </div>
                <div id="sbTypoCustomHeadWrap" style="display:${pgTypo.heading_font === 'custom' ? 'block' : 'none'}">
                  <input id="sbTypoCustomHead" class="zs-input" style="padding:6px 9px;font-size:12px" placeholder="Başlık Google Font Adı" value="${esc(pgTypo.custom_heading_font)}"/>
                </div>
                <div class="zs-grid-2" style="gap:8px">
                  <div class="zs-field" style="margin:0">
                    <label style="font-size:11px">Metin Boyutu</label>
                    <select id="sbTypoBodySize" class="zs-select" style="padding:6px 8px;font-size:12px">
                      <option value="0" ${!pgTypo.base_body_size ? 'selected' : ''}>Genel (${globalTypo.base_body_size || 14}px)</option>
                      ${bodySizeOpts.map(sz => `<option value="${sz}" ${Number(pgTypo.base_body_size) === sz ? 'selected' : ''}>${sz} px</option>`).join('')}
                    </select>
                  </div>
                  <div class="zs-field" style="margin:0">
                    <label style="font-size:11px">Başlık Ölçeği</label>
                    <select id="sbTypoHeadScale" class="zs-select" style="padding:6px 8px;font-size:12px">
                      <option value="0" ${!pgTypo.heading_scale ? 'selected' : ''}>Genel (%${globalTypo.heading_scale || 100})</option>
                      ${headScaleOpts.map(sc => `<option value="${sc}" ${Number(pgTypo.heading_scale) === sc ? 'selected' : ''}>%${sc}</option>`).join('')}
                    </select>
                  </div>
                </div>
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11px">Başlık Harf Aralığı</label>
                  <select id="sbTypoTracking" class="zs-select" style="padding:6px 8px;font-size:12px">
                    <option value="inherit" ${pgTypo.heading_tracking === 'inherit' ? 'selected' : ''}>Site Genel Ayarı (${esc(globalTypo.heading_tracking || 'tight')})</option>
                    <option value="tight" ${pgTypo.heading_tracking === 'tight' ? 'selected' : ''}>Sıkı &amp; Modern</option>
                    <option value="balanced" ${pgTypo.heading_tracking === 'balanced' ? 'selected' : ''}>Dengeli Stüdyo</option>
                    <option value="normal" ${pgTypo.heading_tracking === 'normal' ? 'selected' : ''}>Normal &amp; Ferah (0px)</option>
                  </select>
                </div>
                <button type="button" class="zs-btn zs-btn-sm" id="sbJumpGlobalFontBtn" style="width:100%;font-size:11.5px">🎨 Tüm Site Font Stüdyosunu Aç →</button>
              </div>
            </div>

            <!-- COVER IMAGE CARD -->
            ${supportsBanner ? `
              <div class="zs-card" style="margin-bottom:0">
                <h3 style="margin:0 0 10px;font-size:14.5px">${key.startsWith('project:') ? 'Özel Proje Görseli' : 'Sayfa Kapak Görseli'}</h3>
                ${currentBanner ? `
                  <div style="height:110px;border-radius:10px;overflow:hidden;border:1px solid var(--border-subtle);margin-bottom:10px;background:#040a0d">
                    <img src="${esc(resolveUrl(currentBanner))}" alt="" style="width:100%;height:100%;object-fit:cover"/>
                  </div>
                ` : ''}
                <div class="zs-input-group">
                  <input id="sbBanner" class="zs-input" style="font-size:12px" placeholder="/uploads/... veya /images/..." value="${esc(currentBanner)}"/>
                  <button type="button" class="zs-btn zs-btn-sm" id="sbPickBannerBtn">Seç</button>
                </div>
              </div>
            ` : ''}

            <!-- QUICK SEO SUMMARY CARD -->
            <div class="zs-card" style="margin-bottom:0">
              <div class="zs-card-head" style="margin-bottom:8px">
                <h3 style="font-size:14px">Google & Yapay Zeka SEO</h3>
              </div>
              <div style="padding:10px 12px;border-radius:10px;background:rgba(4,10,14,.75);border:1px solid var(--border-subtle);margin-bottom:10px">
                <div style="font-size:11px;color:#34d399;margin-bottom:2px">zeynart.com${esc(pgInfo.url)}</div>
                <div style="font-size:13px;font-weight:700;color:#93c5fd;margin-bottom:3px">${esc(seoObj.meta_title || pgInfo.metaTitle || (pgInfo.title + ' | ZEYNART'))}</div>
                <div style="font-size:11.5px;color:var(--text-muted);line-height:1.45">${esc((seoObj.meta_desc || pgInfo.metaDesc || 'Meta açıklama tanımlamak için SEO sekmesini açın.').slice(0, 120))}</div>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" style="width:100%" data-ed-tab="seo">🔍 SEO & GEO Ayarlarını Düzenle →</button>
              <button type="button" class="zs-btn zs-btn-sm" id="pgSideShareBtn" style="width:100%;margin-top:8px">📣 Sosyal Medyada Paylaş &amp; Kart Önizle</button>
            </div>
          </aside>
        </div>
      ` : ''}

      ${edTab === 'builder' ? `
        ${renderBuilderStageMarkup(S.builder.sections)}
      ` : ''}

      ${edTab === 'seo' ? `
        <div class="zs-grid-2">
          <div class="zs-card">
            <div class="zs-card-head">
              <div>
                <h3>Arama Motoru (Google) & Sosyal Paylaşım Ayarları</h3>
                <p class="zs-card-sub">Bu sayfanın arama sonuçlarındaki başlığını, açıklamasını ve indeksleme kurallarını belirleyin.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="useoAiGenBtn">✨ Otomatik SEO Üret</button>
            </div>

            <div class="zs-field">
              <label>SEO Meta Başlık (Önerilen: 45–60 karakter)</label>
              <input id="useoMetaTitle" class="zs-input" value="${esc(seoObj.meta_title || pgInfo.metaTitle || '')}"/>
            </div>
            <div class="zs-field">
              <label>SEO Meta Açıklama (Önerilen: 120–160 karakter)</label>
              <textarea id="useoMetaDesc" class="zs-textarea" style="min-height:84px">${esc(seoObj.meta_desc || pgInfo.metaDesc || '')}</textarea>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Odak Anahtar Kelime</label>
                <input id="useoKw" class="zs-input" placeholder="örn: kurumsal web tasarım" value="${esc(seoObj.focus_keyword || pgInfo.focusKeyword || '')}"/>
              </div>
              <div class="zs-field">
                <label>Arama Motoru İndeksleme (Robots)</label>
                <select id="useoRobots" class="zs-select">
                  <option value="index, follow" ${(seoObj.robots || 'index, follow') === 'index, follow' ? 'selected' : ''}>index, follow (İndeksle — Önerilen)</option>
                  <option value="noindex, follow" ${seoObj.robots === 'noindex, follow' ? 'selected' : ''}>noindex, follow (Aramada Gizle)</option>
                </select>
              </div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Sosyal Medya Başlığı (og:title — İsteğe Bağlı)</label>
                <input id="useoOgTitle" class="zs-input" placeholder="Boşsa SEO/Sayfa başlığı kullanılır" value="${esc(seoObj.og_title || pgInfo.ogTitle || '')}"/>
              </div>
              <div class="zs-field">
                <label>Özel Sosyal Paylaşım Görseli (1200×630 Otomatik JPG)</label>
                <div class="zs-input-group">
                  <input id="useoOgImg" class="zs-input" placeholder="Boşsa sayfa/varsayılan kapak kullanılır" value="${esc(seoObj.og_image || pgInfo.ogImage || '')}"/>
                  <button type="button" class="zs-btn zs-btn-sm" id="useoPickOgBtn">Seç</button>
                </div>
              </div>
            </div>
            <div class="zs-field" style="margin-bottom:0">
              <label>Sosyal Medya Açıklaması (og:description — İsteğe Bağlı)</label>
              <textarea id="useoOgDesc" class="zs-textarea" style="min-height:66px" placeholder="Boş bırakılırsa SEO Meta Açıklama link önizlemesinde kullanılır">${esc(seoObj.og_desc || pgInfo.ogDesc || '')}</textarea>
            </div>
          </div>

          <div style="display:grid;gap:18px;align-content:start">
            <div class="zs-card" style="margin-bottom:0">
              <h3 style="margin:0 0 12px">Canlı Google Arama Önizlemesi</h3>
              <div style="padding:16px 18px;border-radius:14px;background:#040b0e;border:1px solid var(--border-subtle)">
                <div style="font-size:12px;color:#34d399;margin-bottom:4px">https://zeynart.com${esc(pgInfo.url)}</div>
                <div id="useoPreviewTitle" style="font-size:17px;font-weight:700;color:#93c5fd;margin-bottom:6px;line-height:1.35">${esc(seoObj.meta_title || pgInfo.metaTitle || (pgInfo.title + ' | ZEYNART'))}</div>
                <div id="useoPreviewDesc" style="font-size:13px;color:#9fb6bb;line-height:1.55">${esc(seoObj.meta_desc || pgInfo.metaDesc || 'Arama motorlarında görünecek sayfa açıklaması burada önizlenir.')}</div>
              </div>
            </div>

            <div class="zs-card" style="margin-bottom:0">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
                <h3 style="margin:0;font-size:15px">📣 Link Yapıştırıldığında Sosyal Medya Kartı</h3>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="useoOpenShareBtn">🚀 Hemen Paylaş →</button>
              </div>
              <div class="zs-og-preview-card">
                <div class="zs-og-preview-media">
                  <img id="useoOgPreviewImg" src="${esc(resolveUrl(seoObj.og_image || pgInfo.ogImage || currentBanner || '/images/banners/services.webp'))}" alt=""/>
                  <span class="zs-og-preview-badge">WhatsApp · LinkedIn · FB · X</span>
                </div>
                <div class="zs-og-preview-body">
                  <div class="zs-og-preview-domain">zeynart.com</div>
                  <div class="zs-og-preview-title" id="useoOgPreviewTitle">${esc(seoObj.og_title || seoObj.meta_title || pgInfo.metaTitle || (pgInfo.title + ' | ZEYNART'))}</div>
                  <div class="zs-og-preview-desc" id="useoOgPreviewDesc">${esc(seoObj.og_desc || seoObj.meta_desc || pgInfo.metaDesc || 'Sayfa linki WhatsApp, LinkedIn, Facebook veya X platformuna yapıştırıldığında bu görsel ve açıklama çıkar.')}</div>
                </div>
              </div>
            </div>

            <div class="zs-card" style="margin-bottom:0">
              <h3 style="margin:0 0 8px">✦ Yapay Zeka Özeti (GEO — ChatGPT, Gemini, Perplexity)</h3>
              <p class="zs-card-sub" style="margin-bottom:12px">Yapay zeka arama motorlarının ve <code>/llms-full.txt</code> manifestosunun bu sayfayı nasıl özetleyeceğini belirleyin.</p>
              <div class="zs-field" style="margin-bottom:0">
                <textarea id="useoAiSummary" class="zs-textarea" style="min-height:115px" placeholder="Bu sayfanın sunduğu hizmet, kapsam ve uzmanlık özetini net cümlelerle yazın...">${esc(seoObj.ai_summary || pgInfo.aiSummary || '')}</textarea>
              </div>
            </div>
          </div>
        </div>
      ` : ''}
    `;

    // Helper: read current rich editor HTML safely
    const readRichHtml = baseId => {
      const srcEl = document.getElementById(baseId + 'Source');
      if (srcEl) return srcEl.value;
      const edEl = document.getElementById(baseId);
      return edEl ? edEl.innerHTML : '';
    };

    // Sync active tab inputs back to S.store / S.pages.draftCustom
    const syncActivePageFromDom = () => {
      if (edTab === 'content') {
        const sbBannerVal = document.getElementById('sbBanner')?.value.trim();
        S.pages.pendingUrlRenames = S.pages.pendingUrlRenames || [];
        const pushUrlRename = (fromU, toU) => {
          if (fromU && toU && fromU !== toU) {
            if (!S.pages.pendingUrlRenames.some(r => r.from === fromU && r.to === toU)) {
              S.pages.pendingUrlRenames.push({ from: fromU, to: toU });
            }
          }
        };

        if (['hakkimizda', 'hizmetler', 'projeler', 'blog', 'iletisim'].includes(key)) {
          const coreTitleEl = document.getElementById('corePageTitleInp');
          if (coreTitleEl) {
            const ctVal = coreTitleEl.value.trim();
            if (ctVal) {
              S.store.page_titles = S.store.page_titles || {};
              S.store.page_titles[key] = ctVal;
            }
          }
          const coreSlugEl = document.getElementById('corePageSlugInp') || document.getElementById('sbPageSlug');
          if (coreSlugEl) {
            const defS = DEFAULT_PAGE_SLUGS[key] || key;
            const nextCoreSlug = slugifyClient(coreSlugEl.value.trim() || defS) || defS;
            S.store.page_slugs = S.store.page_slugs || {};
            S.store.page_slugs[key] = nextCoreSlug;
            pushUrlRename(pgInfo.url, '/' + nextCoreSlug + '/');
          }
        }

        if (isNewCustom || key.startsWith('custom:')) {
          const targetCp = isNewCustom
            ? S.pages.draftCustom
            : (S.store.custom_pages || []).find(x => x.id === key.slice(7));
          if (targetCp && document.getElementById('cpTitle')) {
            const prevCpUrl = !isNewCustom && targetCp.slug ? ('/' + targetCp.slug + '/') : '';
            targetCp.title = document.getElementById('cpTitle').value.trim();
            targetCp.slug = slugifyClient(document.getElementById('cpSlug').value.trim() || targetCp.title || 'yeni-sayfa');
            if (prevCpUrl) pushUrlRename(prevCpUrl, '/' + targetCp.slug + '/');
            targetCp.eyebrow = document.getElementById('cpEyebrow').value.trim();
            targetCp.excerpt = document.getElementById('cpExcerpt').value.trim();
            targetCp.content_html = readRichHtml('cpRichBody');
            if (document.getElementById('sbStatus')) targetCp.status = document.getElementById('sbStatus').value;
            if (document.getElementById('sbShowHeader')) targetCp.show_page_header = document.getElementById('sbShowHeader').checked;
            if (document.getElementById('sbShowCta')) targetCp.show_cta = document.getElementById('sbShowCta').checked;
            if (sbBannerVal !== undefined) targetCp.banner_image = sbBannerVal;
            targetCp.faqs = Array.from(ROOT.querySelectorAll('[data-cp-faq]')).map(r => ({
              q: r.querySelector('.cpfq-q').value.trim(),
              a: r.querySelector('.cpfq-a').value.trim()
            })).filter(f => f.q || f.a);
          }
        } else if (key === 'anasayfa') {
          const home = S.store.home || {};
          home.hero = home.hero || { slides: [] };
          home.intro = home.intro || {};
          home.brand_scene = home.brand_scene || {};
          home.process = home.process || { steps: [] };
          if (sbBannerVal !== undefined && !document.getElementById('hhBg1')) {
            home.hero.bg_image_1 = sbBannerVal;
          }
          ROOT.querySelectorAll('[data-hvis]').forEach(chk => {
            home.section_visibility = home.section_visibility || {};
            home.section_visibility[chk.getAttribute('data-hvis')] = chk.checked;
          });
          if (document.getElementById('hhBg1')) {
            home.hero.bg_image_1 = document.getElementById('hhBg1').value.trim();
            home.hero.bg_image_2 = document.getElementById('hhBg2').value.trim();
            home.hero.primary_btn_label = document.getElementById('hhBtn1L').value.trim();
            home.hero.primary_btn_url = document.getElementById('hhBtn1U').value.trim();
            home.hero.secondary_btn_label = document.getElementById('hhBtn2L').value.trim();
            home.hero.domains_bar = document.getElementById('hhDomains').value.trim();
            home.hero.slides = Array.from(ROOT.querySelectorAll('[data-hslide]')).map(card => ({
              label: card.querySelector('.hsl-lbl').value.trim(),
              lines: card.querySelector('.hsl-lines').value.split('\n').map(s => s.trim()).filter(Boolean),
              accent: card.querySelector('.hsl-accent').value.trim(),
              copy: card.querySelector('.hsl-copy').value.trim(),
              note: card.querySelector('.hsl-note').value.split('\n').map(s => s.trim()).filter(Boolean)
            }));
          }
          if (document.getElementById('hiEyebrow')) {
            home.intro.eyebrow = document.getElementById('hiEyebrow').value.trim();
            home.intro.year = document.getElementById('hiYear').value.trim();
            home.intro.tagline = document.getElementById('hiTagline').value.trim();
            home.intro.lead = document.getElementById('hiLead').value.trim();
            home.intro.heading_before = document.getElementById('hiBefore').value.trim();
            home.intro.heading_em = document.getElementById('hiEm').value.trim();
            home.intro.heading_after = document.getElementById('hiAfter').value.trim();
            home.intro.pillars = document.getElementById('hiPillars').value.trim();
            home.intro.link_label = document.getElementById('hiLinkLbl').value.trim();
            home.brand_scene.image = document.getElementById('hbsImg').value.trim();
            home.brand_scene.eyebrow = document.getElementById('hbsEyebrow').value.trim();
            home.brand_scene.top_note = document.getElementById('hbsTopNote').value.trim();
            home.brand_scene.heading_line1 = document.getElementById('hbsH1').value.trim();
            home.brand_scene.heading_line2 = document.getElementById('hbsH2').value.trim();
            home.brand_scene.heading_em = document.getElementById('hbsEm').value.trim();
            home.brand_scene.copy_line1 = document.getElementById('hbsC1').value.trim();
            home.brand_scene.copy_line2 = document.getElementById('hbsC2').value.trim();
          }
          if (document.getElementById('hpEyebrow')) {
            home.process.eyebrow = document.getElementById('hpEyebrow').value.trim();
            home.process.heading_line1 = document.getElementById('hpH1').value.trim();
            home.process.heading_muted = document.getElementById('hpMuted').value.trim();
            if (document.getElementById('hpIntro')) home.process.intro = document.getElementById('hpIntro').value.trim();
            if (document.getElementById('hpFLeft')) home.process.footer_left = document.getElementById('hpFLeft').value.trim();
            if (document.getElementById('hpFRight')) home.process.footer_right = document.getElementById('hpFRight').value.trim();
            home.process.steps = Array.from(ROOT.querySelectorAll('[data-hpstep]')).map((card, i) => ({
              num: card.querySelector('.hps-num').value.trim(),
              icon: home.process.steps[i]?.icon || 'sparkles',
              title: card.querySelector('.hps-title').value.trim(),
              desc: card.querySelector('.hps-desc').value.trim(),
              output: card.querySelector('.hps-out').value.trim()
            }));
          }
          S.store.home = home;
        } else if (key === 'hakkimizda') {
          const ab = S.store.about || { clients: [], reviews: [], story_paragraphs: [], software_items: [], values: [] };
          if (sbBannerVal !== undefined) ab.banner_image = sbBannerVal;
          if (document.getElementById('abEyebrow')) {
            ab.eyebrow = document.getElementById('abEyebrow').value.trim();
            ab.heading_line1 = document.getElementById('abH1').value.trim();
            ab.heading_accent = document.getElementById('abAcc').value.trim();
            ab.lead = document.getElementById('abLead').value.trim();
            if (document.getElementById('abSigLeft')) ab.signature_left = document.getElementById('abSigLeft').value.trim();
            if (document.getElementById('abSigRight')) ab.signature_right = document.getElementById('abSigRight').value.trim();
            if (document.getElementById('abBrandImg')) ab.brand_hero_image = document.getElementById('abBrandImg').value.trim();
            if (document.getElementById('abBrandEye')) ab.brand_hero_eyebrow = document.getElementById('abBrandEye').value.trim();
            if (document.getElementById('abBrandH1')) ab.brand_hero_h2_1 = document.getElementById('abBrandH1').value.trim();
            if (document.getElementById('abBrandSpan')) ab.brand_hero_h2_span = document.getElementById('abBrandSpan').value.trim();
            if (document.getElementById('abBrandCopy')) ab.brand_hero_copy = document.getElementById('abBrandCopy').value.trim();
            if (document.getElementById('abStoryEye')) ab.story_eyebrow = document.getElementById('abStoryEye').value.trim();
            if (document.getElementById('abStoryH1')) ab.story_h2_1 = document.getElementById('abStoryH1').value.trim();
            if (document.getElementById('abStoryMuted')) ab.story_h2_muted = document.getElementById('abStoryMuted').value.trim();
            if (document.getElementById('abStoryCap')) ab.story_caption = document.getElementById('abStoryCap').value.trim();
            ab.story_paragraphs = document.getElementById('abStoryP').value.split(/\n\s*\n/).map(s => s.trim()).filter(Boolean);
            ab.story_image = document.getElementById('abStoryImg').value.trim();
            if (document.getElementById('abAddValBtn')) {
              ab.values = Array.from(ROOT.querySelectorAll('[data-ab-val]')).map(el => ({
                icon: el.querySelector('.abv-icon')?.value || 'compass',
                title: el.querySelector('.abv-title').value.trim(),
                desc: el.querySelector('.abv-desc').value.trim()
              })).filter(v => v.title || v.desc);
            }
            if (document.getElementById('abVisEye')) ab.vision_eyebrow = document.getElementById('abVisEye').value.trim();
            if (document.getElementById('abVisH1')) ab.vision_h2_1 = document.getElementById('abVisH1').value.trim();
            if (document.getElementById('abVisSpan')) ab.vision_h2_span = document.getElementById('abVisSpan').value.trim();
            if (document.getElementById('abVisCopy')) ab.vision_copy = document.getElementById('abVisCopy').value.trim();
            ab.vision_image = document.getElementById('abVisImg').value.trim();
            if (document.getElementById('abVisCap')) ab.vision_caption = document.getElementById('abVisCap').value.trim();
            if (document.getElementById('abSoftEye')) ab.software_eyebrow = document.getElementById('abSoftEye').value.trim();
            if (document.getElementById('abSoftH2')) ab.software_h2 = document.getElementById('abSoftH2').value.trim();
            ab.software_items = document.getElementById('abSoftItems').value.split('\n').map(s => s.trim()).filter(Boolean);
          }
          if (document.getElementById('abClEye')) {
            ab.clients_eyebrow = document.getElementById('abClEye').value.trim();
            ab.clients_h2_1 = document.getElementById('abClH1').value.trim();
            ab.clients_h2_accent = document.getElementById('abClAcc').value.trim();
            ab.clients_lead = document.getElementById('abClLead').value.trim();
          }
          ab.clients = Array.isArray(ab.clients) ? ab.clients : [];
          ROOT.querySelectorAll('[data-cl-idx]').forEach(el => {
            const i = Number(el.getAttribute('data-cl-idx'));
            const nm = el.querySelector('.cl-name').value.trim();
            const prevNew = Boolean(ab.clients[i]?._isNew);
            ab.clients[i] = {
              id: ab.clients[i]?.id || ('client-' + (i + 1)),
              name: nm,
              alt: nm,
              url: el.querySelector('.cl-url').value.trim(),
              image: el.querySelector('.cl-img').value.trim()
            };
            if (prevNew) ab.clients[i]._isNew = true;
          });
          if (ROOT.querySelectorAll('[data-rv-idx]').length || S.pages.subTab === 'reviews') {
            if (document.getElementById('abAddRevBtn')) {
              ab.reviews = Array.from(ROOT.querySelectorAll('[data-rv-idx]')).map(el => ({
                author: el.querySelector('.rv-author').value.trim(),
                date: el.querySelector('.rv-date').value.trim(),
                text: el.querySelector('.rv-text').value.trim()
              }));
            }
          }
          S.store.about = ab;
        } else if (key === 'hizmetler') {
          const sp = S.store.services_page || {};
          if (document.getElementById('spEyebrow')) {
            sp.eyebrow = document.getElementById('spEyebrow').value.trim();
            sp.heading_line1 = document.getElementById('spH1').value.trim();
            sp.heading_muted = document.getElementById('spMuted').value.trim();
            sp.lead = document.getElementById('spLead').value.trim();
          }
          if (document.getElementById('spProcEye')) {
            S.store.home = S.store.home || {};
            const proc = S.store.home.process || { steps: [] };
            proc.eyebrow = document.getElementById('spProcEye').value.trim();
            proc.heading_line1 = document.getElementById('spProcH1').value.trim();
            proc.heading_muted = document.getElementById('spProcMuted').value.trim();
            proc.intro = document.getElementById('spProcIntro').value.trim();
            proc.footer_left = document.getElementById('spProcFLeft').value.trim();
            proc.footer_right = document.getElementById('spProcFRight').value.trim();
            proc.steps = Array.from(ROOT.querySelectorAll('[data-sp-pstep]')).map((card, i) => ({
              num: card.querySelector('.spps-num').value.trim(),
              icon: proc.steps?.[i]?.icon || 'sparkles',
              title: card.querySelector('.spps-title').value.trim(),
              desc: card.querySelector('.spps-desc').value.trim(),
              output: card.querySelector('.spps-out').value.trim()
            }));
            S.store.home.process = proc;
          }
          if (sbBannerVal !== undefined) sp.banner_image = sbBannerVal;
          S.store.services_page = sp;
        } else if (key.startsWith('service:')) {
          const srv = (S.store.services || []).find(s => s.slug === key.slice(8));
          if (srv && document.getElementById('svTitle')) {
            const prevUrl = '/' + getResolvedPageSlug('hizmetler') + '/' + srv.slug + '/';
            srv.title = document.getElementById('svTitle').value.trim();
            const svSlugRaw = document.getElementById('svSlug')?.value.trim() || document.getElementById('sbPageSlug')?.value.trim() || srv.title;
            srv.slug = slugifyClient(svSlugRaw) || srv.slug;
            srv.layout_type = document.getElementById('svLayout').value;
            srv.eyebrow = document.getElementById('svEyebrow').value.trim();
            srv.short = document.getElementById('svShort').value.trim();
            srv.card_tag = document.getElementById('svCardTag').value.trim();
            srv.headline = document.getElementById('svHeadline').value.trim();
            srv.description = document.getElementById('svDesc').value.trim();
            srv.deliverables = document.getElementById('svDeliv').value.split('\n').map(s => s.trim()).filter(Boolean);
            srv.stack = document.getElementById('svStack').value.split(',').map(s => s.trim()).filter(Boolean);
            srv.approach_copy = document.getElementById('svApproach').value.trim();
            if (document.getElementById('svDelivEye')) {
              srv.deliv_eyebrow = document.getElementById('svDelivEye').value.trim();
              srv.deliv_h2_1 = document.getElementById('svDelivH1').value.trim();
              srv.deliv_h2_muted = document.getElementById('svDelivMuted').value.trim();
              srv.approach_eyebrow = document.getElementById('svAppEye').value.trim();
              srv.approach_h2_1 = document.getElementById('svAppH1').value.trim();
              srv.approach_h2_muted = document.getElementById('svAppMuted').value.trim();
            }
            if (sbBannerVal !== undefined) srv.banner_image = sbBannerVal;
            { const g = collectRecordGalleryFromDom(); if (g !== null) srv.gallery = g; }
            if (srv.layout_type === 'graphic' && document.getElementById('gdShowImg')) {
              srv.graphic_data = srv.graphic_data || {};
              srv.graphic_data.showcase_image = document.getElementById('gdShowImg').value.trim();
              srv.graphic_data.emblem_image = document.getElementById('gdEmbImg').value.trim();
              srv.graphic_data.showcase_title = document.getElementById('gdShowTitle').value.trim();
              srv.graphic_data.palette = document.getElementById('gdPalette').value.split(',').map(s => s.trim()).filter(Boolean);
              srv.graphic_data.showcase_copy = document.getElementById('gdShowCopy').value.trim();
              srv.graphic_data.showcase_sub = document.getElementById('gdShowSub').value.trim();
              srv.graphic_data.offerings = Array.from(ROOT.querySelectorAll('[data-gd-off]')).map(el => ({
                num: el.querySelector('.gdo-num').value.trim(),
                icon: el.querySelector('.gdo-icon')?.value || 'pen-tool',
                title: el.querySelector('.gdo-title').value.trim(),
                desc: el.querySelector('.gdo-desc').value.trim(),
                items: el.querySelector('.gdo-items').value.split('\n').map(s => s.trim()).filter(Boolean)
              })).filter(x => x.title || x.desc);
              srv.graphic_data.process = Array.from(ROOT.querySelectorAll('[data-gd-proc]')).map(el => ({
                num: el.querySelector('.gdp-num').value.trim(),
                title: el.querySelector('.gdp-title').value.trim(),
                desc: el.querySelector('.gdp-desc').value.trim()
              })).filter(x => x.title || x.desc);
            }
            if (srv.layout_type === 'hosting') {
              srv.hosting_data = srv.hosting_data || { packages: [], solutions: [], benefits: [] };
              if (document.getElementById('hdOvEye')) {
                srv.hosting_data.overview_eyebrow = document.getElementById('hdOvEye').value.trim();
                srv.hosting_data.overview_h2_1 = document.getElementById('hdOvH1').value.trim();
                srv.hosting_data.overview_h2_accent = document.getElementById('hdOvAcc').value.trim();
                srv.hosting_data.overview_copy = document.getElementById('hdOvCopy').value.trim();
              }
              if (document.getElementById('svAddHdSolBtn')) {
                srv.hosting_data.solutions = Array.from(ROOT.querySelectorAll('[data-hd-sol]')).map(el => ({
                  num: el.querySelector('.hds-num').value.trim(),
                  title: el.querySelector('.hds-title').value.trim(),
                  desc: el.querySelector('.hds-desc').value.trim()
                })).filter(x => x.title || x.desc);
              }
              const pkgEls = ROOT.querySelectorAll('[data-hpkg]');
              if (pkgEls.length || document.getElementById('svAddHostPkg')) {
                srv.hosting_data.packages = Array.from(pkgEls).map(el => {
                  const nm = el.querySelector('.hpk-name').value.trim();
                  return {
                    name: nm,
                    eyebrow: nm.toUpperCase(),
                    disk: el.querySelector('.hpk-disk').value.trim(),
                    disk_label: el.querySelector('.hpk-dlbl')?.value.trim() || 'Disk alanı',
                    featured: el.querySelector('.hpk-pop').checked,
                    features: el.querySelector('.hpk-feat').value.split('\n').map(s => s.trim()).filter(Boolean)
                  };
                });
              }
              if (document.getElementById('svAddHdBenBtn')) {
                srv.hosting_data.benefits = Array.from(ROOT.querySelectorAll('[data-hd-ben]')).map(el => ({
                  title: el.querySelector('.hdb-title').value.trim(),
                  desc: el.querySelector('.hdb-desc').value.trim()
                })).filter(x => x.title || x.desc);
              }
            }
            srv.faqs = Array.from(ROOT.querySelectorAll('[data-sv-faq]')).map(r => ({
              q: r.querySelector('.svfq-q').value.trim(),
              a: r.querySelector('.svfq-a').value.trim()
            })).filter(f => f.q || f.a);
            const nextSrvUrl = '/' + getResolvedPageSlug('hizmetler') + '/' + srv.slug + '/';
            if (nextSrvUrl !== prevUrl) {
              setPageMenuPlacement(prevUrl, srv.title, false, false, 0);
              pushUrlRename(prevUrl, nextSrvUrl);
            }
            S.pages.activeKey = 'service:' + srv.slug;
          }
        } else if (key === 'projeler') {
          const pp = S.store.projects_page || {};
          if (document.getElementById('ppEyebrow')) {
            pp.eyebrow = document.getElementById('ppEyebrow').value.trim();
            pp.heading_line1 = document.getElementById('ppH1').value.trim();
            pp.heading_muted = document.getElementById('ppMuted').value.trim();
            pp.lead = document.getElementById('ppLead').value.trim();
            pp.disclaimer = document.getElementById('ppDisclaimer').value.trim();
          }
          S.store.projects_page = pp;
        } else if (key.startsWith('project:')) {
          const prj = (S.store.projects || []).find(p => p.slug === key.slice(8));
          if (prj && document.getElementById('pjName')) {
            const prevUrl = '/' + getResolvedPageSlug('projeler') + '/' + prj.slug + '/';
            prj.name = document.getElementById('pjName').value.trim();
            const pjSlugRaw = document.getElementById('pjSlug')?.value.trim() || document.getElementById('sbPageSlug')?.value.trim() || prj.name;
            prj.slug = slugifyClient(pjSlugRaw) || prj.slug;
            prj.year = document.getElementById('pjYear').value.trim();
            { const g = collectRecordGalleryFromDom(); if (g !== null) prj.gallery = g; }
            prj.sector = document.getElementById('pjSector').value.trim();
            if (document.getElementById('pjEyebrow')) prj.eyebrow = document.getElementById('pjEyebrow').value.trim();
            prj.scope = document.getElementById('pjScope').value.trim();
            prj.visual_type = document.getElementById('pjVisType').value;
            if (document.getElementById('pjWorkType')) prj.work_type = document.getElementById('pjWorkType').value.trim();
            if (document.getElementById('pjRelSrv')) prj.related_service_url = document.getElementById('pjRelSrv').value.trim();
            if (document.getElementById('pjLiveUrl')) prj.live_url = document.getElementById('pjLiveUrl').value.trim();
            if (document.getElementById('pjBaEnabled')) {
              prj.before_after = {
                enabled: Boolean(document.getElementById('pjBaEnabled').checked),
                before_label: document.getElementById('pjBaBeforeLbl')?.value.trim() || 'ÖNCESİ (Eski Mimari)',
                after_label: document.getElementById('pjBaAfterLbl')?.value.trim() || 'SONRASI (Zeynart %100 Performans)',
                before_image: document.getElementById('pjBaBeforeImg')?.value.trim() || '',
                after_image: document.getElementById('pjBaAfterImg')?.value.trim() || ''
              };
            }
            const metricRows = Array.from(ROOT.querySelectorAll('[data-pj-metric-row]'));
            if (metricRows.length) {
              prj.metrics = metricRows.map(r => ({
                value: r.querySelector('.pjm-val')?.value.trim() || '',
                label: r.querySelector('.pjm-lbl')?.value.trim() || ''
              })).filter(x => x.value || x.label);
            }
            prj.headline = document.getElementById('pjHeadline').value.trim();
            prj.summary = document.getElementById('pjSummary').value.trim();
            prj.problem = document.getElementById('pjProb').value.trim();
            prj.approach = document.getElementById('pjApp').value.trim();
            prj.solution = document.getElementById('pjSol').value.trim();
            prj.result = document.getElementById('pjRes').value.trim();
            prj.stack = document.getElementById('pjStack').value.split(',').map(s => s.trim()).filter(Boolean);
            if (document.getElementById('pjIfGreeting')) prj.interface_greeting = document.getElementById('pjIfGreeting').value.trim();
            if (document.getElementById('pjIfBlocks')) prj.interface_blocks = document.getElementById('pjIfBlocks').value.split(',').map(s => s.trim()).filter(Boolean);
            if (document.getElementById('pjIfFooter')) prj.interface_footer = document.getElementById('pjIfFooter').value.trim();
            if (sbBannerVal !== undefined) prj.image = sbBannerVal;
            const nextPrjUrl = '/' + getResolvedPageSlug('projeler') + '/' + prj.slug + '/';
            if (nextPrjUrl !== prevUrl) {
              setPageMenuPlacement(prevUrl, prj.name, false, false, 0);
              pushUrlRename(prevUrl, nextPrjUrl);
            }
            S.pages.activeKey = 'project:' + prj.slug;
          }
        } else if (key === 'blog') {
          const bs = S.store.blog_settings || {};
          if (document.getElementById('bsHeading')) {
            const newBlogHeading = document.getElementById('corePageTitleInp')?.value.trim() || document.getElementById('bsHeading').value.trim() || 'Blog';
            const eyebrowVal = document.getElementById('bsEyebrow').value.trim();
            const leadVal = document.getElementById('bsSubheading').value.trim();
            const parsedCats = document.getElementById('bsCats').value.split(',').map(s => s.trim().toUpperCase()).filter(Boolean);
            bs.heading = newBlogHeading;
            bs.eyebrow = eyebrowVal;
            bs.kicker_left = eyebrowVal;
            if (document.getElementById('bsKickerRight')) bs.kicker_right = document.getElementById('bsKickerRight').value.trim();
            if (document.getElementById('bsHeroH1')) bs.heading_line1 = document.getElementById('bsHeroH1').value.trim();
            if (document.getElementById('bsHeroSpan')) bs.heading_span = document.getElementById('bsHeroSpan').value.trim();
            if (document.getElementById('bsBadgeRight')) bs.badge_right = document.getElementById('bsBadgeRight').value.trim();
            bs.per_page = Number(document.getElementById('bsPerPage').value || 24);
            bs.subheading = leadVal;
            bs.lead = leadVal;
            bs.categories_order = parsedCats;
            bs.categories = ['TÜMÜ', ...parsedCats.filter(c => c !== 'TÜMÜ')];
            if (document.getElementById('bsShowShareBar')) {
              bs.show_share_bar = Boolean(document.getElementById('bsShowShareBar').checked);
            }
            if (document.getElementById('bsAudioPlayer')) {
              bs.audio_player_enabled = Boolean(document.getElementById('bsAudioPlayer').checked);
            }
            S.store.page_titles = S.store.page_titles || {};
            S.store.page_titles.blog = newBlogHeading;
          }
          S.store.blog_settings = bs;
        } else if (key === 'iletisim') {
          const ct = S.store.contact || {};
          S.store.site = S.store.site || {};
          S.store.seo = S.store.seo || {};
          if (document.getElementById('ctEyebrow')) {
            ct.eyebrow = document.getElementById('ctEyebrow').value.trim();
            ct.heading_line1 = document.getElementById('ctH1').value.trim();
            ct.heading_muted = document.getElementById('ctMuted').value.trim();
            ct.lead = document.getElementById('ctLead').value.trim();
            ct.sidebar_eyebrow = document.getElementById('ctSideEye').value.trim();
            ct.sidebar_heading = document.getElementById('ctSideH').value.trim();
            ct.steps_heading = document.getElementById('ctStepsH').value.trim();
            ct.sidebar_copy = document.getElementById('ctSideCopy').value.trim();
            ct.form_eyebrow = document.getElementById('ctFormEye').value.trim();
            ct.form_heading = document.getElementById('ctFormH').value.trim();
            ct.submit_label = document.getElementById('ctSubmitLbl').value.trim();
            ct.form_copy = document.getElementById('ctFormCopy').value.trim();
            if (document.getElementById('ctFormHint')) ct.form_hint = document.getElementById('ctFormHint').value.trim();
            if (document.getElementById('ctLblName')) ct.lbl_name = document.getElementById('ctLblName').value.trim();
            if (document.getElementById('ctPhName')) ct.ph_name = document.getElementById('ctPhName').value.trim();
            if (document.getElementById('ctLblPhone')) ct.lbl_phone = document.getElementById('ctLblPhone').value.trim();
            if (document.getElementById('ctPhPhone')) ct.ph_phone = document.getElementById('ctPhPhone').value.trim();
            if (document.getElementById('ctReqPhone')) ct.require_phone = Boolean(document.getElementById('ctReqPhone').checked);
            if (document.getElementById('ctLblEmail')) ct.lbl_email = document.getElementById('ctLblEmail').value.trim();
            if (document.getElementById('ctPhEmail')) ct.ph_email = document.getElementById('ctPhEmail').value.trim();
            if (document.getElementById('ctLblComp')) ct.lbl_company = document.getElementById('ctLblComp').value.trim();
            if (document.getElementById('ctPhComp')) ct.ph_company = document.getElementById('ctPhComp').value.trim();
            if (document.getElementById('ctLblSrv')) ct.lbl_service = document.getElementById('ctLblSrv').value.trim();
            if (document.getElementById('ctPhSrv')) ct.ph_service = document.getElementById('ctPhSrv').value.trim();
            if (document.getElementById('ctLblBudget')) ct.lbl_budget = document.getElementById('ctLblBudget').value.trim();
            if (document.getElementById('ctLblMsg')) ct.lbl_message = document.getElementById('ctLblMsg').value.trim();
            if (document.getElementById('ctPhMsg')) ct.ph_message = document.getElementById('ctPhMsg').value.trim();
            if (document.getElementById('ctConsLbl')) ct.consent_link_label = document.getElementById('ctConsLbl').value.trim();
            if (document.getElementById('ctConsUrl')) ct.consent_link_url = document.getElementById('ctConsUrl').value.trim();
            if (document.getElementById('ctConsTxt')) ct.consent_text = document.getElementById('ctConsTxt').value.trim();
            if (document.getElementById('ctSuccessMsg')) ct.success_message = document.getElementById('ctSuccessMsg').value.trim();
            if (document.getElementById('ctShowPhone')) ct.show_phone = document.getElementById('ctShowPhone').checked;
            if (document.getElementById('ctShowEmail')) ct.show_email = document.getElementById('ctShowEmail').checked;
            if (document.getElementById('ctShowAddr')) ct.show_address = document.getElementById('ctShowAddr').checked;
            if (document.getElementById('ctShowSocials')) {
              ct.show_socials = document.getElementById('ctShowSocials').checked;
              S.store.site.show_contact_socials = ct.show_socials;
            }
            if (document.getElementById('ctShowSteps')) ct.show_steps = document.getElementById('ctShowSteps').checked;
            if (document.getElementById('ctShowCta')) ct.show_cta = document.getElementById('ctShowCta').checked;
            if (document.getElementById('ctPhoneLbl')) ct.phone_label = document.getElementById('ctPhoneLbl').value.trim();
            if (document.getElementById('ctEmailLbl')) ct.email_label = document.getElementById('ctEmailLbl').value.trim();
            if (document.getElementById('ctAddrLbl')) ct.address_label = document.getElementById('ctAddrLbl').value.trim();
            if (document.getElementById('ctMapsUrl')) ct.maps_url = document.getElementById('ctMapsUrl').value.trim();
            if (document.getElementById('ctMapEmbed')) ct.map_embed_url = document.getElementById('ctMapEmbed').value.trim();

            if (document.getElementById('ctSitePhone')) S.store.site.phone = document.getElementById('ctSitePhone').value.trim();
            if (document.getElementById('ctSitePhoneHref')) S.store.site.phone_href = document.getElementById('ctSitePhoneHref').value.trim();
            if (document.getElementById('ctSiteFax')) S.store.site.fax = document.getElementById('ctSiteFax').value.trim();
            if (document.getElementById('ctSiteEmail')) S.store.site.email = document.getElementById('ctSiteEmail').value.trim();
            if (document.getElementById('ctSiteAddr')) S.store.site.address = document.getElementById('ctSiteAddr').value.trim();
            if (document.getElementById('ctSiteCity')) S.store.site.city = document.getElementById('ctSiteCity').value.trim();
            if (document.getElementById('ctSocialProfiles')) {
              S.store.seo.social_profiles = document.getElementById('ctSocialProfiles').value.split('\n').map(s => s.trim()).filter(Boolean);
            }

            ct.budgets = document.getElementById('ctBudgets').value.split('\n').map(s => s.trim()).filter(Boolean);
            ct.steps = Array.from(ROOT.querySelectorAll('[data-ct-step]')).map(r => ({
              num: r.querySelector('.cts-num').value.trim(),
              title: r.querySelector('.cts-title').value.trim(),
              desc: r.querySelector('.cts-desc').value.trim()
            }));
          }
          if (sbBannerVal !== undefined) ct.banner_image = sbBannerVal;
          S.store.contact = ct;
        } else if (key.startsWith('legal:')) {
          const lk = key.slice(6);
          S.store.legal_pages = S.store.legal_pages || {};
          const doc = S.store.legal_pages[lk] || { slug: lk, sections: [] };
          const lpSlugEl = document.getElementById('lpSlug') || document.getElementById('sbPageSlug');
          if (lpSlugEl) {
            const nextLegalSlug = slugifyClient(lpSlugEl.value.trim() || lk) || lk;
            doc.slug = nextLegalSlug;
            S.store.page_slugs = S.store.page_slugs || {};
            S.store.page_slugs[lk] = nextLegalSlug;
            pushUrlRename(pgInfo.url, '/' + nextLegalSlug + '/');
          }
          if (document.getElementById('lpTitle')) {
            doc.title = document.getElementById('lpTitle').value.trim();
            doc.eyebrow = document.getElementById('lpEyebrow').value.trim();
            doc.lead = document.getElementById('lpLead').value.trim();
            doc.content_html = readRichHtml('lpRichBody');
            doc.sections = Array.from(ROOT.querySelectorAll('[data-lp-sec]')).map(el => ({
              heading: el.querySelector('.lps-h').value.trim(),
              body: el.querySelector('.lps-b').value.trim()
            })).filter(s => s.heading || s.body);
          }
          if (sbBannerVal !== undefined) doc.banner_image = sbBannerVal;
          S.store.legal_pages[lk] = doc;
        }

        // Sync Publish Status from Sidebar (for service:*, project:*, legal:*, and core pages)
        const sbStatusEl = document.getElementById('sbStatus');
        if (sbStatusEl && !isNewCustom && !S.pages.activeKey.startsWith('custom:') && S.pages.activeKey !== 'anasayfa') {
          const nextSt = sbStatusEl.value || 'published';
          if (S.pages.activeKey.startsWith('service:')) {
            const sv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
            if (sv) sv.status = nextSt;
          } else if (S.pages.activeKey.startsWith('project:')) {
            const pj = (S.store.projects || []).find(p => p.slug === S.pages.activeKey.slice(8));
            if (pj) pj.status = nextSt;
          } else {
            const cleanStKey = S.pages.activeKey.startsWith('legal:') ? S.pages.activeKey.slice(6) : S.pages.activeKey;
            S.store.page_states = S.store.page_states || {};
            S.store.page_states[cleanStKey] = Object.assign(S.store.page_states[cleanStKey] || {}, { status: nextSt });
          }
        }

        // Sync Menu Placement from Sidebar
        const sbHdr = document.getElementById('sbInHeader');
        const sbFtr = document.getElementById('sbInFooter');
        if (sbHdr && sbFtr) {
          const fallbackMenuTitle = key === 'blog'
            ? (S.store.blog_settings?.heading || S.store.page_titles?.blog || pgInfo.title)
            : (key.startsWith('legal:')
              ? (S.store.legal_pages?.[key.slice(6)]?.title || pgInfo.title)
              : (S.store.page_titles?.[key] || pgInfo.title));
          const menuLbl = document.getElementById('sbMenuLabel')?.value.trim() || fallbackMenuTitle;
          if (isNewCustom && S.pages.draftCustom) {
            S.pages.draftCustom.menu_label = menuLbl;
          }
          const fCol = Number(document.getElementById('sbFooterCol')?.value || 0);
          let targetUrl = isNewCustom
            ? ('/' + (S.pages.draftCustom.slug || 'yeni-sayfa') + '/')
            : getResolvedPageUrl(S.pages.activeKey);
          if (S.pages.activeKey.startsWith('custom:')) {
            const cp = (S.store.custom_pages || []).find(x => x.id === S.pages.activeKey.slice(7));
            if (cp) targetUrl = '/' + cp.slug + '/';
          }
          if (targetUrl !== pgInfo.url) {
            setPageMenuPlacement(pgInfo.url, menuLbl, false, false, 0);
          }
          setPageMenuPlacement(targetUrl, menuLbl, sbHdr.checked, sbFtr.checked, fCol, S.pages.activeKey);
        }

        // Sync Per-Page Typography from Sidebar
        const sbTbf = document.getElementById('sbTypoBodyFont');
        if (sbTbf) {
          const nextPt = {
            body_font: sbTbf.value || 'inherit',
            custom_body_font: document.getElementById('sbTypoCustomBody')?.value.trim() || '',
            heading_font: document.getElementById('sbTypoHeadFont')?.value || 'inherit',
            custom_heading_font: document.getElementById('sbTypoCustomHead')?.value.trim() || '',
            base_body_size: Number(document.getElementById('sbTypoBodySize')?.value || 0),
            heading_scale: Number(document.getElementById('sbTypoHeadScale')?.value || 0),
            heading_tracking: document.getElementById('sbTypoTracking')?.value || 'inherit'
          };
          const isDefaultPt = nextPt.body_font === 'inherit' && nextPt.heading_font === 'inherit' && nextPt.base_body_size === 0 && nextPt.heading_scale === 0 && nextPt.heading_tracking === 'inherit';
          if (isNewCustom && S.pages.draftCustom) {
            S.pages.draftCustom.page_typography = isDefaultPt ? null : nextPt;
          } else {
            S.store.page_typography = S.store.page_typography || {};
            if (key !== S.pages.activeKey) delete S.store.page_typography[key];
            if (isDefaultPt) {
              delete S.store.page_typography[S.pages.activeKey];
            } else {
              S.store.page_typography[S.pages.activeKey] = nextPt;
            }
          }
        }
      } else if (edTab === 'seo') {
        if (document.getElementById('useoMetaTitle')) {
          seoObj.meta_title = document.getElementById('useoMetaTitle').value.trim();
          seoObj.meta_desc = document.getElementById('useoMetaDesc').value.trim();
          seoObj.focus_keyword = document.getElementById('useoKw').value.trim();
          seoObj.robots = document.getElementById('useoRobots').value;
          seoObj.og_title = document.getElementById('useoOgTitle')?.value.trim() || '';
          seoObj.og_desc = document.getElementById('useoOgDesc')?.value.trim() || '';
          seoObj.og_image = document.getElementById('useoOgImg').value.trim();
          seoObj.ai_summary = document.getElementById('useoAiSummary').value.trim();
          if (key.startsWith('legal:')) {
            const lk = key.slice(6);
            if (S.store.legal_pages?.[lk]) {
              S.store.legal_pages[lk].meta_title = seoObj.meta_title;
              S.store.legal_pages[lk].meta_desc = seoObj.meta_desc;
            }
          }
        }
      }
    };

    // Save Current Page to Backend (Scoped Per-Page Payload)
    const saveCurrentUnifiedPage = async () => {
      syncActivePageFromDom();
      const activeAfterSync = S.pages.activeKey;

      if (isNewCustom) {
        if (!S.pages.draftCustom.title) {
          toast('Lütfen sayfa için bir başlık girin.', true);
          return;
        }
        const newId = 'page-' + Math.random().toString(36).slice(2, 10);
        S.pages.draftCustom.id = newId;
        S.pages.draftCustom.slug = slugifyClient(S.pages.draftCustom.slug || S.pages.draftCustom.title);
        S.store.custom_pages = S.store.custom_pages || [];
        S.store.custom_pages.push(JSON.parse(JSON.stringify(S.pages.draftCustom)));
        try {
          S.store.page_typography = S.store.page_typography || {};
          if (S.pages.draftCustom.page_typography) {
            S.store.page_typography['custom:' + newId] = S.pages.draftCustom.page_typography;
          }
          const res = await api('unified_pages_save', {
            custom_pages: S.store.custom_pages,
            menus: S.store.menus,
            page_typography: S.store.page_typography,
            page_menu_labels: Object.assign(S.store.page_menu_labels || {}, { ['custom:' + newId]: S.pages.draftCustom.menu_label || S.pages.draftCustom.title }),
            toast_message: `"${S.pages.draftCustom.title}" sayfası oluşturuldu ve yayına alındı.`
          });
          S.store = res.store;
          const saved = (S.store.custom_pages || []).find(x => x.id === newId) || S.store.custom_pages[S.store.custom_pages.length - 1];
          S.pages.draftCustom = null;
          S.pages.activeKey = saved ? ('custom:' + saved.id) : 'anasayfa';
          updateSidebarBadges();
          toast(res.message);
          renderPagesView();
        } catch (err) {
          toast(err.message, true);
        }
        return;
      }

      const payload = {
        page_titles: S.store.page_titles || {},
        page_slugs: S.store.page_slugs || {},
        page_states: S.store.page_states || {},
        page_menu_labels: S.store.page_menu_labels || {},
        page_typography: S.store.page_typography || {},
        menus: S.store.menus,
        url_renames: S.pages.pendingUrlRenames || [],
        toast_message: 'Sayfa değişiklikleri başarıyla kaydedildi ve canlı siteye yansıtıldı.'
      };

      if (key.startsWith('custom:')) {
        payload.custom_pages = S.store.custom_pages || [];
      } else if (key.startsWith('service:')) {
        payload.services = S.store.services || [];
      } else if (key.startsWith('project:')) {
        payload.projects = S.store.projects || [];
      } else {
        const coreKey = key.startsWith('legal:') ? key.slice(6) : key;
        if (S.store.core_page_blocks && Array.isArray(S.store.core_page_blocks[coreKey])) {
          payload.core_page_blocks = { [coreKey]: S.store.core_page_blocks[coreKey] };
        }
        if (S.store.seo?.pages?.[coreKey]) {
          payload.seo_pages = { [coreKey]: S.store.seo.pages[coreKey] };
        }
        if (key === 'anasayfa') {
          payload.home = S.store.home;
        } else if (key === 'hakkimizda') {
          payload.about = S.store.about;
        } else if (key === 'hizmetler') {
          payload.services_page = S.store.services_page;
          payload.home = S.store.home;
          payload.services = S.store.services || [];
        } else if (key === 'projeler') {
          payload.projects_page = S.store.projects_page;
          payload.projects = S.store.projects || [];
        } else if (key === 'blog') {
          payload.blog_settings = S.store.blog_settings;
        } else if (key === 'iletisim') {
          payload.contact = S.store.contact;
          payload.site_contact = {
            phone: S.store.site?.phone || '',
            phone_href: S.store.site?.phone_href || '',
            email: S.store.site?.email || '',
            address: S.store.site?.address || '',
            city: S.store.site?.city || '',
            show_contact_socials: S.store.site?.show_contact_socials !== false
          };
          payload.social_profiles = S.store.seo?.social_profiles || [];
        } else if (key.startsWith('legal:')) {
          const lk = key.slice(6);
          if (S.store.legal_pages?.[lk]) {
            payload.legal_pages = { [lk]: S.store.legal_pages[lk] };
          }
        }
      }

      try {
        const res = await api('unified_pages_save', payload);
        S.pages.pendingUrlRenames = [];
        S.store = res.store;
        ensureNormalizedHomeSections();
        S.homeLayoutSavedSnap = computeHomeLayoutSnap();
        S.pages.activeKey = activeAfterSync;
        updateSidebarBadges();
        toast(res.message);
        renderPagesView();
      } catch (err) {
        toast(err.message, true);
      }
    };

    // Bind Top & Side Navigation
    document.getElementById('pgBackToListBtn').onclick = () => {
      syncActivePageFromDom();
      S.pages.mode = 'list';
      renderPagesView();
    };
    document.getElementById('pgTopNewPageBtn').onclick = () => {
      syncActivePageFromDom();
      startNewUnifiedCustomPage();
    };
    const topDupBtn = document.getElementById('pgTopDupPageBtn');
    if (topDupBtn) {
      topDupBtn.onclick = () => {
        syncActivePageFromDom();
        duplicateUnifiedPage(key);
      };
    }
    document.getElementById('pgQuickSwitchSel').onchange = e => {
      syncActivePageFromDom();
      openUnifiedPageEditor(e.target.value, S.pages.editorTab);
    };
    document.getElementById('pgMainSaveBtn').onclick = saveCurrentUnifiedPage;
    const sideSaveBtn = document.getElementById('pgSideSaveBtn');
    if (sideSaveBtn) sideSaveBtn.onclick = saveCurrentUnifiedPage;
    const sideDupBtn = document.getElementById('pgSideDupBtn');
    if (sideDupBtn) {
      sideDupBtn.onclick = () => {
        syncActivePageFromDom();
        duplicateUnifiedPage(key);
      };
    }
    const sideDelBtn = document.getElementById('pgSideDeleteBtn');
    if (sideDelBtn) sideDelBtn.onclick = () => deleteUnifiedPage(key);
    const sideRestoreBtn = document.getElementById('pgSideRestoreBtn');
    if (sideRestoreBtn) sideRestoreBtn.onclick = () => restoreUnifiedPages([key]);
    const sidePurgeBtn = document.getElementById('pgSidePurgeBtn');
    if (sidePurgeBtn) sidePurgeBtn.onclick = () => permanentlyDeleteUnifiedPages([key], false);
    const sbJumpMenus = document.getElementById('sbJumpMenusBtn');
    if (sbJumpMenus) sbJumpMenus.onclick = () => { syncActivePageFromDom(); navigateTo('menus'); };
    const ctJumpSmtp = document.getElementById('ctJumpSmtpBtn');
    if (ctJumpSmtp) ctJumpSmtp.onclick = () => { syncActivePageFromDom(); S.tabs.settings = 'smtp'; navigateTo('settings'); };

    const sbTbfEl = document.getElementById('sbTypoBodyFont');
    if (sbTbfEl) {
      sbTbfEl.onchange = () => {
        const w = document.getElementById('sbTypoCustomBodyWrap');
        if (w) w.style.display = sbTbfEl.value === 'custom' ? 'block' : 'none';
      };
    }
    const sbThfEl = document.getElementById('sbTypoHeadFont');
    if (sbThfEl) {
      sbThfEl.onchange = () => {
        const w = document.getElementById('sbTypoCustomHeadWrap');
        if (w) w.style.display = sbThfEl.value === 'custom' ? 'block' : 'none';
      };
    }
    const sbResetTypo = document.getElementById('sbResetTypoBtn');
    if (sbResetTypo) {
      sbResetTypo.onclick = () => {
        if (document.getElementById('sbTypoBodyFont')) document.getElementById('sbTypoBodyFont').value = 'inherit';
        if (document.getElementById('sbTypoHeadFont')) document.getElementById('sbTypoHeadFont').value = 'inherit';
        if (document.getElementById('sbTypoBodySize')) document.getElementById('sbTypoBodySize').value = '0';
        if (document.getElementById('sbTypoHeadScale')) document.getElementById('sbTypoHeadScale').value = '0';
        if (document.getElementById('sbTypoTracking')) document.getElementById('sbTypoTracking').value = 'inherit';
        syncActivePageFromDom();
        toast('Bu sayfanın font ayarı site genel ayarına sıfırlandı — kaydetmeyi unutmayın.');
        renderPagesView();
      };
    }
    const sbJumpGlobalFont = document.getElementById('sbJumpGlobalFontBtn');
    if (sbJumpGlobalFont) {
      sbJumpGlobalFont.onclick = () => {
        syncActivePageFromDom();
        S.tabs.menus = 'typography';
        navigateTo('menus');
      };
    }

    const launchPageShareStudio = () => {
      syncActivePageFromDom();
      openSocialShareStudio({
        kind: 'page',
        targetKey: isNewCustom ? 'anasayfa' : S.pages.activeKey,
        unsaved: isNewCustom,
        draft: isNewCustom ? {
          title: S.pages.draftCustom.title,
          slug: S.pages.draftCustom.slug,
          excerpt: S.pages.draftCustom.excerpt,
          content_html: S.pages.draftCustom.content_html,
          image: S.pages.draftCustom.banner_image,
          meta_title: seoObj.meta_title,
          meta_desc: seoObj.meta_desc,
          og_title: seoObj.og_title,
          og_desc: seoObj.og_desc,
          og_image: seoObj.og_image
        } : {},
        onSaved: upd => {
          if (isNewCustom) {
            seoObj.og_title = upd.og_title || '';
            seoObj.og_desc = upd.og_desc || '';
            seoObj.og_image = upd.og_image || '';
          }
          renderPagesView();
        }
      });
    };
    const topShareBtn = document.getElementById('pgShareStudioBtn');
    if (topShareBtn) topShareBtn.onclick = launchPageShareStudio;
    const sideShareBtn = document.getElementById('pgSideShareBtn');
    if (sideShareBtn) sideShareBtn.onclick = launchPageShareStudio;

    ROOT.querySelectorAll('[data-ed-tab]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        S.pages.editorTab = btn.getAttribute('data-ed-tab');
        renderPagesView();
      };
    });

    // Builder Tab Bindings
    if (edTab === 'builder') {
      bindSharedBuilderCanvasEvents(() => renderPagesView());
      return;
    }

    // SEO Tab Bindings
    if (edTab === 'seo') {
      const tInp = document.getElementById('useoMetaTitle');
      const dInp = document.getElementById('useoMetaDesc');
      const ogtInp = document.getElementById('useoOgTitle');
      const ogdInp = document.getElementById('useoOgDesc');
      const ogiInp = document.getElementById('useoOgImg');
      const syncSeoCardPreviews = () => {
        const mt = tInp?.value.trim() || (pgInfo.title + ' | ZEYNART');
        const md = dInp?.value.trim() || '';
        const ogt = ogtInp?.value.trim() || mt;
        const ogd = ogdInp?.value.trim() || md || 'Sayfa linki WhatsApp, LinkedIn, Facebook veya X platformuna yapıştırıldığında bu görsel ve açıklama çıkar.';
        const ogi = ogiInp?.value.trim() || currentBanner || '/images/banners/services.webp';
        if (document.getElementById('useoPreviewTitle')) document.getElementById('useoPreviewTitle').textContent = mt;
        if (document.getElementById('useoPreviewDesc')) document.getElementById('useoPreviewDesc').textContent = md;
        if (document.getElementById('useoOgPreviewTitle')) document.getElementById('useoOgPreviewTitle').textContent = ogt;
        if (document.getElementById('useoOgPreviewDesc')) document.getElementById('useoOgPreviewDesc').textContent = ogd;
        if (document.getElementById('useoOgPreviewImg')) document.getElementById('useoOgPreviewImg').src = resolveUrl(ogi);
      };
      [tInp, dInp, ogtInp, ogdInp, ogiInp].forEach(el => {
        if (el) el.oninput = syncSeoCardPreviews;
      });
      const pickOg = document.getElementById('useoPickOgBtn');
      if (pickOg) pickOg.onclick = () => openMediaPicker(u => { document.getElementById('useoOgImg').value = u; syncSeoCardPreviews(); });
      const useoShareBtn = document.getElementById('useoOpenShareBtn');
      if (useoShareBtn) useoShareBtn.onclick = launchPageShareStudio;

      const aiBtn = document.getElementById('useoAiGenBtn');
      if (aiBtn) {
        aiBtn.onclick = async () => {
          try {
            aiBtn.disabled = true;
            aiBtn.textContent = 'Üretiliyor...';
            const res = await api('ai_seo_generate', {
              title: pgInfo.title,
              content_html: `<p>${pgInfo.title} — ${pgInfo.metaDesc || pgInfo.typeLabel}</p>`,
              category: pgInfo.typeLabel,
              image: pgInfo.bannerImage || '/images/banners/services.webp'
            });
            if (res.pack) {
              document.getElementById('useoMetaTitle').value = res.pack.meta_title || '';
              document.getElementById('useoMetaDesc').value = res.pack.meta_desc || '';
              document.getElementById('useoKw').value = res.pack.focus_keyword || '';
              document.getElementById('useoAiSummary').value = res.pack.ai_summary || '';
              syncSeoCardPreviews();
            }
            toast(res.message);
          } catch (err) {
            toast(err.message, true);
          } finally {
            aiBtn.disabled = false;
            aiBtn.textContent = '✨ Otomatik SEO Üret';
          }
        };
      }
      return;
    }

    // Content Tab Shared Bindings
    const sbPickBanner = document.getElementById('sbPickBannerBtn');
    if (sbPickBanner) {
      sbPickBanner.onclick = () => openMediaPicker(url => {
        document.getElementById('sbBanner').value = url;
        if (key.startsWith('project:') && document.getElementById('pjVisType')) {
          document.getElementById('pjVisType').value = 'image';
        }
        syncActivePageFromDom();
        renderPagesView();
      });
    }

    if (key.startsWith('service:') || key.startsWith('project:')) {
      bindRecordGalleryCard(() => key.startsWith('service:')
        ? (S.store.services || []).find(s => s.slug === key.slice(8))
        : (S.store.projects || []).find(p => p.slug === key.slice(8)), syncActivePageFromDom, renderPagesView);
    }

    ROOT.querySelectorAll('[data-mpick]').forEach(btn => {
      btn.onclick = () => {
        const targetId = btn.getAttribute('data-mpick');
        openMediaPicker(url => {
          const el = document.getElementById(targetId);
          if (el) el.value = url;
        });
      };
    });

    ROOT.querySelectorAll('[data-pg-sub]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        S.pages.subTab = btn.getAttribute('data-pg-sub');
        renderPagesView();
      };
    });

    ROOT.querySelectorAll('[data-pg-jump]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        openUnifiedPageEditor(btn.getAttribute('data-pg-jump'), 'content');
      };
    });

    // Rich Editor Toolbar Bindings (for Custom & Legal pages)
    const toggleSrcBtn = document.getElementById('pgToggleRichSrcBtn');
    if (toggleSrcBtn) {
      toggleSrcBtn.onclick = () => {
        syncActivePageFromDom();
        S.pages.sourceMode = !S.pages.sourceMode;
        renderPagesView();
      };
    }
    ROOT.querySelectorAll('[data-rcmd]').forEach(btn => {
      btn.onclick = () => {
        const [cmd, arg] = btn.getAttribute('data-rcmd').split(':');
        document.execCommand(cmd, false, arg || null);
      };
    });
    const richLinkBtn = document.getElementById('pgRichLinkBtn');
    if (richLinkBtn) {
      richLinkBtn.onclick = () => {
        const u = prompt('Bağlantı adresini girin (örn: /iletisim/ veya https://...):', '/iletisim/');
        if (u) document.execCommand('createLink', false, u);
      };
    }
    const richImgBtn = document.getElementById('pgRichImgBtn');
    if (richImgBtn) {
      richImgBtn.onclick = () => {
        openMediaPicker((url, alt) => {
          const targetEl = document.getElementById('cpRichBody') || document.getElementById('lpRichBody');
          if (targetEl) {
            targetEl.focus();
            document.execCommand('insertHTML', false, `<figure><img src="${esc(resolveUrl(url))}" alt="${esc(alt || '')}" loading="lazy"/></figure>`);
          }
        }, { allowMultiple: true });
      };
    }

    // Page-Specific Content Action Bindings
    const cpTitleInp = document.getElementById('cpTitle');
    const cpSlugInp = document.getElementById('cpSlug');
    if (cpTitleInp && cpSlugInp && isNewCustom) {
      cpTitleInp.oninput = () => {
        cpSlugInp.value = slugifyClient(cpTitleInp.value);
        const menuLbl = document.getElementById('sbMenuLabel');
        if (menuLbl) menuLbl.value = cpTitleInp.value;
      };
    }

    // Live 2-Way Sync Between Primary Permalink Input & Right Sidebar Permalink Input
    const sbSlugInp = document.getElementById('sbPageSlug');
    const mainSlugInp = document.getElementById('corePageSlugInp')
      || document.getElementById('lpSlug')
      || document.getElementById('svSlug')
      || document.getElementById('pjSlug');
    if (sbSlugInp && mainSlugInp) {
      mainSlugInp.addEventListener('input', () => {
        sbSlugInp.value = mainSlugInp.value;
      });
      sbSlugInp.addEventListener('input', () => {
        mainSlugInp.value = sbSlugInp.value;
      });
    }
    const coreTitleInpEl = document.getElementById('corePageTitleInp');
    const blogHeadingInpEl = document.getElementById('bsHeading');
    if (coreTitleInpEl && blogHeadingInpEl) {
      coreTitleInpEl.addEventListener('input', () => {
        blogHeadingInpEl.value = coreTitleInpEl.value;
      });
      blogHeadingInpEl.addEventListener('input', () => {
        coreTitleInpEl.value = blogHeadingInpEl.value;
      });
    }

    const cpAddFaq = document.getElementById('cpAddFaqBtn');
    if (cpAddFaq) {
      cpAddFaq.onclick = () => {
        syncActivePageFromDom();
        const targetCp = isNewCustom ? S.pages.draftCustom : (S.store.custom_pages || []).find(x => x.id === key.slice(7));
        if (targetCp) {
          targetCp.faqs = targetCp.faqs || [];
          targetCp.faqs.push({ q: 'Yeni soru başlığı?', a: 'Sorunun yanıt metni.' });
          renderPagesView();
        }
      };
    }
    ROOT.querySelectorAll('[data-cp-faq-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const targetCp = isNewCustom ? S.pages.draftCustom : (S.store.custom_pages || []).find(x => x.id === key.slice(7));
        if (targetCp?.faqs) {
          targetCp.faqs.splice(Number(btn.getAttribute('data-cp-faq-del')), 1);
          renderPagesView();
        }
      };
    });

    // Home page dynamic actions
    if (key === 'anasayfa') {
      bindHomeSectionCardsEvents(ROOT, () => renderPagesView());
      const openFullLayoutBtn = document.getElementById('pgOpenFullHomeLayoutBtn');
      if (openFullLayoutBtn) {
        openFullLayoutBtn.onclick = () => {
          syncActivePageFromDom();
          navigateTo('home_layout');
        };
      }
    }
    const hhAddSlide = document.getElementById('hhAddSlideBtn');
    if (hhAddSlide) {
      hhAddSlide.onclick = () => {
        syncActivePageFromDom();
        S.store.home.hero.slides.push({ label: 'YENİ VİZYON', lines: ['Markanız için', 'yeni'], accent: 'ufuklar.', copy: 'Dijital dünyada fark yaratan strateji ve teknoloji.', note: ['TASARIM.', 'YAZILIM.', 'ZEYNART.'] });
        renderPagesView();
      };
    }
    ROOT.querySelectorAll('[data-hslide-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        S.store.home.hero.slides.splice(Number(btn.getAttribute('data-hslide-del')), 1);
        renderPagesView();
      };
    });

    // About page dynamic actions
    const abAddVal = document.getElementById('abAddValBtn');
    if (abAddVal) {
      abAddVal.onclick = () => {
        syncActivePageFromDom();
        S.store.about.values = S.store.about.values || [];
        S.store.about.values.push({ icon: 'sparkles', title: 'Yeni Çalışma İlkesi', desc: 'İlke açıklaması...' });
        renderPagesView();
      };
    }
    ROOT.querySelectorAll('[data-ab-val-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        S.store.about.values.splice(Number(btn.getAttribute('data-ab-val-del')), 1);
        renderPagesView();
      };
    });
    const abSearch = document.getElementById('abClientSearch');
    if (abSearch) {
      abSearch.oninput = e => {
        syncActivePageFromDom();
        S.clientSearch = e.target.value;
        renderPagesView();
        const inp = document.getElementById('abClientSearch');
        if (inp) { inp.focus(); inp.setSelectionRange(inp.value.length, inp.value.length); }
      };
    }
    const abJumpClientsSub = document.getElementById('abJumpClientsSubBtn');
    if (abJumpClientsSub) {
      abJumpClientsSub.onclick = () => {
        syncActivePageFromDom();
        S.pages.subTab = 'clients';
        renderPagesView();
      };
    }
    const abQuickSaveClients = document.getElementById('abQuickSaveClientsBtn');
    if (abQuickSaveClients) {
      abQuickSaveClients.onclick = saveCurrentUnifiedPage;
    }
    const ensureAdminAboutClients = () => {
      S.store.about = S.store.about || {};
      S.store.about.clients = Array.isArray(S.store.about.clients) ? S.store.about.clients : [];
      return S.store.about.clients;
    };
    const abAddClient = document.getElementById('abAddClientBtn');
    if (abAddClient) {
      abAddClient.onclick = () => {
        syncActivePageFromDom();
        S.clientSearch = '';
        openMediaPicker((url, alt) => {
          const cleanName = (alt || '').trim() || humanizeAltFromFilename(url.split('/').pop() || '') || 'yenimarka.com';
          ensureAdminAboutClients().unshift({ id: uid('client'), name: cleanName, alt: cleanName, url: 'https://', image: url, _isNew: true });
          renderPagesView();
        }, { allowMultiple: true });
      };
    }
    const abAddBlankClient = document.getElementById('abAddBlankClientBtn');
    if (abAddBlankClient) {
      abAddBlankClient.onclick = () => {
        syncActivePageFromDom();
        S.clientSearch = '';
        ensureAdminAboutClients().unshift({ id: uid('client'), name: 'yenimarka.com', alt: 'yenimarka.com', url: 'https://', image: '/images/zeynart-logo.avif', _isNew: true });
        renderPagesView();
        const firstNameInp = ROOT.querySelector('[data-cl-idx="0"] .cl-name');
        if (firstNameInp) { firstNameInp.focus(); firstNameInp.select(); }
      };
    }
    const abDirectDrop = document.getElementById('abDirectClientDrop');
    const abDirectFile = document.getElementById('abDirectClientFileInput');
    const abDirectBrowse = document.getElementById('abDirectClientBrowseBtn');
    const abDirectDeck = document.getElementById('abDirectClientDeckSlot');
    if (abDirectDrop && abDirectFile) {
      const handleDirectClientUpload = async fileList => {
        if (!fileList || !fileList.length) return;
        syncActivePageFromDom();
        S.clientSearch = '';
        await runStudioUploadQueue(fileList, abDirectDeck, {
          onComplete: results => {
            if (!results || !results.length) return;
            const list = ensureAdminAboutClients();
            results.slice().reverse().forEach(r => {
              const nm = (r.alt || '').trim() || humanizeAltFromFilename(r.name || '') || 'yenimarka.com';
              list.unshift({ id: uid('client'), name: nm, alt: nm, url: 'https://', image: r.url, _isNew: true });
            });
            toast(`${results.length} müşteri logosu eklendi! Yayınlamak için "Kaydet & Yayınla" butonuna basın.`);
            renderPagesView();
          }
        });
      };
      abDirectDrop.onclick = ev => {
        if (abDirectBrowse && (ev.target === abDirectBrowse || abDirectBrowse.contains(ev.target))) return;
        abDirectFile.click();
      };
      if (abDirectBrowse) abDirectBrowse.onclick = ev => { ev.stopPropagation(); abDirectFile.click(); };
      abDirectDrop.ondragover = ev => { ev.preventDefault(); abDirectDrop.classList.add('drag-over'); };
      abDirectDrop.ondragleave = () => abDirectDrop.classList.remove('drag-over');
      abDirectDrop.ondrop = ev => {
        ev.preventDefault();
        abDirectDrop.classList.remove('drag-over');
        if (ev.dataTransfer?.files?.length) handleDirectClientUpload(ev.dataTransfer.files);
      };
      abDirectFile.onchange = () => {
        if (abDirectFile.files?.length) handleDirectClientUpload(abDirectFile.files);
        abDirectFile.value = '';
      };
    }
    ROOT.querySelectorAll('[data-cl-pick]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const idx = Number(btn.getAttribute('data-cl-pick'));
        openMediaPicker((url, alt) => {
          const list = ensureAdminAboutClients();
          if (list[idx]) {
            list[idx].image = url;
            if (!list[idx].name || list[idx].name === 'yenimarka.com') {
              const nm = (alt || '').trim() || humanizeAltFromFilename(url.split('/').pop() || '') || 'yenimarka.com';
              list[idx].name = nm;
              list[idx].alt = nm;
            }
          }
          renderPagesView();
        });
      };
    });
    ROOT.querySelectorAll('[data-cl-move]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const [idx, dir] = btn.getAttribute('data-cl-move').split(':').map(Number);
        const list = ensureAdminAboutClients();
        if (list[idx] && list[idx + dir]) {
          [list[idx], list[idx + dir]] = [list[idx + dir], list[idx]];
          renderPagesView();
        }
      };
    });
    ROOT.querySelectorAll('[data-cl-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        ensureAdminAboutClients().splice(Number(btn.getAttribute('data-cl-del')), 1);
        renderPagesView();
      };
    });
    const abGoGoogle = document.getElementById('abGoGoogleStudioBtn');
    if (abGoGoogle) abGoGoogle.onclick = () => { syncActivePageFromDom(); navigateTo('google_reviews'); };
    const abAddRev = document.getElementById('abAddRevBtn');
    if (abAddRev) {
      abAddRev.onclick = () => {
        syncActivePageFromDom();
        S.store.about.reviews.unshift({ author: 'Yeni Müşteri', date: 'Ekim 2026 · Google', text: 'Harika bir çalışma deneyimi oldu, teşekkür ederiz.' });
        renderPagesView();
      };
    }
    ROOT.querySelectorAll('[data-rv-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        S.store.about.reviews.splice(Number(btn.getAttribute('data-rv-del')), 1);
        renderPagesView();
      };
    });

    // Services parent & child actions
    const spCreateChild = document.getElementById('spCreateChildSrvBtn');
    if (spCreateChild) spCreateChild.onclick = () => { syncActivePageFromDom(); createNewUnifiedServiceSubpage(); };
    ROOT.querySelectorAll('[data-sp-srv-move]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const [idx, dir] = btn.getAttribute('data-sp-srv-move').split(':').map(Number);
        const arr = S.store.services;
        [arr[idx], arr[idx + dir]] = [arr[idx + dir], arr[idx]];
        renderPagesView();
      };
    });
    const svLayoutSel = document.getElementById('svLayout');
    if (svLayoutSel) {
      svLayoutSel.onchange = () => {
        syncActivePageFromDom();
        renderPagesView();
      };
    }
    const svAddGdOff = document.getElementById('svAddGdOffBtn');
    if (svAddGdOff) {
      svAddGdOff.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv) {
          srv.graphic_data = srv.graphic_data || {};
          srv.graphic_data.offerings = srv.graphic_data.offerings || [];
          srv.graphic_data.offerings.push({ num: String(srv.graphic_data.offerings.length + 1).padStart(2, '0'), icon: 'pen-tool', title: 'Yeni Tasarım Alanı', desc: 'Kapsam açıklaması...', items: ['Özgün konsept tasarım', 'Baskı ve dijital teslim'] });
          renderPagesView();
        }
      };
    }
    ROOT.querySelectorAll('[data-gd-off-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv?.graphic_data?.offerings) {
          srv.graphic_data.offerings.splice(Number(btn.getAttribute('data-gd-off-del')), 1);
          renderPagesView();
        }
      };
    });
    const svAddGdProc = document.getElementById('svAddGdProcBtn');
    if (svAddGdProc) {
      svAddGdProc.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv) {
          srv.graphic_data = srv.graphic_data || {};
          srv.graphic_data.process = srv.graphic_data.process || [];
          srv.graphic_data.process.push({ num: String(srv.graphic_data.process.length + 1).padStart(2, '0'), title: 'Yeni Tasarım Adımı', desc: 'Adım açıklaması...' });
          renderPagesView();
        }
      };
    }
    ROOT.querySelectorAll('[data-gd-proc-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv?.graphic_data?.process) {
          srv.graphic_data.process.splice(Number(btn.getAttribute('data-gd-proc-del')), 1);
          renderPagesView();
        }
      };
    });
    const svAddHdSol = document.getElementById('svAddHdSolBtn');
    if (svAddHdSol) {
      svAddHdSol.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv) {
          srv.hosting_data = srv.hosting_data || {};
          srv.hosting_data.solutions = srv.hosting_data.solutions || [];
          srv.hosting_data.solutions.push({ num: String(srv.hosting_data.solutions.length + 1).padStart(2, '0'), title: 'Yeni Sunucu Çözümü', desc: 'Çözüm açıklaması...' });
          renderPagesView();
        }
      };
    }
    ROOT.querySelectorAll('[data-hd-sol-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv?.hosting_data?.solutions) {
          srv.hosting_data.solutions.splice(Number(btn.getAttribute('data-hd-sol-del')), 1);
          renderPagesView();
        }
      };
    });
    const svAddHdBen = document.getElementById('svAddHdBenBtn');
    if (svAddHdBen) {
      svAddHdBen.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv) {
          srv.hosting_data = srv.hosting_data || {};
          srv.hosting_data.benefits = srv.hosting_data.benefits || [];
          srv.hosting_data.benefits.push({ title: 'Yeni Altyapı Avantajı', desc: 'Avantaj açıklaması...' });
          renderPagesView();
        }
      };
    }
    ROOT.querySelectorAll('[data-hd-ben-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv?.hosting_data?.benefits) {
          srv.hosting_data.benefits.splice(Number(btn.getAttribute('data-hd-ben-del')), 1);
          renderPagesView();
        }
      };
    });
    const svAddPkg = document.getElementById('svAddHostPkg');
    if (svAddPkg) {
      svAddPkg.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv) {
          srv.hosting_data = srv.hosting_data || { packages: [] };
          srv.hosting_data.packages = srv.hosting_data.packages || [];
          srv.hosting_data.packages.push({ name: 'Kurumsal', eyebrow: 'KURUMSAL', disk: '10.000 MB', disk_label: 'Disk alanı', featured: false, features: ['Sınırsız trafik', 'Plesk panel', '7/24 DDoS koruması'] });
          renderPagesView();
        }
      };
    }
    ROOT.querySelectorAll('[data-hpkg-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv?.hosting_data?.packages) {
          srv.hosting_data.packages.splice(Number(btn.getAttribute('data-hpkg-del')), 1);
          renderPagesView();
        }
      };
    });
    const svAddFaq = document.getElementById('svAddFaqBtn');
    if (svAddFaq) {
      svAddFaq.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv) {
          srv.faqs = srv.faqs || [];
          srv.faqs.push({ q: 'Yeni hizmet sorusu?', a: 'Yanıt metni.' });
          renderPagesView();
        }
      };
    }
    ROOT.querySelectorAll('[data-sv-faq-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const srv = (S.store.services || []).find(s => s.slug === S.pages.activeKey.slice(8));
        if (srv?.faqs) {
          srv.faqs.splice(Number(btn.getAttribute('data-sv-faq-del')), 1);
          renderPagesView();
        }
      };
    });

    // Projects parent actions
    const ppCreateChild = document.getElementById('ppCreateChildPrjBtn');
    if (ppCreateChild) ppCreateChild.onclick = () => { syncActivePageFromDom(); createNewUnifiedProjectSubpage(); };
    ROOT.querySelectorAll('[data-pp-prj-move]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const [idx, dir] = btn.getAttribute('data-pp-prj-move').split(':').map(Number);
        const arr = S.store.projects;
        [arr[idx], arr[idx + dir]] = [arr[idx + dir], arr[idx]];
        renderPagesView();
      };
    });

    // Blog showcase jump & live menu title sync
    const goBlogPosts = document.getElementById('pgGoBlogPostsBtn');
    if (goBlogPosts) goBlogPosts.onclick = () => { syncActivePageFromDom(); navigateTo('blog', () => { S.blog.mode = 'list'; }); };
    const bsHeadingInp = document.getElementById('bsHeading');
    if (bsHeadingInp) {
      let prevBsHeading = bsHeadingInp.value;
      bsHeadingInp.oninput = () => {
        const menuLbl = document.getElementById('sbMenuLabel');
        if (menuLbl && menuLbl.value === prevBsHeading) {
          menuLbl.value = bsHeadingInp.value;
        }
        prevBsHeading = bsHeadingInp.value;
      };
    }

    // Contact steps actions
    const ctGoMessages = document.getElementById('ctGoMessagesBtn');
    if (ctGoMessages) ctGoMessages.onclick = () => { syncActivePageFromDom(); navigateTo('messages'); };
    const ctAddStep = document.getElementById('ctAddStepBtn');
    if (ctAddStep) {
      ctAddStep.onclick = () => {
        syncActivePageFromDom();
        S.store.contact.steps = S.store.contact.steps || [];
        S.store.contact.steps.push({ num: String(S.store.contact.steps.length + 1).padStart(2, '0'), title: 'Yeni Adım', desc: 'Adım açıklaması...' });
        renderPagesView();
      };
    }
    ROOT.querySelectorAll('[data-ct-step-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        S.store.contact.steps.splice(Number(btn.getAttribute('data-ct-step-del')), 1);
        renderPagesView();
      };
    });

    // Legal sections actions
    const lpAddSec = document.getElementById('lpAddSecBtn');
    if (lpAddSec) {
      lpAddSec.onclick = () => {
        syncActivePageFromDom();
        const lk = key.slice(6);
        S.store.legal_pages[lk].sections = S.store.legal_pages[lk].sections || [];
        S.store.legal_pages[lk].sections.push({ heading: 'Yeni Madde Başlığı', body: 'Madde açıklama metni...' });
        renderPagesView();
      };
    }
    ROOT.querySelectorAll('[data-lp-del]').forEach(btn => {
      btn.onclick = () => {
        syncActivePageFromDom();
        const lk = key.slice(6);
        S.store.legal_pages[lk].sections.splice(Number(btn.getAttribute('data-lp-del')), 1);
        renderPagesView();
      };
    });
  }


  return {
    renderHomeLayoutView,
    openUnifiedPageEditor,
    startNewUnifiedCustomPage,
    createNewUnifiedServiceSubpage,
    renderPagesView
  };
  });
})();
