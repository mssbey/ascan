<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * Ürün grubu detayı — Panel > Sayfalar > Hizmet Alt Sayfaları > [kayıt]
 * Alanlar: title, eyebrow, headline, description, banner_image, gallery[] (Galeri kartı), deliverables[], seo.*
 * @var array $activeService
 */
$srv = $activeService;
$products = ascam_products($store);
$base = ascam_products_base($store);
$gallery = ascam_product_gallery($srv);
$cover = ascam_product_cover($srv);
$title = (string)($srv['title'] ?? '');
$seo = $srv['seo'] ?? [];
$selfUrl = ascam_product_url($store, $srv);

$idx = 0;
foreach ($products as $i => $p) {
    if (($p['slug'] ?? '') === ($srv['slug'] ?? '')) {
        $idx = $i;
        break;
    }
}
$prev = $products !== [] ? $products[($idx - 1 + count($products)) % count($products)] : null;
$next = $products !== [] ? $products[($idx + 1) % count($products)] : null;

$imgSchema = [];
foreach (array_slice($gallery, 0, 30) as $g) {
    $imgSchema[] = ['@type' => 'ImageObject', 'contentUrl' => canonical_url($g['src'], $store), 'name' => $g['alt']];
}

$pageMeta = [
    'title' => ($seo['meta_title'] ?? '') ?: ($title . ' | ' . ($store['site']['name'] ?? '')),
    'description' => ($seo['meta_desc'] ?? '') ?: (string)($srv['description'] ?? ''),
    'og_title' => $seo['og_title'] ?? '',
    'og_description' => $seo['og_desc'] ?? '',
    'og_image' => ($seo['og_image'] ?? '') ?: $cover,
    'robots' => ($seo['robots'] ?? '') ?: 'index, follow',
    'keywords' => $seo['focus_keyword'] ?? '',
    'canonical_path' => $selfUrl,
    'preload_image' => $cover,
    'body_class' => 'page--product',
    'schema_nodes' => [[
        '@type' => 'CollectionPage',
        'name' => $title,
        'description' => (string)($srv['description'] ?? ''),
        'url' => canonical_url($selfUrl, $store),
        'image' => $imgSchema,
    ]],
    'breadcrumbs' => [
        ['name' => at('Ana sayfa'), 'url' => '/'],
        ['name' => at('Ürünlerimiz'), 'url' => $base],
        ['name' => $title, 'url' => $selfUrl],
    ],
];

require INCLUDES_DIR . '/site_header.php';
$contactUrl = url('/' . zeynart_page_slug($store, 'iletisim') . '/') . '?konu=' . rawurlencode((string)($srv['slug'] ?? ''));
$deliverables = array_values(array_filter((array)($srv['deliverables'] ?? []), static fn($d) => trim((string)$d) !== ''));
$tel = ascam_tel_href($store['site'] ?? []);
?>
<section class="prod" aria-labelledby="prod-h">
  <div class="wrap prod__grid">
    <div class="prod__text">
      <nav class="crumbs crumbs--dark" aria-label="Breadcrumb">
        <a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span>
        <a href="<?= e(url($base)) ?>"><?= e(at('Ürünlerimiz')) ?></a><span aria-hidden="true">/</span>
        <span aria-current="page"><?= e($title) ?></span>
      </nav>
      <p class="eyebrow"><span class="prod__no"><?= e(str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT)) ?></span> <?= e((string)($srv['eyebrow'] ?? '')) ?></p>
      <h1 id="prod-h" class="prod__h"><?= e((string)(($srv['headline'] ?? '') ?: $title)) ?></h1>
      <?php if (!empty($srv['description'])): ?><p class="prod__lead"><?= nl2br_e((string)$srv['description']) ?></p><?php endif; ?>
      <?php if ($deliverables !== []): ?>
      <ul class="prod__list" role="list"><?php foreach ($deliverables as $d): ?><li><?= ascam_icon('check', 16) ?><?= e((string)$d) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <div class="prod__actions">
        <a class="btn btn--red" href="<?= e($contactUrl) ?>"><?= e(at('Bu ürün grubu için teklif isteyin')) ?><?= ascam_icon('arrow', 18) ?></a>
        <?php if ($tel !== ''): ?><a class="btn btn--line" href="<?= e($tel) ?>"><?= ascam_icon('phone', 18) ?><?= e((string)($store['site']['phone'] ?? at('Ara'))) ?></a><?php endif; ?>
      </div>
    </div>
    <figure class="prod__cover">
      <?= ascam_img($cover, $title, ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 900px) 100vw, 50vw']) ?>
      <figcaption><span><?= count($gallery) ?></span> <?= e(at('görsel')) ?></figcaption>
    </figure>
  </div>
</section>

<?php if ($gallery !== []): ?>
<section class="wrap prod__gallery" aria-labelledby="gal-h">
  <div class="sec-head">
    <h2 id="gal-h" class="sec-head__h"><?= e($title) ?> <em><?= e(at('galerisi')) ?></em></h2>
    <p class="sec-head__meta"><?= count($gallery) ?> <?= e(at('görsel')) ?></p>
  </div>
  <?= ascam_render_gallery($gallery, $title, 24, 'galeri') ?>
</section>
<?php endif; ?>

<?= render_builder_sections(is_array($srv['builder_blocks'] ?? null) ? $srv['builder_blocks'] : [], $store) ?>

<?php if ($products !== [] && count($products) > 1): ?>
<nav class="wrap pager" aria-label="<?= e(at('Diğer ürün grupları')) ?>">
  <a class="pager__link pager__link--prev" href="<?= e(url(ascam_product_url($store, $prev))) ?>">
    <span class="pager__dir"><?= ascam_icon('chev-left', 18) ?><?= e(at('Önceki')) ?></span>
    <span class="pager__name"><?= e((string)$prev['title']) ?></span>
  </a>
  <a class="pager__all" href="<?= e(url($base)) ?>"><?= ascam_icon('grid', 20) ?><span class="sr-only"><?= e(at('Tüm ürünler')) ?></span></a>
  <a class="pager__link pager__link--next" href="<?= e(url(ascam_product_url($store, $next))) ?>">
    <span class="pager__dir"><?= e(at('Sonraki')) ?><?= ascam_icon('chev-right', 18) ?></span>
    <span class="pager__name"><?= e((string)$next['title']) ?></span>
  </a>
</nav>
<?php endif; ?>

<?php
echo render_global_cta($store);
require INCLUDES_DIR . '/site_footer.php';
