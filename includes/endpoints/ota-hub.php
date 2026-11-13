<?php
declare(strict_types=1);

/**
 * Zero-Cookie Cryptographic OTA Distribution & License Hub Endpoint
 * Routed via index.php -> /api/ota-hub?op=handshake|manifest|download
 */

if (!defined('ROOT_DIR')) {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once INCLUDES_DIR . '/ota_hub_engine.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-LiteSpeed-Cache-Control: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$otaRespondJson = static function (bool $ok, array $payload = [], int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $otaRespondJson(false, ['error' => 'OTA Hub yalnızca imzalı POST isteklerini kabul eder.'], 405);
}

$op = trim((string)($_GET['op'] ?? 'manifest'));
if (!in_array($op, ['handshake', 'manifest', 'download'], true)) {
    $otaRespondJson(false, ['error' => 'Tanımsız OTA Hub işlemi.'], 400);
}

if (!check_rate_limit('ota_hub_' . $op, 90, 60)) {
    $otaRespondJson(false, ['error' => 'Çok fazla OTA isteği gönderildi. Lütfen 1 dakika bekleyin.'], 429);
}

$hub = zeynart_ota_get_hub_state(true);
if (empty($hub['enabled'])) {
    $otaRespondJson(false, ['error' => 'Merkezi OTA Dağıtım Hub şu anda devre dışı.'], 503);
}

$incomingLic = trim((string)($_SERVER['HTTP_X_ZEYNART_LICENSE'] ?? ''));
$found = zeynart_ota_find_node_by_license_or_id($hub, $incomingLic);
if ($found === null) {
    zeynart_ota_append_log('hub_auth_fail', 'Tanımsız lisans anahtarı ile OTA Hub erişim denemesi (' . substr($incomingLic, 0, 12) . ').', 'warn');
    $otaRespondJson(false, ['error' => 'Geçersiz veya kayıtlı olmayan lisans anahtarı.'], 401);
}

$nodeIdx = $found['index'];
$node = $found['node'];
$rawBody = (string)file_get_contents('php://input');

$auth = zeynart_ota_verify_auth_request($op, (string)$node['license_key'], (string)$node['node_secret'], $rawBody);
if (empty($auth['ok'])) {
    zeynart_ota_append_log('hub_hmac_fail', $node['domain'] . ' için HMAC imza doğrulaması reddedildi: ' . $auth['error'], 'error');
    $otaRespondJson(false, ['error' => (string)$auth['error']], 401);
}

if (($node['license_status'] ?? 'active') !== 'active') {
    $otaRespondJson(false, ['error' => 'Bu sitenin OTA güncelleme lisansı askıya alınmıştır.'], 403);
}

$bodyData = json_decode($rawBody, true);
if (!is_array($bodyData)) {
    $bodyData = [];
}

if ($op === 'handshake' || $op === 'manifest') {
    if (!empty($bodyData['installed_version'])) {
        $hub['nodes'][$nodeIdx]['installed_version'] = mb_substr(trim((string)$bodyData['installed_version']), 0, 32, 'UTF-8');
    }
    if (!empty($bodyData['php_version'])) {
        $hub['nodes'][$nodeIdx]['php_version'] = mb_substr(trim((string)$bodyData['php_version']), 0, 24, 'UTF-8');
    }
    if (!empty($bodyData['db_engine'])) {
        $hub['nodes'][$nodeIdx]['db_engine'] = mb_substr(trim((string)$bodyData['db_engine']), 0, 24, 'UTF-8');
    }
    if (!empty($bodyData['admin_dir'])) {
        $hub['nodes'][$nodeIdx]['admin_dir'] = mb_substr(trim((string)$bodyData['admin_dir']), 0, 48, 'UTF-8');
    }
    if (!empty($bodyData['admin_slug'])) {
        $hub['nodes'][$nodeIdx]['admin_slug'] = mb_substr(trim((string)$bodyData['admin_slug']), 0, 48, 'UTF-8');
    }
    if (isset($bodyData['sector_extensions']) && is_array($bodyData['sector_extensions'])) {
        $hub['nodes'][$nodeIdx]['sector_extensions'] = array_slice($bodyData['sector_extensions'], 0, 12);
        $secNames = array_values(array_filter(array_map(static fn($e) => trim((string)($e['title'] ?? '')), $bodyData['sector_extensions'])));
        $hub['nodes'][$nodeIdx]['sector_summary'] = $secNames !== [] ? implode(', ', $secNames) : '';
    }
    $hub['nodes'][$nodeIdx]['health_status'] = 'online';
    $hub['nodes'][$nodeIdx]['last_seen_at'] = date('Y-m-d H:i:s');
    zeynart_ota_save_hub_state($hub);

    if ($op === 'handshake') {
        zeynart_ota_append_log(
            'node_handshake',
            sprintf('%s istemcisi Hub ile el sıkıştı (Sürüm: v%s, PHP %s).', $node['domain'], $hub['nodes'][$nodeIdx]['installed_version'], $hub['nodes'][$nodeIdx]['php_version']),
            'ok'
        );
    }

    $reqChannel = in_array($bodyData['channel'] ?? ($node['channel'] ?? 'stable'), ['stable', 'beta'], true)
        ? (string)($bodyData['channel'] ?? ($node['channel'] ?? 'stable'))
        : 'stable';

    $latest = zeynart_ota_find_latest_release($hub, $reqChannel);
    $releaseOut = null;
    if ($latest !== null) {
        $releaseOut = [
            'version' => (string)$latest['version'],
            'channel' => (string)$latest['channel'],
            'title' => (string)($latest['title'] ?? ''),
            'changelog' => $latest['changelog'] ?? [],
            'min_php' => (string)($latest['min_php'] ?? '8.1.0'),
            'files_count' => (int)($latest['files_count'] ?? 0),
            'size_bytes' => (int)($latest['size_bytes'] ?? 0),
            'size_kb' => (float)($latest['size_kb'] ?? 0),
            'sha256' => (string)$latest['sha256'],
            'signature' => (string)$latest['signature'],
            'key_fingerprint' => (string)($latest['key_fingerprint'] ?? $hub['key_fingerprint']),
            'created_at' => (string)($latest['created_at'] ?? ''),
            'download_url' => SITE_URL . '/api/ota-hub?op=download&version=' . rawurlencode((string)$latest['version']) . '&channel=' . rawurlencode((string)$latest['channel']),
        ];
    }

    $otaRespondJson(true, [
        'hub_version' => ZEYNART_STUDIO_VERSION,
        'public_key_pem' => (string)$hub['public_key_pem'],
        'key_fingerprint' => (string)$hub['key_fingerprint'],
        'release' => $releaseOut,
    ]);
}

if ($op === 'download') {
    $wantVer = trim((string)($_GET['version'] ?? ($bodyData['version'] ?? '')));
    $wantChan = trim((string)($_GET['channel'] ?? ($bodyData['channel'] ?? ($node['channel'] ?? 'stable'))));

    $targetRel = null;
    if ($wantVer !== '') {
        foreach ($hub['releases'] as $r) {
            if (($r['version'] ?? '') === $wantVer && ($wantChan === '' || ($r['channel'] ?? 'stable') === $wantChan)) {
                $targetRel = $r;
                break;
            }
        }
    }
    if ($targetRel === null) {
        $targetRel = zeynart_ota_find_latest_release($hub, $wantChan ?: 'stable');
    }
    if ($targetRel === null || empty($targetRel['filename'])) {
        $otaRespondJson(false, ['error' => 'İstenen sürüm paketi Hub üzerinde bulunamadı.'], 404);
    }

    $zipAbs = zeynart_ota_releases_dir() . '/' . basename((string)$targetRel['filename']);
    if (!is_file($zipAbs)) {
        $otaRespondJson(false, ['error' => 'Sürüm arşiv dosyası bulunamadı.'], 404);
    }

    zeynart_ota_append_log('package_downloaded', sprintf('%s istemcisi v%s paketini indirdi.', $node['domain'], $targetRel['version']), 'info');

    $size = (int)@filesize($zipAbs);
    http_response_code(200);
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . $size);
    header('X-Zeynart-Package-Version: ' . $targetRel['version']);
    header('X-Zeynart-Package-Sha256: ' . $targetRel['sha256']);
    header('X-Zeynart-Package-Signature: ' . $targetRel['signature']);
    readfile($zipAbs);
    exit;
}
