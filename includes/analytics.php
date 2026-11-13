<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

/**
 * ZEYNART KVKK ZERO-COOKIE CANLI ZİYARETÇİ & TRAFİK ANALİTİK MOTORU
 * - Sıfır Çerez (Set-Cookie göndermez, tarayıcıya izleyici bırakmaz, %100 KVKK uyumlu)
 * - Kişisel IP adresi saklamaz (maskelenmiş IP + günlük dönen HMAC özetiyle tekil kişi sayar)
 * - Anlık Sitedeki Ziyaretçiler (Son 3 dk nabız takibi), Son 30 Gün / 7 Gün / 90 Gün / Bugün serileri
 * - En çok gezilen sayfalar, trafik kaynakları, cihaz/tarayıcı/dil/ülke dağılımı ve bot ayrıştırması
 */

const ZEYNART_LIVE_WINDOW_SECONDS = 180;
const ZEYNART_STATS_RETENTION_DAYS = 92;

function zeynart_analytics_dir(): string
{
    $dir = DATA_DIR . '/analytics';
    if (!zeynart_is_sql_active() && !is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function zeynart_mask_ip_for_privacy(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
            return implode('.', $parts);
        }
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $packed = @inet_pton($ip);
        if (is_string($packed) && strlen($packed) === 16) {
            $masked = substr($packed, 0, 6) . str_repeat("\0", 10);
            $str = @inet_ntop($masked);
            if (is_string($str)) {
                return $str;
            }
        }
    }
    return '0.0.0.0';
}

function zeynart_visitor_daily_hash(string $ip, string $ua): string
{
    $maskedIp = zeynart_mask_ip_for_privacy($ip);
    $normUa = substr(strtolower(trim($ua)), 0, 220);
    $acceptLang = substr(strtolower(trim((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))), 0, 32);
    $payload = date('Y-m-d') . '|' . $maskedIp . '|' . $normUa . '|' . $acceptLang;
    return substr(hash_hmac('sha256', $payload, zeynart_csrf_secret()), 0, 16);
}

/**
 * @return array{kind: 'human'|'search_bot'|'ai_bot'|'ignore'}
 */
function zeynart_classify_user_agent(string $ua): array
{
    $forceTestVisit = (($_SERVER['HTTP_X_ZEYNART_TEST_VISIT'] ?? '') === '1')
        || (($_GET['_ztest_visit'] ?? '') === '1');

    if ($forceTestVisit) {
        return ['kind' => 'human'];
    }

    $trimUa = trim($ua);
    if ($trimUa === '' || strlen($trimUa) < 12) {
        return ['kind' => 'ignore'];
    }

    $low = strtolower($trimUa);

    // Automated headless test browsers & CLI tools -> ignore
    foreach (['headlesschrome', 'phantomjs', 'puppeteer', 'playwright', 'cypress', 'selenium', 'lighthouse', 'pagespeed', 'gtmetrix', 'pingdom', 'uptimerobot', 'curl/', 'wget/', 'python-requests', 'go-http-client', 'axios/', 'node-fetch'] as $ign) {
        if (str_contains($low, $ign)) {
            return ['kind' => 'ignore'];
        }
    }

    // AI Search & LLM Crawlers
    foreach (['gptbot', 'oai-searchbot', 'chatgpt-user', 'claudebot', 'anthropic-ai', 'perplexitybot', 'google-extended', 'applebot-extended', 'bytespider', 'cohere-ai', 'meta-externalagent', 'youbot'] as $aiBot) {
        if (str_contains($low, $aiBot)) {
            return ['kind' => 'ai_bot'];
        }
    }

    // Search Engine & Social Preview Crawlers
    foreach (['googlebot', 'bingbot', 'yandexbot', 'yandex.com/bots', 'duckduckbot', 'baiduspider', 'slurp', 'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'petalbot', 'facebookexternalhit', 'facebot', 'twitterbot', 'linkedinbot', 'whatsapp', 'telegrambot', 'discordbot', 'pinterestbot', 'applebot', 'crawler', 'spider', 'bot/'] as $sBot) {
        if (str_contains($low, $sBot)) {
            return ['kind' => 'search_bot'];
        }
    }

    return ['kind' => 'human'];
}

function zeynart_detect_device_type(string $ua): string
{
    $low = strtolower($ua);
    if (preg_match('/(ipad|tablet|playbook|silk)|(android(?!.*mobile))/i', $low)) {
        return 'tablet';
    }
    if (preg_match('/(mobi|iphone|ipod|android.*mobile|windows phone|opera mini|iemobile)/i', $low)) {
        return 'mobile';
    }
    return 'desktop';
}

function zeynart_detect_browser_name(string $ua): string
{
    $low = strtolower($ua);
    if (str_contains($low, 'edg/') || str_contains($low, 'edga/') || str_contains($low, 'edgios/')) {
        return 'Edge';
    }
    if (str_contains($low, 'opr/') || str_contains($low, 'opera')) {
        return 'Opera';
    }
    if (str_contains($low, 'samsungbrowser')) {
        return 'Samsung Internet';
    }
    if (str_contains($low, 'yabrowser')) {
        return 'Yandex';
    }
    if (str_contains($low, 'firefox/') || str_contains($low, 'fxios/')) {
        return 'Firefox';
    }
    if (str_contains($low, 'chrome/') || str_contains($low, 'crios/')) {
        return 'Chrome';
    }
    if (str_contains($low, 'safari/')) {
        return 'Safari';
    }
    return 'Diğer';
}

function zeynart_detect_country_code(string $siteLang = 'tr'): string
{
    foreach (['HTTP_CF_IPCOUNTRY', 'HTTP_X_COUNTRY_CODE', 'GEOIP_COUNTRY_CODE'] as $hdr) {
        $val = strtoupper(trim((string)($_SERVER[$hdr] ?? '')));
        if (preg_match('/^[A-Z]{2}$/', $val) && $val !== 'XX' && $val !== 'T1') {
            return $val;
        }
    }
    $accept = trim((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    if ($accept !== '') {
        if (preg_match('/^[a-z]{2,3}[_-]([A-Za-z]{2})\b/', $accept, $m)) {
            return strtoupper($m[1]);
        }
        $prefix = strtolower(substr($accept, 0, 2));
        $langToCountry = [
            'tr' => 'TR',
            'de' => 'DE',
            'ru' => 'RU',
            'en' => 'US',
            'az' => 'AZ',
            'nl' => 'NL',
            'fr' => 'FR',
            'ar' => 'AE',
        ];
        if (isset($langToCountry[$prefix])) {
            return $langToCountry[$prefix];
        }
    }
    return match ($siteLang) {
        'de' => 'DE',
        'ru' => 'RU',
        'en' => 'US',
        default => 'TR',
    };
}

/**
 * @return array{source: string, is_internal: bool}
 */
function zeynart_detect_traffic_source(string $referrer, string $queryString = ''): array
{
    if ($queryString !== '') {
        parse_str($queryString, $qp);
        $utm = strtolower(trim((string)($qp['utm_source'] ?? '')));
        if ($utm !== '') {
            if (str_contains($utm, 'google')) return ['source' => 'Google Arama', 'is_internal' => false];
            if (str_contains($utm, 'instagram') || $utm === 'ig') return ['source' => 'Instagram', 'is_internal' => false];
            if (str_contains($utm, 'linkedin')) return ['source' => 'LinkedIn', 'is_internal' => false];
            if (str_contains($utm, 'facebook') || $utm === 'fb' || $utm === 'meta') return ['source' => 'Facebook', 'is_internal' => false];
            if (str_contains($utm, 'twitter') || $utm === 'x') return ['source' => 'X (Twitter)', 'is_internal' => false];
            if (str_contains($utm, 'whatsapp') || $utm === 'wa') return ['source' => 'WhatsApp', 'is_internal' => false];
            if (str_contains($utm, 'chatgpt') || str_contains($utm, 'openai') || str_contains($utm, 'perplexity')) {
                return ['source' => 'Yapay Zeka (AI)', 'is_internal' => false];
            }
            return ['source' => ucfirst(substr(preg_replace('/[^a-z0-9._-]/i', '', $utm) ?? 'Kampanya', 0, 28)), 'is_internal' => false];
        }
    }

    $ref = trim($referrer);
    if ($ref === '') {
        return ['source' => 'Direkt Giriş', 'is_internal' => false];
    }

    $refHost = strtolower((string)(parse_url($ref, PHP_URL_HOST) ?: ''));
    $refHost = preg_replace('/^www\./', '', $refHost) ?? $refHost;
    if ($refHost === '') {
        return ['source' => 'Direkt Giriş', 'is_internal' => false];
    }

    $curHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $curHost = preg_replace('/:\d+$/', '', $curHost) ?? $curHost;
    $curHost = preg_replace('/^www\./', '', $curHost) ?? $curHost;

    if ($refHost === $curHost || in_array($refHost, ['localhost', '127.0.0.1', 'zeynart.com'], true)) {
        return ['source' => 'Site İçi Gezinme', 'is_internal' => true];
    }

    if (str_contains($refHost, 'google.')) return ['source' => 'Google Arama', 'is_internal' => false];
    if (str_contains($refHost, 'yandex.') || str_contains($refHost, 'ya.ru')) return ['source' => 'Yandex Arama', 'is_internal' => false];
    if (str_contains($refHost, 'bing.com') || str_contains($refHost, 'duckduckgo.com') || str_contains($refHost, 'yahoo.com')) {
        return ['source' => 'Bing / Diğer Arama', 'is_internal' => false];
    }
    if (str_contains($refHost, 'instagram.com')) return ['source' => 'Instagram', 'is_internal' => false];
    if (str_contains($refHost, 'linkedin.com') || $refHost === 'lnkd.in') return ['source' => 'LinkedIn', 'is_internal' => false];
    if (str_contains($refHost, 'facebook.com') || $refHost === 'fb.com') return ['source' => 'Facebook', 'is_internal' => false];
    if (str_contains($refHost, 'twitter.com') || $refHost === 'x.com' || $refHost === 't.co') return ['source' => 'X (Twitter)', 'is_internal' => false];
    if (str_contains($refHost, 'whatsapp.com') || $refHost === 'wa.me') return ['source' => 'WhatsApp', 'is_internal' => false];
    if (str_contains($refHost, 'youtube.com') || $refHost === 'youtu.be') return ['source' => 'YouTube', 'is_internal' => false];
    if (str_contains($refHost, 'chatgpt.com') || str_contains($refHost, 'openai.com') || str_contains($refHost, 'perplexity.ai') || str_contains($refHost, 'claude.ai') || str_contains($refHost, 'gemini.google.com') || str_contains($refHost, 'copilot.microsoft.com')) {
        return ['source' => 'Yapay Zeka (AI)', 'is_internal' => false];
    }

    return ['source' => substr($refHost, 0, 38), 'is_internal' => false];
}

function zeynart_normalize_tracked_path(string $rawPath, string $lang = 'tr'): string
{
    $p = '/' . trim((string)(parse_url($rawPath, PHP_URL_PATH) ?: '/'), '/');
    if ($p !== '/') {
        $p .= '/';
    }
    if ($lang !== 'tr' && in_array($lang, ['en', 'de', 'ru'], true) && !str_starts_with($p, '/' . $lang . '/')) {
        $p = '/' . $lang . ($p === '/' ? '/' : $p);
    }
    return substr($p, 0, 160);
}

function zeynart_clean_tracked_title(string $rawTitle, string $fallbackPath): string
{
    $t = trim(strip_tags($rawTitle));
    $t = preg_replace('/\s*[|—–-]\s*ZEYNART.*$/iu', '', $t) ?? $t;
    $t = trim($t);
    if ($t === '') {
        return $fallbackPath === '/' ? 'Ana Sayfa' : $fallbackPath;
    }
    return mb_substr($t, 0, 85, 'UTF-8');
}

function zeynart_default_stats_doc(): array
{
    return [
        'version' => 1,
        'totals' => [
            'views' => 0,
            'uniques' => 0,
            'bot_hits' => 0,
            'ai_bot_hits' => 0,
            'since_date' => date('Y-m-d'),
        ],
        'days' => [],
        'recent_events' => [],
    ];
}

function zeynart_default_day_bucket(): array
{
    return [
        'views' => 0,
        'uniques' => 0,
        'visitors' => [],
        'hours' => array_fill(0, 24, 0),
        'pages' => [],
        'referrers' => [],
        'devices' => ['desktop' => 0, 'mobile' => 0, 'tablet' => 0],
        'browsers' => [],
        'langs' => ['tr' => 0, 'en' => 0, 'de' => 0, 'ru' => 0],
        'countries' => [],
    ];
}

/**
 * Safely reads, mutates via callback, and atomically writes a JSON file under flock(LOCK_EX).
 */
function zeynart_mutate_json_locked(string $filePath, array $defaultDoc, callable $mutator): array
{
    if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
        $desc = zeynart_sql_classify_data_path($filePath);
        if ($desc !== null && $desc['type'] === 'analytics') {
            return zeynart_sql_analytics_mutate($desc['key'], $defaultDoc, $mutator);
        }
    }
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $fp = @fopen($filePath, 'c+');
    if ($fp === false) {
        return $defaultDoc;
    }
    $result = $defaultDoc;
    try {
        if (@flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $decoded = ($raw !== false && trim($raw) !== '') ? json_decode($raw, true) : null;
            $doc = is_array($decoded) ? $decoded : $defaultDoc;
            $result = $mutator($doc);
            $encoded = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded)) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, $encoded);
                fflush($fp);
            }
            @flock($fp, LOCK_UN);
        }
    } finally {
        fclose($fp);
    }
    return $result;
}

function zeynart_record_page_visit(string $routePath, string $pageTitle = '', string $lang = 'tr'): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== 'GET') {
        return;
    }

    // Skip prefetch / prerender speculative loads
    $purpose = strtolower((string)($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? $_SERVER['HTTP_X_MOZ'] ?? ''));
    if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender') || str_contains($purpose, 'preview')) {
        return;
    }

    // Skip logged-in admin sessions (note: is_admin_logged_in() NEVER starts a session when cookie is absent!)
    $forceTestVisit = (($_SERVER['HTTP_X_ZEYNART_TEST_VISIT'] ?? '') === '1')
        || (($_GET['_ztest_visit'] ?? '') === '1');
    if (!$forceTestVisit && is_admin_logged_in()) {
        return;
    }

    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $uaClass = zeynart_classify_user_agent($ua);
    if ($uaClass['kind'] === 'ignore') {
        return;
    }

    $analyticsDir = zeynart_analytics_dir();
    $statsFile = $analyticsDir . '/stats.json';

    if ($uaClass['kind'] === 'search_bot' || $uaClass['kind'] === 'ai_bot') {
        $isAi = ($uaClass['kind'] === 'ai_bot');
        zeynart_mutate_json_locked($statsFile, zeynart_default_stats_doc(), static function (array $doc) use ($isAi): array {
            $doc['totals'] = array_merge(zeynart_default_stats_doc()['totals'], is_array($doc['totals'] ?? null) ? $doc['totals'] : []);
            $doc['totals']['bot_hits'] = (int)($doc['totals']['bot_hits'] ?? 0) + 1;
            if ($isAi) {
                $doc['totals']['ai_bot_hits'] = (int)($doc['totals']['ai_bot_hits'] ?? 0) + 1;
            }
            return $doc;
        });
        return;
    }

    $ip = get_client_ip();
    $vHash = zeynart_visitor_daily_hash($ip, $ua);
    $normLang = in_array(strtolower($lang), ['tr', 'en', 'de', 'ru'], true) ? strtolower($lang) : 'tr';
    $trackedPath = zeynart_normalize_tracked_path($routePath, $normLang);
    $cleanTitle = zeynart_clean_tracked_title($pageTitle, $trackedPath);
    $device = zeynart_detect_device_type($ua);
    $browser = zeynart_detect_browser_name($ua);
    $country = zeynart_detect_country_code($normLang);
    $refInfo = zeynart_detect_traffic_source(
        (string)($_SERVER['HTTP_REFERER'] ?? ''),
        (string)($_SERVER['QUERY_STRING'] ?? '')
    );

    $now = time();
    $todayKey = date('Y-m-d', $now);
    $curHour = (int)date('G', $now);

    // 1. Update Live Active Visitors (last 180s window)
    $liveFile = $analyticsDir . '/live.json';
    $resolvedSource = $refInfo['source'];
    zeynart_mutate_json_locked($liveFile, ['sessions' => []], static function (array $liveDoc) use (
        $vHash,
        $now,
        $trackedPath,
        $cleanTitle,
        $device,
        $browser,
        $normLang,
        $country,
        $refInfo,
        &$resolvedSource
    ): array {
        $sessions = is_array($liveDoc['sessions'] ?? null) ? $liveDoc['sessions'] : [];
        $cutoff = $now - ZEYNART_LIVE_WINDOW_SECONDS;
        foreach ($sessions as $k => $sess) {
            if (!is_array($sess) || (int)($sess['last_seen'] ?? 0) < $cutoff) {
                unset($sessions[$k]);
            }
        }
        $prev = is_array($sessions[$vHash] ?? null) ? $sessions[$vHash] : null;
        if ($prev !== null && $refInfo['is_internal'] && !empty($prev['source'])) {
            $resolvedSource = (string)$prev['source'];
        } elseif ($refInfo['is_internal']) {
            $resolvedSource = 'Direkt Giriş';
        }
        $sessions[$vHash] = [
            'entered_at' => $prev !== null ? (int)($prev['entered_at'] ?? $now) : $now,
            'last_seen' => $now,
            'hits' => $prev !== null ? ((int)($prev['hits'] ?? 1) + 1) : 1,
            'path' => $trackedPath,
            'title' => $cleanTitle,
            'device' => $device,
            'browser' => $browser,
            'lang' => $normLang,
            'country' => $country,
            'source' => $resolvedSource,
        ];
        // Cap concurrent live table safety at 500
        if (count($sessions) > 500) {
            uasort($sessions, static fn(array $a, array $b): int => ((int)($b['last_seen'] ?? 0)) <=> ((int)($a['last_seen'] ?? 0)));
            $sessions = array_slice($sessions, 0, 500, true);
        }
        return ['sessions' => $sessions];
    });

    // 2. Update Daily & Rolling 30/90-Day Aggregated Statistics
    zeynart_mutate_json_locked($statsFile, zeynart_default_stats_doc(), static function (array $doc) use (
        $todayKey,
        $curHour,
        $now,
        $vHash,
        $trackedPath,
        $cleanTitle,
        $device,
        $browser,
        $normLang,
        $country,
        $refInfo,
        $resolvedSource
    ): array {
        $def = zeynart_default_stats_doc();
        $doc['totals'] = array_merge($def['totals'], is_array($doc['totals'] ?? null) ? $doc['totals'] : []);
        if (!isset($doc['days']) || !is_array($doc['days'])) {
            $doc['days'] = [];
        }
        if (!isset($doc['days'][$todayKey]) || !is_array($doc['days'][$todayKey])) {
            $doc['days'][$todayKey] = zeynart_default_day_bucket();
        }

        $day = &$doc['days'][$todayKey];
        if (!isset($day['visitors']) || !is_array($day['visitors'])) {
            $day['visitors'] = [];
        }
        if (!isset($day['hours']) || !is_array($day['hours']) || count($day['hours']) !== 24) {
            $day['hours'] = array_fill(0, 24, 0);
        }

        $isNewUniqueToday = !isset($day['visitors'][$vHash]);
        $day['visitors'][$vHash] = (int)($day['visitors'][$vHash] ?? 0) + 1;
        $day['views'] = (int)($day['views'] ?? 0) + 1;
        $doc['totals']['views'] = (int)($doc['totals']['views'] ?? 0) + 1;

        if ($isNewUniqueToday) {
            $day['uniques'] = (int)($day['uniques'] ?? 0) + 1;
            $doc['totals']['uniques'] = (int)($doc['totals']['uniques'] ?? 0) + 1;
            $day['devices'][$device] = (int)($day['devices'][$device] ?? 0) + 1;
            $day['browsers'][$browser] = (int)($day['browsers'][$browser] ?? 0) + 1;
            $day['countries'][$country] = (int)($day['countries'][$country] ?? 0) + 1;
            $srcKey = $refInfo['is_internal'] ? 'Direkt Giriş' : $resolvedSource;
            $day['referrers'][$srcKey] = (int)($day['referrers'][$srcKey] ?? 0) + 1;
        }

        $day['hours'][$curHour] = (int)($day['hours'][$curHour] ?? 0) + 1;
        $day['langs'][$normLang] = (int)($day['langs'][$normLang] ?? 0) + 1;

        if (!isset($day['pages']) || !is_array($day['pages'])) {
            $day['pages'] = [];
        }
        $prevPage = $day['pages'][$trackedPath] ?? ['views' => 0, 'title' => $cleanTitle];
        $day['pages'][$trackedPath] = [
            'views' => (int)($prevPage['views'] ?? 0) + 1,
            'title' => $cleanTitle !== '' ? $cleanTitle : (string)($prevPage['title'] ?? $trackedPath),
        ];
        if (count($day['pages']) > 80) {
            uasort($day['pages'], static fn(array $a, array $b): int => ((int)($b['views'] ?? 0)) <=> ((int)($a['views'] ?? 0)));
            $day['pages'] = array_slice($day['pages'], 0, 80, true);
        }
        unset($day);

        // Append to live recent events ring buffer (last 35 pageviews)
        $events = is_array($doc['recent_events'] ?? null) ? $doc['recent_events'] : [];
        array_unshift($events, [
            'ts' => $now,
            'path' => $trackedPath,
            'title' => $cleanTitle,
            'source' => $resolvedSource,
            'device' => $device,
            'browser' => $browser,
            'lang' => $normLang,
            'country' => $country,
            'is_new' => $isNewUniqueToday,
        ]);
        $doc['recent_events'] = array_slice($events, 0, 35);

        // Housekeeping: prune visitor hash maps older than 2 days & prune buckets older than 92 days
        $oldestKeepDate = date('Y-m-d', $now - (ZEYNART_STATS_RETENTION_DAYS * 86400));
        $hashKeepDate = date('Y-m-d', $now - (2 * 86400));
        foreach (array_keys($doc['days']) as $dKey) {
            if ($dKey < $oldestKeepDate) {
                unset($doc['days'][$dKey]);
            } elseif ($dKey < $hashKeepDate && isset($doc['days'][$dKey]['visitors'])) {
                unset($doc['days'][$dKey]['visitors']);
            }
        }
        ksort($doc['days']);

        return $doc;
    });
}

function zeynart_record_live_heartbeat(string $rawPath, string $rawTitle = '', string $lang = 'tr'): void
{
    $forceTestVisit = (($_SERVER['HTTP_X_ZEYNART_TEST_VISIT'] ?? '') === '1')
        || (($_GET['_ztest_visit'] ?? '') === '1');
    if (!$forceTestVisit && is_admin_logged_in()) {
        return;
    }

    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $uaClass = zeynart_classify_user_agent($ua);
    if ($uaClass['kind'] !== 'human') {
        return;
    }

    $ip = get_client_ip();
    $vHash = zeynart_visitor_daily_hash($ip, $ua);
    $normLang = in_array(strtolower($lang), ['tr', 'en', 'de', 'ru'], true) ? strtolower($lang) : 'tr';
    $trackedPath = zeynart_normalize_tracked_path($rawPath, $normLang);
    $cleanTitle = zeynart_clean_tracked_title($rawTitle, $trackedPath);
    $device = zeynart_detect_device_type($ua);
    $browser = zeynart_detect_browser_name($ua);
    $country = zeynart_detect_country_code($normLang);
    $now = time();

    $liveFile = zeynart_analytics_dir() . '/live.json';
    zeynart_mutate_json_locked($liveFile, ['sessions' => []], static function (array $liveDoc) use (
        $vHash,
        $now,
        $trackedPath,
        $cleanTitle,
        $device,
        $browser,
        $normLang,
        $country
    ): array {
        $sessions = is_array($liveDoc['sessions'] ?? null) ? $liveDoc['sessions'] : [];
        $cutoff = $now - ZEYNART_LIVE_WINDOW_SECONDS;
        foreach ($sessions as $k => $sess) {
            if (!is_array($sess) || (int)($sess['last_seen'] ?? 0) < $cutoff) {
                unset($sessions[$k]);
            }
        }
        $prev = is_array($sessions[$vHash] ?? null) ? $sessions[$vHash] : null;
        $sessions[$vHash] = [
            'entered_at' => $prev !== null ? (int)($prev['entered_at'] ?? $now) : $now,
            'last_seen' => $now,
            'hits' => $prev !== null ? (int)($prev['hits'] ?? 1) : 1,
            'path' => $trackedPath,
            'title' => $cleanTitle,
            'device' => $device,
            'browser' => $browser,
            'lang' => $normLang,
            'country' => $country,
            'source' => $prev !== null && !empty($prev['source']) ? (string)$prev['source'] : 'Direkt Giriş',
        ];
        return ['sessions' => $sessions];
    });
}

function zeynart_country_label(string $code): string
{
    $map = [
        'TR' => '🇹🇷 Türkiye',
        'DE' => '🇩🇪 Almanya',
        'US' => '🇺🇸 ABD',
        'GB' => '🇬🇧 Birleşik Krallık',
        'RU' => '🇷🇺 Rusya',
        'NL' => '🇳🇱 Hollanda',
        'AZ' => '🇦🇿 Azerbaycan',
        'FR' => '🇫🇷 Fransa',
        'CH' => '🇨🇭 İsviçre',
        'AT' => '🇦🇹 Avusturya',
        'BE' => '🇧🇪 Belçika',
        'IT' => '🇮🇹 İtalya',
        'ES' => '🇪🇸 İspanya',
        'AE' => '🇦🇪 BAE',
        'CA' => '🇨🇦 Kanada',
        'CY' => '🇨🇾 KKTC / Kıbrıs',
    ];
    $up = strtoupper(trim($code));
    return $map[$up] ?? ('🌍 ' . ($up !== '' ? $up : 'Diğer'));
}

function zeynart_tr_short_date_label(string $ymd): string
{
    $months = [
        1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis',
        5 => 'May', 6 => 'Haz', 7 => 'Tem', 8 => 'Ağu',
        9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara',
    ];
    $ts = strtotime($ymd . ' 12:00:00');
    if ($ts === false) {
        return $ymd;
    }
    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts);
    return $d . ' ' . ($months[$m] ?? '');
}

function zeynart_get_analytics_report(int $rangeDays = 30): array
{
    $rangeDays = in_array($rangeDays, [1, 7, 30, 90], true) ? $rangeDays : 30;
    $now = time();
    $analyticsDir = zeynart_analytics_dir();

    // 1. Read & filter active live sessions (last 180 seconds)
    $liveFile = $analyticsDir . '/live.json';
    $liveSessions = [];
    $rawLive = zeynart_read_json_data($liveFile);
    if (is_array($rawLive)) {
        $cutoff = $now - ZEYNART_LIVE_WINDOW_SECONDS;
        foreach (($rawLive['sessions'] ?? []) as $sess) {
            if (is_array($sess) && (int)($sess['last_seen'] ?? 0) >= $cutoff) {
                $liveSessions[] = $sess;
            }
        }
    }

    usort($liveSessions, static fn(array $a, array $b): int => ((int)($b['last_seen'] ?? 0)) <=> ((int)($a['last_seen'] ?? 0)));

    $activePagesMap = [];
    $liveVisitorsList = [];
    foreach ($liveSessions as $ls) {
        $p = (string)($ls['path'] ?? '/');
        $t = (string)($ls['title'] ?? $p);
        if (!isset($activePagesMap[$p])) {
            $activePagesMap[$p] = ['path' => $p, 'title' => $t, 'count' => 0];
        }
        $activePagesMap[$p]['count']++;

        $lastSeen = (int)($ls['last_seen'] ?? $now);
        $enteredAt = (int)($ls['entered_at'] ?? $lastSeen);
        $liveVisitorsList[] = [
            'path' => $p,
            'title' => $t,
            'device' => (string)($ls['device'] ?? 'desktop'),
            'browser' => (string)($ls['browser'] ?? 'Chrome'),
            'lang' => strtoupper((string)($ls['lang'] ?? 'tr')),
            'country' => (string)($ls['country'] ?? 'TR'),
            'country_label' => zeynart_country_label((string)($ls['country'] ?? 'TR')),
            'source' => (string)($ls['source'] ?? 'Direkt Giriş'),
            'seconds_ago' => max(0, $now - $lastSeen),
            'duration_sec' => max(1, $now - $enteredAt),
            'hits' => max(1, (int)($ls['hits'] ?? 1)),
        ];
    }
    $activePages = array_values($activePagesMap);
    usort($activePages, static fn(array $a, array $b): int => ($b['count'] <=> $a['count']));

    // 2. Read historical stats document
    $statsFile = $analyticsDir . '/stats.json';
    $doc = zeynart_default_stats_doc();
    $decoded = zeynart_read_json_data($statsFile);
    if (is_array($decoded)) {
        $doc = array_merge($doc, $decoded);
    }
    $daysMap = is_array($doc['days'] ?? null) ? $doc['days'] : [];
    $totals = array_merge(zeynart_default_stats_doc()['totals'], is_array($doc['totals'] ?? null) ? $doc['totals'] : []);

    $todayKey = date('Y-m-d', $now);
    $yesterdayKey = date('Y-m-d', $now - 86400);

    $todayBucket = is_array($daysMap[$todayKey] ?? null) ? $daysMap[$todayKey] : zeynart_default_day_bucket();
    $yesterdayBucket = is_array($daysMap[$yesterdayKey] ?? null) ? $daysMap[$yesterdayKey] : zeynart_default_day_bucket();

    // Compute fixed KPI windows: 7-day, 30-day, and previous 30-day for growth delta
    $u7 = 0; $v7 = 0;
    $u30 = 0; $v30 = 0;
    $prevU30 = 0;
    for ($i = 0; $i < 60; $i++) {
        $dk = date('Y-m-d', $now - ($i * 86400));
        $b = $daysMap[$dk] ?? null;
        if (!is_array($b)) {
            continue;
        }
        $bu = (int)($b['uniques'] ?? 0);
        $bv = (int)($b['views'] ?? 0);
        if ($i < 7) {
            $u7 += $bu;
            $v7 += $bv;
        }
        if ($i < 30) {
            $u30 += $bu;
            $v30 += $bv;
        } else {
            $prevU30 += $bu;
        }
    }

    $growth30Pct = $prevU30 > 0 ? round((($u30 - $prevU30) / $prevU30) * 100, 1) : null;

    // Aggregate over requested $rangeDays (1, 7, 30, or 90)
    $chartDaysCount = $rangeDays === 1 ? 14 : $rangeDays;
    $dailySeries = [];
    for ($i = $chartDaysCount - 1; $i >= 0; $i--) {
        $dk = date('Y-m-d', $now - ($i * 86400));
        $b = $daysMap[$dk] ?? null;
        $dailySeries[] = [
            'date' => $dk,
            'label' => zeynart_tr_short_date_label($dk),
            'uniques' => is_array($b) ? (int)($b['uniques'] ?? 0) : 0,
            'views' => is_array($b) ? (int)($b['views'] ?? 0) : 0,
        ];
    }

    $rangeUniques = 0;
    $rangeViews = 0;
    $peakDay = $todayKey;
    $peakUniques = 0;
    $aggPages = [];
    $aggReferrers = [];
    $aggDevices = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];
    $aggBrowsers = [];
    $aggLangs = ['tr' => 0, 'en' => 0, 'de' => 0, 'ru' => 0];
    $aggCountries = [];

    for ($i = 0; $i < $rangeDays; $i++) {
        $dk = date('Y-m-d', $now - ($i * 86400));
        $b = $daysMap[$dk] ?? null;
        if (!is_array($b)) {
            continue;
        }
        $bu = (int)($b['uniques'] ?? 0);
        $bv = (int)($b['views'] ?? 0);
        $rangeUniques += $bu;
        $rangeViews += $bv;
        if ($bu >= $peakUniques) {
            $peakUniques = $bu;
            $peakDay = $dk;
        }

        foreach (($b['pages'] ?? []) as $pPath => $pMeta) {
            $pv = is_array($pMeta) ? (int)($pMeta['views'] ?? 0) : (int)$pMeta;
            $pt = is_array($pMeta) ? (string)($pMeta['title'] ?? $pPath) : (string)$pPath;
            if (!isset($aggPages[$pPath])) {
                $aggPages[$pPath] = ['path' => (string)$pPath, 'title' => $pt, 'views' => 0];
            }
            $aggPages[$pPath]['views'] += $pv;
            if ($pt !== '' && $pt !== $pPath) {
                $aggPages[$pPath]['title'] = $pt;
            }
        }
        foreach (($b['referrers'] ?? []) as $rName => $rCnt) {
            $aggReferrers[$rName] = ($aggReferrers[$rName] ?? 0) + (int)$rCnt;
        }
        foreach (($b['devices'] ?? []) as $dName => $dCnt) {
            if (isset($aggDevices[$dName])) {
                $aggDevices[$dName] += (int)$dCnt;
            }
        }
        foreach (($b['browsers'] ?? []) as $brName => $brCnt) {
            $aggBrowsers[$brName] = ($aggBrowsers[$brName] ?? 0) + (int)$brCnt;
        }
        foreach (($b['langs'] ?? []) as $lgName => $lgCnt) {
            if (isset($aggLangs[$lgName])) {
                $aggLangs[$lgName] += (int)$lgCnt;
            }
        }
        foreach (($b['countries'] ?? []) as $cCode => $cCnt) {
            $aggCountries[$cCode] = ($aggCountries[$cCode] ?? 0) + (int)$cCnt;
        }
    }

    // Format sorted breakdowns with percentages
    $topPages = array_values($aggPages);
    usort($topPages, static fn(array $a, array $b): int => ($b['views'] <=> $a['views']));
    $topPages = array_slice($topPages, 0, 20);
    $totalPageSum = max(1, $rangeViews);
    foreach ($topPages as &$tp) {
        $tp['pct'] = round(($tp['views'] / $totalPageSum) * 100, 1);
    }
    unset($tp);

    arsort($aggReferrers);
    $refSum = max(1, array_sum($aggReferrers));
    $referrersList = [];
    foreach (array_slice($aggReferrers, 0, 12, true) as $src => $cnt) {
        $referrersList[] = [
            'source' => (string)$src,
            'count' => (int)$cnt,
            'pct' => round(((int)$cnt / $refSum) * 100, 1),
        ];
    }

    $devSum = max(1, array_sum($aggDevices));
    $devicesList = [
        ['key' => 'desktop', 'label' => '💻 Masaüstü', 'count' => $aggDevices['desktop'], 'pct' => round(($aggDevices['desktop'] / $devSum) * 100, 1)],
        ['key' => 'mobile', 'label' => '📱 Cep Telefonu', 'count' => $aggDevices['mobile'], 'pct' => round(($aggDevices['mobile'] / $devSum) * 100, 1)],
        ['key' => 'tablet', 'label' => '📟 Tablet', 'count' => $aggDevices['tablet'], 'pct' => round(($aggDevices['tablet'] / $devSum) * 100, 1)],
    ];

    arsort($aggBrowsers);
    $brSum = max(1, array_sum($aggBrowsers));
    $browsersList = [];
    foreach (array_slice($aggBrowsers, 0, 6, true) as $br => $cnt) {
        $browsersList[] = [
            'browser' => (string)$br,
            'count' => (int)$cnt,
            'pct' => round(((int)$cnt / $brSum) * 100, 1),
        ];
    }

    $lgSum = max(1, array_sum($aggLangs));
    $langLabels = ['tr' => '🇹🇷 Türkçe (/)', 'en' => '🇬🇧 English (/en/)', 'de' => '🇩🇪 Deutsch (/de/)', 'ru' => '🇷🇺 Русский (/ru/)'];
    $langsList = [];
    foreach ($langLabels as $lc => $ll) {
        $cnt = (int)($aggLangs[$lc] ?? 0);
        $langsList[] = [
            'code' => $lc,
            'label' => $ll,
            'count' => $cnt,
            'pct' => $rangeViews > 0 ? round(($cnt / $lgSum) * 100, 1) : 0.0,
        ];
    }

    arsort($aggCountries);
    $ctSum = max(1, array_sum($aggCountries));
    $countriesList = [];
    foreach (array_slice($aggCountries, 0, 8, true) as $cCode => $cnt) {
        $countriesList[] = [
            'code' => (string)$cCode,
            'label' => zeynart_country_label((string)$cCode),
            'count' => (int)$cnt,
            'pct' => round(((int)$cnt / $ctSum) * 100, 1),
        ];
    }

    $recentEvents = [];
    foreach (array_slice(is_array($doc['recent_events'] ?? null) ? $doc['recent_events'] : [], 0, 25) as $ev) {
        if (!is_array($ev)) {
            continue;
        }
        $ts = (int)($ev['ts'] ?? $now);
        $diff = max(0, $now - $ts);
        if ($diff < 60) {
            $ago = $diff <= 5 ? 'Az önce' : ($diff . ' sn önce');
        } elseif ($diff < 3600) {
            $ago = (int)floor($diff / 60) . ' dk önce';
        } elseif ($diff < 86400) {
            $ago = (int)floor($diff / 3600) . ' sa önce';
        } else {
            $ago = date('d.m.Y H:i', $ts);
        }
        $recentEvents[] = [
            'ts' => $ts,
            'time_str' => date('H:i:s', $ts),
            'ago' => $ago,
            'path' => (string)($ev['path'] ?? '/'),
            'title' => (string)($ev['title'] ?? '/'),
            'source' => (string)($ev['source'] ?? 'Direkt Giriş'),
            'device' => (string)($ev['device'] ?? 'desktop'),
            'browser' => (string)($ev['browser'] ?? 'Chrome'),
            'lang' => strtoupper((string)($ev['lang'] ?? 'tr')),
            'country_label' => zeynart_country_label((string)($ev['country'] ?? 'TR')),
            'is_new' => !empty($ev['is_new']),
        ];
    }

    $hourlyToday = is_array($todayBucket['hours'] ?? null) && count($todayBucket['hours']) === 24
        ? array_values(array_map('intval', $todayBucket['hours']))
        : array_fill(0, 24, 0);

    return [
        'generated_at' => date('H:i:s', $now),
        'range_days' => $rangeDays,
        'live' => [
            'count' => count($liveSessions),
            'window_seconds' => ZEYNART_LIVE_WINDOW_SECONDS,
            'active_pages' => array_slice($activePages, 0, 12),
            'visitors' => array_slice($liveVisitorsList, 0, 25),
        ],
        'kpis' => [
            'today' => [
                'uniques' => (int)($todayBucket['uniques'] ?? 0),
                'views' => (int)($todayBucket['views'] ?? 0),
            ],
            'yesterday' => [
                'uniques' => (int)($yesterdayBucket['uniques'] ?? 0),
                'views' => (int)($yesterdayBucket['views'] ?? 0),
            ],
            'days_7' => [
                'uniques' => $u7,
                'views' => $v7,
            ],
            'days_30' => [
                'uniques' => $u30,
                'views' => $v30,
                'prev_uniques' => $prevU30,
                'growth_pct' => $growth30Pct,
                'avg_views_per_visitor' => $u30 > 0 ? round($v30 / $u30, 1) : 0.0,
            ],
            'range' => [
                'days' => $rangeDays,
                'uniques' => $rangeUniques,
                'views' => $rangeViews,
                'avg_daily_uniques' => round($rangeUniques / max(1, $rangeDays), 1),
                'peak_day' => zeynart_tr_short_date_label($peakDay),
                'peak_uniques' => $peakUniques,
            ],
            'all_time' => [
                'uniques' => (int)($totals['uniques'] ?? 0),
                'views' => (int)($totals['views'] ?? 0),
                'bot_hits' => (int)($totals['bot_hits'] ?? 0),
                'ai_bot_hits' => (int)($totals['ai_bot_hits'] ?? 0),
                'since_date' => (string)($totals['since_date'] ?? $todayKey),
            ],
        ],
        'daily_series' => $dailySeries,
        'hourly_today' => $hourlyToday,
        'top_pages' => $topPages,
        'referrers' => $referrersList,
        'devices' => $devicesList,
        'browsers' => $browsersList,
        'languages' => $langsList,
        'countries' => $countriesList,
        'recent_events' => $recentEvents,
    ];
}

function zeynart_reset_analytics_data(): void
{
    if (zeynart_is_sql_active() && ($pdo = zeynart_get_pdo()) !== null) {
        $tAn = zeynart_sql_table('analytics_daily');
        $pdo->query("DELETE FROM `{$tAn}`");
    }
    $dir = DATA_DIR . '/analytics';
    if (is_dir($dir)) {
        @unlink($dir . '/live.json');
        @unlink($dir . '/stats.json');
    }
}
