<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/studio_suite.php';
require_once __DIR__ . '/../includes/studio_pro_pack.php';
require_once __DIR__ . '/../includes/agency_powerhouse.php';
require_once __DIR__ . '/../includes/enterprise_cms_suite.php';
require_once __DIR__ . '/../includes/agency_growth_suite.php';
require_once __DIR__ . '/../includes/sector_extensions.php';
require_once __DIR__ . '/../includes/ota_hub_engine.php';

$store = load_store();
$activeAdminSlug = zeynart_get_admin_slug($store);
if (strcasecmp($activeAdminSlug, ADMIN_DIR_NAME) !== 0 && empty($_SERVER['ZEYNART_ROUTED_ADMIN'])) {
    if (!empty($_COOKIE['zeynart_sid'])) {
        start_secure_session();
    }
    send_security_headers(false);
    if (!headers_sent()) {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('X-LiteSpeed-Cache-Control: no-cache');
    }
    http_response_code(404);
    $activeLang = 'tr';
    zeynart_set_lang('tr');
    $currentRoutePath = '/404';
    require PAGES_DIR . '/404.php';
    exit;
}

start_secure_session();
send_security_headers(true);

$csrf = csrf_token();
$loggedIn = is_admin_logged_in();
if ($loggedIn && ($_GET['logout'] ?? '') === '1' && verify_csrf($_GET['csrf'] ?? null)) {
    zeynart_audit_log('logout', 'Yönetici üst çubuğundan güvenli çıkış yapıldı.', (string)($_SESSION['zeynart_admin_user'] ?? 'admin'));
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
    }
    @session_destroy();
    header('Location: ' . url('/'));
    exit;
}
$siteName = (string)($store['site']['name'] ?? 'Dijital Stüdyo');
$siteSubTagline = trim((string)($store['site']['tagline'] ?? ''));
if ($siteSubTagline === '') {
    $siteSubTagline = 'Kurumsal Dijital Yönetim Merkezi';
}
$themeBrand = zeynart_get_theme_brand($store);
$rawBadgeText = (string)($themeBrand['studio_badge_text'] ?? 'DİJİTAL STÜDYO');
$studioBadgeText = ($rawBadgeText === '' || in_array($rawBadgeText, ['DİJİTAL STÜDYO', 'DİJİTAL STÜDYO v3', 'DİJİTAL STÜDYO v4.0', 'DİJİTAL STÜDYO v5.0', 'DİJİTAL STÜDYO v5.1'], true)) ? 'DİJİTAL STÜDYO v5.2' : $rawBadgeText;
$adminBrandCss = zeynart_compile_admin_theme_css($store);
$logoUrl = url((string)($store['site']['logo'] ?? '/images/zeynart-logo.avif'));
$faviconUrl = url((string)($store['site']['favicon'] ?? '/icon.png'));
$currentUserProfile = $loggedIn ? zeynart_current_admin_profile($store) : null;
$adminDisplay = (string)($currentUserProfile['display_name'] ?? $store['admin']['display_name'] ?? 'Zeynart Yönetici');
$adminUser = (string)($currentUserProfile['username'] ?? $store['admin']['username'] ?? 'admin');
$adminRole = (string)($currentUserProfile['role'] ?? 'super_admin');
$isSuperAdmin = ($adminRole === 'super_admin');
$trMonthsInit = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];
$trDaysShortInit = [0 => 'Paz', 1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt'];
$initTimeStr = date('H:i:s');
$initDateStr = date('j') . ' ' . ($trMonthsInit[(int)date('n')] ?? '') . ' ' . date('Y') . ' ' . ($trDaysShortInit[(int)date('w')] ?? '');
$apiUrl = zeynart_admin_url('api.php', $store);
$useMinCss = is_file(__DIR__ . '/admin.min.css') && (!is_file(__DIR__ . '/admin.css') || (int)@filemtime(__DIR__ . '/admin.min.css') >= (int)@filemtime(__DIR__ . '/admin.css'));
$useMinJs = is_file(__DIR__ . '/admin.min.js') && (!is_file(__DIR__ . '/admin.js') || (int)@filemtime(__DIR__ . '/admin.min.js') >= (int)@filemtime(__DIR__ . '/admin.js'));
$cssFileRel = $useMinCss ? 'admin.min.css' : 'admin.css';
$jsFileRel = $useMinJs ? 'admin.min.js' : 'admin.js';
$fastAssetVer = static fn(string $absFile): string => is_file($absFile)
    ? substr(dechex(abs(((int)@filemtime($absFile) * 131) ^ (int)@filesize($absFile))), 0, 10)
    : '4';
$cssHash = $fastAssetVer(__DIR__ . '/' . $cssFileRel);
$jsHash = $fastAssetVer(__DIR__ . '/' . $jsFileRel);
$cssUrl = zeynart_admin_url($cssFileRel, $store) . '?v=' . $cssHash;
$jsUrl = zeynart_admin_url($jsFileRel, $store) . '?v=' . $jsHash;
$siteCssUrl = asset_url('/assets/css/site.css');

$chunkManifest = [];
$chunkNames = ['blog', 'media_design', 'pages', 'seo_growth', 'agency_crm', 'ops_system'];
foreach ($chunkNames as $cName) {
    $rawChunkPath = __DIR__ . '/chunks/' . $cName . '.js';
    $minChunkPath = __DIR__ . '/chunks/' . $cName . '.min.js';
    $useMinChunk = is_file($minChunkPath) && (!is_file($rawChunkPath) || (int)@filemtime($minChunkPath) >= (int)@filemtime($rawChunkPath));
    $cRel = 'chunks/' . $cName . ($useMinChunk ? '.min.js' : '.js');
    $cFull = __DIR__ . '/' . $cRel;
    if (is_file($cFull)) {
        $cHash = $fastAssetVer($cFull);
        $chunkManifest[$cName] = zeynart_admin_url($cRel, $store) . '?v=' . $cHash;
    }
}
$sectorAssets = zeynart_collect_sector_admin_assets($store);
$sectorPublicMeta = zeynart_get_sector_extensions_public_meta();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <meta name="robots" content="noindex, nofollow"/>
  <title><?= e($siteName) ?> — <?= e($studioBadgeText) ?></title>
  <link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png"/>
  <link rel="stylesheet" href="<?= e($cssUrl) ?>"/>
<?php foreach ($sectorAssets['css'] as $secCssUrl): ?>
  <link rel="stylesheet" href="<?= e($secCssUrl) ?>"/>
<?php endforeach; ?>
  <style id="zsWhiteLabelVars"><?= $adminBrandCss ?></style>
</head>
<?php if (!$loggedIn): ?>
<body class="zs-login-body">
  <div class="zs-login-grid-bg" aria-hidden="true"></div>
  <main class="zs-login-card zs-v3-login-shell">
    <div class="zs-login-brand">
      <div style="display:flex;align-items:center;gap:12px;min-width:0">
        <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>"/>
        <div style="min-width:0">
          <strong style="display:block;font-size:15.5px;color:#fff;letter-spacing:.05em"><?= e($siteName) ?></strong>
          <span style="display:block;font-size:10.5px;color:var(--accent-bright);letter-spacing:.12em;text-transform:uppercase"><?= e($siteSubTagline) ?></span>
        </div>
      </div>
      <span class="zs-badge-pill">STÜDYO v5.2</span>
    </div>

    <div class="zs-eyebrow"><?= e(mb_strtoupper($siteName, 'UTF-8')) ?> — <?= e($studioBadgeText) ?> · YÖNETİM &amp; OTA MERKEZİ</div>
    <h1>Yönetim Merkezine <em>Giriş</em></h1>
    <p class="zs-sub">Merkezi OTA otomatik güncelleme filosu, çoklu sektör eklenti mimarisi, PDO MySQL veritabanı motoru ve yapay zeka SEO (GEO) araçlarını tek merkezden yönetin.</p>

    <div class="zs-v3-trust-strip" aria-hidden="true">
      <div class="zs-v3-trust-item">
        <strong>Zero-Cookie &amp; KVKK</strong>
        <span>%100 LSCache Uyumu</span>
      </div>
      <div class="zs-v3-trust-item">
        <strong>Merkezi OTA &amp; MySQL v5.2</strong>
        <span>RSA-2048 İmzalı Filo</span>
      </div>
      <div class="zs-v3-trust-item">
        <strong>2FA &amp; Zaman Makinesi</strong>
        <span>5 Sürüm Korumalı</span>
      </div>
    </div>

    <form id="zsLoginForm" autocomplete="on">
      <div id="zsLoginCredentialsStep">
        <div class="zs-field">
          <label for="zsUser">Kullanıcı Adı</label>
          <input id="zsUser" name="username" class="zs-input" type="text" required autofocus placeholder="admin" autocomplete="username"/>
        </div>
        <div class="zs-field">
          <label for="zsPass">Parola</label>
          <div class="zs-input-group">
            <input id="zsPass" name="password" class="zs-input" type="password" required placeholder="••••••••••" autocomplete="current-password"/>
            <button type="button" class="zs-btn zs-btn-sm" id="zsTogglePass" aria-label="Parolayı göster">Göster</button>
          </div>
        </div>
      </div>

      <div id="zsLogin2faStep" style="display:none">
        <div style="margin-bottom:12px;padding:11px 13px;border-radius:11px;background:rgba(20,184,166,.12);border:1px solid rgba(94,234,212,.3);font-size:12.5px;color:#ccfbf1" id="zs2faHint">
          🔐 İki aşamalı doğrulama aktif. Authenticator uygulamanızdaki 6 haneli kodu veya kurtarma kodunuzu girin.
        </div>
        <div class="zs-field">
          <label for="zs2faCode">6 Haneli Doğrulama veya Kurtarma Kodu</label>
          <input id="zs2faCode" name="otp_code" class="zs-input" type="text" inputmode="text" maxlength="16" placeholder="123456 veya ABCD-EF12" autocomplete="one-time-code" style="letter-spacing:.16em;font-weight:700;text-align:center;font-size:16px"/>
        </div>
        <div style="display:flex;justify-content:space-between;gap:8px;margin-top:6px">
          <button type="button" class="zs-btn zs-btn-sm" id="zs2faSendEmailBtn" style="display:none">📧 E-Posta ile Kod Gönder</button>
          <button type="button" class="zs-btn zs-btn-sm" id="zs2faBackBtn">← Geri Dön</button>
        </div>
      </div>

      <div id="zsLoginError" style="display:none;margin:12px 0;padding:10px 13px;border-radius:10px;background:rgba(239,68,68,.14);border:1px solid rgba(239,68,68,.35);color:#fecaca;font-size:12.5px"></div>
      <button type="submit" id="zsLoginBtn" class="zs-btn zs-btn-primary" style="width:100%;min-height:48px;padding:13px 20px;margin-top:10px;font-size:14px">
        Stüdyoya Güvenli Giriş Yap →
      </button>
    </form>

    <div class="zs-login-foot">
      <a href="<?= e(url('/')) ?>">← Ana Siteye Dön</a>
      <a class="zs-admin-credit" href="https://zeynart.com/" target="_blank" rel="noopener">Powered &amp; Designed By Zeynart®</a>
    </div>
  </main>

  <script>
    (function () {
      const form = document.getElementById('zsLoginForm');
      const credStep = document.getElementById('zsLoginCredentialsStep');
      const twoFaStep = document.getElementById('zsLogin2faStep');
      const twoFaInput = document.getElementById('zs2faCode');
      const twoFaHint = document.getElementById('zs2faHint');
      const sendMailBtn = document.getElementById('zs2faSendEmailBtn');
      const backBtn = document.getElementById('zs2faBackBtn');
      const errBox = document.getElementById('zsLoginError');
      const btn = document.getElementById('zsLoginBtn');
      const passInput = document.getElementById('zsPass');
      const toggleBtn = document.getElementById('zsTogglePass');
      let awaiting2fa = false;

      toggleBtn.addEventListener('click', function () {
        const isPass = passInput.type === 'password';
        passInput.type = isPass ? 'text' : 'password';
        toggleBtn.textContent = isPass ? 'Gizle' : 'Göster';
      });

      backBtn.addEventListener('click', function () {
        awaiting2fa = false;
        twoFaStep.style.display = 'none';
        credStep.style.display = 'block';
        errBox.style.display = 'none';
        btn.textContent = 'Stüdyoya Güvenli Giriş Yap →';
      });

      sendMailBtn.addEventListener('click', async function () {
        sendMailBtn.disabled = true;
        errBox.style.display = 'none';
        try {
          const p = new URLSearchParams();
          p.set('csrf_token', <?= json_encode($csrf) ?>);
          const r = await fetch(<?= json_encode($apiUrl) ?> + '?action=send_2fa_email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': <?= json_encode($csrf) ?> },
            body: p.toString()
          });
          const d = await r.json();
          if (d && d.ok) {
            twoFaHint.textContent = d.message || '📧 6 haneli tek kullanımlık doğrulama kodu e-posta adresinize gönderildi.';
          } else {
            errBox.textContent = (d && d.error) || 'E-posta gönderilemedi.';
            errBox.style.display = 'block';
          }
        } catch (_) {
          errBox.textContent = 'Sunucu bağlantısı kurulamadı.';
          errBox.style.display = 'block';
        } finally {
          sendMailBtn.disabled = false;
        }
      });

      form.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        errBox.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Doğrulanıyor...';
        try {
          if (awaiting2fa) {
            const otpParams = new URLSearchParams();
            otpParams.set('csrf_token', <?= json_encode($csrf) ?>);
            otpParams.set('code', twoFaInput.value.trim());
            const r2 = await fetch(<?= json_encode($apiUrl) ?> + '?action=verify_2fa', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': <?= json_encode($csrf) ?> },
              body: otpParams.toString()
            });
            const d2 = await r2.json();
            if (!d2.ok) {
              errBox.textContent = d2.error || 'Doğrulama kodu geçersiz.';
              errBox.style.display = 'block';
              btn.disabled = false;
              btn.textContent = 'Kodu Doğrula ve Giriş Yap →';
              return;
            }
            window.location.reload();
            return;
          }

          const loginParams = new URLSearchParams();
          loginParams.set('csrf_token', <?= json_encode($csrf) ?>);
          loginParams.set('username', document.getElementById('zsUser').value.trim());
          loginParams.set('password', passInput.value);
          const res = await fetch(<?= json_encode($apiUrl) ?> + '?action=login', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
              'X-CSRF-Token': <?= json_encode($csrf) ?>
            },
            body: loginParams.toString()
          });
          const data = await res.json();
          if (!data.ok) {
            errBox.textContent = data.error || 'Giriş yapılamadı.';
            errBox.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Stüdyoya Güvenli Giriş Yap →';
            return;
          }
          if (data.requires_2fa) {
            awaiting2fa = true;
            credStep.style.display = 'none';
            twoFaStep.style.display = 'block';
            if (data.message) twoFaHint.textContent = data.message;
            sendMailBtn.style.display = data.email_fallback ? 'inline-flex' : 'none';
            btn.disabled = false;
            btn.textContent = 'Kodu Doğrula ve Giriş Yap →';
            setTimeout(function () { twoFaInput.focus(); }, 40);
            return;
          }
          window.location.reload();
        } catch (err) {
          errBox.textContent = 'Sunucu bağlantısı kurulamadı.';
          errBox.style.display = 'block';
          btn.disabled = false;
          btn.textContent = awaiting2fa ? 'Kodu Doğrula ve Giriş Yap →' : 'Stüdyoya Güvenli Giriş Yap →';
        }
      });
    })();
  </script>
</body>
<?php else: ?>
<body>
  <div class="zs-ambient-canvas" aria-hidden="true"></div>
  <div class="zs-shell">
    <aside class="zs-sidebar" id="zsSidebar">
      <div class="zs-sidebar-head">
        <div class="zs-brand-lockup">
          <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>"/>
          <div class="zs-brand-meta">
            <strong id="zsBrandSideTitle"><?= e($siteName) ?></strong>
            <span id="zsBrandSideBadge"><?= e($studioBadgeText) ?></span>
          </div>
        </div>
      </div>

      <div class="zs-sidebar-search" id="zsSidebarSearchWrap">
        <div class="zs-sidebar-search-box">
          <svg class="zs-sidebar-search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
          <input type="search" id="zsNavFilterInput" name="zs_sidebar_menu_filter_q" class="zs-sidebar-search-input" placeholder="Menüde veya ayarlarda ara..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" data-form-type="other" readonly onfocus="this.removeAttribute('readonly')" onmousedown="this.removeAttribute('readonly')" ontouchstart="this.removeAttribute('readonly')" aria-label="Sol menüde ara"/>
          <button type="button" id="zsNavFilterClear" class="zs-sidebar-search-clear" title="Aramayı Temizle" style="display:none">✕</button>
        </div>
      </div>

      <nav class="zs-sidebar-nav" id="zsNav">
        <div class="zs-nav-group-label" data-group-header="overview">Genel Bakış &amp; Müşteri</div>

        <div class="zs-nav-item-wrap is-active" data-nav-wrap="dashboard" data-nav-group="overview">
          <button type="button" class="zs-nav-btn active" data-view="dashboard" data-keywords="ozet komuta baslangic ana ekran pano kpi hizli erisim">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg>
            <span>Gösterge Paneli</span>
          </button>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="analytics" data-nav-group="overview">
          <button type="button" class="zs-nav-btn" data-view="analytics" data-keywords="ziyaretci istatistik canli trafik sayac hit anlik rapor pdf a4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><rect x="7" y="11" width="3" height="6" rx="1"/><rect x="12" y="6" width="3" height="11" rx="1"/><rect x="17" y="9" width="3" height="8" rx="1"/></svg>
            <span>Canlı Trafik &amp; Raporlar</span>
            <span class="zs-nav-chev" data-nav-toggle="analytics" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="analytics">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="analytics:live" data-keywords="anlik ziyaretci 30 gunluk trafik kaynak cihaz">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              <span>Anlık &amp; 30 Günlük Trafik</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="analytics:report" data-keywords="aylik musteri seo performans raporu a4 pdf yazdir">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6M9 11h6"/></svg>
              <span>Aylık PDF Performans Raporu</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="messages" data-nav-group="overview">
          <button type="button" class="zs-nav-btn" data-view="messages" data-keywords="proje talepleri crm kanban teklif ozel musteri teklifi proje takip portali dijital kartvizit qr iban vcf">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
            <span>Talepler, Teklif &amp; Portal</span>
            <span class="zs-nav-chev" data-nav-toggle="messages" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="messages">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="messages:kanban" data-keywords="satis hunisi crm kanban surukle birak asama">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="5" height="16" rx="1"/><rect x="10" y="4" width="5" height="11" rx="1"/><rect x="17" y="4" width="4" height="14" rx="1"/></svg>
              <span>Satış Hunisi (CRM Kanban)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="messages:list" data-keywords="gelen form talepleri mesajlar telefon whatsapp">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
              <span>Gelen Form Talepleri</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="proposals" data-keywords="ozel musteri teklifleri online teklif linki okundu onay">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              <span>Özel Müşteri Teklifleri</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="proposals:new" data-keywords="yeni online teklif olustur fiyat kalem">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
              <span>Yeni Online Teklif</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="client_portal" data-keywords="proje takip portali musteri asama onay ilerleme">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
              <span>Proje Takip Portalı</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="vcards" data-keywords="dijital kartvizit qr kod vcf rehber iban fatura">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/><path d="M14 10h4M14 14h4"/></svg>
              <span>Dijital Kartvizit &amp; QR</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-group-label" data-group-header="content">Sayfa, İçerik &amp; Medya</div>

        <div class="zs-nav-item-wrap" data-nav-wrap="pages" data-nav-group="content">
          <button type="button" class="zs-nav-btn" data-view="pages" data-keywords="sayfalar ana sayfa bolumler kurumsal hakkimizda hizmetler projeler iletisim yasal kvkk cerez cop kutusu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
            <span>Sayfa &amp; Vitrin Yönetimi</span>
            <span class="zs-nav-chev" data-nav-toggle="pages" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="pages">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:list" data-keywords="tum sayfalar listesi kalici baglanti slug">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg>
              <span>Tüm Sayfalar Listesi</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:edit_home" data-keywords="ana sayfayi duzenle hero slayt vitrin">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              <span>Ana Sayfayı Düzenle</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="home_layout" data-keywords="ana sayfa bolum sirasi gizle goster akis">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
              <span>Ana Sayfa Bölüm Sırası</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:new_page" data-keywords="yeni ozel sayfa ekle olustur">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
              <span>Yeni Sayfa Ekle</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:edit_about" data-keywords="hakkimizda referanslar musteri logolari">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 10h6M9 14h6"/></svg>
              <span>Hakkımızda &amp; Referanslar</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:edit_services" data-keywords="hizmetler alt sayfalar hosting paketler">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
              <span>Hizmetler &amp; Paketler</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:edit_projects" data-keywords="projeler vaka calismalari portfolyo">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
              <span>Projeler &amp; Vaka Vitrini</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:edit_contact" data-keywords="iletisim sayfasi adres telefon harita form">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <span>İletişim &amp; Harita Sayfası</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:legal" data-keywords="yasal sayfalar kvkk cerez politikasi gizlilik">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <span>Yasal Sayfalar &amp; KVKK</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="pages:trash" data-keywords="sayfa cop kutusu silinen sayfalar geri yukle">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              <span>Sayfa Çöp Kutusu</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="blog" data-nav-group="content">
          <button type="button" class="zs-nav-btn" data-view="blog" data-keywords="blog makale yazi kategori yapay zeka yazar google news otopilot sesli dinle yazar yorum">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            <span>Blog &amp; Haber Stüdyosu</span>
            <span class="zs-nav-chev" data-nav-toggle="blog" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="blog">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="blog:list" data-keywords="tum blog makaleleri yazilar arsiv">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              <span>Tüm Blog Makaleleri</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="blog:new_post" data-keywords="yeni blog yazisi yapay zeka yazar makale uret">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 3v3m0 12v3M3 12h3m12 0h3m-2.6-6.4-2.1 2.1M7.7 16.3l-2.1 2.1m0-12.8 2.1 2.1m8.6 8.6 2.1 2.1"/></svg>
              <span>Yeni Yazı &amp; AI Yazar</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="blog_categories" data-keywords="blog kategorileri alt kategori sira seo">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
              <span>Blog Kategorileri</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="blog:news_bot" data-keywords="google news otopilot teknoloji haber kuyrugu">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"/><path d="M10 6h8M10 10h8M10 14h4"/></svg>
              <span>Google News Otopilotu</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="blog:seo_links" data-keywords="sesli dinle podcast otomatik seo ic linkleme">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              <span>Sesli Dinle &amp; Oto İç Link</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="blog:authors" data-keywords="uzman yazar profilleri person schema eeat">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <span>Yazar Profilleri (E-E-A-T)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="blog:comments" data-keywords="makale yorumlari soru cevap moderasyon">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <span>Yorum Moderasyonu</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="media" data-nav-group="content">
          <button type="button" class="zs-nav-btn" data-view="media" data-keywords="medya kutuphanesi gorsel resim yukle uploads webp marka damgasi kanvas">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            <span>Medya &amp; Uploads</span>
            <span class="zs-nav-chev" data-nav-toggle="media" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="media">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="media:library" data-keywords="tum gorseller uploads yil ay gun klasor lightbox kanvas">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
              <span>Tüm Görseller (/uploads/)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="media:upload" data-keywords="yeni gorsel yukle surukle birak webp marka damgasi">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <span>Yeni Görsel Yükle (WebP)</span>
            </button>
          </div>
        </div>
<?= zeynart_render_sector_sidebar_nav_html($adminRole) ?>
        <div class="zs-nav-group-label" data-group-header="growth">Google, Sosyal &amp; Tasarım</div>

        <div class="zs-nav-item-wrap" data-nav-wrap="seo" data-nav-group="growth">
          <button type="button" class="zs-nav-btn" data-view="seo" data-keywords="google seo merkezi ga4 analytics search console yorumlar harita fonts 301 404 cerez kvkk geo gemini indexnow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8"/><path d="M12 3a14.5 14.5 0 0 0 0 18 14.5 14.5 0 0 0 0-18z"/></svg>
            <span>Google &amp; SEO Merkezi</span>
            <span class="zs-nav-chev" data-nav-toggle="seo" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="seo">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:general" data-keywords="google merkezi ga4 search console tag manager harita google fonts">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8"/></svg>
              <span>Google Merkezi (GA4 · GSC)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:google_reviews" data-keywords="google yorumlari place id yildiz puan yorum">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <span>Google Yorumları</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:pages" data-keywords="sayfa bazli meta baslik aciklama opengraph">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
              <span>Sayfa Meta Başlıkları</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:health" data-keywords="seo doktoru alt etiketi og kart onar">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
              <span>SEO Doktoru &amp; OG Kart</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:analyzer" data-keywords="icerik seo denetcisi odak kelime indexnow">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <span>İçerik Analizi &amp; IndexNow</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:clusters" data-keywords="konu kumesi topic cluster ic link haritasi yetim sayfa pillar oto orucu">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="3"/><circle cx="5" cy="6" r="2"/><circle cx="19" cy="6" r="2"/><circle cx="5" cy="18" r="2"/><circle cx="19" cy="18" r="2"/><path d="M9.5 10.2 6.6 7.5M14.5 10.2l2.9-2.7M9.5 13.8l-2.9 2.7M14.5 13.8l2.9 2.7"/></svg>
              <span>Konu Kümesi &amp; İç Link</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:ai" data-keywords="yapay zeka seo geo llms chatgpt gemini">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2l2.4 7.2L22 12l-7.6 2.8L12 22l-2.4-7.2L2 12l7.6-2.8L12 2z"/></svg>
              <span>Yapay Zeka SEO (GEO)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:redirects" data-keywords="301 yonlendirme sitemap robots">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
              <span>301 Yönlendirme &amp; Sitemap</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:cookies" data-keywords="cerez banner kvkk tumunu reddet kabul et zero cookie">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/></svg>
              <span>Çerez Banner &amp; KVKK</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="seo:solutions" data-keywords="sektorel bolgesel programatik seo cozum sayfalari">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>Sektörel SEO Üreteci</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="social_media" data-nav-group="growth">
          <button type="button" class="zs-nav-btn" data-view="social_media" data-keywords="sosyal medya instagram facebook linkedin x twitter youtube tiktok behance pinterest telegram paylasim ikon sameas opengraph story post">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            <span>Sosyal Medya Merkezi</span>
            <span class="zs-nav-chev" data-nav-toggle="social_media" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="social_media">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="social_media:accounts" data-keywords="sosyal medya hesaplari instagram facebook linkedin x youtube tiktok ikon header footer iletisim sameas">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
              <span>Sosyal Hesaplar &amp; İkonlar</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="social_media:sharing" data-keywords="paylasim cubugu opengraph og kart whatsapp linkedin x onizleme">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              <span>Paylaşım Barı &amp; OpenGraph</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="social_media:studio" data-keywords="instagram post story gorsel uretici 1080x1080 1080x1920 sosyal medya metin">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
              <span>IG Post &amp; Story Üretici</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="social_media:biolink" data-keywords="instagram bio link dijital kartvizit qr kod vcf">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/><path d="M14 10h4M14 14h4"/></svg>
              <span>Bio-Link &amp; QR Kartvizit</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="growth_tools" data-nav-group="growth">
          <button type="button" class="zs-nav-btn" data-view="growth_tools" data-keywords="buyume donusum whatsapp hizli arama teklif sihirbazi akilli popup kesif gorusmesi site hizi analiz">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <span>Büyüme &amp; Teklif Araçları</span>
            <span class="zs-nav-chev" data-nav-toggle="growth_tools" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="growth_tools">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="growth_tools:quick_contact" data-keywords="whatsapp hizli arama kapsul mesai saatleri">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              <span>WhatsApp &amp; Hızlı Arama Barı</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="growth_tools:quote_wizard" data-keywords="interaktif teklif maliyet sihirbazi 4 adim">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="11" x2="10" y2="11"/><line x1="14" y1="11" x2="16" y2="11"/><line x1="8" y1="16" x2="10" y2="16"/><line x1="14" y1="16" x2="16" y2="16"/></svg>
              <span>İnteraktif Teklif Sihirbazı</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="growth_tools:popup" data-keywords="akilli popup exit intent cikis niyeti beni arayin">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="14" x2="16" y2="14"/></svg>
              <span>Akıllı Pop-up (Exit-Intent)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="growth_tools:booking" data-keywords="15 dakika online kesif gorusmesi randevu takvim">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <span>15 Dk Keşif Görüşmesi</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="growth_tools:site_audit" data-keywords="ucretsiz site hizi seo on analiz araci lead">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
              <span>Site Hızı &amp; SEO Analiz Aracı</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="menus" data-nav-group="growth">
          <button type="button" class="zs-nav-btn" data-view="menus" data-keywords="tasarim menu header footer tipografi google fonts boyut tema renk duyuru bakim modu 503">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="13.5" cy="6.5" r="1.2"/><circle cx="17.5" cy="10.5" r="1.2"/><circle cx="8.5" cy="7.5" r="1.2"/><circle cx="6.5" cy="12.5" r="1.2"/><path d="M12 2a10 10 0 1 0 0 20 2.5 2.5 0 0 0 2.5-2.5c0-.64-.24-1.22-.64-1.67a1.8 1.8 0 0 1 1.39-2.93H17a5 5 0 0 0 5-5c0-4.42-4.48-7.9-10-7.9Z"/></svg>
            <span>Tasarım, Menü &amp; Tema</span>
            <span class="zs-nav-chev" data-nav-toggle="menus" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="menus">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="menus:header" data-keywords="ust menu header navigasyon hizalama">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
              <span>Üst Menü (Header)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="menus:footer" data-keywords="alt menu footer sutunlar linkler">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
              <span>Alt Menü &amp; Sütunlar (Footer)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="menus:typography" data-keywords="google fonts yazi tipi boyut px kalinlik">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
              <span>Google Fonts &amp; Yazı Boyutu</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="menus:cta" data-keywords="alt teklif cagri bandi global cta">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
              <span>Alt Teklif Bandı (CTA)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="appearance:theme" data-super-only="1"<?= $isSuperAdmin ? '' : ' data-role-hidden="1" style="display:none"' ?> data-keywords="white label renk paleti tema sihirbazi buton">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="13.5" cy="6.5" r="1"/><circle cx="17.5" cy="10.5" r="1"/><circle cx="8.5" cy="7.5" r="1"/><circle cx="6.5" cy="12.5" r="1"/><path d="M12 2a10 10 0 1 0 0 20 2.5 2.5 0 0 0 2.5-2.5c0-.64-.24-1.22-.64-1.67a1.8 1.8 0 0 1 1.39-2.93H17a5 5 0 0 0 5-5c0-4.42-4.48-7.9-10-7.9Z"/></svg>
              <span>Kurumsal Renk &amp; Tema</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="appearance:promo" data-super-only="1"<?= $isSuperAdmin ? '' : ' data-role-hidden="1" style="display:none"' ?> data-keywords="ust duyuru kampanya cubugu geri sayim">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <span>Üst Duyuru / Kampanya Barı</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="appearance:maintenance" data-super-only="1"<?= $isSuperAdmin ? '' : ' data-role-hidden="1" style="display:none"' ?> data-keywords="bakim modu 503 yenileniyoruz cok yakinda">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              <span>Bakım Modu (503 Ekranı)</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-group-label" data-group-header="system">Altyapı &amp; Sistem</div>

        <div class="zs-nav-item-wrap" data-nav-wrap="ota_hub" data-nav-group="system" data-super-only="1"<?= $isSuperAdmin ? '' : ' data-role-hidden="1" style="display:none"' ?>>
          <button type="button" class="zs-nav-btn" data-view="ota_hub" data-keywords="ota otomatik guncelleme filo hub istemci baglanti kodu zyn5 rsa imza uzaktan guncelle rollback surum paketle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 14.9A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.24"/><path d="M12 12v9"/><path d="m8 17 4-5 4 5"/></svg>
            <span>OTA Güncelleme &amp; Filo</span>
            <span class="zs-nav-chev" data-nav-toggle="ota_hub" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="ota_hub">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="ota_hub:fleet" data-super-only="1" data-keywords="bagli musteri siteleri filo toplu guncelle ping onbellek baglanti kodu zyn5">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><path d="M10.5 7.5 6.5 16.5M13.5 7.5l4 9"/></svg>
              <span>Filo &amp; Bağlı Siteler</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="ota_hub:releases" data-super-only="1" data-keywords="yeni surum paketle rsa 2048 imzala zip dagitim hub">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
              <span>Sürüm Paketle &amp; Yayınla</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="ota_hub:client" data-super-only="1" data-keywords="istemci otomatik guncelleme hub baglan zyn5 rollback geri don">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
              <span>İstemci Oto-Güncelleme</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="ota_hub:logs" data-super-only="1" data-keywords="ota dagitim kayitlari kriptografik imza log denetim">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
              <span>Dağıtım &amp; İmza Kayıtları</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="uptime_tower" data-nav-group="system">
          <button type="button" class="zs-nav-btn" data-view="uptime_tower" data-keywords="uptime ssl kulesi musteri siteleri hosting domain yillik bakim yenileme radari">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            <span>Uptime, SSL &amp; Yenilemeler</span>
            <span class="zs-nav-chev" data-nav-toggle="uptime_tower" title="Alt menüyü aç/kapat">›</span>
          </button>
          <div class="zs-nav-sub-list" data-sub-for="uptime_tower">
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="uptime_tower:all" data-keywords="uptime ssl kontrol kulesi 300 musteri sitesi">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              <span>Uptime &amp; SSL Kontrol Kulesi</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="renewals" data-keywords="hosting domain ssl bakim yenileme radari tahsilat">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <span>Hosting &amp; Yenileme Radarı</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="renewals:new" data-keywords="yeni yenileme kaydi ekle domain hosting">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
              <span>Yeni Yenileme Kaydı</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-item-wrap" data-nav-wrap="settings" data-nav-group="system">
          <button type="button" class="zs-nav-btn" data-view="settings" data-keywords="mysql veritabani mariadb sqlite pdo sql goc sistem guvenlik litespeed lscache onbellek hit miss kullanilmayan dosyalar temizleyici kimlik logo kullanicilar roller 2fa coklu dil smtp zaman makinesi yedekleme">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.6.77 1.02 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span id="zsNavSettingsLabel"><?= $isSuperAdmin ? 'Sistem, Hız &amp; Güvenlik' : 'Hesabım &amp; Parola' ?></span>
            <span class="zs-nav-chev" id="zsNavSettingsChev" data-nav-toggle="settings" title="Alt menüyü aç/kapat"<?= $isSuperAdmin ? '' : ' style="display:none"' ?>>›</span>
          </button>
          <div class="zs-nav-sub-list" id="zsNavSettingsSubList" data-sub-for="settings"<?= $isSuperAdmin ? '' : ' style="display:none"' ?>>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:database" data-super-only="1" data-keywords="mysql veritabani mariadb sqlite pdo sql goc tasima json sil plesk cpanel sql yedek">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
              <span>MySQL Veritabanı &amp; Göç</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="performance" data-super-only="1" data-keywords="litespeed lscache onbellek kapat ac hit miss sayfa haritasi hiz">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
              <span>LiteSpeed &amp; Önbellek</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="performance:unused_files" data-super-only="1" data-keywords="kullanilmayan dosyalar baglantisiz gereksiz eski surum dosya temizleyici v4">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
              <span>Kullanılmayan Dosyalar</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:identity" data-super-only="1" data-keywords="marka kimligi logo favicon iletisim preloader">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
              <span>Marka Kimliği &amp; Logo</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:security" data-super-only="1" data-keywords="20/20 guvenlik kalkani 2fa ip kilit giris kayitlari audit log">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
              <span>20/20 Güvenlik &amp; 2FA</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:users" data-super-only="1" data-keywords="kullanicilar roller editor super admin parola sifre">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
              <span>Kullanıcılar, Roller &amp; Şifre</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:i18n" data-super-only="1" data-keywords="coklu dil otomatik toplu ceviri tr en de ru">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
              <span>Çoklu Dil &amp; Toplu Çeviri</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:smtp" data-super-only="1" data-keywords="smtp eposta mail sunucu ayarlar canli test">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/></svg>
              <span>SMTP E-Posta Ayarları</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:revisions" data-super-only="1" data-keywords="zaman makinesi 5 surum gecmisi geri yukle">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span>Zaman Makinesi (5 Sürüm)</span>
            </button>
            <button type="button" class="zs-nav-sub-btn" data-nav-jump="settings:backups" data-super-only="1" data-keywords="tam veri yedekleme kasasi zip indir geri yukle">
              <svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <span>Tam Yedekleme Kasası (.zip)</span>
            </button>
          </div>
        </div>

        <div class="zs-nav-empty-hint" id="zsNavEmptyHint" style="display:none">
          <span>Sol menüde eşleşme bulunamadı.</span>
          <button type="button" class="zs-btn zs-btn-sm" id="zsNavEmptyOpenCmdBtn">Tüm Stüdyoda Ara (Ctrl+K) →</button>
        </div>
      </nav>

      <div class="zs-sidebar-foot">
        <div class="zs-user-chip">
          <div class="zs-avatar" id="zsAdminAvatarChar"><?= e(mb_strtoupper(mb_substr($adminDisplay, 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
          <div class="zs-user-info">
            <strong id="zsAdminDisplayName"><?= e($adminDisplay) ?></strong>
            <span id="zsAdminUserMeta">@<?= e($adminUser) ?> · <?= $isSuperAdmin ? 'Süper Admin' : 'Editör' ?></span>
          </div>
        </div>
        <button type="button" class="zs-btn zs-btn-sm" id="zsLogoutBtn" title="Güvenli Çıkış">Çıkış</button>
      </div>
    </aside>

    <div class="zs-main">
      <header class="zs-topbar">
        <div class="zs-topbar-left">
          <button type="button" class="zs-burger" id="zsBurgerBtn" aria-label="Menüyü Aç">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <h1 class="zs-view-title" id="zsViewTitle">Gösterge Paneli</h1>
        </div>

        <button type="button" class="zs-cmd-trigger" id="zsCmdBtn">
          <span>Komut &amp; Gezgin...</span>
          <kbd>Ctrl + K</kbd>
        </button>

        <div class="zs-topbar-clock" id="zsTopbarClockWrap" title="Yerel Saat ve Tarih">
          <span class="zs-clock-pulse" aria-hidden="true"></span>
          <strong class="zs-clock-time" id="zsTopbarTime"><?= e($initTimeStr) ?></strong>
          <span class="zs-clock-sep" aria-hidden="true">·</span>
          <span class="zs-clock-date" id="zsTopbarDate"><?= e($initDateStr) ?></span>
        </div>

        <div class="zs-topbar-actions">
          <div class="zs-notif-wrap" id="zsNotifWrap">
            <button type="button" class="zs-btn zs-btn-sm zs-notif-btn" id="zsNotifBtn" title="Bildirim &amp; Uyarı Merkezi">
              🔔 <span class="zs-notif-dot" id="zsNotifCount" style="display:none">0</span>
            </button>
            <div class="zs-notif-dropdown" id="zsNotifDropdown" style="display:none"></div>
          </div>
          <div class="zs-quick-create-wrap" id="zsQuickCreateWrap">
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="zsQuickCreateBtn" aria-expanded="false">+ Hızlı Ekle ▾</button>
            <div class="zs-quick-create-menu" id="zsQuickCreateMenu" style="display:none">
<?= zeynart_render_sector_quick_create_html($adminRole) ?>              <button type="button" class="zs-quick-create-item" id="zsQuickNewPostBtn">
                <span class="zs-qc-ico">✍️</span>
                <span><strong>Yeni Blog Yazısı</strong><small>TR editör + yapay zeka &amp; 4 dil çeviri</small></span>
              </button>
              <button type="button" class="zs-quick-create-item" id="zsQuickNewPageBtn">
                <span class="zs-qc-ico">📄</span>
                <span><strong>Yeni Özel Sayfa</strong><small>Görsel bloklarla yeni sayfa oluştur</small></span>
              </button>
              <button type="button" class="zs-quick-create-item" id="zsQuickNewProposalBtn">
                <span class="zs-qc-ico">💼</span>
                <span><strong>Yeni Online Teklif</strong><small>/teklif/ linki ve okundu takibi</small></span>
              </button>
              <button type="button" class="zs-quick-create-item" id="zsQuickUploadMediaBtn">
                <span class="zs-qc-ico">🖼️</span>
                <span><strong>Görsel Yükle (WebP)</strong><small>/uploads/Yıl/Ay/Gün arşivine aktar</small></span>
              </button>
              <button type="button" class="zs-quick-create-item" id="zsQuickNewPortalBtn">
                <span class="zs-qc-ico">🔐</span>
                <span><strong>Proje Takip Linki</strong><small>Müşteriye özel /proje-takip/ portalı</small></span>
              </button>
            </div>
          </div>
          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="zsQuickEditHomeBtn">✎ Ana Sayfayı Düzenle</button>
          <?php if ($isSuperAdmin): ?>
          <button type="button" class="zs-btn zs-btn-sm zs-hide-compact" id="zsTopbarPurgeBtn" title="LiteSpeed &amp; OPcache Önbelleğini Anında Temizle">⚡ Önbellek</button>
          <?php endif; ?>
          <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener" class="zs-btn zs-btn-sm">Siteyi Gör ↗</a>
        </div>
      </header>
      <script>
        (function () {
          var now = new Date();
          var h = now.getHours();
          var months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
          var daysS = ['Paz','Pzt','Sal','Çar','Per','Cum','Cmt'];
          var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
          var tEl = document.getElementById('zsTopbarTime');
          var dEl = document.getElementById('zsTopbarDate');
          if (tEl) tEl.textContent = pad(h) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
          if (dEl) dEl.textContent = now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear() + ' ' + daysS[now.getDay()];
        })();
      </script>

      <main class="zs-content adm-content" id="zsAppRoot">
        <div class="zs-card" style="text-align:center;padding:60px 20px">
          <p class="zs-badge-pill">STÜDYO v5.2 YÜKLENİYOR</p>
          <h2 style="margin:12px 0 6px"><?= e($siteName) ?> — <?= e($studioBadgeText) ?> Hazırlanıyor...</h2>
          <p class="zs-card-sub">Merkezi OTA güncelleme filosu, sektör eklenti kancaları, PDO MySQL motoru ve modüler çekirdek kabuğu bağlanıyor.</p>
        </div>
      </main>

      <footer class="zs-admin-footer">
        <div class="zs-admin-footer-left" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <span>© <?= date('Y') ?> <strong><?= e($siteName) ?></strong> — <?= e($studioBadgeText) ?></span>
          <span class="zs-v4-telemetry-chip" id="zsV4SpeedBadge" title="Merkezi OTA Hub, Çoklu Sektör Kancası, PDO MySQL Motoru &amp; Modüler Code-Splitting Çekirdeği">⚡ v5.2 Core · 0ms</span>
        </div>
        <div class="zs-admin-footer-right">
          <a class="zs-admin-credit" href="https://zeynart.com/" target="_blank" rel="noopener">Powered &amp; Designed By Zeynart®</a>
        </div>
      </footer>
    </div>
  </div>

  <!-- Admin-Only Social Media Sharing & Link Preview Studio Modal -->
  <div class="zs-modal-backdrop" id="zsSocialModal">
    <div class="zs-modal zs-social-modal">
      <div class="zs-modal-head">
        <div>
          <strong style="font-size:16px" id="zsSocialModalTitle">📣 Sosyal Medya Paylaşım &amp; Kart Stüdyosu</strong>
          <div style="font-size:12px;color:var(--text-muted)">WhatsApp, LinkedIn, Facebook, Instagram, X (Twitter) ve Telegram için canlı link önizlemesi &amp; tek tıkla paylaşım merkezi (Yalnızca Admin)</div>
        </div>
        <button type="button" class="zs-btn zs-btn-sm" id="zsSocialModalClose">Kapat ✕</button>
      </div>
      <div class="zs-modal-body" id="zsSocialModalBody"></div>
    </div>
  </div>

  <!-- Global Media Picker Modal -->
  <div class="zs-modal-backdrop" id="zsMediaModal">
    <div class="zs-modal zs-media-picker-modal">
      <div class="zs-modal-head">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="zs-picker-head-orb" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
          </div>
          <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <strong style="font-size:16px">Medya Kütüphanesi &amp; Görsel Yükleyici</strong>
              <span class="zs-badge-pill" id="zsMediaPickerModeBadge" style="font-size:10px;padding:2px 8px">CANLI WEBP MOTORU</span>
            </div>
            <div style="font-size:12px;color:var(--text-muted)">Çoklu görsel sürükleyip bırakın, <strong>Ctrl+V</strong> ile panodan yapıştırın veya mevcut arşivden seçin.</div>
          </div>
        </div>
        <button type="button" class="zs-btn zs-btn-sm" id="zsMediaModalClose">Kapat ✕</button>
      </div>
      <div class="zs-modal-body" id="zsMediaModalBody"></div>
    </div>
  </div>

  <!-- Studio Image Canvas & Brand/Domain Text Overlay Modal -->
  <div class="zs-modal-backdrop" id="zsImageStudioModal">
    <div class="zs-modal zs-image-studio-modal">
      <div class="zs-modal-head">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="zs-picker-head-orb" aria-hidden="true">🎨</div>
          <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <strong style="font-size:16px" id="zsImgStudioTitle">Stüdyo Görsel Kanvas &amp; Marka Yazı Editörü</strong>
              <span class="zs-badge-pill" style="font-size:10px;padding:2px 8px">ÇOK KATMANLI KANVAS · WEBP</span>
            </div>
            <div style="font-size:12px;color:var(--text-muted)">Görsel üzerine başlık, marka, alan adı (domain) ve logo katmanları ekleyin; fareyle sürükleyip konumlandırın ve kaydedin.</div>
          </div>
        </div>
        <button type="button" class="zs-btn zs-btn-sm" id="zsImgStudioClose">Kapat ✕</button>
      </div>
      <div class="zs-modal-body" id="zsImgStudioBody"></div>
    </div>
  </div>

  <!-- Command Palette Modal -->
  <div class="zs-modal-backdrop" id="zsCmdModal">
    <div class="zs-modal zs-cmd-modal">
      <div class="zs-modal-head">
        <strong>Hızlı Komut Merkezi</strong>
        <button type="button" class="zs-btn zs-btn-sm" id="zsCmdClose">ESC</button>
      </div>
      <div class="zs-modal-body">
        <input type="search" id="zsCmdInput" class="zs-input" placeholder="Modül, eylem veya blog yazısı ara..."/>
        <div class="zs-cmd-list" id="zsCmdList"></div>
      </div>
    </div>
  </div>

  <div class="zs-toast-stack" id="zsToastStack"></div>

  <script>
    window.ZEYNART_ADMIN_CONFIG = {
      apiUrl: <?= json_encode($apiUrl) ?>,
      adminUrl: <?= json_encode(zeynart_admin_url('/', $store)) ?>,
      adminSlug: <?= json_encode($activeAdminSlug) ?>,
      adminDirName: <?= json_encode(ADMIN_DIR_NAME) ?>,
      studioVersion: '5.2',
      chunks: <?= json_encode((object)$chunkManifest, JSON_UNESCAPED_SLASHES) ?>,
      sectorExtensions: <?= json_encode($sectorPublicMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
      csrfToken: <?= json_encode($csrf) ?>,
      basePath: <?= json_encode(BASE_PATH) ?>,
      siteUrl: <?= json_encode(SITE_URL) ?>,
      siteCssUrl: <?= json_encode($siteCssUrl) ?>,
      userRole: <?= json_encode($adminRole) ?>
    };
  </script>
  <script src="<?= e($jsUrl) ?>"></script>
<?php foreach ($sectorAssets['js'] as $secJsUrl): ?>
  <script src="<?= e($secJsUrl) ?>"></script>
<?php endforeach; ?>
</body>
<?php endif; ?>
</html>
