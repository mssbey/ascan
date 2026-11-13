<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/analytics.php';

// Cookie-free, session-free lightweight live visitor heartbeat endpoint
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(204);
    exit;
}

$raw = (string)file_get_contents('php://input');
$payload = $raw !== '' ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    $payload = $_POST;
}

$path = trim((string)($payload['path'] ?? '/'));
$title = trim((string)($payload['title'] ?? ''));
$lang = trim((string)($payload['lang'] ?? 'tr'));

try {
    zeynart_record_live_heartbeat($path !== '' ? $path : '/', $title, $lang !== '' ? $lang : 'tr');
} catch (\Throwable $_) {
    // Silent resilience
}

http_response_code(204);
if (function_exists('litespeed_finish_request')) {
    @litespeed_finish_request();
} elseif (function_exists('fastcgi_finish_request')) {
    @fastcgi_finish_request();
}

// Lightweight zero-cookie Google News Autopilot background check (checks at most once every 15 minutes)
if (
    empty($_SERVER['HTTP_X_DISABLE_AUTOPILOT'])
    && !is_file(ROOT_DIR . '/__diag_verify_tmp.php')
    && !is_file(ROOT_DIR . '/__shot_blog_share_tmp.php')
) {
    $nbStateFile = DATA_DIR . '/news_bot_state.json';
    $nbShouldCheck = true;
    $nbQuick = zeynart_read_json_data($nbStateFile);
    if (is_array($nbQuick)) {
        $lastChk = (int)($nbQuick['last_check_ts'] ?? 0);
        if ((time() - $lastChk) < 900) {
            $nbShouldCheck = false;
        }
    }
    if ($nbShouldCheck) {
        @ignore_user_abort(true);
        @set_time_limit(60);
        try {
            require_once dirname(__DIR__) . '/security.php';
            require_once dirname(__DIR__) . '/db.php';
            require_once dirname(__DIR__) . '/seo.php';
            require_once dirname(__DIR__) . '/translator.php';
            require_once dirname(__DIR__) . '/studio_suite.php';
            require_once dirname(__DIR__) . '/studio_pro_pack.php';
            require_once dirname(__DIR__) . '/news_autopilot.php';
            $nbStore = load_store();
            $nbCfg = zeynart_get_news_autopilot_config($nbStore);
            if (!empty($nbCfg['enabled'])) {
                zeynart_run_news_autopilot($nbStore, ['force' => false]);
            } else {
                $st = zeynart_load_news_bot_state();
                $st['last_check_ts'] = time();
                zeynart_save_news_bot_state($st);
            }
        } catch (\Throwable $_) {
            // Silent resilience — never disrupt visitor pulse
        }
    }

    // Lightweight zero-cookie Agency Uptime & SSL Tower background check (up to 6 due monitors every 10 minutes)
    $utStateFile = DATA_DIR . '/uptime_pulse_state.json';
    $utShouldCheck = true;
    $utQuick = zeynart_read_json_data($utStateFile);
    if (is_array($utQuick) && (time() - (int)($utQuick['last_ts'] ?? 0)) < 600) {
        $utShouldCheck = false;
    }
    if ($utShouldCheck && zeynart_read_json_data(DATA_DIR . '/uptime_monitors.json') !== null) {
        require_once dirname(__DIR__) . '/db.php';
        atomic_write_json($utStateFile, ['last_ts' => time()], false);
        try {
            require_once dirname(__DIR__) . '/security.php';
            require_once dirname(__DIR__) . '/studio_suite.php';
            require_once dirname(__DIR__) . '/studio_pro_pack.php';
            require_once dirname(__DIR__) . '/agency_powerhouse.php';
            $utStore = load_store();
            $utCfg = zeynart_get_uptime_tower_config($utStore);
            if (!empty($utCfg['enabled'])) {
                $utMons = zeynart_load_uptime_monitors($utStore);
                zeynart_probe_uptime_batch($utMons, [], $utStore, 6, false);
            }
        } catch (\Throwable $_) {
            // Silent resilience
        }
    }

    // Lightweight Scheduled Vault Backup check (checks at most once per hour after response is flushed)
    $vbStateFile = DATA_DIR . '/backup_pulse_state.json';
    $vbShouldCheck = true;
    $vbQuick = zeynart_read_json_data($vbStateFile);
    if (is_array($vbQuick) && (time() - (int)($vbQuick['last_ts'] ?? 0)) < 3600) {
        $vbShouldCheck = false;
    }
    if ($vbShouldCheck) {
        require_once dirname(__DIR__) . '/db.php';
        atomic_write_json($vbStateFile, ['last_ts' => time()], false);
        try {
            require_once dirname(__DIR__) . '/security.php';
            require_once dirname(__DIR__) . '/db.php';
            require_once dirname(__DIR__) . '/enterprise_cms_suite.php';
            $vbStore = load_store(true);
            zeynart_maybe_run_scheduled_vault_backup($vbStore);
        } catch (\Throwable $_) {
            // Silent resilience
        }
    }
}
exit;
