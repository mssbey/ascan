<?php
declare(strict_types=1);

/**
 * ASCAM ziyaretçi teması — ortak yardımcılar.
 * Tüm içerik panelin store.json yapısından okunur; burada içerik yoktur, yalnızca sunum yardımcıları vardır.
 */

$GLOBALS['zeynart_public_theme'] = [
    'stylesheet' => '/assets/css/ascam.css',
    'font_preloads' => ['/assets/fonts/archivo-latin.woff2', '/assets/fonts/archivo-latin-ext.woff2'],
];

/** Arayüz metinleri (içerik değil). EN karşılığı yoksa panelin t() sözlüğüne düşer. */
function at(string $tr): string
{
    static $en = [
        'İçeriğe geç' => 'Skip to content',
        'Ana menü' => 'Main menu',
        'Menüyü aç' => 'Open menu',
        'Menüyü kapat' => 'Close menu',
        'Ürün grupları' => 'Product groups',
        'Tüm ürünler' => 'All products',
        'görsel' => 'images',
        'Galeriyi aç' => 'Open gallery',
        'galerisi' => 'gallery',
        'Galeriyi inceleyin' => 'View the gallery',
        'Daha fazla göster' => 'Show more',
        'Kapat' => 'Close',
        'Önceki' => 'Previous',
        'Sonraki' => 'Next',
        'Ara' => 'Call',
        'E-posta' => 'Email',
        'Yol tarifi' => 'Directions',
        'Haritada aç' => 'Open in maps',
        'Teklif isteyin' => 'Request a quote',
        'Bu ürün grubu için teklif isteyin' => 'Request a quote for this group',
        'Diğer ürün grupları' => 'Other product groups',
        'Ana sayfa' => 'Home',
        'Adınız Soyadınız' => 'Full name',
        'Telefon' => 'Phone',
        'Konu' => 'Subject',
        'Mesajınız' => 'Your message',
        'Genel bilgi' => 'General information',
        'Gönder' => 'Send',
        'Gönderiliyor…' => 'Sending…',
        'Zorunlu alanlar * ile işaretlidir.' => 'Required fields are marked *.',
        'Bilgilerimin bu talep kapsamında As Cam tarafından işlenmesini ve bana dönüş yapılmasını onaylıyorum.' => 'I agree that As Cam may process my details to respond to this request.',
        'Mesajınız alındı. En kısa sürede size dönüş yapacağız.' => 'Your message has been received. We will get back to you shortly.',
        'Bir sorun oluştu. Lütfen tekrar deneyin ya da bizi arayın.' => 'Something went wrong. Please try again or call us.',
        'Merkez ofis' => 'Head office',
        'Faks' => 'Fax',
        'Tel' => 'Tel',
        'Keşfetmeye devam edin' => 'Keep exploring',
        'Sayfa bulunamadı' => 'Page not found',
        'Aradığınız sayfa taşınmış ya da kaldırılmış olabilir.' => 'The page you are looking for may have been moved or removed.',
        'Ana sayfaya dönün' => 'Back to home',
        'Ürünlere göz atın' => 'Browse products',
        'Dil' => 'Language',
        'Paneli aç' => 'Open panel',
        'Bu sayfayı düzenle' => 'Edit this page',
        'Bölüm' => 'Section',
        'Görseli büyüt' => 'Enlarge image',
        'Tüm hakları saklıdır.' => 'All rights reserved.',
        'Bize ulaşın' => 'Contact us',
        'Kurumsal' => 'Corporate',
        'İletişim' => 'Contact',
        'Ürünlerimiz' => 'Our products',
        'Projeler' => 'Projects',
        'Çerez Tercihleri' => 'Cookie Preferences',
    ];
    if (function_exists('zeynart_current_lang') && zeynart_current_lang() === 'en') {
        return $en[$tr] ?? (function_exists('t') ? t($tr) : $tr);
    }
    return $tr;
}

function ascam_icon(string $name, int $size = 20): string
{
    $paths = [
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
        'arrow-down' => '<path d="M12 5v14M6 13l6 6 6-6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'menu' => '<path d="M4 8h16M4 16h16"/>',
        'chev-left' => '<path d="m15 18-6-6 6-6"/>',
        'chev-right' => '<path d="m9 18 6-6-6-6"/>',
        'chev-down' => '<path d="m6 9 6 6 6-6"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'fax' => '<path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="7"/><rect x="4" y="13" width="7" height="7"/><rect x="13" y="13" width="7" height="7"/>',
        'expand' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
        'check' => '<path d="m5 12 5 5 9-10"/>',
    ];
    $p = $paths[$name] ?? $paths['arrow'];
    return '<svg class="ic ic-' . e($name) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

/** Yerel görselin genişlik/yükseklik bilgisi (CLS önleme). Çalışma başına önbellekli. */
function ascam_img_dims(string $src): array
{
    static $cache = [];
    if (isset($cache[$src])) {
        return $cache[$src];
    }
    $path = parse_url($src, PHP_URL_PATH) ?: '';
    $abs = ROOT_DIR . '/' . ltrim((string)$path, '/');
    $dims = [0, 0];
    if ($path !== '' && !str_contains($path, '..') && is_file($abs)) {
        $info = @getimagesize($abs);
        if (is_array($info)) {
            $dims = [(int)$info[0], (int)$info[1]];
        }
    }
    return $cache[$src] = $dims;
}

/** Use the clean transparent PNG for the original As Cam brand assets. */
function ascam_logo_src(array $site): string
{
    $src = (string)($site['logo'] ?? '');
    if (in_array($src, ['/uploads/2020/06/logo.png', '/uploads/2020/06/logo.webp', '/uploads/2020/06/cropped-logo.webp'], true)) {
        return '/uploads/2020/06/cropped-logo.png';
    }
    return $src;
}

function ascam_img(string $src, string $alt = '', array $attrs = []): string
{
    if (trim($src) === '') {
        return '';
    }
    [$w, $h] = ascam_img_dims($src);
    $a = array_merge(['loading' => 'lazy', 'decoding' => 'async'], $attrs);
    $html = '<img src="' . e(url($src)) . '" alt="' . e($alt) . '"';
    if ($w > 0 && $h > 0 && !isset($a['width'])) {
        $html .= ' width="' . $w . '" height="' . $h . '"';
    }
    foreach ($a as $k => $v) {
        if ($v === null || $v === false) {
            continue;
        }
        $html .= ' ' . e((string)$k) . ($v === true ? '' : '="' . e((string)$v) . '"');
    }
    return $html . '/>';
}

/** Yayındaki ürün grupları (panel: Sayfalar > Hizmet Alt Sayfaları), panel sırasıyla. */
function ascam_products(array $store): array
{
    return function_exists('zeynart_public_services') ? array_values(zeynart_public_services($store)) : [];
}

function ascam_product_gallery(array $srv): array
{
    $out = [];
    foreach ((array)($srv['gallery'] ?? []) as $g) {
        $src = is_array($g) ? trim((string)($g['src'] ?? '')) : trim((string)$g);
        if ($src !== '') {
            $out[] = ['src' => $src, 'alt' => is_array($g) ? trim((string)($g['alt'] ?? '')) : ''];
        }
    }
    return $out;
}

function ascam_product_cover(array $srv): string
{
    $banner = trim((string)($srv['banner_image'] ?? ''));
    if ($banner !== '' && !str_contains($banner, '/images/banners/')) {
        return $banner;
    }
    $g = ascam_product_gallery($srv);
    return $g[0]['src'] ?? $banner;
}

function ascam_products_base(array $store): string
{
    return '/' . zeynart_page_slug($store, 'hizmetler') . '/';
}

function ascam_product_url(array $store, array $srv): string
{
    return ascam_products_base($store) . rawurlencode((string)($srv['slug'] ?? '')) . '/';
}

function ascam_tel_href(array $site): string
{
    $raw = trim((string)($site['phone_href'] ?? ''));
    if ($raw === '') {
        $raw = preg_replace('/[^0-9+]/', '', (string)($site['phone'] ?? '')) ?? '';
    }
    return $raw !== '' ? 'tel:' . $raw : '';
}

function ascam_maps_url(array $store): string
{
    $custom = trim((string)($store['contact']['maps_url'] ?? ''));
    if ($custom !== '') {
        return $custom;
    }
    $site = $store['site'] ?? [];
    $q = trim((string)($site['address'] ?? '') . ' ' . (string)($site['city'] ?? ''));
    return $q !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q) : '';
}

/** Galeri ızgarası + ışık kutusu (ürün, proje ve özel sayfalarda ortak). */
function ascam_render_gallery(array $images, string $title, int $initial = 24, string $id = 'gallery'): string
{
    if ($images === []) {
        return '';
    }
    $total = count($images);
    $html = '<div class="gal" id="' . e($id) . '" data-gallery data-gallery-title="' . e($title) . '" data-initial="' . $initial . '">';
    $html .= '<ul class="gal__grid" role="list">';
    foreach ($images as $i => $im) {
        $alt = $im['alt'] !== '' ? $im['alt'] : ($title . ' — ' . ($i + 1));
        $hidden = $i >= $initial ? ' hidden' : '';
        [$w, $h] = ascam_img_dims($im['src']);
        $ratio = ($w > 0 && $h > 0) ? ' style="--r:' . round($w / $h, 4) . '"' : '';
        $html .= '<li class="gal__item"' . $hidden . $ratio . '>'
            . '<button type="button" class="gal__btn" data-gal-open="' . $i . '" aria-label="' . e(at('Görseli büyüt') . ': ' . $alt) . '">'
            . ascam_img($im['src'], $alt, ['sizes' => '(max-width: 700px) 50vw, 25vw', 'data-full' => url($im['src'])])
            . '<span class="gal__idx" aria-hidden="true">' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) . '</span>'
            . '</button></li>';
    }
    $html .= '</ul>';
    if ($total > $initial) {
        $html .= '<div class="gal__more"><button type="button" class="btn btn--line" data-gal-more>'
            . e(at('Daha fazla göster')) . ' <span class="gal__count" data-gal-count>' . $initial . ' / ' . $total . '</span></button></div>';
    }
    $html .= '</div>';
    return $html;
}

/** Ekranda bir kez basılan ışık kutusu iskeleti. */
function ascam_lightbox_shell(): string
{
    return '<dialog class="lb" data-lightbox aria-label="' . e(at('Galeriyi aç')) . '">'
        . '<div class="lb__top"><p class="lb__title" data-lb-title></p><p class="lb__count" data-lb-count aria-live="polite"></p>'
        . '<button type="button" class="lb__close" data-lb-close aria-label="' . e(at('Kapat')) . '">' . ascam_icon('close', 24) . '</button></div>'
        . '<figure class="lb__stage"><img data-lb-img alt=""/><figcaption data-lb-cap></figcaption></figure>'
        . '<button type="button" class="lb__nav lb__nav--prev" data-lb-prev aria-label="' . e(at('Önceki')) . '">' . ascam_icon('chev-left', 28) . '</button>'
        . '<button type="button" class="lb__nav lb__nav--next" data-lb-next aria-label="' . e(at('Sonraki')) . '">' . ascam_icon('chev-right', 28) . '</button>'
        . '</dialog>';
}

/** Sayfa başlığı (başlık + vurgu) — "Başlık <em>vurgu</em>" */
function ascam_heading(string $main, string $accent = ''): string
{
    $h = e($main);
    if (trim($accent) !== '') {
        $h .= ' <em>' . e($accent) . '</em>';
    }
    return $h;
}

/** Dil bağlantıları (site.languages; varsayılan TR + EN). */
function ascam_languages(array $store): array
{
    $langs = $store['site']['languages'] ?? ['tr', 'en'];
    $langs = array_values(array_intersect(is_array($langs) ? $langs : ['tr', 'en'], ['tr', 'en', 'de', 'ru']));
    return $langs !== [] ? $langs : ['tr'];
}
