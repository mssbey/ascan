<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';

/**
 * 6698 Sayılı KVKK, Kişisel Verileri Koruma Kurumu "Çerez Uygulamaları Hakkında Rehber"
 * ve Google Consent Mode v2 uyumlu Sıfır-Çerez (Zero-Cookie) Açık Rıza Altyapısı.
 */
function zeynart_default_cookie_policy_page(array $site = []): array
{
    $legalName = (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri');
    $address = (string)($site['address'] ?? 'Maltepe Mh. 21078 Sk. No:6/A Menemen / İzmir');
    $email = (string)($site['email'] ?? 'info@zeynart.com');
    $phone = (string)($site['phone'] ?? '0850 305 0 533');

    $tableHtml = <<<HTML
<p>Bu Çerez Politikası; <strong>{$legalName}</strong> (“Veri Sorumlusu”) tarafından işletilen internet sitesini ziyaret eden kullanıcıların, <strong>6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”)</strong> ve Kişisel Verileri Koruma Kurumu’nun <em>Çerez Uygulamaları Hakkında Rehberi</em> uyarınca çerez ve benzeri teknolojilerin kullanımı hakkında şeffaf biçimde bilgilendirilmesi amacıyla hazırlanmıştır.</p>

<div style="margin:22px 0;padding:18px 20px;border-radius:16px;border:1px solid rgba(94,234,212,.28);background:rgba(20,184,166,.07)">
  <strong style="display:block;margin-bottom:6px;color:#5eead4">Sıfır Çerez Yüklemesi (Zero-Cookie Load) Güvencesi</strong>
  <p style="margin:0 0 12px">Sitemize ilk girişinizde ve çerez bilgilendirme katmanı açıkken tarayıcınıza <strong>zorunlu olmayan hiçbir analitik, işlevsel veya pazarlama/piksel çerezi yazılmaz</strong>. Tercih yönetim panelindeki isteğe bağlı kategoriler varsayılan olarak kapalı (işaretsiz) gelir ve dilediğiniz an aşağıdaki düğmeden tercihlerinizi değiştirebilir veya açık rızanızı geri çekebilirsiniz.</p>
  <button type="button" class="button button-primary" data-open-cookie-prefs="1" style="cursor:pointer">Çerez Tercihlerimi Yönet</button>
</div>

<h2>1. Veri Sorumlusu Bilgileri</h2>
<ul>
  <li><strong>Unvan:</strong> {$legalName}</li>
  <li><strong>Adres:</strong> {$address}</li>
  <li><strong>E-posta:</strong> <a href="mailto:{$email}">{$email}</a></li>
  <li><strong>Telefon:</strong> {$phone}</li>
</ul>

<h2>2. Çerez (Cookie) Nedir?</h2>
<p>Çerezler, ziyaret ettiğiniz internet siteleri tarafından tarayıcınız aracılığıyla cihazınıza kaydedilen küçük metin dosyalarıdır. Bunun yanında tercih kaydı için tarayıcı yerel depolama alanı (<code>localStorage</code>) gibi benzer teknolojiler de kullanılabilir.</p>

<h2>3. Kullanılan Çerez Kategorileri, Amaçları ve Hukuki Sebepleri</h2>
<div style="overflow-x:auto;margin:16px 0 24px">
  <table style="width:100%;border-collapse:collapse;font-size:14px">
    <thead>
      <tr style="border-bottom:1px solid rgba(255,255,255,.16);text-align:left">
        <th style="padding:10px 12px">Çerez / Depolama Adı</th>
        <th style="padding:10px 12px">Kategori</th>
        <th style="padding:10px 12px">Kullanım Amacı</th>
        <th style="padding:10px 12px">Süre</th>
        <th style="padding:10px 12px">KVKK Hukuki Sebebi</th>
      </tr>
    </thead>
    <tbody>
      <tr style="border-bottom:1px solid rgba(255,255,255,.08)">
        <td style="padding:10px 12px"><code>zeynart_cookie_consent_v2</code> (Yerel Depolama)</td>
        <td style="padding:10px 12px">Zorunlu Teknik</td>
        <td style="padding:10px 12px">Açık rıza / reddetme tercihlerinizin hatırlanması ve onaylanmayan scriptlerin engellenmesi</td>
        <td style="padding:10px 12px">180 Gün</td>
        <td style="padding:10px 12px">KVKK m.5/2-ç &amp; m.5/2-f (Hukuki yükümlülük ve meşru menfaat — açık rıza şartına tabi değildir)</td>
      </tr>
      <tr style="border-bottom:1px solid rgba(255,255,255,.08)">
        <td style="padding:10px 12px">İşlevsel Tercih Kayıtları</td>
        <td style="padding:10px 12px">İşlevsel (Varsayılan Kapalı)</td>
        <td style="padding:10px 12px">Dil ve arayüz tercihlerinizin sonraki ziyaretlerde hatırlanması</td>
        <td style="padding:10px 12px">90 Gün</td>
        <td style="padding:10px 12px">KVKK m.5/1 — <strong>Açık Rıza</strong></td>
      </tr>
      <tr style="border-bottom:1px solid rgba(255,255,255,.08)">
        <td style="padding:10px 12px"><code>_ga</code>, <code>_ga_*</code> (Google Analytics 4)</td>
        <td style="padding:10px 12px">Analitik &amp; Performans (Varsayılan Kapalı)</td>
        <td style="padding:10px 12px">Anonimleştirilmiş ziyaretçi istatistikleri ve sayfa performans ölçümü (yalnızca onay verirseniz yüklenir)</td>
        <td style="padding:10px 12px">En fazla 13 Ay</td>
        <td style="padding:10px 12px">KVKK m.5/1 — <strong>Açık Rıza</strong> (ve m.9 yurt dışı aktarım açık rızası)</td>
      </tr>
      <tr>
        <td style="padding:10px 12px"><code>_fbp</code>, <code>_gcl_au</code> vb.</td>
        <td style="padding:10px 12px">Pazarlama &amp; Hedefleme (Varsayılan Kapalı)</td>
        <td style="padding:10px 12px">Kampanya dönüşüm ölçümü ve ilgi alanına dayalı tanıtım gösterimi (yalnızca onay verirseniz yüklenir)</td>
        <td style="padding:10px 12px">90 Gün</td>
        <td style="padding:10px 12px">KVKK m.5/1 — <strong>Açık Rıza</strong></td>
      </tr>
    </tbody>
  </table>
</div>

<h2>4. Çerez Tercihlerinin Yönetimi ve Tarayıcı Ayarları</h2>
<p>Sitemizin en alt kısmında yer alan <strong>“Çerez Tercihleri”</strong> bağlantısına tıklayarak tercih yönetim panelini dilediğiniz zaman yeniden açabilir, verdiğiniz onayı geri çekebilir veya <strong>“Tümünü Reddet”</strong> seçeneğini kullanabilirsiniz. Açık rızanızı geri çektiğiniz anda tarayıcınızdaki analitik ve pazarlama çerezleri otomatik olarak silinir.</p>
<p>Ayrıca tarayıcınızın ayarlar menüsünden de çerezleri engelleyebilir veya silebilirsiniz:</p>
<ul>
  <li><strong>Google Chrome:</strong> Ayarlar → Gizlilik ve Güvenlik → Üçüncü Taraf Çerezleri / Site Verileri</li>
  <li><strong>Mozilla Firefox:</strong> Ayarlar → Gizlilik ve Güvenlik → Çerezler ve Site Verileri</li>
  <li><strong>Apple Safari:</strong> Ayarlar / Tercihler → Gizlilik → Web Sitesi Verilerini Yönet</li>
  <li><strong>Microsoft Edge:</strong> Ayarlar → Çerezler ve Site İzinleri</li>
</ul>

<h2>5. KVKK Madde 11 Kapsamındaki Haklarınız</h2>
<p>KVKK’nın 11. maddesi uyarınca kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse bilgi talep etme, işlenme amacını öğrenme, eksik veya yanlış işlenmişse düzeltilmesini isteme, silinmesini veya yok edilmesini talep etme ve kanuna aykırı işleme nedeniyle zararın giderilmesini talep etme haklarına sahipsiniz. Taleplerinizi <a href="mailto:{$email}">{$email}</a> adresine veya iletişim sayfamız üzerinden bize iletebilirsiniz.</p>
HTML;

    return [
        'slug' => 'cerez-politikasi',
        'eyebrow' => '6698 SAYILI KVKK & ŞEFFAFLIK',
        'title' => 'Çerez Politikası',
        'lead' => 'Kişisel verilerinizin gizliliğine ve açık rıza ilkelerine tam uyum sağlıyoruz. Onayınız olmadan zorunlu olmayan hiçbir analitik veya pazarlama çerezi tarayıcınıza yüklenmez.',
        'meta_title' => 'Çerez Politikası (KVKK Uyumlu) | ZEYNART',
        'meta_desc' => 'Zeynart Web Bilişim KVKK uyumlu çerez politikası, kullanılan çerez türleri, sıfır çerez (zero-cookie) açık rıza yönetimi ve ilgili kişi hakları.',
        'content_html' => $tableHtml,
        'sections' => [],
    ];
}

function zeynart_builtin_localized_cookie_policy(array $lp, string $lang, array $site = []): ?array
{
    $lang = strtolower(trim($lang));
    if (!in_array($lang, ['en', 'de', 'ru'], true)) {
        return null;
    }
    $defaultTr = zeynart_default_cookie_policy_page($site);
    if (($lp['title'] ?? '') !== $defaultTr['title'] || !empty($lp['sections'])) {
        return null;
    }
    $legalName = (string)($site['legal_name'] ?? 'Zeynart Web Bilişim Hizmetleri');
    $address = (string)($site['address'] ?? 'Maltepe Mh. 21078 Sk. No:6/A Menemen / İzmir');
    $email = (string)($site['email'] ?? 'info@zeynart.com');
    $phone = (string)($site['phone'] ?? '0850 305 0 533');

    if ($lang === 'en') {
        return [
            'slug' => 'cookie-policy',
            'eyebrow' => 'KVKK & GDPR TRANSPARENCY',
            'title' => 'Cookie Policy',
            'lead' => 'We respect your privacy and strict prior-consent principles. No non-essential analytics, functional, or marketing cookies are loaded onto your browser without your explicit consent.',
            'meta_title' => 'Cookie Policy (KVKK & GDPR Compliant) | ZEYNART',
            'meta_desc' => 'Zeynart Cookie Policy, cookie categories, strict zero-cookie prior-consent controls, and your data protection rights.',
            'sections' => [],
            'content_html' => <<<HTML
<p>This Cookie Policy explains how <strong>{$legalName}</strong> (“Data Controller”) uses cookies and local storage technologies in full compliance with Turkish Personal Data Protection Law No. 6698 (“KVKK”) and international ePrivacy / GDPR consent standards.</p>
<div style="margin:22px 0;padding:18px 20px;border-radius:16px;border:1px solid rgba(94,234,212,.28);background:rgba(20,184,166,.07)">
  <strong style="display:block;margin-bottom:6px;color:#5eead4">Zero-Cookie Load Guarantee</strong>
  <p style="margin:0 0 12px">When you first visit our website and before you make a choice on the cookie banner, <strong>zero non-essential analytics, functional, or marketing/pixel cookies are written to your browser</strong>. All optional categories are unchecked (OFF) by default, and you can change or withdraw your consent at any time below.</p>
  <button type="button" class="button button-primary" data-open-cookie-prefs="1" style="cursor:pointer">Manage Cookie Preferences</button>
</div>
<h2>1. Data Controller</h2>
<ul>
  <li><strong>Legal Entity:</strong> {$legalName}</li>
  <li><strong>Address:</strong> {$address}</li>
  <li><strong>Email:</strong> <a href="mailto:{$email}">{$email}</a></li>
  <li><strong>Phone:</strong> {$phone}</li>
</ul>
<h2>2. Cookie Categories &amp; Legal Basis</h2>
<ul>
  <li><strong>Strictly Necessary Technical Storage (Always Active):</strong> Stores your consent choice (<code>zeynart_cookie_consent_v2</code>) for 180 days without setting tracking cookies.</li>
  <li><strong>Functional Preferences (Off by Default — Explicit Consent):</strong> Remembers language and UI preferences when explicitly enabled.</li>
  <li><strong>Analytics &amp; Performance (Off by Default — Explicit Consent):</strong> Google Analytics 4 (<code>_ga</code>, <code>_ga_*</code>) is dynamically loaded only after explicit opt-in.</li>
  <li><strong>Marketing &amp; Targeting (Off by Default — Explicit Consent):</strong> Conversion pixels (<code>_fbp</code>, <code>_gcl_au</code>) are blocked unless you explicitly opt in.</li>
</ul>
<h2>3. How to Withdraw Consent</h2>
<p>You can click <strong>“Cookie Preferences”</strong> in the website footer at any time to reopen the preference center or click <strong>“Reject All”</strong>, which immediately purges analytics and marketing cookies from your browser.</p>
HTML,
        ];
    }

    if ($lang === 'de') {
        return [
            'slug' => 'cerez-politikasi',
            'eyebrow' => 'DATENSCHUTZ & TRANSPARENZ',
            'title' => 'Cookie-Richtlinie',
            'lead' => 'Ohne Ihre ausdrückliche Einwilligung werden keinerlei optionale Analyse-, Funktions- oder Marketing-Cookies in Ihrem Browser gespeichert (Zero-Cookie Load).',
            'meta_title' => 'Cookie-Richtlinie | ZEYNART',
            'meta_desc' => 'Cookie-Richtlinie von Zeynart: Cookie-Kategorien, Zero-Cookie-Einwilligungsverwaltung und Ihre Datenschutzrechte.',
            'sections' => [],
            'content_html' => <<<HTML
<p>Diese Cookie-Richtlinie informiert Sie darüber, wie <strong>{$legalName}</strong> („Verantwortlicher“) Cookies und lokale Speichertechnologien gemäß den geltenden Datenschutzvorschriften (KVKK Nr. 6698 &amp; DSGVO-Standards) einsetzt.</p>
<div style="margin:22px 0;padding:18px 20px;border-radius:16px;border:1px solid rgba(94,234,212,.28);background:rgba(20,184,166,.07)">
  <strong style="display:block;margin-bottom:6px;color:#5eead4">Zero-Cookie-Garantie vor Einwilligung</strong>
  <p style="margin:0 0 12px">Vor Ihrer aktiven Auswahl im Cookie-Banner werden <strong>keine optionalen Analyse- oder Marketing-Cookies geladen</strong>. Alle optionalen Kategorien sind standardmäßig deaktiviert.</p>
  <button type="button" class="button button-primary" data-open-cookie-prefs="1" style="cursor:pointer">Cookie-Einstellungen verwalten</button>
</div>
<h2>1. Verantwortliche Stelle</h2>
<ul>
  <li><strong>Unternehmen:</strong> {$legalName}</li>
  <li><strong>Adresse:</strong> {$address}</li>
  <li><strong>E-Mail:</strong> <a href="mailto:{$email}">{$email}</a></li>
  <li><strong>Telefon:</strong> {$phone}</li>
</ul>
<h2>2. Cookie-Kategorien &amp; Widerruf</h2>
<p>Über die Schaltfläche <strong>„Cookie-Einstellungen“</strong> im Footer können Sie Ihre Auswahl jederzeit ändern oder über <strong>„Alle ablehnen“</strong> widerrufen.</p>
HTML,
        ];
    }

    return [
        'slug' => 'cerez-politikasi',
        'eyebrow' => 'КОНФИДЕНЦИАЛЬНОСТЬ И ПРОЗРАЧНОСТЬ',
        'title' => 'Политика использования файлов cookie',
        'lead' => 'Без вашего явного согласия аналитические, функциональные и маркетинговые файлы cookie не загружаются в ваш браузер (Zero-Cookie Load).',
        'meta_title' => 'Политика использования файлов cookie | ZEYNART',
        'meta_desc' => 'Политика использования файлов cookie Zeynart, категории cookie и управление согласием.',
        'sections' => [],
        'content_html' => <<<HTML
<p>Настоящая Политика объясняет, как <strong>{$legalName}</strong> («Оператор данных») использует файлы cookie и локальное хранилище в соответствии с законодательством о защите персональных данных.</p>
<div style="margin:22px 0;padding:18px 20px;border-radius:16px;border:1px solid rgba(94,234,212,.28);background:rgba(20,184,166,.07)">
  <strong style="display:block;margin-bottom:6px;color:#5eead4">Гарантия Zero-Cookie до получения согласия</strong>
  <p style="margin:0 0 12px">До вашего выбора в баннере аналитические и маркетинговые скрипты полностью заблокированы, а необязательные категории отключены по умолчанию.</p>
  <button type="button" class="button button-primary" data-open-cookie-prefs="1" style="cursor:pointer">Настроить файлы cookie</button>
</div>
<h2>1. Контактные данные оператора</h2>
<ul>
  <li><strong>Компания:</strong> {$legalName}</li>
  <li><strong>Адрес:</strong> {$address}</li>
  <li><strong>Email:</strong> <a href="mailto:{$email}">{$email}</a></li>
  <li><strong>Телефон:</strong> {$phone}</li>
</ul>
HTML,
    ];
}

function zeynart_default_cookie_consent_config(): array
{
    return [
        'enabled' => true,
        'banner_title' => 'Çerez ve Gizlilik Tercihleriniz',
        'banner_text' => 'Deneyiminizi güvenle sürdürebilmeniz için yalnızca zorunlu teknik kayıtları kullanıyoruz. Analitik, işlevsel ve pazarlama çerezleri varsayılan olarak kapalıdır; açık rızanız olmadan tarayıcınıza hiçbir izleme çerezi yüklenmez.',
        'policy_label' => 'Çerez Politikası',
        'policy_url' => '/cerez-politikasi/',
        'kvkk_label' => 'KVKK Aydınlatma Metni',
        'kvkk_url' => '/kvkk/',
        'accept_all_label' => 'Tümünü Kabul Et',
        'reject_all_label' => 'Tümünü Reddet',
        'manage_label' => 'Tercihleri Yönet',
        'save_prefs_label' => 'Seçimlerimi Kaydet',
        'footer_reopen_label' => 'Çerez Tercihleri',
        'meta_pixel_id' => '',
        'custom_head_essential' => '',
        'custom_head_analytics' => '',
        'custom_head_marketing' => '',
    ];
}

/**
 * Runtime store normalizer: guarantees `/cerez-politikasi/`, Footer links, and KVKK consent defaults
 * work even on an existing live `data/store.json` without overwriting user data.
 */
function zeynart_normalize_cookie_compliance(array &$store): bool
{
    $changed = false;
    $site = is_array($store['site'] ?? null) ? $store['site'] : [];

    if (!isset($store['legal_pages']) || !is_array($store['legal_pages'])) {
        $store['legal_pages'] = [];
    }
    if (empty($store['legal_pages']['cerez-politikasi']) || !is_array($store['legal_pages']['cerez-politikasi'])) {
        $store['legal_pages']['cerez-politikasi'] = zeynart_default_cookie_policy_page($site);
        $changed = true;
    }

    $defaults = zeynart_default_cookie_consent_config();
    if (!isset($store['cookie_consent']) || !is_array($store['cookie_consent'])) {
        $store['cookie_consent'] = $defaults;
        $changed = true;
    } else {
        foreach ($defaults as $dk => $dv) {
            if (!array_key_exists($dk, $store['cookie_consent'])) {
                $store['cookie_consent'][$dk] = $dv;
                $changed = true;
            }
        }
    }

    // Normalize Footer legal_links: upgrade legacy /cerezler/ -> /cerez-politikasi/ and ensure presence
    if (!isset($store['menus']) || !is_array($store['menus'])) {
        $store['menus'] = [];
    }
    if (!isset($store['menus']['footer']) || !is_array($store['menus']['footer'])) {
        $store['menus']['footer'] = [];
    }
    $legalLinks = is_array($store['menus']['footer']['legal_links'] ?? null)
        ? $store['menus']['footer']['legal_links']
        : [];

    $customCpSlug = slugify((string)(
        $store['page_slugs']['cerez-politikasi']
        ?? ($store['legal_pages']['cerez-politikasi']['slug'] ?? 'cerez-politikasi')
    ));
    if ($customCpSlug === '') {
        $customCpSlug = 'cerez-politikasi';
    }
    $targetCpUrl = '/' . $customCpSlug . '/';

    $hasCookiePolicy = false;
    foreach ($legalLinks as $idx => $ll) {
        $u = '/' . trim((string)($ll['url'] ?? ''), '/');
        if ($u === '/cerezler') {
            $legalLinks[$idx]['url'] = $targetCpUrl;
            if (in_array(trim((string)($ll['label'] ?? '')), ['', 'Çerezler', 'Cookies'], true)) {
                $legalLinks[$idx]['label'] = 'Çerez Politikası';
            }
            $hasCookiePolicy = true;
            $changed = true;
        } elseif ($u === '/cerez-politikasi' || $u === '/cookie-policy' || $u === '/' . $customCpSlug) {
            $hasCookiePolicy = true;
        }
    }
    if (!$hasCookiePolicy) {
        $legalLinks[] = [
            'label' => 'Çerez Politikası',
            'url' => $targetCpUrl,
        ];
        $changed = true;
    }
    $store['menus']['footer']['legal_links'] = array_values($legalLinks);

    return $changed;
}

function zeynart_cookie_policy_url_for_lang(?string $lang = null): string
{
    $l = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    if ($l === 'en') {
        return lang_url('/cookie-policy/', 'en');
    }
    return lang_url('/cerez-politikasi/', $l);
}

/**
 * Renders Google Consent Mode v2 default-denied state + inert JSON config in `<head>`.
 * Strictly ZERO analytics/marketing external scripts or cookies are loaded before explicit consent!
 */
function zeynart_render_consent_head(array $store): string
{
    $seoCfg = is_array($store['seo'] ?? null) ? $store['seo'] : [];
    $cc = array_merge(zeynart_default_cookie_consent_config(), is_array($store['cookie_consent'] ?? null) ? $store['cookie_consent'] : []);

    $gaRaw = trim((string)($seoCfg['ga_measurement_id'] ?? ''));
    $gaId = preg_match('/^G-[A-Z0-9]+$/i', $gaRaw) ? $gaRaw : '';
    $pixelRaw = trim((string)($cc['meta_pixel_id'] ?? ''));
    $pixelId = preg_match('/^\d{6,24}$/', $pixelRaw) ? $pixelRaw : '';

    $lines = [];
    // Google Consent Mode v2 — Default ALL non-essential storages DENIED (Zero-Cookie Load)
    $lines[] = '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("consent","default",{ad_storage:"denied",ad_user_data:"denied",ad_personalization:"denied",analytics_storage:"denied",functionality_storage:"denied",personalization_storage:"denied",security_storage:"granted",wait_for_update:500});gtag("set","ads_data_redaction",true);gtag("set","url_passthrough",false);</script>';

    $essentialHead = trim((string)($cc['custom_head_essential'] ?? ''));
    if ($essentialHead !== '') {
        $lines[] = $essentialHead;
    }

    $runtimeCfg = [
        'enabled' => ($cc['enabled'] ?? true) !== false,
        'gaId' => $gaId,
        'metaPixelId' => $pixelId,
        'analyticsHtml' => trim((string)($cc['custom_head_analytics'] ?? '')),
        'marketingHtml' => trim((string)($cc['custom_head_marketing'] ?? '')),
    ];
    $lines[] = '<script id="zeynart-consent-cfg" type="application/json">'
        . json_encode($runtimeCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)
        . '</script>';

    return implode("\n", $lines);
}

/**
 * Renders the KVKK-compliant Cookie Banner ("Tümünü Reddet" right next to "Tümünü Kabul Et"),
 * Category Preference Modal (Necessary locked ON; Functional, Analytics, Marketing strictly OFF by default),
 * and the Zero-Cookie Load JS controller.
 */
function zeynart_render_cookie_banner(array $store): string
{
    $cc = array_merge(zeynart_default_cookie_consent_config(), is_array($store['cookie_consent'] ?? null) ? $store['cookie_consent'] : []);
    if (($cc['enabled'] ?? true) === false) {
        return '';
    }

    $lang = zeynart_current_lang();
    $i18nUi = [
        'en' => [
            'title' => 'Cookie & Privacy Preferences',
            'text' => 'We only use strictly necessary technical storage to ensure security. Analytics, functional, and marketing cookies are disabled by default — zero tracking cookies are loaded without your explicit consent.',
            'policy' => 'Cookie Policy',
            'kvkk' => 'Privacy & Data Protection Notice',
            'manage' => 'Manage Preferences',
            'reject' => 'Reject All',
            'accept' => 'Accept All',
            'save' => 'Save Preferences',
            'modal_title' => 'Cookie Preference Center',
            'modal_lead' => 'Optional cookie categories are turned off by default. You can customize your choices below or reject all non-essential cookies at any time.',
            'cat_nec_t' => 'Strictly Necessary Cookies',
            'cat_nec_b' => 'Always Active',
            'cat_nec_d' => 'Required for core security, CSRF protection, and remembering your consent choice. Cannot be disabled.',
            'cat_fun_t' => 'Functional & Preference Cookies',
            'cat_fun_d' => 'Allows the website to remember interface and language preferences across visits.',
            'cat_ana_t' => 'Analytics & Performance Cookies',
            'cat_ana_d' => 'Helps us measure anonymous traffic and page speed (Google Analytics 4). Loaded only after explicit consent.',
            'cat_mkt_t' => 'Marketing & Targeting Cookies',
            'cat_mkt_d' => 'Used to measure campaign effectiveness and conversion pixels. Loaded only after explicit consent.',
            'close_aria' => 'Close preference window',
        ],
        'de' => [
            'title' => 'Cookie- & Datenschutzeinstellungen',
            'text' => 'Wir verwenden standardmäßig nur technisch notwendige Speicherungen. Analyse-, Funktions- und Marketing-Cookies sind deaktiviert und werden ohne Ihre ausdrückliche Einwilligung nicht geladen.',
            'policy' => 'Cookie-Richtlinie',
            'kvkk' => 'Datenschutzhinweis',
            'manage' => 'Einstellungen verwalten',
            'reject' => 'Alle ablehnen',
            'accept' => 'Alle akzeptieren',
            'save' => 'Auswahl speichern',
            'modal_title' => 'Cookie-Präferenzzentrum',
            'modal_lead' => 'Optionale Kategorien sind standardmäßig deaktiviert. Sie können Ihre Auswahl jederzeit anpassen oder ablehnen.',
            'cat_nec_t' => 'Technisch notwendige Cookies',
            'cat_nec_b' => 'Immer aktiv',
            'cat_nec_d' => 'Erforderlich für Sicherheit und die Speicherung Ihrer Datenschutzauswahl.',
            'cat_fun_t' => 'Funktionale Cookies',
            'cat_fun_d' => 'Speichert Sprach- und Darstellungseinstellungen.',
            'cat_ana_t' => 'Analyse- & Leistungs-Cookies',
            'cat_ana_d' => 'Anonyme Reichweitenmessung (Google Analytics 4). Wird erst nach Einwilligung geladen.',
            'cat_mkt_t' => 'Marketing- & Pixel-Cookies',
            'cat_mkt_d' => 'Kampagnen- und Konversionsmessung. Wird erst nach Einwilligung geladen.',
            'close_aria' => 'Schließen',
        ],
        'ru' => [
            'title' => 'Настройки файлов cookie и конфиденциальности',
            'text' => 'По умолчанию используются только строго необходимые технические данные. Аналитические, функциональные и маркетинговые cookie отключены и не загружаются без вашего явного согласия.',
            'policy' => 'Политика использования cookie',
            'kvkk' => 'Уведомление о защите данных',
            'manage' => 'Настроить выбор',
            'reject' => 'Отклонить все',
            'accept' => 'Принять все',
            'save' => 'Сохранить выбор',
            'modal_title' => 'Центр управления файлами cookie',
            'modal_lead' => 'Необязательные категории по умолчанию выключены. Вы можете изменить свой выбор в любое время.',
            'cat_nec_t' => 'Строго необходимые cookie',
            'cat_nec_b' => 'Всегда активны',
            'cat_nec_d' => 'Необходимы для безопасности сайта и сохранения вашего выбора согласия.',
            'cat_fun_t' => 'Функциональные cookie',
            'cat_fun_d' => 'Запоминают языковые и интерфейсные предпочтения.',
            'cat_ana_t' => 'Аналитические cookie',
            'cat_ana_d' => 'Анонимная статистика посещаемости (Google Analytics 4). Загружаются только после согласия.',
            'cat_mkt_t' => 'Маркетинговые cookie',
            'cat_mkt_d' => 'Измерение эффективности рекламных кампаний. Загружаются только после согласия.',
            'close_aria' => 'Закрыть',
        ],
    ];

    $L = $i18nUi[$lang] ?? [
        'title' => (string)($cc['banner_title'] ?? 'Çerez ve Gizlilik Tercihleriniz'),
        'text' => (string)($cc['banner_text'] ?? ''),
        'policy' => (string)($cc['policy_label'] ?? 'Çerez Politikası'),
        'kvkk' => (string)($cc['kvkk_label'] ?? 'KVKK Aydınlatma Metni'),
        'manage' => (string)($cc['manage_label'] ?? 'Tercihleri Yönet'),
        'reject' => (string)($cc['reject_all_label'] ?? 'Tümünü Reddet'),
        'accept' => (string)($cc['accept_all_label'] ?? 'Tümünü Kabul Et'),
        'save' => (string)($cc['save_prefs_label'] ?? 'Seçimlerimi Kaydet'),
        'modal_title' => 'Çerez Tercih Yönetim Paneli (KVKK)',
        'modal_lead' => 'KVKK Çerez Uygulamaları Rehberi uyarınca zorunlu olmayan tüm çerez kategorileri varsayılan olarak kapalı (işaretsiz) gelir. Açık rıza vermediğiniz hiçbir analitik veya pazarlama çerezi tarayıcınıza yüklenmez.',
        'cat_nec_t' => 'Zorunlu Teknik Çerezler',
        'cat_nec_b' => 'Zorunlu · Kilitli',
        'cat_nec_d' => 'Sitenin güvenli çalışması, form güvenliği ve çerez onay/ret tercihinizin hatırlanması için zorunludur; kapatılamaz.',
        'cat_fun_t' => 'İşlevsel ve Tercih Çerezleri',
        'cat_fun_d' => 'Dil seçimi ve arayüz tercihlerinizin sonraki ziyaretlerinizde hatırlanmasını sağlar.',
        'cat_ana_t' => 'Analitik ve Performans Çerezleri',
        'cat_ana_d' => 'Sayfa hızı ve anonim ziyaretçi istatistiklerinin (Google Analytics 4) ölçülmesini sağlar. Onay vermeden yüklenmez.',
        'cat_mkt_t' => 'Pazarlama ve Hedefleme Çerezleri',
        'cat_mkt_d' => 'Dijital kampanya dönüşüm ölçümü ve reklam pikselleri için kullanılır. Onay vermeden yüklenmez.',
        'close_aria' => 'Tercih penceresini kapat',
    ];

    $policyHref = zeynart_cookie_policy_url_for_lang($lang);
    $kvkkHref = lang_url((string)($cc['kvkk_url'] ?? '/kvkk/'), $lang);

    ob_start();
    ?>
<div id="zeynartCookieBanner" class="zcc-banner" role="region" aria-label="<?= e($L['title']) ?>">
  <div class="zcc-banner-inner">
    <div class="zcc-banner-copy">
      <strong class="zcc-banner-title"><?= e($L['title']) ?></strong>
      <p class="zcc-banner-desc">
        <?= e($L['text']) ?>
        <a href="<?= e($policyHref) ?>" class="zcc-inline-link"><?= e($L['policy']) ?></a>
        ·
        <a href="<?= e($kvkkHref) ?>" class="zcc-inline-link"><?= e($L['kvkk']) ?></a>
      </p>
    </div>
    <div class="zcc-banner-actions">
      <button type="button" class="zcc-btn zcc-btn-manage" id="zccOpenModalBtn"><?= e($L['manage']) ?></button>
      <button type="button" class="zcc-btn zcc-btn-equal" id="zccRejectAllBtn"><?= e($L['reject']) ?></button>
      <button type="button" class="zcc-btn zcc-btn-equal zcc-btn-accept" id="zccAcceptAllBtn"><?= e($L['accept']) ?></button>
    </div>
  </div>
</div>
<script>try{var _zcc=localStorage.getItem('zeynart_cookie_consent_v2');if(_zcc){var _zo=JSON.parse(_zcc);if(_zo&&typeof _zo.analytics==='boolean'&&(!_zo.expiresAt||Date.now()<=_zo.expiresAt)){var _zb=document.getElementById('zeynartCookieBanner');if(_zb)_zb.hidden=true;}}}catch(_e){}</script>

<div id="zeynartCookieModal" class="zcc-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="zccModalTitle" hidden>
  <div class="zcc-modal-card">
    <div class="zcc-modal-head">
      <div>
        <h2 id="zccModalTitle" class="zcc-modal-title"><?= e($L['modal_title']) ?></h2>
        <p class="zcc-modal-lead"><?= e($L['modal_lead']) ?></p>
      </div>
      <button type="button" class="zcc-close-btn" id="zccCloseModalBtn" aria-label="<?= e($L['close_aria']) ?>">✕</button>
    </div>

    <div class="zcc-cat-list">
      <div class="zcc-cat-item">
        <div class="zcc-cat-top">
          <div>
            <strong><?= e($L['cat_nec_t']) ?></strong>
            <span class="zcc-locked-badge"><?= e($L['cat_nec_b']) ?></span>
          </div>
          <label class="zcc-switch" aria-label="<?= e($L['cat_nec_t']) ?>">
            <input type="checkbox" id="zccCatNecessary" checked disabled/>
            <span class="zcc-slider is-locked"></span>
          </label>
        </div>
        <p><?= e($L['cat_nec_d']) ?></p>
      </div>

      <div class="zcc-cat-item">
        <div class="zcc-cat-top">
          <strong><?= e($L['cat_fun_t']) ?></strong>
          <label class="zcc-switch" aria-label="<?= e($L['cat_fun_t']) ?>">
            <input type="checkbox" id="zccCatFunctional"/>
            <span class="zcc-slider"></span>
          </label>
        </div>
        <p><?= e($L['cat_fun_d']) ?></p>
      </div>

      <div class="zcc-cat-item">
        <div class="zcc-cat-top">
          <strong><?= e($L['cat_ana_t']) ?></strong>
          <label class="zcc-switch" aria-label="<?= e($L['cat_ana_t']) ?>">
            <input type="checkbox" id="zccCatAnalytics"/>
            <span class="zcc-slider"></span>
          </label>
        </div>
        <p><?= e($L['cat_ana_d']) ?></p>
      </div>

      <div class="zcc-cat-item">
        <div class="zcc-cat-top">
          <strong><?= e($L['cat_mkt_t']) ?></strong>
          <label class="zcc-switch" aria-label="<?= e($L['cat_mkt_t']) ?>">
            <input type="checkbox" id="zccCatMarketing"/>
            <span class="zcc-slider"></span>
          </label>
        </div>
        <p><?= e($L['cat_mkt_d']) ?></p>
      </div>
    </div>

    <div class="zcc-modal-foot">
      <a href="<?= e($policyHref) ?>" class="zcc-inline-link"><?= e($L['policy']) ?></a>
      <div class="zcc-modal-btns">
        <button type="button" class="zcc-btn zcc-btn-equal" id="zccModalRejectBtn"><?= e($L['reject']) ?></button>
        <button type="button" class="zcc-btn zcc-btn-manage" id="zccModalSaveBtn"><?= e($L['save']) ?></button>
        <button type="button" class="zcc-btn zcc-btn-equal zcc-btn-accept" id="zccModalAcceptBtn"><?= e($L['accept']) ?></button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  'use strict';
  var STORAGE_KEY = 'zeynart_cookie_consent_v2';
  var cfgNode = document.getElementById('zeynart-consent-cfg');
  var cfg = {};
  try { cfg = cfgNode ? JSON.parse(cfgNode.textContent || '{}') : {}; } catch (_) {}

  var banner = document.getElementById('zeynartCookieBanner');
  var modal = document.getElementById('zeynartCookieModal');
  var chkFun = document.getElementById('zccCatFunctional');
  var chkAna = document.getElementById('zccCatAnalytics');
  var chkMkt = document.getElementById('zccCatMarketing');

  var loadedAna = false;
  var loadedMkt = false;

  function readStoredConsent() {
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      var obj = JSON.parse(raw);
      if (!obj || typeof obj !== 'object' || typeof obj.analytics !== 'boolean') return null;
      if (obj.expiresAt && Date.now() > obj.expiresAt) {
        window.localStorage.removeItem(STORAGE_KEY);
        return null;
      }
      return obj;
    } catch (_) {
      return null;
    }
  }

  function purgeNonEssentialCookies(state) {
    try {
      var pairs = (document.cookie || '').split(';');
      var host = window.location.hostname || '';
      var rootDomain = host.replace(/^www\./i, '');
      var domains = ['', host, '.' + host, rootDomain, '.' + rootDomain];
      pairs.forEach(function(part) {
        var eq = part.indexOf('=');
        var name = (eq > -1 ? part.slice(0, eq) : part).trim();
        if (!name || name === 'zeynart_sid') return;
        var isAna = /^(_ga|_gid|_gat|_ym_|_clck|_clsk)/i.test(name);
        var isMkt = /^(_fbp|_fbc|_gcl_|_ttp|_uetsid|_uetvid)/i.test(name);
        if ((isAna && !state.analytics) || (isMkt && !state.marketing) || (!state.analytics && !state.marketing && !state.functional)) {
          domains.forEach(function(dm) {
            var dAttr = dm ? '; domain=' + dm : '';
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + dAttr;
          });
        }
      });
    } catch (_) {}
  }

  function injectHtmlSnippetOnce(htmlStr, markerAttr) {
    if (!htmlStr || document.querySelector('[' + markerAttr + '="1"]')) return;
    var tpl = document.createElement('div');
    tpl.innerHTML = htmlStr;
    Array.from(tpl.childNodes).forEach(function(node) {
      if (node.nodeType === 1 && node.tagName.toUpperCase() === 'SCRIPT') {
        var s = document.createElement('script');
        s.setAttribute(markerAttr, '1');
        Array.from(node.attributes).forEach(function(a) { s.setAttribute(a.name, a.value); });
        s.textContent = node.textContent;
        document.head.appendChild(s);
      } else if (node.nodeType === 1) {
        node.setAttribute(markerAttr, '1');
        document.head.appendChild(node);
      }
    });
  }

  function activateConsentedScripts(state) {
    if (typeof window.gtag === 'function') {
      window.gtag('consent', 'update', {
        functionality_storage: state.functional ? 'granted' : 'denied',
        personalization_storage: state.functional ? 'granted' : 'denied',
        analytics_storage: state.analytics ? 'granted' : 'denied',
        ad_storage: state.marketing ? 'granted' : 'denied',
        ad_user_data: state.marketing ? 'granted' : 'denied',
        ad_personalization: state.marketing ? 'granted' : 'denied'
      });
    }

    if (state.analytics && !loadedAna) {
      loadedAna = true;
      if (cfg.gaId) {
        var gaScript = document.createElement('script');
        gaScript.async = true;
        gaScript.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.gaId);
        gaScript.setAttribute('data-zcc-ga', '1');
        document.head.appendChild(gaScript);
        window.gtag('js', new Date());
        window.gtag('config', cfg.gaId, { anonymize_ip: true });
      }
      if (cfg.analyticsHtml) {
        injectHtmlSnippetOnce(cfg.analyticsHtml, 'data-zcc-ana-custom');
      }
    }

    if (state.marketing && !loadedMkt) {
      loadedMkt = true;
      if (cfg.metaPixelId && !window.fbq) {
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;t.setAttribute('data-zcc-fbq','1');s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        window.fbq('init', cfg.metaPixelId);
        window.fbq('track', 'PageView');
      }
      if (cfg.marketingHtml) {
        injectHtmlSnippetOnce(cfg.marketingHtml, 'data-zcc-mkt-custom');
      }
    }

    if (!state.analytics || !state.marketing) {
      purgeNonEssentialCookies(state);
    }
  }

  function syncCheckboxes(state) {
    // When no consent has been given yet, ALL optional checkboxes MUST remain unchecked (OFF)
    if (chkFun) chkFun.checked = Boolean(state && state.functional === true);
    if (chkAna) chkAna.checked = Boolean(state && state.analytics === true);
    if (chkMkt) chkMkt.checked = Boolean(state && state.marketing === true);
  }

  function saveConsentChoice(choice) {
    var record = {
      necessary: true,
      functional: Boolean(choice.functional),
      analytics: Boolean(choice.analytics),
      marketing: Boolean(choice.marketing),
      updatedAt: new Date().toISOString(),
      expiresAt: Date.now() + (180 * 24 * 60 * 60 * 1000)
    };
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(record));
    } catch (_) {}
    syncCheckboxes(record);
    if (banner) banner.hidden = true;
    if (modal) modal.hidden = true;
    document.body.classList.remove('zcc-modal-open');
    activateConsentedScripts(record);
  }

  function openPreferencesModal() {
    var existing = readStoredConsent();
    syncCheckboxes(existing);
    if (modal) {
      modal.hidden = false;
      document.body.classList.add('zcc-modal-open');
    }
  }

  function closePreferencesModal() {
    if (modal) {
      modal.hidden = true;
      document.body.classList.remove('zcc-modal-open');
    }
  }

  document.getElementById('zccOpenModalBtn')?.addEventListener('click', openPreferencesModal);
  document.getElementById('zccCloseModalBtn')?.addEventListener('click', closePreferencesModal);
  modal?.addEventListener('click', function(ev) {
    if (ev.target === modal) closePreferencesModal();
  });

  var rejectHandler = function() {
    saveConsentChoice({ functional: false, analytics: false, marketing: false });
  };
  var acceptHandler = function() {
    saveConsentChoice({ functional: true, analytics: true, marketing: true });
  };

  document.getElementById('zccRejectAllBtn')?.addEventListener('click', rejectHandler);
  document.getElementById('zccModalRejectBtn')?.addEventListener('click', rejectHandler);
  document.getElementById('zccAcceptAllBtn')?.addEventListener('click', acceptHandler);
  document.getElementById('zccModalAcceptBtn')?.addEventListener('click', acceptHandler);
  document.getElementById('zccModalSaveBtn')?.addEventListener('click', function() {
    saveConsentChoice({
      functional: Boolean(chkFun && chkFun.checked),
      analytics: Boolean(chkAna && chkAna.checked),
      marketing: Boolean(chkMkt && chkMkt.checked)
    });
  });

  document.addEventListener('click', function(ev) {
    var trigger = ev.target && ev.target.closest ? ev.target.closest('[data-open-cookie-prefs]') : null;
    if (trigger) {
      ev.preventDefault();
      openPreferencesModal();
    }
  });

  document.addEventListener('keydown', function(ev) {
    if (ev.key === 'Escape' && modal && !modal.hidden) {
      closePreferencesModal();
    }
  });

  window.ZeynartCookieConsent = {
    open: openPreferencesModal,
    getState: readStoredConsent,
    reset: function() {
      try { window.localStorage.removeItem(STORAGE_KEY); } catch (_) {}
      purgeNonEssentialCookies({ functional: false, analytics: false, marketing: false });
      syncCheckboxes(null);
      if (banner) banner.hidden = false;
    }
  };

  var initial = readStoredConsent();
  if (initial) {
    syncCheckboxes(initial);
    if (banner) banner.hidden = true;
    activateConsentedScripts(initial);
  } else {
    // Strict Zero-Cookie Load: boxes unchecked, zero analytics/pixels loaded, banner shown
    syncCheckboxes(null);
    if (banner) banner.hidden = false;
  }
})();
</script>
    <?php
    return (string)ob_get_clean();
}
