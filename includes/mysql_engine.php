<?php
declare(strict_types=1);

/**
 * ZEYNART DİJİTAL STÜDYO v5.0 — KURUMSAL PDO MYSQL / MARIADB VERİTABANI MOTORU
 * - 4 İndeksli Tablo Mimarisi ({prefix}posts, {prefix}store_kv, {prefix}analytics_daily, {prefix}revisions)
 * - Plesk / cPanel Tek Tıkla Bağlantı Testi, Otomatik Şema Kurulumu & 1.681+ JSON Makale Göç Sihirbazı
 * - Sıfır Veri Kaybı Doğrulamalı Eski JSON Temizleyici (data/posts/*.json kalabalığını sıfırlar)
 * - Saf PHP .SQL Yedek Üretici (mysqldump gerektirmez, phpMyAdmin / Plesk %100 uyumlu) & .SQL Geri Yükleyici
 */

function zeynart_db_config_file(): string
{
    return DATA_DIR . '/.db_config.php';
}

function zeynart_normalize_db_prefix(string $prefix): string
{
    $clean = preg_replace('/[^a-zA-Z0-9_]/', '', trim($prefix)) ?? 'zn_';
    if ($clean === '') {
        return 'zn_';
    }
    if (!str_ends_with($clean, '_')) {
        $clean .= '_';
    }
    return substr($clean, 0, 16);
}

function zeynart_default_db_config(): array
{
    return [
        'enabled' => false,
        'driver' => 'mysql', // 'mysql' (Plesk/cPanel MariaDB/MySQL) or 'sqlite' (Single-file SQL)
        'host' => 'localhost',
        'port' => 3306,
        'database' => '',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
        'prefix' => 'zn_',
        'sqlite_file' => 'zeynart_database.sqlite',
        'migrated_at' => '',
        'migrated_counts' => [],
        'json_cleaned_at' => '',
    ];
}

function zeynart_load_db_config(bool $forceReload = false): array
{
    if (!$forceReload && is_array($GLOBALS['__zeynart_db_cfg_mem'] ?? null)) {
        return $GLOBALS['__zeynart_db_cfg_mem'];
    }
    $defaults = zeynart_default_db_config();
    $cfgFile = zeynart_db_config_file();
    if (!is_file($cfgFile)) {
        $GLOBALS['__zeynart_db_cfg_mem'] = $defaults;
        return $defaults;
    }
    $loaded = @include $cfgFile;
    if (!is_array($loaded)) {
        $GLOBALS['__zeynart_db_cfg_mem'] = $defaults;
        return $defaults;
    }
    $merged = array_merge($defaults, $loaded);
    $merged['enabled'] = !empty($merged['enabled']);
    $merged['driver'] = in_array($merged['driver'] ?? 'mysql', ['mysql', 'sqlite'], true) ? (string)$merged['driver'] : 'mysql';
    $merged['host'] = trim((string)($merged['host'] ?? 'localhost')) ?: 'localhost';
    $merged['port'] = max(1, min(65535, (int)($merged['port'] ?? 3306)));
    $merged['database'] = trim((string)($merged['database'] ?? ''));
    $merged['username'] = trim((string)($merged['username'] ?? ''));
    $merged['password'] = (string)($merged['password'] ?? '');
    $merged['charset'] = 'utf8mb4';
    $merged['prefix'] = zeynart_normalize_db_prefix((string)($merged['prefix'] ?? 'zn_'));
    $merged['sqlite_file'] = preg_replace('/[^a-zA-Z0-9_\-.]/', '', (string)($merged['sqlite_file'] ?? 'zeynart_database.sqlite')) ?: 'zeynart_database.sqlite';
    $GLOBALS['__zeynart_db_cfg_mem'] = $merged;
    return $merged;
}

function zeynart_save_db_config(array $in): bool
{
    $prev = zeynart_load_db_config(true);
    $merged = array_merge($prev, $in);
    // Preserve stored password when masked/blank password is submitted during settings update
    if (array_key_exists('password', $in) && ($in['password'] === '••••••••••••' || ($in['password'] === '' && !empty($in['keep_existing_password'])))) {
        $merged['password'] = $prev['password'];
    }
    $merged['enabled'] = !empty($merged['enabled']);
    $merged['driver'] = in_array($merged['driver'] ?? 'mysql', ['mysql', 'sqlite'], true) ? (string)$merged['driver'] : 'mysql';
    $merged['host'] = trim((string)($merged['host'] ?? 'localhost')) ?: 'localhost';
    $merged['port'] = max(1, min(65535, (int)($merged['port'] ?? 3306)));
    $merged['database'] = trim((string)($merged['database'] ?? ''));
    $merged['username'] = trim((string)($merged['username'] ?? ''));
    $merged['prefix'] = zeynart_normalize_db_prefix((string)($merged['prefix'] ?? 'zn_'));
    $merged['charset'] = 'utf8mb4';

    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
    $cfgFile = zeynart_db_config_file();
    $export = var_export($merged, true);
    $phpCode = "<?php\ndeclare(strict_types=1);\nif (!defined('ROOT_DIR')) { http_response_code(403); exit; }\nreturn " . $export . ";\n";
    $tmp = $cfgFile . '.tmp.' . bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, $phpCode, LOCK_EX) !== false;
    if ($ok) {
        if (!@rename($tmp, $cfgFile)) {
            $ok = @file_put_contents($cfgFile, $phpCode, LOCK_EX) !== false;
            @unlink($tmp);
        }
    }
    if ($ok) {
        @chmod($cfgFile, 0640);
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($cfgFile, true);
        }
        @clearstatcache(true, $cfgFile);
        $GLOBALS['__zeynart_db_cfg_mem'] = $merged;
        $GLOBALS['__zeynart_pdo_conn'] = null;
        $GLOBALS['__zeynart_pdo_failed'] = false;
    }
    return $ok;
}

function zeynart_mask_db_config_for_client(?array $cfg = null): array
{
    $c = $cfg ?? zeynart_load_db_config();
    $hasPass = ($c['password'] ?? '') !== '';
    unset($c['password']);
    $c['has_password'] = $hasPass;
    $c['password_masked'] = $hasPass ? '••••••••••••' : '';
    return $c;
}

function zeynart_connect_pdo_with_config(array $cfg, bool $tryCreateDb = false): PDO
{
    if (!class_exists('PDO')) {
        throw new RuntimeException('Sunucuda PHP PDO eklentisi yüklü değil.');
    }
    $driver = (string)($cfg['driver'] ?? 'mysql');
    if ($driver === 'sqlite') {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('Sunucuda pdo_sqlite sürücüsü etkin değil.');
        }
        if (!is_dir(DATA_DIR)) {
            @mkdir(DATA_DIR, 0775, true);
        }
        $sqFile = DATA_DIR . '/' . (preg_replace('/[^a-zA-Z0-9_\-.]/', '', (string)($cfg['sqlite_file'] ?? 'zeynart_database.sqlite')) ?: 'zeynart_database.sqlite');
        $pdo = new PDO('sqlite:' . $sqFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->query('PRAGMA journal_mode = WAL');
        $pdo->query('PRAGMA synchronous = NORMAL');
        $pdo->query('PRAGMA busy_timeout = 5000');
        return $pdo;
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('Sunucuda PHP pdo_mysql sürücüsü etkin değil.');
    }
    $host = trim((string)($cfg['host'] ?? 'localhost')) ?: 'localhost';
    $port = max(1, min(65535, (int)($cfg['port'] ?? 3306)));
    $dbName = trim((string)($cfg['database'] ?? ''));
    $user = trim((string)($cfg['username'] ?? ''));
    $pass = (string)($cfg['password'] ?? '');

    if ($dbName === '' || $user === '') {
        throw new RuntimeException('MySQL Veritabanı Adı ve Kullanıcı Adı alanları zorunludur.');
    }
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $dbName)) {
        throw new RuntimeException('Veritabanı adı yalnızca harf, rakam, tire ve alt çizgi içerebilir.');
    }

    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $opts[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
    try {
        return new PDO($dsn, $user, $pass, $opts);
    } catch (PDOException $e) {
        // Unknown database (1049): attempt CREATE DATABASE IF NOT EXISTS when permitted
        if ($tryCreateDb && str_contains($e->getMessage(), '1049')) {
            $serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
            $tmpPdo = new PDO($serverDsn, $user, $pass, $opts);
            $tmpPdo->query('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $tmpPdo = null;
            return new PDO($dsn, $user, $pass, $opts);
        }
        throw new RuntimeException('MySQL bağlantı hatası: ' . $e->getMessage());
    }
}

function zeynart_get_pdo(): ?PDO
{
    if (($GLOBALS['__zeynart_pdo_conn'] ?? null) instanceof PDO) {
        return $GLOBALS['__zeynart_pdo_conn'];
    }
    if (!empty($GLOBALS['__zeynart_pdo_failed'])) {
        return null;
    }
    $cfg = zeynart_load_db_config();
    if (empty($cfg['enabled'])) {
        return null;
    }
    try {
        $pdo = zeynart_connect_pdo_with_config($cfg, false);
        $GLOBALS['__zeynart_pdo_conn'] = $pdo;
        return $pdo;
    } catch (Throwable) {
        $GLOBALS['__zeynart_pdo_failed'] = true;
        return null;
    }
}

function zeynart_is_sql_active(): bool
{
    $cfg = zeynart_load_db_config();
    if (empty($cfg['enabled'])) {
        return false;
    }
    return zeynart_get_pdo() instanceof PDO;
}

function zeynart_is_mysql_active(): bool
{
    return zeynart_is_sql_active();
}

function zeynart_sql_table(string $baseName, ?array $cfg = null): string
{
    $c = $cfg ?? zeynart_load_db_config();
    $p = zeynart_normalize_db_prefix((string)($c['prefix'] ?? 'zn_'));
    $safeBase = preg_replace('/[^a-zA-Z0-9_]/', '', $baseName) ?: 'store_kv';
    return $p . $safeBase;
}

function zeynart_sql_ensure_schema(PDO $pdo, ?array $cfg = null): void
{
    $c = $cfg ?? zeynart_load_db_config();
    $driver = (string)($c['driver'] ?? 'mysql');
    $tPosts = zeynart_sql_table('posts', $c);
    $tKv = zeynart_sql_table('store_kv', $c);
    $tAn = zeynart_sql_table('analytics_daily', $c);
    $tRev = zeynart_sql_table('revisions', $c);

    if ($driver === 'sqlite') {
        $pdo->query("CREATE TABLE IF NOT EXISTS {$tPosts} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL DEFAULT '',
            category TEXT NOT NULL DEFAULT 'Genel',
            status TEXT NOT NULL DEFAULT 'published',
            pub_date TEXT NOT NULL DEFAULT '',
            featured INTEGER NOT NULL DEFAULT 0,
            seo_score INTEGER NOT NULL DEFAULT 90,
            sort_order INTEGER NOT NULL DEFAULT 0,
            en_slug TEXT NOT NULL DEFAULT '',
            de_slug TEXT NOT NULL DEFAULT '',
            ru_slug TEXT NOT NULL DEFAULT '',
            summary_json TEXT NOT NULL,
            post_json TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $pdo->query("CREATE INDEX IF NOT EXISTS idx_{$tPosts}_status_sort ON {$tPosts}(status, sort_order, id)");
        $pdo->query("CREATE INDEX IF NOT EXISTS idx_{$tPosts}_cat ON {$tPosts}(category)");
        $pdo->query("CREATE INDEX IF NOT EXISTS idx_{$tPosts}_en ON {$tPosts}(en_slug)");
        $pdo->query("CREATE INDEX IF NOT EXISTS idx_{$tPosts}_de ON {$tPosts}(de_slug)");
        $pdo->query("CREATE INDEX IF NOT EXISTS idx_{$tPosts}_ru ON {$tPosts}(ru_slug)");

        $pdo->query("CREATE TABLE IF NOT EXISTS {$tKv} (
            store_key TEXT PRIMARY KEY,
            group_name TEXT NOT NULL DEFAULT 'core',
            payload TEXT NOT NULL,
            byte_size INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT NOT NULL
        )");
        $pdo->query("CREATE INDEX IF NOT EXISTS idx_{$tKv}_grp ON {$tKv}(group_name)");

        $pdo->query("CREATE TABLE IF NOT EXISTS {$tAn} (
            stat_key TEXT PRIMARY KEY,
            views_count INTEGER NOT NULL DEFAULT 0,
            uniques_count INTEGER NOT NULL DEFAULT 0,
            payload TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");

        $pdo->query("CREATE TABLE IF NOT EXISTS {$tRev} (
            rev_id TEXT PRIMARY KEY,
            label TEXT NOT NULL DEFAULT '',
            actor TEXT NOT NULL DEFAULT 'admin',
            ip_addr TEXT NOT NULL DEFAULT '',
            created_ts INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT '',
            size_bytes INTEGER NOT NULL DEFAULT 0,
            metrics_json TEXT NOT NULL,
            store_json TEXT NOT NULL
        )");
        $pdo->query("CREATE INDEX IF NOT EXISTS idx_{$tRev}_ts ON {$tRev}(created_ts DESC)");
        return;
    }

    $pdo->query("CREATE TABLE IF NOT EXISTS `{$tPosts}` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `slug` VARCHAR(191) NOT NULL,
        `title` VARCHAR(500) NOT NULL DEFAULT '',
        `category` VARCHAR(191) NOT NULL DEFAULT 'Genel',
        `status` VARCHAR(32) NOT NULL DEFAULT 'published',
        `pub_date` VARCHAR(32) NOT NULL DEFAULT '',
        `featured` TINYINT(1) NOT NULL DEFAULT 0,
        `seo_score` SMALLINT UNSIGNED NOT NULL DEFAULT 90,
        `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
        `en_slug` VARCHAR(191) NOT NULL DEFAULT '',
        `de_slug` VARCHAR(191) NOT NULL DEFAULT '',
        `ru_slug` VARCHAR(191) NOT NULL DEFAULT '',
        `summary_json` MEDIUMTEXT NOT NULL,
        `post_json` LONGTEXT NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_slug` (`slug`),
        KEY `idx_status_sort` (`status`, `sort_order`, `id`),
        KEY `idx_category` (`category`),
        KEY `idx_en_slug` (`en_slug`),
        KEY `idx_de_slug` (`de_slug`),
        KEY `idx_ru_slug` (`ru_slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->query("CREATE TABLE IF NOT EXISTS `{$tKv}` (
        `store_key` VARCHAR(128) NOT NULL,
        `group_name` VARCHAR(48) NOT NULL DEFAULT 'core',
        `payload` LONGTEXT NOT NULL,
        `byte_size` INT UNSIGNED NOT NULL DEFAULT 0,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`store_key`),
        KEY `idx_group` (`group_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->query("CREATE TABLE IF NOT EXISTS `{$tAn}` (
        `stat_key` VARCHAR(64) NOT NULL,
        `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
        `uniques_count` INT UNSIGNED NOT NULL DEFAULT 0,
        `payload` LONGTEXT NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`stat_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->query("CREATE TABLE IF NOT EXISTS `{$tRev}` (
        `rev_id` VARCHAR(96) NOT NULL,
        `label` VARCHAR(255) NOT NULL DEFAULT '',
        `actor` VARCHAR(96) NOT NULL DEFAULT 'admin',
        `ip_addr` VARCHAR(64) NOT NULL DEFAULT '',
        `created_ts` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `created_at` VARCHAR(40) NOT NULL DEFAULT '',
        `size_bytes` INT UNSIGNED NOT NULL DEFAULT 0,
        `metrics_json` TEXT NOT NULL,
        `store_json` LONGTEXT NOT NULL,
        PRIMARY KEY (`rev_id`),
        KEY `idx_created_ts` (`created_ts`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/* ============================================================================
 * 1. KEY-VALUE STORE ENGINE ({prefix}store_kv)
 * ============================================================================ */

function zeynart_sql_kv_get(string $storeKey, ?PDO $explicitPdo = null, ?array $cfg = null): mixed
{
    $pdo = $explicitPdo ?? zeynart_get_pdo();
    if (!$pdo) {
        return null;
    }
    if ($explicitPdo === null && isset($GLOBALS['__zeynart_sql_kv_mem']) && array_key_exists($storeKey, $GLOBALS['__zeynart_sql_kv_mem'])) {
        return $GLOBALS['__zeynart_sql_kv_mem'][$storeKey];
    }
    $tKv = zeynart_sql_table('store_kv', $cfg);
    $stmt = $pdo->prepare("SELECT payload FROM `{$tKv}` WHERE store_key = :k LIMIT 1");
    $stmt->execute([':k' => $storeKey]);
    $raw = $stmt->fetchColumn();
    if (!is_string($raw) || $raw === '') {
        if ($explicitPdo === null) {
            $GLOBALS['__zeynart_sql_kv_mem'][$storeKey] = null;
        }
        return null;
    }
    $dec = json_decode($raw, true);
    if ($explicitPdo === null) {
        $GLOBALS['__zeynart_sql_kv_mem'][$storeKey] = $dec;
    }
    return $dec;
}

function zeynart_sql_kv_set(string $storeKey, mixed $data, string $group = 'core', ?PDO $explicitPdo = null, ?array $cfg = null): bool
{
    $pdo = $explicitPdo ?? zeynart_get_pdo();
    if (!$pdo) {
        return false;
    }
    $c = $cfg ?? zeynart_load_db_config();
    $driver = (string)($c['driver'] ?? 'mysql');
    $tKv = zeynart_sql_table('store_kv', $c);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return false;
    }
    $nowDt = date('Y-m-d H:i:s');
    $len = strlen($json);

    if ($driver === 'sqlite') {
        $stmt = $pdo->prepare("INSERT INTO {$tKv} (store_key, group_name, payload, byte_size, updated_at)
            VALUES (:k, :g, :p, :sz, :dt)
            ON CONFLICT(store_key) DO UPDATE SET group_name = excluded.group_name, payload = excluded.payload, byte_size = excluded.byte_size, updated_at = excluded.updated_at");
    } else {
        $stmt = $pdo->prepare("INSERT INTO `{$tKv}` (`store_key`, `group_name`, `payload`, `byte_size`, `updated_at`)
            VALUES (:k, :g, :p, :sz, :dt)
            ON DUPLICATE KEY UPDATE `group_name` = VALUES(`group_name`), `payload` = VALUES(`payload`), `byte_size` = VALUES(`byte_size`), `updated_at` = VALUES(`updated_at`)");
    }
    $ok = $stmt->execute([
        ':k' => substr($storeKey, 0, 128),
        ':g' => substr($group, 0, 48),
        ':p' => $json,
        ':sz' => $len,
        ':dt' => $nowDt,
    ]);
    if ($ok && $explicitPdo === null) {
        $GLOBALS['__zeynart_sql_kv_mem'][$storeKey] = $data;
        if ($storeKey === 'store') {
            zeynart_sql_invalidate_runtime_caches();
        }
    }
    return $ok;
}

function zeynart_sql_kv_delete(string $storeKey, ?PDO $explicitPdo = null, ?array $cfg = null): bool
{
    $pdo = $explicitPdo ?? zeynart_get_pdo();
    if (!$pdo) {
        return false;
    }
    $tKv = zeynart_sql_table('store_kv', $cfg);
    $stmt = $pdo->prepare("DELETE FROM `{$tKv}` WHERE store_key = :k");
    $ok = $stmt->execute([':k' => $storeKey]);
    if ($explicitPdo === null) {
        unset($GLOBALS['__zeynart_sql_kv_mem'][$storeKey]);
    }
    return $ok;
}

function zeynart_sql_invalidate_runtime_caches(?PDO $explicitPdo = null, ?array $cfg = null): void
{
    $GLOBALS['__zeynart_boot_bundle_mem'] = null;
    $GLOBALS['__zeynart_media_summary_mem'] = null;
    $GLOBALS['__zeynart_unused_scan_mem'] = null;
    $pdo = $explicitPdo ?? zeynart_get_pdo();
    if ($pdo) {
        $tKv = zeynart_sql_table('store_kv', $cfg);
        $stmt = $pdo->prepare("DELETE FROM `{$tKv}` WHERE group_name = :g");
        $stmt->execute([':g' => 'runtime_cache']);
        foreach (array_keys($GLOBALS['__zeynart_sql_kv_mem'] ?? []) as $mk) {
            if (str_starts_with((string)$mk, 'cache_')) {
                unset($GLOBALS['__zeynart_sql_kv_mem'][$mk]);
            }
        }
    }
}

/* ============================================================================
 * 2. BLOG POSTS & INDEX ENGINE ({prefix}posts)
 * ============================================================================ */

function zeynart_sql_build_summary_from_post(array $post): array
{
    $slug = (string)($post['slug'] ?? '');
    $i18nSummary = [];
    foreach (['en', 'de', 'ru'] as $lCode) {
        $trData = $post['translations'][$lCode] ?? ($post['i18n'][$lCode] ?? null);
        if (is_array($trData) && !empty($trData['title'])) {
            $i18nSummary[$lCode] = [
                'slug' => (string)($trData['slug'] ?? $slug),
                'title' => (string)$trData['title'],
                'category' => (string)($trData['category'] ?? ($post['category'] ?? 'Genel')),
                'categories' => is_array($trData['categories'] ?? null) ? $trData['categories'] : (array)($post['categories'] ?? []),
                'excerpt' => (string)($trData['excerpt'] ?? ''),
                'date_display' => (string)($trData['date_display'] ?? ($post['date_display'] ?? '')),
                'read' => (string)($trData['read'] ?? ($post['read'] ?? '')),
            ];
        }
    }
    $summary = [
        'slug' => $slug,
        'title' => (string)($post['title'] ?? ''),
        'category' => (string)($post['category'] ?? 'Genel'),
        'categories' => is_array($post['categories'] ?? null) ? $post['categories'] : [(string)($post['category'] ?? 'Genel')],
        'category_ids' => is_array($post['category_ids'] ?? null) ? $post['category_ids'] : [],
        'category_slugs' => is_array($post['category_slugs'] ?? null) ? $post['category_slugs'] : [],
        'excerpt' => (string)($post['excerpt'] ?? ''),
        'date' => (string)($post['date'] ?? date('Y-m-d')),
        'date_display' => (string)($post['date_display'] ?? ''),
        'read' => (string)($post['read'] ?? '4 dk okuma'),
        'image' => (string)($post['image'] ?? '/images/journal/web-planning.webp'),
        'status' => (string)($post['status'] ?? 'published'),
        'featured' => !empty($post['featured']),
        'curated' => !empty($post['curated']),
        'seo_score' => (int)($post['seo']['seo_score'] ?? ($post['seo_score'] ?? 90)),
    ];
    if ($i18nSummary !== []) {
        $summary['i18n'] = $i18nSummary;
    }
    if (!empty($post['news_bot']) && is_array($post['news_bot'])) {
        $summary['news_bot'] = $post['news_bot'];
    }
    return $summary;
}

function zeynart_sql_load_blog_index(bool $forceReload = false): array
{
    if (!$forceReload && is_array($GLOBALS['__zeynart_sql_blog_idx_mem'] ?? null)) {
        return $GLOBALS['__zeynart_sql_blog_idx_mem'];
    }
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return [];
    }
    $tPosts = zeynart_sql_table('posts');
    $stmt = $pdo->query("SELECT summary_json FROM `{$tPosts}` ORDER BY sort_order ASC, id DESC");
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dec = json_decode((string)($row['summary_json'] ?? ''), true);
        if (is_array($dec) && !empty($dec['slug'])) {
            $out[] = $dec;
        }
    }
    $GLOBALS['__zeynart_sql_blog_idx_mem'] = $out;
    return $out;
}

function zeynart_sql_save_blog_index(array $index): bool
{
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return false;
    }
    $tPosts = zeynart_sql_table('posts');
    $nowDt = date('Y-m-d H:i:s');
    $ownTx = !$pdo->inTransaction();
    if ($ownTx) {
        $pdo->beginTransaction();
    }
    try {
        $stmt = $pdo->prepare("UPDATE `{$tPosts}`
            SET sort_order = :ord,
                title = :title,
                category = :cat,
                status = :st,
                pub_date = :dt,
                featured = :feat,
                seo_score = :seo,
                en_slug = :en,
                de_slug = :de,
                ru_slug = :ru,
                summary_json = :sumj,
                updated_at = :up
            WHERE slug = :slug");

        foreach (array_values($index) as $ord => $item) {
            if (!is_array($item) || empty($item['slug'])) {
                continue;
            }
            $slug = (string)$item['slug'];
            $stmt->execute([
                ':ord' => $ord,
                ':title' => mb_substr((string)($item['title'] ?? ''), 0, 490, 'UTF-8'),
                ':cat' => mb_substr((string)($item['category'] ?? 'Genel'), 0, 185, 'UTF-8'),
                ':st' => (string)($item['status'] ?? 'published'),
                ':dt' => substr((string)($item['date'] ?? ''), 0, 30),
                ':feat' => !empty($item['featured']) ? 1 : 0,
                ':seo' => max(0, min(100, (int)($item['seo_score'] ?? 90))),
                ':en' => substr((string)($item['i18n']['en']['slug'] ?? ''), 0, 190),
                ':de' => substr((string)($item['i18n']['de']['slug'] ?? ''), 0, 190),
                ':ru' => substr((string)($item['i18n']['ru']['slug'] ?? ''), 0, 190),
                ':sumj' => (string)json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':up' => $nowDt,
                ':slug' => substr($slug, 0, 190),
            ]);
        }
        if ($ownTx) {
            $pdo->commit();
        }
        $GLOBALS['__zeynart_sql_blog_idx_mem'] = array_values($index);
        zeynart_sql_invalidate_runtime_caches();
        return true;
    } catch (Throwable) {
        if ($ownTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

function zeynart_sql_get_post(string $slug): ?array
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
    if ($safeSlug === '') {
        return null;
    }
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return null;
    }
    $tPosts = zeynart_sql_table('posts');
    $stmt = $pdo->prepare("SELECT post_json FROM `{$tPosts}` WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => substr($safeSlug, 0, 190)]);
    $raw = $stmt->fetchColumn();
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $dec = json_decode($raw, true);
    return is_array($dec) ? $dec : null;
}

function zeynart_sql_post_exists(string $slug): bool
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
    if ($safeSlug === '') {
        return false;
    }
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return false;
    }
    $tPosts = zeynart_sql_table('posts');
    $stmt = $pdo->prepare("SELECT 1 FROM `{$tPosts}` WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => substr($safeSlug, 0, 190)]);
    return $stmt->fetchColumn() !== false;
}

function zeynart_sql_find_post_by_localized_slug(string $locSlug, string $lang): ?array
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $locSlug) ?? '';
    if ($safeSlug === '' || !in_array($lang, ['en', 'de', 'ru'], true)) {
        return null;
    }
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return null;
    }
    $col = $lang . '_slug';
    $tPosts = zeynart_sql_table('posts');
    $stmt = $pdo->prepare("SELECT post_json FROM `{$tPosts}` WHERE `{$col}` = :s LIMIT 1");
    $stmt->execute([':s' => substr($safeSlug, 0, 190)]);
    $raw = $stmt->fetchColumn();
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $dec = json_decode($raw, true);
    return is_array($dec) ? $dec : null;
}

function zeynart_sql_upsert_post_row(
    PDO $pdo,
    array $cfg,
    array $post,
    array $summaryItem,
    int $sortOrder = 0,
    ?string $oldSlug = null
): bool {
    $driver = (string)($cfg['driver'] ?? 'mysql');
    $tPosts = zeynart_sql_table('posts', $cfg);
    $slug = substr((string)($post['slug'] ?? $summaryItem['slug'] ?? ''), 0, 190);
    if ($slug === '') {
        return false;
    }

    if ($oldSlug !== null && $oldSlug !== '' && $oldSlug !== $slug) {
        $delOld = $pdo->prepare("DELETE FROM `{$tPosts}` WHERE slug = :old");
        $delOld->execute([':old' => substr($oldSlug, 0, 190)]);
    }

    if (!empty($summaryItem['featured'])) {
        $unfeat = $pdo->prepare("UPDATE `{$tPosts}` SET featured = 0 WHERE slug <> :s AND featured = 1");
        $unfeat->execute([':s' => $slug]);
    }

    $enSlug = substr((string)($summaryItem['i18n']['en']['slug'] ?? ($post['translations']['en']['slug'] ?? '')), 0, 190);
    $deSlug = substr((string)($summaryItem['i18n']['de']['slug'] ?? ($post['translations']['de']['slug'] ?? '')), 0, 190);
    $ruSlug = substr((string)($summaryItem['i18n']['ru']['slug'] ?? ($post['translations']['ru']['slug'] ?? '')), 0, 190);
    $sumJson = (string)json_encode($summaryItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $postJson = (string)json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $nowDt = date('Y-m-d H:i:s');

    if ($driver === 'sqlite') {
        $sql = "INSERT INTO {$tPosts}
            (slug, title, category, status, pub_date, featured, seo_score, sort_order, en_slug, de_slug, ru_slug, summary_json, post_json, updated_at)
            VALUES (:slug, :title, :cat, :st, :dt, :feat, :seo, :ord, :en, :de, :ru, :sumj, :postj, :up)
            ON CONFLICT(slug) DO UPDATE SET
                title = excluded.title,
                category = excluded.category,
                status = excluded.status,
                pub_date = excluded.pub_date,
                featured = excluded.featured,
                seo_score = excluded.seo_score,
                sort_order = excluded.sort_order,
                en_slug = excluded.en_slug,
                de_slug = excluded.de_slug,
                ru_slug = excluded.ru_slug,
                summary_json = excluded.summary_json,
                post_json = excluded.post_json,
                updated_at = excluded.updated_at";
    } else {
        $sql = "INSERT INTO `{$tPosts}`
            (`slug`, `title`, `category`, `status`, `pub_date`, `featured`, `seo_score`, `sort_order`, `en_slug`, `de_slug`, `ru_slug`, `summary_json`, `post_json`, `updated_at`)
            VALUES (:slug, :title, :cat, :st, :dt, :feat, :seo, :ord, :en, :de, :ru, :sumj, :postj, :up)
            ON DUPLICATE KEY UPDATE
                `title` = VALUES(`title`),
                `category` = VALUES(`category`),
                `status` = VALUES(`status`),
                `pub_date` = VALUES(`pub_date`),
                `featured` = VALUES(`featured`),
                `seo_score` = VALUES(`seo_score`),
                `sort_order` = VALUES(`sort_order`),
                `en_slug` = VALUES(`en_slug`),
                `de_slug` = VALUES(`de_slug`),
                `ru_slug` = VALUES(`ru_slug`),
                `summary_json` = VALUES(`summary_json`),
                `post_json` = VALUES(`post_json`),
                `updated_at` = VALUES(`updated_at`)";
    }

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':slug' => $slug,
        ':title' => mb_substr((string)($post['title'] ?? ''), 0, 490, 'UTF-8'),
        ':cat' => mb_substr((string)($post['category'] ?? 'Genel'), 0, 185, 'UTF-8'),
        ':st' => (string)($post['status'] ?? 'published'),
        ':dt' => substr((string)($post['date'] ?? date('Y-m-d')), 0, 30),
        ':feat' => !empty($summaryItem['featured']) ? 1 : 0,
        ':seo' => max(0, min(100, (int)($summaryItem['seo_score'] ?? 90))),
        ':ord' => $sortOrder,
        ':en' => $enSlug,
        ':de' => $deSlug,
        ':ru' => $ruSlug,
        ':sumj' => $sumJson,
        ':postj' => $postJson,
        ':up' => $nowDt,
    ]);
}

function zeynart_sql_delete_post(string $slug): bool
{
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
    if ($safeSlug === '') {
        return false;
    }
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return false;
    }
    $tPosts = zeynart_sql_table('posts');
    $stmt = $pdo->prepare("DELETE FROM `{$tPosts}` WHERE slug = :s");
    $ok = $stmt->execute([':s' => substr($safeSlug, 0, 190)]);
    $GLOBALS['__zeynart_sql_blog_idx_mem'] = null;
    zeynart_sql_invalidate_runtime_caches();
    return $ok;
}

/**
 * Streams [slug => decodedPostArray] across all posts without loading all 45MB into RAM simultaneously.
 * @return Generator<string, array>
 */
function zeynart_iterate_all_posts(): Generator
{
    if (zeynart_is_sql_active()) {
        $pdo = zeynart_get_pdo();
        if ($pdo) {
            $tPosts = zeynart_sql_table('posts');
            $stmt = $pdo->query("SELECT slug, post_json FROM `{$tPosts}` ORDER BY sort_order ASC, id DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dec = json_decode((string)($row['post_json'] ?? ''), true);
                if (is_array($dec)) {
                    yield (string)$row['slug'] => $dec;
                }
            }
            return;
        }
    }
    if (is_dir(POSTS_DIR)) {
        foreach (glob(POSTS_DIR . '/*.json') ?: [] as $pf) {
            $dec = json_decode((string)@file_get_contents($pf), true);
            if (is_array($dec)) {
                $sl = (string)($dec['slug'] ?? basename($pf, '.json'));
                yield $sl => $dec;
            }
        }
    }
}

/* ============================================================================
 * 3. ANALYTICS ({prefix}analytics_daily) & TIME MACHINE ({prefix}revisions)
 * ============================================================================ */

function zeynart_sql_analytics_get(string $statKey, array $defaultDoc = []): array
{
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return $defaultDoc;
    }
    $tAn = zeynart_sql_table('analytics_daily');
    $stmt = $pdo->prepare("SELECT payload FROM `{$tAn}` WHERE stat_key = :k LIMIT 1");
    $stmt->execute([':k' => substr($statKey, 0, 64)]);
    $raw = $stmt->fetchColumn();
    if (!is_string($raw) || $raw === '') {
        return $defaultDoc;
    }
    $dec = json_decode($raw, true);
    return is_array($dec) ? $dec : $defaultDoc;
}

function zeynart_sql_analytics_mutate(string $statKey, array $defaultDoc, callable $mutator): array
{
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return $defaultDoc;
    }
    $cfg = zeynart_load_db_config();
    $driver = (string)($cfg['driver'] ?? 'mysql');
    $tAn = zeynart_sql_table('analytics_daily', $cfg);
    $cleanKey = substr($statKey, 0, 64);

    $ownTx = !$pdo->inTransaction();
    if ($ownTx) {
        $pdo->beginTransaction();
    }
    try {
        $selSql = $driver === 'mysql'
            ? "SELECT payload FROM `{$tAn}` WHERE stat_key = :k LIMIT 1 FOR UPDATE"
            : "SELECT payload FROM {$tAn} WHERE stat_key = :k LIMIT 1";
        $sel = $pdo->prepare($selSql);
        $sel->execute([':k' => $cleanKey]);
        $raw = $sel->fetchColumn();
        $decoded = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : null;
        $doc = is_array($decoded) ? $decoded : $defaultDoc;

        $result = $mutator($doc);
        $encoded = (string)json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $views = (int)($result['totals']['views'] ?? (isset($result['sessions']) && is_array($result['sessions']) ? count($result['sessions']) : 0));
        $uniques = (int)($result['totals']['uniques'] ?? 0);
        $nowDt = date('Y-m-d H:i:s');

        if ($driver === 'sqlite') {
            $up = $pdo->prepare("INSERT INTO {$tAn} (stat_key, views_count, uniques_count, payload, updated_at)
                VALUES (:k, :vw, :uq, :p, :dt)
                ON CONFLICT(stat_key) DO UPDATE SET views_count = excluded.views_count, uniques_count = excluded.uniques_count, payload = excluded.payload, updated_at = excluded.updated_at");
        } else {
            $up = $pdo->prepare("INSERT INTO `{$tAn}` (`stat_key`, `views_count`, `uniques_count`, `payload`, `updated_at`)
                VALUES (:k, :vw, :uq, :p, :dt)
                ON DUPLICATE KEY UPDATE `views_count` = VALUES(`views_count`), `uniques_count` = VALUES(`uniques_count`), `payload` = VALUES(`payload`), `updated_at` = VALUES(`updated_at`)");
        }
        $up->execute([
            ':k' => $cleanKey,
            ':vw' => $views,
            ':uq' => $uniques,
            ':p' => $encoded,
            ':dt' => $nowDt,
        ]);

        if ($ownTx) {
            $pdo->commit();
        }
        return $result;
    } catch (Throwable) {
        if ($ownTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return $defaultDoc;
    }
}

function zeynart_sql_capture_revision(array $payload, ?PDO $explicitPdo = null, ?array $cfg = null): bool
{
    $pdo = $explicitPdo ?? zeynart_get_pdo();
    if (!$pdo) {
        return false;
    }
    $c = $cfg ?? zeynart_load_db_config();
    $driver = (string)($c['driver'] ?? 'mysql');
    $tRev = zeynart_sql_table('revisions', $c);

    $revId = substr((string)($payload['id'] ?? ('rev-' . date('Ymd-His'))), 0, 95);
    $storeJson = (string)json_encode($payload['store'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $metricsJson = (string)json_encode($payload['metrics'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $sizeBytes = strlen($storeJson);

    if ($driver === 'sqlite') {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO {$tRev}
            (rev_id, label, actor, ip_addr, created_ts, created_at, size_bytes, metrics_json, store_json)
            VALUES (:id, :lbl, :act, :ip, :ts, :cat, :sz, :mj, :sj)");
    } else {
        $stmt = $pdo->prepare("INSERT INTO `{$tRev}`
            (`rev_id`, `label`, `actor`, `ip_addr`, `created_ts`, `created_at`, `size_bytes`, `metrics_json`, `store_json`)
            VALUES (:id, :lbl, :act, :ip, :ts, :cat, :sz, :mj, :sj)
            ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `store_json` = VALUES(`store_json`), `size_bytes` = VALUES(`size_bytes`)");
    }
    $ok = $stmt->execute([
        ':id' => $revId,
        ':lbl' => mb_substr((string)($payload['label'] ?? 'Sistem Kaydı'), 0, 250, 'UTF-8'),
        ':act' => mb_substr((string)($payload['actor'] ?? 'admin'), 0, 90, 'UTF-8'),
        ':ip' => substr((string)($payload['ip'] ?? ''), 0, 60),
        ':ts' => (int)($payload['timestamp'] ?? time()),
        ':cat' => substr((string)($payload['created_at'] ?? date('c')), 0, 38),
        ':sz' => $sizeBytes,
        ':mj' => $metricsJson,
        ':sj' => $storeJson,
    ]);

    // Prune older revisions beyond newest 5
    if ($explicitPdo === null) {
        $allStmt = $pdo->query("SELECT rev_id FROM `{$tRev}` ORDER BY created_ts DESC, rev_id DESC");
        $ids = $allStmt ? $allStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        if (count($ids) > 5) {
            $delStmt = $pdo->prepare("DELETE FROM `{$tRev}` WHERE rev_id = :id");
            foreach (array_slice($ids, 5) as $oldId) {
                $delStmt->execute([':id' => (string)$oldId]);
            }
        }
    }
    return $ok;
}

function zeynart_sql_list_revisions(): array
{
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return [];
    }
    $tRev = zeynart_sql_table('revisions');
    // Auto-trim any legacy >5 rows in SQL so live databases stay strictly capped at 5
    $allIdsStmt = $pdo->query("SELECT rev_id FROM `{$tRev}` ORDER BY created_ts DESC, rev_id DESC");
    $allIds = $allIdsStmt ? $allIdsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    if (count($allIds) > 5) {
        $delOldStmt = $pdo->prepare("DELETE FROM `{$tRev}` WHERE rev_id = :id");
        foreach (array_slice($allIds, 5) as $extraId) {
            $delOldStmt->execute([':id' => (string)$extraId]);
        }
    }
    $stmt = $pdo->query("SELECT rev_id, created_at, created_ts, label, actor, ip_addr, size_bytes, metrics_json FROM `{$tRev}` ORDER BY created_ts DESC, rev_id DESC LIMIT 5");
    $items = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $metrics = json_decode((string)($r['metrics_json'] ?? '{}'), true);
        $items[] = [
            'id' => (string)$r['rev_id'],
            'created_at' => (string)$r['created_at'],
            'timestamp' => (int)$r['created_ts'],
            'label' => (string)$r['label'],
            'actor' => (string)$r['actor'],
            'ip' => (string)$r['ip_addr'],
            'size_kb' => round(((int)$r['size_bytes']) / 1024, 1),
            'metrics' => is_array($metrics) ? $metrics : [],
        ];
    }
    return $items;
}

function zeynart_sql_get_revision(string $revId): ?array
{
    $cleanId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $revId) ?? '';
    if ($cleanId === '') {
        return null;
    }
    $pdo = zeynart_get_pdo();
    if (!$pdo) {
        return null;
    }
    $tRev = zeynart_sql_table('revisions');
    $stmt = $pdo->prepare("SELECT * FROM `{$tRev}` WHERE rev_id = :id LIMIT 1");
    $stmt->execute([':id' => $cleanId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($r)) {
        return null;
    }
    $store = json_decode((string)($r['store_json'] ?? ''), true);
    if (!is_array($store)) {
        return null;
    }
    return [
        'id' => (string)$r['rev_id'],
        'created_at' => (string)$r['created_at'],
        'timestamp' => (int)$r['created_ts'],
        'label' => (string)$r['label'],
        'actor' => (string)$r['actor'],
        'ip' => (string)$r['ip_addr'],
        'metrics' => json_decode((string)($r['metrics_json'] ?? '{}'), true) ?: [],
        'store' => $store,
    ];
}

/* ============================================================================
 * 4. UNIVERSAL TRANSPARENT PATH ROUTER (DATA_DIR/*.json -> SQL TABLES)
 * ============================================================================ */

/**
 * Maps any DATA_DIR JSON file path to its SQL table/key descriptor.
 * Returns null if file is outside DATA_DIR or is default_seed.json.
 * @return ?array{type: 'kv'|'post'|'blog_index'|'analytics'|'revision', key: string, group?: string}
 */
function zeynart_sql_classify_data_path(string $filePath): ?array
{
    $normFile = str_replace('\\', '/', $filePath);
    $normData = rtrim(str_replace('\\', '/', DATA_DIR), '/');
    if (!str_starts_with(strtolower($normFile), strtolower($normData) . '/')) {
        return null;
    }
    $rel = substr($normFile, strlen($normData) + 1);
    if ($rel === '' || $rel === 'default_seed.json' || !str_ends_with(strtolower($rel), '.json')) {
        return null;
    }

    if (str_starts_with($rel, 'posts/')) {
        $slug = basename($rel, '.json');
        return $slug !== '' ? ['type' => 'post', 'key' => $slug] : null;
    }
    if ($rel === 'blog_index.json') {
        return ['type' => 'blog_index', 'key' => 'blog_index'];
    }
    if (str_starts_with($rel, 'analytics/')) {
        return ['type' => 'analytics', 'key' => basename($rel, '.json')];
    }
    if (str_starts_with($rel, 'revisions/')) {
        return ['type' => 'revision', 'key' => basename($rel, '.json')];
    }
    if (str_starts_with($rel, 'i18n_cache/')) {
        return ['type' => 'kv', 'key' => 'i18n_' . basename($rel, '.json'), 'group' => 'i18n'];
    }
    if (str_starts_with($rel, 'ratelimit/')) {
        return ['type' => 'kv', 'key' => 'rl_' . basename($rel, '.json'), 'group' => 'ratelimit'];
    }

    $base = basename($rel, '.json');
    if (str_ends_with($base, '_cache')) {
        return ['type' => 'kv', 'key' => 'cache_' . $base, 'group' => 'runtime_cache'];
    }
    $groupMap = [
        'store' => 'core',
        'blog_slug_map' => 'blog',
        'blog_comments' => 'blog',
        'news_bot_state' => 'blog',
        'news_bot_queue' => 'blog',
        'proposals' => 'crm',
        'client_trackers' => 'crm',
        'client_renewals' => 'crm',
        'bookings' => 'crm',
        '404_logs' => 'seo',
        'redirect_hits' => 'seo',
        'indexnow_logs' => 'seo',
        'security_logs' => 'security',
        'ip_locks' => 'security',
        'uptime_monitors' => 'ops',
        'uptime_state' => 'ops',
        'ota_hub_state' => 'ops',
        'ota_client_state' => 'ops',
        'ota_logs' => 'ops',
        'lscache_state' => 'cache',
        'cache_purge_state' => 'cache',
        'upload_aliases' => 'media',
        'search_analytics' => 'analytics',
    ];
    return [
        'type' => 'kv',
        'key' => $base,
        'group' => $groupMap[$base] ?? 'misc',
    ];
}

function zeynart_read_json_data(string $filePath, mixed $default = null): mixed
{
    if (zeynart_is_sql_active()) {
        $desc = zeynart_sql_classify_data_path($filePath);
        if ($desc !== null) {
            if ($desc['type'] === 'kv') {
                $val = zeynart_sql_kv_get($desc['key']);
                return $val !== null ? $val : $default;
            }
            if ($desc['type'] === 'post') {
                $p = zeynart_sql_get_post($desc['key']);
                return $p !== null ? $p : $default;
            }
            if ($desc['type'] === 'blog_index') {
                return zeynart_sql_load_blog_index();
            }
            if ($desc['type'] === 'analytics') {
                return zeynart_sql_analytics_get($desc['key'], is_array($default) ? $default : []);
            }
            if ($desc['type'] === 'revision') {
                $rev = zeynart_sql_get_revision($desc['key']);
                return $rev !== null ? $rev : $default;
            }
        }
    }

    if (!is_file($filePath)) {
        return $default;
    }
    $raw = @file_get_contents($filePath);
    if (!is_string($raw) || trim($raw) === '') {
        return $default;
    }
    $dec = json_decode($raw, true);
    return $dec !== null ? $dec : $default;
}

function zeynart_delete_json_data(string $filePath): bool
{
    $okSql = false;
    if (zeynart_is_sql_active()) {
        $desc = zeynart_sql_classify_data_path($filePath);
        if ($desc !== null) {
            if ($desc['type'] === 'kv') {
                $okSql = zeynart_sql_kv_delete($desc['key']);
            } elseif ($desc['type'] === 'post') {
                $okSql = zeynart_sql_delete_post($desc['key']);
            } elseif ($desc['type'] === 'revision') {
                $pdo = zeynart_get_pdo();
                if ($pdo) {
                    $tRev = zeynart_sql_table('revisions');
                    $st = $pdo->prepare("DELETE FROM `{$tRev}` WHERE rev_id = :id");
                    $okSql = $st->execute([':id' => $desc['key']]);
                }
            }
        }
    }
    $okDisk = true;
    if (is_file($filePath)) {
        $okDisk = @unlink($filePath);
    }
    return $okSql || $okDisk;
}

/* ============================================================================
 * 5. ONE-CLICK MIGRATION WIZARD, LEGACY JSON CLEANER & PURE-PHP .SQL ENGINE
 * ============================================================================ */

function zeynart_scan_disk_json_inventory(): array
{
    $postFiles = is_dir(POSTS_DIR) ? (glob(POSTS_DIR . '/*.json') ?: []) : [];
    $revFiles = is_dir(DATA_DIR . '/revisions') ? (glob(DATA_DIR . '/revisions/*.json') ?: []) : [];
    $anFiles = is_dir(DATA_DIR . '/analytics') ? (glob(DATA_DIR . '/analytics/*.json') ?: []) : [];
    $i18nFiles = is_dir(DATA_DIR . '/i18n_cache') ? (glob(DATA_DIR . '/i18n_cache/*.json') ?: []) : [];
    $rlFiles = is_dir(DATA_DIR . '/ratelimit') ? (glob(DATA_DIR . '/ratelimit/*.json') ?: []) : [];
    $rootJsons = glob(DATA_DIR . '/*.json') ?: [];

    $allFiles = array_merge($postFiles, $revFiles, $anFiles, $i18nFiles, $rlFiles, $rootJsons);
    $totalBytes = 0;
    foreach ($allFiles as $f) {
        $totalBytes += (int)@filesize($f);
    }

    return [
        'posts_json_count' => count($postFiles),
        'revisions_json_count' => count($revFiles),
        'analytics_json_count' => count($anFiles),
        'i18n_json_count' => count($i18nFiles),
        'root_json_count' => count($rootJsons),
        'total_json_files' => count($allFiles),
        'total_json_bytes' => $totalBytes,
        'total_json_mb' => round($totalBytes / 1048576, 2),
    ];
}

function zeynart_collect_database_status(): array
{
    $cfg = zeynart_load_db_config();
    $diskInv = zeynart_scan_disk_json_inventory();
    $sqlActive = zeynart_is_sql_active();

    $tables = [];
    $serverVersion = '';
    $totalRows = 0;
    $totalSqlMb = 0.0;
    $postsInSql = 0;
    $storeExistsInSql = false;
    $connError = '';

    if ($sqlActive) {
        $pdo = zeynart_get_pdo();
        if ($pdo) {
            try {
                $serverVersion = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            } catch (Throwable) {
                $serverVersion = ($cfg['driver'] === 'sqlite') ? 'SQLite 3 WAL' : 'MySQL / MariaDB';
            }
            $tableLabels = [
                'posts' => 'Blog Makaleleri & İndeks',
                'store_kv' => 'Site Ayarları, Sayfalar, CRM & Loglar',
                'analytics_daily' => 'Zero-Cookie Ziyaretçi İstatistikleri',
                'revisions' => '5 Sürüm Zaman Makinesi',
            ];
            foreach ($tableLabels as $base => $lbl) {
                $tName = zeynart_sql_table($base, $cfg);
                try {
                    $cntStmt = $pdo->query("SELECT COUNT(*) FROM `{$tName}`");
                    $cnt = $cntStmt ? (int)$cntStmt->fetchColumn() : 0;
                } catch (Throwable) {
                    $cnt = 0;
                }
                if ($base === 'posts') {
                    $postsInSql = $cnt;
                }
                if ($base === 'store_kv' && $cnt > 0) {
                    $chk = $pdo->prepare("SELECT 1 FROM `{$tName}` WHERE store_key = 'store' LIMIT 1");
                    $chk->execute();
                    $storeExistsInSql = $chk->fetchColumn() !== false;
                }
                $sizeKb = 0.0;
                if ($cfg['driver'] === 'mysql' && $cfg['database'] !== '') {
                    try {
                        $szStmt = $pdo->prepare("SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tb LIMIT 1");
                        $szStmt->execute([':db' => $cfg['database'], ':tb' => $tName]);
                        $sizeKb = (float)($szStmt->fetchColumn() ?: 0.0);
                    } catch (Throwable) {
                        $sizeKb = 0.0;
                    }
                }
                $totalRows += $cnt;
                $totalSqlMb += ($sizeKb / 1024);
                $tables[] = [
                    'table' => $tName,
                    'base' => $base,
                    'label' => $lbl,
                    'rows' => $cnt,
                    'size_kb' => $sizeKb,
                ];
            }
            if ($cfg['driver'] === 'sqlite') {
                $sqPath = DATA_DIR . '/' . $cfg['sqlite_file'];
                if (is_file($sqPath)) {
                    $totalSqlMb = round(((int)@filesize($sqPath)) / 1048576, 2);
                }
            }
        }
    } elseif (!empty($cfg['enabled'])) {
        try {
            zeynart_connect_pdo_with_config($cfg, false);
        } catch (Throwable $e) {
            $connError = $e->getMessage();
        }
    }

    $canCleanLegacyJson = $sqlActive
        && $storeExistsInSql
        && ($diskInv['total_json_files'] > 0)
        && ($postsInSql >= $diskInv['posts_json_count']);

    return [
        'active_engine' => $sqlActive ? ($cfg['driver'] === 'sqlite' ? 'sqlite' : 'mysql') : 'json',
        'active_engine_label' => $sqlActive
            ? ($cfg['driver'] === 'sqlite' ? 'PDO SQLite Tek Dosya Motoru' : 'PDO MySQL / MariaDB (InnoDB)')
            : 'Flat-File JSON (MySQL Geçişine Hazır)',
        'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
        'pdo_sqlite_loaded' => extension_loaded('pdo_sqlite'),
        'config' => zeynart_mask_db_config_for_client($cfg),
        'server_version' => $serverVersion,
        'connection_error' => $connError,
        'tables' => $tables,
        'total_sql_rows' => $totalRows,
        'total_sql_mb' => round($totalSqlMb, 2),
        'posts_in_sql' => $postsInSql,
        'store_in_sql' => $storeExistsInSql,
        'disk_json' => $diskInv,
        'can_clean_legacy_json' => $canCleanLegacyJson,
    ];
}

function zeynart_migrate_all_json_to_sql(array $inputCfg): array
{
    $t0 = hrtime(true);
    $prevCfg = zeynart_load_db_config(true);
    $targetCfg = array_merge($prevCfg, $inputCfg);
    if (($inputCfg['password'] ?? '') === '' || ($inputCfg['password'] ?? '') === '••••••••••••') {
        if (($prevCfg['password'] ?? '') !== '' && empty($inputCfg['clear_password'])) {
            $targetCfg['password'] = $prevCfg['password'];
        }
    }
    $targetCfg['prefix'] = zeynart_normalize_db_prefix((string)($targetCfg['prefix'] ?? 'zn_'));

    // Read current live state BEFORE switching engine so migration works from JSON or re-syncs cleanly
    $currentStore = function_exists('load_store') ? load_store(true) : [];
    if ($currentStore === [] && is_file(DATA_DIR . '/store.json')) {
        $currentStore = json_decode((string)@file_get_contents(DATA_DIR . '/store.json'), true) ?: [];
    }

    $pdo = zeynart_connect_pdo_with_config($targetCfg, true);
    zeynart_sql_ensure_schema($pdo, $targetCfg);

    $migratedPosts = 0;
    $migratedKv = 0;
    $migratedAnalytics = 0;
    $migratedRevisions = 0;

    // 1. Migrate main Store
    if ($currentStore !== []) {
        zeynart_sql_kv_set('store', $currentStore, 'core', $pdo, $targetCfg);
        $migratedKv++;
    }

    // 2. Migrate Blog Posts (preserving blog_index.json sort order + picking up any extra disk JSONs)
    $diskIdxFile = DATA_DIR . '/blog_index.json';
    $diskIdx = is_file($diskIdxFile) ? (json_decode((string)@file_get_contents($diskIdxFile), true) ?: []) : [];
    if ($diskIdx === [] && function_exists('load_blog_index')) {
        $diskIdx = load_blog_index(true);
    }

    $orderedSlugs = [];
    $summaryBySlug = [];
    foreach ($diskIdx as $idxRow) {
        if (is_array($idxRow) && !empty($idxRow['slug'])) {
            $sl = (string)$idxRow['slug'];
            $orderedSlugs[] = $sl;
            $summaryBySlug[$sl] = $idxRow;
        }
    }
    if (is_dir(POSTS_DIR)) {
        foreach (glob(POSTS_DIR . '/*.json') ?: [] as $pf) {
            $bSlug = basename($pf, '.json');
            if ($bSlug !== '' && !isset($summaryBySlug[$bSlug])) {
                $orderedSlugs[] = $bSlug;
            }
        }
    }

    $chunks = array_chunk($orderedSlugs, 250);
    $sortCounter = 0;
    foreach ($chunks as $slugChunk) {
        $pdo->beginTransaction();
        try {
            foreach ($slugChunk as $sl) {
                $postArr = null;
                $pFile = POSTS_DIR . '/' . $sl . '.json';
                if (is_file($pFile)) {
                    $postArr = json_decode((string)@file_get_contents($pFile), true);
                } elseif (zeynart_is_sql_active()) {
                    $postArr = zeynart_sql_get_post($sl);
                }
                if (!is_array($postArr)) {
                    continue;
                }
                $postArr['slug'] = (string)($postArr['slug'] ?? $sl);
                $sumItem = $summaryBySlug[$sl] ?? zeynart_sql_build_summary_from_post($postArr);
                if (zeynart_sql_upsert_post_row($pdo, $targetCfg, $postArr, $sumItem, $sortCounter)) {
                    $migratedPosts++;
                    $sortCounter++;
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Blog yazıları MySQL tablosuna aktarılırken hata oluştu: ' . $e->getMessage());
        }
    }

    // 3. Migrate Secondary JSON Collections & i18n Caches
    foreach (glob(DATA_DIR . '/*.json') ?: [] as $rootJson) {
        $bn = basename($rootJson);
        if (in_array($bn, ['store.json', 'blog_index.json', 'default_seed.json'], true) || str_ends_with($bn, '_cache.json')) {
            continue;
        }
        $dec = json_decode((string)@file_get_contents($rootJson), true);
        if ($dec !== null) {
            $desc = zeynart_sql_classify_data_path($rootJson);
            if ($desc !== null && $desc['type'] === 'kv') {
                zeynart_sql_kv_set($desc['key'], $dec, $desc['group'] ?? 'misc', $pdo, $targetCfg);
                $migratedKv++;
            }
        }
    }
    foreach (glob(DATA_DIR . '/i18n_cache/*.json') ?: [] as $i18nFile) {
        $dec = json_decode((string)@file_get_contents($i18nFile), true);
        if ($dec !== null) {
            zeynart_sql_kv_set('i18n_' . basename($i18nFile, '.json'), $dec, 'i18n', $pdo, $targetCfg);
            $migratedKv++;
        }
    }

    // 4. Migrate Analytics JSONs
    if (is_dir(DATA_DIR . '/analytics')) {
        $tAn = zeynart_sql_table('analytics_daily', $targetCfg);
        $driver = (string)($targetCfg['driver'] ?? 'mysql');
        $pdo->beginTransaction();
        foreach (glob(DATA_DIR . '/analytics/*.json') ?: [] as $af) {
            $dec = json_decode((string)@file_get_contents($af), true);
            if (!is_array($dec)) {
                continue;
            }
            $sKey = substr(basename($af, '.json'), 0, 64);
            $encoded = (string)json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $vw = (int)($dec['totals']['views'] ?? (isset($dec['sessions']) && is_array($dec['sessions']) ? count($dec['sessions']) : 0));
            $uq = (int)($dec['totals']['uniques'] ?? 0);
            if ($driver === 'sqlite') {
                $st = $pdo->prepare("INSERT OR REPLACE INTO {$tAn} (stat_key, views_count, uniques_count, payload, updated_at) VALUES (:k, :vw, :uq, :p, :dt)");
            } else {
                $st = $pdo->prepare("INSERT INTO `{$tAn}` (`stat_key`, `views_count`, `uniques_count`, `payload`, `updated_at`) VALUES (:k, :vw, :uq, :p, :dt) ON DUPLICATE KEY UPDATE `payload` = VALUES(`payload`), `views_count` = VALUES(`views_count`), `uniques_count` = VALUES(`uniques_count`)");
            }
            $st->execute([':k' => $sKey, ':vw' => $vw, ':uq' => $uq, ':p' => $encoded, ':dt' => date('Y-m-d H:i:s')]);
            $migratedAnalytics++;
        }
        $pdo->commit();
    }

    // 5. Migrate Time Machine Revisions (newest 5)
    if (is_dir(DATA_DIR . '/revisions')) {
        $revFiles = glob(DATA_DIR . '/revisions/rev-*.json') ?: [];
        usort($revFiles, static fn(string $a, string $b): int => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
        $pdo->beginTransaction();
        foreach (array_slice($revFiles, 0, 5) as $rf) {
            $dec = json_decode((string)@file_get_contents($rf), true);
            if (is_array($dec) && !empty($dec['id'])) {
                zeynart_sql_capture_revision($dec, $pdo, $targetCfg);
                $migratedRevisions++;
            }
        }
        $pdo->commit();
    }

    $elapsedMs = round((hrtime(true) - $t0) / 1e6, 1);

    // Activate SQL engine in .db_config.php
    $targetCfg['enabled'] = true;
    $targetCfg['migrated_at'] = date('Y-m-d H:i:s');
    $targetCfg['migrated_counts'] = [
        'posts' => $migratedPosts,
        'kv' => $migratedKv,
        'analytics' => $migratedAnalytics,
        'revisions' => $migratedRevisions,
        'elapsed_ms' => $elapsedMs,
    ];
    zeynart_save_db_config($targetCfg);

    // Refresh static RAM caches
    $GLOBALS['__zeynart_sql_kv_mem'] = [];
    $GLOBALS['__zeynart_sql_blog_idx_mem'] = null;
    if (function_exists('load_store')) {
        load_store(true);
    }
    if (function_exists('load_blog_index')) {
        load_blog_index(true);
    }

    return [
        'ok' => true,
        'migrated_posts' => $migratedPosts,
        'migrated_kv' => $migratedKv,
        'migrated_analytics' => $migratedAnalytics,
        'migrated_revisions' => $migratedRevisions,
        'elapsed_ms' => $elapsedMs,
        'db_status' => zeynart_collect_database_status(),
    ];
}

function zeynart_cleanup_legacy_json_files(bool $archiveFirst = true): array
{
    $status = zeynart_collect_database_status();
    if ($status['active_engine'] === 'json') {
        return ['ok' => false, 'error' => 'Eski JSON dosyalarını silmeden önce MySQL motoru aktif edilmeli ve veriler aktarılmalıdır.'];
    }
    if (empty($status['store_in_sql']) || ($status['posts_in_sql'] < $status['disk_json']['posts_json_count'])) {
        return [
            'ok' => false,
            'error' => sprintf(
                'Güvenlik kilidi: Diskte %d yazı varken veritabanında %d yazı bulunuyor. Lütfen önce "Tüm Sistemi MySQL\'e Aktar" adımını çalıştırın.',
                (int)$status['disk_json']['posts_json_count'],
                (int)$status['posts_in_sql']
            ),
        ];
    }

    $archiveFilename = '';
    if ($archiveFirst && function_exists('zeynart_backups_dir')) {
        $bakDir = zeynart_backups_dir();
        $archiveFilename = 'mysql-gecis-yedek-' . date('Y-m-d_His') . '.zip';
        $destZip = $bakDir . '/' . $archiveFilename;
        $sqlDump = zeynart_generate_mysql_dump_sql();
        $manifestJson = (string)json_encode([
            'created_at' => date('c'),
            'generator' => 'Zeynart Dijital Stüdyo v5.0 MySQL Geçiş Güvenlik Yedeği',
            'engine' => zeynart_load_db_config()['driver'] ?? 'mysql',
            'include_store' => true,
            'include_posts' => true,
            'include_uploads' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($destZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFromString('backup-manifest.json', $manifestJson);
                $zip->addFromString('database/zeynart-veritabani-yedegi.sql', $sqlDump);
                $zip->close();
            }
        } elseif (function_exists('zeynart_pure_zip_write')) {
            zeynart_pure_zip_write($destZip, [
                'backup-manifest.json' => $manifestJson,
                'database/zeynart-veritabani-yedegi.sql' => $sqlDump,
            ]);
        }
    }

    $deletedCount = 0;
    $freedBytes = 0;

    $deleteMatching = static function (array $files) use (&$deletedCount, &$freedBytes): void {
        foreach ($files as $fp) {
            if (is_file($fp)) {
                $sz = (int)@filesize($fp);
                if (@unlink($fp)) {
                    $deletedCount++;
                    $freedBytes += $sz;
                }
            }
        }
    };

    if (is_dir(POSTS_DIR)) {
        $deleteMatching(glob(POSTS_DIR . '/*.json') ?: []);
        @rmdir(POSTS_DIR);
    }
    if (is_dir(DATA_DIR . '/revisions')) {
        $deleteMatching(glob(DATA_DIR . '/revisions/*.json') ?: []);
        @rmdir(DATA_DIR . '/revisions');
    }
    if (is_dir(DATA_DIR . '/analytics')) {
        $deleteMatching(glob(DATA_DIR . '/analytics/*.json') ?: []);
        @rmdir(DATA_DIR . '/analytics');
    }
    if (is_dir(DATA_DIR . '/i18n_cache')) {
        $deleteMatching(glob(DATA_DIR . '/i18n_cache/*.json') ?: []);
        @rmdir(DATA_DIR . '/i18n_cache');
    }
    if (is_dir(DATA_DIR . '/ratelimit')) {
        $deleteMatching(glob(DATA_DIR . '/ratelimit/*.json') ?: []);
        @rmdir(DATA_DIR . '/ratelimit');
    }
    $deleteMatching(glob(DATA_DIR . '/*.json') ?: []);
    // Also remove obsolete studio_index.sqlite if MySQL is active
    $cfg = zeynart_load_db_config();
    foreach (['studio_index.sqlite', 'studio_index.sqlite-wal', 'studio_index.sqlite-shm'] as $obsSq) {
        $obsPath = DATA_DIR . '/' . $obsSq;
        if (is_file($obsPath)) {
            $freedBytes += (int)@filesize($obsPath);
            if (@unlink($obsPath)) {
                $deletedCount++;
            }
        }
    }

    $cfg['json_cleaned_at'] = date('Y-m-d H:i:s');
    zeynart_save_db_config($cfg);
    if (function_exists('zeynart_v4_invalidate_unused_scan_cache')) {
        zeynart_v4_invalidate_unused_scan_cache();
    }

    return [
        'ok' => true,
        'deleted_count' => $deletedCount,
        'freed_mb' => round($freedBytes / 1048576, 2),
        'archive_filename' => $archiveFilename,
        'db_status' => zeynart_collect_database_status(),
    ];
}

function zeynart_sql_escape_literal(string $val): string
{
    $escaped = strtr($val, [
        '\\' => '\\\\',
        "\0" => '\\0',
        "\n" => '\\n',
        "\r" => '\\r',
        "'"  => "\\'",
        '"'  => '\\"',
        "\x1a" => '\\Z',
    ]);
    return "'" . $escaped . "'";
}

/**
 * Generates a complete, phpMyAdmin / Plesk compatible UTF8MB4 MySQL .sql dump
 * (works whether currently running on MySQL/SQLite OR compiling directly from JSON!).
 */
function zeynart_generate_mysql_dump_sql(?string $destFilePath = null): string
{
    $cfg = zeynart_load_db_config();
    $tPosts = zeynart_sql_table('posts', $cfg);
    $tKv = zeynart_sql_table('store_kv', $cfg);
    $tAn = zeynart_sql_table('analytics_daily', $cfg);
    $tRev = zeynart_sql_table('revisions', $cfg);

    $fh = $destFilePath !== null ? @fopen($destFilePath, 'wb') : null;
    $buf = '';
    $emit = static function (string $chunk) use ($fh, &$buf): void {
        if (is_resource($fh)) {
            fwrite($fh, $chunk);
        } else {
            $buf .= $chunk;
        }
    };

    $emit("-- ============================================================================\n");
    $emit("-- ZEYNART DİJİTAL STÜDYO v5.0 — TAM MYSQL / MARIADB VERİTABANI YEDEĞİ (.SQL)\n");
    $emit("-- Üretim Tarihi: " . date('Y-m-d H:i:s') . " | Karakter Seti: utf8mb4_unicode_ci\n");
    $emit("-- Plesk / cPanel / phpMyAdmin üzerinden doğrudan İçe Aktar (Import) yapılabilir.\n");
    $emit("-- ============================================================================\n\n");
    $emit("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

    // 1. Schema DDL
    $emit("DROP TABLE IF EXISTS `{$tPosts}`;\n");
    $emit("CREATE TABLE `{$tPosts}` (\n"
        . "  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n"
        . "  `slug` VARCHAR(191) NOT NULL,\n"
        . "  `title` VARCHAR(500) NOT NULL DEFAULT '',\n"
        . "  `category` VARCHAR(191) NOT NULL DEFAULT 'Genel',\n"
        . "  `status` VARCHAR(32) NOT NULL DEFAULT 'published',\n"
        . "  `pub_date` VARCHAR(32) NOT NULL DEFAULT '',\n"
        . "  `featured` TINYINT(1) NOT NULL DEFAULT 0,\n"
        . "  `seo_score` SMALLINT UNSIGNED NOT NULL DEFAULT 90,\n"
        . "  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,\n"
        . "  `en_slug` VARCHAR(191) NOT NULL DEFAULT '',\n"
        . "  `de_slug` VARCHAR(191) NOT NULL DEFAULT '',\n"
        . "  `ru_slug` VARCHAR(191) NOT NULL DEFAULT '',\n"
        . "  `summary_json` MEDIUMTEXT NOT NULL,\n"
        . "  `post_json` LONGTEXT NOT NULL,\n"
        . "  `updated_at` DATETIME NOT NULL,\n"
        . "  PRIMARY KEY (`id`),\n"
        . "  UNIQUE KEY `uq_slug` (`slug`),\n"
        . "  KEY `idx_status_sort` (`status`, `sort_order`, `id`),\n"
        . "  KEY `idx_category` (`category`),\n"
        . "  KEY `idx_en_slug` (`en_slug`),\n"
        . "  KEY `idx_de_slug` (`de_slug`),\n"
        . "  KEY `idx_ru_slug` (`ru_slug`)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n");

    $emit("DROP TABLE IF EXISTS `{$tKv}`;\n");
    $emit("CREATE TABLE `{$tKv}` (\n"
        . "  `store_key` VARCHAR(128) NOT NULL,\n"
        . "  `group_name` VARCHAR(48) NOT NULL DEFAULT 'core',\n"
        . "  `payload` LONGTEXT NOT NULL,\n"
        . "  `byte_size` INT UNSIGNED NOT NULL DEFAULT 0,\n"
        . "  `updated_at` DATETIME NOT NULL,\n"
        . "  PRIMARY KEY (`store_key`),\n"
        . "  KEY `idx_group` (`group_name`)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n");

    $emit("DROP TABLE IF EXISTS `{$tAn}`;\n");
    $emit("CREATE TABLE `{$tAn}` (\n"
        . "  `stat_key` VARCHAR(64) NOT NULL,\n"
        . "  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,\n"
        . "  `uniques_count` INT UNSIGNED NOT NULL DEFAULT 0,\n"
        . "  `payload` LONGTEXT NOT NULL,\n"
        . "  `updated_at` DATETIME NOT NULL,\n"
        . "  PRIMARY KEY (`stat_key`)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n");

    $emit("DROP TABLE IF EXISTS `{$tRev}`;\n");
    $emit("CREATE TABLE `{$tRev}` (\n"
        . "  `rev_id` VARCHAR(96) NOT NULL,\n"
        . "  `label` VARCHAR(255) NOT NULL DEFAULT '',\n"
        . "  `actor` VARCHAR(96) NOT NULL DEFAULT 'admin',\n"
        . "  `ip_addr` VARCHAR(64) NOT NULL DEFAULT '',\n"
        . "  `created_ts` BIGINT UNSIGNED NOT NULL DEFAULT 0,\n"
        . "  `created_at` VARCHAR(40) NOT NULL DEFAULT '',\n"
        . "  `size_bytes` INT UNSIGNED NOT NULL DEFAULT 0,\n"
        . "  `metrics_json` TEXT NOT NULL,\n"
        . "  `store_json` LONGTEXT NOT NULL,\n"
        . "  PRIMARY KEY (`rev_id`),\n"
        . "  KEY `idx_created_ts` (`created_ts`)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n");

    $nowDt = date('Y-m-d H:i:s');

    if (zeynart_is_sql_active() && ($pdo = zeynart_get_pdo()) !== null) {
        // Export directly from active SQL tables
        $stKv = $pdo->query("SELECT store_key, group_name, payload, byte_size, updated_at FROM `{$tKv}` WHERE group_name <> 'runtime_cache'");
        while ($r = $stKv->fetch(PDO::FETCH_ASSOC)) {
            $emit(sprintf(
                "INSERT INTO `{$tKv}` (`store_key`, `group_name`, `payload`, `byte_size`, `updated_at`) VALUES (%s, %s, %s, %d, %s);\n",
                zeynart_sql_escape_literal((string)$r['store_key']),
                zeynart_sql_escape_literal((string)$r['group_name']),
                zeynart_sql_escape_literal((string)$r['payload']),
                (int)$r['byte_size'],
                zeynart_sql_escape_literal((string)$r['updated_at'])
            ));
        }

        $stPosts = $pdo->query("SELECT slug, title, category, status, pub_date, featured, seo_score, sort_order, en_slug, de_slug, ru_slug, summary_json, post_json, updated_at FROM `{$tPosts}` ORDER BY sort_order ASC, id DESC");
        while ($r = $stPosts->fetch(PDO::FETCH_ASSOC)) {
            $emit(sprintf(
                "INSERT INTO `{$tPosts}` (`slug`, `title`, `category`, `status`, `pub_date`, `featured`, `seo_score`, `sort_order`, `en_slug`, `de_slug`, `ru_slug`, `summary_json`, `post_json`, `updated_at`) VALUES (%s, %s, %s, %s, %s, %d, %d, %d, %s, %s, %s, %s, %s, %s);\n",
                zeynart_sql_escape_literal((string)$r['slug']),
                zeynart_sql_escape_literal((string)$r['title']),
                zeynart_sql_escape_literal((string)$r['category']),
                zeynart_sql_escape_literal((string)$r['status']),
                zeynart_sql_escape_literal((string)$r['pub_date']),
                (int)$r['featured'],
                (int)$r['seo_score'],
                (int)$r['sort_order'],
                zeynart_sql_escape_literal((string)$r['en_slug']),
                zeynart_sql_escape_literal((string)$r['de_slug']),
                zeynart_sql_escape_literal((string)$r['ru_slug']),
                zeynart_sql_escape_literal((string)$r['summary_json']),
                zeynart_sql_escape_literal((string)$r['post_json']),
                zeynart_sql_escape_literal((string)$r['updated_at'])
            ));
        }

        $stAn = $pdo->query("SELECT stat_key, views_count, uniques_count, payload, updated_at FROM `{$tAn}`");
        while ($r = $stAn->fetch(PDO::FETCH_ASSOC)) {
            $emit(sprintf(
                "INSERT INTO `{$tAn}` (`stat_key`, `views_count`, `uniques_count`, `payload`, `updated_at`) VALUES (%s, %d, %d, %s, %s);\n",
                zeynart_sql_escape_literal((string)$r['stat_key']),
                (int)$r['views_count'],
                (int)$r['uniques_count'],
                zeynart_sql_escape_literal((string)$r['payload']),
                zeynart_sql_escape_literal((string)$r['updated_at'])
            ));
        }

        $stRev = $pdo->query("SELECT rev_id, label, actor, ip_addr, created_ts, created_at, size_bytes, metrics_json, store_json FROM `{$tRev}` ORDER BY created_ts DESC LIMIT 15");
        while ($r = $stRev->fetch(PDO::FETCH_ASSOC)) {
            $emit(sprintf(
                "INSERT INTO `{$tRev}` (`rev_id`, `label`, `actor`, `ip_addr`, `created_ts`, `created_at`, `size_bytes`, `metrics_json`, `store_json`) VALUES (%s, %s, %s, %s, %d, %s, %d, %s, %s);\n",
                zeynart_sql_escape_literal((string)$r['rev_id']),
                zeynart_sql_escape_literal((string)$r['label']),
                zeynart_sql_escape_literal((string)$r['actor']),
                zeynart_sql_escape_literal((string)$r['ip_addr']),
                (int)$r['created_ts'],
                zeynart_sql_escape_literal((string)$r['created_at']),
                (int)$r['size_bytes'],
                zeynart_sql_escape_literal((string)$r['metrics_json']),
                zeynart_sql_escape_literal((string)$r['store_json'])
            ));
        }
    } else {
        // Compile SQL directly from existing JSON files on disk!
        $store = function_exists('load_store') ? load_store(true) : [];
        if ($store !== []) {
            $stRaw = (string)json_encode($store, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $emit(sprintf(
                "INSERT INTO `{$tKv}` (`store_key`, `group_name`, `payload`, `byte_size`, `updated_at`) VALUES ('store', 'core', %s, %d, %s);\n",
                zeynart_sql_escape_literal($stRaw),
                strlen($stRaw),
                zeynart_sql_escape_literal($nowDt)
            ));
        }
        foreach (glob(DATA_DIR . '/*.json') ?: [] as $rj) {
            $bn = basename($rj);
            if (in_array($bn, ['store.json', 'blog_index.json', 'default_seed.json'], true) || str_ends_with($bn, '_cache.json')) {
                continue;
            }
            $raw = (string)@file_get_contents($rj);
            if ($raw !== '' && json_decode($raw, true) !== null) {
                $desc = zeynart_sql_classify_data_path($rj);
                if ($desc !== null && $desc['type'] === 'kv') {
                    $emit(sprintf(
                        "INSERT INTO `{$tKv}` (`store_key`, `group_name`, `payload`, `byte_size`, `updated_at`) VALUES (%s, %s, %s, %d, %s);\n",
                        zeynart_sql_escape_literal($desc['key']),
                        zeynart_sql_escape_literal($desc['group'] ?? 'misc'),
                        zeynart_sql_escape_literal($raw),
                        strlen($raw),
                        zeynart_sql_escape_literal($nowDt)
                    ));
                }
            }
        }
        $idx = function_exists('load_blog_index') ? load_blog_index(true) : [];
        $summaryMap = [];
        foreach ($idx as $r) {
            if (!empty($r['slug'])) {
                $summaryMap[(string)$r['slug']] = $r;
            }
        }
        $ord = 0;
        foreach (zeynart_iterate_all_posts() as $sl => $postArr) {
            $sumItem = $summaryMap[$sl] ?? zeynart_sql_build_summary_from_post($postArr);
            $sumRaw = (string)json_encode($sumItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $postRaw = (string)json_encode($postArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $emit(sprintf(
                "INSERT INTO `{$tPosts}` (`slug`, `title`, `category`, `status`, `pub_date`, `featured`, `seo_score`, `sort_order`, `en_slug`, `de_slug`, `ru_slug`, `summary_json`, `post_json`, `updated_at`) VALUES (%s, %s, %s, %s, %s, %d, %d, %d, %s, %s, %s, %s, %s, %s);\n",
                zeynart_sql_escape_literal(substr($sl, 0, 190)),
                zeynart_sql_escape_literal(mb_substr((string)($postArr['title'] ?? ''), 0, 490, 'UTF-8')),
                zeynart_sql_escape_literal(mb_substr((string)($postArr['category'] ?? 'Genel'), 0, 185, 'UTF-8')),
                zeynart_sql_escape_literal((string)($postArr['status'] ?? 'published')),
                zeynart_sql_escape_literal(substr((string)($postArr['date'] ?? date('Y-m-d')), 0, 30)),
                !empty($sumItem['featured']) ? 1 : 0,
                max(0, min(100, (int)($sumItem['seo_score'] ?? 90))),
                $ord++,
                zeynart_sql_escape_literal(substr((string)($sumItem['i18n']['en']['slug'] ?? ''), 0, 190)),
                zeynart_sql_escape_literal(substr((string)($sumItem['i18n']['de']['slug'] ?? ''), 0, 190)),
                zeynart_sql_escape_literal(substr((string)($sumItem['i18n']['ru']['slug'] ?? ''), 0, 190)),
                zeynart_sql_escape_literal($sumRaw),
                zeynart_sql_escape_literal($postRaw),
                zeynart_sql_escape_literal($nowDt)
            ));
        }
    }

    $emit("\nSET FOREIGN_KEY_CHECKS = 1;\n");
    if (is_resource($fh)) {
        fclose($fh);
        return $destFilePath ?? '';
    }
    return $buf;
}

/**
 * Exports active SQL database back to flat JSON files if an administrator ever wants to switch back.
 */
function zeynart_export_sql_back_to_json(): array
{
    if (!zeynart_is_sql_active() || ($pdo = zeynart_get_pdo()) === null) {
        return ['ok' => false, 'error' => 'Aktif bir SQL veritabanı bağlantısı bulunamadı.'];
    }
    $cfg = zeynart_load_db_config();
    $store = zeynart_sql_kv_get('store');
    if (!is_array($store) || $store === []) {
        return ['ok' => false, 'error' => 'Veritabanında ana store kaydı bulunamadı.'];
    }

    // Temporarily disable SQL routing while writing flat JSON files
    $GLOBALS['__zeynart_db_cfg_mem']['enabled'] = false;
    $GLOBALS['__zeynart_pdo_conn'] = null;

    if (!is_dir(POSTS_DIR)) {
        @mkdir(POSTS_DIR, 0775, true);
    }
    atomic_write_json(DATA_DIR . '/store.json', $store, true);

    $tPosts = zeynart_sql_table('posts', $cfg);
    $stPosts = $pdo->query("SELECT slug, summary_json, post_json FROM `{$tPosts}` ORDER BY sort_order ASC, id DESC");
    $blogIdx = [];
    $exportedPosts = 0;
    while ($r = $stPosts->fetch(PDO::FETCH_ASSOC)) {
        $sl = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$r['slug']) ?: '';
        if ($sl === '') {
            continue;
        }
        $sum = json_decode((string)$r['summary_json'], true);
        if (is_array($sum)) {
            $blogIdx[] = $sum;
        }
        @file_put_contents(POSTS_DIR . '/' . $sl . '.json', (string)$r['post_json'], LOCK_EX);
        $exportedPosts++;
    }
    atomic_write_json(DATA_DIR . '/blog_index.json', $blogIdx, false);

    $tKv = zeynart_sql_table('store_kv', $cfg);
    $stKv = $pdo->query("SELECT store_key, group_name, payload FROM `{$tKv}` WHERE store_key <> 'store' AND group_name <> 'runtime_cache'");
    while ($r = $stKv->fetch(PDO::FETCH_ASSOC)) {
        $k = (string)$r['store_key'];
        $g = (string)$r['group_name'];
        if ($g === 'i18n' && str_starts_with($k, 'i18n_')) {
            if (!is_dir(DATA_DIR . '/i18n_cache')) @mkdir(DATA_DIR . '/i18n_cache', 0775, true);
            @file_put_contents(DATA_DIR . '/i18n_cache/' . substr($k, 5) . '.json', (string)$r['payload'], LOCK_EX);
        } elseif ($g !== 'ratelimit') {
            @file_put_contents(DATA_DIR . '/' . $k . '.json', (string)$r['payload'], LOCK_EX);
        }
    }

    $cfg['enabled'] = false;
    zeynart_save_db_config($cfg);
    if (function_exists('load_store')) load_store(true);
    if (function_exists('load_blog_index')) load_blog_index(true);

    return [
        'ok' => true,
        'exported_posts' => $exportedPosts,
        'db_status' => zeynart_collect_database_status(),
    ];
}

/**
 * Imports a .sql dump produced by zeynart_generate_mysql_dump_sql() or phpMyAdmin into the active SQL engine.
 */
function zeynart_import_mysql_dump_sql(string $sqlContent): array
{
    if (!zeynart_is_sql_active() || ($pdo = zeynart_get_pdo()) === null) {
        return ['ok' => false, 'error' => 'İçe aktarım için önce MySQL / SQL bağlantısını kurup aktifleştirin.'];
    }
    $cfg = zeynart_load_db_config();
    $driver = (string)($cfg['driver'] ?? 'mysql');
    if ($driver === 'sqlite') {
        zeynart_sql_ensure_schema($pdo, $cfg);
    }

    $len = strlen($sqlContent);
    $statements = [];
    $cur = '';
    $inSingle = false;
    $inDouble = false;
    $inLineComment = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sqlContent[$i];
        $next = ($i + 1 < $len) ? $sqlContent[$i + 1] : '';

        if ($inLineComment) {
            if ($ch === "\n") {
                $inLineComment = false;
            }
            continue;
        }
        if (!$inSingle && !$inDouble && $ch === '-' && $next === '-') {
            $inLineComment = true;
            $i++;
            continue;
        }
        if ($ch === '\\' && ($inSingle || $inDouble)) {
            $cur .= $ch . $next;
            $i++;
            continue;
        }
        if ($ch === "'" && !$inDouble) {
            $inSingle = !$inSingle;
            $cur .= $ch;
            continue;
        }
        if ($ch === '"' && !$inSingle) {
            $inDouble = !$inDouble;
            $cur .= $ch;
            continue;
        }
        if ($ch === ';' && !$inSingle && !$inDouble) {
            $trimmed = trim($cur);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $cur = '';
            continue;
        }
        $cur .= $ch;
    }
    if (trim($cur) !== '') {
        $statements[] = trim($cur);
    }

    $executed = 0;
    foreach ($statements as $stmtSql) {
        $upper = strtoupper(substr(ltrim($stmtSql), 0, 18));
        if ($driver === 'sqlite') {
            if (str_starts_with($upper, 'SET ') || str_starts_with($upper, 'DROP TABLE') || str_starts_with($upper, 'CREATE TABLE')) {
                continue;
            }
            if (str_starts_with($upper, 'INSERT INTO')) {
                $stmtSql = preg_replace('/^INSERT\s+INTO/i', 'INSERT OR REPLACE INTO', $stmtSql) ?? $stmtSql;
                // Convert MySQL backslash escapes inside single-quoted literals to SQLite standard
                $stmtSql = preg_replace_callback("/'((?:\\\\.|[^'\\\\])*)'/s", static function (array $m): string {
                    $unesc = strtr($m[1], [
                        '\\\\' => '\\',
                        "\\'" => "'",
                        '\\"' => '"',
                        '\\n' => "\n",
                        '\\r' => "\r",
                        '\\0' => "\0",
                        '\\Z' => "\x1a",
                    ]);
                    return "'" . str_replace("'", "''", $unesc) . "'";
                }, $stmtSql) ?? $stmtSql;
            }
        }
        $pdo->query($stmtSql);
        $executed++;
    }

    $GLOBALS['__zeynart_sql_kv_mem'] = [];
    $GLOBALS['__zeynart_sql_blog_idx_mem'] = null;
    zeynart_sql_invalidate_runtime_caches($pdo, $cfg);

    return [
        'ok' => true,
        'executed_statements' => $executed,
        'db_status' => zeynart_collect_database_status(),
    ];
}
