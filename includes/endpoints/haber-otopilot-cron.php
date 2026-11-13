<?php
declare(strict_types=1);

/**
 * Zero-Cookie Google News Autopilot Cron / Webhook Endpoint
 * Usage in Plesk / cPanel Scheduled Tasks (e.g. twice daily at 09:30 and 15:30):
 * curl -fsSL "https://zeynart.com/api/cron/haber-otopilot?key=YOUR_CRON_SECRET"
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/seo.php';
require_once dirname(__DIR__) . '/translator.php';
require_once dirname(__DIR__) . '/studio_suite.php';
require_once dirname(__DIR__) . '/studio_pro_pack.php';
require_once dirname(__DIR__) . '/news_autopilot.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$store = load_store();
$cfg = zeynart_get_news_autopilot_config($store);
$providedKey = trim((string)($_GET['key'] ?? $_POST['key'] ?? ''));

if ($providedKey === '' || !hash_equals($cfg['cron_secret'], $providedKey)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Geçersiz cron güvenlik anahtarı.'], JSON_UNESCAPED_UNICODE);
    exit;
}

@set_time_limit(90);
$force = !empty($_GET['force']);
$res = zeynart_run_news_autopilot($store, ['force' => $force]);

echo json_encode([
    'ok' => (bool)($res['ok'] ?? false),
    'skipped' => (bool)($res['skipped'] ?? false),
    'reason' => $res['reason'] ?? null,
    'created_count' => (int)($res['created_count'] ?? 0),
    'message' => $res['message'] ?? ($res['error'] ?? ''),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
