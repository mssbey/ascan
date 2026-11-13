<?php
declare(strict_types=1);

if (!headers_sent()) {
    header('X-LiteSpeed-Cache-Control: no-cache');
}

require_once __DIR__ . '/../includes/enterprise_cms_suite.php';

/** @var array $activeProposal */
$prp = $activeProposal;
$prpCode = (string)($prp['code'] ?? '');
$cleanCodeTail = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $prpCode) ?: 'ZYN4', -4));
$contractNo = trim((string)($prp['contract_no'] ?? ''));
if ($contractNo === '') {
    $contractNo = 'SZL-' . date('Y') . '-' . $cleanCodeTail;
}
$contractDate = trim((string)($prp['contract_date'] ?? ''));
if ($contractDate === '') {
    $contractDate = substr((string)($prp['approved_at'] ?? ($prp['created_at'] ?? date('Y-m-d'))), 0, 10);
}

$contractUrl = rtrim(canonical_url('/sozlesme/' . $prpCode . '/', $store, 'tr'), '/') . '/';
$proposalPath = '/teklif/' . $prpCode . '/';
$trackerPath = (string)($prp['automation_chain']['tracker_url'] ?? ('/proje-takip/' . $prpCode . '/'));
$qrSvg = zeynart_qr_render_svg($contractUrl, '#051319', '#ffffff', 3, 168);

$sym = (string)($prp['currency_symbol'] ?? '₺');
$fmtMoney = static fn(float $n): string => number_format($n, 2, ',', '.') . ' ' . $sym;

$siteLegal = (string)($store['site']['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri');
$siteAddress = (string)($store['contact']['address'] ?? ($store['site']['address'] ?? 'İstanbul, Türkiye'));
$sitePhone = (string)($store['contact']['phone_display'] ?? ($store['site']['phone'] ?? ''));
$siteEmail = (string)($store['contact']['email'] ?? ($store['site']['email'] ?? 'info@zeynart.com'));

$clientDisplay = trim((string)($prp['client_company'] !== '' ? $prp['client_company'] : $prp['client_name']));
if ($clientDisplay === '') {
    $clientDisplay = 'Hizmet Alan Kurumsal Müşteri';
}

$isApproved = ($prp['status'] ?? '') === 'approved';
$annualRenewal = (float)($prp['annual_renewal_amount'] ?? 0);
if ($annualRenewal <= 0) {
    $grand = (float)($prp['grand_total'] ?? 0);
    $annualRenewal = $grand > 0 ? round(max(3500.0, $grand * 0.20), 2) : 6500.0;
}
$targetDomain = trim((string)($prp['target_domain'] ?? ($prp['automation_chain']['uptime_domain'] ?? '')));

$pageMeta = [
    'title' => $contractNo . ' — Dijital Hizmet & Yazılım Sözleşmesi | ' . $siteLegal,
    'description' => $clientDisplay . ' ile ' . $siteLegal . ' arasında düzenlenen resmi dijital proje ve hizmet sözleşmesi.',
    'robots' => 'noindex, nofollow',
    'canonical_path' => '/sozlesme/' . $prpCode . '/',
];

require INCLUDES_DIR . '/site_header.php';
?>
<section class="zprp-stage zcnt-stage">
  <div class="zprp-ambient no-print" aria-hidden="true"></div>
  <div class="container zprp-wrap">

    <div class="zprp-topbar no-print">
      <div class="zprp-topbar-meta">
        <span class="zprp-status-pill <?= $isApproved ? 'is-approved' : 'is-pending' ?>">
          <?= $isApproved ? '✓ Dijital Onaylı Sözleşme' : 'Onay Bekleyen Sözleşme Taslağı' ?>
        </span>
        <span class="zprp-code-tag">Sözleşme No: <strong><?= e($contractNo) ?></strong></span>
      </div>
      <div class="zprp-topbar-actions">
        <a class="zprp-btn zprp-btn-ghost" href="<?= e(url($proposalPath)) ?>">
          <span>← Teklif Dosyası</span>
        </a>
        <?php if ($isApproved || !empty($prp['automation_chain']['active'])): ?>
        <a class="zprp-btn zprp-btn-ghost" href="<?= e(url($trackerPath)) ?>">
          <span>🔐 Proje Takip Portalı</span>
        </a>
        <?php endif; ?>
        <button type="button" class="zprp-btn zprp-btn-primary" id="zcntPrintBtn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          <span>A4 Sözleşme PDF Yazdır / İndir</span>
        </button>
      </div>
    </div>

    <article class="zprp-sheet zcnt-sheet" id="zcntSheet">
      <header class="zprp-head">
        <div class="zprp-brand-col">
          <img class="zprp-logo" src="<?= e(url((string)($store['site']['logo'] ?? '/images/zeynart-logo.avif'))) ?>" alt="<?= e($siteLegal) ?>" width="150" height="58" decoding="async"/>
          <p class="zprp-eyebrow">RESMİ KURUMSAL DİJİTAL HİZMET &amp; YAZILIM SÖZLEŞMESİ</p>
          <h1><?= e((string)($prp['project_title'] ?? 'Kurumsal Web Yazılım & Dijital Dönüşüm Sözleşmesi')) ?></h1>
        </div>
        <div class="zprp-doc-box">
          <div class="zprp-doc-row"><span>Sözleşme No</span><strong><?= e($contractNo) ?></strong></div>
          <div class="zprp-doc-row"><span>Bağlı Teklif No</span><strong><?= e((string)($prp['proposal_no'] ?? strtoupper($prpCode))) ?></strong></div>
          <div class="zprp-doc-row"><span>Sözleşme Tarihi</span><strong><?= e($contractDate) ?></strong></div>
          <div class="zprp-doc-row"><span>Tahmini Teslim</span><strong><?= e((string)($prp['delivery_time'] ?? '15 – 20 İş Günü')) ?></strong></div>
          <?php if ($targetDomain !== ''): ?>
          <div class="zprp-doc-row"><span>Hedef Alan Adı</span><strong><?= e($targetDomain) ?></strong></div>
          <?php endif; ?>
        </div>
      </header>

      <div class="zcnt-clauses">
        <section class="zcnt-clause">
          <h2>MADDE 1 — TARAFLAR VE TANIMLAR</h2>
          <p>İşbu Kurumsal Dijital Hizmet ve Yazılım Sözleşmesi ("Sözleşme"), aşağıda bilgileri yer alan taraflar arasında elektronik/yazılı mutabakat ile akdedilmiştir:</p>
          <div class="zprp-parties-grid zcnt-parties-grid">
            <div class="zprp-party-card">
              <span class="zprp-party-label">1.1. HİZMET VEREN (YÜKLENİCİ AJANS)</span>
              <h3><?= e($siteLegal) ?></h3>
              <p><strong>Adres:</strong> <?= e($siteAddress) ?></p>
              <?php if ($sitePhone !== ''): ?>
              <p><strong>Telefon:</strong> <?= e($sitePhone) ?></p>
              <?php endif; ?>
              <p><strong>E-Posta:</strong> <?= e($siteEmail) ?></p>
            </div>

            <div class="zprp-party-card">
              <span class="zprp-party-label">1.2. HİZMET ALAN (İŞVEREN / MÜŞTERİ)</span>
              <h3><?= e($clientDisplay) ?></h3>
              <?php if (!empty($prp['client_name'])): ?>
              <p><strong>Yetkili Temsilci:</strong> <?= e((string)$prp['client_name']) ?></p>
              <?php endif; ?>
              <?php if (!empty($prp['client_tax_office']) || !empty($prp['client_tax_no'])): ?>
              <p><strong>Vergi Dairesi / No:</strong> <?= e(trim((string)($prp['client_tax_office'] ?? '') . ' ' . (string)($prp['client_tax_no'] ?? ''))) ?></p>
              <?php endif; ?>
              <?php if (!empty($prp['client_address'])): ?>
              <p><strong>Adres:</strong> <?= e((string)$prp['client_address']) ?></p>
              <?php endif; ?>
              <?php if (!empty($prp['client_phone'])): ?>
              <p><strong>Telefon:</strong> <?= e((string)$prp['client_phone']) ?></p>
              <?php endif; ?>
              <?php if (!empty($prp['client_email'])): ?>
              <p><strong>E-Posta:</strong> <?= e((string)$prp['client_email']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <section class="zcnt-clause">
          <h2>MADDE 2 — SÖZLEŞMENİN KONUSU VE HİZMET KAPSAMI</h2>
          <p>İşbu sözleşmenin konusu; Hizmet Alan'ın kurumsal dijital ihtiyaçları doğrultusunda <strong><?= e((string)($prp['proposal_no'] ?? strtoupper($prpCode))) ?></strong> referanslı teklifte detaylandırılan tasarım, yazılım, içerik yönetim stüdyosu, SEO/GEO altyapısı ve teknik entegrasyon hizmetlerinin aşağıda belirtilen kapsam ve bedellerle üretilerek teslim edilmesidir.</p>
          <div class="zprp-table-wrap">
            <table class="zprp-table">
              <thead>
                <tr>
                  <th style="width:54%">Hizmet / Teslimat Kalemi</th>
                  <th style="width:10%;text-align:center">Adet</th>
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
        </section>

        <section class="zcnt-clause">
          <h2>MADDE 3 — MALİ HÜKÜMLER VE ÖDEME TAKVİMİ</h2>
          <div class="zprp-totals-wrap">
            <div class="zprp-terms-col">
              <div class="zprp-note-card">
                <h4>Ödeme Planı &amp; Faturalandırma</h4>
                <p><?= nl2br_e((string)($prp['payment_terms'] ?? '%50 Sözleşme Onayı & İş Başlangıcı · %50 Test & Canlı Yayın Tesliminde')) ?></p>
              </div>
              <?php if (!empty($prp['extra_notes'])): ?>
              <div class="zprp-note-card">
                <h4>Özel Hükümler &amp; Dahil Hizmetler</h4>
                <p><?= nl2br_e((string)$prp['extra_notes']) ?></p>
              </div>
              <?php endif; ?>
            </div>
            <div class="zprp-summary-card">
              <div class="zprp-sum-row"><span>Ara Toplam</span><strong><?= e($fmtMoney((float)($prp['subtotal'] ?? 0))) ?></strong></div>
              <?php if (((float)($prp['discount'] ?? 0)) > 0): ?>
              <div class="zprp-sum-row is-discount"><span>Kurumsal İskonto</span><strong>-<?= e($fmtMoney((float)$prp['discount'])) ?></strong></div>
              <?php endif; ?>
              <div class="zprp-sum-row"><span>KDV (%<?= (int)($prp['vat_rate'] ?? 20) ?>)</span><strong><?= e($fmtMoney((float)($prp['vat_amount'] ?? 0))) ?></strong></div>
              <div class="zprp-sum-row is-grand"><span>Sözleşme Toplamı</span><strong><?= e($fmtMoney((float)($prp['grand_total'] ?? 0))) ?></strong></div>
            </div>
          </div>
        </section>

        <section class="zcnt-clause">
          <h2>MADDE 4 — TESLİM SÜRECİ, PROJE PORTALI VE REVİZYON HAKLARI</h2>
          <p><strong>4.1. Tahmini Teslim Süresi:</strong> Görsel, metin ve kurumsal materyallerin Hizmet Alan tarafından eksiksiz iletilmesini takiben tahmini proje süresi <strong><?= e((string)($prp['delivery_time'] ?? '15 – 20 İş Günü')) ?></strong> olarak planlanmıştır.</p>
          <p><strong>4.2. Şeffaf Proje Takip Portalı:</strong> Hizmet Alan, üretim aşamalarını, demo bağlantılarını ve aşama onaylarını <code><?= e($trackerPath) ?></code> adresi üzerinden anlık takip edebilir ve revizyon notlarını dijital olarak iletebilir.</p>
          <p><strong>4.3. Revizyon ve Kabul:</strong> Her aşama sonunda Hizmet Alan'ın kurumsal kimlik ve sözleşme kapsamı dahilindeki revizyon talepleri eksiksiz uygulanır. Kapsam dışı ek modül talepleri tarafların yazılı mutabakatıyla ek iş emri olarak planlanır.</p>
        </section>

        <section class="zcnt-clause">
          <h2>MADDE 5 — GİZLİLİK, KVKK VE FİKRİ MÜLKİYET HAKLARI</h2>
          <p><strong>5.1. Gizlilik ve KVKK:</strong> Taraflar, işbu sözleşme kapsamında öğrendikleri ticari sırları, erişim bilgilerini ve 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamındaki verileri üçüncü kişilerle paylaşmamayı ve çerezsiz/onaylı veri güvenliği standartlarına uymayı taahhüt eder.</p>
          <p><strong>5.2. Kullanım Hakları:</strong> Sözleşme bedelinin tamamının ödenmesiyle birlikte, Hizmet Alan için özel üretilen kurumsal tasarım, içerik ve alan adı üzerindeki süresiz kullanım hakları Hizmet Alan'a devredilir.</p>
        </section>

        <section class="zcnt-clause">
          <h2>MADDE 6 — UPTIME İZLEME, TEKNİK DESTEK VE YILLIK YENİLEME</h2>
          <p><strong>6.1. İlk Yıl Garanti &amp; İzleme:</strong> Teslim tarihinden itibaren 1 (bir) yıl boyunca yazılım kaynaklı teknik hata giderimi ve 7/24 SSL/Uptime süreklilik izlemesi ücretsiz olarak sunulur.</p>
          <p><strong>6.2. Sonraki Yıllar Hizmet Sürekliliği:</strong> İlk yılın sonunda sunucu barındırma (LiteSpeed Kurumsal Hosting), SSL güvenlik sertifikası, yedekleme ve teknik bakım yenileme bedeli tahmini <strong><?= e($fmtMoney($annualRenewal)) ?> + KDV / Yıl</strong> olarak yenileme takvimine kaydedilir ve süre bitiminden 30 gün önce Hizmet Alan'a bildirilir.</p>
        </section>

        <section class="zcnt-clause">
          <h2>MADDE 7 — DELİL SÖZLEŞMESİ, YETKİLİ MAHKEME VE YÜRÜRLÜK</h2>
          <p>İşbu 7 (yedi) maddeden oluşan sözleşmenin uygulanmasından doğabilecek uyuşmazlıklarda tarafların ticari defterleri, e-posta/WhatsApp yazışmaları ve dijital onay zaman damgası kayıtları HMK m.193 uyarınca kesin delil niteliğindedir. İstanbul Merkez Mahkemeleri ve İcra Daireleri yetkilidir.</p>
        </section>
      </div>

      <footer class="zcnt-signatures">
        <div class="zcnt-sig-card">
          <span class="zprp-party-label">HİZMET VEREN (YÜKLENİCİ AJANS)</span>
          <strong><?= e($siteLegal) ?></strong>
          <p>Kurumsal Dijital Stüdyo &amp; Yazılım Ekibi</p>
          <div class="zcnt-sig-box">
            <span class="zcnt-digital-stamp">✦ KURUMSAL DİJİTAL ONAYLI</span>
            <small>Tarih: <?= e($contractDate) ?></small>
          </div>
        </div>

        <div class="zcnt-sig-card">
          <span class="zprp-party-label">HİZMET ALAN (İŞVEREN / MÜŞTERİ)</span>
          <strong><?= e($clientDisplay) ?></strong>
          <p>Yetkili: <?= e((string)($prp['approved_by'] ?? ($prp['client_name'] ?: '.................................'))) ?></p>
          <div class="zcnt-sig-box <?= $isApproved ? 'is-approved' : '' ?>">
            <?php if ($isApproved): ?>
            <span class="zcnt-digital-stamp is-emerald">✓ ELEKTRONİK OLARAK ONAYLANDI</span>
            <small>Zaman: <?= e((string)($prp['approved_at'] ?? $contractDate)) ?><?= !empty($prp['approved_ip']) ? (' · IP: ' . e((string)$prp['approved_ip'])) : '' ?></small>
            <?php else: ?>
            <span class="zcnt-sig-placeholder">Kaşe / Islak İmza veya Online Teklif Onayı</span>
            <small class="no-print"><a href="<?= e(url($proposalPath . '#zprpApproveBox')) ?>">Teklif ekranından dijital onay verebilirsiniz →</a></small>
            <?php endif; ?>
          </div>
        </div>

        <div class="zcnt-qr-verify">
          <?= $qrSvg ?>
          <small>Doğrulama Kodu<br/><strong><?= e($contractNo) ?></strong></small>
        </div>
      </footer>
    </article>

  </div>
</section>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
