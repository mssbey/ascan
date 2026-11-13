<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/ascam_theme.php';

if (!headers_sent()) {
    http_response_code(404);
}
$store = $store ?? load_store();
$currentRoutePath = '/404';
$pageMeta = [
    'title' => at('Sayfa bulunamadı') . ' | ' . ($store['site']['name'] ?? ''),
    'description' => at('Aradığınız sayfa taşınmış ya da kaldırılmış olabilir.'),
    'robots' => 'noindex, follow',
    'canonical_path' => '/404/',
    'body_class' => 'page--404',
];
require INCLUDES_DIR . '/site_header.php';
?>
<section class="nf">
  <div class="wrap nf__in">
    <p class="nf__code" aria-hidden="true">404</p>
    <h1 class="nf__h"><?= e(at('Sayfa bulunamadı')) ?></h1>
    <p><?= e(at('Aradığınız sayfa taşınmış ya da kaldırılmış olabilir.')) ?></p>
    <div class="nf__actions">
      <a class="btn btn--ink" href="<?= e(url('/')) ?>"><?= e(at('Ana sayfaya dönün')) ?></a>
      <a class="btn btn--line" href="<?= e(url(ascam_products_base($store))) ?>"><?= e(at('Ürünlere göz atın')) ?></a>
    </div>
  </div>
</section>
<?php require INCLUDES_DIR . '/site_footer.php';
