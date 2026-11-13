<?php
declare(strict_types=1);

$curLang = zeynart_current_lang();
if ($curLang !== 'tr') {
    require_once INCLUDES_DIR . '/translator.php';
}

$bs = is_array($store['blog_settings'] ?? null) ? $store['blog_settings'] : [];
$seoPage = $store['seo']['pages']['blog'] ?? [];
$siteName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
$blogSlug = zeynart_page_slug($store, 'blog') ?: 'blog';
$blogDisplayTitle = trim((string)($store['page_titles']['blog'] ?? $bs['heading'] ?? ''));
if ($blogDisplayTitle === '') {
    $blogDisplayTitle = 'Blog';
}

$canonicalTree = zeynart_build_blog_category_tree(zeynart_get_blog_categories($rawStore ?? $store));
$published = array_map(
    static fn(array $p): array => zeynart_enrich_post_category_fields($p, $canonicalTree),
    array_values(array_filter(
        load_blog_index(),
        static fn(array $p): bool => is_post_publicly_visible($p, !empty($canPreviewDrafts))
    ))
);

// Build localized hierarchical category list & counts from centralized Admin blog_categories
$treeCategories = zeynart_build_blog_category_tree(zeynart_get_blog_categories($store));
$catCounts = zeynart_count_posts_per_category($treeCategories, $published, false);
$catById = [];
$catBySlug = [];
$categories = [];
foreach ($treeCategories as $tc) {
    $cid = (string)$tc['id'];
    $cSlug = (string)$tc['slug'];
    $directCnt = (int)($catCounts[$cid]['direct'] ?? 0);
    $totalCnt = (int)($catCounts[$cid]['total'] ?? 0);
    $enrichedCat = array_merge($tc, [
        'label' => (string)$tc['name'],
        'count' => $totalCnt,
        'direct_count' => $directCnt,
    ]);
    $catById[$cid] = $enrichedCat;
    $catBySlug[$cSlug] = $enrichedCat;
    $categories[] = $enrichedCat;
}

$activeCatObj = null;
if (isset($activeBlogCategory) && is_array($activeBlogCategory)) {
    $aId = (string)($activeBlogCategory['id'] ?? '');
    $aSlug = (string)($activeBlogCategory['slug'] ?? '');
    $activeCatObj = $catById[$aId] ?? $catBySlug[$aSlug] ?? $activeBlogCategory;
} else {
    $rawQueryCat = trim((string)($_GET['kategori'] ?? $_GET['cat'] ?? ''));
    if ($rawQueryCat !== '' && !zeynart_is_ignored_all_category_label($rawQueryCat)) {
        $queryCatSlug = slugify($rawQueryCat);
        if ($queryCatSlug !== '' && isset($catBySlug[$queryCatSlug])) {
            $activeCatObj = $catBySlug[$queryCatSlug];
        }
    }
}
$selectedCatSlug = $activeCatObj !== null ? (string)$activeCatObj['slug'] : '';

$searchQuery = trim((string)($_GET['ara'] ?? $_GET['q'] ?? ''));
$page = max(1, (int)($_GET['sayfa'] ?? $_GET['page'] ?? 1));
$perPage = max(6, (int)($bs['per_page'] ?? 24));

$filtered = $published;
if ($activeCatObj !== null) {
    $filtered = array_values(array_filter(
        $filtered,
        static fn(array $p): bool => zeynart_post_matches_category($p, $activeCatObj, $treeCategories)
    ));
}
if ($searchQuery !== '') {
    $qLower = mb_strtolower($searchQuery, 'UTF-8');
    $filtered = array_values(array_filter(
        $filtered,
        static function (array $p) use ($qLower, $curLang): bool {
            $catsStr = is_array($p['categories'] ?? null) ? implode(' ', $p['categories']) : '';
            $haystack = ($p['title'] ?? '') . ' ' . ($p['excerpt'] ?? '') . ' ' . ($p['category'] ?? '') . ' ' . $catsStr;
            if ($curLang !== 'tr' && !empty($p['i18n'][$curLang])) {
                $haystack .= ' ' . ($p['i18n'][$curLang]['title'] ?? '') . ' ' . ($p['i18n'][$curLang]['excerpt'] ?? '') . ' ' . ($p['i18n'][$curLang]['category'] ?? '');
            }
            return str_contains(mb_strtolower($haystack, 'UTF-8'), $qLower);
        }
    ));
}

$totalFiltered = count($filtered);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$slice = array_slice($filtered, $offset, $perPage);
if ($curLang !== 'tr' && $slice !== []) {
    $slice = zeynart_localize_blog_cards($slice, $curLang, $store);
}

// Lightweight JSON endpoint for instant client-side search / category filter / load-more
if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: public, max-age=60');
    echo json_encode([
        'total' => $totalFiltered,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'active_category' => $selectedCatSlug,
        'items' => array_map(static function (array $it) use ($blogSlug, $treeCategories): array {
            $resCats = zeynart_resolve_post_categories($it, $treeCategories);
            $catNames = array_values(array_map(static fn(array $c): string => (string)$c['name'], $resCats));
            $catSlugs = array_values(array_map(static fn(array $c): string => (string)$c['slug'], $resCats));
            return [
                'slug' => $it['slug'] ?? '',
                'url' => url('/' . $blogSlug . '/' . ($it['slug'] ?? '') . '/'),
                'title' => $it['title'] ?? '',
                'category' => $catNames[0] ?? ($it['category'] ?? 'Genel'),
                'categories' => $catNames,
                'category_slugs' => $catSlugs,
                'excerpt' => $it['excerpt'] ?? '',
                'date' => $it['date'] ?? '',
                'date_display' => $it['date_display'] ?? '',
                'read' => $it['read'] ?? '3 dk',
                'image' => url((string)($it['image'] ?? '/images/journal/web-planning.webp')),
            ];
        }, $slice),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Determine Featured Post (shown on root /blog/ page 1 when unfiltered)
$featuredPost = null;
if ($activeCatObj === null && $page === 1 && $searchQuery === '') {
    foreach ($published as $p) {
        if (!empty($p['featured'])) {
            $featuredPost = $p;
            break;
        }
    }
    if ($featuredPost === null && $published !== []) {
        $featuredPost = $published[0];
    }
    if ($featuredPost !== null && $curLang !== 'tr') {
        $locFeat = zeynart_localize_blog_cards([$featuredPost], $curLang, $store);
        $featuredPost = $locFeat[0] ?? $featuredPost;
    }
}

$directChildCategories = [];
if ($activeCatObj !== null) {
    $activeIdStr = (string)($activeCatObj['id'] ?? '');
    foreach ($categories as $candChild) {
        if ((string)($candChild['parent_id'] ?? '') === $activeIdStr) {
            $directChildCategories[] = $candChild;
        }
    }
}

$breadcrumbs = [
    ['name' => t('Ana sayfa'), 'url' => '/'],
    ['name' => t($blogDisplayTitle), 'url' => '/' . $blogSlug . '/'],
];

if ($activeCatObj !== null) {
    $catCanonicalBase = '/' . $blogSlug . '/kategori/' . $activeCatObj['slug'] . '/';
    foreach (($activeCatObj['ancestor_breadcrumbs'] ?? []) as $ancCrumb) {
        if (!empty($ancCrumb['slug']) && !empty($ancCrumb['name'])) {
            $ancObj = $catBySlug[(string)$ancCrumb['slug']] ?? null;
            $breadcrumbs[] = [
                'name' => (string)($ancObj['name'] ?? $ancCrumb['name']),
                'url' => '/' . $blogSlug . '/kategori/' . $ancCrumb['slug'] . '/',
            ];
        }
    }
    $breadcrumbs[] = [
        'name' => (string)$activeCatObj['name'],
        'url' => $catCanonicalBase,
    ];
    $catMetaTitle = trim((string)($activeCatObj['meta_title'] ?? ''))
        ?: ($activeCatObj['name'] . ' — Zeynart Journal | ' . $siteName);
    $catMetaDesc = trim((string)($activeCatObj['meta_desc'] ?? ''))
        ?: (trim((string)($activeCatObj['description'] ?? '')) ?: ($activeCatObj['name'] . ' — ' . ($bs['lead'] ?? t('Tasarım, teknoloji ve dijital dönüşüm rehberleri.'))));
    $firstCatImg = ($slice !== [])
        ? ((string)($slice[0]['image'] ?? '') ?: '/images/sections/journal-banner.webp')
        : '/images/sections/journal-banner.webp';

    $pageMeta = [
        'title' => ($page > 1 ? (t('Sayfa') . " {$page} — ") : '') . $catMetaTitle,
        'description' => $catMetaDesc,
        'og_title' => $catMetaTitle,
        'og_description' => $catMetaDesc,
        'og_image' => $firstCatImg,
        'robots' => ($searchQuery !== '') ? 'noindex, follow' : 'index, follow',
        'canonical_path' => $catCanonicalBase . ($page > 1 ? '?sayfa=' . $page : ''),
        'lang_alternates' => [
            'tr' => $catCanonicalBase,
            'en' => $catCanonicalBase,
            'de' => $catCanonicalBase,
            'ru' => $catCanonicalBase,
        ],
        'preload_image' => '',
        'ai_summary' => $catMetaDesc,
        'breadcrumbs' => $breadcrumbs,
    ];
} else {
    $blogCanonicalBase = '/' . $blogSlug . '/';
    $pageMeta = [
        'title' => ($page > 1 ? (t('Sayfa') . " {$page} — ") : '') . (($seoPage['meta_title'] ?? '') ?: (t($blogDisplayTitle) . ' — Zeynart Journal | ' . $siteName)),
        'description' => ($seoPage['meta_desc'] ?? '') ?: ($bs['lead'] ?? ''),
        'og_title' => $seoPage['og_title'] ?? '',
        'og_description' => $seoPage['og_desc'] ?? '',
        'og_image' => ($seoPage['og_image'] ?? '') ?: ($featuredPost['image'] ?? '/images/sections/journal-banner.webp'),
        'robots' => ($searchQuery !== '') ? 'noindex, follow' : (($seoPage['robots'] ?? '') ?: 'index, follow'),
        'canonical_path' => $blogCanonicalBase . ($page > 1 ? '?sayfa=' . $page : ''),
        'lang_alternates' => [
            'tr' => $blogCanonicalBase,
            'en' => $blogCanonicalBase,
            'de' => $blogCanonicalBase,
            'ru' => $blogCanonicalBase,
        ],
        'preload_image' => $featuredPost['image'] ?? '/images/journal/web-planning.webp',
        'ai_summary' => $seoPage['ai_summary'] ?? '',
        'breadcrumbs' => $breadcrumbs,
    ];
}

$activeBaseRoute = $activeCatObj !== null
    ? ('/' . $blogSlug . '/kategori/' . $activeCatObj['slug'] . '/')
    : ('/' . $blogSlug . '/');

require INCLUDES_DIR . '/site_header.php';
?>
<header class="container journal-header<?= $activeCatObj !== null ? ' is-category-hero' : '' ?>">
  <nav class="journal-cat-crumbs" aria-label="Breadcrumb">
    <?php foreach ($breadcrumbs as $bIdx => $crumb):
        $isLastCrumb = ($bIdx === count($breadcrumbs) - 1);
    ?>
      <?php if ($bIdx > 0): ?><span aria-hidden="true">/</span><?php endif; ?>
      <?php if ($isLastCrumb): ?>
        <span aria-current="page" class="is-current"><?= e($crumb['name']) ?></span>
      <?php else: ?>
        <a href="<?= e(url($crumb['url'])) ?>"><?= e($crumb['name']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <?php if ($activeCatObj !== null): ?>
    <div class="journal-kicker">
      <span class="eyebrow">
        <?= !empty($activeCatObj['parent_name']) ? e($activeCatObj['parent_name'] . ' / ' . t('ALT KATEGORİ')) : e('ZEYNART JOURNAL · ' . t('BLOG KATEGORİSİ')) ?>
      </span>
      <span><?= $totalFiltered ?> <?= e(t('YAZI')) ?></span>
    </div>
    <h1><?= e($activeCatObj['name']) ?></h1>
    <?php if ($directChildCategories !== []): ?>
      <div class="journal-subcat-strip" role="group" aria-label="<?= e(t('Alt kategoriler')) ?>">
        <?php foreach ($directChildCategories as $subCatItem): ?>
          <a href="<?= e(url('/' . $blogSlug . '/kategori/' . $subCatItem['slug'] . '/') . '#yazilar') ?>">
            <span>↳ <?= e($subCatItem['label']) ?></span>
            <small><?= (int)$subCatItem['count'] ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="journal-header-bottom">
      <p><?= nl2br_e(trim((string)($activeCatObj['description'] ?? '')) ?: ($activeCatObj['name'] . ' — ' . ($bs['lead'] ?? 'Dijital dünyayı birlikte anlamak için işinize ışık tutan rehberler ve analizler.'))) ?></p>
      <div class="journal-cat-meta-links">
        <?php if (!empty($activeCatObj['parent_name']) && !empty($activeCatObj['parent_slug'])): ?>
          <a href="<?= e(url('/' . $blogSlug . '/kategori/' . $activeCatObj['parent_slug'] . '/')) ?>">↑ <?= e($activeCatObj['parent_name']) ?></a>
        <?php endif; ?>
        <a href="<?= e(url('/' . $blogSlug . '/')) ?>">← <?= e(t('Tüm yazılar')) ?></a>
      </div>
    </div>
  <?php else: ?>
    <div class="journal-kicker">
      <span class="eyebrow"><?= e($bs['kicker_left'] ?? 'ZEYNART JOURNAL') ?></span>
      <span><?= e($bs['kicker_right'] ?? 'TASARIM. TEKNOLOJİ. BİR ADIM ÖTESİ.') ?></span>
    </div>
    <h1><?= e($bs['heading_line1'] ?? 'İyi fikirler,') ?><br/><span><?= e($bs['heading_span'] ?? 'paylaştıkça büyür.') ?></span></h1>
    <div class="journal-header-bottom">
      <p><?= nl2br_e((string)($bs['lead'] ?? "Dijital dünyayı birlikte anlamak için.\nİşinize ışık tutan rehberler, yaratıcı bakış açıları ve bizden notlar.")) ?></p>
      <span><?= e($bs['badge_right'] ?? 'KEŞFET / ÖĞREN / İLHAM AL') ?> <?= zeynart_icon('arrow-up-right', 19) ?></span>
    </div>
  <?php endif; ?>
</header>

<?php if ($featuredPost !== null):
    $fResolvedCats = zeynart_resolve_post_categories($featuredPost, $treeCategories);
    $fPrimaryCat = $fResolvedCats[0] ?? ['name' => ($featuredPost['category'] ?? 'Web Tasarım'), 'slug' => 'web-tasarim'];
?>
<section class="container journal-feature" aria-label="<?= e(t('Öne çıkan yazı')) ?>">
  <a href="<?= e(url('/' . $blogSlug . '/' . ($featuredPost['slug'] ?? '') . '/')) ?>">
    <div class="journal-feature-image">
      <img alt="<?= e($featuredPost['title'] ?? '') ?>" fetchpriority="high" width="1280" height="720" decoding="async" class="fill-img" src="<?= e(url((string)($featuredPost['image'] ?? '/images/journal/web-planning.webp'))) ?>"/>
    </div>
    <div class="journal-feature-copy">
      <div class="journal-feature-badges">
        <span class="journal-feature-pick"><?= e(t('EDİTÖRÜN SEÇİMİ / 01')) ?></span>
        <span class="eyebrow"><?= e(implode(' · ', array_map(static fn(array $c): string => (string)$c['name'], array_slice($fResolvedCats, 0, 2))) ?: (string)$fPrimaryCat['name']) ?></span>
      </div>
      <h2><?= e($featuredPost['title'] ?? '') ?></h2>
      <p><?= e($featuredPost['excerpt'] ?? '') ?></p>
      <div class="journal-feature-meta">
        <time datetime="<?= e($featuredPost['date'] ?? '') ?>"><?= e($featuredPost['date_display'] ?? '') ?></time>
        <span><?= zeynart_icon('clock-3', 13) ?><?= e($featuredPost['read'] ?? '2 dk') ?> <?= e(t('okuma')) ?></span>
      </div>
      <span class="journal-feature-link"><?= e(t('Yazıyı okumaya başlayın')) ?> <?= zeynart_icon('arrow-up-right', 23) ?></span>
    </div>
  </a>
</section>
<?php endif; ?>

<section class="container journal-library" id="yazilar" aria-labelledby="journal-library-title" data-journal-explorer data-api-url="<?= e(url($activeBaseRoute)) ?>" data-active-category="<?= e($selectedCatSlug) ?>" data-per-page="<?= $perPage ?>" data-current-page="<?= $page ?>" data-total-pages="<?= $totalPages ?>">
  <div class="journal-library-heading">
    <div>
      <p class="eyebrow"><?= e($activeCatObj !== null ? ($activeCatObj['name'] . ' · ' . t('ARŞİV')) : t('ZEYNART’TAN NOTLAR')) ?></p>
      <h2 id="journal-library-title"><?= e($activeCatObj !== null ? ($activeCatObj['name'] . ' ' . t('Yazıları')) : t('Keşfetmeye devam edin.')) ?></h2>
    </div>
    <form method="get" action="<?= e(url($activeBaseRoute)) ?>#yazilar" class="journal-search-form" style="margin:0" toolname="search_journal_articles" tooldescription="Zeynart Journal blog arşivindeki web tasarım, yazılım, e-ticaret, hosting ve teknoloji makalelerini anahtar kelimeye göre arar." toolautosubmit>
      <label class="journal-search">
        <?= zeynart_icon('search', 18) ?>
        <span class="sr-only"><?= e(t('Yazılarda ara')) ?></span>
        <input type="search" name="ara" data-journal-search placeholder="<?= e(t('Bir konu arayın…')) ?>" value="<?= e($searchQuery) ?>" toolparamdescription="Blog yazılarında aranacak konu, teknoloji veya anahtar kelime"/>
      </label>
      <button type="submit" class="sr-only"><?= e(t('Yazılarda ara')) ?></button>
    </form>
  </div>

  <nav class="journal-filters" aria-label="<?= e(t('Yazı kategorileri')) ?>" data-journal-filters>
    <a class="journal-filter-pill" href="<?= e(url('/' . $blogSlug . '/') . ($searchQuery !== '' ? '?ara=' . rawurlencode($searchQuery) : '') . '#yazilar') ?>" data-category-slug=""<?= $selectedCatSlug === '' ? ' aria-current="page"' : '' ?>>
      <span><?= e(t_category('TÜMÜ')) ?></span>
      <small><?= count($published) ?></small>
    </a>
    <?php foreach ($categories as $catItem):
        $cSlug = (string)$catItem['slug'];
        $isActiveCat = ($selectedCatSlug !== '' && $selectedCatSlug === $cSlug);
        $catHref = url('/' . $blogSlug . '/kategori/' . $cSlug . '/') . ($searchQuery !== '' ? '?ara=' . rawurlencode($searchQuery) : '') . '#yazilar';
        $depthPrefix = !empty($catItem['depth']) ? str_repeat('↳ ', (int)$catItem['depth']) : '';
    ?>
    <a class="journal-filter-pill<?= !empty($catItem['depth']) ? ' is-subcat' : '' ?>" href="<?= e($catHref) ?>" data-category-slug="<?= e($cSlug) ?>"<?= $isActiveCat ? ' aria-current="page"' : '' ?>>
      <span><?= e($depthPrefix . $catItem['label']) ?></span>
      <small><?= (int)$catItem['count'] ?></small>
    </a>
    <?php endforeach; ?>
  </nav>

  <p class="journal-results" aria-live="polite" data-journal-count><?= $totalFiltered ?> <?= e(t('yazı · Tasarım, teknoloji ve dijital üzerine')) ?></p>

  <div class="journal-card-grid" data-journal-grid>
    <?php if ($slice === []): ?>
      <p class="muted"><?= e(t('Aramanızla eşleşen bir yazı bulunamadı.')) ?></p>
    <?php else: ?>
      <?php foreach ($slice as $item):
          $itemCats = zeynart_resolve_post_categories($item, $treeCategories);
          $badgeLabel = implode(' · ', array_map(static fn(array $c): string => (string)$c['name'], array_slice($itemCats, 0, 2))) ?: (string)($item['category'] ?? 'Genel');
      ?>
      <a class="journal-card" href="<?= e(url('/' . $blogSlug . '/' . ($item['slug'] ?? '') . '/')) ?>">
        <div class="journal-card-cover">
          <img alt="" loading="lazy" decoding="async" class="fill-img" src="<?= e(url((string)($item['image'] ?? '/images/journal/web-planning.webp'))) ?>"/>
          <span><?= e($badgeLabel) ?></span>
          <i><?= zeynart_icon('arrow-up-right', 20) ?></i>
        </div>
        <div class="journal-card-copy">
          <div class="journal-card-meta">
            <time datetime="<?= e($item['date'] ?? '') ?>"><?= e($item['date_display'] ?? '') ?></time>
            <span><?= e($item['read'] ?? '3 dk') ?> <?= e(t('okuma')) ?></span>
          </div>
          <h3><?= e($item['title'] ?? '') ?></h3>
          <p><?= e($item['excerpt'] ?? '') ?></p>
          <span class="journal-read"><?= e(t('Yazıyı keşfet')) ?> <?= zeynart_icon('arrow-up-right', 16) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="journal-pagination-bar" data-journal-pagination>
    <?php if ($page > 1):
        $prevParams = array_filter(['sayfa' => $page - 1 > 1 ? $page - 1 : null, 'ara' => $searchQuery !== '' ? $searchQuery : null]);
        $prevUrl = $activeBaseRoute . ($prevParams ? '?' . http_build_query($prevParams) : '');
    ?>
    <a class="button" href="<?= e(url($prevUrl) . '#yazilar') ?>"><?= zeynart_icon('arrow-left', 16) ?> <?= e(t('Önceki sayfa')) ?></a>
    <?php endif; ?>

    <?php if ($page < $totalPages):
        $nextParams = array_filter(['sayfa' => $page + 1, 'ara' => $searchQuery !== '' ? $searchQuery : null]);
        $nextUrl = $activeBaseRoute . '?' . http_build_query($nextParams);
    ?>
    <a class="button button-primary" data-journal-loadmore href="<?= e(url($nextUrl) . '#yazilar') ?>"><?= e(t('Daha fazla yazı yükle')) ?> <?= zeynart_icon('arrow-down', 16) ?></a>
    <?php endif; ?>

    <span class="journal-page-info" data-journal-pageinfo><?= e(t('Sayfa')) ?> <?= $page ?> / <?= $totalPages ?></span>
  </div>
  <?php endif; ?>
</section>
<?php require_once INCLUDES_DIR . '/page_builder.php'; ?>
<?= render_builder_sections($store['core_page_blocks']['blog'] ?? [], $store) ?>
<?= render_global_cta($store) ?>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
