<?php
declare(strict_types=1);

/**
 * Zero-Cookie Agency Uptime & SSL Control Tower Cron Endpoint (300+ Client Sites)
 * Usage in Plesk / cPanel Scheduled Tasks (every 5–15 minutes):
 * curl -fsSL "https://zeynart.com/api/cron/uptime?key=YOUR_CRON_SECRET"
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/studio_suite.php';
require_once dirname(__DIR__) . '/studio_pro_pack.php';
require_once dirname(__DIR__) . '/agency_powerhouse.php';
require_once INCLUDES_DIR . '/ota_hub_engine.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$store = load_store();
$cfg = zeynart_get_uptime_tower_config($store);
$providedKey = trim((string)($_GET['key'] ?? $_POST['key'] ?? ''));

if ($providedKey === '' || !hash_equals((string)$cfg['cron_secret'], $providedKey)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Geçersiz cron güvenlik anahtarı.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($cfg['enabled']) && empty($_GET['force'])) {
    echo json_encode(['ok' => true, 'skipped' => true, 'reason' => 'disabled'], JSON_UNESCAPED_UNICODE);
    exit;
}

@set_time_limit(75);
$batchLimit = max(5, min(60, (int)($_GET['limit'] ?? 35)));
$monitors = zeynart_load_uptime_monitors($store);

$targetIds = [];
if (!empty($_GET['force'])) {
    $sorted = [];
    foreach ($monitors as $m) {
        if (($m['enabled'] ?? true) !== false) {
            $sorted[] = ['id' => (string)$m['id'], 'ts' => (int)($m['last_checked_ts'] ?? 0)];
        }
    }
    usort($sorted, static fn(array $a, array $b): int => $a['ts'] <=> $b['ts']);
    $targetIds = array_column(array_slice($sorted, 0, $batchLimit), 'id');
}

$res = zeynart_probe_uptime_batch($monitors, $targetIds, $store, $batchLimit, false);
$summary = zeynart_compute_uptime_summary($monitors, $cfg);
$otaBg = zeynart_ota_maybe_background_autoupdate();

echo json_encode([
    'ok' => true,
    'checked_count' => (int)($res['checked_count'] ?? 0),
    'alerts_sent' => (int)($res['alerts_sent'] ?? 0),
    'summary' => $summary,
    'ota_background' => $otaBg !== null ? ['checked' => true, 'ok' => !empty($otaBg['ok'])] : null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
