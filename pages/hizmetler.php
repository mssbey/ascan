<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * Ürünler listesi — Panel > Sayfalar > Hizmetler (URL: page_slugs.hizmetler = "urunler")
 * Başlıklar: services_page.*  ·  Kartlar: yayındaki hizmet alt sayfaları (ürün grupları), panel sırasıyla.
 */
$sp = $store['services_page'] ?? [];
$seoPage = $store['seo']['pages']['hizmetler'] ?? [];
$products = ascam_products($store);
$base = ascam_products_base($store);
$banner = (string)($sp['banner_image'] ?? '');

$itemList = [];
foreach ($products as $i => $p) {
    $itemList[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => (string)$p['title'], 'url' => canonical_url(ascam_product_url($store, $p), $store)];
}

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: (at('Ürünlerimiz') . ' | ' . ($store['site']['name'] ?? '')),
    'description' => ($seoPage['meta_desc'] ?? '') ?: (string)($sp['lead'] ?? ''),
    'og_title' => $seoPage['og_title'] ?? '',
    'og_description' => $seoPage['og_desc'] ?? '',
    'og_image' => ($seoPage['og_image'] ?? '') ?: $banner,
    'robots' => ($seoPage['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => $base,
    'preload_image' => $banner,
    'body_class' => 'page--products',
    'schema_nodes' => $itemList !== [] ? [['@type' => 'ItemList', 'name' => at('Ürün grupları'), 'itemListElement' => $itemList]] : [],
    'breadcrumbs' => [
        ['name' => at('Ana sayfa'), 'url' => '/'],
        ['name' => at('Ürünlerimiz'), 'url' => $base],
    ],
];

require INCLUDES_DIR . '/site_header.php';
?>
<section class="phead phead--dark" aria-labelledby="ph-h">
  <figure class="phead__media" aria-hidden="true"><?= ascam_img($banner, '', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw']) ?></figure>
  <div class="wrap phead__in">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e(at('Ürünlerimiz')) ?></span></nav>
    <p class="eyebrow eyebrow--light"><?= e((string)($sp['eyebrow'] ?? '')) ?></p>
    <h1 id="ph-h" class="phead__h"><?= ascam_heading((string)($sp['heading_line1'] ?? ''), (string)($sp['heading_muted'] ?? '')) ?></h1>
    <?php if (!empty($sp['lead'])): ?><p class="phead__lead"><?= e((string)$sp['lead']) ?></p><?php endif; ?>
    <p class="phead__meta"><?= count($products) ?> <?= e(mb_strtolower(at('Ürün grupları'), 'UTF-8')) ?> · <?= array_sum(array_map(static fn($p) => count(ascam_product_gallery($p)), $products)) ?> <?= e(at('görsel')) ?></p>
  </div>
</section>

<section class="wrap catalog" aria-label="<?= e(at('Ürün grupları')) ?>">
  <ol class="catalog__grid" role="list">
    <?php foreach ($products as $i => $p):
        $count = count(ascam_product_gallery($p));
    ?>
    <li class="catalog__item reveal catalog__item--<?= $i % 6 ?>">
      <a class="catalog__card" href="<?= e(url(ascam_product_url($store, $p))) ?>">
        <figure class="catalog__media"><?= ascam_img(ascam_product_cover($p), (string)$p['title'], ['sizes' => '(max-width: 760px) 100vw, 50vw']) ?></figure>
        <span class="catalog__num"><?= e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
        <span class="catalog__body">
          <span class="catalog__tag"><?= e((string)($p['card_tag'] ?? '')) ?></span>
          <span class="catalog__name"><?= e((string)$p['title']) ?></span>
          <span class="catalog__meta"><?= $count ?> <?= e(at('görsel')) ?> <?= ascam_icon('arrow-up-right', 20) ?></span>
        </span>
      </a>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<?php
$blocks = $store['core_page_blocks']['hizmetler'] ?? [];
if (is_array($blocks) && $blocks !== []) {
    echo '<div class="builder">' . render_builder_sections($blocks, $store) . '</div>';
}
echo render_global_cta($store);
require INCLUDES_DIR . '/site_footer.php';
