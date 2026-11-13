<?php
declare(strict_types=1);

/**
 * ZEYNART DİJİTAL STÜDYO v4.0 — OTOMASYON ZİNCİRİ & KONU KÜMESİ (TOPIC CLUSTER) MOTORU
 * ------------------------------------------------------------------------------------
 * 1. Teklif -> Resmi Hizmet Sözleşmesi (A4 PDF) -> Proje Takip Portalı -> Uptime & Yenileme Zinciri
 * 2. Konu Kümesi (Topic Cluster) & İç Link Haritası Denetçisi + Otomatik İç Link Örücü
 */

require_once __DIR__ . '/config.php';

/**
 * Extracts a clean domain host from any raw domain/URL string or corporate email fallback.
 */
function zeynart_v4_extract_clean_domain(string $rawDomain, string $fallbackEmail = ''): string
{
    $cand = strtolower(trim($rawDomain));
    if ($cand !== '') {
        $cand = preg_replace('~^https?://~i', '', $cand) ?? $cand;
        $cand = trim(explode('/', $cand)[0] ?? '');
        $cand = preg_replace('/:\d+$/', '', $cand) ?? $cand;
        $cand = preg_replace('~^www\.~i', '', $cand) ?? $cand;
        if ($cand !== '' && str_contains($cand, '.') && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $cand)) {
            return $cand;
        }
    }

    $email = strtolower(trim($fallbackEmail));
    if ($email !== '' && str_contains($email, '@')) {
        $mailHost = substr(strrchr($email, '@') ?: '', 1);
        $freeProviders = [
            'gmail.com', 'googlemail.com', 'hotmail.com', 'outlook.com', 'live.com',
            'msn.com', 'yahoo.com', 'icloud.com', 'me.com', 'yandex.com', 'yandex.com.tr',
            'protonmail.com', 'proton.me', 'mail.com', 'windowslive.com', 'hotmail.com.tr',
        ];
        if ($mailHost !== '' && !in_array($mailHost, $freeProviders, true) && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $mailHost)) {
            return $mailHost;
        }
    }

    return '';
}

/**
 * Estimates project delivery date (YYYY-MM-DD) from human-readable Turkish delivery_time text.
 */
function zeynart_v4_estimate_delivery_date(string $deliveryTime): string
{
    $days = 21;
    if (preg_match_all('/\d+/', $deliveryTime, $m) && !empty($m[0])) {
        $nums = array_map('intval', $m[0]);
        $maxNum = max($nums);
        if (stripos($deliveryTime, 'hafta') !== false) {
            $days = max(7, min(180, $maxNum * 7));
        } elseif (stripos($deliveryTime, 'ay') !== false) {
            $days = max(15, min(365, $maxNum * 30));
        } else {
            // İş günü -> takvim günü dönüşümü (~1.35x)
            $days = max(5, min(180, (int)ceil($maxNum * 1.35)));
        }
    }
    return date('Y-m-d', strtotime('+' . $days . ' days'));
}

/**
 * Builds structured Client Portal milestones directly from Proposal line items.
 */
function zeynart_v4_build_milestones_from_proposal(array $prp): array
{
    $contractNo = (string)($prp['contract_no'] ?? $prp['proposal_no'] ?? 'Sözleşme');
    $milestones = [
        [
            'step' => 1,
            'title' => 'Sözleşme Onayı & Proje Başlangıcı (Kickoff)',
            'desc' => $contractNo . ' referanslı dijital hizmet sözleşmesi onaylandı; proje yol haritası ve teknik kurulum başlatıldı.',
            'status' => 'completed',
            'completed_at' => date('d.m.Y H:i'),
        ],
    ];

    $items = is_array($prp['items'] ?? null) ? array_values($prp['items']) : [];
    if (count($items) >= 2) {
        foreach (array_slice($items, 0, 6) as $idx => $item) {
            $title = trim((string)($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $desc = trim((string)($item['desc'] ?? ''));
            if ($desc === '') {
                $desc = 'Teklif kapsamındaki modül geliştirme, tasarım ve kalite kontrol süreci.';
            }
            $milestones[] = [
                'step' => count($milestones) + 1,
                'title' => $title,
                'desc' => $desc,
                'status' => $idx === 0 ? 'current' : 'upcoming',
                'completed_at' => '',
            ];
        }
    } else {
        $milestones[] = [
            'step' => 2,
            'title' => 'Kurumsal UI/UX Tasarım & Mimari Prototip',
            'desc' => 'Marka kimliğine uyumlu yüksek dönüşümlü arayüz ve mobil deneyim tasarımı.',
            'status' => 'current',
            'completed_at' => '',
        ];
        $milestones[] = [
            'step' => 3,
            'title' => 'Özel Yazılım, Yönetim Stüdyosu & İçerik Kurulumu',
            'desc' => 'Saf PHP 8+ performans mimarisi, yönetim stüdyosu ve SEO/GEO altyapısının kodlanması.',
            'status' => 'upcoming',
            'completed_at' => '',
        ];
    }

    $milestones[] = [
        'step' => count($milestones) + 1,
        'title' => 'PageSpeed, Güvenlik Testi & Canlı Yayın Teslimi',
        'desc' => 'SSL, LiteSpeed önbellek, 20/20 güvenlik taraması, Uptime Kulesi kaydı ve yönetici eğitimi.',
        'status' => 'upcoming',
        'completed_at' => '',
    ];

    return $milestones;
}

/**
 * Executes the 4-step Agency Automation Chain for a Proposal:
 * 1) Approves Proposal & generates Official Printable Contract (/sozlesme/{code}/)
 * 2) Creates/Updates Client Project Tracking Portal (/proje-takip/{code}/)
 * 3) Registers/Updates Uptime & SSL Monitor (when domain is available)
 * 4) Schedules +1 Year Hosting/SSL/Maintenance Renewal Radar item
 */
function zeynart_run_proposal_automation_chain(string $idOrCode, array $opts = [], ?array $store = null): array
{
    require_once __DIR__ . '/enterprise_cms_suite.php';
    require_once __DIR__ . '/agency_growth_suite.php';
    require_once __DIR__ . '/agency_powerhouse.php';

    $st = $store ?? (function_exists('load_store') ? load_store() : []);
    $needle = strtolower(trim($idOrCode));
    if ($needle === '') {
        return ['ok' => false, 'error' => 'Teklif kimliği veya kodu belirtilmedi.'];
    }

    $proposals = zeynart_load_proposals();
    $targetIdx = null;
    foreach ($proposals as $idx => $p) {
        if (
            strtolower((string)($p['id'] ?? '')) === $needle
            || strtolower((string)($p['code'] ?? '')) === $needle
        ) {
            $targetIdx = $idx;
            break;
        }
    }

    if ($targetIdx === null) {
        return ['ok' => false, 'error' => 'Teklif kaydı bulunamadı.'];
    }

    $prp = $proposals[$targetIdx];
    $nowSql = date('Y-m-d H:i:s');
    $prpCode = strtolower((string)($prp['code'] ?? ''));

    // Optional field updates passed during chain execution
    if (isset($opts['target_domain']) && trim((string)$opts['target_domain']) !== '') {
        $prp['target_domain'] = trim((string)$opts['target_domain']);
    }
    if (isset($opts['annual_renewal_amount']) && is_numeric($opts['annual_renewal_amount'])) {
        $prp['annual_renewal_amount'] = max(0, round((float)$opts['annual_renewal_amount'], 2));
    }
    if (isset($opts['client_tax_office']) && trim((string)$opts['client_tax_office']) !== '') {
        $prp['client_tax_office'] = mb_substr(trim((string)$opts['client_tax_office']), 0, 120, 'UTF-8');
    }
    if (isset($opts['client_tax_no']) && trim((string)$opts['client_tax_no']) !== '') {
        $prp['client_tax_no'] = mb_substr(trim((string)$opts['client_tax_no']), 0, 40, 'UTF-8');
    }
    if (isset($opts['client_address']) && trim((string)$opts['client_address']) !== '') {
        $prp['client_address'] = mb_substr(trim((string)$opts['client_address']), 0, 320, 'UTF-8');
    }

    // 1. APPROVE PROPOSAL & GENERATE DIGITAL CONTRACT NUMBER
    $signerName = trim((string)($opts['signer_name'] ?? ''));
    if ($signerName === '') {
        $signerName = trim((string)($prp['approved_by'] ?? ''));
    }
    if ($signerName === '') {
        $signerName = trim((string)($prp['client_name'] ?: ($prp['client_company'] ?: 'Kurumsal Müşteri Onayı')));
    }

    $prp['status'] = 'approved';
    if (empty($prp['approved_at'])) {
        $prp['approved_at'] = $nowSql;
    }
    $prp['approved_by'] = $signerName;
    if (!empty($opts['approval_note'])) {
        $prp['approval_note'] = mb_substr(trim((string)$opts['approval_note']), 0, 800, 'UTF-8');
    }
    if (empty($prp['approved_ip']) && function_exists('get_client_ip')) {
        $prp['approved_ip'] = get_client_ip();
    }

    $cleanCodeTail = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $prpCode) ?: 'ZYN4', -4));
    $contractNo = trim((string)($prp['contract_no'] ?? ''));
    if ($contractNo === '') {
        $contractNo = 'SZL-' . date('Y') . '-' . $cleanCodeTail;
    }
    $prp['contract_no'] = $contractNo;
    if (empty($prp['contract_date'])) {
        $prp['contract_date'] = date('Y-m-d');
    }

    $resolvedDomain = zeynart_v4_extract_clean_domain(
        (string)($prp['target_domain'] ?? ''),
        (string)($prp['client_email'] ?? '')
    );
    if ($resolvedDomain !== '' && empty($prp['target_domain'])) {
        $prp['target_domain'] = $resolvedDomain;
    }

    // 2. CREATE OR UPDATE CLIENT PROJECT TRACKING PORTAL (/proje-takip/{code}/)
    $trackers = zeynart_load_client_trackers(true);
    $foundTrackerIdx = null;
    foreach ($trackers as $tIdx => $trk) {
        if (
            strtolower((string)($trk['proposal_code'] ?? '')) === $prpCode
            || strtolower((string)($trk['code'] ?? '')) === $prpCode
        ) {
            $foundTrackerIdx = $tIdx;
            break;
        }
    }

    $scopeTitles = [];
    foreach ((array)($prp['items'] ?? []) as $it) {
        if (!empty($it['title'])) {
            $scopeTitles[] = trim((string)$it['title']);
        }
    }
    $scopeSummary = $scopeTitles !== []
        ? mb_substr(implode(' · ', array_slice($scopeTitles, 0, 4)), 0, 220, 'UTF-8')
        : (string)($prp['project_title'] ?? 'Kurumsal Dijital Dönüşüm Paketi');

    $liveUrl = $resolvedDomain !== '' ? ('https://' . $resolvedDomain . '/') : '';

    if ($foundTrackerIdx !== null) {
        $existingTrk = $trackers[$foundTrackerIdx];
        $existingTrk['proposal_code'] = $prpCode;
        if ($liveUrl !== '' && empty($existingTrk['live_url'])) {
            $existingTrk['live_url'] = $liveUrl;
        }
        if (empty($existingTrk['client_name']) && !empty($prp['client_name'])) {
            $existingTrk['client_name'] = (string)$prp['client_name'];
        }
        if (empty($existingTrk['client_company']) && !empty($prp['client_company'])) {
            $existingTrk['client_company'] = (string)$prp['client_company'];
        }
        $trackerItem = zeynart_normalize_client_tracker($existingTrk);
        $trackers[$foundTrackerIdx] = $trackerItem;
    } else {
        $trackerItem = zeynart_normalize_client_tracker([
            'code' => $prpCode,
            'client_name' => (string)($prp['client_name'] ?? ''),
            'client_company' => (string)($prp['client_company'] ?? ''),
            'client_phone' => (string)($prp['client_phone'] ?? ''),
            'client_email' => (string)($prp['client_email'] ?? ''),
            'project_title' => (string)($prp['project_title'] ?? 'Kurumsal Dijital Proje'),
            'package_scope' => $scopeSummary,
            'manager_name' => (string)($st['site']['name'] ?? 'Zeynart Proje Ekibi'),
            'start_date' => date('Y-m-d'),
            'estimated_delivery' => zeynart_v4_estimate_delivery_date((string)($prp['delivery_time'] ?? '15 – 20 İş Günü')),
            'live_url' => $liveUrl,
            'proposal_code' => $prpCode,
            'status' => 'active',
            'latest_update_note' => $contractNo . ' numaralı hizmet sözleşmesi onaylandı ve proje üretim akışı başlatıldı.',
            'milestones' => zeynart_v4_build_milestones_from_proposal($prp),
        ]);
        array_unshift($trackers, $trackerItem);
    }
    zeynart_save_client_trackers($trackers);

    // 3. REGISTER OR UPDATE UPTIME & SSL TOWER MONITOR
    $monitors = zeynart_load_uptime_monitors($st);
    $uptimeId = '';
    $uptimeStatus = 'domain_pending';
    if ($resolvedDomain !== '') {
        $normMon = zeynart_normalize_monitor_url('https://' . $resolvedDomain);
        if ($normMon !== null) {
            $monIdx = null;
            foreach ($monitors as $mIdx => $mon) {
                if (strtolower((string)($mon['domain'] ?? '')) === strtolower($normMon['domain'])) {
                    $monIdx = $mIdx;
                    break;
                }
            }
            if ($monIdx !== null) {
                $monitors[$monIdx]['enabled'] = true;
                if (empty($monitors[$monIdx]['client_name'])) {
                    $monitors[$monIdx]['client_name'] = (string)($prp['client_company'] ?: $prp['client_name']);
                }
                $uptimeId = (string)$monitors[$monIdx]['id'];
                $uptimeStatus = 'linked_existing';
            } else {
                $uptimeId = 'mon-' . substr(md5($normMon['domain']), 0, 10);
                $monitors[] = [
                    'id' => $uptimeId,
                    'url' => $normMon['url'],
                    'domain' => $normMon['domain'],
                    'client_name' => (string)($prp['client_company'] ?: ($prp['client_name'] ?: $normMon['domain'])),
                    'server_group' => 'Müşteri Projeleri',
                    'notes' => 'Otomatik Zincir: ' . ($prp['proposal_no'] ?? $prpCode) . ' · ' . $contractNo,
                    'enabled' => true,
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
                $uptimeStatus = 'created';
            }
            zeynart_save_uptime_monitors($monitors);
        }
    }

    // 4. REGISTER OR UPDATE +1 YEAR RENEWAL RADAR ENTRY
    $renewals = zeynart_load_client_renewals(true);
    $foundRnwIdx = null;
    $rnwDomainLabel = $resolvedDomain !== ''
        ? $resolvedDomain
        : (slugify((string)($prp['client_company'] ?: ($prp['client_name'] ?: $prpCode))) . '.proje');

    foreach ($renewals as $rIdx => $rRow) {
        $notesStr = (string)($rRow['notes'] ?? '');
        if (
            ($resolvedDomain !== '' && strtolower((string)($rRow['domain'] ?? '')) === strtolower($resolvedDomain))
            || ($prpCode !== '' && str_contains(strtolower($notesStr), strtolower($prpCode)))
        ) {
            $foundRnwIdx = $rIdx;
            break;
        }
    }

    $annualAmount = (float)($prp['annual_renewal_amount'] ?? 0);
    if ($annualAmount <= 0) {
        $grand = (float)($prp['grand_total'] ?? 0);
        $annualAmount = $grand > 0 ? round(max(3500.0, $grand * 0.20), 2) : 6500.0;
        $prp['annual_renewal_amount'] = $annualAmount;
    }

    $rnwCurrency = in_array((string)($prp['currency'] ?? 'TRY'), ['TRY', 'USD', 'EUR'], true)
        ? (string)$prp['currency']
        : 'TRY';

    if ($foundRnwIdx !== null) {
        $existingRnw = $renewals[$foundRnwIdx];
        if ($resolvedDomain !== '') {
            $existingRnw['domain'] = $resolvedDomain;
        }
        if ($annualAmount > 0) {
            $existingRnw['amount'] = $annualAmount;
        }
        $renewalItem = zeynart_normalize_renewal_item($existingRnw);
        $renewals[$foundRnwIdx] = $renewalItem;
    } else {
        $renewalItem = zeynart_normalize_renewal_item([
            'client_name' => (string)($prp['client_name'] ?? ''),
            'company' => (string)($prp['client_company'] ?? ''),
            'phone' => (string)($prp['client_phone'] ?? ''),
            'email' => (string)($prp['client_email'] ?? ''),
            'domain' => $rnwDomainLabel,
            'service_type' => 'Yıllık Hosting + SSL + Bakım Yenilemesi',
            'package_name' => (string)($prp['project_title'] ?? 'Kurumsal Dijital Paket'),
            'renewal_date' => date('Y-m-d', strtotime('+1 year')),
            'amount' => $annualAmount,
            'currency' => $rnwCurrency,
            'status' => 'active',
            'notes' => 'Teklif & Sözleşme Zinciri: ' . ($prp['proposal_no'] ?? $prpCode) . ' / ' . $contractNo . ' [' . $prpCode . ']',
        ]);
        $renewals[] = $renewalItem;
    }
    zeynart_save_client_renewals($renewals);

    $contractPath = '/sozlesme/' . $prpCode . '/';
    $trackerPath = '/proje-takip/' . $trackerItem['code'] . '/';

    $chainMeta = [
        'active' => true,
        'triggered_at' => $nowSql,
        'contract_no' => $contractNo,
        'contract_url' => $contractPath,
        'tracker_id' => $trackerItem['id'],
        'tracker_code' => $trackerItem['code'],
        'tracker_url' => $trackerPath,
        'uptime_id' => $uptimeId,
        'uptime_domain' => $resolvedDomain,
        'uptime_status' => $uptimeStatus,
        'renewal_id' => $renewalItem['id'],
        'renewal_date' => $renewalItem['renewal_date'],
        'renewal_amount' => $renewalItem['amount'],
        'renewal_currency' => $renewalItem['currency'],
    ];

    $prp['automation_chain'] = $chainMeta;
    $prp['updated_at'] = $nowSql;
    $proposals[$targetIdx] = zeynart_normalize_proposal($prp, $prp);
    zeynart_save_proposals($proposals);

    if (function_exists('zeynart_audit_log')) {
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? $signerName);
        zeynart_audit_log(
            'proposal_automation_chain',
            'Otomasyon zinciri tamamlandı: ' . $contractNo . ' -> Proje Portalı (' . $trackerItem['code'] . ') + Yenileme Radarı',
            $actor,
            'ok'
        );
    }

    $freshTrackers = zeynart_load_client_trackers(true);
    $freshRenewals = zeynart_load_client_renewals(true);
    $freshMonitors = zeynart_load_uptime_monitors($st);

    return [
        'ok' => true,
        'proposal' => $proposals[$targetIdx],
        'proposals' => $proposals,
        'chain' => $chainMeta,
        'contract_no' => $contractNo,
        'contract_url' => $contractPath,
        'tracker' => $trackerItem,
        'tracker_url' => $trackerPath,
        'client_trackers' => $freshTrackers,
        'client_trackers_summary' => zeynart_compile_client_trackers_summary($freshTrackers),
        'uptime_monitors' => $freshMonitors,
        'uptime_summary' => zeynart_compute_uptime_summary($freshMonitors, zeynart_get_uptime_tower_config($st)),
        'renewals' => $freshRenewals,
        'renewals_summary' => zeynart_compile_renewals_summary($freshRenewals),
    ];
}

/* ============================================================================
 * 2. KONU KÜMESİ (TOPIC CLUSTER) & İÇ LİNK HARİTASI DENETÇİSİ + OTOMATİK ÖRÜCÜ
 * ============================================================================ */

function zeynart_v4_cluster_cache_file(): string
{
    return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'zeynart_v4_topic_links_' . substr(md5(DATA_DIR), 0, 12) . '.json';
}

/**
 * Extracts normalized internal paths (e.g. "/hizmetler/web-tasarim/", "/blog/seo-rehberi/") from HTML.
 */
function zeynart_v4_extract_internal_paths(string $html, string $canonHost = 'zeynart.com'): array
{
    if ($html === '' || stripos($html, '<a') === false) {
        return [];
    }
    $paths = [];
    if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*["\']([^"\'#]+)~i', $html, $m)) {
        foreach ($m[1] as $rawHref) {
            $href = trim(html_entity_decode((string)$rawHref, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                continue;
            }
            if (preg_match('~^https?://([^/]+)(/.*)?$~i', $href, $hm)) {
                $hHost = strtolower(preg_replace('~^www\.~i', '', $hm[1]) ?? $hm[1]);
                if ($hHost !== strtolower(preg_replace('~^www\.~i', '', $canonHost) ?? $canonHost)) {
                    continue;
                }
                $href = $hm[2] ?? '/';
            } elseif (!str_starts_with($href, '/')) {
                continue;
            }
            $cleanPath = parse_url($href, PHP_URL_PATH);
            if (!is_string($cleanPath) || $cleanPath === '') {
                continue;
            }
            $norm = '/' . trim($cleanPath, '/');
            if ($norm !== '/') {
                $norm .= '/';
            }
            $paths[strtolower($norm)] = $norm;
        }
    }
    return array_values($paths);
}

/**
 * Builds the Pillar Hub definitions (Services + Core Hubs) with keyword matching rules.
 */
function zeynart_v4_build_pillar_hubs(array $store): array
{
    $servicesSlug = function_exists('zeynart_page_slug') ? (zeynart_page_slug($store, 'hizmetler') ?: 'hizmetler') : 'hizmetler';
    $palette = ['#14b8a6', '#38bdf8', '#a78bfa', '#f59e0b', '#fb7185', '#34d399', '#60a5fa', '#f472b6'];
    $hubs = [];

    $services = function_exists('zeynart_public_services') ? zeynart_public_services($store) : (array)($store['services'] ?? []);
    foreach (array_values($services) as $idx => $srv) {
        $slug = trim((string)($srv['slug'] ?? ''));
        $title = trim((string)($srv['title'] ?? ''));
        if ($slug === '' || $title === '') {
            continue;
        }
        $url = '/' . $servicesSlug . '/' . $slug . '/';
        $kwText = mb_strtolower($title . ' ' . str_replace('-', ' ', $slug) . ' ' . (string)($srv['summary'] ?? ''), 'UTF-8');
        $tokens = array_values(array_unique(array_filter(
            preg_split('/[^a-z0-9çğıöşü]+/u', $kwText) ?: [],
            static fn(string $w): bool => mb_strlen($w, 'UTF-8') >= 3
        )));

        // Boost domain-specific semantic keywords for accurate Turkish cluster affinity
        $slugLower = strtolower($slug);
        if (str_contains($slugLower, 'web') || str_contains($kwText, 'web')) {
            $tokens = array_merge($tokens, ['web', 'tasarım', 'tasarim', 'site', 'kurumsal', 'arayüz', 'ux', 'ui', 'php', 'cms', 'yazılım', 'mobil', 'responsive', 'hız', 'pagespeed']);
        }
        if (str_contains($slugLower, 'seo') || str_contains($kwText, 'seo') || str_contains($kwText, 'arama')) {
            $tokens = array_merge($tokens, ['seo', 'google', 'arama', 'sıralama', 'organik', 'anahtar', 'backlink', 'icerik', 'içerik', 'geo', 'yapay', 'zeka', 'harita', 'index']);
        }
        if (str_contains($slugLower, 'ticaret') || str_contains($kwText, 'ticaret') || str_contains($kwText, 'mağaza')) {
            $tokens = array_merge($tokens, ['ticaret', 'eticaret', 'mağaza', 'ödeme', 'sepet', 'ürün', 'satış', 'pazaryeri', 'kargo', 'b2b', 'b2c']);
        }
        if (str_contains($slugLower, 'kimlik') || str_contains($slugLower, 'marka') || str_contains($kwText, 'marka') || str_contains($kwText, 'logo')) {
            $tokens = array_merge($tokens, ['marka', 'logo', 'kurumsal', 'kimlik', 'tasarım', 'renk', 'tipografi', 'katalog', 'baskı', 'imaj']);
        }
        if (str_contains($slugLower, 'sosyal') || str_contains($slugLower, 'reklam') || str_contains($slugLower, 'dijital') || str_contains($kwText, 'pazarlama')) {
            $tokens = array_merge($tokens, ['sosyal', 'medya', 'instagram', 'reklam', 'ads', 'kampanya', 'dijital', 'pazarlama', 'etkileşim', 'video', 'reels']);
        }

        $hubs[] = [
            'id' => 'pillar-' . $slug,
            'slug' => $slug,
            'title' => $title,
            'url' => $url,
            'color' => $palette[$idx % count($palette)],
            'keywords' => array_values(array_unique($tokens)),
        ];
    }

    if ($hubs === []) {
        $hubs[] = [
            'id' => 'pillar-hizmetler',
            'slug' => 'hizmetler',
            'title' => 'Kurumsal Dijital Hizmetler & Çözümler',
            'url' => '/' . $servicesSlug . '/',
            'color' => '#14b8a6',
            'keywords' => ['web', 'dijital', 'seo', 'tasarım', 'yazılım', 'marka'],
        ];
    }

    return $hubs;
}

/**
 * Assigns a blog post to its best-matching Pillar Hub.
 */
function zeynart_v4_match_post_to_pillar(array $postMeta, array $hubs): array
{
    $haystack = mb_strtolower(
        (string)($postMeta['title'] ?? '') . ' '
        . (string)($postMeta['category'] ?? '') . ' '
        . implode(' ', (array)($postMeta['categories'] ?? [])) . ' '
        . str_replace('-', ' ', (string)($postMeta['slug'] ?? '')),
        'UTF-8'
    );

    $bestHub = $hubs[0];
    $bestScore = -1;

    foreach ($hubs as $idx => $hub) {
        $score = 0;
        foreach ($hub['keywords'] as $kw) {
            if ($kw !== '' && mb_strpos($haystack, $kw, 0, 'UTF-8') !== false) {
                $score += (mb_strlen($kw, 'UTF-8') >= 5) ? 3 : 2;
            }
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestHub = $hub;
        }
    }

    // Deterministic balanced fallback when a generic news/tech post has 0 keyword overlap
    if ($bestScore <= 0 && count($hubs) > 1) {
        $hashIdx = abs(crc32((string)($postMeta['slug'] ?? ''))) % count($hubs);
        $bestHub = $hubs[$hashIdx];
    }

    return $bestHub;
}

/**
 * Loads (or incrementally refreshes) the fast per-post internal link map.
 */
function zeynart_v4_get_post_links_index(array $publishedPosts, string $canonHost, bool $forceRefresh = false): array
{
    $cacheFile = zeynart_v4_cluster_cache_file();
    $cached = [];
    if (!$forceRefresh && is_file($cacheFile)) {
        $dec = json_decode((string)@file_get_contents($cacheFile), true);
        if (is_array($dec)) {
            $cached = $dec;
        }
    }

    $nextCache = [];
    $dirty = false;

    if (zeynart_is_sql_active() && ($pdo = zeynart_get_pdo()) !== null) {
        $tPosts = zeynart_sql_table('posts');
        $metaMap = [];
        $stMeta = $pdo->query("SELECT slug, updated_at FROM `{$tPosts}`");
        while ($mr = $stMeta->fetch(PDO::FETCH_ASSOC)) {
            $metaMap[(string)$mr['slug']] = strtotime((string)($mr['updated_at'] ?? '')) ?: 1;
        }

        $missingSlugs = [];
        foreach ($publishedPosts as $p) {
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($p['slug'] ?? '')) ?? '';
            if ($slug === '') {
                continue;
            }
            $mtime = (int)($metaMap[$slug] ?? 1);
            if (!$forceRefresh && isset($cached[$slug]) && is_array($cached[$slug]) && (int)($cached[$slug]['m'] ?? -1) === $mtime) {
                $nextCache[$slug] = $cached[$slug];
            } else {
                $missingSlugs[$slug] = $mtime;
            }
        }

        if ($missingSlugs !== []) {
            $dirty = true;
            $stAll = $pdo->query("SELECT slug, post_json FROM `{$tPosts}`");
            while ($pr = $stAll->fetch(PDO::FETCH_ASSOC)) {
                $sl = (string)$pr['slug'];
                if (!isset($missingSlugs[$sl])) {
                    continue;
                }
                $links = [];
                $hasWoven = false;
                $postArr = json_decode((string)$pr['post_json'], true);
                if (is_array($postArr)) {
                    $html = (string)($postArr['content_html'] ?? '');
                    $links = zeynart_v4_extract_internal_paths($html, $canonHost);
                    $hasWoven = str_contains($html, 'data-cluster-woven="1"');
                }
                $nextCache[$sl] = [
                    'm' => $missingSlugs[$sl],
                    'l' => $links,
                    'w' => $hasWoven ? 1 : 0,
                ];
            }
        }
    } else {
        foreach ($publishedPosts as $p) {
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($p['slug'] ?? '')) ?? '';
            if ($slug === '') {
                continue;
            }
            $pFile = POSTS_DIR . '/' . $slug . '.json';
            $mtime = is_file($pFile) ? (int)@filemtime($pFile) : 0;

            if (!$forceRefresh && isset($cached[$slug]) && is_array($cached[$slug]) && (int)($cached[$slug]['m'] ?? -1) === $mtime) {
                $nextCache[$slug] = $cached[$slug];
                continue;
            }

            $dirty = true;
            $links = [];
            $hasWoven = false;
            if ($mtime > 0) {
                $rawJson = (string)@file_get_contents($pFile);
                if ($rawJson !== '') {
                    $postArr = json_decode($rawJson, true);
                    if (is_array($postArr)) {
                        $html = (string)($postArr['content_html'] ?? '');
                        $links = zeynart_v4_extract_internal_paths($html, $canonHost);
                        $hasWoven = str_contains($html, 'data-cluster-woven="1"');
                    }
                }
            }

            $nextCache[$slug] = [
                'm' => $mtime,
                'l' => $links,
                'w' => $hasWoven ? 1 : 0,
            ];
        }
    }

    if ($dirty || count($nextCache) !== count($cached)) {
        @file_put_contents($cacheFile, json_encode($nextCache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    return $nextCache;
}

/**
 * Full Topic Cluster & Internal Link Graph Audit for Google & SEO Center.
 */
function zeynart_audit_topic_clusters(array $store, bool $forceRefresh = false): array
{
    $t0 = hrtime(true);
    $canonDomain = trim((string)($store['seo']['canonical_domain'] ?? 'https://zeynart.com'));
    $canonHost = (string)(parse_url($canonDomain, PHP_URL_HOST) ?: 'zeynart.com');
    $blogSlug = function_exists('zeynart_page_slug') ? (zeynart_page_slug($store, 'blog') ?: 'blog') : 'blog';
    $servicesSlug = function_exists('zeynart_page_slug') ? (zeynart_page_slug($store, 'hizmetler') ?: 'hizmetler') : 'hizmetler';

    $hubs = zeynart_v4_build_pillar_hubs($store);
    $blogIndex = function_exists('load_blog_index') ? load_blog_index() : [];

    $publishedPosts = [];
    foreach ($blogIndex as $row) {
        if (is_array($row) && ($row['status'] ?? 'published') === 'published' && !empty($row['slug'])) {
            $publishedPosts[] = $row;
        }
    }

    $linkMap = zeynart_v4_get_post_links_index($publishedPosts, $canonHost, $forceRefresh);

    // Precompute inbound link counts to every /blog/{slug}/ and /hizmetler/{slug}/
    $inboundCounts = [];
    $totalInternalEdges = 0;

    foreach ($hubs as $h) {
        $inboundCounts[strtolower($h['url'])] = 0;
    }
    foreach ($publishedPosts as $p) {
        $bUrl = '/' . $blogSlug . '/' . $p['slug'] . '/';
        $inboundCounts[strtolower($bUrl)] = 0;
    }

    foreach ($linkMap as $srcSlug => $entry) {
        $srcBlogUrl = strtolower('/' . $blogSlug . '/' . $srcSlug . '/');
        foreach ((array)($entry['l'] ?? []) as $targetPath) {
            $tKey = strtolower((string)$targetPath);
            if ($tKey === $srcBlogUrl) {
                continue;
            }
            $totalInternalEdges++;
            $inboundCounts[$tKey] = (int)($inboundCounts[$tKey] ?? 0) + 1;
        }
    }

    // Group posts into Pillar Clusters & evaluate link health
    $clusterBuckets = [];
    foreach ($hubs as $h) {
        $clusterBuckets[$h['id']] = [
            'id' => $h['id'],
            'slug' => $h['slug'],
            'title' => $h['title'],
            'pillar_url' => $h['url'],
            'color' => $h['color'],
            'post_count' => 0,
            'pillar_linked_count' => 0,
            'missing_pillar_count' => 0,
            'orphan_count' => 0,
            'pillar_inbound_links' => (int)($inboundCounts[strtolower($h['url'])] ?? 0),
            'health_pct' => 100,
            'sample_posts' => [],
        ];
    }

    $evaluatedPosts = [];
    $pillarLinkedTotal = 0;
    $missingPillarTotal = 0;
    $zeroOutboundTotal = 0;
    $zeroInboundTotal = 0;
    $trueOrphanTotal = 0;

    $servicesPrefix = '/' . strtolower($servicesSlug) . '/';

    foreach ($publishedPosts as $p) {
        $slug = (string)$p['slug'];
        $postUrl = '/' . $blogSlug . '/' . $slug . '/';
        $hub = zeynart_v4_match_post_to_pillar($p, $hubs);
        $cid = $hub['id'];
        $pLinks = (array)($linkMap[$slug]['l'] ?? []);

        $outCount = count($pLinks);
        $inCount = (int)($inboundCounts[strtolower($postUrl)] ?? 0);

        $linksExactPillar = false;
        $linksAnyService = false;
        foreach ($pLinks as $lnk) {
            $lLow = strtolower((string)$lnk);
            if ($lLow === strtolower($hub['url'])) {
                $linksExactPillar = true;
                $linksAnyService = true;
                break;
            }
            if (str_starts_with($lLow, $servicesPrefix) && strlen($lLow) > strlen($servicesPrefix)) {
                $linksAnyService = true;
            }
        }

        $hasPillarLink = $linksExactPillar || $linksAnyService;
        if ($hasPillarLink) {
            $pillarLinkedTotal++;
            $clusterBuckets[$cid]['pillar_linked_count']++;
        } else {
            $missingPillarTotal++;
            $clusterBuckets[$cid]['missing_pillar_count']++;
        }

        if ($outCount === 0) {
            $zeroOutboundTotal++;
        }
        if ($inCount === 0) {
            $zeroInboundTotal++;
        }
        $isOrphan = ($outCount === 0 && $inCount === 0);
        if ($isOrphan) {
            $trueOrphanTotal++;
            $clusterBuckets[$cid]['orphan_count']++;
        }

        $clusterBuckets[$cid]['post_count']++;
        if (count($clusterBuckets[$cid]['sample_posts']) < 8) {
            $clusterBuckets[$cid]['sample_posts'][] = [
                'slug' => $slug,
                'title' => (string)($p['title'] ?? $slug),
                'url' => $postUrl,
                'has_pillar' => $hasPillarLink,
                'out' => $outCount,
                'in' => $inCount,
            ];
        }

        $statusTag = 'healthy';
        if ($isOrphan) {
            $statusTag = 'orphan';
        } elseif (!$hasPillarLink) {
            $statusTag = 'missing_pillar';
        } elseif ($inCount === 0) {
            $statusTag = 'low_inbound';
        }

        $evaluatedPosts[] = [
            'slug' => $slug,
            'title' => (string)($p['title'] ?? $slug),
            'category' => (string)($p['category'] ?? 'Genel'),
            'url' => $postUrl,
            'cluster_id' => $cid,
            'cluster_title' => $hub['title'],
            'cluster_color' => $hub['color'],
            'pillar_url' => $hub['url'],
            'outbound_count' => $outCount,
            'inbound_count' => $inCount,
            'has_pillar_link' => $hasPillarLink,
            'is_orphan' => $isOrphan,
            'status' => $statusTag,
        ];
    }

    $clustersOut = [];
    foreach ($clusterBuckets as $cid => $cb) {
        if ($cb['post_count'] > 0) {
            $pillarRatio = $cb['pillar_linked_count'] / $cb['post_count'];
            $nonOrphanRatio = ($cb['post_count'] - $cb['orphan_count']) / $cb['post_count'];
            $cb['health_pct'] = (int)round(($pillarRatio * 65 + $nonOrphanRatio * 35));
        } else {
            $cb['health_pct'] = 100;
        }
        $clustersOut[] = $cb;
    }

    $totalPosts = count($publishedPosts);
    if ($totalPosts > 0) {
        $pillarCoveragePct = ($pillarLinkedTotal / $totalPosts) * 100.0;
        $connectedPct = (($totalPosts - $trueOrphanTotal) / $totalPosts) * 100.0;
        $inboundCoveragePct = (($totalPosts - $zeroInboundTotal) / $totalPosts) * 100.0;
        $overallScore = (int)round(($pillarCoveragePct * 0.50) + ($connectedPct * 0.30) + ($inboundCoveragePct * 0.20));
    } else {
        $overallScore = 100;
    }
    $overallScore = max(0, min(100, $overallScore));

    // Sort actionable posts: orphans first, then missing_pillar, then low_inbound, then healthy
    $priorityOrder = ['orphan' => 0, 'missing_pillar' => 1, 'low_inbound' => 2, 'healthy' => 3];
    usort($evaluatedPosts, static function (array $a, array $b) use ($priorityOrder): int {
        $pa = $priorityOrder[$a['status']] ?? 9;
        $pb = $priorityOrder[$b['status']] ?? 9;
        if ($pa !== $pb) {
            return $pa <=> $pb;
        }
        return ($a['outbound_count'] + $a['inbound_count']) <=> ($b['outbound_count'] + $b['inbound_count']);
    });

    $elapsedMs = round((hrtime(true) - $t0) / 1_000_000, 1);

    return [
        'score' => $overallScore,
        'scan_ms' => $elapsedMs,
        'summary' => [
            'total_pillars' => count($clustersOut),
            'total_posts' => $totalPosts,
            'pillar_linked_posts' => $pillarLinkedTotal,
            'missing_pillar_posts' => $missingPillarTotal,
            'orphan_posts' => $trueOrphanTotal,
            'zero_outbound_posts' => $zeroOutboundTotal,
            'zero_inbound_posts' => $zeroInboundTotal,
            'total_internal_edges' => $totalInternalEdges,
            'avg_links_per_post' => $totalPosts > 0 ? round($totalInternalEdges / $totalPosts, 2) : 0.0,
        ],
        'clusters' => $clustersOut,
        'posts' => array_slice($evaluatedPosts, 0, 160),
        'total_actionable' => $missingPillarTotal + $trueOrphanTotal,
    ];
}

/**
 * 1-Click Automatic Topic Cluster & Internal Link Weaver:
 * Connects orphan / unlinked blog articles to their Pillar Service Page + 2 sibling cluster articles.
 */
function zeynart_autofix_topic_clusters(array $store, string $clusterFilter = 'all', int $batchLimit = 100): array
{
    $batchLimit = max(5, min(250, $batchLimit));
    $canonDomain = trim((string)($store['seo']['canonical_domain'] ?? 'https://zeynart.com'));
    $canonHost = (string)(parse_url($canonDomain, PHP_URL_HOST) ?: 'zeynart.com');
    $blogSlug = function_exists('zeynart_page_slug') ? (zeynart_page_slug($store, 'blog') ?: 'blog') : 'blog';
    $servicesSlug = function_exists('zeynart_page_slug') ? (zeynart_page_slug($store, 'hizmetler') ?: 'hizmetler') : 'hizmetler';
    $servicesPrefix = '/' . strtolower($servicesSlug) . '/';

    $hubs = zeynart_v4_build_pillar_hubs($store);
    $hubById = [];
    foreach ($hubs as $h) {
        $hubById[$h['id']] = $h;
    }

    $blogIndex = function_exists('load_blog_index') ? load_blog_index(true) : [];
    $publishedByCluster = [];
    $indexPosBySlug = [];

    foreach ($blogIndex as $idx => $row) {
        if (!is_array($row) || ($row['status'] ?? 'published') !== 'published' || empty($row['slug'])) {
            continue;
        }
        $slug = (string)$row['slug'];
        $indexPosBySlug[$slug] = $idx;
        $hub = zeynart_v4_match_post_to_pillar($row, $hubs);
        $publishedByCluster[$hub['id']][] = $row;
    }

    $fixedCount = 0;
    $fixedTitles = [];
    $indexModified = false;

    foreach ($publishedByCluster as $cid => $clusterPosts) {
        if ($clusterFilter !== '' && $clusterFilter !== 'all' && $cid !== $clusterFilter) {
            continue;
        }
        if ($fixedCount >= $batchLimit) {
            break;
        }

        $hub = $hubById[$cid] ?? $hubs[0];
        $pillarUrl = $hub['url'];
        $pillarTitle = $hub['title'];
        $countInCluster = count($clusterPosts);

        foreach ($clusterPosts as $pos => $pMeta) {
            if ($fixedCount >= $batchLimit) {
                break;
            }
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$pMeta['slug']) ?? '';
            if ($slug === '') {
                continue;
            }
            $pFile = POSTS_DIR . '/' . $slug . '.json';
            $postArr = get_post($slug);
            if (!is_array($postArr)) {
                continue;
            }

            $html = (string)($postArr['content_html'] ?? '');
            if (str_contains($html, 'data-cluster-woven="1"')) {
                continue;
            }

            $existingLinks = zeynart_v4_extract_internal_paths($html, $canonHost);
            $hasPillar = false;
            foreach ($existingLinks as $lnk) {
                $lLow = strtolower($lnk);
                if ($lLow === strtolower($pillarUrl) || (str_starts_with($lLow, $servicesPrefix) && strlen($lLow) > strlen($servicesPrefix))) {
                    $hasPillar = true;
                    break;
                }
            }

            if ($hasPillar && count($existingLinks) >= 2) {
                continue;
            }

            // Pick up to 2 circular sibling posts in the same cluster so every post also receives inbound links!
            $siblings = [];
            if ($countInCluster > 1) {
                $sib1 = $clusterPosts[($pos + 1) % $countInCluster];
                if (($sib1['slug'] ?? '') !== $slug) {
                    $siblings[] = $sib1;
                }
                if ($countInCluster > 2) {
                    $sib2 = $clusterPosts[($pos + 2) % $countInCluster];
                    if (($sib2['slug'] ?? '') !== $slug && ($sib2['slug'] ?? '') !== ($sib1['slug'] ?? '')) {
                        $siblings[] = $sib2;
                    }
                }
            }

            $boxHtml = "\n" . '<aside class="editorial-cluster-box" data-cluster-woven="1">'
                . '<h3>İlgili Uzmanlık &amp; Rehber Kaynaklar</h3>'
                . '<ul>'
                . '<li><strong>Kurumsal Çözüm Merkezimiz:</strong> <a href="' . e($pillarUrl) . '">' . e($pillarTitle) . '</a> kapsamındaki profesyonel süreçlerimizi ve referans mimarilerimizi inceleyin.</li>';

            foreach ($siblings as $sib) {
                $sUrl = '/' . $blogSlug . '/' . $sib['slug'] . '/';
                $sTitle = trim((string)($sib['title'] ?? $sib['slug']));
                $boxHtml .= '<li><strong>İlgili Rehber:</strong> <a href="' . e($sUrl) . '">' . e($sTitle) . '</a></li>';
            }
            $boxHtml .= '</ul></aside>';

            $postArr['content_html'] = rtrim($html) . $boxHtml;
            $postArr['updated_at'] = date('Y-m-d');
            if (!isset($postArr['seo']) || !is_array($postArr['seo'])) {
                $postArr['seo'] = [];
            }
            $postArr['seo']['seo_score'] = max((int)($postArr['seo']['seo_score'] ?? 92), 96);

            atomic_write_json($pFile, $postArr, false);

            if (isset($indexPosBySlug[$slug])) {
                $blogIndex[$indexPosBySlug[$slug]]['seo_score'] = (int)$postArr['seo']['seo_score'];
                $indexModified = true;
            }

            $fixedCount++;
            if (count($fixedTitles) < 12) {
                $fixedTitles[] = (string)($pMeta['title'] ?? $slug);
            }
        }
    }

    if ($indexModified && function_exists('save_blog_index')) {
        save_blog_index($blogIndex);
    }

    if ($fixedCount > 0 && function_exists('zeynart_audit_log')) {
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log(
            'seo_cluster_autofix',
            $fixedCount . ' makaleye Konu Kümesi (Pillar + İlgili Rehber) iç link ağı otomatik örüldü.',
            $actor,
            'ok'
        );
    }

    $freshAudit = zeynart_audit_topic_clusters($store, true);

    return [
        'fixed_count' => $fixedCount,
        'fixed_titles' => $fixedTitles,
        'audit' => $freshAudit,
    ];
}

/* ============================================================================
 * 4. ULTRA-TURBO BOOT ENGINE & ADAPTIVE SQLITE WAL / MICRO-INDEX
 * ============================================================================ */

function zeynart_v4_get_version_history(): array
{
    return [
        [
            'version' => 'v5.2',
            'status' => 'active',
            'badge' => 'AKTİF SÜRÜM',
            'title' => 'Çoklu Sektör Eklenti Kancası, Vitrin Koruma Kalkanı & Dinamik /x/ Klasör Eşleme',
            'summary' => 'Emlak, Mücevher, Havuz/Kulüp ve B2B gibi farklı sektörlerdeki veya yönetim klasörü /x/ olarak adlandırılmış sitelerin vitrin tasarımını ve özel modüllerini %100 koruyarak çekirdek OTA güncellemesi yapan evrensel mimari.',
            'items' => [
                'Çoklu Sektör Eklenti Kancası (includes/extensions/ & /admin/extensions/ + __STUDIO_CORE JS köprüsü)',
                'Sıkı Vitrin & Sözlük Koruması (site_header.php, site_footer.php, icons.php, i18n.php asla ezilmez)',
                'Fiziksel /x/ veya /yonetim/ klasör tespiti ve RSA-2048 doğrulama sonrası dinamik OTA klasör eşleme',
                '1 karakter dahil (/x/) sanal gizli giriş uzantısı desteği ve %100 dinamik White-Label panel başlıkları',
                'Filo Komuta Merkezinde bağlı her sitenin özel panel yolunu (/x/) ve aktif sektör modülünü canlı izleme',
            ],
        ],
        [
            'version' => 'v5.1',
            'status' => 'released',
            'badge' => 'MERKEZİ OTA HUB',
            'title' => 'Merkezi OTA (Over-The-Air) Güncelleme Hub & Filo Komuta Merkezi',
            'summary' => 'Ana ajans merkezi (zeynart.com) güncellendiğinde bağlı tüm müşteri sitelerinin sayfa tasarımına, görsellerine ve verilerine dokunmadan kriptografik imzalı tek tıkla / otomatik çekirdek güncelleme altyapısı.',
            'items' => [
                'Merkezi OTA Dağıtım & Lisans Hub (zeynart.com üzerinden tüm bağlı sitelere tek tıkla sürüm gönderme)',
                'OpenSSL RSA-2048 asimetrik paket imzalama + SHA-256 bütünlük ve HMAC-SHA256 Replay koruması',
                'Tek satırlık Bağlantı Kodu (ZYN5-OTA1:...) ile yeni müşteri sitelerini saniyeler içinde filoya bağlama',
                'Müşteri tasarımı (pages/, site.css) ve verilerini (data/, uploads/, MySQL) %100 koruyan çekirdek sınırı',
                'Güncelleme öncesi otomatik geri dönüş arşivi (pre-ota-*.zip), öz-denetim ve hata anında Auto-Rollback',
            ],
        ],
        [
            'version' => 'v5.0',
            'status' => 'released',
            'badge' => 'MYSQL MOTORU',
            'title' => 'Kurumsal PDO MySQL Motoru, Tek Tıkla Göç & .SQL Yedekleme',
            'summary' => 'Binlerce ayrı JSON dosyası yerine indekslenmiş PDO MySQL / MariaDB (InnoDB utf8mb4) veritabanı mimarisi, tek tıkla veritabanı göç sihirbazı, eski JSON temizleyici ve tek dosyalı .SQL yedekleme.',
            'items' => [
                'Plesk & cPanel uyumlu PDO MySQL / MariaDB (InnoDB utf8mb4) + SQLite WAL hibrit veri motoru',
                '1.681+ blog makalesi, sayfalar, CRM, analitik ve 5 revizyonu tek tıkla SQL tablolarına taşıma',
                'Doğrulamalı Eski JSON Temizleyici ile binlerce ayrı .json dosyasını güvenle sıfırlama',
                'Yedekleme Kasasında 1.684 JSON yerine tek dosyalı .SQL dökümü (Plesk & cPanel uyumlu Dışa/İçe Aktar)',
            ],
        ],
        [
            'version' => 'v4.0',
            'status' => 'released',
            'badge' => 'TURBO ÇEKİRDEK',
            'title' => 'Ultra-Turbo Motor, Code-Splitting & Otomasyon Zinciri',
            'summary' => 'Modüler tembel JS yükleme (6 paket), milisaniyelik Ultra-Turbo Boot / SQLite WAL indeks motoru, 4 adımlı teklif→sözleşme→portal otomasyonu ve bağlantısız dosya temizleyici.',
            'items' => [
                'Modüler JS Code-Splitting (6 bağımsız paket) & 0ms sekme geçiş motoru',
                'Ultra-Turbo Boot önbelleği & uyarlamalı SQLite WAL indeks altyapısı',
                'Teklif → Resmi Sözleşme PDF → Proje Portalı → Uptime & Yenileme zinciri',
                'Konu Kümesi (Topic Cluster) iç link örücü & Dergi stili 3 sütunlu blog',
                'Bağlantısız / artık kullanılmayan eski sürüm dosya radarı ve temizleyici',
            ],
        ],
        [
            'version' => 'v3.0',
            'status' => 'released',
            'badge' => 'KURUMSAL KULE',
            'title' => '20/20 Güvenlik Kalkanı, LSCache Haritası & Sosyal Merkez',
            'summary' => 'OWASP 20/20 güvenlik denetçisi, LiteSpeed canlı HIT/MISS sayfa haritası, birleşik Google & SEO Merkezi (GEO) ve bağımsız Sosyal Medya Merkezi.',
            'items' => [
                '20/20 OWASP Güvenlik Kalkanı, TOTP/E-posta 2FA, RBAC Rol & 5 Sürüm Zaman Makinesi',
                'LiteSpeed LSCache Aç/Kapat + Canlı HIT / MISS önbellek sayfa haritası',
                'Birleşik Google & SEO Merkezi (GA4, GSC, Yorumlar, Harita, llms.txt & GEO)',
                'Bağımsız Sosyal Medya Merkezi (sameAs, OG Kart, 1080×1080 IG & Story üretici)',
                '300+ site Uptime & SSL Kontrol Kulesi, Hosting Yenileme Radarı & QR Kartvizit',
            ],
        ],
        [
            'version' => 'v2.0',
            'status' => 'released',
            'badge' => 'CANLI STÜDYO',
            'title' => 'Apple Cam Arayüzü, Canlı Düzenleyici & 4 Dil Stüdyosu',
            'summary' => 'Apple Liquid Glass yönetim stüdyosu, sayfa üzerinde yerinde canlı görsel düzenleme, 4 dil (TR·EN·DE·RU) toplu çeviri kuyruğu ve dönüşüm araçları.',
            'items' => [
                'Apple Cam (Liquid Glass) yönetim stüdyosu & White-Label kurumsal renk sihirbazı',
                'Ön yüzde sabit yönetici barı & 0ms yerinde Canlı Görsel Sayfa Düzenleyici',
                'TR · EN · DE · RU çoklu dil, duraksamayan toplu çeviri & AI Blog/SSS Yazarı',
                'KVKK tam uyumlu Zero-Cookie çerezsiz canlı ziyaretçi & 30 günlük trafik stüdyosu',
                '4 adımlı İnteraktif Teklif Sihirbazı, Akıllı Pop-up (Exit-Intent) & Gerçek SMTP',
            ],
        ],
        [
            'version' => 'v1.0',
            'status' => 'released',
            'badge' => 'ÇEKİRDEK MİMARİ',
            'title' => 'Saf PHP 8+ Çekirdek, Temiz Ana Dizin & WebP Medya',
            'summary' => 'Sıfır dış kütüphane bağımlılıklı saf PHP 8+ Front-Controller mimarisi, temiz /uploads/Yıl/Ay/Gün WebP medya sistemi ve tek merkezli sayfa yönetimi.',
            'items' => [
                'Saf PHP 8+ tek giriş noktası (index.php) & temiz uzantısız /api/* uç noktaları',
                'Otomatik /uploads/YYYY/MM/DD/ klasörleme, WebP dönüşümü & GD marka damgası',
                'Tek merkezli Sayfalar stüdyosu, kalıcı bağlantı (Slug), Çöp Kutusu & 301 motoru',
                'Hiyerarşik Blog Kategorileri, İçindekiler (TOC) & yerel .woff2 PageSpeed mimarisi',
            ],
        ],
    ];
}

function zeynart_v4_sqlite_file(): string
{
    return DATA_DIR . '/studio_index.sqlite';
}

function zeynart_v4_sync_sqlite_index(array $blogIndex, string $blogSig): bool
{
    if (!extension_loaded('pdo_sqlite') || !class_exists('PDO')) {
        return false;
    }
    try {
        $dbPath = zeynart_v4_sqlite_file();
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->query('PRAGMA journal_mode = WAL');
        $pdo->query('PRAGMA synchronous = NORMAL');
        $pdo->query('PRAGMA temp_store = MEMORY');
        $pdo->query('CREATE TABLE IF NOT EXISTS meta_kv (k TEXT PRIMARY KEY, v TEXT NOT NULL)');
        $pdo->query('CREATE TABLE IF NOT EXISTS posts_idx (
            slug TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            category TEXT NOT NULL,
            status TEXT NOT NULL,
            published_at TEXT NOT NULL,
            seo_score INTEGER NOT NULL DEFAULT 90,
            is_translated INTEGER NOT NULL DEFAULT 0
        )');
        $pdo->query('CREATE INDEX IF NOT EXISTS idx_posts_status_pub ON posts_idx(status, published_at DESC)');
        $pdo->query('CREATE INDEX IF NOT EXISTS idx_posts_cat ON posts_idx(category)');

        $curStmt = $pdo->prepare('SELECT v FROM meta_kv WHERE k = :k LIMIT 1');
        $curStmt->execute([':k' => 'blog_sig']);
        $existingSig = (string)($curStmt->fetchColumn() ?: '');
        if ($existingSig === $blogSig) {
            return true;
        }

        $pdo->beginTransaction();
        $pdo->query('DELETE FROM posts_idx');
        $ins = $pdo->prepare('INSERT OR REPLACE INTO posts_idx (slug, title, category, status, published_at, seo_score, is_translated) VALUES (:slug, :title, :cat, :st, :pub, :seo, :tr)');
        foreach ($blogIndex as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = trim((string)($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $isTr = (
                !empty($row['i18n']['en']['title'])
                && !empty($row['i18n']['de']['title'])
                && !empty($row['i18n']['ru']['title'])
            ) ? 1 : 0;
            $ins->execute([
                ':slug' => $slug,
                ':title' => (string)($row['title'] ?? ''),
                ':cat' => (string)($row['category'] ?? 'Genel'),
                ':st' => (string)($row['status'] ?? 'published'),
                ':pub' => (string)($row['published_at'] ?? ''),
                ':seo' => (int)($row['seo_score'] ?? 90),
                ':tr' => $isTr,
            ]);
        }
        $upMeta = $pdo->prepare('INSERT OR REPLACE INTO meta_kv (k, v) VALUES (:k, :v)');
        $upMeta->execute([':k' => 'blog_sig', ':v' => $blogSig]);
        $upMeta->execute([':k' => 'synced_at', ':v' => date('c')]);
        $pdo->commit();
        return true;
    } catch (Throwable) {
        return false;
    }
}

function zeynart_v4_boot_cache_file(): string
{
    return DATA_DIR . '/admin_boot_summary_cache.json';
}

function zeynart_v4_cached_boot_bundle(array $store, bool $forceRefresh = false): array
{
    $sqlActive = function_exists('zeynart_is_sql_active') && zeynart_is_sql_active();
    if ($sqlActive) {
        $postStamp = '0:0';
        if (($pdo = zeynart_get_pdo()) !== null) {
            try {
                $tPosts = zeynart_sql_table('posts');
                $st = $pdo->query("SELECT COUNT(*), MAX(updated_at) FROM `{$tPosts}`");
                $r = $st ? $st->fetch(PDO::FETCH_NUM) : null;
                if (is_array($r)) {
                    $postStamp = (int)($r[0] ?? 0) . ':' . (string)($r[1] ?? '');
                }
            } catch (Throwable) {
            }
        }
        $storeSig = 'sql:' . (string)($store['updated_at'] ?? '');
        $blogSig = $postStamp;
    } else {
        $storeFile = DATA_DIR . '/store.json';
        $blogFile = DATA_DIR . '/blog_index.json';
        $storeSig = is_file($storeFile) ? ((int)@filemtime($storeFile) . ':' . (int)@filesize($storeFile)) : '0:0';
        $blogSig = is_file($blogFile) ? ((int)@filemtime($blogFile) . ':' . (int)@filesize($blogFile)) : '0:0';
    }
    $combinedSig = $storeSig . '|' . $blogSig;

    if (!$forceRefresh && is_array($GLOBALS['__zeynart_boot_bundle_mem'] ?? null)) {
        if (($GLOBALS['__zeynart_boot_bundle_mem']['sig'] ?? '') === $combinedSig) {
            return $GLOBALS['__zeynart_boot_bundle_mem'];
        }
    }

    $cacheFile = zeynart_v4_boot_cache_file();
    if (!$forceRefresh) {
        $dec = zeynart_read_json_data($cacheFile);
        if (is_array($dec) && ($dec['sig'] ?? '') === $combinedSig && isset($dec['blog_categories_payload'], $dec['blog_metrics'])) {
            $dec['cache_hit'] = true;
            $GLOBALS['__zeynart_boot_bundle_mem'] = $dec;
            return $dec;
        }
    }

    $blogIndex = function_exists('load_blog_index') ? load_blog_index(true) : [];
    $sqliteSynced = $sqlActive ? true : zeynart_v4_sync_sqlite_index($blogIndex, $blogSig);

    $totalPosts = count($blogIndex);
    $publishedPosts = 0;
    $visiblePublicPosts = 0;
    $draftPosts = 0;
    $translatedPosts = 0;
    $seoTotal = 0;

    foreach ($blogIndex as $item) {
        $st = (string)($item['status'] ?? 'published');
        if ($st === 'published') {
            $publishedPosts++;
            if (!function_exists('is_post_publicly_visible') || is_post_publicly_visible($item, false)) {
                $visiblePublicPosts++;
            }
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

    $cats = zeynart_get_blog_categories($store);
    $tree = zeynart_build_blog_category_tree($cats);
    $countsById = zeynart_count_posts_per_category($cats, $blogIndex, false);
    $blogSlug = zeynart_page_slug($store, 'blog') ?: 'blog';
    $defaultCatId = '';
    $defaultCat = null;
    $catCountsNamed = [];

    foreach ($tree as $i => $row) {
        $cid = (string)$row['id'];
        $cPair = $countsById[$cid] ?? ['direct' => 0, 'total' => 0];
        $tree[$i]['post_count'] = (int)$cPair['direct'];
        $tree[$i]['total_post_count'] = (int)$cPair['total'];
        $tree[$i]['url'] = '/' . $blogSlug . '/kategori/' . $row['slug'] . '/';
        if (!empty($row['is_default'])) {
            $defaultCatId = $cid;
            $defaultCat = $tree[$i];
        }
        if ((int)$cPair['total'] > 0) {
            $catCountsNamed[(string)$row['name']] = (int)$cPair['total'];
        }
    }
    if ($defaultCat === null && $tree !== []) {
        $defaultCatId = (string)$tree[0]['id'];
        $defaultCat = $tree[0];
    }
    arsort($catCountsNamed);

    $blogCategoriesPayload = [
        'categories' => $tree,
        'raw_categories' => $cats,
        'default_category_id' => $defaultCatId,
        'default_category' => $defaultCat,
        'blog_slug' => $blogSlug,
        'total_posts' => $totalPosts,
    ];

    // Exact base LSCache URL catalog count (without needing to serialize 1,700+ items on boot)
    $resolvedSlugs = function_exists('zeynart_resolved_page_slugs') ? zeynart_resolved_page_slugs($store) : [];
    $nonBlogCount = 1; // '/'
    foreach (['hakkimizda', 'hizmetler', 'projeler', 'blog', 'iletisim', 'teklif-sihirbazi', 'site-analiz'] as $ck) {
        if ((!function_exists('zeynart_page_status') || zeynart_page_status($store, $ck) === 'published') && !empty($resolvedSlugs[$ck])) {
            $nonBlogCount++;
        }
    }
    if (empty($store['booking_settings']) || ($store['booking_settings']['enabled'] ?? true) !== false) {
        $nonBlogCount++;
    }
    if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'hizmetler') === 'published') {
        $pubServices = function_exists('zeynart_public_services') ? zeynart_public_services($store) : ($store['services'] ?? []);
        foreach ($pubServices as $sRow) {
            if (!empty($sRow['slug'])) {
                $nonBlogCount++;
            }
        }
    }
    if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'projeler') === 'published') {
        $pubProjects = function_exists('zeynart_public_projects') ? zeynart_public_projects($store) : ($store['projects'] ?? []);
        foreach ($pubProjects as $pRow) {
            if (!empty($pRow['slug'])) {
                $nonBlogCount++;
            }
        }
    }
    foreach (($store['custom_pages'] ?? []) as $cp) {
        $stCp = function_exists('zeynart_normalize_page_status') ? zeynart_normalize_page_status($cp['status'] ?? 'published') : (string)($cp['status'] ?? 'published');
        if ($stCp === 'published' && !empty($cp['slug'])) {
            $nonBlogCount++;
        }
    }
    foreach (['cerez-politikasi', 'kvkk', 'gizlilik'] as $lk) {
        if ((!function_exists('zeynart_page_status') || zeynart_page_status($store, $lk) === 'published') && !empty($resolvedSlugs[$lk])) {
            $nonBlogCount++;
        }
    }
    $blogUrlCount = 0;
    if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'blog') === 'published') {
        foreach ($cats as $bc) {
            if (trim((string)($bc['slug'] ?? '')) !== '') {
                $blogUrlCount++;
            }
        }
        $blogUrlCount += $visiblePublicPosts;
    }
    if (($store['i18n']['enabled'] ?? true) !== false) {
        $nonBlogCount += 3; // /en/, /de/, /ru/
        foreach (['hakkimizda', 'hizmetler', 'projeler', 'blog', 'iletisim'] as $ek) {
            if ((!function_exists('zeynart_page_status') || zeynart_page_status($store, $ek) === 'published') && !empty($resolvedSlugs[$ek])) {
                $nonBlogCount++;
            }
        }
        if (!function_exists('zeynart_page_status') || zeynart_page_status($store, 'cerez-politikasi') === 'published') {
            $nonBlogCount++;
        }
    }

    $engineMode = $sqliteSynced ? 'sqlite_wal' : 'micro_index';
    if ($sqlActive) {
        $dbCfgBoot = zeynart_load_db_config();
        $engineMode = (($dbCfgBoot['driver'] ?? 'mysql') === 'sqlite') ? 'pdo_sqlite' : 'pdo_mysql';
    }

    $bundle = [
        'sig' => $combinedSig,
        'created_at' => time(),
        'sqlite_synced' => $sqliteSynced,
        'engine' => $engineMode,
        'blog_metrics' => [
            'total_posts' => $totalPosts,
            'published_posts' => $publishedPosts,
            'draft_posts' => $draftPosts,
            'translated_posts' => $translatedPosts,
            'avg_seo_score' => $totalPosts > 0 ? (int)round($seoTotal / $totalPosts) : 95,
            'recent_posts' => array_slice($blogIndex, 0, 8),
            'top_categories' => array_slice($catCountsNamed, 0, 8, true),
            'blog_categories_count' => count($cats),
        ],
        'blog_categories_payload' => $blogCategoriesPayload,
        'lscache_base_count' => $nonBlogCount + $blogUrlCount,
        'robots_preview' => function_exists('compile_robots_txt') ? compile_robots_txt($store) : '',
        'llms_preview' => function_exists('compile_llms_txt') ? compile_llms_txt($store, false) : '',
        'cache_hit' => false,
    ];

    atomic_write_json($cacheFile, $bundle, false);
    $GLOBALS['__zeynart_boot_bundle_mem'] = $bundle;
    return $bundle;
}

/* ============================================================================
 * 5. UNUSED & UNLINKED FILE DEPENDENCY GRAPH SCANNER & SAFE CLEANER (v5.0)
 * ============================================================================ */

function zeynart_v4_unused_scan_cache_file(): string
{
    return DATA_DIR . '/unused_files_cache.json';
}

function zeynart_v4_invalidate_unused_scan_cache(): void
{
    $GLOBALS['__zeynart_unused_scan_mem'] = null;
    $f = zeynart_v4_unused_scan_cache_file();
    zeynart_delete_json_data($f);
}

function zeynart_v4_code_dirs_signature(): string
{
    $dirs = [
        ROOT_DIR,
        ROOT_DIR . '/.well-known',
        ADMIN_DIR,
        ADMIN_DIR . '/chunks',
        ADMIN_DIR . '/extensions',
        INCLUDES_DIR,
        INCLUDES_DIR . '/endpoints',
        INCLUDES_DIR . '/extensions',
        PAGES_DIR,
        LIB_DIR,
        ROOT_DIR . '/assets',
        ROOT_DIR . '/assets/css',
        ROOT_DIR . '/assets/js',
        ROOT_DIR . '/assets/fonts',
        POSTS_DIR,
    ];
    $parts = [];
    foreach ($dirs as $d) {
        $parts[] = is_dir($d) ? ((int)@filemtime($d) . ':' . count(@scandir($d) ?: [])) : '0';
    }
    $blogIdxFile = DATA_DIR . '/blog_index.json';
    $parts[] = is_file($blogIdxFile) ? ((int)@filemtime($blogIdxFile) . ':' . (int)@filesize($blogIdxFile)) : '0';
    return md5(implode('|', $parts));
}

/**
 * Builds the real static dependency graph starting from v5.2 entrypoints:
 * index.php, <ADMIN_DIR>/index.php, <ADMIN_DIR>/api.php, and .htaccess.
 *
 * @return array{reachable: array<string, string>, entrypoints: list<string>, traced_php_count: int}
 */
function zeynart_v4_build_active_dependency_graph(): array
{
    $rootNorm = rtrim(str_replace('\\', '/', (string)(realpath(ROOT_DIR) ?: ROOT_DIR)), '/');
    $admDir = defined('ADMIN_DIR_NAME') ? ADMIN_DIR_NAME : 'admin';
    $entrypoints = [
        'index.php',
        $admDir . '/index.php',
        $admDir . '/api.php',
        '.htaccess',
    ];

    $reachable = [
        '.htaccess' => 'Web sunucusu yönlendirme ve önbellek giriş kuralları',
        'index.php' => 'Ana site tek giriş noktası (Front-Controller)',
        'icon.png' => 'Site ana sekme ikonu (Favicon)',
        'favicon.ico' => 'Tarayıcı uyumluluk sekme ikonu',
        $admDir . '/index.php' => 'Yönetim Stüdyosu v5.2 kabuk giriş noktası',
        $admDir . '/api.php' => 'Yönetim Stüdyosu JSON API giriş noktası',
        'data/.htaccess' => 'Veri kasası doğrudan erişim koruma kalkanı',
        'uploads/.htaccess' => 'Medya dizini betik çalıştırma koruma kalkanı',
        '.well-known/ai-catalog.json' => 'Yapay zeka ajan keşif kataloğu (RFC/.well-known)',
        '.well-known/ard.json' => 'AI kaynak keşif bildirimi',
        '.well-known/mcp-server-card.json' => 'WebMCP sunucu keşif kartı',
        '.well-known/agent-card.json' => 'Kurumsal AI ajan kimlik kartı',
    ];

    $queue = ['index.php', $admDir . '/index.php', $admDir . '/api.php'];
    $visitedPhp = [];

    $markReachable = static function (string $relPath, string $via) use (&$reachable, &$queue, &$visitedPhp, $rootNorm): void {
        $clean = ltrim(str_replace('\\', '/', trim($relPath)), '/');
        if ($clean === '' || str_contains($clean, '..')) {
            return;
        }
        $abs = $rootNorm . '/' . $clean;
        if (!is_file($abs)) {
            return;
        }
        if (!isset($reachable[$clean])) {
            $reachable[$clean] = $via;
        }
        if (str_ends_with(strtolower($clean), '.php') && !isset($visitedPhp[$clean])) {
            $queue[] = $clean;
        }
    };

    // Auto-mark registered sector extension folders & *-studio.js/css assets as reachable
    foreach (['includes/extensions', $admDir . '/extensions'] as $extFolderRel) {
        $extAbs = $rootNorm . '/' . $extFolderRel;
        if (is_dir($extAbs)) {
            $extIt = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extAbs, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($extIt as $efi) {
                if ($efi->isFile()) {
                    $eRel = substr(str_replace('\\', '/', $efi->getPathname()), strlen($rootNorm) + 1);
                    $markReachable($eRel, 'Sektör Eklenti Kancası (v5.2)');
                }
            }
        }
    }
    foreach (glob($rootNorm . '/' . $admDir . '/*-studio.{js,css}', GLOB_BRACE) ?: [] as $stAsset) {
        $markReachable($admDir . '/' . basename($stAsset), 'Sektör Stüdyo Arayüzü (v5.2)');
    }
    foreach (glob($rootNorm . '/includes/i18n_*_dict.php') ?: [] as $dictPhp) {
        $markReachable('includes/' . basename($dictPhp), 'Sektör Çoklu Dil Sözlüğü (v5.2)');
    }

    $constBaseMap = [
        'ROOT_DIR' => '',
        'INCLUDES_DIR' => 'includes',
        'PAGES_DIR' => 'pages',
        'ADMIN_DIR' => $admDir,
        'LIB_DIR' => 'lib',
        'IMAGES_DIR' => 'images',
    ];

    while ($queue !== []) {
        $curRel = array_shift($queue);
        if (isset($visitedPhp[$curRel])) {
            continue;
        }
        $visitedPhp[$curRel] = true;
        $curAbs = $rootNorm . '/' . $curRel;
        if (!is_file($curAbs)) {
            continue;
        }
        $src = (string)@file_get_contents($curAbs);
        if ($src === '') {
            continue;
        }
        $curDirRel = trim(str_replace('\\', '/', dirname($curRel)), '.');
        $curDirRel = trim($curDirRel, '/');

        // 1. Resolve __DIR__ . '/...'
        if (preg_match_all('~__DIR__\s*\.\s*[\'"](/[^\'"]+\.[a-zA-Z0-9]{2,6})[\'"]~', $src, $mDir)) {
            foreach ($mDir[1] as $rawSub) {
                $combined = ($curDirRel !== '' ? ($curDirRel . '/') : '') . ltrim($rawSub, '/');
                $resolvedAbs = realpath($rootNorm . '/' . $combined);
                if ($resolvedAbs !== false) {
                    $normAbs = str_replace('\\', '/', $resolvedAbs);
                    if (str_starts_with($normAbs, $rootNorm . '/')) {
                        $markReachable(substr($normAbs, strlen($rootNorm) + 1), $curRel);
                    }
                }
            }
        }

        // 2. Resolve CONST_DIR . '/...'
        if (preg_match_all('~\b(ROOT_DIR|INCLUDES_DIR|PAGES_DIR|ADMIN_DIR|LIB_DIR|IMAGES_DIR)\s*\.\s*[\'"](/[^\'"]+\.[a-zA-Z0-9]{2,6})[\'"]~', $src, $mConst, PREG_SET_ORDER)) {
            foreach ($mConst as $mc) {
                $basePrefix = $constBaseMap[$mc[1]] ?? '';
                $sub = ltrim($mc[2], '/');
                $cand = $basePrefix !== '' ? ($basePrefix . '/' . $sub) : $sub;
                $markReachable($cand, $curRel);
            }
        }

        // 3. Resolve explicit internal path strings ('/includes/...', '/assets/...', '/pages/...', '/admin/...', '/lib/...', '/.well-known/...')
        if (preg_match_all('~[\'"]/?((?:includes|pages|lib|admin|assets|\.well-known)/[A-Za-z0-9_.\-/]+\.(?:php|js|css|ttf|woff2?|json|png|svg|webp|ico))[\'"]~', $src, $mLit)) {
            foreach ($mLit[1] as $litRel) {
                $markReachable($litRel, $curRel);
            }
        }
        if (str_contains($src, '/assets/fonts/') && preg_match_all('~[\'"]([A-Za-z0-9_.\-]+\.(?:ttf|woff2?))[\'"]~', $src, $mFonts)) {
            foreach ($mFonts[1] as $fontName) {
                $markReachable('assets/fonts/' . $fontName, $curRel);
            }
        }

        // 4. Detect <ADMIN_DIR>/index.php $chunkNames = [...] modular chunks + admin.css/admin.js
        if ($curRel === $admDir . '/index.php') {
            foreach ([$admDir . '/admin.css', $admDir . '/admin.min.css', $admDir . '/admin.js', $admDir . '/admin.min.js'] as $admCoreAsset) {
                $markReachable($admCoreAsset, $admDir . '/index.php');
            }
            if (preg_match('~\$chunkNames\s*=\s*\[([^\]]+)\]~', $src, $cm)) {
                if (preg_match_all('~[\'"]([a-zA-Z0-9_]+)[\'"]~', $cm[1], $cNames)) {
                    foreach ($cNames[1] as $cn) {
                        $markReachable($admDir . '/chunks/' . $cn . '.js', $admDir . '/index.php (v5 chunk)');
                        $markReachable($admDir . '/chunks/' . $cn . '.min.js', $admDir . '/index.php (v5 chunk)');
                    }
                }
            }
        }
    }

    // 5. Propagate canonical .css/.min.css, .js/.min.js pairs and @font-face assets inside reachable CSS files
    $initialReachableKeys = array_keys($reachable);
    foreach ($initialReachableKeys as $rKey) {
        if (preg_match('~^(.+)\.(css|js)$~i', $rKey, $pm) && !str_ends_with(strtolower($pm[1]), '.min')) {
            $minPartner = $pm[1] . '.min.' . strtolower($pm[2]);
            if (is_file($rootNorm . '/' . $minPartner) && !isset($reachable[$minPartner])) {
                $reachable[$minPartner] = $rKey . ' (sıkıştırılmış üretim paketi)';
            }
        } elseif (preg_match('~^(.+)\.min\.(css|js)$~i', $rKey, $pm)) {
            $rawPartner = $pm[1] . '.' . strtolower($pm[2]);
            if (is_file($rootNorm . '/' . $rawPartner) && !isset($reachable[$rawPartner])) {
                $reachable[$rawPartner] = $rKey . ' (kaynak dosya çifti)';
            }
        }

        if (str_ends_with(strtolower($rKey), '.css')) {
            $cssContent = (string)@file_get_contents($rootNorm . '/' . $rKey);
            $cssDir = trim(str_replace('\\', '/', dirname($rKey)), '.');
            $cssDir = trim($cssDir, '/');
            if ($cssContent !== '' && preg_match_all('~url\(\s*[\'"]?([^\'")?#]+\.(?:woff2?|ttf|otf|svg|png|webp))[\'"]?\s*\)~i', $cssContent, $mUrls)) {
                foreach ($mUrls[1] as $uRaw) {
                    if (str_starts_with($uRaw, 'data:') || str_starts_with($uRaw, 'http')) {
                        continue;
                    }
                    $candPath = str_starts_with($uRaw, '/')
                        ? ($rootNorm . $uRaw)
                        : ($rootNorm . '/' . ($cssDir !== '' ? ($cssDir . '/') : '') . $uRaw);
                    $realFont = realpath($candPath);
                    if ($realFont !== false) {
                        $normFont = str_replace('\\', '/', $realFont);
                        if (str_starts_with($normFont, $rootNorm . '/')) {
                            $fRel = substr($normFont, strlen($rootNorm) + 1);
                            if (!isset($reachable[$fRel])) {
                                $reachable[$fRel] = $rKey . ' (@font-face / CSS varlığı)';
                            }
                        }
                    }
                }
            }
        }
    }

    return [
        'reachable' => $reachable,
        'entrypoints' => $entrypoints,
        'traced_php_count' => count($visitedPhp),
        'reachable_count' => count($reachable),
    ];
}

/**
 * Protected system data files in data/ that belong to v4.0 runtime/storage.
 */
function zeynart_v4_known_data_files(): array
{
    $cfgSq = function_exists('zeynart_load_db_config') ? (string)(zeynart_load_db_config()['sqlite_file'] ?? 'zeynart_database.sqlite') : 'zeynart_database.sqlite';
    return [
        'data/.htaccess' => true,
        'data/.csrf_secret' => true,
        'data/.custom_admin_slug' => true,
        'data/.admin_dir_name' => true,
        'data/.lscache_disabled' => true,
        'data/.lscache_ttl' => true,
        'data/.lscache_needs_purge' => true,
        'data/.maintenance_active' => true,
        'data/.gr_avatar_net_cooldown' => true,
        'data/.db_config.php' => true,
        'data/store.json' => true,
        'data/blog_index.json' => true,
        'data/blog_slug_map.json' => true,
        'data/upload_aliases.json' => true,
        'data/404_logs.json' => true,
        'data/redirect_hits.json' => true,
        'data/security_logs.json' => true,
        'data/ip_locks.json' => true,
        'data/news_bot_queue.json' => true,
        'data/news_bot_state.json' => true,
        'data/uptime_monitors.json' => true,
        'data/uptime_state.json' => true,
        'data/uptime_pulse_state.json' => true,
        'data/ota_hub_state.json' => true,
        'data/ota_client_state.json' => true,
        'data/ota_logs.json' => true,
        'data/.ota_installed_version' => true,
        'data/backup_pulse_state.json' => true,
        'data/proposals.json' => true,
        'data/client_trackers.json' => true,
        'data/client_renewals.json' => true,
        'data/bookings.json' => true,
        'data/blog_comments.json' => true,
        'data/search_analytics.json' => true,
        'data/indexnow_logs.json' => true,
        'data/lscache_state.json' => true,
        'data/cache_purge_state.json' => true,
        'data/google_reviews_cache.json' => true,
        'data/media_index_cache.json' => true,
        'data/media_summary_cache.json' => true,
        'data/dir_metrics_cache.json' => true,
        'data/admin_boot_summary_cache.json' => true,
        'data/revisions_list_cache.json' => true,
        'data/unused_files_cache.json' => true,
        'data/studio_index.sqlite' => true,
        'data/studio_index.sqlite-wal' => true,
        'data/studio_index.sqlite-shm' => true,
        'data/zeynart_database.sqlite' => true,
        'data/zeynart_database.sqlite-wal' => true,
        'data/zeynart_database.sqlite-shm' => true,
        'data/' . $cfgSq => true,
        'data/' . $cfgSq . '-wal' => true,
        'data/' . $cfgSq . '-shm' => true,
    ];
}

function zeynart_scan_unused_project_files(array $store = [], bool $force = false): array
{
    $sig = zeynart_v4_code_dirs_signature();
    if (!$force && is_array($GLOBALS['__zeynart_unused_scan_mem'] ?? null)) {
        if (($GLOBALS['__zeynart_unused_scan_mem']['sig'] ?? '') === $sig) {
            return $GLOBALS['__zeynart_unused_scan_mem'];
        }
    }

    $cacheFile = zeynart_v4_unused_scan_cache_file();
    if (!$force) {
        $dec = zeynart_read_json_data($cacheFile);
        if (
            is_array($dec)
            && ($dec['sig'] ?? '') === $sig
            && ((time() - (int)($dec['scanned_ts'] ?? 0)) < 900)
            && is_array($dec['items'] ?? null)
        ) {
            $GLOBALS['__zeynart_unused_scan_mem'] = $dec;
            return $dec;
        }
    }

    $t0 = hrtime(true);
    $rootNorm = rtrim(str_replace('\\', '/', (string)(realpath(ROOT_DIR) ?: ROOT_DIR)), '/');
    $graph = zeynart_v4_build_active_dependency_graph();
    $reachable = $graph['reachable'];
    $knownDataFiles = zeynart_v4_known_data_files();

    $items = [];
    $scannedCodeFiles = 0;

    $addUnused = static function (
        string $rel,
        string $category,
        string $categoryLabel,
        string $reason,
        bool $safeToDelete = true
    ) use (&$items, $rootNorm): void {
        $clean = ltrim(str_replace('\\', '/', $rel), '/');
        $abs = $rootNorm . '/' . $clean;
        if (!is_file($abs)) {
            return;
        }
        $sz = (int)@filesize($abs);
        $mt = (int)@filemtime($abs);
        $items[] = [
            'rel' => $clean,
            'name' => basename($clean),
            'dir' => dirname($clean) === '.' ? '/' : ('/' . dirname($clean) . '/'),
            'ext' => strtolower(pathinfo($clean, PATHINFO_EXTENSION)),
            'size_bytes' => $sz,
            'size_kb' => round($sz / 1024, 1),
            'modified' => $mt > 0 ? date('d.m.Y H:i', $mt) : '-',
            'category' => $category,
            'category_label' => $categoryLabel,
            'reason' => $reason,
            'safe_delete' => $safeToDelete,
        ];
    };

    // 1. Check Root Directory files against strict v5.2 Front-Controller standard
    $admDirScan = defined('ADMIN_DIR_NAME') ? ADMIN_DIR_NAME : 'admin';
    $allowedRootEntries = [
        '.', '..', '.htaccess', 'index.php', 'icon.png', 'favicon.ico',
        '.well-known', 'admin', $admDirScan, 'assets', 'data', 'images', 'includes', 'lib', 'pages', 'uploads',
        'cgi-bin', 'php.ini', '.user.ini', '.ftpquota', '.cagefs', '.cl.selector', '.htpasswds',
        'plesk-stat', 'webstat', 'webstat-ssl', 'ftpstat', 'anon_ftpstat', 'awstats-icon',
    ];
    foreach (@scandir($rootNorm) ?: [] as $entry) {
        if (in_array($entry, $allowedRootEntries, true)) {
            continue;
        }
        if (preg_match('/^(google[a-z0-9]+\.html|yandex_[a-z0-9]+\.html|BingSiteAuth\.xml|[a-f0-9]{16,64}\.txt)$/i', $entry)) {
            continue;
        }
        $absEntry = $rootNorm . '/' . $entry;
        if (is_file($absEntry)) {
            $scannedCodeFiles++;
            $isLogOrBak = (bool)preg_match('/^(error_log|php_errorlog|debug\.log)$|\.(bak|old|orig|tmp|swp|save|zip|tar|gz|rar|7z|sql)$/i', $entry);
            $addUnused(
                $entry,
                $isLogOrBak ? 'temp_backup_junk' : 'root_leftover',
                $isLogOrBak ? 'Yedek / Log Kalıntısı' : 'Ana Dizin Eski Dosya',
                $isLogOrBak
                    ? 'Ana dizinde unutulmuş hata günlüğü, arşiv veya geçici yedek dosyası; v5.2 tarafından kullanılmaz.'
                    : 'Stüdyo v5.2 tek giriş noktası (index.php) mimarisinde bu kök dosyanın hiçbir bağlantısı yok (eski sürümden kalma).'
            );
        }
    }

    // 2. Scan Code & Asset Directories (includes/, pages/, <ADMIN_DIR>/, lib/, assets/, .well-known/)
    $codeDirs = array_values(array_unique(['includes', 'pages', $admDirScan, 'lib', 'assets', '.well-known']));
    foreach ($codeDirs as $cDir) {
        $absDir = $rootNorm . '/' . $cDir;
        if (!is_dir($absDir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $fi) {
            /** @var SplFileInfo $fi */
            if (!$fi->isFile()) {
                continue;
            }
            $scannedCodeFiles++;
            $full = str_replace('\\', '/', $fi->getPathname());
            $rel = substr($full, strlen($rootNorm) + 1);
            $base = $fi->getFilename();
            $ext = strtolower($fi->getExtension());

            if ($base === '.htaccess') {
                continue;
            }

            // Detect editor/backup/log junk anywhere inside code directories
            if (
                preg_match('/\.(bak|old|orig|tmp|swp|save|sql|zip|tar|gz|rar|7z)$/i', $base)
                || str_ends_with($base, '~')
                || in_array(strtolower($base), ['thumbs.db', '.ds_store', 'error_log', 'php_errorlog', 'debug.log'], true)
                || str_starts_with($base, '__tmp_')
            ) {
                $addUnused(
                    $rel,
                    'temp_backup_junk',
                    'Geçici / Yedek Kalıntısı',
                    'Kod dizininde unutulmuş geçici yedek, arşiv veya sistem kalıntısı; aktif v4.0 ile bağlantısı yok.'
                );
                continue;
            }

            // Static images inside assets/ (if any) are checked only if code/font/manifest
            if (in_array($ext, ['php', 'inc', 'phtml', 'js', 'mjs', 'css', 'map', 'html', 'htm', 'json', 'txt', 'xml', 'woff', 'woff2', 'ttf'], true)) {
                if (!isset($reachable[$rel])) {
                    $addUnused(
                        $rel,
                        'unlinked_code',
                        'v4.0 Bağlantısız Modül/Kod',
                        'Aktif v4.0 giriş noktalarından (index.php, admin/index.php, admin/api.php) bu dosyaya hiçbir require/import/rota bağlantısı yok.'
                    );
                }
            }
        }
    }

    // 3. Check data/ top-level stray files & data/posts/*.json orphan post files not in blog_index.json
    if (is_dir(DATA_DIR)) {
        foreach (@scandir(DATA_DIR) ?: [] as $dEntry) {
            if ($dEntry === '.' || $dEntry === '..') {
                continue;
            }
            $dAbs = DATA_DIR . '/' . $dEntry;
            if (!is_file($dAbs)) {
                continue;
            }
            $dRel = 'data/' . $dEntry;
            if (isset($knownDataFiles[$dRel])) {
                continue;
            }
            if (preg_match('/\.tmp\.[a-f0-9]+$/i', $dEntry) || preg_match('/\.(bak|old|tmp|swp|sql)$/i', $dEntry)) {
                $addUnused(
                    $dRel,
                    'temp_backup_junk',
                    'Geçici Veri Kalıntısı',
                    'data/ klasöründe kalmış eski geçici yazım (.tmp/.bak) dosyası.'
                );
            } else {
                $addUnused(
                    $dRel,
                    'unlinked_data',
                    'Bağlantısız Veri Dosyası',
                    'Aktif v4.0 veri tabloları arasında tanımlı olmayan, eski sürümden kalmış veri dosyası.'
                );
            }
        }
    }

    if (is_dir(POSTS_DIR)) {
        $blogIdx = function_exists('load_blog_index') ? load_blog_index() : [];
        $indexedSlugs = [];
        foreach ($blogIdx as $bRow) {
            if (!empty($bRow['slug'])) {
                $indexedSlugs[(string)$bRow['slug']] = true;
            }
        }
        foreach (glob(POSTS_DIR . '/*.json') ?: [] as $pJsonPath) {
            $pSlug = pathinfo($pJsonPath, PATHINFO_FILENAME);
            if ($pSlug !== '' && !isset($indexedSlugs[$pSlug])) {
                $addUnused(
                    'data/posts/' . basename($pJsonPath),
                    'orphan_post_json',
                    'Yetim Blog JSON Kaydı',
                    'blog_index.json indeksinde kaydı bulunmayan, silinmiş veya eski sürümden kalmış bağlantısız makale JSON dosyası.'
                );
            }
        }
    }

    // Sort: unlinked_code & root_leftover first, then largest size
    $priorityMap = [
        'root_leftover' => 1,
        'unlinked_code' => 2,
        'temp_backup_junk' => 3,
        'orphan_post_json' => 4,
        'unlinked_data' => 5,
    ];
    usort($items, static function (array $a, array $b) use ($priorityMap): int {
        $pa = $priorityMap[$a['category']] ?? 9;
        $pb = $priorityMap[$b['category']] ?? 9;
        if ($pa !== $pb) {
            return $pa <=> $pb;
        }
        return ($b['size_bytes'] <=> $a['size_bytes']) ?: strcmp($a['rel'], $b['rel']);
    });

    $totalBytes = array_reduce($items, static fn(int $acc, array $it): int => $acc + (int)$it['size_bytes'], 0);
    $byCategory = [
        'root_leftover' => 0,
        'unlinked_code' => 0,
        'temp_backup_junk' => 0,
        'orphan_post_json' => 0,
        'unlinked_data' => 0,
    ];
    foreach ($items as $it) {
        $ck = $it['category'];
        $byCategory[$ck] = ($byCategory[$ck] ?? 0) + 1;
    }

    $elapsedMs = round((hrtime(true) - $t0) / 1e6, 1);

    $report = [
        'sig' => $sig,
        'scanned_at' => date('d.m.Y H:i:s'),
        'scanned_ts' => time(),
        'scan_ms' => $elapsedMs,
        'active_version' => 'v5.2',
        'entrypoints' => $graph['entrypoints'],
        'traced_php_count' => $graph['traced_php_count'],
        'reachable_files_count' => $graph['reachable_count'],
        'scanned_code_files' => $scannedCodeFiles,
        'unused_count' => count($items),
        'total_unused_bytes' => $totalBytes,
        'total_unused_kb' => round($totalBytes / 1024, 1),
        'total_unused_mb' => round($totalBytes / 1048576, 2),
        'by_category' => $byCategory,
        'items' => $items,
    ];

    atomic_write_json($cacheFile, $report, false);
    $GLOBALS['__zeynart_unused_scan_mem'] = $report;
    return $report;
}

function zeynart_get_unused_files_boot_summary(array $store = []): array
{
    $rep = zeynart_scan_unused_project_files($store, false);
    return [
        'unused_count' => (int)($rep['unused_count'] ?? 0),
        'total_unused_kb' => (float)($rep['total_unused_kb'] ?? 0),
        'reachable_files_count' => (int)($rep['reachable_files_count'] ?? 0),
        'traced_php_count' => (int)($rep['traced_php_count'] ?? 0),
        'scanned_at' => (string)($rep['scanned_at'] ?? ''),
        'by_category' => $rep['by_category'] ?? [],
        'sample_files' => array_values(array_map(
            static fn(array $x): string => (string)$x['rel'],
            array_slice($rep['items'] ?? [], 0, 5)
        )),
    ];
}

/**
 * Safely deletes selected unused/unlinked files after re-verifying against the live v4.0 dependency graph
 * and Core Protection Set (impossible to delete any active v4.0 code, store, or index file).
 */
function zeynart_cleanup_unused_project_files(array $selectedRelPaths, array $store = []): array
{
    $rootReal = realpath(ROOT_DIR);
    if ($rootReal === false) {
        return ['ok' => false, 'error' => 'Kök dizin doğrulanamadı.'];
    }
    $rootNorm = rtrim(str_replace('\\', '/', $rootReal), '/');

    // Fresh scan so we only ever allow deleting files confirmed unused RIGHT NOW
    $freshReport = zeynart_scan_unused_project_files($store, true);
    $allowedUnusedMap = [];
    foreach (($freshReport['items'] ?? []) as $it) {
        if (!empty($it['safe_delete']) && !empty($it['rel'])) {
            $allowedUnusedMap[(string)$it['rel']] = (int)($it['size_bytes'] ?? 0);
        }
    }

    $graph = zeynart_v4_build_active_dependency_graph();
    $reachable = $graph['reachable'];
    $knownData = zeynart_v4_known_data_files();

    $deleted = [];
    $skipped = [];
    $freedBytes = 0;

    foreach ($selectedRelPaths as $rawRel) {
        if (!is_string($rawRel)) {
            continue;
        }
        $cleanRel = ltrim(str_replace('\\', '/', trim($rawRel)), '/');
        if ($cleanRel === '' || str_contains($cleanRel, '..')) {
            $skipped[] = $cleanRel;
            continue;
        }
        // Hard block any active v4.0 file or core runtime data file
        if (isset($reachable[$cleanRel]) || isset($knownData[$cleanRel]) || !isset($allowedUnusedMap[$cleanRel])) {
            $skipped[] = $cleanRel;
            continue;
        }
        // Never touch backups/, ota_releases/, revisions/, uploads/, or images/ from the code cleaner
        if (preg_match('~^(data/backups|data/ota_releases|data/revisions|data/analytics|uploads|images)(/|$)~i', $cleanRel)) {
            $skipped[] = $cleanRel;
            continue;
        }

        $targetReal = realpath($rootNorm . '/' . $cleanRel);
        if ($targetReal === false || !is_file($targetReal)) {
            $skipped[] = $cleanRel;
            continue;
        }
        $targetNorm = str_replace('\\', '/', $targetReal);
        if (!str_starts_with($targetNorm, $rootNorm . '/')) {
            $skipped[] = $cleanRel;
            continue;
        }

        $fSize = (int)@filesize($targetReal);
        if (@unlink($targetReal)) {
            $deleted[] = $cleanRel;
            $freedBytes += $fSize;
        } else {
            $skipped[] = $cleanRel;
        }
    }

    zeynart_v4_invalidate_unused_scan_cache();
    if ($deleted !== [] && function_exists('zeynart_audit_log')) {
        $actor = (string)($_SESSION['zeynart_admin_user'] ?? 'admin');
        zeynart_audit_log(
            'unused_files_clean',
            count($deleted) . ' adet v4.0 ile bağlantısız eski/gereksiz dosya temizlendi (' . implode(', ', array_slice($deleted, 0, 6)) . (count($deleted) > 6 ? '...' : '') . ').',
            $actor,
            'ok'
        );
    }

    $updatedReport = zeynart_scan_unused_project_files($store, true);

    return [
        'ok' => true,
        'deleted_count' => count($deleted),
        'deleted_files' => $deleted,
        'skipped_files' => $skipped,
        'freed_bytes' => $freedBytes,
        'freed_kb' => round($freedBytes / 1024, 1),
        'report' => $updatedReport,
    ];
}
