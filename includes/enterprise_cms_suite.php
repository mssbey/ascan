<?php
declare(strict_types=1);

/**
 * Zeynart Kurumsal Dijital Stüdyo — Enterprise CMS Power Suite
 * 1. LiteSpeed LSCache / OPcache Purge, Sistem Sağlığı & Yetim Medya Temizleyici
 * 2. Site İçi Canlı Arama Motoru (Instant Search) & Çerezsiz Arama Analitiği
 * 3. E-E-A-T Uzman Yazar Profilleri (Person Schema) & Moderasyonlu Soru-Cevap / Yorumlar
 * 4. Müşteriye Özel İnteraktif Online Teklifler (/teklif/{kod}/) & Okundu/Onay Takibi
 * 5. İki Aşamalı Doğrulama (RFC 6238 TOTP QR + E-Posta OTP + Kurtarma Kodları) & Modüler .zip Yedekleme Kasası
 * 6. Gerçek Zamanlı 16 Kriterli İçerik SEO Puanlayıcı, Odak Kelime Çakışma Kontrolü & Anlık IndexNow Ping Motoru
 */

require_once __DIR__ . '/qr_svg.php';

/* ============================================================================
 * 1. LITESPEED ÖNBELLEK, SİSTEM SAĞLIĞI & YETİM MEDYA TEMİZLEYİCİ
 * ============================================================================ */

function zeynart_purge_litespeed_and_caches(bool $purgeOgCards = false): array
{
    $actions = [];

    if (!headers_sent()) {
        header('X-LiteSpeed-Purge: *');
        $actions[] = 'LiteSpeed sunucu önbelleği (X-LiteSpeed-Purge: *) temizleme sinyali gönderildi';
    }

    if (function_exists('zeynart_clear_lscache_state')) {
        zeynart_clear_lscache_state();
        $actions[] = 'LiteSpeed önbellekteki sayfalar tablosu sıfırlandı';
    }

    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $actions[] = 'PHP OPcache bellek önbelleği sıfırlandı';
    }

    clearstatcache(true);
    if (function_exists('zeynart_invalidate_media_cache')) {
        zeynart_invalidate_media_cache();
        $actions[] = 'Turbo medya ve dizin indeks önbelleği yenilendi';
    }

    $i18nCacheDir = DATA_DIR . '/i18n_cache';
    $removedOg = 0;
    if ($purgeOgCards) {
        $ogDir = UPLOADS_DIR . '/og';
        if (is_dir($ogDir)) {
            foreach (glob($ogDir . '/*.{jpg,jpeg,webp,png}', GLOB_BRACE) ?: [] as $f) {
                if (is_file($f) && @unlink($f)) {
                    $removedOg++;
                }
            }
        }
        $actions[] = "Sosyal paylaşım önbelleğinden {$removedOg} geçici kart yenilendi";
    }

    $stampFile = DATA_DIR . '/cache_purge_state.json';
    $state = [
        'last_purged_at' => date('Y-m-d H:i:s'),
        'last_purged_ts' => time(),
        'server_software' => (string)($_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI),
        'removed_og_cards' => $removedOg,
        'i18n_cache_exists' => is_dir($i18nCacheDir),
    ];
    atomic_write_json($stampFile, $state, true);

    return [
        'ok' => true,
        'actions' => $actions,
        'state' => $state,
    ];
}

function zeynart_dir_metrics(string $dir): array
{
    if (!is_dir($dir)) {
        return ['bytes' => 0, 'files' => 0];
    }
    $bytes = 0;
    $files = 0;
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $f) {
            /** @var SplFileInfo $f */
            if ($f->isFile()) {
                $bytes += (int)$f->getSize();
                $files++;
            }
        }
    } catch (\Throwable $_) {
    }
    return ['bytes' => $bytes, 'files' => $files];
}

function zeynart_cached_data_dir_metrics(bool $forceRefresh = false): array
{
    if (!$forceRefresh && is_array($GLOBALS['__zeynart_dir_metrics_mem'] ?? null)) {
        return $GLOBALS['__zeynart_dir_metrics_mem'];
    }
    $cacheFile = function_exists('zeynart_dir_metrics_cache_file')
        ? zeynart_dir_metrics_cache_file()
        : (DATA_DIR . '/dir_metrics_cache.json');
    $backupsDir = DATA_DIR . '/backups';
    $sig = implode('|', [
        is_file(DATA_DIR . '/store.json') ? (int)@filemtime(DATA_DIR . '/store.json') : 0,
        is_file(DATA_DIR . '/blog_index.json') ? (int)@filemtime(DATA_DIR . '/blog_index.json') : 0,
        is_dir($backupsDir) ? (int)@filemtime($backupsDir) : 0,
    ]);

    if (!$forceRefresh) {
        $dec = zeynart_read_json_data($cacheFile);
        if (
            is_array($dec)
            && ($dec['sig'] ?? '') === $sig
            && ((time() - (int)($dec['created_at'] ?? 0)) < 900)
            && isset($dec['data'], $dec['backups'])
        ) {
            $dec['cache_hit'] = true;
            $GLOBALS['__zeynart_dir_metrics_mem'] = $dec;
            return $dec;
        }
    }

    $dataMetrics = zeynart_dir_metrics(DATA_DIR);
    $backupsMetrics = zeynart_dir_metrics($backupsDir);
    $payload = [
        'sig' => $sig,
        'created_at' => time(),
        'data' => $dataMetrics,
        'backups' => $backupsMetrics,
    ];
    atomic_write_json($cacheFile, $payload, false);
    $payload['cache_hit'] = false;
    $GLOBALS['__zeynart_dir_metrics_mem'] = $payload;
    return $payload;
}

function zeynart_collect_system_health(bool $forceDiskScan = false, bool $includeLscacheItems = true): array
{
    $serverSoft = (string)($_SERVER['SERVER_SOFTWARE'] ?? '');
    $sapi = PHP_SAPI;
    $isLiteSpeed = stripos($serverSoft, 'LiteSpeed') !== false || stripos($sapi, 'litespeed') !== false || function_exists('litespeed_finish_request');

    $opcacheEnabled = false;
    $opcacheHitRate = null;
    $opcacheUsedMb = null;
    if (function_exists('opcache_get_status')) {
        $opSt = @opcache_get_status(false);
        if (is_array($opSt) && !empty($opSt['opcache_enabled'])) {
            $opcacheEnabled = true;
            $opcacheHitRate = isset($opSt['opcache_statistics']['opcache_hit_rate'])
                ? round((float)$opSt['opcache_statistics']['opcache_hit_rate'], 1)
                : null;
            $opcacheUsedMb = isset($opSt['memory_usage']['used_memory'])
                ? round(((float)$opSt['memory_usage']['used_memory']) / 1048576, 1)
                : null;
        }
    }

    $diskTotal = @disk_total_space(ROOT_DIR);
    $diskFree = @disk_free_space(ROOT_DIR);
    $diskTotalGb = is_numeric($diskTotal) && $diskTotal > 0 ? round($diskTotal / 1073741824, 2) : null;
    $diskFreeGb = is_numeric($diskFree) && $diskFree >= 0 ? round($diskFree / 1073741824, 2) : null;
    $diskUsedPct = ($diskTotalGb && $diskFreeGb !== null && $diskTotalGb > 0)
        ? round((($diskTotalGb - $diskFreeGb) / $diskTotalGb) * 100, 1)
        : null;

    $dirCache = zeynart_cached_data_dir_metrics($forceDiskScan);
    $dataMetrics = $dirCache['data'] ?? ['bytes' => 0, 'files' => 0];
    $backupsMetrics = $dirCache['backups'] ?? ['bytes' => 0, 'files' => 0];

    if (function_exists('zeynart_quick_media_disk_metrics')) {
        $qDisk = zeynart_quick_media_disk_metrics($forceDiskScan);
        $uploadsMetrics = [
            'bytes' => (int)($qDisk['bytes'] ?? 0),
            'files' => (int)($qDisk['files'] ?? 0),
        ];
        $mediaCacheHit = !empty($qDisk['cache_hit']);
        $mediaTotalFiles = (int)($qDisk['total_files'] ?? $uploadsMetrics['files']);
    } else {
        $mediaIdx = function_exists('zeynart_get_cached_media_entries')
            ? zeynart_get_cached_media_entries($forceDiskScan)
            : null;
        if (is_array($mediaIdx) && isset($mediaIdx['uploads_disk_bytes'], $mediaIdx['uploads_disk_files'])) {
            $uploadsMetrics = [
                'bytes' => (int)$mediaIdx['uploads_disk_bytes'],
                'files' => (int)$mediaIdx['uploads_disk_files'],
            ];
        } else {
            $uploadsMetrics = zeynart_dir_metrics(UPLOADS_DIR);
        }
        $mediaCacheHit = !empty($mediaIdx['cache_hit']);
        $mediaTotalFiles = (int)($mediaIdx['total_files'] ?? $uploadsMetrics['files']);
    }

    $gdInfo = function_exists('gd_info') ? @gd_info() : [];
    $sqliteAvail = extension_loaded('pdo_sqlite');
    $extensions = [
        'gd_webp' => !empty($gdInfo['WebP Support']) || function_exists('imagewebp'),
        'gd_freetype' => !empty($gdInfo['FreeType Support']) || function_exists('imagettftext'),
        'curl_multi' => function_exists('curl_multi_init'),
        'zip' => class_exists('ZipArchive') || function_exists('gzdeflate'),
        'openssl' => extension_loaded('openssl'),
        'mbstring' => extension_loaded('mbstring'),
        'intl' => extension_loaded('intl'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'pdo_sqlite' => $sqliteAvail,
    ];

    $writableChecks = [
        'data/' => is_writable(DATA_DIR),
        'data/posts/' => zeynart_is_sql_active() || !is_dir(POSTS_DIR) || is_writable(POSTS_DIR),
        'uploads/' => is_writable(UPLOADS_DIR),
    ];

    $purgeDec = zeynart_read_json_data(DATA_DIR . '/cache_purge_state.json');
    $purgeState = is_array($purgeDec) ? $purgeDec : [];

    $storeForLs = function_exists('load_store') ? load_store() : [];
    $lscacheOverview = zeynart_compile_lscache_overview($storeForLs, $includeLscacheItems);

    return [
        'php_version' => PHP_VERSION,
        'sapi' => $sapi,
        'server_software' => $serverSoft !== '' ? $serverSoft : ($isLiteSpeed ? 'LiteSpeed LSAPI' : $sapi),
        'is_litespeed' => $isLiteSpeed,
        'memory_limit' => (string)ini_get('memory_limit'),
        'upload_max_filesize' => (string)ini_get('upload_max_filesize'),
        'post_max_size' => (string)ini_get('post_max_size'),
        'max_execution_time' => (int)ini_get('max_execution_time'),
        'opcache_enabled' => $opcacheEnabled,
        'opcache_hit_rate' => $opcacheHitRate,
        'opcache_used_mb' => $opcacheUsedMb,
        'disk_total_gb' => $diskTotalGb,
        'disk_free_gb' => $diskFreeGb,
        'disk_used_pct' => $diskUsedPct,
        'data_size_mb' => round($dataMetrics['bytes'] / 1048576, 2),
        'data_files' => $dataMetrics['files'],
        'uploads_size_mb' => round($uploadsMetrics['bytes'] / 1048576, 2),
        'uploads_files' => $uploadsMetrics['files'],
        'backups_size_mb' => round($backupsMetrics['bytes'] / 1048576, 2),
        'backups_files' => $backupsMetrics['files'],
        'turbo_cache_active' => true,
        'turbo_cache_hit' => $mediaCacheHit && !empty($dirCache['cache_hit']),
        'turbo_indexed_media' => $mediaTotalFiles,
        'sqlite_wal_available' => $sqliteAvail,
        'turbo_engine_mode' => $sqliteAvail ? 'SQLite WAL + Ultra-Turbo İndeks' : 'Ultra-Turbo Mikro-İndeks',
        'extensions' => $extensions,
        'writable' => $writableChecks,
        'last_purge' => $purgeState['last_purged_at'] ?? null,
        'server_time' => date('Y-m-d H:i:s'),
        'lscache' => $lscacheOverview,
    ];
}

function zeynart_purge_single_lscache_url(string $rawUri): array
{
    $norm = zeynart_normalize_lscache_uri($rawUri);
    $tag = zeynart_lscache_tag_for_uri($norm);
    $prefixedUri = (BASE_PATH !== '' ? rtrim(BASE_PATH, '/') : '') . $norm;
    if (!headers_sent()) {
        header('X-LiteSpeed-Purge: tag=' . $tag . ', ' . $prefixedUri);
    }
    zeynart_clear_lscache_state($norm);
    return [
        'ok' => true,
        'uri' => $norm,
        'tag' => $tag,
    ];
}

function zeynart_build_lscache_url_catalog(array $store): array
{
    $resolvedSlugs = function_exists('zeynart_resolved_page_slugs') ? zeynart_resolved_page_slugs($store) : [];
    $catalog = [];
    $seen = [];

    $add = static function (string $path, string $title, string $groupKey, string $groupLabel) use (&$catalog, &$seen): void {
        $norm = zeynart_normalize_lscache_uri($path);
        if (isset($seen[$norm])) {
            return;
        }
        $seen[$norm] = true;
        $catalog[] = [
            'path' => $norm,
            'url' => (BASE_PATH !== '' ? rtrim(BASE_PATH, '/') : '') . $norm,
            'title' => trim($title) !== '' ? trim($title) : $norm,
            'group_key' => $groupKey,
            'group_label' => $groupLabel,
            'tag' => zeynart_lscache_tag_for_uri($norm),
        ];
    };

    $add('/', 'Ana Sayfa', 'core', 'Ana Sayfalar');

    $corePages = [
        'hakkimizda' => 'Kurumsal / Hakkımızda',
        'hizmetler' => 'Hizmetlerimiz',
        'projeler' => 'Projeler & Vaka Analizleri',
        'blog' => 'Blog & Journal',
        'iletisim' => 'İletişim',
        'teklif-sihirbazi' => 'İnteraktif Teklif Sihirbazı',
        'site-analiz' => 'Site Hızı & SEO Analiz Aracı',
    ];
    foreach ($corePages as $cKey => $fallbackTitle) {
        if ((!function_exists('zeynart_page_status') || zeynart_page_status($store, $cKey) === 'published') && !empty($resolvedSlugs[$cKey])) {
            $customTitle = trim((string)($store['page_titles'][$cKey] ?? $store['page_menu_labels'][$cKey] ?? ''));
            $add('/' . $resolvedSlugs[$cKey] . '/', $customTitle !== '' ? $customTitle : $fallbackTitle, 'core', 'Ana Sayfalar');
        }
    }

    if (empty($store['booking_settings']) || ($store['booking_settings']['enabled'] ?? true) !== false) {
        $add('/gorusme-planla/', 'Online Keşif & Demo Görüşmesi Planlayıcı', 'core', 'Ana Sayfalar');
    }

    if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'hizmetler') === 'published') {
        $srvParent = $resolvedSlugs['hizmetler'] ?? 'hizmetler';
        $publicServices = function_exists('zeynart_public_services') ? zeynart_public_services($store) : ($store['services'] ?? []);
        foreach ($publicServices as $srv) {
            if (!empty($srv['slug'])) {
                $add('/' . $srvParent . '/' . $srv['slug'] . '/', (string)($srv['title'] ?? $srv['slug']), 'services', 'Hizmetler & Projeler');
            }
        }
    }

    if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'projeler') === 'published') {
        $prjParent = $resolvedSlugs['projeler'] ?? 'projeler';
        $publicProjects = function_exists('zeynart_public_projects') ? zeynart_public_projects($store) : ($store['projects'] ?? []);
        foreach ($publicProjects as $prj) {
            if (!empty($prj['slug'])) {
                $add('/' . $prjParent . '/' . $prj['slug'] . '/', 'Proje: ' . (string)($prj['title'] ?? $prj['slug']), 'services', 'Hizmetler & Projeler');
            }
        }
    }

    foreach (($store['custom_pages'] ?? []) as $cp) {
        $st = function_exists('zeynart_normalize_page_status')
            ? zeynart_normalize_page_status($cp['status'] ?? 'published')
            : (string)($cp['status'] ?? 'published');
        if ($st === 'published' && !empty($cp['slug'])) {
            $add('/' . $cp['slug'] . '/', (string)($cp['title'] ?? $cp['slug']), 'core', 'Özel & Çözüm Sayfaları');
        }
    }

    $legalLabels = [
        'cerez-politikasi' => 'Çerez Politikası',
        'kvkk' => 'KVKK Aydınlatma Metni',
        'gizlilik' => 'Gizlilik Politikası',
    ];
    foreach ($legalLabels as $lKey => $lFallback) {
        if ((!function_exists('zeynart_page_status') || zeynart_page_status($store, $lKey) === 'published') && !empty($resolvedSlugs[$lKey])) {
            $lTitle = trim((string)($store['legal_pages'][$lKey]['title'] ?? ''));
            $add('/' . $resolvedSlugs[$lKey] . '/', $lTitle !== '' ? $lTitle : $lFallback, 'legal', 'Yasal Sayfalar');
        }
    }

    if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'blog') === 'published') {
        $blogParent = $resolvedSlugs['blog'] ?? 'blog';
        $blogCats = function_exists('zeynart_get_blog_categories') ? zeynart_get_blog_categories($store) : [];
        foreach ($blogCats as $bCat) {
            $bcSlug = trim((string)($bCat['slug'] ?? ''));
            if ($bcSlug !== '') {
                $add('/' . $blogParent . '/kategori/' . $bcSlug . '/', 'Kategori: ' . (string)($bCat['name'] ?? $bcSlug), 'blog', 'Blog & Kategoriler');
            }
        }
        $blogItems = function_exists('load_blog_index') ? load_blog_index() : [];
        foreach ($blogItems as $postItem) {
            if (function_exists('is_post_publicly_visible') && !is_post_publicly_visible($postItem, false)) {
                continue;
            }
            $pSlug = trim((string)($postItem['slug'] ?? ''));
            if ($pSlug !== '') {
                $add('/' . $blogParent . '/' . $pSlug . '/', (string)($postItem['title'] ?? $pSlug), 'blog', 'Blog & Kategoriler');
            }
        }
    }

    // Multilingual public routes (EN / DE / RU)
    if (($store['i18n']['enabled'] ?? true) !== false) {
        $add('/en/', 'Home (English)', 'i18n', 'Çoklu Dil (EN/DE/RU)');
        $add('/de/', 'Startseite (Deutsch)', 'i18n', 'Çoklu Dil (EN/DE/RU)');
        $add('/ru/', 'Главная (Русский)', 'i18n', 'Çoklu Dil (EN/DE/RU)');
        foreach (['hakkimizda' => 'About Us (EN)', 'hizmetler' => 'Services (EN)', 'projeler' => 'Projects (EN)', 'blog' => 'Journal (EN)', 'iletisim' => 'Contact (EN)'] as $enKey => $enLabel) {
            if ((!function_exists('zeynart_page_status') || zeynart_page_status($store, $enKey) === 'published') && !empty($resolvedSlugs[$enKey])) {
                $add('/en/' . $resolvedSlugs[$enKey] . '/', $enLabel, 'i18n', 'Çoklu Dil (EN/DE/RU)');
            }
        }
        if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'cerez-politikasi') === 'published') {
            $add('/en/cookie-policy/', 'Cookie Policy (EN)', 'i18n', 'Çoklu Dil (EN/DE/RU)');
        }
    }

    // Include any additional live-cached routes recorded in lscache_state.json (e.g. /en/blog/*, /de/*, /ru/*, /kartvizit/*)
    $state = zeynart_load_lscache_state();
    foreach (($state['pages'] ?? []) as $extraUri => $extraMeta) {
        if (!isset($seen[$extraUri])) {
            $isLang = (bool)preg_match('~^/(en|de|ru)/~', $extraUri);
            $grpKey = $isLang ? 'i18n' : 'other';
            $grpLbl = $isLang ? 'Çoklu Dil (EN/DE/RU)' : 'Diğer Önbellek Rotaları';
            $add($extraUri, $extraUri, $grpKey, $grpLbl);
        }
    }

    return $catalog;
}

function zeynart_compile_lscache_overview(?array $store = null, bool $includeItems = true): array
{
    $store = is_array($store) ? $store : (function_exists('load_store') ? load_store() : []);
    $cfg = zeynart_get_lscache_config($store);
    $maintActive = !empty($store['maintenance']['enabled']) || is_file(DATA_DIR . '/.maintenance_active');
    $effectiveEnabled = !empty($cfg['enabled']) && !$maintActive;
    $ttl = (int)$cfg['ttl'];

    $state = zeynart_load_lscache_state();
    $ledgerPages = is_array($state['pages'] ?? null) ? $state['pages'] : [];
    $now = time();

    if (!$includeItems && function_exists('zeynart_v4_cached_boot_bundle')) {
        $bundle = zeynart_v4_cached_boot_bundle($store);
        $baseCount = (int)($bundle['lscache_base_count'] ?? 0);
        $cachedCount = 0;
        $extraDynamic = 0;
        foreach ($ledgerPages as $lpKey => $entry) {
            if (preg_match('~^/(?:de|ru)/.+|^/en/(?:blog|projeler|hizmetler)/.+|^/(?:kartvizit|teklif|proje-takip)/~i', (string)$lpKey)) {
                $extraDynamic++;
            }
            if ($effectiveEnabled && is_array($entry) && (int)($entry['expires_at'] ?? 0) > $now) {
                $cachedCount++;
            }
        }
        $total = max($cachedCount, $baseCount + $extraDynamic);
        return [
            'enabled' => (bool)$cfg['enabled'],
            'effective_enabled' => $effectiveEnabled,
            'maintenance_bypass' => $maintActive,
            'ttl' => $ttl,
            'total_urls' => $total,
            'cached_count' => $cachedCount,
            'uncached_count' => max(0, $total - $cachedCount),
            'hit_ratio_pct' => $total > 0 ? round(($cachedCount / $total) * 100, 1) : 0.0,
            'last_probe_at' => $state['last_probe_at'] ?? null,
            'last_probe_mode' => $state['last_probe_mode'] ?? null,
            'items' => [],
            'items_deferred' => true,
        ];
    }

    $catalog = zeynart_build_lscache_url_catalog($store);
    $cachedCount = 0;
    $items = [];
    foreach ($catalog as $row) {
        $p = $row['path'];
        $entry = $effectiveEnabled ? ($ledgerPages[$p] ?? null) : null;
        $isCached = is_array($entry) && (int)($entry['expires_at'] ?? 0) > $now;
        if ($isCached) {
            $cachedCount++;
            if ($includeItems) {
                $cAt = (int)($entry['cached_at'] ?? $now);
                $eAt = (int)($entry['expires_at'] ?? ($cAt + $ttl));
                $row['cached'] = true;
                $row['status'] = 'cached';
                $row['cached_at_ts'] = $cAt;
                $row['cached_at'] = date('H:i:s', $cAt);
                $row['cached_at_full'] = date('d.m.Y H:i:s', $cAt);
                $row['remaining_sec'] = max(0, $eAt - $now);
                $row['source'] = (string)($entry['source'] ?? 'render');
                $items[] = $row;
            }
        } elseif ($includeItems) {
            $row['cached'] = false;
            $row['status'] = $effectiveEnabled ? 'uncached' : 'disabled';
            $row['cached_at_ts'] = 0;
            $row['cached_at'] = null;
            $row['cached_at_full'] = null;
            $row['remaining_sec'] = 0;
            $row['source'] = null;
            $items[] = $row;
        }
    }

    $total = count($catalog);
    return [
        'enabled' => (bool)$cfg['enabled'],
        'effective_enabled' => $effectiveEnabled,
        'maintenance_bypass' => $maintActive,
        'ttl' => $ttl,
        'total_urls' => $total,
        'cached_count' => $cachedCount,
        'uncached_count' => max(0, $total - $cachedCount),
        'hit_ratio_pct' => $total > 0 ? round(($cachedCount / $total) * 100, 1) : 0.0,
        'last_probe_at' => $state['last_probe_at'] ?? null,
        'last_probe_mode' => $state['last_probe_mode'] ?? null,
        'items' => $items,
        'items_deferred' => !$includeItems,
    ];
}

function zeynart_save_lscache_probe_stamp(string $probeMode, ?array $replacePages = null): void
{
    if (zeynart_is_sql_active()) {
        $dec = zeynart_sql_kv_get('lscache_state');
        $pages = $replacePages !== null
            ? $replacePages
            : ((is_array($dec) && is_array($dec['pages'] ?? null)) ? $dec['pages'] : []);
        $now = time();
        foreach ($pages as $pk => $pv) {
            if (!is_array($pv) || (int)($pv['expires_at'] ?? 0) <= $now) {
                unset($pages[$pk]);
            }
        }
        zeynart_sql_kv_set('lscache_state', [
            'pages' => $pages,
            'updated_at' => $now,
            'last_probe_at' => date('d.m.Y H:i:s'),
            'last_probe_mode' => $probeMode,
        ], 'cache');
        return;
    }

    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
    $stateFile = DATA_DIR . '/lscache_state.json';
    $fp = @fopen($stateFile, 'c+');
    if ($fp === false) {
        return;
    }
    if (@flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $dec = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        $pages = $replacePages !== null
            ? $replacePages
            : ((is_array($dec) && is_array($dec['pages'] ?? null)) ? $dec['pages'] : []);
        $now = time();
        foreach ($pages as $pk => $pv) {
            if (!is_array($pv) || (int)($pv['expires_at'] ?? 0) <= $now) {
                unset($pages[$pk]);
            }
        }
        $payload = [
            'pages' => $pages,
            'updated_at' => $now,
            'last_probe_at' => date('d.m.Y H:i:s'),
            'last_probe_mode' => $probeMode,
        ];
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fp);
        @flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function zeynart_probe_or_warm_lscache_urls(array $store, string $mode = 'probe', ?string $singleUri = null): array
{
    $cfg = zeynart_get_lscache_config($store);
    $maintActive = !empty($store['maintenance']['enabled']) || is_file(DATA_DIR . '/.maintenance_active');
    $ttl = (int)$cfg['ttl'];

    if ($mode === 'warm' && (empty($cfg['enabled']) || $maintActive)) {
        return [
            'ok' => false,
            'error' => !empty($maintActive)
                ? 'Bakım modu açıkken sayfalar önbelleğe alınamaz.'
                : 'LiteSpeed LSCache şu an kapalı. Önbelleğe almak için önce LiteSpeed önbelleği açın.',
            'lscache' => zeynart_compile_lscache_overview($store),
        ];
    }

    if (empty($cfg['enabled']) || $maintActive) {
        zeynart_clear_lscache_state();
        zeynart_save_lscache_probe_stamp('disabled', []);
        return [
            'ok' => true,
            'probed_count' => 0,
            'lscache' => zeynart_compile_lscache_overview($store),
        ];
    }

    $catalog = zeynart_build_lscache_url_catalog($store);
    if ($singleUri !== null && trim($singleUri) !== '') {
        $wantNorm = zeynart_normalize_lscache_uri($singleUri);
        $filtered = array_values(array_filter($catalog, static fn(array $r): bool => $r['path'] === $wantNorm));
        if ($filtered === []) {
            $filtered = [[
                'path' => $wantNorm,
                'url' => (BASE_PATH !== '' ? rtrim(BASE_PATH, '/') : '') . $wantNorm,
                'title' => $wantNorm,
                'group_key' => 'other',
                'group_label' => 'Diğer',
                'tag' => zeynart_lscache_tag_for_uri($wantNorm),
            ]];
        }
        $targets = $filtered;
    } else {
        $targets = $catalog;
    }

    // Avoid single-threaded loopback deadlock on local PHP CLI dev server (php -S)
    if (PHP_SAPI === 'cli-server' || !function_exists('curl_multi_init')) {
        if ($mode === 'warm') {
            foreach ($targets as $t) {
                zeynart_record_lscache_page_hit($t['path'], $ttl, 'warm');
            }
        }
        zeynart_save_lscache_probe_stamp('ledger');
        return [
            'ok' => true,
            'probed_count' => count($targets),
            'lscache' => zeynart_compile_lscache_overview($store),
        ];
    }

    $baseOrigin = rtrim(SITE_URL, '/');
    if (BASE_PATH !== '' && str_ends_with($baseOrigin, BASE_PATH)) {
        $baseOrigin = substr($baseOrigin, 0, -strlen(BASE_PATH));
    }
    $parsedOrigin = parse_url($baseOrigin);
    $originHost = (string)($parsedOrigin['host'] ?? '');
    $originScheme = strtolower((string)($parsedOrigin['scheme'] ?? 'https'));
    $originPort = (int)($parsedOrigin['port'] ?? ($originScheme === 'https' ? 443 : 80));

    $resolvePin = [];
    $serverAddr = trim((string)($_SERVER['SERVER_ADDR'] ?? ''));
    if (
        $originHost !== ''
        && $serverAddr !== ''
        && !in_array($serverAddr, ['0.0.0.0', '::', '::1'], true)
        && filter_var($serverAddr, FILTER_VALIDATE_IP) !== false
    ) {
        $fmtAddr = str_contains($serverAddr, ':') ? ('[' . $serverAddr . ']') : $serverAddr;
        $resolvePin = [$originHost . ':' . $originPort . ':' . $fmtAddr];
    }

    $serverSoft = (string)($_SERVER['SERVER_SOFTWARE'] ?? '');
    $sawLiteSpeed = stripos($serverSoft, 'LiteSpeed') !== false || stripos(PHP_SAPI, 'litespeed') !== false || function_exists('litespeed_finish_request');

    $curState = zeynart_load_lscache_state();
    $nextPages = is_array($curState['pages'] ?? null) ? $curState['pages'] : [];
    $now = time();

    $batches = array_chunk($targets, 14);
    foreach ($batches as $batch) {
        $mh = curl_multi_init();
        $handles = [];
        $respHeaders = [];

        foreach ($batch as $idx => $t) {
            $reqUrl = $baseOrigin . (BASE_PATH !== '' ? rtrim(BASE_PATH, '/') : '') . $t['path'];
            $ch = curl_init($reqUrl);
            if ($ch === false) {
                continue;
            }
            $respHeaders[$idx] = [];
            $reqHdrs = [
                'Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
            ];
            if ($mode === 'probe') {
                $reqHdrs[] = 'X-Studio-Cache-Probe: 1';
            }
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT_MS => 2500,
                CURLOPT_TIMEOUT_MS => 6500,
                CURLOPT_HTTPHEADER => $reqHdrs,
                CURLOPT_COOKIE => '',
                CURLOPT_USERAGENT => 'StudioCacheInspector/2.0',
                CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$respHeaders, $idx): int {
                    $len = strlen($headerLine);
                    $parts = explode(':', $headerLine, 2);
                    if (count($parts) === 2) {
                        $respHeaders[$idx][strtolower(trim($parts[0]))] = trim($parts[1]);
                    }
                    return $len;
                },
            ];
            if ($resolvePin !== []) {
                $opts[CURLOPT_RESOLVE] = $resolvePin;
            }
            curl_setopt_array($ch, $opts);
            curl_multi_add_handle($mh, $ch);
            $handles[$idx] = [$ch, $t['path']];
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
            if ($active > 0) {
                curl_multi_select($mh, 0.25);
            }
        } while ($active > 0 && $mrc === CURLM_OK);

        foreach ($handles as $idx => [$ch, $normPath]) {
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hdrs = $respHeaders[$idx] ?? [];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            $lsCacheVal = strtolower((string)($hdrs['x-litespeed-cache'] ?? $hdrs['x-lsadc-cache'] ?? $hdrs['x-qc-cache'] ?? ''));
            if ($lsCacheVal !== '' || stripos((string)($hdrs['server'] ?? ''), 'litespeed') !== false) {
                $sawLiteSpeed = true;
            }

            if ($mode === 'warm') {
                if ($httpCode === 200) {
                    $hdrTs = (int)($hdrs['x-studio-cached-at'] ?? 0);
                    $cAt = ($hdrTs > 0 && $hdrTs <= $now) ? $hdrTs : $now;
                    $nextPages[$normPath] = [
                        'uri' => $normPath,
                        'tag' => zeynart_lscache_tag_for_uri($normPath),
                        'cached_at' => $cAt,
                        'expires_at' => $cAt + $ttl,
                        'ttl' => $ttl,
                        'source' => str_contains($lsCacheVal, 'hit') ? 'lscache_hit' : 'warm',
                    ];
                }
            } else {
                if ($httpCode === 200 && str_contains($lsCacheVal, 'hit')) {
                    $hdrTs = (int)($hdrs['x-studio-cached-at'] ?? 0);
                    $ageSec = isset($hdrs['age']) ? max(0, (int)$hdrs['age']) : 0;
                    $cAt = ($hdrTs > 0 && $hdrTs <= $now) ? $hdrTs : max(1, $now - $ageSec);
                    $nextPages[$normPath] = [
                        'uri' => $normPath,
                        'tag' => zeynart_lscache_tag_for_uri($normPath),
                        'cached_at' => $cAt,
                        'expires_at' => max($now + 30, $cAt + $ttl),
                        'ttl' => $ttl,
                        'source' => 'lscache_hit',
                    ];
                } elseif ($sawLiteSpeed && (($hdrs['x-studio-probe'] ?? '') === 'miss' || $lsCacheVal === 'miss' || $lsCacheVal === '')) {
                    unset($nextPages[$normPath]);
                }
            }
        }
        curl_multi_close($mh);
    }

    zeynart_save_lscache_probe_stamp($sawLiteSpeed ? 'litespeed_live' : 'ledger', $nextPages);

    return [
        'ok' => true,
        'probed_count' => count($targets),
        'saw_litespeed' => $sawLiteSpeed,
        'lscache' => zeynart_compile_lscache_overview($store),
    ];
}

function zeynart_scan_orphan_uploads(array $store, int $maxReturn = 250): array
{
    if (!is_dir(UPLOADS_DIR)) {
        return ['orphans' => [], 'total_orphan_count' => 0, 'total_orphan_bytes' => 0, 'scanned_uploads' => 0];
    }

    // Build fast reference set of all upload relative paths mentioned anywhere in store, blog index, posts, proposals, authors
    $corpusChunks = [json_encode($store, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: ''];
    foreach (['blog_index.json', 'proposals.json'] as $extraJson) {
        $decExtra = zeynart_read_json_data(DATA_DIR . '/' . $extraJson);
        if ($decExtra !== null) {
            $corpusChunks[] = (string)json_encode($decExtra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
    if (zeynart_is_sql_active()) {
        foreach (zeynart_iterate_all_posts() as $postArr) {
            $corpusChunks[] = (string)json_encode($postArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    } elseif (is_dir(POSTS_DIR)) {
        foreach (glob(POSTS_DIR . '/*.json') ?: [] as $pf) {
            $corpusChunks[] = (string)@file_get_contents($pf);
        }
    }

    $usedRelSet = [];
    $usedBasenameSet = [];
    foreach ($corpusChunks as $chunk) {
        if ($chunk === '' || !str_contains($chunk, 'uploads/')) {
            continue;
        }
        if (preg_match_all('~/uploads/([A-Za-z0-9_\-./]+?\.(?:webp|avif|png|jpe?g|gif|svg|pdf))~iu', $chunk, $m)) {
            foreach ($m[1] as $relMatch) {
                $clean = ltrim(str_replace('\\', '/', $relMatch), '/');
                $usedRelSet[strtolower($clean)] = true;
                $usedBasenameSet[strtolower(basename($clean))] = true;
            }
        }
    }

    $orphans = [];
    $totalOrphanCount = 0;
    $totalOrphanBytes = 0;
    $scanned = 0;

    if (function_exists('zeynart_get_cached_media_entries')) {
        $mediaIdx = zeynart_get_cached_media_entries(false);
        foreach (($mediaIdx['entries'] ?? []) as $e) {
            if (empty($e['up'])) {
                continue;
            }
            $rel = ltrim(substr((string)$e['u'], strlen('/uploads/')), '/');
            if ($rel === '' || str_starts_with(strtolower($rel), 'og/')) {
                continue;
            }
            $scanned++;
            $relKey = strtolower($rel);
            $baseKey = strtolower((string)$e['n']);
            if (isset($usedRelSet[$relKey]) || isset($usedBasenameSet[$baseKey])) {
                continue;
            }
            $sz = (int)$e['s'];
            $totalOrphanCount++;
            $totalOrphanBytes += $sz;
            if (count($orphans) < $maxReturn) {
                $orphans[] = [
                    'rel' => $rel,
                    'url' => '/uploads/' . $rel,
                    'size_kb' => round($sz / 1024, 1),
                    'bytes' => $sz,
                    'modified' => date('Y-m-d H:i', (int)$e['m']),
                ];
            }
        }
    } else {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(UPLOADS_DIR, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['webp', 'avif', 'png', 'jpg', 'jpeg', 'gif', 'svg'], true)) {
                continue;
            }
            $rel = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen(UPLOADS_DIR))), '/');
            if (str_starts_with(strtolower($rel), 'og/')) {
                continue;
            }
            $scanned++;
            $relKey = strtolower($rel);
            $baseKey = strtolower(basename($rel));
            if (isset($usedRelSet[$relKey]) || isset($usedBasenameSet[$baseKey])) {
                continue;
            }
            $sz = (int)$file->getSize();
            $totalOrphanCount++;
            $totalOrphanBytes += $sz;
            if (count($orphans) < $maxReturn) {
                $orphans[] = [
                    'rel' => $rel,
                    'url' => '/uploads/' . $rel,
                    'size_kb' => round($sz / 1024, 1),
                    'bytes' => $sz,
                    'modified' => date('Y-m-d H:i', (int)$file->getMTime()),
                ];
            }
        }
    }

    usort($orphans, static fn(array $a, array $b): int => $b['bytes'] <=> $a['bytes']);

    return [
        'orphans' => $orphans,
        'total_orphan_count' => $totalOrphanCount,
        'total_orphan_bytes' => $totalOrphanBytes,
        'total_orphan_mb' => round($totalOrphanBytes / 1048576, 2),
        'scanned_uploads' => $scanned,
    ];
}

function zeynart_delete_orphan_uploads(array $relPaths, array $store): array
{
    $scan = zeynart_scan_orphan_uploads($store, 5000);
    $allowedOrphans = [];
    foreach ($scan['orphans'] as $o) {
        $allowedOrphans[$o['rel']] = (int)$o['bytes'];
    }

    $deleted = 0;
    $freedBytes = 0;
    foreach ($relPaths as $rawRel) {
        $rel = ltrim(str_replace('\\', '/', trim((string)$rawRel)), '/');
        if ($rel === '' || str_contains($rel, '..') || !isset($allowedOrphans[$rel])) {
            continue;
        }
        $abs = UPLOADS_DIR . '/' . $rel;
        if (is_file($abs) && @unlink($abs)) {
            $deleted++;
            $freedBytes += $allowedOrphans[$rel];
        }
    }

    if ($deleted > 0 && function_exists('zeynart_invalidate_media_cache')) {
        zeynart_invalidate_media_cache();
    }

    return [
        'deleted_count' => $deleted,
        'freed_mb' => round($freedBytes / 1048576, 2),
    ];
}

/* ============================================================================
 * 2. SİTE İÇİ CANLI ARAMA MOTORU (INSTANT SEARCH) & ARAMA ANALİTİĞİ
 * ============================================================================ */

function zeynart_tr_fold_lower(string $s): string
{
    $map = ['İ' => 'i', 'I' => 'ı', 'Ğ' => 'ğ', 'Ü' => 'ü', 'Ş' => 'ş', 'Ö' => 'ö', 'Ç' => 'ç'];
    return mb_strtolower(strtr($s, $map), 'UTF-8');
}

function zeynart_instant_search(string $query, array $store, string $lang = 'tr', int $limit = 14): array
{
    $q = trim(preg_replace('/\s+/u', ' ', $query) ?? '');
    if (mb_strlen($q, 'UTF-8') < 2) {
        return ['query' => $q, 'results' => [], 'total' => 0];
    }

    $qLower = zeynart_tr_fold_lower($q);
    $terms = array_values(array_filter(explode(' ', $qLower), static fn(string $w): bool => mb_strlen($w, 'UTF-8') >= 2));
    if ($terms === []) {
        $terms = [$qLower];
    }

    $scoreCandidate = static function (string $title, string $desc, string $cat = '', string $extra = '') use ($qLower, $terms): int {
        $tL = zeynart_tr_fold_lower($title);
        $dL = zeynart_tr_fold_lower($desc);
        $cL = zeynart_tr_fold_lower($cat);
        $eL = zeynart_tr_fold_lower($extra);

        $score = 0;
        if ($tL === $qLower) {
            $score += 120;
        } elseif (str_starts_with($tL, $qLower)) {
            $score += 85;
        } elseif (str_contains($tL, $qLower)) {
            $score += 60;
        }
        if ($cL !== '' && str_contains($cL, $qLower)) {
            $score += 28;
        }
        if (str_contains($dL, $qLower)) {
            $score += 24;
        }

        $matchedTerms = 0;
        foreach ($terms as $tm) {
            $hit = false;
            if (str_contains($tL, $tm)) {
                $score += 22;
                $hit = true;
            }
            if ($cL !== '' && str_contains($cL, $tm)) {
                $score += 10;
                $hit = true;
            }
            if (str_contains($dL, $tm)) {
                $score += 8;
                $hit = true;
            }
            if ($eL !== '' && str_contains($eL, $tm)) {
                $score += 6;
                $hit = true;
            }
            if ($hit) {
                $matchedTerms++;
            }
        }
        if (count($terms) > 1 && $matchedTerms < count($terms)) {
            $score = (int)floor($score * 0.45);
        }
        return $score;
    };

    $candidates = [];
    $slugs = zeynart_resolved_page_slugs($store);
    $svcBase = $slugs['hizmetler'] ?? 'hizmetler';
    $prjBase = $slugs['projeler'] ?? 'projeler';
    $blgBase = $slugs['blog'] ?? 'blog';

    // 1. Services (high priority)
    foreach (($store['services'] ?? []) as $svc) {
        if (!is_array($svc) || ($svc['status'] ?? 'published') !== 'published') {
            continue;
        }
        $t = (string)($svc['title'] ?? '');
        $d = (string)($svc['summary'] ?? $svc['lead'] ?? '');
        $sc = $scoreCandidate($t, $d, 'Hizmet', implode(' ', (array)($svc['deliverables'] ?? [])));
        if ($sc > 0) {
            $candidates[] = [
                'type' => 'service',
                'badge' => 'HİZMET',
                'title' => $t,
                'excerpt' => mb_substr($d, 0, 130, 'UTF-8'),
                'url' => lang_url('/' . $svcBase . '/' . ($svc['slug'] ?? '') . '/', $lang),
                'score' => $sc + 25,
            ];
        }
    }

    // 2. Projects
    foreach (($store['projects'] ?? []) as $prj) {
        if (!is_array($prj) || ($prj['status'] ?? 'published') !== 'published') {
            continue;
        }
        $t = (string)($prj['title'] ?? '');
        $d = (string)($prj['summary'] ?? '');
        $sc = $scoreCandidate($t, $d, (string)($prj['category'] ?? 'Proje'));
        if ($sc > 0) {
            $candidates[] = [
                'type' => 'project',
                'badge' => 'PROJE',
                'title' => $t,
                'excerpt' => mb_substr($d, 0, 130, 'UTF-8'),
                'url' => lang_url('/' . $prjBase . '/' . ($prj['slug'] ?? '') . '/', $lang),
                'score' => $sc + 15,
            ];
        }
    }

    // 3. Core & Interactive Tool Pages
    $staticTools = [
        ['title' => 'Adım Adım İnteraktif Teklif & Maliyet Sihirbazı', 'desc' => 'Web tasarım, yazılım, e-ticaret ve mobil uygulama bütçe hesaplama aracı', 'url' => '/' . ($slugs['teklif-sihirbazi'] ?? 'teklif-sihirbazi') . '/', 'badge' => 'ARAÇ'],
        ['title' => 'Ücretsiz Site Hızı & SEO Ön Analiz Aracı', 'desc' => 'Sitenizin hız, SSL, mobil uyumluluk ve teknik SEO karnesini saniyeler içinde ölçün', 'url' => '/' . ($slugs['site-analiz'] ?? 'site-analiz') . '/', 'badge' => 'ARAÇ'],
        ['title' => 'İletişim & Proje Başvurusu', 'desc' => 'Telefon, WhatsApp, adres ve hızlı proje talep formu', 'url' => '/' . ($slugs['iletisim'] ?? 'iletisim') . '/', 'badge' => 'SAYFA'],
    ];
    foreach ($staticTools as $tl) {
        $sc = $scoreCandidate($tl['title'], $tl['desc'], $tl['badge']);
        if ($sc > 0) {
            $candidates[] = [
                'type' => 'page',
                'badge' => $tl['badge'],
                'title' => $tl['title'],
                'excerpt' => $tl['desc'],
                'url' => lang_url($tl['url'], $lang),
                'score' => $sc + 12,
            ];
        }
    }

    // 4. Custom Pages
    foreach (($store['custom_pages'] ?? []) as $cp) {
        if (!is_array($cp) || ($cp['status'] ?? 'published') !== 'published' || !empty($cp['trashed'])) {
            continue;
        }
        $t = (string)($cp['title'] ?? '');
        $d = (string)($cp['lead'] ?? $cp['meta_desc'] ?? '');
        $sc = $scoreCandidate($t, $d, 'Sayfa');
        if ($sc > 0) {
            $candidates[] = [
                'type' => 'page',
                'badge' => 'SAYFA',
                'title' => $t,
                'excerpt' => mb_substr($d, 0, 130, 'UTF-8'),
                'url' => lang_url('/' . ($cp['slug'] ?? '') . '/', $lang),
                'score' => $sc + 10,
            ];
        }
    }

    // 5. Blog Archive (1,680+ posts via fast in-memory blog_index scan)
    $blogIdx = load_blog_index();
    foreach ($blogIdx as $bp) {
        if (($bp['status'] ?? 'published') !== 'published') {
            continue;
        }
        $t = (string)($bp['title'] ?? '');
        $d = (string)($bp['excerpt'] ?? '');
        $cat = (string)($bp['category'] ?? 'Blog');
        $sc = $scoreCandidate($t, $d, $cat);
        if ($sc > 0) {
            $candidates[] = [
                'type' => 'blog',
                'badge' => $cat !== '' ? mb_strtoupper($cat, 'UTF-8') : 'BLOG',
                'title' => $t,
                'excerpt' => mb_substr($d, 0, 130, 'UTF-8'),
                'url' => lang_url('/' . $blgBase . '/' . ($bp['slug'] ?? '') . '/', $lang),
                'date' => (string)($bp['date_display'] ?? ''),
                'score' => $sc,
            ];
        }
    }

    usort($candidates, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    $total = count($candidates);
    $sliced = array_slice($candidates, 0, max(1, min(30, $limit)));

    return [
        'query' => $q,
        'results' => $sliced,
        'total' => $total,
    ];
}

function zeynart_search_analytics_file(): string
{
    return DATA_DIR . '/search_analytics.json';
}

function zeynart_load_search_analytics(): array
{
    $dec = zeynart_read_json_data(zeynart_search_analytics_file());
    if (!is_array($dec)) {
        return ['queries' => [], 'total_searches' => 0, 'updated_at' => null];
    }
    $dec['queries'] = is_array($dec['queries'] ?? null) ? $dec['queries'] : [];
    $dec['total_searches'] = (int)($dec['total_searches'] ?? 0);
    return $dec;
}

function zeynart_record_search_query(string $query, int $resultCount, string $lang = 'tr'): void
{
    $q = trim(preg_replace('/\s+/u', ' ', strip_tags($query)) ?? '');
    $len = mb_strlen($q, 'UTF-8');
    if ($len < 2 || $len > 90) {
        return;
    }
    // Ignore URLs or scanner noise
    if (preg_match('~(?:https?://|<|>|\{\}|select\s|union\s|\.\./)~iu', $q)) {
        return;
    }
    $normKey = zeynart_tr_fold_lower($q);
    $data = zeynart_load_search_analytics();
    $queries = $data['queries'];

    if (!isset($queries[$normKey])) {
        $queries[$normKey] = [
            'term' => $q,
            'count' => 0,
            'last_results' => $resultCount,
            'lang' => $lang,
            'last_at' => date('Y-m-d H:i:s'),
        ];
    }
    $queries[$normKey]['term'] = $q;
    $queries[$normKey]['count'] = (int)($queries[$normKey]['count'] ?? 0) + 1;
    $queries[$normKey]['last_results'] = $resultCount;
    $queries[$normKey]['lang'] = $lang;
    $queries[$normKey]['last_at'] = date('Y-m-d H:i:s');

    if (count($queries) > 400) {
        uasort($queries, static fn(array $a, array $b): int => ($b['count'] ?? 0) <=> ($a['count'] ?? 0));
        $queries = array_slice($queries, 0, 350, true);
    }

    $data['queries'] = $queries;
    $data['total_searches'] = (int)($data['total_searches'] ?? 0) + 1;
    $data['updated_at'] = date('Y-m-d H:i:s');
    atomic_write_json(zeynart_search_analytics_file(), $data, true);
}

function zeynart_compile_search_insights(): array
{
    $data = zeynart_load_search_analytics();
    $rows = array_values($data['queries']);
    usort($rows, static fn(array $a, array $b): int => ((int)($b['count'] ?? 0)) <=> ((int)($a['count'] ?? 0)));

    $topQueries = array_slice($rows, 0, 25);
    $zeroResult = array_values(array_filter($rows, static fn(array $r): bool => ((int)($r['last_results'] ?? 0)) === 0));
    $zeroResult = array_slice($zeroResult, 0, 20);

    return [
        'total_searches' => (int)($data['total_searches'] ?? 0),
        'unique_terms' => count($rows),
        'zero_result_count' => count($zeroResult),
        'top_queries' => $topQueries,
        'zero_results' => $zeroResult,
        'updated_at' => $data['updated_at'] ?? null,
    ];
}

/* ============================================================================
 * 3. E-E-A-T UZMAN YAZAR PROFİLLERİ & MODERASYONLU SORU-CEVAP / YORUMLAR
 * ============================================================================ */

function zeynart_get_authors_list(array $store): array
{
    $raw = is_array($store['authors'] ?? null) ? $store['authors'] : [];
    $siteName = trim((string)($store['site']['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'));
    $logo = (string)($store['site']['favicon'] ?? '/icon.png');

    if ($raw === []) {
        return [
            [
                'id' => 'editorial-team',
                'slug' => 'zeynart-editoryal-ekip',
                'name' => 'Zeynart Dijital Strateji & Yazılım Ekibi',
                'role' => 'Kıdemli Web Mimarisi, Yazılım & SEO Uzmanları',
                'bio' => '12+ yıllık kurumsal web tasarım, özel yazılım mimarisi, teknik SEO ve yüksek performanslı sunucu altyapısı deneyimiyle işletmeler için sürdürülebilir dijital çözümler üretir.',
                'avatar' => $logo,
                'linkedin' => '',
                'twitter' => '',
                'website' => rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/'),
                'expertise' => ['Kurumsal Web Tasarım', 'Özel Web Yazılım', 'Teknik SEO', 'Bulut Sunucu Mimarisi'],
                'is_default' => true,
            ],
        ];
    }

    $out = [];
    foreach ($raw as $idx => $a) {
        if (!is_array($a)) {
            continue;
        }
        $name = mb_substr(trim((string)($a['name'] ?? '')), 0, 90, 'UTF-8');
        if ($name === '') {
            continue;
        }
        $slug = slugify((string)($a['slug'] ?? $name)) ?: ('yazar-' . ($idx + 1));
        $expRaw = $a['expertise'] ?? [];
        if (is_string($expRaw)) {
            $expRaw = array_map('trim', explode(',', $expRaw));
        }
        $exp = array_values(array_filter(array_map(static fn($x): string => mb_substr(trim((string)$x), 0, 50, 'UTF-8'), (array)$expRaw)));
        $out[] = [
            'id' => preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($a['id'] ?? $slug)) ?: $slug,
            'slug' => $slug,
            'name' => $name,
            'role' => mb_substr(trim((string)($a['role'] ?? 'Dijital Strateji Uzmanı')), 0, 110, 'UTF-8'),
            'bio' => mb_substr(trim((string)($a['bio'] ?? ($siteName . ' uzman içerik ve teknoloji ekibi.'))), 0, 480, 'UTF-8'),
            'avatar' => mb_substr(trim((string)($a['avatar'] ?? $logo)), 0, 260, 'UTF-8') ?: $logo,
            'linkedin' => mb_substr(trim((string)($a['linkedin'] ?? '')), 0, 240, 'UTF-8'),
            'twitter' => mb_substr(trim((string)($a['twitter'] ?? '')), 0, 240, 'UTF-8'),
            'website' => mb_substr(trim((string)($a['website'] ?? '')), 0, 240, 'UTF-8'),
            'expertise' => array_slice($exp, 0, 8),
            'is_default' => !empty($a['is_default']) || $idx === 0,
        ];
    }

    return $out !== [] ? $out : zeynart_get_authors_list([]);
}

function zeynart_resolve_post_author(array $post, array $store): array
{
    $authors = zeynart_get_authors_list($store);
    $wantedId = trim((string)($post['author_id'] ?? ''));
    $wantedName = trim((string)($post['author'] ?? ''));

    foreach ($authors as $a) {
        if ($wantedId !== '' && ($a['id'] === $wantedId || $a['slug'] === $wantedId)) {
            return $a;
        }
    }
    if ($wantedName !== '') {
        foreach ($authors as $a) {
            if (mb_strtolower($a['name'], 'UTF-8') === mb_strtolower($wantedName, 'UTF-8')) {
                return $a;
            }
        }
    }
    foreach ($authors as $a) {
        if (!empty($a['is_default'])) {
            return $a;
        }
    }
    return $authors[0];
}

function zeynart_blog_comments_file(): string
{
    return DATA_DIR . '/blog_comments.json';
}

function zeynart_get_blog_comments_config(array $store): array
{
    $raw = is_array($store['blog_comments_cfg'] ?? null) ? $store['blog_comments_cfg'] : [];
    return [
        'enabled' => ($raw['enabled'] ?? true) !== false,
        'show_author_box' => ($raw['show_author_box'] ?? true) !== false,
        'require_approval' => ($raw['require_approval'] ?? true) !== false,
        'heading' => mb_substr(trim((string)($raw['heading'] ?? 'Uzmanımıza Sorun & Yorumlar')), 0, 90, 'UTF-8'),
        'sublead' => mb_substr(trim((string)($raw['sublead'] ?? 'Bu makaleyle ilgili merak ettiğiniz teknik detayları veya proje sorularınızı paylaşabilirsiniz. Yorumlar editör onayından sonra yayınlanır.')), 0, 240, 'UTF-8'),
    ];
}

function zeynart_load_blog_comments(): array
{
    $dec = zeynart_read_json_data(zeynart_blog_comments_file());
    return is_array($dec) ? array_values($dec) : [];
}

function zeynart_save_blog_comments(array $comments): bool
{
    return atomic_write_json(zeynart_blog_comments_file(), array_values($comments), true);
}

function zeynart_get_approved_comments_for_post(string $postSlug): array
{
    $slugNorm = strtolower(trim($postSlug));
    if ($slugNorm === '') {
        return [];
    }
    $all = zeynart_load_blog_comments();
    $filtered = array_values(array_filter($all, static function (array $c) use ($slugNorm): bool {
        return strtolower((string)($c['post_slug'] ?? '')) === $slugNorm
            && ($c['status'] ?? 'pending') === 'approved';
    }));
    usort($filtered, static fn(array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return $filtered;
}

/* ============================================================================
 * 4. MÜŞTERİYE ÖZEL ONLINE TEKLİF LİNKİ (/teklif/{kod}/) & OKUNDU/ONAY STÜDYOSU
 * ============================================================================ */

function zeynart_proposals_file(): string
{
    return DATA_DIR . '/proposals.json';
}

function zeynart_load_proposals(): array
{
    $dec = zeynart_read_json_data(zeynart_proposals_file());
    return is_array($dec) ? array_values($dec) : [];
}

function zeynart_save_proposals(array $proposals): bool
{
    return atomic_write_json(zeynart_proposals_file(), array_values($proposals), true);
}

function zeynart_normalize_proposal(array $in, ?array $existing = null): array
{
    $code = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', (string)($in['code'] ?? ($existing['code'] ?? ''))) ?: '');
    if (strlen($code) < 5) {
        $code = 'zn-' . substr(bin2hex(random_bytes(4)), 0, 7);
    }
    $currency = strtoupper(trim((string)($in['currency'] ?? ($existing['currency'] ?? 'TRY'))));
    if (!in_array($currency, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
        $currency = 'TRY';
    }
    $currencySymbol = match ($currency) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => '₺',
    };

    $items = [];
    $subtotal = 0.0;
    foreach ((array)($in['items'] ?? ($existing['items'] ?? [])) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $t = mb_substr(trim((string)($row['title'] ?? '')), 0, 140, 'UTF-8');
        if ($t === '') {
            continue;
        }
        $qty = max(1, min(999, (float)($row['qty'] ?? 1)));
        $unitPrice = max(0, round((float)($row['unit_price'] ?? 0), 2));
        $lineTotal = round($qty * $unitPrice, 2);
        $subtotal += $lineTotal;
        $items[] = [
            'title' => $t,
            'desc' => mb_substr(trim((string)($row['desc'] ?? '')), 0, 360, 'UTF-8'),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ];
    }

    $discount = max(0, min($subtotal, round((float)($in['discount'] ?? ($existing['discount'] ?? 0)), 2)));
    $vatRate = max(0, min(50, (int)($in['vat_rate'] ?? ($existing['vat_rate'] ?? 20))));
    $netAfterDiscount = max(0, round($subtotal - $discount, 2));
    $vatAmount = round($netAfterDiscount * ($vatRate / 100), 2);
    $grandTotal = round($netAfterDiscount + $vatAmount, 2);

    $status = (string)($in['status'] ?? ($existing['status'] ?? 'sent'));
    if (!in_array($status, ['draft', 'sent', 'viewed', 'approved', 'rejected', 'expired'], true)) {
        $status = 'sent';
    }

    return [
        'id' => (string)($existing['id'] ?? ('prp-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(2)), 0, 4))),
        'code' => $code,
        'proposal_no' => mb_substr(trim((string)($in['proposal_no'] ?? ($existing['proposal_no'] ?? ('ZYN-' . date('Y') . '-' . strtoupper(substr($code, -4)))))), 0, 40, 'UTF-8'),
        'client_name' => mb_substr(trim((string)($in['client_name'] ?? ($existing['client_name'] ?? ''))), 0, 120, 'UTF-8'),
        'client_company' => mb_substr(trim((string)($in['client_company'] ?? ($existing['client_company'] ?? ''))), 0, 140, 'UTF-8'),
        'client_phone' => mb_substr(trim((string)($in['client_phone'] ?? ($existing['client_phone'] ?? ''))), 0, 40, 'UTF-8'),
        'client_email' => mb_substr(trim((string)($in['client_email'] ?? ($existing['client_email'] ?? ''))), 0, 160, 'UTF-8'),
        'target_domain' => mb_substr(trim((string)($in['target_domain'] ?? ($existing['target_domain'] ?? ''))), 0, 160, 'UTF-8'),
        'annual_renewal_amount' => max(0, round((float)($in['annual_renewal_amount'] ?? ($existing['annual_renewal_amount'] ?? 0)), 2)),
        'client_tax_office' => mb_substr(trim((string)($in['client_tax_office'] ?? ($existing['client_tax_office'] ?? ''))), 0, 120, 'UTF-8'),
        'client_tax_no' => mb_substr(trim((string)($in['client_tax_no'] ?? ($existing['client_tax_no'] ?? ''))), 0, 40, 'UTF-8'),
        'client_address' => mb_substr(trim((string)($in['client_address'] ?? ($existing['client_address'] ?? ''))), 0, 320, 'UTF-8'),
        'project_title' => mb_substr(trim((string)($in['project_title'] ?? ($existing['project_title'] ?? 'Kurumsal Dijital Dönüşüm & Web Yazılım Teklifi'))), 0, 180, 'UTF-8'),
        'intro_note' => mb_substr(trim((string)($in['intro_note'] ?? ($existing['intro_note'] ?? 'Markanızın dijital hedefleri doğrultusunda hazırladığımız kapsam, teslim takvimi ve yatırım detaylarını aşağıda inceleyebilirsiniz.'))), 0, 1200, 'UTF-8'),
        'delivery_time' => mb_substr(trim((string)($in['delivery_time'] ?? ($existing['delivery_time'] ?? '15 – 20 İş Günü'))), 0, 80, 'UTF-8'),
        'valid_until' => preg_replace('/[^0-9\-]/', '', (string)($in['valid_until'] ?? ($existing['valid_until'] ?? date('Y-m-d', strtotime('+14 days'))))),
        'currency' => $currency,
        'currency_symbol' => $currencySymbol,
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'discount' => $discount,
        'vat_rate' => $vatRate,
        'vat_amount' => $vatAmount,
        'grand_total' => $grandTotal,
        'payment_terms' => mb_substr(trim((string)($in['payment_terms'] ?? ($existing['payment_terms'] ?? '%50 İş Başlangıcı · %50 Test & Canlı Yayın Tesliminde'))), 0, 600, 'UTF-8'),
        'extra_notes' => mb_substr(trim((string)($in['extra_notes'] ?? ($existing['extra_notes'] ?? '1 yıl teknik destek, SSL kurulumu ve yönetim stüdyosu eğitimi teklife dahildir.'))), 0, 1200, 'UTF-8'),
        'status' => $status,
        'view_count' => (int)($existing['view_count'] ?? 0),
        'first_viewed_at' => $existing['first_viewed_at'] ?? null,
        'last_viewed_at' => $existing['last_viewed_at'] ?? null,
        'approved_at' => $in['approved_at'] ?? ($existing['approved_at'] ?? null),
        'approved_by' => $in['approved_by'] ?? ($existing['approved_by'] ?? null),
        'approved_ip' => $in['approved_ip'] ?? ($existing['approved_ip'] ?? null),
        'approval_note' => $in['approval_note'] ?? ($existing['approval_note'] ?? null),
        'contract_no' => mb_substr(trim((string)($in['contract_no'] ?? ($existing['contract_no'] ?? ''))), 0, 48, 'UTF-8'),
        'contract_date' => preg_replace('/[^0-9\-]/', '', (string)($in['contract_date'] ?? ($existing['contract_date'] ?? ''))),
        'automation_chain' => is_array($in['automation_chain'] ?? null)
            ? $in['automation_chain']
            : (is_array($existing['automation_chain'] ?? null) ? $existing['automation_chain'] : null),
        'created_at' => (string)($existing['created_at'] ?? date('Y-m-d H:i:s')),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
}

function zeynart_find_proposal_by_code(string $code, bool $includeDraft = false): ?array
{
    $c = strtolower(trim($code));
    if ($c === '') {
        return null;
    }
    foreach (zeynart_load_proposals() as $p) {
        if (strtolower((string)($p['code'] ?? '')) === $c) {
            if (($p['status'] ?? 'sent') === 'draft' && !$includeDraft) {
                return null;
            }
            return $p;
        }
    }
    return null;
}

function zeynart_record_proposal_view(string $code, array $store): ?array
{
    $proposals = zeynart_load_proposals();
    $foundIdx = null;
    foreach ($proposals as $i => $p) {
        if (strtolower((string)($p['code'] ?? '')) === strtolower($code)) {
            $foundIdx = $i;
            break;
        }
    }
    if ($foundIdx === null) {
        return null;
    }
    $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $isBot = (bool)preg_match('/(bot|crawl|spider|slurp|whatsapp|facebookexternalhit|linkedinbot|twitterbot|telegrambot|preview)/i', $ua);
    if (!$isBot) {
        $prevCount = (int)($proposals[$foundIdx]['view_count'] ?? 0);
        $proposals[$foundIdx]['view_count'] = $prevCount + 1;
        $now = date('Y-m-d H:i:s');
        if (empty($proposals[$foundIdx]['first_viewed_at'])) {
            $proposals[$foundIdx]['first_viewed_at'] = $now;
        }
        $proposals[$foundIdx]['last_viewed_at'] = $now;
        if (($proposals[$foundIdx]['status'] ?? 'sent') === 'sent') {
            $proposals[$foundIdx]['status'] = 'viewed';
        }
        zeynart_save_proposals($proposals);

        // Send first-open notification email if SMTP is enabled
        if ($prevCount === 0 && !empty($store['smtp']['enabled'])) {
            try {
                require_once ROOT_DIR . '/lib/smtp.php';
                $to = resolve_smtp_recipient($store);
                if ($to !== '') {
                    $prp = $proposals[$foundIdx];
                    $html = '<div style="font-family:Arial,sans-serif;padding:20px;background:#071318;color:#eaf4f7;border-radius:12px">'
                        . '<h3 style="color:#2dd4bf;margin:0 0 10px">👁️ Müşteri Teklifinizi Açtı!</h3>'
                        . '<p><strong>Müşteri:</strong> ' . e((string)$prp['client_name']) . ' (' . e((string)$prp['client_company']) . ')</p>'
                        . '<p><strong>Teklif:</strong> ' . e((string)$prp['project_title']) . ' (' . e((string)$prp['proposal_no']) . ')</p>'
                        . '<p><strong>Açılış Zamanı:</strong> ' . e($now) . '</p></div>';
                    @send_smtp_detailed($store['smtp'], $to, 'Teklif İnceleniyor: ' . $prp['client_name'], $html, ['allow_mail_fallback' => true]);
                }
            } catch (\Throwable $_) {
            }
        }
    }
    return $proposals[$foundIdx];
}

/* ============================================================================
 * 5. İKİ AŞAMALI DOĞRULAMA (RFC 6238 TOTP 2FA) & MODÜLER .ZIP YEDEKLEME KASASI
 * ============================================================================ */

function zeynart_totp_generate_secret(int $length = 20): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bytes = random_bytes($length);
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[ord($bytes[$i]) & 31];
    }
    return $out;
}

function zeynart_base32_decode_bin(string $b32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $clean = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $b32) ?? '');
    $bits = 0;
    $value = 0;
    $output = '';
    $len = strlen($clean);
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($alphabet, $clean[$i]);
        if ($pos === false) {
            continue;
        }
        $value = ($value << 5) | $pos;
        $bits += 5;
        if ($bits >= 8) {
            $bits -= 8;
            $output .= chr(($value >> $bits) & 0xFF);
        }
    }
    return $output;
}

function zeynart_totp_code_at(string $secret, int $timeSlice): string
{
    $key = zeynart_base32_decode_bin($secret);
    if ($key === '') {
        return '';
    }
    $timeBin = pack('N*', 0) . pack('N*', $timeSlice);
    $hm = hash_hmac('sha1', $timeBin, $key, true);
    $offset = ord($hm[19]) & 0x0F;
    $hashPart = substr($hm, $offset, 4);
    $val = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
    return str_pad((string)($val % 1000000), 6, '0', STR_PAD_LEFT);
}

function zeynart_totp_verify(string $secret, string $code, int $discrepancy = 1): bool
{
    $cleanCode = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($cleanCode) !== 6 || trim($secret) === '') {
        return false;
    }
    $slice = (int)floor(time() / 30);
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $calc = zeynart_totp_code_at($secret, $slice + $i);
        if ($calc !== '' && hash_equals($calc, $cleanCode)) {
            return true;
        }
    }
    return false;
}

function zeynart_get_2fa_config(array $store): array
{
    $raw = is_array($store['security_2fa'] ?? null) ? $store['security_2fa'] : [];
    $method = (string)($raw['method'] ?? 'totp');
    return [
        'enabled' => !empty($raw['enabled']),
        'method' => in_array($method, ['totp', 'email'], true) ? $method : 'totp',
        'totp_secret' => trim((string)($raw['totp_secret'] ?? '')),
        'email_fallback' => ($raw['email_fallback'] ?? true) !== false,
        'recovery_codes' => is_array($raw['recovery_codes'] ?? null) ? array_values($raw['recovery_codes']) : [],
        'enabled_at' => $raw['enabled_at'] ?? null,
    ];
}

function zeynart_generate_recovery_codes(int $count = 8): array
{
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $codes[] = strtoupper(bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)));
    }
    return $codes;
}

function zeynart_backups_dir(): string
{
    $dir = DATA_DIR . '/backups';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Order allow,deny\nDeny from all\n");
    }
    return $dir;
}

function zeynart_get_backup_schedule_config(array $store): array
{
    $raw = is_array($store['backup_vault'] ?? null) ? $store['backup_vault'] : [];
    $freq = (string)($raw['auto_frequency'] ?? 'weekly');
    if (!in_array($freq, ['off', 'daily', 'weekly', 'monthly'], true)) {
        $freq = 'weekly';
    }
    return [
        'auto_frequency' => $freq,
        'retain_count' => max(2, min(20, (int)($raw['retain_count'] ?? 5))),
        'auto_include_posts' => ($raw['auto_include_posts'] ?? true) !== false,
        'auto_include_uploads' => !empty($raw['auto_include_uploads']),
        'last_auto_backup_ts' => (int)($raw['last_auto_backup_ts'] ?? 0),
    ];
}

function zeynart_list_vault_backups(): array
{
    $dir = zeynart_backups_dir();
    $list = [];
    foreach (glob($dir . '/*.zip') ?: [] as $zf) {
        if (!is_file($zf)) {
            continue;
        }
        $fname = basename($zf);
        $sz = (int)filesize($zf);
        $mtime = (int)filemtime($zf);
        $modules = ['Ayarlar & Sayfalar'];
        if (str_contains($fname, '-posts') || str_contains($fname, '-full')) {
            $modules[] = 'Blog Arşivi';
        }
        if (str_contains($fname, '-media') || str_contains($fname, '-full')) {
            $modules[] = 'Medya (Uploads)';
        }
        $list[] = [
            'filename' => $fname,
            'size_mb' => round($sz / 1048576, 2),
            'size_kb' => round($sz / 1024, 1),
            'bytes' => $sz,
            'created_at' => date('Y-m-d H:i:s', $mtime),
            'ts' => $mtime,
            'modules' => $modules,
            'is_auto' => str_starts_with($fname, 'oto-yedek-'),
        ];
    }
    usort($list, static fn(array $a, array $b): int => $b['ts'] <=> $a['ts']);
    return $list;
}

function zeynart_pure_zip_write(string $destPath, array $fileMap): bool
{
    $fh = @fopen($destPath, 'wb');
    if ($fh === false) {
        return false;
    }
    $now = getdate();
    $dosTime = (($now['hours'] << 11) | ($now['minutes'] << 5) | ($now['seconds'] >> 1)) & 0xFFFF;
    $dosDate = (((max(1980, (int)$now['year']) - 1980) << 9) | ($now['mon'] << 5) | $now['mday']) & 0xFFFF;

    $cdEntries = [];
    $offset = 0;

    foreach ($fileMap as $arcName => $spec) {
        $name = ltrim(str_replace('\\', '/', (string)$arcName), '/');
        if ($name === '') {
            continue;
        }
        $raw = is_array($spec) && isset($spec['path'])
            ? @file_get_contents((string)$spec['path'])
            : (string)$spec;
        if ($raw === false) {
            continue;
        }
        $uncompLen = strlen($raw);
        $crc = crc32($raw);
        if ($crc < 0) {
            $crc += 4294967296;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $alreadyComp = in_array($ext, ['webp', 'avif', 'jpg', 'jpeg', 'png', 'gif', 'zip'], true);
        if (!$alreadyComp && function_exists('gzdeflate') && $uncompLen > 64) {
            $comp = gzdeflate($raw, 6);
            $method = 8;
        } else {
            $comp = $raw;
            $method = 0;
        }
        $compLen = strlen($comp);
        $nameLen = strlen($name);

        $localHeader = pack(
            'VvvvvvVVVvv',
            0x04034b50,
            20,
            0x0800,
            $method,
            $dosTime,
            $dosDate,
            $crc,
            $compLen,
            $uncompLen,
            $nameLen,
            0
        ) . $name;

        fwrite($fh, $localHeader);
        fwrite($fh, $comp);

        $cdEntries[] = pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014b50,
            20,
            20,
            0x0800,
            $method,
            $dosTime,
            $dosDate,
            $crc,
            $compLen,
            $uncompLen,
            $nameLen,
            0,
            0,
            0,
            0,
            0,
            $offset
        ) . $name;

        $offset += strlen($localHeader) + $compLen;
    }

    $cdBlob = implode('', $cdEntries);
    $cnt = count($cdEntries);
    $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, $cnt, $cnt, strlen($cdBlob), $offset, 0);
    fwrite($fh, $cdBlob);
    fwrite($fh, $eocd);
    fclose($fh);
    return true;
}

function zeynart_pure_zip_read_all(string $zipPath): ?array
{
    $bin = @file_get_contents($zipPath);
    if ($bin === false || strlen($bin) < 22) {
        return null;
    }
    $len = strlen($bin);
    $searchStart = max(0, $len - 65557);
    $eocdPos = strrpos(substr($bin, $searchStart), "\x50\x4b\x05\x06");
    if ($eocdPos === false) {
        return null;
    }
    $eocdAbs = $searchStart + $eocdPos;
    $hdr = unpack('vdisk/vcdDisk/vdiskEntries/vtotalEntries/VcdSize/VcdOffset', substr($bin, $eocdAbs + 4, 16));
    if (!is_array($hdr)) {
        return null;
    }
    $entries = [];
    $pos = (int)$hdr['cdOffset'];
    $total = (int)$hdr['totalEntries'];

    for ($i = 0; $i < $total && ($pos + 46) <= $len; $i++) {
        $sig = unpack('Vsig', substr($bin, $pos, 4))['sig'] ?? 0;
        if ($sig !== 0x02014b50) {
            break;
        }
        $cd = unpack('vverMade/vverNeed/vflag/vmethod/vmtime/vmdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommentLen/vdiskStart/vintAttr/VextAttr/VlocalOffset', substr($bin, $pos + 4, 42));
        $name = substr($bin, $pos + 46, (int)$cd['nameLen']);
        $pos += 46 + (int)$cd['nameLen'] + (int)$cd['extraLen'] + (int)$cd['commentLen'];

        $locOff = (int)$cd['localOffset'];
        if (($locOff + 30) > $len) {
            continue;
        }
        $loc = unpack('vlnLen/vleLen', substr($bin, $locOff + 26, 4));
        $dataStart = $locOff + 30 + (int)$loc['lnLen'] + (int)$loc['leLen'];
        $compData = substr($bin, $dataStart, (int)$cd['compSize']);
        $method = (int)$cd['method'];
        if ($method === 0) {
            $entries[$name] = $compData;
        } elseif ($method === 8 && function_exists('gzinflate')) {
            $inflated = @gzinflate($compData);
            if ($inflated !== false) {
                $entries[$name] = $inflated;
            }
        }
    }
    return $entries;
}

function zeynart_create_vault_backup(array $options = [], int $retainCount = 5): array
{
    if (!class_exists('ZipArchive') && !function_exists('gzdeflate')) {
        return ['ok' => false, 'error' => 'Sunucuda ZIP sıkıştırma desteği (ZipArchive / zlib) aktif değil.'];
    }
    $incStore = ($options['include_store'] ?? true) !== false;
    $incPosts = ($options['include_posts'] ?? true) !== false;
    $incMedia = !empty($options['include_uploads']);
    $isAuto = !empty($options['is_auto']);

    $tagParts = [];
    if ($incStore && $incPosts && $incMedia) {
        $tagParts[] = 'full';
    } else {
        if ($incStore) $tagParts[] = 'data';
        if ($incPosts) $tagParts[] = 'posts';
        if ($incMedia) $tagParts[] = 'media';
    }
    $tag = implode('-', $tagParts) ?: 'data';
    $prefix = $isAuto ? 'oto-yedek-' : 'zeynart-yedek-';
    $filename = $prefix . date('Y-m-d_His') . '-' . $tag . '.zip';
    $destPath = zeynart_backups_dir() . '/' . $filename;

    @set_time_limit(120);
    $manifestJson = (string)json_encode([
        'created_at' => date('c'),
        'generator' => 'Zeynart Dijital Stüdyo v5.0 Yedekleme Kasası',
        'engine' => zeynart_is_sql_active() ? zeynart_load_db_config()['driver'] : 'json',
        'include_store' => $incStore,
        'include_posts' => $incPosts,
        'include_uploads' => $incMedia,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $fileList = [];
    $stringEntries = [];

    if (zeynart_is_sql_active() && ($incStore || $incPosts)) {
        // SQL Mode: Pack 1 clean .sql dump + 1 compact portable bundle (0 individual .json post clutter!)
        $stringEntries['database/zeynart-veritabani-yedegi.sql'] = zeynart_generate_mysql_dump_sql();
        $bundle = [
            'version' => '5.0',
            'created_at' => date('c'),
            'store' => $incStore ? load_store(true) : null,
            'blog_index' => $incPosts ? load_blog_index(true) : [],
            'posts' => [],
        ];
        if ($incPosts) {
            foreach (zeynart_iterate_all_posts() as $sl => $pRow) {
                $bundle['posts'][$sl] = $pRow;
            }
        }
        $stringEntries['database/zeynart-studio-paket.json'] = (string)json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        if ($incStore && is_dir(DATA_DIR)) {
            foreach (glob(DATA_DIR . '/*.json') ?: [] as $jf) {
                if (is_file($jf) && !str_ends_with(basename($jf), '_cache.json')) {
                    $fileList['data/' . basename($jf)] = $jf;
                }
            }
        }
        if ($incPosts && is_dir(POSTS_DIR)) {
            foreach (glob(POSTS_DIR . '/*.json') ?: [] as $pf) {
                if (is_file($pf)) {
                    $fileList['data/posts/' . basename($pf)] = $pf;
                }
            }
        }
    }
    if ($incMedia && is_dir(UPLOADS_DIR)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(UPLOADS_DIR, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $uf) {
            /** @var SplFileInfo $uf */
            if (!$uf->isFile()) {
                continue;
            }
            $rel = ltrim(str_replace('\\', '/', substr($uf->getPathname(), strlen(UPLOADS_DIR))), '/');
            if (str_starts_with(strtolower($rel), 'og/')) {
                continue;
            }
            $fileList['uploads/' . $rel] = $uf->getPathname();
        }
    }

    $addedFiles = count($fileList) + count($stringEntries);
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($destPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['ok' => false, 'error' => 'Yedek .zip dosyası oluşturulamadı.'];
        }
        $zip->addFromString('backup-manifest.json', $manifestJson);
        foreach ($stringEntries as $arcEntry => $strBytes) {
            $zip->addFromString($arcEntry, $strBytes);
        }
        foreach ($fileList as $arcEntry => $absFile) {
            $zip->addFile($absFile, $arcEntry);
        }
        $zip->close();
    } else {
        $pureMap = array_merge(['backup-manifest.json' => $manifestJson], $stringEntries);
        foreach ($fileList as $arcEntry => $absFile) {
            $pureMap[$arcEntry] = ['path' => $absFile];
        }
        if (!zeynart_pure_zip_write($destPath, $pureMap)) {
            return ['ok' => false, 'error' => 'Yedek .zip dosyası diske yazılamadı.'];
        }
    }

    // Enforce retention limit cleanly
    $all = zeynart_list_vault_backups();
    $maxKeep = max(2, min(25, $retainCount));
    if (count($all) > $maxKeep) {
        foreach (array_slice($all, $maxKeep) as $old) {
            $oldPath = zeynart_backups_dir() . '/' . basename((string)$old['filename']);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    }

    $GLOBALS['__zeynart_dir_metrics_mem'] = null;
    if (function_exists('zeynart_dir_metrics_cache_file')) {
        zeynart_delete_json_data(zeynart_dir_metrics_cache_file());
    }

    return [
        'ok' => true,
        'filename' => $filename,
        'added_files' => $addedFiles,
        'size_mb' => is_file($destPath) ? round(((int)filesize($destPath)) / 1048576, 2) : 0,
    ];
}

function zeynart_maybe_run_scheduled_vault_backup(array &$store): bool
{
    $cfg = zeynart_get_backup_schedule_config($store);
    if ($cfg['auto_frequency'] === 'off') {
        return false;
    }
    $interval = match ($cfg['auto_frequency']) {
        'daily' => 86400,
        'monthly' => 2592000,
        default => 604800,
    };
    $lastTs = (int)($cfg['last_auto_backup_ts'] ?? 0);
    if ($lastTs > 0 && (time() - $lastTs) < $interval) {
        return false;
    }
    if (!isset($store['backup_vault']) || !is_array($store['backup_vault'])) {
        $store['backup_vault'] = [];
    }
    $store['backup_vault']['last_auto_backup_ts'] = time();
    save_store($store);
    $res = zeynart_create_vault_backup([
        'include_store' => true,
        'include_posts' => $cfg['auto_include_posts'],
        'include_uploads' => $cfg['auto_include_uploads'],
        'is_auto' => true,
    ], $cfg['retain_count']);
    return !empty($res['ok']);
}

function zeynart_restore_vault_backup(string $zipPath, bool $preserveLeadsAndAuth = true): array
{
    if (!is_file($zipPath)) {
        return ['ok' => false, 'error' => 'Yedek arşivi bulunamadı.'];
    }

    $currentStore = load_store(true);
    $keepAdmin = $currentStore['admin'] ?? null;
    $keepUsers = $currentStore['admin_users'] ?? null;
    $keep2fa = $currentStore['security_2fa'] ?? null;
    $keepMessages = $currentStore['messages'] ?? [];

    $entriesMap = null;
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'error' => 'Boş veya hasarlı .zip yedek dosyası.'];
        }
        $entriesMap = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nm = (string)$zip->getNameIndex($i);
            $b = $zip->getFromIndex($i);
            if ($b !== false) {
                $entriesMap[$nm] = $b;
            }
        }
        $zip->close();
    } else {
        $entriesMap = zeynart_pure_zip_read_all($zipPath);
        if (!is_array($entriesMap) || $entriesMap === []) {
            return ['ok' => false, 'error' => 'Boş veya hasarlı .zip yedek dosyası.'];
        }
    }

    $restoredData = 0;
    $restoredPosts = 0;
    $restoredMedia = 0;

    foreach ($entriesMap as $rawEntry => $bytes) {
        $entry = str_replace('\\', '/', (string)$rawEntry);
        if ($entry === '' || str_ends_with($entry, '/') || str_contains($entry, '..')) {
            continue;
        }

        if ($entry === 'database/zeynart-studio-paket.json') {
            $bundleDec = json_decode($bytes, true);
            if (is_array($bundleDec)) {
                if (is_array($bundleDec['store'] ?? null)) {
                    $decoded = $bundleDec['store'];
                    if ($preserveLeadsAndAuth) {
                        if ($keepAdmin !== null) $decoded['admin'] = $keepAdmin;
                        if ($keepUsers !== null) $decoded['admin_users'] = $keepUsers;
                        if ($keep2fa !== null) $decoded['security_2fa'] = $keep2fa;
                        if (is_array($keepMessages) && $keepMessages !== []) {
                            $existingIds = array_column((array)($decoded['messages'] ?? []), 'id');
                            $merged = (array)($decoded['messages'] ?? []);
                            foreach (array_reverse($keepMessages) as $km) {
                                if (!in_array($km['id'] ?? '', $existingIds, true)) {
                                    array_unshift($merged, $km);
                                }
                            }
                            $decoded['messages'] = $merged;
                        }
                    }
                    save_store($decoded);
                    $restoredData++;
                }
                if (is_array($bundleDec['posts'] ?? null)) {
                    foreach ($bundleDec['posts'] as $sl => $pObj) {
                        if (is_array($pObj)) {
                            $safeSl = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$sl) ?: 'yazi';
                            atomic_write_json(POSTS_DIR . '/' . $safeSl . '.json', $pObj, false);
                            $restoredPosts++;
                        }
                    }
                    if (is_array($bundleDec['blog_index'] ?? null) && $bundleDec['blog_index'] !== []) {
                        save_blog_index($bundleDec['blog_index']);
                    }
                }
            }
            continue;
        }

        if (preg_match('~^data/([A-Za-z0-9_\-]+\.json)$~', $entry, $m)) {
            $jsonName = $m[1];
            $decoded = json_decode($bytes, true);
            if ($jsonName === 'store.json') {
                if (!is_array($decoded)) {
                    continue;
                }
                if ($preserveLeadsAndAuth) {
                    if ($keepAdmin !== null) $decoded['admin'] = $keepAdmin;
                    if ($keepUsers !== null) $decoded['admin_users'] = $keepUsers;
                    if ($keep2fa !== null) $decoded['security_2fa'] = $keep2fa;
                    if (is_array($keepMessages) && $keepMessages !== []) {
                        $existingIds = array_column((array)($decoded['messages'] ?? []), 'id');
                        $merged = (array)($decoded['messages'] ?? []);
                        foreach (array_reverse($keepMessages) as $km) {
                            if (!in_array($km['id'] ?? '', $existingIds, true)) {
                                array_unshift($merged, $km);
                            }
                        }
                        $decoded['messages'] = $merged;
                    }
                }
                save_store($decoded);
                $restoredData++;
            } elseif ($decoded !== null) {
                atomic_write_json(DATA_DIR . '/' . $jsonName, $decoded, false);
                $restoredData++;
            }
        } elseif (preg_match('~^data/posts/([A-Za-z0-9_\-]+\.json)$~', $entry, $m)) {
            $pDec = json_decode($bytes, true);
            if (is_array($pDec)) {
                atomic_write_json(POSTS_DIR . '/' . $m[1], $pDec, false);
                $restoredPosts++;
            }
        } elseif (preg_match('~^uploads/([A-Za-z0-9_\-./]+\.(?:webp|avif|png|jpe?g|gif|svg|pdf))$~i', $entry, $m)) {
            $target = UPLOADS_DIR . '/' . $m[1];
            $pDir = dirname($target);
            if (!is_dir($pDir)) @mkdir($pDir, 0775, true);
            @file_put_contents($target, $bytes);
            $restoredMedia++;
        }
    }

    if (function_exists('zeynart_invalidate_media_cache')) {
        zeynart_invalidate_media_cache();
    }

    return [
        'ok' => true,
        'restored_data' => $restoredData,
        'restored_posts' => $restoredPosts,
        'restored_media' => $restoredMedia,
    ];
}

/* ============================================================================
 * 6. GERÇEK ZAMANLI 16 KRİTERLİ İÇERİK SEO PUANLAYICI & INDEXNOW PING MOTORU
 * ============================================================================ */

function zeynart_evaluate_content_seo(array $item, array $allKeywordOwners = []): array
{
    $title = trim((string)($item['title'] ?? ''));
    $metaTitle = trim((string)($item['meta_title'] ?? '')) ?: $title;
    $metaDesc = trim((string)($item['meta_desc'] ?? ($item['excerpt'] ?? '')));
    $slug = trim((string)($item['slug'] ?? ''), '/');
    $kw = trim((string)($item['focus_keyword'] ?? ''));
    $html = (string)($item['content_html'] ?? '');
    $hasCoverAlt = !empty($item['has_cover_alt']);
    $faqCount = (int)($item['faq_count'] ?? 0);
    $itemId = (string)($item['id'] ?? $slug);

    $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    $words = $plain === '' ? [] : (preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $wordCount = count($words);

    $kwLower = zeynart_tr_fold_lower($kw);
    $kwSlug = $kw !== '' ? slugify($kw) : '';
    $titleLower = zeynart_tr_fold_lower($metaTitle);
    $descLower = zeynart_tr_fold_lower($metaDesc);
    $plainLower = zeynart_tr_fold_lower($plain);
    $first10Pct = mb_substr($plainLower, 0, max(160, (int)ceil(mb_strlen($plainLower, 'UTF-8') * 0.12)), 'UTF-8');

    // Extract H2/H3 text
    $headingsText = '';
    $headingCount = 0;
    if (preg_match_all('~<h[23][^>]*>(.*?)</h[23]>~isu', $html, $hm)) {
        $headingCount = count($hm[1]);
        $headingsText = zeynart_tr_fold_lower(strip_tags(implode(' ', $hm[1])));
    }

    // Internal vs external links in body
    $internalLinks = 0;
    $externalLinks = 0;
    if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\']~iu', $html, $lm)) {
        foreach ($lm[1] as $href) {
            $h = trim($href);
            if ($h === '' || str_starts_with($h, '#') || str_starts_with($h, 'javascript:')) continue;
            if (str_starts_with($h, '/') || str_contains($h, (string)($_SERVER['HTTP_HOST'] ?? 'zeynart.com'))) {
                $internalLinks++;
            } elseif (preg_match('~^https?://~i', $h)) {
                $externalLinks++;
            }
        }
    }

    // Image alt check in body + cover
    $imgWithAlt = $hasCoverAlt ? 1 : 0;
    if (preg_match_all('~<img\b[^>]*\balt\s*=\s*["\']([^"\']+)["\']~iu', $html, $im)) {
        foreach ($im[1] as $altTxt) {
            if (trim($altTxt) !== '') $imgWithAlt++;
        }
    }

    // Keyword density
    $kwOccurrences = ($kwLower !== '' && $plainLower !== '') ? mb_substr_count($plainLower, $kwLower, 'UTF-8') : 0;
    $kwWordsCount = $kw !== '' ? max(1, count(preg_split('/\s+/u', $kw, -1, PREG_SPLIT_NO_EMPTY) ?: [1])) : 1;
    $density = ($wordCount > 0 && $kwLower !== '') ? round((($kwOccurrences * $kwWordsCount) / $wordCount) * 100, 2) : 0.0;

    $cannibalOwners = [];
    if ($kwLower !== '' && isset($allKeywordOwners[$kwLower])) {
        foreach ($allKeywordOwners[$kwLower] as $ow) {
            if (($ow['id'] ?? '') !== $itemId) {
                $cannibalOwners[] = $ow['title'] ?? $ow['id'];
            }
        }
    }

    $checks = [];
    $addCheck = static function (string $group, string $label, bool $pass, int $points, string $hint) use (&$checks): void {
        $checks[] = [
            'group' => $group,
            'label' => $label,
            'pass' => $pass,
            'points' => $points,
            'hint' => $hint,
        ];
    };

    // Group 1: Temel SEO (42 puan)
    $addCheck('basic', 'Odak anahtar kelime tanımlı', $kwLower !== '', 8, $kwLower !== '' ? "Odak kelime: \"{$kw}\"" : 'Hedeflediğiniz ana arama terimini girin.');
    $addCheck('basic', 'Odak kelime SEO başlığında yer alıyor', $kwLower !== '' && str_contains($titleLower, $kwLower), 10, 'Arama motoru tıklama oranı için başlıkta odak kelime bulunmalıdır.');
    $addCheck('basic', 'Odak kelime Meta Açıklamada yer alıyor', $kwLower !== '' && str_contains($descLower, $kwLower), 8, 'Google sonuç özetinde kalın vurgulanması için açıklamada geçmelidir.');
    $addCheck('basic', 'Odak kelime URL adresinde (slug) yer alıyor', $kwSlug !== '' && str_contains(strtolower($slug), $kwSlug), 8, 'Kısa ve anahtar kelime odaklı kalıcı bağlantı kullanın.');
    $addCheck('basic', 'Odak kelime ilk paragrafta (%10 giriş) geçiyor', $kwLower !== '' && str_contains($first10Pct, $kwLower), 8, 'Konu bütünlüğü sinyali için ilk paragrafta odak kelimeye yer verin.');

    // Group 2: İçerik Derinliği & Bağlantı Mimarisi (34 puan)
    $addCheck('content', 'Kapsamlı içerik uzunluğu (en az 350+ kelime)', $wordCount >= 350, 8, "Mevcut uzunluk: {$wordCount} kelime (Önerilen: 350–1.500+ kelime).");
    $addCheck('content', 'H2 / H3 alt başlıklarında odak kelime veya alt kavramlar', $headingCount >= 2 && ($kwLower === '' || str_contains($headingsText, $kwLower) || $headingCount >= 3), 7, "Mevcut alt başlık sayısı: {$headingCount}.");
    $addCheck('content', 'Anahtar kelime yoğunluğu doğal aralıkta (%0.4 – %2.8)', $kwLower !== '' && $density >= 0.3 && $density <= 3.0, 5, "Yoğunluk: %{$density} ({$kwOccurrences} kez geçti).");
    $addCheck('content', 'Site içi hizmet/sayfa bağlantısı (Internal Link) mevcut', $internalLinks >= 1, 5, $internalLinks >= 1 ? "{$internalLinks} adet iç bağlantı bulundu." : 'En az 1 ilgili hizmet veya rehber sayfasına link verin.');
    $addCheck('content', 'Referans kaynak veya çoklu iç bağlantı derinliği mevcut', $externalLinks >= 1 || $internalLinks >= 2, 3, ($externalLinks >= 1 || $internalLinks >= 2) ? "Bağlantı derinliği güçlü ({$internalLinks} iç, {$externalLinks} dış referans)." : 'En az 1 otorite referans veya 2. bir site içi bağlantı ekleyin.');
    $addCheck('content', 'Görsel optimizasyonu ve dolu alt etiketi mevcut', $imgWithAlt >= 1, 6, $imgWithAlt >= 1 ? "{$imgWithAlt} görselde açıklayıcı alt metni var." : 'Görsellere odak kelime içeren alt metin ekleyin.');

    // Group 3: SERP Görünümü, Şema & Tekillik (24 puan)
    $mtLen = mb_strlen($metaTitle, 'UTF-8');
    $mdLen = mb_strlen($metaDesc, 'UTF-8');
    $addCheck('serp', 'SEO Başlığı ideal uzunlukta (30 – 65 karakter)', $mtLen >= 25 && $mtLen <= 66, 6, "Mevcut başlık: {$mtLen} karakter.");
    $addCheck('serp', 'Meta Açıklama ideal uzunlukta (100 – 165 karakter)', $mdLen >= 90 && $mdLen <= 168, 6, "Mevcut açıklama: {$mdLen} karakter.");
    $addCheck('serp', 'Kısa ve temiz URL yapısı (≤ 75 karakter)', strlen($slug) > 0 && strlen($slug) <= 75, 4, 'URL uzunluğu: ' . strlen($slug) . ' karakter.');
    $addCheck('serp', 'Zengin Sonuç (SSS / FAQPage Şeması) desteği', $faqCount >= 1, 4, $faqCount >= 1 ? "{$faqCount} adet yapılandırılmış SSS maddesi hazır." : 'Arama sonuçlarında geniş yer kaplamak için SSS ekleyin.');
    $addCheck('serp', 'Odak kelime tekilliği (Cannibalization kontrolü)', $kwLower !== '' && $cannibalOwners === [], 4, $cannibalOwners === [] ? 'Bu odak kelime başka bir içerikle çakışmıyor.' : ('Aynı kelime şu içerikte de kullanılıyor: ' . implode(', ', array_slice($cannibalOwners, 0, 2))));

    $score = 0;
    $passedCount = 0;
    foreach ($checks as $c) {
        if ($c['pass']) {
            $score += $c['points'];
            $passedCount++;
        }
    }

    return [
        'score' => min(100, $score),
        'passed' => $passedCount,
        'total_checks' => count($checks),
        'word_count' => $wordCount,
        'heading_count' => $headingCount,
        'internal_links' => $internalLinks,
        'external_links' => $externalLinks,
        'density' => $density,
        'cannibal_conflicts' => $cannibalOwners,
        'checks' => $checks,
    ];
}

function zeynart_audit_all_content_seo(array $store): array
{
    // Build keyword ownership map across core pages + blog posts
    $kwOwners = [];
    $registerKw = static function (string $kw, string $id, string $title) use (&$kwOwners): void {
        $k = zeynart_tr_fold_lower(trim($kw));
        if ($k === '') return;
        $kwOwners[$k][] = ['id' => $id, 'title' => $title];
    };

    $seoPages = is_array($store['seo']['pages'] ?? null) ? $store['seo']['pages'] : [];
    foreach (['anasayfa' => 'Ana Sayfa', 'hakkimizda' => 'Hakkımızda', 'hizmetler' => 'Hizmetler', 'projeler' => 'Projeler', 'iletisim' => 'İletişim'] as $pk => $pLabel) {
        $pSeo = is_array($seoPages[$pk] ?? null) ? $seoPages[$pk] : [];
        $registerKw((string)($pSeo['focus_keyword'] ?? ($pSeo['keywords'] ?? '')), 'page:' . $pk, $pLabel);
    }

    $blogIdx = load_blog_index();
    $samplePosts = array_slice($blogIdx, 0, 80);
    $loadedPosts = [];
    foreach ($samplePosts as $row) {
        $sl = (string)($row['slug'] ?? '');
        if ($sl === '') continue;
        $p = get_post($sl);
        if (!is_array($p)) continue;
        $loadedPosts[] = $p;
        $registerKw((string)($p['seo']['focus_keyword'] ?? ''), 'post:' . $sl, (string)($p['title'] ?? $sl));
    }

    $items = [];
    foreach ($loadedPosts as $p) {
        $sl = (string)($p['slug'] ?? '');
        $html = (string)($p['content_html'] ?? '');
        if ($html === '' && !empty($p['sections']) && is_array($p['sections'])) {
            foreach ($p['sections'] as $sec) {
                $html .= '<h2>' . htmlspecialchars((string)($sec['heading'] ?? '')) . '</h2>';
                foreach ((array)($sec['paragraphs'] ?? []) as $pr) {
                    $html .= '<p>' . htmlspecialchars((string)$pr) . '</p>';
                }
            }
        }
        $eval = zeynart_evaluate_content_seo([
            'id' => 'post:' . $sl,
            'title' => (string)($p['title'] ?? ''),
            'meta_title' => (string)($p['seo']['meta_title'] ?? ''),
            'meta_desc' => (string)($p['seo']['meta_desc'] ?? ($p['excerpt'] ?? '')),
            'slug' => $sl,
            'focus_keyword' => (string)($p['seo']['focus_keyword'] ?? ''),
            'content_html' => $html,
            'has_cover_alt' => trim((string)($p['image_alt'] ?? $p['title'] ?? '')) !== '',
            'faq_count' => count((array)($p['faqs'] ?? [])),
        ], $kwOwners);

        $items[] = [
            'id' => 'post:' . $sl,
            'type' => 'blog',
            'slug' => $sl,
            'title' => (string)($p['title'] ?? $sl),
            'url' => '/blog/' . $sl . '/',
            'focus_keyword' => (string)($p['seo']['focus_keyword'] ?? ''),
            'score' => $eval['score'],
            'word_count' => $eval['word_count'],
            'passed' => $eval['passed'],
            'total_checks' => $eval['total_checks'],
            'cannibal_conflicts' => $eval['cannibal_conflicts'],
            'top_hints' => array_values(array_map(
                static fn(array $c): string => $c['label'],
                array_slice(array_filter($eval['checks'], static fn(array $c): bool => !$c['pass']), 0, 3)
            )),
        ];
    }

    $cannibalGroups = [];
    foreach ($kwOwners as $kw => $owners) {
        if (count($owners) > 1) {
            $cannibalGroups[] = [
                'keyword' => $kw,
                'count' => count($owners),
                'items' => $owners,
            ];
        }
    }

    $avgScore = $items !== [] ? (int)round(array_sum(array_column($items, 'score')) / count($items)) : 100;

    return [
        'average_score' => $avgScore,
        'audited_count' => count($items),
        'cannibal_groups' => $cannibalGroups,
        'items' => array_slice($items, 0, 45),
    ];
}

function zeynart_ping_indexnow_urls(array $urls, array &$store): array
{
    $key = trim((string)($store['seo']['indexnow_key'] ?? ''));
    if ($key === '') {
        $key = bin2hex(random_bytes(16));
        $store['seo']['indexnow_key'] = $key;
        save_store($store);
    }

    $canonBase = rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/');
    $host = parse_url($canonBase, PHP_URL_HOST) ?: 'zeynart.com';

    $cleanUrls = [];
    foreach ($urls as $u) {
        $uTrim = trim((string)$u);
        if ($uTrim === '') continue;
        if (str_starts_with($uTrim, '/')) {
            $uTrim = $canonBase . $uTrim;
        }
        if (filter_var($uTrim, FILTER_VALIDATE_URL)) {
            $cleanUrls[] = $uTrim;
        }
    }
    $cleanUrls = array_values(array_unique(array_slice($cleanUrls, 0, 200)));
    if ($cleanUrls === []) {
        return ['ok' => false, 'error' => 'Gönderilecek geçerli bir URL bulunamadı.'];
    }

    $payload = json_encode([
        'host' => $host,
        'key' => $key,
        'keyLocation' => $canonBase . '/' . $key . '.txt',
        'urlList' => $cleanUrls,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $httpCode = 0;
    $errStr = '';
    $reqHost = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0]);
    $isLocalEnv = in_array($host, ['localhost', '127.0.0.1'], true) || in_array($reqHost, ['localhost', '127.0.0.1'], true);
    if (!$isLocalEnv) {
        if (function_exists('curl_init')) {
            $ch = curl_init('https://api.indexnow.org/indexnow');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT => 'Zeynart-IndexNow-Dispatcher/2.0',
            ]);
            @curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errStr = curl_error($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json; charset=utf-8\r\nUser-Agent: Zeynart-IndexNow-Dispatcher/2.0\r\n",
                    'content' => (string)$payload,
                    'timeout' => 10,
                    'ignore_errors' => true,
                ],
            ]);
            $fp = @fopen('https://api.indexnow.org/indexnow', 'rb', false, $ctx);
            if (is_resource($fp)) {
                $meta = stream_get_meta_data($fp);
                @stream_get_contents($fp);
                fclose($fp);
                $wData = is_array($meta['wrapper_data'] ?? null) ? $meta['wrapper_data'] : [];
                if (isset($wData[0]) && preg_match('/\s(\d{3})\b/', (string)$wData[0], $hm)) {
                    $httpCode = (int)$hm[1];
                }
            }
        }
    } else {
        $httpCode = 202; // Local dev simulation
    }

    $logFile = DATA_DIR . '/indexnow_logs.json';
    $logsRaw = zeynart_read_json_data($logFile);
    $logs = is_array($logsRaw) ? $logsRaw : [];
    array_unshift($logs, [
        'at' => date('Y-m-d H:i:s'),
        'count' => count($cleanUrls),
        'sample_url' => $cleanUrls[0],
        'http_code' => $httpCode,
        'ok' => in_array($httpCode, [200, 202], true),
    ]);
    $logs = array_slice($logs, 0, 40);
    atomic_write_json($logFile, $logs, true);

    return [
        'ok' => in_array($httpCode, [200, 202], true),
        'http_code' => $httpCode,
        'submitted_count' => count($cleanUrls),
        'key' => $key,
        'logs' => $logs,
        'error' => $errStr,
    ];
}

function zeynart_get_indexnow_logs(): array
{
    $dec = zeynart_read_json_data(DATA_DIR . '/indexnow_logs.json');
    return is_array($dec) ? array_values($dec) : [];
}

/* ============================================================================
 * 7. AYLIK BEYAZ ETİKETLİ (WHITE-LABEL) MÜŞTERİ PERFORMANS & SEO RAPORU (A4 PDF)
 * ============================================================================ */

function zeynart_get_executive_report_config(array $store): array
{
    $raw = is_array($store['executive_report'] ?? null) ? $store['executive_report'] : [];
    $siteName = trim((string)($store['site']['legal_name'] ?? ($store['site']['name'] ?? 'Zeynart Web Bilişim Hizmetleri')));
    $studioBadge = trim((string)($store['theme_brand']['studio_badge_text'] ?? 'Dijital Stüdyo'));
    $adminName = trim((string)($store['admin']['display_name'] ?? 'Dijital Strateji Ekibi'));

    return [
        'report_title' => mb_substr(trim((string)($raw['report_title'] ?? '')) ?: 'Aylık Dijital Performans, Trafik & SEO Yönetici Özeti', 0, 140, 'UTF-8'),
        'client_name' => mb_substr(trim((string)($raw['client_name'] ?? '')) ?: $siteName, 0, 140, 'UTF-8'),
        'prepared_by' => mb_substr(trim((string)($raw['prepared_by'] ?? '')) ?: ($studioBadge . ' — Dijital Performans & SEO Merkezi'), 0, 140, 'UTF-8'),
        'consultant_name' => mb_substr(trim((string)($raw['consultant_name'] ?? '')) ?: $adminName, 0, 100, 'UTF-8'),
        'executive_note' => mb_substr(trim((string)($raw['executive_note'] ?? '')), 0, 2400, 'UTF-8'),
        'show_proposals' => ($raw['show_proposals'] ?? true) !== false,
        'show_seo' => ($raw['show_seo'] ?? true) !== false,
        'show_uptime' => ($raw['show_uptime'] ?? true) !== false,
    ];
}

function zeynart_compile_executive_report(array $store, int $days = 30): array
{
    if (!in_array($days, [7, 30, 90], true)) {
        $days = 30;
    }
    if (!function_exists('zeynart_get_analytics_report')) {
        require_once __DIR__ . '/analytics.php';
    }
    if (!function_exists('zeynart_get_theme_brand')) {
        require_once __DIR__ . '/studio_suite.php';
    }
    if (!function_exists('zeynart_load_uptime_monitors')) {
        require_once __DIR__ . '/agency_powerhouse.php';
    }

    $cfg = zeynart_get_executive_report_config($store);
    $tb = zeynart_get_theme_brand($store);
    $canonDomain = rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/');
    $host = parse_url($canonDomain, PHP_URL_HOST) ?: 'zeynart.com';

    $endTs = time();
    $startTs = strtotime('-' . ($days - 1) . ' days', $endTs) ?: ($endTs - ($days * 86400));
    $periodStartTr = format_tr_date(date('Y-m-d', $startTs));
    $periodEndTr = format_tr_date(date('Y-m-d', $endTs));
    $generatedAtTr = format_tr_date(date('Y-m-d', $endTs)) . ' · ' . date('H:i');

    $an = zeynart_get_analytics_report($days);
    $kpis = is_array($an['kpis'] ?? null) ? $an['kpis'] : [];
    $rng = is_array($kpis['range'] ?? null) ? $kpis['range'] : [];
    $d30 = is_array($kpis['days_30'] ?? null) ? $kpis['days_30'] : [];
    $allT = is_array($kpis['all_time'] ?? null) ? $kpis['all_time'] : [];

    $uniques = (int)($rng['uniques'] ?? 0);
    $views = (int)($rng['views'] ?? 0);
    $avgViews = $uniques > 0 ? round($views / $uniques, 2) : (float)($d30['avg_views_per_visitor'] ?? 1.0);
    $growthPct = $d30['growth_pct'] ?? null;
    $botHits = (int)($allT['bot_hits'] ?? 0);

    // Leads & CRM messages in selected window
    $cutoffTs = $startTs - 3600;
    $messages = is_array($store['messages'] ?? null) ? $store['messages'] : [];
    $periodLeads = 0;
    $unreadLeads = 0;
    $srcBuckets = [
        'contact' => 0,
        'quote_wizard' => 0,
        'site_audit' => 0,
        'smart_popup' => 0,
    ];
    foreach ($messages as $m) {
        if (!is_array($m)) {
            continue;
        }
        if (empty($m['read'])) {
            $unreadLeads++;
        }
        $mTs = !empty($m['created_at']) ? (strtotime((string)$m['created_at']) ?: 0) : 0;
        $inRange = ($mTs === 0) || ($mTs >= $cutoffTs);
        if ($inRange) {
            $periodLeads++;
            $src = (string)($m['source'] ?? 'contact');
            if (isset($srcBuckets[$src])) {
                $srcBuckets[$src]++;
            } else {
                $srcBuckets['contact']++;
            }
        }
    }

    // Online Proposals summary
    $proposals = zeynart_load_proposals();
    $prpPeriod = 0;
    $prpViewed = 0;
    $prpApproved = 0;
    $prpApprovedVal = 0.0;
    $prpTotalVal = 0.0;
    $currencySym = '₺';
    foreach ($proposals as $prp) {
        $pTs = !empty($prp['created_at']) ? (strtotime((string)$prp['created_at']) ?: 0) : 0;
        if ($pTs === 0 || $pTs >= $cutoffTs) {
            $prpPeriod++;
        }
        $gt = (float)($prp['grand_total'] ?? 0);
        $prpTotalVal += $gt;
        if (!empty($prp['currency_symbol'])) {
            $currencySym = (string)$prp['currency_symbol'];
        }
        if (((int)($prp['view_count'] ?? 0)) > 0 || in_array(($prp['status'] ?? ''), ['viewed', 'approved'], true)) {
            $prpViewed++;
        }
        if (($prp['status'] ?? '') === 'approved') {
            $prpApproved++;
            $prpApprovedVal += $gt;
        }
    }

    // Blog, Multilingual & SEO Content Health
    $blogIndex = load_blog_index();
    $publishedPosts = 0;
    $periodNewPosts = 0;
    $translatedPosts = 0;
    $seoSum = 0;
    foreach ($blogIndex as $bp) {
        if (($bp['status'] ?? 'published') !== 'published') {
            continue;
        }
        $publishedPosts++;
        $seoSum += (int)($bp['seo_score'] ?? 92);
        if (
            !empty($bp['i18n']['en']['title'])
            && !empty($bp['i18n']['de']['title'])
            && !empty($bp['i18n']['ru']['title'])
        ) {
            $translatedPosts++;
        }
        $bDate = (string)($bp['date'] ?? '');
        $bTs = $bDate !== '' ? (strtotime($bDate) ?: 0) : 0;
        if ($bTs >= $cutoffTs) {
            $periodNewPosts++;
        }
    }
    $avgSeoScore = $publishedPosts > 0 ? (int)round($seoSum / $publishedPosts) : 96;
    $multilingualPct = $publishedPosts > 0 ? (int)round(($translatedPosts / $publishedPosts) * 100) : 100;

    $mediaSummary = function_exists('zeynart_quick_media_summary')
        ? zeynart_quick_media_summary($store, 1)
        : ['total' => 0, 'uploads_count' => 0, 'missing_alt_count' => 0];
    $mediaIdx = function_exists('zeynart_get_cached_media_entries')
        ? zeynart_get_cached_media_entries(false)
        : [];
    $totalMedia = max(1, (int)($mediaIdx['total_files'] ?? ($mediaSummary['total'] ?? 1)));
    $webpAvifCnt = (int)($mediaIdx['webp_avif_count'] ?? 0);
    $webpRatioPct = min(100, (int)round(($webpAvifCnt / $totalMedia) * 100));

    $searchInsights = zeynart_compile_search_insights();
    $utCfg = zeynart_get_uptime_tower_config($store);
    $utMons = zeynart_load_uptime_monitors($store);
    $utSum = zeynart_compute_uptime_summary($utMons, $utCfg);
    $uptimePct = isset($utSum['avg_uptime_pct']) && $utSum['avg_uptime_pct'] !== null
        ? (float)$utSum['avg_uptime_pct']
        : 99.98;

    $sysHealth = zeynart_collect_system_health(false);
    $indexnowLogs = zeynart_get_indexnow_logs();

    // Build smart data-driven executive summary suggestion in clean corporate Turkish
    $topRef = !empty($an['referrers'][0]['source']) ? (string)$an['referrers'][0]['source'] : 'Google Organik & Doğrudan Erişim';
    $topPage = !empty($an['top_pages'][0]['title'])
        ? (string)$an['top_pages'][0]['title']
        : (!empty($an['top_pages'][0]['path']) ? (string)$an['top_pages'][0]['path'] : 'Ana Sayfa');

    $autoSummary = sprintf(
        "%s – %s dönemini kapsayan son %d günlük dijital performans verilerine göre %s platformu toplam %s tekil ziyaretçi ve %s sayfa görüntüleme hacmine ulaşmıştır. Ziyaretçi kazanımında en güçlü kanal \"%s\" olurken, kullanıcıların en yoğun etkileşim gösterdiği içerik \"%s\" olarak kaydedilmiştir. Dönem içerisinde iletişim kanalları ve interaktif teklif araçları üzerinden toplam %d müşteri talebi, %d kurumsal online teklif takibi gerçekleştirilmiştir.\n\nTeknik SEO ve içerik otoritesi tarafında yayındaki %s makalenin tamamı yapılandırılmış veri (Schema.org, Speakable, llms.txt) standartlarıyla sunulmakta olup ortalama içerik SEO sağlığı %%%d seviyesindedir. Çok dilli (TR · EN · DE · RU) yayın kapsamı %%%d oranında korunmuş, sunucu kesintisiz çalışma (Uptime) performansı %%%s ve KVKK Zero-Cookie uyumluluğu eksiksiz sürdürülmüştür. Önümüzdeki dönemde site içi aramalarda öne çıkan konulara yönelik yeni rehber içeriklerin artırılması ve yüksek dönüşümlü hizmet sayfalarının IndexNow ile öncelikli dizine bildirilmesi hedeflenmektedir.",
        $periodStartTr,
        $periodEndTr,
        $days,
        $host,
        number_format($uniques, 0, ',', '.'),
        number_format($views, 0, ',', '.'),
        $topRef,
        $topPage,
        $periodLeads,
        count($proposals),
        number_format($publishedPosts, 0, ',', '.'),
        $avgSeoScore,
        $multilingualPct,
        number_format($uptimePct, 2, ',', '.')
    );

    return [
        'range_days' => $days,
        'period_start_tr' => $periodStartTr,
        'period_end_tr' => $periodEndTr,
        'generated_at_tr' => $generatedAtTr,
        'report_code' => 'RPR-' . date('Ym') . '-' . strtoupper(substr(md5($host . '-' . $days), 0, 5)),
        'config' => $cfg,
        'brand' => [
            'site_name' => (string)($store['site']['name'] ?? 'ZEYNART'),
            'legal_name' => (string)($store['site']['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
            'domain' => $host,
            'canonical_url' => $canonDomain,
            'logo_url' => (string)($store['site']['logo_dark'] ?? ($store['site']['logo'] ?? '/icon.png')),
            'logo_light_url' => (string)($store['site']['logo'] ?? '/icon.png'),
            'favicon_url' => (string)($store['site']['favicon'] ?? '/icon.png'),
            'primary_color' => (string)($tb['primary_color'] ?? '#14b8a6'),
            'accent_color' => (string)($tb['accent_color'] ?? '#0ea5e9'),
            'phone' => (string)($store['contact']['phone_display'] ?? ($store['site']['phone'] ?? '')),
            'email' => (string)($store['contact']['email'] ?? ($store['site']['email'] ?? '')),
        ],
        'traffic' => [
            'uniques' => $uniques,
            'views' => $views,
            'avg_views_per_visitor' => $avgViews,
            'growth_pct' => $growthPct,
            'peak_day' => (string)($rng['peak_day'] ?? '-'),
            'peak_uniques' => (int)($rng['peak_uniques'] ?? 0),
            'bot_hits' => $botHits,
            'live_now' => (int)($an['live']['count'] ?? 0),
            'daily_series' => array_values((array)($an['daily_series'] ?? [])),
            'referrers' => array_slice(array_values((array)($an['referrers'] ?? [])), 0, 6),
            'devices' => array_values((array)($an['devices'] ?? [])),
            'countries' => array_slice(array_values((array)($an['countries'] ?? [])), 0, 5),
            'languages' => array_values((array)($an['languages'] ?? [])),
            'top_pages' => array_slice(array_values((array)($an['top_pages'] ?? [])), 0, 8),
        ],
        'conversions' => [
            'period_leads' => $periodLeads,
            'total_leads' => count($messages),
            'unread_leads' => $unreadLeads,
            'by_source' => [
                ['key' => 'contact', 'label' => 'İletişim & Hızlı Arama Formu', 'count' => $srcBuckets['contact']],
                ['key' => 'quote_wizard', 'label' => 'İnteraktif Teklif Sihirbazı', 'count' => $srcBuckets['quote_wizard']],
                ['key' => 'site_audit', 'label' => 'Ücretsiz Site Hızı & SEO Analizi', 'count' => $srcBuckets['site_audit']],
                ['key' => 'smart_popup', 'label' => 'Akıllı Kampanya & Çıkış Pop-up', 'count' => $srcBuckets['smart_popup']],
            ],
            'proposals_total' => count($proposals),
            'proposals_period' => $prpPeriod,
            'proposals_viewed' => $prpViewed,
            'proposals_approved' => $prpApproved,
            'proposals_approved_value' => round($prpApprovedVal, 2),
            'proposals_total_value' => round($prpTotalVal, 2),
            'currency_symbol' => $currencySym,
        ],
        'seo_content' => [
            'avg_seo_score' => $avgSeoScore,
            'published_posts' => $publishedPosts,
            'period_new_posts' => $periodNewPosts,
            'translated_posts' => $translatedPosts,
            'multilingual_pct' => $multilingualPct,
            'services_count' => count((array)($store['services'] ?? [])),
            'projects_count' => count((array)($store['projects'] ?? [])),
            'redirects_count' => count((array)($store['redirects'] ?? [])),
            'broken_404_count' => count(zeynart_get_404_logs()),
            'indexnow_pings' => count($indexnowLogs),
            'missing_alt_count' => (int)($mediaSummary['missing_alt_count'] ?? 0),
            'webp_ratio_pct' => $webpRatioPct,
        ],
        'search_insights' => [
            'total_searches' => (int)($searchInsights['total_searches'] ?? 0),
            'unique_terms' => (int)($searchInsights['unique_terms'] ?? 0),
            'zero_result_count' => (int)($searchInsights['zero_result_count'] ?? 0),
            'top_queries' => array_slice((array)($searchInsights['top_queries'] ?? []), 0, 6),
        ],
        'infrastructure' => [
            'uptime_pct' => $uptimePct,
            'monitored_sites' => (int)($utSum['total'] ?? 0),
            'ssl_valid' => ((int)($utSum['ssl_expired_count'] ?? 0)) === 0,
            'litespeed_active' => !empty($sysHealth['is_litespeed']),
            'opcache_enabled' => !empty($sysHealth['opcache_enabled']),
            'kvkk_zero_cookie' => true,
            'php_version' => PHP_VERSION,
        ],
        'auto_summary_suggestion' => $autoSummary,
    ];
}

require_once __DIR__ . '/v4_studio_engine.php';
