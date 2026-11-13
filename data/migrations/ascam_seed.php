<?php
/**
 * ASCAM içerik taşıma (migration) betiği — yalnızca komut satırı.
 *
 *   php data/migrations/ascam_seed.php --images=<indirilen-wp-görselleri-klasörü> [--admin-pass=...] [--force]
 *
 * Ne yapar?
 *  1. Mevcut data/store.json varsa data/backups/ altına zaman damgalı yedek alır (geri dönüş: yedeği store.json olarak geri kopyalayın).
 *  2. ascam.com.tr WordPress medya kütüphanesinden indirilen gerçek görselleri WebP'ye çevirir ve
 *     /uploads/<yıl>/<ay>/<ad>.webp yapısına (WP ile aynı yol) yerleştirir. Böylece eski /wp-content/uploads/... adresleri
 *     front controller tarafından otomatik olarak yeni dosyaya 301 ile yönlenir.
 *  3. ascam.com.tr'den doğrulanmış içerikleri panelin mevcut veri yapısına (site, home, about, services, custom_pages,
 *     contact, menus, seo, redirects) yazar. Panelde hepsi düzenlenebilir.
 *  4. store.json zaten varsa ve --force verilmemişse hiçbir şeyi ezmez.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
require_once $root . '/includes/config.php';
require_once $root . '/includes/security.php';
require_once $root . '/includes/db.php';

$opts = getopt('', ['images:', 'admin-user::', 'admin-pass::', 'force', 'skip-images']);
$imgSrc = rtrim(str_replace('\\', '/', (string)($opts['images'] ?? '')), '/');
$force = isset($opts['force']);
$storeFile = DATA_DIR . '/store.json';

if (is_file($storeFile) && !$force) {
    fwrite(STDERR, "data/store.json zaten var. Üzerine yazmak için --force kullanın (önce yedek alınır).\n");
    exit(1);
}

// 1) Yedek
if (is_file($storeFile)) {
    @mkdir(DATA_DIR . '/backups', 0775, true);
    $bk = DATA_DIR . '/backups/store-pre-ascam-' . date('Ymd-His') . '.json';
    copy($storeFile, $bk) or exit("Yedek alınamadı: $bk\n");
    echo "Yedek: $bk\n";
}

// 2) Görseller
$imported = [];
function ascam_webp(string $src, string $dst, int $maxW = 1920, int $q = 82): bool
{
    $raw = @file_get_contents($src);
    if ($raw === false) {
        return false;
    }
    $im = @imagecreatefromstring($raw);
    if (!$im) {
        return false;
    }
    imagepalettetotruecolor($im);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $w = imagesx($im);
    $h = imagesy($im);
    if ($w > $maxW) {
        $nh = (int)round($h * $maxW / $w);
        $rs = imagecreatetruecolor($maxW, $nh);
        imagealphablending($rs, false);
        imagesavealpha($rs, true);
        imagecopyresampled($rs, $im, 0, 0, 0, 0, $maxW, $nh, $w, $h);
        $im = $rs;
    }
    @mkdir(dirname($dst), 0775, true);
    return imagewebp($im, $dst, $q);
}

if (!isset($opts['skip-images'])) {
    if ($imgSrc === '' || !is_dir($imgSrc)) {
        fwrite(STDERR, "--images=<klasör> gerekli (dosya adları WP yolundan türetilmiş: 2020_07_ad.jpg)\n");
        exit(1);
    }
    foreach (glob($imgSrc . '/20[0-9][0-9]_[0-9][0-9]_*') ?: [] as $file) {
        $base = basename($file);
        if (!preg_match('~^(\d{4})_(\d{2})_(.+)\.(jpe?g|png)$~i', $base, $m)) {
            continue;
        }
        $rel = '/uploads/' . $m[1] . '/' . $m[2] . '/' . $m[3] . '.webp';
        $dst = ROOT_DIR . $rel;
        if (!is_file($dst) && !ascam_webp($file, $dst)) {
            echo "  ! dönüştürülemedi: $base\n";
            continue;
        }
        $imported[$m[3]] = $rel;
        // Logo ve site ikonu için şeffaf PNG kopyası da tutulur
        if (in_array($m[3], ['logo', 'cropped-logo'], true) && strtolower($m[4]) === 'png' && $m[1] === '2020') {
            copy($file, ROOT_DIR . '/uploads/' . $m[1] . '/' . $m[2] . '/' . $m[3] . '.png');
        }
    }
    echo 'Görsel: ' . count($imported) . " dosya hazır\n";
}

$u = static function (string $name): string {
    // WP dosya adı -> yeni yol (2020/07 varsayılan)
    foreach (['2020/07', '2020/06'] as $ym) {
        if (is_file(ROOT_DIR . '/uploads/' . $ym . '/' . $name . '.webp')) {
            return '/uploads/' . $ym . '/' . $name . '.webp';
        }
    }
    return '/uploads/2020/07/' . $name . '.webp';
};

// Galeri listeleri ascam.com.tr sayfalarındaki sırayla (WP sayfa ID'si -> ekli görseller)
$gal = static function (array $names, string $altPrefix) use ($u): array {
    $out = [];
    foreach (array_values($names) as $i => $n) {
        $out[] = ['src' => $u($n), 'alt' => $altPrefix . ' — görsel ' . ($i + 1)];
    }
    return $out;
};
$range = static fn(array $nums, string $fmt): array => array_map(static fn($n) => sprintf($fmt, $n), $nums);

$bathNums = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 38, 41, 42, 44, 47, 49, 53, 54, 56, 58, 61, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 78, 81, 82, 83, 85, 87, 91, 93, 95, 97, 99, 101, 103, 105, 106, 107, 108, 110, 111, 112, 113, 114, 115, 116, 119, 120, 121, 122, 123, 124, 125, 126, 128, 129, 130, 131];
$kitchenNums = range(11, 24);
$doorNames = ['3007-699x1024-1', '3011-701x1024-1', '3038-688x1024-1', '3054-676x1024-1', '3057-677x1024-1', '3065-570x1024-1', '3085-678x1024-1', '3949-674x1024-1', '3955-670x1024-1', '3966-684x1024-1', '3976-680x1024-1', '3982-672x1024-1', '3995-678x1024-1', '4004-678x1024-1', '6452-B-678x1024-1', '6459-B-680x1024-1', '6465-B-676x1024-1', '6469-B-678x1024-1', '6475-B-679x1024-1', '6485-B-680x1024-1', '6502-B-678x1024-1', '6506-B-680x1024-1', '6513-B-682x1024-1', '6514-B-678x1024-1', '6566-B-678x1024-1', '6573-B-678x1024-1', '6579-B-678x1024-1', '6621-B-675x1024-1', '6626-B-682x1024-1', '6633-B-679x1024-1', '6660-B-680x1024-1', '6696-1-B-678x1024-1', '6696-B-678x1024-1', '6703-B-678x1024-1', '6719-B-681x1024-1', '6723-B-678x1024-1', '6727-B-678x1024-1', '6730-B-684x1024-1', '6734-B-680x1024-1', '6737-B-682x1024-1', '6742-B-680x1024-1', '6745-B-679x1024-1', '8482-B-684x1024-1', '8485-B-681x1024-1', '8500-B-683x1024-1', '8517-B-682x1024-1', '8525-B-683x1024-1', '8528-B-683x1024-1'];
$aluNums = array_values(array_diff(range(150, 304), [151, 182, 185, 218, 220, 257]));
$otherNames = ['2358-1024x635-1', '2397-1-1024x800-1', '2813-1024x612-1', '2816-679x1024-1', '3845-1024x729-1', '3845-1024x729-2', '3852-1024x683-1', '3857-1024x691-1', '6500-R-1-676x1024-1', '6500-R-676x1024-1', '6507-R-674x1024-1', '6518-R-689x1024-1', '6534-R-1-1024x705-1', '6534-R-1024x705-1', '6543-R-1024x728-1', '6550-R-1024x705-1', '6559-R-1024x718-1', '6764-mix-R-1024x647-1', '6804-R-731x1024-1', '6812-R-681x1024-1', '6816-R-681x1024-1', '6824-R-1024x681-1'];
$factory = ['fabrika-uretim-1', 'fabrika-uretim-3', 'fabrika-uretim-4', 'fabrika-uretim-5', 'fabrika-uretim-6', 'fabrika-uretim-7', 'fabrika-uretim-8', 'fabrika-uretim-9', 'fabrika-uretim-10'];

$heroImg = $u('AsCam-insaat-mimarlik-179-Copy');
$aluGallery = $gal($range($aluNums, 'AsCam-insaat-mimarlik-%d-Copy'), 'Alüminyum Grubu');
// Kapak için cam korkuluk karesini öne al (sıra panelden değiştirilebilir)
$fireDoor = $u('UZUNLAR_KATALOG_2013_web-58-1024x1024-1');

// Ürün grupları: ascam.com.tr menüsündeki "Ürünlerimiz" sırası. Açıklamalar yalnızca kurumsal metinden türetildi
// (kaynakta ürün grubu bazında açıklama yoktur) — panelden zenginleştirilebilir.
$productGroups = [
    ['banyo-grubu', 'Banyo Grubu', 'Bathroom Group', $gal($range($bathNums, '%d'), 'Banyo Grubu'), $u('114')],
    ['mutfak-grubu', 'Mutfak Grubu', 'Kitchen Group', $gal($range($kitchenNums, '%d'), 'Mutfak Grubu'), $u('17')],
    ['kapi-cesitleri', 'Kapı Çeşitleri', 'Door Types', $gal($doorNames, 'Kapı Çeşitleri'), $u('6745-B-679x1024-1')],
    ['yangin-cikis-kapilari', 'Yangın & Çıkış Kapıları', 'Fire & Exit Doors', [['src' => $fireDoor, 'alt' => 'Yangın & Çıkış Kapıları — görsel 1']], $fireDoor],
    ['aluminyum-grubu', 'Alüminyum Grubu', 'Aluminum Group', $aluGallery, $u('AsCam-insaat-mimarlik-184-Copy')],
    ['diger-urunler', 'Diğer Ürünler', 'Other Products', $gal($otherNames, 'Diğer Ürünler'), $u('3857-1024x691-1')],
];
$services = [];
foreach ($productGroups as $i => [$slug, $title, $enTitle, $gallery, $cover]) {
    $isDoor = in_array($slug, ['kapi-cesitleri', 'yangin-cikis-kapilari'], true);
    $services[] = [
        'slug' => $slug,
        'num' => str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT),
        'status' => 'published',
        'title' => $title,
        'eyebrow' => $isDoor ? 'KAPI GRUPLARI' : 'ÜRÜNLERİMİZ',
        'icon' => 'sparkles',
        'card_class' => '',
        'short' => count($gallery) . ' görsellik ürün galerisi',
        'card_tag' => $isDoor ? 'Kapı Grupları' : 'Ürün Grubu',
        'headline' => $title,
        'description' => 'As Cam, 2016 yılından bu yana banyo, mutfak, kapı ve benzeri ürünlerin satış ve pazarlamasını yapmaktadır. ' . $title . ' galerisindeki ürünler hakkında bilgi ve teklif için bizimle iletişime geçebilirsiniz.',
        'banner_image' => $cover,
        'layout_type' => 'standard',
        'deliverables' => [],
        'approach_copy' => '',
        'stack' => [],
        'faqs' => [],
        'builder_blocks' => [],
        'gallery' => $gallery,
        'seo' => [
            'meta_title' => $title . ' | As Cam',
            'meta_desc' => 'As Cam ' . mb_strtolower($title, 'UTF-8') . ' ürün galerisi. Bilgi ve teklif için As Cam Yapı İnşaat ile iletişime geçin.',
            'focus_keyword' => mb_strtolower($title, 'UTF-8'),
            'ai_summary' => '',
            'og_image' => $cover,
        ],
        'en_title_hint' => $enTitle,
    ];
}

$store = [
    'site' => [
        'name' => 'As Cam',
        'legal_name' => 'AS CAM YAPI İNŞAAT DIŞ TİCARET LTD. ŞTİ.',
        'tagline' => 'Tek seferde çözüm',
        'tagline_en' => 'One stop solution',
        'logo' => '/uploads/2020/06/logo.png',
        'logo_alt' => 'As Cam — Tek seferde çözüm',
        'logo_width' => 567,
        'logo_height' => 289,
        'favicon' => '/uploads/2020/06/cropped-logo.png',
        'email' => 'info@ascam.com.tr',
        'phone' => '(+90) 216 410 27 47',
        'phone_href' => '+902164102747',
        'fax' => '(+90) 216 410 27 48',
        'address' => 'Tantavi Mh. Estergon Cd. Suryapı Exen İstanbul Sitesi No: 20 F Kule Residence Kat: 26 Daire: 312',
        'city' => 'Ümraniye / İstanbul',
        'country' => 'TR',
        'founded_year' => '2004',
        'og_image' => $heroImg,
        'theme_color' => '#0d1012',
        'languages' => ['tr', 'en'],
        'show_header_socials' => false,
        'show_footer_socials' => false,
        'show_contact_socials' => false,
        'enable_custom_cursor' => false,
        'enable_scroll_progress' => false,
    ],
    'admin' => [
        'username' => (string)($opts['admin-user'] ?? 'ascam'),
        'display_name' => 'As Cam Yönetici',
        'password_hash' => '',
    ],
    'page_slugs' => ['hizmetler' => 'urunler'],
    'page_states' => [
        'projeler' => ['status' => 'draft'],
        'blog' => ['status' => 'draft'],
        'site-analiz' => ['status' => 'draft'],
        'teklif-sihirbazi' => ['status' => 'draft'],
        'gorusme-planla' => ['status' => 'draft'],
        'kvkk' => ['status' => 'draft'],
        'gizlilik' => ['status' => 'draft'],
        'cerez-politikasi' => ['status' => 'draft'],
    ],
    'home' => [
        'section_order' => ['hero', 'services', 'intro', 'brand_scene', 'process', 'clients_strip', 'projects_showcase', 'latest_blog', 'builder_blocks', 'cta', 'google_reviews', 'site_audit', 'quote_wizard'],
        'section_visibility' => [
            'hero' => true, 'services' => true, 'intro' => true, 'brand_scene' => true, 'process' => true,
            'clients_strip' => true, 'projects_showcase' => false, 'latest_blog' => false, 'builder_blocks' => true,
            'cta' => true, 'google_reviews' => false, 'site_audit' => false, 'quote_wizard' => false,
        ],
        'hero' => [
            'bg_image_1' => $heroImg,
            'bg_alt_1' => 'As Cam alüminyum ve cam korkuluk sistemi, gün batımında',
            'bg_image_2' => $u('fabrika-uretim-3'),
            'bg_alt_2' => 'As Cam üretim tesisi',
            'primary_btn_label' => 'Ürünleri keşfedin',
            'primary_btn_url' => '/urunler/',
            'secondary_btn_label' => 'Hemen teklif alın',
            'secondary_btn_url' => '/iletisim/',
            'domains_bar' => 'BANYO · MUTFAK · KAPI · ALÜMİNYUM',
            'scroll_label' => 'KEŞFETMEYE DEVAM EDİN',
            'coordinate_text' => 'AS CAM YAPI İNŞAAT DIŞ TİCARET LTD. ŞTİ.',
            'slides' => [[
                'label' => 'Kurumsal Hizmet & Çözümler',
                'lines' => ['Tek seferde'],
                'accent' => 'çözüm.',
                'note' => ['Etkileyici ve yaratıcı tasarımlar', 'Zarif ve detaylı tasarımlar'],
                'copy' => 'Banyo, mutfak, kapı ve alüminyum gruplarında satış ve pazarlama; cam sektöründe 2006’dan bugüne gelen deneyim.',
            ]],
        ],
        'intro' => [
            'eyebrow' => 'AS CAM YAPI KİMDİR?',
            'year' => '2004',
            'tagline' => 'Triko sektöründen cama, camdan yapıya.',
            'lead' => '2006’dan bu yana cam sektöründe; 2016’dan bu yana inşaat alanında.',
            'heading_before' => 'Sektöre',
            'heading_em' => 'yeni bir soluk',
            'heading_after' => 'getirmek için çalışıyoruz.',
            'pillars' => '310 CAM İŞLEME MAKİNESİ · 5 DİSTRİBÜTÖRLÜK · TÜRKİYE’NİN DÖRT BİR YANI',
            'link_label' => 'Hikâyemizi okuyun',
            'link_url' => '/hakkimizda/',
        ],
        'brand_scene' => [
            'image' => $u('fabrika-uretim-3'),
            'eyebrow' => 'AS CAM ÜRETİM',
            'top_note' => 'ISO 9001 KALİTE KOŞULLARI',
            'heading_line1' => 'El işçiliği tadında',
            'heading_line2' => 'üretim,',
            'heading_em' => 'sıfır hata montaj.',
            'copy_line1' => '30.000 m² alan üzerine kurulu tesislerde özgün ve ergonomik tasarımlar.',
            'copy_line2' => 'Özel eğitimli montaj ve satış sonrası hizmet ekibi.',
            'link_label' => 'Üretimi inceleyin',
            'link_url' => '/uretim/',
        ],
        'process' => [
            'eyebrow' => 'HİZMETLERİMİZ',
            'heading_line1' => 'As Cam, sizler için',
            'heading_muted' => 'profesyonel ve kurumsal hizmetler sunar.',
            'intro' => 'As Cam profesyonel ekibi ile kurumsal bir hizmet yapısı sunmaktadır.',
            'footer_left' => 'Profesyonel Hizmet!',
            'footer_right' => 'AS CAM MİMARLIK',
            'steps' => [
                ['num' => '01', 'icon' => 'sparkles', 'title' => 'Profesyonel Ekip', 'desc' => 'As Cam profesyonel ekibi ile kurumsal bir hizmet yapısı sunmaktadır.', 'output' => ''],
                ['num' => '02', 'icon' => 'sparkles', 'title' => 'Başarı Odaklı Çalışma', 'desc' => 'As Cam yaptığı her işte başarı odaklı çalışma prensibi ile çalışmaktadır.', 'output' => ''],
                ['num' => '03', 'icon' => 'sparkles', 'title' => 'Profesyonel Ekip Çalışması', 'desc' => 'As Cam profesyonel ekip çalışma ruhuna yatkın kurumsal ve profesyonel bir hizmet sunar.', 'output' => ''],
                ['num' => '04', 'icon' => 'sparkles', 'title' => 'Stratejik Kararlar', 'desc' => 'As Cam projelerinizde stratejik kararlar alıp siz değerli müşterilerine profesyonel bir hizmet sunmayı hedeflemektedir.', 'output' => ''],
                ['num' => '05', 'icon' => 'sparkles', 'title' => 'Kurumsal İletişim', 'desc' => 'As Cam kurumsal iletişime önem vermektedir. Tüm müşterileri ile kurumsal iletişim ağı oluşturmaktadır.', 'output' => ''],
            ],
        ],
    ],
    'services_page' => [
        'eyebrow' => 'ÜRÜNLERİMİZ',
        'heading_line1' => 'Zarif ve',
        'heading_muted' => 'detaylı tasarımlar.',
        'lead' => 'Banyo, mutfak, kapı ve alüminyum gruplarında ürün galerilerimizi inceleyin; ihtiyacınıza uygun çözüm için bize ulaşın.',
        'banner_image' => $u('AsCam-insaat-mimarlik-184-Copy'),
    ],
    'services' => $services,
    'projects_page' => [
        'eyebrow' => 'REFERANSLARIMIZ',
        'heading_line1' => 'Projelerimiz',
        'heading_muted' => '',
        'lead' => '',
    ],
    'projects' => [],
    'about' => [
        'eyebrow' => 'AS CAM MİSYON & VİZYON',
        'heading_line1' => 'As Cam Yapı',
        'heading_accent' => 'kimdir?',
        'lead' => '2004’te triko sektöründe kurulan firmamız, 2006’dan bu yana cam sektöründe, 2016’dan bu yana da inşaat alanında faaliyet göstermektedir.',
        'banner_image' => $u('AsCam-insaat-mimarlik-230-Copy'),
        'story_eyebrow' => 'HİKÂYEMİZ',
        'story_h2_1' => 'Triko’dan cama,',
        'story_h2_muted' => 'camdan yapıya.',
        'story_paragraphs' => [
            '2004 yılında As Triko ve Kimya San. Dış Tic. Ltd. Şti. olarak kurulan firmamız kuruluşundan 2006 yılına kadar triko sektöründe yurt içi ve yurt dışına yılda 800.000 adet üretim yapmıştır.',
            '2006 yılında triko sektörünün piyasadaki kötü durumundan dolayı firmamız kendisi için yeni olan cam sektöründe yoluna devam etmiştir. Günümüze kadar gelen bu süreçte firmamız Türkiye’nin dört bir tarafına 310 adet cam işleme makinesi, cam işleme hatları ve cam silikon malzemelerinin satışını yapmıştır. Distribütörü olduğumuz Çin ve İtalya markalı, kendi ülkelerinin 1 numarası olan firmalar ile birlikte yurt içi ve yurt dışında fuarlara katılarak As Cam adını birçok müşterisine ve firmalara duyurmuş, cam sektöründe adını altın harflerle yazdırmıştır.',
            '2016 yılına geldiğimizde ise inşaat alanına da yatırımlar yapılmaya başlanmış; banyo, mutfak, kapı ve benzeri diğer ürünlerin satış ve pazarlaması ile ilgili yeni kadrosunu oluşturmuş olup bu anlamda sektöre yeni bir soluk getirme amacı ile çalışmalarına hızla devam etmektedir.',
        ],
        'story_image' => $u('fabrika-uretim-8'),
        'story_caption' => 'AS CAM ÜRETİM TESİSİ',
        'software_eyebrow' => 'DİSTRİBÜTÖRLÜKLERİMİZ',
        'software_h2' => 'Kendi ülkelerinin 1 numarası olan Çin ve İtalya markalarıyla.',
        'software_items' => [
            'GUANDONG FUSHAN (Çin) — 10 yıllık distribütörlük, 200 makine',
            'IVACO MACHINERY (Çin) — 7 yıllık distribütörlük, 63 makine',
            'BAIYUN (Çin) — 7 yıllık distribütörlük, 40.000 varil',
            'FOREL COMPANY (İtalya) — 5 yıllık distribütörlük, 25 makine',
            'MAPPI COMPANY (İtalya) — 5 yıllık distribütörlük, 22 makine',
        ],
        'clients_eyebrow' => 'ÇÖZÜM ORTAKLARIMIZ',
        'clients_h2_1' => 'Birlikte',
        'clients_h2_accent' => 'çalıştığımız markalar.',
        'clients_lead' => '',
        'clients' => [
            ['id' => 'partner-1', 'name' => 'Franke', 'alt' => 'Franke logosu', 'url' => '#', 'image' => $u('0455')],
        ],
        'values' => [],
        'reviews' => [],
    ],
    'custom_pages' => [[
        'id' => 'cp-uretim',
        'slug' => 'uretim',
        'status' => 'published',
        'title' => 'Üretim',
        'eyebrow' => 'AS CAM ÜRETİM',
        'excerpt' => '30.000 m² alan üzerine kurulu tesislerde, ISO 9001 kalite koşullarında üretim.',
        'banner_image' => $u('fabrika-uretim-4'),
        'show_page_header' => true,
        'show_cta' => true,
        'template' => 'technical',
        'content_html' => '<p>30.000 m2 alan üzerine kurulu tesislerde, ISO 9001 Kalite Koşullarında, “el işçiliği tadında üretim” ve “sıfır hata montaj” ilkesiyle yapılan özgün ve ergonomik tasarımlar, her büyüklükteki inşaat projesinin ihtiyacını karşılar. Özel eğitimli montaj ve satış sonrası hizmet ekibiyle de proje ortaklarına hizmet verir.</p><p>Yüksek kaliteli hammaddeler ile dünya standartlarında hız ve üretim adedi hedefleyerek bunu başarmakla birlikte, sağlık ve güvenliğin ön planda tutulduğu bir ortamda üretime katkıda bulunur.</p>',
        'sections' => [
            ['id' => 'sec-uretim-stats', 'layout' => '1', 'style' => ['padding_y' => 40], 'columns' => [['widgets' => [[
                'id' => 'w-uretim-stats', 'type' => 'stats_bar', 'props' => ['items' => [
                    ['value' => '30.000 m²', 'label' => 'Tesis alanı'],
                    ['value' => 'ISO 9001', 'label' => 'Kalite koşulları'],
                    ['value' => 'Sıfır hata', 'label' => 'Montaj ilkesi'],
                ]],
            ]]]]],
            ['id' => 'sec-uretim-gallery', 'layout' => '1', 'style' => ['padding_y' => 40], 'columns' => [['widgets' => [[
                'id' => 'w-uretim-gallery', 'type' => 'gallery_grid', 'props' => ['images' => $gal($factory, 'As Cam üretim tesisi')],
            ]]]]],
        ],
        'faqs' => [],
        'seo' => [
            'meta_title' => 'Üretim | As Cam',
            'meta_desc' => '30.000 m² tesislerde ISO 9001 kalite koşullarında, el işçiliği tadında üretim ve sıfır hata montaj ilkesi.',
            'og_image' => $u('fabrika-uretim-4'),
        ],
        'updated_at' => date('c'),
    ]],
    'contact' => [
        'eyebrow' => 'OFİS BİLGİLERİ',
        'heading_line' => 'İletişim',
        'heading_muted' => 'bilgilerimiz.',
        'lead' => 'Ofisimizi ziyaret edin ya da dilediğiniz zaman bize e-posta gönderin. Müşterilerimizden gelen tüm önerilere açığız.',
        'banner_image' => $u('AsCam-insaat-mimarlik-238-Copy'),
        'phone_label' => 'TEL / FAKS',
        'email_label' => 'E-POSTA',
        'address_label' => 'MERKEZ OFİS',
        'show_phone' => true,
        'show_email' => true,
        'show_address' => true,
        'show_socials' => false,
        'show_steps' => false,
        'show_cta' => false,
        'require_phone' => false,
        'maps_url' => '',
        'map_embed_url' => '',
        'form_eyebrow' => 'TEKLİF FORMU',
        'form_heading' => 'Hemen teklif alın.',
        'form_copy' => 'İnovatif bir projeniz mi var? Hemen bize ulaşın, firma yetkililerimiz size ufuk açan yaklaşımları ile yardımcı olsunlar.',
        'submit_label' => 'Gönder',
        'success_message' => 'Mesajınız alındı. En kısa sürede size dönüş yapacağız.',
        'budgets' => [],
        'steps' => [],
    ],
    'cta_global' => [
        'eyebrow' => 'İLETİŞİMDE KALALIM!',
        'heading_line1' => 'Hemen',
        'heading_line2' => 'teklif',
        'heading_accent' => 'alın.',
        'button_label' => 'Teklif formu',
        'button_url' => '/iletisim/',
        'side_note' => 'İnovatif bir projeniz mi var? Hemen bize ulaşın, firma yetkililerimiz size ufuk açan yaklaşımları ile yardımcı olsunlar.',
    ],
    'menus' => [
        'header' => [
            'logo_aria' => 'As Cam ana sayfa',
            'language_badge' => 'TR',
            'show_language' => true,
            'cta_label' => 'Teklif alın',
            'cta_url' => '/iletisim/',
            'show_cta' => true,
            'mobile_eyebrow' => 'TEK SEFERDE ÇÖZÜM',
            'nav_align' => 'center',
            'element_order' => ['brand', 'nav', 'actions'],
            'items' => [
                ['id' => 'nav-1', 'label' => 'Ürünler', 'url' => '/urunler/', 'target' => '_self', 'enabled' => true],
                ['id' => 'nav-2', 'label' => 'Üretim', 'url' => '/uretim/', 'target' => '_self', 'enabled' => true],
                ['id' => 'nav-3', 'label' => 'Kurumsal', 'url' => '/hakkimizda/', 'target' => '_self', 'enabled' => true],
                ['id' => 'nav-4', 'label' => 'İletişim', 'url' => '/iletisim/', 'target' => '_self', 'enabled' => true],
            ],
        ],
        'footer' => [
            'columns' => [
                ['id' => 'fcol-1', 'title' => 'Ürünlerimiz', 'links' => array_map(static fn($s) => ['label' => $s['title'], 'url' => '/urunler/' . $s['slug'] . '/', 'target' => '_self'], $services)],
                ['id' => 'fcol-2', 'title' => 'Kurumsal', 'links' => [
                    ['label' => 'Hakkımızda', 'url' => '/hakkimizda/', 'target' => '_self'],
                    ['label' => 'Üretim', 'url' => '/uretim/', 'target' => '_self'],
                    ['label' => 'İletişim', 'url' => '/iletisim/', 'target' => '_self'],
                ]],
            ],
            'legal_links' => [],
            'copyright' => 'Copyright by As Cam Group. Tüm hakları saklıdır.',
        ],
    ],
    'seo' => [
        '_seo_v2_seeded' => true,
        'google_verification' => '',
        'ga_measurement_id' => '',
        'social_profiles' => [],
        'default_title' => 'As Cam — Yapı İnşaat',
        'default_description' => 'As Cam Yapı İnşaat Dış Ticaret: banyo, mutfak, kapı, yangın & çıkış kapıları ve alüminyum gruplarında satış ve pazarlama. Tek seferde çözüm.',
        'title_suffix' => ' | As Cam',
        'og_image' => $heroImg,
        'pages' => [
            'anasayfa' => ['meta_title' => 'As Cam — Yapı İnşaat | Tek seferde çözüm', 'meta_desc' => 'As Cam Yapı İnşaat Dış Ticaret: banyo, mutfak, kapı, yangın & çıkış kapıları ve alüminyum gruplarında satış ve pazarlama. 2006’dan bu yana cam sektöründe.'],
            'hakkimizda' => ['meta_title' => 'Hakkımızda | As Cam', 'meta_desc' => '2004’te kurulan As Cam; 2006’dan bu yana cam sektöründe, 2016’dan bu yana inşaat alanında faaliyet gösteriyor. 310 cam işleme makinesi satışı ve 5 distribütörlük.'],
            'hizmetler' => ['meta_title' => 'Ürünlerimiz | As Cam', 'meta_desc' => 'Banyo grubu, mutfak grubu, kapı çeşitleri, yangın & çıkış kapıları, alüminyum grubu ve diğer ürünler — As Cam ürün galerileri.'],
            'iletisim' => ['meta_title' => 'İletişim | As Cam', 'meta_desc' => 'As Cam merkez ofis: Tantavi Mh. Estergon Cd. Ümraniye / İstanbul. Tel: (+90) 216 410 27 47 · info@ascam.com.tr'],
        ],
    ],
    'redirects' => [],
    'i18n' => ['provider' => 'auto'],
    'lscache' => ['enabled' => false, 'ttl' => 3600],
    'messages' => [],
    'vcards' => [['id' => 'vc-ascam', 'slug' => 'ascam', 'status' => 'draft', 'full_name' => 'As Cam', 'company' => 'AS CAM YAPI İNŞAAT DIŞ TİCARET LTD. ŞTİ.', 'phone' => '(+90) 216 410 27 47', 'email' => 'info@ascam.com.tr', 'website' => 'https://www.ascam.com.tr/', 'address' => 'Tantavi Mh. Estergon Cd. Suryapı Exen İstanbul Sitesi No: 20 F Kule Residence Kat: 26 Daire: 312 Ümraniye / İstanbul']],
    'blog_categories_initialized' => true,
    'blog_categories' => [],
    '_redirects_blog_healed_v51' => true,
    '_auto_home_fallback_cleaned_v52' => true,
];
foreach ($store['services'] as &$s) {
    unset($s['en_title_hint']);
}
unset($s);

// Eski WordPress adresleri -> yeni adresler (panel > SEO > 301 Yönlendirmeler'den düzenlenebilir)
$red = [
    '/banyo-grubu' => '/urunler/banyo-grubu/',
    '/mutfak-grubu' => '/urunler/mutfak-grubu/',
    '/kapi-cesitleri' => '/urunler/kapi-cesitleri/',
    '/yangin-cikis-kapilari' => '/urunler/yangin-cikis-kapilari/',
    '/aluminyum-grubu' => '/urunler/aluminyum-grubu/',
    '/diger-urunler' => '/urunler/diger-urunler/',
    '/hizmetler' => '/urunler/',
    '/projeler' => '/urunler/',
    '/cozum-ortaklarimiz' => '/hakkimizda/#cozum-ortaklari',
    '/footer' => '/',
    '/home' => '/',
    '/en/home-2' => '/en/',
    '/en/about-us' => '/en/hakkimizda/',
    '/en/production' => '/en/uretim/',
    '/en/contact' => '/en/iletisim/',
    '/en/services' => '/en/urunler/',
    '/en/projects' => '/en/urunler/',
    '/en/bathroom-group' => '/en/urunler/banyo-grubu/',
    '/en/kitchen-group' => '/en/urunler/mutfak-grubu/',
    '/en/door-types' => '/en/urunler/kapi-cesitleri/',
    '/en/fire-exit-doors' => '/en/urunler/yangin-cikis-kapilari/',
    '/en/aluminum-group' => '/en/urunler/aluminyum-grubu/',
    '/en/other-products' => '/en/urunler/diger-urunler/',
    '/feed' => '/',
];
foreach ($red as $from => $to) {
    $store['redirects'][] = ['from' => $from, 'to' => $to, 'code' => 301, 'hits' => 0, 'created_at' => date('c')];
}

// Medya alt metinleri (Panel > Medya'da görünür)
$meta = [];
foreach ($store['services'] as $s) {
    foreach ($s['gallery'] as $g) {
        $meta[$g['src']] = ['alt' => $g['alt']];
    }
}
foreach ($store['custom_pages'][0]['sections'][1]['columns'][0]['widgets'][0]['props']['images'] as $g) {
    $meta[$g['src']] = ['alt' => $g['alt']];
}
$meta['/uploads/2020/06/logo.png'] = ['alt' => 'As Cam logosu'];
$store['media_meta'] = $meta;

// Yönetici parolası
$pass = (string)($opts['admin-pass'] ?? '');
if ($pass === '') {
    $pass = rtrim(strtr(base64_encode(random_bytes(12)), '+/', 'Kx'), '=');
}
$store['admin']['password_hash'] = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

if (!save_store($store)) {
    fwrite(STDERR, "store.json yazılamadı (data/ izinlerini kontrol edin)\n");
    exit(1);
}
echo "store.json yazıldı.\n";
echo 'Panel kullanıcı adı: ' . $store['admin']['username'] . "\n";
echo "Panel parolası: $pass\n";
