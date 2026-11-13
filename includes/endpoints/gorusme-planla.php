<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/agency_growth_suite.php';
require_once ROOT_DIR . '/lib/smtp.php';

if (!empty($_COOKIE['zeynart_sid'])) {
    start_secure_session();
}
send_security_headers();

// GET /api/gorusme-takvim?id=bk_xxx&token=xxx -> RFC 5545 .ics calendar file download
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['ics'])) {
    $bkId = trim((string)($_GET['id'] ?? ''));
    $bkTok = trim((string)($_GET['token'] ?? ''));
    $adminOk = is_admin_authenticated();
    foreach (zeynart_load_bookings() as $bk) {
        $tokMatch = $bkTok !== '' && !empty($bk['ics_token']) && hash_equals((string)$bk['ics_token'], $bkTok);
        if (($bk['id'] ?? '') === $bkId && ($tokMatch || $adminOk)) {
            $store = load_store();
            $ics = zeynart_compile_booking_ics($bk, $store);
            header('Content-Type: text/calendar; charset=UTF-8');
            header('Content-Disposition: attachment; filename="zeynart-kesif-gorusmesi-' . $bk['date'] . '.ics"');
            header('Cache-Control: no-store, max-age=0');
            echo $ics;
            exit;
        }
    }
    http_response_code(404);
    exit('Takvim kaydı bulunamadı.');
}

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (bool $ok, string $message, array $extra = [], int $http = 200): never {
    http_response_code($http);
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(false, 'Yalnızca POST isteği kabul edilir.', [], 405);
}

if (!empty($_POST['website'])) {
    $respond(true, 'Görüşme talebiniz alındı.');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $respond(false, 'Güvenlik anahtarı süresi doldu. Lütfen sayfayı yenileyin.', [], 403);
}

if (!check_rate_limit('discovery_booking', 6, 300)) {
    $respond(false, 'Çok fazla deneme yapıldı. Lütfen birkaç dakika bekleyin.', [], 429);
}

$store = load_store(true);
$cfg = zeynart_get_booking_settings($store);
if (empty($cfg['enabled'])) {
    $respond(false, 'Online görüşme planlayıcı şu an kapalıdır. Lütfen iletişim sayfasından bize ulaşın.', [], 422);
}

$name = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$company = trim((string)($_POST['company'] ?? ''));
$channel = trim((string)($_POST['channel'] ?? 'google_meet'));
$date = trim((string)($_POST['date'] ?? ''));
$time = trim((string)($_POST['time'] ?? ''));
$topic = trim((string)($_POST['topic'] ?? ''));

$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
if (mb_strlen($name, 'UTF-8') < 2) {
    $respond(false, 'Lütfen adınızı ve soyadınızı girin.', [], 422);
}
if (strlen($phoneDigits) < 10) {
    $respond(false, 'Lütfen geçerli bir cep telefonu numarası girin.', [], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $respond(false, 'Lütfen geçerli bir e-posta adresi girin.', [], 422);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    $respond(false, 'Lütfen takvimden geçerli bir gün ve saat dilimi seçin.', [], 422);
}

// Verify slot availability
$availDays = zeynart_compute_available_booking_days($store);
$slotValid = false;
$dateLabel = $date;
foreach ($availDays as $dRow) {
    if ($dRow['date'] === $date) {
        $dateLabel = (string)$dRow['label'];
        foreach ($dRow['slots'] as $sRow) {
            if ($sRow['time'] === $time && !empty($sRow['available'])) {
                $slotValid = true;
                break 2;
            }
        }
    }
}
if (!$slotValid) {
    $respond(false, 'Seçtiğiniz saat dilimi az önce doldu veya geçersiz. Lütfen başka bir saat seçin.', [], 409);
}

$channelMap = [
    'google_meet' => 'Google Meet (Online Sunum)',
    'whatsapp' => 'WhatsApp Sesli / Görüntülü',
    'phone' => 'Doğrudan Telefon Görüşmesi',
];
$channelLabel = $channelMap[$channel] ?? $channelMap['google_meet'];

$bkId = 'bk_' . substr(bin2hex(random_bytes(5)), 0, 10);
$icsToken = bin2hex(random_bytes(12));
$bookingRecord = [
    'id' => $bkId,
    'ics_token' => $icsToken,
    'name' => mb_substr($name, 0, 100, 'UTF-8'),
    'company' => mb_substr($company, 0, 120, 'UTF-8'),
    'phone' => mb_substr($phone, 0, 32, 'UTF-8'),
    'email' => mb_substr($email, 0, 160, 'UTF-8'),
    'channel' => array_key_exists($channel, $channelMap) ? $channel : 'google_meet',
    'channel_label' => $channelLabel,
    'date' => $date,
    'date_label' => $dateLabel,
    'time' => $time,
    'duration_minutes' => (int)$cfg['slot_minutes'],
    'topic' => mb_substr($topic, 0, 800, 'UTF-8'),
    'status' => 'confirmed',
    'created_at' => date('d.m.Y H:i'),
];

$allBookings = zeynart_load_bookings(true);
array_unshift($allBookings, $bookingRecord);
zeynart_save_bookings($allBookings);

// Also inject directly into CRM Pipeline (store['messages']) so Admin Kanban & Bell surface it immediately
$leadEntry = [
    'id' => 'msg_' . substr(bin2hex(random_bytes(5)), 0, 10),
    'source' => 'discovery_booking',
    'name' => $bookingRecord['name'],
    'company' => $bookingRecord['company'],
    'phone' => $bookingRecord['phone'],
    'email' => $bookingRecord['email'],
    'service' => '15 Dk Dijital Keşif Görüşmesi (' . $dateLabel . ' ' . $time . ')',
    'budget' => $channelLabel,
    'message' => "📅 Planlanan Görüşme: {$dateLabel} Saat {$time} ({$cfg['slot_minutes']} dk)\n📡 Kanal: {$channelLabel}" . ($topic !== '' ? "\n📝 Gündem / Proje Özeti: {$topic}" : ''),
    'booking_data' => [
        'booking_id' => $bkId,
        'date' => $date,
        'date_label' => $dateLabel,
        'time' => $time,
        'channel' => $bookingRecord['channel'],
        'channel_label' => $channelLabel,
        'topic' => $bookingRecord['topic'],
    ],
    'crm_stage' => 'new',
    'followup_date' => $date,
    'read' => false,
    'created_at' => date('d.m.Y H:i'),
];
if (!isset($store['messages']) || !is_array($store['messages'])) {
    $store['messages'] = [];
}
array_unshift($store['messages'], $leadEntry);
$GLOBALS['zeynart_skip_revision_capture'] = true;
save_store($store);
unset($GLOBALS['zeynart_skip_revision_capture']);

$toAdmin = resolve_smtp_recipient($store);
if ($toAdmin !== '' && !empty($store['smtp']['enabled'])) {
    $mailHtml = '<div style="font-family:Arial,sans-serif;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px">'
        . '<h2 style="margin:0 0 12px;color:#2dd4bf">📅 Yeni Keşif Görüşmesi Planlandı</h2>'
        . '<p style="margin:0 0 6px"><strong>Ad Soyad:</strong> ' . e($bookingRecord['name']) . ($company !== '' ? ' (' . e($company) . ')' : '') . '</p>'
        . '<p style="margin:0 0 6px"><strong>Telefon:</strong> ' . e($bookingRecord['phone']) . '</p>'
        . '<p style="margin:0 0 6px"><strong>Tarih &amp; Saat:</strong> ' . e($dateLabel . ' — ' . $time) . '</p>'
        . '<p style="margin:0 0 10px"><strong>Görüşme Kanalı:</strong> ' . e($channelLabel) . '</p>'
        . ($topic !== '' ? '<p style="padding:12px;background:#0d2129;border-radius:10px">' . e($topic) . '</p>' : '')
        . '</div>';
    @send_smtp_detailed($store['smtp'], $toAdmin, '📅 Keşif Görüşmesi: ' . $bookingRecord['name'] . ' (' . $dateLabel . ' ' . $time . ')', $mailHtml, ['allow_mail_fallback' => true]);
}

$icsHref = url('/api/gorusme-takvim') . '?ics=1&id=' . rawurlencode($bkId) . '&token=' . rawurlencode($icsToken);
$waAgencyDigits = preg_replace('/\D+/', '', (string)($store['site']['phone_href'] ?? '+908503050533')) ?? '';
$waConfirmHref = $waAgencyDigits !== ''
    ? ('https://wa.me/' . $waAgencyDigits . '?text=' . rawurlencode("Merhaba Zeynart, {$dateLabel} saat {$time} ({$channelLabel}) için 15 dakikalık dijital keşif görüşmemi planladım. Adım: {$bookingRecord['name']}"))
    : '';

$respond(true, 'Harika! Keşif görüşmeniz takvimimize kaydedildi.', [
    'booking' => [
        'id' => $bkId,
        'date_label' => $dateLabel,
        'time' => $time,
        'channel_label' => $channelLabel,
        'duration_minutes' => (int)$cfg['slot_minutes'],
        'ics_url' => $icsHref,
        'whatsapp_url' => $waConfirmHref,
    ],
]);
