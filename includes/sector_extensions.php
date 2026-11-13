<?php
declare(strict_types=1);

/**
 * ZEYNART DİJİTAL STÜDYO v5.2 — ÇOKLU SEKTÖR EKLENTİ KANCASI (SECTOR EXTENSION REGISTRY)
 * --------------------------------------------------------------------------------------
 * Emlak (Portföy/Değerleme), Mücevher (Koleksiyon/Sipariş), Havuz/Kulüp (Üyelik/Program),
 * Klinik/Sağlık veya B2B Sanayi sitelerinin sektöre özel PHP ve JS modüllerini çekirdekten
 * (admin/index.php, admin/api.php, admin/admin.js) izole olarak bağlar.
 *
 * Böylece Merkezi OTA güncellemesi çekirdeği yenilediğinde:
 *  - includes/extensions/*.php
 *  - <ADMIN_DIR>/extensions/*.js & *.css (veya mevcut *-studio.js dosyaları)
 *  - includes/endpoints/ext-* veya /api/ext/* uç noktaları
 * %100 korunur ve sol menüye, API'ye, Komut Merkezine otomatik bağlanmaya devam eder.
 */

require_once __DIR__ . '/config.php';

function zeynart_sector_default_nav_svg(): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>';
}

function zeynart_sector_default_sub_svg(): string
{
    return '<svg class="zs-nav-sub-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>';
}

function zeynart_sector_normalize_svg(string $rawSvg, bool $isSubItem = false): string
{
    $trim = trim($rawSvg);
    if ($trim === '' || stripos($trim, '<svg') === false || stripos($trim, '</svg>') === false) {
        return $isSubItem ? zeynart_sector_default_sub_svg() : zeynart_sector_default_nav_svg();
    }
    // Strip any dangerous inline event handlers or script tags inside custom extension SVGs
    $safe = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $trim) ?? '';
    $safe = preg_replace('/\son[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $safe) ?? '';
    if ($isSubItem && !str_contains($safe, 'zs-nav-sub-ico')) {
        if (preg_match('/<svg\b[^>]*\bclass\s*=\s*(["\'])([^"\']*)\1/i', $safe)) {
            $safe = preg_replace('/(<svg\b[^>]*\bclass\s*=\s*["\'])([^"\']*)/i', '$1zs-nav-sub-ico $2', $safe, 1) ?? $safe;
        } else {
            $safe = preg_replace('/<svg\b/i', '<svg class="zs-nav-sub-ico"', $safe, 1) ?? $safe;
        }
    }
    return $safe;
}

/**
 * Registers a sector-specific module extension into the Studio v5.2 runtime.
 * Supports both:
 *   zeynart_register_sector_extension(['id' => 'emlak', 'title' => ...])
 * and:
 *   zeynart_register_sector_extension('emlak', ['title' => ...])
 */
function zeynart_register_sector_extension(array|string $idOrSpec, array $maybeSpec = []): void
{
    if (!isset($GLOBALS['__zeynart_sector_extensions']) || !is_array($GLOBALS['__zeynart_sector_extensions'])) {
        $GLOBALS['__zeynart_sector_extensions'] = [];
    }

    if (is_string($idOrSpec)) {
        $spec = $maybeSpec;
        $spec['id'] = $idOrSpec;
    } else {
        $spec = $idOrSpec;
    }

    $rawId = strtolower(trim((string)($spec['id'] ?? '')));
    $id = preg_replace('/[^a-z0-9_\-]+/', '_', $rawId) ?? '';
    $id = trim($id, '_-');
    if ($id === '') {
        return;
    }

    $navItems = [];
    foreach ((array)($spec['nav_items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $view = preg_replace('/[^a-zA-Z0-9_\-]+/', '', trim((string)($item['view'] ?? ''))) ?? '';
        $label = trim((string)($item['label'] ?? ''));
        if ($view === '' || $label === '') {
            continue;
        }
        $subs = [];
        foreach ((array)($item['sub_items'] ?? []) as $sub) {
            if (!is_array($sub)) {
                continue;
            }
            $jump = preg_replace('/[^a-zA-Z0-9_:\-]+/', '', trim((string)($sub['jump'] ?? ''))) ?? '';
            $subLabel = trim((string)($sub['label'] ?? ''));
            if ($jump === '' || $subLabel === '') {
                continue;
            }
            $subs[] = [
                'jump' => $jump,
                'label' => $subLabel,
                'keywords' => trim((string)($sub['keywords'] ?? $subLabel)),
                'svg' => zeynart_sector_normalize_svg((string)($sub['svg'] ?? ''), true),
                'super_only' => !empty($sub['super_only']),
            ];
        }
        $navItems[] = [
            'view' => $view,
            'label' => $label,
            'keywords' => trim((string)($item['keywords'] ?? $label)),
            'svg' => zeynart_sector_normalize_svg((string)($item['svg'] ?? ''), false),
            'super_only' => !empty($item['super_only']),
            'sub_items' => $subs,
        ];
    }

    $quickItems = [];
    foreach ((array)($spec['quick_create_items'] ?? []) as $qc) {
        if (!is_array($qc)) {
            continue;
        }
        $qcTitle = trim((string)($qc['title'] ?? ''));
        $qcJump = preg_replace('/[^a-zA-Z0-9_:\-]+/', '', trim((string)($qc['jump'] ?? ($qc['view'] ?? '')))) ?? '';
        if ($qcTitle === '' || $qcJump === '') {
            continue;
        }
        $quickItems[] = [
            'id' => preg_replace('/[^a-zA-Z0-9_\-]+/', '', (string)($qc['id'] ?? '')) ?: ('zsQuickExt_' . $id . '_' . count($quickItems)),
            'icon' => mb_substr(trim((string)($qc['icon'] ?? '✦')), 0, 8, 'UTF-8') ?: '✦',
            'title' => $qcTitle,
            'subtitle' => trim((string)($qc['subtitle'] ?? '')),
            'jump' => $qcJump,
            'super_only' => !empty($qc['super_only']),
        ];
    }

    $jsFiles = [];
    foreach ((array)($spec['js_files'] ?? []) as $jf) {
        $cleanJf = ltrim(str_replace('\\', '/', trim((string)$jf)), '/');
        if ($cleanJf !== '' && !str_contains($cleanJf, '..') && str_ends_with(strtolower($cleanJf), '.js')) {
            $jsFiles[] = $cleanJf;
        }
    }

    $cssFiles = [];
    foreach ((array)($spec['css_files'] ?? []) as $cf) {
        $cleanCf = ltrim(str_replace('\\', '/', trim((string)$cf)), '/');
        if ($cleanCf !== '' && !str_contains($cleanCf, '..') && str_ends_with(strtolower($cleanCf), '.css')) {
            $cssFiles[] = $cleanCf;
        }
    }

    $GLOBALS['__zeynart_sector_extensions'][$id] = [
        'id' => $id,
        'title' => trim((string)($spec['title'] ?? ucfirst($id))),
        'sector' => trim((string)($spec['sector'] ?? 'Özel Sektör Modülü')),
        'version' => trim((string)($spec['version'] ?? '1.0.0')),
        'description' => trim((string)($spec['description'] ?? '')),
        'nav_group_key' => preg_replace('/[^a-z0-9_\-]+/', '', strtolower(trim((string)($spec['nav_group_key'] ?? ('sector_' . $id))))) ?: ('sector_' . $id),
        'nav_group_label' => trim((string)($spec['nav_group_label'] ?? 'Sektörel Yönetim')),
        'nav_items' => $navItems,
        'quick_create_items' => $quickItems,
        'js_files' => array_values(array_unique($jsFiles)),
        'css_files' => array_values(array_unique($cssFiles)),
        'editor_actions' => array_values(array_filter(array_map('strval', (array)($spec['editor_actions'] ?? [])))),
        'super_actions' => array_values(array_filter(array_map('strval', (array)($spec['super_actions'] ?? [])))),
        'read_only_actions' => array_values(array_filter(array_map('strval', (array)($spec['read_only_actions'] ?? [])))),
        'boot_callback' => is_callable($spec['boot_callback'] ?? null) ? $spec['boot_callback'] : null,
        'api_handler' => is_callable($spec['api_handler'] ?? null) ? $spec['api_handler'] : null,
    ];
}

/**
 * Loads all active sector extensions for the current runtime (or inspects an external root directory).
 */
function zeynart_load_sector_extensions(?string $targetRoot = null): array
{
    $normMainRoot = rtrim(str_replace('\\', '/', ROOT_DIR), '/');
    $root = $targetRoot !== null ? rtrim(str_replace('\\', '/', $targetRoot), '/') : $normMainRoot;

    // External root inspection mode (e.g. during OTA client/test scan without executing foreign PHP in current process)
    if ($root !== $normMainRoot) {
        return zeynart_inspect_external_root_extensions($root);
    }

    if (empty($GLOBALS['__zeynart_sector_extensions_loaded'])) {
        $GLOBALS['__zeynart_sector_extensions_loaded'] = true;
        if (!isset($GLOBALS['__zeynart_sector_extensions']) || !is_array($GLOBALS['__zeynart_sector_extensions'])) {
            $GLOBALS['__zeynart_sector_extensions'] = [];
        }

        $extPhpDir = INCLUDES_DIR . '/extensions';
        if (is_dir($extPhpDir)) {
            $phpFiles = glob($extPhpDir . '/*.php') ?: [];
            sort($phpFiles);
            foreach ($phpFiles as $pf) {
                $base = basename($pf);
                if ($base === '' || $base[0] === '.') {
                    continue;
                }
                try {
                    require_once $pf;
                } catch (Throwable) {
                    // Fault-isolation: a broken custom sector script can never block Core Studio or OTA recovery.
                }
            }
        }

        // Auto-bridge any standalone <ADMIN_DIR>/extensions/*.js or legacy <ADMIN_DIR>/*-studio.js not yet bound
        $alreadyJs = [];
        foreach ($GLOBALS['__zeynart_sector_extensions'] as $ext) {
            foreach (($ext['js_files'] ?? []) as $jf) {
                $alreadyJs[strtolower($jf)] = true;
            }
        }

        $autoJsCandidates = [];
        if (is_dir(ADMIN_DIR . '/extensions')) {
            foreach (glob(ADMIN_DIR . '/extensions/*.js') ?: [] as $aj) {
                $b = basename($aj);
                if (!str_ends_with(strtolower($b), '.min.js')) {
                    $autoJsCandidates[] = 'extensions/' . $b;
                }
            }
        }
        foreach (glob(ADMIN_DIR . '/*-studio.js') ?: [] as $legacyJs) {
            $b = basename($legacyJs);
            if (!str_ends_with(strtolower($b), '.min.js')) {
                $autoJsCandidates[] = $b;
            }
        }

        foreach ($autoJsCandidates as $relJs) {
            if (isset($alreadyJs[strtolower($relJs)])) {
                continue;
            }
            $stem = preg_replace('/(?:-studio)?\.js$/i', '', basename($relJs)) ?? 'custom';
            $cssRel = preg_replace('/\.js$/i', '.css', $relJs) ?? '';
            $cssArr = ($cssRel !== '' && is_file(ADMIN_DIR . '/' . $cssRel)) ? [$cssRel] : [];
            zeynart_register_sector_extension([
                'id' => 'auto_' . $stem,
                'title' => strtoupper($stem) . ' Sektör Stüdyosu',
                'sector' => 'Otomatik Algılanan Sektör Katmanı',
                'version' => '1.0',
                'js_files' => [$relJs],
                'css_files' => $cssArr,
            ]);
        }
    }

    return $GLOBALS['__zeynart_sector_extensions'] ?? [];
}

/**
 * Safely inspects sector extensions & custom sector files inside an arbitrary client root directory.
 */
function zeynart_inspect_external_root_extensions(string $root): array
{
    $found = [];
    $admRel = function_exists('zeynart_ota_resolve_target_admin_rel_dir')
        ? zeynart_ota_resolve_target_admin_rel_dir($root)
        : 'admin';

    $extDir = $root . '/includes/extensions';
    if (is_dir($extDir)) {
        $files = glob($extDir . '/*.php') ?: [];
        sort($files);
        foreach ($files as $f) {
            $stem = pathinfo($f, PATHINFO_FILENAME);
            $src = (string)@file_get_contents($f, false, null, 0, 8192);
            $title = ucfirst($stem) . ' Sektör Eklentisi';
            $sector = 'Sektör Modülü';
            $ver = '1.0.0';
            if (preg_match("~['\"]title['\"]\\s*=>\\s*['\"]([^'\"]+)['\"]~u", $src, $mT)) {
                $title = $mT[1];
            }
            if (preg_match("~['\"]sector['\"]\\s*=>\\s*['\"]([^'\"]+)['\"]~u", $src, $mS)) {
                $sector = $mS[1];
            }
            if (preg_match("~['\"]version['\"]\\s*=>\\s*['\"]([^'\"]+)['\"]~u", $src, $mV)) {
                $ver = $mV[1];
            }
            $jsRel = 'extensions/' . $stem . '.js';
            $found[$stem] = [
                'id' => $stem,
                'title' => $title,
                'sector' => $sector,
                'version' => $ver,
                'php_file' => 'includes/extensions/' . basename($f),
                'js_files' => is_file($root . '/' . $admRel . '/' . $jsRel) ? [$jsRel] : [],
            ];
        }
    }

    foreach (glob($root . '/' . $admRel . '/*-studio.js') ?: [] as $lj) {
        $b = basename($lj);
        if (str_ends_with(strtolower($b), '.min.js')) {
            continue;
        }
        $stem = preg_replace('/-studio\.js$/i', '', $b) ?? 'studio';
        if (!isset($found[$stem])) {
            $found[$stem] = [
                'id' => $stem,
                'title' => strtoupper($stem) . ' Sektör Stüdyosu',
                'sector' => 'Sektör Arayüz Katmanı',
                'version' => '1.0',
                'php_file' => '',
                'js_files' => [$b],
            ];
        }
    }

    return $found;
}

/**
 * Serializes active sector extensions into safe metadata for Admin UI & OTA Overview.
 */
function zeynart_get_sector_extensions_public_meta(?string $targetRoot = null): array
{
    $exts = zeynart_load_sector_extensions($targetRoot);
    $out = [];
    foreach ($exts as $ext) {
        $views = [];
        foreach (($ext['nav_items'] ?? []) as $ni) {
            if (!empty($ni['view'])) {
                $views[] = (string)$ni['view'];
            }
        }
        $out[] = [
            'id' => (string)$ext['id'],
            'title' => (string)($ext['title'] ?? $ext['id']),
            'sector' => (string)($ext['sector'] ?? 'Özel Modül'),
            'version' => (string)($ext['version'] ?? '1.0.0'),
            'description' => (string)($ext['description'] ?? ''),
            'views' => $views,
            'js_files' => array_values($ext['js_files'] ?? []),
            'css_files' => array_values($ext['css_files'] ?? []),
        ];
    }
    return $out;
}

/**
 * Renders 100% SVG-icon-compliant sidebar navigation HTML for registered sector extensions.
 */
function zeynart_render_sector_sidebar_nav_html(string $adminRole = 'super_admin'): string
{
    $exts = zeynart_load_sector_extensions();
    if ($exts === []) {
        return '';
    }
    $isSuper = ($adminRole === 'super_admin');
    $html = '';

    foreach ($exts as $ext) {
        $items = $ext['nav_items'] ?? [];
        if ($items === []) {
            continue;
        }
        $visibleItems = [];
        foreach ($items as $it) {
            if (!empty($it['super_only']) && !$isSuper) {
                continue;
            }
            $visibleItems[] = $it;
        }
        if ($visibleItems === []) {
            continue;
        }

        $groupKey = e((string)$ext['nav_group_key']);
        $groupLabel = e((string)$ext['nav_group_label']);
        $html .= "\n        " . '<div class="zs-nav-group-label" data-group-header="' . $groupKey . '">' . $groupLabel . '</div>' . "\n";

        foreach ($visibleItems as $it) {
            $view = e((string)$it['view']);
            $label = e((string)$it['label']);
            $kw = e((string)$it['keywords']);
            $svg = (string)$it['svg'];
            $superAttr = !empty($it['super_only']) ? ' data-super-only="1"' : '';

            $subs = [];
            foreach (($it['sub_items'] ?? []) as $sub) {
                if (!empty($sub['super_only']) && !$isSuper) {
                    continue;
                }
                $subs[] = $sub;
            }

            $html .= '        <div class="zs-nav-item-wrap" data-nav-wrap="' . $view . '" data-nav-group="' . $groupKey . '" data-sector-ext="' . e((string)$ext['id']) . '"' . $superAttr . '>' . "\n";
            $html .= '          <button type="button" class="zs-nav-btn" data-view="' . $view . '" data-keywords="' . $kw . '">' . "\n";
            $html .= '            ' . $svg . "\n";
            $html .= '            <span>' . $label . '</span>' . "\n";
            if ($subs !== []) {
                $html .= '            <span class="zs-nav-chev" data-nav-toggle="' . $view . '" title="Alt menüyü aç/kapat">›</span>' . "\n";
            }
            $html .= '          </button>' . "\n";

            if ($subs !== []) {
                $html .= '          <div class="zs-nav-sub-list" data-sub-for="' . $view . '">' . "\n";
                foreach ($subs as $sub) {
                    $subSuper = !empty($sub['super_only']) ? ' data-super-only="1"' : '';
                    $html .= '            <button type="button" class="zs-nav-sub-btn" data-nav-jump="' . e((string)$sub['jump']) . '" data-keywords="' . e((string)$sub['keywords']) . '"' . $subSuper . '>' . "\n";
                    $html .= '              ' . (string)$sub['svg'] . "\n";
                    $html .= '              <span>' . e((string)$sub['label']) . '</span>' . "\n";
                    $html .= '            </button>' . "\n";
                }
                $html .= '          </div>' . "\n";
            }
            $html .= '        </div>' . "\n";
        }
    }

    return $html;
}

/**
 * Renders optional "+ Hızlı Ekle ▾" menu items registered by sector extensions.
 */
function zeynart_render_sector_quick_create_html(string $adminRole = 'super_admin'): string
{
    $exts = zeynart_load_sector_extensions();
    if ($exts === []) {
        return '';
    }
    $isSuper = ($adminRole === 'super_admin');
    $html = '';
    foreach ($exts as $ext) {
        foreach (($ext['quick_create_items'] ?? []) as $qc) {
            if (!empty($qc['super_only']) && !$isSuper) {
                continue;
            }
            $html .= '              <button type="button" class="zs-quick-create-item" id="' . e((string)$qc['id']) . '" data-sector-quick-jump="' . e((string)$qc['jump']) . '">' . "\n"
                . '                <span class="zs-qc-ico">' . e((string)$qc['icon']) . '</span>' . "\n"
                . '                <span><strong>' . e((string)$qc['title']) . '</strong>'
                . ($qc['subtitle'] !== '' ? '<small>' . e((string)$qc['subtitle']) . '</small>' : '')
                . '</span>' . "\n"
                . '              </button>' . "\n";
        }
    }
    return $html;
}

/**
 * Resolves versioned CSS & JS asset URLs inside ADMIN_DIR for all active sector extensions.
 *
 * @return array{css: list<string>, js: list<string>}
 */
function zeynart_collect_sector_admin_assets(array $store = []): array
{
    $exts = zeynart_load_sector_extensions();
    $cssUrls = [];
    $jsUrls = [];
    $seenCss = [];
    $seenJs = [];

    $hashFn = static fn(string $abs): string => is_file($abs)
        ? substr(dechex(abs(((int)@filemtime($abs) * 131) ^ (int)@filesize($abs))), 0, 10)
        : '1';

    foreach ($exts as $ext) {
        foreach (($ext['css_files'] ?? []) as $relCss) {
            if (isset($seenCss[$relCss])) {
                continue;
            }
            $seenCss[$relCss] = true;
            $minRel = preg_replace('/\.css$/i', '.min.css', $relCss) ?? $relCss;
            $rawAbs = ADMIN_DIR . '/' . $relCss;
            $minAbs = ADMIN_DIR . '/' . $minRel;
            $chosenRel = (is_file($minAbs) && (!is_file($rawAbs) || (int)@filemtime($minAbs) >= (int)@filemtime($rawAbs)))
                ? $minRel
                : $relCss;
            $chosenAbs = ADMIN_DIR . '/' . $chosenRel;
            if (is_file($chosenAbs)) {
                $cssUrls[] = zeynart_admin_url($chosenRel, $store) . '?v=' . $hashFn($chosenAbs);
            }
        }

        foreach (($ext['js_files'] ?? []) as $relJs) {
            if (isset($seenJs[$relJs])) {
                continue;
            }
            $seenJs[$relJs] = true;
            $minRel = preg_replace('/\.js$/i', '.min.js', $relJs) ?? $relJs;
            $rawAbs = ADMIN_DIR . '/' . $relJs;
            $minAbs = ADMIN_DIR . '/' . $minRel;
            $chosenRel = (is_file($minAbs) && (!is_file($rawAbs) || (int)@filemtime($minAbs) >= (int)@filemtime($rawAbs)))
                ? $minRel
                : $relJs;
            $chosenAbs = ADMIN_DIR . '/' . $chosenRel;
            if (is_file($chosenAbs)) {
                $jsUrls[] = zeynart_admin_url($chosenRel, $store) . '?v=' . $hashFn($chosenAbs);
            }
        }
    }

    return [
        'css' => $cssUrls,
        'js' => $jsUrls,
    ];
}

/**
 * Compiles boot payload contributed by sector extensions for action=boot.
 */
function zeynart_compile_sector_boot_payload(array $store, string $role): array
{
    $exts = zeynart_load_sector_extensions();
    $bootData = [];
    foreach ($exts as $id => $ext) {
        if (is_callable($ext['boot_callback'] ?? null)) {
            try {
                $res = ($ext['boot_callback'])($store, $role);
                if (is_array($res)) {
                    $bootData[$id] = $res;
                }
            } catch (Throwable $ex) {
                @error_log('[ZeynartSectorExtBoot:' . $id . '] ' . $ex->getMessage());
            }
        }
    }
    return [
        'registered' => zeynart_get_sector_extensions_public_meta(),
        'data' => $bootData === [] ? new stdClass() : $bootData,
    ];
}

function zeynart_sector_pattern_matches(string $action, string $pattern): bool
{
    $pat = trim($pattern);
    if ($pat === '') {
        return false;
    }
    if (str_ends_with($pat, '*')) {
        $prefix = substr($pat, 0, -1);
        return $prefix !== '' && str_starts_with($action, $prefix);
    }
    return $action === $pat;
}

/**
 * Evaluates whether a sector extension explicitly restricts or permits an API action for $role.
 * Returns true (allowed), false (restricted to super_admin), or null (not claimed by sector rules).
 */
function zeynart_sector_extension_allows_action(string $action, string $role): ?bool
{
    if ($role === 'super_admin') {
        return true;
    }
    $exts = zeynart_load_sector_extensions();
    foreach ($exts as $ext) {
        foreach (($ext['super_actions'] ?? []) as $sPat) {
            if (zeynart_sector_pattern_matches($action, $sPat)) {
                return false;
            }
        }
        foreach (($ext['editor_actions'] ?? []) as $ePat) {
            if (zeynart_sector_pattern_matches($action, $ePat)) {
                return true;
            }
        }
    }
    return null;
}

/**
 * Collects read-only action names registered by sector extensions so session_write_close() can unlock early.
 */
function zeynart_sector_read_only_actions(): array
{
    $exts = zeynart_load_sector_extensions();
    $list = [];
    foreach ($exts as $ext) {
        foreach (($ext['read_only_actions'] ?? []) as $act) {
            $list[] = (string)$act;
        }
    }
    return array_values(array_unique($list));
}

/**
 * Dispatches an API action to registered sector extension handlers.
 * Returns null if no extension handled $action, or an associative response array.
 */
function zeynart_dispatch_sector_extension_action(string $action, array $data, array &$store, string $role): ?array
{
    $exts = zeynart_load_sector_extensions();
    foreach ($exts as $id => $ext) {
        if (!is_callable($ext['api_handler'] ?? null)) {
            continue;
        }
        $res = ($ext['api_handler'])($action, $data, $store, $role);
        if (is_array($res)) {
            return $res;
        }
    }
    return null;
}
