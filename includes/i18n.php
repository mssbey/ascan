<?php
declare(strict_types=1);

/**
 * ZEYNART ÇOKLU DİL (i18n) ALTYAPISI — TR · EN · DE · RU
 * - Türkçe (tr): Varsayılan kök dizin (/)
 * - İngilizce (en): /en/...
 * - Almanca (de): /de/...
 * - Rusça (ru): /ru/...
 */

function zeynart_supported_languages(): array
{
    return [
        'tr' => [
            'code' => 'tr',
            'badge' => 'TR',
            'name' => 'Türkçe',
            'native' => 'Türkçe',
            'flag' => '🇹🇷',
            'locale' => 'tr_TR',
            'bcp47' => 'tr-TR',
        ],
        'en' => [
            'code' => 'en',
            'badge' => 'EN',
            'name' => 'İngilizce',
            'native' => 'English',
            'flag' => '🇬🇧',
            'locale' => 'en_US',
            'bcp47' => 'en-US',
        ],
        'de' => [
            'code' => 'de',
            'badge' => 'DE',
            'name' => 'Almanca',
            'native' => 'Deutsch',
            'flag' => '🇩🇪',
            'locale' => 'de_DE',
            'bcp47' => 'de-DE',
        ],
        'ru' => [
            'code' => 'ru',
            'badge' => 'RU',
            'name' => 'Rusça',
            'native' => 'Русский',
            'flag' => '🇷🇺',
            'locale' => 'ru_RU',
            'bcp47' => 'ru-RU',
        ],
    ];
}

/**
 * Herkese açık dil sürümleri: store.site.languages ile sınırlandırılabilir (ayar yoksa tüm diller).
 */
function zeynart_public_languages(?array $store = null): array
{
    $all = array_keys(zeynart_supported_languages());
    $cfg = $store['site']['languages'] ?? null;
    if (!is_array($cfg) || $cfg === []) {
        return $all;
    }
    $out = array_values(array_intersect($all, array_map('strval', $cfg)));
    return $out !== [] ? $out : ['tr'];
}

function zeynart_current_lang(): string
{
    $lang = (string)($GLOBALS['__zeynart_lang'] ?? 'tr');
    return in_array($lang, ['tr', 'en', 'de', 'ru'], true) ? $lang : 'tr';
}

function zeynart_set_lang(string $lang): void
{
    $clean = strtolower(trim($lang));
    $GLOBALS['__zeynart_lang'] = in_array($clean, ['tr', 'en', 'de', 'ru'], true) ? $clean : 'tr';
}

function zeynart_lang_meta(?string $lang = null): array
{
    $langs = zeynart_supported_languages();
    $code = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    return $langs[$code] ?? $langs['tr'];
}

function zeynart_is_unprefixed_path(string $cleanPath): bool
{
    if ($cleanPath === '' || $cleanPath === '/') {
        return false;
    }
    $pathOnly = (string)(parse_url($cleanPath, PHP_URL_PATH) ?: $cleanPath);
    $staticPrefixes = [
        '/assets/', '/images/', '/uploads/', '/admin/', '/api/', '/kartvizit/', '/teklif/', '/proje-takip/', '/og-card',
        '/.well-known/', '/wp-content/', '/en/', '/de/', '/ru/',
    ];
    $admSlug = function_exists('zeynart_get_admin_slug') ? zeynart_get_admin_slug() : 'admin';
    if ($admSlug !== 'admin') {
        $staticPrefixes[] = '/' . $admSlug . '/';
    }
    if (defined('ADMIN_DIR_NAME') && strtolower(ADMIN_DIR_NAME) !== 'admin') {
        $staticPrefixes[] = '/' . ADMIN_DIR_NAME . '/';
    }
    foreach ($staticPrefixes as $pfx) {
        if (str_starts_with($pathOnly, $pfx)) {
            return true;
        }
    }
    $exactOrPrefix = [
        '/admin', '/api', '/kartvizit', '/teklif', '/proje-takip', '/en', '/de', '/ru',
        '/opengraph-image', '/opengraph-image/',
        '/robots.txt', '/llms.txt', '/llms-full.txt', '/ai-catalog.json',
        '/sitemap.xml', '/sitemap-pages.xml', '/sitemap-blog.xml', '/sitemap.css', '/sitemap.xsl',
        '/icon.png', '/icon.svg', '/favicon.ico', '/apple-touch-icon.png',
    ];
    if ($admSlug !== 'admin') {
        $exactOrPrefix[] = '/' . $admSlug;
    }
    if (defined('ADMIN_DIR_NAME') && strtolower(ADMIN_DIR_NAME) !== 'admin') {
        $exactOrPrefix[] = '/' . ADMIN_DIR_NAME;
    }
    if (in_array($pathOnly, $exactOrPrefix, true)) {
        return true;
    }
    // Any direct static media/asset file extension at root
    if (preg_match('~\.(?:webp|avif|png|jpe?g|gif|svg|ico|woff2?|ttf|css|js|xml|txt|json|pdf|zip)$~i', $pathOnly)) {
        return true;
    }
    return false;
}

function zeynart_upload_versioned_path(string $cleanPath): string
{
    static $mtimeMemo = [];
    if (
        !str_starts_with($cleanPath, '/uploads/')
        || str_starts_with($cleanPath, '/uploads/og/')
        || str_contains($cleanPath, '?')
        || str_contains($cleanPath, '..')
        || !preg_match('~\.(?:webp|avif|png|jpe?g|gif|svg)$~i', $cleanPath)
    ) {
        return $cleanPath;
    }
    if (!array_key_exists($cleanPath, $mtimeMemo)) {
        $abs = ROOT_DIR . $cleanPath;
        $mtimeMemo[$cleanPath] = is_file($abs) ? (int)(@filemtime($abs) ?: 0) : 0;
    }
    return $mtimeMemo[$cleanPath] > 0
        ? ($cleanPath . '?v=' . $mtimeMemo[$cleanPath])
        : $cleanPath;
}

function zeynart_version_upload_imgs_in_html(string $html): string
{
    if ($html === '' || !str_contains($html, '/uploads/')) {
        return $html;
    }
    return preg_replace_callback(
        '~(<img\b[^>]*\bsrc=)(["\'])((?:' . preg_quote(BASE_PATH, '~') . ')?/uploads/[^"\'\?\s]+)\2~iu',
        static function (array $m): string {
            $rawSrc = $m[3];
            $rel = (BASE_PATH !== '' && str_starts_with($rawSrc, BASE_PATH . '/'))
                ? substr($rawSrc, strlen(BASE_PATH))
                : $rawSrc;
            $verRel = zeynart_upload_versioned_path($rel);
            return $m[1] . $m[2] . BASE_PATH . $verRel . $m[2];
        },
        $html
    ) ?? $html;
}

function lang_url(string $path = '/', ?string $lang = null): string
{
    $targetLang = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    if (!in_array($targetLang, ['tr', 'en', 'de', 'ru'], true)) {
        $targetLang = 'tr';
    }
    if (
        str_starts_with($path, 'http://')
        || str_starts_with($path, 'https://')
        || str_starts_with($path, 'mailto:')
        || str_starts_with($path, 'tel:')
        || str_starts_with($path, '#')
    ) {
        return $path;
    }

    // Strip any existing /en, /de, /ru prefix first so switching languages is idempotent
    $clean = ($path === '' || $path === '/') ? '/' : ('/' . ltrim($path, '/'));
    if (preg_match('~^/(en|de|ru)(/.*|\?.*|$)~', $clean, $m)) {
        $rest = $m[2];
        $clean = ($rest === '' || $rest[0] === '?') ? ('/' . $rest) : $rest;
    }

    if (zeynart_is_unprefixed_path($clean)) {
        return BASE_PATH . zeynart_upload_versioned_path($clean);
    }

    if (function_exists('zeynart_rewrite_custom_slug_path')) {
        $clean = zeynart_rewrite_custom_slug_path($clean);
    }

    if ($targetLang === 'tr') {
        if ($clean === '/') {
            return BASE_PATH !== '' ? BASE_PATH . '/' : '/';
        }
        return BASE_PATH . $clean;
    }

    if ($clean === '/' || str_starts_with($clean, '/?')) {
        $q = strlen($clean) > 1 ? substr($clean, 1) : '';
        return BASE_PATH . '/' . $targetLang . '/' . $q;
    }
    return BASE_PATH . '/' . $targetLang . $clean;
}

function lang_canonical_url(string $path = '/', ?array $store = null, ?string $lang = null): string
{
    $domain = rtrim((string)($store['seo']['canonical_domain'] ?? ''), '/');
    if ($domain === '') {
        $domain = rtrim(SITE_URL, '/');
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $targetLang = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    if (!in_array($targetLang, ['tr', 'en', 'de', 'ru'], true)) {
        $targetLang = 'tr';
    }

    $clean = ($path === '' || $path === '/') ? '/' : ('/' . ltrim($path, '/'));
    if (preg_match('~^/(en|de|ru)(/.*|\?.*|$)~', $clean, $m)) {
        $rest = $m[2];
        $clean = ($rest === '' || $rest[0] === '?') ? ('/' . $rest) : $rest;
    }

    if (zeynart_is_unprefixed_path($clean)) {
        return $domain . $clean;
    }

    if (function_exists('zeynart_rewrite_custom_slug_path')) {
        $clean = zeynart_rewrite_custom_slug_path($clean, $store);
    }

    if ($targetLang === 'tr') {
        return $clean === '/' ? ($domain . '/') : ($domain . $clean);
    }

    if ($clean === '/' || str_starts_with($clean, '/?')) {
        $q = strlen($clean) > 1 ? substr($clean, 1) : '';
        return $domain . '/' . $targetLang . '/' . $q;
    }
    return $domain . '/' . $targetLang . $clean;
}

function format_localized_date(string $isoDate, ?string $lang = null): string
{
    $code = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    if ($code === 'tr') {
        return format_tr_date($isoDate);
    }
    $ts = strtotime($isoDate);
    if ($ts === false) {
        return $isoDate;
    }
    $day = (int)date('j', $ts);
    $monthIdx = (int)date('n', $ts);
    $year = date('Y', $ts);

    if ($code === 'en') {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        return ($months[$monthIdx] ?? '') . " {$day}, {$year}";
    }
    if ($code === 'de') {
        $months = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];
        return "{$day}. " . ($months[$monthIdx] ?? '') . " {$year}";
    }
    if ($code === 'ru') {
        $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
        ];
        return "{$day} " . ($months[$monthIdx] ?? '') . " {$year}";
    }
    return format_tr_date($isoDate);
}

function localize_reading_time(string $rawRead, ?string $lang = null): string
{
    $code = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    if ($code === 'tr') {
        return $rawRead !== '' ? $rawRead : '2 dk';
    }
    $mins = 2;
    if (preg_match('/(\d+)/', $rawRead, $m)) {
        $mins = max(1, (int)$m[1]);
    }
    return match ($code) {
        'en' => $mins . ' min',
        'de' => $mins . ' Min.',
        'ru' => $mins . ' мин',
        default => $mins . ' dk',
    };
}

function zeynart_ui_dictionary(): array
{
    static $dict = null;
    if ($dict !== null) {
        return $dict;
    }

    $dict = [
        // Header, Navigation & Footer
        'Ana sayfa' => ['en' => 'Home', 'de' => 'Startseite', 'ru' => 'Главная'],
        'Hakkımızda' => ['en' => 'About Us', 'de' => 'Über uns', 'ru' => 'О нас'],
        'Hizmetler' => ['en' => 'Services', 'de' => 'Leistungen', 'ru' => 'Услуги'],
        'Hizmetlerimiz' => ['en' => 'Our Services', 'de' => 'Unsere Leistungen', 'ru' => 'Наши услуги'],
        'Projeler' => ['en' => 'Projects', 'de' => 'Projekte', 'ru' => 'Проекты'],
        'Blog' => ['en' => 'Blog', 'de' => 'Blog', 'ru' => 'Блог'],
        'İletişim' => ['en' => 'Contact', 'de' => 'Kontakt', 'ru' => 'Контакты'],
        'İçeriğe geç' => ['en' => 'Skip to content', 'de' => 'Zum Inhalt springen', 'ru' => 'Перейти к содержанию'],
        'Ana menü' => ['en' => 'Main menu', 'de' => 'Hauptmenü', 'ru' => 'Главное меню'],
        'Mobil menü' => ['en' => 'Mobile menu', 'de' => 'Mobiles Menü', 'ru' => 'Мобильное меню'],
        'Menüyü aç' => ['en' => 'Open menu', 'de' => 'Menü öffnen', 'ru' => 'Открыть меню'],
        'Menüyü kapat' => ['en' => 'Close menu', 'de' => 'Menü schließen', 'ru' => 'Закрыть меню'],
        'Dil seçimi' => ['en' => 'Language switcher', 'de' => 'Sprachauswahl', 'ru' => 'Выбор языка'],
        'Sosyal medya' => ['en' => 'Social media', 'de' => 'Soziale Medien', 'ru' => 'Социальные сети'],
        'Sosyal medya hesaplarımız' => ['en' => 'Our social media accounts', 'de' => 'Unsere Social-Media-Kanäle', 'ru' => 'Наши соцсети'],
        'Sosyal medya kanalları' => ['en' => 'Social media channels', 'de' => 'Social-Media-Kanäle', 'ru' => 'Каналы социальных сетей'],
        'Sayfa yolu' => ['en' => 'Breadcrumb', 'de' => 'Seitenpfad', 'ru' => 'Навигация'],
        'GÖR ↗' => ['en' => 'VIEW ↗', 'de' => 'ANSEHEN ↗', 'ru' => 'ОТКРЫТЬ ↗'],
        'DİJİTAL STÜDYO' => ['en' => 'DIGITAL STUDIO', 'de' => 'DIGITALSTUDIO', 'ru' => 'ЦИФРОВАЯ СТУДИЯ'],
        'WEB · GRAFİK · YAZILIM · HOSTING' => ['en' => 'WEB · DESIGN · SOFTWARE · HOSTING', 'de' => 'WEB · GRAFIK · SOFTWARE · HOSTING', 'ru' => 'ВЕБ · ДИЗАЙН · РАЗРАБОТКА · ХОСТИНГ'],
        'YARATICILIK · VİZYON' => ['en' => 'CREATIVITY · VISION', 'de' => 'KREATIVITÄT · VISION', 'ru' => 'КРЕАТИВНОСТЬ · ВИДЕНИЕ'],
        'DİJİTAL DENEYİM' => ['en' => 'DIGITAL EXPERIENCE', 'de' => 'DIGITALES ERLEBNIS', 'ru' => 'ЦИФРОВОЙ ОПЫТ'],
        'FİKİRLER. TEKNOLOJİ. DAHA FAZLASI.' => ['en' => 'IDEAS. TECHNOLOGY. BEYOND.', 'de' => 'IDEEN. TECHNOLOGIE. MEHR.', 'ru' => 'ИДЕИ. ТЕХНОЛОГИИ. БОЛЬШЕ.'],
        'BİR FİKRİNİZ Mİ VAR?' => ['en' => 'HAVE AN IDEA?', 'de' => 'HABEN SIE EINE IDEE?', 'ru' => 'ЕСТЬ ИДЕЯ?'],
        'Birlikte' => ['en' => 'Let’s bring', 'de' => 'Gemeinsam', 'ru' => 'Воплотим'],
        'hayata' => ['en' => 'it to', 'de' => 'zum Leben', 'ru' => 'её в'],
        'geçirelim.' => ['en' => 'life.', 'de' => 'erwecken.', 'ru' => 'жизнь.'],
        'Projenizi anlatın' => ['en' => 'Tell us about your project', 'de' => 'Projekt anfragen', 'ru' => 'Расскажите о проекте'],
        'BİR MERHABA, YENİ BİR BAŞLANGIÇ.' => ['en' => 'ONE HELLO, A NEW BEGINNING.', 'de' => 'EIN HALLO, EIN NEUER ANFANG.', 'ru' => 'ОДНО ПРИВЕТСТВИЕ — НОВОЕ НАЧАЛО.'],
        'Bir merhaba ile başlar.' => ['en' => 'It starts with a hello.', 'de' => 'Alles beginnt mit einem Hallo.', 'ru' => 'Всё начинается с приветствия.'],
        'Fikrinizi konuşalım' => ['en' => 'Let’s discuss your idea', 'de' => 'Lassen Sie uns sprechen', 'ru' => 'Обсудим вашу идею'],

        // Homepage & About
        'Zeynart Web Bilişim tanıtımı' => ['en' => 'Zeynart Digital Studio showcase', 'de' => 'Zeynart Digital Studio Vorstellung', 'ru' => 'Презентация студии Zeynart'],
        'Tanıtım slaytları' => ['en' => 'Hero slides', 'de' => 'Präsentationsfolien', 'ru' => 'Слайды презентации'],
        'Otomatik geçişi duraklat' => ['en' => 'Pause autoplay', 'de' => 'Automatischen Wechsel pausieren', 'ru' => 'Приостановить автопереключение'],
        'Otomatik geçişi başlat' => ['en' => 'Start autoplay', 'de' => 'Automatischen Wechsel starten', 'ru' => 'Запустить автопереключение'],
        'Zeynart yaklaşımı' => ['en' => 'Zeynart approach', 'de' => 'Zeynart-Ansatz', 'ru' => 'Подход Zeynart'],
        'Teklif alın' => ['en' => 'Get a quote', 'de' => 'Angebot anfordern', 'ru' => 'Получить расчёт'],
        'Neler yapıyoruz?' => ['en' => 'What we do?', 'de' => 'Was wir tun?', 'ru' => 'Что мы делаем?'],
        'NELER YAPIYORUZ?' => ['en' => 'WHAT WE DO', 'de' => 'WAS WIR TUN', 'ru' => 'ЧТО МЫ ДЕЛАЕМ'],
        'Zeynart marka dünyası' => ['en' => 'Zeynart brand universe', 'de' => 'Zeynart Markenwelt', 'ru' => 'Мир бренда Zeynart'],
        'YARATICI BAKIŞ. GÜÇLÜ BİR İMZA.' => ['en' => 'CREATIVE PERSPECTIVE. A STRONG SIGNATURE.', 'de' => 'KREATIVE PERSPEKTIVE. STARKE HANDSCHRIFT.', 'ru' => 'КРЕАТИВНЫЙ ВЗГЛЯД. СИЛЬНЫЙ ПОЧЕРК.'],
        'Birlikte çalışalım' => ['en' => 'Work with us', 'de' => 'Zusammenarbeiten', 'ru' => 'Работать вместе'],
        'Çalışma ilkelerimiz' => ['en' => 'Our working principles', 'de' => 'Unsere Arbeitsprinzipien', 'ru' => 'Принципы нашей работы'],
        'BİRLİKTE DEĞER ÜRETİYORUZ' => ['en' => 'CREATING VALUE TOGETHER', 'de' => 'GEMEINSAM MEHRWERT SCHAFFEN', 'ru' => 'СОЗДАЁМ ЦЕННОСТЬ ВМЕСТЕ'],
        'Farklı sektörler.' => ['en' => 'Diverse industries.', 'de' => 'Verschiedene Branchen.', 'ru' => 'Разные отрасли.'],
        'Ortak bir güven.' => ['en' => 'Shared trust.', 'de' => 'Gemeinsames Vertrauen.', 'ru' => 'Общее доверие.'],
        'Her markanın hikâyesi farklı. Dijital dünyada birlikte yol aldığımız müşterilerimizle tanışın.' => [
            'en' => 'Every brand has a unique story. Meet the clients we partner with in the digital world.',
            'de' => 'Jede Marke hat ihre eigene Geschichte. Lernen Sie die Kunden kennen, die wir digital begleiten.',
            'ru' => 'У каждого бренда своя история. Познакомьтесь с клиентами, с которыми мы развиваем цифровые проекты.',
        ],
        'MARKALAR & İŞ BİRLİKLERİ' => ['en' => 'BRANDS & PARTNERSHIPS', 'de' => 'MARKEN & PARTNERSCHAFTEN', 'ru' => 'БРЕНДЫ И ПАРТНЁРСТВА'],
        'marka, farklı hikâyeler' => ['en' => 'brands, unique stories', 'de' => 'Marken, eigene Geschichten', 'ru' => 'брендов, уникальные истории'],
        'web sitesini aç' => ['en' => 'open website', 'de' => 'Website öffnen', 'ru' => 'открыть сайт'],
        'Hizmetlerimizi keşfedin' => ['en' => 'Explore our services', 'de' => 'Unsere Leistungen entdecken', 'ru' => 'Изучить наши услуги'],
        'YENİ BAKIŞ AÇILARI. GÜÇLÜ TEMELLER.' => ['en' => 'NEW PERSPECTIVES. SOLID FOUNDATIONS.', 'de' => 'NEUE PERSPEKTIVEN. STARKE FUNDAMENTEN.', 'ru' => 'НОВЫЕ ПЕРСПЕКТИВЫ. ПРОЧНЫЙ ФУНДАМЕНТ.'],
        'İŞİNİZE ÖZEL ÇÖZÜMLER' => ['en' => 'TAILORED BUSINESS SOLUTIONS', 'de' => 'MASSGESCHNEIDERTE LÖSUNGEN', 'ru' => 'РЕШЕНИЯ ПОД ВАШ БИЗНЕС'],
        'Web yazılım projelerimiz.' => ['en' => 'Our custom web software solutions.', 'de' => 'Unsere Web-Softwareprojekte.', 'ru' => 'Наши веб-программные решения.'],
        'Yazılım çözümlerini keşfedin' => ['en' => 'Explore software solutions', 'de' => 'Softwarelösungen entdecken', 'ru' => 'Изучить программные решения'],
        'MÜŞTERİ DENEYİMLERİ' => ['en' => 'CLIENT EXPERIENCES', 'de' => 'KUNDENERFAHRUNGEN', 'ru' => 'ОТЗЫВЫ КЛИЕНТОВ'],
        'Birlikte çalıştıklarımızdan.' => ['en' => 'From our partners & clients.', 'de' => 'Stimmen unserer Kunden.', 'ru' => 'Отзывы наших клиентов.'],
        'Zeynart’ın birlikte çalıştığı markalardan paylaşılan Google değerlendirmeleri.' => [
            'en' => 'Verified Google reviews shared by brands partnering with Zeynart.',
            'de' => 'Verifizierte Google-Bewertungen von Marken, die mit Zeynart zusammenarbeiten.',
            'ru' => 'Проверенные отзывы Google от брендов, сотрудничающих с Zeynart.',
        ],

        // Services & Service Detail
        'Projenizi konuşalım' => ['en' => 'Let’s discuss your project', 'de' => 'Über Ihr Projekt sprechen', 'ru' => 'Обсудим ваш проект'],
        'ZEYNART / TASARIM STÜDYOSU' => ['en' => 'ZEYNART / DESIGN STUDIO', 'de' => 'ZEYNART / DESIGNSTUDIO', 'ru' => 'ZEYNART / ДИЗАЙН-СТУДИЯ'],
        'FİKİRDEN GÖRSEL KİMLİĞE' => ['en' => 'FROM IDEA TO VISUAL IDENTITY', 'de' => 'VON DER IDEE ZUR VISUELLEN IDENTITÄT', 'ru' => 'ОТ ИДЕИ К ВИЗУАЛЬНОЙ АЙДЕНТИКЕ'],
        'BİZİM İMZAMIZ, SİZİN HİKÂYENİZ.' => ['en' => 'OUR CRAFT, YOUR STORY.', 'de' => 'UNSERE HANDSCHRIFT, IHRE GESCHICHTE.', 'ru' => 'НАШ ПОЧЕРК, ВАША ИСТОРИЯ.'],
        'Tasarımda karakter.' => ['en' => 'Character in design.', 'de' => 'Charakter im Design.', 'ru' => 'Характер в дизайне.'],
        'Her detayda Zeynart.' => ['en' => 'Zeynart in every detail.', 'de' => 'Zeynart in jedem Detail.', 'ru' => 'Zeynart в каждой детали.'],
        'Bir rengin enerjisi, bir çizginin karakteri, bir dokunun hissi. Markanın bütününü oluşturan detayları aynı yaratıcı bakışla bir araya getiriyoruz.' => [
            'en' => 'The energy of a color, the character of a line, the feel of a texture. We unite every detail that shapes your brand under one cohesive creative vision.',
            'de' => 'Die Energie einer Farbe, der Charakter einer Linie, das Gefühl einer Textur. Wir vereinen alle Details Ihrer Marke mit einer klaren kreativen Handschrift.',
            'ru' => 'Энергия цвета, характер линии, тактильность текстуры. Мы объединяем все детали вашего бренда в единую креативную систему.',
        ],
        'Zeynart marka renkleri' => ['en' => 'Zeynart brand colors', 'de' => 'Zeynart Markenfarben', 'ru' => 'Фирменные цвета Zeynart'],
        'Markanızın hikâyesini tasarlayalım' => ['en' => 'Let’s design your brand story', 'de' => 'Gestalten wir Ihre Markengeschichte', 'ru' => 'Создадим историю вашего бренда'],
        'TASARIMIN HER ALANINDA' => ['en' => 'ACROSS EVERY DESIGN DISCIPLINE', 'de' => 'IN JEDEM DESIGNBEREICH', 'ru' => 'ВО ВСЕХ СФЕРАХ ДИЗАЙНА'],
        'Bir bütün olarak' => ['en' => 'Your brand', 'de' => 'Ihre Marke', 'ru' => 'Ваш бренд'],
        'markanız.' => ['en' => 'as a whole.', 'de' => 'als Ganzes.', 'ru' => 'как единое целое.'],
        'İlk izlenimden günlük iletişime kadar, markanızın ihtiyaç duyduğu görsel dili aynı özenle tasarlıyoruz.' => [
            'en' => 'From first impression to everyday communication, we craft the visual language your brand needs with consistent precision.',
            'de' => 'Vom ersten Eindruck bis zur täglichen Kommunikation gestalten wir die visuelle Sprache Ihrer Marke mit höchster Sorgfalt.',
            'ru' => 'От первого впечатления до ежедневных коммуникаций — мы создаём целостный визуальный язык вашего бренда.',
        ],
        'TASARIMA BAKIŞIMIZ' => ['en' => 'OUR DESIGN PHILOSOPHY', 'de' => 'UNSERE DESIGNPHILOSOPHIE', 'ru' => 'НАША ФИЛОСОФИЯ ДИЗАЙНА'],
        'Estetik bir başlangıç.' => ['en' => 'An aesthetic start.', 'de' => 'Ein ästhetischer Anfang.', 'ru' => 'Эстетичное начало.'],
        'Akılda kalıcı bir bütün.' => ['en' => 'A memorable whole.', 'de' => 'Ein unvergessliches Ganzes.', 'ru' => 'Запоминающееся целое.'],
        'Güzel görünen bir tasarımın ötesine geçiyor; okunabilirliği, uygulanabilirliği ve markanızla kurduğu bağı birlikte düşünüyoruz.' => [
            'en' => 'We go beyond aesthetics—considering readability, scalability, and the emotional connection your brand builds.',
            'de' => 'Wir gehen über reine Ästhetik hinaus und denken Lesbarkeit, Umsetzbarkeit und Markenbindung gemeinsam.',
            'ru' => 'Мы выходим за рамки внешней красоты, продумывая читаемость, применимость и эмоциональную связь с брендом.',
        ],
        '01 / Özgün karakter' => ['en' => '01 / Distinct character', 'de' => '01 / Eigener Charakter', 'ru' => '01 / Уникальный характер'],
        '02 / Tutarlı görsel dil' => ['en' => '02 / Cohesive visual language', 'de' => '02 / Konsistente Bildsprache', 'ru' => '02 / Единый визуальный язык'],
        '03 / Her mecraya uyum' => ['en' => '03 / Cross-media adaptability', 'de' => '03 / Medienübergreifend', 'ru' => '03 / Адаптивность к любой среде'],
        'BİRLİKTE NASIL İLERLİYORUZ?' => ['en' => 'HOW WE COLLABORATE', 'de' => 'WIE WIR ZUSAMMENARBEITEN', 'ru' => 'КАК МЫ РАБОТАЕМ ВМЕСТЕ'],
        'Fikirden son dokunuşa.' => ['en' => 'From concept to final polish.', 'de' => 'Von der Idee bis zum Feinschliff.', 'ru' => 'От идеи до финального штриха.'],
        'Beklentileri baştan netleştirdiğimiz, her aşamasında sizin de yer aldığınız şeffaf bir tasarım süreci.' => [
            'en' => 'A transparent design workflow where expectations are aligned upfront and you stay involved at every milestone.',
            'de' => 'Ein transparenter Designprozess, bei dem Erwartungen von Anfang an klar sind und Sie jede Phase begleiten.',
            'ru' => 'Прозрачный дизайн-процесс, где ожидания фиксируются с самого начала, а вы вовлечены на каждом этапе.',
        ],
        'KULLANIMA HAZIR TESLİM' => ['en' => 'PRODUCTION-READY DELIVERY', 'de' => 'EINSATZBEREITE ÜBERGABE', 'ru' => 'ГОТОВНОСТЬ К ИСПОЛЬЗОВАНИЮ'],
        'İyi tasarım, doğru' => ['en' => 'Great design is completed', 'de' => 'Gutes Design endet mit', 'ru' => 'Отличный дизайн завершается'],
        'dosyayla tamamlanır.' => ['en' => 'with the right files.', 'de' => 'den richtigen Dateien.', 'ru' => 'правильными файлами.'],
        'Dosya formatları, revizyon kapsamı ve teslim takvimini teklif aşamasında birlikte belirliyoruz. İhtiyacınıza göre vektörel dosyalar, baskıya uygun PDF ve dijital görseller hazırlıyoruz.' => [
            'en' => 'We define file formats, revision scope, and delivery timelines together upfront—delivering vector sources, print-ready PDFs, and digital assets.',
            'de' => 'Dateiformate, Korrekturschleifen und Zeitplan legen wir vorab gemeinsam fest – inklusive Vektordateien, Druck-PDFs und digitalen Assets.',
            'ru' => 'Форматы файлов, объём правок и сроки мы согласуем заранее — передаём векторные исходники, печатные PDF и цифровые макеты.',
        ],
        'Markanıza yeni bir' => ['en' => 'Let’s look at your brand', 'de' => 'Ein frischer Blick auf', 'ru' => 'Взглянем на ваш бренд'],
        'gözle bakalım.' => ['en' => 'with fresh eyes.', 'de' => 'Ihre Marke.', 'ru' => 'по-новому.'],
        'Yeni bir başlangıç ya da mevcut kimliğinize taze bir dokunuş. İhtiyacınızı birlikte şekillendirelim.' => [
            'en' => 'Whether launching from scratch or refreshing an existing identity, let’s shape what your brand needs next.',
            'de' => 'Ob Neustart oder sanfter Relaunch Ihrer bestehenden Identität – gestalten wir gemeinsam den nächsten Schritt.',
            'ru' => 'Новый старт или свежее обновление существующего стиля — давайте вместе сформируем решение.',
        ],
        'Tasarım projenizi konuşalım' => ['en' => 'Discuss your design project', 'de' => 'Designprojekt besprechen', 'ru' => 'Обсудить дизайн-проект'],
        'Tasarım hakkında merak edilenler.' => ['en' => 'Frequently asked design questions.', 'de' => 'Häufige Fragen zum Design.', 'ru' => 'Частые вопросы о дизайне.'],
        'İHTİYACINIZA UYGUN ALAN' => ['en' => 'BUILT FOR YOUR SCALE', 'de' => 'PASSEND FÜR IHREN BEDARF', 'ru' => 'РЕСУРСЫ ПОД ВАШИ ЗАДАЧИ'],
        'Web hosting paketleri.' => ['en' => 'Web hosting plans.', 'de' => 'Webhosting-Pakete.', 'ru' => 'Тарифы веб-хостинга.'],
        'için teklif alın' => ['en' => 'request a quote', 'de' => 'Angebot anfordern', 'ru' => 'запросить расчёт'],
        'Neden Zeynart?' => ['en' => 'Why Zeynart?', 'de' => 'Warum Zeynart?', 'ru' => 'Почему Zeynart?'],
        'İhtiyacınıza özel.' => ['en' => 'Tailored to your needs.', 'de' => 'Maßgeschneidert für Sie.', 'ru' => 'Индивидуально под задачи.'],
        'Her detay düşünülmüş.' => ['en' => 'Every detail considered.', 'de' => 'Jedes Detail durchdacht.', 'ru' => 'Продумана каждая деталь.'],
        'YAKLAŞIMIMIZ' => ['en' => 'OUR APPROACH', 'de' => 'UNSER ANSATZ', 'ru' => 'НАШ ПОДХОД'],
        'Doğru araçlar.' => ['en' => 'Right tools.', 'de' => 'Die richtigen Werkzeuge.', 'ru' => 'Правильные инструменты.'],
        'Sürdürülebilir sonuçlar.' => ['en' => 'Sustainable results.', 'de' => 'Nachhaltige Ergebnisse.', 'ru' => 'Устойчивый результат.'],
        'Merak edilenler.' => ['en' => 'Frequently asked questions.', 'de' => 'Häufig gestellte Fragen.', 'ru' => 'Часто задаваемые вопросы.'],
        'Konuyla ilgili bir bakış açısı' => ['en' => 'Related perspective from our journal', 'de' => 'Passender Beitrag aus unserem Journal', 'ru' => 'Статья по теме в нашем блоге'],

        // Projects & Case Studies
        'SEKTÖR' => ['en' => 'INDUSTRY', 'de' => 'BRANCHE', 'ru' => 'ОТРАСЛЬ'],
        'KAPSAM' => ['en' => 'SCOPE', 'de' => 'UMFANG', 'ru' => 'ОБЪЁМ РАБОТ'],
        'YIL' => ['en' => 'YEAR', 'de' => 'JAHR', 'ru' => 'ГОД'],
        'ÇALIŞMA TÜRÜ' => ['en' => 'PROJECT TYPE', 'de' => 'PROJEKTART', 'ru' => 'ТИП ПРОЕКТА'],
        'Genel bakış' => ['en' => 'Overview', 'de' => 'Überblick', 'ru' => 'Обзор'],
        'Problem' => ['en' => 'Challenge', 'de' => 'Herausforderung', 'ru' => 'Задача'],
        'Yaklaşım' => ['en' => 'Approach', 'de' => 'Ansatz', 'ru' => 'Подход'],
        'Çözüm' => ['en' => 'Solution', 'de' => 'Lösung', 'ru' => 'Решение'],
        'Sonuç' => ['en' => 'Outcome', 'de' => 'Ergebnis', 'ru' => 'Результат'],
        'Teknoloji' => ['en' => 'Technology', 'de' => 'Technologie', 'ru' => 'Технологии'],
        'İlgili uzmanlık' => ['en' => 'Related expertise', 'de' => 'Zugehörige Leistung', 'ru' => 'Профильная экспертиза'],
        'Bu deneyimin arkasındaki yaklaşım' => ['en' => 'Explore the expertise behind this concept', 'de' => 'Der Ansatz hinter diesem Erlebnis', 'ru' => 'Подход, лежащий в основе проекта'],
        'DENEYİMDEN DETAYLAR' => ['en' => 'EXPERIENCE HIGHLIGHTS', 'de' => 'DETAILS DES ERLEBNISSES', 'ru' => 'ДЕТАЛИ ИНТЕРФЕЙСА'],
        'Her ekranda aynı özen.' => ['en' => 'Precision on every screen.', 'de' => 'Präzision auf jedem Bildschirm.', 'ru' => 'Безупречность на каждом экране.'],
        '01 / MOBİL DENEYİM KONSEPTİ' => ['en' => '01 / MOBILE EXPERIENCE CONCEPT', 'de' => '01 / MOBILES ERLEBNISKONZEPT', 'ru' => '01 / КОНЦЕПЦИЯ МОБИЛЬНОГО ОПЫТА'],
        '02 / ARAYÜZ VE BİLGİ HİYERARŞİSİ' => ['en' => '02 / INTERFACE & INFORMATION ARCHITECTURE', 'de' => '02 / INTERFACE & INFORMATIONSHIERARCHIE', 'ru' => '02 / ИНТЕРФЕЙС И ИЕРАРХИЯ ДАННЫХ'],
        'SIRADAKİ ÇALIŞMA' => ['en' => 'NEXT PROJECT', 'de' => 'NÄCHSTES PROJEKT', 'ru' => 'СЛЕДУЮЩИЙ ПРОЕКТ'],
        'KONSEPT ÇALIŞMA' => ['en' => 'CONCEPT STUDY', 'de' => 'KONZEPTSTUDIE', 'ru' => 'КОНЦЕПТ-ПРОЕКТ'],
        'yaşamın doğal hali.' => ['en' => 'living in its natural form.', 'de' => 'Natürlich wohnen.', 'ru' => 'естественная эстетика жизни.'],
        'AZ. ÖZ. ZAMANSIZ.' => ['en' => 'MINIMAL. PURE. TIMELESS.', 'de' => 'KLAR. REDUZIERT. ZEITLOS.', 'ru' => 'ЛАКОНИЧНО. ВНЕ ВРЕМЕНИ.'],
        'Birlikte. Daha ileri.' => ['en' => 'Together. Further.', 'de' => 'Gemeinsam. Weiter.', 'ru' => 'Вместе. Дальше.'],
        'Tasarımdan gerçeğe' => ['en' => 'From design to reality', 'de' => 'Vom Design zur Realität', 'ru' => 'От дизайна к реальности'],
        'Yeni bir çalışma biçimi.' => ['en' => 'A new way of working.', 'de' => 'Eine neue Art zu arbeiten.', 'ru' => 'Новый формат командной работы.'],
        'FİKİRLERE YER AÇIN.' => ['en' => 'MAKE ROOM FOR IDEAS.', 'de' => 'RAUM FÜR IDEEN.', 'ru' => 'ПРОСТРАНСТВО ДЛЯ ИДЕЙ.'],
        'TOPLAM BAKİYE' => ['en' => 'TOTAL BALANCE', 'de' => 'GESAMTSALDO', 'ru' => 'ОБЩИЙ БАЛАНС'],
        'Finansın, senin elinde.' => ['en' => 'Your finances, in your hands.', 'de' => 'Ihre Finanzen in Ihrer Hand.', 'ru' => 'Ваши финансы под контролем.'],
        'DAHA NET BİR FİNANSAL GELECEK.' => ['en' => 'A CLEARER FINANCIAL FUTURE.', 'de' => 'EINE KLARERE FINANZIELLE ZUKUNFT.', 'ru' => 'ЯСНОЕ ФИНАНСОВОЕ БУДУЩЕЕ.'],

        // Blog / Journal Listing & Detail
        'Sayfa' => ['en' => 'Page', 'de' => 'Seite', 'ru' => 'Страница'],
        'Öne çıkan yazı' => ['en' => 'Featured article', 'de' => 'Empfohlener Beitrag', 'ru' => 'Избранная статья'],
        'EDİTÖRÜN SEÇİMİ / 01' => ['en' => 'EDITOR’S PICK / 01', 'de' => 'REDAKTIONSTIPP / 01', 'ru' => 'ВЫБОР РЕДАКЦИИ / 01'],
        'okuma' => ['en' => 'read', 'de' => 'Lesezeit', 'ru' => 'чтения'],
        'Yazıyı okumaya başlayın' => ['en' => 'Start reading article', 'de' => 'Beitrag jetzt lesen', 'ru' => 'Читать статью'],
        'ZEYNART’TAN NOTLAR' => ['en' => 'NOTES FROM ZEYNART', 'de' => 'NOTIZEN VON ZEYNART', 'ru' => 'ЗАМЕТКИ ZEYNART'],
        'Keşfetmeye devam edin.' => ['en' => 'Keep exploring.', 'de' => 'Weiter entdecken.', 'ru' => 'Продолжайте изучать.'],
        'Yazılarda ara' => ['en' => 'Search articles', 'de' => 'Beiträge durchsuchen', 'ru' => 'Поиск по статьям'],
        'Bir konu arayın…' => ['en' => 'Search a topic…', 'de' => 'Thema suchen…', 'ru' => 'Поиск по темам…'],
        'Yazı kategorileri' => ['en' => 'Article categories', 'de' => 'Beitragskategorien', 'ru' => 'Категории статей'],
        'yazı · Tasarım, teknoloji ve dijital üzerine' => [
            'en' => 'articles · On design, technology & digital craft',
            'de' => 'Beiträge · Über Design, Technologie & Digitales',
            'ru' => 'статей · О дизайне, технологиях и цифровой среде',
        ],
        'Yazıyı keşfet' => ['en' => 'Read article', 'de' => 'Beitrag lesen', 'ru' => 'Читать далее'],
        'Önceki sayfa' => ['en' => 'Previous page', 'de' => 'Vorherige Seite', 'ru' => 'Предыдущая страница'],
        'Daha fazla yazı yükle' => ['en' => 'Load more articles', 'de' => 'Mehr Beiträge laden', 'ru' => 'Загрузить ещё статьи'],
        'Aramanızla eşleşen yazı bulunamadı.' => [
            'en' => 'No articles matched your search.',
            'de' => 'Keine passenden Beiträge gefunden.',
            'ru' => 'По вашему запросу статей не найдено.',
        ],
        'ZEYNART JOURNAL’A DÖN' => ['en' => 'BACK TO ZEYNART JOURNAL', 'de' => 'ZURÜCK ZUM ZEYNART JOURNAL', 'ru' => 'НАЗАД В ZEYNART JOURNAL'],
        'Tasarım & teknoloji notları' => ['en' => 'Design & technology insights', 'de' => 'Design- & Technologie-Einblicke', 'ru' => 'Заметки о дизайне и технологиях'],
        'BU YAZIDA' => ['en' => 'IN THIS ARTICLE', 'de' => 'IN DIESEM BEITRAG', 'ru' => 'СОДЕРЖАНИЕ'],
        'Yazı içindekiler' => ['en' => 'Table of contents', 'de' => 'Inhaltsverzeichnis', 'ru' => 'Оглавление статьи'],
        'Hızlı Özet' => ['en' => 'Quick Summary', 'de' => 'Kurzfassung', 'ru' => 'Краткое резюме'],
        'KISA ÖZET (AI / HIZLI BAKIŞ)' => ['en' => 'TL;DR (AI QUICK SUMMARY)', 'de' => 'KURZFASSUNG (KI-ÜBERBLICK)', 'ru' => 'КРАТКО (AI-РЕЗЮМЕ)'],
        'Sıkça sorulan sorular' => ['en' => 'Frequently asked questions', 'de' => 'Häufig gestellte Fragen', 'ru' => 'Часто задаваемые вопросы'],
        'Sıkça sorulan sorular.' => ['en' => 'Frequently asked questions.', 'de' => 'Häufig gestellte Fragen.', 'ru' => 'Часто задаваемые вопросы.'],
        'ZEYNART’TAN BİR NOT' => ['en' => 'A NOTE FROM ZEYNART', 'de' => 'EINE NOTIZ VON ZEYNART', 'ru' => 'ЗАМЕТКА ОТ ZEYNART'],
        'Her işletmenin ihtiyacı farklı. Bu fikirleri kendi projenize nasıl uyarlayabileceğinizi birlikte değerlendirelim.' => [
            'en' => 'Every business is unique. Let’s explore how we can tailor these ideas to your own digital project.',
            'de' => 'Jedes Unternehmen hat eigene Anforderungen. Lassen Sie uns gemeinsam prüfen, wie wir diese Ideen für Ihr Projekt umsetzen können.',
            'ru' => 'У каждого бизнеса свои задачи. Давайте обсудим, как адаптировать эти решения для вашего проекта.',
        ],
        'Tüm yazılara dön' => ['en' => 'Back to all articles', 'de' => 'Zurück zu allen Beiträgen', 'ru' => 'Ко всем статьям'],
        'BİR SONRAKİ FİKİR' => ['en' => 'NEXT INSIGHT', 'de' => 'NÄCHSTE INSPIRATION', 'ru' => 'ЧИТАЙТЕ ТАКЖЕ'],
        'Bunlar da ilginizi çekebilir.' => ['en' => 'You might also like.', 'de' => 'Das könnte Sie auch interessieren.', 'ru' => 'Вам также может быть интересно.'],
        'Tüm yazılar' => ['en' => 'All articles', 'de' => 'Alle Beiträge', 'ru' => 'Все статьи'],
        'Okumaya devam et' => ['en' => 'Continue reading', 'de' => 'Weiterlesen', 'ru' => 'Читать далее'],

        // Contact Page & Form
        'TELEFON' => ['en' => 'PHONE', 'de' => 'TELEFON', 'ru' => 'ТЕЛЕФОН'],
        'E-POSTA' => ['en' => 'EMAIL', 'de' => 'E-MAIL', 'ru' => 'ЭЛ. ПОЧТА'],
        'Ad soyad *' => ['en' => 'Full name *', 'de' => 'Vor- und Nachname *', 'ru' => 'Имя и фамилия *'],
        'Adınız ve soyadınız' => ['en' => 'Your full name', 'de' => 'Ihr Vor- und Nachname', 'ru' => 'Ваше имя и фамилия'],
        'Cep telefonu *' => ['en' => 'Mobile phone *', 'de' => 'Mobiltelefon *', 'ru' => 'Мобильный телефон *'],
        'Cep telefonu' => ['en' => 'Mobile phone', 'de' => 'Mobiltelefon', 'ru' => 'Мобильный телефон'],
        '05XX XXX XX XX' => ['en' => '+90 5XX XXX XX XX', 'de' => '+49 1XX XXXXXXX', 'ru' => '+7 9XX XXX-XX-XX'],
        'E-posta adresiniz *' => ['en' => 'Email address *', 'de' => 'Ihre E-Mail-Adresse *', 'ru' => 'Ваш email *'],
        'siz@sirketiniz.com' => ['en' => 'you@company.com', 'de' => 'name@unternehmen.de', 'ru' => 'you@company.com'],
        'Şirket / kurum' => ['en' => 'Company / organization', 'de' => 'Unternehmen / Organisation', 'ru' => 'Компания / организация'],
        'Şirket veya kurum adı' => ['en' => 'Company or brand name', 'de' => 'Name des Unternehmens', 'ru' => 'Название компании'],
        'Hizmet alanı *' => ['en' => 'Service area *', 'de' => 'Leistungsbereich *', 'ru' => 'Направление услуг *'],
        'İlgilendiğiniz alanı seçin' => ['en' => 'Select a service area', 'de' => 'Bitte Bereich wählen', 'ru' => 'Выберите направление'],
        'Birlikte belirleyelim' => ['en' => 'Let’s decide together', 'de' => 'Gemeinsam festlegen', 'ru' => 'Определим вместе'],
        'Bütçe aralığınız' => ['en' => 'Estimated budget', 'de' => 'Budgetrahmen', 'ru' => 'Ориентировочный бюджет'],
        'Proje kapsamı ve hedefleriniz *' => ['en' => 'Project scope & goals *', 'de' => 'Projektumfang & Ziele *', 'ru' => 'Задачи и описание проекта *'],
        'İş hedefleriniz, ihtiyaç duyduğunuz çözüm ve öngördüğünüz proje takvimi hakkında bilgi paylaşabilirsiniz.' => [
            'en' => 'Share your business goals, desired solution, and expected timeline.',
            'de' => 'Beschreiben Sie kurz Ihre Ziele, die gewünschte Lösung und Ihren Zeitplan.',
            'ru' => 'Расскажите о целях бизнеса, необходимом решении и желаемых сроках.',
        ],
        'Proje hedeflerinizi kısaca paylaşın.' => ['en' => 'Briefly share your project goals.', 'de' => 'Teilen Sie kurz Ihre Projektziele.', 'ru' => 'Кратко опишите цели вашего проекта.'],
        'Gizlilik bilgilendirmesini' => ['en' => 'Privacy Policy', 'de' => 'Datenschutzerklärung', 'ru' => 'Политику конфиденциальности'],
        'okudum. Projem hakkında iletişime geçilmesini kabul ediyorum.' => [
            'en' => '— I have read it and consent to being contacted regarding my project.',
            'de' => 'habe ich gelesen und stimme der Kontaktaufnahme zu meinem Projekt zu.',
            'ru' => 'прочитал(а) и соглашаюсь на обратную связь по моему проекту.',
        ],
        'okudum ve kabul ediyorum.' => ['en' => '— I have read and accept.', 'de' => 'gelesen und akzeptiert.', 'ru' => 'прочитал(а) и принимаю.'],
        'Proje talebini gönder' => ['en' => 'Send project inquiry', 'de' => 'Projektanfrage senden', 'ru' => 'Отправить заявку'],
        'Gönderiliyor…' => ['en' => 'Sending…', 'de' => 'Wird gesendet…', 'ru' => 'Отправка…'],
        'Proje talebiniz başarıyla alındı. Ekibimiz en kısa sürede sizinle iletişime geçecektir.' => [
            'en' => 'Your project inquiry has been received. Our team will get back to you shortly.',
            'de' => 'Ihre Projektanfrage ist erfolgreich eingegangen. Unser Team meldet sich in Kürze bei Ihnen.',
            'ru' => 'Ваша заявка успешно получена. Наша команда свяжется с вами в ближайшее время.',
        ],
        'Talebiniz gönderilirken bir sorun oluştu. Lütfen alanları kontrol edip tekrar deneyin.' => [
            'en' => 'Something went wrong while sending your request. Please check the fields and try again.',
            'de' => 'Beim Senden Ihrer Anfrage ist ein Fehler aufgetreten. Bitte prüfen Sie Ihre Eingaben.',
            'ru' => 'При отправке заявки произошла ошибка. Пожалуйста, проверьте поля и попробуйте снова.',
        ],
        'Bağlantı hatası oluştu. Lütfen tekrar deneyin.' => [
            'en' => 'Connection error. Please try again.',
            'de' => 'Verbindungsfehler. Bitte versuchen Sie es erneut.',
            'ru' => 'Ошибка соединения. Пожалуйста, попробуйте ещё раз.',
        ],
        'Geçersiz istek yöntemi.' => ['en' => 'Invalid request method.', 'de' => 'Ungültige Anfragemethode.', 'ru' => 'Недопустимый метод запроса.'],
        'Talebiniz alındı, teşekkür ederiz.' => ['en' => 'Thank you, your inquiry has been received.', 'de' => 'Vielen Dank, Ihre Anfrage ist eingegangen.', 'ru' => 'Спасибо, ваша заявка принята.'],
        'Güvenlik doğrulaması süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.' => [
            'en' => 'Security session expired. Please refresh the page and try again.',
            'de' => 'Sicherheitsüberprüfung abgelaufen. Bitte laden Sie die Seite neu.',
            'ru' => 'Сессия безопасности истекла. Обновите страницу и попробуйте снова.',
        ],
        'Çok fazla talep gönderildi. Lütfen birkaç dakika sonra tekrar deneyin.' => [
            'en' => 'Too many requests sent. Please try again in a few minutes.',
            'de' => 'Zu viele Anfragen. Bitte versuchen Sie es in einigen Minuten erneut.',
            'ru' => 'Слишком много запросов. Повторите попытку через несколько минут.',
        ],
        'Lütfen geçerli bir ad ve soyad girin.' => ['en' => 'Please enter a valid full name.', 'de' => 'Bitte geben Sie Ihren vollständigen Namen ein.', 'ru' => 'Пожалуйста, укажите имя и фамилию.'],
        'Lütfen geçerli bir cep telefonu numarası girin.' => ['en' => 'Please enter a valid mobile phone number.', 'de' => 'Bitte geben Sie eine gültige Mobiltelefonnummer ein.', 'ru' => 'Пожалуйста, введите корректный номер мобильного телефона.'],
        'Lütfen geçerli bir e-posta adresi girin.' => ['en' => 'Please enter a valid email address.', 'de' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'ru' => 'Пожалуйста, введите корректный email.'],
        'Lütfen ilgilendiğiniz hizmet alanını seçin.' => ['en' => 'Please select a service area.', 'de' => 'Bitte wählen Sie einen Leistungsbereich aus.', 'ru' => 'Пожалуйста, выберите направление услуг.'],
        'Lütfen projeniz hakkında en az 10 karakterlik kısa bir bilgi paylaşın.' => [
            'en' => 'Please share a brief description of your project (at least 10 characters).',
            'de' => 'Bitte beschreiben Sie Ihr Projekt kurz (mindestens 10 Zeichen).',
            'ru' => 'Пожалуйста, кратко опишите проект (не менее 10 символов).',
        ],
        'Lütfen gizlilik bilgilendirmesini onaylayın.' => ['en' => 'Please accept the privacy policy.', 'de' => 'Bitte bestätigen Sie die Datenschutzerklärung.', 'ru' => 'Пожалуйста, подтвердите согласие с политикой конфиденциальности.'],

        // 404 Page
        '404 — Sayfa Bulunamadı | ZEYNART' => ['en' => '404 — Page Not Found | ZEYNART', 'de' => '404 — Seite nicht gefunden | ZEYNART', 'ru' => '404 — Страница не найдена | ZEYNART'],
        'Aradığınız sayfa taşınmış veya hiç var olmamış olabilir.' => [
            'en' => 'The page you are looking for may have been moved or no longer exists.',
            'de' => 'Die gesuchte Seite wurde möglicherweise verschoben oder existiert nicht mehr.',
            'ru' => 'Запрашиваемая страница была перемещена или никогда не существовала.',
        ],
        '404 — YENİ BİR YÖN' => ['en' => '404 — A NEW DIRECTION', 'de' => '404 — EINE NEUE RICHTUNG', 'ru' => '404 — НОВОЕ НАПРАВЛЕНИЕ'],
        'Bu yol burada bitiyor.' => ['en' => 'This path ends here.', 'de' => 'Dieser Weg endet hier.', 'ru' => 'Этот путь заканчивается здесь.'],
        'Güzel fikirler için ana sayfada buluşalım.' => ['en' => 'Let’s meet on the homepage for great ideas.', 'de' => 'Treffen wir uns auf der Startseite für neue Ideen.', 'ru' => 'Встретимся на главной странице для новых идей.'],
        'Ana sayfaya dön' => ['en' => 'Back to homepage', 'de' => 'Zur Startseite', 'ru' => 'На главную'],

        // Google Reviews Widget
        'MÜKEMMEL' => ['en' => 'EXCELLENT', 'de' => 'AUSGEZEICHNET', 'ru' => 'ОТЛИЧНО'],
        'ÇOK İYİ' => ['en' => 'VERY GOOD', 'de' => 'SEHR GUT', 'ru' => 'ОЧЕНЬ ХОРОШО'],
        'OLUMLU' => ['en' => 'POSITIVE', 'de' => 'POSITIV', 'ru' => 'ХОРОШО'],
        'Google Değerlendirmeleri' => ['en' => 'Google Reviews', 'de' => 'Google-Bewertungen', 'ru' => 'Отзывы Google'],
        'yorum' => ['en' => 'reviews', 'de' => 'Bewertungen', 'ru' => 'отзывов'],
        'Google’da Yorum Yazın' => ['en' => 'Write a Review on Google', 'de' => 'Auf Google bewerten', 'ru' => 'Оставить отзыв в Google'],
        'Bize Google’da Yorum Yazın' => ['en' => 'Review Us on Google', 'de' => 'Bewerten Sie uns auf Google', 'ru' => 'Оцените нас в Google'],
        'Doğrulanmış Google Değerlendirmesi' => ['en' => 'Verified Google Review', 'de' => 'Verifizierte Google-Bewertung', 'ru' => 'Подтверждённый отзыв Google'],
        'Doğrulanmış' => ['en' => 'Verified', 'de' => 'Verifiziert', 'ru' => 'Подтверждено'],
        'Devamını oku' => ['en' => 'Read more', 'de' => 'Mehr lesen', 'ru' => 'Читать далее'],
        'Daha az göster' => ['en' => 'Show less', 'de' => 'Weniger anzeigen', 'ru' => 'Свернуть'],
        'İşletme Yanıtı' => ['en' => 'Owner’s Reply', 'de' => 'Antwort des Inhabers', 'ru' => 'Ответ компании'],
        'Yanıtın tamamı' => ['en' => 'Full reply', 'de' => 'Ganze Antwort', 'ru' => 'Весь ответ'],
        'Yanıtı daralt' => ['en' => 'Collapse reply', 'de' => 'Antwort einklappen', 'ru' => 'Свернуть ответ'],
        'Google İşletme Profili' => ['en' => 'Google Business Profile', 'de' => 'Google-Unternehmensprofil', 'ru' => 'Профиль компании в Google'],
        'Önceki yorumlar' => ['en' => 'Previous reviews', 'de' => 'Vorherige Bewertungen', 'ru' => 'Предыдущие отзывы'],
        'Sonraki yorumlar' => ['en' => 'Next reviews', 'de' => 'Nächste Bewertungen', 'ru' => 'Следующие отзывы'],
        'Tümünü Sitede Aç' => ['en' => 'Expand All Reviews', 'de' => 'Alle hier anzeigen', 'ru' => 'Показать все на сайте'],
        'Karusel Görünümüne Dön' => ['en' => 'Back to Carousel', 'de' => 'Zurück zum Karussell', 'ru' => 'Вернуться к карусели'],
        'Google üzerinde görüntüle' => ['en' => 'View on Google', 'de' => 'Auf Google ansehen', 'ru' => 'Открыть в Google'],

        // Smart Quick Contact Bar (WhatsApp + Quick Call)
        'Hızlı iletişim kanalları' => ['en' => 'Quick contact channels', 'de' => 'Schnellkontakt-Kanäle', 'ru' => 'Быстрая связь'],
        'Hızlı Arama' => ['en' => 'Quick Call', 'de' => 'Direktanruf', 'ru' => 'Быстрый звонок'],
        'Hızlı Mesaj' => ['en' => 'Instant Chat', 'de' => 'Direktchat', 'ru' => 'Быстрый чат'],
        'Çevrimiçi · Hızlı Yanıt' => ['en' => 'Online · Fast Reply', 'de' => 'Online · Schnelle Antwort', 'ru' => 'Онлайн · Быстрый ответ'],
        'Mesai Dışı · Mesaj Bırakın' => ['en' => 'After Hours · Leave a Message', 'de' => 'Außerhalb der Geschäftszeiten · Nachricht senden', 'ru' => 'Вне рабочего времени · Оставьте сообщение'],

        // Site Speed & SEO Pre-Audit Lead Tool
        'Site Hızı & SEO Analiz' => ['en' => 'Site Speed & SEO Audit', 'de' => 'Website-Speed & SEO-Analyse', 'ru' => 'Анализ скорости и SEO сайта'],
        'ÜCRETSİZ SİTE HIZI & SEO ÖN ANALİZ ARACI' => [
            'en' => 'FREE WEBSITE SPEED & SEO PRE-AUDIT TOOL',
            'de' => 'KOSTENLOSER WEBSITE-SPEED- & SEO-VORABCHECK',
            'ru' => 'БЕСПЛАТНЫЙ ЭКСПРЕСС-АНАЛИЗ СКОРОСТИ И SEO САЙТА',
        ],
        'Web sitenizin karnesini' => ['en' => 'See your website’s scorecard', 'de' => 'Der Performance-Bericht Ihrer Website', 'ru' => 'Проверьте показатели вашего сайта'],
        'saniyeler içinde görün.' => ['en' => 'in seconds.', 'de' => 'in wenigen Sekunden.', 'ru' => 'всего за несколько секунд.'],
        'Mevcut web sitenizin adresini girin; SSL güvenliği, sunucu yanıt hızı, mobil uyumluluk ve temel SEO eksiklerini anında analiz edelim.' => [
            'en' => 'Enter your current website URL—we will instantly inspect SSL security, server response latency, mobile usability, and core SEO gaps.',
            'de' => 'Geben Sie Ihre aktuelle Website-Adresse ein – wir prüfen sofort SSL-Sicherheit, Servergeschwindigkeit, Mobilfreundlichkeit und Basis-SEO.',
            'ru' => 'Введите адрес вашего текущего сайта — мы мгновенно проверим SSL-безопасность, скорость ответа сервера, мобильную адаптацию и базовое SEO.',
        ],
        'ornekfirma.com veya https://siteniz.com' => [
            'en' => 'yourcompany.com or https://yoursite.com',
            'de' => 'ihre-firma.de oder https://ihreseite.de',
            'ru' => 'company.com или https://yoursite.com',
        ],
        'Ücretsiz Analiz Et' => ['en' => 'Run Free Audit', 'de' => 'Kostenlos prüfen', 'ru' => 'Проверить бесплатно'],
        'HEDEF: 99+ PERFORMANS & YÜKSEK DÖNÜŞÜM' => [
            'en' => 'GOAL: 99+ PERFORMANCE & HIGH CONVERSION',
            'de' => 'ZIEL: 99+ PERFORMANCE & STARKE CONVERSION',
            'ru' => 'ЦЕЛЬ: ПРОИЗВОДИТЕЛЬНОСТЬ 99+ И КОНВЕРСИЯ',
        ],
        'Zeynart ile sitenizi 99+ performansa taşıyalım' => [
            'en' => 'Let’s take your website to 99+ performance with Zeynart',
            'de' => 'Bringen wir Ihre Website mit Zeynart auf 99+ Performance',
            'ru' => 'Выведем ваш сайт на 99+ производительности вместе с Zeynart',
        ],
        'Analizde tespit edilen teknik eksikleri giderip sitenizi hız, mobil deneyim ve arama motorlarında üst sıralara taşıyacak yol haritasını ücretsiz paylaşalım.' => [
            'en' => 'Let us share a free tailored roadmap to fix the detected technical gaps and elevate your site in speed, mobile UX, and Google rankings.',
            'de' => 'Wir teilen kostenlos einen maßgeschneiderten Fahrplan, um erkannte Technik-Lücken zu schließen und Ihr Google-Ranking nachhaltig zu steigern.',
            'ru' => 'Мы бесплатно подготовим персональный план устранения технических недочётов и вывода сайта в топ по скорости, мобильному UX и SEO.',
        ],
        'Ücretsiz İyileştirme Planı Al' => ['en' => 'Get Free Optimization Plan', 'de' => 'Kostenlosen Optimierungsplan anfordern', 'ru' => 'Получить бесплатный план улучшения'],
        'Ücretsiz Site Hızı & SEO Ön Analiz Aracı | ZEYNART' => [
            'en' => 'Free Website Speed & SEO Pre-Audit Tool | ZEYNART',
            'de' => 'Kostenloses Website-Speed- & SEO-Analyse-Tool | ZEYNART',
            'ru' => 'Бесплатный экспресс-анализ скорости и SEO сайта | ZEYNART',
        ],
        'Mevcut web sitenizin SSL, sunucu hızı (TTFB), mobil uyumluluk ve temel SEO eksiklerini saniyeler içinde ücretsiz analiz edin; Zeynart ile 99+ performansa taşıyın.' => [
            'en' => 'Analyze your website’s SSL security, server TTFB speed, mobile usability, and core SEO gaps in seconds—and scale to 99+ performance with Zeynart.',
            'de' => 'Analysieren Sie SSL, Servergeschwindigkeit (TTFB), Mobilfreundlichkeit und SEO-Lücken Ihrer Website in Sekunden – für 99+ Performance mit Zeynart.',
            'ru' => 'Проанализируйте SSL, скорость ответа сервера (TTFB), мобильную адаптацию и базовое SEO вашего сайта за секунды — выведем на 99+ с Zeynart.',
        ],
        'Kontrol edilen alanlar' => ['en' => 'Audited pillars', 'de' => 'Geprüfte Bereiche', 'ru' => 'Проверяемые параметры'],
        'SSL & Güvenlik' => ['en' => 'SSL & Security', 'de' => 'SSL & Sicherheit', 'ru' => 'SSL и безопасность'],
        'Sunucu & Açılış Hızı' => ['en' => 'Server & Load Speed', 'de' => 'Server- & Ladezeit', 'ru' => 'Скорость сервера'],
        'Hız & Performans' => ['en' => 'Speed & Performance', 'de' => 'Speed & Performance', 'ru' => 'Скорость и производительность'],
        'Mobil Uyumluluk' => ['en' => 'Mobile Usability', 'de' => 'Mobilfreundlichkeit', 'ru' => 'Мобильная адаптация'],
        'Temel Meta & AI SEO' => ['en' => 'Core Meta & AI SEO', 'de' => 'Basis-Meta & KI-SEO', 'ru' => 'Базовое и ИИ-SEO'],
        'Temel Meta & SEO' => ['en' => 'Core Meta & SEO', 'de' => 'Basis-Meta & SEO', 'ru' => 'Мета-теги и SEO'],
        'Web sitenizin adresi' => ['en' => 'Your website URL', 'de' => 'Ihre Website-Adresse', 'ru' => 'Адрес вашего сайта'],
        'Web siteniz canlı olarak taranıyor…' => [
            'en' => 'Scanning your website in real time…',
            'de' => 'Ihre Website wird live geprüft…',
            'ru' => 'Выполняется сканирование вашего сайта…',
        ],
        'SSL sertifikası, sunucu TTFB hızı, mobil viewport ve SEO meta etiketleri inceleniyor.' => [
            'en' => 'Inspecting SSL certificate, server TTFB latency, mobile viewport, and SEO meta tags.',
            'de' => 'SSL-Zertifikat, Server-TTFB, mobiler Viewport und SEO-Meta-Tags werden analysiert.',
            'ru' => 'Проверяем SSL-сертификат, скорость TTFB сервера, мобильный viewport и мета-теги SEO.',
        ],
        'Teknik Ön Analiz Karnesi' => ['en' => 'Technical Pre-Audit Scorecard', 'de' => 'Technischer Vorab-Bericht', 'ru' => 'Техническая карта экспресс-анализа'],
        'Tespit Edilen Eksikler & İyileştirme Alanları' => [
            'en' => 'Detected Issues & Growth Opportunities',
            'de' => 'Erkannte Mängel & Optimierungspotenziale',
            'ru' => 'Выявленные недочёты и точки роста',
        ],
        'Doğrulanan Güçlü Noktalar' => ['en' => 'Verified Passed Checks', 'de' => 'Bestätigte Stärken', 'ru' => 'Успешно пройденные проверки'],
        'Telefon / WhatsApp *' => ['en' => 'Phone / WhatsApp *', 'de' => 'Telefon / WhatsApp *', 'ru' => 'Телефон / WhatsApp *'],
        'Eklemek istediğiniz not (opsiyonel)' => ['en' => 'Additional note (optional)', 'de' => 'Zusätzliche Notiz (optional)', 'ru' => 'Комментарий (необязательно)'],
        'Ör: Yenileme, hızlandırma veya SEO teklifi istiyorum' => [
            'en' => 'e.g. Looking for a redesign, speed boost, or SEO quote',
            'de' => 'z. B. Interesse an Relaunch, Speed-Optimierung oder SEO',
            'ru' => 'Напр.: интересует редизайн, ускорение или SEO-продвижение',
        ],
        'WhatsApp ile Raporu Gönder' => ['en' => 'Send Report via WhatsApp', 'de' => 'Bericht per WhatsApp senden', 'ru' => 'Отправить отчёт в WhatsApp'],
        'Site analiz aracı şu anda geçici olarak kapalıdır.' => [
            'en' => 'The site audit tool is temporarily unavailable.',
            'de' => 'Das Analyse-Tool ist vorübergehend deaktiviert.',
            'ru' => 'Инструмент анализа временно недоступен.',
        ],
        'Analiz tamamlanamadı.' => ['en' => 'Audit could not be completed.', 'de' => 'Analyse konnte nicht abgeschlossen werden.', 'ru' => 'Не удалось завершить анализ.'],
        'Lütfen geçerli bir telefon veya WhatsApp numarası girin.' => [
            'en' => 'Please enter a valid phone or WhatsApp number.',
            'de' => 'Bitte geben Sie eine gültige Telefon- oder WhatsApp-Nummer ein.',
            'ru' => 'Пожалуйста, укажите корректный номер телефона или WhatsApp.',
        ],
        'Harika! Sitenizin analiz raporu ve iletişim bilgileriniz ekibimize ulaştı. Sitenizi 99+ performansa taşıyacak yol haritasıyla en kısa sürede dönüş yapacağız.' => [
            'en' => 'Awesome! Your site audit report and contact details have reached our team. We will get back to you shortly with a roadmap to 99+ performance.',
            'de' => 'Vielen Dank! Ihr Analysebericht und Ihre Kontaktdaten sind bei uns eingegangen. Wir melden uns in Kürze mit Ihrem Fahrplan für 99+ Performance.',
            'ru' => 'Отлично! Отчёт по вашему сайту и контактные данные получены. Мы свяжемся с вами в ближайшее время с планом выхода на 99+ производительности.',
        ],
        'Çerez Politikası' => ['en' => 'Cookie Policy', 'de' => 'Cookie-Richtlinie', 'ru' => 'Политика использования файлов cookie'],
        'Çerez Tercihleri' => ['en' => 'Cookie Preferences', 'de' => 'Cookie-Einstellungen', 'ru' => 'Настройки файлов cookie'],

        // Blog Article Reader Social Share Bar
        'BU YAZIYI PAYLAŞIN' => ['en' => 'SHARE THIS ARTICLE', 'de' => 'BEITRAG TEILEN', 'ru' => 'ПОДЕЛИТЬСЯ СТАТЬЁЙ'],
        'İçeriği faydalı bulduysanız ekibinizle veya sosyal ağlarınızda paylaşabilirsiniz.' => [
            'en' => 'Found this article helpful? Share it with your team or network.',
            'de' => 'War dieser Beitrag hilfreich? Teilen Sie ihn gerne mit Ihrem Team oder Netzwerk.',
            'ru' => 'Статья оказалась полезной? Поделитесь ею с коллегами или в соцсетях.',
        ],
        'Bağlantıyı Kopyala' => ['en' => 'Copy Link', 'de' => 'Link kopieren', 'ru' => 'Скопировать ссылку'],
        'Kopyalandı ✓' => ['en' => 'Copied ✓', 'de' => 'Kopiert ✓', 'ru' => 'Скопировано ✓'],
        'Paylaş' => ['en' => 'Share', 'de' => 'Teilen', 'ru' => 'Поделиться'],
        'E-posta' => ['en' => 'Email', 'de' => 'E-Mail', 'ru' => 'Email'],
        'WhatsApp ile paylaş' => ['en' => 'Share on WhatsApp', 'de' => 'Per WhatsApp teilen', 'ru' => 'Поделиться в WhatsApp'],
        'X üzerinde paylaş' => ['en' => 'Share on X', 'de' => 'Auf X teilen', 'ru' => 'Поделиться в X'],
        'LinkedIn üzerinde paylaş' => ['en' => 'Share on LinkedIn', 'de' => 'Auf LinkedIn teilen', 'ru' => 'Поделиться в LinkedIn'],
        'Facebook üzerinde paylaş' => ['en' => 'Share on Facebook', 'de' => 'Auf Facebook teilen', 'ru' => 'Поделиться в Facebook'],
        'Telegram ile paylaş' => ['en' => 'Share on Telegram', 'de' => 'Per Telegram teilen', 'ru' => 'Поделиться в Telegram'],
        'E-posta ile paylaş' => ['en' => 'Share via Email', 'de' => 'Per E-Mail teilen', 'ru' => 'Отправить по Email'],
        'OKUMA İLERLEMESİ' => ['en' => 'READING PROGRESS', 'de' => 'LESEFORTSCHRITT', 'ru' => 'ПРОГРЕСС ЧТЕНИЯ'],
        'BÖLÜM' => ['en' => 'SECTION', 'de' => 'ABSCHNITT', 'ru' => 'РАЗДЕЛ'],
        'İLGİLİ HİZMET' => ['en' => 'RELATED SERVICE', 'de' => 'PASSENDE LEISTUNG', 'ru' => 'УСЛУГА ПО ТЕМЕ'],
        'Ücretsiz Ön Görüşme' => ['en' => 'Free Consultation', 'de' => 'Kostenloses Erstgespräch', 'ru' => 'Бесплатная консультация'],
        'Ücretsiz Site Analizi' => ['en' => 'Free Site Audit', 'de' => 'Kostenlose Website-Analyse', 'ru' => 'Бесплатный аудит сайта'],
        'Kopyala' => ['en' => 'Copy', 'de' => 'Kopieren', 'ru' => 'Копировать'],
        'Tasarım & Teknoloji Stüdyosu' => ['en' => 'Design & Technology Studio', 'de' => 'Design- & Technologiestudio', 'ru' => 'Студия дизайна и технологий'],
        'SIKÇA SORULAN SORULAR' => ['en' => 'FREQUENTLY ASKED QUESTIONS', 'de' => 'HÄUFIGE FRAGEN', 'ru' => 'ЧАСТЫЕ ВОПРОСЫ'],
    ];

    foreach (glob(__DIR__ . '/i18n_*_dict.php') ?: [] as $extDictFile) {
        $extDict = include $extDictFile;
        if (is_array($extDict)) {
            foreach ($extDict as $k => $v) {
                if (is_string($k) && is_array($v)) {
                    $dict[$k] = $v;
                }
            }
        }
    }
    if (function_exists('zeynart_custom_ui_dictionary')) {
        $extraDict = zeynart_custom_ui_dictionary();
        if (is_array($extraDict)) {
            foreach ($extraDict as $k => $v) {
                if (is_string($k) && is_array($v)) {
                    $dict[$k] = $v;
                }
            }
        }
    }

    return $dict;
}

function t(string $trText, ?string $lang = null): string
{
    $code = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    if ($code === 'tr' || $trText === '') {
        return $trText;
    }
    $dict = zeynart_ui_dictionary();
    if (isset($dict[$trText][$code])) {
        return $dict[$trText][$code];
    }
    return $trText;
}

function t_category(string $category, ?string $lang = null): string
{
    $code = $lang !== null ? strtolower(trim($lang)) : zeynart_current_lang();
    if ($code === 'tr' || $category === '') {
        return $category;
    }
    $trimmed = trim($category);
    $up = mb_strtoupper($trimmed, 'UTF-8');
    $isAllCaps = ($trimmed === $up && mb_strtolower($trimmed, 'UTF-8') !== $trimmed);
    $titleMap = [
        'TÜMÜ' => ['en' => 'All', 'de' => 'Alle', 'ru' => 'Все'],
        'WEB TASARIM' => ['en' => 'Web Design', 'de' => 'Webdesign', 'ru' => 'Веб-дизайн'],
        'GRAFİK TASARIM' => ['en' => 'Graphic Design', 'de' => 'Grafikdesign', 'ru' => 'Графический дизайн'],
        'E-TİCARET' => ['en' => 'E-Commerce', 'de' => 'E-Commerce', 'ru' => 'Электронная коммерция'],
        'HOSTING & ALTYAPI' => ['en' => 'Hosting & Cloud', 'de' => 'Hosting & Infrastruktur', 'ru' => 'Хостинг и инфраструктура'],
        'ÜRÜN STRATEJİSİ' => ['en' => 'Product Strategy', 'de' => 'Produktstrategie', 'ru' => 'Продуктовая стратегия'],
        'YAPAY ZEKA' => ['en' => 'Artificial Intelligence', 'de' => 'Künstliche Intelligenz', 'ru' => 'Искусственный интеллект'],
        'TASARIM & TEKNOLOJİ' => ['en' => 'Design & Tech', 'de' => 'Design & Technologie', 'ru' => 'Дизайн и технологии'],
        'GENEL' => ['en' => 'General', 'de' => 'Allgemein', 'ru' => 'Общее'],
        'BLOG' => ['en' => 'Blog', 'de' => 'Blog', 'ru' => 'Блог'],
        'WEB' => ['en' => 'Web', 'de' => 'Web', 'ru' => 'Веб'],
        'WEB YAZILIM' => ['en' => 'Web Software', 'de' => 'Websoftware', 'ru' => 'Веб-разработка'],
        'MOBİL UYGULAMA' => ['en' => 'Mobile Apps', 'de' => 'Mobile Apps', 'ru' => 'Мобильные приложения'],
        'SEO' => ['en' => 'SEO', 'de' => 'SEO', 'ru' => 'SEO'],
        'DİJİTAL PAZARLAMA' => ['en' => 'Digital Marketing', 'de' => 'Digitales Marketing', 'ru' => 'Цифровой маркетинг'],
    ];
    if (isset($titleMap[$up][$code])) {
        return $isAllCaps ? mb_strtoupper($titleMap[$up][$code], 'UTF-8') : $titleMap[$up][$code];
    }
    return $category;
}

/**
 * Localizes $store (Menus, Homepage, About, Services, Projects, Contact, Legal, Custom Pages, SEO)
 * into EN / DE / RU with atomic disk caching in data/i18n_cache/store_{lang}.json.
 */
function zeynart_localize_store(array $store, string $lang): array
{
    $lang = strtolower(trim($lang));
    if ($lang === 'tr' || !in_array($lang, ['en', 'de', 'ru'], true)) {
        return $store;
    }

    static $memCache = [];
    $cacheVersion = 'v3|' . (string)($store['updated_at'] ?? 'init');
    $hash = md5($cacheVersion);

    if (isset($memCache[$lang]) && ($memCache[$lang]['_hash'] ?? '') === $hash) {
        return $memCache[$lang]['store'];
    }

    $cacheDir = DATA_DIR . '/i18n_cache';
    $cacheFile = $cacheDir . '/store_' . $lang . '.json';
    $sectionsToLocalize = [
        'site', 'menus', 'cta_global', 'seo', 'home', 'about',
        'services_page', 'services', 'projects_page', 'projects',
        'blog_settings', 'blog_categories', 'contact', 'custom_pages',
        'core_page_blocks', 'google_reviews', 'quick_contact', 'site_audit',
    ];

    $decoded = zeynart_read_json_data($cacheFile);
    if (is_array($decoded) && ($decoded['_hash'] ?? '') === $hash && is_array($decoded['sections'] ?? null)) {
        $localized = $store;
        foreach ($decoded['sections'] as $secKey => $secVal) {
            $localized[$secKey] = $secVal;
        }
        if (!isset($decoded['sections']['blog_categories']) && is_array($store['blog_categories'] ?? null)) {
            $fastUiDict = zeynart_ui_dictionary();
            $localized['blog_categories'] = array_map(static function ($catRow) use ($fastUiDict, $lang) {
                if (!is_array($catRow)) {
                    return $catRow;
                }
                if (!empty($catRow['name'])) {
                    $catRow['name'] = t_category((string)$catRow['name'], $lang);
                }
                foreach (['description', 'meta_title', 'meta_desc'] as $ck) {
                    $cv = (string)($catRow[$ck] ?? '');
                    if ($cv !== '' && isset($fastUiDict[$cv][$lang])) {
                        $catRow[$ck] = $fastUiDict[$cv][$lang];
                    }
                }
                return $catRow;
            }, $store['blog_categories']);
        }
        $memCache[$lang] = ['_hash' => $hash, 'store' => $localized];
        return $localized;
    }

    require_once __DIR__ . '/translator.php';

    $textKeys = array_flip([
        'title', 'label', 'eyebrow', 'heading', 'heading_line1', 'heading_line2',
        'heading_muted', 'heading_accent', 'heading_span', 'heading_em', 'accent',
        'heading_before', 'heading_after', 'subheading', 'subtitle', 'lead',
        'short', 'description', 'desc', 'summary', 'copy', 'copy_line1', 'copy_line2',
        'intro', 'intro_text', 'tagline', 'pillars', 'top_note', 'kicker',
        'kicker_left', 'kicker_right', 'badge_right', 'caption', 'origin_text',
        'footer_left', 'footer_right', 'button_label', 'cta_label', 'btn_label',
        'link_label', 'primary_btn_label', 'secondary_btn_label', 'scroll_label',
        'domains_bar', 'coordinate_text', 'side_note', 'mobile_eyebrow', 'logo_aria',
        'logo_alt', 'availability_text', 'contact_heading', 'contact_cta_label',
        'crafted_note', 'signature_left', 'signature_right', 'brand_hero_alt',
        'brand_hero_eyebrow', 'brand_hero_h2_1', 'brand_hero_h2_span', 'brand_hero_copy',
        'story_image_alt', 'story_caption', 'story_eyebrow', 'story_h2_1', 'story_h2_muted',
        'vision_eyebrow', 'vision_h2_1', 'vision_h2_span', 'vision_copy', 'vision_alt',
        'card_tag', 'headline', 'approach_copy', 'showcase_alt', 'showcase_title',
        'showcase_copy', 'showcase_sub', 'emblem_alt', 'overview_eyebrow',
        'overview_h2_1', 'overview_h2_accent', 'overview_copy', 'disk_label',
        'disclaimer', 'sector', 'scope', 'work_type', 'problem', 'approach',
        'solution', 'result', 'interface_greeting', 'interface_footer', 'tag_label',
        'sidebar_eyebrow', 'sidebar_heading', 'sidebar_copy', 'steps_heading',
        'form_eyebrow', 'form_heading', 'form_copy', 'submit_label', 'form_hint',
        'meta_title', 'meta_desc', 'og_title', 'og_desc', 'ai_summary',
        'default_title', 'default_description', 'q', 'a', 'output', 'body', 'next_name',
        'placeholder', 'analyze_button', 'cta_badge', 'cta_heading', 'cta_sub', 'cta_button',
        'whatsapp_label', 'phone_label', 'online_label', 'offline_label',
        'email_label', 'address_label', 'lbl_name', 'ph_name', 'lbl_email', 'ph_email',
        'lbl_company', 'ph_company', 'lbl_service', 'ph_service', 'service_unsure_label',
        'lbl_budget', 'lbl_message', 'ph_message', 'consent_link_label', 'consent_text',
        'success_message', 'clients_eyebrow', 'clients_h2_1', 'clients_h2_accent',
        'clients_lead', 'vision_caption', 'software_eyebrow', 'software_h2',
        'deliv_eyebrow', 'deliv_h2_1', 'deliv_h2_muted',
        'approach_eyebrow', 'approach_h2_1', 'approach_h2_muted',
    ]);
    $stringArrayKeys = array_flip([
        'lines', 'note', 'story_paragraphs', 'software_items', 'deliverables',
        'features', 'palette', 'interface_blocks', 'budgets', 'items', 'categories',
    ]);
    $htmlKeys = array_flip(['content_html', 'html']);
    $skipSubtrees = array_flip(['clients', 'reviews', 'credit_label', 'credit_url']); // Keep original brand names, verified Google customer reviews & Zeynart® signature authentic

    $work = [];
    foreach ($sectionsToLocalize as $sk) {
        if (isset($store[$sk])) {
            $work[$sk] = $store[$sk];
        }
    }
    // For google_reviews, only translate section header texts, never the 34 raw review items array
    if (isset($work['google_reviews']['items'])) {
        $origGrItems = $work['google_reviews']['items'];
        unset($work['google_reviews']['items']);
    } else {
        $origGrItems = null;
    }

    $strings = [];
    $refs = [];
    $htmlRefs = [];

    $collect = static function (array &$node, string $parentKey = '') use (
        &$collect, &$strings, &$refs, &$htmlRefs, $textKeys, $stringArrayKeys, $htmlKeys, $skipSubtrees, $lang
    ): void {
        foreach ($node as $k => &$v) {
            $kStr = is_string($k) ? $k : '';
            if ($kStr !== '' && isset($skipSubtrees[$kStr])) {
                continue;
            }
            if (is_string($v)) {
                $trimmed = trim($v);
                if ($trimmed === '') {
                    continue;
                }
                if ($kStr === 'name' && $parentKey === 'blog_categories') {
                    $fastCat = t_category($trimmed, $lang);
                    if ($fastCat !== $trimmed) {
                        $v = $fastCat;
                        continue;
                    }
                    $idx = count($strings);
                    $strings[] = $v;
                    $refs[$idx] = &$v;
                    continue;
                }
                if ($kStr !== '' && isset($htmlKeys[$kStr])) {
                    $htmlRefs[] = &$v;
                } elseif (
                    ($kStr !== '' && isset($textKeys[$kStr]))
                    || ($kStr === '' && isset($stringArrayKeys[$parentKey]))
                ) {
                    $idx = count($strings);
                    $strings[] = $v;
                    $refs[$idx] = &$v;
                }
            } elseif (is_array($v)) {
                $collect($v, $kStr !== '' ? $kStr : $parentKey);
            }
        }
        unset($v);
    };

    $collect($work);

    $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];
    if ($strings !== []) {
        $translated = zeynart_translate_batch($strings, $lang, 'tr', $i18nCfg);
        foreach ($refs as $idx => &$refVal) {
            if (isset($translated[$idx]) && trim($translated[$idx]) !== '') {
                $refVal = $translated[$idx];
            }
        }
        unset($refVal);
    }

    foreach ($htmlRefs as &$hRef) {
        $hRef = zeynart_translate_html($hRef, $lang, 'tr', $i18nCfg);
    }
    unset($hRef);

    if ($origGrItems !== null) {
        $work['google_reviews']['items'] = $origGrItems;
    }
    if (isset($work['menus']['header'])) {
        $work['menus']['header']['language_badge'] = strtoupper($lang);
    }

    atomic_write_json($cacheFile, [
        '_hash' => $hash,
        '_lang' => $lang,
        '_generated_at' => date('c'),
        'sections' => $work,
    ], false);

    $localized = $store;
    foreach ($work as $secKey => $secVal) {
        $localized[$secKey] = $secVal;
    }
    $memCache[$lang] = ['_hash' => $hash, 'store' => $localized];
    return $localized;
}

/**
 * On-demand localization & disk caching for large Legal/Policy pages (kvkk, gizlilik, cerezler)
 * so normal site navigation never waits on legal document translation.
 */
function zeynart_localize_legal_page(array $lp, string $legalKey, string $lang, array $store = []): array
{
    $lang = strtolower(trim($lang));
    if ($lang === 'tr' || !in_array($lang, ['en', 'de', 'ru'], true) || $lp === []) {
        return $lp;
    }
    $srcHash = md5(json_encode($lp, JSON_UNESCAPED_UNICODE) ?: '');
    $cacheFile = DATA_DIR . '/i18n_cache/legal_' . $lang . '_' . preg_replace('/[^a-z0-9_-]/i', '', $legalKey) . '.json';
    $decoded = zeynart_read_json_data($cacheFile);
    if (is_array($decoded) && ($decoded['_hash'] ?? '') === $srcHash && is_array($decoded['page'] ?? null)) {
        return $decoded['page'];
    }

    require_once __DIR__ . '/translator.php';
    $i18nCfg = is_array($store['i18n'] ?? null) ? $store['i18n'] : [];

    $batch = [
        (string)($lp['eyebrow'] ?? ''),
        (string)($lp['title'] ?? ''),
        (string)($lp['lead'] ?? ''),
        (string)($lp['meta_title'] ?? ''),
        (string)($lp['meta_desc'] ?? ''),
    ];
    $secOffsets = [];
    foreach (($lp['sections'] ?? []) as $sIdx => $sec) {
        $hI = count($batch);
        $batch[] = (string)($sec['heading'] ?? '');
        $bI = count($batch);
        $batch[] = (string)($sec['body'] ?? '');
        $secOffsets[$sIdx] = ['h' => $hI, 'b' => $bI];
    }

    $trBatch = zeynart_translate_batch($batch, $lang, 'tr', $i18nCfg);
    $out = $lp;
    if (trim((string)($trBatch[0] ?? '')) !== '') $out['eyebrow'] = $trBatch[0];
    if (trim((string)($trBatch[1] ?? '')) !== '') $out['title'] = $trBatch[1];
    if (trim((string)($trBatch[2] ?? '')) !== '') $out['lead'] = $trBatch[2];
    if (trim((string)($trBatch[3] ?? '')) !== '') $out['meta_title'] = $trBatch[3];
    if (trim((string)($trBatch[4] ?? '')) !== '') $out['meta_desc'] = $trBatch[4];

    if (!empty($out['sections']) && is_array($out['sections'])) {
        foreach ($out['sections'] as $sIdx => &$secRef) {
            if (isset($secOffsets[$sIdx])) {
                $secRef['heading'] = $trBatch[$secOffsets[$sIdx]['h']] ?? ($secRef['heading'] ?? '');
                $secRef['body'] = $trBatch[$secOffsets[$sIdx]['b']] ?? ($secRef['body'] ?? '');
            }
        }
        unset($secRef);
    }

    if (!empty($out['content_html'])) {
        $out['content_html'] = zeynart_translate_html((string)$out['content_html'], $lang, 'tr', $i18nCfg);
    }

    atomic_write_json($cacheFile, [
        '_hash' => $srcHash,
        'page' => $out,
    ], false);

    return $out;
}
