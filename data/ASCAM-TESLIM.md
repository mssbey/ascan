# ASCAM yeni site — teslim notu

## Çalıştırma
- Gereksinim: PHP 8.1+ (GD + WebP). Veritabanı: panelin kendi `data/store.json` deposu (panelden MySQL'e geçiş seçeneği aynen duruyor).
- Yerel önizleme: proje kökünde `php -S 127.0.0.1:8088 index.php` → http://127.0.0.1:8088 · Panel: http://127.0.0.1:8088/admin/
- Sunucuda: Apache/LiteSpeed + mevcut `.htaccess`. `data/` ve `uploads/` yazılabilir olmalı.
- Panel kullanıcısı: `ascam` (ilk girişten sonra Panel > Ayarlar > Kullanıcılar'dan parolayı değiştirin).

## İçerik taşıma / geri dönüş
- Taşıma betiği: `php data/migrations/ascam_seed.php --images=<wp-görsel-klasörü> [--force]`
  - Var olan `store.json`'u önce `data/backups/store-pre-ascam-<tarih>.json` olarak yedekler; `--force` olmadan hiçbir şeyi ezmez.
- Panel her kayıtta otomatik revizyon tutar (Panel > Sürüm Geçmişi).
- Eski temaya dönüş: `data/backups/legacy-theme/` içindeki `pages/`, `includes/` ve `admin/pages*.js` dosyalarını geri kopyalayın.
- Uçtan uca test (yalnızca test kopyasında): `php data/migrations/e2e_panel_test.php http://127.0.0.1:8089`

## Hangi bölüm panelde nereden yönetilir
| Site bölümü | Panel yeri | Veri alanı |
|---|---|---|
| Logo, şirket adı, slogan | Ayarlar > Marka | `site.*` |
| Üst menü + "Teklif alın" butonu | Menüler > Üst Menü | `menus.header` |
| Alt bilgi sütunları, telif | Menüler > Alt Menü | `menus.footer` |
| Ana sayfa bölüm sırası / gizle-göster | Sayfalar > Ana Sayfa > Bölüm Sıralaması | `home.section_order/visibility` |
| Açılış sahnesi (görsel, başlık, vurgu, metin, butonlar, alt şerit) | Ana Sayfa > Hero & Slaytlar | `home.hero` |
| Numaralı ürün keşif alanı | Hizmet Alt Sayfaları (kayıtlar + sıra) · başlık: Hizmetler sayfası | `services[]`, `services_page` |
| Hikâye girişi (2004, başlık, şerit) | Ana Sayfa > Hikâye Girişi | `home.intro` (kronoloji Hakkımızda paragraflarından) |
| Üretim sahnesi | Ana Sayfa > Üretim Sahne Paneli | `home.brand_scene` |
| Hizmetlerimiz (5 kart) | Ana Sayfa > Değerler / Süreç Kartları | `home.process` |
| Çözüm ortakları (Franke) | Hakkımızda > Müşteri/Referans Logoları | `about.clients` |
| Teklif çağrısı bandı (tüm sayfalar) | Görünüm > Global CTA | `cta_global` |
| Ürünler sayfası başlığı ve banner | Sayfalar > Hizmetler (URL: /urunler/) | `services_page`, `page_slugs.hizmetler` |
| Ürün grubu: ad, açıklama, kapak, **galeri**, sıra, yayın durumu, SEO | Sayfalar > Hizmet Alt Sayfaları > [grup] — yeni **Galeri** kartı | `services[].gallery` |
| Kurumsal sayfa (başlık, kronoloji, distribütörlükler) | Sayfalar > Hakkımızda | `about.*` |
| Üretim sayfası (metin, 3 gösterge, galeri) | Sayfalar > Özel Sayfalar > Üretim (oluşturucu: stats_bar + gallery_grid) | `custom_pages[cp-uretim]` |
| İletişim bilgileri (adres, tel, **faks**, e-posta, harita linki) | Sayfalar > İletişim | `site.*`, `contact.*` |
| Form talepleri | Talepler > Mesajlar (SMTP açılırsa e-posta da gider) | `messages[]` |
| Eski WP adresi yönlendirmeleri | Google & SEO > 301 Yönlendirmeler | `redirects[]` |
| Sayfa başlığı/açıklaması/paylaşım görseli | Her sayfanın "SEO & Yapay Zeka" sekmesi | `seo.pages.*`, kayıt `seo` |
| EN sürümü | Panelin otomatik çeviri katmanı (Ayarlar > Çoklu Dil); dil listesi `site.languages` | — |
| Projeler / Referanslar | Proje Alt Sayfaları (sayfa şu an **taslak**; galeri kartı eklendi) | `projects[]` |

## Doğrulanamayan / kaynakta olmayan içerik
- Canlı sitede **Projeler** ve **Hizmetler** sayfaları boş; proje/referans kaydı yok → Projeler sayfası taslak, `/projeler/` ve `/hizmetler/` → `/urunler/` yönlenir. Proje eklenip sayfa yayına alınınca bu yönlendirmeyi silin.
- Ürün gruplarında grup bazında açıklama/teknik bilgi yok; yalnızca kurumsal metinden türetilen tek cümlelik giriş kullanıldı. Model adı/teknik özellik eklenmedi.
- Video yok → açılışta video kullanılmadı.
- Sosyal medya bağlantıları canlı sitede boş yer tutucu (twitter.com/, facebook.com/) → eklenmedi.
- KVKK / gizlilik / çerez metni yok → yasal sayfalar taslak; formdaki onay kutusu politika bağlantısı içermez.
- Görseller düşük çözünürlüklü (en büyük 1280 px; banyo 400 px). Tam ekran alanlar için yüksek çözünürlüklü orijinaller önerilir.
- Tema demosuna ait 2019/04 tarihli stok görseller (ASCAM'a ait değil) taşınmadı.
- Çözüm ortağı yalnızca Franke logosu; Yangın kapısı görseli Uzunlar Çelik Kapı kataloğundan.
- Google harita gömmesi yok (koordinat doğrulanmadı); adresten Google Maps arama bağlantısı üretiliyor.

## Alt bilgi yasal bağlantıları · KVKK · Hızlı iletişim (2026-11-08)
- `php data/migrations/ascam_footer_legal.php` (tekrar çalıştırılabilir; seed'den sonra da çalıştırın): KVKK Aydınlatma Metni (`/kvkk/`), Çerez Politikası yayında, alt bilgi linkleri, çerez paneli KVKK bağlantısı, WhatsApp + Hızlı Arama kapsülü (`quick_contact`).
- Kapsül ve çerez paneli stilleri `assets/css/ascam.css` içinde (tema renkleri). WhatsApp numarası şimdilik sabit hat (+90 216 410 27 47) — WhatsApp hattı farklıysa Panel > Hızlı İletişim'den değiştirin.
- KVKK metni genel bir şablondur; yayından önce şirketin hukuk danışmanına onaylatılmalıdır.
