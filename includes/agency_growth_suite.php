<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Zeynart Dijital Stüdyo v3 — Kurumsal Ajans Satış Hunisi (CRM), Müşteri Proje Takip Portalı,
 * Yenileme Radarı, Programatik Sektörel Çözüm Sayfaları & Çerezsiz Keşif Görüşmesi Motoru
 */

define('CLIENT_TRACKERS_FILE', DATA_DIR . '/client_trackers.json');
define('CLIENT_RENEWALS_FILE', DATA_DIR . '/client_renewals.json');
define('DISCOVERY_BOOKINGS_FILE', DATA_DIR . '/bookings.json');

/* ============================================================================
 * 1. MINI-CRM KANBAN PIPELINE & 1-CLICK PROPOSAL CONVERSION
 * ============================================================================ */

function zeynart_crm_stages(): array
{
    return [
        'new' => ['key' => 'new', 'label' => 'Yeni Talep', 'badge' => 'YENİ TALEP', 'color' => '#38bdf8'],
        'contacted' => ['key' => 'contacted', 'label' => 'Görüşüldü', 'badge' => 'GÖRÜŞÜLDÜ', 'color' => '#fbbf24'],
        'proposal_sent' => ['key' => 'proposal_sent', 'label' => 'Teklif İletildi', 'badge' => 'TEKLİF İLETİLDİ', 'color' => '#a78bfa'],
        'won' => ['key' => 'won', 'label' => 'Anlaşıldı ✅', 'badge' => 'ANLAŞILDI', 'color' => '#34d399'],
        'archived' => ['key' => 'archived', 'label' => 'Ertelendi / Arşiv', 'badge' => 'ERTELENDİ', 'color' => '#94a3b8'],
    ];
}

function zeynart_normalize_message_crm(array $msg): array
{
    $stages = zeynart_crm_stages();
    $stage = strtolower(trim((string)($msg['crm_stage'] ?? 'new')));
    if (!isset($stages[$stage])) {
        $stage = 'new';
    }
    $msg['crm_stage'] = $stage;
    $msg['crm_note'] = trim((string)($msg['crm_note'] ?? ''));
    $fDate = trim((string)($msg['followup_date'] ?? ''));
    $msg['followup_date'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDate) ? $fDate : '';
    $msg['deal_value'] = round(max(0.0, (float)($msg['deal_value'] ?? 0)), 2);
    $msg['linked_proposal_code'] = trim((string)($msg['linked_proposal_code'] ?? ''));
    $msg['updated_crm_at'] = (string)($msg['updated_crm_at'] ?? '');
    return $msg;
}

function zeynart_compile_crm_summary(array $messages): array
{
    $today = date('Y-m-d');
    $stageCounts = ['new' => 0, 'contacted' => 0, 'proposal_sent' => 0, 'won' => 0, 'archived' => 0];
    $dueFollowups = 0;
    $wonRevenue = 0.0;
    $pipelineValue = 0.0;

    foreach ($messages as $rawM) {
        if (!is_array($rawM)) {
            continue;
        }
        $m = zeynart_normalize_message_crm($rawM);
        $st = $m['crm_stage'];
        $stageCounts[$st] = ($stageCounts[$st] ?? 0) + 1;
        if ($st === 'won') {
            $wonRevenue += (float)$m['deal_value'];
        } elseif ($st !== 'archived') {
            $pipelineValue += (float)$m['deal_value'];
            if ($m['followup_date'] !== '' && $m['followup_date'] <= $today) {
                $dueFollowups++;
            }
        }
    }

    return [
        'stage_counts' => $stageCounts,
        'due_followups' => $dueFollowups,
        'won_revenue' => round($wonRevenue, 2),
        'pipeline_value' => round($pipelineValue, 2),
    ];
}

function zeynart_convert_lead_to_proposal(array $msg, array $store): array
{
    require_once __DIR__ . '/enterprise_cms_suite.php';
    $m = zeynart_normalize_message_crm($msg);
    $wd = is_array($m['wizard_data'] ?? null) ? $m['wizard_data'] : [];
    $ad = is_array($m['audit_data'] ?? null) ? $m['audit_data'] : [];
    $bd = is_array($m['booking_data'] ?? null) ? $m['booking_data'] : [];

    $serviceTitle = trim((string)($wd['category_title'] ?? ($m['service'] ?? '')));
    if ($serviceTitle === '' && !empty($ad['domain'])) {
        $serviceTitle = $ad['domain'] . ' Hız (%100 PageSpeed) & Yapay Zeka SEO Yenileme Projesi';
    } elseif ($serviceTitle === '' && !empty($bd['topic'])) {
        $serviceTitle = (string)$bd['topic'];
    } elseif ($serviceTitle === '') {
        $serviceTitle = 'Kurumsal Dijital Stüdyo & Web Tasarım Projesi';
    }

    $estimatedPrice = (float)$m['deal_value'];
    if ($estimatedPrice <= 0 && !empty($wd['min_price'])) {
        $estimatedPrice = (float)$wd['min_price'];
    }
    if ($estimatedPrice <= 0) {
        $estimatedPrice = 32000.0;
    }

    $items = [
        [
            'title' => $serviceTitle,
            'desc' => '%100 PageSpeed performans mimarisi, mobil uyumlu kurumsal UI/UX tasarım, KVKK Zero-Cookie altyapısı ve yönetim stüdyosu kurulumu.',
            'qty' => 1,
            'unit_price' => $estimatedPrice,
        ],
    ];
    if (!empty($wd['features']) && is_array($wd['features'])) {
        $items[] = [
            'title' => 'Seçilen Ek Kapsam & Modüller',
            'desc' => implode(', ', array_map('strval', $wd['features'])),
            'qty' => 1,
            'unit_price' => 0,
        ];
    }

    $code = 'zyn-' . substr(bin2hex(random_bytes(4)), 0, 7);
    $proposalNo = 'ZYN-' . date('Y') . '-' . random_int(100, 999);
    $rawProposal = [
        'code' => $code,
        'proposal_no' => $proposalNo,
        'client_name' => (string)($m['name'] ?? 'Sayın Yetkili'),
        'client_company' => (string)($m['company'] ?? ''),
        'client_email' => (string)($m['email'] ?? ''),
        'client_phone' => (string)($m['phone'] ?? ''),
        'project_title' => $serviceTitle,
        'status' => 'sent',
        'currency' => 'TRY',
        'vat_rate' => 20,
        'discount' => 0,
        'valid_until' => date('Y-m-d', strtotime('+14 days')),
        'delivery_time' => (string)($wd['timeline_title'] ?? '2 – 4 Hafta Anahtar Teslim'),
        'intro_note' => 'Talebiniz doğrultusunda markanızın dijital görünürlüğünü, açılış hızını ve müşteri dönüşüm oranını en üst seviyeye taşıyacak kurumsal proje kapsamımız aşağıda sunulmuştur.',
        'payment_terms' => '%50 sözleşme başlangıcında, %50 test ve canlı yayın onayı sonrasında faturalandırılır.',
        'items' => $items,
        'source_message_id' => (string)($m['id'] ?? ''),
    ];

    $allProposals = zeynart_load_proposals();
    $normalized = zeynart_normalize_proposal($rawProposal, null);
    array_unshift($allProposals, $normalized);
    zeynart_save_proposals($allProposals);

    return $normalized;
}

/* ============================================================================
 * 2. CLIENT PROJECT STATUS & MILESTONE APPROVAL PORTAL (/proje-takip/{code}/)
 * ============================================================================ */

function zeynart_default_tracker_milestones(): array
{
    return [
        [
            'step' => 1,
            'title' => 'Analiz, Strateji & Bilgi Mimarisi',
            'desc' => 'Sektörel rakip analizi, site haritası, SEO anahtar kelime kurgusu ve dönüşüm hunisinin netleştirilmesi.',
            'status' => 'completed', // completed | current | upcoming
            'completed_at' => date('Y-m-d'),
        ],
        [
            'step' => 2,
            'title' => 'Kurumsal UI/UX Tasarım & Görsel Sistem',
            'desc' => 'Marka kimliğine özel modern arayüz tasarımı, mobil öncelikli yerleşim ve tipografi sisteminin hazırlanması.',
            'status' => 'current',
            'completed_at' => '',
        ],
        [
            'step' => 3,
            'title' => 'Saf PHP Mimarisi, Yönetim Stüdyosu & Çoklu Dil',
            'desc' => 'Yüksek hızlı ön yüz kodlaması, Apple Cam Yönetim Stüdyosu v3 ve TR/EN/DE/RU dil altyapısının inşası.',
            'status' => 'upcoming',
            'completed_at' => '',
        ],
        [
            'step' => 4,
            'title' => '%100 PageSpeed, Güvenlik & Yapay Zeka SEO Testi',
            'desc' => 'LiteSpeed LSCache, Zero-Cookie KVKK uyumu, Schema.org/llms.txt ve tüm cihazlarda hız/form testleri.',
            'status' => 'upcoming',
            'completed_at' => '',
        ],
        [
            'step' => 5,
            'title' => 'Canlı Yayın, SSL & Yönetici Teslimi 🚀',
            'desc' => 'DNS/SSL geçişi, Google Search Console doğrulaması ve yönetim panelinin anahtar teslim devreye alınması.',
            'status' => 'upcoming',
            'completed_at' => '',
        ],
    ];
}

function zeynart_normalize_client_tracker(array $raw): array
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($raw['id'] ?? '')) ?: ('trk_' . substr(bin2hex(random_bytes(4)), 0, 8));
    $codeRaw = strtolower(trim((string)($raw['code'] ?? '')));
    $code = preg_replace('/[^a-z0-9-]/', '', $codeRaw) ?: ('zyn-' . substr(bin2hex(random_bytes(3)), 0, 6));
    if (strlen($code) < 4) {
        $code = 'zyn-' . $code . substr(bin2hex(random_bytes(2)), 0, 4);
    }

    $status = strtolower(trim((string)($raw['status'] ?? 'active')));
    if (!in_array($status, ['active', 'awaiting_approval', 'completed', 'paused', 'draft'], true)) {
        $status = 'active';
    }

    $rawMilestones = is_array($raw['milestones'] ?? null) && $raw['milestones'] !== []
        ? $raw['milestones']
        : zeynart_default_tracker_milestones();

    $milestones = [];
    $completedCount = 0;
    $currentIdx = 0;
    foreach (array_values($rawMilestones) as $idx => $ms) {
        if (!is_array($ms)) {
            continue;
        }
        $mSt = strtolower(trim((string)($ms['status'] ?? 'upcoming')));
        if (!in_array($mSt, ['completed', 'current', 'upcoming'], true)) {
            $mSt = 'upcoming';
        }
        if ($mSt === 'completed') {
            $completedCount++;
        } elseif ($mSt === 'current') {
            $currentIdx = $idx + 1;
        }
        $milestones[] = [
            'step' => $idx + 1,
            'title' => trim((string)($ms['title'] ?? ('Aşama ' . ($idx + 1)))),
            'desc' => trim((string)($ms['desc'] ?? '')),
            'status' => $mSt,
            'completed_at' => trim((string)($ms['completed_at'] ?? '')),
        ];
    }
    if ($milestones === []) {
        $milestones = zeynart_default_tracker_milestones();
    }
    $totalSteps = count($milestones);
    $calcPct = $status === 'completed'
        ? 100
        : (int)min(99, round((($completedCount + ($currentIdx > 0 ? 0.5 : 0)) / max(1, $totalSteps)) * 100));
    $progressPct = isset($raw['progress_pct']) && is_numeric($raw['progress_pct']) && !empty($raw['manual_progress'])
        ? max(0, min(100, (int)$raw['progress_pct']))
        : $calcPct;

    $feedbacks = [];
    foreach ((is_array($raw['feedbacks'] ?? null) ? $raw['feedbacks'] : []) as $fb) {
        if (!is_array($fb)) {
            continue;
        }
        $feedbacks[] = [
            'id' => (string)($fb['id'] ?? ('fb_' . substr(bin2hex(random_bytes(3)), 0, 6))),
            'type' => in_array(($fb['type'] ?? 'approve'), ['approve', 'revision'], true) ? $fb['type'] : 'approve',
            'author' => trim((string)($fb['author'] ?? 'Müşteri')),
            'milestone_title' => trim((string)($fb['milestone_title'] ?? '')),
            'note' => trim((string)($fb['note'] ?? '')),
            'created_at' => (string)($fb['created_at'] ?? date('d.m.Y H:i')),
            'seen_by_admin' => !empty($fb['seen_by_admin']),
        ];
    }

    return [
        'id' => $id,
        'code' => $code,
        'client_name' => trim((string)($raw['client_name'] ?? '')),
        'client_company' => trim((string)($raw['client_company'] ?? '')),
        'client_phone' => trim((string)($raw['client_phone'] ?? '')),
        'client_email' => trim((string)($raw['client_email'] ?? '')),
        'project_title' => trim((string)($raw['project_title'] ?? 'Kurumsal Web Tasarım & Dijital Stüdyo Projesi')),
        'package_scope' => trim((string)($raw['package_scope'] ?? 'Kurumsal Web + Çoklu Dil + %100 PageSpeed SEO')),
        'manager_name' => trim((string)($raw['manager_name'] ?? 'Zeynart Proje Ekibi')),
        'start_date' => trim((string)($raw['start_date'] ?? date('Y-m-d'))),
        'estimated_delivery' => trim((string)($raw['estimated_delivery'] ?? date('Y-m-d', strtotime('+21 days')))),
        'demo_url' => trim((string)($raw['demo_url'] ?? '')),
        'live_url' => trim((string)($raw['live_url'] ?? '')),
        'proposal_code' => trim((string)($raw['proposal_code'] ?? '')),
        'vcard_slug' => trim((string)($raw['vcard_slug'] ?? '')),
        'status' => $status,
        'progress_pct' => $progressPct,
        'manual_progress' => !empty($raw['manual_progress']),
        'latest_update_note' => trim((string)($raw['latest_update_note'] ?? '')),
        'milestones' => $milestones,
        'feedbacks' => $feedbacks,
        'views' => max(0, (int)($raw['views'] ?? 0)),
        'last_viewed_at' => (string)($raw['last_viewed_at'] ?? ''),
        'created_at' => (string)($raw['created_at'] ?? date('c')),
        'updated_at' => date('c'),
    ];
}

function zeynart_load_client_trackers(bool $forceReload = false): array
{
    static $cached = null;
    if ($cached !== null && !$forceReload) {
        return $cached;
    }
    if ($forceReload) {
        unset($GLOBALS['__zeynart_sql_kv_mem']['client_trackers']);
    }
    $dec = zeynart_read_json_data(CLIENT_TRACKERS_FILE);
    if (!is_array($dec)) {
        $cached = [];
        return $cached;
    }
    $out = [];
    foreach ($dec as $row) {
        if (is_array($row)) {
            $out[] = zeynart_normalize_client_tracker($row);
        }
    }
    $cached = $out;
    return $cached;
}

function zeynart_save_client_trackers(array $trackers): bool
{
    $norm = [];
    $usedCodes = [];
    foreach ($trackers as $row) {
        if (!is_array($row)) {
            continue;
        }
        $t = zeynart_normalize_client_tracker($row);
        $baseCode = $t['code'];
        $n = 2;
        while (isset($usedCodes[$t['code']])) {
            $t['code'] = $baseCode . '-' . $n;
            $n++;
        }
        $usedCodes[$t['code']] = true;
        $norm[] = $t;
    }
    $ok = atomic_write_json(CLIENT_TRACKERS_FILE, $norm, true);
    if ($ok) {
        zeynart_load_client_trackers(true);
    }
    return $ok;
}

function zeynart_find_client_tracker_by_code(string $code, bool $includeDrafts = false): ?array
{
    $clean = strtolower(trim($code));
    if ($clean === '') {
        return null;
    }
    foreach (zeynart_load_client_trackers() as $trk) {
        if (strtolower((string)$trk['code']) === $clean) {
            if (($trk['status'] ?? 'active') === 'draft' && !$includeDrafts) {
                return null;
            }
            return $trk;
        }
    }
    return null;
}

function zeynart_record_client_tracker_view(string $code): ?array
{
    $all = zeynart_load_client_trackers(true);
    $updated = null;
    foreach ($all as $i => $trk) {
        if (strtolower((string)$trk['code']) === strtolower(trim($code))) {
            $all[$i]['views'] = (int)($trk['views'] ?? 0) + 1;
            $all[$i]['last_viewed_at'] = date('d.m.Y H:i');
            $updated = $all[$i];
            break;
        }
    }
    if ($updated !== null) {
        $GLOBALS['zeynart_skip_revision_capture'] = true;
        zeynart_save_client_trackers($all);
        unset($GLOBALS['zeynart_skip_revision_capture']);
    }
    return $updated;
}

function zeynart_compile_client_trackers_summary(): array
{
    $all = zeynart_load_client_trackers();
    $activeCount = 0;
    $awaitingCount = 0;
    $completedCount = 0;
    $unseenFeedbackCount = 0;

    foreach ($all as $t) {
        $st = (string)($t['status'] ?? 'active');
        if ($st === 'completed') {
            $completedCount++;
        } elseif ($st === 'awaiting_approval') {
            $awaitingCount++;
            $activeCount++;
        } elseif ($st === 'active') {
            $activeCount++;
        }
        foreach (($t['feedbacks'] ?? []) as $fb) {
            if (empty($fb['seen_by_admin'])) {
                $unseenFeedbackCount++;
            }
        }
    }

    return [
        'total' => count($all),
        'active_count' => $activeCount,
        'awaiting_count' => $awaitingCount,
        'completed_count' => $completedCount,
        'unseen_feedback_count' => $unseenFeedbackCount,
    ];
}

/* ============================================================================
 * 3. HOSTING, DOMAIN, SSL & ANNUAL MAINTENANCE RENEWAL RADAR
 * ============================================================================ */

function zeynart_normalize_renewal_item(array $raw): array
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($raw['id'] ?? '')) ?: ('rnw_' . substr(bin2hex(random_bytes(4)), 0, 8));
    $domainRaw = strtolower(trim((string)($raw['domain'] ?? '')));
    $domain = preg_replace('~^https?://~i', '', $domainRaw) ?? $domainRaw;
    $domain = trim(explode('/', $domain)[0] ?? '');

    $rDate = trim((string)($raw['renewal_date'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rDate)) {
        $rDate = date('Y-m-d', strtotime('+1 year'));
    }

    $currency = strtoupper(trim((string)($raw['currency'] ?? 'TRY')));
    if (!in_array($currency, ['TRY', 'USD', 'EUR'], true)) {
        $currency = 'TRY';
    }

    $status = strtolower(trim((string)($raw['status'] ?? 'active')));
    if (!in_array($status, ['active', 'reminded', 'paid', 'cancelled'], true)) {
        $status = 'active';
    }

    $todayTs = strtotime(date('Y-m-d') . ' 00:00:00') ?: time();
    $expTs = strtotime($rDate . ' 00:00:00') ?: $todayTs;
    $daysLeft = (int)round(($expTs - $todayTs) / 86400);

    $urgency = 'healthy';
    if ($status === 'cancelled') {
        $urgency = 'cancelled';
    } elseif ($daysLeft < 0) {
        $urgency = 'overdue';
    } elseif ($daysLeft <= 7) {
        $urgency = 'critical_7';
    } elseif ($daysLeft <= 15) {
        $urgency = 'warn_15';
    } elseif ($daysLeft <= 30) {
        $urgency = 'upcoming_30';
    }

    return [
        'id' => $id,
        'client_name' => trim((string)($raw['client_name'] ?? '')),
        'company' => trim((string)($raw['company'] ?? '')),
        'phone' => trim((string)($raw['phone'] ?? '')),
        'email' => trim((string)($raw['email'] ?? '')),
        'domain' => $domain,
        'service_type' => trim((string)($raw['service_type'] ?? 'Kurumsal Hosting + Domain + SSL')),
        'package_name' => trim((string)($raw['package_name'] ?? 'Pro LiteSpeed Paket')),
        'renewal_date' => $rDate,
        'days_left' => $daysLeft,
        'urgency' => $urgency,
        'amount' => round(max(0.0, (float)($raw['amount'] ?? 0)), 2),
        'currency' => $currency,
        'vcard_slug' => trim((string)($raw['vcard_slug'] ?? '')),
        'status' => $status,
        'notes' => trim((string)($raw['notes'] ?? '')),
        'last_reminded_at' => (string)($raw['last_reminded_at'] ?? ''),
        'last_paid_at' => (string)($raw['last_paid_at'] ?? ''),
        'updated_at' => date('c'),
    ];
}

function zeynart_load_client_renewals(bool $forceReload = false): array
{
    static $cached = null;
    if ($cached !== null && !$forceReload) {
        return $cached;
    }
    if ($forceReload) {
        unset($GLOBALS['__zeynart_sql_kv_mem']['client_renewals']);
    }
    $dec = zeynart_read_json_data(CLIENT_RENEWALS_FILE);
    if (!is_array($dec)) {
        $cached = [];
        return $cached;
    }
    $out = [];
    foreach ($dec as $row) {
        if (is_array($row)) {
            $out[] = zeynart_normalize_renewal_item($row);
        }
    }
    usort($out, static fn(array $a, array $b): int => strcmp((string)$a['renewal_date'], (string)$b['renewal_date']));
    $cached = $out;
    return $cached;
}

function zeynart_save_client_renewals(array $items): bool
{
    $norm = [];
    foreach ($items as $row) {
        if (is_array($row)) {
            $norm[] = zeynart_normalize_renewal_item($row);
        }
    }
    usort($norm, static fn(array $a, array $b): int => strcmp((string)$a['renewal_date'], (string)$b['renewal_date']));
    $ok = atomic_write_json(CLIENT_RENEWALS_FILE, $norm, true);
    if ($ok) {
        zeynart_load_client_renewals(true);
    }
    return $ok;
}

function zeynart_compile_renewals_summary(?array $items = null): array
{
    $list = $items ?? zeynart_load_client_renewals();
    $totalActive = 0;
    $expiring30 = 0;
    $expiring7 = 0;
    $overdue = 0;
    $annualTry = 0.0;

    foreach ($list as $it) {
        if (($it['status'] ?? 'active') === 'cancelled') {
            continue;
        }
        $totalActive++;
        $mult = match ($it['currency'] ?? 'TRY') {
            'USD' => 36.0,
            'EUR' => 39.0,
            default => 1.0,
        };
        $annualTry += ((float)($it['amount'] ?? 0)) * $mult;
        $dl = (int)($it['days_left'] ?? 999);
        if ($dl < 0) {
            $overdue++;
            $expiring30++;
        } elseif ($dl <= 7) {
            $expiring7++;
            $expiring30++;
        } elseif ($dl <= 30) {
            $expiring30++;
        }
    }

    return [
        'total_active' => $totalActive,
        'expiring_30_count' => $expiring30,
        'expiring_7_count' => $expiring7,
        'overdue_count' => $overdue,
        'annual_recurring_try' => round($annualTry, 2),
    ];
}

function zeynart_format_currency_amount(float $amount, string $currency = 'TRY'): string
{
    $sym = match (strtoupper($currency)) {
        'USD' => '$',
        'EUR' => '€',
        default => '₺',
    };
    return number_format($amount, 0, ',', '.') . ' ' . $sym;
}

function zeynart_build_renewal_email_html(array $rnw, array $store): string
{
    $brand = (string)($store['site']['name'] ?? 'ZEYNART');
    $clientLabel = trim((string)($rnw['client_name'] ?: ($rnw['company'] ?: 'Sayın Yetkili')));
    $domain = (string)($rnw['domain'] ?? '');
    $service = (string)($rnw['service_type'] ?? 'Hosting & Alan Adı Hizmeti');
    $dateTr = format_tr_date((string)($rnw['renewal_date'] ?? date('Y-m-d')));
    $amtStr = zeynart_format_currency_amount((float)($rnw['amount'] ?? 0), (string)($rnw['currency'] ?? 'TRY'));
    $vcardSlug = trim((string)($rnw['vcard_slug'] ?? ''));
    $billingUrl = $vcardSlug !== ''
        ? rtrim(canonical_url('/kartvizit/' . $vcardSlug . '/', $store, 'tr'), '/') . '/'
        : rtrim(canonical_url('/iletisim/', $store, 'tr'), '/') . '/';

    return '<div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:28px;background:#071318;color:#eaf4f7;border-radius:16px;border:1px solid rgba(45,212,191,.28)">'
        . '<p style="margin:0 0 8px;font-size:11px;letter-spacing:1.6px;text-transform:uppercase;color:#5eead4;font-weight:700">' . e($brand) . ' · HİZMET SÜREKLİLİĞİ &amp; YENİLEME BİLDİRİMİ</p>'
        . '<h2 style="margin:0 0 14px;font-size:21px;color:#ffffff">Sayın ' . e($clientLabel) . ',</h2>'
        . '<p style="margin:0 0 16px;font-size:14px;line-height:1.65;color:#cbd5e1">Kurumsal dijital sürekliliğinizin ve e-posta/web erişiminizin kesintisiz devam etmesi adına <strong>' . e($domain) . '</strong> alan adınıza ait yenileme dönemini hatırlatmak isteriz.</p>'
        . '<div style="padding:16px 18px;border-radius:12px;background:#0c1e26;border:1px solid rgba(94,234,212,.22);margin-bottom:18px">'
        . '<p style="margin:0 0 6px;font-size:13.5px"><strong>🌐 Alan Adı:</strong> <span style="color:#5eead4">' . e($domain) . '</span></p>'
        . '<p style="margin:0 0 6px;font-size:13.5px"><strong>📦 Hizmet Kapsamı:</strong> ' . e($service) . '</p>'
        . '<p style="margin:0 0 6px;font-size:13.5px"><strong>📅 Yenileme Tarihi:</strong> ' . e($dateTr) . '</p>'
        . '<p style="margin:0;font-size:14px"><strong>💳 Yıllık Yenileme Bedeli:</strong> <strong style="color:#6ee7b7">' . e($amtStr) . ' + KDV</strong></p>'
        . '</div>'
        . '<p style="margin:0 0 18px"><a href="' . e($billingUrl) . '" style="display:inline-block;padding:12px 22px;border-radius:999px;background:#14b8a6;color:#041316;font-weight:700;text-decoration:none;font-size:13.5px">Kurumsal Hesap (IBAN) &amp; İletişim Bilgilerini Görüntüle →</a></p>'
        . '<p style="margin:0;font-size:12px;color:#94a3b8">Ödemenizi tamamladıysanız veya otomatik yenileme talimatınız bulunuyorsa bu hatırlatmayı dikkate almayabilirsiniz.</p>'
        . '</div>';
}

/* ============================================================================
 * 4. PROGRAMMATIC SECTORAL & REGIONAL CORPORATE SOLUTION PAGE GENERATOR
 * ============================================================================ */

function zeynart_solution_page_catalog(): array
{
    return [
        'sectors' => [
            'ihracat-sanayi' => [
                'name' => 'İhracat & Endüstriyel Üretim',
                'eyebrow' => 'B2B İHRACAT & ENDÜSTRİYEL DİJİTAL STÜDYO',
                'wizard_preset' => 'industrial_b2b',
                'pain' => 'Yurt dışı satın alma yöneticileri tedarikçi seçerken yavaş açılan, çok dilli olmayan ve teknik ürün kataloglarını mobilde sunamayan siteleri ilk 5 saniyede terk eder.',
                'value' => 'TR · EN · DE · RU 4 dilli yayın motoru, %100 Google PageSpeed hızı, uluslararası hreflang SEO mimarisi ve hızlı RFQ teklif formlarıyla küresel pazarlarda güven inşa ediyoruz.',
                'kpis' => [
                    ['value' => '4 Dil', 'label' => 'TR · EN · DE · RU Küresel SEO'],
                    ['value' => '100/100', 'label' => 'PageSpeed & LSCache Performansı'],
                    ['value' => '< 0.5sn', 'label' => 'Avrupa & Global İlk Yanıt (TTFB)'],
                    ['value' => '+280%', 'label' => 'Nitelikli B2B İhracat Talebi'],
                ],
            ],
            'saglik-klinik' => [
                'name' => 'Sağlık, Klinik & Diş Merkezleri',
                'eyebrow' => 'SAĞLIK TURİZMİ & KLİNİK WEB MİMARİSİ',
                'wizard_preset' => 'clinic_service',
                'pain' => 'Hasta ve sağlık turizmi danışanları, tedavi detaylarına ve uzman hekim kadrosuna mobilde anında ulaşamadığında rakip kliniklere yönelir.',
                'value' => 'KVKK Zero-Cookie tam uyumlu altyapı, çok dilli sağlık turizmi sayfaları, WhatsApp/randevu dönüşüm kapsülü ve E-E-A-T uzman hekim şemalarıyla dijital hasta güvenini büyütüyoruz.',
                'kpis' => [
                    ['value' => '%100', 'label' => 'KVKK & Zero-Cookie Uyumluluğu'],
                    ['value' => '4 Dil', 'label' => 'Sağlık Turizmi Dil Altyapısı'],
                    ['value' => '99+', 'label' => 'Mobil Deneyim & Hız Skoru'],
                    ['value' => '7/24', 'label' => 'Akıllı Randevu & WhatsApp Hunisi'],
                ],
            ],
            'insaat-mimarlik' => [
                'name' => 'İnşaat, Mimarlık & Gayrimenkul Projeleri',
                'eyebrow' => 'MİMARİ PRESTİJ & PROJE LANSMAN STÜDYOSU',
                'wizard_preset' => 'construction_arch',
                'pain' => 'Yüksek bütçeli konut, villa ve ticari yapı projelerinde düşük çözünürlüklü veya ağır yüklenen galeriler projenin mimari prestijini zedeler.',
                'value' => 'Kırpılmayan yüksek çözünürlüklü WebP/AVIF proje vitrinleri, kat planı sunumları, yatırımcı talep formları ve kurumsal hız mühendisliğiyle lansman dönüşümlerini artırıyoruz.',
                'kpis' => [
                    ['value' => '0ms', 'label' => 'Akıcı WebP Görsel Vitrin Geçişi'],
                    ['value' => '100', 'label' => 'Google Core Web Vitals Skoru'],
                    ['value' => '360°', 'label' => 'Lansman & Yatırımcı Lead Hunisi'],
                    ['value' => '%99,9', 'label' => 'Kesintisiz Kurumsal Barındırma'],
                ],
            ],
            'hukuk-danismanlik' => [
                'name' => 'Hukuk, Mali Müşavirlik & Kurumsal Danışmanlık',
                'eyebrow' => 'KURUMSAL İTİBAR & UZMANLIK PLATFORMU',
                'wizard_preset' => 'web_software',
                'pain' => 'Kurumsal danışmanlık ve hukuk bürolarında karmaşık şablon siteler otorite algısını düşürür ve makalelerin arama motorlarında bulunmasını engeller.',
                'value' => 'Ağırbaşlı tipografi, hiyerarşik uzmanlık makaleleri, E-E-A-T yazar profilleri, sesli makale dinleme ve yapay zeka SEO (GEO) şemalarıyla dijital otoritenizi güçlendiriyoruz.',
                'kpis' => [
                    ['value' => '%98+', 'label' => 'Teknik SEO & Schema.org Skoru'],
                    ['value' => 'GEO', 'label' => 'ChatGPT & Perplexity Keşif Uyumu'],
                    ['value' => '100%', 'label' => 'KVKK & Bilgi Güvenliği Disiplini'],
                    ['value' => '15dk', 'label' => 'Online Ön Görüşme Planlayıcı'],
                ],
            ],
            'e-ticaret-perakende' => [
                'name' => 'E-Ticaret & Dijital Mağazacılık',
                'eyebrow' => 'YÜKSEK DÖNÜŞÜMLÜ E-TİCARET & CRO MİMARİSİ',
                'wizard_preset' => 'web_software',
                'pain' => 'Mobilde her 1 saniyelik gecikme sepet terk oranını %20 artırır; standart kalıp temalar markanızı fiyat rekabetine mahkûm eder.',
                'value' => 'Milisaniyelik ürün/kategori sayfaları, güvenli ödeme entegrasyonu, sepet dönüşüm optimizasyonu (CRO) ve teknik SEO mimarisiyle sürdürülebilir satış hacmi kuruyoruz.',
                'kpis' => [
                    ['value' => '3x', 'label' => 'Daha Hızlı Mobil Sayfa Geçişi'],
                    ['value' => 'CRO', 'label' => 'Sepet & Ödeme Dönüşüm Kurgusu'],
                    ['value' => 'SSL/2FA', 'label' => 'Uçtan Uca Güvenlik Kalkanı'],
                    ['value' => '4 Dil', 'label' => 'Sınır Ötesi E-İhracat Hazırlığı'],
                ],
            ],
            'turizm-otelcilik' => [
                'name' => 'Butik Otel, Turizm & Yatçılık',
                'eyebrow' => 'TURİZM & DOĞRUDAN REZERVASYON DENEYİMİ',
                'wizard_preset' => 'clinic_service',
                'pain' => 'Yüksek komisyonlu aracı platformlara bağımlı kalan tesisler, kendi web siteleri yavaş ve tek dilli olduğunda doğrudan rezervasyon gelirini kaybeder.',
                'value' => 'Dört dilde kusursuz deneyim sunan görsel hikâye anlatımı, doğrudan WhatsApp/rezervasyon talep motoru ve yerel/küresel SEO ile komisyonsuz misafir kazanımını büyütüyoruz.',
                'kpis' => [
                    ['value' => '%0', 'label' => 'Aracı Komisyonu — Doğrudan Talep'],
                    ['value' => 'TR/EN/DE/RU', 'label' => 'Yabancı Misafir Dil Uyumu'],
                    ['value' => '100', 'label' => 'Mobil Görsel & Hız Performansı'],
                    ['value' => '24/7', 'label' => 'Çok Dilli Hızlı İletişim'],
                ],
            ],
            'lojistik-dis-ticaret' => [
                'name' => 'Lojistik, Filo & Dış Ticaret',
                'eyebrow' => 'KÜRESEL TEDARİK & LOJİSTİK WEB ALTYAPISI',
                'wizard_preset' => 'industrial_b2b',
                'pain' => 'Uluslararası taşımacılık ve dış ticaret firmalarında operasyonel hız ve kurumsal güveni yansıtmayan eski web siteleri kurumsal ihalelerde dezavantaj yaratır.',
                'value' => 'Hizmet hatlarını netleştiren interaktif navlun/teklif sihirbazı, 4 dilli kurumsal sunum ve yüksek erişilebilirlikli LiteSpeed altyapısıyla küresel prestij sağlıyoruz.',
                'kpis' => [
                    ['value' => '%99,9', 'label' => 'Kesintisiz Uptime & SSL İzleme'],
                    ['value' => '4 Dil', 'label' => 'Uluslararası Hat & Hizmet Sunumu'],
                    ['value' => '30sn', 'label' => 'İnteraktif Navlun/Teklif Sihirbazı'],
                    ['value' => 'A+', 'label' => 'Kurumsal Güvenlik Başlıkları'],
                ],
            ],
            'teknoloji-saas' => [
                'name' => 'Teknoloji, Yazılım & B2B SaaS',
                'eyebrow' => 'DİJİTAL ÜRÜN & B2B SAAS BÜYÜME STÜDYOSU',
                'wizard_preset' => 'web_software',
                'pain' => 'Karmaşık teknik ürünleri sade bir değer önerisiyle anlatamayan SaaS ve teknoloji şirketleri demo taleplerinde potansiyelinin altında kalır.',
                'value' => 'Ürün mimarisini berraklaştıran modern arayüz sistemi, canlı vaka metrikleri, yapay zeka arama motoru (GEO / llms.txt) uyumu ve demo görüşme planlayıcıyla büyümeyi hızlandırıyoruz.',
                'kpis' => [
                    ['value' => 'llms.txt', 'label' => 'Yapay Zeka Motorları İçin Tam Keşif'],
                    ['value' => '100/100', 'label' => 'Sıfır Şişkinlik — Saf Performans'],
                    ['value' => 'Demo', 'label' => '15 Dk Online Keşif Planlayıcı'],
                    ['value' => 'v3', 'label' => 'Kendi Kendine Yönetilebilir Stüdyo'],
                ],
            ],
        ],
        'regions' => [
            '' => 'Türkiye Geneli (Bölgesiz Sektörel Çözüm)',
            'İstanbul' => 'İstanbul',
            'Ankara' => 'Ankara',
            'İzmir' => 'İzmir',
            'Bursa' => 'Bursa',
            'Kocaeli' => 'Kocaeli',
            'Antalya' => 'Antalya',
            'Konya' => 'Konya',
            'Gaziantep' => 'Gaziantep',
            'Avrupa & Global' => 'Avrupa & Global İhracat Pazarı',
        ],
    ];
}

function zeynart_build_programmatic_solution_page(string $sectorKey, string $customSectorName = '', string $region = '', string $customSlug = ''): array
{
    $cat = zeynart_solution_page_catalog();
    $tpl = $cat['sectors'][$sectorKey] ?? $cat['sectors']['ihracat-sanayi'];
    $sectorTitle = trim($customSectorName) !== '' ? trim($customSectorName) : (string)$tpl['name'];
    $regionClean = trim($region);

    $fullTitle = $regionClean !== ''
        ? ($regionClean . ' ' . $sectorTitle . ' Web Tasarım & Dijital Stüdyo Çözümleri')
        : ($sectorTitle . ' Odaklı Kurumsal Web Tasarım & SEO Çözümleri');

    $slugBase = trim($customSlug) !== ''
        ? slugify($customSlug)
        : slugify(($regionClean !== '' ? $regionClean . '-' : '') . $sectorTitle . '-web-tasarim');
    if ($slugBase === '') {
        $slugBase = 'kurumsal-cozum-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    $locPhrase = $regionClean !== '' ? ($regionClean . ' merkezli ve küresel ölçekte faaliyet gösteren ') : '';
    $excerpt = $locPhrase . $sectorTitle . ' markaları için %100 PageSpeed hızında, 4 dil destekli, KVKK Zero-Cookie uyumlu ve doğrudan müşteri dönüşümü üreten kurumsal web mimarisi.';

    $contentHtml = '<h2 id="sektorel-ihtiyac">Neden ' . e($sectorTitle) . ' Sektöründe Standart Şablon Siteler Yetersiz Kalır?</h2>'
        . '<p>' . e($tpl['pain']) . '</p>'
        . '<p><strong>Zeynart Dijital Stüdyo</strong> olarak ' . e($locPhrase . $sectorTitle) . ' firmalarının dijital vitrinini yalnızca görsel bir broşür olarak değil; 7/24 çalışan, ölçülebilir teklif ve görüşme üreten yüksek performanslı bir büyüme motoru olarak kurguluyoruz.</p>'
        . '<h2 id="zeynart-yaklasimi">Zeynart Mühendisliği ile Ölçülebilir Dijital Üstünlük</h2>'
        . '<p>' . e($tpl['value']) . '</p>'
        . '<ul>'
        . '<li><strong>%100 PageSpeed &amp; LiteSpeed LSCache Mimarisi:</strong> Ağır eklenti yığınları olmadan milisaniyelik sayfa açılışı.</li>'
        . '<li><strong>Dört Dilli Küresel Yayın (TR · EN · DE · RU):</strong> Otomatik <code>hreflang</code>, yerelleştirilmiş URL yapısı ve yapay zeka destekli editoryal çeviri.</li>'
        . '<li><strong>Yapay Zeka Arama Motoru (GEO) Uyumu:</strong> Google yanında ChatGPT, Gemini ve Perplexity önerilerinde öne çıkaran <code>Schema.org</code> ve <code>llms.txt</code> katmanı.</li>'
        . '<li><strong>Apple Cam Yönetim Stüdyosu v3:</strong> Teknik bilgi gerektirmeden tüm sayfaları, görselleri ve blog içeriklerini tek ekrandan canlı yönetme özgürlüğü.</li>'
        . '</ul>';

    $steps = [
        ['num' => '01', 'icon' => 'compass', 'title' => 'Sektörel Analiz & Mimari', 'desc' => $sectorTitle . ' dinamiklerine uygun bilgi mimarisi, rakip SEO analizi ve dönüşüm kurgusu.', 'output' => 'Yol Haritası & Tel Kafes'],
        ['num' => '02', 'icon' => 'sparkles', 'title' => 'Özgün Kurumsal UI/UX', 'desc' => 'Hazır tema kullanmadan markanızın prestijini yansıtan mobil öncelikli tasarım sistemi.', 'output' => 'Onaylı Arayüz Tasarımı'],
        ['num' => '03', 'icon' => 'code', 'title' => 'Saf PHP & 4 Dil Kodlama', 'desc' => 'Sıfır şişkinlik ilkesiyle yüksek hızlı kodlama, yönetim stüdyosu ve çoklu dil entegrasyonu.', 'output' => 'Canlı Test Ortamı'],
        ['num' => '04', 'icon' => 'shield', 'title' => 'Hız, Güvenlik & Lansman', 'desc' => 'Core Web Vitals doğrulaması, KVKK Zero-Cookie kontrolü, SSL ve arama motoru indeksleme.', 'output' => '99+ Performanslı Yayın'],
    ];

    $sections = [
        [
            'id' => 'sec_' . substr(bin2hex(random_bytes(3)), 0, 6),
            'layout' => '1',
            'style' => ['padding_y' => 36, 'gap' => 24, 'container' => 'boxed', 'visibility' => 'all'],
            'columns' => [
                ['widgets' => [['type' => 'stats_bar', 'props' => ['items' => $tpl['kpis']]]]],
            ],
        ],
        [
            'id' => 'sec_' . substr(bin2hex(random_bytes(3)), 0, 6),
            'layout' => '1',
            'style' => ['padding_y' => 48, 'gap' => 28, 'container' => 'boxed', 'visibility' => 'all'],
            'columns' => [
                ['widgets' => [['type' => 'process_steps', 'props' => ['steps' => $steps]]]],
            ],
        ],
        [
            'id' => 'sec_' . substr(bin2hex(random_bytes(3)), 0, 6),
            'layout' => '1',
            'style' => ['padding_y' => 44, 'gap' => 24, 'container' => 'boxed', 'visibility' => 'all'],
            'columns' => [
                ['widgets' => [['type' => 'client_logos', 'props' => ['limit' => 12]]]],
            ],
        ],
        [
            'id' => 'sec_' . substr(bin2hex(random_bytes(3)), 0, 6),
            'layout' => '1',
            'style' => ['padding_y' => 48, 'gap' => 28, 'container' => 'boxed', 'visibility' => 'all'],
            'columns' => [
                ['widgets' => [['type' => 'contact_form_embed', 'props' => []]]],
            ],
        ],
    ];

    $faqs = [
        [
            'q' => $sectorTitle . ' web sitesi projesi ortalama kaç günde tamamlanır?',
            'a' => 'Proje kapsamına, entegrasyonlara ve dil sayısına bağlı olarak ortalama 2 ile 4 hafta arasında tüm hız, güvenlik ve SEO testleri tamamlanmış şekilde anahtar teslim yayına alınır.',
        ],
        [
            'q' => 'Sitemizi yayına aldıktan sonra içerik ve görselleri kendimiz güncelleyebilir miyiz?',
            'a' => 'Evet. Zeynart Dijital Stüdyo v4.0 yönetim paneli ve canlı sayfa düzenleyici sayesinde kod bilgisine ihtiyaç duymadan tüm metinleri, görselleri, blog kategorilerini ve menüleri saniyeler içinde güncelleyebilirsiniz.',
        ],
        [
            'q' => 'Yurt dışı müşteriler için çoklu dil ve uluslararası Google SEO altyapısı hazır mı?',
            'a' => 'Evet. Türkçe ana yapının yanında İngilizce (EN), Almanca (DE) ve Rusça (RU) dil rotaları, otomatik hreflang etiketleri ve çok dilli XML site haritası yerleşik olarak sunulur.',
        ],
        [
            'q' => 'KVKK çerez uyumu ve Google PageSpeed hız garantisi nasıl sağlanıyor?',
            'a' => 'Açık rıza öncesi sıfır çerez (Zero-Cookie Load) mimarisi, yerel .woff2 fontlar, otomatik WebP görsel sıkıştırma ve LiteSpeed LSCache önbellekleme standart olarak uygulanır.',
        ],
    ];

    return [
        'id' => 'cp_' . substr(bin2hex(random_bytes(4)), 0, 8),
        'slug' => $slugBase,
        'title' => $fullTitle,
        'eyebrow' => (string)$tpl['eyebrow'],
        'excerpt' => $excerpt,
        'banner_image' => '/images/banners/services.webp',
        'show_page_header' => true,
        'show_cta' => true,
        'status' => 'published',
        'content_html' => $contentHtml,
        'sections' => $sections,
        'faqs' => $faqs,
        'seo' => [
            'meta_title' => mb_substr($fullTitle . ' | ZEYNART', 0, 68, 'UTF-8'),
            'meta_desc' => mb_substr($excerpt, 0, 158, 'UTF-8'),
            'focus_keyword' => trim(($regionClean !== '' ? $regionClean . ' ' : '') . $sectorTitle . ' web tasarım'),
            'robots' => 'index, follow',
            'ai_summary' => $excerpt,
        ],
        'solution_meta' => [
            'is_solution_page' => true,
            'sector_key' => $sectorKey,
            'sector_name' => $sectorTitle,
            'region_name' => $regionClean,
            'wizard_preset' => (string)$tpl['wizard_preset'],
            'service_schema' => true,
            'generated_at' => date('c'),
        ],
        'updated_at' => date('c'),
    ];
}

/* ============================================================================
 * 5. COOKIE-FREE 15-MINUTE DISCOVERY MEETING SCHEDULER (/gorusme-planla/)
 * ============================================================================ */

function zeynart_get_booking_settings(?array $store = null): array
{
    $st = $store ?? load_store();
    $raw = is_array($st['booking_settings'] ?? null) ? $st['booking_settings'] : [];
    $days = is_array($raw['working_days'] ?? null) && $raw['working_days'] !== []
        ? array_values(array_intersect($raw['working_days'], [1, 2, 3, 4, 5, 6, 7]))
        : [1, 2, 3, 4, 5, 6]; // Mon-Sat

    $duration = (int)($raw['slot_minutes'] ?? 30);
    if (!in_array($duration, [15, 20, 30, 45, 60], true)) {
        $duration = 30;
    }

    return [
        'enabled' => ($raw['enabled'] ?? true) !== false,
        'show_on_contact' => ($raw['show_on_contact'] ?? true) !== false,
        'eyebrow' => trim((string)($raw['eyebrow'] ?? '')) ?: '15 DAKİKALIK DİJİTAL KEŞİF GÖRÜŞMESİ',
        'heading' => trim((string)($raw['heading'] ?? '')) ?: 'Projenizi birlikte netleştirelim.',
        'lead' => trim((string)($raw['lead'] ?? '')) ?: 'Takvimimizden size uygun günü ve saati seçin; üçüncü parti çerez veya karmaşık formlar olmadan 15 dakikalık odaklanmış ön görüşmemizi planlayalım.',
        'working_days' => array_map('intval', $days),
        'start_hour' => preg_match('/^\d{2}:\d{2}$/', (string)($raw['start_hour'] ?? '')) ? (string)$raw['start_hour'] : '10:00',
        'end_hour' => preg_match('/^\d{2}:\d{2}$/', (string)($raw['end_hour'] ?? '')) ? (string)$raw['end_hour'] : '17:30',
        'slot_minutes' => $duration,
        'days_ahead' => max(5, min(21, (int)($raw['days_ahead'] ?? 10))),
        'channels' => [
            ['key' => 'google_meet', 'label' => 'Google Meet (Online Ekran Paylaşımlı)', 'badge' => '📹 Online Sunum'],
            ['key' => 'whatsapp', 'label' => 'WhatsApp Sesli / Görüntülü Görüşme', 'badge' => '💬 Hızlı Görüşme'],
            ['key' => 'phone', 'label' => 'Doğrudan Telefon Görüşmesi', 'badge' => '📞 Telefon'],
        ],
    ];
}

function zeynart_load_bookings(bool $forceReload = false): array
{
    static $cached = null;
    if ($cached !== null && !$forceReload) {
        return $cached;
    }
    if ($forceReload) {
        unset($GLOBALS['__zeynart_sql_kv_mem']['bookings']);
    }
    $dec = zeynart_read_json_data(DISCOVERY_BOOKINGS_FILE);
    $cached = is_array($dec) ? array_values(array_filter($dec, 'is_array')) : [];
    return $cached;
}

function zeynart_save_bookings(array $bookings): bool
{
    $ok = atomic_write_json(DISCOVERY_BOOKINGS_FILE, array_values($bookings), true);
    if ($ok) {
        zeynart_load_bookings(true);
    }
    return $ok;
}

function zeynart_compute_available_booking_days(?array $store = null): array
{
    $cfg = zeynart_get_booking_settings($store);
    $bookings = zeynart_load_bookings();
    $takenMap = [];
    foreach ($bookings as $bk) {
        if (($bk['status'] ?? 'confirmed') === 'cancelled') {
            continue;
        }
        $k = trim((string)($bk['date'] ?? '')) . '|' . trim((string)($bk['time'] ?? ''));
        if ($k !== '|') {
            $takenMap[$k] = true;
        }
    }

    [$sh, $sm] = array_map('intval', explode(':', $cfg['start_hour'] . ':00'));
    [$eh, $em] = array_map('intval', explode(':', $cfg['end_hour'] . ':00'));
    $startMins = ($sh * 60) + $sm;
    $endMins = ($eh * 60) + $em;
    if ($endMins <= $startMins) {
        $startMins = 600;
        $endMins = 1050;
    }

    $trDays = [1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'];
    $trMonths = [1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis', 5 => 'May', 6 => 'Haz', 7 => 'Tem', 8 => 'Ağu', 9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara'];

    $daysOut = [];
    $nowTs = time();
    $todayIso = date('Y-m-d', $nowTs);
    $nowMins = ((int)date('G', $nowTs) * 60) + (int)date('i', $nowTs) + 45;

    for ($offset = 0; $offset < 28 && count($daysOut) < $cfg['days_ahead']; $offset++) {
        $ts = strtotime("+{$offset} days", strtotime($todayIso . ' 12:00:00')) ?: $nowTs;
        $dow = (int)date('N', $ts);
        if (!in_array($dow, $cfg['working_days'], true)) {
            continue;
        }
        $iso = date('Y-m-d', $ts);
        $slots = [];
        for ($m = $startMins; ($m + $cfg['slot_minutes']) <= $endMins; $m += $cfg['slot_minutes']) {
            if ($iso === $todayIso && $m <= $nowMins) {
                continue;
            }
            // Skip lunch break 12:30 - 13:29 gently for realism
            if ($m >= 750 && $m < 810) {
                continue;
            }
            $hh = str_pad((string)intdiv($m, 60), 2, '0', STR_PAD_LEFT);
            $mm = str_pad((string)($m % 60), 2, '0', STR_PAD_LEFT);
            $timeStr = $hh . ':' . $mm;
            $isBooked = isset($takenMap[$iso . '|' . $timeStr]);
            $slots[] = [
                'time' => $timeStr,
                'available' => !$isBooked,
            ];
        }
        if ($slots === []) {
            continue;
        }
        $dayNum = (int)date('j', $ts);
        $monNum = (int)date('n', $ts);
        $daysOut[] = [
            'date' => $iso,
            'dow_short' => $trDays[$dow] ?? '',
            'day_num' => (string)$dayNum,
            'month_short' => $trMonths[$monNum] ?? '',
            'label' => $dayNum . ' ' . ($trMonths[$monNum] ?? '') . ' ' . ($trDays[$dow] ?? ''),
            'slots' => $slots,
        ];
    }

    return $daysOut;
}

function zeynart_compile_booking_ics(array $booking, array $store): string
{
    $dateIso = (string)($booking['date'] ?? date('Y-m-d'));
    $timeStr = (string)($booking['time'] ?? '10:00');
    $durMin = max(15, (int)($booking['duration_minutes'] ?? 30));
    $tz = new DateTimeZone('Europe/Istanbul');
    $startDt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateIso . ' ' . $timeStr, $tz) ?: new DateTimeImmutable('now', $tz);
    $endDt = $startDt->modify("+{$durMin} minutes");
    $utc = new DateTimeZone('UTC');

    $dtStartUtc = $startDt->setTimezone($utc)->format('Ymd\THis\Z');
    $dtEndUtc = $endDt->setTimezone($utc)->format('Ymd\THis\Z');
    $dtStamp = gmdate('Ymd\THis\Z');
    $uid = ($booking['id'] ?? 'bk') . '@zeynart.com';

    $brand = (string)($store['site']['name'] ?? 'ZEYNART');
    $summary = $brand . ' — Dijital Keşif Görüşmesi (' . ($booking['name'] ?? 'Müşteri') . ')';
    $channelLabel = (string)($booking['channel_label'] ?? 'Online / Telefon');
    $desc = 'Görüşme Kanalı: ' . $channelLabel . "\\nKonu: " . str_replace(["\r", "\n"], ' ', (string)($booking['topic'] ?? '')) . "\\nİletişim: " . (string)($booking['phone'] ?? '');

    $escIcs = static fn(string $s): string => str_replace([',', ';'], ['\,', '\;'], $s);

    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Zeynart Digital Studio v3//Discovery Scheduler//TR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . $dtStamp,
        'DTSTART:' . $dtStartUtc,
        'DTEND:' . $dtEndUtc,
        'SUMMARY:' . $escIcs($summary),
        'DESCRIPTION:' . $escIcs($desc),
        'LOCATION:' . $escIcs($channelLabel),
        'STATUS:CONFIRMED',
        'END:VEVENT',
        'END:VCALENDAR',
    ];
    return implode("\r\n", $lines) . "\r\n";
}

function zeynart_compile_bookings_summary(): array
{
    $today = date('Y-m-d');
    $all = zeynart_load_bookings();
    $upcoming = 0;
    $todayCount = 0;
    foreach ($all as $b) {
        if (($b['status'] ?? 'confirmed') === 'cancelled') {
            continue;
        }
        $d = (string)($b['date'] ?? '');
        if ($d >= $today) {
            $upcoming++;
            if ($d === $today) {
                $todayCount++;
            }
        }
    }
    return [
        'total' => count($all),
        'upcoming_count' => $upcoming,
        'today_count' => $todayCount,
    ];
}
