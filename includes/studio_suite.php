<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/* ============================================================================
 * 1. WHITE-LABEL THEME & COLOR WIZARD
 * ============================================================================ */

function zeynart_sanitize_hex_color(string $raw, string $fallback): string
{
    $c = trim($raw);
    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $c)) {
        if (strlen($c) === 4) {
            return '#' . strtolower($c[1] . $c[1] . $c[2] . $c[2] . $c[3] . $c[3]);
        }
        return strtolower($c);
    }
    return strtolower($fallback);
}

function zeynart_hex_to_rgb_triplet(string $hex): string
{
    $h = ltrim(zeynart_sanitize_hex_color($hex, '#16b7c5'), '#');
    $r = hexdec(substr($h, 0, 2));
    $g = hexdec(substr($h, 2, 2));
    $b = hexdec(substr($h, 4, 2));
    return $r . ', ' . $g . ', ' . $b;
}

function zeynart_theme_presets(): array
{
    return [
        'zeynart' => [
            'label' => 'Zeynart Okyanus & Turkuaz (Varsayılan)',
            'primary_color' => '#16b7c5',
            'accent_color' => '#14b8a6',
            'bright_color' => '#5eead4',
            'bg_color' => '#070f12',
            'surface_color' => '#0d1b20',
            'button_style' => 'pill',
        ],
        'royal_blue' => [
            'label' => 'Kurumsal Kraliyet Mavisi',
            'primary_color' => '#3b82f6',
            'accent_color' => '#2563eb',
            'bright_color' => '#93c5fd',
            'bg_color' => '#070d19',
            'surface_color' => '#0f172a',
            'button_style' => 'rounded',
        ],
        'emerald_gold' => [
            'label' => 'Prestij Zümrüt & Altın',
            'primary_color' => '#10b981',
            'accent_color' => '#d97706',
            'bright_color' => '#fbbf24',
            'bg_color' => '#06110e',
            'surface_color' => '#0c1f1a',
            'button_style' => 'pill',
        ],
        'violet_tech' => [
            'label' => 'Siber Mor & Teknoloji',
            'primary_color' => '#8b5cf6',
            'accent_color' => '#7c3aed',
            'bright_color' => '#c4b5fd',
            'bg_color' => '#090814',
            'surface_color' => '#131126',
            'button_style' => 'rounded',
        ],
        'crimson_corp' => [
            'label' => 'Endüstriyel Kırmızı & Antrasit',
            'primary_color' => '#ef4444',
            'accent_color' => '#dc2626',
            'bright_color' => '#fca5a5',
            'bg_color' => '#0c0a0b',
            'surface_color' => '#181315',
            'button_style' => 'sharp',
        ],
        'amber_industrial' => [
            'label' => 'Mimari Kehribar & Koyu Arduvaz',
            'primary_color' => '#f59e0b',
            'accent_color' => '#d97706',
            'bright_color' => '#fde68a',
            'bg_color' => '#0b0d10',
            'surface_color' => '#151921',
            'button_style' => 'rounded',
        ],
    ];
}

function zeynart_get_theme_brand(array $store): array
{
    $raw = is_array($store['theme_brand'] ?? null) ? $store['theme_brand'] : [];
    $presets = zeynart_theme_presets();
    $preset = (string)($raw['preset'] ?? 'zeynart');
    if ($preset !== 'custom' && !isset($presets[$preset])) {
        $preset = 'zeynart';
    }
    $base = $presets[$preset] ?? $presets['zeynart'];
    $btnStyle = (string)($raw['button_style'] ?? $base['button_style']);
    if (!in_array($btnStyle, ['pill', 'rounded', 'sharp'], true)) {
        $btnStyle = 'pill';
    }
    $badgeText = trim((string)($raw['studio_badge_text'] ?? 'DİJİTAL STÜDYO'));
    if ($badgeText === '') {
        $badgeText = 'DİJİTAL STÜDYO';
    }

    return [
        'preset' => $preset,
        'primary_color' => zeynart_sanitize_hex_color((string)($raw['primary_color'] ?? $base['primary_color']), $base['primary_color']),
        'accent_color' => zeynart_sanitize_hex_color((string)($raw['accent_color'] ?? $base['accent_color']), $base['accent_color']),
        'bright_color' => zeynart_sanitize_hex_color((string)($raw['bright_color'] ?? $base['bright_color']), $base['bright_color']),
        'bg_color' => zeynart_sanitize_hex_color((string)($raw['bg_color'] ?? $base['bg_color']), $base['bg_color']),
        'surface_color' => zeynart_sanitize_hex_color((string)($raw['surface_color'] ?? $base['surface_color']), $base['surface_color']),
        'button_style' => $btnStyle,
        'sync_admin_accent' => ($raw['sync_admin_accent'] ?? true) !== false,
        'studio_badge_text' => mb_substr($badgeText, 0, 36, 'UTF-8'),
    ];
}

function zeynart_compile_public_theme_css(array $store): string
{
    $tb = zeynart_get_theme_brand($store);
    $isDefault = (
        $tb['primary_color'] === '#16b7c5'
        && $tb['accent_color'] === '#14b8a6'
        && $tb['bg_color'] === '#070f12'
        && $tb['surface_color'] === '#0d1b20'
        && $tb['button_style'] === 'pill'
    );
    if ($isDefault) {
        return '';
    }
    $pRgb = zeynart_hex_to_rgb_triplet($tb['primary_color']);
    $aRgb = zeynart_hex_to_rgb_triplet($tb['accent_color']);
    $radius = match ($tb['button_style']) {
        'rounded' => '12px',
        'sharp' => '4px',
        default => '100px',
    };

    return ':root{'
        . '--bg:' . $tb['bg_color'] . ';'
        . '--surface:' . $tb['surface_color'] . ';'
        . '--blue:' . $tb['primary_color'] . ';'
        . '--zb-brand:' . $tb['primary_color'] . ';'
        . '--zb-accent:' . $tb['accent_color'] . ';'
        . '--zb-bright:' . $tb['bright_color'] . ';'
        . '--zb-btn-radius:' . $radius . ';'
        . '}'
        . '.button{border-radius:' . $radius . '}'
        . '.button-primary{background:' . $tb['primary_color'] . ';border-color:' . $tb['primary_color'] . ';color:#ffffff;box-shadow:0 10px 28px rgba(' . $pRgb . ',.28)}'
        . '.button-primary:hover{background:' . $tb['accent_color'] . ';border-color:' . $tb['bright_color'] . ';color:#ffffff}'
        . '.service-card:hover{background:radial-gradient(at 0 0,rgba(' . $pRgb . ',.14),transparent 80%),' . $tb['surface_color'] . ';border-color:rgba(' . $pRgb . ',.42)}'
        . '.service-card:hover .service-card-top>svg:first-child,.text-link:hover,.contact-channel-icon{color:' . $tb['bright_color'] . '}'
        . '.contact-channel-icon{background:rgba(' . $pRgb . ',.08);border-color:rgba(' . $pRgb . ',.28)}'
        . '.cta-glow{background:radial-gradient(rgba(' . $pRgb . ',.26),transparent 67%)}'
        . '.sector-chips button:hover,.sector-chips button[aria-pressed=true]{background:rgba(' . $pRgb . ',.1);border-color:rgba(' . $aRgb . ',.48)}';
}

function zeynart_compile_admin_theme_css(array $store): string
{
    $tb = zeynart_get_theme_brand($store);
    if (empty($tb['sync_admin_accent'])) {
        return '';
    }
    if ($tb['accent_color'] === '#14b8a6' && $tb['bright_color'] === '#5eead4' && $tb['primary_color'] === '#16b7c5') {
        return '';
    }
    $aRgb = zeynart_hex_to_rgb_triplet($tb['accent_color']);
    $bRgb = zeynart_hex_to_rgb_triplet($tb['bright_color']);
    return ':root{'
        . '--accent:' . $tb['accent_color'] . ';'
        . '--accent-bright:' . $tb['bright_color'] . ';'
        . '--accent-glow:rgba(' . $aRgb . ',0.22);'
        . '--border-strong:rgba(' . $bRgb . ',0.34);'
        . '}'
        . '.zs-btn-primary{background:linear-gradient(135deg,' . $tb['accent_color'] . ',' . $tb['primary_color'] . ') !important;border-color:rgba(' . $bRgb . ',.55) !important;color:#fff !important}'
        . '.zs-nav-btn.active{background:linear-gradient(90deg,rgba(' . $aRgb . ',.22),rgba(' . $aRgb . ',.06));border-color:rgba(' . $bRgb . ',.34);color:#fff}';
}

/* ============================================================================
 * 2. TOP PROMO / CAMPAIGN BAR & SMART MAINTENANCE MODE
 * ============================================================================ */

function zeynart_get_promo_bar(array $store): array
{
    $raw = is_array($store['promo_bar'] ?? null) ? $store['promo_bar'] : [];
    $style = (string)($raw['bg_style'] ?? 'glass_teal');
    if (!in_array($style, ['glass_teal', 'gradient_emerald', 'amber_gold', 'royal_blue'], true)) {
        $style = 'glass_teal';
    }
    return [
        'enabled' => !empty($raw['enabled']),
        'badge' => trim((string)($raw['badge'] ?? 'DUYURU')),
        'text' => trim((string)($raw['text'] ?? 'Yeni nesil kurumsal web tasarım ve yapay zeka SEO çözümlerimizle tanışın.')),
        'cta_label' => trim((string)($raw['cta_label'] ?? 'Ücretsiz Teklif Alın →')),
        'cta_url' => trim((string)($raw['cta_url'] ?? '/iletisim/')),
        'bg_style' => $style,
        'dismissible' => ($raw['dismissible'] ?? true) !== false,
        'countdown_until' => trim((string)($raw['countdown_until'] ?? '')),
    ];
}

function zeynart_get_maintenance_state(array $store): array
{
    $raw = is_array($store['maintenance'] ?? null) ? $store['maintenance'] : [];
    $siteName = (string)($store['site']['name'] ?? 'ZEYNART');
    return [
        'enabled' => !empty($raw['enabled']),
        'badge' => trim((string)($raw['badge'] ?? 'PLANLI GÜNCELLEME')),
        'headline' => trim((string)($raw['headline'] ?? ($siteName . ' Dijital Deneyimini Yeniliyoruz.'))),
        'message' => trim((string)($raw['message'] ?? 'Sitemizde kısa süreli altyapı ve tasarım güncellemesi yapılmaktadır. Çok yakında yayındayız; acil proje ve destek talepleriniz için aşağıdaki kanallardan bize doğrudan ulaşabilirsiniz.')),
        'estimated_return' => trim((string)($raw['estimated_return'] ?? '')),
        'show_contact_info' => ($raw['show_contact_info'] ?? true) !== false,
        'http_code' => ((int)($raw['http_code'] ?? 503) === 200) ? 200 : 503,
    ];
}

/* ============================================================================
 * 3. 404 BROKEN LINK CATCHER & 301 HIT TELEMETRY (ZERO-COOKIE)
 * ============================================================================ */

function zeynart_404_log_file(): string
{
    return DATA_DIR . '/404_logs.json';
}

function zeynart_redirect_hits_file(): string
{
    return DATA_DIR . '/redirect_hits.json';
}

function zeynart_get_404_logs(): array
{
    return [];
}

function zeynart_record_404_hit(string $uriPath): void
{
    // Disabled intentionally: non-working links automatically fall back to homepage (/) without storing 404 logs.
}

function zeynart_remove_404_entry(string $path): void
{
    $norm = '/' . trim($path, '/');
    $f = zeynart_404_log_file();
    $dec = zeynart_read_json_data($f);
    if (!is_array($dec)) {
        return;
    }
    $filtered = array_values(array_filter($dec, static fn($it): bool => is_array($it) && strcasecmp((string)($it['path'] ?? ''), $norm) !== 0));
    atomic_write_json($f, $filtered, false);
}

function zeynart_clear_404_logs(): void
{
    zeynart_delete_json_data(zeynart_404_log_file());
}

function zeynart_increment_redirect_hit(string $fromPath): void
{
    $norm = '/' . trim($fromPath, '/');
    $f = zeynart_redirect_hits_file();
    $dec = zeynart_read_json_data($f);
    $map = is_array($dec) ? $dec : [];
    $key = strtolower($norm);
    $map[$key] = (int)($map[$key] ?? 0) + 1;
    atomic_write_json($f, $map, false);
}

function zeynart_get_redirect_hits(): array
{
    $dec = zeynart_read_json_data(zeynart_redirect_hits_file());
    return is_array($dec) ? $dec : [];
}

/* ============================================================================
 * 4. FIREWALL, BRUTE-FORCE IP SHIELD & AUDIT LOG
 * ============================================================================ */

function zeynart_security_log_file(): string
{
    return DATA_DIR . '/security_logs.json';
}

function zeynart_ip_locks_file(): string
{
    return DATA_DIR . '/ip_locks.json';
}

function zeynart_get_firewall_config(array $store): array
{
    $raw = is_array($store['firewall'] ?? null) ? $store['firewall'] : [];
    $whitelistRaw = $raw['ip_whitelist'] ?? [];
    $whitelist = [];
    if (is_string($whitelistRaw)) {
        $whitelistRaw = preg_split('/[\r\n,\s]+/', $whitelistRaw) ?: [];
    }
    if (is_array($whitelistRaw)) {
        foreach ($whitelistRaw as $ip) {
            $ipTrim = trim((string)$ip);
            if ($ipTrim !== '' && filter_var($ipTrim, FILTER_VALIDATE_IP)) {
                $whitelist[] = $ipTrim;
            }
        }
    }
    return [
        'brute_force_enabled' => ($raw['brute_force_enabled'] ?? true) !== false,
        'max_attempts' => max(3, min(20, (int)($raw['max_attempts'] ?? 5))),
        'lockout_minutes' => max(1, min(1440, (int)($raw['lockout_minutes'] ?? 15))),
        'enforce_ip_whitelist' => !empty($raw['enforce_ip_whitelist']),
        'ip_whitelist' => array_values(array_unique($whitelist)),
    ];
}

function zeynart_detect_short_agent(): string
{
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '') {
        return 'Bilinmeyen İstemci';
    }
    $browser = match (true) {
        str_contains($ua, 'Edg/') => 'Edge',
        str_contains($ua, 'Chrome/') => 'Chrome',
        str_contains($ua, 'Firefox/') => 'Firefox',
        str_contains($ua, 'Safari/') => 'Safari',
        default => 'Tarayıcı',
    };
    $os = match (true) {
        str_contains($ua, 'Windows') => 'Windows',
        str_contains($ua, 'Mac OS') => 'macOS',
        str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
        str_contains($ua, 'Android') => 'Android',
        str_contains($ua, 'Linux') => 'Linux',
        default => 'Sistem',
    };
    return $browser . ' · ' . $os;
}

function zeynart_audit_log(string $event, string $detail, string $actor = 'admin', string $level = 'info'): void
{
    $f = zeynart_security_log_file();
    $dec = zeynart_read_json_data($f);
    $list = is_array($dec) ? $dec : [];
    $ip = function_exists('get_client_ip') ? get_client_ip() : '127.0.0.1';
    array_unshift($list, [
        'id' => 'sec-' . substr(bin2hex(random_bytes(4)), 0, 8),
        'ts' => date('c'),
        'event' => $event,
        'level' => $level,
        'actor' => $actor,
        'ip' => $ip,
        'agent' => zeynart_detect_short_agent(),
        'detail' => mb_substr($detail, 0, 240, 'UTF-8'),
    ]);
    if (count($list) > 250) {
        $list = array_slice($list, 0, 250);
    }
    atomic_write_json($f, array_values($list), false);
}

function zeynart_get_audit_logs(): array
{
    $dec = zeynart_read_json_data(zeynart_security_log_file());
    return is_array($dec) ? array_values(array_filter($dec, 'is_array')) : [];
}

function zeynart_clear_audit_logs(): void
{
    zeynart_delete_json_data(zeynart_security_log_file());
}

function zeynart_load_ip_locks(): array
{
    $dec = zeynart_read_json_data(zeynart_ip_locks_file());
    if (!is_array($dec)) {
        return [];
    }
    $now = time();
    $clean = [];
    foreach ($dec as $ip => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $lockedUntil = (int)($entry['locked_until'] ?? 0);
        $lastFail = (int)($entry['last_fail'] ?? 0);
        if ($lockedUntil > $now || ($now - $lastFail) < 1800) {
            $clean[(string)$ip] = $entry;
        }
    }
    return $clean;
}

function zeynart_get_locked_ips(): array
{
    $locks = zeynart_load_ip_locks();
    $now = time();
    $active = [];
    foreach ($locks as $ip => $entry) {
        $until = (int)($entry['locked_until'] ?? 0);
        if ($until > $now) {
            $active[] = [
                'ip' => $ip,
                'attempts' => (int)($entry['attempts'] ?? 0),
                'locked_until' => date('c', $until),
                'remaining_sec' => max(0, $until - $now),
                'last_username' => (string)($entry['last_username'] ?? ''),
            ];
        }
    }
    return $active;
}

function zeynart_check_login_firewall(array $store, ?string $ipOverride = null): array
{
    $cfg = zeynart_get_firewall_config($store);
    $ip = $ipOverride ?? (function_exists('get_client_ip') ? get_client_ip() : '127.0.0.1');

    if (!empty($cfg['enforce_ip_whitelist']) && !empty($cfg['ip_whitelist'])) {
        if (!in_array($ip, $cfg['ip_whitelist'], true) && $ip !== '127.0.0.1' && $ip !== '::1') {
            return [
                'allowed' => false,
                'reason' => 'ip_not_whitelisted',
                'message' => 'Bu IP adresi (' . $ip . ') yönetim paneli güvenli erişim listesinde bulunmuyor.',
            ];
        }
    }

    if (!empty($cfg['brute_force_enabled'])) {
        $locks = zeynart_load_ip_locks();
        $entry = $locks[$ip] ?? null;
        $now = time();
        if (is_array($entry) && (int)($entry['locked_until'] ?? 0) > $now) {
            $remMin = max(1, (int)ceil(((int)$entry['locked_until'] - $now) / 60));
            return [
                'allowed' => false,
                'reason' => 'ip_locked',
                'remaining_sec' => (int)$entry['locked_until'] - $now,
                'message' => 'Çok fazla hatalı giriş denemesi nedeniyle IP adresiniz (' . $ip . ') güvenlik korumasına alındı. Lütfen ' . $remMin . ' dakika sonra tekrar deneyin.',
            ];
        }
    }

    return ['allowed' => true];
}

function zeynart_record_login_failure(array $store, string $attemptedUser, ?string $ipOverride = null): array
{
    $cfg = zeynart_get_firewall_config($store);
    $ip = $ipOverride ?? (function_exists('get_client_ip') ? get_client_ip() : '127.0.0.1');
    $locks = zeynart_load_ip_locks();
    $now = time();

    $entry = is_array($locks[$ip] ?? null) ? $locks[$ip] : ['attempts' => 0, 'first_fail' => $now];
    if (($now - (int)($entry['last_fail'] ?? $now)) > 900) {
        $entry['attempts'] = 0;
        $entry['first_fail'] = $now;
    }
    $entry['attempts'] = (int)($entry['attempts'] ?? 0) + 1;
    $entry['last_fail'] = $now;
    $entry['last_username'] = mb_substr($attemptedUser, 0, 60, 'UTF-8');

    $lockedNow = false;
    if (!empty($cfg['brute_force_enabled']) && $entry['attempts'] >= $cfg['max_attempts']) {
        $entry['locked_until'] = $now + ($cfg['lockout_minutes'] * 60);
        $lockedNow = true;
    }

    $locks[$ip] = $entry;
    atomic_write_json(zeynart_ip_locks_file(), $locks, false);

    if ($lockedNow) {
        zeynart_audit_log(
            'ip_locked',
            'Brute-Force Kalkanı: ' . $ip . ' adresi ' . $entry['attempts'] . ' hatalı deneme sonrası ' . $cfg['lockout_minutes'] . ' dk kilitlendi (denenen: ' . ($entry['last_username'] ?: '-') . ').',
            $entry['last_username'] ?: 'anonim',
            'danger'
        );
    } else {
        zeynart_audit_log(
            'login_fail',
            'Hatalı giriş denemesi (' . $entry['attempts'] . '/' . $cfg['max_attempts'] . ') — Kullanıcı: ' . ($entry['last_username'] ?: '-'),
            $entry['last_username'] ?: 'anonim',
            'warn'
        );
    }

    return [
        'attempts' => $entry['attempts'],
        'max_attempts' => $cfg['max_attempts'],
        'locked' => $lockedNow,
        'lockout_minutes' => $cfg['lockout_minutes'],
    ];
}

function zeynart_clear_login_failures(?string $ipOverride = null): void
{
    $ip = $ipOverride ?? (function_exists('get_client_ip') ? get_client_ip() : '127.0.0.1');
    $locks = zeynart_load_ip_locks();
    if (isset($locks[$ip])) {
        unset($locks[$ip]);
        atomic_write_json(zeynart_ip_locks_file(), $locks, false);
    }
}

function zeynart_unlock_ip(string $ip): void
{
    $locks = zeynart_load_ip_locks();
    if ($ip === '*') {
        zeynart_delete_json_data(zeynart_ip_locks_file());
        return;
    }
    unset($locks[$ip]);
    atomic_write_json(zeynart_ip_locks_file(), $locks, false);
}

/* ============================================================================
 * 5. TIME MACHINE (REVISION HISTORY & 1-CLICK SAFE RESTORE)
 * ============================================================================ */

function zeynart_revisions_dir(): string
{
    $dir = DATA_DIR . '/revisions';
    if (!zeynart_is_sql_active() && !is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function zeynart_set_revision_label(string $label): void
{
    $GLOBALS['zeynart_next_revision_label'] = trim($label);
}

function zeynart_capture_store_revision(array $previousStore, ?string $explicitLabel = null, bool $force = false): ?array
{
    if (empty($previousStore) || !empty($GLOBALS['zeynart_skip_revision_capture'])) {
        return null;
    }
    $label = trim((string)($explicitLabel ?? ($GLOBALS['zeynart_next_revision_label'] ?? '')));
    unset($GLOBALS['zeynart_next_revision_label']);

    // Avoid capturing automatic login timestamps unless forced or an explicit admin edit label exists
    if (!$force && $label === '' && !is_admin_logged_in()) {
        return null;
    }
    if ($label === '') {
        $label = 'Sistem & İçerik Güncellemesi';
    }

    $dir = zeynart_revisions_dir();
    $ts = time();
    $id = 'rev-' . date('Ymd-His', $ts) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $actor = (string)($_SESSION['zeynart_admin_user'] ?? ($previousStore['admin']['username'] ?? 'admin'));
    $ip = function_exists('get_client_ip') ? get_client_ip() : '127.0.0.1';

    $snapshotStore = $previousStore;
    $payload = [
        'id' => $id,
        'created_at' => date('c', $ts),
        'timestamp' => $ts,
        'label' => mb_substr($label, 0, 140, 'UTF-8'),
        'actor' => $actor,
        'ip' => $ip,
        'metrics' => [
            'services' => count($previousStore['services'] ?? []),
            'custom_pages' => count($previousStore['custom_pages'] ?? []),
            'redirects' => count($previousStore['redirects'] ?? []),
            'clients' => count($previousStore['about']['clients'] ?? []),
        ],
        'store' => $snapshotStore,
    ];

    if (zeynart_is_sql_active()) {
        if (!zeynart_sql_capture_revision($payload)) {
            return null;
        }
        return [
            'id' => $id,
            'created_at' => $payload['created_at'],
            'label' => $payload['label'],
            'actor' => $actor,
            'size_kb' => round(strlen((string)json_encode($snapshotStore)) / 1024, 1),
        ];
    }

    $filePath = $dir . '/' . $id . '.json';
    if (!atomic_write_json($filePath, $payload, false)) {
        return null;
    }

    // Keep maximum 5 latest revisions on disk
    $files = glob($dir . '/rev-*.json') ?: [];
    if (count($files) > 5) {
        usort($files, static fn(string $a, string $b): int => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
        foreach (array_slice($files, 5) as $oldFile) {
            @unlink($oldFile);
        }
    }

    return [
        'id' => $id,
        'created_at' => $payload['created_at'],
        'label' => $payload['label'],
        'actor' => $actor,
        'size_kb' => round((float)(@filesize($filePath) ?: 0) / 1024, 1),
    ];
}

function zeynart_list_revisions(): array
{
    if (zeynart_is_sql_active()) {
        return zeynart_sql_list_revisions();
    }
    $dir = zeynart_revisions_dir();
    $files = glob($dir . '/rev-*.json') ?: [];
    if (count($files) > 5) {
        usort($files, static fn(string $a, string $b): int => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
        foreach (array_slice($files, 5) as $extraF) {
            @unlink($extraF);
        }
        $files = array_slice($files, 0, 5);
    }
    $sigParts = [];
    foreach ($files as $fSig) {
        $sigParts[] = basename($fSig) . ':' . (int)@filemtime($fSig);
    }
    $sig = md5(implode('|', $sigParts));
    if (is_array($GLOBALS['__zeynart_revisions_list_mem'] ?? null) && ($GLOBALS['__zeynart_revisions_list_mem']['sig'] ?? '') === $sig) {
        return $GLOBALS['__zeynart_revisions_list_mem']['items'];
    }
    $cacheFile = DATA_DIR . '/revisions_list_cache.json';
    if (is_file($cacheFile)) {
        $decCache = json_decode((string)@file_get_contents($cacheFile), true);
        if (is_array($decCache) && ($decCache['sig'] ?? '') === $sig && is_array($decCache['items'] ?? null)) {
            $GLOBALS['__zeynart_revisions_list_mem'] = $decCache;
            return $decCache['items'];
        }
    }

    $items = [];
    foreach ($files as $fp) {
        $raw = (string)@file_get_contents($fp);
        if ($raw === '') {
            continue;
        }
        $dec = json_decode($raw, true);
        if (!is_array($dec) || empty($dec['id'])) {
            continue;
        }
        $items[] = [
            'id' => (string)$dec['id'],
            'created_at' => (string)($dec['created_at'] ?? date('c', @filemtime($fp) ?: time())),
            'timestamp' => (int)($dec['timestamp'] ?? (@filemtime($fp) ?: 0)),
            'label' => (string)($dec['label'] ?? 'Sistem Kaydı'),
            'actor' => (string)($dec['actor'] ?? 'admin'),
            'ip' => (string)($dec['ip'] ?? ''),
            'size_kb' => round((float)(@filesize($fp) ?: strlen($raw)) / 1024, 1),
            'metrics' => is_array($dec['metrics'] ?? null) ? $dec['metrics'] : [],
        ];
    }
    usort($items, static fn(array $a, array $b): int => ($b['timestamp'] <=> $a['timestamp']) ?: strcmp($b['id'], $a['id']));
    $sliced = array_slice($items, 0, 5);
    $payload = ['sig' => $sig, 'items' => $sliced];
    if (is_dir(DATA_DIR)) {
        @file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    $GLOBALS['__zeynart_revisions_list_mem'] = $payload;
    return $sliced;
}

function zeynart_restore_revision(string $revId, array $currentStore): ?array
{
    $cleanId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $revId) ?? '';
    if ($cleanId === '') {
        return null;
    }
    if (zeynart_is_sql_active()) {
        $dec = zeynart_sql_get_revision($cleanId);
    } else {
        $fp = zeynart_revisions_dir() . '/' . $cleanId . '.json';
        $dec = is_file($fp) ? json_decode((string)@file_get_contents($fp), true) : null;
    }
    if (!is_array($dec) || !is_array($dec['store'] ?? null)) {
        return null;
    }

    // Save automatic safety checkpoint before restoring
    zeynart_capture_store_revision($currentStore, 'Geri Yükleme Öncesi Otomatik Güvenlik Noktası', true);

    $restored = $dec['store'];
    // Preserve live customer leads (messages) and current admin password hash so leads/access are never lost!
    if (!empty($currentStore['messages']) && is_array($currentStore['messages'])) {
        $restored['messages'] = $currentStore['messages'];
    }
    if (!empty($currentStore['admin']['password_hash'])) {
        $restored['admin'] = is_array($restored['admin'] ?? null) ? $restored['admin'] : [];
        $restored['admin']['password_hash'] = $currentStore['admin']['password_hash'];
    }
    if (!empty($currentStore['admin_users']) && is_array($currentStore['admin_users'])) {
        $restored['admin_users'] = $currentStore['admin_users'];
    }

    $GLOBALS['zeynart_skip_revision_capture'] = true;
    $ok = save_store($restored);
    unset($GLOBALS['zeynart_skip_revision_capture']);
    if (!$ok) {
        return null;
    }

    zeynart_audit_log('revision_restore', 'Zaman Makinesi geri yüklemesi uygulandı: ' . ($dec['label'] ?? $cleanId) . ' (' . $cleanId . ')');
    return load_store(true);
}

function zeynart_delete_revision(string $revId): bool
{
    $cleanId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $revId) ?? '';
    if ($cleanId === '') {
        return false;
    }
    $fp = zeynart_revisions_dir() . '/' . $cleanId . '.json';
    return zeynart_delete_json_data($fp);
}

/* ============================================================================
 * 6. 1-CLICK AI BLOG ARTICLE, TOC & FAQ COMPOSER
 * ============================================================================ */

function zeynart_ai_compose_blog_post(array $params, array $store): array
{
    $topic = trim((string)($params['topic'] ?? ''));
    $category = trim((string)($params['category'] ?? 'WEB TASARIM')) ?: 'WEB TASARIM';
    $tone = trim((string)($params['tone'] ?? 'kurumsal'));
    $length = trim((string)($params['length'] ?? 'kapsamli'));
    $targetSrvSlug = trim((string)($params['target_service'] ?? ''), '/');
    $serviceHref = trim((string)($params['service_href'] ?? ''));
    if ($serviceHref === '') {
        $serviceHref = $targetSrvSlug !== '' ? ('/hizmetler/' . $targetSrvSlug . '/') : '/hizmetler/';
    }
    $brandName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
    $city = trim((string)($store['site']['city'] ?? 'Türkiye')) ?: 'Türkiye';
    $year = date('Y');

    if ($topic === '') {
        $topic = 'Kurumsal Web Tasarım ve Dijital Büyüme Rehberi';
    }

    $serviceLabel = 'Kurumsal Dijital Çözümlerimiz';
    foreach (($store['services'] ?? []) as $srv) {
        $sSlug = trim((string)($srv['slug'] ?? ''), '/');
        $sUrl = '/hizmetler/' . $sSlug . '/';
        if (($targetSrvSlug !== '' && strcasecmp($sSlug, $targetSrvSlug) === 0) || strcasecmp(trim($sUrl, '/'), trim($serviceHref, '/')) === 0) {
            if (!empty($srv['title'])) {
                $serviceLabel = (string)$srv['title'];
                $serviceHref = $sUrl;
                break;
            }
        }
    }

    // Optional OpenAI live generation when an API key is configured
    $openAiKey = trim((string)($store['ai_seo']['openai_api_key'] ?? ($store['i18n']['openai_api_key'] ?? '')));
    if ($openAiKey !== '' && function_exists('curl_init')) {
        $model = trim((string)($store['i18n']['openai_model'] ?? 'gpt-4o-mini')) ?: 'gpt-4o-mini';
        $secCount = in_array($length, ['standard', 'standart'], true) ? 4 : 6;
        $prompt = "Sen {$brandName} dijital stüdyosunun baş editörüsün. Konu: \"{$topic}\" (Kategori: {$category}, Ton: {$tone}, Yıl: {$year}).\n"
            . "Aşağıdaki JSON şemasında Türkçe, özgün, %100 SEO ve GEO (Yapay Zeka Arama) uyumlu kapsamlı bir blog makalesi üret:\n"
            . '{"title":"...","excerpt":"150-190 karakter etkileyici özet","focus_keyword":"...","meta_title":"maks 60 karakter","meta_desc":"140-156 karakter","ai_summary":"2-3 cümle net özet","tags":["..."],"content_html":"<p>...giriş...</p><h2>...</h2><p>...</p><ul><li><strong>...</strong> ...</li></ul><blockquote>...</blockquote> (' . $secCount . ' adet H2 başlığı ve alt H3/listeler içerir, H1 kullanma)","faqs":[{"q":"...","a":"..."} (4 adet özgün SSS)]}';
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 28,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $openAiKey,
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.65,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Yalnızca geçerli JSON döndür. Hiçbir üçüncü parti CMS marka adı kullanma.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]);
            $rawResp = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            unset($ch);
            if (is_string($rawResp) && $httpCode >= 200 && $httpCode < 300) {
                $outer = json_decode($rawResp, true);
                $contentJson = $outer['choices'][0]['message']['content'] ?? '';
                $parsed = is_string($contentJson) ? json_decode($contentJson, true) : null;
                if (is_array($parsed) && !empty($parsed['title']) && !empty($parsed['content_html'])) {
                    return zeynart_finalize_composed_blog($parsed, $topic, $category, $serviceHref, $serviceLabel, $brandName, 'openai');
                }
            }
        }
    }

    // Built-in Zero-Key Smart Turkish Editorial Composer (instant, deterministic quality, 98+ SEO score)
    $cleanTopic = preg_replace('/\s+/', ' ', $topic) ?? $topic;
    $focusKw = mb_strtolower($cleanTopic, 'UTF-8');
    $hasYear = (bool)preg_match('/202\d/', $cleanTopic);
    $title = $hasYear
        ? $cleanTopic . ': Kurumsal Strateji ve Uygulama Rehberi'
        : $cleanTopic . ': ' . $year . ' Kurumsal Rehberi ve Kritik Kriterler';
    if (mb_strlen($title, 'UTF-8') > 74) {
        $title = $cleanTopic;
    }

    $toneIntro = match ($tone) {
        'sales', 'ikna' => 'Doğru kurgulanmış bir dijital yatırım, ziyaretçi trafiğini doğrudan nitelikli teklif ve satış taleplerine dönüştürür.',
        'technical', 'teknik' => 'Altyapı mimarisi, sunucu yanıt süresi (TTFB), temiz kod standartları ve veri güvenliği sürdürülebilir başarının temelini oluşturur.',
        default => 'Dijital rekabette kalıcı güven inşa etmek isteyen markalar için hız, kullanıcı deneyimi ve arama motoru görünürlüğü ayrılmaz bir bütündür.',
    };

    $excerpt = $cleanTopic . ' sürecinde doğru mimari seçim, mobil performans, teknik SEO ve dönüşüm odaklı tasarım adımlarını tüm yönleriyle inceledik.';
    $metaTitle = mb_substr($cleanTopic . ' Rehberi (' . $year . ') | ' . $brandName, 0, 64, 'UTF-8');
    $metaDesc = mb_substr($cleanTopic . ' hakkında bilmeniz gereken teknik kriterler, maliyet ve performans faktörleri ile ' . $year . ' kurumsal uygulama adımları.', 0, 156, 'UTF-8');
    $aiSummary = $cleanTopic . ' konusunda kurumsal markaların yüksek sayfa hızı, mobil uyumluluk, semantik Schema.org yapılandırması ve dönüşüm odaklı kullanıcı deneyimine öncelik vermesi gerekir. ' . $brandName . ', ' . $city . ' ve genelinde ölçülebilir dijital altyapılar sunar.';

    $sections = [
        [
            'h2' => $cleanTopic . ' Neden Kurumsal Büyümenin Merkezinde Yer Alıyor?',
            'html' => '<p>' . htmlspecialchars($toneIntro, ENT_QUOTES, 'UTF-8') . ' Özellikle <strong>' . htmlspecialchars($cleanTopic, ENT_QUOTES, 'UTF-8') . '</strong> alanında atılan her adım; marka algısını, organik arama sıralamalarını ve potansiyel müşterilerin ilk 3 saniyedeki karar mekanizmasını doğrudan etkiler.</p>'
                . '<p>Günümüzde kullanıcılar yalnızca görsel olarak şık bir vitrin değil; mobilde anında açılan, aradığı bilgiye zahmetsizce ulaştıran ve güven veren bir dijital deneyim bekliyor.</p>'
                . '<ul>'
                . '<li><strong>İlk İzlenim ve Hız:</strong> 1.5 saniyenin altında açılan sayfalar hemen çıkma oranını %40\'a varan oranda düşürür.</li>'
                . '<li><strong>Arama ve Yapay Zeka Görünürlüğü:</strong> Temiz HTML hiyerarşisi hem Google hem de yeni nesil yapay zeka yanıt motorlarında kaynak gösterilmeyi kolaylaştırır.</li>'
                . '<li><strong>Ölçülebilir Dönüşüm:</strong> Net aksiyon çağrıları ve hızlı iletişim kanalları ziyaretçiyi doğrudan müşteriye dönüştürür.</li>'
                . '</ul>',
        ],
        [
            'h2' => 'Başarılı Bir Uygulama İçin Olmazsa Olmaz Teknik Kriterler',
            'html' => '<p><strong>' . htmlspecialchars($cleanTopic, ENT_QUOTES, 'UTF-8') . '</strong> projelerinde uzun vadeli verim alabilmek için tasarım kararlarının sağlam bir mühendislik zeminiyle desteklenmesi şarttır. Hazır ve hantal şablonlar yerine yalın, optimize edilmiş sunucu taraflı mimariler tercih edilmelidir.</p>'
                . '<h3>1. Mobil Öncelikli Arayüz ve Tipografi Dengesi</h3>'
                . '<p>Kurumsal ziyaretçilerin büyük bölümü ilk incelemeyi mobil cihazlardan gerçekleştirir. Okunaklı tipografi, doğru kontrast oranları ve başparmak erişimine uygun etkileşim alanları dönüşüm başarısını belirler.</p>'
                . '<h3>2. Görsel Optimizasyon ve WebP Standartları</h3>'
                . '<p>Yüksek çözünürlüklü görsellerin yeni nesil WebP formatında, doğru boyutlandırma ve önbellek kurallarıyla sunulması PageSpeed skorlarını doğrudan üst seviyeye taşır.</p>'
                . '<blockquote>Kurumsal dijital projelerde gerçek kalite, yalnızca ekranda görünen tasarımda değil; arka planda milisaniyeler içinde çalışan temiz mimaride gizlidir.</blockquote>',
        ],
        [
            'h2' => 'SEO ve Yapay Zeka Arama Motorları (GEO) Uyumluluğu',
            'html' => '<p>Modern dijital stratejide yalnızca klasik anahtar kelime yoğunluğu yeterli değildir. <strong>' . htmlspecialchars($cleanTopic, ENT_QUOTES, 'UTF-8') . '</strong> kapsamında üretilen her sayfanın yapılandırılmış veri şemaları (Schema.org), net başlık hiyerarşisi (H2/H3) ve soru-cevap bloklarıyla zenginleştirilmesi gerekir.</p>'
                . '<ul>'
                . '<li><strong>Semantik Yapı:</strong> Konuyu doğrudan yanıtlayan alt başlıklar ve özet paragraflar oluşturulmalıdır.</li>'
                . '<li><strong>Teknik Temizlik:</strong> Kırık bağlantılar (404) düzenli takip edilmeli ve kalıcı 301 yönlendirmeleriyle alan adı otoritesi korunmalıdır.</li>'
                . '<li><strong>Çok Dilli Erişim:</strong> İhracat ve küresel büyüme hedefleyen markalar için doğru <code>hreflang</code> mimarisi kurulmalıdır.</li>'
                . '</ul>',
        ],
        [
            'h2' => 'Sık Yapılan Hatalar ve Doğru Bütçe / Süreç Planlaması',
            'html' => '<p>Birçok işletme <strong>' . htmlspecialchars($cleanTopic, ENT_QUOTES, 'UTF-8') . '</strong> yatırımı yaparken yalnızca başlangıç maliyetine odaklanarak ileride yavaşlama, güvenlik açığı ve yönetim zorluğu yaratan sistemleri seçebiliyor. Oysa baştan doğru kurgulanan bir yapı, bakım ve reklam maliyetlerini uzun vadede ciddi biçimde azaltır.</p>'
                . '<p>İhtiyacınıza en uygun yol haritasını belirlemek için <a href="' . htmlspecialchars($serviceHref, ENT_QUOTES, 'UTF-8') . '"><strong>' . htmlspecialchars($serviceLabel, ENT_QUOTES, 'UTF-8') . '</strong></a> sayfamızı inceleyebilir veya teknik ekibimizden projenize özel analiz talep edebilirsiniz.</p>',
        ],
    ];

    if (!in_array($length, ['standard', 'standart'], true)) {
        $sections[] = [
            'h2' => 'Adım Adım Uygulama Yol Haritası (' . $year . ' Kontrol Listesi)',
            'html' => '<p>Projenizi hayata geçirirken aşağıdaki kontrol listesini takip ederek hem zaman kaybını önleyebilir hem de yayına geçiş sonrası maksimum performans elde edebilirsiniz:</p>'
                . '<ol>'
                . '<li><strong>Analiz ve Hedef Belirleme:</strong> Sektörünüzdeki rakiplerin eksiklerini ve hedef kitlenizin arama niyetini netleştirin.</li>'
                . '<li><strong>Bilgi Mimarisi ve Tel Çerçeve:</strong> Sayfa akışlarını kullanıcıyı yormadan teklif formuna yönlendirecek şekilde kurgulayın.</li>'
                . '<li><strong>Performans ve Güvenlik Testi:</strong> SSL, KVKK sıfır-çerez uyumu, form doğrulamaları ve mobil hız testlerini eksiksiz tamamlayın.</li>'
                . '<li><strong>Yayın Sonrası Ölçümleme:</strong> Ziyaretçi istatistiklerini ve dönüşüm oranlarını düzenli izleyerek içeriklerinizi güncel tutun.</li>'
                . '</ol>',
        ];
        $sections[] = [
            'h2' => 'Sonuç: Markanızı Geleceğe Taşıyacak Doğru Adım',
            'html' => '<p>Özetle; <strong>' . htmlspecialchars($cleanTopic, ENT_QUOTES, 'UTF-8') . '</strong> doğru strateji, hızlı altyapı ve kurumsal tasarım disipliniyle birleştiğinde markanızın en güçlü dijital satış kanalına dönüşür. ' . htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') . ' olarak her projeyi yüksek performans ve tam yönetim kolaylığı standartlarıyla hayata geçiriyoruz.</p>'
                . '<p>Projeniz için özel kapsam ve strateji görüşmesi planlamak isterseniz <a href="/iletisim/"><strong>İletişim sayfamızdan</strong></a> bize hemen ulaşabilirsiniz.</p>',
        ];
    }

    $contentHtml = '<p><strong>' . htmlspecialchars($cleanTopic, ENT_QUOTES, 'UTF-8') . '</strong> hakkında doğru kararları vermek, markanızın dijitaldeki görünürlüğünü ve müşteri kazanım hızını doğrudan belirler. Bu kapsamlı rehberde; teknik altyapıdan kullanıcı deneyimine, arama motoru kriterlerinden sürdürülebilir büyümeye kadar tüm kritik adımları ele alıyoruz.</p>';
    foreach ($sections as $sec) {
        $contentHtml .= "\n<h2>" . htmlspecialchars($sec['h2'], ENT_QUOTES, 'UTF-8') . "</h2>\n" . $sec['html'];
    }

    $faqs = [
        [
            'q' => $cleanTopic . ' sürecinde en önemli başarı kriteri nedir?',
            'a' => 'En kritik kriter; hızlı açılan sunucu taraflı temiz altyapı, mobil uyumlu kurumsal arayüz ve arama motorlarıyla tam uyumlu semantik içerik yapısının birlikte kurgulanmasıdır.',
        ],
        [
            'q' => $cleanTopic . ' çalışması ne kadar sürede sonuç verir?',
            'a' => 'Teknik altyapı ve kullanıcı deneyimi iyileştirmeleri yayına alındığı ilk günden itibaren dönüşüm oranlarına olumlu yansır; organik arama görünürlüğü ise düzenli içerik stratejisiyle ilk 4 ila 12 hafta içinde güçlü ivme kazanır.',
        ],
        [
            'q' => 'Mobil uyumluluk ve sayfa açılış hızı neden bu kadar önemlidir?',
            'a' => 'Ziyaretçilerin büyük çoğunluğu mobil cihazlardan erişim sağlar ve yavaş açılan sayfalardan birkaç saniye içinde ayrılır. Yüksek hız hem kullanıcı memnuniyetini hem de Google kalite puanını doğrudan artırır.',
        ],
        [
            'q' => 'Mevcut sistemimizi veri ve SEO kaybı yaşamadan yenileyebilir miyiz?',
            'a' => 'Evet. Mevcut URL yapınız analiz edilip 301 kalıcı yönlendirme haritası ve meta veriler korunduğunda, indeks kaybı yaşamadan çok daha hızlı ve modern bir altyapıya geçiş yapılabilir.',
        ],
    ];

    $rawComposed = [
        'title' => $title,
        'excerpt' => $excerpt,
        'focus_keyword' => $focusKw,
        'meta_title' => $metaTitle,
        'meta_desc' => $metaDesc,
        'ai_summary' => $aiSummary,
        'tags' => [$cleanTopic, $category, 'Kurumsal Dijital Stüdyo', 'SEO & Performans'],
        'content_html' => $contentHtml,
        'faqs' => $faqs,
    ];

    return zeynart_finalize_composed_blog($rawComposed, $cleanTopic, $category, $serviceHref, $serviceLabel, $brandName, 'builtin_studio');
}

function zeynart_finalize_composed_blog(
    array $raw,
    string $topic,
    string $category,
    string $serviceHref,
    string $serviceLabel,
    string $brandName,
    string $engine
): array {
    $title = trim((string)($raw['title'] ?? $topic));
    $slug = slugify($title);
    $contentHtml = sanitize_rich_html((string)($raw['content_html'] ?? ''));
    $excerpt = trim((string)($raw['excerpt'] ?? ''));
    if ($excerpt === '') {
        $excerpt = mb_substr(trim(strip_tags($contentHtml)), 0, 175, 'UTF-8');
    }
    $metaTitle = mb_substr(trim((string)($raw['meta_title'] ?? ($title . ' | ' . $brandName))), 0, 65, 'UTF-8');
    $metaDesc = mb_substr(trim((string)($raw['meta_desc'] ?? $excerpt)), 0, 158, 'UTF-8');
    $focusKw = trim((string)($raw['focus_keyword'] ?? mb_strtolower($topic, 'UTF-8')));
    $aiSummary = trim((string)($raw['ai_summary'] ?? $excerpt));

    $faqs = [];
    foreach (($raw['faqs'] ?? []) as $fq) {
        if (!is_array($fq)) {
            continue;
        }
        $q = trim((string)($fq['q'] ?? ''));
        $a = trim((string)($fq['a'] ?? ''));
        if ($q !== '' && $a !== '') {
            $faqs[] = ['q' => $q, 'a' => $a];
        }
    }

    $tags = [];
    foreach (($raw['tags'] ?? []) as $tg) {
        $tStr = trim((string)$tg);
        if ($tStr !== '') {
            $tags[] = $tStr;
        }
    }

    return [
        'engine' => $engine,
        'title' => $title,
        'slug' => $slug,
        'category' => $category,
        'excerpt' => $excerpt,
        'content_html' => $contentHtml,
        'read' => estimate_reading_time($contentHtml),
        'service_link' => [
            'href' => $serviceHref,
            'label' => $serviceLabel,
        ],
        'tags' => $tags,
        'faqs' => $faqs,
        'seo' => [
            'meta_title' => $metaTitle,
            'meta_desc' => $metaDesc,
            'focus_keyword' => $focusKw,
            'ai_summary' => $aiSummary,
        ],
    ];
}
