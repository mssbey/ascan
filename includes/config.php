<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Istanbul');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Production error & Global Exception shield: never leak server file paths, arguments, or stack traces to visitors
@ini_set('zend.exception_ignore_args', '1');

function zeynart_is_https_request(): bool
{
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );
}

function zeynart_register_global_exception_handler(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('log_errors', '1');

    $renderSafe500 = static function (): void {
        if (!headers_sent()) {
            http_response_code(500);
            @header_remove('X-Powered-By');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        $reqUri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $isApiReq = str_contains($reqUri, '/api/') || str_contains($reqUri, 'api.php') || str_contains($accept, 'application/json');
        if ($isApiReq) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode([
                'ok' => false,
                'error' => 'Sunucu tarafında beklenmeyen geçici bir durum oluştu. Lütfen tekrar deneyin.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Geçici Hizmet Kesintisi | Zeynart</title>'
            . '<style>body{margin:0;min-height:100dvh;display:grid;place-items:center;background:#061117;color:#eaf4f7;font-family:system-ui,-apple-system,sans-serif;padding:24px}'
            . '.box{max-width:480px;padding:32px;border-radius:20px;background:rgba(14,32,42,.92);border:1px solid rgba(45,212,191,.22);text-align:center}'
            . 'h1{margin:0 0 10px;font-size:22px;color:#ffffff}p{margin:0 0 18px;font-size:14.5px;line-height:1.6;color:#9fb6c1}'
            . 'a{display:inline-block;padding:10px 20px;border-radius:999px;background:#14b8a6;color:#fff;text-decoration:none;font-weight:700;font-size:14px}</style></head>'
            . '<body><div class="box"><h1>Sayfa Şu Anda Yüklenemedi</h1><p>İsteğiniz işlenirken geçici bir teknik durum oluştu. Lütfen sayfayı yenileyin veya ana sayfaya dönün.</p><a href="/">Ana Sayfaya Dön</a></div></body></html>';
    };

    set_exception_handler(static function (\Throwable $ex) use ($renderSafe500): void {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        $safeMsg = sprintf(
            '[ZeynartUncaught] %s: %s in %s:%d',
            get_class($ex),
            $ex->getMessage(),
            basename($ex->getFile()),
            $ex->getLine()
        );
        @error_log($safeMsg);
        $renderSafe500();
        exit;
    });

    register_shutdown_function(static function () use ($renderSafe500): void {
        $err = error_get_last();
        if (!is_array($err)) {
            return;
        }
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int)($err['type'] ?? 0), $fatalTypes, true)) {
            return;
        }
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        @error_log(sprintf(
            '[ZeynartFatal] %s in %s:%d',
            (string)($err['message'] ?? ''),
            basename((string)($err['file'] ?? '')),
            (int)($err['line'] ?? 0)
        ));
        $renderSafe500();
    });
}

zeynart_register_global_exception_handler();

define('ROOT_DIR', str_replace('\\', '/', dirname(__DIR__)));
define('DATA_DIR', ROOT_DIR . '/data');
define('POSTS_DIR', DATA_DIR . '/posts');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('IMAGES_DIR', ROOT_DIR . '/images');
define('PAGES_DIR', ROOT_DIR . '/pages');
define('INCLUDES_DIR', ROOT_DIR . '/includes');
define('LIB_DIR', ROOT_DIR . '/lib');

function zeynart_detect_physical_admin_dir_name(string $rootDir): string
{
    $reserved = [
        '.well-known' => true,
        'assets' => true,
        'data' => true,
        'images' => true,
        'includes' => true,
        'lib' => true,
        'pages' => true,
        'uploads' => true,
    ];
    $normRoot = rtrim(str_replace('\\', '/', $rootDir), '/');

    // 1. Explicit hint file in data/.admin_dir_name (if physical directory exists)
    $hintFile = $normRoot . '/data/.admin_dir_name';
    if (is_file($hintFile)) {
        $hint = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string)@file_get_contents($hintFile))) ?? '';
        if (
            $hint !== ''
            && !isset($reserved[strtolower($hint)])
            && is_file($normRoot . '/' . $hint . '/index.php')
            && is_file($normRoot . '/' . $hint . '/api.php')
        ) {
            return $hint;
        }
    }

    // 2. Direct script execution inside <root>/<custom_admin>/(index|api).php
    $scriptFile = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if ($scriptFile !== '' && str_starts_with(strtolower($scriptFile), strtolower($normRoot . '/'))) {
        $relScript = substr($scriptFile, strlen($normRoot) + 1);
        $parts = explode('/', $relScript);
        if (count($parts) === 2 && in_array(strtolower($parts[1]), ['index.php', 'api.php'], true)) {
            $candDir = $parts[0];
            if (
                $candDir !== ''
                && $candDir[0] !== '.'
                && !isset($reserved[strtolower($candDir)])
                && is_file($normRoot . '/' . $candDir . '/index.php')
                && is_file($normRoot . '/' . $candDir . '/api.php')
            ) {
                return $candDir;
            }
        }
    }

    // 3. Standard /admin directory
    if (is_file($normRoot . '/admin/index.php') && is_file($normRoot . '/admin/api.php')) {
        return 'admin';
    }

    // 4. Auto-discover renamed physical admin directory (e.g. /x/, /yonetim/, /panel/)
    $entries = @scandir($normRoot) ?: [];
    foreach ($entries as $entry) {
        if ($entry === '' || $entry[0] === '.' || isset($reserved[strtolower($entry)])) {
            continue;
        }
        $candPath = $normRoot . '/' . $entry;
        if (
            is_dir($candPath)
            && is_file($candPath . '/index.php')
            && is_file($candPath . '/api.php')
            && (is_file($candPath . '/admin.js') || is_file($candPath . '/admin.css'))
        ) {
            if (is_dir($normRoot . '/data')) {
                @file_put_contents($hintFile, $entry, LOCK_EX);
            }
            return $entry;
        }
    }

    return 'admin';
}

define('ADMIN_DIR_NAME', zeynart_detect_physical_admin_dir_name(ROOT_DIR));
define('ADMIN_DIR', ROOT_DIR . '/' . ADMIN_DIR_NAME);

// Detect protocol, host, and optional subdirectory base path
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443')
    || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
);
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'zeynart.com';
$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
if (PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli') {
    $detectedBase = '';
} else {
    $detectedBase = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $admDirSuffix = '/' . strtolower(ADMIN_DIR_NAME);
    if (str_ends_with(strtolower($detectedBase), $admDirSuffix)) {
        $detectedBase = substr($detectedBase, 0, -strlen($admDirSuffix));
    } elseif (str_ends_with(strtolower($detectedBase), '/admin')) {
        $detectedBase = substr($detectedBase, 0, -6);
    }
    if ($detectedBase === '/' || $detectedBase === '.') {
        $detectedBase = '';
    }
}

define('BASE_PATH', $detectedBase);
define('SITE_URL', $scheme . '://' . $host . BASE_PATH);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function nl2br_e(string $text): string
{
    return nl2br(e($text), false);
}

function url(string $path = '/', ?string $forceLang = null): string
{
    return lang_url($path, $forceLang);
}

function asset_url(string $relPath): string
{
    static $resolvedCache = [];
    if (isset($resolvedCache[$relPath])) {
        return $resolvedCache[$relPath];
    }
    $clean = '/' . ltrim($relPath, '/');
    if (
        (str_ends_with($clean, '.js') && !str_ends_with($clean, '.min.js'))
        || (str_ends_with($clean, '.css') && !str_ends_with($clean, '.min.css'))
    ) {
        $minClean = preg_replace('/\.(js|css)$/', '.min.$1', $clean) ?? $clean;
        $absOrig = ROOT_DIR . $clean;
        $absMin = ROOT_DIR . $minClean;
        if (is_file($absMin) && (!is_file($absOrig) || (int)@filemtime($absMin) >= (int)@filemtime($absOrig))) {
            $clean = $minClean;
        }
    }
    $abs = ROOT_DIR . $clean;
    if (is_file($abs)) {
        $ver = substr((string)(@md5_file($abs) ?: @filemtime($abs) ?: '1'), 0, 10);
    } else {
        $ver = '1';
    }
    return $resolvedCache[$relPath] = (BASE_PATH . $clean . '?v=' . $ver);
}

function canonical_url(string $path = '/', ?array $store = null, ?string $forceLang = null): string
{
    return lang_canonical_url($path, $store, $forceLang);
}

function slugify(string $text): string
{
    $map = [
        'ş' => 's', 'Ş' => 's',
        'ı' => 'i', 'İ' => 'i', 'I' => 'i',
        'ğ' => 'g', 'Ğ' => 'g',
        'ü' => 'u', 'Ü' => 'u',
        'ö' => 'o', 'Ö' => 'o',
        'ç' => 'c', 'Ç' => 'c',
        'â' => 'a', 'Â' => 'a',
        'î' => 'i', 'Î' => 'i',
        'û' => 'u', 'Û' => 'u',
    ];
    $text = strtr(trim($text), $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'icerik-' . date('YmdHis');
}

function format_tr_date(string $isoDate): string
{
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
    ];
    $ts = strtotime($isoDate);
    if ($ts === false) {
        return $isoDate;
    }
    $day = (int)date('j', $ts);
    $month = $months[(int)date('n', $ts)] ?? '';
    $year = date('Y', $ts);
    return trim("$day $month $year");
}

function estimate_reading_time(string $html): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    if ($plain === '') {
        return '2 dk';
    }
    $words = count(explode(' ', $plain));
    $mins = max(1, (int)ceil($words / 190));
    return $mins . ' dk';
}

function zeynart_normalize_admin_slug(string $raw): string
{
    $map = [
        'ş' => 's', 'Ş' => 's',
        'ı' => 'i', 'İ' => 'i', 'I' => 'i',
        'ğ' => 'g', 'Ğ' => 'g',
        'ü' => 'u', 'Ü' => 'u',
        'ö' => 'o', 'Ö' => 'o',
        'ç' => 'c', 'Ç' => 'c',
        'â' => 'a', 'Â' => 'a',
        'î' => 'i', 'Î' => 'i',
        'û' => 'u', 'Û' => 'u',
    ];
    $clean = strtr(trim($raw, " \t\n\r\0\x0B/\\"), $map);
    $clean = mb_strtolower($clean, 'UTF-8');
    $clean = preg_replace('/[^a-z0-9_-]+/', '-', $clean) ?? '';
    $clean = preg_replace('/-{2,}/', '-', $clean) ?? '';
    $clean = trim($clean, '-_');
    if (strlen($clean) > 48) {
        $clean = trim(substr($clean, 0, 48), '-_');
    }
    return $clean;
}

function zeynart_get_admin_slug(?array $store = null): string
{
    $physNorm = zeynart_normalize_admin_slug(defined('ADMIN_DIR_NAME') ? ADMIN_DIR_NAME : 'admin');
    $defaultSlug = ($physNorm !== '' && !ctype_digit($physNorm)) ? $physNorm : 'admin';

    if ($store !== null) {
        $raw = (string)($store['security']['admin_slug'] ?? '');
        $norm = zeynart_normalize_admin_slug($raw);
        if ($norm === '' || $norm === 'admin' || ctype_digit($norm)) {
            return $defaultSlug;
        }
        return $norm;
    }
    if (isset($GLOBALS['__zeynart_active_admin_slug']) && is_string($GLOBALS['__zeynart_active_admin_slug']) && $GLOBALS['__zeynart_active_admin_slug'] !== '') {
        return $GLOBALS['__zeynart_active_admin_slug'];
    }
    $flagFile = DATA_DIR . '/.custom_admin_slug';
    if (is_file($flagFile)) {
        $normFlag = zeynart_normalize_admin_slug((string)@file_get_contents($flagFile));
        if ($normFlag !== '' && $normFlag !== 'admin' && !ctype_digit($normFlag)) {
            return $GLOBALS['__zeynart_active_admin_slug'] = $normFlag;
        }
    }
    return $GLOBALS['__zeynart_active_admin_slug'] = $defaultSlug;
}

function zeynart_admin_url(string $subPath = '/', ?array $store = null): string
{
    $slug = zeynart_get_admin_slug($store);
    $trim = ltrim($subPath, '/');
    if ($trim === '') {
        return BASE_PATH . '/' . $slug . '/';
    }
    return BASE_PATH . '/' . $slug . '/' . $trim;
}

require_once __DIR__ . '/mysql_engine.php';
require_once __DIR__ . '/i18n.php';
