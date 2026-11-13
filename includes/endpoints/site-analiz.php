<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/security.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/site_audit.php';
require_once ROOT_DIR . '/lib/smtp.php';

if (!empty($_COOKIE['zeynart_sid'])) {
    start_secure_session();
}
send_security_headers();
header('Content-Type: application/json; charset=UTF-8');

$input = $_POST;
if ($input === []) {
    $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($ct, 'application/json')) {
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
}

$reqLang = strtolower(trim((string)($input['lang'] ?? $_GET['lang'] ?? 'tr')));
if (!in_array($reqLang, ['tr', 'en', 'de', 'ru'], true)) {
    $reqLang = 'tr';
}
zeynart_set_lang($reqLang);

$jsonOut = static function (bool $ok, array $payload = [], int $status = 200): never {
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $jsonOut(false, ['error' => t('Geçersiz istek yöntemi.')], 405);
}

$action = strtolower(trim((string)($input['op'] ?? $input['action'] ?? 'analyze')));
$store = load_store();
$auditState = get_site_audit_state($store);

if (empty($auditState['enabled']) && !is_admin_logged_in()) {
    $jsonOut(false, ['error' => t('Site analiz aracı şu anda geçici olarak kapalıdır.')], 403);
}

if ($action === 'analyze') {
    if (!check_rate_limit('site_audit_analyze', 14, 300)) {
        $jsonOut(false, ['error' => t('Çok fazla talep gönderildi. Lütfen birkaç dakika sonra tekrar deneyin.')], 429);
    }

    // Release session lock before external network HTTP inspection so concurrent page loads never wait
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $rawUrl = trim((string)($input['url'] ?? ''));
    $report = zeynart_run_site_audit($rawUrl, $reqLang, $store);
    if (empty($report['ok'])) {
        $jsonOut(false, ['error' => (string)($report['error'] ?? t('Analiz tamamlanamadı.'))], 422);
    }

    $jsonOut(true, ['report' => $report]);
}

if ($action === 'lead') {
    if (!empty($input['website_hp'])) {
        $jsonOut(true, ['message' => t('Talebiniz alındı, teşekkür ederiz.')]);
    }

    $csrfToken = is_string($input['csrf_token'] ?? null) ? $input['csrf_token'] : null;
    if (!verify_csrf($csrfToken)) {
        $jsonOut(false, ['error' => t('Güvenlik doğrulaması süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.')], 403);
    }

    if (!check_rate_limit('site_audit_lead', 8, 300)) {
        $jsonOut(false, ['error' => t('Çok fazla talep gönderildi. Lütfen birkaç dakika sonra tekrar deneyin.')], 429);
    }

    $name = trim((string)($input['name'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $note = trim((string)($input['note'] ?? ''));
    $consent = !empty($input['consent']);
    $auditToken = trim((string)($input['audit_token'] ?? ''));

    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
        $jsonOut(false, ['error' => t('Lütfen geçerli bir ad ve soyad girin.')], 422);
    }
    if (mb_strlen($phone, 'UTF-8') < 6 || mb_strlen($phone, 'UTF-8') > 40) {
        $jsonOut(false, ['error' => t('Lütfen geçerli bir telefon veya WhatsApp numarası girin.')], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 200) {
        $jsonOut(false, ['error' => t('Lütfen geçerli bir e-posta adresi girin.')], 422);
    }
    if (!$consent) {
        $jsonOut(false, ['error' => t('Lütfen gizlilik bilgilendirmesini onaylayın.')], 422);
    }

    $storeFresh = load_store(true);
    $verifiedAudit = $auditToken !== '' ? zeynart_verify_audit_token($auditToken, $storeFresh) : null;

    $domain = (string)($verifiedAudit['domain'] ?? 'Belirtilmedi');
    $auditedUrl = (string)($verifiedAudit['url'] ?? '');
    $overallScore = (int)($verifiedAudit['overall_score'] ?? 0);
    $scores = is_array($verifiedAudit['scores'] ?? null) ? $verifiedAudit['scores'] : [];
    $ttfbMs = (int)($verifiedAudit['ttfb_ms'] ?? 0);
    $issuesList = is_array($verifiedAudit['issues'] ?? null) ? $verifiedAudit['issues'] : [];

    $summaryLines = [];
    if ($auditedUrl !== '') {
        $summaryLines[] = "Analiz Edilen Site: {$auditedUrl} (Genel Skor: {$overallScore}/100 | TTFB: {$ttfbMs} ms)";
        $summaryLines[] = sprintf(
            'Alt Skorlar -> SSL: %d | Hız: %d | Mobil: %d | SEO: %d',
            (int)($scores['ssl'] ?? 0),
            (int)($scores['speed'] ?? 0),
            (int)($scores['mobile'] ?? 0),
            (int)($scores['seo'] ?? 0)
        );
        if ($issuesList !== []) {
            $summaryLines[] = 'Tespit Edilen Eksikler: ' . implode(' • ', array_slice($issuesList, 0, 6));
        }
    }
    if ($note !== '') {
        $summaryLines[] = 'Müşteri Notu: ' . mb_substr($note, 0, 1000, 'UTF-8');
    }
    $compiledMessage = implode("\n", $summaryLines) ?: 'Site Hızı & SEO Ön Analiz aracından 99+ performans iyileştirme talebi.';

    $lead = [
        'id' => 'msg-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
        'source' => 'site_audit',
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company' => $domain !== 'Belirtilmedi' ? $domain : '',
        'service' => 'Site Hızı & SEO Analiz (' . $domain . ')',
        'budget' => '99+ Performans & SEO Yol Haritası',
        'message' => $compiledMessage,
        'audit_data' => $verifiedAudit ? [
            'domain' => $domain,
            'url' => $auditedUrl,
            'overall_score' => $overallScore,
            'scores' => $scores,
            'ttfb_ms' => $ttfbMs,
            'html_kb' => (float)($verifiedAudit['html_kb'] ?? 0),
            'issues_count' => (int)($verifiedAudit['issues_count'] ?? count($issuesList)),
            'issues' => array_slice($issuesList, 0, 10),
        ] : null,
        'ip' => get_client_ip(),
        'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 220, 'UTF-8'),
        'read' => false,
        'status' => 'new',
        'created_at' => date('Y-m-d H:i:s'),
    ];

    if (!isset($storeFresh['messages']) || !is_array($storeFresh['messages'])) {
        $storeFresh['messages'] = [];
    }

    // Optional SMTP / Local Postfix notification
    $smtpCfg = is_array($storeFresh['smtp'] ?? null) ? $storeFresh['smtp'] : [];
    $notifyTo = resolve_smtp_recipient($storeFresh);
    if (!empty($smtpCfg['enabled']) && $notifyTo !== '') {
        $mailHtml = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px;border:1px solid #16353f">'
            . '<h2 style="margin:0 0 16px;color:#2dd4bf;font-size:20px">🔍 Yeni Site Hızı &amp; SEO Analiz Talebi — Zeynart</h2>'
            . '<p style="margin:8px 0"><strong>Ad Soyad:</strong> ' . e($name) . '</p>'
            . '<p style="margin:8px 0"><strong>Telefon / WhatsApp:</strong> ' . e($phone) . '</p>'
            . ($email !== '' ? '<p style="margin:8px 0"><strong>E-posta:</strong> ' . e($email) . '</p>' : '')
            . '<p style="margin:8px 0"><strong>Analiz Edilen Alan Adı:</strong> ' . e($domain) . ' (' . $overallScore . '/100)</p>'
            . '<hr style="border:0;border-top:1px solid #16353f;margin:16px 0"/>'
            . '<p style="margin:8px 0;line-height:1.65"><strong>Rapor Özeti &amp; Not:</strong><br>' . nl2br_e($compiledMessage) . '</p>'
            . '</div>';
        $mailRes = send_smtp_detailed($smtpCfg, $notifyTo, 'Site Analiz Lead (' . $domain . '): ' . $name, $mailHtml, [
            'allow_mail_fallback' => true,
            'reply_to_email' => $email,
            'reply_to_name' => $name,
        ]);
        $lead['mail_sent'] = !empty($mailRes['ok']);
        $storeFresh['smtp_last_status'] = [
            'ok' => !empty($mailRes['ok']),
            'at' => date('Y-m-d H:i:s'),
            'to' => $notifyTo,
            'method' => (string)($mailRes['method'] ?? 'smtp'),
            'error' => (string)($mailRes['error'] ?? ''),
        ];
    }

    array_unshift($storeFresh['messages'], $lead);
    save_store($storeFresh);

    $jsonOut(true, [
        'message' => t('Harika! Sitenizin analiz raporu ve iletişim bilgileriniz ekibimize ulaştı. Sitenizi 99+ performansa taşıyacak yol haritasıyla en kısa sürede dönüş yapacağız.'),
    ]);
}

$jsonOut(false, ['error' => t('Geçersiz istek yöntemi.')], 400);
