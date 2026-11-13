<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/ascam_theme.php';

/**
 * İletişim — Panel > Sayfalar > İletişim (contact.* + site.phone/fax/email/address/city)
 * Form, panelin mevcut /api/iletisim uç noktasına gönderir; talepler Panel > Mesajlar'a düşer (SMTP açıksa e-posta da gider).
 */
$ct = $store['contact'] ?? [];
$site = $store['site'] ?? [];
$seoPage = $store['seo']['pages']['iletisim'] ?? [];
$slug = '/' . zeynart_page_slug($store, 'iletisim') . '/';
$banner = (string)($ct['banner_image'] ?? '');
$token = csrf_token();
$products = ascam_products($store);
$preselect = (string)($_GET['konu'] ?? '');
$status = (string)($_GET['durum'] ?? '');
$tel = ascam_tel_href($site);
$maps = ascam_maps_url($store);

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: ('İletişim | ' . ($site['name'] ?? '')),
    'description' => ($seoPage['meta_desc'] ?? '') ?: (string)($ct['lead'] ?? ''),
    'og_title' => $seoPage['og_title'] ?? '',
    'og_description' => $seoPage['og_desc'] ?? '',
    'og_image' => ($seoPage['og_image'] ?? '') ?: $banner,
    'robots' => ($seoPage['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => $slug,
    'body_class' => 'page--contact',
    'breadcrumbs' => [
        ['name' => at('Ana sayfa'), 'url' => '/'],
        ['name' => at('İletişim'), 'url' => $slug],
    ],
];

require INCLUDES_DIR . '/site_header.php';
?>
<section class="contact" aria-labelledby="ct-h">
  <div class="wrap contact__grid">
    <div class="contact__info">
      <nav class="crumbs crumbs--dark" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>"><?= e(at('Ana sayfa')) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e(at('İletişim')) ?></span></nav>
      <p class="eyebrow"><?= e((string)($ct['eyebrow'] ?? '')) ?></p>
      <h1 id="ct-h" class="contact__h"><?= ascam_heading((string)($ct['heading_line'] ?? ''), (string)($ct['heading_muted'] ?? '')) ?></h1>
      <?php if (!empty($ct['lead'])): ?><p class="contact__lead"><?= e((string)$ct['lead']) ?></p><?php endif; ?>

      <div class="quick" role="group" aria-label="<?= e(at('Bize ulaşın')) ?>">
        <?php if ($tel !== ''): ?><a class="quick__btn" href="<?= e($tel) ?>"><?= ascam_icon('phone', 22) ?><span><?= e(at('Ara')) ?></span></a><?php endif; ?>
        <?php if (!empty($site['email'])): ?><a class="quick__btn" href="mailto:<?= e((string)$site['email']) ?>"><?= ascam_icon('mail', 22) ?><span><?= e(at('E-posta')) ?></span></a><?php endif; ?>
        <?php if ($maps !== ''): ?><a class="quick__btn" href="<?= e($maps) ?>" target="_blank" rel="noopener"><?= ascam_icon('pin', 22) ?><span><?= e(at('Yol tarifi')) ?></span></a><?php endif; ?>
      </div>

      <dl class="cards">
        <?php if (($ct['show_address'] ?? true) !== false && !empty($site['address'])): ?>
        <div class="cards__item">
          <dt><?= e((string)($ct['address_label'] ?? at('Merkez ofis'))) ?></dt>
          <dd><address><?= e((string)$site['address']) ?><br/><?= e((string)($site['city'] ?? '')) ?></address>
            <?php if ($maps !== ''): ?><a class="link-arrow link-arrow--sm" href="<?= e($maps) ?>" target="_blank" rel="noopener"><?= e(at('Haritada aç')) ?><?= ascam_icon('arrow-up-right', 16) ?></a><?php endif; ?>
          </dd>
        </div>
        <?php endif; ?>
        <?php if (($ct['show_phone'] ?? true) !== false && !empty($site['phone'])): ?>
        <div class="cards__item">
          <dt><?= e((string)($ct['phone_label'] ?? at('Telefon'))) ?></dt>
          <dd><a href="<?= e($tel) ?>"><?= e((string)$site['phone']) ?></a> <small>(pbx)</small><?php if (!empty($site['fax'])): ?><br/><span class="muted"><?= e(at('Faks')) ?>:</span> <?= e((string)$site['fax']) ?><?php endif; ?></dd>
        </div>
        <?php endif; ?>
        <?php if (($ct['show_email'] ?? true) !== false && !empty($site['email'])): ?>
        <div class="cards__item">
          <dt><?= e((string)($ct['email_label'] ?? at('E-posta'))) ?></dt>
          <dd><a href="mailto:<?= e((string)$site['email']) ?>"><?= e((string)$site['email']) ?></a></dd>
        </div>
        <?php endif; ?>
      </dl>
    </div>

    <div class="contact__form glass">
      <p class="eyebrow"><?= e((string)($ct['form_eyebrow'] ?? '')) ?></p>
      <h2 class="contact__fh"><?= e((string)($ct['form_heading'] ?? '')) ?></h2>
      <?php if (!empty($ct['form_copy'])): ?><p class="contact__fcopy"><?= e((string)$ct['form_copy']) ?></p><?php endif; ?>

      <form class="form" method="post" action="<?= e(url('/api/iletisim')) ?>" data-contact-form novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($token) ?>"/>
        <input type="hidden" name="lang" value="<?= e(zeynart_current_lang()) ?>"/>
        <input type="hidden" name="budget" value="-"/>
        <div class="form__hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"/></label></div>

        <div class="form__row">
          <label class="field"><span><?= e(at('Adınız Soyadınız')) ?> *</span><input type="text" name="name" required minlength="2" maxlength="100" autocomplete="name"/></label>
          <label class="field"><span><?= e(at('E-posta')) ?> *</span><input type="email" name="email" required maxlength="200" autocomplete="email" inputmode="email"/></label>
        </div>
        <div class="form__row">
          <label class="field"><span><?= e(at('Telefon')) ?><?= ($ct['require_phone'] ?? false) ? ' *' : '' ?></span><input type="tel" name="phone" autocomplete="tel" inputmode="tel" maxlength="35"<?= ($ct['require_phone'] ?? false) ? ' required' : '' ?>/></label>
          <label class="field"><span><?= e(at('Konu')) ?> *</span>
            <select name="service" required>
              <option value="<?= e(at('Genel bilgi')) ?>"><?= e(at('Genel bilgi')) ?></option>
              <?php foreach ($products as $p): ?>
              <option value="<?= e((string)$p['title']) ?>"<?= $preselect === ($p['slug'] ?? '') ? ' selected' : '' ?>><?= e((string)$p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <label class="field"><span><?= e(at('Mesajınız')) ?> *</span><textarea name="message" rows="5" required minlength="10" maxlength="5000"></textarea></label>
        <label class="check"><input type="checkbox" name="consent" value="1" required/><span><?= e(at('Bilgilerimin bu talep kapsamında As Cam tarafından işlenmesini ve bana dönüş yapılmasını onaylıyorum.')) ?></span></label>
        <div class="form__foot">
          <p class="form__hint"><?= e(at('Zorunlu alanlar * ile işaretlidir.')) ?></p>
          <button type="submit" class="btn btn--red" data-label-sending="<?= e(at('Gönderiliyor…')) ?>"><?= e((string)($ct['submit_label'] ?? at('Gönder'))) ?><?= ascam_icon('arrow', 18) ?></button>
        </div>
        <p class="form__status<?= $status === 'basarili' ? ' is-ok' : ($status === 'hata' ? ' is-err' : '') ?>" role="status" aria-live="polite" data-form-status><?php
            if ($status === 'basarili') { echo e((string)(($ct['success_message'] ?? '') ?: at('Mesajınız alındı. En kısa sürede size dönüş yapacağız.'))); }
            elseif ($status === 'hata') { echo e(at('Bir sorun oluştu. Lütfen tekrar deneyin ya da bizi arayın.')); }
        ?></p>
      </form>
    </div>
  </div>
</section>
<?php require INCLUDES_DIR . '/site_footer.php';
