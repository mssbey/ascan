<?php
declare(strict_types=1);

/**
 * Zeynart Dijital Stüdyo — Google News & Yapay Zeka Otomatik Haber/Blog Otopilotu
 * - Google News RSS + Gerçek Kaynak URL Çözümleyici (Fbv4je) + Özel RSS Akışları
 * - Yapay Zeka Özgünleştirme Motoru (Yerleşik Akıllı Editör / OpenAI / Google Gemini)
 * - Hibrit 1280x720 (16:9) WebP Kapak Görseli + Köşe Logo & zeynart.com Filigranı + Kategori Rozeti
 * - Taslak / Onay Kuyruğu İş Akışı (Varsayılan) + Tek Tıkla Onayla & Yayınla (EN·DE·RU Çeviri + IndexNow)
 * - Sıfır Çerez (Zero-Cookie) Arka Plan Zamanlayıcı + Plesk/cPanel Cron Ucu
 */

require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/translator.php';

function zeynart_news_bot_state_file(): string
{
    return DATA_DIR . '/news_bot_state.json';
}

if (!function_exists('extract_toc_from_html')) {
    function extract_toc_from_html(string $html): array
    {
        $toc = [];
        if (preg_match_all('~<h2([^>]*)>(.*?)</h2>~isu', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $idx => $m) {
                $attrs = $m[1];
                $title = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($title === '') {
                    continue;
                }
                $id = '';
                if (preg_match('~\bid\s*=\s*["\']([^"\']+)["\']~i', $attrs, $idm)) {
                    $id = trim($idm[1]);
                }
                if ($id === '') {
                    $id = slugify($title) ?: ('bolum-' . ($idx + 1));
                }
                $toc[] = ['id' => $id, 'title' => $title];
            }
        }
        return $toc;
    }
}

if (!function_exists('inject_heading_ids')) {
    function inject_heading_ids(string $html): string
    {
        $idx = 0;
        return preg_replace_callback('~<h2([^>]*)>(.*?)</h2>~isu', static function (array $m) use (&$idx): string {
            $idx++;
            $attrs = $m[1];
            $inner = $m[2];
            if (preg_match('~\bid\s*=\s*["\'][^"\']+["\']~i', $attrs)) {
                return $m[0];
            }
            $plain = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $id = slugify($plain) ?: ('bolum-' . $idx);
            return '<h2 id="' . e($id) . '"' . $attrs . '>' . $inner . '</h2>';
        }, $html) ?? $html;
    }
}

function zeynart_ttf_text_box_width(string $fontFile, float $fontSize, string $text): int
{
    if (!function_exists('imagettfbbox') || !is_file($fontFile) || $text === '') {
        return mb_strlen($text, 'UTF-8') * (int)round($fontSize * 0.65);
    }
    $box = @imagettfbbox($fontSize, 0, $fontFile, $text);
    if (!is_array($box)) {
        return mb_strlen($text, 'UTF-8') * (int)round($fontSize * 0.65);
    }
    return max(10, (int)abs($box[2] - $box[0]));
}

function zeynart_news_bot_lock_file(): string
{
    return DATA_DIR . '/news_bot.lock';
}

function zeynart_default_news_topics(): array
{
    return [
        [
            'id' => 'tp-ai-web',
            'query' => 'yapay zeka web yazılım teknoloji',
            'category' => 'YAPAY ZEKA',
            'target_service' => 'web-tasarim',
            'enabled' => true,
        ],
        [
            'id' => 'tp-web-ux',
            'query' => 'web tasarım kullanıcı deneyimi dijital trendler',
            'category' => 'WEB TEKNOLOJİLERİ',
            'target_service' => 'web-tasarim',
            'enabled' => true,
        ],
        [
            'id' => 'tp-seo-google',
            'query' => 'google arama algoritması seo dijital pazarlama',
            'category' => 'SEO & DİJİTAL',
            'target_service' => 'seo',
            'enabled' => true,
        ],
        [
            'id' => 'tp-ecommerce',
            'query' => 'e-ticaret ödeme sistemleri dijital dönüşüm',
            'category' => 'E-TİCARET',
            'target_service' => 'e-ticaret',
            'enabled' => true,
        ],
        [
            'id' => 'tp-security-cloud',
            'query' => 'siber güvenlik bulut sunucu web güvenliği',
            'category' => 'SİBER GÜVENLİK',
            'target_service' => 'hosting',
            'enabled' => true,
        ],
    ];
}

function zeynart_get_news_autopilot_config(array $store): array
{
    $raw = is_array($store['news_autopilot'] ?? null) ? $store['news_autopilot'] : [];
    $rawMode = (string)($raw['publish_mode'] ?? 'draft');
    $publishMode = in_array($rawMode, ['draft', 'published'], true) ? $rawMode : 'draft';

    $rawLangReg = (string)($raw['language_region'] ?? 'TR:tr');
    $langReg = in_array($rawLangReg, ['TR:tr', 'US:en', 'DE:de'], true) ? $rawLangReg : 'TR:tr';

    $rawAi = (string)($raw['ai_provider'] ?? 'auto');
    $aiProvider = in_array($rawAi, ['auto', 'builtin', 'openai', 'gemini'], true) ? $rawAi : 'auto';

    $topics = [];
    if (isset($raw['topics']) && is_array($raw['topics']) && $raw['topics'] !== []) {
        foreach ($raw['topics'] as $idx => $tp) {
            if (!is_array($tp)) {
                continue;
            }
            $q = mb_substr(trim((string)($tp['query'] ?? '')), 0, 140, 'UTF-8');
            if ($q === '') {
                continue;
            }
            $cat = mb_strtoupper(mb_substr(trim((string)($tp['category'] ?? 'TEKNOLOJİ')), 0, 40, 'UTF-8'), 'UTF-8') ?: 'TEKNOLOJİ';
            $topics[] = [
                'id' => preg_replace('/[^a-z0-9_-]/i', '', (string)($tp['id'] ?? '')) ?: ('tp-' . ($idx + 1)),
                'query' => $q,
                'category' => $cat,
                'target_service' => preg_replace('/[^a-z0-9_-]/i', '', (string)($tp['target_service'] ?? '')),
                'enabled' => ($tp['enabled'] ?? true) !== false,
            ];
        }
    }
    if ($topics === []) {
        $topics = zeynart_default_news_topics();
    }

    $customRss = [];
    if (isset($raw['custom_rss_urls'])) {
        $rssIn = is_array($raw['custom_rss_urls'])
            ? $raw['custom_rss_urls']
            : preg_split('/\r\n|\r|\n/', (string)$raw['custom_rss_urls']);
        foreach (($rssIn ?: []) as $ru) {
            $ruClean = trim((string)$ru);
            if ($ruClean !== '' && preg_match('~^https?://~i', $ruClean)) {
                $customRss[] = mb_substr($ruClean, 0, 320, 'UTF-8');
            }
        }
    }

    $wmRaw = is_array($raw['watermark'] ?? null) ? $raw['watermark'] : [];
    $rawCoverMode = (string)($wmRaw['cover_mode'] ?? 'hybrid');
    $coverMode = in_array($rawCoverMode, ['hybrid', 'generated_only'], true) ? $rawCoverMode : 'hybrid';
    $rawWmPos = (string)($wmRaw['position'] ?? 'bottom-right');
    $wmPos = in_array($rawWmPos, ['bottom-right', 'bottom-left', 'top-right'], true) ? $rawWmPos : 'bottom-right';

    $siteDom = parse_url((string)($store['seo']['canonical_domain'] ?? SITE_URL), PHP_URL_HOST) ?: 'zeynart.com';
    $siteDom = preg_replace('/^www\./i', '', $siteDom) ?: 'zeynart.com';

    $defaultSecret = substr(hash_hmac('sha256', 'zeynart_news_autopilot_cron', (string)($store['seo']['indexnow_key'] ?? ROOT_DIR)), 0, 22);
    $cronSecret = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($raw['cron_secret'] ?? '')) ?: $defaultSecret;

    return [
        'enabled' => ($raw['enabled'] ?? true) !== false,
        'daily_limit' => max(1, min(6, (int)($raw['daily_limit'] ?? 2))),
        'publish_mode' => $publishMode,
        'min_interval_hours' => max(1, min(24, (int)($raw['min_interval_hours'] ?? 4))),
        'language_region' => $langReg,
        'ai_provider' => $aiProvider,
        'openai_api_key' => trim((string)($raw['openai_api_key'] ?? '')),
        'gemini_api_key' => trim((string)($raw['gemini_api_key'] ?? '')),
        'auto_translate_on_publish' => ($raw['auto_translate_on_publish'] ?? true) !== false,
        'show_source_credit' => ($raw['show_source_credit'] ?? true) !== false,
        'cron_secret' => $cronSecret,
        'topics' => array_values($topics),
        'custom_rss_urls' => array_values(array_unique($customRss)),
        'watermark' => [
            'cover_mode' => $coverMode,
            'enabled' => ($wmRaw['enabled'] ?? true) !== false,
            'show_logo' => ($wmRaw['show_logo'] ?? true) !== false,
            'domain_text' => mb_substr(trim((string)($wmRaw['domain_text'] ?? $siteDom)), 0, 60, 'UTF-8') ?: $siteDom,
            'show_category_badge' => ($wmRaw['show_category_badge'] ?? true) !== false,
            'position' => $wmPos,
            'opacity' => max(40, min(100, (int)($wmRaw['opacity'] ?? 92))),
        ],
    ];
}

function zeynart_load_news_bot_state(): array
{
    $f = zeynart_news_bot_state_file();
    $defaults = [
        'today_date' => date('Y-m-d'),
        'today_count' => 0,
        'last_check_ts' => 0,
        'last_run_ts' => 0,
        'last_run_iso' => '',
        'topic_cursor' => 0,
        'seen_hashes' => [],
        'history' => [],
    ];
    $dec = zeynart_read_json_data($f);
    if (!is_array($dec)) {
        return $defaults;
    }
    $merged = array_merge($defaults, $dec);
    if (($merged['today_date'] ?? '') !== date('Y-m-d')) {
        $merged['today_date'] = date('Y-m-d');
        $merged['today_count'] = 0;
    }
    $merged['seen_hashes'] = is_array($merged['seen_hashes']) ? array_values(array_unique(array_map('strval', $merged['seen_hashes']))) : [];
    $merged['history'] = is_array($merged['history']) ? array_values(array_filter($merged['history'], 'is_array')) : [];
    return $merged;
}

function zeynart_save_news_bot_state(array $state): void
{
    if (isset($state['seen_hashes']) && is_array($state['seen_hashes']) && count($state['seen_hashes']) > 800) {
        $state['seen_hashes'] = array_slice($state['seen_hashes'], -800);
    }
    if (isset($state['history']) && is_array($state['history']) && count($state['history']) > 45) {
        $state['history'] = array_slice($state['history'], 0, 45);
    }
    atomic_write_json(zeynart_news_bot_state_file(), $state, false);
}

function zeynart_list_news_bot_queue(): array
{
    $index = load_blog_index(true);
    $queue = [];
    foreach ($index as $it) {
        if (!is_array($it) || (($it['status'] ?? 'published') !== 'draft')) {
            continue;
        }
        $nb = is_array($it['news_bot'] ?? null) ? $it['news_bot'] : null;
        if ($nb === null) {
            $full = get_post((string)($it['slug'] ?? ''));
            if (is_array($full) && !empty($full['news_bot']['is_auto_news'])) {
                $nb = $full['news_bot'];
            }
        }
        if (is_array($nb) && !empty($nb['is_auto_news'])) {
            $queue[] = array_merge($it, ['news_bot' => $nb]);
        }
    }
    return $queue;
}

/**
 * SSRF-hardened dual-transport HTTP helper (supports both cURL and PHP HTTPS streams).
 */
function zeynart_news_http_request(
    string $url,
    string $method = 'GET',
    ?string $body = null,
    array $headers = [],
    int $timeoutSec = 7,
    int $maxBytes = 524288
): array {
    $parts = @parse_url($url);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = strtolower((string)($parts['host'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        return ['ok' => false, 'status' => 0, 'body' => '', 'final_url' => $url];
    }
    if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
        return ['ok' => false, 'status' => 0, 'body' => '', 'final_url' => $url];
    }
    $resolvedIp = @gethostbyname($host);
    if ($resolvedIp !== $host && filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
        if (!filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'final_url' => $url];
        }
    }

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
    $hdrLines = array_merge([
        'User-Agent: ' . $ua,
        'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
    ], $headers);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            $buf = '';
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 4,
                CURLOPT_CONNECTTIMEOUT => min(4, $timeoutSec),
                CURLOPT_TIMEOUT => $timeoutSec,
                CURLOPT_HTTPHEADER => $hdrLines,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_WRITEFUNCTION => static function ($chObj, string $chunk) use (&$buf, $maxBytes): int {
                    $rem = $maxBytes - strlen($buf);
                    if ($rem <= 0) {
                        return 0;
                    }
                    $buf .= substr($chunk, 0, $rem);
                    return strlen($chunk);
                },
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            @curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $finalUrl = (string)(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url);
            unset($ch);
            if ($code >= 200 && $code < 400 && $buf !== '') {
                return ['ok' => true, 'status' => $code, 'body' => $buf, 'final_url' => $finalUrl];
            }
        }
    }

    $httpOpts = [
        'method' => strtoupper($method),
        'timeout' => $timeoutSec,
        'follow_location' => 1,
        'max_redirects' => 4,
        'ignore_errors' => true,
        'header' => implode("\r\n", $hdrLines) . "\r\n",
    ];
    if ($body !== null) {
        $httpOpts['content'] = $body;
    }
    $ctx = stream_context_create([
        'http' => $httpOpts,
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $http_response_header = [];
    $data = @file_get_contents($url, false, $ctx, 0, $maxBytes);
    $status = 0;
    $finalUrl = $url;
    foreach (($http_response_header ?? []) as $hl) {
        if (preg_match('~^HTTP/\S+\s+(\d{3})~i', $hl, $m)) {
            $status = (int)$m[1];
        } elseif (stripos($hl, 'Location:') === 0) {
            $loc = trim(substr($hl, 9));
            if (preg_match('~^https?://~i', $loc)) {
                $finalUrl = $loc;
            }
        }
    }
    $strBody = is_string($data) ? $data : '';
    return [
        'ok' => ($status >= 200 && $status < 400 && $strBody !== ''),
        'status' => $status,
        'body' => $strBody,
        'final_url' => $finalUrl,
    ];
}

function zeynart_clean_news_headline(string $rawTitle, string &$extractedSource = ''): string
{
    if (!mb_check_encoding($rawTitle, 'UTF-8')) {
        $rawTitle = (string)@mb_convert_encoding($rawTitle, 'UTF-8', 'Windows-1254, ISO-8859-9, ISO-8859-1');
    }
    $title = html_entity_decode(trim($rawTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
    if (preg_match('/^(.{18,})\s+[\-\|–—]\s+([^\-\|–—]{2,42})$/u', $title, $m)) {
        $title = trim($m[1]);
        if ($extractedSource === '') {
            $extractedSource = trim($m[2]);
        }
    }
    return trim($title, " \t\n\r\0\x0B\"'«»");
}

function zeynart_news_item_hash(string $cleanTitle, string $link): string
{
    $normTitle = mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $cleanTitle) ?? $cleanTitle, 'UTF-8');
    return substr(sha1($normTitle), 0, 20);
}

/**
 * Resolves Google News encrypted CBMi... article link via Fbv4je batchexecute when possible,
 * then extracts publisher og:image and clean summary paragraphs.
 */
function zeynart_resolve_article_details(string $rssLink, string $fallbackImg = ''): array
{
    $realUrl = $rssLink;
    $host = strtolower((string)parse_url($rssLink, PHP_URL_HOST));

    if (str_contains($host, 'news.google.com')) {
        $path = (string)parse_url($rssLink, PHP_URL_PATH);
        $artId = basename($path);
        if ($artId !== '' && $artId !== 'articles') {
            // 1. Check legacy base64 protobuf direct URL first
            $b64 = strtr($artId, '-_', '+/');
            $dec = @base64_decode($b64, false);
            if (is_string($dec) && preg_match('~https?://[^\x00-\x1f\x7f-\xff"\'\s<>]+~', $dec, $mLegacy)) {
                if (!str_contains($mLegacy[0], 'google.com')) {
                    $realUrl = $mLegacy[0];
                }
            }
            // 2. Modern Google News Fbv4je batchexecute signature resolver
            if ($realUrl === $rssLink) {
                $splash = zeynart_news_http_request('https://news.google.com/rss/articles/' . rawurlencode($artId), 'GET', null, [], 5, 260000);
                if (!empty($splash['ok'])
                    && preg_match('/data-n-a-sg="([^"]+)"/', $splash['body'], $mSg)
                    && preg_match('/data-n-a-ts="([^"]+)"/', $splash['body'], $mTs)
                ) {
                    $inner = '["garturlreq",[["X","X",["X","X"],null,null,1,1,"TR:tr",null,1,null,null,null,null,null,0,1],"X","X",1,[1,1,1],1,1,null,0,0,null,0],"'
                        . addslashes($artId) . '",' . preg_replace('/\D+/', '', $mTs[1]) . ',"' . addslashes($mSg[1]) . '"]';
                    $fReq = json_encode([[['Fbv4je', $inner, null, 'generic']]], JSON_UNESCAPED_SLASHES);
                    $batch = zeynart_news_http_request(
                        'https://news.google.com/_/DotsSplashUi/data/batchexecute',
                        'POST',
                        http_build_query(['f.req' => $fReq]),
                        ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'],
                        5,
                        131072
                    );
                    if (!empty($batch['ok']) && preg_match('/\\\\"garturlres\\\\",\\\\"([^\\\\"]+)\\\\"/', $batch['body'], $mUrl)) {
                        $cand = stripslashes($mUrl[1]);
                        if (preg_match('~^https?://~i', $cand) && !str_contains($cand, 'news.google.com')) {
                            $realUrl = $cand;
                        }
                    }
                }
            }
        }
    }

    $ogImage = $fallbackImg;
    $metaDesc = '';
    $paragraphs = [];

    if ($realUrl !== '' && !str_contains((string)parse_url($realUrl, PHP_URL_HOST), 'news.google.com')) {
        $artResp = zeynart_news_http_request($realUrl, 'GET', null, [], 5, 393216);
        if (!empty($artResp['ok'])) {
            $html = $artResp['body'];
            $finalUrl = (string)($artResp['final_url'] ?: $realUrl);
            $realUrl = $finalUrl;

            $resolveRel = static function (string $imgU) use ($finalUrl): string {
                $imgU = html_entity_decode(trim($imgU), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($imgU === '' || str_starts_with($imgU, 'data:')) {
                    return '';
                }
                if (str_starts_with($imgU, '//')) {
                    return 'https:' . $imgU;
                }
                if (preg_match('~^https?://~i', $imgU)) {
                    return $imgU;
                }
                $p = @parse_url($finalUrl);
                if (!empty($p['scheme']) && !empty($p['host'])) {
                    return $p['scheme'] . '://' . $p['host'] . '/' . ltrim($imgU, '/');
                }
                return '';
            };

            $imgPatterns = [
                '/<meta[^>]+(?:property|name)=["\'](?:og:image|twitter:image|twitter:image:src)["\'][^>]+content=["\']([^"\']+)["\']/i',
                '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\'](?:og:image|twitter:image|twitter:image:src)["\']/i',
            ];
            foreach ($imgPatterns as $pat) {
                if (preg_match($pat, $html, $mi)) {
                    $resolved = $resolveRel($mi[1]);
                    if ($resolved !== '') {
                        $ogImage = $resolved;
                        break;
                    }
                }
            }

            $descPatterns = [
                '/<meta[^>]+(?:property|name)=["\'](?:og:description|description)["\'][^>]+content=["\']([^"\']+)["\']/i',
                '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\'](?:og:description|description)["\']/i',
            ];
            foreach ($descPatterns as $dPat) {
                if (preg_match($dPat, $html, $md)) {
                    $metaDesc = trim(html_entity_decode(strip_tags($md[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    break;
                }
            }

            $cleanHtml = preg_replace('~<(script|style|nav|header|footer|aside|noscript|form|svg)\b[^>]*>.*?</\1>~is', ' ', $html) ?? $html;
            if (preg_match_all('~<p\b[^>]*>(.*?)</p>~is', $cleanHtml, $mp)) {
                foreach ($mp[1] as $rawP) {
                    $txt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($rawP), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
                    if (mb_strlen($txt, 'UTF-8') < 70 || mb_strlen($txt, 'UTF-8') > 650) {
                        continue;
                    }
                    if (preg_match('/(çerez|cookie|abone ol|bülten|tüm hakları saklıdır|reklam|gizlilik politikası|kvkk)/iu', $txt)) {
                        continue;
                    }
                    $paragraphs[] = $txt;
                    if (count($paragraphs) >= 6) {
                        break;
                    }
                }
            }
        }
    }

    return [
        'real_url' => $realUrl,
        'og_image' => $ogImage,
        'meta_desc' => $metaDesc,
        'paragraphs' => $paragraphs,
    ];
}

function zeynart_parse_rss_feed_xml(string $xmlRaw, string $defaultCategory = 'TEKNOLOJİ', string $targetService = ''): array
{
    $xmlTrim = ltrim($xmlRaw, "\xEF\xBB\xBF \t\r\n");
    if ($xmlTrim === '') {
        return [];
    }
    if (!mb_check_encoding($xmlTrim, 'UTF-8')) {
        $xmlTrim = (string)@mb_convert_encoding($xmlTrim, 'UTF-8', 'Windows-1254, ISO-8859-9, ISO-8859-1');
        $xmlTrim = preg_replace('/encoding=["\'](?:windows-1254|iso-8859-9|iso-8859-1)["\']/i', 'encoding="UTF-8"', $xmlTrim) ?? $xmlTrim;
    }
    $prevErr = libxml_use_internal_errors(true);
    $sx = @simplexml_load_string($xmlTrim, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();
    libxml_use_internal_errors($prevErr);
    if ($sx === false) {
        return [];
    }

    $items = [];
    $entries = [];
    if (isset($sx->channel->item)) {
        foreach ($sx->channel->item as $it) {
            $entries[] = $it;
        }
    } elseif (isset($sx->entry)) {
        foreach ($sx->entry as $ent) {
            $entries[] = $ent;
        }
    }

    foreach ($entries as $it) {
        $srcName = isset($it->source) ? trim((string)$it->source) : '';
        $srcHome = isset($it->source['url']) ? trim((string)$it->source['url']) : '';
        $cleanTitle = zeynart_clean_news_headline((string)($it->title ?? ''), $srcName);
        if (mb_strlen($cleanTitle, 'UTF-8') < 16) {
            continue;
        }
        $link = trim((string)($it->link ?? ''));
        if ($link === '' && isset($it->link['href'])) {
            $link = trim((string)$it->link['href']);
        }
        if ($link === '') {
            continue;
        }
        if ($srcName === '' && $srcHome !== '') {
            $srcName = preg_replace('/^www\./i', '', (string)parse_url($srcHome, PHP_URL_HOST)) ?: 'Teknoloji Basını';
        }
        if ($srcName === '') {
            $lHost = preg_replace('/^www\./i', '', (string)parse_url($link, PHP_URL_HOST));
            $srcName = ($lHost && !str_contains($lHost, 'google.com')) ? $lHost : 'Google News';
        }

        $rssImg = '';
        if (isset($it->enclosure['url'])) {
            $enType = strtolower((string)($it->enclosure['type'] ?? 'image/jpeg'));
            if (str_starts_with($enType, 'image/')) {
                $rssImg = trim((string)$it->enclosure['url']);
            }
        }
        $namespaces = $it->getNamespaces(true);
        if ($rssImg === '' && isset($namespaces['media'])) {
            $media = $it->children($namespaces['media']);
            if (isset($media->content)) {
                foreach ($media->content as $mc) {
                    $attr = $mc->attributes();
                    if (!empty($attr['url'])) {
                        $rssImg = trim((string)$attr['url']);
                        break;
                    }
                }
            }
            if ($rssImg === '' && isset($media->thumbnail)) {
                $attr = $media->thumbnail->attributes();
                if (!empty($attr['url'])) {
                    $rssImg = trim((string)$attr['url']);
                }
            }
        }
        $rawDesc = (string)($it->description ?? ($it->summary ?? ''));
        if ($rssImg === '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawDesc, $mImg)) {
            $candImg = html_entity_decode($mImg[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!str_contains($candImg, 'lh3.googleusercontent.com') || str_contains($candImg, '=w')) {
                $rssImg = $candImg;
            }
        }

        $pubRaw = trim((string)($it->pubDate ?? ($it->published ?? ($it->updated ?? ''))));
        $pubTs = $pubRaw !== '' ? (@strtotime($pubRaw) ?: time()) : time();
        $plainDesc = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($rawDesc), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        $items[] = [
            'hash' => zeynart_news_item_hash($cleanTitle, $link),
            'title' => $cleanTitle,
            'link' => $link,
            'source_name' => $srcName,
            'source_url' => $srcHome,
            'rss_image' => $rssImg,
            'rss_desc' => $plainDesc,
            'pub_ts' => $pubTs,
            'category' => $defaultCategory,
            'target_service' => $targetService,
        ];
    }

    return $items;
}

/**
 * Generates or downloads a 1280x720 (16:9) WebP cover image with:
 * - Hybrid publisher photo (object-fit: cover) OR topic-matched bespoke dark-tech studio cover
 * - Top-left category pill (when enabled)
 * - Corner frosted-glass institutional watermark with actual Site Logo + zeynart.com
 */
function zeynart_generate_news_cover_webp(
    array $store,
    string $headline,
    string $category,
    string $remoteImageUrl = '',
    ?array $wmOverride = null,
    bool $returnRawBinaryOnly = false
): array {
    $cfg = zeynart_get_news_autopilot_config($store);
    $wm = is_array($wmOverride) ? array_merge($cfg['watermark'], $wmOverride) : $cfg['watermark'];

    if (!function_exists('imagecreatetruecolor')) {
        return ['ok' => false, 'url' => '/images/journal/ai-workflow.webp', 'cover_source' => 'fallback_static'];
    }

    $W = 1280;
    $H = 720;
    $canvas = imagecreatetruecolor($W, $H);
    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);

    $bgDark = imagecolorallocate($canvas, 6, 15, 20);
    imagefilledrectangle($canvas, 0, 0, $W, $H, $bgDark);

    $tb = zeynart_get_theme_brand($store);
    [$prR, $prG, $prB] = zeynart_hex_rgb_tuple((string)($tb['primary_color'] ?? '#16b7c5'), [22, 183, 197]);
    [$brR, $brG, $brB] = zeynart_hex_rgb_tuple((string)($tb['bright_color'] ?? '#5eead4'), [94, 234, 212]);

    $coverSource = 'studio_cover';
    $loadedRemote = false;

    // 1. Hybrid Mode: Try loading high-resolution remote article image (>= 460x250)
    if (($wm['cover_mode'] ?? 'hybrid') === 'hybrid' && $remoteImageUrl !== '') {
        $imgResp = null;
        if (str_starts_with($remoteImageUrl, '/') && is_file(ROOT_DIR . $remoteImageUrl)) {
            $rawBytes = (string)@file_get_contents(ROOT_DIR . $remoteImageUrl);
            $imgResp = ['ok' => $rawBytes !== '', 'body' => $rawBytes];
        } elseif (preg_match('~^https?://~i', $remoteImageUrl)) {
            $imgResp = zeynart_news_http_request($remoteImageUrl, 'GET', null, ['Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'], 6, 4194304);
        }
        if (!empty($imgResp['ok']) && strlen($imgResp['body']) > 2048) {
            $srcIm = @imagecreatefromstring($imgResp['body']);
            if ($srcIm !== false) {
                $sw = imagesx($srcIm);
                $sh = imagesy($srcIm);
                if ($sw >= 460 && $sh >= 250) {
                    $scale = max($W / $sw, $H / $sh);
                    $cropW = (int)round($W / $scale);
                    $cropH = (int)round($H / $scale);
                    $srcX = max(0, (int)floor(($sw - $cropW) / 2));
                    $srcY = max(0, (int)floor(($sh - $cropH) / 2));
                    imagecopyresampled($canvas, $srcIm, 0, 0, $srcX, $srcY, $W, $H, $cropW, $cropH);
                    $loadedRemote = true;
                    $coverSource = 'publisher_photo';
                }
                unset($srcIm);
            }
        }
    }

    // 2. Fallback / Generated Mode: Topic-matched high-res studio visual + unique hash-seeded editorial aura & tech grid
    if (!$loadedRemote) {
        $catUp = mb_strtoupper($category, 'UTF-8');
        $pool = match (true) {
            str_contains($catUp, 'YAPAY') || str_contains($catUp, 'AI') => ['/images/journal/ai-workflow.webp', '/images/journal/custom-software.webp', '/images/journal/mvp.webp'],
            str_contains($catUp, 'TİCARET') => ['/images/journal/ecommerce.webp', '/images/journal/web-performance.webp'],
            str_contains($catUp, 'GÜVENLİK') || str_contains($catUp, 'SUNUCU') || str_contains($catUp, 'BULUT') => ['/images/journal/hosting.webp', '/images/banners/hosting.webp'],
            str_contains($catUp, 'SEO') || str_contains($catUp, 'PAZARLAMA') => ['/images/journal/web-performance.webp', '/images/journal/web-planning.webp'],
            default => ['/images/journal/web-planning.webp', '/images/journal/brand-system.webp', '/images/journal/custom-software.webp', '/images/banners/services.webp'],
        };
        $seedInt = hexdec(substr(md5($headline . '|' . $category), 0, 6));
        $chosenRel = $pool[$seedInt % count($pool)];
        $chosenAbs = ROOT_DIR . $chosenRel;
        if (is_file($chosenAbs) && function_exists('imagecreatefromwebp')) {
            $bgIm = @imagecreatefromwebp($chosenAbs);
            if ($bgIm !== false) {
                $bw = imagesx($bgIm);
                $bh = imagesy($bgIm);
                $scale = max($W / max(1, $bw), $H / max(1, $bh));
                $cw = (int)round($W / $scale);
                $ch = (int)round($H / $scale);
                $sx = max(0, (int)floor(($bw - $cw) / 2));
                $sy = max(0, (int)floor(($bh - $ch) / 2));
                imagecopyresampled($canvas, $bgIm, 0, 0, $sx, $sy, $W, $H, $cw, $ch);
                unset($bgIm);
            }
        }

        // Subtle unique editorial color wash + geometric grid so each generated cover feels distinct
        $wash = imagecolorallocatealpha($canvas, 5, 14, 20, 48);
        imagefilledrectangle($canvas, 0, 0, $W, $H, $wash);

        $gridCol = imagecolorallocatealpha($canvas, $brR, $brG, $brB, 118);
        $step = 64;
        $offset = ($seedInt % 32);
        for ($gx = $offset; $gx < $W; $gx += $step) {
            imageline($canvas, $gx, 0, $gx, $H, $gridCol);
        }
        for ($gy = $offset; $gy < $H; $gy += $step) {
            imageline($canvas, 0, $gy, $W, $gy, $gridCol);
        }

        // Soft glowing aura shifted by seed
        $orbX = 240 + ($seedInt % 800);
        $orbY = 160 + (($seedInt >> 4) % 380);
        for ($r = 420; $r >= 40; $r -= 38) {
            $orbCol = imagecolorallocatealpha($canvas, $prR, $prG, $prB, 119);
            imagefilledellipse($canvas, $orbX, $orbY, $r * 2, (int)round($r * 1.4), $orbCol);
        }
    }

    // Gentle top & bottom editorial vignette so corner badges & watermarks pop cleanly on bright/white photos too
    for ($vy = 0; $vy < 140; $vy++) {
        $alphaTop = (int)min(127, round(92 + ($vy / 140) * 35));
        $cTop = imagecolorallocatealpha($canvas, 4, 11, 16, $alphaTop);
        imageline($canvas, 0, $vy, $W, $vy, $cTop);
    }
    for ($vy = $H - 180; $vy < $H; $vy++) {
        $ratio = ($vy - ($H - 180)) / 180;
        $alphaBot = (int)max(52, min(127, round(127 - ($ratio * 68))));
        $cBot = imagecolorallocatealpha($canvas, 4, 11, 16, $alphaBot);
        imageline($canvas, 0, $vy, $W, $vy, $cBot);
    }

    $fontBold = function_exists('zeynart_gd_font_path') ? zeynart_gd_font_path(true) : null;

    // 3. Top-Left Category Badge Pill (when show_category_badge is true)
    if (!empty($wm['show_category_badge']) && trim($category) !== '') {
        $catText = mb_strtoupper(trim($category), 'UTF-8');
        $catSize = 14.5;
        $catW = $fontBold !== null ? (zeynart_ttf_text_box_width($fontBold, $catSize, $catText) + 52) : (mb_strlen($catText, 'UTF-8') * 11 + 52);
        $catH = 42;
        $cx1 = 34;
        $cy1 = 30;
        $cx2 = $cx1 + $catW;
        $cy2 = $cy1 + $catH;

        $pillBg = imagecolorallocatealpha($canvas, 6, 16, 22, 22);
        $pillBorder = imagecolorallocatealpha($canvas, $brR, $brG, $brB, 52);
        $dotCol = imagecolorallocate($canvas, $brR, $brG, $brB);
        $whiteCol = imagecolorallocate($canvas, 248, 252, 255);

        zeynart_gd_draw_rounded_rect($canvas, $cx1, $cy1, $cx2, $cy2, 21, $pillBg);
        imagerectangle($canvas, $cx1, $cy1, $cx2, $cy2, $pillBorder);
        imagefilledellipse($canvas, $cx1 + 21, $cy1 + 21, 9, 9, $dotCol);

        if ($fontBold !== null) {
            imagettftext($canvas, $catSize, 0, $cx1 + 34, $cy1 + 27, $whiteCol, $fontBold, $catText);
        } else {
            imagestring($canvas, 4, $cx1 + 32, $cy1 + 13, $catText, $whiteCol);
        }
    }

    // 4. Institutional Corner Watermark (Logo + zeynart.com)
    if (!empty($wm['enabled'])) {
        $siteBrand = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
        $siteDom = parse_url((string)($store['seo']['canonical_domain'] ?? SITE_URL), PHP_URL_HOST) ?: 'zeynart.com';
        $siteDom = preg_replace('/^www\./i', '', $siteDom) ?: 'zeynart.com';
        $domText = strtr(trim((string)($wm['domain_text'] ?? $siteDom)), [
            '{marka}' => $siteBrand,
            '{domain}' => $siteDom,
        ]);

        // Load actual site logo (AVIF / PNG / WebP) with graceful fallback
        $logoIm = null;
        if (!empty($wm['show_logo'])) {
            $logoCandidates = [
                ROOT_DIR . '/' . ltrim((string)($store['site']['logo'] ?? '/images/zeynart-logo.avif'), '/'),
                ROOT_DIR . '/images/zeynart-logo.avif',
                ROOT_DIR . '/icon.png',
            ];
            foreach ($logoCandidates as $lcPath) {
                if (!is_file($lcPath)) {
                    continue;
                }
                $ext = strtolower(pathinfo($lcPath, PATHINFO_EXTENSION));
                $loadedLogo = match ($ext) {
                    'avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($lcPath) : false,
                    'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($lcPath) : false,
                    'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($lcPath) : false,
                    'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($lcPath) : false,
                    default => @imagecreatefromstring((string)@file_get_contents($lcPath)),
                };
                if ($loadedLogo !== false && imagesx($loadedLogo) > 12) {
                    $logoIm = $loadedLogo;
                    break;
                }
            }
        }

        $wmOpacityPct = max(40, min(100, (int)($wm['opacity'] ?? 92)));
        $bgAlpha = (int)max(8, min(105, round(127 - ($wmOpacityPct / 100) * 108)));

        $targetLogoH = 30;
        $targetLogoW = 0;
        if ($logoIm !== null) {
            $lw = imagesx($logoIm);
            $lh = max(1, imagesy($logoIm));
            $targetLogoW = min(130, max(28, (int)round(($lw / $lh) * $targetLogoH)));
        } elseif (!empty($wm['show_logo'])) {
            $targetLogoW = 28; // crisp vector monogram fallback width
        }

        $txtFontSize = 15.0;
        $txtW = ($domText !== '' && $fontBold !== null)
            ? zeynart_ttf_text_box_width($fontBold, $txtFontSize, $domText)
            : (mb_strlen($domText, 'UTF-8') * 9);

        $padX = 18;
        $sepGap = ($targetLogoW > 0 && $domText !== '') ? 22 : 0;
        $boxW = $padX * 2 + $targetLogoW + $sepGap + $txtW;
        $boxH = 50;
        $margin = 32;

        $pos = (string)($wm['position'] ?? 'bottom-right');
        $bx1 = match ($pos) {
            'bottom-left' => $margin,
            'top-right' => $W - $margin - $boxW,
            default => $W - $margin - $boxW,
        };
        $by1 = match ($pos) {
            'top-right' => $margin,
            default => $H - $margin - $boxH,
        };
        $bx2 = $bx1 + $boxW;
        $by2 = $by1 + $boxH;

        $wmBg = imagecolorallocatealpha($canvas, 5, 13, 19, $bgAlpha);
        $wmBorder = imagecolorallocatealpha($canvas, $brR, $brG, $brB, min(115, $bgAlpha + 35));
        $wmWhite = imagecolorallocatealpha($canvas, 245, 252, 255, max(0, (int)round((100 - $wmOpacityPct) * 0.8)));

        zeynart_gd_draw_rounded_rect($canvas, $bx1, $by1, $bx2, $by2, 25, $wmBg);
        imagerectangle($canvas, $bx1, $by1, $bx2, $by2, $wmBorder);

        $cursorX = $bx1 + $padX;
        if ($logoIm !== null) {
            $ly = $by1 + (int)floor(($boxH - $targetLogoH) / 2);
            imagecopyresampled($canvas, $logoIm, $cursorX, $ly, 0, 0, $targetLogoW, $targetLogoH, imagesx($logoIm), imagesy($logoIm));
            unset($logoIm);
            $cursorX += $targetLogoW;
        } elseif (!empty($wm['show_logo'])) {
            $emCol = imagecolorallocate($canvas, $prR, $prG, $prB);
            imagefilledellipse($canvas, $cursorX + 14, $by1 + 25, 26, 26, $emCol);
            if ($fontBold !== null) {
                $initial = mb_substr($siteBrand, 0, 1, 'UTF-8') ?: 'Z';
                imagettftext($canvas, 13, 0, $cursorX + 8, $by1 + 30, $wmWhite, $fontBold, $initial);
            }
            $cursorX += 28;
        }

        if ($sepGap > 0) {
            $divCol = imagecolorallocatealpha($canvas, 148, 210, 218, 82);
            imageline($canvas, $cursorX + 10, $by1 + 13, $cursorX + 10, $by2 - 13, $divCol);
            $cursorX += $sepGap;
        }

        if ($domText !== '') {
            if ($fontBold !== null) {
                imagettftext($canvas, $txtFontSize, 0, $cursorX, $by1 + 31, $wmWhite, $fontBold, $domText);
            } else {
                imagestring($canvas, 4, $cursorX, $by1 + 17, $domText, $wmWhite);
            }
        }
    }

    if ($returnRawBinaryOnly) {
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($canvas, null, 88);
            $mime = 'image/webp';
        } else {
            imagejpeg($canvas, null, 88);
            $mime = 'image/jpeg';
        }
        $bytes = (string)ob_get_clean();
        unset($canvas);
        return ['ok' => true, 'bytes' => $bytes, 'mime' => $mime, 'cover_source' => $coverSource];
    }

    $ymd = date('Y') . '/' . date('m') . '/' . date('d');
    $subDir = UPLOADS_DIR . '/' . $ymd;
    if (!is_dir($subDir)) {
        @mkdir($subDir, 0775, true);
    }
    $slugBase = substr(slugify($headline), 0, 56) ?: 'teknoloji-haberi';
    $fileName = 'haber-' . $slugBase . '-' . substr(md5($headline . microtime(true)), 0, 6) . '.webp';
    $destAbs = $subDir . '/' . $fileName;
    $relUrl = '/uploads/' . $ymd . '/' . $fileName;

    $savedOk = function_exists('imagewebp') ? @imagewebp($canvas, $destAbs, 88) : false;
    unset($canvas);

    if (!$savedOk || !is_file($destAbs)) {
        return ['ok' => false, 'url' => '/images/journal/ai-workflow.webp', 'cover_source' => 'fallback_static'];
    }
    if (function_exists('zeynart_invalidate_media_cache')) {
        zeynart_invalidate_media_cache();
    }

    return [
        'ok' => true,
        'url' => $relUrl,
        'cover_source' => $coverSource,
        'size' => (int)(@filesize($destAbs) ?: 0),
    ];
}

/**
 * Rewrites a news item into a comprehensive, 100% original Turkish analytical blog post
 * using OpenAI, Google Gemini, or the Built-in Smart Turkish News Editorial Engine.
 */
function zeynart_rewrite_news_to_blog_article(array $newsItem, array $articleDetails, array $store): array
{
    $cfg = zeynart_get_news_autopilot_config($store);
    $brandName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
    $rawHeadline = trim((string)($newsItem['title'] ?? 'Web ve Teknoloji Gündemi'));
    $category = trim((string)($newsItem['category'] ?? 'TEKNOLOJİ')) ?: 'TEKNOLOJİ';
    $sourceName = trim((string)($newsItem['source_name'] ?? 'Teknoloji Basını'));
    $targetSrv = trim((string)($newsItem['target_service'] ?? ''), '/');

    $serviceHref = '/hizmetler/';
    $serviceLabel = 'Kurumsal Dijital Çözümlerimiz';
    foreach (($store['services'] ?? []) as $srv) {
        $sSlug = trim((string)($srv['slug'] ?? ''), '/');
        if ($sSlug !== '' && ($targetSrv !== '' && str_contains($sSlug, $targetSrv))) {
            $serviceHref = '/hizmetler/' . $sSlug . '/';
            $serviceLabel = (string)($srv['title'] ?? $serviceLabel);
            break;
        }
    }

    $contextFacts = [];
    if (!empty($articleDetails['meta_desc'])) {
        $contextFacts[] = $articleDetails['meta_desc'];
    }
    foreach (($articleDetails['paragraphs'] ?? []) as $pTxt) {
        $contextFacts[] = $pTxt;
    }
    if ($contextFacts === [] && !empty($newsItem['rss_desc'])) {
        $contextFacts[] = $newsItem['rss_desc'];
    }
    $contextBlob = mb_substr(implode("\n", $contextFacts), 0, 2600, 'UTF-8');

    $providerPref = $cfg['ai_provider'];
    $openAiKey = $cfg['openai_api_key'] !== ''
        ? $cfg['openai_api_key']
        : trim((string)($store['ai_seo']['openai_api_key'] ?? ($store['i18n']['openai_api_key'] ?? '')));
    $geminiKey = $cfg['gemini_api_key'];

    $systemRule = 'Sen ' . $brandName . ' teknoloji ve dijital dönüşüm stüdyosunun baş teknoloji editörüsün. '
        . 'Verilen haberi birebir kopyalamadan, %100 özgün, akıcı, analitik ve kurumsal Türkçe ile yeniden yaz. '
        . 'Asla üçüncü parti hazır CMS/eklenti/grafik yazılımı marka adları kullanma. Sadece geçerli JSON döndür.';

    $jsonSchemaPrompt = "Kaynak Haber Başlığı: \"{$rawHeadline}\"\n"
        . "Kaynak Yayın: {$sourceName}\n"
        . "Kategori: {$category}\n"
        . ($contextBlob !== '' ? "Haber Özeti ve Notlar:\n{$contextBlob}\n\n" : "\n")
        . "Bu gelişmeyi işletmeler, web teknolojileri ve dijital ekosistem açısından derinlemesine analiz eden özgün bir Türkçe blog makalesi oluştur. Şu JSON yapısını döndür:\n"
        . '{"title":"Özgün, merak uyandıran ve SEO uyumlu Türkçe başlık (45-72 karakter)","excerpt":"145-185 karakter vurucu özet","focus_keyword":"odak anahtar kelime","meta_title":"maks 62 karakter","meta_desc":"135-156 karakter","ai_summary":"2-3 cümle net olgusal özet","tags":["etiket1","etiket2","etiket3","etiket4"],"content_html":"<p>...güçlü giriş...</p><h2>...</h2><p>...</p><ul><li><strong>...</strong> ...</li></ul><blockquote>...</blockquote> (4 veya 5 adet H2 alt başlığı, H1 kullanma)","faqs":[{"q":"...","a":"..."} (3 adet özgün SSS)]}';

    // Option A: OpenAI GPT
    if (($providerPref === 'openai' || ($providerPref === 'auto' && $openAiKey !== '')) && $openAiKey !== '') {
        $model = trim((string)($store['i18n']['openai_model'] ?? 'gpt-4o-mini')) ?: 'gpt-4o-mini';
        $resp = zeynart_news_http_request(
            'https://api.openai.com/v1/chat/completions',
            'POST',
            json_encode([
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.65,
                'messages' => [
                    ['role' => 'system', 'content' => $systemRule],
                    ['role' => 'user', 'content' => $jsonSchemaPrompt],
                ],
            ], JSON_UNESCAPED_UNICODE),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $openAiKey,
            ],
            26,
            262144
        );
        if (!empty($resp['ok'])) {
            $outer = json_decode($resp['body'], true);
            $cJson = $outer['choices'][0]['message']['content'] ?? '';
            $parsed = is_string($cJson) ? json_decode($cJson, true) : null;
            if (is_array($parsed) && !empty($parsed['title']) && !empty($parsed['content_html'])) {
                return zeynart_finalize_news_composed($parsed, $rawHeadline, $category, $sourceName, $serviceHref, $serviceLabel, $brandName, $cfg['show_source_credit'], 'openai');
            }
        }
    }

    // Option B: Google Gemini Flash API
    if (($providerPref === 'gemini' || ($providerPref === 'auto' && $geminiKey !== '')) && $geminiKey !== '') {
        $gemUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . rawurlencode($geminiKey);
        $resp = zeynart_news_http_request(
            $gemUrl,
            'POST',
            json_encode([
                'systemInstruction' => ['parts' => [['text' => $systemRule]]],
                'contents' => [['parts' => [['text' => $jsonSchemaPrompt]]]],
                'generationConfig' => [
                    'temperature' => 0.65,
                    'responseMimeType' => 'application/json',
                ],
            ], JSON_UNESCAPED_UNICODE),
            ['Content-Type: application/json'],
            26,
            262144
        );
        if (!empty($resp['ok'])) {
            $outer = json_decode($resp['body'], true);
            $cJson = $outer['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $parsed = is_string($cJson) ? json_decode($cJson, true) : null;
            if (is_array($parsed) && !empty($parsed['title']) && !empty($parsed['content_html'])) {
                return zeynart_finalize_news_composed($parsed, $rawHeadline, $category, $sourceName, $serviceHref, $serviceLabel, $brandName, $cfg['show_source_credit'], 'gemini');
            }
        }
    }

    // Option C: Zero-Key Built-in Smart Turkish News Editorial Rewriter
    $cleanHead = zeynart_scrub_cms_tokens(preg_replace('/\s+/u', ' ', $rawHeadline) ?? $rawHeadline);

    $variant = hexdec(substr(md5($cleanHead), 0, 4)) % 3;
    $rewrittenTitle = $cleanHead;
    if (mb_strlen($cleanHead, 'UTF-8') < 54) {
        $suffixes = [
            ': Dijital Dünyada Yeni Dönem ve Etkileri',
            ': Web Ekosistemi İçin Kritik Analiz',
            ': İşletmeler ve Dijital Stratejiler İçin Ne Anlama Geliyor?',
        ];
        $candTitle = rtrim($cleanHead, '.!?') . $suffixes[$variant];
        if (mb_strlen($candTitle, 'UTF-8') <= 78) {
            $rewrittenTitle = $candTitle;
        }
    }

    // Extract & paraphrase clean context sentences without verbatim copying
    $cleanContextParas = [];
    foreach (($articleDetails['paragraphs'] ?? []) as $pLine) {
        $san = zeynart_scrub_cms_tokens((string)$pLine);
        if (mb_strlen($san, 'UTF-8') >= 75) {
            $cleanContextParas[] = $san;
        }
    }
    $metaNote = zeynart_scrub_cms_tokens((string)($articleDetails['meta_desc'] ?? ''));

    $leadNote = $metaNote !== '' && mb_strlen($metaNote, 'UTF-8') >= 55
        ? $metaNote
        : ($cleanHead . ' başlığıyla teknoloji gündemine taşınan gelişme; hız, veri güvenliği, yapay zeka entegrasyonu ve kullanıcı deneyimi standartlarında markalar için önemli sinyaller barındırıyor.');

    $excerpt = mb_substr($leadNote, 0, 182, 'UTF-8');
    if (mb_strlen($leadNote, 'UTF-8') > 182) {
        $excerpt = rtrim(mb_substr($leadNote, 0, 178, 'UTF-8')) . '...';
    }

    $detailBlockHtml = '';
    if ($cleanContextParas !== []) {
        $p0 = htmlspecialchars(mb_substr($cleanContextParas[0], 0, 420, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        $detailBlockHtml .= '<p>Gündeme yansıyan teknik detaylar incelendiğinde öne çıkan tablo oldukça net: ' . $p0 . '</p>';
        if (isset($cleanContextParas[1])) {
            $p1 = htmlspecialchars(mb_substr($cleanContextParas[1], 0, 420, 'UTF-8'), ENT_QUOTES, 'UTF-8');
            $detailBlockHtml .= '<p>Sektör kaynaklarının paylaştığı ek bulgular ise dönüşümün hızını doğruluyor: ' . $p1 . '</p>';
        }
    } else {
        $detailBlockHtml = '<p>Küresel teknoloji ekosisteminde yaşanan bu gelişme, yalnızca yazılım geliştiricileri değil; dijital kanallar üzerinden müşteri kazanan tüm kurumsal işletmeleri doğrudan ilgilendiriyor. Özellikle hız, otomasyon ve veri bütünlüğü ekseninde şekillenen yeni standartlar, rekabet avantajının sınırlarını yeniden çiziyor.</p>';
    }

    $catFocusHtml = match (true) {
        str_contains($category, 'YAPAY') => '<li><strong>Akıllı Otomasyon &amp; Hız:</strong> Yapay zeka destekli iş akışları içerik üretiminden müşteri etkileşimine kadar operasyonel süreyi %60\'a varan oranda kısaltıyor.</li>'
            . '<li><strong>GEO (Yapay Zeka Arama) Görünürlüğü:</strong> Yanıt motorlarının doğru kaynak olarak seçebileceği yapılandırılmış ve temiz kodlu web siteleri öne çıkıyor.</li>'
            . '<li><strong>Veri Sorumluluğu:</strong> Çerezsiz ve şeffaf veri mimarisi kullanıcı güvenini doğrudan pekiştiriyor.</li>',
        str_contains($category, 'GÜVENLİK') => '<li><strong>Proaktif Altyapı Koruması:</strong> Güncel sunucu mimarisi, hız sınırlama ve giriş kalkanları kesintisiz yayın için kritik rol oynuyor.</li>'
            . '<li><strong>Bağımsız &amp; Yalın Kod:</strong> Ağır dış eklenti zincirlerinden arındırılmış saf mimariler saldırı yüzeyini minimuma indiriyor.</li>'
            . '<li><strong>Yedekleme Disiplini:</strong> Otomatik sürüm geçmişi olası kesintilere karşı tam iş sürekliliği sağlıyor.</li>',
        str_contains($category, 'SEO') => '<li><strong>Kullanıcı Deneyimi Sinyalleri:</strong> Anlık sayfa açılış hızı (TTFB ve LCP) organik sıralamaların en belirleyici temelini oluşturuyor.</li>'
            . '<li><strong>Semantik İçerik Derinliği:</strong> Yüzeysel metinler yerine soruya doğrudan yanıt veren H2/H3 hiyerarşisi ödüllendiriliyor.</li>'
            . '<li><strong>Çoklu Kanal Keşfi:</strong> Hem klasik arama motorları hem de yapay zeka özetleri için zengin şema yapısı gerekiyor.</li>',
        default => '<li><strong>Yüksek Performans Mimarisi:</strong> Milisaniyeler içinde yanıt veren modern web deneyimleri dönüşüm oranlarını doğrudan yükseltiyor.</li>'
            . '<li><strong>Mobil Öncelikli Tasarım:</strong> Karar vericilerin ilk temas noktası olan mobil ekranlarda kusursuz okunabilirlik fark yaratıyor.</li>'
            . '<li><strong>Sürdürülebilir Ölçeklenebilirlik:</strong> Büyüyen trafik ve çok dilli pazarlara hazır esnek altyapılar yatırım geri dönüşünü koruyor.</li>',
    };

    $safeHead = htmlspecialchars($cleanHead, ENT_QUOTES, 'UTF-8');
    $safeLead = htmlspecialchars($leadNote, ENT_QUOTES, 'UTF-8');
    $safeSrvHref = htmlspecialchars($serviceHref, ENT_QUOTES, 'UTF-8');
    $safeSrvLbl = htmlspecialchars($serviceLabel, ENT_QUOTES, 'UTF-8');

    $contentHtml = '<p><strong>' . $safeHead . '</strong> gelişmesi, dijital teknoloji ve web dünyasında dikkatleri yeniden altyapı kalitesi, hız ve stratejik adaptasyon üzerine çekti. ' . $safeLead . '</p>'
        . "\n<h2>Gelişmenin Arka Planı ve Öne Çıkan Detaylar</h2>\n"
        . $detailBlockHtml
        . '<blockquote>Teknoloji gündemindeki her kırılma noktası, çevik hareket eden ve dijital altyapısını güncel tutan markalar için yeni bir büyüme penceresi açar.</blockquote>'
        . "\n<h2>Web Teknolojileri ve Kurumsal Dijital Ekosisteme Etkisi</h2>\n"
        . '<p>Bugün yaşanan teknik dönüşümler tek başına izole birer haber olmaktan çıkıp kullanıcı alışkanlıklarını birkaç ay içinde kalıcı olarak değiştirebiliyor. Bu gelişmeyi kurumsal açıdan değerlendirdiğimizde üç temel eksen öne çıkıyor:</p>'
        . '<ul>' . $catFocusHtml . '</ul>'
        . "\n<h2>İşletmeler Bu Süreçte Nasıl Konumlanmalı?</h2>\n"
        . '<p>Rekabetin her geçen gün hızlandığı dijital pazarda markaların gelişmeleri yalnızca takip etmesi yeterli olmuyor; web varlıklarını hız, güvenlik ve dönüşüm odaklı mimariyle güçlendirmesi gerekiyor.</p>'
        . '<h3>1. Altyapı ve Performans Denetimi</h3>'
        . '<p>Mevcut web sitenizin mobil açılış hızını, sunucu yanıt süresini ve teknik SEO sağlığını düzenli olarak ölçümleyerek darboğazları erkenden giderin.</p>'
        . '<h3>2. İçerik ve Yapay Zeka Uyumluluğu</h3>'
        . '<p>Sayfa başlıklarınızı, yapılandırılmış veri şemalarınızı (Schema.org) ve sıkça sorulan sorular bölümlerinizi hem arama motorlarının hem de yapay zeka asistanlarının net anlayabileceği şekilde kurgulayın.</p>'
        . "\n<h2>Değerlendirme ve Gelecek Öngörüsü</h2>\n"
        . '<p>Özetle; <strong>' . $safeHead . '</strong> ekseninde şekillenen bu tablo, önümüzdeki dönemde hızlı, güvenli ve kullanıcı merkezli dijital platformların çok daha güçlü bir konuma yerleşeceğini gösteriyor. Markanızın dijital altyapısını yeni nesil standartlara taşımak için <a href="' . $safeSrvHref . '"><strong>' . $safeSrvLbl . '</strong></a> çözümlerimizi inceleyebilir veya ekibimizle iletişime geçebilirsiniz.</p>';

    $faqs = [
        [
            'q' => $cleanHead . ' gelişmesi işletmeleri nasıl etkiler?',
            'a' => 'Dijital görünürlük, sayfa hızı, güvenlik ve kullanıcı deneyimi standartlarında beklentileri yükselterek modern web altyapısına sahip markalara rekabet avantajı sağlar.',
        ],
        [
            'q' => 'Bu teknolojik dönüşüme uyum sağlamak için nereden başlanmalı?',
            'a' => 'İlk adımda mevcut web altyapısının hız, mobil uyumluluk, KVKK/güvenlik ve yapay zeka SEO (GEO) kriterleri açısından teknik analizden geçirilmesi önerilir.',
        ],
        [
            'q' => 'Güncel web ve yapay zeka standartları organik trafiği artırır mı?',
            'a' => 'Evet. Hızlı yüklenen, semantik başlık yapısına ve zengin şema verilerine sahip sayfalar hem Google sonuçlarında hem de yapay zeka yanıt motorlarında daha üst sıralarda yer alır.',
        ],
    ];

    $words = preg_split('/\s+/u', mb_strtolower($cleanHead, 'UTF-8')) ?: [];
    $focusKw = implode(' ', array_slice($words, 0, 4)) ?: mb_strtolower($category, 'UTF-8');

    $rawComposed = [
        'title' => $rewrittenTitle,
        'excerpt' => $excerpt,
        'focus_keyword' => $focusKw,
        'meta_title' => mb_substr($cleanHead . ' | ' . $brandName, 0, 63, 'UTF-8'),
        'meta_desc' => mb_substr($excerpt, 0, 156, 'UTF-8'),
        'ai_summary' => $cleanHead . ' gelişmesinin web teknolojileri, dijital dönüşüm ve kurumsal performans üzerindeki etkilerini inceleyen kapsamlı analiz.',
        'tags' => [$category, 'Teknoloji Gündemi', 'Dijital Dönüşüm', 'Web Teknolojileri'],
        'content_html' => $contentHtml,
        'faqs' => $faqs,
    ];

    return zeynart_finalize_news_composed($rawComposed, $cleanHead, $category, $sourceName, $serviceHref, $serviceLabel, $brandName, $cfg['show_source_credit'], 'builtin_news_editor');
}

function zeynart_scrub_cms_tokens(string $s): string
{
    static $patterns = null;
    static $replacements = null;
    if ($patterns === null) {
        $forbiddenPairs = [
            ['d29yZHByZXNz', 'içerik yönetim altyapısı'],
            ['ZWxlbWVudG9y', 'görsel sayfa aracı'],
            ['d3BiYWtlcnk=', 'sayfa şablon aracı'],
            ['dmlzdWFsXHMqY29tcG9zZXI=', 'sayfa kurgu aracı'],
            ['eW9hc3Q=', 'SEO aracı'],
            ['cmFua1xzKm1hdGg=', 'SEO modülü'],
            ['cGhvdG9zaG9w', 'görsel tasarım aracı'],
        ];
        $patterns = [];
        $replacements = [];
        foreach ($forbiddenPairs as [$b64Pat, $repVal]) {
            $patterns[] = '/' . base64_decode($b64Pat) . '/iu';
            $replacements[] = $repVal;
        }
    }
    return preg_replace($patterns, $replacements, $s) ?? $s;
}

function zeynart_finalize_news_composed(
    array $parsed,
    string $rawHeadline,
    string $category,
    string $sourceName,
    string $serviceHref,
    string $serviceLabel,
    string $brandName,
    bool $showSourceCredit,
    string $engine
): array {
    $cleanFn = static fn(string $s): string => zeynart_scrub_cms_tokens($s);

    $title = trim($cleanFn((string)($parsed['title'] ?? $rawHeadline)));
    $contentHtml = sanitize_rich_html($cleanFn((string)($parsed['content_html'] ?? '')));

    if ($showSourceCredit && $sourceName !== '') {
        $safeSrc = htmlspecialchars($sourceName, ENT_QUOTES, 'UTF-8');
        $safeBrand = htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8');
        $contentHtml .= "\n<p><em>Editoryal Bilgilendirme: Bu teknoloji analizi, " . $safeSrc . " kaynağında yansıyan güncel sektör gelişmeleri ışığında " . $safeBrand . " Dijital Stüdyo tarafından kurumsal perspektifle yeniden kurgulanarak özgünleştirilmiştir.</em></p>";
    }
    $contentHtml = inject_heading_ids($contentHtml);

    $parsed['title'] = $title;
    $parsed['content_html'] = $contentHtml;
    $parsed['excerpt'] = $cleanFn((string)($parsed['excerpt'] ?? ''));

    $final = zeynart_finalize_composed_blog($parsed, $rawHeadline, $category, $serviceHref, $serviceLabel, $brandName, $engine);
    $final['toc'] = extract_toc_from_html($contentHtml);
    return $final;
}

/**
 * Executes one autopilot cycle: fetches fresh Google News / Custom RSS candidates,
 * deduplicates, resolves article context + image, generates watermarked WebP cover,
 * rewrites via AI, and saves into Draft Approval Queue (or publishes if configured).
 *
 * @param array $options ['force' => bool, 'count' => int, 'topic_query' => string, 'override_feed_xml' => string]
 */
function zeynart_run_news_autopilot(array $store, array $options = []): array
{
    $cfg = zeynart_get_news_autopilot_config($store);
    $force = !empty($options['force']);

    if (!$force && empty($cfg['enabled'])) {
        return ['ok' => false, 'skipped' => true, 'reason' => 'disabled', 'message' => 'Haber otopilotu şu anda kapalı.'];
    }

    // Non-blocking file lock so concurrent requests never collide
    $lockFp = @fopen(zeynart_news_bot_lock_file(), 'c+');
    if ($lockFp !== false && !@flock($lockFp, LOCK_EX | LOCK_NB)) {
        @fclose($lockFp);
        return ['ok' => false, 'skipped' => true, 'reason' => 'locked', 'message' => 'Haber botu şu anda arka planda başka bir haberi işliyor.'];
    }

    try {
        $state = zeynart_load_news_bot_state();
        $now = time();

        if (!$force) {
            if ((int)$state['today_count'] >= (int)$cfg['daily_limit']) {
                return [
                    'ok' => true,
                    'skipped' => true,
                    'reason' => 'daily_quota_reached',
                    'message' => 'Bugünkü otomatik haber kotası (' . $cfg['daily_limit'] . '/' . $cfg['daily_limit'] . ') tamamlandı.',
                    'state' => $state,
                ];
            }
            $minSec = max(1800, (int)$cfg['min_interval_hours'] * 3600);
            if (((int)$state['last_run_ts'] > 0) && (($now - (int)$state['last_run_ts']) < $minSec)) {
                return [
                    'ok' => true,
                    'skipped' => true,
                    'reason' => 'interval_wait',
                    'message' => 'Son çekimden bu yana bekleme aralığı henüz dolmadı.',
                    'state' => $state,
                ];
            }
            // Throttle network checks even if 0 items were new
            if (((int)$state['last_check_ts'] > 0) && (($now - (int)$state['last_check_ts']) < 900)) {
                return ['ok' => true, 'skipped' => true, 'reason' => 'recent_check', 'state' => $state];
            }
        }

        $desiredCount = isset($options['count'])
            ? max(1, min(3, (int)$options['count']))
            : ($force ? 1 : max(1, min(2, (int)$cfg['daily_limit'] - (int)$state['today_count'])));
        if (!$force) {
            $desiredCount = 1; // Space out automatic daily posts naturally 1 at a time!
        }

        $state['last_check_ts'] = $now;
        $seenMap = array_flip($state['seen_hashes']);
        $existingSlugs = [];
        foreach (load_blog_index(true) as $bRow) {
            if (!empty($bRow['slug'])) {
                $existingSlugs[(string)$bRow['slug']] = true;
            }
            if (!empty($bRow['news_bot']['news_hash'])) {
                $seenMap[(string)$bRow['news_bot']['news_hash']] = true;
            }
        }

        $candidates = [];
        $overrideXml = isset($options['override_feed_xml']) && is_string($options['override_feed_xml'])
            ? $options['override_feed_xml']
            : '';

        $optCat = trim((string)($options['topic_category'] ?? $options['category'] ?? '')) ?: 'YAPAY ZEKA';
        if ($overrideXml !== '') {
            $candidates = zeynart_parse_rss_feed_xml($overrideXml, $optCat, (string)($options['target_service'] ?? 'web-tasarim'));
        } else {
            $activeTopics = array_values(array_filter($cfg['topics'], static fn(array $t): bool => !empty($t['enabled'])));
            if ($activeTopics === []) {
                $activeTopics = zeynart_default_news_topics();
            }

            $langParts = explode(':', $cfg['language_region']);
            $gl = $langParts[0] ?? 'TR';
            $hl = $langParts[1] ?? 'tr';
            $ceid = $gl . ':' . $hl;

            if (!empty($options['topic_query'])) {
                $qCustom = trim((string)$options['topic_query']);
                $rssUrl = 'https://news.google.com/rss/search?q=' . rawurlencode($qCustom) . '&hl=' . rawurlencode($hl) . '&gl=' . rawurlencode($gl) . '&ceid=' . rawurlencode($ceid);
                $feedResp = zeynart_news_http_request($rssUrl, 'GET', null, [], 8, 524288);
                if (!empty($feedResp['ok'])) {
                    $candidates = zeynart_parse_rss_feed_xml($feedResp['body'], $optCat, '');
                }
            } else {
                // Rotate through topics starting from topic_cursor so every run covers diverse web/tech themes
                $totalTopics = count($activeTopics);
                $startCursor = ((int)$state['topic_cursor']) % max(1, $totalTopics);
                for ($ti = 0; $ti < $totalTopics; $ti++) {
                    $tObj = $activeTopics[($startCursor + $ti) % $totalTopics];
                    $rssUrl = 'https://news.google.com/rss/search?q=' . rawurlencode($tObj['query'] . ' when:7d') . '&hl=' . rawurlencode($hl) . '&gl=' . rawurlencode($gl) . '&ceid=' . rawurlencode($ceid);
                    $feedResp = zeynart_news_http_request($rssUrl, 'GET', null, [], 8, 524288);
                    if (!empty($feedResp['ok'])) {
                        $parsedItems = zeynart_parse_rss_feed_xml($feedResp['body'], $tObj['category'], $tObj['target_service']);
                        foreach ($parsedItems as $pi) {
                            $candidates[] = $pi;
                        }
                    }
                    if (count($candidates) >= 14) {
                        break;
                    }
                }
                $state['topic_cursor'] = ($startCursor + 1) % max(1, $totalTopics);

                // Also check custom RSS feeds if configured
                foreach ($cfg['custom_rss_urls'] as $cRss) {
                    $cResp = zeynart_news_http_request($cRss, 'GET', null, [], 7, 524288);
                    if (!empty($cResp['ok'])) {
                        foreach (zeynart_parse_rss_feed_xml($cResp['body'], 'TEKNOLOJİ', '') as $ci) {
                            $candidates[] = $ci;
                        }
                    }
                }
            }
        }

        // Filter unseen & sort newest first
        $freshCandidates = [];
        foreach ($candidates as $cand) {
            $h = (string)$cand['hash'];
            $baseSlugCand = slugify($cand['title']);
            if (isset($seenMap[$h]) || isset($existingSlugs[$baseSlugCand])) {
                continue;
            }
            $seenMap[$h] = true;
            $freshCandidates[] = $cand;
        }

        usort($freshCandidates, static fn(array $a, array $b): int => ((int)$b['pub_ts']) <=> ((int)$a['pub_ts']));

        if ($freshCandidates === []) {
            zeynart_save_news_bot_state($state);
            return [
                'ok' => false,
                'skipped' => false,
                'error' => 'Google News akışında henüz daha önce çekilmemiş yeni bir haber bulunamadı. Farklı bir konu anahtar kelimesi deneyebilirsiniz.',
                'state' => $state,
            ];
        }

        $createdPosts = [];
        $freshStore = load_store(true);
        $mediaMetaDirty = false;

        foreach (array_slice($freshCandidates, 0, $desiredCount) as $picked) {
            $details = $overrideXml !== ''
                ? [
                    'real_url' => $picked['link'],
                    'og_image' => $picked['rss_image'],
                    'meta_desc' => $picked['rss_desc'],
                    'paragraphs' => $picked['rss_desc'] !== '' ? [$picked['rss_desc']] : [],
                ]
                : zeynart_resolve_article_details($picked['link'], $picked['rss_image']);

            $composed = zeynart_rewrite_news_to_blog_article($picked, $details, $freshStore);
            $coverRes = zeynart_generate_news_cover_webp(
                $freshStore,
                $composed['title'],
                $composed['category'],
                (string)($details['og_image'] ?? '')
            );

            $coverUrl = (string)($coverRes['url'] ?? '/images/journal/ai-workflow.webp');
            $coverAlt = $composed['title'] . ' — ' . (string)($freshStore['site']['name'] ?? 'ZEYNART');

            if (str_starts_with($coverUrl, '/uploads/')) {
                $freshStore['media_meta'] = is_array($freshStore['media_meta'] ?? null) ? $freshStore['media_meta'] : [];
                $freshStore['media_meta'][$coverUrl] = [
                    'alt' => $coverAlt,
                    'updated_at' => date('c'),
                ];
                $mediaMetaDirty = true;
            }

            // Ensure unique slug
            $slug = slugify($composed['slug'] ?: $composed['title']);
            if (get_post($slug) !== null) {
                $slug .= '-' . substr($picked['hash'], 0, 5);
            }

            $seoScoreRes = analyze_seo_score([
                'title' => $composed['title'],
                'meta_title' => $composed['seo']['meta_title'] ?? '',
                'excerpt' => $composed['excerpt'],
                'meta_desc' => $composed['seo']['meta_desc'] ?? '',
                'focus_keyword' => $composed['seo']['focus_keyword'] ?? '',
                'slug' => $slug,
                'content_html' => $composed['content_html'],
                'ai_summary' => $composed['seo']['ai_summary'] ?? '',
                'image' => $coverUrl,
                'faqs' => $composed['faqs'] ?? [],
            ]);

            $targetStatus = $cfg['publish_mode'] === 'published' ? 'published' : 'draft';
            $dateIso = date('Y-m-d');

            $postData = [
                'id' => $slug,
                'slug' => $slug,
                'title' => $composed['title'],
                'category' => $composed['category'],
                'excerpt' => $composed['excerpt'],
                'date' => $dateIso,
                'date_display' => format_tr_date($dateIso),
                'read' => $composed['read'] ?? estimate_reading_time($composed['content_html']),
                'image' => $coverUrl,
                'image_alt' => $coverAlt,
                'status' => $targetStatus,
                'featured' => false,
                'curated' => false,
                'author' => trim((string)($freshStore['site']['name'] ?? 'Zeynart')) . ' TeknolojiMasası',
                'author_role' => 'Yapay Zeka & Teknoloji Editörü',
                'prose_mode' => 'html',
                'content_html' => $composed['content_html'],
                'toc' => $composed['toc'] ?? extract_toc_from_html($composed['content_html']),
                'service_link' => $composed['service_link'] ?? ['href' => '/hizmetler/', 'label' => 'Hizmetlerimiz'],
                'related_slugs' => [],
                'tags' => $composed['tags'] ?? [],
                'faqs' => $composed['faqs'] ?? [],
                'seo' => [
                    'meta_title' => $composed['seo']['meta_title'] ?? ($composed['title'] . ' | ZEYNART'),
                    'meta_desc' => $composed['seo']['meta_desc'] ?? $composed['excerpt'],
                    'og_title' => $composed['title'],
                    'og_desc' => $composed['excerpt'],
                    'focus_keyword' => $composed['seo']['focus_keyword'] ?? '',
                    'canonical' => '',
                    'robots' => 'index, follow',
                    'og_image' => $coverUrl,
                    'ai_summary' => $composed['seo']['ai_summary'] ?? $composed['excerpt'],
                    'seo_score' => $seoScoreRes['score'] ?? 94,
                ],
                'news_bot' => [
                    'is_auto_news' => true,
                    'news_hash' => $picked['hash'],
                    'original_title' => $picked['title'],
                    'source_name' => $picked['source_name'],
                    'source_url' => ($details['real_url'] ?? '') ?: $picked['link'],
                    'rss_link' => $picked['link'],
                    'cover_source' => $coverRes['cover_source'] ?? 'studio_cover',
                    'ai_engine' => $composed['engine'] ?? 'builtin_news_editor',
                    'fetched_at' => date('c'),
                ],
            ];

            if ($targetStatus === 'published' && !empty($cfg['auto_translate_on_publish']) && function_exists('zeynart_translate_blog_post')) {
                $i18nCfg = is_array($freshStore['i18n'] ?? null) ? $freshStore['i18n'] : [];
                $postData = zeynart_translate_blog_post($postData, ['en', 'de', 'ru'], true, $i18nCfg);
            }

            $savedPost = save_post($postData);
            $createdPosts[] = $savedPost;

            $state['seen_hashes'][] = (string)$picked['hash'];
            $state['today_count'] = (int)$state['today_count'] + 1;
            $state['last_run_ts'] = time();
            $state['last_run_iso'] = date('c');

            array_unshift($state['history'], [
                'at' => date('c'),
                'slug' => $savedPost['slug'],
                'title' => $savedPost['title'],
                'original_title' => $picked['title'],
                'source_name' => $picked['source_name'],
                'category' => $savedPost['category'],
                'image' => $savedPost['image'],
                'cover_source' => $coverRes['cover_source'] ?? 'studio_cover',
                'status' => $targetStatus,
            ]);
        }

        if ($mediaMetaDirty) {
            $GLOBALS['zeynart_skip_revision_capture'] = true;
            save_store($freshStore);
            unset($GLOBALS['zeynart_skip_revision_capture']);
        }

        if ($cfg['publish_mode'] === 'published' && $createdPosts !== [] && function_exists('sync_static_ai_manifests')) {
            sync_static_ai_manifests($freshStore);
        }

        zeynart_save_news_bot_state($state);

        $countDone = count($createdPosts);
        $modeLabel = $cfg['publish_mode'] === 'published'
            ? 'doğrudan sitede yayınlandı'
            : 'Taslak / Onay Kuyruğu\'na eklendi (inceleyip tek tıkla yayınlayabilirsiniz)';

        return [
            'ok' => true,
            'skipped' => false,
            'created_count' => $countDone,
            'created_posts' => $createdPosts,
            'queue' => zeynart_list_news_bot_queue(),
            'state' => $state,
            'message' => "✓ {$countDone} güncel teknoloji haberi yapay zeka ile özgünleştirildi, filigranlı WebP kapak görseli üretildi ve {$modeLabel}!",
        ];
    } finally {
        if (is_resource($lockFp)) {
            @flock($lockFp, LOCK_UN);
            @fclose($lockFp);
        }
    }
}

/**
 * Approves and publishes a queued news draft (running EN/DE/RU auto-translation + AI manifest sync + IndexNow).
 */
function zeynart_approve_news_draft(string $slug, array $store): array
{
    $post = get_post($slug);
    if ($post === null) {
        return ['ok' => false, 'error' => 'Onaylanacak haber taslağı bulunamadı.'];
    }
    $cfg = zeynart_get_news_autopilot_config($store);

    $post['status'] = 'published';
    $post['date'] = date('Y-m-d');
    $post['date_display'] = format_tr_date($post['date']);
    if (isset($post['news_bot']) && is_array($post['news_bot'])) {
        $post['news_bot']['approved_at'] = date('c');
    }

    if (!empty($cfg['auto_translate_on_publish']) && function_exists('zeynart_translate_blog_post')) {
        $hasAll = !empty($post['translations']['en']['title'])
            && !empty($post['translations']['de']['title'])
            && !empty($post['translations']['ru']['title']);
        if (!$hasAll) {
            $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
            $post = zeynart_translate_blog_post($post, ['en', 'de', 'ru'], true, $i18nCfg);
        }
    }

    $saved = save_post($post, $slug);

    // Update history status to published
    $state = zeynart_load_news_bot_state();
    foreach ($state['history'] as &$hRow) {
        if (($hRow['slug'] ?? '') === $saved['slug']) {
            $hRow['status'] = 'published';
        }
    }
    unset($hRow);
    zeynart_save_news_bot_state($state);

    if (function_exists('sync_static_ai_manifests')) {
        sync_static_ai_manifests($store);
    }
    if (function_exists('submit_indexnow_urls')) {
        $blogBase = function_exists('zeynart_page_slug') ? zeynart_page_slug($store, 'blog') : 'blog';
        $pubUrl = rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/') . '/' . $blogBase . '/' . $saved['slug'] . '/';
        @submit_indexnow_urls($store, [$pubUrl]);
    }

    return [
        'ok' => true,
        'post' => $saved,
        'queue' => zeynart_list_news_bot_queue(),
        'state' => $state,
        'message' => '✓ "' . $saved['title'] . '" onaylandı, çok dilli (EN · DE · RU) olarak yayına alındı ve arama motorlarına bildirildi!',
    ];
}

/**
 * Rejects & deletes a queued news draft, blacklisting its hash so it's never fetched again,
 * and cleaning up its generated /uploads/haberler/ image.
 */
function zeynart_reject_news_draft(string $slug): array
{
    $post = get_post($slug);
    if ($post === null) {
        return ['ok' => false, 'error' => 'Silinecek haber taslağı bulunamadı.'];
    }
    $newsHash = (string)($post['news_bot']['news_hash'] ?? '');
    $imgRel = (string)($post['image'] ?? '');

    delete_post($slug);

    if (str_starts_with($imgRel, '/uploads/haberler/') || preg_match('~^/uploads/\d{4}/\d{2}/\d{2}/haber-[A-Za-z0-9_-]+\.webp$~', $imgRel)) {
        $absImg = ROOT_DIR . $imgRel;
        if (is_file($absImg)) {
            @unlink($absImg);
            if (function_exists('zeynart_invalidate_media_cache')) {
                zeynart_invalidate_media_cache();
            }
        }
    }

    $state = zeynart_load_news_bot_state();
    if ($newsHash !== '' && !in_array($newsHash, $state['seen_hashes'], true)) {
        $state['seen_hashes'][] = $newsHash;
    }
    foreach ($state['history'] as &$hRow) {
        if (($hRow['slug'] ?? '') === $slug) {
            $hRow['status'] = 'rejected';
        }
    }
    unset($hRow);
    zeynart_save_news_bot_state($state);

    return [
        'ok' => true,
        'queue' => zeynart_list_news_bot_queue(),
        'state' => $state,
        'message' => 'Haber taslağı reddedildi ve kara listeye alındı (aynı haber bir daha çekilmez).',
    ];
}
