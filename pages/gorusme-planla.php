<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/agency_growth_suite.php';
require_once __DIR__ . '/../includes/page_builder.php';

$bkCfg = zeynart_get_booking_settings($store);
$availDays = zeynart_compute_available_booking_days($store);
$bookingSlug = zeynart_page_slug($store, 'gorusme-planla') ?: 'gorusme-planla';
$seoPage = $store['seo']['pages']['gorusme-planla'] ?? [];

$pageMeta = [
    'title' => ($seoPage['meta_title'] ?? '') ?: (t('15 Dakika Dijital Keşif Görüşmesi Planla') . ' | ZEYNART'),
    'description' => ($seoPage['meta_desc'] ?? '') ?: t($bkCfg['lead']),
    'robots' => ($seoPage['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => '/' . $bookingSlug . '/',
    'breadcrumbs' => [
        ['name' => t('Ana sayfa'), 'url' => '/'],
        ['name' => t('Keşif Görüşmesi Planla'), 'url' => '/' . $bookingSlug . '/'],
    ],
];

$token = csrf_token();
require INCLUDES_DIR . '/site_header.php';
?>
<section class="booking-page-section">
  <div class="container page-intro" style="padding-bottom:28px">
    <p class="eyebrow"><span></span><?= e(t($bkCfg['eyebrow'])) ?></p>
    <h1><?= e(t($bkCfg['heading'])) ?></h1>
    <p><?= e(t($bkCfg['lead'])) ?></p>
  </div>

  <div class="container" style="padding-bottom:90px">
    <div class="booking-studio-shell" id="zeynartBookingShell">
      <form id="zeynartBookingForm" method="post" action="<?= e(url('/api/gorusme-planla')) ?>" toolname="schedule_discovery_meeting" tooldescription="Zeynart dijital strateji ekibiyle 15-30 dakikalık ücretsiz online veya telefon keşif görüşmesi planlar.">
        <input type="hidden" name="csrf_token" value="<?= e($token) ?>" toolparamdescription="Güvenlik doğrulama anahtarı"/>
        <input type="hidden" name="channel" id="bkChannelInput" value="google_meet" toolparamdescription="Görüşme kanalı: google_meet, whatsapp veya phone"/>
        <input type="hidden" name="date" id="bkDateInput" value="<?= e($availDays[0]['date'] ?? '') ?>" toolparamdescription="YYYY-MM-DD formatında görüşme tarihi"/>
        <input type="hidden" name="time" id="bkTimeInput" value="" toolparamdescription="SS:DD formatında görüşme saati"/>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0" aria-hidden="true"/>

        <div class="booking-grid">
          <div class="booking-left-col">
            <div class="booking-step-block">
              <span class="booking-step-num">01 · <?= e(t('GÖRÜŞME KANALI SEÇİN')) ?></span>
              <div class="booking-channel-grid">
                <?php foreach ($bkCfg['channels'] as $cIdx => $ch): ?>
                <button type="button" class="booking-channel-card<?= $cIdx === 0 ? ' active' : '' ?>" data-bk-channel="<?= e($ch['key']) ?>">
                  <small><?= e(t($ch['badge'])) ?></small>
                  <strong><?= e(t($ch['label'])) ?></strong>
                </button>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="booking-step-block">
              <span class="booking-step-num">02 · <?= e(t('GÜN & MÜSAİT SAAT DİLİMİ')) ?> (<?= (int)$bkCfg['slot_minutes'] ?> <?= e(t('dk')) ?>)</span>
              <?php if ($availDays === []): ?>
                <p class="muted">Şu an yakın tarihli boş saat dilimi görünmüyor; lütfen doğrudan iletişim sayfamızdan ulaşın.</p>
              <?php else: ?>
                <div class="booking-days-strip" role="group" aria-label="<?= e(t('Müsait günler')) ?>">
                  <?php foreach ($availDays as $dIdx => $dRow): ?>
                  <button type="button" class="booking-day-pill<?= $dIdx === 0 ? ' active' : '' ?>" data-bk-date="<?= e($dRow['date']) ?>">
                    <small><?= e($dRow['dow_short']) ?></small>
                    <strong><?= e($dRow['day_num']) ?></strong>
                    <span><?= e($dRow['month_short']) ?></span>
                  </button>
                  <?php endforeach; ?>
                </div>

                <?php foreach ($availDays as $dIdx => $dRow): ?>
                <div class="booking-slots-grid" data-bk-slots-for="<?= e($dRow['date']) ?>" style="<?= $dIdx === 0 ? '' : 'display:none' ?>">
                  <?php foreach ($dRow['slots'] as $sRow): ?>
                  <button type="button" class="booking-slot-btn<?= empty($sRow['available']) ? ' is-taken' : '' ?>" data-bk-time="<?= e($sRow['time']) ?>" <?= empty($sRow['available']) ? 'disabled' : '' ?>>
                    <?= e($sRow['time']) ?>
                  </button>
                  <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="booking-right-col">
            <span class="booking-step-num">03 · <?= e(t('İLETİŞİM & PROJE GÜNDEMİ')) ?></span>
            <div class="booking-selected-summary" id="bkSelectedSummary">
              <span>📅 <?= e($availDays[0]['label'] ?? '-') ?></span>
              <strong id="bkSummaryTimeText"><?= e(t('Lütfen soldan saat seçin')) ?></strong>
            </div>

            <div class="contact-form" style="gap:14px">
              <label class="form-field">
                <?= e(t('Ad soyad *')) ?>
                <input type="text" name="name" required minlength="2" maxlength="100" autocomplete="name" placeholder="<?= e(t('Adınız ve soyadınız')) ?>" toolparamdescription="Görüşmeye katılacak kişinin adı ve soyadı"/>
              </label>

              <label class="form-field">
                <?= e(t('Cep telefonu *')) ?>
                <input type="tel" inputmode="tel" name="phone" required minlength="10" maxlength="30" autocomplete="tel" placeholder="05XX XXX XX XX" toolparamdescription="WhatsApp veya telefon onayı için zorunlu cep telefonu"/>
              </label>

              <div class="form-row">
                <label class="form-field">
                  <?= e(t('Kurumsal e-posta')) ?>
                  <input type="email" name="email" maxlength="160" autocomplete="email" placeholder="siz@sirketiniz.com" toolparamdescription="Takvim daveti için e-posta adresi"/>
                </label>
                <label class="form-field">
                  <?= e(t('Şirket / Marka')) ?>
                  <input type="text" name="company" maxlength="120" autocomplete="organization" placeholder="<?= e(t('Şirket veya marka adı')) ?>" toolparamdescription="Şirket veya kurum adı"/>
                </label>
              </div>

              <label class="form-field">
                <?= e(t('Görüşmek istediğiniz konu')) ?>
                <textarea name="topic" rows="3" maxlength="800" placeholder="<?= e(t('Örn: Çok dilli kurumsal web yenileme, hız optimizasyonu veya e-ticaret...')) ?>" toolparamdescription="Görüşme gündemi ve proje hedefi"></textarea>
              </label>

              <button type="submit" class="button button-primary" id="bkSubmitBtn" style="width:100%;justify-content:center">
                <?= e(t('Görüşmeyi Onayla & Takvime İşle')) ?> →
              </button>
              <div id="bkFormAlert" class="tracker-feedback-alert" style="display:none"></div>
            </div>
          </div>
        </div>
      </form>

      <div id="bkSuccessPanel" class="booking-success-panel" style="display:none">
        <div class="booking-success-badge">✓ <?= e(t('RANDEVUNUZ ONAYLANDI')) ?></div>
        <h2 id="bkSuccessTitle">Keşif Görüşmeniz Planlandı</h2>
        <p id="bkSuccessMeta" class="muted"></p>
        <div class="booking-success-actions">
          <a href="#" id="bkDownloadIcsBtn" class="button button-primary">📅 <?= e(t('Takvime Ekle (.ics İndir)')) ?></a>
          <a href="#" id="bkWhatsappBtn" target="_blank" rel="noopener" class="button">💬 <?= e(t('WhatsApp ile Teyit Gönder')) ?></a>
          <a href="<?= e(url('/')) ?>" class="button">← <?= e(t('Ana sayfaya dön')) ?></a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
