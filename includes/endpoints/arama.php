<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/enterprise_cms_suite.php';

// Cookie-free (Zero-Cookie Load) Instant Search API
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-LiteSpeed-Cache-Control: no-cache');
header('X-Robots-Tag: noindex, nofollow');

if (!check_rate_limit('instant_search', 90, 60)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => 'Çok sık arama isteği gönderildi.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawBody = (string)file_get_contents('php://input');
$jsonIn = $rawBody !== '' ? json_decode($rawBody, true) : null;
$params = is_array($jsonIn) ? array_merge($_GET, $_POST, $jsonIn) : array_merge($_GET, $_POST);

$q = trim((string)($params['q'] ?? ''));
$lang = strtolower(trim((string)($params['lang'] ?? 'tr')));
if (!in_array($lang, ['tr', 'en', 'de', 'ru'], true)) {
    $lang = 'tr';
}
zeynart_set_lang($lang);

$limit = max(3, min(25, (int)($params['limit'] ?? 12)));
$store = load_store();
if ($lang !== 'tr') {
    $store = zeynart_localize_store($store, $lang);
}

$t0 = microtime(true);
$searchRes = zeynart_instant_search($q, $store, $lang, $limit);
$elapsedMs = round((microtime(true) - $t0) * 1000, 1);

$recordParam = isset($params['record']) ? (string)$params['record'] : '';
$shouldRecord = ($recordParam === '1') || ($recordParam === '' && mb_strlen($q, 'UTF-8') >= 3);
if ($shouldRecord && mb_strlen($q, 'UTF-8') >= 2) {
    try {
        zeynart_record_search_query($q, (int)($searchRes['total'] ?? 0), $lang);
    } catch (\Throwable $_) {
    }
}

echo json_encode([
    'ok' => true,
    'query' => $searchRes['query'],
    'results' => $searchRes['results'],
    'total' => $searchRes['total'],
    'elapsed_ms' => $elapsedMs,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
