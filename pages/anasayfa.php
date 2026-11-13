<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * Ana sayfa — Panel > Sayfalar > Ana Sayfa
 *  hero          → Hero & Slaytlar (home.hero)
 *  services      → Numaralı ürün keşif alanı (yayındaki ürün grupları + services_page başlıkları)
 *  intro         → Hikâye Girişi (home.intro)
 *  brand_scene   → Üretim Sahne Paneli (home.brand_scene)
 *  process       → Değerler / Süreç Kartları (home.process)
 *  clients_strip → Çözüm ortakları (about.clients)
 *  builder_blocks→ Ana sayfa blokları (core_page_blocks.anasayfa)
 *  cta           → Global CTA (cta_global)
 * Bölüm sırası ve görünürlüğü: "Bölüm Sıralaması & Görünürlük".
 */
$home = $store['home'] ?? [];
$hero = $home['hero'] ?? [];
$slide = (array)(array_values((array)($hero['slides'] ?? []))[0] ?? []);
$seoHome = $store['seo']['pages']['anasayfa'] ?? [];
$site = $store['site'] ?? [];
$products = ascam_products($store);
$sp = $store['services_page'] ?? [];
$heroImg = (string)($hero['bg_image_1'] ?? '');

$pageMeta = [
    'title' => ($seoHome['meta_title'] ?? '') ?: (string)($store['seo']['default_title'] ?? ($site['name'] ?? '')),
    'description' => ($seoHome['meta_desc'] ?? '') ?: (string)($store['seo']['default_description'] ?? ''),
    'og_title' => $seoHome['og_title'] ?? '',
    'og_description' => $seoHome['og_desc'] ?? '',
    'og_image' => ($seoHome['og_image'] ?? '') ?: $heroImg,
    'robots' => ($seoHome['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => '/',
    'preload_image' => $heroImg,
    'body_class' => 'page--home',
];

require INCLUDES_DIR . '/site_header.php';

$order = (array)($home['section_order'] ?? []);
$vis = (array)($home['section_visibility'] ?? []);

$sections = [];

/* ---------- 1. Açılış sahnesi ---------- */
$sections['hero'] = static function () use ($hero, $slide, $heroImg, $site): void {
    $lines = array_values(array_filter((array)($slide['lines'] ?? []), static fn($l) => trim((string)$l) !== ''));
    $notes = array_values(array_filter((array)($slide['note'] ?? []), static fn($l) => trim((string)$l) !== ''));
    ?>
<section class="hero" aria-labelledby="hero-h" data-hero>
  <div class="hero__media" aria-hidden="true">
    <?= ascam_img($heroImg, (string)($hero['bg_alt_1'] ?? ''), ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw', 'class' => 'hero__img']) ?>
    <div class="hero__panes" data-hero-panes><span></span><span></span><span></span><span></span></div>
    <div class="hero__shade"></div>
  </div>
  <div class="wrap hero__in">
    <?php if (!empty($slide['label'])): ?><p class="eyebrow eyebrow--light hero__eyebrow"><?= e((string)$slide['label']) ?></p><?php endif; ?>
    <h1 id="hero-h" class="hero__h">
      <?php foreach ($lines as $l): ?><span class="hero__line"><?= e((string)$l) ?></span><?php endforeach; ?>
      <?php if (!empty($slide['accent'])): ?><em class="hero__accent"><?= e((string)$slide['accent']) ?></em><?php endif; ?>
    </h1>
    <div class="hero__foot">
      <?php if (!empty($slide['copy'])): ?><p class="hero__copy"><?= e((string)$slide['copy']) ?></p><?php endif; ?>
      <div class="hero__actions">
        <?php if (!empty($hero['primary_btn_label'])): ?>
        <a class="btn btn--light" href="<?= e(url((string)($hero['primary_btn_url'] ?? '/urunler/'))) ?>"><?= e((string)$hero['primary_btn_label']) ?><?= ascam_icon('arrow', 18) ?></a>
        <?php endif; ?>
        <?php if (!empty($hero['secondary_btn_label'])): ?>
        <a class="btn btn--glass" href="<?= e(url((string)($hero['secondary_btn_url'] ?? '/iletisim/'))) ?>"><?= e((string)$hero['secondary_btn_label']) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($notes !== []): ?>
    <ul class="hero__notes" role="list">
      <?php foreach ($notes as $i => $n): ?><li><span><?= e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)) ?></span><?= e((string)$n) ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
  <div class="hero__bar">
    <div class="wrap hero__bar-in">
      <a class="hero__scroll" href="#kesfet"><?= ascam_icon('arrow-down', 16) ?><?= e((string)($hero['scroll_label'] ?? at('Keşfetmeye devam edin'))) ?></a>
      <span class="hero__domains"><?= e((string)($hero['domains_bar'] ?? '')) ?></span>
      <span class="hero__coord"><?= e((string)($hero['coordinate_text'] ?? ($site['legal_name'] ?? ''))) ?></span>
    </div>
  </div>
</section>
    <?php
};

/* ---------- 2. İmza bölümü: numaralı ürün keşif alanı ---------- */
$sections['services'] = static function () use ($store, $products, $sp): void {
    if ($products === []) {
        return;
    }
    ?>
<section class="explore" id="kesfet" aria-labelledby="explore-h" data-explorer>
  <div class="wrap explore__head">
    <p class="eyebrow eyebrow--light"><?= e((string)($sp['eyebrow'] ?? at('Ürünlerimiz'))) ?></p>
    <h2 id="explore-h" class="explore__h"><?= ascam_heading((string)($sp['heading_line1'] ?? ''), (string)($sp['heading_muted'] ?? '')) ?></h2>
    <p class="explore__count" aria-hidden="true"><span data-explorer-current>01</span> / <?= e(str_pad((string)count($products), 2, '0', STR_PAD_LEFT)) ?></p>
  </div>
  <div class="explore__body">
    <div class="explore__list" role="tablist" aria-label="<?= e(at('Ürün grupları')) ?>" aria-orientation="vertical">
      <?php foreach ($products as $i => $p):
          $id = 'xp-' . preg_replace('/[^a-z0-9-]/', '', (string)$p['slug']);
      ?>
      <button type="button" role="tab" class="explore__tab" id="<?= e($id) ?>-tab" aria-controls="<?= e($id) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" tabindex="<?= $i === 0 ? '0' : '-1' ?>" data-explorer-tab="<?= $i ?>">
        <span class="explore__num"><?= e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
        <span class="explore__name"><?= e((string)($p['title'] ?? '')) ?></span>
        <span class="explore__qty"><?= count(ascam_product_gallery($p)) ?></span>
      </button>
      <?php endforeach; ?>
    </div>
    <div class="explore__stage">
      <?php foreach ($products as $i => $p):
          $id = 'xp-' . preg_replace('/[^a-z0-9-]/', '', (string)$p['slug']);
          $gal = ascam_product_gallery($p);
      ?>
      <article class="explore__panel<?= $i === 0 ? ' is-active' : '' ?>" role="tabpanel" id="<?= e($id) ?>" aria-labelledby="<?= e($id) ?>-tab"<?= $i === 0 ? '' : ' hidden' ?> data-explorer-panel="<?= $i ?>">
        <figure class="explore__media">
          <?= ascam_img(ascam_product_cover($p), (string)($p['title'] ?? ''), ['sizes' => '(max-width: 900px) 100vw, 62vw', 'loading' => $i === 0 ? 'lazy' : 'lazy']) ?>
        </figure>
        <div class="explore__card">
          <p class="explore__tag"><?= e((string)($p['card_tag'] ?? '')) ?> · <?= count($gal) ?> <?= e(at('görsel')) ?></p>
          <h3 class="explore__title"><?= e((string)($p['title'] ?? '')) ?></h3>
          <?php if (!empty($p['short'])): ?><p class="explore__desc"><?= e((string)$p['short']) ?></p><?php endif; ?>
          <a class="btn btn--light btn--sm" href="<?= e(url(ascam_product_url($store, $p))) ?>"><?= e(at('Galeriyi inceleyin')) ?><?= ascam_icon('arrow', 16) ?></a>
        </div>
      </article>
      <?php endforeach; ?>
      <div class="explore__sweep" aria-hidden="true" data-explorer-sweep></div>
    </div>
  </div>
</section>
    <?php
};

/* ---------- 3. Hikâye girişi ---------- */
$sections['intro'] = static function () use ($home, $store): void {
    $in = $home['intro'] ?? [];
    $about = $store['about'] ?? [];
    $paras = array_values((array)($about['story_paragraphs'] ?? []));
    // Kronoloji: hakkımızda metnindeki her paragrafın ilk yılı
    $chapters = [];
    foreach ($paras as $p) {
        if (preg_match('/\b(19|20)\d{2}\b/u', (string)$p, $m)) {
            $sentence = preg_split('/(?<!San\.)(?<!Tic\.)(?<!Ltd\.)(?<!Şti\.)(?<=[.!?])\s+/u', trim((string)$p))[0] ?? '';
            $chapters[] = ['year' => $m[0], 'text' => $sentence];
        }
    }
    ?>
<section class="story" aria-labelledby="story-h">
  <div class="wrap story__grid">
    <div class="story__year" aria-hidden="true"><span><?= e((string)($in['year'] ?? '')) ?></span></div>
    <div class="story__main">
      <p class="eyebrow"><?= e((string)($in['eyebrow'] ?? '')) ?></p>
      <?php if (!empty($in['lead'])): ?><p class="story__lead"><?= e((string)$in['lead']) ?></p><?php endif; ?>
      <h2 id="story-h" class="story__h"><?= e((string)($in['heading_before'] ?? '')) ?> <em><?= e((string)($in['heading_em'] ?? '')) ?></em> <?= e((string)($in['heading_after'] ?? '')) ?></h2>
      <?php if ($chapters !== []): ?>
      <ol class="timeline" role="list">
        <?php foreach ($chapters as $c): ?>
        <li class="timeline__item reveal"><span class="timeline__y"><?= e($c['year']) ?></span><p><?= e($c['text']) ?></p></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
      <div class="story__foot">
        <?php if (!empty($in['tagline'])): ?><p class="story__tag"><?= e((string)$in['tagline']) ?></p><?php endif; ?>
        <?php if (!empty($in['link_label'])): ?>
        <a class="link-arrow" href="<?= e(url((string)($in['link_url'] ?? '/hakkimizda/'))) ?>"><?= e((string)$in['link_label']) ?><?= ascam_icon('arrow-up-right', 22) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if (!empty($in['pillars'])): ?>
  <div class="marquee" aria-hidden="true"><div class="marquee__track"><span><?= e((string)$in['pillars']) ?></span><span><?= e((string)$in['pillars']) ?></span></div></div>
  <p class="sr-only"><?= e((string)$in['pillars']) ?></p>
  <?php endif; ?>
</section>
    <?php
};

/* ---------- 4. Üretim sahnesi ---------- */
$sections['brand_scene'] = static function () use ($home): void {
    $bs = $home['brand_scene'] ?? [];
    if (empty($bs['image']) && empty($bs['heading_line1'])) {
        return;
    }
    ?>
<section class="scene" aria-labelledby="scene-h">
  <figure class="scene__media" aria-hidden="true"><?= ascam_img((string)($bs['image'] ?? ''), '', ['sizes' => '100vw']) ?></figure>
  <div class="wrap scene__in">
    <?php if (!empty($bs['top_note'])): ?><p class="scene__note"><?= e((string)$bs['top_note']) ?></p><?php endif; ?>
    <div class="scene__card glass reveal">
      <p class="eyebrow"><?= e((string)($bs['eyebrow'] ?? '')) ?></p>
      <h2 id="scene-h" class="scene__h"><?= e((string)($bs['heading_line1'] ?? '')) ?> <?= e((string)($bs['heading_line2'] ?? '')) ?> <em><?= e((string)($bs['heading_em'] ?? '')) ?></em></h2>
      <p><?= e((string)($bs['copy_line1'] ?? '')) ?> <?= e((string)($bs['copy_line2'] ?? '')) ?></p>
      <?php if (!empty($bs['link_url'])): ?>
      <a class="btn btn--ink btn--sm" href="<?= e(url((string)$bs['link_url'])) ?>"><?= e((string)($bs['link_label'] ?? '')) ?><?= ascam_icon('arrow', 16) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
    <?php
};

/* ---------- 5. Değerler ---------- */
$sections['process'] = static function () use ($home): void {
    $pr = $home['process'] ?? [];
    $steps = array_values(array_filter((array)($pr['steps'] ?? []), static fn($s) => is_array($s) && trim((string)($s['title'] ?? '')) !== ''));
    if ($steps === []) {
        return;
    }
    ?>
<section class="values" aria-labelledby="values-h">
  <div class="wrap">
    <div class="values__head">
      <p class="eyebrow"><?= e((string)($pr['eyebrow'] ?? '')) ?></p>
      <h2 id="values-h" class="values__h"><?= ascam_heading((string)($pr['heading_line1'] ?? ''), (string)($pr['heading_muted'] ?? '')) ?></h2>
    </div>
    <ol class="values__grid" role="list">
      <?php foreach ($steps as $i => $s): ?>
      <li class="values__card reveal" style="--i:<?= $i ?>">
        <span class="values__num"><?= e((string)($s['num'] ?? str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT))) ?></span>
        <h3><?= e((string)$s['title']) ?></h3>
        <p><?= e((string)($s['desc'] ?? '')) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php if (!empty($pr['footer_left']) || !empty($pr['footer_right'])): ?>
    <div class="values__foot"><span><?= e((string)($pr['footer_left'] ?? '')) ?></span><span><?= e((string)($pr['footer_right'] ?? '')) ?></span></div>
    <?php endif; ?>
  </div>
</section>
    <?php
};

/* ---------- 6. Çözüm ortakları ---------- */
$sections['clients_strip'] = static function () use ($store): void {
    $ab = $store['about'] ?? [];
    $clients = array_values(array_filter((array)($ab['clients'] ?? []), static fn($c) => is_array($c) && !empty($c['image'])));
    if ($clients === []) {
        return;
    }
    ?>
<section class="partners" aria-labelledby="partners-h">
  <div class="wrap partners__in">
    <div>
      <p class="eyebrow"><?= e((string)($ab['clients_eyebrow'] ?? '')) ?></p>
      <h2 id="partners-h" class="partners__h"><?= ascam_heading((string)($ab['clients_h2_1'] ?? ''), (string)($ab['clients_h2_accent'] ?? '')) ?></h2>
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
    <?php
};

/* ---------- 7. Projeler (yalnızca kayıt varsa) ---------- */
$sections['projects_showcase'] = static function () use ($store): void {
    if (!zeynart_is_page_accessible($store, 'projeler')) {
        return;
    }
    $projects = zeynart_public_projects($store);
    if ($projects === []) {
        return;
    }
    $base = '/' . zeynart_page_slug($store, 'projeler') . '/';
    ?>
<section class="pshow" aria-labelledby="pshow-h">
  <div class="wrap">
    <p class="eyebrow"><?= e((string)($store['projects_page']['eyebrow'] ?? at('Projeler'))) ?></p>
    <h2 id="pshow-h" class="values__h"><?= e((string)($store['projects_page']['heading_line1'] ?? at('Projeler'))) ?></h2>
    <ul class="pgrid" role="list">
      <?php foreach (array_slice($projects, 0, 4) as $i => $pj):
          $cover = trim((string)($pj['image'] ?? '')) ?: ((ascam_product_gallery($pj)[0]['src'] ?? ''));
      ?>
      <li class="pgrid__item"><a href="<?= e(url($base . $pj['slug'] . '/')) ?>"><?= ascam_img($cover, (string)($pj['name'] ?? '')) ?><span class="pgrid__name"><?= e((string)($pj['name'] ?? '')) ?></span><span class="pgrid__meta"><?= e(trim((string)($pj['sector'] ?? '') . ' · ' . (string)($pj['year'] ?? ''), ' ·')) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
    <?php
};

/* ---------- 8. Ana sayfa blokları (panel sayfa oluşturucu) ---------- */
$sections['builder_blocks'] = static function () use ($store): void {
    $blocks = $store['core_page_blocks']['anasayfa'] ?? [];
    if (is_array($blocks) && $blocks !== []) {
        echo '<div class="builder">' . render_builder_sections($blocks, $store) . '</div>';
    }
};

/* ---------- 9. CTA ---------- */
$sections['cta'] = static function () use ($store): void {
    echo render_global_cta($store);
};

foreach ($order as $key) {
    if (($vis[$key] ?? false) && isset($sections[$key])) {
        $sections[$key]();
    }
}

require INCLUDES_DIR . '/site_footer.php';
