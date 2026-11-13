<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * Kurumsal / Hakkımızda — Panel > Sayfalar > Hakkımızda (about.*)
 *  eyebrow, heading_line1, heading_accent, lead, banner_image
 *  story_eyebrow, story_h2_1, story_h2_muted, story_paragraphs[], story_image, story_caption  → kronolojik hikâye
 *  software_eyebrow, software_h2, software_items[]                                           → distribütörlükler
 *  clients_eyebrow, clients_h2_1, clients_h2_accent, clients[]                               → çözüm ortakları
 */
$ab = $store['about'] ?? [];
$seoPage = $store['seo']['pages']['hakkimizda'] ?? [];
$banner = (string)($ab['banner_image'] ?? '');
$slug = '/' . zeynart_page_slug($store, 'hakkimizda') . '/';

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: ('Hakkımızda | ' . ($store['site']['name'] ?? '')),
    'description' => ($seoPage['meta_desc'] ?? '') ?: (string)($ab['lead'] ?? ''),
    'og_title' => $seoPage['og_title'] ?? '',
    'og_description' => $seoPage['og_desc'] ?? '',
    'og_image' => ($seoPage['og_image'] ?? '') ?: $banner,
    'robots' => ($seoPage['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => $slug,
    'body_class' => 'page--about',
    'breadcrumbs' => [
        ['name' => at('Ana sayfa'), 'url' => '/'],
        ['name' => at('Kurumsal'), 'url' => $slug],
    ],
];

require INCLUDES_DIR . '/site_header.php';

$paras = array_values(array_filter((array)($ab['story_paragraphs'] ?? []), static fn($p) => trim((string)$p) !== ''));
$items = array_values(array_filter((array)($ab['software_items'] ?? []), static fn($p) => trim((string)$p) !== ''));
$clients = array_values(array_filter((array)($ab['clients'] ?? []), static fn($c) => is_array($c) && !empty($c['image'])));
?>
<section class="ahead" aria-labelledby="ah-h">
  <div class="wrap ahead__grid">
    <div class="ahead__text">
      <nav class="crumbs crumbs--dark" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e(at('Kurumsal')) ?></span></nav>
      <p class="eyebrow"><?= e((string)($ab['eyebrow'] ?? '')) ?></p>
      <h1 id="ah-h" class="ahead__h"><?= ascam_heading((string)($ab['heading_line1'] ?? ''), (string)($ab['heading_accent'] ?? '')) ?></h1>
      <?php if (!empty($ab['lead'])): ?><p class="ahead__lead"><?= e((string)$ab['lead']) ?></p><?php endif; ?>
    </div>
  </div>
</section>

<?php if ($paras !== []): ?>
<section class="chron" aria-labelledby="chron-h">
  <div class="wrap">
    <div class="sec-head sec-head--split">
      <p class="eyebrow"><?= e((string)($ab['story_eyebrow'] ?? '')) ?></p>
      <h2 id="chron-h" class="sec-head__h"><?= ascam_heading((string)($ab['story_h2_1'] ?? ''), (string)($ab['story_h2_muted'] ?? '')) ?></h2>
    </div>
    <ol class="chron__list" role="list">
      <?php foreach ($paras as $i => $p):
          $year = preg_match('/\b(19|20)\d{2}\b/u', (string)$p, $m) ? $m[0] : str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
      ?>
      <li class="chron__item reveal">
        <span class="chron__year" aria-hidden="true"><?= e($year) ?></span>
        <p><?= e((string)$p) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($ab['story_image'])): ?>
<figure class="wide-figure">
  <?= ascam_img((string)$ab['story_image'], (string)($ab['story_caption'] ?? ''), ['sizes' => '100vw']) ?>
  <?php if (!empty($ab['story_caption'])): ?><figcaption class="wrap"><?= e((string)$ab['story_caption']) ?></figcaption><?php endif; ?>
</figure>
<?php endif; ?>

<?php if ($items !== []): ?>
<section class="dist" aria-labelledby="dist-h">
  <div class="wrap dist__grid">
    <div>
      <p class="eyebrow eyebrow--light"><?= e((string)($ab['software_eyebrow'] ?? '')) ?></p>
      <h2 id="dist-h" class="dist__h"><?= e((string)($ab['software_h2'] ?? '')) ?></h2>
    </div>
    <ol class="dist__list" role="list">
      <?php foreach ($items as $i => $it):
          $parts = preg_split('/\s+[—–-]\s+/u', (string)$it, 2);
      ?>
      <li class="reveal"><span class="dist__no"><?= e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)) ?></span><strong><?= e((string)($parts[0] ?? '')) ?></strong><?php if (!empty($parts[1])): ?><span class="dist__meta"><?= e((string)$parts[1]) ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>

<?php if ($clients !== []): ?>
<section class="partners partners--page" id="cozum-ortaklari" aria-labelledby="partners-h">
  <div class="wrap partners__in">
    <div>
      <p class="eyebrow"><?= e((string)($ab['clients_eyebrow'] ?? '')) ?></p>
      <h2 id="partners-h" class="partners__h"><?= ascam_heading((string)($ab['clients_h2_1'] ?? ''), (string)($ab['clients_h2_accent'] ?? '')) ?></h2>
      <?php if (!empty($ab['clients_lead'])): ?><p><?= e((string)$ab['clients_lead']) ?></p><?php endif; ?>
    </div>
    <ul class="partners__list" role="list">
      <?php foreach ($clients as $c):
          $img = ascam_img((string)$c['image'], (string)($c['alt'] ?? $c['name'] ?? ''), ['sizes' => '200px']);
          $u = (string)($c['url'] ?? '#');
      ?>
      <li><?= ($u !== '' && $u !== '#') ? '<a href="' . e($u) . '" target="_blank" rel="noopener">' . $img . '</a>' : $img ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php
$blocks = $store['core_page_blocks']['hakkimizda'] ?? [];
if (is_array($blocks) && $blocks !== []) {
    echo '<div class="builder">' . render_builder_sections($blocks, $store) . '</div>';
}
echo render_global_cta($store);
require INCLUDES_DIR . '/site_footer.php';
