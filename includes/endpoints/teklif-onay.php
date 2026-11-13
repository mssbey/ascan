<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/enterprise_cms_suite.php';

if (!empty($_COOKIE['zeynart_sid'])) {
    start_secure_session();
}
send_security_headers();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-LiteSpeed-Cache-Control: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$respond = static function (bool $ok, string $message, int $httpCode = 200, array $extra = []): never {
    http_response_code($httpCode);
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    $respond(false, 'Geçersiz istek yöntemi.', 405);
}

$rawBody = (string)file_get_contents('php://input');
$jsonIn = $rawBody !== '' ? json_decode($rawBody, true) : null;
$in = is_array($jsonIn) ? array_merge($_POST, $jsonIn) : $_POST;

if (!verify_csrf(isset($in['csrf_token']) ? (string)$in['csrf_token'] : null)) {
    $respond(false, 'Güvenlik doğrulaması süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.', 403);
}

if (!check_rate_limit('proposal_approve', 6, 300)) {
    $respond(false, 'Çok fazla işlem denemesi yapıldı. Lütfen birkaç dakika bekleyin.', 429);
}

$code = strtolower(trim((string)($in['code'] ?? '')));
$signerName = mb_substr(trim(strip_tags((string)($in['signer_name'] ?? ''))), 0, 120, 'UTF-8');
$signerNote = mb_substr(trim(strip_tags((string)($in['approval_note'] ?? ''))), 0, 800, 'UTF-8');

if ($code === '' || strlen($code) < 4) {
    $respond(false, 'Geçersiz teklif kodu.', 422);
}
if (mb_strlen($signerName, 'UTF-8') < 2) {
    $respond(false, 'Lütfen onaylayan yetkili ad ve soyadını girin.', 422);
}

$proposals = zeynart_load_proposals();
$targetIdx = null;
foreach ($proposals as $idx => $p) {
    if (strtolower((string)($p['code'] ?? '')) === $code) {
        $targetIdx = $idx;
        break;
    }
}

if ($targetIdx === null || ($proposals[$targetIdx]['status'] ?? 'sent') === 'draft') {
    $respond(false, 'Teklif kaydı bulunamadı.', 404);
}

if (($proposals[$targetIdx]['status'] ?? '') === 'approved') {
    $existingChain = is_array($proposals[$targetIdx]['automation_chain'] ?? null) ? $proposals[$targetIdx]['automation_chain'] : [];
    $respond(true, 'Bu teklif zaten onaylanmış durumdadır.', 200, [
        'approved_at' => $proposals[$targetIdx]['approved_at'],
        'approved_by' => $proposals[$targetIdx]['approved_by'],
        'contract_no' => $proposals[$targetIdx]['contract_no'] ?? '',
        'contract_url' => $existingChain['contract_url'] ?? ('/sozlesme/' . $code . '/'),
        'tracker_url' => $existingChain['tracker_url'] ?? ('/proje-takip/' . $code . '/'),
    ]);
}

$now = date('Y-m-d H:i:s');
$store = load_store(true);

// Run 4-step Stüdyo v4 Automation Chain (Approve + Contract No + Client Tracker Portal + Uptime + +1Yr Renewal)
$chainRes = zeynart_run_proposal_automation_chain($code, [
    'signer_name' => $signerName,
    'approval_note' => $signerNote,
], $store);

$prp = is_array($chainRes['proposal'] ?? null) ? $chainRes['proposal'] : $proposals[$targetIdx];
$formattedTotal = number_format((float)($prp['grand_total'] ?? 0), 2, ',', '.') . ' ' . ($prp['currency_symbol'] ?? '₺');
$contractUrl = (string)($chainRes['contract_url'] ?? ('/sozlesme/' . $code . '/'));
$trackerUrl = (string)($chainRes['tracker_url'] ?? ('/proje-takip/' . $code . '/'));
$contractNo = (string)($chainRes['contract_no'] ?? ($prp['contract_no'] ?? ''));

// Also push an instant lead notification into /admin/?view=messages so the studio sees it in KPIs & Leads
if (!isset($store['messages']) || !is_array($store['messages'])) {
    $store['messages'] = [];
}
$lead = [
    'id' => 'msg-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
    'source' => 'Online Teklif Onayı',
    'name' => $signerName . ($prp['client_company'] !== '' ? (' (' . $prp['client_company'] . ')') : ''),
    'email' => (string)($prp['client_email'] ?? ''),
    'phone' => (string)($prp['client_phone'] ?? ''),
    'company' => (string)($prp['client_company'] ?? ''),
    'service' => 'Teklif Onaylandı: ' . ($prp['proposal_no'] ?? $code),
    'budget' => $formattedTotal,
    'message' => 'Müşteri "' . ($prp['project_title'] ?? '') . '" başlıklı online teklifi (' . ($prp['proposal_no'] ?? $code) . ') dijital olarak onayladı.'
        . ($signerNote !== '' ? ("\nNot: " . $signerNote) : ''),
    'ip' => get_client_ip(),
    'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 220, 'UTF-8'),
    'read' => false,
    'status' => 'new',
    'created_at' => $now,
];
array_unshift($store['messages'], $lead);
save_store($store);

if (!empty($store['smtp']['enabled'])) {
    try {
        require_once ROOT_DIR . '/lib/smtp.php';
        $notifyTo = resolve_smtp_recipient($store);
        if ($notifyTo !== '') {
            $mailHtml = '<div style="font-family:Arial,sans-serif;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px">'
                . '<h2 style="margin:0 0 12px;color:#2dd4bf">✅ Online Teklif Onaylandı!</h2>'
                . '<p><strong>Teklif No:</strong> ' . e((string)($prp['proposal_no'] ?? $code)) . '</p>'
                . '<p><strong>Proje:</strong> ' . e((string)($prp['project_title'] ?? '')) . '</p>'
                . '<p><strong>Onaylayan Yetkili:</strong> ' . e($signerName) . ' (' . e((string)($prp['client_company'] ?? '')) . ')</p>'
                . '<p><strong>Toplam Yatırım:</strong> ' . e($formattedTotal) . '</p>'
                . ($signerNote !== '' ? ('<p><strong>Müşteri Notu:</strong> ' . nl2br_e($signerNote) . '</p>') : '')
                . '<p style="font-size:12px;color:#94a3b8;margin-top:14px">Onay Zamanı: ' . e($now) . ' · IP: ' . e(get_client_ip()) . '</p></div>';
            @send_smtp_detailed($store['smtp'], $notifyTo, 'Teklif Onaylandı: ' . ($prp['proposal_no'] ?? $signerName), $mailHtml, ['allow_mail_fallback' => true]);
        }
    } catch (\Throwable $_) {
    }
}

$respond(true, 'Teklif onayınız kaydedildi, resmi hizmet sözleşmeniz ve canlı proje takip portalınız otomatik oluşturuldu!', 200, [
    'approved_at' => $prp['approved_at'] ?? $now,
    'approved_by' => $signerName,
    'contract_no' => $contractNo,
    'contract_url' => $contractUrl,
    'tracker_url' => $trackerUrl,
]);
