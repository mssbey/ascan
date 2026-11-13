<?php
declare(strict_types=1);

/**
 * Zero-Cookie Cryptographic OTA Client Receiver Endpoint
 * Routed via index.php -> /api/ota-client?op=ping|purge_cache|push_update
 */

if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once INCLUDES_DIR . '/ota_hub_engine.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-LiteSpeed-Cache-Control: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$otaClientJson = static function (bool $ok, array $payload = [], int $status = 200): never {
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $otaClientJson(false, ['error' => 'OTA istemci ucu yalnızca imzalı POST isteklerini kabul eder.'], 405);
}

$op = trim((string)($_GET['op'] ?? 'ping'));
if (!in_array($op, ['ping', 'purge_cache', 'push_update'], true)) {
    $otaClientJson(false, ['error' => 'Tanımsız OTA istemci işlemi.'], 400);
}

if (!check_rate_limit('ota_client_' . $op, 40, 60)) {
    $otaClientJson(false, ['error' => 'İstek sınırı aşıldı. Lütfen biraz bekleyin.'], 429);
}

$cState = zeynart_ota_get_client_state();
if (empty($cState['connected']) || empty($cState['license_key']) || empty($cState['node_secret'])) {
    $otaClientJson(false, ['error' => 'Bu sitede OTA istemci bağlantısı aktif değil.'], 403);
}

$rawBody = (string)file_get_contents('php://input');
$auth = zeynart_ota_verify_auth_request($op, (string)$cState['license_key'], (string)$cState['node_secret'], $rawBody);
if (empty($auth['ok'])) {
    zeynart_ota_append_log('client_hmac_fail', 'Yetkisiz uzak OTA komutu reddedildi: ' . $auth['error'], 'error');
    $otaClientJson(false, ['error' => (string)$auth['error']], 401);
}

$bodyData = json_decode($rawBody, true);
if (!is_array($bodyData)) {
    $bodyData = [];
}

if ($op === 'ping') {
    $otaClientJson(true, [
        'installed_version' => zeynart_ota_current_version(),
        'php_version' => PHP_VERSION,
        'db_engine' => (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) ? 'mysql' : 'json',
        'admin_dir' => zeynart_ota_resolve_target_admin_rel_dir(ROOT_DIR, $cState),
        'admin_slug' => function_exists('zeynart_get_admin_slug') ? zeynart_get_admin_slug() : ADMIN_DIR_NAME,
        'sector_extensions' => function_exists('zeynart_get_sector_extensions_public_meta') ? zeynart_get_sector_extensions_public_meta() : [],
        'allow_remote_push' => !empty($cState['allow_remote_push']),
    ]);
}

if ($op === 'purge_cache') {
    zeynart_ota_post_update_housekeeping();
    if (!headers_sent()) {
        header('X-LiteSpeed-Purge: *');
    }
    zeynart_ota_append_log('remote_cache_purged', 'Merkezi Hub komutu ile LiteSpeed & OPcache önbelleği temizlendi.', 'ok');
    $otaClientJson(true, [
        'message' => 'LiteSpeed LSCache ve PHP OPcache başarıyla temizlendi.',
    ]);
}

if ($op === 'push_update') {
    if (empty($cState['allow_remote_push'])) {
        $otaClientJson(false, ['error' => 'Bu istemcide uzaktan otomatik güncelleme alma (Remote Push) kapalı.'], 403);
    }

    @set_time_limit(120);
    $rel = is_array($bodyData['release'] ?? null) ? $bodyData['release'] : null;
    if ($rel === null || empty($rel['version']) || empty($rel['sha256']) || empty($rel['signature'])) {
        $otaClientJson(false, ['error' => 'Eksik sürüm manifestosu veya kriptografik imza.'], 422);
    }

    $zipBytes = '';
    if (!empty($bodyData['package_b64']) && is_string($bodyData['package_b64'])) {
        $decZip = base64_decode($bodyData['package_b64'], true);
        if (is_string($decZip) && $decZip !== '') {
            $zipBytes = $decZip;
        }
    }

    if ($zipBytes === '') {
        $dlUrl = !empty($rel['download_url'])
            ? (string)$rel['download_url']
            : (rtrim((string)$cState['hub_url'], '/') . '/api/ota-hub?op=download&version=' . rawurlencode((string)$rel['version']));
        $dlReq = (string)json_encode(['op' => 'download', 'version' => (string)$rel['version'], 'channel' => (string)($rel['channel'] ?? 'stable')], JSON_UNESCAPED_SLASHES);
        $dlHeaders = zeynart_ota_build_auth_headers('download', (string)$cState['license_key'], (string)$cState['node_secret'], $dlReq);
        $dlResp = zeynart_ota_http_request($dlUrl, 'POST', $dlReq, $dlHeaders, 60);
        if (empty($dlResp['ok']) || $dlResp['body'] === '') {
            $otaClientJson(false, ['error' => 'Hub üzerinden paket indirilemedi: ' . ($dlResp['error'] ?? 'Bağlantı hatası')], 502);
        }
        $zipBytes = $dlResp['body'];
    }

    $tmpZip = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'zn_ota_push_' . bin2hex(random_bytes(6)) . '.zip';
    if (@file_put_contents($tmpZip, $zipBytes, LOCK_EX) === false) {
        $otaClientJson(false, ['error' => 'Geçici OTA arşivi sunucu diskine yazılamadı.'], 500);
    }

    $res = zeynart_ota_apply_zip_package($tmpZip, $rel, $cState);
    @unlink($tmpZip);

    if (empty($res['ok'])) {
        $otaClientJson(false, ['error' => (string)($res['error'] ?? 'OTA kurulum hatası.')], 422);
    }

    if (!headers_sent()) {
        header('X-LiteSpeed-Purge: *');
    }

    $otaClientJson(true, $res);
}
