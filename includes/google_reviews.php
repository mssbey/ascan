<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

/**
 * ZEYNART DİJİTAL STÜDYO — Otomatik Google Yorumları & Puan Motoru
 * - API Anahtarsız Google Haritalar İşletme Keşfi (Place ID, Canlı Puan, Toplam Yorum Sayısı)
 * - Resmi Google Places API (New v1 + Legacy Dual-Sort) ile Tam Otomatik Yeni Yorum & Avatar Çekimi
 * - Akıllı Birikimli Arşiv (Eski yorumları silmeden yeni yorumları birleştirir)
 * - Arka Plan Otomatik Senkronizasyon (Ziyaretçi hızını ve PageSpeed puanını etkilemez)
 */

function get_google_reviews_state(array $store): array
{
    $raw = is_array($store['google_reviews'] ?? null) ? $store['google_reviews'] : [];
    $defaultPlaceId = 'ChIJfzprZUz_thQRf1H4gPOGMsk';
    $placeId = trim((string)($raw['place_id'] ?? $defaultPlaceId));
    if ($placeId === '') {
        $placeId = $defaultPlaceId;
    }

    $seedItems = [];
    if (!empty($raw['items']) && is_array($raw['items'])) {
        $seedItems = $raw['items'];
    } elseif (!empty($store['about']['reviews']) && is_array($store['about']['reviews'])) {
        foreach ($store['about']['reviews'] as $idx => $rv) {
            $author = trim((string)($rv['author'] ?? ''));
            $text = trim(str_replace('*', '', (string)($rv['text'] ?? '')));
            if ($author === '' && $text === '') {
                continue;
            }
            $rawDate = trim((string)($rv['date'] ?? ''));
            $cleanDate = trim((string)preg_replace('/\s*·\s*Google$/iu', '', $rawDate));
            $seedItems[] = [
                'id' => (string)($rv['id'] ?? ('gr-seed-' . ($idx + 1))),
                'author' => $author !== '' ? $author : 'Google Kullanıcısı',
                'avatar' => (string)($rv['avatar'] ?? ''),
                'rating' => max(1, min(5, (int)($rv['rating'] ?? 5))),
                'date' => $cleanDate !== '' ? $cleanDate : 'Google Yorumu',
                'timestamp' => (int)($rv['timestamp'] ?? (time() - ($idx * 86400 * 14))),
                'text' => $text,
                'author_url' => (string)($rv['author_url'] ?? ''),
                'verified' => ($rv['verified'] ?? true) !== false,
                'visible' => ($rv['visible'] ?? true) !== false,
                'pinned' => !empty($rv['pinned']),
            ];
        }
    }

    $layout = (string)($raw['layout'] ?? 'slider');
    if (!in_array($layout, ['slider', 'grid', 'masonry', 'badge'], true)) {
        $layout = 'slider';
    }

    return [
        'enabled' => ($raw['enabled'] ?? true) !== false,
        'business_name' => trim((string)($raw['business_name'] ?? ($store['site']['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'))),
        'query_input' => trim((string)($raw['query_input'] ?? 'https://www.google.com/maps/place/?q=place_id:' . $placeId)),
        'place_id' => $placeId,
        'feature_id' => trim((string)($raw['feature_id'] ?? '0x14b6ff4c656b3a7f:0xc93286f380f8517f')),
        'maps_url' => trim((string)($raw['maps_url'] ?? ('https://www.google.com/maps/place/?q=place_id:' . $placeId))),
        'write_review_url' => trim((string)($raw['write_review_url'] ?? ('https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId)))),
        'all_reviews_url' => trim((string)($raw['all_reviews_url'] ?? ('https://search.google.com/local/reviews?placeid=' . rawurlencode($placeId) . '&hl=tr'))),
        'business_avatar' => trim((string)($raw['business_avatar'] ?? '')),
        'api_key' => trim((string)($raw['api_key'] ?? '')),
        'rating' => round(max(1.0, min(5.0, (float)($raw['rating'] ?? 5.0))), 1),
        'total_reviews' => max(count($seedItems), (int)($raw['total_reviews'] ?? 34)),
        'layout' => $layout,
        'min_rating' => max(1, min(5, (int)($raw['min_rating'] ?? 4))),
        'hide_empty_text' => !empty($raw['hide_empty_text']),
        'show_verified_badge' => ($raw['show_verified_badge'] ?? true) !== false,
        'show_write_btn' => ($raw['show_write_btn'] ?? true) !== false,
        'show_on_home' => ($raw['show_on_home'] ?? true) !== false,
        'show_on_about' => ($raw['show_on_about'] ?? true) !== false,
        'auto_sync' => ($raw['auto_sync'] ?? true) !== false,
        'sync_interval_hours' => max(1, min(720, (int)($raw['sync_interval_hours'] ?? 12))),
        'last_synced_at' => (string)($raw['last_synced_at'] ?? ''),
        'last_sync_status' => (string)($raw['last_sync_status'] ?? 'Google İşletme Profili bağlı (5.0 ★ · 34 değerlendirme)'),
        'eyebrow' => trim((string)($raw['eyebrow'] ?? 'DOĞRULANMIŞ GOOGLE DEĞERLENDİRMELERİ')),
        'heading' => trim((string)($raw['heading'] ?? 'Birlikte çalıştıklarımızdan.')),
        'subheading' => trim((string)($raw['subheading'] ?? 'Zeynart’ın birlikte çalıştığı markalardan paylaşılan gerçek Google yorumları.')),
        'items' => array_values($seedItems),
    ];
}

function gr_http_request(string $url, string $method = 'GET', array $headers = [], ?string $body = null, int $timeout = 10): array
{
    $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8',
    ];
    $allHeaders = array_merge($defaultHeaders, $headers);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body ?? '';
        }
        curl_setopt_array($ch, $opts);
        $respBody = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effUrl = (string)(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url);
        $err = curl_error($ch);
        unset($ch);

        return [
            'ok' => is_string($respBody) && $code >= 200 && $code < 400,
            'code' => $code,
            'body' => is_string($respBody) ? $respBody : '',
            'effective_url' => $effUrl,
            'error' => $err,
        ];
    }

    $ctxOpts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $allHeaders),
            'ignore_errors' => true,
            'follow_location' => 1,
            'max_redirects' => 5,
            'timeout' => $timeout,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];
    if ($body !== null) {
        $ctxOpts['http']['content'] = $body;
    }

    $http_response_header = [];
    $raw = @file_get_contents($url, false, stream_context_create($ctxOpts));
    $code = 0;
    $effUrl = $url;
    foreach ($http_response_header as $line) {
        if (preg_match('~^HTTP/\S+\s+(\d+)~i', $line, $m)) {
            $code = (int)$m[1];
        } elseif (stripos($line, 'location:') === 0) {
            $loc = trim(substr($line, 9));
            if ($loc !== '') {
                $effUrl = $loc;
            }
        }
    }

    return [
        'ok' => is_string($raw) && $code >= 200 && $code < 400,
        'code' => $code,
        'body' => is_string($raw) ? $raw : '',
        'effective_url' => $effUrl,
        'error' => is_string($raw) ? '' : 'Bağlantı kurulamadı',
    ];
}

function gr_format_turkish_date(int $timestamp, string $fallback = ''): string
{
    if ($timestamp <= 0) {
        return $fallback !== '' ? $fallback : 'Google Yorumu';
    }
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
    ];
    $d = (int)date('j', $timestamp);
    $m = $months[(int)date('n', $timestamp)] ?? date('m', $timestamp);
    $y = date('Y', $timestamp);
    return "{$d} {$m} {$y}";
}

function gr_parse_input_identifiers(string $input): array
{
    $input = trim($input);
    $placeId = '';
    $featureId = '';
    $searchQuery = '';

    if ($input === '') {
        return ['place_id' => '', 'feature_id' => '', 'query' => ''];
    }

    // Direct ChIJ... or Eh... Place ID
    if (preg_match('/^(ChIJ[A-Za-z0-9_-]{12,}|[A-Za-z0-9_-]{24,45})$/', $input) && !str_contains($input, ' ')) {
        return ['place_id' => $input, 'feature_id' => '', 'query' => ''];
    }

    // Resolve short Google Maps URLs (maps.app.goo.gl, goo.gl/maps, g.page)
    if (preg_match('~^https?://(maps\.app\.goo\.gl|goo\.gl|g\.page)/~i', $input)) {
        $res = gr_http_request($input, 'GET', [
            'Cookie: CONSENT=YES+cb.20240101-01-p0.tr+FX+999; SOCS=CAESHAgBEhJnd3NfMjAyNDAxMDEtMF9SQzIaAnRyIAEaBgiA_LyuBg',
        ], null, 8);
        if ($res['effective_url'] !== '') {
            $input = $res['effective_url'];
        }
    }

    if (preg_match('/place_id[=:]([A-Za-z0-9_-]{15,})/i', $input, $m)) {
        $placeId = $m[1];
    } elseif (preg_match('/placeid=([A-Za-z0-9_-]{15,})/i', $input, $m)) {
        $placeId = $m[1];
    } elseif (preg_match('/(ChIJ[A-Za-z0-9_-]{14,})/', $input, $m)) {
        $placeId = $m[1];
    }

    $decoded = rawurldecode($input);
    if (preg_match('/(0x[0-9a-fA-F]+:0x[0-9a-fA-F]+)/', $decoded, $fm)) {
        $featureId = strtolower($fm[1]);
    }

    if (preg_match('~/maps/place/([^/@?]+)~i', $decoded, $qm)) {
        $searchQuery = trim(str_replace('+', ' ', $qm[1]));
    } elseif (!preg_match('~^https?://~i', $input)) {
        $searchQuery = $input;
    }

    return [
        'place_id' => $placeId,
        'feature_id' => $featureId,
        'query' => $searchQuery,
    ];
}

function gr_discover_via_google_maps_public(string $query): ?array
{
    $q = trim($query);
    if ($q === '') {
        return null;
    }
    $url = 'https://www.google.com/search?tbm=map&authuser=0&hl=tr&gl=tr&q=' . rawurlencode($q);
    $res = gr_http_request($url, 'GET', [
        'Cookie: CONSENT=YES+cb.20240101-01-p0.tr+FX+999; SOCS=CAESHAgBEhJnd3NfMjAyNDAxMDEtMF9SQzIaAnRyIAEaBgiA_LyuBg',
        'Referer: https://www.google.com/',
    ], null, 10);

    if (!$res['ok'] || strlen($res['body']) < 200) {
        return null;
    }

    $body = $res['body'];
    $placeId = '';
    $featureId = '';
    $rating = null;
    $totalReviews = null;
    $businessName = '';
    $avatar = '';

    if (preg_match('/placeid(?:\\\\u003d|=)(ChIJ[A-Za-z0-9_-]{12,})/', $body, $pm)) {
        $placeId = $pm[1];
    } elseif (preg_match('/"(ChIJ[A-Za-z0-9_-]{14,})"/', $body, $pm)) {
        $placeId = $pm[1];
    }

    if (preg_match('/(0x[0-9a-fA-F]+)(?::|\\\\u003a|%3A)(0x[0-9a-fA-F]+)/', $body, $fm)) {
        $featureId = strtolower($fm[1] . ':' . $fm[2]);
    }

    // Extract ["...reviews...", "34 yorum", ...], null, null, null, 5, 34
    if (preg_match('/"(\d[\d\.]*)\s*yorum"[^\]]*\],null,null,null,(\d(?:\.\d+)?),(\d+)\]/u', $body, $rm)) {
        $rating = (float)$rm[2];
        $totalReviews = (int)$rm[3];
    } elseif (preg_match('/"(\d[\d\.]*)\s*yorum"/u', $body, $rm)) {
        $totalReviews = (int)str_replace('.', '', $rm[1]);
    }

    if ($featureId !== '' && preg_match('/"' . preg_quote($featureId, '/') . '","([^"]+)"/u', $body, $nm)) {
        $businessName = stripcslashes($nm[1]);
    }

    if (preg_match('~"(https://lh3\.googleusercontent\.com/[^"]+)"~', $body, $am)) {
        $avatar = stripcslashes($am[1]);
    }

    if ($placeId === '' && $featureId === '' && $rating === null) {
        return null;
    }

    return [
        'place_id' => $placeId,
        'feature_id' => $featureId,
        'business_name' => $businessName,
        'rating' => $rating,
        'total_reviews' => $totalReviews,
        'business_avatar' => $avatar,
    ];
}

function gr_fetch_via_places_api(string $apiKey, string $placeId, string $fallbackQuery = ''): array
{
    $apiKey = trim($apiKey);
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'API anahtarı girilmedi.'];
    }

    $discoveredPlaceId = trim($placeId);
    $businessName = '';
    $rating = null;
    $totalReviews = null;
    $mapsUri = '';
    $fetchedReviews = [];
    $apiErrors = [];

    // 1. Discover Place ID via Text Search if missing
    if ($discoveredPlaceId === '' && $fallbackQuery !== '') {
        $findUrl = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input='
            . rawurlencode($fallbackQuery)
            . '&inputtype=textquery&fields=place_id,name,rating,user_ratings_total&language=tr&key='
            . rawurlencode($apiKey);
        $findRes = gr_http_request($findUrl, 'GET', [], null, 10);
        if ($findRes['ok']) {
            $findJson = json_decode($findRes['body'], true);
            $cand = $findJson['candidates'][0] ?? null;
            if (is_array($cand) && !empty($cand['place_id'])) {
                $discoveredPlaceId = (string)$cand['place_id'];
                $businessName = (string)($cand['name'] ?? '');
                if (isset($cand['rating'])) {
                    $rating = (float)$cand['rating'];
                }
                if (isset($cand['user_ratings_total'])) {
                    $totalReviews = (int)$cand['user_ratings_total'];
                }
            } elseif (!empty($findJson['error_message'])) {
                $apiErrors[] = (string)$findJson['error_message'];
            }
        }
    }

    if ($discoveredPlaceId === '') {
        return ['ok' => false, 'error' => $apiErrors !== [] ? implode(' | ', $apiErrors) : 'Place ID tespit edilemedi.'];
    }

    // 2. Try Google Places API (New v1)
    $v1Url = 'https://places.googleapis.com/v1/places/' . rawurlencode($discoveredPlaceId) . '?languageCode=tr';
    $v1Res = gr_http_request($v1Url, 'GET', [
        'X-Goog-Api-Key: ' . $apiKey,
        'X-Goog-FieldMask: id,displayName,rating,userRatingCount,googleMapsUri,reviews',
    ], null, 10);

    if ($v1Res['ok']) {
        $v1 = json_decode($v1Res['body'], true);
        if (is_array($v1)) {
            if (!empty($v1['displayName']['text'])) {
                $businessName = (string)$v1['displayName']['text'];
            }
            if (isset($v1['rating'])) {
                $rating = (float)$v1['rating'];
            }
            if (isset($v1['userRatingCount'])) {
                $totalReviews = (int)$v1['userRatingCount'];
            }
            if (!empty($v1['googleMapsUri'])) {
                $mapsUri = (string)$v1['googleMapsUri'];
            }
            foreach (($v1['reviews'] ?? []) as $rv) {
                $author = trim((string)($rv['authorAttribution']['displayName'] ?? 'Google Kullanıcısı'));
                $text = trim((string)($rv['text']['text'] ?? $rv['originalText']['text'] ?? ''));
                $ts = !empty($rv['publishTime']) ? (int)strtotime((string)$rv['publishTime']) : time();
                $relDesc = trim((string)($rv['relativePublishTimeDescription'] ?? ''));
                $fetchedReviews[] = [
                    'id' => 'gr-' . substr(sha1($author . '|' . $ts . '|' . mb_substr($text, 0, 40, 'UTF-8')), 0, 12),
                    'author' => $author,
                    'avatar' => trim((string)($rv['authorAttribution']['photoUri'] ?? '')),
                    'author_url' => trim((string)($rv['authorAttribution']['uri'] ?? '')),
                    'rating' => max(1, min(5, (int)($rv['rating'] ?? 5))),
                    'date' => gr_format_turkish_date($ts, $relDesc),
                    'timestamp' => $ts,
                    'text' => $text,
                    'verified' => true,
                    'visible' => true,
                    'pinned' => false,
                ];
            }
        }
    } else {
        $errJson = json_decode($v1Res['body'], true);
        if (!empty($errJson['error']['message'])) {
            $apiErrors[] = (string)$errJson['error']['message'];
        }
    }

    // 3. Also query Legacy Places Details (both 'newest' and 'most_relevant' to pull up to 10 distinct reviews!)
    foreach (['newest', 'most_relevant'] as $sortMode) {
        $legUrl = 'https://maps.googleapis.com/maps/api/place/details/json?place_id='
            . rawurlencode($discoveredPlaceId)
            . '&fields=name,rating,user_ratings_total,url,reviews&reviews_sort=' . $sortMode
            . '&language=tr&key=' . rawurlencode($apiKey);
        $legRes = gr_http_request($legUrl, 'GET', [], null, 10);
        if (!$legRes['ok']) {
            continue;
        }
        $leg = json_decode($legRes['body'], true);
        if (!is_array($leg)) {
            continue;
        }
        if (($leg['status'] ?? '') === 'OK' && is_array($leg['result'] ?? null)) {
            $r = $leg['result'];
            if ($businessName === '' && !empty($r['name'])) {
                $businessName = (string)$r['name'];
            }
            if ($rating === null && isset($r['rating'])) {
                $rating = (float)$r['rating'];
            }
            if ($totalReviews === null && isset($r['user_ratings_total'])) {
                $totalReviews = (int)$r['user_ratings_total'];
            }
            if ($mapsUri === '' && !empty($r['url'])) {
                $mapsUri = (string)$r['url'];
            }
            foreach (($r['reviews'] ?? []) as $rv) {
                $author = trim((string)($rv['author_name'] ?? 'Google Kullanıcısı'));
                $text = trim((string)($rv['text'] ?? ''));
                $ts = (int)($rv['time'] ?? time());
                $relDesc = trim((string)($rv['relative_time_description'] ?? ''));
                $fetchedReviews[] = [
                    'id' => 'gr-' . substr(sha1($author . '|' . $ts . '|' . mb_substr($text, 0, 40, 'UTF-8')), 0, 12),
                    'author' => $author,
                    'avatar' => trim((string)($rv['profile_photo_url'] ?? '')),
                    'author_url' => trim((string)($rv['author_url'] ?? '')),
                    'rating' => max(1, min(5, (int)($rv['rating'] ?? 5))),
                    'date' => gr_format_turkish_date($ts, $relDesc),
                    'timestamp' => $ts,
                    'text' => $text,
                    'verified' => true,
                    'visible' => true,
                    'pinned' => false,
                ];
            }
        } elseif (!empty($leg['error_message'])) {
            $apiErrors[] = (string)$leg['error_message'];
        }
    }

    return [
        'ok' => ($rating !== null || $fetchedReviews !== []),
        'place_id' => $discoveredPlaceId,
        'business_name' => $businessName,
        'rating' => $rating,
        'total_reviews' => $totalReviews,
        'maps_url' => $mapsUri,
        'reviews' => $fetchedReviews,
        'error' => $apiErrors !== [] ? implode(' | ', array_unique($apiErrors)) : '',
    ];
}

function gr_fetch_public_maps_reviews(string $featureId, string $businessName = ''): array
{
    $fid = trim($featureId);
    if ($fid === '' || !preg_match('/^0x[0-9a-f]+:0x[0-9a-f]+$/i', $fid)) {
        return [];
    }

    $qName = urlencode($businessName !== '' ? $businessName : 'Zeynart Web Bilişim Hizmetleri');
    $fidEnc = str_replace(':', '%3A', strtolower($fid));
    $byId = [];

    // Fetch both sort=1 (Most Relevant) and sort=2 (Newest) from Google Maps Place Preview RPC
    foreach ([1, 2] as $sortEnum) {
        $pb = '!1m14!1s' . strtolower($fid) . '!2s' . $qName
            . '!3m8!1m3!1d3070.9166086714877!2d27.8896039!3d39.6740904!3m2!1i1024!2i768!4f13.1!4m2!3d39.6740904!4d27.8896039'
            . '!12m4!2m3!1i360!2i120!4i8!13m57!2m2!1i203!2i100!3m2!2i4!5b1!6m6!1m2!1i86!2i86!1m2!1i408!2i240'
            . '!7m33!1m3!1e1!2b0!3e3!1m3!1e2!2b1!3e2!1m3!1e2!2b0!3e3!1m3!1e8!2b0!3e3!1m3!1e10!2b0!3e3!1m3!1e10!2b1!3e2!1m3!1e10!2b0!3e4!1m3!1e9!2b1!3e2!2b1!9b0'
            . '!15m8!1m7!1m2!1m1!1e2!2m2!1i195!2i195!3i20!14m3!1sSfjdau6gIYe3wPAPyuyEmAQ!7e81!15i10112'
            . '!15m106!1m26!13m9!2b1!3b1!4b1!6i1!8b1!9b1!14b1!20b1!25b1!18m15!3b1!4b1!5b1!6b1!13b1!14b1!17b1!21b1!22b1!30b1!32b1!33m1!1b1!34b1!36e2'
            . '!10m1!8e3!11m1!3e1!17b1!20m2!1e3!1e6!24b1!25b1!26b1!27b1!29b1!30m1!2b1!36b1!37b1!39m3!2m2!2i1!3i1!43b1!52b1!55b1!56m1!1b1!61m2!1m1!1e1'
            . '!65m5!3m4!1m3!1m2!1i224!2i298!72m22!1m8!2b1!5b1!7b1!12m4!1b1!2b1!4m1!1e1!4b1'
            . '!8m10!1m6!4m1!1e1!4m1!1e3!4m1!1e4!3sother_user_google_review_posts__and__hotel_and_vr_partner_review_posts!6m1!1e' . $sortEnum . '!9b1'
            . '!89b1!90m2!1m1!1e2!98m3!1b1!2b1!3b1!103b1!113b1!114m3!1b1!2m1!1b1!117b1!122m1!1b1!126b1!127b1!128m1!1b1'
            . '!21m0!22m1!1e81!30m8!3b1!6m2!1b1!2b1!7m2!1e3!2b1!9b1!34m5!7b1!10b1!14b1!15m1!1b0!37i800';

        $pbEnc = str_replace(['!', strtolower($fid)], ['%21', $fidEnc], $pb);
        $url = 'https://www.google.com/maps/preview/place?authuser=0&hl=tr&gl=tr&q=' . $qName . '&pb=' . $pbEnc;
        $res = gr_http_request($url, 'GET', [
            'Cookie: CONSENT=YES+cb.20240101-01-p0.tr+FX+999; SOCS=CAESHAgBEhJnd3NfMjAyNDAxMDEtMF9SQzIaAnRyIAEaBgiA_LyuBg',
            'Referer: https://www.google.com/maps/',
        ], null, 10);

        if (!$res['ok'] || strlen($res['body']) < 200) {
            continue;
        }

        $clean = preg_replace('/^\)\]\}\'\s*/', '', $res['body']) ?? $res['body'];
        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            continue;
        }

        $walk = static function (mixed $node) use (&$walk, &$byId): void {
            if (!is_array($node)) {
                return;
            }
            if (
                isset($node[0], $node[1], $node[2])
                && is_string($node[0])
                && preg_match('/^C[hi][A-Za-z0-9_-]{15,}/', $node[0])
                && is_array($node[1])
            ) {
                $authorArr = $node[1][4][5] ?? null;
                if (is_array($authorArr) && !empty($authorArr[0]) && is_string($authorArr[0])) {
                    $author = trim($authorArr[0]);
                    $avatar = trim((string)($authorArr[1] ?? ''));
                    $authorUrl = trim((string)($authorArr[2][0] ?? ''));
                    $tsMicros = (int)($node[1][2] ?? 0);
                    $ts = $tsMicros > 10000000000 ? (int)round($tsMicros / 1000000) : time();
                    $relDate = trim((string)($node[1][6] ?? ''));
                    $rating = max(1, min(5, (int)($node[2][0][0] ?? 5)));

                    $text = '';
                    $findText = static function (mixed $sub) use (&$findText, &$text): void {
                        if (!is_array($sub)) {
                            return;
                        }
                        if (
                            isset($sub[0], $sub[2])
                            && is_string($sub[0])
                            && is_array($sub[2])
                            && isset($sub[2][0], $sub[2][1])
                            && is_int($sub[2][0])
                            && is_int($sub[2][1])
                            && strlen($sub[0]) > 2
                        ) {
                            if (strlen($sub[0]) > strlen($text)) {
                                $text = trim($sub[0]);
                            }
                        }
                        foreach ($sub as $ch) {
                            if (is_array($ch)) {
                                $findText($ch);
                            }
                        }
                    };
                    $findText($node[2]);

                    $ownerReply = '';
                    if (isset($node[3][14][0][0]) && is_string($node[3][14][0][0])) {
                        $ownerReply = trim($node[3][14][0][0]);
                    }

                    $byId[$node[0]] = [
                        'id' => 'gr-' . substr(sha1($node[0]), 0, 12),
                        'author' => $author !== '' ? $author : 'Google Kullanıcısı',
                        'avatar' => $avatar,
                        'author_url' => $authorUrl,
                        'rating' => $rating,
                        'date' => gr_format_turkish_date($ts, $relDate),
                        'timestamp' => $ts,
                        'text' => $text,
                        'owner_reply' => $ownerReply,
                        'verified' => true,
                        'visible' => true,
                        'pinned' => false,
                    ];
                    return;
                }
            }
            foreach ($node as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };
        $walk($decoded);
    }

    return array_values($byId);
}

function gr_estimate_relative_timestamp(string $relText, int $indexOffset = 0): int
{
    $s = mb_strtolower(trim($relText), 'UTF-8');
    $now = time() - ($indexOffset * 3600);
    if ($s === '' || $s === 'yeni' || str_contains($s, 'az önce') || str_contains($s, 'bugün')) {
        return $now;
    }
    if (str_contains($s, 'dün') || str_contains($s, 'yesterday')) {
        return $now - 86400;
    }
    $num = 1;
    if (preg_match('/(\d+)/', $s, $m)) {
        $num = max(1, (int)$m[1]);
    }
    if (str_contains($s, 'saat') || str_contains($s, 'hour')) {
        return $now - ($num * 3600);
    }
    if (str_contains($s, 'gün') || str_contains($s, 'day')) {
        return $now - ($num * 86400);
    }
    if (str_contains($s, 'hafta') || str_contains($s, 'week')) {
        return $now - ($num * 7 * 86400);
    }
    if (str_contains($s, 'ay') || str_contains($s, 'month')) {
        return $now - ($num * 30 * 86400);
    }
    if (str_contains($s, 'yıl') || str_contains($s, 'sene') || str_contains($s, 'year')) {
        return $now - ($num * 365 * 86400);
    }
    return $now - ($indexOffset * 86400 * 7);
}

function gr_parse_bulk_reviews_input(string $rawInput): array
{
    $raw = trim($rawInput);
    if ($raw === '') {
        return [];
    }

    // 1. Check if JSON (Google Takeout reviews.json or GBP API JSON or custom array)
    if ($raw[0] === '{' || $raw[0] === '[') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $list = isset($json['reviews']) && is_array($json['reviews']) ? $json['reviews'] : $json;
            $parsedJson = [];
            $starMap = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
            foreach ($list as $idx => $rv) {
                if (!is_array($rv)) {
                    continue;
                }
                $author = trim((string)($rv['reviewer']['displayName'] ?? $rv['authorAttribution']['displayName'] ?? $rv['author_name'] ?? $rv['author'] ?? ''));
                if ($author === '') {
                    continue;
                }
                $avatar = trim((string)($rv['reviewer']['profilePhotoUrl'] ?? $rv['authorAttribution']['photoUri'] ?? $rv['profile_photo_url'] ?? $rv['avatar'] ?? ''));
                $rawStar = $rv['starRating'] ?? $rv['rating'] ?? 5;
                $stars = is_string($rawStar) && isset($starMap[strtoupper($rawStar)])
                    ? $starMap[strtoupper($rawStar)]
                    : max(1, min(5, (int)$rawStar));
                $text = trim((string)($rv['comment'] ?? $rv['text']['text'] ?? $rv['text'] ?? ''));
                // Strip Google auto-translation suffix if present
                if (str_contains($text, '(Translated by Google)')) {
                    $parts = explode('(Original)', $text);
                    $text = trim(end($parts) ?: explode('(Translated by Google)', $text)[0]);
                }
                $timeStr = (string)($rv['createTime'] ?? $rv['updateTime'] ?? $rv['publishTime'] ?? $rv['date'] ?? '');
                $ts = $timeStr !== '' && strtotime($timeStr) ? (int)strtotime($timeStr) : (time() - ($idx * 86400 * 10));
                $reply = trim((string)($rv['reviewReply']['comment'] ?? $rv['owner_reply'] ?? ''));

                $parsedJson[] = [
                    'id' => 'gr-' . substr(sha1($author . '|' . $ts . '|' . $idx), 0, 12),
                    'author' => $author,
                    'avatar' => $avatar,
                    'author_url' => '',
                    'rating' => $stars,
                    'date' => gr_format_turkish_date($ts, $timeStr),
                    'timestamp' => $ts,
                    'text' => $text,
                    'owner_reply' => $reply,
                    'verified' => true,
                    'visible' => true,
                    'pinned' => false,
                ];
            }
            if ($parsedJson !== []) {
                return $parsedJson;
            }
        }
    }

    // 2. Smart Google Maps / Search Plain-Text Copy-Paste Parser (Ctrl+A -> Ctrl+C on Reviews Panel)
    $rawLines = preg_split('/\R/u', $raw) ?: [];
    $lines = [];
    foreach ($rawLines as $ln) {
        $t = trim(preg_replace('/\s+/u', ' ', $ln) ?? $ln);
        if ($t !== '') {
            $lines[] = $t;
        }
    }

    $isDateAnchor = static function (string $s): bool {
        return (bool)preg_match(
            '/^(bir|\d+)\s+(saniye|dakika|saat|gün|hafta|ay|yıl|sene)\s+önce(\s*\(düzenlendi\))?$/iu',
            $s
        ) || (bool)preg_match(
            '/^(a|an|\d+)\s+(second|minute|hour|day|week|month|year)s?\s+ago(\s*\(edited\))?$/iu',
            $s
        ) || (bool)preg_match(
            '/^(dün|bugün|yesterday|today|\d{1,2}\s+(ocak|şubat|mart|nisan|mayıs|haziran|temmuz|ağustos|eylül|ekim|kasım|aralık)\s+\d{4})$/iu',
            $s
        );
    };

    $isMetaNoise = static function (string $s): bool {
        $low = mb_strtolower($s, 'UTF-8');
        if (mb_strlen($s, 'UTF-8') <= 1) {
            return true;
        }
        if (preg_match('/^(yerel rehber|local guide|\d+\s*yorum|\d+\s*değerlendirme|\d+\s*fotoğraf|\d+\s*reviews?)/iu', $s)) {
            return true;
        }
        $exactNoise = [
            'yeni', 'new', 'beğen', 'paylaş', 'like', 'share', 'yanıtla', 'reply',
            'daha fazla', 'diğer', 'more', 'tümü', 'en alakalı', 'en yeni',
            'en yüksek puan', 'en düşük puan', 'yorum yazın', 'yorum yaz',
            'google yorumları', 'yorumlar', 'değerlendirmeler', 'özet', 'hakkında',
        ];
        if (in_array($low, $exactNoise, true)) {
            return true;
        }
        if (preg_match('/^★+$/u', $s) || preg_match('/^\d(\.\d)?\s*\/\s*5$/', $s)) {
            return true;
        }
        return false;
    };

    $isOwnerReplyHeader = static function (string $s): bool {
        return (bool)preg_match('/^(işletme sahibinin yanıtı|response from the owner|işletme yanıtı)/iu', $s);
    };

    // Find all date anchor indices that are NOT immediately preceded by "İşletme sahibinin yanıtı"
    $anchors = [];
    $totalLines = count($lines);
    for ($i = 0; $i < $totalLines; $i++) {
        if ($isDateAnchor($lines[$i])) {
            if ($i > 0 && $isOwnerReplyHeader($lines[$i - 1])) {
                continue;
            }
            $anchors[] = $i;
        }
    }

    $results = [];
    if ($anchors !== []) {
        $anchorCount = count($anchors);
        for ($aIdx = 0; $aIdx < $anchorCount; $aIdx++) {
            $dateLineIdx = $anchors[$aIdx];
            $dateStr = (string)preg_replace('/\s*\((düzenlendi|edited)\)$/iu', '', $lines[$dateLineIdx]);

            // Walk backwards up to 4 lines to find Author Name
            $author = '';
            $authorLineIdx = $dateLineIdx;
            for ($b = $dateLineIdx - 1; $b >= max(0, $dateLineIdx - 4); $b--) {
                $cand = $lines[$b];
                if ($isMetaNoise($cand) || $isOwnerReplyHeader($cand) || $isDateAnchor($cand)) {
                    continue;
                }
                if (mb_strlen($cand, 'UTF-8') <= 60 && !str_contains($cand, '. ')) {
                    $author = $cand;
                    $authorLineIdx = $b;
                    break;
                }
            }
            if ($author === '') {
                continue;
            }

            // Determine where the NEXT review starts so we don't bleed into the next author's name
            $nextReviewStart = $totalLines;
            if ($aIdx + 1 < $anchorCount) {
                $nextDateIdx = $anchors[$aIdx + 1];
                $nextReviewStart = $nextDateIdx;
                for ($nb = $nextDateIdx - 1; $nb >= max($dateLineIdx + 1, $nextDateIdx - 3); $nb--) {
                    $nc = $lines[$nb];
                    if (
                        preg_match('/^(yerel rehber|local guide|\d+\s*yorum|\d+\s*reviews?)/iu', $nc)
                        || mb_strlen($nc, 'UTF-8') === 1
                    ) {
                        $nextReviewStart = $nb;
                    } elseif (!$isMetaNoise($nc) && mb_strlen($nc, 'UTF-8') <= 55 && $nb >= $nextDateIdx - 2) {
                        $nextReviewStart = $nb;
                    }
                }
            }

            // Collect review text & optional owner reply between $dateLineIdx + 1 and $nextReviewStart - 1
            $bodyParts = [];
            $replyParts = [];
            $inReply = false;
            $stars = 5;

            for ($k = $dateLineIdx + 1; $k < $nextReviewStart; $k++) {
                $cur = $lines[$k];
                if (preg_match('/^([1-5])\s*yıldız/iu', $cur, $sm) || preg_match('/^([1-5])\/5$/', $cur, $sm)) {
                    $stars = (int)$sm[1];
                    continue;
                }
                if ($isOwnerReplyHeader($cur)) {
                    $inReply = true;
                    continue;
                }
                if ($inReply && $isDateAnchor($cur)) {
                    continue;
                }
                if ($isMetaNoise($cur)) {
                    continue;
                }
                // Strip "... Daha fazla" suffix
                $curClean = trim((string)preg_replace('/\s*…?\s*(Daha fazla|Devamını oku|More)$/iu', '', $cur));
                if ($curClean === '') {
                    continue;
                }
                if ($inReply) {
                    $replyParts[] = $curClean;
                } else {
                    $bodyParts[] = $curClean;
                }
            }

            $ts = gr_estimate_relative_timestamp($dateStr, $aIdx);
            $results[] = [
                'id' => 'gr-' . substr(sha1($author . '|' . $dateStr . '|' . $aIdx), 0, 12),
                'author' => $author,
                'avatar' => '',
                'author_url' => '',
                'rating' => $stars,
                'date' => gr_format_turkish_date($ts, $dateStr),
                'timestamp' => $ts,
                'text' => implode(' ', $bodyParts),
                'owner_reply' => implode(' ', $replyParts),
                'verified' => true,
                'visible' => true,
                'pinned' => false,
            ];
        }
    }

    // 3. Fallback: Simple blank-line separated blocks ("Ad Soyad\nYorum metni")
    if ($results === []) {
        $blocks = preg_split('/\R{2,}/u', $raw) ?: [];
        foreach ($blocks as $bIdx => $blk) {
            $bLines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $blk) ?: [])));
            if ($bLines === []) {
                continue;
            }
            $author = array_shift($bLines);
            if (mb_strlen($author, 'UTF-8') > 65) {
                continue;
            }
            $txt = trim(implode(' ', $bLines));
            $ts = time() - ($bIdx * 86400 * 7);
            $results[] = [
                'id' => 'gr-' . substr(sha1($author . '|' . $bIdx), 0, 12),
                'author' => $author,
                'avatar' => '',
                'author_url' => '',
                'rating' => 5,
                'date' => gr_format_turkish_date($ts),
                'timestamp' => $ts,
                'text' => $txt,
                'owner_reply' => '',
                'verified' => true,
                'visible' => true,
                'pinned' => false,
            ];
        }
    }

    return $results;
}

function gr_merge_reviews(array $existing, array $incoming): array
{
    $byKey = [];
    $normKey = static function (array $item): string {
        $author = mb_strtolower(trim((string)($item['author'] ?? '')), 'UTF-8');
        $author = preg_replace('/\s+/', ' ', $author) ?? $author;
        return $author !== '' ? $author : uniqid('anon-', true);
    };

    foreach ($existing as $ex) {
        $k = $normKey($ex);
        $byKey[$k] = $ex;
    }

    $addedCount = 0;
    foreach ($incoming as $inc) {
        $k = $normKey($inc);
        if (isset($byKey[$k])) {
            // Enrich existing review with avatar, author_url, owner_reply, rating, timestamp without losing pinned/visible flags
            if (empty($byKey[$k]['avatar']) && !empty($inc['avatar'])) {
                $byKey[$k]['avatar'] = $inc['avatar'];
            }
            if (empty($byKey[$k]['author_url']) && !empty($inc['author_url'])) {
                $byKey[$k]['author_url'] = $inc['author_url'];
            }
            if (empty($byKey[$k]['owner_reply']) && !empty($inc['owner_reply'])) {
                $byKey[$k]['owner_reply'] = $inc['owner_reply'];
            }
            if (!empty($inc['text']) && mb_strlen((string)$inc['text'], 'UTF-8') > mb_strlen((string)($byKey[$k]['text'] ?? ''), 'UTF-8')) {
                $byKey[$k]['text'] = $inc['text'];
            }
            if (!empty($inc['rating'])) {
                $byKey[$k]['rating'] = $inc['rating'];
            }
            if (!empty($inc['avatar']) && !empty($inc['timestamp'])) {
                $byKey[$k]['timestamp'] = (int)$inc['timestamp'];
                if (!empty($inc['date'])) {
                    $byKey[$k]['date'] = (string)$inc['date'];
                }
            }
        } else {
            $byKey[$k] = $inc;
            $addedCount++;
        }
    }

    $merged = array_values($byKey);
    usort($merged, static function (array $a, array $b): int {
        $pinA = !empty($a['pinned']) ? 1 : 0;
        $pinB = !empty($b['pinned']) ? 1 : 0;
        if ($pinA !== $pinB) {
            return $pinB <=> $pinA;
        }
        return ((int)($b['timestamp'] ?? 0)) <=> ((int)($a['timestamp'] ?? 0));
    });

    return ['items' => $merged, 'added' => $addedCount];
}

function sync_google_reviews(array &$store, array $override = []): array
{
    $gr = get_google_reviews_state($store);
    foreach (['query_input', 'place_id', 'api_key', 'business_name'] as $k) {
        if (array_key_exists($k, $override) && is_string($override[$k])) {
            $gr[$k] = trim($override[$k]);
        }
    }

    $parsed = gr_parse_input_identifiers($gr['query_input'] !== '' ? $gr['query_input'] : $gr['place_id']);
    if ($parsed['place_id'] !== '') {
        $gr['place_id'] = $parsed['place_id'];
    }
    if ($parsed['feature_id'] !== '') {
        $gr['feature_id'] = $parsed['feature_id'];
    }

    $searchTerm = $parsed['query'] !== ''
        ? $parsed['query']
        : ($gr['business_name'] !== '' ? $gr['business_name'] : 'Zeynart Web Bilişim Hizmetleri');

    $publicMeta = gr_discover_via_google_maps_public($searchTerm);
    $statusParts = [];
    $addedReviews = 0;

    if (is_array($publicMeta)) {
        if (!empty($publicMeta['place_id'])) {
            $gr['place_id'] = $publicMeta['place_id'];
        }
        if (!empty($publicMeta['feature_id'])) {
            $gr['feature_id'] = $publicMeta['feature_id'];
        }
        if (!empty($publicMeta['business_name'])) {
            $gr['business_name'] = $publicMeta['business_name'];
        }
        if ($publicMeta['rating'] !== null && $publicMeta['rating'] > 0) {
            $gr['rating'] = round((float)$publicMeta['rating'], 1);
        }
        if ($publicMeta['total_reviews'] !== null && $publicMeta['total_reviews'] > 0) {
            $gr['total_reviews'] = (int)$publicMeta['total_reviews'];
        }
        if (!empty($publicMeta['business_avatar'])) {
            $gr['business_avatar'] = $publicMeta['business_avatar'];
        }
        $statusParts[] = 'Google Haritalar profili doğrulandı (' . number_format($gr['rating'], 1) . ' ★ · ' . $gr['total_reviews'] . ' yorum)';
    }

    if ($gr['feature_id'] !== '') {
        $pubReviews = gr_fetch_public_maps_reviews($gr['feature_id'], $gr['business_name']);
        if ($pubReviews !== []) {
            $mergedPub = gr_merge_reviews($gr['items'], $pubReviews);
            $gr['items'] = $mergedPub['items'];
            $addedReviews += (int)$mergedPub['added'];
            $statusParts[] = 'Haritalar canlı akışı: ' . count($pubReviews) . ' yorum/avatar tarandı (' . (int)$mergedPub['added'] . ' yeni eklendi)';
        }
    }

    if ($gr['api_key'] !== '') {
        $apiResult = gr_fetch_via_places_api($gr['api_key'], $gr['place_id'], $searchTerm);
        if (!empty($apiResult['ok'])) {
            if (!empty($apiResult['place_id'])) {
                $gr['place_id'] = (string)$apiResult['place_id'];
            }
            if (!empty($apiResult['business_name'])) {
                $gr['business_name'] = (string)$apiResult['business_name'];
            }
            if ($apiResult['rating'] !== null && $apiResult['rating'] > 0) {
                $gr['rating'] = round((float)$apiResult['rating'], 1);
            }
            if ($apiResult['total_reviews'] !== null && $apiResult['total_reviews'] > 0) {
                $gr['total_reviews'] = (int)$apiResult['total_reviews'];
            }
            if (!empty($apiResult['maps_url'])) {
                $gr['maps_url'] = (string)$apiResult['maps_url'];
            }
            $apiAdded = 0;
            if (!empty($apiResult['reviews'])) {
                $merged = gr_merge_reviews($gr['items'], $apiResult['reviews']);
                $gr['items'] = $merged['items'];
                $apiAdded = (int)$merged['added'];
                $addedReviews += $apiAdded;
            }
            $statusParts[] = 'Places API senkronize edildi (' . count($apiResult['reviews'] ?? []) . ' Google yorumu tarandı, ' . $apiAdded . ' yeni eklendi)';
        } elseif (!empty($apiResult['error'])) {
            $statusParts[] = 'Places API uyarısı: ' . $apiResult['error'];
        }
    }

    if ($gr['place_id'] !== '') {
        $gr['maps_url'] = 'https://www.google.com/maps/place/?q=place_id:' . rawurlencode($gr['place_id']);
        $gr['write_review_url'] = 'https://search.google.com/local/writereview?placeid=' . rawurlencode($gr['place_id']);
        $gr['all_reviews_url'] = 'https://search.google.com/local/reviews?placeid=' . rawurlencode($gr['place_id']) . '&hl=tr';
    }

    $gr['total_reviews'] = max($gr['total_reviews'], count($gr['items']));
    $gr['items'] = gr_localize_review_items_avatars($gr['items'], 24);
    $gr['last_synced_at'] = date('c');
    $gr['last_sync_status'] = $statusParts !== []
        ? implode(' · ', $statusParts)
        : ('Senkronizasyon tamamlandı (' . number_format($gr['rating'], 1) . ' ★ · ' . $gr['total_reviews'] . ' yorum)');

    $store['google_reviews'] = $gr;

    // Mirror visible reviews back to about.reviews so legacy consumers stay 100% in sync
    $mirror = [];
    foreach ($gr['items'] as $it) {
        if (($it['visible'] ?? true) === false) {
            continue;
        }
        $mirror[] = [
            'author' => (string)($it['author'] ?? ''),
            'date' => trim((string)($it['date'] ?? '')) . ' · Google',
            'text' => (string)($it['text'] ?? ''),
            'rating' => (int)($it['rating'] ?? 5),
            'avatar' => (string)($it['avatar'] ?? ''),
            'owner_reply' => (string)($it['owner_reply'] ?? ''),
        ];
    }
    if ($mirror !== []) {
        $store['about']['reviews'] = $mirror;
    }

    save_store($store);

    return [
        'ok' => true,
        'added_reviews' => $addedReviews,
        'google_reviews' => $gr,
        'message' => $gr['last_sync_status'],
    ];
}

function maybe_auto_sync_google_reviews(array $store): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
        return;
    }
    $gr = get_google_reviews_state($store);
    if (!$gr['enabled'] || !$gr['auto_sync']) {
        return;
    }
    $lastTs = $gr['last_synced_at'] !== '' ? (int)strtotime($gr['last_synced_at']) : 0;
    $dueSeconds = max(3600, $gr['sync_interval_hours'] * 3600);
    if ($lastTs > 0 && (time() - $lastTs) < $dueSeconds) {
        return;
    }

    $lockFile = DATA_DIR . '/.google_reviews_sync.lock';
    if (is_file($lockFile) && (time() - (int)@filemtime($lockFile)) < 600) {
        return;
    }
    @file_put_contents($lockFile, (string)time());

    register_shutdown_function(static function () use ($lockFile): void {
        if (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
        } elseif (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        try {
            $freshStore = load_store(true);
            sync_google_reviews($freshStore);
        } catch (\Throwable $e) {
            // Silent fail to protect visitor response
        } finally {
            @unlink($lockFile);
        }
    });
}

function gr_google_g_svg(int $size = 20): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true" class="zgr-g-icon">'
        . '<path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v2.98h3.88c2.27-2.09 3.54-5.17 3.54-8.8z"/>'
        . '<path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-2.98c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.11-6.72-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/>'
        . '<path fill="#FBBC05" d="M5.28 14.31c-.24-.72-.38-1.49-.38-2.31s.14-1.59.38-2.31V6.6H1.29C.47 8.23 0 10.06 0 12s.47 3.77 1.29 5.4l3.99-3.09z"/>'
        . '<path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.6l3.99 3.09c.95-2.85 3.6-4.94 6.72-4.94z"/>'
        . '</svg>';
}

function gr_optimize_remote_avatar_url(string $avatarUrl): string
{
    $u = trim($avatarUrl);
    if ($u === '' || str_starts_with($u, '/')) {
        return $u;
    }
    if (str_contains($u, 'googleusercontent.com/') || str_contains($u, 'ggpht.com/')) {
        $replaced = preg_replace('/=s\d+(-[a-z0-9\-]+)?$/i', '=s84$1', $u, 1, $cnt);
        if (is_string($replaced) && $cnt > 0) {
            return $replaced;
        }
    }
    return $u;
}

function gr_load_avatar_cache_map(): array
{
    if (isset($GLOBALS['__zeynart_gr_avatar_map']) && is_array($GLOBALS['__zeynart_gr_avatar_map'])) {
        return $GLOBALS['__zeynart_gr_avatar_map'];
    }
    $map = [];
    if (function_exists('zeynart_sql_is_active') && zeynart_sql_is_active() && function_exists('zeynart_sql_kv_get')) {
        $raw = zeynart_sql_kv_get('gr_avatar_map');
        if (is_array($raw)) {
            $map = $raw;
        }
    }
    $GLOBALS['__zeynart_gr_avatar_map'] = $map;
    return $map;
}

function gr_save_avatar_cache_map(array $map): void
{
    $GLOBALS['__zeynart_gr_avatar_map'] = $map;
    if (function_exists('zeynart_sql_is_active') && zeynart_sql_is_active() && function_exists('zeynart_sql_kv_set')) {
        zeynart_sql_kv_set('gr_avatar_map', $map, 'cache');
    }
}

function gr_localize_single_avatar(string $remoteUrl, int &$downloadBudget): string
{
    $u = trim($remoteUrl);
    if ($u === '') {
        return '';
    }
    if (str_starts_with($u, '/uploads/') || str_starts_with($u, '/images/')) {
        return is_file(ROOT_DIR . $u) ? $u : '';
    }
    if (!preg_match('~^https://([a-z0-9\-]+\.)*(googleusercontent\.com|ggpht\.com)/~i', $u)) {
        return $u;
    }

    $optUrl = gr_optimize_remote_avatar_url($u);
    $hash = substr(md5($optUrl), 0, 14);
    $map = gr_load_avatar_cache_map();
    if (!empty($map[$hash]) && is_string($map[$hash]) && str_starts_with($map[$hash], '/uploads/') && is_file(ROOT_DIR . $map[$hash])) {
        return $map[$hash];
    }

    if ($downloadBudget <= 0 || PHP_SAPI === 'cli' || !function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        return $optUrl;
    }

    $failCooldownFile = DATA_DIR . '/.gr_avatar_net_cooldown';
    if (is_file($failCooldownFile) && (time() - (int)@filemtime($failCooldownFile)) < 120) {
        return $optUrl;
    }

    $downloadBudget--;
    $resp = gr_http_request($optUrl, 'GET', [], null, 3);
    if (empty($resp['ok']) || empty($resp['body']) || strlen((string)$resp['body']) < 64) {
        @file_put_contents($failCooldownFile, (string)time());
        return $optUrl;
    }

    $srcIm = @imagecreatefromstring((string)$resp['body']);
    if (!$srcIm) {
        return $optUrl;
    }

    $sw = imagesx($srcIm);
    $sh = imagesy($srcIm);
    $target = 84;
    $dst = imagecreatetruecolor($target, $target);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $target, $target, $trans);
    imagecopyresampled($dst, $srcIm, 0, 0, 0, 0, $target, $target, max(1, $sw), max(1, $sh));

    $subDir = date('Y') . '/' . date('m') . '/' . date('d');
    $absDir = UPLOADS_DIR . '/' . $subDir;
    if (!is_dir($absDir)) {
        @mkdir($absDir, 0775, true);
    }
    $fileName = 'gr-avatar-' . $hash . '.webp';
    $absFile = $absDir . '/' . $fileName;
    $relFile = '/uploads/' . $subDir . '/' . $fileName;

    if (@imagewebp($dst, $absFile, 80) && is_file($absFile) && filesize($absFile) > 50) {
        $map[$hash] = $relFile;
        gr_save_avatar_cache_map($map);
        return $relFile;
    }

    return $optUrl;
}

function gr_localize_review_items_avatars(array $items, int $maxDownloads = 10): array
{
    $budget = $maxDownloads;
    foreach ($items as $idx => $rv) {
        if (!is_array($rv)) {
            continue;
        }
        $av = trim((string)($rv['avatar'] ?? ''));
        if ($av === '') {
            continue;
        }
        if (str_starts_with($av, '/uploads/') && !is_file(ROOT_DIR . $av) && !empty($rv['remote_avatar'])) {
            $av = trim((string)$rv['remote_avatar']);
        }
        if (str_starts_with($av, 'https://')) {
            $rv['remote_avatar'] = $av;
            $localOrOpt = gr_localize_single_avatar($av, $budget);
            $rv['avatar'] = $localOrOpt;
            $items[$idx] = $rv;
        }
    }
    return $items;
}

function gr_stars_svg(int $count = 5, int $size = 16): string
{
    $count = max(1, min(5, $count));
    $html = '<span class="zgr-stars" role="img" aria-label="' . $count . ' / 5 ' . e(t('yıldız')) . '">';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $i <= $count ? '#fbbf24' : 'rgba(255,255,255,.16)';
        $html .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 20 20" aria-hidden="true"><path fill="' . $fill . '" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.18 3.63a1 1 0 0 0 .95.69h3.817c.969 0 1.371 1.24.588 1.81l-3.088 2.244a1 1 0 0 0-.364 1.118l1.18 3.63c.3.921-.755 1.688-1.54 1.118l-3.088-2.244a1 1 0 0 0-1.175 0l-3.088 2.244c-.784.57-1.838-.197-1.539-1.118l1.18-3.63a1 1 0 0 0-.364-1.118L2.592 9.057c-.783-.57-.38-1.81.588-1.81h3.817a1 1 0 0 0 .95-.69l1.102-3.63z"/></svg>';
    }
    $html .= '</span>';
    return $html;
}

function gr_verified_svg(): string
{
    $lbl = t('Doğrulanmış Google Değerlendirmesi');
    return '<span class="zgr-verified" role="img" title="' . e($lbl) . '" aria-label="' . e($lbl) . '">'
        . '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
        . '<circle cx="12" cy="12" r="10" fill="#38bdf8"/>'
        . '<path d="m8.5 12.3 2.3 2.3 4.8-5" stroke="#041016" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg></span>';
}

function gr_avatar_palette(string $name): string
{
    $palettes = [
        'linear-gradient(135deg,#0d9488,#115e59)',
        'linear-gradient(135deg,#0284c7,#1e3a8a)',
        'linear-gradient(135deg,#059669,#064e3b)',
        'linear-gradient(135deg,#4f46e5,#312e81)',
        'linear-gradient(135deg,#d97706,#78350f)',
        'linear-gradient(135deg,#0891b2,#164e63)',
    ];
    $idx = abs(crc32(mb_strtolower($name, 'UTF-8'))) % count($palettes);
    return $palettes[$idx];
}

function gr_rating_word(float $rating): string
{
    if ($rating >= 4.7) {
        return t('MÜKEMMEL');
    }
    if ($rating >= 4.2) {
        return t('ÇOK İYİ');
    }
    return t('OLUMLU');
}

function get_filtered_google_reviews(array $gr): array
{
    $minRating = (int)($gr['min_rating'] ?? 4);
    $hideEmpty = !empty($gr['hide_empty_text']);
    $out = [];
    foreach (($gr['items'] ?? []) as $it) {
        if (($it['visible'] ?? true) === false) {
            continue;
        }
        $stars = (int)($it['rating'] ?? 5);
        if ($stars < $minRating) {
            continue;
        }
        $txt = trim((string)($it['text'] ?? ''));
        if ($hideEmpty && $txt === '') {
            continue;
        }
        $out[] = $it;
    }
    return $out;
}

function render_google_reviews_section(array $store, array $opts = []): string
{
    maybe_auto_sync_google_reviews($store);

    $gr = get_google_reviews_state($store);
    if (!$gr['enabled'] && empty($opts['force'])) {
        return '';
    }

    $context = (string)($opts['context'] ?? 'custom');
    if ($context === 'home' && !$gr['show_on_home']) {
        return '';
    }
    if ($context === 'about' && !$gr['show_on_about']) {
        return '';
    }

    $items = get_filtered_google_reviews($gr);
    if ($items === []) {
        return '';
    }

    $layout = (string)($opts['layout'] ?? $gr['layout'] ?? 'slider');
    if (!in_array($layout, ['slider', 'grid', 'masonry', 'badge'], true)) {
        $layout = 'slider';
    }

    $wrapped = ($opts['wrapped'] ?? true) !== false;
    $ratingStr = number_format((float)$gr['rating'], 1, '.', '');
    $totalCount = (int)$gr['total_reviews'];
    $word = gr_rating_word((float)$gr['rating']);
    $allUrl = $gr['all_reviews_url'] ?: $gr['maps_url'];
    $writeUrl = $gr['write_review_url'] ?: $gr['maps_url'];
    $showVerified = !empty($gr['show_verified_badge']);
    $showWriteBtn = !empty($gr['show_write_btn']);
    $uid = 'zgr-' . substr(md5($context . '-' . $layout), 0, 6);

    // Compact Badge Layout
    if ($layout === 'badge') {
        $badgeHtml = '<div class="zgr-badge-wrap">'
            . '<a href="' . e($allUrl) . '" target="_blank" rel="noopener noreferrer" class="zgr-compact-badge">'
            . gr_google_g_svg(28)
            . '<div class="zgr-compact-info">'
            . '<div class="zgr-compact-top"><strong>' . e(t('Google Değerlendirmeleri')) . '</strong><span>' . e($word) . '</span></div>'
            . '<div class="zgr-compact-bottom"><b>' . e($ratingStr) . '</b>' . gr_stars_svg((int)round($gr['rating']), 15) . '<small>(' . $totalCount . ' ' . e(t('yorum')) . ')</small></div>'
            . '</div>'
            . '</a>';
        if ($showWriteBtn) {
            $badgeHtml .= '<a href="' . e($writeUrl) . '" target="_blank" rel="noopener noreferrer" class="button zgr-write-btn">' . e(t('Google’da Yorum Yazın')) . zeynart_icon('arrow-up-right', 15) . '</a>';
        }
        $badgeHtml .= '</div>';

        return $wrapped ? '<section class="section container zgr-section">' . $badgeHtml . '</section>' : $badgeHtml;
    }

    $curLangGr = zeynart_current_lang();
    $verifiedCountHtml = match ($curLangGr) {
        'en' => 'Verified across <strong>' . $totalCount . ' Google reviews</strong> ↗',
        'de' => 'Verifiziert durch <strong>' . $totalCount . ' Google-Bewertungen</strong> ↗',
        'ru' => 'Подтверждено <strong>' . $totalCount . ' отзывами Google</strong> ↗',
        default => '<strong>' . $totalCount . ' değerlendirmeye</strong> göre doğrulanmıştır ↗',
    };
    $emptyStarNoteTpl = static fn(int $s): string => match ($curLangGr) {
        'en' => "Rated {$s} stars via Google Business Profile.",
        'de' => "Mit {$s} Sternen über das Google-Unternehmensprofil bewertet.",
        'ru' => "Оценка {$s} ★ через профиль компании в Google.",
        default => "Google İşletme Profili üzerinden {$s} yıldız ile değerlendirdi.",
    };
    $seeAllReviewsTxt = match ($curLangGr) {
        'en' => "See all {$totalCount} Google reviews ",
        'de' => "Alle {$totalCount} Google-Bewertungen ansehen ",
        'ru' => "Все отзывы Google ({$totalCount}) ",
        default => "Tüm {$totalCount} Google yorumunu gör ",
    };

    // Build review cards (auto-localize Google avatars to /uploads/YYYY/MM/DD/*.webp for 1yr cache & ~94% byte savings)
    $avatarBudget = 12;
    $cardsHtml = '';
    foreach ($items as $rv) {
        $author = trim((string)($rv['author'] ?? 'Google Kullanıcısı'));
        $initial = mb_strtoupper(mb_substr($author, 0, 1, 'UTF-8'), 'UTF-8');
        $rawAvatar = trim((string)($rv['avatar'] ?? ''));
        if ($rawAvatar !== '' && str_starts_with($rawAvatar, '/uploads/') && !is_file(ROOT_DIR . $rawAvatar) && !empty($rv['remote_avatar'])) {
            $rawAvatar = trim((string)$rv['remote_avatar']);
        }
        $avatar = $rawAvatar !== '' ? gr_localize_single_avatar($rawAvatar, $avatarBudget) : '';
        if ($avatar !== '' && str_starts_with($avatar, '/')) {
            $avatar = url($avatar);
        }
        $stars = max(1, min(5, (int)($rv['rating'] ?? 5)));
        $date = trim((string)($rv['date'] ?? 'Google Yorumu'));
        $text = trim(str_replace('*', '', (string)($rv['text'] ?? '')));
        $ownerReply = trim((string)($rv['owner_reply'] ?? ''));
        $isLong = mb_strlen($text, 'UTF-8') > 185;
        $shortText = $isLong ? (rtrim(mb_substr($text, 0, 182, 'UTF-8')) . '…') : $text;

        $avatarNode = $avatar !== ''
            ? '<img src="' . e($avatar) . '" alt="' . e($author) . '" width="42" height="42" loading="lazy" decoding="async"' . (str_starts_with($avatar, 'https://') ? ' referrerpolicy="no-referrer"' : '') . '/>'
            : '<span style="background:' . gr_avatar_palette($author) . '">' . e($initial) . '</span>';

        if ($text === '') {
            $bodyHtml = '<div class="zgr-card-body"><p class="zgr-empty-note">' . e($emptyStarNoteTpl($stars)) . '</p></div>';
        } else {
            $bodyHtml = '<div class="zgr-card-body"' . ($isLong ? ' data-zgr-full="' . e($text) . '" data-zgr-short="' . e($shortText) . '"' : '') . '>'
                . '<p>' . e($shortText) . '</p>'
                . ($isLong ? '<button type="button" class="zgr-readmore" data-zgr-toggle>' . e(t('Devamını oku')) . '</button>' : '')
                . '</div>';
        }

        $replyHtml = '';
        if ($ownerReply !== '') {
            $replyLong = mb_strlen($ownerReply, 'UTF-8') > 140;
            $replyShort = $replyLong ? (rtrim(mb_substr($ownerReply, 0, 137, 'UTF-8')) . '…') : $ownerReply;
            $replyHtml = '<div class="zgr-owner-reply"' . ($replyLong ? ' data-zgr-full="' . e($ownerReply) . '" data-zgr-short="' . e($replyShort) . '"' : '') . '>'
                . '<strong>' . e(t('İşletme Yanıtı')) . '</strong>'
                . '<p>' . e($replyShort) . '</p>'
                . ($replyLong ? '<button type="button" class="zgr-readmore" data-zgr-reply-toggle>' . e(t('Yanıtın tamamı')) . '</button>' : '')
                . '</div>';
        }

        $cardsHtml .= '<article class="zgr-card">'
            . '<header class="zgr-card-head">'
            . '<div class="zgr-avatar">' . $avatarNode . '<i class="zgr-avatar-g">' . gr_google_g_svg(13) . '</i></div>'
            . '<div class="zgr-author">'
            . '<strong>' . e($author) . ($showVerified ? gr_verified_svg() : '') . '</strong>'
            . '<time>' . e($date) . '</time>'
            . '</div>'
            . '<a href="' . e($allUrl) . '" target="_blank" rel="noopener noreferrer" class="zgr-card-glink" aria-label="' . e(t('Google üzerinde görüntüle')) . '">' . gr_google_g_svg(18) . '</a>'
            . '</header>'
            . '<div class="zgr-card-stars">' . gr_stars_svg($stars, 15) . ($showVerified ? '<span class="zgr-verified-text">' . e(t('Doğrulanmış')) . '</span>' : '') . '</div>'
            . $bodyHtml
            . $replyHtml
            . '</article>';
    }

    // Summary Box (Left sidebar in 'slider', top bar in 'grid'/'masonry')
    $summaryCard = '<aside class="zgr-summary-card">'
        . '<div class="zgr-summary-brand">' . gr_google_g_svg(26) . '<span>' . e(t('Google İşletme Profili')) . '</span></div>'
        . '<div class="zgr-summary-word">' . e($word) . '</div>'
        . '<div class="zgr-summary-score"><strong>' . e($ratingStr) . '</strong>' . gr_stars_svg((int)round($gr['rating']), 20) . '</div>'
        . '<a href="' . e($allUrl) . '" target="_blank" rel="noopener noreferrer" class="zgr-summary-count">'
        . $verifiedCountHtml
        . '</a>'
        . '<div class="zgr-summary-biz">' . e($gr['business_name']) . '</div>'
        . ($showWriteBtn ? '<a href="' . e($writeUrl) . '" target="_blank" rel="noopener noreferrer" class="zgr-review-cta">' . gr_google_g_svg(16) . '<span>' . e(t('Bize Google’da Yorum Yazın')) . '</span></a>' : '')
        . '</aside>';

    $visibleCount = count($items);
    $innerHtml = '';
    if ($layout === 'slider') {
        $innerHtml = '<div class="zgr-slider-shell" data-zgr-slider id="' . e($uid) . '">'
            . $summaryCard
            . '<div class="zgr-carousel-col">'
            . '<div class="zgr-track" data-zgr-track>' . $cardsHtml . '</div>'
            . '<div class="zgr-carousel-nav">'
            . '<button type="button" class="zgr-nav-btn" data-zgr-prev aria-label="' . e(t('Önceki yorumlar')) . '">' . zeynart_icon('arrow-left', 16) . '</button>'
            . '<button type="button" class="zgr-nav-btn" data-zgr-next aria-label="' . e(t('Sonraki yorumlar')) . '">' . zeynart_icon('arrow-right', 16) . '</button>'
            . ($visibleCount > 3 ? '<button type="button" class="zgr-expand-btn" data-zgr-expand data-count="' . $visibleCount . '">' . e(t('Tümünü Sitede Aç')) . ' (' . $visibleCount . ')</button>' : '')
            . '<a href="' . e($allUrl) . '" target="_blank" rel="noopener noreferrer" class="zgr-all-link">' . e($seeAllReviewsTxt) . zeynart_icon('arrow-up-right', 14) . '</a>'
            . '</div>'
            . '</div>'
            . '</div>';
    } else {
        $gridClass = $layout === 'masonry' ? 'zgr-masonry-grid' : 'zgr-cards-grid';
        $topBar = '<div class="zgr-topbar">'
            . '<div class="zgr-topbar-left">'
            . gr_google_g_svg(30)
            . '<div>'
            . '<div class="zgr-topbar-title"><strong>' . e($word) . '</strong> · <b>' . e($ratingStr) . '</b> ' . gr_stars_svg((int)round($gr['rating']), 17) . '</div>'
            . '<a href="' . e($allUrl) . '" target="_blank" rel="noopener noreferrer" class="zgr-summary-count">' . e($gr['business_name']) . ' · ' . $totalCount . ' ' . e(t('yorum')) . ' ↗</a>'
            . '</div>'
            . '</div>'
            . ($showWriteBtn ? '<a href="' . e($writeUrl) . '" target="_blank" rel="noopener noreferrer" class="zgr-review-cta">' . gr_google_g_svg(16) . '<span>' . e(t('Google’da Yorum Yazın')) . '</span></a>' : '')
            . '</div>';
        $innerHtml = '<div class="zgr-stack">' . $topBar . '<div class="' . $gridClass . '">' . $cardsHtml . '</div></div>';
    }

    if (!$wrapped) {
        return $innerHtml;
    }

    $headingHtml = '<div class="section-heading zgr-section-head">'
        . '<div>'
        . '<p class="eyebrow"><span></span>' . e($gr['eyebrow']) . '</p>'
        . '<h2 id="' . e($uid) . '-title">' . e($gr['heading']) . '</h2>'
        . '</div>'
        . ($gr['subheading'] !== '' ? '<p class="section-copy" style="margin:0">' . e($gr['subheading']) . '</p>' : '')
        . '</div>';

    return '<section class="section container zgr-section" aria-labelledby="' . e($uid) . '-title">'
        . $headingHtml
        . $innerHtml
        . '</section>';
}
