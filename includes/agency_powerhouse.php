<?php
declare(strict_types=1);

/**
 * ZEYNART DİJİTAL STÜDYO — AJANS GÜÇ PAKETİ (FAZ 3)
 * ----------------------------------------------------------------------------
 * 1. Blog'dan Tek Tıkla Instagram Kare (1080×1080) & Story (1080×1920) Görsel
 *    + Çoklu Platform (Instagram, LinkedIn, Story/WhatsApp, X) Hazır Metin Üretici
 * 2. 300+ Müşteri Sitesi İçin Ajans Uptime, Yanıt Hızı & SSL Bitiş Günü Kulesi
 * 3. Blog "Makaleyi Sesli Dinle" Podcast Ayarı & Otomatik SEO İç Linkleme Motoru
 * 4. QR Kodlu Kurumsal Dijital Kartvizit (/kartvizit/{isim}), .vcf & IBAN Stüdyosu
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qr_svg.php';

/* ============================================================================
 * 1. BLOG -> INSTAGRAM POST (1080x1080) & STORY (1080x1920) GRAPHIC & COPY STUDIO
 * ============================================================================ */

function zeynart_ap_ttf_width(?string $fontPath, float $ptSize, string $text): int
{
    if ($text === '') {
        return 0;
    }
    if ($fontPath !== null && function_exists('imagettfbbox')) {
        $box = @imagettfbbox($ptSize, 0, $fontPath, $text);
        if (is_array($box)) {
            return (int)abs($box[2] - $box[0]);
        }
    }
    return (int)round(mb_strlen($text, 'UTF-8') * ($ptSize * 0.62));
}

/**
 * Word-wraps UTF-8 Turkish text to fit $maxWidthPx with TrueType measurement.
 *
 * @return array<int, string>
 */
function zeynart_ap_wrap_ttf_lines(?string $fontPath, float $ptSize, string $text, int $maxWidthPx, int $maxLines = 4): array
{
    $clean = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    if ($clean === '') {
        return [];
    }
    $words = preg_split('/\s+/u', $clean) ?: [];
    $lines = [];
    $cur = '';

    foreach ($words as $w) {
        $cand = ($cur === '') ? $w : ($cur . ' ' . $w);
        if (zeynart_ap_ttf_width($fontPath, $ptSize, $cand) <= $maxWidthPx || $cur === '') {
            $cur = $cand;
        } else {
            $lines[] = $cur;
            $cur = $w;
            if (count($lines) >= $maxLines) {
                break;
            }
        }
    }

    if ($cur !== '' && count($lines) < $maxLines) {
        $lines[] = $cur;
    } elseif ($cur !== '' && $lines !== []) {
        $lastIdx = count($lines) - 1;
        $trimmed = rtrim($lines[$lastIdx], " .,;:-");
        while (mb_strlen($trimmed, 'UTF-8') > 12 && zeynart_ap_ttf_width($fontPath, $ptSize, $trimmed . '…') > $maxWidthPx) {
            $sp = mb_strrpos($trimmed, ' ', 0, 'UTF-8');
            $trimmed = ($sp !== false && $sp > 8) ? mb_substr($trimmed, 0, $sp, 'UTF-8') : mb_substr($trimmed, 0, -3, 'UTF-8');
        }
        $lines[$lastIdx] = rtrim($trimmed, " .,;:-") . '…';
    }

    return $lines;
}

function zeynart_ap_load_gd_image(string $localRelPath)
{
    $clean = '/' . ltrim(str_replace('\\', '/', trim(explode('?', $localRelPath, 2)[0])), '/');
    if (str_contains($clean, '..')) {
        return null;
    }
    $abs = ROOT_DIR . $clean;
    if (!is_file($abs)) {
        return null;
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $im = match ($ext) {
        'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($abs) : false,
        'avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($abs) : false,
        'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($abs) : false,
        'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($abs) : false,
        default => false,
    };
    if ($im === false) {
        $bytes = @file_get_contents($abs);
        if (is_string($bytes) && $bytes !== '') {
            $im = @imagecreatefromstring($bytes);
        }
    }
    return ($im !== false) ? $im : null;
}

function zeynart_ap_load_brand_logo(array $store)
{
    $candidates = [
        '/' . ltrim((string)($store['site']['logo'] ?? '/images/zeynart-logo.avif'), '/'),
        '/images/zeynart-logo.avif',
        '/icon.png',
    ];
    foreach ($candidates as $rel) {
        $im = zeynart_ap_load_gd_image($rel);
        if ($im !== null) {
            return $im;
        }
    }
    return null;
}

/**
 * Generates a high-impact 1080x1080 (Instagram Feed / LinkedIn Square) or 1080x1920 (Instagram Story / Reels)
 * branded visual from any blog post or custom studio text.
 *
 * @param array{
 *   format?: string,
 *   template?: string,
 *   title?: string,
 *   eyebrow?: string,
 *   excerpt?: string,
 *   cta_badge?: string,
 *   bg_path?: string,
 *   slug?: string,
 *   output?: string,
 *   save_to_uploads?: bool
 * } $opts
 */
function zeynart_generate_social_media_graphic(array $store, array $opts = []): array
{
    if (!function_exists('imagecreatetruecolor')) {
        return ['ok' => false, 'error' => 'Sunucuda PHP GD görsel motoru aktif değil.'];
    }

    $format = (($opts['format'] ?? 'square') === 'story') ? 'story' : 'square';
    $template = (string)($opts['template'] ?? 'editorial_glass');
    if (!in_array($template, ['editorial_glass', 'full_photo_cinema', 'tech_emerald'], true)) {
        $template = 'editorial_glass';
    }

    $W = 1080;
    $H = ($format === 'story') ? 1920 : 1080;

    $canvas = imagecreatetruecolor($W, $H);
    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);

    $tb = function_exists('zeynart_get_theme_brand') ? zeynart_get_theme_brand($store) : ['primary_color' => '#16b7c5', 'bright_color' => '#5eead4'];
    [$prR, $prG, $prB] = function_exists('zeynart_hex_rgb_tuple')
        ? zeynart_hex_rgb_tuple((string)($tb['primary_color'] ?? '#16b7c5'), [22, 183, 197])
        : [22, 183, 197];
    [$brR, $brG, $brB] = function_exists('zeynart_hex_rgb_tuple')
        ? zeynart_hex_rgb_tuple((string)($tb['bright_color'] ?? '#5eead4'), [94, 234, 212])
        : [94, 234, 212];

    $siteBrand = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
    $siteDom = parse_url((string)($store['seo']['canonical_domain'] ?? SITE_URL), PHP_URL_HOST) ?: 'zeynart.com';
    $siteDom = preg_replace('/^www\./i', '', $siteDom) ?: 'zeynart.com';

    $title = trim((string)($opts['title'] ?? '')) ?: 'Kurumsal Dijital Dönüşüm Rehberi';
    $eyebrow = mb_strtoupper(trim((string)($opts['eyebrow'] ?? 'TEKNOLOJİ & WEB')) ?: 'TEKNOLOJİ & WEB', 'UTF-8');
    $excerpt = trim((string)($opts['excerpt'] ?? ''));
    $defaultCta = ($format === 'story') ? ('Detaylar ' . $siteDom . ' adresinde ↗') : ('Devamı: ' . $siteDom . '/blog');
    $ctaBadge = trim((string)($opts['cta_badge'] ?? '')) ?: $defaultCta;
    $bgRel = trim((string)($opts['bg_path'] ?? ''));

    // Base dark obsidian background
    $bgObsidian = imagecolorallocate($canvas, 5, 13, 18);
    imagefilledrectangle($canvas, 0, 0, $W, $H, $bgObsidian);

    // Resolve cover photo
    $photoIm = ($bgRel !== '') ? zeynart_ap_load_gd_image($bgRel) : null;
    if ($photoIm === null) {
        $fallbacks = ['/images/journal/ai-workflow.webp', '/images/journal/web-planning.webp', '/images/banners/services.webp'];
        foreach ($fallbacks as $fb) {
            $photoIm = zeynart_ap_load_gd_image($fb);
            if ($photoIm !== null) {
                break;
            }
        }
    }

    $fontBold = function_exists('zeynart_gd_font_path') ? zeynart_gd_font_path(true) : null;
    $fontMed = function_exists('zeynart_gd_font_path') ? zeynart_gd_font_path(false) : $fontBold;

    $drawRounded = static function ($im, int $x1, int $y1, int $x2, int $y2, int $r, int $col): void {
        if (function_exists('zeynart_gd_draw_rounded_rect')) {
            zeynart_gd_draw_rounded_rect($im, $x1, $y1, $x2, $y2, $r, $col);
        } else {
            imagefilledrectangle($im, $x1, $y1, $x2, $y2, $col);
        }
    };

    $drawCroppedCover = static function ($dst, $src, int $dx, int $dy, int $dw, int $dh): void {
        $sw = max(1, imagesx($src));
        $sh = max(1, imagesy($src));
        $scale = max($dw / $sw, $dh / $sh);
        $cw = (int)round($dw / $scale);
        $ch = (int)round($dh / $scale);
        $sx = max(0, (int)floor(($sw - $cw) / 2));
        $sy = max(0, (int)floor(($sh - $ch) / 2));
        imagecopyresampled($dst, $src, $dx, $dy, $sx, $sy, $dw, $dh, $cw, $ch);
    };

    // Background & Photo Composition by Template
    if ($template === 'full_photo_cinema') {
        if ($photoIm !== null) {
            $drawCroppedCover($canvas, $photoIm, 0, 0, $W, $H);
        }
        // Top vignette + deep bottom cinema scrim
        $topBand = ($format === 'story') ? 320 : 220;
        for ($y = 0; $y < $topBand; $y++) {
            $a = (int)min(127, round(38 + ($y / $topBand) * 89));
            $c = imagecolorallocatealpha($canvas, 4, 10, 15, $a);
            imageline($canvas, 0, $y, $W, $y, $c);
        }
        $botStart = ($format === 'story') ? 760 : 340;
        for ($y = $botStart; $y < $H; $y++) {
            $ratio = ($y - $botStart) / max(1, ($H - $botStart));
            $a = (int)max(8, min(127, round(125 - ($ratio * 114))));
            $c = imagecolorallocatealpha($canvas, 4, 11, 16, $a);
            imageline($canvas, 0, $y, $W, $y, $c);
        }
    } elseif ($template === 'tech_emerald') {
        // Tech geometric grid + glowing emerald orb + framed photo window
        $gridCol = imagecolorallocatealpha($canvas, $brR, $brG, $brB, 119);
        for ($gx = 40; $gx < $W; $gx += 60) {
            imageline($canvas, $gx, 0, $gx, $H, $gridCol);
        }
        for ($gy = 40; $gy < $H; $gy += 60) {
            imageline($canvas, 0, $gy, $W, $gy, $gridCol);
        }
        for ($r = 520; $r >= 60; $r -= 46) {
            $orb = imagecolorallocatealpha($canvas, $prR, $prG, $prB, 118);
            imagefilledellipse($canvas, 860, ($format === 'story') ? 420 : 240, $r * 2, (int)round($r * 1.4), $orb);
        }
        if ($photoIm !== null) {
            $fx = 72;
            $fy = ($format === 'story') ? 230 : 168;
            $fw = $W - 144;
            $fh = ($format === 'story') ? 640 : 370;
            $frameBorder = imagecolorallocatealpha($canvas, $brR, $brG, $brB, 62);
            $drawRounded($canvas, $fx - 4, $fy - 4, $fx + $fw + 4, $fy + $fh + 4, 24, $frameBorder);
            $drawCroppedCover($canvas, $photoIm, $fx, $fy, $fw, $fh);
            $wash = imagecolorallocatealpha($canvas, 5, 14, 20, 102);
            imagefilledrectangle($canvas, $fx, $fy, $fx + $fw, $fy + $fh, $wash);
        }
    } else {
        // editorial_glass (Default): Upper bleed photo seamlessly melting into dark Apple Glass editorial deck
        $photoH = ($format === 'story') ? 940 : 560;
        if ($photoIm !== null) {
            $drawCroppedCover($canvas, $photoIm, 0, 0, $W, $photoH);
        }
        // Top subtle vignette for brand header legibility
        for ($y = 0; $y < 210; $y++) {
            $a = (int)min(127, round(46 + ($y / 210) * 81));
            $c = imagecolorallocatealpha($canvas, 4, 11, 16, $a);
            imageline($canvas, 0, $y, $W, $y, $c);
        }
        // Smooth dissolve into obsidian editorial stage
        $fadeStart = $photoH - 280;
        for ($y = $fadeStart; $y <= $photoH; $y++) {
            $ratio = ($y - $fadeStart) / 280;
            $a = (int)max(0, min(127, round(127 - ($ratio * 127))));
            $c = imagecolorallocatealpha($canvas, 5, 13, 18, $a);
            imageline($canvas, 0, $y, $W, $y, $c);
        }
        // Subtle turquoise ambient glow behind text deck
        for ($r = 440; $r >= 60; $r -= 44) {
            $orb = imagecolorallocatealpha($canvas, $prR, $prG, $prB, 121);
            imagefilledellipse($canvas, 220, ($format === 'story') ? 1320 : 800, $r * 2, $r, $orb);
        }
    }

    if ($photoIm !== null) {
        unset($photoIm);
    }

    // Top Header Bar: Brand Logo Pill (Left) + Category Pill (Right or Below)
    $padX = 72;
    $topY = ($format === 'story') ? 112 : 58;

    $logoIm = zeynart_ap_load_brand_logo($store);
    $pillBg = imagecolorallocatealpha($canvas, 6, 17, 24, 24);
    $pillStroke = imagecolorallocatealpha($canvas, $brR, $brG, $brB, 58);
    $white = imagecolorallocate($canvas, 248, 252, 255);
    $muted = imagecolorallocate($canvas, 182, 208, 216);
    $accent = imagecolorallocate($canvas, $brR, $brG, $brB);
    $accentSolid = imagecolorallocate($canvas, $prR, $prG, $prB);

    // Top-left logo lockup pill
    $brandPillH = 62;
    $brandPillW = 248;
    $drawRounded($canvas, $padX, $topY, $padX + $brandPillW, $topY + $brandPillH, 31, $pillBg);
    imagerectangle($canvas, $padX, $topY, $padX + $brandPillW, $topY + $brandPillH, $pillStroke);

    if ($logoIm !== null) {
        $lw = max(1, imagesx($logoIm));
        $lh = max(1, imagesy($logoIm));
        $targetH = 34;
        $targetW = min(140, (int)round(($lw / $lh) * $targetH));
        $ly = $topY + (int)floor(($brandPillH - $targetH) / 2);
        imagecopyresampled($canvas, $logoIm, $padX + 22, $ly, 0, 0, $targetW, $targetH, $lw, $lh);
        if ($fontBold !== null) {
            imagettftext($canvas, 13.5, 0, $padX + 34 + $targetW, $topY + 37, $accent, $fontBold, 'JOURNAL');
        }
        unset($logoIm);
    } elseif ($fontBold !== null) {
        imagettftext($canvas, 17, 0, $padX + 26, $topY + 39, $white, $fontBold, $siteBrand . ' JOURNAL');
    }

    // Category badge on top-right
    $catShort = mb_substr($eyebrow, 0, 26, 'UTF-8');
    $catFontPt = 14.5;
    $catW = zeynart_ap_ttf_width($fontBold, $catFontPt, $catShort) + 56;
    $catX1 = $W - $padX - $catW;
    $drawRounded($canvas, $catX1, $topY + 6, $W - $padX, $topY + 56, 25, $pillBg);
    imagefilledellipse($canvas, $catX1 + 24, $topY + 31, 10, 10, $accent);
    if ($fontBold !== null) {
        imagettftext($canvas, $catFontPt, 0, $catX1 + 38, $topY + 37, $white, $fontBold, $catShort);
    }

    // Main Typography Block (Editorial Headline + Excerpt + Footer CTA Bar)
    $maxTextW = $W - ($padX * 2);
    $titleLen = mb_strlen($title, 'UTF-8');
    if ($format === 'story') {
        $titlePt = ($titleLen > 78) ? 40.0 : (($titleLen > 52) ? 46.0 : 52.0);
        $lineGap = (int)round($titlePt * 1.34);
        $titleLines = zeynart_ap_wrap_ttf_lines($fontBold, $titlePt, $title, $maxTextW, 5);
        $excerptLines = ($excerpt !== '') ? zeynart_ap_wrap_ttf_lines($fontMed, 22.0, $excerpt, $maxTextW, 3) : [];
        $textStartY = ($template === 'tech_emerald') ? 960 : 1030;
    } else {
        $titlePt = ($titleLen > 78) ? 33.0 : (($titleLen > 52) ? 38.0 : 43.0);
        $lineGap = (int)round($titlePt * 1.32);
        $titleLines = zeynart_ap_wrap_ttf_lines($fontBold, $titlePt, $title, $maxTextW, 4);
        $excerptLines = ($excerpt !== '' && count($titleLines) <= 3) ? zeynart_ap_wrap_ttf_lines($fontMed, 18.5, $excerpt, $maxTextW, 2) : [];
        $textStartY = ($template === 'tech_emerald') ? 605 : (($template === 'full_photo_cinema') ? 590 : 575);
    }

    // Accent bar above headline
    $drawRounded($canvas, $padX, $textStartY - 34, $padX + 74, $textStartY - 28, 3, $accent);

    $curY = $textStartY + (int)round($titlePt * 0.85);
    foreach ($titleLines as $ln) {
        if ($fontBold !== null) {
            // Crisp subtle drop shadow for maximum contrast
            $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 65);
            imagettftext($canvas, $titlePt, 0, $padX + 2, $curY + 3, $shadow, $fontBold, $ln);
            imagettftext($canvas, $titlePt, 0, $padX, $curY, $white, $fontBold, $ln);
        } else {
            imagestring($canvas, 5, $padX, $curY - 16, $ln, $white);
        }
        $curY += $lineGap;
    }

    if ($excerptLines !== [] && $fontMed !== null) {
        $curY += ($format === 'story') ? 18 : 10;
        $exPt = ($format === 'story') ? 21.5 : 18.0;
        $exGap = (int)round($exPt * 1.48);
        foreach ($excerptLines as $eln) {
            imagettftext($canvas, $exPt, 0, $padX, $curY, $muted, $fontMed, $eln);
            $curY += $exGap;
        }
    }

    // Bottom Frosted-Glass Footer Strip (CTA Pill + Domain Signature)
    $footY1 = ($format === 'story') ? ($H - 210) : ($H - 124);
    $footY2 = $footY1 + (($format === 'story') ? 96 : 72);
    $footBg = imagecolorallocatealpha($canvas, 8, 22, 30, 18);
    $drawRounded($canvas, $padX, $footY1, $W - $padX, $footY2, ($format === 'story') ? 48 : 36, $footBg);
    imagerectangle($canvas, $padX, $footY1, $W - $padX, $footY2, $pillStroke);

    // Emerald CTA pill inside left of footer bar
    $ctaShort = mb_substr($ctaBadge, 0, 42, 'UTF-8');
    $ctaPt = ($format === 'story') ? 18.0 : 15.5;
    $ctaPillW = min($maxTextW - 220, zeynart_ap_ttf_width($fontBold, $ctaPt, $ctaShort) + 52);
    $ctaInPad = ($format === 'story') ? 14 : 11;
    $drawRounded($canvas, $padX + $ctaInPad, $footY1 + $ctaInPad, $padX + $ctaInPad + $ctaPillW, $footY2 - $ctaInPad, 30, $accentSolid);
    $darkInk = imagecolorallocate($canvas, 3, 14, 18);
    if ($fontBold !== null) {
        $ctaTextY = $footY1 + (int)round((($footY2 - $footY1) / 2) + ($ctaPt * 0.36));
        imagettftext($canvas, $ctaPt, 0, $padX + $ctaInPad + 26, $ctaTextY, $white, $fontBold, $ctaShort);

        $domLabel = '• ' . $siteDom;
        $domPt = ($format === 'story') ? 17.5 : 15.0;
        $domW = zeynart_ap_ttf_width($fontBold, $domPt, $domLabel);
        imagettftext($canvas, $domPt, 0, $W - $padX - $domW - 26, $ctaTextY, $accent, $fontBold, $domLabel);
    }

    // Save to /uploads/ or return binary
    $outFormat = strtolower((string)($opts['output'] ?? 'png'));
    if (!in_array($outFormat, ['png', 'jpeg', 'jpg', 'webp'], true)) {
        $outFormat = 'png';
    }

    if (!empty($opts['save_to_uploads'])) {
        $ymd = date('Y') . '/' . date('m') . '/' . date('d');
        $targetDir = UPLOADS_DIR . '/' . $ymd;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $baseSlug = slugify((string)($opts['slug'] ?? $title)) ?: 'sosyal-gorsel';
        $baseSlug = substr($baseSlug, 0, 56);
        $fileName = 'ig-' . $format . '-' . $baseSlug . '.webp';
        $absOut = $targetDir . '/' . $fileName;
        if (function_exists('imagewebp')) {
            @imagewebp($canvas, $absOut, 90);
        } else {
            $fileName = 'ig-' . $format . '-' . $baseSlug . '.png';
            $absOut = $targetDir . '/' . $fileName;
            @imagepng($canvas, $absOut, 6);
        }
        unset($canvas);
        clearstatcache(true, $absOut);
        if (function_exists('zeynart_invalidate_media_cache')) {
            zeynart_invalidate_media_cache();
        }
        $relOut = '/uploads/' . $ymd . '/' . $fileName;
        $mtime = is_file($absOut) ? ((int)filemtime($absOut) ?: time()) : time();
        return [
            'ok' => is_file($absOut),
            'url' => $relOut,
            'mtime' => $mtime,
            'cache_busted_url' => $relOut . '?v=' . $mtime,
            'width' => $W,
            'height' => $H,
            'size' => is_file($absOut) ? (int)filesize($absOut) : 0,
            'format' => $format,
        ];
    }

    ob_start();
    if ($outFormat === 'jpeg' || $outFormat === 'jpg') {
        imagejpeg($canvas, null, 92);
        $mime = 'image/jpeg';
    } elseif ($outFormat === 'webp' && function_exists('imagewebp')) {
        imagewebp($canvas, null, 90);
        $mime = 'image/webp';
    } else {
        imagepng($canvas, null, 6);
        $mime = 'image/png';
    }
    $bytes = (string)ob_get_clean();
    unset($canvas);

    return [
        'ok' => $bytes !== '',
        'bytes' => $bytes,
        'mime' => $mime,
        'width' => $W,
        'height' => $H,
        'format' => $format,
    ];
}

/**
 * Generates ready-to-copy multi-platform social captions (Instagram, LinkedIn, Story/WhatsApp, X)
 * enriched with extracted H2 takeaways and smart category hashtags.
 */
function zeynart_generate_social_captions_pack(array $store, array $postData, string $canonicalUrl, string $lang = 'tr'): array
{
    $siteBrand = trim((string)($store['site']['name'] ?? 'Zeynart')) ?: 'Zeynart';
    $siteDom = parse_url((string)($store['seo']['canonical_domain'] ?? SITE_URL), PHP_URL_HOST) ?: 'zeynart.com';
    $siteDom = preg_replace('/^www\./i', '', $siteDom) ?: 'zeynart.com';

    $title = trim((string)($postData['title'] ?? ''));
    $category = trim((string)($postData['category'] ?? 'TEKNOLOJİ'));
    $excerpt = trim((string)($postData['excerpt'] ?? ($postData['seo']['meta_desc'] ?? '')));
    $html = (string)($postData['content_html'] ?? '');

    if ($excerpt === '' && $html !== '' && function_exists('zeynart_extract_plain_excerpt')) {
        $excerpt = zeynart_extract_plain_excerpt($html, 200);
    }

    // Extract up to 3 H2/H3 takeaways from article body
    $takeaways = [];
    if ($html !== '' && preg_match_all('~<h[23][^>]*>(.*?)</h[23]>~isu', $html, $hm)) {
        foreach ($hm[1] as $rawH) {
            $cleanH = trim(preg_replace('/^\d+[\.\)\-\s]+/u', '', html_entity_decode(strip_tags($rawH), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            if (mb_strlen($cleanH, 'UTF-8') >= 10 && mb_strlen($cleanH, 'UTF-8') <= 95) {
                if (stripos($cleanH, 'sıkça sorulan') === false) {
                    $takeaways[] = $cleanH;
                }
            }
            if (count($takeaways) >= 3) {
                break;
            }
        }
    }
    if ($takeaways === [] && is_array($postData['toc'] ?? null)) {
        foreach (array_slice($postData['toc'], 0, 3) as $tRow) {
            $tt = trim((string)($tRow['title'] ?? ''));
            if ($tt !== '') {
                $takeaways[] = $tt;
            }
        }
    }
    if ($takeaways === []) {
        $takeaways = [
            'Performans, güvenlik ve kullanıcı deneyimi standartları',
            'Kurumsal ölçekte sürdürülebilir dijital mimari adımları',
            'İşletmenize doğrudan hız ve dönüşüm kazandıran pratikler',
        ];
    }

    $catUp = mb_strtoupper($category, 'UTF-8');
    $tags = match (true) {
        str_contains($catUp, 'YAPAY') || str_contains($catUp, 'AI') => ['#YapayZeka', '#TeknolojiTrendleri', '#DijitalDönüşüm', '#WebYazılım', '#Otomasyon', '#YapayZekaÇözümleri'],
        str_contains($catUp, 'TİCARET') => ['#ETicaret', '#OnlineSatış', '#DijitalPazarlama', '#DönüşümOptimizasyonu', '#KurumsalWeb', '#SanalPos'],
        str_contains($catUp, 'SUNUCU') || str_contains($catUp, 'HOST') || str_contains($catUp, 'GÜVENLİK') => ['#WebHosting', '#BulutSunucu', '#SiberGüvenlik', '#SunucuYönetimi', '#KurumsalAltyapı', '#YüksekPerformans'],
        str_contains($catUp, 'SEO') => ['#TeknikSEO', '#AramaMotoruOptimizasyonu', '#DijitalBüyüme', '#OrganikTrafik', '#WebPerformans', '#İçerikStratejisi'],
        default => ['#WebTasarım', '#KurumsalWebSitesi', '#WebYazılım', '#DijitalAjans', '#Teknoloji', '#MarkaDeneyimi'],
    };
    $brandTag = '#' . preg_replace('/[^\p{L}\p{N}]+/u', '', $siteBrand);
    array_unshift($tags, $brandTag);
    $tags = array_values(array_unique($tags));

    $bulletsIg = implode("\n", array_map(static fn(string $b): string => '✦ ' . $b, $takeaways));
    $bulletsIn = implode("\n", array_map(static fn(string $b): string => '• ' . $b, $takeaways));

    $igCaption = "🚀 {$title}\n\n"
        . ($excerpt !== '' ? "{$excerpt}\n\n" : '')
        . "Bu rehberde öne çıkan başlıklar:\n{$bulletsIg}\n\n"
        . "💬 Sizin dijital altyapınızda bu yıl en öncelikli hedefiniz hangisi? Yorumlarda buluşalım!\n\n"
        . "🔗 Tam inceleme profildeki bağlantıda ve {$siteDom}/blog adresinde.\n\n"
        . implode(' ', array_slice($tags, 0, 10));

    $liCaption = "{$title}\n\n"
        . ($excerpt !== '' ? "{$excerpt}\n\n" : '')
        . "{$siteBrand} ekibi olarak son analizimizde kurumsal ekiplerin dikkat etmesi gereken kritik noktaları ele aldık:\n\n"
        . "{$bulletsIn}\n\n"
        . "Makalenin tamamını incelemek için:\n👉 {$canonicalUrl}\n\n"
        . implode(' ', array_slice($tags, 0, 5));

    $storyCaption = "⚡ YENİ REHBER YAYINDA!\n{$title}\n\n🔗 Hemen okumak için tıklayın:\n{$canonicalUrl}";

    $xTags = implode(' ', array_slice($tags, 0, 2));
    $xMaxHeadline = max(60, 230 - mb_strlen($xTags, 'UTF-8'));
    $xText = mb_strlen($title, 'UTF-8') > $xMaxHeadline ? (mb_substr($title, 0, $xMaxHeadline - 1, 'UTF-8') . '…') : $title;
    $xCaption = "📌 {$xText}\n\nDetaylı inceleme 👉 {$canonicalUrl}\n\n{$xTags}";

    return [
        'instagram' => $igCaption,
        'linkedin' => $liCaption,
        'story' => $storyCaption,
        'x_post' => $xCaption,
        'takeaways' => $takeaways,
        'hashtags' => $tags,
    ];
}


/* ============================================================================
 * 2. AGENCY UPTIME & SSL CONTROL TOWER (300+ CLIENT WEBSITES)
 * ============================================================================ */

function zeynart_uptime_monitors_file(): string
{
    return DATA_DIR . '/uptime_monitors.json';
}

function zeynart_get_uptime_tower_config(array &$store): array
{
    $raw = is_array($store['uptime_tower'] ?? null) ? $store['uptime_tower'] : [];
    $secret = trim((string)($raw['cron_secret'] ?? ''));
    if (strlen($secret) < 12) {
        $secret = substr(hash('sha256', ROOT_DIR . '|uptime|' . ($store['site']['name'] ?? 'zeynart')), 0, 24);
    }
    $defEmail = trim((string)($store['smtp']['notify_email'] ?? ($store['site']['email'] ?? 'info@zeynart.com')));
    return [
        'enabled' => ($raw['enabled'] ?? true) !== false,
        'check_interval_minutes' => max(5, min(180, (int)($raw['check_interval_minutes'] ?? 15))),
        'ssl_warn_days' => max(3, min(60, (int)($raw['ssl_warn_days'] ?? 14))),
        'slow_ms_threshold' => max(400, min(8000, (int)($raw['slow_ms_threshold'] ?? 1500))),
        'alert_email_enabled' => !empty($raw['alert_email_enabled']),
        'alert_email' => trim((string)($raw['alert_email'] ?? $defEmail)) ?: $defEmail,
        'cron_secret' => $secret,
    ];
}

function zeynart_normalize_monitor_url(string $rawUrl): ?array
{
    $u = trim($rawUrl);
    if ($u === '') {
        return null;
    }
    if (!preg_match('~^https?://~i', $u)) {
        $u = 'https://' . ltrim($u, '/');
    }
    $parts = @parse_url($u);
    $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
    $host = strtolower(trim((string)($parts['host'] ?? '')));
    if (!in_array($scheme, ['http', 'https'], true) || $host === '' || !str_contains($host, '.')) {
        return null;
    }
    // Block localhost/internal hosts (SSRF shield)
    if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
        return null;
    }
    if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return null;
    }
    $path = (string)($parts['path'] ?? '/');
    if ($path === '') {
        $path = '/';
    }
    $cleanUrl = $scheme . '://' . $host . ($path === '/' ? '/' : $path);
    $displayDomain = preg_replace('/^www\./i', '', $host) ?: $host;
    return [
        'url' => $cleanUrl,
        'host' => $host,
        'domain' => $displayDomain,
        'scheme' => $scheme,
    ];
}

function zeynart_load_uptime_monitors(?array $store = null): array
{
    $file = zeynart_uptime_monitors_file();
    $decoded = zeynart_read_json_data($file);
    if ($decoded === null) {
        $st = $store ?? (function_exists('load_store') ? load_store() : []);
        $canon = trim((string)($st['seo']['canonical_domain'] ?? 'https://zeynart.com'));
        $norm = zeynart_normalize_monitor_url($canon) ?? ['url' => 'https://zeynart.com/', 'host' => 'zeynart.com', 'domain' => 'zeynart.com', 'scheme' => 'https'];
        $seed = [[
            'id' => 'mon-' . substr(md5($norm['domain']), 0, 10),
            'url' => $norm['url'],
            'domain' => $norm['domain'],
            'client_name' => (string)($st['site']['legal_name'] ?? 'Zeynart Ana Kurumsal Site'),
            'server_group' => 'Ana Sunucu',
            'notes' => 'Otomatik başlangıç izleme kaydı',
            'enabled' => true,
            'status' => 'pending',
            'http_code' => 0,
            'response_ms' => 0,
            'server_header' => '',
            'ssl_valid' => null,
            'ssl_days_left' => null,
            'ssl_expires_at' => '',
            'ssl_issuer' => '',
            'ssl_checked_ts' => 0,
            'last_checked_ts' => 0,
            'last_checked_at' => '',
            'last_error' => '',
            'uptime_pct' => 100.0,
            'history' => [],
            'last_alert_sent_ts' => 0,
            'created_at' => date('c'),
        ]];
        zeynart_save_uptime_monitors($seed);
        return $seed;
    }

    if (!is_array($decoded)) {
        return [];
    }
    return array_values(array_filter($decoded, 'is_array'));
}

function zeynart_save_uptime_monitors(array $monitors): bool
{
    return atomic_write_json(zeynart_uptime_monitors_file(), array_values($monitors), true);
}

function zeynart_compute_uptime_summary(array $monitors, array $cfg): array
{
    $sslWarn = max(3, (int)($cfg['ssl_warn_days'] ?? 14));
    $slowMs = max(400, (int)($cfg['slow_ms_threshold'] ?? 1500));

    $total = count($monitors);
    $up = 0;
    $down = 0;
    $pending = 0;
    $sslWarnCnt = 0;
    $slowCnt = 0;
    $msSum = 0;
    $msCnt = 0;
    $pctSum = 0.0;
    $pctCnt = 0;
    $groups = [];

    foreach ($monitors as $m) {
        $grp = trim((string)($m['server_group'] ?? 'Genel')) ?: 'Genel';
        $groups[$grp] = ($groups[$grp] ?? 0) + 1;

        $st = (string)($m['status'] ?? 'pending');
        if ($st === 'up') {
            $up++;
        } elseif ($st === 'down') {
            $down++;
        } else {
            $pending++;
        }

        $ms = (int)($m['response_ms'] ?? 0);
        if ($ms > 0 && $st === 'up') {
            $msSum += $ms;
            $msCnt++;
            if ($ms >= $slowMs) {
                $slowCnt++;
            }
        }

        if ($st !== 'pending') {
            $pctSum += (float)($m['uptime_pct'] ?? 100.0);
            $pctCnt++;
        }

        $daysLeft = $m['ssl_days_left'] ?? null;
        if (($m['ssl_valid'] ?? null) === false || (is_int($daysLeft) && $daysLeft <= $sslWarn)) {
            $sslWarnCnt++;
        }
    }

    return [
        'total' => $total,
        'up_count' => $up,
        'down_count' => $down,
        'pending_count' => $pending,
        'ssl_expiring_count' => $sslWarnCnt,
        'slow_count' => $slowCnt,
        'avg_response_ms' => $msCnt > 0 ? (int)round($msSum / $msCnt) : 0,
        'overall_uptime_pct' => $pctCnt > 0 ? round($pctSum / $pctCnt, 2) : 100.0,
        'groups' => $groups,
    ];
}

/**
 * Inspects TLS/SSL X.509 certificate expiration & issuer for a domain over port 443.
 */
function zeynart_inspect_ssl_certificate(string $host, float $timeoutSec = 3.5): array
{
    if (!function_exists('stream_socket_client') || !function_exists('openssl_x509_parse')) {
        return ['checked' => false, 'ssl_valid' => null, 'ssl_days_left' => null, 'ssl_expires_at' => '', 'ssl_issuer' => ''];
    }
    $resolvedIp = @gethostbyname($host);
    if ($resolvedIp !== $host && filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
        if (!filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['checked' => true, 'ssl_valid' => false, 'ssl_days_left' => 0, 'ssl_expires_at' => '', 'ssl_issuer' => 'Blocked Private IP'];
        }
    }

    $ctx = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'SNI_enabled' => true,
            'peer_name' => $host,
        ],
    ]);
    $errno = 0;
    $errstr = '';
    $client = @stream_socket_client(
        'ssl://' . $host . ':443',
        $errno,
        $errstr,
        $timeoutSec,
        STREAM_CLIENT_CONNECT,
        $ctx
    );
    if (!is_resource($client)) {
        return ['checked' => true, 'ssl_valid' => false, 'ssl_days_left' => 0, 'ssl_expires_at' => '', 'ssl_issuer' => ''];
    }
    $params = @stream_context_get_params($client);
    @fclose($client);

    $certRes = $params['options']['ssl']['peer_certificate'] ?? null;
    if ($certRes === null) {
        return ['checked' => true, 'ssl_valid' => false, 'ssl_days_left' => 0, 'ssl_expires_at' => '', 'ssl_issuer' => ''];
    }
    $parsed = @openssl_x509_parse($certRes);
    if (!is_array($parsed)) {
        return ['checked' => true, 'ssl_valid' => false, 'ssl_days_left' => 0, 'ssl_expires_at' => '', 'ssl_issuer' => ''];
    }

    $validToTs = (int)($parsed['validTo_time_t'] ?? 0);
    $validFromTs = (int)($parsed['validFrom_time_t'] ?? 0);
    $now = time();
    $daysLeft = $validToTs > 0 ? (int)floor(($validToTs - $now) / 86400) : 0;
    $isValid = ($validToTs > $now) && ($validFromTs <= ($now + 3600));
    $issuerOrg = trim((string)($parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? '')));

    return [
        'checked' => true,
        'ssl_valid' => $isValid,
        'ssl_days_left' => $daysLeft,
        'ssl_expires_at' => $validToTs > 0 ? date('Y-m-d', $validToTs) : '',
        'ssl_issuer' => mb_substr($issuerOrg, 0, 48, 'UTF-8'),
    ];
}

/**
 * Probes a batch of client monitors in parallel using curl_multi (high-throughput for 300+ sites!)
 * plus cached/on-demand X.509 SSL expiry inspection.
 *
 * @param array<int, string> $targetIds Empty array = due monitors up to $maxCount
 */
function zeynart_probe_uptime_batch(array &$monitors, array $targetIds, array $store, int $maxCount = 25, bool $forceSsl = false): array
{
    $cfg = zeynart_get_uptime_tower_config($store);
    $idMap = [];
    foreach ($monitors as $idx => $m) {
        $id = (string)($m['id'] ?? '');
        if ($id !== '') {
            $idMap[$id] = $idx;
        }
    }

    $selectedIndexes = [];
    if ($targetIds !== []) {
        foreach ($targetIds as $tid) {
            if (isset($idMap[$tid])) {
                $selectedIndexes[] = $idMap[$tid];
            }
        }
    } else {
        // Pick enabled monitors sorted by oldest last_checked_ts first
        $candidates = [];
        $minAgeSec = max(180, ((int)$cfg['check_interval_minutes']) * 60);
        $now = time();
        foreach ($monitors as $idx => $m) {
            if (($m['enabled'] ?? true) === false) {
                continue;
            }
            $lastTs = (int)($m['last_checked_ts'] ?? 0);
            if (($now - $lastTs) >= $minAgeSec) {
                $candidates[] = ['idx' => $idx, 'ts' => $lastTs];
            }
        }
        usort($candidates, static fn(array $a, array $b): int => $a['ts'] <=> $b['ts']);
        foreach (array_slice($candidates, 0, max(1, $maxCount)) as $c) {
            $selectedIndexes[] = $c['idx'];
        }
    }

    if ($selectedIndexes === []) {
        return ['checked_count' => 0, 'checked_ids' => [], 'alerts_sent' => 0];
    }

    $selectedIndexes = array_slice(array_values(array_unique($selectedIndexes)), 0, max(1, $maxCount));
    $ua = 'Mozilla/5.0 (compatible; ZeynartUptimeTower/2.0; +https://zeynart.com)';
    $results = [];

    // 1. Parallel HTTP probe via curl_multi when available
    if (function_exists('curl_multi_init')) {
        $mh = curl_multi_init();
        $handles = [];
        $headersMap = [];

        foreach ($selectedIndexes as $idx) {
            $url = (string)($monitors[$idx]['url'] ?? '');
            $norm = zeynart_normalize_monitor_url($url);
            if ($norm === null) {
                $results[$idx] = ['ok' => false, 'code' => 0, 'ms' => 0, 'server' => '', 'error' => 'Geçersiz domain adresi'];
                continue;
            }
            $resolvedIp = @gethostbyname($norm['host']);
            if ($resolvedIp !== $norm['host'] && filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
                if (!filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $results[$idx] = ['ok' => false, 'code' => 0, 'ms' => 0, 'server' => '', 'error' => 'Özel ağ IP engellendi'];
                    continue;
                }
            }

            $headersMap[$idx] = '';
            $ch = curl_init($norm['url']);
            if ($ch === false) {
                continue;
            }
            $bytesRead = 0;
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 4,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 7,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: ' . $ua,
                    'Accept: text/html,application/xhtml+xml,*/*;q=0.8',
                    'Cache-Control: no-cache',
                ],
                CURLOPT_HEADERFUNCTION => static function ($chObj, string $hLine) use (&$headersMap, $idx): int {
                    if (stripos($hLine, 'server:') === 0) {
                        $headersMap[$idx] = trim(substr($hLine, 7));
                    }
                    return strlen($hLine);
                },
                CURLOPT_WRITEFUNCTION => static function ($chObj, string $chunk) use (&$bytesRead): int {
                    $bytesRead += strlen($chunk);
                    // Stop reading body after first 8 KB to keep 300-site checks ultra-lightweight!
                    return ($bytesRead > 8192) ? 0 : strlen($chunk);
                },
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$idx] = ['ch' => $ch, 'start' => microtime(true)];
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
            if ($active > 0) {
                curl_multi_select($mh, 0.25);
            }
        } while ($active > 0 && $mrc === CURLM_OK);

        foreach ($handles as $idx => $hInfo) {
            $ch = $hInfo['ch'];
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $totalSec = (float)curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $ms = $totalSec > 0 ? (int)round($totalSec * 1000) : (int)round((microtime(true) - $hInfo['start']) * 1000);
            $err = curl_error($ch);
            $errno = curl_errno($ch);
            curl_multi_remove_handle($mh, $ch);
            unset($ch);

            // Note: CURLE_WRITE_ERROR (23) happens intentionally when we abort body after 8KB — if $code >= 200 && $code < 400 it is UP!
            $isUp = ($code >= 200 && $code < 400) || ($code === 403 && strtolower($headersMap[$idx] ?? '') === 'cloudflare');
            $cleanErr = (!$isUp) ? ($code > 0 ? ('HTTP ' . $code) : ($errno === 28 ? 'Zaman aşımı (Timeout)' : ($err ?: 'Bağlantı kurulamadı'))) : '';
            $results[$idx] = [
                'ok' => $isUp,
                'code' => $code,
                'ms' => max(1, $ms),
                'server' => mb_substr((string)($headersMap[$idx] ?? ''), 0, 40, 'UTF-8'),
                'error' => $cleanErr,
            ];
        }
        curl_multi_close($mh);
    } else {
        // Fallback stream probe
        foreach ($selectedIndexes as $idx) {
            $norm = zeynart_normalize_monitor_url((string)($monitors[$idx]['url'] ?? ''));
            if ($norm === null) {
                $results[$idx] = ['ok' => false, 'code' => 0, 'ms' => 0, 'server' => '', 'error' => 'Geçersiz domain'];
                continue;
            }
            $t0 = microtime(true);
            $ctx = stream_context_create([
                'http' => ['method' => 'GET', 'timeout' => 6, 'ignore_errors' => true, 'header' => "User-Agent: {$ua}\r\n"],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $http_response_header = [];
            @file_get_contents($norm['url'], false, $ctx, 0, 4096);
            $ms = max(1, (int)round((microtime(true) - $t0) * 1000));
            $code = 0;
            $srv = '';
            foreach (($http_response_header ?? []) as $hl) {
                if (preg_match('~^HTTP/\S+\s+(\d{3})~i', $hl, $m)) {
                    $code = (int)$m[1];
                } elseif (stripos($hl, 'Server:') === 0) {
                    $srv = trim(substr($hl, 7));
                }
            }
            $isUp = ($code >= 200 && $code < 400);
            $results[$idx] = [
                'ok' => $isUp,
                'code' => $code,
                'ms' => $ms,
                'server' => mb_substr($srv, 0, 40, 'UTF-8'),
                'error' => $isUp ? '' : ($code > 0 ? ('HTTP ' . $code) : 'Yanıt alınamadı'),
            ];
        }
    }

    // 2. Update monitor telemetry + SSL inspection + optional alert dispatch
    $now = time();
    $nowIso = date('c');
    $checkedIds = [];
    $alertsSent = 0;

    foreach ($selectedIndexes as $idx) {
        if (!isset($results[$idx])) {
            continue;
        }
        $r = $results[$idx];
        $m = &$monitors[$idx];
        $norm = zeynart_normalize_monitor_url((string)($m['url'] ?? ''));

        $m['status'] = $r['ok'] ? 'up' : 'down';
        $m['http_code'] = (int)$r['code'];
        $m['response_ms'] = (int)$r['ms'];
        if ($r['server'] !== '') {
            $m['server_header'] = $r['server'];
        }
        $m['last_error'] = (string)$r['error'];
        $m['last_checked_ts'] = $now;
        $m['last_checked_at'] = $nowIso;

        // Rolling 12-point history & uptime percentage
        $hist = is_array($m['history'] ?? null) ? $m['history'] : [];
        $hist[] = [
            'ok' => (bool)$r['ok'],
            'ms' => (int)$r['ms'],
            'code' => (int)$r['code'],
            'ts' => $now,
        ];
        if (count($hist) > 16) {
            $hist = array_slice($hist, -16);
        }
        $m['history'] = $hist;
        $okTicks = count(array_filter($hist, static fn(array $h): bool => !empty($h['ok'])));
        $m['uptime_pct'] = count($hist) > 0 ? round(($okTicks / count($hist)) * 100, 1) : 100.0;

        // SSL check (re-inspect if never checked, forced, invalid, <= 15 days left, or older than 6 hours)
        $sslAge = $now - (int)($m['ssl_checked_ts'] ?? 0);
        $prevDays = $m['ssl_days_left'] ?? null;
        $needsSsl = $forceSsl
            || ($m['ssl_checked_ts'] ?? 0) === 0
            || ($m['ssl_valid'] ?? null) === false
            || (is_int($prevDays) && $prevDays <= 15)
            || ($sslAge >= 21600);

        if ($needsSsl && $norm !== null) {
            $sslInfo = zeynart_inspect_ssl_certificate($norm['host'], 3.2);
            if (!empty($sslInfo['checked'])) {
                $m['ssl_valid'] = $sslInfo['ssl_valid'];
                $m['ssl_days_left'] = $sslInfo['ssl_days_left'];
                $m['ssl_expires_at'] = $sslInfo['ssl_expires_at'];
                if ($sslInfo['ssl_issuer'] !== '') {
                    $m['ssl_issuer'] = $sslInfo['ssl_issuer'];
                }
                $m['ssl_checked_ts'] = $now;
            }
        }

        // Throttled SMTP alert (once per 24h per domain)
        $needsAlert = (!$r['ok']) || (($m['ssl_valid'] ?? true) === false) || (is_int($m['ssl_days_left'] ?? null) && $m['ssl_days_left'] <= 7);
        if ($needsAlert && !empty($cfg['alert_email_enabled']) && !empty($store['smtp']['enabled']) && ($now - (int)($m['last_alert_sent_ts'] ?? 0)) > 86400) {
            if (is_file(ROOT_DIR . '/lib/smtp.php')) {
                require_once ROOT_DIR . '/lib/smtp.php';
                if (function_exists('send_smtp_mail')) {
                    $subj = (!$r['ok'])
                        ? ('[UPTIME ALARMI] ' . ($m['domain'] ?? '') . ' erişilemiyor!')
                        : ('[SSL UYARISI] ' . ($m['domain'] ?? '') . ' SSL süresi azaldı (' . (int)($m['ssl_days_left'] ?? 0) . ' gün)');
                    $bodyHtml = '<div style="font-family:Arial,sans-serif;padding:20px;background:#071319;color:#e6f4f6;border-radius:12px">'
                        . '<h2 style="color:#5eead4;margin-top:0">' . htmlspecialchars($subj, ENT_QUOTES, 'UTF-8') . '</h2>'
                        . '<p><strong>Domain:</strong> ' . htmlspecialchars((string)($m['url'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
                        . '<p><strong>Müşteri / Grup:</strong> ' . htmlspecialchars((string)($m['client_name'] ?? '-') . ' / ' . (string)($m['server_group'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</p>'
                        . '<p><strong>HTTP Durumu:</strong> ' . (int)$m['http_code'] . ' (' . htmlspecialchars((string)$m['last_error'], ENT_QUOTES, 'UTF-8') . ') · ' . (int)$m['response_ms'] . ' ms</p>'
                        . '<p><strong>SSL Kalan Süre:</strong> ' . ($m['ssl_days_left'] !== null ? ((int)$m['ssl_days_left'] . ' gün (' . htmlspecialchars((string)$m['ssl_expires_at'], ENT_QUOTES, 'UTF-8') . ')') : 'Bilinmiyor') . '</p>'
                        . '</div>';
                    $sent = @send_smtp_mail($store['smtp'], (string)$cfg['alert_email'], $subj, $bodyHtml);
                    if (!empty($sent['ok'])) {
                        $m['last_alert_sent_ts'] = $now;
                        $alertsSent++;
                    }
                }
            }
        }

        $checkedIds[] = (string)($m['id'] ?? '');
        unset($m);
    }

    zeynart_save_uptime_monitors($monitors);

    return [
        'checked_count' => count($checkedIds),
        'checked_ids' => $checkedIds,
        'alerts_sent' => $alertsSent,
    ];
}

/**
 * Bulk-imports up to 500 domain lines ("domain.com | Müşteri Adı | Sunucu") into $monitors.
 */
function zeynart_bulk_import_uptime_monitors(array &$monitors, string $rawLines, string $defaultGroup = 'Plesk / Müşteri'): array
{
    $existingDomains = [];
    foreach ($monitors as $m) {
        $d = strtolower((string)($m['domain'] ?? ''));
        if ($d !== '') {
            $existingDomains[$d] = true;
        }
    }

    $lines = preg_split('/\r\n|\r|\n/', $rawLines) ?: [];
    $added = 0;
    $skippedDuplicate = 0;
    $invalid = 0;
    $addedIds = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        // Support pipe "|", semicolon ";", tab "\t", or comma "," delimiters
        $parts = preg_split('/\s*[\|;\t,]\s*/u', $trimmed) ?: [$trimmed];
        $rawDom = trim((string)($parts[0] ?? ''));
        $clientName = trim((string)($parts[1] ?? ''));
        $groupName = trim((string)($parts[2] ?? '')) ?: $defaultGroup;

        $norm = zeynart_normalize_monitor_url($rawDom);
        if ($norm === null) {
            $invalid++;
            continue;
        }
        if (isset($existingDomains[$norm['domain']])) {
            $skippedDuplicate++;
            continue;
        }
        $existingDomains[$norm['domain']] = true;
        $id = 'mon-' . substr(md5($norm['domain'] . '|' . microtime(true) . '|' . $added), 0, 10);
        $monitors[] = [
            'id' => $id,
            'url' => $norm['url'],
            'domain' => $norm['domain'],
            'client_name' => mb_substr($clientName !== '' ? $clientName : $norm['domain'], 0, 80, 'UTF-8'),
            'server_group' => mb_substr($groupName, 0, 48, 'UTF-8'),
            'notes' => '',
            'enabled' => true,
            'status' => 'pending',
            'http_code' => 0,
            'response_ms' => 0,
            'server_header' => '',
            'ssl_valid' => null,
            'ssl_days_left' => null,
            'ssl_expires_at' => '',
            'ssl_issuer' => '',
            'ssl_checked_ts' => 0,
            'last_checked_ts' => 0,
            'last_checked_at' => '',
            'last_error' => '',
            'uptime_pct' => 100.0,
            'history' => [],
            'last_alert_sent_ts' => 0,
            'created_at' => date('c'),
        ];
        $added++;
        $addedIds[] = $id;
        if ($added >= 500) {
            break;
        }
    }

    if ($added > 0) {
        zeynart_save_uptime_monitors($monitors);
    }

    return [
        'added' => $added,
        'skipped_duplicate' => $skippedDuplicate,
        'invalid' => $invalid,
        'added_ids' => $addedIds,
    ];
}


/* ============================================================================
 * 3. BLOG AUDIO PODCAST CONFIG & AUTOMATIC SEO INTERNAL LINKING ENGINE
 * ============================================================================ */

function zeynart_default_interlink_rules(array $store): array
{
    $srvSlug = function_exists('zeynart_page_slug') ? zeynart_page_slug($store, 'hizmetler') : 'hizmetler';
    return [
        ['keyword' => 'kurumsal web tasarım', 'url' => "/{$srvSlug}/web-tasarim/", 'title' => 'Kurumsal Web Tasarım Hizmetimiz'],
        ['keyword' => 'web tasarım', 'url' => "/{$srvSlug}/web-tasarim/", 'title' => 'Profesyonel Web Tasarım Çözümleri'],
        ['keyword' => 'özel web yazılım', 'url' => "/{$srvSlug}/web-yazilim/", 'title' => 'Özel Web Yazılım & CRM Geliştirme'],
        ['keyword' => 'web yazılım', 'url' => "/{$srvSlug}/web-yazilim/", 'title' => 'Kurumsal Web Yazılım Çözümleri'],
        ['keyword' => 'e-ticaret altyapısı', 'url' => "/{$srvSlug}/e-ticaret/", 'title' => 'Yüksek Dönüşümlü E-Ticaret Çözümleri'],
        ['keyword' => 'e-ticaret sitesi', 'url' => "/{$srvSlug}/e-ticaret/", 'title' => 'Kurumsal E-Ticaret Sistemleri'],
        ['keyword' => 'mobil uygulama geliştirme', 'url' => "/{$srvSlug}/mobil-uygulama/", 'title' => 'iOS & Android Mobil Uygulama Geliştirme'],
        ['keyword' => 'mobil uygulama', 'url' => "/{$srvSlug}/mobil-uygulama/", 'title' => 'Kurumsal Mobil Uygulama Çözümleri'],
        ['keyword' => 'web hosting', 'url' => "/{$srvSlug}/hosting/", 'title' => 'Yüksek Hızlı Kurumsal Hosting & Sunucu'],
        ['keyword' => 'bulut sunucu', 'url' => "/{$srvSlug}/hosting/", 'title' => 'Yönetilen Bulut Sunucu Altyapısı'],
        ['keyword' => 'kurumsal kimlik', 'url' => "/{$srvSlug}/grafik-tasarim/", 'title' => 'Kurumsal Kimlik & Marka Tasarımı'],
        ['keyword' => 'grafik tasarım', 'url' => "/{$srvSlug}/grafik-tasarim/", 'title' => 'Kreatif Grafik Tasarım Stüdyosu'],
        ['keyword' => 'ücretsiz seo analizi', 'url' => '/site-analiz/', 'title' => 'Ücretsiz Site Hızı & SEO Ön Analiz Aracı'],
        ['keyword' => 'site hızı testi', 'url' => '/site-analiz/', 'title' => 'Canlı Site Hızı & Performans Testi'],
        ['keyword' => 'fiyat teklifi', 'url' => '/teklif-sihirbazi/', 'title' => 'Adım Adım Proje Teklif & Bütçe Sihirbazı'],
    ];
}

function zeynart_get_auto_interlink_config(array $store): array
{
    $bs = is_array($store['blog_settings'] ?? null) ? $store['blog_settings'] : [];
    $raw = is_array($store['seo_interlinks'] ?? null) ? $store['seo_interlinks'] : [];

    $rules = [];
    if (array_key_exists('rules', $raw) && is_array($raw['rules'])) {
        foreach ($raw['rules'] as $r) {
            if (!is_array($r)) {
                continue;
            }
            $kw = trim((string)($r['keyword'] ?? ''));
            $u = trim((string)($r['url'] ?? ''));
            if ($kw !== '' && $u !== '') {
                $rules[] = [
                    'keyword' => mb_substr($kw, 0, 80, 'UTF-8'),
                    'url' => mb_substr($u, 0, 180, 'UTF-8'),
                    'title' => mb_substr(trim((string)($r['title'] ?? $kw)), 0, 110, 'UTF-8'),
                ];
            }
        }
    } else {
        $rules = zeynart_default_interlink_rules($store);
    }

    return [
        'enabled' => ($raw['enabled'] ?? true) !== false,
        'max_links_per_post' => max(1, min(10, (int)($raw['max_links_per_post'] ?? 4))),
        'max_per_target' => max(1, min(3, (int)($raw['max_per_target'] ?? 1))),
        'open_in_new_tab' => !empty($raw['open_in_new_tab']),
        'audio_player_enabled' => ($bs['audio_player_enabled'] ?? ($raw['audio_player_enabled'] ?? true)) !== false,
        'rules' => $rules,
    ];
}

/**
 * Builds a Turkish-aware case-insensitive regex pattern for a keyword (handles İ/i and I/ı properly).
 */
function zeynart_tr_ci_pattern(string $keyword): string
{
    $chars = preg_split('//u', $keyword, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $out = '';
    foreach ($chars as $ch) {
        if ($ch === 'i' || $ch === 'İ') {
            $out .= '(?:i|İ)';
        } elseif ($ch === 'ı' || $ch === 'I') {
            $out .= '(?:ı|I)';
        } elseif (preg_match('/\s/u', $ch)) {
            $out .= '\s+';
        } else {
            $lo = mb_strtolower($ch, 'UTF-8');
            $up = mb_strtoupper($ch, 'UTF-8');
            if ($lo !== $up) {
                $out .= '(?:' . preg_quote($lo, '~') . '|' . preg_quote($up, '~') . ')';
            } else {
                $out .= preg_quote($ch, '~');
            }
        }
    }
    return '~(?<![\p{L}\p{N}_\-/])(' . $out . ')(?![\p{L}\p{N}_\-])~u';
}

/**
 * Safely injects contextual SEO internal links into blog prose HTML without ever touching
 * existing <a>, <h1..h6>, <code>, <pre>, <script>, <style>, <button>, or HTML tag attributes!
 */
function zeynart_apply_auto_internal_links(string $html, array $store, string $currentPath = '', string $lang = 'tr'): string
{
    if (trim($html) === '' || $lang !== 'tr') {
        // Apply Turkish keyword rules on Turkish prose so translated articles keep clean grammar
        if ($lang !== 'tr') {
            return $html;
        }
    }
    $cfg = zeynart_get_auto_interlink_config($store);
    if (empty($cfg['enabled']) || empty($cfg['rules'])) {
        return $html;
    }

    // Sort longest keywords first so "kurumsal web tasarım" matches before "web tasarım"
    $rules = $cfg['rules'];
    usort($rules, static fn(array $a, array $b): int => mb_strlen($b['keyword'], 'UTF-8') <=> mb_strlen($a['keyword'], 'UTF-8'));

    $compiledRules = [];
    $curNorm = '/' . trim($currentPath, '/') . '/';
    foreach ($rules as $r) {
        $rawTarget = trim((string)($r['url'] ?? ''));
        if ($rawTarget === '') {
            continue;
        }
        if (function_exists('zeynart_is_internal_url_hidden') && zeynart_is_internal_url_hidden($store, $rawTarget)) {
            continue;
        }
        $targetNorm = '/' . trim((string)parse_url($rawTarget, PHP_URL_PATH), '/') . '/';
        if ($targetNorm === $curNorm) {
            continue;
        }
        $resolvedHref = str_starts_with($rawTarget, 'http')
            ? $rawTarget
            : (function_exists('lang_url') ? lang_url($rawTarget, $lang) : $rawTarget);

        $compiledRules[] = [
            'pattern' => zeynart_tr_ci_pattern($r['keyword']),
            'href' => $resolvedHref,
            'target_key' => strtolower($targetNorm),
            'title' => (string)($r['title'] ?? $r['keyword']),
        ];
    }

    if ($compiledRules === []) {
        return $html;
    }

    $maxTotal = (int)$cfg['max_links_per_post'];
    $maxPerTarget = (int)$cfg['max_per_target'];
    $newTabAttr = !empty($cfg['open_in_new_tab']) ? ' target="_blank" rel="noopener"' : '';

    $totalLinked = 0;
    $targetCounts = [];

    // Tokenize HTML into tags vs plain text nodes and track protected container depth
    $tokens = preg_split('~(<[^>]+>)~u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($tokens)) {
        return $html;
    }

    $protectedDepth = 0;
    $protectedTags = ['a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'code', 'pre', 'script', 'style', 'button', 'figcaption', 'summary'];

    foreach ($tokens as $idx => $tok) {
        if ($tok === '') {
            continue;
        }
        if ($tok[0] === '<') {
            if (preg_match('~^<\s*(/)?\s*([a-zA-Z0-9]+)~', $tok, $tm)) {
                $isClose = ($tm[1] === '/');
                $tagName = strtolower($tm[2]);
                $isSelfClosing = str_ends_with(rtrim($tok), '/>');
                if (in_array($tagName, $protectedTags, true) && !$isSelfClosing) {
                    if ($isClose) {
                        $protectedDepth = max(0, $protectedDepth - 1);
                    } else {
                        $protectedDepth++;
                    }
                }
            }
            continue;
        }

        // Plain text node outside protected tags
        if ($protectedDepth > 0 || $totalLinked >= $maxTotal || mb_strlen(trim($tok), 'UTF-8') < 4) {
            continue;
        }

        // Use placeholders inside this text node so a newly inserted <a> tag isn't re-scanned by shorter keywords!
        $placeholders = [];
        $working = $tok;

        foreach ($compiledRules as $rule) {
            if ($totalLinked >= $maxTotal) {
                break;
            }
            $tKey = $rule['target_key'];
            if (($targetCounts[$tKey] ?? 0) >= $maxPerTarget) {
                continue;
            }

            $matchedOnce = false;
            $working = preg_replace_callback(
                $rule['pattern'],
                static function (array $m) use ($rule, $newTabAttr, &$placeholders, &$matchedOnce): string {
                    $matchedOnce = true;
                    $ph = "\x02ZLINK_" . count($placeholders) . "\x03";
                    $anchor = '<a href="' . htmlspecialchars($rule['href'], ENT_QUOTES, 'UTF-8') . '"'
                        . ' class="journal-auto-link"'
                        . ' title="' . htmlspecialchars($rule['title'], ENT_QUOTES, 'UTF-8') . '"'
                        . $newTabAttr . '>' . $m[1] . '</a>';
                    $placeholders[$ph] = $anchor;
                    return $ph;
                },
                $working,
                1
            ) ?? $working;

            if ($matchedOnce) {
                $totalLinked++;
                $targetCounts[$tKey] = ($targetCounts[$tKey] ?? 0) + 1;
            }
        }

        if ($placeholders !== []) {
            $tokens[$idx] = strtr($working, $placeholders);
        }
    }

    return implode('', $tokens);
}


/* ============================================================================
 * 4. CORPORATE QR DIGITAL BUSINESS CARD (/kartvizit/{isim}), .VCF & IBAN STUDIO
 * ============================================================================ */

function zeynart_default_vcards(array $store): array
{
    $site = is_array($store['site'] ?? null) ? $store['site'] : [];
    $phoneDisp = trim((string)($site['phone'] ?? '0850 305 0 533'));
    $phoneHref = trim((string)($site['phone_href'] ?? '+908503050533'));
    $waDigits = preg_replace('/\D+/', '', (string)($store['quick_contact']['whatsapp_number'] ?? $phoneHref)) ?: '908503050533';
    $email = trim((string)($site['email'] ?? 'info@zeynart.com'));
    $addr = trim((string)($site['address'] ?? 'Maltepe Mh. 21078 Sk. No:6/A'));
    $city = trim((string)($site['city'] ?? 'İzmir'));
    $canon = rtrim((string)($store['seo']['canonical_domain'] ?? 'https://zeynart.com'), '/');

    return [[
        'id' => 'vc-zeynart-main',
        'slug' => 'zeynart',
        'status' => 'published',
        'full_name' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim'),
        'job_title' => 'Tasarım, Yazılım & Sunucu Stüdyosu',
        'company' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
        'bio' => (string)($site['tagline'] ?? 'Kurumsal web tasarım, özel yazılım, mobil uygulama ve yüksek hızlı sunucu altyapısı.'),
        'avatar' => (string)($site['favicon'] ?? '/icon.png'),
        'phone' => $phoneDisp,
        'phone_raw' => $phoneHref,
        'whatsapp' => $waDigits,
        'whatsapp_greeting' => 'Merhaba, dijital kartvizitiniz üzerinden ulaşıyorum.',
        'email' => $email,
        'website' => $canon,
        'catalog_label' => 'Hizmetler & Teklif Sihirbazı',
        'catalog_url' => '/teklif-sihirbazi/',
        'address' => trim($addr . ($city !== '' ? (', ' . $city) : '')),
        'maps_url' => 'https://maps.google.com/?q=' . rawurlencode(trim($addr . ' ' . $city)),
        'linkedin' => '',
        'instagram' => 'https://instagram.com/zeynartweb',
        'show_billing' => false,
        'tax_title' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
        'tax_office' => '',
        'tax_number' => '',
        'ibans' => [],
        'noindex' => false,
    ]];
}

function zeynart_get_vcards_list(array $store): array
{
    $raw = $store['vcards'] ?? null;
    if (!is_array($raw)) {
        return zeynart_default_vcards($store);
    }
    $out = [];
    $seenSlugs = [];
    foreach ($raw as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = trim((string)($row['full_name'] ?? ''));
        $slug = slugify((string)($row['slug'] ?? $name));
        if ($slug === '' || isset($seenSlugs[$slug])) {
            continue;
        }
        $seenSlugs[$slug] = true;

        $ibans = [];
        if (is_array($row['ibans'] ?? null)) {
            foreach ($row['ibans'] as $ib) {
                if (!is_array($ib)) {
                    continue;
                }
                $ibanStr = strtoupper(trim((string)($ib['iban'] ?? '')));
                if ($ibanStr !== '') {
                    $ibans[] = [
                        'bank' => mb_substr(trim((string)($ib['bank'] ?? 'Banka Hesabı')), 0, 60, 'UTF-8'),
                        'currency' => mb_substr(strtoupper(trim((string)($ib['currency'] ?? 'TL'))), 0, 8, 'UTF-8') ?: 'TL',
                        'iban' => mb_substr($ibanStr, 0, 42, 'UTF-8'),
                        'holder' => mb_substr(trim((string)($ib['holder'] ?? '')), 0, 90, 'UTF-8'),
                    ];
                }
            }
        }

        $phoneDisp = trim((string)($row['phone'] ?? ''));
        $phoneRaw = trim((string)($row['phone_raw'] ?? ''));
        if ($phoneRaw === '' && $phoneDisp !== '') {
            $digits = preg_replace('/\D+/', '', $phoneDisp) ?: '';
            if (str_starts_with($digits, '0')) {
                $digits = '90' . substr($digits, 1);
            }
            $phoneRaw = $digits !== '' ? ('+' . ltrim($digits, '+')) : '';
        }

        $waRaw = preg_replace('/\D+/', '', (string)($row['whatsapp'] ?? '')) ?: '';
        if (str_starts_with($waRaw, '0')) {
            $waRaw = '90' . substr($waRaw, 1);
        }

        $out[] = [
            'id' => trim((string)($row['id'] ?? '')) ?: ('vc-' . ($idx + 1)),
            'slug' => $slug,
            'status' => in_array((string)($row['status'] ?? 'published'), ['published', 'draft'], true) ? (string)$row['status'] : 'published',
            'full_name' => mb_substr($name !== '' ? $name : $slug, 0, 90, 'UTF-8'),
            'job_title' => mb_substr(trim((string)($row['job_title'] ?? '')), 0, 90, 'UTF-8'),
            'company' => mb_substr(trim((string)($row['company'] ?? ($store['site']['legal_name'] ?? 'Zeynart Web Bilişim'))), 0, 100, 'UTF-8'),
            'bio' => mb_substr(trim((string)($row['bio'] ?? '')), 0, 260, 'UTF-8'),
            'avatar' => trim((string)($row['avatar'] ?? '/icon.png')) ?: '/icon.png',
            'phone' => mb_substr($phoneDisp, 0, 40, 'UTF-8'),
            'phone_raw' => mb_substr($phoneRaw, 0, 32, 'UTF-8'),
            'whatsapp' => mb_substr($waRaw, 0, 24, 'UTF-8'),
            'whatsapp_greeting' => mb_substr(trim((string)($row['whatsapp_greeting'] ?? 'Merhaba, dijital kartvizitiniz üzerinden ulaşıyorum.')), 0, 160, 'UTF-8'),
            'email' => mb_substr(trim((string)($row['email'] ?? '')), 0, 90, 'UTF-8'),
            'website' => mb_substr(trim((string)($row['website'] ?? '')), 0, 140, 'UTF-8'),
            'catalog_label' => mb_substr(trim((string)($row['catalog_label'] ?? 'Hizmetler & Teklif Al')), 0, 60, 'UTF-8'),
            'catalog_url' => mb_substr(trim((string)($row['catalog_url'] ?? '/teklif-sihirbazi/')), 0, 140, 'UTF-8'),
            'address' => mb_substr(trim((string)($row['address'] ?? '')), 0, 180, 'UTF-8'),
            'maps_url' => mb_substr(trim((string)($row['maps_url'] ?? '')), 0, 240, 'UTF-8'),
            'linkedin' => mb_substr(trim((string)($row['linkedin'] ?? '')), 0, 160, 'UTF-8'),
            'instagram' => mb_substr(trim((string)($row['instagram'] ?? '')), 0, 160, 'UTF-8'),
            'show_billing' => !empty($row['show_billing']),
            'tax_title' => mb_substr(trim((string)($row['tax_title'] ?? '')), 0, 120, 'UTF-8'),
            'tax_office' => mb_substr(trim((string)($row['tax_office'] ?? '')), 0, 80, 'UTF-8'),
            'tax_number' => mb_substr(trim((string)($row['tax_number'] ?? '')), 0, 30, 'UTF-8'),
            'ibans' => $ibans,
            'noindex' => !empty($row['noindex']),
        ];
    }

    return $out !== [] ? $out : zeynart_default_vcards($store);
}

function zeynart_find_vcard(array $store, string $slug, bool $includeDrafts = false): ?array
{
    $clean = strtolower(trim($slug));
    if ($clean === '') {
        return null;
    }
    foreach (zeynart_get_vcards_list($store) as $vc) {
        if (strtolower((string)$vc['slug']) === $clean) {
            if (($vc['status'] ?? 'published') === 'published' || $includeDrafts) {
                return $vc;
            }
        }
    }
    return null;
}

/**
 * Compiles an iPhone & Android compatible UTF-8 vCard 3.0 (.vcf) contact file.
 */
function zeynart_compile_vcard_vcf(array $vc, array $store): string
{
    $escVcf = static function (string $val): string {
        $v = str_replace(["\r\n", "\r", "\n"], '\\n', trim($val));
        return addcslashes($v, ',;\\');
    };

    $fullName = trim((string)($vc['full_name'] ?? 'Zeynart'));
    $parts = preg_split('/\s+/u', $fullName) ?: [$fullName];
    $lastName = count($parts) > 1 ? (string)array_pop($parts) : '';
    $firstName = implode(' ', $parts);

    $cardUrl = rtrim(canonical_url('/kartvizit/' . $vc['slug'] . '/', $store, 'tr'), '/') . '/';

    $lines = [
        'BEGIN:VCARD',
        'VERSION:3.0',
        'PRODID:-//Zeynart Dijital Studyo//vCard 3.0//TR',
        'N;CHARSET=UTF-8:' . $escVcf($lastName) . ';' . $escVcf($firstName) . ';;;',
        'FN;CHARSET=UTF-8:' . $escVcf($fullName),
    ];

    if (!empty($vc['company'])) {
        $lines[] = 'ORG;CHARSET=UTF-8:' . $escVcf((string)$vc['company']);
    }
    if (!empty($vc['job_title'])) {
        $lines[] = 'TITLE;CHARSET=UTF-8:' . $escVcf((string)$vc['job_title']);
    }
    if (!empty($vc['phone_raw']) || !empty($vc['phone'])) {
        $tel = !empty($vc['phone_raw']) ? (string)$vc['phone_raw'] : (string)$vc['phone'];
        $lines[] = 'TEL;TYPE=CELL,VOICE,WORK:' . $escVcf($tel);
    }
    if (!empty($vc['email'])) {
        $lines[] = 'EMAIL;TYPE=INTERNET,WORK:' . $escVcf((string)$vc['email']);
    }
    if (!empty($vc['website'])) {
        $lines[] = 'URL;TYPE=WORK:' . $escVcf((string)$vc['website']);
    }
    $lines[] = 'URL;TYPE=PREF:' . $escVcf($cardUrl);
    if (!empty($vc['address'])) {
        $lines[] = 'ADR;TYPE=WORK;CHARSET=UTF-8:;;' . $escVcf((string)$vc['address']) . ';;;;Türkiye';
    }
    if (!empty($vc['bio'])) {
        $lines[] = 'NOTE;CHARSET=UTF-8:' . $escVcf((string)$vc['bio']);
    }
    $lines[] = 'REV:' . gmdate('Ymd\THis\Z');
    $lines[] = 'END:VCARD';

    return implode("\r\n", $lines) . "\r\n";
}
