<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Clean text for OpenGraph / Twitter / WhatsApp meta tags:
 * strips HTML, decodes HTML entities (&nbsp;, &#8217;, &quot;, etc.), normalizes whitespace,
 * and truncates cleanly on word boundary.
 */
function zeynart_clean_social_text(string $raw, int $maxLen = 220): string
{
    if ($raw === '') {
        return '';
    }
    $stripped = strip_tags($raw);
    $decoded = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Replace non-breaking spaces (\xC2\xA0) and control/newline sequences with single space
    $normalized = str_replace("\xC2\xA0", ' ', $decoded);
    $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
    $normalized = trim($normalized);

    if ($maxLen > 0 && mb_strlen($normalized, 'UTF-8') > $maxLen) {
        $cut = mb_substr($normalized, 0, $maxLen - 1, 'UTF-8');
        $lastSpace = mb_strrpos($cut, ' ', 0, 'UTF-8');
        if ($lastSpace !== false && $lastSpace > (int)($maxLen * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace, 'UTF-8');
        }
        $normalized = rtrim($cut, " \t\n\r\0\x0B.,;:-") . '…';
    }
    return $normalized;
}

/**
 * Extract first readable plain-text excerpt from rich HTML content when meta_desc/excerpt is empty.
 */
function zeynart_extract_plain_excerpt(string $html, int $maxLen = 185): string
{
    if (trim($html) === '') {
        return '';
    }
    // Add spaces around block closures so headings and paragraphs don't glue together
    $spaced = preg_replace('~</(p|h[1-6]|li|div|section|blockquote|br)\s*>~i', '$0 ', $html) ?? $html;
    return zeynart_clean_social_text($spaced, $maxLen);
}

/**
 * Extract first <img src="..."> from rich HTML as fallback cover image.
 */
function zeynart_extract_first_image(string $html): string
{
    if ($html === '') {
        return '';
    }
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
        return trim($m[1]);
    }
    return '';
}

/**
 * Normalize a local relative media path (/uploads/... or /images/...) if valid and existing on disk.
 */
function zeynart_normalize_local_media_path(string $rawPath, array $store = []): ?string
{
    $p = trim($rawPath);
    if ($p === '') {
        return null;
    }

    // Strip own canonical domain if full URL was entered in Admin
    $canonDomain = rtrim((string)($store['seo']['canonical_domain'] ?? ''), '/');
    if ($canonDomain !== '' && str_starts_with($p, $canonDomain . '/')) {
        $p = substr($p, strlen($canonDomain));
    } elseif (str_starts_with($p, SITE_URL . '/')) {
        $p = substr($p, strlen(SITE_URL));
    }

    // Strip query string if any
    $qPos = strpos($p, '?');
    if ($qPos !== false) {
        $p = substr($p, 0, $qPos);
    }
    $p = rawurldecode($p);

    if (BASE_PATH !== '' && str_starts_with($p, BASE_PATH . '/')) {
        $p = substr($p, strlen(BASE_PATH));
    }

    $clean = '/' . ltrim(str_replace('\\', '/', $p), '/');
    if (str_contains($clean, '..')) {
        return null;
    }
    if (!preg_match('~^/(uploads|images)/[A-Za-z0-9_\-\./]+$~', $clean)) {
        if ($clean === '/icon.png' && is_file(ROOT_DIR . '/icon.png')) {
            return '/icon.png';
        }
        return null;
    }

    $abs = ROOT_DIR . $clean;
    if (is_file($abs)) {
        return $clean;
    }

    // Check WebP converted equivalent if legacy .jpg/.png path was stored
    if (preg_match('~^/uploads/(.+)\.(jpe?g|png)$~i', $clean, $em)) {
        $cand1 = '/uploads/' . $em[1] . '-' . strtolower($em[2]) . '.webp';
        if (is_file(ROOT_DIR . $cand1)) {
            return $cand1;
        }
        $cand2 = '/uploads/' . $em[1] . '.webp';
        if (is_file(ROOT_DIR . $cand2)) {
            return $cand2;
        }
    }

    return null;
}

/**
 * Build a URL-safe token encoding the local relative path + filemtime for instant social cache busting.
 */
function zeynart_build_og_card_token(string $cleanLocalPath): string
{
    $abs = ROOT_DIR . $cleanLocalPath;
    $mtime = is_file($abs) ? (int)filemtime($abs) : 1;
    $payload = $cleanLocalPath . '|' . $mtime;
    return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
}

/**
 * Decode an /og-card/{token}.jpg token back into a verified local relative path.
 */
function zeynart_decode_og_card_token(string $token): ?string
{
    $cleanToken = preg_replace('/\.jpe?g$/i', '', trim($token)) ?? '';
    if ($cleanToken === '' || strlen($cleanToken) > 512) {
        return null;
    }
    $b64 = strtr($cleanToken, '-_', '+/');
    $rem = strlen($b64) % 4;
    if ($rem > 0) {
        $b64 .= str_repeat('=', 4 - $rem);
    }
    $decoded = base64_decode($b64, true);
    if (!is_string($decoded) || $decoded === '') {
        return null;
    }
    $parts = explode('|', $decoded, 2);
    return zeynart_normalize_local_media_path($parts[0]);
}

/**
 * Resolve any candidate social image into a guaranteed 1200x630 JPEG URL (for WhatsApp, LinkedIn,
 * Facebook, X/Twitter, Telegram, Discord, iMessage compatibility — never .webp or .avif!).
 *
 * @return array{url:string, relative_url:string, source_path:string, mime:string, width:int, height:int}
 */
function zeynart_resolve_social_image(string $rawCandidate, array $store): array
{
    $defaultOg = trim((string)($store['site']['og_image'] ?? '')) ?: '/images/opengraph-image.png';
    $candidate = trim($rawCandidate);

    // External HTTPS JPG/PNG image (if explicitly entered)
    if (preg_match('~^https?://~i', $candidate)) {
        $localCheck = zeynart_normalize_local_media_path($candidate, $store);
        if ($localCheck === null) {
            return [
                'url' => $candidate,
                'relative_url' => $candidate,
                'source_path' => $candidate,
                'mime' => str_ends_with(strtolower($candidate), '.png') ? 'image/png' : 'image/jpeg',
                'width' => 1200,
                'height' => 630,
            ];
        }
        $candidate = $localCheck;
    }

    $localPath = zeynart_normalize_local_media_path($candidate, $store);
    if ($localPath === null || str_ends_with(strtolower($localPath), '.svg')) {
        $localPath = zeynart_normalize_local_media_path($defaultOg, $store)
            ?? zeynart_normalize_local_media_path('/images/opengraph-image.png', $store)
            ?? '/images/opengraph-image.png';
    }

    // Materialize a real physical 1200x630 <275KB JPEG in /uploads/og/*.jpg so Plesk Nginx, Apache,
    // WhatsApp, LinkedIn, Facebook, X/Twitter, and Telegram serve it statically with Content-Length!
    $jpegFile = zeynart_generate_og_jpeg_file($localPath);
    if ($jpegFile !== null && is_file($jpegFile)) {
        $relCard = '/uploads/og/' . basename($jpegFile);
        return [
            'url' => canonical_url($relCard, $store),
            'relative_url' => url($relCard),
            'source_path' => $localPath,
            'physical_file' => $jpegFile,
            'mime' => 'image/jpeg',
            'width' => 1200,
            'height' => 630,
        ];
    }

    $token = zeynart_build_og_card_token($localPath);
    $relCard = '/og-card/' . $token . '.jpg';

    return [
        'url' => canonical_url($relCard, $store),
        'relative_url' => url($relCard),
        'source_path' => $localPath,
        'physical_file' => '',
        'mime' => 'image/jpeg',
        'width' => 1200,
        'height' => 630,
    ];
}

/**
 * Generate (or return cached) 1200x630 <275KB JPEG file on disk in /uploads/og/ from a local source image.
 */
function zeynart_generate_og_jpeg_file(?string $cleanLocalPath): ?string
{
    $fallbackPng = IMAGES_DIR . '/opengraph-image.png';
    $srcAbs = ($cleanLocalPath !== null && is_file(ROOT_DIR . $cleanLocalPath))
        ? (ROOT_DIR . $cleanLocalPath)
        : (is_file($fallbackPng) ? $fallbackPng : null);

    if ($srcAbs === null || !is_file($srcAbs)) {
        return null;
    }

    $cacheDir = UPLOADS_DIR . '/og';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    $mtime = (int)@filemtime($srcAbs);
    $relKey = ($cleanLocalPath !== null && is_file(ROOT_DIR . $cleanLocalPath)) ? $cleanLocalPath : '/images/opengraph-image.png';
    $slugHint = preg_replace('/[^a-z0-9\-]/', '', strtolower(pathinfo($relKey, PATHINFO_FILENAME))) ?: 'zeynart';
    $slugHint = substr($slugHint, 0, 36);
    $cacheKey = $slugHint . '-' . substr(sha1($relKey . '|' . $mtime . '|og1200x630'), 0, 12);
    $cacheFile = $cacheDir . '/' . $cacheKey . '.jpg';

    if (is_file($cacheFile) && filesize($cacheFile) > 500) {
        return $cacheFile;
    }

    if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
        return null;
    }

    $info = @getimagesize($srcAbs);
    $mime = is_array($info) ? strtolower((string)($info['mime'] ?? '')) : '';
    $srcIm = false;

    if ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $srcIm = @imagecreatefromwebp($srcAbs);
    } elseif ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $srcIm = @imagecreatefromjpeg($srcAbs);
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $srcIm = @imagecreatefrompng($srcAbs);
    } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
        $srcIm = @imagecreatefromgif($srcAbs);
    } elseif ($mime === 'image/avif' && function_exists('imagecreatefromavif')) {
        $srcIm = @imagecreatefromavif($srcAbs);
    }

    // Fallback to opengraph-image.png if source image format could not be decoded
    if ($srcIm === false && $srcAbs !== $fallbackPng && is_file($fallbackPng) && function_exists('imagecreatefrompng')) {
        $srcIm = @imagecreatefrompng($fallbackPng);
    }
    if ($srcIm === false) {
        return null;
    }

    $srcW = imagesx($srcIm);
    $srcH = imagesy($srcIm);
    if ($srcW < 1 || $srcH < 1) {
        unset($srcIm);
        return null;
    }

    $dstW = 1200;
    $dstH = 630;
    $canvas = imagecreatetruecolor($dstW, $dstH);

    // Fill luxury dark petrol background (#070f12) so transparent images look refined
    $bgCol = imagecolorallocate($canvas, 7, 15, 18);
    imagefill($canvas, 0, 0, $bgCol);
    imagealphablending($canvas, true);

    $srcRatio = $srcW / $srcH;
    $dstRatio = $dstW / $dstH; // 1.90476

    if ($srcW < 420 || $srcH < 220 || $srcRatio > 3.4 || $srcRatio < 0.65) {
        // Small or extreme-ratio asset (e.g. logo/icon/portrait): contain cleanly over subtle dark teal glow
        $accentGlow = imagecolorallocatealpha($canvas, 20, 184, 166, 114);
        imagefilledellipse($canvas, 600, 315, 760, 420, $accentGlow);

        $maxBoxW = 960;
        $maxBoxH = 490;
        $scale = min($maxBoxW / $srcW, $maxBoxH / $srcH, 2.0);
        $drawW = max(1, (int)round($srcW * $scale));
        $drawH = max(1, (int)round($srcH * $scale));
        $dstX = (int)floor(($dstW - $drawW) / 2);
        $dstY = (int)floor(($dstH - $drawH) / 2);

        imagecopyresampled($canvas, $srcIm, $dstX, $dstY, 0, 0, $drawW, $drawH, $srcW, $srcH);
    } else {
        // Standard photo/banner: smart object-fit cover crop to 1200x630
        if ($srcRatio > $dstRatio) {
            // Source is wider than 1200:630 -> crop left/right equally
            $cropH = $srcH;
            $cropW = (int)round($srcH * $dstRatio);
            $cropX = (int)floor(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            // Source is taller than 1200:630 -> crop top/bottom slightly favoring upper-middle (0.38)
            $cropW = $srcW;
            $cropH = (int)round($srcW / $dstRatio);
            $cropX = 0;
            $cropY = (int)floor(($srcH - $cropH) * 0.38);
        }
        imagecopyresampled($canvas, $srcIm, 0, 0, $cropX, $cropY, $dstW, $dstH, max(1, $cropW), max(1, $cropH));
    }

    unset($srcIm);

    // Progressive JPEG helps WhatsApp & mobile previews render faster; ensure < 275 KB for WhatsApp Android/iOS!
    imageinterlace($canvas, true);
    $qualities = [85, 78, 70, 62];
    $jpegBytes = '';
    foreach ($qualities as $q) {
        ob_start();
        imagejpeg($canvas, null, $q);
        $jpegBytes = (string)ob_get_clean();
        if (strlen($jpegBytes) <= 275000) {
            break;
        }
    }
    unset($canvas);

    if ($jpegBytes === '') {
        return null;
    }

    $tmp = $cacheFile . '.tmp.' . bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, $jpegBytes, LOCK_EX) !== false) {
        if (!@rename($tmp, $cacheFile)) {
            @copy($tmp, $cacheFile);
            @unlink($tmp);
        }
    }

    return is_file($cacheFile) ? $cacheFile : null;
}

/**
 * Serve /og-card/{token}.jpg or /og-card.jpg?src=... HTTP response with exact Content-Length & JPEG MIME.
 */
function zeynart_serve_og_card_request(string $token = '', string $rawSrcQuery = ''): never
{
    $localPath = null;
    if ($token !== '') {
        $localPath = zeynart_decode_og_card_token($token);
    }
    if ($localPath === null && $rawSrcQuery !== '') {
        $localPath = zeynart_normalize_local_media_path($rawSrcQuery);
    }

    $jpegFile = zeynart_generate_og_jpeg_file($localPath);
    if ($jpegFile !== null && is_file($jpegFile)) {
        $size = (int)filesize($jpegFile);
        $etag = '"' . md5($jpegFile . '|' . $size) . '"';
        if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
            http_response_code(304);
            exit;
        }
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . $size);
        header('Cache-Control: public, max-age=2592000');
        header('ETag: ' . $etag);
        header('Access-Control-Allow-Origin: *');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            readfile($jpegFile);
        }
        exit;
    }

    // Ultimate fallback if GD is absent: serve /images/opengraph-image.png with exact Content-Length
    $fallbackPng = IMAGES_DIR . '/opengraph-image.png';
    if (is_file($fallbackPng)) {
        $size = (int)filesize($fallbackPng);
        header('Content-Type: image/png');
        header('Content-Length: ' . $size);
        header('Cache-Control: public, max-age=86400');
        header('Access-Control-Allow-Origin: *');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            readfile($fallbackPng);
        }
        exit;
    }

    http_response_code(404);
    exit;
}
