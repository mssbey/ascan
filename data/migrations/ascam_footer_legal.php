<?php
/**
 * ASCAM — alt bilgi yasal bağlantıları, KVKK sayfası ve hızlı iletişim kapsülü (yalnızca komut satırı).
 *
 *   php data/migrations/ascam_footer_legal.php
 *
 * Tekrar çalıştırılabilir (idempotent). Panelde düzenlenmiş değerleri ezmez; yalnızca eksik olanı ekler,
 * Zeynart ajans varsayılanlarından kalan metinleri As Cam'e uyarlar. Önce data/backups/ altına yedek alır.
 *  - legal_pages.kvkk: KVKK Aydınlatma Metni (Panel > Sayfalar > KVKK)
 *  - page_states: kvkk + cerez-politikasi yayında
 *  - menus.footer.legal_links: Gizlilik Politikası (KVKK) · Çerez Politikası
 *  - quick_contact: WhatsApp + Hızlı Arama kapsülü (Panel > Hızlı İletişim)
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

$storeFile = DATA_DIR . '/store.json';
if (!is_file($storeFile)) {
    fwrite(STDERR, "data/store.json yok — önce ascam_seed.php çalıştırın.\n");
    exit(1);
}
@mkdir(DATA_DIR . '/backups', 0775, true);
copy($storeFile, DATA_DIR . '/backups/store-before-footer-legal-' . date('Ymd-His') . '.json');

$store = json_decode((string)file_get_contents($storeFile), true);
if (!is_array($store)) {
    fwrite(STDERR, "store.json okunamadı.\n");
    exit(1);
}

$site = $store['site'] ?? [];
$legal = (string)($site['legal_name'] ?? 'AS CAM YAPI İNŞAAT DIŞ TİCARET LTD. ŞTİ.');
$addr = trim((string)($site['address'] ?? '') . ' ' . (string)($site['city'] ?? ''));
$mail = (string)($site['email'] ?? 'info@ascam.com.tr');
$phone = (string)($site['phone'] ?? '');
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// 1) KVKK Aydınlatma Metni
if (empty($store['legal_pages']['kvkk']['content_html'])) {
    $store['legal_pages']['kvkk'] = [
        'slug' => 'kvkk',
        'eyebrow' => '6698 SAYILI KVKK',
        'title' => 'Gizlilik Politikası (KVKK)',
        'lead' => 'Kişisel verilerinizi hangi amaçlarla ve hangi hukuki sebeplere dayanarak işlediğimizi, kimlerle paylaşabileceğimizi ve sahip olduğunuz hakları bu metinde açıklıyoruz.',
        'meta_title' => 'Gizlilik Politikası (KVKK) | As Cam',
        'meta_desc' => 'As Cam Yapı İnşaat Dış Ticaret Ltd. Şti. KVKK aydınlatma metni: işlenen kişisel veriler, amaçlar, hukuki sebepler, aktarım ve başvuru hakları.',
        'content_html' => <<<HTML
<p>Bu aydınlatma metni, <strong>{$h($legal)}</strong> (“As Cam” veya “Veri Sorumlusu”) tarafından, <strong>6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”)</strong> madde 10 ve Aydınlatma Yükümlülüğünün Yerine Getirilmesinde Uyulacak Usul ve Esaslar Hakkında Tebliğ uyarınca, internet sitemizi ziyaret eden ve bizimle iletişime geçen kişileri bilgilendirmek amacıyla hazırlanmıştır.</p>

<h2>1. Veri Sorumlusu</h2>
<ul>
  <li><strong>Unvan:</strong> {$h($legal)}</li>
  <li><strong>Adres:</strong> {$h($addr)}</li>
  <li><strong>E-posta:</strong> <a href="mailto:{$h($mail)}">{$h($mail)}</a></li>
  <li><strong>Telefon:</strong> {$h($phone)}</li>
</ul>

<h2>2. İşlenen Kişisel Veriler</h2>
<ul>
  <li><strong>Kimlik ve iletişim bilgileri:</strong> ad, soyad, firma adı, telefon numarası, e-posta adresi — iletişim formu, e-posta, telefon veya WhatsApp üzerinden bize ilettiğiniz ölçüde.</li>
  <li><strong>Talep içeriği:</strong> ürün, teklif veya proje talebinize ilişkin mesajınızda yer alan bilgiler.</li>
  <li><strong>İşlem güvenliği verileri:</strong> IP adresi, tarayıcı ve cihaz bilgisi, ziyaret tarih/saati gibi sunucu kayıtları.</li>
  <li><strong>Çerez verileri:</strong> yalnızca <a href="/cerez-politikasi/">Çerez Politikası</a>’nda açıklanan kapsamda ve isteğe bağlı çerezler için açık rızanıza bağlı olarak.</li>
</ul>

<h2>3. İşleme Amaçları</h2>
<ul>
  <li>Ürün, fiyat ve teklif taleplerinize yanıt verilmesi ve sizinle iletişim kurulması,</li>
  <li>Satış öncesi ve sonrası süreçlerin yürütülmesi, sözleşme süreçlerinin hazırlanması,</li>
  <li>İnternet sitesinin güvenliğinin sağlanması ve yasal yükümlülüklerin (ör. 5651 sayılı Kanun kapsamında kayıt tutma) yerine getirilmesi,</li>
  <li>Yetkili kurum ve kuruluşlardan gelen taleplerin karşılanması.</li>
</ul>

<h2>4. Hukuki Sebepler ve Toplama Yöntemi</h2>
<p>Kişisel verileriniz; internet sitemizdeki formlar, e-posta, telefon ve WhatsApp gibi elektronik kanallar ile sunucu kayıtları aracılığıyla otomatik veya kısmen otomatik yollarla toplanır. Bu veriler KVKK madde 5/2 uyarınca <em>bir sözleşmenin kurulması veya ifasıyla doğrudan ilgili olması</em>, <em>veri sorumlusunun hukuki yükümlülüğünü yerine getirebilmesi</em> ve <em>ilgili kişinin temel hak ve özgürlüklerine zarar vermemek kaydıyla veri sorumlusunun meşru menfaati</em> hukuki sebeplerine; isteğe bağlı çerezler bakımından ise madde 5/1 uyarınca <em>açık rızanıza</em> dayanılarak işlenir.</p>

<h2>5. Kişisel Verilerin Aktarılması</h2>
<p>Kişisel verileriniz, yukarıdaki amaçlarla sınırlı olarak; barındırma, e-posta ve bilgi teknolojisi hizmeti aldığımız tedarikçilerimize, hukuki yükümlülüklerimiz kapsamında yetkili kamu kurum ve kuruluşlarına ve talep halinde adli mercilere aktarılabilir. WhatsApp üzerinden iletişime geçmeniz halinde mesajlarınız WhatsApp hizmet sağlayıcısının altyapısı üzerinden iletilir. Verileriniz pazarlama amacıyla üçüncü kişilere satılmaz veya kiralanmaz.</p>

<h2>6. Saklama Süresi</h2>
<p>Kişisel verileriniz, işleme amacının gerektirdiği süre ve ilgili mevzuatta öngörülen zamanaşımı süreleri boyunca saklanır; sürenin sona ermesiyle silinir, yok edilir veya anonim hale getirilir.</p>

<h2>7. KVKK Madde 11 Kapsamındaki Haklarınız</h2>
<ul>
  <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme ve işlenmişse buna ilişkin bilgi talep etme,</li>
  <li>İşlenme amacını ve bunların amacına uygun kullanılıp kullanılmadığını öğrenme,</li>
  <li>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme,</li>
  <li>Eksik veya yanlış işlenmişse düzeltilmesini, KVKK madde 7 çerçevesinde silinmesini veya yok edilmesini isteme ve bu işlemlerin aktarılan üçüncü kişilere bildirilmesini talep etme,</li>
  <li>Münhasıran otomatik sistemlerle analiz edilmesi sonucu aleyhinize bir sonucun ortaya çıkmasına itiraz etme,</li>
  <li>Kanuna aykırı işlenmesi sebebiyle zarara uğramanız halinde zararın giderilmesini talep etme.</li>
</ul>

<h2>8. Başvuru</h2>
<p>Haklarınıza ilişkin taleplerinizi, Veri Sorumlusuna Başvuru Usul ve Esasları Hakkında Tebliğ’e uygun olarak yazılı şekilde yukarıdaki adresimize veya <a href="mailto:{$h($mail)}">{$h($mail)}</a> adresine iletebilirsiniz. Başvurularınız en geç otuz gün içinde ücretsiz olarak sonuçlandırılır.</p>
HTML,
        'sections' => [],
    ];
}

// 2) Çerez politikası: ajans kalıntılarını As Cam'e uyarla, satır içi koyu renk stillerini tema sınıflarına çevir
$cp = &$store['legal_pages']['cerez-politikasi'];
if (is_array($cp)) {
    if (str_contains((string)($cp['meta_title'] ?? ''), 'ZEYNART')) {
        $cp['meta_title'] = 'Çerez Politikası | As Cam';
    }
    if (str_contains((string)($cp['meta_desc'] ?? ''), 'Zeynart')) {
        $cp['meta_desc'] = 'As Cam Yapı İnşaat Dış Ticaret Ltd. Şti. çerez politikası: kullanılan çerez türleri, amaçları, KVKK hukuki sebepleri ve çerez tercihlerinin yönetimi.';
    }
    $html = (string)($cp['content_html'] ?? '');
    $html = preg_replace('~<div style="margin:22px 0;padding:18px 20px;border-radius:16px;border:1px solid rgba\(94,234,212,\.28\);background:rgba\(20,184,166,\.07\)">\s*<strong style="[^"]*">~u', "<div class=\"legal-note\">\n  <strong>", $html) ?? $html;
    $html = preg_replace('~<(tr|th|td|table|thead|tbody) style="[^"]*"~u', '<$1', $html) ?? $html;
    $cp['content_html'] = $html;
}
unset($cp);

// 3) Sayfaları yayına al
$store['page_states']['kvkk']['status'] = 'published';
$store['page_states']['cerez-politikasi']['status'] = 'published';

// 4) Alt bilgi yasal bağlantıları (sıra: KVKK, Çerez Politikası)
$links = is_array($store['menus']['footer']['legal_links'] ?? null) ? $store['menus']['footer']['legal_links'] : [];
$hasKvkk = false;
foreach ($links as $l) {
    if (trim((string)($l['url'] ?? ''), '/') === 'kvkk') {
        $hasKvkk = true;
    }
}
if (!$hasKvkk) {
    array_unshift($links, ['label' => 'Gizlilik Politikası (KVKK)', 'url' => '/kvkk/']);
}
$store['menus']['footer']['legal_links'] = array_values($links);
$store['menus']['footer']['credit_label'] = 'Powered & Designed By Zeynart®';
$store['menus']['footer']['credit_url'] = $store['menus']['footer']['credit_url'] ?? 'https://zeynart.com/';

// 5) Çerez paneli KVKK bağlantısı
$store['cookie_consent']['kvkk_url'] = '/kvkk/';
if (in_array((string)($store['cookie_consent']['kvkk_label'] ?? ''), ['', 'KVKK Aydınlatma Metni'], true)) {
    $store['cookie_consent']['kvkk_label'] = 'Gizlilik Politikası (KVKK)';
}

// 6) Hızlı iletişim kapsülü — Zeynart ajans mesajları yerine As Cam mesajları
if (!is_array($store['quick_contact'] ?? null)) {
    $tel = (string)($site['phone_href'] ?? '+902164102747');
    $store['quick_contact'] = [
        'enabled' => true,
        'position' => 'right',
        'show_whatsapp' => true,
        'show_phone' => true,
        'whatsapp_number' => $tel,
        'whatsapp_label' => 'WhatsApp',
        'phone_display' => $phone,
        'phone_href' => $tel,
        'phone_label' => 'Hızlı Arama',
        'working_hours' => ['enabled' => false],
        'messages' => [
            'default' => 'Merhaba, As Cam ürünleri hakkında bilgi almak istiyorum',
            'service' => 'Merhaba, {title} ürünleriniz hakkında bilgi almak istiyorum',
            'project' => 'Merhaba, {title} projeniz hakkında bilgi almak istiyorum',
            'blog' => 'Merhaba, "{title}" yazınızı okudum; ürünleriniz hakkında bilgi almak istiyorum',
            'audit' => 'Merhaba, As Cam ürünleri hakkında bilgi almak istiyorum',
            'contact' => 'Merhaba, projemiz için teklif almak istiyorum',
        ],
        'custom_rules' => [],
    ];
}

if (!save_store($store)) {
    fwrite(STDERR, "store.json yazılamadı.\n");
    exit(1);
}
echo "Tamam: KVKK sayfası, yasal bağlantılar ve hızlı iletişim kapsülü güncellendi.\n";
