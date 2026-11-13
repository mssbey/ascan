<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/cookie_consent.php';
require_once __DIR__ . '/../includes/social_card.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/** @var string $legalKey */
$lp = $store['legal_pages'][$legalKey] ?? [];
if ($lp === [] && $legalKey === 'cerez-politikasi') {
    $lp = zeynart_default_cookie_policy_page($store['site'] ?? []);
}
if (zeynart_current_lang() !== 'tr') {
    $builtinCookiePol = ($legalKey === 'cerez-politikasi')
        ? zeynart_builtin_localized_cookie_policy($lp, zeynart_current_lang(), $store['site'] ?? [])
        : null;
    $lp = $builtinCookiePol ?? zeynart_localize_legal_page($lp, $legalKey, zeynart_current_lang(), $store);
}
$seoPage = $store['seo']['pages'][$legalKey] ?? ($lp['seo'] ?? []);
$legalPlainFallback = zeynart_extract_plain_excerpt((string)($lp['content_html'] ?? ''), 180);

$isCookiePolicy = ($legalKey === 'cerez-politikasi');
$activeLegalSlug = zeynart_page_slug($store, $legalKey);
$canonicalLegalPath = ($isCookiePolicy && zeynart_current_lang() === 'en' && ($currentRoutePath ?? '') === '/cookie-policy/')
    ? '/cookie-policy/'
    : ('/' . $activeLegalSlug . '/');

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: (($lp['meta_title'] ?? '') ?: (($lp['title'] ?? 'Bilgilendirme') . ' | ' . ($store['site']['name'] ?? ''))),
    'description' => ($seoPage['meta_desc'] ?? '') ?: (($lp['meta_desc'] ?? '') ?: (($lp['lead'] ?? '') ?: $legalPlainFallback)),
    'og_title' => $seoPage['og_title'] ?? '',
    'og_description' => $seoPage['og_desc'] ?? '',
    'keywords' => $seoPage['focus_keyword'] ?? '',
    'robots' => ($seoPage['robots'] ?? '') ?: (($lp['robots'] ?? '') ?: 'index, follow'),
    'canonical_path' => $canonicalLegalPath,
    'lang_alternates' => $isCookiePolicy ? [
        'tr' => '/' . $activeLegalSlug . '/',
        'en' => ($activeLegalSlug === 'cerez-politikasi') ? '/cookie-policy/' : ('/' . $activeLegalSlug . '/'),
        'de' => '/' . $activeLegalSlug . '/',
        'ru' => '/' . $activeLegalSlug . '/',
    ] : [],
    'og_image' => ($seoPage['og_image'] ?? '') ?: ($lp['banner_image'] ?? ''),
    'ai_summary' => ($seoPage['ai_summary'] ?? '') ?: ($lp['lead'] ?? ''),
    'breadcrumbs' => [
        ['name' => t('Ana sayfa'), 'url' => '/'],
        ['name' => (string)($lp['title'] ?? ''), 'url' => $canonicalLegalPath],
    ],
];

require INCLUDES_DIR . '/site_header.php';
$bannerImg = trim((string)($lp['banner_image'] ?? ''));
$richHtml = trim((string)($lp['content_html'] ?? ''));
?>
<section class="thead" aria-labelledby="lg-h">
  <div class="wrap thead__in">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e((string)($lp['title'] ?? '')) ?></span></nav>
    <h1 id="lg-h" class="thead__h"><?= e((string)($lp['title'] ?? '')) ?></h1>
    <?php if (!empty($lp['lead'])): ?><p class="thead__lead"><?= e((string)$lp['lead']) ?></p><?php endif; ?>
  </div>
</section>
<?php if ($richHtml !== ''): ?>
<section class="wrap tbody tbody--legal"><div class="prose"><?= $richHtml ?></div></section>
<?php endif; ?>
<?= render_builder_sections(is_array($lp['sections'] ?? null) ? $lp['sections'] : [], $store) ?>
<?php require INCLUDES_DIR . '/site_footer.php';
