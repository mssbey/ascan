/* ZEYNART DİJİTAL STÜDYO — İstemci Uygulaması */
(function () {
  'use strict';

  const CFG = window.ZEYNART_ADMIN_CONFIG || {};
  const ROOT = document.getElementById('zsAppRoot');
  if (!ROOT) return;

  function getAdminTimeGreeting(hourOverride) {
    const h = typeof hourOverride === 'number' ? hourOverride : new Date().getHours();
    if (h >= 6 && h <= 11) return 'Günaydın! Sitenizdeki Her Detay Kontrolünüzde.';
    if (h >= 12 && h <= 16) return 'Tünaydın! Sitenizdeki Her Detay Kontrolünüzde.';
    if (h >= 17 && h <= 23) return 'İyi Akşamlar! Sitenizdeki Her Detay Kontrolünüzde.';
    return 'İyi Geceler! Sitenizdeki Her Detay Kontrolünüzde.';
  }
  window.__getZeynartAdminGreeting = getAdminTimeGreeting;

  const TR_MONTHS_FULL = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
  const TR_DAYS_FULL = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
  const TR_DAYS_SHORT = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];

  function formatTurkishClockParts(dateObj) {
    const d = dateObj instanceof Date ? dateObj : new Date();
    const h = d.getHours();
    const m = d.getMinutes();
    const s = d.getSeconds();
    const pad2 = n => (n < 10 ? '0' + n : String(n));
    const hm = `${pad2(h)}:${pad2(m)}`;
    const sec = `:${pad2(s)}`;
    const fullTime = `${hm}${sec}`;
    const dayNum = d.getDate();
    const monthName = TR_MONTHS_FULL[d.getMonth()] || '';
    const yearNum = d.getFullYear();
    const dayFull = TR_DAYS_FULL[d.getDay()] || '';
    const dayShort = TR_DAYS_SHORT[d.getDay()] || '';

    const startOfYear = new Date(yearNum, 0, 1);
    const weekNum = Math.max(1, Math.ceil((((d - startOfYear) / 86400000) + startOfYear.getDay() + 1) / 7));

    let periodBadge = '🌙 İYİ GECELER';
    if (h >= 6 && h <= 11) periodBadge = '☀️ GÜNAYDIN';
    else if (h >= 12 && h <= 16) periodBadge = '🌤️ TÜNAYDIN';
    else if (h >= 17 && h <= 23) periodBadge = '🌙 İYİ AKŞAMLAR';

    return {
      hm,
      sec,
      fullTime,
      shortDate: `${dayNum} ${monthName} ${yearNum} ${dayShort}`,
      fullDate: `${dayNum} ${monthName} ${yearNum}, ${dayFull}`,
      weekLabel: `${weekNum}. Hafta`,
      periodBadge
    };
  }

  function tickAdminClocks() {
    const parts = formatTurkishClockParts(new Date());
    const topTime = document.getElementById('zsTopbarTime');
    const topDate = document.getElementById('zsTopbarDate');
    if (topTime && topTime.textContent !== parts.fullTime) topTime.textContent = parts.fullTime;
    if (topDate && topDate.textContent !== parts.shortDate) topDate.textContent = parts.shortDate;

    const heroHm = document.getElementById('zsDashHeroClockHM');
    const heroSec = document.getElementById('zsDashHeroClockSec');
    const heroDate = document.getElementById('zsDashHeroDateFull');
    const heroWeek = document.getElementById('zsDashHeroWeek');
    if (heroHm && heroHm.textContent !== parts.hm) heroHm.textContent = parts.hm;
    if (heroSec && heroSec.textContent !== parts.sec) heroSec.textContent = parts.sec;
    if (heroDate && heroDate.textContent !== parts.fullDate) heroDate.textContent = parts.fullDate;
    if (heroWeek && heroWeek.textContent !== parts.weekLabel) heroWeek.textContent = parts.weekLabel;

    if (!S.view || S.view === 'dashboard') {
      const g = getAdminTimeGreeting();
      const hEl = document.getElementById('zsDashGreetingHeading');
      if (hEl && hEl.textContent !== g) hEl.textContent = g;
    }
  }

  const VIEW_TITLES = {
    dashboard: 'Gösterge Paneli',
    analytics: 'Ziyaretçi İstatistikleri & Anlık Canlı Trafik',
    home: 'Ana Sayfa — Hero, Vitrin Metinleri & Görsel Tasarımcı',
    messages: 'Proje Talepleri (CRM Kutusu)',
    home_layout: 'Ana Sayfa Bölüm Sıralaması & Görünürlük Stüdyosu',
    pages: 'Sayfalar — Tüm Site Sayfaları',
    contact: 'İletişim Sayfası — Kanallar, Form & Harita Yönetimi',
    menus: 'Menüler & Tipografi Stüdyosu',
    blog: 'Blog Yazıları (Zeynart Journal)',
    blog_categories: 'Blog Kategorileri — Hiyerarşi, Sıralama & SEO Yönetimi',
    media: 'Medya Kütüphanesi & Görsel Yükleyici',
    appearance: 'White-Label Tema, Duyuru Çubuğu & Bakım Modu',
    google_reviews: 'Google & SEO Merkezi — Otomatik Google Yorumları & Puan Stüdyosu',
    growth_tools: 'Büyüme, Teklif Sihirbazı & Akıllı Pop-up Stüdyosu',
    vcards: 'QR Dijital Kartvizit, Rehbere Ekle (.vcf) & IBAN Stüdyosu',
    proposals: 'Özel Müşteri Teklifleri — Online Teklif Linki & Okundu/Onay Takibi',
    client_portal: 'Müşteri Proje Durum & Aşama Onay Portalı (/proje-takip/)',
    uptime_tower: 'Ajans Uptime & SSL Kontrol Kulesi (300+ Müşteri Sitesi)',
    renewals: 'Hosting, Domain, SSL & Yıllık Bakım Yenileme Radarı',
    performance: 'LiteSpeed LSCache, Sistem Sağlığı & Kullanılmayan Dosyalar',
    ota_hub: 'Merkezi OTA Güncelleme Hub & Çoklu Sektör Filo Komuta Merkezi (v5.2)',
    seo: 'Google & SEO Merkezi (GA4 · Search Console · Yorumlar · Harita · Fonts · GEO)',
    social_media: 'Sosyal Medya Merkezi — Hesaplar, İkonlar, Paylaşım & IG/Story Stüdyosu',
    settings: 'Sistem, Kullanıcı Rolleri & Güvenlik'
  };

  /* ======================== v5.2 ÇOKLU SEKTÖR MODÜL & GÖRÜNÜM KANCASI (EXTENSION HOOK REGISTRY) ======================== */
  const SECTOR_VIEWS = (window.STUDIO_EXT_VIEWS = window.STUDIO_EXT_VIEWS || {});
  const SECTOR_COMMANDS = [];
  const SECTOR_BOOT_HOOKS = [];
  const SECTOR_DASHBOARD_HOOKS = [];

  function registerSectorViewClient(viewKey, spec) {
    const k = String(viewKey || '').trim();
    if (!k || !spec) return;
    const normalized = typeof spec === 'function' ? { render: spec } : Object.assign({}, spec);
    SECTOR_VIEWS[k] = normalized;
    if (normalized.title) {
      VIEW_TITLES[k] = String(normalized.title);
    }
    if (normalized.defaultTab) {
      DEFAULT_VIEW_TABS[k] = String(normalized.defaultTab);
      if (S.tabs && S.tabs[k] === undefined) {
        S.tabs[k] = String(normalized.defaultTab);
      }
    }
    if (Array.isArray(normalized.commands)) {
      normalized.commands.forEach(cmd => {
        if (cmd && cmd.label && typeof cmd.run === 'function') {
          SECTOR_COMMANDS.push(cmd);
        }
      });
    }
    if (typeof normalized.onBoot === 'function') {
      SECTOR_BOOT_HOOKS.push(normalized.onBoot);
    }
    if (typeof normalized.enhanceDashboard === 'function') {
      SECTOR_DASHBOARD_HOOKS.push(normalized.enhanceDashboard);
    }
  }
  window.ZEYNART_REGISTER_SECTOR_VIEW = registerSectorViewClient;
  window.ZEYNART_REGISTER_SECTOR_MODULE = registerSectorViewClient;

  function isSuperAdmin() {
    const r = S.currentUser?.role || S.store?.current_user?.role || CFG.userRole || 'super_admin';
    return r === 'super_admin';
  }

  function syncUserRoleUi() {
    const prof = S.currentUser || S.store?.current_user || null;
    const superMode = isSuperAdmin();
    if (prof) {
      const dispEl = document.getElementById('zsAdminDisplayName');
      const metaEl = document.getElementById('zsAdminUserMeta');
      const avEl = document.getElementById('zsAdminAvatarChar');
      if (dispEl && prof.display_name) dispEl.textContent = prof.display_name;
      if (metaEl && prof.username) metaEl.textContent = `@${prof.username} · ${superMode ? 'Süper Admin' : 'Editör'}`;
      if (avEl && prof.display_name) avEl.textContent = String(prof.display_name).trim().charAt(0).toLocaleUpperCase('tr-TR') || 'A';
    }
    const appNavBtn = document.querySelector('#zsNav .zs-nav-btn[data-view="appearance"]');
    if (appNavBtn) appNavBtn.style.display = superMode ? '' : 'none';
    document.querySelectorAll('#zsNav [data-super-only="1"]').forEach(el => {
      if (superMode) {
        el.removeAttribute('data-role-hidden');
        el.style.display = '';
      } else {
        el.setAttribute('data-role-hidden', '1');
        el.style.display = 'none';
      }
    });
    const setChev = document.getElementById('zsNavSettingsChev');
    if (setChev) setChev.style.display = superMode ? '' : 'none';
    const setSub = document.getElementById('zsNavSettingsSubList');
    if (setSub) setSub.style.display = superMode ? '' : 'none';
    const purgeBtn = document.getElementById('zsTopbarPurgeBtn');
    if (purgeBtn) purgeBtn.style.display = superMode ? '' : 'none';
    const setLbl = document.getElementById('zsNavSettingsLabel');
    if (setLbl) setLbl.textContent = superMode ? 'Sistem, Hız & Güvenlik' : 'Hesabım & Parola';
    if (typeof applySidebarNavFilter === 'function') applySidebarNavFilter();
  }

  function resolveViewTitle(view) {
    if (!view || view === 'dashboard') {
      return 'Gösterge Paneli';
    }
    if (view === 'settings' && !isSuperAdmin()) {
      return 'Hesabım & Parola Yönetimi (Müşteri / Editör)';
    }
    if (SECTOR_VIEWS[view] && SECTOR_VIEWS[view].title) {
      return SECTOR_VIEWS[view].title;
    }
    if (VIEW_TITLES[view]) {
      return VIEW_TITLES[view];
    }
    const brandLabel = S.store?.site?.name || document.getElementById('zsBrandSideTitle')?.textContent?.trim() || 'Dijital Stüdyo';
    return `${brandLabel} — Yönetim Merkezi`;
  }

  const HOME_SECTION_CATALOG = [
    { key: 'hero', title: 'Tam Ekran Hero & Tanıtım Slaytları', sub: 'Giriş arka plan görselleri, slogan slaytları ve ana aksiyon butonları', badge: 'Üst Vitrin', icon: '🎬', accent: '#2dd4bf', defaultVisible: true },
    { key: 'intro', title: '2014’ten Bugüne Stüdyo Manifestosu', sub: 'Kuruluş yılı vurgusu, tasarım & teknoloji ilkeleri ve kurumsal giriş', badge: 'Kurumsal', icon: '🏛️', accent: '#38bdf8', defaultVisible: true },
    { key: 'services', title: 'Hizmetler Vitrin Izgarası (#hizmetler)', sub: 'Uzmanlık alanlarını 3B yörüngeli kartlarla sergileyen ana hizmetler ızgarası', badge: 'Hizmetler', icon: '⚡', accent: '#818cf8', defaultVisible: true },
    { key: 'brand_scene', title: '“Biz Zeynart” Sinematik Sahne Paneli', sub: 'Tam genişlikli görsel sahne, 3 temel sütun ve marka felsefesi', badge: 'Marka Sahnesi', icon: '✨', accent: '#c084fc', defaultVisible: true },
    { key: 'process', title: 'Nasıl Çalışıyoruz? (4 Adımlı Süreç)', sub: 'Keşiften yayına 4 adımlı şeffaf proje yönetim akışı', badge: 'Süreç', icon: '🧭', accent: '#fbbf24', defaultVisible: true },
    { key: 'projects_showcase', title: 'Seçilmiş Projeler & Vaka Çalışmaları', sub: 'Projeler sayfasındaki seçili çalışmaları ana sayfada vitrin olarak gösterir', badge: 'Ek Vitrin', icon: '🚀', accent: '#f472b6', defaultVisible: false },
    { key: 'clients_strip', title: 'Birlikte Çalıştığımız Markalar (Referanslar)', sub: '62+ kurumsal müşteri ve iş ortağı logosunu ızgara halinde sergiler', badge: 'Sosyal Kanıt', icon: '🤝', accent: '#22d3ee', defaultVisible: false },
    { key: 'google_reviews', title: 'Google Yorumları & 5.0★ Müşteri Puanı', sub: 'Doğrulanmış gerçek Google Haritalar yorum karuseli ve puan rozeti', badge: 'Sosyal Kanıt', icon: '⭐', accent: '#facc15', defaultVisible: true },
    { key: 'site_audit', title: 'Ücretsiz Site Hızı & SEO Ön Analiz Aracı', sub: 'Ziyaretçinin sitesini canlı analiz edip iletişim bilgisi toplayan lead aracı', badge: 'Lead Aracı', icon: '🔍', accent: '#34d399', defaultVisible: true },
    { key: 'quote_wizard', title: 'İnteraktif Teklif & Maliyet Sihirbazı', sub: '4 adımda hizmet kapsamı ve tahmini bütçe seçtiren interaktif teklif aracı', badge: 'Dönüşüm', icon: '🧮', accent: '#14b8a6', defaultVisible: false },
    { key: 'latest_blog', title: 'Blog & Dijital İçgörüler (Son 3 Yazı)', sub: 'Yayınlanan son 3 blog makalesini otomatik olarak ana sayfada listeler', badge: 'İçerik & SEO', icon: '📰', accent: '#fb923c', defaultVisible: false },
    { key: 'builder_blocks', title: 'Görsel Sürükle-Bırak Özel Bölümler', sub: 'Görsel Tasarımcı ile ana sayfaya eklediğiniz serbest özel bloklar', badge: 'Özel Blok', icon: '🧩', accent: '#a78bfa', defaultVisible: true },
    { key: 'cta', title: 'Alt Teklif Çağrı Bandı (Global CTA)', sub: '“Birlikte hayata geçirelim” kapanış aksiyon ve iletişim çağrısı', badge: 'Dönüşüm', icon: '📣', accent: '#14b8a6', defaultVisible: true }
  ];

  const HOME_LAYOUT_PRESETS = [
    {
      id: 'classic',
      name: '🏛️ Kurumsal Orijinal Akış',
      desc: 'Hero → 2014 Giriş → Hizmetler → Biz Zeynart → Süreç → Google Yorumları → Site Analiz → CTA',
      order: ['hero', 'intro', 'services', 'brand_scene', 'process', 'projects_showcase', 'clients_strip', 'google_reviews', 'site_audit', 'latest_blog', 'builder_blocks', 'cta'],
      vis: { hero: true, intro: true, services: true, brand_scene: true, process: true, projects_showcase: false, clients_strip: false, google_reviews: true, site_audit: true, latest_blog: false, builder_blocks: true, cta: true }
    },
    {
      id: 'lead_magnet',
      name: '🚀 Lead & Dönüşüm Odaklı',
      desc: 'Site Hızı & SEO Analiz Aracını hemen Hero altına taşıyarak maksimum teklif talebi toplar.',
      order: ['hero', 'site_audit', 'services', 'google_reviews', 'process', 'intro', 'brand_scene', 'projects_showcase', 'clients_strip', 'latest_blog', 'builder_blocks', 'cta'],
      vis: { hero: true, site_audit: true, services: true, google_reviews: true, process: true, intro: true, brand_scene: true, projects_showcase: false, clients_strip: false, latest_blog: false, builder_blocks: true, cta: true }
    },
    {
      id: 'social_trust',
      name: '⭐ Sosyal Kanıt & Güven Odaklı',
      desc: 'Google 5.0★ Yorumları, 62+ Referans Markayı ve Projeleri üst sıralarda öne çıkarır.',
      order: ['hero', 'services', 'google_reviews', 'clients_strip', 'projects_showcase', 'site_audit', 'process', 'brand_scene', 'intro', 'latest_blog', 'builder_blocks', 'cta'],
      vis: { hero: true, services: true, google_reviews: true, clients_strip: true, projects_showcase: true, site_audit: true, process: true, brand_scene: true, intro: false, latest_blog: false, builder_blocks: true, cta: true }
    },
    {
      id: 'full_agency',
      name: '🎯 Tam Ajans Vitrini (Tüm Bölümler)',
      desc: 'Projeler, Referans Markalar ve Son Blog Yazıları dahil tüm vitrin bölümlerini aktif eder.',
      order: ['hero', 'intro', 'services', 'projects_showcase', 'brand_scene', 'process', 'clients_strip', 'google_reviews', 'site_audit', 'latest_blog', 'builder_blocks', 'cta'],
      vis: { hero: true, intro: true, services: true, projects_showcase: true, brand_scene: true, process: true, clients_strip: true, google_reviews: true, site_audit: true, latest_blog: true, builder_blocks: true, cta: true }
    }
  ];

  const WIDGET_CATALOG = [
    { type: 'hero_banner', name: 'Kapak & Tanıtım', sub: 'Görsel + Başlık + Buton', defaults: { eyebrow: 'ZEYNART STÜDYO', title: 'Yeni nesil dijital deneyim.', subtitle: 'Markanızın hedeflerine özel strateji, tasarım ve yazılım mimarisi.', btn_label: 'İletişime geçin', btn_url: '/iletisim/', image: '/images/banners/services.webp' } },
    { type: 'heading_text', name: 'Başlık & Metin', sub: 'Üst etiket + H2 + Açıklama', defaults: { eyebrow: 'YAKLAŞIMIMIZ', title: 'Net strateji, güçlü tasarım.', muted: 'Uzun vadeli dijital değer.', copy: 'İş hedeflerinizi anlayan, hızlı açılan ve kolay yönetilen modern çözümler geliştiriyoruz.', btn_label: '', btn_url: '/iletisim/', card_style: true } },
    { type: 'rich_text', name: 'Zengin İçerik', sub: 'Serbest HTML / Makale Bloğu', defaults: { html: '<h3>Özgün Dijital Çözümler</h3><p>Buradaki zengin metin alanını dilediğiniz gibi biçimlendirebilir, listeler ve bağlantılar ekleyebilirsiniz.</p>' } },
    { type: 'image_showcase', name: 'Görsel Vitrin', sub: 'Tekil Görsel + Alt Yazı', defaults: { src: '/images/zeynart-identity-still-life.webp', alt: 'Zeynart Kurumsal Tasarım', caption: 'ZEYNART STÜDYO SEÇKİSİ' } },
    { type: 'gallery_grid', name: 'Görsel Galeri', sub: 'Çoklu Izgara Galeri', defaults: { images: [{ src: '/images/zeynart-identity-still-life.webp', alt: 'Görsel 1' }, { src: '/images/zeynart-emblem-gallery.webp', alt: 'Görsel 2' }, { src: '/images/zeynart-brand-art.webp', alt: 'Görsel 3' }] } },
    { type: 'stats_bar', name: 'Rakamlarla Güç', sub: '4’lü İstatistik Kutu Dizisi', defaults: { items: [{ value: '2014', label: 'Kuruluş Yılı & Deneyim' }, { value: '62+', label: 'Kurumsal Marka İş Birliği' }, { value: '%99,9', label: 'Sunucu Uptime Sürekliliği' }, { value: '7/24', label: 'Teknik Destek & İzleme' }] } },
    { type: 'service_cards', name: 'Hizmet Kartları', sub: 'Canlı Hizmet Vitrini', defaults: { limit: 6 } },
    { type: 'project_showcase', name: 'Proje Vitrini', sub: 'Seçili Ürün Konseptleri', defaults: {} },
    { type: 'process_steps', name: 'Süreç Adımları', sub: 'Numaralı Çalışma Adımları', defaults: { steps: [] } },
    { type: 'pricing_packages', name: 'Fiyat / Paketler', sub: 'Karşılaştırmalı Paket Kartları', defaults: { packages: [] } },
    { type: 'faq_accordion', name: 'Soru-Cevap (SSS)', sub: 'Açılır Akordeon + Şema', defaults: { heading: 'Sıkça sorulan sorular.', items: [{ q: 'Proje süreci nasıl ilerliyor?', a: 'Keşif, strateji, tasarım, yazılım geliştirme ve test adımlarını şeffaf takvimle yönetiyoruz.' }, { q: 'Yayın sonrası teknik destek sağlıyor musunuz?', a: 'Evet, sunucu izleme, yedekleme ve içerik güncellemelerinde yanınızdayız.' }] } },
    { type: 'client_logos', name: 'Müşteri Logoları', sub: 'Referans Marka Izgarası', defaults: { limit: 12 } },
    { type: 'reviews_grid', name: 'Müşteri Yorumları', sub: 'Doğrulanmış Geri Bildirimler', defaults: {} },
    { type: 'cta_banner', name: 'Teklif Çağrı Bandı', sub: 'Vurgulu Aksiyon Alanı', defaults: { eyebrow: 'BİR FİKRİNİZ Mİ VAR?', line1: 'Birlikte', line2: 'hayata', accent: 'geçirelim.', btn_label: 'Projenizi anlatın', btn_url: '/iletisim/' } },
    { type: 'quote_wizard', name: 'Teklif Sihirbazı', sub: '4 Adımlı İnteraktif Teklif & Bütçe Aracı', defaults: {} },
    { type: 'contact_form_embed', name: 'İletişim Formu', sub: 'Canlı Proje Talep Formu', defaults: {} },
    { type: 'spacer_divider', name: 'Boşluk & Ayırıcı', sub: 'Dikey Mesafe / Çizgi', defaults: { height: 48, show_line: true } },
    { type: 'custom_html', name: 'Özel Kod Alanı', sub: 'Güvenli Özel HTML Bloğu', defaults: { html: '<div class="zb-card"><p>Özel HTML bileşeni</p></div>' } }
  ];

  const S = {
    view: 'dashboard',
    bootMs: null,
    store: null,
    summary: null,
    robotsPreview: '',
    llmsPreview: '',
    tabs: {
      analytics: 'live',
      menus: 'header',
      home: 'order',
      about: 'clients',
      contact: 'info',
      appearance: 'theme',
      growth_tools: 'quick_contact',
      seo: 'general',
      social_media: 'accounts',
      performance: 'lscache',
      ota_hub: 'fleet',
      settings: 'identity'
    },
    blog: {
      mode: 'list', // 'list' | 'edit' | 'settings'
      items: [],
      total: 0,
      page: 1,
      totalPages: 1,
      search: '',
      category: 'TÜMÜ',
      status: 'all',
      categories: {},
      editingPost: null,
      oldSlug: null,
      sourceMode: false,
      quickCatOpen: false,
      i18nTab: 'en',
      autoTranslate: true,
      seoAnalysis: { score: 90, checks: [] }
    },
    blogCats: {
      loaded: false,
      loading: false,
      items: [],
      defaultCategory: null,
      defaultCategoryId: '',
      blogSlug: 'blog',
      search: '',
      editingId: null,
      selectedIds: [],
      slugManual: false
    },
    i18nBatch: {
      loaded: false,
      loading: false,
      state: 'idle', // 'idle' | 'running' | 'paused'
      forceAll: false,
      totalPosts: 0,
      translatedCount: 0,
      pendingCount: 0,
      failedCount: 0,
      sessionDone: 0,
      avgMs: 0,
      activeSlug: '',
      activeTitle: '',
      activeOrderNum: 0,
      filter: 'all', // 'all' | 'done' | 'pending' | 'error'
      search: '',
      visibleLimit: 80,
      items: [],
      showOnBlogView: false,
      workerActive: false
    },
    analytics: {
      rangeDays: 30,
      report: null,
      loading: false,
      execReport: null,
      execDays: 30,
      execLoading: false
    },
    media: {
      items: [],
      total: 0,
      page: 1,
      totalPages: 1,
      folder: 'all',
      folders: {},
      search: '',
      typeFilter: 'all',
      sort: 'newest',
      viewMode: 'grid', // 'grid' | 'large' | 'compact' | 'list'
      optimizeWebp: true,
      webpQuality: 86,
      maxWidth: 1920,
      autoAlt: true,
      stats: null,
      recentUploadedUrls: [],
      selectedItem: null,
      bulkMode: false,
      selectedUrls: [],
      lastClickedIdx: null
    },
    builder: {
      mode: 'custom', // 'custom' | 'core'
      coreKey: 'anasayfa',
      activePageId: null,
      editingPage: null,
      addToHeader: false,
      sections: [],
      selectedSecIdx: 0,
      selectedColIdx: 0,
      selectedWidgetIdx: null,
      stageMode: 'canvas', // 'canvas' | 'preview'
      previewDevice: '100%',
      previewHtml: ''
    },
    pages: {
      mode: 'list', // 'list' | 'edit'
      filter: 'all',
      search: '',
      selectedKeys: [],
      quickEditKey: null,
      activeKey: 'anasayfa',
      editorTab: 'content', // 'content' | 'builder' | 'seo'
      subTab: '',
      sourceMode: false,
      draftCustom: null
    },
    activeServiceIdx: 0,
    activeProjectIdx: 0,
    clientSearch: '',
    logs404: [],
    revisions: [],
    securityLogs: [],
    lockedIps: [],
    securityAudit20: null,
    currentIp: '',
    securityFilter: 'all',
    currentUser: null,
    editingUserObj: null,
    seoDoctorReport: null,
    seoDoctorLoading: false,
    newsBotQueue: [],
    newsBotState: null,
    newsBotCronUrl: '',
    newsBotFetching: false,
    uptime: {
      monitors: [],
      summary: null,
      filter: 'all',
      group: 'all',
      search: '',
      scanning: false,
      stopRequested: false,
      scanDone: 0,
      scanTotal: 0,
      showBulkImport: false,
      showConfig: false,
      editingMonitor: null
    },
    uptimeCronUrl: '',
    vcardsState: {
      activeIdx: 0
    },
    proposals: [],
    proposalsFilter: 'all',
    editingProposal: null,
    messagesMode: 'kanban', // 'kanban' | 'list'
    messagesFilter: 'all',
    crmStages: {},
    crmSummary: null,
    crmEditingId: null,
    clientTrackers: [],
    clientTrackersSummary: null,
    clientTrackersFilter: 'all',
    editingTracker: null,
    renewals: [],
    renewalsSummary: null,
    renewalsFilter: 'all',
    editingRenewal: null,
    bookings: [],
    bookingsSummary: null,
    solutionCatalog: null,
    blogComments: [],
    blogCommentsFilter: 'all',
    searchInsights: null,
    systemHealth: null,
    turboEngine: null,
    versionHistory: [],
    unusedFilesSummary: null,
    unusedFilesReport: null,
    unusedFilesScanning: false,
    unusedFilesCleaning: false,
    ota: null,
    otaLoading: false,
    otaSummary: null,
    otaEditingNode: null,
    otaTokenSpot: '',
    otaBatchRunning: false,
    otaBatchStop: false,
    orphanScan: null,
    orphanScanning: false,
    vaultBackups: [],
    twoFaSetup: null,
    twoFaRecoveryCodes: null,
    seoContentAudit: null,
    seoAuditLoading: false,
    indexnowLogs: [],
    openAiWriterOnNew: false,
    aiWriterDraft: {
      topic: '',
      tone: 'kurumsal',
      length: 'kapsamli',
      target_service: ''
    }
  };

  /* ======================== UTILITIES ======================== */
  function esc(val) {
    return String(val ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function resolveUrl(p) {
    const s = String(p || '');
    if (!s) return '';
    if (/^(https?:|mailto:|tel:|#|data:)/i.test(s)) return s;
    const clean = '/' + s.replace(/^\/+/, '');
    return (CFG.basePath || '') + clean;
  }

  function rememberMediaMtimes(items) {
    S.mediaMtimes = S.mediaMtimes || {};
    (items || []).forEach(it => {
      if (it && it.url && it.mtime) {
        const cleanKey = '/' + String(it.url).split('?')[0].replace(/^\/+/, '');
        S.mediaMtimes[cleanKey] = Math.max(Number(S.mediaMtimes[cleanKey] || 0), Number(it.mtime));
      }
    });
  }

  function resolvePreviewUrl(p, explicitMtime) {
    const s = String(p || '');
    if (!s) return '';
    if (/^(https?:|mailto:|tel:|#|data:)/i.test(s)) return s;
    if (s.includes('?')) return resolveUrl(s);
    const clean = '/' + s.replace(/^\/+/, '');
    S.mediaMtimes = S.mediaMtimes || {};
    const mt = Number(explicitMtime || S.mediaMtimes[clean] || 0);
    const baseResolved = (CFG.basePath || '') + clean;
    if (mt > 0 && clean.startsWith('/uploads/')) {
      return `${baseResolved}?v=${mt}`;
    }
    return baseResolved;
  }

  function fmtBytes(bytes) {
    const n = Number(bytes || 0);
    if (n < 1024) return n + ' B';
    if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
    return (n / (1024 * 1024)).toFixed(2) + ' MB';
  }

  function uid(prefix) {
    return (prefix || 'id') + '-' + Math.random().toString(36).slice(2, 9);
  }

  function slugifyClient(str) {
    const map = { 'ş': 's', 'Ş': 's', 'ı': 'i', 'İ': 'i', 'I': 'i', 'ğ': 'g', 'Ğ': 'g', 'ü': 'u', 'Ü': 'u', 'ö': 'o', 'Ö': 'o', 'ç': 'c', 'Ç': 'c' };
    return String(str || '')
      .split('')
      .map(ch => map[ch] || ch)
      .join('')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function toast(msg, isError) {
    const stack = document.getElementById('zsToastStack');
    if (!stack) return;
    const el = document.createElement('div');
    el.className = 'zs-toast' + (isError ? ' error' : '');
    el.innerHTML = '<span>' + (isError ? '⚠' : '✓') + '</span><span>' + esc(msg) + '</span>';
    stack.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 220);
    }, 3600);
  }

  function toPlainDict(val) {
    return (val && typeof val === 'object' && !Array.isArray(val)) ? val : {};
  }

  function normalizeClientStoreMaps(st) {
    if (!st || typeof st !== 'object') return st;
    st.page_titles = toPlainDict(st.page_titles);
    st.page_slugs = toPlainDict(st.page_slugs);
    st.page_states = toPlainDict(st.page_states);
    st.page_menu_labels = toPlainDict(st.page_menu_labels);
    st.page_typography = toPlainDict(st.page_typography);
    st.legal_pages = toPlainDict(st.legal_pages);
    st.core_page_blocks = toPlainDict(st.core_page_blocks);
    st.page_live_patches = toPlainDict(st.page_live_patches);
    st.seo = toPlainDict(st.seo);
    st.seo.pages = toPlainDict(st.seo.pages);
    if (st.home && typeof st.home === 'object') {
      st.home.section_visibility = toPlainDict(st.home.section_visibility);
    }
    return st;
  }

  function encodeApiPayloadB64(obj) {
    const bytes = new TextEncoder().encode(JSON.stringify(obj || {}));
    let bin = '';
    const step = 0x8000;
    for (let i = 0; i < bytes.length; i += step) {
      bin += String.fromCharCode.apply(null, bytes.subarray(i, i + step));
    }
    return btoa(bin);
  }

  async function api(action, payload) {
    const bodyParams = new URLSearchParams();
    bodyParams.set('csrf_token', CFG.csrfToken || '');
    bodyParams.set('_zb64', encodeApiPayloadB64(payload || {}));
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
      throw new Error(`Sunucu yanıtı okunamadı (HTTP ${res.status}). Oturumunuzu yenileyip tekrar deneyin.`);
    }
    if (!json.ok) {
      if (json.auth_required) {
        window.location.reload();
      }
      throw new Error(json.error || 'İşlem sırasında bir hata oluştu.');
    }
    if (json && json.store) {
      json.store = normalizeClientStoreMaps(json.store);
    }
    return json;
  }

  async function apiUpload(formData) {
    formData.append('csrf_token', CFG.csrfToken);
    const res = await fetch(CFG.apiUrl + '?action=media_upload', {
      method: 'POST',
      headers: { 'X-CSRF-Token': CFG.csrfToken },
      body: formData
    });
    const json = await res.json();
    if (!json.ok) {
      throw new Error(json.error || 'Görsel yüklenemedi.');
    }
    return json;
  }

  function humanizeAltFromFilename(rawName) {
    let base = String(rawName || '').replace(/\.[^.]+$/, '');
    base = base.replace(/-\d{2,4}x\d{2,4}$/i, '').replace(/-[A-Za-z0-9]{8}$/, '');
    const words = base.split(/[-_\s.]+/).filter(w => w && !/^\d{6,}$/.test(w));
    if (!words.length) return 'Zeynart Dijital Stüdyo Görseli';
    return words.map(w => w.charAt(0).toLocaleUpperCase('tr-TR') + w.slice(1).toLocaleLowerCase('tr-TR')).join(' ');
  }

  function apiUploadSingleWithProgress(file, opts, onProgress, registerXhr) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      if (typeof registerXhr === 'function') registerXhr(xhr);

      xhr.open('POST', CFG.apiUrl + '?action=media_upload', true);
      xhr.setRequestHeader('X-CSRF-Token', CFG.csrfToken);

      xhr.upload.onprogress = ev => {
        if (ev.lengthComputable && typeof onProgress === 'function') {
          onProgress(ev.loaded, ev.total);
        }
      };

      xhr.onload = () => {
        let json = null;
        try {
          json = JSON.parse(xhr.responseText || '{}');
        } catch (_) {
          reject(new Error('Sunucudan geçersiz yanıt alındı.'));
          return;
        }
        if (xhr.status >= 200 && xhr.status < 300 && json && json.ok && json.uploaded && json.uploaded[0]) {
          resolve(json.uploaded[0]);
        } else {
          if (json && json.auth_required) window.location.reload();
          reject(new Error((json && json.error) || 'Görsel yüklenemedi.'));
        }
      };

      xhr.onerror = () => reject(new Error('Ağ bağlantı hatası oluştu.'));
      xhr.onabort = () => {
        const abErr = new Error('Yükleme durduruldu.');
        abErr.aborted = true;
        reject(abErr);
      };

      const fd = new FormData();
      fd.append('csrf_token', CFG.csrfToken);
      fd.append('file', file, file.name || ('gorsel-' + Date.now() + '.png'));
      fd.append('optimize_webp', opts.optimizeWebp !== false ? '1' : '0');
      fd.append('webp_quality', String(opts.webpQuality || 86));
      fd.append('max_width', String(opts.maxWidth ?? 1920));
      fd.append('auto_alt', opts.autoAlt !== false ? '1' : '0');
      if (opts.subfolder) fd.append('subfolder', String(opts.subfolder));
      const ms = S.store?.media_stamp || {};
      const stampOn = opts.stampEnabled !== undefined ? Boolean(opts.stampEnabled) : Boolean(ms.enabled);
      fd.append('stamp_enabled', stampOn ? '1' : '0');
      if (stampOn) {
        fd.append('stamp_text', String(opts.stampText ?? ms.text ?? ''));
        fd.append('stamp_position', String(opts.stampPosition ?? ms.position ?? 'bottom-right'));
        fd.append('stamp_style', String(opts.stampStyle ?? ms.style ?? 'glass-pill'));
        fd.append('stamp_opacity', String(opts.stampOpacity ?? ms.opacity ?? 88));
        fd.append('stamp_scale', String(opts.stampScale ?? ms.scale ?? 100));
      }
      xhr.send(fd);
    });
  }

  let activePreviewBlobUrls = [];
  function revokeActivePreviewBlobs() {
    activePreviewBlobUrls.forEach(u => {
      try { URL.revokeObjectURL(u); } catch (_) {}
    });
    activePreviewBlobUrls = [];
  }

  async function runStudioUploadQueue(rawFiles, mountEl, config) {
    const cfg = config || {};
    const validExts = /\.(jpe?g|png|webp|avif|gif|svg)$/i;
    const files = Array.from(rawFiles || []).filter(f => (f.type && f.type.startsWith('image/')) || validExts.test(f.name || ''));
    if (!files.length) {
      toast('Lütfen geçerli görsel dosyaları (JPG, PNG, WebP, AVIF, GIF veya SVG) seçin.', true);
      return [];
    }
    if (!mountEl) return [];

    revokeActivePreviewBlobs();

    const totalCount = files.length;
    const totalOrigBytes = files.reduce((acc, f) => acc + (Number(f.size) || 0), 0);
    const startTs = performance.now();
    const activeXhrs = new Set();
    let abortedAll = false;
    let completedCount = 0;
    let failedCount = 0;
    let totalSavedBytes = 0;
    let totalFinalBytes = 0;
    let totalOriginalDoneBytes = 0;
    const uploadedResults = [];

    const tasks = files.map((file, idx) => {
      const blobUrl = URL.createObjectURL(file);
      activePreviewBlobUrls.push(blobUrl);
      const rawExt = ((file.name || '').split('.').pop() || 'IMG').toUpperCase();
      return {
        idx,
        file,
        blobUrl,
        origExt: rawExt.slice(0, 5),
        origSize: Number(file.size) || 0,
        loadedBytes: 0,
        pct: 0,
        status: 'queued', // 'queued' | 'uploading' | 'optimizing' | 'done' | 'error' | 'aborted'
        result: null,
        errorMsg: ''
      };
    });

    mountEl.innerHTML = `
      <div class="zs-upload-deck" id="zsActiveUploadDeck">
        <div class="zs-upload-deck-head">
          <div class="zs-upload-deck-status">
            <span class="zs-upload-pulse-dot"></span>
            <div>
              <div class="zs-upload-deck-title" id="zsUpDeckTitle">⚡ Canlı Görsel Yükleme &amp; Optimizasyon · 0 / ${totalCount} Görsel</div>
              <div class="zs-upload-deck-sub" id="zsUpDeckSub">${fmtBytes(totalOrigBytes)} toplam boyut · Paralel aktarım başlatılıyor...</div>
            </div>
          </div>
          <div class="zs-upload-deck-telemetry">
            <span class="zs-telemetry-pill" id="zsUpSpeedPill">⚡ Hazırlanıyor...</span>
            <span class="zs-telemetry-pill savings" id="zsUpSavingsPill" style="display:none">🔥 WebP Kazancı</span>
            <div class="zs-upload-master-pct" id="zsUpMasterPct">0%</div>
            <div id="zsUpDeckActions" style="display:flex;gap:7px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="zsUpAbortBtn">✕ Durdur</button>
            </div>
          </div>
        </div>

        <div class="zs-upload-master-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="zsUpMasterTrack">
          <div class="zs-upload-master-fill" id="zsUpMasterFill" style="width:2%"></div>
        </div>

        <div class="zs-upload-queue-grid" id="zsUpQueueGrid">
          ${tasks.map(t => `
            <div class="zs-upload-item is-queued" data-up-idx="${t.idx}" style="animation-delay:${Math.min(t.idx * 40, 320)}ms">
              <div class="zs-upload-item-top">
                <div class="zs-upload-item-thumb">
                  <img src="${esc(t.blobUrl)}" alt=""/>
                  <span class="zs-upload-item-badge" data-up-badge="${t.idx}">•••</span>
                </div>
                <div class="zs-upload-item-info">
                  <strong class="zs-upload-item-name" title="${esc(t.file.name)}">${esc(t.file.name || ('Görsel ' + (t.idx + 1)))}</strong>
                  <div class="zs-upload-item-meta" data-up-meta="${t.idx}">
                    <span>${esc(t.origExt)} · ${fmtBytes(t.origSize)}</span>
                  </div>
                </div>
              </div>
              <div class="zs-upload-item-bar">
                <div class="zs-upload-item-fill" data-up-fill="${t.idx}" style="width:0%"></div>
              </div>
              <div class="zs-upload-item-foot">
                <span class="zs-upload-item-state" data-up-state="${t.idx}">Sırada bekliyor...</span>
                <div class="zs-upload-mini-actions" data-up-actions="${t.idx}"></div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;

    const deckEl = mountEl.querySelector('#zsActiveUploadDeck');
    const titleEl = mountEl.querySelector('#zsUpDeckTitle');
    const subEl = mountEl.querySelector('#zsUpDeckSub');
    const speedPillEl = mountEl.querySelector('#zsUpSpeedPill');
    const savingsPillEl = mountEl.querySelector('#zsUpSavingsPill');
    const masterPctEl = mountEl.querySelector('#zsUpMasterPct');
    const masterFillEl = mountEl.querySelector('#zsUpMasterFill');
    const masterTrackEl = mountEl.querySelector('#zsUpMasterTrack');
    const actionsSlot = mountEl.querySelector('#zsUpDeckActions');

    const abortBtn = mountEl.querySelector('#zsUpAbortBtn');
    if (abortBtn) {
      abortBtn.onclick = () => {
        abortedAll = true;
        activeXhrs.forEach(x => { try { x.abort(); } catch (_) {} });
        activeXhrs.clear();
      };
    }

    const updateMasterTelemetry = () => {
      const sumLoaded = tasks.reduce((acc, t) => acc + t.loadedBytes, 0);
      const byteRatio = totalOrigBytes > 0 ? (sumLoaded / totalOrigBytes) : ((completedCount + failedCount) / totalCount);
      const doneRatio = (completedCount + failedCount) / totalCount;
      // Blend byte transfer (82%) + server WebP finalization (18%) for silky honest progress
      const rawPct = Math.round((byteRatio * 82) + (doneRatio * 18));
      const pct = (completedCount + failedCount) >= totalCount ? 100 : Math.max(2, Math.min(99, rawPct));

      if (masterPctEl) masterPctEl.textContent = pct + '%';
      if (masterFillEl) masterFillEl.style.width = pct + '%';
      if (masterTrackEl) masterTrackEl.setAttribute('aria-valuenow', String(pct));

      const elapsedSec = Math.max(0.08, (performance.now() - startTs) / 1000);
      const bytesPerSec = sumLoaded / elapsedSec;
      const remBytes = Math.max(0, totalOrigBytes - sumLoaded);
      const etaSec = bytesPerSec > 1024 ? Math.ceil(remBytes / bytesPerSec) : 0;

      if (speedPillEl) {
        if ((completedCount + failedCount) >= totalCount) {
          speedPillEl.textContent = `⏱ ${elapsedSec.toFixed(1)} sn'de tamamlandı`;
        } else {
          speedPillEl.textContent = `⚡ ${fmtBytes(bytesPerSec)}/sn` + (etaSec > 0 ? ` · ~${etaSec} sn` : '');
        }
      }

      if (titleEl) {
        if (abortedAll) {
          titleEl.textContent = `⚠ Yükleme Durduruldu · ${completedCount} / ${totalCount} Görsel Tamamlandı`;
        } else if ((completedCount + failedCount) >= totalCount) {
          titleEl.textContent = failedCount > 0
            ? `Tamamlandı · ${completedCount} Başarılı, ${failedCount} Hatalı`
            : `✨ Harika! ${completedCount} / ${totalCount} Görsel Yüklendi & Optimize Edildi`;
        } else {
          titleEl.textContent = `⚡ Canlı Çoklu Yükleme · ${completedCount} / ${totalCount} Görsel Tamamlandı`;
        }
      }

      if (subEl) {
        if ((completedCount + failedCount) >= totalCount) {
          subEl.textContent = totalSavedBytes > 0
            ? `${fmtBytes(totalOriginalDoneBytes)} orijinal boyut → ${fmtBytes(totalFinalBytes)} optimize WebP boyutu (/uploads klasöründe yayında)`
            : `${fmtBytes(totalFinalBytes)} toplam boyut · /uploads klasörüne kaydedildi`;
        } else {
          subEl.textContent = `${fmtBytes(Math.min(sumLoaded, totalOrigBytes))} / ${fmtBytes(totalOrigBytes)} aktarıldı · Otomatik WebP & Retina ölçekleme aktif`;
        }
      }

      if (savingsPillEl && totalSavedBytes > 1024 && totalOriginalDoneBytes > 0) {
        const savedPct = Math.max(1, Math.round((totalSavedBytes / totalOriginalDoneBytes) * 100));
        savingsPillEl.style.display = 'inline-flex';
        savingsPillEl.textContent = `🔥 -%${savedPct} Küçüldü (${fmtBytes(totalSavedBytes)} tasarruf)`;
      }
    };

    const paintTaskCard = t => {
      const cardEl = mountEl.querySelector(`[data-up-idx="${t.idx}"]`);
      if (!cardEl) return;
      cardEl.className = `zs-upload-item is-${t.status}`;
      const badgeEl = mountEl.querySelector(`[data-up-badge="${t.idx}"]`);
      const metaEl = mountEl.querySelector(`[data-up-meta="${t.idx}"]`);
      const fillEl = mountEl.querySelector(`[data-up-fill="${t.idx}"]`);
      const stateEl = mountEl.querySelector(`[data-up-state="${t.idx}"]`);
      const actEl = mountEl.querySelector(`[data-up-actions="${t.idx}"]`);

      if (fillEl) fillEl.style.width = t.pct + '%';

      if (t.status === 'queued') {
        if (badgeEl) badgeEl.textContent = '•••';
        if (stateEl) stateEl.textContent = 'Sırada bekliyor...';
      } else if (t.status === 'uploading') {
        if (badgeEl) badgeEl.textContent = t.pct + '%';
        if (stateEl) stateEl.textContent = `Aktarılıyor · %${t.pct}`;
      } else if (t.status === 'optimizing') {
        if (badgeEl) badgeEl.textContent = '⚡';
        if (stateEl) stateEl.textContent = '⚡ WebP optimize ediliyor...';
      } else if (t.status === 'done' && t.result) {
        const r = t.result;
        if (badgeEl) badgeEl.textContent = '✓';
        const dimTxt = (r.width && r.height) ? ` · ${r.width}×${r.height}` : '';
        const savedPct = Number(r.saved_pct || 0);
        if (metaEl) {
          metaEl.innerHTML =
            `<span>${esc((r.ext || 'webp').toUpperCase())} · ${fmtBytes(r.size)}</span>` +
            (savedPct >= 3 ? `<span class="zs-upload-savings-tag">-%${savedPct}</span>` : '');
        }
        if (stateEl) stateEl.textContent = `✓ Hazır${dimTxt}`;
        if (actEl) {
          const canCanvas = r.ext && r.ext !== 'svg';
          actEl.innerHTML = cfg.pickerMode
            ? `<button type="button" class="zs-upload-mini-btn" data-up-pick="${t.idx}">✓ Seç</button>` +
              (canCanvas ? `<button type="button" class="zs-upload-mini-btn" data-up-canvas="${t.idx}" title="Görsel Üzerine Yazı / Marka Yaz">🎨</button>` : '') +
              `<button type="button" class="zs-upload-mini-btn" data-up-copy="${t.idx}" title="URL Kopyala">🔗</button>`
            : (canCanvas ? `<button type="button" class="zs-upload-mini-btn" data-up-canvas="${t.idx}" title="Görsel Üzerine Yazı / Marka / Domain Yaz">🎨 Yazı Yaz</button>` : '') +
              `<button type="button" class="zs-upload-mini-btn" data-up-copy="${t.idx}" title="URL Kopyala">🔗</button>` +
              `<button type="button" class="zs-upload-mini-btn" data-up-open="${t.idx}" title="Büyük Önizleme">🔍</button>`;

          const copyB = actEl.querySelector(`[data-up-copy="${t.idx}"]`);
          if (copyB) {
            copyB.onclick = ev => {
              ev.stopPropagation();
              copyTextSafe(r.url, 'Görsel adresi kopyalandı: ' + r.url);
            };
          }
          const canvasB = actEl.querySelector(`[data-up-canvas="${t.idx}"]`);
          if (canvasB) {
            canvasB.onclick = ev => {
              ev.stopPropagation();
              document.getElementById('zsMediaModal')?.classList.remove('open');
              openImageCanvasStudio(r);
            };
          }
          const pickB = actEl.querySelector(`[data-up-pick="${t.idx}"]`);
          if (pickB && typeof cfg.onPickSingle === 'function') {
            pickB.onclick = ev => {
              ev.stopPropagation();
              cfg.onPickSingle(r);
            };
          }
          const openB = actEl.querySelector(`[data-up-open="${t.idx}"]`);
          if (openB && typeof cfg.onInspectUploaded === 'function') {
            openB.onclick = ev => {
              ev.stopPropagation();
              cfg.onInspectUploaded(r);
            };
          }
        }
      } else if (t.status === 'error' || t.status === 'aborted') {
        if (badgeEl) badgeEl.textContent = '✕';
        if (stateEl) stateEl.textContent = t.errorMsg || 'Yüklenemedi';
      }
    };

    const uploadOpts = {
      optimizeWebp: cfg.optimizeWebp !== undefined ? cfg.optimizeWebp : S.media.optimizeWebp,
      webpQuality: cfg.webpQuality || S.media.webpQuality || 86,
      maxWidth: cfg.maxWidth !== undefined ? cfg.maxWidth : (S.media.maxWidth ?? 1920),
      autoAlt: cfg.autoAlt !== undefined ? cfg.autoAlt : S.media.autoAlt,
      subfolder: cfg.subfolder || ''
    };

    let cursor = 0;
    const CONCURRENCY = Math.min(2, totalCount);

    const worker = async () => {
      while (!abortedAll) {
        const myIdx = cursor++;
        if (myIdx >= tasks.length) break;
        const task = tasks[myIdx];
        task.status = 'uploading';
        task.pct = 8;
        paintTaskCard(task);
        updateMasterTelemetry();

        let myXhr = null;
        try {
          // Ensure browser paints initial uploading bar smoothly before ultra-fast local XHR completes
          await new Promise(r => requestAnimationFrame(() => setTimeout(r, 55)));
          const upRes = await apiUploadSingleWithProgress(
            task.file,
            uploadOpts,
            (loaded, total) => {
              task.loadedBytes = Math.min(task.origSize || total, loaded);
              const rawP = total > 0 ? Math.round((loaded / total) * 88) : 50;
              task.pct = Math.max(10, Math.min(92, rawP));
              if (loaded >= total) {
                task.status = 'optimizing';
                task.pct = 94;
              }
              paintTaskCard(task);
              updateMasterTelemetry();
            },
            xhrInstance => {
              myXhr = xhrInstance;
              activeXhrs.add(xhrInstance);
            }
          );

          task.loadedBytes = task.origSize;
          task.pct = 100;
          task.status = 'done';
          task.result = upRes;
          completedCount++;
          totalOriginalDoneBytes += (Number(upRes.original_size) || task.origSize);
          totalFinalBytes += (Number(upRes.size) || 0);
          totalSavedBytes += Math.max(0, Number(upRes.saved_bytes || 0));
          uploadedResults.push(upRes);

          if (!S.media.recentUploadedUrls.includes(upRes.url)) {
            S.media.recentUploadedUrls.unshift(upRes.url);
          }

          paintTaskCard(task);
          updateMasterTelemetry();
        } catch (err) {
          if (err && err.aborted) {
            task.status = 'aborted';
            task.errorMsg = 'Durduruldu';
          } else {
            task.status = 'error';
            task.errorMsg = err?.message || 'Hata oluştu';
            failedCount++;
          }
          paintTaskCard(task);
          updateMasterTelemetry();
        } finally {
          if (myXhr) activeXhrs.delete(myXhr);
        }
      }
    };

    const workers = [];
    for (let w = 0; w < CONCURRENCY; w++) {
      workers.push(worker());
    }
    await Promise.all(workers);

    if (deckEl && !abortedAll) {
      deckEl.classList.add('is-complete');
    }
    updateMasterTelemetry();

    if (S.summary && uploadedResults.length > 0) {
      S.summary.media_total = (S.summary.media_total || 0) + uploadedResults.length;
      updateSidebarBadges();
    }

    if (actionsSlot) {
      let btnsHtml = '';
      if (cfg.pickerMode && uploadedResults.length > 0) {
        if (cfg.allowMultiple && uploadedResults.length > 1) {
          btnsHtml += `<button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="zsUpInsertAllBtn">✓ Yüklenen ${uploadedResults.length} Görselin Tümünü Ekle</button>`;
        } else {
          btnsHtml += `<button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="zsUpUseFirstBtn">✓ Görseli Kullan</button>`;
        }
      } else if (!cfg.pickerMode && uploadedResults.length > 1) {
        btnsHtml += `<button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="zsUpBulkSelectNewBtn">✓ Yüklenen ${uploadedResults.length} Görseli Seç</button>`;
      }
      btnsHtml += `<button type="button" class="zs-btn zs-btn-sm" id="zsUpDismissBtn">Paneli Kapat ✕</button>`;
      actionsSlot.innerHTML = btnsHtml;

      const dismissB = actionsSlot.querySelector('#zsUpDismissBtn');
      if (dismissB) {
        dismissB.onclick = () => {
          revokeActivePreviewBlobs();
          mountEl.innerHTML = '';
        };
      }
      const insAllB = actionsSlot.querySelector('#zsUpInsertAllBtn');
      if (insAllB && typeof cfg.onPickMultiple === 'function') {
        insAllB.onclick = () => cfg.onPickMultiple(uploadedResults);
      }
      const useFirstB = actionsSlot.querySelector('#zsUpUseFirstBtn');
      if (useFirstB && typeof cfg.onPickSingle === 'function') {
        useFirstB.onclick = () => cfg.onPickSingle(uploadedResults[0]);
      }
      const selNewB = actionsSlot.querySelector('#zsUpBulkSelectNewBtn');
      if (selNewB) {
        selNewB.onclick = () => {
          const merged = new Set(S.media.selectedUrls);
          uploadedResults.forEach(r => { if (r.url) merged.add(r.url); });
          S.media.selectedUrls = Array.from(merged);
          S.media.bulkMode = true;
          syncMediaSelectionDom();
          toast(`${uploadedResults.length} yeni görsel toplu seçime eklendi.`);
        };
      }
    }

    if (uploadedResults.length > 0) {
      toast(
        totalSavedBytes > 4096
          ? `${uploadedResults.length} görsel yüklendi (${fmtBytes(totalSavedBytes)} WebP tasarrufu sağlandı!)`
          : `${uploadedResults.length} görsel başarıyla yüklendi.`
      );
    }

    if (typeof cfg.onComplete === 'function') {
      await cfg.onComplete(uploadedResults);
    }

    return uploadedResults;
  }

  function renderSeoRing(score) {
    const s = Math.max(0, Math.min(100, Number(score || 0)));
    const r = 28;
    const c = 2 * Math.PI * r;
    const offset = c - (s / 100) * c;
    const color = s >= 80 ? '#10b981' : (s >= 55 ? '#f59e0b' : '#ef4444');
    return `
      <div class="zs-seo-ring">
        <svg viewBox="0 0 68 68">
          <circle cx="34" cy="34" r="${r}" fill="none" stroke="rgba(148,210,214,0.14)" stroke-width="6"/>
          <circle cx="34" cy="34" r="${r}" fill="none" stroke="${color}" stroke-width="6"
            stroke-linecap="round" stroke-dasharray="${c.toFixed(1)}" stroke-dashoffset="${offset.toFixed(1)}"/>
        </svg>
        <div class="zs-seo-ring-num" style="color:${color}">${s}</div>
      </div>`;
  }

  function getHomeSectionMeta(secKey) {
    return HOME_SECTION_CATALOG.find(x => x.key === secKey) || {
      key: secKey,
      title: secKey,
      sub: 'Ana sayfa içerik bölümü',
      badge: 'Bölüm',
      icon: '▣',
      accent: '#2dd4bf',
      defaultVisible: true
    };
  }

  function ensureNormalizedHomeSections() {
    S.store = S.store || {};
    S.store.home = S.store.home || {};
    const home = S.store.home;
    const validKeys = HOME_SECTION_CATALOG.map(x => x.key);

    const rawOrder = Array.isArray(home.section_order) ? home.section_order : [];
    let order = [];
    rawOrder.forEach(k => {
      if (validKeys.includes(k) && !order.includes(k)) order.push(k);
    });

    if (!order.length) {
      order = validKeys.slice();
    } else {
      const missing = validKeys.filter(k => !order.includes(k));
      if (missing.length) {
        let anchorIdx = order.indexOf('builder_blocks');
        if (anchorIdx < 0) anchorIdx = order.indexOf('cta');
        if (anchorIdx >= 0) {
          order.splice(anchorIdx, 0, ...missing);
        } else {
          order = order.concat(missing);
        }
      }
    }

    const rawVis = (home.section_visibility && typeof home.section_visibility === 'object') ? home.section_visibility : {};
    const vis = {};
    HOME_SECTION_CATALOG.forEach(meta => {
      const k = meta.key;
      if (Object.prototype.hasOwnProperty.call(rawVis, k)) {
        vis[k] = Boolean(rawVis[k]);
      } else if (k === 'google_reviews' && S.store.google_reviews && Object.prototype.hasOwnProperty.call(S.store.google_reviews, 'show_on_home')) {
        vis[k] = Boolean(S.store.google_reviews.show_on_home);
      } else if (k === 'site_audit' && S.store.site_audit && Object.prototype.hasOwnProperty.call(S.store.site_audit, 'show_on_home')) {
        vis[k] = Boolean(S.store.site_audit.show_on_home);
      } else {
        vis[k] = Boolean(meta.defaultVisible);
      }
    });

    home.section_order = order;
    home.section_visibility = vis;
    return { order, vis };
  }

  function openHomeSectionEditor(secKey) {
    switch (secKey) {
      case 'hero':
        S.pages.subTab = 'hero';
        return openUnifiedPageEditor('anasayfa', 'content');
      case 'intro':
      case 'brand_scene':
        S.pages.subTab = 'intro';
        return openUnifiedPageEditor('anasayfa', 'content');
      case 'process':
        S.pages.subTab = 'process';
        return openUnifiedPageEditor('anasayfa', 'content');
      case 'services':
        return openUnifiedPageEditor('hizmetler', 'content');
      case 'projects_showcase':
        return openUnifiedPageEditor('projeler', 'content');
      case 'clients_strip':
        S.pages.subTab = 'clients';
        return openUnifiedPageEditor('hakkimizda', 'content');
      case 'google_reviews':
        return navigateTo('google_reviews');
      case 'site_audit':
        S.tabs.growth_tools = 'site_audit';
        return navigateTo('growth_tools');
      case 'quote_wizard':
        S.tabs.growth_tools = 'quote_wizard';
        return navigateTo('growth_tools');
      case 'latest_blog':
        S.blog.mode = 'list';
        return navigateTo('blog');
      case 'builder_blocks':
        return openUnifiedPageEditor('anasayfa', 'builder');
      case 'cta':
        S.tabs.menus = 'cta';
        return navigateTo('menus');
      default:
        return openUnifiedPageEditor('anasayfa', 'content');
    }
  }

  function renderHomeSectionCardsHtml(order, vis) {
    let activeStepCounter = 0;
    return `
      <div class="zs-hsec-list" id="zsHomeSecList">
        ${order.map((sk, idx) => {
          const meta = getHomeSectionMeta(sk);
          const isVisible = vis[sk] !== false;
          if (isVisible) activeStepCounter++;
          const badgeNum = isVisible ? String(activeStepCounter).padStart(2, '0') : '—';
          return `
            <div class="zs-hsec-card ${isVisible ? '' : 'is-hidden-sec'}" draggable="true" data-hsec-card="${esc(sk)}" data-hsec-idx="${idx}">
              <div class="zs-hsec-left">
                <span class="zs-hsec-grip" title="Basılı tutarak sürükleyip bırakın">⋮⋮</span>
                <span class="zs-hsec-num" title="${isVisible ? ('Ana sayfada ' + activeStepCounter + '. görünür bölüm') : 'Şu an gizli'}">${esc(badgeNum)}</span>
                <span class="zs-hsec-icon" style="border-color:${esc(meta.accent)}44">${meta.icon}</span>
                <div class="zs-hsec-meta">
                  <div class="zs-hsec-title-row">
                    <span class="zs-hsec-title">${esc(meta.title)}</span>
                    <span class="zs-pill zs-pill-cat" style="font-size:10.5px;padding:2px 7px">${esc(meta.badge)}</span>
                    ${!isVisible ? `<span class="zs-pill zs-pill-draft" style="font-size:10.5px;padding:2px 7px">Gizli</span>` : ''}
                  </div>
                  <div class="zs-hsec-sub">${esc(meta.sub)}</div>
                </div>
              </div>

              <div class="zs-hsec-controls">
                <select class="zs-select zs-hsec-pos-sel" data-hsec-pos="${idx}" title="Doğrudan sıraya taşı">
                  ${order.map((_, optIdx) => `<option value="${optIdx}" ${optIdx === idx ? 'selected' : ''}>${optIdx + 1}. Sıra</option>`).join('')}
                </select>

                <div class="zs-hsec-move-group" role="group" aria-label="Bölümü taşı">
                  <button type="button" class="zs-hsec-move-btn" data-hsec-jump="${idx}:top" ${idx === 0 ? 'disabled' : ''} title="En Üste Taşı">⤒</button>
                  <button type="button" class="zs-hsec-move-btn" data-hsec-step="${idx}:-1" ${idx === 0 ? 'disabled' : ''} title="Bir Üste Taşı">↑</button>
                  <button type="button" class="zs-hsec-move-btn" data-hsec-step="${idx}:1" ${idx === order.length - 1 ? 'disabled' : ''} title="Bir Alta Taşı">↓</button>
                  <button type="button" class="zs-hsec-move-btn" data-hsec-jump="${idx}:bottom" ${idx === order.length - 1 ? 'disabled' : ''} title="En Alta Taşı">⤓</button>
                </div>

                <label class="zs-switch" style="margin:0;min-width:74px" title="Ana sayfada göster veya gizle">
                  <input type="checkbox" data-hvis="${esc(sk)}" data-hsec-vis="${esc(sk)}" ${isVisible ? 'checked' : ''}/>
                  <span style="font-size:12px">${isVisible ? 'Yayında' : 'Gizli'}</span>
                </label>

                <button type="button" class="zs-btn zs-btn-sm" data-hsec-edit="${esc(sk)}" title="Bu bölümün metin/görsel içeriğini düzenle">✎ Düzenle</button>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  function bindHomeSectionCardsEvents(containerEl, onChanged) {
    if (!containerEl) return;
    const triggerChange = () => {
      updateSidebarBadges();
      if (typeof onChanged === 'function') onChanged();
    };

    const moveItemToIndex = (fromIdx, toIdx) => {
      const { order } = ensureNormalizedHomeSections();
      if (fromIdx < 0 || fromIdx >= order.length) return;
      const clampedTo = Math.max(0, Math.min(order.length - 1, toIdx));
      if (fromIdx === clampedTo) return;
      const [moved] = order.splice(fromIdx, 1);
      order.splice(clampedTo, 0, moved);
      triggerChange();
    };

    // Position <select>
    containerEl.querySelectorAll('[data-hsec-pos]').forEach(sel => {
      sel.onchange = e => {
        const fromIdx = Number(sel.getAttribute('data-hsec-pos'));
        const toIdx = Number(e.target.value);
        moveItemToIndex(fromIdx, toIdx);
      };
    });

    // Step ↑ / ↓
    containerEl.querySelectorAll('[data-hsec-step]').forEach(btn => {
      btn.onclick = () => {
        const [idx, dir] = btn.getAttribute('data-hsec-step').split(':').map(Number);
        moveItemToIndex(idx, idx + dir);
      };
    });

    // Jump Top / Bottom
    containerEl.querySelectorAll('[data-hsec-jump]').forEach(btn => {
      btn.onclick = () => {
        const [rawIdx, target] = btn.getAttribute('data-hsec-jump').split(':');
        const idx = Number(rawIdx);
        const { order } = ensureNormalizedHomeSections();
        moveItemToIndex(idx, target === 'top' ? 0 : order.length - 1);
      };
    });

    // Visibility toggle
    containerEl.querySelectorAll('[data-hsec-vis]').forEach(chk => {
      chk.onchange = () => {
        const { vis } = ensureNormalizedHomeSections();
        const sk = chk.getAttribute('data-hsec-vis');
        vis[sk] = chk.checked;
        triggerChange();
      };
    });

    // Edit content jump
    containerEl.querySelectorAll('[data-hsec-edit]').forEach(btn => {
      btn.onclick = () => {
        const sk = btn.getAttribute('data-hsec-edit');
        openHomeSectionEditor(sk);
      };
    });

    // HTML5 Drag & Drop on main cards
    let dragFromIdx = null;
    const cards = containerEl.querySelectorAll('[data-hsec-card]');
    cards.forEach(card => {
      card.addEventListener('dragstart', e => {
        if (e.target && e.target.closest('select, button, input, label')) {
          e.preventDefault();
          return;
        }
        dragFromIdx = Number(card.getAttribute('data-hsec-idx'));
        card.classList.add('is-dragging');
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
          try { e.dataTransfer.setData('text/plain', String(dragFromIdx)); } catch (_) {}
        }
      });

      card.addEventListener('dragover', e => {
        if (dragFromIdx === null) return;
        e.preventDefault();
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
        const rect = card.getBoundingClientRect();
        const isTopHalf = (e.clientY - rect.top) < (rect.height / 2);
        card.classList.toggle('drag-over-top', isTopHalf);
        card.classList.toggle('drag-over-bottom', !isTopHalf);
      });

      card.addEventListener('dragleave', () => {
        card.classList.remove('drag-over-top', 'drag-over-bottom');
      });

      card.addEventListener('drop', e => {
        e.preventDefault();
        const wasTop = card.classList.contains('drag-over-top');
        card.classList.remove('drag-over-top', 'drag-over-bottom');
        if (dragFromIdx === null) return;
        const targetIdx = Number(card.getAttribute('data-hsec-idx'));
        let destIdx = wasTop ? targetIdx : (targetIdx + 1);
        if (dragFromIdx < destIdx) destIdx -= 1;
        const from = dragFromIdx;
        dragFromIdx = null;
        moveItemToIndex(from, destIdx);
      });

      card.addEventListener('dragend', () => {
        dragFromIdx = null;
        cards.forEach(c => c.classList.remove('is-dragging', 'drag-over-top', 'drag-over-bottom'));
      });
    });
  }

  function buildNotificationItems() {
    const items = [];
    const unreadMsgs = Array.isArray(S.store?.messages)
      ? S.store.messages.filter(m => !m.read).length
      : Number(S.summary?.messages_unread || 0);
    if (unreadMsgs > 0) {
      items.push({
        icon: '✉️',
        badge: `${unreadMsgs} YENİ`,
        tone: 'urgent',
        title: `${unreadMsgs} Okunmamış Proje & Teklif Talebi`,
        sub: 'İletişim, Teklif Sihirbazı veya Site Analiz aracından gelen yeni müşteri mesajları.',
        run: () => { S.messagesFilter = 'unread'; navigateTo('messages'); }
      });
    }

    const dueCrm = Number(S.crmSummary?.due_followups || 0);
    if (dueCrm > 0) {
      items.push({
        icon: '⏰',
        badge: `${dueCrm} TAKİP`,
        tone: 'warn',
        title: `${dueCrm} Müşteri Geri Dönüş Hatırlatması Geldi`,
        sub: 'Satış hunisinde (CRM) bugün veya geçmiş tarihli aranacak potansiyel müşteriler.',
        run: () => { S.messagesMode = 'kanban'; S.messagesFilter = 'all'; navigateTo('messages'); }
      });
    }

    const unseenPortalFb = Number(S.clientTrackersSummary?.unseen_feedback_count || 0);
    if (unseenPortalFb > 0) {
      items.push({
        icon: '✅',
        badge: `${unseenPortalFb} PORTAL`,
        tone: 'ok',
        title: `Proje Takip Portalında ${unseenPortalFb} Yeni Müşteri Yanıtı`,
        sub: 'Müşterilerin ilettiği aşama onayı veya revize notlarını inceleyin.',
        run: () => { S.editingTracker = null; navigateTo('client_portal'); }
      });
    }

    const expRenewals = Number(S.renewalsSummary?.expiring_30_count || 0);
    const overRenewals = Number(S.renewalsSummary?.overdue_count || 0);
    if (expRenewals > 0) {
      items.push({
        icon: '📆',
        badge: overRenewals > 0 ? `${overRenewals} GECİKTİ` : `${expRenewals} YAKLAŞTI`,
        tone: overRenewals > 0 ? 'urgent' : 'warn',
        title: `Hosting & Domain Yenileme Radarı (${expRenewals} Hizmet)`,
        sub: '30 gün içinde süresi dolacak veya geciken müşteri yenilemelerini görüntüleyin.',
        run: () => { S.renewalsFilter = 'expiring'; S.editingRenewal = null; navigateTo('renewals'); }
      });
    }

    const upcomingBookings = Number(S.bookingsSummary?.upcoming_count || 0);
    if (upcomingBookings > 0) {
      items.push({
        icon: '📅',
        badge: `${upcomingBookings} RANDEVU`,
        tone: 'info',
        title: `${upcomingBookings} Planlanmış Online Keşif Görüşmesi`,
        sub: '15 dakikalık Google Meet / WhatsApp / Telefon ön görüşme takvimini açın.',
        run: () => { S.tabs.growth_tools = 'booking'; navigateTo('growth_tools'); }
      });
    }

    const utSum = S.uptime?.summary || {};
    const utDown = Number(utSum.down_count || 0);
    const utSsl = Number(utSum.ssl_expiring_count || 0);
    if (utDown > 0 || utSsl > 0) {
      items.push({
        icon: '🛰️',
        badge: utDown > 0 ? `${utDown} ACİL` : `${utSsl} SSL`,
        tone: utDown > 0 ? 'urgent' : 'warn',
        title: utDown > 0 ? `${utDown} Müşteri Sitesinde Erişim Sorunu` : `${utSsl} Sitenin SSL Süresi Yaklaşıyor`,
        sub: 'Ajans Uptime & SSL Kontrol Kulesinden anlık durumları inceleyin.',
        run: () => navigateTo('uptime_tower')
      });
    }

    const pendComments = Array.isArray(S.blogComments)
      ? S.blogComments.filter(c => (c.status || 'pending') === 'pending').length
      : 0;
    if (pendComments > 0) {
      items.push({
        icon: '💬',
        badge: `${pendComments} BEKLİYOR`,
        tone: 'warn',
        title: `${pendComments} Okuyucu Yorumu Onay Bekliyor`,
        sub: 'Blog makalelerine gelen yeni soru ve yorumları denetleyin.',
        run: () => { S.blog.mode = 'comments'; navigateTo('blog'); }
      });
    }

    const newsCnt = Array.isArray(S.newsBotQueue) ? S.newsBotQueue.length : 0;
    if (newsCnt > 0) {
      items.push({
        icon: '📰',
        badge: `${newsCnt} TASLAK`,
        tone: 'info',
        title: `Google News Otopilotu: ${newsCnt} Hazır Haber`,
        sub: 'Filigranlı kapak görseli hazırlanmış taslakları inceleyip yayınlayın.',
        run: () => { S.blog.mode = 'news_bot'; navigateTo('blog'); }
      });
    }

    const approvedProps = Array.isArray(S.proposals)
      ? S.proposals.filter(p => p.status === 'approved').length
      : 0;
    if (approvedProps > 0) {
      items.push({
        icon: '🤝',
        badge: `${approvedProps} ONAY`,
        tone: 'ok',
        title: `${approvedProps} Online Müşteri Teklifi Onaylandı`,
        sub: 'Müşteriler tarafından dijital olarak onaylanan teklifleri görüntüleyin.',
        run: () => { S.proposalsFilter = 'approved'; S.editingProposal = null; navigateTo('proposals'); }
      });
    }

    const unusedFilesCnt = isSuperAdmin() ? Number(S.unusedFilesSummary?.unused_count || 0) : 0;
    if (unusedFilesCnt > 0) {
      items.push({
        icon: '🧹',
        badge: `${unusedFilesCnt} DOSYA`,
        tone: 'warn',
        title: `Bağlantısız / Kullanılmayan ${unusedFilesCnt} Eski Dosya`,
        sub: 'Stüdyo v5.2 ile bağlantısı olmayan gereksiz dosyaları inceleyip temizleyin.',
        run: () => runAdminJump('performance:unused_files')
      });
    }

    if (isSuperAdmin() && S.otaSummary?.client_update_available) {
      items.push({
        icon: '🚀',
        badge: `v${S.otaSummary.client_remote_version || 'YENİ'}`,
        tone: 'ok',
        title: `Yeni Stüdyo OTA Güncellemesi Hazır (v${S.otaSummary.client_remote_version || ''})`,
        sub: 'Merkezi Hub üzerinde imzalı yeni çekirdek sürümü yayınlandı; tek tıkla güncelleyin.',
        run: () => runAdminJump('ota_hub:client')
      });
    }

    if (S.store?.maintenance?.enabled) {
      items.push({
        icon: '🚧',
        badge: 'AKTİF',
        tone: 'warn',
        title: 'Bakım Modu (503) Şu An Açık',
        sub: 'Ziyaretçiler "Çok Yakında" ekranını görüyor; kapatmak için tıklayın.',
        run: () => { S.tabs.appearance = 'maintenance'; navigateTo('appearance'); }
      });
    }

    return items;
  }

  function refreshNotificationsBell() {
    const cntEl = document.getElementById('zsNotifCount');
    const dropEl = document.getElementById('zsNotifDropdown');
    if (!cntEl || !dropEl) return;

    const items = buildNotificationItems();
    const totalCount = items.length;
    if (totalCount > 0) {
      cntEl.style.display = 'inline-flex';
      cntEl.textContent = String(totalCount);
    } else {
      cntEl.style.display = 'none';
      cntEl.textContent = '0';
    }

    const catsCount = Array.isArray(S.blogCats?.items) && S.blogCats.items.length
      ? S.blogCats.items.length
      : (Array.isArray(S.store?.blog_categories) ? S.store.blog_categories.length : 10);

    dropEl.innerHTML = `
      <div class="zs-notif-head">
        <div>
          <strong>🔔 Stüdyo v5.2 Bildirim Merkezi</strong>
          <span>${totalCount > 0 ? `${totalCount} aktif bildirim ve hızlı aksiyon` : 'Tüm sistemler sorunsuz çalışıyor'}</span>
        </div>
        ${S.bootMs ? `<span class="zs-badge-pill" style="font-size:10px;padding:2px 8px">⚡ ${S.bootMs} ms</span>` : ''}
      </div>
      ${totalCount === 0 ? `
        <div class="zs-notif-empty">
          <strong>✓ Bekleyen kritik uyarı bulunmuyor</strong>
          <p>Form talepleri, kırık link radarı, SSL kulesi ve yorum kuyruğu temiz durumda.</p>
        </div>
      ` : `
        <div class="zs-notif-list">
          ${items.map((it, idx) => `
            <button type="button" class="zs-notif-item is-${esc(it.tone || 'info')}" data-notif-idx="${idx}">
              <span class="zs-notif-ico">${it.icon}</span>
              <span class="zs-notif-body">
                <span class="zs-notif-top">
                  <strong>${esc(it.title)}</strong>
                  <em class="zs-notif-tag">${esc(it.badge)}</em>
                </span>
                <small>${esc(it.sub)}</small>
              </span>
            </button>
          `).join('')}
        </div>
      `}
      <div class="zs-notif-foot">
        <button type="button" class="zs-btn zs-btn-sm" id="zsNotifJumpCatsBtn">📂 Kategoriler (${catsCount})</button>
        <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="zsNotifJumpReportBtn">📄 A4 PDF Rapor</button>
      </div>
    `;

    dropEl.querySelectorAll('[data-notif-idx]').forEach(btn => {
      btn.onclick = () => {
        const idx = Number(btn.getAttribute('data-notif-idx'));
        dropEl.classList.remove('open');
        dropEl.style.display = 'none';
        document.getElementById('zsNotifBtn')?.setAttribute('aria-expanded', 'false');
        if (items[idx] && typeof items[idx].run === 'function') {
          items[idx].run();
        }
      };
    });
    dropEl.querySelector('#zsNotifJumpCatsBtn')?.addEventListener('click', () => {
      dropEl.classList.remove('open');
      dropEl.style.display = 'none';
      document.getElementById('zsNotifBtn')?.setAttribute('aria-expanded', 'false');
      navigateTo('blog_categories');
    });
    dropEl.querySelector('#zsNotifJumpReportBtn')?.addEventListener('click', () => {
      dropEl.classList.remove('open');
      dropEl.style.display = 'none';
      document.getElementById('zsNotifBtn')?.setAttribute('aria-expanded', 'false');
      S.tabs.analytics = 'report';
      navigateTo('analytics');
    });
  }

  function updateSidebarBadges() {
    document.querySelectorAll('#zsNav .zs-nav-count').forEach(el => el.remove());
    refreshNotificationsBell();
  }

  async function copyTextSafe(text, successMsg) {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
      } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
      }
      toast(successMsg || 'Panoya kopyalandı!');
    } catch (_) {
      toast('Kopyalama başarısız oldu, metni seçip Ctrl+C yapabilirsiniz.', true);
    }
  }

  function buildSmartHashtags(category, title, lang) {
    const lg = lang || 'tr';
    const tags = ['#Zeynart'];
    const hay = ((category || '') + ' ' + (title || '')).toLowerCase();
    if (lg === 'en') {
      if (hay.includes('web') || hay.includes('site') || hay.includes('design')) tags.push('#WebDesign', '#DigitalStudio');
      if (hay.includes('brand') || hay.includes('logo') || hay.includes('graphic')) tags.push('#Branding', '#GraphicDesign');
      if (hay.includes('software') || hay.includes('erp') || hay.includes('crm')) tags.push('#CustomSoftware', '#DigitalTransformation');
      if (hay.includes('hosting') || hay.includes('server') || hay.includes('cloud')) tags.push('#WebHosting', '#CloudTech');
      if (tags.length < 3) tags.push('#DigitalAgency', '#TechInsights', '#UIUX');
      return Array.from(new Set(tags)).slice(0, 6).join(' ');
    }
    if (lg === 'de') {
      if (hay.includes('web') || hay.includes('seite') || hay.includes('design')) tags.push('#Webdesign', '#Digitalagentur');
      if (hay.includes('marke') || hay.includes('logo') || hay.includes('grafik')) tags.push('#CorporateDesign', '#Markenidentität');
      if (hay.includes('software') || hay.includes('erp') || hay.includes('crm')) tags.push('#Softwareentwicklung', '#Digitalisierung');
      if (tags.length < 3) tags.push('#Webentwicklung', '#Technologie', '#Design');
      return Array.from(new Set(tags)).slice(0, 6).join(' ');
    }
    if (lg === 'ru') {
      if (hay.includes('веб') || hay.includes('сайт') || hay.includes('web')) tags.push('#ВебДизайн', '#РазработкаСайтов');
      if (hay.includes('бренд') || hay.includes('лого') || hay.includes('дизайн')) tags.push('#Брендинг', '#ГрафическийДизайн');
      if (tags.length < 3) tags.push('#ДиджиталАгентство', '#Технологии', '#IT');
      return Array.from(new Set(tags)).slice(0, 6).join(' ');
    }
    if (hay.includes('web') || hay.includes('site')) tags.push('#WebTasarım', '#KurumsalWeb');
    if (hay.includes('grafik') || hay.includes('logo') || hay.includes('kimlik')) tags.push('#GrafikTasarım', '#KurumsalKimlik');
    if (hay.includes('yazılım') || hay.includes('yazilim') || hay.includes('erp') || hay.includes('crm')) tags.push('#ÖzelYazılım', '#DijitalDönüşüm');
    if (hay.includes('mobil') || hay.includes('ios') || hay.includes('android')) tags.push('#MobilUygulama');
    if (hay.includes('hosting') || hay.includes('sunucu') || hay.includes('gb') || hay.includes('mb')) tags.push('#Hosting', '#Teknoloji');
    if (hay.includes('ticaret') || hay.includes('mağaza')) tags.push('#ETicaret');
    if (hay.includes('seo') || hay.includes('google') || hay.includes('yapay zeka')) tags.push('#SEO', '#DijitalPazarlama');
    if (tags.length < 3) tags.push('#DijitalAjans', '#Teknoloji', '#Tasarım');
    return Array.from(new Set(tags)).slice(0, 6).join(' ');
  }

  function buildSocialShareCaption(card, tone) {
    const lg = card.lang || 'tr';
    const cleanTitle = (card.resolved_title || '').replace(/\s*[|—-]\s*ZEYNART.*$/i, '').trim() || card.resolved_title;
    const desc = (card.resolved_desc || '').trim();
    const hashtags = buildSmartHashtags(card.category, cleanTitle, lg);
    const ctaMap = {
      tr: { wa: '👉 İnceleyin:', hook: 'Detayları incelemek için bağlantıya göz atın 👇', corp: 'Detaylı inceleme ve profesyonel çözümlerimiz için:' },
      en: { wa: '👉 Read more:', hook: 'Explore the full article & insights below 👇', corp: 'Read the full guide and discover our digital solutions:' },
      de: { wa: '👉 Jetzt lesen:', hook: 'Alle Details & Einblicke finden Sie hier 👇', corp: 'Erfahren Sie mehr über unsere digitalen Lösungen:' },
      ru: { wa: '👉 Подробнее:', hook: 'Читайте подробнее по ссылке ниже 👇', corp: 'Подробный обзор и профессиональные цифровые решения:' }
    };
    const c = ctaMap[lg] || ctaMap.tr;
    if (tone === 'whatsapp') {
      return `✨ *${cleanTitle}*\n\n${desc}\n\n${c.wa} ${card.canonical_url}`;
    }
    if (tone === 'hook') {
      return `🚀 ${cleanTitle}\n\n${desc}\n\n${c.hook}\n🔗 ${card.canonical_url}\n\n${hashtags}`;
    }
    return `📌 ${cleanTitle}\n\n${desc}\n\n${c.corp}\n🌐 ${card.canonical_url}\n\n${hashtags}`;
  }

  /* ======================== ADMIN-ONLY SOCIAL SHARE, IG/STORY GRAPHIC & OG PREVIEW STUDIO ======================== */
  async function openSocialShareStudio(opts) {
    const kind = opts?.kind === 'blog' ? 'blog' : 'page';
    const targetKey = String(opts?.targetKey || '');
    const isUnsavedDraft = Boolean(opts?.unsaved);
    const onSaved = typeof opts?.onSaved === 'function' ? opts.onSaved : null;
    let activeLang = String(opts?.lang || 'tr');
    let activeStudioTab = opts?.initialTab || (kind === 'blog' ? 'ig_graphic' : 'og_link');

    const modal = document.getElementById('zsSocialModal');
    const body = document.getElementById('zsSocialModalBody');
    const titleEl = document.getElementById('zsSocialModalTitle');
    if (!modal || !body) return;

    modal.classList.add('open');
    if (titleEl) {
      titleEl.textContent = kind === 'blog'
        ? '📸 Blog — Instagram 1080×1080, Story 1080×1920 & Sosyal Medya Stüdyosu'
        : '📣 Sayfa — Sosyal Medya Görsel & Paylaşım Kartı Stüdyosu';
    }
    body.innerHTML = `<div style="padding:36px;text-align:center;color:var(--text-muted)">Sosyal medya görselleri ve hazır gönderi metinleri hazırlanıyor...</div>`;

    let card;
    try {
      const res = await api('social_card_inspect', {
        kind,
        target_key: targetKey,
        lang: activeLang,
        draft: opts?.draft || {}
      });
      card = res.social_card;
    } catch (err) {
      body.innerHTML = `<div style="padding:24px;color:#fca5a5">${esc(err.message)}</div>`;
      return;
    }

    let activeTone = 'corp';
    const igState = {
      format: opts?.igFormat === 'story' ? 'story' : 'square',
      template: 'editorial_glass',
      eyebrow: card.category || 'ZEYNART JOURNAL',
      title: card.raw_headline || card.resolved_title || '',
      excerpt: card.resolved_desc || '',
      cta_badge: '',
      bg_path: card.og_image || card.fallback_image || '',
      captionPlatform: 'instagram',
      lastSavedMediaUrl: ''
    };

    const buildIgRenderUrl = (fmtOverride, downloadMode, outFmt) => {
      const p = new URLSearchParams();
      p.set('action', 'social_graphic_render');
      p.set('csrf_token', CFG.csrfToken || '');
      p.set('format', fmtOverride || igState.format);
      p.set('template', igState.template);
      if (kind === 'blog' && targetKey) p.set('slug', targetKey);
      p.set('eyebrow', igState.eyebrow);
      p.set('title', igState.title);
      p.set('excerpt', igState.excerpt);
      if (igState.cta_badge) p.set('cta_badge', igState.cta_badge);
      if (igState.bg_path) p.set('bg_path', igState.bg_path);
      p.set('out', outFmt || 'png');
      if (downloadMode) p.set('download', '1');
      p.set('_t', String(Date.now()));
      return CFG.apiUrl + '?' + p.toString();
    };

    const syncIgFromCard = () => {
      igState.eyebrow = card.category || 'ZEYNART JOURNAL';
      igState.title = card.raw_headline || card.resolved_title || '';
      igState.excerpt = card.resolved_desc || '';
      igState.bg_path = card.og_image || card.fallback_image || '';
    };

    const renderModalContent = () => {
      const captionText = buildSocialShareCaption(card, activeTone);
      const encUrl = encodeURIComponent(card.canonical_url);
      const encTitle = encodeURIComponent(card.resolved_title);
      const encWa = encodeURIComponent(buildSocialShareCaption(card, 'whatsapp'));
      const encMedia = encodeURIComponent(card.card_image_url);
      const pack = card.captions_pack || {};
      const takeaways = Array.isArray(pack.takeaways) ? pack.takeaways : [];
      const hashtags = Array.isArray(pack.hashtags) ? pack.hashtags : [];
      const platformTextMap = {
        instagram: pack.instagram || buildSocialShareCaption(card, 'hook'),
        linkedin: pack.linkedin || buildSocialShareCaption(card, 'corp'),
        story: pack.story || buildSocialShareCaption(card, 'whatsapp'),
        x_post: pack.x_post || buildSocialShareCaption(card, 'corp')
      };
      const activePlatformText = platformTextMap[igState.captionPlatform] || platformTextMap.instagram;

      const langChoices = [
        { code: 'tr', label: '🇹🇷 Türkçe (/)' },
        { code: 'en', label: '🇬🇧 English (/en/)' },
        { code: 'de', label: '🇩🇪 Deutsch (/de/)' },
        { code: 'ru', label: '🇷🇺 Русский (/ru/)' }
      ];

      body.innerHTML = `
        <div class="zs-ig-top-bar">
          <div class="zs-tabs" style="margin:0">
            <button type="button" class="zs-tab ${activeStudioTab === 'ig_graphic' ? 'active' : ''}" data-soc-main-tab="ig_graphic">
              📸 Instagram (1080×1080) &amp; Story (1080×1920) Görsel + Hazır Metin Paketi
            </button>
            <button type="button" class="zs-tab ${activeStudioTab === 'og_link' ? 'active' : ''}" data-soc-main-tab="og_link">
              🔗 Link Önizleme Kartı (OpenGraph 1200×630) &amp; Hızlı Paylaş
            </button>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            ${langChoices.map(lc => `
              <button type="button" class="zs-btn zs-btn-sm ${activeLang === lc.code ? 'zs-btn-primary' : ''}" data-soc-lang="${lc.code}" ${isUnsavedDraft && lc.code !== 'tr' ? 'disabled title="Diğer dillerde paylaşmak için önce yazıyı kaydedin"' : ''}>${lc.label}</button>
            `).join('')}
          </div>
        </div>

        ${activeStudioTab === 'ig_graphic' ? `
          <div class="zs-ig-studio-grid">
            <!-- LEFT: 1080x1080 / 1080x1920 GRAPHIC GENERATOR -->
            <div class="zs-ig-stage-col">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                <div class="zs-view-mode-group">
                  <button type="button" class="zs-view-mode-btn ${igState.format === 'square' ? 'active' : ''}" data-ig-fmt="square">◼ Instagram Kare (1080×1080)</button>
                  <button type="button" class="zs-view-mode-btn ${igState.format === 'story' ? 'active' : ''}" data-ig-fmt="story">📱 Story / Reels Kapak (1080×1920)</button>
                </div>
                <span class="zs-pill zs-pill-pub">${igState.format === 'story' ? '1080 × 1920 px HD' : '1080 × 1080 px HD'}</span>
              </div>

              <div class="zs-ig-preview-stage ${igState.format === 'story' ? 'is-story' : 'is-square'}">
                <img id="igLiveGraphicImg" src="${esc(buildIgRenderUrl(igState.format, false, 'jpg'))}" alt="Instagram & Story Önizleme"/>
              </div>

              <div class="zs-ig-dl-bar">
                <a class="zs-btn zs-btn-primary zs-btn-sm" id="igDlSquareBtn" href="${esc(buildIgRenderUrl('square', true, 'png'))}">
                  ⬇️ 1080×1080 PNG İndir
                </a>
                <a class="zs-btn zs-btn-primary zs-btn-sm" id="igDlStoryBtn" href="${esc(buildIgRenderUrl('story', true, 'png'))}">
                  ⬇️ 1080×1920 Story PNG İndir
                </a>
                <button type="button" class="zs-btn zs-btn-ai zs-btn-sm" id="igSaveToUploadsBtn">
                  ☁️ Medya Kütüphanesine (.webp) Kaydet
                </button>
                ${igState.lastSavedMediaUrl ? `
                  <button type="button" class="zs-btn zs-btn-sm" id="igOpenInCanvasBtn" title="Katmanlı Görsel Kanvas Editöründe Aç">
                    🎨 Kanvas'ta Düzenle
                  </button>
                ` : ''}
              </div>

              <div style="padding:13px 14px;border-radius:13px;background:rgba(6,14,19,.86);border:1px solid var(--border-subtle);margin-top:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
                  <strong style="font-size:12.5px;color:#5eead4">🎨 Tasarım Şablonu &amp; Görsel Üzerindeki Metinler</strong>
                  <button type="button" class="zs-btn zs-btn-sm" id="igRefreshBtn" style="padding:3px 9px;font-size:11px">↻ Görseli Yenile</button>
                </div>
                <div class="zs-grid-2" style="margin-bottom:8px">
                  <div class="zs-field" style="margin:0">
                    <label>Görsel Şablonu</label>
                    <select id="igTemplateSel" class="zs-select">
                      <option value="editorial_glass" ${igState.template === 'editorial_glass' ? 'selected' : ''}>✦ Editoryal Buzlu Cam + Kapak</option>
                      <option value="full_photo_cinema" ${igState.template === 'full_photo_cinema' ? 'selected' : ''}>🎬 Tam Ekran Sinematik Fotoğraf</option>
                      <option value="tech_emerald" ${igState.template === 'tech_emerald' ? 'selected' : ''}>💎 Koyu Kurumsal Teknoloji Sahnesi</option>
                    </select>
                  </div>
                  <div class="zs-field" style="margin:0">
                    <label>Üst Kategori Rozeti</label>
                    <input id="igEyebrowInp" class="zs-input" value="${esc(igState.eyebrow)}"/>
                  </div>
                </div>
                <div class="zs-field" style="margin-bottom:8px">
                  <label>Görsel Üzerindeki Manşet Başlık</label>
                  <input id="igTitleInp" class="zs-input" value="${esc(igState.title)}"/>
                </div>
                <div class="zs-grid-2">
                  <div class="zs-field" style="margin:0">
                    <label>Alt Çağrı Rozeti (CTA)</label>
                    <input id="igCtaInp" class="zs-input" placeholder="Otomatik (DEVAMI ZEYNART.COM'DA →)" value="${esc(igState.cta_badge)}"/>
                  </div>
                  <div class="zs-field" style="margin:0">
                    <label>Arka Plan Görseli</label>
                    <div class="zs-input-group">
                      <input id="igBgInp" class="zs-input" value="${esc(igState.bg_path)}"/>
                      <button type="button" class="zs-btn zs-btn-sm" id="igPickBgBtn">Seç</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- RIGHT: MULTI-PLATFORM READY-TO-PASTE CAPTIONS & TAKEAWAYS -->
            <div style="display:grid;gap:12px;align-content:start">
              <div style="padding:14px;border-radius:13px;background:rgba(6,14,19,.86);border:1px solid var(--border-subtle)">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                  <strong style="font-size:13.5px;color:#f1fbfc">✍️ Kopyala-Yapıştır Sosyal Medya Metin Paketi</strong>
                  <span style="font-size:11px;color:var(--accent-bright)">H2 Özetli &amp; Akıllı Hashtag</span>
                </div>

                <div class="zs-ig-platform-tabs" style="margin-bottom:10px">
                  <button type="button" class="zs-btn zs-btn-sm ${igState.captionPlatform === 'instagram' ? 'zs-btn-primary' : ''}" data-ig-plat="instagram">📸 Instagram Gönderi</button>
                  <button type="button" class="zs-btn zs-btn-sm ${igState.captionPlatform === 'linkedin' ? 'zs-btn-primary' : ''}" data-ig-plat="linkedin">💼 LinkedIn Makale</button>
                  <button type="button" class="zs-btn zs-btn-sm ${igState.captionPlatform === 'story' ? 'zs-btn-primary' : ''}" data-ig-plat="story">📱 Story / WhatsApp</button>
                  <button type="button" class="zs-btn zs-btn-sm ${igState.captionPlatform === 'x_post' ? 'zs-btn-primary' : ''}" data-ig-plat="x_post">𝕏 X (Twitter)</button>
                </div>

                <textarea id="igPackCaptionBox" class="zs-textarea" style="min-height:210px;font-size:12.5px;line-height:1.58;margin-bottom:10px">${esc(activePlatformText)}</textarea>

                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <button type="button" class="zs-btn zs-btn-primary" style="flex:1" id="igCopyPackCaptionBtn">
                    📋 Seçili Gönderi Metnini + Hashtagleri Kopyala
                  </button>
                  <button type="button" class="zs-btn zs-btn-ai" id="igCopyCanonLinkBtn">
                    🔗 Makale Linkini Kopyala
                  </button>
                </div>
              </div>

              ${takeaways.length > 0 ? `
                <div style="padding:13px 14px;border-radius:13px;background:rgba(6,14,19,.82);border:1px solid var(--border-subtle)">
                  <strong style="display:block;font-size:12px;color:#5eead4;margin-bottom:7px">✦ Yazıdan Otomatik Çıkarılan Öne Çıkan Başlıklar (H2/H3)</strong>
                  <ul style="margin:0;padding-left:18px;font-size:12px;color:var(--text-main);line-height:1.6">
                    ${takeaways.map(tk => `<li>${esc(tk)}</li>`).join('')}
                  </ul>
                </div>
              ` : ''}

              ${hashtags.length > 0 ? `
                <div style="padding:12px 14px;border-radius:13px;background:rgba(6,14,19,.82);border:1px solid var(--border-subtle)">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px">
                    <strong style="font-size:12px;color:var(--text-muted)">🏷️ Akıllı Sektörel Hashtag Seti (Tıkla Ekle / Kopyala)</strong>
                    <button type="button" class="zs-btn zs-btn-sm" id="igCopyAllTagsBtn" style="padding:2px 8px;font-size:11px">Tümünü Kopyala</button>
                  </div>
                  <div style="display:flex;gap:6px;flex-wrap:wrap">
                    ${hashtags.map(tg => `<button type="button" class="zs-token-chip" data-ig-tag="${esc(tg)}">${esc(tg)}</button>`).join('')}
                  </div>
                </div>
              ` : ''}
            </div>
          </div>
        ` : `
          <div class="zs-social-studio-grid">
            <!-- LEFT: LIVE LINK PASTE PREVIEW CARD + CUSTOM OG EDITOR -->
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap">
                <strong style="font-size:13px;color:#5eead4">👁 Link Yapıştırıldığında Görünecek Kart (Canlı)</strong>
                <span class="zs-pill zs-pill-pub">✓ 1200×630 JPG${card.card_kb ? ` · ${card.card_kb} KB` : ''}</span>
              </div>

              <div class="zs-og-preview-card" style="margin-bottom:14px">
                <div class="zs-og-preview-media">
                  <img id="socPrevImg" src="${esc(card.card_image_preview)}" alt=""/>
                  <span class="zs-og-preview-badge">WhatsApp · LinkedIn · FB · X Uyumlu</span>
                </div>
                <div class="zs-og-preview-body">
                  <div class="zs-og-preview-domain">${esc(card.domain || 'zeynart.com')}</div>
                  <div class="zs-og-preview-title" id="socPrevTitle">${esc(card.resolved_title)}</div>
                  <div class="zs-og-preview-desc" id="socPrevDesc">${esc(card.resolved_desc)}</div>
                </div>
              </div>

              <div style="padding:14px;border-radius:13px;background:rgba(6,14,19,.82);border:1px solid var(--border-subtle)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;flex-wrap:wrap">
                  <strong style="font-size:12.5px">🎨 Bu İçeriğin Sosyal Kartını Özelleştir (İsteğe Bağlı)</strong>
                  <span style="font-size:11px;color:var(--text-dim)">Boş bırakılırsa kapak &amp; SEO verisi otomatik kullanılır</span>
                </div>

                <div class="zs-field" style="margin-bottom:10px">
                  <label>Sosyal Medya Başlığı (og:title)</label>
                  <input id="socOgTitleInp" class="zs-input" placeholder="${esc(card.base_title)}" value="${esc(card.og_title || '')}"/>
                </div>

                <div class="zs-field" style="margin-bottom:10px">
                  <label>Sosyal Medya Açıklaması (og:description)</label>
                  <textarea id="socOgDescInp" class="zs-textarea" style="min-height:62px" placeholder="${esc(card.base_desc)}">${esc(card.og_desc || '')}</textarea>
                </div>

                <div class="zs-field" style="margin-bottom:12px">
                  <label>Özel Sosyal Paylaşım Görseli (Otomatik 1200×630 JPG Üretilir)</label>
                  <div class="zs-input-group">
                    <input id="socOgImgInp" class="zs-input" placeholder="Varsayılan: ${esc(card.fallback_image)}" value="${esc(card.og_image || '')}"/>
                    <button type="button" class="zs-btn zs-btn-sm" id="socPickImgBtn">Kütüphane</button>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="socUploadImgBtn">Yükle</button>
                    <input type="file" id="socUploadFileInp" accept="image/*" style="display:none"/>
                  </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
                  <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <a href="https://developers.facebook.com/tools/debug/?q=${encUrl}" target="_blank" rel="noopener" class="zs-btn zs-btn-sm" title="Daha önce paylaşılmış bir linkin Meta/WhatsApp önbelleğini temizler">↻ FB/WhatsApp Önbellek ↗</a>
                    <a href="https://www.linkedin.com/post-inspector/inspect/${encUrl}" target="_blank" rel="noopener" class="zs-btn zs-btn-sm" title="LinkedIn kart önbelleğini yeniler">↻ LinkedIn Denetle ↗</a>
                  </div>
                  <button type="button" class="zs-btn zs-btn-primary zs-btn-sm" id="socSaveMetaBtn">
                    ✓ ${isUnsavedDraft ? 'Editöre Uygula' : 'Sosyal Kartı Kaydet'}
                  </button>
                </div>
              </div>
            </div>

            <!-- RIGHT: 1-CLICK ADMIN SHARE BUTTONS + CAPTION & HASHTAG GENERATOR -->
            <div style="display:grid;gap:14px">
              <div style="padding:14px;border-radius:13px;background:rgba(6,14,19,.82);border:1px solid var(--border-subtle)">
                <label style="display:block;font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:6px">🔗 Paylaşılacak Kalıcı Bağlantı (Link Yapıştırma Hazır)</label>
                <div class="zs-input-group">
                  <input id="socCanonUrlInp" class="zs-input" readonly value="${esc(card.canonical_url)}" style="font-family:Consolas,monospace;font-size:12.5px;color:var(--accent-bright)"/>
                  <button type="button" class="zs-btn zs-btn-primary zs-btn-sm" id="socCopyUrlBtn">🔗 Linki Kopyala</button>
                </div>
              </div>

              <div style="padding:14px;border-radius:13px;background:rgba(6,14,19,.82);border:1px solid var(--border-subtle)">
                <strong style="display:block;font-size:13px;margin-bottom:10px">🚀 Tek Tıkla Sosyal Medyada Paylaş (Yalnızca Admin)</strong>
                <div class="zs-share-grid">
                  <a class="zs-share-btn" href="https://api.whatsapp.com/send?text=${encWa}" target="_blank" rel="noopener">
                    <span class="zs-share-dot" style="background:#25D366">W</span><span>WhatsApp'ta Gönder</span>
                  </a>
                  <a class="zs-share-btn" href="https://www.linkedin.com/sharing/share-offsite/?url=${encUrl}" target="_blank" rel="noopener">
                    <span class="zs-share-dot" style="background:#0A66C2">in</span><span>LinkedIn'de Paylaş</span>
                  </a>
                  <a class="zs-share-btn" href="https://www.facebook.com/sharer/sharer.php?u=${encUrl}" target="_blank" rel="noopener">
                    <span class="zs-share-dot" style="background:#1877F2">f</span><span>Facebook'ta Paylaş</span>
                  </a>
                  <a class="zs-share-btn" href="https://twitter.com/intent/tweet?text=${encTitle}&url=${encUrl}" target="_blank" rel="noopener">
                    <span class="zs-share-dot" style="background:#111827;border:1px solid rgba(255,255,255,.2)">𝕏</span><span>X (Twitter)'da Paylaş</span>
                  </a>
                  <a class="zs-share-btn" href="https://t.me/share/url?url=${encUrl}&text=${encTitle}" target="_blank" rel="noopener">
                    <span class="zs-share-dot" style="background:#0088cc">✈</span><span>Telegram'da Paylaş</span>
                  </a>
                  <a class="zs-share-btn" href="https://pinterest.com/pin/create/button/?url=${encUrl}&media=${encMedia}&description=${encTitle}" target="_blank" rel="noopener">
                    <span class="zs-share-dot" style="background:#E60023">P</span><span>Pinterest'te Pinle</span>
                  </a>
                  <a class="zs-share-btn" href="mailto:?subject=${encTitle}&body=${encWa}">
                    <span class="zs-share-dot" style="background:#0d9488">✉</span><span>E-posta ile Paylaş</span>
                  </a>
                  <a class="zs-share-btn" href="${esc(card.card_image_preview)}" download="zeynart-sosyal-kart-1200x630.jpg">
                    <span class="zs-share-dot" style="background:#7c3aed">🖼</span><span>1200×630 JPG İndir</span>
                  </a>
                </div>
              </div>

              <div style="padding:14px;border-radius:13px;background:rgba(6,14,19,.82);border:1px solid var(--border-subtle)">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                  <strong style="font-size:13px">✍ Hazır Gönderi Metni &amp; Hashtag (Instagram / LinkedIn / FB)</strong>
                  <div style="display:flex;gap:5px;flex-wrap:wrap">
                    <button type="button" class="zs-btn zs-btn-sm ${activeTone === 'corp' ? 'zs-btn-ai' : ''}" data-soc-tone="corp" style="padding:4px 8px;font-size:11px">Kurumsal</button>
                    <button type="button" class="zs-btn zs-btn-sm ${activeTone === 'hook' ? 'zs-btn-ai' : ''}" data-soc-tone="hook" style="padding:4px 8px;font-size:11px">Merak Uyandıran</button>
                    <button type="button" class="zs-btn zs-btn-sm ${activeTone === 'whatsapp' ? 'zs-btn-ai' : ''}" data-soc-tone="whatsapp" style="padding:4px 8px;font-size:11px">WhatsApp</button>
                  </div>
                </div>
                <textarea id="socCaptionBox" class="zs-textarea" style="min-height:132px;font-size:12.5px;margin-bottom:10px">${esc(captionText)}</textarea>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <button type="button" class="zs-btn zs-btn-ai zs-btn-sm" style="flex:1" id="socCopyCaptionBtn">📋 Gönderi Metnini + Linki Kopyala</button>
                </div>
              </div>
            </div>
          </div>
        `}
      `;

      body.querySelectorAll('[data-soc-main-tab]').forEach(tb => {
        tb.onclick = () => {
          activeStudioTab = tb.getAttribute('data-soc-main-tab') || 'ig_graphic';
          renderModalContent();
        };
      });

      body.querySelectorAll('[data-soc-lang]').forEach(lb => {
        lb.onclick = async () => {
          const nextLang = lb.getAttribute('data-soc-lang');
          if (!nextLang || nextLang === activeLang) return;
          activeLang = nextLang;
          lb.disabled = true;
          lb.textContent = 'Çevriliyor...';
          try {
            const r = await api('social_card_inspect', {
              kind,
              target_key: targetKey,
              lang: activeLang,
              draft: opts?.draft || {}
            });
            card = r.social_card;
            syncIgFromCard();
            renderModalContent();
          } catch (err) {
            toast(err.message, true);
            renderModalContent();
          }
        };
      });

      if (activeStudioTab === 'ig_graphic') {
        const syncIgInputsAndRefresh = () => {
          igState.template = document.getElementById('igTemplateSel')?.value || 'editorial_glass';
          igState.eyebrow = document.getElementById('igEyebrowInp')?.value.trim() || 'ZEYNART JOURNAL';
          igState.title = document.getElementById('igTitleInp')?.value.trim() || card.raw_headline || card.resolved_title;
          igState.cta_badge = document.getElementById('igCtaInp')?.value.trim() || '';
          igState.bg_path = document.getElementById('igBgInp')?.value.trim() || '';

          const imgEl = document.getElementById('igLiveGraphicImg');
          if (imgEl) imgEl.src = buildIgRenderUrl(igState.format, false, 'jpg');
          const sqBtn = document.getElementById('igDlSquareBtn');
          if (sqBtn) sqBtn.href = buildIgRenderUrl('square', true, 'png');
          const stBtn = document.getElementById('igDlStoryBtn');
          if (stBtn) stBtn.href = buildIgRenderUrl('story', true, 'png');
        };

        let igDeb = null;
        ['igEyebrowInp', 'igTitleInp', 'igCtaInp', 'igBgInp'].forEach(id => {
          document.getElementById(id)?.addEventListener('input', () => {
            clearTimeout(igDeb);
            igDeb = setTimeout(syncIgInputsAndRefresh, 320);
          });
        });
        document.getElementById('igTemplateSel')?.addEventListener('change', syncIgInputsAndRefresh);
        document.getElementById('igRefreshBtn')?.addEventListener('click', syncIgInputsAndRefresh);

        body.querySelectorAll('[data-ig-fmt]').forEach(fb => {
          fb.onclick = () => {
            igState.template = document.getElementById('igTemplateSel')?.value || igState.template;
            igState.eyebrow = document.getElementById('igEyebrowInp')?.value.trim() || igState.eyebrow;
            igState.title = document.getElementById('igTitleInp')?.value.trim() || igState.title;
            igState.cta_badge = document.getElementById('igCtaInp')?.value.trim() || '';
            igState.bg_path = document.getElementById('igBgInp')?.value.trim() || '';
            igState.format = fb.getAttribute('data-ig-fmt') === 'story' ? 'story' : 'square';
            renderModalContent();
          };
        });

        document.getElementById('igPickBgBtn')?.addEventListener('click', () => {
          openMediaPicker(url => {
            const bgInp = document.getElementById('igBgInp');
            if (bgInp) bgInp.value = url;
            syncIgInputsAndRefresh();
          });
        });

        document.getElementById('igSaveToUploadsBtn')?.addEventListener('click', async function () {
          syncIgInputsAndRefresh();
          const btn = this;
          btn.disabled = true;
          btn.textContent = '⏳ Medya Kütüphanesine Kaydediliyor...';
          try {
            const res = await api('social_graphic_save_media', {
              format: igState.format,
              template: igState.template,
              slug: kind === 'blog' ? targetKey : slugifyClient(igState.title || 'sayfa'),
              title: igState.title,
              eyebrow: igState.eyebrow,
              excerpt: igState.excerpt,
              cta_badge: igState.cta_badge,
              bg_path: igState.bg_path
            });
            igState.lastSavedMediaUrl = res.url || '';
            rememberMediaMtimes([{ url: res.url, mtime: res.mtime }]);
            toast(res.message);
            renderModalContent();
          } catch (err) {
            toast(err.message, true);
            btn.disabled = false;
            btn.textContent = '☁️ Medya Kütüphanesine (.webp) Kaydet';
          }
        });

        document.getElementById('igOpenInCanvasBtn')?.addEventListener('click', () => {
          if (!igState.lastSavedMediaUrl) return;
          modal.classList.remove('open');
          openImageCanvasStudio({
            url: igState.lastSavedMediaUrl,
            name: igState.lastSavedMediaUrl.split('/').pop() || 'instagram.webp',
            alt: igState.title
          });
        });

        body.querySelectorAll('[data-ig-plat]').forEach(pb => {
          pb.onclick = () => {
            igState.captionPlatform = pb.getAttribute('data-ig-plat') || 'instagram';
            const box = document.getElementById('igPackCaptionBox');
            if (box) box.value = platformTextMap[igState.captionPlatform] || '';
            body.querySelectorAll('[data-ig-plat]').forEach(x => x.classList.toggle('zs-btn-primary', x === pb));
          };
        });

        document.getElementById('igCopyPackCaptionBtn')?.addEventListener('click', () => {
          const val = document.getElementById('igPackCaptionBox')?.value || '';
          copyTextSafe(val, 'Gönderi metni ve hashtagler panoya kopyalandı!');
        });
        document.getElementById('igCopyCanonLinkBtn')?.addEventListener('click', () => {
          copyTextSafe(card.canonical_url, 'Makale kalıcı bağlantısı panoya kopyalandı!');
        });
        document.getElementById('igCopyAllTagsBtn')?.addEventListener('click', () => {
          copyTextSafe(hashtags.join(' '), 'Tüm sektörel hashtagler panoya kopyalandı!');
        });
        body.querySelectorAll('[data-ig-tag]').forEach(ch => {
          ch.onclick = () => {
            const tg = ch.getAttribute('data-ig-tag') || '';
            const box = document.getElementById('igPackCaptionBox');
            if (box && tg && !box.value.includes(tg)) {
              box.value = box.value.trimEnd() + ' ' + tg;
            }
            copyTextSafe(tg, `${tg} kopyalandı!`);
          };
        });
        return;
      }

      // OG Link Tab Bindings
      document.getElementById('socCopyUrlBtn').onclick = () => copyTextSafe(card.canonical_url, 'Sayfa bağlantısı kopyalandı! Sosyal medyaya doğrudan yapıştırabilirsiniz.');
      document.getElementById('socCopyCaptionBtn').onclick = () => copyTextSafe(document.getElementById('socCaptionBox').value, 'Gönderi metni, hashtagler ve link panoya kopyalandı!');

      body.querySelectorAll('[data-soc-tone]').forEach(b => {
        b.onclick = () => {
          activeTone = b.getAttribute('data-soc-tone');
          document.getElementById('socCaptionBox').value = buildSocialShareCaption(card, activeTone);
          body.querySelectorAll('[data-soc-tone]').forEach(x => x.classList.toggle('zs-btn-ai', x === b));
        };
      });

      const ogTitleInp = document.getElementById('socOgTitleInp');
      const ogDescInp = document.getElementById('socOgDescInp');
      const ogImgInp = document.getElementById('socOgImgInp');

      const syncLiveText = () => {
        const nextT = ogTitleInp.value.trim() || card.base_title;
        const nextD = ogDescInp.value.trim() || card.base_desc;
        document.getElementById('socPrevTitle').textContent = nextT;
        document.getElementById('socPrevDesc').textContent = nextD;
      };
      ogTitleInp.oninput = syncLiveText;
      ogDescInp.oninput = syncLiveText;

      const refreshServerPreview = async imgVal => {
        try {
          const r = await api('social_card_inspect', {
            kind,
            target_key: targetKey,
            lang: activeLang,
            draft: Object.assign({}, opts?.draft || {}, {
              og_title: ogTitleInp.value.trim(),
              og_desc: ogDescInp.value.trim(),
              og_image: imgVal
            })
          });
          card = r.social_card;
          syncIgFromCard();
          renderModalContent();
        } catch (err) {
          toast(err.message, true);
        }
      };

      document.getElementById('socPickImgBtn').onclick = () => {
        openMediaPicker(url => {
          ogImgInp.value = url;
          refreshServerPreview(url);
        });
      };

      const fileInp = document.getElementById('socUploadFileInp');
      document.getElementById('socUploadImgBtn').onclick = () => fileInp.click();
      fileInp.onchange = async () => {
        if (!fileInp.files.length) return;
        try {
          const fd = new FormData();
          fd.append('file', fileInp.files[0]);
          fd.append('optimize_webp', '1');
          toast('Görsel yükleniyor ve 1200×630 JPG sosyal kart hazırlanıyor...');
          const upRes = await apiUpload(fd);
          const up = upRes.uploaded[0];
          if (up) {
            ogImgInp.value = up.url;
            await refreshServerPreview(up.url);
          }
        } catch (err) {
          toast(err.message, true);
        }
        fileInp.value = '';
      };

      document.getElementById('socSaveMetaBtn').onclick = async () => {
        const newOgTitle = ogTitleInp.value.trim();
        const newOgDesc = ogDescInp.value.trim();
        const newOgImg = ogImgInp.value.trim();

        if (isUnsavedDraft) {
          if (onSaved) onSaved({ og_title: newOgTitle, og_desc: newOgDesc, og_image: newOgImg });
          await refreshServerPreview(newOgImg);
          toast('Sosyal kart ayarları açık olan editöre aktarıldı.');
          return;
        }

        try {
          const saveRes = await api('social_meta_save', {
            kind,
            target_key: targetKey,
            lang: activeLang,
            og_title: newOgTitle,
            og_desc: newOgDesc,
            og_image: newOgImg
          });
          card = saveRes.social_card;
          syncIgFromCard();
          if (saveRes.store) S.store = saveRes.store;
          if (onSaved) onSaved({ og_title: newOgTitle, og_desc: newOgDesc, og_image: newOgImg, post: saveRes.post });
          toast(saveRes.message);
          renderModalContent();
        } catch (err) {
          toast(err.message, true);
        }
      };
    };

    renderModalContent();
  }

  /* ======================== GLOBAL MEDIA PICKER MODAL ======================== */
  let pickerState = {
    folder: 'all',
    typeFilter: 'all',
    sort: 'newest',
    search: '',
    page: 1,
    allowMultiple: false,
    selectedItems: [],
    onSelect: null
  };

  async function openMediaPicker(onSelectCallback, opts) {
    const o = opts || {};
    pickerState.onSelect = onSelectCallback;
    pickerState.allowMultiple = Boolean(o.allowMultiple);
    pickerState.selectedItems = [];
    pickerState.page = 1;
    const badge = document.getElementById('zsMediaPickerModeBadge');
    if (badge) {
      badge.textContent = pickerState.allowMultiple ? 'ÇOKLU SEÇİM & YÜKLEME AKTİF' : 'CANLI WEBP MOTORU';
    }
    const modal = document.getElementById('zsMediaModal');
    modal.classList.add('open');
    await renderMediaPickerBody();
    if (o.initialFiles && o.initialFiles.length) {
      await handlePickerUpload(o.initialFiles);
    }
  }

  function finishPickerSelection(items) {
    const list = (Array.isArray(items) ? items : [items]).filter(Boolean);
    if (!list.length) return;
    document.getElementById('zsMediaModal')?.classList.remove('open');
    revokeActivePreviewBlobs();
    if (typeof pickerState.onSelect === 'function') {
      if (pickerState.allowMultiple) {
        list.forEach(it => pickerState.onSelect(it.url, it.alt || it.name || ''));
      } else {
        pickerState.onSelect(list[0].url, list[0].alt || list[0].name || '');
      }
    }
  }

  async function renderMediaPickerBody() {
    const body = document.getElementById('zsMediaModalBody');
    if (!body) return;
    body.innerHTML = `
      <div class="zs-upload-studio-hero compact" id="zsPickerDrop">
        <input type="file" id="zsPickerFileInput" multiple accept="image/*" style="display:none"/>
        <div class="zs-upload-hero-left">
          <div class="zs-upload-orb" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          </div>
          <div class="zs-upload-hero-copy">
            <div class="zs-upload-hero-badges">
              <span class="zs-kbd-pill">⚡ Otomatik WebP</span>
              <span class="zs-kbd-pill">📋 Ctrl + V Yapıştır</span>
              <span class="zs-kbd-pill">Çoklu Yükleme Barı</span>
            </div>
            <h3>Görselleri Sürükleyip Bırakın veya Bilgisayardan Çoklu Seçin</h3>
            <p>Aynı anda 5, 10 veya daha fazla resmi seçebilirsiniz; canlı hız ve WebP sıkıştırma barı ile anında yüklenir.</p>
          </div>
        </div>
        <div class="zs-upload-hero-cta">
          <button type="button" class="zs-btn zs-btn-primary zs-btn-sm" id="zsPickerBrowseBtn">+ Görsel Seç</button>
        </div>
      </div>

      <div id="zsPickerUploadDeckSlot"></div>

      <div style="display:flex;gap:10px;margin:16px 0 12px;flex-wrap:wrap;align-items:center">
        <div class="zs-media-type-pills" id="zsPickerTypePills">
          <button type="button" class="zs-type-pill ${pickerState.typeFilter === 'all' ? 'active' : ''}" data-ptype="all">Tümü</button>
          <button type="button" class="zs-type-pill ${pickerState.typeFilter === 'uploads' ? 'active' : ''}" data-ptype="uploads">Yüklenenler</button>
          <button type="button" class="zs-type-pill ${pickerState.typeFilter === 'images' ? 'active' : ''}" data-ptype="images">Tema</button>
          <button type="button" class="zs-type-pill ${pickerState.typeFilter === 'webp' ? 'active' : ''}" data-ptype="webp">WebP / AVIF</button>
        </div>
        <div style="display:flex;gap:8px;flex:1;justify-content:flex-end;min-width:260px;flex-wrap:wrap">
          <select id="zsPickerFolder" class="zs-select" style="max-width:220px"></select>
          <input id="zsPickerSearch" class="zs-input" style="max-width:240px" placeholder="Dosya adı veya alt etiketi ara..." value="${esc(pickerState.search)}"/>
          <button type="button" class="zs-btn zs-btn-sm" id="zsPickerSearchBtn">Ara</button>
        </div>
      </div>

      <div id="zsPickerCatalogSlot">
        <div style="padding:30px;text-align:center;color:var(--text-muted)">Görsel arşivi yükleniyor...</div>
      </div>
      <div id="zsPickerSelectionSlot"></div>
    `;

    const drop = document.getElementById('zsPickerDrop');
    const browseBtn = document.getElementById('zsPickerBrowseBtn');
    const fileInp = document.getElementById('zsPickerFileInput');
    drop.onclick = ev => {
      if (ev.target === browseBtn || browseBtn.contains(ev.target)) return;
      fileInp.click();
    };
    browseBtn.onclick = ev => { ev.stopPropagation(); fileInp.click(); };
    drop.ondragover = e => { e.preventDefault(); drop.classList.add('drag-over'); };
    drop.ondragleave = () => drop.classList.remove('drag-over');
    drop.ondrop = async e => {
      e.preventDefault();
      drop.classList.remove('drag-over');
      if (e.dataTransfer.files.length) await handlePickerUpload(e.dataTransfer.files);
    };
    fileInp.onchange = async () => {
      if (fileInp.files.length) await handlePickerUpload(fileInp.files);
      fileInp.value = '';
    };

    body.querySelectorAll('[data-ptype]').forEach(btn => {
      btn.onclick = () => {
        pickerState.typeFilter = btn.getAttribute('data-ptype') || 'all';
        pickerState.page = 1;
        body.querySelectorAll('[data-ptype]').forEach(b => b.classList.toggle('active', b === btn));
        refreshPickerCatalogOnly();
      };
    });

    document.getElementById('zsPickerFolder').onchange = e => {
      pickerState.folder = e.target.value;
      pickerState.page = 1;
      refreshPickerCatalogOnly();
    };
    const triggerSearch = () => {
      pickerState.search = document.getElementById('zsPickerSearch').value.trim();
      pickerState.page = 1;
      refreshPickerCatalogOnly();
    };
    document.getElementById('zsPickerSearchBtn').onclick = triggerSearch;
    document.getElementById('zsPickerSearch').onkeydown = e => { if (e.key === 'Enter') { e.preventDefault(); triggerSearch(); } };

    await refreshPickerCatalogOnly();
  }

  function paintPickerSelectionBar() {
    const slot = document.getElementById('zsPickerSelectionSlot');
    if (!slot) return;
    if (!pickerState.allowMultiple || pickerState.selectedItems.length === 0) {
      slot.innerHTML = '';
      return;
    }
    const cnt = pickerState.selectedItems.length;
    slot.innerHTML = `
      <div class="zs-picker-selection-bar">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="zs-pill zs-pill-pub">${cnt} Görsel Seçildi</span>
          <button type="button" class="zs-btn zs-btn-sm" id="zsPickerClearMultiBtn">Seçimi Temizle</button>
        </div>
        <button type="button" class="zs-btn zs-btn-primary" id="zsPickerConfirmMultiBtn">✓ Seçilen ${cnt} Görseli Ekle →</button>
      </div>
    `;
    document.getElementById('zsPickerClearMultiBtn').onclick = () => {
      pickerState.selectedItems = [];
      refreshPickerSelectionHighlights();
    };
    document.getElementById('zsPickerConfirmMultiBtn').onclick = () => {
      finishPickerSelection(pickerState.selectedItems);
    };
  }

  function refreshPickerSelectionHighlights() {
    const grid = document.getElementById('zsPickerGrid');
    const selUrls = new Set(pickerState.selectedItems.map(x => x.url));
    if (grid) {
      grid.classList.toggle('has-bulk', selUrls.size > 0 || pickerState.allowMultiple);
      grid.querySelectorAll('[data-pick-url]').forEach(card => {
        const u = card.getAttribute('data-pick-url');
        const checked = selUrls.has(u);
        card.classList.toggle('bulk-checked', checked);
        const chk = card.querySelector('.zs-media-check');
        if (chk) chk.textContent = checked ? '✓' : '';
      });
    }
    paintPickerSelectionBar();
  }

  async function refreshPickerCatalogOnly() {
    const slot = document.getElementById('zsPickerCatalogSlot');
    if (!slot) return;
    try {
      const data = await api('media_list', {
        folder: pickerState.folder,
        type_filter: pickerState.typeFilter,
        sort: pickerState.sort,
        search: pickerState.search,
        page: pickerState.page,
        per_page: 36
      });
      rememberMediaMtimes(data.items || []);
      const folderSel = document.getElementById('zsPickerFolder');
      if (folderSel) {
        folderSel.innerHTML = Object.keys(data.folders || {}).map(f =>
          `<option value="${esc(f)}" ${pickerState.folder === f ? 'selected' : ''}>${f === 'all' ? 'Tüm Klasörler' : esc(f)} (${data.folders[f]})</option>`
        ).join('');
      }

      const selUrls = new Set(pickerState.selectedItems.map(x => x.url));
      const recentSet = new Set(S.media.recentUploadedUrls || []);

      slot.innerHTML = (data.items || []).length === 0
        ? `<div style="padding:32px;text-align:center;color:var(--text-muted)">Bu filtreyle eşleşen görsel bulunamadı.</div>`
        : `
          <div class="zs-media-grid ${pickerState.allowMultiple ? 'has-bulk' : ''}" id="zsPickerGrid">
            ${(data.items || []).map(it => {
              const isChecked = selUrls.has(it.url);
              const isNew = recentSet.has(it.url);
              const dimStr = (it.width && it.height) ? `${it.width}×${it.height} · ` : '';
              return `
                <div class="zs-media-card ${isChecked ? 'bulk-checked' : ''}" data-pick-url="${esc(it.url)}" data-pick-alt="${esc(it.alt || humanizeAltFromFilename(it.name))}">
                  ${pickerState.allowMultiple ? `<button type="button" class="zs-media-check" data-picker-chk="1">${isChecked ? '✓' : ''}</button><button type="button" class="zs-media-quick-pick" data-picker-quick="1">+ Ekle</button>` : ''}
                  ${isNew ? `<span class="zs-media-new-badge">YENİ</span>` : ''}
                  <div class="zs-media-thumb"><img src="${esc(resolvePreviewUrl(it.url, it.mtime))}" alt="${esc(it.alt || it.name)}" loading="lazy"/></div>
                  <div class="zs-media-meta">
                    <strong title="${esc(it.name)}">${esc(it.name)}</strong>
                    <span>${dimStr}${esc((it.ext || '').toUpperCase())} · ${fmtBytes(it.size)}</span>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px">
            <span style="font-size:12px;color:var(--text-muted)">Toplam ${data.total} görsel · Sayfa ${data.page} / ${data.total_pages}${pickerState.allowMultiple ? ' · Çoklu seçim açık (Çift tıkla anında ekle)' : ''}</span>
            <div style="display:flex;gap:8px">
              <button type="button" class="zs-btn zs-btn-sm" id="zsPickerPrev" ${data.page <= 1 ? 'disabled' : ''}>← Önceki</button>
              <button type="button" class="zs-btn zs-btn-sm" id="zsPickerNext" ${data.page >= data.total_pages ? 'disabled' : ''}>Sonraki →</button>
            </div>
          </div>
        `;

      const prevB = document.getElementById('zsPickerPrev');
      const nextB = document.getElementById('zsPickerNext');
      if (prevB) prevB.onclick = () => { pickerState.page--; refreshPickerCatalogOnly(); };
      if (nextB) nextB.onclick = () => { pickerState.page++; refreshPickerCatalogOnly(); };

      slot.querySelectorAll('[data-pick-url]').forEach(card => {
        const url = card.getAttribute('data-pick-url');
        const alt = card.getAttribute('data-pick-alt') || '';
        const quickBtn = card.querySelector('[data-picker-quick]');
        if (quickBtn) {
          quickBtn.onclick = ev => {
            ev.preventDefault();
            ev.stopPropagation();
            if (pickerState.selectedItems.length > 0 && pickerState.selectedItems.some(x => x.url === url)) {
              finishPickerSelection(pickerState.selectedItems);
            } else {
              finishPickerSelection([{ url, alt }]);
            }
          };
        }
        card.onclick = ev => {
          if (pickerState.allowMultiple) {
            ev.preventDefault();
            const idx = pickerState.selectedItems.findIndex(x => x.url === url);
            if (idx >= 0) pickerState.selectedItems.splice(idx, 1);
            else pickerState.selectedItems.push({ url, alt });
            refreshPickerSelectionHighlights();
            return;
          }
          finishPickerSelection([{ url, alt }]);
        };
        card.ondblclick = () => {
          finishPickerSelection([{ url, alt }]);
        };
      });

      paintPickerSelectionBar();
    } catch (err) {
      slot.innerHTML = `<div style="color:#fca5a5;padding:20px">${esc(err.message)}</div>`;
    }
  }

  async function handlePickerUpload(files) {
    const deckSlot = document.getElementById('zsPickerUploadDeckSlot');
    const uploaded = await runStudioUploadQueue(files, deckSlot, {
      pickerMode: true,
      allowMultiple: pickerState.allowMultiple,
      onPickSingle: item => finishPickerSelection([item]),
      onPickMultiple: items => finishPickerSelection(items),
      onComplete: async results => {
        pickerState.page = 1;
        await refreshPickerCatalogOnly();
        // If user uploaded just 1 single file and had no other multi-selection queued, celebrate 100% bar briefly then insert automatically!
        if (results.length === 1 && (!pickerState.allowMultiple || pickerState.selectedItems.length === 0)) {
          setTimeout(() => {
            if (document.getElementById('zsMediaModal')?.classList.contains('open')) {
              finishPickerSelection([results[0]]);
            }
          }, 380);
        } else if (results.length > 0 && pickerState.allowMultiple) {
          // Pre-check newly uploaded files in multi-select bar so 1 click inserts all of them!
          results.forEach(r => {
            if (!pickerState.selectedItems.some(x => x.url === r.url)) {
              pickerState.selectedItems.push({ url: r.url, alt: r.alt || humanizeAltFromFilename(r.name) });
            }
          });
          refreshPickerSelectionHighlights();
        }
      }
    });
    return uploaded;
  }

  /* ======================== NAVIGATION, URL STATE & COMMAND PALETTE ======================== */
  const DEFAULT_VIEW_TABS = {
    analytics: 'live',
    menus: 'header',
    appearance: 'theme',
    growth_tools: 'quick_contact',
    seo: 'general',
    social_media: 'accounts',
    performance: 'lscache',
    ota_hub: 'fleet',
    settings: 'identity'
  };
  let isRestoringFromUrl = false;
  let pendingHistoryMode = null;

  function markHistoryPush() {
    if (!isRestoringFromUrl) pendingHistoryMode = 'push';
  }

  function applyLiveAdminBrandState(adminThemeCss, themeBrand, siteCfg) {
    let styleEl = document.getElementById('zsWhiteLabelVars');
    if (typeof adminThemeCss === 'string') {
      if (adminThemeCss.trim() !== '') {
        if (!styleEl) {
          styleEl = document.createElement('style');
          styleEl.id = 'zsWhiteLabelVars';
          document.head.appendChild(styleEl);
        }
        styleEl.textContent = adminThemeCss;
      } else if (styleEl) {
        styleEl.textContent = '';
      }
    }
    const sideTitle = document.getElementById('zsBrandSideTitle');
    if (sideTitle && siteCfg?.name) {
      sideTitle.textContent = siteCfg.name;
    }
    const sideBadge = document.getElementById('zsBrandSideBadge');
    if (sideBadge && themeBrand?.studio_badge_text) {
      sideBadge.textContent = themeBrand.studio_badge_text;
    }
  }

  function buildAdminStateQuery() {
    const params = new URLSearchParams();
    if (!S.view || S.view === 'dashboard') {
      return '';
    }
    if (S.view === 'pages') {
      if (S.pages.mode === 'edit') {
        if (S.pages.activeKey === 'custom:__new__') {
          params.set('new_page', '1');
        } else {
          params.set('edit_page', S.pages.activeKey || 'anasayfa');
          if (S.pages.editorTab && S.pages.editorTab !== 'content') {
            params.set('tab', S.pages.editorTab);
          }
          if (S.pages.subTab) {
            params.set('sub', S.pages.subTab);
          }
        }
      } else {
        params.set('view', 'pages');
        if (S.pages.filter && S.pages.filter !== 'all') {
          params.set('flt', S.pages.filter);
        }
      }
    } else if (S.view === 'blog') {
      if (S.blog.mode === 'edit') {
        if (S.blog.oldSlug) {
          params.set('edit_post', S.blog.oldSlug);
        } else {
          params.set('new_post', '1');
        }
      } else if (S.blog.mode === 'settings') {
        params.set('view', 'blog');
        params.set('tab', 'settings');
      } else if (S.blog.mode === 'news_bot') {
        params.set('view', 'blog');
        params.set('tab', 'news_bot');
      } else if (S.blog.mode === 'seo_links') {
        params.set('view', 'blog');
        params.set('tab', 'seo_links');
      } else if (S.blog.mode === 'authors') {
        params.set('view', 'blog');
        params.set('tab', 'authors');
      } else if (S.blog.mode === 'comments') {
        params.set('view', 'blog');
        params.set('tab', 'comments');
      } else {
        params.set('view', 'blog');
      }
    } else {
      params.set('view', S.view);
      const defTab = DEFAULT_VIEW_TABS[S.view];
      const curTab = S.tabs[S.view];
      if (curTab && ((defTab && curTab !== defTab) || (!defTab && SECTOR_VIEWS[S.view]))) {
        params.set('tab', curTab);
      }
      if (S.view === 'messages') {
        if (S.messagesMode && S.messagesMode !== 'kanban') {
          params.set('mode', S.messagesMode);
        }
        if (S.messagesFilter && S.messagesFilter !== 'all') {
          params.set('flt', S.messagesFilter);
        }
      }
      if (S.view === 'renewals' && S.renewalsFilter && S.renewalsFilter !== 'all') {
        params.set('flt', S.renewalsFilter);
      }
      if (S.view === 'uptime_tower' && S.uptime.filter && S.uptime.filter !== 'all') {
        params.set('flt', S.uptime.filter);
      }
    }
    const qs = params.toString();
    return qs ? ('?' + qs) : '';
  }

  function normalizeTrSearch(str) {
    const map = { 'İ': 'i', 'I': 'i', 'ı': 'i', 'Ş': 's', 'ş': 's', 'Ğ': 'g', 'ğ': 'g', 'Ü': 'u', 'ü': 'u', 'Ö': 'o', 'ö': 'o', 'Ç': 'c', 'ç': 'c' };
    return String(str || '')
      .replace(/[İIıŞşĞğÜüÖöÇç]/g, ch => map[ch] || ch)
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();
  }

  function matchesTrQuery(haystack, query) {
    const q = normalizeTrSearch(query);
    if (!q) return true;
    const h = normalizeTrSearch(haystack);
    return q.split(' ').every(tok => !tok || h.includes(tok));
  }

  function resolveParentNavView(v) {
    if (SECTOR_VIEWS[v] && SECTOR_VIEWS[v].parentNav) return SECTOR_VIEWS[v].parentNav;
    if (v === 'proposals' || v === 'client_portal' || v === 'vcards') return 'messages';
    if (['home', 'builder', 'home_layout', 'services', 'projects', 'about', 'contact'].includes(v)) return 'pages';
    if (v === 'blog_categories') return 'blog';
    if (v === 'google_reviews') return 'seo';
    if (v === 'appearance') return 'menus';
    if (v === 'renewals') return 'uptime_tower';
    if (v === 'performance') return 'settings';
    return v || 'dashboard';
  }

  function resolveActiveNavJumpSpec() {
    const v = S.view || 'dashboard';
    if (v === 'dashboard') return 'dashboard';
    if (v === 'analytics') return S.tabs?.analytics === 'report' ? 'analytics:report' : 'analytics:live';
    if (v === 'messages') return S.messagesMode === 'list' ? 'messages:list' : 'messages:kanban';
    if (v === 'proposals') return (S.editingProposal && !S.editingProposal.id) ? 'proposals:new' : 'proposals';
    if (v === 'client_portal') return 'client_portal';
    if (v === 'vcards') return 'vcards';
    if (v === 'home_layout') return 'home_layout';
    if (v === 'home') return 'pages:edit_home';
    if (v === 'about') return 'pages:edit_about';
    if (v === 'services') return 'pages:edit_services';
    if (v === 'projects') return 'pages:edit_projects';
    if (v === 'contact') return 'pages:edit_contact';
    if (v === 'pages') {
      if (S.pages.mode === 'edit') {
        const ak = String(S.pages.activeKey || '');
        if (ak === 'custom:__new__') return 'pages:new_page';
        if (ak === 'anasayfa') return 'pages:edit_home';
        if (ak === 'hakkimizda') return 'pages:edit_about';
        if (ak === 'hizmetler' || ak.startsWith('service:')) return 'pages:edit_services';
        if (ak === 'projeler') return 'pages:edit_projects';
        if (ak === 'iletisim') return 'pages:edit_contact';
        return 'pages:list';
      }
      if (S.pages.filter === 'trash') return 'pages:trash';
      if (S.pages.filter === 'legal') return 'pages:legal';
      if (S.pages.filter === 'services') return 'pages:edit_services';
      if (S.pages.filter === 'projects') return 'pages:edit_projects';
      return 'pages:list';
    }
    if (v === 'blog_categories') return 'blog_categories';
    if (v === 'blog') {
      if (S.blog.mode === 'edit' && !S.blog.oldSlug) return 'blog:new_post';
      if (S.blog.mode === 'categories') return 'blog_categories';
      if (['news_bot', 'seo_links', 'authors', 'comments'].includes(S.blog.mode)) return 'blog:' + S.blog.mode;
      return 'blog:list';
    }
    if (v === 'media') {
      return S.mediaSubJump === 'upload' ? 'media:upload' : 'media:library';
    }
    if (v === 'seo' || v === 'google_reviews') {
      return 'seo:' + (v === 'google_reviews' ? 'google_reviews' : (S.tabs?.seo || 'general'));
    }
    if (v === 'social_media') {
      return 'social_media:' + (S.tabs?.social_media || 'accounts');
    }
    if (v === 'growth_tools') {
      return 'growth_tools:' + (S.tabs?.growth_tools || 'quick_contact');
    }
    if (v === 'menus') {
      return 'menus:' + (S.tabs?.menus || 'header');
    }
    if (v === 'appearance') {
      return 'appearance:' + (S.tabs?.appearance || 'theme');
    }
    if (v === 'uptime_tower') return 'uptime_tower:all';
    if (v === 'renewals') return (S.editingRenewal && !S.editingRenewal.id) ? 'renewals:new' : 'renewals';
    if (v === 'ota_hub') return 'ota_hub:' + (S.tabs?.ota_hub || 'fleet');
    if (v === 'performance') return S.tabs?.performance === 'unused_files' ? 'performance:unused_files' : 'performance';
    if (v === 'settings') {
      const st = S.tabs?.settings || 'identity';
      if (st === 'performance') return 'performance';
      return 'settings:' + st;
    }
    const curTab = S.tabs?.[v] || DEFAULT_VIEW_TABS[v];
    return curTab ? `${v}:${curTab}` : v;
  }

  function syncSidebarActiveTree() {
    const parentView = resolveParentNavView(S.view || 'dashboard');
    const activeSpec = resolveActiveNavJumpSpec();
    document.querySelectorAll('#zsNav .zs-nav-item-wrap').forEach(wrap => {
      const isAct = wrap.getAttribute('data-nav-wrap') === parentView;
      wrap.classList.toggle('is-active', isAct);
      if (isAct) wrap.classList.remove('is-peek-open');
    });
    document.querySelectorAll('#zsNav .zs-nav-btn').forEach(b => {
      b.classList.toggle('active', b.getAttribute('data-view') === parentView);
    });
    document.querySelectorAll('#zsNav .zs-nav-sub-btn').forEach(sb => {
      sb.classList.toggle('active', sb.getAttribute('data-nav-jump') === activeSpec);
    });
  }

  function applySidebarNavFilter(explicitQuery) {
    const inp = document.getElementById('zsNavFilterInput');
    const clearBtn = document.getElementById('zsNavFilterClear');
    const emptyHint = document.getElementById('zsNavEmptyHint');
    const rawQ = explicitQuery !== undefined ? String(explicitQuery) : (inp ? inp.value : '');
    const q = rawQ.trim();
    if (clearBtn) clearBtn.style.display = q ? 'grid' : 'none';

    const wraps = Array.from(document.querySelectorAll('#zsNav .zs-nav-item-wrap'));
    let visibleWrapCount = 0;

    wraps.forEach(wrap => {
      if (wrap.getAttribute('data-role-hidden') === '1') {
        wrap.style.display = 'none';
        wrap.classList.remove('is-filter-open');
        return;
      }
      const mainBtn = wrap.querySelector('.zs-nav-btn');
      const subBtns = Array.from(wrap.querySelectorAll('.zs-nav-sub-btn'));

      if (!q) {
        wrap.style.display = '';
        wrap.classList.remove('is-filter-open');
        subBtns.forEach(sb => {
          sb.style.display = sb.getAttribute('data-role-hidden') === '1' ? 'none' : '';
        });
        visibleWrapCount++;
        return;
      }

      const mainHay = (mainBtn?.querySelector('span:not(.zs-nav-chev)')?.textContent || mainBtn?.textContent || '') + ' ' + (mainBtn?.getAttribute('data-keywords') || '');
      const parentMatches = matchesTrQuery(mainHay, q);
      let matchingSubCount = 0;

      subBtns.forEach(sb => {
        if (sb.getAttribute('data-role-hidden') === '1') {
          sb.style.display = 'none';
          return;
        }
        const subHay = (sb.textContent || '') + ' ' + (sb.getAttribute('data-keywords') || '');
        if (matchesTrQuery(subHay, q)) {
          matchingSubCount++;
          sb.style.display = '';
        } else {
          sb.style.display = 'none';
        }
      });

      if (matchingSubCount > 0) {
        wrap.style.display = '';
        wrap.classList.add('is-filter-open');
        visibleWrapCount++;
      } else if (parentMatches) {
        wrap.style.display = '';
        subBtns.forEach(sb => {
          sb.style.display = sb.getAttribute('data-role-hidden') === '1' ? 'none' : '';
        });
        const subList = wrap.querySelector('.zs-nav-sub-list');
        const subAllowed = subList && subList.style.display !== 'none';
        wrap.classList.toggle('is-filter-open', Boolean(subAllowed && subBtns.some(sb => sb.getAttribute('data-role-hidden') !== '1')));
        visibleWrapCount++;
      } else {
        wrap.style.display = 'none';
        wrap.classList.remove('is-filter-open');
      }
    });

    document.querySelectorAll('#zsNav .zs-nav-group-label[data-group-header]').forEach(hdr => {
      const grp = hdr.getAttribute('data-group-header');
      const hasVisibleChild = wraps.some(w => w.getAttribute('data-nav-group') === grp && w.style.display !== 'none');
      hdr.style.display = hasVisibleChild ? '' : 'none';
    });

    if (emptyHint) {
      emptyHint.style.display = (q && visibleWrapCount === 0) ? 'grid' : 'none';
    }
  }

  function runAdminJump(spec) {
    const raw = String(spec || '').trim();
    if (!raw) return;
    document.getElementById('zsSidebar')?.classList.remove('open');
    document.getElementById('zsQuickCreateMenu')?.classList.remove('open');
    S.mediaSubJump = '';

    if (raw === 'blog_categories') {
      S.blogCats.editingId = null;
      navigateTo('blog_categories');
      return;
    }
    if (raw === 'home_layout') {
      navigateTo('home_layout');
      return;
    }
    if (raw === 'proposals') {
      S.editingProposal = null;
      navigateTo('proposals');
      return;
    }
    if (raw === 'client_portal') {
      S.editingTracker = null;
      navigateTo('client_portal');
      return;
    }
    if (raw === 'vcards') {
      navigateTo('vcards');
      return;
    }
    if (raw === 'renewals') {
      S.editingRenewal = null;
      navigateTo('renewals');
      return;
    }
    if (raw === 'performance') {
      S.tabs.performance = 'lscache';
      navigateTo('performance');
      return;
    }
    if (!raw.includes(':')) {
      if (raw === 'pages') { S.pages.mode = 'list'; S.pages.filter = 'all'; }
      if (raw === 'blog') S.blog.mode = 'list';
      navigateTo(raw);
      return;
    }

    const [view, sub] = raw.split(':');
    const targetWrap = document.querySelector(`#zsNav .zs-nav-item-wrap[data-nav-wrap="${resolveParentNavView(view)}"]`);
    if (targetWrap) targetWrap.classList.remove('is-collapsed');

    if (view === 'pages') {
      if (sub === 'edit_home') { openUnifiedPageEditor('anasayfa', 'content'); return; }
      if (sub === 'edit_about') { openUnifiedPageEditor('hakkimizda', 'content'); return; }
      if (sub === 'edit_services') { openUnifiedPageEditor('hizmetler', 'content'); return; }
      if (sub === 'edit_projects') { openUnifiedPageEditor('projeler', 'content'); return; }
      if (sub === 'edit_contact') { openUnifiedPageEditor('iletisim', 'content'); return; }
      if (sub === 'new_page') { startNewUnifiedCustomPage(); return; }
      if (sub === 'legal') { S.pages.mode = 'list'; S.pages.filter = 'legal'; S.pages.search = ''; navigateTo('pages'); return; }
      if (sub === 'trash') { S.pages.mode = 'list'; S.pages.filter = 'trash'; S.pages.search = ''; navigateTo('pages'); return; }
      S.pages.mode = 'list';
      S.pages.filter = 'all';
      navigateTo('pages');
      return;
    }

    if (view === 'blog') {
      if (sub === 'new_post') {
        navigateTo('blog', () => startNewBlogPost());
        return;
      }
      if (['news_bot', 'seo_links', 'authors', 'comments', 'settings'].includes(sub)) {
        S.blog.mode = sub;
        navigateTo('blog');
        return;
      }
      S.blog.mode = 'list';
      navigateTo('blog');
      return;
    }

    if (view === 'messages') {
      S.messagesMode = sub === 'list' ? 'list' : 'kanban';
      navigateTo('messages');
      return;
    }

    if (view === 'proposals' && sub === 'new') {
      S.editingProposal = createBlankProposalDraft();
      navigateTo('proposals');
      return;
    }

    if (view === 'renewals' && sub === 'new') {
      S.editingRenewal = { id: '', client_name: '', domain: '', service_type: 'Hosting + Domain', expires_at: '', amount: 0, currency: 'TRY', phone: '', email: '', notes: '', status: 'active' };
      navigateTo('renewals');
      return;
    }

    if (view === 'uptime_tower') {
      S.uptime.filter = 'all';
      navigateTo('uptime_tower');
      return;
    }

    if (view === 'media') {
      S.mediaSubJump = sub === 'upload' ? 'upload' : '';
      navigateTo('media');
      if (sub === 'upload') {
        setTimeout(() => {
          const drop = document.getElementById('mediaMainDrop');
          if (drop) {
            drop.scrollIntoView({ behavior: 'smooth', block: 'center' });
            drop.style.boxShadow = '0 0 0 3px rgba(94, 234, 212, 0.52)';
            setTimeout(() => { drop.style.boxShadow = ''; }, 1400);
          }
        }, 120);
      }
      return;
    }

    if (S.tabs[view] !== undefined || SECTOR_VIEWS[view]) {
      S.tabs[view] = sub;
    }
    navigateTo(view);
  }

  function buildConsolidatedFamilyBarHtml() {
    const v = S.view || 'dashboard';
    if (['messages', 'proposals', 'client_portal', 'vcards'].includes(v)) {
      return `
        <div class="zs-family-switch-bar no-print" data-family-bar="client_suite">
          <div class="zs-family-switch-pills">
            <span class="zs-family-switch-label">Müşteri &amp; Satış Merkezi</span>
            <button type="button" class="zs-family-switch-btn ${v === 'messages' ? 'active' : ''}" data-family-jump="messages:kanban">📥 Talepler &amp; CRM Kanban</button>
            <button type="button" class="zs-family-switch-btn ${v === 'proposals' ? 'active' : ''}" data-family-jump="proposals">💼 Online Müşteri Teklifleri</button>
            <button type="button" class="zs-family-switch-btn ${v === 'client_portal' ? 'active' : ''}" data-family-jump="client_portal">🔐 Proje Takip Portalı</button>
            <button type="button" class="zs-family-switch-btn ${v === 'vcards' ? 'active' : ''}" data-family-jump="vcards">💳 Dijital Kartvizit &amp; QR</button>
          </div>
        </div>
      `;
    }
    if (['menus', 'appearance'].includes(v) && isSuperAdmin()) {
      return `
        <div class="zs-family-switch-bar no-print" data-family-bar="design_suite">
          <div class="zs-family-switch-pills">
            <span class="zs-family-switch-label">Tasarım &amp; Görünüm</span>
            <button type="button" class="zs-family-switch-btn ${v === 'menus' ? 'active' : ''}" data-family-jump="menus:header">🔤 Menüler, Google Fonts &amp; Alt Bant</button>
            <button type="button" class="zs-family-switch-btn ${v === 'appearance' ? 'active' : ''}" data-family-jump="appearance:theme">🎨 Renk Paleti, Duyuru &amp; Bakım Modu</button>
          </div>
        </div>
      `;
    }
    if (['uptime_tower', 'renewals'].includes(v)) {
      return `
        <div class="zs-family-switch-bar no-print" data-family-bar="ops_suite">
          <div class="zs-family-switch-pills">
            <span class="zs-family-switch-label">Altyapı &amp; Süreklilik</span>
            <button type="button" class="zs-family-switch-btn ${v === 'uptime_tower' ? 'active' : ''}" data-family-jump="uptime_tower:all">🛰️ Uptime &amp; SSL Kontrol Kulesi</button>
            <button type="button" class="zs-family-switch-btn ${v === 'renewals' ? 'active' : ''}" data-family-jump="renewals">📆 Hosting, Domain &amp; Yenileme Radarı</button>
          </div>
        </div>
      `;
    }
    if (['performance', 'settings', 'ota_hub'].includes(v) && isSuperAdmin()) {
      const isUnusedTab = v === 'performance' && S.tabs?.performance === 'unused_files';
      const isDbTab = v === 'settings' && S.tabs?.settings === 'database';
      return `
        <div class="zs-family-switch-bar no-print" data-family-bar="sys_suite">
          <div class="zs-family-switch-pills">
            <span class="zs-family-switch-label">Sistem &amp; Performans</span>
            <button type="button" class="zs-family-switch-btn ${v === 'ota_hub' ? 'active' : ''}" data-family-jump="ota_hub:fleet">🚀 Merkezi OTA &amp; Filo (v5.2)</button>
            <button type="button" class="zs-family-switch-btn ${isDbTab ? 'active' : ''}" data-family-jump="settings:database">🗄️ MySQL Veritabanı &amp; Göç</button>
            <button type="button" class="zs-family-switch-btn ${(v === 'settings' && !isDbTab) ? 'active' : ''}" data-family-jump="settings:identity">⚙️ Sistem, Kimlik, Güvenlik &amp; Yedek</button>
            <button type="button" class="zs-family-switch-btn ${(v === 'performance' && !isUnusedTab) ? 'active' : ''}" data-family-jump="performance">⚡ LiteSpeed LSCache &amp; Sayfa Haritası (HIT/MISS)</button>
            <button type="button" class="zs-family-switch-btn ${isUnusedTab ? 'active' : ''}" data-family-jump="performance:unused_files">🧹 Kullanılmayan &amp; Bağlantısız Dosyalar</button>
          </div>
        </div>
      `;
    }
    return '';
  }

  function ensureConsolidatedFamilyBar() {
    if (!ROOT || ROOT.querySelector(':scope > .zs-family-switch-bar')) return;
    const html = buildConsolidatedFamilyBarHtml();
    if (!html) return;
    ROOT.insertAdjacentHTML('afterbegin', html);
    ROOT.querySelectorAll(':scope > .zs-family-switch-bar [data-family-jump]').forEach(btn => {
      btn.onclick = () => runAdminJump(btn.getAttribute('data-family-jump'));
    });
  }

  function syncAdminUrl(fallbackMode) {
    syncSidebarActiveTree();
    if (isRestoringFromUrl) return;
    try {
      const targetUrl = window.location.pathname + buildAdminStateQuery();
      const currentUrl = window.location.pathname + (window.location.search || '');
      const mode = pendingHistoryMode || fallbackMode || 'replace';
      pendingHistoryMode = null;
      if (targetUrl !== currentUrl) {
        if (mode === 'push') {
          window.history.pushState({ zeynartAdmin: 1 }, '', targetUrl);
        } else {
          window.history.replaceState({ zeynartAdmin: 1 }, '', targetUrl);
        }
      }
    } catch (_) {}
  }

  /* ======================== STÜDYO v4.0 — MODÜLER CODE-SPLITTING & 0ms GEÇİŞ MOTORU ======================== */
  const CTX = Object.create(null);
  CTX.chunks = Object.create(null);
  const v4ChunkPromises = Object.create(null);
  const v4LoadedChunks = new Set();
  const V4_ALL_CHUNKS = ['blog', 'media_design', 'pages', 'seo_growth', 'agency_crm', 'ops_system'];
  const VIEW_CHUNK_MAP = {
    dashboard: null,
    analytics: null,
    home_layout: 'pages',
    pages: 'pages',
    builder: 'pages',
    home: 'pages',
    services: 'pages',
    projects: 'pages',
    about: 'pages',
    contact: 'pages',
    blog: 'blog',
    blog_categories: 'blog',
    media: 'media_design',
    menus: 'media_design',
    appearance: 'media_design',
    seo: 'seo_growth',
    google_reviews: 'seo_growth',
    growth_tools: 'seo_growth',
    social_media: 'seo_growth',
    messages: 'agency_crm',
    proposals: 'agency_crm',
    client_portal: 'agency_crm',
    vcards: 'agency_crm',
    uptime_tower: 'ops_system',
    renewals: 'ops_system',
    ota_hub: 'ops_system',
    performance: 'ops_system',
    settings: 'ops_system'
  };
  let v4LastNavMs = 0;

  function updateV4SpeedTelemetry(navMs) {
    if (typeof navMs === 'number' && navMs >= 0) {
      v4LastNavMs = navMs;
    }
    const badge = document.getElementById('zsV4SpeedBadge');
    if (!badge) return;
    const msTxt = v4LastNavMs > 0 ? `${v4LastNavMs < 1 ? v4LastNavMs.toFixed(2) : v4LastNavMs.toFixed(1)} ms` : (S.bootMs ? `${S.bootMs} ms` : '0.4 ms');
    const dbTag = (S.databaseStatus && S.databaseStatus.active_engine && S.databaseStatus.active_engine !== 'json')
      ? ` · 🗄️ ${S.databaseStatus.active_engine === 'mysql' ? 'MySQL' : 'SQLite'}`
      : '';
    badge.textContent = `⚡ v5.2 Core${dbTag} · ${msTxt} · ${v4LoadedChunks.size}/${V4_ALL_CHUNKS.length} Modül Hazır`;
  }
  window.__ZS_UPDATE_TELEMETRY__ = updateV4SpeedTelemetry;

  window.__ZS_REGISTER_CHUNK__ = function (chunkName, factoryFn) {
    if (!chunkName || typeof factoryFn !== 'function') return;
    syncCoreContextExports();
    const exported = factoryFn(CTX) || {};
    CTX.chunks[chunkName] = exported;
    v4LoadedChunks.add(chunkName);
    updateV4SpeedTelemetry();
  };

  function resolveChunkUrl(chunkName) {
    if (CFG.chunks && CFG.chunks[chunkName]) {
      return CFG.chunks[chunkName];
    }
    const admDir = CFG.adminDirName || 'admin';
    return `/${admDir}/chunks/${chunkName}.js`;
  }

  function ensureStudioChunk(chunkName) {
    if (!chunkName) return Promise.resolve(null);
    if (CTX.chunks[chunkName]) {
      return Promise.resolve(CTX.chunks[chunkName]);
    }
    if (v4ChunkPromises[chunkName]) {
      return v4ChunkPromises[chunkName];
    }
    v4ChunkPromises[chunkName] = new Promise((resolve, reject) => {
      const sc = document.createElement('script');
      sc.src = resolveChunkUrl(chunkName);
      sc.async = true;
      sc.onload = () => {
        if (CTX.chunks[chunkName]) {
          resolve(CTX.chunks[chunkName]);
        } else {
          reject(new Error(`Stüdyo v4 modülü kaydedilemedi: ${chunkName}`));
        }
      };
      sc.onerror = () => {
        delete v4ChunkPromises[chunkName];
        reject(new Error(`Stüdyo v4 modülü yüklenemedi: ${chunkName}`));
      };
      document.head.appendChild(sc);
    });
    return v4ChunkPromises[chunkName];
  }

  function prefetchStudioChunk(chunkName) {
    if (!chunkName || CTX.chunks[chunkName] || v4ChunkPromises[chunkName]) return;
    ensureStudioChunk(chunkName).catch(() => {});
  }

  function resolveChunksForView(view) {
    const primary = VIEW_CHUNK_MAP[view] || null;
    const list = primary ? [primary] : [];
    if (view === 'settings' && S.tabs?.settings === 'i18n' && !list.includes('blog')) {
      list.push('blog');
    }
    return list;
  }

  function bindV4IntentPrefetcher() {
    const navEl = document.getElementById('zsNav');
    if (navEl) {
      const onIntent = ev => {
        const btn = ev.target && ev.target.closest ? ev.target.closest('[data-view], [data-nav-jump]') : null;
        if (!btn) return;
        let v = btn.getAttribute('data-view') || '';
        if (!v) {
          const jmp = btn.getAttribute('data-nav-jump') || '';
          v = jmp.split(':')[0] || '';
        }
        resolveChunksForView(v).forEach(prefetchStudioChunk);
      };
      navEl.addEventListener('pointerover', onIntent, { passive: true });
      navEl.addEventListener('focusin', onIntent, { passive: true });
    }

    const warmRemaining = () => {
      const next = V4_ALL_CHUNKS.find(c => !CTX.chunks[c] && !v4ChunkPromises[c]);
      if (!next) return;
      ensureStudioChunk(next).catch(() => {}).finally(() => {
        if (typeof window.requestIdleCallback === 'function') {
          window.requestIdleCallback(warmRemaining, { timeout: 1800 });
        } else {
          setTimeout(warmRemaining, 260);
        }
      });
    };

    setTimeout(() => {
      if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(warmRemaining, { timeout: 1500 });
      } else {
        setTimeout(warmRemaining, 400);
      }
    }, 650);
  }

  // Cross-chunk bridges (0ms when already loaded, automatic lazy-load otherwise)
  function syncMediaSelectionDom() {
    if (CTX.chunks.media_design?.syncMediaSelectionDom) {
      return CTX.chunks.media_design.syncMediaSelectionDom();
    }
  }
  async function startNewBlogPost() {
    const ch = await ensureStudioChunk('blog');
    return ch.startNewBlogPost();
  }
  async function openBlogPostForEdit(slug) {
    const ch = await ensureStudioChunk('blog');
    return ch.openBlogPostForEdit(slug);
  }
  async function loadI18nTranslateQueue(forceReload) {
    const ch = await ensureStudioChunk('blog');
    return ch.loadI18nTranslateQueue(forceReload);
  }
  async function refreshI18nStudioMount() {
    const ch = await ensureStudioChunk('blog');
    return ch.refreshI18nStudioMount();
  }
  async function renderBlogCategoriesView(skipNetwork) {
    const ch = await ensureStudioChunk('blog');
    return ch.renderBlogCategoriesView(skipNetwork);
  }
  async function renderBlogView() {
    const ch = await ensureStudioChunk('blog');
    return ch.renderBlogView();
  }
  async function openImageCanvasStudio(item, onSaved) {
    const ch = await ensureStudioChunk('media_design');
    return ch.openImageCanvasStudio(item, onSaved);
  }
  async function triggerMainMediaUpload(fileList) {
    const ch = await ensureStudioChunk('media_design');
    return ch.triggerMainMediaUpload(fileList);
  }
  async function renderMediaView() {
    const ch = await ensureStudioChunk('media_design');
    return ch.renderMediaView();
  }
  async function renderMenusView() {
    const ch = await ensureStudioChunk('media_design');
    return ch.renderMenusView();
  }
  async function renderAppearanceStudioView() {
    const ch = await ensureStudioChunk('media_design');
    return ch.renderAppearanceStudioView();
  }
  async function renderHomeLayoutView() {
    const ch = await ensureStudioChunk('pages');
    return ch.renderHomeLayoutView();
  }
  async function openUnifiedPageEditor(pageKey, editorTab, subTab) {
    const ch = await ensureStudioChunk('pages');
    return ch.openUnifiedPageEditor(pageKey, editorTab, subTab);
  }
  async function startNewUnifiedCustomPage() {
    const ch = await ensureStudioChunk('pages');
    return ch.startNewUnifiedCustomPage();
  }
  async function createNewUnifiedServiceSubpage() {
    const ch = await ensureStudioChunk('pages');
    return ch.createNewUnifiedServiceSubpage();
  }
  async function renderPagesView() {
    const ch = await ensureStudioChunk('pages');
    return ch.renderPagesView();
  }
  async function renderGoogleReviewsView() {
    const ch = await ensureStudioChunk('seo_growth');
    return ch.renderGoogleReviewsView();
  }
  async function renderSeoCenterView() {
    const ch = await ensureStudioChunk('seo_growth');
    return ch.renderSeoCenterView();
  }
  async function renderGrowthToolsView() {
    const ch = await ensureStudioChunk('seo_growth');
    return ch.renderGrowthToolsView();
  }
  async function mountProgrammaticSolutionsPanel(host, opts) {
    const ch = await ensureStudioChunk('seo_growth');
    return ch.mountProgrammaticSolutionsPanel(host, opts);
  }
  async function renderSocialMediaView() {
    const ch = await ensureStudioChunk('seo_growth');
    return ch.renderSocialMediaView();
  }
  async function renderMessagesView() {
    const ch = await ensureStudioChunk('agency_crm');
    return ch.renderMessagesView();
  }
  async function renderVcardsStudioView() {
    const ch = await ensureStudioChunk('agency_crm');
    return ch.renderVcardsStudioView();
  }
  async function renderProposalsStudioView() {
    const ch = await ensureStudioChunk('agency_crm');
    return ch.renderProposalsStudioView();
  }
  async function renderClientPortalView() {
    const ch = await ensureStudioChunk('agency_crm');
    return ch.renderClientPortalView();
  }
  async function renderSettingsView() {
    const ch = await ensureStudioChunk('ops_system');
    return ch.renderSettingsView();
  }
  async function renderUptimeTowerView() {
    const ch = await ensureStudioChunk('ops_system');
    return ch.renderUptimeTowerView();
  }
  async function renderPerformanceStudioView() {
    const ch = await ensureStudioChunk('ops_system');
    return ch.renderPerformanceStudioView();
  }
  async function mountSystemPerformanceTab(host) {
    const ch = await ensureStudioChunk('ops_system');
    return ch.mountSystemPerformanceTab(host);
  }
  async function renderRenewalsView() {
    const ch = await ensureStudioChunk('ops_system');
    return ch.renderRenewalsView();
  }
  async function renderOtaHubView() {
    const ch = await ensureStudioChunk('ops_system');
    return ch.renderOtaHubView();
  }

  function syncCoreContextExports() {
    Object.assign(CTX, {
      CFG,
      DEFAULT_PAGE_SLUGS,
      DEFAULT_VIEW_TABS,
      FONT_GROUPS,
      FONT_PAIRING_PRESETS,
      HOME_LAYOUT_PRESETS,
      HOME_SECTION_CATALOG,
      ROOT,
      S,
      SECTOR_BOOT_HOOKS,
      SECTOR_COMMANDS,
      SECTOR_DASHBOARD_HOOKS,
      SECTOR_VIEWS,
      STUDIO_CORE_API,
      TR_DAYS_FULL,
      TR_DAYS_SHORT,
      TR_MONTHS_FULL,
      VIEW_TITLES,
      WIDGET_CATALOG,
      api,
      apiUpload,
      apiUploadSingleWithProgress,
      applyLiveAdminBrandState,
      applySidebarNavFilter,
      applyUrlStateToStudio,
      bindAnalyticsModeTabs,
      bindHomeSectionCardsEvents,
      buildAdminStateQuery,
      buildClientCategoryTreeFromList,
      buildConsolidatedFamilyBarHtml,
      buildExecutiveSvgChart,
      buildNotificationItems,
      buildSmartHashtags,
      buildSocialShareCaption,
      buildUnifiedPagesCatalog,
      computeHomeLayoutSnap,
      copyTextSafe,
      createBlankProposalDraft,
      createDefaultRenewalDraft,
      createDefaultTrackerDraft,
      createNewUnifiedServiceSubpage,
      detectSocialBadgeLabel,
      encodeApiPayloadB64,
      ensureConsolidatedFamilyBar,
      ensureNormalizedHomeSections,
      esc,
      extractGoogleFontName,
      findCatalogFont,
      finishPickerSelection,
      fmtBytes,
      formatDurationShort,
      formatMoneyTr,
      formatTurkishClockParts,
      getAdminTimeGreeting,
      getClientBlogCategoriesTree,
      getCoreOrLegalStateStatus,
      getDefaultBlogCategoryClient,
      getDefaultMenuTypography,
      getFooterMenuPlacement,
      getHomeSectionMeta,
      getPageMenuLabel,
      getResolvedPageSlug,
      getResolvedPageUrl,
      getSocialPlatformMeta,
      handlePickerUpload,
      humanizeAltFromFilename,
      initStudio,
      isSuperAdmin,
      isUrlInHeaderMenu,
      loadExecutiveReportData,
      loadI18nTranslateQueue,
      markHistoryPush,
      matchesTrQuery,
      mountProgrammaticSolutionsPanel,
      mountSearchInsightsPanel,
      mountSystemPerformanceTab,
      navigateTo,
      normalizeClientPageStatus,
      normalizeClientStoreMaps,
      normalizeMenuUrl,
      normalizeTrSearch,
      openBlogPostForEdit,
      openCommandPalette,
      openHomeSectionEditor,
      openImageCanvasStudio,
      openMediaPicker,
      openSocialShareStudio,
      openUnifiedPageEditor,
      paintAnalyticsDom,
      paintPickerSelectionBar,
      refreshAnalyticsReport,
      refreshI18nStudioMount,
      refreshNotificationsBell,
      refreshPickerCatalogOnly,
      refreshPickerSelectionHighlights,
      registerSectorViewClient,
      rememberMediaMtimes,
      renderAnalyticsModeTabsHtml,
      renderAnalyticsView,
      renderAppearanceStudioView,
      renderBlogCategoriesView,
      renderBlogView,
      renderClientPortalView,
      renderCommandItems,
      renderCurrentView,
      renderDashboard,
      renderExecutiveMonthlyReportView,
      renderFontSelectOptions,
      renderGoogleReviewsView,
      renderGrowthToolsView,
      renderHomeLayoutView,
      renderHomeSectionCardsHtml,
      renderMediaPickerBody,
      renderMediaView,
      renderMenusView,
      renderMessagesView,
      renderOtaHubView,
      renderPagesView,
      renderPerformanceStudioView,
      renderProposalsStudioView,
      renderRenewalsView,
      renderSeoCenterView,
      renderSeoRing,
      renderSettingsView,
      renderSocialMediaView,
      renderStatRowsHtml,
      renderUptimeTowerView,
      renderVcardsStudioView,
      resolveActiveNavJumpSpec,
      resolveAdminFontPreview,
      resolveParentNavView,
      resolvePreviewUrl,
      resolveUrl,
      resolveViewTitle,
      revokeActivePreviewBlobs,
      runAdminJump,
      runStudioUploadQueue,
      setPageMenuPlacement,
      slugifyClient,
      startNewBlogPost,
      startNewUnifiedCustomPage,
      syncAdminUrl,
      syncBlogCatsFromApiPayload,
      syncMediaSelectionDom,
      syncSidebarActiveTree,
      syncUserRoleUi,
      tickAdminClocks,
      toPlainDict,
      toast,
      trFoldLowerClient,
      triggerMainMediaUpload,
      uid,
      updateSidebarBadges
    });
  }

  async function navigateTo(view, extraCallback) {
    const t0 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
    if (view === 'google_reviews') {
      S.tabs.seo = 'google_reviews';
      view = 'seo';
    }
    if ((view === 'appearance' || view === 'ota_hub' || Boolean(SECTOR_VIEWS[view]?.superOnly)) && !isSuperAdmin()) {
      toast('Bu teknik modül yalnızca Ajans Süper Yönetici yetkisiyle açılabilir.', true);
      view = 'dashboard';
    }
    if (!isRestoringFromUrl) pendingHistoryMode = 'push';
    S.view = view;
    const parentTarget = resolveParentNavView(view);
    document.querySelectorAll('#zsNav .zs-nav-item-wrap').forEach(w => {
      w.classList.remove('is-peek-open');
      if (w.getAttribute('data-nav-wrap') === parentTarget) {
        w.classList.remove('is-collapsed');
      }
    });
    syncSidebarActiveTree();
    const titleEl = document.getElementById('zsViewTitle');
    if (titleEl) titleEl.textContent = resolveViewTitle(view);
    document.getElementById('zsSidebar')?.classList.remove('open');
    document.getElementById('zsQuickCreateMenu')?.classList.remove('open');

    const neededChunks = resolveChunksForView(view);
    const missingChunks = neededChunks.filter(c => !CTX.chunks[c]);
    if (missingChunks.length > 0) {
      let loaderTimer = setTimeout(() => {
        if (S.view === view && missingChunks.some(c => !CTX.chunks[c])) {
          ROOT.innerHTML = `<div class="zs-v4-chunk-loader">⚡ Stüdyo v5.2 modülü hazırlanıyor...</div>`;
        }
      }, 50);
      try {
        await Promise.all(missingChunks.map(ensureStudioChunk));
      } finally {
        clearTimeout(loaderTimer);
      }
    }

    if (typeof extraCallback === 'function') {
      await extraCallback();
    }
    await renderCurrentView();
    syncSidebarActiveTree();
    const t1 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
    updateV4SpeedTelemetry(Math.max(0.1, t1 - t0));
  }

  function renderCurrentView() {
    switch (S.view) {
      case 'dashboard': return renderDashboard();
      case 'analytics': return renderAnalyticsView();
      case 'home_layout': return renderHomeLayoutView();
      case 'pages': return renderPagesView();
      case 'blog': return renderBlogView();
      case 'blog_categories': return renderBlogCategoriesView();
      case 'media': return renderMediaView();
      case 'menus': return renderMenusView();
      case 'builder': return openUnifiedPageEditor('anasayfa', 'builder');
      case 'home': return openUnifiedPageEditor('anasayfa', 'content');
      case 'services': return openUnifiedPageEditor('hizmetler', 'content');
      case 'projects': return openUnifiedPageEditor('projeler', 'content');
      case 'about': return openUnifiedPageEditor('hakkimizda', 'content');
      case 'contact': return openUnifiedPageEditor('iletisim', 'content');
      case 'appearance': return renderAppearanceStudioView();
      case 'google_reviews': return renderGoogleReviewsView();
      case 'growth_tools': return renderGrowthToolsView();
      case 'vcards': return renderVcardsStudioView();
      case 'proposals': return renderProposalsStudioView();
      case 'client_portal': return renderClientPortalView();
      case 'uptime_tower': return renderUptimeTowerView();
      case 'renewals': return renderRenewalsView();
      case 'ota_hub': return renderOtaHubView();
      case 'performance': return renderPerformanceStudioView();
      case 'seo': return renderSeoCenterView();
      case 'social_media': return renderSocialMediaView();
      case 'messages': return renderMessagesView();
      case 'settings': return renderSettingsView();
      default: {
        const secEntry = SECTOR_VIEWS[S.view];
        if (secEntry) {
          syncAdminUrl('replace');
          const fn = typeof secEntry === 'function' ? secEntry : secEntry.render;
          if (typeof fn === 'function') {
            return fn(window.__STUDIO_CORE);
          }
        }
        return renderDashboard();
      }
    }
  }

  function openCommandPalette(initialQuery) {
    const modal = document.getElementById('zsCmdModal');
    const inp = document.getElementById('zsCmdInput');
    const qInit = typeof initialQuery === 'string' ? initialQuery : '';
    modal.classList.add('open');
    inp.value = qInit;
    renderCommandItems(qInit);
    setTimeout(() => inp.focus(), 40);
  }

  function renderCommandItems(query) {
    const q = query.trim().toLowerCase();
    const pageJumpCmds = (S.store ? buildUnifiedPagesCatalog() : []).map(pg => ({
      label: `Sayfayı Düzenle: ${pg.title} (${pg.url})`,
      hint: pg.typeLabel,
      run: () => openUnifiedPageEditor(pg.key, 'content')
    }));
    const commands = [
      ...SECTOR_COMMANDS,
      { label: '🚀 Merkezi OTA Güncelleme Hub & Bağlı Müşteri Filosu (Tek Tıkla Tüm Siteleri Güncelle)', hint: 'Merkezi OTA · v5.2', run: () => runAdminJump('ota_hub:fleet') },
      { label: '📦 Yeni Stüdyo Çekirdek Sürümü Paketle & OpenSSL RSA-2048 İle İmzala', hint: 'OTA Paketleyici · v5.2', run: () => runAdminJump('ota_hub:releases') },
      { label: '🔗 Bağlantı Kodu (ZYN5-OTA1) ile Merkezi OTA Hub’a Bağlan & Otomatik Güncelleme', hint: 'OTA İstemci · v5.2', run: () => runAdminJump('ota_hub:client') },
      { label: '🗄️ MySQL Veritabanı & Tek Tıkla Göç Sihirbazı (1.681+ JSON → SQL & Tek .SQL Yedek)', hint: 'Sistem & Veritabanı · v5', run: () => runAdminJump('settings:database') },
      { label: '🧹 Kullanılmayan & Bağlantısız Dosya Radarı + Güvenli Temizleyici (v5.2)', hint: 'Sistem & Temizlik · v5.2', run: () => runAdminJump('performance:unused_files') },
      { label: '🕸️ Konu Kümesi (Topic Cluster) & İç Link Haritası Denetçisi + Otomatik Örücü', hint: 'Google & SEO · v4', run: () => { S.tabs.seo = 'clusters'; navigateTo('seo'); } },
      { label: '🔗 Teklif → Sözleşme PDF → Proje Portalı → Uptime & Yenileme Otomasyon Zinciri', hint: 'Ajans Otomasyonu · v4', run: () => navigateTo('proposals') },
      { label: '📱 Sosyal Medya Merkezi — Instagram, Facebook, LinkedIn, X, YouTube & TikTok Hesapları', hint: 'Sosyal Medya Merkezi', run: () => { S.tabs.social_media = 'accounts'; navigateTo('social_media'); } },
      { label: '🔗 Sosyal Medya Paylaşım Çubuğu & OpenGraph (1200×630) Kart Ayarları', hint: 'Sosyal Medya · Paylaşım', run: () => { S.tabs.social_media = 'sharing'; navigateTo('social_media'); } },
      { label: '📸 Instagram Post (1080×1080) & Story (1080×1920) Görsel Üretici Merkezi', hint: 'Sosyal Medya · IG/Story', run: () => { S.tabs.social_media = 'studio'; navigateTo('social_media'); } },
      { label: '📂 Blog Kategorileri — Hiyerarşik Kategori, Alt Kategori, Sıralama & SEO Yönetimi', hint: 'Blog Kategorileri', run: () => navigateTo('blog_categories') },
      { label: '🎯 Satış Hunisi (Mini-CRM Kanban) & Tek Tıkla Teklife Dönüştür', hint: 'CRM Kanban', run: () => { S.messagesMode = 'kanban'; navigateTo('messages'); } },
      { label: '🔐 Müşteri Proje Durum & Aşama Onay Portalı (/proje-takip/...)', hint: 'Proje Takip Portalı', run: () => navigateTo('client_portal') },
      { label: '📆 Hosting, Domain, SSL & Yıllık Bakım Yenileme Radarı', hint: 'Yenileme Radarı', run: () => navigateTo('renewals') },
      { label: '🌍 Sektörel & Bölgesel SEO Çözüm Sayfaları Üreteci (Programatik SEO)', hint: 'Programatik SEO', run: () => { S.tabs.seo = 'solutions'; navigateTo('seo'); } },
      { label: '📅 15 Dakika Online Dijital Keşif Görüşmesi Planlayıcı (/gorusme-planla/)', hint: 'Keşif Görüşmesi', run: () => { S.tabs.growth_tools = 'booking'; navigateTo('growth_tools'); } },
      { label: '+ Yeni Özel Müşteri Teklifi Oluştur (/teklif/... Paylaşım Linki)', hint: 'Online Teklifler', run: () => { S.editingProposal = createBlankProposalDraft(); navigateTo('proposals'); } },
      { label: '📄 Aylık Beyaz Etiketli (White-Label) Müşteri Performans & SEO Raporu (A4 PDF)', hint: 'A4 PDF Rapor', run: () => { S.tabs.analytics = 'report'; navigateTo('analytics'); } },
      { label: '📄 Özel Müşteri Teklifleri — Online Teklif Linki (/teklif/...) & Okundu Takibi', hint: 'Online Teklifler', run: () => navigateTo('proposals') },
      { label: '⚡ LiteSpeed LSCache Kapat/Aç & Önbellekteki Sayfalar (HIT / MISS Haritası)', hint: 'LiteSpeed & Önbellek', run: () => navigateTo('performance') },
      { label: '⚡ LiteSpeed Önbellek Temizleyici, Sistem Sağlığı & Yetim Görsel Temizleme', hint: 'Sistem & Hız', run: () => { S.tabs.settings = 'performance'; navigateTo('settings'); } },
      { label: '☁️ Tam Veri Yedekleme Kasası (.zip İndir / Otomatik Plan / Geri Yükle)', hint: 'Yedekleme Kasası', run: () => { S.tabs.settings = 'backups'; navigateTo('settings'); } },
      { label: '🎯 16 Kriterli İçerik SEO Denetçisi, Odak Kelime Çakışması & IndexNow Merkezi', hint: 'SEO Denetçisi', run: () => { S.tabs.seo = 'analyzer'; navigateTo('seo'); } },
      { label: '✍️ E-E-A-T Uzman Yazar Profilleri (Person Schema) Yönetimi', hint: 'Blog · Yazarlar', run: () => { S.blog.mode = 'authors'; navigateTo('blog'); } },
      { label: '💬 Makale Soru-Cevap & Okuyucu Yorum Moderasyonu', hint: 'Blog · Yorumlar', run: () => { S.blog.mode = 'comments'; navigateTo('blog'); } },
      { label: '🛰️ Ajans Uptime & SSL Kontrol Kulesi (300+ Müşteri Sitesi Takibi)', hint: 'Uptime & SSL Kulesi', run: () => navigateTo('uptime_tower') },
      { label: '💳 QR Kodlu Dijital Kartvizit (.vcf) & Kurumsal IBAN / Fatura Stüdyosu', hint: 'Dijital Kartvizit', run: () => navigateTo('vcards') },
      { label: '🎧 Blog "Makaleyi Sesli Dinle" Podcast & Otomatik SEO İç Linkleme Motoru', hint: 'Blog · Oto İç Link', run: () => { S.blog.mode = 'seo_links'; navigateTo('blog'); } },
      { label: '📸 Blog Instagram (1080×1080) & Story (1080×1920) Görsel + Metin Üretici', hint: 'Sosyal Görsel Stüdyosu', run: () => { S.blog.mode = 'list'; navigateTo('blog'); } },
      { label: '📰 Google News & Yapay Zeka Haber Otopilotu + Filigranlı Kapak Stüdyosu', hint: 'Otomatik Blog Botu', run: () => { S.blog.mode = 'news_bot'; navigateTo('blog'); } },
      { label: '🎨 Ajans White-Label Renk & Tema Sihirbazı (6 Hazır Palet + Canlı Önizleme)', hint: 'Tema & Görünüm', run: () => { S.tabs.appearance = 'theme'; navigateTo('appearance'); } },
      { label: '📣 Üst Kampanya & Duyuru Çubuğu (Geri Sayım + Sıfır Çerez)', hint: 'Tema & Duyuru', run: () => { S.tabs.appearance = 'promo'; navigateTo('appearance'); } },
      { label: '🚧 Tek Tıkla Bakım Modu / Çok Yakında Ekranı (Admin Bypass)', hint: 'Bakım Modu', run: () => { S.tabs.appearance = 'maintenance'; navigateTo('appearance'); } },
      { label: '🤖 Yapay Zeka ile Tam Blog Makalesi, İçindekiler & SSS Üret', hint: 'AI Blog Yazarı', run: () => { S.openAiWriterOnNew = true; navigateTo('blog', () => startNewBlogPost()); } },
      { label: '🔄 301 Kalıcı Yönlendirme & Sitemap Merkezi', hint: 'SEO & 301', run: () => { S.tabs.seo = 'redirects'; navigateTo('seo'); } },
      { label: '🕒 Zaman Makinesi — Sürüm Geçmişi & Tek Tıkla Geri Dönüş (5 Kopya)', hint: 'Yedek & Sürüm', run: () => { S.tabs.settings = 'revisions'; navigateTo('settings'); } },
      { label: '🛡️ Güvenlik Duvarı, IP Koruması & Giriş / İşlem Kayıtları (Audit Log)', hint: 'Güvenlik Merkezi', run: () => { S.tabs.settings = 'security'; navigateTo('settings'); } },
      { label: '📊 Ziyaretçi İstatistikleri, Son 30 Gün Girenler & Anlık Sitedeki Kişiler', hint: 'Canlı Analitik', run: () => navigateTo('analytics') },
      { label: '🤝 Hakkımızda — Yeni Müşteri / Referans Logosu Ekle', hint: 'Hakkımızda · Referanslar', run: () => { S.pages.subTab = 'clients'; openUnifiedPageEditor('hakkimizda', 'content'); } },
      { label: '✎ Ana Sayfayı Düzenle (Hero Slaytları, Metinler & Görsel Tasarımcı)', hint: 'Ana Sayfa', run: () => openUnifiedPageEditor('anasayfa', 'content') },
      { label: '⇅ Ana Sayfa Bölümlerinin Yerini Değiştir / Gizle-Göster', hint: 'Ana Sayfa Bölümleri', run: () => navigateTo('home_layout') },
      { label: 'Tüm Sayfalar Listesini Aç', hint: 'Sayfalar', run: () => navigateTo('pages', () => { S.pages.mode = 'list'; }) },
      { label: '+ Yeni Sayfa Ekle', hint: 'Sayfalar', run: () => startNewUnifiedCustomPage() },
      { label: '+ Yeni Blog Yazısı Oluştur (TR + EN/DE/RU Otomatik Çeviri)', hint: 'Blog Editörü', run: () => navigateTo('blog', () => startNewBlogPost()) },
      { label: '🌍 Çoklu Dil & Otomatik Blog Çeviri Ayarları (EN · DE · RU)', hint: 'Çoklu Dil (i18n)', run: () => { S.tabs.settings = 'i18n'; navigateTo('settings'); } },
      { label: '📣 Ana Sayfayı Sosyal Medyada Paylaş & Kart Önizle', hint: 'Sosyal Paylaşım Stüdyosu', run: () => openSocialShareStudio({ kind: 'page', targetKey: 'anasayfa' }) },
      { label: '🔤 Site Yazı Tipini Değiştir (Google Fonts & Başlık Fontu)', hint: 'Google Fonts & Tipografi', run: () => { S.tabs.menus = 'typography'; navigateTo('menus'); } },
      { label: 'Header & Footer Menüleri Düzenle / Yer Değiştir', hint: 'Menüler & Tipografi', run: () => { S.tabs.menus = 'header'; navigateTo('menus'); } },
      { label: 'Header & Footer Menü Yazı Boyutlarını Ayarla (px)', hint: 'Menüler & Tipografi', run: () => { S.tabs.menus = 'typography'; navigateTo('menus'); } },
      { label: 'Yeni Footer Menü Sütunu Ekle', hint: 'Menüler & Tipografi', run: () => { S.tabs.menus = 'footer'; navigateTo('menus'); } },
      { label: 'Medya Kütüphanesi & Resim Yükle (/uploads)', hint: 'Medya', run: () => navigateTo('media') },
      { label: 'Normal SEO & Yapay Zeka SEO (GEO) Ayarları', hint: 'SEO & llms.txt', run: () => navigateTo('seo') },
      { label: '🍪 KVKK Çerez Banner, Tümünü Reddet & Zero-Cookie Ayarları', hint: 'KVKK & Çerez Politikası', run: () => { S.tabs.seo = 'cookies'; navigateTo('seo'); } },
      { label: '301 Yönlendirme Kuralları', hint: 'SEO Merkezi', run: () => { S.tabs.seo = 'redirects'; navigateTo('seo'); } },
      { label: '62+ Müşteri Referans Logolarını Yönet', hint: 'Hakkımızda Sayfası', run: () => { S.pages.subTab = 'clients'; openUnifiedPageEditor('hakkimizda', 'content'); } },
      { label: '⚡ Akıllı Hızlı İletişim Barı (WhatsApp + Hızlı Arama & Mesai)', hint: 'Büyüme Araçları', run: () => { S.tabs.growth_tools = 'quick_contact'; navigateTo('growth_tools'); } },
      { label: '🔍 Ücretsiz Site Hızı & SEO Ön Analiz Lead Aracı Ayarları', hint: 'Büyüme Araçları', run: () => { S.tabs.growth_tools = 'site_audit'; navigateTo('growth_tools'); } },
      { label: 'Otomatik Google Yorumları & Puan Widget Stüdyosu', hint: 'Google Yorumları', run: () => navigateTo('google_reviews') },
      { label: 'Hosting Paketlerini Düzenle (Basic / Eko / Pro)', hint: 'Hosting Sayfası', run: () => openUnifiedPageEditor('service:hosting', 'content') },
      { label: 'Ana Sayfa Hero Slaytlarını Düzenle', hint: 'Ana Sayfa', run: () => { S.pages.subTab = 'hero'; openUnifiedPageEditor('anasayfa', 'content'); } },
      { label: 'Proje Talepleri / Gelen Kutusu', hint: 'CRM', run: () => navigateTo('messages') },
      { label: 'Sistem Yedeği İndir (.json)', hint: 'Güvenlik', run: () => { window.location.href = CFG.apiUrl + '?action=backup_download&csrf_token=' + encodeURIComponent(CFG.csrfToken); } }
    ].concat(pageJumpCmds);
    const listEl = document.getElementById('zsCmdList');
    const filtered = commands.filter(c => !q || (c.label + ' ' + c.hint).toLowerCase().includes(q));
    listEl.innerHTML = filtered.map((c, idx) => `
      <button type="button" class="zs-cmd-item" data-cmd-idx="${idx}">
        <strong>${esc(c.label)}</strong>
        <span class="zs-pill zs-pill-cat">${esc(c.hint)}</span>
      </button>
    `).join('');
    listEl.querySelectorAll('[data-cmd-idx]').forEach(btn => {
      btn.onclick = () => {
        const item = filtered[Number(btn.getAttribute('data-cmd-idx'))];
        document.getElementById('zsCmdModal').classList.remove('open');
        if (item) item.run();
      };
    });
  }

  /* ======================== MODULE 1: DASHBOARD (APPLE LIQUID GLASS COMMAND STUDIO) ======================== */
  function renderDashboard() {
    syncAdminUrl('replace');
    const titleEl = document.getElementById('zsViewTitle');
    const dynGreeting = getAdminTimeGreeting();
    if (titleEl) titleEl.textContent = 'Gösterge Paneli';
    const clk = formatTurkishClockParts(new Date());
    const sum = S.summary || {};
    const recentPosts = sum.recent_posts || [];
    const recentMsgs = sum.recent_messages || [];
    const an = S.analytics.report || sum.analytics || {};
    const liveCnt = Number(an.live?.count || 0);
    const todayU = Number(an.kpis?.today?.uniques || 0);
    const todayV = Number(an.kpis?.today?.views || 0);
    const d30U = Number(an.kpis?.days_30?.uniques || 0);
    const d30V = Number(an.kpis?.days_30?.views || 0);
    const sparkSlice = (an.daily_series || []).slice(-14);
    const maxSpark = Math.max(1, ...sparkSlice.map(x => Number(x.views || 0)));
    const pagesCount = buildUnifiedPagesCatalog().length;
    const adminName = S.store?.admin?.display_name || 'Zeynart Yönetici';
    const mntActive = Boolean(S.store?.maintenance?.enabled);
    const promoActive = Boolean(S.store?.promo_bar?.enabled);
    const logs404Cnt = Array.isArray(S.logs404) ? S.logs404.length : Number(sum.logs_404_count || 0);
    const revCnt = Array.isArray(S.revisions) ? S.revisions.length : Number(sum.revisions_count || 0);
    const newsQueueCnt = Array.isArray(S.newsBotQueue) ? S.newsBotQueue.length : Number(sum.news_queue_count || 0);
    const utSum = S.uptime.summary || {};
    const utDownCnt = Number(utSum.down_count || 0);
    const utSslWarnCnt = Number(utSum.ssl_expiring_count || 0);
    const utTotalCnt = Number(utSum.total || 0);
    const vcardsCnt = Array.isArray(S.store?.vcards) ? S.store.vcards.length : Number(sum.vcards_count || 1);
    const blogCatsCnt = getClientBlogCategoriesTree().length || Number(sum.blog_categories_count || 10);
    const proposalsCnt = Array.isArray(S.proposals) ? S.proposals.length : 0;
    const superMode = isSuperAdmin();
    const unusedSum = S.unusedFilesSummary || {};
    const unusedCnt = superMode ? Number(unusedSum.unused_count || 0) : 0;
    const unusedSamples = Array.isArray(unusedSum.sample_files) ? unusedSum.sample_files : [];
    const dbSt = S.databaseStatus || {};
    const sqlEngineActive = dbSt.active_engine === 'mysql' || dbSt.active_engine === 'sqlite';
    const diskJsonTotal = Number(dbSt.disk_json?.total_json_files || 0);
    const turboModeLabel = dbSt.active_engine === 'mysql'
      ? 'PDO MySQL Motoru'
      : (dbSt.active_engine === 'sqlite'
        ? 'PDO SQLite Motoru'
        : (S.turboEngine?.sqlite_wal ? 'Ultra-Turbo SQLite WAL' : 'Ultra-Turbo İndeks'));
    const otaSum = S.otaSummary || {};
    const otaFleetTotal = Number(otaSum.total_nodes || 0);
    const otaFleetOutdated = Number(otaSum.outdated_nodes || 0);
    const otaClientUpdateReady = superMode && Boolean(otaSum.client_update_available);
    const rawVerHistory = Array.isArray(S.versionHistory) && S.versionHistory.length ? S.versionHistory : [
      {
        version: 'v5.2',
        status: 'active',
        badge: 'AKTİF SÜRÜM',
        title: 'Çoklu Sektör Modül Kancası & Özel /x/ Klasör Eşleyici',
        items: [
          'Emlak, Mücevher, Kulüp, Klinik & B2B özel sektör eklenti kancaları (extensions/)',
          'Fiziksel /x/ klasörü veya gizli giriş adresli sitelere otomatik OTA eşleme',
          'Açık Çekirdek Beyaz Listesi ile vitrin şablonu, ikon ve sözlük koruması',
          '%100 dinamik White-Label panel başlığı, giriş ekranı ve marka kimliği'
        ]
      },
      {
        version: 'v5.1',
        status: 'released',
        badge: 'MERKEZİ OTA HUB',
        title: 'Merkezi OTA Otomatik Güncelleme Hub & Filo Komuta Merkezi',
        items: [
          'Zeynart güncellendiğinde tüm bağlı müşteri panellerine tek tıkla / oto-güncelleme',
          'OpenSSL RSA-2048 kriptografik paket imzalama & SHA-256 doğrulama',
          'Müşteriye özel pages/, site.css, uploads/ ve data/ %100 dokunulmaz koruma',
          'Güncelleme öncesi otomatik pre-ota.zip yedeği & hatada anında Rollback'
        ]
      },
      {
        version: 'v5.0',
        status: 'released',
        badge: 'MYSQL MOTORU',
        title: 'Kurumsal PDO MySQL Motoru, Tek Tıkla Göç & .SQL Yedekleme',
        items: [
          '1.681+ JSON makale yığını yerine indeksli PDO MySQL (InnoDB utf8mb4) tabloları',
          'Admin panelden Tek Tıkla MySQL Göç Sihirbazı & Eski JSON Temizleyici',
          'Yedekleme Kasasında binlerce .json yerine tek .sql veritabanı dökümü',
          'Tüm site, CRM, Zero-Cookie analitik ve 5 sürüm Zaman Makinesi SQL üzerinde'
        ]
      },
      {
        version: 'v4.0',
        status: 'released',
        badge: 'TURBO ÇEKİRDEK',
        title: 'Ultra-Turbo Motor, Code-Splitting & Otomasyon Zinciri',
        items: [
          'Modüler JS Code-Splitting (6 paket) & 0ms geçiş motoru',
          'Ultra-Turbo Boot önbelleği & uyarlamalı SQLite WAL indeks',
          'Teklif → Sözleşme PDF → Proje Portalı → Uptime & Yenileme zinciri',
          'Konu Kümesi iç link örücü, Dergi stili blog & Bağlantısız Dosya Temizleyici'
        ]
      },
      {
        version: 'v3.0',
        status: 'released',
        badge: 'KURUMSAL KULE',
        title: '20/20 Güvenlik Kalkanı, LSCache & Sosyal Merkez',
        items: [
          '20/20 OWASP Kalkanı, 2FA, RBAC Rol & 5 Sürüm Zaman Makinesi',
          'LiteSpeed LSCache Aç/Kapat + Canlı HIT / MISS sayfa haritası',
          'Google & SEO Merkezi (GEO) + Bağımsız Sosyal Medya Merkezi',
          'Uptime & SSL Kulesi, Yenileme Radarı & QR Dijital Kartvizit'
        ]
      },
      {
        version: 'v2.0',
        status: 'released',
        badge: 'CANLI STÜDYO',
        title: 'Apple Cam Stüdyo, Canlı Düzenleyici & 4 Dil',
        items: [
          'Apple Cam (Liquid Glass) arayüzü & White-Label renk sihirbazı',
          'Ön yüzde sabit bar & 0ms yerinde Canlı Görsel Sayfa Düzenleyici',
          'TR · EN · DE · RU çoklu dil, toplu çeviri & AI Blog/SSS Yazarı',
          'Zero-Cookie canlı analitik, Teklif Sihirbazı, Akıllı Pop-up & SMTP'
        ]
      },
      {
        version: 'v1.0',
        status: 'released',
        badge: 'ÇEKİRDEK MİMARİ',
        title: 'Saf PHP 8+ Çekirdek, Temiz Kök & WebP Medya',
        items: [
          'Saf PHP 8+ tek giriş noktası (index.php) & temiz /api/* rotaları',
          'Otomatik /uploads/YYYY/MM/DD/ WebP dönüşümü & marka damgası',
          'Tek merkezli Sayfalar, kalıcı bağlantı (Slug), Çöp Kutusu & 301',
          'Hiyerarşik Blog Kategorileri, TOC & yerel .woff2 PageSpeed'
        ]
      }
    ];
    const orderedVerHistory = rawVerHistory.slice().reverse();

    ROOT.innerHTML = `
      ${otaClientUpdateReady ? `
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 18px;margin-bottom:16px;border-radius:14px;background:rgba(16,185,129,.16);border:1px solid rgba(52,211,153,.52);color:#ecfdf5">
          <div style="font-size:13px"><strong>🚀 Yeni Stüdyo OTA Güncellemesi Hazır (v${esc(otaSum.client_remote_version || '')}):</strong> Merkezi Hub üzerinde RSA-2048 imzalı yeni çekirdek paketi yayınlandı. Sitenizin tasarımına ve verilerine dokunulmadan tek tıkla güncelleyebilirsiniz.</div>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="dashOpenOtaClientAlertBtn">⚡ İncele &amp; Güncelle →</button>
        </div>
      ` : ''}
      ${unusedCnt > 0 ? `
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 18px;margin-bottom:16px;border-radius:14px;background:rgba(245,158,11,.15);border:1px solid rgba(251,191,36,.48);color:#fef3c7">
          <div style="font-size:13px"><strong>🧹 Kullanılmayan / Bağlantısız Dosya Uyarısı:</strong> Sunucuda aktif <strong>Stüdyo v5.2</strong> ile hiçbir bağlantısı olmayan <strong>${unusedCnt} adet eski/gereksiz dosya</strong> tespit edildi${unusedSamples.length ? ` (<code>${esc(unusedSamples.slice(0, 2).join(', '))}</code>${unusedCnt > 2 ? ' vb.' : ''})` : ''}.</div>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="dashOpenUnusedAlertBtn">🧹 İncele &amp; Güvenle Temizle (${unusedCnt}) →</button>
        </div>
      ` : ''}
      ${(utDownCnt > 0 || utSslWarnCnt > 0) ? `
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 18px;margin-bottom:16px;border-radius:14px;background:${utDownCnt > 0 ? 'rgba(239,68,68,.16)' : 'rgba(245,158,11,.15)'};border:1px solid ${utDownCnt > 0 ? 'rgba(248,113,113,.5)' : 'rgba(251,191,36,.45)'};color:#fff1f2">
          <div style="font-size:13px"><strong>🛰️ Uptime &amp; SSL Kulesi Uyarısı:</strong> ${utDownCnt > 0 ? `<strong>${utDownCnt} müşteri sitesi şu an erişilemiyor</strong>` : ''}${utDownCnt > 0 && utSslWarnCnt > 0 ? ' ve ' : ''}${utSslWarnCnt > 0 ? `<strong>${utSslWarnCnt} sitenin SSL süresi yaklaşıyor</strong>` : ''}.</div>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="dashOpenUptimeAlertBtn">🛰️ Kontrol Kulesini Aç →</button>
        </div>
      ` : ''}
      ${mntActive ? `
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 18px;margin-bottom:16px;border-radius:14px;background:rgba(245,158,11,.14);border:1px solid rgba(251,191,36,.45);color:#fef3c7">
          <div style="font-size:13px"><strong>🚧 Bakım Modu Aktif:</strong> Ziyaretçiler şu an "Çok Yakında / Yenileniyoruz" ekranını görüyor. Yönetici oturumunuz açık olduğu için siz canlı siteyi normal görebilirsiniz.</div>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="dashManageMaintBtn">Bakım Modunu Yönet →</button>
        </div>
      ` : ''}
      ${newsQueueCnt > 0 ? `
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 18px;margin-bottom:16px;border-radius:14px;background:rgba(16,185,129,.14);border:1px solid rgba(52,211,153,.42);color:#ecfdf5">
          <div style="font-size:13px"><strong>📰 Google News Otopilotu:</strong> Yapay zeka ile özgünleştirilmiş ve filigranlı kapak görseli hazırlanmış <strong>${newsQueueCnt} yeni teknoloji haberi</strong> onayınızı bekliyor.</div>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="dashOpenNewsQueueBtn">İncele &amp; Yayınla (${newsQueueCnt}) →</button>
        </div>
      ` : ''}
      <!-- ROW 1: APPLE LIQUID GLASS HERO & LIVE DIGITAL CLOCK DECK -->
      <section class="zs-dash-hero-glass">
        <div class="zs-dash-hero-main">
          <div class="zs-dash-hero-badges">
            <span class="zs-glass-chip is-accent" style="font-weight:800">✦ STÜDYO v5.2</span>
            <span class="zs-glass-chip"><i class="zs-dot-ok"></i> SSL &amp; KVKK Zero-Cookie Aktif</span>
            <span class="zs-glass-chip">🌍 TR · EN · DE · RU Yayında</span>
            ${superMode ? `<span class="zs-glass-chip" style="border-color:rgba(56,189,248,.46);color:#bae6fd;cursor:pointer" id="dashHeroOtaChip" title="Merkezi OTA Güncelleme Hub &amp; Filo Komuta Merkezi">🚀 Merkezi OTA Hub${otaFleetTotal > 0 ? ` (${otaFleetTotal} Site)` : ''}</span>` : ''}
            ${sqlEngineActive ? `<span class="zs-glass-chip" style="border-color:rgba(52,211,153,.5);color:#6ee7b7;font-weight:700">🗄️ ${dbSt.active_engine === 'mysql' ? 'PDO MySQL Aktif' : 'PDO SQLite Aktif'} (${Number(dbSt.posts_in_sql || 0).toLocaleString('tr-TR')} Makale)</span>` : ''}
            ${S.bootMs ? `<span class="zs-glass-chip" title="${esc(turboModeLabel)} &amp; Modüler Code-Splitting Başlatma Süresi">⚡ ${esc(turboModeLabel)}: ${S.bootMs} ms</span>` : ''}
            ${promoActive ? `<span class="zs-glass-chip" style="border-color:rgba(94,234,212,.45);color:#99f6e4">📣 Kampanya Çubuğu Yayında</span>` : ''}
          </div>
          <h2 class="zs-dash-hero-title" id="zsDashGreetingHeading">${esc(dynGreeting)}</h2>
          <p class="zs-dash-hero-sub">
            Hoş geldiniz, <strong>${esc(adminName)}</strong>. Ana sayfa vitrinini, hiyerarşik blog kategorilerini, çok dilli blog arşivini, medya kütüphanesini ve canlı ziyaretçi akışını tek cam stüdyodan yönetin.
          </p>
          <div class="zs-dash-hero-actions">
            <button type="button" class="zs-btn zs-btn-primary" id="dashOpenHomeEdit">✎ Ana Sayfayı Düzenle</button>
            <a href="${esc(resolveUrl('/?canli_duzenle=1'))}" target="_blank" rel="noopener" class="zs-btn zs-btn-glass-light" id="dashLiveHomeEdit">✨ Ana Sayfayı Canlı Düzenle ↗</a>
            <button type="button" class="zs-btn zs-btn-glass-subtle" id="dashHeroOpenSeo">🌐 Google &amp; SEO Merkezi</button>
            <button type="button" class="zs-btn zs-btn-glass-subtle" id="dashHeroOpenSocial">📱 Sosyal Medya Merkezi</button>
            <button type="button" class="zs-btn zs-btn-glass-subtle" id="dashOpenNewsBotTile">📰 Google News Otopilotu${newsQueueCnt > 0 ? ` (${newsQueueCnt})` : ''}</button>
          </div>
        </div>

        <div class="zs-dash-clock-widget" aria-label="Canlı Saat ve Tarih">
          <div class="zs-dash-clock-head">
            <span class="zs-dash-clock-tag">🕒 CANLI STÜDYO SAATİ</span>
            <span class="zs-dash-clock-week" id="zsDashHeroWeek">${esc(clk.weekLabel)}</span>
          </div>
          <div class="zs-dash-clock-digits">
            <strong id="zsDashHeroClockHM">${esc(clk.hm)}</strong><span id="zsDashHeroClockSec">${esc(clk.sec)}</span>
          </div>
          <div class="zs-dash-clock-date" id="zsDashHeroDateFull">${esc(clk.fullDate)}</div>
          <div class="zs-dash-clock-foot">
            <span><span class="zs-live-orb ${liveCnt > 0 ? '' : 'idle'}"></span> Anlık: <strong data-dash-clock-live>${liveCnt.toLocaleString('tr-TR')} Kişi</strong></span>
            <span>Bugün: <strong>${todayU.toLocaleString('tr-TR')} Tekil</strong></span>
          </div>
        </div>
      </section>

      <!-- ROW 2: LIVE VISITOR & 30-DAY TRAFFIC GLASS STRIP -->
      <div class="zs-an-dash-strip" id="zsDashAnalyticsStrip">
        <div class="zs-an-dash-metrics">
          <div class="zs-an-dash-stat">
            <small>ŞU AN SİTEDE (ANLIK)</small>
            <strong style="color:${liveCnt > 0 ? '#6ee7b7' : '#ffffff'}">
              <span class="zs-live-orb ${liveCnt > 0 ? '' : 'idle'}"></span>
              <span data-dash-live-cnt>${liveCnt.toLocaleString('tr-TR')} Kişi</span>
            </strong>
          </div>
          <div class="zs-an-dash-stat">
            <small>BUGÜN GİRENLER</small>
            <strong>${todayU.toLocaleString('tr-TR')} <span class="zs-an-dash-inline">(${todayV.toLocaleString('tr-TR')} görüntüleme)</span></strong>
          </div>
          <div class="zs-an-dash-stat">
            <small>SON 30 GÜN GİRENLER</small>
            <strong style="color:#67e8f9">${d30U.toLocaleString('tr-TR')} Kişi <span class="zs-an-dash-inline">· ${d30V.toLocaleString('tr-TR')} sayfa</span></strong>
          </div>
        </div>
        <div class="zs-an-mini-spark" title="Son 14 günlük sayfa görüntüleme eğilimi">
          ${(sparkSlice.length ? sparkSlice : Array.from({ length: 14 }, () => ({ views: 0, label: '' }))).map(pt => {
            const hPct = Math.max(12, Math.round((Number(pt.views || 0) / maxSpark) * 100));
            return `<i style="height:${pt.views > 0 ? hPct : 10}%;opacity:${pt.views > 0 ? 1 : 0.34}" title="${esc(pt.label || '')}: ${Number(pt.views || 0)} görüntüleme"></i>`;
          }).join('')}
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button type="button" class="zs-btn zs-btn-ai" id="dashOpenAnalytics">📊 Ziyaretçi İstatistikleri &amp; Canlı Trafik →</button>
          <button type="button" class="zs-btn zs-btn-primary" id="dashOpenExecReport">📄 Aylık Müşteri &amp; SEO Raporu (A4 PDF)</button>
        </div>
      </div>

      <!-- ROW 3: 4 ILLUMINATED APPLE GLASS KPI CARDS -->
      <div class="zs-kpi-grid">
        <div class="zs-kpi zs-kpi-emerald">
          <div class="zs-kpi-top"><span>TOPLAM BLOG MAKALESİ</span><span class="zs-kpi-icon">✎</span></div>
          <strong>${Number(sum.total_posts || 0).toLocaleString('tr-TR')}</strong>
          <small>${Number(sum.published_posts || 0).toLocaleString('tr-TR')} yayında · ${sum.draft_posts || 0} taslak</small>
        </div>
        <div class="zs-kpi zs-kpi-sky">
          <div class="zs-kpi-top"><span>MEDYA &amp; UPLOADS ARŞİVİ</span><span class="zs-kpi-icon">▣</span></div>
          <strong>${Number(sum.media_total || 0).toLocaleString('tr-TR')}</strong>
          <small>/uploads/Yıl/Ay/Gün WebP arşivi aktif</small>
        </div>
        <div class="zs-kpi zs-kpi-mint">
          <div class="zs-kpi-top"><span>SEO &amp; YAPAY ZEKA SKORU</span><span class="zs-kpi-icon">✦</span></div>
          <strong style="color:#6ee7b7">%${sum.avg_seo_score || 95}</strong>
          <small>Schema.org · llms.txt · Sitemap aktif</small>
        </div>
        <div class="zs-kpi zs-kpi-violet">
          <div class="zs-kpi-top"><span>PROJE TALEPLERİ &amp; REFERANS</span><span class="zs-kpi-icon">✉</span></div>
          <strong>${sum.messages_total || 0} <span style="font-size:15px;font-weight:700;color:var(--text-muted)">/ ${sum.clients_count || 62} Marka</span></strong>
          <small>${sum.messages_unread || 0} okunmamış yeni teklif talebi</small>
        </div>
      </div>

      <!-- ROW 4: COMPACT 8-TILE ESSENTIAL SHORTCUTS (+ COLLAPSIBLE MORE DRAWER) -->
      <div class="zs-card zs-glass-launchpad-card">
        <div class="zs-card-head" style="margin-bottom:14px">
          <div>
            <span class="zs-badge-pill">HIZLI STÜDYO AKSİYONLARI</span>
            <h3 style="margin-top:7px;font-size:16.5px">Temel Yönetim Kısayolları</h3>
          </div>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span class="zs-card-sub" style="margin:0">Hızlı arama için <strong>Ctrl + K</strong></span>
            <button type="button" class="zs-btn zs-btn-sm" id="dashToggleMoreTilesBtn">${S.dashShowAllTiles ? 'Daha Az Göster ▴' : 'Diğer Araçlar (8) ▾'}</button>
          </div>
        </div>
        <div class="zs-dash-launchpad">
          <button type="button" class="zs-glass-tile" id="dashOpenPages">
            <span class="zs-glass-tile-ico is-sky">📄</span>
            <span class="zs-glass-tile-body">
              <strong>Sayfa &amp; Vitrin Yönetimi (${pagesCount})</strong>
              <small>Kurumsal, hizmet ve yasal sayfalar</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashNewPost">
            <span class="zs-glass-tile-ico is-emerald">✍️</span>
            <span class="zs-glass-tile-body">
              <strong>Yeni Blog &amp; AI Yazar</strong>
              <small>TR editör + otomatik 4 dil çeviri</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashUploadMedia">
            <span class="zs-glass-tile-ico is-cyan">🖼️</span>
            <span class="zs-glass-tile-body">
              <strong>Medya &amp; Uploads</strong>
              <small>/uploads/Yıl/Ay/Gün &amp; WebP arşivi</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenSeo">
            <span class="zs-glass-tile-ico is-rose">🌐</span>
            <span class="zs-glass-tile-body">
              <strong>Google &amp; SEO Merkezi</strong>
              <small>GA4, GSC, Yorumlar, Harita &amp; GEO</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenProposals">
            <span class="zs-glass-tile-ico is-sky">💼</span>
            <span class="zs-glass-tile-body">
              <strong>Talepler &amp; Online Teklif (${proposalsCnt})</strong>
              <small>CRM Kanban, /teklif/ &amp; Proje Portalı</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenHomeLayout">
            <span class="zs-glass-tile-ico is-teal">⇅</span>
            <span class="zs-glass-tile-body">
              <strong>Ana Sayfa Bölüm Sırası</strong>
              <small>Sürükle-bırak sırala &amp; gizle/göster</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenUptimeTower">
            <span class="zs-glass-tile-ico is-cyan">🛰️</span>
            <span class="zs-glass-tile-body">
              <strong>Uptime, SSL &amp; Yenileme (${utTotalCnt})</strong>
              <small>${utDownCnt > 0 ? `${utDownCnt} erişim sorunu var!` : 'Müşteri siteleri izleme &amp; yenileme'}</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenTimeMachine">
            <span class="zs-glass-tile-ico is-indigo">🛡️</span>
            <span class="zs-glass-tile-body">
              <strong>LiteSpeed, Güvenlik &amp; Yedek</strong>
              <small>LSCache, 20/20 Kalkan &amp; ${revCnt} sürüm</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
        </div>

        <div class="zs-dash-launchpad" id="dashSecondaryLaunchpad" style="margin-top:10px;display:${S.dashShowAllTiles ? 'grid' : 'none'}">
          <button type="button" class="zs-glass-tile" id="dashNewPage">
            <span class="zs-glass-tile-ico is-indigo">➕</span>
            <span class="zs-glass-tile-body">
              <strong>Yeni Sayfa Ekle</strong>
              <small>Görsel bloklarla özel sayfa oluştur</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenContact">
            <span class="zs-glass-tile-ico is-amber">📞</span>
            <span class="zs-glass-tile-body">
              <strong>İletişim Sayfasını Düzenle</strong>
              <small>Telefon, adres, harita ve form</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenMenus">
            <span class="zs-glass-tile-ico is-purple">🔤</span>
            <span class="zs-glass-tile-body">
              <strong>Menüler, Font &amp; Boyutlar</strong>
              <small>Üst/alt menü ve yazı tipi ayarları</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenAppearance">
            <span class="zs-glass-tile-ico is-teal">🎨</span>
            <span class="zs-glass-tile-body">
              <strong>White-Label Tema &amp; Bakım</strong>
              <small>Renk sihirbazı, duyuru &amp; bakım modu</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenAiWriter">
            <span class="zs-glass-tile-ico is-emerald">🤖</span>
            <span class="zs-glass-tile-body">
              <strong>Yapay Zeka Blog &amp; SSS Yazarı</strong>
              <small>Tek tıkla makale, TOC &amp; SSS üret</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenBlogCats">
            <span class="zs-glass-tile-ico is-teal">📂</span>
            <span class="zs-glass-tile-body">
              <strong>Blog Kategorileri (${blogCatsCnt})</strong>
              <small>Ana/alt kategori hiyerarşisi &amp; SEO</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpenVcards">
            <span class="zs-glass-tile-ico is-purple">💳</span>
            <span class="zs-glass-tile-body">
              <strong>Dijital Kartvizit &amp; QR (${vcardsCnt})</strong>
              <small>/kartvizit/ · .vcf rehber &amp; IBAN</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
          <button type="button" class="zs-glass-tile" id="dashOpen404Radar">
            <span class="zs-glass-tile-ico is-amber">🔄</span>
            <span class="zs-glass-tile-body">
              <strong>301 Yönlendirme &amp; Sitemap</strong>
              <small>Kırık linkler oto ana sayfaya gider</small>
            </span>
            <span class="zs-glass-tile-arr">›</span>
          </button>
        </div>
      </div>

      <!-- ROW 4B: COMPACT STUDIO VERSION HISTORY (v1.0 -> v2.0 -> v3.0 -> v4.0 -> v5.0 -> v5.1 -> v5.2) & UNUSED FILE RADAR -->
      <section class="zs-card zs-vh-card" aria-label="Stüdyo Sürüm Geçmişi">
        <div class="zs-card-head" style="margin-bottom:12px;gap:12px">
          <div>
            <span class="zs-badge-pill">📜 STÜDYO SÜRÜM GEÇMİŞİ (v1.0 → v5.2 MİMARİ EVRİM)</span>
            <h3 style="margin-top:6px;font-size:16px">Sürüm Geçmişi — v1’den v5.2’ye Neler Yaptık?</h3>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            ${S.bootMs ? `<span class="zs-sec-badge ok" title="Aktif Veri &amp; Modüler Motor Yanıt Süresi">⚡ ${esc(turboModeLabel)}: ${S.bootMs} ms</span>` : ''}
            ${superMode ? `
              <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="dashOpenOtaHubBtn" title="Merkezi OTA Güncelleme Hub, RSA-2048 Paketleme ve Bağlı Müşteri Filosu">
                🚀 Merkezi OTA &amp; Filo${otaFleetTotal > 0 ? ` (${otaFleetTotal} Site${otaFleetOutdated > 0 ? ` · ${otaFleetOutdated} Bekliyor` : ''})` : ' (v5.2)'} →
              </button>
              <button type="button" class="zs-btn zs-btn-sm ${sqlEngineActive ? 'zs-btn-ai' : 'zs-btn-primary'}" id="dashOpenDbWizardBtn" title="MySQL Veritabanı Bağlantısı, Tek Tıkla Göç ve .SQL Yedekleme">
                ${sqlEngineActive
                  ? `🗄️ ${dbSt.active_engine === 'mysql' ? 'PDO MySQL Aktif' : 'PDO SQLite Aktif'} (${diskJsonTotal === 0 ? '0 Eski JSON' : `${diskJsonTotal} JSON Silinebilir`})`
                  : `🗄️ MySQL Veritabanı &amp; Göç Sihirbazı (${diskJsonTotal.toLocaleString('tr-TR')} JSON → SQL) →`}
              </button>
              <button type="button" class="zs-btn zs-btn-sm ${unusedCnt > 0 ? 'zs-btn-primary' : ''}" id="dashOpenUnusedCleanerBtn" title="Aktif v5.2 ile bağlantısı olmayan eski dosyaları denetleyin">
                ${unusedCnt > 0 ? `🧹 ${unusedCnt} Bağlantısız Dosya Bulundu — Temizle →` : '✓ 0 Bağlantısız Dosya · Dosya Temizleyici'}
              </button>
            ` : ''}
          </div>
        </div>
        <div class="zs-vh-grid">
          ${orderedVerHistory.map(vh => {
            const isAct = vh.status === 'active' || vh.version === 'v5.2';
            const bullets = Array.isArray(vh.items) ? vh.items.slice(0, 4) : [];
            return `
              <article class="zs-vh-stage ${isAct ? 'is-current' : ''}">
                <div class="zs-vh-stage-top">
                  <strong class="zs-vh-ver">${esc(vh.version)}</strong>
                  <span class="zs-vh-tag ${isAct ? 'is-live' : ''}">${esc(vh.badge || '')}</span>
                </div>
                <div class="zs-vh-stage-title">${esc(vh.title || '')}</div>
                <ul class="zs-vh-list">
                  ${bullets.map(b => `<li><span>${isAct ? '✦' : '✓'}</span><span>${esc(b)}</span></li>`).join('')}
                </ul>
              </article>
            `;
          }).join('')}
        </div>
      </section>

      <!-- ROW 5: 2-COLUMN BENTO WORKSPACE -->
      <div class="zs-grid-2">
        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Son Blog Yazıları</h3>
              <p class="zs-card-sub">Tek tıkla IG/Story görseli üretin, mevcut yazıları düzenleyin veya yenisini ekleyin.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm" id="dashAllPosts">Tümünü Gör →</button>
          </div>
          <div class="zs-table-wrap">
            <table class="zs-table">
              <thead><tr><th>Başlık</th><th>Kategori</th><th>SEO</th><th>İşlem</th></tr></thead>
              <tbody>
                ${recentPosts.map(p => `
                  <tr>
                    <td>
                      <strong class="zs-dash-post-title">${esc(p.title)}</strong>
                      <span class="zs-dash-post-url">/blog/${esc(p.slug)}/</span>
                    </td>
                    <td><span class="zs-pill zs-pill-cat">${esc(p.category)}</span></td>
                    <td><span class="zs-seo-score-pill">${p.seo_score || 90}</span></td>
                    <td style="white-space:nowrap">
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-dash-share-post="${esc(p.slug)}" title="1080×1080 Instagram & 1080×1920 Story Görseli + Hazır Metin Üret">📸 IG/Story</button>
                      <button type="button" class="zs-btn zs-btn-sm" data-dash-edit-post="${esc(p.slug)}">Düzenle</button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head">
            <div>
              <h3>Performans, Mobil &amp; Yapay Zeka SEO Sağlığı</h3>
              <p class="zs-card-sub">Saf PHP 8 mimarisi, PageSpeed ve arama motoru hazırlık kontrolü.</p>
            </div>
            <span class="zs-pill zs-pill-pub">100 / 100 HAZIR</span>
          </div>
          <ul class="zs-check-list zs-glass-check-list" style="margin-bottom:20px">
            <li><span class="zs-check-dot ok">✓</span><span><strong>Saf PHP 8 + pages/ Mimarisi:</strong> Sıfır React/Next istemci yükü, anlık sunucu taraflı HTML üretimi ve ~6 KB yalın JS.</span></li>
            <li><span class="zs-check-dot ok">✓</span><span><strong>Temiz /uploads/ Medya Sistemi:</strong> Tüm görseller optimize WebP altyapısı ve 301 yönlendirmeleriyle korunur.</span></li>
            <li><span class="zs-check-dot ok">✓</span><span><strong>Mobil &amp; Masaüstü PageSpeed:</strong> Yerel .woff2 Manrope font önyükleme, LCP görsel önceliği ve Gzip/Brotli önbellek.</span></li>
            <li><span class="zs-check-dot ok">✓</span><span><strong>Yapay Zeka SEO (GEO):</strong> <a href="${esc(resolveUrl('/llms.txt'))}" target="_blank">/llms.txt</a>, <a href="${esc(resolveUrl('/llms-full.txt'))}" target="_blank">/llms-full.txt</a>, Speakable şeması ve AI Bot izin matrisi yayında.</span></li>
            <li><span class="zs-check-dot ok">✓</span><span><strong>4 Dil &amp; Otomatik Blog Çevirisi:</strong> <a href="${esc(resolveUrl('/'))}" target="_blank">TR</a> · <a href="${esc(resolveUrl('/en/'))}" target="_blank">EN</a> · <a href="${esc(resolveUrl('/de/'))}" target="_blank">DE</a> · <a href="${esc(resolveUrl('/ru/'))}" target="_blank">RU</a> yayında; toplu çeviri stüdyosu aktif.</span></li>
            <li><span class="zs-check-dot ok">✓</span><span><strong>Teknik SEO &amp; Sitemap:</strong> <a href="${esc(resolveUrl('/sitemap.xml'))}" target="_blank">/sitemap.xml</a>, <a href="${esc(resolveUrl('/robots.txt'))}" target="_blank">/robots.txt</a> ve JSON-LD @graph şeması aktif.</span></li>
          </ul>

          <div class="zs-card-head" style="margin-top:18px;margin-bottom:10px">
            <h3>Son Gelen Proje Talepleri</h3>
            <button type="button" class="zs-btn zs-btn-sm" id="dashAllMsgs">Gelen Kutusu →</button>
          </div>
          ${recentMsgs.length === 0 ? `<div class="zs-glass-empty-note">Henüz yeni form talebi bulunmuyor. İletişim sayfasından gönderilen tüm mesajlar anında buraya düşer.</div>` :
            recentMsgs.slice(0, 3).map(m => `
              <div class="zs-sort-row zs-glass-msg-row" style="justify-content:space-between">
                <div style="min-width:0">
                  <strong style="color:#ffffff">${esc(m.name)}</strong> <span style="color:var(--text-dim);font-size:12px">· ${esc(m.service)}${m.phone ? ` · 📱 ${esc(m.phone)}` : ''}</span>
                  <div style="font-size:12.5px;color:var(--text-muted);margin-top:2px">${esc((m.message || '').slice(0, 85))}...</div>
                </div>
                <span class="zs-pill ${m.read ? 'zs-pill-cat' : 'zs-pill-pub'}">${m.read ? 'Okundu' : 'Yeni'}</span>
              </div>
            `).join('')
          }
        </div>
      </div>
    `;

    document.getElementById('dashOpenAnalytics').onclick = () => { S.tabs.analytics = 'live'; navigateTo('analytics'); };
    document.getElementById('dashOpenExecReport')?.addEventListener('click', () => { S.tabs.analytics = 'report'; navigateTo('analytics'); });
    document.getElementById('dashHeroOpenSeo')?.addEventListener('click', () => { S.tabs.seo = 'general'; navigateTo('seo'); });
    document.getElementById('dashHeroOpenSocial')?.addEventListener('click', () => { S.tabs.social_media = 'accounts'; navigateTo('social_media'); });
    document.getElementById('dashToggleMoreTilesBtn')?.addEventListener('click', function () {
      S.dashShowAllTiles = !S.dashShowAllTiles;
      const secGrid = document.getElementById('dashSecondaryLaunchpad');
      if (secGrid) secGrid.style.display = S.dashShowAllTiles ? 'grid' : 'none';
      this.textContent = S.dashShowAllTiles ? 'Daha Az Göster ▴' : 'Diğer Araçlar (8) ▾';
    });
    const heroI18nBtn = document.getElementById('dashHeroOpenI18n');
    if (heroI18nBtn) {
      heroI18nBtn.onclick = () => {
        S.tabs.settings = 'i18n';
        navigateTo('settings');
      };
    }
    document.getElementById('dashOpenHomeEdit').onclick = () => openUnifiedPageEditor('anasayfa', 'content');
    document.getElementById('dashOpenHomeLayout').onclick = () => navigateTo('home_layout');
    document.getElementById('dashOpenPages').onclick = () => navigateTo('pages', () => { S.pages.mode = 'list'; });
    document.getElementById('dashOpenContact').onclick = () => navigateTo('contact');
    document.getElementById('dashNewPage').onclick = () => startNewUnifiedCustomPage();
    document.getElementById('dashNewPost').onclick = () => navigateTo('blog', () => startNewBlogPost());
    document.getElementById('dashUploadMedia').onclick = () => navigateTo('media');
    document.getElementById('dashOpenMenus').onclick = () => navigateTo('menus');
    document.getElementById('dashOpenSeo').onclick = () => navigateTo('seo');
    document.getElementById('dashOpenAppearance').onclick = () => { S.tabs.appearance = 'theme'; navigateTo('appearance'); };
    document.getElementById('dashOpenAiWriter').onclick = () => { S.openAiWriterOnNew = true; navigateTo('blog', () => startNewBlogPost()); };
    const nbTileBtn = document.getElementById('dashOpenNewsBotTile');
    if (nbTileBtn) nbTileBtn.onclick = () => { S.blog.mode = 'news_bot'; navigateTo('blog'); };
    const nbQueueJump = document.getElementById('dashOpenNewsQueueBtn');
    if (nbQueueJump) nbQueueJump.onclick = () => { S.blog.mode = 'news_bot'; navigateTo('blog'); };
    document.getElementById('dashOpenUptimeTower')?.addEventListener('click', () => navigateTo('uptime_tower'));
    document.getElementById('dashOpenVcards')?.addEventListener('click', () => navigateTo('vcards'));
    document.getElementById('dashOpenUptimeAlertBtn')?.addEventListener('click', () => navigateTo('uptime_tower'));
    document.getElementById('dashOpenUnusedAlertBtn')?.addEventListener('click', () => runAdminJump('performance:unused_files'));
    document.getElementById('dashOpenUnusedCleanerBtn')?.addEventListener('click', () => runAdminJump('performance:unused_files'));
    document.getElementById('dashOpenDbWizardBtn')?.addEventListener('click', () => runAdminJump('settings:database'));
    document.getElementById('dashOpenOtaHubBtn')?.addEventListener('click', () => runAdminJump('ota_hub:fleet'));
    document.getElementById('dashHeroOtaChip')?.addEventListener('click', () => runAdminJump('ota_hub:fleet'));
    document.getElementById('dashOpenOtaClientAlertBtn')?.addEventListener('click', () => runAdminJump('ota_hub:client'));
    document.getElementById('dashOpen404Radar').onclick = () => { S.tabs.seo = 'redirects'; navigateTo('seo'); };
    document.getElementById('dashOpenTimeMachine').onclick = () => { S.tabs.settings = 'revisions'; navigateTo('settings'); };
    document.getElementById('dashOpenBlogCatsHero')?.addEventListener('click', () => navigateTo('blog_categories'));
    document.getElementById('dashOpenBlogCats')?.addEventListener('click', () => navigateTo('blog_categories'));
    document.getElementById('dashOpenProposals')?.addEventListener('click', () => { S.editingProposal = null; navigateTo('proposals'); });
    const mntJump = document.getElementById('dashManageMaintBtn');
    if (mntJump) {
      mntJump.onclick = () => { S.tabs.appearance = 'maintenance'; navigateTo('appearance'); };
    }
    document.getElementById('dashAllPosts').onclick = () => navigateTo('blog', () => { S.blog.mode = 'list'; });
    document.getElementById('dashAllMsgs').onclick = () => navigateTo('messages');
    ROOT.querySelectorAll('[data-dash-edit-post]').forEach(btn => {
      btn.onclick = () => openBlogPostForEdit(btn.getAttribute('data-dash-edit-post'));
    });
    ROOT.querySelectorAll('[data-dash-share-post]').forEach(btn => {
      btn.onclick = () => openSocialShareStudio({ kind: 'blog', targetKey: btn.getAttribute('data-dash-share-post') });
    });
    if (typeof window.__enhanceClubDashboard === 'function') {
      try { window.__enhanceClubDashboard(window.__STUDIO_CORE); } catch (_) {}
    }
    SECTOR_DASHBOARD_HOOKS.forEach(fn => {
      try { fn(window.__STUDIO_CORE); } catch (_) {}
    });
  }

  /* ======================== MODULE 1A: VISITOR ANALYTICS & LIVE TRAFFIC STUDIO ======================== */
  async function refreshAnalyticsReport(daysOverride, silent) {
    const days = Number(daysOverride || S.analytics.rangeDays || 30);
    S.analytics.rangeDays = days;
    if (S.analytics.loading) return;
    S.analytics.loading = true;
    try {
      const res = await api('analytics_stats', { days });
      if (res.report) {
        S.analytics.report = res.report;
        if (S.summary && days === 30) {
          S.summary.analytics = res.report;
        }
        updateSidebarBadges();
        if (S.view === 'analytics') {
          paintAnalyticsDom();
        } else if (S.view === 'dashboard') {
          const liveTxt = `${Number(res.report.live?.count || 0).toLocaleString('tr-TR')} Kişi`;
          const dashLiveEl = document.querySelector('[data-dash-live-cnt]');
          if (dashLiveEl) dashLiveEl.textContent = liveTxt;
          const clkLiveEl = document.querySelector('[data-dash-clock-live]');
          if (clkLiveEl) clkLiveEl.textContent = liveTxt;
        }
      }
    } catch (err) {
      if (!silent) toast(err.message || 'İstatistik verisi alınamadı.', true);
    } finally {
      S.analytics.loading = false;
    }
  }

  function renderStatRowsHtml(items, labelFn, countFn, pctFn, emptyText) {
    if (!items || items.length === 0) {
      return `<div style="padding:18px;text-align:center;color:var(--text-dim);font-size:12.5px;border-radius:10px;background:rgba(6,13,17,.55)">${esc(emptyText)}</div>`;
    }
    return `
      <div class="zs-an-stat-list">
        ${items.map(it => {
          const pct = Math.max(0, Math.min(100, Number(pctFn(it) || 0)));
          return `
            <div class="zs-an-stat-row">
              <div class="zs-an-stat-head">
                <span class="zs-an-stat-label">${labelFn(it)}</span>
                <span class="zs-an-stat-val"><strong>${Number(countFn(it) || 0).toLocaleString('tr-TR')}</strong> (%${pct})</span>
              </div>
              <div class="zs-an-stat-bar"><i style="width:${Math.max(pct, pct > 0 ? 3 : 0)}%"></i></div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  function formatDurationShort(sec) {
    const s = Math.max(1, Number(sec || 1));
    if (s < 60) return `${s} sn`;
    const m = Math.floor(s / 60);
    const r = s % 60;
    return r > 0 ? `${m} dk ${r} sn` : `${m} dk`;
  }

  function renderAnalyticsModeTabsHtml(activeMode) {
    return `
      <div class="zs-tabs zs-an-mode-tabs no-print" style="margin-bottom:16px">
        <button type="button" class="zs-tab ${activeMode !== 'report' ? 'active' : ''}" data-an-mode="live">📊 Canlı Trafik &amp; Ziyaretçi Analitiği</button>
        <button type="button" class="zs-tab ${activeMode === 'report' ? 'active' : ''}" data-an-mode="report">📄 Aylık Kurumsal Performans &amp; SEO Raporu (A4 PDF)</button>
      </div>
    `;
  }

  function bindAnalyticsModeTabs() {
    ROOT.querySelectorAll('[data-an-mode]').forEach(btn => {
      btn.onclick = () => {
        const nextMode = btn.getAttribute('data-an-mode') || 'live';
        if (S.tabs.analytics === nextMode) return;
        S.tabs.analytics = nextMode;
        syncAdminUrl('push');
        if (nextMode === 'report') {
          renderExecutiveMonthlyReportView(true);
        } else {
          paintAnalyticsDom();
          refreshAnalyticsReport(S.analytics.rangeDays || 30, true);
        }
      };
    });
  }

  async function loadExecutiveReportData(daysOverride, silent) {
    const days = Number(daysOverride || S.analytics.execDays || 30);
    S.analytics.execDays = days;
    if (S.analytics.execLoading) return;
    S.analytics.execLoading = true;
    try {
      const res = await api('executive_report_get', { days });
      if (res.executive_report_data) {
        S.analytics.execReport = res.executive_report_data;
      }
      if (S.view === 'analytics' && S.tabs.analytics === 'report') {
        renderExecutiveMonthlyReportView(false);
      }
    } catch (err) {
      if (!silent) toast(err.message || 'Yönetici raporu verisi alınamadı.', true);
    } finally {
      S.analytics.execLoading = false;
    }
  }

  function buildExecutiveSvgChart(series, primaryColor, accentColor) {
    const pts = Array.isArray(series) && series.length > 0
      ? series
      : Array.from({ length: 14 }, (_, idx) => ({ label: `Gün ${idx + 1}`, views: 0, uniques: 0 }));
    const W = 860;
    const H = 192;
    const padL = 42;
    const padR = 16;
    const padT = 18;
    const padB = 30;
    const plotW = W - padL - padR;
    const plotH = H - padT - padB;
    const maxV = Math.max(4, ...pts.map(p => Math.max(Number(p.views || 0), Number(p.uniques || 0))));
    const n = pts.length;
    const colW = plotW / Math.max(1, n);
    const barW = Math.max(4, Math.min(20, colW * 0.56));

    const gridLines = [0, 0.25, 0.5, 0.75, 1].map(fr => {
      const y = (padT + plotH * (1 - fr)).toFixed(1);
      const val = Math.round(maxV * fr).toLocaleString('tr-TR');
      return `
        <line x1="${padL}" y1="${y}" x2="${W - padR}" y2="${y}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="${fr === 0 ? '0' : '3,3'}"/>
        <text x="${padL - 8}" y="${Number(y) + 4}" text-anchor="end" font-size="10" font-weight="600" fill="#64748b">${esc(val)}</text>
      `;
    }).join('');

    const barsSvg = pts.map((pt, i) => {
      const v = Number(pt.views || 0);
      const u = Number(pt.uniques || 0);
      const cx = padL + i * colW + colW / 2;
      const vh = v > 0 ? Math.max(3, (v / maxV) * plotH) : 1.5;
      const uh = u > 0 ? Math.max(2.5, (u / maxV) * plotH) : 0;
      const vy = padT + plotH - vh;
      const uy = padT + plotH - uh;
      return `
        <g>
          <rect x="${(cx - barW / 2).toFixed(1)}" y="${vy.toFixed(1)}" width="${barW.toFixed(1)}" height="${vh.toFixed(1)}" rx="3" fill="${esc(accentColor)}" fill-opacity="${v > 0 ? '0.26' : '0.12'}"/>
          ${uh > 0 ? `<rect x="${(cx - barW * 0.36).toFixed(1)}" y="${uy.toFixed(1)}" width="${(barW * 0.72).toFixed(1)}" height="${uh.toFixed(1)}" rx="2.5" fill="${esc(primaryColor)}"/>` : ''}
        </g>
      `;
    }).join('');

    const lineCoords = pts.map((pt, i) => {
      const v = Number(pt.views || 0);
      const cx = padL + i * colW + colW / 2;
      const vy = padT + plotH - ((v / maxV) * plotH);
      return `${cx.toFixed(1)},${vy.toFixed(1)}`;
    });
    const polylineSvg = lineCoords.length > 1
      ? `<polyline fill="none" stroke="${esc(accentColor)}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="${lineCoords.join(' ')}"/>`
      : '';

    const firstLbl = pts[0]?.label || '';
    const midLbl = pts.length > 2 ? (pts[Math.floor(pts.length / 2)]?.label || '') : '';
    const lastLbl = pts[pts.length - 1]?.label || '';

    return `
      <svg viewBox="0 0 ${W} ${H}" class="zs-exec-svg-chart" role="img" aria-label="Günlük ziyaretçi ve sayfa görüntüleme grafiği">
        ${gridLines}
        ${barsSvg}
        ${polylineSvg}
        <text x="${padL}" y="${H - 8}" text-anchor="start" font-size="10.5" font-weight="600" fill="#475569">${esc(firstLbl)}</text>
        <text x="${(padL + plotW / 2).toFixed(1)}" y="${H - 8}" text-anchor="middle" font-size="10.5" font-weight="600" fill="#475569">${esc(midLbl)}</text>
        <text x="${W - padR}" y="${H - 8}" text-anchor="end" font-size="10.5" font-weight="700" fill="#0f172a">Bugün (${esc(lastLbl)})</text>
      </svg>
    `;
  }

  function renderExecutiveMonthlyReportView(fetchIfMissing) {
    if (S.view !== 'analytics' || S.tabs.analytics !== 'report') return;
    const data = S.analytics.execReport;
    if (!data) {
      ROOT.innerHTML = `
        ${renderAnalyticsModeTabsHtml('report')}
        <div class="zs-card"><p style="color:var(--text-muted)">⏳ Aylık kurumsal müşteri performans ve SEO raporu hazırlanıyor...</p></div>
      `;
      bindAnalyticsModeTabs();
      if (fetchIfMissing !== false) {
        loadExecutiveReportData(S.analytics.execDays || 30, false);
      }
      return;
    }

    const rDays = Number(data.range_days || S.analytics.execDays || 30);
    const cfg = data.config || {};
    const brand = data.brand || {};
    const trf = data.traffic || {};
    const cnv = data.conversions || {};
    const seo = data.seo_content || {};
    const srch = data.search_insights || {};
    const inf = data.infrastructure || {};

    const primaryHex = brand.primary_color || '#0d9488';
    const accentHex = brand.accent_color || '#0284c7';
    const activeNote = (cfg.executive_note && cfg.executive_note.trim() !== '')
      ? cfg.executive_note
      : (data.auto_summary_suggestion || '');

    const growthBadge = (trf.growth_pct !== null && trf.growth_pct !== undefined)
      ? `${trf.growth_pct >= 0 ? '+' : ''}%${trf.growth_pct} önceki döneme göre`
      : `Zirve gün: ${trf.peak_day || '-'} (${Number(trf.peak_uniques || 0)} kişi)`;

    ROOT.innerHTML = `
      ${renderAnalyticsModeTabsHtml('report')}

      <div class="zs-card zs-exec-toolbar no-print" style="margin-bottom:20px">
        <div class="zs-card-head" style="margin-bottom:14px">
          <div>
            <span class="zs-badge-pill">BEYAZ ETİKETLİ (WHITE-LABEL) A4 YÖNETİCİ &amp; MÜŞTERİ RAPORU</span>
            <h3 style="margin-top:6px">📄 Kurumsal Performans, Trafik &amp; SEO Raporu Stüdyosu</h3>
            <p class="zs-card-sub">Rapor başlığını, sunulan müşteri ünvanını ve yönetici değerlendirmesini özelleştirin; tek tıkla <strong>A4 PDF</strong> olarak indirip WhatsApp veya e-posta ile paylaşın.</p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <div class="zs-an-range-pills" role="tablist" aria-label="Rapor dönemi">
              <button type="button" class="zs-an-range-btn ${rDays === 7 ? 'active' : ''}" data-exec-days="7">Son 7 Gün</button>
              <button type="button" class="zs-an-range-btn ${rDays === 30 ? 'active' : ''}" data-exec-days="30">Son 30 Gün (Aylık)</button>
              <button type="button" class="zs-an-range-btn ${rDays === 90 ? 'active' : ''}" data-exec-days="90">Son 90 Gün</button>
            </div>
            <button type="button" class="zs-btn zs-btn-primary" id="execPrintPdfBtn">🖨️ A4 PDF Olarak Yazdır / İndir</button>
          </div>
        </div>

        <div class="zs-grid-3" style="margin-bottom:12px">
          <div class="zs-field" style="margin-bottom:0">
            <label>Rapor Üst Başlığı</label>
            <input type="text" id="execTitleInp" class="zs-input" value="${esc(cfg.report_title || '')}" placeholder="Aylık Dijital Performans & SEO Özeti"/>
          </div>
          <div class="zs-field" style="margin-bottom:0">
            <label>Sunulan Kurum / Müşteri Ünvanı</label>
            <input type="text" id="execClientInp" class="zs-input" value="${esc(cfg.client_name || '')}" placeholder="Müşteri / Firma Adı"/>
          </div>
          <div class="zs-field" style="margin-bottom:0">
            <label>Hazırlayan Ajans / Birim İmzası</label>
            <input type="text" id="execPreparedInp" class="zs-input" value="${esc(cfg.prepared_by || '')}" placeholder="Dijital Stüdyo — Strateji Ekibi"/>
          </div>
        </div>

        <div class="zs-field" style="margin-bottom:12px">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
            <label style="margin:0">Yönetici Değerlendirmesi &amp; Sonraki Ay Aksiyon Planı</label>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="execAutoNoteBtn">✦ Verilerden Otomatik Yönetici Özeti Oluştur</button>
          </div>
          <textarea id="execNoteInp" class="zs-textarea" rows="3" placeholder="Dönem özeti ve gelecek ay hedefleri...">${esc(activeNote)}</textarea>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
          <span style="font-size:12px;color:var(--text-muted)">💡 Yazdırma ekranında <strong>Hedef: PDF Olarak Kaydet</strong> seçeneğiyle mürekkep dostu kurumsal A4 belgesi üretilir.</span>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-sm" id="execRefreshBtn">↻ Verileri Tazele</button>
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="execSaveCfgBtn">✓ Rapor Şablonunu Kaydet</button>
          </div>
        </div>
      </div>

      <!-- PRINT-READY WHITE-LABEL CORPORATE A4 DOCUMENT -->
      <article class="zs-exec-a4-sheet" id="zsExecA4Sheet" style="--exec-primary:${esc(primaryHex)};--exec-accent:${esc(accentHex)}">
        <div class="zs-exec-ribbon"></div>

        <header class="zs-exec-header">
          <div class="zs-exec-brand-left">
            <div class="zs-exec-logo-pill">
              <img src="${esc(resolveUrl(brand.logo_light_url || brand.logo_url || '/icon.png'))}" alt="${esc(brand.site_name || 'Logo')}"/>
            </div>
            <div>
              <div class="zs-exec-doc-eyebrow" id="execPreviewPrepared">${esc(cfg.prepared_by || '')}</div>
              <h1 class="zs-exec-doc-title" id="execPreviewTitle">${esc(cfg.report_title || 'Aylık Dijital Performans & SEO Özeti')}</h1>
              <div class="zs-exec-doc-client">Sunulan Kurum: <strong id="execPreviewClient">${esc(cfg.client_name || brand.legal_name || '')}</strong> · <span>${esc(brand.domain || '')}</span></div>
            </div>
          </div>
          <div class="zs-exec-meta-box">
            <div><span>RAPOR KODU</span><strong>${esc(data.report_code || '')}</strong></div>
            <div><span>KAPSANAN DÖNEM (${rDays} GÜN)</span><strong>${esc(data.period_start_tr || '')} – ${esc(data.period_end_tr || '')}</strong></div>
            <div><span>OLUŞTURULMA TARİHİ</span><strong>${esc(data.generated_at_tr || '')}</strong></div>
          </div>
        </header>

        <!-- 6 EXECUTIVE KPI SCORECARDS -->
        <section class="zs-exec-kpi-grid">
          <div class="zs-exec-kpi">
            <span class="zs-exec-kpi-lbl">TEKİL ZİYARETÇİ (${rDays} GÜN)</span>
            <strong class="zs-exec-kpi-val">${Number(trf.uniques || 0).toLocaleString('tr-TR')}</strong>
            <small class="zs-exec-kpi-sub">${esc(growthBadge)}</small>
          </div>
          <div class="zs-exec-kpi">
            <span class="zs-exec-kpi-lbl">SAYFA GÖRÜNTÜLEME</span>
            <strong class="zs-exec-kpi-val">${Number(trf.views || 0).toLocaleString('tr-TR')}</strong>
            <small class="zs-exec-kpi-sub">Ziyaretçi başı ort. ${Number(trf.avg_views_per_visitor || 1)} sayfa</small>
          </div>
          <div class="zs-exec-kpi">
            <span class="zs-exec-kpi-lbl">DÖNÜŞÜM &amp; PROJE TALEBİ</span>
            <strong class="zs-exec-kpi-val">${Number(cnv.period_leads || 0).toLocaleString('tr-TR')} <span style="font-size:13px;font-weight:600;color:#64748b">Talep</span></strong>
            <small class="zs-exec-kpi-sub">${Number(cnv.proposals_total || 0)} Online Teklif (${Number(cnv.proposals_approved || 0)} Onaylı)</small>
          </div>
          <div class="zs-exec-kpi">
            <span class="zs-exec-kpi-lbl">SİTE SEO &amp; İÇERİK SKORU</span>
            <strong class="zs-exec-kpi-val" style="color:#059669">%${Number(seo.avg_seo_score || 96)}</strong>
            <small class="zs-exec-kpi-sub">${Number(seo.published_posts || 0).toLocaleString('tr-TR')} yayında makale · %${Number(seo.multilingual_pct || 100)} 4 dil</small>
          </div>
          <div class="zs-exec-kpi">
            <span class="zs-exec-kpi-lbl">UPTIME &amp; SSL SÜREKLİLİĞİ</span>
            <strong class="zs-exec-kpi-val" style="color:#0284c7">%${Number(inf.uptime_pct || 99.98).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
            <small class="zs-exec-kpi-sub">${inf.ssl_valid ? '✓ SSL Geçerli' : '⚠ SSL Kontrol'} · KVKK Sıfır Çerez</small>
          </div>
          <div class="zs-exec-kpi">
            <span class="zs-exec-kpi-lbl">ARAMA &amp; AI BOT TARAMASI</span>
            <strong class="zs-exec-kpi-val">${Number(trf.bot_hits || 0).toLocaleString('tr-TR')}</strong>
            <small class="zs-exec-kpi-sub">Google, Bing &amp; Yapay Zeka Bot İndeksi</small>
          </div>
        </section>

        <!-- VECTOR SVG DAILY TRAFFIC CHART -->
        <section class="zs-exec-section">
          <div class="zs-exec-sec-head">
            <h2>📈 Günlük Tekil Ziyaretçi &amp; Sayfa Görüntüleme Eğilimi (${rDays} Günlük Vektörel Grafik)</h2>
            <div class="zs-exec-legend">
              <span><i style="background:${esc(primaryHex)}"></i> Tekil Ziyaretçi</span>
              <span><i style="background:${esc(accentHex)};opacity:.45"></i> Sayfa Görüntüleme</span>
            </div>
          </div>
          <div class="zs-exec-chart-wrap">
            ${buildExecutiveSvgChart(trf.daily_series || [], primaryHex, accentHex)}
          </div>
        </section>

        <!-- 2-COLUMN ACQUISITION & TOP PAGES -->
        <section class="zs-exec-two-col">
          <div class="zs-exec-panel">
            <h3>🧭 Trafik Kazanım Kanalları &amp; Cihaz Dağılımı</h3>
            ${(!trf.referrers || trf.referrers.length === 0) ? `
              <p class="zs-exec-empty">Seçili dönemde yönlendiren kanal kaydı bekleniyor; doğrudan ve organik girişler aktif olarak izlenmektedir.</p>
            ` : `
              <div class="zs-exec-bar-list">
                ${trf.referrers.map(r => `
                  <div class="zs-exec-bar-row">
                    <div class="zs-exec-bar-top">
                      <strong>${esc(r.source)}</strong>
                      <span>${Number(r.count || 0).toLocaleString('tr-TR')} (%${Number(r.pct || 0)})</span>
                    </div>
                    <div class="zs-exec-bar-track"><div style="width:${Math.max(4, Number(r.pct || 0))}%"></div></div>
                  </div>
                `).join('')}
              </div>
            `}
            ${(trf.devices && trf.devices.length > 0) ? `
              <div class="zs-exec-chip-row">
                ${trf.devices.map(d => `<span class="zs-exec-chip"><strong>${esc(d.label)}:</strong> %${Number(d.pct || 0)} (${Number(d.count || 0)})</span>`).join('')}
              </div>
            ` : ''}
          </div>

          <div class="zs-exec-panel">
            <h3>🔥 En Çok İlgi Gören Sayfalar &amp; Site İçi Aramalar</h3>
            ${(!trf.top_pages || trf.top_pages.length === 0) ? `
              <p class="zs-exec-empty">Ana sayfa, kurumsal hizmet sayfaları ve çok dilli blog makaleleri yayında ve dizine açık durumdadır.</p>
            ` : `
              <table class="zs-exec-table">
                <thead><tr><th>Sayfa Başlığı / URL</th><th style="text-align:right">Görüntüleme</th><th style="text-align:right">Pay</th></tr></thead>
                <tbody>
                  ${trf.top_pages.slice(0, 6).map(pg => `
                    <tr>
                      <td>
                        <strong>${esc(pg.title || pg.path)}</strong>
                        <small>${esc(pg.path)}</small>
                      </td>
                      <td style="text-align:right;font-weight:700">${Number(pg.views || 0).toLocaleString('tr-TR')}</td>
                      <td style="text-align:right;color:#475569">%${Number(pg.pct || 0)}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            `}
            ${(srch.top_queries && srch.top_queries.length > 0) ? `
              <div class="zs-exec-subnote">
                <strong>🔍 Öne Çıkan Site İçi Aramalar (${Number(srch.total_searches || 0)} arama):</strong>
                ${srch.top_queries.map(q => `<span class="zs-exec-chip">${esc(q.term)} (${Number(q.count || 0)})</span>`).join(' ')}
              </div>
            ` : ''}
          </div>
        </section>

        <!-- 2-COLUMN CONVERSION CHANNELS & TECHNICAL SEO COMPLIANCE -->
        <section class="zs-exec-two-col">
          <div class="zs-exec-panel">
            <h3>💼 Müşteri Kazanım Kanalları &amp; Online Teklif Performansı</h3>
            <table class="zs-exec-table">
              <thead><tr><th>Dönüşüm / Talep Kanalı</th><th style="text-align:right">Dönem İçi Adet</th></tr></thead>
              <tbody>
                ${(cnv.by_source || []).map(s => `
                  <tr>
                    <td>${esc(s.label)}</td>
                    <td style="text-align:right;font-weight:700">${Number(s.count || 0).toLocaleString('tr-TR')}</td>
                  </tr>
                `).join('')}
                <tr>
                  <td><strong>İnteraktif Online Müşteri Teklifleri</strong> <small>(Açılan: ${Number(cnv.proposals_viewed || 0)} · Onaylanan: ${Number(cnv.proposals_approved || 0)})</small></td>
                  <td style="text-align:right;font-weight:800;color:#0f766e">${Number(cnv.proposals_total || 0)} Teklif</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="zs-exec-panel">
            <h3>🛡️ Teknik SEO, Yapay Zeka (GEO) &amp; Altyapı Denetimi</h3>
            <ul class="zs-exec-checklist">
              <li><span>✓</span> <div><strong>Yapay Zeka SEO (GEO) &amp; Schema.org:</strong> <code>/sitemap.xml</code>, <code>/llms.txt</code>, <code>ai-catalog.json</code> ve Speakable şemaları aktif.</div></li>
              <li><span>✓</span> <div><strong>Çok Dilli Küresel Yayın (4 Dil):</strong> Toplam <strong>${Number(seo.published_posts || 0).toLocaleString('tr-TR')}</strong> makalenin <strong>${Number(seo.translated_posts || 0).toLocaleString('tr-TR')}</strong> adedi TR · EN · DE · RU dillerinde yayında.</div></li>
              <li><span>✓</span> <div><strong>Görsel &amp; Hız Optimizasyonu:</strong> Yeni nesil WebP/AVIF oranı <strong>%${Number(seo.webp_ratio_pct || 0)}</strong> · Aktif 301 yönlendirme: <strong>${Number(seo.redirects_count || 0)}</strong> kural.</div></li>
              <li><span>✓</span> <div><strong>KVKK Tam Uyumlu Çerezsiz Mimari:</strong> Açık onay öncesi sıfır çerez (<code>Zero-Cookie Load</code>) ve SSL/TLS koruması aktif.</div></li>
            </ul>
          </div>
        </section>

        <!-- EXECUTIVE SUMMARY & CONSULTANT SIGNATURE -->
        <section class="zs-exec-note-section">
          <h3>📋 Yönetici Değerlendirmesi &amp; Sonraki Dönem Aksiyon Planı</h3>
          <div class="zs-exec-note-body" id="execPreviewNote">${esc(activeNote).replace(/\n/g, '<br/>')}</div>
        </section>

        <footer class="zs-exec-footer">
          <div>
            <strong>${esc(brand.legal_name || brand.site_name || '')}</strong>
            <span>${esc(brand.canonical_url || '')}${brand.phone ? ' · ' + esc(brand.phone) : ''}${brand.email ? ' · ' + esc(brand.email) : ''}</span>
          </div>
          <div style="text-align:right">
            <strong id="execFooterSign">${esc(cfg.prepared_by || '')}</strong>
            <span>Belge Referansı: ${esc(data.report_code || '')} · Doğrulanmış Kurumsal Performans Özeti</span>
          </div>
        </footer>
      </article>
    `;

    bindAnalyticsModeTabs();

    // Live 0ms preview bindings for White-Label customization inputs
    const titleInp = document.getElementById('execTitleInp');
    const clientInp = document.getElementById('execClientInp');
    const prepInp = document.getElementById('execPreparedInp');
    const noteInp = document.getElementById('execNoteInp');

    titleInp?.addEventListener('input', () => {
      const el = document.getElementById('execPreviewTitle');
      if (el) el.textContent = titleInp.value || 'Aylık Dijital Performans & SEO Özeti';
    });
    clientInp?.addEventListener('input', () => {
      const el = document.getElementById('execPreviewClient');
      if (el) el.textContent = clientInp.value || brand.legal_name || '';
    });
    prepInp?.addEventListener('input', () => {
      const el1 = document.getElementById('execPreviewPrepared');
      const el2 = document.getElementById('execFooterSign');
      if (el1) el1.textContent = prepInp.value;
      if (el2) el2.textContent = prepInp.value;
    });
    noteInp?.addEventListener('input', () => {
      const el = document.getElementById('execPreviewNote');
      if (el) el.innerHTML = esc(noteInp.value).replace(/\n/g, '<br/>');
    });

    document.getElementById('execAutoNoteBtn')?.addEventListener('click', () => {
      if (noteInp) {
        noteInp.value = data.auto_summary_suggestion || '';
        noteInp.dispatchEvent(new Event('input'));
        toast('Güncel trafik, SEO ve dönüşüm verilerinden yönetici özeti oluşturuldu.');
      }
    });

    ROOT.querySelectorAll('[data-exec-days]').forEach(btn => {
      btn.onclick = () => {
        const d = Number(btn.getAttribute('data-exec-days') || 30);
        loadExecutiveReportData(d, false);
      };
    });

    document.getElementById('execRefreshBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      await loadExecutiveReportData(rDays, false);
      toast('Rapor metrikleri yenilendi.');
    });

    document.getElementById('execSaveCfgBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      try {
        const res = await api('executive_report_save', {
          days: rDays,
          executive_report: {
            report_title: titleInp?.value || '',
            client_name: clientInp?.value || '',
            prepared_by: prepInp?.value || '',
            executive_note: noteInp?.value || ''
          }
        });
        if (S.store && res.executive_report) {
          S.store.executive_report = res.executive_report;
        }
        if (res.executive_report_data) {
          S.analytics.execReport = res.executive_report_data;
        }
        toast(res.message);
        renderExecutiveMonthlyReportView(false);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });

    document.getElementById('execPrintPdfBtn')?.addEventListener('click', () => {
      document.body.classList.add('is-printing-exec-report');
      const cleanup = () => {
        document.body.classList.remove('is-printing-exec-report');
        window.removeEventListener('afterprint', cleanup);
      };
      window.addEventListener('afterprint', cleanup);
      setTimeout(() => {
        window.print();
        setTimeout(cleanup, 800);
      }, 60);
    });
  }

  function paintAnalyticsDom() {
    if (S.view !== 'analytics') return;
    if (S.tabs.analytics === 'report') {
      renderExecutiveMonthlyReportView(true);
      return;
    }
    const rep = S.analytics.report || S.summary?.analytics;
    if (!rep) {
      ROOT.innerHTML = `${renderAnalyticsModeTabsHtml('live')}<div class="zs-card"><p style="color:var(--text-muted)">Ziyaretçi istatistikleri yükleniyor...</p></div>`;
      bindAnalyticsModeTabs();
      return;
    }

    const rDays = Number(rep.range_days || S.analytics.rangeDays || 30);
    const rangeName = rDays === 1 ? 'Bugün' : `Son ${rDays} Gün`;
    const live = rep.live || { count: 0, active_pages: [], visitors: [] };
    const kpis = rep.kpis || {};
    const d30 = kpis.days_30 || {};
    const rng = kpis.range || {};
    const today = kpis.today || {};
    const yest = kpis.yesterday || {};
    const allT = kpis.all_time || {};
    const series = rep.daily_series || [];
    const maxViews = Math.max(1, ...series.map(d => Number(d.views || 0)));
    const firstDateLbl = series.length ? series[0].label : '';
    const midDateLbl = series.length > 2 ? series[Math.floor(series.length / 2)].label : '';
    const lastDateLbl = series.length ? series[series.length - 1].label : '';

    const hours = Array.isArray(rep.hourly_today) && rep.hourly_today.length === 24 ? rep.hourly_today : Array(24).fill(0);
    const maxHour = Math.max(1, ...hours);

    const growthTxt = (d30.growth_pct !== null && d30.growth_pct !== undefined)
      ? `${d30.growth_pct >= 0 ? '▲ +%' : '▼ %'}${Math.abs(d30.growth_pct)} önceki 30 güne göre`
      : `Bugün: ${Number(today.uniques || 0).toLocaleString('tr-TR')} kişi · Dün: ${Number(yest.uniques || 0).toLocaleString('tr-TR')} kişi`;

    const devEmoji = d => d === 'mobile' ? '📱 Cep' : (d === 'tablet' ? '📟 Tablet' : '💻 Masaüstü');

    ROOT.innerHTML = `
      ${renderAnalyticsModeTabsHtml('live')}
      <div class="zs-an-period-bar">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <div class="zs-an-range-pills" role="tablist" aria-label="Zaman aralığı">
            <button type="button" class="zs-an-range-btn ${rDays === 1 ? 'active' : ''}" data-an-range="1">Bugün</button>
            <button type="button" class="zs-an-range-btn ${rDays === 7 ? 'active' : ''}" data-an-range="7">Son 7 Gün</button>
            <button type="button" class="zs-an-range-btn ${rDays === 30 ? 'active' : ''}" data-an-range="30">Son 30 Gün</button>
            <button type="button" class="zs-an-range-btn ${rDays === 90 ? 'active' : ''}" data-an-range="90">Son 90 Gün</button>
          </div>
          <span class="zs-pill zs-pill-cat" style="font-size:11px">🔒 Çerezsiz &amp; %100 KVKK Uyumlu Gerçek Ziyaretçi Sayacı · Son güncelleme: ${esc(rep.generated_at || '')}</span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="anOpenExecReportBtn">📄 A4 Müşteri Raporu (PDF)</button>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="anRefreshBtn">↻ Şimdi Yenile</button>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="anResetBtn" title="Test verilerini veya geçmişi temizle">🗑 İstatistikleri Sıfırla</button>
        </div>
      </div>

      <div class="zs-kpi-grid" style="margin-bottom:18px">
        <div class="zs-kpi" style="border-color:${live.count > 0 ? 'rgba(52,211,153,0.45)' : 'var(--border-subtle)'};background:${live.count > 0 ? 'linear-gradient(145deg,rgba(16,185,129,0.16),rgba(9,20,26,0.95))' : ''}">
          <div class="zs-kpi-top">
            <span>ŞU AN SİTEDE (ANLIK)</span>
            <span class="zs-live-orb ${live.count > 0 ? '' : 'idle'}"></span>
          </div>
          <strong style="color:${live.count > 0 ? '#6ee7b7' : '#f8fafc'}" id="anLiveCountVal">${Number(live.count || 0).toLocaleString('tr-TR')} <span style="font-size:15px;font-weight:600;color:var(--text-muted)">Kişi</span></strong>
          <small>${live.active_pages?.length ? `${live.active_pages.length} farklı sayfada aktif gezinme (son 3 dk)` : 'Son 3 dakikada sitede açık sekme takibi'}</small>
        </div>

        <div class="zs-kpi">
          <div class="zs-kpi-top"><span>SON 30 GÜN GİRENLER (TEKİL)</span><span class="zs-kpi-icon">👥</span></div>
          <strong style="color:#5eead4" id="anDays30UniquesVal">${Number(d30.uniques || 0).toLocaleString('tr-TR')} <span style="font-size:14px;font-weight:600;color:var(--text-muted)">Kişi</span></strong>
          <small>${esc(growthTxt)}</small>
        </div>

        <div class="zs-kpi">
          <div class="zs-kpi-top"><span>${esc(rangeName.toUpperCase())} GÖRÜNTÜLEME</span><span class="zs-kpi-icon">👁</span></div>
          <strong id="anRangeViewsVal">${Number(rng.views || 0).toLocaleString('tr-TR')}</strong>
          <small>${Number(rng.uniques || 0).toLocaleString('tr-TR')} tekil kişi · Kişi başı ort. ${d30.avg_views_per_visitor || 1} sayfa</small>
        </div>

        <div class="zs-kpi">
          <div class="zs-kpi-top"><span>BUGÜN &amp; TÜM ZAMANLAR</span><span class="zs-kpi-icon">🌍</span></div>
          <strong>${Number(today.uniques || 0).toLocaleString('tr-TR')} <span style="font-size:14px;color:var(--text-muted)">Bugün / ${Number(allT.uniques || 0).toLocaleString('tr-TR')} Toplam</span></strong>
          <small>Toplam ${Number(allT.views || 0).toLocaleString('tr-TR')} sayfa · 🤖 ${Number(allT.bot_hits || 0).toLocaleString('tr-TR')} Arama/AI Bot taraması</small>
        </div>
      </div>

      <!-- LIVE ACTIVE VISITORS & ACTIVE PAGES SPOTLIGHT -->
      <div class="zs-grid-2" style="margin-bottom:18px">
        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0;display:flex;align-items:center;gap:8px">
                <span class="zs-live-orb ${live.count > 0 ? '' : 'idle'}"></span>
                <span>Anlık Sitede Gezilen Sayfalar (${Number(live.count || 0)} Aktif Kişi)</span>
              </h3>
              <p class="zs-card-sub">Şu an ziyaretçilerin hangi sayfalarda bulunduğunu canlı olarak izleyin.</p>
            </div>
          </div>
          ${(!live.active_pages || live.active_pages.length === 0) ? `
            <div style="padding:22px;border-radius:12px;background:rgba(6,13,17,.6);color:var(--text-muted);font-size:12.5px;text-align:center">
              Şu an sitede aktif ziyaretçi görünmüyor (yönetici oturumunuz istatistikleri şişirmemek için otomatik hariç tutulur). Yeni bir ziyaretçi girdiğinde anında burada listelenir.
            </div>
          ` : `
            <div class="zs-an-stat-list">
              ${live.active_pages.map(ap => `
                <div class="zs-an-stat-row" style="border-color:rgba(52,211,153,0.26)">
                  <div class="zs-an-stat-head">
                    <span class="zs-an-stat-label">
                      <a href="${esc(resolveUrl(ap.path))}" target="_blank" rel="noopener" style="color:#f8fafc;text-decoration:none;font-weight:700">${esc(ap.title || ap.path)} ↗</a>
                      <span style="color:var(--text-dim);font-size:11.5px;margin-left:6px">${esc(ap.path)}</span>
                    </span>
                    <span class="zs-pill zs-pill-pub" style="font-size:11px;padding:2px 9px">🟢 ${ap.count} Kişi</span>
                  </div>
                </div>
              `).join('')}
            </div>
          `}
        </div>

        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">Anlık Çevrimiçi Ziyaretçi Detayları</h3>
              <p class="zs-card-sub">Sitedeki aktif oturumların cihaz, ülke, dil ve geliş kaynağı bilgisi.</p>
            </div>
          </div>
          ${(!live.visitors || live.visitors.length === 0) ? `
            <div style="padding:22px;border-radius:12px;background:rgba(6,13,17,.6);color:var(--text-muted);font-size:12.5px;text-align:center">
              Son 3 dakika içinde aktif ziyaretçi nabzı bekleniyor.
            </div>
          ` : `
            <div class="zs-table-wrap" style="max-height:228px;overflow-y:auto;overflow-x:hidden">
              <table class="zs-table zs-an-live-table">
                <thead><tr><th>Sayfa</th><th>Kaynak</th><th>Cihaz</th><th>Bölge / Süre</th></tr></thead>
                <tbody>
                  ${live.visitors.map(v => `
                    <tr>
                      <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(v.path)}">
                        <strong style="font-size:12px;color:#fff">${esc(v.title || v.path)}</strong>
                      </td>
                      <td style="white-space:nowrap"><span class="zs-pill zs-pill-cat" style="font-size:10px;padding:2px 7px;white-space:nowrap">${esc(v.source)}</span></td>
                      <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">${devEmoji(v.device)} · ${esc(v.browser)}</td>
                      <td style="font-size:11px;color:#6ee7b7;white-space:nowrap">${esc(v.country_label)} · ${formatDurationShort(v.duration_sec)}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
      </div>

      <!-- DAILY 30-DAY CHART + TODAY HOURLY DISTRIBUTION -->
      <div class="zs-card" style="margin-bottom:18px">
        <div class="zs-card-head" style="margin-bottom:12px">
          <div>
            <h3 style="margin:0">📈 Günlük Ziyaretçi &amp; Sayfa Görüntüleme Grafiği (${series.length} Günlük Zaman Çizelgesi)</h3>
            <p class="zs-card-sub">Koyu yeşil sütunlar <strong>Tekil Kişi</strong> sayısını, açık mavi üst sütunlar toplam <strong>Sayfa Görüntüleme</strong> sayısını gösterir. Zirve gün: <strong>${esc(rng.peak_day || '-')}</strong> (${Number(rng.peak_uniques || 0)} kişi).</p>
          </div>
          <div style="display:flex;gap:12px;align-items:center;font-size:12px;color:var(--text-muted)">
            <span><i style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#10b981;margin-right:5px"></i>Tekil Ziyaretçi</span>
            <span><i style="display:inline-block;width:10px;height:10px;border-radius:3px;background:rgba(56,189,248,0.45);border:1px solid #38bdf8;margin-right:5px"></i>Sayfa Görüntüleme</span>
          </div>
        </div>

        <div class="zs-an-chart-stage">
          <div class="zs-an-bars" style="--an-cols:${Math.max(1, series.length)}">
            ${series.map(pt => {
              const vCnt = Number(pt.views || 0);
              const uCnt = Number(pt.uniques || 0);
              const outerH = vCnt > 0 ? Math.max(8, Math.round((vCnt / maxViews) * 100)) : 3;
              const innerRatio = vCnt > 0 ? Math.min(100, Math.max(22, Math.round((uCnt / vCnt) * 100))) : 0;
              return `
                <div class="zs-an-bar-col" title="${esc(pt.label)} (${esc(pt.date)}): ${uCnt.toLocaleString('tr-TR')} Tekil Kişi · ${vCnt.toLocaleString('tr-TR')} Görüntüleme">
                  <div class="zs-an-bar-views" style="height:${outerH}%;opacity:${vCnt > 0 ? 1 : 0.25}">
                    ${uCnt > 0 ? `<div class="zs-an-bar-uniques" style="height:${innerRatio}%"></div>` : ''}
                  </div>
                </div>
              `;
            }).join('')}
          </div>
          <div class="zs-an-axis">
            <span>${esc(firstDateLbl)}</span>
            <span>${esc(midDateLbl)}</span>
            <span>Bugün (${esc(lastDateLbl)})</span>
          </div>
        </div>

        <div style="margin-top:14px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:12px;color:var(--text-muted)">
            <strong>🕒 Bugün Saatlik Yoğunluk (00:00 – 23:00)</strong>
            <span>Bugün toplam ${Number(today.views || 0).toLocaleString('tr-TR')} görüntüleme</span>
          </div>
          <div class="zs-an-hours-grid">
            ${hours.map((hv, hIdx) => {
              const hh = String(hIdx).padStart(2, '0') + ':00';
              const hp = hv > 0 ? Math.max(12, Math.round((hv / maxHour) * 100)) : 5;
              return `<div class="zs-an-hour-bar" style="height:${hp}%;opacity:${hv > 0 ? 1 : 0.2}" title="${hh} — ${hv} görüntüleme"></div>`;
            }).join('')}
          </div>
        </div>
      </div>

      <!-- TOP PAGES & TRAFFIC SOURCES -->
      <div class="zs-grid-2" style="margin-bottom:18px">
        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">🔥 En Çok Ziyaret Edilen Sayfalar (${esc(rangeName)})</h3>
              <p class="zs-card-sub">Ziyaretçilerin en çok ilgi gösterdiği hizmet, kurumsal sayfa ve blog içerikleri.</p>
            </div>
          </div>
          ${renderStatRowsHtml(
            (rep.top_pages || []).slice(0, 10),
            it => `<a href="${esc(resolveUrl(it.path))}" target="_blank" rel="noopener" style="color:#f8fafc;text-decoration:none">${esc(it.title || it.path)} ↗</a> <span style="color:var(--text-dim);font-size:11px">${esc(it.path)}</span>`,
            it => it.views,
            it => it.pct,
            'Seçili dönemde henüz sayfa görüntüleme verisi yok.'
          )}
        </div>

        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">🧭 Trafik Kaynakları — Nereden Geldiler? (${esc(rangeName)})</h3>
              <p class="zs-card-sub">Google arama, sosyal medya, yapay zeka asistanları ve doğrudan giriş dağılımı.</p>
            </div>
          </div>
          ${renderStatRowsHtml(
            rep.referrers || [],
            it => esc(it.source),
            it => it.count,
            it => it.pct,
            'Seçili dönemde henüz yönlendiren kaynak verisi yok.'
          )}
        </div>
      </div>

      <!-- DEVICES / BROWSERS & LANGUAGES / COUNTRIES -->
      <div class="zs-grid-2" style="margin-bottom:18px">
        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">📱 Cihaz &amp; Tarayıcı Dağılımı (${esc(rangeName)})</h3>
              <p class="zs-card-sub">Mobil ve masaüstü kullanım oranları ile tercih edilen tarayıcılar.</p>
            </div>
          </div>
          ${renderStatRowsHtml(rep.devices || [], it => esc(it.label), it => it.count, it => it.pct, 'Henüz cihaz verisi yok.')}
          ${(rep.browsers && rep.browsers.length > 0) ? `
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-subtle)">
              <strong style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:8px">Tarayıcılar</strong>
              ${renderStatRowsHtml(rep.browsers, it => esc(it.browser), it => it.count, it => it.pct, '')}
            </div>
          ` : ''}
        </div>

        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">🌍 Site Dili &amp; Ülke Dağılımı (${esc(rangeName)})</h3>
              <p class="zs-card-sub">TR / EN / DE / RU çoklu dil kullanımı ve ziyaretçi bölgeleri.</p>
            </div>
          </div>
          ${renderStatRowsHtml(rep.languages || [], it => esc(it.label), it => it.count, it => it.pct, 'Henüz dil verisi yok.')}
          ${(rep.countries && rep.countries.length > 0) ? `
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-subtle)">
              <strong style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:8px">Ülkeler / Bölgeler</strong>
              ${renderStatRowsHtml(rep.countries, it => esc(it.label), it => it.count, it => it.pct, '')}
            </div>
          ` : ''}
        </div>
      </div>

      <!-- RECENT REAL-TIME VISITOR STREAM -->
      <div class="zs-card" style="margin-bottom:0">
        <div class="zs-card-head" style="margin-bottom:12px">
          <div>
            <h3 style="margin:0">⚡ Son Ziyaretçi Hareketleri (Canlı Akış)</h3>
            <p class="zs-card-sub">Siteye gerçekleşen son sayfa girişlerinin anlık kaydı (kişisel veri ve IP saklanmaz).</p>
          </div>
        </div>
        ${(!rep.recent_events || rep.recent_events.length === 0) ? `
          <div style="padding:22px;text-align:center;color:var(--text-muted);font-size:12.5px;border-radius:12px;background:rgba(6,13,17,.6)">
            Henüz kayıtlı ziyaretçi hareketi bulunmuyor. Gizli sekmeden veya telefondan siteye girdiğinizde anında buraya düşer.
          </div>
        ` : `
          <div class="zs-table-wrap">
            <table class="zs-table">
              <thead><tr><th>Zaman</th><th>Gezilen Sayfa</th><th>Geliş Kaynağı</th><th>Cihaz &amp; Tarayıcı</th><th>Dil / Ülke</th></tr></thead>
              <tbody>
                ${rep.recent_events.map(ev => `
                  <tr>
                    <td style="white-space:nowrap;font-size:12px;color:var(--text-muted)">
                      <strong style="color:#5eead4">${esc(ev.ago)}</strong>
                      <span style="display:block;font-size:10.5px;color:var(--text-dim)">${esc(ev.time_str)}</span>
                    </td>
                    <td>
                      <a href="${esc(resolveUrl(ev.path))}" target="_blank" rel="noopener" style="color:#fff;font-weight:700;text-decoration:none;font-size:13px">${esc(ev.title || ev.path)} ↗</a>
                      <div style="font-size:11px;color:var(--text-dim)">${esc(ev.path)} ${ev.is_new ? '<span class="zs-pill zs-pill-pub" style="font-size:9.5px;padding:1px 6px;margin-left:4px">Yeni Ziyaretçi</span>' : ''}</div>
                    </td>
                    <td><span class="zs-pill zs-pill-cat">${esc(ev.source)}</span></td>
                    <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">${devEmoji(ev.device)} · ${esc(ev.browser)}</td>
                    <td style="font-size:12px;white-space:nowrap">${esc(ev.country_label)} <span style="color:var(--text-dim)">(${esc(ev.lang)})</span></td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `}
      </div>

      <!-- SITE ICI ARAMA ANALITIGI (INSTANT SEARCH INSIGHTS) -->
      <div id="anSearchInsightsHost" style="margin-top:18px"></div>
    `;

    bindAnalyticsModeTabs();
    mountSearchInsightsPanel(document.getElementById('anSearchInsightsHost'));

    document.getElementById('anOpenExecReportBtn')?.addEventListener('click', () => {
      S.tabs.analytics = 'report';
      syncAdminUrl('push');
      renderExecutiveMonthlyReportView(true);
    });

    ROOT.querySelectorAll('[data-an-range]').forEach(btn => {
      btn.onclick = () => {
        const nextDays = Number(btn.getAttribute('data-an-range') || 30);
        refreshAnalyticsReport(nextDays, false);
      };
    });

    const refBtn = document.getElementById('anRefreshBtn');
    if (refBtn) {
      refBtn.onclick = async () => {
        refBtn.disabled = true;
        refBtn.textContent = '⏳ Yenileniyor...';
        await refreshAnalyticsReport(S.analytics.rangeDays, false);
        toast('Canlı ziyaretçi verileri güncellendi.');
      };
    }

    const rstBtn = document.getElementById('anResetBtn');
    if (rstBtn) {
      rstBtn.onclick = async () => {
        if (!confirm('Tüm ziyaretçi istatistiklerini ve anlık trafik geçmişini sıfırlamak istediğinize emin misiniz?')) return;
        rstBtn.disabled = true;
        try {
          const res = await api('analytics_reset', { days: S.analytics.rangeDays });
          S.analytics.report = res.report;
          if (S.summary) S.summary.analytics = res.report;
          updateSidebarBadges();
          paintAnalyticsDom();
          toast(res.message);
        } catch (err) {
          toast(err.message, true);
          rstBtn.disabled = false;
        }
      };
    }
  }

  function renderAnalyticsView() {
    syncAdminUrl('replace');
    if (S.tabs.analytics === 'report') {
      renderExecutiveMonthlyReportView(true);
      return;
    }
    paintAnalyticsDom();
    refreshAnalyticsReport(S.analytics.rangeDays || 30, true);
  }

  /* ======================== MODULE 1B: HOMEPAGE SECTION ARRANGER & LIVE WIREFRAME STUDIO ======================== */
  function computeHomeLayoutSnap() {
    const { order, vis } = ensureNormalizedHomeSections();
    return JSON.stringify({ order, vis });
  }

  /* ======================== MODULE 2: BLOG & JOURNAL MANAGEMENT ======================== */
  function buildClientCategoryTreeFromList(rawCats) {
    const blogSlug = getResolvedPageSlug('blog');
    let list = Array.isArray(rawCats) ? rawCats.map((c, idx) => ({
      id: String(c.id || ('cat-' + slugifyClient(c.slug || c.name || ('kategori-' + (idx + 1))))),
      name: String(c.name || '').trim(),
      slug: slugifyClient(c.slug || c.name || ('kategori-' + (idx + 1))),
      parent_id: String(c.parent_id || '').trim(),
      description: String(c.description || '').trim(),
      meta_title: String(c.meta_title || '').trim(),
      meta_desc: String(c.meta_desc || '').trim(),
      order: Number(c.order || (idx + 1)),
      is_default: Boolean(c.is_default),
      post_count: Number(c.post_count ?? 0),
      total_post_count: Number(c.total_post_count ?? c.post_count ?? 0),
      url: c.url || ''
    })).filter(c => c.name !== '') : [];

    if (!list.length) {
      list = [
        { id: 'cat-web-tasarim', name: 'Web Tasarım', slug: 'web-tasarim', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 1, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-grafik-tasarim', name: 'Grafik Tasarım', slug: 'grafik-tasarim', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 2, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-e-ticaret', name: 'E-Ticaret', slug: 'e-ticaret', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 3, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-hosting-altyapi', name: 'Hosting & Altyapı', slug: 'hosting-altyapi', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 4, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-urun-stratejisi', name: 'Ürün Stratejisi', slug: 'urun-stratejisi', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 5, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-yapay-zeka', name: 'Yapay Zeka', slug: 'yapay-zeka', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 6, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-tasarim-teknoloji', name: 'Tasarım & Teknoloji', slug: 'tasarim-teknoloji', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 7, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-genel', name: 'Genel', slug: 'genel', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 8, is_default: true, post_count: 0, total_post_count: 0 },
        { id: 'cat-web', name: 'Web', slug: 'web', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 9, is_default: false, post_count: 0, total_post_count: 0 },
        { id: 'cat-blog', name: 'Blog', slug: 'blog', parent_id: '', description: '', meta_title: '', meta_desc: '', order: 10, is_default: false, post_count: 0, total_post_count: 0 }
      ];
    }

    list.sort((a, b) => {
      if (a.order !== b.order) return a.order - b.order;
      return a.name.localeCompare(b.name, 'tr');
    });

    if (!list.some(c => c.is_default)) {
      const fallbackDef = list.find(c => c.slug === 'genel') || list[0];
      if (fallbackDef) fallbackDef.is_default = true;
    }

    const byId = {};
    list.forEach(c => { byId[c.id] = c; });
    list.forEach(c => {
      if (c.parent_id && (!byId[c.parent_id] || c.parent_id === c.id)) {
        c.parent_id = '';
      }
      if (c.parent_id) {
        const seen = new Set([c.id]);
        let cur = c.parent_id;
        while (cur !== '') {
          if (seen.has(cur) || !byId[cur]) {
            c.parent_id = '';
            break;
          }
          seen.add(cur);
          cur = byId[cur].parent_id || '';
        }
      }
    });

    const childrenMap = {};
    list.forEach(c => {
      const pid = c.parent_id || '__root__';
      if (!childrenMap[pid]) childrenMap[pid] = [];
      childrenMap[pid].push(c);
    });

    const collectDescendants = catId => {
      const ids = [];
      const kids = childrenMap[catId] || [];
      kids.forEach(k => {
        ids.push(k.id);
        collectDescendants(k.id).forEach(subId => ids.push(subId));
      });
      return ids;
    };

    const flat = [];
    const visited = new Set();
    const walk = (pid, depth, ancestors) => {
      (childrenMap[pid] || []).forEach(cat => {
        if (visited.has(cat.id)) return;
        visited.add(cat.id);
        const parentObj = cat.parent_id && byId[cat.parent_id] ? byId[cat.parent_id] : null;
        const descIds = Array.isArray(cat.descendant_ids) && cat.descendant_ids.length
          ? cat.descendant_ids
          : collectDescendants(cat.id);
        flat.push(Object.assign({}, cat, {
          depth,
          indent_prefix: depth > 0 ? '— '.repeat(depth) : '',
          parent_name: parentObj ? parentObj.name : '',
          parent_slug: parentObj ? parentObj.slug : '',
          ancestor_ids: ancestors.slice(),
          descendant_ids: descIds,
          url: cat.url || ('/' + blogSlug + '/kategori/' + cat.slug + '/')
        }));
        walk(cat.id, depth + 1, ancestors.concat([cat.id]));
      });
    };
    walk('__root__', 0, []);

    list.forEach(cat => {
      if (!visited.has(cat.id)) {
        flat.push(Object.assign({}, cat, {
          depth: 0,
          indent_prefix: '',
          parent_name: '',
          parent_slug: '',
          ancestor_ids: [],
          descendant_ids: [],
          url: cat.url || ('/' + blogSlug + '/kategori/' + cat.slug + '/')
        }));
      }
    });
    return flat;
  }

  function syncBlogCatsFromApiPayload(payload) {
    if (!payload) return;
    const incomingList = Array.isArray(payload.categories)
      ? payload.categories
      : (Array.isArray(payload.blog_categories) ? payload.blog_categories : null);
    if (!incomingList) return;

    const tree = buildClientCategoryTreeFromList(incomingList);
    S.blogCats.items = tree;
    const defCat = payload.default_category
      || tree.find(c => c.id === payload.default_category_id)
      || tree.find(c => c.is_default)
      || tree[0]
      || null;
    S.blogCats.defaultCategory = defCat;
    S.blogCats.defaultCategoryId = defCat ? defCat.id : '';
    S.blogCats.blogSlug = payload.blog_slug || getResolvedPageSlug('blog');
    if (!payload.partial) {
      S.blogCats.loaded = true;
    }
    if (S.store) {
      S.store.blog_categories = tree.map(c => ({
        id: c.id,
        name: c.name,
        slug: c.slug,
        parent_id: c.parent_id || '',
        description: c.description || '',
        meta_title: c.meta_title || '',
        meta_desc: c.meta_desc || '',
        order: c.order,
        is_default: Boolean(c.is_default)
      }));
      S.store.blog_settings = S.store.blog_settings || {};
      S.store.blog_settings.categories = ['TÜMÜ'].concat(tree.map(c => c.name));
    }
    if (S.summary) {
      S.summary.blog_categories_count = tree.length;
    }
  }

  function getClientBlogCategoriesTree() {
    if (Array.isArray(S.blogCats.items) && S.blogCats.items.length > 0) {
      return S.blogCats.items;
    }
    const raw = Array.isArray(S.store?.blog_categories) ? S.store.blog_categories : [];
    const built = buildClientCategoryTreeFromList(raw);
    S.blogCats.items = built;
    const defCat = built.find(c => c.is_default) || built[0] || null;
    S.blogCats.defaultCategory = defCat;
    S.blogCats.defaultCategoryId = defCat ? defCat.id : '';
    return built;
  }

  function getDefaultBlogCategoryClient() {
    const tree = getClientBlogCategoriesTree();
    return tree.find(c => c.is_default) || tree[0] || { id: 'cat-genel', name: 'Genel', slug: 'genel' };
  }

  /* ======================== MODULE 4: HEADER & FOOTER MENU + GOOGLE FONTS & TYPOGRAPHY STUDIO ======================== */
  const FONT_GROUPS = [
    {
      label: '⚡ Yerleşik & Sistem (Sıfır Harici İstek / Maksimum Hız)',
      items: [
        { value: 'Manrope', label: 'Manrope — Zeynart Orijinal (Yerleşik Hızlı WOFF2)', stack: '"Manrope", system-ui, sans-serif', css2: '' },
        { value: 'System UI', label: 'System UI — Cihaz Yerleşik Fontu (Apple SF / Segoe UI)', stack: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', css2: '' },
        { value: 'Georgia', label: 'Georgia — Klasik Sistem Serif', stack: 'Georgia, Cambria, "Times New Roman", serif', css2: '' }
      ]
    },
    {
      label: '🌐 Google Fonts — Modern Ajans & Teknoloji (Tam Türkçe)',
      items: [
        { value: 'Plus Jakarta Sans', label: 'Plus Jakarta Sans — Modern Kurumsal & SaaS', stack: '"Plus Jakarta Sans", sans-serif', css2: 'Plus+Jakarta+Sans:wght@300;400;500;600;700;800' },
        { value: 'Inter', label: 'Inter — Net Okunabilirlik & Ürün Tasarımı', stack: '"Inter", sans-serif', css2: 'Inter:wght@300;400;500;600;700;800' },
        { value: 'Outfit', label: 'Outfit — Geometrik Dijital Stüdyo', stack: '"Outfit", sans-serif', css2: 'Outfit:wght@300;400;500;600;700;800' },
        { value: 'Space Grotesk', label: 'Space Grotesk — Teknoloji & Yazılım Ajansı', stack: '"Space Grotesk", sans-serif', css2: 'Space+Grotesk:wght@300;400;500;600;700' },
        { value: 'Sora', label: 'Sora — Fütüristik & Keskin', stack: '"Sora", sans-serif', css2: 'Sora:wght@300;400;500;600;700;800' },
        { value: 'Urbanist', label: 'Urbanist — Minimalist Modern Vitrin', stack: '"Urbanist", sans-serif', css2: 'Urbanist:wght@300;400;500;600;700;800' },
        { value: 'DM Sans', label: 'DM Sans — Dengeli & Dostane Kurumsal', stack: '"DM Sans", sans-serif', css2: 'DM+Sans:wght@300;400;500;600;700' },
        { value: 'Poppins', label: 'Poppins — Popüler Geometrik Kurumsal', stack: '"Poppins", sans-serif', css2: 'Poppins:wght@300;400;500;600;700' },
        { value: 'Montserrat', label: 'Montserrat — Güçlü Marka & Başlık Duruşu', stack: '"Montserrat", sans-serif', css2: 'Montserrat:wght@300;400;500;600;700;800' },
        { value: 'Rubik', label: 'Rubik — Yumuşak Köşeli Modern', stack: '"Rubik", sans-serif', css2: 'Rubik:wght@300;400;500;600;700' },
        { value: 'Nunito Sans', label: 'Nunito Sans — Akıcı & Yüksek Okunaklı', stack: '"Nunito Sans", sans-serif', css2: 'Nunito+Sans:wght@300;400;600;700;800' },
        { value: 'Work Sans', label: 'Work Sans — Editoryal Teknoloji', stack: '"Work Sans", sans-serif', css2: 'Work+Sans:wght@300;400;500;600;700' },
        { value: 'Figtree', label: 'Figtree — Temiz Arayüz & Web', stack: '"Figtree", sans-serif', css2: 'Figtree:wght@300;400;500;600;700;800' },
        { value: 'Lexend', label: 'Lexend — Maksimum Okuma Konforu', stack: '"Lexend", sans-serif', css2: 'Lexend:wght@300;400;500;600;700' }
      ]
    },
    {
      label: '✨ Google Fonts — Kreatif Vitrin & Başlık (Display)',
      items: [
        { value: 'Syne', label: 'Syne — Ödüllü Kreatif Ajans Başlık Fontu', stack: '"Syne", sans-serif', css2: 'Syne:wght@400;500;600;700;800' },
        { value: 'Bricolage Grotesque', label: 'Bricolage Grotesque — Karakteristik & Özgün', stack: '"Bricolage Grotesque", sans-serif', css2: 'Bricolage+Grotesque:wght@300;400;500;600;700;800' },
        { value: 'Archivo', label: 'Archivo — Endüstriyel & Güçlü Vurgu', stack: '"Archivo", sans-serif', css2: 'Archivo:wght@300;400;500;600;700;800' },
        { value: 'Josefin Sans', label: 'Josefin Sans — Zarif Mimari Çizgi', stack: '"Josefin Sans", sans-serif', css2: 'Josefin+Sans:wght@300;400;500;600;700' }
      ]
    },
    {
      label: '🏛️ Google Fonts — Prestij & Editoryal (Serif)',
      items: [
        { value: 'Playfair Display', label: 'Playfair Display — Lüks & Prestijli Başlık', stack: '"Playfair Display", Georgia, serif', css2: 'Playfair+Display:wght@400;500;600;700' },
        { value: 'Lora', label: 'Lora — Çağdaş Editoryal Serif', stack: '"Lora", Georgia, serif', css2: 'Lora:wght@400;500;600;700' },
        { value: 'Merriweather', label: 'Merriweather — Kurumsal Otoriter', stack: '"Merriweather", Georgia, serif', css2: 'Merriweather:wght@300;400;700' },
        { value: 'Cormorant Garamond', label: 'Cormorant Garamond — Sanatsal & Butik', stack: '"Cormorant Garamond", Georgia, serif', css2: 'Cormorant+Garamond:wght@400;500;600;700' }
      ]
    }
  ];

  const FONT_PAIRING_PRESETS = [
    { id: 'zeynart', name: 'Zeynart Orijinal (Yerleşik)', sub: 'Manrope · 0 Harici İstek', body_font: 'Manrope', heading_font: 'same', heading_tracking: 'tight' },
    { id: 'studio', name: 'Modern Dijital Stüdyo', sub: 'Space Grotesk + Plus Jakarta Sans', body_font: 'Plus Jakarta Sans', heading_font: 'Space Grotesk', heading_tracking: 'balanced' },
    { id: 'creative', name: 'Kreatif Tasarım Ajansı', sub: 'Syne + DM Sans', body_font: 'DM Sans', heading_font: 'Syne', heading_tracking: 'balanced' },
    { id: 'saas', name: 'Minimalist Yazılım & SaaS', sub: 'Outfit + Inter', body_font: 'Inter', heading_font: 'Outfit', heading_tracking: 'tight' },
    { id: 'editorial', name: 'Prestij & Lüks Editoryal', sub: 'Playfair Display + Plus Jakarta Sans', body_font: 'Plus Jakarta Sans', heading_font: 'Playfair Display', heading_tracking: 'normal' },
    { id: 'corporate', name: 'Geometrik Kurumsal', sub: 'Montserrat + Poppins', body_font: 'Poppins', heading_font: 'Montserrat', heading_tracking: 'balanced' }
  ];

  function extractGoogleFontName(raw) {
    let s = String(raw || '').trim();
    if (!s) return '';
    const m = s.match(/[?&]family=([^&:;"'<>]+)/i);
    if (m) {
      try { s = decodeURIComponent(m[1].replace(/\+/g, ' ')); } catch (_) { s = m[1].replace(/\+/g, ' '); }
    }
    return s.replace(/[^A-Za-z0-9 \-]/g, '').replace(/\s+/g, ' ').trim().slice(0, 60);
  }

  function findCatalogFont(val) {
    for (const grp of FONT_GROUPS) {
      const f = grp.items.find(x => x.value === val);
      if (f) return f;
    }
    return null;
  }

  function renderFontSelectOptions(selectedVal, includeSameOption, inheritOptionLabel) {
    const hasInherit = Boolean(inheritOptionLabel);
    const known = Boolean(
      (hasInherit && (!selectedVal || selectedVal === 'inherit')) ||
      (includeSameOption && selectedVal === 'same') ||
      selectedVal === 'custom' ||
      findCatalogFont(selectedVal)
    );
    const effective = known
      ? ((!selectedVal && hasInherit) ? 'inherit' : selectedVal)
      : (selectedVal ? 'custom' : (hasInherit ? 'inherit' : (includeSameOption ? 'same' : 'Manrope')));
    let html = '';
    if (hasInherit) {
      html += `<option value="inherit" ${effective === 'inherit' ? 'selected' : ''}>${esc(inheritOptionLabel)}</option>`;
    }
    if (includeSameOption) {
      html += `<option value="same" ${effective === 'same' ? 'selected' : ''}>🔗 Gövde Yazı Tipiyle Aynı Kullan</option>`;
    }
    for (const grp of FONT_GROUPS) {
      html += `<optgroup label="${esc(grp.label)}">`;
      for (const it of grp.items) {
        html += `<option value="${esc(it.value)}" ${effective === it.value ? 'selected' : ''}>${esc(it.label)}</option>`;
      }
      html += `</optgroup>`;
    }
    html += `<optgroup label="✍️ Özel Google Font (1.600+ Font Desteği)">`;
    html += `<option value="custom" ${effective === 'custom' ? 'selected' : ''}>Özel Google Font Adı veya Linki Gir...</option>`;
    html += `</optgroup>`;
    return html;
  }

  function resolveAdminFontPreview(typo) {
    const css2Specs = [];
    const customFams = [];

    const resolveSingle = (choice, customRaw, fallbackManrope) => {
      if (choice === 'custom') {
        const cf = extractGoogleFontName(customRaw);
        if (!cf) return { family: 'Manrope', stack: '"Manrope", sans-serif' };
        const cat = findCatalogFont(cf);
        if (cat) {
          if (cat.css2) css2Specs.push(cat.css2);
          return { family: cat.value, stack: cat.stack };
        }
        customFams.push(cf);
        return { family: cf, stack: `"${cf}", sans-serif` };
      }
      const cat = findCatalogFont(choice);
      if (cat) {
        if (cat.css2) css2Specs.push(cat.css2);
        return { family: cat.value, stack: cat.stack };
      }
      if (fallbackManrope) return { family: 'Manrope', stack: '"Manrope", sans-serif' };
      const clean = extractGoogleFontName(choice);
      if (clean) {
        customFams.push(clean);
        return { family: clean, stack: `"${clean}", sans-serif` };
      }
      return { family: 'Manrope', stack: '"Manrope", sans-serif' };
    };

    const bodyRes = resolveSingle(typo.body_font || 'Manrope', typo.custom_body_font || '', false);
    const headRes = (!typo.heading_font || typo.heading_font === 'same')
      ? bodyRes
      : resolveSingle(typo.heading_font, typo.custom_heading_font || '', false);

    // Dynamically load Google Fonts stylesheet inside Admin <head> so preview renders real glyphs immediately
    const uniqCss2 = Array.from(new Set(css2Specs));
    const uniqCust = Array.from(new Set(customFams));
    let link1 = document.getElementById('zsAdminPreviewGFont1');
    if (uniqCss2.length) {
      const href = 'https://fonts.googleapis.com/css2?family=' + uniqCss2.join('&family=') + '&display=swap';
      if (!link1) {
        link1 = document.createElement('link');
        link1.id = 'zsAdminPreviewGFont1';
        link1.rel = 'stylesheet';
        document.head.appendChild(link1);
      }
      if (link1.href !== href) link1.href = href;
    } else if (link1) {
      link1.remove();
    }

    let link2 = document.getElementById('zsAdminPreviewGFont2');
    if (uniqCust.length) {
      const href = 'https://fonts.googleapis.com/css?family=' + uniqCust.map(f => f.replace(/ /g, '+') + ':300,400,500,600,700,800').join('|') + '&subset=latin,latin-ext&display=swap';
      if (!link2) {
        link2 = document.createElement('link');
        link2.id = 'zsAdminPreviewGFont2';
        link2.rel = 'stylesheet';
        document.head.appendChild(link2);
      }
      if (link2.href !== href) link2.href = href;
    } else if (link2) {
      link2.remove();
    }

    return { body: bodyRes, heading: headRes };
  }

  function getDefaultMenuTypography() {
    return {
      body_font: 'Manrope',
      custom_body_font: '',
      heading_font: 'same',
      custom_heading_font: '',
      base_body_size: 14,
      heading_scale: 100,
      heading_tracking: 'tight',
      header_link_size: 16,
      header_link_weight: 500,
      header_gap: 32,
      header_cta_size: 13,
      mobile_link_size: 28,
      footer_title_size: 14.5,
      footer_link_size: 14.5,
      footer_text_size: 14,
      footer_legal_size: 13
    };
  }

  /* ======================== MODULE 6: UNIFIED SITE PAGES MANAGER ======================== */
  function normalizeMenuUrl(raw) {
    const s = String(raw || '').trim();
    if (!s || s === '/') return '/';
    if (/^(https?:|mailto:|tel:|#)/i.test(s)) return s;
    const clean = '/' + s.replace(/^\/+|\/+$/g, '');
    return clean + '/';
  }

  function isUrlInHeaderMenu(url) {
    const target = normalizeMenuUrl(url);
    const items = S.store?.menus?.header?.items || [];
    return items.some(it => normalizeMenuUrl(it.url) === target && it.enabled !== false);
  }

  function getFooterMenuPlacement(url) {
    const target = normalizeMenuUrl(url);
    const cols = S.store?.menus?.footer?.columns || [];
    for (let cIdx = 0; cIdx < cols.length; cIdx++) {
      const links = cols[cIdx]?.links || cols[cIdx]?.items || [];
      if (links.some(it => normalizeMenuUrl(it.url) === target)) {
        return { inFooter: true, colIdx: cIdx, inLegal: false };
      }
    }
    const legals = S.store?.menus?.footer?.legal_links || [];
    if (legals.some(it => normalizeMenuUrl(it.url) === target)) {
      return { inFooter: true, colIdx: -1, inLegal: true };
    }
    return { inFooter: false, colIdx: 0, inLegal: false };
  }

  function getPageMenuLabel(url, fallbackTitle, pageKey) {
    const target = normalizeMenuUrl(url);
    const hdrItems = S.store?.menus?.header?.items || [];
    const activeHdr = hdrItems.find(it => normalizeMenuUrl(it.url) === target && it.enabled !== false && String(it.label || '').trim() !== '');
    if (activeHdr) return String(activeHdr.label).trim();

    const cols = S.store?.menus?.footer?.columns || [];
    for (let cIdx = 0; cIdx < cols.length; cIdx++) {
      const links = cols[cIdx]?.links || cols[cIdx]?.items || [];
      const fMatch = links.find(it => normalizeMenuUrl(it.url) === target && String(it.label || '').trim() !== '');
      if (fMatch) return String(fMatch.label).trim();
    }

    const legals = S.store?.menus?.footer?.legal_links || [];
    const lMatch = legals.find(it => normalizeMenuUrl(it.url) === target && String(it.label || '').trim() !== '');
    if (lMatch) return String(lMatch.label).trim();

    if (pageKey && S.store?.page_menu_labels?.[pageKey] && String(S.store.page_menu_labels[pageKey]).trim() !== '') {
      return String(S.store.page_menu_labels[pageKey]).trim();
    }

    const anyHdr = hdrItems.find(it => normalizeMenuUrl(it.url) === target && String(it.label || '').trim() !== '');
    if (anyHdr) return String(anyHdr.label).trim();

    return fallbackTitle;
  }

  function setPageMenuPlacement(url, label, wantHeader, wantFooter, footerColIdx, pageKey) {
    S.store.menus = S.store.menus || { header: { items: [] }, footer: { columns: [], legal_links: [] } };
    S.store.menus.header = S.store.menus.header || { items: [] };
    S.store.menus.footer = S.store.menus.footer || { columns: [], legal_links: [] };
    if (!Array.isArray(S.store.menus.header.items)) S.store.menus.header.items = [];
    if (!Array.isArray(S.store.menus.footer.columns)) S.store.menus.footer.columns = [];

    const norm = normalizeMenuUrl(url);
    const cleanLabel = String(label || 'Sayfa').trim() || 'Sayfa';
    if (pageKey) {
      S.store.page_menu_labels = S.store.page_menu_labels || {};
      S.store.page_menu_labels[pageKey] = cleanLabel;
    }

    // Header
    const hIdx = S.store.menus.header.items.findIndex(it => normalizeMenuUrl(it.url) === norm);
    if (wantHeader) {
      if (hIdx >= 0) {
        S.store.menus.header.items[hIdx].label = cleanLabel;
        S.store.menus.header.items[hIdx].enabled = true;
      } else {
        S.store.menus.header.items.push({
          id: 'nav-' + Math.random().toString(36).slice(2, 8),
          label: cleanLabel,
          url: norm,
          target: '_self',
          enabled: true
        });
      }
    } else if (hIdx >= 0) {
      S.store.menus.header.items.splice(hIdx, 1);
    }

    // Footer
    if (S.store.menus.footer.columns.length === 0 && wantFooter) {
      S.store.menus.footer.columns.push({ title: 'SİTE HARİTASI', links: [] });
    }
    const targetCol = Math.max(0, Math.min(Number(footerColIdx || 0), Math.max(0, S.store.menus.footer.columns.length - 1)));
    const curFooter = getFooterMenuPlacement(norm);

    if (wantFooter) {
      if (curFooter.inLegal) {
        const lIdx = (S.store.menus.footer.legal_links || []).findIndex(it => normalizeMenuUrl(it.url) === norm);
        if (lIdx >= 0) S.store.menus.footer.legal_links[lIdx].label = cleanLabel;
      } else {
        S.store.menus.footer.columns.forEach((col, cIdx) => {
          if (cIdx !== targetCol && Array.isArray(col.links)) {
            col.links = col.links.filter(it => normalizeMenuUrl(it.url) !== norm);
          }
        });
        const col = S.store.menus.footer.columns[targetCol];
        col.links = Array.isArray(col.links) ? col.links : [];
        const fIdx = col.links.findIndex(it => normalizeMenuUrl(it.url) === norm);
        if (fIdx >= 0) {
          col.links[fIdx].label = cleanLabel;
        } else {
          col.links.push({ label: cleanLabel, url: norm, target: '_self' });
        }
      }
    } else {
      S.store.menus.footer.columns.forEach(col => {
        if (Array.isArray(col.links)) {
          col.links = col.links.filter(it => normalizeMenuUrl(it.url) !== norm);
        }
      });
      if (Array.isArray(S.store.menus.footer.legal_links)) {
        S.store.menus.footer.legal_links = S.store.menus.footer.legal_links.filter(it => normalizeMenuUrl(it.url) !== norm);
      }
    }
  }

  const DEFAULT_PAGE_SLUGS = {
    hakkimizda: 'hakkimizda',
    hizmetler: 'hizmetler',
    projeler: 'projeler',
    blog: 'blog',
    iletisim: 'iletisim',
    kvkk: 'kvkk',
    gizlilik: 'gizlilik',
    'cerez-politikasi': 'cerez-politikasi'
  };

  function getResolvedPageSlug(pageKey) {
    const cleanKey = String(pageKey || '').startsWith('legal:') ? String(pageKey).slice(6) : String(pageKey || '');
    if (cleanKey === 'anasayfa' || !cleanKey) return '';
    const def = DEFAULT_PAGE_SLUGS[cleanKey];
    if (!def) return cleanKey;
    const raw = S.store?.page_slugs?.[cleanKey] || S.store?.legal_pages?.[cleanKey]?.slug || def;
    return slugifyClient(raw) || def;
  }

  function getResolvedPageUrl(pageKey) {
    const k = String(pageKey || '');
    if (!k || k === 'anasayfa') return '/';
    if (k.startsWith('service:')) {
      return '/' + getResolvedPageSlug('hizmetler') + '/' + k.slice(8) + '/';
    }
    if (k.startsWith('project:')) {
      return '/' + getResolvedPageSlug('projeler') + '/' + k.slice(8) + '/';
    }
    if (k.startsWith('custom:')) {
      const cp = (S.store?.custom_pages || []).find(x => x.id === k.slice(7));
      return '/' + (cp?.slug || 'sayfa') + '/';
    }
    return '/' + getResolvedPageSlug(k) + '/';
  }

  function normalizeClientPageStatus(raw) {
    const st = String(raw || 'published').trim().toLowerCase();
    return ['published', 'draft', 'trash', 'deleted'].includes(st) ? st : 'published';
  }

  function getCoreOrLegalStateStatus(pageKey) {
    if (!pageKey || pageKey === 'anasayfa') return 'published';
    const cleanKey = pageKey.startsWith('legal:') ? pageKey.slice(6) : pageKey;
    const stObj = S.store?.page_states?.[cleanKey];
    if (stObj && stObj.status) return normalizeClientPageStatus(stObj.status);
    if (S.store?.legal_pages?.[cleanKey]?.status) return normalizeClientPageStatus(S.store.legal_pages[cleanKey].status);
    return 'published';
  }

  function buildUnifiedPagesCatalog(opts) {
    if (!S.store) return [];
    const includeTrash = Boolean(opts && opts.includeTrash);
    const seoPages = S.store.seo?.pages || {};
    const coreBlocks = S.store.core_page_blocks || {};
    const services = S.store.services || [];
    const projects = S.store.projects || [];
    const legalPages = S.store.legal_pages || {};
    const customPages = S.store.custom_pages || [];
    const servicesBaseSlug = getResolvedPageSlug('hizmetler');
    const projectsBaseSlug = getResolvedPageSlug('projeler');
    const activeServicesCnt = services.filter(s => !['trash', 'deleted'].includes(normalizeClientPageStatus(s.status))).length;
    const activeProjectsCnt = projects.filter(p => !['trash', 'deleted'].includes(normalizeClientPageStatus(p.status))).length;

    const makeCore = (key, defaultTitle, _legacyUrl, typeLabel, stateTag, group, bannerImg) => {
      const st = getCoreOrLegalStateStatus(key);
      if (st === 'deleted' || (!includeTrash && st === 'trash')) return null;
      const url = getResolvedPageUrl(key);
      const slug = key === 'anasayfa' ? '' : getResolvedPageSlug(key);
      const title = String(S.store.page_titles?.[key] || '').trim() || defaultTitle;
      const sp = seoPages[key] || {};
      const fInfo = getFooterMenuPlacement(url);
      return {
        key,
        slug,
        title,
        menuLabel: getPageMenuLabel(url, title, key),
        url,
        group,
        typeLabel,
        stateTag,
        status: st,
        isChild: false,
        parentTitle: '',
        deletable: key !== 'anasayfa',
        duplicatable: true,
        inHeaderMenu: isUrlInHeaderMenu(url),
        inFooterMenu: fInfo.inFooter,
        footerColIdx: fInfo.colIdx >= 0 ? fInfo.colIdx : 0,
        blockCount: Array.isArray(coreBlocks[key]) ? coreBlocks[key].length : 0,
        bannerImage: bannerImg || '',
        metaTitle: sp.meta_title || (title + ' | ZEYNART'),
        metaDesc: sp.meta_desc || '',
        focusKeyword: sp.focus_keyword || '',
        aiSummary: sp.ai_summary || '',
        robots: sp.robots || 'index, follow',
        ogTitle: sp.og_title || '',
        ogDesc: sp.og_desc || '',
        ogImage: sp.og_image || ''
      };
    };

    const list = [];
    const pushIf = item => { if (item) list.push(item); };

    // 1. Ana Sayfa
    pushIf(makeCore('anasayfa', 'Ana Sayfa', '/', 'Ana Sayfa', 'Ön Sayfa', 'root', S.store.home?.hero?.bg_image_1 || ''));

    // 2. Hakkımızda
    pushIf(makeCore('hakkimizda', 'Hakkımızda', '/hakkimizda/', 'Kurumsal Sayfa', `${(S.store.about?.clients || []).length || 62} Referans`, 'root', S.store.about?.banner_image || ''));

    // 3. Hizmetler + Alt Hizmet Sayfaları
    const servicesParentTitle = String(S.store.page_titles?.hizmetler || '').trim() || 'Hizmetler';
    pushIf(makeCore('hizmetler', 'Hizmetler', '/hizmetler/', 'Hizmetler Ana Sayfa', `${activeServicesCnt} Alt Sayfa`, 'root', S.store.services_page?.banner_image || ''));
    services.forEach(srv => {
      const sSt = normalizeClientPageStatus(srv.status);
      if (sSt === 'deleted' || (!includeTrash && sSt === 'trash')) return;
      const slug = srv.slug || slugifyClient(srv.title || 'hizmet');
      const url = '/' + servicesBaseSlug + '/' + slug + '/';
      const sSeo = srv.seo || {};
      const fInfo = getFooterMenuPlacement(url);
      const sTitle = srv.title || 'Hizmet Sayfası';
      const sKey = 'service:' + slug;
      list.push({
        key: sKey,
        slug,
        title: sTitle,
        menuLabel: getPageMenuLabel(url, sTitle, sKey),
        url,
        group: 'services',
        typeLabel: 'Hizmet Alt Sayfası',
        stateTag: srv.layout_type === 'hosting' ? 'Paketler Şablonu' : (srv.layout_type === 'graphic' ? 'Stüdyo Şablonu' : 'Alt Sayfa'),
        status: sSt,
        isChild: true,
        parentTitle: servicesParentTitle,
        deletable: true,
        duplicatable: true,
        inHeaderMenu: isUrlInHeaderMenu(url),
        inFooterMenu: fInfo.inFooter,
        footerColIdx: fInfo.colIdx >= 0 ? fInfo.colIdx : 0,
        blockCount: Array.isArray(srv.builder_blocks) ? srv.builder_blocks.length : 0,
        bannerImage: srv.banner_image || '',
        metaTitle: sSeo.meta_title || ((srv.title || 'Hizmet') + ' | ZEYNART'),
        metaDesc: sSeo.meta_desc || srv.description || '',
        focusKeyword: sSeo.focus_keyword || '',
        aiSummary: sSeo.ai_summary || '',
        robots: sSeo.robots || 'index, follow',
        ogTitle: sSeo.og_title || '',
        ogDesc: sSeo.og_desc || '',
        ogImage: sSeo.og_image || ''
      });
    });

    // 4. Projeler + Alt Proje Sayfaları
    const projectsParentTitle = String(S.store.page_titles?.projeler || '').trim() || 'Projeler';
    pushIf(makeCore('projeler', 'Projeler', '/projeler/', 'Projeler Ana Sayfa', `${activeProjectsCnt} Alt Sayfa`, 'root', ''));
    projects.forEach(prj => {
      const pSt = normalizeClientPageStatus(prj.status);
      if (pSt === 'deleted' || (!includeTrash && pSt === 'trash')) return;
      const slug = prj.slug || slugifyClient(prj.name || 'proje');
      const url = '/' + projectsBaseSlug + '/' + slug + '/';
      const pSeo = prj.seo || {};
      const fInfo = getFooterMenuPlacement(url);
      const pTitle = prj.name || 'Proje Sayfası';
      const pKey = 'project:' + slug;
      list.push({
        key: pKey,
        slug,
        title: pTitle,
        menuLabel: getPageMenuLabel(url, pTitle, pKey),
        url,
        group: 'projects',
        typeLabel: 'Proje Alt Sayfası',
        stateTag: (prj.year || '2026') + ' · Alt Sayfa',
        status: pSt,
        isChild: true,
        parentTitle: projectsParentTitle,
        deletable: true,
        duplicatable: true,
        inHeaderMenu: isUrlInHeaderMenu(url),
        inFooterMenu: fInfo.inFooter,
        footerColIdx: fInfo.colIdx >= 0 ? fInfo.colIdx : 0,
        blockCount: Array.isArray(prj.builder_blocks) ? prj.builder_blocks.length : 0,
        bannerImage: prj.image || '',
        metaTitle: pSeo.meta_title || ((prj.name || 'Proje') + ' | ZEYNART'),
        metaDesc: pSeo.meta_desc || prj.summary || '',
        focusKeyword: pSeo.focus_keyword || '',
        aiSummary: pSeo.ai_summary || '',
        robots: pSeo.robots || 'index, follow',
        ogTitle: pSeo.og_title || '',
        ogDesc: pSeo.og_desc || '',
        ogImage: pSeo.og_image || ''
      });
    });

    // 5. Blog Vitrin Sayfası
    const blogUrl = getResolvedPageUrl('blog');
    const blogDefaultTitle = String(S.store.blog_settings?.heading || '').trim() || getPageMenuLabel(blogUrl, '') || 'Blog';
    pushIf(makeCore('blog', blogDefaultTitle, blogUrl, 'Yazılar Sayfası', 'Blog Vitrini', 'root', ''));

    // 6. İletişim Sayfası
    pushIf(makeCore('iletisim', 'İletişim', '/iletisim/', 'İletişim & Form', 'Proje Formu', 'root', S.store.contact?.banner_image || ''));

    // 7. Yasal Politika Sayfaları (KVKK, Gizlilik, Çerez Politikası)
    const legalMeta = [
      { k: 'kvkk', defaultTitle: 'KVKK Aydınlatma Metni' },
      { k: 'gizlilik', defaultTitle: 'Gizlilik Politikası' },
      { k: 'cerez-politikasi', defaultTitle: 'Çerez Politikası' }
    ];
    legalMeta.forEach(lm => {
      const lSt = getCoreOrLegalStateStatus('legal:' + lm.k);
      if (lSt === 'deleted' || (!includeTrash && lSt === 'trash')) return;
      const doc = legalPages[lm.k] || {};
      const sp = seoPages[lm.k] || {};
      const lSlug = getResolvedPageSlug(lm.k);
      const lUrl = '/' + lSlug + '/';
      const fInfo = getFooterMenuPlacement(lUrl);
      const t = doc.title || lm.defaultTitle;
      const lKey = 'legal:' + lm.k;
      list.push({
        key: lKey,
        legalKey: lm.k,
        slug: lSlug,
        title: t,
        menuLabel: getPageMenuLabel(lUrl, t, lKey),
        url: lUrl,
        group: 'legal',
        typeLabel: 'Yasal Politika',
        stateTag: lm.k === 'gizlilik' ? 'Gizlilik Sayfası' : 'Yasal Metin',
        status: lSt,
        isChild: false,
        parentTitle: '',
        deletable: true,
        duplicatable: true,
        inHeaderMenu: isUrlInHeaderMenu(lUrl),
        inFooterMenu: fInfo.inFooter,
        footerColIdx: fInfo.colIdx >= 0 ? fInfo.colIdx : 0,
        blockCount: Array.isArray(coreBlocks[lm.k]) ? coreBlocks[lm.k].length : 0,
        bannerImage: doc.banner_image || '',
        metaTitle: sp.meta_title || doc.meta_title || (t + ' | ZEYNART'),
        metaDesc: sp.meta_desc || doc.meta_desc || doc.lead || '',
        focusKeyword: sp.focus_keyword || '',
        aiSummary: sp.ai_summary || '',
        robots: sp.robots || 'index, follow',
        ogTitle: sp.og_title || '',
        ogDesc: sp.og_desc || '',
        ogImage: sp.og_image || ''
      });
    });

    // 8. Özel Oluşturulan Sayfalar
    customPages.forEach(cp => {
      const cSt = normalizeClientPageStatus(cp.status);
      if (cSt === 'deleted' || (!includeTrash && cSt === 'trash')) return;
      const slug = cp.slug || slugifyClient(cp.title || 'sayfa');
      const url = '/' + slug + '/';
      const cSeo = cp.seo || {};
      const fInfo = getFooterMenuPlacement(url);
      const cTitle = cp.title || 'Özel Sayfa';
      const cKey = 'custom:' + cp.id;
      list.push({
        key: cKey,
        customId: cp.id,
        slug,
        title: cTitle,
        menuLabel: getPageMenuLabel(url, cTitle, cKey),
        url,
        group: 'custom',
        typeLabel: 'Özel Sayfa',
        stateTag: cSt === 'draft' ? 'Taslak' : (cSt === 'trash' ? 'Çöp Kutusu' : 'Özel Sayfa'),
        status: cSt,
        isChild: false,
        parentTitle: '',
        deletable: true,
        duplicatable: true,
        inHeaderMenu: isUrlInHeaderMenu(url),
        inFooterMenu: fInfo.inFooter,
        footerColIdx: fInfo.colIdx >= 0 ? fInfo.colIdx : 0,
        blockCount: Array.isArray(cp.sections) ? cp.sections.length : 0,
        bannerImage: cp.banner_image || '',
        metaTitle: cSeo.meta_title || ((cp.title || 'Sayfa') + ' | ZEYNART'),
        metaDesc: cSeo.meta_desc || cp.excerpt || '',
        focusKeyword: cSeo.focus_keyword || '',
        aiSummary: cSeo.ai_summary || '',
        robots: cSeo.robots || 'index, follow',
        ogTitle: cSeo.og_title || '',
        ogDesc: cSeo.og_desc || '',
        ogImage: cSeo.og_image || ''
      });
    });

    return list;
  }

  function detectSocialBadgeLabel(rawUrl) {
    const u = String(rawUrl || '').trim().toLowerCase();
    if (!u) return '';
    if (u.includes('instagram.com') || u.includes('instagr.am')) return 'Instagram';
    if (u.includes('facebook.com') || u.includes('fb.com') || u.includes('fb.me')) return 'Facebook';
    if (u.includes('linkedin.com') || u.includes('lnkd.in')) return 'LinkedIn';
    if (u.includes('x.com') || u.includes('twitter.com')) return 'X (Twitter)';
    if (u.includes('youtube.com') || u.includes('youtu.be')) return 'YouTube';
    if (u.includes('wa.me') || u.includes('whatsapp.com')) return 'WhatsApp';
    if (u.includes('tiktok.com')) return 'TikTok';
    if (u.includes('behance.net')) return 'Behance';
    if (u.includes('dribbble.com')) return 'Dribbble';
    if (u.includes('github.com')) return 'GitHub';
    if (u.includes('pinterest.') || u.includes('pin.it')) return 'Pinterest';
    if (u.includes('t.me') || u.includes('telegram.')) return 'Telegram';
    return 'Sosyal / Web';
  }

  function trFoldLowerClient(str) {
    const map = { 'İ': 'i', 'I': 'ı', 'Ğ': 'ğ', 'Ü': 'ü', 'Ş': 'ş', 'Ö': 'ö', 'Ç': 'ç' };
    return String(str || '').replace(/[İIĞÜŞÖÇ]/g, ch => map[ch] || ch).toLowerCase();
  }

  function formatMoneyTr(num, sym) {
    const v = Number(num || 0);
    return (sym || '₺') + v.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  /* ---- MODULE 2: ZERO-COOKIE INSTANT SEARCH INSIGHTS PANEL (IN ANALYTICS) ---- */
  function mountSearchInsightsPanel(host) {
    if (!host) return;
    const ins = S.searchInsights || { total_searches: 0, unique_terms: 0, zero_result_count: 0, top_queries: [], zero_results: [] };
    const topQ = Array.isArray(ins.top_queries) ? ins.top_queries : [];
    const zeroQ = Array.isArray(ins.zero_results) ? ins.zero_results : [];

    host.innerHTML = `
      <div class="zs-card" style="margin-bottom:0">
        <div class="zs-card-head" style="margin-bottom:14px">
          <div>
            <span class="zs-badge-pill">ÇEREZSİZ SİTE İÇİ CANLI ARAMA (INSTANT SEARCH · CTRL+K)</span>
            <h3 style="margin-top:6px">🔍 Ziyaretçiler Sitenizde Ne Arıyor? (${Number(ins.total_searches || 0)} Toplam Arama)</h3>
            <p class="zs-card-sub">Üst menüdeki büyüteç (<code>⌘K / Ctrl+K</code>) üzerinden yapılan çerezsiz aramalar ve içerik fırsatları.</p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-sm" id="siRefreshBtn">↻ Arama Verilerini Yenile</button>
            ${ins.total_searches > 0 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="siClearBtn">🗑 Kayıtları Sıfırla</button>` : ''}
          </div>
        </div>

        <div class="zs-grid-2" style="align-items:start">
          <div style="padding:14px;border-radius:14px;background:rgba(5,12,17,.65);border:1px solid var(--border-subtle)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
              <strong style="color:#fff;font-size:13.5px">🔥 En Çok Aranan Kelimeler</strong>
              <span class="zs-pill zs-pill-cat">${Number(ins.unique_terms || 0)} farklı terim</span>
            </div>
            ${topQ.length === 0 ? `
              <div style="padding:16px;text-align:center;color:var(--text-muted);font-size:12.5px">Henüz site içi arama kaydı yok. Sitede büyüteç ikonuna tıklayıp arama yapıldığında anında burada listelenir.</div>
            ` : `
              <div style="display:grid;gap:7px">
                ${topQ.slice(0, 10).map(q => `
                  <div class="zs-sort-row" style="justify-content:space-between;padding:8px 12px">
                    <div>
                      <strong style="color:#fff;font-size:13px">${esc(q.term)}</strong>
                      <span style="font-size:11.5px;color:var(--text-dim);margin-left:6px">${Number(q.last_results || 0)} sonuç · ${esc((q.last_at || '').slice(0, 16))}</span>
                    </div>
                    <span class="zs-pill zs-pill-pub">${Number(q.count || 1)} kez</span>
                  </div>
                `).join('')}
              </div>
            `}
          </div>

          <div style="padding:14px;border-radius:14px;background:rgba(5,12,17,.65);border:1px solid var(--border-subtle)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
              <strong style="color:#fbbf24;font-size:13.5px">💡 Sonuç Bulunamayan Aramalar (İçerik Fırsatı)</strong>
              <span class="zs-sec-badge ${zeroQ.length > 0 ? 'warn' : 'ok'}">${zeroQ.length} terim</span>
            </div>
            ${zeroQ.length === 0 ? `
              <div style="padding:16px;text-align:center;color:#a7f3d0;font-size:12.5px">✓ Ziyaretçilerin aradığı tüm terimlerde ilgili hizmet veya makale sonucu bulundu!</div>
            ` : `
              <div style="display:grid;gap:7px">
                ${zeroQ.slice(0, 10).map(q => `
                  <div class="zs-sort-row" style="justify-content:space-between;padding:8px 12px;gap:8px">
                    <div>
                      <strong style="color:#fde68a;font-size:13px">${esc(q.term)}</strong>
                      <span style="font-size:11.5px;color:var(--text-dim);display:block">${Number(q.count || 1)} kez arandı · 0 sonuç döndü</span>
                    </div>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-si-write-ai="${esc(q.term)}">✦ AI Blog Yaz</button>
                  </div>
                `).join('')}
              </div>
            `}
          </div>
        </div>
      </div>
    `;

    host.querySelector('#siRefreshBtn')?.addEventListener('click', async () => {
      try {
        const res = await api('search_insights_get', {});
        S.searchInsights = res.search_insights || null;
        mountSearchInsightsPanel(host);
        toast('Site içi arama istatistikleri güncellendi.');
      } catch (err) {
        toast(err.message, true);
      }
    });

    host.querySelector('#siClearBtn')?.addEventListener('click', async () => {
      if (!window.confirm('Site içi arama istatistikleri sıfırlansın mı?')) return;
      try {
        const res = await api('search_insights_clear', {});
        S.searchInsights = res.search_insights || null;
        mountSearchInsightsPanel(host);
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      }
    });

    host.querySelectorAll('[data-si-write-ai]').forEach(btn => {
      btn.onclick = () => {
        const topic = btn.getAttribute('data-si-write-ai') || '';
        S.aiWriterDraft = Object.assign({}, S.aiWriterDraft, { topic });
        S.openAiWriterOnNew = true;
        navigateTo('blog', () => startNewBlogPost());
      };
    });
  }

  /* ---- MODULE 4: INTERACTIVE CLIENT PROPOSALS & 4-STEP AUTOMATION CHAIN STUDIO (/admin/?view=proposals) ---- */
  function createBlankProposalDraft() {
    const dt = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);
    const rnd = Math.random().toString(36).slice(2, 8);
    return {
      id: '',
      code: 'zn-' + rnd,
      proposal_no: 'ZYN-' + new Date().getFullYear() + '-' + rnd.slice(0, 4).toUpperCase(),
      client_name: '',
      client_company: '',
      client_phone: '',
      client_email: '',
      target_domain: '',
      annual_renewal_amount: 7500,
      client_tax_office: '',
      client_tax_no: '',
      client_address: '',
      contract_no: '',
      contract_date: '',
      automation_chain: null,
      project_title: 'Kurumsal Web Tasarım, Özel Yazılım & Dijital Büyüme Teklifi',
      intro_note: 'Markanızın kurumsal hedefleri doğrultusunda hazırladığımız teknik kapsam, teslim takvimi ve yatırım detaylarını aşağıda inceleyebilirsiniz.',
      delivery_time: '15 – 20 İş Günü',
      valid_until: dt,
      currency: 'TRY',
      discount: 0,
      vat_rate: 20,
      payment_terms: '%50 İş Başlangıcı · %50 Test & Canlı Yayın Tesliminde',
      extra_notes: '1 yıl ücretsiz teknik destek, SSL güvenlik sertifikası ve Yönetim Stüdyosu eğitimi teklife dahildir.',
      status: 'sent',
      items: [
        { title: 'Kurumsal Web Tasarım & Apple Cam Yönetim Stüdyosu', desc: '%100 mobil ve PageSpeed uyumlu özel arayüz tasarımı, canlı sayfa düzenleyici ve çok dilli altyapı', qty: 1, unit_price: 28500 },
        { title: 'Teknik SEO, Yapay Zeka Görünürlüğü (GEO) & Sitemap Kurulumu', desc: 'Schema.org kurumsal veri işaretlemesi, llms.txt, IndexNow ve hız optimizasyonu', qty: 1, unit_price: 6500 }
      ]
    };
  }

  function createDefaultTrackerDraft() {
    const today = new Date().toISOString().slice(0, 10);
    const plus21 = new Date(Date.now() + 21 * 86400000).toISOString().slice(0, 10);
    const randCode = 'zyn-' + Math.random().toString(36).slice(2, 8);
    return {
      id: '',
      code: randCode,
      client_name: '',
      client_company: '',
      client_phone: '',
      client_email: '',
      project_title: 'Kurumsal Web Tasarım & Dijital Stüdyo Projesi',
      package_scope: 'Kurumsal Web + Çoklu Dil + %100 PageSpeed SEO',
      manager_name: 'Zeynart Proje Ekibi',
      start_date: today,
      estimated_delivery: plus21,
      demo_url: '',
      live_url: '',
      proposal_code: '',
      vcard_slug: (S.store?.vcards?.[0]?.slug) || '',
      status: 'active',
      latest_update_note: 'Proje başlangıç analizi ve bilgi mimarisi planlaması tamamlandı.',
      milestones: [
        { step: 1, title: 'Analiz, Strateji & Bilgi Mimarisi', desc: 'Sektörel rakip analizi, site haritası, SEO anahtar kelime kurgusu ve dönüşüm hunisinin netleştirilmesi.', status: 'completed', completed_at: today },
        { step: 2, title: 'Kurumsal UI/UX Tasarım & Görsel Sistem', desc: 'Marka kimliğine özel modern arayüz tasarımı, mobil öncelikli yerleşim ve tipografi sisteminin hazırlanması.', status: 'current', completed_at: '' },
        { step: 3, title: 'Saf PHP Mimarisi, Yönetim Stüdyosu & Çoklu Dil', desc: 'Yüksek hızlı ön yüz kodlaması, Apple Cam Yönetim Stüdyosu v3 ve TR/EN/DE/RU dil altyapısının inşası.', status: 'upcoming', completed_at: '' },
        { step: 4, title: '%100 PageSpeed, Güvenlik & Yapay Zeka SEO Testi', desc: 'LiteSpeed LSCache, Zero-Cookie KVKK uyumu, Schema.org/llms.txt ve tüm cihazlarda hız/form testleri.', status: 'upcoming', completed_at: '' },
        { step: 5, title: 'Canlı Yayın, SSL & Yönetici Teslimi 🚀', desc: 'DNS/SSL geçişi, Google Search Console doğrulaması ve yönetim panelinin anahtar teslim devreye alınması.', status: 'upcoming', completed_at: '' }
      ]
    };
  }

  function createDefaultRenewalDraft() {
    const nextYear = new Date(Date.now() + 365 * 86400000).toISOString().slice(0, 10);
    return {
      id: '',
      domain: '',
      client_name: '',
      company: '',
      phone: '',
      email: '',
      service_type: 'Kurumsal Hosting + Domain + SSL',
      package_name: 'Pro LiteSpeed Paket',
      renewal_date: nextYear,
      amount: 4500,
      currency: 'TRY',
      vcard_slug: (S.store?.vcards?.[0]?.slug) || '',
      status: 'active',
      notes: ''
    };
  }

  function getSocialPlatformMeta(rawUrl) {
    const u = String(rawUrl || '').trim().toLowerCase();
    const svgs = {
      instagram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>',
      facebook: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
      linkedin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>',
      x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 4l11.73 16h4.27L8.27 4z"/><path d="M4 20l6.77-6.77M13.23 10.77 20 4"/></svg>',
      youtube: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="2" y="5" width="20" height="14" rx="4"/><polygon points="10 9 15 12 10 15 10 9"/></svg>',
      tiktok: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>',
      whatsapp: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
      behance: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 7h5a2.5 2.5 0 0 1 0 5H3V7zm0 5h5.5a2.5 2.5 0 0 1 0 5H3v-5z"/><path d="M14 14h7a3.5 3.5 0 1 0-3.5 3.5 3.4 3.4 0 0 0 2.8-1.4"/><line x1="15" y1="8" x2="20" y2="8"/></svg>',
      pinterest: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="10"/><path d="M8 20l3-9"/><path d="M10.7 14c.4 1 1.3 1.5 2.3 1.5 2 0 3.5-1.8 3.5-4.2 0-2.3-1.8-4.3-4.5-4.3-2.8 0-4.8 2-4.8 4.4 0 1 .4 2 1.1 2.6"/></svg>',
      telegram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
      github: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>',
      dribbble: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="10"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/></svg>',
      web: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>'
    };
    if (u.includes('instagram.com') || u.includes('instagr.am')) return { key: 'instagram', label: 'Instagram', svg: svgs.instagram };
    if (u.includes('facebook.com') || u.includes('fb.com') || u.includes('fb.me')) return { key: 'facebook', label: 'Facebook', svg: svgs.facebook };
    if (u.includes('linkedin.com') || u.includes('lnkd.in')) return { key: 'linkedin', label: 'LinkedIn', svg: svgs.linkedin };
    if (u.includes('x.com') || u.includes('twitter.com')) return { key: 'x', label: 'X (Twitter)', svg: svgs.x };
    if (u.includes('youtube.com') || u.includes('youtu.be')) return { key: 'youtube', label: 'YouTube', svg: svgs.youtube };
    if (u.includes('tiktok.com')) return { key: 'tiktok', label: 'TikTok', svg: svgs.tiktok };
    if (u.includes('wa.me') || u.includes('whatsapp.com')) return { key: 'whatsapp', label: 'WhatsApp', svg: svgs.whatsapp };
    if (u.includes('behance.net')) return { key: 'behance', label: 'Behance', svg: svgs.behance };
    if (u.includes('pinterest.') || u.includes('pin.it')) return { key: 'pinterest', label: 'Pinterest', svg: svgs.pinterest };
    if (u.includes('t.me') || u.includes('telegram.')) return { key: 'telegram', label: 'Telegram', svg: svgs.telegram };
    if (u.includes('github.com')) return { key: 'github', label: 'GitHub', svg: svgs.github };
    if (u.includes('dribbble.com')) return { key: 'dribbble', label: 'Dribbble', svg: svgs.dribbble };
    return { key: 'web', label: u ? 'Sosyal / Web' : 'Yeni Hesap', svg: svgs.web };
  }

  async function applyUrlStateToStudio(searchStr) {
    const urlParams = new URLSearchParams(searchStr ?? window.location.search ?? '');
    const qEditPage = urlParams.get('edit_page');
    const qEditPost = urlParams.get('edit_post');
    const qNewPage = urlParams.get('new_page');
    const qNewService = urlParams.get('new_service');
    const qNewPost = urlParams.get('new_post');
    const qCanvasUrl = urlParams.get('canvas_url');
    let qView = urlParams.get('view');
    const qTab = urlParams.get('tab');
    const qSub = urlParams.get('sub');
    const qFlt = urlParams.get('flt');

    if (!qView && qTab && (VIEW_TITLES[qTab] || SECTOR_VIEWS[qTab])) {
      qView = qTab;
    }

    if (qNewService === '1') {
      pendingHistoryMode = 'replace';
      await createNewUnifiedServiceSubpage();
      return;
    }

    isRestoringFromUrl = true;
    try {
      if (qEditPost) {
        await openBlogPostForEdit(qEditPost);
      } else if (qNewPost === '1') {
        navigateTo('blog', () => startNewBlogPost());
      } else if (qNewPage === '1') {
        await startNewUnifiedCustomPage();
      } else if (qEditPage) {
        await openUnifiedPageEditor(qEditPage, qTab || 'content', qSub || '');
      } else if (qView) {
        if (qView === 'google_reviews') {
          qView = 'seo';
          S.tabs.seo = 'google_reviews';
        }
        if (qView === 'pages') {
          S.pages.mode = 'list';
          S.pages.filter = qFlt || 'all';
        } else if (qView === 'blog') {
          S.blog.mode = ['settings', 'news_bot', 'seo_links', 'authors', 'comments'].includes(qTab || '')
            ? qTab
            : 'list';
        } else if (qView === 'messages') {
          S.messagesFilter = qFlt || 'all';
          S.messagesMode = urlParams.get('mode') === 'list' ? 'list' : 'kanban';
        } else if (qView === 'proposals') {
          S.proposalsFilter = qFlt || 'all';
        } else if (qView === 'renewals') {
          S.renewalsFilter = qFlt || 'all';
        } else if (qView === 'uptime_tower') {
          S.uptime.filter = qFlt || 'all';
        } else if (qTab && (S.tabs[qView] !== undefined || SECTOR_VIEWS[qView])) {
          S.tabs[qView] = qTab;
        }
        await navigateTo(qView);
      } else {
        await navigateTo('dashboard');
      }
    } finally {
      isRestoringFromUrl = false;
    }
    syncAdminUrl('replace');
    if (qCanvasUrl && qCanvasUrl.startsWith('/')) {
      const cleanCv = qCanvasUrl.split('?')[0];
      setTimeout(() => {
        openImageCanvasStudio({
          url: cleanCv,
          name: cleanCv.split('/').pop() || 'gorsel.webp',
          alt: humanizeAltFromFilename(cleanCv.split('/').pop() || '')
        });
      }, 120);
    }
  }

  /* ======================== v5.2 GLOBAL CORE API FOR SECTOR EXTENSIONS ======================== */
  const STUDIO_CORE_API = (window.__STUDIO_CORE = {
    S,
    CFG,
    ROOT,
    api,
    apiUpload,
    toast,
    esc,
    uid,
    slugifyClient,
    resolveUrl,
    resolvePreviewUrl,
    navigateTo: (...args) => navigateTo(...args),
    runAdminJump: (...args) => runAdminJump(...args),
    syncAdminUrl: (...args) => syncAdminUrl(...args),
    getActiveRole: () => (S.currentUser?.role || S.store?.current_user?.role || CFG.userRole || 'super_admin'),
    isSuperAdmin,
    openMediaPicker,
    openImageCanvasStudio: (...args) => openImageCanvasStudio(...args),
    openUnifiedPageEditor: (...args) => openUnifiedPageEditor(...args),
    mountSystemPerformanceTab: (...args) => mountSystemPerformanceTab(...args),
    registerView: registerSectorViewClient
  });

  /* ======================== STUDIO INITIALIZATION ======================== */
  async function initStudio() {
    if (typeof MutationObserver === 'function' && ROOT) {
      const rootObs = new MutationObserver(() => {
        ensureConsolidatedFamilyBar();
        syncSidebarActiveTree();
      });
      rootObs.observe(ROOT, { childList: true });
    }

    document.querySelectorAll('#zsNav .zs-nav-chev[data-nav-toggle]').forEach(chev => {
      chev.addEventListener('click', ev => {
        ev.preventDefault();
        ev.stopPropagation();
        const wrap = chev.closest('.zs-nav-item-wrap');
        if (!wrap) return;
        if (wrap.classList.contains('is-active')) {
          wrap.classList.toggle('is-collapsed');
        } else {
          const willPeek = !wrap.classList.contains('is-peek-open');
          document.querySelectorAll('#zsNav .zs-nav-item-wrap.is-peek-open').forEach(w => {
            if (w !== wrap) w.classList.remove('is-peek-open');
          });
          wrap.classList.toggle('is-peek-open', willPeek);
        }
      });
    });

    document.querySelectorAll('#zsNav .zs-nav-btn').forEach(btn => {
      btn.addEventListener('click', ev => {
        if (ev.target && ev.target.closest('.zs-nav-chev')) return;
        const v = btn.getAttribute('data-view');
        if (v === 'blog') S.blog.mode = 'list';
        if (v === 'blog_categories') S.blogCats.editingId = null;
        if (v === 'pages') { S.pages.mode = 'list'; S.pages.filter = 'all'; }
        if (v === 'proposals') S.editingProposal = null;
        if (v === 'client_portal') S.editingTracker = null;
        if (v === 'renewals') S.editingRenewal = null;
        if (v === 'ota_hub') S.otaEditingNode = null;
        navigateTo(v);
      });
    });

    document.querySelectorAll('#zsNav .zs-nav-sub-btn[data-nav-jump]').forEach(subBtn => {
      subBtn.addEventListener('click', () => {
        runAdminJump(subBtn.getAttribute('data-nav-jump'));
      });
    });

    const navFilterInp = document.getElementById('zsNavFilterInput');
    const navFilterClr = document.getElementById('zsNavFilterClear');
    if (navFilterInp) {
      let userActivatedFilter = false;
      const unlockFilter = () => {
        userActivatedFilter = true;
        navFilterInp.removeAttribute('readonly');
      };
      navFilterInp.addEventListener('mousedown', unlockFilter);
      navFilterInp.addEventListener('click', unlockFilter);
      navFilterInp.addEventListener('touchstart', unlockFilter, { passive: true });
      navFilterInp.addEventListener('focus', unlockFilter);
      navFilterInp.addEventListener('blur', () => {
        if (!navFilterInp.value.trim()) {
          userActivatedFilter = false;
          navFilterInp.setAttribute('readonly', 'readonly');
        }
      });
      navFilterInp.addEventListener('input', () => {
        let isAutoFilled = false;
        try { isAutoFilled = navFilterInp.matches(':-webkit-autofill'); } catch (_) {}
        if (!userActivatedFilter || isAutoFilled) {
          navFilterInp.value = '';
          navFilterInp.setAttribute('readonly', 'readonly');
          applySidebarNavFilter('');
          return;
        }
        applySidebarNavFilter(navFilterInp.value);
      });
      navFilterInp.addEventListener('keydown', ev => {
        if (ev.key === 'Escape') {
          navFilterInp.value = '';
          userActivatedFilter = false;
          navFilterInp.setAttribute('readonly', 'readonly');
          applySidebarNavFilter('');
          navFilterInp.blur();
        } else if (ev.key === 'Enter') {
          ev.preventDefault();
          const firstSub = Array.from(document.querySelectorAll('#zsNav .zs-nav-item-wrap:not([style*="display: none"]) .zs-nav-sub-btn'))
            .find(el => el.style.display !== 'none' && el.offsetParent !== null);
          if (firstSub) {
            firstSub.click();
            return;
          }
          const firstMain = Array.from(document.querySelectorAll('#zsNav .zs-nav-item-wrap'))
            .find(w => w.style.display !== 'none')?.querySelector('.zs-nav-btn');
          if (firstMain) firstMain.click();
        }
      });
    }
    if (navFilterClr && navFilterInp) {
      navFilterClr.addEventListener('click', () => {
        navFilterInp.value = '';
        navFilterInp.removeAttribute('readonly');
        applySidebarNavFilter('');
        navFilterInp.focus();
      });
    }
    document.getElementById('zsNavEmptyOpenCmdBtn')?.addEventListener('click', () => {
      const qVal = navFilterInp ? navFilterInp.value.trim() : '';
      openCommandPalette(qVal);
    });

    const qcWrap = document.getElementById('zsQuickCreateWrap');
    const qcBtn = document.getElementById('zsQuickCreateBtn');
    const qcMenu = document.getElementById('zsQuickCreateMenu');
    const closeQuickCreate = () => {
      if (!qcMenu) return;
      qcMenu.classList.remove('open');
      qcMenu.style.display = 'none';
      qcBtn?.setAttribute('aria-expanded', 'false');
    };
    if (qcBtn && qcMenu) {
      qcBtn.addEventListener('click', ev => {
        ev.stopPropagation();
        const willOpen = !qcMenu.classList.contains('open');
        document.getElementById('zsNotifDropdown')?.classList.remove('open');
        qcMenu.classList.toggle('open', willOpen);
        qcMenu.style.display = willOpen ? 'grid' : 'none';
        qcBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
      qcMenu.addEventListener('click', () => closeQuickCreate());
      document.addEventListener('click', ev => {
        if (qcMenu.classList.contains('open') && qcWrap && !qcWrap.contains(ev.target)) {
          closeQuickCreate();
        }
      });
    }
    document.getElementById('zsQuickUploadMediaBtn')?.addEventListener('click', () => {
      runAdminJump('media:upload');
    });
    document.getElementById('zsQuickNewPortalBtn')?.addEventListener('click', () => {
      runAdminJump('client_portal');
    });
    document.querySelectorAll('#zsQuickCreateMenu [data-sector-quick-jump]').forEach(qBtn => {
      qBtn.addEventListener('click', () => {
        runAdminJump(qBtn.getAttribute('data-sector-quick-jump'));
      });
    });

    document.getElementById('zsTopbarPurgeBtn')?.addEventListener('click', async function () {
      const btn = this;
      btn.disabled = true;
      const orig = btn.innerHTML;
      btn.textContent = '⏳ Temizleniyor...';
      try {
        const res = await api('cache_purge', { purge_og: false });
        if (res.system_health) S.systemHealth = res.system_health;
        toast(res.message);
        if (S.view === 'performance') {
          mountSystemPerformanceTab(document.getElementById('prfSysDiagHost'));
        }
        if (S.view === 'settings' && S.tabs.settings === 'performance') {
          mountSystemPerformanceTab(document.getElementById('setPerformanceHost'));
        }
      } catch (err) {
        toast(err.message, true);
      } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
      }
    });

    document.getElementById('zsBurgerBtn')?.addEventListener('click', () => {
      document.getElementById('zsSidebar')?.classList.toggle('open');
    });

    document.getElementById('zsQuickEditHomeBtn')?.addEventListener('click', () => {
      openUnifiedPageEditor('anasayfa', 'content');
    });

    document.getElementById('zsQuickNewProposalBtn')?.addEventListener('click', () => {
      S.editingProposal = createBlankProposalDraft();
      navigateTo('proposals');
    });

    document.getElementById('zsQuickNewPageBtn')?.addEventListener('click', () => {
      startNewUnifiedCustomPage();
    });

    const notifWrap = document.getElementById('zsNotifWrap');
    const notifBtn = document.getElementById('zsNotifBtn');
    const notifDrop = document.getElementById('zsNotifDropdown');
    if (notifBtn && notifDrop) {
      notifBtn.addEventListener('click', ev => {
        ev.stopPropagation();
        const isOpen = !notifDrop.classList.contains('open');
        notifDrop.classList.toggle('open', isOpen);
        notifDrop.style.display = isOpen ? 'block' : 'none';
        notifBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) {
          refreshNotificationsBell();
        }
      });
      document.addEventListener('click', ev => {
        if (notifDrop.classList.contains('open') && notifWrap && !notifWrap.contains(ev.target)) {
          notifDrop.classList.remove('open');
          notifDrop.style.display = 'none';
          notifBtn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    tickAdminClocks();
    if (!navigator.webdriver) {
      setInterval(tickAdminClocks, 1000);
    }

    document.getElementById('zsQuickNewPostBtn')?.addEventListener('click', () => {
      navigateTo('blog', () => startNewBlogPost());
    });

    document.getElementById('zsLogoutBtn')?.addEventListener('click', async () => {
      try {
        await api('logout', {});
      } catch (_) {}
      window.location.reload();
    });

    document.getElementById('zsMediaModalClose')?.addEventListener('click', () => {
      document.getElementById('zsMediaModal').classList.remove('open');
      revokeActivePreviewBlobs();
    });
    document.getElementById('zsMediaModal')?.addEventListener('click', e => {
      if (e.target.id === 'zsMediaModal') {
        document.getElementById('zsMediaModal').classList.remove('open');
        revokeActivePreviewBlobs();
      }
    });

    // Global Ctrl+V Clipboard Image / Screenshot Paste Uploader
    document.addEventListener('paste', ev => {
      const pickerOpen = document.getElementById('zsMediaModal')?.classList.contains('open');
      if (!pickerOpen && S.view !== 'media') return;

      const items = Array.from(ev.clipboardData?.items || []);
      const imgFiles = [];
      items.forEach((it, idx) => {
        if (it.kind === 'file' && it.type && it.type.startsWith('image/')) {
          const rawF = it.getAsFile();
          if (rawF) {
            const ext = (rawF.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
            const stamp = new Date().toISOString().replace(/[-:T]/g, '').slice(0, 14);
            const hasRealName = rawF.name && !/^image\./i.test(rawF.name);
            const namedFile = hasRealName
              ? rawF
              : new File([rawF], `ekran-goruntusu-${stamp}-${idx + 1}.${ext}`, { type: rawF.type });
            imgFiles.push(namedFile);
          }
        }
      });

      if (imgFiles.length > 0) {
        ev.preventDefault();
        if (pickerOpen) {
          handlePickerUpload(imgFiles);
        } else {
          triggerMainMediaUpload(imgFiles);
        }
      }
    });

    document.getElementById('zsImgStudioClose')?.addEventListener('click', () => {
      document.getElementById('zsImageStudioModal')?.classList.remove('open');
    });
    document.getElementById('zsImageStudioModal')?.addEventListener('click', e => {
      if (e.target.id === 'zsImageStudioModal') {
        document.getElementById('zsImageStudioModal')?.classList.remove('open');
      }
    });

    document.getElementById('zsSocialModalClose')?.addEventListener('click', () => {
      document.getElementById('zsSocialModal')?.classList.remove('open');
    });
    document.getElementById('zsSocialModal')?.addEventListener('click', e => {
      if (e.target.id === 'zsSocialModal') {
        document.getElementById('zsSocialModal')?.classList.remove('open');
      }
    });

    document.getElementById('zsCmdBtn')?.addEventListener('click', () => openCommandPalette());
    document.getElementById('zsCmdClose')?.addEventListener('click', () => {
      document.getElementById('zsCmdModal').classList.remove('open');
    });
    document.getElementById('zsCmdInput')?.addEventListener('input', e => {
      renderCommandItems(e.target.value);
    });

    document.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        openCommandPalette();
      } else if (e.key === 'Escape') {
        const imgStudioModal = document.getElementById('zsImageStudioModal');
        const mediaModal = document.getElementById('zsMediaModal');
        if (imgStudioModal && imgStudioModal.classList.contains('open')) {
          imgStudioModal.classList.remove('open');
        } else if (mediaModal && mediaModal.classList.contains('open')) {
          mediaModal.classList.remove('open');
        } else {
          document.getElementById('zsSocialModal')?.classList.remove('open');
          document.getElementById('zsCmdModal')?.classList.remove('open');
          document.getElementById('zsNotifDropdown')?.classList.remove('open');
          document.getElementById('zsNotifBtn')?.setAttribute('aria-expanded', 'false');
          closeQuickCreate();
        }
      }
    });

    try {
      const boot = await api('boot', {});
      S.bootMs = boot.boot_ms || null;
      S.turboEngine = boot.turbo_engine || null;
      S.databaseStatus = boot.database_status || null;
      S.versionHistory = Array.isArray(boot.version_history) ? boot.version_history : [];
      S.unusedFilesSummary = boot.unused_files_summary || null;
      S.otaSummary = boot.ota_summary || null;
      S.adminDirName = boot.admin_dir_name || CFG.adminDirName || 'admin';
      S.sectorExtensions = boot.sector_extensions || null;
      S.store = boot.store || {};
      if (boot.blog_categories_payload) {
        syncBlogCatsFromApiPayload(boot.blog_categories_payload);
      }
      S.currentUser = boot.current_user || S.store.current_user || null;
      syncUserRoleUi();
      ensureNormalizedHomeSections();
      S.homeLayoutSavedSnap = computeHomeLayoutSnap();
      S.summary = boot.summary || {};
      if (S.summary.analytics) {
        S.analytics.report = S.summary.analytics;
      }
      S.robotsPreview = boot.robots_preview || '';
      S.llmsPreview = boot.llms_preview || '';
      S.logs404 = boot.logs_404 || [];
      S.revisions = boot.revisions || [];
      S.securityLogs = boot.security_logs || [];
      S.lockedIps = boot.locked_ips || [];
      S.securityAudit20 = boot.security_audit_20 || null;
      S.currentIp = boot.current_ip || '';
      S.newsBotQueue = boot.news_bot_queue || [];
      S.newsBotState = boot.news_bot_state || null;
      S.newsBotCronUrl = boot.news_bot_cron_url || '';
      S.uptime.monitors = boot.uptime_monitors || [];
      S.uptime.summary = boot.uptime_summary || null;
      S.uptimeCronUrl = boot.uptime_cron_url || '';
      S.proposals = boot.proposals || [];
      S.blogComments = boot.blog_comments || [];
      S.searchInsights = boot.search_insights || null;
      S.systemHealth = boot.system_health || null;
      S.vaultBackups = boot.vault_backups || [];
      S.indexnowLogs = boot.indexnow_logs || [];
      S.crmStages = boot.crm_stages || {};
      S.crmSummary = boot.crm_summary || null;
      S.clientTrackers = boot.client_trackers || [];
      S.clientTrackersSummary = boot.client_trackers_summary || null;
      S.renewals = boot.renewals || [];
      S.renewalsSummary = boot.renewals_summary || null;
      S.bookings = boot.bookings || [];
      S.bookingsSummary = boot.bookings_summary || null;
      S.solutionCatalog = boot.solution_catalog || null;
      updateSidebarBadges();
      if (!navigator.webdriver) {
        setInterval(() => {
          if (document.visibilityState === 'visible' && (S.view === 'analytics' || S.view === 'dashboard')) {
            refreshAnalyticsReport(S.view === 'analytics' ? S.analytics.rangeDays : 30, true);
          }
        }, 15000);
      }
      window.addEventListener('popstate', () => {
        if (S.store) applyUrlStateToStudio(window.location.search);
      });
      if (typeof window.__initClubStudio === 'function') {
        try { await window.__initClubStudio(STUDIO_CORE_API, boot); } catch (_) {}
      }
      for (const bootHook of SECTOR_BOOT_HOOKS) {
        try { await bootHook(STUDIO_CORE_API, boot); } catch (_) {}
      }
      syncCoreContextExports();
      updateV4SpeedTelemetry();
      bindV4IntentPrefetcher();
      await applyUrlStateToStudio(window.location.search);
    } catch (err) {
      ROOT.innerHTML = `<div class="zs-card" style="color:#fca5a5"><h3>Başlatma Hatası</h3><p>${esc(err.message)}</p></div>`;
    }
  }

  initStudio();
})();
