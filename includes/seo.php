<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function zeynart_font_catalog(): array
{
    return [
        // Yerleşik & Sistem (0 Harici İstek)
        'Manrope' => ['type' => 'local', 'category' => 'local', 'stack' => '"Manrope", "Manrope Fallback", Arial, sans-serif', 'css2' => ''],
        'Outfit' => ['type' => 'local', 'category' => 'local', 'stack' => '"Outfit", "Outfit Fallback", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => ''],
        'System UI' => ['type' => 'system', 'category' => 'local', 'stack' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif', 'css2' => ''],
        'Georgia' => ['type' => 'system', 'category' => 'serif', 'stack' => 'Georgia, Cambria, "Times New Roman", Times, serif', 'css2' => ''],

        // Modern Kurumsal & Teknoloji (Google Fonts — Sans-Serif)
        'Plus Jakarta Sans' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Plus+Jakarta+Sans:wght@300;400;500;600;700;800'],
        'Inter' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Inter:wght@300;400;500;600;700;800'],
        'Space Grotesk' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Space+Grotesk:wght@300;400;500;600;700'],
        'Sora' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Sora", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Sora:wght@300;400;500;600;700;800'],
        'Urbanist' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Urbanist", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Urbanist:wght@300;400;500;600;700;800'],
        'DM Sans' => ['type' => 'google', 'category' => 'sans', 'stack' => '"DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'DM+Sans:wght@300;400;500;600;700'],
        'Poppins' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Poppins", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Poppins:wght@300;400;500;600;700'],
        'Montserrat' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Montserrat:wght@300;400;500;600;700;800'],
        'Rubik' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Rubik", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Rubik:wght@300;400;500;600;700'],
        'Nunito Sans' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Nunito Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Nunito+Sans:wght@300;400;600;700;800'],
        'Work Sans' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Work Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Work+Sans:wght@300;400;500;600;700'],
        'Figtree' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Figtree", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Figtree:wght@300;400;500;600;700;800'],
        'Lexend' => ['type' => 'google', 'category' => 'sans', 'stack' => '"Lexend", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Lexend:wght@300;400;500;600;700'],

        // Kreatif Ajans & Başlık (Display)
        'Syne' => ['type' => 'google', 'category' => 'display', 'stack' => '"Syne", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Syne:wght@400;500;600;700;800'],
        'Bricolage Grotesque' => ['type' => 'google', 'category' => 'display', 'stack' => '"Bricolage Grotesque", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Bricolage+Grotesque:wght@300;400;500;600;700;800'],
        'Archivo' => ['type' => 'google', 'category' => 'display', 'stack' => '"Archivo", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Archivo:wght@300;400;500;600;700;800'],
        'Josefin Sans' => ['type' => 'google', 'category' => 'display', 'stack' => '"Josefin Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif', 'css2' => 'Josefin+Sans:wght@300;400;500;600;700'],

        // Editoryal & Prestijli (Serif)
        'Playfair Display' => ['type' => 'google', 'category' => 'serif', 'stack' => '"Playfair Display", Georgia, serif', 'css2' => 'Playfair+Display:wght@400;500;600;700'],
        'Lora' => ['type' => 'google', 'category' => 'serif', 'stack' => '"Lora", Georgia, serif', 'css2' => 'Lora:wght@400;500;600;700'],
        'Merriweather' => ['type' => 'google', 'category' => 'serif', 'stack' => '"Merriweather", Georgia, serif', 'css2' => 'Merriweather:wght@300;400;700'],
        'Cormorant Garamond' => ['type' => 'google', 'category' => 'serif', 'stack' => '"Cormorant Garamond", Georgia, serif', 'css2' => 'Cormorant+Garamond:wght@400;500;600;700'],
    ];
}

function zeynart_extract_font_family(string $raw): string
{
    $s = trim($raw);
    if ($s === '') {
        return '';
    }
    // Support pasting full Google Fonts <link> or CSS URL (e.g. ?family=Chakra+Petch:wght@400;700)
    if (preg_match('/[?&]family=([^&:;"\'<>]+)/i', $s, $m)) {
        $s = rawurldecode(str_replace('+', ' ', $m[1]));
    }
    $s = preg_replace('/[^A-Za-z0-9 \-]/', '', $s) ?? '';
    $s = trim(preg_replace('/\s+/', ' ', $s) ?? '');
    return substr($s, 0, 60);
}

function zeynart_route_to_page_key(string $routePath, array $store): string
{
    $clean = trim(strtok($routePath, '?') ?: '/', '/');
    if ($clean === '') {
        return 'anasayfa';
    }
    $resolved = zeynart_resolved_page_slugs($store);
    foreach (['hakkimizda', 'hizmetler', 'projeler', 'blog', 'iletisim', 'site-analiz'] as $cKey) {
        if ($clean === $resolved[$cKey] || $clean === $cKey) {
            return $cKey;
        }
    }
    if (str_starts_with($clean, $resolved['blog'] . '/') || str_starts_with($clean, 'blog/')) {
        return 'blog';
    }
    if (str_starts_with($clean, $resolved['hizmetler'] . '/')) {
        return 'service:' . substr($clean, strlen($resolved['hizmetler']) + 1);
    }
    if (str_starts_with($clean, 'hizmetler/')) {
        return 'service:' . substr($clean, 10);
    }
    if (str_starts_with($clean, $resolved['projeler'] . '/')) {
        return 'project:' . substr($clean, strlen($resolved['projeler']) + 1);
    }
    if (str_starts_with($clean, 'projeler/')) {
        return 'project:' . substr($clean, 9);
    }
    if (in_array($clean, [$resolved['cerez-politikasi'], 'cerez-politikasi', 'cookie-policy', 'cerezler'], true)) {
        return 'legal:cerez-politikasi';
    }
    foreach (['kvkk', 'gizlilik'] as $lKey) {
        if ($clean === $resolved[$lKey] || $clean === $lKey) {
            return 'legal:' . $lKey;
        }
    }
    foreach (($store['custom_pages'] ?? []) as $cp) {
        if (is_array($cp) && ($cp['slug'] ?? '') === $clean && !empty($cp['id'])) {
            return 'custom:' . $cp['id'];
        }
    }
    return $clean;
}

function zeynart_resolve_typography(array $store, ?string $routePath = null): array
{
    $typo = is_array($store['menus']['typography'] ?? null) ? $store['menus']['typography'] : [];
    if ($routePath !== null && $routePath !== '') {
        $pgKey = zeynart_route_to_page_key($routePath, $store);
        $pgTypo = is_array($store['page_typography'][$pgKey] ?? null) ? $store['page_typography'][$pgKey] : [];
        if ($pgTypo !== []) {
            $ovBody = trim((string)($pgTypo['body_font'] ?? 'inherit'));
            if ($ovBody !== '' && $ovBody !== 'inherit') {
                $typo['body_font'] = $ovBody;
                if ($ovBody === 'custom') {
                    $typo['custom_body_font'] = (string)($pgTypo['custom_body_font'] ?? '');
                }
            }
            $ovHead = trim((string)($pgTypo['heading_font'] ?? 'inherit'));
            if ($ovHead !== '' && $ovHead !== 'inherit') {
                $typo['heading_font'] = $ovHead;
                if ($ovHead === 'custom') {
                    $typo['custom_heading_font'] = (string)($pgTypo['custom_heading_font'] ?? '');
                }
            }
            $ovBodySize = (float)($pgTypo['base_body_size'] ?? 0);
            if ($ovBodySize >= 12.0 && $ovBodySize <= 22.0) {
                $typo['base_body_size'] = $ovBodySize;
            }
            $ovHeadScale = (float)($pgTypo['heading_scale'] ?? 0);
            if ($ovHeadScale >= 75.0 && $ovHeadScale <= 140.0) {
                $typo['heading_scale'] = $ovHeadScale;
            }
            $ovTrack = trim((string)($pgTypo['heading_tracking'] ?? 'inherit'));
            if (in_array($ovTrack, ['tight', 'balanced', 'normal'], true)) {
                $typo['heading_tracking'] = $ovTrack;
            }
        }
    }
    $catalog = zeynart_font_catalog();

    $rawBody = trim((string)($typo['body_font'] ?? 'Manrope'));
    $customBody = zeynart_extract_font_family((string)($typo['custom_body_font'] ?? ''));

    $rawHeading = trim((string)($typo['heading_font'] ?? 'same'));
    $customHeading = zeynart_extract_font_family((string)($typo['custom_heading_font'] ?? ''));

    $css2Specs = [];
    $customFamilies = [];

    // Resolve Body Font
    if ($rawBody === 'custom' && $customBody !== '') {
        if (isset($catalog[$customBody])) {
            $bodyFamily = $customBody;
            $bodyStack = $catalog[$customBody]['stack'];
            if ($catalog[$customBody]['type'] === 'google') {
                $css2Specs[$customBody] = $catalog[$customBody]['css2'];
            }
        } else {
            $bodyFamily = $customBody;
            $bodyStack = '"' . $customBody . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif';
            $customFamilies[$customBody] = $customBody;
        }
    } elseif (isset($catalog[$rawBody])) {
        $bodyFamily = $rawBody;
        $bodyStack = $catalog[$rawBody]['stack'];
        if ($catalog[$rawBody]['type'] === 'google') {
            $css2Specs[$rawBody] = $catalog[$rawBody]['css2'];
        }
    } else {
        $cleanDirect = zeynart_extract_font_family($rawBody);
        if ($cleanDirect !== '' && strtolower($cleanDirect) !== 'custom') {
            $bodyFamily = $cleanDirect;
            $bodyStack = '"' . $cleanDirect . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif';
            $customFamilies[$cleanDirect] = $cleanDirect;
        } else {
            $bodyFamily = 'Manrope';
            $bodyStack = $catalog['Manrope']['stack'];
        }
    }

    // Resolve Heading Font
    if ($rawHeading === '' || $rawHeading === 'same') {
        $headingFamily = $bodyFamily;
        $headingStack = $bodyStack;
    } elseif ($rawHeading === 'custom' && $customHeading !== '') {
        if (isset($catalog[$customHeading])) {
            $headingFamily = $customHeading;
            $headingStack = $catalog[$customHeading]['stack'];
            if ($catalog[$customHeading]['type'] === 'google') {
                $css2Specs[$customHeading] = $catalog[$customHeading]['css2'];
            }
        } else {
            $headingFamily = $customHeading;
            $headingStack = '"' . $customHeading . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif';
            $customFamilies[$customHeading] = $customHeading;
        }
    } elseif (isset($catalog[$rawHeading])) {
        $headingFamily = $rawHeading;
        $headingStack = $catalog[$rawHeading]['stack'];
        if ($catalog[$rawHeading]['type'] === 'google') {
            $css2Specs[$rawHeading] = $catalog[$rawHeading]['css2'];
        }
    } else {
        $cleanH = zeynart_extract_font_family($rawHeading);
        if ($cleanH !== '' && strtolower($cleanH) !== 'custom') {
            $headingFamily = $cleanH;
            $headingStack = '"' . $cleanH . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif';
            $customFamilies[$cleanH] = $cleanH;
        } else {
            $headingFamily = $bodyFamily;
            $headingStack = $bodyStack;
        }
    }

    $googleUrls = [];
    $curLangForFont = zeynart_current_lang();
    $fontSubsets = ($curLangForFont === 'ru') ? 'latin,latin-ext,cyrillic' : 'latin,latin-ext';
    if ($css2Specs !== []) {
        $googleUrls[] = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_values($css2Specs)) . '&display=swap';
    }
    if ($customFamilies !== []) {
        $famParts = array_map(static fn(string $f): string => str_replace(' ', '+', $f) . ':300,400,500,600,700,800', array_values($customFamilies));
        $googleUrls[] = 'https://fonts.googleapis.com/css?family=' . implode('|', $famParts) . '&subset=' . $fontSubsets . '&display=swap';
    }

    $baseBodySize = round(max(12.0, min(22.0, (float)($typo['base_body_size'] ?? 14))), 1);
    $bodyScale = round($baseBodySize / 14.0, 4);
    $headingScalePct = round(max(75.0, min(140.0, (float)($typo['heading_scale'] ?? 100))), 0);
    $headingScale = round($headingScalePct / 100.0, 3);
    $rawTracking = trim((string)($typo['heading_tracking'] ?? 'tight'));
    $headingTracking = in_array($rawTracking, ['tight', 'balanced', 'normal'], true) ? $rawTracking : 'tight';

    return [
        'body_font' => $rawBody !== '' ? $rawBody : 'Manrope',
        'custom_body_font' => $customBody,
        'heading_font' => $rawHeading !== '' ? $rawHeading : 'same',
        'custom_heading_font' => $customHeading,
        'body_family' => $bodyFamily,
        'heading_family' => $headingFamily,
        'body_stack' => $bodyStack,
        'heading_stack' => $headingStack,
        'base_body_size' => $baseBodySize,
        'body_scale' => $bodyScale,
        'heading_scale_pct' => $headingScalePct,
        'heading_scale' => $headingScale,
        'heading_tracking' => $headingTracking,
        'uses_local_manrope' => ($bodyFamily === 'Manrope' || $headingFamily === 'Manrope'),
        'uses_local_outfit' => ($bodyFamily === 'Outfit' || $headingFamily === 'Outfit'),
        'google_font_urls' => $googleUrls,
    ];
}

function render_seo_head(array $store, array $meta = []): string
{
    require_once __DIR__ . '/social_card.php';

    $site = $store['site'] ?? [];
    $seoCfg = $store['seo'] ?? [];
    $aiCfg = $store['ai_seo'] ?? [];

    $pickFirst = static function (string ...$vals): string {
        foreach ($vals as $v) {
            $t = trim($v);
            if ($t !== '') {
                return $t;
            }
        }
        return '';
    };

    $rawTitle = $pickFirst(
        (string)($meta['title'] ?? ''),
        (string)($seoCfg['default_title'] ?? ''),
        'Zeynart Web Bilişim'
    );
    $title = zeynart_clean_social_text($rawTitle, 140);

    $rawDesc = $pickFirst(
        (string)($meta['description'] ?? ''),
        (string)($meta['ai_summary'] ?? ''),
        (string)($seoCfg['default_description'] ?? '')
    );
    $desc = zeynart_clean_social_text($rawDesc, 220);

    $socialTitle = zeynart_clean_social_text($pickFirst(
        (string)($meta['og_title'] ?? ''),
        $title
    ), 120);

    $socialDesc = zeynart_clean_social_text($pickFirst(
        (string)($meta['og_description'] ?? ''),
        $desc,
        (string)($seoCfg['default_description'] ?? ''),
        'Siz hayal edin, Zeynart tasarlasın. Web tasarım, grafik tasarım, özel yazılım, mobil uygulama ve kurumsal hosting çözümleri.'
    ), 200);

    $keywords = trim((string)($meta['keywords'] ?? $seoCfg['default_keywords'] ?? ''));
    $robots = trim((string)($meta['robots'] ?? 'index, follow')) ?: 'index, follow';
    $path = (string)($meta['canonical_path'] ?? '/');
    $canonical = trim((string)($meta['canonical_override'] ?? '')) !== ''
        ? trim((string)$meta['canonical_override'])
        : canonical_url($path, $store);

    $ogType = trim((string)($meta['og_type'] ?? 'website')) ?: 'website';
    $explicitOgCandidate = $pickFirst(
        (string)($meta['og_image'] ?? ''),
        (string)($meta['fallback_image'] ?? ''),
        ($path !== '/' ? (string)($meta['preload_image'] ?? '') : '')
    );
    $ogImgCandidate = $pickFirst(
        $explicitOgCandidate,
        (string)($site['og_image'] ?? ''),
        '/images/opengraph-image.png'
    );
    $brandedOgCfg = function_exists('zeynart_get_branded_og_config') ? zeynart_get_branded_og_config($store) : ['mode' => 'off'];
    $socialImg = null;
    if (
        function_exists('zeynart_generate_branded_og_jpeg')
        && (
            ($brandedOgCfg['mode'] ?? 'off') === 'all'
            || (($brandedOgCfg['mode'] ?? 'off') === 'fallback' && $explicitOgCandidate === '' && $path !== '/')
        )
    ) {
        $bgNorm = $explicitOgCandidate !== '' ? (zeynart_normalize_local_media_path($explicitOgCandidate, $store) ?? '') : '';
        $brandedFile = zeynart_generate_branded_og_jpeg([
            'title' => $socialTitle,
            'eyebrow' => (string)($meta['og_eyebrow'] ?? ''),
            'subtitle' => $socialDesc,
            'bg_local' => $bgNorm,
        ], $store);
        if ($brandedFile !== null && is_file($brandedFile)) {
            $relBranded = '/uploads/og/' . basename($brandedFile);
            $socialImg = [
                'url' => canonical_url($relBranded, $store),
                'relative_url' => url($relBranded),
                'mime' => 'image/jpeg',
                'width' => 1200,
                'height' => 630,
            ];
        }
    }
    if ($socialImg === null) {
        $socialImg = zeynart_resolve_social_image($ogImgCandidate, $store);
    }
    $ogImage = $socialImg['url'];
    $ogImageSecure = str_starts_with($ogImage, 'http://')
        ? ('https://' . substr($ogImage, 7))
        : $ogImage;

    $preloadImage = trim((string)($meta['preload_image'] ?? ''));
    $logoUrl = url((string)($site['logo'] ?? '/images/zeynart-logo.avif'));
    $faviconUrl = url((string)($site['favicon'] ?? '/icon.png'));
    $themeColor = (string)($site['theme_color'] ?? '#070f12');
    $resolvedTypo = zeynart_resolve_typography($store, $path);
    $domainHost = (string)(parse_url($canonical, PHP_URL_HOST) ?: 'zeynart.com');

    $curLang = zeynart_current_lang();
    $langMeta = zeynart_lang_meta($curLang);
    $allLangs = array_intersect_key(zeynart_supported_languages(), array_flip(zeynart_public_languages($store)));

    $out = [];
    $out[] = '<meta charset="utf-8"/>';
    $out[] = '<meta name="viewport" content="width=device-width, initial-scale=1"/>';
    $out[] = '<title>' . e($title) . '</title>';
    if ($desc !== '') {
        $out[] = '<meta name="description" content="' . e($desc) . '"/>';
    }
    if ($keywords !== '') {
        $out[] = '<meta name="keywords" content="' . e($keywords) . '"/>';
    }
    $out[] = '<meta name="robots" content="' . e($robots) . ', max-image-preview:large, max-snippet:-1, max-video-preview:-1"/>';
    $out[] = '<link rel="canonical" href="' . e($canonical) . '"/>';

    // Multilingual hreflang alternate links (TR · EN · DE · RU · x-default)
    if (!str_contains(strtolower($robots), 'noindex')) {
        $metaAlternates = is_array($meta['lang_alternates'] ?? null) ? $meta['lang_alternates'] : [];
        $hreflangUrls = [];
        foreach ($allLangs as $lCode => $lInfo) {
            $altPath = !empty($metaAlternates[$lCode]) ? (string)$metaAlternates[$lCode] : $path;
            $hreflangUrls[$lCode] = lang_canonical_url($altPath, $store, $lCode);
            $out[] = '<link rel="alternate" hreflang="' . e($lCode) . '" href="' . e($hreflangUrls[$lCode]) . '"/>';
        }
        $out[] = '<link rel="alternate" hreflang="x-default" href="' . e($hreflangUrls['tr'] ?? canonical_url($path, $store, 'tr')) . '"/>';
    }

    // OpenGraph & Social Link Paste Meta Tags (placed early in <head> so WhatsApp/Telegram/LinkedIn crawlers read within first 4 KB)
    $out[] = '<meta property="og:locale" content="' . e($langMeta['locale']) . '"/>';
    foreach ($allLangs as $lCode => $lInfo) {
        if ($lCode !== $curLang) {
            $out[] = '<meta property="og:locale:alternate" content="' . e($lInfo['locale']) . '"/>';
        }
    }
    $out[] = '<meta property="og:site_name" content="' . e((string)($site['name'] ?? 'ZEYNART')) . '"/>';
    $out[] = '<meta property="og:type" content="' . e($ogType) . '"/>';
    $out[] = '<meta property="og:title" content="' . e($socialTitle) . '"/>';
    $out[] = '<meta property="og:description" content="' . e($socialDesc) . '"/>';
    $out[] = '<meta property="og:url" content="' . e($canonical) . '"/>';
    $out[] = '<meta property="og:image" content="' . e($ogImage) . '"/>';
    $out[] = '<meta property="og:image:secure_url" content="' . e($ogImageSecure) . '"/>';
    $out[] = '<meta property="og:image:type" content="' . e($socialImg['mime']) . '"/>';
    $out[] = '<meta property="og:image:width" content="' . (int)$socialImg['width'] . '"/>';
    $out[] = '<meta property="og:image:height" content="' . (int)$socialImg['height'] . '"/>';
    $out[] = '<meta property="og:image:alt" content="' . e($socialTitle) . '"/>';

    if ($ogType === 'article') {
        if (!empty($meta['published_time'])) {
            $out[] = '<meta property="article:published_time" content="' . e((string)$meta['published_time']) . '"/>';
        }
        if (!empty($meta['modified_time'])) {
            $out[] = '<meta property="article:modified_time" content="' . e((string)$meta['modified_time']) . '"/>';
        }
        if (!empty($meta['article_section'])) {
            $out[] = '<meta property="article:section" content="' . e((string)$meta['article_section']) . '"/>';
        }
    }

    // X (Twitter) Large Summary Card + Microdata Image Fallbacks (WhatsApp Desktop / Telegram / Discord)
    $out[] = '<meta name="twitter:card" content="summary_large_image"/>';
    $twHandle = trim((string)($seo['twitter_handle'] ?? ''));
    if ($twHandle !== '') {
        $twHandle = '@' . ltrim($twHandle, '@');
        $out[] = '<meta name="twitter:site" content="' . e($twHandle) . '"/>';
    }
    $out[] = '<meta name="twitter:domain" content="' . e($domainHost) . '"/>';
    $out[] = '<meta name="twitter:url" content="' . e($canonical) . '"/>';
    $out[] = '<meta name="twitter:title" content="' . e($socialTitle) . '"/>';
    $out[] = '<meta name="twitter:description" content="' . e($socialDesc) . '"/>';
    $out[] = '<meta name="twitter:image" content="' . e($ogImage) . '"/>';
    $out[] = '<meta name="twitter:image:alt" content="' . e($socialTitle) . '"/>';
    $out[] = '<meta itemprop="name" content="' . e($socialTitle) . '"/>';
    $out[] = '<meta itemprop="description" content="' . e($socialDesc) . '"/>';
    $out[] = '<meta itemprop="image" content="' . e($ogImage) . '"/>';
    $out[] = '<link rel="image_src" href="' . e($ogImage) . '"/>';

    // Optional public theme hook: a theme may register its own stylesheet + font preloads ($GLOBALS['zeynart_public_theme'])
    $publicTheme = is_array($GLOBALS['zeynart_public_theme'] ?? null) ? $GLOBALS['zeynart_public_theme'] : null;
    if ($publicTheme !== null) {
        $resolvedTypo['uses_local_manrope'] = false;
        $resolvedTypo['uses_local_outfit'] = false;
        $resolvedTypo['google_font_urls'] = [];
        foreach ((array)($publicTheme['font_preloads'] ?? []) as $fp) {
            $out[] = '<link rel="preload" href="' . e(url((string)$fp)) . '" as="font" type="font/woff2" crossorigin/>';
        }
    }
    // Preload self-hosted Manrope / Outfit WOFF2 only when active (zero external Google Fonts chain)
    if ($resolvedTypo['uses_local_manrope']) {
        $out[] = '<link rel="preload" href="' . e(url('/assets/fonts/6ab0db14f70d8ed6-s.p.13hnt-xgp82zk.woff2')) . '" as="font" type="font/woff2" crossorigin/>';
        $out[] = '<link rel="preload" href="' . e(url('/assets/fonts/a343f882a40d2cc9-s.p.1sj6eobyi31rd.woff2')) . '" as="font" type="font/woff2" crossorigin/>';
    }
    if (!empty($resolvedTypo['uses_local_outfit'])) {
        $out[] = '<link rel="preload" href="' . e(url('/assets/fonts/outfit-latin-ext.woff2')) . '" as="font" type="font/woff2" crossorigin/>';
        $out[] = '<link rel="preload" href="' . e(url('/assets/fonts/outfit-latin.woff2')) . '" as="font" type="font/woff2" crossorigin/>';
    }
    if ($resolvedTypo['google_font_urls'] !== []) {
        $out[] = '<link rel="preconnect" href="https://fonts.googleapis.com"/>';
        $out[] = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>';
        foreach ($resolvedTypo['google_font_urls'] as $gfUrl) {
            $out[] = '<link rel="stylesheet" href="' . e($gfUrl) . '" media="print" onload="this.media=\'all\'"/>';
            $out[] = '<noscript><link rel="stylesheet" href="' . e($gfUrl) . '"/></noscript>';
        }
    }
    $rawLogoPath = function_exists('ascam_logo_src') ? ascam_logo_src($site) : (string)($site['logo'] ?? '/images/zeynart-logo.avif');
    $headerLogoUrl = url($rawLogoPath);
    if ($rawLogoPath === '/images/zeynart-logo.avif' && is_file(ROOT_DIR . '/images/zeynart-logo-192w.avif')) {
        $out[] = '<link rel="preload" as="image" href="' . e($logoUrl) . '" imagesrcset="' . e(url('/images/zeynart-logo-192w.avif')) . ' 192w, ' . e($logoUrl) . ' 320w" imagesizes="(max-width: 760px) 150px, 192px"/>';
    } else {
        $out[] = '<link rel="preload" as="image" href="' . e($headerLogoUrl) . '"/>';
    }

    if ($preloadImage !== '') {
        if ($preloadImage === '/images/architecture.webp' && is_file(ROOT_DIR . '/images/architecture-800w.webp')) {
            $out[] = '<link rel="preload" as="image" href="' . e(url($preloadImage)) . '" imagesrcset="' . e(url('/images/architecture-800w.webp')) . ' 836w, ' . e(url('/images/architecture.webp')) . ' 1672w" imagesizes="100vw" fetchpriority="high"/>';
        } else {
            $out[] = '<link rel="preload" as="image" href="' . e(url($preloadImage)) . '" fetchpriority="high"/>';
        }
    }

    if ($publicTheme !== null && !empty($publicTheme['stylesheet'])) {
        $out[] = '<link rel="stylesheet" href="' . e(asset_url((string)$publicTheme['stylesheet'])) . '"/>';
    } else {
    $out[] = '<link rel="stylesheet" href="' . e(asset_url('/assets/css/site.css')) . '"/>';
    }
    $out[] = '<meta name="theme-color" content="' . e($themeColor) . '"/>';

    // AI SEO / GEO & Agentic Resource Discovery (ARD / ai-catalog.json) Hints
    if (!empty($aiCfg['enabled'])) {
        if (!empty($aiCfg['llms_txt_enabled'])) {
            $out[] = '<link rel="alternate" type="text/plain" title="AI Knowledge Summary (llms.txt)" href="' . e(canonical_url('/llms.txt', $store)) . '"/>';
        }
        $out[] = '<link rel="ai-catalog" type="application/json" href="' . e(canonical_url('/.well-known/ai-catalog.json', $store)) . '"/>';
    }
    if (!empty($meta['ai_summary'])) {
        $out[] = '<meta name="ai-summary" content="' . e((string)$meta['ai_summary']) . '"/>';
    }

    // Search Console Verifications
    if (!empty($seoCfg['google_verification'])) {
        $gscRaw = trim((string)$seoCfg['google_verification']);
        if (preg_match('/content=["\']([^"\']+)["\']/i', $gscRaw, $gm)) {
            $gscRaw = trim($gm[1]);
        }
        if ($gscRaw !== '') {
            $out[] = '<meta name="google-site-verification" content="' . e($gscRaw) . '"/>';
        }
    }
    if (!empty($seoCfg['bing_verification'])) {
        $out[] = '<meta name="msvalidate.01" content="' . e((string)$seoCfg['bing_verification']) . '"/>';
    }
    if (!empty($seoCfg['yandex_verification'])) {
        $out[] = '<meta name="yandex-verification" content="' . e((string)$seoCfg['yandex_verification']) . '"/>';
    }

    $out[] = '<link rel="icon" href="' . e($faviconUrl) . '" sizes="128x128" type="image/png"/>';

    // Build Rich JSON-LD Schema Graph (Normal SEO + AI Entity Graph)
    $baseDomain = rtrim(canonical_url('/', $store, 'tr'), '/');
    $orgId = $baseDomain . '/#organization';
    $websiteId = $baseDomain . '/#website';

    $orgNode = [
        '@type' => (string)($seoCfg['schema_org_type'] ?? 'ProfessionalService'),
        '@id' => $orgId,
        'name' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
        'alternateName' => (string)($site['name'] ?? 'ZEYNART'),
        'url' => $baseDomain . '/',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => canonical_url((string)($site['logo'] ?? '/images/zeynart-logo.avif'), $store),
        ],
        'image' => canonical_url((string)($site['og_image'] ?? '/images/opengraph-image.png'), $store),
        'foundingDate' => (string)($site['founded_year'] ?? '2014'),
        'telephone' => (string)($site['phone_href'] ?? '+908503050533'),
        'email' => (string)($site['email'] ?? 'info@zeynart.com'),
        'priceRange' => (string)($seoCfg['price_range'] ?? '₺₺'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => (string)($site['address'] ?? 'Maltepe Mh. 21078 Sk. No:6/A'),
            'addressLocality' => (string)($site['city'] ?? 'İzmir'),
            'addressCountry' => (string)($site['country'] ?? 'TR'),
        ],
    ];
    if (!empty($aiCfg['entity_knows_about']) && is_array($aiCfg['entity_knows_about'])) {
        $orgNode['knowsAbout'] = array_values($aiCfg['entity_knows_about']);
    }
require_once __DIR__ . '/icons.php';
    $parsedSocials = zeynart_social_links($store);
    if ($parsedSocials !== []) {
        $orgNode['sameAs'] = array_values(array_map(static fn(array $s): string => $s['url'], $parsedSocials));
    }
    require_once __DIR__ . '/google_reviews.php';
    $grSchema = get_google_reviews_state($store);
    if (!empty($grSchema['enabled']) && (int)$grSchema['total_reviews'] > 0) {
        $orgNode['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float)$grSchema['rating'], 1, '.', ''),
            'bestRating' => '5',
            'worstRating' => '1',
            'reviewCount' => (string)((int)$grSchema['total_reviews']),
        ];
        $schemaReviews = [];
        foreach (array_slice(get_filtered_google_reviews($grSchema), 0, 5) as $srv) {
            $schemaReviews[] = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => (string)($srv['author'] ?? 'Google Kullanıcısı'),
                ],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (string)((int)($srv['rating'] ?? 5)),
                    'bestRating' => '5',
                ],
                'reviewBody' => (string)($srv['text'] ?? ''),
            ];
        }
        if ($schemaReviews !== []) {
            $orgNode['review'] = $schemaReviews;
        }
    }

    $websiteNode = [
        '@type' => 'WebSite',
        '@id' => $websiteId,
        'url' => canonical_url('/', $store),
        'name' => (string)($site['name'] ?? 'ZEYNART'),
        'description' => (string)($seoCfg['default_description'] ?? ''),
        'inLanguage' => $langMeta['bcp47'],
        'publisher' => ['@id' => $orgId],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => canonical_url('/blog/', $store) . '?ara={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $webPageNode = [
        '@type' => 'WebPage',
        '@id' => $canonical . '#webpage',
        'url' => $canonical,
        'name' => $title,
        'description' => $desc,
        'inLanguage' => $langMeta['bcp47'],
        'isPartOf' => ['@id' => $websiteId],
        'about' => ['@id' => $orgId],
    ];
    if (!empty($meta['ai_summary'])) {
        $webPageNode['abstract'] = (string)$meta['ai_summary'];
    }
    if (!empty($aiCfg['speakable_schema'])) {
        $webPageNode['speakable'] = [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => ['h1', '.hero-description', '.journal-article-lead', '.page-intro p'],
        ];
    }

    $graph = [$orgNode, $websiteNode, $webPageNode];

    // BreadcrumbList Schema
    if (!empty($meta['breadcrumbs']) && is_array($meta['breadcrumbs'])) {
        $itemList = [];
        foreach (array_values($meta['breadcrumbs']) as $idx => $bc) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'name' => (string)($bc['name'] ?? ''),
                'item' => canonical_url((string)($bc['url'] ?? '/'), $store),
            ];
        }
        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical . '#breadcrumb',
            'itemListElement' => $itemList,
        ];
    }

    // Extra custom schema nodes (BlogPosting, Service, CreativeWork)
    if (!empty($meta['schema_nodes']) && is_array($meta['schema_nodes'])) {
        foreach ($meta['schema_nodes'] as $node) {
            if (is_array($node)) {
                $graph[] = $node;
            }
        }
    }

    // FAQPage Schema
    if (!empty($meta['faqs']) && is_array($meta['faqs'])) {
        $faqEntities = [];
        foreach ($meta['faqs'] as $fq) {
            $q = trim((string)($fq['q'] ?? ''));
            $a = trim((string)($fq['a'] ?? ''));
            if ($q !== '' && $a !== '') {
                $faqEntities[] = [
                    '@type' => 'Question',
                    'name' => $q,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $a,
                    ],
                ];
            }
        }
        if ($faqEntities !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical . '#faq',
                'mainEntity' => $faqEntities,
            ];
        }
    }

    $ldJson = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($ldJson !== false) {
        $out[] = '<script type="application/ld+json">' . $ldJson . '</script>';
    }

    // Google Consent Mode v2 (default-denied) & Zero-Cookie Load Script Vault
    require_once __DIR__ . '/cookie_consent.php';
    $out[] = zeynart_render_consent_head($store);

    return implode("\n", $out) . "\n";
}

function compile_robots_txt(array $store): string
{
    $aiCfg = $store['ai_seo'] ?? [];
    $seoCfg = $store['seo'] ?? [];
    $base = rtrim(canonical_url('/', $store), '/');

    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin/',
        'Disallow: /data/',
        'Disallow: /includes/',
        'Disallow: /lib/',
        '',
    ];

    $bots = is_array($aiCfg['bots'] ?? null) ? $aiCfg['bots'] : [];
    foreach ($bots as $botName => $policy) {
        $cleanBot = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$botName) ?? '';
        if ($cleanBot === '') {
            continue;
        }
        $lines[] = 'User-agent: ' . $cleanBot;
        if ($policy === 'disallow') {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin/';
        }
        $lines[] = '';
    }

    $custom = trim((string)($seoCfg['robots_custom_rules'] ?? ''));
    if ($custom !== '') {
        $custom = preg_replace('/^\s*Agentmap\s*:\s*(.+)$/mi', '# AI-Catalog: $1', $custom) ?? $custom;
        $lines[] = $custom;
        $lines[] = '';
    }

    $lines[] = 'Sitemap: ' . $base . '/sitemap.xml';
    if (!empty($aiCfg['enabled'])) {
        $lines[] = '# AI-Catalog: ' . $base . '/.well-known/ai-catalog.json';
        if (!empty($aiCfg['llms_txt_enabled'])) {
            $lines[] = '# AI / LLM Context: ' . $base . '/llms.txt';
        }
    }

    return implode("\n", $lines) . "\n";
}

function compile_llms_txt(array $store, bool $full = false): string
{
    $site = $store['site'] ?? [];
    $aiCfg = $store['ai_seo'] ?? [];
    $base = rtrim(canonical_url('/', $store), '/');

    $lines = [];
    $lines[] = '# ' . ($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri');
    $lines[] = '';
    $lines[] = '> ' . ($aiCfg['custom_llms_intro'] ?? ($site['tagline'] ?? 'Siz hayal edin, Zeynart tasarlasın.'));
    $lines[] = '';
    $lines[] = '## Kurumsal Bilgiler & İletişim';
    $lines[] = '- Marka Adı: ' . ($site['name'] ?? 'ZEYNART');
    $lines[] = '- Kuruluş Yılı: ' . ($site['founded_year'] ?? '2014');
    $lines[] = '- Telefon: ' . ($site['phone'] ?? '0850 305 0 533');
    $lines[] = '- E-posta: ' . ($site['email'] ?? 'info@zeynart.com');
    $lines[] = '- Adres: ' . ($site['address'] ?? 'Maltepe Mh. 21078 Sk. No:6/A') . ', ' . ($site['city'] ?? 'İzmir');
    $lines[] = '- Web: ' . $base . '/';
    $lines[] = '- İletişim & Teklif Formu: ' . $base . '/iletisim/';
    $lines[] = '';

    $lines[] = '## Temel Sayfalar';
    $lines[] = '- [Ana Sayfa](' . $base . '/): Zeynart dijital tasarım, özel yazılım ve bulut altyapı stüdyosu.';
    $lines[] = '- [Hizmetler](' . $base . '/hizmetler/): Tüm uzmanlık alanlarımız ve geliştirme sürecimiz.';
    $lines[] = '- [Hakkımızda](' . $base . '/hakkimizda/): 2014’ten bugüne Zeynart hikâyesi ve 62+ marka referansı.';
    $lines[] = '- [Projeler](' . $base . '/projeler/): Fintech, e-ticaret ve kurumsal SaaS ürün konseptleri.';
    $lines[] = '- [15 Dk Dijital Keşif Görüşmesi Planla](' . $base . '/gorusme-planla/): Çerezsiz online Google Meet, WhatsApp veya telefon ön görüşme takvimi.';
    $lines[] = '- [Blog / Zeynart Journal](' . $base . '/blog/): Tasarım, yazılım ve teknoloji rehberleri.';
    $lines[] = '';

    $publishedCustomPages = array_values(array_filter(
        is_array($store['custom_pages'] ?? null) ? $store['custom_pages'] : [],
        static fn($cp): bool => is_array($cp) && ($cp['status'] ?? 'published') === 'published' && !empty($cp['slug'])
    ));
    if ($publishedCustomPages !== []) {
        $lines[] = '## Sektörel & Bölgesel Kurumsal Çözüm Sayfaları';
        foreach ($publishedCustomPages as $cpItem) {
            $cpUrl = $base . '/' . trim((string)$cpItem['slug'], '/') . '/';
            $lines[] = '- [' . ($cpItem['title'] ?? '') . '](' . $cpUrl . '): ' . ($cpItem['excerpt'] ?? '');
        }
        $lines[] = '';
    }

    $lines[] = '## Uzmanlık Alanları ve Hizmetler';
    foreach (($store['services'] ?? []) as $srv) {
        $sUrl = $base . '/hizmetler/' . ($srv['slug'] ?? '') . '/';
        $lines[] = '- [' . ($srv['title'] ?? '') . '](' . $sUrl . '): ' . ($srv['description'] ?? $srv['short'] ?? '');
        if ($full && !empty($srv['deliverables']) && is_array($srv['deliverables'])) {
            $lines[] = '  - Teslimatlar: ' . implode(', ', $srv['deliverables']);
        }
        if ($full && !empty($srv['hosting_data']['packages'])) {
            foreach ($srv['hosting_data']['packages'] as $pkg) {
                $lines[] = '  - Hosting Paketi ' . ($pkg['name'] ?? '') . ': ' . ($pkg['disk'] ?? '') . ' disk alanı, Sınırsız trafik, Plesk, 7/24 DDoS koruması.';
            }
        }
    }
    $lines[] = '';

    $lines[] = '## Seçili Ürün Konseptleri';
    foreach (($store['projects'] ?? []) as $prj) {
        $pUrl = $base . '/projeler/' . ($prj['slug'] ?? '') . '/';
        $lines[] = '- [' . ($prj['name'] ?? '') . '](' . $pUrl . ') (' . ($prj['sector'] ?? '') . '): ' . ($prj['summary'] ?? '');
    }
    $lines[] = '';

    $blogIndex = load_blog_index();
    $publishedPosts = array_values(array_filter(
        $blogIndex,
        static fn(array $p): bool => ($p['status'] ?? 'published') === 'published'
    ));
    $blogSlug = zeynart_page_slug($store, 'blog') ?: 'blog';
    $catTree = zeynart_build_blog_category_tree(zeynart_get_blog_categories($store));
    $catCounts = zeynart_count_posts_per_category($catTree, $publishedPosts, false);
    if ($catTree !== []) {
        $lines[] = '## Blog Kategorileri (Hiyerarşik Konu Dizinleri)';
        foreach ($catTree as $cat) {
            $cUrl = $base . '/' . $blogSlug . '/kategori/' . ($cat['slug'] ?? '') . '/';
            $cCnt = (int)($catCounts[$cat['id']]['total'] ?? 0);
            $indent = !empty($cat['depth']) ? str_repeat('  ', (int)$cat['depth']) : '';
            $lines[] = $indent . '- [' . ($cat['name'] ?? '') . '](' . $cUrl . ') (' . $cCnt . ' makale): ' . ($cat['description'] ?? '');
        }
        $lines[] = '';
    }
    $limit = $full ? 60 : 12;
    $slice = array_slice($publishedPosts, 0, $limit);

    $lines[] = '## Zeynart Journal — Öne Çıkan Rehberler (Toplam ' . count($publishedPosts) . ' Makale)';
    foreach ($slice as $post) {
        $bUrl = $base . '/blog/' . ($post['slug'] ?? '') . '/';
        $lines[] = '- [' . ($post['title'] ?? '') . '](' . $bUrl . ') [' . ($post['category'] ?? 'GENEL') . ']: ' . ($post['excerpt'] ?? '');
    }

    if (!$full && !empty($aiCfg['llms_full_enabled'])) {
        $lines[] = '';
        $lines[] = '## Tam Bilgi Tabanı';
        $lines[] = '- [Genişletilmiş AI Bağlam Dosyası (llms-full.txt)](' . $base . '/llms-full.txt)';
    }

    return implode("\n", $lines) . "\n";
}

function analyze_seo_score(array $data): array
{
    $title = trim((string)($data['meta_title'] ?? $data['title'] ?? ''));
    $desc = trim((string)($data['meta_desc'] ?? $data['excerpt'] ?? ''));
    $kw = mb_strtolower(trim((string)($data['focus_keyword'] ?? '')), 'UTF-8');
    $slug = trim((string)($data['slug'] ?? ''));
    $bodyHtml = (string)($data['content_html'] ?? '');
    $plain = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags($bodyHtml)) ?? ''), 'UTF-8');
    $aiSummary = trim((string)($data['ai_summary'] ?? ''));
    $image = trim((string)($data['image'] ?? ''));
    $faqs = is_array($data['faqs'] ?? null) ? $data['faqs'] : [];

    $score = 0;
    $checks = [];

    // 1. Meta title length (15 pts)
    $tLen = mb_strlen($title, 'UTF-8');
    if ($tLen >= 25 && $tLen <= 68) {
        $score += 15;
        $checks[] = ['ok' => true, 'label' => "Meta başlık uzunluğu ideal ({$tLen} karakter)."];
    } elseif ($tLen > 0) {
        $score += 9;
        $checks[] = ['ok' => false, 'label' => "Meta başlık 25–65 karakter aralığında olmalı (şu an {$tLen})."];
    } else {
        $checks[] = ['ok' => false, 'label' => 'Meta başlık eksik.'];
    }

    // 2. Meta description length (15 pts)
    $dLen = mb_strlen($desc, 'UTF-8');
    if ($dLen >= 80 && $dLen <= 170) {
        $score += 15;
        $checks[] = ['ok' => true, 'label' => "Meta açıklama uzunluğu ideal ({$dLen} karakter)."];
    } elseif ($dLen > 0) {
        $score += 9;
        $checks[] = ['ok' => false, 'label' => "Meta açıklama 80–160 karakter aralığında olmalı (şu an {$dLen})."];
    } else {
        $checks[] = ['ok' => false, 'label' => 'Meta açıklama eksik.'];
    }

    // 3. Focus keyword presence (20 pts)
    if ($kw !== '') {
        $inTitle = str_contains(mb_strtolower($title, 'UTF-8'), $kw);
        $inBody = $plain === '' || str_contains($plain, $kw) || str_contains(mb_strtolower($desc, 'UTF-8'), $kw);
        if ($inTitle && $inBody) {
            $score += 20;
            $checks[] = ['ok' => true, 'label' => 'Odak anahtar kelime başlık ve içerikte geçiyor.'];
        } else {
            $score += 12;
            $checks[] = ['ok' => false, 'label' => 'Odak anahtar kelime hem başlıkta hem açıklamada kullanılmalı.'];
        }
    } else {
        $score += 8;
        $checks[] = ['ok' => false, 'label' => 'Odak anahtar kelime tanımlanmamış.'];
    }

    // 4. Clean URL slug (10 pts)
    if ($slug !== '' && strlen($slug) <= 75) {
        $score += 10;
        $checks[] = ['ok' => true, 'label' => 'SEO dostu temiz URL yapısı aktif.'];
    } else {
        $score += 5;
        $checks[] = ['ok' => false, 'label' => 'URL uzantısı (slug) daha kısa tutulabilir.'];
    }

    // 5. Visual media (10 pts)
    if ($image !== '') {
        $score += 10;
        $checks[] = ['ok' => true, 'label' => 'Kapak / OpenGraph görseli tanımlı.'];
    } else {
        $checks[] = ['ok' => false, 'label' => 'Kapak görseli seçilmemiş.'];
    }

    // 6. Content depth & headings (15 pts)
    $wordCount = $plain !== '' ? count(explode(' ', $plain)) : 0;
    $hasHeadings = (bool)preg_match('~<h[23]\b~i', $bodyHtml);
    if ($wordCount >= 180 && $hasHeadings) {
        $score += 15;
        $checks[] = ['ok' => true, 'label' => "Zengin içerik derinliği ({$wordCount} kelime) ve H2/H3 alt başlıklar mevcut."];
    } elseif ($wordCount >= 80) {
        $score += 10;
        $checks[] = ['ok' => false, 'label' => 'İçeriğe H2/H3 alt başlıklar ekleyerek okunabilirliği artırın.'];
    } else {
        $score += 5;
        $checks[] = ['ok' => false, 'label' => 'İçerik kelime sayısı artırılabilir (önerilen 250+ kelime).'];
    }

    // 7. AI SEO / GEO Summary & Structured FAQ (15 pts)
    if (mb_strlen($aiSummary, 'UTF-8') >= 40) {
        $score += 10;
        $checks[] = ['ok' => true, 'label' => 'Yapay Zeka (ChatGPT/Perplexity/Gemini) özet katmanı hazır.'];
    } else {
        $checks[] = ['ok' => false, 'label' => 'Yapay Zeka SEO (AI Özet) alanı doldurulmalı.'];
    }
    if (count($faqs) > 0) {
        $score += 5;
        $checks[] = ['ok' => true, 'label' => 'FAQPage (Soru-Cevap) zengin şema verisi aktif.'];
    } else {
        $score += 3;
        $checks[] = ['ok' => false, 'label' => 'Soru-Cevap (SSS) ekleyerek Google ve AI yanıt kutularında öne çıkabilirsiniz.'];
    }

    return [
        'score' => min(100, $score),
        'checks' => $checks,
    ];
}

function generate_ai_seo_pack(array $store, string $title, string $contentHtml, string $category = 'GENEL'): array
{
    $aiCfg = $store['ai_seo'] ?? [];
    $provider = (string)($aiCfg['ai_provider'] ?? 'smart_local');
    $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($contentHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

    // Optional external OpenAI API call if configured
    if ($provider === 'openai' && !empty($aiCfg['openai_api_key']) && function_exists('curl_init')) {
        $prompt = "Aşağıdaki Türkçe içerik için JSON formatında SEO ve Yapay Zeka SEO (GEO) paketi üret. Sadece geçerli JSON döndür: {\"meta_title\":\"...\",\"meta_desc\":\"...\",\"focus_keyword\":\"...\",\"ai_summary\":\"...\",\"faqs\":[{\"q\":\"...\",\"a\":\"...\"}]}\nBaşlık: {$title}\nKategori: {$category}\nİçerik: " . mb_substr($plain, 0, 2500, 'UTF-8');
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim((string)$aiCfg['openai_api_key']),
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => (string)($aiCfg['ai_model'] ?? 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'Sen uzman bir Türkçe Teknik SEO ve Yapay Zeka Arama (GEO) stratejistisin.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
            ]),
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);
        if (is_string($resp) && $code === 200) {
            $json = json_decode($resp, true);
            $inner = json_decode((string)($json['choices'][0]['message']['content'] ?? ''), true);
            if (is_array($inner) && !empty($inner['meta_title'])) {
                $inner['slug'] = slugify($title);
                $inner['source'] = 'OpenAI API';
                return $inner;
            }
        }
    }

    // Built-in Zero-Dependency Smart Turkish NLP SEO & GEO Generator
    $cleanTitle = trim($title) !== '' ? trim($title) : 'Dijital Çözümler Rehberi';
    $stopWords = ['için', 'olan', 'gibi', 'daha', 'nasıl', 'nedir', 'neden', 'ile', 'veya', 'bir', 'bu', 'de', 'da', 've', 'mi', 'mı', 'mu', 'mü', 'ne', 'kadar', 'üzerine'];
    $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($cleanTitle . ' ' . mb_substr($plain, 0, 600, 'UTF-8'), 'UTF-8')) ?: [];
    $freq = [];
    foreach ($words as $w) {
        if (mb_strlen($w, 'UTF-8') < 4 || in_array($w, $stopWords, true)) {
            continue;
        }
        $freq[$w] = ($freq[$w] ?? 0) + 1;
    }
    arsort($freq);
    $topKeywords = array_slice(array_keys($freq), 0, 3);
    $focusKeyword = $topKeywords !== [] ? implode(' ', array_slice($topKeywords, 0, 2)) : mb_strtolower($category, 'UTF-8');

    $metaTitle = mb_strlen($cleanTitle, 'UTF-8') <= 52
        ? $cleanTitle . ' | ZEYNART'
        : mb_substr($cleanTitle, 0, 55, 'UTF-8') . ' | ZEYNART';

    $sentences = preg_split('/(?<=[.!?])\s+/u', $plain) ?: [];
    $firstSentence = trim((string)($sentences[0] ?? ''));
    $secondSentence = trim((string)($sentences[1] ?? ''));

    if ($firstSentence !== '') {
        $metaDesc = $firstSentence . ($secondSentence !== '' ? ' ' . $secondSentence : '');
        if (mb_strlen($metaDesc, 'UTF-8') > 156) {
            $metaDesc = rtrim(mb_substr($metaDesc, 0, 153, 'UTF-8')) . '...';
        }
        if (mb_strlen($metaDesc, 'UTF-8') < 85) {
            $metaDesc .= ' Zeynart Web Bilişim uzman rehberi ve uygulama önerilerini keşfedin.';
            if (mb_strlen($metaDesc, 'UTF-8') > 158) {
                $metaDesc = rtrim(mb_substr($metaDesc, 0, 155, 'UTF-8')) . '...';
            }
        }
    } else {
        $metaDesc = "{$cleanTitle} hakkında bilmeniz gereken temel stratejiler, uygulama adımları ve Zeynart Web Bilişim uzman önerileri.";
    }

    $aiSummary = $firstSentence !== ''
        ? trim($firstSentence . ' ' . $secondSentence)
        : "{$cleanTitle}; işletmelerin dijital görünürlüğünü, kullanıcı deneyimini ve teknik sürdürülebilirliğini güçlendirmeye odaklanan kapsamlı bir Zeynart rehberidir.";
    if (mb_strlen($aiSummary, 'UTF-8') > 280) {
        $aiSummary = rtrim(mb_substr($aiSummary, 0, 277, 'UTF-8')) . '...';
    }

    // Extract headings to build contextual Q&A for FAQPage Schema (word-boundary safe, natural Turkish phrasing)
    $faqs = [];
    if (preg_match_all('~<h[23][^>]*>(.*?)</h[23]>\s*(<p[^>]*>.*?</p>(?:\s*<p[^>]*>.*?</p>)?)~isu', $contentHtml, $hm, PREG_SET_ORDER)) {
        foreach (array_slice($hm, 0, 3) as $match) {
            $hq = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $haRaw = preg_replace('~</p>\s*<p[^>]*>~iu', ' ', (string)$match[2]) ?? (string)$match[2];
            $ha = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($haRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            if ($hq !== '' && $ha !== '') {
                $hq = preg_replace('/^\d+[\.\)\-\s]+/u', '', $hq) ?? $hq;
                if (!str_ends_with($hq, '?')) {
                    $hqClean = rtrim($hq, '.:;,- ');
                    if (str_contains($hqClean, ':')) {
                        $hq = $hqClean . ' yaklaşımı neyi ifade eder?';
                    } elseif (mb_strlen($hqClean, 'UTF-8') > 44) {
                        $hq = $hqClean . ' neden kritik öneme sahiptir?';
                    } else {
                        $hq = $hqClean . ' sürecinde nasıl bir yaklaşım izlenir?';
                    }
                }
                if (mb_strlen($ha, 'UTF-8') > 340) {
                    $slice = mb_substr($ha, 0, 340, 'UTF-8');
                    $sentEnd = max(
                        (int)mb_strrpos($slice, '.', 0, 'UTF-8'),
                        (int)mb_strrpos($slice, '!', 0, 'UTF-8'),
                        (int)mb_strrpos($slice, '?', 0, 'UTF-8')
                    );
                    if ($sentEnd >= 120) {
                        $ha = mb_substr($slice, 0, $sentEnd + 1, 'UTF-8');
                    } else {
                        $lastSpace = mb_strrpos($slice, ' ', 0, 'UTF-8');
                        $ha = rtrim($lastSpace !== false ? mb_substr($slice, 0, $lastSpace, 'UTF-8') : $slice, ' ,;:-') . '…';
                    }
                }
                $faqs[] = [
                    'q' => $hq,
                    'a' => $ha,
                ];
            }
        }
    }
    if ($faqs === []) {
        $faqs = [
            [
                'q' => $cleanTitle . ' neden önemlidir?',
                'a' => $aiSummary,
            ],
            [
                'q' => 'Zeynart bu süreçte nasıl destek sağlar?',
                'a' => 'Zeynart Web Bilişim; keşif, strateji, tasarım, yazılım geliştirme ve yayın sonrası teknik destek adımlarını uçtan uca yönetir.',
            ],
        ];
    }

    return [
        'slug' => slugify($cleanTitle),
        'meta_title' => $metaTitle,
        'meta_desc' => $metaDesc,
        'focus_keyword' => $focusKeyword,
        'ai_summary' => $aiSummary,
        'faqs' => $faqs,
        'source' => 'Zeynart Akıllı Türkçe SEO Motoru',
    ];
}

function compile_ai_catalog_json(array $store): string
{
    $site = $store['site'] ?? [];
    $base = rtrim(canonical_url('/', $store), '/');
    $hostOnly = parse_url($base, PHP_URL_HOST) ?: 'zeynart.com';
    $hostOnly = preg_replace('/[^a-zA-Z0-9\.\-]/', '', (string)$hostOnly) ?: 'zeynart.com';

    $manifest = [
        'specVersion' => '1.0',
        'host' => [
            'displayName' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
            'identifier' => 'did:web:' . $hostOnly,
            'documentationUrl' => $base . '/llms.txt',
            'logoUrl' => canonical_url((string)($site['favicon'] ?? '/icon.png'), $store),
        ],
        'entries' => [
            [
                'identifier' => 'urn:air:' . $hostOnly . ':mcp:web-studio',
                'displayName' => (string)($site['name'] ?? 'ZEYNART') . ' WebMCP Dijital Hizmet & Blog Arama Araçları',
                'type' => 'application/mcp-server-card+json',
                'url' => $base . '/.well-known/mcp-server-card.json',
                'representativeQueries' => [
                    'Zeynart web tasarım ve özel yazılım hizmetleri nelerdir',
                    'kurumsal web sitesi veya e-ticaret projesi için teklif talebi oluştur',
                    'Zeynart blog arşivinde tasarım yazılım ve SEO yazılarını ara',
                    'bulut sunucu ve kurumsal hosting paketlerini karşılaştır',
                ],
            ],
            [
                'identifier' => 'urn:air:' . $hostOnly . ':agents:digital-advisor',
                'displayName' => (string)($site['name'] ?? 'ZEYNART') . ' Kurumsal Hizmet ve Bilgi Rehberi',
                'type' => 'application/a2a-agent-card+json',
                'url' => $base . '/.well-known/agent-card.json',
                'representativeQueries' => [
                    'Zeynart iletişim bilgileri ve proje geliştirme adımları',
                    'mobil uygulama ve kurumsal kimlik tasarımı teslimatları',
                    'Zeynart kurumsal referansları ve örnek projeleri',
                ],
            ],
        ],
    ];

    return (string)json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
}

function compile_mcp_server_card_json(array $store): string
{
    $site = $store['site'] ?? [];
    $base = rtrim(canonical_url('/', $store), '/');

    $card = [
        'name' => 'zeynart-webmcp',
        'displayName' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
        'version' => '1.0.0',
        'description' => 'Zeynart kurumsal hizmet bilgisi, 1.680+ makalelik teknoloji/tasarım blog araması ve proje teklif formu etkileşim araçları.',
        'homepageUrl' => $base . '/',
        'documentationUrl' => $base . '/llms-full.txt',
        'transport' => [
            'type' => 'webmcp',
            'url' => $base . '/',
        ],
        'tools' => [
            [
                'name' => 'search_zeynart_blog',
                'description' => 'Zeynart Journal arşivinde anahtar kelimeye ve kategoriye göre blog makalesi arar.',
                'endpoint' => $base . '/blog/?format=json',
            ],
            [
                'name' => 'get_zeynart_services_and_info',
                'description' => 'Zeynart hizmetlerini, hosting paketlerini ve kurumsal iletişim bilgilerini getirir.',
                'endpoint' => $base . '/llms.txt',
            ],
            [
                'name' => 'submit_project_inquiry',
                'description' => 'İletişim sayfasındaki proje talep formu üzerinden teklif başvurusu iletir.',
                'endpoint' => $base . '/iletisim/',
            ],
        ],
    ];

    return (string)json_encode($card, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
}

function compile_agent_card_json(array $store): string
{
    $site = $store['site'] ?? [];
    $base = rtrim(canonical_url('/', $store), '/');

    $card = [
        'name' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
        'description' => (string)($store['seo']['default_description'] ?? 'Web tasarım, grafik tasarım, özel yazılım, mobil uygulama, e-ticaret ve bulut hosting çözümleri.'),
        'url' => $base . '/',
        'version' => '1.0.0',
        'documentationUrl' => $base . '/llms.txt',
        'provider' => [
            'organization' => (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri'),
            'url' => $base . '/',
        ],
        'capabilities' => [
            'streaming' => false,
            'pushNotifications' => false,
        ],
        'skills' => [
            [
                'id' => 'services-discovery',
                'name' => 'Dijital Hizmetler ve Hosting Paketleri',
                'description' => 'Web tasarım, özel yazılım, mobil uygulama, e-ticaret ve bulut hosting paket detaylarını sunar.',
                'tags' => ['web-tasarim', 'yazilim', 'e-ticaret', 'hosting'],
            ],
            [
                'id' => 'journal-search',
                'name' => 'Zeynart Journal Bilgi Bankası Araması',
                'description' => 'Tasarım, yazılım ve teknoloji rehberlerinde arama yapar.',
                'tags' => ['blog', 'rehber', 'teknoloji', 'seo'],
            ],
        ],
    ];

    return (string)json_encode($card, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
}

function sync_static_ai_manifests(array $store): void
{
    // Keep RFC 8615 /.well-known/ manifests synced on disk (for Plesk/Nginx direct static /.well-known/ serving),
    // while keeping ROOT_DIR 100% clean (never write ai-catalog.json or loose .php endpoints into root).
    $wellKnown = ROOT_DIR . '/.well-known';
    if (!is_dir($wellKnown)) {
        @mkdir($wellKnown, 0775, true);
    }
    $catalogJson = compile_ai_catalog_json($store);
    @file_put_contents($wellKnown . '/ai-catalog.json', $catalogJson);
    @file_put_contents($wellKnown . '/ard.json', $catalogJson);
    @file_put_contents($wellKnown . '/mcp-server-card.json', compile_mcp_server_card_json($store));
    @file_put_contents($wellKnown . '/agent-card.json', compile_agent_card_json($store));

    // Self-heal: purge any legacy root clutter files if left behind from an older deployment.
    $legacyRootFiles = [
        ROOT_DIR . '/ai-catalog.json',
        ROOT_DIR . '/site-analiz-api.php',
        ROOT_DIR . '/teklif-sihirbazi-api.php',
        ROOT_DIR . '/uptime-cron.php',
        ROOT_DIR . '/haber-otopilot-cron.php',
        ROOT_DIR . '/analitik-nabiz.php',
        ROOT_DIR . '/iletisim-gonder.php',
    ];
    foreach ($legacyRootFiles as $lf) {
        if (is_file($lf)) {
            @unlink($lf);
        }
    }
}
