<?php
declare(strict_types=1);

/**
 * ZEYNART DİJİTAL STÜDYO v5.2 — MERKEZİ OTA (OVER-THE-AIR) GÜNCELLEME, ÇOKLU SEKTÖR KORUMA & /x/ KLASÖR EŞLEME MOTORU
 * --------------------------------------------------------------------------------------------------------------------
 * 1. Dağıtım & Lisans Hub Modu (zeynart.com):
 *    - Yalnızca saf Stüdyo Çekirdek dosyalarını paketler; müşteri vitrinini (pages/, site_header.php, site_footer.php,
 *      icons.php, i18n.php, site.css, site.js), sektör eklentilerini (includes/extensions/*, <ADMIN_DIR>/extensions/*,
 *      *-studio.js, sektörel PHP/endpoint dosyalarını), görselleri (uploads/, images/) ve verilerini (data/, SQL) ASLA ezmez.
 *    - OpenSSL RSA-2048 (SHA-256) asimetrik kriptografik imza + SHA-256 bütünlük özeti üretir.
 *    - Bağlı müşteri sitelerini (Filo), özel panel yollarını (/admin/ veya /x/) ve sektör modüllerini izler;
 *      uzaktan tek tıkla tekil veya toplu güncelleme (Remote Push) ve önbellek temizliği tetikler.
 * 2. Kendi Kendini Onaran & /x/ Klasör Eşleyen İstemci Modu (Müşteri Siteleri):
 *    - Paketi açmadan önce SHA-256 ve RSA-2048 açık anahtar imzasını doğrular.
 *    - Müşteri sitesinde yönetim klasörü fiziksel olarak /x/ veya /yonetim/ olarak adlandırılmışsa, imzalı paketteki
 *      admin/* çekirdek dosyalarını doğrulama sonrasında otomatik olarak /x/* klasörüne eşler (asla fazladan /admin/ açmaz).
 *    - Güncelleme öncesi otomatik geri dönüş arşivi (data/backups/pre-ota-*.zip, son 3 yedek) alır; hata anında Auto-Rollback yapar.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sector_extensions.php';

if (!defined('ZEYNART_STUDIO_VERSION')) {
    define('ZEYNART_STUDIO_VERSION', '5.2.0');
}
if (!defined('ZEYNART_STUDIO_CHANNEL')) {
    define('ZEYNART_STUDIO_CHANNEL', 'stable');
}

function zeynart_ota_resolve_target_admin_rel_dir(string $targetRoot = ROOT_DIR, ?array $clientState = null): string
{
    $reserved = [
        '.well-known' => true,
        'assets' => true,
        'data' => true,
        'images' => true,
        'includes' => true,
        'lib' => true,
        'pages' => true,
        'uploads' => true,
    ];
    $root = rtrim(str_replace('\\', '/', $targetRoot), '/');

    if (is_array($clientState) && !empty($clientState['admin_dir'])) {
        $explicit = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string)$clientState['admin_dir'])) ?? '';
        if ($explicit !== '' && !isset($reserved[strtolower($explicit)]) && is_dir($root . '/' . $explicit)) {
            return $explicit;
        }
    }

    $normMainRoot = rtrim(str_replace('\\', '/', ROOT_DIR), '/');
    if ($root === $normMainRoot && defined('ADMIN_DIR_NAME')) {
        return ADMIN_DIR_NAME;
    }

    if (function_exists('zeynart_detect_physical_admin_dir_name')) {
        return zeynart_detect_physical_admin_dir_name($root);
    }

    return 'admin';
}

function zeynart_ota_map_canonical_to_client_rel(string $canonicalRel, string $targetAdminDir): string
{
    $clean = ltrim(str_replace('\\', '/', trim($canonicalRel)), '/');
    $adm = trim(str_replace('\\', '/', $targetAdminDir), '/');
    if ($adm === '' || $adm === 'admin') {
        return $clean;
    }
    if ($clean === 'admin') {
        return $adm;
    }
    if (str_starts_with($clean, 'admin/')) {
        return $adm . '/' . substr($clean, 6);
    }
    return $clean;
}

function zeynart_ota_current_version(?string $targetRoot = null): string
{
    $normMainRoot = rtrim(str_replace('\\', '/', ROOT_DIR), '/');
    $root = $targetRoot !== null ? rtrim(str_replace('\\', '/', $targetRoot), '/') : $normMainRoot;
    $verOverrideFile = $root . '/data/.ota_installed_version';
    if (is_file($verOverrideFile)) {
        $v = trim((string)@file_get_contents($verOverrideFile));
        if ($v !== '' && preg_match('/^\d+\.\d+(?:\.\d+)?(?:-[a-zA-Z0-9.]+)?$/', $v)) {
            return $v;
        }
    }
    if ($root !== $normMainRoot) {
        $engFile = $root . '/includes/ota_hub_engine.php';
        if (is_file($engFile)) {
            $src = (string)@file_get_contents($engFile);
            if (preg_match("~define\\(\\s*'ZEYNART_STUDIO_VERSION'\\s*,\\s*'([^']+)'\\s*\\)~", $src, $m)) {
                return trim($m[1]);
            }
        }
        $admRel = zeynart_ota_resolve_target_admin_rel_dir($root);
        $admIdx = $root . '/' . $admRel . '/index.php';
        if (is_file($admIdx)) {
            $admSrc = (string)@file_get_contents($admIdx);
            if (preg_match("~['\"]?studioVersion['\"]?\\s*(?::|=>)\\s*['\"]([^'\"]+)['\"]~", $admSrc, $m2)) {
                $sv = trim($m2[1]);
                return substr_count($sv, '.') === 1 ? ($sv . '.0') : $sv;
            }
        }
    }
    return ZEYNART_STUDIO_VERSION;
}

/* ============================================================================
 * 1. STATE & LOG STORAGE (SQL store_kv + ATOMIC JSON FALLBACK)
 * ============================================================================ */

function zeynart_ota_releases_dir(string $baseRoot = ROOT_DIR): string
{
    $dir = rtrim(str_replace('\\', '/', $baseRoot), '/') . '/data/ota_releases';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Require all denied\n", LOCK_EX);
    }
    return $dir;
}

function zeynart_ota_hub_state_file(string $baseRoot = ROOT_DIR): string
{
    return rtrim(str_replace('\\', '/', $baseRoot), '/') . '/data/ota_hub_state.json';
}

function zeynart_ota_client_state_file(string $baseRoot = ROOT_DIR): string
{
    return rtrim(str_replace('\\', '/', $baseRoot), '/') . '/data/ota_client_state.json';
}

function zeynart_ota_logs_file(string $baseRoot = ROOT_DIR): string
{
    return rtrim(str_replace('\\', '/', $baseRoot), '/') . '/data/ota_logs.json';
}

function zeynart_ota_default_hub_state(): array
{
    return [
        'enabled' => true,
        'default_channel' => 'stable',
        'require_https' => true,
        'inline_push_fallback' => true,
        'retain_releases' => 5,
        'public_key_pem' => '',
        'private_key_pem' => '',
        'key_fingerprint' => '',
        'key_created_at' => '',
        'releases' => [],
        'nodes' => [],
        'updated_at' => '',
    ];
}

function zeynart_ota_default_client_state(): array
{
    return [
        'connected' => false,
        'hub_url' => 'https://zeynart.com',
        'node_id' => '',
        'license_key' => '',
        'node_secret' => '',
        'channel' => 'stable',
        'auto_update' => 'off', // 'off' | 'patch' | 'all'
        'allow_remote_push' => true,
        'admin_dir' => '',
        'hub_public_key_pem' => '',
        'key_fingerprint' => '',
        'protected_paths' => [],
        'installed_version' => ZEYNART_STUDIO_VERSION,
        'latest_remote_version' => '',
        'update_available' => false,
        'latest_manifest' => null,
        'last_checked_at' => '',
        'last_checked_ts' => 0,
        'last_updated_at' => '',
        'last_rollback_zip' => '',
        'last_error' => '',
    ];
}

function zeynart_ota_get_hub_state(bool $ensureKeys = false, string $baseRoot = ROOT_DIR): array
{
    $defaults = zeynart_ota_default_hub_state();
    $file = zeynart_ota_hub_state_file($baseRoot);
    $loaded = ($baseRoot === ROOT_DIR && function_exists('zeynart_read_json_data'))
        ? zeynart_read_json_data($file, [])
        : (is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : []);
    if (!is_array($loaded)) {
        $loaded = [];
    }
    $state = array_merge($defaults, $loaded);
    $state['enabled'] = !empty($state['enabled']);
    $state['default_channel'] = in_array($state['default_channel'] ?? 'stable', ['stable', 'beta'], true)
        ? (string)$state['default_channel']
        : 'stable';
    $state['require_https'] = ($state['require_https'] ?? true) !== false;
    $state['inline_push_fallback'] = ($state['inline_push_fallback'] ?? true) !== false;
    $state['retain_releases'] = max(2, min(10, (int)($state['retain_releases'] ?? 5)));
    $state['releases'] = is_array($state['releases'] ?? null) ? array_values($state['releases']) : [];
    $state['nodes'] = is_array($state['nodes'] ?? null) ? array_values($state['nodes']) : [];

    if ($ensureKeys && (empty($state['private_key_pem']) || empty($state['public_key_pem']))) {
        $kp = zeynart_ota_generate_rsa_keypair();
        if (!empty($kp['ok'])) {
            $state['private_key_pem'] = $kp['private_key_pem'];
            $state['public_key_pem'] = $kp['public_key_pem'];
            $state['key_fingerprint'] = $kp['fingerprint'];
            $state['key_created_at'] = date('c');
            zeynart_ota_save_hub_state($state, $baseRoot);
        }
    }
    return $state;
}

function zeynart_ota_save_hub_state(array $state, string $baseRoot = ROOT_DIR): bool
{
    $state['updated_at'] = date('c');
    $file = zeynart_ota_hub_state_file($baseRoot);
    if ($baseRoot === ROOT_DIR && function_exists('atomic_write_json')) {
        return atomic_write_json($file, $state, false);
    }
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return @file_put_contents($file, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function zeynart_ota_get_client_state(string $baseRoot = ROOT_DIR): array
{
    $defaults = zeynart_ota_default_client_state();
    $file = zeynart_ota_client_state_file($baseRoot);
    $loaded = ($baseRoot === ROOT_DIR && function_exists('zeynart_read_json_data'))
        ? zeynart_read_json_data($file, [])
        : (is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : []);
    if (!is_array($loaded)) {
        $loaded = [];
    }
    $state = array_merge($defaults, $loaded);
    $state['connected'] = !empty($state['connected']) && trim((string)($state['license_key'] ?? '')) !== '' && trim((string)($state['node_secret'] ?? '')) !== '';
    $state['channel'] = in_array($state['channel'] ?? 'stable', ['stable', 'beta'], true) ? (string)$state['channel'] : 'stable';
    $state['auto_update'] = in_array($state['auto_update'] ?? 'off', ['off', 'patch', 'all'], true) ? (string)$state['auto_update'] : 'off';
    $state['allow_remote_push'] = ($state['allow_remote_push'] ?? true) !== false;
    $state['admin_dir'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string)($state['admin_dir'] ?? ''))) ?? '';
    $state['detected_admin_dir'] = zeynart_ota_resolve_target_admin_rel_dir($baseRoot, $state);
    $state['protected_paths'] = is_array($state['protected_paths'] ?? null)
        ? array_values(array_filter(array_map(static fn($p) => trim(str_replace('\\', '/', (string)$p), '/'), $state['protected_paths'])))
        : [];
    $liveVer = zeynart_ota_current_version($baseRoot);
    if (!empty($state['installed_version']) && version_compare((string)$state['installed_version'], $liveVer, '>')) {
        // Keep tracked installed_version if higher (e.g. during CLI test before reload)
    } else {
        $state['installed_version'] = $liveVer;
    }
    $remoteVer = trim((string)($state['latest_remote_version'] ?? ''));
    $state['update_available'] = $remoteVer !== '' && version_compare($remoteVer, (string)$state['installed_version'], '>');
    return $state;
}

function zeynart_ota_save_client_state(array $state, string $baseRoot = ROOT_DIR): bool
{
    $file = zeynart_ota_client_state_file($baseRoot);
    if ($baseRoot === ROOT_DIR && function_exists('atomic_write_json')) {
        return atomic_write_json($file, $state, false);
    }
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return @file_put_contents($file, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function zeynart_ota_get_logs(int $limit = 80, string $baseRoot = ROOT_DIR): array
{
    $file = zeynart_ota_logs_file($baseRoot);
    $rows = ($baseRoot === ROOT_DIR && function_exists('zeynart_read_json_data'))
        ? zeynart_read_json_data($file, [])
        : (is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : []);
    if (!is_array($rows)) {
        return [];
    }
    return array_slice(array_values($rows), 0, max(1, $limit));
}

function zeynart_ota_append_log(string $event, string $message, string $level = 'ok', array $meta = [], string $baseRoot = ROOT_DIR): void
{
    $logs = zeynart_ota_get_logs(120, $baseRoot);
    array_unshift($logs, [
        'id' => 'otalog-' . bin2hex(random_bytes(5)),
        'event' => substr($event, 0, 64),
        'level' => in_array($level, ['ok', 'info', 'warn', 'error'], true) ? $level : 'info',
        'message' => mb_substr($message, 0, 500, 'UTF-8'),
        'meta' => $meta,
        'ip' => function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $logs = array_slice($logs, 0, 100);
    $file = zeynart_ota_logs_file($baseRoot);
    if ($baseRoot === ROOT_DIR && function_exists('atomic_write_json')) {
        atomic_write_json($file, $logs, false);
    } else {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($file, json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
}

/* ============================================================================
 * 2. CRYPTOGRAPHIC ENGINE (RSA-2048 SHA-256 PACKAGE SIGNING + HMAC-SHA256)
 * ============================================================================ */

function zeynart_ota_key_fingerprint(string $publicKeyPem): string
{
    $norm = preg_replace('/\s+/', '', trim($publicKeyPem)) ?? '';
    if ($norm === '') {
        return '';
    }
    return 'RSA2048:' . strtoupper(substr(hash('sha256', $norm), 0, 24));
}

function zeynart_ota_generate_rsa_keypair(): array
{
    if (!extension_loaded('openssl') || !function_exists('openssl_pkey_new')) {
        return ['ok' => false, 'error' => 'Sunucuda PHP OpenSSL eklentisi etkin değil.'];
    }

    $cfg = [
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
    $tmpCnf = '';
    $res = @openssl_pkey_new($cfg);
    if ($res === false) {
        $tmpCnf = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'zn_ossl_' . bin2hex(random_bytes(5)) . '.cnf';
        $minimalCnf = "HOME = .\n[req]\ndefault_bits = 2048\ndistinguished_name = req_distinguished_name\n[req_distinguished_name]\n";
        @file_put_contents($tmpCnf, $minimalCnf);
        $cfg['config'] = $tmpCnf;
        $res = @openssl_pkey_new($cfg);
    }

    if ($res === false) {
        if ($tmpCnf !== '' && is_file($tmpCnf)) {
            @unlink($tmpCnf);
        }
        return ['ok' => false, 'error' => 'RSA-2048 anahtar çifti oluşturulamadı.'];
    }

    $privPem = '';
    $expOpts = $tmpCnf !== '' ? ['config' => $tmpCnf] : [];
    $exported = @openssl_pkey_export($res, $privPem, null, $expOpts);
    $details = @openssl_pkey_get_details($res);
    if ($tmpCnf !== '' && is_file($tmpCnf)) {
        @unlink($tmpCnf);
    }

    $pubPem = is_array($details) ? (string)($details['key'] ?? '') : '';
    if (!$exported || $privPem === '' || $pubPem === '') {
        return ['ok' => false, 'error' => 'RSA-2048 anahtar dışa aktarımı başarısız oldu.'];
    }

    return [
        'ok' => true,
        'private_key_pem' => trim($privPem),
        'public_key_pem' => trim($pubPem),
        'fingerprint' => zeynart_ota_key_fingerprint($pubPem),
    ];
}

function zeynart_ota_canonical_package_signing_string(string $version, string $channel, string $sha256, int $sizeBytes): string
{
    return "ZEYNART-OTA-PKG-V1\n"
        . strtolower(trim($version)) . "\n"
        . strtolower(trim($channel)) . "\n"
        . strtolower(trim($sha256)) . "\n"
        . $sizeBytes;
}

function zeynart_ota_sign_package_digest(string $version, string $channel, string $sha256, int $sizeBytes, string $privateKeyPem): string
{
    $payload = zeynart_ota_canonical_package_signing_string($version, $channel, $sha256, $sizeBytes);
    $sigBin = '';
    if (!@openssl_sign($payload, $sigBin, $privateKeyPem, OPENSSL_ALGO_SHA256) || $sigBin === '') {
        return '';
    }
    return base64_encode($sigBin);
}

function zeynart_ota_verify_package_digest(
    string $version,
    string $channel,
    string $sha256,
    int $sizeBytes,
    string $signatureB64,
    string $publicKeyPem
): bool {
    if (trim($signatureB64) === '' || trim($publicKeyPem) === '') {
        return false;
    }
    $sigBin = base64_decode($signatureB64, true);
    if (!is_string($sigBin) || $sigBin === '') {
        return false;
    }
    $payload = zeynart_ota_canonical_package_signing_string($version, $channel, $sha256, $sizeBytes);
    return @openssl_verify($payload, $sigBin, $publicKeyPem, OPENSSL_ALGO_SHA256) === 1;
}

function zeynart_ota_build_auth_headers(string $op, string $licenseKey, string $nodeSecret, string $rawBody = ''): array
{
    $ts = (string)time();
    $nonce = bin2hex(random_bytes(12));
    $bodyHash = hash('sha256', $rawBody);
    $canon = $op . "\n" . $licenseKey . "\n" . $ts . "\n" . $nonce . "\n" . $bodyHash;
    $sig = hash_hmac('sha256', $canon, $nodeSecret);
    return [
        'X-Zeynart-License: ' . $licenseKey,
        'X-Zeynart-Timestamp: ' . $ts,
        'X-Zeynart-Nonce: ' . $nonce,
        'X-Zeynart-Signature: ' . $sig,
    ];
}

function zeynart_ota_verify_auth_request(string $op, string $expectedLicense, string $nodeSecret, string $rawBody = ''): array
{
    $lic = trim((string)($_SERVER['HTTP_X_ZEYNART_LICENSE'] ?? ''));
    $ts = (int)($_SERVER['HTTP_X_ZEYNART_TIMESTAMP'] ?? 0);
    $nonce = trim((string)($_SERVER['HTTP_X_ZEYNART_NONCE'] ?? ''));
    $sig = trim((string)($_SERVER['HTTP_X_ZEYNART_SIGNATURE'] ?? ''));

    if ($lic === '' || $ts <= 0 || $nonce === '' || $sig === '') {
        return ['ok' => false, 'error' => 'Eksik OTA kimlik doğrulama başlığı.'];
    }
    if ($expectedLicense !== '' && !hash_equals($expectedLicense, $lic)) {
        return ['ok' => false, 'error' => 'Lisans anahtarı eşleşmedi.'];
    }
    if (abs(time() - $ts) > 300) {
        return ['ok' => false, 'error' => 'İstek zaman damgası (Replay Koruması ±5 dk) geçersiz.'];
    }
    $canon = $op . "\n" . $lic . "\n" . $ts . "\n" . $nonce . "\n" . hash('sha256', $rawBody);
    $calc = hash_hmac('sha256', $canon, $nodeSecret);
    if (!hash_equals($calc, $sig)) {
        return ['ok' => false, 'error' => 'HMAC-SHA256 kriptografik imza doğrulaması başarısız.'];
    }
    return ['ok' => true, 'license_key' => $lic];
}

/* ============================================================================
 * 3. SSRF-SAFE HTTP TRANSPORT FOR HUB <-> CLIENT COMMUNICATION
 * ============================================================================ */

function zeynart_ota_validate_peer_url(string $rawUrl, bool $requireHttps = false): array
{
    $trimmed = rtrim(trim($rawUrl), '/');
    if ($trimmed === '') {
        return ['ok' => false, 'error' => 'Hedef site adresi boş olamaz.'];
    }
    if (!preg_match('~^https?://~i', $trimmed)) {
        $trimmed = 'https://' . $trimmed;
    }
    $parts = @parse_url($trimmed);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return ['ok' => false, 'error' => 'Geçersiz URL biçimi.'];
    }
    $scheme = strtolower((string)$parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['ok' => false, 'error' => 'Yalnızca HTTP/HTTPS protokolleri desteklenir.'];
    }
    $host = strtolower((string)$parts['host']);
    $allowLocal = defined('ZEYNART_OTA_ALLOW_LOCALHOST') && ZEYNART_OTA_ALLOW_LOCALHOST === true;

    if ($requireHttps && $scheme !== 'https' && !$allowLocal) {
        return ['ok' => false, 'error' => 'Üretim modunda OTA bağlantısı için HTTPS zorunludur.'];
    }

    if (!$allowLocal) {
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return ['ok' => false, 'error' => 'Yerel/iç ağ adreslerine dış OTA bağlantısı güvenlik kalkanı (SSRF) tarafından engellendi.'];
        }
        $resolvedIp = @gethostbyname($host);
        if (filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
            $isPublic = filter_var(
                $resolvedIp,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            if ($isPublic === false) {
                return ['ok' => false, 'error' => 'Özel/iç IP aralığına OTA isteği SSRF kalkanı tarafından engellendi.'];
            }
        }
    }

    $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
    $path = isset($parts['path']) ? rtrim((string)$parts['path'], '/') : '';
    return [
        'ok' => true,
        'url' => $scheme . '://' . $host . $port . $path,
        'host' => $host,
        'scheme' => $scheme,
    ];
}

function zeynart_ota_http_request(
    string $endpointUrl,
    string $method = 'POST',
    string $rawBody = '',
    array $headers = [],
    int $timeoutSec = 25
): array {
    $allHeaders = array_merge([
        'Accept: application/json, application/octet-stream',
        'User-Agent: ZeynartStudioOTA/' . ZEYNART_STUDIO_VERSION,
        'Cache-Control: no-cache',
        'Connection: close',
    ], $headers);

    if ($method === 'POST') {
        $hasCt = false;
        $hasCl = false;
        foreach ($allHeaders as $h) {
            if (stripos($h, 'Content-Type:') === 0) {
                $hasCt = true;
            }
            if (stripos($h, 'Content-Length:') === 0) {
                $hasCl = true;
            }
        }
        if (!$hasCt) {
            $allHeaders[] = 'Content-Type: application/json; charset=UTF-8';
        }
        if (!$hasCl) {
            $allHeaders[] = 'Content-Length: ' . strlen($rawBody);
        }
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($endpointUrl);
        if ($ch !== false) {
            $allowLocal = defined('ZEYNART_OTA_ALLOW_LOCALHOST') && ZEYNART_OTA_ALLOW_LOCALHOST === true;
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $allHeaders,
                CURLOPT_CONNECTTIMEOUT => min(10, $timeoutSec),
                CURLOPT_TIMEOUT => $timeoutSec,
                CURLOPT_SSL_VERIFYPEER => !$allowLocal,
                CURLOPT_SSL_VERIFYHOST => $allowLocal ? 0 : 2,
            ]);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
            }
            $respBody = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            if (is_string($respBody)) {
                return [
                    'ok' => $httpCode >= 200 && $httpCode < 300,
                    'http_code' => $httpCode,
                    'body' => $respBody,
                    'error' => ($httpCode >= 200 && $httpCode < 300) ? '' : ('HTTP ' . $httpCode),
                ];
            }
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'body' => '',
                'error' => $curlErr !== '' ? $curlErr : 'Uzak sunucuya bağlanılamadı.',
            ];
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $allHeaders),
            'content' => $rawBody,
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => !(defined('ZEYNART_OTA_ALLOW_LOCALHOST') && ZEYNART_OTA_ALLOW_LOCALHOST === true),
            'verify_peer_name' => !(defined('ZEYNART_OTA_ALLOW_LOCALHOST') && ZEYNART_OTA_ALLOW_LOCALHOST === true),
        ],
    ]);
    $fp = @fopen($endpointUrl, 'rb', false, $ctx);
    if (!is_resource($fp)) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => 'Uzak sunucu yanıt vermedi.'];
    }
    $meta = stream_get_meta_data($fp);
    $respBody = stream_get_contents($fp);
    fclose($fp);

    $httpCode = 0;
    $wrapperData = $meta['wrapper_data'] ?? [];
    if (is_array($wrapperData)) {
        foreach ($wrapperData as $hdrLine) {
            if (is_string($hdrLine) && preg_match('~^HTTP/\S+\s+(\d{3})~i', $hdrLine, $m)) {
                $httpCode = (int)$m[1];
            }
        }
    }
    if (!is_string($respBody)) {
        return ['ok' => false, 'http_code' => $httpCode, 'body' => '', 'error' => 'Uzak sunucu yanıtı okunamadı.'];
    }
    return [
        'ok' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'body' => $respBody,
        'error' => ($httpCode >= 200 && $httpCode < 300) ? '' : ('HTTP ' . $httpCode),
    ];
}

/* ============================================================================
 * 4. CORE FILE PROTECTION BOUNDARY & RELEASE PACKAGE BUILDER (HUB)
 * ============================================================================ */

/**
 * Explicit Studio Core Engine PHP files inside includes/.
 * Storefront layout & sector files (site_header.php, site_footer.php, icons.php, i18n.php,
 * includes/extensions/*, jewelry_commerce.php, real_estate_engine.php, etc.) are STRICTLY EXCLUDED.
 */
function zeynart_ota_core_includes_whitelist(): array
{
    return [
        'includes/agency_growth_suite.php' => true,
        'includes/agency_powerhouse.php' => true,
        'includes/analytics.php' => true,
        'includes/config.php' => true,
        'includes/cookie_consent.php' => true,
        'includes/db.php' => true,
        'includes/enterprise_cms_suite.php' => true,
        'includes/google_reviews.php' => true,
        'includes/mysql_engine.php' => true,
        'includes/news_autopilot.php' => true,
        'includes/ota_hub_engine.php' => true,
        'includes/page_builder.php' => true,
        'includes/qr_svg.php' => true,
        'includes/quick_contact.php' => true,
        'includes/sector_extensions.php' => true,
        'includes/security.php' => true,
        'includes/seo.php' => true,
        'includes/site_audit.php' => true,
        'includes/social_card.php' => true,
        'includes/studio_pro_pack.php' => true,
        'includes/studio_suite.php' => true,
        'includes/translator.php' => true,
        'includes/v4_studio_engine.php' => true,
    ];
}

/**
 * Explicit Studio Core endpoints inside includes/endpoints/.
 * Sector endpoints (shop-api.php, mulk-degerleme.php, portfoy-filtre.php, includes/extensions/endpoints/*)
 * are STRICTLY EXCLUDED so OTA updates never touch sector APIs.
 */
function zeynart_ota_core_endpoints_whitelist(): array
{
    return [
        'includes/endpoints/analitik-nabiz.php' => true,
        'includes/endpoints/arama.php' => true,
        'includes/endpoints/blog-yorum.php' => true,
        'includes/endpoints/gorusme-planla.php' => true,
        'includes/endpoints/haber-otopilot-cron.php' => true,
        'includes/endpoints/iletisim.php' => true,
        'includes/endpoints/ota-client.php' => true,
        'includes/endpoints/ota-hub.php' => true,
        'includes/endpoints/proje-takip-onay.php' => true,
        'includes/endpoints/site-analiz.php' => true,
        'includes/endpoints/teklif-onay.php' => true,
        'includes/endpoints/teklif-sihirbazi.php' => true,
        'includes/endpoints/uptime-cron.php' => true,
    ];
}

/**
 * Strictly validates that a canonical relative path belongs ONLY to the Studio Core
 * and NEVER touches client pages/, site_header.php, site_footer.php, icons.php, i18n.php,
 * extensions/, site.css, site.js, uploads/, images/, or data/.
 */
function zeynart_ota_is_allowed_core_target_path(string $relPath, array $clientProtectedPaths = [], string $targetAdminDir = 'admin'): bool
{
    $clean = ltrim(str_replace('\\', '/', trim($relPath)), '/');
    if ($clean === '' || str_contains($clean, '..') || str_contains($clean, "\0") || str_starts_with($clean, '.')) {
        return false;
    }

    $lowerClean = strtolower($clean);

    // Hard-block client design, pages, sector extensions, uploads, static images, root entrypoints, and runtime data forever
    $hardBlockedPrefixes = [
        'pages/',
        'data/',
        'uploads/',
        'images/',
        '.well-known/',
        'includes/extensions/',
        'admin/extensions/',
    ];
    foreach ($hardBlockedPrefixes as $bp) {
        if (str_starts_with($lowerClean, $bp)) {
            return false;
        }
    }
    $hardBlockedExact = [
        'index.php',
        '.htaccess',
        'icon.png',
        'favicon.ico',
        'includes/site_header.php',
        'includes/site_footer.php',
        'includes/icons.php',
        'includes/i18n.php',
        'assets/css/site.css',
        'assets/css/site.min.css',
        'assets/js/site.js',
        'assets/js/site.min.js',
    ];
    if (in_array($lowerClean, $hardBlockedExact, true)) {
        return false;
    }

    // Honor custom client-defined protected paths (checked against both canonical 'admin/*' and mapped '<x>/*')
    $mappedLower = strtolower(zeynart_ota_map_canonical_to_client_rel($clean, $targetAdminDir));
    foreach ($clientProtectedPaths as $prot) {
        $pClean = trim(strtolower(str_replace('\\', '/', (string)$prot)), '/');
        if ($pClean === '') {
            continue;
        }
        if (
            $lowerClean === $pClean
            || str_starts_with($lowerClean, $pClean . '/')
            || $mappedLower === $pClean
            || str_starts_with($mappedLower, $pClean . '/')
        ) {
            return false;
        }
    }

    // Strict whitelist of allowed Studio Core files
    if (preg_match('~^admin/(?:index\.php|api\.php|admin\.(?:min\.)?css|admin\.(?:min\.)?js)$~', $clean)) {
        return true;
    }
    if (preg_match('~^admin/chunks/[a-zA-Z0-9_\-]+(?:\.min)?\.js$~', $clean)) {
        return true;
    }
    $coreInc = zeynart_ota_core_includes_whitelist();
    if (isset($coreInc[$clean])) {
        return true;
    }
    if (preg_match('~^includes/core/[a-zA-Z0-9_\-]+\.php$~', $clean)) {
        return true;
    }
    if (preg_match('~^includes/(?:v\d+_|studio_)[a-zA-Z0-9_\-]+\.php$~', $clean)) {
        return true;
    }
    $coreEp = zeynart_ota_core_endpoints_whitelist();
    if (isset($coreEp[$clean])) {
        return true;
    }
    if (preg_match('~^includes/endpoints/studio-[a-zA-Z0-9_\-]+\.php$~', $clean)) {
        return true;
    }
    if ($clean === 'lib/smtp.php') {
        return true;
    }
    if (preg_match('~^assets/(?:css|js)/live-studio(?:\.min)?\.(?:css|js)$~', $clean)) {
        return true;
    }
    if (preg_match('~^assets/fonts/studio-sans-(?:bold|medium)\.ttf$~', $clean)) {
        return true;
    }

    return false;
}

/**
 * Collects all Studio Core files from $sourceRoot keyed by canonical POSIX path ('admin/...', 'includes/...'),
 * seamlessly resolving physical custom admin folders (e.g. '/x/') when snapshotting a client root.
 *
 * @return array<string, string> Map of canonical relative POSIX path => absolute file path on disk
 */
function zeynart_ota_collect_core_files(string $sourceRoot = ROOT_DIR, ?string $explicitAdminDir = null): array
{
    $root = rtrim(str_replace('\\', '/', $sourceRoot), '/');
    $admDir = $explicitAdminDir !== null && $explicitAdminDir !== ''
        ? $explicitAdminDir
        : zeynart_ota_resolve_target_admin_rel_dir($root);

    $candidates = [
        'admin/index.php',
        'admin/api.php',
        'admin/admin.css',
        'admin/admin.min.css',
        'admin/admin.js',
        'admin/admin.min.js',
        'assets/css/live-studio.css',
        'assets/css/live-studio.min.css',
        'assets/js/live-studio.js',
        'assets/js/live-studio.min.js',
        'assets/fonts/studio-sans-bold.ttf',
        'assets/fonts/studio-sans-medium.ttf',
        'lib/smtp.php',
    ];

    foreach (glob($root . '/' . $admDir . '/chunks/*.js') ?: [] as $f) {
        $candidates[] = 'admin/chunks/' . basename($f);
    }
    foreach (array_keys(zeynart_ota_core_includes_whitelist()) as $incRel) {
        $candidates[] = $incRel;
    }
    foreach (glob($root . '/includes/core/*.php') ?: [] as $f) {
        $candidates[] = 'includes/core/' . basename($f);
    }
    foreach (glob($root . '/includes/v*_*.php') ?: [] as $f) {
        $bn = basename($f);
        if (preg_match('/^v\d+_[a-zA-Z0-9_\-]+\.php$/', $bn)) {
            $candidates[] = 'includes/' . $bn;
        }
    }
    foreach (glob($root . '/includes/studio_*.php') ?: [] as $f) {
        $candidates[] = 'includes/' . basename($f);
    }
    foreach (array_keys(zeynart_ota_core_endpoints_whitelist()) as $epRel) {
        $candidates[] = $epRel;
    }
    foreach (glob($root . '/includes/endpoints/studio-*.php') ?: [] as $f) {
        $candidates[] = 'includes/endpoints/' . basename($f);
    }

    sort($candidates);
    $map = [];
    foreach (array_unique($candidates) as $canonicalRel) {
        if (!zeynart_ota_is_allowed_core_target_path($canonicalRel)) {
            continue;
        }
        $diskRel = zeynart_ota_map_canonical_to_client_rel($canonicalRel, $admDir);
        $abs = $root . '/' . $diskRel;
        if (is_file($abs)) {
            $map[$canonicalRel] = $abs;
        }
    }
    return $map;
}

function zeynart_ota_build_release_package(array $opts = [], string $sourceRoot = ROOT_DIR): array
{
    require_once __DIR__ . '/enterprise_cms_suite.php';

    $hub = zeynart_ota_get_hub_state(true, $sourceRoot);
    if (empty($hub['private_key_pem']) || empty($hub['public_key_pem'])) {
        return ['ok' => false, 'error' => 'Hub RSA-2048 imzalama anahtarı hazır değil.'];
    }

    $version = trim((string)($opts['version'] ?? ZEYNART_STUDIO_VERSION));
    if (!preg_match('/^\d+\.\d+(?:\.\d+)?(?:-[a-zA-Z0-9.]+)?$/', $version)) {
        return ['ok' => false, 'error' => 'Geçersiz sürüm numarası biçimi (Örn: 5.1.0).'];
    }
    $channel = in_array($opts['channel'] ?? 'stable', ['stable', 'beta'], true) ? (string)$opts['channel'] : 'stable';
    $minPhp = trim((string)($opts['min_php'] ?? '8.1.0')) ?: '8.1.0';
    $title = mb_substr(trim((string)($opts['title'] ?? ('Zeynart Dijital Stüdyo v' . $version . ' Çekirdek Paketi'))), 0, 160, 'UTF-8');

    $rawNotes = $opts['changelog'] ?? [];
    $changelog = [];
    if (is_string($rawNotes)) {
        foreach (preg_split('/\r?\n/', $rawNotes) ?: [] as $line) {
            $cleanLine = trim(ltrim(trim($line), '-•* '));
            if ($cleanLine !== '') {
                $changelog[] = mb_substr($cleanLine, 0, 240, 'UTF-8');
            }
        }
    } elseif (is_array($rawNotes)) {
        foreach ($rawNotes as $line) {
            $cleanLine = trim((string)$line);
            if ($cleanLine !== '') {
                $changelog[] = mb_substr($cleanLine, 0, 240, 'UTF-8');
            }
        }
    }
    if ($changelog === []) {
        $changelog = [
            'Yönetim Stüdyosu çekirdek modülleri, performans paketleri ve güvenlik kalkanı güncellendi.',
        ];
    }

    $coreFiles = zeynart_ota_collect_core_files($sourceRoot);
    if (count($coreFiles) < 10) {
        return ['ok' => false, 'error' => 'Çekirdek dosya listesi eksik görünüyor (' . count($coreFiles) . ' dosya).'];
    }

    $manifestFiles = [];
    $zipEntries = [];
    $uncompressedBytes = 0;

    foreach ($coreFiles as $rel => $abs) {
        $sha = @hash_file('sha256', $abs);
        $sz = (int)@filesize($abs);
        if (!is_string($sha) || $sha === '') {
            return ['ok' => false, 'error' => 'Dosya özeti hesaplanamadı: ' . $rel];
        }
        $manifestFiles[$rel] = [
            'sha256' => $sha,
            'size' => $sz,
        ];
        $zipEntries[$rel] = ['path' => $abs];
        $uncompressedBytes += $sz;
    }

    $builtAt = date('c');
    $manifest = [
        'format' => 'zeynart-ota-v1',
        'version' => $version,
        'channel' => $channel,
        'title' => $title,
        'changelog' => $changelog,
        'min_php' => $minPhp,
        'built_at' => $builtAt,
        'key_fingerprint' => (string)$hub['key_fingerprint'],
        'files_count' => count($manifestFiles),
        'uncompressed_bytes' => $uncompressedBytes,
        'files' => $manifestFiles,
    ];

    $zipEntries['ota-manifest.json'] = (string)json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    $relDir = zeynart_ota_releases_dir($sourceRoot);
    $safeVerSlug = preg_replace('/[^a-zA-Z0-9._\-]/', '-', $version) ?? $version;
    $fileName = 'zeynart-ota-core-v' . $safeVerSlug . '-' . $channel . '.zip';
    $destZip = $relDir . '/' . $fileName;

    if (!zeynart_pure_zip_write($destZip, $zipEntries) || !is_file($destZip)) {
        return ['ok' => false, 'error' => 'OTA çekirdek ZIP paketi diske yazılamadı.'];
    }

    $zipSha256 = (string)@hash_file('sha256', $destZip);
    $zipSize = (int)@filesize($destZip);
    $signatureB64 = zeynart_ota_sign_package_digest($version, $channel, $zipSha256, $zipSize, (string)$hub['private_key_pem']);
    if ($signatureB64 === '') {
        @unlink($destZip);
        return ['ok' => false, 'error' => 'OTA paketi RSA-2048 özel anahtarıyla imzalanamadı.'];
    }

    $releaseRecord = [
        'id' => 'rel-' . str_replace('.', '_', $safeVerSlug) . '-' . substr($zipSha256, 0, 8),
        'version' => $version,
        'channel' => $channel,
        'title' => $title,
        'changelog' => $changelog,
        'min_php' => $minPhp,
        'filename' => $fileName,
        'size_bytes' => $zipSize,
        'size_kb' => round($zipSize / 1024, 1),
        'files_count' => count($manifestFiles),
        'sha256' => $zipSha256,
        'signature' => $signatureB64,
        'key_fingerprint' => (string)$hub['key_fingerprint'],
        'created_at' => date('Y-m-d H:i:s'),
        'built_iso' => $builtAt,
    ];

    // Replace existing release with exact same version+channel or prepend
    $filtered = [];
    foreach ($hub['releases'] as $r) {
        if (($r['version'] ?? '') === $version && ($r['channel'] ?? 'stable') === $channel) {
            if (!empty($r['filename']) && $r['filename'] !== $fileName && is_file($relDir . '/' . basename((string)$r['filename']))) {
                @unlink($relDir . '/' . basename((string)$r['filename']));
            }
            continue;
        }
        $filtered[] = $r;
    }
    array_unshift($filtered, $releaseRecord);

    $retain = max(2, min(10, (int)($hub['retain_releases'] ?? 5)));
    if (count($filtered) > $retain) {
        $dropped = array_slice($filtered, $retain);
        foreach ($dropped as $dr) {
            if (!empty($dr['filename'])) {
                $oldF = $relDir . '/' . basename((string)$dr['filename']);
                if (is_file($oldF)) {
                    @unlink($oldF);
                }
            }
        }
        $filtered = array_slice($filtered, 0, $retain);
    }

    $hub['releases'] = array_values($filtered);
    zeynart_ota_save_hub_state($hub, $sourceRoot);
    zeynart_ota_append_log(
        'release_built',
        sprintf('v%s (%s) çekirdek güncelleme paketi oluşturuldu ve RSA-2048 ile imzalandı (%d dosya, %.1f KB).', $version, $channel, count($manifestFiles), $releaseRecord['size_kb']),
        'ok',
        ['version' => $version, 'sha256' => substr($zipSha256, 0, 16)],
        $sourceRoot
    );

    return [
        'ok' => true,
        'release' => $releaseRecord,
    ];
}

function zeynart_ota_find_latest_release(array $hubState, string $channel = 'stable', string $baseRoot = ROOT_DIR): ?array
{
    $relDir = zeynart_ota_releases_dir($baseRoot);
    $best = null;
    foreach (($hubState['releases'] ?? []) as $rel) {
        if (!is_array($rel) || empty($rel['version']) || empty($rel['filename'])) {
            continue;
        }
        $relChan = (string)($rel['channel'] ?? 'stable');
        if ($channel === 'stable' && $relChan !== 'stable') {
            continue;
        }
        if (!is_file($relDir . '/' . basename((string)$rel['filename']))) {
            continue;
        }
        if ($best === null || version_compare((string)$rel['version'], (string)$best['version'], '>')) {
            $best = $rel;
        }
    }
    return $best;
}

/* ============================================================================
 * 5. FLEET NODE MANAGEMENT, CONNECTION TOKEN & REMOTE PUSH (HUB)
 * ============================================================================ */

function zeynart_ota_base64url_encode(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function zeynart_ota_base64url_decode(string $str): string
{
    $pad = strlen($str) % 4;
    if ($pad > 0) {
        $str .= str_repeat('=', 4 - $pad);
    }
    $dec = base64_decode(strtr($str, '-_', '+/'), true);
    return is_string($dec) ? $dec : '';
}

function zeynart_ota_save_node(array $input, string $hubBaseUrl = SITE_URL, string $baseRoot = ROOT_DIR): array
{
    $hub = zeynart_ota_get_hub_state(true, $baseRoot);
    $urlVal = zeynart_ota_validate_peer_url((string)($input['site_url'] ?? ''), false);
    if (empty($urlVal['ok'])) {
        return ['ok' => false, 'error' => (string)$urlVal['error']];
    }

    $id = trim((string)($input['id'] ?? ''));
    $existingIdx = null;
    foreach ($hub['nodes'] as $idx => $n) {
        if (($id !== '' && ($n['id'] ?? '') === $id) || strcasecmp((string)($n['site_url'] ?? ''), $urlVal['url']) === 0) {
            $existingIdx = $idx;
            break;
        }
    }

    $prev = $existingIdx !== null ? $hub['nodes'][$existingIdx] : [];
    $nodeId = (string)($prev['id'] ?? ('node-' . bin2hex(random_bytes(6))));
    $licenseKey = (string)($prev['license_key'] ?? (
        'ZYN5-' . strtoupper(bin2hex(random_bytes(3)))
        . '-' . strtoupper(bin2hex(random_bytes(3)))
        . '-' . strtoupper(bin2hex(random_bytes(3)))
    ));
    $nodeSecret = !empty($input['rotate_secret']) || empty($prev['node_secret'])
        ? bin2hex(random_bytes(32))
        : (string)$prev['node_secret'];

    $clientName = mb_substr(trim((string)($input['client_name'] ?? ($prev['client_name'] ?? $urlVal['host']))), 0, 120, 'UTF-8');
    $channel = in_array($input['channel'] ?? ($prev['channel'] ?? 'stable'), ['stable', 'beta'], true)
        ? (string)($input['channel'] ?? ($prev['channel'] ?? 'stable'))
        : 'stable';
    $status = in_array($input['license_status'] ?? ($prev['license_status'] ?? 'active'), ['active', 'suspended'], true)
        ? (string)($input['license_status'] ?? ($prev['license_status'] ?? 'active'))
        : 'active';

    $node = [
        'id' => $nodeId,
        'client_name' => $clientName !== '' ? $clientName : $urlVal['host'],
        'site_url' => $urlVal['url'],
        'domain' => $urlVal['host'],
        'channel' => $channel,
        'license_status' => $status,
        'license_key' => $licenseKey,
        'node_secret' => $nodeSecret,
        'notes' => mb_substr(trim((string)($input['notes'] ?? ($prev['notes'] ?? ''))), 0, 260, 'UTF-8'),
        'installed_version' => (string)($prev['installed_version'] ?? '—'),
        'php_version' => (string)($prev['php_version'] ?? ''),
        'db_engine' => (string)($prev['db_engine'] ?? ''),
        'admin_dir' => (string)($prev['admin_dir'] ?? 'admin'),
        'admin_slug' => (string)($prev['admin_slug'] ?? 'admin'),
        'sector_summary' => (string)($prev['sector_summary'] ?? ''),
        'health_status' => (string)($prev['health_status'] ?? 'pending'),
        'last_seen_at' => (string)($prev['last_seen_at'] ?? ''),
        'last_push_at' => (string)($prev['last_push_at'] ?? ''),
        'last_push_result' => (string)($prev['last_push_result'] ?? ''),
        'created_at' => (string)($prev['created_at'] ?? date('Y-m-d H:i:s')),
    ];

    if ($existingIdx !== null) {
        $hub['nodes'][$existingIdx] = $node;
    } else {
        array_unshift($hub['nodes'], $node);
    }

    zeynart_ota_save_hub_state($hub, $baseRoot);
    $connToken = zeynart_ota_build_connection_token($node, $hub, $hubBaseUrl);

    return [
        'ok' => true,
        'node' => $node,
        'connection_token' => $connToken,
    ];
}

function zeynart_ota_build_connection_token(array $node, array $hubState, string $hubBaseUrl = SITE_URL): string
{
    $payload = [
        'v' => 1,
        'hub_url' => rtrim($hubBaseUrl, '/'),
        'site_url' => (string)($node['site_url'] ?? ''),
        'node_id' => (string)($node['id'] ?? ''),
        'license_key' => (string)($node['license_key'] ?? ''),
        'node_secret' => (string)($node['node_secret'] ?? ''),
        'channel' => (string)($node['channel'] ?? 'stable'),
        'public_key' => (string)($hubState['public_key_pem'] ?? ''),
        'key_fp' => (string)($hubState['key_fingerprint'] ?? ''),
    ];
    return 'ZYN5-OTA1:' . zeynart_ota_base64url_encode((string)json_encode($payload, JSON_UNESCAPED_SLASHES));
}

function zeynart_ota_parse_connection_token(string $rawToken): array
{
    $trim = trim($rawToken);
    if (!str_starts_with($trim, 'ZYN5-OTA1:')) {
        return ['ok' => false, 'error' => 'Geçersiz Bağlantı Kodu biçimi ("ZYN5-OTA1:" ile başlamalıdır).'];
    }
    $b64 = substr($trim, strlen('ZYN5-OTA1:'));
    $json = zeynart_ota_base64url_decode($b64);
    $dec = json_decode($json, true);
    if (!is_array($dec) || empty($dec['hub_url']) || empty($dec['license_key']) || empty($dec['node_secret']) || empty($dec['public_key'])) {
        return ['ok' => false, 'error' => 'Bağlantı Kodu çözümlenemedi veya eksik anahtar içeriyor.'];
    }
    $urlVal = zeynart_ota_validate_peer_url((string)$dec['hub_url'], false);
    if (empty($urlVal['ok'])) {
        return ['ok' => false, 'error' => 'Hub URL geçersiz: ' . $urlVal['error']];
    }
    return [
        'ok' => true,
        'hub_url' => $urlVal['url'],
        'node_id' => trim((string)($dec['node_id'] ?? '')),
        'license_key' => trim((string)$dec['license_key']),
        'node_secret' => trim((string)$dec['node_secret']),
        'channel' => in_array($dec['channel'] ?? 'stable', ['stable', 'beta'], true) ? (string)$dec['channel'] : 'stable',
        'public_key_pem' => trim((string)$dec['public_key']),
        'key_fingerprint' => zeynart_ota_key_fingerprint((string)$dec['public_key']),
    ];
}

function zeynart_ota_find_node_by_license_or_id(array $hubState, string $needle): ?array
{
    $n = trim($needle);
    if ($n === '') {
        return null;
    }
    foreach (($hubState['nodes'] ?? []) as $idx => $node) {
        if (
            hash_equals((string)($node['license_key'] ?? ''), $n)
            || ($node['id'] ?? '') === $n
        ) {
            return ['index' => $idx, 'node' => $node];
        }
    }
    return null;
}

function zeynart_ota_ping_node(string $nodeId, string $baseRoot = ROOT_DIR): array
{
    $hub = zeynart_ota_get_hub_state(true, $baseRoot);
    $found = zeynart_ota_find_node_by_license_or_id($hub, $nodeId);
    if ($found === null) {
        return ['ok' => false, 'error' => 'Müşteri sitesi kaydı bulunamadı.'];
    }
    $node = $found['node'];
    $idx = $found['index'];

    $endpoint = rtrim((string)$node['site_url'], '/') . '/api/ota-client?op=ping';
    $body = (string)json_encode(['op' => 'ping', 'hub_version' => ZEYNART_STUDIO_VERSION], JSON_UNESCAPED_SLASHES);
    $headers = zeynart_ota_build_auth_headers('ping', (string)$node['license_key'], (string)$node['node_secret'], $body);
    $resp = zeynart_ota_http_request($endpoint, 'POST', $body, $headers, 12);

    $dec = json_decode((string)($resp['body'] ?? ''), true);
    if (!empty($resp['ok']) && is_array($dec) && !empty($dec['ok'])) {
        $hub['nodes'][$idx]['installed_version'] = (string)($dec['installed_version'] ?? $hub['nodes'][$idx]['installed_version']);
        $hub['nodes'][$idx]['php_version'] = (string)($dec['php_version'] ?? '');
        $hub['nodes'][$idx]['db_engine'] = (string)($dec['db_engine'] ?? '');
        if (!empty($dec['admin_dir'])) {
            $hub['nodes'][$idx]['admin_dir'] = mb_substr(trim((string)$dec['admin_dir']), 0, 48, 'UTF-8');
        }
        if (!empty($dec['admin_slug'])) {
            $hub['nodes'][$idx]['admin_slug'] = mb_substr(trim((string)$dec['admin_slug']), 0, 48, 'UTF-8');
        }
        if (isset($dec['sector_extensions']) && is_array($dec['sector_extensions'])) {
            $hub['nodes'][$idx]['sector_extensions'] = array_slice($dec['sector_extensions'], 0, 12);
            $secNames = array_values(array_filter(array_map(static fn($e) => trim((string)($e['title'] ?? '')), $dec['sector_extensions'])));
            $hub['nodes'][$idx]['sector_summary'] = $secNames !== [] ? implode(', ', $secNames) : '';
        }
        $hub['nodes'][$idx]['health_status'] = 'online';
        $hub['nodes'][$idx]['last_seen_at'] = date('Y-m-d H:i:s');
        zeynart_ota_save_hub_state($hub, $baseRoot);
        return [
            'ok' => true,
            'node' => zeynart_ota_mask_node_for_ui($hub['nodes'][$idx], $hub),
            'remote' => $dec,
        ];
    }

    $hub['nodes'][$idx]['health_status'] = 'unreachable';
    zeynart_ota_save_hub_state($hub, $baseRoot);
    $errMsg = is_array($dec) && !empty($dec['error']) ? (string)$dec['error'] : ((string)($resp['error'] ?? 'Bağlantı kurulamadı'));
    return ['ok' => false, 'error' => $errMsg];
}

function zeynart_ota_remote_purge_node_cache(string $nodeId, string $baseRoot = ROOT_DIR): array
{
    $hub = zeynart_ota_get_hub_state(true, $baseRoot);
    $found = zeynart_ota_find_node_by_license_or_id($hub, $nodeId);
    if ($found === null) {
        return ['ok' => false, 'error' => 'Müşteri sitesi kaydı bulunamadı.'];
    }
    $node = $found['node'];
    $endpoint = rtrim((string)$node['site_url'], '/') . '/api/ota-client?op=purge_cache';
    $body = (string)json_encode(['op' => 'purge_cache', 'ts' => time()], JSON_UNESCAPED_SLASHES);
    $headers = zeynart_ota_build_auth_headers('purge_cache', (string)$node['license_key'], (string)$node['node_secret'], $body);
    $resp = zeynart_ota_http_request($endpoint, 'POST', $body, $headers, 15);
    $dec = json_decode((string)($resp['body'] ?? ''), true);
    if (!empty($resp['ok']) && is_array($dec) && !empty($dec['ok'])) {
        zeynart_ota_append_log('remote_cache_purge', $node['domain'] . ' sitesinin LiteSpeed & OPcache önbelleği uzaktan temizlendi.', 'ok', [], $baseRoot);
        return ['ok' => true, 'message' => (string)($dec['message'] ?? 'Uzak önbellek temizlendi.')];
    }
    return ['ok' => false, 'error' => is_array($dec) && !empty($dec['error']) ? (string)$dec['error'] : 'Uzak önbellek temizlenemedi.'];
}

function zeynart_ota_push_update_to_node(string $nodeId, string $hubBaseUrl = SITE_URL, ?string $releaseId = null, string $baseRoot = ROOT_DIR): array
{
    $hub = zeynart_ota_get_hub_state(true, $baseRoot);
    $found = zeynart_ota_find_node_by_license_or_id($hub, $nodeId);
    if ($found === null) {
        return ['ok' => false, 'error' => 'Müşteri sitesi kaydı bulunamadı.'];
    }
    $idx = $found['index'];
    $node = $found['node'];

    if (($node['license_status'] ?? 'active') !== 'active') {
        return ['ok' => false, 'error' => $node['domain'] . ' lisansı askıda olduğu için güncelleme gönderilmedi.'];
    }

    $targetRelease = null;
    if ($releaseId !== null && $releaseId !== '') {
        foreach ($hub['releases'] as $r) {
            if (($r['id'] ?? '') === $releaseId || ($r['version'] ?? '') === $releaseId) {
                $targetRelease = $r;
                break;
            }
        }
    }
    if ($targetRelease === null) {
        $targetRelease = zeynart_ota_find_latest_release($hub, (string)($node['channel'] ?? 'stable'), $baseRoot);
    }
    if ($targetRelease === null) {
        // Auto-build current studio core if no release exists yet
        $autoBuild = zeynart_ota_build_release_package(['channel' => (string)($node['channel'] ?? 'stable')], $baseRoot);
        if (empty($autoBuild['ok'])) {
            return ['ok' => false, 'error' => (string)($autoBuild['error'] ?? 'Yayınlanacak OTA paketi bulunamadı.')];
        }
        $hub = zeynart_ota_get_hub_state(false, $baseRoot);
        $targetRelease = $autoBuild['release'];
    }

    $zipAbs = zeynart_ota_releases_dir($baseRoot) . '/' . basename((string)$targetRelease['filename']);
    if (!is_file($zipAbs)) {
        return ['ok' => false, 'error' => 'Paket dosyası diskte bulunamadı. Lütfen yeni sürüm paketleyin.'];
    }

    $downloadUrl = rtrim($hubBaseUrl, '/') . '/api/ota-hub?op=download&version=' . rawurlencode((string)$targetRelease['version']) . '&channel=' . rawurlencode((string)$targetRelease['channel']);
    $pushPayload = [
        'op' => 'push_update',
        'release' => [
            'version' => (string)$targetRelease['version'],
            'channel' => (string)$targetRelease['channel'],
            'title' => (string)($targetRelease['title'] ?? ''),
            'changelog' => $targetRelease['changelog'] ?? [],
            'min_php' => (string)($targetRelease['min_php'] ?? '8.1.0'),
            'sha256' => (string)$targetRelease['sha256'],
            'size_bytes' => (int)$targetRelease['size_bytes'],
            'signature' => (string)$targetRelease['signature'],
            'key_fingerprint' => (string)($targetRelease['key_fingerprint'] ?? ''),
            'download_url' => $downloadUrl,
        ],
    ];

    // Include inline signed ZIP when <= 4 MB and inline_push_fallback is active so shared hosts behind NAT update with 0 callback issues
    if (!empty($hub['inline_push_fallback']) && (int)$targetRelease['size_bytes'] <= 4194304) {
        $rawZip = @file_get_contents($zipAbs);
        if (is_string($rawZip) && $rawZip !== '') {
            $pushPayload['package_b64'] = base64_encode($rawZip);
        }
    }

    $endpoint = rtrim((string)$node['site_url'], '/') . '/api/ota-client?op=push_update';
    $body = (string)json_encode($pushPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = zeynart_ota_build_auth_headers('push_update', (string)$node['license_key'], (string)$node['node_secret'], $body);
    $resp = zeynart_ota_http_request($endpoint, 'POST', $body, $headers, 45);
    $dec = json_decode((string)($resp['body'] ?? ''), true);

    $nowStr = date('Y-m-d H:i:s');
    $hub = zeynart_ota_get_hub_state(false, $baseRoot);
    $reFound = zeynart_ota_find_node_by_license_or_id($hub, (string)$node['id']);
    $nIdx = $reFound !== null ? $reFound['index'] : $idx;

    if (!empty($resp['ok']) && is_array($dec) && !empty($dec['ok'])) {
        $newVer = (string)($dec['new_version'] ?? $targetRelease['version']);
        if (isset($hub['nodes'][$nIdx])) {
            $hub['nodes'][$nIdx]['installed_version'] = $newVer;
            if (!empty($dec['admin_dir'])) {
                $hub['nodes'][$nIdx]['admin_dir'] = mb_substr(trim((string)$dec['admin_dir']), 0, 48, 'UTF-8');
            }
            $hub['nodes'][$nIdx]['health_status'] = 'online';
            $hub['nodes'][$nIdx]['last_seen_at'] = $nowStr;
            $hub['nodes'][$nIdx]['last_push_at'] = $nowStr;
            $hub['nodes'][$nIdx]['last_push_result'] = 'ok:v' . $newVer;
            zeynart_ota_save_hub_state($hub, $baseRoot);
        }
        zeynart_ota_append_log(
            'remote_push_ok',
            sprintf('%s sitesi uzaktan v%s sürümüne başarıyla güncellendi (%d çekirdek dosya).', $node['domain'], $newVer, (int)($dec['updated_files_count'] ?? 0)),
            'ok',
            ['domain' => $node['domain'], 'version' => $newVer],
            $baseRoot
        );
        return [
            'ok' => true,
            'domain' => $node['domain'],
            'new_version' => $newVer,
            'updated_files_count' => (int)($dec['updated_files_count'] ?? 0),
            'message' => (string)($dec['message'] ?? ($node['domain'] . ' v' . $newVer . ' sürümüne güncellendi.')),
            'node' => isset($hub['nodes'][$nIdx]) ? zeynart_ota_mask_node_for_ui($hub['nodes'][$nIdx], $hub) : null,
        ];
    }

    $errText = is_array($dec) && !empty($dec['error'])
        ? (string)$dec['error']
        : ((string)($resp['error'] ?? 'Uzak istemci güncelleme yanıtı alınamadı.'));

    if (isset($hub['nodes'][$nIdx])) {
        $hub['nodes'][$nIdx]['last_push_at'] = $nowStr;
        $hub['nodes'][$nIdx]['last_push_result'] = 'err:' . mb_substr($errText, 0, 80, 'UTF-8');
        zeynart_ota_save_hub_state($hub, $baseRoot);
    }
    zeynart_ota_append_log(
        'remote_push_fail',
        sprintf('%s sitesine v%s güncelleme gönderimi başarısız: %s', $node['domain'], $targetRelease['version'], $errText),
        'error',
        ['domain' => $node['domain']],
        $baseRoot
    );

    return [
        'ok' => false,
        'domain' => $node['domain'],
        'error' => $errText,
    ];
}

/* ============================================================================
 * 6. CLIENT ATOMIC UPDATE, PRE-OTA ROLLBACK SNAPSHOT & SELF-HEALING ENGINE
 * ============================================================================ */

function zeynart_ota_backups_dir(string $targetRoot = ROOT_DIR): string
{
    $dir = rtrim(str_replace('\\', '/', $targetRoot), '/') . '/data/backups';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function zeynart_ota_list_rollback_snapshots(string $targetRoot = ROOT_DIR): array
{
    $dir = zeynart_ota_backups_dir($targetRoot);
    $files = glob($dir . '/pre-ota-*.zip') ?: [];
    usort($files, static fn(string $a, string $b): int => ((int)@filemtime($b)) <=> ((int)@filemtime($a)));
    $out = [];
    foreach ($files as $f) {
        $sz = (int)@filesize($f);
        $mt = (int)@filemtime($f);
        $base = basename($f);
        $ver = '';
        if (preg_match('/^pre-ota-v([0-9A-Za-z._\-]+)-\d{8}_\d{6}\.zip$/', $base, $m)) {
            $ver = str_replace('_', '.', $m[1]);
        }
        $out[] = [
            'filename' => $base,
            'previous_version' => $ver,
            'size_kb' => round($sz / 1024, 1),
            'created_at' => $mt > 0 ? date('d.m.Y H:i:s', $mt) : '',
        ];
    }
    return $out;
}

function zeynart_ota_create_pre_update_snapshot(
    array $incomingPaths,
    string $oldVersion,
    string $targetRoot = ROOT_DIR,
    ?string $explicitAdminDir = null
): array {
    require_once __DIR__ . '/enterprise_cms_suite.php';

    $root = rtrim(str_replace('\\', '/', $targetRoot), '/');
    $admDir = ($explicitAdminDir !== null && $explicitAdminDir !== '')
        ? $explicitAdminDir
        : zeynart_ota_resolve_target_admin_rel_dir($root);
    $existingCore = zeynart_ota_collect_core_files($root, $admDir);
    $zipEntries = [];
    $newlyAdded = [];

    foreach ($existingCore as $canonicalRel => $abs) {
        $zipEntries[$canonicalRel] = ['path' => $abs];
    }
    foreach ($incomingPaths as $inCanonicalRel) {
        $diskRel = zeynart_ota_map_canonical_to_client_rel((string)$inCanonicalRel, $admDir);
        if (!isset($existingCore[$inCanonicalRel]) && !is_file($root . '/' . $diskRel)) {
            $newlyAdded[] = $inCanonicalRel;
        }
    }

    $meta = [
        'format' => 'zeynart-ota-rollback-v1',
        'previous_version' => $oldVersion,
        'admin_dir' => $admDir,
        'created_at' => date('c'),
        'newly_added_files' => $newlyAdded,
    ];
    $zipEntries['ota-rollback.json'] = (string)json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    $safeVer = preg_replace('/[^a-zA-Z0-9.\-]/', '_', $oldVersion) ?? 'prev';
    $bDir = zeynart_ota_backups_dir($root);
    $fname = 'pre-ota-v' . $safeVer . '-' . date('Ymd_His') . '.zip';
    $dest = $bDir . '/' . $fname;

    if (!zeynart_pure_zip_write($dest, $zipEntries) || !is_file($dest)) {
        return ['ok' => false, 'error' => 'Güncelleme öncesi güvenlik yedeği (Rollback ZIP) oluşturulamadı.'];
    }

    // Retain at most 3 pre-ota rollback snapshots
    $all = glob($bDir . '/pre-ota-*.zip') ?: [];
    usort($all, static fn(string $a, string $b): int => ((int)@filemtime($b)) <=> ((int)@filemtime($a)));
    foreach (array_slice($all, 3) as $oldSnap) {
        @unlink($oldSnap);
    }

    return [
        'ok' => true,
        'filename' => $fname,
        'path' => $dest,
    ];
}

function zeynart_ota_restore_rollback_zip(string $zipPath, string $targetRoot = ROOT_DIR): array
{
    require_once __DIR__ . '/enterprise_cms_suite.php';

    if (!is_file($zipPath)) {
        return ['ok' => false, 'error' => 'Geri dönüş yedek arşivi bulunamadı.'];
    }
    $entries = zeynart_pure_zip_read_all($zipPath);
    if (!is_array($entries) || empty($entries['ota-rollback.json'])) {
        return ['ok' => false, 'error' => 'Geri dönüş arşivi okunamadı veya geçersiz biçimde.'];
    }
    $meta = json_decode((string)$entries['ota-rollback.json'], true);
    if (!is_array($meta)) {
        return ['ok' => false, 'error' => 'Geri dönüş üst verisi bozuk.'];
    }

    $root = rtrim(str_replace('\\', '/', $targetRoot), '/');
    $cState = zeynart_ota_get_client_state($root);
    $metaAdm = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string)($meta['admin_dir'] ?? ''))) ?? '';
    $admDir = ($metaAdm !== '' && is_dir($root . '/' . $metaAdm))
        ? $metaAdm
        : zeynart_ota_resolve_target_admin_rel_dir($root, $cState);
    $restoredCount = 0;

    foreach ($entries as $canonicalRel => $rawBytes) {
        if ($canonicalRel === 'ota-rollback.json') {
            continue;
        }
        if (!zeynart_ota_is_allowed_core_target_path((string)$canonicalRel, [], $admDir)) {
            continue;
        }
        $diskRel = zeynart_ota_map_canonical_to_client_rel((string)$canonicalRel, $admDir);
        $dest = $root . '/' . $diskRel;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $tmp = $dest . '.rb_tmp_' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $rawBytes, LOCK_EX) !== false) {
            if (!@rename($tmp, $dest)) {
                @copy($tmp, $dest);
                @unlink($tmp);
            }
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($dest, true);
            }
            $restoredCount++;
        }
    }

    foreach (($meta['newly_added_files'] ?? []) as $addedRel) {
        $cleanRel = ltrim(str_replace('\\', '/', (string)$addedRel), '/');
        if ($cleanRel !== '' && zeynart_ota_is_allowed_core_target_path($cleanRel, [], $admDir)) {
            $diskRel = zeynart_ota_map_canonical_to_client_rel($cleanRel, $admDir);
            $addedAbs = $root . '/' . $diskRel;
            if (is_file($addedAbs)) {
                @unlink($addedAbs);
            }
        }
    }

    $prevVer = trim((string)($meta['previous_version'] ?? ZEYNART_STUDIO_VERSION));
    if ($prevVer !== '') {
        @file_put_contents($root . '/data/.ota_installed_version', $prevVer, LOCK_EX);
    }

    $cState['installed_version'] = $prevVer !== '' ? $prevVer : $cState['installed_version'];
    $cState['update_available'] = !empty($cState['latest_remote_version'])
        && version_compare((string)$cState['latest_remote_version'], (string)$cState['installed_version'], '>');
    zeynart_ota_save_client_state($cState, $root);

    zeynart_ota_post_update_housekeeping($root);
    zeynart_ota_append_log(
        'rollback_restored',
        sprintf('Sistem v%s sürümüne başarıyla geri döndürüldü (%d çekirdek dosya /%s/ klasörüne geri yüklendi).', $prevVer, $restoredCount, $admDir),
        'warn',
        ['restored_version' => $prevVer, 'files' => $restoredCount, 'admin_dir' => $admDir],
        $root
    );

    return [
        'ok' => true,
        'restored_version' => $prevVer,
        'restored_files_count' => $restoredCount,
        'admin_dir' => $admDir,
    ];
}

function zeynart_ota_post_update_housekeeping(string $targetRoot = ROOT_DIR): void
{
    $root = rtrim(str_replace('\\', '/', $targetRoot), '/');
    foreach ([
        '/data/admin_boot_summary_cache.json',
        '/data/unused_files_cache.json',
        '/data/dir_metrics_cache.json',
    ] as $cRel) {
        if (is_file($root . $cRel)) {
            @unlink($root . $cRel);
        }
    }
    @file_put_contents($root . '/data/.lscache_needs_purge', (string)time(), LOCK_EX);

    if ($root === ROOT_DIR) {
        $GLOBALS['__zeynart_boot_bundle_mem'] = null;
        $GLOBALS['__zeynart_unused_scan_mem'] = null;
        $migScript = $root . '/includes/core/ota_migration.php';
        if (is_file($migScript)) {
            try {
                include_once $migScript;
                if (function_exists('zeynart_ota_run_version_migrations')) {
                    zeynart_ota_run_version_migrations($root);
                }
            } catch (Throwable) {
            }
        }
        if (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) {
            $pdo = zeynart_get_pdo();
            if ($pdo instanceof PDO && function_exists('zeynart_sql_ensure_schema')) {
                try {
                    zeynart_sql_ensure_schema($pdo);
                    zeynart_sql_invalidate_runtime_caches($pdo);
                } catch (Throwable) {
                }
            }
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }
}

function zeynart_ota_run_core_self_check(string $targetRoot = ROOT_DIR, ?string $explicitAdminDir = null): array
{
    $root = rtrim(str_replace('\\', '/', $targetRoot), '/');
    $admDir = ($explicitAdminDir !== null && $explicitAdminDir !== '')
        ? $explicitAdminDir
        : zeynart_ota_resolve_target_admin_rel_dir($root);
    $criticalPhpFiles = [
        $admDir . '/index.php',
        $admDir . '/api.php',
        'includes/config.php',
        'includes/db.php',
        'includes/security.php',
    ];
    foreach ($criticalPhpFiles as $rel) {
        $abs = $root . '/' . $rel;
        if (!is_file($abs)) {
            return ['ok' => false, 'error' => 'Öz-denetim hatası: Kritik çekirdek dosyası eksik (' . $rel . ').'];
        }
        $head = (string)@file_get_contents($abs, false, null, 0, 64);
        if (!str_starts_with($head, '<?php')) {
            return ['ok' => false, 'error' => 'Öz-denetim hatası: Geçersiz PHP başlığı (' . $rel . ').'];
        }
    }
    return ['ok' => true];
}

/**
 * Verifies SHA-256 + RSA-2048 signature, snapshots existing core, atomically extracts Core files,
 * runs post-update migrations + self-check, and auto-rolls back on any failure.
 */
function zeynart_ota_apply_zip_package(
    string $zipPath,
    array $expectedRelease,
    ?array $explicitClientState = null,
    string $targetRoot = ROOT_DIR
): array {
    require_once __DIR__ . '/enterprise_cms_suite.php';

    $t0 = hrtime(true);
    $root = rtrim(str_replace('\\', '/', $targetRoot), '/');
    $clientState = $explicitClientState ?? zeynart_ota_get_client_state($root);

    if (!is_file($zipPath)) {
        return ['ok' => false, 'error' => 'İndirilen güncelleme paketi bulunamadı.'];
    }

    $actualSize = (int)@filesize($zipPath);
    $actualSha256 = (string)@hash_file('sha256', $zipPath);
    $expectedSha256 = strtolower(trim((string)($expectedRelease['sha256'] ?? '')));
    $expectedSize = (int)($expectedRelease['size_bytes'] ?? 0);
    $expectedVer = trim((string)($expectedRelease['version'] ?? ''));
    $expectedChan = trim((string)($expectedRelease['channel'] ?? 'stable'));
    $signatureB64 = trim((string)($expectedRelease['signature'] ?? ''));
    $pubKeyPem = trim((string)($clientState['hub_public_key_pem'] ?? ''));

    if ($expectedVer === '' || $expectedSha256 === '' || $signatureB64 === '') {
        return ['ok' => false, 'error' => 'Güncelleme üst verisi (sürüm/özet/imza) eksik.'];
    }
    if ($expectedSize > 0 && $actualSize !== $expectedSize) {
        zeynart_ota_append_log('ota_size_mismatch', 'Paket boyutu beklenen değerle eşleşmedi, güncelleme reddedildi.', 'error', [], $root);
        return ['ok' => false, 'error' => 'Paket boyutu doğrulaması başarısız oldu.'];
    }
    if (!hash_equals($expectedSha256, strtolower($actualSha256))) {
        zeynart_ota_append_log('ota_hash_reject', 'SHA-256 paket bütünlük özeti eşleşmedi! Bozuk veya değiştirilmiş paket reddedildi.', 'error', [], $root);
        return ['ok' => false, 'error' => 'SHA-256 bütünlük doğrulaması başarısız! Paket reddedildi.'];
    }
    if ($pubKeyPem === '' || !zeynart_ota_verify_package_digest($expectedVer, $expectedChan, $actualSha256, $actualSize, $signatureB64, $pubKeyPem)) {
        zeynart_ota_append_log('ota_sig_reject', 'RSA-2048 kriptografik imza doğrulaması BAŞARISIZ! Yetkisiz paket engellendi.', 'error', [], $root);
        if ($root === ROOT_DIR && function_exists('zeynart_audit_log')) {
            zeynart_audit_log('ota_sig_reject', 'Geçersiz RSA-2048 imzalı OTA güncelleme paketi engellendi.', 'system', 'warn');
        }
        return ['ok' => false, 'error' => 'RSA-2048 kriptografik imza doğrulaması başarısız! Paket reddedildi.'];
    }

    $entries = zeynart_pure_zip_read_all($zipPath);
    if (!is_array($entries) || empty($entries['ota-manifest.json'])) {
        return ['ok' => false, 'error' => 'OTA paketi açılamadı veya ota-manifest.json bulunamadı.'];
    }

    $manifest = json_decode((string)$entries['ota-manifest.json'], true);
    if (!is_array($manifest) || ($manifest['format'] ?? '') !== 'zeynart-ota-v1') {
        return ['ok' => false, 'error' => 'Geçersiz ota-manifest.json şeması.'];
    }
    if ((string)($manifest['version'] ?? '') !== $expectedVer) {
        return ['ok' => false, 'error' => 'Manifest sürümü imzalı üst veriyle uyuşmuyor.'];
    }
    $minPhp = trim((string)($manifest['min_php'] ?? '8.1.0'));
    if ($minPhp !== '' && version_compare(PHP_VERSION, $minPhp, '<')) {
        return ['ok' => false, 'error' => sprintf('Bu sürüm en az PHP %s gerektirir (Mevcut: PHP %s).', $minPhp, PHP_VERSION)];
    }

    $manifestFiles = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
    if ($manifestFiles === []) {
        return ['ok' => false, 'error' => 'Manifest içerisinde güncellenecek çekirdek dosya yok.'];
    }

    $targetAdminDir = zeynart_ota_resolve_target_admin_rel_dir($root, $clientState);
    $protectedPaths = is_array($clientState['protected_paths'] ?? null) ? $clientState['protected_paths'] : [];
    $validatedPayloads = [];

    foreach ($manifestFiles as $relPath => $fMeta) {
        if (!zeynart_ota_is_allowed_core_target_path((string)$relPath, $protectedPaths, $targetAdminDir)) {
            zeynart_ota_append_log('ota_path_blocked', 'Çekirdek dışı dosya yolu tespit edildi ve paket reddedildi: ' . $relPath, 'error', [], $root);
            return ['ok' => false, 'error' => 'Güvenlik sınırı ihlali: Çekirdek dışı dosya güncellenemez (' . $relPath . ').'];
        }
        if (!array_key_exists($relPath, $entries)) {
            return ['ok' => false, 'error' => 'Manifestte belirtilen dosya arşivde eksik: ' . $relPath];
        }
        $rawContent = (string)$entries[$relPath];
        $fileSha = hash('sha256', $rawContent);
        if (!hash_equals(strtolower((string)($fMeta['sha256'] ?? '')), strtolower($fileSha))) {
            return ['ok' => false, 'error' => 'Dosya bazlı SHA-256 doğrulaması başarısız: ' . $relPath];
        }
        $validatedPayloads[$relPath] = $rawContent;
    }

    $oldVersion = zeynart_ota_current_version($root);
    $snap = zeynart_ota_create_pre_update_snapshot(array_keys($validatedPayloads), $oldVersion, $root, $targetAdminDir);
    if (empty($snap['ok'])) {
        return ['ok' => false, 'error' => (string)$snap['error']];
    }

    $runId = bin2hex(random_bytes(4));
    $writtenCount = 0;
    $writeError = '';

    foreach ($validatedPayloads as $canonicalRel => $rawContent) {
        $diskRel = zeynart_ota_map_canonical_to_client_rel((string)$canonicalRel, $targetAdminDir);
        $destAbs = $root . '/' . $diskRel;
        $parentDir = dirname($destAbs);
        if (!is_dir($parentDir) && !@mkdir($parentDir, 0775, true) && !is_dir($parentDir)) {
            $writeError = 'Klasör oluşturulamadı: ' . $diskRel;
            break;
        }
        $tmpFile = $destAbs . '.ota_tmp_' . $runId;
        $bytesWritten = @file_put_contents($tmpFile, $rawContent, LOCK_EX);
        if ($bytesWritten === false || $bytesWritten !== strlen($rawContent)) {
            @unlink($tmpFile);
            $writeError = 'Atomik geçici dosya yazılamadı: ' . $diskRel;
            break;
        }
        if (!@rename($tmpFile, $destAbs)) {
            if (!@copy($tmpFile, $destAbs)) {
                @unlink($tmpFile);
                $writeError = 'Hedef dosya üzerine aktarılamadı: ' . $diskRel;
                break;
            }
            @unlink($tmpFile);
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($destAbs, true);
        }
        $writtenCount++;
    }

    if ($writeError !== '') {
        zeynart_ota_restore_rollback_zip((string)$snap['path'], $root);
        zeynart_ota_append_log('ota_auto_rollback', 'Yazım hatası nedeniyle otomatik geri dönüş (Auto-Rollback) uygulandı: ' . $writeError, 'error', [], $root);
        return ['ok' => false, 'error' => $writeError . ' (Sistem otomatik olarak v' . $oldVersion . ' sürümüne geri döndürüldü.)'];
    }

    $selfCheck = zeynart_ota_run_core_self_check($root, $targetAdminDir);
    if (empty($selfCheck['ok'])) {
        zeynart_ota_restore_rollback_zip((string)$snap['path'], $root);
        return ['ok' => false, 'error' => $selfCheck['error'] . ' (Otomatik geri dönüş uygulandı.)'];
    }

    if ($targetAdminDir !== 'admin' && is_dir($root . '/data')) {
        @file_put_contents($root . '/data/.admin_dir_name', $targetAdminDir, LOCK_EX);
    }
    @file_put_contents($root . '/data/.ota_installed_version', $expectedVer, LOCK_EX);
    zeynart_ota_post_update_housekeeping($root);

    $clientState['installed_version'] = $expectedVer;
    $clientState['latest_remote_version'] = $expectedVer;
    $clientState['update_available'] = false;
    $clientState['detected_admin_dir'] = $targetAdminDir;
    $clientState['last_updated_at'] = date('Y-m-d H:i:s');
    $clientState['last_rollback_zip'] = (string)$snap['filename'];
    $clientState['last_error'] = '';
    zeynart_ota_save_client_state($clientState, $root);

    $elapsedMs = round((hrtime(true) - $t0) / 1e6, 1);
    zeynart_ota_append_log(
        'ota_update_applied',
        sprintf('Stüdyo çekirdeği v%s → v%s sürümüne başarıyla güncellendi (%d dosya → /%s/, %.1f ms, Yedek: %s).', $oldVersion, $expectedVer, $writtenCount, $targetAdminDir, $elapsedMs, $snap['filename']),
        'ok',
        ['from' => $oldVersion, 'to' => $expectedVer, 'files' => $writtenCount, 'admin_dir' => $targetAdminDir],
        $root
    );
    if ($root === ROOT_DIR && function_exists('zeynart_audit_log')) {
        zeynart_audit_log('ota_update_applied', sprintf('OTA güncellemesi tamamlandı: v%s -> v%s (%d çekirdek dosya, /%s/).', $oldVersion, $expectedVer, $writtenCount, $targetAdminDir), 'system', 'ok');
    }

    return [
        'ok' => true,
        'previous_version' => $oldVersion,
        'new_version' => $expectedVer,
        'updated_files_count' => $writtenCount,
        'admin_dir' => $targetAdminDir,
        'rollback_file' => (string)$snap['filename'],
        'elapsed_ms' => $elapsedMs,
        'message' => sprintf('✓ Zeynart Dijital Stüdyo v%s sürümüne başarıyla güncellendi (%d çekirdek dosya /%s/ klasörüne işlendi; vitrin tasarımı, sektör modülleri ve verileriniz %%100 korundu)!', $expectedVer, $writtenCount, $targetAdminDir),
    ];
}

function zeynart_ota_client_check_for_updates(bool $handshake = false, string $targetRoot = ROOT_DIR): array
{
    $cState = zeynart_ota_get_client_state($targetRoot);
    if (empty($cState['connected']) || empty($cState['hub_url']) || empty($cState['license_key']) || empty($cState['node_secret'])) {
        return ['ok' => false, 'error' => 'Henüz bir OTA Dağıtım Hub\'ına bağlı değilsiniz. Lütfen Bağlantı Kodunu yapıştırın.'];
    }

    $op = $handshake ? 'handshake' : 'manifest';
    $endpoint = rtrim((string)$cState['hub_url'], '/') . '/api/ota-hub?op=' . $op;
    $detectedAdmDir = zeynart_ota_resolve_target_admin_rel_dir($targetRoot, $cState);
    $sectorExts = function_exists('zeynart_get_sector_extensions_public_meta')
        ? zeynart_get_sector_extensions_public_meta($targetRoot)
        : [];
    $reqPayload = [
        'op' => $op,
        'channel' => (string)$cState['channel'],
        'installed_version' => (string)$cState['installed_version'],
        'php_version' => PHP_VERSION,
        'db_engine' => (function_exists('zeynart_is_sql_active') && zeynart_is_sql_active()) ? 'mysql' : 'json',
        'admin_dir' => $detectedAdmDir,
        'admin_slug' => ($targetRoot === ROOT_DIR && function_exists('zeynart_get_admin_slug')) ? zeynart_get_admin_slug() : $detectedAdmDir,
        'sector_extensions' => $sectorExts,
        'site_url' => SITE_URL,
    ];
    $rawBody = (string)json_encode($reqPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = zeynart_ota_build_auth_headers($op, (string)$cState['license_key'], (string)$cState['node_secret'], $rawBody);
    $resp = zeynart_ota_http_request($endpoint, 'POST', $rawBody, $headers, 18);
    $dec = json_decode((string)($resp['body'] ?? ''), true);

    $cState['last_checked_at'] = date('Y-m-d H:i:s');
    $cState['last_checked_ts'] = time();

    if (empty($resp['ok']) || !is_array($dec) || empty($dec['ok'])) {
        $fallbackErr = !empty($resp['error'])
            ? (string)$resp['error']
            : ('Hub sunucusundan geçersiz yanıt alındı (HTTP ' . (int)($resp['http_code'] ?? 0) . '): ' . mb_substr(trim(strip_tags((string)($resp['body'] ?? ''))), 0, 120, 'UTF-8'));
        $err = is_array($dec) && !empty($dec['error']) ? (string)$dec['error'] : $fallbackErr;
        $cState['last_error'] = $err;
        zeynart_ota_save_client_state($cState, $targetRoot);
        return ['ok' => false, 'error' => $err];
    }

    $cState['last_error'] = '';
    if (!empty($dec['public_key_pem']) && empty($cState['hub_public_key_pem'])) {
        $cState['hub_public_key_pem'] = (string)$dec['public_key_pem'];
        $cState['key_fingerprint'] = zeynart_ota_key_fingerprint((string)$dec['public_key_pem']);
    }

    $rel = is_array($dec['release'] ?? null) ? $dec['release'] : null;
    if ($rel !== null && !empty($rel['version'])) {
        $cState['latest_remote_version'] = (string)$rel['version'];
        $cState['latest_manifest'] = $rel;
        $cState['update_available'] = version_compare((string)$rel['version'], (string)$cState['installed_version'], '>');
    } else {
        $cState['update_available'] = false;
    }

    zeynart_ota_save_client_state($cState, $targetRoot);
    return [
        'ok' => true,
        'installed_version' => (string)$cState['installed_version'],
        'latest_remote_version' => (string)$cState['latest_remote_version'],
        'update_available' => (bool)$cState['update_available'],
        'release' => $rel,
    ];
}

function zeynart_ota_client_pull_and_apply(string $targetRoot = ROOT_DIR): array
{
    $chk = zeynart_ota_client_check_for_updates(false, $targetRoot);
    if (empty($chk['ok'])) {
        return $chk;
    }
    $rel = $chk['release'] ?? null;
    if (!is_array($rel) || empty($rel['version'])) {
        return ['ok' => false, 'error' => 'Hub üzerinde yayınlanmış aktif bir sürüm paketi bulunamadı.'];
    }

    $cState = zeynart_ota_get_client_state($targetRoot);
    $dlUrl = !empty($rel['download_url'])
        ? (string)$rel['download_url']
        : (rtrim((string)$cState['hub_url'], '/') . '/api/ota-hub?op=download&version=' . rawurlencode((string)$rel['version']) . '&channel=' . rawurlencode((string)($rel['channel'] ?? 'stable')));

    $dlBody = (string)json_encode([
        'op' => 'download',
        'version' => (string)$rel['version'],
        'channel' => (string)($rel['channel'] ?? 'stable'),
    ], JSON_UNESCAPED_SLASHES);
    $headers = zeynart_ota_build_auth_headers('download', (string)$cState['license_key'], (string)$cState['node_secret'], $dlBody);
    $resp = zeynart_ota_http_request($dlUrl, 'POST', $dlBody, $headers, 60);

    if (empty($resp['ok']) || $resp['body'] === '') {
        return ['ok' => false, 'error' => 'OTA paket dosyası Hub sunucusundan indirilemedi (' . ($resp['error'] ?? 'Bağlantı hatası') . ').'];
    }

    $tmpZip = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'zn_ota_pull_' . bin2hex(random_bytes(6)) . '.zip';
    if (@file_put_contents($tmpZip, $resp['body'], LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'Geçici paket dosyası diske yazılamadı.'];
    }

    $applyRes = zeynart_ota_apply_zip_package($tmpZip, $rel, $cState, $targetRoot);
    @unlink($tmpZip);
    return $applyRes;
}

function zeynart_ota_maybe_background_autoupdate(): ?array
{
    $cState = zeynart_ota_get_client_state();
    if (empty($cState['connected']) || ($cState['auto_update'] ?? 'off') === 'off') {
        return null;
    }
    if ((time() - (int)($cState['last_checked_ts'] ?? 0)) < 21600) {
        return null;
    }
    $chk = zeynart_ota_client_check_for_updates(false);
    if (empty($chk['ok']) || empty($chk['update_available']) || !is_array($chk['release'] ?? null)) {
        return $chk;
    }
    $curParts = explode('.', (string)$cState['installed_version']);
    $remParts = explode('.', (string)$chk['latest_remote_version']);
    if ($cState['auto_update'] === 'patch') {
        if (($curParts[0] ?? '') !== ($remParts[0] ?? '') || ($curParts[1] ?? '') !== ($remParts[1] ?? '')) {
            return $chk;
        }
    }
    return zeynart_ota_client_pull_and_apply();
}

/* ============================================================================
 * 7. SAFE UI SERIALIZATION & SUMMARY HELPERS
 * ============================================================================ */

function zeynart_ota_mask_node_for_ui(array $node, array $hubState, string $hubBaseUrl = SITE_URL): array
{
    $latest = zeynart_ota_find_latest_release($hubState, (string)($node['channel'] ?? 'stable'));
    $latestVer = $latest['version'] ?? ZEYNART_STUDIO_VERSION;
    $instVer = (string)($node['installed_version'] ?? '—');
    $outdated = ($instVer !== '' && $instVer !== '—' && preg_match('/^\d+\.\d+/', $instVer))
        ? version_compare((string)$latestVer, $instVer, '>')
        : true;

    $copy = $node;
    unset($copy['node_secret']);
    $copy['outdated'] = $outdated;
    $copy['target_version'] = $latestVer;
    $copy['connection_token'] = zeynart_ota_build_connection_token($node, $hubState, $hubBaseUrl);
    return $copy;
}

function zeynart_ota_compile_overview(string $hubBaseUrl = SITE_URL): array
{
    $hub = zeynart_ota_get_hub_state(true);
    $client = zeynart_ota_get_client_state();
    $logs = zeynart_ota_get_logs(60);
    $rollbacks = zeynart_ota_list_rollback_snapshots();

    $uiNodes = [];
    $onlineCount = 0;
    $upToDateCount = 0;
    $outdatedCount = 0;
    $suspendedCount = 0;

    foreach ($hub['nodes'] as $n) {
        $masked = zeynart_ota_mask_node_for_ui($n, $hub, $hubBaseUrl);
        if (($masked['license_status'] ?? 'active') === 'suspended') {
            $suspendedCount++;
        } else {
            if (($masked['health_status'] ?? '') === 'online') {
                $onlineCount++;
            }
            if (!empty($masked['outdated'])) {
                $outdatedCount++;
            } else {
                $upToDateCount++;
            }
        }
        $uiNodes[] = $masked;
    }

    $safeClient = $client;
    unset($safeClient['node_secret']);
    $safeClient['has_secret'] = !empty($client['node_secret']);
    $safeClient['detected_admin_dir'] = zeynart_ota_resolve_target_admin_rel_dir(ROOT_DIR, $client);
    $safeClient['active_admin_slug'] = function_exists('zeynart_get_admin_slug') ? zeynart_get_admin_slug() : $safeClient['detected_admin_dir'];

    return [
        'studio_version' => zeynart_ota_current_version(),
        'core_files_count' => count(zeynart_ota_collect_core_files()),
        'openssl_ready' => extension_loaded('openssl'),
        'detected_admin_dir' => $safeClient['detected_admin_dir'],
        'active_admin_slug' => $safeClient['active_admin_slug'],
        'sector_extensions' => function_exists('zeynart_get_sector_extensions_public_meta') ? zeynart_get_sector_extensions_public_meta() : [],
        'protected_boundaries' => [
            'pages/*.php (Tüm Vitrin & Sektör Sayfaları)',
            'includes/site_header.php · site_footer.php · icons.php · i18n.php',
            'includes/extensions/* & /' . $safeClient['detected_admin_dir'] . '/extensions/* (Sektör Modülleri)',
            'assets/css/site.css · assets/js/site.js (Müşteri Tasarımı)',
            'uploads/* · images/* · data/* · MySQL Veritabanı',
        ],
        'hub' => [
            'enabled' => $hub['enabled'],
            'default_channel' => $hub['default_channel'],
            'require_https' => $hub['require_https'],
            'inline_push_fallback' => $hub['inline_push_fallback'],
            'retain_releases' => $hub['retain_releases'],
            'public_key_pem' => $hub['public_key_pem'],
            'key_fingerprint' => $hub['key_fingerprint'],
            'key_created_at' => $hub['key_created_at'],
            'hub_endpoint' => rtrim($hubBaseUrl, '/') . '/api/ota-hub',
            'releases' => $hub['releases'],
            'nodes' => $uiNodes,
            'fleet_stats' => [
                'total_nodes' => count($uiNodes),
                'online_nodes' => $onlineCount,
                'up_to_date_nodes' => $upToDateCount,
                'outdated_nodes' => $outdatedCount,
                'suspended_nodes' => $suspendedCount,
            ],
        ],
        'client' => $safeClient,
        'rollbacks' => $rollbacks,
        'logs' => $logs,
    ];
}

function zeynart_ota_boot_summary(): array
{
    $hub = zeynart_ota_get_hub_state(false);
    $client = zeynart_ota_get_client_state();
    $latest = zeynart_ota_find_latest_release($hub, 'stable');
    $latestVer = $latest['version'] ?? ZEYNART_STUDIO_VERSION;
    $outdated = 0;
    foreach ($hub['nodes'] as $n) {
        if (($n['license_status'] ?? 'active') !== 'active') {
            continue;
        }
        $iv = (string)($n['installed_version'] ?? '');
        if ($iv === '' || $iv === '—' || version_compare((string)$latestVer, $iv, '>')) {
            $outdated++;
        }
    }
    return [
        'studio_version' => zeynart_ota_current_version(),
        'hub_enabled' => !empty($hub['enabled']),
        'total_nodes' => count($hub['nodes']),
        'outdated_nodes' => $outdated,
        'releases_count' => count($hub['releases']),
        'latest_release_version' => $latest['version'] ?? '',
        'client_connected' => !empty($client['connected']),
        'client_update_available' => !empty($client['update_available']),
        'client_remote_version' => (string)($client['latest_remote_version'] ?? ''),
    ];
}
