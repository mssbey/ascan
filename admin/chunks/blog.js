/* ZEYNART DİJİTAL STÜDYO v4.0 — Lazy Modül: blog */
(function () {
  'use strict';
  if (typeof window.__ZS_REGISTER_CHUNK__ !== 'function') return;
  window.__ZS_REGISTER_CHUNK__('blog', function (CTX) {
  const { CFG, ROOT, S, api, apiUpload, buildUnifiedPagesCatalog, esc, getClientBlogCategoriesTree, getDefaultBlogCategoryClient, getResolvedPageSlug, isSuperAdmin, markHistoryPush, navigateTo, openImageCanvasStudio, openMediaPicker, openSocialShareStudio, rememberMediaMtimes, renderSeoRing, resolvePreviewUrl, resolveUrl, slugifyClient, syncAdminUrl, syncBlogCatsFromApiPayload, toast, trFoldLowerClient, uid, updateSidebarBadges } = CTX;

  function resolvePostCategoryIdsClient(post) {
    const tree = getClientBlogCategoriesTree();
    const byId = {};
    const bySlug = {};
    const byName = {};
    tree.forEach(c => {
      byId[c.id] = c;
      bySlug[c.slug] = c;
      byName[c.name.toLocaleLowerCase('tr-TR')] = c;
    });
    const matched = [];
    const addMatch = c => {
      if (c && !matched.includes(c.id)) matched.push(c.id);
    };
    if (Array.isArray(post?.category_ids)) {
      post.category_ids.forEach(cid => {
        const key = String(cid || '').trim();
        if (byId[key]) addMatch(byId[key]);
      });
    }
    if (!matched.length && Array.isArray(post?.category_slugs)) {
      post.category_slugs.forEach(cs => {
        const key = slugifyClient(String(cs || ''));
        if (bySlug[key]) addMatch(bySlug[key]);
      });
    }
    if (!matched.length && Array.isArray(post?.categories)) {
      post.categories.forEach(cn => {
        const low = String(cn || '').trim().toLocaleLowerCase('tr-TR');
        const sl = slugifyClient(String(cn || ''));
        if (byName[low]) addMatch(byName[low]);
        else if (bySlug[sl]) addMatch(bySlug[sl]);
      });
    }
    if (!matched.length && post?.category) {
      const low = String(post.category || '').trim().toLocaleLowerCase('tr-TR');
      const sl = slugifyClient(String(post.category || ''));
      if (byName[low]) addMatch(byName[low]);
      else if (bySlug[sl]) addMatch(bySlug[sl]);
    }
    if (!matched.length && post?._explicitEmptyCats !== true) {
      const def = getDefaultBlogCategoryClient();
      if (def?.id) matched.push(def.id);
    }
    return matched;
  }

  function createBlankPost() {
    const today = new Date().toISOString().slice(0, 10);
    const defCat = getDefaultBlogCategoryClient();
    return {
      slug: '',
      title: '',
      category: defCat.name || 'Genel',
      categories: [defCat.name || 'Genel'],
      category_ids: defCat.id ? [defCat.id] : [],
      category_slugs: defCat.slug ? [defCat.slug] : [],
      excerpt: '',
      date: today,
      read: '',
      image: '/images/journal/web-planning.webp',
      image_alt: '',
      status: 'published',
      featured: false,
      curated: false,
      author: 'Zeynart Web Bilişim',
      author_role: 'Tasarım & teknoloji notları',
      prose_mode: 'html',
      content_html: '<h2>Giriş ve Temel Yaklaşım</h2>\n<p>Yazınızın içeriğini buraya yazabilir, üst araç çubuğundan alt başlıklar, listeler ve görseller ekleyebilirsiniz.</p>\n<h2>Uygulama Adımları</h2>\n<p>İşletmeniz için öne çıkan noktaları detaylandırın.</p>',
      toc: [],
      service_link: { href: '/hizmetler/web-tasarim/', label: 'Web Tasarım Hizmetimiz' },
      related_slugs: [],
      tags: [],
      faqs: [],
      seo: {
        meta_title: '',
        meta_desc: '',
        focus_keyword: '',
        canonical: '',
        robots: 'index, follow',
        og_title: '',
        og_desc: '',
        og_image: '',
        ai_summary: '',
        seo_score: 85
      }
    };
  }

  function startNewBlogPost() {
    S.blog.mode = 'edit';
    S.blog.oldSlug = null;
    S.blog.sourceMode = false;
    S.blog.quickCatOpen = false;
    S.blog.i18nTab = 'en';
    S.blog.autoTranslate = S.store?.i18n?.auto_translate_blog !== false;
    S.blog.editingPost = createBlankPost();
    S.blog.seoAnalysis = { score: 82, checks: [] };
  }

  async function openBlogPostForEdit(slug) {
    try {
      toast('Yazı yükleniyor...');
      const res = await api('blog_get', { slug });
      syncBlogCatsFromApiPayload(res);
      markHistoryPush();
      S.view = 'blog';
      S.blog.mode = 'edit';
      S.blog.oldSlug = res.post.slug;
      S.blog.sourceMode = false;
      S.blog.quickCatOpen = false;
      S.blog.i18nTab = 'en';
      S.blog.autoTranslate = S.store?.i18n?.auto_translate_blog !== false;
      S.blog.editingPost = res.post;
      S.blog.seoAnalysis = res.seo_analysis || { score: 90, checks: [] };
      document.querySelectorAll('#zsNav .zs-nav-btn').forEach(b => b.classList.toggle('active', b.getAttribute('data-view') === 'blog'));
      document.getElementById('zsViewTitle').textContent = 'Blog Yazısını Düzenle';
      renderBlogView();
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function loadBlogList() {
    const res = await api('blog_list', {
      search: S.blog.search,
      category: S.blog.category,
      status: S.blog.status,
      page: S.blog.page,
      per_page: 24
    });
    syncBlogCatsFromApiPayload(res);
    S.blog.items = res.items || [];
    S.blog.total = res.total || 0;
    S.blog.page = res.page || 1;
    S.blog.totalPages = res.total_pages || 1;
    S.blog.categories = res.categories || {};
  }

  /* ======================== BULK BLOG TRANSLATION STUDIO (LIVE % BAR & PER-POST QUEUE) ======================== */
  function recalcI18nBatchCounters() {
    const B = S.i18nBatch;
    let done = 0;
    let pending = 0;
    let failed = 0;
    for (let i = 0; i < B.items.length; i++) {
      const it = B.items[i];
      if (it.error) {
        failed++;
      } else if (it.done && !it.retranslatePending) {
        done++;
      } else {
        pending++;
      }
    }
    B.totalPosts = B.items.length;
    B.translatedCount = done;
    B.pendingCount = pending;
    B.failedCount = failed;
    if (S.summary) {
      S.summary.translated_posts = done;
    }
  }

  function getLiveI18nOverrideFromDom() {
    const provEl = document.getElementById('i18nProvider');
    const deeplEl = document.getElementById('i18nDeeplKey');
    const oaiEl = document.getElementById('i18nOpenaiKey');
    const autoEl = document.getElementById('i18nAutoBlog');
    if (provEl || deeplEl || oaiEl) {
      S.store.i18n = S.store.i18n || {};
      if (provEl) S.store.i18n.provider = provEl.value || 'auto';
      if (deeplEl) S.store.i18n.deepl_api_key = deeplEl.value.trim();
      if (oaiEl) S.store.i18n.openai_api_key = oaiEl.value.trim();
      if (autoEl) S.store.i18n.auto_translate_blog = Boolean(autoEl.checked);
    }
    return S.store?.i18n ? {
      provider: S.store.i18n.provider || 'auto',
      deepl_api_key: S.store.i18n.deepl_api_key || '',
      openai_api_key: S.store.i18n.openai_api_key || '',
      openai_model: S.store.i18n.openai_model || 'gpt-4o-mini'
    } : {};
  }

  async function loadI18nTranslateQueue(forceReload) {
    const B = S.i18nBatch;
    if (B.loading) return;
    if (B.loaded && !forceReload) {
      refreshI18nStudioMount();
      return;
    }
    B.loading = true;
    refreshI18nStudioMount();
    try {
      const res = await api('blog_translate_queue', {});
      const prevMap = {};
      for (const oldIt of B.items) {
        prevMap[oldIt.slug] = oldIt;
      }
      B.items = (res.items || []).map(it => {
        const prev = prevMap[it.slug];
        return {
          slug: it.slug,
          title: it.title || it.slug,
          category: it.category || 'GENEL',
          date: it.date || '',
          done: Boolean(it.done),
          retranslatePending: prev ? Boolean(prev.retranslatePending) : false,
          justDone: prev ? Boolean(prev.justDone) : false,
          justDoneAt: prev ? (prev.justDoneAt || 0) : 0,
          error: prev ? (prev.error || '') : '',
          en_slug: it.en_slug || it.slug,
          de_slug: it.de_slug || it.slug,
          ru_slug: it.ru_slug || it.slug,
          en_title: it.en_title || ''
        };
      });
      B.loaded = true;
      recalcI18nBatchCounters();
    } catch (err) {
      toast(err.message || 'Çeviri kuyruğu okunamadı.', true);
    } finally {
      B.loading = false;
      refreshI18nStudioMount();
    }
  }

  function renderBlogRowI18nStatusHtml(slug, i18nObj, isTranslatingNow) {
    const blogSlug = getResolvedPageSlug('blog');
    if (isTranslatingNow) {
      return `<span class="zs-i18n-badge active">🔄 EN · DE · RU Çevriliyor...</span>`;
    }
    const enOk = i18nObj?.en?.title || i18nObj?.en_slug;
    const deOk = i18nObj?.de?.title || i18nObj?.de_slug;
    const ruOk = i18nObj?.ru?.title || i18nObj?.ru_slug;
    if (enOk && deOk && ruOk) {
      const enS = i18nObj?.en?.slug || i18nObj?.en_slug || slug;
      const deS = i18nObj?.de?.slug || i18nObj?.de_slug || slug;
      const ruS = i18nObj?.ru?.slug || i18nObj?.ru_slug || slug;
      return `
        <span class="zs-pill zs-pill-pub" style="font-size:10px;padding:1px 6px">✓ EN · DE · RU</span>
        <a href="${esc(resolveUrl('/en/' + blogSlug + '/' + enS + '/'))}" target="_blank" style="font-size:10.5px;color:var(--accent-bright)">EN↗</a>
        <a href="${esc(resolveUrl('/de/' + blogSlug + '/' + deS + '/'))}" target="_blank" style="font-size:10.5px;color:var(--accent-bright)">DE↗</a>
        <a href="${esc(resolveUrl('/ru/' + blogSlug + '/' + ruS + '/'))}" target="_blank" style="font-size:10.5px;color:var(--accent-bright)">RU↗</a>
      `;
    }
    return `<span class="zs-pill zs-pill-cat" style="font-size:10px;padding:1px 6px;opacity:.75" title="Ziyaret edildiğinde veya Çevir butonuna basıldığında EN/DE/RU dillerine çevrilir">🌍 Oto-Çeviri Aktif</span>`;
  }

  function patchVisibleBlogTableRow(slug, state, i18nMini) {
    if (!slug) return;
    if (Array.isArray(S.blog.items)) {
      const memRow = S.blog.items.find(x => x.slug === slug);
      if (memRow && state === 'done' && i18nMini) {
        memRow.i18n = {
          en: i18nMini.en || { slug, title: memRow.title },
          de: i18nMini.de || { slug, title: memRow.title },
          ru: i18nMini.ru || { slug, title: memRow.title }
        };
      }
    }
    const cell = document.querySelector(`[data-blog-i18n-status="${CSS.escape ? CSS.escape(slug) : slug}"]`);
    if (cell) {
      cell.innerHTML = renderBlogRowI18nStatusHtml(slug, i18nMini, state === 'active');
    }
    const rowBtn = document.querySelector(`[data-translate-slug="${CSS.escape ? CSS.escape(slug) : slug}"]`);
    if (rowBtn) {
      if (state === 'active') {
        rowBtn.disabled = true;
        rowBtn.textContent = '⏳ Çevriliyor...';
      } else {
        rowBtn.disabled = false;
        rowBtn.textContent = '🌍 Çevir';
      }
    }
  }

  async function translateSinglePostWithRetry(slug, forceFlag) {
    const payload = {
      slug,
      force: Boolean(forceFlag),
      compact: true,
      i18n_override: getLiveI18nOverrideFromDom()
    };
    try {
      return await api('blog_translate', payload);
    } catch (firstErr) {
      await new Promise(r => setTimeout(r, 750));
      return await api('blog_translate', payload);
    }
  }

  async function runBulkTranslationLoop() {
    const B = S.i18nBatch;
    if (B.workerActive) return;
    B.workerActive = true;

    try {
      while (B.state === 'running') {
        const nextItem = B.items.find(it => (!it.done || it.retranslatePending) && !it.error);
        if (!nextItem) {
          B.state = 'idle';
          B.forceAll = false;
          B.activeSlug = '';
          B.activeTitle = '';
          recalcI18nBatchCounters();
          refreshI18nStudioMount();
          if (B.failedCount > 0) {
            toast(`Toplu çeviri tamamlandı (${B.translatedCount}/${B.totalPosts}). ${B.failedCount} yazıda geçici hata oluştu; "Hatalıları Tekrar Dene" ile tamamlayabilirsiniz.`, true);
          } else {
            toast(`✓ Harika! Toplam ${B.translatedCount.toLocaleString('tr-TR')} blog yazısının tamamı EN · DE · RU dillerine çevrildi!`);
          }
          break;
        }

        B.activeSlug = nextItem.slug;
        B.activeTitle = nextItem.title;
        B.activeOrderNum = B.translatedCount + 1;
        refreshI18nStudioMount();
        patchVisibleBlogTableRow(nextItem.slug, 'active', null);

        const t0 = performance.now();
        try {
          const res = await translateSinglePostWithRetry(nextItem.slug, B.forceAll || nextItem.retranslatePending);
          const elapsed = Math.max(120, performance.now() - t0);
          B.avgMs = B.avgMs > 0 ? Math.round(B.avgMs * 0.72 + elapsed * 0.28) : Math.round(elapsed);

          nextItem.done = true;
          nextItem.retranslatePending = false;
          nextItem.justDone = true;
          nextItem.justDoneAt = Date.now();
          nextItem.error = '';
          nextItem.en_slug = res.i18n?.en?.slug || nextItem.slug;
          nextItem.de_slug = res.i18n?.de?.slug || nextItem.slug;
          nextItem.ru_slug = res.i18n?.ru?.slug || nextItem.slug;
          nextItem.en_title = res.i18n?.en?.title || nextItem.title;
          B.sessionDone++;

          patchVisibleBlogTableRow(nextItem.slug, 'done', res.i18n);
        } catch (err) {
          nextItem.error = err?.message || 'Bağlantı zaman aşımı';
          patchVisibleBlogTableRow(nextItem.slug, 'idle', null);
        }

        recalcI18nBatchCounters();
        if (B.state !== 'running') {
          B.activeSlug = '';
          B.activeTitle = '';
        }
        refreshI18nStudioMount();

        if (B.state === 'running') {
          await new Promise(r => setTimeout(r, 45));
        }
      }
    } finally {
      B.workerActive = false;
      if (B.state !== 'running') {
        B.activeSlug = '';
        B.activeTitle = '';
        refreshI18nStudioMount();
      }
    }
  }

  async function startBulkBlogTranslation(opts) {
    const options = opts || {};
    const B = S.i18nBatch;
    B.showOnBlogView = true;
    refreshI18nStudioMount();

    if (!B.loaded) {
      await loadI18nTranslateQueue(true);
    }

    if (options.retryErrorsOnly) {
      B.items.forEach(it => {
        if (it.error) it.error = '';
      });
      B.filter = 'all';
    } else if (options.forceAll) {
      B.forceAll = true;
      B.sessionDone = 0;
      B.items.forEach(it => {
        it.retranslatePending = true;
        it.justDone = false;
        it.error = '';
      });
    } else {
      // Clear previous errors when user explicitly clicks Start/Resume
      B.items.forEach(it => {
        if (!it.done && it.error) it.error = '';
      });
    }

    recalcI18nBatchCounters();
    if (B.pendingCount === 0) {
      toast('Tüm yayındaki blog yazıları zaten EN · DE · RU dillerine çevrilmiş durumda! İsterseniz "Tümünü Yeniden Çevir" butonunu kullanabilirsiniz.');
      refreshI18nStudioMount();
      return;
    }

    B.state = 'running';
    refreshI18nStudioMount();
    runBulkTranslationLoop();
  }

  function pauseBulkBlogTranslation() {
    S.i18nBatch.state = 'paused';
    refreshI18nStudioMount();
    toast('Toplu çeviri duraklatıldı. İstediğiniz an kaldığınız yerden devam edebilirsiniz.');
  }

  function stopBulkBlogTranslation() {
    const B = S.i18nBatch;
    B.state = 'idle';
    B.forceAll = false;
    B.activeSlug = '';
    B.activeTitle = '';
    B.items.forEach(it => { it.retranslatePending = false; });
    recalcI18nBatchCounters();
    refreshI18nStudioMount();
    toast('Toplu çeviri durduruldu.');
  }

  async function translateSingleQueueItem(slug) {
    const B = S.i18nBatch;
    const item = B.items.find(x => x.slug === slug);
    if (!item || B.activeSlug === slug) return;
    const prevActive = B.activeSlug;
    if (B.state !== 'running') {
      B.activeSlug = slug;
      B.activeTitle = item.title;
    }
    item.error = '';
    refreshI18nStudioMount();
    patchVisibleBlogTableRow(slug, 'active', null);
    try {
      const res = await translateSinglePostWithRetry(slug, true);
      item.done = true;
      item.retranslatePending = false;
      item.justDone = true;
      item.justDoneAt = Date.now();
      item.en_slug = res.i18n?.en?.slug || slug;
      item.de_slug = res.i18n?.de?.slug || slug;
      item.ru_slug = res.i18n?.ru?.slug || slug;
      item.en_title = res.i18n?.en?.title || item.title;
      recalcI18nBatchCounters();
      patchVisibleBlogTableRow(slug, 'done', res.i18n);
      toast(`"${item.title}" EN · DE · RU dillerine çevrildi.`);
    } catch (err) {
      item.error = err?.message || 'Çeviri hatası';
      recalcI18nBatchCounters();
      patchVisibleBlogTableRow(slug, 'idle', null);
      toast(item.error, true);
    } finally {
      if (B.state !== 'running' && B.activeSlug === slug) {
        B.activeSlug = prevActive || '';
        B.activeTitle = '';
      }
      refreshI18nStudioMount();
    }
  }

  function formatEtaLabel(pendingCount, avgMs) {
    if (!pendingCount || !avgMs || avgMs <= 0) return '';
    const totalSec = Math.max(1, Math.round((pendingCount * avgMs) / 1000));
    if (totalSec < 60) return `~${totalSec} sn kaldı`;
    const mins = Math.floor(totalSec / 60);
    const secs = totalSec % 60;
    if (mins < 60) return `~${mins} dk ${secs > 0 ? secs + ' sn ' : ''}kaldı`;
    const hrs = Math.floor(mins / 60);
    const remM = mins % 60;
    return `~${hrs} sa ${remM} dk kaldı`;
  }

  function mountOrUpdateI18nStudio(hostEl, opts) {
    if (!hostEl) return;
    const B = S.i18nBatch;
    const showCacheClear = Boolean(opts?.showCacheClear);
    const allowClose = Boolean(opts?.allowClose);

    if (!hostEl.querySelector('.zs-i18n-studio')) {
      hostEl.innerHTML = `
        <div class="zs-i18n-studio">
          <div class="zs-i18n-top">
            <div class="zs-i18n-status-title">
              <span class="zs-i18n-dot" data-i18n-dot></span>
              <span data-i18n-status-text>Çeviri arşivi kontrol ediliyor...</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
              <span class="zs-i18n-pct" data-i18n-pct>%0</span>
              ${allowClose ? `<button type="button" class="zs-btn zs-btn-sm" data-i18n-hide-panel title="Paneli Gizle">✕</button>` : ''}
            </div>
          </div>

          <div class="zs-i18n-bar-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-i18n-track>
            <div class="zs-i18n-bar-fill" data-i18n-fill></div>
          </div>

          <div class="zs-i18n-metrics" data-i18n-metrics></div>
          <div data-i18n-active-slot></div>
          <div class="zs-i18n-controls" data-i18n-controls></div>

          <div class="zs-i18n-queue-box">
            <div class="zs-i18n-queue-toolbar">
              <div class="zs-i18n-filter-pills" data-i18n-filters></div>
              <input type="search" class="zs-i18n-mini-search" data-i18n-search placeholder="Yazı başlığı ara..." value="${esc(B.search)}"/>
            </div>
            <div class="zs-i18n-queue-list" data-i18n-list></div>
          </div>
        </div>
      `;

      const searchInp = hostEl.querySelector('[data-i18n-search]');
      if (searchInp) {
        searchInp.addEventListener('input', () => {
          B.search = searchInp.value;
          B.visibleLimit = 80;
          refreshI18nStudioMount();
        });
      }

      const hideBtn = hostEl.querySelector('[data-i18n-hide-panel]');
      if (hideBtn) {
        hideBtn.onclick = () => {
          B.showOnBlogView = false;
          hostEl.innerHTML = '';
        };
      }

      hostEl.addEventListener('click', async ev => {
        const actEl = ev.target.closest('[data-i18n-act]');
        if (!actEl) return;
        const act = actEl.getAttribute('data-i18n-act');
        if (act === 'start') {
          startBulkBlogTranslation({ forceAll: false });
        } else if (act === 'force_all') {
          if (confirm(`Yayındaki tüm ${B.totalPosts.toLocaleString('tr-TR')} blog yazısını sıfırdan EN · DE · RU dillerine yeniden çevirmek istiyor musunuz?`)) {
            startBulkBlogTranslation({ forceAll: true });
          }
        } else if (act === 'pause') {
          pauseBulkBlogTranslation();
        } else if (act === 'resume') {
          startBulkBlogTranslation({ forceAll: B.forceAll });
        } else if (act === 'stop') {
          stopBulkBlogTranslation();
        } else if (act === 'retry_errors') {
          startBulkBlogTranslation({ retryErrorsOnly: true });
        } else if (act === 'clear_cache') {
          actEl.disabled = true;
          try {
            const res = await api('i18n_cache_clear', {});
            toast(res.message);
          } catch (err) {
            toast(err.message, true);
          }
          actEl.disabled = false;
        } else if (act === 'filter') {
          B.filter = actEl.getAttribute('data-filter-val') || 'all';
          B.visibleLimit = 80;
          refreshI18nStudioMount();
        } else if (act === 'more') {
          B.visibleLimit += 120;
          refreshI18nStudioMount();
        } else if (act === 'single') {
          const sl = actEl.getAttribute('data-slug') || '';
          if (sl) translateSingleQueueItem(sl);
        }
      });
    }

    const dotEl = hostEl.querySelector('[data-i18n-dot]');
    const statusTxtEl = hostEl.querySelector('[data-i18n-status-text]');
    const pctEl = hostEl.querySelector('[data-i18n-pct]');
    const trackEl = hostEl.querySelector('[data-i18n-track]');
    const fillEl = hostEl.querySelector('[data-i18n-fill]');
    const metricsEl = hostEl.querySelector('[data-i18n-metrics]');
    const activeSlot = hostEl.querySelector('[data-i18n-active-slot]');
    const ctrlsEl = hostEl.querySelector('[data-i18n-controls]');
    const filtersEl = hostEl.querySelector('[data-i18n-filters]');
    const listEl = hostEl.querySelector('[data-i18n-list]');

    if (B.loading && !B.loaded) {
      statusTxtEl.textContent = 'Blog çeviri kuyruğu taranıyor...';
      pctEl.textContent = '...';
      listEl.innerHTML = `<div style="padding:14px;text-align:center;color:var(--text-muted);font-size:12px">⏳ Makale arşivi taranıyor...</div>`;
      return;
    }

    const total = B.totalPosts;
    const done = B.translatedCount;
    const pending = B.pendingCount;
    const rawPct = total > 0 ? (done / total) * 100 : 100;
    const pctStr = total === 0
      ? '100'
      : (done === total ? '100' : (rawPct > 0 && rawPct < 0.1 ? '0.1' : rawPct.toFixed(rawPct < 10 ? 1 : 1).replace(/\.0$/, '')));

    dotEl.className = 'zs-i18n-dot'
      + (B.state === 'running' ? ' is-running' : (B.state === 'paused' ? ' is-paused' : (pending === 0 ? ' is-done' : '')));

    if (B.state === 'running') {
      statusTxtEl.textContent = `Toplu Çeviri Çalışıyor (EN · DE · RU) — ${done.toLocaleString('tr-TR')} / ${total.toLocaleString('tr-TR')}`;
    } else if (B.state === 'paused') {
      statusTxtEl.textContent = `Toplu Çeviri Duraklatıldı — ${done.toLocaleString('tr-TR')} / ${total.toLocaleString('tr-TR')} Tamamlandı`;
    } else if (pending === 0 && total > 0) {
      statusTxtEl.textContent = `Tüm Blog Arşivi Çevrildi (${total.toLocaleString('tr-TR')} / ${total.toLocaleString('tr-TR')} Makale)`;
    } else {
      statusTxtEl.textContent = `Blog Çeviri Durumu: ${done.toLocaleString('tr-TR')} Çevrildi · ${pending.toLocaleString('tr-TR')} Bekliyor`;
    }

    pctEl.textContent = `%${pctStr}`;
    trackEl.setAttribute('aria-valuenow', String(Math.round(rawPct)));
    fillEl.style.width = `${Math.min(100, Math.max(0, rawPct)).toFixed(2)}%`;
    fillEl.classList.toggle('is-running', B.state === 'running');

    const speedTxt = B.avgMs > 0 ? `⚡ ${(B.avgMs / 1000).toFixed(1)} sn/yazı` : '';
    const etaTxt = B.state === 'running' ? formatEtaLabel(pending, B.avgMs) : '';
    metricsEl.innerHTML = `
      <span>✓ Çevrilen: <strong>${done.toLocaleString('tr-TR')}</strong> · ⏳ Sırada: <strong>${pending.toLocaleString('tr-TR')}</strong> · Toplam: <strong>${total.toLocaleString('tr-TR')}</strong>${B.sessionDone > 0 ? ` · Bu oturumda: <strong style="color:#34d399">+${B.sessionDone.toLocaleString('tr-TR')}</strong>` : ''}${B.failedCount > 0 ? ` · <strong style="color:#fca5a5">⚠ Hata: ${B.failedCount}</strong>` : ''}</span>
      <span>${[speedTxt, etaTxt].filter(Boolean).join(' · ')}</span>
    `;

    if (B.activeSlug) {
      activeSlot.innerHTML = `
        <div class="zs-i18n-active-card">
          <div class="zs-i18n-active-left">
            <span class="zs-i18n-spinner" aria-hidden="true"></span>
            <div class="zs-i18n-active-copy">
              <small>🔄 Şu An Çevriliyor (${B.activeOrderNum ? '#' + B.activeOrderNum.toLocaleString('tr-TR') + ' / ' + total.toLocaleString('tr-TR') : 'EN · DE · RU'})</small>
              <strong title="${esc(B.activeTitle)}">${esc(B.activeTitle)}</strong>
            </div>
          </div>
          <span class="zs-i18n-badge active">TR → EN · DE · RU</span>
        </div>
      `;
    } else {
      activeSlot.innerHTML = '';
    }

    const ctrlBtns = [];
    if (B.state === 'running') {
      ctrlBtns.push(`<button type="button" class="zs-btn" data-i18n-act="pause">⏸ Duraklat</button>`);
      ctrlBtns.push(`<button type="button" class="zs-btn zs-btn-danger" data-i18n-act="stop">⏹ Durdur</button>`);
    } else if (B.state === 'paused') {
      ctrlBtns.push(`<button type="button" class="zs-btn zs-btn-ai" data-i18n-act="resume">▶ Kaldığı Yerden Devam Et (${pending.toLocaleString('tr-TR')} Yazı)</button>`);
      ctrlBtns.push(`<button type="button" class="zs-btn" data-i18n-act="stop">⏹ Sıfırla</button>`);
    } else {
      if (pending > 0) {
        ctrlBtns.push(`<button type="button" class="zs-btn zs-btn-ai" data-i18n-act="start">🌍 Eksik Yazıları Toplu Çevir (${pending.toLocaleString('tr-TR')} Yazı)</button>`);
      }
      if (total > 0) {
        ctrlBtns.push(`<button type="button" class="zs-btn" data-i18n-act="force_all">${pending === 0 ? '↻ Tüm Yazıları Yeniden Çevir' : '↻ Tümünü Baştan Çevir'}</button>`);
      }
      if (B.failedCount > 0) {
        ctrlBtns.push(`<button type="button" class="zs-btn zs-btn-ai" data-i18n-act="retry_errors">🔁 Hatalı Olanları Tekrar Dene (${B.failedCount})</button>`);
      }
    }
    if (showCacheClear) {
      ctrlBtns.push(`<button type="button" class="zs-btn" data-i18n-act="clear_cache">↻ Çeviri Önbelleğini Temizle</button>`);
    }
    ctrlsEl.innerHTML = ctrlBtns.join('');

    filtersEl.innerHTML = `
      <button type="button" class="zs-i18n-filter-btn ${B.filter === 'all' ? 'active' : ''}" data-i18n-act="filter" data-filter-val="all">Tümü (${total.toLocaleString('tr-TR')})</button>
      <button type="button" class="zs-i18n-filter-btn ${B.filter === 'done' ? 'active' : ''}" data-i18n-act="filter" data-filter-val="done">✓ Çevrilen (${done.toLocaleString('tr-TR')})</button>
      <button type="button" class="zs-i18n-filter-btn ${B.filter === 'pending' ? 'active' : ''}" data-i18n-act="filter" data-filter-val="pending">⏳ Bekleyen (${pending.toLocaleString('tr-TR')})</button>
      ${B.failedCount > 0 ? `<button type="button" class="zs-i18n-filter-btn ${B.filter === 'error' ? 'active' : ''}" data-i18n-act="filter" data-filter-val="error">⚠ Hatalı (${B.failedCount})</button>` : ''}
    `;

    // Order so user immediately sees without scrolling:
    // 1) Currently active item (🔄 Çevriliyor...)
    // 2) Latest 2 newly translated items (or 2 already-translated items when idle)
    // 3) Next upcoming pending items (⏳ Sırada)
    // 4) Remaining items in archive order
    const q = (B.search || '').trim().toLocaleLowerCase('tr-TR');
    const filtered = B.items.filter(it => {
      const isDone = it.done && !it.retranslatePending && !it.error;
      if (B.filter === 'done' && !isDone) return false;
      if (B.filter === 'pending' && (isDone || it.error)) return false;
      if (B.filter === 'error' && !it.error) return false;
      if (q && !(it.title.toLocaleLowerCase('tr-TR').includes(q) || it.slug.toLowerCase().includes(q))) return false;
      return true;
    });

    if (B.filter === 'all' && !q) {
      const recentJustSlugs = new Set(
        filtered
          .filter(x => x.justDone && x.slug !== B.activeSlug)
          .sort((a, b) => (b.justDoneAt || 0) - (a.justDoneAt || 0))
          .slice(0, 2)
          .map(x => x.slug)
      );
      const previewDoneSlugs = new Set();
      if (recentJustSlugs.size === 0) {
        for (const x of filtered) {
          if (x.done && !x.retranslatePending && !x.error && x.slug !== B.activeSlug) {
            previewDoneSlugs.add(x.slug);
            if (previewDoneSlugs.size >= 2) break;
          }
        }
      }
      filtered.sort((a, b) => {
        const rankOf = it => {
          if (it.slug === B.activeSlug) return 50;
          if (recentJustSlugs.has(it.slug)) return 40;
          if (previewDoneSlugs.has(it.slug)) return 35;
          if (it.error) return 30;
          const isDone = it.done && !it.retranslatePending && !it.error;
          return isDone ? 10 : 20;
        };
        const rDiff = rankOf(b) - rankOf(a);
        if (rDiff !== 0) return rDiff;
        if (recentJustSlugs.has(a.slug) && recentJustSlugs.has(b.slug)) {
          return (b.justDoneAt || 0) - (a.justDoneAt || 0);
        }
        return 0;
      });
    } else {
      filtered.sort((a, b) => {
        const aActive = a.slug === B.activeSlug ? 1 : 0;
        const bActive = b.slug === B.activeSlug ? 1 : 0;
        if (aActive !== bActive) return bActive - aActive;
        const aJust = a.justDone ? (a.justDoneAt || 1) : 0;
        const bJust = b.justDone ? (b.justDoneAt || 1) : 0;
        if (aJust !== bJust) return bJust - aJust;
        return 0;
      });
    }

    const blogSlug = getResolvedPageSlug('blog');
    const prevScroll = listEl.scrollTop;
    const slice = filtered.slice(0, B.visibleLimit);

    if (slice.length === 0) {
      listEl.innerHTML = `<div style="padding:14px;text-align:center;color:var(--text-dim);font-size:12px">Bu filtrede gösterilecek blog yazısı yok.</div>`;
    } else {
      const rowsHtml = slice.map(it => {
        const isAct = it.slug === B.activeSlug;
        const isDone = it.done && !it.retranslatePending && !it.error;
        let rowCls = 'zs-i18n-row';
        let badgeHtml = '';
        let rightHtml = '';

        if (isAct) {
          rowCls += ' is-active';
          badgeHtml = `<span class="zs-i18n-badge active">🔄 Çevriliyor...</span>`;
        } else if (it.error) {
          rowCls += ' is-error';
          badgeHtml = `<span class="zs-i18n-badge err" title="${esc(it.error)}">⚠ Hata</span>`;
          rightHtml = `<button type="button" class="zs-btn zs-btn-sm" data-i18n-act="single" data-slug="${esc(it.slug)}">Tekrar Dene</button>`;
        } else if (isDone) {
          if (it.justDone) rowCls += ' is-just-done';
          badgeHtml = `<span class="zs-i18n-badge done">${it.justDone ? '✓ Şimdi Çevrildi' : '✓ Çevrildi'}</span>`;
          rightHtml = `
            <span class="zs-i18n-lang-links">
              <a href="${esc(resolveUrl('/en/' + blogSlug + '/' + (it.en_slug || it.slug) + '/'))}" target="_blank">EN↗</a>
              <a href="${esc(resolveUrl('/de/' + blogSlug + '/' + (it.de_slug || it.slug) + '/'))}" target="_blank">DE↗</a>
              <a href="${esc(resolveUrl('/ru/' + blogSlug + '/' + (it.ru_slug || it.slug) + '/'))}" target="_blank">RU↗</a>
            </span>
          `;
        } else {
          badgeHtml = `<span class="zs-i18n-badge pending">⏳ Sırada</span>`;
          if (B.state !== 'running') {
            rightHtml = `<button type="button" class="zs-btn zs-btn-sm" style="padding:2px 8px;font-size:11px" data-i18n-act="single" data-slug="${esc(it.slug)}">Çevir</button>`;
          }
        }

        return `
          <div class="${rowCls}">
            <div class="zs-i18n-row-main">
              ${badgeHtml}
              <div class="zs-i18n-row-title">
                <strong title="${esc(it.title)}">${esc(it.title)}</strong>
                <span>/${esc(blogSlug)}/${esc(it.slug)}/${it.en_title && isDone ? ' → ' + esc(it.en_title) : ''}</span>
              </div>
            </div>
            <div class="zs-i18n-row-actions">${rightHtml}</div>
          </div>
        `;
      }).join('');

      const moreHtml = filtered.length > slice.length
        ? `<div style="text-align:center;padding:6px 0"><button type="button" class="zs-btn zs-btn-sm" data-i18n-act="more">Daha Fazla Göster (${(filtered.length - slice.length).toLocaleString('tr-TR')} yazı daha)</button></div>`
        : '';

      listEl.innerHTML = rowsHtml + moreHtml;
      listEl.scrollTop = prevScroll;
    }

    const blogTopBtn = document.getElementById('btnBatchTranslatePosts');
    if (blogTopBtn) {
      if (B.state === 'running') {
        blogTopBtn.textContent = `🔄 Çevriliyor %${pctStr} (${done.toLocaleString('tr-TR')}/${total.toLocaleString('tr-TR')})`;
      } else if (B.state === 'paused') {
        blogTopBtn.textContent = `⏸ Duraklatıldı %${pctStr} (${done.toLocaleString('tr-TR')}/${total.toLocaleString('tr-TR')})`;
      } else {
        blogTopBtn.textContent = pending > 0
          ? `🌍 Mevcut Yazıları Toplu Çevir (${pending.toLocaleString('tr-TR')} Kalan)`
          : `✓ Tüm Yazılar Çevrildi (%100)`;
      }
    }
  }

  function refreshI18nStudioMount() {
    const settingsHost = document.getElementById('i18nBulkStudioHost');
    if (settingsHost) {
      mountOrUpdateI18nStudio(settingsHost, { showCacheClear: true, allowClose: false });
    }
    const blogHost = document.getElementById('blogBulkStudioHost');
    if (blogHost) {
      if (S.i18nBatch.showOnBlogView || S.i18nBatch.state !== 'idle') {
        mountOrUpdateI18nStudio(blogHost, { showCacheClear: false, allowClose: S.i18nBatch.state === 'idle' });
      } else {
        blogHost.innerHTML = '';
      }
    }
  }

  function renderBlogModeTabBarHtml(activeOverride) {
    const qCnt = Array.isArray(S.newsBotQueue) ? S.newsBotQueue.length : 0;
    const pendComCnt = Array.isArray(S.blogComments) ? S.blogComments.filter(c => (c.status || 'pending') === 'pending').length : 0;
    const catCnt = getClientBlogCategoriesTree().length;
    const curMode = activeOverride || (S.view === 'blog_categories' ? 'categories' : (S.blog.mode || 'list'));
    return `
      <div class="zs-blog-mode-tabs">
        <div class="zs-tabs">
          <button type="button" class="zs-tab ${curMode === 'list' ? 'active' : ''}" data-blog-mode-tab="list">
            📚 Blog Arşivi
          </button>
          <button type="button" class="zs-tab ${curMode === 'categories' ? 'active' : ''}" data-blog-mode-tab="categories" id="blogTabCategoriesBtn">
            📂 Kategoriler${catCnt > 0 ? `<span class="zs-nb-count-pill">${catCnt}</span>` : ''}
          </button>
          <button type="button" class="zs-tab ${curMode === 'news_bot' ? 'active' : ''}" data-blog-mode-tab="news_bot" id="blogTabNewsBotBtn">
            📰 Google News Otopilot &amp; Onay Kuyruğu${qCnt > 0 ? `<span class="zs-nb-count-pill">⏳ ${qCnt} Onay Bekliyor</span>` : ''}
          </button>
          <button type="button" class="zs-tab ${curMode === 'seo_links' ? 'active' : ''}" data-blog-mode-tab="seo_links" id="blogTabSeoLinksBtn">
            🎧 Sesli Dinleme &amp; Oto İç Linkleme
          </button>
          <button type="button" class="zs-tab ${curMode === 'authors' ? 'active' : ''}" data-blog-mode-tab="authors" id="blogTabAuthorsBtn">
            ✍️ Uzman Yazarlar (E-E-A-T)
          </button>
          <button type="button" class="zs-tab ${curMode === 'comments' ? 'active' : ''}" data-blog-mode-tab="comments" id="blogTabCommentsBtn">
            💬 Soru-Cevap &amp; Yorumlar${pendComCnt > 0 ? `<span class="zs-nb-count-pill">🔔 ${pendComCnt} Bekliyor</span>` : ''}
          </button>
          <button type="button" class="zs-tab ${curMode === 'settings' ? 'active' : ''}" data-blog-mode-tab="settings">
            ⚙️ Blog Vitrin Ayarları
          </button>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          ${curMode !== 'news_bot' ? `
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-blog-mode-tab="news_bot">
              ⚡ Google News'ten Otomatik Haber Çek
            </button>
          ` : `
            <button type="button" class="zs-btn zs-btn-sm" data-blog-mode-tab="list">
              ← Tüm Blog Yazılarına Dön
            </button>
          `}
        </div>
      </div>
    `;
  }

  function bindBlogModeTabBarEvents() {
    ROOT.querySelectorAll('[data-blog-mode-tab]').forEach(btn => {
      btn.onclick = () => {
        const nextMode = btn.getAttribute('data-blog-mode-tab') || 'list';
        if (nextMode === 'categories') {
          S.blogCats.editingId = null;
          navigateTo('blog_categories');
          return;
        }
        if (S.view !== 'blog') {
          navigateTo('blog', () => { S.blog.mode = nextMode; });
          return;
        }
        S.blog.mode = nextMode;
        renderBlogView();
      };
    });
  }

  async function loadBlogCategoriesFromApi() {
    S.blogCats.loading = true;
    try {
      const res = await api('blog_categories_list', {});
      syncBlogCatsFromApiPayload(res);
    } finally {
      S.blogCats.loading = false;
    }
  }

  async function renderBlogCategoriesView(skipNetwork) {
    syncAdminUrl('replace');
    if (!skipNetwork) {
      if (!S.blogCats.loaded) {
        ROOT.innerHTML = `${renderBlogModeTabBarHtml('categories')}<div class="zs-card"><p style="color:var(--text-muted)">Blog kategorileri yükleniyor...</p></div>`;
      }
      try {
        await loadBlogCategoriesFromApi();
      } catch (err) {
        ROOT.innerHTML = `${renderBlogModeTabBarHtml('categories')}<div class="zs-card" style="color:#fca5a5">${esc(err.message)}</div>`;
        bindBlogModeTabBarEvents();
        return;
      }
    }

    const allCats = getClientBlogCategoriesTree();
    const defCat = getDefaultBlogCategoryClient();
    const blogSlug = S.blogCats.blogSlug || getResolvedPageSlug('blog');
    const siteName = S.store?.site?.name || 'ZEYNART';

    const validIdsSet = new Set(allCats.map(c => c.id));
    S.blogCats.selectedIds = (S.blogCats.selectedIds || []).filter(id => validIdsSet.has(id));

    let editCat = S.blogCats.editingId ? (allCats.find(c => c.id === S.blogCats.editingId) || null) : null;
    if (S.blogCats.editingId && !editCat) {
      S.blogCats.editingId = null;
    }
    const isEdit = Boolean(editCat);
    const forbiddenParentIds = new Set(isEdit ? [editCat.id].concat(editCat.descendant_ids || []) : []);
    const nextSuggestedOrder = isEdit ? Number(editCat.order || 1) : (allCats.length + 1);
    const rootCount = allCats.filter(c => !c.parent_id).length;
    const subCount = allCats.length - rootCount;

    const getFilteredCats = () => {
      const q = (S.blogCats.search || '').trim().toLocaleLowerCase('tr-TR');
      if (!q) return allCats;
      return allCats.filter(c =>
        c.name.toLocaleLowerCase('tr-TR').includes(q)
        || c.slug.toLocaleLowerCase('tr-TR').includes(q)
        || (c.parent_name || '').toLocaleLowerCase('tr-TR').includes(q)
        || (c.description || '').toLocaleLowerCase('tr-TR').includes(q)
      );
    };

    const siblingsByParent = {};
    allCats.forEach(c => {
      const pk = c.parent_id || '__root__';
      if (!siblingsByParent[pk]) siblingsByParent[pk] = [];
      siblingsByParent[pk].push(c.id);
    });

    const buildRowsHtml = list => {
      if (!list.length) {
        return `<tr><td colspan="7" style="text-align:center;padding:34px;color:var(--text-muted)">Aramanızla eşleşen blog kategorisi bulunamadı.</td></tr>`;
      }
      const selSet = new Set(S.blogCats.selectedIds || []);
      return list.map(c => {
        const pk = c.parent_id || '__root__';
        const sibs = siblingsByParent[pk] || [];
        const sibPos = sibs.indexOf(c.id);
        const canUp = sibPos > 0;
        const canDown = sibPos >= 0 && sibPos < sibs.length - 1;
        const isChecked = selSet.has(c.id);
        const isEditingRow = isEdit && editCat.id === c.id;
        const hasSeo = Boolean(c.meta_title && c.meta_desc);
        const postCnt = Number(c.total_post_count ?? c.post_count ?? 0);
        const directCnt = Number(c.post_count ?? 0);
        return `
          <tr style="${isEditingRow ? 'background:rgba(20,184,166,.14)' : ''}">
            <td style="width:38px;text-align:center">
              <input type="checkbox" class="bc-row-chk" value="${esc(c.id)}"
                ${isChecked ? 'checked' : ''}
                ${c.is_default ? 'disabled title="Varsayılan kategori doğrudan silinemez; önce başka bir kategoriyi varsayılan yapın."' : ''}
                style="width:16px;height:16px;accent-color:var(--accent);cursor:${c.is_default ? 'not-allowed' : 'pointer'}"/>
            </td>
            <td style="width:94px">
              <div class="zs-cat-order-btns">
                <button type="button" class="zs-cat-order-btn" data-bc-move="${esc(c.id)}:up" ${canUp ? '' : 'disabled'} title="Yukarı Taşı">↑</button>
                <button type="button" class="zs-cat-order-btn" data-bc-move="${esc(c.id)}:down" ${canDown ? '' : 'disabled'} title="Aşağı Taşı">↓</button>
                <span style="font-size:11.5px;color:var(--text-dim);margin-left:3px">#${Number(c.order || 1)}</span>
              </div>
            </td>
            <td>
              <div style="padding-left:${Math.min(4, c.depth || 0) * 18}px">
                <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                  <strong style="font-size:13.5px;color:#fff;cursor:pointer" data-bc-edit="${esc(c.id)}">
                    ${c.depth > 0 ? `<span style="color:var(--accent-bright);font-weight:700">${esc(c.indent_prefix)}</span>` : ''}${esc(c.name)}
                  </strong>
                  ${c.is_default ? `<span class="zs-cat-default-badge">★ Varsayılan</span>` : ''}
                  ${c.depth > 0 ? `<span class="zs-cat-sub-badge">↳ Alt Kategori</span>` : ''}
                  <span style="font-size:10.5px;color:${hasSeo ? '#34d399' : '#94a3b8'}" title="${hasSeo ? 'Özel Meta Title ve Meta Description tanımlı' : 'Otomatik SEO şablonu aktif'}">${hasSeo ? '● SEO Tam' : '○ Oto SEO'}</span>
                </div>
                ${c.description ? `<div style="font-size:12px;color:var(--text-muted);margin-top:3px;max-width:440px;line-height:1.45">${esc(c.description.length > 105 ? c.description.slice(0, 105) + '…' : c.description)}</div>` : ''}
              </div>
            </td>
            <td>
              <a href="${esc(resolveUrl('/' + blogSlug + '/kategori/' + c.slug + '/'))}" target="_blank" rel="noopener"
                style="font-size:12px;color:var(--accent-bright);font-family:ui-monospace,Consolas,monospace;text-decoration:none"
                title="Kategori sayfasını yeni sekmede aç">
                /${esc(blogSlug)}/kategori/${esc(c.slug)}/ ↗
              </a>
            </td>
            <td>
              ${c.parent_name
                ? `<span class="zs-pill zs-pill-cat">${esc(c.parent_name)}</span>`
                : `<span style="font-size:12px;color:var(--text-dim)">Ana Kategori</span>`}
            </td>
            <td style="text-align:center">
              <button type="button" class="zs-cat-count-btn" data-bc-open-posts="${esc(c.name)}" title="Bu kategorideki blog yazılarını listele${directCnt !== postCnt ? ' (Doğrudan: ' + directCnt + ', Alt kategorilerle: ' + postCnt + ')' : ''}">
                📄 ${Number(postCnt).toLocaleString('tr-TR')} Yazı
              </button>
            </td>
            <td style="text-align:right;white-space:nowrap">
              <div style="display:inline-flex;align-items:center;gap:5px;justify-content:flex-end">
                <button type="button" class="zs-btn zs-btn-sm" data-bc-edit="${esc(c.id)}">Düzenle</button>
                ${!c.is_default ? `<button type="button" class="zs-btn zs-btn-sm" data-bc-set-def="${esc(c.id)}" title="Varsayılan Kategori Olarak Belirle" style="padding:5px 8px">★</button>` : ''}
                <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-bc-del="${esc(c.id)}" ${c.is_default ? 'disabled title="Varsayılan kategori silinemez; önce başka bir kategoriyi varsayılan seçin."' : ''}>Sil</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    };

    const filteredInitial = getFilteredCats();

    ROOT.innerHTML = `
      ${renderBlogModeTabBarHtml('categories')}

      <div class="zs-cat-default-bar">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <span class="zs-cat-default-badge" style="font-size:12px;padding:4px 10px">★ Varsayılan Kategori</span>
          <div style="font-size:12.5px;color:var(--text-muted)">
            Kategori seçilmeden oluşturulan blog yazıları ve silinen kategorilerin tekil yazıları otomatik olarak <strong style="color:#fff">${esc(defCat.name)}</strong> kategorisine atanır.
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <select id="bcDefaultCatSelect" class="zs-select" style="width:auto;min-width:220px;padding:7px 11px">
            ${allCats.map(c => `<option value="${esc(c.id)}" ${c.id === defCat.id ? 'selected' : ''}>${esc(c.indent_prefix + c.name)}${c.is_default ? ' (Aktif Varsayılan)' : ''}</option>`).join('')}
          </select>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="bcSaveDefaultBtn">Varsayılan Olarak Belirle</button>
        </div>
      </div>

      <div class="zs-cat-studio-layout">
        <!-- LEFT STICKY CREATE / EDIT PANEL -->
        <div class="zs-card zs-cat-form-sticky">
          <div class="zs-card-head" style="margin-bottom:14px">
            <div>
              <span class="zs-badge-pill">${isEdit ? 'MEVCUT KATEGORİYİ DÜZENLE' : 'YENİ BLOG KATEGORİSİ EKLE'}</span>
              <h3 style="margin:6px 0 0">${isEdit ? esc(editCat.name) : 'Yeni Kategori Oluştur'}</h3>
            </div>
            ${isEdit ? `<button type="button" class="zs-btn zs-btn-sm" id="bcCancelEditBtn">↺ Yeni Ekle</button>` : ''}
          </div>

          <div class="zs-field">
            <label>Kategori Adı *</label>
            <input id="bcName" class="zs-input" placeholder="Örn: Kurumsal SEO &amp; GEO" value="${esc(isEdit ? editCat.name : '')}"/>
          </div>

          <div class="zs-field">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;margin-bottom:5px">
              <label style="margin:0">URL Adresi (Slug) <span style="color:var(--accent-bright)">/${esc(blogSlug)}/kategori/<b id="bcSlugPreview">${esc(isEdit ? editCat.slug : 'yeni-kategori')}</b>/</span></label>
              <button type="button" class="zs-btn zs-btn-sm" id="bcRegenSlugBtn" style="padding:2px 8px;font-size:11px">↻ Addan Üret</button>
            </div>
            <input id="bcSlug" class="zs-input" placeholder="Kategori adından otomatik oluşturulur" value="${esc(isEdit ? editCat.slug : '')}"/>
            <div id="bcSlugRedirectHint" style="display:none;margin-top:6px;padding:7px 10px;border-radius:10px;background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.32);font-size:11.5px;color:#6ee7b7">
              ⚡ URL adresi değiştiğinde TR · EN · DE · RU dillerinde eski kategori adresinden yeni adrese otomatik <strong>301 yönlendirmesi</strong> tanımlanır ve bağlı yazılar korunur.
            </div>
          </div>

          <div class="zs-grid-2">
            <div class="zs-field">
              <label>Üst Kategori (Ana / Alt)</label>
              <select id="bcParentId" class="zs-select">
                <option value="">— Ana Kategori (Yok) —</option>
                ${allCats.filter(c => !forbiddenParentIds.has(c.id)).map(c => `
                  <option value="${esc(c.id)}" ${(isEdit && editCat.parent_id === c.id) ? 'selected' : ''}>${esc(c.indent_prefix + c.name)}</option>
                `).join('')}
              </select>
            </div>
            <div class="zs-field">
              <label>Görüntülenme Sırası</label>
              <input type="number" id="bcOrder" class="zs-input" min="1" max="999" value="${nextSuggestedOrder}"/>
            </div>
          </div>

          <div class="zs-field">
            <label>Kategori Açıklaması (Kategori sayfası üst tanıtım metni)</label>
            <textarea id="bcDescription" class="zs-textarea" style="min-height:68px" placeholder="Bu kategoride yer alan rehber ve içerikleri tanıtan kısa kurumsal açıklama...">${esc(isEdit ? (editCat.description || '') : '')}</textarea>
          </div>

          <div style="padding:10px 12px;border-radius:10px;background:rgba(5,12,17,.74);border:1px solid var(--border-subtle);margin-bottom:14px">
            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="bcIsDefault" ${(isEdit && editCat.is_default) ? 'checked disabled' : ''}/>
              <span><strong>Varsayılan blog kategorisi olarak belirle</strong></span>
            </label>
          </div>

          <div style="padding:12px;border-radius:12px;background:rgba(5,12,17,.82);border:1px solid var(--border-subtle);margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
              <strong style="font-size:12.5px;color:#5eead4">🔍 Kategori SEO Ayarları</strong>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="bcAutoSeoBtn" style="font-size:11px;padding:3px 9px">✦ Otomatik Doldur</button>
            </div>
            <div class="zs-field">
              <label>SEO Başlığı (Meta Title) <span id="bcMetaTitleLen">0/65</span></label>
              <input id="bcMetaTitle" class="zs-input" placeholder="Boşsa kategori adı ve site başlığı kullanılır" value="${esc(isEdit ? (editCat.meta_title || '') : '')}"/>
            </div>
            <div class="zs-field" style="margin-bottom:10px">
              <label>Meta Açıklaması (Meta Description) <span id="bcMetaDescLen">0/160</span></label>
              <textarea id="bcMetaDesc" class="zs-textarea" style="min-height:62px" placeholder="Google arama sonuçlarında görünecek kategori açıklaması...">${esc(isEdit ? (editCat.meta_desc || '') : '')}</textarea>
            </div>
            <div class="zs-serp-card" style="margin:0">
              <div class="zs-serp-url">zeynart.com › ${esc(blogSlug)} › kategori › <span id="bcSerpSlug">${esc(isEdit ? editCat.slug : 'yeni-kategori')}</span></div>
              <div class="zs-serp-title" id="bcSerpTitle"></div>
              <div class="zs-serp-desc" id="bcSerpDesc"></div>
            </div>
          </div>

          <div style="display:flex;gap:8px">
            <button type="button" class="zs-btn zs-btn-primary" style="flex:1" id="bcSubmitBtn">
              ${isEdit ? '✓ Kategori Değişikliklerini Kaydet' : '+ Yeni Kategoriyi Oluştur'}
            </button>
            ${isEdit ? `<button type="button" class="zs-btn" id="bcResetBtn">Vazgeç</button>` : ''}
          </div>
        </div>

        <!-- RIGHT WIDE LIST, SEARCH, REORDER & BULK ACTIONS PANEL -->
        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h2>Blog Kategorileri (${allCats.length} Kategori · ${rootCount} Ana, ${subCount} Alt)</h2>
              <p class="zs-card-sub">Kategorileri hiyerarşik olarak yönetin, sıralamalarını web sitesine (TR · EN · DE · RU) yansıtın veya toplu işlem uygulayın. Kategori silindiğinde bağlı blog yazıları asla silinmez.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <a href="${esc(resolveUrl('/' + blogSlug + '/'))}" target="_blank" rel="noopener" class="zs-btn">Blog Vitrinini Gör ↗</a>
              <button type="button" class="zs-btn zs-btn-primary" id="bcNewPostJumpBtn">+ Yeni Blog Yazısı</button>
            </div>
          </div>

          <div class="zs-cat-toolbar">
            <input type="search" id="bcSearchInp" class="zs-input" style="flex:1 1 280px;width:auto" placeholder="Kategori adı, URL adresi, üst kategori veya açıklama ara..." value="${esc(S.blogCats.search || '')}"/>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap" id="bcBulkControlsWrap">
              <select id="bcBulkActionSel" class="zs-select" style="width:auto;min-width:220px">
                <option value="">Toplu İşlem Seçin...</option>
                <option value="delete">Seçilen Kategorileri Sil (Yazılar Korunur)</option>
              </select>
              <button type="button" class="zs-btn" id="bcBulkApplyBtn">Uygula</button>
              <span id="bcSelectedBadge" style="font-size:12px;color:var(--accent-bright);font-weight:700"></span>
            </div>
          </div>

          <div class="zs-table-wrap">
            <table class="zs-table">
              <thead>
                <tr>
                  <th style="width:38px;text-align:center">
                    <input type="checkbox" id="bcSelectAllChk" title="Tümünü Seç / Kaldır" style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer"/>
                  </th>
                  <th style="width:94px">Sıra</th>
                  <th>Kategori Adı &amp; Hiyerarşi</th>
                  <th>SEO URL Adresi</th>
                  <th>Üst Kategori</th>
                  <th style="text-align:center">Yazı Sayısı</th>
                  <th style="text-align:right">İşlemler</th>
                </tr>
              </thead>
              <tbody id="bcTableBodyHost">
                ${buildRowsHtml(filteredInitial)}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    `;

    bindBlogModeTabBarEvents();

    const nameInp = document.getElementById('bcName');
    const slugInp = document.getElementById('bcSlug');
    const descInp = document.getElementById('bcDescription');
    const mtInp = document.getElementById('bcMetaTitle');
    const mdInp = document.getElementById('bcMetaDesc');
    const origEditSlug = isEdit ? editCat.slug : '';
    S.blogCats.slugManual = isEdit;

    const refreshCatSerp = () => {
      const nm = nameInp.value.trim();
      const sl = slugInp.value.trim() || slugifyClient(nm) || 'yeni-kategori';
      const desc = descInp.value.trim();
      const mt = mtInp.value.trim() || (nm ? `${nm} — Blog | ${siteName}` : `Kategori Başlığı | ${siteName}`);
      const md = mdInp.value.trim() || desc || `${nm || 'Kategori'} kapsamındaki kurumsal web tasarım, özel yazılım, SEO ve dijital strateji makaleleri.`;
      document.getElementById('bcSlugPreview').textContent = sl;
      document.getElementById('bcSerpSlug').textContent = sl;
      document.getElementById('bcSerpTitle').textContent = mt;
      document.getElementById('bcSerpDesc').textContent = md;
      document.getElementById('bcMetaTitleLen').textContent = `${mtInp.value.trim().length}/65`;
      document.getElementById('bcMetaDescLen').textContent = `${mdInp.value.trim().length}/160`;
      const redirHint = document.getElementById('bcSlugRedirectHint');
      if (redirHint) {
        redirHint.style.display = (isEdit && sl && sl !== origEditSlug) ? 'block' : 'none';
      }
    };
    refreshCatSerp();

    nameInp.addEventListener('input', () => {
      if (!S.blogCats.slugManual) {
        slugInp.value = slugifyClient(nameInp.value.trim());
      }
      refreshCatSerp();
    });

    slugInp.addEventListener('input', () => {
      S.blogCats.slugManual = slugInp.value.trim() !== '';
      refreshCatSerp();
    });

    document.getElementById('bcRegenSlugBtn').onclick = () => {
      slugInp.value = slugifyClient(nameInp.value.trim());
      S.blogCats.slugManual = false;
      refreshCatSerp();
    };

    [descInp, mtInp, mdInp].forEach(el => el.addEventListener('input', refreshCatSerp));

    document.getElementById('bcAutoSeoBtn').onclick = () => {
      const nm = nameInp.value.trim();
      if (!nm) {
        toast('Lütfen önce kategori adını girin.', true);
        nameInp.focus();
        return;
      }
      if (!slugInp.value.trim()) slugInp.value = slugifyClient(nm);
      const baseDesc = descInp.value.trim() || `${nm} alanında hazırladığımız uzman kurumsal web tasarım, yazılım mimarisi, teknik SEO ve dijital büyüme rehberleri.`;
      if (!descInp.value.trim()) descInp.value = baseDesc;
      mtInp.value = `${nm} Rehberi & Makaleleri | ${siteName}`.slice(0, 65);
      mdInp.value = baseDesc.slice(0, 158);
      refreshCatSerp();
      toast('Kategori SEO başlığı ve meta açıklaması otomatik oluşturuldu.');
    };

    const resetEditMode = () => {
      S.blogCats.editingId = null;
      S.blogCats.slugManual = false;
      renderBlogCategoriesView(true);
    };
    document.getElementById('bcCancelEditBtn')?.addEventListener('click', resetEditMode);
    document.getElementById('bcResetBtn')?.addEventListener('click', resetEditMode);

    document.getElementById('bcNewPostJumpBtn').onclick = () => {
      navigateTo('blog', () => startNewBlogPost());
    };

    document.getElementById('bcSaveDefaultBtn').onclick = async () => {
      const targetId = document.getElementById('bcDefaultCatSelect').value;
      if (!targetId || targetId === defCat.id) {
        toast('Seçilen kategori zaten varsayılan kategori olarak aktif.');
        return;
      }
      const btn = document.getElementById('bcSaveDefaultBtn');
      btn.disabled = true;
      try {
        const res = await api('blog_category_set_default', { id: targetId });
        syncBlogCatsFromApiPayload(res);
        toast(res.message);
        renderBlogCategoriesView(true);
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
      }
    };

    document.getElementById('bcSubmitBtn').onclick = async () => {
      const nameVal = nameInp.value.trim();
      if (!nameVal) {
        toast('Kategori adı boş bırakılamaz.', true);
        nameInp.focus();
        return;
      }
      const slugVal = slugifyClient(slugInp.value.trim() || nameVal);
      const payloadCat = {
        id: isEdit ? editCat.id : '',
        name: nameVal,
        slug: slugVal,
        parent_id: document.getElementById('bcParentId').value || '',
        order: Number(document.getElementById('bcOrder').value || nextSuggestedOrder),
        description: descInp.value.trim(),
        meta_title: mtInp.value.trim(),
        meta_desc: mdInp.value.trim(),
        is_default: Boolean(document.getElementById('bcIsDefault').checked)
      };
      const btn = document.getElementById('bcSubmitBtn');
      btn.disabled = true;
      btn.textContent = '⏳ Kaydediliyor...';
      try {
        const res = await api('blog_category_save', { category: payloadCat });
        syncBlogCatsFromApiPayload(res);
        S.blogCats.editingId = null;
        S.blogCats.slugManual = false;
        toast(res.message);
        renderBlogCategoriesView(true);
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
        btn.textContent = isEdit ? '✓ Kategori Değişikliklerini Kaydet' : '+ Yeni Kategoriyi Oluştur';
      }
    };

    const syncBulkHeaderUi = () => {
      const visible = getFilteredCats().filter(c => !c.is_default);
      const selSet = new Set(S.blogCats.selectedIds || []);
      const allChk = document.getElementById('bcSelectAllChk');
      if (allChk) {
        allChk.disabled = visible.length === 0;
        allChk.checked = visible.length > 0 && visible.every(c => selSet.has(c.id));
      }
      const badge = document.getElementById('bcSelectedBadge');
      if (badge) {
        const cnt = (S.blogCats.selectedIds || []).length;
        badge.textContent = cnt > 0 ? `${cnt} kategori seçildi` : '';
      }
    };

    const bindTableRowActions = () => {
      const tbody = document.getElementById('bcTableBodyHost');
      if (!tbody) return;

      tbody.querySelectorAll('.bc-row-chk').forEach(chk => {
        chk.onchange = () => {
          const cid = chk.value;
          const set = new Set(S.blogCats.selectedIds || []);
          if (chk.checked) set.add(cid);
          else set.delete(cid);
          S.blogCats.selectedIds = Array.from(set);
          syncBulkHeaderUi();
        };
      });

      tbody.querySelectorAll('[data-bc-edit]').forEach(el => {
        el.onclick = () => {
          S.blogCats.editingId = el.getAttribute('data-bc-edit');
          renderBlogCategoriesView(true);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        };
      });

      tbody.querySelectorAll('[data-bc-open-posts]').forEach(btn => {
        btn.onclick = () => {
          const catName = btn.getAttribute('data-bc-open-posts') || 'TÜMÜ';
          navigateTo('blog', () => {
            S.blog.mode = 'list';
            S.blog.category = catName;
            S.blog.page = 1;
          });
        };
      });

      tbody.querySelectorAll('[data-bc-set-def]').forEach(btn => {
        btn.onclick = async () => {
          const cid = btn.getAttribute('data-bc-set-def');
          btn.disabled = true;
          try {
            const res = await api('blog_category_set_default', { id: cid });
            syncBlogCatsFromApiPayload(res);
            toast(res.message);
            renderBlogCategoriesView(true);
          } catch (err) {
            toast(err.message, true);
            btn.disabled = false;
          }
        };
      });

      tbody.querySelectorAll('[data-bc-move]').forEach(btn => {
        btn.onclick = async () => {
          const [cid, dir] = (btn.getAttribute('data-bc-move') || '').split(':');
          if (!cid || !dir) return;
          const curCat = allCats.find(x => x.id === cid);
          if (!curCat) return;
          const pk = curCat.parent_id || '';
          const sibs = allCats.filter(x => (x.parent_id || '') === pk);
          const idx = sibs.findIndex(x => x.id === cid);
          const swapIdx = dir === 'up' ? idx - 1 : idx + 1;
          if (idx < 0 || swapIdx < 0 || swapIdx >= sibs.length) return;
          const reorderedSibs = sibs.slice();
          [reorderedSibs[idx], reorderedSibs[swapIdx]] = [reorderedSibs[swapIdx], reorderedSibs[idx]];

          const nextOrderedIds = [];
          const appendGroup = parentKey => {
            const group = parentKey === pk
              ? reorderedSibs
              : allCats.filter(x => (x.parent_id || '') === parentKey);
            group.forEach(g => {
              nextOrderedIds.push(g.id);
              appendGroup(g.id);
            });
          };
          appendGroup('');
          allCats.forEach(x => {
            if (!nextOrderedIds.includes(x.id)) nextOrderedIds.push(x.id);
          });

          btn.disabled = true;
          try {
            const res = await api('blog_category_reorder', { ordered_ids: nextOrderedIds });
            syncBlogCatsFromApiPayload(res);
            toast(res.message);
            renderBlogCategoriesView(true);
          } catch (err) {
            toast(err.message, true);
            btn.disabled = false;
          }
        };
      });

      tbody.querySelectorAll('[data-bc-del]').forEach(btn => {
        btn.onclick = async () => {
          const cid = btn.getAttribute('data-bc-del');
          const target = allCats.find(x => x.id === cid);
          if (!target || target.is_default) return;
          const cnt = Number(target.post_count || 0);
          const msg = cnt > 0
            ? `"${target.name}" kategorisini silmek istediğinize emin misiniz?\n\nÖnemli: Bu kategoriye bağlı ${cnt} blog yazısı SİLİNMEYECEK; başka kategorisi olmayan yazılar otomatik olarak "${defCat.name}" (Varsayılan Kategori) altına aktarılacaktır.`
            : `"${target.name}" kategorisini silmek istediğinize emin misiniz? (Bağlı blog yazıları kesinlikle silinmez.)`;
          if (!confirm(msg)) return;
          btn.disabled = true;
          try {
            const res = await api('blog_category_delete', { id: cid });
            syncBlogCatsFromApiPayload(res);
            if (S.blogCats.editingId === cid) S.blogCats.editingId = null;
            S.blogCats.selectedIds = (S.blogCats.selectedIds || []).filter(x => x !== cid);
            toast(res.message);
            renderBlogCategoriesView(true);
          } catch (err) {
            toast(err.message, true);
            btn.disabled = false;
          }
        };
      });
    };

    bindTableRowActions();
    syncBulkHeaderUi();

    document.getElementById('bcSearchInp').addEventListener('input', e => {
      S.blogCats.search = e.target.value;
      const tbody = document.getElementById('bcTableBodyHost');
      if (tbody) {
        tbody.innerHTML = buildRowsHtml(getFilteredCats());
        bindTableRowActions();
        syncBulkHeaderUi();
      }
    });

    document.getElementById('bcSelectAllChk').onchange = e => {
      const visible = getFilteredCats().filter(c => !c.is_default);
      const set = new Set(S.blogCats.selectedIds || []);
      if (e.target.checked) {
        visible.forEach(c => set.add(c.id));
      } else {
        visible.forEach(c => set.delete(c.id));
      }
      S.blogCats.selectedIds = Array.from(set);
      const tbody = document.getElementById('bcTableBodyHost');
      if (tbody) {
        tbody.querySelectorAll('.bc-row-chk').forEach(chk => {
          if (!chk.disabled) chk.checked = set.has(chk.value);
        });
      }
      syncBulkHeaderUi();
    };

    document.getElementById('bcBulkApplyBtn').onclick = async () => {
      const act = document.getElementById('bcBulkActionSel').value;
      const ids = (S.blogCats.selectedIds || []).filter(Boolean);
      if (!act) {
        toast('Lütfen uygulanacak toplu işlemi seçin.', true);
        return;
      }
      if (!ids.length) {
        toast('Lütfen tablodan en az bir kategori seçin.', true);
        return;
      }
      if (act === 'delete') {
        if (!confirm(`Seçilen ${ids.length} kategoriyi toplu olarak silmek istediğinize emin misiniz?\n\nHiçbir blog yazısı silinmez; sahipsiz kalan yazılar otomatik olarak "${defCat.name}" kategorisine aktarılır.`)) {
          return;
        }
        const btn = document.getElementById('bcBulkApplyBtn');
        btn.disabled = true;
        try {
          const res = await api('blog_category_bulk_delete', { ids });
          syncBlogCatsFromApiPayload(res);
          S.blogCats.selectedIds = [];
          if (S.blogCats.editingId && ids.includes(S.blogCats.editingId)) {
            S.blogCats.editingId = null;
          }
          toast(res.message);
          renderBlogCategoriesView(true);
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      }
    };
  }

  async function renderBlogView() {
    syncAdminUrl('replace');
    if (S.blog.mode === 'edit' && S.blog.editingPost) {
      return renderBlogEditor();
    }
    if (S.blog.mode === 'settings') {
      return renderBlogSettings();
    }
    if (S.blog.mode === 'news_bot') {
      return renderNewsAutopilotView();
    }
    if (S.blog.mode === 'seo_links') {
      return renderBlogSeoLinksView();
    }
    if (S.blog.mode === 'authors') {
      return renderBlogAuthorsView();
    }
    if (S.blog.mode === 'comments') {
      return renderBlogCommentsView();
    }

    ROOT.innerHTML = `<div class="zs-card"><p style="color:var(--text-muted)">Blog arşivi yükleniyor...</p></div>`;
    try {
      await loadBlogList();
    } catch (err) {
      ROOT.innerHTML = `<div class="zs-card" style="color:#fca5a5">${esc(err.message)}</div>`;
      return;
    }

    const allTreeCats = getClientBlogCategoriesTree();
    const totalAllCnt = Number(S.blog.categories?.['TÜMÜ'] ?? S.blog.total ?? 0);
    const curCatSel = S.blog.category || 'TÜMÜ';
    const curCatSelLow = curCatSel.toLocaleLowerCase('tr-TR');
    const catOptions = [
      `<option value="TÜMÜ" ${(curCatSel === 'TÜMÜ' || curCatSel === '') ? 'selected' : ''}>Tüm Kategoriler (${totalAllCnt.toLocaleString('tr-TR')})</option>`,
      ...allTreeCats.map(tc => {
        const cnt = Number(tc.total_post_count ?? S.blog.categories?.[tc.name] ?? tc.post_count ?? 0);
        const isSel = curCatSel === tc.name || curCatSel === tc.slug || curCatSel === tc.id || curCatSelLow === tc.name.toLocaleLowerCase('tr-TR');
        return `<option value="${esc(tc.name)}" ${isSel ? 'selected' : ''}>${esc(tc.indent_prefix + tc.name)} (${cnt.toLocaleString('tr-TR')})</option>`;
      })
    ].join('');

    ROOT.innerHTML = `
      ${renderBlogModeTabBarHtml()}
      <div class="zs-card">
        <div class="zs-card-head">
          <div>
            <h2>Blog & İçgörü Arşivi (${Number(S.blog.total).toLocaleString('tr-TR')} Yazı)</h2>
            <p class="zs-card-sub">Mevcut tüm blog yazılarını arayabilir, hiyerarşik kategorilere göre süzebilir, düzenleyebilir ve yeni içerik ekleyebilirsiniz.</p>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="button" class="zs-btn" id="btnBlogManageCats">📂 Kategoriler (${allTreeCats.length})</button>
            <button type="button" class="zs-btn zs-btn-ai" id="btnBatchTranslatePosts" title="Mevcut blog yazılarını toplu olarak İngilizce, Almanca ve Rusça dillerine çevirir">🌍 Mevcut Yazıları Toplu Çevir (EN · DE · RU)</button>
            <button type="button" class="zs-btn" id="btnBlogVitrinSettings">Vitrin Ayarları</button>
            <button type="button" class="zs-btn zs-btn-ai" id="btnAiWriteNewPost">🤖 Yapay Zeka ile Yazı Oluştur</button>
            <button type="button" class="zs-btn zs-btn-primary" id="btnNewBlogPost">+ Yeni Blog Yazısı Ekle</button>
          </div>
        </div>

        <div id="blogBulkStudioHost"></div>

        <div style="display:grid;grid-template-columns:minmax(220px,2fr) 1fr 1fr auto;gap:10px;margin-bottom:16px">
          <input id="blogSearchInp" class="zs-input" placeholder="Başlık, URL uzantısı veya içerik özeti ara..." value="${esc(S.blog.search)}"/>
          <select id="blogCatSel" class="zs-select">${catOptions}</select>
          <select id="blogStatusSel" class="zs-select">
            <option value="all" ${S.blog.status === 'all' ? 'selected' : ''}>Tüm Durumlar</option>
            <option value="published" ${S.blog.status === 'published' ? 'selected' : ''}>Yayında Olanlar</option>
            <option value="draft" ${S.blog.status === 'draft' ? 'selected' : ''}>Taslaklar</option>
            <option value="featured" ${S.blog.status === 'featured' ? 'selected' : ''}>Manşet Yazısı</option>
            <option value="curated" ${S.blog.status === 'curated' ? 'selected' : ''}>Editör Seçkisi</option>
          </select>
          <button type="button" class="zs-btn" id="blogFilterBtn">Filtrele</button>
        </div>

        <div class="zs-table-wrap">
          <table class="zs-table">
            <thead>
              <tr>
                <th style="width:64px">Kapak</th>
                <th>Başlık & URL</th>
                <th>Kategori</th>
                <th>Tarih</th>
                <th>Durum / Vitrin</th>
                <th>SEO</th>
                <th style="text-align:right">İşlemler</th>
              </tr>
            </thead>
            <tbody>
              ${S.blog.items.length === 0 ? `<tr><td colspan="7" style="text-align:center;padding:34px;color:var(--text-muted)">Aramanızla eşleşen blog yazısı bulunamadı.</td></tr>` :
                S.blog.items.map(it => {
                  const rowCats = (Array.isArray(it.categories) && it.categories.length) ? it.categories : [it.category || 'Genel'];
                  return `
                  <tr>
                    <td>
                      <img src="${esc(resolveUrl(it.image || '/images/journal/web-planning.webp'))}" alt="" loading="lazy"
                        style="width:52px;height:38px;object-fit:cover;border-radius:8px;border:1px solid var(--border-subtle)"/>
                    </td>
                    <td>
                      <strong style="display:block;font-size:13.5px;cursor:pointer;color:#fff" data-edit-slug="${esc(it.slug)}">${esc(it.title)}</strong>
                      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:3px">
                        <span style="font-size:11.5px;color:var(--text-dim)">/${esc(getResolvedPageSlug('blog'))}/${esc(it.slug)}/ · ${esc(it.read || '3 dk')}</span>
                        ${it.news_bot?.is_auto_news ? `<span class="zs-pill zs-pill-cat" style="border-color:rgba(52,211,153,.4);color:#6ee7b7;font-size:10.5px;padding:1px 7px">📰 Haber Botu</span>` : ''}
                        <span data-blog-i18n-status="${esc(it.slug)}" style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap">
                          ${renderBlogRowI18nStatusHtml(it.slug, it.i18n, S.i18nBatch.activeSlug === it.slug)}
                        </span>
                      </div>
                    </td>
                    <td>
                      <div style="display:flex;gap:4px;flex-wrap:wrap">
                        ${rowCats.map(cn => `<span class="zs-pill zs-pill-cat">${esc(cn)}</span>`).join('')}
                      </div>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">${esc(it.date_display || it.date)}</td>
                    <td>
                      <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button type="button" class="zs-pill ${it.status === 'draft' ? 'zs-pill-draft' : 'zs-pill-pub'}" style="border:none;cursor:pointer" data-toggle-slug="${esc(it.slug)}" data-toggle-field="status" title="Durumu Değiştir">
                          ${it.status === 'draft' ? 'Taslak' : 'Yayında'}
                        </button>
                        <button type="button" class="zs-pill zs-pill-cat" style="cursor:pointer;opacity:${it.featured ? '1' : '0.45'}" data-toggle-slug="${esc(it.slug)}" data-toggle-field="featured" title="Manşet Yap">
                          ★ Manşet
                        </button>
                        <button type="button" class="zs-pill zs-pill-cat" style="cursor:pointer;opacity:${it.curated ? '1' : '0.45'}" data-toggle-slug="${esc(it.slug)}" data-toggle-field="curated" title="Editör Seçkisi">
                          ✦ Seçki
                        </button>
                      </div>
                    </td>
                    <td><strong style="color:${(it.seo_score || 90) >= 80 ? '#34d399' : '#fbbf24'}">${it.seo_score || 90}</strong></td>
                    <td style="text-align:right;white-space:nowrap">
                      <button type="button" class="zs-btn zs-btn-sm" data-translate-slug="${esc(it.slug)}" title="İngilizce, Almanca ve Rusça'ya Şimdi Çevir / Güncelle">🌍 Çevir</button>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-share-slug="${esc(it.slug)}" title="1080×1080 Instagram, 1080×1920 Story Görseli & Sosyal Medya Metni Üret">📸 IG &amp; Paylaş</button>
                      <button type="button" class="zs-btn zs-btn-sm" data-edit-slug="${esc(it.slug)}">Düzenle</button>
                      <a href="${esc(resolveUrl('/' + getResolvedPageSlug('blog') + '/' + it.slug + '/'))}" target="_blank" class="zs-btn zs-btn-sm">Gör ↗</a>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-del-slug="${esc(it.slug)}">Sil</button>
                    </td>
                  </tr>
                `}).join('')
              }
            </tbody>
          </table>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:10px">
          <span style="font-size:12.5px;color:var(--text-muted)">Sayfa ${S.blog.page} / ${S.blog.totalPages} (Toplam ${Number(S.blog.total).toLocaleString('tr-TR')} makale)</span>
          <div style="display:flex;gap:8px;align-items:center">
            <button type="button" class="zs-btn zs-btn-sm" id="blogPrevBtn" ${S.blog.page <= 1 ? 'disabled' : ''}>← Önceki</button>
            <input type="number" id="blogJumpPage" class="zs-input" style="width:74px;padding:6px 8px;text-align:center" min="1" max="${S.blog.totalPages}" value="${S.blog.page}"/>
            <button type="button" class="zs-btn zs-btn-sm" id="blogNextBtn" ${S.blog.page >= S.blog.totalPages ? 'disabled' : ''}>Sonraki →</button>
          </div>
        </div>
      </div>
    `;

    bindBlogModeTabBarEvents();
    document.getElementById('btnBlogManageCats').onclick = () => { S.blogCats.editingId = null; navigateTo('blog_categories'); };
    document.getElementById('btnNewBlogPost').onclick = () => { S.openAiWriterOnNew = false; startNewBlogPost(); renderBlogView(); };
    document.getElementById('btnAiWriteNewPost').onclick = () => { S.openAiWriterOnNew = true; startNewBlogPost(); renderBlogView(); };
    document.getElementById('btnBlogVitrinSettings').onclick = () => { S.blog.mode = 'settings'; renderBlogView(); };

    const applyFilter = () => {
      S.blog.search = document.getElementById('blogSearchInp').value.trim();
      S.blog.category = document.getElementById('blogCatSel').value;
      S.blog.status = document.getElementById('blogStatusSel').value;
      S.blog.page = 1;
      renderBlogView();
    };
    document.getElementById('blogFilterBtn').onclick = applyFilter;
    document.getElementById('blogCatSel').onchange = applyFilter;
    document.getElementById('blogStatusSel').onchange = applyFilter;
    document.getElementById('blogSearchInp').onkeydown = e => { if (e.key === 'Enter') applyFilter(); };
    document.getElementById('blogPrevBtn').onclick = () => { S.blog.page--; renderBlogView(); };
    document.getElementById('blogNextBtn').onclick = () => { S.blog.page++; renderBlogView(); };
    document.getElementById('blogJumpPage').onchange = e => {
      S.blog.page = Math.max(1, Math.min(S.blog.totalPages, Number(e.target.value || 1)));
      renderBlogView();
    };

    ROOT.querySelectorAll('[data-edit-slug]').forEach(el => {
      el.onclick = () => openBlogPostForEdit(el.getAttribute('data-edit-slug'));
    });

    ROOT.querySelectorAll('[data-share-slug]').forEach(btn => {
      btn.onclick = () => openSocialShareStudio({ kind: 'blog', targetKey: btn.getAttribute('data-share-slug') });
    });

    ROOT.querySelectorAll('[data-translate-slug]').forEach(btn => {
      btn.onclick = async () => {
        const slug = btn.getAttribute('data-translate-slug');
        const origTxt = btn.textContent;
        btn.disabled = true;
        btn.textContent = '⏳ Çevriliyor...';
        patchVisibleBlogTableRow(slug, 'active', null);
        try {
          const res = await api('blog_translate', { slug, compact: true, i18n_override: getLiveI18nOverrideFromDom() });
          toast(res.message);
          patchVisibleBlogTableRow(slug, 'done', res.i18n);
          if (S.i18nBatch.loaded) {
            const qIt = S.i18nBatch.items.find(x => x.slug === slug);
            if (qIt) {
              qIt.done = true;
              qIt.retranslatePending = false;
              qIt.justDone = true;
              qIt.justDoneAt = Date.now();
              qIt.error = '';
              qIt.en_slug = res.i18n?.en?.slug || slug;
              qIt.de_slug = res.i18n?.de?.slug || slug;
              qIt.ru_slug = res.i18n?.ru?.slug || slug;
              qIt.en_title = res.i18n?.en?.title || qIt.title;
              recalcI18nBatchCounters();
              refreshI18nStudioMount();
            }
          }
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
          btn.textContent = origTxt;
          patchVisibleBlogTableRow(slug, 'idle', null);
        }
      };
    });

    if (S.i18nBatch.showOnBlogView || S.i18nBatch.state !== 'idle') {
      loadI18nTranslateQueue(false);
    }

    document.getElementById('btnBatchTranslatePosts').onclick = async () => {
      S.i18nBatch.showOnBlogView = true;
      if (S.i18nBatch.state === 'running') {
        refreshI18nStudioMount();
        document.getElementById('blogBulkStudioHost')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
      }
      await startBulkBlogTranslation({ forceAll: false });
    };

    ROOT.querySelectorAll('[data-toggle-slug]').forEach(btn => {
      btn.onclick = async () => {
        try {
          const slug = btn.getAttribute('data-toggle-slug');
          const field = btn.getAttribute('data-toggle-field');
          await api('blog_quick_toggle', { slug, field });
          if (S.i18nBatch.state === 'idle') S.i18nBatch.loaded = false;
          toast('Yazı durumu güncellendi.');
          renderBlogView();
        } catch (err) {
          toast(err.message, true);
        }
      };
    });

    ROOT.querySelectorAll('[data-del-slug]').forEach(btn => {
      btn.onclick = async () => {
        const slug = btn.getAttribute('data-del-slug');
        if (!confirm(`"${slug}" başlıklı blog yazısını kalıcı olarak silmek istediğinize emin misiniz?`)) return;
        try {
          await api('blog_delete', { slug });
          S.blogCats.loaded = false;
          if (S.i18nBatch.state === 'idle') S.i18nBatch.loaded = false;
          if (S.summary) S.summary.total_posts = Math.max(0, (S.summary.total_posts || 1) - 1);
          updateSidebarBadges();
          toast('Blog yazısı silindi.');
          renderBlogView();
        } catch (err) {
          toast(err.message, true);
        }
      };
    });
  }

  function renderBlogSettings() {
    const bs = S.store.blog_settings || {};
    const cats = (bs.categories || []).filter(c => c !== 'TÜMÜ').join(', ');
    ROOT.innerHTML = `
      ${renderBlogModeTabBarHtml()}
      <div class="zs-card">
        <div class="zs-card-head">
          <div>
            <h2>Zeynart Journal Vitrin & Kategori Ayarları</h2>
            <p class="zs-card-sub">/blog/ ana sayfasının üst başlıklarını, sayfa başı yazı sayısını ve paylaşım modüllerini yönetin.</p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-ai" id="jumpToBlogCategoriesStudio">📂 Hiyerarşik Kategori Stüdyosu →</button>
            <button type="button" class="zs-btn" id="backToBlogList">← Yazı Listesine Dön</button>
          </div>
        </div>
        <div class="zs-grid-2">
          <div class="zs-field"><label>Sol Üst Etiket</label><input id="bsKickerL" class="zs-input" value="${esc(bs.kicker_left || '')}"/></div>
          <div class="zs-field"><label>Sağ Üst Etiket</label><input id="bsKickerR" class="zs-input" value="${esc(bs.kicker_right || '')}"/></div>
          <div class="zs-field"><label>Ana Başlık (1. Satır)</label><input id="bsH1" class="zs-input" value="${esc(bs.heading_line1 || '')}"/></div>
          <div class="zs-field"><label>Ana Başlık Vurgulu (2. Satır)</label><input id="bsSpan" class="zs-input" value="${esc(bs.heading_span || '')}"/></div>
          <div class="zs-field"><label>Sağ Rozet Notu</label><input id="bsBadge" class="zs-input" value="${esc(bs.badge_right || '')}"/></div>
          <div class="zs-field"><label>Sayfa Başına Yazı Adedi</label><input id="bsPerPage" type="number" class="zs-input" value="${esc(bs.per_page || 24)}"/></div>
        </div>
        <div class="zs-field"><label>Giriş Açıklaması</label><textarea id="bsLead" class="zs-textarea">${esc(bs.lead || '')}</textarea></div>
        <div class="zs-field"><label>Hızlı Kategori Filtre Etiketleri (Ana / Alt kategori ve SEO yönetimi için Kategori Stüdyosunu kullanın)</label><input id="bsCats" class="zs-input" value="${esc(cats)}"/></div>
        <div style="padding:12px 14px;margin-bottom:10px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div>
            <strong style="color:#5eead4;font-size:13px">🎧 "Makaleyi Sesli Dinle" Podcast Oynatıcısı</strong>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Her blog makalesinin başında 1x/1.25x/1.5x hız seçenekli ve paragraf vurgulu sesli dinleme çubuğunu gösterir (TR · EN · DE · RU, sıfır çerez).</div>
          </div>
          <label class="zs-switch" style="margin:0"><input type="checkbox" id="bsAudioPlayer" ${bs.audio_player_enabled !== false ? 'checked' : ''}/><span>Aktif</span></label>
        </div>
        <div style="padding:12px 14px;margin-bottom:14px;border-radius:12px;background:rgba(4,10,14,.74);border:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div>
            <strong style="color:#5eead4;font-size:13px">Okuyucu Sosyal Paylaşım Çubuğu</strong>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Her blog yazısının sonunda WhatsApp, X, LinkedIn, Facebook, Telegram, E-posta ve Bağlantıyı Kopyala çubuğunu gösterir (0 çerez / KVKK uyumlu).</div>
          </div>
          <label class="zs-switch" style="margin:0"><input type="checkbox" id="bsShowShareBar" ${bs.show_share_bar !== false ? 'checked' : ''}/><span>Aktif</span></label>
        </div>
        <button type="button" class="zs-btn zs-btn-primary" id="saveBlogSettingsBtn">Vitrin Ayarlarını Kaydet</button>
      </div>
    `;
    bindBlogModeTabBarEvents();
    document.getElementById('jumpToBlogCategoriesStudio').onclick = () => { S.blogCats.editingId = null; navigateTo('blog_categories'); };
    document.getElementById('backToBlogList').onclick = () => { S.blog.mode = 'list'; renderBlogView(); };
    document.getElementById('saveBlogSettingsBtn').onclick = async () => {
      try {
        const res = await api('blog_settings_save', {
          blog_settings: {
            heading: bs.heading || S.store.page_titles?.blog || 'Blog',
            kicker_left: document.getElementById('bsKickerL').value,
            kicker_right: document.getElementById('bsKickerR').value,
            heading_line1: document.getElementById('bsH1').value,
            heading_span: document.getElementById('bsSpan').value,
            badge_right: document.getElementById('bsBadge').value,
            per_page: Number(document.getElementById('bsPerPage').value || 24),
            lead: document.getElementById('bsLead').value,
            categories: document.getElementById('bsCats').value,
            show_share_bar: Boolean(document.getElementById('bsShowShareBar')?.checked),
            audio_player_enabled: Boolean(document.getElementById('bsAudioPlayer')?.checked)
          }
        });
        S.store.blog_settings = res.blog_settings;
        if (S.store.seo_interlinks) {
          S.store.seo_interlinks.audio_player_enabled = Boolean(res.blog_settings?.audio_player_enabled);
        }
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      }
    };
  }

  function buildDefaultInterlinkRulesClient() {
    const srvBase = '/' + getResolvedPageSlug('hizmetler');
    return [
      { keyword: 'kurumsal web tasarım', url: srvBase + '/web-tasarim/', title: 'Kurumsal Web Tasarım Hizmetimiz' },
      { keyword: 'web tasarım', url: srvBase + '/web-tasarim/', title: 'Profesyonel Web Tasarım Çözümleri' },
      { keyword: 'özel web yazılım', url: srvBase + '/web-yazilim/', title: 'Özel Web Yazılım & CRM Geliştirme' },
      { keyword: 'web yazılım', url: srvBase + '/web-yazilim/', title: 'Kurumsal Web Yazılım Çözümleri' },
      { keyword: 'e-ticaret altyapısı', url: srvBase + '/e-ticaret/', title: 'Yüksek Dönüşümlü E-Ticaret Çözümleri' },
      { keyword: 'e-ticaret sitesi', url: srvBase + '/e-ticaret/', title: 'Kurumsal E-Ticaret Sistemleri' },
      { keyword: 'mobil uygulama geliştirme', url: srvBase + '/mobil-uygulama/', title: 'iOS & Android Mobil Uygulama Geliştirme' },
      { keyword: 'mobil uygulama', url: srvBase + '/mobil-uygulama/', title: 'Kurumsal Mobil Uygulama Çözümleri' },
      { keyword: 'web hosting', url: srvBase + '/hosting/', title: 'Yüksek Hızlı Kurumsal Hosting & Sunucu' },
      { keyword: 'bulut sunucu', url: srvBase + '/hosting/', title: 'Yönetilen Bulut Sunucu Altyapısı' },
      { keyword: 'kurumsal kimlik', url: srvBase + '/grafik-tasarim/', title: 'Kurumsal Kimlik & Marka Tasarımı' },
      { keyword: 'grafik tasarım', url: srvBase + '/grafik-tasarim/', title: 'Kreatif Grafik Tasarım Stüdyosu' },
      { keyword: 'ücretsiz seo analizi', url: '/site-analiz/', title: 'Ücretsiz Site Hızı & SEO Ön Analiz Aracı' },
      { keyword: 'site hızı testi', url: '/site-analiz/', title: 'Canlı Site Hızı & Performans Testi' },
      { keyword: 'fiyat teklifi', url: '/teklif-sihirbazi/', title: 'Adım Adım Proje Teklif & Bütçe Sihirbazı' }
    ];
  }

  function renderBlogSeoLinksView() {
    syncAdminUrl('replace');
    const cfg = S.store?.seo_interlinks || {};
    const bs = S.store?.blog_settings || {};
    const audioOn = (cfg.audio_player_enabled ?? bs.audio_player_enabled ?? true) !== false;
    const enabled = cfg.enabled !== false;
    const maxPerPost = Number(cfg.max_links_per_post || 4);
    const maxPerTarget = Number(cfg.max_per_target || 1);
    const openNewTab = Boolean(cfg.open_in_new_tab);
    const rules = Array.isArray(cfg.rules) ? cfg.rules.map(r => Object.assign({}, r)) : buildDefaultInterlinkRulesClient();

    const quickTargets = [
      ...buildUnifiedPagesCatalog().map(pg => ({ label: `${pg.title} (${pg.url})`, url: pg.url, title: pg.title })),
      { label: 'Teklif & Bütçe Sihirbazı (/teklif-sihirbazi/)', url: '/teklif-sihirbazi/', title: 'Proje Teklif & Maliyet Sihirbazı' },
      { label: 'Ücretsiz Site & SEO Analizi (/site-analiz/)', url: '/site-analiz/', title: 'Ücretsiz Site Hızı & SEO Ön Analiz' }
    ];

    const renderRuleRowHtml = (r, idx) => `
      <div class="zs-il-rule-row" data-il-row="${idx}">
        <input class="zs-input il-kw" placeholder="Anahtar kelime (örn: kurumsal web tasarım)" value="${esc(r.keyword || '')}"/>
        <div class="zs-input-group">
          <input class="zs-input il-url" placeholder="/hizmetler/web-tasarim/" value="${esc(r.url || '')}"/>
          <select class="zs-select il-quick-sel" style="max-width:170px" title="Sitedeki bir sayfayı hızlı seç">
            <option value="">Hızlı Sayfa Seç...</option>
            ${quickTargets.map(qt => `<option value="${esc(qt.url)}" data-qt-title="${esc(qt.title)}">${esc(qt.label)}</option>`).join('')}
          </select>
        </div>
        <input class="zs-input il-title" placeholder="Link başlığı (SEO title özniteliği)" value="${esc(r.title || '')}"/>
        <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-il-del="${idx}" title="Kuralı Kaldır">✕</button>
      </div>
    `;

    ROOT.innerHTML = `
      ${renderBlogModeTabBarHtml()}

      <div class="zs-card" style="border-color:rgba(94,234,212,.34)">
        <div class="zs-card-head">
          <div>
            <span class="zs-badge-pill">MODÜL 1 · APPLE PODCAST DENEYİMİ</span>
            <h2 style="margin-top:6px">🎧 "Makaleyi Sesli Dinle" Podcast Oynatıcısı</h2>
            <p class="zs-card-sub">Ziyaretçileriniz blog yazılarını yolda veya çalışırken tek tıkla sesli dinlesin; sayfada kalma süresi (Dwell Time) ve SEO otoritesi doğal olarak artsın.</p>
          </div>
          <label class="zs-switch" style="margin:0">
            <input type="checkbox" id="ilAudioEnabled" ${audioOn ? 'checked' : ''}/>
            <span><strong>Tüm Blog Yazılarında Aktif</strong></span>
          </label>
        </div>
        <div class="zs-grid-3" style="gap:12px">
          <div style="padding:12px 14px;border-radius:12px;background:rgba(4,11,15,.72);border:1px solid var(--border-subtle)">
            <strong style="color:#5eead4;font-size:12.5px">🗣️ 4 Dil Doğal Seslendirme</strong>
            <p style="margin:4px 0 0;font-size:12px;color:var(--text-muted)">TR, EN, DE ve RU sayfalarında ilgili dilin yerleşik ses motorunu otomatik seçer; API faturası üretmez.</p>
          </div>
          <div style="padding:12px 14px;border-radius:12px;background:rgba(4,11,15,.72);border:1px solid var(--border-subtle)">
            <strong style="color:#5eead4;font-size:12.5px">⚡ 1x · 1.25x · 1.5x Hız &amp; Paragraf Takibi</strong>
            <p style="margin:4px 0 0;font-size:12px;color:var(--text-muted)">Okunan paragrafı canlı vurgular, uzun yazılarda tarayıcı kesilmesini önleyen akıllı cümle kuyruğuyla çalışır.</p>
          </div>
          <div style="padding:12px 14px;border-radius:12px;background:rgba(4,11,15,.72);border:1px solid var(--border-subtle)">
            <strong style="color:#5eead4;font-size:12.5px">🛡️ Sıfır Çerez &amp; %100 PageSpeed</strong>
            <p style="margin:4px 0 0;font-size:12px;color:var(--text-muted)">Dış MP3 veya üçüncü parti iframe yüklemez; KVKK Zero-Cookie standardıyla tam uyumludur.</p>
          </div>
        </div>
      </div>

      <div class="zs-card">
        <div class="zs-card-head">
          <div>
            <span class="zs-badge-pill">MODÜL 2 · OTOMATİK SEO İÇ LİNKLEME MOTORU</span>
            <h2 style="margin-top:6px">🔗 Anahtar Kelimeden Hizmet Sayfalarına Otomatik İç Linkleme</h2>
            <p class="zs-card-sub">1.680+ blog makalenizin içinde geçen stratejik kelimeleri (Türkçe İ/i ve I/ı duyarlı) mevcut linkleri veya başlıkları bozmadan ilgili hizmet sayfalarınıza otomatik bağlar.</p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn" id="ilLoadDefaultsBtn">↺ Varsayılan Hizmet Kurallarını Yükle</button>
            <button type="button" class="zs-btn zs-btn-primary" id="ilSaveBtn">✓ Ayarları &amp; Kuralları Kaydet</button>
          </div>
        </div>

        <div class="zs-grid-4" style="margin-bottom:16px;align-items:end">
          <div style="padding:11px 14px;border-radius:12px;background:rgba(4,11,15,.74);border:1px solid var(--border-subtle)">
            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="ilEnabled" ${enabled ? 'checked' : ''}/>
              <span><strong>Oto İç Linkleme Aktif</strong></span>
            </label>
          </div>
          <div class="zs-field" style="margin:0">
            <label>Yazı Başı Maksimum Otomatik Link</label>
            <input type="number" id="ilMaxPerPost" class="zs-input" min="1" max="10" value="${maxPerPost}"/>
          </div>
          <div class="zs-field" style="margin:0">
            <label>Aynı Hedef Sayfaya Maks. Link</label>
            <input type="number" id="ilMaxPerTarget" class="zs-input" min="1" max="3" value="${maxPerTarget}"/>
          </div>
          <div style="padding:11px 14px;border-radius:12px;background:rgba(4,11,15,.74);border:1px solid var(--border-subtle)">
            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="ilNewTab" ${openNewTab ? 'checked' : ''}/>
              <span>Yeni Sekmede Aç</span>
            </label>
          </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap">
          <strong style="font-size:13px;color:#e6f7f9">🎯 Anahtar Kelime → Hedef Sayfa Eşleştirmeleri (${rules.length} Kural)</strong>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="ilAddRuleBtn">+ Yeni Kelime Kuralı Ekle</button>
        </div>

        <div id="ilRulesHost" style="display:grid;gap:8px;margin-bottom:16px">
          ${rules.map((r, i) => renderRuleRowHtml(r, i)).join('')}
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding-top:12px;border-top:1px solid var(--border-subtle)">
          <span style="font-size:12px;color:var(--text-muted)">💡 Not: Mevcut <code>&lt;a&gt;</code> linklerine, <code>H1-H6</code> başlıklara ve kod bloklarına asla dokunulmaz; uzun kelime öbekleri (örn. "kurumsal web tasarım") tek kelimelerden önce eşleştirilir.</span>
          <button type="button" class="zs-btn zs-btn-primary" id="ilSaveBottomBtn">✓ Sesli Dinleme &amp; İç Linkleme Kurallarını Kaydet</button>
        </div>
      </div>
    `;

    bindBlogModeTabBarEvents();

    const syncRulesFromDom = () => {
      const next = [];
      ROOT.querySelectorAll('[data-il-row]').forEach(row => {
        const keyword = row.querySelector('.il-kw')?.value.trim() || '';
        const url = row.querySelector('.il-url')?.value.trim() || '';
        const title = row.querySelector('.il-title')?.value.trim() || '';
        if (keyword || url) {
          next.push({ keyword, url, title });
        }
      });
      return next;
    };

    const bindRuleRowEvents = () => {
      ROOT.querySelectorAll('[data-il-del]').forEach(btn => {
        btn.onclick = () => {
          btn.closest('[data-il-row]')?.remove();
        };
      });
      ROOT.querySelectorAll('.il-quick-sel').forEach(sel => {
        sel.onchange = () => {
          const val = sel.value;
          if (!val) return;
          const row = sel.closest('[data-il-row]');
          const urlInp = row?.querySelector('.il-url');
          const titleInp = row?.querySelector('.il-title');
          const opt = sel.options[sel.selectedIndex];
          if (urlInp) urlInp.value = val;
          if (titleInp && !titleInp.value.trim() && opt) {
            titleInp.value = opt.getAttribute('data-qt-title') || '';
          }
          sel.value = '';
        };
      });
    };
    bindRuleRowEvents();

    document.getElementById('ilAddRuleBtn').onclick = () => {
      const host = document.getElementById('ilRulesHost');
      const idx = host.querySelectorAll('[data-il-row]').length;
      host.insertAdjacentHTML('beforeend', renderRuleRowHtml({ keyword: '', url: '/hizmetler/web-tasarim/', title: '' }, idx));
      bindRuleRowEvents();
      const rows = host.querySelectorAll('.il-kw');
      rows[rows.length - 1]?.focus();
    };

    document.getElementById('ilLoadDefaultsBtn').onclick = () => {
      const defs = buildDefaultInterlinkRulesClient();
      const host = document.getElementById('ilRulesHost');
      host.innerHTML = defs.map((r, i) => renderRuleRowHtml(r, i)).join('');
      bindRuleRowEvents();
      toast('Varsayılan hizmet ve araç iç linkleme kuralları yüklendi. Kaydet butonuna basarak yayınlayın.');
    };

    const doSaveInterlinks = async () => {
      const cleanRules = syncRulesFromDom().filter(r => r.keyword && r.url);
      const payload = {
        enabled: Boolean(document.getElementById('ilEnabled')?.checked),
        max_links_per_post: Number(document.getElementById('ilMaxPerPost')?.value || 4),
        max_per_target: Number(document.getElementById('ilMaxPerTarget')?.value || 1),
        open_in_new_tab: Boolean(document.getElementById('ilNewTab')?.checked),
        audio_player_enabled: Boolean(document.getElementById('ilAudioEnabled')?.checked),
        rules: cleanRules
      };
      try {
        const res = await api('seo_interlinks_save', { seo_interlinks: payload });
        S.store.seo_interlinks = res.seo_interlinks;
        if (res.blog_settings) S.store.blog_settings = res.blog_settings;
        toast(res.message);
        renderBlogSeoLinksView();
      } catch (err) {
        toast(err.message, true);
      }
    };

    document.getElementById('ilSaveBtn').onclick = doSaveInterlinks;
    document.getElementById('ilSaveBottomBtn').onclick = doSaveInterlinks;
  }

  function collectNewsAutopilotFormState() {
    const existing = S.store?.news_autopilot || {};
    const topics = [];
    ROOT.querySelectorAll('[data-nb-topic-row]').forEach((row, idx) => {
      const q = row.querySelector('.nb-tp-query')?.value.trim() || '';
      if (!q) return;
      topics.push({
        id: row.getAttribute('data-nb-topic-id') || ('tp-' + (idx + 1)),
        enabled: Boolean(row.querySelector('.nb-tp-enabled')?.checked),
        query: q,
        category: (row.querySelector('.nb-tp-cat')?.value.trim() || 'TEKNOLOJİ').toLocaleUpperCase('tr-TR'),
        target_service: row.querySelector('.nb-tp-srv')?.value || ''
      });
    });

    const rssRaw = document.getElementById('nbCustomRss')?.value || '';
    const customRss = rssRaw.split(/\r?\n/).map(s => s.trim()).filter(Boolean);

    const payload = {
      enabled: document.getElementById('nbEnabled') ? Boolean(document.getElementById('nbEnabled').checked) : (existing.enabled !== false),
      daily_limit: Number(document.getElementById('nbDailyLimit')?.value || existing.daily_limit || 2),
      publish_mode: document.getElementById('nbPublishMode')?.value || existing.publish_mode || 'draft',
      min_interval_hours: Number(document.getElementById('nbIntervalHours')?.value || existing.min_interval_hours || 4),
      language_region: document.getElementById('nbLangRegion')?.value || existing.language_region || 'TR:tr',
      ai_provider: document.getElementById('nbAiProvider')?.value || existing.ai_provider || 'auto',
      auto_translate_on_publish: document.getElementById('nbAutoTranslate') ? Boolean(document.getElementById('nbAutoTranslate').checked) : true,
      show_source_credit: document.getElementById('nbSourceCredit') ? Boolean(document.getElementById('nbSourceCredit').checked) : true,
      topics,
      custom_rss_urls: customRss,
      watermark: {
        cover_mode: document.getElementById('nbCoverMode')?.value || existing.watermark?.cover_mode || 'hybrid',
        enabled: document.getElementById('nbWmEnabled') ? Boolean(document.getElementById('nbWmEnabled').checked) : true,
        show_logo: document.getElementById('nbWmLogo') ? Boolean(document.getElementById('nbWmLogo').checked) : true,
        show_category_badge: document.getElementById('nbWmCatBadge') ? Boolean(document.getElementById('nbWmCatBadge').checked) : true,
        domain_text: document.getElementById('nbWmDomain')?.value.trim() || existing.watermark?.domain_text || 'zeynart.com',
        position: document.getElementById('nbWmPosition')?.value || existing.watermark?.position || 'bottom-right',
        opacity: Number(document.getElementById('nbWmOpacity')?.value || existing.watermark?.opacity || 92)
      }
    };

    if (isSuperAdmin()) {
      const gemInp = document.getElementById('nbGeminiKey');
      const oaiInp = document.getElementById('nbOpenaiKey');
      if (gemInp) payload.gemini_api_key = gemInp.value.trim();
      if (oaiInp) payload.openai_api_key = oaiInp.value.trim();
    }
    return payload;
  }

  function buildNewsWatermarkPreviewUrl(wmCfg) {
    const wm = wmCfg || S.store?.news_autopilot?.watermark || {};
    const p = new URLSearchParams();
    p.set('action', 'news_bot_watermark_preview');
    p.set('csrf_token', CFG.csrfToken || '');
    p.set('cover_mode', wm.cover_mode || 'hybrid');
    p.set('enabled', wm.enabled !== false ? '1' : '0');
    p.set('show_logo', wm.show_logo !== false ? '1' : '0');
    p.set('show_category_badge', wm.show_category_badge !== false ? '1' : '0');
    p.set('domain_text', wm.domain_text || 'zeynart.com');
    p.set('position', wm.position || 'bottom-right');
    p.set('opacity', String(wm.opacity || 92));
    p.set('_t', String(Date.now()));
    return CFG.apiUrl + '?' + p.toString();
  }

  async function renderNewsAutopilotView(skipFreshStatus) {
    syncAdminUrl('replace');

    if (!skipFreshStatus) {
      try {
        const stRes = await api('news_bot_status', {});
        if (stRes.news_autopilot) S.store.news_autopilot = stRes.news_autopilot;
        if (Array.isArray(stRes.queue)) S.newsBotQueue = stRes.queue;
        if (stRes.state) S.newsBotState = stRes.state;
        if (typeof stRes.cron_url === 'string') S.newsBotCronUrl = stRes.cron_url;
        if (stRes.summary) S.summary = stRes.summary;
      } catch (_) {}
    }

    const cfg = S.store?.news_autopilot || {};
    const wm = cfg.watermark || {};
    const st = S.newsBotState || { today_count: 0, history: [] };
    const queue = Array.isArray(S.newsBotQueue) ? S.newsBotQueue : [];
    const topics = Array.isArray(cfg.topics) && cfg.topics.length ? cfg.topics : [
      { id: 'tp-ai-web', query: 'yapay zeka web yazılım teknoloji', category: 'YAPAY ZEKA', target_service: 'web-tasarim', enabled: true },
      { id: 'tp-web-ux', query: 'web tasarım kullanıcı deneyimi dijital trendler', category: 'WEB TEKNOLOJİLERİ', target_service: 'web-tasarim', enabled: true },
      { id: 'tp-seo-google', query: 'google arama algoritması seo dijital pazarlama', category: 'SEO & DİJİTAL', target_service: 'seo', enabled: true }
    ];
    const servicesList = S.store?.services || [];
    const superMode = isSuperAdmin();
    const todayCnt = Number(st.today_count || 0);
    const dailyLim = Number(cfg.daily_limit || 2);
    const blogBase = getResolvedPageSlug('blog');
    const historyRows = Array.isArray(st.history) ? st.history.slice(0, 8) : [];

    const renderTopicRowHtml = (tp, idx) => `
      <div class="zs-nb-topic-row" data-nb-topic-row="${idx}" data-nb-topic-id="${esc(tp.id || ('tp-' + (idx + 1)))}">
        <label class="zs-switch" style="margin:0" title="Bu konu akışını aktif/pasif yap">
          <input type="checkbox" class="nb-tp-enabled" ${tp.enabled !== false ? 'checked' : ''}/>
          <span></span>
        </label>
        <input class="zs-input nb-tp-query" placeholder="Google News arama konusu (örn: yapay zeka web yazılım)" value="${esc(tp.query || '')}"/>
        <input class="zs-input nb-tp-cat" placeholder="Kategori (örn: YAPAY ZEKA)" value="${esc(tp.category || 'TEKNOLOJİ')}"/>
        <select class="zs-select nb-tp-srv">
          <option value="">Hizmet: Otomatik</option>
          ${servicesList.map(srv => `<option value="${esc(srv.slug)}" ${tp.target_service === srv.slug ? 'selected' : ''}>${esc(srv.title)}</option>`).join('')}
        </select>
        <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-nb-del-topic="${idx}" title="Konuyu Sil">✕</button>
      </div>
    `;

    ROOT.innerHTML = `
      ${renderBlogModeTabBarHtml()}

      <!-- HERO COMMAND DECK -->
      <section class="zs-nb-hero">
        <div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
            <span class="zs-badge-pill" style="${cfg.enabled !== false ? 'background:rgba(16,185,129,.2);color:#6ee7b7;border-color:rgba(52,211,153,.45)' : 'background:rgba(245,158,11,.18);color:#fde68a;border-color:rgba(251,191,36,.4)'}">
              ${cfg.enabled !== false ? '● OTOPİLOT AKTİF' : '⏸ OTOPİLOT DURAKLATILDI'} · GÜNDE ${dailyLim} HABER
            </span>
            <span class="zs-badge-pill">
              ${cfg.publish_mode === 'published' ? '🚀 DOĞRUDAN YAYIN MODU' : '🛡️ TASLAK / ONAY KUYRUĞU MODU'}
            </span>
            <span class="zs-badge-pill" style="color:#67e8f9">
              📊 Bugün Hazırlanan: ${todayCnt} / ${dailyLim}
            </span>
          </div>
          <h2 style="margin:0 0 6px;font-size:20px;color:#f4fcfd">📰 Google News &amp; Yapay Zeka Otomatik Blog Stüdyosu</h2>
          <p class="zs-card-sub" style="margin:0;max-width:760px;line-height:1.55">
            Belirlediğiniz web, yazılım ve teknoloji gündemini Google News üzerinden takip eder; haberi <strong>%100 özgün Türkçe editoryal makaleye</strong> (H2/H3 alt başlıklar + SSS şeması + SEO meta) dönüştürür, kapak görselini <strong>1280×720 WebP + Logo &amp; ${esc(wm.domain_text || 'zeynart.com')} filigranıyla</strong> hazırlar ve onayınıza sunar.
          </p>
        </div>

        <div class="zs-nb-quick-fetch-box">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
            <strong style="font-size:12.5px;color:#5eead4">⚡ Anında Haber Çek &amp; Özgünleştir</strong>
            <span style="font-size:11px;color:var(--text-dim)">0 Çerez · Otomatik Filigran</span>
          </div>
          <div style="display:grid;grid-template-columns:minmax(0,1fr) 108px;gap:8px">
            <input id="nbInstantTopicInp" class="zs-input" placeholder="İsteğe bağlı özel konu (boşsa sıradaki akıştan çeker)..."/>
            <select id="nbFetchCountSel" class="zs-select">
              <option value="1">1 Haber</option>
              <option value="2">2 Haber</option>
            </select>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-primary" style="flex:1" id="nbFetchNowBtn" ${S.newsBotFetching ? 'disabled' : ''}>
              ${S.newsBotFetching ? '⏳ Haber Çekiliyor & Filigranlanıyor...' : '⚡ Şimdi Google News\'ten Haber Çek'}
            </button>
            <button type="button" class="zs-btn zs-btn-ai" id="nbSaveCfgBtn">✓ Ayarları Kaydet</button>
          </div>
        </div>
      </section>

      <!-- APPROVAL QUEUE -->
      <div class="zs-card" style="border-color:${queue.length > 0 ? 'rgba(52,211,153,.42)' : 'var(--border-subtle)'}">
        <div class="zs-card-head">
          <div>
            <h3 style="display:flex;align-items:center;gap:8px;margin:0">
              <span>⏳ Taslak / Onay Kuyruğu</span>
              <span class="zs-pill ${queue.length > 0 ? 'zs-pill-pub' : 'zs-pill-cat'}">${queue.length} Bekleyen Haber</span>
            </h3>
            <p class="zs-card-sub" style="margin-top:4px">
              Buradaki haberler ziyaretçilere kapalıdır. İnceleyip <strong>Onayla &amp; Yayınla</strong> dediğinizde anında <strong>TR · EN · DE · RU</strong> dillerinde yayına alınır ve arama motorlarına bildirilir.
            </p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            ${queue.length > 1 ? `
              <button type="button" class="zs-btn zs-btn-primary" id="nbApproveAllBtn">
                ✓ Tümünü Onayla &amp; Yayınla (${queue.length})
              </button>
            ` : ''}
            <button type="button" class="zs-btn zs-btn-sm" id="nbRefreshQueueBtn">↻ Kuyruğu Yenile</button>
          </div>
        </div>

        ${queue.length === 0 ? `
          <div class="zs-glass-empty-note" style="padding:26px;text-align:center">
            <strong style="display:block;font-size:14.5px;color:#e6f7f9;margin-bottom:6px">Şu an onay bekleyen haber taslağı bulunmuyor.</strong>
            <span style="font-size:12.5px;color:var(--text-muted)">Sağ üstteki <strong>"⚡ Şimdi Google News'ten Haber Çek"</strong> butonuna tıklayarak saniyeler içinde yeni bir teknoloji haberi hazırlatabilir veya günlük otopilotun otomatik çekmesini bekleyebilirsiniz.</span>
          </div>
        ` : `
          <div class="zs-nb-queue-grid" id="nbQueueGrid">
            ${queue.map(it => {
              const nb = it.news_bot || {};
              const isRemotePhoto = nb.cover_source === 'publisher_photo';
              const coverTag = isRemotePhoto ? '📸 Kaynak Fotoğraf + Filigran' : '🎨 Özel Stüdyo Kapak + Filigran';
              const srcName = nb.source_name || 'Google News';
              const srcUrl = nb.source_url || nb.rss_link || '';
              const origTitle = nb.original_title || '';
              return `
                <article class="zs-nb-draft-card" data-nb-draft-card="${esc(it.slug)}">
                  <div class="zs-nb-draft-cover">
                    <img src="${esc(resolveUrl(it.image || '/images/journal/ai-workflow.webp'))}" alt="${esc(it.title)}" loading="lazy"/>
                    <div class="zs-nb-draft-badges">
                      <span class="zs-nb-chip-dark">${coverTag}</span>
                      <span class="zs-nb-chip-dark" style="color:#6ee7b7">SEO %${Number(it.seo_score || 94)}</span>
                    </div>
                  </div>
                  <div class="zs-nb-draft-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
                      <span class="zs-pill zs-pill-cat">${esc(it.category || 'TEKNOLOJİ')}</span>
                      <span style="font-size:11.5px;color:var(--text-dim)">${esc(it.date_display || it.date || '')} · ${esc(it.read || '4 dk')}</span>
                    </div>

                    <div class="zs-nb-source-strip" title="${esc(origTitle)}">
                      <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        📰 Kaynak: <strong style="color:#e2f6f8">${esc(srcName)}</strong>${origTitle ? ` — ${esc(origTitle)}` : ''}
                      </span>
                      ${srcUrl ? `<a href="${esc(srcUrl)}" target="_blank" rel="noopener noreferrer">Kaynağı Gör ↗</a>` : ''}
                    </div>

                    <h4 class="zs-nb-draft-title">${esc(it.title)}</h4>
                    <p class="zs-nb-draft-excerpt">${esc(it.excerpt || '')}</p>

                    <div class="zs-nb-draft-actions">
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" data-nb-approve="${esc(it.slug)}">
                        ✓ Onayla &amp; Yayınla
                      </button>
                      <button type="button" class="zs-btn zs-btn-sm" data-nb-edit="${esc(it.slug)}">
                        ✏️ Düzenle
                      </button>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-nb-canvas="${esc(it.slug)}" data-nb-img="${esc(it.image || '')}" data-nb-title="${esc(it.title)}">
                        🎨 Kapak Kanvas
                      </button>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-nb-ig="${esc(it.slug)}" title="1080×1080 Instagram & 1080×1920 Story Görseli + Hazır Metin">
                        📸 IG/Story
                      </button>
                      <a href="${esc(resolveUrl('/' + blogBase + '/' + it.slug + '/'))}" target="_blank" class="zs-btn zs-btn-sm" title="Yönetici Taslak Önizlemesi">Önizle ↗</a>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" style="margin-left:auto" data-nb-reject="${esc(it.slug)}" title="Sil ve aynı haberi bir daha çekme">
                        ✕ Reddet
                      </button>
                    </div>
                  </div>
                </article>
              `;
            }).join('')}
          </div>
        `}
      </div>

      <!-- 2-COLUMN SETTINGS & LIVE WATERMARK STUDIO -->
      <div class="zs-grid-2" style="align-items:start">
        <!-- LEFT: SCHEDULE, TOPICS & RSS -->
        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:14px">
            <div>
              <h3>⏱️ Günlük Otopilot, Yayın Akışı &amp; Google News Konuları</h3>
              <p class="zs-card-sub">Günde kaç haber hazırlanacağını, onay modunu ve takip edilecek teknoloji konularını belirleyin.</p>
            </div>
          </div>

          <div style="padding:12px 14px;border-radius:12px;background:rgba(16,185,129,.08);border:1px solid rgba(52,211,153,.26);margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
              <strong style="color:#6ee7b7;font-size:13.5px">Günlük Otomatik Haber Otopilotu</strong>
              <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Aktifken gün içine yayarak belirlediğiniz kotada teknoloji haberini otomatik çeker ve hazırlar.</div>
            </div>
            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="nbEnabled" ${cfg.enabled !== false ? 'checked' : ''}/>
              <span>Aktif</span>
            </label>
          </div>

          <div class="zs-grid-2">
            <div class="zs-field">
              <label>Günlük Haber Kotası</label>
              <select id="nbDailyLimit" class="zs-select">
                <option value="1" ${dailyLim === 1 ? 'selected' : ''}>Günde 1 Haber</option>
                <option value="2" ${dailyLim === 2 ? 'selected' : ''}>Günde 2 Haber (Önerilen)</option>
                <option value="3" ${dailyLim === 3 ? 'selected' : ''}>Günde 3 Haber</option>
                <option value="4" ${dailyLim === 4 ? 'selected' : ''}>Günde 4 Haber</option>
              </select>
            </div>
            <div class="zs-field">
              <label>Yayınlama İş Akışı</label>
              <select id="nbPublishMode" class="zs-select">
                <option value="draft" ${cfg.publish_mode !== 'published' ? 'selected' : ''}>Önce Taslak / Onay Kuyruğuna Düşsün (Önerilen)</option>
                <option value="published" ${cfg.publish_mode === 'published' ? 'selected' : ''}>Onay Beklemeden Doğrudan Sitede Yayınla</option>
              </select>
            </div>
            <div class="zs-field">
              <label>Haberler Arası Minimum Süre</label>
              <select id="nbIntervalHours" class="zs-select">
                <option value="2" ${Number(cfg.min_interval_hours) === 2 ? 'selected' : ''}>En az 2 saat arayla</option>
                <option value="4" ${(!cfg.min_interval_hours || Number(cfg.min_interval_hours) === 4) ? 'selected' : ''}>En az 4 saat arayla (Önerilen)</option>
                <option value="6" ${Number(cfg.min_interval_hours) === 6 ? 'selected' : ''}>En az 6 saat arayla</option>
                <option value="8" ${Number(cfg.min_interval_hours) === 8 ? 'selected' : ''}>En az 8 saat arayla</option>
                <option value="12" ${Number(cfg.min_interval_hours) === 12 ? 'selected' : ''}>En az 12 saat arayla</option>
              </select>
            </div>
            <div class="zs-field">
              <label>Google News Kaynak Bölgesi</label>
              <select id="nbLangRegion" class="zs-select">
                <option value="TR:tr" ${(cfg.language_region || 'TR:tr') === 'TR:tr' ? 'selected' : ''}>🇹🇷 Türkiye Teknoloji Gündemi (TR)</option>
                <option value="US:en" ${cfg.language_region === 'US:en' ? 'selected' : ''}>🌐 Global Teknoloji Gündemi (US → Türkçe Özgünleştir)</option>
                <option value="DE:de" ${cfg.language_region === 'DE:de' ? 'selected' : ''}>🇪🇺 Avrupa Teknoloji Gündemi (DE → Türkçe Özgünleştir)</option>
              </select>
            </div>
          </div>

          <div style="display:flex;flex-direction:column;gap:9px;margin-bottom:16px">
            <label class="zs-switch"><input type="checkbox" id="nbAutoTranslate" ${cfg.auto_translate_on_publish !== false ? 'checked' : ''}/><span>Onaylanıp yayınlandığında <strong>İngilizce (EN), Almanca (DE) ve Rusça (RU)</strong> dillerine otomatik çevir</span></label>
            <label class="zs-switch"><input type="checkbox" id="nbSourceCredit" ${cfg.show_source_credit !== false ? 'checked' : ''}/><span>Yazı sonunda şeffaf editoryal kaynak bilgisini göster</span></label>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px">
            <label style="font-size:12.5px;font-weight:700;color:#e6f7f9">🎯 Google News Takip Konuları (Sırayla Dönüşümlü Çekilir)</label>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="nbAddTopicBtn">+ Yeni Konu Ekle</button>
          </div>
          <div id="nbTopicsList" style="margin-bottom:14px">
            ${topics.map((tp, idx) => renderTopicRowHtml(tp, idx)).join('')}
          </div>

          <div class="zs-field">
            <label>Ek Özel Teknoloji RSS Kaynakları (İsteğe Bağlı — Her satıra 1 RSS URL)</label>
            <textarea id="nbCustomRss" class="zs-textarea" style="min-height:66px;font-family:monospace;font-size:12px" placeholder="https://ornek-teknoloji-portali.com/feed/">${esc((cfg.custom_rss_urls || []).join('\n'))}</textarea>
          </div>

          ${superMode && S.newsBotCronUrl ? `
            <div style="padding:12px 14px;border-radius:12px;background:rgba(4,11,16,.84);border:1px solid var(--border-subtle)">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:6px">
                <strong style="font-size:12px;color:#5eead4">⚙️ Sıfır Ayarlı Otomatik Tetikleyici + Plesk/cPanel Cron Ucu</strong>
                <button type="button" class="zs-btn zs-btn-sm" id="nbCopyCronBtn">📋 Cron URL Kopyala</button>
              </div>
              <p style="margin:0 0 8px;font-size:11.5px;color:var(--text-muted)">
                Otopilot siteye ziyaretçi girdikçe arka planda kendiliğinden çalışır (0 çerez). Dilerseniz Plesk Zamanlanmış Görevler'e günde 2 kez çalışacak şekilde aşağıdaki URL'yi de ekleyebilirsiniz:
              </p>
              <input id="nbCronUrlInp" class="zs-input" readonly style="font-family:monospace;font-size:11.5px" value="${esc(S.newsBotCronUrl)}"/>
            </div>
          ` : ''}
        </div>

        <!-- RIGHT: HYBRID COVER, WATERMARK LIVE PREVIEW & AI ENGINE -->
        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3>🎨 Hibrit 16:9 WebP Kapak &amp; Logo + Filigran Stüdyosu</h3>
              <p class="zs-card-sub">Her haber görseline otomatik işlenecek kategori rozeti, Zeynart logosu ve domain imzasını canlı önizleyin.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm" id="nbRefreshWmBtn">↻ Önizlemeyi Yenile</button>
          </div>

          <div class="zs-nb-wm-frame">
            <img id="nbWatermarkPreviewImg" src="${esc(buildNewsWatermarkPreviewUrl(wm))}" alt="Canlı Filigranlı Haber Kapak Önizlemesi"/>
          </div>

          <div class="zs-field">
            <label>Kapak Görseli Üretim Modu</label>
            <select id="nbCoverMode" class="zs-select">
              <option value="hybrid" ${(wm.cover_mode || 'hybrid') === 'hybrid' ? 'selected' : ''}>Hibrit Akıllı Kapak — Haber fotoğrafını WebP kırp, bulamazsa özel kapak üret (Önerilen)</option>
              <option value="generated_only" ${wm.cover_mode === 'generated_only' ? 'selected' : ''}>Sadece Özel Stüdyo Kapak — Her zaman sıfırdan koyu kurumsal teknoloji kapağı üret</option>
            </select>
          </div>

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;margin-bottom:12px">
            <label class="zs-switch"><input type="checkbox" id="nbWmEnabled" ${wm.enabled !== false ? 'checked' : ''}/><span>Köşe Filigran Kapsülü İşle</span></label>
            <label class="zs-switch"><input type="checkbox" id="nbWmLogo" ${wm.show_logo !== false ? 'checked' : ''}/><span>Zeynart Logosunu Göster</span></label>
            <label class="zs-switch"><input type="checkbox" id="nbWmCatBadge" ${wm.show_category_badge !== false ? 'checked' : ''}/><span>Sol Üst Kategori Rozeti</span></label>
          </div>

          <div class="zs-grid-2">
            <div class="zs-field">
              <label>Filigran Domain / İmza Metni</label>
              <input id="nbWmDomain" class="zs-input" value="${esc(wm.domain_text || 'zeynart.com')}" placeholder="zeynart.com"/>
            </div>
            <div class="zs-field">
              <label>Filigran Köşe Konumu</label>
              <select id="nbWmPosition" class="zs-select">
                <option value="bottom-right" ${(wm.position || 'bottom-right') === 'bottom-right' ? 'selected' : ''}>Sağ Alt Köşe (Önerilen)</option>
                <option value="bottom-left" ${wm.position === 'bottom-left' ? 'selected' : ''}>Sol Alt Köşe</option>
                <option value="top-right" ${wm.position === 'top-right' ? 'selected' : ''}>Sağ Üst Köşe</option>
              </select>
            </div>
          </div>

          <div class="zs-field">
            <label>Filigran Kontrast &amp; Görünürlük Yoğunluğu: <strong id="nbWmOpacityVal" style="color:var(--accent-bright)">%${Number(wm.opacity || 92)}</strong></label>
            <input type="range" id="nbWmOpacity" min="40" max="100" step="2" value="${Number(wm.opacity || 92)}" style="width:100%;accent-color:#14b8a6"/>
          </div>

          <hr style="border:0;border-top:1px solid var(--border-subtle);margin:16px 0"/>

          <h4 style="margin:0 0 10px;font-size:14px;color:#5eead4">🤖 Yapay Zeka Özgünleştirme Motoru</h4>
          <div class="zs-field">
            <label>Tercih Edilen Makale Yazım Motoru</label>
            <select id="nbAiProvider" class="zs-select">
              <option value="auto" ${(cfg.ai_provider || 'auto') === 'auto' ? 'selected' : ''}>Otomatik (API Anahtarı varsa Gemini/OpenAI, yoksa Yerleşik Akıllı Editör)</option>
              <option value="builtin" ${cfg.ai_provider === 'builtin' ? 'selected' : ''}>Yerleşik Türkçe Teknoloji Editörü (Ücretsiz &amp; Anahtarsız)</option>
              <option value="gemini" ${cfg.ai_provider === 'gemini' ? 'selected' : ''}>Google Gemini 2.0 Flash API</option>
              <option value="openai" ${cfg.ai_provider === 'openai' ? 'selected' : ''}>OpenAI GPT-4o-mini API</option>
            </select>
          </div>

          ${superMode ? `
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Google Gemini API Anahtarı (Opsiyonel)</label>
                <input type="password" id="nbGeminiKey" class="zs-input" autocomplete="off" placeholder="AIzaSy... (Boş bırakılabilir)" value="${esc(cfg.gemini_api_key || '')}"/>
              </div>
              <div class="zs-field">
                <label>OpenAI API Anahtarı (Opsiyonel)</label>
                <input type="password" id="nbOpenaiKey" class="zs-input" autocomplete="off" placeholder="sk-... (Boş bırakılabilir)" value="${esc(cfg.openai_api_key || '')}"/>
              </div>
            </div>
          ` : ''}

          <div style="display:flex;justify-content:flex-end;margin-top:6px">
            <button type="button" class="zs-btn zs-btn-primary" id="nbSaveCfgBottomBtn">✓ Tüm Otopilot &amp; Filigran Ayarlarını Kaydet</button>
          </div>

          ${historyRows.length > 0 ? `
            <hr style="border:0;border-top:1px solid var(--border-subtle);margin:16px 0"/>
            <h4 style="margin:0 0 10px;font-size:13.5px;color:#e6f7f9">🕓 Son Çekilen Haber Geçmişi</h4>
            <div style="display:flex;flex-direction:column;gap:7px">
              ${historyRows.map(h => {
                const stPill = h.status === 'published'
                  ? '<span class="zs-pill zs-pill-pub">Yayında</span>'
                  : (h.status === 'rejected'
                    ? '<span class="zs-pill" style="background:rgba(239,68,68,.16);color:#fca5a5">Reddedildi</span>'
                    : '<span class="zs-pill zs-pill-draft">Onay Bekliyor</span>');
                return `
                  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;background:rgba(4,11,16,.72);border:1px solid var(--border-subtle)">
                    <div style="min-width:0">
                      <strong style="display:block;font-size:12.5px;color:#f1fbfc;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(h.title || '')}</strong>
                      <span style="font-size:11px;color:var(--text-dim)">${esc(h.source_name || 'Google News')} · ${esc(h.category || '')}</span>
                    </div>
                    ${stPill}
                  </div>
                `;
              }).join('')}
            </div>
          ` : ''}
        </div>
      </div>
    `;

    bindBlogModeTabBarEvents();

    const refreshLiveWmPreview = () => {
      const formSnap = collectNewsAutopilotFormState();
      const opLbl = document.getElementById('nbWmOpacityVal');
      if (opLbl) opLbl.textContent = '%' + formSnap.watermark.opacity;
      const imgEl = document.getElementById('nbWatermarkPreviewImg');
      if (imgEl) {
        imgEl.src = buildNewsWatermarkPreviewUrl(formSnap.watermark);
      }
    };

    let wmDebounce = null;
    ['nbCoverMode', 'nbWmEnabled', 'nbWmLogo', 'nbWmCatBadge', 'nbWmPosition'].forEach(id => {
      document.getElementById(id)?.addEventListener('change', refreshLiveWmPreview);
    });
    ['nbWmDomain', 'nbWmOpacity'].forEach(id => {
      document.getElementById(id)?.addEventListener('input', () => {
        const opEl = document.getElementById('nbWmOpacity');
        const opLbl = document.getElementById('nbWmOpacityVal');
        if (opEl && opLbl) opLbl.textContent = '%' + opEl.value;
        clearTimeout(wmDebounce);
        wmDebounce = setTimeout(refreshLiveWmPreview, 240);
      });
    });
    document.getElementById('nbRefreshWmBtn')?.addEventListener('click', refreshLiveWmPreview);

    const bindTopicDeleteButtons = () => {
      ROOT.querySelectorAll('[data-nb-del-topic]').forEach(btn => {
        btn.onclick = () => {
          const rows = ROOT.querySelectorAll('[data-nb-topic-row]');
          if (rows.length <= 1) {
            toast('En az 1 Google News konu akışı bulunmalıdır.', true);
            return;
          }
          btn.closest('[data-nb-topic-row]')?.remove();
        };
      });
    };
    bindTopicDeleteButtons();

    document.getElementById('nbAddTopicBtn')?.addEventListener('click', () => {
      const listEl = document.getElementById('nbTopicsList');
      if (!listEl) return;
      const nextIdx = listEl.querySelectorAll('[data-nb-topic-row]').length;
      const wrap = document.createElement('div');
      wrap.innerHTML = renderTopicRowHtml({
        id: uid('tp'),
        query: '',
        category: 'WEB & TEKNOLOJİ',
        target_service: '',
        enabled: true
      }, nextIdx);
      const newRow = wrap.firstElementChild;
      if (newRow) {
        listEl.appendChild(newRow);
        bindTopicDeleteButtons();
        newRow.querySelector('.nb-tp-query')?.focus();
      }
    });

    const saveNewsBotSettings = async () => {
      const payload = collectNewsAutopilotFormState();
      try {
        const res = await api('news_bot_save', { news_autopilot: payload });
        S.store.news_autopilot = res.news_autopilot || payload;
        if (typeof res.cron_url === 'string') S.newsBotCronUrl = res.cron_url;
        toast(res.message || 'Otopilot ayarları kaydedildi.');
        renderNewsAutopilotView(true);
      } catch (err) {
        toast(err.message, true);
      }
    };
    document.getElementById('nbSaveCfgBtn')?.addEventListener('click', saveNewsBotSettings);
    document.getElementById('nbSaveCfgBottomBtn')?.addEventListener('click', saveNewsBotSettings);

    document.getElementById('nbCopyCronBtn')?.addEventListener('click', async () => {
      const inp = document.getElementById('nbCronUrlInp');
      if (!inp) return;
      try {
        await navigator.clipboard.writeText(inp.value);
        toast('Plesk/cPanel Cron URL panoya kopyalandı.');
      } catch (_) {
        inp.select();
        toast('Cron URL seçildi (Ctrl+C ile kopyalayabilirsiniz).');
      }
    });

    document.getElementById('nbRefreshQueueBtn')?.addEventListener('click', () => renderNewsAutopilotView(false));

    document.getElementById('nbFetchNowBtn')?.addEventListener('click', async () => {
      if (S.newsBotFetching) return;
      // Save current watermark/topic settings in memory first so fetch uses latest settings!
      const liveForm = collectNewsAutopilotFormState();
      const instantTopic = document.getElementById('nbInstantTopicInp')?.value.trim() || '';
      const fetchCount = Number(document.getElementById('nbFetchCountSel')?.value || 1);
      const btn = document.getElementById('nbFetchNowBtn');
      S.newsBotFetching = true;
      if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ Google News Taranıyor & Filigranlı Kapak Hazırlanıyor...';
      }
      try {
        const saveRes = await api('news_bot_save', { news_autopilot: liveForm });
        if (saveRes.news_autopilot) S.store.news_autopilot = saveRes.news_autopilot;

        const res = await api('news_bot_fetch_now', {
          count: fetchCount,
          topic_query: instantTopic
        });
        S.newsBotQueue = res.queue || [];
        S.newsBotState = res.state || S.newsBotState;
        if (res.summary) S.summary = res.summary;
        if (S.i18nBatch.state === 'idle') S.i18nBatch.loaded = false;
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      } finally {
        S.newsBotFetching = false;
        renderNewsAutopilotView(true);
      }
    });

    document.getElementById('nbApproveAllBtn')?.addEventListener('click', async () => {
      const allSlugs = queue.map(x => x.slug).filter(Boolean);
      if (!allSlugs.length) return;
      const btn = document.getElementById('nbApproveAllBtn');
      if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ Yayınlanıyor & EN·DE·RU Çevriliyor...';
      }
      try {
        const res = await api('news_bot_approve', { slugs: allSlugs });
        S.newsBotQueue = res.queue || [];
        S.newsBotState = res.state || S.newsBotState;
        if (res.summary) S.summary = res.summary;
        if (S.i18nBatch.state === 'idle') S.i18nBatch.loaded = false;
        toast(res.message);
        renderNewsAutopilotView(true);
      } catch (err) {
        toast(err.message, true);
        if (btn) {
          btn.disabled = false;
          btn.textContent = `✓ Tümünü Onayla & Yayınla (${allSlugs.length})`;
        }
      }
    });

    ROOT.querySelectorAll('[data-nb-approve]').forEach(btn => {
      btn.onclick = async () => {
        const slug = btn.getAttribute('data-nb-approve');
        if (!slug) return;
        btn.disabled = true;
        btn.textContent = '⏳ Yayınlanıyor...';
        try {
          const res = await api('news_bot_approve', { slug });
          S.newsBotQueue = res.queue || [];
          S.newsBotState = res.state || S.newsBotState;
          if (res.summary) S.summary = res.summary;
          if (S.i18nBatch.state === 'idle') S.i18nBatch.loaded = false;
          toast(res.message);
          renderNewsAutopilotView(true);
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
          btn.textContent = '✓ Onayla & Yayınla';
        }
      };
    });

    ROOT.querySelectorAll('[data-nb-reject]').forEach(btn => {
      btn.onclick = async () => {
        const slug = btn.getAttribute('data-nb-reject');
        if (!slug) return;
        if (!window.confirm('Bu haber taslağını silmek ve aynı haberi tekrar çekilmemesi için kara listeye almak istiyor musunuz?')) return;
        btn.disabled = true;
        try {
          const res = await api('news_bot_reject', { slug });
          S.newsBotQueue = res.queue || [];
          S.newsBotState = res.state || S.newsBotState;
          if (res.summary) S.summary = res.summary;
          toast(res.message);
          renderNewsAutopilotView(true);
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      };
    });

    ROOT.querySelectorAll('[data-nb-edit]').forEach(btn => {
      btn.onclick = () => {
        const slug = btn.getAttribute('data-nb-edit');
        if (slug) {
          S.blog.returnMode = 'news_bot';
          openBlogPostForEdit(slug);
        }
      };
    });

    ROOT.querySelectorAll('[data-nb-canvas]').forEach(btn => {
      btn.onclick = () => {
        const slug = btn.getAttribute('data-nb-canvas') || '';
        const imgUrl = btn.getAttribute('data-nb-img') || '';
        const title = btn.getAttribute('data-nb-title') || slug;
        if (!imgUrl) return;
        openImageCanvasStudio({
          url: imgUrl,
          name: (imgUrl.split('/').pop() || (slug + '.webp')),
          alt: title
        }, async savedItem => {
          if (savedItem && savedItem.url && savedItem.url !== imgUrl) {
            try {
              const postRes = await api('blog_get', { slug });
              if (postRes.post) {
                postRes.post.image = savedItem.url;
                if (postRes.post.seo) postRes.post.seo.og_image = savedItem.url;
                await api('blog_save', { post: postRes.post, old_slug: slug, auto_translate: false });
              }
            } catch (_) {}
          }
          renderNewsAutopilotView(false);
        });
      };
    });

    ROOT.querySelectorAll('[data-nb-ig]').forEach(btn => {
      btn.onclick = () => {
        const slug = btn.getAttribute('data-nb-ig');
        if (slug) {
          openSocialShareStudio({ kind: 'blog', targetKey: slug, initialTab: 'ig_graphic' });
        }
      };
    });
  }

  function renderBlogEditor() {
    syncAdminUrl('replace');
    const p = S.blog.editingPost;
    const seo = p.seo || {};
    const customOgImg = (seo.og_image && seo.og_image !== p.image) ? seo.og_image : '';
    const treeCats = getClientBlogCategoriesTree();
    const defCatObj = getDefaultBlogCategoryClient();
    const selectedCatIdsInit = resolvePostCategoryIdsClient(p);
    const analysis = S.blog.seoAnalysis || { score: seo.seo_score || 85, checks: [] };
    const faqs = Array.isArray(p.faqs) ? p.faqs : [];
    const activeI18nTab = ['en', 'de', 'ru'].includes(S.blog.i18nTab) ? S.blog.i18nTab : 'en';
    const trData = (p.translations && p.translations[activeI18nTab]) ? p.translations[activeI18nTab] : {};
    const trSeo = trData.seo || {};
    const blogParentSlug = getResolvedPageSlug('blog');
    const i18nLangTabs = [
      { code: 'en', label: '🇬🇧 English (EN)', prefix: '/en/' + blogParentSlug + '/', ready: Boolean(p.translations?.en?.title) },
      { code: 'de', label: '🇩🇪 Deutsch (DE)', prefix: '/de/' + blogParentSlug + '/', ready: Boolean(p.translations?.de?.title) },
      { code: 'ru', label: '🇷🇺 Русский (RU)', prefix: '/ru/' + blogParentSlug + '/', ready: Boolean(p.translations?.ru?.title) }
    ];
    const activeLangMeta = i18nLangTabs.find(x => x.code === activeI18nTab) || i18nLangTabs[0];

    const renderChecklistItemsHtml = (catsList, checkedIdsArr, searchQuery) => {
      const checkedSet = new Set(checkedIdsArr || []);
      const q = (searchQuery || '').trim().toLocaleLowerCase('tr-TR');
      const filtered = q
        ? catsList.filter(c => c.name.toLocaleLowerCase('tr-TR').includes(q) || c.slug.toLocaleLowerCase('tr-TR').includes(q) || (c.parent_name || '').toLocaleLowerCase('tr-TR').includes(q))
        : catsList;
      if (!filtered.length) {
        return `<div style="padding:14px 10px;text-align:center;font-size:12px;color:var(--text-muted)">Aramanızla eşleşen kategori bulunamadı. Aşağıdan hemen yeni kategori ekleyebilirsiniz.</div>`;
      }
      return filtered.map(c => {
        const isChecked = checkedSet.has(c.id);
        const padLeft = 8 + Math.min(4, c.depth || 0) * 18;
        return `
          <label class="zs-pe-cat-row ${isChecked ? 'is-checked' : ''}" style="padding-left:${padLeft}px" data-pe-cat-row="${esc(c.id)}">
            <input type="checkbox" class="pe-cat-chk" value="${esc(c.id)}" data-cat-name="${esc(c.name)}" data-cat-slug="${esc(c.slug)}" ${isChecked ? 'checked' : ''}/>
            <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              ${c.depth > 0 ? `<span style="color:var(--accent-bright);font-weight:700;margin-right:3px">↳</span>` : ''}${esc(c.name)}
            </span>
            ${c.is_default ? `<span class="zs-cat-default-badge" style="font-size:9.5px;padding:1px 6px">★ Varsayılan</span>` : ''}
          </label>
        `;
      }).join('');
    };

    const renderSelectedCatsPillsHtml = (catsList, checkedIdsArr) => {
      const byId = {};
      catsList.forEach(c => { byId[c.id] = c; });
      const chosen = (checkedIdsArr || []).map(id => byId[id]).filter(Boolean);
      if (!chosen.length) {
        return `<span style="font-size:11.5px;color:#fbbf24">Kategori seçilmedi — kaydedildiğinde otomatik olarak <strong>${esc(defCatObj.name)}</strong> (Varsayılan) atanır.</span>`;
      }
      return chosen.map((c, idx) =>
        `<span class="zs-pill zs-pill-cat" title="${idx === 0 ? 'Ana vitrin kategorisi' : 'Ek kategori'}">${idx === 0 ? '★ ' : ''}${esc(c.name)}</span>`
      ).join('');
    };

    ROOT.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:10px">
          <button type="button" class="zs-btn" id="peBackBtn">${S.blog.returnMode === 'news_bot' ? '← Haber Kuyruğuna Dön' : '← Tüm Yazılar'}</button>
          <span class="zs-badge-pill">${S.blog.oldSlug ? 'MEVCUT YAZIYI DÜZENLE' : 'YENİ BLOG YAZISI'}</span>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          ${p.slug ? `<a href="${esc(resolveUrl('/' + blogParentSlug + '/' + p.slug + '/'))}" target="_blank" class="zs-btn">Yazıyı Sitede Gör ↗</a>` : ''}
          <button type="button" class="zs-btn zs-btn-ai" id="peShareStudioBtn">📸 IG / Story Görseli &amp; Paylaş</button>
          <button type="button" class="zs-btn" id="peAiGenerateBtn">✦ Yapay Zeka ile SEO & Özet Üret</button>
          <button type="button" class="zs-btn zs-btn-primary" id="peSaveBtn">✓ Yazıyı Kaydet ve Yayınla</button>
        </div>
      </div>

      <div class="zs-editor-layout">
        <div>
          <div class="zs-ai-writer-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:12px">
              <div>
                <span class="zs-badge-pill" style="background:rgba(16,185,129,.18);color:#6ee7b7;border-color:rgba(52,211,153,.4)">🤖 TEK TIKLA YAPAY ZEKA BLOG &amp; SSS YAZARI</span>
                <h3 style="margin:6px 0 3px;font-size:15.5px">Konuyu Yazın — Makale, Numaralı Alt Başlıklar, SEO ve SSS Anında Hazırlansın</h3>
                <p class="zs-card-sub" style="margin:0">API anahtarı gerekmeden yerleşik editoryal motorla (veya ayarlardaki OpenAI anahtarınızla) %98+ SEO uyumlu Türkçe makale ve FAQ şeması üretir.</p>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:minmax(220px,2.2fr) minmax(130px,1fr) minmax(130px,1fr) minmax(150px,1.2fr);gap:10px;margin-bottom:10px">
              <div class="zs-field" style="margin:0">
                <label>Makale Konusu veya Odak Anahtar Kelime *</label>
                <input id="aiWriterTopic" class="zs-input" placeholder="Örn: 2026 Kurumsal Web Tasarım Trendleri ve Hız Optimizasyonu" value="${esc(S.aiWriterDraft.topic || p.title || '')}"/>
              </div>
              <div class="zs-field" style="margin:0">
                <label>Anlatım Tonu</label>
                <select id="aiWriterTone" class="zs-select">
                  <option value="kurumsal" ${S.aiWriterDraft.tone === 'kurumsal' ? 'selected' : ''}>Kurumsal &amp; Uzman</option>
                  <option value="rehber" ${S.aiWriterDraft.tone === 'rehber' ? 'selected' : ''}>Adım Adım Rehber</option>
                  <option value="teknik" ${S.aiWriterDraft.tone === 'teknik' ? 'selected' : ''}>Teknik &amp; Analitik</option>
                  <option value="ikna" ${S.aiWriterDraft.tone === 'ikna' ? 'selected' : ''}>Dönüşüm &amp; Satış Odaklı</option>
                </select>
              </div>
              <div class="zs-field" style="margin:0">
                <label>Makale Kapsamı</label>
                <select id="aiWriterLength" class="zs-select">
                  <option value="standart" ${S.aiWriterDraft.length === 'standart' ? 'selected' : ''}>Standart (4 Bölüm)</option>
                  <option value="kapsamli" ${S.aiWriterDraft.length !== 'standart' && S.aiWriterDraft.length !== 'derin' ? 'selected' : ''}>Kapsamlı (5 Bölüm + Tablo)</option>
                  <option value="derin" ${S.aiWriterDraft.length === 'derin' ? 'selected' : ''}>Derinlemesine Rehber (6 Bölüm)</option>
                </select>
              </div>
              <div class="zs-field" style="margin:0">
                <label>İç Link Verilecek Hizmet</label>
                <select id="aiWriterService" class="zs-select">
                  <option value="">Otomatik Eşleştir</option>
                  ${(S.store?.services || []).map(srv => `<option value="${esc(srv.slug)}" ${S.aiWriterDraft.target_service === srv.slug ? 'selected' : ''}>${esc(srv.title)}</option>`).join('')}
                </select>
              </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
              <span style="font-size:12px;color:var(--text-muted)">✨ Kaydettiğinizde otomatik olarak <strong>EN · DE · RU</strong> dillerine de çevrilir.</span>
              <button type="button" class="zs-btn zs-btn-primary" id="peRunFullAiWriterBtn">🤖 Tek Tıkla Makale, İçindekiler &amp; SSS Üret</button>
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-field">
              <label>Yazı Başlığı (H1) *</label>
              <input id="peTitle" class="zs-input" style="font-size:18px;font-weight:700;padding:13px 15px" placeholder="Örn: Kurumsal Web Sitesi Yaptırmadan Önce Bilmeniz Gerekenler" value="${esc(p.title)}"/>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Kalıcı Bağlantı (URL Slug) <span style="color:var(--accent-bright)">/${esc(blogParentSlug)}/<b id="peSlugPreview">${esc(p.slug || 'yeni-yazi')}</b>/</span></label>
                <input id="peSlug" class="zs-input" placeholder="Otomatik oluşturulur" value="${esc(p.slug)}"/>
              </div>
              <div class="zs-field">
                <label>Seçili Kategoriler</label>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;min-height:42px;padding:6px 11px;border-radius:10px;background:rgba(5,12,17,.78);border:1px solid var(--border-subtle)">
                  <div id="peTopCatsSummary" style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">${renderSelectedCatsPillsHtml(treeCats, selectedCatIdsInit)}</div>
                  <button type="button" class="zs-btn zs-btn-sm" id="peFocusCatsPanelBtn" style="flex-shrink:0">Seç / Ekle →</button>
                </div>
              </div>
            </div>
            <div class="zs-field">
              <label>Kısa Özet (Liste Kartı & Giriş Spotu)</label>
              <textarea id="peExcerpt" class="zs-textarea" style="min-height:70px" placeholder="Yazının ana fikrini özetleyen 1-2 cümle...">${esc(p.excerpt)}</textarea>
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <h3>Yazı İçeriği & Görsel Editör</h3>
                <p class="zs-card-sub">Yazı içine bilgisayarınızdan veya Medya Kütüphanesinden tek tıkla görsel ekleyebilirsiniz.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm" id="peToggleSourceBtn">${S.blog.sourceMode ? 'Görsel Editöre Geç' : '</> HTML Kaynak Kodu'}</button>
            </div>

            <div class="zs-wysiwyg-box">
              <div class="zs-wysiwyg-toolbar">
                <button type="button" class="zs-tool-btn" data-wcmd="formatBlock" data-wval="H2">H2 Başlık</button>
                <button type="button" class="zs-tool-btn" data-wcmd="formatBlock" data-wval="H3">H3 Alt Başlık</button>
                <button type="button" class="zs-tool-btn" data-wcmd="formatBlock" data-wval="P">Paragraf</button>
                <button type="button" class="zs-tool-btn" data-wcmd="bold"><b>B</b></button>
                <button type="button" class="zs-tool-btn" data-wcmd="italic"><i>I</i></button>
                <button type="button" class="zs-tool-btn" data-wcmd="underline"><u>U</u></button>
                <button type="button" class="zs-tool-btn" data-wcmd="insertUnorderedList">• Liste</button>
                <button type="button" class="zs-tool-btn" data-wcmd="insertOrderedList">1. Liste</button>
                <button type="button" class="zs-tool-btn" data-wcmd="formatBlock" data-wval="BLOCKQUOTE">“ Alıntı</button>
                <button type="button" class="zs-tool-btn" id="peInsertLinkBtn">🔗 Link</button>
                <button type="button" class="zs-tool-btn" id="peInsertMediaBtn" style="border-color:rgba(94,234,212,.4);color:var(--accent-bright)">🖼 Kütüphaneden Resim Ekle</button>
                <button type="button" class="zs-tool-btn" id="peQuickUploadInlineBtn">⬆ Bilgisayardan Resim Yükle</button>
                <input type="file" id="peInlineFileInp" multiple accept="image/*" style="display:none"/>
              </div>
              <div id="peVisualArea" class="zs-wysiwyg-area" contenteditable="true" style="display:${S.blog.sourceMode ? 'none' : 'block'}">${p.content_html || ''}</div>
              <textarea id="peSourceArea" class="zs-wysiwyg-source" style="display:${S.blog.sourceMode ? 'block' : 'none'}">${esc(p.content_html || '')}</textarea>
            </div>
          </div>

          <div class="zs-card" style="border-color:rgba(94,234,212,.28)">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <h3 style="color:#5eead4">🌍 Çoklu Dil & Otomatik Çeviri (İngilizce · Almanca · Rusça)</h3>
                <p class="zs-card-sub">Yazıyı kaydettiğinizde <strong>/en/blog/</strong>, <strong>/de/blog/</strong> ve <strong>/ru/blog/</strong> sayfalarına otomatik çevrilerek eklenir.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="peTranslateNowBtn">🌍 Şimdi EN · DE · RU Çevir</button>
            </div>

            <div style="padding:10px 14px;border-radius:11px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.22);margin-bottom:14px">
              <label class="zs-switch" style="margin:0">
                <input type="checkbox" id="peAutoTranslate" ${S.blog.autoTranslate !== false ? 'checked' : ''}/>
                <span><strong>Kaydederken İngilizce (EN), Almanca (DE) ve Rusça (RU) dillerine otomatik çevir ve yayınla</strong></span>
              </label>
            </div>

            <div class="zs-tabs" style="margin-bottom:14px">
              ${i18nLangTabs.map(lt => `
                <button type="button" class="zs-tab ${activeI18nTab === lt.code ? 'active' : ''}" data-pe-i18n-tab="${lt.code}">
                  ${lt.label} ${lt.ready ? '<span style="color:#34d399;margin-left:4px">✓</span>' : '<span style="opacity:.5;margin-left:4px">•</span>'}
                </button>
              `).join('')}
            </div>

            <div style="padding:14px;border-radius:12px;background:rgba(5,12,17,.75);border:1px solid var(--border-subtle)">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                <div style="font-size:12.5px;color:var(--text-muted)">
                  Canlı Adres: <strong style="color:var(--accent-bright)">${esc(activeLangMeta.prefix + (trData.slug || p.slug || 'auto-slug') + '/')}</strong>
                  ${(S.blog.oldSlug && trData.title) ? `<a href="${esc(resolveUrl(activeLangMeta.prefix + (trData.slug || p.slug) + '/'))}" target="_blank" style="margin-left:8px;color:#5eead4">Sitede Gör ↗</a>` : ''}
                </div>
                <label class="zs-switch" style="margin:0;font-size:12px">
                  <input type="checkbox" id="peTrManualLock" ${trData.manual_lock ? 'checked' : ''}/>
                  <span>Manuel düzenlemeyi kilitle (otomatik çeviride koru)</span>
                </label>
              </div>

              <div class="zs-field">
                <label>Çevrilmiş Başlık (${activeI18nTab.toUpperCase()})</label>
                <input id="peTrTitle" class="zs-input" placeholder="Kaydettiğinizde veya 'Şimdi Çevir'e bastığınızda otomatik doldurulur..." value="${esc(trData.title || '')}"/>
              </div>

              <div class="zs-grid-2">
                <div class="zs-field">
                  <label>Çevrilmiş URL Uzantısı (${activeI18nTab.toUpperCase()} Slug)</label>
                  <input id="peTrSlug" class="zs-input" placeholder="Otomatik SEO uyumlu URL üretilir" value="${esc(trData.slug || '')}"/>
                </div>
                <div class="zs-field">
                  <label>Çevrilmiş Kategori</label>
                  <input id="peTrCategory" class="zs-input" placeholder="Otomatik çevrilir" value="${esc(trData.category || '')}"/>
                </div>
              </div>

              <div class="zs-field">
                <label>Çevrilmiş Kısa Özet (Excerpt)</label>
                <textarea id="peTrExcerpt" class="zs-textarea" style="min-height:60px" placeholder="Otomatik çevrilir...">${esc(trData.excerpt || '')}</textarea>
              </div>

              <div class="zs-grid-2">
                <div class="zs-field">
                  <label>SEO Başlığı (${activeI18nTab.toUpperCase()} Meta Title)</label>
                  <input id="peTrMetaTitle" class="zs-input" placeholder="Otomatik çevrilir" value="${esc(trSeo.meta_title || '')}"/>
                </div>
                <div class="zs-field">
                  <label>SEO Açıklaması (${activeI18nTab.toUpperCase()} Meta Description)</label>
                  <input id="peTrMetaDesc" class="zs-input" placeholder="Otomatik çevrilir" value="${esc(trSeo.meta_desc || '')}"/>
                </div>
              </div>

              <div class="zs-field" style="margin-bottom:0">
                <label>Çevrilmiş Makale İçeriği (${activeI18nTab.toUpperCase()} HTML — Tüm başlık, liste ve resimler korunur)</label>
                <textarea id="peTrContentHtml" class="zs-wysiwyg-source" style="min-height:170px" placeholder="Kaydettiğinizde Türkçe makale HTML yapısı bozulmadan bu dile otomatik çevrilir...">${esc(trData.content_html || '')}</textarea>
              </div>
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head">
              <div>
                <h3>Sıkça Sorulan Sorular (FAQPage Zengin Şema & AI Yanıt Kutuları)</h3>
                <p class="zs-card-sub">Eklediğiniz sorular makale altında akordeon olarak görünür ve Google / Yapay Zeka şemasına otomatik işlenir.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm" id="peAddFaqBtn">+ Soru Ekle</button>
            </div>
            <div id="peFaqList">
              ${faqs.map((fq, idx) => `
                <div class="zs-sort-row" style="flex-direction:column;align-items:stretch" data-faq-idx="${idx}">
                  <div style="display:flex;gap:8px">
                    <input class="zs-input pe-faq-q" placeholder="Soru başlığı..." value="${esc(fq.q)}"/>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-remove-faq="${idx}">Sil</button>
                  </div>
                  <textarea class="zs-textarea pe-faq-a" style="min-height:60px" placeholder="Net ve bilgilendirici yanıt...">${esc(fq.a)}</textarea>
                </div>
              `).join('')}
            </div>
          </div>
        </div>

        <!-- RIGHT SIDEBAR: CATEGORIES, PUBLISH, COVER IMAGE, NORMAL SEO + AI SEO -->
        <div>
          <div class="zs-card" id="peCategoriesCard">
            <div class="zs-card-head" style="margin-bottom:10px">
              <div>
                <h3 style="margin:0">📂 Blog Kategorileri</h3>
                <p class="zs-card-sub">Birden fazla kategori seçebilir veya ayrılmadan yeni kategori ekleyebilirsiniz.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm" id="peManageCatsJumpBtn" title="Kategori Yönetimi ekranını aç">Tümünü Yönet ↗</button>
            </div>

            <div class="zs-field" style="margin-bottom:8px">
              <input type="search" id="peCatSearchInp" class="zs-input" style="padding:7px 11px;font-size:12.5px" placeholder="Kategorilerde ara..."/>
            </div>

            <div id="peCatChecklist" class="zs-pe-cat-checklist">
              ${renderChecklistItemsHtml(treeCats, selectedCatIdsInit, '')}
            </div>

            <div id="peSelectedCatsSummary" style="display:flex;gap:5px;flex-wrap:wrap;margin-top:9px;min-height:22px">
              ${renderSelectedCatsPillsHtml(treeCats, selectedCatIdsInit)}
            </div>

            <div style="margin-top:10px">
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="peToggleQuickCatBtn" style="width:100%;justify-content:center">
                + Yeni Kategori Ekle
              </button>
              <div id="peQuickCatBox" class="zs-pe-cat-quick-box" style="display:${S.blog.quickCatOpen ? 'grid' : 'none'}">
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11.5px">Yeni Kategori Adı *</label>
                  <input id="peQuickCatName" class="zs-input" style="padding:7px 10px;font-size:12.5px" placeholder="Örn: Teknik SEO &amp; GEO"/>
                </div>
                <div class="zs-field" style="margin:0">
                  <label style="font-size:11.5px">Üst Kategori (İsteğe Bağlı)</label>
                  <select id="peQuickCatParent" class="zs-select" style="padding:7px 10px;font-size:12.5px">
                    <option value="">— Ana Kategori —</option>
                    ${treeCats.map(c => `<option value="${esc(c.id)}">${esc(c.indent_prefix + c.name)}</option>`).join('')}
                  </select>
                </div>
                <div style="display:flex;gap:6px">
                  <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="peQuickCatSubmitBtn" style="flex:1;justify-content:center">+ Ekle ve İşaretle</button>
                  <button type="button" class="zs-btn zs-btn-sm" id="peQuickCatCancelBtn">Gizle</button>
                </div>
              </div>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">Yayın & Vitrin Durumu</h3>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Durum</label>
                <select id="peStatus" class="zs-select">
                  <option value="published" ${p.status !== 'draft' ? 'selected' : ''}>Yayında</option>
                  <option value="draft" ${p.status === 'draft' ? 'selected' : ''}>Taslak</option>
                </select>
              </div>
              <div class="zs-field">
                <label>Yayın Tarihi</label>
                <input type="date" id="peDate" class="zs-input" value="${esc(p.date || '')}"/>
              </div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Uzman Yazar Profili (E-E-A-T)</label>
                <select id="peAuthorId" class="zs-select">
                  ${(Array.isArray(S.store?.authors) && S.store.authors.length ? S.store.authors : [{ id: 'editorial-team', name: 'Zeynart Dijital Strateji & Yazılım Ekibi' }]).map(au => `
                    <option value="${esc(au.id)}" ${(p.author_id || '') === au.id ? 'selected' : ''}>${esc(au.name)}</option>
                  `).join('')}
                  <option value="" ${!p.author_id ? 'selected' : ''}>Özel Görünen İsim Kullan</option>
                </select>
              </div>
              <div class="zs-field"><label>Görünen Yazar Adı</label><input id="peAuthor" class="zs-input" value="${esc(p.author || 'Zeynart Web Bilişim')}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Makale Şema Türü (Schema.org)</label>
                <select id="peSchemaType" class="zs-select">
                  ${[
                    ['BlogPosting', 'BlogPosting (Standart Makale)'],
                    ['TechArticle', 'TechArticle (Teknik Rehber)'],
                    ['NewsArticle', 'NewsArticle (Sektörel Haber)'],
                    ['Article', 'Article (Kurumsal İçerik)']
                  ].map(([val, lbl]) => `<option value="${val}" ${(seo.schema_type || 'BlogPosting') === val ? 'selected' : ''}>${lbl}</option>`).join('')}
                </select>
              </div>
              <div class="zs-field"><label>Okuma Süresi</label><input id="peRead" class="zs-input" placeholder="Otomatik (örn: 4 dk)" value="${esc(p.read || '')}"/></div>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:6px">
              <label class="zs-switch"><input type="checkbox" id="peFeatured" ${p.featured ? 'checked' : ''}/><span>Blog Manşet Yazısı (En Üstte Büyük Göster)</span></label>
              <label class="zs-switch"><input type="checkbox" id="peCurated" ${p.curated ? 'checked' : ''}/><span>Editör Seçkisi vitrininde öne çıkar</span></label>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 12px">Kapak & Paylaşım Görseli</h3>
            <div style="aspect-ratio:16/9;border-radius:12px;overflow:hidden;border:1px solid var(--border-subtle);background:#050c0f;margin-bottom:12px">
              <img id="peCoverPreview" src="${esc(resolvePreviewUrl(p.image || '/images/journal/web-planning.webp', Date.now()))}" alt="" style="width:100%;height:100%;object-fit:cover"/>
            </div>
            <div class="zs-field">
              <label>Görsel Adresi (/uploads/...)</label>
              <input id="peImage" class="zs-input" value="${esc(p.image || '')}"/>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:10px">
              <button type="button" class="zs-btn zs-btn-sm" style="flex:1" id="pePickCoverBtn">Kütüphaneden Seç</button>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" style="flex:1" id="peUploadCoverBtn">Bilgisayardan Yükle</button>
              <input type="file" id="peCoverFileInp" accept="image/*" style="display:none"/>
            </div>
            <div style="padding:10px;border-radius:11px;background:rgba(20,184,166,.09);border:1px solid rgba(94,234,212,.28);display:grid;gap:7px;margin-bottom:12px">
              <span style="font-size:11.5px;font-weight:700;color:#5eead4">🎨 Kapak Görseline Marka / Domain Filigranı Ekle</span>
              <div style="display:flex;gap:7px;flex-wrap:wrap">
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" style="flex:1" id="peCanvasCoverBtn">🎨 Görsel Kanvas Editörü</button>
                <button type="button" class="zs-btn zs-btn-sm" style="flex:1" id="peQuickStampCoverBtn">🏷️ Tek Tıkla Filigranla</button>
              </div>
            </div>
            <div class="zs-field" style="margin-bottom:0">
              <label>Görsel Alt Etiketi (Görsel SEO)</label>
              <input id="peImageAlt" class="zs-input" placeholder="Görseli anlatan SEO açıklaması" value="${esc(p.image_alt || '')}"/>
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <h3>Normal SEO & Yapay Zeka SEO</h3>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="peAiSideBtn">✦ Otomatik Doldur</button>
            </div>

            <div id="peSeoGaugeBox">
              <div class="zs-seo-gauge-wrap">
                ${renderSeoRing(analysis.score)}
                <div>
                  <strong style="display:block;font-size:14px">SEO & GEO Uyumluluk Skoru</strong>
                  <span style="font-size:12px;color:var(--text-muted)">Google, Bing, ChatGPT, Perplexity ve Gemini analizi</span>
                </div>
              </div>
            </div>

            <div class="zs-serp-card">
              <div class="zs-serp-url">zeynart.com › blog › <span id="serpSlug">${esc(p.slug || 'yazi-adresi')}</span></div>
              <div class="zs-serp-title" id="serpTitle">${esc(seo.meta_title || (p.title ? p.title + ' | ZEYNART' : 'Yazı Başlığı | ZEYNART'))}</div>
              <div class="zs-serp-desc" id="serpDesc">${esc(seo.meta_desc || p.excerpt || 'Google arama sonuçlarında görünecek meta açıklama önizlemesi.')}</div>
            </div>

            <div class="zs-field">
              <label>SEO Başlığı (Meta Title) <span id="peMetaTitleLen">0/65</span></label>
              <input id="peMetaTitle" class="zs-input" value="${esc(seo.meta_title || '')}"/>
            </div>
            <div class="zs-field">
              <label>Meta Açıklama (Description) <span id="peMetaDescLen">0/160</span></label>
              <textarea id="peMetaDesc" class="zs-textarea" style="min-height:74px">${esc(seo.meta_desc || '')}</textarea>
            </div>
            <div class="zs-field">
              <label>Odak Anahtar Kelime</label>
              <input id="peFocusKw" class="zs-input" placeholder="örn: kurumsal web tasarım" value="${esc(seo.focus_keyword || '')}"/>
            </div>
            <div class="zs-field">
              <label>Yapay Zeka Özeti (AI / LLM Summary — GEO)</label>
              <textarea id="peAiSummary" class="zs-textarea" style="min-height:82px" placeholder="ChatGPT, Perplexity ve Google AI yanıt motorları için net, olgusal özet...">${esc(seo.ai_summary || '')}</textarea>
            </div>
            <div class="zs-field">
              <label>İndeksleme Kuralı (Robots)</label>
              <select id="peRobots" class="zs-select">
                <option value="index, follow" ${(seo.robots || 'index, follow') === 'index, follow' ? 'selected' : ''}>index, follow (Önerilen)</option>
                <option value="noindex, follow" ${seo.robots === 'noindex, follow' ? 'selected' : ''}>noindex, follow</option>
                <option value="noindex, nofollow" ${seo.robots === 'noindex, nofollow' ? 'selected' : ''}>noindex, nofollow</option>
              </select>
            </div>

            <div style="padding:14px;border-radius:12px;background:rgba(5,12,17,.85);border:1px solid var(--border-subtle);margin-bottom:14px">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
                <strong style="font-size:13px;color:#5eead4">📣 Sosyal Medya Kartı (WhatsApp / LinkedIn / FB / X)</strong>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="peOpenShareStudioSideBtn">Paylaş &amp; Denetle →</button>
              </div>
              <div class="zs-og-preview-card" style="margin-bottom:12px">
                <div class="zs-og-preview-media">
                  <img id="peOgPrevImg" src="${esc(resolveUrl(customOgImg || p.image || '/images/journal/web-planning.webp'))}" alt=""/>
                  <span class="zs-og-preview-badge">1200×630 JPG Otomatik</span>
                </div>
                <div class="zs-og-preview-body">
                  <div class="zs-og-preview-domain">zeynart.com</div>
                  <div class="zs-og-preview-title" id="peOgPrevTitle">${esc(seo.og_title || seo.meta_title || p.title || 'Yazı Başlığı | ZEYNART')}</div>
                  <div class="zs-og-preview-desc" id="peOgPrevDesc">${esc(seo.og_desc || seo.meta_desc || p.excerpt || 'Link sosyal medyaya yapıştırıldığında görünecek açıklama...')}</div>
                </div>
              </div>
              <div class="zs-field" style="margin-bottom:8px">
                <label>Özel Sosyal Başlık (og:title — İsteğe Bağlı)</label>
                <input id="peOgTitle" class="zs-input" placeholder="Boşsa SEO/Yazı başlığı kullanılır" value="${esc(seo.og_title || '')}"/>
              </div>
              <div class="zs-field" style="margin-bottom:8px">
                <label>Özel Sosyal Açıklama (og:description — İsteğe Bağlı)</label>
                <textarea id="peOgDesc" class="zs-textarea" style="min-height:56px" placeholder="Boşsa Meta açıklama / özet kullanılır">${esc(seo.og_desc || '')}</textarea>
              </div>
              <div class="zs-field" style="margin-bottom:0">
                <label>Özel Sosyal Paylaşım Görseli (Boşsa Kapak Görseli Kullanılır)</label>
                <div class="zs-input-group">
                  <input id="peOgImage" class="zs-input" placeholder="Varsayılan kapak görseli aktif" value="${esc(customOgImg)}"/>
                  <button type="button" class="zs-btn zs-btn-sm" id="pePickOgBtn">Seç</button>
                </div>
              </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px">
              <strong style="font-size:12.5px;color:#5eead4">🎯 16 Kriterli Gerçek Zamanlı İçerik SEO Puanlayıcı</strong>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="pePingIndexNowBtn">⚡ IndexNow'a Bildir</button>
            </div>
            <div id="peLiveSeo16Host" style="margin-bottom:10px"></div>

            <ul class="zs-check-list" id="peChecksList" style="display:none">
              ${(analysis.checks || []).map(c => `
                <li><span class="zs-check-dot ${c.ok ? 'ok' : 'warn'}">${c.ok ? '✓' : '!'}</span><span>${esc(c.label)}</span></li>
              `).join('')}
            </ul>
          </div>
        </div>
      </div>
    `;

    const visualEl = document.getElementById('peVisualArea');
    const sourceEl = document.getElementById('peSourceArea');
    const getEditorHtml = () => S.blog.sourceMode ? sourceEl.value : visualEl.innerHTML;
    const setEditorHtml = html => {
      visualEl.innerHTML = html;
      sourceEl.value = html;
    };

    let activeCheckedCatIds = selectedCatIdsInit.slice();

    const syncSelectedCatsUi = () => {
      const latestTree = getClientBlogCategoriesTree();
      const pillsHtml = renderSelectedCatsPillsHtml(latestTree, activeCheckedCatIds);
      const sideSum = document.getElementById('peSelectedCatsSummary');
      const topSum = document.getElementById('peTopCatsSummary');
      if (sideSum) sideSum.innerHTML = pillsHtml;
      if (topSum) topSum.innerHTML = pillsHtml;
    };

    const bindChecklistEvents = () => {
      const host = document.getElementById('peCatChecklist');
      if (!host) return;
      host.querySelectorAll('.pe-cat-chk').forEach(chk => {
        chk.onchange = () => {
          const cid = chk.value;
          const row = chk.closest('.zs-pe-cat-row');
          if (chk.checked) {
            if (!activeCheckedCatIds.includes(cid)) activeCheckedCatIds.push(cid);
            row?.classList.add('is-checked');
          } else {
            activeCheckedCatIds = activeCheckedCatIds.filter(x => x !== cid);
            row?.classList.remove('is-checked');
          }
          const orderIds = getClientBlogCategoriesTree().map(c => c.id);
          activeCheckedCatIds.sort((a, b) => orderIds.indexOf(a) - orderIds.indexOf(b));
          syncSelectedCatsUi();
        };
      });
    };
    bindChecklistEvents();

    document.getElementById('peFocusCatsPanelBtn')?.addEventListener('click', () => {
      const card = document.getElementById('peCategoriesCard');
      card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      document.getElementById('peCatSearchInp')?.focus();
    });

    document.getElementById('peManageCatsJumpBtn')?.addEventListener('click', () => {
      syncPostStateFromDom();
      navigateTo('blog_categories');
    });

    document.getElementById('peCatSearchInp')?.addEventListener('input', e => {
      const host = document.getElementById('peCatChecklist');
      if (!host) return;
      host.innerHTML = renderChecklistItemsHtml(getClientBlogCategoriesTree(), activeCheckedCatIds, e.target.value);
      bindChecklistEvents();
    });

    const toggleQuickCat = openState => {
      const box = document.getElementById('peQuickCatBox');
      if (!box) return;
      S.blog.quickCatOpen = typeof openState === 'boolean' ? openState : (box.style.display === 'none');
      box.style.display = S.blog.quickCatOpen ? 'grid' : 'none';
      if (S.blog.quickCatOpen) {
        document.getElementById('peQuickCatName')?.focus();
      }
    };
    document.getElementById('peToggleQuickCatBtn')?.addEventListener('click', () => toggleQuickCat());
    document.getElementById('peQuickCatCancelBtn')?.addEventListener('click', () => toggleQuickCat(false));

    const handleQuickCreateCategory = async () => {
      const nmInp = document.getElementById('peQuickCatName');
      const parSel = document.getElementById('peQuickCatParent');
      const addBtn = document.getElementById('peQuickCatSubmitBtn');
      const nmVal = nmInp?.value.trim() || '';
      if (!nmVal) {
        toast('Lütfen yeni kategori adını girin.', true);
        nmInp?.focus();
        return;
      }
      addBtn.disabled = true;
      const origTxt = addBtn.textContent;
      addBtn.textContent = '⏳ Ekleniyor...';
      try {
        const res = await api('blog_category_save', {
          category: {
            name: nmVal,
            parent_id: parSel?.value || ''
          }
        });
        syncBlogCatsFromApiPayload(res);
        const created = res.saved_category;
        if (created && created.id && !activeCheckedCatIds.includes(created.id)) {
          activeCheckedCatIds.push(created.id);
        }
        const latestTree = getClientBlogCategoriesTree();
        const orderIds = latestTree.map(c => c.id);
        activeCheckedCatIds.sort((a, b) => orderIds.indexOf(a) - orderIds.indexOf(b));

        const searchInp = document.getElementById('peCatSearchInp');
        if (searchInp) searchInp.value = '';
        const host = document.getElementById('peCatChecklist');
        if (host) {
          host.innerHTML = renderChecklistItemsHtml(latestTree, activeCheckedCatIds, '');
          bindChecklistEvents();
        }
        if (parSel) {
          parSel.innerHTML = `<option value="">— Ana Kategori —</option>` + latestTree.map(c => `<option value="${esc(c.id)}">${esc(c.indent_prefix + c.name)}</option>`).join('');
        }
        if (nmInp) nmInp.value = '';
        syncSelectedCatsUi();
        syncPostStateFromDom();
        toast(`"${created?.name || nmVal}" kategorisi oluşturuldu ve bu yazıya seçildi.`);
      } catch (err) {
        toast(err.message, true);
      } finally {
        addBtn.disabled = false;
        addBtn.textContent = origTxt;
      }
    };
    document.getElementById('peQuickCatSubmitBtn')?.addEventListener('click', handleQuickCreateCategory);
    document.getElementById('peQuickCatName')?.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleQuickCreateCategory();
      }
    });

    const syncPostStateFromDom = () => {
      p.title = document.getElementById('peTitle').value.trim();
      p.slug = document.getElementById('peSlug').value.trim() || slugifyClient(p.title);
      const latestTree = getClientBlogCategoriesTree();
      const byId = {};
      latestTree.forEach(c => { byId[c.id] = c; });
      const chosenCats = activeCheckedCatIds.map(id => byId[id]).filter(Boolean);
      p.category_ids = chosenCats.map(c => c.id);
      p.categories = chosenCats.map(c => c.name);
      p.category_slugs = chosenCats.map(c => c.slug);
      p.category = chosenCats[0] ? chosenCats[0].name : '';
      p._explicitEmptyCats = chosenCats.length === 0;
      p.excerpt = document.getElementById('peExcerpt').value.trim();
      p.content_html = getEditorHtml();
      p.status = document.getElementById('peStatus').value;
      p.date = document.getElementById('peDate').value;
      p.author_id = document.getElementById('peAuthorId')?.value || '';
      p.author = document.getElementById('peAuthor').value.trim();
      p.read = document.getElementById('peRead').value.trim();
      p.featured = document.getElementById('peFeatured').checked;
      p.curated = document.getElementById('peCurated').checked;
      p.image = document.getElementById('peImage').value.trim();
      p.image_alt = document.getElementById('peImageAlt').value.trim();
      const customOgVal = document.getElementById('peOgImage')?.value.trim() || '';
      p.seo = {
        meta_title: document.getElementById('peMetaTitle').value.trim(),
        meta_desc: document.getElementById('peMetaDesc').value.trim(),
        focus_keyword: document.getElementById('peFocusKw').value.trim(),
        ai_summary: document.getElementById('peAiSummary').value.trim(),
        robots: document.getElementById('peRobots').value,
        schema_type: document.getElementById('peSchemaType')?.value || 'BlogPosting',
        canonical: seo.canonical || '',
        og_title: document.getElementById('peOgTitle')?.value.trim() || '',
        og_desc: document.getElementById('peOgDesc')?.value.trim() || '',
        og_image: customOgVal || p.image
      };
      const faqRows = ROOT.querySelectorAll('[data-faq-idx]');
      const nextFaqs = [];
      faqRows.forEach(r => {
        const q = r.querySelector('.pe-faq-q')?.value.trim() || '';
        const a = r.querySelector('.pe-faq-a')?.value.trim() || '';
        if (q || a) nextFaqs.push({ q, a });
      });
      p.faqs = nextFaqs;

      const autoTrEl = document.getElementById('peAutoTranslate');
      if (autoTrEl) S.blog.autoTranslate = autoTrEl.checked;

      const trTitleVal = document.getElementById('peTrTitle')?.value.trim() || '';
      const trHtmlVal = document.getElementById('peTrContentHtml')?.value || '';
      const trLockVal = Boolean(document.getElementById('peTrManualLock')?.checked);
      p.translations = (p.translations && typeof p.translations === 'object') ? p.translations : {};
      if (trTitleVal || trHtmlVal || p.translations[activeI18nTab]) {
        const prevTr = p.translations[activeI18nTab] || {};
        p.translations[activeI18nTab] = Object.assign({}, prevTr, {
          lang: activeI18nTab,
          title: trTitleVal,
          slug: document.getElementById('peTrSlug')?.value.trim() || prevTr.slug || '',
          category: document.getElementById('peTrCategory')?.value.trim() || prevTr.category || '',
          excerpt: document.getElementById('peTrExcerpt')?.value.trim() || '',
          content_html: trHtmlVal,
          manual_lock: trLockVal,
          seo: Object.assign({}, prevTr.seo || {}, {
            meta_title: document.getElementById('peTrMetaTitle')?.value.trim() || '',
            meta_desc: document.getElementById('peTrMetaDesc')?.value.trim() || ''
          })
        });
      }
    };

    const updateLiveSerp = () => {
      const t = document.getElementById('peTitle').value.trim();
      const s = document.getElementById('peSlug').value.trim() || slugifyClient(t) || 'yeni-yazi';
      const mt = document.getElementById('peMetaTitle').value.trim() || (t ? t + ' | ZEYNART' : 'Başlık | ZEYNART');
      const md = document.getElementById('peMetaDesc').value.trim() || document.getElementById('peExcerpt').value.trim() || '';
      const ogt = document.getElementById('peOgTitle')?.value.trim() || mt;
      const ogd = document.getElementById('peOgDesc')?.value.trim() || md || 'Link sosyal medyaya yapıştırıldığında görünecek açıklama...';
      const ogi = document.getElementById('peOgImage')?.value.trim() || document.getElementById('peImage')?.value.trim() || '/images/journal/web-planning.webp';
      document.getElementById('peSlugPreview').textContent = s;
      document.getElementById('serpSlug').textContent = s;
      document.getElementById('serpTitle').textContent = mt;
      document.getElementById('serpDesc').textContent = md || 'Google arama sonuçlarında görünecek açıklama...';
      document.getElementById('peMetaTitleLen').textContent = mt.length + '/65';
      document.getElementById('peMetaDescLen').textContent = md.length + '/160';
      const ogPrevTitle = document.getElementById('peOgPrevTitle');
      const ogPrevDesc = document.getElementById('peOgPrevDesc');
      const ogPrevImg = document.getElementById('peOgPrevImg');
      if (ogPrevTitle) ogPrevTitle.textContent = ogt;
      if (ogPrevDesc) ogPrevDesc.textContent = ogd;
      if (ogPrevImg) ogPrevImg.src = resolvePreviewUrl(ogi, Date.now());
      refreshLiveBlogSeo16(p);
    };
    updateLiveSerp();

    ['peTitle', 'peSlug', 'peExcerpt', 'peMetaTitle', 'peMetaDesc', 'peFocusKw', 'peImageAlt', 'peOgTitle', 'peOgDesc', 'peOgImage'].forEach(id => {
      document.getElementById(id)?.addEventListener('input', updateLiveSerp);
    });
    visualEl?.addEventListener('input', () => refreshLiveBlogSeo16(p));
    sourceEl?.addEventListener('input', () => refreshLiveBlogSeo16(p));

    document.getElementById('peAuthorId')?.addEventListener('change', e => {
      const selId = e.target.value;
      const foundAu = (S.store?.authors || []).find(a => a.id === selId);
      if (foundAu) {
        document.getElementById('peAuthor').value = foundAu.name;
      }
    });

    document.getElementById('pePingIndexNowBtn')?.addEventListener('click', async function () {
      syncPostStateFromDom();
      const btn = this;
      const slugVal = p.slug || slugifyClient(p.title);
      if (!slugVal) {
        toast('Önce yazı başlığını veya URL adresini girin.', true);
        return;
      }
      btn.disabled = true;
      btn.textContent = '⏳ Gönderiliyor...';
      try {
        const res = await api('indexnow_ping', { url: '/blog/' + slugVal + '/' });
        if (res.indexnow_logs) S.indexnowLogs = res.indexnow_logs;
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      } finally {
        btn.disabled = false;
        btn.textContent = '⚡ IndexNow\'a Bildir';
      }
    });

    document.getElementById('peImage').addEventListener('input', e => {
      document.getElementById('peCoverPreview').src = resolvePreviewUrl(e.target.value.trim(), Date.now());
      updateLiveSerp();
    });

    document.getElementById('pePickOgBtn').onclick = () => {
      openMediaPicker(url => {
        document.getElementById('peOgImage').value = url;
        updateLiveSerp();
      });
    };

    const launchBlogShareStudio = () => {
      syncPostStateFromDom();
      const customOgOnly = document.getElementById('peOgImage')?.value.trim() || '';
      openSocialShareStudio({
        kind: 'blog',
        targetKey: S.blog.oldSlug || p.slug,
        unsaved: !S.blog.oldSlug,
        draft: {
          title: p.title,
          slug: p.slug,
          category: p.category,
          excerpt: p.excerpt,
          content_html: p.content_html,
          image: p.image,
          meta_title: p.seo.meta_title,
          meta_desc: p.seo.meta_desc,
          og_title: p.seo.og_title,
          og_desc: p.seo.og_desc,
          og_image: customOgOnly
        },
        onSaved: upd => {
          if (upd.post) {
            S.blog.editingPost = upd.post;
          } else {
            p.seo.og_title = upd.og_title || '';
            p.seo.og_desc = upd.og_desc || '';
            p.seo.og_image = upd.og_image || p.image;
          }
          renderBlogEditor();
        }
      });
    };
    document.getElementById('peShareStudioBtn').onclick = launchBlogShareStudio;
    document.getElementById('peOpenShareStudioSideBtn').onclick = launchBlogShareStudio;

    document.getElementById('peBackBtn').onclick = () => {
      const targetMode = S.blog.returnMode || 'list';
      S.blog.returnMode = '';
      S.blog.mode = targetMode;
      renderBlogView();
    };

    document.getElementById('peToggleSourceBtn').onclick = () => {
      if (S.blog.sourceMode) {
        visualEl.innerHTML = sourceEl.value;
        S.blog.sourceMode = false;
      } else {
        sourceEl.value = visualEl.innerHTML;
        S.blog.sourceMode = true;
      }
      syncPostStateFromDom();
      renderBlogEditor();
    };

    ROOT.querySelectorAll('[data-wcmd]').forEach(btn => {
      btn.onclick = () => {
        if (S.blog.sourceMode) {
          toast('Biçimlendirme için önce Görsel Editör moduna geçin.');
          return;
        }
        visualEl.focus();
        const cmd = btn.getAttribute('data-wcmd');
        const val = btn.getAttribute('data-wval') || null;
        document.execCommand(cmd, false, val);
      };
    });

    document.getElementById('peInsertLinkBtn').onclick = () => {
      const href = prompt('Bağlantı adresini girin (örn: https://... veya /iletisim/):', '/iletisim/');
      if (!href) return;
      if (S.blog.sourceMode) {
        sourceEl.value += `<a href="${esc(href)}">Bağlantı metni</a>`;
      } else {
        visualEl.focus();
        document.execCommand('createLink', false, href);
      }
    };

    const insertImageIntoContent = (imgUrl, imgAlt) => {
      const figureHtml = `<figure style="margin:22px 0"><img src="${esc(resolveUrl(imgUrl))}" alt="${esc(imgAlt || p.title || 'Zeynart Görsel')}" loading="lazy" decoding="async"/></figure><p></p>`;
      if (S.blog.sourceMode) {
        sourceEl.value += '\n' + figureHtml;
      } else {
        visualEl.focus();
        document.execCommand('insertHTML', false, figureHtml);
      }
      toast('Görsel yazı içeriğine eklendi.');
    };

    document.getElementById('peInsertMediaBtn').onclick = () => {
      openMediaPicker((url, alt) => insertImageIntoContent(url, alt), { allowMultiple: true });
    };

    const inlineInp = document.getElementById('peInlineFileInp');
    document.getElementById('peQuickUploadInlineBtn').onclick = () => inlineInp.click();
    inlineInp.onchange = async () => {
      if (!inlineInp.files.length) return;
      const chosenFiles = Array.from(inlineInp.files);
      inlineInp.value = '';
      if (chosenFiles.length > 1) {
        await openMediaPicker((url, alt) => insertImageIntoContent(url, alt), {
          allowMultiple: true,
          initialFiles: chosenFiles
        });
        return;
      }
      try {
        const fd = new FormData();
        fd.append('file', chosenFiles[0]);
        fd.append('optimize_webp', '1');
        toast('Görsel /uploads klasörüne yükleniyor...');
        const res = await apiUpload(fd);
        const up = res.uploaded[0];
        if (up) insertImageIntoContent(up.url, up.alt || up.name);
      } catch (err) {
        toast(err.message, true);
      }
    };

    document.getElementById('pePickCoverBtn').onclick = () => {
      openMediaPicker((url, alt) => {
        document.getElementById('peImage').value = url;
        document.getElementById('peCoverPreview').src = resolvePreviewUrl(url, Date.now());
        if (!document.getElementById('peImageAlt').value && alt) {
          document.getElementById('peImageAlt').value = alt;
        }
        updateLiveSerp();
      });
    };

    const coverFileInp = document.getElementById('peCoverFileInp');
    document.getElementById('peUploadCoverBtn').onclick = () => coverFileInp.click();
    coverFileInp.onchange = async () => {
      if (!coverFileInp.files.length) return;
      try {
        const fd = new FormData();
        fd.append('file', coverFileInp.files[0]);
        fd.append('optimize_webp', '1');
        toast('Kapak görseli yükleniyor...');
        const res = await apiUpload(fd);
        const up = res.uploaded[0];
        if (up) {
          rememberMediaMtimes([up]);
          document.getElementById('peImage').value = up.url;
          document.getElementById('peCoverPreview').src = resolvePreviewUrl(up.url, up.mtime || Date.now());
          updateLiveSerp();
          toast('Kapak görseli yüklendi ve seçildi.');
        }
      } catch (err) {
        toast(err.message, true);
      }
      coverFileInp.value = '';
    };

    document.getElementById('peCanvasCoverBtn')?.addEventListener('click', () => {
      const curCoverUrl = (document.getElementById('peImage')?.value || p.image || '').trim().split('?')[0];
      if (!curCoverUrl) {
        toast('Lütfen önce bir kapak görseli seçin veya yükleyin.', true);
        return;
      }
      const curAlt = (document.getElementById('peImageAlt')?.value || p.title || '').trim();
      openImageCanvasStudio({
        url: curCoverUrl,
        name: curCoverUrl.split('/').pop() || 'kapak.webp',
        alt: curAlt
      }, async savedItem => {
        if (!savedItem || !savedItem.url) return;
        const nextUrl = savedItem.url;
        const nextMtime = savedItem.mtime || Date.now();
        S.mediaMtimes = S.mediaMtimes || {};
        S.mediaMtimes[nextUrl] = nextMtime;
        const imgInp = document.getElementById('peImage');
        const prevEl = document.getElementById('peCoverPreview');
        const altInp = document.getElementById('peImageAlt');
        if (imgInp) imgInp.value = nextUrl;
        if (prevEl) prevEl.src = resolvePreviewUrl(nextUrl, nextMtime);
        if (altInp && !altInp.value.trim() && savedItem.alt) altInp.value = savedItem.alt;
        p.image = nextUrl;
        updateLiveSerp();
        if (S.blog.oldSlug && nextUrl !== curCoverUrl) {
          try {
            syncPostStateFromDom();
            await api('blog_save', { post: p, old_slug: S.blog.oldSlug, auto_translate: false });
            toast('Yeni filigranlı kapak görseli yazıya otomatik atandı ve yayınlandı!');
          } catch (_) {}
        }
      });
    });

    document.getElementById('peQuickStampCoverBtn')?.addEventListener('click', async function () {
      const curCoverUrl = (document.getElementById('peImage')?.value || p.image || '').trim().split('?')[0];
      if (!curCoverUrl || !curCoverUrl.startsWith('/uploads/')) {
        toast('Tek tıkla filigran için kapak görseli /uploads/ klasöründe olmalıdır (veya Görsel Kanvas Editörünü kullanın).', true);
        return;
      }
      const btn = this;
      const origTxt = btn.textContent;
      btn.disabled = true;
      btn.textContent = '⏳ İşleniyor...';
      try {
        const res = await api('media_apply_stamp', { url: curCoverUrl });
        const mt = res.mtime || Date.now();
        S.mediaMtimes = S.mediaMtimes || {};
        S.mediaMtimes[curCoverUrl] = mt;
        const prevEl = document.getElementById('peCoverPreview');
        if (prevEl) prevEl.src = resolvePreviewUrl(curCoverUrl, mt);
        updateLiveSerp();
        toast(res.message || 'Kapak görseline marka & domain filigranı işlendi!');
      } catch (err) {
        toast(err.message, true);
      } finally {
        btn.disabled = false;
        btn.textContent = origTxt;
      }
    });

    document.getElementById('peAddFaqBtn').onclick = () => {
      syncPostStateFromDom();
      p.faqs.push({ q: '', a: '' });
      renderBlogEditor();
    };

    ROOT.querySelectorAll('[data-remove-faq]').forEach(btn => {
      btn.onclick = () => {
        syncPostStateFromDom();
        p.faqs.splice(Number(btn.getAttribute('data-remove-faq')), 1);
        renderBlogEditor();
      };
    });
    ROOT.querySelectorAll('.pe-faq-q').forEach(inp => {
      inp.addEventListener('input', () => refreshLiveBlogSeo16(p));
    });

    const runAiSeoGenerator = async () => {
      syncPostStateFromDom();
      if (!p.title) {
        toast('Lütfen önce yazı başlığını girin.', true);
        return;
      }
      try {
        toast('Yapay Zeka SEO & Özet paketi üretiliyor...');
        const res = await api('ai_seo_generate', {
          title: p.title,
          content_html: p.content_html,
          category: p.category,
          image: p.image
        });
        const pack = res.pack;
        if (!S.blog.oldSlug && !document.getElementById('peSlug').value.trim()) {
          p.slug = pack.slug;
        }
        p.seo.meta_title = pack.meta_title;
        p.seo.meta_desc = pack.meta_desc;
        p.seo.focus_keyword = pack.focus_keyword;
        p.seo.ai_summary = pack.ai_summary;
        if (!p.excerpt) p.excerpt = pack.meta_desc;
        if (!p.faqs || p.faqs.length === 0) p.faqs = pack.faqs || [];
        S.blog.seoAnalysis = res.seo_analysis;
        toast(res.message);
        renderBlogEditor();
      } catch (err) {
        toast(err.message, true);
      }
    };

    document.getElementById('peAiGenerateBtn').onclick = runAiSeoGenerator;
    document.getElementById('peAiSideBtn').onclick = runAiSeoGenerator;

    const fullAiBtn = document.getElementById('peRunFullAiWriterBtn');
    if (fullAiBtn) {
      if (S.openAiWriterOnNew) {
        S.openAiWriterOnNew = false;
        setTimeout(() => document.getElementById('aiWriterTopic')?.focus(), 40);
      }
      fullAiBtn.onclick = async () => {
        syncPostStateFromDom();
        const topicVal = (document.getElementById('aiWriterTopic')?.value || p.title || '').trim();
        if (!topicVal) {
          toast('Lütfen önce makale üretilecek konuyu veya anahtar kelimeyi yazın.', true);
          document.getElementById('aiWriterTopic')?.focus();
          return;
        }
        const toneVal = document.getElementById('aiWriterTone')?.value || 'kurumsal';
        const lenVal = document.getElementById('aiWriterLength')?.value || 'kapsamli';
        const srvVal = document.getElementById('aiWriterService')?.value || '';
        S.aiWriterDraft = { topic: topicVal, tone: toneVal, length: lenVal, target_service: srvVal };

        if (p.content_html && p.content_html.replace(/<[^>]+>/g, '').trim().length > 120) {
          if (!window.confirm('Mevcut yazı içeriği yeni üretilecek Yapay Zeka makalesiyle değiştirilecek. Onaylıyor musunuz?')) {
            return;
          }
        }

        fullAiBtn.disabled = true;
        fullAiBtn.textContent = '⏳ Makale, H2 Başlıklar & SSS Üretiliyor...';
        try {
          const res = await api('ai_write_full_post', {
            topic: topicVal,
            tone: toneVal,
            length: lenVal,
            target_service: srvVal,
            category: p.category || 'GENEL',
            image: p.image || '/images/journal/web-planning.webp'
          });
          const comp = res.composed || {};
          p.title = comp.title || topicVal;
          if (!S.blog.oldSlug) {
            p.slug = comp.slug || slugifyClient(p.title);
          }
          if (comp.category) {
            p.category = comp.category;
            p.categories = [comp.category];
            delete p.category_ids;
            delete p.category_slugs;
          }
          p.excerpt = comp.excerpt || '';
          p.content_html = comp.content_html || '';
          p.faqs = Array.isArray(comp.faqs) ? comp.faqs : [];
          p.seo = Object.assign({}, p.seo || {}, comp.seo || {});
          if (res.seo_analysis) {
            S.blog.seoAnalysis = res.seo_analysis;
          }
          toast(res.message || 'Makale başarıyla üretildi!');
          renderBlogEditor();
        } catch (err) {
          toast(err.message, true);
          fullAiBtn.disabled = false;
          fullAiBtn.textContent = '🤖 Tek Tıkla Makale, İçindekiler & SSS Üret';
        }
      };
    }

    ROOT.querySelectorAll('[data-pe-i18n-tab]').forEach(tb => {
      tb.onclick = () => {
        syncPostStateFromDom();
        S.blog.i18nTab = tb.getAttribute('data-pe-i18n-tab');
        renderBlogEditor();
      };
    });

    document.getElementById('peTranslateNowBtn').onclick = async () => {
      syncPostStateFromDom();
      if (!p.title) {
        toast('Otomatik çeviri için önce Türkçe yazı başlığını girin.', true);
        return;
      }
      const btn = document.getElementById('peTranslateNowBtn');
      btn.disabled = true;
      btn.textContent = '⏳ EN · DE · RU Çevriliyor...';
      try {
        const res = await api('blog_translate', {
          slug: S.blog.oldSlug || '',
          post: p,
          draft_only: !S.blog.oldSlug
        });
        p.translations = res.translations || {};
        toast(res.message);
        renderBlogEditor();
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
        btn.textContent = '🌍 Şimdi EN · DE · RU Çevir';
      }
    };

    document.getElementById('peSaveBtn').onclick = async () => {
      syncPostStateFromDom();
      const saveBtn = document.getElementById('peSaveBtn');
      saveBtn.disabled = true;
      saveBtn.textContent = S.blog.autoTranslate
        ? 'Kaydediliyor & EN·DE·RU Çevriliyor...'
        : 'Kaydediliyor...';
      try {
        const res = await api('blog_save', {
          post: p,
          old_slug: S.blog.oldSlug,
          auto_translate: S.blog.autoTranslate
        });
        const wasNew = !S.blog.oldSlug;
        S.blog.editingPost = res.post;
        S.blog.oldSlug = res.post.slug;
        S.blog.seoAnalysis = res.seo_analysis;
        S.blogCats.loaded = false;
        if (S.i18nBatch.state === 'idle') S.i18nBatch.loaded = false;
        if (wasNew && S.summary) {
          S.summary.total_posts = (S.summary.total_posts || 0) + 1;
          updateSidebarBadges();
        }
        toast(res.message);
        renderBlogEditor();
      } catch (err) {
        toast(err.message, true);
        saveBtn.disabled = false;
        saveBtn.textContent = '✓ Yazıyı Kaydet ve Yayınla';
      }
    };
  }

  /* ---- MODULE 6A: LIVE 16-CRITERIA CONTENT SEO EVALUATOR (0ms IN BLOG EDITOR) ---- */
  function evaluateLivePostSeo16(draft) {
    const title = String(draft.title || '').trim();
    const metaTitle = String(draft.meta_title || '').trim() || title;
    const metaDesc = String(draft.meta_desc || draft.excerpt || '').trim();
    const slug = String(draft.slug || '').replace(/^\/+|\/+$/g, '').trim();
    const kw = String(draft.focus_keyword || '').trim();
    const html = String(draft.content_html || '');
    const hasCoverAlt = Boolean(draft.has_cover_alt);
    const faqCount = Number(draft.faq_count || 0);
    const currentId = String(draft.id || ('post:' + slug));

    const tmpDiv = document.createElement('div');
    tmpDiv.innerHTML = html;
    const plain = (tmpDiv.textContent || tmpDiv.innerText || '').replace(/\s+/g, ' ').trim();
    const words = plain ? plain.split(/\s+/).filter(Boolean) : [];
    const wordCount = words.length;

    const kwLower = trFoldLowerClient(kw);
    const kwSlug = kw ? slugifyClient(kw) : '';
    const titleLower = trFoldLowerClient(metaTitle);
    const descLower = trFoldLowerClient(metaDesc);
    const plainLower = trFoldLowerClient(plain);
    const first10Pct = plainLower.slice(0, Math.max(160, Math.ceil(plainLower.length * 0.12)));

    const headingNodes = Array.from(tmpDiv.querySelectorAll('h2, h3'));
    const headingCount = headingNodes.length;
    const headingsText = trFoldLowerClient(headingNodes.map(n => n.textContent || '').join(' '));

    let internalLinks = 0;
    let externalLinks = 0;
    const curHost = window.location.hostname || 'zeynart.com';
    tmpDiv.querySelectorAll('a[href]').forEach(a => {
      const h = String(a.getAttribute('href') || '').trim();
      if (!h || h.startsWith('#') || h.toLowerCase().startsWith('javascript:')) return;
      if (h.startsWith('/') || h.includes(curHost)) internalLinks++;
      else if (/^https?:\/\//i.test(h)) externalLinks++;
    });

    let imgWithAlt = hasCoverAlt ? 1 : 0;
    tmpDiv.querySelectorAll('img[alt]').forEach(im => {
      if (String(im.getAttribute('alt') || '').trim()) imgWithAlt++;
    });

    let kwOccurrences = 0;
    if (kwLower && plainLower) {
      let pos = 0;
      while ((pos = plainLower.indexOf(kwLower, pos)) !== -1) {
        kwOccurrences++;
        pos += kwLower.length;
      }
    }
    const kwWordsCount = kw ? Math.max(1, kw.split(/\s+/).filter(Boolean).length) : 1;
    const density = (wordCount > 0 && kwLower) ? Number((((kwOccurrences * kwWordsCount) / wordCount) * 100).toFixed(2)) : 0;

    // Keyword cannibalization check against core pages + cached audit items
    const conflicts = [];
    if (kwLower) {
      const seoPages = S.store?.seo?.pages || {};
      const pageMap = { anasayfa: 'Ana Sayfa', hakkimizda: 'Hakkımızda', hizmetler: 'Hizmetler', projeler: 'Projeler', iletisim: 'İletişim' };
      Object.keys(pageMap).forEach(pk => {
        const pkw = trFoldLowerClient(seoPages[pk]?.focus_keyword || seoPages[pk]?.keywords || '');
        if (pkw && pkw === kwLower && ('page:' + pk) !== currentId) {
          conflicts.push(pageMap[pk]);
        }
      });
      if (S.seoContentAudit && Array.isArray(S.seoContentAudit.items)) {
        S.seoContentAudit.items.forEach(it => {
          if (it.id !== currentId && trFoldLowerClient(it.focus_keyword || '') === kwLower) {
            conflicts.push(it.title || it.slug);
          }
        });
      }
    }

    const checks = [];
    const add = (group, label, pass, points, hint) => checks.push({ group, label, pass: Boolean(pass), points, hint });

    // Group 1: Temel SEO (42 puan)
    add('basic', 'Odak anahtar kelime tanımlı', Boolean(kwLower), 8, kw ? `Odak kelime: "${kw}"` : 'Hedeflediğiniz ana arama terimini girin.');
    add('basic', 'Odak kelime SEO başlığında yer alıyor', Boolean(kwLower && titleLower.includes(kwLower)), 10, 'Arama motoru tıklama oranı için başlıkta geçmelidir.');
    add('basic', 'Odak kelime Meta Açıklamada yer alıyor', Boolean(kwLower && descLower.includes(kwLower)), 8, 'Arama özetinde kalın vurgulanması için açıklamada geçmelidir.');
    add('basic', 'Odak kelime URL adresinde (slug) yer alıyor', Boolean(kwSlug && slug.toLowerCase().includes(kwSlug)), 8, 'Kısa ve anahtar kelime odaklı kalıcı bağlantı kullanın.');
    add('basic', 'Odak kelime ilk paragrafta (%10 giriş) geçiyor', Boolean(kwLower && first10Pct.includes(kwLower)), 8, 'Konu bütünlüğü sinyali için ilk paragrafta odak kelimeye yer verin.');

    // Group 2: İçerik Derinliği & Bağlantı Mimarisi (34 puan)
    add('content', 'Kapsamlı içerik uzunluğu (en az 350+ kelime)', wordCount >= 350, 8, `Mevcut: ${wordCount} kelime (Önerilen: 350–1.500+ kelime).`);
    add('content', 'H2 / H3 alt başlıklarında odak kelime veya alt kavramlar', headingCount >= 2 && (!kwLower || headingsText.includes(kwLower) || headingCount >= 3), 7, `Mevcut alt başlık sayısı: ${headingCount}.`);
    add('content', 'Anahtar kelime yoğunluğu doğal aralıkta (%0.4 – %2.8)', Boolean(kwLower && density >= 0.3 && density <= 3.0), 5, `Yoğunluk: %${density} (${kwOccurrences} kez geçti).`);
    add('content', 'Site içi hizmet/sayfa bağlantısı (Internal Link) mevcut', internalLinks >= 1, 5, internalLinks >= 1 ? `${internalLinks} iç bağlantı bulundu.` : 'En az 1 ilgili hizmet veya rehber sayfasına link verin.');
    add('content', 'Referans kaynak veya çoklu iç bağlantı derinliği mevcut', externalLinks >= 1 || internalLinks >= 2, 3, (externalLinks >= 1 || internalLinks >= 2) ? `${internalLinks} iç, ${externalLinks} dış referans bağlantı mevcut.` : 'En az 1 otorite referans veya 2. bir iç bağlantı ekleyin.');
    add('content', 'Görsel optimizasyonu ve dolu alt etiketi mevcut', imgWithAlt >= 1, 6, imgWithAlt >= 1 ? `${imgWithAlt} görselde açıklayıcı alt metni var.` : 'Kapak veya içerik görsellerine alt metin ekleyin.');

    // Group 3: SERP Görünümü, Şema & Tekillik (24 puan)
    const mtLen = metaTitle.length;
    const mdLen = metaDesc.length;
    add('serp', 'SEO Başlığı ideal uzunlukta (30 – 65 karakter)', mtLen >= 25 && mtLen <= 66, 6, `Mevcut: ${mtLen} karakter.`);
    add('serp', 'Meta Açıklama ideal uzunlukta (100 – 165 karakter)', mdLen >= 90 && mdLen <= 168, 6, `Mevcut: ${mdLen} karakter.`);
    add('serp', 'Kısa ve temiz URL yapısı (≤ 75 karakter)', slug.length > 0 && slug.length <= 75, 4, `URL uzunluğu: ${slug.length} karakter.`);
    add('serp', 'Zengin Sonuç (SSS / FAQPage Şeması) desteği', faqCount >= 1, 4, faqCount >= 1 ? `${faqCount} adet yapılandırılmış SSS maddesi hazır.` : 'Arama sonuçlarında geniş yer kaplamak için SSS ekleyin.');
    add('serp', 'Odak kelime tekilliği (Cannibalization kontrolü)', Boolean(kwLower && conflicts.length === 0), 4, conflicts.length === 0 ? 'Bu odak kelime başka bir içerikle çakışmıyor.' : `Çakışan içerik: ${conflicts.slice(0, 2).join(', ')}`);

    let score = 0;
    let passed = 0;
    checks.forEach(c => {
      if (c.pass) {
        score += c.points;
        passed++;
      }
    });

    return {
      score: Math.min(100, score),
      passed,
      total: checks.length,
      wordCount,
      headingCount,
      internalLinks,
      externalLinks,
      density,
      conflicts,
      checks
    };
  }

  function refreshLiveBlogSeo16(p) {
    const host = document.getElementById('peLiveSeo16Host');
    if (!host) return;
    const visualEl = document.getElementById('peVisualArea');
    const sourceEl = document.getElementById('peSourceArea');
    const titleVal = document.getElementById('peTitle')?.value.trim() || p?.title || '';
    const slugVal = document.getElementById('peSlug')?.value.trim() || slugifyClient(titleVal) || p?.slug || '';
    const htmlVal = S.blog.sourceMode ? (sourceEl?.value || '') : (visualEl?.innerHTML || p?.content_html || '');
    let faqCnt = 0;
    ROOT.querySelectorAll('[data-faq-idx]').forEach(r => {
      if ((r.querySelector('.pe-faq-q')?.value || '').trim()) faqCnt++;
    });

    const ev = evaluateLivePostSeo16({
      id: 'post:' + (S.blog.oldSlug || slugVal),
      title: titleVal,
      slug: slugVal,
      meta_title: document.getElementById('peMetaTitle')?.value.trim() || '',
      meta_desc: document.getElementById('peMetaDesc')?.value.trim() || '',
      excerpt: document.getElementById('peExcerpt')?.value.trim() || '',
      focus_keyword: document.getElementById('peFocusKw')?.value.trim() || '',
      content_html: htmlVal,
      has_cover_alt: Boolean((document.getElementById('peImageAlt')?.value || '').trim()),
      faq_count: faqCnt
    });

    const gaugeBox = document.getElementById('peSeoGaugeBox');
    if (gaugeBox) {
      gaugeBox.innerHTML = `
        <div class="zs-seo-gauge-wrap">
          ${renderSeoRing(ev.score)}
          <div>
            <strong style="display:block;font-size:14px">16 Kriterli İçerik SEO Skoru (${ev.passed}/${ev.total})</strong>
            <span style="font-size:12px;color:var(--text-muted)">${ev.wordCount} kelime · ${ev.headingCount} alt başlık · %${ev.density} yoğunluk</span>
          </div>
        </div>
      `;
    }

    const groups = [
      { key: 'basic', title: '1. Temel Odak Kelime Uyumluluğu' },
      { key: 'content', title: '2. İçerik Derinliği & Bağlantı Mimarisi' },
      { key: 'serp', title: '3. SERP Görünümü, Şema & Tekillik' }
    ];

    host.innerHTML = `
      ${ev.conflicts.length > 0 ? `
        <div class="zs-seo16-conflict-banner">
          ⚠️ <strong>Odak Kelime Çakışması:</strong> Bu anahtar kelime <em>${esc(ev.conflicts.slice(0, 2).join(', '))}</em> içeriğinde de hedeflenmiş. Farklı bir uzun kuyruklu (long-tail) kelime seçmeniz önerilir.
        </div>
      ` : ''}
      <div class="zs-seo16-groups">
        ${groups.map(g => {
          const gChecks = ev.checks.filter(c => c.group === g.key);
          const gPassed = gChecks.filter(c => c.pass).length;
          return `
            <div class="zs-seo16-group">
              <div class="zs-seo16-group-head">
                <strong>${esc(g.title)}</strong>
                <span class="zs-pill ${gPassed === gChecks.length ? 'zs-pill-pub' : 'zs-pill-cat'}">${gPassed}/${gChecks.length}</span>
              </div>
              <ul class="zs-seo16-list">
                ${gChecks.map(c => `
                  <li class="${c.pass ? 'is-pass' : 'is-warn'}">
                    <span class="zs-check-dot ${c.pass ? 'ok' : 'warn'}">${c.pass ? '✓' : '!'}</span>
                    <div>
                      <div class="zs-seo16-lbl">${esc(c.label)} <span class="zs-seo16-pts">+${c.points}p</span></div>
                      <div class="zs-seo16-hint">${esc(c.hint)}</div>
                    </div>
                  </li>
                `).join('')}
              </ul>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  /* ---- MODULE 3A: E-E-A-T EXPERT AUTHOR PROFILES STUDIO (/admin/?view=blog&tab=authors) ---- */
  function renderBlogAuthorsView() {
    syncAdminUrl('replace');
    const authors = Array.isArray(S.store?.authors) && S.store.authors.length
      ? S.store.authors
      : [{
          id: 'editorial-team',
          slug: 'zeynart-editoryal-ekip',
          name: 'Zeynart Dijital Strateji & Yazılım Ekibi',
          role: 'Kıdemli Web Mimarisi, Yazılım & SEO Uzmanları',
          bio: '12+ yıllık kurumsal web tasarım, özel yazılım mimarisi ve teknik SEO deneyimiyle sürdürülebilir dijital çözümler üretir.',
          avatar: S.store?.site?.favicon || '/icon.png',
          linkedin: '',
          twitter: '',
          website: '',
          expertise: ['Kurumsal Web Tasarım', 'Özel Web Yazılım', 'Teknik SEO'],
          is_default: true
        }];
    S.store.authors = authors;
    if (typeof S.activeAuthorIdx !== 'number' || S.activeAuthorIdx < 0 || S.activeAuthorIdx >= authors.length) {
      S.activeAuthorIdx = 0;
    }
    const cur = authors[S.activeAuthorIdx];

    ROOT.innerHTML = `
      ${renderBlogModeTabBarHtml()}
      <div class="zs-grid-2" style="align-items:start">
        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:14px">
            <div>
              <span class="zs-badge-pill">GOOGLE E-E-A-T &amp; PERSON JSON-LD ŞEMASI</span>
              <h3 style="margin-top:6px">✍️ Uzman Yazar Profilleri (${authors.length})</h3>
              <p class="zs-card-sub">Makale altındaki doğrulanmış uzman yazar kutusunu ve Google <code>Person</code> uzmanlık şemasını yönetin.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="auAddNewBtn">+ Yeni Uzman Ekle</button>
          </div>

          <div style="display:grid;gap:10px">
            ${authors.map((a, idx) => `
              <div class="zs-rev-item" style="border-color:${idx === S.activeAuthorIdx ? 'rgba(94,234,212,.55)' : 'var(--border-subtle)'};background:${idx === S.activeAuthorIdx ? 'rgba(20,184,166,.11)' : 'rgba(6,14,19,.75)'}">
                <div style="display:flex;align-items:center;gap:12px;min-width:0">
                  <img src="${esc(resolvePreviewUrl(a.avatar || '/icon.png'))}" alt="${esc(a.name)}" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:1px solid rgba(94,234,212,.35);flex-shrink:0"/>
                  <div style="min-width:0">
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                      <strong style="color:#fff;font-size:14px">${esc(a.name)}</strong>
                      ${a.is_default ? `<span class="zs-sec-badge ok">VARSAYILAN</span>` : ''}
                    </div>
                    <div style="font-size:12px;color:#5eead4;margin-top:2px">${esc(a.role || '')}</div>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:5px">
                      ${(a.expertise || []).slice(0, 4).map(ex => `<span class="zs-pill zs-pill-cat" style="font-size:10.5px">${esc(ex)}</span>`).join('')}
                    </div>
                  </div>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0">
                  <button type="button" class="zs-btn zs-btn-sm ${idx === S.activeAuthorIdx ? 'zs-btn-ai' : ''}" data-au-sel="${idx}">Düzenle</button>
                  ${authors.length > 1 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-au-del="${idx}" title="Sil">✕</button>` : ''}
                </div>
              </div>
            `).join('')}
          </div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:14px">
            <h3 style="margin:0">✏️ Yazar Profilini Düzenle: ${esc(cur.name)}</h3>
            <button type="button" class="zs-btn zs-btn-primary" id="auSaveAllBtn">✓ Yazar Profillerini Kaydet</button>
          </div>

          <div class="zs-grid-2">
            <div class="zs-field"><label>Ad Soyad / Ekip Adı *</label><input id="auName" class="zs-input" value="${esc(cur.name || '')}"/></div>
            <div class="zs-field"><label>Uzmanlık Ünvanı (Role)</label><input id="auRole" class="zs-input" value="${esc(cur.role || '')}"/></div>
          </div>

          <div class="zs-field">
            <label>Profil Fotoğrafı / Avatar (/uploads/...)</label>
            <div class="zs-input-group">
              <input id="auAvatar" class="zs-input" value="${esc(cur.avatar || '')}"/>
              <button type="button" class="zs-btn zs-btn-sm" id="auPickAvatarBtn">Kütüphaneden Seç</button>
            </div>
          </div>

          <div class="zs-field">
            <label>Kısa Uzman Biyografisi (Makale Altı Yazar Kutusu &amp; E-E-A-T)</label>
            <textarea id="auBio" class="zs-textarea" style="min-height:84px">${esc(cur.bio || '')}</textarea>
          </div>

          <div class="zs-field">
            <label>Uzmanlık Alanları — Schema.org <code>knowsAbout</code> (Virgülle ayırın)</label>
            <input id="auExpertise" class="zs-input" placeholder="Kurumsal Web Tasarım, Teknik SEO, Bulut Sunucu" value="${esc((cur.expertise || []).join(', '))}"/>
          </div>

          <div class="zs-grid-3">
            <div class="zs-field"><label>LinkedIn Profil URL</label><input id="auLinkedin" class="zs-input" placeholder="https://linkedin.com/in/..." value="${esc(cur.linkedin || '')}"/></div>
            <div class="zs-field"><label>X (Twitter) URL</label><input id="auTwitter" class="zs-input" placeholder="https://x.com/..." value="${esc(cur.twitter || '')}"/></div>
            <div class="zs-field"><label>Kişisel / Kurumsal Web</label><input id="auWebsite" class="zs-input" placeholder="https://..." value="${esc(cur.website || '')}"/></div>
          </div>

          <label class="zs-switch" style="margin-top:6px">
            <input type="checkbox" id="auDefault" ${cur.is_default ? 'checked' : ''}/>
            <span><strong>Yeni blog yazılarında varsayılan uzman yazar olarak ata</strong></span>
          </label>
        </div>
      </div>
    `;

    bindBlogModeTabBarEvents();

    const syncCurAuthor = () => {
      const a = authors[S.activeAuthorIdx];
      if (!a) return;
      a.name = document.getElementById('auName')?.value.trim() || 'Uzman Yazar';
      a.slug = slugifyClient(a.name) || a.id;
      a.role = document.getElementById('auRole')?.value.trim() || '';
      a.avatar = document.getElementById('auAvatar')?.value.trim() || '/icon.png';
      a.bio = document.getElementById('auBio')?.value.trim() || '';
      a.expertise = (document.getElementById('auExpertise')?.value || '').split(',').map(s => s.trim()).filter(Boolean);
      a.linkedin = document.getElementById('auLinkedin')?.value.trim() || '';
      a.twitter = document.getElementById('auTwitter')?.value.trim() || '';
      a.website = document.getElementById('auWebsite')?.value.trim() || '';
      if (document.getElementById('auDefault')?.checked) {
        authors.forEach((x, idx) => { x.is_default = (idx === S.activeAuthorIdx); });
      }
    };

    ROOT.querySelectorAll('[data-au-sel]').forEach(btn => {
      btn.onclick = () => {
        syncCurAuthor();
        S.activeAuthorIdx = Number(btn.getAttribute('data-au-sel') || 0);
        renderBlogAuthorsView();
      };
    });

    ROOT.querySelectorAll('[data-au-del]').forEach(btn => {
      btn.onclick = () => {
        syncCurAuthor();
        const idx = Number(btn.getAttribute('data-au-del'));
        if (authors.length <= 1) return;
        authors.splice(idx, 1);
        S.activeAuthorIdx = Math.max(0, S.activeAuthorIdx - 1);
        renderBlogAuthorsView();
      };
    });

    document.getElementById('auAddNewBtn')?.addEventListener('click', () => {
      syncCurAuthor();
      authors.push({
        id: uid('author'),
        slug: 'yeni-uzman-yazar',
        name: 'Yeni Uzman Yazar',
        role: 'Kıdemli Dijital Strateji Uzmanı',
        bio: 'Kurumsal dijital dönüşüm, performans odaklı web mimarisi ve arama motoru görünürlüğü alanında uzman.',
        avatar: S.store?.site?.favicon || '/icon.png',
        linkedin: '',
        twitter: '',
        website: '',
        expertise: ['Web Mimarisi', 'SEO Stratejisi'],
        is_default: false
      });
      S.activeAuthorIdx = authors.length - 1;
      renderBlogAuthorsView();
    });

    document.getElementById('auPickAvatarBtn')?.addEventListener('click', () => {
      openMediaPicker(url => {
        document.getElementById('auAvatar').value = url;
      });
    });

    document.getElementById('auSaveAllBtn')?.addEventListener('click', async function () {
      syncCurAuthor();
      this.disabled = true;
      try {
        const res = await api('authors_save', { authors });
        if (res.authors) S.store.authors = res.authors;
        toast(res.message);
        renderBlogAuthorsView();
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });
  }

  /* ---- MODULE 3B: MODERATED BLOG Q&A / COMMENTS STUDIO (/admin/?view=blog&tab=comments) ---- */
  function renderBlogCommentsView() {
    syncAdminUrl('replace');
    const cfg = Object.assign({
      enabled: true,
      show_author_box: true,
      require_approval: true,
      heading: 'Uzmanımıza Sorun & Yorumlar',
      sublead: 'Bu makaleyle ilgili merak ettiğiniz teknik detayları veya proje sorularınızı paylaşabilirsiniz. Yorumlar editör onayından sonra yayınlanır.'
    }, S.store?.blog_comments_cfg || {});
    const all = Array.isArray(S.blogComments) ? S.blogComments : [];
    const flt = S.blogCommentsFilter || 'all';
    const pendingCnt = all.filter(c => (c.status || 'pending') === 'pending').length;
    const approvedCnt = all.filter(c => c.status === 'approved').length;
    const rows = all.filter(c => {
      if (flt === 'pending') return (c.status || 'pending') === 'pending';
      if (flt === 'approved') return c.status === 'approved';
      return true;
    });

    ROOT.innerHTML = `
      ${renderBlogModeTabBarHtml()}
      <div class="zs-card" style="margin-bottom:16px">
        <div class="zs-card-head" style="margin-bottom:12px">
          <div>
            <span class="zs-badge-pill">ÇEREZSİZ OKUYUCU ETKİLEŞİMİ &amp; E-E-A-T KUTUSU</span>
            <h3 style="margin-top:6px">⚙️ Makale Altı Soru-Cevap &amp; Yazar Kutusu Ayarları</h3>
          </div>
          <button type="button" class="zs-btn zs-btn-primary" id="bcSaveCfgBtn">✓ Ayarları Kaydet</button>
        </div>
        <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:14px">
          <label class="zs-switch" style="margin:0"><input type="checkbox" id="bcEnabled" ${cfg.enabled !== false ? 'checked' : ''}/><span><strong>Blog yazılarında Soru-Cevap &amp; Yorum modülünü göster</strong></span></label>
          <label class="zs-switch" style="margin:0"><input type="checkbox" id="bcShowAuthor" ${cfg.show_author_box !== false ? 'checked' : ''}/><span><strong>Doğrulanmış Uzman Yazar Kutusunu göster</strong></span></label>
          <label class="zs-switch" style="margin:0"><input type="checkbox" id="bcReqApproval" ${cfg.require_approval !== false ? 'checked' : ''}/><span><strong>Yorumları editör onayından sonra yayınla (Spam Korumalı)</strong></span></label>
        </div>
        <div class="zs-grid-2">
          <div class="zs-field" style="margin-bottom:0"><label>Bölüm Başlığı</label><input id="bcHeading" class="zs-input" value="${esc(cfg.heading || '')}"/></div>
          <div class="zs-field" style="margin-bottom:0"><label>Alt Bilgilendirme Metni</label><input id="bcSublead" class="zs-input" value="${esc(cfg.sublead || '')}"/></div>
        </div>
      </div>

      <div class="zs-card" style="margin-bottom:0">
        <div class="zs-card-head" style="margin-bottom:14px">
          <div>
            <h3 style="margin:0">💬 Gelen Okuyucu Soruları &amp; Yorum Moderasyonu (${all.length})</h3>
            <p class="zs-card-sub">Onayladığınız sorular ve yazdığınız kurumsal yanıtlar makale altında <strong>“Zeynart Uzman Yanıtı ✓”</strong> rozetiyle yayınlanır.</p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-sm ${flt === 'all' ? 'zs-btn-ai' : ''}" data-bc-flt="all">Tümü (${all.length})</button>
            <button type="button" class="zs-btn zs-btn-sm ${flt === 'pending' ? 'zs-btn-ai' : ''}" data-bc-flt="pending">⏳ Onay Bekleyen (${pendingCnt})</button>
            <button type="button" class="zs-btn zs-btn-sm ${flt === 'approved' ? 'zs-btn-ai' : ''}" data-bc-flt="approved">✓ Yayında (${approvedCnt})</button>
            <button type="button" class="zs-btn zs-btn-sm" id="bcRefreshBtn">↻ Yenile</button>
          </div>
        </div>

        ${rows.length === 0 ? `
          <div class="zs-glass-empty-note">Bu filtrede henüz okuyucu sorusu veya yorumu bulunmuyor.</div>
        ` : `
          <div style="display:grid;gap:12px">
            ${rows.map(c => {
              const isApp = c.status === 'approved';
              return `
                <div class="zs-comment-mod-card" data-cm-id="${esc(c.id)}">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:8px">
                    <div>
                      <strong style="color:#fff;font-size:14px">${esc(c.name || 'Ziyaretçi')}</strong>
                      ${c.email ? `<span style="font-size:12px;color:var(--text-muted);margin-left:6px">&lt;${esc(c.email)}&gt;</span>` : ''}
                      <span style="color:#fbbf24;margin-left:8px">${'★'.repeat(Math.max(1, Math.min(5, Number(c.rating || 5))))}</span>
                      <div style="font-size:12px;color:var(--text-muted);margin-top:3px">
                        📰 Makale: <a href="${esc(resolveUrl('/blog/' + (c.post_slug || '') + '/'))}" target="_blank" rel="noopener" style="color:#5eead4;text-decoration:none">${esc(c.post_title || c.post_slug)} ↗</a>
                        · 🕒 ${esc(c.created_at || '')}
                      </div>
                    </div>
                    <span class="zs-sec-badge ${isApp ? 'ok' : 'warn'}">${isApp ? '✓ YAYINDA' : '⏳ ONAY BEKLİYOR'}</span>
                  </div>

                  <div style="padding:10px 12px;border-radius:10px;background:rgba(4,10,14,.75);border:1px solid var(--border-subtle);color:#e2e8f0;font-size:13px;line-height:1.6;margin-bottom:10px">
                    ${esc(c.comment || c.body || '')}
                  </div>

                  <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
                    <textarea class="zs-textarea cm-reply-inp" style="flex:1;min-width:240px;min-height:56px" placeholder="İsteğe bağlı resmi uzman yanıtınız (yazıp onayladığınızda makale altında Zeynart Uzman Yanıtı ✓ olarak görünür)...">${esc(c.admin_reply || '')}</textarea>
                    <div style="display:flex;flex-direction:column;gap:6px">
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" data-cm-op="approve">✓ ${isApp ? 'Yanıtı Güncelle' : 'Onayla &amp; Yayınla'}</button>
                      ${isApp ? `<button type="button" class="zs-btn zs-btn-sm" data-cm-op="unapprove">⏸ Gizle (Beklemeye Al)</button>` : ''}
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-cm-op="delete">🗑 Sil</button>
                    </div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        `}
      </div>
    `;

    bindBlogModeTabBarEvents();

    document.getElementById('bcSaveCfgBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      try {
        const nextCfg = {
          enabled: Boolean(document.getElementById('bcEnabled')?.checked),
          show_author_box: Boolean(document.getElementById('bcShowAuthor')?.checked),
          require_approval: Boolean(document.getElementById('bcReqApproval')?.checked),
          heading: document.getElementById('bcHeading')?.value.trim() || '',
          sublead: document.getElementById('bcSublead')?.value.trim() || ''
        };
        const res = await api('blog_comments_save_cfg', { blog_comments_cfg: nextCfg });
        S.store.blog_comments_cfg = res.blog_comments_cfg || nextCfg;
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      } finally {
        this.disabled = false;
      }
    });

    ROOT.querySelectorAll('[data-bc-flt]').forEach(btn => {
      btn.onclick = () => {
        S.blogCommentsFilter = btn.getAttribute('data-bc-flt') || 'all';
        renderBlogCommentsView();
      };
    });

    document.getElementById('bcRefreshBtn')?.addEventListener('click', async () => {
      try {
        const res = await api('blog_comments_list', {});
        S.blogComments = res.blog_comments || [];
        toast('Yorum listesi güncellendi.');
        renderBlogCommentsView();
      } catch (err) {
        toast(err.message, true);
      }
    });

    ROOT.querySelectorAll('[data-cm-op]').forEach(btn => {
      btn.onclick = async () => {
        const card = btn.closest('[data-cm-id]');
        const id = card?.getAttribute('data-cm-id');
        const op = btn.getAttribute('data-cm-op');
        if (!id || !op) return;
        if (op === 'delete' && !window.confirm('Bu yorum kalıcı olarak silinsin mi?')) return;
        const admin_reply = card.querySelector('.cm-reply-inp')?.value.trim() || '';
        btn.disabled = true;
        try {
          const res = await api('blog_comment_moderate', { id, op, admin_reply });
          S.blogComments = res.blog_comments || [];
          if (res.summary) S.summary = res.summary;
          toast(res.message);
          renderBlogCommentsView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      };
    });
  }


  return {
    startNewBlogPost,
    openBlogPostForEdit,
    loadI18nTranslateQueue,
    refreshI18nStudioMount,
    renderBlogCategoriesView,
    renderBlogView
  };
  });
})();
