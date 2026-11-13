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
    $respond(true, 'Geri bildiriminiz kaydedildi.');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $respond(false, 'Güvenlik doğrulaması süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.', [], 403);
}

if (!check_rate_limit('client_tracker_feedback', 8, 300)) {
    $respond(false, 'Çok sık işlem yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.', [], 429);
}

$code = strtolower(trim((string)($_POST['code'] ?? '')));
$type = in_array(($_POST['feedback_type'] ?? ''), ['approve', 'revision'], true) ? (string)$_POST['feedback_type'] : 'approve';
$author = trim((string)($_POST['author'] ?? ''));
$note = trim((string)($_POST['note'] ?? ''));

if ($code === '') {
    $respond(false, 'Geçersiz proje takip kodu.', [], 422);
}
if (mb_strlen($author, 'UTF-8') < 2) {
    $respond(false, 'Lütfen adınızı ve soyadınızı girin.', [], 422);
}
if ($type === 'revision' && mb_strlen($note, 'UTF-8') < 4) {
    $respond(false, 'Lütfen revize veya ek talep notunuzu kısaca belirtin.', [], 422);
}

$all = zeynart_load_client_trackers(true);
$foundIdx = null;
foreach ($all as $idx => $trk) {
    if (strtolower((string)$trk['code']) === $code && ($trk['status'] ?? 'active') !== 'draft') {
        $foundIdx = $idx;
        break;
    }
}

if ($foundIdx === null) {
    $respond(false, 'Proje takip kaydı bulunamadı.', [], 404);
}

$currentStepTitle = '';
foreach (($all[$foundIdx]['milestones'] ?? []) as $ms) {
    if (($ms['status'] ?? '') === 'current') {
        $currentStepTitle = (string)($ms['title'] ?? '');
        break;
    }
}
if ($currentStepTitle === '' && !empty($all[$foundIdx]['milestones'])) {
    $lastMs = end($all[$foundIdx]['milestones']);
    $currentStepTitle = (string)($lastMs['title'] ?? 'Proje Aşaması');
}

$fbEntry = [
    'id' => 'fb_' . substr(bin2hex(random_bytes(4)), 0, 8),
    'type' => $type,
    'author' => mb_substr($author, 0, 100, 'UTF-8'),
    'milestone_title' => $currentStepTitle,
    'note' => mb_substr($note, 0, 1500, 'UTF-8'),
    'created_at' => date('d.m.Y H:i'),
    'seen_by_admin' => false,
];

if (!is_array($all[$foundIdx]['feedbacks'] ?? null)) {
    $all[$foundIdx]['feedbacks'] = [];
}
array_unshift($all[$foundIdx]['feedbacks'], $fbEntry);
if ($type === 'approve' && ($all[$foundIdx]['status'] ?? '') === 'awaiting_approval') {
    $all[$foundIdx]['status'] = 'active';
}

$GLOBALS['zeynart_skip_revision_capture'] = true;
zeynart_save_client_trackers($all);
unset($GLOBALS['zeynart_skip_revision_capture']);

$store = load_store();
$toAdmin = resolve_smtp_recipient($store);
if ($toAdmin !== '' && !empty($store['smtp']['enabled'])) {
    $badgeTxt = $type === 'approve' ? '✅ AŞAMA ONAYLANDI' : '✏️ REVİZE / NOT İLETİLDİ';
    $mailHtml = '<div style="font-family:Arial,sans-serif;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px">'
        . '<h2 style="margin:0 0 10px;color:#2dd4bf">' . e($badgeTxt) . ' — ' . e((string)$all[$foundIdx]['project_title']) . '</h2>'
        . '<p style="margin:0 0 8px"><strong>Müşteri:</strong> ' . e($author) . ' (' . e((string)$all[$foundIdx]['client_company']) . ')</p>'
        . '<p style="margin:0 0 8px"><strong>İlgili Aşama:</strong> ' . e($currentStepTitle) . '</p>'
        . ($note !== '' ? '<p style="padding:12px;background:#0d2129;border-radius:10px;white-space:pre-wrap">' . e($note) . '</p>' : '')
        . '</div>';
    @send_smtp_detailed($store['smtp'], $toAdmin, $badgeTxt . ': ' . ($all[$foundIdx]['project_title'] ?? $code), $mailHtml, ['allow_mail_fallback' => true]);
}

$msgText = $type === 'approve'
    ? 'Teşekkür ederiz! Mevcut proje aşaması onayınız proje ekibimize iletildi.'
    : 'Teşekkür ederiz! Revize ve değerlendirme notunuz proje ekibimize iletildi.';

$respond(true, $msgText, ['feedback' => $fbEntry]);
