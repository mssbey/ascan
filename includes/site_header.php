<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/analytics.php';
require_once __DIR__ . '/studio_suite.php';
require_once __DIR__ . '/ascam_theme.php';

/**
 * ASCAM teması — üst bilgi.
 * Beklenen değişkenler: array $store, array $pageMeta, string $currentRoutePath
 * Menü: Panel > Menüler (menus.header). "Ürünler" bağlantısı, yayındaki ürün gruplarından otomatik açılır panel üretir.
 */
$site = $store['site'] ?? [];
$headerMenu = $store['menus']['header'] ?? [];
$currentPath = $currentRoutePath ?? '/';
$curLang = zeynart_current_lang();

if (http_response_code() !== 404 && $currentPath !== '/404') {
    try {
        zeynart_record_page_visit((string)$currentPath, (string)($pageMeta['title'] ?? ''), $curLang);
    } catch (\Throwable $_) {
    }
}

$navItems = array_values(array_filter(
    $headerMenu['items'] ?? [],
    static fn($it): bool => is_array($it) && ($it['enabled'] ?? true) !== false && !zeynart_is_internal_url_hidden($store, (string)($it['url'] ?? ''))
));
$productsBase = ascam_products_base($store);
$products = ascam_products($store);
$logoSrc = ascam_logo_src($site);
$logoAlt = (string)($site['logo_alt'] ?? ($site['name'] ?? ''));

if (!function_exists('is_nav_active')) {
    function is_nav_active(string $itemUrl, string $currentPath): bool
    {
        $normItem = '/' . trim($itemUrl, '/');
        $normCur = '/' . trim($currentPath, '/');
        if ($normItem === '/') {
            return $normCur === '/';
        }
        return $normCur === $normItem || str_starts_with($normCur . '/', $normItem . '/');
    }
}

if (!function_exists('render_global_cta')) {
    /** Eski şablonların da kullandığı ortak çağrı bölümü (Panel > Görünüm > Global CTA: cta_global). */
    function render_global_cta(array $store): string
    {
        $cta = $store['cta_global'] ?? [];
        $site = $store['site'] ?? [];
        $tel = ascam_tel_href($site);
        $h = '<section class="cta" aria-labelledby="cta-h"><div class="cta__glow" aria-hidden="true"></div><div class="wrap cta__in">';
        $h .= '<p class="eyebrow eyebrow--light">' . e((string)($cta['eyebrow'] ?? '')) . '</p>';
        $h .= '<h2 id="cta-h" class="cta__h">' . e((string)($cta['heading_line1'] ?? '')) . ' ' . e((string)($cta['heading_line2'] ?? '')) . ' <em>' . e((string)($cta['heading_accent'] ?? '')) . '</em></h2>';
        $h .= '<div class="cta__side"><p>' . e((string)($cta['side_note'] ?? '')) . '</p><div class="cta__actions">';
        $h .= '<a class="btn btn--red" href="' . e(url((string)($cta['button_url'] ?? '/iletisim/'))) . '">' . e((string)($cta['button_label'] ?? at('Bize ulaşın'))) . ascam_icon('arrow', 18) . '</a>';
        if ($tel !== '' && !empty($site['phone'])) {
            $h .= '<a class="btn btn--glass" href="' . e($tel) . '">' . ascam_icon('phone', 18) . e((string)$site['phone']) . '</a>';
        }
        $h .= '</div></div></div></section>';
        return $h;
    }
}

$adminBar = null;
if (function_exists('is_admin_logged_in') && is_admin_logged_in()) {
    $pgKey = function_exists('zeynart_route_to_page_key') ? zeynart_route_to_page_key((string)($pageMeta['canonical_path'] ?? $currentPath), $store) : null;
    $adminBar = [
        'panel' => zeynart_admin_url('/', $store),
        'edit' => $pgKey ? zeynart_admin_url('?edit_page=' . rawurlencode((string)$pgKey), $store) : zeynart_admin_url('?view=pages', $store),
    ];
}
$langs = ascam_languages($store);
$bodyClass = trim('page ' . (string)($pageMeta['body_class'] ?? ''));
?><!DOCTYPE html>
<html lang="<?= e(zeynart_lang_meta()['bcp47'] ?? 'tr-TR') ?>">
<head>
<?= render_seo_head($store, $pageMeta ?? []) ?>
<script>document.documentElement.classList.add('js');</script>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip" href="#main"><?= e(at('İçeriğe geç')) ?></a>

<header class="hdr" data-hdr>
  <div class="hdr__bar">
    <a class="hdr__brand" href="<?= e(url('/')) ?>" aria-label="<?= e((string)($headerMenu['logo_aria'] ?? ($site['name'] ?? ''))) ?>">
      <?php if ($logoSrc === '/uploads/2020/06/cropped-logo.png'): ?>
      <span class="hdr__emblem" aria-hidden="true"><?= ascam_img($logoSrc, '', ['loading' => 'eager', 'fetchpriority' => 'high', 'class' => 'hdr__logo']) ?></span>
      <span class="hdr__wordmark" aria-hidden="true">AS <span>CAM</span></span>
      <?php else: ?>
      <?= ascam_img($logoSrc, $logoAlt, ['loading' => 'eager', 'fetchpriority' => 'high', 'class' => 'hdr__logo']) ?>
      <?php endif; ?>
    </a>

    <nav class="hdr__nav" aria-label="<?= e(at('Ana menü')) ?>">
      <ul role="list">
        <?php foreach ($navItems as $it):
            $href = (string)($it['url'] ?? '/');
            $active = is_nav_active($href, $currentPath);
            $isProducts = rtrim($href, '/') . '/' === $productsBase && $products !== [];
            $target = ($it['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';
        ?>
        <li class="hdr__item<?= $isProducts ? ' hdr__item--mega' : '' ?>">
          <a class="hdr__link" href="<?= e(url($href)) ?>"<?= $active ? ' aria-current="page"' : '' ?><?= $target ?>><?= e((string)($it['label'] ?? '')) ?></a>
          <?php if ($isProducts): ?>
          <button type="button" class="hdr__caret" aria-expanded="false" aria-controls="mega" data-mega-toggle aria-label="<?= e(at('Ürün grupları')) ?>"><?= ascam_icon('chev-down', 16) ?></button>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="hdr__actions">
      <?php if (count($langs) > 1 && ($headerMenu['show_language'] ?? true) !== false): ?>
      <div class="lang" role="group" aria-label="<?= e(at('Dil')) ?>">
        <?php foreach ($langs as $lc): ?>
        <a href="<?= e(lang_url((string)($pageMeta['canonical_path'] ?? $currentPath), $lc)) ?>" hreflang="<?= e($lc) ?>" lang="<?= e($lc) ?>"<?= $lc === $curLang ? ' aria-current="true"' : '' ?>><?= e(strtoupper($lc)) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (($headerMenu['show_cta'] ?? true) !== false && !empty($headerMenu['cta_label'])): ?>
      <a class="btn btn--ink btn--sm hdr__cta" href="<?= e(url((string)($headerMenu['cta_url'] ?? '/iletisim/'))) ?>"><?= e((string)$headerMenu['cta_label']) ?></a>
      <?php endif; ?>
      <button type="button" class="hdr__burger" aria-expanded="false" aria-controls="mnav" data-mnav-toggle aria-label="<?= e(at('Menüyü aç')) ?>">
        <span></span><span></span>
      </button>
    </div>
  </div>

  <?php if ($products !== []): ?>
  <div class="mega" id="mega" data-mega hidden>
    <div class="mega__in">
      <p class="mega__label"><?= e(at('Ürün grupları')) ?> <span><?= count($products) ?></span></p>
      <ul class="mega__grid" role="list">
        <?php foreach ($products as $i => $p): ?>
        <li>
          <a class="mega__card" href="<?= e(url(ascam_product_url($store, $p))) ?>">
            <span class="mega__thumb"><?= ascam_img(ascam_product_cover($p), '', ['sizes' => '160px']) ?></span>
            <span class="mega__num"><?= e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
            <span class="mega__name"><?= e((string)($p['title'] ?? '')) ?></span>
            <span class="mega__meta"><?= count(ascam_product_gallery($p)) ?> <?= e(at('görsel')) ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <a class="mega__all" href="<?= e(url($productsBase)) ?>"><?= e(at('Tüm ürünler')) ?> <?= ascam_icon('arrow', 18) ?></a>
    </div>
  </div>
  <?php endif; ?>
</header>

<div class="mnav" id="mnav" data-mnav hidden>
  <div class="mnav__in">
    <p class="mnav__eyebrow"><?= e((string)($headerMenu['mobile_eyebrow'] ?? '')) ?></p>
    <nav aria-label="<?= e(at('Ana menü')) ?>">
      <ol class="mnav__list" role="list">
        <?php foreach ($navItems as $i => $it):
            $href = (string)($it['url'] ?? '/');
            $isProducts = rtrim($href, '/') . '/' === $productsBase && $products !== [];
        ?>
        <li>
          <a class="mnav__link" href="<?= e(url($href)) ?>"<?= is_nav_active($href, $currentPath) ? ' aria-current="page"' : '' ?>>
            <small><?= e(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)) ?></small><?= e((string)($it['label'] ?? '')) ?>
          </a>
          <?php if ($isProducts): ?>
          <ul class="mnav__sub" role="list">
            <?php foreach ($products as $p): ?>
            <li><a href="<?= e(url(ascam_product_url($store, $p))) ?>"><?= e((string)($p['title'] ?? '')) ?><span><?= count(ascam_product_gallery($p)) ?></span></a></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <div class="mnav__quick">
      <?php if (($tel = ascam_tel_href($site)) !== ''): ?><a href="<?= e($tel) ?>"><?= ascam_icon('phone') ?><?= e(at('Ara')) ?></a><?php endif; ?>
      <?php if (!empty($site['email'])): ?><a href="mailto:<?= e((string)$site['email']) ?>"><?= ascam_icon('mail') ?><?= e(at('E-posta')) ?></a><?php endif; ?>
      <?php if (($maps = ascam_maps_url($store)) !== ''): ?><a href="<?= e($maps) ?>" target="_blank" rel="noopener"><?= ascam_icon('pin') ?><?= e(at('Yol tarifi')) ?></a><?php endif; ?>
    </div>
    <?php if (count($langs) > 1): ?>
    <div class="lang lang--m" role="group" aria-label="<?= e(at('Dil')) ?>">
      <?php foreach ($langs as $lc): ?>
      <a href="<?= e(lang_url((string)($pageMeta['canonical_path'] ?? $currentPath), $lc)) ?>" hreflang="<?= e($lc) ?>"<?= $lc === $curLang ? ' aria-current="true"' : '' ?>><?= e(strtoupper($lc)) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($adminBar !== null): ?>
<div class="adminbar" role="region" aria-label="Yönetim">
  <a href="<?= e($adminBar['panel']) ?>"><?= e(at('Paneli aç')) ?></a>
  <a href="<?= e($adminBar['edit']) ?>"><?= e(at('Bu sayfayı düzenle')) ?></a>
</div>
<?php endif; ?>

<main id="main" tabindex="-1">
