<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cookie_consent.php';
require_once __DIR__ . '/studio_suite.php';
require_once __DIR__ . '/studio_pro_pack.php';

function atomic_write_json(string $filePath, mixed $data, bool $pretty = true): bool
{
    if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        $desc = zeynart_sql_classify_data_path($filePath);
        if ($desc !== null) {
            $okSql = false;
            if ($desc['type'] === 'kv') {
                $okSql = zeynart_sql_kv_set($desc['key'], $data, $desc['group'] ?? 'misc');
            } elseif ($desc['type'] === 'blog_index') {
                $okSql = is_array($data) ? zeynart_sql_save_blog_index($data) : false;
            } elseif ($desc['type'] === 'post') {
                $pdo = zeynart_get_pdo();
                if ($pdo && is_array($data)) {
                    $okSql = zeynart_sql_upsert_post_row($pdo, zeynart_load_db_config(), $data, zeynart_sql_build_summary_from_post($data));
                }
            } elseif ($desc['type'] === 'revision') {
                $okSql = is_array($data) ? zeynart_sql_capture_revision($data) : false;
            } elseif ($desc['type'] === 'analytics') {
                $okSql = is_array($data) ? (zeynart_sql_analytics_mutate($desc['key'], $data, static fn(): array => $data) !== []) : false;
            }
            if ($okSql) {
                zeynart_notify_json_mutated($filePath);
            }
            return $okSql;
        }
    }

    $dir = dirname($filePath);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if ($pretty) {
        $flags |= JSON_PRETTY_PRINT;
    }
    $json = json_encode($data, $flags);
    if ($json === false) {
        return false;
    }
    $tmp = $filePath . '.tmp.' . bin2hex(random_bytes(4));
    $wroteTmp = @file_put_contents($tmp, $json, LOCK_EX);
    if ($wroteTmp === false) {
        @chmod($dir, 0775);
        $wroteTmp = @file_put_contents($tmp, $json, LOCK_EX);
    }
    if ($wroteTmp === false) {
        @unlink($tmp);
        // Fallback when directory disallowing .tmp creation still allows writing existing target file (e.g. Plesk 0755 dir + 0664 file)
        if (is_file($filePath)) {
            @chmod($filePath, 0664);
        }
        $okDirect = @file_put_contents($filePath, $json, LOCK_EX) !== false;
        if ($okDirect) {
            zeynart_notify_json_mutated($filePath);
        }
        return $okDirect;
    }
    if (!@rename($tmp, $filePath)) {
        if (is_file($filePath)) {
            @chmod($filePath, 0664);
        }
        // Fallback when destination is briefly locked or cross-owner in shared directory
        if (@copy($tmp, $filePath) || @file_put_contents($filePath, $json, LOCK_EX) !== false) {
            @unlink($tmp);
            zeynart_notify_json_mutated($filePath);
            return true;
        }
        @unlink($tmp);
        return false;
    }
    zeynart_notify_json_mutated($filePath);
    return true;
}

function zeynart_notify_json_mutated(string $filePath): void
{
    @clearstatcache(true, $filePath);
    $baseWritten = basename($filePath);
    if ($baseWritten === 'store.json' || $baseWritten === 'blog_index.json') {
        $GLOBALS['__zeynart_boot_bundle_mem'] = null;
        if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
            zeynart_sql_invalidate_runtime_caches();
        }
        $bootCache = DATA_DIR . '/admin_boot_summary_cache.json';
        if (is_file($bootCache)) {
            @unlink($bootCache);
        }
        if ($baseWritten === 'store.json') {
            $GLOBALS['__zeynart_media_summary_mem'] = null;
            $sumCache = DATA_DIR . '/media_summary_cache.json';
            if (is_file($sumCache)) {
                @unlink($sumCache);
            }
        }
    }
}

function get_home_sections_catalog(): array
{
    return [
        'hero' => ['default_visible' => true],
        'intro' => ['default_visible' => true],
        'services' => ['default_visible' => true],
        'brand_scene' => ['default_visible' => true],
        'process' => ['default_visible' => true],
        'projects_showcase' => ['default_visible' => false],
        'clients_strip' => ['default_visible' => false],
        'google_reviews' => ['default_visible' => true],
        'site_audit' => ['default_visible' => true],
        'quote_wizard' => ['default_visible' => false],
        'latest_blog' => ['default_visible' => false],
        'builder_blocks' => ['default_visible' => true],
        'cta' => ['default_visible' => true],
    ];
}

function zeynart_default_page_slugs(): array
{
    return [
        'hakkimizda' => 'hakkimizda',
        'hizmetler' => 'hizmetler',
        'projeler' => 'projeler',
        'blog' => 'blog',
        'iletisim' => 'iletisim',
        'site-analiz' => 'site-analiz',
        'teklif-sihirbazi' => 'teklif-sihirbazi',
        'gorusme-planla' => 'gorusme-planla',
        'kvkk' => 'kvkk',
        'gizlilik' => 'gizlilik',
        'cerez-politikasi' => 'cerez-politikasi',
    ];
}

function zeynart_system_reserved_slugs(): array
{
    $list = [
        'admin', 'api', 'data', 'includes', 'lib', 'pages', 'uploads', 'images', 'assets',
        'en', 'de', 'ru', 'tr', 'og-card', 'opengraph-image', 'kartvizit', 'teklif', 'proje-takip',
        'sitemap', 'robots', 'llms', 'llms-full', 'ai-catalog',
    ];
    if (defined('ADMIN_DIR_NAME')) {
        $phys = strtolower(trim(ADMIN_DIR_NAME));
        if ($phys !== '' && !in_array($phys, $list, true)) {
            $list[] = $phys;
        }
    }
    return $list;
}

function zeynart_resolved_page_slugs(?array $store = null): array
{
    $st = $store ?? load_store();
    $defaults = zeynart_default_page_slugs();
    $reserved = array_flip(zeynart_system_reserved_slugs());
    $rawMap = is_array($st['page_slugs'] ?? null) ? $st['page_slugs'] : [];
    $legalMap = is_array($st['legal_pages'] ?? null) ? $st['legal_pages'] : [];

    $resolved = [];
    $used = [];
    foreach ($defaults as $key => $defSlug) {
        $candRaw = '';
        if (isset($rawMap[$key]) && is_string($rawMap[$key]) && trim($rawMap[$key]) !== '') {
            $candRaw = trim($rawMap[$key]);
        } elseif (in_array($key, ['kvkk', 'gizlilik', 'cerez-politikasi'], true) && !empty($legalMap[$key]['slug'])) {
            $candRaw = trim((string)$legalMap[$key]['slug']);
        }
        $slug = $candRaw !== '' ? slugify($candRaw) : $defSlug;
        if ($slug === '' || isset($reserved[$slug]) || isset($used[$slug])) {
            $slug = isset($used[$defSlug]) ? ($defSlug . '-sayfa') : $defSlug;
        }
        $resolved[$key] = $slug;
        $used[$slug] = true;
    }
    return $resolved;
}

function zeynart_page_slug(array $store, string $pageKey): string
{
    $cleanKey = str_starts_with($pageKey, 'legal:') ? substr($pageKey, 6) : $pageKey;
    if ($cleanKey === 'anasayfa') {
        return '';
    }
    $resolved = zeynart_resolved_page_slugs($store);
    return $resolved[$cleanKey] ?? slugify($cleanKey);
}

function zeynart_page_url(array $store, string $pageKey): string
{
    if ($pageKey === 'anasayfa' || $pageKey === '') {
        return '/';
    }
    $resolved = zeynart_resolved_page_slugs($store);
    if (str_starts_with($pageKey, 'service:')) {
        $sSlug = trim(substr($pageKey, 8), '/');
        return '/' . $resolved['hizmetler'] . '/' . $sSlug . '/';
    }
    if (str_starts_with($pageKey, 'project:')) {
        $pSlug = trim(substr($pageKey, 8), '/');
        return '/' . $resolved['projeler'] . '/' . $pSlug . '/';
    }
    if (str_starts_with($pageKey, 'custom:')) {
        $cId = substr($pageKey, 7);
        foreach (($store['custom_pages'] ?? []) as $cp) {
            if (is_array($cp) && (($cp['id'] ?? '') === $cId || ($cp['slug'] ?? '') === $cId)) {
                return '/' . trim((string)($cp['slug'] ?? 'sayfa'), '/') . '/';
            }
        }
        return '/' . slugify($cId) . '/';
    }
    $cleanKey = str_starts_with($pageKey, 'legal:') ? substr($pageKey, 6) : $pageKey;
    if (isset($resolved[$cleanKey])) {
        return '/' . $resolved[$cleanKey] . '/';
    }
    return '/' . trim($cleanKey, '/') . '/';
}

function zeynart_rewrite_custom_slug_path(string $cleanPath, ?array $store = null): string
{
    if ($cleanPath === '' || $cleanPath === '/' || str_starts_with($cleanPath, '/?')) {
        return $cleanPath;
    }
    $suffix = '';
    $pathOnly = $cleanPath;
    $qPos = strcspn($cleanPath, '?#');
    if ($qPos < strlen($cleanPath)) {
        $pathOnly = substr($cleanPath, 0, $qPos);
        $suffix = substr($cleanPath, $qPos);
    }
    $trimmed = trim($pathOnly, '/');
    if ($trimmed === '') {
        return $cleanPath;
    }

    $resolved = zeynart_resolved_page_slugs($store);
    $segs = explode('/', $trimmed);
    $seg0 = $segs[0];

    if (count($segs) === 1) {
        if (in_array($seg0, $resolved, true)) {
            return $cleanPath;
        }
        $defaults = zeynart_default_page_slugs();
        if (isset($defaults[$seg0]) && $resolved[$seg0] !== $seg0) {
            return '/' . $resolved[$seg0] . '/' . $suffix;
        }
        return $cleanPath;
    }

    if (count($segs) === 2 && in_array($seg0, ['hizmetler', 'projeler', 'blog'], true)) {
        if (!in_array($seg0, [$resolved['hizmetler'], $resolved['projeler'], $resolved['blog']], true) && $resolved[$seg0] !== $seg0) {
            return '/' . $resolved[$seg0] . '/' . $segs[1] . '/' . $suffix;
        }
    }

    if (count($segs) === 3 && $seg0 === 'blog' && $segs[1] === 'kategori') {
        if ($resolved['blog'] !== 'blog') {
            return '/' . $resolved['blog'] . '/kategori/' . $segs[2] . '/' . $suffix;
        }
    }

    return $cleanPath;
}

function zeynart_normalize_page_status(mixed $raw): string
{
    $st = strtolower(trim((string)$raw));
    return in_array($st, ['published', 'draft', 'trash', 'deleted'], true) ? $st : 'published';
}

function zeynart_page_status(?array $store, string $pageKey): string
{
    if ($pageKey === 'anasayfa' || $pageKey === '/') {
        return 'published';
    }
    $st = $store ?? load_store();
    if (str_starts_with($pageKey, 'service:')) {
        $slug = substr($pageKey, 8);
        foreach (($st['services'] ?? []) as $srv) {
            if (($srv['slug'] ?? '') === $slug) {
                return zeynart_normalize_page_status($srv['status'] ?? 'published');
            }
        }
        return 'deleted';
    }
    if (str_starts_with($pageKey, 'project:')) {
        $slug = substr($pageKey, 8);
        foreach (($st['projects'] ?? []) as $prj) {
            if (($prj['slug'] ?? '') === $slug) {
                return zeynart_normalize_page_status($prj['status'] ?? 'published');
            }
        }
        return 'deleted';
    }
    if (str_starts_with($pageKey, 'custom:')) {
        $cid = substr($pageKey, 7);
        foreach (($st['custom_pages'] ?? []) as $cp) {
            if (($cp['id'] ?? '') === $cid) {
                return zeynart_normalize_page_status($cp['status'] ?? 'published');
            }
        }
        return 'deleted';
    }
    $cleanKey = str_starts_with($pageKey, 'legal:') ? substr($pageKey, 6) : $pageKey;
    if (isset($st['page_states'][$cleanKey]) && is_array($st['page_states'][$cleanKey])) {
        return zeynart_normalize_page_status($st['page_states'][$cleanKey]['status'] ?? 'published');
    }
    if (isset($st['legal_pages'][$cleanKey]) && is_array($st['legal_pages'][$cleanKey]) && isset($st['legal_pages'][$cleanKey]['status'])) {
        return zeynart_normalize_page_status($st['legal_pages'][$cleanKey]['status']);
    }
    return 'published';
}

function zeynart_is_page_accessible(?array $store, string $pageKey, bool $includeDrafts = false): bool
{
    $status = zeynart_page_status($store, $pageKey);
    if ($status === 'published') {
        return true;
    }
    if ($status === 'draft') {
        return $includeDrafts;
    }
    return false;
}

function zeynart_public_services(?array $store = null): array
{
    $st = $store ?? load_store();
    $out = [];
    foreach (($st['services'] ?? []) as $srv) {
        if (is_array($srv) && zeynart_normalize_page_status($srv['status'] ?? 'published') === 'published') {
            $out[] = $srv;
        }
    }
    return $out;
}

function zeynart_public_projects(?array $store = null): array
{
    $st = $store ?? load_store();
    $out = [];
    foreach (($st['projects'] ?? []) as $prj) {
        if (is_array($prj) && zeynart_normalize_page_status($prj['status'] ?? 'published') === 'published') {
            $out[] = $prj;
        }
    }
    return $out;
}

function zeynart_is_internal_url_hidden(?array $store, string $rawUrl): bool
{
    $t = trim($rawUrl);
    if ($t === '' || $t === '/' || str_starts_with($t, 'http://') || str_starts_with($t, 'https://') || str_starts_with($t, 'mailto:') || str_starts_with($t, 'tel:') || str_starts_with($t, '#')) {
        return false;
    }
    $path = parse_url($t, PHP_URL_PATH);
    $trimmed = trim((string)$path, '/');
    if ($trimmed === '') {
        return false;
    }
    $segs = explode('/', $trimmed);
    if (in_array($segs[0], ['en', 'de', 'ru', 'tr'], true)) {
        array_shift($segs);
    }
    if ($segs === []) {
        return false;
    }
    $st = $store ?? load_store();
    $slugs = zeynart_resolved_page_slugs($st);
    $defaults = zeynart_default_page_slugs();

    if (count($segs) === 1) {
        $s0 = $segs[0];
        if (in_array($s0, ['cerezler', 'cookie-policy'], true)) {
            return zeynart_page_status($st, 'cerez-politikasi') !== 'published';
        }
        foreach ($slugs as $k => $resSlug) {
            if ($s0 === $resSlug || (isset($defaults[$k]) && $s0 === $defaults[$k])) {
                return zeynart_page_status($st, $k) !== 'published';
            }
        }
        foreach (($st['custom_pages'] ?? []) as $cp) {
            if (($cp['slug'] ?? '') === $s0) {
                return zeynart_normalize_page_status($cp['status'] ?? 'published') !== 'published';
            }
        }
        return false;
    }

    if (count($segs) === 2) {
        if ($segs[0] === $slugs['hizmetler'] || $segs[0] === 'hizmetler') {
            if (zeynart_page_status($st, 'hizmetler') !== 'published') {
                return true;
            }
            foreach (($st['services'] ?? []) as $srv) {
                if (($srv['slug'] ?? '') === $segs[1]) {
                    return zeynart_normalize_page_status($srv['status'] ?? 'published') !== 'published';
                }
            }
        } elseif ($segs[0] === $slugs['projeler'] || $segs[0] === 'projeler') {
            if (zeynart_page_status($st, 'projeler') !== 'published') {
                return true;
            }
            foreach (($st['projects'] ?? []) as $prj) {
                if (($prj['slug'] ?? '') === $segs[1]) {
                    return zeynart_normalize_page_status($prj['status'] ?? 'published') !== 'published';
                }
            }
        }
    }

    if (count($segs) === 3 && ($segs[0] === $slugs['blog'] || $segs[0] === 'blog') && $segs[1] === 'kategori') {
        return zeynart_page_status($st, 'blog') !== 'published';
    }

    return false;
}

function normalize_home_sections(array $home, array $store = []): array
{
    $catalog = get_home_sections_catalog();
    $validKeys = array_keys($catalog);

    $rawOrder = is_array($home['section_order'] ?? null) ? $home['section_order'] : [];
    $order = [];
    foreach ($rawOrder as $k) {
        if (is_string($k) && isset($catalog[$k]) && !in_array($k, $order, true)) {
            $order[] = $k;
        }
    }

    if ($order === []) {
        $order = $validKeys;
    } else {
        $missing = array_values(array_diff($validKeys, $order));
        if ($missing !== []) {
            $anchorPos = array_search('builder_blocks', $order, true);
            if ($anchorPos === false) {
                $anchorPos = array_search('cta', $order, true);
            }
            if ($anchorPos !== false) {
                array_splice($order, (int)$anchorPos, 0, $missing);
            } else {
                $order = array_merge($order, $missing);
            }
        }
    }

    $rawVis = is_array($home['section_visibility'] ?? null) ? $home['section_visibility'] : [];
    $vis = [];
    foreach ($validKeys as $k) {
        if (array_key_exists($k, $rawVis)) {
            $vis[$k] = (bool)$rawVis[$k];
        } elseif ($k === 'google_reviews' && isset($store['google_reviews']) && is_array($store['google_reviews']) && array_key_exists('show_on_home', $store['google_reviews'])) {
            $vis[$k] = !empty($store['google_reviews']['show_on_home']);
        } elseif ($k === 'site_audit' && isset($store['site_audit']) && is_array($store['site_audit']) && array_key_exists('show_on_home', $store['site_audit'])) {
            $vis[$k] = !empty($store['site_audit']['show_on_home']);
        } elseif ($k === 'quote_wizard' && isset($store['quote_wizard']) && is_array($store['quote_wizard']) && array_key_exists('show_on_home', $store['quote_wizard'])) {
            $vis[$k] = !empty($store['quote_wizard']['show_on_home']);
        } else {
            $vis[$k] = (bool)$catalog[$k]['default_visible'];
        }
    }

    $home['section_order'] = array_values($order);
    $home['section_visibility'] = $vis;
    return $home;
}

function load_store(bool $forceReload = false): array
{
    static $cached = null;
    if ($cached !== null && !$forceReload) {
        return $cached;
    }

    $storeFile = DATA_DIR . '/store.json';

    $decoded = null;
    if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        if ($forceReload) {
            unset($GLOBALS['__zeynart_sql_kv_mem']['store']);
        }
        $decoded = zeynart_sql_kv_get('store');
        if (!is_array($decoded) && is_file($storeFile)) {
            $decoded = json_decode((string)@file_get_contents($storeFile), true);
            if (is_array($decoded)) {
                zeynart_sql_kv_set('store', $decoded, 'core');
            }
        }
    } else {
        if (is_file($storeFile)) {
            $decoded = json_decode((string)file_get_contents($storeFile), true);
        }
    }

    if (is_array($decoded)) {
        if (is_file(DATA_DIR . '/default_seed.json')) {
            @unlink(DATA_DIR . '/default_seed.json');
        }
        {
            $dirty = false;
            if (zeynart_normalize_cookie_compliance($decoded)) {
                $dirty = true;
            }
            if (empty($decoded['seo']['_seo_v2_seeded'])) {
                $decoded['seo'] = is_array($decoded['seo'] ?? null) ? $decoded['seo'] : [];
                if (trim((string)($decoded['seo']['google_verification'] ?? '')) === '') {
                    $decoded['seo']['google_verification'] = 'Eer2XT9k78VRZUl32lkSzjKBRkUce5YUAh2aZLIa4nE';
                }
                if (trim((string)($decoded['seo']['ga_measurement_id'] ?? '')) === '') {
                    $decoded['seo']['ga_measurement_id'] = 'G-ZW9HGFSH9G';
                }
                if (empty($decoded['seo']['social_profiles']) || !is_array($decoded['seo']['social_profiles'])) {
                    $decoded['seo']['social_profiles'] = [
                        'https://instagram.com/zeynartweb',
                        'https://facebook.com/zeynartweb',
                    ];
                }
                $decoded['seo']['_seo_v2_seeded'] = true;
                $dirty = true;
            }
            if (empty($decoded['blog_categories_initialized']) || !is_array($decoded['blog_categories'] ?? null) || $decoded['blog_categories'] === []) {
                $seedFromExistingPosts = empty($decoded['blog_categories_initialized']);
                $decoded['blog_categories'] = zeynart_normalize_blog_categories(
                    is_array($decoded['blog_categories'] ?? null) ? $decoded['blog_categories'] : [],
                    $seedFromExistingPosts ? load_blog_index() : null,
                    is_array($decoded['blog_settings']['categories'] ?? null) ? $decoded['blog_settings']['categories'] : [],
                    $seedFromExistingPosts
                );
                $decoded['blog_settings'] = is_array($decoded['blog_settings'] ?? null) ? $decoded['blog_settings'] : [];
                $decoded['blog_settings']['categories'] = array_merge(
                    ['TÜMÜ'],
                    array_values(array_map(static fn(array $c): string => (string)$c['name'], $decoded['blog_categories']))
                );
                $decoded['blog_categories_initialized'] = true;
                $dirty = true;
            } else {
                $decoded['blog_categories'] = zeynart_normalize_blog_categories($decoded['blog_categories'], null, [], false);
            }
            $needCachePurgeAfterHeal = false;
            if (empty($decoded['_auto_home_fallback_cleaned_v52'])) {
                $idxForHeal = load_blog_index();
                $removedConflictingReds = zeynart_prune_blog_conflicts_from_redirects($decoded, $idxForHeal);
                if (!empty($decoded['redirects']) && is_array($decoded['redirects'])) {
                    $beforeCnt = count($decoded['redirects']);
                    $decoded['redirects'] = array_values(array_filter(
                        $decoded['redirects'],
                        static function ($r): bool {
                            if (!is_array($r)) {
                                return false;
                            }
                            $toTrim = trim((string)($r['to'] ?? ''));
                            return $toTrim !== '' && $toTrim !== '/';
                        }
                    ));
                    $removedConflictingReds += ($beforeCnt - count($decoded['redirects']));
                }
                if (function_exists('zeynart_clear_404_logs')) {
                    zeynart_clear_404_logs();
                }
                $decoded['_redirects_blog_healed_v51'] = true;
                $decoded['_auto_home_fallback_cleaned_v52'] = true;
                $dirty = true;
                if ($removedConflictingReds > 0) {
                    $needCachePurgeAfterHeal = true;
                }
            }
            if ($dirty) {
                atomic_write_json($storeFile, $decoded);
            }
            if ($needCachePurgeAfterHeal) {
                zeynart_request_lscache_purge();
            }
            $decoded['menus'] = is_array($decoded['menus'] ?? null) ? $decoded['menus'] : [];
            $decoded['menus']['footer'] = is_array($decoded['menus']['footer'] ?? null) ? $decoded['menus']['footer'] : [];
            $decoded['menus']['footer']['credit_label'] = 'Powered & Designed By Zeynart®';
            $decoded['menus']['footer']['credit_url'] = 'https://zeynart.com/';
            $decoded['home'] = normalize_home_sections(is_array($decoded['home'] ?? null) ? $decoded['home'] : [], $decoded);
            zeynart_sync_admin_slug_flag(zeynart_get_admin_slug($decoded), false);
            $cached = $decoded;
            return $cached;
        }
    }

    $cached = [
        'site' => ['name' => 'Zeynart'],
        'menus' => [
            'footer' => [
                'credit_label' => 'Powered & Designed By Zeynart®',
                'credit_url' => 'https://zeynart.com/',
            ],
        ],
        'home' => normalize_home_sections([], []),
    ];
    return $cached;
}

function zeynart_sync_admin_slug_flag(string $slug, bool $emitPurge = false): void
{
    $physNorm = zeynart_normalize_admin_slug(defined('ADMIN_DIR_NAME') ? ADMIN_DIR_NAME : 'admin');
    $defaultSlug = ($physNorm !== '' && !ctype_digit($physNorm)) ? $physNorm : 'admin';

    $norm = zeynart_normalize_admin_slug($slug);
    if ($norm === '' || $norm === 'admin' || ctype_digit($norm)) {
        $norm = $defaultSlug;
    }
    $GLOBALS['__zeynart_active_admin_slug'] = $norm;
    $flagFile = DATA_DIR . '/.custom_admin_slug';
    $exists = is_file($flagFile);
    $curVal = $exists ? zeynart_normalize_admin_slug((string)@file_get_contents($flagFile)) : $defaultSlug;
    $changed = false;

    if ($norm !== 'admin' && $norm !== $defaultSlug) {
        if (!$exists || $curVal !== $norm) {
            if (!is_dir(DATA_DIR)) {
                @mkdir(DATA_DIR, 0775, true);
            }
            @file_put_contents($flagFile, $norm, LOCK_EX);
            $changed = true;
        }
    } elseif ($exists) {
        @unlink($flagFile);
        $changed = true;
    }

    if ($changed && $emitPurge) {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header('X-LiteSpeed-Purge: *');
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }
}

function zeynart_validate_admin_slug(string $raw, ?array $store = null): array
{
    $physNorm = zeynart_normalize_admin_slug(defined('ADMIN_DIR_NAME') ? ADMIN_DIR_NAME : 'admin');
    $defaultSlug = ($physNorm !== '' && !ctype_digit($physNorm)) ? $physNorm : 'admin';

    $trimmed = trim($raw, " \t\n\r\0\x0B/\\");
    if ($trimmed === '' || strcasecmp($trimmed, 'admin') === 0 || strcasecmp($trimmed, $defaultSlug) === 0) {
        return ['ok' => true, 'slug' => $defaultSlug, 'error' => ''];
    }
    if (str_contains($trimmed, '/') || str_contains($trimmed, '\\')) {
        return ['ok' => false, 'slug' => '', 'error' => 'Giriş uzantısı alt klasör eğik çizgisi (/) içeremez; tek parça olmalıdır (örn: x veya ozel-panel).'];
    }

    $slug = zeynart_normalize_admin_slug($trimmed);
    if (strlen($slug) < 1) {
        return ['ok' => false, 'slug' => '', 'error' => 'Yönetim paneli uzantısı en az 1 karakter olmalıdır.'];
    }
    if (strlen($slug) > 48) {
        return ['ok' => false, 'slug' => '', 'error' => 'Yönetim paneli uzantısı en fazla 48 karakter olabilir.'];
    }
    if (!preg_match('/^[a-z0-9](?:[a-z0-9_-]{0,47})?$/', $slug)) {
        return ['ok' => false, 'slug' => '', 'error' => 'Uzantı yalnızca küçük İngilizce harf, rakam, tire (-) ve alt çizgi (_) içerebilir.'];
    }
    if (ctype_digit($slug)) {
        return ['ok' => false, 'slug' => '', 'error' => 'Yalnızca rakamlardan oluşan bir uzantı kullanılamaz.'];
    }
    if ($slug === 'admin' || $slug === $defaultSlug) {
        return ['ok' => true, 'slug' => $defaultSlug, 'error' => ''];
    }

    $st = $store ?? load_store();
    $blocked = array_merge(zeynart_system_reserved_slugs(), [
        'well-known', 'og-card-branded', 'sitemap-pages', 'sitemap-blog',
        'index', 'bakim', '404', 'cerezler', 'cookie-policy', 'cgi-bin', 'webmail', 'cpanel', 'plesk-stat',
    ]);
    foreach (zeynart_default_page_slugs() as $k => $v) {
        $blocked[] = (string)$k;
        $blocked[] = (string)$v;
    }
    foreach (zeynart_resolved_page_slugs($st) as $v) {
        $blocked[] = (string)$v;
    }
    foreach (($st['custom_pages'] ?? []) as $cp) {
        if (is_array($cp) && !empty($cp['slug'])) {
            $blocked[] = strtolower(trim((string)$cp['slug'], '/'));
        }
    }
    foreach (($st['services'] ?? []) as $srv) {
        if (is_array($srv) && !empty($srv['slug'])) {
            $blocked[] = strtolower(trim((string)$srv['slug'], '/'));
        }
    }
    foreach (($st['projects'] ?? []) as $prj) {
        if (is_array($prj) && !empty($prj['slug'])) {
            $blocked[] = strtolower(trim((string)$prj['slug'], '/'));
        }
    }
    foreach (load_blog_index() as $bp) {
        if (is_array($bp) && !empty($bp['slug'])) {
            $blocked[] = strtolower(trim((string)$bp['slug'], '/'));
        }
    }
    foreach (($st['redirects'] ?? []) as $red) {
        if (is_array($red) && !empty($red['from'])) {
            $rTrim = strtolower(trim((string)$red['from'], '/'));
            if ($rTrim !== '' && !str_contains($rTrim, '/')) {
                $blocked[] = $rTrim;
            }
        }
    }

    if (in_array($slug, $blocked, true)) {
        return [
            'ok' => false,
            'slug' => '',
            'error' => '"/' . $slug . '/" adresi sitedeki mevcut bir sayfa veya sistem rotası tarafından kullanılıyor. Lütfen farklı bir özel uzantı belirleyin.',
        ];
    }

    return ['ok' => true, 'slug' => $slug, 'error' => ''];
}

function zeynart_normalize_lscache_uri(string $uri): string
{
    $path = (string)(parse_url($uri, PHP_URL_PATH) ?: '/');
    if (BASE_PATH !== '' && str_starts_with($path, BASE_PATH)) {
        $path = substr($path, strlen(BASE_PATH)) ?: '/';
    }
    $path = '/' . ltrim($path, '/');
    if ($path !== '/' && !preg_match('~\.[a-z0-9]{2,5}$~i', $path)) {
        $path = rtrim($path, '/') . '/';
    }
    return $path;
}

function zeynart_lscache_tag_for_uri(string $uri): string
{
    return 'u_' . substr(md5(zeynart_normalize_lscache_uri($uri)), 0, 12);
}

function zeynart_get_lscache_config(?array $store = null): array
{
    if (is_array($store)) {
        $raw = is_array($store['lscache'] ?? null) ? $store['lscache'] : [];
        $enabled = ($raw['enabled'] ?? true) !== false;
        $ttl = (int)($raw['ttl'] ?? 3600);
        if ($ttl < 60 || $ttl > 604800) {
            $ttl = 3600;
        }
        return ['enabled' => $enabled, 'ttl' => $ttl];
    }
    $enabled = !is_file(DATA_DIR . '/.lscache_disabled');
    $ttlFile = DATA_DIR . '/.lscache_ttl';
    $ttlRaw = is_file($ttlFile) ? (int)trim((string)@file_get_contents($ttlFile)) : 3600;
    $ttl = ($ttlRaw >= 60 && $ttlRaw <= 604800) ? $ttlRaw : 3600;
    return ['enabled' => $enabled, 'ttl' => $ttl];
}

function zeynart_load_lscache_state(): array
{
    $stateFile = DATA_DIR . '/lscache_state.json';
    $dec = zeynart_read_json_data($stateFile);
    if (!is_array($dec) || !is_array($dec['pages'] ?? null)) {
        return ['pages' => [], 'updated_at' => 0];
    }
    $now = time();
    $cleanPages = [];
    foreach ($dec['pages'] as $k => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $exp = (int)($entry['expires_at'] ?? 0);
        if ($exp > $now) {
            $norm = zeynart_normalize_lscache_uri((string)($entry['uri'] ?? $k));
            $entry['uri'] = $norm;
            $entry['tag'] = (string)($entry['tag'] ?? zeynart_lscache_tag_for_uri($norm));
            $cleanPages[$norm] = $entry;
        }
    }
    return [
        'pages' => $cleanPages,
        'updated_at' => (int)($dec['updated_at'] ?? 0),
        'last_probe_at' => $dec['last_probe_at'] ?? null,
        'last_probe_mode' => $dec['last_probe_mode'] ?? null,
    ];
}

function zeynart_record_lscache_page_hit(string $uri, int $ttl = 3600, string $source = 'render', ?int $customCachedAt = null): void
{
    $norm = zeynart_normalize_lscache_uri($uri);
    $ttl = max(60, min(604800, $ttl));
    $now = time();
    $cachedAt = ($customCachedAt !== null && $customCachedAt > 0 && $customCachedAt <= $now) ? $customCachedAt : $now;
    $stateFile = DATA_DIR . '/lscache_state.json';

    if (zeynart_is_sql_active()) {
        $qDec = zeynart_sql_kv_get('lscache_state');
        if ($source === 'render' && is_array($qDec['pages'][$norm] ?? null)) {
            $prevAt = (int)($qDec['pages'][$norm]['cached_at'] ?? 0);
            $prevExp = (int)($qDec['pages'][$norm]['expires_at'] ?? 0);
            if ($prevExp > $now && ($now - $prevAt) < 45) {
                return;
            }
        }
        $pages = (is_array($qDec) && is_array($qDec['pages'] ?? null)) ? $qDec['pages'] : [];
        foreach ($pages as $pk => $pv) {
            if (!is_array($pv) || (int)($pv['expires_at'] ?? 0) <= $now) {
                unset($pages[$pk]);
            }
        }
        $pages[$norm] = [
            'uri' => $norm,
            'tag' => zeynart_lscache_tag_for_uri($norm),
            'cached_at' => $cachedAt,
            'expires_at' => $cachedAt + $ttl,
            'ttl' => $ttl,
            'source' => $source,
        ];
        zeynart_sql_kv_set('lscache_state', [
            'pages' => $pages,
            'updated_at' => $now,
            'last_probe_at' => $qDec['last_probe_at'] ?? null,
            'last_probe_mode' => $qDec['last_probe_mode'] ?? null,
        ], 'cache');
        return;
    }

    if ($source === 'render' && is_file($stateFile)) {
        $quick = @file_get_contents($stateFile);
        $qDec = is_string($quick) && $quick !== '' ? json_decode($quick, true) : null;
        if (is_array($qDec['pages'][$norm] ?? null)) {
            $prevAt = (int)($qDec['pages'][$norm]['cached_at'] ?? 0);
            $prevExp = (int)($qDec['pages'][$norm]['expires_at'] ?? 0);
            if ($prevExp > $now && ($now - $prevAt) < 45) {
                return;
            }
        }
    }

    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
    $fp = @fopen($stateFile, 'c+');
    if ($fp === false) {
        return;
    }
    if (@flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $dec = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        $pages = (is_array($dec) && is_array($dec['pages'] ?? null)) ? $dec['pages'] : [];
        foreach ($pages as $pk => $pv) {
            if (!is_array($pv) || (int)($pv['expires_at'] ?? 0) <= $now) {
                unset($pages[$pk]);
            }
        }
        $pages[$norm] = [
            'uri' => $norm,
            'tag' => zeynart_lscache_tag_for_uri($norm),
            'cached_at' => $cachedAt,
            'expires_at' => $cachedAt + $ttl,
            'ttl' => $ttl,
            'source' => $source,
        ];
        $payload = [
            'pages' => $pages,
            'updated_at' => $now,
            'last_probe_at' => $dec['last_probe_at'] ?? null,
            'last_probe_mode' => $dec['last_probe_mode'] ?? null,
        ];
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fp);
        @flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function zeynart_clear_lscache_state(?string $onlyUri = null): void
{
    if (zeynart_is_sql_active()) {
        $dec = zeynart_sql_kv_get('lscache_state');
        if (!is_array($dec)) {
            return;
        }
        $pages = is_array($dec['pages'] ?? null) ? $dec['pages'] : [];
        if ($onlyUri === null) {
            $pages = [];
        } else {
            $norm = zeynart_normalize_lscache_uri($onlyUri);
            unset($pages[$norm]);
        }
        zeynart_sql_kv_set('lscache_state', [
            'pages' => $pages,
            'updated_at' => time(),
            'last_probe_at' => $dec['last_probe_at'] ?? null,
            'last_probe_mode' => $dec['last_probe_mode'] ?? null,
        ], 'cache');
        return;
    }

    $stateFile = DATA_DIR . '/lscache_state.json';
    if (!is_file($stateFile)) {
        return;
    }
    $fp = @fopen($stateFile, 'c+');
    if ($fp === false) {
        return;
    }
    if (@flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $dec = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        $pages = (is_array($dec) && is_array($dec['pages'] ?? null)) ? $dec['pages'] : [];
        if ($onlyUri === null) {
            $pages = [];
        } else {
            $norm = zeynart_normalize_lscache_uri($onlyUri);
            unset($pages[$norm]);
        }
        $payload = [
            'pages' => $pages,
            'updated_at' => time(),
            'last_probe_at' => $dec['last_probe_at'] ?? null,
            'last_probe_mode' => $dec['last_probe_mode'] ?? null,
        ];
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fp);
        @flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function zeynart_request_lscache_purge(): void
{
    zeynart_clear_lscache_state();
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('X-LiteSpeed-Purge: *');
    } else {
        if (!is_dir(DATA_DIR)) {
            @mkdir(DATA_DIR, 0775, true);
        }
        @file_put_contents(DATA_DIR . '/.lscache_needs_purge', (string)time(), LOCK_EX);
    }
}

function zeynart_sync_lscache_flag(bool $enabled, int $ttl = 3600, bool $emitPurge = false): void
{
    $ttl = max(60, min(604800, $ttl));
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
    $disabledFile = DATA_DIR . '/.lscache_disabled';
    $ttlFile = DATA_DIR . '/.lscache_ttl';
    $wasDisabled = is_file($disabledFile);
    $changed = false;

    if (!$enabled && !$wasDisabled) {
        @file_put_contents($disabledFile, date('c'), LOCK_EX);
        $changed = true;
    } elseif ($enabled && $wasDisabled) {
        @unlink($disabledFile);
        $changed = true;
    }

    $curTtl = is_file($ttlFile) ? (int)trim((string)@file_get_contents($ttlFile)) : 0;
    if ($curTtl !== $ttl) {
        @file_put_contents($ttlFile, (string)$ttl, LOCK_EX);
        $changed = true;
    }

    if (!$enabled || $emitPurge || $changed) {
        zeynart_clear_lscache_state();
    }
    if (($emitPurge || $changed || !$enabled) && PHP_SAPI !== 'cli' && !headers_sent()) {
        header('X-LiteSpeed-Purge: *');
    }
}

function zeynart_sync_maintenance_flag(bool $enabled, bool $emitPurge = false): void
{
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
    $mntFile = DATA_DIR . '/.maintenance_active';
    $wasActive = is_file($mntFile);
    $changed = false;
    if ($enabled && !$wasActive) {
        @file_put_contents($mntFile, date('c'), LOCK_EX);
        $changed = true;
    } elseif (!$enabled && $wasActive) {
        @unlink($mntFile);
        $changed = true;
    }
    if ($changed || $emitPurge) {
        zeynart_clear_lscache_state();
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header('X-LiteSpeed-Purge: *');
        }
    }
}

function save_store(array $store): bool
{
    $store['lscache'] = zeynart_get_lscache_config($store);
    $store['security'] = is_array($store['security'] ?? null) ? $store['security'] : [];
    $store['security']['admin_slug'] = zeynart_get_admin_slug($store);
    if (isset($store['home']) && is_array($store['home'])) {
        $store['home'] = normalize_home_sections($store['home'], $store);
    }
    if (isset($store['blog_categories']) && is_array($store['blog_categories'])) {
        $store['blog_categories'] = zeynart_normalize_blog_categories($store['blog_categories'], null, [], false);
        $store['blog_categories_initialized'] = true;
        $store['blog_settings'] = is_array($store['blog_settings'] ?? null) ? $store['blog_settings'] : [];
        $store['blog_settings']['categories'] = array_merge(
            ['TÜMÜ'],
            array_values(array_map(static fn(array $c): string => (string)$c['name'], $store['blog_categories']))
        );
    }
    $store['menus'] = is_array($store['menus'] ?? null) ? $store['menus'] : [];
    $store['menus']['footer'] = is_array($store['menus']['footer'] ?? null) ? $store['menus']['footer'] : [];
    $store['menus']['footer']['credit_label'] = 'Powered & Designed By Zeynart®';
    $store['menus']['footer']['credit_url'] = 'https://zeynart.com/';
    $storeFile = DATA_DIR . '/store.json';
    $isSilentLoginTouch = !empty($GLOBALS['zeynart_skip_revision_capture']);
    if (!$isSilentLoginTouch) {
        $prevDecoded = zeynart_read_json_data($storeFile);
        if (is_array($prevDecoded)) {
            $cmpPrev = $prevDecoded;
            $cmpNext = $store;
            unset($cmpPrev['updated_at'], $cmpNext['updated_at'], $cmpPrev['admin']['last_login'], $cmpNext['admin']['last_login'], $cmpPrev['smtp_last_status'], $cmpNext['smtp_last_status']);
            if ($cmpPrev !== $cmpNext) {
                zeynart_capture_store_revision($prevDecoded);
            } else {
                unset($GLOBALS['zeynart_next_revision_label']);
            }
        }
    }
    $store['updated_at'] = date('c');
    $ok = atomic_write_json($storeFile, $store, true);
    if ($ok) {
        zeynart_sync_admin_slug_flag((string)$store['security']['admin_slug'], !$isSilentLoginTouch);
        zeynart_sync_lscache_flag((bool)$store['lscache']['enabled'], (int)$store['lscache']['ttl'], !$isSilentLoginTouch);
        zeynart_sync_maintenance_flag(!empty($store['maintenance']['enabled']), false);
        load_store(true);
    }
    return $ok;
}

function load_blog_index(bool $forceReload = false): array
{
    static $cachedIndex = null;
    if ($cachedIndex !== null && !$forceReload) {
        return $cachedIndex;
    }

    if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        $cachedIndex = zeynart_sql_load_blog_index($forceReload);
        return $cachedIndex;
    }

    $idxFile = DATA_DIR . '/blog_index.json';
    if (!is_file($idxFile)) {
        $cachedIndex = [];
        return [];
    }
    $decoded = json_decode((string)file_get_contents($idxFile), true);
    $cachedIndex = is_array($decoded) ? $decoded : [];
    return $cachedIndex;
}

function save_blog_index(array $index): bool
{
    $ok = atomic_write_json(DATA_DIR . '/blog_index.json', array_values($index), false);
    if ($ok) {
        zeynart_request_lscache_purge();
        load_blog_index(true);
    }
    return $ok;
}

function load_blog_slug_map(bool $forceReload = false): array
{
    static $cachedMap = null;
    if ($cachedMap !== null && !$forceReload) {
        return $cachedMap;
    }
    if ($forceReload && function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        unset($GLOBALS['__zeynart_sql_kv_mem']['blog_slug_map']);
    }
    $mapFile = DATA_DIR . '/blog_slug_map.json';
    $decoded = zeynart_read_json_data($mapFile);
    $cachedMap = is_array($decoded) ? array_merge(['en' => [], 'de' => [], 'ru' => []], $decoded) : ['en' => [], 'de' => [], 'ru' => []];
    return $cachedMap;
}

function save_blog_slug_map(array $map): bool
{
    $ok = atomic_write_json(DATA_DIR . '/blog_slug_map.json', $map, false);
    if ($ok) {
        load_blog_slug_map(true);
    }
    return $ok;
}

if (!function_exists('is_post_publicly_visible')) {
    function is_post_publicly_visible(array $post, bool $includeDrafts = false): bool
    {
        $st = strtolower(trim((string)($post['status'] ?? 'published')));
        if ($st === 'published') {
            return true;
        }
        if ($includeDrafts && in_array($st, ['draft', 'scheduled'], true)) {
            return true;
        }
        return false;
    }
}

function zeynart_default_blog_category_seeds(): array
{
    return [
        'web-tasarim' => [
            'id' => 'cat_web_tasarim',
            'name' => 'Web Tasarım',
            'slug' => 'web-tasarim',
            'parent_id' => '',
            'description' => 'Kurumsal web sitesi mimarisi, yüksek dönüşümlü UI/UX prensipleri, %100 PageSpeed performans mühendisliği ve modern web tasarım rehberleri.',
            'meta_title' => 'Web Tasarım & UI/UX Rehberleri — Zeynart Journal | ZEYNART',
            'meta_desc' => 'Kurumsal web tasarım trendleri, hız optimizasyonu, dönüşüm odaklı arayüz tasarımı ve SEO uyumlu web mimarisi üzerine Zeynart rehberleri.',
            'is_default' => false,
        ],
        'grafik-tasarim' => [
            'id' => 'cat_grafik_tasarim',
            'name' => 'Grafik Tasarım',
            'slug' => 'grafik-tasarim',
            'parent_id' => '',
            'description' => 'Kurumsal kimlik tasarımı, marka dili, tipografi, görsel iletişim sistemleri ve dijital marka konumlandırma stratejileri.',
            'meta_title' => 'Grafik Tasarım & Marka Kimliği Yazıları | ZEYNART',
            'meta_desc' => 'Kurumsal kimlik oluşturma, logo ve tipografi sistemleri, görsel iletişim ilkeleri ve marka algısını güçlendiren tasarım rehberleri.',
            'is_default' => false,
        ],
        'e-ticaret' => [
            'id' => 'cat_e_ticaret',
            'name' => 'E-Ticaret',
            'slug' => 'e-ticaret',
            'parent_id' => '',
            'description' => 'E-ticaret altyapıları, ödeme sistemleri entegrasyonu, sepet dönüşüm optimizasyonu (CRO) ve dijital mağaza büyüme stratejileri.',
            'meta_title' => 'E-Ticaret Altyapı & Dönüşüm Rehberleri | ZEYNART',
            'meta_desc' => 'Yüksek satış dönüşümlü e-ticaret sitesi kurulumu, ödeme entegrasyonları, kullanıcı deneyimi ve dijital ticaret stratejileri.',
            'is_default' => false,
        ],
        'hosting-altyapi' => [
            'id' => 'cat_hosting_altyapi',
            'name' => 'Hosting & Altyapı',
            'slug' => 'hosting-altyapi',
            'parent_id' => '',
            'description' => 'LiteSpeed sunucu mimarisi, bulut barındırma, SSL/TLS güvenliği, kurumsal e-posta sürekliliği ve yüksek erişilebilirlik çözümleri.',
            'meta_title' => 'Hosting, Sunucu & Web Altyapı Rehberleri | ZEYNART',
            'meta_desc' => 'Kurumsal hosting seçimi, LiteSpeed önbellekleme, siber güvenlik, DNS yönetimi ve kesintisiz sunucu mimarisi üzerine teknik yazılar.',
            'is_default' => false,
        ],
        'urun-stratejisi' => [
            'id' => 'cat_urun_stratejisi',
            'name' => 'Ürün Stratejisi',
            'slug' => 'urun-stratejisi',
            'parent_id' => '',
            'description' => 'Dijital ürün geliştirme, MVP planlama, büyüme metrikleri, B2B/B2C müşteri yolculuğu ve sürdürülebilir yazılım yol haritaları.',
            'meta_title' => 'Dijital Ürün Stratejisi & Büyüme Yazıları | ZEYNART',
            'meta_desc' => 'Dijital ürün yönetimi, doğru teknoloji seçimi, kullanıcı araştırması ve ölçeklenebilir iş modelleri üzerine stratejik analizler.',
            'is_default' => false,
        ],
        'yapay-zeka' => [
            'id' => 'cat_yapay_zeka',
            'name' => 'Yapay Zeka',
            'slug' => 'yapay-zeka',
            'parent_id' => '',
            'description' => 'Üretken yapay zeka iş akışları, GEO (Yapay Zeka Arama Motoru Optimizasyonu), kurumsal otomasyon ve yeni nesil web teknolojileri.',
            'meta_title' => 'Yapay Zeka, GEO & Dijital Otomasyon Yazıları | ZEYNART',
            'meta_desc' => 'İş süreçlerinde yapay zeka kullanımı, AI destekli SEO (GEO), akıllı otomasyon sistemleri ve geleceğin web standartları.',
            'is_default' => false,
        ],
        'tasarim-teknoloji' => [
            'id' => 'cat_tasarim_teknoloji',
            'name' => 'Tasarım & Teknoloji',
            'slug' => 'tasarim-teknoloji',
            'parent_id' => '',
            'description' => 'Estetik tasarım ile modern yazılım mühendisliğinin kesişim noktasındaki trendler, performans standartları ve dijital kültür notları.',
            'meta_title' => 'Tasarım & Teknoloji Makaleleri — Zeynart Journal | ZEYNART',
            'meta_desc' => 'Tasarım sistemleri, modern web standartları, ön yüz mühendisliği ve dijital dünyadaki yenilikler üzerine Zeynart Journal seçkisi.',
            'is_default' => false,
        ],
        'genel' => [
            'id' => 'cat_genel',
            'name' => 'Genel',
            'slug' => 'genel',
            'parent_id' => '',
            'description' => 'Dijital dönüşüm, kurumsal web çözümleri, SEO, sosyal medya yönetimi ve iş dünyasına yön veren kapsamlı teknoloji rehberleri.',
            'meta_title' => 'Genel Dijital Rehberler & Teknoloji Yazıları | ZEYNART',
            'meta_desc' => 'Web tasarım, SEO, dijital pazarlama, kurumsal yazılım ve teknoloji dünyasına dair kapsamlı rehberler ve güncel içerikler.',
            'is_default' => true,
        ],
        'web' => [
            'id' => 'cat_web',
            'name' => 'Web',
            'slug' => 'web',
            'parent_id' => '',
            'description' => 'Web standartları, tarayıcı teknolojileri, alan adı yönetimi ve modern internet ekosistemi üzerine pratik bilgiler.',
            'meta_title' => 'Web Teknolojileri & İnternet Rehberleri | ZEYNART',
            'meta_desc' => 'Modern web teknolojileri, alan adı ve protokol standartları, web güvenliği ve performans ipuçları.',
            'is_default' => false,
        ],
        'blog' => [
            'id' => 'cat_blog',
            'name' => 'Blog',
            'slug' => 'blog',
            'parent_id' => '',
            'description' => 'İçerik stratejisi, kurumsal blog yayıncılığı, organik trafik büyütme ve editoryal SEO pratikleri.',
            'meta_title' => 'Kurumsal Blog & İçerik Stratejisi Yazıları | ZEYNART',
            'meta_desc' => 'Kurumsal blog yönetimi, editoryal içerik üretimi ve arama motoru görünürlüğünü artıran yayın stratejileri.',
            'is_default' => false,
        ],
    ];
}

function zeynart_is_ignored_all_category_label(string $name): bool
{
    $fold = mb_strtolower(trim($name), 'UTF-8');
    return in_array($fold, ['', 'tümü', 'tumu', 'tüm yazılar', 'tum yazilar', 'all', 'alle', 'все'], true);
}

function zeynart_normalize_blog_categories(
    array $rawCategories,
    ?array $blogIndex = null,
    array $legacySettingCats = [],
    bool $seedFromPosts = false
): array {
    $seeds = zeynart_default_blog_category_seeds();
    $list = [];
    $usedIds = [];
    $usedSlugs = [];

    $reserveSlug = static function (string $desiredSlug, string $fallbackName) use (&$usedSlugs): string {
        $base = slugify($desiredSlug !== '' ? $desiredSlug : $fallbackName);
        if ($base === '' || $base === 'kategori' || $base === 'category') {
            $base = 'genel-kategori';
        }
        $cand = $base;
        $n = 2;
        while (isset($usedSlugs[$cand])) {
            $cand = $base . '-' . $n;
            $n++;
        }
        $usedSlugs[$cand] = true;
        return $cand;
    };

    $reserveId = static function (string $desiredId, string $slug) use (&$usedIds): string {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($desiredId)) ?? '';
        if ($clean === '') {
            $clean = 'cat_' . str_replace('-', '_', $slug);
        }
        $cand = $clean;
        $n = 2;
        while (isset($usedIds[$cand])) {
            $cand = $clean . '_' . $n;
            $n++;
        }
        $usedIds[$cand] = true;
        return $cand;
    };

    foreach ($rawCategories as $idx => $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string)($item['name'] ?? ''));
        if (zeynart_is_ignored_all_category_label($name)) {
            continue;
        }
        $slug = $reserveSlug(trim((string)($item['slug'] ?? '')), $name);
        $seedMeta = $seeds[$slug] ?? null;
        $id = $reserveId((string)($item['id'] ?? ($seedMeta['id'] ?? '')), $slug);
        $list[] = [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'parent_id' => trim((string)($item['parent_id'] ?? '')),
            'description' => trim((string)($item['description'] ?? ($seedFromPosts && $seedMeta ? $seedMeta['description'] : ''))),
            'meta_title' => trim((string)($item['meta_title'] ?? ($seedFromPosts && $seedMeta ? $seedMeta['meta_title'] : ''))),
            'meta_desc' => trim((string)($item['meta_desc'] ?? ($seedFromPosts && $seedMeta ? $seedMeta['meta_desc'] : ''))),
            'order' => isset($item['order']) && is_numeric($item['order']) ? (int)$item['order'] : ($idx + 1),
            'is_default' => !empty($item['is_default']),
        ];
    }

    if ($seedFromPosts) {
        $discoveredNames = [];
        foreach ($seeds as $sd) {
            $discoveredNames[] = $sd['name'];
        }
        foreach ($legacySettingCats as $lc) {
            if (is_string($lc) && !zeynart_is_ignored_all_category_label($lc)) {
                $discoveredNames[] = trim($lc);
            }
        }
        if (is_array($blogIndex)) {
            foreach ($blogIndex as $pRow) {
                if (!is_array($pRow)) {
                    continue;
                }
                if (!empty($pRow['categories']) && is_array($pRow['categories'])) {
                    foreach ($pRow['categories'] as $cn) {
                        if (is_string($cn) && !zeynart_is_ignored_all_category_label($cn)) {
                            $discoveredNames[] = trim($cn);
                        }
                    }
                }
                $cSingle = trim((string)($pRow['category'] ?? ''));
                if (!zeynart_is_ignored_all_category_label($cSingle)) {
                    $discoveredNames[] = $cSingle;
                }
            }
        }

        foreach ($discoveredNames as $candName) {
            $candSlug = slugify($candName);
            if ($candSlug === '' || isset($usedSlugs[$candSlug])) {
                continue;
            }
            $seedMeta = $seeds[$candSlug] ?? null;
            $finalName = $seedMeta !== null ? $seedMeta['name'] : $candName;
            $slug = $reserveSlug($candSlug, $finalName);
            $id = $reserveId((string)($seedMeta['id'] ?? ''), $slug);
            $list[] = [
                'id' => $id,
                'name' => $finalName,
                'slug' => $slug,
                'parent_id' => '',
                'description' => (string)($seedMeta['description'] ?? ''),
                'meta_title' => (string)($seedMeta['meta_title'] ?? ''),
                'meta_desc' => (string)($seedMeta['meta_desc'] ?? ''),
                'order' => count($list) + 1,
                'is_default' => !empty($seedMeta['is_default']),
            ];
        }
    }

    if ($list === []) {
        $defSeed = $seeds['genel'];
        $list[] = [
            'id' => $defSeed['id'],
            'name' => $defSeed['name'],
            'slug' => $defSeed['slug'],
            'parent_id' => '',
            'description' => $defSeed['description'],
            'meta_title' => $defSeed['meta_title'],
            'meta_desc' => $defSeed['meta_desc'],
            'order' => 1,
            'is_default' => true,
        ];
    }

    // Sort by explicit order then preserve insertion sequence
    $indexed = [];
    foreach ($list as $i => $row) {
        $row['_idx'] = $i;
        $indexed[] = $row;
    }
    usort($indexed, static fn(array $a, array $b): int => ($a['order'] <=> $b['order']) ?: ($a['_idx'] <=> $b['_idx']));

    $byId = [];
    foreach ($indexed as $i => &$row) {
        unset($row['_idx']);
        $row['order'] = $i + 1;
        $byId[$row['id']] = $i;
    }
    unset($row);

    // Validate parent_id (must exist, cannot be self, cannot form cycle)
    foreach ($indexed as $i => &$row) {
        $pid = $row['parent_id'];
        if ($pid === '' || !isset($byId[$pid]) || $pid === $row['id']) {
            $row['parent_id'] = '';
            continue;
        }
        $visited = [$row['id'] => true];
        $cur = $pid;
        $hasCycle = false;
        while ($cur !== '') {
            if (isset($visited[$cur])) {
                $hasCycle = true;
                break;
            }
            $visited[$cur] = true;
            if (!isset($byId[$cur])) {
                break;
            }
            $cur = (string)$indexed[$byId[$cur]]['parent_id'];
        }
        if ($hasCycle) {
            $row['parent_id'] = '';
        }
    }
    unset($row);

    // Ensure exactly ONE category has is_default = true
    $defaultIdx = null;
    foreach ($indexed as $i => $row) {
        if (!empty($row['is_default'])) {
            $defaultIdx = $i;
            break;
        }
    }
    if ($defaultIdx === null) {
        foreach ($indexed as $i => $row) {
            if ($row['slug'] === 'genel') {
                $defaultIdx = $i;
                break;
            }
        }
    }
    if ($defaultIdx === null) {
        $defaultIdx = 0;
    }
    foreach ($indexed as $i => &$row) {
        $row['is_default'] = ($i === $defaultIdx);
    }
    unset($row);

    return array_values($indexed);
}

function zeynart_get_blog_categories(?array $store = null): array
{
    $st = $store ?? load_store();
    if (isset($st['blog_categories']) && is_array($st['blog_categories']) && $st['blog_categories'] !== []) {
        return zeynart_normalize_blog_categories($st['blog_categories'], null, [], false);
    }
    return zeynart_normalize_blog_categories(
        [],
        load_blog_index(),
        is_array($st['blog_settings']['categories'] ?? null) ? $st['blog_settings']['categories'] : [],
        true
    );
}

function zeynart_get_default_blog_category(?array $store = null): array
{
    $cats = zeynart_get_blog_categories($store);
    foreach ($cats as $c) {
        if (!empty($c['is_default'])) {
            return $c;
        }
    }
    return $cats[0];
}

function zeynart_build_blog_category_tree(array $categories): array
{
    $normalized = zeynart_normalize_blog_categories($categories, null, [], false);
    $byId = [];
    $childrenMap = ['' => []];
    foreach ($normalized as $c) {
        $byId[$c['id']] = $c;
        $childrenMap[$c['id']] = [];
    }
    foreach ($normalized as $c) {
        $pid = ($c['parent_id'] !== '' && isset($byId[$c['parent_id']])) ? $c['parent_id'] : '';
        $childrenMap[$pid][] = $c['id'];
    }

    $collectDescendants = static function (string $catId, array &$visited = []) use (&$collectDescendants, $childrenMap, $byId): array {
        $ids = [];
        $slugs = [];
        foreach (($childrenMap[$catId] ?? []) as $childId) {
            if (isset($visited[$childId]) || !isset($byId[$childId])) {
                continue;
            }
            $visited[$childId] = true;
            $ids[] = $childId;
            $slugs[] = $byId[$childId]['slug'];
            $sub = $collectDescendants($childId, $visited);
            $ids = array_merge($ids, $sub['ids']);
            $slugs = array_merge($slugs, $sub['slugs']);
        }
        return ['ids' => $ids, 'slugs' => $slugs];
    };

    $flat = [];
    $visitedDfs = [];
    $walk = static function (string $parentId, int $depth, array $ancestors) use (
        &$walk, &$flat, &$visitedDfs, $childrenMap, $byId, $collectDescendants
    ): void {
        foreach (($childrenMap[$parentId] ?? []) as $cid) {
            if (isset($visitedDfs[$cid]) || !isset($byId[$cid])) {
                continue;
            }
            $visitedDfs[$cid] = true;
            $node = $byId[$cid];
            $parentNode = ($parentId !== '' && isset($byId[$parentId])) ? $byId[$parentId] : null;
            $visSub = [$cid => true];
            $desc = $collectDescendants($cid, $visSub);
            $node['depth'] = $depth;
            $node['indent_prefix'] = $depth > 0 ? str_repeat('— ', $depth) : '';
            $node['parent_name'] = $parentNode !== null ? (string)$parentNode['name'] : '';
            $node['parent_slug'] = $parentNode !== null ? (string)$parentNode['slug'] : '';
            $node['ancestor_ids'] = array_values(array_map(static fn(array $a): string => (string)$a['id'], $ancestors));
            $node['ancestor_breadcrumbs'] = $ancestors;
            $node['descendant_ids'] = $desc['ids'];
            $node['descendant_slugs'] = $desc['slugs'];
            $flat[] = $node;

            $nextAncestors = array_merge($ancestors, [[
                'id' => $node['id'],
                'name' => $node['name'],
                'slug' => $node['slug'],
            ]]);
            $walk($cid, $depth + 1, $nextAncestors);
        }
    };

    $walk('', 0, []);
    foreach ($normalized as $c) {
        if (!isset($visitedDfs[$c['id']])) {
            $c['depth'] = 0;
            $c['indent_prefix'] = '';
            $c['parent_name'] = '';
            $c['parent_slug'] = '';
            $c['ancestor_ids'] = [];
            $c['ancestor_breadcrumbs'] = [];
            $c['descendant_ids'] = [];
            $c['descendant_slugs'] = [];
            $flat[] = $c;
        }
    }
    return $flat;
}

function zeynart_find_blog_category_by_slug(string $slug, ?array $store = null): ?array
{
    $want = slugify($slug);
    if ($want === '') {
        return null;
    }
    $tree = zeynart_build_blog_category_tree(zeynart_get_blog_categories($store));
    foreach ($tree as $cat) {
        if (strcasecmp((string)$cat['slug'], $want) === 0) {
            return $cat;
        }
    }
    return null;
}

function zeynart_resolve_post_categories(array $post, ?array $categories = null): array
{
    $cats = $categories !== null ? $categories : zeynart_get_blog_categories();
    if ($cats === []) {
        $cats = zeynart_normalize_blog_categories([], null, [], false);
    }

    $byId = [];
    $bySlug = [];
    $defaultCat = $cats[0];
    foreach ($cats as $c) {
        $byId[$c['id']] = $c;
        $bySlug[$c['slug']] = $c;
        $nameSlug = slugify((string)$c['name']);
        if ($nameSlug !== '' && !isset($bySlug[$nameSlug])) {
            $bySlug[$nameSlug] = $c;
        }
        if (!empty($c['is_default'])) {
            $defaultCat = $c;
        }
    }

    $resolved = [];
    $seenIds = [];
    $pushCat = static function (?array $cand) use (&$resolved, &$seenIds): void {
        if ($cand !== null && !isset($seenIds[$cand['id']])) {
            $seenIds[$cand['id']] = true;
            $resolved[] = $cand;
        }
    };

    if (!empty($post['category_ids']) && is_array($post['category_ids'])) {
        foreach ($post['category_ids'] as $cid) {
            $cidStr = trim((string)$cid);
            if ($cidStr !== '' && isset($byId[$cidStr])) {
                $pushCat($byId[$cidStr]);
            }
        }
    }
    if ($resolved === [] && !empty($post['category_slugs']) && is_array($post['category_slugs'])) {
        foreach ($post['category_slugs'] as $cSlug) {
            $sKey = slugify((string)$cSlug);
            if ($sKey !== '' && isset($bySlug[$sKey])) {
                $pushCat($bySlug[$sKey]);
            }
        }
    }
    if ($resolved === [] && !empty($post['categories']) && is_array($post['categories'])) {
        foreach ($post['categories'] as $cName) {
            $cStr = trim((string)$cName);
            if ($cStr === '') {
                continue;
            }
            if (isset($byId[$cStr])) {
                $pushCat($byId[$cStr]);
            } else {
                $sKey = slugify($cStr);
                if ($sKey !== '' && isset($bySlug[$sKey])) {
                    $pushCat($bySlug[$sKey]);
                }
            }
        }
    }
    if ($resolved === []) {
        $legacySingle = trim((string)($post['category'] ?? ''));
        if ($legacySingle !== '' && !zeynart_is_ignored_all_category_label($legacySingle)) {
            if (isset($byId[$legacySingle])) {
                $pushCat($byId[$legacySingle]);
            } else {
                $sKey = slugify($legacySingle);
                if ($sKey !== '' && isset($bySlug[$sKey])) {
                    $pushCat($bySlug[$sKey]);
                }
            }
        }
    }

    if ($resolved === []) {
        $resolved[] = $defaultCat;
    }

    return $resolved;
}

function zeynart_enrich_post_category_fields(array $post, ?array $categories = null): array
{
    $cats = $categories !== null ? $categories : zeynart_get_blog_categories();
    $resolved = zeynart_resolve_post_categories($post, $cats);
    $post['category_ids'] = array_values(array_map(static fn(array $c): string => (string)$c['id'], $resolved));
    $post['categories'] = array_values(array_map(static fn(array $c): string => (string)$c['name'], $resolved));
    $post['category_slugs'] = array_values(array_map(static fn(array $c): string => (string)$c['slug'], $resolved));
    $post['category'] = (string)$resolved[0]['name'];
    return $post;
}

function zeynart_post_matches_category(array $post, array $targetCategory, ?array $allCategories = null): bool
{
    $cats = $allCategories !== null ? $allCategories : zeynart_get_blog_categories();
    $resolved = zeynart_resolve_post_categories($post, $cats);
    $allowedIds = [$targetCategory['id'] => true];
    foreach (($targetCategory['descendant_ids'] ?? []) as $did) {
        $allowedIds[(string)$did] = true;
    }
    foreach ($resolved as $rc) {
        if (isset($allowedIds[$rc['id']])) {
            return true;
        }
    }
    return false;
}

function zeynart_count_posts_per_category(array $categories, ?array $blogIndex = null, bool $publicOnly = false): array
{
    $tree = zeynart_build_blog_category_tree($categories);
    $index = $blogIndex !== null ? $blogIndex : load_blog_index();
    $directSets = [];
    foreach ($tree as $c) {
        $directSets[$c['id']] = [];
    }

    foreach ($index as $idx => $item) {
        if (!is_array($item)) {
            continue;
        }
        if ($publicOnly && !is_post_publicly_visible($item, false)) {
            continue;
        }
        $pKey = trim((string)($item['slug'] ?? '')) ?: ('row_' . $idx);
        $resolved = zeynart_resolve_post_categories($item, $tree);
        foreach ($resolved as $rc) {
            if (isset($directSets[$rc['id']])) {
                $directSets[$rc['id']][$pKey] = true;
            }
        }
    }

    $counts = [];
    foreach ($tree as $c) {
        $cid = $c['id'];
        $merged = $directSets[$cid] ?? [];
        foreach (($c['descendant_ids'] ?? []) as $did) {
            foreach (($directSets[$did] ?? []) as $pk => $_) {
                $merged[$pk] = true;
            }
        }
        $counts[$cid] = [
            'direct' => count($directSets[$cid] ?? []),
            'total' => count($merged),
        ];
    }
    return $counts;
}

function zeynart_append_loop_free_redirect(array &$store, string $fromPath, string $toPath, int $code = 301): bool
{
    $oldTrim = trim($fromPath, '/');
    $newTrim = trim($toPath, '/');
    if ($oldTrim === '' || $newTrim === '' || strcasecmp($oldTrim, $newTrim) === 0) {
        return false;
    }
    $oldNorm = '/' . $oldTrim . '/';
    $newNorm = '/' . $newTrim . '/';

    $existingRedirects = is_array($store['redirects'] ?? null) ? $store['redirects'] : [];
    $cleanRedirects = [];
    $hasOldRedirect = false;
    foreach ($existingRedirects as $red) {
        if (!is_array($red)) {
            continue;
        }
        $rFrom = '/' . trim((string)($red['from'] ?? ''), '/');
        $rTo = '/' . trim((string)($red['to'] ?? ''), '/') . '/';
        if ($rFrom === '/') {
            continue;
        }
        if (strcasecmp($rFrom . '/', $newNorm) === 0) {
            continue;
        }
        if (strcasecmp($rTo, $oldNorm) === 0) {
            $red['to'] = $newNorm;
            $rTo = $newNorm;
        }
        if (strcasecmp($rFrom . '/', $rTo) === 0) {
            continue;
        }
        if (strcasecmp($rFrom . '/', $oldNorm) === 0) {
            $red['to'] = $newNorm;
            $red['code'] = $code;
            $hasOldRedirect = true;
        }
        $cleanRedirects[] = $red;
    }
    if (!$hasOldRedirect) {
        $cleanRedirects[] = [
            'id' => 'red-' . substr(bin2hex(random_bytes(4)), 0, 8),
            'from' => '/' . $oldTrim,
            'to' => $newNorm,
            'code' => $code,
            'hits' => 0,
        ];
    }
    $store['redirects'] = array_values($cleanRedirects);
    return true;
}

function zeynart_propagate_blog_category_changes(array &$store, array $oldCategories, array $newCategories): array
{
    $oldNorm = zeynart_normalize_blog_categories($oldCategories, null, [], false);
    $newNorm = zeynart_normalize_blog_categories($newCategories, null, [], false);

    $oldById = [];
    foreach ($oldNorm as $oc) {
        $oldById[$oc['id']] = $oc;
    }
    $newById = [];
    $defaultCat = $newNorm[0];
    foreach ($newNorm as $nc) {
        $newById[$nc['id']] = $nc;
        if (!empty($nc['is_default'])) {
            $defaultCat = $nc;
        }
    }

    $blogSlug = zeynart_page_slug($store, 'blog') ?: 'blog';
    $redirectsAdded = 0;

    foreach ($newById as $cid => $nc) {
        if (!isset($oldById[$cid])) {
            continue;
        }
        $oc = $oldById[$cid];
        if ($oc['slug'] !== $nc['slug']) {
            $oldTrUrl = '/' . $blogSlug . '/kategori/' . $oc['slug'] . '/';
            $newTrUrl = '/' . $blogSlug . '/kategori/' . $nc['slug'] . '/';
            if (function_exists('zeynart_register_url_rename')) {
                zeynart_register_url_rename($store, $oldTrUrl, $newTrUrl);
            } else {
                zeynart_append_loop_free_redirect($store, $oldTrUrl, $newTrUrl, 301);
            }
            foreach (['en', 'de', 'ru'] as $lCode) {
                zeynart_append_loop_free_redirect(
                    $store,
                    '/' . $lCode . '/' . $blogSlug . '/kategori/' . $oc['slug'] . '/',
                    '/' . $lCode . '/' . $blogSlug . '/kategori/' . $nc['slug'] . '/',
                    301
                );
            }
            $redirectsAdded++;
        }
    }

    // Update blog_index.json & affected post files so renamed/re-slugged/deleted categories stay 100% consistent without ever deleting posts
    $index = load_blog_index(true);
    $indexChanged = false;
    $updatedPosts = 0;

    foreach ($index as $k => $summaryRow) {
        if (!is_array($summaryRow)) {
            continue;
        }
        $prevResolved = zeynart_resolve_post_categories($summaryRow, $oldNorm);
        $prevIds = array_values(array_map(static fn(array $c): string => (string)$c['id'], $prevResolved));
        $prevNames = array_values(array_map(static fn(array $c): string => (string)$c['name'], $prevResolved));
        $prevSlugs = array_values(array_map(static fn(array $c): string => (string)$c['slug'], $prevResolved));

        $nextMatched = [];
        $seenNew = [];
        foreach ($prevResolved as $prevCat) {
            $pid = $prevCat['id'];
            if (isset($newById[$pid]) && !isset($seenNew[$pid])) {
                $seenNew[$pid] = true;
                $nextMatched[] = $newById[$pid];
            }
        }
        if ($nextMatched === []) {
            $nextMatched[] = $defaultCat;
        }

        $nextIds = array_values(array_map(static fn(array $c): string => (string)$c['id'], $nextMatched));
        $nextNames = array_values(array_map(static fn(array $c): string => (string)$c['name'], $nextMatched));
        $nextSlugs = array_values(array_map(static fn(array $c): string => (string)$c['slug'], $nextMatched));
        $nextPrimary = $nextNames[0];

        $postSemanticCatChanged = (
            $prevIds !== $nextIds
            || $prevNames !== $nextNames
            || $prevSlugs !== $nextSlugs
        );

        $needsRowSync = (
            $postSemanticCatChanged
            || ($summaryRow['category'] ?? '') !== $nextPrimary
            || ($summaryRow['category_ids'] ?? null) !== $nextIds
            || ($summaryRow['categories'] ?? null) !== $nextNames
            || ($summaryRow['category_slugs'] ?? null) !== $nextSlugs
        );

        if ($needsRowSync) {
            $index[$k]['category'] = $nextPrimary;
            $index[$k]['category_ids'] = $nextIds;
            $index[$k]['categories'] = $nextNames;
            $index[$k]['category_slugs'] = $nextSlugs;
            foreach (['en', 'de', 'ru'] as $lCode) {
                if (isset($index[$k]['i18n'][$lCode]) && is_array($index[$k]['i18n'][$lCode])) {
                    $index[$k]['i18n'][$lCode]['category'] = t_category($nextPrimary, $lCode);
                    $index[$k]['i18n'][$lCode]['categories'] = array_values(array_map(static fn(string $nm): string => t_category($nm, $lCode), $nextNames));
                }
            }
            $indexChanged = true;

            if ($postSemanticCatChanged) {
                $updatedPosts++;
                $postSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($summaryRow['slug'] ?? '')) ?? '';
                if ($postSlug !== '') {
                    $fullPost = get_post($postSlug);
                    if (is_array($fullPost)) {
                        $fullPost['category'] = $nextPrimary;
                        $fullPost['category_ids'] = $nextIds;
                        $fullPost['categories'] = $nextNames;
                        $fullPost['category_slugs'] = $nextSlugs;
                        foreach (['en', 'de', 'ru'] as $lCode) {
                            if (isset($fullPost['translations'][$lCode]) && is_array($fullPost['translations'][$lCode]) && empty($fullPost['translations'][$lCode]['manual_lock'])) {
                                $fullPost['translations'][$lCode]['category'] = t_category($nextPrimary, $lCode);
                            }
                        }
                        atomic_write_json(POSTS_DIR . '/' . $postSlug . '.json', $fullPost, false);
                    }
                }
            }
        }
    }

    if ($indexChanged) {
        save_blog_index($index);
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header('X-LiteSpeed-Purge: *');
        }
    }

    return [
        'updated_posts' => $updatedPosts,
        'redirects_added' => $redirectsAdded,
    ];
}

function post_exists(string $slug): bool
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
    if ($safeSlug === '') {
        return false;
    }
    if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        return zeynart_sql_post_exists($safeSlug);
    }
    return is_file(POSTS_DIR . '/' . $safeSlug . '.json');
}

function get_post(string $slug): ?array
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
    if ($safeSlug === '') {
        return null;
    }
    if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        return zeynart_sql_get_post($safeSlug);
    }
    $file = POSTS_DIR . '/' . $safeSlug . '.json';
    if (!is_file($file)) {
        return null;
    }
    $decoded = json_decode((string)file_get_contents($file), true);
    return is_array($decoded) ? $decoded : null;
}

if (!function_exists('load_post')) {
    function load_post(string $slug): ?array
    {
        return get_post($slug);
    }
}

function find_post_by_localized_slug(string $slug, string $lang = 'tr'): ?array
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
    if ($safeSlug === '') {
        return null;
    }
    $lang = strtolower(trim($lang));
    if ($lang !== 'tr' && in_array($lang, ['en', 'de', 'ru'], true)) {
        if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
            $sqlLoc = zeynart_sql_find_post_by_localized_slug($safeSlug, $lang);
            if ($sqlLoc !== null) {
                return $sqlLoc;
            }
        }
        $map = load_blog_slug_map();
        $mappedBase = $map[$lang][$safeSlug] ?? null;
        if (is_string($mappedBase) && $mappedBase !== '') {
            $post = get_post($mappedBase);
            if ($post !== null) {
                return $post;
            }
        }
        // Fallback: scan blog_index i18n entries if map wasn't warmed yet
        foreach (load_blog_index() as $entry) {
            if (($entry['i18n'][$lang]['slug'] ?? '') === $safeSlug && !empty($entry['slug'])) {
                $post = get_post((string)$entry['slug']);
                if ($post !== null) {
                    return $post;
                }
            }
        }
    }
    // Also support accessing via base Turkish slug directly (e.g. /en/blog/{tr-slug}/)
    return get_post($safeSlug);
}

/**
 * Returns a lowercase lookup map ['/blog/slug' => true, '/en/blog/en-slug' => true, ...]
 * for all published (or all existing when $publishedOnly = false) blog posts.
 */
function zeynart_collect_blog_paths(array $store, ?array $blogIndex = null, bool $publishedOnly = true): array
{
    $resolved = zeynart_resolved_page_slugs($store);
    $blogBase = strtolower((string)($resolved['blog'] ?? 'blog'));
    $bases = $blogBase !== 'blog' ? [$blogBase, 'blog'] : ['blog'];
    $idx = $blogIndex ?? load_blog_index();
    $paths = [];

    foreach ($idx as $entry) {
        if (!is_array($entry) || empty($entry['slug'])) {
            continue;
        }
        $st = strtolower(trim((string)($entry['status'] ?? 'published')));
        if ($publishedOnly && $st !== 'published') {
            continue;
        }
        $sLower = strtolower(trim((string)$entry['slug'], '/'));
        if ($sLower === '') {
            continue;
        }
        foreach ($bases as $bb) {
            $paths['/' . $bb . '/' . $sLower] = true;
            foreach (['en', 'de', 'ru'] as $lc) {
                $paths['/' . $lc . '/' . $bb . '/' . $sLower] = true;
                $locSlug = strtolower(trim((string)($entry['i18n'][$lc]['slug'] ?? ''), '/'));
                if ($locSlug !== '') {
                    $paths['/' . $lc . '/' . $bb . '/' . $locSlug] = true;
                }
            }
        }
    }
    return $paths;
}

/**
 * Removes any stale redirect rules whose `from` path matches an actively published blog article URL.
 * Returns the number of conflicting redirect rules removed.
 */
function zeynart_prune_blog_conflicts_from_redirects(array &$store, ?array $blogIndex = null): int
{
    if (empty($store['redirects']) || !is_array($store['redirects'])) {
        return 0;
    }
    $livePaths = zeynart_collect_blog_paths($store, $blogIndex, true);
    if ($livePaths === []) {
        return 0;
    }
    $kept = [];
    $removed = 0;
    foreach ($store['redirects'] as $red) {
        if (!is_array($red)) {
            continue;
        }
        $fromNorm = '/' . strtolower(trim((string)($red['from'] ?? ''), '/'));
        if ($fromNorm !== '/' && isset($livePaths[$fromNorm])) {
            $removed++;
            continue;
        }
        $kept[] = $red;
    }
    if ($removed > 0) {
        $store['redirects'] = array_values($kept);
    }
    return $removed;
}

/**
 * Removes 404 log entries that correspond to any existing (published or queued draft) blog article.
 */
function zeynart_prune_existing_posts_from_404_logs(array $store, ?array $blogIndex = null): int
{
    if (!function_exists('zeynart_404_log_file')) {
        return 0;
    }
    $f = zeynart_404_log_file();
    $dec = zeynart_read_json_data($f);
    if (!is_array($dec) || $dec === []) {
        return 0;
    }
    $knownPaths = zeynart_collect_blog_paths($store, $blogIndex, false);
    if ($knownPaths === []) {
        return 0;
    }
    $kept = [];
    $removed = 0;
    foreach ($dec as $it) {
        if (!is_array($it)) {
            continue;
        }
        $pNorm = '/' . strtolower(trim((string)($it['path'] ?? ''), '/'));
        if ($pNorm !== '/' && isset($knownPaths[$pNorm])) {
            $removed++;
            continue;
        }
        $kept[] = $it;
    }
    if ($removed > 0) {
        atomic_write_json($f, array_values($kept), false);
    }
    return $removed;
}

/**
 * Checks whether a request URI path resolves to a currently published blog article.
 */
function zeynart_uri_is_live_published_post(string $uriPath, ?array $store = null): bool
{
    $trimmed = trim((string)(parse_url($uriPath, PHP_URL_PATH) ?: ''), '/');
    if ($trimmed === '') {
        return false;
    }
    $segs = explode('/', $trimmed);
    $lang = 'tr';
    if ($segs !== [] && in_array(strtolower($segs[0]), ['en', 'de', 'ru', 'tr'], true)) {
        $lang = strtolower((string)array_shift($segs));
    }
    if (count($segs) !== 2) {
        return false;
    }
    $st = $store ?? load_store();
    if (!zeynart_is_page_accessible($st, 'blog', false)) {
        return false;
    }
    $resolved = zeynart_resolved_page_slugs($st);
    $blogBase = strtolower((string)($resolved['blog'] ?? 'blog'));
    $seg0 = strtolower($segs[0]);
    if ($seg0 !== $blogBase && $seg0 !== 'blog') {
        return false;
    }
    $post = find_post_by_localized_slug($segs[1], $lang === 'tr' ? 'tr' : $lang);
    return $post !== null && strtolower((string)($post['status'] ?? 'published')) === 'published';
}

function save_post(array $post, ?string $oldSlug = null): array
{
    $slug = slugify((string)($post['slug'] ?? $post['title'] ?? 'yeni-yazi'));
    $post['id'] = $slug;
    $post['slug'] = $slug;
    $post['date'] = (string)($post['date'] ?? date('Y-m-d'));
    if (empty($post['date_display'])) {
        $post['date_display'] = format_tr_date($post['date']);
    }
    $post['updated_at'] = date('Y-m-d');
    if (empty($post['read'])) {
        $post['read'] = estimate_reading_time((string)($post['content_html'] ?? ''));
    }
    $post['status'] = in_array($post['status'] ?? 'published', ['published', 'draft'], true)
        ? $post['status']
        : 'published';
    $post = zeynart_enrich_post_category_fields($post);

    // Build compact i18n card summary & synchronize localized slug map
    $i18nSummary = [];
    $slugMap = load_blog_slug_map(true);
    foreach (['en', 'de', 'ru'] as $lCode) {
        if (!isset($slugMap[$lCode]) || !is_array($slugMap[$lCode])) {
            $slugMap[$lCode] = [];
        }
        foreach ($slugMap[$lCode] as $lSlug => $targetBase) {
            if ($targetBase === $slug || ($oldSlug !== null && $oldSlug !== '' && $targetBase === $oldSlug)) {
                unset($slugMap[$lCode][$lSlug]);
            }
        }
        $trData = $post['translations'][$lCode] ?? null;
        if (is_array($trData) && !empty($trData['title'])) {
            $locSlug = (string)($trData['slug'] ?? $slug);
            $i18nSummary[$lCode] = [
                'slug' => $locSlug,
                'title' => (string)$trData['title'],
                'category' => (string)($trData['category'] ?? t_category((string)$post['category'], $lCode)),
                'categories' => array_values(array_map(static fn(string $nm): string => t_category($nm, $lCode), $post['categories'])),
                'excerpt' => (string)($trData['excerpt'] ?? ''),
                'date_display' => (string)($trData['date_display'] ?? format_localized_date($post['date'], $lCode)),
                'read' => (string)($trData['read'] ?? localize_reading_time((string)$post['read'], $lCode)),
            ];
            if ($locSlug !== '') {
                $slugMap[$lCode][$locSlug] = $slug;
            }
        }
    }

    $isFeatured = !empty($post['featured']);
    $summaryItem = [
        'slug' => $slug,
        'title' => (string)($post['title'] ?? ''),
        'category' => (string)($post['category'] ?? 'Genel'),
        'categories' => $post['categories'],
        'category_ids' => $post['category_ids'],
        'category_slugs' => $post['category_slugs'],
        'excerpt' => (string)($post['excerpt'] ?? ''),
        'date' => (string)$post['date'],
        'date_display' => (string)$post['date_display'],
        'read' => (string)$post['read'],
        'image' => (string)($post['image'] ?? '/images/journal/web-planning.webp'),
        'status' => (string)$post['status'],
        'featured' => $isFeatured,
        'curated' => !empty($post['curated']),
        'seo_score' => (int)($post['seo']['seo_score'] ?? 90),
    ];
    if ($i18nSummary !== []) {
        $summaryItem['i18n'] = $i18nSummary;
    }
    if (!empty($post['news_bot']) && is_array($post['news_bot'])) {
        $summaryItem['news_bot'] = $post['news_bot'];
    }

    $savedViaSqlFastPath = false;
    if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        $pdo = zeynart_get_pdo();
        if ($pdo) {
            $cfgSql = zeynart_load_db_config();
            $tPosts = zeynart_sql_table('posts', $cfgSql);
            $lookupSlug = ($oldSlug !== null && $oldSlug !== '' && $oldSlug !== $slug) ? $oldSlug : $slug;
            $ordStmt = $pdo->prepare("SELECT sort_order FROM `{$tPosts}` WHERE slug = :s LIMIT 1");
            $ordStmt->execute([':s' => substr($lookupSlug, 0, 190)]);
            $existingOrd = $ordStmt->fetchColumn();
            if ($existingOrd === false && $lookupSlug !== $slug) {
                $ordStmt->execute([':s' => substr($slug, 0, 190)]);
                $existingOrd = $ordStmt->fetchColumn();
            }
            $ownTx = !$pdo->inTransaction();
            if ($ownTx) {
                $pdo->beginTransaction();
            }
            try {
                if ($existingOrd === false) {
                    $shiftStmt = $pdo->prepare("UPDATE `{$tPosts}` SET sort_order = sort_order + 1");
                    $shiftStmt->execute();
                    $targetOrd = 0;
                } else {
                    $targetOrd = (int)$existingOrd;
                }
                zeynart_sql_upsert_post_row($pdo, $cfgSql, $post, $summaryItem, $targetOrd, $oldSlug);
                if ($ownTx) {
                    $pdo->commit();
                }
                $savedViaSqlFastPath = true;
            } catch (Throwable) {
                if ($ownTx && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
            if ($savedViaSqlFastPath) {
                save_blog_slug_map($slugMap);
                $GLOBALS['__zeynart_sql_blog_idx_mem'] = null;
                zeynart_sql_invalidate_runtime_caches();
                zeynart_notify_json_mutated(DATA_DIR . '/blog_index.json');
                zeynart_request_lscache_purge();
                load_blog_index(true);
            }
        }
    }

    if (!$savedViaSqlFastPath) {
        if ($oldSlug !== null && $oldSlug !== '' && $oldSlug !== $slug) {
            $oldFile = POSTS_DIR . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $oldSlug) . '.json';
            zeynart_delete_json_data($oldFile);
        }
        atomic_write_json(POSTS_DIR . '/' . $slug . '.json', $post, false);
        save_blog_slug_map($slugMap);

        $index = load_blog_index(true);
        $replaced = false;
        foreach ($index as $k => &$entry) {
            if ($isFeatured && $entry['slug'] !== $slug) {
                $entry['featured'] = false;
            }
            if ($entry['slug'] === $slug || ($oldSlug !== null && $entry['slug'] === $oldSlug)) {
                $index[$k] = $summaryItem;
                $replaced = true;
            }
        }
        unset($entry);

        if (!$replaced) {
            array_unshift($index, $summaryItem);
        }
        save_blog_index($index);
    }

    // Clean any 404 log entries & conflicting stale 301 redirects for this post's active URLs
    $curStore = load_store();
    $resolvedSlugs = zeynart_resolved_page_slugs($curStore);
    $blogBaseSlug = (string)($resolvedSlugs['blog'] ?? 'blog');
    $activePostPaths = [
        '/' . strtolower($blogBaseSlug . '/' . $slug) => true,
        '/blog/' . strtolower($slug) => true,
    ];
    foreach (['en', 'de', 'ru'] as $lc) {
        $activePostPaths['/' . $lc . '/' . strtolower($blogBaseSlug . '/' . $slug)] = true;
        $activePostPaths['/' . $lc . '/blog/' . strtolower($slug)] = true;
        $locSl = strtolower(trim((string)($i18nSummary[$lc]['slug'] ?? ''), '/'));
        if ($locSl !== '') {
            $activePostPaths['/' . $lc . '/' . strtolower($blogBaseSlug) . '/' . $locSl] = true;
            $activePostPaths['/' . $lc . '/blog/' . $locSl] = true;
        }
    }
    if (function_exists('zeynart_remove_404_entry')) {
        foreach (array_keys($activePostPaths) as $cleanP) {
            zeynart_remove_404_entry($cleanP);
        }
        if ($oldSlug !== null && $oldSlug !== '' && $oldSlug !== $slug) {
            zeynart_remove_404_entry('/' . $blogBaseSlug . '/' . $oldSlug);
        }
    }

    if ($post['status'] === 'published') {
        $storeDirty = false;
        if (!empty($curStore['redirects']) && is_array($curStore['redirects'])) {
            $keptReds = [];
            foreach ($curStore['redirects'] as $redRow) {
                if (!is_array($redRow)) {
                    continue;
                }
                $rFromLower = '/' . strtolower(trim((string)($redRow['from'] ?? ''), '/'));
                if ($rFromLower !== '/' && isset($activePostPaths[$rFromLower])) {
                    $storeDirty = true;
                    continue;
                }
                $keptReds[] = $redRow;
            }
            if ($storeDirty) {
                $curStore['redirects'] = array_values($keptReds);
            }
        }
        if ($oldSlug !== null && $oldSlug !== '' && $oldSlug !== $slug) {
            if (zeynart_append_loop_free_redirect($curStore, '/' . $blogBaseSlug . '/' . $oldSlug, '/' . $blogBaseSlug . '/' . $slug . '/', 301)) {
                $storeDirty = true;
            }
        }
        if ($storeDirty) {
            $prevSkipRev = $GLOBALS['zeynart_skip_revision_capture'] ?? null;
            $GLOBALS['zeynart_skip_revision_capture'] = true;
            save_store($curStore);
            if ($prevSkipRev === null) {
                unset($GLOBALS['zeynart_skip_revision_capture']);
            } else {
                $GLOBALS['zeynart_skip_revision_capture'] = $prevSkipRev;
            }
        }
    }

    return $post;
}

function delete_post(string $slug): bool
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
    if ($safeSlug === '') {
        return false;
    }
    zeynart_delete_json_data(POSTS_DIR . '/' . $safeSlug . '.json');
    $slugMap = load_blog_slug_map(true);
    $mapDirty = false;
    foreach (['en', 'de', 'ru'] as $lCode) {
        foreach (($slugMap[$lCode] ?? []) as $lSlug => $targetBase) {
            if ($targetBase === $safeSlug) {
                unset($slugMap[$lCode][$lSlug]);
                $mapDirty = true;
            }
        }
    }
    if ($mapDirty) {
        save_blog_slug_map($slugMap);
    }
    $index = load_blog_index(true);
    $filtered = array_values(array_filter(
        $index,
        static fn(array $item): bool => ($item['slug'] ?? '') !== $safeSlug
    ));
    return save_blog_index($filtered);
}

function find_service(array $store, string $slug, bool $includeDrafts = false): ?array
{
    foreach (($store['services'] ?? []) as $srv) {
        if (($srv['slug'] ?? '') === $slug) {
            $st = zeynart_normalize_page_status($srv['status'] ?? 'published');
            if ($st === 'published' || ($includeDrafts && $st === 'draft')) {
                return $srv;
            }
        }
    }
    return null;
}

function find_project(array $store, string $slug, bool $includeDrafts = false): ?array
{
    foreach (($store['projects'] ?? []) as $prj) {
        if (($prj['slug'] ?? '') === $slug) {
            $st = zeynart_normalize_page_status($prj['status'] ?? 'published');
            if ($st === 'published' || ($includeDrafts && $st === 'draft')) {
                return $prj;
            }
        }
    }
    return null;
}

function find_custom_page(array $store, string $slug, bool $includeDrafts = false): ?array
{
    foreach (($store['custom_pages'] ?? []) as $pg) {
        if (($pg['slug'] ?? '') === $slug) {
            $st = zeynart_normalize_page_status($pg['status'] ?? 'published');
            if ($st === 'published' || ($includeDrafts && $st === 'draft')) {
                return $pg;
            }
        }
    }
    return null;
}

function zeynart_media_cache_file(): string
{
    return DATA_DIR . '/media_index_cache.json';
}

function zeynart_media_summary_cache_file(): string
{
    return DATA_DIR . '/media_summary_cache.json';
}

function zeynart_dir_metrics_cache_file(): string
{
    return DATA_DIR . '/dir_metrics_cache.json';
}

function zeynart_invalidate_media_cache(): void
{
    $GLOBALS['__zeynart_media_index_mem'] = null;
    $GLOBALS['__zeynart_media_summary_mem'] = null;
    $GLOBALS['__zeynart_media_dirs_sig_mem'] = null;
    $GLOBALS['__zeynart_dir_metrics_mem'] = null;
    $GLOBALS['__zeynart_boot_bundle_mem'] = null;
    foreach ([
        zeynart_media_cache_file(),
        zeynart_media_summary_cache_file(),
        zeynart_dir_metrics_cache_file(),
        DATA_DIR . '/admin_boot_summary_cache.json',
    ] as $cf) {
        zeynart_delete_json_data($cf);
    }
}

function zeynart_media_dirs_signature(): string
{
    if (is_string($GLOBALS['__zeynart_media_dirs_sig_mem'] ?? null) && $GLOBALS['__zeynart_media_dirs_sig_mem'] !== '') {
        return $GLOBALS['__zeynart_media_dirs_sig_mem'];
    }
    $parts = [];
    foreach ([UPLOADS_DIR, IMAGES_DIR] as $root) {
        if (!is_dir($root)) {
            $parts[] = '0';
            continue;
        }
        $parts[] = $root . ':' . (int)@filemtime($root);
        foreach (@scandir($root) ?: [] as $n1) {
            if ($n1 === '.' || $n1 === '..' || $n1 === 'og') {
                continue;
            }
            $d1 = $root . '/' . $n1;
            if (!is_dir($d1)) {
                continue;
            }
            $parts[] = $n1 . ':' . (int)@filemtime($d1);
            foreach (@scandir($d1) ?: [] as $n2) {
                if ($n2 === '.' || $n2 === '..') {
                    continue;
                }
                $d2 = $d1 . '/' . $n2;
                if (!is_dir($d2)) {
                    continue;
                }
                $parts[] = $n1 . '/' . $n2 . ':' . (int)@filemtime($d2);
                // Only descend to level-3 (DD) if $n1 looks like YYYY or custom subfolder with < 40 entries
                if (ctype_digit($n1) || strlen($n2) <= 4) {
                    foreach (@scandir($d2) ?: [] as $n3) {
                        if ($n3 === '.' || $n3 === '..' || str_contains($n3, '.')) {
                            continue;
                        }
                        $d3 = $d2 . '/' . $n3;
                        if (is_dir($d3)) {
                            $parts[] = $n1 . '/' . $n2 . '/' . $n3 . ':' . (int)@filemtime($d3);
                        }
                    }
                }
            }
        }
    }
    $sig = md5(implode('|', $parts));
    $GLOBALS['__zeynart_media_dirs_sig_mem'] = $sig;
    return $sig;
}

function zeynart_get_cached_media_entries(bool $forceRefresh = false): array
{
    if (!$forceRefresh && is_array($GLOBALS['__zeynart_media_index_mem'] ?? null)) {
        $mem = $GLOBALS['__zeynart_media_index_mem'];
        $mem['cache_hit'] = true;
        return $mem;
    }

    $cacheFile = zeynart_media_cache_file();
    $sig = zeynart_media_dirs_signature();

    if (!$forceRefresh) {
        $dec = zeynart_read_json_data($cacheFile);
        if (
            is_array($dec)
            && ($dec['sig'] ?? '') === $sig
            && ((time() - (int)($dec['created_at'] ?? 0)) < 604800)
            && is_array($dec['entries'] ?? null)
        ) {
            $dec['cache_hit'] = true;
            $GLOBALS['__zeynart_media_index_mem'] = $dec;
            return $dec;
        }
    }

    $allowedExts = ['webp', 'avif', 'jpg', 'jpeg', 'png', 'gif', 'svg'];
    $roots = [
        'uploads' => UPLOADS_DIR,
        'images' => IMAGES_DIR,
    ];

    $entries = [];
    $folders = ['all' => 0];
    $totalBytes = 0;
    $uploadsCount = 0;
    $uploadsBytes = 0;
    $uploadsDiskFiles = 0;
    $uploadsDiskBytes = 0;
    $imagesCount = 0;
    $webpAvifCount = 0;

    foreach ($roots as $prefix => $baseDir) {
        if (!is_dir($baseDir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if (!$fileInfo->isFile()) {
                continue;
            }
            $fileSize = (int)$fileInfo->getSize();
            if ($prefix === 'uploads') {
                $uploadsDiskFiles++;
                $uploadsDiskBytes += $fileSize;
            }
            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, $allowedExts, true)) {
                continue;
            }
            $fullPath = str_replace('\\', '/', $fileInfo->getPathname());
            $relFromRoot = substr($fullPath, strlen(ROOT_DIR));
            $folderKey = trim(dirname($relFromRoot), '/');

            if ($folderKey === 'uploads/og' || str_starts_with($folderKey, 'uploads/og/')) {
                continue;
            }

            $isUpload = ($prefix === 'uploads') || str_starts_with($relFromRoot, '/uploads/');
            $folders['all']++;
            $folders[$folderKey] = ($folders[$folderKey] ?? 0) + 1;
            if (preg_match('~^(uploads(?:/[a-zA-Z0-9_-]+)?)/(\d{4})/(\d{2})/(\d{2})$~', $folderKey, $dm)) {
                $yKey = $dm[1] . '/' . $dm[2];
                $ymKey = $dm[1] . '/' . $dm[2] . '/' . $dm[3];
                $folders[$yKey] = ($folders[$yKey] ?? 0) + 1;
                $folders[$ymKey] = ($folders[$ymKey] ?? 0) + 1;
            }
            $totalBytes += $fileSize;
            if ($isUpload) {
                $uploadsCount++;
                $uploadsBytes += $fileSize;
            } else {
                $imagesCount++;
            }
            if (in_array($ext, ['webp', 'avif'], true)) {
                $webpAvifCount++;
            }

            $entries[] = [
                'u' => $relFromRoot,
                'n' => $fileInfo->getFilename(),
                'f' => $folderKey,
                'e' => $ext,
                's' => $fileSize,
                'm' => (int)$fileInfo->getMTime(),
                'up' => $isUpload ? 1 : 0,
            ];
        }
    }

    // Pre-sort newest modified first so default Media Library & Dashboard queries require zero re-sorting
    usort($entries, static fn(array $a, array $b): int => ($b['m'] <=> $a['m']) ?: strcmp($a['n'], $b['n']));
    ksort($folders);

    $payload = [
        'sig' => $sig,
        'created_at' => time(),
        'total_files' => count($entries),
        'total_bytes' => $totalBytes,
        'uploads_count' => $uploadsCount,
        'uploads_bytes' => $uploadsBytes,
        'uploads_disk_files' => $uploadsDiskFiles,
        'uploads_disk_bytes' => $uploadsDiskBytes,
        'images_count' => $imagesCount,
        'webp_avif_count' => $webpAvifCount,
        'folders' => $folders,
        'entries' => $entries,
    ];

    atomic_write_json($cacheFile, $payload, false);

    $payload['cache_hit'] = false;
    $GLOBALS['__zeynart_media_index_mem'] = $payload;
    return $payload;
}

function zeynart_quick_media_summary(array $store, int $recentLimit = 8): array
{
    $limit = max(1, $recentLimit);
    if (is_array($GLOBALS['__zeynart_media_summary_mem'] ?? null) && (int)($GLOBALS['__zeynart_media_summary_mem']['limit'] ?? 0) === $limit) {
        return $GLOBALS['__zeynart_media_summary_mem'];
    }

    $sig = zeynart_media_dirs_signature();
    $mediaMeta = is_array($store['media_meta'] ?? null) ? $store['media_meta'] : [];
    $metaHash = md5((string)json_encode($mediaMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $sumFile = zeynart_media_summary_cache_file();

    $decSum = zeynart_read_json_data($sumFile);
    if (
        is_array($decSum)
        && ($decSum['sig'] ?? '') === $sig
        && ($decSum['meta_hash'] ?? '') === $metaHash
        && (int)($decSum['limit'] ?? 0) === $limit
        && is_array($decSum['items'] ?? null)
    ) {
        $decSum['cache_hit'] = true;
        $GLOBALS['__zeynart_media_summary_mem'] = $decSum;
        return $decSum;
    }

    $idx = zeynart_get_cached_media_entries(false);
    $entries = $idx['entries'] ?? [];

    $missingAlt = 0;
    foreach ($entries as $e) {
        if (!empty($e['up'])) {
            $alt = trim((string)($mediaMeta[$e['u']]['alt'] ?? ''));
            if ($alt === '') {
                $missingAlt++;
            }
        }
    }

    $recent = [];
    foreach (array_slice($entries, 0, $limit) as $e) {
        $url = (string)$e['u'];
        $ext = (string)$e['e'];
        $w = 0;
        $h = 0;
        if ($ext !== 'svg') {
            $szInfo = @getimagesize(ROOT_DIR . $url);
            if (is_array($szInfo)) {
                $w = (int)($szInfo[0] ?? 0);
                $h = (int)($szInfo[1] ?? 0);
            }
        }
        $recent[] = [
            'url' => $url,
            'name' => (string)$e['n'],
            'folder' => (string)$e['f'],
            'ext' => $ext,
            'size' => (int)$e['s'],
            'mtime' => (int)$e['m'],
            'alt' => trim((string)($mediaMeta[$url]['alt'] ?? '')),
            'width' => $w,
            'height' => $h,
        ];
    }

    $out = [
        'sig' => $sig,
        'meta_hash' => $metaHash,
        'limit' => $limit,
        'created_at' => time(),
        'total' => (int)($idx['total_files'] ?? count($entries)),
        'uploads_count' => (int)($idx['uploads_count'] ?? 0),
        'uploads_disk_files' => (int)($idx['uploads_disk_files'] ?? 0),
        'uploads_disk_bytes' => (int)($idx['uploads_disk_bytes'] ?? 0),
        'missing_alt_count' => $missingAlt,
        'items' => $recent,
        'cache_hit' => !empty($idx['cache_hit']),
    ];

    atomic_write_json($sumFile, $out, false);
    $GLOBALS['__zeynart_media_summary_mem'] = $out;
    return $out;
}

function zeynart_quick_media_disk_metrics(bool $forceDiskScan = false): array
{
    if (!$forceDiskScan) {
        $sig = zeynart_media_dirs_signature();
        if (is_array($GLOBALS['__zeynart_media_summary_mem'] ?? null) && ($GLOBALS['__zeynart_media_summary_mem']['sig'] ?? '') === $sig) {
            $m = $GLOBALS['__zeynart_media_summary_mem'];
            return [
                'bytes' => (int)($m['uploads_disk_bytes'] ?? 0),
                'files' => (int)($m['uploads_disk_files'] ?? 0),
                'total_files' => (int)($m['total'] ?? 0),
                'cache_hit' => true,
            ];
        }
        $sumFile = zeynart_media_summary_cache_file();
        $dec = zeynart_read_json_data($sumFile);
        if (is_array($dec) && ($dec['sig'] ?? '') === $sig && isset($dec['uploads_disk_bytes'], $dec['uploads_disk_files'])) {
            return [
                'bytes' => (int)$dec['uploads_disk_bytes'],
                'files' => (int)$dec['uploads_disk_files'],
                'total_files' => (int)($dec['total'] ?? 0),
                'cache_hit' => true,
            ];
        }
    }
    $idx = zeynart_get_cached_media_entries($forceDiskScan);
    return [
        'bytes' => (int)($idx['uploads_disk_bytes'] ?? 0),
        'files' => (int)($idx['uploads_disk_files'] ?? 0),
        'total_files' => (int)($idx['total_files'] ?? 0),
        'cache_hit' => !empty($idx['cache_hit']),
    ];
}

function scan_media_files(
    string $folderFilter = 'all',
    string $search = '',
    int $page = 1,
    int $perPage = 48,
    string $typeFilter = 'all',
    string $sort = 'newest'
): array
{
    $store = load_store();
    $mediaMeta = is_array($store['media_meta'] ?? null) ? $store['media_meta'] : [];
    $idx = zeynart_get_cached_media_entries(false);

    $missingAltCount = 0;
    $allFiles = [];
    $searchLower = mb_strtolower(trim($search), 'UTF-8');

    foreach (($idx['entries'] ?? []) as $e) {
        $relFromRoot = (string)$e['u'];
        $filename = (string)$e['n'];
        $folderKey = (string)$e['f'];
        $ext = (string)$e['e'];
        $fileSize = (int)$e['s'];
        $isUpload = !empty($e['up']);
        $alt = trim((string)($mediaMeta[$relFromRoot]['alt'] ?? ''));

        if ($isUpload && $alt === '') {
            $missingAltCount++;
        }

        if ($folderFilter !== 'all' && $folderFilter !== '') {
            if ($folderKey !== $folderFilter && !str_starts_with($folderKey, $folderFilter . '/')) {
                continue;
            }
        }

        if ($typeFilter === 'uploads' && !$isUpload) {
            continue;
        }
        if ($typeFilter === 'images' && $isUpload) {
            continue;
        }
        if ($typeFilter === 'webp' && !in_array($ext, ['webp', 'avif'], true)) {
            continue;
        }
        if ($typeFilter === 'raster' && !in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            continue;
        }
        if ($typeFilter === 'svg' && $ext !== 'svg') {
            continue;
        }
        if ($typeFilter === 'missing_alt' && (!$isUpload || $alt !== '')) {
            continue;
        }

        if ($searchLower !== '') {
            $haystack = mb_strtolower($filename . ' ' . $relFromRoot . ' ' . $alt, 'UTF-8');
            if (!str_contains($haystack, $searchLower)) {
                continue;
            }
        }

        $allFiles[] = [
            'url' => $relFromRoot,
            'name' => $filename,
            'folder' => $folderKey,
            'ext' => $ext,
            'size' => $fileSize,
            'mtime' => (int)$e['m'],
            'alt' => $alt,
        ];
    }

    // Cached entries are already sorted newest-first; only re-sort if non-default sort requested
    if ($sort === 'oldest') {
        usort($allFiles, static fn(array $a, array $b): int => ($a['mtime'] <=> $b['mtime']) ?: strcmp($a['name'], $b['name']));
    } elseif ($sort === 'largest') {
        usort($allFiles, static fn(array $a, array $b): int => ($b['size'] <=> $a['size']) ?: strcmp($a['name'], $b['name']));
    } elseif ($sort === 'smallest') {
        usort($allFiles, static fn(array $a, array $b): int => ($a['size'] <=> $b['size']) ?: strcmp($a['name'], $b['name']));
    } elseif ($sort === 'name') {
        usort($allFiles, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    }

    $stats = [
        'total_files' => (int)($idx['total_files'] ?? 0),
        'total_bytes' => (int)($idx['total_bytes'] ?? 0),
        'uploads_count' => (int)($idx['uploads_count'] ?? 0),
        'images_count' => (int)($idx['images_count'] ?? 0),
        'webp_avif_count' => (int)($idx['webp_avif_count'] ?? 0),
        'missing_alt_count' => $missingAltCount,
    ];

    $folders = is_array($idx['folders'] ?? null) ? $idx['folders'] : ['all' => $stats['total_files']];
    $total = count($allFiles);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allFiles, $offset, $perPage);

    // Enrich only the visible page slice with pixel dimensions (zero overhead on full scan)
    foreach ($items as &$item) {
        $item['width'] = 0;
        $item['height'] = 0;
        if ($item['ext'] !== 'svg') {
            $absFile = ROOT_DIR . $item['url'];
            $szInfo = @getimagesize($absFile);
            if (is_array($szInfo)) {
                $item['width'] = (int)($szInfo[0] ?? 0);
                $item['height'] = (int)($szInfo[1] ?? 0);
            }
        }
    }
    unset($item);

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'folders' => $folders,
        'stats' => $stats,
        'cache_hit' => !empty($idx['cache_hit']),
    ];
}
