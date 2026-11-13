<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * ZEYNART ÇOKLU DİL OTOMATİK ÇEVİRİ MOTORU (TR -> EN · DE · RU)
 * - Anahtarsız Yüksek Hızlı Nöral Toplu Çeviri + Çok Katmanlı Yedekli Altyapı
 * - İsteğe Bağlı DeepL API / OpenAI API Desteği (Admin > Ayarlar üzerinden)
 * - Zengin HTML Yapısını (Görseller, Linkler, Sınıflar, Başlık ID'leri) %100 Koruyan DOM Çevirmeni
 * - Çok Dilli SEO Dostu URL (Slug) Üretici (Almanca Umlaut + Rusça Kiril Transliterasyonu)
 */

function zeynart_slugify_multilang(string $text, string $lang = 'en', string $fallback = ''): string
{
    $s = trim($text);
    if ($s === '') {
        return $fallback !== '' ? $fallback : ('post-' . date('YmdHis'));
    }

    // German & Turkish & European diacritics + Russian Cyrillic -> ASCII Latin transliteration
    $translit = [
        // German
        'ä' => 'ae', 'Ä' => 'ae', 'ö' => 'oe', 'Ö' => 'oe',
        'ü' => 'ue', 'Ü' => 'ue', 'ß' => 'ss',
        // Turkish (if any brand/loanword remains)
        'ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'I' => 'i',
        'ğ' => 'g', 'Ğ' => 'g', 'ç' => 'c', 'Ç' => 'c',
        'â' => 'a', 'Â' => 'a', 'î' => 'i', 'Î' => 'i', 'û' => 'u', 'Û' => 'u',
        // French/Spanish/common Latin accents
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'å' => 'a',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ñ' => 'n',
        // Russian Cyrillic (Upper & Lower)
        'А' => 'a', 'а' => 'a', 'Б' => 'b', 'б' => 'b', 'В' => 'v', 'в' => 'v',
        'Г' => 'g', 'г' => 'g', 'Д' => 'd', 'д' => 'd', 'Е' => 'e', 'е' => 'e',
        'Ё' => 'yo', 'ё' => 'yo', 'Ж' => 'zh', 'ж' => 'zh', 'З' => 'z', 'з' => 'z',
        'И' => 'i', 'и' => 'i', 'Й' => 'y', 'й' => 'y', 'К' => 'k', 'к' => 'k',
        'Л' => 'l', 'л' => 'l', 'М' => 'm', 'м' => 'm', 'Н' => 'n', 'н' => 'n',
        'О' => 'o', 'о' => 'o', 'П' => 'p', 'п' => 'p', 'Р' => 'r', 'р' => 'r',
        'С' => 's', 'с' => 's', 'Т' => 't', 'т' => 't', 'У' => 'u', 'у' => 'u',
        'Ф' => 'f', 'ф' => 'f', 'Х' => 'kh', 'х' => 'kh', 'Ц' => 'ts', 'ц' => 'ts',
        'Ч' => 'ch', 'ч' => 'ch', 'Ш' => 'sh', 'ш' => 'sh', 'Щ' => 'shch', 'щ' => 'shch',
        'Ъ' => '', 'ъ' => '', 'Ы' => 'y', 'ы' => 'y', 'Ь' => '', 'ь' => '',
        'Э' => 'e', 'э' => 'e', 'Ю' => 'yu', 'ю' => 'yu', 'Я' => 'ya', 'я' => 'ya',
    ];

    // If Turkish specifically, use standard Turkish 'u'/'o' instead of German 'ue'/'oe'
    if ($lang === 'tr') {
        $translit['ö'] = 'o';
        $translit['Ö'] = 'o';
        $translit['ü'] = 'u';
        $translit['Ü'] = 'u';
    }

    $s = strtr($s, $translit);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    if (strlen($s) > 85) {
        $cut = substr($s, 0, 85);
        $lastDash = strrpos($cut, '-');
        $s = $lastDash !== false && $lastDash > 35 ? substr($cut, 0, $lastDash) : rtrim($cut, '-');
    }

    return $s !== '' ? $s : ($fallback !== '' ? $fallback : ('post-' . date('YmdHis')));
}

function zeynart_http_request(string $url, string $method = 'GET', array $headers = [], ?string $body = null, int $timeout = 12): array
{
    $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
    ];
    $allHeaders = array_merge($defaultHeaders, $headers);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(4, $timeout),
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
        ];
        if (defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body ?? '';
        }
        curl_setopt_array($ch, $opts);
        $respBody = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        return [
            'ok' => is_string($respBody) && $code >= 200 && $code < 300,
            'code' => $code,
            'body' => is_string($respBody) ? $respBody : '',
        ];
    }

    $ctxOpts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $allHeaders),
            'ignore_errors' => true,
            'follow_location' => 1,
            'max_redirects' => 3,
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
    foreach ($http_response_header as $line) {
        if (preg_match('~^HTTP/\S+\s+(\d+)~i', $line, $m)) {
            $code = (int)$m[1];
        }
    }

    return [
        'ok' => is_string($raw) && $code >= 200 && $code < 300,
        'code' => $code,
        'body' => is_string($raw) ? $raw : '',
    ];
}

function zeynart_translate_chunk_deepl(array $chunk, string $targetLang, string $sourceLang, string $apiKey): ?array
{
    $apiKey = trim($apiKey);
    if ($apiKey === '' || $chunk === []) {
        return null;
    }
    $isFree = str_ends_with($apiKey, ':fx');
    $endpoint = $isFree ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';
    $dlTarget = match ($targetLang) {
        'en' => 'EN-US',
        'de' => 'DE',
        'ru' => 'RU',
        default => strtoupper($targetLang),
    };
    $parts = ['source_lang=' . rawurlencode(strtoupper($sourceLang)), 'target_lang=' . rawurlencode($dlTarget)];
    foreach ($chunk as $txt) {
        $parts[] = 'text=' . rawurlencode($txt);
    }
    $res = zeynart_http_request($endpoint, 'POST', [
        'Authorization: DeepL-Auth-Key ' . $apiKey,
        'Content-Type: application/x-www-form-urlencoded',
    ], implode('&', $parts), 15);

    if (!$res['ok']) {
        return null;
    }
    $json = json_decode($res['body'], true);
    if (!is_array($json) || !is_array($json['translations'] ?? null) || count($json['translations']) !== count($chunk)) {
        return null;
    }
    $out = [];
    foreach ($json['translations'] as $trItem) {
        $out[] = (string)($trItem['text'] ?? '');
    }
    return $out;
}

function zeynart_translate_chunk_openai(array $chunk, string $targetLang, string $sourceLang, string $apiKey, string $model = 'gpt-4o-mini'): ?array
{
    $apiKey = trim($apiKey);
    if ($apiKey === '' || $chunk === []) {
        return null;
    }
    $langNames = ['en' => 'English', 'de' => 'German', 'ru' => 'Russian', 'tr' => 'Turkish'];
    $tName = $langNames[$targetLang] ?? $targetLang;
    $payload = json_encode([
        'model' => $model !== '' ? $model : 'gpt-4o-mini',
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.2,
        'messages' => [
            [
                'role' => 'system',
                'content' => "Translate the input JSON array `items` from Turkish into natural, professional {$tName}. Keep brand name 'Zeynart' intact. Return ONLY valid JSON {\"items\":[...]} with the exact same array length and order.",
            ],
            [
                'role' => 'user',
                'content' => json_encode(['items' => array_values($chunk)], JSON_UNESCAPED_UNICODE),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE);

    $res = zeynart_http_request('https://api.openai.com/v1/chat/completions', 'POST', [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ], (string)$payload, 25);

    if (!$res['ok']) {
        return null;
    }
    $outer = json_decode($res['body'], true);
    $inner = json_decode((string)($outer['choices'][0]['message']['content'] ?? ''), true);
    if (!is_array($inner) || !is_array($inner['items'] ?? null) || count($inner['items']) !== count($chunk)) {
        return null;
    }
    return array_values(array_map('strval', $inner['items']));
}

function zeynart_translate_chunk_neural_free(array $chunk, string $targetLang, string $sourceLang = 'tr'): array
{
    if ($chunk === []) {
        return [];
    }

    // Primary Provider: Chrome Neural Batch Endpoint (1-to-1 array alignment)
    $parts = [];
    foreach ($chunk as $txt) {
        $parts[] = 'q=' . rawurlencode($txt);
    }
    $urlPrimary = 'https://clients5.google.com/translate_a/t?client=dict-chrome-ex&sl='
        . rawurlencode($sourceLang) . '&tl=' . rawurlencode($targetLang);

    $res = zeynart_http_request($urlPrimary, 'POST', [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
    ], implode('&', $parts), 12);

    if ($res['ok']) {
        $decoded = json_decode($res['body'], true);
        if (is_array($decoded) && count($decoded) === count($chunk)) {
            $out = [];
            foreach ($decoded as $i => $item) {
                if (is_string($item)) {
                    $out[] = $item;
                } elseif (is_array($item) && isset($item[0]) && is_string($item[0])) {
                    $out[] = $item[0];
                } else {
                    $out[] = $chunk[$i];
                }
            }
            return $out;
        }
    }

    // Fallback #1: Google Single-Joined Neural Endpoint with numeric separator
    $sep = "\n[[[ZTR_SEP]]]\n";
    $joined = implode($sep, $chunk);
    $urlFallback1 = 'https://translate.google.com/translate_a/single?client=at&dt=t&dj=1&sl='
        . rawurlencode($sourceLang) . '&tl=' . rawurlencode($targetLang);
    $resF1 = zeynart_http_request($urlFallback1, 'POST', [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
    ], 'q=' . rawurlencode($joined), 12);

    if ($resF1['ok']) {
        $dj = json_decode($resF1['body'], true);
        if (is_array($dj) && is_array($dj['sentences'] ?? null)) {
            $fullTrans = '';
            foreach ($dj['sentences'] as $sent) {
                if (isset($sent['trans'])) {
                    $fullTrans .= (string)$sent['trans'];
                }
            }
            $split = preg_split('/\s*\[\[\[\s*ZTR_SEP\s*\]\]\]\s*/iu', $fullTrans);
            if (is_array($split) && count($split) === count($chunk)) {
                return array_values($split);
            }
        }
    }

    // Fallback #2: MyMemory API per item (last-resort resilience)
    $outFallback = [];
    foreach ($chunk as $orig) {
        if (mb_strlen($orig, 'UTF-8') > 480) {
            $outFallback[] = $orig;
            continue;
        }
        $mmUrl = 'https://api.mymemory.translated.net/get?q=' . rawurlencode($orig)
            . '&langpair=' . rawurlencode($sourceLang . '|' . $targetLang);
        $mmRes = zeynart_http_request($mmUrl, 'GET', [], null, 8);
        if ($mmRes['ok']) {
            $mmJson = json_decode($mmRes['body'], true);
            $mmTxt = trim((string)($mmJson['responseData']['translatedText'] ?? ''));
            $outFallback[] = $mmTxt !== '' ? html_entity_decode($mmTxt, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $orig;
        } else {
            $outFallback[] = $orig;
        }
    }
    return $outFallback;
}

/**
 * Translates an array of Turkish strings into $targetLang, preserving exact array indices,
 * deduplicating identical strings, and skipping non-linguistic strings.
 */
function zeynart_translate_batch(array $strings, string $targetLang, string $sourceLang = 'tr', array $i18nCfg = []): array
{
    $targetLang = strtolower(trim($targetLang));
    $sourceLang = strtolower(trim($sourceLang));
    if ($strings === [] || $targetLang === $sourceLang || !in_array($targetLang, ['en', 'de', 'ru', 'tr'], true)) {
        return $strings;
    }

    // Deduplicate non-empty strings that actually contain letters
    $uniqueList = [];
    $uniqueMap = [];
    $indexToUnique = [];

    foreach ($strings as $idx => $raw) {
        $str = (string)$raw;
        $trim = trim($str);
        // Skip empty, pure numbers/symbols, URLs, or pure brand token "ZEYNART"
        if (
            $trim === ''
            || !preg_match('/\p{L}/u', $trim)
            || preg_match('~^(https?://|mailto:|tel:|/[a-z0-9/_-]+$)~i', $trim)
            || mb_strtoupper($trim, 'UTF-8') === 'ZEYNART'
        ) {
            continue;
        }
        if (!isset($uniqueMap[$trim])) {
            $uIdx = count($uniqueList);
            $uniqueMap[$trim] = $uIdx;
            $uniqueList[] = $trim;
        }
        $indexToUnique[$idx] = $uniqueMap[$trim];
    }

    if ($uniqueList === []) {
        return $strings;
    }

    // Chunk unique strings into safe HTTP POST batches (max 30 strings or 3,600 chars per request)
    $chunks = [];
    $curChunk = [];
    $curChars = 0;
    foreach ($uniqueList as $uStr) {
        $len = strlen($uStr);
        if ($curChunk !== [] && (count($curChunk) >= 30 || ($curChars + $len) > 3600)) {
            $chunks[] = $curChunk;
            $curChunk = [];
            $curChars = 0;
        }
        $curChunk[] = $uStr;
        $curChars += $len;
    }
    if ($curChunk !== []) {
        $chunks[] = $curChunk;
    }

    $provider = strtolower(trim((string)($i18nCfg['provider'] ?? 'auto')));
    $deeplKey = trim((string)($i18nCfg['deepl_api_key'] ?? ''));
    $openaiKey = trim((string)($i18nCfg['openai_api_key'] ?? ''));
    $openaiModel = trim((string)($i18nCfg['openai_model'] ?? 'gpt-4o-mini'));

    $translatedUnique = [];
    $useParallelFree = (
        count($chunks) > 1
        && function_exists('curl_multi_init')
        && !(($provider === 'deepl' || ($provider === 'auto' && $deeplKey !== '')) && $deeplKey !== '')
        && !(($provider === 'openai' || ($provider === 'auto' && $openaiKey !== '')) && $openaiKey !== '')
    );

    if ($useParallelFree) {
        $mh = curl_multi_init();
        $handles = [];
        $urlPrimary = 'https://clients5.google.com/translate_a/t?client=dict-chrome-ex&sl='
            . rawurlencode($sourceLang) . '&tl=' . rawurlencode($targetLang);
        foreach ($chunks as $cIdx => $chunk) {
            $parts = [];
            foreach ($chunk as $txt) {
                $parts[] = 'q=' . rawurlencode($txt);
            }
            $ch = curl_init($urlPrimary);
            $chOpts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => implode('&', $parts),
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                ],
            ];
            if (defined('CURL_IPRESOLVE_V4')) {
                $chOpts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }
            curl_setopt_array($ch, $chOpts);
            curl_multi_add_handle($mh, $ch);
            $handles[$cIdx] = $ch;
        }
        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running > 0 && curl_multi_select($mh, 0.2) === -1) {
                usleep(10000);
            }
        } while ($running > 0 && $status === CURLM_OK);

        foreach ($chunks as $cIdx => $chunk) {
            $ch = $handles[$cIdx];
            $rawBody = (string)curl_multi_getcontent($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            unset($ch);

            $chunkResult = null;
            if ($code >= 200 && $code < 300 && $rawBody !== '') {
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded) && count($decoded) === count($chunk)) {
                    $chunkResult = [];
                    foreach ($decoded as $i => $item) {
                        if (is_string($item)) {
                            $chunkResult[] = $item;
                        } elseif (is_array($item) && isset($item[0]) && is_string($item[0])) {
                            $chunkResult[] = $item[0];
                        } else {
                            $chunkResult[] = $chunk[$i];
                        }
                    }
                }
            }
            if ($chunkResult === null) {
                $chunkResult = zeynart_translate_chunk_neural_free($chunk, $targetLang, $sourceLang);
            }
            foreach ($chunkResult as $cVal) {
                $translatedUnique[] = $cVal;
            }
        }
        unset($handles, $mh);
    } else {
        foreach ($chunks as $chunk) {
            $chunkResult = null;
            if (($provider === 'deepl' || ($provider === 'auto' && $deeplKey !== '')) && $deeplKey !== '') {
                $chunkResult = zeynart_translate_chunk_deepl($chunk, $targetLang, $sourceLang, $deeplKey);
            }
            if ($chunkResult === null && ($provider === 'openai' || ($provider === 'auto' && $openaiKey !== '')) && $openaiKey !== '') {
                $chunkResult = zeynart_translate_chunk_openai($chunk, $targetLang, $sourceLang, $openaiKey, $openaiModel);
            }
            if ($chunkResult === null) {
                $chunkResult = zeynart_translate_chunk_neural_free($chunk, $targetLang, $sourceLang);
            }
            foreach ($chunkResult as $cVal) {
                $translatedUnique[] = $cVal;
            }
        }
    }

    $result = $strings;
    foreach ($indexToUnique as $origIdx => $uIdx) {
        if (isset($translatedUnique[$uIdx]) && trim($translatedUnique[$uIdx]) !== '') {
            $origRaw = (string)$strings[$origIdx];
            // Preserve leading/trailing whitespace of original string
            if (preg_match('/^(\s*)(.*?)(\s*)$/su', $origRaw, $wm)) {
                $result[$origIdx] = $wm[1] . trim($translatedUnique[$uIdx]) . $wm[3];
            } else {
                $result[$origIdx] = $translatedUnique[$uIdx];
            }
        }
    }

    return $result;
}

/**
 * Translates an array of Turkish strings into multiple target languages (e.g. ['en', 'de', 'ru'])
 * simultaneously in a single parallel curl_multi wave (3x faster per blog post, zero sequential bottleneck).
 *
 * @return array<string, array<int, string>>
 */
function zeynart_translate_batch_multilang(array $strings, array $targetLangs, string $sourceLang = 'tr', array $i18nCfg = []): array
{
    $sourceLang = strtolower(trim($sourceLang));
    $cleanLangs = [];
    foreach ($targetLangs as $tl) {
        $tl = strtolower(trim((string)$tl));
        if ($tl !== '' && $tl !== $sourceLang && in_array($tl, ['en', 'de', 'ru', 'tr'], true) && !in_array($tl, $cleanLangs, true)) {
            $cleanLangs[] = $tl;
        }
    }
    if ($cleanLangs === []) {
        return [];
    }

    $provider = strtolower(trim((string)($i18nCfg['provider'] ?? 'auto')));
    $deeplKey = trim((string)($i18nCfg['deepl_api_key'] ?? ''));
    $openaiKey = trim((string)($i18nCfg['openai_api_key'] ?? ''));
    $hasCustomKey = (($provider === 'deepl' || ($provider === 'auto' && $deeplKey !== '')) && $deeplKey !== '')
        || (($provider === 'openai' || ($provider === 'auto' && $openaiKey !== '')) && $openaiKey !== '');

    if ($strings === [] || $hasCustomKey || !function_exists('curl_multi_init')) {
        $fallbackMap = [];
        foreach ($cleanLangs as $lg) {
            $fallbackMap[$lg] = zeynart_translate_batch($strings, $lg, $sourceLang, $i18nCfg);
        }
        return $fallbackMap;
    }

    $uniqueList = [];
    $uniqueMap = [];
    $indexToUnique = [];
    foreach ($strings as $idx => $raw) {
        $str = (string)$raw;
        $trim = trim($str);
        if (
            $trim === ''
            || !preg_match('/\p{L}/u', $trim)
            || preg_match('~^(https?://|mailto:|tel:|/[a-z0-9/_-]+$)~i', $trim)
            || mb_strtoupper($trim, 'UTF-8') === 'ZEYNART'
        ) {
            continue;
        }
        if (!isset($uniqueMap[$trim])) {
            $uIdx = count($uniqueList);
            $uniqueMap[$trim] = $uIdx;
            $uniqueList[] = $trim;
        }
        $indexToUnique[$idx] = $uniqueMap[$trim];
    }

    if ($uniqueList === []) {
        $same = [];
        foreach ($cleanLangs as $lg) {
            $same[$lg] = $strings;
        }
        return $same;
    }

    $chunks = [];
    $curChunk = [];
    $curChars = 0;
    foreach ($uniqueList as $uStr) {
        $len = strlen($uStr);
        if ($curChunk !== [] && (count($curChunk) >= 30 || ($curChars + $len) > 3600)) {
            $chunks[] = $curChunk;
            $curChunk = [];
            $curChars = 0;
        }
        $curChunk[] = $uStr;
        $curChars += $len;
    }
    if ($curChunk !== []) {
        $chunks[] = $curChunk;
    }

    $chunkPostBodies = [];
    foreach ($chunks as $cIdx => $chunk) {
        $parts = [];
        foreach ($chunk as $txt) {
            $parts[] = 'q=' . rawurlencode($txt);
        }
        $chunkPostBodies[$cIdx] = implode('&', $parts);
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($cleanLangs as $lg) {
        $urlPrimary = 'https://clients5.google.com/translate_a/t?client=dict-chrome-ex&sl='
            . rawurlencode($sourceLang) . '&tl=' . rawurlencode($lg);
        foreach ($chunks as $cIdx => $_chunk) {
            $ch = curl_init($urlPrimary);
            $chOpts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $chunkPostBodies[$cIdx],
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                ],
            ];
            if (defined('CURL_IPRESOLVE_V4')) {
                $chOpts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }
            curl_setopt_array($ch, $chOpts);
            curl_multi_add_handle($mh, $ch);
            $handles[$lg][$cIdx] = $ch;
        }
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running > 0 && curl_multi_select($mh, 0.15) === -1) {
            usleep(10000);
        }
    } while ($running > 0 && $status === CURLM_OK);

    $outByLang = [];
    foreach ($cleanLangs as $lg) {
        $translatedUnique = [];
        foreach ($chunks as $cIdx => $chunk) {
            $ch = $handles[$lg][$cIdx];
            $rawBody = (string)curl_multi_getcontent($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            unset($ch);

            $chunkResult = null;
            if ($code >= 200 && $code < 300 && $rawBody !== '') {
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded) && count($decoded) === count($chunk)) {
                    $chunkResult = [];
                    foreach ($decoded as $i => $item) {
                        if (is_string($item)) {
                            $chunkResult[] = $item;
                        } elseif (is_array($item) && isset($item[0]) && is_string($item[0])) {
                            $chunkResult[] = $item[0];
                        } else {
                            $chunkResult[] = $chunk[$i];
                        }
                    }
                }
            }
            if ($chunkResult === null) {
                $chunkResult = zeynart_translate_chunk_neural_free($chunk, $lg, $sourceLang);
            }
            foreach ($chunkResult as $cVal) {
                $translatedUnique[] = $cVal;
            }
        }

        $resLang = $strings;
        foreach ($indexToUnique as $origIdx => $uIdx) {
            if (isset($translatedUnique[$uIdx]) && trim($translatedUnique[$uIdx]) !== '') {
                $origRaw = (string)$strings[$origIdx];
                if (preg_match('/^(\s*)(.*?)(\s*)$/su', $origRaw, $wm)) {
                    $resLang[$origIdx] = $wm[1] . trim($translatedUnique[$uIdx]) . $wm[3];
                } else {
                    $resLang[$origIdx] = $translatedUnique[$uIdx];
                }
            }
        }
        $outByLang[$lg] = $resLang;
    }

    unset($handles, $mh);

    return $outByLang;
}

/**
 * Extracts translatable text nodes and img[alt] attributes from rich HTML so they can be
 * batch-translated alongside post metadata in a single request without corrupting HTML tags.
 */
function zeynart_extract_html_texts(string $html): array
{
    if (trim($html) === '' || !preg_match('/\p{L}/u', $html)) {
        return ['texts' => []];
    }

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $wrapped = '<?xml encoding="utf-8" ?><div id="__zeynart_i18n_root__">' . $html . '</div>';
    @$dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

    $xp = new \DOMXPath($dom);
    $root = $xp->query('//*[@id="__zeynart_i18n_root__"]')->item(0);
    if (!$root) {
        return ['texts' => []];
    }

    $texts = [];
    $skipTags = ['script' => true, 'style' => true, 'code' => true, 'pre' => true, 'svg' => true];

    foreach ($xp->query('.//text()', $root) as $textNode) {
        /** @var \DOMText $textNode */
        $parent = $textNode->parentNode;
        $skip = false;
        while ($parent instanceof \DOMElement && $parent->getAttribute('id') !== '__zeynart_i18n_root__') {
            $tag = strtolower($parent->tagName);
            if (isset($skipTags[$tag]) || $parent->getAttribute('class') === 'journal-section-number') {
                $skip = true;
                break;
            }
            $parent = $parent->parentNode;
        }
        if ($skip) {
            continue;
        }
        $val = (string)$textNode->nodeValue;
        if (preg_match('/^(\s*)(.*?)(\s*)$/su', $val, $m) && $m[2] !== '' && preg_match('/\p{L}/u', $m[2])) {
            $texts[] = $m[2];
        }
    }

    foreach ($xp->query('.//img[@alt]', $root) as $imgNode) {
        /** @var \DOMElement $imgNode */
        $alt = trim($imgNode->getAttribute('alt'));
        if ($alt !== '' && preg_match('/\p{L}/u', $alt)) {
            $texts[] = $alt;
        }
    }

    return ['texts' => $texts];
}

function zeynart_apply_html_translations(string $html, array $translatedTexts, string $targetLang): string
{
    if (trim($html) === '') {
        return $html;
    }

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $wrapped = '<?xml encoding="utf-8" ?><div id="__zeynart_i18n_root__">' . $html . '</div>';
    @$dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

    $xp = new \DOMXPath($dom);
    $root = $xp->query('//*[@id="__zeynart_i18n_root__"]')->item(0);
    if (!$root) {
        return $html;
    }

    $skipTags = ['script' => true, 'style' => true, 'code' => true, 'pre' => true, 'svg' => true];
    $cursor = 0;

    foreach ($xp->query('.//text()', $root) as $textNode) {
        /** @var \DOMText $textNode */
        $parent = $textNode->parentNode;
        $skip = false;
        while ($parent instanceof \DOMElement && $parent->getAttribute('id') !== '__zeynart_i18n_root__') {
            $tag = strtolower($parent->tagName);
            if (isset($skipTags[$tag]) || $parent->getAttribute('class') === 'journal-section-number') {
                $skip = true;
                break;
            }
            $parent = $parent->parentNode;
        }
        if ($skip) {
            continue;
        }
        $val = (string)$textNode->nodeValue;
        if (preg_match('/^(\s*)(.*?)(\s*)$/su', $val, $m) && $m[2] !== '' && preg_match('/\p{L}/u', $m[2])) {
            if (isset($translatedTexts[$cursor]) && trim((string)$translatedTexts[$cursor]) !== '') {
                $textNode->nodeValue = $m[1] . trim((string)$translatedTexts[$cursor]) . $m[3];
            }
            $cursor++;
        }
    }

    foreach ($xp->query('.//img[@alt]', $root) as $imgNode) {
        /** @var \DOMElement $imgNode */
        $alt = trim($imgNode->getAttribute('alt'));
        if ($alt !== '' && preg_match('/\p{L}/u', $alt)) {
            if (isset($translatedTexts[$cursor]) && trim((string)$translatedTexts[$cursor]) !== '') {
                $imgNode->setAttribute('alt', trim((string)$translatedTexts[$cursor]));
            }
            $cursor++;
        }
    }

    // Rewrite internal relative Zeynart page links (e.g. /hizmetler/..., /iletisim/) to localized language prefix
    if ($targetLang !== 'tr' && in_array($targetLang, ['en', 'de', 'ru'], true)) {
        foreach ($xp->query('.//a[@href]', $root) as $aNode) {
            /** @var \DOMElement $aNode */
            $href = trim($aNode->getAttribute('href'));
            if ($href !== '' && str_starts_with($href, '/') && !str_starts_with($href, '//') && !zeynart_is_unprefixed_path($href)) {
                $aNode->setAttribute('href', lang_url($href, $targetLang));
            }
        }
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    return $out !== '' ? $out : $html;
}

function zeynart_translate_html(string $html, string $targetLang, string $sourceLang = 'tr', array $i18nCfg = []): string
{
    if (trim($html) === '' || $targetLang === $sourceLang) {
        return $html;
    }
    $extracted = zeynart_extract_html_texts($html);
    if ($extracted['texts'] === []) {
        return $html;
    }
    $translated = zeynart_translate_batch($extracted['texts'], $targetLang, $sourceLang, $i18nCfg);
    return zeynart_apply_html_translations($html, $translated, $targetLang);
}

/**
 * Automatically translates a full Turkish blog post into English ('en'), German ('de'), and Russian ('ru'),
 * populating $post['translations']['en'|'de'|'ru'] with localized title, SEO slug, category, excerpt,
 * rich HTML body, table of contents, FAQs, and SEO/OpenGraph metadata.
 */
function zeynart_translate_blog_post(array $post, array $targetLangs = ['en', 'de', 'ru'], bool $force = false, array $i18nCfg = []): array
{
    $baseSlug = slugify((string)($post['slug'] ?? $post['title'] ?? 'yeni-yazi'));
    $title = trim((string)($post['title'] ?? ''));
    if ($title === '') {
        $title = ucwords(str_replace('-', ' ', $baseSlug));
    }
    $category = trim((string)($post['category'] ?? 'GENEL'));
    $excerpt = trim((string)($post['excerpt'] ?? ''));
    $imageAlt = trim((string)($post['image_alt'] ?? $title));
    $authorRole = trim((string)($post['author_role'] ?? 'Tasarım & teknoloji notları'));
    $srvLabel = trim((string)($post['service_link']['label'] ?? 'Hizmetlerimiz'));
    $contentHtml = (string)($post['content_html'] ?? '');
    $toc = is_array($post['toc'] ?? null) ? array_values($post['toc']) : [];
    $faqs = is_array($post['faqs'] ?? null) ? array_values($post['faqs']) : [];
    $seo = is_array($post['seo'] ?? null) ? $post['seo'] : [];
    $dateIso = (string)($post['date'] ?? date('Y-m-d'));
    $rawRead = (string)($post['read'] ?? estimate_reading_time($contentHtml));

    $sourceHash = md5(json_encode([
        $title, $category, $excerpt, $imageAlt, $contentHtml, $toc, $faqs,
        $seo['meta_title'] ?? '', $seo['meta_desc'] ?? '', $seo['ai_summary'] ?? '',
    ], JSON_UNESCAPED_UNICODE) ?: '');

    if (!isset($post['translations']) || !is_array($post['translations'])) {
        $post['translations'] = [];
    }

    $langsToRun = [];
    foreach ($targetLangs as $tl) {
        $tl = strtolower(trim((string)$tl));
        if (!in_array($tl, ['en', 'de', 'ru'], true)) {
            continue;
        }
        $existing = $post['translations'][$tl] ?? null;
        if (!$force && is_array($existing)) {
            if (!empty($existing['manual_lock'])) {
                continue;
            }
            if (($existing['source_hash'] ?? '') === $sourceHash && !empty($existing['title']) && !empty($existing['content_html'])) {
                continue;
            }
        }
        $langsToRun[] = $tl;
    }

    if ($langsToRun === []) {
        return $post;
    }

    // Build ONE unified string batch (scalars + TOC + FAQs + HTML text nodes) so each language needs just 1 batch pass
    $batch = [];
    $idxTitle = count($batch);       $batch[] = $title;
    $idxCategory = count($batch);    $batch[] = $category;
    $idxExcerpt = count($batch);     $batch[] = $excerpt;
    $idxImgAlt = count($batch);      $batch[] = $imageAlt;
    $idxAuthorRole = count($batch);  $batch[] = $authorRole;
    $idxSrvLabel = count($batch);    $batch[] = $srvLabel;
    $idxMetaTitle = count($batch);   $batch[] = (string)($seo['meta_title'] ?? '');
    $idxMetaDesc = count($batch);    $batch[] = (string)($seo['meta_desc'] ?? '');
    $idxFocusKw = count($batch);     $batch[] = (string)($seo['focus_keyword'] ?? '');
    $idxOgTitle = count($batch);     $batch[] = (string)($seo['og_title'] ?? '');
    $idxOgDesc = count($batch);      $batch[] = (string)($seo['og_desc'] ?? '');
    $idxAiSum = count($batch);       $batch[] = (string)($seo['ai_summary'] ?? '');

    $tocIndices = [];
    foreach ($toc as $k => $tItem) {
        $tocIndices[$k] = count($batch);
        $batch[] = (string)($tItem['title'] ?? '');
    }

    $faqIndices = [];
    foreach ($faqs as $k => $fq) {
        $qI = count($batch); $batch[] = (string)($fq['q'] ?? '');
        $aI = count($batch); $batch[] = (string)($fq['a'] ?? '');
        $faqIndices[$k] = ['q' => $qI, 'a' => $aI];
    }

    $htmlExtract = zeynart_extract_html_texts($contentHtml);
    $htmlStartIdx = count($batch);
    $htmlCount = count($htmlExtract['texts']);
    foreach ($htmlExtract['texts'] as $ht) {
        $batch[] = $ht;
    }

    $slugMap = load_blog_slug_map();
    $multiBatches = zeynart_translate_batch_multilang($batch, $langsToRun, 'tr', $i18nCfg);

    foreach ($langsToRun as $lang) {
        $trBatch = $multiBatches[$lang] ?? zeynart_translate_batch($batch, $lang, 'tr', $i18nCfg);

        $trTitle = trim((string)($trBatch[$idxTitle] ?? $title)) ?: $title;
        $prevSlug = trim((string)($post['translations'][$lang]['slug'] ?? ''));

        // Keep existing localized slug if already set (prevents broken external links on edits), otherwise generate clean localized SEO slug
        if ($prevSlug !== '' && !$force) {
            $locSlug = zeynart_slugify_multilang($prevSlug, $lang, $baseSlug);
        } else {
            $candidateSlug = zeynart_slugify_multilang($trTitle, $lang, $baseSlug);
            // Avoid slug collision with another post in the same language
            $existingMappedBase = $slugMap[$lang][$candidateSlug] ?? null;
            if ($existingMappedBase !== null && $existingMappedBase !== $baseSlug) {
                $candidateSlug .= '-' . substr(md5($baseSlug), 0, 4);
            }
            $locSlug = $candidateSlug;
        }

        // Prefer dictionary category label when known, otherwise neural-translated category
        $dictCat = t_category($category, $lang);
        $trCategory = ($dictCat !== $category)
            ? $dictCat
            : mb_strtoupper(trim((string)($trBatch[$idxCategory] ?? $category)), 'UTF-8');

        $trToc = [];
        foreach ($toc as $k => $tItem) {
            $trToc[] = [
                'id' => (string)($tItem['id'] ?? ('bolum-' . ($k + 1))),
                'title' => trim((string)($trBatch[$tocIndices[$k]] ?? ($tItem['title'] ?? ''))),
            ];
        }

        $trFaqs = [];
        foreach ($faqs as $k => $fq) {
            $qTxt = trim((string)($trBatch[$faqIndices[$k]['q']] ?? ($fq['q'] ?? '')));
            $aTxt = trim((string)($trBatch[$faqIndices[$k]['a']] ?? ($fq['a'] ?? '')));
            if ($qTxt !== '' && $aTxt !== '') {
                $trFaqs[] = ['q' => $qTxt, 'a' => $aTxt];
            }
        }

        $htmlTranslatedSlice = $htmlCount > 0 ? array_slice($trBatch, $htmlStartIdx, $htmlCount) : [];
        $trHtml = $htmlCount > 0
            ? zeynart_apply_html_translations($contentHtml, $htmlTranslatedSlice, $lang)
            : $contentHtml;

        $trExcerpt = trim((string)($trBatch[$idxExcerpt] ?? $excerpt));
        $trMetaTitle = trim((string)($trBatch[$idxMetaTitle] ?? ''));
        if ($trMetaTitle === '') {
            $trMetaTitle = $trTitle . ' | ZEYNART';
        }
        $trMetaDesc = trim((string)($trBatch[$idxMetaDesc] ?? '')) ?: $trExcerpt;
        $trAiSum = trim((string)($trBatch[$idxAiSum] ?? '')) ?: $trMetaDesc;

        $post['translations'][$lang] = [
            'lang' => $lang,
            'slug' => $locSlug,
            'title' => $trTitle,
            'category' => $trCategory,
            'excerpt' => $trExcerpt,
            'image_alt' => trim((string)($trBatch[$idxImgAlt] ?? $trTitle)),
            'author_role' => trim((string)($trBatch[$idxAuthorRole] ?? t('Tasarım & teknoloji notları', $lang))),
            'service_label' => trim((string)($trBatch[$idxSrvLabel] ?? t('Hizmetlerimiz', $lang))),
            'date_display' => format_localized_date($dateIso, $lang),
            'read' => localize_reading_time($rawRead, $lang),
            'content_html' => $trHtml,
            'toc' => $trToc,
            'faqs' => $trFaqs,
            'seo' => [
                'meta_title' => $trMetaTitle,
                'meta_desc' => $trMetaDesc,
                'focus_keyword' => trim((string)($trBatch[$idxFocusKw] ?? '')),
                'og_title' => trim((string)($trBatch[$idxOgTitle] ?? '')),
                'og_desc' => trim((string)($trBatch[$idxOgDesc] ?? '')),
                'ai_summary' => $trAiSum,
            ],
            'source_hash' => $sourceHash,
            'translated_at' => date('c'),
            'manual_lock' => false,
        ];
    }

    return $post;
}

/**
 * Ensures a blog post has localized translations and returns a localized $post array ready for blog-detay.php,
 * including $post['lang_alternates'] for <link rel="alternate" hreflang="..."> and Header Language Switcher.
 */
function zeynart_ensure_post_translation(array $post, string $lang, array $store = []): array
{
    $baseSlug = (string)($post['slug'] ?? '');
    $lang = strtolower(trim($lang));

    // If viewing in EN/DE/RU and translation does not exist yet (e.g. legacy post), auto-translate & persist!
    if ($lang !== 'tr' && in_array($lang, ['en', 'de', 'ru'], true)) {
        $hasTarget = !empty($post['translations'][$lang]['title']) && !empty($post['translations'][$lang]['content_html']);
        if (!$hasTarget) {
            $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
            $post = zeynart_translate_blog_post($post, ['en', 'de', 'ru'], false, $i18nCfg);
            if ($baseSlug !== '') {
                save_post($post, $baseSlug);
            }
        }
    }

    // Build exact alternate paths across all 4 languages
    $alternates = [
        'tr' => '/blog/' . $baseSlug . '/',
        'en' => '/en/blog/' . ((string)($post['translations']['en']['slug'] ?? $baseSlug)) . '/',
        'de' => '/de/blog/' . ((string)($post['translations']['de']['slug'] ?? $baseSlug)) . '/',
        'ru' => '/ru/blog/' . ((string)($post['translations']['ru']['slug'] ?? $baseSlug)) . '/',
    ];

    $post['base_slug'] = $baseSlug;
    $post['lang_alternates'] = $alternates;

    if ($lang === 'tr' || !isset($post['translations'][$lang]) || !is_array($post['translations'][$lang])) {
        return $post;
    }

    $tr = $post['translations'][$lang];
    $localized = $post;
    $localized['slug'] = (string)($tr['slug'] ?? $baseSlug);
    $localized['title'] = (string)($tr['title'] ?? $post['title'] ?? '');
    $localized['category'] = (string)($tr['category'] ?? t_category((string)($post['category'] ?? 'GENEL'), $lang));
    $localized['excerpt'] = (string)($tr['excerpt'] ?? $post['excerpt'] ?? '');
    $localized['image_alt'] = (string)($tr['image_alt'] ?? $localized['title']);
    $localized['author_role'] = (string)($tr['author_role'] ?? t('Tasarım & teknoloji notları', $lang));
    $localized['date_display'] = (string)($tr['date_display'] ?? format_localized_date((string)($post['date'] ?? ''), $lang));
    $localized['read'] = (string)($tr['read'] ?? localize_reading_time((string)($post['read'] ?? '3 dk'), $lang));
    if (!empty($tr['content_html'])) {
        $localized['content_html'] = (string)$tr['content_html'];
    }
    if (isset($tr['toc']) && is_array($tr['toc'])) {
        $localized['toc'] = $tr['toc'];
    }
    if (isset($tr['faqs']) && is_array($tr['faqs'])) {
        $localized['faqs'] = $tr['faqs'];
    }
    if (!empty($tr['service_label']) && is_array($localized['service_link'] ?? null)) {
        $localized['service_link']['label'] = (string)$tr['service_label'];
    }
    if (is_array($tr['seo'] ?? null)) {
        $localized['seo'] = array_merge(is_array($post['seo'] ?? null) ? $post['seo'] : [], array_filter(
            $tr['seo'],
            static fn(mixed $v): bool => is_string($v) && trim($v) !== ''
        ));
    }

    return $localized;
}

/**
 * Localizes an array of blog index card summaries (e.g. 24 cards on /en/blog/ or related posts)
 * using pre-saved $item['i18n'][$lang] when available and batch-caching legacy cards in data/i18n_cache/blog_cards_{lang}.json.
 */
function zeynart_localize_blog_cards(array $items, string $lang, array $store = []): array
{
    $lang = strtolower(trim($lang));
    if ($lang === 'tr' || !in_array($lang, ['en', 'de', 'ru'], true) || $items === []) {
        return $items;
    }

    $cacheFile = DATA_DIR . '/i18n_cache/blog_cards_' . $lang . '.json';
    $decoded = zeynart_read_json_data($cacheFile);
    $cardCache = is_array($decoded) ? $decoded : [];

    $missingStrings = [];
    $missingMeta = [];

    foreach ($items as $idx => $it) {
        $slug = (string)($it['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        // Already has saved post-level i18n summary in blog_index.json?
        if (!empty($it['i18n'][$lang]['title'])) {
            continue;
        }
        // Already cached in blog_cards_{lang}.json?
        $srcKey = md5(($it['title'] ?? '') . '|' . ($it['excerpt'] ?? ''));
        if (isset($cardCache[$slug]) && ($cardCache[$slug]['h'] ?? '') === $srcKey) {
            continue;
        }
        $tIdx = count($missingStrings);
        $missingStrings[] = (string)($it['title'] ?? '');
        $eIdx = count($missingStrings);
        $missingStrings[] = (string)($it['excerpt'] ?? '');
        $missingMeta[] = ['slug' => $slug, 'h' => $srcKey, 't' => $tIdx, 'e' => $eIdx];
    }

    if ($missingStrings !== []) {
        $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
        $trBatch = zeynart_translate_batch($missingStrings, $lang, 'tr', $i18nCfg);
        foreach ($missingMeta as $mm) {
            $trT = trim((string)($trBatch[$mm['t']] ?? ''));
            $trE = trim((string)($trBatch[$mm['e']] ?? ''));
            if ($trT !== '') {
                $cardCache[$mm['slug']] = [
                    'h' => $mm['h'],
                    'title' => $trT,
                    'excerpt' => $trE,
                ];
            }
        }
        atomic_write_json($cacheFile, $cardCache, false);
    }

    $out = [];
    foreach ($items as $it) {
        $slug = (string)($it['slug'] ?? '');
        $loc = $it;
        $loc['base_slug'] = $slug;
        $loc['date_display'] = format_localized_date((string)($it['date'] ?? ''), $lang);
        $loc['read'] = localize_reading_time((string)($it['read'] ?? '3 dk'), $lang);

        if (!empty($it['i18n'][$lang]['title'])) {
            $i18nEntry = $it['i18n'][$lang];
            $loc['slug'] = (string)($i18nEntry['slug'] ?? $slug);
            $loc['title'] = (string)$i18nEntry['title'];
            $loc['category'] = (string)($i18nEntry['category'] ?? t_category((string)($it['category'] ?? 'GENEL'), $lang));
            $loc['excerpt'] = (string)($i18nEntry['excerpt'] ?? $it['excerpt'] ?? '');
        } elseif (isset($cardCache[$slug])) {
            $loc['title'] = (string)($cardCache[$slug]['title'] ?? $it['title'] ?? '');
            $loc['excerpt'] = (string)($cardCache[$slug]['excerpt'] ?? $it['excerpt'] ?? '');
            $loc['category'] = t_category((string)($it['category'] ?? 'GENEL'), $lang);
        } else {
            $loc['category'] = t_category((string)($it['category'] ?? 'GENEL'), $lang);
        }
        if (!empty($it['categories']) && is_array($it['categories'])) {
            $loc['categories'] = array_values(array_map(static fn(string $cn): string => t_category($cn, $lang), $it['categories']));
        }
        $out[] = $loc;
    }

    return $out;
}
