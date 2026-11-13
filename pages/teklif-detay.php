<?php
declare(strict_types=1);

if (!headers_sent()) {
    header('X-LiteSpeed-Cache-Control: no-cache');
}

require_once __DIR__ . '/../includes/enterprise_cms_suite.php';

/** @var array $activeProposal */
$prp = $activeProposal;
$prpCode = (string)($prp['code'] ?? '');
$proposalUrl = rtrim(canonical_url('/teklif/' . $prpCode . '/', $store, 'tr'), '/') . '/';
$qrSvg = zeynart_qr_render_svg($proposalUrl, '#051319', '#ffffff', 3, 180);

$sym = (string)($prp['currency_symbol'] ?? '₺');
$fmtMoney = static fn(float $n): string => number_format($n, 2, ',', '.') . ' ' . $sym;

$siteLegal = (string)($store['site']['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri');
$sitePhone = (string)($store['contact']['phone_display'] ?? ($store['site']['phone'] ?? ''));
$siteEmail = (string)($store['contact']['email'] ?? ($store['site']['email'] ?? 'info@zeynart.com'));
$waDigits = preg_replace('/\D+/', '', (string)($store['contact']['whatsapp'] ?? $sitePhone)) ?: '';
if (str_starts_with($waDigits, '0') && strlen($waDigits) === 11) {
    $waDigits = '9' . $waDigits;
} elseif (strlen($waDigits) === 10 && str_starts_with($waDigits, '5')) {
    $waDigits = '90' . $waDigits;
}
$waHref = $waDigits !== ''
    ? ('https://wa.me/' . $waDigits . '?text=' . rawurlencode('Merhaba, ' . ($prp['proposal_no'] ?? $prpCode) . ' numaralı "' . ($prp['project_title'] ?? '') . '" teklifimiz hakkında görüşmek istiyorum.'))
    : '';

$isApproved = ($prp['status'] ?? '') === 'approved';
$validUntil = (string)($prp['valid_until'] ?? '');
$isExpired = !$isApproved && $validUntil !== '' && strtotime($validUntil . ' 23:59:59') !== false && strtotime($validUntil . ' 23:59:59') < time();

$statusLabel = 'Onay Bekliyor';
$statusClass = 'is-pending';
if ($isApproved) {
    $statusLabel = '✓ Dijital Olarak Onaylandı';
    $statusClass = 'is-approved';
} elseif ($isExpired) {
    $statusLabel = 'Geçerlilik Süresi Doldu';
    $statusClass = 'is-expired';
}

$pageMeta = [
    'title' => (string)($prp['proposal_no'] ?? 'Proje Teklifi') . ' — ' . (string)($prp['project_title'] ?? 'Özel Teklif') . ' | ' . $siteLegal,
    'description' => (string)($prp['client_company'] ?: $prp['client_name']) . ' için hazırlanan özel dijital proje ve yatırım teklifi.',
    'robots' => 'noindex, nofollow',
    'canonical_path' => '/teklif/' . $prpCode . '/',
];

require INCLUDES_DIR . '/site_header.php';
?>
<section class="zprp-stage">
  <div class="zprp-ambient" aria-hidden="true"></div>
  <div class="container zprp-wrap">

    <div class="zprp-topbar no-print">
      <div class="zprp-topbar-meta">
        <span class="zprp-status-pill <?= e($statusClass) ?>" id="zprpTopStatusPill"><?= e($statusLabel) ?></span>
        <span class="zprp-code-tag">Teklif No: <strong><?= e((string)($prp['proposal_no'] ?? strtoupper($prpCode))) ?></strong></span>
      </div>
      <div class="zprp-topbar-actions">
        <button type="button" class="zprp-btn zprp-btn-ghost" id="zprpPrintBtn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          <span>PDF Yazdır / İndir</span>
        </button>
        <a class="zprp-btn zprp-btn-ghost" href="<?= e(url('/sozlesme/' . $prpCode . '/')) ?>">
          <span>📜 Hizmet Sözleşmesi</span>
        </a>
        <?php if (!empty($prp['automation_chain']['tracker_url']) || $isApproved): ?>
        <a class="zprp-btn zprp-btn-ghost" href="<?= e(url((string)($prp['automation_chain']['tracker_url'] ?? ('/proje-takip/' . $prpCode . '/')))) ?>">
          <span>🔐 Proje Takip Portalı</span>
        </a>
        <?php endif; ?>
        <?php if ($waHref !== ''): ?>
        <a class="zprp-btn zprp-btn-wa" href="<?= e($waHref) ?>" target="_blank" rel="noopener noreferrer">
          <span>WhatsApp ile Görüş</span>
        </a>
        <?php endif; ?>
        <?php if (!$isApproved && !$isExpired): ?>
        <a class="zprp-btn zprp-btn-primary" href="#zprpApproveBox">
          <span>✓ Hemen Onayla</span>
        </a>
        <?php endif; ?>
      </div>
    </div>

    <article class="zprp-sheet" id="zprpSheet">
      <header class="zprp-head">
        <div class="zprp-brand-col">
          <img class="zprp-logo" src="<?= e(url((string)($store['site']['logo'] ?? '/images/zeynart-logo.avif'))) ?>" alt="<?= e($siteLegal) ?>" width="150" height="58" decoding="async"/>
          <p class="zprp-eyebrow">ÖZEL KURUMSAL PROJE &amp; YATIRIM TEKLİFİ</p>
          <h1><?= e((string)($prp['project_title'] ?? 'Kurumsal Dijital Proje Teklifi')) ?></h1>
        </div>
        <div class="zprp-doc-box">
          <div class="zprp-doc-row"><span>Teklif Referansı</span><strong><?= e((string)($prp['proposal_no'] ?? strtoupper($prpCode))) ?></strong></div>
          <div class="zprp-doc-row"><span>Düzenlenme Tarihi</span><strong><?= e(substr((string)($prp['created_at'] ?? date('Y-m-d')), 0, 10)) ?></strong></div>
          <?php if ($validUntil !== ''): ?>
          <div class="zprp-doc-row"><span>Geçerlilik Tarihi</span><strong><?= e($validUntil) ?></strong></div>
          <?php endif; ?>
          <?php if (!empty($prp['delivery_time'])): ?>
          <div class="zprp-doc-row"><span>Tahmini Teslim</span><strong><?= e((string)$prp['delivery_time']) ?></strong></div>
          <?php endif; ?>
        </div>
      </header>

      <div class="zprp-parties-grid">
        <div class="zprp-party-card">
          <span class="zprp-party-label">TEKLİF SUNULAN KURUM / YETKİLİ</span>
          <h2><?= e((string)($prp['client_company'] !== '' ? $prp['client_company'] : $prp['client_name'])) ?></h2>
          <?php if ($prp['client_company'] !== '' && $prp['client_name'] !== ''): ?>
          <p><strong>İlgili Yetkili:</strong> <?= e((string)$prp['client_name']) ?></p>
          <?php endif; ?>
          <?php if (!empty($prp['client_email'])): ?>
          <p><strong>E-Posta:</strong> <?= e((string)$prp['client_email']) ?></p>
          <?php endif; ?>
          <?php if (!empty($prp['client_phone'])): ?>
          <p><strong>Telefon:</strong> <?= e((string)$prp['client_phone']) ?></p>
          <?php endif; ?>
        </div>

        <div class="zprp-party-card zprp-party-studio">
          <div class="zprp-party-studio-text">
            <span class="zprp-party-label">HAZIRLAYAN DİJİTAL STÜDYO</span>
            <h2><?= e($siteLegal) ?></h2>
            <?php if ($sitePhone !== ''): ?>
            <p><strong>Telefon:</strong> <?= e($sitePhone) ?></p>
            <?php endif; ?>
            <p><strong>E-Posta:</strong> <?= e($siteEmail) ?></p>
            <p><strong>Dijital Erişim:</strong> <?= e(parse_url($proposalUrl, PHP_URL_HOST) ?: 'zeynart.com') ?>/teklif/<?= e($prpCode) ?>/</p>
          </div>
          <div class="zprp-qr-mini" title="Mobil cihazdan görüntülemek için okutun">
            <?= $qrSvg ?>
          </div>
        </div>
      </div>

      <?php if (!empty($prp['intro_note'])): ?>
      <div class="zprp-intro-box">
        <h3>Yönetici Özeti &amp; Proje Yaklaşımı</h3>
        <p><?= nl2br_e((string)$prp['intro_note']) ?></p>
      </div>
      <?php endif; ?>

      <div class="zprp-items-section">
        <h3>Hizmet Kapsamı &amp; Yatırım Kalemleri</h3>
        <div class="zprp-table-wrap">
          <table class="zprp-table">
            <thead>
              <tr>
                <th style="width:52%">Hizmet / Modül Kapsamı</th>
                <th style="width:12%;text-align:center">Miktar</th>
                <th style="width:18%;text-align:right">Birim Bedel</th>
                <th style="width:18%;text-align:right">Toplam</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ((array)($prp['items'] ?? []) as $idx => $item): ?>
              <tr>
                <td>
                  <strong class="zprp-item-title"><?= ($idx + 1) ?>. <?= e((string)($item['title'] ?? '')) ?></strong>
                  <?php if (!empty($item['desc'])): ?>
                  <span class="zprp-item-desc"><?= nl2br_e((string)$item['desc']) ?></span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center"><?= e((string)($item['qty'] ?? 1)) ?></td>
                <td style="text-align:right"><?= e($fmtMoney((float)($item['unit_price'] ?? 0))) ?></td>
                <td style="text-align:right"><strong><?= e($fmtMoney((float)($item['line_total'] ?? 0))) ?></strong></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="zprp-totals-wrap">
          <div class="zprp-terms-col">
            <?php if (!empty($prp['payment_terms'])): ?>
            <div class="zprp-note-card">
              <h4>Ödeme Planı &amp; Takvim</h4>
              <p><?= nl2br_e((string)$prp['payment_terms']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($prp['extra_notes'])): ?>
            <div class="zprp-note-card">
              <h4>Dahil Olan Hizmetler &amp; Notlar</h4>
              <p><?= nl2br_e((string)$prp['extra_notes']) ?></p>
            </div>
            <?php endif; ?>
          </div>

          <div class="zprp-summary-card">
            <div class="zprp-sum-row">
              <span>Ara Toplam</span>
              <strong><?= e($fmtMoney((float)($prp['subtotal'] ?? 0))) ?></strong>
            </div>
            <?php if (((float)($prp['discount'] ?? 0)) > 0): ?>
            <div class="zprp-sum-row is-discount">
              <span>Kurumsal İskonto</span>
              <strong>-<?= e($fmtMoney((float)$prp['discount'])) ?></strong>
            </div>
            <?php endif; ?>
            <div class="zprp-sum-row">
              <span>KDV (%<?= (int)($prp['vat_rate'] ?? 20) ?>)</span>
              <strong><?= e($fmtMoney((float)($prp['vat_amount'] ?? 0))) ?></strong>
            </div>
            <div class="zprp-sum-row is-grand">
              <span>Genel Toplam</span>
              <strong><?= e($fmtMoney((float)($prp['grand_total'] ?? 0))) ?></strong>
            </div>
          </div>
        </div>
      </div>

      <section class="zprp-approve-box" id="zprpApproveBox">
        <?php if ($isApproved): ?>
        <div class="zprp-approved-banner" id="zprpApprovedBanner">
          <div class="zprp-approved-ico">✓</div>
          <div>
            <h3>Bu Teklif Dijital Olarak Onaylanmıştır</h3>
            <p>
              <strong>Onaylayan Yetkili:</strong> <?= e((string)($prp['approved_by'] ?? $prp['client_name'])) ?> ·
              <strong>Onay Zamanı:</strong> <?= e((string)($prp['approved_at'] ?? '')) ?>
            </p>
            <?php if (!empty($prp['approval_note'])): ?>
            <p class="zprp-approved-note"><strong>Not:</strong> <?= e((string)$prp['approval_note']) ?></p>
            <?php endif; ?>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px" class="no-print">
              <a class="zprp-btn zprp-btn-primary" href="<?= e(url('/sozlesme/' . $prpCode . '/')) ?>">📜 Resmi Hizmet Sözleşmesi (A4 PDF)</a>
              <a class="zprp-btn zprp-btn-ghost" href="<?= e(url((string)($prp['automation_chain']['tracker_url'] ?? ('/proje-takip/' . $prpCode . '/')))) ?>">🔐 Canlı Proje Takip Portalı →</a>
            </div>
          </div>
        </div>
        <?php elseif ($isExpired): ?>
        <div class="zprp-expired-banner">
          <h3>Teklif Geçerlilik Süresi Tamamlanmıştır</h3>
          <p>Güncel kapsam ve revize teklif bağlantısı için bizimle iletişime geçebilirsiniz.</p>
        </div>
        <?php else: ?>
        <div class="zprp-approve-form-wrap no-print" id="zprpApproveFormWrap">
          <div class="zprp-approve-copy">
            <span class="zprp-party-label">HIZLI DİJİTAL ONAY</span>
            <h3>Teklifi Onaylayın &amp; Projeyi Başlatalım</h3>
            <p>Aşağıdaki alanı doldurup onayladığınızda proje ekibimize anında bildirim iletilir ve başlangıç planlaması başlatılır.</p>
          </div>
          <form class="zprp-approve-form" id="zprpApproveForm" action="<?= e(url('/api/teklif-onay')) ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
            <input type="hidden" name="code" value="<?= e($prpCode) ?>"/>
            <div class="zprp-form-row">
              <label for="zprpSignerName">Onaylayan Ad Soyad / Ünvan *</label>
              <input type="text" id="zprpSignerName" name="signer_name" required maxlength="120" value="<?= e((string)($prp['client_name'] ?? '')) ?>" placeholder="Örn: Ahmet Yılmaz — Genel Müdür"/>
            </div>
            <div class="zprp-form-row">
              <label for="zprpApprovalNote">Eklemek İstediğiniz Not (Opsiyonel)</label>
              <textarea id="zprpApprovalNote" name="approval_note" rows="2" maxlength="800" placeholder="Fatura bilgileri, tercih edilen başlangıç tarihi veya özel notunuz..."></textarea>
            </div>
            <div class="zprp-form-actions">
              <button type="submit" class="zprp-btn zprp-btn-primary zprp-btn-lg" id="zprpSubmitBtn">
                <span>✓ Teklifi Dijital Olarak Onaylıyorum</span>
              </button>
            </div>
            <div class="zprp-feedback" id="zprpFeedback" hidden></div>
          </form>
        </div>
        <?php endif; ?>
      </section>
    </article>
  </div>
</section>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
