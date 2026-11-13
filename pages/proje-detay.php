<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * Proje detayı — görsel ağırlıklı şablon. Panel > Sayfalar > Proje Alt Sayfaları > [kayıt]
 * Alanlar: name, image (kapak), headline, summary, sector, year, scope, gallery[] (Galeri kartı), seo.*
 * @var array $activeProject
 */
$prj = $activeProject;
$slug = (string)($prj['slug'] ?? '');
$seo = $prj['seo'] ?? [];
$base = '/' . zeynart_page_slug($store, 'projeler') . '/';
$gallery = ascam_product_gallery($prj);
$cover = trim((string)($prj['image'] ?? '')) ?: ($gallery[0]['src'] ?? '');
$name = (string)($prj['name'] ?? '');

$pageMeta = [
    'title' => ($seo['meta_title'] ?? '') ?: ($name . ' | ' . ($store['site']['name'] ?? '')),
    'description' => ($seo['meta_desc'] ?? '') ?: (string)($prj['summary'] ?? ''),
    'og_image' => ($seo['og_image'] ?? '') ?: $cover,
    'robots' => ($seo['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => $base . $slug . '/',
    'preload_image' => $cover,
    'body_class' => 'page--project',
    'breadcrumbs' => [['name' => at('Ana sayfa'), 'url' => '/'], ['name' => at('Projeler'), 'url' => $base], ['name' => $name, 'url' => $base . $slug . '/']],
];
require INCLUDES_DIR . '/site_header.php';
$facts = array_filter([
    'Sektör' => (string)($prj['sector'] ?? ''),
    'Yıl' => (string)($prj['year'] ?? ''),
    'Kapsam' => (string)($prj['scope'] ?? ''),
], static fn($v) => trim($v) !== '');
?>
<section class="phero" aria-labelledby="pr-h">
  <figure class="phero__media" aria-hidden="true"><?= ascam_img($cover, '', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw']) ?></figure>
  <div class="wrap phero__in">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span><a href="<?= e(url($base)) ?>"><?= e(at('Projeler')) ?></a></nav>
    <h1 id="pr-h" class="phero__h"><?= e($name) ?></h1>
    <?php if ($facts !== []): ?>
    <dl class="phero__facts"><?php foreach ($facts as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?></dl>
    <?php endif; ?>
  </div>
</section>
<?php if (!empty($prj['headline']) || !empty($prj['summary'])): ?>
<section class="wrap tbody">
  <div class="tbody__aside" aria-hidden="true"><span><?= e(mb_strtoupper($name, 'UTF-8')) ?></span></div>
  <div class="prose prose--lead">
    <?php if (!empty($prj['headline'])): ?><h2><?= e((string)$prj['headline']) ?></h2><?php endif; ?>
    <?php if (!empty($prj['summary'])): ?><p><?= nl2br_e((string)$prj['summary']) ?></p><?php endif; ?>
  </div>
</section>
<?php endif; ?>
<?php if ($gallery !== []): ?>
<section class="wrap tgal"><?= ascam_render_gallery($gallery, $name, 24, 'proje-galeri') ?></section>
<?php endif; ?>
<?= render_builder_sections(is_array($prj['builder_blocks'] ?? null) ? $prj['builder_blocks'] : [], $store) ?>
<?php
echo render_global_cta($store);
require INCLUDES_DIR . '/site_footer.php';
