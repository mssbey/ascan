<?php
declare(strict_types=1);

/**
 * ZEYNART DİJİTAL STÜDYO — AJANS PRO PAKETİ (FAZ 2)
 * ----------------------------------------------------------------------------
 * 1. Çoklu Kullanıcı & Rol Yetkilendirmesi (Süper Admin vs Müşteri / Editör)
 * 2. Medya Görsel Kanvas & Otomatik Marka / Domain / Başlık Yazma Motoru (GD + TTF)
 * 3. Otomatik Markalı OpenGraph Görsel Üretici (1200x630 GD) & Site SEO Doktoru
 * 4. Adım Adım İnteraktif Teklif & Maliyet Sihirbazı (Multi-Step Quote Wizard)
 * 5. Çerezsiz Akıllı Pop-up & Çıkış Niyeti (Exit-Intent) Stüdyosu
 */

require_once __DIR__ . '/config.php';

/* ============================================================================
 * 1. MULTI-USER & ROLE AUTHORIZATION (super_admin vs editor)
 * ============================================================================ */

function zeynart_normalize_admin_users(array $store, bool $stripHashes = true): array
{
    $primaryUser = trim((string)($store['admin']['username'] ?? 'admin')) ?: 'admin';
    $primaryDisplay = trim((string)($store['admin']['display_name'] ?? 'Zeynart Yönetici')) ?: 'Zeynart Yönetici';
    $primaryRow = [
        'id' => 'usr-primary',
        'username' => $primaryUser,
        'display_name' => $primaryDisplay,
        'role' => 'super_admin',
        'active' => true,
        'is_primary' => true,
        'last_login' => (string)($store['admin']['last_login'] ?? ''),
        'created_at' => (string)($store['admin']['created_at'] ?? '2025-01-01T00:00:00+03:00'),
    ];
    if (!$stripHashes) {
        $primaryRow['password_hash'] = (string)($store['admin']['password_hash'] ?? '');
    }

    $list = [$primaryRow];
    $seenNames = [mb_strtolower($primaryUser, 'UTF-8') => true];

    $rawUsers = is_array($store['admin_users'] ?? null) ? $store['admin_users'] : [];
    foreach ($rawUsers as $idx => $u) {
        if (!is_array($u)) {
            continue;
        }
        $uname = trim((string)($u['username'] ?? ''));
        $uKey = mb_strtolower($uname, 'UTF-8');
        if ($uname === '' || isset($seenNames[$uKey])) {
            continue;
        }
        $seenNames[$uKey] = true;
        $role = in_array((string)($u['role'] ?? 'editor'), ['super_admin', 'editor'], true)
            ? (string)($u['role'] ?? 'editor')
            : 'editor';
        $row = [
            'id' => trim((string)($u['id'] ?? '')) ?: ('usr-' . ($idx + 1)),
            'username' => $uname,
            'display_name' => trim((string)($u['display_name'] ?? $uname)) ?: $uname,
            'role' => $role,
            'active' => ($u['active'] ?? true) !== false,
            'is_primary' => false,
            'last_login' => (string)($u['last_login'] ?? ''),
            'created_at' => (string)($u['created_at'] ?? ''),
        ];
        if (!$stripHashes) {
            $row['password_hash'] = (string)($u['password_hash'] ?? '');
        }
        $list[] = $row;
    }

    return $list;
}

function zeynart_authenticate_admin_login(array &$store, string $username, string $password): ?array
{
    $uname = trim($username);
    if ($uname === '' || $password === '') {
        return null;
    }

    $primaryUser = trim((string)($store['admin']['username'] ?? 'admin')) ?: 'admin';
    $primaryHash = (string)($store['admin']['password_hash'] ?? '');
    if (strcasecmp($primaryUser, $uname) === 0 && $primaryHash !== '' && password_verify($password, $primaryHash)) {
        $store['admin']['last_login'] = date('c');
        return [
            'id' => 'usr-primary',
            'username' => $primaryUser,
            'display_name' => trim((string)($store['admin']['display_name'] ?? 'Zeynart Yönetici')) ?: $primaryUser,
            'role' => 'super_admin',
            'is_primary' => true,
        ];
    }

    if (isset($store['admin_users']) && is_array($store['admin_users'])) {
        foreach ($store['admin_users'] as $idx => $u) {
            if (!is_array($u) || ($u['active'] ?? true) === false) {
                continue;
            }
            $candUser = trim((string)($u['username'] ?? ''));
            $candHash = (string)($u['password_hash'] ?? '');
            if ($candUser !== '' && $candHash !== '' && strcasecmp($candUser, $uname) === 0 && password_verify($password, $candHash)) {
                $role = in_array((string)($u['role'] ?? 'editor'), ['super_admin', 'editor'], true)
                    ? (string)($u['role'] ?? 'editor')
                    : 'editor';
                $store['admin_users'][$idx]['last_login'] = date('c');
                return [
                    'id' => (string)($u['id'] ?? ('usr-' . ($idx + 1))),
                    'username' => $candUser,
                    'display_name' => trim((string)($u['display_name'] ?? $candUser)) ?: $candUser,
                    'role' => $role,
                    'is_primary' => false,
                ];
            }
        }
    }

    return null;
}

function zeynart_current_admin_role(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return 'super_admin';
    }
    $r = (string)($_SESSION['zeynart_admin_role'] ?? 'super_admin');
    return in_array($r, ['super_admin', 'editor'], true) ? $r : 'super_admin';
}

function zeynart_current_admin_profile(array $store): array
{
    $sessionUser = trim((string)($_SESSION['zeynart_admin_user'] ?? ''));
    $sessionRole = zeynart_current_admin_role();
    $all = zeynart_normalize_admin_users($store, true);

    foreach ($all as $u) {
        if ($sessionUser !== '' && strcasecmp((string)$u['username'], $sessionUser) === 0) {
            return [
                'id' => $u['id'],
                'username' => $u['username'],
                'display_name' => $u['display_name'],
                'role' => $u['role'],
                'role_label' => $u['role'] === 'super_admin' ? 'Ajans Süper Admin' : 'Müşteri / Editör',
                'is_super_admin' => $u['role'] === 'super_admin',
                'is_primary' => !empty($u['is_primary']),
            ];
        }
    }

    $fallback = $all[0];
    return [
        'id' => $fallback['id'],
        'username' => $sessionUser !== '' ? $sessionUser : $fallback['username'],
        'display_name' => $sessionRole === 'editor' && $sessionUser !== '' ? $sessionUser : $fallback['display_name'],
        'role' => $sessionRole,
        'role_label' => $sessionRole === 'super_admin' ? 'Ajans Süper Admin' : 'Müşteri / Editör',
        'is_super_admin' => $sessionRole === 'super_admin',
        'is_primary' => $sessionRole === 'super_admin',
    ];
}

function zeynart_is_action_allowed_for_role(string $action, string $role): bool
{
    if ($role === 'super_admin') {
        return true;
    }
    // Actions restricted strictly to super_admin (technical security, users, raw backups, white-label theme, SMTP)
    $superOnlyActions = [
        'appearance_save',
        'settings_save',
        'smtp_test',
        'security_overview',
        'security_20_audit',
        'security_clean_root_leftovers',
        'firewall_save',
        'security_unlock_ip',
        'security_clear_logs',
        'revisions_list',
        'revision_create',
        'revision_restore',
        'revision_delete',
        'backup_export',
        'backup_download',
        'backup_restore_json',
        'users_list',
        'user_save',
        'user_delete',
        'user_toggle_active',
        'uptime_save_config',
        'cache_purge',
        'lscache_save_settings',
        'lscache_scan',
        'lscache_warm',
        'lscache_purge_url',
        'system_health_scan',
        'orphan_media_scan',
        'orphan_media_delete',
        'unused_files_scan',
        'unused_files_clean',
        'security_2fa_init',
        'security_2fa_enable',
        'security_2fa_disable',
        'vault_backup_save_cfg',
        'vault_backup_create',
        'vault_backup_download',
        'vault_backup_upload',
        'vault_backup_restore',
        'vault_backup_delete',
        'database_status_get',
        'database_test_connection',
        'database_migrate_all',
        'database_cleanup_legacy_json',
        'database_download_sql',
        'database_import_sql',
        'database_export_to_json',
        'ota_overview',
        'ota_hub_save_settings',
        'ota_hub_regenerate_keys',
        'ota_build_release',
        'ota_delete_release',
        'ota_download_release',
        'ota_node_save',
        'ota_node_delete',
        'ota_node_ping',
        'ota_node_purge_cache',
        'ota_push_single',
        'ota_push_all',
        'ota_client_save_config',
        'ota_client_connect_token',
        'ota_client_disconnect',
        'ota_client_check',
        'ota_client_apply_update',
        'ota_client_rollback',
        'ota_logs_clear',
    ];
    if (str_starts_with($action, 'ota_')) {
        return false;
    }
    if (function_exists('zeynart_sector_extension_allows_action')) {
        $sectorDecision = zeynart_sector_extension_allows_action($action, $role);
        if ($sectorDecision !== null) {
            return $sectorDecision;
        }
    }
    return !in_array($action, $superOnlyActions, true);
}

/* ============================================================================
 * 2. GD TRUETYPE TYPOGRAPHY & IMAGE TEXT / BRAND / DOMAIN CANVAS ENGINE
 * ============================================================================ */

function zeynart_gd_font_path(bool $bold = true): ?string
{
    $primary = ROOT_DIR . '/assets/fonts/' . ($bold ? 'studio-sans-bold.ttf' : 'studio-sans-medium.ttf');
    if (is_file($primary)) {
        return $primary;
    }
    $alt = ROOT_DIR . '/assets/fonts/studio-sans-bold.ttf';
    return is_file($alt) ? $alt : null;
}

function zeynart_hex_rgb_tuple(string $hex, array $fallback = [20, 184, 166]): array
{
    $clean = ltrim(trim($hex), '#');
    if (strlen($clean) === 3 && ctype_xdigit($clean)) {
        $clean = $clean[0] . $clean[0] . $clean[1] . $clean[1] . $clean[2] . $clean[2];
    }
    if (strlen($clean) === 6 && ctype_xdigit($clean)) {
        return [
            hexdec(substr($clean, 0, 2)),
            hexdec(substr($clean, 2, 2)),
            hexdec(substr($clean, 4, 2)),
        ];
    }
    return $fallback;
}

function zeynart_get_media_stamp_config(array $store): array
{
    $raw = is_array($store['media_stamp'] ?? null) ? $store['media_stamp'] : [];
    $siteDomain = parse_url((string)($store['seo']['canonical_domain'] ?? SITE_URL), PHP_URL_HOST) ?: 'zeynart.com';
    $siteName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
    $brand = function_exists('zeynart_get_theme_brand') ? zeynart_get_theme_brand($store) : ['primary_color' => '#16b7c5'];

    $pos = (string)($raw['position'] ?? 'bottom-right');
    if (!in_array($pos, ['bottom-right', 'bottom-left', 'top-right', 'top-left', 'center-watermark', 'bottom-bar'], true)) {
        $pos = 'bottom-right';
    }
    $style = (string)($raw['style'] ?? 'glass-pill');
    if (!in_array($style, ['glass-pill', 'brand-pill', 'clean-shadow', 'watermark'], true)) {
        $style = 'glass-pill';
    }

    return [
        'enabled' => !empty($raw['enabled']),
        'text' => mb_substr(trim((string)($raw['text'] ?? ($siteName . ' · ' . $siteDomain))) ?: $siteDomain, 0, 120, 'UTF-8'),
        'subtext' => mb_substr(trim((string)($raw['subtext'] ?? '')), 0, 100, 'UTF-8'),
        'position' => $pos,
        'style' => $style,
        'opacity' => max(15, min(100, (int)($raw['opacity'] ?? 88))),
        'scale' => max(60, min(180, (int)($raw['scale'] ?? 100))),
        'text_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($raw['text_color'] ?? '')) ? strtolower((string)$raw['text_color']) : '#ffffff',
        'accent_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($raw['accent_color'] ?? '')) ? strtolower((string)$raw['accent_color']) : (string)($brand['primary_color'] ?? '#16b7c5'),
    ];
}

function zeynart_gd_draw_rounded_rect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
{
    if ($x2 < $x1) {
        [$x1, $x2] = [$x2, $x1];
    }
    if ($y2 < $y1) {
        [$y1, $y2] = [$y2, $y1];
    }
    $r = max(0, min($radius, (int)floor(($x2 - $x1) / 2), (int)floor(($y2 - $y1) / 2)));
    if ($r <= 1) {
        imagefilledrectangle($im, $x1, $y1, $x2, $y2, $color);
        return;
    }
    imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    $d = $r * 2;
    imagefilledellipse($im, $x1 + $r, $y1 + $r, $d, $d, $color);
    imagefilledellipse($im, $x2 - $r, $y1 + $r, $d, $d, $color);
    imagefilledellipse($im, $x1 + $r, $y2 - $r, $d, $d, $color);
    imagefilledellipse($im, $x2 - $r, $y2 - $r, $d, $d, $color);
}

function zeynart_apply_gd_text_stamp(string $absImagePath, array $stampCfg, array $store = []): bool
{
    if (!is_file($absImagePath) || !function_exists('imagettftext')) {
        return false;
    }
    $text = trim((string)($stampCfg['text'] ?? ''));
    if ($text === '') {
        return false;
    }
    $fontBold = zeynart_gd_font_path(true);
    $fontMed = zeynart_gd_font_path(false) ?? $fontBold;
    if ($fontBold === null) {
        return false;
    }

    $info = @getimagesize($absImagePath);
    if (!is_array($info)) {
        return false;
    }
    $mime = strtolower((string)($info['mime'] ?? ''));
    $im = match ($mime) {
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absImagePath) : false,
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($absImagePath) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($absImagePath) : false,
        default => false,
    };
    if ($im === false) {
        return false;
    }

    imagepalettetotruecolor($im);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $w = imagesx($im);
    $h = imagesy($im);
    if ($w < 120 || $h < 80) {
        unset($im);
        return false;
    }

    $scaleFactor = max(0.6, min(1.8, ((int)($stampCfg['scale'] ?? 100)) / 100.0));
    $baseDim = min($w, $h);
    $ptSize = max(11.0, min(64.0, round($baseDim * 0.028 * $scaleFactor, 1)));
    $subtext = trim((string)($stampCfg['subtext'] ?? ''));
    $pos = (string)($stampCfg['position'] ?? 'bottom-right');
    $style = (string)($stampCfg['style'] ?? 'glass-pill');
    $opacityPct = max(15, min(100, (int)($stampCfg['opacity'] ?? 88)));
    // GD alpha: 0 = opaque, 127 = transparent
    $gdAlphaText = (int)round((100 - $opacityPct) * 1.15);
    $gdAlphaBg = min(122, (int)round(36 + (100 - $opacityPct) * 0.85));

    [$tr, $tg, $tb] = zeynart_hex_rgb_tuple((string)($stampCfg['text_color'] ?? '#ffffff'), [255, 255, 255]);
    [$ar, $ag, $ab] = zeynart_hex_rgb_tuple((string)($stampCfg['accent_color'] ?? '#16b7c5'), [22, 183, 197]);

    if ($pos === 'center-watermark' || $style === 'watermark') {
        $wmSize = max(18.0, min(92.0, round($w * 0.042 * $scaleFactor, 1)));
        $angle = 24.0;
        $bbox = @imagettfbbox($wmSize, $angle, $fontBold, $text);
        if (is_array($bbox)) {
            $minX = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
            $maxX = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
            $minY = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
            $maxY = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
            $drawX = (int)round(($w - ($maxX - $minX)) / 2 - $minX);
            $drawY = (int)round(($h - ($maxY - $minY)) / 2 - $minY);
            $wmShadow = imagecolorallocatealpha($im, 4, 10, 14, min(124, $gdAlphaText + 28));
            $wmCol = imagecolorallocatealpha($im, $tr, $tg, $tb, max(45, $gdAlphaText));
            @imagettftext($im, $wmSize, $angle, $drawX + 2, $drawY + 2, $wmShadow, $fontBold, $text);
            @imagettftext($im, $wmSize, $angle, $drawX, $drawY, $wmCol, $fontBold, $text);
        }
    } elseif ($pos === 'bottom-bar') {
        $padY = max(14, (int)round($ptSize * 0.72));
        $padX = max(20, (int)round($w * 0.03));
        $barH = (int)round($ptSize * 1.45 + $padY * 2 + ($subtext !== '' ? $ptSize * 0.95 : 0));
        $barBg = imagecolorallocatealpha($im, 6, 15, 20, min(118, max(22, $gdAlphaBg)));
        $barTopLine = imagecolorallocatealpha($im, $ar, $ag, $ab, 35);
        imagefilledrectangle($im, 0, max(0, $h - $barH), $w, $h, $barBg);
        imagefilledrectangle($im, 0, max(0, $h - $barH), $w, max(0, $h - $barH) + 3, $barTopLine);
        $txtCol = imagecolorallocatealpha($im, $tr, $tg, $tb, $gdAlphaText);
        $textY = max(20, $h - $barH + $padY + (int)round($ptSize * 1.05));
        @imagettftext($im, $ptSize, 0.0, $padX, $textY, $txtCol, $fontBold, $text);
        if ($subtext !== '') {
            $subCol = imagecolorallocatealpha($im, $ar, $ag, $ab, min(120, $gdAlphaText + 10));
            @imagettftext($im, max(10.0, $ptSize * 0.72), 0.0, $padX, $textY + (int)round($ptSize * 1.05), $subCol, $fontMed, $subtext);
        }
    } else {
        $bbox = @imagettfbbox($ptSize, 0.0, $fontBold, $text);
        if (is_array($bbox)) {
            $txtW = abs($bbox[2] - $bbox[0]);
            $txtH = abs($bbox[7] - $bbox[1]);
            $padX = max(14, (int)round($ptSize * 0.85));
            $padY = max(8, (int)round($ptSize * 0.48));
            $dotSpace = ($style === 'glass-pill') ? (int)round($ptSize * 0.85) : 0;
            $boxW = $txtW + ($padX * 2) + $dotSpace;
            $boxH = $txtH + ($padY * 2);
            $margin = max(16, (int)round($baseDim * 0.028));

            $bx1 = str_ends_with($pos, 'left') ? $margin : max(8, $w - $boxW - $margin);
            $by1 = str_starts_with($pos, 'top') ? $margin : max(8, $h - $boxH - $margin);
            $bx2 = min($w - 4, $bx1 + $boxW);
            $by2 = min($h - 4, $by1 + $boxH);

            if ($style === 'glass-pill') {
                $bgCol = imagecolorallocatealpha($im, 7, 16, 22, $gdAlphaBg);
                $borderCol = imagecolorallocatealpha($im, $ar, $ag, $ab, min(120, $gdAlphaText + 32));
                zeynart_gd_draw_rounded_rect($im, $bx1 - 1, $by1 - 1, $bx2 + 1, $by2 + 1, (int)floor($boxH / 2), $borderCol);
                zeynart_gd_draw_rounded_rect($im, $bx1, $by1, $bx2, $by2, (int)floor($boxH / 2), $bgCol);
                $dotCol = imagecolorallocatealpha($im, $ar, $ag, $ab, $gdAlphaText);
                $dotR = max(6, (int)round($ptSize * 0.44));
                imagefilledellipse($im, $bx1 + $padX + (int)floor($dotR / 2), (int)round(($by1 + $by2) / 2), $dotR, $dotR, $dotCol);
            } elseif ($style === 'brand-pill') {
                $bgCol = imagecolorallocatealpha($im, $ar, $ag, $ab, max(12, $gdAlphaText));
                zeynart_gd_draw_rounded_rect($im, $bx1, $by1, $bx2, $by2, (int)floor($boxH / 2), $bgCol);
            }

            $shadowCol = imagecolorallocatealpha($im, 3, 8, 12, min(122, $gdAlphaText + 26));
            $txtCol = imagecolorallocatealpha($im, $tr, $tg, $tb, $gdAlphaText);
            $tx = $bx1 + $padX + $dotSpace;
            $ty = $by1 + $padY + $txtH - (int)round($txtH * 0.12);
            if ($style === 'clean-shadow') {
                @imagettftext($im, $ptSize, 0.0, $tx + 2, $ty + 2, $shadowCol, $fontBold, $text);
            }
            @imagettftext($im, $ptSize, 0.0, $tx, $ty, $txtCol, $fontBold, $text);
        }
    }

    $ok = match ($mime) {
        'image/webp' => function_exists('imagewebp') ? @imagewebp($im, $absImagePath, 88) : false,
        'image/jpeg' => function_exists('imagejpeg') ? @imagejpeg($im, $absImagePath, 88) : false,
        'image/png' => function_exists('imagepng') ? @imagepng($im, $absImagePath, 6) : false,
        default => false,
    };
    unset($im);
    return (bool)$ok;
}

function zeynart_save_canvas_image(string $sourceUrl, string $dataUri, string $saveMode, string $altText, array &$store): array
{
    if (!preg_match('~^data:image/(webp|png|jpeg);base64,(.+)$~is', trim($dataUri), $m)) {
        return ['ok' => false, 'error' => 'Geçersiz kanvas görsel verisi alındı.'];
    }
    $rawBytes = base64_decode($m[2], true);
    if (!is_string($rawBytes) || strlen($rawBytes) < 64) {
        return ['ok' => false, 'error' => 'Görsel verisi çözümlenemedi.'];
    }
    if (strlen($rawBytes) > 18 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Düzenlenen görsel boyutu çok büyük (Maks. 18 MB).'];
    }
    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        return ['ok' => false, 'error' => 'Sunucuda GD WebP motoru aktif değil.'];
    }

    $gd = @imagecreatefromstring($rawBytes);
    if ($gd === false) {
        return ['ok' => false, 'error' => 'Kanvas görseli GD tarafından işlenemedi.'];
    }
    imagepalettetotruecolor($gd);
    imagealphablending($gd, true);
    imagesavealpha($gd, true);
    $w = imagesx($gd);
    $h = imagesy($gd);

    $cleanSource = '/' . ltrim(str_replace('\\', '/', trim(strtok($sourceUrl, '?') ?: '')), '/');
    if (str_contains($cleanSource, '..')) {
        unset($gd);
        return ['ok' => false, 'error' => 'Geçersiz kaynak dosya yolu.'];
    }

    $canOverwrite = (
        $saveMode === 'overwrite'
        && str_starts_with($cleanSource, '/uploads/')
        && preg_match('~^/uploads/[A-Za-z0-9_\-\./]+\.(webp|jpe?g|png)$~i', $cleanSource)
        && is_file(ROOT_DIR . $cleanSource)
    );

    if ($canOverwrite) {
        $destAbs = ROOT_DIR . $cleanSource;
        $extLower = strtolower(pathinfo($destAbs, PATHINFO_EXTENSION));
        $saved = match ($extLower) {
            'jpg', 'jpeg' => @imagejpeg($gd, $destAbs, 90),
            'png' => @imagepng($gd, $destAbs, 6),
            default => @imagewebp($gd, $destAbs, 90),
        };
        unset($gd);
        if (!$saved) {
            return ['ok' => false, 'error' => 'Dosya üzerine yazılamadı. Klasör yazma izinlerini kontrol edin.'];
        }
        clearstatcache(true, $destAbs);
        $relUrl = $cleanSource;
    } else {
        $origBase = slugify(pathinfo($cleanSource, PATHINFO_FILENAME) ?: 'st-tasarim');
        if ($origBase === '') {
            $origBase = 'st-tasarim';
        }
        if (!str_ends_with($origBase, '-st')) {
            $origBase .= '-st';
        }
        $subDir = date('Y') . '/' . date('m') . '/' . date('d');
        $targetDir = UPLOADS_DIR . '/' . $subDir;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $finalName = $origBase . '.webp';
        $counter = 1;
        while (is_file($targetDir . '/' . $finalName)) {
            $finalName = $origBase . '-' . (++$counter) . '.webp';
        }
        $destAbs = $targetDir . '/' . $finalName;
        $saved = @imagewebp($gd, $destAbs, 90);
        unset($gd);
        if (!$saved) {
            return ['ok' => false, 'error' => 'Yeni görsel /uploads klasörüne kaydedilemedi.'];
        }
        $relUrl = '/uploads/' . $subDir . '/' . $finalName;
    }

    $cleanAlt = trim($altText);
    if ($cleanAlt === '') {
        $cleanAlt = function_exists('humanize_media_filename_to_alt')
            ? humanize_media_filename_to_alt(basename($destAbs))
            : 'Kurumsal Görsel';
    }
    if (!isset($store['media_meta']) || !is_array($store['media_meta'])) {
        $store['media_meta'] = [];
    }
    $store['media_meta'][$relUrl] = [
        'alt' => $cleanAlt,
        'updated_at' => date('c'),
    ];

    $fSize = (int)(@filesize($destAbs) ?: 0);
    $fMtime = max(time(), (int)(@filemtime($destAbs) ?: time()));
    return [
        'ok' => true,
        'url' => $relUrl,
        'mtime' => $fMtime,
        'cache_busted_url' => $relUrl . '?v=' . $fMtime,
        'name' => basename($destAbs),
        'size' => $fSize,
        'width' => $w,
        'height' => $h,
        'alt' => $cleanAlt,
        'overwritten' => $canOverwrite,
    ];
}

/* ============================================================================
 * 3. DYNAMIC BRANDED OPENGRAPH GENERATOR (1200x630 GD) & SITE SEO DOCTOR
 * ============================================================================ */

function zeynart_get_branded_og_config(array $store): array
{
    $raw = is_array($store['seo']['branded_og'] ?? null) ? $store['seo']['branded_og'] : [];
    $mode = (string)($raw['mode'] ?? 'fallback');
    if (!in_array($mode, ['fallback', 'all', 'off'], true)) {
        $mode = 'fallback';
    }
    $domainHost = parse_url((string)($store['seo']['canonical_domain'] ?? SITE_URL), PHP_URL_HOST) ?: 'zeynart.com';
    $siteName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';

    return [
        'mode' => $mode,
        'domain_badge' => mb_substr(trim((string)($raw['domain_badge'] ?? $domainHost)) ?: $domainHost, 0, 80, 'UTF-8'),
        'eyebrow_default' => mb_substr(trim((string)($raw['eyebrow_default'] ?? ($siteName . ' · DİJİTAL STÜDYO'))), 0, 80, 'UTF-8'),
        'footer_tagline' => mb_substr(trim((string)($raw['footer_tagline'] ?? (string)($store['site']['tagline'] ?? 'Kurumsal Web Tasarım & Dijital Çözümler'))), 0, 100, 'UTF-8'),
        'overlay_opacity' => max(45, min(92, (int)($raw['overlay_opacity'] ?? 76))),
        'show_domain_pill' => ($raw['show_domain_pill'] ?? true) !== false,
    ];
}

function zeynart_gd_wrap_lines(string $text, float $ptSize, string $fontFile, int $maxWidthPx, int $maxLines = 3): array
{
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    if ($words === []) {
        return [];
    }
    $lines = [];
    $current = '';
    foreach ($words as $w) {
        $cand = $current === '' ? $w : ($current . ' ' . $w);
        $bbox = @imagettfbbox($ptSize, 0.0, $fontFile, $cand);
        $candW = is_array($bbox) ? abs($bbox[2] - $bbox[0]) : (mb_strlen($cand, 'UTF-8') * (int)$ptSize);
        if ($candW <= $maxWidthPx || $current === '') {
            $current = $cand;
        } else {
            $lines[] = $current;
            $current = $w;
            if (count($lines) >= $maxLines - 1) {
                break;
            }
        }
    }
    $consumedWords = count($lines) > 0 ? count(explode(' ', implode(' ', $lines))) : 0;
    if (count($lines) >= $maxLines - 1 && $consumedWords < count($words)) {
        $rem = implode(' ', array_slice($words, $consumedWords));
        while (mb_strlen($rem, 'UTF-8') > 8) {
            $bbox = @imagettfbbox($ptSize, 0.0, $fontFile, $rem . '…');
            $rw = is_array($bbox) ? abs($bbox[2] - $bbox[0]) : 0;
            if ($rw <= $maxWidthPx) {
                break;
            }
            $rem = rtrim(mb_substr($rem, 0, mb_strlen($rem, 'UTF-8') - 2, 'UTF-8'));
        }
        $lines[] = rtrim($rem, ' .,;:-') . (count($words) > ($consumedWords + count(explode(' ', $rem))) ? '…' : '');
    } elseif ($current !== '') {
        $lines[] = $current;
    }

    return array_slice($lines, 0, $maxLines);
}

function zeynart_generate_branded_og_jpeg(array $params, array $store, bool $forceRegenerate = false): ?string
{
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext') || !function_exists('imagejpeg')) {
        return null;
    }
    $fontBold = zeynart_gd_font_path(true);
    $fontMed = zeynart_gd_font_path(false) ?? $fontBold;
    if ($fontBold === null) {
        return null;
    }

    $ogCfg = zeynart_get_branded_og_config($store);
    $brand = function_exists('zeynart_get_theme_brand') ? zeynart_get_theme_brand($store) : [
        'primary_color' => '#16b7c5',
        'accent_color' => '#14b8a6',
        'bright_color' => '#5eead4',
        'bg_color' => '#070f12',
    ];
    $siteName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';

    $title = trim((string)($params['title'] ?? ''));
    // Strip trailing "| Brand" suffix so H1 looks clean on card
    $title = preg_replace('/\s*[\|\-–—]\s*' . preg_quote($siteName, '/') . '.*$/iu', '', $title) ?? $title;
    if ($title === '') {
        $title = (string)($store['seo']['default_title'] ?? ($siteName . ' Dijital Stüdyo'));
    }
    $eyebrow = mb_strtoupper(trim((string)($params['eyebrow'] ?? '')) ?: $ogCfg['eyebrow_default'], 'UTF-8');
    $subtitle = trim((string)($params['subtitle'] ?? '')) ?: $ogCfg['footer_tagline'];
    $bgLocal = isset($params['bg_local']) && is_string($params['bg_local']) ? trim($params['bg_local']) : '';
    $bgAbs = ($bgLocal !== '' && !str_contains($bgLocal, '..') && is_file(ROOT_DIR . $bgLocal)) ? (ROOT_DIR . $bgLocal) : '';
    $bgMtime = $bgAbs !== '' ? (int)@filemtime($bgAbs) : 0;

    $cacheDir = UPLOADS_DIR . '/og';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    $hashInput = json_encode([
        'v' => 2,
        't' => $title,
        'e' => $eyebrow,
        's' => $subtitle,
        'bg' => $bgLocal,
        'bgm' => $bgMtime,
        'dom' => $ogCfg['domain_badge'],
        'op' => $ogCfg['overlay_opacity'],
        'pill' => $ogCfg['show_domain_pill'],
        'pc' => $brand['primary_color'] ?? '#16b7c5',
        'ac' => $brand['accent_color'] ?? '#14b8a6',
        'bgc' => $brand['bg_color'] ?? '#070f12',
        'sn' => $siteName,
    ], JSON_UNESCAPED_UNICODE);
    $slugHint = substr(slugify($title) ?: 'og-kart', 0, 32);
    $cacheFile = $cacheDir . '/branded-' . $slugHint . '-' . substr(sha1((string)$hashInput), 0, 12) . '.jpg';

    if (!$forceRegenerate && is_file($cacheFile) && filesize($cacheFile) > 1000) {
        return $cacheFile;
    }

    $W = 1200;
    $H = 630;
    $im = imagecreatetruecolor($W, $H);
    [$bgr, $bgg, $bgb] = zeynart_hex_rgb_tuple((string)($brand['bg_color'] ?? '#070f12'), [7, 15, 18]);
    [$pr, $pg, $pb] = zeynart_hex_rgb_tuple((string)($brand['primary_color'] ?? '#16b7c5'), [22, 183, 197]);
    [$ar, $ag, $ab] = zeynart_hex_rgb_tuple((string)($brand['accent_color'] ?? '#14b8a6'), [20, 184, 166]);
    [$brr, $brg, $brb] = zeynart_hex_rgb_tuple((string)($brand['bright_color'] ?? '#5eead4'), [94, 234, 212]);

    $bgSolid = imagecolorallocate($im, $bgr, $bgg, $bgb);
    imagefill($im, 0, 0, $bgSolid);
    imagealphablending($im, true);

    // Optional background photo with dark corporate scrim
    $drewPhoto = false;
    if ($bgAbs !== '' && !str_ends_with(strtolower($bgAbs), 'opengraph-image.png')) {
        $info = @getimagesize($bgAbs);
        $mime = is_array($info) ? strtolower((string)($info['mime'] ?? '')) : '';
        $src = match ($mime) {
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($bgAbs) : false,
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($bgAbs) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($bgAbs) : false,
            default => false,
        };
        if ($src !== false) {
            $sw = imagesx($src);
            $sh = imagesy($src);
            if ($sw >= 360 && $sh >= 200) {
                $dstRatio = $W / $H;
                $srcRatio = $sw / $sh;
                if ($srcRatio > $dstRatio) {
                    $ch = $sh;
                    $cw = (int)round($sh * $dstRatio);
                    $cx = (int)floor(($sw - $cw) / 2);
                    $cy = 0;
                } else {
                    $cw = $sw;
                    $ch = (int)round($sw / $dstRatio);
                    $cx = 0;
                    $cy = (int)floor(($sh - $ch) * 0.35);
                }
                imagecopyresampled($im, $src, 0, 0, $cx, $cy, $W, $H, max(1, $cw), max(1, $ch));
                $drewPhoto = true;
            }
            unset($src);
        }
    }

    if ($drewPhoto) {
        $scrimAlpha = max(14, min(70, (int)round((100 - $ogCfg['overlay_opacity']) * 1.15)));
        $scrimCol = imagecolorallocatealpha($im, $bgr, $bgg, $bgb, $scrimAlpha);
        imagefilledrectangle($im, 0, 0, $W, $H, $scrimCol);
        // Extra left-to-bottom readability vignette
        $deepLeft = imagecolorallocatealpha($im, max(0, $bgr - 3), max(0, $bgg - 4), max(0, $bgb - 4), 38);
        imagefilledrectangle($im, 0, 0, 820, $H, $deepLeft);
    } else {
        // Luxury Apple Glass ambient brand glow mesh
        for ($step = 6; $step >= 1; $step--) {
            $radW = 220 + ($step * 145);
            $radH = 160 + ($step * 95);
            $glowA = imagecolorallocatealpha($im, $pr, $pg, $pb, 114 + $step);
            imagefilledellipse($im, 1020, 130, $radW, $radH, $glowA);
            $glowB = imagecolorallocatealpha($im, $ar, $ag, $ab, 116 + $step);
            imagefilledellipse($im, 180, 540, (int)($radW * 0.85), (int)($radH * 0.85), $glowB);
        }
    }

    // Subtle glass frame & top brand bar
    $cardBorder = imagecolorallocatealpha($im, $brr, $brg, $brb, 98);
    $cardFill = imagecolorallocatealpha($im, max(4, $bgr + 4), max(10, $bgg + 8), max(14, $bgb + 10), $drewPhoto ? 56 : 36);
    zeynart_gd_draw_rounded_rect($im, 42, 40, $W - 42, $H - 40, 28, $cardBorder);
    zeynart_gd_draw_rounded_rect($im, 44, 42, $W - 44, $H - 42, 27, $cardFill);

    $accentSolid = imagecolorallocate($im, $pr, $pg, $pb);
    imagefilledrectangle($im, 84, 42, 290, 48, $accentSolid);

    // Eyebrow capsule pill
    $eyeSize = 15.0;
    $eyeBox = @imagettfbbox($eyeSize, 0.0, $fontBold, $eyebrow);
    $eyeW = is_array($eyeBox) ? abs($eyeBox[2] - $eyeBox[0]) : 220;
    $pillBg = imagecolorallocatealpha($im, $pr, $pg, $pb, 102);
    $pillBorder = imagecolorallocatealpha($im, $brr, $brg, $brb, 74);
    zeynart_gd_draw_rounded_rect($im, 84, 88, 84 + $eyeW + 46, 130, 21, $pillBorder);
    zeynart_gd_draw_rounded_rect($im, 85, 89, 84 + $eyeW + 45, 129, 20, $pillBg);
    $dotBright = imagecolorallocate($im, $brr, $brg, $brb);
    imagefilledellipse($im, 104, 109, 9, 9, $dotBright);
    @imagettftext($im, $eyeSize, 0.0, 118, 115, $dotBright, $fontBold, $eyebrow);

    // Adaptive H1 Title (scales smoothly for short vs long headlines)
    $len = mb_strlen($title, 'UTF-8');
    $titlePt = $len <= 38 ? 48.0 : ($len <= 68 ? 41.0 : 35.0);
    $lines = zeynart_gd_wrap_lines($title, $titlePt, $fontBold, 1010, 3);
    $white = imagecolorallocate($im, 248, 252, 253);
    $shadow = imagecolorallocatealpha($im, 2, 6, 10, 65);
    $lineHeight = (int)round($titlePt * 1.34);
    $startY = count($lines) === 1 ? 265 : (count($lines) === 2 ? 235 : 212);

    foreach ($lines as $i => $ln) {
        $ly = $startY + ($i * $lineHeight);
        @imagettftext($im, $titlePt, 0.0, 86, $ly + 2, $shadow, $fontBold, $ln);
        @imagettftext($im, $titlePt, 0.0, 84, $ly, $white, $fontBold, $ln);
    }

    // Bottom Divider & Signature Strip
    $divCol = imagecolorallocatealpha($im, $brr, $brg, $brb, 104);
    imagefilledrectangle($im, 84, 488, $W - 84, 489, $divCol);

    $mutedCol = imagecolorallocate($im, 178, 206, 214);
    @imagettftext($im, 20.0, 0.0, 84, 536, $white, $fontBold, $siteName);
    $shortSub = mb_strlen($subtitle, 'UTF-8') > 68 ? (mb_substr($subtitle, 0, 66, 'UTF-8') . '…') : $subtitle;
    @imagettftext($im, 14.5, 0.0, 84, 564, $mutedCol, $fontMed, $shortSub);

    if (!empty($ogCfg['show_domain_pill']) && $ogCfg['domain_badge'] !== '') {
        $domTxt = $ogCfg['domain_badge'];
        $domBox = @imagettfbbox(16.0, 0.0, $fontBold, $domTxt);
        $domW = is_array($domBox) ? abs($domBox[2] - $domBox[0]) : 160;
        $dpW = $domW + 48;
        $dx1 = $W - 84 - $dpW;
        $dx2 = $W - 84;
        $domBg = imagecolorallocatealpha($im, $pr, $pg, $pb, 22);
        zeynart_gd_draw_rounded_rect($im, $dx1, 514, $dx2, 562, 24, $domBg);
        $darkOnBtn = imagecolorallocate($im, 4, 18, 22);
        @imagettftext($im, 16.0, 0.0, $dx1 + 24, 544, $darkOnBtn, $fontBold, $domTxt);
    }

    imageinterlace($im, true);
    $jpegBytes = '';
    foreach ([86, 78, 70] as $q) {
        ob_start();
        imagejpeg($im, null, $q);
        $jpegBytes = (string)ob_get_clean();
        if (strlen($jpegBytes) <= 265000) {
            break;
        }
    }
    unset($im);

    if ($jpegBytes === '') {
        return null;
    }
    $tmp = $cacheFile . '.tmp.' . bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, $jpegBytes, LOCK_EX) !== false) {
        if (!@rename($tmp, $cacheFile)) {
            @copy($tmp, $cacheFile);
            @unlink($tmp);
        }
    }
    return is_file($cacheFile) ? $cacheFile : null;
}

function zeynart_scan_uploads_for_doctor(array $store): array
{
    $mediaMeta = is_array($store['media_meta'] ?? null) ? $store['media_meta'] : [];
    $total = 0;
    $missing = [];
    if (!is_dir(UPLOADS_DIR)) {
        return ['total' => 0, 'missing' => []];
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(UPLOADS_DIR, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }
        $abs = str_replace('\\', '/', $fileInfo->getPathname());
        $relInside = ltrim(substr($abs, strlen(str_replace('\\', '/', UPLOADS_DIR))), '/');
        if (str_starts_with($relInside, 'og/')) {
            continue;
        }
        $ext = strtolower($fileInfo->getExtension());
        if (!in_array($ext, ['webp', 'jpg', 'jpeg', 'png', 'avif', 'svg', 'gif'], true)) {
            continue;
        }
        $total++;
        $url = '/uploads/' . $relInside;
        $alt = trim((string)($mediaMeta[$url]['alt'] ?? ''));
        if ($alt === '') {
            $missing[] = [
                'url' => $url,
                'name' => $fileInfo->getFilename(),
                'suggested_alt' => function_exists('humanize_media_filename_to_alt')
                    ? humanize_media_filename_to_alt($fileInfo->getFilename())
                    : 'Kurumsal Görsel',
            ];
        }
    }
    return ['total' => $total, 'missing' => $missing];
}

function zeynart_run_seo_doctor_scan(array $store): array
{
    $issues = [];
    $seoPages = is_array($store['seo']['pages'] ?? null) ? $store['seo']['pages'] : [];
    $coreKeys = [
        'anasayfa' => 'Ana Sayfa (/)',
        'hakkimizda' => 'Hakkımızda',
        'hizmetler' => 'Hizmetler',
        'projeler' => 'Projeler',
        'blog' => 'Blog Arşivi',
        'iletisim' => 'İletişim',
    ];

    $pagesChecked = 0;
    $pagesHealthy = 0;

    foreach ($coreKeys as $k => $label) {
        if (function_exists('zeynart_page_status') && zeynart_page_status($store, $k) !== 'published') {
            continue;
        }
        $pagesChecked++;
        $t = trim((string)($seoPages[$k]['meta_title'] ?? $seoPages[$k]['title'] ?? ''));
        $d = trim((string)($seoPages[$k]['meta_desc'] ?? $seoPages[$k]['description'] ?? ''));
        $ok = true;
        if ($t === '') {
            $ok = false;
            $issues[] = [
                'severity' => 'warn',
                'category' => 'Sayfa Meta',
                'target' => $label,
                'edit_hint' => 'page:' . $k,
                'message' => 'Özel SEO başlığı (Meta Title) tanımlanmamış; varsayılan başlık kullanılıyor.',
            ];
        }
        if ($d === '' || mb_strlen($d, 'UTF-8') < 50) {
            $ok = false;
            $issues[] = [
                'severity' => 'warn',
                'category' => 'Sayfa Meta',
                'target' => $label,
                'edit_hint' => 'page:' . $k,
                'message' => $d === '' ? 'Meta açıklama (Description) boş bırakılmış.' : 'Meta açıklama çok kısa (50 karakterden az).',
            ];
        }
        if ($ok) {
            $pagesHealthy++;
        }
    }

    foreach (($store['services'] ?? []) as $srv) {
        if (!is_array($srv)) {
            continue;
        }
        $slug = (string)($srv['slug'] ?? '');
        if ($slug === '' || (function_exists('zeynart_page_status') && zeynart_page_status($store, 'service:' . $slug) !== 'published')) {
            continue;
        }
        $pagesChecked++;
        $sSeo = is_array($srv['seo'] ?? null) ? $srv['seo'] : [];
        $mt = trim((string)($sSeo['meta_title'] ?? $srv['meta_title'] ?? ''));
        $md = trim((string)($sSeo['meta_desc'] ?? $srv['meta_desc'] ?? $srv['description'] ?? $srv['short'] ?? ''));
        if ($mt === '' || mb_strlen($md, 'UTF-8') < 45) {
            $issues[] = [
                'severity' => 'info',
                'category' => 'Hizmet SEO',
                'target' => 'Hizmet: ' . (string)($srv['title'] ?? $slug),
                'edit_hint' => 'service:' . $slug,
                'message' => 'Hizmet alt sayfasında özel meta başlık veya açıklama optimize edilebilir.',
            ];
        } else {
            $pagesHealthy++;
        }
    }

    foreach (($store['custom_pages'] ?? []) as $cp) {
        if (!is_array($cp) || (($cp['status'] ?? 'published') !== 'published')) {
            continue;
        }
        $pagesChecked++;
        $cSeo = is_array($cp['seo'] ?? null) ? $cp['seo'] : [];
        $mt = trim((string)($cSeo['meta_title'] ?? $cp['meta_title'] ?? ''));
        $md = trim((string)($cSeo['meta_desc'] ?? $cp['meta_desc'] ?? ''));
        if ($mt === '' || $md === '') {
            $issues[] = [
                'severity' => 'warn',
                'category' => 'Özel Sayfa SEO',
                'target' => 'Sayfa: ' . (string)($cp['title'] ?? $cp['slug'] ?? ''),
                'edit_hint' => 'custom:' . (string)($cp['id'] ?? ''),
                'message' => 'Özel sayfada SEO başlığı veya meta açıklama eksik.',
            ];
        } else {
            $pagesHealthy++;
        }
    }

    // Blog check
    $blogIndex = function_exists('load_blog_index') ? load_blog_index() : [];
    $postsTotal = count($blogIndex);
    $postsMissingExcerpt = 0;
    foreach ($blogIndex as $bp) {
        if (($bp['status'] ?? 'published') !== 'published') {
            continue;
        }
        if (trim((string)($bp['excerpt'] ?? '')) === '') {
            $postsMissingExcerpt++;
        }
    }
    if ($postsMissingExcerpt > 0) {
        $issues[] = [
            'severity' => 'warn',
            'category' => 'Blog SEO',
            'target' => $postsMissingExcerpt . ' Blog Yazısı',
            'edit_hint' => 'blog',
            'message' => $postsMissingExcerpt . ' adet blog yazısında özet / meta açıklama alanı boş.',
        ];
    }

    // Media Alt Check
    $mediaScan = zeynart_scan_uploads_for_doctor($store);
    $missingAltCount = count($mediaScan['missing']);
    if ($missingAltCount > 0) {
        $issues[] = [
            'severity' => 'warn',
            'category' => 'Görsel Alt Etiketi',
            'target' => '/uploads/ (' . $missingAltCount . ' Görsel)',
            'edit_hint' => 'media_missing_alt',
            'message' => $missingAltCount . ' adet yüklenen görselde SEO alt etiketi tanımlı değil.',
        ];
    }

    $canon = trim((string)($store['seo']['canonical_domain'] ?? ''));
    if ($canon === '') {
        $issues[] = [
            'severity' => 'warn',
            'category' => 'Teknik SEO',
            'target' => 'Canonical Alan Adı',
            'edit_hint' => 'seo_general',
            'message' => 'Kanonik (Canonical) alan adı tanımlanmamış.',
        ];
    }

    $pageRatio = $pagesChecked > 0 ? ($pagesHealthy / $pagesChecked) : 1.0;
    $mediaRatio = $mediaScan['total'] > 0 ? (($mediaScan['total'] - $missingAltCount) / $mediaScan['total']) : 1.0;
    $blogRatio = $postsTotal > 0 ? (($postsTotal - $postsMissingExcerpt) / $postsTotal) : 1.0;
    $techScore = ($canon !== '' ? 1.0 : 0.65);

    $overall = (int)round(($pageRatio * 35) + ($mediaRatio * 30) + ($blogRatio * 20) + ($techScore * 15));
    $overall = max(25, min(100, $overall));

    return [
        'score' => $overall,
        'pages_checked' => $pagesChecked,
        'pages_healthy' => $pagesHealthy,
        'posts_total' => $postsTotal,
        'posts_missing_excerpt' => $postsMissingExcerpt,
        'media_total' => $mediaScan['total'],
        'media_missing_alt' => $missingAltCount,
        'missing_alt_samples' => array_slice($mediaScan['missing'], 0, 12),
        'issues_count' => count($issues),
        'issues' => array_slice($issues, 0, 40),
        'checked_at' => date('Y-m-d H:i:s'),
    ];
}

function zeynart_run_seo_doctor_autofix(array &$store): array
{
    $siteName = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
    $tagline = trim((string)($store['site']['tagline'] ?? 'Kurumsal Dijital Çözümler'));
    $fixedMeta = 0;
    $fixedMediaAlts = 0;
    $fixedBlog = 0;

    if (!isset($store['seo']) || !is_array($store['seo'])) {
        $store['seo'] = [];
    }
    if (trim((string)($store['seo']['canonical_domain'] ?? '')) === '') {
        $store['seo']['canonical_domain'] = rtrim(SITE_URL, '/');
        $fixedMeta++;
    }
    if (!isset($store['seo']['pages']) || !is_array($store['seo']['pages'])) {
        $store['seo']['pages'] = [];
    }

    $coreFallbacks = [
        'anasayfa' => [
            'title' => $siteName . ' | ' . $tagline,
            'description' => $siteName . ' ile kurumsal web tasarım, hızlı yazılım mimarisi, SEO ve yapay zeka uyumlu dijital büyüme çözümlerini keşfedin.',
        ],
        'hakkimizda' => [
            'title' => 'Hakkımızda & Kurumsal Yaklaşımımız | ' . $siteName,
            'description' => $siteName . ' ekibinin kurumsal tasarım vizyonu, çalışma disiplini, referans markaları ve sürdürülebilir teknoloji standartları.',
        ],
        'hizmetler' => [
            'title' => 'Hizmetlerimiz & Uzmanlık Alanlarımız | ' . $siteName,
            'description' => 'Markanızın dijital görünürlüğünü ve dönüşüm oranlarını artıran kurumsal web tasarım, özel yazılım ve SEO hizmetlerimiz.',
        ],
        'projeler' => [
            'title' => 'Projeler & Seçilmiş Çalışmalar | ' . $siteName,
            'description' => $siteName . ' tarafından farklı sektörlerdeki markalar için geliştirilen yüksek performanslı kurumsal projeler ve vaka analizleri.',
        ],
        'blog' => [
            'title' => 'Blog & Dijital İçgörüler | ' . $siteName,
            'description' => 'Web tasarım, teknik SEO, performans optimizasyonu ve yapay zeka arama motorları hakkında güncel rehber ve makaleler.',
        ],
        'iletisim' => [
            'title' => 'İletişim & Proje Teklif Formu | ' . $siteName,
            'description' => 'Yeni projeniz için ' . $siteName . ' ekibiyle hemen iletişime geçin; telefon, WhatsApp veya teklif formu üzerinden hızlı dönüş alın.',
        ],
    ];

    foreach ($coreFallbacks as $k => $fb) {
        if (!isset($store['seo']['pages'][$k]) || !is_array($store['seo']['pages'][$k])) {
            $store['seo']['pages'][$k] = [];
        }
        if (trim((string)($store['seo']['pages'][$k]['meta_title'] ?? $store['seo']['pages'][$k]['title'] ?? '')) === '') {
            $store['seo']['pages'][$k]['meta_title'] = $fb['title'];
            $fixedMeta++;
        }
        $curD = trim((string)($store['seo']['pages'][$k]['meta_desc'] ?? $store['seo']['pages'][$k]['description'] ?? ''));
        if ($curD === '' || mb_strlen($curD, 'UTF-8') < 50) {
            $store['seo']['pages'][$k]['meta_desc'] = $fb['description'];
            $fixedMeta++;
        }
    }

    if (isset($store['services']) && is_array($store['services'])) {
        foreach ($store['services'] as $idx => $srv) {
            if (!is_array($srv)) {
                continue;
            }
            $sTitle = trim((string)($srv['title'] ?? 'Kurumsal Hizmet'));
            $sSum = trim((string)($srv['description'] ?? $srv['short'] ?? $srv['summary'] ?? ''));
            if (!isset($store['services'][$idx]['seo']) || !is_array($store['services'][$idx]['seo'])) {
                $store['services'][$idx]['seo'] = [];
            }
            if (trim((string)($store['services'][$idx]['seo']['meta_title'] ?? $srv['meta_title'] ?? '')) === '') {
                $store['services'][$idx]['seo']['meta_title'] = $sTitle . ' | ' . $siteName;
                $fixedMeta++;
            }
            $curMd = trim((string)($store['services'][$idx]['seo']['meta_desc'] ?? $srv['meta_desc'] ?? ''));
            if ($curMd === '' || mb_strlen($curMd, 'UTF-8') < 45) {
                $baseDesc = $sSum !== '' && mb_strlen($sSum, 'UTF-8') >= 50
                    ? $sSum
                    : ($sTitle . ' alanında ' . $siteName . ' güvencesiyle hızlı, ölçülebilir ve kurumsal ihtiyaçlarınıza özel profesyonel çözümler sunuyoruz.');
                $store['services'][$idx]['seo']['meta_desc'] = mb_substr($baseDesc, 0, 165, 'UTF-8');
                $fixedMeta++;
            }
        }
    }

    if (isset($store['custom_pages']) && is_array($store['custom_pages'])) {
        foreach ($store['custom_pages'] as $idx => $cp) {
            if (!is_array($cp)) {
                continue;
            }
            $cpTitle = trim((string)($cp['title'] ?? 'Kurumsal Sayfa'));
            if (!isset($store['custom_pages'][$idx]['seo']) || !is_array($store['custom_pages'][$idx]['seo'])) {
                $store['custom_pages'][$idx]['seo'] = [];
            }
            if (trim((string)($store['custom_pages'][$idx]['seo']['meta_title'] ?? $cp['meta_title'] ?? '')) === '') {
                $store['custom_pages'][$idx]['seo']['meta_title'] = $cpTitle . ' | ' . $siteName;
                $fixedMeta++;
            }
            if (trim((string)($store['custom_pages'][$idx]['seo']['meta_desc'] ?? $cp['meta_desc'] ?? '')) === '') {
                $sub = trim((string)($cp['excerpt'] ?? $cp['hero_subtitle'] ?? ''));
                $store['custom_pages'][$idx]['seo']['meta_desc'] = $sub !== '' && mb_strlen($sub, 'UTF-8') >= 45
                    ? mb_substr($sub, 0, 165, 'UTF-8')
                    : ($cpTitle . ' hakkında detaylı kurumsal bilgiler ve ' . $siteName . ' profesyonel hizmet standartları.');
                $fixedMeta++;
            }
        }
    }

    // Auto-fill all missing /uploads/ media alt tags
    $mediaScan = zeynart_scan_uploads_for_doctor($store);
    if (!isset($store['media_meta']) || !is_array($store['media_meta'])) {
        $store['media_meta'] = [];
    }
    foreach ($mediaScan['missing'] as $mItem) {
        $store['media_meta'][$mItem['url']] = [
            'alt' => $mItem['suggested_alt'],
            'updated_at' => date('c'),
        ];
        $fixedMediaAlts++;
    }

    // Fix blog posts missing excerpt in index + post JSON if any
    if (function_exists('load_blog_index') && function_exists('get_post') && function_exists('save_post')) {
        $bIndex = load_blog_index();
        foreach ($bIndex as $bp) {
            $slug = (string)($bp['slug'] ?? '');
            if ($slug !== '' && trim((string)($bp['excerpt'] ?? '')) === '') {
                $full = get_post($slug);
                if (is_array($full)) {
                    $plain = trim(strip_tags((string)($full['content_html'] ?? '')));
                    $full['excerpt'] = $plain !== ''
                        ? mb_substr(preg_replace('/\s+/u', ' ', $plain) ?? $plain, 0, 160, 'UTF-8')
                        : ((string)($full['title'] ?? $slug) . ' — ' . $siteName . ' kapsamlı rehberi.');
                    save_post($full, $slug);
                    $fixedBlog++;
                }
            }
        }
    }

    return [
        'fixed_meta' => $fixedMeta,
        'fixed_media_alts' => $fixedMediaAlts,
        'fixed_blog' => $fixedBlog,
        'total_fixed' => $fixedMeta + $fixedMediaAlts + $fixedBlog,
    ];
}

/* ============================================================================
 * 4. INTERACTIVE MULTI-STEP QUOTE & COST WIZARD (teklif-sihirbazi)
 * ============================================================================ */

function zeynart_quote_wizard_presets(): array
{
    return [
        'web_agency' => [
            'name' => '💻 Web Tasarım, Yazılım & SEO Ajansı',
            'eyebrow' => 'İNTERAKTİF PROJE & MALİYET SİHİRBAZI',
            'heading' => 'Projenizin kapsamını seçin,',
            'heading_accent' => 'özel yol haritanızı hazırlayalım.',
            'lead' => 'İhtiyacınız olan hizmeti ve özellikleri 30 saniyede işaretleyin; tahmini bütçe aralığını görün ve size özel teklifimizi hemen alın.',
            'currency' => '₺',
            'show_price_estimate' => true,
            'categories' => [
                ['id' => 'cat-corp', 'icon' => '🏛️', 'title' => 'Kurumsal Web Sitesi', 'desc' => '%100 PageSpeed hedefli, yönetim panelli modern kurumsal vitrin', 'min' => 25000, 'max' => 42000],
                ['id' => 'cat-ecom', 'icon' => '🛒', 'title' => 'E-Ticaret & B2B Portal', 'desc' => 'Ödeme, katalog, bayi veya gelişmiş ürün yönetim altyapısı', 'min' => 45000, 'max' => 80000],
                ['id' => 'cat-seo', 'icon' => '🚀', 'title' => 'Teknik SEO & Yapay Zeka (GEO)', 'desc' => 'Google ve yapay zeka arama motorlarında üst sıra büyüme paketi', 'min' => 18000, 'max' => 32000],
                ['id' => 'cat-custom', 'icon' => '⚡', 'title' => 'Özel Web Yazılım / Revizyon', 'desc' => 'Mevcut sitenizi hızlandırma veya iş akışınıza özel modül geliştirme', 'min' => 30000, 'max' => 65000],
            ],
            'features' => [
                ['id' => 'ft-i18n', 'title' => 'Çoklu Dil Altyapısı (EN · DE · RU)', 'desc' => 'Otomatik çeviri ve hreflang uluslararası SEO yapısı', 'min' => 6000, 'max' => 10000],
                ['id' => 'ft-ai', 'title' => 'Yapay Zeka Blog & İçerik Stüdyosu', 'desc' => 'Tek tıkla SEO uyumlu makale ve SSS üretim modülü', 'min' => 4500, 'max' => 7500],
                ['id' => 'ft-crm', 'title' => 'Hızlı WhatsApp, CRM & Analitik', 'desc' => 'Çerezsiz canlı ziyaretçi takibi ve tek tıkla müşteri yanıtlama', 'min' => 4000, 'max' => 6500],
                ['id' => 'ft-brand', 'title' => 'Kurumsal Kimlik & Görsel Prodüksiyon', 'desc' => 'Logo yenileme, sosyal medya kartları ve özel görsel tasarım', 'min' => 8000, 'max' => 15000],
            ],
            'timelines' => [
                ['id' => 'tl-urgent', 'title' => 'Hemen Başlasın (Öncelikli)', 'desc' => '1 – 2 hafta içinde hızlı yayına geçiş'],
                ['id' => 'tl-normal', 'title' => 'Standart Planlama (2 – 4 Hafta)', 'desc' => 'Kapsamlı hazırlık ve içerik kurgusuyla teslim'],
                ['id' => 'tl-flex', 'title' => 'Araştırma & Bütçe Planlama', 'desc' => 'Ön görüşme ve teknik yol haritası almak istiyoruz'],
            ],
        ],
        'construction' => [
            'name' => '🏗️ İnşaat, Mimarlık & Anahtar Teslim Tadilat',
            'eyebrow' => 'MİMARİ PROJE & KEŞİF SİHİRBAZI',
            'heading' => 'Proje detaylarınızı seçin,',
            'heading_accent' => 'ücretsiz keşif planlayalım.',
            'lead' => 'Yapı türünü ve uygulama kapsamını belirleyin; mühendislik ekibimiz ön maliyet ve iş takvimini hazırlasın.',
            'currency' => '₺',
            'show_price_estimate' => false,
            'categories' => [
                ['id' => 'cat-villa', 'icon' => '🏡', 'title' => 'Konut & Villa Projesi', 'desc' => 'Mimari tasarım, ruhsat ve anahtar teslim uygulama', 'min' => 0, 'max' => 0],
                ['id' => 'cat-office', 'icon' => '🏢', 'title' => 'Ofis & Ticari Mağaza', 'desc' => 'Kurumsal iç mimari ve hızlı anahtar teslim kurulum', 'min' => 0, 'max' => 0],
                ['id' => 'cat-ind', 'icon' => '🏭', 'title' => 'Endüstriyel Tesis & Fabrika', 'desc' => 'Çelik konstrüksiyon, idari bina ve saha mühendisliği', 'min' => 0, 'max' => 0],
                ['id' => 'cat-reno', 'icon' => '🔨', 'title' => 'Kapsamlı Yenileme & Cephe', 'desc' => 'Dış cephe kaplama, güçlendirme ve modernizasyon', 'min' => 0, 'max' => 0],
            ],
            'features' => [
                ['id' => 'ft-3d', 'title' => '3D Mimari Modelleme & Render', 'desc' => 'Uygulama öncesi fotogerçekçi mekan sunumu', 'min' => 0, 'max' => 0],
                ['id' => 'ft-mech', 'title' => 'Mekanik & Elektrik Altyapı', 'desc' => 'Tüm tesisat ve akıllı bina otomasyon projeleri', 'min' => 0, 'max' => 0],
                ['id' => 'ft-furn', 'title' => 'Özel Üretim Sabit Mobilya', 'desc' => 'Mekana özel ölçü üretim ve montaj', 'min' => 0, 'max' => 0],
            ],
            'timelines' => [
                ['id' => 'tl-now', 'title' => 'Bu Ay Başlamak İstiyoruz', 'desc' => 'Yerimiz hazır, hızlı keşif talep ediyoruz'],
                ['id' => 'tl-q', 'title' => '1 – 3 Ay İçinde', 'desc' => 'Projelendirme ve teklif karşılaştırma aşamasındayız'],
                ['id' => 'tl-plan', 'title' => 'Yatırım Fizibilitesi', 'desc' => 'Ön maliyet ve teknik danışmanlık arıyoruz'],
            ],
        ],
        'industrial_b2b' => [
            'name' => '🏭 Endüstriyel Üretim, Makine & B2B Tedarik',
            'eyebrow' => 'HIZLI B2B TEKNİK TEKLİF SİHİRBAZI',
            'heading' => 'Üretim ve tedarik talebinizi',
            'heading_accent' => '4 adımda iletin.',
            'lead' => 'Ürün grubunu ve sipariş ölçeğini seçin; satış mühendislerimiz teknik şartname ve termin süresini paylaşsın.',
            'currency' => '$',
            'show_price_estimate' => false,
            'categories' => [
                ['id' => 'cat-std', 'icon' => '⚙️', 'title' => 'Standart Seri Ürün / Makine', 'desc' => 'Katalog ürünlerimizden hızlı stok ve üretim teslimi', 'min' => 0, 'max' => 0],
                ['id' => 'cat-oem', 'icon' => '📐', 'title' => 'Özel Ölçü / Proje Bazlı Üretim', 'desc' => 'Teknik resme veya numuneye özel mühendislik üretimi', 'min' => 0, 'max' => 0],
                ['id' => 'cat-exp', 'icon' => '🚢', 'title' => 'İhracat & Toptan Bayilik', 'desc' => 'Yurt dışı sevkiyat ve periyodik kontratlı tedarik', 'min' => 0, 'max' => 0],
                ['id' => 'cat-srv', 'icon' => '🔧', 'title' => 'Yedek Parça & Teknik Servis', 'desc' => 'Periyodik bakım, revizyon ve orijinal parça talebi', 'min' => 0, 'max' => 0],
            ],
            'features' => [
                ['id' => 'ft-install', 'title' => 'Yerinde Kurulum & Devreye Alma', 'desc' => 'Saha montajı ve operatör eğitimi dahil', 'min' => 0, 'max' => 0],
                ['id' => 'ft-cert', 'title' => 'CE / ISO Kalite & Test Sertifikaları', 'desc' => 'Uluslararası denetim ve malzeme raporları', 'min' => 0, 'max' => 0],
                ['id' => 'ft-ship', 'title' => 'Adrese / Limana Teslim Lojistik', 'desc' => 'Sigortalı nakliye ve gümrükleme organizasyonu', 'min' => 0, 'max' => 0],
            ],
            'timelines' => [
                ['id' => 'tl-imm', 'title' => 'Acil Termin / Stoktan', 'desc' => 'En kısa sürede sevkiyat gerekiyor'],
                ['id' => 'tl-30', 'title' => '30 Gün İçinde Teslim', 'desc' => 'Standart üretim planlamasına uygun'],
                ['id' => 'tl-year', 'title' => 'Yıllık Çerçeve Anlaşma', 'desc' => 'Düzenli parti sevkiyatı planlıyoruz'],
            ],
        ],
        'clinic_service' => [
            'name' => '🦷 Klinik, Danışmanlık & Kurumsal Hizmet',
            'eyebrow' => 'ÖN DEĞERLENDİRME & RANDEVU SİHİRBAZI',
            'heading' => 'İhtiyacınız olan uzmanlık alanını',
            'heading_accent' => 'birlikte planlayalım.',
            'lead' => 'Size en uygun hizmet başlığını ve zaman dilimini seçin; uzman danışmanlarımız telefon veya WhatsApp üzerinden dönüş yapsın.',
            'currency' => '₺',
            'show_price_estimate' => false,
            'categories' => [
                ['id' => 'cat-c1', 'icon' => '✨', 'title' => 'Kapsamlı Uzman Değerlendirmesi', 'desc' => 'Birebir analiz ve kişiye/kuruma özel planlama', 'min' => 0, 'max' => 0],
                ['id' => 'cat-c2', 'icon' => '📋', 'title' => 'Periyodik Danışmanlık & Takip', 'desc' => 'Düzenli aylık süreç yönetimi ve raporlama', 'min' => 0, 'max' => 0],
                ['id' => 'cat-c3', 'icon' => '🎯', 'title' => 'Hızlı Online Ön Görüşme', 'desc' => '15 dakikalık telefon / görüntülü bilgilendirme', 'min' => 0, 'max' => 0],
            ],
            'features' => [
                ['id' => 'ft-rep', 'title' => 'Detaylı Yazılı Yol Haritası', 'desc' => 'Görüşme sonrası kapsam ve süreç dökümanı', 'min' => 0, 'max' => 0],
                ['id' => 'ft-vip', 'title' => 'Öncelikli Randevu Planlaması', 'desc' => 'Mesai içi/sonu esnek saat seçeneği', 'min' => 0, 'max' => 0],
            ],
            'timelines' => [
                ['id' => 'tl-today', 'title' => 'Bugün / Yarın Aranmak İstiyorum', 'desc' => 'En uygun ilk saatte dönüş yapılsın'],
                ['id' => 'tl-week', 'title' => 'Bu Hafta İçinde', 'desc' => 'Uygun gün ve saat planlaması yapalım'],
            ],
        ],
    ];
}

function zeynart_get_quote_wizard_config(array $store): array
{
    $presets = zeynart_quote_wizard_presets();
    $def = $presets['web_agency'];
    $raw = is_array($store['quote_wizard'] ?? null) ? $store['quote_wizard'] : [];

    $cats = [];
    $rawCats = is_array($raw['categories'] ?? null) && $raw['categories'] !== [] ? $raw['categories'] : $def['categories'];
    foreach ($rawCats as $idx => $c) {
        if (!is_array($c) || trim((string)($c['title'] ?? '')) === '') {
            continue;
        }
        $cats[] = [
            'id' => preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($c['id'] ?? '')) ?: ('cat-' . ($idx + 1)),
            'icon' => mb_substr(trim((string)($c['icon'] ?? '⚡')) ?: '⚡', 0, 8, 'UTF-8'),
            'title' => mb_substr(trim((string)$c['title']), 0, 90, 'UTF-8'),
            'desc' => mb_substr(trim((string)($c['desc'] ?? '')), 0, 180, 'UTF-8'),
            'min' => max(0, (int)($c['min'] ?? 0)),
            'max' => max(0, (int)($c['max'] ?? 0)),
        ];
    }
    if ($cats === []) {
        $cats = $def['categories'];
    }

    $feats = [];
    $rawFeats = is_array($raw['features'] ?? null) ? $raw['features'] : $def['features'];
    foreach ($rawFeats as $idx => $f) {
        if (!is_array($f) || trim((string)($f['title'] ?? '')) === '') {
            continue;
        }
        $feats[] = [
            'id' => preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($f['id'] ?? '')) ?: ('ft-' . ($idx + 1)),
            'title' => mb_substr(trim((string)$f['title']), 0, 90, 'UTF-8'),
            'desc' => mb_substr(trim((string)($f['desc'] ?? '')), 0, 180, 'UTF-8'),
            'min' => max(0, (int)($f['min'] ?? 0)),
            'max' => max(0, (int)($f['max'] ?? 0)),
        ];
    }

    $tls = [];
    $rawTls = is_array($raw['timelines'] ?? null) && $raw['timelines'] !== [] ? $raw['timelines'] : $def['timelines'];
    foreach ($rawTls as $idx => $tl) {
        if (!is_array($tl) || trim((string)($tl['title'] ?? '')) === '') {
            continue;
        }
        $tls[] = [
            'id' => preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($tl['id'] ?? '')) ?: ('tl-' . ($idx + 1)),
            'title' => mb_substr(trim((string)$tl['title']), 0, 90, 'UTF-8'),
            'desc' => mb_substr(trim((string)($tl['desc'] ?? '')), 0, 160, 'UTF-8'),
        ];
    }
    if ($tls === []) {
        $tls = $def['timelines'];
    }

    return [
        'enabled' => ($raw['enabled'] ?? true) !== false,
        'show_on_home' => !empty($raw['show_on_home']),
        'show_on_services' => ($raw['show_on_services'] ?? true) !== false,
        'show_on_contact' => !empty($raw['show_on_contact']),
        'show_price_estimate' => ($raw['show_price_estimate'] ?? $def['show_price_estimate']) !== false,
        'currency' => mb_substr(trim((string)($raw['currency'] ?? $def['currency'])) ?: '₺', 0, 6, 'UTF-8'),
        'eyebrow' => mb_substr(trim((string)($raw['eyebrow'] ?? $def['eyebrow'])), 0, 90, 'UTF-8'),
        'heading' => mb_substr(trim((string)($raw['heading'] ?? $def['heading'])), 0, 120, 'UTF-8'),
        'heading_accent' => mb_substr(trim((string)($raw['heading_accent'] ?? $def['heading_accent'])), 0, 120, 'UTF-8'),
        'lead' => mb_substr(trim((string)($raw['lead'] ?? $def['lead'])), 0, 280, 'UTF-8'),
        'submit_label' => mb_substr(trim((string)($raw['submit_label'] ?? 'Teklif & Yol Haritasını Gönder')) ?: 'Teklif & Yol Haritasını Gönder', 0, 70, 'UTF-8'),
        'whatsapp_handoff' => ($raw['whatsapp_handoff'] ?? true) !== false,
        'categories' => $cats,
        'features' => $feats,
        'timelines' => $tls,
    ];
}

function zeynart_render_quote_wizard_section(array $store, string $context = 'section'): string
{
    $cfg = zeynart_get_quote_wizard_config($store);
    if (empty($cfg['enabled']) && $context !== 'page') {
        return '';
    }
    $csrf = function_exists('csrf_token') ? csrf_token() : '';
    $apiEndpoint = url('/api/teklif-sihirbazi');
    $uid = 'zqw_' . substr(md5($context . '|' . mt_rand()), 0, 6);
    $kvkkUrl = url((string)($store['cookie_consent']['kvkk_url'] ?? '/kvkk/'));
    $jsonCfg = json_encode([
        'currency' => $cfg['currency'],
        'show_price_estimate' => $cfg['show_price_estimate'],
        'whatsapp_handoff' => $cfg['whatsapp_handoff'],
        'api_url' => $apiEndpoint,
        'csrf_token' => $csrf,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

    ob_start();
    ?>
    <section class="zqw-section<?= $context === 'page' ? ' zqw-page-embed' : '' ?>" id="teklif-sihirbazi" data-zqw-root="<?= e($uid) ?>">
      <script type="application/json" class="zqw-cfg-json"><?= $jsonCfg ?></script>
      <div class="container">
        <div class="zqw-shell">
          <?php if ($context !== 'page'): ?>
          <div class="zqw-head">
            <span class="eyebrow"><?= e(t($cfg['eyebrow'])) ?></span>
            <h2><?= e(t($cfg['heading'])) ?> <span><?= e(t($cfg['heading_accent'])) ?></span></h2>
            <p><?= e(t($cfg['lead'])) ?></p>
          </div>
          <?php endif; ?>

          <div class="zqw-progress-bar" role="group" aria-label="<?= e(t('Teklif adımları')) ?>">
            <button type="button" class="zqw-step-pill is-active" aria-current="step" data-zqw-step-btn="1"><b>1</b><span><?= e(t('Hizmet Seçimi')) ?></span></button>
            <button type="button" class="zqw-step-pill" data-zqw-step-btn="2"><b>2</b><span><?= e(t('Kapsam & Özellikler')) ?></span></button>
            <button type="button" class="zqw-step-pill" data-zqw-step-btn="3"><b>3</b><span><?= e(t('Süre & Planlama')) ?></span></button>
            <button type="button" class="zqw-step-pill" data-zqw-step-btn="4"><b>4</b><span><?= e(t('İletişim & Sonuç')) ?></span></button>
          </div>

          <form class="zqw-form" method="post" action="<?= e($apiEndpoint) ?>" toolname="requestInteractiveQuote" tooldescription="Submit multi-step interactive project scope and receive tailored quote" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"/>
            <input type="hidden" name="source_type" value="quote_wizard"/>
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;pointer-events:none" aria-hidden="true"/>

            <!-- STEP 1 -->
            <div class="zqw-pane is-active" data-zqw-pane="1">
              <h3 class="zqw-pane-title"><?= e(t('1. Hangi alanda çözüm arıyorsunuz?')) ?></h3>
              <div class="zqw-card-grid">
                <?php foreach ($cfg['categories'] as $cIdx => $cat): ?>
                <label class="zqw-choice-card<?= $cIdx === 0 ? ' is-selected' : '' ?>">
                  <input type="radio" name="category_id" value="<?= e($cat['id']) ?>" data-title="<?= e($cat['title']) ?>" data-min="<?= (int)$cat['min'] ?>" data-max="<?= (int)$cat['max'] ?>" <?= $cIdx === 0 ? 'checked' : '' ?>/>
                  <span class="zqw-card-icon" aria-hidden="true"><?= e($cat['icon']) ?></span>
                  <strong class="zqw-card-title"><?= e(t($cat['title'])) ?></strong>
                  <span class="zqw-card-desc"><?= e(t($cat['desc'])) ?></span>
                  <?php if ($cfg['show_price_estimate'] && ($cat['max'] > 0 || $cat['min'] > 0)): ?>
                  <span class="zqw-card-price"><?= number_format((int)$cat['min'], 0, ',', '.') ?> – <?= number_format((int)$cat['max'], 0, ',', '.') ?> <?= e($cfg['currency']) ?></span>
                  <?php endif; ?>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="zqw-nav-row">
                <div class="zqw-live-est" data-zqw-est-box <?= $cfg['show_price_estimate'] ? '' : 'style="display:none"' ?>></div>
                <button type="button" class="button button-primary zqw-next-btn" data-zqw-goto="2"><?= e(t('Devam Et: Özellikler →')) ?></button>
              </div>
            </div>

            <!-- STEP 2 -->
            <div class="zqw-pane" data-zqw-pane="2">
              <h3 class="zqw-pane-title"><?= e(t('2. Eklemek istediğiniz özellikler var mı? (İsteğe bağlı)')) ?></h3>
              <div class="zqw-card-grid">
                <?php foreach ($cfg['features'] as $ft): ?>
                <label class="zqw-choice-card zqw-multi">
                  <input type="checkbox" name="feature_ids[]" value="<?= e($ft['id']) ?>" data-title="<?= e($ft['title']) ?>" data-min="<?= (int)$ft['min'] ?>" data-max="<?= (int)$ft['max'] ?>"/>
                  <strong class="zqw-card-title"><?= e(t($ft['title'])) ?></strong>
                  <span class="zqw-card-desc"><?= e(t($ft['desc'])) ?></span>
                  <?php if ($cfg['show_price_estimate'] && ($ft['max'] > 0 || $ft['min'] > 0)): ?>
                  <span class="zqw-card-price">+<?= number_format((int)$ft['min'], 0, ',', '.') ?> – <?= number_format((int)$ft['max'], 0, ',', '.') ?> <?= e($cfg['currency']) ?></span>
                  <?php endif; ?>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="zqw-nav-row">
                <button type="button" class="button zqw-prev-btn" data-zqw-goto="1"><?= e(t('← Geri')) ?></button>
                <div class="zqw-live-est" data-zqw-est-box <?= $cfg['show_price_estimate'] ? '' : 'style="display:none"' ?>></div>
                <button type="button" class="button button-primary zqw-next-btn" data-zqw-goto="3"><?= e(t('Devam Et: Süre Planı →')) ?></button>
              </div>
            </div>

            <!-- STEP 3 -->
            <div class="zqw-pane" data-zqw-pane="3">
              <h3 class="zqw-pane-title"><?= e(t('3. Hedeflediğiniz başlangıç veya teslim takvimi nedir?')) ?></h3>
              <div class="zqw-card-grid zqw-card-grid-3">
                <?php foreach ($cfg['timelines'] as $tIdx => $tl): ?>
                <label class="zqw-choice-card<?= $tIdx === 0 ? ' is-selected' : '' ?>">
                  <input type="radio" name="timeline_id" value="<?= e($tl['id']) ?>" data-title="<?= e($tl['title']) ?>" <?= $tIdx === 0 ? 'checked' : '' ?>/>
                  <strong class="zqw-card-title"><?= e(t($tl['title'])) ?></strong>
                  <span class="zqw-card-desc"><?= e(t($tl['desc'])) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="zqw-nav-row">
                <button type="button" class="button zqw-prev-btn" data-zqw-goto="2"><?= e(t('← Geri')) ?></button>
                <div class="zqw-live-est" data-zqw-est-box <?= $cfg['show_price_estimate'] ? '' : 'style="display:none"' ?>></div>
                <button type="button" class="button button-primary zqw-next-btn" data-zqw-goto="4"><?= e(t('Son Adım: İletişim →')) ?></button>
              </div>
            </div>

            <!-- STEP 4 -->
            <div class="zqw-pane" data-zqw-pane="4">
              <h3 class="zqw-pane-title"><?= e(t('4. Teklif özeti ve iletişim bilgileriniz')) ?></h3>
              <div class="zqw-summary-banner" data-zqw-summary-box></div>
              <div class="zqw-fields-grid">
                <div class="zqw-field">
                  <label><?= e(t('Adınız Soyadınız *')) ?></label>
                  <input type="text" name="name" required placeholder="<?= e(t('Ad Soyad')) ?>" autocomplete="name"/>
                </div>
                <div class="zqw-field">
                  <label><?= e(t('Cep Telefonunuz *')) ?></label>
                  <input type="tel" name="phone" required inputmode="tel" placeholder="05XX XXX XX XX" autocomplete="tel"/>
                </div>
                <div class="zqw-field">
                  <label><?= e(t('E-posta Adresiniz')) ?></label>
                  <input type="email" name="email" placeholder="ornek@firma.com" autocomplete="email"/>
                </div>
                <div class="zqw-field">
                  <label><?= e(t('Firma / Marka Adı')) ?></label>
                  <input type="text" name="company" placeholder="<?= e(t('İsteğe bağlı')) ?>" autocomplete="organization"/>
                </div>
              </div>
              <div class="zqw-field" style="margin-top:12px">
                <label><?= e(t('Ek Notunuz (İsteğe bağlı)')) ?></label>
                <textarea name="notes" rows="2" placeholder="<?= e(t('Varsa mevcut site adresiniz veya eklemek istediğiniz kısa not...')) ?>"></textarea>
              </div>
              <label class="zqw-consent">
                <input type="checkbox" name="consent" value="1" checked required/>
                <span><a href="<?= e($kvkkUrl) ?>" target="_blank" rel="noopener"><?= e(t('KVKK Aydınlatma Metni')) ?></a> <?= e(t('kapsamında teklif talebimin yanıtlanmasını onaylıyorum.')) ?></span>
              </label>
              <div class="zqw-feedback" data-zqw-feedback style="display:none"></div>
              <div class="zqw-nav-row">
                <button type="button" class="button zqw-prev-btn" data-zqw-goto="3"><?= e(t('← Geri')) ?></button>
                <button type="submit" class="button button-primary zqw-submit-btn"><?= e(t($cfg['submit_label'])) ?> →</button>
              </div>
            </div>

            <!-- SUCCESS STATE -->
            <div class="zqw-pane zqw-success-pane" data-zqw-pane="done">
              <div class="zqw-success-orb">✓</div>
              <h3><?= e(t('Talebiniz Başarıyla Alındı!')) ?></h3>
              <p data-zqw-done-msg><?= e(t('Seçtiğiniz proje kapsamı ekibimize iletildi. En kısa sürede telefon veya WhatsApp üzerinden sizinle iletişime geçeceğiz.')) ?></p>
              <div class="zqw-done-actions">
                <a href="#" target="_blank" rel="noopener" class="button button-primary" data-zqw-wa-btn style="display:none">💬 <?= e(t('Seçimlerimi WhatsApp ile Hemen İlet')) ?></a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </section>
    <?php
    return (string)ob_get_clean();
}

/* ============================================================================
 * 5. ZERO-COOKIE SMART POP-UP & EXIT-INTENT STUDIO
 * ============================================================================ */

function zeynart_get_smart_popup_config(array $store): array
{
    $raw = is_array($store['smart_popup'] ?? null) ? $store['smart_popup'] : [];
    $rawMode = (string)($raw['mode'] ?? 'lead_form');
    $mode = in_array($rawMode, ['cta', 'lead_form'], true) ? $rawMode : 'lead_form';
    $rawTrigger = (string)($raw['trigger'] ?? 'exit_intent');
    $trigger = in_array($rawTrigger, ['exit_intent', 'delay', 'scroll'], true) ? $rawTrigger : 'exit_intent';
    $rawFreq = (string)($raw['frequency'] ?? 'session');
    $freq = in_array($rawFreq, ['session', 'daily', 'always'], true) ? $rawFreq : 'session';
    $rawTargets = (string)($raw['target_pages'] ?? 'all');
    $targets = in_array($rawTargets, ['all', 'home', 'services', 'blog'], true) ? $rawTargets : 'all';

    return [
        'enabled' => !empty($raw['enabled']),
        'mode' => $mode,
        'trigger' => $trigger,
        'delay_seconds' => max(1, min(120, (int)($raw['delay_seconds'] ?? 6))),
        'scroll_percent' => max(10, min(95, (int)($raw['scroll_percent'] ?? 45))),
        'frequency' => $freq,
        'target_pages' => $targets,
        'badge' => mb_substr(trim((string)($raw['badge'] ?? 'ÜCRETSİZ ÖN GÖRÜŞME & ANALİZ')), 0, 80, 'UTF-8'),
        'headline' => mb_substr(trim((string)($raw['headline'] ?? 'Ayrılmadan Önce Sizi 15 Dakikada Arayalım mı?')), 0, 140, 'UTF-8'),
        'body' => mb_substr(trim((string)($raw['body'] ?? 'Projeniz için hızlı fiyat aralığı ve teknik yol haritasını uzman ekibimizle telefonda netleştirelim.')), 0, 320, 'UTF-8'),
        'image' => mb_substr(trim((string)($raw['image'] ?? '')), 0, 260, 'UTF-8'),
        'primary_label' => mb_substr(trim((string)($raw['primary_label'] ?? 'Teklif Sihirbazını Aç →')), 0, 70, 'UTF-8'),
        'primary_url' => mb_substr(trim((string)($raw['primary_url'] ?? '/teklif-sihirbazi/')), 0, 220, 'UTF-8'),
        'form_button_label' => mb_substr(trim((string)($raw['form_button_label'] ?? 'Beni Hemen Arayın →')), 0, 70, 'UTF-8'),
        'show_whatsapp_btn' => ($raw['show_whatsapp_btn'] ?? true) !== false,
    ];
}

function zeynart_render_smart_popup(array $store, string $activePageKey = ''): string
{
    $cfg = zeynart_get_smart_popup_config($store);
    $isPreview = (($_GET['popup_preview'] ?? '') === '1');
    if (empty($cfg['enabled']) && !$isPreview) {
        return '';
    }

    if (!$isPreview && $cfg['target_pages'] !== 'all') {
        $pk = $activePageKey !== '' ? $activePageKey : (string)($GLOBALS['zeynart_active_page_key'] ?? '');
        if ($cfg['target_pages'] === 'home' && $pk !== 'anasayfa') {
            return '';
        }
        if ($cfg['target_pages'] === 'services' && !in_array($pk, ['hizmetler', 'projeler'], true) && !str_starts_with($pk, 'service:') && !str_starts_with($pk, 'project:')) {
            return '';
        }
        if ($cfg['target_pages'] === 'blog' && $pk !== 'blog' && !str_starts_with($pk, 'post:')) {
            return '';
        }
    }

    $csrf = function_exists('csrf_token') ? csrf_token() : '';
    $apiUrl = url('/api/teklif-sihirbazi');
    $rawWa = preg_replace('/\D+/', '', (string)($store['quick_contact']['whatsapp_number'] ?? $store['site']['phone_href'] ?? '908503050533')) ?? '';
    if (str_starts_with($rawWa, '0') && strlen($rawWa) === 11) {
        $rawWa = '9' . $rawWa;
    }
    $waHref = $rawWa !== '' ? ('https://wa.me/' . $rawWa . '?text=' . rawurlencode('Merhaba, web siteniz üzerinden hızlı bilgi ve teklif almak istiyorum.')) : '';
    $imgUrl = $cfg['image'] !== '' ? url($cfg['image']) : '';
    $popKey = 'zsp_' . substr(md5($cfg['headline'] . '|' . $cfg['mode']), 0, 8);

    $clientCfg = json_encode([
        'key' => $popKey,
        'preview' => $isPreview,
        'trigger' => $isPreview ? 'delay' : $cfg['trigger'],
        'delay_ms' => $isPreview ? 350 : ($cfg['delay_seconds'] * 1000),
        'scroll_pct' => $cfg['scroll_percent'],
        'frequency' => $isPreview ? 'always' : $cfg['frequency'],
        'api_url' => $apiUrl,
        'csrf_token' => $csrf,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

    ob_start();
    ?>
    <div class="zsp-backdrop" id="zeynartSmartPopup" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="zspHeadline">
      <script type="application/json" id="zeynartSmartPopupCfg"><?= $clientCfg ?></script>
      <div class="zsp-dialog<?= $imgUrl !== '' ? ' has-media' : '' ?>">
        <button type="button" class="zsp-close" data-zsp-close aria-label="<?= e(t('Kapat')) ?>">✕</button>
        <?php if ($imgUrl !== ''): ?>
        <div class="zsp-media">
          <img src="<?= e($imgUrl) ?>" alt="<?= e($cfg['headline']) ?>" loading="lazy" decoding="async"/>
        </div>
        <?php endif; ?>
        <div class="zsp-body">
          <?php if ($cfg['badge'] !== ''): ?>
          <span class="zsp-badge"><i></i> <?= e(t($cfg['badge'])) ?></span>
          <?php endif; ?>
          <h3 id="zspHeadline"><?= e(t($cfg['headline'])) ?></h3>
          <p class="zsp-copy"><?= e(t($cfg['body'])) ?></p>

          <?php if ($cfg['mode'] === 'lead_form'): ?>
          <form class="zsp-form" id="zspLeadForm" method="post" action="<?= e($apiUrl) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"/>
            <input type="hidden" name="source_type" value="smart_popup"/>
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;pointer-events:none" aria-hidden="true"/>
            <div class="zsp-inputs">
              <input type="text" name="name" required placeholder="<?= e(t('Adınız Soyadınız *')) ?>" autocomplete="name"/>
              <input type="tel" name="phone" required inputmode="tel" placeholder="<?= e(t('Cep Telefonunuz * (05XX...)')) ?>" autocomplete="tel"/>
            </div>
            <div class="zsp-feedback" id="zspFeedback" style="display:none"></div>
            <div class="zsp-actions">
              <button type="submit" class="button button-primary zsp-submit-btn"><?= e(t($cfg['form_button_label'])) ?></button>
              <?php if ($cfg['show_whatsapp_btn'] && $waHref !== ''): ?>
              <a href="<?= e($waHref) ?>" target="_blank" rel="noopener" class="button zsp-wa-btn">💬 WhatsApp</a>
              <?php endif; ?>
            </div>
          </form>
          <?php else: ?>
          <div class="zsp-actions">
            <a href="<?= e(url($cfg['primary_url'])) ?>" class="button button-primary" data-zsp-cta><?= e(t($cfg['primary_label'])) ?></a>
            <?php if ($cfg['show_whatsapp_btn'] && $waHref !== ''): ?>
            <a href="<?= e($waHref) ?>" target="_blank" rel="noopener" class="button zsp-wa-btn">💬 WhatsApp</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
    return (string)ob_get_clean();
}
