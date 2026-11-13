<?php
declare(strict_types=1);

/** @var string $sitemapRoute */
$base = rtrim(canonical_url('/', $store, 'tr'), '/');
$today = date('Y-m-d');

if ($sitemapRoute === 'sitemap.css' || $sitemapRoute === 'sitemap.xsl') {
    header('Location: ' . url('/sitemap.xml'), true, 301);
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');

if ($sitemapRoute === 'sitemap.xml') {
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo '  <sitemap><loc>' . e($base . '/sitemap-pages.xml') . '</loc><lastmod>' . $today . '</lastmod></sitemap>' . "\n";
    echo '  <sitemap><loc>' . e($base . '/sitemap-blog.xml') . '</loc><lastmod>' . $today . '</lastmod></sitemap>' . "\n";
    echo '</sitemapindex>' . "\n";
    exit;
}

if ($sitemapRoute === 'sitemap-pages.xml') {
    $resolvedSlugs = zeynart_resolved_page_slugs($store);
    $pagePaths = [
        ['path' => '/', 'freq' => 'weekly', 'prio' => '1.0', 'mod' => $today],
    ];
    $coreSitemapCandidates = [
        'hizmetler' => ['freq' => 'weekly', 'prio' => '0.9'],
        'hakkimizda' => ['freq' => 'monthly', 'prio' => '0.8'],
        'projeler' => ['freq' => 'monthly', 'prio' => '0.8'],
        'blog' => ['freq' => 'daily', 'prio' => '0.9'],
        'site-analiz' => ['freq' => 'weekly', 'prio' => '0.88'],
        'teklif-sihirbazi' => ['freq' => 'weekly', 'prio' => '0.88'],
        'gorusme-planla' => ['freq' => 'weekly', 'prio' => '0.86'],
        'iletisim' => ['freq' => 'monthly', 'prio' => '0.8'],
    ];
    foreach ($coreSitemapCandidates as $cKey => $cMeta) {
        if (zeynart_page_status($store, $cKey) === 'published') {
            $pagePaths[] = ['path' => '/' . $resolvedSlugs[$cKey] . '/', 'freq' => $cMeta['freq'], 'prio' => $cMeta['prio'], 'mod' => $today];
        }
    }
    if (zeynart_page_status($store, 'hizmetler') === 'published') {
        foreach (zeynart_public_services($store) as $srv) {
            $pagePaths[] = ['path' => '/' . $resolvedSlugs['hizmetler'] . '/' . ($srv['slug'] ?? '') . '/', 'freq' => 'monthly', 'prio' => '0.85', 'mod' => $today];
        }
    }
    if (zeynart_page_status($store, 'projeler') === 'published') {
        foreach (zeynart_public_projects($store) as $prj) {
            $pagePaths[] = ['path' => '/' . $resolvedSlugs['projeler'] . '/' . ($prj['slug'] ?? '') . '/', 'freq' => 'monthly', 'prio' => '0.75', 'mod' => $today];
        }
    }
    foreach (($store['custom_pages'] ?? []) as $cp) {
        if (zeynart_normalize_page_status($cp['status'] ?? 'published') === 'published') {
            $pagePaths[] = ['path' => '/' . ($cp['slug'] ?? '') . '/', 'freq' => 'weekly', 'prio' => '0.80', 'mod' => $today];
        }
    }
    require_once __DIR__ . '/../includes/agency_powerhouse.php';
    foreach (zeynart_get_vcards_list($store) as $vcItem) {
        if (($vcItem['status'] ?? 'published') === 'published' && empty($vcItem['noindex']) && !empty($vcItem['slug'])) {
            $pagePaths[] = ['path' => '/kartvizit/' . $vcItem['slug'] . '/', 'freq' => 'monthly', 'prio' => '0.65', 'mod' => $today];
        }
    }
    foreach (['gizlilik', 'kvkk'] as $lg) {
        if (zeynart_page_status($store, $lg) === 'published') {
            $pagePaths[] = ['path' => '/' . $resolvedSlugs[$lg] . '/', 'freq' => 'yearly', 'prio' => '0.35', 'mod' => $today];
        }
    }

    $urls = [];
    foreach (zeynart_public_languages($store) as $lCode) {
        foreach ($pagePaths as $pp) {
            $loc = lang_canonical_url($pp['path'], $store, $lCode);
            $alts = [
                'tr' => lang_canonical_url($pp['path'], $store, 'tr'),
                'en' => lang_canonical_url($pp['path'], $store, 'en'),
                'de' => lang_canonical_url($pp['path'], $store, 'de'),
                'ru' => lang_canonical_url($pp['path'], $store, 'ru'),
            ];
            $alts = array_intersect_key($alts, array_flip(zeynart_public_languages($store)));
            $urls[] = ['loc' => $loc, 'freq' => $pp['freq'], 'prio' => $pp['prio'], 'mod' => $pp['mod'], 'alts' => $alts];
        }
    }

    // Mandatory Cookie Policy routes (active slug + /en/cookie-policy/ + /cookie-policy/)
    if (zeynart_page_status($store, 'cerez-politikasi') === 'published') {
        $cpActivePath = '/' . $resolvedSlugs['cerez-politikasi'] . '/';
        $cookieAlts = [
            'tr' => lang_canonical_url($cpActivePath, $store, 'tr'),
            'en' => lang_canonical_url(($resolvedSlugs['cerez-politikasi'] === 'cerez-politikasi') ? '/cookie-policy/' : $cpActivePath, $store, 'en'),
            'de' => lang_canonical_url($cpActivePath, $store, 'de'),
            'ru' => lang_canonical_url($cpActivePath, $store, 'ru'),
        ];
        foreach ($cookieAlts as $cLoc) {
            $urls[] = ['loc' => $cLoc, 'freq' => 'yearly', 'prio' => '0.45', 'mod' => $today, 'alts' => $cookieAlts];
        }
        $urls[] = ['loc' => lang_canonical_url('/cookie-policy/', $store, 'tr'), 'freq' => 'yearly', 'prio' => '0.40', 'mod' => $today, 'alts' => $cookieAlts];
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach ($urls as $u) {
        echo '  <url><loc>' . e($u['loc']) . '</loc>';
        foreach ($u['alts'] as $hl => $href) {
            echo '<xhtml:link rel="alternate" hreflang="' . e($hl) . '" href="' . e($href) . '"/>';
        }
        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . e($u['alts']['tr']) . '"/>';
        echo '<lastmod>' . e($u['mod']) . '</lastmod><changefreq>' . e($u['freq']) . '</changefreq><priority>' . e($u['prio']) . '</priority></url>' . "\n";
    }
    echo '</urlset>' . "\n";
    exit;
}

// sitemap-blog.xml (Blog Categories in TR/EN/DE/RU + all published blog posts with xhtml:link hreflang)
$blogIndex = load_blog_index();
$blogBaseSlug = zeynart_page_slug($store, 'blog');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
if (zeynart_page_status($store, 'blog') === 'published') {
    $blogCatsTree = zeynart_build_blog_category_tree(zeynart_get_blog_categories($store));
    foreach ($blogCatsTree as $bCat) {
        $catSlug = trim((string)($bCat['slug'] ?? ''));
        if ($catSlug === '') {
            continue;
        }
        $catPath = '/' . $blogBaseSlug . '/kategori/' . $catSlug . '/';
        $catAlts = [
            'tr' => lang_canonical_url($catPath, $store, 'tr'),
            'en' => lang_canonical_url($catPath, $store, 'en'),
            'de' => lang_canonical_url($catPath, $store, 'de'),
            'ru' => lang_canonical_url($catPath, $store, 'ru'),
        ];
        $catXhtml = '';
        foreach ($catAlts as $hl => $href) {
            $catXhtml .= '<xhtml:link rel="alternate" hreflang="' . e($hl) . '" href="' . e($href) . '"/>';
        }
        $catXhtml .= '<xhtml:link rel="alternate" hreflang="x-default" href="' . e($catAlts['tr']) . '"/>';
        $catPrio = !empty($bCat['depth']) ? '0.72' : '0.78';
        foreach (['tr', 'en', 'de', 'ru'] as $lCode) {
            echo '  <url><loc>' . e($catAlts[$lCode]) . '</loc>' . $catXhtml . '<lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>' . $catPrio . '</priority></url>' . "\n";
        }
    }
}
foreach ($blogIndex as $item) {
    if (($item['status'] ?? 'published') !== 'published') {
        continue;
    }
    $trSlug = (string)($item['slug'] ?? '');
    $loc = $base . '/' . $blogBaseSlug . '/' . $trSlug . '/';
    $mod = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($item['date'] ?? '')) ? (string)$item['date'] : $today;
    $prio = !empty($item['curated']) ? '0.80' : '0.65';
    $hasI18n = !empty($item['i18n']['en']['slug']) || !empty($item['i18n']['de']['slug']) || !empty($item['i18n']['ru']['slug']);
    $alts = [
        'tr' => $loc,
        'en' => $base . '/en/' . $blogBaseSlug . '/' . ($item['i18n']['en']['slug'] ?? $trSlug) . '/',
        'de' => $base . '/de/' . $blogBaseSlug . '/' . ($item['i18n']['de']['slug'] ?? $trSlug) . '/',
        'ru' => $base . '/ru/' . $blogBaseSlug . '/' . ($item['i18n']['ru']['slug'] ?? $trSlug) . '/',
    ];
    $xhtmlLinks = '';
    if ($hasI18n) {
        foreach ($alts as $hl => $href) {
            $xhtmlLinks .= '<xhtml:link rel="alternate" hreflang="' . e($hl) . '" href="' . e($href) . '"/>';
        }
        $xhtmlLinks .= '<xhtml:link rel="alternate" hreflang="x-default" href="' . e($loc) . '"/>';
    }
    echo '  <url><loc>' . e($loc) . '</loc>' . $xhtmlLinks . '<lastmod>' . e($mod) . '</lastmod><changefreq>monthly</changefreq><priority>' . $prio . '</priority></url>' . "\n";
    foreach (['en', 'de', 'ru'] as $lCode) {
        if (!empty($item['i18n'][$lCode]['slug'])) {
            $trLoc = $alts[$lCode];
            echo '  <url><loc>' . e($trLoc) . '</loc>' . $xhtmlLinks . '<lastmod>' . e($mod) . '</lastmod><changefreq>monthly</changefreq><priority>' . $prio . '</priority></url>' . "\n";
        }
    }
}
echo '</urlset>' . "\n";
