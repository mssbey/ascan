<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/agency_growth_suite.php';

/** @var array $activeTracker */
$trk = $activeTracker;
$code = (string)($trk['code'] ?? '');
$pct = max(0, min(100, (int)($trk['progress_pct'] ?? 0)));
$milestones = is_array($trk['milestones'] ?? null) ? $trk['milestones'] : [];
$feedbacks = is_array($trk['feedbacks'] ?? null) ? $trk['feedbacks'] : [];
$status = (string)($trk['status'] ?? 'active');

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('X-LiteSpeed-Cache-Control: no-cache');
}

$statusLabel = match ($status) {
    'completed' => 'TAMAMLANDI & YAYINDA 🚀',
    'awaiting_approval' => 'MÜŞTERİ ONAYI BEKLİYOR',
    'paused' => 'BEKLEMEDE',
    default => 'AKTİF GELİŞTİRME SÜRECİNDE',
};

$pageMeta = [
    'title' => ($trk['project_title'] ?? 'Proje Takip Portalı') . ' — Durum & Onay | ZEYNART',
    'description' => 'Zeynart Dijital Stüdyo kurumsal müşteri proje durum, aşama takip ve onay portalı.',
    'robots' => 'noindex, nofollow',
    'canonical_path' => '/proje-takip/' . $code . '/',
];

$token = csrf_token();
$waRaw = preg_replace('/\D+/', '', (string)($store['site']['phone_href'] ?? '+908503050533')) ?? '';
$waAgencyHref = $waRaw !== ''
    ? ('https://wa.me/' . $waRaw . '?text=' . rawurlencode('Merhaba Zeynart Ekibi, "' . ($trk['project_title'] ?? '') . '" (' . strtoupper($code) . ') projemiz hakkında yazıyorum.'))
    : '';

require INCLUDES_DIR . '/site_header.php';
?>
<section class="tracker-portal-section">
  <div class="container">
    <div class="tracker-hero-card">
      <div class="tracker-hero-top">
        <div>
          <div class="tracker-pills-row">
            <span class="tracker-status-pill status-<?= e($status) ?>">● <?= e($statusLabel) ?></span>
            <span class="tracker-code-pill">KOD: <?= e(strtoupper($code)) ?></span>
          </div>
          <h1><?= e($trk['project_title'] ?? 'Proje Takip Portalı') ?></h1>
          <p class="tracker-client-line">
            <strong><?= e($trk['client_company'] ?: ($trk['client_name'] ?: 'Kurumsal Müşteri')) ?></strong>
            <?php if (!empty($trk['package_scope'])): ?>
              <span>· <?= e($trk['package_scope']) ?></span>
            <?php endif; ?>
          </p>
        </div>
        <div class="tracker-pct-box">
          <small>GENEL İLERLEME</small>
          <strong>%<?= $pct ?></strong>
          <span>Sorumlu: <?= e($trk['manager_name'] ?? 'Zeynart Ekibi') ?></span>
        </div>
      </div>

      <div class="tracker-progress-bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="tracker-progress-fill" style="width:<?= $pct ?>%"></div>
      </div>

      <div class="tracker-meta-strip">
        <div><small>BAŞLANGIÇ TARİHİ</small><strong><?= e(format_tr_date((string)($trk['start_date'] ?? date('Y-m-d')))) ?></strong></div>
        <div><small>HEDEF TESLİM</small><strong><?= e(format_tr_date((string)($trk['estimated_delivery'] ?? date('Y-m-d')))) ?></strong></div>
        <div><small>CANLI / DEMO ÖNİZLEME</small>
          <?php if (!empty($trk['demo_url'])): ?>
            <a href="<?= e($trk['demo_url']) ?>" target="_blank" rel="noopener" class="tracker-demo-link">Önizlemeyi Aç ↗</a>
          <?php elseif (!empty($trk['live_url'])): ?>
            <a href="<?= e($trk['live_url']) ?>" target="_blank" rel="noopener" class="tracker-demo-link">Canlı Siteyi Aç ↗</a>
          <?php else: ?>
            <strong>Hazırlanıyor</strong>
          <?php endif; ?>
        </div>
        <div class="tracker-hero-actions">
          <?php if (!empty($trk['proposal_code'])): ?>
            <a href="<?= e(url('/teklif/' . $trk['proposal_code'] . '/')) ?>" class="button">📄 Proje Teklifi</a>
          <?php endif; ?>
          <?php if ($waAgencyHref !== ''): ?>
            <a href="<?= e($waAgencyHref) ?>" target="_blank" rel="noopener" class="button button-primary">💬 Proje Ekibine Yaz</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($trk['latest_update_note'])): ?>
      <div class="tracker-update-callout">
        <strong>📣 Son Ekip Güncellemesi:</strong>
        <p><?= nl2br_e((string)$trk['latest_update_note']) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <div class="tracker-grid">
      <div class="tracker-milestones-card">
        <p class="eyebrow"><span></span>5 ADIMLI ŞEFFAF SÜREÇ HARİTASI</p>
        <h2>Proje Aşamaları</h2>
        <div class="tracker-step-list">
          <?php foreach ($milestones as $ms):
              $mState = (string)($ms['status'] ?? 'upcoming');
          ?>
          <article class="tracker-step-item is-<?= e($mState) ?>">
            <div class="tracker-step-marker">
              <?php if ($mState === 'completed'): ?>
                ✓
              <?php else: ?>
                <?= str_pad((string)($ms['step'] ?? 1), 2, '0', STR_PAD_LEFT) ?>
              <?php endif; ?>
            </div>
            <div class="tracker-step-body">
              <div class="tracker-step-head">
                <h3><?= e($ms['title'] ?? '') ?></h3>
                <span class="tracker-step-tag">
                  <?= $mState === 'completed' ? 'Tamamlandı' : ($mState === 'current' ? 'Şu An Bu Aşamada' : 'Sıradaki Adım') ?>
                </span>
              </div>
              <p><?= e($ms['desc'] ?? '') ?></p>
              <?php if (!empty($ms['completed_at'])): ?>
                <small class="tracker-step-date">Tamamlanma: <?= e($ms['completed_at']) ?></small>
              <?php endif; ?>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>

      <aside class="tracker-feedback-card">
        <p class="eyebrow"><span></span>MÜŞTERİ ONAY &amp; GERİ BİLDİRİM MERKEZİ</p>
        <h2>Aşama Onayı veya Not İletin</h2>
        <p class="muted" style="margin-bottom:18px">Mevcut aşamayı inceledikten sonra tek tıkla onay verebilir veya revize notlarınızı doğrudan proje ekibimize iletebilirsiniz.</p>

        <form id="clientTrackerFeedbackForm" class="contact-form" method="post" action="<?= e(url('/api/proje-takip-onay')) ?>" toolname="submit_project_milestone_feedback" tooldescription="Aktif kurumsal proje aşaması için müşteri onayı veya revize talebi gönderir.">
          <input type="hidden" name="csrf_token" value="<?= e($token) ?>"/>
          <input type="hidden" name="code" value="<?= e($code) ?>"/>
          <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0" aria-hidden="true"/>

          <div class="tracker-choice-row">
            <label class="tracker-choice-pill active">
              <input type="radio" name="feedback_type" value="approve" checked/>
              <span>✅ Aşamayı Onaylıyorum</span>
            </label>
            <label class="tracker-choice-pill">
              <input type="radio" name="feedback_type" value="revision"/>
              <span>✏️ Revize / Ek Notum Var</span>
            </label>
          </div>

          <label class="form-field">
            Adınız Soyadınız *
            <input type="text" name="author" required minlength="2" maxlength="100" value="<?= e($trk['client_name'] ?? '') ?>" placeholder="Adınız ve soyadınız"/>
          </label>

          <label class="form-field">
            Değerlendirme veya Revize Notunuz
            <textarea name="note" rows="4" maxlength="1500" placeholder="Onay mesajınızı veya yapılmasını istediğiniz düzenlemeleri buraya yazabilirsiniz..."></textarea>
          </label>

          <button type="submit" class="button button-primary" style="width:100%;justify-content:center">Onay / Notu Ekibe Gönder →</button>
          <div id="trackerFeedbackAlert" class="tracker-feedback-alert" style="display:none"></div>
        </form>

        <?php if ($feedbacks !== []): ?>
        <div class="tracker-history-box">
          <h3>Önceki Kayıtlar (<?= count($feedbacks) ?>)</h3>
          <?php foreach (array_slice($feedbacks, 0, 5) as $fb): ?>
          <div class="tracker-history-item">
            <div style="display:flex;justify-content:space-between;gap:8px;align-items:center">
              <strong><?= ($fb['type'] ?? '') === 'approve' ? '✅ Onay' : '✏️ Revize Notu' ?> · <?= e($fb['author'] ?? '') ?></strong>
              <small><?= e($fb['created_at'] ?? '') ?></small>
            </div>
            <?php if (!empty($fb['milestone_title'])): ?>
              <small style="color:var(--accent-bright);display:block;margin-top:2px"><?= e($fb['milestone_title']) ?></small>
            <?php endif; ?>
            <?php if (!empty($fb['note'])): ?>
              <p><?= nl2br_e((string)$fb['note']) ?></p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
