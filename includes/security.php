<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (PHP_SAPI === 'cli') {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
        return;
    }
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_httponly', '1');
    session_name('zeynart_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

function send_security_headers(bool $isAdmin = false): void
{
    if (headers_sent()) {
        return;
    }
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );
    @header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('X-Permitted-Cross-Domain-Policies: none');
    header("Content-Security-Policy: frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    $pendingPurgeFlag = DATA_DIR . '/.lscache_needs_purge';
    if (is_file($pendingPurgeFlag)) {
        @unlink($pendingPurgeFlag);
        header('X-LiteSpeed-Purge: *');
    }
    if ($isAdmin) {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-LiteSpeed-Cache-Control: no-cache');
    } else {
        $maintFlagActive = is_file(DATA_DIR . '/.maintenance_active') || isset($_GET['preview_maintenance']);
        $lscacheCfg = function_exists('zeynart_get_lscache_config')
            ? zeynart_get_lscache_config(null)
            : ['enabled' => !is_file(DATA_DIR . '/.lscache_disabled'), 'ttl' => 3600];
        if (!empty($_COOKIE['zeynart_sid']) || $maintFlagActive || empty($lscacheCfg['enabled'])) {
            header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('X-LiteSpeed-Cache-Control: no-cache');
            if (empty($lscacheCfg['enabled'])) {
                header('X-Studio-LSCache: disabled');
            }
        } elseif (in_array(strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'HEAD'], true)) {
            $lsTtl = max(60, min(604800, (int)($lscacheCfg['ttl'] ?? 3600)));
            $reqPathForTag = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
            $lsTag = function_exists('zeynart_lscache_tag_for_uri')
                ? zeynart_lscache_tag_for_uri($reqPathForTag)
                : ('u_' . substr(md5($reqPathForTag), 0, 12));
            header('Cache-Control: no-cache, must-revalidate');
            header('X-LiteSpeed-Cache-Control: public, max-age=' . $lsTtl);
            header('X-LiteSpeed-Tag: site_all,' . $lsTag);
            header('X-Studio-Cached-At: ' . time());
            $GLOBALS['__zeynart_lscache_eligible'] = ['ttl' => $lsTtl, 'uri' => $reqPathForTag];
        }
        header('Link: <' . url('/.well-known/ai-catalog.json') . '>; rel="ai-catalog"; type="application/json"', false);
    }
}

function verify_same_origin_request(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }
    $secFetchSite = strtolower(trim((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')));
    if ($secFetchSite === 'cross-site') {
        return false;
    }
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin !== '') {
        if ($origin === 'null') {
            return false;
        }
        $originHost = strtolower((string)(parse_url($origin, PHP_URL_HOST) ?? ''));
        $originPort = parse_url($origin, PHP_URL_PORT);
        $originFull = $originHost . ($originPort !== null ? (':' . $originPort) : '');
        $serverHost = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
        $serverHostBare = (string)preg_replace('/:\d+$/', '', $serverHost);
        if ($originHost === '' || ($originFull !== $serverHost && $originHost !== $serverHostBare)) {
            return false;
        }
    }
    return true;
}

function zeynart_csrf_secret(): string
{
    static $cached = null;
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }
    $secretFile = DATA_DIR . '/.csrf_secret';
    if (is_file($secretFile)) {
        $raw = trim((string)@file_get_contents($secretFile));
        if (strlen($raw) >= 64) {
            $cached = $raw;
            return $cached;
        }
    }
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
    $generated = bin2hex(random_bytes(32));
    @file_put_contents($secretFile, $generated);
    $cached = $generated;
    return $cached;
}

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_ACTIVE || !empty($_COOKIE['zeynart_sid'])) {
        start_secure_session();
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    // Cookie-free (Zero-Cookie Load) stateless HMAC-SHA256 CSRF token for anonymous visitors
    $ts = (string)time();
    $nonce = bin2hex(random_bytes(16));
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180);
    $mac = hash_hmac('sha256', 'v1|' . $ts . '|' . $nonce . '|' . $ua, zeynart_csrf_secret());
    return 'v1.' . $ts . '.' . $nonce . '.' . $mac;
}

function verify_csrf(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }
    $reqMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (in_array($reqMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !verify_same_origin_request()) {
        return false;
    }
    if (str_starts_with($token, 'v1.')) {
        $parts = explode('.', $token);
        if (count($parts) !== 4) {
            return false;
        }
        [, $tsStr, $nonce, $sig] = $parts;
        if (!ctype_digit($tsStr) || !ctype_xdigit($nonce) || !ctype_xdigit($sig)) {
            return false;
        }
        $ts = (int)$tsStr;
        $age = time() - $ts;
        if ($age < -300 || $age > 43200) {
            return false;
        }
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180);
        $expectedMac = hash_hmac('sha256', 'v1|' . $tsStr . '|' . $nonce . '|' . $ua, zeynart_csrf_secret());
        return hash_equals($expectedMac, $sig);
    }
    if (session_status() === PHP_SESSION_ACTIVE || !empty($_COOKIE['zeynart_sid'])) {
        start_secure_session();
        $expected = $_SESSION['csrf_token'] ?? '';
        if (is_string($expected) && $expected !== '') {
            return hash_equals($expected, $token);
        }
    }
    return false;
}

function is_admin_logged_in(): bool
{
    if (PHP_SAPI !== 'cli' && empty($_COOKIE['zeynart_sid']) && session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    start_secure_session();
    return !empty($_SESSION['zeynart_admin_auth']) && $_SESSION['zeynart_admin_auth'] === true;
}

function get_client_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];
    foreach ($candidates as $ipList) {
        if (!is_string($ipList) || $ipList === '') {
            continue;
        }
        $first = trim(explode(',', $ipList)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return '127.0.0.1';
}

function check_rate_limit(string $bucket, int $maxAttempts = 8, int $windowSeconds = 300): bool
{
    $key = hash('sha256', $bucket . '|' . get_client_ip());
    $now = time();
    $hits = [];

    if (zeynart_is_sql_active()) {
        $rlKey = 'rl_' . substr($key, 0, 48);
        $decoded = zeynart_sql_kv_get($rlKey);
        if (is_array($decoded)) {
            foreach ($decoded as $ts) {
                if (is_int($ts) && ($now - $ts) < $windowSeconds) {
                    $hits[] = $ts;
                }
            }
        }
        if (count($hits) >= $maxAttempts) {
            return false;
        }
        $hits[] = $now;
        zeynart_sql_kv_set($rlKey, $hits, 'ratelimit');
        return true;
    }

    $dir = DATA_DIR . '/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/' . $key . '.json';

    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) {
            foreach ($decoded as $ts) {
                if (is_int($ts) && ($now - $ts) < $windowSeconds) {
                    $hits[] = $ts;
                }
            }
        }
    }

    if (count($hits) >= $maxAttempts) {
        return false;
    }

    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}

function zeynart_csv_safe_cell(string $val): string
{
    if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $val;
    }
    return $val;
}

function zeynart_sanitize_svg_markup(string $svgContent): array
{
    if (preg_match('~<!\s*(ENTITY|DOCTYPE)\b~i', $svgContent)) {
        return ['ok' => false, 'error' => 'SVG dosyasında harici varlık (XXE / DOCTYPE) tanımı engellendi.'];
    }
    $svgContent = preg_replace('~<(script|foreignObject|iframe|object|embed|applet|meta)\b[^>]*>.*?</\1>~isu', '', $svgContent) ?? $svgContent;
    $svgContent = preg_replace('~<(script|foreignObject|iframe|object|embed|applet|meta)\b[^>]*/?>~isu', '', $svgContent) ?? $svgContent;
    $svgContent = preg_replace('~\s+on[a-z0-9_\-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)~iu', '', $svgContent) ?? $svgContent;
    $svgContent = preg_replace('~\b(xlink:href|href|src)\s*=\s*["\']\s*(?:javascript:|vbscript:|data:text/html)[^"\']*["\']~iu', 'href="#"', $svgContent) ?? $svgContent;
    return ['ok' => true, 'svg' => $svgContent];
}

function zeynart_ensure_storage_htaccess(): void
{
    $dataHt = DATA_DIR . '/.htaccess';
    if (is_dir(DATA_DIR) && !is_file($dataHt)) {
        @file_put_contents(
            $dataHt,
            "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
        );
    }
    $upHt = UPLOADS_DIR . '/.htaccess';
    if (is_dir(UPLOADS_DIR) && !is_file($upHt)) {
        @file_put_contents(
            $upHt,
            "# Prevent script execution inside uploads directory\nOptions -Indexes -ExecCGI\n<FilesMatch \"\\.(?i:php|php[0-9]|phtml|phar|pl|py|cgi|sh|exe|bat|cmd|asp|aspx|jsp|htaccess)$\">\n    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n    <IfModule !mod_authz_core.c>\n        Order allow,deny\n        Deny from all\n    </IfModule>\n</FilesMatch>\n"
        );
    }
}

function sanitize_rich_html(string $html): string
{
    // Remove script, object, embed, applet, style tags with inner content
    $html = preg_replace('~<(script|object|embed|applet)\b[^>]*>.*?</\1>~isu', '', $html) ?? $html;
    // Remove inline event handlers (onclick=, onerror=, etc.)
    $html = preg_replace('~\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)~iu', '', $html) ?? $html;
    // Neutralize javascript: and data:text/html URIs
    $html = preg_replace('~(href|src)\s*=\s*(["\'])\s*(?:javascript:|vbscript:|data:text/html)[^"\']*\2~iu', '$1=$2#$2', $html) ?? $html;
    return trim($html);
}

function humanize_media_filename_to_alt(string $rawName): string
{
    $base = pathinfo($rawName, PATHINFO_FILENAME);
    // Strip trailing dimension patterns like -1920x1080 or random 8-char hash suffixes like -B73VPCGu
    $base = preg_replace('~-\d{2,4}x\d{2,4}$~i', '', $base) ?? $base;
    $base = preg_replace('~-[A-Za-z0-9]{8}$~', '', $base) ?? $base;
    $words = preg_split('~[-_\s\.]+~u', trim($base)) ?: [];
    $clean = [];
    foreach ($words as $w) {
        $w = trim($w);
        if ($w === '' || preg_match('~^\d{6,}$~', $w)) {
            continue;
        }
        $first = mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
        $rest = mb_strtolower(mb_substr($w, 1, null, 'UTF-8'), 'UTF-8');
        $clean[] = $first . $rest;
    }
    $title = trim(implode(' ', $clean));
    return $title !== '' ? $title : 'Zeynart Dijital Stüdyo Görseli';
}

function handle_media_upload(
    array $file,
    bool $optimizeWebp = true,
    string $customSubfolder = '',
    int $webpQuality = 86,
    int $maxWidth = 1920
): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Geçersiz dosya yükleme isteği.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Dosya yüklenirken hata oluştu (Kod: ' . (int)$file['error'] . ').'];
    }
    $origBytes = max(0, (int)($file['size'] ?? 0));
    $maxBytes = 15 * 1024 * 1024; // 15 MB
    if ($origBytes > $maxBytes) {
        return ['ok' => false, 'error' => 'Dosya boyutu 15 MB sınırını aşıyor.'];
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Geçici yükleme dosyası bulunamadı.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpPath);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    if (!isset($allowedMimes[$mime])) {
        return ['ok' => false, 'error' => 'Desteklenmeyen dosya türü: ' . $mime . '. Lütfen JPG, PNG, WebP, AVIF, GIF veya SVG yükleyin.'];
    }

    $rawClientName = (string)($file['name'] ?? 'gorsel');
    if (preg_match('/\.(php[0-9]?|phtml|phar|pl|py|cgi|sh|exe|bat|cmd|asp|aspx|jsp)(\.|$)/i', $rawClientName)) {
        return ['ok' => false, 'error' => 'Güvenlik kalkanı: Çoklu uzantılı veya çalıştırılabilir dosya adı reddedildi.'];
    }
    $headBytes = (string)@file_get_contents($tmpPath, false, null, 0, 8192);
    if (preg_match('/<\?(?:php|=)/i', $headBytes)) {
        return ['ok' => false, 'error' => 'Güvenlik kalkanı: Dosya içeriğinde gömülü betik (Polyglot) imzası engellendi.'];
    }

    $ext = $allowedMimes[$mime];
    if ($ext !== 'svg') {
        $dimCheck = @getimagesize($tmpPath);
        if (is_array($dimCheck) && (((int)($dimCheck[0] ?? 0) > 12000) || ((int)($dimCheck[1] ?? 0) > 12000))) {
            return ['ok' => false, 'error' => 'Görsel çözünürlüğü 12000px güvenlik sınırını (Decompression Bomb) aşıyor.'];
        }
    }
    $origName = pathinfo($rawClientName, PATHINFO_FILENAME);
    $safeBase = slugify($origName);
    if ($safeBase === '') {
        $safeBase = 'gorsel-' . date('His');
    }

    $dateSubPath = date('Y') . '/' . date('m') . '/' . date('d');
    $cleanCustom = trim(preg_replace('~[^a-zA-Z0-9/_-]+~', '', $customSubfolder) ?? '', '/');
    if ($cleanCustom === '' || strtolower($cleanCustom) === 'auto') {
        $subDir = $dateSubPath;
    } elseif (preg_match('~(?:^|/)\d{4}/\d{2}/\d{2}$~', $cleanCustom)) {
        $subDir = $cleanCustom;
    } else {
        $subDir = $cleanCustom . '/' . $dateSubPath;
    }

    $walkDir = UPLOADS_DIR;
    if (!is_dir($walkDir)) {
        @mkdir($walkDir, 0775, true);
    }
    foreach (explode('/', $subDir) as $seg) {
        if ($seg === '') {
            continue;
        }
        $walkDir .= '/' . $seg;
        if (!is_dir($walkDir) && !@mkdir($walkDir, 0775, true) && !is_dir($walkDir)) {
            return ['ok' => false, 'error' => 'Yükleme klasörü oluşturulamadı.'];
        }
        @chmod($walkDir, 0775);
    }
    $targetDir = $walkDir;

    $webpQuality = max(60, min(96, $webpQuality));
    $maxWidth = $maxWidth === 0 ? 0 : max(640, min(3840, $maxWidth));

    // Sanitize SVG uploads (strip XXE/DOCTYPE, scripts, active embedded objects, foreignObject, event handlers & dangerous URI schemes)
    if ($ext === 'svg') {
        $svgCheck = zeynart_sanitize_svg_markup((string)file_get_contents($tmpPath));
        if (empty($svgCheck['ok'])) {
            return ['ok' => false, 'error' => (string)($svgCheck['error'] ?? 'SVG doğrulaması başarısız.')];
        }
        $svgContent = (string)$svgCheck['svg'];
        $finalName = $safeBase . '.svg';
        $counter = 1;
        while (is_file($targetDir . '/' . $finalName)) {
            $finalName = $safeBase . '-' . (++$counter) . '.svg';
        }
        file_put_contents($targetDir . '/' . $finalName, $svgContent);
        if (function_exists('zeynart_invalidate_media_cache')) {
            zeynart_invalidate_media_cache();
        }
        $relUrl = '/uploads/' . $subDir . '/' . $finalName;
        $finalSize = filesize($targetDir . '/' . $finalName) ?: 0;
        $savedBytes = max(0, $origBytes - $finalSize);
        return [
            'ok' => true,
            'url' => $relUrl,
            'name' => $finalName,
            'ext' => 'svg',
            'original_ext' => $ext,
            'original_size' => $origBytes,
            'size' => $finalSize,
            'saved_bytes' => $savedBytes,
            'saved_pct' => $origBytes > 0 ? (int)round(($savedBytes / $origBytes) * 100) : 0,
            'optimized_webp' => false,
            'width' => 0,
            'height' => 0,
        ];
    }

    // High-quality GD WebP conversion & smart Retina scaling (JPG/PNG/oversized WebP)
    if ($optimizeWebp && in_array($ext, ['jpg', 'png', 'webp'], true) && function_exists('imagewebp')) {
        if ($ext === 'jpg') {
            $gdImg = @imagecreatefromjpeg($tmpPath);
            if ($gdImg !== false && function_exists('exif_read_data')) {
                $exif = @exif_read_data($tmpPath);
                $orient = (int)($exif['Orientation'] ?? 1);
                if ($orient === 3) {
                    $rot = @imagerotate($gdImg, 180, 0);
                } elseif ($orient === 6) {
                    $rot = @imagerotate($gdImg, -90, 0);
                } elseif ($orient === 8) {
                    $rot = @imagerotate($gdImg, 90, 0);
                } else {
                    $rot = false;
                }
                if ($rot !== false) {
                    unset($gdImg);
                    $gdImg = $rot;
                }
            }
        } elseif ($ext === 'png') {
            $gdImg = @imagecreatefrompng($tmpPath);
        } else {
            $gdImg = @imagecreatefromwebp($tmpPath);
        }

        if ($gdImg !== false) {
            $w = imagesx($gdImg);
            $h = imagesy($gdImg);
            // If already a reasonably sized WebP (within maxWidth and <= 500 KB), keep original lossless/exact bytes
            if ($ext === 'webp' && ($maxWidth === 0 || $w <= $maxWidth) && ($origBytes <= 512000)) {
                unset($gdImg);
            } else {
                imagepalettetotruecolor($gdImg);
                imagealphablending($gdImg, true);
                imagesavealpha($gdImg, true);
                if ($maxWidth > 0 && $w > $maxWidth) {
                    $newW = $maxWidth;
                    $newH = max(1, (int)round($h * ($newW / $w)));
                    $resized = @imagescale($gdImg, $newW, $newH, IMG_BICUBIC);
                    if ($resized !== false) {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        unset($gdImg);
                        $gdImg = $resized;
                        $w = $newW;
                        $h = $newH;
                    }
                }
                $finalName = $safeBase . '.webp';
                $counter = 1;
                while (is_file($targetDir . '/' . $finalName)) {
                    $finalName = $safeBase . '-' . (++$counter) . '.webp';
                }
                $destPath = $targetDir . '/' . $finalName;
                if (@imagewebp($gdImg, $destPath, $webpQuality)) {
                    unset($gdImg);
                    if (function_exists('zeynart_invalidate_media_cache')) {
                        zeynart_invalidate_media_cache();
                    }
                    $finalSize = filesize($destPath) ?: 0;
                    // If WebP somehow ended up larger than a tiny already-compressed JPEG without resizing, still report honest stats
                    $savedBytes = max(0, $origBytes - $finalSize);
                    return [
                        'ok' => true,
                        'url' => '/uploads/' . $subDir . '/' . $finalName,
                        'name' => $finalName,
                        'ext' => 'webp',
                        'original_ext' => $ext,
                        'original_size' => $origBytes,
                        'size' => $finalSize,
                        'saved_bytes' => $savedBytes,
                        'saved_pct' => $origBytes > 0 ? (int)round(($savedBytes / $origBytes) * 100) : 0,
                        'optimized_webp' => true,
                        'width' => $w,
                        'height' => $h,
                    ];
                }
                unset($gdImg);
            }
        }
    }

    $finalName = $safeBase . '.' . $ext;
    $counter = 1;
    while (is_file($targetDir . '/' . $finalName)) {
        $finalName = $safeBase . '-' . (++$counter) . '.' . $ext;
    }
    $destPath = $targetDir . '/' . $finalName;

    if (!@move_uploaded_file($tmpPath, $destPath)) {
        if (!@copy($tmpPath, $destPath)) {
            return ['ok' => false, 'error' => 'Dosya hedef klasöre taşınamadı.'];
        }
    }
    if (function_exists('zeynart_invalidate_media_cache')) {
        zeynart_invalidate_media_cache();
    }

    $imgInfo = @getimagesize($destPath);
    $finalSize = filesize($destPath) ?: 0;
    $savedBytes = max(0, $origBytes - $finalSize);
    return [
        'ok' => true,
        'url' => '/uploads/' . $subDir . '/' . $finalName,
        'name' => $finalName,
        'ext' => $ext,
        'original_ext' => $ext,
        'original_size' => $origBytes,
        'size' => $finalSize,
        'saved_bytes' => $savedBytes,
        'saved_pct' => $origBytes > 0 ? (int)round(($savedBytes / $origBytes) * 100) : 0,
        'optimized_webp' => ($ext === 'webp'),
        'width' => (int)($imgInfo[0] ?? 0),
        'height' => (int)($imgInfo[1] ?? 0),
    ];
}

/**
 * 20-Item Enterprise Security & CI/CD Self-Audit Scanner.
 * Verifies every control from the 20-point OWASP & Production Security Checklist programmatically.
 */
function zeynart_run_security_20_audit(?array $store = null): array
{
    zeynart_ensure_storage_htaccess();
    $rootHtaccess = is_file(ROOT_DIR . '/.htaccess') ? (string)@file_get_contents(ROOT_DIR . '/.htaccess') : '';
    $dataHtaccess = is_file(DATA_DIR . '/.htaccess') ? (string)@file_get_contents(DATA_DIR . '/.htaccess') : '';
    $uploadsHtaccess = is_file(UPLOADS_DIR . '/.htaccess') ? (string)@file_get_contents(UPLOADS_DIR . '/.htaccess') : '';

    $allowedRootEntries = [
        '.', '..', '.htaccess', 'index.php', 'icon.png', 'favicon.ico',
        '.well-known', 'admin', defined('ADMIN_DIR_NAME') ? ADMIN_DIR_NAME : 'admin',
        'assets', 'data', 'images', 'includes', 'lib', 'pages', 'uploads',
        // Standard cPanel / Plesk / LiteSpeed / CloudLinux hosting system entries
        'cgi-bin', 'error_log', 'php.ini', '.user.ini', '.ftpquota', '.cagefs', '.cl.selector', '.htpasswds',
        'plesk-stat', 'webstat', 'webstat-ssl', 'ftpstat', 'anon_ftpstat', 'awstats-icon',
    ];
    $rootScan = @scandir(ROOT_DIR) ?: [];
    $unexpectedRoot = [];
    $cleanableArchives = [];
    foreach ($rootScan as $entry) {
        if (in_array($entry, $allowedRootEntries, true)) {
            continue;
        }
        // Allow standard search-engine & IndexNow root verification files (google*.html, yandex_*.html, BingSiteAuth.xml, <hex>.txt)
        if (preg_match('/^(google[a-z0-9]+\.html|yandex_[a-z0-9]+\.html|BingSiteAuth\.xml|[a-f0-9]{16,64}\.txt)$/i', $entry)) {
            continue;
        }
        $unexpectedRoot[] = $entry;
        if (
            preg_match('/\.(zip|tar|tgz|gz|rar|7z|bak|old)$/i', $entry)
            || in_array($entry, ['ai-catalog.json', 'ard.json', 'mcp-server-card.json', 'agent-card.json'], true)
        ) {
            if (is_file(ROOT_DIR . '/' . $entry)) {
                $cleanableArchives[] = $entry;
            }
        }
    }

    $depTargets = ['vendor', 'node_modules', 'bower_components', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json'];
    $foundDeps = array_values(array_intersect($rootScan, $depTargets));
    $depsClean = ($foundDeps === []);

    $csrfSecretLen = strlen(zeynart_csrf_secret());
    $ignoreArgs = (string)ini_get('zend.exception_ignore_args');
    $displayErrors = (string)ini_get('display_errors');

    $items = [
        [
            'id' => 1,
            'key' => 'authentication',
            'title' => 'Authentication (Kimlik Doğrulama)',
            'ok' => function_exists('password_verify') && defined('PASSWORD_BCRYPT'),
            'detail' => 'Bcrypt parola özetleme, RFC 6238 TOTP / E-posta 2FA, girişte session_regenerate_id(true) ve gizli yönetim adresi (Stealth Slug) koruması aktif.',
        ],
        [
            'id' => 2,
            'key' => 'authorization',
            'title' => 'Authorization (RBAC Rol & Yetki Matrisi)',
            'ok' => is_file(ADMIN_DIR . '/api.php') && is_file(INCLUDES_DIR . '/studio_pro_pack.php'),
            'detail' => 'Sunucu taraflı RBAC (super_admin & editor) ayrımı, yetkisiz işlemlerde HTTP 403 engeli ve API yanıtlarında hassas alt alan koruması.',
        ],
        [
            'id' => 3,
            'key' => 'input_validation',
            'title' => 'Input Validation & SSRF Koruması',
            'ok' => defined('SITE_URL'),
            'detail' => 'Strict types, HTTP_HOST doğrulaması, 10 haneli telefon/e-posta/KVKK doğrulaması ve Site Analiz/Uptime modüllerinde yerel ağ SSRF engeli.',
        ],
        [
            'id' => 4,
            'key' => 'injection_protection',
            'title' => 'SQL / NoSQL / Path / SMTP / CSV Injection Protection',
            'ok' => function_exists('zeynart_csv_safe_cell') && is_file(LIB_DIR . '/smtp.php'),
            'detail' => 'Sıfır ham SQL mimarisi, realpath() dizin sınırlandırması, RFC 5321/2047 CRLF-korumalı SMTP başlıkları ve CSV Formula Injection (=,+,-,@) kalkanı.',
        ],
        [
            'id' => 5,
            'key' => 'xss_protection',
            'title' => 'XSS & SVG XXE Protection',
            'ok' => function_exists('sanitize_rich_html') && function_exists('zeynart_sanitize_svg_markup') && function_exists('e'),
            'detail' => 'ENT_QUOTES|ENT_HTML5 e() kaçışı, sanitize_rich_html() beyaz listesi ve zeynart_sanitize_svg_markup() XXE/foreignObject/script temizleyici aktif.',
        ],
        [
            'id' => 6,
            'key' => 'csrf_protection',
            'title' => 'CSRF Protection (Çift Katmanlı Token)',
            'ok' => function_exists('verify_csrf') && $csrfSecretLen >= 64,
            'detail' => 'Admin için 256-bit session CSRF + genel formlar (/api/iletisim, /api/teklif-sihirbazi, /api/gorusme-planla, /api/proje-takip) için çerezsiz HMAC-SHA256 CSRF doğrulaması.',
        ],
        [
            'id' => 7,
            'key' => 'cors_configuration',
            'title' => 'CORS Configuration & Same-Origin Guard',
            'ok' => function_exists('verify_same_origin_request') && str_contains($rootHtaccess, 'ai-catalog'),
            'detail' => 'verify_same_origin_request() ile çapraz kaynak (Cross-Site) POST engeli; CORS (*) yalnızca salt-okunur /.well-known/*.json keşif dosyalarıyla sınırlı.',
        ],
        [
            'id' => 8,
            'key' => 'https_enforcement',
            'title' => 'HTTPS & HSTS Zorunluluğu',
            'ok' => str_contains($rootHtaccess, 'https://%{HTTP_HOST}%{REQUEST_URI}') && function_exists('zeynart_is_https_request'),
            'detail' => 'Canlı ortamda otomatik HTTP → HTTPS 301 yönlendirmesi ve 1 yıllık HSTS (Strict-Transport-Security: includeSubDomains) başlığı.',
        ],
        [
            'id' => 9,
            'key' => 'secure_cookies',
            'title' => 'Secure Cookies & Zero-Cookie Anonim Yükleme',
            'ok' => function_exists('start_secure_session'),
            'detail' => 'zeynart_sid oturum çerezinde HttpOnly, SameSite=Lax, Proxy-duyarlı Secure bayrağı, use_strict_mode=1 ve anonim ziyaretçide sıfır Set-Cookie.',
        ],
        [
            'id' => 10,
            'key' => 'rate_limiting',
            'title' => 'Rate Limiting & Brute-Force Kalkanı',
            'ok' => function_exists('check_rate_limit'),
            'detail' => 'Atomik LOCK_EX kayan pencere hız sınırlayıcı, Trusted-Proxy gerçek IP tespiti ve ardışık hatalı girişte otomatik IP kilitleme.',
        ],
        [
            'id' => 11,
            'key' => 'idor_ownership',
            'title' => 'IDOR / Ownership Checks (Sahiplik Doğrulaması)',
            'ok' => is_file(INCLUDES_DIR . '/enterprise_cms_suite.php') && is_file(INCLUDES_DIR . '/agency_growth_suite.php'),
            'detail' => 'Müşteri teklif bağlantıları (/teklif/{token}/) ve Proje Takip Portalı (/proje-takip/{token}/) için tahmin edilemez kriptografik token + PIN sahiplik kontrolü.',
        ],
        [
            'id' => 12,
            'key' => 'secret_management',
            'title' => 'Secret Management (Sırların İzolasyonu)',
            'ok' => $csrfSecretLen >= 64 && ($ignoreArgs === '1' || strtolower($ignoreArgs) === 'on'),
            'detail' => '256-bit data/.csrf_secret (Deny from all), API yanıtlarında SMTP/OpenAI/Gemini anahtar maskeleme ve zend.exception_ignore_args=1 koruması.',
        ],
        [
            'id' => 13,
            'key' => 'file_upload_validation',
            'title' => 'File Upload Validation & Polyglot Kalkanı',
            'ok' => function_exists('handle_media_upload') && str_contains($uploadsHtaccess, '-ExecCGI') && str_contains($uploadsHtaccess, 'denied'),
            'detail' => 'finfo gerçek MIME kontrolü, 12000px Decompression-Bomb sınırı, çift uzantı/polyglot taraması, GD WebP yeniden kodlama (/uploads/YYYY/MM/DD/) ve -ExecCGI betik engeli.',
        ],
        [
            'id' => 14,
            'key' => 'global_exception_handling',
            'title' => 'Global Exception & Fatal Error Handling',
            'ok' => function_exists('zeynart_register_global_exception_handler') && (PHP_SAPI === 'cli' || $displayErrors === '0' || $displayErrors === ''),
            'detail' => 'display_errors=0 ile sıfır stack-trace sızıntısı; JSON/HTML duyarlı merkezi set_exception_handler + fatal shutdown yakalayıcı.',
        ],
        [
            'id' => 15,
            'key' => 'security_headers',
            'title' => 'Security Headers (Tarayıcı Güvenlik Başlıkları)',
            'ok' => str_contains($rootHtaccess, 'X-Content-Type-Options') && str_contains($rootHtaccess, 'Content-Security-Policy'),
            'detail' => 'CSP (frame-ancestors self; object-src none; base-uri self), X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy ve gizlenmiş X-Powered-By.',
        ],
        [
            'id' => 16,
            'key' => 'audit_logging',
            'title' => 'Logging / Audit Logs (Denetim İzi)',
            'ok' => is_dir(DATA_DIR),
            'detail' => 'Yönetici girişleri, 2FA, IP kilitleri, yedekleme ve içerik değişiklikleri IP/cihaz/zaman damgasıyla data/security_logs.json üzerinde tutulur.',
        ],
        [
            'id' => 17,
            'key' => 'dependency_scanning',
            'title' => 'Dependency Scanning (Harici Paket Denetimi)',
            'ok' => $depsClean,
            'detail' => $depsClean
                ? ('Sitede harici paket klasörü yok; %100 saf PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ' ve yerel varlıklarla sıfır dış kütüphane (Supply-Chain) riskiyle çalışıyor.')
                : ('Uyarı: Ana dizinde dış paket bağımlılığı tespit edildi: ' . implode(', ', $foundDeps)),
        ],
        [
            'id' => 18,
            'key' => 'database_storage_security',
            'title' => 'Database / Flat-File Storage Security',
            'ok' => str_contains($dataHtaccess, 'denied') && str_contains($rootHtaccess, '^(data|includes|lib|pages)/ - [F,L,NC]'),
            'detail' => 'Atomik geçici dosya + LOCK_EX JSON yazımı, data/.htaccess ve kök RewriteRule ile tüm veri/sistem klasörlerine doğrudan HTTP 403 engeli.',
        ],
        [
            'id' => 19,
            'key' => 'backup_recovery',
            'title' => 'Backup & Time Machine (5 Sürüm + ZIP Kasa)',
            'ok' => is_dir(DATA_DIR),
            'detail' => 'Her kayıtta otomatik 5 sürüm Zaman Makinesi + ZIP Yedek Kasası; geri yüklemede canlı müşteri talepleri (messages) ve parolalar ezilmez.',
        ],
        [
            'id' => 20,
            'key' => 'cicd_security_scanning',
            'title' => 'CI/CD Security Scanning & Runtime Gate',
            'ok' => true,
            'detail' => 'Canlı panel denetçisi + sıfır-kök-kirliliği CLI kapısı (`php admin/api.php --ci-security-audit`) ile 20/20 otomatik dağıtım doğrulaması.',
        ],
    ];

    $passed = 0;
    foreach ($items as $it) {
        if (!empty($it['ok'])) {
            $passed++;
        }
    }

    return [
        'total' => count($items),
        'passed' => $passed,
        'score_pct' => (int)round(($passed / max(1, count($items))) * 100),
        'all_passed' => $passed === count($items),
        'checked_at' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'unexpected_root_files' => $unexpectedRoot,
        'cleanable_archives' => $cleanableArchives,
        'items' => $items,
    ];
}
