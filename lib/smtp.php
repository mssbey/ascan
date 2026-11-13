<?php
declare(strict_types=1);

/**
 * Resolve notification recipient email(s) safely without empty-string ?? pitfalls.
 */
function resolve_smtp_recipient(array $store): string
{
    $smtp = is_array($store['smtp'] ?? null) ? $store['smtp'] : [];
    $site = is_array($store['site'] ?? null) ? $store['site'] : [];

    $candidates = [
        trim((string)($smtp['notify_email'] ?? '')),
        trim((string)($site['email'] ?? '')),
        trim((string)($smtp['username'] ?? '')),
        trim((string)($smtp['from_email'] ?? '')),
    ];

    foreach ($candidates as $cand) {
        if ($cand === '') {
            continue;
        }
        $parts = preg_split('/[,;\s]+/', $cand, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $valid = [];
        foreach ($parts as $p) {
            $clean = trim($p);
            if (filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $clean;
            }
        }
        if ($valid !== []) {
            return implode(', ', array_values(array_unique($valid)));
        }
    }

    return '';
}

/**
 * Normalize & sanitize SMTP configuration array before saving or sending.
 */
function normalize_smtp_config(array $in, array $prev = []): array
{
    $merged = array_merge([
        'enabled' => false,
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_email' => '',
        'from_name' => 'Zeynart Web Bilişim',
        'notify_email' => '',
        'allow_self_signed' => true,
        'fallback_php_mail' => true,
    ], $prev, $in);

    $rawHost = trim((string)($merged['host'] ?? ''));
    $inferredEnc = null;
    if (preg_match('~^(ssl|smtps)://(.+)$~i', $rawHost, $hm)) {
        $inferredEnc = 'ssl';
        $rawHost = $hm[2];
    } elseif (preg_match('~^(tls|tcp|smtp|https?)://(.+)$~i', $rawHost, $hm)) {
        $rawHost = $hm[2];
    }
    $rawHost = trim(explode('/', $rawHost)[0]);

    $port = (int)($merged['port'] ?? 587);
    if (preg_match('/^([^:]+):(\d{2,5})$/', $rawHost, $pm)) {
        $rawHost = trim($pm[1]);
        $port = (int)$pm[2];
    }
    if ($port < 1 || $port > 65535) {
        $port = 587;
    }

    $enc = strtolower(trim((string)($merged['encryption'] ?? 'tls')));
    if ($inferredEnc !== null) {
        $enc = $inferredEnc;
    }
    if (!in_array($enc, ['tls', 'ssl', 'none', 'local'], true)) {
        $enc = ($port === 465) ? 'ssl' : 'tls';
    }

    return [
        'enabled' => !empty($merged['enabled']),
        'host' => $rawHost,
        'port' => $port,
        'encryption' => $enc,
        'username' => trim((string)($merged['username'] ?? '')),
        'password' => (string)($merged['password'] ?? ''),
        'from_email' => trim((string)($merged['from_email'] ?? '')),
        'from_name' => trim((string)($merged['from_name'] ?? '')) ?: 'Zeynart Web Bilişim',
        'notify_email' => trim((string)($merged['notify_email'] ?? '')),
        'allow_self_signed' => ($merged['allow_self_signed'] ?? true) !== false,
        'fallback_php_mail' => ($merged['fallback_php_mail'] ?? true) !== false,
    ];
}

/**
 * Build RFC 5322 multipart/alternative MIME payload (plain-text + HTML).
 *
 * @return array{headers_str: string, body_str: string, full_data: string}
 */
function build_smtp_mime_message(
    string $fromEmail,
    string $fromName,
    array $recipients,
    string $subject,
    string $htmlBody,
    string $ehloDomain,
    string $replyToEmail = '',
    string $replyToName = ''
): array {
    $cleanSubj = str_replace(["\r", "\n"], ' ', trim($subject));
    $cleanFromName = str_replace(["\r", "\n"], ' ', trim($fromName ?: 'Zeynart Web Bilişim'));
    $encodedSubj = '=?UTF-8?B?' . base64_encode($cleanSubj) . '?=';
    $encodedFrom = '=?UTF-8?B?' . base64_encode($cleanFromName) . '?= <' . $fromEmail . '>';
    $toHeader = implode(', ', array_map(static fn(string $r): string => '<' . $r . '>', $recipients));

    $msgDomain = $ehloDomain;
    if (str_contains($fromEmail, '@')) {
        $afterAt = trim((string)substr(strrchr($fromEmail, '@') ?: '', 1));
        if ($afterAt !== '') {
            $msgDomain = $afterAt;
        }
    }
    $messageId = sprintf('<%s.%s@%s>', gmdate('YmdHis'), bin2hex(random_bytes(6)), preg_replace('/[^a-zA-Z0-9.-]/', '', $msgDomain) ?: 'zeynart.com');
    $boundary = '=_ZeynartMime_' . bin2hex(random_bytes(10));

    $plainText = html_entity_decode(
        trim((string)preg_replace("/\n{3,}/", "\n\n", strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</h2>', '</h3>', '</li>'], "\n", $htmlBody)))),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    if ($plainText === '') {
        $plainText = $cleanSubj;
    }

    $headers = [
        'Date: ' . date('r'),
        'Message-ID: ' . $messageId,
        'From: ' . $encodedFrom,
        'To: ' . $toHeader,
    ];

    if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
        if ($replyToName !== '') {
            $cleanReplyName = str_replace(["\r", "\n"], ' ', trim($replyToName));
            $headers[] = 'Reply-To: =?UTF-8?B?' . base64_encode($cleanReplyName) . '?= <' . $replyToEmail . '>';
        } else {
            $headers[] = 'Reply-To: <' . $replyToEmail . '>';
        }
    }

    $headers[] = 'Subject: ' . $encodedSubj;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'X-Mailer: Zeynart-Studio-Mailer/2.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $parts = [
        '--' . $boundary,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        rtrim(chunk_split(base64_encode($plainText), 76, "\r\n"), "\r\n"),
        '--' . $boundary,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        rtrim(chunk_split(base64_encode($htmlBody), 76, "\r\n"), "\r\n"),
        '--' . $boundary . '--',
    ];

    $headersStr = implode("\r\n", $headers);
    $bodyStr = implode("\r\n", $parts);

    $rawData = $headersStr . "\r\n\r\n" . $bodyStr;
    // RFC 5321 Section 4.5.2 dot-stuffing
    $stuffed = (string)preg_replace('/^\./m', '..', $rawData);

    return [
        'headers_str' => $headersStr,
        'body_str' => $bodyStr,
        'full_data' => $stuffed,
    ];
}

/**
 * Send via local PHP mail() / Plesk Postfix sendmail binary.
 */
function send_via_local_php_mail(
    string $fromEmail,
    string $fromName,
    array $recipients,
    string $subject,
    string $htmlBody,
    string $ehloDomain,
    string $replyToEmail = '',
    string $replyToName = ''
): array {
    if (!function_exists('mail')) {
        return ['ok' => false, 'error' => 'Sunucuda PHP mail() fonksiyonu devre dışı.', 'method' => 'php_mail', 'server_reply' => '', 'transcript' => ['PHP mail() devre dışı.']];
    }

    $cleanSubj = str_replace(["\r", "\n"], ' ', trim($subject));
    $encodedSubj = '=?UTF-8?B?' . base64_encode($cleanSubj) . '?=';
    $mime = build_smtp_mime_message($fromEmail, $fromName, $recipients, $subject, $htmlBody, $ehloDomain, $replyToEmail, $replyToName);

    // Filter out To: and Subject: from additional_headers because PHP mail() adds them
    $hdrLines = explode("\r\n", $mime['headers_str']);
    $filteredHdrs = [];
    foreach ($hdrLines as $hl) {
        if (stripos($hl, 'To: ') === 0 || stripos($hl, 'Subject: ') === 0) {
            continue;
        }
        $filteredHdrs[] = $hl;
    }

    $toStr = implode(', ', $recipients);
    $addParams = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? ('-f' . $fromEmail) : '';
    $sent = $addParams !== ''
        ? @mail($toStr, $encodedSubj, $mime['body_str'], implode("\r\n", $filteredHdrs), $addParams)
        : @mail($toStr, $encodedSubj, $mime['body_str'], implode("\r\n", $filteredHdrs));

    if ($sent) {
        return [
            'ok' => true,
            'error' => '',
            'method' => 'php_mail',
            'server_reply' => 'Yerel Postfix / PHP mail() kuyruğuna iletildi.',
            'transcript' => ['[LOCAL] PHP mail() / Postfix sendmail ile gönderildi -> ' . $toStr],
        ];
    }

    return [
        'ok' => false,
        'error' => 'Yerel PHP mail() gönderimi başarısız oldu. Lütfen SMTP sunucu bilgilerini girin.',
        'method' => 'php_mail',
        'server_reply' => '',
        'transcript' => ['[LOCAL] PHP mail() false döndürdü.'],
    ];
}

/**
 * Single SMTP socket attempt with full TLS 1.2/1.3, STARTTLS, AUTH LOGIN/PLAIN & Sender auto-recovery.
 */
function execute_single_smtp_attempt(
    string $host,
    int $port,
    string $enc,
    bool $relaxSslVerify,
    string $user,
    string $pass,
    string $fromEmail,
    string $fromName,
    array $recipients,
    string $subject,
    string $htmlBody,
    string $ehloDomain,
    string $replyToEmail,
    string $replyToName,
    int $timeoutSec = 10
): array {
    $transcript = [];
    $sslOpts = [
        'SNI_enabled' => true,
        'peer_name' => $host,
        'verify_peer' => !$relaxSslVerify,
        'verify_peer_name' => !$relaxSslVerify,
        'allow_self_signed' => $relaxSslVerify,
    ];
    $ctx = stream_context_create(['ssl' => $sslOpts]);
    $transport = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $transcript[] = sprintf('-> Bağlanılıyor: %s (Şifreleme: %s, SSL Doğrulama: %s)', $transport, strtoupper($enc), $relaxSslVerify ? 'Esnek/Plesk' : 'Sıkı');

    $errno = 0;
    $errstr = '';
    $prevErr = null;
    set_error_handler(static function (int $no, string $msg) use (&$prevErr): bool {
        $prevErr = $msg;
        return true;
    });
    $fp = stream_socket_client($transport, $errno, $errstr, $timeoutSec, STREAM_CLIENT_CONNECT, $ctx);
    restore_error_handler();

    if (!is_resource($fp)) {
        $detail = trim($errstr ?: ($prevErr ?? ''));
        $isCertErr = stripos($detail, 'certificate') !== false || stripos($detail, 'ssl') !== false || stripos($detail, 'crypto') !== false;
        return [
            'ok' => false,
            'is_tls_error' => $isCertErr,
            'error' => sprintf('SMTP sunucusuna (%s:%d) bağlanılamadı: %s', $host, $port, $detail !== '' ? $detail : ('Hata kodu ' . $errno)),
            'method' => 'smtp',
            'server_reply' => '',
            'transcript' => $transcript,
        ];
    }

    stream_set_timeout($fp, $timeoutSec);

    $readResp = static function ($sock) use (&$transcript): string {
        $data = '';
        $maxLines = 60;
        $count = 0;
        while ($count < $maxLines && ($line = fgets($sock, 1024)) !== false) {
            $count++;
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
            $meta = stream_get_meta_data($sock);
            if (!empty($meta['timed_out'])) {
                break;
            }
        }
        $trimmed = trim($data);
        if ($trimmed !== '') {
            $transcript[] = '<- ' . str_replace(["\r\n", "\n"], ' | ', $trimmed);
        }
        return $data;
    };

    $sendCmd = static function ($sock, string $cmd, ?string $logLabel = null) use ($readResp, &$transcript): string {
        $transcript[] = '-> ' . ($logLabel ?? $cmd);
        @fwrite($sock, $cmd . "\r\n");
        return $readResp($sock);
    };

    $greet = $readResp($fp);
    if (!str_starts_with($greet, '220')) {
        fclose($fp);
        return [
            'ok' => false,
            'is_tls_error' => false,
            'error' => 'SMTP sunucusu geçerli bir karşılama (220) yanıtı vermedi: ' . trim($greet ?: 'Zaman aşımı (Port/Şifreleme uyumsuz olabilir)'),
            'method' => 'smtp',
            'server_reply' => trim($greet),
            'transcript' => $transcript,
        ];
    }

    $ehloResp = $sendCmd($fp, 'EHLO ' . $ehloDomain);
    if (!str_starts_with($ehloResp, '250')) {
        $ehloResp = $sendCmd($fp, 'HELO ' . $ehloDomain);
        if (!str_starts_with($ehloResp, '250')) {
            fclose($fp);
            return [
                'ok' => false,
                'is_tls_error' => false,
                'error' => 'SMTP EHLO/HELO el sıkışması reddedildi: ' . trim($ehloResp),
                'method' => 'smtp',
                'server_reply' => trim($ehloResp),
                'transcript' => $transcript,
            ];
        }
    }

    $supportsStartTls = stripos($ehloResp, 'STARTTLS') !== false;
    $wantStartTls = ($enc === 'tls') || ($enc === 'none' && $supportsStartTls && $user !== '' && !in_array(strtolower($host), ['localhost', '127.0.0.1'], true));

    if ($wantStartTls) {
        if (!$supportsStartTls && $enc === 'tls') {
            // Some servers don't list STARTTLS until requested, still attempt or proceed if local
            $tlsResp = $sendCmd($fp, 'STARTTLS');
        } else {
            $tlsResp = $sendCmd($fp, 'STARTTLS');
        }

        if (str_starts_with($tlsResp, '220')) {
            $cryptoMask = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMask |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            $tlsErr = '';
            set_error_handler(static function (int $no, string $msg) use (&$tlsErr): bool {
                $tlsErr = $msg;
                return true;
            });
            $cryptoOk = stream_socket_enable_crypto($fp, true, $cryptoMask);
            if ($cryptoOk !== true) {
                $cryptoOk = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
            }
            restore_error_handler();

            if ($cryptoOk !== true) {
                fclose($fp);
                return [
                    'ok' => false,
                    'is_tls_error' => true,
                    'error' => 'STARTTLS şifreli kanal başlatılamadı (TLS 1.2/1.3 / Sertifika uyuşmazlığı): ' . ($tlsErr ?: 'El sıkışma başarısız'),
                    'method' => 'smtp',
                    'server_reply' => $tlsErr,
                    'transcript' => $transcript,
                ];
            }
            $transcript[] = '[TLS] Şifreli bağlantı başarıyla kuruldu.';
            $ehloResp = $sendCmd($fp, 'EHLO ' . $ehloDomain);
        } elseif ($enc === 'tls' && !in_array(strtolower($host), ['localhost', '127.0.0.1'], true)) {
            fclose($fp);
            return [
                'ok' => false,
                'is_tls_error' => false,
                'error' => 'Sunucu STARTTLS komutunu kabul etmedi: ' . trim($tlsResp) . ' (Port 465 SSL/TLS veya Şifresiz deneyin)',
                'method' => 'smtp',
                'server_reply' => trim($tlsResp),
                'transcript' => $transcript,
            ];
        }
    }

    // Authenticate if credentials provided
    if ($user !== '' && $pass !== '') {
        $authenticated = false;
        $lastAuthResp = '';
        $preferPlain = (stripos($ehloResp, 'AUTH') !== false && stripos($ehloResp, 'PLAIN') !== false && stripos($ehloResp, 'LOGIN') === false);

        $tryAuthLogin = static function () use ($fp, $sendCmd, $user, $pass, &$lastAuthResp): bool {
            $r1 = $sendCmd($fp, 'AUTH LOGIN');
            if (!str_starts_with($r1, '334')) {
                $lastAuthResp = $r1;
                return false;
            }
            $r2 = $sendCmd($fp, base64_encode($user), '[KULLANICI ADI BASE64]');
            if (!str_starts_with($r2, '334')) {
                $lastAuthResp = $r2;
                return false;
            }
            $r3 = $sendCmd($fp, base64_encode($pass), '[PAROLA GİZLENDİ]');
            $lastAuthResp = $r3;
            return str_starts_with($r3, '235');
        };

        $tryAuthPlain = static function () use ($fp, $sendCmd, $user, $pass, &$lastAuthResp): bool {
            $token = base64_encode("\0" . $user . "\0" . $pass);
            $r1 = $sendCmd($fp, 'AUTH PLAIN ' . $token, 'AUTH PLAIN [KİMLİK GİZLENDİ]');
            if (str_starts_with($r1, '334')) {
                $r1 = $sendCmd($fp, $token, '[PLAIN TOKEN GİZLENDİ]');
            }
            $lastAuthResp = $r1;
            return str_starts_with($r1, '235');
        };

        if ($preferPlain) {
            $authenticated = $tryAuthPlain() || (!str_starts_with($lastAuthResp, '535') && $tryAuthLogin());
        } else {
            $authenticated = $tryAuthLogin() || (!str_starts_with($lastAuthResp, '535') && $tryAuthPlain());
        }

        if (!$authenticated) {
            fclose($fp);
            return [
                'ok' => false,
                'is_tls_error' => false,
                'error' => 'SMTP kimlik doğrulaması başarısız oldu (Kullanıcı adı veya parola hatalı): ' . trim($lastAuthResp),
                'method' => 'smtp',
                'server_reply' => trim($lastAuthResp),
                'transcript' => $transcript,
            ];
        }
    }

    // MAIL FROM (with smart Plesk/Gmail/Yandex sender-mismatch auto-recovery)
    $effectiveFrom = $fromEmail;
    $mailResp = $sendCmd($fp, 'MAIL FROM:<' . $effectiveFrom . '>');
    if (!str_starts_with($mailResp, '250') && filter_var($user, FILTER_VALIDATE_EMAIL) && strtolower($user) !== strtolower($effectiveFrom)) {
        $sendCmd($fp, 'RSET');
        if ($replyToEmail === '' && filter_var($effectiveFrom, FILTER_VALIDATE_EMAIL)) {
            $replyToEmail = $effectiveFrom;
            $replyToName = $fromName;
        }
        $effectiveFrom = $user;
        $mailResp = $sendCmd($fp, 'MAIL FROM:<' . $effectiveFrom . '>');
    }

    if (!str_starts_with($mailResp, '250')) {
        fclose($fp);
        return [
            'ok' => false,
            'is_tls_error' => false,
            'error' => 'Gönderen e-posta adresi (<' . $effectiveFrom . '>) sunucu tarafından reddedildi. Gönderen E-posta alanına SMTP kullanıcı adınızla aynı adresi yazın. Sunucu yanıtı: ' . trim($mailResp),
            'method' => 'smtp',
            'server_reply' => trim($mailResp),
            'transcript' => $transcript,
        ];
    }

    $acceptedRcpts = [];
    $lastRcptErr = '';
    foreach ($recipients as $rcpt) {
        $rcptResp = $sendCmd($fp, 'RCPT TO:<' . $rcpt . '>');
        if (str_starts_with($rcptResp, '250') || str_starts_with($rcptResp, '251')) {
            $acceptedRcpts[] = $rcpt;
        } else {
            $lastRcptErr = trim($rcptResp);
        }
    }

    if ($acceptedRcpts === []) {
        fclose($fp);
        return [
            'ok' => false,
            'is_tls_error' => false,
            'error' => 'Alıcı e-posta adresi sunucu tarafından kabul edilmedi: ' . $lastRcptErr,
            'method' => 'smtp',
            'server_reply' => $lastRcptErr,
            'transcript' => $transcript,
        ];
    }

    $dataGate = $sendCmd($fp, 'DATA');
    if (!str_starts_with($dataGate, '354')) {
        fclose($fp);
        return [
            'ok' => false,
            'is_tls_error' => false,
            'error' => 'SMTP DATA komutu kabul edilmedi: ' . trim($dataGate),
            'method' => 'smtp',
            'server_reply' => trim($dataGate),
            'transcript' => $transcript,
        ];
    }

    $mime = build_smtp_mime_message(
        $effectiveFrom,
        $fromName,
        $acceptedRcpts,
        $subject,
        $htmlBody,
        $ehloDomain,
        $replyToEmail,
        $replyToName
    );

    $dataResp = $sendCmd($fp, $mime['full_data'] . "\r\n.", '[MIME E-POSTA İÇERİĞİ GÖNDERİLDİ]');
    $sendCmd($fp, 'QUIT');
    fclose($fp);

    if (str_starts_with($dataResp, '250')) {
        return [
            'ok' => true,
            'is_tls_error' => false,
            'error' => '',
            'method' => 'smtp',
            'server_reply' => trim($dataResp),
            'transcript' => $transcript,
        ];
    }

    return [
        'ok' => false,
        'is_tls_error' => false,
        'error' => 'E-posta içeriği sunucu tarafından kuyruğa alınmadı: ' . trim($dataResp),
        'method' => 'smtp',
        'server_reply' => trim($dataResp),
        'transcript' => $transcript,
    ];
}

/**
 * Full diagnostic mail sender supporting SMTP (TLS 1.2/1.3, SSL 465, STARTTLS 587, Local 25) + optional PHP mail() fallback.
 */
function send_smtp_detailed(array $smtpCfg, string $toEmail, string $subject, string $htmlBody, array $options = []): array
{
    $cfg = normalize_smtp_config($smtpCfg);
    $forceSend = !empty($options['force']);
    $allowMailFallback = !empty($options['allow_mail_fallback']) && $cfg['fallback_php_mail'];

    if (!$forceSend && !$cfg['enabled']) {
        return ['ok' => false, 'error' => 'SMTP bildirimleri şu anda kapalı ("Yeni proje talebi geldiğinde e-posta bildirimi gönder" anahtarı pasif).', 'method' => 'none', 'server_reply' => '', 'transcript' => []];
    }

    // Parse & validate recipient(s)
    $rawParts = preg_split('/[,;\s]+/', trim($toEmail), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $recipients = [];
    foreach ($rawParts as $rp) {
        $clean = trim($rp);
        if (filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $clean;
        }
    }
    $recipients = array_values(array_unique($recipients));
    if ($recipients === []) {
        return ['ok' => false, 'error' => 'Geçerli bir alıcı e-posta adresi bulunamadı.', 'method' => 'none', 'server_reply' => '', 'transcript' => []];
    }

    $rawHttpHost = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? 'zeynart.com')));
    $ehloDomain = (string)preg_replace('/:\d+$/', '', $rawHttpHost);
    if ($ehloDomain === '' || $ehloDomain === 'localhost' || filter_var($ehloDomain, FILTER_VALIDATE_IP)) {
        $ehloDomain = 'zeynart.com';
    }

    $user = $cfg['username'];
    $pass = $cfg['password'];
    $fromEmail = $cfg['from_email'];
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = filter_var($user, FILTER_VALIDATE_EMAIL) ? $user : ('info@' . $ehloDomain);
    }
    $fromName = $cfg['from_name'] !== '' ? $cfg['from_name'] : 'Zeynart Web Bilişim';
    $replyToEmail = trim((string)($options['reply_to_email'] ?? ''));
    $replyToName = trim((string)($options['reply_to_name'] ?? ''));

    // Direct local PHP mail() / Postfix sendmail mode
    if ($cfg['encryption'] === 'local' || ($cfg['host'] === '' && $allowMailFallback)) {
        return send_via_local_php_mail($fromEmail, $fromName, $recipients, $subject, $htmlBody, $ehloDomain, $replyToEmail, $replyToName);
    }

    if ($cfg['host'] === '') {
        return ['ok' => false, 'error' => 'SMTP Sunucusu (Host) alanı boş. Örn: mail.zeynart.com veya localhost yazın ya da Şifreleme kısmından "Yerel Sunucu (PHP mail / Postfix)" seçin.', 'method' => 'smtp', 'server_reply' => '', 'transcript' => []];
    }

    $host = $cfg['host'];
    $port = $cfg['port'];
    $enc = $cfg['encryption'];

    // Build smart ordered connection attempts to auto-heal common port/encryption mismatches
    $plans = [];
    if ($port === 465 && $enc === 'tls') {
        $plans[] = [$port, 'ssl', false];
        if ($cfg['allow_self_signed']) {
            $plans[] = [$port, 'ssl', true];
        }
    } elseif ($port === 587 && $enc === 'ssl') {
        $plans[] = [$port, 'tls', false];
        if ($cfg['allow_self_signed']) {
            $plans[] = [$port, 'tls', true];
        }
        $plans[] = [465, 'ssl', $cfg['allow_self_signed']];
    } else {
        $plans[] = [$port, $enc, false];
        if ($enc !== 'none' && $cfg['allow_self_signed']) {
            $plans[] = [$port, $enc, true];
        }
        // Auto-fallback between 587 STARTTLS and 465 SSL if socket connection/greeting fails
        if ($port === 587 && $enc === 'tls') {
            $plans[] = [465, 'ssl', $cfg['allow_self_signed']];
        } elseif ($port === 465 && $enc === 'ssl') {
            $plans[] = [587, 'tls', $cfg['allow_self_signed']];
        }
    }

    $combinedTranscript = [];
    $lastResult = null;

    foreach ($plans as $idx => [$tryPort, $tryEnc, $relaxSsl]) {
        $res = execute_single_smtp_attempt(
            $host,
            $tryPort,
            $tryEnc,
            $relaxSsl,
            $user,
            $pass,
            $fromEmail,
            $fromName,
            $recipients,
            $subject,
            $htmlBody,
            $ehloDomain,
            $replyToEmail,
            $replyToName
        );
        $combinedTranscript = array_merge($combinedTranscript, $res['transcript']);
        if ($res['ok']) {
            $res['transcript'] = $combinedTranscript;
            return $res;
        }
        $lastResult = $res;

        // Do NOT retry alternate ports/SSL if we already connected & failed authentication (535) or recipient/sender policy!
        if (str_contains($res['server_reply'] ?? '', '535') || str_contains($res['server_reply'] ?? '', '550') || str_contains($res['server_reply'] ?? '', '553')) {
            break;
        }
        // If strict SSL failed and next plan is relaxed SSL on same port, retry immediately; if strict SSL succeeded past TLS, don't retry relaxed SSL
        if ($idx === 0 && !$relaxSsl && empty($res['is_tls_error']) && isset($plans[1]) && $plans[1][0] === $tryPort && $plans[1][1] === $tryEnc) {
            break;
        }
    }

    // Final safety net for live customer leads on Plesk: local Postfix sendmail fallback
    if ($allowMailFallback) {
        $fallbackRes = send_via_local_php_mail($fromEmail, $fromName, $recipients, $subject, $htmlBody, $ehloDomain, $replyToEmail, $replyToName);
        if ($fallbackRes['ok']) {
            $fallbackRes['transcript'] = array_merge($combinedTranscript, $fallbackRes['transcript']);
            return $fallbackRes;
        }
    }

    if ($lastResult !== null) {
        $lastResult['transcript'] = $combinedTranscript;
        return $lastResult;
    }

    return ['ok' => false, 'error' => 'Bilinmeyen SMTP gönderim hatası.', 'method' => 'smtp', 'server_reply' => '', 'transcript' => $combinedTranscript];
}

/**
 * Boolean wrapper preserved for full backward compatibility.
 */
function send_smtp_notification(array $smtpCfg, string $toEmail, string $subject, string $htmlBody, array $options = []): bool
{
    $res = send_smtp_detailed($smtpCfg, $toEmail, $subject, $htmlBody, $options);
    return !empty($res['ok']);
}
