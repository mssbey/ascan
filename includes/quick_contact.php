<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/icons.php';

/**
 * ZEYNART AKILLI HIZLI İLETİŞİM BARI (Sayfa Duyarlı WhatsApp + Hızlı Arama)
 * - Göz yormayan lüks cam efektli (glassmorphism) sağ/sol alt kapsül
 * - Ziyaretçinin bulunduğu sayfaya göre dinamik WhatsApp ön-mesajı (TR · EN · DE · RU)
 * - Çalışma saatleri (Europe/Istanbul) canlı durum göstergesi ve mesai kontrolü
 */

function get_quick_contact_defaults(?array $store = null): array
{
    $sitePhone = trim((string)($store['site']['phone'] ?? '0850 305 0 533')) ?: '0850 305 0 533';
    $sitePhoneHref = trim((string)($store['site']['phone_href'] ?? '+908503050533')) ?: '+908503050533';

    return [
        'enabled' => true,
        'position' => 'right', // 'right' | 'left'
        'show_whatsapp' => true,
        'show_phone' => true,
        'whatsapp_number' => $sitePhoneHref,
        'whatsapp_label' => 'WhatsApp',
        'phone_display' => $sitePhone,
        'phone_href' => $sitePhoneHref,
        'phone_label' => 'Hızlı Arama',
        'working_hours' => [
            'enabled' => true,
            'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
            'start' => '09:00',
            'end' => '18:30',
            'online_label' => 'Çevrimiçi · Hızlı Yanıt',
            'offline_label' => 'Mesai Dışı · Mesaj Bırakın',
            'hide_phone_when_offline' => false,
            'hide_bar_when_offline' => false,
        ],
        'messages' => [
            'default' => 'Merhaba, dijital çözümleriniz hakkında bilgi almak istiyorum',
            'service' => 'Merhaba, {title} çözümleriniz hakkında bilgi almak istiyorum',
            'project' => 'Merhaba, {title} projenizi inceledim; benzer bir çalışma hakkında bilgi almak istiyorum',
            'blog' => 'Merhaba, "{title}" yazınızı inceledim; çözümleriniz hakkında bilgi almak istiyorum',
            'audit' => 'Merhaba, web sitemiz için hız ve SEO iyileştirme çözümleriniz hakkında bilgi almak istiyorum',
            'contact' => 'Merhaba, projemiz için teklif ve bilgi almak istiyorum',
        ],
        'custom_rules' => [],
    ];
}

function get_quick_contact_state(array $store): array
{
    $defaults = get_quick_contact_defaults($store);
    $raw = is_array($store['quick_contact'] ?? null) ? $store['quick_contact'] : [];

    $pos = strtolower(trim((string)($raw['position'] ?? $defaults['position'])));
    if (!in_array($pos, ['right', 'left'], true)) {
        $pos = 'right';
    }

    $whRaw = is_array($raw['working_hours'] ?? null) ? $raw['working_hours'] : [];
    $validDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    $days = [];
    if (isset($whRaw['days']) && is_array($whRaw['days'])) {
        foreach ($whRaw['days'] as $d) {
            $dc = strtolower(trim((string)$d));
            if (in_array($dc, $validDays, true) && !in_array($dc, $days, true)) {
                $days[] = $dc;
            }
        }
    } else {
        $days = $defaults['working_hours']['days'];
    }

    $start = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', trim((string)($whRaw['start'] ?? '')))
        ? trim((string)$whRaw['start'])
        : $defaults['working_hours']['start'];
    $end = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', trim((string)($whRaw['end'] ?? '')))
        ? trim((string)$whRaw['end'])
        : $defaults['working_hours']['end'];

    $msgRaw = is_array($raw['messages'] ?? null) ? $raw['messages'] : [];
    $messages = [];
    foreach ($defaults['messages'] as $mk => $mv) {
        $val = trim((string)($msgRaw[$mk] ?? ''));
        $messages[$mk] = $val !== '' ? $val : $mv;
    }

    $customRules = [];
    if (isset($raw['custom_rules']) && is_array($raw['custom_rules'])) {
        foreach ($raw['custom_rules'] as $cr) {
            if (!is_array($cr)) {
                continue;
            }
            $pfx = trim((string)($cr['path_prefix'] ?? ''));
            $msg = trim((string)($cr['message'] ?? ''));
            if ($pfx !== '' && $msg !== '') {
                $customRules[] = [
                    'path_prefix' => '/' . ltrim($pfx, '/'),
                    'message' => $msg,
                ];
            }
        }
    }

    return [
        'enabled' => array_key_exists('enabled', $raw) ? !empty($raw['enabled']) : $defaults['enabled'],
        'position' => $pos,
        'show_whatsapp' => array_key_exists('show_whatsapp', $raw) ? !empty($raw['show_whatsapp']) : $defaults['show_whatsapp'],
        'show_phone' => array_key_exists('show_phone', $raw) ? !empty($raw['show_phone']) : $defaults['show_phone'],
        'whatsapp_number' => trim((string)($raw['whatsapp_number'] ?? '')) ?: $defaults['whatsapp_number'],
        'whatsapp_label' => trim((string)($raw['whatsapp_label'] ?? '')) ?: $defaults['whatsapp_label'],
        'phone_display' => trim((string)($raw['phone_display'] ?? '')) ?: $defaults['phone_display'],
        'phone_href' => trim((string)($raw['phone_href'] ?? '')) ?: $defaults['phone_href'],
        'phone_label' => trim((string)($raw['phone_label'] ?? '')) ?: $defaults['phone_label'],
        'working_hours' => [
            'enabled' => array_key_exists('enabled', $whRaw) ? !empty($whRaw['enabled']) : $defaults['working_hours']['enabled'],
            'days' => $days,
            'start' => $start,
            'end' => $end,
            'online_label' => trim((string)($whRaw['online_label'] ?? '')) ?: $defaults['working_hours']['online_label'],
            'offline_label' => trim((string)($whRaw['offline_label'] ?? '')) ?: $defaults['working_hours']['offline_label'],
            'hide_phone_when_offline' => !empty($whRaw['hide_phone_when_offline']),
            'hide_bar_when_offline' => !empty($whRaw['hide_bar_when_offline']),
        ],
        'messages' => $messages,
        'custom_rules' => array_slice($customRules, 0, 25),
    ];
}

function evaluate_quick_contact_schedule(array $qc): array
{
    $wh = $qc['working_hours'] ?? [];
    if (empty($wh['enabled'])) {
        return [
            'tracking_enabled' => false,
            'online' => true,
            'status_label' => t((string)($wh['online_label'] ?? 'Çevrimiçi · Hızlı Yanıt')),
            'hours_range' => '',
        ];
    }

    $tz = new DateTimeZone('Europe/Istanbul');
    $now = new DateTimeImmutable('now', $tz);
    $dayMap = [
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
        7 => 'sun',
    ];
    $todayCode = $dayMap[(int)$now->format('N')] ?? 'mon';
    $nowHm = $now->format('H:i');

    $days = is_array($wh['days'] ?? null) ? $wh['days'] : [];
    $start = (string)($wh['start'] ?? '09:00');
    $end = (string)($wh['end'] ?? '18:30');

    $inDay = in_array($todayCode, $days, true);
    if ($start <= $end) {
        $inTime = ($nowHm >= $start && $nowHm <= $end);
    } else {
        // Overnight shift support (e.g. 20:00 -> 02:00)
        $inTime = ($nowHm >= $start || $nowHm <= $end);
    }

    $isOnline = $inDay && $inTime;
    $rawLabel = $isOnline
        ? (string)($wh['online_label'] ?? 'Çevrimiçi · Hızlı Yanıt')
        : (string)($wh['offline_label'] ?? 'Mesai Dışı · Mesaj Bırakın');

    return [
        'tracking_enabled' => true,
        'online' => $isOnline,
        'status_label' => t($rawLabel),
        'hours_range' => $start . ' – ' . $end,
    ];
}

function resolve_quick_contact_message(array $qc, array $context, ?string $lang = null): string
{
    $code = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    $routePath = '/' . ltrim((string)($context['route_path'] ?? '/'), '/');
    $type = (string)($context['type'] ?? 'default');
    $title = trim((string)($context['title'] ?? ''));

    // 1. Check custom path prefix rules first
    foreach (($qc['custom_rules'] ?? []) as $rule) {
        $pfx = (string)($rule['path_prefix'] ?? '');
        $msg = trim((string)($rule['message'] ?? ''));
        if ($pfx !== '' && $msg !== '' && str_starts_with(strtolower($routePath), strtolower($pfx))) {
            return str_replace(['{title}', '{service}'], $title, t($msg, $code));
        }
    }

    // 2. Native localized templates when default Turkish templates are in use
    $defaults = get_quick_contact_defaults()['messages'];
    $configuredTpl = (string)($qc['messages'][$type] ?? $qc['messages']['default'] ?? $defaults['default']);
    $defaultForType = (string)($defaults[$type] ?? $defaults['default']);

    if ($code !== 'tr' && $configuredTpl === $defaultForType) {
        $nativeTemplates = [
            'en' => [
                'default' => 'Hello, I would like to get information about your digital solutions',
                'service' => 'Hello, I would like to get information about your {title} solutions',
                'project' => 'Hello, I explored your {title} case study and would like to discuss a similar project',
                'blog' => 'Hello, I read your article "{title}" and would like to learn more about your solutions',
                'audit' => 'Hello, I would like to get information about your website speed & SEO optimization solutions',
                'contact' => 'Hello, I would like to request a quote and information for our project',
            ],
            'de' => [
                'default' => 'Hallo, ich möchte gerne Informationen zu Ihren digitalen Lösungen erhalten',
                'service' => 'Hallo, ich möchte gerne Informationen zu Ihren {title}-Lösungen erhalten',
                'project' => 'Hallo, ich habe Ihr Projekt {title} gesehen und möchte über ein ähnliches Vorhaben sprechen',
                'blog' => 'Hallo, ich habe Ihren Beitrag „{title}“ gelesen und möchte mehr über Ihre Lösungen erfahren',
                'audit' => 'Hallo, ich möchte gerne Informationen zu Ihren Website-Speed- und SEO-Optimierungslösungen erhalten',
                'contact' => 'Hallo, ich möchte gerne ein Angebot und Informationen für unser Projekt anfragen',
            ],
            'ru' => [
                'default' => 'Здравствуйте, я хотел(а) бы получить информацию о ваших цифровых решениях',
                'service' => 'Здравствуйте, я хотел(а) бы получить информацию о ваших решениях в сфере «{title}»',
                'project' => 'Здравствуйте, я изучил(а) ваш проект «{title}» и хочу обсудить похожую задачу',
                'blog' => 'Здравствуйте, я прочитал(а) вашу статью «{title}» и хочу узнать подробнее о ваших услугах',
                'audit' => 'Здравствуйте, я хотел(а) бы узнать о ваших решениях по ускорению сайта и SEO-оптимизации',
                'contact' => 'Здравствуйте, я хотел(а) бы запросить расчёт и консультацию по нашему проекту',
            ],
        ];
        $tpl = $nativeTemplates[$code][$type] ?? $nativeTemplates[$code]['default'] ?? $configuredTpl;
    } else {
        $tpl = t($configuredTpl, $code);
    }

    if ($title === '' && str_contains($tpl, '{title}')) {
        $fallbackTpl = (string)($qc['messages']['default'] ?? $defaults['default']);
        return t($fallbackTpl, $code);
    }

    return trim(str_replace(['{title}', '{service}'], $title, $tpl));
}

function detect_quick_contact_context(): array
{
    $routePath = (string)($GLOBALS['currentRoutePath'] ?? '/');
    if (isset($GLOBALS['activeService']) && is_array($GLOBALS['activeService'])) {
        return [
            'route_path' => $routePath,
            'type' => 'service',
            'title' => (string)($GLOBALS['activeService']['title'] ?? ''),
        ];
    }
    if (isset($GLOBALS['activeProject']) && is_array($GLOBALS['activeProject'])) {
        return [
            'route_path' => $routePath,
            'type' => 'project',
            'title' => (string)($GLOBALS['activeProject']['title'] ?? ''),
        ];
    }
    if (isset($GLOBALS['activePost']) && is_array($GLOBALS['activePost'])) {
        return [
            'route_path' => $routePath,
            'type' => 'blog',
            'title' => (string)($GLOBALS['activePost']['title'] ?? ''),
        ];
    }
    if ($routePath === '/site-analiz/') {
        return [
            'route_path' => $routePath,
            'type' => 'audit',
            'title' => '',
        ];
    }
    if ($routePath === '/iletisim/') {
        return [
            'route_path' => $routePath,
            'type' => 'contact',
            'title' => '',
        ];
    }
    if (isset($GLOBALS['customPage']) && is_array($GLOBALS['customPage'])) {
        return [
            'route_path' => $routePath,
            'type' => 'service',
            'title' => (string)($GLOBALS['customPage']['title'] ?? ''),
        ];
    }

    return [
        'route_path' => $routePath,
        'type' => 'default',
        'title' => '',
    ];
}

function render_quick_contact_bar(array $store, ?array $overrideContext = null): string
{
    $qc = get_quick_contact_state($store);
    if (empty($qc['enabled'])) {
        return '';
    }

    $sched = evaluate_quick_contact_schedule($qc);
    if (!$sched['online'] && !empty($qc['working_hours']['hide_bar_when_offline'])) {
        return '';
    }

    $showWa = !empty($qc['show_whatsapp']);
    $showPhone = !empty($qc['show_phone']);
    if (!$sched['online'] && !empty($qc['working_hours']['hide_phone_when_offline'])) {
        $showPhone = false;
    }

    $waDigits = preg_replace('/\D+/', '', (string)$qc['whatsapp_number']) ?? '';
    if ($waDigits === '') {
        $showWa = false;
    }
    $phoneHrefClean = preg_replace('/[^\d+]+/', '', (string)$qc['phone_href']) ?? '';
    if ($phoneHrefClean === '') {
        $showPhone = false;
    }

    if (!$showWa && !$showPhone) {
        return '';
    }

    $ctx = $overrideContext ?? detect_quick_contact_context();
    $waMessage = resolve_quick_contact_message($qc, $ctx);
    $waUrl = 'https://wa.me/' . $waDigits . ($waMessage !== '' ? ('?text=' . rawurlencode($waMessage)) : '');

    $posClass = ($qc['position'] === 'left') ? 'zqc-left' : 'zqc-right';
    $stateClass = $sched['online'] ? 'is-online' : 'is-offline';
    $waLabel = t((string)$qc['whatsapp_label']);
    $phoneLabel = t((string)$qc['phone_label']);
    $phoneDisplay = (string)$qc['phone_display'];
    $statusText = (string)$sched['status_label'];
    $hoursText = $sched['hours_range'] !== '' ? (' · ' . $sched['hours_range']) : '';

    $html = '<aside class="zqc-capsule ' . e($posClass . ' ' . $stateClass) . '" data-quick-contact aria-label="' . e(t('Hızlı iletişim kanalları')) . '">';

    if (!empty($sched['tracking_enabled'])) {
        $html .= '<div class="zqc-status-badge" title="' . e($statusText . $hoursText) . '">'
            . '<span class="zqc-status-dot" aria-hidden="true"></span>'
            . '<span class="zqc-status-text">' . e($statusText) . '</span>'
            . '</div>';
    }

    $html .= '<div class="zqc-pill">';
    if ($showWa) {
        $html .= '<a class="zqc-action zqc-action-wa" data-zqc-wa data-wa-number="' . e($waDigits) . '" href="' . e($waUrl) . '" target="_blank" rel="noopener noreferrer" title="' . e($waLabel . ' — ' . $waMessage) . '">'
            . '<span class="zqc-icon" aria-hidden="true">' . zeynart_social_icon('whatsapp', 18) . '</span>'
            . '<span class="zqc-copy"><strong>' . e($waLabel) . '</strong> <small>' . e(t('Hızlı Mesaj')) . '</small></span>'
            . '</a>';
    }
    if ($showWa && $showPhone) {
        $html .= '<span class="zqc-divider" aria-hidden="true"></span>';
    }
    if ($showPhone) {
        $html .= '<a class="zqc-action zqc-action-phone" href="tel:' . e($phoneHrefClean) . '" title="' . e($phoneLabel . ': ' . $phoneDisplay) . '">'
            . '<span class="zqc-icon" aria-hidden="true">' . zeynart_icon('phone', 17, '', 1.9) . '</span>'
            . '<span class="zqc-copy"><strong>' . e($phoneLabel) . '</strong> <small>' . e($phoneDisplay) . '</small></span>'
            . '</a>';
    }
    $html .= '</div></aside>';

    return $html;
}
