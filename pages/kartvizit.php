<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/agency_powerhouse.php';

/** @var array $activeVcard */
$vc = $activeVcard;
$vcSlug = (string)($vc['slug'] ?? 'zeynart');
$cardPublicUrl = rtrim(canonical_url('/kartvizit/' . $vcSlug . '/', $store, 'tr'), '/') . '/';
$vcfUrl = url('/kartvizit/' . $vcSlug . '/rehber/');
$qrSvgInline = zeynart_qr_render_svg($cardPublicUrl, '#051319', '#ffffff', 3, 280);

$waDigits = preg_replace('/\D+/', '', (string)($vc['whatsapp'] ?? '')) ?: '';
$waLink = $waDigits !== ''
    ? ('https://wa.me/' . $waDigits . '?text=' . rawurlencode((string)($vc['whatsapp_greeting'] ?? 'Merhaba, dijital kartvizitiniz üzerinden ulaşıyorum.')))
    : '';
$telHref = trim((string)($vc['phone_raw'] ?? '')) ?: preg_replace('/[^\d+]+/', '', (string)($vc['phone'] ?? ''));

$hasBilling = !empty($vc['show_billing']) && (
    trim((string)($vc['tax_title'] ?? '')) !== ''
    || trim((string)($vc['tax_number'] ?? '')) !== ''
    || !empty($vc['ibans'])
);

$billingSummaryText = '';
if ($hasBilling) {
    $bLines = [];
    if (!empty($vc['tax_title'])) {
        $bLines[] = 'Ünvan: ' . $vc['tax_title'];
    }
    if (!empty($vc['tax_office'])) {
        $bLines[] = 'Vergi Dairesi: ' . $vc['tax_office'];
    }
    if (!empty($vc['tax_number'])) {
        $bLines[] = 'Vergi No: ' . $vc['tax_number'];
    }
    if (!empty($vc['address'])) {
        $bLines[] = 'Adres: ' . $vc['address'];
    }
    foreach (($vc['ibans'] ?? []) as $ib) {
        $bLines[] = ($ib['bank'] ?? 'Banka') . ' (' . ($ib['currency'] ?? 'TL') . '): ' . ($ib['iban'] ?? '');
    }
    $billingSummaryText = implode("\n", $bLines);
}

$vcSchema = [
    '@type' => 'Person',
    '@id' => $cardPublicUrl . '#vcard',
    'name' => (string)($vc['full_name'] ?? ''),
    'jobTitle' => (string)($vc['job_title'] ?? ''),
    'worksFor' => [
        '@type' => 'Organization',
        'name' => (string)($vc['company'] ?? ($store['site']['legal_name'] ?? 'Zeynart')),
    ],
    'url' => $cardPublicUrl,
];
if (!empty($vc['email'])) {
    $vcSchema['email'] = (string)$vc['email'];
}
if (!empty($telHref)) {
    $vcSchema['telephone'] = (string)$telHref;
}

$pageMeta = [
    'title' => (string)$vc['full_name'] . (!empty($vc['job_title']) ? (' — ' . $vc['job_title']) : '') . ' | Dijital Kartvizit',
    'description' => trim((string)($vc['bio'] ?? '')) ?: ((string)$vc['full_name'] . ' kurumsal dijital iletişim kartı, rehbere hızlı kayıt ve kurumsal bağlantılar.'),
    'robots' => !empty($vc['noindex']) ? 'noindex, nofollow' : 'index, follow',
    'canonical_path' => '/kartvizit/' . $vcSlug . '/',
    'schema_nodes' => [$vcSchema],
    'breadcrumbs' => [
        ['name' => t('Ana sayfa'), 'url' => '/'],
        ['name' => (string)$vc['full_name'], 'url' => '/kartvizit/' . $vcSlug . '/'],
    ],
];

require INCLUDES_DIR . '/site_header.php';
?>
<section class="zvc-stage">
  <div class="zvc-ambient" aria-hidden="true"></div>
  <div class="container zvc-wrap">
    <article class="zvc-card" aria-label="<?= e((string)$vc['full_name']) ?> Dijital Kartvizit">
      <div class="zvc-card-banner" aria-hidden="true">
        <div class="zvc-banner-grid"></div>
        <div class="zvc-banner-brand">
          <img src="<?= e(url((string)($store['site']['logo'] ?? '/images/zeynart-logo.avif'))) ?>" alt="" width="118" height="44" decoding="async"/>
          <span class="zvc-verified-pill">✓ Kurumsal Kartvizit</span>
        </div>
      </div>

      <div class="zvc-profile-head">
        <div class="zvc-avatar-ring">
          <img src="<?= e(url((string)($vc['avatar'] ?? '/icon.png'))) ?>" alt="<?= e((string)$vc['full_name']) ?>" width="96" height="96" decoding="async"/>
        </div>
        <button type="button" class="zvc-qr-trigger" id="zvcOpenQrBtn" aria-label="QR Kodu Büyüt">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M14 20h7M20 20v0"/></svg>
          <span>QR Kod</span>
        </button>
      </div>

      <div class="zvc-identity">
        <h1><?= e((string)$vc['full_name']) ?></h1>
        <?php if (!empty($vc['job_title'])): ?>
          <p class="zvc-role"><?= e((string)$vc['job_title']) ?></p>
        <?php endif; ?>
        <?php if (!empty($vc['company']) && $vc['company'] !== $vc['full_name']): ?>
          <p class="zvc-company"><?= e((string)$vc['company']) ?></p>
        <?php endif; ?>
        <?php if (!empty($vc['bio'])): ?>
          <p class="zvc-bio"><?= e((string)$vc['bio']) ?></p>
        <?php endif; ?>
      </div>

      <!-- PRIMARY CTA: SAVE TO PHONE CONTACTS (.VCF) + WHATSAPP -->
      <div class="zvc-primary-cta">
        <a class="zvc-btn-save" href="<?= e($vcfUrl) ?>" download="<?= e($vcSlug) ?>.vcf">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          <span>Rehbere Tek Tıkla Kaydet (.vcf)</span>
        </a>
        <?php if ($waLink !== ''): ?>
        <a class="zvc-btn-wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener noreferrer">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
          <span>WhatsApp’tan Yaz</span>
        </a>
        <?php endif; ?>
      </div>

      <!-- CONTACT CHANNELS GRID -->
      <div class="zvc-links-list">
        <?php if (!empty($vc['phone']) && $telHref !== ''): ?>
        <a class="zvc-link-row" href="tel:<?= e($telHref) ?>">
          <span class="zvc-link-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </span>
          <span class="zvc-link-meta">
            <small>Telefonla Hemen Ara</small>
            <strong><?= e((string)$vc['phone']) ?></strong>
          </span>
          <span class="zvc-link-arr">↗</span>
        </a>
        <?php endif; ?>

        <?php if (!empty($vc['email'])): ?>
        <a class="zvc-link-row" href="mailto:<?= e((string)$vc['email']) ?>">
          <span class="zvc-link-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          </span>
          <span class="zvc-link-meta">
            <small>Kurumsal E-Posta</small>
            <strong><?= e((string)$vc['email']) ?></strong>
          </span>
          <span class="zvc-link-arr">↗</span>
        </a>
        <?php endif; ?>

        <?php if (!empty($vc['website'])): ?>
        <a class="zvc-link-row" href="<?= e(str_starts_with((string)$vc['website'], 'http') ? (string)$vc['website'] : url((string)$vc['website'])) ?>" target="_blank" rel="noopener">
          <span class="zvc-link-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </span>
          <span class="zvc-link-meta">
            <small>Kurumsal Web Sitesi</small>
            <strong><?= e(preg_replace('~^https?://~i', '', rtrim((string)$vc['website'], '/'))) ?></strong>
          </span>
          <span class="zvc-link-arr">↗</span>
        </a>
        <?php endif; ?>

        <?php if (!empty($vc['catalog_url'])): ?>
        <a class="zvc-link-row" href="<?= e(str_starts_with((string)$vc['catalog_url'], 'http') ? (string)$vc['catalog_url'] : url((string)$vc['catalog_url'])) ?>">
          <span class="zvc-link-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          </span>
          <span class="zvc-link-meta">
            <small>Çözümler &amp; Hızlı Teklif</small>
            <strong><?= e((string)($vc['catalog_label'] ?: 'Hizmetler & Teklif Sihirbazı')) ?></strong>
          </span>
          <span class="zvc-link-arr">↗</span>
        </a>
        <?php endif; ?>

        <?php if (!empty($vc['address'])): ?>
        <a class="zvc-link-row" href="<?= e(!empty($vc['maps_url']) ? (string)$vc['maps_url'] : ('https://maps.google.com/?q=' . rawurlencode((string)$vc['address']))) ?>" target="_blank" rel="noopener noreferrer">
          <span class="zvc-link-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
          </span>
          <span class="zvc-link-meta">
            <small>Ofis Konumu &amp; Yol Tarifi</small>
            <strong><?= e((string)$vc['address']) ?></strong>
          </span>
          <span class="zvc-link-arr">↗</span>
        </a>
        <?php endif; ?>
      </div>

      <?php if (!empty($vc['linkedin']) || !empty($vc['instagram'])): ?>
      <div class="zvc-socials">
        <?php if (!empty($vc['linkedin'])): ?>
          <a href="<?= e((string)$vc['linkedin']) ?>" target="_blank" rel="noopener noreferrer" class="zvc-soc-pill">LinkedIn ↗</a>
        <?php endif; ?>
        <?php if (!empty($vc['instagram'])): ?>
          <a href="<?= e((string)$vc['instagram']) ?>" target="_blank" rel="noopener noreferrer" class="zvc-soc-pill">Instagram ↗</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- OPTIONAL BILLING / TAX / IBAN PANEL -->
      <?php if ($hasBilling): ?>
      <div class="zvc-billing-box">
        <div class="zvc-billing-head">
          <div>
            <span class="zvc-billing-kicker">KURUMSAL CARİ &amp; BANKA BİLGİLERİ</span>
            <h2>Fatura &amp; IBAN Bilgileri</h2>
          </div>
          <?php if ($billingSummaryText !== ''): ?>
          <button type="button" class="zvc-copy-chip" data-zvc-copy="<?= e($billingSummaryText) ?>">📋 Tümünü Kopyala</button>
          <?php endif; ?>
        </div>

        <?php if (!empty($vc['tax_title']) || !empty($vc['tax_office']) || !empty($vc['tax_number'])): ?>
        <div class="zvc-tax-grid">
          <?php if (!empty($vc['tax_title'])): ?>
            <div><small>Resmi Ticari Ünvan</small><strong><?= e((string)$vc['tax_title']) ?></strong></div>
          <?php endif; ?>
          <?php if (!empty($vc['tax_office'])): ?>
            <div><small>Vergi Dairesi</small><strong><?= e((string)$vc['tax_office']) ?></strong></div>
          <?php endif; ?>
          <?php if (!empty($vc['tax_number'])): ?>
            <div><small>Vergi / Mersis No</small><strong><?= e((string)$vc['tax_number']) ?></strong></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($vc['ibans'])): ?>
        <div class="zvc-iban-list">
          <?php foreach ($vc['ibans'] as $ib): ?>
          <div class="zvc-iban-item">
            <div class="zvc-iban-top">
              <strong><?= e((string)($ib['bank'] ?? 'Banka Hesabı')) ?></strong>
              <span class="zvc-curr-badge"><?= e((string)($ib['currency'] ?? 'TL')) ?></span>
            </div>
            <code class="zvc-iban-code"><?= e((string)($ib['iban'] ?? '')) ?></code>
            <div class="zvc-iban-foot">
              <span><?= e((string)($ib['holder'] ?: ($vc['tax_title'] ?: $vc['company']))) ?></span>
              <button type="button" class="zvc-copy-chip" data-zvc-copy="<?= e((string)($ib['iban'] ?? '')) ?>">📋 IBAN Kopyala</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="zvc-card-foot">
        <button type="button" class="zvc-share-card-btn" id="zvcShareCardBtn" data-card-url="<?= e($cardPublicUrl) ?>" data-card-title="<?= e((string)$vc['full_name']) ?>">
          🔗 Kartvizit Bağlantısını Paylaş / Kopyala
        </button>
      </div>
    </article>
  </div>

  <!-- FULLSCREEN SCANNER QR MODAL -->
  <div class="zvc-qr-modal" id="zvcQrModal" hidden>
    <div class="zvc-qr-dialog" role="dialog" aria-modal="true" aria-label="Dijital Kartvizit QR Kodu">
      <div class="zvc-qr-dialog-head">
        <strong>📱 Kamerayla Okutun</strong>
        <button type="button" id="zvcCloseQrBtn" aria-label="Kapat">✕</button>
      </div>
      <div class="zvc-qr-frame">
        <?= $qrSvgInline ?>
      </div>
      <p class="zvc-qr-caption"><?= e((string)$vc['full_name']) ?> · <?= e(preg_replace('~^https?://~i', '', $cardPublicUrl)) ?></p>
      <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:12px">
        <a class="zvc-copy-chip" href="<?= e($vcfUrl) ?>" download="<?= e($vcSlug) ?>.vcf">📇 .vcf İndir</a>
        <button type="button" class="zvc-copy-chip" data-zvc-copy="<?= e($cardPublicUrl) ?>">🔗 Linki Kopyala</button>
      </div>
    </div>
  </div>
</section>

<script>
(function(){
  var modal = document.getElementById('zvcQrModal');
  var openBtn = document.getElementById('zvcOpenQrBtn');
  var closeBtn = document.getElementById('zvcCloseQrBtn');
  if (openBtn && modal) {
    openBtn.addEventListener('click', function(){ modal.hidden = false; });
    if (closeBtn) closeBtn.addEventListener('click', function(){ modal.hidden = true; });
    modal.addEventListener('click', function(e){ if (e.target === modal) modal.hidden = true; });
  }
  function copyText(txt, btn) {
    var orig = btn.textContent;
    var done = function(){
      btn.textContent = '✓ Kopyalandı';
      setTimeout(function(){ btn.textContent = orig; }, 1800);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(txt).then(done).catch(function(){});
    } else {
      var ta = document.createElement('textarea');
      ta.value = txt; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch(_) {}
      document.body.removeChild(ta);
    }
  }
  document.querySelectorAll('[data-zvc-copy]').forEach(function(b){
    b.addEventListener('click', function(){
      copyText(b.getAttribute('data-zvc-copy') || '', b);
    });
  });
  var shareBtn = document.getElementById('zvcShareCardBtn');
  if (shareBtn) {
    shareBtn.addEventListener('click', function(){
      var u = shareBtn.getAttribute('data-card-url') || location.href;
      var t = shareBtn.getAttribute('data-card-title') || document.title;
      if (navigator.share) {
        navigator.share({ title: t, url: u }).catch(function(){});
      } else {
        copyText(u, shareBtn);
      }
    });
  }
})();
</script>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
