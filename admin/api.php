<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../includes/page_builder.php';
require_once __DIR__ . '/../includes/google_reviews.php';
require_once __DIR__ . '/../includes/quick_contact.php';
require_once __DIR__ . '/../includes/site_audit.php';
require_once __DIR__ . '/../includes/translator.php';
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/../includes/studio_suite.php';
require_once __DIR__ . '/../includes/studio_pro_pack.php';
require_once __DIR__ . '/../includes/news_autopilot.php';
require_once __DIR__ . '/../includes/agency_powerhouse.php';
require_once __DIR__ . '/../includes/enterprise_cms_suite.php';
require_once __DIR__ . '/../includes/agency_growth_suite.php';
require_once __DIR__ . '/../includes/sector_extensions.php';
require_once __DIR__ . '/../includes/ota_hub_engine.php';
require_once __DIR__ . '/../lib/smtp.php';

// CI/CD Security Scanning CLI Gate (php admin/api.php --ci-security-audit)
if (PHP_SAPI === 'cli' && in_array('--ci-security-audit', $argv ?? [], true)) {
    $ciReport = zeynart_run_security_20_audit(load_store());
    echo json_encode($ciReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(!empty($ciReport['all_passed']) ? 0 : 1);
}

start_secure_session();
$bootStartHr = hrtime(true);
send_security_headers(true);
header('Content-Type: application/json; charset=UTF-8');

function api_json(bool $ok, array $payload = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strcasecmp(zeynart_get_admin_slug(), ADMIN_DIR_NAME) !== 0 && empty($_SERVER['ZEYNART_ROUTED_ADMIN'])) {
    api_json(false, ['error' => 'Bulunamadı.'], 404);
}

function save_store_or_fail(array $store): void
{
    $act = (string)($GLOBALS['action'] ?? '');
    $reqData = is_array($GLOBALS['data'] ?? null) ? $GLOBALS['data'] : [];
    if (empty($GLOBALS['zeynart_next_revision_label']) && $act !== '') {
        $autoLabel = match ($act) {
            'unified_page_save' => 'Sayfa Düzenlendi (' . (string)($reqData['page_key'] ?? 'sayfa') . ')',
            'unified_page_clone' => 'Sayfa Çoğaltıldı (' . (string)($reqData['page_key'] ?? '') . ')',
            'unified_page_status', 'unified_pages_bulk' => 'Sayfa Yayın / Çöp Kutusu Durumu Güncellendi',
            'unified_page_purge' => 'Çöp Kutusundan Sayfa Kalıcı Silindi',
            'home_save', 'home_layout_save' => 'Ana Sayfa İçerik & Bölüm Sıralaması Güncellendi',
            'menus_save' => 'Menüler, Font & Tipografi Güncellendi',
            'seo_save' => 'SEO, KVKK & 301 Yönlendirme Ayarları Kaydedildi',
            '404_convert_redirect' => '404 Kırık Link 301 Yönlendirmeye Bağlandı',
            'settings_save' => 'Sistem, Çoklu Dil & Marka Ayarları Kaydedildi',
            'social_media_save' => 'Sosyal Medya Hesapları, İkon Görünürlüğü & Paylaşım Ayarları Kaydedildi',
            'appearance_save' => 'Tema Sihirbazı, Duyuru Çubuğu & Bakım Ayarları Kaydedildi',
            'firewall_save' => 'Güvenlik Duvarı & Özel Yönetim Adresi Güncellendi',
            'blog_category_save' => 'Blog Kategorisi Kaydedildi',
            'blog_category_delete', 'blog_category_bulk_delete' => 'Blog Kategorisi Silindi (Yazılar Korundu)',
            'blog_category_set_default' => 'Varsayılan Blog Kategorisi Değiştirildi',
            'blog_category_reorder' => 'Blog Kategori Sıralaması Güncellendi',
            'live_studio_save', 'page_live_save' => 'Canlı Görsel Düzenleyici Kaydı (' . (string)($reqData['page_key'] ?? $reqData['route_path'] ?? '') . ')',
            'growth_tools_save' => 'Büyüme, Pop-up & Teklif Sihirbazı Ayarları Güncellendi',
            'google_reviews_save', 'google_reviews_sync' => 'Google Yorumları Güncellendi',
            'contact_save' => 'İletişim Sayfası Güncellendi',
            'password_change' => 'Yönetici Hesap & Parola Bilgileri Güncellendi',
            'user_save', 'user_delete', 'user_toggle_active' => 'Kullanıcı & Rol Yetkileri Güncellendi',
            'media_stamp_save' => 'Medya Otomatik Marka/Domain Damga Ayarları Kaydedildi',
            'media_canvas_save', 'media_apply_stamp' => 'Medya Görsel Üzerine Yazı & Marka Tasarımı Kaydedildi',
            'seo_doctor_autofix' => 'SEO Doktoru Otomatik Meta & Alt Etiketi Onarımı Uygulandı',
            'news_bot_save' => 'Google News Otopilot & Filigran Ayarları Kaydedildi',
            'seo_interlinks_save' => 'Blog Sesli Okuma & Otomatik SEO İç Linkleme Ayarları Kaydedildi',
            'vcards_save' => 'QR Dijital Kartvizit & Kurumsal Fatura/IBAN Stüdyosu Kaydedildi',
            'uptime_save_config' => 'Ajans Uptime & SSL Kontrol Kulesi Ayarları Kaydedildi',
            'authors_save' => 'E-E-A-T Uzman Yazar Profilleri Güncellendi',
            'blog_comments_save_cfg' => 'Blog Yorum & Soru-Cevap Ayarları Kaydedildi',
            'vault_backup_save_cfg' => 'Tam Yedekleme Kasası Otomatik Planı Güncellendi',
            'executive_report_save' => 'Aylık Beyaz Etiketli Performans & SEO Rapor Şablonu Güncellendi',
            'message_convert_proposal' => 'Proje Talebi Resmi Müşteri Teklifine Dönüştürüldü',
            'solution_pages_generate' => 'Programatik Sektörel & Bölgesel Çözüm Sayfası Üretildi',
            'booking_settings_save' => 'Online Keşif Görüşmesi Planlayıcı Ayarları Kaydedildi',
            'security_2fa_enable', 'security_2fa_disable' => 'İki Aşamalı Giriş Doğrulama (2FA) Durumu Güncellendi',
            default => 'Yönetim Paneli Güncellemesi (' . $act . ')',
        };
        zeynart_set_revision_label($autoLabel);
    }
    if (!save_store($store)) {
        api_json(false, [
            'error' => 'Değişiklikler sunucu diskine kaydedilemedi. Lütfen Plesk/cPanel üzerinde "data/" klasörü (775) ve "data/store.json" dosyası (664) yazma izinlerini kontrol edin.',
        ], 500);
    }
    if ($act !== '' && !in_array($act, ['message_mark_read'], true)) {
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? ($store['admin']['username'] ?? 'admin'));
        zeynart_audit_log('admin_save', 'Kayıt başarılı: ' . ($autoLabel ?? $act), $actor, 'info');
    }
}

function read_request_data(): array
{
    $unwrapPayload = static function (array $arr): array {
        if (!empty($arr['_zb64']) && is_string($arr['_zb64'])) {
            $rawJson = base64_decode($arr['_zb64'], true);
            if (is_string($rawJson) && $rawJson !== '') {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    if (!empty($arr['csrf_token']) && empty($decoded['csrf_token'])) {
                        $decoded['csrf_token'] = (string)$arr['csrf_token'];
                    }
                    return $decoded;
                }
            }
        }
        return $arr;
    };

    if (!empty($_POST)) {
        return $unwrapPayload($_POST);
    }

    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
    $raw = (string)file_get_contents('php://input');
    if ($raw !== '') {
        if (str_contains($contentType, 'application/json') || str_starts_with(ltrim($raw), '{')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $unwrapPayload($decoded);
            }
        }
        $parsedForm = [];
        parse_str($raw, $parsedForm);
        if (is_array($parsedForm) && $parsedForm !== []) {
            return $unwrapPayload($parsedForm);
        }
    }
    return [];
}

function build_safe_store_for_client(array $store): array
{
    $role = zeynart_current_admin_role();
    $copy = $store;
    if (isset($copy['admin']) && is_array($copy['admin'])) {
        unset($copy['admin']['password_hash']);
    }
    if ($role === 'super_admin') {
        $copy['admin_users'] = zeynart_normalize_admin_users($store, true);
    } else {
        unset($copy['admin_users'], $copy['smtp'], $copy['firewall']);
    }
    $copy['current_user'] = zeynart_current_admin_profile($store);
    $copy['google_reviews'] = get_google_reviews_state($store);
    $copy['quick_contact'] = get_quick_contact_state($store);
    $copy['site_audit'] = get_site_audit_state($store);
    $copy['quote_wizard'] = zeynart_get_quote_wizard_config($store);
    $copy['quote_wizard_presets'] = zeynart_quote_wizard_presets();
    $copy['smart_popup'] = zeynart_get_smart_popup_config($store);
    $copy['media_stamp'] = zeynart_get_media_stamp_config($store);
    $nbCfgSafe = zeynart_get_news_autopilot_config($store);
    if ($role !== 'super_admin') {
        $nbCfgSafe['openai_api_key'] = '';
        $nbCfgSafe['gemini_api_key'] = '';
    }
    $copy['news_autopilot'] = $nbCfgSafe;
    $utCfgSafe = zeynart_get_uptime_tower_config($store);
    if ($role !== 'super_admin') {
        $utCfgSafe['cron_secret'] = '';
    }
    $copy['uptime_tower'] = $utCfgSafe;
    $copy['seo_interlinks'] = zeynart_get_auto_interlink_config($store);
    $copy['vcards'] = zeynart_get_vcards_list($store);
    $copy['authors'] = zeynart_get_authors_list($store);
    $copy['blog_comments_cfg'] = zeynart_get_blog_comments_config($store);
    $copy['backup_vault'] = zeynart_get_backup_schedule_config($store);
    $copy['executive_report'] = zeynart_get_executive_report_config($store);
    $twoFaRaw = zeynart_get_2fa_config($store);
    $copy['security_2fa'] = [
        'enabled' => $twoFaRaw['enabled'],
        'method' => $twoFaRaw['method'],
        'email_fallback' => $twoFaRaw['email_fallback'],
        'enabled_at' => $twoFaRaw['enabled_at'],
        'recovery_remaining' => count($twoFaRaw['recovery_codes']),
        'remaining_recovery_codes' => count($twoFaRaw['recovery_codes']),
    ];
    $copy['seo'] = is_array($copy['seo'] ?? null) ? $copy['seo'] : [];
    $copy['seo']['branded_og'] = zeynart_get_branded_og_config($store);
    $copy['theme_brand'] = zeynart_get_theme_brand($store);
    $copy['theme_presets'] = zeynart_theme_presets();
    $copy['promo_bar'] = zeynart_get_promo_bar($store);
    $copy['maintenance'] = zeynart_get_maintenance_state($store);
    $copy['lscache'] = zeynart_get_lscache_config($store);
    $copy['blog_categories'] = zeynart_get_blog_categories($store);
    $copy['booking_settings'] = zeynart_get_booking_settings($store);
    if (isset($copy['messages']) && is_array($copy['messages'])) {
        $copy['messages'] = array_map(
            static fn($m) => is_array($m) ? zeynart_normalize_message_crm($m) : $m,
            $copy['messages']
        );
    }
    if ($role === 'super_admin') {
        $fwSafe = zeynart_get_firewall_config($store);
        $fwSafe['admin_slug'] = zeynart_get_admin_slug($store);
        $copy['firewall'] = $fwSafe;
        $copy['security'] = array_merge(
            is_array($store['security'] ?? null) ? $store['security'] : [],
            ['admin_slug' => zeynart_get_admin_slug($store)]
        );
    } else {
        unset($copy['security']);
    }
    $hitMap = zeynart_get_redirect_hits();
    if (is_array($copy['redirects'] ?? null)) {
        foreach ($copy['redirects'] as $rIdx => $rRow) {
            if (is_array($rRow)) {
                $fKey = strtolower('/' . trim((string)($rRow['from'] ?? ''), '/'));
                $copy['redirects'][$rIdx]['hits'] = (int)($rRow['hits'] ?? 0) + (int)($hitMap[$fKey] ?? 0);
            }
        }
    }
    $i18nDefaults = [
        'enabled' => true,
        'auto_translate_blog' => true,
        'languages' => ['tr', 'en', 'de', 'ru'],
        'provider' => 'auto',
        'deepl_api_key' => '',
        'openai_api_key' => '',
        'openai_model' => 'gpt-4o-mini',
    ];
    $copy['i18n'] = array_merge($i18nDefaults, is_array($copy['i18n'] ?? null) ? $copy['i18n'] : []);
    if ($role !== 'super_admin') {
        $copy['i18n']['deepl_api_key'] = '';
        $copy['i18n']['openai_api_key'] = '';
        if (isset($copy['ai_seo']) && is_array($copy['ai_seo'])) {
            $copy['ai_seo']['openai_api_key'] = '';
            $copy['ai_seo']['gemini_api_key'] = '';
        }
        if (isset($copy['seo']) && is_array($copy['seo'])) {
            $copy['seo']['custom_head_scripts'] = '';
        }
        if (isset($copy['cookie_consent']) && is_array($copy['cookie_consent'])) {
            $copy['cookie_consent']['custom_head_essential'] = '';
            $copy['cookie_consent']['custom_head_analytics'] = '';
            $copy['cookie_consent']['custom_head_marketing'] = '';
        }
    }
    $copy['page_slugs'] = zeynart_resolved_page_slugs($store);
    $copy['page_states'] = is_array($store['page_states'] ?? null) ? $store['page_states'] : [];
    foreach (['page_titles', 'page_slugs', 'page_states', 'page_menu_labels', 'page_typography', 'legal_pages', 'core_page_blocks', 'page_live_patches'] as $dictKey) {
        $dictVal = is_array($copy[$dictKey] ?? null) ? $copy[$dictKey] : [];
        $copy[$dictKey] = $dictVal === [] ? new stdClass() : $dictVal;
    }
    if (!isset($copy['seo']) || !is_array($copy['seo'])) {
        $copy['seo'] = [];
    }
    $seoPagesDict = is_array($copy['seo']['pages'] ?? null) ? $copy['seo']['pages'] : [];
    $copy['seo']['pages'] = $seoPagesDict === [] ? new stdClass() : $seoPagesDict;
    return $copy;
}

function zeynart_register_url_rename(array &$store, string $oldUrl, string $newUrl): void
{
    $oldTrim = trim($oldUrl, '/');
    $newTrim = trim($newUrl, '/');
    if ($oldTrim === '' || $newTrim === '' || strcasecmp($oldTrim, $newTrim) === 0) {
        return;
    }
    $oldNorm = '/' . $oldTrim . '/';
    $newNorm = '/' . $newTrim . '/';

    $rewriteSingle = static function (string $val) use ($oldNorm, $newNorm, $oldTrim): string {
        $t = trim($val);
        if ($t === '' || str_starts_with($t, 'http://') || str_starts_with($t, 'https://') || str_starts_with($t, 'mailto:') || str_starts_with($t, 'tel:') || str_starts_with($t, '#')) {
            return $val;
        }
        $normT = '/' . trim($t, '/') . '/';
        if (strcasecmp($normT, $oldNorm) === 0) {
            return $newNorm;
        }
        if (!str_contains($oldTrim, '/') && str_starts_with(strtolower($normT), strtolower($oldNorm))) {
            return $newNorm . substr($normT, strlen($oldNorm));
        }
        return $val;
    };

    if (isset($store['menus']['header']['items']) && is_array($store['menus']['header']['items'])) {
        foreach ($store['menus']['header']['items'] as $idx => $it) {
            if (is_array($it) && isset($it['url'])) {
                $store['menus']['header']['items'][$idx]['url'] = $rewriteSingle((string)$it['url']);
            }
        }
    }
    if (!empty($store['menus']['header']['cta_url'])) {
        $store['menus']['header']['cta_url'] = $rewriteSingle((string)$store['menus']['header']['cta_url']);
    }
    if (isset($store['menus']['footer']['columns']) && is_array($store['menus']['footer']['columns'])) {
        foreach ($store['menus']['footer']['columns'] as $cIdx => $col) {
            if (is_array($col['links'] ?? null)) {
                foreach ($col['links'] as $lIdx => $lnk) {
                    if (is_array($lnk) && isset($lnk['url'])) {
                        $store['menus']['footer']['columns'][$cIdx]['links'][$lIdx]['url'] = $rewriteSingle((string)$lnk['url']);
                    }
                }
            }
        }
    }
    if (isset($store['menus']['footer']['legal_links']) && is_array($store['menus']['footer']['legal_links'])) {
        foreach ($store['menus']['footer']['legal_links'] as $lIdx => $lnk) {
            if (is_array($lnk) && isset($lnk['url'])) {
                $store['menus']['footer']['legal_links'][$lIdx]['url'] = $rewriteSingle((string)$lnk['url']);
            }
        }
    }
    if (!empty($store['menus']['footer']['contact_cta_url'])) {
        $store['menus']['footer']['contact_cta_url'] = $rewriteSingle((string)$store['menus']['footer']['contact_cta_url']);
    }
    if (!empty($store['contact']['consent_link_url'])) {
        $store['contact']['consent_link_url'] = $rewriteSingle((string)$store['contact']['consent_link_url']);
    }
    if (!empty($store['cookie_consent']['policy_url'])) {
        $store['cookie_consent']['policy_url'] = $rewriteSingle((string)$store['cookie_consent']['policy_url']);
    }
    if (!empty($store['cookie_consent']['kvkk_url'])) {
        $store['cookie_consent']['kvkk_url'] = $rewriteSingle((string)$store['cookie_consent']['kvkk_url']);
    }
    if (!empty($store['cta_global']['button_url'])) {
        $store['cta_global']['button_url'] = $rewriteSingle((string)$store['cta_global']['button_url']);
    }
    if (!empty($store['home']['hero']['primary_btn_url'])) {
        $store['home']['hero']['primary_btn_url'] = $rewriteSingle((string)$store['home']['hero']['primary_btn_url']);
    }

    // Maintain loop-free 301 Redirect table
    $existingRedirects = is_array($store['redirects'] ?? null) ? $store['redirects'] : [];
    $cleanRedirects = [];
    $hasOldRedirect = false;
    foreach ($existingRedirects as $red) {
        if (!is_array($red)) {
            continue;
        }
        $rFrom = '/' . trim((string)($red['from'] ?? ''), '/');
        $rTo = '/' . trim((string)($red['to'] ?? ''), '/') . '/';
        if ($rFrom === '/') {
            continue;
        }
        // Drop any old redirect that used to redirect FROM the newly activated URL (prevents loops)
        if (strcasecmp($rFrom . '/', $newNorm) === 0) {
            continue;
        }
        // Collapse multi-hop chains: A -> old becomes A -> new
        if (strcasecmp($rTo, $oldNorm) === 0) {
            $red['to'] = $newNorm;
            $rTo = $newNorm;
        }
        if (strcasecmp($rFrom . '/', $rTo) === 0) {
            continue;
        }
        if (strcasecmp($rFrom . '/', $oldNorm) === 0) {
            $red['to'] = $newNorm;
            $red['code'] = 301;
            $hasOldRedirect = true;
        }
        $cleanRedirects[] = $red;
    }
    if (!$hasOldRedirect) {
        $cleanRedirects[] = [
            'id' => 'red-' . substr(bin2hex(random_bytes(4)), 0, 8),
            'from' => '/' . $oldTrim,
            'to' => $newNorm,
            'code' => 301,
            'hits' => 0,
        ];
    }
    $store['redirects'] = array_values($cleanRedirects);
}

function compute_dashboard_summary(array $store): array
{
    if (function_exists('zeynart_v4_cached_boot_bundle')) {
        $bundle = zeynart_v4_cached_boot_bundle($store);
        $bm = is_array($bundle['blog_metrics'] ?? null) ? $bundle['blog_metrics'] : [];
        $totalPosts = (int)($bm['total_posts'] ?? 0);
        $publishedPosts = (int)($bm['published_posts'] ?? 0);
        $draftPosts = (int)($bm['draft_posts'] ?? 0);
        $translatedPosts = (int)($bm['translated_posts'] ?? 0);
        $avgSeo = (int)($bm['avg_seo_score'] ?? 95);
        $recentPostsList = is_array($bm['recent_posts'] ?? null) ? $bm['recent_posts'] : [];
        $topCategoriesMap = is_array($bm['top_categories'] ?? null) ? $bm['top_categories'] : [];
        $blogCatsCount = (int)($bm['blog_categories_count'] ?? count(zeynart_get_blog_categories($store)));
    } else {
        $blogIndex = load_blog_index();
        $totalPosts = count($blogIndex);
        $publishedPosts = 0;
        $draftPosts = 0;
        $translatedPosts = 0;
        $seoTotal = 0;
        foreach ($blogIndex as $item) {
            $st = (string)($item['status'] ?? 'published');
            if ($st === 'published') {
                $publishedPosts++;
            } else {
                $draftPosts++;
            }
            if (
                !empty($item['i18n']['en']['title'])
                && !empty($item['i18n']['de']['title'])
                && !empty($item['i18n']['ru']['title'])
            ) {
                $translatedPosts++;
            }
            $seoTotal += (int)($item['seo_score'] ?? 90);
        }
        $blogCatsSummary = zeynart_get_blog_categories($store);
        $catCountsById = zeynart_count_posts_per_category($blogCatsSummary, $blogIndex, false);
        $catCounts = [];
        foreach ($blogCatsSummary as $bcs) {
            $cid = (string)$bcs['id'];
            $totC = (int)($catCountsById[$cid]['total'] ?? 0);
            if ($totC > 0) {
                $catCounts[(string)$bcs['name']] = $totC;
            }
        }
        arsort($catCounts);
        $avgSeo = $totalPosts > 0 ? (int)round($seoTotal / $totalPosts) : 95;
        $recentPostsList = array_slice($blogIndex, 0, 8);
        $topCategoriesMap = array_slice($catCounts, 0, 8, true);
        $blogCatsCount = count($blogCatsSummary);
    }
    $messages = is_array($store['messages'] ?? null) ? $store['messages'] : [];
    $unreadMessages = 0;
    $auditLeadsCount = 0;
    $wizardLeadsCount = 0;
    $popupLeadsCount = 0;
    foreach ($messages as $m) {
        if (empty($m['read'])) {
            $unreadMessages++;
        }
        $mSrc = (string)($m['source'] ?? '');
        if ($mSrc === 'site_audit') {
            $auditLeadsCount++;
        } elseif ($mSrc === 'quote_wizard') {
            $wizardLeadsCount++;
        } elseif ($mSrc === 'smart_popup') {
            $popupLeadsCount++;
        }
    }

    $mediaScan = function_exists('zeynart_quick_media_summary')
        ? zeynart_quick_media_summary($store, 8)
        : scan_media_files('all', '', 1, 8);

    return [
        'total_posts' => $totalPosts,
        'published_posts' => $publishedPosts,
        'draft_posts' => $draftPosts,
        'translated_posts' => $translatedPosts,
        'avg_seo_score' => $avgSeo,
        'services_count' => count($store['services'] ?? []),
        'projects_count' => count($store['projects'] ?? []),
        'clients_count' => count($store['about']['clients'] ?? []),
        'custom_pages_count' => count($store['custom_pages'] ?? []),
        'redirects_count' => count($store['redirects'] ?? []),
        'messages_total' => count($messages),
        'messages_unread' => $unreadMessages,
        'audit_leads_count' => $auditLeadsCount,
        'wizard_leads_count' => $wizardLeadsCount,
        'popup_leads_count' => $popupLeadsCount,
        'media_total' => (int)($mediaScan['total'] ?? 0),
        'recent_media' => $mediaScan['items'] ?? [],
        'recent_posts' => $recentPostsList,
        'recent_messages' => array_slice($messages, 0, 6),
        'top_categories' => $topCategoriesMap,
        'php_version' => PHP_VERSION,
        'webp_supported' => function_exists('imagewebp'),
        'analytics' => zeynart_get_analytics_report(30),
        'broken_404_count' => count(zeynart_get_404_logs()),
        'revisions_count' => count(zeynart_list_revisions()),
        'locked_ips_count' => count(zeynart_get_locked_ips()),
        'maintenance_enabled' => !empty(zeynart_get_maintenance_state($store)['enabled']),
        'promo_bar_enabled' => !empty(zeynart_get_promo_bar($store)['enabled']),
        'news_queue_count' => count(zeynart_list_news_bot_queue()),
        'uptime_summary' => zeynart_compute_uptime_summary(zeynart_load_uptime_monitors($store), zeynart_get_uptime_tower_config($store)),
        'vcards_count' => count(zeynart_get_vcards_list($store)),
        'proposals_count' => count(zeynart_load_proposals()),
        'pending_comments_count' => count(array_filter(zeynart_load_blog_comments(), static fn(array $c): bool => ($c['status'] ?? 'pending') === 'pending')),
        'blog_categories_count' => $blogCatsCount,
    ];
}

if (!function_exists('extract_toc_from_html')) {
    function extract_toc_from_html(string $html): array
    {
        $toc = [];
        if (preg_match_all('~<h2([^>]*)>(.*?)</h2>~isu', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $idx => $m) {
                $attrs = $m[1];
                $title = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($title === '') {
                    continue;
                }
                $id = '';
                if (preg_match('~\bid\s*=\s*["\']([^"\']+)["\']~i', $attrs, $idm)) {
                    $id = trim($idm[1]);
                }
                if ($id === '') {
                    $id = slugify($title) ?: ('bolum-' . ($idx + 1));
                }
                $toc[] = ['id' => $id, 'title' => $title];
            }
        }
        return $toc;
    }
}

if (!function_exists('inject_heading_ids')) {
    function inject_heading_ids(string $html): string
    {
        $idx = 0;
        return preg_replace_callback('~<h2([^>]*)>(.*?)</h2>~isu', static function (array $m) use (&$idx): string {
            $idx++;
            $attrs = $m[1];
            $inner = $m[2];
            if (preg_match('~\bid\s*=\s*["\'][^"\']+["\']~i', $attrs)) {
                return $m[0];
            }
            $plain = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $id = slugify($plain) ?: ('bolum-' . $idx);
            return '<h2 id="' . e($id) . '"' . $attrs . '>' . $inner . '</h2>';
        }, $html) ?? $html;
    }
}

function zeynart_compile_blog_categories_payload(array $store, ?array $blogIndex = null): array
{
    $cats = zeynart_get_blog_categories($store);
    $tree = zeynart_build_blog_category_tree($cats);
    $idx = $blogIndex !== null ? $blogIndex : load_blog_index(true);
    $counts = zeynart_count_posts_per_category($cats, $idx, false);
    $blogSlug = zeynart_page_slug($store, 'blog') ?: 'blog';
    $defaultCatId = '';
    $defaultCat = null;

    foreach ($tree as $i => $row) {
        $cid = (string)$row['id'];
        $cPair = $counts[$cid] ?? ['direct' => 0, 'total' => 0];
        $tree[$i]['post_count'] = (int)$cPair['direct'];
        $tree[$i]['total_post_count'] = (int)$cPair['total'];
        $tree[$i]['url'] = '/' . $blogSlug . '/kategori/' . $row['slug'] . '/';
        if (!empty($row['is_default'])) {
            $defaultCatId = $cid;
            $defaultCat = $tree[$i];
        }
    }
    if ($defaultCat === null && $tree !== []) {
        $defaultCatId = (string)$tree[0]['id'];
        $defaultCat = $tree[0];
    }

    return [
        'categories' => $tree,
        'raw_categories' => $cats,
        'default_category_id' => $defaultCatId,
        'default_category' => $defaultCat,
        'blog_slug' => $blogSlug,
        'total_posts' => count($idx),
    ];
}

$data = read_request_data();
$action = trim((string)($_GET['action'] ?? $data['action'] ?? ''));

if ($action === '') {
    api_json(false, ['error' => 'İşlem (action) belirtilmedi.'], 400);
}

// Public action: login
if ($action === 'login') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        api_json(false, ['error' => 'Yalnızca POST isteği kabul edilir.'], 405);
    }
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
    if (!verify_csrf(is_string($headerToken) ? $headerToken : null)) {
        api_json(false, ['error' => 'Güvenlik anahtarı geçersiz. Lütfen sayfayı yenileyin.'], 403);
    }
    if (!check_rate_limit('admin_login', 10, 300)) {
        api_json(false, ['error' => 'Çok fazla başarısız giriş denemesi. Lütfen 5 dakika bekleyin.'], 429);
    }

    $username = trim((string)($data['username'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $store = load_store(true);

    $fwCheck = zeynart_check_login_firewall($store);
    if (empty($fwCheck['allowed'])) {
        $httpSt = ($fwCheck['reason'] ?? '') === 'ip_not_whitelisted' ? 403 : 429;
        api_json(false, ['error' => (string)($fwCheck['message'] ?? 'Erişim engellendi.')], $httpSt);
    }

    $authUser = zeynart_authenticate_admin_login($store, $username, $password);

    if ($authUser === null) {
        $failInfo = zeynart_record_login_failure($store, $username);
        if (!empty($failInfo['locked'])) {
            api_json(false, [
                'error' => 'Ardışık hatalı parola denemeleri nedeniyle IP adresiniz ' . (int)$failInfo['lockout_minutes'] . ' dakika süreyle güvenlik korumasına alındı.',
            ], 429);
        }
        $rem = max(0, (int)$failInfo['max_attempts'] - (int)$failInfo['attempts']);
        $remNote = $rem > 0 ? ' (Kalan deneme hakkı: ' . $rem . ')' : '';
        api_json(false, ['error' => 'Kullanıcı adı veya parola hatalı.' . $remNote], 401);
    }

    zeynart_clear_login_failures();

    // Check Two-Factor Authentication (2FA) requirement for primary / super_admin login
    $twoFaCfg = zeynart_get_2fa_config($store);
    if (!empty($twoFaCfg['enabled']) && $authUser['role'] === 'super_admin') {
        $emailOtp = null;
        $emailOtpExp = 0;
        $hintMsg = '🔐 Authenticator uygulamanızdaki 6 haneli doğrulama kodunu veya kurtarma kodunuzu girin.';
        if ($twoFaCfg['method'] === 'email') {
            $emailOtp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $emailOtpExp = time() + 300;
            $to2fa = resolve_smtp_recipient($store);
            if ($to2fa !== '' && !empty($store['smtp']['enabled'])) {
                $htmlOtp = '<div style="font-family:Arial,sans-serif;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px">'
                    . '<h2 style="margin:0 0 10px;color:#2dd4bf">🔐 Yönetim Merkezi Giriş Doğrulama Kodunuz</h2>'
                    . '<p style="font-size:28px;font-weight:800;letter-spacing:6px;color:#ffffff;margin:14px 0">' . e($emailOtp) . '</p>'
                    . '<p style="font-size:12.5px;color:#94a3b8">Bu kod 5 dakika süreyle geçerlidir · IP: ' . e(get_client_ip()) . '</p></div>';
                @send_smtp_detailed($store['smtp'], $to2fa, 'Giriş Doğrulama Kodu: ' . $emailOtp, $htmlOtp, ['allow_mail_fallback' => true]);
            }
            $hintMsg = '📧 6 haneli tek kullanımlık doğrulama kodu kayıtlı e-posta adresinize gönderildi.';
        }
        $_SESSION['zeynart_2fa_pending'] = [
            'user' => $authUser,
            'expires_at' => time() + 300,
            'email_otp' => $emailOtp,
            'email_otp_exp' => $emailOtpExp,
        ];
        api_json(true, [
            'requires_2fa' => true,
            'method' => $twoFaCfg['method'],
            'email_fallback' => !empty($twoFaCfg['email_fallback']) && !empty($store['smtp']['enabled']),
            'message' => $hintMsg,
        ]);
    }

    if (PHP_SAPI !== 'cli') {
        @session_regenerate_id(true);
    }
    $_SESSION['zeynart_admin_auth'] = true;
    $_SESSION['zeynart_admin_user'] = $authUser['username'];
    $_SESSION['zeynart_admin_role'] = $authUser['role'];
    $_SESSION['zeynart_admin_display'] = $authUser['display_name'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $GLOBALS['zeynart_skip_revision_capture'] = true;
    save_store($store);
    unset($GLOBALS['zeynart_skip_revision_capture']);
    $roleTag = $authUser['role'] === 'super_admin' ? 'Süper Admin' : 'Müşteri/Editör';
    zeynart_audit_log('login_ok', 'Yönetim paneline giriş yapıldı (' . $roleTag . ').', $authUser['username'], 'ok');

    api_json(true, [
        'message' => 'Hoş geldiniz! Dijital Stüdyo hazır.',
        'csrf_token' => $_SESSION['csrf_token'],
        'role' => $authUser['role'],
    ]);
}

if ($action === 'send_2fa_email') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        api_json(false, ['error' => 'Yalnızca POST isteği kabul edilir.'], 405);
    }
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
    if (!verify_csrf(is_string($headerToken) ? $headerToken : null)) {
        api_json(false, ['error' => 'Güvenlik anahtarı geçersiz.'], 403);
    }
    $pending = $_SESSION['zeynart_2fa_pending'] ?? null;
    if (!is_array($pending) || ((int)($pending['expires_at'] ?? 0)) < time()) {
        api_json(false, ['error' => 'Doğrulama oturumu süresi doldu. Lütfen tekrar kullanıcı adı ve parola girin.'], 401);
    }
    if (!check_rate_limit('admin_2fa_mail', 4, 300)) {
        api_json(false, ['error' => 'Çok sık e-posta kodu istendi. Lütfen birkaç dakika bekleyin.'], 429);
    }
    $store = load_store();
    $to2fa = resolve_smtp_recipient($store);
    if ($to2fa === '' || empty($store['smtp']['enabled'])) {
        api_json(false, ['error' => 'SMTP e-posta servisi yapılandırılmamış. Lütfen Authenticator veya kurtarma kodu kullanın.'], 422);
    }
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['zeynart_2fa_pending']['email_otp'] = $otp;
    $_SESSION['zeynart_2fa_pending']['email_otp_exp'] = time() + 300;
    $htmlOtp = '<div style="font-family:Arial,sans-serif;padding:24px;background:#071318;color:#eaf4f7;border-radius:14px">'
        . '<h2 style="margin:0 0 10px;color:#2dd4bf">🔐 Yönetim Merkezi Tek Kullanımlık Giriş Kodunuz</h2>'
        . '<p style="font-size:28px;font-weight:800;letter-spacing:6px;color:#ffffff;margin:14px 0">' . e($otp) . '</p>'
        . '<p style="font-size:12.5px;color:#94a3b8">Bu kod 5 dakika süreyle geçerlidir · IP: ' . e(get_client_ip()) . '</p></div>';
    $mRes = send_smtp_detailed($store['smtp'], $to2fa, 'Giriş Doğrulama Kodu: ' . $otp, $htmlOtp, ['allow_mail_fallback' => true]);
    if (empty($mRes['ok'])) {
        api_json(false, ['error' => 'E-posta gönderilemedi: ' . ($mRes['error'] ?? 'SMTP hatası')], 500);
    }
    api_json(true, ['message' => '📧 6 haneli tek kullanımlık doğrulama kodu kayıtlı yönetici e-posta adresine gönderildi.']);
}

if ($action === 'verify_2fa') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        api_json(false, ['error' => 'Yalnızca POST isteği kabul edilir.'], 405);
    }
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
    if (!verify_csrf(is_string($headerToken) ? $headerToken : null)) {
        api_json(false, ['error' => 'Güvenlik anahtarı geçersiz.'], 403);
    }
    if (!check_rate_limit('admin_2fa_verify', 8, 300)) {
        api_json(false, ['error' => 'Çok fazla hatalı kod denemesi yapıldı. Lütfen 5 dakika bekleyin.'], 429);
    }
    $pending = $_SESSION['zeynart_2fa_pending'] ?? null;
    if (!is_array($pending) || ((int)($pending['expires_at'] ?? 0)) < time() || !is_array($pending['user'] ?? null)) {
        unset($_SESSION['zeynart_2fa_pending']);
        api_json(false, ['error' => 'Doğrulama süresi doldu. Lütfen tekrar giriş yapın.'], 401);
    }

    $inputCode = strtoupper(trim((string)($data['code'] ?? '')));
    if ($inputCode === '') {
        api_json(false, ['error' => 'Lütfen 6 haneli doğrulama kodunu girin.'], 422);
    }

    $store = load_store(true);
    $twoFaCfg = zeynart_get_2fa_config($store);
    $verified = false;
    $usedRecovery = false;

    // 1. Check TOTP code
    if ($twoFaCfg['totp_secret'] !== '' && zeynart_totp_verify($twoFaCfg['totp_secret'], $inputCode)) {
        $verified = true;
    }

    // 2. Check Email OTP
    if (!$verified && !empty($pending['email_otp']) && ((int)($pending['email_otp_exp'] ?? 0)) >= time()) {
        $cleanDigits = preg_replace('/\D+/', '', $inputCode) ?? '';
        if ($cleanDigits !== '' && hash_equals((string)$pending['email_otp'], $cleanDigits)) {
            $verified = true;
        }
    }

    // 3. Check Single-Use Recovery Code
    if (!$verified && !empty($twoFaCfg['recovery_codes'])) {
        $normInput = preg_replace('/[^A-Z0-9]/', '', $inputCode) ?? '';
        foreach ($twoFaCfg['recovery_codes'] as $rIdx => $rc) {
            $normRc = preg_replace('/[^A-Z0-9]/', '', strtoupper((string)$rc)) ?? '';
            if ($normInput !== '' && $normRc !== '' && hash_equals($normRc, $normInput)) {
                $verified = true;
                $usedRecovery = true;
                unset($twoFaCfg['recovery_codes'][$rIdx]);
                $store['security_2fa']['recovery_codes'] = array_values($twoFaCfg['recovery_codes']);
                break;
            }
        }
    }

    if (!$verified) {
        zeynart_audit_log('login_2fa_fail', 'Hatalı 2FA doğrulama kodu denemesi.', (string)($pending['user']['username'] ?? 'admin'), 'warn');
        api_json(false, ['error' => 'Doğrulama kodu geçersiz veya süresi dolmuş.'], 401);
    }

    $authUser = $pending['user'];
    unset($_SESSION['zeynart_2fa_pending']);
    if (PHP_SAPI !== 'cli') {
        @session_regenerate_id(true);
    }
    $_SESSION['zeynart_admin_auth'] = true;
    $_SESSION['zeynart_admin_user'] = $authUser['username'];
    $_SESSION['zeynart_admin_role'] = $authUser['role'];
    $_SESSION['zeynart_admin_display'] = $authUser['display_name'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $GLOBALS['zeynart_skip_revision_capture'] = true;
    save_store($store);
    unset($GLOBALS['zeynart_skip_revision_capture']);
    zeynart_audit_log('login_2fa_ok', '2FA doğrulaması ile güvenli giriş yapıldı' . ($usedRecovery ? ' (Kurtarma Kodu kullanıldı)' : '') . '.', $authUser['username'], 'ok');

    api_json(true, [
        'message' => 'Doğrulama başarılı! Dijital Stüdyo açılıyor...',
        'csrf_token' => $_SESSION['csrf_token'],
        'role' => $authUser['role'],
    ]);
}

if ($action === 'logout') {
    if (is_admin_logged_in()) {
        zeynart_audit_log('logout', 'Yönetim paneli oturumu güvenli şekilde kapatıldı.', (string)($_SESSION['zeynart_admin_user'] ?? 'admin'), 'info');
    }
    $_SESSION = [];
    if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
    api_json(true, ['message' => 'Oturum güvenli şekilde kapatıldı.']);
}

// Require Admin Authentication for all actions below
if (!is_admin_logged_in()) {
    api_json(false, ['error' => 'Oturum süresi doldu. Lütfen tekrar giriş yapın.', 'auth_required' => true], 401);
}

// Verify CSRF for state-changing or API requests (GET backup download checks query token)
$reqToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? ($_GET['csrf_token'] ?? null));
if (!verify_csrf(is_string($reqToken) ? $reqToken : null)) {
    api_json(false, ['error' => 'CSRF güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.'], 403);
}

$store = load_store(true);
$activeRole = zeynart_current_admin_role();
if (!zeynart_is_action_allowed_for_role($action, $activeRole)) {
    api_json(false, [
        'error' => 'Bu teknik işlem yalnızca Ajans Süper Yönetici yetkisiyle gerçekleştirilebilir.',
        'forbidden_role' => true,
    ], 403);
}

$cachedCurrentUser = zeynart_current_admin_profile($store);
$readOnlyNoLockActions = [
    'boot',
    'admin_lazy_tab_data',
    'analytics_stats',
    'blog_list',
    'blog_get',
    'blog_categories_list',
    'media_list',
    'seo_doctor_scan',
    'seo_content_audit',
    'seo_topic_clusters_audit',
    'system_health_scan',
    'security_20_audit',
    'orphan_media_scan',
    'unused_files_scan',
    'proposals_list',
    'client_trackers_list',
    'renewals_list',
    'bookings_list',
    'blog_comments_list',
    'search_insights_get',
    'news_bot_status',
    'executive_report_get',
    'database_status_get',
    'database_download_sql',
    'ota_overview',
    'ota_download_release',
];
if (function_exists('zeynart_sector_read_only_actions')) {
    $readOnlyNoLockActions = array_values(array_unique(array_merge($readOnlyNoLockActions, zeynart_sector_read_only_actions())));
}
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE && in_array($action, $readOnlyNoLockActions, true)) {
    @session_write_close();
}

switch ($action) {
    case 'boot': {
        $isSuper = ($activeRole === 'super_admin');
        $nbCfgBoot = zeynart_get_news_autopilot_config($store);
        $utCfgBoot = zeynart_get_uptime_tower_config($store);
        $utMonsBoot = zeynart_load_uptime_monitors($store);
        $canonBase = rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/');
        $safeStoreBoot = build_safe_store_for_client($store);
        $safeStoreBoot['current_user'] = $cachedCurrentUser;
        $bootBundle = function_exists('zeynart_v4_cached_boot_bundle') ? zeynart_v4_cached_boot_bundle($store) : null;
        $blogCatsBoot = is_array($bootBundle['blog_categories_payload'] ?? null)
            ? $bootBundle['blog_categories_payload']
            : zeynart_compile_blog_categories_payload($store);
        $summaryBoot = compute_dashboard_summary($store);
        $sysHealthBoot = $isSuper ? zeynart_collect_system_health(false, false) : null;
        $unusedSummaryBoot = ($isSuper && function_exists('zeynart_get_unused_files_boot_summary'))
            ? zeynart_get_unused_files_boot_summary($store)
            : null;
        $versionHistoryBoot = function_exists('zeynart_v4_get_version_history')
            ? zeynart_v4_get_version_history()
            : [];
        $dbStatusBoot = $isSuper
            ? zeynart_collect_database_status()
            : ['active_engine' => (zeynart_is_sql_active() ? 'mysql' : 'json')];
        $bootElapsedMs = round((hrtime(true) - $bootStartHr) / 1e6, 1);
        if (is_array($sysHealthBoot)) {
            $sysHealthBoot['last_boot_ms'] = $bootElapsedMs;
        }
        api_json(true, [
            'boot_ms' => $bootElapsedMs,
            'admin_dir_name' => ADMIN_DIR_NAME,
            'sector_extensions' => zeynart_compile_sector_boot_payload($store, $activeRole),
            'turbo_engine' => [
                'mode' => (string)($bootBundle['engine'] ?? 'micro_index'),
                'cache_hit' => !empty($bootBundle['cache_hit']),
                'sqlite_wal' => extension_loaded('pdo_sqlite'),
                'sql_active' => zeynart_is_sql_active(),
            ],
            'database_status' => $dbStatusBoot,
            'version_history' => $versionHistoryBoot,
            'unused_files_summary' => $unusedSummaryBoot,
            'store' => $safeStoreBoot,
            'summary' => $summaryBoot,
            'blog_categories_payload' => $blogCatsBoot,
            'current_user' => $cachedCurrentUser,
            'robots_preview' => is_string($bootBundle['robots_preview'] ?? null) ? $bootBundle['robots_preview'] : compile_robots_txt($store),
            'llms_preview' => is_string($bootBundle['llms_preview'] ?? null) ? $bootBundle['llms_preview'] : compile_llms_txt($store, false),
            'logs_404' => zeynart_get_404_logs(),
            'revisions' => $isSuper ? zeynart_list_revisions() : [],
            'security_logs' => $isSuper ? array_slice(zeynart_get_audit_logs(), 0, 60) : [],
            'locked_ips' => $isSuper ? zeynart_get_locked_ips() : [],
            'security_audit_20' => $isSuper ? zeynart_run_security_20_audit($store) : null,
            'current_ip' => get_client_ip(),
            'news_bot_queue' => zeynart_list_news_bot_queue(),
            'news_bot_state' => zeynart_load_news_bot_state(),
            'news_bot_cron_url' => $isSuper ? ($canonBase . '/api/cron/haber-otopilot?key=' . $nbCfgBoot['cron_secret']) : '',
            'uptime_monitors' => $utMonsBoot,
            'uptime_summary' => zeynart_compute_uptime_summary($utMonsBoot, $utCfgBoot),
            'uptime_cron_url' => $isSuper ? ($canonBase . '/api/cron/uptime?key=' . $utCfgBoot['cron_secret']) : '',
            'proposals' => zeynart_load_proposals(),
            'crm_stages' => zeynart_crm_stages(),
            'crm_summary' => zeynart_compile_crm_summary(is_array($store['messages'] ?? null) ? $store['messages'] : []),
            'client_trackers' => zeynart_load_client_trackers(),
            'client_trackers_summary' => zeynart_compile_client_trackers_summary(),
            'renewals' => zeynart_load_client_renewals(),
            'renewals_summary' => zeynart_compile_renewals_summary(),
            'bookings' => zeynart_load_bookings(),
            'bookings_summary' => zeynart_compile_bookings_summary(),
            'solution_catalog' => zeynart_solution_page_catalog(),
            'blog_comments' => zeynart_load_blog_comments(),
            'search_insights' => zeynart_compile_search_insights(),
            'system_health' => $sysHealthBoot,
            'vault_backups' => $isSuper ? zeynart_list_vault_backups() : [],
            'ota_summary' => $isSuper ? zeynart_ota_boot_summary() : null,
            'indexnow_logs' => zeynart_get_indexnow_logs(),
        ]);
    }

    case 'admin_lazy_tab_data': {
        $isSuper = ($activeRole === 'super_admin');
        $scope = trim((string)($data['scope'] ?? ''));
        $out = ['scope' => $scope];
        if ($scope === 'revisions' && $isSuper) {
            $out['revisions'] = zeynart_list_revisions();
        } elseif ($scope === 'security' && $isSuper) {
            $out['security_logs'] = array_slice(zeynart_get_audit_logs(), 0, 80);
            $out['locked_ips'] = zeynart_get_locked_ips();
            $out['security_audit_20'] = zeynart_run_security_20_audit($store);
        } elseif ($scope === 'backups' && $isSuper) {
            $out['vault_backups'] = zeynart_list_vault_backups();
        } elseif ($scope === 'performance' && $isSuper) {
            $out['system_health'] = zeynart_collect_system_health(!empty($data['force']));
        } elseif ($scope === 'database' && $isSuper) {
            $out['database_status'] = zeynart_collect_database_status();
        } elseif ($scope === 'redirects') {
            $out['logs_404'] = zeynart_get_404_logs();
        }
        api_json(true, $out);
    }

    case 'analytics_stats': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $days = (int)($data['days'] ?? $_GET['days'] ?? 30);
        if (!in_array($days, [1, 7, 30, 90], true)) {
            $days = 30;
        }
        api_json(true, [
            'report' => zeynart_get_analytics_report($days),
        ]);
    }

    case 'analytics_reset': {
        zeynart_reset_analytics_data();
        $days = (int)($data['days'] ?? 30);
        if (!in_array($days, [1, 7, 30, 90], true)) {
            $days = 30;
        }
        api_json(true, [
            'message' => 'Ziyaretçi istatistikleri ve anlık trafik geçmişi sıfırlandı.',
            'report' => zeynart_get_analytics_report($days),
        ]);
    }

    case 'blog_list': {
        $search = mb_strtolower(trim((string)($data['search'] ?? $_GET['search'] ?? '')), 'UTF-8');
        $category = trim((string)($data['category'] ?? $_GET['category'] ?? 'TÜMÜ'));
        $statusFilter = trim((string)($data['status'] ?? $_GET['status'] ?? 'all'));
        $page = max(1, (int)($data['page'] ?? $_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($data['per_page'] ?? $_GET['per_page'] ?? 24)));

        $index = load_blog_index(true);
        $catPayload = zeynart_compile_blog_categories_payload($store, $index);
        $treeCats = $catPayload['categories'];
        $rawCats = $catPayload['raw_categories'];

        $categoriesMap = ['TÜMÜ' => count($index)];
        $targetCatObj = null;
        $wantFilterSlug = slugify($category);
        foreach ($treeCats as $tc) {
            $categoriesMap[$tc['name']] = (int)$tc['total_post_count'];
            if (
                $category !== ''
                && !zeynart_is_ignored_all_category_label($category)
                && ($tc['id'] === $category || $tc['slug'] === $wantFilterSlug || strcasecmp($tc['name'], $category) === 0)
            ) {
                $targetCatObj = $tc;
            }
        }

        $filtered = [];
        foreach ($index as $item) {
            $enrichedItem = zeynart_enrich_post_category_fields($item, $rawCats);
            if ($category !== '' && !zeynart_is_ignored_all_category_label($category)) {
                if ($targetCatObj !== null) {
                    if (!zeynart_post_matches_category($enrichedItem, $targetCatObj, $rawCats)) {
                        continue;
                    }
                } else {
                    $matchedLoose = false;
                    foreach (($enrichedItem['categories'] ?? [$enrichedItem['category']]) as $cn) {
                        if (strcasecmp((string)$cn, $category) === 0 || slugify((string)$cn) === $wantFilterSlug) {
                            $matchedLoose = true;
                            break;
                        }
                    }
                    if (!$matchedLoose) {
                        continue;
                    }
                }
            }
            $st = (string)($enrichedItem['status'] ?? 'published');
            if ($statusFilter === 'published' && $st !== 'published') {
                continue;
            }
            if ($statusFilter === 'draft' && $st !== 'draft') {
                continue;
            }
            if ($statusFilter === 'featured' && empty($enrichedItem['featured'])) {
                continue;
            }
            if ($statusFilter === 'curated' && empty($enrichedItem['curated'])) {
                continue;
            }
            if ($search !== '') {
                $catsJoined = implode(' ', $enrichedItem['categories'] ?? [(string)($enrichedItem['category'] ?? '')]);
                $hay = mb_strtolower(
                    ($enrichedItem['title'] ?? '') . ' ' . ($enrichedItem['slug'] ?? '') . ' ' . ($enrichedItem['excerpt'] ?? '') . ' ' . $catsJoined,
                    'UTF-8'
                );
                if (!str_contains($hay, $search)) {
                    continue;
                }
            }
            $filtered[] = $enrichedItem;
        }

        $total = count($filtered);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $items = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        api_json(true, [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'categories' => $categoriesMap,
            'blog_categories' => $treeCats,
            'default_category_id' => $catPayload['default_category_id'],
        ]);
    }

    case 'blog_get': {
        $slug = trim((string)($data['slug'] ?? $_GET['slug'] ?? ''));
        if ($slug === '') {
            api_json(false, ['error' => 'Yazı uzantısı (slug) belirtilmedi.'], 400);
        }
        $post = get_post($slug);
        if ($post === null) {
            api_json(false, ['error' => 'Blog yazısı bulunamadı: ' . $slug], 404);
        }
        $catPayload = zeynart_compile_blog_categories_payload($store);
        $post = zeynart_enrich_post_category_fields($post, $catPayload['raw_categories']);
        $seoCheck = analyze_seo_score([
            'title' => $post['title'] ?? '',
            'meta_title' => $post['seo']['meta_title'] ?? '',
            'excerpt' => $post['excerpt'] ?? '',
            'meta_desc' => $post['seo']['meta_desc'] ?? '',
            'focus_keyword' => $post['seo']['focus_keyword'] ?? '',
            'slug' => $post['slug'] ?? '',
            'content_html' => $post['content_html'] ?? '',
            'ai_summary' => $post['seo']['ai_summary'] ?? '',
            'image' => $post['image'] ?? '',
            'faqs' => $post['faqs'] ?? [],
        ]);
        api_json(true, [
            'post' => $post,
            'seo_analysis' => $seoCheck,
            'blog_categories' => $catPayload['categories'],
            'default_category_id' => $catPayload['default_category_id'],
        ]);
    }

    case 'blog_save': {
        $rawPost = is_array($data['post'] ?? null) ? $data['post'] : [];
        $oldSlug = isset($data['old_slug']) && is_string($data['old_slug']) && trim($data['old_slug']) !== ''
            ? trim($data['old_slug'])
            : null;

        $title = trim((string)($rawPost['title'] ?? ''));
        if ($title === '') {
            api_json(false, ['error' => 'Lütfen blog yazısı için bir başlık girin.'], 422);
        }

        $desiredSlug = trim((string)($rawPost['slug'] ?? ''));
        $slug = slugify($desiredSlug !== '' ? $desiredSlug : $title);

        // Load existing post if editing to preserve any extra metadata
        $existing = $oldSlug !== null ? (get_post($oldSlug) ?? []) : (get_post($slug) ?? []);

        $rawHtmlIn = (string)($rawPost['content_html'] ?? '');
        // Unwrap dynamic auto-internal links if saved from Live Visual Studio so rules remain centralized
        $rawHtmlIn = preg_replace('~<a\b[^>]*\bclass="[^"]*\bjournal-auto-link\b[^"]*"[^>]*>(.*?)</a>~isu', '$1', $rawHtmlIn) ?? $rawHtmlIn;
        $contentHtml = sanitize_rich_html($rawHtmlIn);
        $contentHtml = inject_heading_ids($contentHtml);

        $blogCats = zeynart_get_blog_categories($store);
        $catCandidate = [];
        if (array_key_exists('category_ids', $rawPost) && is_array($rawPost['category_ids'])) {
            $catCandidate['category_ids'] = $rawPost['category_ids'];
        } elseif (array_key_exists('categories', $rawPost) && is_array($rawPost['categories'])) {
            $catCandidate['categories'] = $rawPost['categories'];
        } elseif (array_key_exists('category', $rawPost)) {
            $catCandidate['category'] = (string)$rawPost['category'];
        } else {
            $catCandidate = $existing;
        }
        $resolvedPostCats = zeynart_resolve_post_categories($catCandidate, $blogCats);
        $resolvedCatIds = array_values(array_map(static fn(array $c): string => (string)$c['id'], $resolvedPostCats));
        $resolvedCatNames = array_values(array_map(static fn(array $c): string => (string)$c['name'], $resolvedPostCats));
        $resolvedCatSlugs = array_values(array_map(static fn(array $c): string => (string)$c['slug'], $resolvedPostCats));
        $category = $resolvedCatNames[0];

        $plainBody = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($contentHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        $excerpt = trim((string)($rawPost['excerpt'] ?? ''));
        if ($excerpt === '' && $plainBody !== '') {
            $excerpt = mb_strlen($plainBody, 'UTF-8') > 180
                ? rtrim(mb_substr($plainBody, 0, 177, 'UTF-8')) . '...'
                : $plainBody;
        }

        $dateIso = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($rawPost['date'] ?? ''))
            ? (string)$rawPost['date']
            : date('Y-m-d');

        $toc = [];
        if (!empty($rawPost['toc']) && is_array($rawPost['toc'])) {
            foreach ($rawPost['toc'] as $ti) {
                $tTitle = trim((string)($ti['title'] ?? ''));
                $tId = slugify((string)($ti['id'] ?? $tTitle));
                if ($tTitle !== '') {
                    $toc[] = ['id' => $tId, 'title' => $tTitle];
                }
            }
        }
        if ($toc === []) {
            $toc = extract_toc_from_html($contentHtml);
        }

        $faqs = [];
        if (!empty($rawPost['faqs']) && is_array($rawPost['faqs'])) {
            foreach ($rawPost['faqs'] as $fq) {
                $q = trim((string)($fq['q'] ?? ''));
                $a = trim((string)($fq['a'] ?? ''));
                if ($q !== '' && $a !== '') {
                    $faqs[] = ['q' => $q, 'a' => $a];
                }
            }
        }

        $tags = [];
        if (isset($rawPost['tags'])) {
            $rawTags = is_array($rawPost['tags']) ? $rawPost['tags'] : explode(',', (string)$rawPost['tags']);
            foreach ($rawTags as $tg) {
                $tgClean = trim((string)$tg);
                if ($tgClean !== '') {
                    $tags[] = $tgClean;
                }
            }
        }

        $relatedSlugs = [];
        if (isset($rawPost['related_slugs'])) {
            $rawRel = is_array($rawPost['related_slugs']) ? $rawPost['related_slugs'] : explode(',', (string)$rawPost['related_slugs']);
            foreach ($rawRel as $rs) {
                $rsClean = slugify(trim((string)$rs));
                if ($rsClean !== '' && $rsClean !== $slug) {
                    $relatedSlugs[] = $rsClean;
                }
            }
        }

        $rawCoverCandidate = $rawPost['image'] ?? ($rawPost['cover'] ?? '/images/journal/web-planning.webp');
        $image = strtok(trim((string)$rawCoverCandidate), '?') ?: '/images/journal/web-planning.webp';
        if ($image === '') {
            $image = '/images/journal/web-planning.webp';
        }

        $seoIn = is_array($rawPost['seo'] ?? null) ? $rawPost['seo'] : [];
        $metaTitle = trim((string)($seoIn['meta_title'] ?? ($rawPost['meta_title'] ?? '')));
        if ($metaTitle === '') {
            $metaTitle = $title . ' | ZEYNART';
        }
        $metaDesc = trim((string)($seoIn['meta_desc'] ?? ($rawPost['meta_desc'] ?? '')));
        if ($metaDesc === '') {
            $metaDesc = mb_substr($excerpt, 0, 160, 'UTF-8');
        }
        $focusKw = trim((string)($seoIn['focus_keyword'] ?? ''));
        $aiSummary = trim((string)($seoIn['ai_summary'] ?? ''));
        if ($aiSummary === '' && $excerpt !== '') {
            $aiSummary = $excerpt;
        }

        $seoAnalysis = analyze_seo_score([
            'title' => $title,
            'meta_title' => $metaTitle,
            'excerpt' => $excerpt,
            'meta_desc' => $metaDesc,
            'focus_keyword' => $focusKw,
            'slug' => $slug,
            'content_html' => $contentHtml,
            'ai_summary' => $aiSummary,
            'image' => $image,
            'faqs' => $faqs,
        ]);

        $proseMode = (string)($rawPost['prose_mode'] ?? ($existing['prose_mode'] ?? 'html'));
        if (!str_contains($contentHtml, 'journal-section-number')) {
            $proseMode = 'html';
        }

        $statusVal = (string)($rawPost['status'] ?? ($existing['status'] ?? 'published'));
        $prevCoverImg = strtok(trim((string)($existing['image'] ?? '')), '?') ?: '';
        $rawOgImg = strtok(trim((string)($seoIn['og_image'] ?? '')), '?') ?: '';
        if ($rawOgImg === '' || ($prevCoverImg !== '' && $rawOgImg === $prevCoverImg && $image !== $prevCoverImg)) {
            $resolvedOgImg = $image;
        } else {
            $resolvedOgImg = $rawOgImg;
        }

        $authorIdIn = trim((string)($rawPost['author_id'] ?? ($existing['author_id'] ?? '')));
        $resolvedAuthorObj = zeynart_resolve_post_author(['author_id' => $authorIdIn, 'author' => (string)($rawPost['author'] ?? '')], $store);
        $authorNameFinal = trim((string)($rawPost['author'] ?? '')) !== '' ? trim((string)$rawPost['author']) : (string)$resolvedAuthorObj['name'];
        $authorRoleFinal = trim((string)($rawPost['author_role'] ?? '')) !== '' ? trim((string)$rawPost['author_role']) : (string)$resolvedAuthorObj['role'];
        if ($authorIdIn !== '') {
            foreach (zeynart_get_authors_list($store) as $candAuth) {
                if ($candAuth['id'] === $authorIdIn || $candAuth['slug'] === $authorIdIn) {
                    $authorNameFinal = (string)$candAuth['name'];
                    $authorRoleFinal = (string)$candAuth['role'];
                    break;
                }
            }
        }
        $schemaTypeIn = (string)($seoIn['schema_type'] ?? ($existing['seo']['schema_type'] ?? 'BlogPosting'));
        if (!in_array($schemaTypeIn, ['BlogPosting', 'TechArticle', 'NewsArticle', 'Article'], true)) {
            $schemaTypeIn = 'BlogPosting';
        }

        $postToSave = array_merge($existing, [
            'id' => $slug,
            'slug' => $slug,
            'title' => $title,
            'category' => $category,
            'categories' => $resolvedCatNames,
            'category_ids' => $resolvedCatIds,
            'category_slugs' => $resolvedCatSlugs,
            'excerpt' => $excerpt,
            'date' => $dateIso,
            'date_display' => format_tr_date($dateIso),
            'read' => trim((string)($rawPost['read'] ?? '')) !== ''
                ? trim((string)$rawPost['read'])
                : estimate_reading_time($contentHtml),
            'image' => $image,
            'image_alt' => trim((string)($rawPost['image_alt'] ?? ($rawPost['cover_alt'] ?? $title))),
            'status' => in_array($statusVal, ['published', 'draft'], true) ? $statusVal : 'published',
            'featured' => !empty($rawPost['featured']),
            'curated' => !empty($rawPost['curated']),
            'author_id' => $authorIdIn !== '' ? $authorIdIn : (string)$resolvedAuthorObj['id'],
            'author' => $authorNameFinal,
            'author_role' => $authorRoleFinal,
            'prose_mode' => $proseMode,
            'content_html' => $contentHtml,
            'toc' => $toc,
            'service_link' => [
                'href' => trim((string)($rawPost['service_link']['href'] ?? '/hizmetler/')),
                'label' => trim((string)($rawPost['service_link']['label'] ?? 'Hizmetlerimiz')),
            ],
            'related_slugs' => $relatedSlugs,
            'tags' => $tags,
            'faqs' => $faqs,
            'seo' => [
                'meta_title' => $metaTitle,
                'meta_desc' => $metaDesc,
                'og_title' => trim((string)($seoIn['og_title'] ?? '')),
                'og_desc' => trim((string)($seoIn['og_desc'] ?? '')),
                'focus_keyword' => $focusKw,
                'canonical' => trim((string)($seoIn['canonical'] ?? '')),
                'robots' => trim((string)($seoIn['robots'] ?? 'index, follow')),
                'og_image' => $resolvedOgImg,
                'ai_summary' => $aiSummary,
                'schema_type' => $schemaTypeIn,
                'seo_score' => $seoAnalysis['score'],
            ],
        ]);

        // Merge any manual per-language translation overrides from Admin Blog Editor
        if (isset($rawPost['translations']) && is_array($rawPost['translations'])) {
            $postToSave['translations'] = is_array($postToSave['translations'] ?? null) ? $postToSave['translations'] : [];
            foreach (['en', 'de', 'ru'] as $tLang) {
                if (!isset($rawPost['translations'][$tLang]) || !is_array($rawPost['translations'][$tLang])) {
                    continue;
                }
                $inTr = $rawPost['translations'][$tLang];
                $prevTr = is_array($postToSave['translations'][$tLang] ?? null) ? $postToSave['translations'][$tLang] : [];
                $trTitle = trim((string)($inTr['title'] ?? ($prevTr['title'] ?? '')));
                if ($trTitle === '' && empty($inTr['content_html']) && empty($prevTr['title'])) {
                    continue;
                }
                $rawLocSlug = trim((string)($inTr['slug'] ?? ($prevTr['slug'] ?? $trTitle)));
                $locSlug = zeynart_slugify_multilang($rawLocSlug !== '' ? $rawLocSlug : $trTitle, $tLang, $slug);
                $inTrSeo = is_array($inTr['seo'] ?? null) ? $inTr['seo'] : [];
                $prevTrSeo = is_array($prevTr['seo'] ?? null) ? $prevTr['seo'] : [];
                $postToSave['translations'][$tLang] = array_merge($prevTr, [
                    'lang' => $tLang,
                    'slug' => $locSlug,
                    'title' => $trTitle,
                    'category' => trim((string)($inTr['category'] ?? ($prevTr['category'] ?? t_category($category, $tLang)))),
                    'excerpt' => trim((string)($inTr['excerpt'] ?? ($prevTr['excerpt'] ?? ''))),
                    'content_html' => isset($inTr['content_html'])
                        ? sanitize_rich_html((string)$inTr['content_html'])
                        : (string)($prevTr['content_html'] ?? ''),
                    'date_display' => format_localized_date($dateIso, $tLang),
                    'read' => localize_reading_time((string)$postToSave['read'], $tLang),
                    'seo' => array_merge($prevTrSeo, [
                        'meta_title' => trim((string)($inTrSeo['meta_title'] ?? ($prevTrSeo['meta_title'] ?? ''))),
                        'meta_desc' => trim((string)($inTrSeo['meta_desc'] ?? ($prevTrSeo['meta_desc'] ?? ''))),
                        'og_title' => trim((string)($inTrSeo['og_title'] ?? ($prevTrSeo['og_title'] ?? ''))),
                        'og_desc' => trim((string)($inTrSeo['og_desc'] ?? ($prevTrSeo['og_desc'] ?? ''))),
                    ]),
                    'manual_lock' => !empty($inTr['manual_lock']),
                ]);
            }
        }

        // Automatically translate new or updated blog posts into English (EN), German (DE), and Russian (RU)
        $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
        $defaultAutoTranslate = ($i18nCfg['auto_translate_blog'] ?? true) !== false;
        $autoTranslate = array_key_exists('auto_translate', $data)
            ? !empty($data['auto_translate'])
            : (array_key_exists('auto_translate', $rawPost) ? !empty($rawPost['auto_translate']) : $defaultAutoTranslate);
        $forceRetranslate = !empty($data['force_retranslate']) || !empty($rawPost['force_retranslate']);

        if ($autoTranslate || $forceRetranslate) {
            $postToSave = zeynart_translate_blog_post($postToSave, ['en', 'de', 'ru'], $forceRetranslate, $i18nCfg);
        }

        require_once INCLUDES_DIR . '/social_card.php';
        zeynart_resolve_social_image((string)($postToSave['seo']['og_image'] ?? $image), $store);

        $saved = save_post($postToSave, $oldSlug);
        zeynart_audit_log('blog_save', 'Blog makalesi kaydedildi: ' . $title . ' (/' . $slug . '/)');

        // Auto-add loop-free 301 redirect if slug changed on an existing published post
        $blogBaseSlug = zeynart_page_slug($store, 'blog') ?: 'blog';
        if ($oldSlug !== null && $oldSlug !== $slug) {
            zeynart_register_url_rename($store, '/' . $blogBaseSlug . '/' . $oldSlug . '/', '/' . $blogBaseSlug . '/' . $slug . '/');
            save_store_or_fail($store);
        }

        $viewUrls = [
            'tr' => lang_url('/' . $blogBaseSlug . '/' . $saved['slug'] . '/', 'tr'),
            'en' => lang_url('/' . $blogBaseSlug . '/' . ($saved['translations']['en']['slug'] ?? $saved['slug']) . '/', 'en'),
            'de' => lang_url('/' . $blogBaseSlug . '/' . ($saved['translations']['de']['slug'] ?? $saved['slug']) . '/', 'de'),
            'ru' => lang_url('/' . $blogBaseSlug . '/' . ($saved['translations']['ru']['slug'] ?? $saved['slug']) . '/', 'ru'),
        ];
        $hasAllLangs = !empty($saved['translations']['en']['title'])
            && !empty($saved['translations']['de']['title'])
            && !empty($saved['translations']['ru']['title']);

        api_json(true, [
            'message' => $hasAllLangs
                ? 'Blog yazısı kaydedildi ve İngilizce, Almanca, Rusça dillerine otomatik çevrildi.'
                : 'Blog yazısı başarıyla kaydedildi.',
            'post' => $saved,
            'seo_analysis' => $seoAnalysis,
            'view_url' => $viewUrls['tr'],
            'view_urls' => $viewUrls,
        ]);
    }

    case 'blog_categories_list': {
        $payload = zeynart_compile_blog_categories_payload($store);
        api_json(true, $payload);
    }

    case 'blog_category_save': {
        $catIn = is_array($data['category'] ?? null) ? $data['category'] : $data;
        $name = trim((string)($catIn['name'] ?? ''));
        if ($name === '' || zeynart_is_ignored_all_category_label($name)) {
            api_json(false, ['error' => 'Lütfen geçerli bir kategori adı girin.'], 422);
        }

        $oldCats = zeynart_get_blog_categories($store);
        $oldTree = zeynart_build_blog_category_tree($oldCats);
        $editId = trim((string)($catIn['id'] ?? ''));
        $isEdit = false;
        $existingIdx = null;

        foreach ($oldCats as $idx => $oc) {
            if ($editId !== '' && $oc['id'] === $editId) {
                $isEdit = true;
                $existingIdx = $idx;
                break;
            }
        }

        $rawSlug = trim((string)($catIn['slug'] ?? ''));
        $baseSlug = slugify($rawSlug !== '' ? $rawSlug : $name);
        if ($baseSlug === '' || $baseSlug === 'kategori') {
            $baseSlug = 'kategori-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $usedSlugs = [];
        foreach ($oldCats as $oc) {
            if (!$isEdit || $oc['id'] !== $editId) {
                $usedSlugs[$oc['slug']] = true;
            }
        }
        $finalSlug = $baseSlug;
        $suffixNum = 2;
        while (isset($usedSlugs[$finalSlug])) {
            $finalSlug = $baseSlug . '-' . $suffixNum;
            $suffixNum++;
        }

        $parentId = trim((string)($catIn['parent_id'] ?? ''));
        if ($parentId !== '') {
            $parentExists = false;
            foreach ($oldCats as $oc) {
                if ($oc['id'] === $parentId) {
                    $parentExists = true;
                    break;
                }
            }
            if (!$parentExists) {
                $parentId = '';
            } elseif ($isEdit) {
                if ($parentId === $editId) {
                    api_json(false, ['error' => 'Bir kategori kendisinin alt kategorisi olamaz.'], 422);
                }
                foreach ($oldTree as $tn) {
                    if ($tn['id'] === $editId && in_array($parentId, $tn['descendant_ids'] ?? [], true)) {
                        api_json(false, ['error' => 'Döngüsel hiyerarşi engellendi: Bir kategoriyi kendi alt kategorisinin altına taşıyamazsınız.'], 422);
                    }
                }
            }
        }

        $description = trim((string)($catIn['description'] ?? ''));
        $metaTitle = trim((string)($catIn['meta_title'] ?? ''));
        $metaDesc = trim((string)($catIn['meta_desc'] ?? ''));
        $wantDefault = !empty($catIn['is_default']);

        $nextCats = $oldCats;
        if ($isEdit && $existingIdx !== null) {
            $prevItem = $nextCats[$existingIdx];
            $nextOrder = isset($catIn['order']) && is_numeric($catIn['order'])
                ? max(1, (int)$catIn['order'])
                : (int)$prevItem['order'];
            if ($wantDefault) {
                foreach ($nextCats as $ni => $_) {
                    $nextCats[$ni]['is_default'] = false;
                }
            }
            $nextCats[$existingIdx] = [
                'id' => $prevItem['id'],
                'name' => $name,
                'slug' => $finalSlug,
                'parent_id' => $parentId,
                'description' => $description,
                'meta_title' => $metaTitle,
                'meta_desc' => $metaDesc,
                'order' => $nextOrder,
                'is_default' => $wantDefault ? true : !empty($prevItem['is_default']),
            ];
            $savedId = (string)$prevItem['id'];
        } else {
            $newId = 'cat_' . str_replace('-', '_', $finalSlug);
            $existingIds = array_flip(array_map(static fn(array $c): string => (string)$c['id'], $nextCats));
            if (isset($existingIds[$newId])) {
                $newId = 'cat_' . bin2hex(random_bytes(4));
            }
            if ($wantDefault) {
                foreach ($nextCats as $ni => $_) {
                    $nextCats[$ni]['is_default'] = false;
                }
            }
            $nextOrder = isset($catIn['order']) && is_numeric($catIn['order'])
                ? max(1, (int)$catIn['order'])
                : (count($nextCats) + 1);
            $nextCats[] = [
                'id' => $newId,
                'name' => $name,
                'slug' => $finalSlug,
                'parent_id' => $parentId,
                'description' => $description,
                'meta_title' => $metaTitle,
                'meta_desc' => $metaDesc,
                'order' => $nextOrder,
                'is_default' => $wantDefault,
            ];
            $savedId = $newId;
        }

        $store['blog_categories'] = zeynart_normalize_blog_categories($nextCats, null, [], false);
        $propInfo = zeynart_propagate_blog_category_changes($store, $oldCats, $store['blog_categories']);
        save_store_or_fail($store);

        $payload = zeynart_compile_blog_categories_payload($store);
        $savedCategory = null;
        foreach ($payload['categories'] as $tc) {
            if ($tc['id'] === $savedId) {
                $savedCategory = $tc;
                break;
            }
        }

        $msg = $isEdit ? 'Kategori bilgileri güncellendi.' : 'Yeni blog kategorisi oluşturuldu.';
        if (($propInfo['redirects_added'] ?? 0) > 0) {
            $msg .= ' Eski kategori adresinden yeni adrese 4 dilde otomatik 301 yönlendirmesi tanımlandı.';
        }

        api_json(true, array_merge($payload, [
            'message' => $msg,
            'saved_category' => $savedCategory,
            'redirects' => $store['redirects'] ?? [],
            'propagated' => $propInfo,
        ]));
    }

    case 'blog_category_delete':
    case 'blog_category_bulk_delete': {
        $rawIds = [];
        if ($action === 'blog_category_bulk_delete' || isset($data['ids'])) {
            $inIds = is_array($data['ids'] ?? null) ? $data['ids'] : [];
            foreach ($inIds as $rid) {
                $s = trim((string)$rid);
                if ($s !== '') {
                    $rawIds[] = $s;
                }
            }
        } else {
            $singleId = trim((string)($data['id'] ?? ''));
            if ($singleId !== '') {
                $rawIds[] = $singleId;
            }
        }
        $rawIds = array_values(array_unique($rawIds));
        if ($rawIds === []) {
            api_json(false, ['error' => 'Silinecek kategori seçilmedi.'], 422);
        }

        $oldCats = zeynart_get_blog_categories($store);
        $oldById = [];
        $defaultId = '';
        $defaultName = '';
        foreach ($oldCats as $oc) {
            $oldById[$oc['id']] = $oc;
            if (!empty($oc['is_default'])) {
                $defaultId = (string)$oc['id'];
                $defaultName = (string)$oc['name'];
            }
        }

        $skippedDefault = false;
        $deleteSet = [];
        foreach ($rawIds as $cid) {
            if (!isset($oldById[$cid])) {
                continue;
            }
            if ($cid === $defaultId) {
                $skippedDefault = true;
                continue;
            }
            $deleteSet[$cid] = true;
        }

        if ($deleteSet === []) {
            if ($skippedDefault) {
                api_json(false, [
                    'error' => 'Varsayılan kategori ("' . $defaultName . '") doğrudan silinemez. Silmek için önce listeden başka bir kategoriyi "Varsayılan" olarak belirleyin.',
                ], 422);
            }
            api_json(false, ['error' => 'Silinecek geçerli bir kategori bulunamadı.'], 404);
        }

        // Promote child categories whose parent is deleted so hierarchy stays intact
        $resolveSurvivingParent = static function (string $pid) use ($oldById, $deleteSet): string {
            $guard = 0;
            $cur = $pid;
            while ($cur !== '' && isset($deleteSet[$cur]) && $guard < 50) {
                $cur = isset($oldById[$cur]) ? (string)$oldById[$cur]['parent_id'] : '';
                $guard++;
            }
            return isset($oldById[$cur]) && !isset($deleteSet[$cur]) ? $cur : '';
        };

        $remaining = [];
        foreach ($oldCats as $oc) {
            if (isset($deleteSet[$oc['id']])) {
                continue;
            }
            if ($oc['parent_id'] !== '' && isset($deleteSet[$oc['parent_id']])) {
                $oc['parent_id'] = $resolveSurvivingParent((string)$oc['parent_id']);
            }
            $remaining[] = $oc;
        }

        $store['blog_categories'] = zeynart_normalize_blog_categories($remaining, null, [], false);
        $propInfo = zeynart_propagate_blog_category_changes($store, $oldCats, $store['blog_categories']);
        save_store_or_fail($store);

        $deletedCount = count($deleteSet);
        $payload = zeynart_compile_blog_categories_payload($store);
        $msg = $deletedCount === 1
            ? 'Kategori silindi; bu kategoriye bağlı blog yazıları silinmeden korundu.'
            : ($deletedCount . ' kategori silindi; bağlı tüm blog yazıları silinmeden korundu.');
        if (($propInfo['updated_posts'] ?? 0) > 0) {
            $msg .= ' (' . (int)$propInfo['updated_posts'] . ' yazı güncellendi / varsayılan kategoriye aktarıldı.)';
        }
        if ($skippedDefault) {
            $msg .= ' Not: Varsayılan kategori ("' . $defaultName . '") korunarak silme işleminden hariç tutuldu.';
        }

        api_json(true, array_merge($payload, [
            'message' => $msg,
            'deleted_count' => $deletedCount,
            'skipped_default' => $skippedDefault,
            'propagated' => $propInfo,
        ]));
    }

    case 'blog_category_set_default': {
        $targetId = trim((string)($data['id'] ?? ''));
        if ($targetId === '') {
            api_json(false, ['error' => 'Varsayılan yapılacak kategori seçilmedi.'], 422);
        }
        $cats = zeynart_get_blog_categories($store);
        $foundName = null;
        foreach ($cats as $i => $c) {
            if ($c['id'] === $targetId) {
                $cats[$i]['is_default'] = true;
                $foundName = (string)$c['name'];
            } else {
                $cats[$i]['is_default'] = false;
            }
        }
        if ($foundName === null) {
            api_json(false, ['error' => 'Belirtilen kategori bulunamadı.'], 404);
        }
        $store['blog_categories'] = zeynart_normalize_blog_categories($cats, null, [], false);
        save_store_or_fail($store);

        $payload = zeynart_compile_blog_categories_payload($store);
        api_json(true, array_merge($payload, [
            'message' => '"' . $foundName . '" varsayılan blog kategorisi olarak ayarlandı.',
        ]));
    }

    case 'blog_category_reorder': {
        $cats = zeynart_get_blog_categories($store);
        $byId = [];
        foreach ($cats as $c) {
            $byId[$c['id']] = $c;
        }

        $orderedIds = is_array($data['ordered_ids'] ?? null) ? $data['ordered_ids'] : [];
        $moveId = trim((string)($data['id'] ?? ''));
        $direction = strtolower(trim((string)($data['direction'] ?? '')));

        if ($moveId !== '' && in_array($direction, ['up', 'down'], true) && isset($byId[$moveId])) {
            $targetParent = (string)$byId[$moveId]['parent_id'];
            $siblingIndices = [];
            foreach ($cats as $idx => $c) {
                if ((string)$c['parent_id'] === $targetParent) {
                    $siblingIndices[] = $idx;
                }
            }
            $posInSiblings = null;
            foreach ($siblingIndices as $sPos => $catIdx) {
                if ($cats[$catIdx]['id'] === $moveId) {
                    $posInSiblings = $sPos;
                    break;
                }
            }
            if ($posInSiblings !== null) {
                $swapPos = $direction === 'up' ? ($posInSiblings - 1) : ($posInSiblings + 1);
                if (isset($siblingIndices[$swapPos])) {
                    $idxA = $siblingIndices[$posInSiblings];
                    $idxB = $siblingIndices[$swapPos];
                    $tmp = $cats[$idxA];
                    $cats[$idxA] = $cats[$idxB];
                    $cats[$idxB] = $tmp;
                    foreach ($cats as $i => $_) {
                        $cats[$i]['order'] = $i + 1;
                    }
                }
            }
        } elseif ($orderedIds !== []) {
            $reordered = [];
            $seen = [];
            foreach ($orderedIds as $oid) {
                $oidStr = trim((string)$oid);
                if ($oidStr !== '' && isset($byId[$oidStr]) && !isset($seen[$oidStr])) {
                    $seen[$oidStr] = true;
                    $reordered[] = $byId[$oidStr];
                }
            }
            foreach ($cats as $c) {
                if (!isset($seen[$c['id']])) {
                    $reordered[] = $c;
                }
            }
            foreach ($reordered as $i => $_) {
                $reordered[$i]['order'] = $i + 1;
            }
            $cats = $reordered;
        } else {
            api_json(false, ['error' => 'Sıralama verisi gönderilmedi.'], 422);
        }

        $store['blog_categories'] = zeynart_normalize_blog_categories($cats, null, [], false);
        save_store_or_fail($store);

        $payload = zeynart_compile_blog_categories_payload($store);
        api_json(true, array_merge($payload, [
            'message' => 'Kategori görüntülenme sırası güncellendi.',
        ]));
    }

    case 'blog_translate_queue': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $index = load_blog_index(true);
        $items = [];
        $translatedCount = 0;
        $pendingCount = 0;

        foreach ($index as $it) {
            if (($it['status'] ?? 'published') !== 'published') {
                continue;
            }
            $bSlug = trim((string)($it['slug'] ?? ''));
            $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $bSlug) ?? '';
            if ($safeSlug === '' || !post_exists($safeSlug)) {
                continue;
            }
            $hasAll = !empty($it['i18n']['en']['title'])
                && !empty($it['i18n']['de']['title'])
                && !empty($it['i18n']['ru']['title']);
            if ($hasAll) {
                $translatedCount++;
            } else {
                $pendingCount++;
            }
            $items[] = [
                'slug' => $safeSlug,
                'title' => (string)($it['title'] ?? $safeSlug),
                'category' => (string)($it['category'] ?? 'GENEL'),
                'date' => (string)($it['date_display'] ?? ($it['date'] ?? '')),
                'done' => $hasAll,
                'en_slug' => (string)($it['i18n']['en']['slug'] ?? $safeSlug),
                'de_slug' => (string)($it['i18n']['de']['slug'] ?? $safeSlug),
                'ru_slug' => (string)($it['i18n']['ru']['slug'] ?? $safeSlug),
                'en_title' => (string)($it['i18n']['en']['title'] ?? ''),
            ];
        }

        $totalPublished = $translatedCount + $pendingCount;
        $percent = $totalPublished > 0
            ? round(($translatedCount / $totalPublished) * 100, 1)
            : 100.0;

        api_json(true, [
            'total_posts' => $totalPublished,
            'translated_count' => $translatedCount,
            'pending_count' => $pendingCount,
            'percent' => $percent,
            'items' => $items,
        ]);
    }

    case 'blog_translate': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        @set_time_limit(60);

        $slug = trim((string)($data['slug'] ?? ''));
        $draftPost = is_array($data['post'] ?? null) ? $data['post'] : [];
        $targetLangs = is_array($data['target_langs'] ?? null) ? $data['target_langs'] : ['en', 'de', 'ru'];
        $force = array_key_exists('force', $data) ? !empty($data['force']) : true;
        $compact = !empty($data['compact']);

        $diskPost = $slug !== '' ? get_post($slug) : null;
        if ($diskPost === null && $draftPost === []) {
            api_json(false, ['error' => 'Çevrilecek blog yazısı bulunamadı.'], 404);
        }

        $working = $diskPost ?? [];
        if ($draftPost !== []) {
            $dTitle = trim((string)($draftPost['title'] ?? ($working['title'] ?? '')));
            if ($dTitle === '') {
                api_json(false, ['error' => 'Otomatik çeviri için önce Türkçe yazı başlığını girin.'], 422);
            }
            $dSlug = slugify(trim((string)($draftPost['slug'] ?? ($working['slug'] ?? $dTitle))));
            $dHtml = sanitize_rich_html((string)($draftPost['content_html'] ?? ($working['content_html'] ?? '')));
            $dHtml = inject_heading_ids($dHtml);
            $dSeo = is_array($draftPost['seo'] ?? null) ? $draftPost['seo'] : [];
            $working = array_merge($working, [
                'slug' => $dSlug,
                'title' => $dTitle,
                'category' => mb_strtoupper(trim((string)($draftPost['category'] ?? ($working['category'] ?? 'GENEL'))), 'UTF-8') ?: 'GENEL',
                'excerpt' => trim((string)($draftPost['excerpt'] ?? ($working['excerpt'] ?? ''))),
                'image_alt' => trim((string)($draftPost['image_alt'] ?? $dTitle)),
                'author_role' => trim((string)($draftPost['author_role'] ?? ($working['author_role'] ?? 'Tasarım & teknoloji notları'))),
                'date' => (string)($draftPost['date'] ?? ($working['date'] ?? date('Y-m-d'))),
                'read' => trim((string)($draftPost['read'] ?? '')) ?: estimate_reading_time($dHtml),
                'content_html' => $dHtml,
                'toc' => !empty($draftPost['toc']) && is_array($draftPost['toc']) ? $draftPost['toc'] : extract_toc_from_html($dHtml),
                'faqs' => is_array($draftPost['faqs'] ?? null) ? $draftPost['faqs'] : ($working['faqs'] ?? []),
                'service_link' => is_array($draftPost['service_link'] ?? null) ? $draftPost['service_link'] : ($working['service_link'] ?? ['href' => '/hizmetler/', 'label' => 'Hizmetlerimiz']),
                'seo' => array_merge(is_array($working['seo'] ?? null) ? $working['seo'] : [], $dSeo),
            ]);
        }

        if ($force && isset($working['translations']) && is_array($working['translations'])) {
            foreach ($targetLangs as $tl) {
                $tlk = strtolower(trim((string)$tl));
                if (isset($working['translations'][$tlk]) && is_array($working['translations'][$tlk])) {
                    $working['translations'][$tlk]['manual_lock'] = false;
                }
            }
        }

        $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
        if (is_array($data['i18n_override'] ?? null)) {
            $i18nCfg = array_merge($i18nCfg, $data['i18n_override']);
        }
        $translated = zeynart_translate_blog_post($working, $targetLangs, $force, $i18nCfg);

        $shouldSave = ($diskPost !== null) && empty($data['draft_only']);
        if ($shouldSave) {
            $translated = save_post($translated, (string)($diskPost['slug'] ?? $slug));
        }

        $bSlug = (string)($translated['slug'] ?? $slug);
        $i18nMini = [];
        foreach (['en', 'de', 'ru'] as $lc) {
            $trRow = $translated['translations'][$lc] ?? [];
            if (is_array($trRow) && !empty($trRow['title'])) {
                $i18nMini[$lc] = [
                    'slug' => (string)($trRow['slug'] ?? $bSlug),
                    'title' => (string)$trRow['title'],
                    'category' => (string)($trRow['category'] ?? ''),
                    'excerpt' => (string)($trRow['excerpt'] ?? ''),
                ];
            }
        }
        $viewUrls = [
            'tr' => lang_url('/blog/' . $bSlug . '/', 'tr'),
            'en' => lang_url('/blog/' . ($translated['translations']['en']['slug'] ?? $bSlug) . '/', 'en'),
            'de' => lang_url('/blog/' . ($translated['translations']['de']['slug'] ?? $bSlug) . '/', 'de'),
            'ru' => lang_url('/blog/' . ($translated['translations']['ru']['slug'] ?? $bSlug) . '/', 'ru'),
        ];

        $resp = [
            'message' => 'Yazı İngilizce (EN), Almanca (DE) ve Rusça (RU) dillerine otomatik çevrildi.',
            'slug' => $bSlug,
            'title' => (string)($translated['title'] ?? ''),
            'i18n' => $i18nMini,
            'saved' => $shouldSave,
            'view_urls' => $viewUrls,
        ];
        if (!$compact) {
            $resp['post'] = $translated;
            $resp['translations'] = $translated['translations'] ?? [];
        }
        api_json(true, $resp);
    }

    case 'blog_translate_batch': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        @set_time_limit(60);

        $limit = max(1, min(10, (int)($data['limit'] ?? 2)));
        $forceAll = !empty($data['force']);
        $index = load_blog_index(true);
        $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
        if (is_array($data['i18n_override'] ?? null)) {
            $i18nCfg = array_merge($i18nCfg, $data['i18n_override']);
        }

        $missingSlugs = [];
        $alreadyTranslated = 0;
        foreach ($index as $it) {
            if (($it['status'] ?? 'published') !== 'published') {
                continue;
            }
            $bSlug = trim((string)($it['slug'] ?? ''));
            $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $bSlug) ?? '';
            if ($safeSlug === '' || !post_exists($safeSlug)) {
                continue;
            }
            $hasAll = !empty($it['i18n']['en']['title'])
                && !empty($it['i18n']['de']['title'])
                && !empty($it['i18n']['ru']['title']);
            if ($hasAll && !$forceAll) {
                $alreadyTranslated++;
            } else {
                $missingSlugs[] = $safeSlug;
            }
        }

        $batchSlugs = array_slice($missingSlugs, 0, $limit);
        $doneSlugs = [];
        $doneItems = [];
        foreach ($batchSlugs as $bSlug) {
            $p = get_post($bSlug);
            if ($p === null) {
                continue;
            }
            $p = zeynart_translate_blog_post($p, ['en', 'de', 'ru'], $forceAll, $i18nCfg);
            $p = save_post($p, $bSlug);
            $doneSlugs[] = $bSlug;
            $doneItems[] = [
                'slug' => $bSlug,
                'title' => (string)($p['title'] ?? $bSlug),
                'en_slug' => (string)($p['translations']['en']['slug'] ?? $bSlug),
                'de_slug' => (string)($p['translations']['de']['slug'] ?? $bSlug),
                'ru_slug' => (string)($p['translations']['ru']['slug'] ?? $bSlug),
            ];
        }

        $remaining = max(0, count($missingSlugs) - count($doneSlugs));
        $totalDone = $alreadyTranslated + count($doneSlugs);
        $totalPublished = $alreadyTranslated + count($missingSlugs);
        $pct = $totalPublished > 0 ? round(($totalDone / $totalPublished) * 100, 1) : 100.0;

        api_json(true, [
            'message' => count($doneSlugs) > 0
                ? (count($doneSlugs) . ' blog yazısı EN · DE · RU dillerine çevrildi. (Kalan: ' . $remaining . ')')
                : ($remaining > 0 ? 'Sıradaki yazılar hazır.' : 'Tüm yayındaki blog yazıları EN · DE · RU dillerine çevrilmiş durumda!'),
            'translated_count' => count($doneSlugs),
            'translated_slugs' => $doneSlugs,
            'translated_items' => $doneItems,
            'remaining_count' => $remaining,
            'total_translated' => $totalDone,
            'total_posts' => $totalPublished,
            'percent' => $pct,
        ]);
    }

    case 'i18n_cache_clear': {
        $cleared = 0;
        if (zeynart_is_sql_active() && ($pdo = zeynart_get_pdo()) !== null) {
            $tKv = zeynart_sql_table('store_kv');
            $stDel = $pdo->prepare("DELETE FROM `{$tKv}` WHERE group_name = 'i18n'");
            $stDel->execute();
            $cleared += (int)$stDel->rowCount();
            $GLOBALS['__zeynart_sql_kv_mem'] = [];
        }
        $cacheDir = DATA_DIR . '/i18n_cache';
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '/*.json') ?: [] as $cf) {
                if (@unlink($cf)) {
                    $cleared++;
                }
            }
        }
        api_json(true, [
            'message' => 'Çoklu dil önbelleği temizlendi (' . $cleared . ' önbellek kaydı yenilenecek).',
            'cleared_files' => $cleared,
        ]);
    }

    case 'blog_delete': {
        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') {
            api_json(false, ['error' => 'Silinecek yazı belirtilmedi.'], 400);
        }
        delete_post($slug);
        api_json(true, ['message' => 'Blog yazısı silindi.']);
    }

    case 'blog_quick_toggle': {
        $slug = trim((string)($data['slug'] ?? ''));
        $field = trim((string)($data['field'] ?? ''));
        $post = get_post($slug);
        if ($post === null) {
            api_json(false, ['error' => 'Yazı bulunamadı.'], 404);
        }
        if ($field === 'status') {
            $post['status'] = ($post['status'] ?? 'published') === 'published' ? 'draft' : 'published';
        } elseif ($field === 'featured') {
            $post['featured'] = empty($post['featured']);
        } elseif ($field === 'curated') {
            $post['curated'] = empty($post['curated']);
        } else {
            api_json(false, ['error' => 'Geçersiz alan.'], 400);
        }
        $saved = save_post($post, $slug);
        api_json(true, ['message' => 'Yazı durumu güncellendi.', 'post' => $saved]);
    }

    case 'blog_settings_save': {
        $bs = is_array($data['blog_settings'] ?? null) ? $data['blog_settings'] : [];
        $prevBs = is_array($store['blog_settings'] ?? null) ? $store['blog_settings'] : [];
        $cats = [];
        if (isset($bs['categories'])) {
            $rawC = is_array($bs['categories']) ? $bs['categories'] : explode(',', (string)$bs['categories']);
            foreach ($rawC as $c) {
                $cc = mb_strtoupper(trim((string)$c), 'UTF-8');
                if ($cc !== '') {
                    $cats[] = $cc;
                }
            }
        }
        if (!in_array('TÜMÜ', $cats, true)) {
            array_unshift($cats, 'TÜMÜ');
        }
        $mergedBs = array_merge($prevBs, [
            'kicker_left' => trim((string)($bs['kicker_left'] ?? $prevBs['kicker_left'] ?? 'ZEYNART JOURNAL')),
            'kicker_right' => trim((string)($bs['kicker_right'] ?? $prevBs['kicker_right'] ?? 'TASARIM. TEKNOLOJİ. BİR ADIM ÖTESİ.')),
            'heading_line1' => trim((string)($bs['heading_line1'] ?? $prevBs['heading_line1'] ?? 'İyi fikirler,')),
            'heading_span' => trim((string)($bs['heading_span'] ?? $prevBs['heading_span'] ?? 'paylaştıkça büyür.')),
            'lead' => trim((string)($bs['lead'] ?? $prevBs['lead'] ?? '')),
            'badge_right' => trim((string)($bs['badge_right'] ?? $prevBs['badge_right'] ?? 'KEŞFET / ÖĞREN / İLHAM AL')),
            'per_page' => max(6, min(96, (int)($bs['per_page'] ?? $prevBs['per_page'] ?? 24))),
            'categories' => array_values(array_unique($cats)),
            'show_share_bar' => array_key_exists('show_share_bar', $bs) ? (bool)$bs['show_share_bar'] : (bool)($prevBs['show_share_bar'] ?? true),
            'audio_player_enabled' => array_key_exists('audio_player_enabled', $bs) ? (bool)$bs['audio_player_enabled'] : (bool)($prevBs['audio_player_enabled'] ?? true),
        ]);
        if (isset($bs['heading'])) {
            $hClean = trim((string)$bs['heading']);
            if ($hClean !== '') {
                $mergedBs['heading'] = $hClean;
                $store['page_titles'] = is_array($store['page_titles'] ?? null) ? $store['page_titles'] : [];
                $store['page_titles']['blog'] = $hClean;
            }
        }
        $store['blog_settings'] = $mergedBs;
        save_store_or_fail($store);
        api_json(true, ['message' => 'Blog vitrin ayarları güncellendi.', 'blog_settings' => $store['blog_settings']]);
    }

    case 'media_list': {
        $folder = trim((string)($data['folder'] ?? $_GET['folder'] ?? 'all'));
        $search = trim((string)($data['search'] ?? $_GET['search'] ?? ''));
        $page = max(1, (int)($data['page'] ?? $_GET['page'] ?? 1));
        $perPage = max(12, min(120, (int)($data['per_page'] ?? $_GET['per_page'] ?? 48)));
        $typeFilter = trim((string)($data['type_filter'] ?? $_GET['type_filter'] ?? 'all'));
        $sort = trim((string)($data['sort'] ?? $_GET['sort'] ?? 'newest'));
        $res = scan_media_files($folder, $search, $page, $perPage, $typeFilter, $sort);
        api_json(true, $res);
    }

    case 'media_upload': {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $rawOpt = (string)($_POST['optimize_webp'] ?? '1');
        $optimizeWebp = ($rawOpt !== '0' && strtolower($rawOpt) !== 'false');
        $webpQuality = isset($_POST['webp_quality']) ? (int)$_POST['webp_quality'] : 86;
        $maxWidth = isset($_POST['max_width']) ? (int)$_POST['max_width'] : 1920;
        $subfolder = trim((string)($_POST['subfolder'] ?? ''));
        $defaultAlt = trim((string)($_POST['alt'] ?? ''));
        $rawAutoAlt = (string)($_POST['auto_alt'] ?? '1');
        $autoAlt = ($rawAutoAlt !== '0' && strtolower($rawAutoAlt) !== 'false');

        $baseStampCfg = zeynart_get_media_stamp_config($store);
        $stampEnabled = isset($_POST['stamp_enabled'])
            ? (!in_array(strtolower((string)$_POST['stamp_enabled']), ['0', 'false', ''], true))
            : !empty($baseStampCfg['enabled']);
        $stampCfg = array_merge($baseStampCfg, [
            'enabled' => $stampEnabled,
            'text' => isset($_POST['stamp_text']) && trim((string)$_POST['stamp_text']) !== ''
                ? trim((string)$_POST['stamp_text'])
                : $baseStampCfg['text'],
            'position' => isset($_POST['stamp_position']) && $_POST['stamp_position'] !== ''
                ? (string)$_POST['stamp_position']
                : $baseStampCfg['position'],
            'style' => isset($_POST['stamp_style']) && $_POST['stamp_style'] !== ''
                ? (string)$_POST['stamp_style']
                : $baseStampCfg['style'],
            'opacity' => isset($_POST['stamp_opacity']) ? (int)$_POST['stamp_opacity'] : $baseStampCfg['opacity'],
            'scale' => isset($_POST['stamp_scale']) ? (int)$_POST['stamp_scale'] : $baseStampCfg['scale'],
        ]);

        $applyStampIfRequested = static function (array &$upRes, string $imgTitle = '') use ($stampCfg, $store): void {
            if (empty($stampCfg['enabled'])) {
                return;
            }
            $extL = strtolower((string)($upRes['ext'] ?? ''));
            if (!in_array($extL, ['webp', 'jpg', 'jpeg', 'png'], true)) {
                return;
            }
            $absFile = ROOT_DIR . (string)($upRes['url'] ?? '');
            $localCfg = $stampCfg;
            $siteBrand = trim((string)($store['site']['name'] ?? 'ZEYNART')) ?: 'ZEYNART';
            $siteDom = parse_url((string)($store['seo']['canonical_domain'] ?? SITE_URL), PHP_URL_HOST) ?: 'zeynart.com';
            $rMap = [
                '{baslik}' => $imgTitle !== '' ? $imgTitle : $siteBrand,
                '{marka}' => $siteBrand,
                '{domain}' => $siteDom,
            ];
            $localCfg['text'] = strtr((string)$localCfg['text'], $rMap);
            $localCfg['subtext'] = strtr((string)($localCfg['subtext'] ?? ''), $rMap);
            if (is_file($absFile) && zeynart_apply_gd_text_stamp($absFile, $localCfg, $store)) {
                clearstatcache(true, $absFile);
                $upRes['size'] = (int)(@filesize($absFile) ?: $upRes['size']);
                $upRes['stamped'] = true;
            }
        };

        $uploaded = [];
        $errors = [];
        $newAlts = [];

        if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
            $count = count($_FILES['files']['name']);
            for ($i = 0; $i < $count; $i++) {
                $single = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['files']['tmp_name'][$i] ?? '',
                    'error' => $_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $_FILES['files']['size'][$i] ?? 0,
                ];
                $res = handle_media_upload($single, $optimizeWebp, $subfolder, $webpQuality, $maxWidth);
                if (!empty($res['ok'])) {
                    $resolvedAlt = $defaultAlt !== ''
                        ? $defaultAlt
                        : ($autoAlt ? humanize_media_filename_to_alt((string)($single['name'] ?? $res['name'])) : '');
                    $applyStampIfRequested($res, $resolvedAlt);
                    if ($resolvedAlt !== '') {
                        $newAlts[$res['url']] = ['alt' => $resolvedAlt, 'updated_at' => date('c')];
                    }
                    $res['alt'] = $resolvedAlt;
                    $uploaded[] = $res;
                } else {
                    $errors[] = (string)($res['error'] ?? 'Yükleme hatası.');
                }
            }
        } elseif (isset($_FILES['file'])) {
            $res = handle_media_upload($_FILES['file'], $optimizeWebp, $subfolder, $webpQuality, $maxWidth);
            if (!empty($res['ok'])) {
                $resolvedAlt = $defaultAlt !== ''
                    ? $defaultAlt
                    : ($autoAlt ? humanize_media_filename_to_alt((string)($_FILES['file']['name'] ?? $res['name'])) : '');
                $applyStampIfRequested($res, $resolvedAlt);
                if ($resolvedAlt !== '') {
                    $newAlts[$res['url']] = ['alt' => $resolvedAlt, 'updated_at' => date('c')];
                }
                $res['alt'] = $resolvedAlt;
                $uploaded[] = $res;
            } else {
                $errors[] = (string)($res['error'] ?? 'Yükleme hatası.');
            }
        } else {
            api_json(false, ['error' => 'Yüklenecek görsel dosyası seçilmedi.'], 400);
        }

        if ($newAlts !== [] && $uploaded !== []) {
            $freshStore = load_store(true);
            if (!isset($freshStore['media_meta']) || !is_array($freshStore['media_meta'])) {
                $freshStore['media_meta'] = [];
            }
            foreach ($newAlts as $uKey => $metaVal) {
                $freshStore['media_meta'][$uKey] = $metaVal;
            }
            save_store($freshStore);
        }

        if ($uploaded === []) {
            api_json(false, ['error' => implode(' ', $errors) ?: 'Dosya yüklenemedi.'], 422);
        }

        api_json(true, [
            'message' => count($uploaded) . ' görsel /uploads klasörüne başarıyla yüklendi.',
            'uploaded' => $uploaded,
            'errors' => $errors,
        ]);
    }

    case 'media_bulk_auto_alt': {
        if (!isset($store['media_meta']) || !is_array($store['media_meta'])) {
            $store['media_meta'] = [];
        }
        $updated = 0;
        $idxEntries = zeynart_get_cached_media_entries(false)['entries'] ?? [];
        foreach ($idxEntries as $e) {
            if (empty($e['up'])) {
                continue;
            }
            $relUrl = (string)$e['u'];
            $existingAlt = trim((string)($store['media_meta'][$relUrl]['alt'] ?? ''));
            if ($existingAlt === '') {
                $store['media_meta'][$relUrl] = [
                    'alt' => humanize_media_filename_to_alt((string)$e['n']),
                    'updated_at' => date('c'),
                ];
                $updated++;
            }
        }
        if ($updated > 0) {
            save_store_or_fail($store);
        }
        api_json(true, [
            'message' => $updated > 0
                ? ($updated . ' görsel için otomatik SEO alt etiketi üretildi ve kaydedildi.')
                : 'Tüm yüklenen görsellerin SEO alt etiketleri zaten dolu!',
            'updated_count' => $updated,
        ]);
    }

    case 'media_meta_save': {
        $mediaUrl = trim((string)($data['url'] ?? ''));
        $alt = trim((string)($data['alt'] ?? ''));
        if ($mediaUrl === '') {
            api_json(false, ['error' => 'Görsel adresi eksik.'], 400);
        }
        if (!isset($store['media_meta']) || !is_array($store['media_meta'])) {
            $store['media_meta'] = [];
        }
        $store['media_meta'][$mediaUrl] = ['alt' => $alt, 'updated_at' => date('c')];
        save_store_or_fail($store);
        api_json(true, ['message' => 'Görsel SEO (alt etiketi) kaydedildi.']);
    }

    case 'media_delete': {
        $mediaUrl = trim((string)($data['url'] ?? ''));
        if (!str_starts_with($mediaUrl, '/uploads/')) {
            api_json(false, ['error' => 'Güvenlik gereği yalnızca /uploads/ klasöründeki medya dosyaları silinebilir; çekirdek tema görselleri korunmaktadır.'], 403);
        }
        $candidate = realpath(ROOT_DIR . $mediaUrl);
        $uploadsReal = realpath(UPLOADS_DIR);
        if ($candidate === false || $uploadsReal === false || !str_starts_with(str_replace('\\', '/', $candidate), str_replace('\\', '/', $uploadsReal) . '/')) {
            api_json(false, ['error' => 'Dosya bulunamadı veya geçersiz yol.'], 404);
        }
        if (!@unlink($candidate)) {
            api_json(false, ['error' => 'Dosya silinemedi.'], 500);
        }
        zeynart_invalidate_media_cache();
        unset($store['media_meta'][$mediaUrl]);
        save_store_or_fail($store);
        api_json(true, ['message' => 'Görsel kalıcı olarak silindi.']);
    }

    case 'media_bulk_delete': {
        $urls = $data['urls'] ?? [];
        if (!is_array($urls) || $urls === []) {
            api_json(false, ['error' => 'Silinecek görsel seçilmedi.'], 400);
        }
        $uploadsReal = realpath(UPLOADS_DIR);
        if ($uploadsReal === false) {
            api_json(false, ['error' => 'Uploads dizini bulunamadı.'], 500);
        }
        $uploadsPrefix = str_replace('\\', '/', $uploadsReal) . '/';

        $deletedUrls = [];
        $skippedProtected = 0;
        $skippedMissing = 0;
        $freedBytes = 0;

        foreach (array_slice($urls, 0, 500) as $rawU) {
            $u = trim((string)$rawU);
            if ($u === '') {
                continue;
            }
            if (!str_starts_with($u, '/uploads/')) {
                $skippedProtected++;
                continue;
            }
            $candidate = realpath(ROOT_DIR . $u);
            if ($candidate === false || !str_starts_with(str_replace('\\', '/', $candidate), $uploadsPrefix)) {
                $skippedMissing++;
                continue;
            }
            $sz = @filesize($candidate) ?: 0;
            if (@unlink($candidate)) {
                $deletedUrls[] = $u;
                $freedBytes += $sz;
                unset($store['media_meta'][$u]);
            }
        }

        if ($deletedUrls !== []) {
            zeynart_invalidate_media_cache();
            save_store_or_fail($store);
        }

        if ($deletedUrls === [] && $skippedProtected > 0) {
            api_json(false, ['error' => 'Seçilen görseller çekirdek tema görseli (/images/) olduğu için güvenlik gereği silinmedi. Yalnızca /uploads/ görselleri silinebilir.'], 403);
        }

        $msg = count($deletedUrls) . ' görsel kalıcı olarak silindi.';
        if ($skippedProtected > 0) {
            $msg .= ' (' . $skippedProtected . ' tema görseli korundu.)';
        }

        api_json(true, [
            'message' => $msg,
            'deleted_count' => count($deletedUrls),
            'deleted_urls' => $deletedUrls,
            'skipped_protected' => $skippedProtected,
            'skipped_missing' => $skippedMissing,
            'freed_bytes' => $freedBytes,
        ]);
    }

    case 'menus_save': {
        $menuSrc = (isset($data['menus']) && is_array($data['menus'])) ? $data['menus'] : $data;
        if (isset($menuSrc['header']) && is_array($menuSrc['header'])) {
            $h = $menuSrc['header'];
            $items = [];
            foreach (($h['items'] ?? []) as $idx => $it) {
                $lbl = trim((string)($it['label'] ?? ''));
                $u = trim((string)($it['url'] ?? '/'));
                if ($lbl === '') {
                    continue;
                }
                $items[] = [
                    'id' => trim((string)($it['id'] ?? ('nav-' . ($idx + 1)))),
                    'label' => $lbl,
                    'url' => $u !== '' ? $u : '/',
                    'target' => ($it['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                    'enabled' => !isset($it['enabled']) || !empty($it['enabled']),
                ];
            }
            $rawAlign = (string)($h['nav_align'] ?? ($store['menus']['header']['nav_align'] ?? 'left'));
            $navAlign = in_array($rawAlign, ['left', 'center', 'right'], true) ? $rawAlign : 'left';

            $rawElOrd = is_array($h['element_order'] ?? null)
                ? $h['element_order']
                : ($store['menus']['header']['element_order'] ?? ['brand', 'nav', 'actions']);
            $elOrder = [];
            foreach ($rawElOrd as $ek) {
                $eks = (string)$ek;
                if (in_array($eks, ['brand', 'nav', 'actions'], true) && !in_array($eks, $elOrder, true)) {
                    $elOrder[] = $eks;
                }
            }
            foreach (['brand', 'nav', 'actions'] as $defEk) {
                if (!in_array($defEk, $elOrder, true)) {
                    $elOrder[] = $defEk;
                }
            }

            $prevH = $store['menus']['header'] ?? [];
            $store['menus']['header'] = [
                'logo_aria' => trim((string)($h['logo_aria'] ?? ($prevH['logo_aria'] ?? 'Zeynart Web Bilişim ana sayfa'))),
                'language_badge' => trim((string)($h['language_badge'] ?? ($prevH['language_badge'] ?? 'TR'))),
                'show_language' => isset($h['show_language']) ? !empty($h['show_language']) : (bool)($prevH['show_language'] ?? true),
                'cta_label' => trim((string)($h['cta_label'] ?? ($prevH['cta_label'] ?? 'Birlikte çalışalım'))),
                'cta_url' => trim((string)($h['cta_url'] ?? ($prevH['cta_url'] ?? '/iletisim/'))),
                'show_cta' => isset($h['show_cta']) ? !empty($h['show_cta']) : (bool)($prevH['show_cta'] ?? true),
                'mobile_eyebrow' => trim((string)($h['mobile_eyebrow'] ?? ($prevH['mobile_eyebrow'] ?? 'FİKİRLER. TEKNOLOJİ. DAHA FAZLASI.'))),
                'nav_align' => $navAlign,
                'element_order' => $elOrder,
                'items' => $items,
            ];
        }

        if (isset($menuSrc['footer']) && is_array($menuSrc['footer'])) {
            $f = $menuSrc['footer'];
            $prevF = $store['menus']['footer'] ?? [];
            $cols = [];
            foreach (($f['columns'] ?? []) as $cIdx => $col) {
                $cTitle = trim((string)($col['title'] ?? ''));
                if ($cTitle === '') {
                    continue;
                }
                $links = [];
                $rawColLinks = $col['links'] ?? $col['items'] ?? [];
                foreach ($rawColLinks as $lnk) {
                    $ll = trim((string)($lnk['label'] ?? ''));
                    $lu = trim((string)($lnk['url'] ?? '/'));
                    if ($ll !== '') {
                        $links[] = [
                            'label' => $ll,
                            'url' => $lu !== '' ? $lu : '/',
                            'target' => ($lnk['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                        ];
                    }
                }
                $cols[] = [
                    'id' => trim((string)($col['id'] ?? ('fcol-' . ($cIdx + 1)))),
                    'title' => $cTitle,
                    'links' => $links,
                ];
            }

            $legalLinks = [];
            $rawLegal = $f['legal_links'] ?? ($prevF['legal_links'] ?? []);
            foreach ($rawLegal as $llk) {
                $lbl = trim((string)($llk['label'] ?? ''));
                $u = trim((string)($llk['url'] ?? '/'));
                if ($lbl !== '') {
                    $legalLinks[] = [
                        'label' => $lbl,
                        'url' => $u !== '' ? $u : '/',
                        'target' => ($llk['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                    ];
                }
            }

            $rawBlkOrd = is_array($f['block_order'] ?? null)
                ? $f['block_order']
                : ($prevF['block_order'] ?? ['intro', 'menus', 'contact']);
            $blkOrder = [];
            foreach ($rawBlkOrd as $bk) {
                $bks = (string)$bk;
                if (in_array($bks, ['intro', 'menus', 'contact'], true) && !in_array($bks, $blkOrder, true)) {
                    $blkOrder[] = $bks;
                }
            }
            foreach (['intro', 'menus', 'contact'] as $defBk) {
                if (!in_array($defBk, $blkOrder, true)) {
                    $blkOrder[] = $defBk;
                }
            }

            $store['menus']['footer'] = [
                'intro_text' => trim((string)($f['intro_text'] ?? ($prevF['intro_text'] ?? ''))),
                'availability_text' => trim((string)($f['availability_text'] ?? ($prevF['availability_text'] ?? 'Yeni fikirlere açığız'))),
                'show_availability' => isset($f['show_availability']) ? !empty($f['show_availability']) : (bool)($prevF['show_availability'] ?? true),
                'block_order' => $blkOrder,
                'columns' => $cols,
                'contact_heading' => trim((string)($f['contact_heading'] ?? ($prevF['contact_heading'] ?? 'Bir merhaba ile başlar.'))),
                'contact_cta_label' => trim((string)($f['contact_cta_label'] ?? ($prevF['contact_cta_label'] ?? 'Fikrinizi konuşalım'))),
                'contact_cta_url' => trim((string)($f['contact_cta_url'] ?? ($prevF['contact_cta_url'] ?? '/iletisim/'))),
                'copyright' => trim((string)($f['copyright'] ?? ($prevF['copyright'] ?? '© 2026 ZEYNART'))),
                'crafted_note' => 'Powered & Designed By Zeynart®',
                'credit_label' => 'Powered & Designed By Zeynart®',
                'credit_url' => 'https://zeynart.com/',
                'wordmark' => trim((string)($f['wordmark'] ?? ($prevF['wordmark'] ?? 'ZEYNART'))),
                'legal_links' => $legalLinks,
            ];
        }

        if (isset($menuSrc['typography']) && is_array($menuSrc['typography'])) {
            $prevT = is_array($store['menus']['typography'] ?? null) ? $store['menus']['typography'] : [];
            $t = array_merge($prevT, $menuSrc['typography']);
            $wRaw = (int)($t['header_link_weight'] ?? 500);
            $rawTrack = trim((string)($t['heading_tracking'] ?? 'tight'));
            $rawBodyF = trim((string)($t['body_font'] ?? 'Manrope'));
            $rawHeadF = trim((string)($t['heading_font'] ?? 'same'));
            $store['menus']['typography'] = [
                'body_font' => $rawBodyF !== '' ? ($rawBodyF === 'custom' ? 'custom' : zeynart_extract_font_family($rawBodyF)) : 'Manrope',
                'custom_body_font' => zeynart_extract_font_family((string)($t['custom_body_font'] ?? '')),
                'heading_font' => $rawHeadF !== '' ? ($rawHeadF === 'same' || $rawHeadF === 'custom' ? $rawHeadF : zeynart_extract_font_family($rawHeadF)) : 'same',
                'custom_heading_font' => zeynart_extract_font_family((string)($t['custom_heading_font'] ?? '')),
                'base_body_size' => round(max(12.0, min(20.0, (float)($t['base_body_size'] ?? 14))), 1),
                'heading_scale' => round(max(75.0, min(140.0, (float)($t['heading_scale'] ?? 100))), 0),
                'heading_tracking' => in_array($rawTrack, ['tight', 'balanced', 'normal'], true) ? $rawTrack : 'tight',
                'header_link_size' => round(max(11.0, min(28.0, (float)($t['header_link_size'] ?? 16))), 1),
                'header_link_weight' => in_array($wRaw, [400, 500, 600, 700], true) ? $wRaw : 500,
                'header_gap' => (int)max(12, min(64, (int)($t['header_gap'] ?? 32))),
                'header_cta_size' => round(max(10.0, min(22.0, (float)($t['header_cta_size'] ?? 13))), 1),
                'mobile_link_size' => round(max(18.0, min(44.0, (float)($t['mobile_link_size'] ?? 28))), 1),
                'footer_title_size' => round(max(10.0, min(26.0, (float)($t['footer_title_size'] ?? 14.5))), 1),
                'footer_link_size' => round(max(10.0, min(24.0, (float)($t['footer_link_size'] ?? 14.5))), 1),
                'footer_text_size' => round(max(10.0, min(22.0, (float)($t['footer_text_size'] ?? 14))), 1),
                'footer_legal_size' => round(max(9.0, min(20.0, (float)($t['footer_legal_size'] ?? 13))), 1),
            ];
        }

        $ctaSrc = $data['cta_global'] ?? $menuSrc['cta_global'] ?? null;
        if (is_array($ctaSrc)) {
            $c = $ctaSrc;
            $store['cta_global'] = [
                'eyebrow' => trim((string)($c['eyebrow'] ?? 'BİR FİKRİNİZ Mİ VAR?')),
                'heading_line1' => trim((string)($c['heading_line1'] ?? 'Birlikte')),
                'heading_line2' => trim((string)($c['heading_line2'] ?? 'hayata')),
                'heading_accent' => trim((string)($c['heading_accent'] ?? 'geçirelim.')),
                'button_label' => trim((string)($c['button_label'] ?? 'Projenizi anlatın')),
                'button_url' => trim((string)($c['button_url'] ?? '/iletisim/')),
                'side_note' => trim((string)($c['side_note'] ?? 'BİR MERHABA, YENİ BİR BAŞLANGIÇ.')),
            ];
        }

        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Menü yerleşimi, Google Fonts yazı tipleri, boyutlar ve çağrı alanı güncellendi.',
            'menus' => $store['menus'],
            'cta_global' => $store['cta_global'],
        ]);
    }

    case 'builder_preview_html': {
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $rendered = render_builder_sections($sections, $store);
        api_json(true, ['html' => $rendered]);
    }

    case 'builder_save_core_blocks': {
        $pageKey = trim((string)($data['page_key'] ?? 'anasayfa'));
        $allowedKeys = ['anasayfa', 'hakkimizda', 'hizmetler', 'projeler', 'iletisim', 'blog', 'kvkk', 'gizlilik', 'cerez-politikasi', 'cerezler'];
        if (!in_array($pageKey, $allowedKeys, true)) {
            api_json(false, ['error' => 'Geçersiz çekirdek sayfa anahtarı.'], 400);
        }
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        if (!isset($store['core_page_blocks']) || !is_array($store['core_page_blocks'])) {
            $store['core_page_blocks'] = [];
        }
        $store['core_page_blocks'][$pageKey] = array_values($sections);
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Görsel sayfa bölümleri canlıya alındı.',
            'core_page_blocks' => $store['core_page_blocks'],
        ]);
    }

    case 'builder_save_custom_page': {
        $pg = is_array($data['page'] ?? null) ? $data['page'] : [];
        $title = trim((string)($pg['title'] ?? ''));
        if ($title === '') {
            api_json(false, ['error' => 'Lütfen sayfa başlığı girin.'], 422);
        }
        $slug = slugify((string)($pg['slug'] ?? $title));
        $reserved = array_values(array_unique(array_merge(
            zeynart_system_reserved_slugs(),
            array_values(zeynart_resolved_page_slugs($store))
        )));
        if (in_array($slug, $reserved, true)) {
            $slug .= '-sayfa';
        }

        $id = trim((string)($pg['id'] ?? ''));
        if ($id === '') {
            $id = 'page-' . substr(bin2hex(random_bytes(4)), 0, 8);
        }

        $sections = is_array($pg['sections'] ?? null) ? array_values($pg['sections']) : [];
        $faqs = [];
        foreach (($pg['faqs'] ?? []) as $fq) {
            $q = trim((string)($fq['q'] ?? ''));
            $a = trim((string)($fq['a'] ?? ''));
            if ($q !== '' && $a !== '') {
                $faqs[] = ['q' => $q, 'a' => $a];
            }
        }

        $seoIn = is_array($pg['seo'] ?? null) ? $pg['seo'] : [];
        $contentHtml = sanitize_rich_html((string)($pg['content_html'] ?? ''));
        $record = [
            'id' => $id,
            'slug' => $slug,
            'title' => $title,
            'eyebrow' => trim((string)($pg['eyebrow'] ?? 'ZEYNART STÜDYO')),
            'excerpt' => trim((string)($pg['excerpt'] ?? '')),
            'banner_image' => trim((string)($pg['banner_image'] ?? '')),
            'status' => ($pg['status'] ?? 'published') === 'draft' ? 'draft' : 'published',
            'show_page_header' => ($pg['show_page_header'] ?? true) !== false,
            'show_cta' => ($pg['show_cta'] ?? true) !== false,
            'content_html' => $contentHtml,
            'sections' => $sections,
            'faqs' => $faqs,
            'seo' => [
                'meta_title' => trim((string)($seoIn['meta_title'] ?? ($title . ' | ZEYNART'))),
                'meta_desc' => trim((string)($seoIn['meta_desc'] ?? ($pg['excerpt'] ?? ''))),
                'og_title' => trim((string)($seoIn['og_title'] ?? '')),
                'og_desc' => trim((string)($seoIn['og_desc'] ?? '')),
                'focus_keyword' => trim((string)($seoIn['focus_keyword'] ?? '')),
                'robots' => trim((string)($seoIn['robots'] ?? 'index, follow')),
                'og_image' => trim((string)($seoIn['og_image'] ?? '')) !== '' ? trim((string)$seoIn['og_image']) : trim((string)($pg['banner_image'] ?? '')),
                'ai_summary' => trim((string)($seoIn['ai_summary'] ?? ($pg['excerpt'] ?? ''))),
            ],
            'updated_at' => date('c'),
        ];

        $list = is_array($store['custom_pages'] ?? null) ? $store['custom_pages'] : [];
        $found = false;
        foreach ($list as $idx => $item) {
            if (($item['id'] ?? '') === $id || ($item['slug'] ?? '') === $slug) {
                $list[$idx] = $record;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $list[] = $record;
        }
        $store['custom_pages'] = array_values($list);

        if (!empty($data['add_to_header'])) {
            $targetUrl = '/' . $slug . '/';
            $existsInHeader = false;
            foreach (($store['menus']['header']['items'] ?? []) as $hItem) {
                if (($hItem['url'] ?? '') === $targetUrl) {
                    $existsInHeader = true;
                    break;
                }
            }
            if (!$existsInHeader) {
                $store['menus']['header']['items'][] = [
                    'id' => 'nav-' . time(),
                    'label' => $title,
                    'url' => $targetUrl,
                    'target' => '_self',
                    'enabled' => true,
                ];
            }
        }

        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Özel sayfa başarıyla kaydedildi ve yayına hazır.',
            'page' => $record,
            'custom_pages' => $store['custom_pages'],
            'menus' => $store['menus'],
            'view_url' => url('/' . $slug . '/'),
        ]);
    }

    case 'builder_delete_custom_page': {
        $id = trim((string)($data['id'] ?? ''));
        $list = is_array($store['custom_pages'] ?? null) ? $store['custom_pages'] : [];
        $store['custom_pages'] = array_values(array_filter(
            $list,
            static fn(array $p): bool => ($p['id'] ?? '') !== $id && ($p['slug'] ?? '') !== $id
        ));
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Özel sayfa silindi.',
            'custom_pages' => $store['custom_pages'],
        ]);
    }

    case 'home_layout_save': {
        $prevHome = is_array($store['home'] ?? null) ? $store['home'] : [];
        if (isset($data['section_order']) && is_array($data['section_order'])) {
            $prevHome['section_order'] = $data['section_order'];
        }
        if (isset($data['section_visibility']) && is_array($data['section_visibility'])) {
            $prevHome['section_visibility'] = $data['section_visibility'];
        }
        $normHome = normalize_home_sections($prevHome, $store);
        $store['home'] = $normHome;

        // Bi-directionally sync Google Reviews & Site Audit home visibility flags
        $grVis = !empty($normHome['section_visibility']['google_reviews']);
        $store['google_reviews'] = is_array($store['google_reviews'] ?? null) ? $store['google_reviews'] : [];
        $store['google_reviews']['show_on_home'] = $grVis;
        if ($grVis && array_key_exists('enabled', $store['google_reviews']) && !$store['google_reviews']['enabled']) {
            $store['google_reviews']['enabled'] = true;
        }

        $saVis = !empty($normHome['section_visibility']['site_audit']);
        $store['site_audit'] = is_array($store['site_audit'] ?? null) ? $store['site_audit'] : [];
        $store['site_audit']['show_on_home'] = $saVis;
        if ($saVis && array_key_exists('enabled', $store['site_audit']) && !$store['site_audit']['enabled']) {
            $store['site_audit']['enabled'] = true;
        }

        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Ana sayfa bölüm sıralaması ve görünürlük ayarları canlı siteye yansıtıldı.',
            'home' => $store['home'],
            'store' => $store,
        ]);
    }

    case 'home_save': {
        if (isset($data['home']) && is_array($data['home'])) {
            $normHome = normalize_home_sections($data['home'], $store);
            $store['home'] = $normHome;
            $store['google_reviews'] = is_array($store['google_reviews'] ?? null) ? $store['google_reviews'] : [];
            $store['google_reviews']['show_on_home'] = !empty($normHome['section_visibility']['google_reviews']);
            $store['site_audit'] = is_array($store['site_audit'] ?? null) ? $store['site_audit'] : [];
            $store['site_audit']['show_on_home'] = !empty($normHome['section_visibility']['site_audit']);
            save_store_or_fail($store);
            api_json(true, ['message' => 'Ana sayfa vitrin, slaytlar ve bölüm sıralaması kaydedildi.', 'home' => $store['home']]);
        }
        api_json(false, ['error' => 'Geçersiz ana sayfa verisi.'], 400);
    }

    case 'services_save': {
        if (isset($data['services_page']) && is_array($data['services_page'])) {
            $store['services_page'] = $data['services_page'];
        }
        if (isset($data['services']) && is_array($data['services'])) {
            $cleanServices = [];
            foreach (array_values($data['services']) as $idx => $srv) {
                if (!is_array($srv) || trim((string)($srv['title'] ?? '')) === '') {
                    continue;
                }
                $srv['slug'] = slugify((string)($srv['slug'] ?? $srv['title']));
                $srv['num'] = trim((string)($srv['num'] ?? str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT)));
                $cleanServices[] = $srv;
            }
            $store['services'] = $cleanServices;
        }
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Hizmetler ve paket yapılandırması kaydedildi.',
            'services' => $store['services'],
            'services_page' => $store['services_page'],
        ]);
    }

    case 'projects_save': {
        if (isset($data['projects_page']) && is_array($data['projects_page'])) {
            $store['projects_page'] = $data['projects_page'];
        }
        if (isset($data['projects']) && is_array($data['projects'])) {
            $cleanProjects = [];
            foreach (array_values($data['projects']) as $prj) {
                if (!is_array($prj) || trim((string)($prj['name'] ?? '')) === '') {
                    continue;
                }
                $prj['slug'] = slugify((string)($prj['slug'] ?? $prj['name']));
                $cleanProjects[] = $prj;
            }
            $store['projects'] = $cleanProjects;
        }
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Projeler ve vaka çalışmaları kaydedildi.',
            'projects' => $store['projects'],
            'projects_page' => $store['projects_page'],
        ]);
    }

    case 'about_save': {
        if (isset($data['about']) && is_array($data['about'])) {
            $store['about'] = $data['about'];
            save_store_or_fail($store);
            api_json(true, [
                'message' => 'Kurumsal sayfa, müşteri logoları ve yorumlar kaydedildi.',
                'about' => $store['about'],
            ]);
        }
        api_json(false, ['error' => 'Geçersiz hakkımızda verisi.'], 400);
    }

    case 'google_reviews_save': {
        if (!isset($data['google_reviews']) || !is_array($data['google_reviews'])) {
            api_json(false, ['error' => 'Geçersiz Google yorum yapılandırması.'], 400);
        }
        $in = $data['google_reviews'];
        $cur = get_google_reviews_state($store);
        $merged = array_merge($cur, $in);

        $merged['enabled'] = !empty($merged['enabled']);
        $merged['layout'] = in_array((string)($merged['layout'] ?? 'slider'), ['slider', 'grid', 'masonry', 'badge'], true)
            ? (string)$merged['layout']
            : 'slider';
        $merged['rating'] = round(max(1.0, min(5.0, (float)($merged['rating'] ?? 5.0))), 1);
        $merged['min_rating'] = max(1, min(5, (int)($merged['min_rating'] ?? 4)));
        $merged['sync_interval_hours'] = max(1, min(720, (int)($merged['sync_interval_hours'] ?? 12)));
        $merged['hide_empty_text'] = !empty($merged['hide_empty_text']);
        $merged['show_verified_badge'] = !empty($merged['show_verified_badge']);
        $merged['show_write_btn'] = !empty($merged['show_write_btn']);
        $merged['show_on_home'] = !empty($merged['show_on_home']);
        $merged['show_on_about'] = !empty($merged['show_on_about']);
        $merged['auto_sync'] = !empty($merged['auto_sync']);

        $cleanItems = [];
        foreach (($merged['items'] ?? []) as $idx => $rv) {
            if (!is_array($rv)) {
                continue;
            }
            $author = trim((string)($rv['author'] ?? ''));
            $text = trim((string)($rv['text'] ?? ''));
            if ($author === '' && $text === '') {
                continue;
            }
            $cleanItems[] = [
                'id' => trim((string)($rv['id'] ?? ('gr-item-' . ($idx + 1)))),
                'author' => $author !== '' ? $author : 'Google Kullanıcısı',
                'avatar' => trim((string)($rv['avatar'] ?? '')),
                'author_url' => trim((string)($rv['author_url'] ?? '')),
                'rating' => max(1, min(5, (int)($rv['rating'] ?? 5))),
                'date' => trim((string)($rv['date'] ?? 'Google Yorumu')),
                'timestamp' => (int)($rv['timestamp'] ?? time()),
                'text' => $text,
                'owner_reply' => trim((string)($rv['owner_reply'] ?? '')),
                'verified' => ($rv['verified'] ?? true) !== false,
                'visible' => ($rv['visible'] ?? true) !== false,
                'pinned' => !empty($rv['pinned']),
            ];
        }
        $merged['items'] = $cleanItems;
        $merged['total_reviews'] = max(count($cleanItems), (int)($merged['total_reviews'] ?? 34));

        if (!empty($merged['place_id'])) {
            $pid = trim((string)$merged['place_id']);
            $merged['maps_url'] = 'https://www.google.com/maps/place/?q=place_id:' . rawurlencode($pid);
            $merged['write_review_url'] = 'https://search.google.com/local/writereview?placeid=' . rawurlencode($pid);
            $merged['all_reviews_url'] = 'https://search.google.com/local/reviews?placeid=' . rawurlencode($pid) . '&hl=tr';
        }

        $store['google_reviews'] = $merged;
        if (isset($store['home']) && is_array($store['home'])) {
            $store['home']['section_visibility'] = is_array($store['home']['section_visibility'] ?? null) ? $store['home']['section_visibility'] : [];
            $store['home']['section_visibility']['google_reviews'] = !empty($merged['show_on_home']) && !empty($merged['enabled']);
        }

        $mirror = [];
        foreach ($cleanItems as $it) {
            if (!$it['visible']) {
                continue;
            }
            $mirror[] = [
                'author' => $it['author'],
                'date' => $it['date'] . ' · Google',
                'text' => $it['text'],
                'rating' => $it['rating'],
                'avatar' => $it['avatar'],
                'owner_reply' => $it['owner_reply'],
            ];
        }
        if ($mirror !== []) {
            $store['about']['reviews'] = $mirror;
        }

        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Google Yorumları widget tasarımı ve otomasyon ayarları kaydedildi.',
            'google_reviews' => $store['google_reviews'],
            'about' => $store['about'],
        ]);
    }

    case 'google_reviews_sync': {
        if (isset($data['google_reviews']) && is_array($data['google_reviews'])) {
            $store['google_reviews'] = array_merge(get_google_reviews_state($store), $data['google_reviews']);
        }
        $syncRes = sync_google_reviews($store, [
            'query_input' => (string)($data['query_input'] ?? ($store['google_reviews']['query_input'] ?? '')),
            'place_id' => (string)($data['place_id'] ?? ($store['google_reviews']['place_id'] ?? '')),
            'api_key' => (string)($data['api_key'] ?? ($store['google_reviews']['api_key'] ?? '')),
            'business_name' => (string)($data['business_name'] ?? ($store['google_reviews']['business_name'] ?? '')),
        ]);
        api_json(true, [
            'message' => $syncRes['message'],
            'added_reviews' => $syncRes['added_reviews'],
            'google_reviews' => $syncRes['google_reviews'],
            'about' => $store['about'],
        ]);
    }

    case 'google_reviews_bulk_import': {
        if (isset($data['google_reviews']) && is_array($data['google_reviews'])) {
            $store['google_reviews'] = array_merge(get_google_reviews_state($store), $data['google_reviews']);
        }
        $rawInput = trim((string)($data['raw_input'] ?? ''));
        if ($rawInput === '') {
            api_json(false, ['error' => 'Lütfen Google Haritalar yorum metnini veya JSON verisini kutuya yapıştırın.'], 422);
        }
        $parsed = gr_parse_bulk_reviews_input($rawInput);
        if ($parsed === []) {
            api_json(false, ['error' => 'Yapıştırılan içerikte geçerli bir Google yorumu tespit edilemedi. Google Haritalar sol panelindeki yorumlar sekmesinden kopyaladığınızdan emin olun.'], 422);
        }

        $gr = get_google_reviews_state($store);
        $mergedRes = gr_merge_reviews($gr['items'], $parsed);
        $gr['items'] = $mergedRes['items'];
        $gr['total_reviews'] = max((int)($gr['total_reviews'] ?? 34), count($gr['items']));
        $gr['last_synced_at'] = date('c');
        $gr['last_sync_status'] = 'Toplu aktarım tamamlandı: ' . count($parsed) . ' Google yorumu ayrıştırıldı (' . (int)$mergedRes['added'] . ' yeni eklendi · Havuzda toplam ' . count($gr['items']) . ' yorum aktif)';
        $store['google_reviews'] = $gr;

        $mirror = [];
        foreach ($gr['items'] as $it) {
            if (($it['visible'] ?? true) === false) {
                continue;
            }
            $mirror[] = [
                'author' => (string)($it['author'] ?? ''),
                'date' => trim((string)($it['date'] ?? '')) . ' · Google',
                'text' => (string)($it['text'] ?? ''),
                'rating' => (int)($it['rating'] ?? 5),
                'avatar' => (string)($it['avatar'] ?? ''),
                'owner_reply' => (string)($it['owner_reply'] ?? ''),
            ];
        }
        if ($mirror !== []) {
            $store['about']['reviews'] = $mirror;
        }

        save_store_or_fail($store);
        api_json(true, [
            'message' => $gr['last_sync_status'],
            'parsed_count' => count($parsed),
            'added_reviews' => (int)$mergedRes['added'],
            'google_reviews' => $store['google_reviews'],
            'about' => $store['about'],
        ]);
    }

    case 'contact_legal_save': {
        if (isset($data['contact']) && is_array($data['contact'])) {
            $cntIn = $data['contact'];
            if (!empty($cntIn['map_embed_url']) && is_string($cntIn['map_embed_url']) && str_contains($cntIn['map_embed_url'], '<iframe')) {
                if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $cntIn['map_embed_url'], $mSrc)) {
                    $cntIn['map_embed_url'] = trim($mSrc[1]);
                }
            }
            $store['contact'] = array_merge($store['contact'] ?? [], $cntIn);
        }
        if (isset($data['site_contact']) && is_array($data['site_contact'])) {
            $sc = $data['site_contact'];
            $store['site'] = is_array($store['site'] ?? null) ? $store['site'] : [];
            foreach (['phone', 'phone_href', 'email', 'address', 'city'] as $scField) {
                if (array_key_exists($scField, $sc)) {
                    $store['site'][$scField] = trim((string)$sc[$scField]);
                }
            }
            if (array_key_exists('show_contact_socials', $sc)) {
                $store['site']['show_contact_socials'] = (bool)$sc['show_contact_socials'];
            }
        }
        if (isset($data['social_profiles']) && is_array($data['social_profiles'])) {
            $cleanSoc = [];
            foreach ($data['social_profiles'] as $rawSoc) {
                $su = trim((string)$rawSoc);
                if ($su === '') {
                    continue;
                }
                if (!preg_match('~^https?://~i', $su)) {
                    $su = 'https://' . ltrim($su, '/');
                }
                $cleanSoc[] = $su;
            }
            $store['seo'] = is_array($store['seo'] ?? null) ? $store['seo'] : [];
            $store['seo']['social_profiles'] = array_values(array_unique($cleanSoc));
        }
        if (isset($data['page_typography']) && is_array($data['page_typography'])) {
            $cleanPgTypo = [];
            foreach ($data['page_typography'] as $pgK => $ptObj) {
                $cleanKey = preg_replace('/[^a-z0-9:_-]/i', '', (string)$pgK);
                if ($cleanKey === '' || !is_array($ptObj)) {
                    continue;
                }
                $bf = trim((string)($ptObj['body_font'] ?? 'inherit'));
                $cbf = zeynart_extract_font_family((string)($ptObj['custom_body_font'] ?? ''));
                $hf = trim((string)($ptObj['heading_font'] ?? 'inherit'));
                $chf = zeynart_extract_font_family((string)($ptObj['custom_heading_font'] ?? ''));
                $bbsRaw = (float)($ptObj['base_body_size'] ?? 0);
                $bbs = ($bbsRaw >= 11.5 && $bbsRaw <= 22.0) ? round(max(12.0, min(20.0, $bbsRaw)), 1) : 0;
                $hscRaw = (float)($ptObj['heading_scale'] ?? 0);
                $hsc = ($hscRaw >= 70.0 && $hscRaw <= 150.0) ? round(max(75.0, min(140.0, $hscRaw)), 0) : 0;
                $htrk = trim((string)($ptObj['heading_tracking'] ?? 'inherit'));
                if (!in_array($htrk, ['inherit', 'tight', 'balanced', 'normal'], true)) {
                    $htrk = 'inherit';
                }
                $normBf = ($bf === '' || $bf === 'inherit') ? 'inherit' : ($bf === 'custom' ? 'custom' : zeynart_extract_font_family($bf));
                $normHf = ($hf === '' || $hf === 'inherit') ? 'inherit' : (($hf === 'same' || $hf === 'custom') ? $hf : zeynart_extract_font_family($hf));

                if ($normBf === 'inherit' && $normHf === 'inherit' && $bbs == 0 && $hsc == 0 && $htrk === 'inherit') {
                    continue;
                }
                $cleanPgTypo[$cleanKey] = [
                    'body_font' => $normBf,
                    'custom_body_font' => $cbf,
                    'heading_font' => $normHf,
                    'custom_heading_font' => $chf,
                    'base_body_size' => $bbs,
                    'heading_scale' => $hsc,
                    'heading_tracking' => $htrk,
                ];
            }
            $store['page_typography'] = $cleanPgTypo;
        }
        if (isset($data['page_states']) && is_array($data['page_states'])) {
            $store['page_states'] = is_array($store['page_states'] ?? null) ? $store['page_states'] : [];
            foreach ($data['page_states'] as $stKey => $stVal) {
                $cleanStKey = str_starts_with((string)$stKey, 'legal:') ? substr((string)$stKey, 6) : (string)$stKey;
                $cleanStKey = preg_replace('/[^a-z0-9_-]/i', '', $cleanStKey) ?? '';
                if ($cleanStKey === '' || $cleanStKey === 'anasayfa' || !is_array($stVal)) {
                    continue;
                }
                $normSt = zeynart_normalize_page_status($stVal['status'] ?? 'published');
                $entry = ['status' => $normSt];
                if (!empty($stVal['trashed_at']) && is_string($stVal['trashed_at'])) {
                    $entry['trashed_at'] = $stVal['trashed_at'];
                }
                if (isset($stVal['prev_menu']) && is_array($stVal['prev_menu'])) {
                    $entry['prev_menu'] = [
                        'in_header' => !empty($stVal['prev_menu']['in_header']),
                        'in_footer' => !empty($stVal['prev_menu']['in_footer']),
                        'footer_col' => max(0, (int)($stVal['prev_menu']['footer_col'] ?? 0)),
                        'menu_label' => trim((string)($stVal['prev_menu']['menu_label'] ?? '')),
                    ];
                }
                if ($normSt === 'published' && empty($entry['prev_menu'])) {
                    unset($store['page_states'][$cleanStKey]);
                } else {
                    $store['page_states'][$cleanStKey] = $entry;
                }
            }
        }
        if (isset($data['legal_pages']) && is_array($data['legal_pages'])) {
            $store['legal_pages'] = is_array($store['legal_pages'] ?? null) ? $store['legal_pages'] : [];
            foreach ($data['legal_pages'] as $lk => $lpVal) {
                if (!is_array($lpVal)) {
                    continue;
                }
                if (isset($lpVal['content_html'])) {
                    $lpVal['content_html'] = sanitize_rich_html((string)$lpVal['content_html']);
                }
                $store['legal_pages'][(string)$lk] = array_merge($store['legal_pages'][(string)$lk] ?? [], $lpVal);
            }
        }
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'İletişim ve yasal politika sayfaları kaydedildi.',
            'contact' => $store['contact'],
            'legal_pages' => $store['legal_pages'],
        ]);
    }

    case 'unified_pages_save': {
        $prevCoreLegalUrls = [];
        foreach (array_keys(zeynart_default_page_slugs()) as $pk) {
            $prevCoreLegalUrls[$pk] = zeynart_page_url($store, $pk);
        }
        $prevCustomUrls = [];
        foreach (($store['custom_pages'] ?? []) as $cpOld) {
            if (is_array($cpOld) && !empty($cpOld['id']) && !empty($cpOld['slug'])) {
                $prevCustomUrls[(string)$cpOld['id']] = '/' . trim((string)$cpOld['slug'], '/') . '/';
            }
        }

        if (isset($data['home']) && is_array($data['home'])) {
            $normHome = normalize_home_sections($data['home'], $store);
            $store['home'] = $normHome;
            $store['google_reviews'] = is_array($store['google_reviews'] ?? null) ? $store['google_reviews'] : [];
            $store['google_reviews']['show_on_home'] = !empty($normHome['section_visibility']['google_reviews']);
            $store['site_audit'] = is_array($store['site_audit'] ?? null) ? $store['site_audit'] : [];
            $store['site_audit']['show_on_home'] = !empty($normHome['section_visibility']['site_audit']);
            $store['quote_wizard'] = is_array($store['quote_wizard'] ?? null) ? $store['quote_wizard'] : [];
            $store['quote_wizard']['show_on_home'] = !empty($normHome['section_visibility']['quote_wizard']);
        }
        if (isset($data['about']) && is_array($data['about'])) {
            $aboutIn = $data['about'];
            if (isset($aboutIn['clients']) && is_array($aboutIn['clients'])) {
                $cleanClients = [];
                foreach (array_values($aboutIn['clients']) as $cIdx => $clRow) {
                    if (!is_array($clRow)) {
                        continue;
                    }
                    $cImg = trim((string)($clRow['image'] ?? ''));
                    $cName = trim((string)($clRow['name'] ?? ''));
                    if ($cImg === '' && $cName === '') {
                        continue;
                    }
                    if ($cName === '') {
                        $cName = pathinfo(parse_url($cImg, PHP_URL_PATH) ?: 'marka', PATHINFO_FILENAME) ?: ('Marka ' . ($cIdx + 1));
                    }
                    $cUrl = trim((string)($clRow['url'] ?? '#'));
                    if ($cUrl === '' || $cUrl === 'https://' || $cUrl === 'http://') {
                        $cUrl = '#';
                    } elseif ($cUrl !== '#' && !str_starts_with($cUrl, '/') && !preg_match('~^https?://~i', $cUrl)) {
                        $cUrl = 'https://' . ltrim($cUrl, '/');
                    }
                    $cleanClients[] = [
                        'id' => trim((string)($clRow['id'] ?? '')) ?: ('client-' . ($cIdx + 1)),
                        'name' => $cName,
                        'alt' => trim((string)($clRow['alt'] ?? '')) ?: $cName,
                        'url' => $cUrl,
                        'image' => $cImg !== '' ? $cImg : '/images/zeynart-logo.avif',
                    ];
                }
                $aboutIn['clients'] = $cleanClients;
            }
            $store['about'] = array_merge(is_array($store['about'] ?? null) ? $store['about'] : [], $aboutIn);
        }
        if (isset($data['services_page']) && is_array($data['services_page'])) {
            $store['services_page'] = $data['services_page'];
        }
        if (isset($data['services']) && is_array($data['services'])) {
            $cleanServices = [];
            foreach (array_values($data['services']) as $idx => $srv) {
                if (!is_array($srv) || trim((string)($srv['title'] ?? '')) === '') {
                    continue;
                }
                $srvStatus = zeynart_normalize_page_status($srv['status'] ?? 'published');
                if ($srvStatus === 'deleted') {
                    continue;
                }
                $srv['status'] = $srvStatus;
                $srv['slug'] = slugify((string)($srv['slug'] ?? $srv['title']));
                $srv['num'] = trim((string)($srv['num'] ?? str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT)));
                $cleanServices[] = $srv;
            }
            $store['services'] = $cleanServices;
        }
        if (isset($data['projects_page']) && is_array($data['projects_page'])) {
            $store['projects_page'] = $data['projects_page'];
        }
        if (isset($data['projects']) && is_array($data['projects'])) {
            $cleanProjects = [];
            foreach (array_values($data['projects']) as $prj) {
                if (!is_array($prj) || trim((string)($prj['name'] ?? '')) === '') {
                    continue;
                }
                $prjStatus = zeynart_normalize_page_status($prj['status'] ?? 'published');
                if ($prjStatus === 'deleted') {
                    continue;
                }
                $prj['status'] = $prjStatus;
                $prj['slug'] = slugify((string)($prj['slug'] ?? $prj['name']));
                $cleanProjects[] = $prj;
            }
            $store['projects'] = $cleanProjects;
        }
        if (isset($data['blog_settings']) && is_array($data['blog_settings'])) {
            $bsIn = $data['blog_settings'];
            if (isset($bsIn['categories'])) {
                $rawCats = is_array($bsIn['categories']) ? $bsIn['categories'] : explode(',', (string)$bsIn['categories']);
                $normCats = [];
                foreach ($rawCats as $c) {
                    $cc = mb_strtoupper(trim((string)$c), 'UTF-8');
                    if ($cc !== '') {
                        $normCats[] = $cc;
                    }
                }
                if (!in_array('TÜMÜ', $normCats, true)) {
                    array_unshift($normCats, 'TÜMÜ');
                }
                $bsIn['categories'] = array_values(array_unique($normCats));
            }
            if (array_key_exists('show_share_bar', $bsIn)) {
                $bsIn['show_share_bar'] = (bool)$bsIn['show_share_bar'];
            }
            $store['blog_settings'] = array_merge($store['blog_settings'] ?? [], $bsIn);
        }
        if (isset($data['page_titles']) && is_array($data['page_titles'])) {
            $store['page_titles'] = is_array($store['page_titles'] ?? null) ? $store['page_titles'] : [];
            foreach ($data['page_titles'] as $ptKey => $ptVal) {
                $cleanPtKey = preg_replace('/[^a-z0-9:_-]/i', '', (string)$ptKey);
                $cleanPtVal = trim((string)$ptVal);
                if ($cleanPtKey !== '' && $cleanPtVal !== '') {
                    $store['page_titles'][$cleanPtKey] = $cleanPtVal;
                }
            }
        }
        if (isset($data['page_menu_labels']) && is_array($data['page_menu_labels'])) {
            $store['page_menu_labels'] = is_array($store['page_menu_labels'] ?? null) ? $store['page_menu_labels'] : [];
            foreach ($data['page_menu_labels'] as $pmKey => $pmVal) {
                $cleanPmKey = preg_replace('/[^a-z0-9:_-]/i', '', (string)$pmKey);
                $cleanPmVal = trim((string)$pmVal);
                if ($cleanPmKey !== '' && $cleanPmVal !== '') {
                    $store['page_menu_labels'][$cleanPmKey] = $cleanPmVal;
                }
            }
        }
        if (isset($data['contact']) && is_array($data['contact'])) {
            $cntIn = $data['contact'];
            if (!empty($cntIn['map_embed_url']) && is_string($cntIn['map_embed_url']) && str_contains($cntIn['map_embed_url'], '<iframe')) {
                if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $cntIn['map_embed_url'], $mSrc)) {
                    $cntIn['map_embed_url'] = trim($mSrc[1]);
                }
            }
            $store['contact'] = array_merge($store['contact'] ?? [], $cntIn);
        }
        if (isset($data['site_contact']) && is_array($data['site_contact'])) {
            $sc = $data['site_contact'];
            $store['site'] = is_array($store['site'] ?? null) ? $store['site'] : [];
            foreach (['phone', 'phone_href', 'email', 'address', 'city'] as $scField) {
                if (array_key_exists($scField, $sc)) {
                    $store['site'][$scField] = trim((string)$sc[$scField]);
                }
            }
            if (array_key_exists('show_contact_socials', $sc)) {
                $store['site']['show_contact_socials'] = (bool)$sc['show_contact_socials'];
            }
        }
        if (isset($data['social_profiles']) && is_array($data['social_profiles'])) {
            $cleanSoc = [];
            foreach ($data['social_profiles'] as $rawSoc) {
                $su = trim((string)$rawSoc);
                if ($su === '') {
                    continue;
                }
                if (!preg_match('~^https?://~i', $su)) {
                    $su = 'https://' . ltrim($su, '/');
                }
                $cleanSoc[] = $su;
            }
            $store['seo'] = is_array($store['seo'] ?? null) ? $store['seo'] : [];
            $store['seo']['social_profiles'] = array_values(array_unique($cleanSoc));
        }
        if (isset($data['page_typography']) && is_array($data['page_typography'])) {
            $cleanPgTypo = [];
            foreach ($data['page_typography'] as $pgK => $ptObj) {
                $cleanKey = preg_replace('/[^a-z0-9:_-]/i', '', (string)$pgK);
                if ($cleanKey === '' || !is_array($ptObj)) {
                    continue;
                }
                $bf = trim((string)($ptObj['body_font'] ?? 'inherit'));
                $cbf = zeynart_extract_font_family((string)($ptObj['custom_body_font'] ?? ''));
                $hf = trim((string)($ptObj['heading_font'] ?? 'inherit'));
                $chf = zeynart_extract_font_family((string)($ptObj['custom_heading_font'] ?? ''));
                $bbsRaw = (float)($ptObj['base_body_size'] ?? 0);
                $bbs = ($bbsRaw >= 11.5 && $bbsRaw <= 22.0) ? round(max(12.0, min(20.0, $bbsRaw)), 1) : 0;
                $hscRaw = (float)($ptObj['heading_scale'] ?? 0);
                $hsc = ($hscRaw >= 70.0 && $hscRaw <= 150.0) ? round(max(75.0, min(140.0, $hscRaw)), 0) : 0;
                $htrk = trim((string)($ptObj['heading_tracking'] ?? 'inherit'));
                if (!in_array($htrk, ['inherit', 'tight', 'balanced', 'normal'], true)) {
                    $htrk = 'inherit';
                }
                $normBf = ($bf === '' || $bf === 'inherit') ? 'inherit' : ($bf === 'custom' ? 'custom' : zeynart_extract_font_family($bf));
                $normHf = ($hf === '' || $hf === 'inherit') ? 'inherit' : (($hf === 'same' || $hf === 'custom') ? $hf : zeynart_extract_font_family($hf));

                if ($normBf === 'inherit' && $normHf === 'inherit' && $bbs == 0 && $hsc == 0 && $htrk === 'inherit') {
                    continue;
                }
                $cleanPgTypo[$cleanKey] = [
                    'body_font' => $normBf,
                    'custom_body_font' => $cbf,
                    'heading_font' => $normHf,
                    'custom_heading_font' => $chf,
                    'base_body_size' => $bbs,
                    'heading_scale' => $hsc,
                    'heading_tracking' => $htrk,
                ];
            }
            $store['page_typography'] = $cleanPgTypo;
        }
        if (isset($data['page_states']) && is_array($data['page_states'])) {
            $store['page_states'] = is_array($store['page_states'] ?? null) ? $store['page_states'] : [];
            foreach ($data['page_states'] as $stKey => $stVal) {
                $cleanStKey = str_starts_with((string)$stKey, 'legal:') ? substr((string)$stKey, 6) : (string)$stKey;
                $cleanStKey = preg_replace('/[^a-z0-9_-]/i', '', $cleanStKey) ?? '';
                if ($cleanStKey === '' || $cleanStKey === 'anasayfa' || !is_array($stVal)) {
                    continue;
                }
                $normSt = zeynart_normalize_page_status($stVal['status'] ?? 'published');
                $entry = ['status' => $normSt];
                if (!empty($stVal['trashed_at']) && is_string($stVal['trashed_at'])) {
                    $entry['trashed_at'] = $stVal['trashed_at'];
                }
                if (isset($stVal['prev_menu']) && is_array($stVal['prev_menu'])) {
                    $entry['prev_menu'] = [
                        'in_header' => !empty($stVal['prev_menu']['in_header']),
                        'in_footer' => !empty($stVal['prev_menu']['in_footer']),
                        'footer_col' => max(0, (int)($stVal['prev_menu']['footer_col'] ?? 0)),
                        'menu_label' => trim((string)($stVal['prev_menu']['menu_label'] ?? '')),
                    ];
                }
                if ($normSt === 'published' && empty($entry['prev_menu'])) {
                    unset($store['page_states'][$cleanStKey]);
                } else {
                    $store['page_states'][$cleanStKey] = $entry;
                }
            }
        }
        if (isset($data['legal_pages']) && is_array($data['legal_pages'])) {
            $store['legal_pages'] = is_array($store['legal_pages'] ?? null) ? $store['legal_pages'] : [];
            foreach ($data['legal_pages'] as $lk => $lpVal) {
                if (!is_array($lpVal)) {
                    continue;
                }
                if (isset($lpVal['content_html'])) {
                    $lpVal['content_html'] = sanitize_rich_html((string)$lpVal['content_html']);
                }
                $store['legal_pages'][(string)$lk] = array_merge($store['legal_pages'][(string)$lk] ?? [], $lpVal);
            }
        }
        if ((isset($data['page_slugs']) && is_array($data['page_slugs'])) || isset($data['legal_pages'])) {
            $defaultsMap = zeynart_default_page_slugs();
            $sysReserved = array_flip(zeynart_system_reserved_slugs());
            $mergedSlugs = zeynart_resolved_page_slugs($store);
            if (isset($data['page_slugs']) && is_array($data['page_slugs'])) {
                foreach ($data['page_slugs'] as $psKey => $psVal) {
                    $cleanPsKey = str_starts_with((string)$psKey, 'legal:') ? substr((string)$psKey, 6) : (string)$psKey;
                    if (!isset($defaultsMap[$cleanPsKey])) {
                        continue;
                    }
                    $cand = slugify((string)$psVal !== '' ? (string)$psVal : $defaultsMap[$cleanPsKey]);
                    if (isset($sysReserved[$cand])) {
                        api_json(false, ['error' => '"/' . $cand . '/" sistem dizini olarak ayrılmıştır. Lütfen farklı bir kalıcı bağlantı uzantısı girin.'], 422);
                    }
                    $mergedSlugs[$cleanPsKey] = $cand;
                }
            }
            if (isset($data['legal_pages']) && is_array($data['legal_pages'])) {
                foreach (['kvkk', 'gizlilik', 'cerez-politikasi'] as $lk) {
                    if (!empty($data['legal_pages'][$lk]['slug']) && (!isset($data['page_slugs'][$lk]))) {
                        $cand = slugify((string)$data['legal_pages'][$lk]['slug']);
                        if (isset($sysReserved[$cand])) {
                            api_json(false, ['error' => '"/' . $cand . '/" sistem dizini olarak ayrılmıştır. Lütfen farklı bir kalıcı bağlantı uzantısı girin.'], 422);
                        }
                        $mergedSlugs[$lk] = $cand;
                    }
                }
            }
            $seenCoreSlugs = [];
            foreach ($mergedSlugs as $mKey => $mSlug) {
                if (isset($seenCoreSlugs[$mSlug])) {
                    api_json(false, ['error' => '"/' . $mSlug . '/" adresi başka bir sayfa tarafından kullanılıyor. Lütfen benzersiz bir URL uzantısı girin.'], 422);
                }
                $seenCoreSlugs[$mSlug] = $mKey;
            }
            $store['page_slugs'] = $mergedSlugs;
            if (!isset($store['legal_pages']) || !is_array($store['legal_pages'])) {
                $store['legal_pages'] = [];
            }
            foreach (['kvkk', 'gizlilik', 'cerez-politikasi'] as $lk) {
                if (isset($store['legal_pages'][$lk]) && is_array($store['legal_pages'][$lk])) {
                    $store['legal_pages'][$lk]['slug'] = $mergedSlugs[$lk];
                }
            }
        }
        if (isset($data['custom_pages']) && is_array($data['custom_pages'])) {
            $activeCoreSlugs = [];
            foreach (zeynart_resolved_page_slugs($store) as $rk => $rSlug) {
                if (!in_array(zeynart_page_status($store, $rk), ['trash', 'deleted'], true)) {
                    $activeCoreSlugs[] = $rSlug;
                }
            }
            $reserved = array_values(array_unique(array_merge(
                zeynart_system_reserved_slugs(),
                $activeCoreSlugs
            )));
            $cleanCustom = [];
            foreach (array_values($data['custom_pages']) as $cp) {
                if (!is_array($cp) || trim((string)($cp['title'] ?? '')) === '') {
                    continue;
                }
                $cpStatus = zeynart_normalize_page_status($cp['status'] ?? 'published');
                if ($cpStatus === 'deleted') {
                    continue;
                }
                $cTitle = trim((string)$cp['title']);
                $cSlug = slugify((string)($cp['slug'] ?? $cTitle));
                if (in_array($cSlug, $reserved, true)) {
                    $cSlug .= '-sayfa';
                }
                $cId = trim((string)($cp['id'] ?? ''));
                if ($cId === '') {
                    $cId = 'page-' . substr(bin2hex(random_bytes(4)), 0, 8);
                }
                $cp['id'] = $cId;
                $cp['slug'] = $cSlug;
                $cp['title'] = $cTitle;
                $cp['status'] = $cpStatus;
                $cp['content_html'] = sanitize_rich_html((string)($cp['content_html'] ?? ''));
                $cp['sections'] = is_array($cp['sections'] ?? null) ? array_values($cp['sections']) : [];
                $cp['updated_at'] = $cp['updated_at'] ?? date('c');
                $cleanCustom[] = $cp;
            }
            $store['custom_pages'] = $cleanCustom;
        }
        if (isset($data['core_page_blocks']) && is_array($data['core_page_blocks'])) {
            if (!isset($store['core_page_blocks']) || !is_array($store['core_page_blocks'])) {
                $store['core_page_blocks'] = [];
            }
            foreach ($data['core_page_blocks'] as $pk => $bSec) {
                if (is_array($bSec)) {
                    $store['core_page_blocks'][(string)$pk] = array_values($bSec);
                }
            }
        }
        if (isset($data['seo_pages']) && is_array($data['seo_pages'])) {
            if (!isset($store['seo']['pages']) || !is_array($store['seo']['pages'])) {
                $store['seo']['pages'] = [];
            }
            foreach ($data['seo_pages'] as $sk => $sVal) {
                if (is_array($sVal)) {
                    $store['seo']['pages'][(string)$sk] = array_merge($store['seo']['pages'][(string)$sk] ?? [], $sVal);
                }
            }
        }
        if (isset($data['cta_global']) && is_array($data['cta_global'])) {
            $store['cta_global'] = array_merge($store['cta_global'] ?? [], $data['cta_global']);
        }
        if (isset($data['page_live_patches']) && is_array($data['page_live_patches'])) {
            $cleanPatchesMap = [];
            foreach ($data['page_live_patches'] as $lpKey => $lpArr) {
                $cleanLpKey = preg_replace('/[^a-zA-Z0-9:_-]/', '', (string)$lpKey);
                if ($cleanLpKey === '' || !is_array($lpArr)) {
                    continue;
                }
                $cleanList = [];
                foreach ($lpArr as $patch) {
                    if (!is_array($patch) || !isset($patch['path']) || !is_array($patch['path'])) {
                        continue;
                    }
                    $pathNums = array_map('intval', array_values($patch['path']));
                    $rawRoot = (string)($patch['root'] ?? 'main');
                    $entry = [
                        'root' => in_array($rawRoot, ['header', 'footer', 'main'], true) ? $rawRoot : 'main',
                        'path' => $pathNums,
                    ];
                    foreach (['text', 'src', 'alt', 'href'] as $strF) {
                        if (isset($patch[$strF]) && is_string($patch[$strF])) {
                            $val = trim($patch[$strF]);
                            if ($strF === 'href' && preg_match('/^\s*(javascript|vbscript|data):/i', $val)) {
                                continue;
                            }
                            $entry[$strF] = $val;
                        }
                    }
                    if (isset($patch['style']) && is_array($patch['style'])) {
                        $stClean = [];
                        if (!empty($patch['style']['color']) && preg_match('/^[#a-zA-Z0-9(),.\s%-]{1,40}$/', (string)$patch['style']['color'])) {
                            $stClean['color'] = (string)$patch['style']['color'];
                        }
                        if (!empty($patch['style']['fontSize']) && preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/', (string)$patch['style']['fontSize'])) {
                            $stClean['fontSize'] = (string)$patch['style']['fontSize'];
                        }
                        if (!empty($patch['style']['textAlign']) && in_array($patch['style']['textAlign'], ['left', 'center', 'right'], true)) {
                            $stClean['textAlign'] = $patch['style']['textAlign'];
                        }
                        if (!empty($patch['style']['hidden'])) {
                            $stClean['hidden'] = true;
                        }
                        if ($stClean !== []) {
                            $entry['style'] = $stClean;
                        }
                    }
                    $cleanList[] = $entry;
                }
                if ($cleanList !== []) {
                    $cleanPatchesMap[$cleanLpKey] = $cleanList;
                }
            }
            $store['page_live_patches'] = $cleanPatchesMap;
        }
        if (isset($data['menus']) && is_array($data['menus'])) {
            if (isset($data['menus']['header']) && is_array($data['menus']['header'])) {
                $store['menus']['header'] = array_merge($store['menus']['header'] ?? [], $data['menus']['header']);
            }
            if (isset($data['menus']['footer']) && is_array($data['menus']['footer'])) {
                $store['menus']['footer'] = array_merge($store['menus']['footer'] ?? [], $data['menus']['footer']);
            }
        }

        // Propagate any URL renames across menus, consent links, and 301 redirect rules
        foreach ($prevCoreLegalUrls as $pk => $oldU) {
            $newU = zeynart_page_url($store, $pk);
            if ($oldU !== $newU) {
                zeynart_register_url_rename($store, $oldU, $newU);
                if ($pk === 'hizmetler') {
                    foreach (($store['services'] ?? []) as $srvItem) {
                        $sSlug = trim((string)($srvItem['slug'] ?? ''), '/');
                        if ($sSlug !== '') {
                            zeynart_register_url_rename($store, $oldU . $sSlug . '/', $newU . $sSlug . '/');
                        }
                    }
                } elseif ($pk === 'projeler') {
                    foreach (($store['projects'] ?? []) as $prjItem) {
                        $pSlug = trim((string)($prjItem['slug'] ?? ''), '/');
                        if ($pSlug !== '') {
                            zeynart_register_url_rename($store, $oldU . $pSlug . '/', $newU . $pSlug . '/');
                        }
                    }
                }
            }
        }
        foreach (($store['custom_pages'] ?? []) as $cpNow) {
            $cpId = (string)($cpNow['id'] ?? '');
            $cpSlug = trim((string)($cpNow['slug'] ?? ''), '/');
            if ($cpId !== '' && $cpSlug !== '' && isset($prevCustomUrls[$cpId])) {
                $newCpUrl = '/' . $cpSlug . '/';
                if ($prevCustomUrls[$cpId] !== $newCpUrl) {
                    zeynart_register_url_rename($store, $prevCustomUrls[$cpId], $newCpUrl);
                }
            }
        }
        if (isset($data['url_renames']) && is_array($data['url_renames'])) {
            foreach ($data['url_renames'] as $rn) {
                if (is_array($rn) && !empty($rn['from']) && !empty($rn['to'])) {
                    zeynart_register_url_rename($store, (string)$rn['from'], (string)$rn['to']);
                }
            }
        }

        save_store_or_fail($store);
        sync_static_ai_manifests($store);
        api_json(true, [
            'message' => (string)($data['toast_message'] ?? 'Sayfa değişiklikleri başarıyla kaydedildi ve yayına alındı.'),
            'store' => build_safe_store_for_client($store),
        ]);
    }

    case 'seo_save': {
        $prevSeo = is_array($store['seo'] ?? null) ? $store['seo'] : [];
        $prevCc = is_array($store['cookie_consent'] ?? null) ? $store['cookie_consent'] : [];
        if (isset($data['seo']) && is_array($data['seo'])) {
            $store['seo'] = array_merge($prevSeo, $data['seo']);
            if (!empty($store['seo']['google_verification']) && preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', (string)$store['seo']['google_verification'], $gscM)) {
                $store['seo']['google_verification'] = trim($gscM[1]);
            }
            if ($activeRole !== 'super_admin') {
                foreach (['custom_head_scripts', 'robots_custom_rules', 'ga_measurement_id', 'ga4_id', 'google_verification', 'gsc_verification', 'bing_verification', 'yandex_verification', 'indexnow_key'] as $protectedSeoKey) {
                    if (array_key_exists($protectedSeoKey, $prevSeo)) {
                        $store['seo'][$protectedSeoKey] = $prevSeo[$protectedSeoKey];
                    } else {
                        unset($store['seo'][$protectedSeoKey]);
                    }
                }
            }
            if (isset($data['seo']['social_profiles']) && is_array($data['seo']['social_profiles'])) {
                $cleanSocials = [];
                foreach ($data['seo']['social_profiles'] as $rawSoc) {
                    $su = trim((string)$rawSoc);
                    if ($su === '') {
                        continue;
                    }
                    if (!preg_match('~^https?://~i', $su)) {
                        $su = 'https://' . ltrim($su, '/');
                    }
                    $cleanSocials[] = $su;
                }
                $store['seo']['social_profiles'] = array_values(array_unique($cleanSocials));
            }
        }
        $store['seo'] = is_array($store['seo'] ?? null) ? $store['seo'] : [];
        $store['seo']['branded_og'] = zeynart_get_branded_og_config($store);

        if (isset($data['site_socials']) && is_array($data['site_socials'])) {
            $store['site'] = $store['site'] ?? [];
            $store['site']['show_header_socials'] = !empty($data['site_socials']['show_header_socials']);
            $store['site']['show_footer_socials'] = !empty($data['site_socials']['show_footer_socials']);
            $store['site']['show_contact_socials'] = !empty($data['site_socials']['show_contact_socials']);
        }
        if (isset($data['ai_seo']) && is_array($data['ai_seo'])) {
            $prevAiSeo = is_array($store['ai_seo'] ?? null) ? $store['ai_seo'] : [];
            $store['ai_seo'] = array_merge($prevAiSeo, $data['ai_seo']);
            if ($activeRole !== 'super_admin') {
                foreach (['openai_api_key', 'gemini_api_key'] as $protectedAiKey) {
                    if (array_key_exists($protectedAiKey, $prevAiSeo)) {
                        $store['ai_seo'][$protectedAiKey] = $prevAiSeo[$protectedAiKey];
                    } else {
                        unset($store['ai_seo'][$protectedAiKey]);
                    }
                }
            }
        }
        if (isset($data['redirects']) && is_array($data['redirects'])) {
            $cleanRedirects = [];
            foreach ($data['redirects'] as $idx => $r) {
                $from = '/' . ltrim(trim((string)($r['from'] ?? '')), '/');
                $to = preg_replace('/[\x00-\x1F\x7F]+/', '', trim((string)($r['to'] ?? ''))) ?? '';
                if ($from === '/' || $to === '' || $to === '/') {
                    continue;
                }
                // Block dangerous URI schemes or protocol-relative open redirects
                if (preg_match('~^(?:javascript|vbscript|data|file):~i', $to) || str_starts_with($to, '//')) {
                    continue;
                }
                if (!preg_match('~^https?://~i', $to) && !str_starts_with($to, '/')) {
                    $to = '/' . ltrim($to, '/');
                }
                $cleanRedirects[] = [
                    'id' => trim((string)($r['id'] ?? ('red-' . ($idx + 1)))),
                    'from' => $from,
                    'to' => $to,
                    'code' => in_array((int)($r['code'] ?? 301), [301, 302, 307, 308], true) ? (int)$r['code'] : 301,
                    'hits' => (int)($r['hits'] ?? 0),
                ];
            }
            $store['redirects'] = $cleanRedirects;
            if (function_exists('zeynart_prune_blog_conflicts_from_redirects')) {
                zeynart_prune_blog_conflicts_from_redirects($store);
            }
        }
        if (isset($data['cookie_consent']) && is_array($data['cookie_consent'])) {
            $ccIn = $data['cookie_consent'];
            $store['cookie_consent'] = array_merge(
                zeynart_default_cookie_consent_config(),
                $prevCc
            );
            $store['cookie_consent']['enabled'] = ($ccIn['enabled'] ?? true) !== false;
            $allowedCcKeys = [
                'banner_title', 'banner_text', 'policy_label', 'policy_url',
                'kvkk_label', 'kvkk_url', 'accept_all_label', 'reject_all_label',
                'manage_label', 'save_prefs_label', 'footer_reopen_label',
            ];
            if ($activeRole === 'super_admin') {
                $allowedCcKeys = array_merge($allowedCcKeys, ['custom_head_essential', 'custom_head_analytics', 'custom_head_marketing']);
            }
            foreach ($allowedCcKeys as $ccStrKey) {
                if (array_key_exists($ccStrKey, $ccIn)) {
                    $store['cookie_consent'][$ccStrKey] = trim((string)$ccIn[$ccStrKey]);
                }
            }
            if ($activeRole === 'super_admin' && array_key_exists('meta_pixel_id', $ccIn)) {
                $store['cookie_consent']['meta_pixel_id'] = preg_replace('/\D+/', '', (string)$ccIn['meta_pixel_id']) ?? '';
            }
        }
        if (isset($data['google_maps']) && is_array($data['google_maps'])) {
            $store['contact'] = is_array($store['contact'] ?? null) ? $store['contact'] : [];
            if (array_key_exists('maps_url', $data['google_maps'])) {
                $rawMapUrl = trim((string)$data['google_maps']['maps_url']);
                if ($rawMapUrl === '' || preg_match('~^https?://~i', $rawMapUrl)) {
                    $store['contact']['maps_url'] = $rawMapUrl;
                }
            }
            if (array_key_exists('map_embed_url', $data['google_maps'])) {
                $rawEmbed = trim((string)$data['google_maps']['map_embed_url']);
                if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $rawEmbed, $em)) {
                    $rawEmbed = trim($em[1]);
                }
                if ($rawEmbed === '' || preg_match('~^https://~i', $rawEmbed)) {
                    $store['contact']['map_embed_url'] = $rawEmbed;
                }
            }
        }
        if (isset($data['google_fonts']) && is_array($data['google_fonts'])) {
            $store['menus'] = is_array($store['menus'] ?? null) ? $store['menus'] : [];
            $prevTypo = is_array($store['menus']['typography'] ?? null) ? $store['menus']['typography'] : [];
            $gfIn = $data['google_fonts'];
            if (array_key_exists('body_font', $gfIn)) {
                $bf = trim((string)$gfIn['body_font']);
                $prevTypo['body_font'] = $bf !== '' ? ($bf === 'custom' ? 'custom' : zeynart_extract_font_family($bf)) : 'Manrope';
            }
            if (array_key_exists('custom_body_font', $gfIn)) {
                $prevTypo['custom_body_font'] = zeynart_extract_font_family((string)$gfIn['custom_body_font']);
            }
            if (array_key_exists('heading_font', $gfIn)) {
                $hf = trim((string)$gfIn['heading_font']);
                $prevTypo['heading_font'] = $hf !== '' ? (($hf === 'same' || $hf === 'custom') ? $hf : zeynart_extract_font_family($hf)) : 'same';
            }
            if (array_key_exists('custom_heading_font', $gfIn)) {
                $prevTypo['custom_heading_font'] = zeynart_extract_font_family((string)$gfIn['custom_heading_font']);
            }
            $store['menus']['typography'] = $prevTypo;
        }
        save_store_or_fail($store);
        sync_static_ai_manifests($store);
        $safeClientAfterSeo = build_safe_store_for_client($store);
        api_json(true, [
            'message' => 'Google & SEO Merkezi ayarları (GA4, Search Console, Harita, Google Fonts, GEO ve Yönlendirmeler) kaydedildi.',
            'seo' => $safeClientAfterSeo['seo'],
            'site' => $safeClientAfterSeo['site'] ?? [],
            'contact' => $safeClientAfterSeo['contact'] ?? [],
            'menus' => $safeClientAfterSeo['menus'] ?? [],
            'ai_seo' => $safeClientAfterSeo['ai_seo'] ?? [],
            'cookie_consent' => $safeClientAfterSeo['cookie_consent'] ?? zeynart_default_cookie_consent_config(),
            'redirects' => $store['redirects'],
            'robots_preview' => compile_robots_txt($store),
            'llms_preview' => compile_llms_txt($store, false),
        ]);
    }

    case 'ai_seo_generate': {
        $title = trim((string)($data['title'] ?? ''));
        $contentHtml = (string)($data['content_html'] ?? '');
        $category = trim((string)($data['category'] ?? 'GENEL'));
        $image = trim((string)($data['image'] ?? '/images/journal/web-planning.webp'));

        if ($title === '') {
            api_json(false, ['error' => 'Yapay Zeka SEO üretimi için önce bir başlık yazın.'], 422);
        }

        $pack = generate_ai_seo_pack($store, $title, $contentHtml, $category);
        $analysis = analyze_seo_score([
            'title' => $title,
            'meta_title' => $pack['meta_title'],
            'meta_desc' => $pack['meta_desc'],
            'focus_keyword' => $pack['focus_keyword'],
            'slug' => $pack['slug'],
            'content_html' => $contentHtml,
            'ai_summary' => $pack['ai_summary'],
            'image' => $image,
            'faqs' => $pack['faqs'],
        ]);

        api_json(true, [
            'message' => $pack['source'] . ' ile SEO & Yapay Zeka paketi oluşturuldu.',
            'pack' => $pack,
            'seo_analysis' => $analysis,
        ]);
    }

    case 'seo_score_check': {
        $analysis = analyze_seo_score(is_array($data['payload'] ?? null) ? $data['payload'] : $data);
        api_json(true, ['seo_analysis' => $analysis]);
    }

    case 'message_update': {
        $id = trim((string)($data['id'] ?? ''));
        $op = trim((string)($data['op'] ?? 'read'));
        $messages = is_array($store['messages'] ?? null) ? $store['messages'] : [];

        if ($op === 'delete') {
            $messages = array_values(array_filter($messages, static fn(array $m): bool => ($m['id'] ?? '') !== $id));
        } elseif ($op === 'mark_all_read') {
            foreach ($messages as &$m) {
                $m['read'] = true;
            }
            unset($m);
        } else {
            foreach ($messages as &$m) {
                if (($m['id'] ?? '') === $id) {
                    if ($op === 'read') {
                        $m['read'] = true;
                    } elseif ($op === 'unread') {
                        $m['read'] = false;
                    } elseif ($op === 'status') {
                        $m['status'] = trim((string)($data['status'] ?? 'contacted'));
                        $m['read'] = true;
                    } elseif ($op === 'crm_update') {
                        if (isset($data['crm_stage'])) {
                            $m['crm_stage'] = trim((string)$data['crm_stage']);
                            if ($m['crm_stage'] !== 'new') {
                                $m['read'] = true;
                            }
                        }
                        if (isset($data['crm_note'])) {
                            $m['crm_note'] = trim((string)$data['crm_note']);
                        }
                        if (isset($data['followup_date'])) {
                            $m['followup_date'] = trim((string)$data['followup_date']);
                        }
                        if (isset($data['deal_value'])) {
                            $m['deal_value'] = round(max(0.0, (float)$data['deal_value']), 2);
                        }
                        $m['updated_crm_at'] = date('c');
                    }
                    $m = zeynart_normalize_message_crm($m);
                    break;
                }
            }
            unset($m);
        }

        $store['messages'] = array_map(
            static fn($row) => is_array($row) ? zeynart_normalize_message_crm($row) : $row,
            $messages
        );
        save_store_or_fail($store);
        api_json(true, [
            'message' => $op === 'crm_update' ? '✓ Satış hunisi (CRM) aşaması ve takip notu kaydedildi.' : 'Mesaj kutusu güncellendi.',
            'messages' => $store['messages'],
            'crm_summary' => zeynart_compile_crm_summary($store['messages']),
        ]);
    }

    case 'social_media_save': {
        $store['site'] = is_array($store['site'] ?? null) ? $store['site'] : [];
        $store['seo'] = is_array($store['seo'] ?? null) ? $store['seo'] : [];
        $store['blog_settings'] = is_array($store['blog_settings'] ?? null) ? $store['blog_settings'] : [];

        if (isset($data['social_profiles']) && is_array($data['social_profiles'])) {
            $cleanSocials = [];
            $seenNorm = [];
            foreach ($data['social_profiles'] as $rawSoc) {
                $su = trim((string)$rawSoc);
                if ($su === '') {
                    continue;
                }
                if (!preg_match('~^https?://~i', $su)) {
                    $su = 'https://' . ltrim($su, '/');
                }
                $nk = strtolower(rtrim($su, '/'));
                if (isset($seenNorm[$nk])) {
                    continue;
                }
                $seenNorm[$nk] = true;
                $cleanSocials[] = $su;
            }
            $store['seo']['social_profiles'] = $cleanSocials;
        }

        if (isset($data['visibility']) && is_array($data['visibility'])) {
            $vis = $data['visibility'];
            if (array_key_exists('show_header_socials', $vis)) {
                $store['site']['show_header_socials'] = !empty($vis['show_header_socials']);
            }
            if (array_key_exists('show_footer_socials', $vis)) {
                $store['site']['show_footer_socials'] = !empty($vis['show_footer_socials']);
            }
            if (array_key_exists('show_contact_socials', $vis)) {
                $store['site']['show_contact_socials'] = !empty($vis['show_contact_socials']);
            }
        }

        if (isset($data['sharing']) && is_array($data['sharing'])) {
            $sh = $data['sharing'];
            if (array_key_exists('show_share_bar', $sh)) {
                $store['blog_settings']['show_share_bar'] = !empty($sh['show_share_bar']);
            }
            if (array_key_exists('og_image', $sh)) {
                $store['site']['og_image'] = trim((string)$sh['og_image']);
            }
            if (array_key_exists('twitter_handle', $sh)) {
                $th = trim((string)$sh['twitter_handle']);
                $th = preg_replace('/[^A-Za-z0-9_]/', '', ltrim($th, '@')) ?? '';
                $store['seo']['twitter_handle'] = $th !== '' ? ('@' . $th) : '';
            }
            if (array_key_exists('auto_branded_og', $sh)) {
                $store['seo']['auto_branded_og'] = !empty($sh['auto_branded_og']);
            }
        }

        save_store_or_fail($store);
        sync_static_ai_manifests($store);
        api_json(true, [
            'message' => '✓ Sosyal medya hesapları, ikon görünürlükleri ve paylaşım ayarları kaydedildi.',
            'site' => $store['site'],
            'seo' => $store['seo'],
            'blog_settings' => $store['blog_settings'],
        ]);
    }

    case 'settings_save': {
        if (isset($data['site']) && is_array($data['site'])) {
            $store['site'] = array_merge($store['site'] ?? [], $data['site']);
        }
        if (isset($data['social_profiles']) && is_array($data['social_profiles'])) {
            $cleanSocials = [];
            foreach ($data['social_profiles'] as $rawSoc) {
                $su = trim((string)$rawSoc);
                if ($su === '') {
                    continue;
                }
                if (!preg_match('~^https?://~i', $su)) {
                    $su = 'https://' . ltrim($su, '/');
                }
                $cleanSocials[] = $su;
            }
            $store['seo'] = $store['seo'] ?? [];
            $store['seo']['social_profiles'] = array_values(array_unique($cleanSocials));
        }
        if (isset($data['typography']) && is_array($data['typography'])) {
            $store['menus'] = is_array($store['menus'] ?? null) ? $store['menus'] : [];
            $prevT = is_array($store['menus']['typography'] ?? null) ? $store['menus']['typography'] : [];
            $t = array_merge($prevT, $data['typography']);
            $rawTrack = trim((string)($t['heading_tracking'] ?? 'tight'));
            $rawBodyF = trim((string)($t['body_font'] ?? 'Manrope'));
            $rawHeadF = trim((string)($t['heading_font'] ?? 'same'));
            $prevT['body_font'] = $rawBodyF !== '' ? ($rawBodyF === 'custom' ? 'custom' : zeynart_extract_font_family($rawBodyF)) : 'Manrope';
            $prevT['custom_body_font'] = zeynart_extract_font_family((string)($t['custom_body_font'] ?? ''));
            $prevT['heading_font'] = $rawHeadF !== '' ? ($rawHeadF === 'same' || $rawHeadF === 'custom' ? $rawHeadF : zeynart_extract_font_family($rawHeadF)) : 'same';
            $prevT['custom_heading_font'] = zeynart_extract_font_family((string)($t['custom_heading_font'] ?? ''));
            if (isset($t['base_body_size'])) {
                $prevT['base_body_size'] = round(max(12.0, min(20.0, (float)$t['base_body_size'])), 1);
            }
            if (isset($t['heading_scale'])) {
                $prevT['heading_scale'] = round(max(75.0, min(140.0, (float)$t['heading_scale'])), 0);
            }
            $prevT['heading_tracking'] = in_array($rawTrack, ['tight', 'balanced', 'normal'], true) ? $rawTrack : 'tight';
            $store['menus']['typography'] = $prevT;
        }
        if (isset($data['smtp']) && is_array($data['smtp'])) {
            $store['smtp'] = normalize_smtp_config($data['smtp'], is_array($store['smtp'] ?? null) ? $store['smtp'] : []);
        }
        if (isset($data['i18n']) && is_array($data['i18n'])) {
            $prevI18n = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
            $inI18n = $data['i18n'];
            $prov = strtolower(trim((string)($inI18n['provider'] ?? ($prevI18n['provider'] ?? 'auto'))));
            $store['i18n'] = [
                'enabled' => ($inI18n['enabled'] ?? ($prevI18n['enabled'] ?? true)) !== false,
                'auto_translate_blog' => ($inI18n['auto_translate_blog'] ?? ($prevI18n['auto_translate_blog'] ?? true)) !== false,
                'languages' => ['tr', 'en', 'de', 'ru'],
                'provider' => in_array($prov, ['auto', 'google', 'deepl', 'openai'], true) ? $prov : 'auto',
                'deepl_api_key' => trim((string)($inI18n['deepl_api_key'] ?? ($prevI18n['deepl_api_key'] ?? ''))),
                'openai_api_key' => trim((string)($inI18n['openai_api_key'] ?? ($prevI18n['openai_api_key'] ?? ''))),
                'openai_model' => trim((string)($inI18n['openai_model'] ?? ($prevI18n['openai_model'] ?? 'gpt-4o-mini'))) ?: 'gpt-4o-mini',
            ];
        }
        save_store_or_fail($store);
        sync_static_ai_manifests($store);
        api_json(true, [
            'message' => 'Genel site kimliği, çoklu dil, yazı tipi, sosyal medya ve e-posta ayarları güncellendi.',
            'site' => $store['site'],
            'menus' => $store['menus'] ?? [],
            'seo' => $store['seo'] ?? [],
            'smtp' => $store['smtp'],
            'i18n' => $store['i18n'] ?? [],
        ]);
    }

    case 'smtp_test': {
        if (isset($data['smtp']) && is_array($data['smtp'])) {
            $store['smtp'] = normalize_smtp_config($data['smtp'], is_array($store['smtp'] ?? null) ? $store['smtp'] : []);
        } else {
            $store['smtp'] = normalize_smtp_config([], is_array($store['smtp'] ?? null) ? $store['smtp'] : []);
        }

        $testTo = trim((string)($data['test_to'] ?? ''));
        if ($testTo === '') {
            $testTo = resolve_smtp_recipient($store);
        }

        $brandName = trim((string)($store['site']['brand_name'] ?? 'ZEYNART'));
        $testHtml = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:26px;background:#071318;color:#eaf4f7;border-radius:14px;border:1px solid #16353f">'
            . '<h2 style="margin:0 0 12px;color:#2dd4bf;font-size:20px">✓ ' . e($brandName) . ' — SMTP E-posta Testi Başarılı!</h2>'
            . '<p style="margin:0 0 14px;color:#b8d0d8;line-height:1.6">Bu e-posta, <strong>Zeynart Dijital Stüdyo</strong> yönetim panelindeki SMTP yapılandırmanızın sorunsuz çalıştığını doğrulamak için gönderilmiştir.</p>'
            . '<div style="padding:14px;border-radius:10px;background:#040b0e;border:1px solid #122c35;font-size:13px;color:#9fc0ca">'
            . '<div><strong>Sunucu:</strong> ' . e(($store['smtp']['encryption'] ?? '') === 'local' ? 'Yerel Sunucu (PHP mail / Postfix)' : (($store['smtp']['host'] ?? '') . ':' . (string)($store['smtp']['port'] ?? 587) . ' (' . strtoupper((string)($store['smtp']['encryption'] ?? 'tls')) . ')')) . '</div>'
            . '<div style="margin-top:4px"><strong>Gönderen:</strong> ' . e((string)($store['smtp']['from_email'] ?? '')) . '</div>'
            . '<div style="margin-top:4px"><strong>Tarih:</strong> ' . e(date('Y-m-d H:i:s')) . '</div>'
            . '</div>'
            . '</div>';

        $res = send_smtp_detailed(
            $store['smtp'],
            $testTo,
            '✓ ' . $brandName . ' — SMTP Bağlantı Testi (' . date('H:i:s') . ')',
            $testHtml,
            ['force' => true, 'allow_mail_fallback' => false]
        );

        $store['smtp_last_status'] = [
            'ok' => !empty($res['ok']),
            'at' => date('Y-m-d H:i:s'),
            'to' => $testTo,
            'method' => (string)($res['method'] ?? 'smtp'),
            'error' => (string)($res['error'] ?? ''),
        ];
        save_store_or_fail($store);

        api_json(true, [
            'sent' => !empty($res['ok']),
            'message' => !empty($res['ok'])
                ? ('SMTP ayarları kaydedildi ve test e-postası "' . $testTo . '" adresine başarıyla gönderildi!')
                : ('Test e-postası gönderilemedi: ' . ($res['error'] ?? 'Bilinmeyen hata')),
            'error' => (string)($res['error'] ?? ''),
            'server_reply' => (string)($res['server_reply'] ?? ''),
            'transcript' => $res['transcript'] ?? [],
            'smtp' => $store['smtp'],
            'smtp_last_status' => $store['smtp_last_status'],
        ]);
    }

    case 'password_change': {
        $currentPassword = (string)($data['current_password'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');
        $curProf = zeynart_current_admin_profile($store);

        if (!empty($curProf['is_primary'])) {
            $newUsername = trim((string)($data['username'] ?? $store['admin']['username'] ?? 'admin'));
            $displayName = trim((string)($data['display_name'] ?? $store['admin']['display_name'] ?? 'Zeynart Yönetici'));
            if (!password_verify($currentPassword, (string)($store['admin']['password_hash'] ?? ''))) {
                api_json(false, ['error' => 'Mevcut parolanız doğrulanamadı.'], 403);
            }
            if ($newUsername === '' || strlen($newUsername) < 3) {
                api_json(false, ['error' => 'Kullanıcı adı en az 3 karakter olmalıdır.'], 422);
            }
            foreach (($store['admin_users'] ?? []) as $suRow) {
                if (is_array($suRow) && strcasecmp((string)($suRow['username'] ?? ''), $newUsername) === 0) {
                    api_json(false, ['error' => 'Bu kullanıcı adı sistemdeki başka bir kullanıcı tarafından kullanılıyor.'], 422);
                }
            }
            $store['admin']['username'] = $newUsername;
            $store['admin']['display_name'] = $displayName !== '' ? $displayName : $newUsername;
            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) {
                    api_json(false, ['error' => 'Yeni parola en az 8 karakter olmalıdır.'], 422);
                }
                $store['admin']['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            }
            $_SESSION['zeynart_admin_user'] = $store['admin']['username'];
            $_SESSION['zeynart_admin_display'] = $store['admin']['display_name'];
        } else {
            $matchedIdx = null;
            foreach (($store['admin_users'] ?? []) as $idx => $uRow) {
                if (is_array($uRow) && strcasecmp((string)($uRow['username'] ?? ''), (string)$curProf['username']) === 0) {
                    $matchedIdx = $idx;
                    break;
                }
            }
            if ($matchedIdx === null) {
                api_json(false, ['error' => 'Oturum kullanıcı kaydı bulunamadı.'], 404);
            }
            if (!password_verify($currentPassword, (string)($store['admin_users'][$matchedIdx]['password_hash'] ?? ''))) {
                api_json(false, ['error' => 'Mevcut parolanız doğrulanamadı.'], 403);
            }
            $displayName = trim((string)($data['display_name'] ?? $store['admin_users'][$matchedIdx]['display_name'] ?? $curProf['username']));
            $store['admin_users'][$matchedIdx]['display_name'] = $displayName !== '' ? $displayName : $curProf['username'];
            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) {
                    api_json(false, ['error' => 'Yeni parola en az 8 karakter olmalıdır.'], 422);
                }
                $store['admin_users'][$matchedIdx]['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            }
            $_SESSION['zeynart_admin_display'] = $store['admin_users'][$matchedIdx]['display_name'];
        }

        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Hesap ve parola bilgileriniz güvenli şekilde güncellendi.',
            'current_user' => zeynart_current_admin_profile($store),
        ]);
    }

    case 'social_card_inspect':
    case 'social_meta_save': {
        require_once INCLUDES_DIR . '/social_card.php';

        $kind = ((string)($data['kind'] ?? 'page')) === 'blog' ? 'blog' : 'page';
        $targetKey = trim((string)($data['target_key'] ?? ''));
        $rawLang = strtolower(trim((string)($data['lang'] ?? 'tr')));
        $cardLang = in_array($rawLang, ['tr', 'en', 'de', 'ru'], true) ? $rawLang : 'tr';
        $isSave = ($action === 'social_meta_save');

        if ($isSave) {
            $inOgTitle = trim((string)($data['og_title'] ?? ''));
            $inOgDesc = trim((string)($data['og_desc'] ?? ''));
            $inOgImg = trim((string)($data['og_image'] ?? ''));

            if ($kind === 'blog') {
                $post = get_post($targetKey);
                if ($post === null) {
                    api_json(false, ['error' => 'Düzenlenecek blog yazısı bulunamadı.'], 404);
                }
                $post['seo'] = is_array($post['seo'] ?? null) ? $post['seo'] : [];
                if ($cardLang === 'tr') {
                    $post['seo']['og_title'] = $inOgTitle;
                    $post['seo']['og_desc'] = $inOgDesc;
                } else {
                    $post = zeynart_translate_blog_post($post, [$cardLang], false, $store['i18n'] ?? []);
                    $post['translations'][$cardLang]['seo'] = is_array($post['translations'][$cardLang]['seo'] ?? null)
                        ? $post['translations'][$cardLang]['seo']
                        : [];
                    $post['translations'][$cardLang]['seo']['og_title'] = $inOgTitle;
                    $post['translations'][$cardLang]['seo']['og_desc'] = $inOgDesc;
                }
                if ($inOgImg !== '' || $cardLang === 'tr') {
                    $post['seo']['og_image'] = $inOgImg !== '' ? $inOgImg : (string)($post['image'] ?? '');
                }
                $savedBlogPost = save_post($post, $targetKey);
            } else {
                if (str_starts_with($targetKey, 'service:')) {
                    $sSlug = substr($targetKey, 8);
                    foreach (($store['services'] ?? []) as $idx => $sv) {
                        if (($sv['slug'] ?? '') === $sSlug) {
                            $store['services'][$idx]['seo'] = is_array($sv['seo'] ?? null) ? $sv['seo'] : [];
                            $store['services'][$idx]['seo']['og_title'] = $inOgTitle;
                            $store['services'][$idx]['seo']['og_desc'] = $inOgDesc;
                            $store['services'][$idx]['seo']['og_image'] = $inOgImg;
                            break;
                        }
                    }
                } elseif (str_starts_with($targetKey, 'project:')) {
                    $pSlug = substr($targetKey, 8);
                    foreach (($store['projects'] ?? []) as $idx => $pj) {
                        if (($pj['slug'] ?? '') === $pSlug) {
                            $store['projects'][$idx]['seo'] = is_array($pj['seo'] ?? null) ? $pj['seo'] : [];
                            $store['projects'][$idx]['seo']['og_title'] = $inOgTitle;
                            $store['projects'][$idx]['seo']['og_desc'] = $inOgDesc;
                            $store['projects'][$idx]['seo']['og_image'] = $inOgImg;
                            break;
                        }
                    }
                } elseif (str_starts_with($targetKey, 'custom:')) {
                    $cId = substr($targetKey, 7);
                    foreach (($store['custom_pages'] ?? []) as $idx => $cp) {
                        if (($cp['id'] ?? '') === $cId || ($cp['slug'] ?? '') === $cId) {
                            $store['custom_pages'][$idx]['seo'] = is_array($cp['seo'] ?? null) ? $cp['seo'] : [];
                            $store['custom_pages'][$idx]['seo']['og_title'] = $inOgTitle;
                            $store['custom_pages'][$idx]['seo']['og_desc'] = $inOgDesc;
                            $store['custom_pages'][$idx]['seo']['og_image'] = $inOgImg;
                            break;
                        }
                    }
                } else {
                    $seoKey = str_starts_with($targetKey, 'legal:') ? substr($targetKey, 6) : ($targetKey ?: 'anasayfa');
                    $store['seo'] = is_array($store['seo'] ?? null) ? $store['seo'] : [];
                    $store['seo']['pages'] = is_array($store['seo']['pages'] ?? null) ? $store['seo']['pages'] : [];
                    $store['seo']['pages'][$seoKey] = is_array($store['seo']['pages'][$seoKey] ?? null) ? $store['seo']['pages'][$seoKey] : [];
                    $store['seo']['pages'][$seoKey]['og_title'] = $inOgTitle;
                    $store['seo']['pages'][$seoKey]['og_desc'] = $inOgDesc;
                    $store['seo']['pages'][$seoKey]['og_image'] = $inOgImg;
                }
                save_store_or_fail($store);
            }
        }

        $inspectStore = ($cardLang !== 'tr' && $kind !== 'blog')
            ? zeynart_localize_store($store, $cardLang)
            : $store;

        $draft = is_array($data['draft'] ?? null) ? $data['draft'] : [];
        $relUrl = '/';
        $baseTitle = 'Zeynart Web Bilişim';
        $baseDesc = (string)($inspectStore['seo']['default_description'] ?? '');
        $ogTitle = '';
        $ogDesc = '';
        $ogImgCustom = '';
        $fallbackImg = (string)($store['site']['og_image'] ?? '/images/opengraph-image.png');
        $catTag = 'ZEYNART';

        if ($kind === 'blog') {
            $rawPost = $targetKey !== '' ? get_post($targetKey) : null;
            $post = ($rawPost !== null && $cardLang !== 'tr')
                ? zeynart_ensure_post_translation($rawPost, $cardLang, $store)
                : $rawPost;
            $pSeo = is_array($post['seo'] ?? null) ? $post['seo'] : [];
            $useDraft = ($cardLang === 'tr') ? $draft : [];
            $slug = trim((string)($useDraft['slug'] ?? ($post['slug'] ?? $targetKey ?: 'yeni-yazi')));
            $relUrl = '/blog/' . $slug . '/';
            $rawT = trim((string)($useDraft['title'] ?? ($post['title'] ?? 'Blog Yazısı')));
            $baseTitle = trim((string)($useDraft['meta_title'] ?? ($pSeo['meta_title'] ?? ''))) ?: ($rawT . ' | ZEYNART');
            $plainBody = zeynart_extract_plain_excerpt((string)($useDraft['content_html'] ?? ($post['content_html'] ?? '')), 185);
            $baseDesc = trim((string)($useDraft['meta_desc'] ?? ($pSeo['meta_desc'] ?? '')))
                ?: (trim((string)($useDraft['excerpt'] ?? ($post['excerpt'] ?? ''))) ?: $plainBody);
            $ogTitle = array_key_exists('og_title', $useDraft) ? trim((string)$useDraft['og_title']) : trim((string)($pSeo['og_title'] ?? ''));
            $ogDesc = array_key_exists('og_desc', $useDraft) ? trim((string)$useDraft['og_desc']) : trim((string)($pSeo['og_desc'] ?? ''));
            $fallbackImg = trim((string)($draft['image'] ?? ($rawPost['image'] ?? ''))) ?: '/images/journal/web-planning.webp';
            $rawStoredOg = trim((string)(($rawPost['seo']['og_image'] ?? '') ?: ($pSeo['og_image'] ?? '')));
            $ogImgCustom = array_key_exists('og_image', $draft)
                ? trim((string)$draft['og_image'])
                : (($rawStoredOg !== '' && $rawStoredOg !== $fallbackImg) ? $rawStoredOg : '');
            $catTag = trim((string)($useDraft['category'] ?? ($post['category'] ?? 'BLOG')));
        } else {
            if (str_starts_with($targetKey, 'service:')) {
                $sSlug = substr($targetKey, 8);
                $sv = find_service($inspectStore, $sSlug) ?? [];
                $sSeo = is_array($sv['seo'] ?? null) ? $sv['seo'] : [];
                $relUrl = zeynart_page_url($inspectStore, 'service:' . ($sv['slug'] ?? $sSlug));
                $baseTitle = trim((string)($sSeo['meta_title'] ?? '')) ?: (($sv['title'] ?? t('Hizmetler', $cardLang)) . ' | ZEYNART');
                $baseDesc = trim((string)($sSeo['meta_desc'] ?? '')) ?: (string)($sv['description'] ?? ($sv['short'] ?? ''));
                $ogTitle = trim((string)($sSeo['og_title'] ?? ''));
                $ogDesc = trim((string)($sSeo['og_desc'] ?? ''));
                $ogImgCustom = trim((string)($sSeo['og_image'] ?? ''));
                $fallbackImg = trim((string)($sv['banner_image'] ?? '')) ?: '/images/banners/services.webp';
                $catTag = mb_strtoupper(t('Hizmetler', $cardLang), 'UTF-8');
            } elseif (str_starts_with($targetKey, 'project:')) {
                $pSlug = substr($targetKey, 8);
                $pj = find_project($inspectStore, $pSlug) ?? [];
                $pSeo = is_array($pj['seo'] ?? null) ? $pj['seo'] : [];
                $relUrl = zeynart_page_url($inspectStore, 'project:' . ($pj['slug'] ?? $pSlug));
                $baseTitle = trim((string)($pSeo['meta_title'] ?? '')) ?: (($pj['name'] ?? t('Projeler', $cardLang)) . ' | ZEYNART');
                $baseDesc = trim((string)($pSeo['meta_desc'] ?? '')) ?: (string)($pj['summary'] ?? '');
                $ogTitle = trim((string)($pSeo['og_title'] ?? ''));
                $ogDesc = trim((string)($pSeo['og_desc'] ?? ''));
                $ogImgCustom = trim((string)($pSeo['og_image'] ?? ''));
                $fallbackImg = trim((string)($pj['image'] ?? '')) ?: '/images/zeynart-brand-art.webp';
                $catTag = mb_strtoupper(t('Projeler', $cardLang), 'UTF-8');
            } elseif (str_starts_with($targetKey, 'custom:')) {
                $cId = substr($targetKey, 7);
                $cp = null;
                foreach (($inspectStore['custom_pages'] ?? []) as $cand) {
                    if (($cand['id'] ?? '') === $cId || ($cand['slug'] ?? '') === $cId) {
                        $cp = $cand;
                        break;
                    }
                }
                $cp = $cp ?? [];
                $cSeo = is_array($cp['seo'] ?? null) ? $cp['seo'] : [];
                $relUrl = '/' . ($cp['slug'] ?? 'sayfa') . '/';
                $baseTitle = trim((string)($cSeo['meta_title'] ?? '')) ?: (($cp['title'] ?? 'ZEYNART') . ' | ZEYNART');
                $baseDesc = trim((string)($cSeo['meta_desc'] ?? ''))
                    ?: (trim((string)($cp['excerpt'] ?? '')) ?: zeynart_extract_plain_excerpt((string)($cp['content_html'] ?? ''), 185));
                $ogTitle = trim((string)($cSeo['og_title'] ?? ''));
                $ogDesc = trim((string)($cSeo['og_desc'] ?? ''));
                $ogImgCustom = trim((string)($cSeo['og_image'] ?? ''));
                $fallbackImg = trim((string)($cp['banner_image'] ?? ''))
                    ?: (zeynart_extract_first_image((string)($cp['content_html'] ?? '')) ?: $fallbackImg);
                $catTag = 'ZEYNART';
            } else {
                $seoKey = str_starts_with($targetKey, 'legal:') ? substr($targetKey, 6) : ($targetKey ?: 'anasayfa');
                $sp = is_array($inspectStore['seo']['pages'][$seoKey] ?? null) ? $inspectStore['seo']['pages'][$seoKey] : [];
                $coreMap = [
                    'anasayfa' => ['url' => '/', 'title' => (string)($inspectStore['seo']['default_title'] ?? 'Zeynart Web Bilişim'), 'img' => $fallbackImg, 'cat' => 'ZEYNART'],
                    'hakkimizda' => ['url' => zeynart_page_url($inspectStore, 'hakkimizda'), 'title' => t('Hakkımızda', $cardLang) . ' | ZEYNART', 'img' => (string)($store['about']['banner_image'] ?? '/images/banners/about.webp'), 'cat' => mb_strtoupper(t('Kurumsal', $cardLang), 'UTF-8')],
                    'hizmetler' => ['url' => zeynart_page_url($inspectStore, 'hizmetler'), 'title' => t('Hizmetler', $cardLang) . ' | ZEYNART', 'img' => (string)($store['services_page']['banner_image'] ?? '/images/banners/services.webp'), 'cat' => mb_strtoupper(t('Hizmetler', $cardLang), 'UTF-8')],
                    'projeler' => ['url' => zeynart_page_url($inspectStore, 'projeler'), 'title' => t('Projeler', $cardLang) . ' | ZEYNART', 'img' => '/images/zeynart-brand-art.webp', 'cat' => mb_strtoupper(t('Projeler', $cardLang), 'UTF-8')],
                    'blog' => ['url' => zeynart_page_url($inspectStore, 'blog'), 'title' => 'Blog | ZEYNART', 'img' => '/images/sections/journal-banner.webp', 'cat' => 'BLOG'],
                    'iletisim' => ['url' => zeynart_page_url($inspectStore, 'iletisim'), 'title' => t('İletişim', $cardLang) . ' | ZEYNART', 'img' => (string)($store['contact']['banner_image'] ?? '/images/banners/contact.webp'), 'cat' => mb_strtoupper(t('İletişim', $cardLang), 'UTF-8')],
                    'kvkk' => ['url' => zeynart_page_url($inspectStore, 'kvkk'), 'title' => t('KVKK Aydınlatma Metni', $cardLang) . ' | ZEYNART', 'img' => (string)($store['legal_pages']['kvkk']['banner_image'] ?? '') ?: $fallbackImg, 'cat' => 'ZEYNART'],
                    'gizlilik' => ['url' => zeynart_page_url($inspectStore, 'gizlilik'), 'title' => t('Gizlilik Politikası', $cardLang) . ' | ZEYNART', 'img' => (string)($store['legal_pages']['gizlilik']['banner_image'] ?? '') ?: $fallbackImg, 'cat' => 'ZEYNART'],
                    'cerez-politikasi' => ['url' => zeynart_page_url($inspectStore, 'cerez-politikasi'), 'title' => t('Çerez Politikası', $cardLang) . ' | ZEYNART', 'img' => (string)($store['legal_pages']['cerez-politikasi']['banner_image'] ?? '') ?: $fallbackImg, 'cat' => 'ZEYNART'],
                    'cerezler' => ['url' => zeynart_page_url($inspectStore, 'cerez-politikasi'), 'title' => t('Çerez Politikası', $cardLang) . ' | ZEYNART', 'img' => (string)($store['legal_pages']['cerez-politikasi']['banner_image'] ?? '') ?: $fallbackImg, 'cat' => 'ZEYNART'],
                    'site_analiz' => ['url' => zeynart_page_url($inspectStore, 'site-analiz'), 'title' => t('Ücretsiz Site Hızı & SEO Ön Analiz Aracı | ZEYNART', $cardLang), 'img' => $fallbackImg, 'cat' => 'SEO & ANALİZ'],
                ];
                $mInfo = $coreMap[$seoKey] ?? $coreMap['anasayfa'];
                $relUrl = $mInfo['url'];
                $baseTitle = trim((string)($sp['meta_title'] ?? '')) ?: $mInfo['title'];
                $baseDesc = trim((string)($sp['meta_desc'] ?? '')) ?: (string)($inspectStore['seo']['default_description'] ?? '');
                $ogTitle = trim((string)($sp['og_title'] ?? ''));
                $ogDesc = trim((string)($sp['og_desc'] ?? ''));
                $ogImgCustom = trim((string)($sp['og_image'] ?? ''));
                $fallbackImg = $mInfo['img'];
                $catTag = $mInfo['cat'];
            }

            if ($cardLang === 'tr') {
                if (array_key_exists('og_title', $draft)) {
                    $ogTitle = trim((string)$draft['og_title']);
                }
                if (array_key_exists('og_desc', $draft)) {
                    $ogDesc = trim((string)$draft['og_desc']);
                }
            }
            if (array_key_exists('og_image', $draft)) {
                $ogImgCustom = trim((string)$draft['og_image']);
            }
        }

        $resolvedTitle = zeynart_clean_social_text($ogTitle !== '' ? $ogTitle : $baseTitle, 120);
        $resolvedDesc = zeynart_clean_social_text(
            $ogDesc !== '' ? $ogDesc : ($baseDesc !== '' ? $baseDesc : (string)($inspectStore['seo']['default_description'] ?? '')),
            200
        );
        $socialImg = zeynart_resolve_social_image($ogImgCustom !== '' ? $ogImgCustom : $fallbackImg, $store);
        $canonUrl = lang_canonical_url($relUrl, $store, $cardLang);
        $kb = (!empty($socialImg['physical_file']) && is_file($socialImg['physical_file']))
            ? round((float)filesize($socialImg['physical_file']) / 1024, 1)
            : 0.0;

        $cleanHeadlineForGraphic = preg_replace('/\s*\|\s*ZEYNART.*$/iu', '', $resolvedTitle) ?: $resolvedTitle;
        $postSourceForPack = ($kind === 'blog' && isset($post) && is_array($post))
            ? array_merge($post, array_filter([
                'title' => $cleanHeadlineForGraphic,
                'category' => $catTag,
                'excerpt' => $resolvedDesc,
                'content_html' => (string)($draft['content_html'] ?? ($post['content_html'] ?? '')),
            ], static fn($v): bool => $v !== ''))
            : [
                'title' => $cleanHeadlineForGraphic,
                'category' => $catTag,
                'excerpt' => $resolvedDesc,
                'content_html' => '',
            ];
        $captionsPack = zeynart_generate_social_captions_pack($store, $postSourceForPack, $canonUrl, $cardLang);

        $resp = [
            'message' => $isSave ? 'Sosyal medya paylaşım kartı (OpenGraph 1200×630 JPG) kaydedildi ve güncellendi.' : 'Sosyal medya paylaşım kartı hazır.',
            'social_card' => [
                'kind' => $kind,
                'target_key' => $targetKey,
                'lang' => $cardLang,
                'canonical_url' => $canonUrl,
                'relative_url' => lang_url($relUrl, $cardLang),
                'domain' => (string)(parse_url($canonUrl, PHP_URL_HOST) ?: 'zeynart.com'),
                'category' => $catTag,
                'raw_headline' => $cleanHeadlineForGraphic,
                'base_title' => zeynart_clean_social_text($baseTitle, 120),
                'base_desc' => zeynart_clean_social_text($baseDesc, 200),
                'og_title' => $ogTitle,
                'og_desc' => $ogDesc,
                'og_image' => $ogImgCustom,
                'fallback_image' => $fallbackImg,
                'resolved_title' => $resolvedTitle,
                'resolved_desc' => $resolvedDesc,
                'card_image_url' => $socialImg['url'],
                'card_image_preview' => $socialImg['relative_url'],
                'card_mime' => $socialImg['mime'],
                'card_width' => $socialImg['width'],
                'card_height' => $socialImg['height'],
                'card_kb' => $kb,
                'captions_pack' => $captionsPack,
            ],
        ];
        if ($isSave) {
            if ($kind === 'blog' && isset($savedBlogPost)) {
                $resp['post'] = $savedBlogPost;
            } else {
                $resp['store'] = $store;
            }
        }
        api_json(true, $resp);
    }

    case 'growth_tools_save': {
        if (isset($data['quick_contact']) && is_array($data['quick_contact'])) {
            $store['quick_contact'] = $data['quick_contact'];
            $store['quick_contact'] = get_quick_contact_state($store);
        }
        if (isset($data['site_audit']) && is_array($data['site_audit'])) {
            $store['site_audit'] = $data['site_audit'];
            $store['site_audit'] = get_site_audit_state($store);
            if (isset($store['home']) && is_array($store['home'])) {
                $store['home']['section_visibility'] = is_array($store['home']['section_visibility'] ?? null) ? $store['home']['section_visibility'] : [];
                $store['home']['section_visibility']['site_audit'] = !empty($store['site_audit']['show_on_home']) && !empty($store['site_audit']['enabled']);
            }
        }
        if (isset($data['quote_wizard']) && is_array($data['quote_wizard'])) {
            $store['quote_wizard'] = $data['quote_wizard'];
            $store['quote_wizard'] = zeynart_get_quote_wizard_config($store);
            if (isset($store['home']) && is_array($store['home'])) {
                $store['home']['section_visibility'] = is_array($store['home']['section_visibility'] ?? null) ? $store['home']['section_visibility'] : [];
                $store['home']['section_visibility']['quote_wizard'] = !empty($store['quote_wizard']['show_on_home']) && !empty($store['quote_wizard']['enabled']);
            }
        }
        if (isset($data['smart_popup']) && is_array($data['smart_popup'])) {
            $store['smart_popup'] = $data['smart_popup'];
            $store['smart_popup'] = zeynart_get_smart_popup_config($store);
        }
        if (isset($data['booking_settings']) && is_array($data['booking_settings'])) {
            $store['booking_settings'] = $data['booking_settings'];
            $store['booking_settings'] = zeynart_get_booking_settings($store);
        }
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Büyüme, Teklif Sihirbazı, Akıllı Pop-up, Keşif Görüşmesi ve Hızlı İletişim ayarları kaydedildi.',
            'quick_contact' => get_quick_contact_state($store),
            'site_audit' => get_site_audit_state($store),
            'quote_wizard' => zeynart_get_quote_wizard_config($store),
            'smart_popup' => zeynart_get_smart_popup_config($store),
            'booking_settings' => zeynart_get_booking_settings($store),
            'home' => $store['home'] ?? [],
        ]);
    }

    case 'live_studio_init': {
        $postSlug = trim((string)($data['post_slug'] ?? ''));
        $loadedPost = ($postSlug !== '') ? get_post($postSlug) : null;
        $recentMedia = scan_media_files('all', '', 1, 36, 'image', 'newest');
        api_json(true, [
            'store' => build_safe_store_for_client($store),
            'post' => $loadedPost,
            'media' => $recentMedia['items'] ?? [],
        ]);
    }

    case 'live_render_sections': {
        $GLOBALS['zeynart_is_admin_view'] = true;
        $rawSecs = is_array($data['sections'] ?? null) ? array_values($data['sections']) : [];
        $rendered = render_builder_sections($rawSecs, $store);
        api_json(true, [
            'html' => $rendered,
        ]);
    }

    case 'backup_download': {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="zeynart-yedek-' . date('Ymd-His') . '.json"');
        echo json_encode($store, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    case 'appearance_save': {
        if (isset($data['theme_brand']) && is_array($data['theme_brand'])) {
            $store['theme_brand'] = $data['theme_brand'];
            $store['theme_brand'] = zeynart_get_theme_brand($store);
        }
        if (isset($data['promo_bar']) && is_array($data['promo_bar'])) {
            $store['promo_bar'] = $data['promo_bar'];
            $store['promo_bar'] = zeynart_get_promo_bar($store);
        }
        if (isset($data['maintenance']) && is_array($data['maintenance'])) {
            $store['maintenance'] = $data['maintenance'];
            $store['maintenance'] = zeynart_get_maintenance_state($store);
        }
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Tema renk sihirbazı, duyuru çubuğu ve bakım modu ayarları kaydedildi.',
            'theme_brand' => zeynart_get_theme_brand($store),
            'promo_bar' => zeynart_get_promo_bar($store),
            'maintenance' => zeynart_get_maintenance_state($store),
            'admin_theme_css' => zeynart_compile_admin_theme_css($store),
            'summary' => compute_dashboard_summary($store),
            'revisions' => zeynart_list_revisions(),
        ]);
    }

    case '404_logs_get': {
        api_json(true, [
            'logs_404' => zeynart_get_404_logs(),
            'redirect_hits' => zeynart_get_redirect_hits(),
        ]);
    }

    case '404_convert_redirect': {
        $fromRaw = trim((string)($data['from'] ?? ''));
        $toRaw = trim((string)($data['to'] ?? '/'));
        $code = in_array((int)($data['code'] ?? 301), [301, 302], true) ? (int)$data['code'] : 301;
        if ($fromRaw === '' || $fromRaw === '/') {
            api_json(false, ['error' => 'Yönlendirilecek geçerli bir kırık adres belirtilmedi.'], 422);
        }
        $fromNorm = '/' . trim($fromRaw, '/');
        if (function_exists('zeynart_uri_is_live_published_post') && zeynart_uri_is_live_published_post($fromNorm, $store)) {
            zeynart_remove_404_entry($fromNorm);
            $safeStore = build_safe_store_for_client($store);
            api_json(true, [
                'message' => 'Bu adres (' . $fromNorm . ') zaten yayında olan canlı bir blog makalesine ait olduğu için 404 listesinden temizlendi ve canlı yazı korundu.',
                'redirects' => $safeStore['redirects'] ?? [],
                'logs_404' => zeynart_get_404_logs(),
                'summary' => compute_dashboard_summary($store),
            ]);
        }
        $toNorm = $toRaw === '/' ? '/' : (str_starts_with($toRaw, 'http') ? $toRaw : ('/' . trim($toRaw, '/') . '/'));

        $redirects = is_array($store['redirects'] ?? null) ? array_values($store['redirects']) : [];
        $updatedExisting = false;
        foreach ($redirects as $idx => $r) {
            if (is_array($r) && strcasecmp('/' . trim((string)($r['from'] ?? ''), '/'), $fromNorm) === 0) {
                $redirects[$idx]['to'] = $toNorm;
                $redirects[$idx]['code'] = $code;
                $updatedExisting = true;
                break;
            }
        }
        if (!$updatedExisting) {
            array_unshift($redirects, [
                'id' => 'red-' . substr(bin2hex(random_bytes(4)), 0, 8),
                'from' => $fromNorm,
                'to' => $toNorm,
                'code' => $code,
                'hits' => 0,
            ]);
        }
        $store['redirects'] = $redirects;
        zeynart_remove_404_entry($fromNorm);
        save_store_or_fail($store);
        $safeStore = build_safe_store_for_client($store);
        api_json(true, [
            'message' => 'Kırık bağlantı (' . $fromNorm . ') başarıyla ' . $toNorm . ' adresine ' . $code . ' yönlendirildi.',
            'redirects' => $safeStore['redirects'] ?? [],
            'logs_404' => zeynart_get_404_logs(),
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case '404_delete_item': {
        $path = trim((string)($data['path'] ?? ''));
        if ($path !== '') {
            zeynart_remove_404_entry($path);
        }
        api_json(true, [
            'message' => '404 kaydı listeden kaldırıldı.',
            'logs_404' => zeynart_get_404_logs(),
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case '404_clear_all': {
        zeynart_clear_404_logs();
        zeynart_audit_log('404_clear', '404 kırık link kayıt listesi temizlendi.');
        api_json(true, [
            'message' => 'Tüm 404 kırık bağlantı kayıtları temizlendi.',
            'logs_404' => [],
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'ai_write_full_post': {
        $topic = trim((string)($data['topic'] ?? ''));
        if ($topic === '') {
            api_json(false, ['error' => 'Lütfen makale üretilecek konuyu veya anahtar kelimeyi girin.'], 422);
        }
        $composed = zeynart_ai_compose_blog_post($data, $store);
        $image = trim((string)($data['image'] ?? '/images/journal/web-planning.webp')) ?: '/images/journal/web-planning.webp';
        $seoCheck = analyze_seo_score([
            'title' => $composed['title'],
            'meta_title' => $composed['seo']['meta_title'],
            'excerpt' => $composed['excerpt'],
            'meta_desc' => $composed['seo']['meta_desc'],
            'focus_keyword' => $composed['seo']['focus_keyword'],
            'slug' => $composed['slug'],
            'content_html' => $composed['content_html'],
            'ai_summary' => $composed['seo']['ai_summary'],
            'image' => $image,
            'faqs' => $composed['faqs'],
        ]);
        zeynart_audit_log('ai_blog_write', 'Yapay Zeka Blog & SSS Yazarı ile makale üretildi: ' . $composed['title']);
        api_json(true, [
            'message' => 'Makale, alt başlıklar, SEO meta verileri ve ' . count($composed['faqs']) . ' adet SSS maddesi başarıyla üretildi!',
            'composed' => $composed,
            'seo_analysis' => $seoCheck,
        ]);
    }

    case 'security_overview': {
        $fwCfg = zeynart_get_firewall_config($store);
        $fwCfg['admin_slug'] = zeynart_get_admin_slug($store);
        api_json(true, [
            'firewall' => $fwCfg,
            'admin_slug' => zeynart_get_admin_slug($store),
            'security_logs' => zeynart_get_audit_logs(),
            'locked_ips' => zeynart_get_locked_ips(),
            'security_audit_20' => zeynart_run_security_20_audit($store),
            'current_ip' => get_client_ip(),
        ]);
    }

    case 'security_20_audit': {
        api_json(true, [
            'message' => '20/20 kurumsal güvenlik ve CI/CD kalkan denetimi tamamlandı.',
            'security_audit_20' => zeynart_run_security_20_audit($store),
        ]);
    }

    case 'security_clean_root_leftovers': {
        $auditBefore = zeynart_run_security_20_audit($store);
        $removed = [];
        foreach (($auditBefore['cleanable_archives'] ?? []) as $fname) {
            $safeName = basename((string)$fname);
            if ($safeName === '' || in_array($safeName, ['.htaccess', 'index.php', 'icon.png', 'favicon.ico'], true)) {
                continue;
            }
            $abs = ROOT_DIR . '/' . $safeName;
            if (is_file($abs) && @unlink($abs)) {
                $removed[] = $safeName;
            }
        }
        if ($removed !== []) {
            zeynart_audit_log(
                'root_cleanup',
                'Ana dizindeki kurulum/arşiv kalıntıları temizlendi: ' . implode(', ', $removed),
                (string)($_SESSION['zeynart_admin_user'] ?? 'admin'),
                'ok'
            );
        }
        api_json(true, [
            'message' => $removed !== []
                ? ('Ana dizinden ' . count($removed) . ' kurulum kalıntısı silindi: ' . implode(', ', $removed))
                : 'Silinecek kurulum kalıntısı bulunamadı.',
            'removed' => $removed,
            'security_audit_20' => zeynart_run_security_20_audit($store),
        ]);
    }

    case 'firewall_save': {
        $fwIn = is_array($data['firewall'] ?? null) ? $data['firewall'] : [];
        $prevSlug = zeynart_get_admin_slug($store);
        $rawAdminSlug = null;
        if (array_key_exists('admin_slug', $data)) {
            $rawAdminSlug = (string)$data['admin_slug'];
        } elseif (array_key_exists('admin_slug', $fwIn)) {
            $rawAdminSlug = (string)$fwIn['admin_slug'];
        }
        $nextSlug = $prevSlug;
        if ($rawAdminSlug !== null) {
            $slugVal = zeynart_validate_admin_slug($rawAdminSlug, $store);
            if (empty($slugVal['ok'])) {
                api_json(false, ['error' => (string)($slugVal['error'] ?? 'Geçersiz yönetim paneli uzantısı.')], 422);
            }
            $nextSlug = (string)$slugVal['slug'];
            $store['security'] = is_array($store['security'] ?? null) ? $store['security'] : [];
            $store['security']['admin_slug'] = $nextSlug;
        }
        unset($fwIn['admin_slug']);
        $store['firewall'] = $fwIn;
        $normFw = zeynart_get_firewall_config($store);
        $curIp = get_client_ip();
        if (!empty($normFw['enforce_ip_whitelist']) && !in_array($curIp, $normFw['ip_whitelist'], true)) {
            $normFw['ip_whitelist'][] = $curIp;
        }
        $store['firewall'] = $normFw;
        save_store_or_fail($store);
        $normFw['admin_slug'] = $nextSlug;
        $slugChanged = ($nextSlug !== $prevSlug);
        api_json(true, [
            'message' => $slugChanged
                ? ('Güvenlik duvarı ve özel yönetim adresi güncellendi: /' . $nextSlug . '/')
                : 'Güvenlik duvarı ve IP koruma kuralları güncellendi.',
            'firewall' => $normFw,
            'admin_slug' => $nextSlug,
            'slug_changed' => $slugChanged,
            'new_admin_url' => zeynart_admin_url('/', $store),
            'security_logs' => array_slice(zeynart_get_audit_logs(), 0, 80),
            'locked_ips' => zeynart_get_locked_ips(),
        ]);
    }

    case 'security_unlock_ip': {
        $ip = trim((string)($data['ip'] ?? '*'));
        zeynart_unlock_ip($ip);
        zeynart_audit_log('ip_unlock', 'IP kilidi kaldırıldı: ' . $ip);
        api_json(true, [
            'message' => 'IP kilidi başarıyla kaldırıldı.',
            'locked_ips' => zeynart_get_locked_ips(),
            'security_logs' => array_slice(zeynart_get_audit_logs(), 0, 80),
        ]);
    }

    case 'security_clear_logs': {
        zeynart_clear_audit_logs();
        zeynart_audit_log('logs_cleared', 'Güvenlik ve işlem kayıtları temizlendi.', (string)($_SESSION['zeynart_admin_user'] ?? 'admin'));
        api_json(true, [
            'message' => 'Güvenlik denetim kayıtları temizlendi.',
            'security_logs' => zeynart_get_audit_logs(),
        ]);
    }

    case 'revisions_list': {
        api_json(true, [
            'revisions' => zeynart_list_revisions(),
        ]);
    }

    case 'revision_create': {
        $lbl = trim((string)($data['label'] ?? 'Manuel Yedek Noktası'));
        if ($lbl === '') {
            $lbl = 'Manuel Yedek Noktası';
        }
        $snap = zeynart_capture_store_revision($store, $lbl, true);
        if ($snap === null) {
            api_json(false, ['error' => 'Anlık yedek noktası oluşturulamadı.'], 500);
        }
        zeynart_audit_log('revision_create', 'Manuel Zaman Makinesi yedek noktası oluşturuldu: ' . $lbl);
        api_json(true, [
            'message' => 'Anlık yedek noktası başarıyla oluşturuldu.',
            'snapshot' => $snap,
            'revisions' => zeynart_list_revisions(),
        ]);
    }

    case 'revision_restore': {
        $revId = trim((string)($data['id'] ?? ''));
        $restoredStore = zeynart_restore_revision($revId, $store);
        if ($restoredStore === null) {
            api_json(false, ['error' => 'Seçilen sürüm bulunamadı veya geri yüklenemedi.'], 404);
        }
        sync_static_ai_manifests($restoredStore);
        api_json(true, [
            'message' => 'Seçilen sürüme başarıyla geri dönüldü! Müşteri mesajlarınız ve oturumunuz korundu.',
            'store' => build_safe_store_for_client($restoredStore),
            'summary' => compute_dashboard_summary($restoredStore),
            'revisions' => zeynart_list_revisions(),
            'admin_theme_css' => zeynart_compile_admin_theme_css($restoredStore),
        ]);
    }

    case 'revision_delete': {
        $revId = trim((string)($data['id'] ?? ''));
        zeynart_delete_revision($revId);
        api_json(true, [
            'message' => 'Sürüm kaydı silindi.',
            'revisions' => zeynart_list_revisions(),
        ]);
    }

    case 'backup_restore_json': {
        $rawBackup = (string)($data['backup_json'] ?? '');
        $decoded = $rawBackup !== '' ? json_decode($rawBackup, true) : null;
        if (!is_array($decoded) || !isset($decoded['site']) || !is_array($decoded['site'])) {
            api_json(false, ['error' => 'Geçersiz yedek dosyası formatı. Lütfen geçerli bir sistem .json yedeği seçin.'], 422);
        }
        zeynart_capture_store_revision($store, 'JSON Yedek İçe Aktarma Öncesi Güvenlik Noktası', true);
        if (!empty($store['admin']['password_hash'])) {
            $decoded['admin'] = is_array($decoded['admin'] ?? null) ? $decoded['admin'] : [];
            $decoded['admin']['password_hash'] = $store['admin']['password_hash'];
        }
        if (!empty($store['admin_users']) && is_array($store['admin_users'])) {
            $decoded['admin_users'] = $store['admin_users'];
        }
        if (!empty($store['messages']) && empty($decoded['messages'])) {
            $decoded['messages'] = $store['messages'];
        }
        $GLOBALS['zeynart_skip_revision_capture'] = true;
        save_store_or_fail($decoded);
        unset($GLOBALS['zeynart_skip_revision_capture']);
        $fresh = load_store(true);
        sync_static_ai_manifests($fresh);
        zeynart_audit_log('backup_import', 'JSON sistem yedeği başarıyla içe aktarıldı.');
        api_json(true, [
            'message' => 'JSON sistem yedeği başarıyla geri yüklendi!',
            'store' => build_safe_store_for_client($fresh),
            'summary' => compute_dashboard_summary($fresh),
            'revisions' => zeynart_list_revisions(),
            'admin_theme_css' => zeynart_compile_admin_theme_css($fresh),
        ]);
    }

    case 'users_list': {
        api_json(true, [
            'users' => zeynart_normalize_admin_users($store, true),
        ]);
    }

    case 'user_save': {
        $inId = trim((string)($data['id'] ?? ''));
        $uname = trim((string)($data['username'] ?? ''));
        $display = trim((string)($data['display_name'] ?? ''));
        $rawRole = (string)($data['role'] ?? 'editor');
        $role = in_array($rawRole, ['super_admin', 'editor'], true) ? $rawRole : 'editor';
        $active = ($data['active'] ?? true) !== false;
        $rawPass = (string)($data['password'] ?? '');

        if ($uname === '' || strlen($uname) < 3 || !preg_match('/^[a-zA-Z0-9._\-]+$/', $uname)) {
            api_json(false, ['error' => 'Kullanıcı adı en az 3 karakter olmalı ve yalnızca harf, rakam, nokta veya tire içermelidir.'], 422);
        }

        $primaryUsername = trim((string)($store['admin']['username'] ?? 'admin')) ?: 'admin';
        if ($inId === 'usr-primary') {
            $store['admin']['display_name'] = $display !== '' ? $display : $primaryUsername;
            if ($rawPass !== '') {
                if (strlen($rawPass) < 8) {
                    api_json(false, ['error' => 'Parola en az 8 karakter olmalıdır.'], 422);
                }
                $store['admin']['password_hash'] = password_hash($rawPass, PASSWORD_BCRYPT, ['cost' => 12]);
            }
            save_store_or_fail($store);
            api_json(true, [
                'message' => 'Ana yönetici hesabı güncellendi.',
                'users' => zeynart_normalize_admin_users($store, true),
            ]);
        }

        if (strcasecmp($uname, $primaryUsername) === 0) {
            api_json(false, ['error' => 'Bu kullanıcı adı ana yönetici hesabına aittir. Lütfen farklı bir kullanıcı adı seçin.'], 422);
        }

        $users = is_array($store['admin_users'] ?? null) ? array_values($store['admin_users']) : [];
        $foundIdx = null;
        foreach ($users as $idx => $uRow) {
            if (!is_array($uRow)) {
                continue;
            }
            if ($inId !== '' && (($uRow['id'] ?? '') === $inId)) {
                $foundIdx = $idx;
                continue;
            }
            if (strcasecmp((string)($uRow['username'] ?? ''), $uname) === 0) {
                api_json(false, ['error' => '"' . $uname . '" kullanıcı adı zaten kayıtlı.'], 422);
            }
        }

        if ($foundIdx === null) {
            if (strlen($rawPass) < 8) {
                api_json(false, ['error' => 'Yeni kullanıcı için en az 8 karakterli güçlü bir parola belirleyin.'], 422);
            }
            $users[] = [
                'id' => 'usr-' . substr(bin2hex(random_bytes(4)), 0, 8),
                'username' => $uname,
                'display_name' => $display !== '' ? $display : $uname,
                'role' => $role,
                'active' => $active,
                'password_hash' => password_hash($rawPass, PASSWORD_BCRYPT, ['cost' => 12]),
                'last_login' => '',
                'created_at' => date('c'),
            ];
            $msg = 'Yeni ' . ($role === 'super_admin' ? 'Süper Admin' : 'Müşteri / Editör') . ' hesabı oluşturuldu: @' . $uname;
        } else {
            $users[$foundIdx]['username'] = $uname;
            $users[$foundIdx]['display_name'] = $display !== '' ? $display : $uname;
            $users[$foundIdx]['role'] = $role;
            $users[$foundIdx]['active'] = $active;
            if ($rawPass !== '') {
                if (strlen($rawPass) < 8) {
                    api_json(false, ['error' => 'Yeni parola en az 8 karakter olmalıdır.'], 422);
                }
                $users[$foundIdx]['password_hash'] = password_hash($rawPass, PASSWORD_BCRYPT, ['cost' => 12]);
            }
            $msg = '@' . $uname . ' kullanıcı hesabı ve rol yetkisi güncellendi.';
        }

        $store['admin_users'] = $users;
        save_store_or_fail($store);
        api_json(true, [
            'message' => $msg,
            'users' => zeynart_normalize_admin_users($store, true),
        ]);
    }

    case 'user_toggle_active': {
        $targetId = trim((string)($data['id'] ?? ''));
        if ($targetId === '' || $targetId === 'usr-primary') {
            api_json(false, ['error' => 'Ana yönetici hesabı pasife alınamaz.'], 422);
        }
        $curSessionUser = (string)($_SESSION['zeynart_admin_user'] ?? '');
        $users = is_array($store['admin_users'] ?? null) ? array_values($store['admin_users']) : [];
        $updated = false;
        foreach ($users as $idx => $uRow) {
            if (is_array($uRow) && (($uRow['id'] ?? '') === $targetId)) {
                if (strcasecmp((string)($uRow['username'] ?? ''), $curSessionUser) === 0) {
                    api_json(false, ['error' => 'Aktif oturumunuzdaki hesabı pasife alamazsınız.'], 422);
                }
                $users[$idx]['active'] = !(($uRow['active'] ?? true) !== false);
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            api_json(false, ['error' => 'Kullanıcı kaydı bulunamadı.'], 404);
        }
        $store['admin_users'] = $users;
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Kullanıcı aktiflik durumu güncellendi.',
            'users' => zeynart_normalize_admin_users($store, true),
        ]);
    }

    case 'user_delete': {
        $targetId = trim((string)($data['id'] ?? ''));
        if ($targetId === '' || $targetId === 'usr-primary') {
            api_json(false, ['error' => 'Ana Süper Admin hesabı silinemez.'], 422);
        }
        $curSessionUser = (string)($_SESSION['zeynart_admin_user'] ?? '');
        $users = is_array($store['admin_users'] ?? null) ? array_values($store['admin_users']) : [];
        $filtered = [];
        $deletedName = '';
        foreach ($users as $uRow) {
            if (!is_array($uRow)) {
                continue;
            }
            if (($uRow['id'] ?? '') === $targetId) {
                if (strcasecmp((string)($uRow['username'] ?? ''), $curSessionUser) === 0) {
                    api_json(false, ['error' => 'Aktif oturumunuzdaki hesabı silemezsiniz.'], 422);
                }
                $deletedName = (string)($uRow['username'] ?? '');
                continue;
            }
            $filtered[] = $uRow;
        }
        if ($deletedName === '') {
            api_json(false, ['error' => 'Silinecek kullanıcı bulunamadı.'], 404);
        }
        $store['admin_users'] = $filtered;
        save_store_or_fail($store);
        api_json(true, [
            'message' => '@' . $deletedName . ' kullanıcı hesabı silindi.',
            'users' => zeynart_normalize_admin_users($store, true),
        ]);
    }

    case 'media_stamp_save': {
        if (isset($data['media_stamp']) && is_array($data['media_stamp'])) {
            $store['media_stamp'] = $data['media_stamp'];
        }
        $store['media_stamp'] = zeynart_get_media_stamp_config($store);
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Otomatik görsel marka / domain damga ayarları kaydedildi.',
            'media_stamp' => $store['media_stamp'],
        ]);
    }

    case 'media_apply_stamp': {
        $relUrl = '/' . ltrim(str_replace('\\', '/', trim(strtok((string)($data['url'] ?? ''), '?') ?: '')), '/');
        if (!str_starts_with($relUrl, '/uploads/') || str_contains($relUrl, '..') || !is_file(ROOT_DIR . $relUrl)) {
            api_json(false, ['error' => 'Yalnızca /uploads/ arşivindeki görsellere doğrudan damga uygulanabilir.'], 422);
        }
        if (isset($data['media_stamp']) && is_array($data['media_stamp'])) {
            $store['media_stamp'] = $data['media_stamp'];
            $store['media_stamp'] = zeynart_get_media_stamp_config($store);
        }
        $stampCfg = zeynart_get_media_stamp_config($store);
        $stampCfg['enabled'] = true;
        $applied = zeynart_apply_gd_text_stamp(ROOT_DIR . $relUrl, $stampCfg, $store);
        if (!$applied) {
            api_json(false, ['error' => 'Bu dosya formatına otomatik yazı damgası uygulanamadı (JPG, PNG veya WebP olmalıdır).'], 422);
        }
        clearstatcache(true, ROOT_DIR . $relUrl);
        zeynart_invalidate_media_cache();
        $stampMtime = max(time(), (int)(@filemtime(ROOT_DIR . $relUrl) ?: time()));
        $stampSize = (int)(@filesize(ROOT_DIR . $relUrl) ?: 0);
        $store['media_meta'] = is_array($store['media_meta'] ?? null) ? $store['media_meta'] : [];
        $store['media_meta'][$relUrl] = array_merge($store['media_meta'][$relUrl] ?? [], ['updated_at' => date('c')]);
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Görsel üzerine marka / domain imzası başarıyla işlendi.',
            'url' => $relUrl,
            'mtime' => $stampMtime,
            'size' => $stampSize,
            'cache_busted_url' => $relUrl . '?v=' . $stampMtime,
            'media_stamp' => $store['media_stamp'],
        ]);
    }

    case 'media_canvas_save': {
        $sourceUrl = trim((string)($data['source_url'] ?? ''));
        $dataUri = (string)($data['data_uri'] ?? '');
        $saveMode = ((string)($data['save_mode'] ?? 'copy')) === 'overwrite' ? 'overwrite' : 'copy';
        $altText = trim((string)($data['alt'] ?? ''));

        $res = zeynart_save_canvas_image($sourceUrl, $dataUri, $saveMode, $altText, $store);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Görsel kaydedilemedi.')], 422);
        }
        zeynart_invalidate_media_cache();
        save_store_or_fail($store);
        api_json(true, [
            'message' => !empty($res['overwritten'])
                ? 'Görsel üzerindeki yazı & marka tasarımı mevcut dosyanın üzerine başarıyla kaydedildi!'
                : 'Yeni markalı görsel kopyası (' . $res['name'] . ') /uploads/ arşivine kaydedildi!',
            'item' => $res,
            'media_meta' => $store['media_meta'] ?? [],
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'seo_doctor_scan': {
        api_json(true, [
            'report' => zeynart_run_seo_doctor_scan($store),
        ]);
    }

    case 'seo_doctor_autofix': {
        $stats = zeynart_run_seo_doctor_autofix($store);
        save_store_or_fail($store);
        sync_static_ai_manifests($store);
        api_json(true, [
            'message' => 'SEO Doktoru tamamlandı: ' . (int)$stats['fixed_meta'] . ' sayfa meta alanı, ' . (int)$stats['fixed_media_alts'] . ' görsel alt etiketi ve ' . (int)$stats['fixed_blog'] . ' blog özeti otomatik onarıldı!',
            'stats' => $stats,
            'report' => zeynart_run_seo_doctor_scan($store),
            'store' => build_safe_store_for_client($store),
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'news_bot_status': {
        $isSuper = ($activeRole === 'super_admin');
        $nbCfg = zeynart_get_news_autopilot_config($store);
        $canonBase = rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/');
        api_json(true, [
            'news_autopilot' => build_safe_store_for_client($store)['news_autopilot'],
            'queue' => zeynart_list_news_bot_queue(),
            'state' => zeynart_load_news_bot_state(),
            'cron_url' => $isSuper ? ($canonBase . '/api/cron/haber-otopilot?key=' . $nbCfg['cron_secret']) : '',
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'news_bot_save': {
        $existingNb = zeynart_get_news_autopilot_config($store);
        $in = is_array($data['news_autopilot'] ?? null) ? $data['news_autopilot'] : [];
        if ($activeRole !== 'super_admin') {
            $in['openai_api_key'] = $existingNb['openai_api_key'];
            $in['gemini_api_key'] = $existingNb['gemini_api_key'];
            $in['cron_secret'] = $existingNb['cron_secret'];
        } else {
            if (!array_key_exists('cron_secret', $in) || trim((string)$in['cron_secret']) === '') {
                $in['cron_secret'] = $existingNb['cron_secret'];
            }
        }
        $mergedStore = $store;
        $mergedStore['news_autopilot'] = array_merge($existingNb, $in);
        $store['news_autopilot'] = zeynart_get_news_autopilot_config($mergedStore);
        save_store_or_fail($store);

        $canonBase = rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/');
        api_json(true, [
            'message' => 'Google News Otopilot & Hibrit Filigran ayarları kaydedildi.',
            'news_autopilot' => build_safe_store_for_client($store)['news_autopilot'],
            'cron_url' => ($activeRole === 'super_admin') ? ($canonBase . '/api/cron/haber-otopilot?key=' . $store['news_autopilot']['cron_secret']) : '',
        ]);
    }

    case 'news_bot_fetch_now': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        @set_time_limit(95);
        $count = max(1, min(3, (int)($data['count'] ?? 1)));
        $topicQuery = trim((string)($data['topic_query'] ?? ''));
        $topicCategory = trim((string)($data['topic_category'] ?? 'TEKNOLOJİ'));
        $overrideFeedXml = ($activeRole === 'super_admin') ? (string)($data['override_feed_xml'] ?? '') : '';

        $res = zeynart_run_news_autopilot($store, [
            'force' => true,
            'count' => $count,
            'topic_query' => $topicQuery,
            'topic_category' => $topicCategory,
            'override_feed_xml' => $overrideFeedXml,
        ]);

        if (empty($res['ok'])) {
            api_json(false, [
                'error' => (string)($res['error'] ?? 'Haber çekilemedi.'),
                'queue' => zeynart_list_news_bot_queue(),
                'state' => zeynart_load_news_bot_state(),
            ], 422);
        }

        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('news_bot_fetch', 'Google News Otopilot çalıştırıldı (' . (int)($res['created_count'] ?? 0) . ' haber hazırlandı).', $actor, 'ok');

        api_json(true, [
            'message' => (string)($res['message'] ?? 'Haber başarıyla çekildi ve hazırlandı.'),
            'created_count' => (int)($res['created_count'] ?? 0),
            'created_posts' => $res['created_posts'] ?? [],
            'queue' => $res['queue'] ?? zeynart_list_news_bot_queue(),
            'state' => $res['state'] ?? zeynart_load_news_bot_state(),
            'summary' => compute_dashboard_summary(load_store(true)),
        ]);
    }

    case 'news_bot_approve': {
        $slugs = [];
        if (!empty($data['slugs']) && is_array($data['slugs'])) {
            foreach ($data['slugs'] as $sVal) {
                $sClean = trim((string)$sVal);
                if ($sClean !== '') {
                    $slugs[] = $sClean;
                }
            }
        } elseif (!empty($data['slug'])) {
            $slugs[] = trim((string)$data['slug']);
        }
        if ($slugs === []) {
            api_json(false, ['error' => 'Onaylanacak haber taslağı belirtilmedi.'], 422);
        }

        $approvedCount = 0;
        $lastMessage = '';
        foreach ($slugs as $oneSlug) {
            $res = zeynart_approve_news_draft($oneSlug, $store);
            if (!empty($res['ok'])) {
                $approvedCount++;
                $lastMessage = (string)($res['message'] ?? '');
            }
        }
        if ($approvedCount === 0) {
            api_json(false, ['error' => 'Onaylanacak geçerli bir haber taslağı bulunamadı.'], 404);
        }

        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('news_bot_approve', $approvedCount . ' adet otomatik haber taslağı onaylanıp yayına alındı.', $actor, 'ok');

        api_json(true, [
            'message' => $approvedCount > 1
                ? "✓ {$approvedCount} haber taslağı onaylandı, çok dilli (EN · DE · RU) olarak yayına alındı ve arama motorlarına bildirildi!"
                : $lastMessage,
            'approved_count' => $approvedCount,
            'queue' => zeynart_list_news_bot_queue(),
            'state' => zeynart_load_news_bot_state(),
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'news_bot_reject': {
        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') {
            api_json(false, ['error' => 'Reddedilecek haber taslağı belirtilmedi.'], 422);
        }
        $res = zeynart_reject_news_draft($slug);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Haber taslağı silinemedi.')], 404);
        }
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('news_bot_reject', 'Haber taslağı reddedildi ve kara listeye alındı (' . $slug . ').', $actor, 'info');

        api_json(true, [
            'message' => (string)$res['message'],
            'queue' => $res['queue'],
            'state' => $res['state'],
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'news_bot_watermark_preview': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $wmCfg = zeynart_get_news_autopilot_config($store)['watermark'];
        $wmOverride = [
            'cover_mode' => in_array((string)($_GET['cover_mode'] ?? $wmCfg['cover_mode']), ['hybrid', 'generated_only'], true)
                ? (string)($_GET['cover_mode'] ?? $wmCfg['cover_mode'])
                : 'hybrid',
            'enabled' => isset($_GET['enabled']) ? ($_GET['enabled'] === '1' || $_GET['enabled'] === 'true') : $wmCfg['enabled'],
            'show_logo' => isset($_GET['show_logo']) ? ($_GET['show_logo'] === '1' || $_GET['show_logo'] === 'true') : $wmCfg['show_logo'],
            'show_category_badge' => isset($_GET['show_category_badge']) ? ($_GET['show_category_badge'] === '1' || $_GET['show_category_badge'] === 'true') : $wmCfg['show_category_badge'],
            'domain_text' => isset($_GET['domain_text']) ? mb_substr(trim((string)$_GET['domain_text']), 0, 60, 'UTF-8') : $wmCfg['domain_text'],
            'position' => in_array((string)($_GET['position'] ?? $wmCfg['position']), ['bottom-right', 'bottom-left', 'top-right'], true)
                ? (string)($_GET['position'] ?? $wmCfg['position'])
                : 'bottom-right',
            'opacity' => isset($_GET['opacity']) ? max(40, min(100, (int)$_GET['opacity'])) : $wmCfg['opacity'],
        ];
        $samplePhoto = ($wmOverride['cover_mode'] === 'hybrid' && is_file(ROOT_DIR . '/images/journal/ai-workflow.webp'))
            ? '/images/journal/ai-workflow.webp'
            : '';
        $sampleCat = trim((string)($_GET['category'] ?? 'YAPAY ZEKA & WEB')) ?: 'YAPAY ZEKA & WEB';
        $sampleHeadline = 'Üretken Yapay Zeka ve Modern Web Mimarilerinde 2026 Dönüşümü';

        $gen = zeynart_generate_news_cover_webp($store, $sampleHeadline, $sampleCat, $samplePhoto, $wmOverride, true);
        if (empty($gen['ok']) || empty($gen['bytes'])) {
            http_response_code(500);
            exit;
        }
        header('Content-Type: ' . ($gen['mime'] ?? 'image/webp'));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $gen['bytes'];
        exit;
    }

    /* ========================================================================
     * AGENCY POWERHOUSE ACTIONS (IG/STORY STUDIO, UPTIME TOWER, INTERLINKS, VCARDS)
     * ======================================================================== */

    case 'social_graphic_render': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $format = ((string)($_GET['format'] ?? ($data['format'] ?? 'square'))) === 'story' ? 'story' : 'square';
        $template = (string)($_GET['template'] ?? ($data['template'] ?? 'editorial_glass'));
        $slug = trim((string)($_GET['slug'] ?? ($data['slug'] ?? '')));
        $post = $slug !== '' ? get_post($slug) : null;

        $title = trim((string)($_GET['title'] ?? ($data['title'] ?? ($post['title'] ?? ''))));
        $eyebrow = trim((string)($_GET['eyebrow'] ?? ($data['eyebrow'] ?? ($post['category'] ?? 'ZEYNART JOURNAL'))));
        $excerpt = trim((string)($_GET['excerpt'] ?? ($data['excerpt'] ?? ($post['excerpt'] ?? ''))));
        $ctaBadge = trim((string)($_GET['cta_badge'] ?? ($data['cta_badge'] ?? '')));
        $bgPath = trim((string)($_GET['bg_path'] ?? ($data['bg_path'] ?? ($post['image'] ?? ''))));
        $outFmt = strtolower((string)($_GET['out'] ?? ($data['out'] ?? 'png')));

        $gen = zeynart_generate_social_media_graphic($store, [
            'format' => $format,
            'template' => $template,
            'title' => $title,
            'eyebrow' => $eyebrow,
            'excerpt' => $excerpt,
            'cta_badge' => $ctaBadge,
            'bg_path' => $bgPath,
            'slug' => $slug,
            'output' => $outFmt,
            'save_to_uploads' => false,
        ]);

        if (empty($gen['ok']) || empty($gen['bytes'])) {
            http_response_code(500);
            exit;
        }
        $ext = ($outFmt === 'jpg' || $outFmt === 'jpeg') ? 'jpg' : (($outFmt === 'webp') ? 'webp' : 'png');
        $dlName = 'zeynart-' . ($format === 'story' ? 'story-1080x1920' : 'instagram-1080x1080') . ($slug !== '' ? ('-' . substr($slug, 0, 40)) : '') . '.' . $ext;
        header('Content-Type: ' . ($gen['mime'] ?? 'image/png'));
        if (!empty($_GET['download'])) {
            header('Content-Disposition: attachment; filename="' . $dlName . '"');
        }
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Content-Length: ' . strlen($gen['bytes']));
        echo $gen['bytes'];
        exit;
    }

    case 'social_graphic_save_media': {
        $format = ((string)($data['format'] ?? 'square')) === 'story' ? 'story' : 'square';
        $template = (string)($data['template'] ?? 'editorial_glass');
        $slug = trim((string)($data['slug'] ?? ''));
        $post = $slug !== '' ? get_post($slug) : null;

        $gen = zeynart_generate_social_media_graphic($store, [
            'format' => $format,
            'template' => $template,
            'title' => trim((string)($data['title'] ?? ($post['title'] ?? ''))),
            'eyebrow' => trim((string)($data['eyebrow'] ?? ($post['category'] ?? 'ZEYNART JOURNAL'))),
            'excerpt' => trim((string)($data['excerpt'] ?? ($post['excerpt'] ?? ''))),
            'cta_badge' => trim((string)($data['cta_badge'] ?? '')),
            'bg_path' => trim((string)($data['bg_path'] ?? ($post['image'] ?? ''))),
            'slug' => $slug ?: 'paylasim',
            'save_to_uploads' => true,
        ]);

        if (empty($gen['ok'])) {
            api_json(false, ['error' => (string)($gen['error'] ?? 'Sosyal medya görseli kaydedilemedi.')], 500);
        }

        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('social_graphic', ($format === 'story' ? '1080×1920 Story' : '1080×1080 Instagram') . ' görseli üretilip Medya Kütüphanesine kaydedildi (' . $gen['url'] . ').', $actor, 'ok');

        api_json(true, [
            'message' => '✓ ' . ($format === 'story' ? '1080×1920 Story' : '1080×1080 Instagram Post') . ' görseli Medya Kütüphanesine (.webp) kaydedildi!',
            'url' => $gen['url'],
            'mtime' => $gen['mtime'],
            'cache_busted_url' => $gen['cache_busted_url'],
            'width' => $gen['width'],
            'height' => $gen['height'],
            'size' => $gen['size'],
        ]);
    }

    case 'seo_interlinks_save': {
        $in = is_array($data['seo_interlinks'] ?? null) ? $data['seo_interlinks'] : [];
        $rulesClean = [];
        if (is_array($in['rules'] ?? null)) {
            foreach ($in['rules'] as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $kw = trim((string)($r['keyword'] ?? ''));
                $u = trim((string)($r['url'] ?? ''));
                if ($kw !== '' && $u !== '') {
                    $rulesClean[] = [
                        'keyword' => mb_substr($kw, 0, 80, 'UTF-8'),
                        'url' => mb_substr($u, 0, 180, 'UTF-8'),
                        'title' => mb_substr(trim((string)($r['title'] ?? $kw)), 0, 110, 'UTF-8'),
                    ];
                }
            }
        }
        $audioEnabled = array_key_exists('audio_player_enabled', $in)
            ? (bool)$in['audio_player_enabled']
            : (bool)($store['blog_settings']['audio_player_enabled'] ?? true);

        $store['seo_interlinks'] = [
            'enabled' => array_key_exists('enabled', $in) ? (bool)$in['enabled'] : true,
            'max_links_per_post' => max(1, min(10, (int)($in['max_links_per_post'] ?? 4))),
            'max_per_target' => max(1, min(3, (int)($in['max_per_target'] ?? 1))),
            'open_in_new_tab' => !empty($in['open_in_new_tab']),
            'audio_player_enabled' => $audioEnabled,
            'rules' => $rulesClean,
        ];
        $store['blog_settings'] = is_array($store['blog_settings'] ?? null) ? $store['blog_settings'] : [];
        $store['blog_settings']['audio_player_enabled'] = $audioEnabled;

        save_store_or_fail($store);
        api_json(true, [
            'message' => 'Blog Sesli Dinleme (Podcast) & Otomatik SEO İç Linkleme ayarları kaydedildi.',
            'seo_interlinks' => zeynart_get_auto_interlink_config($store),
            'blog_settings' => $store['blog_settings'],
        ]);
    }

    case 'vcards_save': {
        $rawCards = is_array($data['vcards'] ?? null) ? $data['vcards'] : [];
        $tempStore = $store;
        $tempStore['vcards'] = $rawCards;
        $normalized = zeynart_get_vcards_list($tempStore);
        $store['vcards'] = $normalized;
        save_store_or_fail($store);

        api_json(true, [
            'message' => 'QR Dijital Kartvizit & Kurumsal Fatura/IBAN profilleri kaydedildi.',
            'vcards' => $store['vcards'],
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'vcard_qr_preview': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $slug = slugify((string)($_GET['slug'] ?? 'zeynart')) ?: 'zeynart';
        $cardUrl = rtrim(canonical_url('/kartvizit/' . $slug . '/', $store, 'tr'), '/') . '/';
        $type = strtolower((string)($_GET['type'] ?? 'svg'));
        if ($type === 'png') {
            $png = zeynart_qr_render_png_bytes($cardUrl, 1000, [6, 19, 24], [255, 255, 255]);
            header('Content-Type: image/png');
            if (!empty($_GET['download'])) {
                header('Content-Disposition: attachment; filename="zeynart-kartvizit-qr-' . $slug . '.png"');
            }
            header('Cache-Control: no-store');
            echo $png;
            exit;
        }
        $svg = zeynart_qr_render_svg($cardUrl, '#061318', '#ffffff', 3, 512);
        header('Content-Type: image/svg+xml; charset=UTF-8');
        if (!empty($_GET['download'])) {
            header('Content-Disposition: attachment; filename="zeynart-kartvizit-qr-' . $slug . '.svg"');
        }
        header('Cache-Control: no-store');
        echo $svg;
        exit;
    }

    case 'uptime_list': {
        $cfg = zeynart_get_uptime_tower_config($store);
        $mons = zeynart_load_uptime_monitors($store);
        api_json(true, [
            'monitors' => $mons,
            'uptime_summary' => zeynart_compute_uptime_summary($mons, $cfg),
            'uptime_tower' => build_safe_store_for_client($store)['uptime_tower'],
        ]);
    }

    case 'uptime_save_config': {
        $existing = zeynart_get_uptime_tower_config($store);
        $in = is_array($data['uptime_tower'] ?? null) ? $data['uptime_tower'] : [];
        if (empty($in['cron_secret'])) {
            $in['cron_secret'] = $existing['cron_secret'];
        }
        $merged = $store;
        $merged['uptime_tower'] = array_merge($existing, $in);
        $store['uptime_tower'] = zeynart_get_uptime_tower_config($merged);
        save_store_or_fail($store);

        $mons = zeynart_load_uptime_monitors($store);
        $canonBase = rtrim((string)($store['seo']['canonical_domain'] ?? SITE_URL), '/');
        api_json(true, [
            'message' => 'Uptime & SSL Kontrol Kulesi ayarları kaydedildi.',
            'uptime_tower' => build_safe_store_for_client($store)['uptime_tower'],
            'uptime_summary' => zeynart_compute_uptime_summary($mons, $store['uptime_tower']),
            'cron_url' => $canonBase . '/api/cron/uptime?key=' . $store['uptime_tower']['cron_secret'],
        ]);
    }

    case 'uptime_save_monitor': {
        $in = is_array($data['monitor'] ?? null) ? $data['monitor'] : [];
        $norm = zeynart_normalize_monitor_url((string)($in['url'] ?? ($in['domain'] ?? '')));
        if ($norm === null) {
            api_json(false, ['error' => 'Lütfen geçerli bir alan adı girin (örn: orneksite.com veya https://orneksite.com).'], 422);
        }
        $mons = zeynart_load_uptime_monitors($store);
        $editId = trim((string)($in['id'] ?? ''));
        $foundIdx = null;

        foreach ($mons as $idx => $m) {
            if ($editId !== '' && ($m['id'] ?? '') === $editId) {
                $foundIdx = $idx;
                break;
            }
            if ($editId === '' && strtolower((string)($m['domain'] ?? '')) === $norm['domain']) {
                api_json(false, ['error' => $norm['domain'] . ' zaten izleme listesinde mevcut.'], 422);
            }
        }

        if ($foundIdx !== null) {
            $mons[$foundIdx]['url'] = $norm['url'];
            $mons[$foundIdx]['domain'] = $norm['domain'];
            $mons[$foundIdx]['client_name'] = mb_substr(trim((string)($in['client_name'] ?? $norm['domain'])) ?: $norm['domain'], 0, 80, 'UTF-8');
            $mons[$foundIdx]['server_group'] = mb_substr(trim((string)($in['server_group'] ?? 'Genel')) ?: 'Genel', 0, 48, 'UTF-8');
            $mons[$foundIdx]['notes'] = mb_substr(trim((string)($in['notes'] ?? '')), 0, 180, 'UTF-8');
            $mons[$foundIdx]['enabled'] = ($in['enabled'] ?? true) !== false;
            $savedId = (string)$mons[$foundIdx]['id'];
        } else {
            $savedId = 'mon-' . substr(md5($norm['domain'] . '|' . microtime(true)), 0, 10);
            $mons[] = [
                'id' => $savedId,
                'url' => $norm['url'],
                'domain' => $norm['domain'],
                'client_name' => mb_substr(trim((string)($in['client_name'] ?? $norm['domain'])) ?: $norm['domain'], 0, 80, 'UTF-8'),
                'server_group' => mb_substr(trim((string)($in['server_group'] ?? 'Plesk / Müşteri')) ?: 'Plesk / Müşteri', 0, 48, 'UTF-8'),
                'notes' => mb_substr(trim((string)($in['notes'] ?? '')), 0, 180, 'UTF-8'),
                'enabled' => ($in['enabled'] ?? true) !== false,
                'status' => 'pending',
                'http_code' => 0,
                'response_ms' => 0,
                'server_header' => '',
                'ssl_valid' => null,
                'ssl_days_left' => null,
                'ssl_expires_at' => '',
                'ssl_issuer' => '',
                'ssl_checked_ts' => 0,
                'last_checked_ts' => 0,
                'last_checked_at' => '',
                'last_error' => '',
                'uptime_pct' => 100.0,
                'history' => [],
                'last_alert_sent_ts' => 0,
                'created_at' => date('c'),
            ];
        }

        // Immediately probe the newly added/updated site if requested
        if (!empty($data['probe_now'])) {
            if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            zeynart_probe_uptime_batch($mons, [$savedId], $store, 1, true);
        } else {
            zeynart_save_uptime_monitors($mons);
        }

        $cfg = zeynart_get_uptime_tower_config($store);
        api_json(true, [
            'message' => ($foundIdx !== null ? 'Site kaydı güncellendi.' : '✓ Müşteri sitesi kuleye eklendi ve kontrol edildi!'),
            'saved_id' => $savedId,
            'monitors' => $mons,
            'uptime_summary' => zeynart_compute_uptime_summary($mons, $cfg),
        ]);
    }

    case 'uptime_bulk_import': {
        $rawLines = (string)($data['lines'] ?? '');
        $defGroup = trim((string)($data['server_group'] ?? 'Plesk / Müşteri')) ?: 'Plesk / Müşteri';
        if (trim($rawLines) === '') {
            api_json(false, ['error' => 'Lütfen her satıra bir alan adı gelecek şekilde listeyi yapıştırın.'], 422);
        }
        $mons = zeynart_load_uptime_monitors($store);
        $imp = zeynart_bulk_import_uptime_monitors($mons, $rawLines, $defGroup);
        $cfg = zeynart_get_uptime_tower_config($store);

        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('uptime_import', $imp['added'] . ' müşteri sitesi toplu olarak Uptime & SSL Kulesine eklendi.', $actor, 'ok');

        api_json(true, [
            'message' => "✓ {$imp['added']} yeni müşteri sitesi eklendi" . ($imp['skipped_duplicate'] > 0 ? " ({$imp['skipped_duplicate']} mükerrer atlandı)" : '') . '!',
            'added' => $imp['added'],
            'added_ids' => $imp['added_ids'],
            'monitors' => $mons,
            'uptime_summary' => zeynart_compute_uptime_summary($mons, $cfg),
        ]);
    }

    case 'uptime_delete_monitor': {
        $ids = [];
        if (!empty($data['ids']) && is_array($data['ids'])) {
            foreach ($data['ids'] as $v) {
                $ids[trim((string)$v)] = true;
            }
        } elseif (!empty($data['id'])) {
            $ids[trim((string)$data['id'])] = true;
        }
        if ($ids === []) {
            api_json(false, ['error' => 'Silinecek site seçilmedi.'], 422);
        }
        $mons = zeynart_load_uptime_monitors($store);
        $kept = [];
        $removed = 0;
        foreach ($mons as $m) {
            if (isset($ids[(string)($m['id'] ?? '')])) {
                $removed++;
            } else {
                $kept[] = $m;
            }
        }
        zeynart_save_uptime_monitors($kept);
        $cfg = zeynart_get_uptime_tower_config($store);
        api_json(true, [
            'message' => $removed . ' site izleme listesinden kaldırıldı.',
            'monitors' => $kept,
            'uptime_summary' => zeynart_compute_uptime_summary($kept, $cfg),
        ]);
    }

    case 'uptime_check_batch': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        @set_time_limit(65);
        $mons = zeynart_load_uptime_monitors($store);
        $targetIds = [];
        if (!empty($data['ids']) && is_array($data['ids'])) {
            foreach ($data['ids'] as $idVal) {
                $c = trim((string)$idVal);
                if ($c !== '') {
                    $targetIds[] = $c;
                }
            }
        } elseif (!empty($data['id'])) {
            $targetIds[] = trim((string)$data['id']);
        }
        $forceSsl = !empty($data['force_ssl']) || count($targetIds) === 1;
        $limit = max(1, min(30, (int)($data['limit'] ?? 20)));

        $probeRes = zeynart_probe_uptime_batch($mons, $targetIds, $store, $limit, $forceSsl);
        $cfg = zeynart_get_uptime_tower_config($store);

        api_json(true, [
            'message' => $probeRes['checked_count'] . ' site kontrol edildi.',
            'checked_count' => $probeRes['checked_count'],
            'checked_ids' => $probeRes['checked_ids'],
            'monitors' => $mons,
            'uptime_summary' => zeynart_compute_uptime_summary($mons, $cfg),
        ]);
    }

    /* =========================================================================
     * ENTERPRISE CMS SUITE ACTIONS (6 MODULES)
     * ========================================================================= */

    case 'cache_purge': {
        $purgeOg = !empty($data['purge_og']);
        $res = zeynart_purge_litespeed_and_caches($purgeOg);
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('cache_purge', 'LiteSpeed LSCache & PHP OPcache önbelleği temizlendi.', $actor, 'ok');
        api_json(true, [
            'message' => '⚡ LiteSpeed önbelleği ve PHP OPcache başarıyla sıfırlandı!',
            'actions' => $res['actions'],
            'system_health' => zeynart_collect_system_health(),
        ]);
    }

    case 'lscache_save_settings': {
        $enabled = !empty($data['enabled']);
        $ttl = (int)($data['ttl'] ?? 3600);
        if ($ttl < 60 || $ttl > 604800) {
            $ttl = 3600;
        }
        $store['lscache'] = [
            'enabled' => $enabled,
            'ttl' => $ttl,
        ];
        $GLOBALS['zeynart_next_revision_label'] = 'LiteSpeed LSCache Ayarı (' . ($enabled ? 'Açık' : 'Kapalı') . ')';
        save_store_or_fail($store);
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log(
            'lscache_toggle',
            'LiteSpeed LSCache durumu ' . ($enabled ? ('AÇIK (TTL: ' . $ttl . ' sn)') : 'KAPALI (Devre Dışı)') . ' olarak güncellendi.',
            $actor,
            'ok'
        );
        api_json(true, [
            'message' => $enabled
                ? '⚡ LiteSpeed LSCache aktif edildi (Süre: ' . $ttl . ' sn).'
                : '⏸ LiteSpeed LSCache devre dışı bırakıldı ve mevcut önbellek temizlendi.',
            'lscache' => $store['lscache'],
            'system_health' => zeynart_collect_system_health(),
        ]);
    }

    case 'lscache_scan': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $singleUri = isset($data['uri']) && trim((string)$data['uri']) !== '' ? trim((string)$data['uri']) : null;
        $res = zeynart_probe_or_warm_lscache_urls($store, 'probe', $singleUri);
        $ls = $res['lscache'] ?? zeynart_compile_lscache_overview($store);
        api_json(true, [
            'message' => '🔍 Canlı LiteSpeed taraması tamamlandı: ' . (int)($ls['cached_count'] ?? 0) . ' / ' . (int)($ls['total_urls'] ?? 0) . ' sayfa önbellekte.',
            'lscache' => $ls,
            'system_health' => zeynart_collect_system_health(),
        ]);
    }

    case 'lscache_warm': {
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $singleUri = isset($data['uri']) && trim((string)$data['uri']) !== '' ? trim((string)$data['uri']) : null;
        $res = zeynart_probe_or_warm_lscache_urls($store, 'warm', $singleUri);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Önbellek ısıtma işlemi yapılamadı.')], 422);
        }
        $ls = $res['lscache'] ?? zeynart_compile_lscache_overview($store);
        zeynart_audit_log(
            'lscache_warm',
            $singleUri !== null
                ? ('Sayfa LiteSpeed önbelleğine alındı: ' . $singleUri)
                : ('Tüm site LiteSpeed önbelleğine alındı (' . (int)($ls['cached_count'] ?? 0) . ' sayfa).'),
            $actor,
            'ok'
        );
        api_json(true, [
            'message' => $singleUri !== null
                ? ('🔥 "' . $singleUri . '" sayfası LiteSpeed önbelleğine alındı.')
                : ('🔥 Tüm site önbelleğe alındı: ' . (int)($ls['cached_count'] ?? 0) . ' / ' . (int)($ls['total_urls'] ?? 0) . ' sayfa hazır!'),
            'lscache' => $ls,
            'system_health' => zeynart_collect_system_health(),
        ]);
    }

    case 'lscache_purge_url': {
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        $uri = trim((string)($data['uri'] ?? ''));
        if ($uri === '') {
            api_json(false, ['error' => 'Temizlenecek sayfa adresi belirtilmedi.'], 422);
        }
        $pRes = zeynart_purge_single_lscache_url($uri);
        zeynart_audit_log('lscache_purge_url', 'Tekil sayfa LiteSpeed önbelleğinden silindi: ' . $pRes['uri'], $actor, 'info');
        api_json(true, [
            'message' => '🗑 "' . $pRes['uri'] . '" sayfasının LiteSpeed önbelleği temizlendi.',
            'purged_uri' => $pRes['uri'],
            'system_health' => zeynart_collect_system_health(),
        ]);
    }

    case 'system_health_scan': {
        api_json(true, [
            'system_health' => zeynart_collect_system_health(true),
        ]);
    }

    case 'orphan_media_scan': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $scan = zeynart_scan_orphan_uploads($store, 250);
        api_json(true, [
            'scan' => $scan,
        ]);
    }

    case 'orphan_media_delete': {
        $rels = is_array($data['files'] ?? null) ? $data['files'] : [];
        if ($rels === []) {
            api_json(false, ['error' => 'Temizlenecek yetim görsel seçilmedi.'], 422);
        }
        $del = zeynart_delete_orphan_uploads($rels, $store);
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('orphan_media_clean', $del['deleted_count'] . ' kullanılmayan yetim görsel temizlendi (' . $del['freed_mb'] . ' MB).', $actor, 'info');
        api_json(true, [
            'message' => "✓ {$del['deleted_count']} kullanılmayan görsel silindi ({$del['freed_mb']} MB alan açıldı).",
            'deleted_count' => $del['deleted_count'],
            'freed_mb' => $del['freed_mb'],
            'scan' => zeynart_scan_orphan_uploads($store, 250),
            'system_health' => zeynart_collect_system_health(),
        ]);
    }

    case 'search_insights_get': {
        api_json(true, [
            'search_insights' => zeynart_compile_search_insights(),
        ]);
    }

    case 'search_insights_clear': {
        zeynart_delete_json_data(zeynart_search_analytics_file());
        api_json(true, [
            'message' => 'Site içi arama istatistikleri sıfırlandı.',
            'search_insights' => zeynart_compile_search_insights(),
        ]);
    }

    case 'authors_save': {
        $rawList = is_array($data['authors'] ?? null) ? $data['authors'] : [];
        $tmpStore = $store;
        $tmpStore['authors'] = $rawList;
        $store['authors'] = zeynart_get_authors_list($tmpStore);
        save_store_or_fail($store);
        api_json(true, [
            'message' => '✓ E-E-A-T uzman yazar profilleri kaydedildi.',
            'authors' => $store['authors'],
            'store' => build_safe_store_for_client($store),
        ]);
    }

    case 'blog_comments_save_cfg': {
        $inCfg = is_array($data['blog_comments_cfg'] ?? null) ? $data['blog_comments_cfg'] : [];
        $tmpStore = $store;
        $tmpStore['blog_comments_cfg'] = array_merge(zeynart_get_blog_comments_config($store), $inCfg);
        $store['blog_comments_cfg'] = zeynart_get_blog_comments_config($tmpStore);
        save_store_or_fail($store);
        api_json(true, [
            'message' => '✓ Blog Soru-Cevap & Yazar kutusu ayarları kaydedildi.',
            'blog_comments_cfg' => $store['blog_comments_cfg'],
        ]);
    }

    case 'blog_comments_list': {
        api_json(true, [
            'blog_comments' => zeynart_load_blog_comments(),
            'blog_comments_cfg' => zeynart_get_blog_comments_config($store),
        ]);
    }

    case 'blog_comment_moderate': {
        $id = trim((string)($data['id'] ?? ''));
        $op = trim((string)($data['op'] ?? 'approve'));
        if ($id === '') {
            api_json(false, ['error' => 'Yorum kimliği belirtilmedi.'], 422);
        }
        $all = zeynart_load_blog_comments();
        $found = false;
        $kept = [];
        foreach ($all as $c) {
            if (($c['id'] ?? '') === $id) {
                $found = true;
                if ($op === 'delete') {
                    continue;
                }
                if ($op === 'approve') {
                    $c['status'] = 'approved';
                } elseif ($op === 'unapprove') {
                    $c['status'] = 'pending';
                }
                if (isset($data['admin_reply'])) {
                    $rep = mb_substr(trim((string)$data['admin_reply']), 0, 2000, 'UTF-8');
                    $c['admin_reply'] = $rep;
                    if ($rep !== '') {
                        $c['replied_by'] = (string)($_SESSION['zeynart_admin_display'] ?? 'Zeynart Uzman Ekibi');
                        $c['replied_at'] = date('Y-m-d H:i:s');
                        $c['status'] = 'approved';
                    }
                }
            }
            $kept[] = $c;
        }
        if (!$found) {
            api_json(false, ['error' => 'İlgili yorum kaydı bulunamadı.'], 404);
        }
        zeynart_save_blog_comments($kept);
        api_json(true, [
            'message' => $op === 'delete' ? 'Yorum silindi.' : '✓ Yorum ve uzman yanıtı güncellendi.',
            'blog_comments' => $kept,
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'proposals_list': {
        api_json(true, [
            'proposals' => zeynart_load_proposals(),
        ]);
    }

    case 'proposal_save': {
        $in = is_array($data['proposal'] ?? null) ? $data['proposal'] : [];
        if (trim((string)($in['client_name'] ?? '')) === '' && trim((string)($in['client_company'] ?? '')) === '') {
            api_json(false, ['error' => 'Lütfen müşteri adı veya firma ünvanını girin.'], 422);
        }
        $proposals = zeynart_load_proposals();
        $editId = trim((string)($in['id'] ?? ''));
        $existingIdx = null;
        foreach ($proposals as $idx => $p) {
            if ($editId !== '' && ($p['id'] ?? '') === $editId) {
                $existingIdx = $idx;
                break;
            }
        }
        $normalized = zeynart_normalize_proposal($in, $existingIdx !== null ? $proposals[$existingIdx] : null);
        // Ensure unique proposal code
        foreach ($proposals as $idx => $p) {
            if ($idx !== $existingIdx && strtolower((string)($p['code'] ?? '')) === strtolower($normalized['code'])) {
                $normalized['code'] .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);
                break;
            }
        }
        if ($existingIdx !== null) {
            $proposals[$existingIdx] = $normalized;
        } else {
            array_unshift($proposals, $normalized);
        }
        zeynart_save_proposals($proposals);
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('proposal_save', 'Özel müşteri teklifi kaydedildi: ' . $normalized['proposal_no'], $actor, 'ok');
        api_json(true, [
            'message' => '✓ Özel online teklif kaydedildi ve paylaşım linki hazır!',
            'proposal' => $normalized,
            'proposals' => $proposals,
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'proposal_delete': {
        $id = trim((string)($data['id'] ?? ''));
        if ($id === '') {
            api_json(false, ['error' => 'Silinecek teklif seçilmedi.'], 422);
        }
        $proposals = array_values(array_filter(zeynart_load_proposals(), static fn(array $p): bool => ($p['id'] ?? '') !== $id));
        zeynart_save_proposals($proposals);
        api_json(true, [
            'message' => 'Teklif kaydı silindi.',
            'proposals' => $proposals,
            'summary' => compute_dashboard_summary($store),
        ]);
    }

    case 'security_2fa_init': {
        $secret = zeynart_totp_generate_secret(20);
        $_SESSION['zeynart_2fa_setup_secret'] = $secret;
        $issuer = (string)($store['site']['name'] ?? 'Zeynart');
        $account = (string)($store['admin']['username'] ?? 'admin');
        $otpAuthUri = 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&digits=6&period=30';
        $qrSvg = zeynart_qr_render_svg($otpAuthUri, '#051319', '#ffffff', 3, 240);
        api_json(true, [
            'secret' => $secret,
            'otpauth_uri' => $otpAuthUri,
            'qr_svg' => $qrSvg,
        ]);
    }

    case 'security_2fa_enable': {
        $method = in_array((string)($data['method'] ?? 'totp'), ['totp', 'email'], true) ? (string)$data['method'] : 'totp';
        $secret = trim((string)($_SESSION['zeynart_2fa_setup_secret'] ?? ($data['secret'] ?? '')));
        $code = trim((string)($data['code'] ?? ''));
        if ($method === 'totp') {
            if ($secret === '' || !zeynart_totp_verify($secret, $code)) {
                api_json(false, ['error' => 'Girdiğiniz 6 haneli Authenticator kodu doğrulanamadı. Lütfen QR kodu okutup güncel kodu girin.'], 422);
            }
        } else {
            if (empty($store['smtp']['enabled'])) {
                api_json(false, ['error' => 'E-posta tabanlı 2FA için önce SMTP ayarlarını etkinleştirmeniz gerekir.'], 422);
            }
        }
        $recoveryCodes = zeynart_generate_recovery_codes(8);
        $store['security_2fa'] = [
            'enabled' => true,
            'method' => $method,
            'totp_secret' => $secret,
            'email_fallback' => ($data['email_fallback'] ?? true) !== false,
            'recovery_codes' => $recoveryCodes,
            'enabled_at' => date('Y-m-d H:i:s'),
        ];
        unset($_SESSION['zeynart_2fa_setup_secret']);
        save_store_or_fail($store);
        api_json(true, [
            'message' => '🔐 İki Aşamalı Giriş Doğrulama (2FA) başarıyla etkinleştirildi!',
            'recovery_codes' => $recoveryCodes,
            'security_2fa' => build_safe_store_for_client($store)['security_2fa'],
        ]);
    }

    case 'security_2fa_disable': {
        $currentPass = (string)($data['current_password'] ?? '');
        $primaryHash = (string)($store['admin']['password_hash'] ?? '');
        if ($currentPass === '' || $primaryHash === '' || !password_verify($currentPass, $primaryHash)) {
            api_json(false, ['error' => '2FA korumasını kapatmak için mevcut yönetici parolanızı doğrulamanız gerekir.'], 403);
        }
        $store['security_2fa'] = [
            'enabled' => false,
            'method' => 'totp',
            'totp_secret' => '',
            'email_fallback' => true,
            'recovery_codes' => [],
            'enabled_at' => null,
        ];
        save_store_or_fail($store);
        api_json(true, [
            'message' => 'İki aşamalı giriş doğrulama (2FA) devre dışı bırakıldı.',
            'security_2fa' => build_safe_store_for_client($store)['security_2fa'],
        ]);
    }

    case 'vault_backup_save_cfg': {
        $in = is_array($data['backup_vault'] ?? null) ? $data['backup_vault'] : [];
        $tmpStore = $store;
        $tmpStore['backup_vault'] = array_merge(zeynart_get_backup_schedule_config($store), $in);
        $store['backup_vault'] = zeynart_get_backup_schedule_config($tmpStore);
        save_store_or_fail($store);
        api_json(true, [
            'message' => '✓ Otomatik yedekleme planı ve saklama sınırı kaydedildi.',
            'backup_vault' => $store['backup_vault'],
        ]);
    }

    case 'vault_backup_create': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $cfg = zeynart_get_backup_schedule_config($store);
        $res = zeynart_create_vault_backup([
            'include_store' => ($data['include_store'] ?? true) !== false,
            'include_posts' => ($data['include_posts'] ?? true) !== false,
            'include_uploads' => !empty($data['include_uploads']),
            'is_auto' => false,
        ], $cfg['retain_count']);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Yedek oluşturulamadı.')], 500);
        }
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('vault_backup_create', 'Tam veri yedeği oluşturuldu: ' . $res['filename'], $actor, 'ok');
        api_json(true, [
            'message' => "✓ Yedek paketi oluşturuldu: {$res['filename']} ({$res['added_files']} dosya · {$res['size_mb']} MB)",
            'created' => $res,
            'vault_backups' => zeynart_list_vault_backups(),
            'system_health' => zeynart_collect_system_health(),
        ]);
    }

    case 'vault_backup_download': {
        $fname = basename((string)($_GET['file'] ?? ($data['file'] ?? '')));
        $abs = zeynart_backups_dir() . '/' . $fname;
        if ($fname === '' || !str_ends_with($fname, '.zip') || !is_file($abs)) {
            api_json(false, ['error' => 'Yedek dosyası bulunamadı.'], 404);
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . (int)filesize($abs));
        header('Cache-Control: no-store');
        readfile($abs);
        exit;
    }

    case 'vault_backup_upload': {
        if (empty($_FILES['backup_zip']['tmp_name']) || !is_uploaded_file((string)$_FILES['backup_zip']['tmp_name'])) {
            api_json(false, ['error' => 'Lütfen geçerli bir .zip yedek dosyası seçin.'], 422);
        }
        $origName = preg_replace('/[^A-Za-z0-9_\-.]/', '-', basename((string)($_FILES['backup_zip']['name'] ?? 'yuklenen-yedek.zip'))) ?: 'yuklenen-yedek.zip';
        if (!str_ends_with(strtolower($origName), '.zip')) {
            api_json(false, ['error' => 'Yalnızca .zip uzantılı stüdyo yedek paketleri yüklenebilir.'], 422);
        }
        $dest = zeynart_backups_dir() . '/yuklenen-' . date('Ymd-His') . '-' . $origName;
        if (!@move_uploaded_file((string)$_FILES['backup_zip']['tmp_name'], $dest)) {
            api_json(false, ['error' => 'Yedek dosyası sunucuya taşınamadı.'], 500);
        }
        api_json(true, [
            'message' => '✓ Dış yedek paketi kasaya yüklendi. İstediğiniz zaman geri yükleyebilirsiniz.',
            'vault_backups' => zeynart_list_vault_backups(),
        ]);
    }

    case 'vault_backup_restore': {
        $fname = basename((string)($data['file'] ?? ''));
        $abs = zeynart_backups_dir() . '/' . $fname;
        if ($fname === '' || !str_ends_with($fname, '.zip') || !is_file($abs)) {
            api_json(false, ['error' => 'Geri yüklenecek yedek dosyası bulunamadı.'], 404);
        }
        $res = zeynart_restore_vault_backup($abs, true);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Yedek geri yüklenemedi.')], 500);
        }
        $freshStore = load_store(true);
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('vault_backup_restore', 'Yedek kasasından geri yükleme yapıldı: ' . $fname . ' (Form talepleri ve parolalar korundu).', $actor, 'warn');
        api_json(true, [
            'message' => "✓ Yedek başarıyla geri yüklendi ({$res['restored_data']} veri, {$res['restored_posts']} yazı, {$res['restored_media']} medya). Canlı müşteri talepleri ve şifreniz korundu!",
            'store' => build_safe_store_for_client($freshStore),
            'summary' => compute_dashboard_summary($freshStore),
        ]);
    }

    case 'vault_backup_delete': {
        $fname = basename((string)($data['file'] ?? ''));
        $abs = zeynart_backups_dir() . '/' . $fname;
        if ($fname !== '' && str_ends_with($fname, '.zip') && is_file($abs)) {
            @unlink($abs);
        }
        api_json(true, [
            'message' => 'Yedek arşivi silindi.',
            'vault_backups' => zeynart_list_vault_backups(),
        ]);
    }

    case 'seo_content_audit': {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        api_json(true, [
            'audit' => zeynart_audit_all_content_seo($store),
            'indexnow_logs' => zeynart_get_indexnow_logs(),
        ]);
    }

    case 'indexnow_ping': {
        $urls = [];
        if (!empty($data['urls']) && is_array($data['urls'])) {
            $urls = $data['urls'];
        } elseif (!empty($data['urls_text'])) {
            $urls = preg_split('/[\r\n]+/', (string)$data['urls_text'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        } elseif (!empty($data['url'])) {
            $urls = [(string)$data['url']];
        } else {
            // Default: ping homepage + core pages + latest 10 published blog posts
            $slugs = zeynart_resolved_page_slugs($store);
            $urls = ['/', '/' . $slugs['hizmetler'] . '/', '/' . $slugs['projeler'] . '/', '/' . $slugs['blog'] . '/', '/' . $slugs['iletisim'] . '/'];
            foreach (array_slice(load_blog_index(), 0, 10) as $bp) {
                if (($bp['status'] ?? 'published') === 'published' && !empty($bp['slug'])) {
                    $urls[] = '/' . $slugs['blog'] . '/' . $bp['slug'] . '/';
                }
            }
        }
        $ping = zeynart_ping_indexnow_urls($urls, $store);
        if (empty($ping['submitted_count']) && !empty($ping['error'])) {
            api_json(false, ['error' => $ping['error']], 422);
        }
        api_json(true, [
            'message' => !empty($ping['ok'])
                ? "🚀 {$ping['submitted_count']} URL anında IndexNow arama motoru ağına (Bing / Yandex / Naver) bildirildi! (HTTP {$ping['http_code']})"
                : "IndexNow doğrulama anahtarı hazırlandı (/{$ping['key']}.txt) ve {$ping['submitted_count']} URL kuyruğa kaydedildi.",
            'key' => $ping['key'] ?? '',
            'ping' => $ping,
            'indexnow_logs' => $ping['logs'] ?? zeynart_get_indexnow_logs(),
        ]);
    }

    case 'executive_report_get': {
        $days = (int)($data['days'] ?? $_GET['days'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }
        api_json(true, [
            'executive_report_data' => zeynart_compile_executive_report($store, $days),
        ]);
    }

    case 'executive_report_save': {
        $in = is_array($data['executive_report'] ?? null) ? $data['executive_report'] : [];
        $tmpStore = $store;
        $tmpStore['executive_report'] = array_merge(zeynart_get_executive_report_config($store), $in);
        $store['executive_report'] = zeynart_get_executive_report_config($tmpStore);
        save_store_or_fail($store);
        $days = (int)($data['days'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }
        api_json(true, [
            'message' => '✓ Aylık kurumsal performans raporu başlık ve değerlendirme şablonu kaydedildi.',
            'executive_report' => $store['executive_report'],
            'executive_report_data' => zeynart_compile_executive_report($store, $days),
        ]);
    }

    case 'message_convert_proposal': {
        $id = trim((string)($data['id'] ?? ''));
        $messages = is_array($store['messages'] ?? null) ? $store['messages'] : [];
        $targetIdx = null;
        foreach ($messages as $i => $m) {
            if (($m['id'] ?? '') === $id) {
                $targetIdx = $i;
                break;
            }
        }
        if ($targetIdx === null) {
            api_json(false, ['error' => 'Dönüştürülecek proje talebi bulunamadı.'], 404);
        }
        $proposal = zeynart_convert_lead_to_proposal($messages[$targetIdx], $store);
        $messages[$targetIdx]['read'] = true;
        $messages[$targetIdx]['linked_proposal_code'] = (string)$proposal['code'];
        if (in_array(($messages[$targetIdx]['crm_stage'] ?? 'new'), ['new', 'contacted'], true)) {
            $messages[$targetIdx]['crm_stage'] = 'proposal_sent';
        }
        if ((float)($messages[$targetIdx]['deal_value'] ?? 0) <= 0) {
            $messages[$targetIdx]['deal_value'] = (float)($proposal['subtotal'] ?? 0);
        }
        $messages[$targetIdx]['updated_crm_at'] = date('c');
        $messages[$targetIdx] = zeynart_normalize_message_crm($messages[$targetIdx]);
        $store['messages'] = array_map(
            static fn($row) => is_array($row) ? zeynart_normalize_message_crm($row) : $row,
            $messages
        );
        save_store_or_fail($store);
        $propUrl = rtrim(canonical_url('/teklif/' . $proposal['code'] . '/', $store, 'tr'), '/') . '/';
        api_json(true, [
            'message' => '📄 Talep resmi müşteri teklifine dönüştürüldü (' . $proposal['proposal_no'] . ')!',
            'proposal' => $proposal,
            'proposal_url' => $propUrl,
            'proposals' => zeynart_load_proposals(),
            'messages' => $store['messages'],
            'crm_summary' => zeynart_compile_crm_summary($store['messages']),
        ]);
    }

    case 'client_trackers_list': {
        api_json(true, [
            'client_trackers' => zeynart_load_client_trackers(true),
            'client_trackers_summary' => zeynart_compile_client_trackers_summary(),
        ]);
    }

    case 'client_tracker_save': {
        $in = is_array($data['tracker'] ?? null) ? $data['tracker'] : $data;
        if (trim((string)($in['client_name'] ?? '')) === '' && trim((string)($in['client_company'] ?? '')) === '') {
            api_json(false, ['error' => 'Müşteri veya firma adı zorunludur.'], 422);
        }
        $all = zeynart_load_client_trackers(true);
        $wantId = trim((string)($in['id'] ?? ''));
        $updatedItem = null;
        $found = false;
        foreach ($all as $i => $row) {
            if ($wantId !== '' && ($row['id'] ?? '') === $wantId) {
                $in['feedbacks'] = $in['feedbacks'] ?? ($row['feedbacks'] ?? []);
                $in['views'] = $row['views'] ?? 0;
                $in['last_viewed_at'] = $row['last_viewed_at'] ?? '';
                $in['created_at'] = $row['created_at'] ?? date('c');
                $all[$i] = zeynart_normalize_client_tracker($in);
                $updatedItem = $all[$i];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $updatedItem = zeynart_normalize_client_tracker($in);
            array_unshift($all, $updatedItem);
        }
        if (!zeynart_save_client_trackers($all)) {
            api_json(false, ['error' => 'Proje takip kartı kaydedilemedi.'], 500);
        }
        $reloaded = zeynart_load_client_trackers(true);
        foreach ($reloaded as $rt) {
            if (($rt['id'] ?? '') === ($updatedItem['id'] ?? '')) {
                $updatedItem = $rt;
                break;
            }
        }
        zeynart_audit_log('client_tracker_save', 'Proje takip portalı kaydedildi: ' . ($updatedItem['project_title'] ?? ''), (string)($cachedCurrentUser['username'] ?? 'admin'));
        api_json(true, [
            'message' => '✓ Müşteri proje takip portalı kaydedildi (/proje-takip/' . $updatedItem['code'] . '/).',
            'tracker' => $updatedItem,
            'tracker_url' => rtrim(canonical_url('/proje-takip/' . $updatedItem['code'] . '/', $store, 'tr'), '/') . '/',
            'client_trackers' => $reloaded,
            'client_trackers_summary' => zeynart_compile_client_trackers_summary(),
        ]);
    }

    case 'client_tracker_advance': {
        $id = trim((string)($data['id'] ?? ''));
        $all = zeynart_load_client_trackers(true);
        $target = null;
        foreach ($all as $i => $row) {
            if (($row['id'] ?? '') === $id) {
                $ms = is_array($row['milestones'] ?? null) ? $row['milestones'] : zeynart_default_tracker_milestones();
                $advanced = false;
                for ($k = 0; $k < count($ms); $k++) {
                    if (($ms[$k]['status'] ?? '') === 'current') {
                        $ms[$k]['status'] = 'completed';
                        $ms[$k]['completed_at'] = date('Y-m-d');
                        if (isset($ms[$k + 1])) {
                            $ms[$k + 1]['status'] = 'current';
                        } else {
                            $all[$i]['status'] = 'completed';
                        }
                        $advanced = true;
                        break;
                    }
                }
                if (!$advanced) {
                    for ($k = 0; $k < count($ms); $k++) {
                        if (($ms[$k]['status'] ?? '') === 'upcoming') {
                            $ms[$k]['status'] = 'current';
                            $advanced = true;
                            break;
                        }
                    }
                }
                $all[$i]['milestones'] = $ms;
                $all[$i]['manual_progress'] = false;
                if (($all[$i]['status'] ?? '') === 'awaiting_approval' && ($all[$i]['status'] ?? '') !== 'completed') {
                    $all[$i]['status'] = 'active';
                }
                foreach (($all[$i]['feedbacks'] ?? []) as $fi => $_fb) {
                    $all[$i]['feedbacks'][$fi]['seen_by_admin'] = true;
                }
                $all[$i] = zeynart_normalize_client_tracker($all[$i]);
                $target = $all[$i];
                break;
            }
        }
        if ($target === null) {
            api_json(false, ['error' => 'Proje kaydı bulunamadı.'], 404);
        }
        zeynart_save_client_trackers($all);
        api_json(true, [
            'message' => '🚀 Proje bir sonraki aşamaya ilerletildi (%' . $target['progress_pct'] . ')!',
            'tracker' => $target,
            'client_trackers' => zeynart_load_client_trackers(true),
            'client_trackers_summary' => zeynart_compile_client_trackers_summary(),
        ]);
    }

    case 'client_tracker_mark_seen': {
        $id = trim((string)($data['id'] ?? ''));
        $all = zeynart_load_client_trackers(true);
        foreach ($all as $i => $row) {
            if ($id === '' || ($row['id'] ?? '') === $id) {
                foreach (($all[$i]['feedbacks'] ?? []) as $fi => $_fb) {
                    $all[$i]['feedbacks'][$fi]['seen_by_admin'] = true;
                }
            }
        }
        zeynart_save_client_trackers($all);
        api_json(true, [
            'message' => 'Müşteri onay/revize notları okundu olarak işaretlendi.',
            'client_trackers' => zeynart_load_client_trackers(true),
            'client_trackers_summary' => zeynart_compile_client_trackers_summary(),
        ]);
    }

    case 'client_tracker_delete': {
        $id = trim((string)($data['id'] ?? ''));
        $all = array_values(array_filter(
            zeynart_load_client_trackers(true),
            static fn(array $r): bool => ($r['id'] ?? '') !== $id
        ));
        zeynart_save_client_trackers($all);
        api_json(true, [
            'message' => 'Proje takip portalı kaydı silindi.',
            'client_trackers' => zeynart_load_client_trackers(true),
            'client_trackers_summary' => zeynart_compile_client_trackers_summary(),
        ]);
    }

    case 'renewals_list': {
        $items = zeynart_load_client_renewals(true);
        api_json(true, [
            'renewals' => $items,
            'renewals_summary' => zeynart_compile_renewals_summary($items),
        ]);
    }

    case 'renewal_save': {
        $in = is_array($data['renewal'] ?? null) ? $data['renewal'] : $data;
        if (trim((string)($in['domain'] ?? '')) === '' && trim((string)($in['client_name'] ?? '')) === '') {
            api_json(false, ['error' => 'Alan adı (domain) veya müşteri adı zorunludur.'], 422);
        }
        $all = zeynart_load_client_renewals(true);
        $wantId = trim((string)($in['id'] ?? ''));
        $found = false;
        foreach ($all as $i => $row) {
            if ($wantId !== '' && ($row['id'] ?? '') === $wantId) {
                $in['last_reminded_at'] = $in['last_reminded_at'] ?? ($row['last_reminded_at'] ?? '');
                $in['last_paid_at'] = $in['last_paid_at'] ?? ($row['last_paid_at'] ?? '');
                $all[$i] = zeynart_normalize_renewal_item($in);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $all[] = zeynart_normalize_renewal_item($in);
        }
        if (!zeynart_save_client_renewals($all)) {
            api_json(false, ['error' => 'Yenileme kaydı yazılamadı.'], 500);
        }
        $reloaded = zeynart_load_client_renewals(true);
        api_json(true, [
            'message' => '✓ Müşteri yenileme kaydı güncellendi.',
            'renewals' => $reloaded,
            'renewals_summary' => zeynart_compile_renewals_summary($reloaded),
        ]);
    }

    case 'renewal_extend_year': {
        $id = trim((string)($data['id'] ?? ''));
        $years = max(1, min(5, (int)($data['years'] ?? 1)));
        $all = zeynart_load_client_renewals(true);
        $extendedDomain = '';
        $newDate = '';
        foreach ($all as $i => $row) {
            if (($row['id'] ?? '') === $id) {
                $curTs = strtotime((string)($row['renewal_date'] ?? date('Y-m-d')) . ' 12:00:00') ?: time();
                $baseTs = max($curTs, strtotime(date('Y-m-d') . ' 12:00:00') ?: time());
                $newDate = date('Y-m-d', strtotime("+{$years} year", $baseTs));
                $all[$i]['renewal_date'] = $newDate;
                $all[$i]['status'] = 'active';
                $all[$i]['last_paid_at'] = date('d.m.Y H:i');
                $extendedDomain = (string)($row['domain'] ?: $row['client_name']);
                break;
            }
        }
        if ($newDate === '') {
            api_json(false, ['error' => 'Uzatılacak yenileme kaydı bulunamadı.'], 404);
        }
        zeynart_save_client_renewals($all);
        $reloaded = zeynart_load_client_renewals(true);
        api_json(true, [
            'message' => "✅ {$extendedDomain} yenileme tarihi +{$years} yıl uzatıldı (Yeni tarih: {$newDate}).",
            'renewals' => $reloaded,
            'renewals_summary' => zeynart_compile_renewals_summary($reloaded),
        ]);
    }

    case 'renewal_send_email': {
        $id = trim((string)($data['id'] ?? ''));
        $all = zeynart_load_client_renewals(true);
        $targetIdx = null;
        foreach ($all as $i => $row) {
            if (($row['id'] ?? '') === $id) {
                $targetIdx = $i;
                break;
            }
        }
        if ($targetIdx === null) {
            api_json(false, ['error' => 'Yenileme kaydı bulunamadı.'], 404);
        }
        $rnw = $all[$targetIdx];
        $toEmail = trim((string)($rnw['email'] ?? ''));
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            api_json(false, ['error' => 'Bu yenileme kaydında geçerli bir müşteri e-posta adresi bulunmuyor.'], 422);
        }
        $subj = '[' . ($store['site']['name'] ?? 'ZEYNART') . '] ' . ($rnw['domain'] ?: 'Hizmet') . ' Yıllık Yenileme Hatırlatması';
        $html = zeynart_build_renewal_email_html($rnw, $store);
        $res = send_smtp_detailed(is_array($store['smtp'] ?? null) ? $store['smtp'] : [], $toEmail, $subj, $html, [
            'force' => true,
            'allow_mail_fallback' => true,
        ]);
        if (empty($res['ok'])) {
            api_json(false, ['error' => 'E-posta gönderilemedi: ' . ($res['error'] ?? 'SMTP ayarlarını kontrol edin.')], 422);
        }
        $all[$targetIdx]['last_reminded_at'] = date('d.m.Y H:i');
        $all[$targetIdx]['status'] = 'reminded';
        zeynart_save_client_renewals($all);
        $reloaded = zeynart_load_client_renewals(true);
        api_json(true, [
            'message' => '📧 Yenileme hatırlatma e-postası ' . $toEmail . ' adresine başarıyla gönderildi!',
            'renewals' => $reloaded,
            'renewals_summary' => zeynart_compile_renewals_summary($reloaded),
        ]);
    }

    case 'renewal_delete': {
        $id = trim((string)($data['id'] ?? ''));
        $all = array_values(array_filter(
            zeynart_load_client_renewals(true),
            static fn(array $r): bool => ($r['id'] ?? '') !== $id
        ));
        zeynart_save_client_renewals($all);
        $reloaded = zeynart_load_client_renewals(true);
        api_json(true, [
            'message' => 'Yenileme kaydı silindi.',
            'renewals' => $reloaded,
            'renewals_summary' => zeynart_compile_renewals_summary($reloaded),
        ]);
    }

    case 'solution_pages_generate': {
        $sectors = is_array($data['sectors'] ?? null) && $data['sectors'] !== []
            ? array_values(array_map('strval', $data['sectors']))
            : [trim((string)($data['sector_key'] ?? 'ihracat-sanayi'))];
        $regions = is_array($data['regions'] ?? null) && $data['regions'] !== []
            ? array_values(array_map('strval', $data['regions']))
            : [trim((string)($data['region'] ?? ''))];
        $customSectorName = trim((string)($data['custom_sector_name'] ?? ''));
        $customSlug = (count($sectors) === 1 && count($regions) === 1) ? trim((string)($data['custom_slug'] ?? '')) : '';
        $statusWant = in_array(($data['status'] ?? 'published'), ['published', 'draft'], true) ? (string)$data['status'] : 'published';

        $existingPages = is_array($store['custom_pages'] ?? null) ? $store['custom_pages'] : [];
        $usedSlugs = array_flip(zeynart_system_reserved_slugs());
        foreach (zeynart_resolved_page_slugs($store) as $_pk => $pSlug) {
            $usedSlugs[strtolower($pSlug)] = true;
        }
        foreach ($existingPages as $ep) {
            if (is_array($ep) && !empty($ep['slug'])) {
                $usedSlugs[strtolower((string)$ep['slug'])] = true;
            }
        }

        $created = [];
        foreach (array_slice($sectors, 0, 8) as $secKey) {
            foreach (array_slice($regions, 0, 6) as $regName) {
                $built = zeynart_build_programmatic_solution_page($secKey, $customSectorName, $regName, $customSlug);
                $built['status'] = $statusWant;
                $baseSlug = $built['slug'];
                $candSlug = $baseSlug;
                $n = 2;
                while (isset($usedSlugs[strtolower($candSlug)])) {
                    $candSlug = $baseSlug . '-' . $n;
                    $n++;
                }
                $built['slug'] = $candSlug;
                $usedSlugs[strtolower($candSlug)] = true;
                $existingPages[] = $built;
                $created[] = [
                    'id' => $built['id'],
                    'slug' => $built['slug'],
                    'title' => $built['title'],
                    'url' => '/' . $built['slug'] . '/',
                    'status' => $built['status'],
                ];
            }
        }

        if ($created === []) {
            api_json(false, ['error' => 'Üretilecek sektör seçilmedi.'], 422);
        }

        $store['custom_pages'] = $existingPages;
        save_store_or_fail($store);
        sync_static_ai_manifests($store);
        api_json(true, [
            'message' => '🌍 ' . count($created) . ' adet sektörel/bölgesel SEO çözüm sayfası üretildi ve site haritasına eklendi!',
            'created_pages' => $created,
            'store' => build_safe_store_for_client($store),
            'llms_preview' => compile_llms_txt($store, false),
        ]);
    }

    case 'bookings_list': {
        api_json(true, [
            'bookings' => zeynart_load_bookings(true),
            'bookings_summary' => zeynart_compile_bookings_summary(),
            'booking_settings' => zeynart_get_booking_settings($store),
        ]);
    }

    case 'booking_update': {
        $id = trim((string)($data['id'] ?? ''));
        $op = trim((string)($data['op'] ?? 'confirm'));
        $all = zeynart_load_bookings(true);
        if ($op === 'delete') {
            $all = array_values(array_filter($all, static fn(array $b): bool => ($b['id'] ?? '') !== $id));
        } else {
            foreach ($all as $i => $b) {
                if (($b['id'] ?? '') === $id) {
                    $all[$i]['status'] = ($op === 'cancel') ? 'cancelled' : 'confirmed';
                    $all[$i]['updated_at'] = date('c');
                    break;
                }
            }
        }
        zeynart_save_bookings($all);
        api_json(true, [
            'message' => $op === 'delete' ? 'Görüşme randevu kaydı silindi.' : 'Görüşme randevu durumu güncellendi.',
            'bookings' => zeynart_load_bookings(true),
            'bookings_summary' => zeynart_compile_bookings_summary(),
        ]);
    }

    case 'booking_settings_save': {
        $in = is_array($data['booking_settings'] ?? null) ? $data['booking_settings'] : $data;
        $store['booking_settings'] = $in;
        $store['booking_settings'] = zeynart_get_booking_settings($store);
        save_store_or_fail($store);
        api_json(true, [
            'message' => '✓ 15 Dakika Online Keşif Görüşmesi Planlayıcı ayarları kaydedildi.',
            'booking_settings' => $store['booking_settings'],
        ]);
    }

    case 'proposal_run_automation_chain': {
        $idOrCode = trim((string)($data['id'] ?? $data['code'] ?? ''));
        if ($idOrCode === '') {
            api_json(false, ['error' => 'Teklif kimliği (ID veya kod) belirtilmedi.'], 422);
        }
        $opts = [
            'target_domain' => trim((string)($data['target_domain'] ?? '')),
            'annual_renewal_amount' => isset($data['annual_renewal_amount']) && $data['annual_renewal_amount'] !== ''
                ? (float)$data['annual_renewal_amount']
                : null,
            'signer_name' => trim((string)($data['signer_name'] ?? '')),
            'client_tax_office' => trim((string)($data['client_tax_office'] ?? '')),
            'client_tax_no' => trim((string)($data['client_tax_no'] ?? '')),
            'client_address' => trim((string)($data['client_address'] ?? '')),
        ];
        $res = zeynart_run_proposal_automation_chain($idOrCode, $opts, $store);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Otomasyon zinciri çalıştırılamadı.')], 422);
        }
        api_json(true, [
            'message' => '⚡ 4 Adımlı Otomasyon Zinciri tamamlandı! Sözleşme (' . $res['contract_no'] . '), Proje Takip Portalı, Uptime Kulesi ve +1 Yıl Yenileme Radarı aktif edildi.',
            'proposal' => $res['proposal'],
            'proposals' => $res['proposals'],
            'chain' => $res['chain'],
            'contract_no' => $res['contract_no'],
            'contract_url' => $res['contract_url'],
            'tracker' => $res['tracker'],
            'tracker_url' => $res['tracker_url'],
            'client_trackers' => $res['client_trackers'],
            'client_trackers_summary' => $res['client_trackers_summary'],
            'uptime_monitors' => $res['uptime_monitors'],
            'uptime_summary' => $res['uptime_summary'],
            'renewals' => $res['renewals'],
            'renewals_summary' => $res['renewals_summary'],
        ]);
    }

    case 'seo_topic_clusters_audit': {
        $force = !empty($data['force']);
        $report = zeynart_audit_topic_clusters($store, $force);
        api_json(true, [
            'report' => $report,
        ]);
    }

    case 'seo_topic_clusters_autofix': {
        $clusterId = trim((string)($data['cluster_id'] ?? 'all'));
        $limit = max(10, min(250, (int)($data['limit'] ?? 120)));
        if (function_exists('zeynart_capture_revision')) {
            zeynart_capture_revision($store, 'Konu Kümesi & İç Link Otomatik Örücü Öncesi');
        }
        $res = zeynart_autofix_topic_clusters($store, $clusterId, $limit);
        $cnt = (int)($res['fixed_count'] ?? 0);
        $msg = $cnt > 0
            ? ('🕸️ ' . $cnt . ' makaleye Pillar Hizmet bağlantısı ve küme içi kardeş makale linkleri otomatik örüldü!')
            : '✓ Seçili kümedeki tüm makalelerin Pillar ve iç link ağı zaten eksiksiz durumda.';
        api_json(true, [
            'message' => $msg,
            'fixed_count' => $cnt,
            'fixed_titles' => $res['fixed_titles'] ?? [],
            'report' => $res['audit'],
        ]);
    }

    case 'unused_files_scan': {
        $force = !empty($data['force']);
        $report = zeynart_scan_unused_project_files($store, $force);
        api_json(true, [
            'report' => $report,
            'unused_files_summary' => zeynart_get_unused_files_boot_summary($store),
        ]);
    }

    case 'unused_files_clean': {
        $paths = is_array($data['paths'] ?? null) ? $data['paths'] : [];
        if ($paths === []) {
            api_json(false, ['error' => 'Lütfen temizlenecek en az bir dosya seçin.'], 422);
        }
        $res = zeynart_cleanup_unused_project_files($paths, $store);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Temizleme işlemi başarısız oldu.')], 422);
        }
        $delCnt = (int)($res['deleted_count'] ?? 0);
        $freedKb = (float)($res['freed_kb'] ?? 0);
        $msg = $delCnt > 0
            ? ('🧹 ' . $delCnt . ' adet v4.0 ile bağlantısız eski/gereksiz dosya güvenle temizlendi (' . $freedKb . ' KB alan açıldı)!')
            : 'Seçilen dosyalar arasında silinebilir bağlantısız dosya bulunmadı.';
        api_json(true, [
            'message' => $msg,
            'deleted_count' => $delCnt,
            'deleted_files' => $res['deleted_files'] ?? [],
            'skipped_files' => $res['skipped_files'] ?? [],
            'freed_kb' => $freedKb,
            'report' => $res['report'],
            'unused_files_summary' => zeynart_get_unused_files_boot_summary($store),
            'security_audit_20' => zeynart_run_security_20_audit($store),
        ]);
    }

    case 'database_status_get': {
        api_json(true, [
            'database_status' => zeynart_collect_database_status(),
        ]);
    }

    case 'database_test_connection': {
        $prevCfg = zeynart_load_db_config(true);
        $driver = in_array(($data['driver'] ?? 'mysql'), ['mysql', 'sqlite'], true) ? (string)$data['driver'] : 'mysql';
        $testCfg = array_merge($prevCfg, [
            'driver' => $driver,
            'host' => trim((string)($data['host'] ?? ($prevCfg['host'] ?? 'localhost'))) ?: 'localhost',
            'port' => max(1, min(65535, (int)($data['port'] ?? ($prevCfg['port'] ?? 3306)))),
            'database' => trim((string)($data['database'] ?? ($prevCfg['database'] ?? ''))),
            'username' => trim((string)($data['username'] ?? ($prevCfg['username'] ?? ''))),
            'prefix' => zeynart_normalize_db_prefix((string)($data['prefix'] ?? ($prevCfg['prefix'] ?? 'zn_'))),
            'sqlite_file' => trim((string)($data['sqlite_file'] ?? ($prevCfg['sqlite_file'] ?? 'zeynart_database.sqlite'))) ?: 'zeynart_database.sqlite',
        ]);
        $inPass = (string)($data['password'] ?? '');
        if ($inPass !== '' && $inPass !== '••••••••••••') {
            $testCfg['password'] = $inPass;
        } elseif (!empty($data['clear_password'])) {
            $testCfg['password'] = '';
        }

        try {
            $tryCreate = !isset($data['auto_create_db']) || !empty($data['auto_create_db']);
            $pdo = zeynart_connect_pdo_with_config($testCfg, $tryCreate);
            zeynart_sql_ensure_schema($pdo, $testCfg);
            $srvVer = '';
            try {
                $srvVer = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            } catch (Throwable) {
                $srvVer = ($driver === 'sqlite') ? 'SQLite 3 WAL' : 'MySQL / MariaDB';
            }
            // Persist credentials without flipping enabled yet (until data is migrated or already enabled)
            $testCfg['enabled'] = !empty($prevCfg['enabled']);
            zeynart_save_db_config($testCfg);
            $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
            zeynart_audit_log('database_test', 'SQL veritabanı bağlantısı doğrulandı ve 4 kurumsal tablo şeması hazırlandı (' . strtoupper($driver) . ' · ' . $srvVer . ').', $actor, 'ok');
            api_json(true, [
                'message' => '✓ Bağlantı başarılı (' . $srvVer . ')! 4 kurumsal SQL tablosu (' . $testCfg['prefix'] . 'posts, ' . $testCfg['prefix'] . 'store_kv, ' . $testCfg['prefix'] . 'analytics_daily, ' . $testCfg['prefix'] . 'revisions) utf8mb4_unicode_ci olarak hazırlandı.',
                'server_version' => $srvVer,
                'database_status' => zeynart_collect_database_status(),
            ]);
        } catch (Throwable $e) {
            api_json(false, [
                'error' => $e->getMessage(),
                'database_status' => zeynart_collect_database_status(),
            ], 422);
        }
    }

    case 'database_migrate_all': {
        @set_time_limit(180);
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $driver = in_array(($data['driver'] ?? ''), ['mysql', 'sqlite'], true)
            ? (string)$data['driver']
            : (string)(zeynart_load_db_config()['driver'] ?? 'mysql');
        $inCfg = [
            'driver' => $driver,
        ];
        foreach (['host', 'port', 'database', 'username', 'password', 'prefix', 'sqlite_file', 'clear_password'] as $k) {
            if (array_key_exists($k, $data)) {
                $inCfg[$k] = $data[$k];
            }
        }
        try {
            $res = zeynart_migrate_all_json_to_sql($inCfg);
            $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
            zeynart_audit_log(
                'database_migrate',
                sprintf(
                    'Tüm sistem SQL veritabanına aktarıldı (%d makale, %d tablo koleksiyonu, %d analitik, %d revizyon — %.1f ms).',
                    (int)$res['migrated_posts'],
                    (int)$res['migrated_kv'],
                    (int)$res['migrated_analytics'],
                    (int)$res['migrated_revisions'],
                    (float)$res['elapsed_ms']
                ),
                $actor,
                'ok'
            );
            $freshStore = load_store(true);
            $freshBoot = function_exists('zeynart_v4_cached_boot_bundle') ? zeynart_v4_cached_boot_bundle($freshStore, true) : [];
            api_json(true, [
                'message' => sprintf(
                    '🚀 Göç tamamlandı! %d blog makalesi, %d sistem koleksiyonu, %d analitik kaydı ve %d revizyon %.0f ms içinde SQL tablolarına aktarıldı ve canlı SQL motoru aktif edildi!',
                    (int)$res['migrated_posts'],
                    (int)$res['migrated_kv'],
                    (int)$res['migrated_analytics'],
                    (int)$res['migrated_revisions'],
                    (float)$res['elapsed_ms']
                ),
                'migration' => $res,
                'database_status' => $res['db_status'],
                'turbo_engine' => [
                    'mode' => (string)($freshBoot['engine'] ?? 'pdo_mysql'),
                    'cache_hit' => false,
                    'sqlite_wal' => extension_loaded('pdo_sqlite'),
                    'sql_active' => true,
                ],
            ]);
        } catch (Throwable $e) {
            api_json(false, [
                'error' => $e->getMessage(),
                'database_status' => zeynart_collect_database_status(),
            ], 422);
        }
    }

    case 'database_cleanup_legacy_json': {
        @set_time_limit(120);
        $archiveFirst = !isset($data['archive_first']) || !empty($data['archive_first']);
        $res = zeynart_cleanup_legacy_json_files($archiveFirst);
        if (empty($res['ok'])) {
            api_json(false, [
                'error' => (string)($res['error'] ?? 'Eski JSON dosyaları temizlenemedi.'),
                'database_status' => zeynart_collect_database_status(),
            ], 422);
        }
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log(
            'database_cleanup_json',
            sprintf(
                '%d adet eski JSON dosyası güvenle temizlendi (%.2f MB disk alanı açıldı%s).',
                (int)$res['deleted_count'],
                (float)$res['freed_mb'],
                !empty($res['archive_filename']) ? ', güvenlik yedeği: ' . $res['archive_filename'] : ''
            ),
            $actor,
            'ok'
        );
        $freshStore = load_store(true);
        api_json(true, [
            'message' => sprintf(
                '🧹 %d adet eski JSON dosyası güvenle silindi ve %.2f MB disk alanı açıldı! Sistem artık sıfır JSON kalıntısıyla doğrudan SQL veritabanından çalışıyor.',
                (int)$res['deleted_count'],
                (float)$res['freed_mb']
            ),
            'deleted_count' => (int)$res['deleted_count'],
            'freed_mb' => (float)$res['freed_mb'],
            'archive_filename' => (string)($res['archive_filename'] ?? ''),
            'database_status' => $res['db_status'],
            'vault_backups' => zeynart_list_vault_backups(),
            'unused_files_summary' => zeynart_get_unused_files_boot_summary($freshStore),
        ]);
    }

    case 'database_download_sql': {
        @set_time_limit(180);
        $fname = 'zeynart-mysql-yedek-' . date('Y-m-d_His') . '.sql';
        $tmpSql = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'zn_dump_' . bin2hex(random_bytes(6)) . '.sql';
        zeynart_generate_mysql_dump_sql($tmpSql);
        if (!is_file($tmpSql)) {
            api_json(false, ['error' => '.SQL dökümü oluşturulamadı.'], 500);
        }
        $size = (int)@filesize($tmpSql);
        header_remove('Content-Type');
        header('Content-Type: application/sql; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . $size);
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($tmpSql);
        @unlink($tmpSql);
        exit;
    }

    case 'database_import_sql': {
        @set_time_limit(180);
        $sqlContent = '';
        if (!empty($_FILES['sql_file']['tmp_name']) && is_uploaded_file((string)$_FILES['sql_file']['tmp_name'])) {
            $ext = strtolower(pathinfo((string)($_FILES['sql_file']['name'] ?? ''), PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                api_json(false, ['error' => 'Yalnızca standart .sql veritabanı yedek dosyası yüklenebilir.'], 422);
            }
            $sqlContent = (string)@file_get_contents((string)$_FILES['sql_file']['tmp_name']);
        } elseif (!empty($data['sql_content']) && is_string($data['sql_content'])) {
            $sqlContent = $data['sql_content'];
        }
        if (trim($sqlContent) === '') {
            api_json(false, ['error' => 'İçe aktarılacak .sql dosyası boş veya okunamadı.'], 422);
        }
        try {
            $res = zeynart_import_mysql_dump_sql($sqlContent);
            if (empty($res['ok'])) {
                api_json(false, ['error' => (string)($res['error'] ?? '.SQL içe aktarımı başarısız oldu.')], 422);
            }
            $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
            zeynart_audit_log('database_import_sql', '.SQL veritabanı yedeği içe aktarıldı (' . (int)$res['executed_statements'] . ' SQL sorgusu işlendi).', $actor, 'ok');
            $freshStore = load_store(true);
            api_json(true, [
                'message' => '✓ .SQL veritabanı yedeği başarıyla içe aktarıldı (' . (int)$res['executed_statements'] . ' SQL ifadesi çalıştırıldı)!',
                'executed_statements' => (int)$res['executed_statements'],
                'database_status' => $res['db_status'],
                'store' => build_safe_store_for_client($freshStore),
                'summary' => compute_dashboard_summary($freshStore),
            ]);
        } catch (Throwable $e) {
            api_json(false, ['error' => '.SQL içe aktarım hatası: ' . $e->getMessage()], 422);
        }
    }

    case 'database_export_to_json': {
        @set_time_limit(180);
        $res = zeynart_export_sql_back_to_json();
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'JSON dışa aktarımı başarısız oldu.')], 422);
        }
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('database_export_json', 'SQL veritabanındaki veriler düz JSON dosyalarına geri aktarıldı (' . (int)$res['exported_posts'] . ' makale).', $actor, 'warn');
        api_json(true, [
            'message' => 'Veritabanındaki tüm içerik ve ' . (int)$res['exported_posts'] . ' makale düz JSON dosyalarına geri aktarıldı ve JSON modu etkinleştirildi.',
            'database_status' => $res['db_status'],
        ]);
    }

    /* ========================================================================
     * ZEYNART v5.1 — MERKEZİ OTA GÜNCELLEME HUB & FİLO KOMUTA MERKEZİ
     * ======================================================================== */
    case 'ota_overview': {
        api_json(true, [
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_hub_save_settings': {
        $hub = zeynart_ota_get_hub_state(true);
        $hub['enabled'] = !empty($data['enabled']);
        $hub['default_channel'] = in_array($data['default_channel'] ?? 'stable', ['stable', 'beta'], true)
            ? (string)$data['default_channel']
            : 'stable';
        $hub['require_https'] = !empty($data['require_https']);
        $hub['inline_push_fallback'] = !empty($data['inline_push_fallback']);
        $hub['retain_releases'] = max(2, min(10, (int)($data['retain_releases'] ?? 5)));
        zeynart_ota_save_hub_state($hub);
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('ota_hub_settings', 'Merkezi OTA Dağıtım Hub ayarları güncellendi.', $actor, 'ok');
        api_json(true, [
            'message' => '✓ Merkezi OTA Dağıtım Hub ayarları kaydedildi.',
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_hub_regenerate_keys': {
        $hub = zeynart_ota_get_hub_state(false);
        $kp = zeynart_ota_generate_rsa_keypair();
        if (empty($kp['ok'])) {
            api_json(false, ['error' => (string)($kp['error'] ?? 'RSA-2048 anahtar çifti üretilemedi.')], 422);
        }
        $hub['private_key_pem'] = $kp['private_key_pem'];
        $hub['public_key_pem'] = $kp['public_key_pem'];
        $hub['key_fingerprint'] = $kp['fingerprint'];
        $hub['key_created_at'] = date('c');
        zeynart_ota_save_hub_state($hub);
        zeynart_ota_append_log('keys_regenerated', 'Yeni RSA-2048 imzalama anahtar çifti oluşturuldu (' . $kp['fingerprint'] . ').', 'warn');
        api_json(true, [
            'message' => '🔐 Yeni RSA-2048 kriptografik imzalama anahtar çifti oluşturuldu. Mevcut istemciler için güncel Bağlantı Kodunu yenileyin.',
            'ota' => zeynart_ota_compile_overview(SITE_URL),
        ]);
    }

    case 'ota_build_release': {
        @set_time_limit(120);
        $res = zeynart_ota_build_release_package([
            'version' => (string)($data['version'] ?? ZEYNART_STUDIO_VERSION),
            'channel' => (string)($data['channel'] ?? 'stable'),
            'min_php' => (string)($data['min_php'] ?? '8.1.0'),
            'title' => (string)($data['title'] ?? ''),
            'changelog' => $data['changelog'] ?? '',
        ]);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'OTA sürüm paketi oluşturulamadı.')], 422);
        }
        $rel = $res['release'];
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('ota_build_release', sprintf('OTA çekirdek paketi oluşturuldu ve imzalandı: v%s (%d dosya, %.1f KB).', $rel['version'], $rel['files_count'], $rel['size_kb']), $actor, 'ok');
        api_json(true, [
            'message' => sprintf('📦 v%s (%s) çekirdek güncelleme paketi oluşturuldu ve RSA-2048 ile imzalandı (%d dosya · %.1f KB)!', $rel['version'], strtoupper($rel['channel']), $rel['files_count'], $rel['size_kb']),
            'release' => $rel,
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_delete_release': {
        $relId = trim((string)($data['id'] ?? ''));
        if ($relId === '') {
            api_json(false, ['error' => 'Silinecek sürüm kimliği belirtilmedi.'], 400);
        }
        $hub = zeynart_ota_get_hub_state(false);
        $relDir = zeynart_ota_releases_dir();
        $next = [];
        $deletedVer = '';
        foreach ($hub['releases'] as $r) {
            if (($r['id'] ?? '') === $relId) {
                $deletedVer = (string)($r['version'] ?? '');
                if (!empty($r['filename'])) {
                    $f = $relDir . '/' . basename((string)$r['filename']);
                    if (is_file($f)) {
                        @unlink($f);
                    }
                }
                continue;
            }
            $next[] = $r;
        }
        $hub['releases'] = $next;
        zeynart_ota_save_hub_state($hub);
        api_json(true, [
            'message' => 'Sürüm paketi silindi' . ($deletedVer !== '' ? ' (v' . $deletedVer . ')' : '') . '.',
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_download_release': {
        $relId = trim((string)($_GET['id'] ?? $data['id'] ?? ''));
        $hub = zeynart_ota_get_hub_state(false);
        $foundRel = null;
        foreach ($hub['releases'] as $r) {
            if ($relId === '' || ($r['id'] ?? '') === $relId || ($r['version'] ?? '') === $relId) {
                $foundRel = $r;
                break;
            }
        }
        if ($foundRel === null || empty($foundRel['filename'])) {
            api_json(false, ['error' => 'İndirilecek OTA paketi bulunamadı.'], 404);
        }
        $zipAbs = zeynart_ota_releases_dir() . '/' . basename((string)$foundRel['filename']);
        if (!is_file($zipAbs)) {
            api_json(false, ['error' => 'Paket dosyası diskte bulunamadı.'], 404);
        }
        header_remove('Content-Type');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename((string)$foundRel['filename']) . '"');
        header('Content-Length: ' . (int)@filesize($zipAbs));
        readfile($zipAbs);
        exit;
    }

    case 'ota_node_save': {
        $res = zeynart_ota_save_node($data, SITE_URL);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Müşteri sitesi kaydedilemedi.')], 422);
        }
        $node = $res['node'];
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('ota_node_save', 'OTA filosuna site kaydedildi: ' . $node['domain'], $actor, 'ok');
        api_json(true, [
            'message' => '✓ ' . $node['domain'] . ' OTA filosuna kaydedildi. Tek satırlık Bağlantı Kodunu kopyalayıp müşteri paneline yapıştırabilirsiniz.',
            'connection_token' => $res['connection_token'],
            'node_id' => $node['id'],
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_node_delete': {
        $id = trim((string)($data['id'] ?? ''));
        if ($id === '') {
            api_json(false, ['error' => 'Silinecek site kimliği belirtilmedi.'], 400);
        }
        $hub = zeynart_ota_get_hub_state(false);
        $removedDomain = '';
        $nextNodes = [];
        foreach ($hub['nodes'] as $n) {
            if (($n['id'] ?? '') === $id) {
                $removedDomain = (string)($n['domain'] ?? '');
                continue;
            }
            $nextNodes[] = $n;
        }
        $hub['nodes'] = $nextNodes;
        zeynart_ota_save_hub_state($hub);
        api_json(true, [
            'message' => ($removedDomain !== '' ? $removedDomain : 'Site') . ' OTA filosundan çıkarıldı.',
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_node_ping': {
        $id = trim((string)($data['id'] ?? ''));
        $res = zeynart_ota_ping_node($id);
        if (empty($res['ok'])) {
            api_json(false, [
                'error' => (string)($res['error'] ?? 'İstemciye ulaşılamadı.'),
                'ota' => zeynart_ota_compile_overview(SITE_URL),
            ], 422);
        }
        api_json(true, [
            'message' => '✓ Bağlantı aktif! Yüklü sürüm: v' . ($res['remote']['installed_version'] ?? '?') . ' · PHP ' . ($res['remote']['php_version'] ?? ''),
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_node_purge_cache': {
        $id = trim((string)($data['id'] ?? ''));
        $res = zeynart_ota_remote_purge_node_cache($id);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Uzak önbellek temizlenemedi.')], 422);
        }
        api_json(true, [
            'message' => '⚡ ' . $res['message'],
            'ota' => zeynart_ota_compile_overview(SITE_URL),
        ]);
    }

    case 'ota_push_single': {
        @set_time_limit(90);
        $id = trim((string)($data['id'] ?? ''));
        $relId = trim((string)($data['release_id'] ?? '')) ?: null;
        $res = zeynart_ota_push_update_to_node($id, SITE_URL, $relId);
        if (empty($res['ok'])) {
            api_json(false, [
                'error' => (string)($res['error'] ?? 'Uzak güncelleme başarısız oldu.'),
                'ota' => zeynart_ota_compile_overview(SITE_URL),
            ], 422);
        }
        api_json(true, [
            'message' => '🚀 ' . $res['message'],
            'result' => $res,
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_push_all': {
        @set_time_limit(300);
        $hub = zeynart_ota_get_hub_state(true);
        $relId = trim((string)($data['release_id'] ?? '')) ?: null;
        $onlyOutdated = !empty($data['only_outdated']);
        $latest = zeynart_ota_find_latest_release($hub, 'stable');
        if ($latest === null) {
            $autoBuild = zeynart_ota_build_release_package(['channel' => 'stable']);
            if (empty($autoBuild['ok'])) {
                api_json(false, ['error' => (string)($autoBuild['error'] ?? 'Önce bir OTA paketi oluşturun.')], 422);
            }
            $hub = zeynart_ota_get_hub_state(false);
            $latest = $autoBuild['release'];
        }
        $targetVer = (string)$latest['version'];

        $succeeded = 0;
        $failed = 0;
        $skipped = 0;
        $results = [];

        foreach ($hub['nodes'] as $n) {
            if (($n['license_status'] ?? 'active') !== 'active') {
                $skipped++;
                continue;
            }
            if ($onlyOutdated && !empty($n['installed_version']) && $n['installed_version'] !== '—' && !version_compare($targetVer, (string)$n['installed_version'], '>')) {
                $skipped++;
                continue;
            }
            $r = zeynart_ota_push_update_to_node((string)$n['id'], SITE_URL, $relId);
            $results[] = $r;
            if (!empty($r['ok'])) {
                $succeeded++;
            } else {
                $failed++;
            }
        }

        api_json(true, [
            'message' => sprintf('🚀 Toplu Filo Güncellemesi Tamamlandı: %d başarılı, %d hatalı, %d atlandı.', $succeeded, $failed, $skipped),
            'succeeded' => $succeeded,
            'failed' => $failed,
            'skipped' => $skipped,
            'results' => $results,
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_client_connect_token': {
        $rawToken = trim((string)($data['token'] ?? ''));
        $parsed = zeynart_ota_parse_connection_token($rawToken);
        if (empty($parsed['ok'])) {
            api_json(false, ['error' => (string)$parsed['error']], 422);
        }
        $cState = zeynart_ota_get_client_state();
        $cState['connected'] = true;
        $cState['hub_url'] = $parsed['hub_url'];
        $cState['node_id'] = $parsed['node_id'];
        $cState['license_key'] = $parsed['license_key'];
        $cState['node_secret'] = $parsed['node_secret'];
        $cState['channel'] = $parsed['channel'];
        $cState['hub_public_key_pem'] = $parsed['public_key_pem'];
        $cState['key_fingerprint'] = $parsed['key_fingerprint'];
        $cState['last_error'] = '';
        zeynart_ota_save_client_state($cState);

        $hs = zeynart_ota_client_check_for_updates(true);
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log('ota_client_connect', 'Merkezi OTA Hub bağlantısı kuruldu (' . $parsed['hub_url'] . ').', $actor, 'ok');

        $msg = !empty($hs['ok'])
            ? ('🔗 Merkezi OTA Hub bağlantısı doğrulandı! Anahtar: ' . $parsed['key_fingerprint'])
            : ('Bağlantı Kodu kaydedildi ancak Hub el sıkışması yanıt vermedi: ' . ($hs['error'] ?? ''));

        api_json(true, [
            'message' => $msg,
            'handshake_ok' => !empty($hs['ok']),
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_client_save_config': {
        $cState = zeynart_ota_get_client_state();
        $cState['channel'] = in_array($data['channel'] ?? 'stable', ['stable', 'beta'], true) ? (string)$data['channel'] : 'stable';
        $cState['auto_update'] = in_array($data['auto_update'] ?? 'off', ['off', 'patch', 'all'], true) ? (string)$data['auto_update'] : 'off';
        $cState['allow_remote_push'] = !empty($data['allow_remote_push']);
        if (array_key_exists('admin_dir', $data)) {
            $cState['admin_dir'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string)$data['admin_dir'])) ?? '';
        }

        $rawProt = $data['protected_paths'] ?? [];
        $protList = [];
        if (is_string($rawProt)) {
            foreach (preg_split('/[\r\n,]+/', $rawProt) ?: [] as $p) {
                $cp = trim(str_replace('\\', '/', $p), '/ ');
                if ($cp !== '') {
                    $protList[] = $cp;
                }
            }
        } elseif (is_array($rawProt)) {
            foreach ($rawProt as $p) {
                $cp = trim(str_replace('\\', '/', (string)$p), '/ ');
                if ($cp !== '') {
                    $protList[] = $cp;
                }
            }
        }
        $cState['protected_paths'] = array_values(array_unique($protList));
        zeynart_ota_save_client_state($cState);

        api_json(true, [
            'message' => '✓ İstemci otomatik güncelleme ve koruma sınırları kaydedildi.',
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_client_disconnect': {
        $cState = zeynart_ota_get_client_state();
        $cState['connected'] = false;
        $cState['node_secret'] = '';
        $cState['update_available'] = false;
        zeynart_ota_save_client_state($cState);
        api_json(true, [
            'message' => 'OTA Hub bağlantısı kesildi.',
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_client_check': {
        $res = zeynart_ota_client_check_for_updates(false);
        if (empty($res['ok'])) {
            api_json(false, [
                'error' => (string)($res['error'] ?? 'Güncelleme kontrolü başarısız oldu.'),
                'ota' => zeynart_ota_compile_overview(SITE_URL),
            ], 422);
        }
        $msg = !empty($res['update_available'])
            ? ('🚀 Yeni güncelleme hazır: v' . $res['latest_remote_version'] . ' (Mevcut: v' . $res['installed_version'] . ')')
            : ('✓ Sisteminiz güncel (v' . $res['installed_version'] . ').');
        api_json(true, [
            'message' => $msg,
            'check' => $res,
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_client_apply_update': {
        @set_time_limit(120);
        $res = zeynart_ota_client_pull_and_apply();
        if (empty($res['ok'])) {
            api_json(false, [
                'error' => (string)($res['error'] ?? 'OTA güncellemesi uygulanamadı.'),
                'ota' => zeynart_ota_compile_overview(SITE_URL),
            ], 422);
        }
        api_json(true, [
            'message' => $res['message'],
            'result' => $res,
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_client_rollback': {
        @set_time_limit(90);
        $wantFile = basename(trim((string)($data['filename'] ?? '')));
        $bDir = zeynart_ota_backups_dir();
        if ($wantFile === '') {
            $snaps = zeynart_ota_list_rollback_snapshots();
            if ($snaps === []) {
                api_json(false, ['error' => 'Geri dönülecek OTA öncesi yedek arşivi bulunamadı.'], 404);
            }
            $wantFile = (string)$snaps[0]['filename'];
        }
        if (!str_starts_with($wantFile, 'pre-ota-') || !str_ends_with($wantFile, '.zip')) {
            api_json(false, ['error' => 'Geçersiz geri dönüş arşiv adı.'], 422);
        }
        $res = zeynart_ota_restore_rollback_zip($bDir . '/' . $wantFile);
        if (empty($res['ok'])) {
            api_json(false, ['error' => (string)($res['error'] ?? 'Geri dönüş başarısız oldu.')], 422);
        }
        api_json(true, [
            'message' => sprintf('⏪ Sistem başarıyla v%s sürümüne geri döndürüldü (%d çekirdek dosya yenilendi).', $res['restored_version'], $res['restored_files_count']),
            'ota' => zeynart_ota_compile_overview(SITE_URL),
            'ota_summary' => zeynart_ota_boot_summary(),
        ]);
    }

    case 'ota_logs_clear': {
        atomic_write_json(zeynart_ota_logs_file(), [], false);
        api_json(true, [
            'message' => 'OTA dağıtım ve doğrulama kayıtları temizlendi.',
            'ota' => zeynart_ota_compile_overview(SITE_URL),
        ]);
    }

    default: {
        $sectorRes = zeynart_dispatch_sector_extension_action($action, $data, $store, $activeRole);
        if (is_array($sectorRes)) {
            $okFlag = array_key_exists('ok', $sectorRes) ? (bool)$sectorRes['ok'] : true;
            $httpCode = isset($sectorRes['_http_status']) ? (int)$sectorRes['_http_status'] : ($okFlag ? 200 : 422);
            unset($sectorRes['ok'], $sectorRes['_http_status']);
            api_json($okFlag, $sectorRes, $httpCode);
        }
        api_json(false, ['error' => 'Tanımsız API işlemi: ' . $action], 400);
    }
}
