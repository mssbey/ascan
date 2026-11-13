<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ascam_theme.php';
require_once __DIR__ . '/quick_contact.php';
require_once __DIR__ . '/cookie_consent.php';

/**
 * ASCAM teması — alt bilgi.
 * Sütunlar: Panel > Menüler > Alt Menü (menus.footer.columns). İletişim: Panel > Sayfalar > İletişim (site.*).
 */
$site = $store['site'] ?? [];
$footerMenu = $store['menus']['footer'] ?? [];
$tel = ascam_tel_href($site);
$maps = ascam_maps_url($store);
$columns = array_values(array_filter((array)($footerMenu['columns'] ?? []), 'is_array'));
$legalLinks = array_values(array_filter((array)($footerMenu['legal_links'] ?? []), static fn($l) => is_array($l) && !zeynart_is_internal_url_hidden($store, (string)($l['url'] ?? ''))));
$creditLabel = (string)($footerMenu['credit_label'] ?? '');
$creditUrl = (string)($footerMenu['credit_url'] ?? '');
$ccCfg = array_merge(zeynart_default_cookie_consent_config(), is_array($store['cookie_consent'] ?? null) ? $store['cookie_consent'] : []);
$ccEnabled = ($ccCfg['enabled'] ?? true) !== false;
$ccReopenLabel = zeynart_current_lang() === 'tr'
    ? (string)($ccCfg['footer_reopen_label'] ?? 'Çerez Tercihleri')
    : at('Çerez Tercihleri');
?>
</main>

<footer class="ftr">
  <div class="wrap ftr__top">
    <div class="ftr__brand">
      <a class="ftr__logo" href="<?= e(url('/')) ?>"><?= ascam_img(ascam_logo_src($site), (string)($site['logo_alt'] ?? ($site['name'] ?? '')), ['sizes' => '240px']) ?></a>
      <p class="ftr__legal-name"><?= e((string)($site['legal_name'] ?? '')) ?></p>
      <?php if (!empty($site['tagline'])): ?><p class="ftr__tag"><em><?= e((string)$site['tagline']) ?>…</em></p><?php endif; ?>
    </div>

    <address class="ftr__contact">
      <p class="ftr__h"><?= e(at('Merkez ofis')) ?></p>
      <?php if (!empty($site['address'])): ?>
      <a class="ftr__addr" href="<?= e($maps) ?>" target="_blank" rel="noopener"><?= e((string)$site['address']) ?><br/><?= e((string)($site['city'] ?? '')) ?></a>
      <?php endif; ?>
      <ul role="list">
        <?php if ($tel !== '' && !empty($site['phone'])): ?><li><span><?= e(at('Tel')) ?></span><a href="<?= e($tel) ?>"><?= e((string)$site['phone']) ?></a></li><?php endif; ?>
        <?php if (!empty($site['fax'])): ?><li><span><?= e(at('Faks')) ?></span><?= e((string)$site['fax']) ?></li><?php endif; ?>
        <?php if (!empty($site['email'])): ?><li><span><?= e(at('E-posta')) ?></span><a href="mailto:<?= e((string)$site['email']) ?>"><?= e((string)$site['email']) ?></a></li><?php endif; ?>
      </ul>
    </address>

    <?php foreach ($columns as $col): ?>
    <nav class="ftr__col" aria-label="<?= e((string)($col['title'] ?? '')) ?>">
      <p class="ftr__h"><?= e((string)($col['title'] ?? '')) ?></p>
      <ul role="list">
        <?php foreach ((array)($col['links'] ?? []) as $lnk):
            if (!is_array($lnk) || zeynart_is_internal_url_hidden($store, (string)($lnk['url'] ?? '/'))) { continue; }
        ?>
        <li><a href="<?= e(url((string)($lnk['url'] ?? '/'))) ?>"<?= ($lnk['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener"' : '' ?>><?= e((string)($lnk['label'] ?? '')) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endforeach; ?>
  </div>

  <div class="wrap ftr__bottom">
    <p><?= e((string)($footerMenu['copyright'] ?? ('© ' . date('Y') . ' ' . ($site['name'] ?? '')))) ?></p>
    <ul class="ftr__legal" role="list">
      <?php foreach ($legalLinks as $l): ?><li><a href="<?= e(url((string)$l['url'])) ?>"><?= e((string)($l['label'] ?? '')) ?></a></li><?php endforeach; ?>
      <?php if ($ccEnabled): ?><li><button type="button" class="ftr__cookie" data-open-cookie-prefs="1"><?= e($ccReopenLabel) ?></button></li><?php endif; ?>
      <?php if ($creditLabel !== ''): ?><li><a class="ftr__credit" href="<?= e($creditUrl) ?>" target="_blank" rel="noopener"><?= e($creditLabel) ?></a></li><?php endif; ?>
    </ul>
  </div>
</footer>

<?= render_quick_contact_bar($store) ?>
<?= zeynart_render_cookie_banner($store) ?>
<?= ascam_lightbox_shell() ?>
<script src="<?= e(asset_url('/assets/js/ascam.js')) ?>" defer></script>
</body>
</html>
