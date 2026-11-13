<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/security.php';

/**
 * Zeynart Görsel Sayfa ve Bölüm Tasarımcısı — Sunucu Tarafı Yorumlayıcı
 * Her satır çoklu sütun düzenini, boşluk/renk ayarlarını ve 16 yerleşik Zeynart bileşenini destekler.
 */
function render_builder_sections(array $sections, array $store): string
{
    if ($sections === []) {
        return !empty($GLOBALS['zeynart_is_admin_view'])
            ? '<div class="zb-canvas zb-canvas-empty" data-zvs-builder-canvas="1"></div>'
            : '';
    }

    $html = '<div class="zb-canvas" data-zvs-builder-canvas="1">';
    foreach (array_values($sections) as $secIdx => $sec) {
        if (!is_array($sec) || !empty($sec['disabled'])) {
            continue;
        }
        $layout = (string)($sec['layout'] ?? '1');
        $colClass = match ($layout) {
            '1-1' => 'zb-cols-1-1',
            '1-2' => 'zb-cols-1-2',
            '2-1' => 'zb-cols-2-1',
            '1-1-1' => 'zb-cols-1-1-1',
            '1-1-1-1' => 'zb-cols-1-1-1-1',
            default => 'zb-cols-1',
        };

        $styleCfg = is_array($sec['style'] ?? null) ? $sec['style'] : [];
        $py = max(0, min(220, (int)($styleCfg['padding_y'] ?? 64)));
        $gap = max(0, min(96, (int)($styleCfg['gap'] ?? 28)));
        $bg = trim((string)($styleCfg['bg_color'] ?? ''));
        $bgImage = trim((string)($styleCfg['bg_image'] ?? ''));
        $containerMode = (string)($styleCfg['container'] ?? 'boxed');
        $visibility = (string)($styleCfg['visibility'] ?? 'all');
        $secId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($sec['anchor_id'] ?? '')) ?? '';

        $visClass = match ($visibility) {
            'desktop' => ' zb-hide-mobile',
            'mobile' => ' zb-hide-desktop',
            default => '',
        };

        $inlineStyles = ["--zb-py:{$py}px", "--zb-gap:{$gap}px"];
        if ($bg !== '' && preg_match('/^[#a-zA-Z0-9(),.\s%-]+$/', $bg)) {
            $inlineStyles[] = 'background:' . $bg;
        }
        if ($bgImage !== '') {
            $inlineStyles[] = 'background-image:linear-gradient(rgba(7,15,18,.82),rgba(7,15,18,.9)),url(' . e(url($bgImage)) . ');background-size:cover;background-position:center';
        }

        $idAttr = $secId !== '' ? ' id="' . e($secId) . '"' : '';
        $html .= '<section class="zb-section' . $visClass . '"' . $idAttr . ' data-zb-idx="' . $secIdx . '" style="' . e(implode(';', $inlineStyles)) . '">';
        $wrapClass = $containerMode === 'full' ? 'zb-row ' . $colClass : 'container zb-row ' . $colClass;
        $html .= '<div class="' . e($wrapClass) . '">';

        $columns = is_array($sec['columns'] ?? null) ? $sec['columns'] : [];
        foreach ($columns as $col) {
            $html .= '<div class="zb-col">';
            $widgets = is_array($col['widgets'] ?? null) ? $col['widgets'] : [];
            foreach ($widgets as $widget) {
                if (is_array($widget)) {
                    $html .= render_builder_widget($widget, $store);
                }
            }
            $html .= '</div>';
        }

        $html .= '</div></section>';
    }
    $html .= '</div>';

    return $html;
}

function render_builder_widget(array $widget, array $store): string
{
    $type = (string)($widget['type'] ?? 'heading_text');
    $p = is_array($widget['props'] ?? null) ? $widget['props'] : [];

    switch ($type) {
        case 'hero_banner':
            $eyebrow = (string)($p['eyebrow'] ?? 'ZEYNART STÜDYO');
            $title = (string)($p['title'] ?? 'Yeni nesil dijital deneyim.');
            $subtitle = (string)($p['subtitle'] ?? '');
            $btnLabel = (string)($p['btn_label'] ?? 'İletişime geçin');
            $btnUrl = (string)($p['btn_url'] ?? '/iletisim/');
            $bannerImg = (string)($p['image'] ?? '/images/banners/services.webp');
            $out = '<div class="page-banner" style="border-radius:26px;overflow:hidden">';
            if ($bannerImg !== '') {
                $out .= '<div class="page-banner-art" aria-hidden="true"><img alt="" class="fill-img" loading="lazy" decoding="async" src="' . e(url($bannerImg)) . '"/></div>';
            }
            $out .= '<div class="page-intro" style="padding:56px 40px">';
            if ($eyebrow !== '') {
                $out .= '<p class="eyebrow"><span></span>' . e($eyebrow) . '</p>';
            }
            $out .= '<h1>' . nl2br_e($title) . '</h1>';
            if ($subtitle !== '') {
                $out .= '<p>' . nl2br_e($subtitle) . '</p>';
            }
            if ($btnLabel !== '') {
                $out .= '<div style="margin-top:26px"><a class="button button-primary" data-magnetic="true" href="' . e(url($btnUrl)) . '">' . e($btnLabel) . ' ' . zeynart_icon('arrow-right', 17) . '</a></div>';
            }
            $out .= '</div></div>';
            return $out;

        case 'heading_text':
            $eyebrow = (string)($p['eyebrow'] ?? '');
            $title = (string)($p['title'] ?? '');
            $muted = (string)($p['muted'] ?? '');
            $copy = (string)($p['copy'] ?? '');
            $btnLabel = (string)($p['btn_label'] ?? '');
            $btnUrl = (string)($p['btn_url'] ?? '/iletisim/');
            $card = !empty($p['card_style']);
            $out = $card ? '<div class="zb-card">' : '<div>';
            if ($eyebrow !== '') {
                $out .= '<p class="eyebrow"><span></span>' . e($eyebrow) . '</p>';
            }
            if ($title !== '' || $muted !== '') {
                $out .= '<h2>' . nl2br_e($title);
                if ($muted !== '') {
                    $out .= '<br/><span class="muted">' . nl2br_e($muted) . '</span>';
                }
                $out .= '</h2>';
            }
            if ($copy !== '') {
                $out .= '<p class="section-copy" style="margin-top:14px">' . nl2br_e($copy) . '</p>';
            }
            if ($btnLabel !== '') {
                $out .= '<div style="margin-top:24px"><a class="button" data-magnetic="true" href="' . e(url($btnUrl)) . '">' . e($btnLabel) . ' ' . zeynart_icon('arrow-right', 17) . '</a></div>';
            }
            $out .= '</div>';
            return $out;

        case 'rich_text':
            $content = sanitize_rich_html((string)($p['html'] ?? '<p>İçerik alanınızı buradan düzenleyebilirsiniz.</p>'));
            return '<div class="journal-prose-html zb-card">' . $content . '</div>';

        case 'image_showcase':
            $src = (string)($p['src'] ?? '/images/zeynart-identity-still-life.webp');
            $alt = (string)($p['alt'] ?? 'Zeynart Görsel');
            $caption = (string)($p['caption'] ?? '');
            $out = '<figure class="company-art" style="margin:0">';
            $out .= '<img src="' . e(url($src)) . '" alt="' . e($alt) . '" loading="lazy" decoding="async" style="width:100%;height:auto;border-radius:20px"/>';
            if ($caption !== '') {
                $out .= '<span>' . e($caption) . '</span>';
            }
            $out .= '</figure>';
            return $out;

        case 'gallery_grid':
            $images = is_array($p['images'] ?? null) ? $p['images'] : [];
            if ($images === []) {
                $images = [
                    ['src' => '/images/zeynart-identity-still-life.webp', 'alt' => 'Zeynart Kurumsal Kimlik'],
                    ['src' => '/images/zeynart-emblem-gallery.webp', 'alt' => 'Zeynart Amblem'],
                    ['src' => '/images/zeynart-brand-art.webp', 'alt' => 'Zeynart Marka Sanatı'],
                ];
            }
            $out = '<div class="zb-gallery-grid">';
            foreach ($images as $im) {
                $s = is_array($im) ? (string)($im['src'] ?? '') : (string)$im;
                $a = is_array($im) ? (string)($im['alt'] ?? '') : '';
                if ($s === '') {
                    continue;
                }
                $out .= '<div class="zb-gallery-item"><img src="' . e(url($s)) . '" alt="' . e($a) . '" loading="lazy" decoding="async"/></div>';
            }
            $out .= '</div>';
            return $out;

        case 'service_cards':
            $limit = max(1, min(12, (int)($p['limit'] ?? 6)));
            $services = array_slice($store['services'] ?? [], 0, $limit);
            $out = '<div class="service-grid studio-service-grid">';
            foreach ($services as $idx => $srv) {
                $num = (string)($srv['num'] ?? str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT));
                $icon = (string)($srv['icon'] ?? 'code');
                $cls = (string)($srv['card_class'] ?? 'studio-service-web-tasarim');
                $href = url('/hizmetler/' . ($srv['slug'] ?? '') . '/');
                $out .= '<a class="service-card studio-service-card ' . e($cls) . '" href="' . e($href) . '">';
                $out .= '<div class="service-card-top">' . zeynart_icon($icon, 27, '', 1.3) . '<span>/' . e($num) . '</span>' . zeynart_icon('arrow-up-right', 20, 'card-arrow') . '</div>';
                $out .= '<div class="service-object" aria-hidden="true"><div class="service-object-orbit"></div><div class="service-object-main"><span></span><i></i><i></i><i></i></div><div class="service-object-satellite">' . zeynart_icon($icon, 22, '', 1.3) . '</div></div>';
                $out .= '<div class="studio-service-copy"><h3>' . e($srv['title'] ?? '') . '</h3><p>' . e($srv['short'] ?? '') . '</p><div class="service-card-bottom"><span>' . e($srv['card_tag'] ?? '') . '</span>' . zeynart_icon('arrow-up-right', 19) . '</div></div>';
                $out .= '</a>';
            }
            $out .= '</div>';
            return $out;

        case 'project_showcase':
            $projects = $store['projects'] ?? [];
            $out = '<div class="project-grid">';
            foreach ($projects as $idx => $prj) {
                $out .= render_project_card_html($prj, $idx);
            }
            $out .= '</div>';
            return $out;

        case 'process_steps':
            $steps = is_array($p['steps'] ?? null) && $p['steps'] !== []
                ? $p['steps']
                : ($store['home']['process']['steps'] ?? []);
            $out = '<div class="process-grid">';
            foreach ($steps as $idx => $st) {
                $num = (string)($st['num'] ?? str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT));
                $icon = (string)($st['icon'] ?? 'sparkles');
                $out .= '<article class="process-step">';
                $out .= '<div class="studio-step-top"><span class="step-number">' . e($num) . '</span>' . zeynart_icon($icon, 26, '', 1.3) . '</div>';
                $out .= '<div class="step-line"><i></i></div>';
                $out .= '<h3>' . e($st['title'] ?? '') . '</h3>';
                $out .= '<p>' . e($st['desc'] ?? '') . '</p>';
                if (!empty($st['output'])) {
                    $out .= '<div class="studio-step-output"><span>' . e($st['output']) . '</span>' . zeynart_icon('arrow-up-right', 16) . '</div>';
                }
                $out .= '</article>';
            }
            $out .= '</div>';
            return $out;

        case 'stats_bar':
            $items = is_array($p['items'] ?? null) && $p['items'] !== [] ? $p['items'] : [
                ['value' => '2014', 'label' => 'Kuruluş Yılı & Deneyim'],
                ['value' => '62+', 'label' => 'Kurumsal Marka İş Birliği'],
                ['value' => '%99,9', 'label' => 'Sunucu Uptime Sürekliliği'],
                ['value' => '7/24', 'label' => 'Teknik Destek & İzleme'],
            ];
            $out = '<div class="zb-stats-grid">';
            foreach ($items as $it) {
                $out .= '<div class="zb-stat-item"><strong>' . e($it['value'] ?? '') . '</strong><span>' . e($it['label'] ?? '') . '</span></div>';
            }
            $out .= '</div>';
            return $out;

        case 'pricing_packages':
            $packages = is_array($p['packages'] ?? null) && $p['packages'] !== []
                ? $p['packages']
                : ($store['services'][4]['hosting_data']['packages'] ?? []);
            $out = '<div class="hosting-packages">';
            foreach ($packages as $pkg) {
                $feat = !empty($pkg['featured']) ? ' featured' : '';
                $btnCls = !empty($pkg['featured']) ? 'button button-primary' : 'button';
                $name = (string)($pkg['name'] ?? 'Paket');
                $out .= '<article class="hosting-package' . $feat . '">';
                $out .= '<p class="eyebrow">' . e($pkg['eyebrow'] ?? mb_strtoupper($name, 'UTF-8')) . '</p>';
                $out .= '<h3>' . e($pkg['disk'] ?? $pkg['price'] ?? '') . '</h3>';
                $out .= '<p class="muted">' . e($pkg['disk_label'] ?? $pkg['subtitle'] ?? '') . '</p>';
                $out .= '<ul>';
                foreach (($pkg['features'] ?? []) as $ft) {
                    $out .= '<li>' . zeynart_icon('check', 15) . e($ft) . '</li>';
                }
                $out .= '</ul>';
                $ctaHref = (string)($pkg['cta_url'] ?? ('/iletisim/?paket=' . rawurlencode($name)));
                $out .= '<a class="' . $btnCls . '" data-magnetic="true" href="' . e(url($ctaHref)) . '">' . e($name) . ' için teklif alın' . zeynart_icon('arrow-right', 17) . '</a>';
                $out .= '</article>';
            }
            $out .= '</div>';
            return $out;

        case 'faq_accordion':
            $heading = (string)($p['heading'] ?? 'Merak edilenler.');
            $items = is_array($p['items'] ?? null) ? $p['items'] : [];
            $out = '<div class="faq" style="margin-top:0">';
            if ($heading !== '') {
                $out .= '<h2>' . e($heading) . '</h2>';
            }
            foreach ($items as $fq) {
                $q = (string)($fq['q'] ?? '');
                $a = (string)($fq['a'] ?? '');
                if ($q === '') {
                    continue;
                }
                $out .= '<details><summary>' . e($q) . '</summary><p>' . nl2br_e($a) . '</p></details>';
            }
            $out .= '</div>';
            return $out;

        case 'client_logos':
            $limit = max(1, min(62, (int)($p['limit'] ?? 12)));
            $clients = array_slice($store['about']['clients'] ?? [], 0, $limit);
            $out = '<div class="client-logo-grid">';
            foreach ($clients as $cl) {
                $out .= '<a href="' . e($cl['url'] ?? '#') . '" target="_blank" rel="noopener noreferrer" class="client-logo-card" aria-label="' . e(($cl['name'] ?? '') . ' web sitesini aç') . '">';
                $out .= '<img alt="' . e($cl['alt'] ?? $cl['name'] ?? '') . '" loading="lazy" width="400" height="220" decoding="async" src="' . e(url((string)($cl['image'] ?? ''))) . '"/>';
                $out .= '<span>' . e($cl['name'] ?? '') . zeynart_icon('arrow-up-right', 13) . '</span></a>';
            }
            $out .= '</div>';
            return $out;

        case 'reviews_grid':
            require_once __DIR__ . '/google_reviews.php';
            $grWidget = render_google_reviews_section($store, [
                'wrapped' => false,
                'force' => true,
                'layout' => !empty($p['layout']) ? (string)$p['layout'] : null,
            ]);
            if ($grWidget !== '') {
                return $grWidget;
            }
            $reviews = $store['about']['reviews'] ?? [];
            $out = '<div class="company-reviews">';
            foreach ($reviews as $rv) {
                $out .= '<figure><span class="company-quote" aria-hidden="true">“</span>';
                $out .= '<blockquote>' . e($rv['text'] ?? '') . '</blockquote>';
                $out .= '<figcaption><strong>' . e($rv['author'] ?? '') . '</strong><span>' . e($rv['date'] ?? '') . '</span></figcaption></figure>';
            }
            $out .= '</div>';
            return $out;

        case 'cta_banner':
            $eyebrow = (string)($p['eyebrow'] ?? 'BİR FİKRİNİZ Mİ VAR?');
            $h1 = (string)($p['line1'] ?? 'Birlikte');
            $h2 = (string)($p['line2'] ?? 'hayata');
            $accent = (string)($p['accent'] ?? 'geçirelim.');
            $btn = (string)($p['btn_label'] ?? 'Projenizi anlatın');
            $btnUrl = (string)($p['btn_url'] ?? '/iletisim/');
            return '<div class="cta-section" style="border-radius:26px"><div class="cta-glow" aria-hidden="true"></div><div class="container"><p class="eyebrow">' . e($eyebrow) . '</p><h2>' . e($h1) . '<br/>' . e($h2) . ' <span>' . e($accent) . '</span></h2><a class="button button-primary" data-magnetic="true" href="' . e(url($btnUrl)) . '">' . e($btn) . zeynart_icon('arrow-up-right', 17) . '</a></div></div>';

        case 'contact_form_embed':
            $token = csrf_token();
            $services = $store['services'] ?? [];
            $cntCfg = is_array($store['contact'] ?? null) ? $store['contact'] : [];
            $reqPhone = ($cntCfg['require_phone'] ?? true) !== false;
            $phoneLbl = trim((string)($cntCfg['lbl_phone'] ?? '')) ?: ($reqPhone ? 'Cep telefonu *' : 'Cep telefonu');
            $phonePh = trim((string)($cntCfg['ph_phone'] ?? '')) ?: '05XX XXX XX XX';
            $out = '<div class="contact-form-panel"><form class="contact-form" method="post" action="' . e(url('/api/iletisim')) . '" data-ajax-contact="true" toolname="submit_project_inquiry" tooldescription="Zeynart Web Bilişim ekibine kurumsal web tasarım, özel yazılım, mobil uygulama, e-ticaret veya bulut hosting projesi için teklif talebi gönderir.">';
            $out .= '<input type="hidden" name="csrf_token" value="' . e($token) . '" toolparamdescription="Oturum güvenlik doğrulama anahtarı"/>';
            $out .= '<input type="hidden" name="lang" value="' . e(zeynart_current_lang()) . '"/>';
            $out .= '<div class="form-row"><label class="form-field">' . e(t('Ad soyad *')) . '<input name="name" autocomplete="name" required minlength="2" maxlength="100" placeholder="' . e(t('Adınız ve soyadınız')) . '" toolparamdescription="İletişime geçen kişinin adı ve soyadı"/></label>';
            $out .= '<label class="form-field">' . e(t($phoneLbl)) . '<input type="tel" inputmode="tel" autocomplete="tel" name="phone"' . ($reqPhone ? ' required' : '') . ' minlength="10" maxlength="30" placeholder="' . e(t($phonePh)) . '" toolparamdescription="Geri dönüş yapılacak cep telefonu veya WhatsApp numarası"/></label></div>';
            $out .= '<div class="form-row"><label class="form-field">' . e(t('E-posta adresiniz *')) . '<input type="email" name="email" autocomplete="email" required maxlength="200" placeholder="' . e(t('siz@sirketiniz.com')) . '" toolparamdescription="Geri dönüş yapılacak e-posta adresi"/></label>';
            $out .= '<label class="form-field">' . e(t('Şirket / kurum')) . '<input name="company" autocomplete="organization" maxlength="150" placeholder="' . e(t('Şirket veya kurum adı')) . '" toolparamdescription="Proje sahibi şirket veya kurum adı"/></label></div>';
            $out .= '<label class="form-field">' . e(t('Hizmet alanı *')) . '<select name="service" required toolparamdescription="Teklif alınmak istenen dijital hizmet alanı"><option value="" disabled selected>' . e(t('İlgilendiğiniz alanı seçin')) . '</option>';
            foreach ($services as $srv) {
                $out .= '<option value="' . e($srv['slug'] ?? '') . '">' . e($srv['title'] ?? '') . '</option>';
            }
            $out .= '<option value="birlikte-belirleyelim">' . e(t('Birlikte belirleyelim')) . '</option></select></label>';
            $out .= '<label class="form-field">' . e(t('Proje kapsamı ve hedefleriniz *')) . '<textarea name="message" required minlength="10" maxlength="5000" rows="4" placeholder="' . e(t('Proje hedeflerinizi kısaca paylaşın.')) . '" toolparamdescription="Proje kapsamı ve iş hedefleri açıklaması"></textarea></label>';
            $out .= '<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="display:none" toolparamdescription="Spam koruma alanı, her zaman boş bırakın"/>';
            $out .= '<label class="form-check"><input type="checkbox" name="consent" value="1" required toolparamdescription="Gizlilik bilgilendirmesi ve iletişim onayı"/><span><a href="' . e(url('/gizlilik/')) . '">' . e(t('Gizlilik bilgilendirmesini')) . '</a> ' . e(t('okudum ve kabul ediyorum.')) . '</span></label>';
            $out .= '<div class="form-actions"><button type="submit" class="button button-primary">' . e(t('Proje talebini gönder')) . ' ' . zeynart_icon('send', 16) . '</button></div>';
            $out .= '<div class="form-feedback" aria-live="polite"></div></form></div>';
            return $out;

        case 'custom_html':
            return '<div class="zb-custom-html">' . sanitize_rich_html((string)($p['html'] ?? '')) . '</div>';

        case 'spacer_divider':
            $h = max(8, min(180, (int)($p['height'] ?? 32)));
            $showLine = !empty($p['show_line']);
            return '<div style="padding-block:' . (int)($h / 2) . 'px">' . ($showLine ? '<hr class="zb-divider"/>' : '') . '</div>';

        case 'quote_wizard':
            return function_exists('zeynart_render_quote_wizard_section')
                ? zeynart_render_quote_wizard_section($store, 'widget')
                : '';

        default:
            return '';
    }
}

function render_project_visual_html(array $prj, bool $large = false): string
{
    $vType = (string)($prj['visual_type'] ?? 'orbit');
    $lgClass = $large ? 'large' : '';
    $customImg = trim((string)($prj['image'] ?? ''));

    if ($vType === 'image' && $customImg !== '') {
        return '<div class="project-visual ' . $lgClass . '" style="position:relative;overflow:hidden"><img src="' . e(url($customImg)) . '" alt="' . e($prj['name'] ?? '') . '" class="fill-img" loading="lazy" decoding="async"/></div>';
    }

    if ($vType === 'forma') {
        return '<div aria-hidden="true" class="project-visual forma ' . $lgClass . '"><span class="forma-title">forma<span>' . e(t('yaşamın doğal hali.')) . '</span></span><div class="sculpture"><div class="vase vase-one"></div><div class="vase vase-two"></div><div class="sculpture-base"></div></div><span class="visual-caption">' . e(t('AZ. ÖZ. ZAMANSIZ.')) . '</span></div>';
    }

    if ($vType === 'nexus') {
        return '<div aria-hidden="true" class="project-visual nexus ' . $lgClass . '"><div class="nexus-orbits"><i></i><i></i><i></i></div><span class="nexus-title">nexus<span>' . e(t('Birlikte. Daha ileri.')) . '</span></span><div class="task-float task-one"><i></i> ' . e(t('Tasarımdan gerçeğe')) . ' <span>↗</span></div><div class="task-float task-two"><span>✳</span> ' . e(t('Yeni bir çalışma biçimi.')) . '</div><span class="visual-caption">' . e(t('FİKİRLERE YER AÇIN.')) . '</span></div>';
    }

    // Default: orbit
    return '<div aria-hidden="true" class="project-visual orbit ' . $lgClass . '"><div class="orbit-halo"></div><div class="finance-card"><div class="finance-top"><b>orbit<span>®</span></b><span>•••</span></div><small>' . e(t('TOPLAM BAKİYE')) . '</small><strong>₺ 284.650<span>,00</span></strong><div class="finance-chart"><i style="height:25%"></i><i style="height:40%"></i><i style="height:31%"></i><i style="height:52%"></i><i style="height:45%"></i><i style="height:66%"></i><i style="height:57%"></i><i style="height:72%"></i><i style="height:68%"></i><i style="height:85%"></i><i style="height:76%"></i><i style="height:96%"></i></div><div class="finance-bottom"><span>' . e(t('Finansın, senin elinde.')) . '</span><span>↗</span></div></div><span class="visual-caption">' . e(t('DAHA NET BİR FİNANSAL GELECEK.')) . '</span></div>';
}

function render_project_card_html(array $prj, int $idx = 0): string
{
    $href = url('/projeler/' . ($prj['slug'] ?? '') . '/');
    $tm = (string)($prj['trademark'] ?? '®');
    $tag = (string)($prj['tag_label'] ?? ($prj['sector'] ?? t('KONSEPT ÇALIŞMA')));
    $sectorRaw = trim((string)($prj['sector_filter'] ?? ($prj['sector'] ?? 'Kurumsal')));
    $sectorSlug = slugify($sectorRaw) ?: 'kurumsal';
    $metrics = is_array($prj['metrics'] ?? null) ? array_slice($prj['metrics'], 0, 2) : [];

    $out = '<a class="project-card project-' . ($idx % 3) . '" data-cursor="project" data-project-sector="' . e($sectorSlug) . '" href="' . e($href) . '">';
    $out .= '<div class="project-image">' . render_project_visual_html($prj, false);
    $out .= '<span class="project-open">' . zeynart_icon('arrow-up-right', 24) . '</span>';
    if ($tag !== '') {
        $out .= '<span class="concept-tag">' . e($tag) . '</span>';
    }
    $out .= '</div>';
    $out .= '<div class="project-info"><div><h3>' . e($prj['name'] ?? '');
    if ($tm !== '') {
        $out .= '<span>' . e($tm) . '</span>';
    }
    $out .= '</h3><p>' . e($prj['scope'] ?? '') . '</p>';
    if ($metrics !== []) {
        $out .= '<div class="project-card-kpis">';
        foreach ($metrics as $mk) {
            if (!empty($mk['value'])) {
                $out .= '<span><strong>' . e((string)$mk['value']) . '</strong> ' . e((string)($mk['label'] ?? '')) . '</span>';
            }
        }
        $out .= '</div>';
    }
    $out .= '</div>';
    $out .= '<span>' . e($prj['year'] ?? '2026') . ' ' . zeynart_icon('arrow-up-right', 20) . '</span></div></a>';
    return $out;
}
