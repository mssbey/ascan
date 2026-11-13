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
header('Content-Type: application/json; charset=UTF-8');

$respond = static function (bool $ok, string $message, array $extra = [], int $httpCode = 200): never {
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'ok' => $ok,
        'message' => t($message),
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(false, 'Geçersiz istek yöntemi.', [], 405);
}

$raw = $_POST;
if ($raw === []) {
    $bodyStr = (string)file_get_contents('php://input');
    $dec = json_decode($bodyStr, true);
    if (is_array($dec)) {
        $raw = $dec;
    }
}

if (!empty($raw['website'])) {
    $respond(true, 'Talebiniz başarıyla alındı.');
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($raw['csrf_token'] ?? null);
if (!verify_csrf(is_string($csrf) ? $csrf : null)) {
    $respond(false, 'Güvenlik doğrulaması süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.', [], 403);
}

if (!check_rate_limit('interactive_lead', 8, 300)) {
    $respond(false, 'Çok fazla talep gönderildi. Lütfen birkaç dakika bekleyin.', [], 429);
}

$sourceType = in_array((string)($raw['source_type'] ?? 'quote_wizard'), ['quote_wizard', 'smart_popup'], true)
    ? (string)$raw['source_type']
    : 'quote_wizard';

$name = trim((string)($raw['name'] ?? ''));
$phone = trim((string)($raw['phone'] ?? ''));
$email = trim((string)($raw['email'] ?? ''));
$company = trim((string)($raw['company'] ?? ''));
$notes = trim((string)($raw['notes'] ?? ''));
$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';

if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
    $respond(false, 'Lütfen geçerli bir ad ve soyad girin.', [], 422);
}
$phoneValid = (
    strlen($phoneDigits) >= 10
    && strlen($phoneDigits) <= 15
    && mb_strlen($phone, 'UTF-8') <= 35
    && preg_match('/^[0-9+\s().\-]+$/u', $phone) === 1
);
if (!$phoneValid) {
    $respond(false, 'Lütfen geçerli bir cep telefonu numarası girin.', [], 422);
}
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 200)) {
    $respond(false, 'Lütfen geçerli bir e-posta adresi girin.', [], 422);
}

$store = load_store(true);
$siteName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';

if ($sourceType === 'smart_popup') {
    $serviceLabel = 'Hızlı Geri Arama (Akıllı Pop-up)';
    $budgetLabel = 'Telefonda Görüşülecek';
    $messageText = 'Akıllı Pop-up formu üzerinden hızlı geri arama talebi oluşturuldu.' . ($notes !== '' ? (' Not: ' . $notes) : '');
    $wizardData = null;
} else {
    $wCfg = zeynart_get_quote_wizard_config($store);
    $catId = (string)($raw['category_id'] ?? '');
    $featIds = is_array($raw['feature_ids'] ?? null) ? array_map('strval', $raw['feature_ids']) : [];
    $tlId = (string)($raw['timeline_id'] ?? '');

    $chosenCat = $wCfg['categories'][0] ?? ['title' => 'Kurumsal Proje', 'min' => 0, 'max' => 0];
    foreach ($wCfg['categories'] as $c) {
        if ($c['id'] === $catId) {
            $chosenCat = $c;
            break;
        }
    }

    $chosenFeats = [];
    $minSum = (int)($chosenCat['min'] ?? 0);
    $maxSum = (int)($chosenCat['max'] ?? 0);
    foreach ($wCfg['features'] as $f) {
        if (in_array($f['id'], $featIds, true)) {
            $chosenFeats[] = $f['title'];
            $minSum += (int)($f['min'] ?? 0);
            $maxSum += (int)($f['max'] ?? 0);
        }
    }

    $chosenTl = $wCfg['timelines'][0]['title'] ?? 'Standart Planlama';
    foreach ($wCfg['timelines'] as $tl) {
        if ($tl['id'] === $tlId) {
            $chosenTl = $tl['title'];
            break;
        }
    }

    $estText = ($wCfg['show_price_estimate'] && ($maxSum > 0 || $minSum > 0))
        ? (number_format($minSum, 0, ',', '.') . ' – ' . number_format($maxSum, 0, ',', '.') . ' ' . $wCfg['currency'])
        : 'Kapsama Özel Teklif';

    $serviceLabel = $chosenCat['title'];
    $budgetLabel = $estText;
    $parts = [
        'Hizmet: ' . $chosenCat['title'],
        'Ek Özellikler: ' . ($chosenFeats !== [] ? implode(', ', $chosenFeats) : 'Standart Kapsam'),
        'Hedef Takvim: ' . $chosenTl,
    ];
    if ($wCfg['show_price_estimate'] && ($maxSum > 0 || $minSum > 0)) {
        $parts[] = 'Tahmini Aralık: ' . $estText;
    }
    if ($notes !== '') {
        $parts[] = 'Müşteri Notu: ' . $notes;
    }
    $messageText = implode(' | ', $parts);
    $wizardData = [
        'category' => $chosenCat['title'],
        'features' => $chosenFeats,
        'timeline' => $chosenTl,
        'estimated_range' => $estText,
        'notes' => $notes,
    ];
}

$lead = [
    'id' => 'msg-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
    'source' => $sourceType,
    'name' => $name,
    'email' => $email !== '' ? $email : '-',
    'phone' => $phone,
    'company' => $company,
    'service' => $serviceLabel,
    'budget' => $budgetLabel,
    'message' => $messageText,
    'wizard_data' => $wizardData,
    'ip' => get_client_ip(),
    'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 220, 'UTF-8'),
    'read' => false,
    'status' => 'new',
    'created_at' => date('Y-m-d H:i:s'),
];

if (!isset($store['messages']) || !is_array($store['messages'])) {
    $store['messages'] = [];
}

$smtpCfg = is_array($store['smtp'] ?? null) ? $store['smtp'] : [];
$notifyTo = resolve_smtp_recipient($store);
if (!empty($smtpCfg['enabled']) && $notifyTo !== '') {
    $cleanTelHref = preg_replace('/[^0-9+]/', '', $phone) ?: $phone;
    $mailSubject = ($sourceType === 'smart_popup' ? 'Hızlı Geri Arama Talebi: ' : 'Teklif Sihirbazı Talebi: ') . $name;
    $mailHtml = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px;border:1px solid #16353f">'
        . '<h2 style="margin:0 0 16px;color:#2dd4bf;font-size:20px">' . e($mailSubject) . ' — ' . e($siteName) . '</h2>'
        . '<p style="margin:8px 0"><strong>Ad Soyad:</strong> ' . e($name) . '</p>'
        . '<p style="margin:8px 0"><strong>Cep Telefonu:</strong> <a href="tel:' . e($cleanTelHref) . '" style="color:#5eead4">' . e($phone) . '</a></p>'
        . ($email !== '' ? '<p style="margin:8px 0"><strong>E-posta:</strong> ' . e($email) . '</p>' : '')
        . ($company !== '' ? '<p style="margin:8px 0"><strong>Firma:</strong> ' . e($company) . '</p>' : '')
        . '<p style="margin:8px 0"><strong>Seçilen Hizmet:</strong> ' . e($serviceLabel) . '</p>'
        . '<p style="margin:8px 0"><strong>Tahmini Bütçe / Kapsam:</strong> ' . e($budgetLabel) . '</p>'
        . '<hr style="border:0;border-top:1px solid #16353f;margin:16px 0"/>'
        . '<p style="margin:8px 0;line-height:1.65">' . e($messageText) . '</p>'
        . '</div>';
    $mailRes = send_smtp_detailed($smtpCfg, $notifyTo, $mailSubject, $mailHtml, [
        'allow_mail_fallback' => true,
        'reply_to_email' => $email !== '' ? $email : '',
        'reply_to_name' => $name,
    ]);
    $lead['mail_sent'] = !empty($mailRes['ok']);
}

$GLOBALS['zeynart_skip_revision_capture'] = true;
array_unshift($store['messages'], $lead);
save_store($store);
unset($GLOBALS['zeynart_skip_revision_capture']);

$rawStudioWa = preg_replace('/\D+/', '', (string)($store['quick_contact']['whatsapp_number'] ?? $store['site']['phone_href'] ?? '908503050533')) ?? '';
if (str_starts_with($rawStudioWa, '0') && strlen($rawStudioWa) === 11) {
    $rawStudioWa = '9' . $rawStudioWa;
}
$waSummaryText = 'Merhaba ' . $siteName . ', Teklif Sihirbazı üzerinden talebimi ilettim (' . $name . ' - ' . $serviceLabel . ' / ' . $budgetLabel . '). Detayları görüşmek isterim.';
$waUrl = $rawStudioWa !== '' ? ('https://wa.me/' . $rawStudioWa . '?text=' . rawurlencode($waSummaryText)) : '';

$respond(true, 'Talebiniz başarıyla kaydedildi! Ekibimiz en kısa sürede telefonla dönüş yapacaktır.', [
    'lead_id' => $lead['id'],
    'wa_url' => $waUrl,
]);
