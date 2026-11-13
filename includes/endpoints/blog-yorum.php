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

$respond = static function (bool $ok, string $message, int $code = 200, array $extra = []): never {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok, 'message' => t($message)], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    $respond(false, 'Geçersiz istek yöntemi.', 405);
}

$rawBody = (string)file_get_contents('php://input');
$jsonIn = $rawBody !== '' ? json_decode($rawBody, true) : null;
$in = is_array($jsonIn) ? array_merge($_POST, $jsonIn) : $_POST;

$lang = strtolower(trim((string)($in['lang'] ?? 'tr')));
if (in_array($lang, ['tr', 'en', 'de', 'ru'], true)) {
    zeynart_set_lang($lang);
}

// Honeypot protection
if (!empty($in['website'])) {
    $respond(true, 'Sorunuz ve yorumunuz başarıyla iletildi.');
}

if (!verify_csrf(isset($in['csrf_token']) ? (string)$in['csrf_token'] : null)) {
    $respond(false, 'Güvenlik doğrulaması süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.', 403);
}

if (!check_rate_limit('blog_comment', 5, 300)) {
    $respond(false, 'Çok fazla yorum gönderildi. Lütfen birkaç dakika sonra tekrar deneyin.', 429);
}

$store = load_store();
$cfg = zeynart_get_blog_comments_config($store);
if (empty($cfg['enabled'])) {
    $respond(false, 'Bu içerikte yorum ve soru-cevap alanı şu anda kapalıdır.', 403);
}

$postSlug = slugify((string)($in['post_slug'] ?? ''));
$postTitle = mb_substr(trim(strip_tags((string)($in['post_title'] ?? ''))), 0, 180, 'UTF-8');
$name = mb_substr(trim(strip_tags((string)($in['name'] ?? ''))), 0, 80, 'UTF-8');
$email = mb_substr(trim((string)($in['email'] ?? '')), 0, 160, 'UTF-8');
$comment = mb_substr(trim(strip_tags((string)($in['comment'] ?? ''))), 0, 1800, 'UTF-8');

if ($postSlug === '' || get_post($postSlug) === null) {
    $respond(false, 'İlgili makale bulunamadı.', 404);
}
if (mb_strlen($name, 'UTF-8') < 2) {
    $respond(false, 'Lütfen adınızı ve soyadınızı girin.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $respond(false, 'Lütfen geçerli bir e-posta adresi girin (yayınlanmaz).', 422);
}
if (mb_strlen($comment, 'UTF-8') < 8) {
    $respond(false, 'Lütfen en az 8 karakter uzunluğunda soru veya yorumunuzu yazın.', 422);
}
// Block obvious link spam (>1 URL in comment)
if (preg_match_all('~https?://~i', $comment) > 1) {
    $respond(false, 'Güvenlik politikası gereği yorumlarda çoklu dış bağlantıya izin verilmemektedir.', 422);
}

$status = !empty($cfg['require_approval']) ? 'pending' : 'approved';
$entry = [
    'id' => 'cmt-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 5),
    'post_slug' => $postSlug,
    'post_title' => $postTitle !== '' ? $postTitle : $postSlug,
    'name' => $name,
    'email' => $email,
    'comment' => $comment,
    'status' => $status,
    'admin_reply' => '',
    'replied_by' => '',
    'replied_at' => null,
    'ip_hash' => substr(hash('sha256', get_client_ip() . '|' . date('Y-m')), 0, 16),
    'created_at' => date('Y-m-d H:i:s'),
];

$allComments = zeynart_load_blog_comments();
array_unshift($allComments, $entry);
if (count($allComments) > 1500) {
    $allComments = array_slice($allComments, 0, 1500);
}
zeynart_save_blog_comments($allComments);

// Optional SMTP notification to studio team
if (!empty($store['smtp']['enabled'])) {
    try {
        require_once ROOT_DIR . '/lib/smtp.php';
        $notifyTo = resolve_smtp_recipient($store);
        if ($notifyTo !== '') {
            $mailHtml = '<div style="font-family:Arial,sans-serif;padding:22px;background:#071318;color:#eaf4f7;border-radius:14px">'
                . '<h3 style="margin:0 0 12px;color:#2dd4bf">💬 Yeni Blog Sorusu / Yorumu</h3>'
                . '<p><strong>Makale:</strong> ' . e($entry['post_title']) . '</p>'
                . '<p><strong>Gönderen:</strong> ' . e($name) . ' (' . e($email) . ')</p>'
                . '<p><strong>Durum:</strong> ' . ($status === 'pending' ? 'Editör Onayı Bekliyor' : 'Yayında') . '</p>'
                . '<hr style="border:0;border-top:1px solid #16353f;margin:14px 0"/>'
                . '<p style="line-height:1.6">' . nl2br_e($comment) . '</p></div>';
            @send_smtp_detailed($store['smtp'], $notifyTo, 'Yeni Makale Sorusu: ' . $name, $mailHtml, [
                'allow_mail_fallback' => true,
                'reply_to_email' => $email,
                'reply_to_name' => $name,
            ]);
        }
    } catch (\Throwable $_) {
    }
}

$msg = $status === 'pending'
    ? 'Sorunuz ve yorumunuz alındı. Editör incelemesinin ardından uzman yanıtıyla birlikte yayınlanacaktır.'
    : 'Yorumunuz başarıyla yayınlandı, katkınız için teşekkür ederiz.';

$respond(true, $msg, 200, ['status' => $status]);
