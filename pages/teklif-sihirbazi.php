<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';

$wizCfg = zeynart_get_quote_wizard_config($store);
$seoPage = $store['seo']['pages']['teklif_sihirbazi'] ?? [];
$siteName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
$wizSlug = zeynart_page_slug($store, 'teklif-sihirbazi') ?: 'teklif-sihirbazi';

$pageTitleDefault = t('İnteraktif Proje Teklif & Maliyet Sihirbazı') . ' | ' . $siteName;
$pageDescDefault = t($wizCfg['lead']);

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: $pageTitleDefault,
    'description' => ($seoPage['meta_desc'] ?? '') ?: $pageDescDefault,
    'og_title' => ($seoPage['og_title'] ?? '') ?: $pageTitleDefault,
    'og_description' => ($seoPage['og_desc'] ?? '') ?: $pageDescDefault,
    'og_image' => ($seoPage['og_image'] ?? '') ?: '',
    'og_eyebrow' => $wizCfg['eyebrow'],
    'robots' => ($seoPage['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => '/' . $wizSlug . '/',
    'ai_summary' => ($seoPage['ai_summary'] ?? '') ?: $pageDescDefault,
    'breadcrumbs' => [
        ['name' => t('Ana sayfa'), 'url' => '/'],
        ['name' => t('Teklif Sihirbazı'), 'url' => '/' . $wizSlug . '/'],
    ],
];

require INCLUDES_DIR . '/site_header.php';
?>
<section class="page-banner">
  <div class="page-banner-art" aria-hidden="true">
    <img alt="" fetchpriority="high" decoding="async" class="fill-img" src="<?= e(url('/images/banners/services.webp')) ?>"/>
  </div>
  <div class="container page-intro">
    <p class="eyebrow"><span></span><?= e(t($wizCfg['eyebrow'])) ?></p>
    <h1><?= e(t($wizCfg['heading'])) ?><br/><span class="muted"><?= e(t($wizCfg['heading_accent'])) ?></span></h1>
    <p><?= e(t($wizCfg['lead'])) ?></p>
  </div>
</section>
<?= zeynart_render_quote_wizard_section($store, 'page') ?>
<?= render_builder_sections($store['core_page_blocks']['teklif-sihirbazi'] ?? [], $store) ?>
<?= render_global_cta($store) ?>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
