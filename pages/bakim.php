<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/studio_suite.php';

$mnt = zeynart_get_maintenance_state($store);
$tb = zeynart_get_theme_brand($store);
$site = $store['site'] ?? [];
$siteName = (string)($site['name'] ?? 'ZEYNART');
$logoUrl = url((string)($site['logo'] ?? '/images/zeynart-logo.avif'));
$faviconUrl = url((string)($site['favicon'] ?? '/icon.png'));
$phone = trim((string)($site['phone'] ?? ''));
$phoneHref = trim((string)($site['phone_href'] ?? preg_replace('/[^0-9+]/', '', $phone)));
$email = trim((string)($site['email'] ?? ''));
$waNum = preg_replace('/\D+/', '', (string)($store['quick_contact']['whatsapp_number'] ?? $phoneHref));
if ($waNum !== '' && str_starts_with($waNum, '0')) {
    $waNum = '90' . substr($waNum, 1);
}

$statusCode = (int)($mnt['http_code'] ?? 503);
if (!headers_sent()) {
    http_response_code($statusCode);
    if ($statusCode === 503) {
        header('Retry-After: 3600');
    }
    header('X-Robots-Tag: noarchive');
    header('X-LiteSpeed-Cache-Control: no-cache');
}
$pRgb = zeynart_hex_to_rgb_triplet($tb['primary_color']);
?><!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= e($mnt['headline']) ?> — <?= e($siteName) ?></title>
  <link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png"/>
  <style>
    :root {
      --bg: <?= e($tb['bg_color']) ?>;
      --surface: <?= e($tb['surface_color']) ?>;
      --primary: <?= e($tb['primary_color']) ?>;
      --accent: <?= e($tb['accent_color']) ?>;
      --bright: <?= e($tb['bright_color']) ?>;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100dvh;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Inter, Arial, sans-serif;
      background:
        radial-gradient(900px 460px at 18% 12%, rgba(<?= $pRgb ?>, 0.18), transparent 65%),
        radial-gradient(760px 420px at 86% 88%, rgba(<?= $pRgb ?>, 0.12), transparent 65%),
        var(--bg);
      color: #f4f8fb;
      display: grid;
      place-items: center;
      padding: 28px 18px;
      -webkit-font-smoothing: antialiased;
    }
    .zm-card {
      width: min(760px, 100%);
      padding: clamp(30px, 5vw, 56px);
      border-radius: 28px;
      background: linear-gradient(155deg, rgba(16, 32, 40, 0.78), rgba(8, 17, 22, 0.88));
      border: 1px solid rgba(<?= $pRgb ?>, 0.32);
      box-shadow: 0 32px 90px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.14);
      backdrop-filter: blur(24px) saturate(150%);
      -webkit-backdrop-filter: blur(24px) saturate(150%);
      text-align: center;
    }
    .zm-logo {
      max-height: 54px;
      width: auto;
      margin: 0 auto 24px;
      display: block;
    }
    .zm-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 15px;
      border-radius: 999px;
      font-size: 11.5px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--bright);
      background: rgba(<?= $pRgb ?>, 0.13);
      border: 1px solid rgba(<?= $pRgb ?>, 0.36);
      margin-bottom: 20px;
    }
    .zm-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--bright);
      box-shadow: 0 0 12px var(--primary);
      animation: zmPulse 1.8s infinite ease-in-out;
    }
    @keyframes zmPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .45; transform: scale(.82); } }
    h1 {
      font-size: clamp(28px, 4.2vw, 46px);
      line-height: 1.15;
      letter-spacing: -1.2px;
      font-weight: 700;
      margin-bottom: 16px;
    }
    .zm-msg {
      color: #b8c9d4;
      font-size: clamp(14.5px, 1.6vw, 17px);
      line-height: 1.75;
      max-width: 580px;
      margin: 0 auto 28px;
    }
    .zm-countdown {
      display: inline-flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;
      margin-bottom: 28px;
    }
    .zm-cd-box {
      min-width: 76px;
      padding: 12px 14px;
      border-radius: 16px;
      background: rgba(4, 11, 15, 0.68);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .zm-cd-box strong {
      display: block;
      font-size: 24px;
      font-weight: 800;
      color: #ffffff;
      font-variant-numeric: tabular-nums;
    }
    .zm-cd-box small {
      font-size: 10.5px;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: #8fa6b5;
    }
    .zm-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
      margin-top: 8px;
    }
    .zm-btn {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      padding: 13px 22px;
      border-radius: 999px;
      font-size: 13.5px;
      font-weight: 600;
      text-decoration: none;
      color: #ffffff;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.16);
      transition: transform .2s, border-color .2s, background .2s;
    }
    .zm-btn:hover {
      transform: translateY(-2px);
      border-color: var(--bright);
      background: rgba(<?= $pRgb ?>, 0.16);
    }
    .zm-btn.primary {
      background: linear-gradient(135deg, var(--accent), var(--primary));
      border-color: var(--bright);
      box-shadow: 0 10px 28px rgba(<?= $pRgb ?>, 0.3);
    }
    .zm-foot {
      margin-top: 30px;
      padding-top: 18px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      font-size: 12px;
      color: #7d93a1;
    }
    .zm-foot a { color: #9cb2c0; text-decoration: none; }
    .zm-foot a:hover { color: #ffffff; }
  </style>
</head>
<body>
  <main class="zm-card">
    <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="zm-logo"/>
    <div class="zm-badge"><span class="zm-dot"></span><span><?= e($mnt['badge']) ?></span></div>
    <h1><?= e($mnt['headline']) ?></h1>
    <p class="zm-msg"><?= nl2br(e($mnt['message'])) ?></p>

    <?php if ($mnt['estimated_return'] !== ''): ?>
    <div class="zm-countdown" id="zmCountdown" data-target="<?= e($mnt['estimated_return']) ?>">
      <div class="zm-cd-box"><strong data-cd="d">00</strong><small>Gün</small></div>
      <div class="zm-cd-box"><strong data-cd="h">00</strong><small>Saat</small></div>
      <div class="zm-cd-box"><strong data-cd="m">00</strong><small>Dakika</small></div>
      <div class="zm-cd-box"><strong data-cd="s">00</strong><small>Saniye</small></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($mnt['show_contact_info'])): ?>
    <div class="zm-actions">
      <?php if ($waNum !== ''): ?>
      <a class="zm-btn primary" href="https://wa.me/<?= e($waNum) ?>" target="_blank" rel="noopener">💬 WhatsApp Hattı</a>
      <?php endif; ?>
      <?php if ($phone !== ''): ?>
      <a class="zm-btn" href="tel:<?= e($phoneHref) ?>">📞 <?= e($phone) ?></a>
      <?php endif; ?>
      <?php if ($email !== ''): ?>
      <a class="zm-btn" href="mailto:<?= e($email) ?>">✉ <?= e($email) ?></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="zm-foot">
      <span>© <?= date('Y') ?> <?= e($siteName) ?></span>
      <a href="<?= e(url('/admin/')) ?>">Yönetici Girişi →</a>
    </div>
  </main>

  <?php if ($mnt['estimated_return'] !== ''): ?>
  <script>
    (function () {
      var wrap = document.getElementById('zmCountdown');
      if (!wrap) return;
      var target = Date.parse(wrap.getAttribute('data-target') || '');
      if (isNaN(target)) { wrap.style.display = 'none'; return; }
      var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
      var tick = function () {
        var diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
        wrap.querySelector('[data-cd="d"]').textContent = pad(Math.floor(diff / 86400));
        wrap.querySelector('[data-cd="h"]').textContent = pad(Math.floor((diff % 86400) / 3600));
        wrap.querySelector('[data-cd="m"]').textContent = pad(Math.floor((diff % 3600) / 60));
        wrap.querySelector('[data-cd="s"]').textContent = pad(diff % 60);
      };
      tick();
      setInterval(tick, 1000);
    })();
  </script>
  <?php endif; ?>
</body>
</html>
