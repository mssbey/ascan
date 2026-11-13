<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * Projeler / Referanslar — Panel > Sayfalar > Projeler (projects_page.*, projects[])
 * Not: ascam.com.tr'de proje kaydı yoktur; sayfa taslak durumdadır. Panelden proje eklenip sayfa yayına alındığında çalışır.
 */
$pp = $store['projects_page'] ?? [];
$seoPage = $store['seo']['pages']['projeler'] ?? [];
$projects = zeynart_public_projects($store);
$base = '/' . zeynart_page_slug($store, 'projeler') . '/';

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: (($pp['heading_line1'] ?? at('Projeler')) . ' | ' . ($store['site']['name'] ?? '')),
    'description' => ($seoPage['meta_desc'] ?? '') ?: (string)($pp['lead'] ?? ''),
    'robots' => ($seoPage['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => $base,
    'body_class' => 'page--projects',
    'breadcrumbs' => [['name' => at('Ana sayfa'), 'url' => '/'], ['name' => at('Projeler'), 'url' => $base]],
];
require INCLUDES_DIR . '/site_header.php';
?>
<section class="thead" aria-labelledby="pj-h">
  <div class="wrap thead__in">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e(at('Projeler')) ?></span></nav>
    <p class="eyebrow eyebrow--light"><?= e((string)($pp['eyebrow'] ?? '')) ?></p>
    <h1 id="pj-h" class="thead__h"><?= ascam_heading((string)($pp['heading_line1'] ?? ''), (string)($pp['heading_muted'] ?? '')) ?></h1>
    <?php if (!empty($pp['lead'])): ?><p class="thead__lead"><?= e((string)$pp['lead']) ?></p><?php endif; ?>
  </div>
</section>
<section class="wrap pgrid-wrap">
  <ul class="pgrid" role="list">
    <?php foreach ($projects as $pj):
        $cover = trim((string)($pj['image'] ?? '')) ?: ((ascam_product_gallery($pj)[0]['src'] ?? ''));
    ?>
    <li class="pgrid__item reveal"><a href="<?= e(url($base . $pj['slug'] . '/')) ?>"><?= ascam_img($cover, (string)($pj['name'] ?? ''), ['sizes' => '(max-width: 760px) 100vw, 50vw']) ?><span class="pgrid__name"><?= e((string)($pj['name'] ?? '')) ?></span><span class="pgrid__meta"><?= e(trim((string)($pj['sector'] ?? '') . ' · ' . (string)($pj['year'] ?? ''), ' ·')) ?></span></a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
echo render_global_cta($store);
require INCLUDES_DIR . '/site_footer.php';
