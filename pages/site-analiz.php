<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/site_audit.php';
require_once __DIR__ . '/../includes/page_builder.php';

$auditCfg = get_site_audit_state($store);
$seoPage = $store['seo']['pages']['site_analiz'] ?? [];

$pageTitleDefault = t('Ücretsiz Site Hızı & SEO Ön Analiz Aracı | ZEYNART');
$pageDescDefault = t('Mevcut web sitenizin SSL, sunucu hızı (TTFB), mobil uyumluluk ve temel SEO eksiklerini saniyeler içinde ücretsiz analiz edin; Zeynart ile 99+ performansa taşıyın.');

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: $pageTitleDefault,
    'description' => ($seoPage['meta_desc'] ?? '') ?: $pageDescDefault,
    'og_title' => ($seoPage['og_title'] ?? '') ?: $pageTitleDefault,
    'og_description' => ($seoPage['og_desc'] ?? '') ?: $pageDescDefault,
    'og_image' => ($seoPage['og_image'] ?? '') ?: '/images/banners/services.webp',
    'robots' => ($seoPage['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => '/site-analiz/',
    'ai_summary' => ($seoPage['ai_summary'] ?? '') ?: $pageDescDefault,
    'breadcrumbs' => [
        ['name' => t('Ana sayfa'), 'url' => '/'],
        ['name' => t('Site Hızı & SEO Analiz'), 'url' => '/site-analiz/'],
    ],
];

require INCLUDES_DIR . '/site_header.php';
?>
<div class="zsa-page-top">
  <?= render_site_audit_section($store, ['standalone' => true, 'context' => 'standalone']) ?>
</div>
<?= render_builder_sections($store['core_page_blocks']['site-analiz'] ?? [], $store) ?>
<?= render_global_cta($store) ?>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
