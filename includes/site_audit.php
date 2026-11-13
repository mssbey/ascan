<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/quick_contact.php';

/**
 * ZEYNART ÜCRETSİZ “SİTE HIZI & SEO ÖN ANALİZ” LEAD ARACI
 * - Gerçek zamanlı, SSRF korumalı HTTP + HTML + SSL + Mobil + Hız + Meta SEO analizi
 * - TR · EN · DE · RU tam yerelleştirilmiş eksik & fırsat raporu
 * - "Zeynart ile sitenizi 99+ performansa taşıyalım" Lead Yakalama & CRM entegrasyonu
 */

function get_site_audit_defaults(): array
{
    return [
        'enabled' => true,
        'show_on_home' => true,
        'show_on_services' => true,
        'eyebrow' => 'ÜCRETSİZ SİTE HIZI & SEO ÖN ANALİZ ARACI',
        'heading_line1' => 'Web sitenizin karnesini',
        'heading_accent' => 'saniyeler içinde görün.',
        'lead' => 'Mevcut web sitenizin adresini girin; SSL güvenliği, sunucu yanıt hızı, mobil uyumluluk ve temel SEO eksiklerini anında analiz edelim.',
        'placeholder' => 'ornekfirma.com veya https://siteniz.com',
        'analyze_button' => 'Ücretsiz Analiz Et',
        'cta_badge' => 'HEDEF: 99+ PERFORMANS & YÜKSEK DÖNÜŞÜM',
        'cta_heading' => 'Zeynart ile sitenizi 99+ performansa taşıyalım',
        'cta_sub' => 'Analizde tespit edilen teknik eksikleri giderip sitenizi hız, mobil deneyim ve arama motorlarında üst sıralara taşıyacak yol haritasını ücretsiz paylaşalım.',
        'cta_button' => 'Ücretsiz İyileştirme Planı Al',
    ];
}

function get_site_audit_state(array $store): array
{
    $defaults = get_site_audit_defaults();
    $raw = is_array($store['site_audit'] ?? null) ? $store['site_audit'] : [];

    $out = [
        'enabled' => array_key_exists('enabled', $raw) ? !empty($raw['enabled']) : $defaults['enabled'],
        'show_on_home' => array_key_exists('show_on_home', $raw) ? !empty($raw['show_on_home']) : $defaults['show_on_home'],
        'show_on_services' => array_key_exists('show_on_services', $raw) ? !empty($raw['show_on_services']) : $defaults['show_on_services'],
    ];

    foreach (['eyebrow', 'heading_line1', 'heading_accent', 'lead', 'placeholder', 'analyze_button', 'cta_badge', 'cta_heading', 'cta_sub', 'cta_button'] as $k) {
        $val = trim((string)($raw[$k] ?? ''));
        $out[$k] = $val !== '' ? $val : $defaults[$k];
    }

    return $out;
}

function zeynart_audit_msg(string $key, string $lang = 'tr', array $vars = []): string
{
    $lang = in_array($lang, ['tr', 'en', 'de', 'ru'], true) ? $lang : 'tr';
    $catalog = [
        'err_empty' => [
            'tr' => 'Lütfen analiz edilecek web sitesi adresini girin (ör. ornekfirma.com).',
            'en' => 'Please enter a website URL to analyze (e.g. example.com).',
            'de' => 'Bitte geben Sie eine Website-Adresse zur Analyse ein (z. B. beispiel.de).',
            'ru' => 'Пожалуйста, введите адрес сайта для анализа (например, example.com).',
        ],
        'err_invalid_url' => [
            'tr' => 'Geçerli bir alan adı veya web adresi girin (ör. sirketiniz.com).',
            'en' => 'Please enter a valid domain name or URL (e.g. yourcompany.com).',
            'de' => 'Bitte geben Sie eine gültige Domain oder URL ein (z. B. ihre-firma.de).',
            'ru' => 'Введите корректный домен или URL-адрес (например, company.com).',
        ],
        'err_private_host' => [
            'tr' => 'Güvenlik nedeniyle yalnızca herkese açık (public) web siteleri analiz edilebilir.',
            'en' => 'For security reasons, only publicly accessible websites can be analyzed.',
            'de' => 'Aus Sicherheitsgründen können nur öffentlich erreichbare Websites analysiert werden.',
            'ru' => 'По соображениям безопасности анализируются только публично доступные сайты.',
        ],
        'err_dns' => [
            'tr' => 'Girilen alan adına ulaşılamadı (DNS kaydı bulunamadı). Lütfen adresi kontrol edin.',
            'en' => 'Could not resolve this domain name (DNS lookup failed). Please check the address.',
            'de' => 'Diese Domain konnte nicht aufgelöst werden (DNS-Fehler). Bitte prüfen Sie die Adresse.',
            'ru' => 'Не удалось найти указанный домен (ошибка DNS). Проверьте правильность адреса.',
        ],
        'err_unreachable' => [
            'tr' => 'Web sitesi yanıt vermedi veya bağlantıyı reddetti. Lütfen adresin aktif olduğundan emin olun.',
            'en' => 'The website did not respond or refused the connection. Please make sure it is online.',
            'de' => 'Die Website antwortet nicht oder lehnt die Verbindung ab. Bitte prüfen Sie die Erreichbarkeit.',
            'ru' => 'Сайт не отвечает или отклонил соединение. Убедитесь, что сайт доступен.',
        ],

        // Findings — SSL & Security
        'ssl_missing_title' => [
            'tr' => 'Aktif veya geçerli SSL (HTTPS) sertifikası bulunamadı',
            'en' => 'No active or valid SSL (HTTPS) certificate detected',
            'de' => 'Kein aktives oder gültiges SSL-(HTTPS-)Zertifikat erkannt',
            'ru' => 'Не обнаружен активный или действительный SSL-сертификат (HTTPS)',
        ],
        'ssl_missing_detail' => [
            'tr' => 'Tarayıcılar sitenizi "Güvenli Değil" olarak işaretler; bu durum Google sıralamasını ve müşteri güvenini doğrudan düşürür.',
            'en' => 'Browsers mark the site as "Not Secure", directly hurting Google rankings and visitor trust.',
            'de' => 'Browser stufen die Seite als „Nicht sicher“ ein – das schadet Rankings und Kundenvertrauen direkt.',
            'ru' => 'Браузеры помечают сайт как «Незащищённый», что снижает позиции в Google и доверие клиентов.',
        ],
        'ssl_ok_title' => [
            'tr' => 'HTTPS ve geçerli SSL sertifikası aktif',
            'en' => 'HTTPS & valid SSL certificate active',
            'de' => 'HTTPS & gültiges SSL-Zertifikat aktiv',
            'ru' => 'HTTPS и действительный SSL-сертификат активны',
        ],
        'hsts_missing_title' => [
            'tr' => 'HSTS (Strict-Transport-Security) güvenlik başlığı eksik',
            'en' => 'Missing HSTS (Strict-Transport-Security) security header',
            'de' => 'Sicherheits-Header HSTS (Strict-Transport-Security) fehlt',
            'ru' => 'Отсутствует заголовок безопасности HSTS (Strict-Transport-Security)',
        ],
        'hsts_missing_detail' => [
            'tr' => 'Tarayıcıları doğrudan güvenli HTTPS bağlantısına zorlayan HSTS politikası tanımlanmamış.',
            'en' => 'HSTS policy forcing browsers to strict HTTPS connections is not configured.',
            'de' => 'Die HSTS-Richtlinie zur Erzwingung sicherer HTTPS-Verbindungen ist nicht aktiv.',
            'ru' => 'Политика HSTS для принудительного использования HTTPS не настроена.',
        ],
        'sec_headers_missing_title' => [
            'tr' => 'Temel tarayıcı güvenlik başlıkları (X-Content-Type / X-Frame) eksik',
            'en' => 'Core browser security headers (X-Content-Type / X-Frame) missing',
            'de' => 'Wichtige Browser-Sicherheitsheader (X-Content-Type / X-Frame) fehlen',
            'ru' => 'Отсутствуют базовые заголовки безопасности (X-Content-Type / X-Frame)',
        ],
        'sec_headers_missing_detail' => [
            'tr' => 'Clickjacking ve MIME-sniffing saldırılarına karşı sunucu başlık korumaları güçlendirilmeli.',
            'en' => 'Server response headers should be hardened against clickjacking and MIME-sniffing.',
            'de' => 'Server-Header sollten gegen Clickjacking und MIME-Sniffing abgesichert werden.',
            'ru' => 'Рекомендуется настроить серверные заголовки защиты от clickjacking и MIME-sniffing.',
        ],

        // Findings — Speed & Performance
        'ttfb_slow_title' => [
            'tr' => 'Sunucu ilk yanıt süresi yüksek ({ms} ms)',
            'en' => 'Slow server initial response time ({ms} ms)',
            'de' => 'Hohe Server-Antwortzeit / TTFB ({ms} ms)',
            'ru' => 'Медленный ответ сервера / TTFB ({ms} мс)',
        ],
        'ttfb_slow_detail' => [
            'tr' => '99+ PageSpeed için sunucu ilk bayt yanıt süresinin (TTFB) 350 ms altında olması hedeflenmelidir.',
            'en' => 'For 99+ PageSpeed, server Time to First Byte (TTFB) should be under 350 ms.',
            'de' => 'Für 99+ PageSpeed sollte die Server-Antwortzeit (TTFB) unter 350 ms liegen.',
            'ru' => 'Для оценки 99+ PageSpeed время ответа сервера (TTFB) должно быть менее 350 мс.',
        ],
        'ttfb_fast_title' => [
            'tr' => 'Sunucu yanıt hızı (TTFB: {ms} ms) ideal seviyede',
            'en' => 'Fast server response time (TTFB: {ms} ms)',
            'de' => 'Schnelle Server-Antwortzeit (TTFB: {ms} ms)',
            'ru' => 'Быстрый отклик сервера (TTFB: {ms} мс)',
        ],
        'compression_missing_title' => [
            'tr' => 'Brotli / Gzip metin sıkıştırması aktif değil',
            'en' => 'Brotli / Gzip text compression is not enabled',
            'de' => 'Brotli- / Gzip-Textkomprimierung ist nicht aktiviert',
            'ru' => 'Сжатие текста Brotli / Gzip не включено',
        ],
        'compression_missing_detail' => [
            'tr' => 'HTML, CSS ve JS dosyaları sıkıştırılmadan gönderiliyor; sayfa açılış hızı %60’a kadar yavaşlayabilir.',
            'en' => 'HTML, CSS, and JS payloads are served uncompressed, slowing load times by up to 60%.',
            'de' => 'HTML, CSS und JS werden unkomprimiert übertragen – das bremst die Ladezeit erheblich.',
            'ru' => 'HTML, CSS и JS передаются без сжатия, что замедляет загрузку страницы до 60%.',
        ],
        'compression_ok_title' => [
            'tr' => 'Sunucu sıkıştırması aktif ({enc})',
            'en' => 'Text compression active ({enc})',
            'de' => 'Textkomprimierung aktiv ({enc})',
            'ru' => 'Сжатие данных активно ({enc})',
        ],
        'render_blocking_title' => [
            'tr' => 'Açılışı yavaşlatan {count} adet bloklayıcı JavaScript dosyası tespit edildi',
            'en' => 'Detected {count} render-blocking JavaScript file(s) in <head>',
            'de' => '{count} render-blockierende JavaScript-Datei(en) im <head> erkannt',
            'ru' => 'Обнаружено блокирующих рендеринг JS-скриптов в <head>: {count}',
        ],
        'render_blocking_detail' => [
            'tr' => 'defer veya async olmadan yüklenen scriptler ilk ekran boyamasını (LCP / FCP) geciktirir.',
            'en' => 'Scripts loaded without defer or async delay First Contentful Paint (FCP) and LCP.',
            'de' => 'Skripte ohne defer/async verzögern das erste sichtbare Rendering (FCP & LCP).',
            'ru' => 'Скрипты без атрибутов defer или async задерживают отрисовку первого экрана (FCP / LCP).',
        ],
        'legacy_images_title' => [
            'tr' => 'Yeni nesil görsel formatları (WebP / AVIF) yeterince kullanılmıyor',
            'en' => 'Limited or no next-gen image formats (WebP / AVIF) detected',
            'de' => 'Moderne Bildformate (WebP / AVIF) werden kaum oder nicht genutzt',
            'ru' => 'Современные форматы изображений (WebP / AVIF) не используются',
        ],
        'legacy_images_detail' => [
            'tr' => 'Klasik JPG/PNG görselleri WebP veya AVIF formatına dönüştürerek görsel boyutlarını %50–75 küçültebilirsiniz.',
            'en' => 'Converting legacy JPG/PNG assets to WebP or AVIF reduces image payload by 50–75%.',
            'de' => 'Durch Umstellung von JPG/PNG auf WebP oder AVIF sinkt die Bildgröße um 50–75 %.',
            'ru' => 'Переход с JPG/PNG на WebP или AVIF уменьшает вес изображений на 50–75%.',
        ],
        'nextgen_images_ok' => [
            'tr' => 'Yeni nesil görsel optimizasyonu (WebP / AVIF) kullanılıyor',
            'en' => 'Next-gen image formats (WebP / AVIF) in use',
            'de' => 'Moderne Bildformate (WebP / AVIF) im Einsatz',
            'ru' => 'Используются современные форматы изображений (WebP / AVIF)',
        ],
        'lazy_missing_title' => [
            'tr' => 'Görsellerde akıllı gecikmeli yükleme (loading="lazy") eksik',
            'en' => 'Below-the-fold images lack native lazy-loading (loading="lazy")',
            'de' => 'Bildern fehlt verzögertes Laden (loading="lazy")',
            'ru' => 'У изображений отсутствует отложенная загрузка (loading="lazy")',
        ],
        'lazy_missing_detail' => [
            'tr' => 'Tüm görsellerin açılışta aynı anda indirilmesi mobil veri tüketimini ve açılış süresini artırır.',
            'en' => 'Downloading all images upfront increases initial bandwidth and delays interactivity on mobile.',
            'de' => 'Das gleichzeitige Laden aller Bilder erhöht das Datenvolumen und verlangsamt den Seitenstart.',
            'ru' => 'Одновременная загрузка всех картинок при входе увеличивает расход трафика и время открытия.',
        ],
        'html_heavy_title' => [
            'tr' => 'HTML doküman boyutu yüksek ({kb} KB)',
            'en' => 'Large initial HTML document payload ({kb} KB)',
            'de' => 'Großes HTML-Dokument ({kb} KB)',
            'ru' => 'Большой размер HTML-документа ({kb} КБ)',
        ],
        'html_heavy_detail' => [
            'tr' => 'Şişkin DOM yapısı ve satır içi kodlar tarayıcı işleme süresini (TBT) uzatır.',
            'en' => 'Bloated DOM markup and inline code increase browser parsing time and Total Blocking Time (TBT).',
            'de' => 'Aufgeblähtes DOM und Inline-Code erhöhen die Browser-Verarbeitungszeit (TBT).',
            'ru' => 'Перегруженный DOM и встроенный код увеличивают время обработки страницы браузером (TBT).',
        ],

        // Findings — Mobile Usability
        'viewport_missing_title' => [
            'tr' => 'Mobil görünüm etiketi (<meta name="viewport">) bulunamadı',
            'en' => 'Mobile viewport tag (<meta name="viewport">) is missing',
            'de' => 'Mobiles Viewport-Meta-Tag (<meta name="viewport">) fehlt',
            'ru' => 'Отсутствует мета-тег мобильной адаптации (<meta name="viewport">)',
        ],
        'viewport_missing_detail' => [
            'tr' => 'Site telefon ekranlarına uyum sağlamaz; Google Mobil Öncelikli İndeksleme’de ciddi sıralama kaybı yaşanır.',
            'en' => 'Pages will not scale properly on smartphones, causing severe Google Mobile-First Indexing penalties.',
            'de' => 'Die Seite skaliert nicht für Smartphones – gravierender Nachteil beim Google Mobile-First-Index.',
            'ru' => 'Страница не адаптируется под смартфоны, что критично для мобильной индексации Google.',
        ],
        'viewport_zoom_locked_title' => [
            'tr' => 'Mobilde yakınlaştırma (pinch-to-zoom) kısıtlanmış',
            'en' => 'Mobile pinch-to-zoom is restricted in viewport tag',
            'de' => 'Zoomen auf Mobilgeräten im Viewport eingeschränkt',
            'ru' => 'Масштабирование на мобильных устройствах заблокировано в viewport',
        ],
        'viewport_zoom_locked_detail' => [
            'tr' => 'user-scalable=no kullanımı Google Lighthouse erişilebilirlik ve mobil deneyim puanını düşürür.',
            'en' => 'Using user-scalable=no lowers Google Lighthouse accessibility and mobile UX scores.',
            'de' => 'user-scalable=no senkt den Google-Lighthouse-Score für Barrierefreiheit und Mobilnutzung.',
            'ru' => 'Параметр user-scalable=no снижает оценку доступности и мобильного UX в Google Lighthouse.',
        ],
        'viewport_ok_title' => [
            'tr' => 'Mobil duyarlı (responsive) viewport yapılandırması doğrulandı',
            'en' => 'Mobile-responsive viewport configuration verified',
            'de' => 'Mobil-responsives Viewport-Tag vorhanden',
            'ru' => 'Мобильный адаптивный тег viewport настроен корректно',
        ],
        'touch_icon_missing_title' => [
            'tr' => 'Mobil uygulama / sekme ikonu (Favicon / Apple Touch Icon) eksik',
            'en' => 'Missing Favicon or Apple Touch Icon declaration',
            'de' => 'Favicon oder Apple-Touch-Icon fehlt im HTML',
            'ru' => 'Не указан Favicon или Apple Touch Icon',
        ],
        'touch_icon_missing_detail' => [
            'tr' => 'Google mobil arama sonuçlarında ve telefon ekranlarında marka logonuzun net görünmesi için tanımlanmalıdır.',
            'en' => 'Required for crisp brand icon display in Google mobile search results and iOS/Android bookmarks.',
            'de' => 'Wichtig für die saubere Darstellung Ihres Marken-Icons in der mobilen Google-Suche.',
            'ru' => 'Необходим для отображения значка вашего бренда в мобильной выдаче Google и закладках.',
        ],

        // Findings — SEO & AI Visibility
        'title_missing_title' => [
            'tr' => 'Sayfa başlığı (<title>) etiketi bulunamadı',
            'en' => 'Missing page <title> tag',
            'de' => 'Seitentitel (<title>) fehlt',
            'ru' => 'Отсутствует тег заголовка страницы (<title>)',
        ],
        'title_missing_detail' => [
            'tr' => 'Arama motorları için en kritik temel SEO sinyalidir; her sayfada özgün bir başlık bulunmalıdır.',
            'en' => 'The <title> tag is the primary ranking and click-through signal on Google search.',
            'de' => 'Das <title>-Tag ist das wichtigste SEO-Signal für Suchmaschinen und Klickrate.',
            'ru' => 'Тег <title> — важнейший фактор ранжирования и кликабельности в поисковой выдаче.',
        ],
        'title_len_title' => [
            'tr' => 'Sayfa başlığı uzunluğu optimize edilmeli ({len} karakter)',
            'en' => 'Page <title> length needs optimization ({len} characters)',
            'de' => 'Länge des Seitentitels optimierbar ({len} Zeichen)',
            'ru' => 'Длина тега <title> требует оптимизации ({len} симв.)',
        ],
        'title_len_detail' => [
            'tr' => 'Google arama sonuçlarında kesilmemesi ve güçlü tıklama oranı için 30–65 karakter aralığı önerilir.',
            'en' => 'Ideal <title> length is 30–65 characters for maximum visibility in Google SERPs.',
            'de' => 'Für optimale Darstellung bei Google werden 30–65 Zeichen empfohlen.',
            'ru' => 'Оптимальная длина заголовка для поисковой выдачи Google составляет 30–65 символов.',
        ],
        'title_ok_title' => [
            'tr' => 'Sayfa başlığı (<title>) ideal uzunlukta ({len} karakter)',
            'en' => 'Optimal page <title> length ({len} characters)',
            'de' => 'Ideale Länge des Seitentitels ({len} Zeichen)',
            'ru' => 'Оптимальная длина тега <title> ({len} симв.)',
        ],
        'desc_missing_title' => [
            'tr' => 'Meta açıklama (<meta name="description">) etiketi eksik',
            'en' => 'Missing <meta name="description"> tag',
            'de' => 'Meta-Beschreibung (<meta name="description">) fehlt',
            'ru' => 'Отсутствует мета-описание (<meta name="description">)',
        ],
        'desc_missing_detail' => [
            'tr' => 'Arama sonuçlarında potansiyel müşteriyi tıklamaya ikna eden özet açıklama tanımlanmamış.',
            'en' => 'No search snippet summary defined to attract clicks from prospective customers.',
            'de' => 'Es ist keine überzeugende Suchmaschinen-Beschreibung für potenzielle Kunden hinterlegt.',
            'ru' => 'Не задано описание страницы для привлечения кликов из поисковой выдачи.',
        ],
        'desc_len_title' => [
            'tr' => 'Meta açıklama uzunluğu ideal aralık dışında ({len} karakter)',
            'en' => 'Meta description length outside optimal range ({len} characters)',
            'de' => 'Länge der Meta-Beschreibung außerhalb des Idealbereichs ({len} Zeichen)',
            'ru' => 'Длина мета-описания вне оптимального диапазона ({len} симв.)',
        ],
        'desc_len_detail' => [
            'tr' => 'En yüksek organik tıklama oranı (CTR) için 70–165 karakter arasında net değer önerisi yazılmalıdır.',
            'en' => 'Aim for 70–165 characters with a clear value proposition to maximize organic CTR.',
            'de' => 'Für eine hohe Klickrate (CTR) sind 70–165 Zeichen mit klarem Nutzenversprechen ideal.',
            'ru' => 'Для высокого CTR рекомендуется 70–165 символов с чётким ценностным предложением.',
        ],
        'desc_ok_title' => [
            'tr' => 'Meta açıklama etiketi optimize edilmiş ({len} karakter)',
            'en' => 'Meta description properly optimized ({len} characters)',
            'de' => 'Meta-Beschreibung optimal gepflegt ({len} Zeichen)',
            'ru' => 'Мета-описание оптимизировано ({len} симв.)',
        ],
        'h1_missing_title' => [
            'tr' => 'Ana başlık (<h1>) etiketi bulunamadı',
            'en' => 'No primary <h1> heading found on the page',
            'de' => 'Keine Hauptüberschrift (<h1>) auf der Seite gefunden',
            'ru' => 'На странице отсутствует главный заголовок (<h1>)',
        ],
        'h1_missing_detail' => [
            'tr' => 'Google’ın sayfanın ana konusunu ve hedef anahtar kelimesini anlaması için net bir <h1> başlığı şarttır.',
            'en' => 'A clear <h1> heading is essential for search engines to understand your primary topic.',
            'de' => 'Eine klare <h1>-Überschrift hilft Google, das Hauptthema Ihrer Seite sofort zu erfassen.',
            'ru' => 'Чёткий заголовок <h1> необходим поисковым системам для понимания основной темы страницы.',
        ],
        'h1_multi_title' => [
            'tr' => 'Sayfada birden fazla ({count} adet) <h1> etiketi kullanılmış',
            'en' => 'Multiple <h1> headings ({count}) detected on the same page',
            'de' => 'Mehrere <h1>-Überschriften ({count}) auf derselben Seite erkannt',
            'ru' => 'На странице обнаружено несколько заголовков <h1> ({count} шт.)',
        ],
        'h1_multi_detail' => [
            'tr' => 'Semantik başlık hiyerarşisi için sayfada tek bir ana <h1> ve altında <h2>/<h3> yapısı önerilir.',
            'en' => 'Use a single primary <h1> supported by structured <h2>/<h3> subheadings for clean hierarchy.',
            'de' => 'Für eine saubere SEO-Struktur wird genau eine <h1> mit untergeordneten <h2>/<h3> empfohlen.',
            'ru' => 'Для чистой SEO-иерархии рекомендуется использовать один заголовок <h1> и подзаголовки <h2>/<h3>.',
        ],
        'h1_ok_title' => [
            'tr' => 'Tekil ve net <h1> başlık hiyerarşisi mevcut',
            'en' => 'Clean single <h1> heading structure verified',
            'de' => 'Saubere einzelne <h1>-Überschrift vorhanden',
            'ru' => 'Корректная структура с единственным заголовком <h1>',
        ],
        'canonical_missing_title' => [
            'tr' => 'Canonical (<link rel="canonical">) etiketi tanımlanmamış',
            'en' => 'Missing <link rel="canonical"> URL declaration',
            'de' => 'Canonical-Tag (<link rel="canonical">) fehlt',
            'ru' => 'Отсутствует канонический тег (<link rel="canonical">)',
        ],
        'canonical_missing_detail' => [
            'tr' => 'Kopya içerik (duplicate content) sorunlarını önlemek için her sayfada canonical adres belirtilmelidir.',
            'en' => 'Canonical tags prevent duplicate URL indexing issues in Google Search Console.',
            'de' => 'Canonical-Tags verhindern Duplicate-Content-Probleme in der Google-Indexierung.',
            'ru' => 'Канонический URL защищает сайт от дублей страниц при индексации в Google.',
        ],
        'img_alt_missing_title' => [
            'tr' => '{count} görselde alternatif metin (alt etiketi) eksik',
            'en' => '{count} image(s) are missing descriptive alt attributes',
            'de' => 'Bei {count} Bild(ern) fehlt das alt-Attribut',
            'ru' => 'У {count} изображений отсутствует атрибут alt',
        ],
        'img_alt_missing_detail' => [
            'tr' => 'Görsel SEO ve erişilebilirlik standartları için görsellere açıklayıcı alt metinler eklenmelidir.',
            'en' => 'Descriptive alt attributes boost Google Images visibility and web accessibility.',
            'de' => 'Beschreibende Alt-Texte verbessern die Sichtbarkeit in der Google-Bildersuche und Barrierefreiheit.',
            'ru' => 'Описательные атрибуты alt повышают видимость в Google Картинках и доступность сайта.',
        ],
        'og_missing_title' => [
            'tr' => 'Sosyal medya önizleme kartları (OpenGraph og:title / og:image) eksik',
            'en' => 'Incomplete OpenGraph social preview tags (og:title / og:image)',
            'de' => 'Unvollständige OpenGraph-Vorschau-Tags (og:title / og:image)',
            'ru' => 'Не настроены теги превью для соцсетей и мессенджеров (og:title / og:image)',
        ],
        'og_missing_detail' => [
            'tr' => 'WhatsApp, LinkedIn ve sosyal medyada link paylaşıldığında profesyonel görsel kart çıkmaz.',
            'en' => 'Links shared on WhatsApp, LinkedIn, or X will lack rich branded preview cards.',
            'de' => 'Beim Teilen auf WhatsApp oder LinkedIn erscheint ohne OpenGraph keine ansprechende Vorschaukarte.',
            'ru' => 'При отправке ссылки в WhatsApp, Telegram или соцсетях не отображается красивая карточка сайта.',
        ],
        'schema_missing_title' => [
            'tr' => 'Yapısal veri (Schema.org JSON-LD) ve Yapay Zeka SEO işaretlemesi bulunamadı',
            'en' => 'No Schema.org JSON-LD structured data for Google & AI Search',
            'de' => 'Keine strukturierten Daten (Schema.org JSON-LD) für Google & KI-Suche gefunden',
            'ru' => 'Не найдена микроразметка Schema.org (JSON-LD) для Google и ИИ-поиска',
        ],
        'schema_missing_detail' => [
            'tr' => 'Google zengin sonuçları ile ChatGPT, Gemini ve Perplexity görünürlüğü için JSON-LD şeması büyük avantaj sağlar.',
            'en' => 'JSON-LD structured data significantly improves visibility in Google Rich Results, ChatGPT, and Gemini.',
            'de' => 'JSON-LD steigert die Sichtbarkeit in Google Rich Snippets sowie in ChatGPT, Gemini und Perplexity.',
            'ru' => 'Разметка JSON-LD значительно повышает видимость бизнеса в Google, ChatGPT, Gemini и Perplexity.',
        ],
        'schema_ok_title' => [
            'tr' => 'Schema.org (JSON-LD) yapısal veri işaretlemesi aktif',
            'en' => 'Schema.org (JSON-LD) structured data active',
            'de' => 'Schema.org (JSON-LD) strukturierte Daten aktiv',
            'ru' => 'Микроразметка Schema.org (JSON-LD) активна',
        ],
    ];

    $tpl = $catalog[$key][$lang] ?? $catalog[$key]['tr'] ?? $key;
    foreach ($vars as $vk => $vv) {
        $tpl = str_replace('{' . $vk . '}', (string)$vv, $tpl);
    }
    return $tpl;
}

function zeynart_resolve_safe_public_ip(string $host, string $lang = 'tr'): array
{
    $cleanHost = strtolower(trim($host, '. '));
    if ($cleanHost === '' || strlen($cleanHost) > 253) {
        return ['ok' => false, 'error' => zeynart_audit_msg('err_invalid_url', $lang)];
    }

    if (
        $cleanHost === 'localhost'
        || str_ends_with($cleanHost, '.localhost')
        || str_ends_with($cleanHost, '.local')
        || str_ends_with($cleanHost, '.internal')
        || str_ends_with($cleanHost, '.lan')
    ) {
        return ['ok' => false, 'error' => zeynart_audit_msg('err_private_host', $lang)];
    }

    // Convert IDN domain if intl is available
    $asciiHost = $cleanHost;
    if (preg_match('/[^\x20-\x7E]/', $cleanHost) && function_exists('idn_to_ascii')) {
        $converted = idn_to_ascii($cleanHost, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if (is_string($converted) && $converted !== '') {
            $asciiHost = strtolower($converted);
        }
    }

    if (filter_var($asciiHost, FILTER_VALIDATE_IP)) {
        if (filter_var($asciiHost, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['ok' => false, 'error' => zeynart_audit_msg('err_private_host', $lang)];
        }
        return ['ok' => true, 'host' => $asciiHost, 'ip' => $asciiHost];
    }

    if (!str_contains($asciiHost, '.') || !preg_match('/^[a-z0-9.-]+$/', $asciiHost)) {
        return ['ok' => false, 'error' => zeynart_audit_msg('err_invalid_url', $lang)];
    }

    $ips = @gethostbynamel($asciiHost);
    if (!is_array($ips) || $ips === []) {
        return ['ok' => false, 'error' => zeynart_audit_msg('err_dns', $lang)];
    }

    $safeIp = '';
    foreach ($ips as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['ok' => false, 'error' => zeynart_audit_msg('err_private_host', $lang)];
        }
        if ($safeIp === '') {
            $safeIp = $ip;
        }
    }

    return ['ok' => true, 'host' => $asciiHost, 'ip' => $safeIp];
}

function zeynart_fetch_audit_url_once(string $startUrl, string $lang = 'tr', bool $verifySsl = true): array
{
    $currentUrl = $startUrl;
    $maxRedirects = 4;
    $redirectCount = 0;

    for ($hop = 0; $hop <= $maxRedirects; $hop++) {
        $parts = @parse_url($currentUrl);
        if (!is_array($parts) || empty($parts['host'])) {
            return ['ok' => false, 'error' => zeynart_audit_msg('err_invalid_url', $lang)];
        }

        $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['ok' => false, 'error' => zeynart_audit_msg('err_invalid_url', $lang)];
        }

        $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443], true)) {
            return ['ok' => false, 'error' => zeynart_audit_msg('err_private_host', $lang)];
        }

        $dns = zeynart_resolve_safe_public_ip((string)$parts['host'], $lang);
        if (empty($dns['ok'])) {
            return $dns;
        }
        $asciiHost = (string)$dns['host'];
        $pinnedIp = (string)$dns['ip'];

        $path = (string)($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }
        $query = isset($parts['query']) && $parts['query'] !== '' ? ('?' . $parts['query']) : '';
        $normalizedHopUrl = $scheme . '://' . $asciiHost . $path . $query;

        $respHeaders = [];
        $bodyBuf = '';
        $maxBytes = 1200000; // 1.2 MB safety cap
        $httpCode = 0;
        $ttfbSec = 0.0;
        $totalSec = 0.0;
        $dlSize = 0;
        $sslCertValid = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($normalizedHopUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT_MS => 3000,
                CURLOPT_TIMEOUT_MS => 6500,
                CURLOPT_SSL_VERIFYPEER => $verifySsl,
                CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
                CURLOPT_ENCODING => '', // Requests br, gzip, deflate & auto-decodes body while preserving Content-Encoding header
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ZeynartSiteAnalyzer/2.0; +https://zeynart.com/site-analiz/)',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Cache-Control: no-cache',
                ],
                CURLOPT_RESOLVE => [$asciiHost . ':' . $port . ':' . $pinnedIp],
                CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$respHeaders): int {
                    $len = strlen($headerLine);
                    $pos = strpos($headerLine, ':');
                    if ($pos !== false) {
                        $hName = strtolower(trim(substr($headerLine, 0, $pos)));
                        $hVal = trim(substr($headerLine, $pos + 1));
                        if ($hName !== '') {
                            $respHeaders[$hName] = isset($respHeaders[$hName])
                                ? ($respHeaders[$hName] . ', ' . $hVal)
                                : $hVal;
                        }
                    } elseif (str_starts_with(strtoupper(trim($headerLine)), 'HTTP/')) {
                        $respHeaders = [];
                    }
                    return $len;
                },
                CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$bodyBuf, $maxBytes): int {
                    $remaining = $maxBytes - strlen($bodyBuf);
                    if ($remaining <= 0) {
                        return 0;
                    }
                    $bodyBuf .= substr($chunk, 0, $remaining);
                    return strlen($chunk);
                },
            ]);

            $execOk = curl_exec($ch);
            $curlErrno = curl_errno($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ttfbSec = (float)curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME);
            $totalSec = (float)curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $dlSize = (int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            unset($ch);

            if (!$execOk && $curlErrno !== 23 && $httpCode === 0) {
                return [
                    'ok' => false,
                    'curl_errno' => $curlErrno,
                    'ssl_error' => in_array($curlErrno, [35, 51, 58, 60, 77, 83], true),
                    'error' => zeynart_audit_msg('err_unreachable', $lang),
                ];
            }
            $sslCertValid = ($scheme === 'https' && $verifySsl);
        } else {
            // Native OpenSSL + TCP Stream Socket Fallback (works even when ext-curl is disabled!)
            $hasCaBundle = false;
            if (function_exists('openssl_get_cert_locations')) {
                $locs = openssl_get_cert_locations();
                $caFile = (string)($locs['ini_cafile'] ?: ($locs['default_cert_file'] ?? ''));
                $caDir = (string)($locs['ini_capath'] ?: ($locs['default_cert_dir'] ?? ''));
                $hasCaBundle = ($caFile !== '' && @is_file($caFile)) || ($caDir !== '' && @is_dir($caDir));
            }

            $sslOpts = [
                'peer_name' => $asciiHost,
                'SNI_enabled' => true,
                'capture_peer_cert' => true,
                'verify_peer' => ($verifySsl && $hasCaBundle),
                'verify_peer_name' => ($verifySsl && $hasCaBundle),
            ];
            $ctx = stream_context_create(['ssl' => $sslOpts]);
            $remote = ($scheme === 'https' ? 'tls://' : 'tcp://') . $pinnedIp . ':' . $port;

            $tStart = microtime(true);
            $errNo = 0;
            $errStr = '';
            $fp = @stream_socket_client($remote, $errNo, $errStr, 3.5, STREAM_CLIENT_CONNECT, $ctx);
            if (!is_resource($fp)) {
                return [
                    'ok' => false,
                    'ssl_error' => ($scheme === 'https'),
                    'error' => zeynart_audit_msg('err_unreachable', $lang),
                ];
            }

            stream_set_timeout($fp, 6);
            if ($scheme === 'https') {
                $params = stream_context_get_params($ctx);
                $peerCert = $params['options']['ssl']['peer_certificate'] ?? null;
                if ($peerCert !== null && function_exists('openssl_x509_parse')) {
                    $certData = @openssl_x509_parse($peerCert);
                    $nowTs = time();
                    if (
                        is_array($certData)
                        && (int)($certData['validFrom_time_t'] ?? 0) <= ($nowTs + 300)
                        && (int)($certData['validTo_time_t'] ?? 0) > $nowTs
                        && ($certData['issuer'] ?? null) !== ($certData['subject'] ?? null)
                    ) {
                        $sslCertValid = true;
                    }
                }
            }

            $reqTarget = $path . $query;
            $rawReq = "GET {$reqTarget} HTTP/1.1\r\n"
                . "Host: {$asciiHost}\r\n"
                . "User-Agent: Mozilla/5.0 (compatible; ZeynartSiteAnalyzer/2.0; +https://zeynart.com/site-analiz/)\r\n"
                . "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
                . "Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7\r\n"
                . "Accept-Encoding: gzip, deflate\r\n"
                . "Connection: close\r\n\r\n";

            @fwrite($fp, $reqReqBuf = $rawReq);

            $statusLine = @fgets($fp, 4096);
            $ttfbSec = microtime(true) - $tStart;
            if (!is_string($statusLine) || !preg_match('~^HTTP/\S+\s+(\d+)~i', trim($statusLine), $stm)) {
                @fclose($fp);
                return [
                    'ok' => false,
                    'error' => zeynart_audit_msg('err_unreachable', $lang),
                ];
            }
            $httpCode = (int)$stm[1];

            while (!feof($fp)) {
                $line = @fgets($fp, 8192);
                if (!is_string($line) || $line === "\r\n" || $line === "\n" || $line === '') {
                    break;
                }
                $pos = strpos($line, ':');
                if ($pos !== false) {
                    $hName = strtolower(trim(substr($line, 0, $pos)));
                    $hVal = trim(substr($line, $pos + 1));
                    if ($hName !== '') {
                        $respHeaders[$hName] = isset($respHeaders[$hName])
                            ? ($respHeaders[$hName] . ', ' . $hVal)
                            : $hVal;
                    }
                }
            }

            $rawBody = '';
            while (!feof($fp) && strlen($rawBody) < $maxBytes) {
                $chunk = @fread($fp, min(16384, $maxBytes - strlen($rawBody)));
                if (!is_string($chunk) || $chunk === '') {
                    break;
                }
                $rawBody .= $chunk;
            }
            @fclose($fp);
            $totalSec = microtime(true) - $tStart;
            $dlSize = strlen($rawBody);

            // Decode Transfer-Encoding: chunked if present
            $te = strtolower((string)($respHeaders['transfer-encoding'] ?? ''));
            if (str_contains($te, 'chunked') && $rawBody !== '') {
                $unchunked = '';
                $offset = 0;
                $rawLen = strlen($rawBody);
                while ($offset < $rawLen) {
                    $rnPos = strpos($rawBody, "\r\n", $offset);
                    if ($rnPos === false) {
                        break;
                    }
                    $hexLine = trim(substr($rawBody, $offset, $rnPos - $offset));
                    $semi = strpos($hexLine, ';');
                    if ($semi !== false) {
                        $hexLine = substr($hexLine, 0, $semi);
                    }
                    if ($hexLine === '' || !ctype_xdigit($hexLine)) {
                        break;
                    }
                    $chunkLen = (int)hexdec($hexLine);
                    $offset = $rnPos + 2;
                    if ($chunkLen === 0) {
                        break;
                    }
                    $unchunked .= substr($rawBody, $offset, $chunkLen);
                    $offset += $chunkLen + 2;
                }
                if ($unchunked !== '') {
                    $rawBody = $unchunked;
                }
            }

            // Decompress gzip / deflate if server compressed response
            $ce = strtolower((string)($respHeaders['content-encoding'] ?? ''));
            if (str_contains($ce, 'gzip') && function_exists('gzdecode') && $rawBody !== '') {
                $decodedGz = @gzdecode($rawBody);
                if (is_string($decodedGz)) {
                    $rawBody = $decodedGz;
                }
            } elseif (str_contains($ce, 'deflate') && function_exists('gzinflate') && $rawBody !== '') {
                $decodedDef = @gzinflate($rawBody);
                if (!is_string($decodedDef) && function_exists('gzuncompress')) {
                    $decodedDef = @gzuncompress($rawBody);
                }
                if (is_string($decodedDef)) {
                    $rawBody = $decodedDef;
                }
            }
            $bodyBuf = substr($rawBody, 0, $maxBytes);
        }

        if (in_array($httpCode, [301, 302, 303, 307, 308], true) && !empty($respHeaders['location'])) {
            $loc = trim((string)explode(',', $respHeaders['location'])[0]);
            if (str_starts_with($loc, '//')) {
                $currentUrl = $scheme . ':' . $loc;
            } elseif (preg_match('~^https?://~i', $loc)) {
                $currentUrl = $loc;
            } elseif (str_starts_with($loc, '/')) {
                $currentUrl = $scheme . '://' . $asciiHost . $loc;
            } else {
                $currentUrl = $scheme . '://' . $asciiHost . '/' . ltrim($loc, '/');
            }
            $redirectCount++;
            continue;
        }

        return [
            'ok' => true,
            'final_url' => $normalizedHopUrl,
            'scheme' => $scheme,
            'host' => $asciiHost,
            'http_code' => $httpCode,
            'redirects' => $redirectCount,
            'ttfb_ms' => max(12, (int)round($ttfbSec * 1000)),
            'total_ms' => max(18, (int)round($totalSec * 1000)),
            'download_bytes' => $dlSize > 0 ? $dlSize : strlen($bodyBuf),
            'html_bytes' => strlen($bodyBuf),
            'headers' => $respHeaders,
            'body' => $bodyBuf,
            'ssl_valid' => $sslCertValid,
        ];
    }

    return ['ok' => false, 'error' => zeynart_audit_msg('err_unreachable', $lang)];
}

function zeynart_audit_secret(array $store = []): string
{
    $seed = (string)($store['admin']['password_hash'] ?? 'zeynart-audit-v2') . '|' . ROOT_DIR;
    return hash('sha256', $seed);
}

function zeynart_sign_audit_token(array $summary, array $store = []): string
{
    $json = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, zeynart_audit_secret($store));
    return $b64 . '.' . substr($sig, 0, 32);
}

function zeynart_verify_audit_token(string $token, array $store = []): ?array
{
    $parts = explode('.', trim($token), 2);
    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
        return null;
    }
    $expected = substr(hash_hmac('sha256', $parts[0], zeynart_audit_secret($store)), 0, 32);
    if (!hash_equals($expected, $parts[1])) {
        return null;
    }
    $raw = base64_decode(strtr($parts[0], '-_', '+/'), true);
    if (!is_string($raw)) {
        return null;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function zeynart_run_site_audit(string $rawInput, string $lang = 'tr', array $store = []): array
{
    $lang = in_array($lang, ['tr', 'en', 'de', 'ru'], true) ? $lang : 'tr';
    $trimmed = trim($rawInput);
    if ($trimmed === '') {
        return ['ok' => false, 'error' => zeynart_audit_msg('err_empty', $lang)];
    }

    $hasExplicitScheme = (bool)preg_match('~^https?://~i', $trimmed);
    $candidateUrl = $hasExplicitScheme ? $trimmed : ('https://' . ltrim($trimmed, '/'));

    $parsedInit = @parse_url($candidateUrl);
    if (!is_array($parsedInit) || empty($parsedInit['host'])) {
        return ['ok' => false, 'error' => zeynart_audit_msg('err_invalid_url', $lang)];
    }

    // Pre-validate SSRF/DNS before any network socket
    $dnsCheck = zeynart_resolve_safe_public_ip((string)$parsedInit['host'], $lang);
    if (empty($dnsCheck['ok'])) {
        return $dnsCheck;
    }

    $sslBrokenFallback = false;
    $fetch = zeynart_fetch_audit_url_once($candidateUrl, $lang, true);
    if (empty($fetch['ok'])) {
        // If HTTPS failed (e.g. expired/self-signed SSL or port 443 closed), test HTTP or unverified TLS so we can still audit & report SSL issue!
        if (!empty($fetch['ssl_error'])) {
            $sslBrokenFallback = true;
            $fetch = zeynart_fetch_audit_url_once($candidateUrl, $lang, false);
        }
        if (empty($fetch['ok']) && !$hasExplicitScheme) {
            $sslBrokenFallback = true;
            $httpCandidate = 'http://' . ltrim($trimmed, '/');
            $fetch = zeynart_fetch_audit_url_once($httpCandidate, $lang, false);
        }
    }

    if (empty($fetch['ok'])) {
        return ['ok' => false, 'error' => (string)($fetch['error'] ?? zeynart_audit_msg('err_unreachable', $lang))];
    }

    $finalUrl = (string)$fetch['final_url'];
    $host = (string)$fetch['host'];
    $html = (string)$fetch['body'];
    $headers = is_array($fetch['headers']) ? $fetch['headers'] : [];
    $ttfbMs = (int)$fetch['ttfb_ms'];
    $htmlKb = round(((int)$fetch['html_bytes']) / 1024, 1);
    $sslValid = !empty($fetch['ssl_valid']) && !$sslBrokenFallback && ($fetch['scheme'] === 'https');

    $issues = [];
    $passed = [];

    // =========================================================================
    // 1. SSL & GÜVENLİK KONTROLÜ
    // =========================================================================
    $sslScore = 0;
    if ($sslValid) {
        $sslScore += 65;
        $passed[] = [
            'category' => 'SSL',
            'title' => zeynart_audit_msg('ssl_ok_title', $lang),
        ];
    } else {
        $issues[] = [
            'severity' => 'critical',
            'category' => 'SSL',
            'title' => zeynart_audit_msg('ssl_missing_title', $lang),
            'detail' => zeynart_audit_msg('ssl_missing_detail', $lang),
        ];
    }

    if (!empty($headers['strict-transport-security'])) {
        $sslScore += 18;
    } else {
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'SSL',
            'title' => zeynart_audit_msg('hsts_missing_title', $lang),
            'detail' => zeynart_audit_msg('hsts_missing_detail', $lang),
        ];
    }

    $hasSecHeaders = !empty($headers['x-content-type-options'])
        || !empty($headers['x-frame-options'])
        || !empty($headers['content-security-policy']);
    if ($hasSecHeaders) {
        $sslScore += 17;
    } else {
        $issues[] = [
            'severity' => 'warning',
            'category' => 'SSL',
            'title' => zeynart_audit_msg('sec_headers_missing_title', $lang),
            'detail' => zeynart_audit_msg('sec_headers_missing_detail', $lang),
        ];
    }

    // =========================================================================
    // 2. SUNUCU HIZI & PERFORMANS KONTROLÜ
    // =========================================================================
    $speedScore = 0;
    if ($ttfbMs <= 380) {
        $speedScore += 32;
        $passed[] = [
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('ttfb_fast_title', $lang, ['ms' => $ttfbMs]),
        ];
    } elseif ($ttfbMs <= 750) {
        $speedScore += 22;
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('ttfb_slow_title', $lang, ['ms' => $ttfbMs]),
            'detail' => zeynart_audit_msg('ttfb_slow_detail', $lang),
        ];
    } elseif ($ttfbMs <= 1300) {
        $speedScore += 12;
        $issues[] = [
            'severity' => 'warning',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('ttfb_slow_title', $lang, ['ms' => $ttfbMs]),
            'detail' => zeynart_audit_msg('ttfb_slow_detail', $lang),
        ];
    } else {
        $speedScore += 4;
        $issues[] = [
            'severity' => 'critical',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('ttfb_slow_title', $lang, ['ms' => $ttfbMs]),
            'detail' => zeynart_audit_msg('ttfb_slow_detail', $lang),
        ];
    }

    $enc = strtolower(trim((string)($headers['content-encoding'] ?? '')));
    if ($enc !== '' && (str_contains($enc, 'br') || str_contains($enc, 'gzip') || str_contains($enc, 'deflate') || str_contains($enc, 'zstd'))) {
        $speedScore += 20;
        $passed[] = [
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('compression_ok_title', $lang, ['enc' => strtoupper($enc)]),
        ];
    } else {
        $issues[] = [
            'severity' => 'warning',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('compression_missing_title', $lang),
            'detail' => zeynart_audit_msg('compression_missing_detail', $lang),
        ];
    }

    if ($htmlKb <= 140) {
        $speedScore += 14;
    } elseif ($htmlKb <= 280) {
        $speedScore += 8;
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('html_heavy_title', $lang, ['kb' => $htmlKb]),
            'detail' => zeynart_audit_msg('html_heavy_detail', $lang),
        ];
    } else {
        $speedScore += 2;
        $issues[] = [
            'severity' => 'warning',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('html_heavy_title', $lang, ['kb' => $htmlKb]),
            'detail' => zeynart_audit_msg('html_heavy_detail', $lang),
        ];
    }

    // Parse HTML safely via DOMDocument / regex hybrid
    $headHtml = '';
    if (preg_match('~<head\b[^>]*>(.*?)</head>~isu', $html, $hm)) {
        $headHtml = $hm[1];
    }
    $blockingScripts = 0;
    if ($headHtml !== '' && preg_match_all('~<script\b([^>]*)>~iu', $headHtml, $sm)) {
        foreach ($sm[1] as $sAttrs) {
            if (
                preg_match('~\bsrc\s*=~i', $sAttrs)
                && !preg_match('~\b(defer|async)\b~i', $sAttrs)
                && !preg_match('~\btype\s*=\s*["\']?(module|application/ld\+json)["\']?~i', $sAttrs)
            ) {
                $blockingScripts++;
            }
        }
    }

    if ($blockingScripts === 0) {
        $speedScore += 16;
    } elseif ($blockingScripts <= 2) {
        $speedScore += 9;
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('render_blocking_title', $lang, ['count' => $blockingScripts]),
            'detail' => zeynart_audit_msg('render_blocking_detail', $lang),
        ];
    } else {
        $issues[] = [
            'severity' => 'warning',
            'category' => 'HIZ',
            'title' => zeynart_audit_msg('render_blocking_title', $lang, ['count' => $blockingScripts]),
            'detail' => zeynart_audit_msg('render_blocking_detail', $lang),
        ];
    }

    // Inspect images
    $totalImgs = 0;
    $nextGenImgs = 0;
    $lazyImgs = 0;
    $missingAltImgs = 0;
    $responsiveImgs = 0;

    if (preg_match_all('~<img\b([^>]*)>~iu', $html, $im)) {
        foreach ($im[1] as $iAttrs) {
            $totalImgs++;
            if (preg_match('~\.(webp|avif|svg)(\?|["\'\s>]|$)~i', $iAttrs)) {
                $nextGenImgs++;
            }
            if (preg_match('~\bloading\s*=\s*["\']?lazy~i', $iAttrs) || preg_match('~\bfetchpriority\s*=\s*["\']?high~i', $iAttrs)) {
                $lazyImgs++;
            }
            if (preg_match('~\b(srcset|sizes)\s*=~i', $iAttrs)) {
                $responsiveImgs++;
            }
            if (!preg_match('~\balt\s*=~i', $iAttrs)) {
                $missingAltImgs++;
            }
        }
    }
    $hasPictureTag = (bool)preg_match('~<picture\b~i', $html);

    if ($totalImgs === 0) {
        $speedScore += 18;
    } else {
        $nextGenRatio = $nextGenImgs / max(1, $totalImgs);
        if ($nextGenRatio >= 0.5 || $hasPictureTag) {
            $speedScore += 11;
            $passed[] = [
                'category' => 'HIZ',
                'title' => zeynart_audit_msg('nextgen_images_ok', $lang),
            ];
        } else {
            $speedScore += 3;
            $issues[] = [
                'severity' => 'warning',
                'category' => 'HIZ',
                'title' => zeynart_audit_msg('legacy_images_title', $lang),
                'detail' => zeynart_audit_msg('legacy_images_detail', $lang),
            ];
        }

        if ($totalImgs <= 2 || $lazyImgs > 0) {
            $speedScore += 7;
        } else {
            $issues[] = [
                'severity' => 'opportunity',
                'category' => 'HIZ',
                'title' => zeynart_audit_msg('lazy_missing_title', $lang),
                'detail' => zeynart_audit_msg('lazy_missing_detail', $lang),
            ];
        }
    }

    // =========================================================================
    // 3. MOBİL UYUMLULUK KONTROLÜ
    // =========================================================================
    $mobileScore = 0;
    $viewportContent = '';
    if (preg_match('~<meta\b[^>]*name\s*=\s*["\']viewport["\'][^>]*content\s*=\s*["\']([^"\']*)["\']~iu', $html, $vm)) {
        $viewportContent = $vm[1];
    } elseif (preg_match('~<meta\b[^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*name\s*=\s*["\']viewport["\']~iu', $html, $vm2)) {
        $viewportContent = $vm2[1];
    }

    if ($viewportContent !== '' && stripos($viewportContent, 'width=device-width') !== false) {
        $mobileScore += 62;
        if (preg_match('~user-scalable\s*=\s*(no|0)~i', $viewportContent)) {
            $issues[] = [
                'severity' => 'opportunity',
                'category' => 'MOBİL',
                'title' => zeynart_audit_msg('viewport_zoom_locked_title', $lang),
                'detail' => zeynart_audit_msg('viewport_zoom_locked_detail', $lang),
            ];
        } else {
            $mobileScore += 12;
            $passed[] = [
                'category' => 'MOBİL',
                'title' => zeynart_audit_msg('viewport_ok_title', $lang),
            ];
        }
    } else {
        $issues[] = [
            'severity' => 'critical',
            'category' => 'MOBİL',
            'title' => zeynart_audit_msg('viewport_missing_title', $lang),
            'detail' => zeynart_audit_msg('viewport_missing_detail', $lang),
        ];
    }

    $hasIcon = (bool)preg_match('~<link\b[^>]*rel\s*=\s*["\'][^"\']*(icon|apple-touch-icon)[^"\']*["\']~iu', $html);
    if ($hasIcon) {
        $mobileScore += 14;
    } else {
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'MOBİL',
            'title' => zeynart_audit_msg('touch_icon_missing_title', $lang),
            'detail' => zeynart_audit_msg('touch_icon_missing_detail', $lang),
        ];
    }

    if ($responsiveImgs > 0 || $hasPictureTag || $nextGenImgs > 0 || $totalImgs === 0) {
        $mobileScore += 12;
    } else {
        $mobileScore += 5;
    }

    // =========================================================================
    // 4. TEMEL META, SOSYAL & YAPAY ZEKA SEO KONTROLÜ
    // =========================================================================
    $seoScore = 0;
    $pageTitle = '';
    if (preg_match('~<title\b[^>]*>(.*?)</title>~isu', $html, $tm)) {
        $pageTitle = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($tm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }
    $titleLen = mb_strlen($pageTitle, 'UTF-8');
    if ($titleLen === 0) {
        $issues[] = [
            'severity' => 'critical',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('title_missing_title', $lang),
            'detail' => zeynart_audit_msg('title_missing_detail', $lang),
        ];
    } elseif ($titleLen >= 25 && $titleLen <= 68) {
        $seoScore += 22;
        $passed[] = [
            'category' => 'SEO',
            'title' => zeynart_audit_msg('title_ok_title', $lang, ['len' => $titleLen]),
        ];
    } else {
        $seoScore += 13;
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('title_len_title', $lang, ['len' => $titleLen]),
            'detail' => zeynart_audit_msg('title_len_detail', $lang),
        ];
    }

    $metaDesc = '';
    if (preg_match('~<meta\b[^>]*name\s*=\s*["\']description["\'][^>]*content\s*=\s*["\']([^"\']*)["\']~isu', $html, $dm)) {
        $metaDesc = trim(html_entity_decode($dm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    } elseif (preg_match('~<meta\b[^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*name\s*=\s*["\']description["\']~isu', $html, $dm2)) {
        $metaDesc = trim(html_entity_decode($dm2[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    $descLen = mb_strlen($metaDesc, 'UTF-8');
    if ($descLen === 0) {
        $issues[] = [
            'severity' => 'critical',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('desc_missing_title', $lang),
            'detail' => zeynart_audit_msg('desc_missing_detail', $lang),
        ];
    } elseif ($descLen >= 65 && $descLen <= 170) {
        $seoScore += 22;
        $passed[] = [
            'category' => 'SEO',
            'title' => zeynart_audit_msg('desc_ok_title', $lang, ['len' => $descLen]),
        ];
    } else {
        $seoScore += 13;
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('desc_len_title', $lang, ['len' => $descLen]),
            'detail' => zeynart_audit_msg('desc_len_detail', $lang),
        ];
    }

    $h1Count = preg_match_all('~<h1\b[^>]*>.*?</h1>~isu', $html);
    if ($h1Count === 1) {
        $seoScore += 16;
        $passed[] = [
            'category' => 'SEO',
            'title' => zeynart_audit_msg('h1_ok_title', $lang),
        ];
    } elseif ($h1Count === 0) {
        $issues[] = [
            'severity' => 'warning',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('h1_missing_title', $lang),
            'detail' => zeynart_audit_msg('h1_missing_detail', $lang),
        ];
    } else {
        $seoScore += 9;
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('h1_multi_title', $lang, ['count' => $h1Count]),
            'detail' => zeynart_audit_msg('h1_multi_detail', $lang),
        ];
    }

    $hasCanonical = (bool)preg_match('~<link\b[^>]*rel\s*=\s*["\']canonical["\']~iu', $html);
    if ($hasCanonical) {
        $seoScore += 12;
    } else {
        $issues[] = [
            'severity' => 'warning',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('canonical_missing_title', $lang),
            'detail' => zeynart_audit_msg('canonical_missing_detail', $lang),
        ];
    }

    if ($missingAltImgs === 0) {
        $seoScore += 10;
    } else {
        $seoScore += max(0, 10 - min(10, $missingAltImgs * 3));
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('img_alt_missing_title', $lang, ['count' => $missingAltImgs]),
            'detail' => zeynart_audit_msg('img_alt_missing_detail', $lang),
        ];
    }

    $hasOg = (bool)preg_match('~property\s*=\s*["\']og:title["\']~iu', $html)
        && (bool)preg_match('~property\s*=\s*["\']og:image["\']~iu', $html);
    if ($hasOg) {
        $seoScore += 9;
    } else {
        $issues[] = [
            'severity' => 'opportunity',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('og_missing_title', $lang),
            'detail' => zeynart_audit_msg('og_missing_detail', $lang),
        ];
    }

    $hasSchema = (bool)preg_match('~application/ld\+json~iu', $html);
    if ($hasSchema) {
        $seoScore += 9;
        $passed[] = [
            'category' => 'SEO',
            'title' => zeynart_audit_msg('schema_ok_title', $lang),
        ];
    } else {
        $issues[] = [
            'severity' => 'warning',
            'category' => 'SEO',
            'title' => zeynart_audit_msg('schema_missing_title', $lang),
            'detail' => zeynart_audit_msg('schema_missing_detail', $lang),
        ];
    }

    // Sort issues: critical -> warning -> opportunity
    $sevWeight = ['critical' => 0, 'warning' => 1, 'opportunity' => 2];
    usort($issues, static fn(array $a, array $b): int => ($sevWeight[$a['severity']] ?? 9) <=> ($sevWeight[$b['severity']] ?? 9));

    $sslScore = max(0, min(100, $sslScore));
    $speedScore = max(0, min(100, $speedScore));
    $mobileScore = max(0, min(100, $mobileScore));
    $seoScore = max(0, min(100, $seoScore));

    $overallScore = (int)round(($speedScore * 0.30) + ($seoScore * 0.30) + ($mobileScore * 0.20) + ($sslScore * 0.20));
    $overallScore = max(8, min(99, $overallScore));

    $scores = [
        'ssl' => $sslScore,
        'speed' => $speedScore,
        'mobile' => $mobileScore,
        'seo' => $seoScore,
    ];

    $issueTitles = array_map(static fn(array $it): string => (string)$it['title'], array_slice($issues, 0, 10));
    $auditToken = zeynart_sign_audit_token([
        'domain' => $host,
        'url' => $finalUrl,
        'overall_score' => $overallScore,
        'scores' => $scores,
        'ttfb_ms' => $ttfbMs,
        'html_kb' => $htmlKb,
        'issues_count' => count($issues),
        'issues' => $issueTitles,
        'ts' => time(),
    ], $store);

    // Pre-build a direct WhatsApp follow-up link for convenience
    $qc = get_quick_contact_state($store);
    $waDigits = preg_replace('/\D+/', '', (string)($qc['whatsapp_number'] ?? '+908503050533')) ?? '908503050533';
    $waTextMap = [
        'tr' => "Merhaba, {$host} web sitemizin hız ve SEO ön analizini yaptım (Skor: {$overallScore}/100). Sitemizi 99+ performansa taşımak için bilgi almak istiyorum.",
        'en' => "Hello, I ran the speed & SEO pre-audit for {$host} (Score: {$overallScore}/100). I would like to get information on taking our site to 99+ performance.",
        'de' => "Hallo, ich habe die Speed- & SEO-Vorab-Analyse für {$host} durchgeführt (Score: {$overallScore}/100) und möchte Informationen für 99+ Performance erhalten.",
        'ru' => "Здравствуйте, я провёл(а) экспресс-анализ сайта {$host} (Оценка: {$overallScore}/100). Хочу узнать, как вывести наш сайт на производительность 99+.",
    ];
    $waFollowUrl = 'https://wa.me/' . $waDigits . '?text=' . rawurlencode($waTextMap[$lang] ?? $waTextMap['tr']);

    return [
        'ok' => true,
        'domain' => $host,
        'final_url' => $finalUrl,
        'http_code' => (int)$fetch['http_code'],
        'ssl_valid' => $sslValid,
        'ttfb_ms' => $ttfbMs,
        'html_kb' => $htmlKb,
        'overall_score' => $overallScore,
        'scores' => $scores,
        'issues_count' => count($issues),
        'issues' => $issues,
        'passed' => array_slice($passed, 0, 8),
        'page_title' => $pageTitle,
        'audit_token' => $auditToken,
        'whatsapp_url' => $waFollowUrl,
    ];
}

function render_site_audit_section(array $store, array $opts = []): string
{
    $cfg = get_site_audit_state($store);
    $standalone = !empty($opts['standalone']);
    $context = (string)($opts['context'] ?? 'home');

    if (!$standalone) {
        if (empty($cfg['enabled'])) {
            return '';
        }
        if ($context === 'home' && empty($cfg['show_on_home'])) {
            return '';
        }
        if ($context === 'services' && empty($cfg['show_on_services'])) {
            return '';
        }
    } elseif (empty($cfg['enabled']) && !is_admin_logged_in()) {
        return '';
    }

    $csrf = csrf_token();
    $lang = zeynart_current_lang();
    $apiUrl = url('/api/site-analiz');
    $uid = 'zsa-' . substr(md5($context . ($standalone ? '-page' : '-sec')), 0, 6);

    ob_start();
    ?>
    <section class="section container zsa-section<?= $standalone ? ' zsa-standalone' : '' ?>" id="site-analiz" data-site-audit-widget>
      <div class="zsa-shell">
        <div class="zsa-glow" aria-hidden="true"></div>

        <div class="zsa-head">
          <div class="zsa-head-copy">
            <p class="eyebrow"><span></span><?= e(t($cfg['eyebrow'])) ?></p>
            <?php if ($standalone): ?>
            <h1 class="zsa-title"><?= e(t($cfg['heading_line1'])) ?><br/><em><?= e(t($cfg['heading_accent'])) ?></em></h1>
            <?php else: ?>
            <h2 class="zsa-title"><?= e(t($cfg['heading_line1'])) ?><br/><em><?= e(t($cfg['heading_accent'])) ?></em></h2>
            <?php endif; ?>
            <p class="zsa-lead"><?= e(t($cfg['lead'])) ?></p>
          </div>
          <div class="zsa-pillars" role="group" aria-label="<?= e(t('Kontrol edilen alanlar')) ?>">
            <span><?= zeynart_icon('shield-check', 15) ?> <?= e(t('SSL & Güvenlik')) ?></span>
            <span><?= zeynart_icon('rocket', 15) ?> <?= e(t('Sunucu & Açılış Hızı')) ?></span>
            <span><?= zeynart_icon('smartphone', 15) ?> <?= e(t('Mobil Uyumluluk')) ?></span>
            <span><?= zeynart_icon('scan-search', 15) ?> <?= e(t('Temel Meta & AI SEO')) ?></span>
          </div>
        </div>

        <form class="zsa-url-bar" data-site-audit-form action="<?= e($apiUrl) ?>" method="post" novalidate>
          <input type="hidden" name="op" value="analyze"/>
          <input type="hidden" name="lang" value="<?= e($lang) ?>"/>
          <div class="zsa-input-wrap">
            <span class="zsa-input-icon" aria-hidden="true"><?= zeynart_icon('globe', 19) ?></span>
            <label class="sr-only" for="<?= e($uid) ?>-url"><?= e(t('Web sitenizin adresi')) ?></label>
            <input
              id="<?= e($uid) ?>-url"
              type="text"
              name="url"
              inputmode="url"
              autocomplete="off"
              autocapitalize="off"
              spellcheck="false"
              required
              placeholder="<?= e(t($cfg['placeholder'])) ?>"
            />
          </div>
          <button type="submit" class="button button-primary zsa-submit-btn" data-magnetic="true">
            <span data-zsa-btn-label><?= e(t($cfg['analyze_button'])) ?></span>
            <?= zeynart_icon('arrow-up-right', 17) ?>
          </button>
        </form>

        <div class="zsa-error-box" data-site-audit-error style="display:none" role="alert"></div>

        <div class="zsa-loading" data-site-audit-loading style="display:none" aria-live="polite">
          <div class="zsa-loader-ring" aria-hidden="true"></div>
          <div class="zsa-loading-steps">
            <strong data-zsa-loading-title><?= e(t('Web siteniz canlı olarak taranıyor…')) ?></strong>
            <span data-zsa-loading-step><?= e(t('SSL sertifikası, sunucu TTFB hızı, mobil viewport ve SEO meta etiketleri inceleniyor.')) ?></span>
          </div>
        </div>

        <div class="zsa-result" data-site-audit-result style="display:none">
          <div class="zsa-score-banner">
            <div class="zsa-gauge-wrap">
              <svg class="zsa-gauge" viewBox="0 0 120 120" aria-hidden="true">
                <circle class="zsa-gauge-track" cx="60" cy="60" r="52"></circle>
                <circle class="zsa-gauge-fill" data-zsa-ring cx="60" cy="60" r="52" stroke-dasharray="326.73" stroke-dashoffset="326.73"></circle>
              </svg>
              <div class="zsa-gauge-num">
                <strong data-zsa-overall>0</strong>
                <small>/ 100</small>
              </div>
            </div>

            <div class="zsa-domain-summary">
              <div class="zsa-domain-badges">
                <span class="zsa-domain-pill" data-zsa-domain>ornek.com</span>
                <span class="zsa-metric-chip" data-zsa-ttfb>TTFB: -- ms</span>
                <span class="zsa-metric-chip" data-zsa-size>HTML: -- KB</span>
                <span class="zsa-metric-chip" data-zsa-ssl-chip>SSL</span>
              </div>
              <h3 data-zsa-verdict><?= e(t('Teknik Ön Analiz Karnesi')) ?></h3>
              <p data-zsa-verdict-sub></p>
            </div>

            <div class="zsa-subscores">
              <div class="zsa-subcard">
                <div class="zsa-subcard-top"><span><?= e(t('SSL & Güvenlik')) ?></span><strong data-zsa-sc-ssl>0</strong></div>
                <div class="zsa-bar"><i data-zsa-bar-ssl style="width:0%"></i></div>
              </div>
              <div class="zsa-subcard">
                <div class="zsa-subcard-top"><span><?= e(t('Hız & Performans')) ?></span><strong data-zsa-sc-speed>0</strong></div>
                <div class="zsa-bar"><i data-zsa-bar-speed style="width:0%"></i></div>
              </div>
              <div class="zsa-subcard">
                <div class="zsa-subcard-top"><span><?= e(t('Mobil Uyumluluk')) ?></span><strong data-zsa-sc-mobile>0</strong></div>
                <div class="zsa-bar"><i data-zsa-bar-mobile style="width:0%"></i></div>
              </div>
              <div class="zsa-subcard">
                <div class="zsa-subcard-top"><span><?= e(t('Temel Meta & SEO')) ?></span><strong data-zsa-sc-seo>0</strong></div>
                <div class="zsa-bar"><i data-zsa-bar-seo style="width:0%"></i></div>
              </div>
            </div>
          </div>

          <div class="zsa-report-grid">
            <div class="zsa-findings-col">
              <div class="zsa-col-head">
                <h4><?= e(t('Tespit Edilen Eksikler & İyileştirme Alanları')) ?></h4>
                <span class="zsa-count-pill" data-zsa-issues-count>0</span>
              </div>
              <div class="zsa-issues-list" data-zsa-issues-list></div>

              <div class="zsa-passed-wrap" data-zsa-passed-wrap style="display:none">
                <h5><?= e(t('Doğrulanan Güçlü Noktalar')) ?></h5>
                <div class="zsa-passed-list" data-zsa-passed-list></div>
              </div>
            </div>

            <div class="zsa-lead-card">
              <span class="zsa-cta-badge"><?= zeynart_icon('sparkles', 14) ?> <?= e(t($cfg['cta_badge'])) ?></span>
              <h3><?= e(t($cfg['cta_heading'])) ?></h3>
              <p><?= e(t($cfg['cta_sub'])) ?></p>

              <form class="zsa-lead-form" data-site-audit-lead-form action="<?= e($apiUrl) ?>" method="post">
                <input type="hidden" name="op" value="lead"/>
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"/>
                <input type="hidden" name="lang" value="<?= e($lang) ?>"/>
                <input type="hidden" name="audit_token" data-zsa-audit-token value=""/>
                <div style="position:absolute;left:-9999px;opacity:0;pointer-events:none" aria-hidden="true">
                  <input type="text" name="website_hp" tabindex="-1" autocomplete="off"/>
                </div>

                <div class="zsa-lead-field">
                  <label for="<?= e($uid) ?>-name"><?= e(t('Ad soyad *')) ?></label>
                  <input id="<?= e($uid) ?>-name" type="text" name="name" required placeholder="<?= e(t('Adınız ve soyadınız')) ?>"/>
                </div>
                <div class="zsa-lead-row">
                  <div class="zsa-lead-field">
                    <label for="<?= e($uid) ?>-phone"><?= e(t('Telefon / WhatsApp *')) ?></label>
                    <input id="<?= e($uid) ?>-phone" type="tel" name="phone" required placeholder="+90 5XX XXX XX XX"/>
                  </div>
                  <div class="zsa-lead-field">
                    <label for="<?= e($uid) ?>-email"><?= e(t('E-posta adresiniz *')) ?></label>
                    <input id="<?= e($uid) ?>-email" type="email" name="email" required placeholder="<?= e(t('siz@sirketiniz.com')) ?>"/>
                  </div>
                </div>
                <div class="zsa-lead-field">
                  <label for="<?= e($uid) ?>-note"><?= e(t('Eklemek istediğiniz not (opsiyonel)')) ?></label>
                  <input id="<?= e($uid) ?>-note" type="text" name="note" placeholder="<?= e(t('Ör: Yenileme, hızlandırma veya SEO teklifi istiyorum')) ?>"/>
                </div>

                <label class="form-check zsa-consent">
                  <input type="checkbox" name="consent" value="1" required/>
                  <span><a href="<?= e(url('/gizlilik/')) ?>" target="_blank" rel="noopener"><?= e(t('Gizlilik bilgilendirmesini')) ?></a> <?= e(t('okudum ve kabul ediyorum.')) ?></span>
                </label>

                <div class="zsa-lead-actions">
                  <button type="submit" class="button button-primary zsa-lead-submit">
                    <span><?= e(t($cfg['cta_button'])) ?></span>
                    <?= zeynart_icon('arrow-up-right', 16) ?>
                  </button>
                  <a class="zsa-wa-report-btn" data-zsa-wa-btn href="#" target="_blank" rel="noopener noreferrer">
                    <?= zeynart_social_icon('whatsapp', 17) ?>
                    <span><?= e(t('WhatsApp ile Raporu Gönder')) ?></span>
                  </a>
                </div>
                <div class="zsa-lead-feedback" data-zsa-lead-feedback></div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
    return (string)ob_get_clean();
}
