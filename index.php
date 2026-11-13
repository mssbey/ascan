<?php
declare(strict_types=1);

// Support PHP built-in development server (php -S) for static assets & /admin (or custom physical /x/ admin dir)
if (PHP_SAPI === 'cli-server') {
    $reqPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $decodedPath = rawurldecode($reqPath);
    $fullCandidate = __DIR__ . $decodedPath;

    // Block internal directories, hidden dotfiles (except /.well-known/), and script execution in static folders
    if (
        preg_match('~^/(data|includes|lib|pages)(/|$)~i', $decodedPath)
        || preg_match('~(^|/)\.(?!well-known(/|$))~i', $decodedPath)
        || preg_match('~^/(uploads|images|assets)/.*\.(php[0-9]?|phtml|phar|inc|sh|sql|env)$~i', $decodedPath)
    ) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }

    $cliAdminDir = 'admin';
    if (is_file(__DIR__ . '/data/.admin_dir_name')) {
        $candDir = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string)@file_get_contents(__DIR__ . '/data/.admin_dir_name'))) ?? '';
        if ($candDir !== '' && is_file(__DIR__ . '/' . $candDir . '/index.php')) {
            $cliAdminDir = $candDir;
        }
    } elseif (!is_file(__DIR__ . '/admin/index.php')) {
        foreach (@scandir(__DIR__) ?: [] as $eName) {
            if ($eName === '' || $eName[0] === '.' || in_array(strtolower($eName), ['assets', 'data', 'images', 'includes', 'lib', 'pages', 'uploads'], true)) {
                continue;
            }
            if (is_file(__DIR__ . '/' . $eName . '/index.php') && is_file(__DIR__ . '/' . $eName . '/api.php')) {
                $cliAdminDir = $eName;
                break;
            }
        }
    }
    $cliCustomSlug = is_file(__DIR__ . '/data/.custom_admin_slug')
        ? strtolower(trim((string)@file_get_contents(__DIR__ . '/data/.custom_admin_slug')))
        : '';
    $hasVirtualAdminSlug = ($cliCustomSlug !== '' && strcasecmp($cliCustomSlug, $cliAdminDir) !== 0);
    $cliAdminPrefix = '/' . $cliAdminDir;

    if (!$hasVirtualAdminSlug && ($decodedPath === $cliAdminPrefix || str_starts_with($decodedPath, $cliAdminPrefix . '/'))) {
        if (is_file($fullCandidate) && !str_ends_with($fullCandidate, '.php')) {
            return false;
        }
        $_SERVER['ZEYNART_ROUTED_ADMIN'] = '1';
        if (is_file($fullCandidate) && str_ends_with($fullCandidate, '.php')) {
            require $fullCandidate;
            exit;
        }
        require __DIR__ . $cliAdminPrefix . '/index.php';
        exit;
    }

    $isHiddenAdminAsset = ($hasVirtualAdminSlug && str_starts_with($decodedPath, $cliAdminPrefix))
        || (strcasecmp($cliAdminDir, 'admin') !== 0 && str_starts_with($decodedPath, '/admin'));
    if ($decodedPath !== '/' && !str_starts_with($decodedPath, '/.well-known/') && !$isHiddenAdminAsset && is_file($fullCandidate) && !str_ends_with($fullCandidate, '.php')) {
        return false;
    }
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';

$rawUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$uriPath = preg_replace('/[\x00-\x1F\x7F]+/', '', rawurldecode((string)(parse_url($rawUri, PHP_URL_PATH) ?: '/'))) ?: '/';

if (BASE_PATH !== '' && str_starts_with($uriPath, BASE_PATH)) {
    $uriPath = substr($uriPath, strlen(BASE_PATH)) ?: '/';
}

// Defense-in-depth: block direct access to dotfiles (except /.well-known/) and internal folders even if .htaccess is bypassed
if (
    preg_match('~^/(data|includes|lib|pages)(/|$)~i', $uriPath)
    || preg_match('~(^|/)\.(?!well-known(/|$))~i', $uriPath)
    || preg_match('~^/(uploads|images|assets)/.*\.(php[0-9]?|phtml|phar|inc|sh|sql|env)$~i', $uriPath)
) {
    http_response_code(403);
    exit('403 Forbidden');
}

// Fast non-polluting LiteSpeed LSCache probe responder
if (($_SERVER['HTTP_X_STUDIO_CACHE_PROBE'] ?? '') === '1') {
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-LiteSpeed-Cache-Control: no-cache');
    header('X-Studio-Probe: miss');
    http_response_code(200);
    exit;
}

// Clean Internal API & Scheduled Job Endpoints (served via Front Controller — zero root clutter, zero Set-Cookie)
$cleanEndpointPath = rtrim($uriPath, '/') ?: '/';
$endpointDispatchMap = [
    '/api/iletisim'             => '/includes/endpoints/iletisim.php',
    '/iletisim-gonder.php'      => '/includes/endpoints/iletisim.php',
    '/api/site-analiz'          => '/includes/endpoints/site-analiz.php',
    '/site-analiz-api.php'      => '/includes/endpoints/site-analiz.php',
    '/api/teklif-sihirbazi'     => '/includes/endpoints/teklif-sihirbazi.php',
    '/teklif-sihirbazi-api.php' => '/includes/endpoints/teklif-sihirbazi.php',
    '/api/analitik-nabiz'       => '/includes/endpoints/analitik-nabiz.php',
    '/analitik-nabiz.php'       => '/includes/endpoints/analitik-nabiz.php',
    '/api/arama'                => '/includes/endpoints/arama.php',
    '/api/blog-yorum'           => '/includes/endpoints/blog-yorum.php',
    '/api/teklif-onay'          => '/includes/endpoints/teklif-onay.php',
    '/api/proje-takip-onay'     => '/includes/endpoints/proje-takip-onay.php',
    '/api/gorusme-planla'       => '/includes/endpoints/gorusme-planla.php',
    '/api/gorusme-takvim'       => '/includes/endpoints/gorusme-planla.php',
    '/api/cron/haber-otopilot'  => '/includes/endpoints/haber-otopilot-cron.php',
    '/api/haber-otopilot-cron'  => '/includes/endpoints/haber-otopilot-cron.php',
    '/haber-otopilot-cron.php'  => '/includes/endpoints/haber-otopilot-cron.php',
    '/api/cron/uptime'          => '/includes/endpoints/uptime-cron.php',
    '/api/uptime-cron'          => '/includes/endpoints/uptime-cron.php',
    '/uptime-cron.php'          => '/includes/endpoints/uptime-cron.php',
    '/api/ota-hub'              => '/includes/endpoints/ota-hub.php',
    '/api/ota-client'           => '/includes/endpoints/ota-client.php',
];
if (isset($endpointDispatchMap[$cleanEndpointPath])) {
    header('X-LiteSpeed-Cache-Control: no-cache');
    require __DIR__ . $endpointDispatchMap[$cleanEndpointPath];
    exit;
}
if (preg_match('~^/api/ext/([a-z0-9][a-z0-9_-]{0,63})$~i', $cleanEndpointPath, $extEpMatch)) {
    $extEpFile = INCLUDES_DIR . '/extensions/endpoints/' . strtolower($extEpMatch[1]) . '.php';
    if (is_file($extEpFile)) {
        header('X-LiteSpeed-Cache-Control: no-cache');
        require $extEpFile;
        exit;
    }
}
if (preg_match('~^/api/([a-z0-9][a-z0-9_-]{1,63})$~i', $cleanEndpointPath, $sectorEpMatch)) {
    $sectorEpFile = INCLUDES_DIR . '/endpoints/' . strtolower($sectorEpMatch[1]) . '.php';
    if (is_file($sectorEpFile)) {
        header('X-LiteSpeed-Cache-Control: no-cache');
        require $sectorEpFile;
        exit;
    }
}

// Custom Admin Panel URL Slug Dispatcher & Stealth /admin (or physical /ADMIN_DIR_NAME) 404 Shield
$earlyAdminStore = load_store();
$activeAdminSlug = zeynart_get_admin_slug($earlyAdminStore);
$physAdminDir = ADMIN_DIR_NAME;
$isBlockedDefaultAdminReq = ($activeAdminSlug !== 'admin' && preg_match('~^/admin(?:/|$)~i', $uriPath));
$isBlockedPhysAdminReq = (strcasecmp($activeAdminSlug, $physAdminDir) !== 0 && preg_match('~^/' . preg_quote($physAdminDir, '~') . '(?:/|$)~i', $uriPath));
if ($isBlockedDefaultAdminReq || $isBlockedPhysAdminReq) {
    if (!empty($_COOKIE['zeynart_sid'])) {
        start_secure_session();
    }
    send_security_headers(false);
    if (!headers_sent()) {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('X-LiteSpeed-Cache-Control: no-cache');
    }
    http_response_code(404);
    $store = $earlyAdminStore;
    $activeLang = 'tr';
    zeynart_set_lang('tr');
    $currentRoutePath = '/404';
    require PAGES_DIR . '/404.php';
    exit;
}
if (($activeAdminSlug !== 'admin' || strcasecmp($physAdminDir, 'admin') !== 0) && preg_match('~^/' . preg_quote($activeAdminSlug, '~') . '(?:/(.*))?$~i', $uriPath, $admRouteMatch)) {
    $admSubPath = $admRouteMatch[1] ?? null;
    if ($admSubPath === null) {
        $qs = (string)($_SERVER['QUERY_STRING'] ?? '');
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('X-LiteSpeed-Cache-Control: no-cache');
        header('X-Robots-Tag: noindex, nofollow');
        header('Location: ' . BASE_PATH . '/' . $activeAdminSlug . '/' . ($qs !== '' ? '?' . $qs : ''), true, 302);
        exit;
    }
    if ($admSubPath === '' || strcasecmp($admSubPath, 'index.php') === 0) {
        $_SERVER['ZEYNART_ROUTED_ADMIN'] = '1';
        header('X-LiteSpeed-Cache-Control: no-cache');
        @chdir(ADMIN_DIR);
        require ADMIN_DIR . '/index.php';
        exit;
    }
    if (strcasecmp($admSubPath, 'api.php') === 0) {
        $_SERVER['ZEYNART_ROUTED_ADMIN'] = '1';
        @ignore_user_abort(true);
        header('X-LiteSpeed-Cache-Control: no-cache');
        @chdir(ADMIN_DIR);
        require ADMIN_DIR . '/api.php';
        exit;
    }
    $admSafeAsset = str_replace('\\', '/', $admSubPath);
    if (!str_contains($admSafeAsset, '..') && preg_match('~^[A-Za-z0-9_.\-/]+\.(?:css|js|png|webp|avif|svg|ico|woff2?)$~i', $admSafeAsset)) {
        $admAssetAbs = ADMIN_DIR . '/' . $admSafeAsset;
        if (is_file($admAssetAbs)) {
            $ext = strtolower(pathinfo($admAssetAbs, PATHINFO_EXTENSION));
            $mimes = [
                'css' => 'text/css; charset=UTF-8',
                'js' => 'application/javascript; charset=UTF-8',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'avif' => 'image/avif',
                'ico' => 'image/x-icon',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
            ];
            header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
            header('Cache-Control: public, max-age=86400');
            readfile($admAssetAbs);
            exit;
        }
    }
}

// Dynamic & Cached 1200x630 JPEG OpenGraph Social Card Endpoint (served sessionless for WhatsApp/LinkedIn/Facebook/X bots)
if (preg_match('~^/og-card/([A-Za-z0-9_\-]+)(?:\.jpe?g)?$~i', $uriPath, $ogm)) {
    require_once __DIR__ . '/includes/social_card.php';
    zeynart_serve_og_card_request($ogm[1], '');
}
if ($uriPath === '/og-card.jpg' || $uriPath === '/og-card.jpeg') {
    require_once __DIR__ . '/includes/social_card.php';
    zeynart_serve_og_card_request('', (string)($_GET['src'] ?? ''));
}
if ($uriPath === '/og-card-branded.jpg' || $uriPath === '/og-card-branded.jpeg') {
    $previewStore = load_store();
    if (!empty($_GET['domain_badge'])) {
        $previewStore['seo']['branded_og']['domain_badge'] = (string)$_GET['domain_badge'];
    }
    if (!empty($_GET['eyebrow_default'])) {
        $previewStore['seo']['branded_og']['eyebrow_default'] = (string)$_GET['eyebrow_default'];
    }
    if (isset($_GET['overlay_opacity']) && is_numeric($_GET['overlay_opacity'])) {
        $previewStore['seo']['branded_og']['overlay_opacity'] = (int)$_GET['overlay_opacity'];
    }
    $bgSrc = trim((string)($_GET['bg'] ?? ''));
    require_once __DIR__ . '/includes/social_card.php';
    $bgNorm = $bgSrc !== '' ? (zeynart_normalize_local_media_path($bgSrc, $previewStore) ?? '') : '';
    $genFile = zeynart_generate_branded_og_jpeg([
        'title' => (string)($_GET['title'] ?? ($previewStore['seo']['default_title'] ?? 'Kurumsal Dijital Stüdyo')),
        'eyebrow' => (string)($_GET['eyebrow'] ?? ''),
        'subtitle' => (string)($_GET['subtitle'] ?? ''),
        'bg_local' => $bgNorm,
    ], $previewStore);
    if ($genFile !== null && is_file($genFile)) {
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . (int)filesize($genFile));
        header('Cache-Control: public, max-age=3600');
        readfile($genFile);
        exit;
    }
    zeynart_serve_og_card_request('', '');
}

if (!empty($_COOKIE['zeynart_sid'])) {
    start_secure_session();
}
send_security_headers();

// Helper to resolve converted/deduplicated WebP path for legacy /uploads/* URLs
$resolveUploadTarget = static function (string $relPath): string {
    $cleanRel = ltrim($relPath, '/');
    if ($cleanRel === '' || str_contains($cleanRel, '..')) {
        return '/uploads/' . $cleanRel;
    }
    if (is_file(UPLOADS_DIR . '/' . $cleanRel)) {
        return '/uploads/' . $cleanRel;
    }
    if (preg_match('~^(.+)\.(jpe?g|png|webp)$~i', $cleanRel, $em)) {
        $base = $em[1];
        $extLower = strtolower($em[2]);
        if ($extLower !== 'webp' && is_file(UPLOADS_DIR . '/' . $base . '-' . $extLower . '.webp')) {
            return '/uploads/' . $base . '-' . $extLower . '.webp';
        }
        if (is_file(UPLOADS_DIR . '/' . $base . '.webp')) {
            return '/uploads/' . $base . '.webp';
        }
        $aliasFile = DATA_DIR . '/upload_aliases.json';
        $aliases = zeynart_read_json_data($aliasFile);
        if (is_array($aliases)) {
            $targetShort = $aliases[$cleanRel] ?? $aliases[$base] ?? null;
            if (is_string($targetShort) && is_file(UPLOADS_DIR . '/' . $targetShort)) {
                return '/uploads/' . $targetShort;
            }
        }
    }
    return '/uploads/' . $cleanRel;
};

// Legacy /wp-content/uploads/* fallback 301 redirect (single-hop to .webp when converted)
if (preg_match('~^/wp-content/uploads/(.+)$~i', $uriPath, $wpm)) {
    header('Location: ' . url($resolveUploadTarget($wpm[1])), true, 301);
    exit;
}

// Legacy /uploads/*.jpg|jpeg|png|webp -> canonical .webp 301 redirect when optimized/deduplicated
if (preg_match('~^/uploads/(.+\.(?:jpe?g|png|webp))$~i', $uriPath, $upm)) {
    $resolvedUpload = $resolveUploadTarget($upm[1]);
    if ($resolvedUpload !== '/uploads/' . ltrim($upm[1], '/')) {
        header('Location: ' . url($resolvedUpload), true, 301);
        exit;
    }
}

// Legacy date permalink /YYYY/MM/DD/slug/ -> /blog/slug/
if (preg_match('~^/\d{4}/\d{2}/\d{2}/([^/]+)/?$~', $uriPath, $dm)) {
    header('Location: ' . url('/blog/' . $dm[1] . '/'), true, 301);
    exit;
}

// Backward-compatible /opengraph-image social link support
if ($uriPath === '/opengraph-image' || $uriPath === '/opengraph-image/') {
    $ogFile = IMAGES_DIR . '/opengraph-image.png';
    if (is_file($ogFile)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($ogFile);
        exit;
    }
}

$store = load_store();

// IndexNow verification key file support (/{key}.txt)
$indexNowKey = trim((string)($store['seo']['indexnow_key'] ?? ''));
if ($indexNowKey !== '' && $uriPath === '/' . $indexNowKey . '.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo $indexNowKey;
    exit;
}

// Custom 301/302 Redirect Manager lookup (specific page-to-page rules only; broken links auto-fallback to / at end of router)
$normUri = '/' . trim($uriPath, '/');
foreach (($store['redirects'] ?? []) as $rIdx => $red) {
    $fromNorm = '/' . trim((string)($red['from'] ?? ''), '/');
    if ($fromNorm !== '/' && strcasecmp($fromNorm, $normUri) === 0) {
        $to = trim((string)($red['to'] ?? ''));
        if ($to === '' || $to === '/') {
            continue;
        }
        if (function_exists('zeynart_uri_is_live_published_post') && zeynart_uri_is_live_published_post($normUri, $store)) {
            break;
        }
        $code = in_array((int)($red['code'] ?? 301), [301, 302, 307, 308], true) ? (int)$red['code'] : 301;
        $targetLocation = (str_starts_with($to, 'http://') || str_starts_with($to, 'https://'))
            ? $to
            : (BASE_PATH . '/' . ltrim($to, '/'));
        $targetPathNorm = '/' . trim((string)(parse_url($targetLocation, PHP_URL_PATH) ?: '/'), '/');
        $curPathNorm = '/' . trim((string)(parse_url($rawUri, PHP_URL_PATH) ?: '/'), '/');
        if (strcasecmp($targetPathNorm, $curPathNorm) !== 0) {
            zeynart_increment_redirect_hit($fromNorm);
            if (!headers_sent()) {
                header('X-LiteSpeed-Cache-Control: no-cache');
            }
            header('Location: ' . $targetLocation, true, $code);
            exit;
        }
    }
}

// Technical SEO & AI SEO dynamic endpoints
if ($uriPath === '/sitemap.xsl' || $uriPath === '/sitemap.css') {
    header('Location: ' . url('/sitemap.xml'), true, 301);
    exit;
}
if (in_array($uriPath, ['/sitemap.xml', '/sitemap-pages.xml', '/sitemap-blog.xml'], true)) {
    $sitemapRoute = ltrim($uriPath, '/');
    require PAGES_DIR . '/sitemap.php';
    exit;
}

if ($uriPath === '/robots.txt') {
    require PAGES_DIR . '/robots.php';
    exit;
}

if ($uriPath === '/llms.txt' || $uriPath === '/llms-full.txt') {
    $llmsFull = ($uriPath === '/llms-full.txt');
    require PAGES_DIR . '/llms.php';
    exit;
}

if (in_array($uriPath, ['/.well-known/ai-catalog.json', '/ai-catalog.json', '/.well-known/ard.json', '/.well-known/mcp-server-card.json', '/.well-known/agent-card.json'], true)) {
    $aiCatalogRoute = basename($uriPath);
    require PAGES_DIR . '/ai-catalog.php';
    exit;
}

$rawRoute = trim($uriPath, '/');
$rawSegments = $rawRoute === '' ? [] : explode('/', $rawRoute);

// Canonicalize /tr/... -> /...
if ($rawSegments !== [] && strtolower($rawSegments[0]) === 'tr') {
    $restSegs = array_slice($rawSegments, 1);
    $redirPath = $restSegs === [] ? '/' : ('/' . implode('/', $restSegs) . '/');
    header('Location: ' . lang_url($redirPath, 'tr'), true, 301);
    exit;
}

$activeLang = 'tr';
if ($rawSegments !== [] && in_array(strtolower($rawSegments[0]), ['en', 'de', 'ru'], true)) {
    $activeLang = strtolower((string)array_shift($rawSegments));
}
zeynart_set_lang($activeLang);
$rawStore = $store;
if ($activeLang !== 'tr') {
    $store = zeynart_localize_store($store, $activeLang);
}

$segments = $rawSegments;
$route = implode('/', $segments);
$pageSlugs = zeynart_resolved_page_slugs($store);

$canPreviewDrafts = is_admin_logged_in();

// Smart Corporate Maintenance Mode (anonymous visitors see Apple Glass maintenance screen; logged-in admin previews live site)
$mntState = zeynart_get_maintenance_state($store);
if (!empty($mntState['enabled']) && !$canPreviewDrafts) {
    require PAGES_DIR . '/bakim.php';
    exit;
}

// Track anonymous public 200 OK page renders handed to LiteSpeed LSCache
if (
    !empty($GLOBALS['__zeynart_lscache_eligible'])
    && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET'
    && empty($_GET)
    && !str_starts_with($uriPath, '/teklif/')
    && !str_starts_with($uriPath, '/proje-takip/')
) {
    $lsTrackUri = $uriPath;
    $lsTrackTtl = (int)($GLOBALS['__zeynart_lscache_eligible']['ttl'] ?? 3600);
    register_shutdown_function(static function () use ($lsTrackUri, $lsTrackTtl): void {
        global $currentRoutePath;
        if (
            http_response_code() === 200
            && is_string($currentRoutePath ?? null)
            && $currentRoutePath !== ''
            && $currentRoutePath !== '/404'
            && !is_file(DATA_DIR . '/.lscache_disabled')
            && !is_file(DATA_DIR . '/.maintenance_active')
        ) {
            zeynart_record_lscache_page_hit($lsTrackUri, $lsTrackTtl, 'render');
        }
    });
}

// Clean Route Dispatcher -> pages/*.php
if ($route === '') {
    $currentRoutePath = '/';
    require PAGES_DIR . '/anasayfa.php';
    exit;
}

if ($route === $pageSlugs['hakkimizda'] && zeynart_is_page_accessible($store, 'hakkimizda', $canPreviewDrafts)) {
    $currentRoutePath = '/' . $pageSlugs['hakkimizda'] . '/';
    require PAGES_DIR . '/hakkimizda.php';
    exit;
}

if ($route === $pageSlugs['hizmetler'] && zeynart_is_page_accessible($store, 'hizmetler', $canPreviewDrafts)) {
    $currentRoutePath = '/' . $pageSlugs['hizmetler'] . '/';
    require PAGES_DIR . '/hizmetler.php';
    exit;
}

if (count($segments) === 2 && $segments[0] === $pageSlugs['hizmetler'] && zeynart_is_page_accessible($store, 'hizmetler', $canPreviewDrafts)) {
    $serviceSlug = $segments[1];
    $activeService = find_service($store, $serviceSlug, $canPreviewDrafts);
    if ($activeService !== null) {
        $currentRoutePath = '/' . $pageSlugs['hizmetler'] . '/' . $serviceSlug . '/';
        require PAGES_DIR . '/hizmet-detay.php';
        exit;
    }
}

if ($route === $pageSlugs['projeler'] && zeynart_is_page_accessible($store, 'projeler', $canPreviewDrafts)) {
    $currentRoutePath = '/' . $pageSlugs['projeler'] . '/';
    require PAGES_DIR . '/projeler.php';
    exit;
}

if (count($segments) === 2 && $segments[0] === $pageSlugs['projeler'] && zeynart_is_page_accessible($store, 'projeler', $canPreviewDrafts)) {
    $projectSlug = $segments[1];
    $activeProject = find_project($store, $projectSlug, $canPreviewDrafts);
    if ($activeProject !== null) {
        $currentRoutePath = '/' . $pageSlugs['projeler'] . '/' . $projectSlug . '/';
        require PAGES_DIR . '/proje-detay.php';
        exit;
    }
}

if ($route === $pageSlugs['blog'] && zeynart_is_page_accessible($store, 'blog', $canPreviewDrafts)) {
    $legacyCatParam = trim((string)($_GET['kategori'] ?? $_GET['cat'] ?? ''));
    $legacySearchParam = trim((string)($_GET['ara'] ?? $_GET['q'] ?? ''));
    $isJsonReq = (($_GET['format'] ?? '') === 'json');
    if ($legacyCatParam !== '' && $legacySearchParam === '' && !$isJsonReq && !zeynart_is_ignored_all_category_label($legacyCatParam)) {
        $legacySlug = slugify($legacyCatParam);
        $foundCat = $legacySlug !== '' ? zeynart_find_blog_category_by_slug($legacySlug, $rawStore) : null;
        if ($foundCat !== null) {
            $redirPath = '/' . $pageSlugs['blog'] . '/kategori/' . $foundCat['slug'] . '/';
            $targetUrl = lang_url($redirPath, $activeLang);
            $legacyPageNum = (int)($_GET['sayfa'] ?? $_GET['page'] ?? 1);
            if ($legacyPageNum > 1) {
                $targetUrl .= '?sayfa=' . $legacyPageNum;
            }
            header('Location: ' . $targetUrl, true, 301);
            exit;
        }
        $candOldPath = '/' . $pageSlugs['blog'] . '/kategori/' . $legacySlug;
        foreach (($rawStore['redirects'] ?? []) as $rRule) {
            $rf = rtrim((string)($rRule['from'] ?? ''), '/');
            if ($rf !== '' && strcasecmp($rf, $candOldPath) === 0 && !empty($rRule['to'])) {
                $toClean = '/' . trim((string)$rRule['to'], '/') . '/';
                header('Location: ' . lang_url($toClean, $activeLang), true, 301);
                exit;
            }
        }
    }
    $currentRoutePath = '/' . $pageSlugs['blog'] . '/';
    require PAGES_DIR . '/blog.php';
    exit;
}

if (
    count($segments) === 3
    && $segments[0] === $pageSlugs['blog']
    && in_array(strtolower($segments[1]), ['kategori', 'category'], true)
    && zeynart_is_page_accessible($store, 'blog', $canPreviewDrafts)
) {
    $reqCatSlug = slugify($segments[2]);
    if ($segments[1] !== 'kategori') {
        header('Location: ' . lang_url('/' . $pageSlugs['blog'] . '/kategori/' . $reqCatSlug . '/', $activeLang), true, 301);
        exit;
    }
    $activeBlogCategory = $reqCatSlug !== '' ? zeynart_find_blog_category_by_slug($reqCatSlug, $store) : null;
    if ($activeBlogCategory !== null) {
        if ($segments[2] !== $activeBlogCategory['slug']) {
            header('Location: ' . lang_url('/' . $pageSlugs['blog'] . '/kategori/' . $activeBlogCategory['slug'] . '/', $activeLang), true, 301);
            exit;
        }
        $currentRoutePath = '/' . $pageSlugs['blog'] . '/kategori/' . $activeBlogCategory['slug'] . '/';
        require PAGES_DIR . '/blog.php';
        exit;
    }
}

$matchedUnpublishedDraftPost = false;
if (count($segments) === 2 && $segments[0] === $pageSlugs['blog'] && zeynart_is_page_accessible($store, 'blog', $canPreviewDrafts)) {
    $blogSlug = $segments[1];
    $activePost = find_post_by_localized_slug($blogSlug, $activeLang);
    if ($activePost !== null && (($activePost['status'] ?? 'published') === 'published' || $canPreviewDrafts)) {
        $rawCatTree = zeynart_build_blog_category_tree(zeynart_get_blog_categories($rawStore));
        $activePost = zeynart_enrich_post_category_fields($activePost, $rawCatTree);
        require_once __DIR__ . '/includes/translator.php';
        $activePost = zeynart_ensure_post_translation($activePost, $activeLang, $store);
        $currentRoutePath = '/' . $pageSlugs['blog'] . '/' . ($activePost['slug'] ?? $blogSlug) . '/';
        require PAGES_DIR . '/blog-detay.php';
        exit;
    }
    if ($activePost !== null) {
        $matchedUnpublishedDraftPost = true;
    }
}

if ($route === $pageSlugs['iletisim'] && zeynart_is_page_accessible($store, 'iletisim', $canPreviewDrafts)) {
    $currentRoutePath = '/' . $pageSlugs['iletisim'] . '/';
    require PAGES_DIR . '/iletisim.php';
    exit;
}

if ($route === $pageSlugs['site-analiz'] && zeynart_is_page_accessible($store, 'site-analiz', $canPreviewDrafts)) {
    $currentRoutePath = '/' . $pageSlugs['site-analiz'] . '/';
    require PAGES_DIR . '/site-analiz.php';
    exit;
}

if ($route === $pageSlugs['teklif-sihirbazi'] && zeynart_is_page_accessible($store, 'teklif-sihirbazi', $canPreviewDrafts)) {
    $currentRoutePath = '/' . $pageSlugs['teklif-sihirbazi'] . '/';
    require PAGES_DIR . '/teklif-sihirbazi.php';
    exit;
}

if ($route === ($pageSlugs['gorusme-planla'] ?? 'gorusme-planla') && zeynart_is_page_accessible($store, 'gorusme-planla', $canPreviewDrafts)) {
    require_once __DIR__ . '/includes/agency_growth_suite.php';
    $bkSettingsCheck = zeynart_get_booking_settings($store);
    if (!empty($bkSettingsCheck['enabled']) || $canPreviewDrafts) {
        $currentRoutePath = '/' . ($pageSlugs['gorusme-planla'] ?? 'gorusme-planla') . '/';
        require PAGES_DIR . '/gorusme-planla.php';
        exit;
    }
}

// Corporate QR Digital Business Card (/kartvizit/{slug}/, /kartvizit/{slug}/rehber/, /kartvizit/{slug}/qr/)
if ($segments !== [] && $segments[0] === 'kartvizit') {
    require_once __DIR__ . '/includes/agency_powerhouse.php';
    if (count($segments) === 1) {
        $vcList = zeynart_get_vcards_list($store);
        $firstPub = null;
        foreach ($vcList as $candVc) {
            if (($candVc['status'] ?? 'published') === 'published' || $canPreviewDrafts) {
                $firstPub = $candVc;
                break;
            }
        }
        if ($firstPub !== null) {
            header('Location: ' . url('/kartvizit/' . $firstPub['slug'] . '/'), true, 302);
            exit;
        }
    } elseif (count($segments) >= 2) {
        $vcSlugReq = $segments[1];
        $activeVcard = zeynart_find_vcard($store, $vcSlugReq, $canPreviewDrafts);
        if ($activeVcard !== null) {
            $subAction = strtolower((string)($segments[2] ?? ($_GET['format'] ?? '')));
            if (in_array($subAction, ['rehber', 'vcard.vcf', 'vcf'], true) || !empty($_GET['vcf'])) {
                $vcfBytes = zeynart_compile_vcard_vcf($activeVcard, $store);
                header('Content-Type: text/vcard; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . $activeVcard['slug'] . '.vcf"');
                header('Content-Length: ' . strlen($vcfBytes));
                header('Cache-Control: no-store, max-age=0');
                echo $vcfBytes;
                exit;
            }
            if (in_array($subAction, ['qr', 'qr.svg', 'qr.png'], true)) {
                $cardPublicUrl = rtrim(canonical_url('/kartvizit/' . $activeVcard['slug'] . '/', $store, 'tr'), '/') . '/';
                $wantPng = ($subAction === 'qr.png' || (($_GET['type'] ?? '') === 'png'));
                if ($wantPng) {
                    $pngBytes = zeynart_qr_render_png_bytes($cardPublicUrl, 1000, [6, 19, 24], [255, 255, 255]);
                    if ($pngBytes !== '') {
                        header('Content-Type: image/png');
                        header('Content-Disposition: inline; filename="qr-' . $activeVcard['slug'] . '.png"');
                        header('Content-Length: ' . strlen($pngBytes));
                        header('Cache-Control: public, max-age=86400');
                        echo $pngBytes;
                        exit;
                    }
                }
                $svgStr = zeynart_qr_render_svg($cardPublicUrl, '#061318', '#ffffff', 3, 600);
                header('Content-Type: image/svg+xml; charset=UTF-8');
                header('Content-Disposition: inline; filename="qr-' . $activeVcard['slug'] . '.svg"');
                header('Cache-Control: public, max-age=86400');
                echo $svgStr;
                exit;
            }
            if (count($segments) === 2) {
                $currentRoutePath = '/kartvizit/' . $activeVcard['slug'] . '/';
                require PAGES_DIR . '/kartvizit.php';
                exit;
            }
        }
    }
}

// Client-Specific Interactive Online Proposal (/teklif/{code}/) with Open Tracking & Digital Approval
if (count($segments) === 2 && $segments[0] === 'teklif') {
    require_once __DIR__ . '/includes/enterprise_cms_suite.php';
    $prpCandidate = zeynart_find_proposal_by_code($segments[1], $canPreviewDrafts);
    if ($prpCandidate !== null) {
        if (!$canPreviewDrafts) {
            $updatedPrp = zeynart_record_proposal_view((string)$prpCandidate['code'], $store);
            if ($updatedPrp !== null) {
                $prpCandidate = $updatedPrp;
            }
        }
        $activeProposal = $prpCandidate;
        $currentRoutePath = '/teklif/' . $activeProposal['code'] . '/';
        require PAGES_DIR . '/teklif-detay.php';
        exit;
    }
}

// Client-Specific Printable A4 Digital Service Contract (/sozlesme/{code}/)
if (count($segments) === 2 && $segments[0] === 'sozlesme') {
    require_once __DIR__ . '/includes/enterprise_cms_suite.php';
    $prpCandidate = zeynart_find_proposal_by_code($segments[1], $canPreviewDrafts);
    if ($prpCandidate !== null) {
        $activeProposal = $prpCandidate;
        $currentRoutePath = '/sozlesme/' . $activeProposal['code'] . '/';
        require PAGES_DIR . '/sozlesme-detay.php';
        exit;
    }
}

// Client-Specific Project Milestone & Approval Portal (/proje-takip/{code}/)
if (count($segments) === 2 && $segments[0] === 'proje-takip') {
    require_once __DIR__ . '/includes/agency_growth_suite.php';
    $trkCandidate = zeynart_find_client_tracker_by_code($segments[1], $canPreviewDrafts);
    if ($trkCandidate !== null) {
        if (!$canPreviewDrafts) {
            $updatedTrk = zeynart_record_client_tracker_view((string)$trkCandidate['code']);
            if ($updatedTrk !== null) {
                $trkCandidate = $updatedTrk;
            }
        }
        $activeTracker = $trkCandidate;
        $currentRoutePath = '/proje-takip/' . $activeTracker['code'] . '/';
        require PAGES_DIR . '/proje-takip.php';
        exit;
    }
}

if ($route === 'cerezler' && zeynart_is_page_accessible($store, 'cerez-politikasi', $canPreviewDrafts)) {
    require_once __DIR__ . '/includes/cookie_consent.php';
    header('Location: ' . zeynart_cookie_policy_url_for_lang($activeLang), true, 301);
    exit;
}

if ($route === 'cookie-policy' && zeynart_is_page_accessible($store, 'cerez-politikasi', $canPreviewDrafts)) {
    require_once __DIR__ . '/includes/cookie_consent.php';
    if ($activeLang === 'tr') {
        $activeLang = 'en';
        zeynart_set_lang('en');
        $store = zeynart_localize_store($store, 'en');
    }
    $legalKey = 'cerez-politikasi';
    $currentRoutePath = '/cookie-policy/';
    require PAGES_DIR . '/politikalar.php';
    exit;
}

if ($route === $pageSlugs['cerez-politikasi'] && zeynart_is_page_accessible($store, 'cerez-politikasi', $canPreviewDrafts)) {
    require_once __DIR__ . '/includes/cookie_consent.php';
    $legalKey = 'cerez-politikasi';
    $currentRoutePath = '/' . $pageSlugs['cerez-politikasi'] . '/';
    require PAGES_DIR . '/politikalar.php';
    exit;
}

foreach (['kvkk', 'gizlilik'] as $lKeyCand) {
    if ($route === $pageSlugs[$lKeyCand] && isset($store['legal_pages'][$lKeyCand]) && zeynart_is_page_accessible($store, $lKeyCand, $canPreviewDrafts)) {
        $legalKey = $lKeyCand;
        $currentRoutePath = '/' . $pageSlugs[$lKeyCand] . '/';
        require PAGES_DIR . '/politikalar.php';
        exit;
    }
}

// Sector Extension Public Route Hook (e.g. /portfoy/*, /koleksiyon/*, /uyelik/* without modifying index.php)
if (is_file(INCLUDES_DIR . '/extensions/router.php')) {
    require INCLUDES_DIR . '/extensions/router.php';
}

// Custom Visual Builder & Rich Content Pages (any clean slug created in Admin Pages)
if (count($segments) === 1) {
    $customPage = find_custom_page($store, $segments[0], $canPreviewDrafts);
    if ($customPage !== null) {
        $currentRoutePath = '/' . $segments[0] . '/';
        require PAGES_DIR . '/sayfa.php';
        exit;
    }
}

// Automatic 301 fallback when a core or legal page's default URL was customized in Admin
$defaultPageSlugs = zeynart_default_page_slugs();
if (count($segments) === 1 && isset($defaultPageSlugs[$route]) && $pageSlugs[$route] !== $route && zeynart_is_page_accessible($store, $route, $canPreviewDrafts)) {
    header('Location: ' . lang_url('/' . $pageSlugs[$route] . '/', $activeLang), true, 301);
    exit;
}
if (count($segments) === 2 && in_array($segments[0], ['hizmetler', 'projeler', 'blog'], true) && $pageSlugs[$segments[0]] !== $segments[0] && zeynart_is_page_accessible($store, $segments[0], $canPreviewDrafts)) {
    header('Location: ' . lang_url('/' . $pageSlugs[$segments[0]] . '/' . $segments[1] . '/', $activeLang), true, 301);
    exit;
}
if (count($segments) === 3 && $segments[0] === 'blog' && strtolower($segments[1]) === 'kategori' && $pageSlugs['blog'] !== 'blog' && zeynart_is_page_accessible($store, 'blog', $canPreviewDrafts)) {
    header('Location: ' . lang_url('/' . $pageSlugs['blog'] . '/kategori/' . $segments[2] . '/', $activeLang), true, 301);
    exit;
}

// Non-working links automatically fall back to homepage via uncached 302 (never poisons future published URLs)
if (
    preg_match('~\.(?:webp|png|jpe?g|gif|svg|ico|css|js|map|woff2?|ttf|eot|xml|json|txt|php7?|env|git|sql|bak|zip|rar|tar|gz)$~i', $uriPath)
    || preg_match('~^/(?:\.well-known|\.git|\.env|wp-admin|wp-login|xmlrpc|phpmyadmin|cgi-bin)(/|$)~i', $uriPath)
) {
    if (!headers_sent()) {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('X-LiteSpeed-Cache-Control: no-cache');
    }
    http_response_code(404);
    exit('404 Not Found');
}

if (!headers_sent()) {
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-LiteSpeed-Cache-Control: no-cache');
}
header('Location: ' . lang_url('/', $activeLang), true, 302);
exit;
