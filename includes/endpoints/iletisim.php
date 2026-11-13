<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once ROOT_DIR . '/lib/smtp.php';

if (!empty($_COOKIE['zeynart_sid'])) {
    start_secure_session();
}
send_security_headers();

$reqLang = strtolower(trim((string)($_POST['lang'] ?? $_GET['lang'] ?? 'tr')));
if (in_array($reqLang, ['tr', 'en', 'de', 'ru'], true)) {
    zeynart_set_lang($reqLang);
}

$isAjax = (
    strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
    || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
);

$respond = static function (bool $ok, string $message, int $httpCode = 200) use ($isAjax): never {
    http_response_code($httpCode);
    $locMsg = t($message);
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => $ok, 'message' => $locMsg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $statusKey = $ok ? 'basarili' : 'hata';
    header('Location: ' . url('/iletisim/?durum=' . $statusKey));
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(false, 'Geçersiz istek yöntemi.', 405);
}

// Honeypot check
if (!empty($_POST['website'])) {
    $respond(true, 'Talebiniz alındı, teşekkür ederiz.', 200);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $respond(false, 'Güvenlik doğrulaması süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.', 403);
}

if (!check_rate_limit('contact_form', 6, 300)) {
    $respond(false, 'Çok fazla talep gönderildi. Lütfen birkaç dakika sonra tekrar deneyin.', 429);
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$company = trim((string)($_POST['company'] ?? ''));
$service = trim((string)($_POST['service'] ?? ''));
$budget = trim((string)($_POST['budget'] ?? 'Birlikte değerlendirelim'));
$message = trim((string)($_POST['message'] ?? ''));
$consent = !empty($_POST['consent']);

$store = load_store(true);
$requirePhone = ($store['contact']['require_phone'] ?? true) !== false;
$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';

if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
    $respond(false, 'Lütfen geçerli bir ad ve soyad girin.', 422);
}
if ($requirePhone || $phone !== '') {
    $phoneValid = (
        strlen($phoneDigits) >= 10
        && strlen($phoneDigits) <= 15
        && mb_strlen($phone, 'UTF-8') <= 35
        && preg_match('/^[0-9+\s().\-]+$/u', $phone) === 1
    );
    if (!$phoneValid) {
        $respond(false, 'Lütfen geçerli bir cep telefonu numarası girin.', 422);
    }
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 200) {
    $respond(false, 'Lütfen geçerli bir e-posta adresi girin.', 422);
}
if ($service === '') {
    $respond(false, 'Lütfen ilgilendiğiniz hizmet alanını seçin.', 422);
}
if (mb_strlen($message, 'UTF-8') < 10 || mb_strlen($message, 'UTF-8') > 5000) {
    $respond(false, 'Lütfen projeniz hakkında en az 10 karakterlik kısa bir bilgi paylaşın.', 422);
}
if (!$consent) {
    $respond(false, 'Lütfen gizlilik bilgilendirmesini onaylayın.', 422);
}

$lead = [
    'id' => 'msg-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'company' => $company,
    'service' => $service,
    'budget' => $budget,
    'message' => $message,
    'ip' => get_client_ip(),
    'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 220, 'UTF-8'),
    'read' => false,
    'status' => 'new',
    'created_at' => date('Y-m-d H:i:s'),
];

if (!isset($store['messages']) || !is_array($store['messages'])) {
    $store['messages'] = [];
}

// Optional SMTP / Local Postfix notification
$smtpCfg = is_array($store['smtp'] ?? null) ? $store['smtp'] : [];
$notifyTo = resolve_smtp_recipient($store);
if (!empty($smtpCfg['enabled']) && $notifyTo !== '') {
    $cleanTelHref = preg_replace('/[^0-9+]/', '', $phone) ?: $phone;
    $waDigits = $phoneDigits;
    if (str_starts_with($waDigits, '0') && strlen($waDigits) === 11) {
        $waDigits = '9' . $waDigits;
    } elseif (strlen($waDigits) === 10 && str_starts_with($waDigits, '5')) {
        $waDigits = '90' . $waDigits;
    }
    $waMailLink = $waDigits !== '' ? ('https://wa.me/' . $waDigits) : '';

    $mailHtml = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px;border:1px solid #16353f">'
        . '<h2 style="margin:0 0 16px;color:#2dd4bf;font-size:20px">Yeni Proje Talebi — Zeynart</h2>'
        . '<p style="margin:8px 0"><strong>Ad Soyad:</strong> ' . e($name) . '</p>'
        . ($phone !== '' ? '<p style="margin:8px 0"><strong>Cep Telefonu:</strong> <a href="tel:' . e($cleanTelHref) . '" style="color:#5eead4">' . e($phone) . '</a>' . ($waMailLink !== '' ? ' · <a href="' . e($waMailLink) . '" style="color:#4ade80">WhatsApp ile Yaz</a>' : '') . '</p>' : '')
        . '<p style="margin:8px 0"><strong>E-posta:</strong> <a href="mailto:' . e($email) . '" style="color:#5eead4">' . e($email) . '</a></p>'
        . ($company !== '' ? '<p style="margin:8px 0"><strong>Şirket:</strong> ' . e($company) . '</p>' : '')
        . '<p style="margin:8px 0"><strong>Hizmet:</strong> ' . e($service) . '</p>'
        . '<p style="margin:8px 0"><strong>Bütçe:</strong> ' . e($budget) . '</p>'
        . '<hr style="border:0;border-top:1px solid #16353f;margin:16px 0"/>'
        . '<p style="margin:8px 0;line-height:1.65"><strong>Mesaj:</strong><br>' . nl2br_e($message) . '</p>'
        . '</div>';

    $mailRes = send_smtp_detailed($smtpCfg, $notifyTo, 'Yeni Proje Talebi: ' . $name, $mailHtml, [
        'allow_mail_fallback' => true,
        'reply_to_email' => $email,
        'reply_to_name' => $name,
    ]);
    $lead['mail_sent'] = !empty($mailRes['ok']);
    $store['smtp_last_status'] = [
        'ok' => !empty($mailRes['ok']),
        'at' => date('Y-m-d H:i:s'),
        'to' => $notifyTo,
        'method' => (string)($mailRes['method'] ?? 'smtp'),
        'error' => (string)($mailRes['error'] ?? ''),
    ];
}

array_unshift($store['messages'], $lead);
save_store($store);

$customSuccessMsg = trim((string)($store['contact']['success_message'] ?? ''));
$respond(true, $customSuccessMsg !== '' ? $customSuccessMsg : 'Proje talebiniz başarıyla alındı. Ekibimiz en kısa sürede sizinle iletişime geçecektir.');
