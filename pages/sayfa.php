<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/social_card.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * Özel sayfa — Panel > Sayfalar > Özel Sayfalar (custom_pages[])
 * Üretim sayfası buradan yönetilir: başlık, üst etiket, özet, banner, zengin metin (content_html) ve
 * sayfa oluşturucu blokları (stats_bar = teknik göstergeler, gallery_grid = ışık kutulu galeri).
 * @var array $customPage
 */
$pg = $customPage;
$slug = (string)($pg['slug'] ?? '');
$seo = $pg['seo'] ?? [];
$plain = zeynart_extract_plain_excerpt((string)($pg['content_html'] ?? ''), 185);
$banner = trim((string)($pg['banner_image'] ?? ''));

$pageMeta = [
    'title' => ($seo['meta_title'] ?? '') ?: (($pg['title'] ?? '') . ' | ' . ($store['site']['name'] ?? '')),
    'description' => ($seo['meta_desc'] ?? '') ?: (($pg['excerpt'] ?? '') ?: $plain),
    'og_title' => $seo['og_title'] ?? '',
    'og_description' => $seo['og_desc'] ?? '',
    'keywords' => $seo['focus_keyword'] ?? '',
    'robots' => ($seo['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => '/' . $slug . '/',
    'og_image' => ($seo['og_image'] ?? '') ?: ($banner ?: zeynart_extract_first_image((string)($pg['content_html'] ?? ''))),
    'preload_image' => $banner,
    'faqs' => $pg['faqs'] ?? [],
    'body_class' => 'page--custom',
    'breadcrumbs' => [
        ['name' => at('Ana sayfa'), 'url' => '/'],
        ['name' => (string)($pg['title'] ?? ''), 'url' => '/' . $slug . '/'],
    ],
];

require INCLUDES_DIR . '/site_header.php';

/* Teknik sayfa düzeni: stats_bar ve gallery_grid bloklarını özel bileşenlerle, diğer blokları oluşturucu ile çiz */
$stats = [];
$galleries = [];
$otherSections = [];
foreach ((array)($pg['sections'] ?? []) as $sec) {
    if (!is_array($sec) || !empty($sec['disabled'])) {
        continue;
    }
    $consumed = true;
    foreach ((array)($sec['columns'] ?? []) as $col) {
        foreach ((array)($col['widgets'] ?? []) as $w) {
            $type = (string)($w['type'] ?? '');
            $props = (array)($w['props'] ?? []);
            if ($type === 'stats_bar') {
                foreach ((array)($props['items'] ?? []) as $it) {
                    if (is_array($it) && trim((string)($it['value'] ?? '')) !== '') {
                        $stats[] = $it;
                    }
                }
            } elseif ($type === 'gallery_grid') {
                $imgs = [];
                foreach ((array)($props['images'] ?? []) as $im) {
                    $s = is_array($im) ? trim((string)($im['src'] ?? '')) : trim((string)$im);
                    if ($s !== '') {
                        $imgs[] = ['src' => $s, 'alt' => is_array($im) ? (string)($im['alt'] ?? '') : ''];
                    }
                }
                if ($imgs !== []) {
                    $galleries[] = $imgs;
                }
            } else {
                $consumed = false;
            }
        }
    }
    if (!$consumed) {
        $otherSections[] = $sec;
    }
}
?>
<?php if (($pg['show_page_header'] ?? true) !== false): ?>
<section class="thead<?= $banner !== '' ? ' thead--media' : '' ?>" aria-labelledby="th-h">
  <?php if ($banner !== ''): ?><figure class="thead__media" aria-hidden="true"><?= ascam_img($banner, '', ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw']) ?></figure><?php endif; ?>
  <div class="wrap thead__in">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e((string)($pg['title'] ?? '')) ?></span></nav>
    <?php if (!empty($pg['eyebrow'])): ?><p class="eyebrow eyebrow--light"><?= e((string)$pg['eyebrow']) ?></p><?php endif; ?>
    <h1 id="th-h" class="thead__h"><?= e((string)($pg['title'] ?? '')) ?></h1>
    <?php if (!empty($pg['excerpt'])): ?><p class="thead__lead"><?= e((string)$pg['excerpt']) ?></p><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($stats !== []): ?>
<section class="specs" aria-label="<?= e((string)($pg['title'] ?? '')) ?>">
  <dl class="wrap specs__grid">
    <?php foreach ($stats as $i => $st): ?>
    <div class="specs__item reveal" style="--i:<?= $i ?>"><dt><?= e((string)($st['label'] ?? '')) ?></dt><dd><?= e((string)$st['value']) ?></dd></div>
    <?php endforeach; ?>
  </dl>
</section>
<?php endif; ?>

<?php if (trim((string)($pg['content_html'] ?? '')) !== ''): ?>
<section class="wrap tbody">
  <div class="tbody__aside" aria-hidden="true"><span><?= e(mb_strtoupper((string)($pg['title'] ?? ''), 'UTF-8')) ?></span></div>
  <div class="prose prose--lead"><?= (string)$pg['content_html'] ?></div>
</section>
<?php endif; ?>

<?php foreach ($galleries as $gi => $imgs): ?>
<section class="wrap tgal" aria-labelledby="tgal-h-<?= $gi ?>">
  <div class="sec-head">
    <h2 id="tgal-h-<?= $gi ?>" class="sec-head__h"><?= e((string)($pg['title'] ?? '')) ?> <em><?= e(at('galerisi')) ?></em></h2>
    <p class="sec-head__meta"><?= count($imgs) ?> <?= e(at('görsel')) ?></p>
  </div>
  <?= ascam_render_gallery($imgs, (string)($pg['title'] ?? ''), 24, 'galeri-' . $gi) ?>
</section>
<?php endforeach; ?>

<?php if ($otherSections !== []): ?><div class="builder"><?= render_builder_sections($otherSections, $store) ?></div><?php endif; ?>

<?php
if (($pg['show_cta'] ?? true) !== false) {
    echo render_global_cta($store);
}
require INCLUDES_DIR . '/site_footer.php';
