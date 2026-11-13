/* ZEYNART DİJİTAL STÜDYO v4.0 — Lazy Modül: ops_system */
(function () {
  'use strict';
  if (typeof window.__ZS_REGISTER_CHUNK__ !== 'function') return;
  window.__ZS_REGISTER_CHUNK__('ops_system', function (CTX) {
  const { CFG, ROOT, S, api, applyLiveAdminBrandState, copyTextSafe, createDefaultRenewalDraft, detectSocialBadgeLabel, ensureConsolidatedFamilyBar, ensureNormalizedHomeSections, esc, extractGoogleFontName, isSuperAdmin, loadI18nTranslateQueue, markHistoryPush, navigateTo, normalizeClientStoreMaps, openMediaPicker, refreshI18nStudioMount, renderFontSelectOptions, resolvePreviewUrl, resolveUrl, syncAdminUrl, syncUserRoleUi, toast, uid, updateSidebarBadges } = CTX;

  /* ======================== MODULE 13: SYSTEM, IDENTITY, USERS & SECURITY SETTINGS ======================== */
  function mountDatabaseStudioTab(host) {
    if (!host) return;
    if (!S.databaseStatus) {
      host.innerHTML = `<div class="zs-card" style="padding:36px;text-align:center"><p class="zs-badge-pill">VERİTABANI MOTORU TARANIYOR</p><div style="margin-top:8px;color:var(--text-muted)">SQL tablo şeması ve diskteki JSON envanteri hesaplanıyor...</div></div>`;
      api('database_status_get', {}).then(res => {
        if (res.database_status) S.databaseStatus = res.database_status;
        if (S.view === 'settings' && S.tabs.settings === 'database') {
          mountDatabaseStudioTab(document.getElementById('setDatabaseHost'));
        }
      }).catch(err => toast(err.message, true));
      return;
    }

    const db = S.databaseStatus || {};
    const cfg = db.config || {};
    const disk = db.disk_json || {};
    const selectedDriver = S.dbDraftDriver || cfg.driver || 'mysql';
    const sqlActive = db.active_engine === 'mysql' || db.active_engine === 'sqlite';
    const postsJsonCnt = Number(disk.posts_json_count || 0);
    const totalJsonCnt = Number(disk.total_json_files || 0);
    const totalJsonMb = Number(disk.total_json_mb || 0);
    const postsInSql = Number(db.posts_in_sql || 0);
    const totalSqlRows = Number(db.total_sql_rows || 0);
    const canCleanJson = Boolean(db.can_clean_legacy_json);
    const prefixVal = cfg.prefix || 'zn_';
    const sqlDownloadUrl = CFG.apiUrl + '?action=database_download_sql&csrf_token=' + encodeURIComponent(CFG.csrfToken);

    const defaultTables = [
      { table: prefixVal + 'posts', label: 'Blog Makaleleri, Çoklu Dil & İndeks', rows: 0, size_kb: 0 },
      { table: prefixVal + 'store_kv', label: 'Site Ayarları, Sayfalar, CRM & Loglar', rows: 0, size_kb: 0 },
      { table: prefixVal + 'analytics_daily', label: 'Zero-Cookie Ziyaretçi İstatistikleri', rows: 0, size_kb: 0 },
      { table: prefixVal + 'revisions', label: '5 Sürüm Zaman Makinesi', rows: 0, size_kb: 0 }
    ];
    const tablesList = (Array.isArray(db.tables) && db.tables.length) ? db.tables : defaultTables;

    host.innerHTML = `
      <div class="zs-section-head">
        <div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="zs-badge-pill">🗄️ KURUMSAL VERİTABANI &amp; GÖÇ MERKEZİ · STÜDYO v5.0</span>
            <span class="zs-sec-badge ${sqlActive ? 'ok' : 'warn'}">${sqlActive ? `✓ CANLI SQL AKTİF (${esc(db.active_engine_label || 'PDO MySQL')})` : '⚡ JSON MODU — MYSQL GÖÇÜNE HAZIR'}</span>
          </div>
          <h2 style="margin:6px 0 4px">PDO MySQL / MariaDB Göç Sihirbazı &amp; .SQL Yedekleme</h2>
          <p class="zs-card-sub" style="margin:0">Binlerce ayrı <code>.json</code> dosyası yerine Plesk / cPanel MySQL (<code>InnoDB · utf8mb4_unicode_ci</code>) tablolarına tek tıkla geçin, eski JSON yığınını güvenle silin ve tüm veritabanını tek <code>.sql</code> dosyasıyla yedekleyin.</p>
        </div>
        <div class="zs-btn-group">
          <button type="button" class="zs-btn zs-btn-sm" id="dbRefreshStatusBtn">↻ Durumu Yenile</button>
          <a href="${esc(sqlDownloadUrl)}" class="zs-btn zs-btn-sm zs-btn-primary">📥 Tam .SQL Yedeği İndir</a>
        </div>
      </div>

      ${db.connection_error ? `
        <div style="padding:12px 16px;margin-bottom:16px;border-radius:13px;background:rgba(239,68,68,.15);border:1px solid rgba(248,113,113,.48);color:#fee2e2;font-size:13px">
          <strong>⚠ Veritabanı Bağlantı Uyarısı:</strong> ${esc(db.connection_error)} (Sistem kesintisiz çalışması için otomatik koruma modundadır; lütfen aşağıdaki bağlantı bilgilerini kontrol edin.)
        </div>
      ` : ''}

      <div id="dbLiveFeedbackBanner" style="display:none;margin-bottom:16px"></div>

      <div class="zs-kpi-grid" style="margin-bottom:18px">
        <div class="zs-kpi ${sqlActive ? 'zs-kpi-emerald' : 'zs-kpi-sky'}">
          <div class="zs-kpi-top"><span>AKTİF VERİ MOTORU</span><span class="zs-kpi-icon">🗄️</span></div>
          <strong style="font-size:21px">${sqlActive ? (db.active_engine === 'mysql' ? 'PDO MySQL' : 'PDO SQLite') : 'Flat-File JSON'}</strong>
          <small>${sqlActive ? esc(db.server_version || db.active_engine_label || 'SQL Motoru Aktif') : 'Plesk / cPanel MySQL geçişine hazır'}</small>
        </div>
        <div class="zs-kpi zs-kpi-mint">
          <div class="zs-kpi-top"><span>SQL TABLO &amp; MAKALE KAYDI</span><span class="zs-kpi-icon">📊</span></div>
          <strong>${postsInSql.toLocaleString('tr-TR')} <span style="font-size:14px;font-weight:700;color:var(--text-muted)">Makale</span></strong>
          <small>4 SQL Tablosu · Toplam ${totalSqlRows.toLocaleString('tr-TR')} satır${db.total_sql_mb ? ` · ${db.total_sql_mb} MB` : ''}</small>
        </div>
        <div class="zs-kpi ${totalJsonCnt === 0 ? 'zs-kpi-emerald' : 'zs-kpi-violet'}">
          <div class="zs-kpi-top"><span>DİSKTEKİ ESKİ JSON DOSYASI</span><span class="zs-kpi-icon">📁</span></div>
          <strong style="color:${totalJsonCnt === 0 ? '#6ee7b7' : '#ffffff'}">${totalJsonCnt.toLocaleString('tr-TR')} <span style="font-size:14px;font-weight:700;color:var(--text-muted)">JSON</span></strong>
          <small>${totalJsonCnt === 0 ? '✓ Disk tertemiz — 0 eski JSON dosyası' : `${postsJsonCnt.toLocaleString('tr-TR')} yazı JSON + sistem · ${totalJsonMb} MB`}</small>
        </div>
        <div class="zs-kpi zs-kpi-sky">
          <div class="zs-kpi-top"><span>YEDEKLEME &amp; KARAKTER SETİ</span><span class="zs-kpi-icon">💾</span></div>
          <strong style="font-size:20px;color:#67e8f9">Tek .SQL Dökümü</strong>
          <small>utf8mb4_unicode_ci · InnoDB İndeksli</small>
        </div>
      </div>

      <div class="zs-grid-2" style="align-items:start">
        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <span class="zs-badge-pill">ADIM ADIM TEK TIKLA KURULUM</span>
              <h3 style="margin-top:6px">🔌 Plesk / cPanel MySQL Bağlantısı &amp; 3 Adımlı Göç</h3>
              <p class="zs-card-sub">Plesk veya cPanel’de oluşturduğunuz boş veritabanı bilgilerini girin; tablolar otomatik kurulur ve tüm içerik saniyeler içinde aktarılır.</p>
            </div>
          </div>

          <div class="zs-db-driver-grid" role="tablist" aria-label="Veritabanı Sürücüsü Seçimi">
            <button type="button" class="zs-db-driver-btn ${selectedDriver === 'mysql' ? 'is-active' : ''}" data-db-driver="mysql">
              <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:8px">
                <strong>🐬 MySQL / MariaDB (Önerilen)</strong>
                <span class="zs-sec-badge ${db.pdo_mysql_loaded ? 'ok' : 'warn'}">${db.pdo_mysql_loaded ? 'PDO Hazır' : 'Eklenti Yok'}</span>
              </div>
              <small>Plesk &amp; cPanel sunucular için kurumsal InnoDB utf8mb4 veritabanı motoru.</small>
            </button>
            <button type="button" class="zs-db-driver-btn ${selectedDriver === 'sqlite' ? 'is-active' : ''}" data-db-driver="sqlite">
              <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:8px">
                <strong>⚡ Tek Dosyalı SQLite (WAL SQL)</strong>
                <span class="zs-sec-badge ${db.pdo_sqlite_loaded ? 'ok' : 'warn'}">${db.pdo_sqlite_loaded ? 'PDO Hazır' : 'Pasif'}</span>
              </div>
              <small>Şifre/sunucu ayarı gerektirmeden tüm JSON’ları tek <code>.sqlite</code> SQL dosyasında toplar.</small>
            </button>
          </div>

          <div id="dbMysqlFieldsWrap" style="display:${selectedDriver === 'mysql' ? 'block' : 'none'}">
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>MySQL Sunucu Adresi (Host)</label>
                <input id="dbHostInp" class="zs-input" placeholder="localhost veya 127.0.0.1" value="${esc(cfg.host || 'localhost')}"/>
              </div>
              <div class="zs-field">
                <label>Port &amp; Tablo Öneki</label>
                <div style="display:flex;gap:8px">
                  <input id="dbPortInp" type="number" class="zs-input" style="max-width:110px" placeholder="3306" value="${Number(cfg.port || 3306)}"/>
                  <input id="dbPrefixInp" class="zs-input" placeholder="zn_" value="${esc(prefixVal)}" title="Tablo öneki (örn: zn_)"/>
                </div>
              </div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Veritabanı Adı (Database Name) *</label>
                <input id="dbNameInp" class="zs-input" placeholder="Örn: zeynart_db" value="${esc(cfg.database || '')}"/>
              </div>
              <div class="zs-field">
                <label>Veritabanı Kullanıcı Adı *</label>
                <input id="dbUserInp" class="zs-input" placeholder="Örn: zeynart_user" value="${esc(cfg.username || '')}" autocomplete="off"/>
              </div>
            </div>
            <div class="zs-field">
              <label><span>Veritabanı Parolası ${cfg.has_password ? '— Kayıtlı parola korumalı (değiştirmek istemiyorsanız dokunmayın)' : '*'}</span></label>
              <div class="zs-input-group">
                <input id="dbPassInp" type="password" class="zs-input" placeholder="${cfg.has_password ? '••••••••••••' : 'Plesk / cPanel veritabanı parolası'}" value="${cfg.has_password ? '••••••••••••' : ''}" autocomplete="new-password"/>
                <button type="button" class="zs-btn zs-btn-sm" id="dbTogglePassBtn">Göster</button>
              </div>
            </div>
            <label class="zs-switch" style="margin-bottom:6px">
              <input type="checkbox" id="dbAutoCreateChk" checked/>
              <span>Yetki varsa veritabanını (<code>CREATE DATABASE IF NOT EXISTS</code>) ve 4 tabloyu otomatik oluştur</span>
            </label>
          </div>

          <div id="dbSqliteFieldsWrap" style="display:${selectedDriver === 'sqlite' ? 'block' : 'none'}">
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>SQLite Veritabanı Dosya Adı (data/ altında korunur)</label>
                <input id="dbSqliteFileInp" class="zs-input" value="${esc(cfg.sqlite_file || 'zeynart_database.sqlite')}"/>
              </div>
              <div class="zs-field">
                <label>Tablo Öneki</label>
                <input id="dbSqlitePrefixInp" class="zs-input" value="${esc(prefixVal)}"/>
              </div>
            </div>
          </div>

          <div class="zs-db-pipeline">
            <div class="zs-db-step ${sqlActive ? 'is-done' : 'is-ready'}">
              <div class="zs-db-step-info">
                <strong>1. Bağlantıyı Test Et &amp; 4 Kurumsal Tabloyu Kur</strong>
                <small>Sunucu erişimini doğrular ve <code>${esc(prefixVal)}posts</code>, <code>${esc(prefixVal)}store_kv</code>, <code>${esc(prefixVal)}analytics_daily</code>, <code>${esc(prefixVal)}revisions</code> şemasını hazırlar.</small>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="dbTestConnBtn">🔌 Bağlantıyı Test Et &amp; Kur</button>
            </div>

            <div class="zs-db-step ${sqlActive && db.store_in_sql ? 'is-done' : 'is-ready'}">
              <div class="zs-db-step-info">
                <strong>2. Tüm Sistemi SQL Tablolarına Aktar &amp; Aktif Et</strong>
                <small>${totalJsonCnt > 0 ? `Diskteki <strong>${postsJsonCnt.toLocaleString('tr-TR')} blog makalesini</strong>, tüm sayfaları, CRM taleplerini, analitik ve Zaman Makinesi kayıtlarını tek işlemde SQL’e taşır.` : 'Tüm veriler zaten SQL tablolarında aktif olarak çalışıyor; dilediğiniz an yeniden senkronize edebilirsiniz.'}</small>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="dbMigrateAllBtn">🚀 Tüm Sistemi SQL’e Aktar</button>
            </div>

            <div class="zs-db-step ${sqlActive && totalJsonCnt === 0 ? 'is-done' : (canCleanJson ? 'is-ready' : '')}">
              <div class="zs-db-step-info">
                <strong>3. Eski JSON Dosyalarını Güvenle Sil (${totalJsonCnt.toLocaleString('tr-TR')} JSON → 0 JSON)</strong>
                <small>${sqlActive && totalJsonCnt === 0
                  ? '✓ Diskteki tüm eski <code>data/posts/*.json</code> ve sistem JSON yığını temizlendi! Yedekler artık yalnızca tek <code>.sql</code> içerir.'
                  : 'SQL aktarımı doğrulandıktan sonra diskteki 1.681+ <code>.json</code> dosyasını temizler (öncesinde Yedekleme Kasasına otomatik güvenlik kopyası alır).'}</small>
                ${canCleanJson ? `
                  <label class="zs-switch" style="margin-top:8px;font-size:12px">
                    <input type="checkbox" id="dbArchiveBeforeCleanChk" checked/>
                    <span>Silmeden önce <code>data/backups/</code> altına tek <code>.sql</code> içeren güvenlik ZIP yedeği al</span>
                  </label>
                ` : ''}
              </div>
              <button type="button" class="zs-btn zs-btn-sm ${canCleanJson ? 'zs-btn-danger' : ''}" id="dbCleanupJsonBtn" ${canCleanJson ? '' : 'disabled style="opacity:.55;cursor:not-allowed"'}>
                ${sqlActive && totalJsonCnt === 0 ? '✓ 0 Eski JSON Kaldı' : `🧹 Eski JSON’ları Sil (${totalJsonCnt})`}
              </button>
            </div>
          </div>
        </div>

        <div style="display:grid;gap:16px">
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">CANLI ŞEMA DENETÇİSİ</span>
                <h3 style="margin-top:6px">📊 SQL Veritabanı Tabloları (4 Tablo)</h3>
                <p class="zs-card-sub">Binlerce dağınık dosya yerine tüm sisteminiz indekslenmiş 4 kurumsal SQL tablosunda tutulur.</p>
              </div>
              <span class="zs-pill ${sqlActive ? 'zs-pill-pub' : 'zs-pill-cat'}">${sqlActive ? 'CANLI BAĞLI' : 'BEKLİYOR'}</span>
            </div>

            <div style="display:grid;gap:9px">
              ${tablesList.map(tb => `
                <div class="zs-db-table-card">
                  <div style="min-width:0">
                    <code>${esc(tb.table)}</code>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px">${esc(tb.label || '')}</div>
                  </div>
                  <div style="text-align:right;flex-shrink:0">
                    <strong style="display:block;font-size:14px;color:#fff">${Number(tb.rows || 0).toLocaleString('tr-TR')} satır</strong>
                    <span style="font-size:11px;color:#5eead4">${tb.size_kb ? `${tb.size_kb} KB` : (sqlActive ? 'InnoDB / UTF8MB4' : 'Hazır Şema')}</span>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">TAŞINABİLİR VERİTABANI KASASI</span>
                <h3 style="margin-top:6px">💾 Tek Dosyalı .SQL Yedek İndir &amp; İçe Aktar</h3>
                <p class="zs-card-sub">Plesk, cPanel ve tüm SQL yönetim araçlarıyla %100 uyumlu standart <code>.sql</code> veritabanı yedeği alın veya geri yükleyin.</p>
              </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
              <a href="${esc(sqlDownloadUrl)}" class="zs-btn zs-btn-primary">📥 Tam MySQL Yedeği İndir (.SQL)</a>
              <button type="button" class="zs-btn zs-btn-ai" id="dbImportSqlBtn">📤 Bilgisayardan .SQL Yedeği Yükle</button>
              <input type="file" id="dbImportSqlInput" accept=".sql,application/sql,text/plain" style="display:none"/>
              <button type="button" class="zs-btn" id="dbJumpVaultBackupsBtn">☁️ ZIP Yedek Kasası →</button>
            </div>

            <div style="padding:12px 14px;border-radius:12px;background:rgba(20,184,166,.07);border:1px solid rgba(94,234,212,.22);font-size:12.5px;color:var(--text-muted);line-height:1.5">
              <strong style="color:#5eead4;display:block;margin-bottom:3px">✓ ZIP Yedekleme Kasası Artık Şişmez!</strong>
              SQL motoru aktifken <strong>Tam Yedekleme Kasası (.zip)</strong> arşiv içinde 1.684 adet ayrı <code>.json</code> dosyası tutmaz; tüm veritabanını tek bir <code>database/zeynart-veritabani-yedegi.sql</code> dosyası olarak paketler.
            </div>

            ${sqlActive ? `
              <details style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-subtle)">
                <summary style="cursor:pointer;font-size:12px;color:var(--text-muted);font-weight:600">🔄 Gelişmiş / Acil Durum: SQL Veritabanını Düz JSON Dosyalarına Geri Çıkar</summary>
                <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:12px;color:var(--text-muted)">
                  <span>Gerekirse SQL’deki tüm yazıları ve ayarları <code>data/</code> klasörüne <code>.json</code> olarak geri yazıp JSON moduna dönebilirsiniz.</span>
                  <button type="button" class="zs-btn zs-btn-sm" id="dbExportBackToJsonBtn">JSON Moduna Geri Dön</button>
                </div>
              </details>
            ` : ''}
          </div>
        </div>
      </div>
    `;

    const collectFormPayload = () => {
      if (selectedDriver === 'sqlite') {
        return {
          driver: 'sqlite',
          sqlite_file: (document.getElementById('dbSqliteFileInp')?.value || 'zeynart_database.sqlite').trim(),
          prefix: (document.getElementById('dbSqlitePrefixInp')?.value || 'zn_').trim()
        };
      }
      return {
        driver: 'mysql',
        host: (document.getElementById('dbHostInp')?.value || 'localhost').trim(),
        port: Number(document.getElementById('dbPortInp')?.value || 3306),
        prefix: (document.getElementById('dbPrefixInp')?.value || 'zn_').trim(),
        database: (document.getElementById('dbNameInp')?.value || '').trim(),
        username: (document.getElementById('dbUserInp')?.value || '').trim(),
        password: document.getElementById('dbPassInp')?.value || '',
        auto_create_db: Boolean(document.getElementById('dbAutoCreateChk')?.checked)
      };
    };

    const showBanner = (html, isErr) => {
      const el = document.getElementById('dbLiveFeedbackBanner');
      if (!el) return;
      el.style.display = 'block';
      el.innerHTML = `<div style="padding:13px 16px;border-radius:13px;background:${isErr ? 'rgba(239,68,68,.15)' : 'rgba(16,185,129,.15)'};border:1px solid ${isErr ? 'rgba(248,113,113,.48)' : 'rgba(52,211,153,.48)'};color:${isErr ? '#fee2e2' : '#d1fae5'};font-size:13px;line-height:1.48">${html}</div>`;
    };

    host.querySelectorAll('[data-db-driver]').forEach(btn => {
      btn.onclick = () => {
        S.dbDraftDriver = btn.getAttribute('data-db-driver') || 'mysql';
        mountDatabaseStudioTab(host);
      };
    });

    const passInp = document.getElementById('dbPassInp');
    const togglePassBtn = document.getElementById('dbTogglePassBtn');
    if (passInp && togglePassBtn) {
      togglePassBtn.onclick = () => {
        const isPw = passInp.type === 'password';
        passInp.type = isPw ? 'text' : 'password';
        togglePassBtn.textContent = isPw ? 'Gizle' : 'Göster';
      };
    }

    document.getElementById('dbRefreshStatusBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      this.textContent = '⏳ Yenileniyor...';
      try {
        const res = await api('database_status_get', {});
        if (res.database_status) S.databaseStatus = res.database_status;
        if (typeof window.__ZS_UPDATE_TELEMETRY__ === 'function') window.__ZS_UPDATE_TELEMETRY__();
        mountDatabaseStudioTab(host);
        toast('Veritabanı ve disk envanteri güncellendi.');
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
        this.textContent = '↻ Durumu Yenile';
      }
    });

    document.getElementById('dbJumpVaultBackupsBtn')?.addEventListener('click', () => {
      S.tabs.settings = 'backups';
      renderSettingsView();
    });

    document.getElementById('dbTestConnBtn')?.addEventListener('click', async function () {
      const payload = collectFormPayload();
      if (payload.driver === 'mysql' && (!payload.database || !payload.username)) {
        toast('Lütfen Plesk / cPanel Veritabanı Adı ve Kullanıcı Adı alanlarını girin.', true);
        return;
      }
      this.disabled = true;
      const origTxt = this.textContent;
      this.textContent = '⏳ Bağlanılıyor...';
      showBanner('⏳ SQL sunucusuna bağlanılıyor ve 4 kurumsal tablo şeması doğrulanıyor...', false);
      try {
        const res = await api('database_test_connection', payload);
        if (res.database_status) S.databaseStatus = res.database_status;
        toast(res.message);
        mountDatabaseStudioTab(host);
        showBanner(`<strong>${esc(res.message)}</strong><br/>Şimdi <strong>2. Adım: Tüm Sistemi SQL’e Aktar</strong> butonuna tıklayarak verilerinizi taşıyabilirsiniz.`, false);
      } catch (err) {
        toast(err.message, true);
        showBanner(`<strong>⚠ Bağlantı Hatası:</strong> ${esc(err.message)}`, true);
        this.disabled = false;
        this.textContent = origTxt;
      }
    });

    document.getElementById('dbMigrateAllBtn')?.addEventListener('click', async function () {
      const payload = collectFormPayload();
      if (payload.driver === 'mysql' && (!payload.database || !payload.username)) {
        toast('Lütfen önce Veritabanı Adı ve Kullanıcı Adı bilgilerini girin.', true);
        return;
      }
      this.disabled = true;
      const origTxt = this.textContent;
      this.textContent = '⏳ SQL’e Aktarılıyor...';
      showBanner('🚀 Blog makaleleri, sayfa içerikleri, CRM kayıtları, analitik verileri ve Zaman Makinesi sürümleri SQL tablolarına aktarılıyor...', false);
      try {
        const res = await api('database_migrate_all', payload);
        if (res.database_status) S.databaseStatus = res.database_status;
        if (res.turbo_engine) S.turboEngine = res.turbo_engine;
        if (typeof window.__ZS_UPDATE_TELEMETRY__ === 'function') window.__ZS_UPDATE_TELEMETRY__();
        toast(res.message);
        mountDatabaseStudioTab(host);
        showBanner(`<strong>${esc(res.message)}</strong>${S.databaseStatus?.can_clean_legacy_json ? '<br/>✓ Doğrulama tamamlandı! Artık <strong>3. Adım: Eski JSON’ları Sil</strong> butonuyla diskteki binlerce eski JSON dosyasını güvenle temizleyebilirsiniz.' : ''}`, false);
      } catch (err) {
        toast(err.message, true);
        showBanner(`<strong>⚠ Göç Hatası:</strong> ${esc(err.message)}`, true);
        this.disabled = false;
        this.textContent = origTxt;
      }
    });

    document.getElementById('dbCleanupJsonBtn')?.addEventListener('click', async function () {
      if (!canCleanJson) return;
      if (!confirm(`Tüm verilerinizin SQL veritabanında aktif olduğu doğrulandı.\n\nDiskteki ${totalJsonCnt.toLocaleString('tr-TR')} adet eski .json dosyasını (${totalJsonMb} MB) şimdi güvenle silmek istiyor musunuz?`)) {
        return;
      }
      const archiveFirst = Boolean(document.getElementById('dbArchiveBeforeCleanChk')?.checked);
      this.disabled = true;
      this.textContent = '⏳ JSON’lar Temizleniyor...';
      showBanner('🧹 Önce güvenlik yedeği alınıyor ve ardından diskteki eski JSON dosyaları temizleniyor...', false);
      try {
        const res = await api('database_cleanup_legacy_json', { archive_first: archiveFirst ? 1 : 0 });
        if (res.database_status) S.databaseStatus = res.database_status;
        if (res.vault_backups) S.vaultBackups = res.vault_backups;
        if (res.unused_files_summary) S.unusedFilesSummary = res.unused_files_summary;
        if (typeof window.__ZS_UPDATE_TELEMETRY__ === 'function') window.__ZS_UPDATE_TELEMETRY__();
        toast(res.message);
        mountDatabaseStudioTab(host);
        showBanner(`<strong>${esc(res.message)}</strong>${res.archive_filename ? `<br/>Güvenlik Yedeği: <code>data/backups/${esc(res.archive_filename)}</code>` : ''}`, false);
      } catch (err) {
        toast(err.message, true);
        showBanner(`<strong>⚠ Temizleme Hatası:</strong> ${esc(err.message)}`, true);
        this.disabled = false;
        this.textContent = `🧹 Eski JSON’ları Sil (${totalJsonCnt})`;
      }
    });

    const sqlFileInp = document.getElementById('dbImportSqlInput');
    document.getElementById('dbImportSqlBtn')?.addEventListener('click', () => sqlFileInp?.click());
    if (sqlFileInp) {
      sqlFileInp.onchange = async () => {
        const file = sqlFileInp.files && sqlFileInp.files[0];
        sqlFileInp.value = '';
        if (!file) return;
        if (!confirm(`"${file.name}" dosyasındaki SQL veritabanı yedeğini mevcut veritabanına içe aktarmak istediğinize emin misiniz?`)) {
          return;
        }
        showBanner(`⏳ <strong>${esc(file.name)}</strong> okunuyor ve SQL tablolarına aktarılıyor...`, false);
        try {
          const fd = new FormData();
          fd.append('action', 'database_import_sql');
          fd.append('csrf_token', CFG.csrfToken);
          fd.append('sql_file', file);
          const resp = await fetch(CFG.apiUrl, {
            method: 'POST',
            headers: { 'X-CSRF-Token': CFG.csrfToken },
            body: fd
          });
          const res = await resp.json();
          if (!res || !res.ok) {
            throw new Error(res?.error || '.SQL içe aktarımı başarısız oldu.');
          }
          if (res.database_status) S.databaseStatus = res.database_status;
          if (res.store) S.store = res.store;
          if (res.summary) S.summary = res.summary;
          if (typeof window.__ZS_UPDATE_TELEMETRY__ === 'function') window.__ZS_UPDATE_TELEMETRY__();
          toast(res.message);
          mountDatabaseStudioTab(host);
          showBanner(`<strong>${esc(res.message)}</strong>`, false);
        } catch (err) {
          toast(err.message, true);
          showBanner(`<strong>⚠ .SQL İçe Aktarım Hatası:</strong> ${esc(err.message)}`, true);
        }
      };
    }

    document.getElementById('dbExportBackToJsonBtn')?.addEventListener('click', async function () {
      if (!confirm('SQL veritabanındaki tüm makale ve ayarları data/ klasörüne düz .json dosyaları olarak geri çıkarıp JSON moduna dönmek istiyor musunuz?')) {
        return;
      }
      this.disabled = true;
      this.textContent = '⏳ JSON Dosyalarına Çıkarılıyor...';
      try {
        const res = await api('database_export_to_json', {});
        if (res.database_status) S.databaseStatus = res.database_status;
        if (typeof window.__ZS_UPDATE_TELEMETRY__ === 'function') window.__ZS_UPDATE_TELEMETRY__();
        toast(res.message);
        mountDatabaseStudioTab(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
        this.textContent = 'JSON Moduna Geri Dön';
      }
    });
  }

  function renderSettingsView() {
    syncAdminUrl('replace');
    if (!isSuperAdmin()) {
      const cu = S.currentUser || S.store.current_user || { username: 'editor', display_name: 'Editör', role_label: 'Müşteri / Editör' };
      ROOT.innerHTML = `
        <div class="zs-grid-2" style="align-items:start">
          <div class="zs-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px">
              <h3 style="margin:0">🔑 Hesabım &amp; Parola Güncelleme</h3>
              <span class="zs-user-role-pill editor">${esc(cu.role_label || 'Müşteri / Editör')}</span>
            </div>
            <div class="zs-field"><label>Görünen İsim / Ünvan</label><input id="pwDisplay" class="zs-input" value="${esc(cu.display_name || '')}"/></div>
            <div class="zs-field"><label>Kullanıcı Adınız (Sabit)</label><input id="pwUser" class="zs-input" value="${esc(cu.username || '')}" readonly style="opacity:.75"/></div>
            <div class="zs-field"><label>Mevcut Parolanız *</label><input type="password" id="pwCurrent" class="zs-input" placeholder="Doğrulama için mevcut parolanız"/></div>
            <div class="zs-field"><label>Yeni Parola (En az 8 karakter — değiştirmek istemiyorsanız boş bırakın)</label><input type="password" id="pwNew" class="zs-input" placeholder="Yeni güçlü parola"/></div>
            <button type="button" class="zs-btn zs-btn-primary" id="pwUpdateBtn">✓ Hesap Bilgilerimi Güncelle</button>
          </div>

          <div class="zs-card">
            <span class="zs-badge-pill">GÜVENLİ İÇERİK YÖNETİM YETKİSİ</span>
            <h3 style="margin:8px 0 8px">👥 Müşteri / Editör Rol Kapsamınız</h3>
            <p class="zs-card-sub" style="margin-bottom:14px">Bu hesap teknik sunucu ayarlarını bozma riski olmadan sitenizdeki tüm ticari içerikleri güvenle yönetebilmeniz için yapılandırılmıştır.</p>
            <div style="display:grid;gap:8px;font-size:13px">
              <div style="padding:10px 12px;border-radius:10px;background:rgba(16,185,129,.1);border:1px solid rgba(52,211,153,.28);color:#d1fae5">✓ <strong>Tam Yetki:</strong> Sayfalar, Canlı Görsel Düzenleyici, Müşteri Logoları, Görsel Kanvas &amp; Medya Arşivi, Blog &amp; Yapay Zeka Yazar, Teklif Sihirbazı, Pop-up, SEO Doktoru ve Proje Talepleri.</div>
              <div style="padding:10px 12px;border-radius:10px;background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.2);color:var(--text-muted)">🔒 <strong>Ajans Teknik Koruması:</strong> Güvenlik duvarı, SMTP e-posta sunucusu, ham kod blokları ve sistem yedekleri ajans süper yöneticisi tarafından korunmaktadır.</div>
            </div>
          </div>
        </div>
      `;
      document.getElementById('pwUpdateBtn')?.addEventListener('click', async () => {
        try {
          const res = await api('password_change', {
            display_name: document.getElementById('pwDisplay').value.trim(),
            username: document.getElementById('pwUser').value.trim(),
            current_password: document.getElementById('pwCurrent').value,
            new_password: document.getElementById('pwNew').value
          });
          if (res.current_user) {
            S.currentUser = res.current_user;
            S.store.current_user = res.current_user;
            syncUserRoleUi();
          }
          document.getElementById('pwCurrent').value = '';
          document.getElementById('pwNew').value = '';
          toast(res.message);
        } catch (err) {
          toast(err.message, true);
        }
      });
      return;
    }

    const site = S.store.site || {};
    const seo = S.store.seo || { social_profiles: [] };
    const smtp = S.store.smtp || {};
    const i18n = S.store.i18n || { enabled: true, auto_translate_blog: true, provider: 'auto', deepl_api_key: '', openai_api_key: '', openai_model: 'gpt-4o-mini' };
    const adm = S.store.admin || {};
    const tab = S.tabs.settings || 'identity';

    ROOT.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        <div class="zs-tabs" style="margin-bottom:0">
          <button type="button" class="zs-tab ${tab === 'database' ? 'active' : ''}" data-settab="database">🗄️ MySQL Veritabanı &amp; Göç</button>
          <button type="button" class="zs-tab ${tab === 'identity' ? 'active' : ''}" data-settab="identity">🏢 Marka &amp; Kimlik</button>
          <button type="button" class="zs-tab ${tab === 'security' ? 'active' : ''}" data-settab="security">🛡️ 20/20 Güvenlik &amp; 2FA</button>
          <button type="button" class="zs-tab ${tab === 'users' ? 'active' : ''}" data-settab="users">👥 Kullanıcı &amp; Roller</button>
          <button type="button" class="zs-tab ${tab === 'i18n' ? 'active' : ''}" data-settab="i18n">🌍 Çoklu Dil</button>
          <button type="button" class="zs-tab ${tab === 'smtp' ? 'active' : ''}" data-settab="smtp">📧 SMTP E-Posta</button>
          <button type="button" class="zs-tab ${tab === 'revisions' ? 'active' : ''}" data-settab="revisions">🕒 Zaman Makinesi</button>
          <button type="button" class="zs-tab ${tab === 'backups' ? 'active' : ''}" data-settab="backups">☁️ ZIP Yedek Kasası</button>
          <button type="button" class="zs-tab ${tab === 'performance' ? 'active' : ''}" data-settab="performance">⚡ LiteSpeed &amp; Sağlık</button>
        </div>
        ${(tab === 'identity' || tab === 'i18n' || tab === 'smtp') ? `<button type="button" class="zs-btn zs-btn-primary" id="saveSettingsBtn">✓ Ayarları Kaydet</button>` : ''}
      </div>

      ${tab === 'database' ? `<div id="setDatabaseHost"></div>` : ''}

      ${tab === 'identity' ? `
        <div class="zs-grid-2">
          <div class="zs-card">
            <h3 style="margin:0 0 14px">Kurumsal Kimlik & İletişim Bilgileri</h3>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Kısa Marka Adı</label><input id="stName" class="zs-input" value="${esc(site.name || '')}"/></div>
              <div class="zs-field"><label>Resmi Ünvan</label><input id="stLegal" class="zs-input" value="${esc(site.legal_name || '')}"/></div>
            </div>
            <div class="zs-field"><label>Marka Sloganı</label><input id="stTagline" class="zs-input" value="${esc(site.tagline || '')}"/></div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>Telefon (Görünür)</label><input id="stPhone" class="zs-input" value="${esc(site.phone || '')}"/></div>
              <div class="zs-field"><label>Telefon Bağlantısı (tel:)</label><input id="stPhoneHref" class="zs-input" value="${esc(site.phone_href || '')}"/></div>
            </div>
            <div class="zs-grid-2">
              <div class="zs-field"><label>E-posta Adresi</label><input id="stEmail" class="zs-input" value="${esc(site.email || '')}"/></div>
              <div class="zs-field"><label>Şehir & Kuruluş Yılı</label><div style="display:flex;gap:6px"><input id="stCity" class="zs-input" value="${esc(site.city || '')}"/><input id="stYear" class="zs-input" style="max-width:95px" value="${esc(site.founded_year || '')}"/></div></div>
            </div>
            <div class="zs-field"><label>Açık Adres</label><input id="stAddr" class="zs-input" value="${esc(site.address || '')}"/></div>
            <div class="zs-field" style="margin-bottom:0;padding-top:12px;border-top:1px solid var(--border-subtle)">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                <label style="margin:0">Sosyal Medya Hesapları (Her satıra 1 URL — Sosyal Medya Merkezi ile senkronize)</label>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="stJumpSocialCenterBtn">📱 Sosyal Medya Merkezi'nde Yönet →</button>
              </div>
              <textarea id="stSocials" class="zs-textarea" style="min-height:84px" placeholder="https://instagram.com/zeynartweb&#10;https://facebook.com/zeynartweb&#10;https://linkedin.com/company/...">${esc((seo.social_profiles || []).join('\n'))}</textarea>
              <div id="stSocialBadges" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">
                ${(seo.social_profiles || []).filter(Boolean).map(u => `<span class="zs-pill zs-pill-cat">✓ ${esc(detectSocialBadgeLabel(u))}</span>`).join('') || '<span style="font-size:11.5px;color:var(--text-dim)">Henüz sosyal medya hesabı eklenmedi.</span>'}
              </div>
              <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:10px;padding:10px 12px;border-radius:10px;background:rgba(20,184,166,.06);border:1px solid rgba(94,234,212,.2)">
                <label class="zs-switch" style="margin:0;font-size:12.5px"><input type="checkbox" id="stShowFooterSoc" ${site.show_footer_socials !== false ? 'checked' : ''}/><span>Footer'da İkon Göster</span></label>
                <label class="zs-switch" style="margin:0;font-size:12.5px"><input type="checkbox" id="stShowHeaderSoc" ${site.show_header_socials !== false ? 'checked' : ''}/><span>Header &amp; Mobil Menüde Göster</span></label>
                <label class="zs-switch" style="margin:0;font-size:12.5px"><input type="checkbox" id="stShowContactSoc" ${site.show_contact_socials !== false ? 'checked' : ''}/><span>İletişim Sayfasında Göster</span></label>
              </div>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">Logo, Favicon, Yazı Tipi (Google Fonts) & Deneyim</h3>
            <div class="zs-field"><label>Site Logosu</label><div class="zs-input-group"><input id="stLogo" class="zs-input" value="${esc(site.logo || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-stpick="stLogo">Seç</button></div></div>
            <div class="zs-field"><label>Tarayıcı Sekme İkonu (Favicon)</label><div class="zs-input-group"><input id="stFav" class="zs-input" value="${esc(site.favicon || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-stpick="stFav">Seç</button></div></div>
            <div class="zs-field"><label>Varsayılan Paylaşım Görseli (OpenGraph 1200x630)</label><div class="zs-input-group"><input id="stOg" class="zs-input" value="${esc(site.og_image || '')}"/><button type="button" class="zs-btn zs-btn-sm" data-stpick="stOg">Seç</button></div></div>

            <div style="padding:12px 14px;border-radius:12px;background:rgba(20,184,166,.06);border:1px solid rgba(94,234,212,.22);margin-top:14px">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                <strong style="font-size:13px;color:#5eead4">🔤 Site Yazı Tipi (Google Fonts)</strong>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="stGoFontStudioBtn">Canlı Font &amp; Boyut Stüdyosu →</button>
              </div>
              <div class="zs-grid-2">
                <div class="zs-field" style="margin-bottom:0">
                  <label>Ana Gövde Yazı Tipi</label>
                  <select id="stBodyFont" class="zs-select">
                    ${renderFontSelectOptions(S.store.menus?.typography?.body_font || 'Manrope', false)}
                  </select>
                  <div id="stCustomBodyWrap" style="margin-top:8px;display:${(S.store.menus?.typography?.body_font === 'custom') ? 'block' : 'none'}">
                    <input id="stCustomBodyFont" class="zs-input" placeholder="Google Font adı veya <link> (örn: Raleway)" value="${esc(S.store.menus?.typography?.custom_body_font || '')}"/>
                  </div>
                </div>
                <div class="zs-field" style="margin-bottom:0">
                  <label>Başlık Yazı Tipi (H1–H4)</label>
                  <select id="stHeadingFont" class="zs-select">
                    ${renderFontSelectOptions(S.store.menus?.typography?.heading_font || 'same', true)}
                  </select>
                  <div id="stCustomHeadWrap" style="margin-top:8px;display:${(S.store.menus?.typography?.heading_font === 'custom') ? 'block' : 'none'}">
                    <input id="stCustomHeadFont" class="zs-input" placeholder="Başlık Google Font adı veya <link> (örn: Cinzel)" value="${esc(S.store.menus?.typography?.custom_heading_font || '')}"/>
                  </div>
                </div>
              </div>
            </div>

            <div style="display:grid;gap:12px;margin-top:14px">
              <div style="padding:12px 14px;border-radius:12px;background:rgba(20,184,166,.06);border:1px solid rgba(94,234,212,.22);display:grid;gap:10px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                  <label class="zs-switch" style="margin:0"><input type="checkbox" id="stIntro" ${site.enable_intro !== false ? 'checked' : ''}/><span><strong>Site açılışta Logo Animasyonu (Preloader) Göster</strong></span></label>
                  <a href="${esc(resolveUrl('/?preloader=1'))}" target="_blank" rel="noopener" class="zs-btn zs-btn-sm zs-btn-ai">✨ Canlı Önizle ↗</a>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                  <label for="stIntroMode" style="font-size:12px;color:var(--text-muted);margin:0">Gösterim Sıklığı:</label>
                  <select id="stIntroMode" class="zs-select" style="max-width:340px;padding:6px 10px;font-size:12.5px">
                    <option value="always" ${(site.intro_mode || 'always') === 'always' ? 'selected' : ''}>Tüm Sayfa Geçişlerinde Göster (Varsayılan)</option>
                    <option value="entry" ${site.intro_mode === 'entry' ? 'selected' : ''}>Yalnızca İlk Girişte &amp; Sayfa Yenilemede Göster</option>
                  </select>
                </div>
              </div>
              <label class="zs-switch"><input type="checkbox" id="stCursor" ${site.enable_custom_cursor !== false ? 'checked' : ''}/><span>Masaüstünde özel etkileşimli imleç halkasını göster</span></label>
              <label class="zs-switch"><input type="checkbox" id="stScroll" ${site.enable_scroll_progress !== false ? 'checked' : ''}/><span>Üst okuma / kaydırma ilerleme çubuğunu göster</span></label>
            </div>
          </div>
        </div>
      ` : ''}

      ${tab === 'i18n' ? `
        <div class="zs-grid-2">
          <div class="zs-card">
            <h3 style="margin:0 0 10px">🌍 Çoklu Dil Sistemi & Otomatik Blog Çevirisi</h3>
            <p class="zs-card-sub" style="margin-bottom:16px">Siteniz Türkçe (varsayılan), İngilizce, Almanca ve Rusça olmak üzere 4 dilde SEO uyumlu adreslerle (<code>hreflang</code> + yerelleştirilmiş URL) yayın yapar.</p>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:16px">
              <a href="${esc(resolveUrl('/'))}" target="_blank" class="zs-btn" style="justify-content:space-between"><span>🇹🇷 Türkçe (Varsayılan)</span><span>/ ↗</span></a>
              <a href="${esc(resolveUrl('/en/'))}" target="_blank" class="zs-btn" style="justify-content:space-between"><span>🇬🇧 English</span><span>/en/ ↗</span></a>
              <a href="${esc(resolveUrl('/de/'))}" target="_blank" class="zs-btn" style="justify-content:space-between"><span>🇩🇪 Deutsch</span><span>/de/ ↗</span></a>
              <a href="${esc(resolveUrl('/ru/'))}" target="_blank" class="zs-btn" style="justify-content:space-between"><span>🇷🇺 Русский</span><span>/ru/ ↗</span></a>
            </div>

            <div style="padding:14px;border-radius:12px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.24);display:grid;gap:12px;margin-bottom:16px">
              <label class="zs-switch" style="margin:0">
                <input type="checkbox" id="i18nAutoBlog" ${i18n.auto_translate_blog !== false ? 'checked' : ''}/>
                <span><strong>Yeni bir blog eklendiğinde veya güncellendiğinde otomatik olarak İngilizce (EN), Almanca (DE) ve Rusça (RU) dillerine çevir ve yayınla</strong></span>
              </label>
            </div>

            <div class="zs-field">
              <label>Otomatik Çeviri Motoru</label>
              <select id="i18nProvider" class="zs-select">
                <option value="auto" ${(i18n.provider || 'auto') === 'auto' ? 'selected' : ''}>Otomatik Akıllı Çeviri (API Anahtarı Gerekmez — Anında Hazır)</option>
                <option value="deepl" ${i18n.provider === 'deepl' ? 'selected' : ''}>DeepL Pro / Free API (Anahtar Girilirse Öncelikli)</option>
                <option value="openai" ${i18n.provider === 'openai' ? 'selected' : ''}>OpenAI GPT Çeviri Motoru (Anahtar Girilirse Öncelikli)</option>
              </select>
            </div>

            <div class="zs-grid-2">
              <div class="zs-field">
                <label>DeepL API Anahtarı (İsteğe Bağlı)</label>
                <input type="password" id="i18nDeeplKey" class="zs-input" placeholder="Boş bırakılabilir (ücretsiz motor aktif)" value="${esc(i18n.deepl_api_key || '')}"/>
              </div>
              <div class="zs-field">
                <label>OpenAI API Anahtarı (İsteğe Bağlı)</label>
                <input type="password" id="i18nOpenaiKey" class="zs-input" placeholder="sk-... (Boş bırakılabilir)" value="${esc(i18n.openai_api_key || '')}"/>
              </div>
            </div>
          </div>

          <div class="zs-card" style="min-width:0">
            <h3 style="margin:0 0 8px">Çeviri Önbelleği & Mevcut Blog Arşivi</h3>
            <p class="zs-card-sub" style="margin-bottom:14px">Menü, hizmet, proje ve kurumsal sayfa metinleri ilk ziyarette çevrilerek yüksek hız için <code>data/i18n_cache/</code> altında önbelleğe alınır; blog arşivinizi aşağıdan canlı ilerleme çubuğuyla beklemeden toplu çevirebilirsiniz.</p>

            <div id="i18nBulkStudioHost"></div>

            <ul class="zs-check-list">
              <li><span class="zs-check-dot ok">✓</span><span><strong>HTML & Görsel Korumalı Çeviri:</strong> Blog içindeki tüm H2/H3 başlıklar, listeler, resimler ve iç bağlantılar bozulmadan çevrilir.</span></li>
              <li><span class="zs-check-dot ok">✓</span><span><strong>Yerelleştirilmiş SEO URL:</strong> İngilizce, Almanca ve Rusça (Kiril → Latin transliterasyon) için temiz SEO slug üretilir.</span></li>
              <li><span class="zs-check-dot ok">✓</span><span><strong>Çok Dilli Sitemap & Hreflang:</strong> Google için <code>hreflang="tr|en|de|ru|x-default"</code> ve çok dilli site haritası aktiftir.</span></li>
            </ul>
          </div>
        </div>
      ` : ''}

      ${tab === 'smtp' ? (() => {
        const lastSt = S.store.smtp_last_status || null;
        const isLocalEnc = smtp.encryption === 'local';
        const defaultTestTo = smtp.notify_email || site.email || smtp.username || smtp.from_email || '';
        return `
        <div class="zs-grid-2" style="align-items:start">
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <h3 style="margin:0">SMTP E-posta Bildirim Yapılandırması</h3>
                <p class="zs-card-sub">İletişim ve Site Analiz formlarından gelen taleplerin e-posta kutunuza anında düşmesini sağlar.</p>
              </div>
              <span class="zs-pill ${smtp.enabled ? 'zs-pill-pub' : 'zs-pill-cat'}">${smtp.enabled ? 'AKTİF' : 'PASİF'}</span>
            </div>

            <label class="zs-switch" style="margin-bottom:14px"><input type="checkbox" id="smEnabled" ${smtp.enabled ? 'checked' : ''}/><span><strong>Yeni proje talebi geldiğinde e-posta bildirimi gönder</strong></span></label>

            <div style="margin-bottom:14px">
              <div style="font-size:11.5px;color:var(--text-muted);margin-bottom:6px">⚡ Hızlı Sunucu Şablonu Seçin (Tek Tıkla Doldurur):</div>
              <div style="display:flex;flex-wrap:wrap;gap:6px">
                <button type="button" class="zs-btn zs-btn-sm" data-smtp-preset="plesk587">Plesk (587 STARTTLS)</button>
                <button type="button" class="zs-btn zs-btn-sm" data-smtp-preset="plesk465">Plesk SSL (465)</button>
                <button type="button" class="zs-btn zs-btn-sm" data-smtp-preset="local">Yerel Sunucu (PHP mail / Postfix)</button>
                <button type="button" class="zs-btn zs-btn-sm" data-smtp-preset="yandex">Yandex 360 (465)</button>
                <button type="button" class="zs-btn zs-btn-sm" data-smtp-preset="gmail">Google Workspace (587)</button>
                <button type="button" class="zs-btn zs-btn-sm" data-smtp-preset="m365">Microsoft 365 (587)</button>
              </div>
            </div>

            <div class="zs-grid-3">
              <div class="zs-field">
                <label>Şifreleme &amp; Yöntem</label>
                <select id="smEnc" class="zs-select">
                  <option value="tls" ${(smtp.encryption || 'tls') === 'tls' ? 'selected' : ''}>STARTTLS (Önerilen · 587)</option>
                  <option value="ssl" ${smtp.encryption === 'ssl' ? 'selected' : ''}>SSL / TLS (Port 465)</option>
                  <option value="none" ${smtp.encryption === 'none' ? 'selected' : ''}>Şifresiz SMTP (Port 25/587)</option>
                  <option value="local" ${isLocalEnc ? 'selected' : ''}>Yerel Sunucu (PHP mail / Postfix)</option>
                </select>
              </div>
              <div class="zs-field sm-remote-field" style="${isLocalEnc ? 'opacity:.45;pointer-events:none' : ''}">
                <label>SMTP Sunucusu (Host)</label>
                <input id="smHost" class="zs-input" placeholder="mail.zeynart.com" value="${esc(smtp.host || '')}"/>
              </div>
              <div class="zs-field sm-remote-field" style="${isLocalEnc ? 'opacity:.45;pointer-events:none' : ''}">
                <label>Port</label>
                <input type="number" id="smPort" class="zs-input" value="${esc(smtp.port || 587)}"/>
              </div>
            </div>

            <div class="zs-grid-2 sm-remote-field" style="${isLocalEnc ? 'opacity:.45;pointer-events:none' : ''}">
              <div class="zs-field">
                <label>SMTP Kullanıcı Adı (Tam E-posta)</label>
                <input id="smUser" class="zs-input" placeholder="info@zeynart.com" value="${esc(smtp.username || '')}"/>
              </div>
              <div class="zs-field">
                <label>SMTP Parolası</label>
                <div style="display:flex;gap:6px">
                  <input type="password" id="smPass" class="zs-input" placeholder="E-posta parolası veya Uygulama Şifresi" value="${esc(smtp.password || '')}"/>
                  <button type="button" class="zs-btn zs-btn-sm" id="smTogglePassBtn" title="Parolayı Göster/Gizle" style="white-space:nowrap">👁 Göster</button>
                </div>
              </div>
            </div>

            <div class="zs-grid-3">
              <div class="zs-field">
                <label>Gönderen E-posta (From)</label>
                <input id="smFromMail" class="zs-input" placeholder="SMTP kullanıcı adıyla aynı olmalı" value="${esc(smtp.from_email || '')}"/>
              </div>
              <div class="zs-field">
                <label>Gönderen Adı</label>
                <input id="smFromName" class="zs-input" placeholder="Zeynart Web Bilişim" value="${esc(smtp.from_name || 'Zeynart Web Bilişim')}"/>
              </div>
              <div class="zs-field">
                <label>Bildirim Alıcı E-posta</label>
                <input id="smNotify" class="zs-input" placeholder="info@zeynart.com (Virgülle çoklu)" value="${esc(smtp.notify_email || '')}"/>
              </div>
            </div>

            <div style="display:grid;gap:8px;padding:12px;border-radius:10px;background:rgba(4,10,14,.68);border:1px solid var(--border-subtle)">
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="smAllowSelfSigned" ${smtp.allow_self_signed !== false ? 'checked' : ''}/><span>Plesk / Kurumsal mail SSL sertifika adı uyuşmazlıklarını otomatik tolere et (Önerilen)</span></label>
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="smFallbackMail" ${smtp.fallback_php_mail !== false ? 'checked' : ''}/><span>Canlı formlarda SMTP yanıt vermezse yerel sunucu (Postfix / PHP mail) yedeğini kullan</span></label>
            </div>
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 8px">📨 Canlı SMTP Bağlantı Testi &amp; Tanılama</h3>
            <p class="zs-card-sub" style="margin-bottom:14px">Ayarlarınızı tek tıkla kaydedip gerçek bir test e-postası gönderin. Herhangi bir hata varsa sunucunun tam yanıtını aşağıda anında görün.</p>

            ${lastSt ? `
              <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:${lastSt.ok ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.12)'};border:1px solid ${lastSt.ok ? 'rgba(52,211,153,.35)' : 'rgba(248,113,113,.38)'};font-size:12.5px">
                <div style="font-weight:700;color:${lastSt.ok ? '#34d399' : '#fca5a5'}">${lastSt.ok ? '✓ Son E-posta Gönderimi Başarılı' : '⚠ Son E-posta Gönderiminde Hata'} (${esc(lastSt.at || '')})</div>
                <div style="margin-top:4px;color:var(--text-muted)">Alıcı: <strong>${esc(lastSt.to || '-')}</strong> · Yöntem: <code>${esc(lastSt.method || 'smtp')}</code></div>
                ${!lastSt.ok && lastSt.error ? `<div style="margin-top:6px;color:#fecaca">${esc(lastSt.error)}</div>` : ''}
              </div>
            ` : ''}

            <div class="zs-field">
              <label>Test Gönderilecek E-posta Adresi</label>
              <input id="smTestTo" class="zs-input" placeholder="ornek@zeynart.com" value="${esc(defaultTestTo)}"/>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
              <button type="button" class="zs-btn zs-btn-ai" id="smTestSendBtn">📨 Kaydet &amp; Test E-postası Gönder</button>
            </div>

            <div id="smTestResultWrap" style="display:none"></div>

            <div style="margin-top:16px;padding:13px;border-radius:10px;background:rgba(6,14,19,.75);border:1px solid var(--border-subtle);font-size:12px;color:var(--text-muted);line-height:1.65">
              <strong style="color:#5eead4;display:block;margin-bottom:4px">💡 Plesk &amp; Kurumsal Mail İpuçları:</strong>
              • <strong>Plesk kendi sunucunuzdaysa:</strong> Host olarak <code>mail.zeynart.com</code> (veya <code>localhost</code>), Port <code>587</code> (STARTTLS) ya da <code>465</code> (SSL/TLS) kullanın.<br/>
              • <strong>Gönderen E-posta:</strong> Sunucunun <em>553 Sender rejected</em> hatası vermemesi için <strong>SMTP Kullanıcı Adı</strong> ile aynı e-posta adresini yazın.<br/>
              • <strong>Gmail / Yandex 360:</strong> Normal hesap şifresi yerine güvenlik ayarlarından oluşturulmuş 16 haneli <strong>Uygulama Şifresi</strong> kullanılmalıdır.
            </div>
          </div>
        </div>
        `;
      })() : ''}

      ${tab === 'revisions' ? (() => {
        const revs = (Array.isArray(S.revisions) ? S.revisions : []).slice(0, 5);
        return `
        <div class="zs-grid-2" style="align-items:start">
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">OTOMATİK 5 SÜRÜM KORUMASI</span>
                <h3 style="margin-top:6px">🕒 Zaman Makinesi (Sürüm Geçmişi &amp; Tek Tıkla Dönüş)</h3>
                <p class="zs-card-sub">Sitede yapılan her kayıt işleminden önce sistem son <strong>5 sürüm</strong> kopyasını otomatik tutar (daha eski kopyalar otomatik silinir). Geçmiş bir sürüme döndüğünüzde <strong>gelen müşteri mesajlarınız ve yönetici şifreniz korunur</strong>.</p>
              </div>
              <button type="button" class="zs-btn zs-btn-sm" id="revRefreshBtn">↻ Yenile</button>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:12px;border-radius:12px;background:rgba(20,184,166,.07);border:1px solid rgba(94,234,212,.22)">
              <input id="revLabelInp" class="zs-input" style="flex:1;min-width:200px" placeholder="Örn: Ana sayfa kampanya öncesi tam yedek"/>
              <button type="button" class="zs-btn zs-btn-primary" id="revCreateBtn">+ Şimdi Anlık Yedek Noktası Al</button>
            </div>

            ${revs.length === 0 ? `
              <div class="zs-glass-empty-note">Henüz kayıtlı sürüm noktası bulunmuyor. Yukarıdaki butonla ilk yedek noktanızı oluşturabilirsiniz; bundan sonraki tüm kayıtlar otomatik eklenecektir.</div>
            ` : `
              <div class="zs-rev-timeline">
                ${revs.map((rv, idx) => {
                  const dt = (rv.created_at || '').replace('T', ' ').slice(0, 19);
                  const mt = rv.metrics || {};
                  return `
                    <div class="zs-rev-item">
                      <div style="min-width:0">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                          <strong style="color:#fff;font-size:13.5px">${esc(rv.label || 'Sistem Yedeği')}</strong>
                          ${idx === 0 ? `<span class="zs-sec-badge ok">SON KAYIT</span>` : ''}
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:3px">
                          🕒 ${esc(dt)} · 👤 ${esc(rv.actor || 'admin')} · 📦 ${esc(rv.size_kb || 0)} KB
                          ${mt.services !== undefined ? ` · ${mt.services} Hizmet · ${mt.clients || 0} Referans` : ''}
                        </div>
                      </div>
                      <div style="display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0">
                        <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-rev-restore="${esc(rv.id)}">↺ Bu Sürüme Geri Dön</button>
                        <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-rev-del="${esc(rv.id)}" title="Bu Kopyayı Sil">✕</button>
                      </div>
                    </div>
                  `;
                }).join('')}
              </div>
            `}
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 10px">📦 Taşınabilir Tam Sistem Yedeği (.json İndir &amp; Yükle)</h3>
            <p class="zs-card-sub" style="margin-bottom:16px">Tüm menü, sayfa, renk teması, hizmet, referans ve SEO yapılandırmanızı bilgisayarınıza <code>.json</code> dosyası olarak indirebilir veya başka bir sunucuya tek tıkla aktarabilirsiniz.</p>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
              <a href="${esc(CFG.apiUrl + '?action=backup_download&csrf_token=' + encodeURIComponent(CFG.csrfToken))}" class="zs-btn zs-btn-primary">⬇ Tam Sistem Yedeğini İndir (.json)</a>
              <button type="button" class="zs-btn zs-btn-ai" id="revPickJsonBtn">⬆ Bilgisayardan .json Yedek Geri Yükle</button>
              <input type="file" id="revImportFileInp" accept=".json,application/json" style="display:none"/>
            </div>

            <div style="padding:14px;border-radius:12px;background:rgba(5,12,16,.75);border:1px solid var(--border-subtle);font-size:12.5px;color:var(--text-muted);display:grid;gap:6px">
              <div><strong style="color:#5eead4">Çalışma Ortamı:</strong> Saf PHP ${esc(S.summary?.php_version || '8.x')} · <code>pages/</code> Şablon Mimarisi</div>
              <div><strong style="color:#5eead4">Görsel Optimizasyon (GD WebP):</strong> ${S.summary?.webp_supported ? 'Aktif ✓' : 'Standart Mod'}</div>
              <div><strong style="color:#5eead4">Akıllı Veri Koruması:</strong> Sürüm geri dönüşlerinde mevcut müşteri form talepleriniz (CRM) ve yönetici giriş parolanız asla silinmez.</div>
            </div>
          </div>
        </div>
        `;
      })() : ''}

      ${tab === 'security' ? (() => {
        const fw = Object.assign({
          brute_force_enabled: true,
          max_attempts: 5,
          lockout_minutes: 15,
          enforce_ip_whitelist: false,
          ip_whitelist: []
        }, S.store.firewall || {});
        const lockedIps = Array.isArray(S.lockedIps) ? S.lockedIps : [];
        const allSecLogs = Array.isArray(S.securityLogs) ? S.securityLogs : [];
        const flt = S.securityFilter || 'all';
        const filteredLogs = allSecLogs.filter(lg => {
          if (flt === 'auth') return ['login_ok', 'login_fail', 'logout', 'ip_locked', 'password_change'].includes(lg.event);
          if (flt === 'warn') return lg.level === 'warn' || lg.level === 'danger';
          if (flt === 'edits') return !['login_ok', 'login_fail', 'logout'].includes(lg.event);
          return true;
        });

        return `
        <div id="sec20AuditCardHost" style="margin-bottom:16px"></div>

        <div class="zs-grid-2" style="align-items:start;margin-bottom:16px">
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">AKTİF KORUMA KALKANI</span>
                <h3 style="margin-top:6px">🛡️ Brute-Force Kalkanı &amp; IP Güvenlik Duvarı</h3>
                <p class="zs-card-sub">Şifre deneme saldırılarını otomatik kilitler; dilerseniz yönetim panelini yalnızca kendi ofis IP adreslerinize açabilirsiniz.</p>
              </div>
              <span class="zs-pill ${fw.brute_force_enabled ? 'zs-pill-pub' : 'zs-pill-cat'}">${fw.brute_force_enabled ? 'KORUMA AKTİF' : 'PASİF'}</span>
            </div>

            <label class="zs-switch" style="margin-bottom:14px">
              <input type="checkbox" id="fwBruteEnabled" ${fw.brute_force_enabled !== false ? 'checked' : ''}/>
              <span><strong>Ardışık hatalı şifre denemelerinde saldırgan IP adresini otomatik kilitle</strong></span>
            </label>

            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Maksimum Hatalı Deneme Hakkı</label>
                <input type="number" id="fwMaxAttempts" min="3" max="20" class="zs-input" value="${Number(fw.max_attempts || 5)}"/>
              </div>
              <div class="zs-field">
                <label>Otomatik IP Kilitleme Süresi (Dakika)</label>
                <input type="number" id="fwLockMinutes" min="1" max="1440" class="zs-input" value="${Number(fw.lockout_minutes || 15)}"/>
              </div>
            </div>

            <div style="padding:12px 14px;border-radius:12px;background:rgba(4,10,14,.72);border:1px solid var(--border-subtle);margin-bottom:14px">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                <label class="zs-switch" style="margin:0">
                  <input type="checkbox" id="fwEnforceWhitelist" ${fw.enforce_ip_whitelist ? 'checked' : ''}/>
                  <span><strong>Sadece İzin Verilen IP Listesinden Girişe İzin Ver</strong></span>
                </label>
                ${S.currentIp ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="fwAddMyIpBtn">+ Mevcut IP'mi Ekle (${esc(S.currentIp)})</button>` : ''}
              </div>
              <textarea id="fwWhitelist" class="zs-textarea" style="min-height:66px;font-family:Consolas,monospace" placeholder="Her satıra 1 güvenli IP adresi yazın">${esc((fw.ip_whitelist || []).join('\n'))}</textarea>
              <div style="font-size:11.5px;color:var(--text-dim);margin-top:4px">Not: Kendinizi kilitlememeniz için kaydettiğinizde mevcut IP adresiniz (<code>${esc(S.currentIp || '127.0.0.1')}</code>) listeye otomatik dahil edilir.</div>
            </div>

            <div style="padding:12px 14px;border-radius:12px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.26);margin-bottom:14px">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                <strong style="color:#5eead4;font-size:13px">🕵️ Gizli Özel Yönetim Paneli Giriş Adresi (Stealth Slug / Fiziksel Klasör)</strong>
                <span class="zs-pill zs-pill-cat">Aktif: /${esc(S.store?.security?.admin_slug || CFG.adminDirName || 'admin')}/</span>
              </div>
              <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px">
                Varsayılan <code>/${esc(CFG.adminDirName || 'admin')}/</code> adresini özel bir adrese (örn: <code>x</code>, <code>yonetim</code>, <code>panel</code>) çevirdiğinizde bot taramaları varsayılan adreste <code>404</code> alır; OTA güncellemeleri ise hem sanal adrese hem fiziksel klasöre (<code>/${esc(CFG.adminDirName || 'admin')}/</code>) otomatik uyum sağlar.
              </div>
              <div class="zs-input-group">
                <span class="zs-btn zs-btn-sm" style="pointer-events:none;opacity:.85">/</span>
                <input id="fwAdminSlug" class="zs-input" style="font-family:Consolas,monospace" placeholder="${esc(CFG.adminDirName || 'admin')} veya x / ozel-giris-adresiniz" value="${esc(S.store?.security?.admin_slug || CFG.adminDirName || 'admin')}"/>
                <button type="button" class="zs-btn zs-btn-sm" id="fwResetAdminSlugBtn" title="Fiziksel klasör adresine sıfırla">Varsayılan (${esc(CFG.adminDirName || 'admin')})</button>
              </div>
            </div>

            <button type="button" class="zs-btn zs-btn-primary" id="fwSaveBtn">✓ Güvenlik Duvarı &amp; Giriş Adresini Kaydet</button>

            ${lockedIps.length > 0 ? `
              <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border-subtle)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                  <strong style="color:#fca5a5;font-size:13px">🔒 Şu An Kilitli Olan IP Adresleri (${lockedIps.length})</strong>
                  <button type="button" class="zs-btn zs-btn-sm" data-unlock-ip="*">Tüm Kilitleri Aç</button>
                </div>
                ${lockedIps.map(lk => `
                  <div class="zs-sort-row" style="justify-content:space-between">
                    <div style="font-size:12.5px">
                      <code style="color:#fecaca;font-weight:700">${esc(lk.ip)}</code>
                      <span style="color:var(--text-muted)"> · ${lk.attempts} hatalı deneme · Kalan: ${Math.max(1, Math.ceil((lk.remaining_sec || 60) / 60))} dk</span>
                    </div>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-unlock-ip="${esc(lk.ip)}">Kilidi Kaldır</button>
                  </div>
                `).join('')}
              </div>
            ` : ''}
          </div>

          <div class="zs-card">
            <h3 style="margin:0 0 14px">🔑 Yönetici Hesabı &amp; Parola Güncelleme</h3>
            <div class="zs-field"><label>Görünen İsim</label><input id="pwDisplay" class="zs-input" value="${esc(adm.display_name || 'Zeynart Yönetici')}"/></div>
            <div class="zs-field"><label>Yönetici Kullanıcı Adı</label><input id="pwUser" class="zs-input" value="${esc(adm.username || 'admin')}"/></div>
            <div class="zs-field"><label>Mevcut Parolanız *</label><input type="password" id="pwCurrent" class="zs-input" placeholder="Doğrulama için mevcut parolanız"/></div>
            <div class="zs-field"><label>Yeni Parola (En az 8 karakter — değiştirmek istemiyorsanız boş bırakın)</label><input type="password" id="pwNew" class="zs-input" placeholder="Yeni güçlü parola"/></div>
            <button type="button" class="zs-btn zs-btn-primary" id="pwUpdateBtn">Hesap Bilgilerini Güncelle</button>
          </div>
        </div>

        <div id="sec2faCardHost" style="margin-bottom:16px"></div>

        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">📋 Güvenlik &amp; Yönetici İşlem Kayıtları (Audit Log)</h3>
              <p class="zs-card-sub">Panele kimin, hangi IP ve tarayıcıdan giriş yaptığını, hatalı şifre denemelerini ve içerik değişikliklerini şeffaf olarak izleyin.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-sm ${flt === 'all' ? 'zs-btn-ai' : ''}" data-sec-flt="all">Tümü (${allSecLogs.length})</button>
              <button type="button" class="zs-btn zs-btn-sm ${flt === 'auth' ? 'zs-btn-ai' : ''}" data-sec-flt="auth">Giriş / Oturum</button>
              <button type="button" class="zs-btn zs-btn-sm ${flt === 'warn' ? 'zs-btn-ai' : ''}" data-sec-flt="warn">Uyarılar</button>
              <button type="button" class="zs-btn zs-btn-sm ${flt === 'edits' ? 'zs-btn-ai' : ''}" data-sec-flt="edits">İçerik İşlemleri</button>
              <button type="button" class="zs-btn zs-btn-sm" id="secRefreshBtn">↻ Yenile</button>
              ${allSecLogs.length > 0 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="secClearLogsBtn">🗑 Kayıtları Temizle</button>` : ''}
            </div>
          </div>

          ${filteredLogs.length === 0 ? `
            <div class="zs-glass-empty-note">Bu filtrede henüz güvenlik veya işlem kaydı bulunmuyor.</div>
          ` : `
            <div class="zs-table-wrap">
              <table class="zs-table">
                <thead>
                  <tr>
                    <th style="width:155px">Tarih &amp; Saat</th>
                    <th style="width:130px">Seviye / Olay</th>
                    <th>İşlem Detayı</th>
                    <th style="width:135px">Kullanıcı</th>
                    <th style="width:200px">IP &amp; Cihaz</th>
                  </tr>
                </thead>
                <tbody>
                  ${filteredLogs.slice(0, 80).map(lg => {
                    const lvl = ['ok', 'warn', 'danger', 'info'].includes(lg.level) ? lg.level : (lg.event === 'login_ok' ? 'ok' : 'info');
                    return `
                      <tr>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">${esc((lg.ts || '').replace('T', ' ').slice(0, 19))}</td>
                        <td><span class="zs-sec-badge ${lvl}">${esc(lg.event || 'islem')}</span></td>
                        <td style="font-size:13px;color:#fff">${esc(lg.detail || '')}</td>
                        <td style="font-size:12.5px;color:#99f6e4">${esc(lg.actor || 'admin')}</td>
                        <td style="font-size:12px;color:var(--text-muted)"><code>${esc(lg.ip || '-')}</code> · ${esc(lg.agent || '')}</td>
                      </tr>
                    `;
                  }).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
        `;
      })() : ''}

      ${tab === 'users' ? (() => {
        const users = Array.isArray(S.store.admin_users) && S.store.admin_users.length
          ? S.store.admin_users
          : [{ id: 'usr-primary', username: adm.username || 'admin', display_name: adm.display_name || 'Zeynart Yönetici', role: 'super_admin', active: true, is_primary: true }];
        const ed = S.editingUser || null;
        return `
        <div class="zs-grid-2" style="align-items:start">
          <div class="zs-card">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">AJANS &amp; MÜŞTERİ YETKİ AYIRIMI</span>
                <h3 style="margin-top:6px">👥 Kayıtlı Kullanıcılar &amp; Roller (${users.length})</h3>
                <p class="zs-card-sub">Müşterilerinize veya içerik ekibinize teknik ayarları bozma riski olmayan güvenli <strong>Müşteri / Editör</strong> hesabı tanımlayın.</p>
              </div>
            </div>

            <div style="display:grid;gap:10px">
              ${users.map(u => {
                const isSup = u.role === 'super_admin';
                const lastLogin = u.last_login ? u.last_login.replace('T', ' ').slice(0, 16) : 'Henüz giriş yapmadı';
                return `
                  <div class="zs-rev-item" style="opacity:${u.active === false ? '.62' : '1'}">
                    <div style="min-width:0">
                      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <strong style="color:#fff;font-size:14px">${esc(u.display_name || u.username)}</strong>
                        <code style="color:#5eead4;font-size:12px">@${esc(u.username)}</code>
                        <span class="zs-user-role-pill ${isSup ? 'super' : 'editor'}">${isSup ? 'Ajans Süper Admin' : 'Müşteri / Editör'}</span>
                        ${u.is_primary ? `<span class="zs-sec-badge ok">ANA HESAP</span>` : ''}
                        ${u.active === false ? `<span class="zs-sec-badge danger">PASİF</span>` : ''}
                      </div>
                      <div style="font-size:12px;color:var(--text-muted);margin-top:4px">
                        🕒 Son Giriş: ${esc(lastLogin)}
                      </div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0">
                      <button type="button" class="zs-btn zs-btn-sm" data-usr-edit="${esc(u.id)}">Düzenle</button>
                      ${!u.is_primary ? `
                        <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-usr-toggle="${esc(u.id)}">${u.active === false ? 'Aktif Et' : 'Pasife Al'}</button>
                        <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-usr-del="${esc(u.id)}" title="Kullanıcıyı Sil">✕</button>
                      ` : ''}
                    </div>
                  </div>
                `;
              }).join('')}
            </div>
          </div>

          <div class="zs-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px">
              <h3 style="margin:0">${ed ? `✏️ Hesabı Düzenle: @${esc(ed.username)}` : '➕ Yeni Kullanıcı / Müşteri Hesabı Ekle'}</h3>
              ${ed ? `<button type="button" class="zs-btn zs-btn-sm" id="usrCancelEditBtn">+ Yeni Hesap Formuna Dön</button>` : ''}
            </div>

            <input type="hidden" id="usrEditId" value="${esc(ed?.id || '')}"/>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Ad Soyad / Müşteri Ünvanı *</label>
                <input id="usrDisplay" class="zs-input" placeholder="Örn: Ahmet Yılmaz (Müşteri Editörü)" value="${esc(ed?.display_name || '')}"/>
              </div>
              <div class="zs-field">
                <label>Giriş Kullanıcı Adı *</label>
                <input id="usrUsername" class="zs-input" placeholder="Örn: musteri_editor" value="${esc(ed?.username || '')}" ${ed?.is_primary ? 'readonly style="opacity:.7"' : ''}/>
              </div>
            </div>

            <div class="zs-field">
              <label>Yetki Rolü</label>
              <select id="usrRole" class="zs-select" ${ed?.is_primary ? 'disabled' : ''}>
                <option value="editor" ${(!ed || ed.role === 'editor') ? 'selected' : ''}>👤 Müşteri / Editör — Sayfalar, Görsel Kanvas, Medya, Blog, SEO &amp; Talepler (Önerilen)</option>
                <option value="super_admin" ${(ed && ed.role === 'super_admin') ? 'selected' : ''}>🛡️ Ajans Süper Admin — Tam Sistem, Tema, Güvenlik &amp; Yedekleme Yetkisi</option>
              </select>
            </div>

            <div class="zs-field">
              <label>${ed ? 'Yeni Parola (Değiştirmek istemiyorsanız boş bırakın)' : 'Giriş Parolası * (En az 8 karakter)'}</label>
              <div class="zs-input-group">
                <input type="text" id="usrPassword" class="zs-input" placeholder="${ed ? 'Parolayı korumak için boş bırakın' : 'En az 8 karakterli parola girin veya üretin'}" autocomplete="new-password"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="usrGenPassBtn">🎲 Güçlü Şifre Üret</button>
              </div>
            </div>

            ${!ed?.is_primary ? `
              <label class="zs-switch" style="margin-bottom:14px">
                <input type="checkbox" id="usrActive" ${(!ed || ed.active !== false) ? 'checked' : ''}/>
                <span><strong>Hesap Aktif (Panele giriş yapabilir)</strong></span>
              </label>
            ` : ''}

            <div style="padding:12px 14px;border-radius:12px;background:rgba(20,184,166,.07);border:1px solid rgba(94,234,212,.22);font-size:12px;color:var(--text-muted);margin-bottom:14px">
              <strong style="color:#5eead4;display:block;margin-bottom:4px">💡 Müşteri / Editör Rolü Neleri Kısıtlar?</strong>
              Editör hesabıyla giriş yapan müşteri; yazılarını, görsellerini, logolarını, teklif sihirbazını ve gelen form taleplerini rahatça yönetir; ancak <strong>White-Label Tema, Bakım Modu, SMTP, Güvenlik Duvarı, Ham Script Kodları ve Sistem Yedeklerine</strong> erişemez (HTTP 403 korumalı).
            </div>

            <button type="button" class="zs-btn zs-btn-primary" id="usrSaveBtn">✓ ${ed ? 'Kullanıcı Güncellemesini Kaydet' : 'Kullanıcı Hesabını Oluştur'}</button>
          </div>
        </div>
        `;
      })() : ''}

      ${tab === 'performance' ? `<div id="setPerformanceHost"></div>` : ''}
      ${tab === 'backups' ? `<div id="setBackupsHost"></div>` : ''}
    `;

    if (tab === 'database') {
      mountDatabaseStudioTab(document.getElementById('setDatabaseHost'));
    } else if (tab === 'performance') {
      mountSystemPerformanceTab(document.getElementById('setPerformanceHost'));
    } else if (tab === 'backups') {
      mountVaultBackupsTab(document.getElementById('setBackupsHost'));
    } else if (tab === 'security') {
      mountSecurity20AuditCard(document.getElementById('sec20AuditCardHost'));
      mountSecurity2faCard(document.getElementById('sec2faCardHost'));
    }

    const syncSettingsDom = () => {
      if (tab === 'identity') {
        site.name = document.getElementById('stName').value.trim();
        site.legal_name = document.getElementById('stLegal').value.trim();
        site.tagline = document.getElementById('stTagline').value.trim();
        site.phone = document.getElementById('stPhone').value.trim();
        site.phone_href = document.getElementById('stPhoneHref').value.trim();
        site.email = document.getElementById('stEmail').value.trim();
        site.city = document.getElementById('stCity').value.trim();
        site.founded_year = document.getElementById('stYear').value.trim();
        site.address = document.getElementById('stAddr').value.trim();
        site.logo = document.getElementById('stLogo').value.trim();
        site.favicon = document.getElementById('stFav').value.trim();
        site.og_image = document.getElementById('stOg').value.trim();
        site.enable_intro = document.getElementById('stIntro').checked;
        site.intro_mode = document.getElementById('stIntroMode')?.value || 'always';
        site.enable_custom_cursor = document.getElementById('stCursor').checked;
        site.enable_scroll_progress = document.getElementById('stScroll').checked;
        site.show_footer_socials = Boolean(document.getElementById('stShowFooterSoc')?.checked);
        site.show_header_socials = Boolean(document.getElementById('stShowHeaderSoc')?.checked);
        site.show_contact_socials = Boolean(document.getElementById('stShowContactSoc')?.checked);
        S.store.seo = S.store.seo || {};
        S.store.seo.social_profiles = (document.getElementById('stSocials')?.value || '').split('\n').map(s => s.trim()).filter(Boolean);
        S.store.menus = S.store.menus || {};
        S.store.menus.typography = S.store.menus.typography || {};
        const stBodySel = document.getElementById('stBodyFont');
        const stHeadSel = document.getElementById('stHeadingFont');
        if (stBodySel) {
          S.store.menus.typography.body_font = stBodySel.value || 'Manrope';
          S.store.menus.typography.custom_body_font = extractGoogleFontName(document.getElementById('stCustomBodyFont')?.value || '');
        }
        if (stHeadSel) {
          S.store.menus.typography.heading_font = stHeadSel.value || 'same';
          S.store.menus.typography.custom_heading_font = extractGoogleFontName(document.getElementById('stCustomHeadFont')?.value || '');
        }
      } else if (tab === 'i18n') {
        i18n.enabled = true;
        i18n.auto_translate_blog = Boolean(document.getElementById('i18nAutoBlog')?.checked);
        i18n.provider = document.getElementById('i18nProvider')?.value || 'auto';
        i18n.deepl_api_key = document.getElementById('i18nDeeplKey')?.value.trim() || '';
        i18n.openai_api_key = document.getElementById('i18nOpenaiKey')?.value.trim() || '';
        S.store.i18n = i18n;
      } else if (tab === 'smtp') {
        smtp.enabled = document.getElementById('smEnabled').checked;
        smtp.host = document.getElementById('smHost').value.trim();
        smtp.port = Number(document.getElementById('smPort').value || 587);
        smtp.encryption = document.getElementById('smEnc').value;
        smtp.username = document.getElementById('smUser').value.trim();
        smtp.password = document.getElementById('smPass').value;
        smtp.from_email = document.getElementById('smFromMail').value.trim();
        smtp.from_name = document.getElementById('smFromName').value.trim();
        smtp.notify_email = document.getElementById('smNotify').value.trim();
        smtp.allow_self_signed = Boolean(document.getElementById('smAllowSelfSigned')?.checked);
        smtp.fallback_php_mail = Boolean(document.getElementById('smFallbackMail')?.checked);
      }
      S.store.site = site;
      S.store.smtp = smtp;
    };

    if (tab === 'smtp') {
      const encEl = document.getElementById('smEnc');
      const hostEl = document.getElementById('smHost');
      const portEl = document.getElementById('smPort');
      const userEl = document.getElementById('smUser');
      const passEl = document.getElementById('smPass');
      const fromEl = document.getElementById('smFromMail');

      const updateRemoteFieldState = () => {
        const isLocal = encEl.value === 'local';
        ROOT.querySelectorAll('.sm-remote-field').forEach(el => {
          el.style.opacity = isLocal ? '.45' : '1';
          el.style.pointerEvents = isLocal ? 'none' : 'auto';
        });
      };

      encEl.addEventListener('change', () => {
        if (encEl.value === 'ssl' && (portEl.value === '587' || portEl.value === '25')) portEl.value = '465';
        else if (encEl.value === 'tls' && (portEl.value === '465' || portEl.value === '25')) portEl.value = '587';
        else if (encEl.value === 'none' && portEl.value === '465') portEl.value = '25';
        updateRemoteFieldState();
      });

      userEl.addEventListener('blur', () => {
        const uv = userEl.value.trim();
        if (uv.includes('@') && (!fromEl.value.trim() || fromEl.value.trim() === 'info@zeynart.com')) {
          fromEl.value = uv;
        }
      });

      const togglePassBtn = document.getElementById('smTogglePassBtn');
      if (togglePassBtn) {
        togglePassBtn.onclick = () => {
          const isPw = passEl.type === 'password';
          passEl.type = isPw ? 'text' : 'password';
          togglePassBtn.textContent = isPw ? '🙈 Gizle' : '👁 Göster';
        };
      }

      ROOT.querySelectorAll('[data-smtp-preset]').forEach(btn => {
        btn.onclick = () => {
          const p = btn.getAttribute('data-smtp-preset');
          document.getElementById('smEnabled').checked = true;
          const dom = (window.location.hostname && !window.location.hostname.includes('127.0.0.1') && window.location.hostname !== 'localhost')
            ? window.location.hostname.replace(/^www\./i, '')
            : 'zeynart.com';
          if (p === 'plesk587') {
            encEl.value = 'tls';
            hostEl.value = 'mail.' + dom;
            portEl.value = '587';
          } else if (p === 'plesk465') {
            encEl.value = 'ssl';
            hostEl.value = 'mail.' + dom;
            portEl.value = '465';
          } else if (p === 'local') {
            encEl.value = 'local';
          } else if (p === 'yandex') {
            encEl.value = 'ssl';
            hostEl.value = 'smtp.yandex.com';
            portEl.value = '465';
          } else if (p === 'gmail') {
            encEl.value = 'tls';
            hostEl.value = 'smtp.gmail.com';
            portEl.value = '587';
          } else if (p === 'm365') {
            encEl.value = 'tls';
            hostEl.value = 'smtp.office365.com';
            portEl.value = '587';
          }
          updateRemoteFieldState();
          toast('Şablon uygulandı. Kullanıcı adı ve parolanızı kontrol edip test gönderebilirsiniz.');
        };
      });

      const testSendBtn = document.getElementById('smTestSendBtn');
      const testResWrap = document.getElementById('smTestResultWrap');
      if (testSendBtn && testResWrap) {
        testSendBtn.onclick = async () => {
          document.getElementById('smEnabled').checked = true;
          syncSettingsDom();
          const testTo = (document.getElementById('smTestTo')?.value || '').trim();
          testSendBtn.disabled = true;
          testSendBtn.textContent = '⏳ Sunucuya Bağlanılıyor & Test Gönderiliyor...';
          testResWrap.style.display = 'block';
          testResWrap.innerHTML = `<div style="padding:12px;border-radius:10px;background:rgba(14,165,233,.1);border:1px solid rgba(56,189,248,.3);font-size:12.5px;color:#bae6fd">⏳ SMTP sunucusu ile el sıkışma ve kimlik doğrulama test ediliyor...</div>`;
          try {
            const res = await api('smtp_test', {
              smtp: S.store.smtp,
              test_to: testTo
            });
            if (res.smtp) S.store.smtp = res.smtp;
            if (res.smtp_last_status) S.store.smtp_last_status = res.smtp_last_status;
            const logHtml = (res.transcript && res.transcript.length)
              ? `<details style="margin-top:10px"${res.sent ? '' : ' open'}><summary style="cursor:pointer;font-size:11.5px;color:#7dd3fc;font-weight:600">SMTP Sunucu Diyalog Kaydı (${res.transcript.length} satır)</summary><pre style="margin:8px 0 0;padding:10px;border-radius:8px;background:#03080b;color:#a5f3fc;font-size:11px;max-height:210px;overflow:auto;white-space:pre-wrap">${esc(res.transcript.join('\n'))}</pre></details>`
              : '';
            if (res.sent) {
              toast(res.message);
              testResWrap.innerHTML = `
                <div style="padding:13px 15px;border-radius:10px;background:rgba(16,185,129,.12);border:1px solid rgba(52,211,153,.4);font-size:12.5px;color:#d1fae5">
                  <strong style="color:#34d399;display:block;margin-bottom:4px">✓ Bağlantı ve Gönderim Başarılı!</strong>
                  <div>${esc(res.message)}</div>
                  ${res.server_reply ? `<div style="margin-top:4px;font-size:11.5px;color:#a7f3d0">Sunucu Yanıtı: <code>${esc(res.server_reply)}</code></div>` : ''}
                  ${logHtml}
                </div>
              `;
            } else {
              toast(res.error || 'SMTP testi başarısız oldu.', true);
              testResWrap.innerHTML = `
                <div style="padding:13px 15px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(248,113,113,.4);font-size:12.5px;color:#fee2e2">
                  <strong style="color:#fca5a5;display:block;margin-bottom:4px">⚠ SMTP Gönderimi Başarısız Oldu</strong>
                  <div>${esc(res.error || res.message)}</div>
                  ${logHtml}
                </div>
              `;
            }
          } catch (err) {
            toast(err.message, true);
            testResWrap.innerHTML = `<div style="padding:12px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(248,113,113,.4);color:#fecaca;font-size:12.5px">⚠ ${esc(err.message)}</div>`;
          }
          testSendBtn.disabled = false;
          testSendBtn.textContent = '📨 Kaydet & Test E-postası Gönder';
        };
      }
    }

    ROOT.querySelectorAll('[data-settab]').forEach(b => {
      b.onclick = () => { syncSettingsDom(); S.tabs.settings = b.getAttribute('data-settab'); renderSettingsView(); };
    });
    document.getElementById('stJumpSocialCenterBtn')?.addEventListener('click', () => {
      syncSettingsDom();
      S.tabs.social_media = 'accounts';
      navigateTo('social_media');
    });

    ROOT.querySelectorAll('[data-stpick]').forEach(b => {
      b.onclick = () => {
        const id = b.getAttribute('data-stpick');
        openMediaPicker(u => { document.getElementById(id).value = u; });
      };
    });

    const stSocInput = document.getElementById('stSocials');
    const stSocBadges = document.getElementById('stSocialBadges');
    if (stSocInput && stSocBadges) {
      stSocInput.addEventListener('input', () => {
        const lines = stSocInput.value.split('\n').map(s => s.trim()).filter(Boolean);
        stSocBadges.innerHTML = lines.length
          ? lines.map(u => `<span class="zs-pill zs-pill-cat">✓ ${esc(detectSocialBadgeLabel(u))}</span>`).join('')
          : '<span style="font-size:11.5px;color:var(--text-dim)">Henüz sosyal medya hesabı eklenmedi.</span>';
      });
    }

    const stBodySelEl = document.getElementById('stBodyFont');
    const stHeadSelEl = document.getElementById('stHeadingFont');
    if (stBodySelEl) {
      stBodySelEl.addEventListener('change', () => {
        const w = document.getElementById('stCustomBodyWrap');
        if (w) w.style.display = stBodySelEl.value === 'custom' ? 'block' : 'none';
      });
    }
    if (stHeadSelEl) {
      stHeadSelEl.addEventListener('change', () => {
        const w = document.getElementById('stCustomHeadWrap');
        if (w) w.style.display = stHeadSelEl.value === 'custom' ? 'block' : 'none';
      });
    }

    const stGoFontBtn = document.getElementById('stGoFontStudioBtn');
    if (stGoFontBtn) {
      stGoFontBtn.onclick = () => {
        syncSettingsDom();
        S.tabs.menus = 'typography';
        navigateTo('menus');
      };
    }

    if (tab === 'i18n') {
      refreshI18nStudioMount();
      loadI18nTranslateQueue(false);
    }

    const saveBtn = document.getElementById('saveSettingsBtn');
    if (saveBtn) {
      saveBtn.onclick = async () => {
        syncSettingsDom();
        try {
          const res = await api('settings_save', {
            site: S.store.site,
            social_profiles: S.store.seo?.social_profiles || [],
            typography: S.store.menus?.typography || {},
            smtp: S.store.smtp,
            i18n: S.store.i18n || {}
          });
          S.store.site = res.site;
          if (res.menus) S.store.menus = res.menus;
          if (res.seo) S.store.seo = res.seo;
          if (res.i18n) S.store.i18n = res.i18n;
          S.store.smtp = res.smtp;
          applyLiveAdminBrandState(null, S.store.theme_brand, res.site);
          toast(res.message);
          renderSettingsView();
        } catch (err) {
          toast(err.message, true);
        }
      };
    }

    if (tab === 'revisions') {
      document.getElementById('revRefreshBtn')?.addEventListener('click', async () => {
        try {
          const res = await api('revisions_list', {});
          S.revisions = res.revisions || [];
          toast('Sürüm geçmişi güncellendi.');
          renderSettingsView();
        } catch (err) {
          toast(err.message, true);
        }
      });

      document.getElementById('revCreateBtn')?.addEventListener('click', async () => {
        const lbl = (document.getElementById('revLabelInp')?.value || '').trim() || 'Manuel Yedek Noktası';
        const btn = document.getElementById('revCreateBtn');
        btn.disabled = true;
        btn.textContent = '⏳ Oluşturuluyor...';
        try {
          const res = await api('revision_create', { label: lbl });
          S.revisions = res.revisions || [];
          toast(res.message);
          renderSettingsView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
          btn.textContent = '+ Şimdi Anlık Yedek Noktası Al';
        }
      });

      ROOT.querySelectorAll('[data-rev-restore]').forEach(btn => {
        btn.onclick = async () => {
          const revId = btn.getAttribute('data-rev-restore');
          if (!window.confirm('Seçilen sürüme geri dönülecek (mevcut durumunuz için de otomatik güvenlik kopyası alınır; müşteri mesajlarınız ve şifreniz korunur). Onaylıyor musunuz?')) {
            return;
          }
          btn.disabled = true;
          btn.textContent = '⏳ Yükleniyor...';
          try {
            const res = await api('revision_restore', { id: revId });
            if (res.store) {
              S.store = normalizeClientStoreMaps(res.store);
              ensureNormalizedHomeSections();
            }
            if (res.summary) S.summary = res.summary;
            if (res.revisions) S.revisions = res.revisions;
            applyLiveAdminBrandState(res.admin_theme_css || '', S.store.theme_brand, S.store.site);
            toast(res.message);
            renderSettingsView();
          } catch (err) {
            toast(err.message, true);
            btn.disabled = false;
            btn.textContent = '↺ Bu Sürüme Geri Dön';
          }
        };
      });

      ROOT.querySelectorAll('[data-rev-del]').forEach(btn => {
        btn.onclick = async () => {
          const revId = btn.getAttribute('data-rev-del');
          try {
            const res = await api('revision_delete', { id: revId });
            S.revisions = res.revisions || [];
            toast(res.message);
            renderSettingsView();
          } catch (err) {
            toast(err.message, true);
          }
        };
      });

      const pickJsonBtn = document.getElementById('revPickJsonBtn');
      const jsonFileInp = document.getElementById('revImportFileInp');
      if (pickJsonBtn && jsonFileInp) {
        pickJsonBtn.onclick = () => jsonFileInp.click();
        jsonFileInp.onchange = async () => {
          const f = jsonFileInp.files?.[0];
          if (!f) return;
          if (!window.confirm(`"${f.name}" yedek dosyası sisteme geri yüklenecek. Devam edilsin mi?`)) {
            jsonFileInp.value = '';
            return;
          }
          try {
            const rawText = await f.text();
            const res = await api('backup_restore_json', { backup_json: rawText });
            if (res.store) {
              S.store = normalizeClientStoreMaps(res.store);
              ensureNormalizedHomeSections();
            }
            if (res.summary) S.summary = res.summary;
            if (res.revisions) S.revisions = res.revisions;
            applyLiveAdminBrandState(res.admin_theme_css || '', S.store.theme_brand, S.store.site);
            toast(res.message);
            renderSettingsView();
          } catch (err) {
            toast(err.message, true);
          }
          jsonFileInp.value = '';
        };
      }
    }

    if (tab === 'security') {
      document.getElementById('fwAddMyIpBtn')?.addEventListener('click', () => {
        const wlEl = document.getElementById('fwWhitelist');
        if (!wlEl || !S.currentIp) return;
        const lines = wlEl.value.split('\n').map(x => x.trim()).filter(Boolean);
        if (!lines.includes(S.currentIp)) {
          lines.push(S.currentIp);
          wlEl.value = lines.join('\n');
        }
        toast(`Mevcut IP adresiniz (${S.currentIp}) listeye eklendi.`);
      });

      document.getElementById('fwResetAdminSlugBtn')?.addEventListener('click', () => {
        const slInp = document.getElementById('fwAdminSlug');
        if (slInp) slInp.value = CFG.adminDirName || 'admin';
      });

      document.getElementById('fwSaveBtn')?.addEventListener('click', async () => {
        const firewallPayload = {
          brute_force_enabled: Boolean(document.getElementById('fwBruteEnabled')?.checked),
          max_attempts: Number(document.getElementById('fwMaxAttempts')?.value || 5),
          lockout_minutes: Number(document.getElementById('fwLockMinutes')?.value || 15),
          enforce_ip_whitelist: Boolean(document.getElementById('fwEnforceWhitelist')?.checked),
          ip_whitelist: (document.getElementById('fwWhitelist')?.value || '').split('\n').map(x => x.trim()).filter(Boolean)
        };
        const admin_slug = (document.getElementById('fwAdminSlug')?.value || CFG.adminDirName || 'admin').trim().replace(/^\/+|\/+$/g, '');
        try {
          const res = await api('firewall_save', { firewall: firewallPayload, admin_slug });
          S.store.firewall = res.firewall;
          if (res.admin_slug) {
            S.store.security = S.store.security || {};
            S.store.security.admin_slug = res.admin_slug;
          }
          if (res.security_logs) S.securityLogs = res.security_logs;
          if (res.locked_ips) S.lockedIps = res.locked_ips;
          toast(res.message);
          if (res.slug_changed && res.new_admin_url) {
            setTimeout(() => {
              window.location.href = res.new_admin_url.replace(/\/+$/, '/') + '?view=settings&tab=security';
            }, 450);
            return;
          }
          renderSettingsView();
        } catch (err) {
          toast(err.message, true);
        }
      });

      ROOT.querySelectorAll('[data-unlock-ip]').forEach(btn => {
        btn.onclick = async () => {
          const ip = btn.getAttribute('data-unlock-ip');
          try {
            const res = await api('security_unlock_ip', { ip });
            S.lockedIps = res.locked_ips || [];
            if (res.security_logs) S.securityLogs = res.security_logs;
            toast(res.message);
            renderSettingsView();
          } catch (err) {
            toast(err.message, true);
          }
        };
      });

      ROOT.querySelectorAll('[data-sec-flt]').forEach(btn => {
        btn.onclick = () => {
          S.securityFilter = btn.getAttribute('data-sec-flt');
          renderSettingsView();
        };
      });

      document.getElementById('secRefreshBtn')?.addEventListener('click', async () => {
        try {
          const res = await api('security_overview', {});
          if (res.firewall) S.store.firewall = res.firewall;
          if (res.security_audit_20) S.securityAudit20 = res.security_audit_20;
          S.securityLogs = res.security_logs || [];
          S.lockedIps = res.locked_ips || [];
          if (res.current_ip) S.currentIp = res.current_ip;
          toast('Güvenlik ve giriş kayıtları yenilendi.');
          renderSettingsView();
        } catch (err) {
          toast(err.message, true);
        }
      });

      document.getElementById('secClearLogsBtn')?.addEventListener('click', async () => {
        if (!window.confirm('Tüm güvenlik ve işlem kayıtları temizlensin mi?')) return;
        try {
          const res = await api('security_clear_logs', {});
          S.securityLogs = res.security_logs || [];
          toast(res.message);
          renderSettingsView();
        } catch (err) {
          toast(err.message, true);
        }
      });
    }

    if (tab === 'users') {
      document.getElementById('usrGenPassBtn')?.addEventListener('click', () => {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#';
        let out = 'Zyn!';
        for (let i = 0; i < 10; i++) {
          out += chars[Math.floor(Math.random() * chars.length)];
        }
        const inp = document.getElementById('usrPassword');
        if (inp) {
          inp.value = out;
          inp.select();
        }
        toast('Güçlü parola üretildi: ' + out);
      });

      document.getElementById('usrCancelEditBtn')?.addEventListener('click', () => {
        S.editingUser = null;
        renderSettingsView();
      });

      ROOT.querySelectorAll('[data-usr-edit]').forEach(btn => {
        btn.onclick = () => {
          const uid = btn.getAttribute('data-usr-edit');
          const found = (S.store.admin_users || []).find(x => x.id === uid);
          if (found) {
            S.editingUser = Object.assign({}, found);
            renderSettingsView();
          }
        };
      });

      ROOT.querySelectorAll('[data-usr-toggle]').forEach(btn => {
        btn.onclick = async () => {
          const uid = btn.getAttribute('data-usr-toggle');
          try {
            const res = await api('user_toggle_active', { id: uid });
            S.store.admin_users = res.users || [];
            toast(res.message);
            renderSettingsView();
          } catch (err) {
            toast(err.message, true);
          }
        };
      });

      ROOT.querySelectorAll('[data-usr-del]').forEach(btn => {
        btn.onclick = async () => {
          const uid = btn.getAttribute('data-usr-del');
          if (!window.confirm('Bu kullanıcı hesabını kalıcı olarak silmek istediğinize emin misiniz?')) return;
          try {
            const res = await api('user_delete', { id: uid });
            S.store.admin_users = res.users || [];
            if (S.editingUser && S.editingUser.id === uid) S.editingUser = null;
            toast(res.message);
            renderSettingsView();
          } catch (err) {
            toast(err.message, true);
          }
        };
      });

      document.getElementById('usrSaveBtn')?.addEventListener('click', async () => {
        const payload = {
          id: document.getElementById('usrEditId')?.value || '',
          display_name: document.getElementById('usrDisplay')?.value.trim() || '',
          username: document.getElementById('usrUsername')?.value.trim() || '',
          role: document.getElementById('usrRole')?.value || 'editor',
          password: document.getElementById('usrPassword')?.value || '',
          active: document.getElementById('usrActive') ? Boolean(document.getElementById('usrActive').checked) : true
        };
        try {
          const res = await api('user_save', payload);
          S.store.admin_users = res.users || [];
          S.editingUser = null;
          toast(res.message);
          renderSettingsView();
        } catch (err) {
          toast(err.message, true);
        }
      });
    }

    const pwBtn = document.getElementById('pwUpdateBtn');
    if (pwBtn) {
      pwBtn.onclick = async () => {
        try {
          const res = await api('password_change', {
            display_name: document.getElementById('pwDisplay').value.trim(),
            username: document.getElementById('pwUser').value.trim(),
            current_password: document.getElementById('pwCurrent').value,
            new_password: document.getElementById('pwNew').value
          });
          S.store.admin.display_name = document.getElementById('pwDisplay').value.trim();
          S.store.admin.username = document.getElementById('pwUser').value.trim();
          if (res.current_user) {
            S.currentUser = res.current_user;
            S.store.current_user = res.current_user;
          }
          syncUserRoleUi();
          document.getElementById('pwCurrent').value = '';
          document.getElementById('pwNew').value = '';
          toast(res.message);
        } catch (err) {
          toast(err.message, true);
        }
      };
    }
  }

  /* ======================== MODULE: AGENCY UPTIME & SSL CONTROL TOWER (300+ SITES) ======================== */
  function syncCurrentUptimeInputsBeforeRender() {
    const sEl = document.getElementById('utSearchInp');
    if (sEl) S.uptime.search = sEl.value;
    const gEl = document.getElementById('utGroupSel');
    if (gEl) S.uptime.group = gEl.value;
  }

  async function runUptimeBatchScan(targetIds, forceSsl) {
    if (S.uptime.scanning) return;
    const ids = Array.isArray(targetIds) ? targetIds.filter(Boolean) : [];
    if (!ids.length) {
      toast('Kontrol edilecek aktif müşteri sitesi bulunamadı.', true);
      return;
    }
    S.uptime.scanning = true;
    S.uptime.stopRequested = false;
    S.uptime.scanDone = 0;
    S.uptime.scanTotal = ids.length;
    if (S.view === 'uptime_tower') renderUptimeTowerView();

    const chunkSize = 18;
    try {
      for (let i = 0; i < ids.length; i += chunkSize) {
        if (S.uptime.stopRequested) break;
        const chunk = ids.slice(i, i + chunkSize);
        const res = await api('uptime_check_batch', {
          ids: chunk,
          limit: chunkSize,
          force_ssl: Boolean(forceSsl)
        });
        if (Array.isArray(res.monitors)) S.uptime.monitors = res.monitors;
        if (res.uptime_summary) S.uptime.summary = res.uptime_summary;
        S.uptime.scanDone = Math.min(ids.length, i + chunk.length);
        if (S.view === 'uptime_tower') renderUptimeTowerView();
      }
      if (S.uptime.stopRequested) {
        toast(`Tarama durduruldu (${S.uptime.scanDone} / ${S.uptime.scanTotal} site kontrol edildi).`);
      } else {
        toast(`✓ Toplam ${S.uptime.scanDone} sitenin HTTP erişim hızı ve SSL sertifikası kontrol edildi!`);
      }
    } catch (err) {
      toast(err.message || 'Toplu tarama sırasında bir hata oluştu.', true);
    } finally {
      S.uptime.scanning = false;
      S.uptime.stopRequested = false;
      if (S.view === 'uptime_tower') renderUptimeTowerView();
    }
  }

  function formatRelativeCheckTime(isoStr, ts) {
    const unix = Number(ts || 0);
    if (!unix && !isoStr) return 'Henüz taranmadı';
    const sec = Math.max(0, Math.floor(Date.now() / 1000) - unix);
    if (unix > 0) {
      if (sec < 45) return 'Az önce';
      if (sec < 3600) return `${Math.floor(sec / 60)} dk önce`;
      if (sec < 86400) return `${Math.floor(sec / 3600)} sa önce`;
      return `${Math.floor(sec / 86400)} gün önce`;
    }
    return String(isoStr).slice(0, 16).replace('T', ' ');
  }

  function renderUptimeTowerView() {
    syncAdminUrl('replace');
    const mons = Array.isArray(S.uptime.monitors) ? S.uptime.monitors : [];
    const cfg = S.store?.uptime_tower || {};
    const sum = S.uptime.summary || {
      total: mons.length,
      up_count: mons.filter(m => m.status === 'up').length,
      down_count: mons.filter(m => m.status === 'down').length,
      pending_count: mons.filter(m => !m.status || m.status === 'pending').length,
      ssl_expiring_count: 0,
      slow_count: 0,
      avg_response_ms: 0,
      overall_uptime_pct: 100,
      groups: {}
    };

    const sslWarnDays = Number(cfg.ssl_warn_days || 14);
    const slowMs = Number(cfg.slow_ms_threshold || 1800);
    const groupNames = Object.keys(sum.groups || {});
    const superMode = isSuperAdmin();

    const searchQ = String(S.uptime.search || '').trim().toLowerCase();
    const curFilter = S.uptime.filter || 'all';
    const curGroup = S.uptime.group || 'all';

    const filtered = mons.filter(m => {
      if (curGroup !== 'all' && (m.server_group || 'Genel') !== curGroup) return false;
      const isSslWarn = m.ssl_valid === false || (typeof m.ssl_days_left === 'number' && m.ssl_days_left <= sslWarnDays);
      const isSlow = m.status === 'up' && Number(m.response_ms || 0) >= slowMs;
      if (curFilter === 'up' && m.status !== 'up') return false;
      if (curFilter === 'down' && m.status !== 'down') return false;
      if (curFilter === 'ssl' && !isSslWarn) return false;
      if (curFilter === 'slow' && !isSlow) return false;
      if (curFilter === 'pending' && m.status && m.status !== 'pending') return false;
      if (searchQ) {
        const hay = `${m.domain || ''} ${m.url || ''} ${m.client_name || ''} ${m.server_group || ''} ${m.notes || ''} ${m.ssl_issuer || ''}`.toLowerCase();
        if (!hay.includes(searchQ)) return false;
      }
      return true;
    });

    const scanPct = S.uptime.scanTotal > 0 ? Math.min(100, Math.round((S.uptime.scanDone / S.uptime.scanTotal) * 100)) : 0;
    const ed = S.uptime.editingMonitor;

    ROOT.innerHTML = `
      <!-- HERO DECK -->
      <section class="zs-nb-hero" style="margin-bottom:18px">
        <div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
            <span class="zs-badge-pill" style="${cfg.enabled !== false ? 'background:rgba(16,185,129,.2);color:#6ee7b7;border-color:rgba(52,211,153,.45)' : 'background:rgba(245,158,11,.18);color:#fde68a;border-color:rgba(251,191,36,.4)'}">
              ${cfg.enabled !== false ? '● OTOPİLOT İZLEME AKTİF' : '⏸ ARKA PLAN İZLEME KAPALI'} · HER ${Number(cfg.check_interval_minutes || 15)} DK
            </span>
            <span class="zs-badge-pill" style="color:#67e8f9">⚡ Ort. Yanıt: ${Number(sum.avg_response_ms || 0)} ms</span>
            <span class="zs-badge-pill" style="color:#a7f3d0">✦ Genel Uptime: %${sum.overall_uptime_pct ?? 100}</span>
          </div>
          <h2 style="margin:0 0 6px;font-size:20px;color:#f4fcfd">🛰️ Ajans Uptime, Hız &amp; SSL Kontrol Kulesi</h2>
          <p class="zs-card-sub" style="margin:0;max-width:780px;line-height:1.55">
            Yönettiğiniz <strong>300+ müşteri web sitesinin</strong> HTTP erişim durumunu, sunucu yanıt hızını (ms) ve <strong>SSL sertifika bitiş günlerini</strong> paralel <code>curl_multi</code> motoruyla tek ekrandan izleyin; kesinti veya SSL bitişi yaklaştığında anında haberdar olun.
          </p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          ${S.uptime.scanning ? `
            <button type="button" class="zs-btn zs-btn-danger" id="utStopScanBtn">⏹ Taramayı Durdur (${S.uptime.scanDone}/${S.uptime.scanTotal})</button>
          ` : `
            <button type="button" class="zs-btn zs-btn-primary" id="utScanAllBtn">⚡ Tüm Siteleri Şimdi Tara (${mons.filter(x => x.enabled !== false).length})</button>
          `}
          <button type="button" class="zs-btn zs-btn-ai" id="utToggleBulkBtn">📥 + Toplu Site Aktar (300+ Domain)</button>
          <button type="button" class="zs-btn" id="utOpenAddBtn">+ Tek Site Ekle</button>
          ${superMode ? `<button type="button" class="zs-btn" id="utToggleCfgBtn">⚙️ Alarm &amp; Cron Ayarları</button>` : ''}
          <button type="button" class="zs-btn zs-btn-sm" id="utRefreshListBtn" title="Sunucudaki son kayıtları yenile">↻</button>
        </div>
      </section>

      ${S.uptime.scanning ? `
        <div class="zs-ut-scan-banner">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px;flex-wrap:wrap">
            <strong style="color:#6ee7b7;font-size:13.5px">🛰️ Paralel HTTP &amp; SSL Taraması Devam Ediyor... (%${scanPct})</strong>
            <span style="font-size:12px;color:var(--text-muted)">${S.uptime.scanDone} / ${S.uptime.scanTotal} müşteri sitesi denetlendi</span>
          </div>
          <div class="zs-ut-progress-track">
            <div class="zs-ut-progress-fill" style="width:${scanPct}%"></div>
          </div>
        </div>
      ` : ''}

      <!-- 4 KPI CARDS -->
      <div class="zs-kpi-grid" style="margin-bottom:18px">
        <div class="zs-kpi zs-kpi-sky">
          <div class="zs-kpi-top"><span>İZLENEN MÜŞTERİ SİTESİ</span><span class="zs-kpi-icon">🌐</span></div>
          <strong>${Number(sum.total || mons.length).toLocaleString('tr-TR')}</strong>
          <small>${Number(sum.up_count || 0)} aktif yayında · ${Number(sum.pending_count || 0)} ilk tarama bekliyor</small>
        </div>
        <div class="zs-kpi ${Number(sum.down_count || 0) > 0 ? 'zs-kpi-violet' : 'zs-kpi-emerald'}">
          <div class="zs-kpi-top"><span>ERİŞİM SORUNU (DOWN)</span><span class="zs-kpi-icon">${Number(sum.down_count || 0) > 0 ? '🚨' : '✓'}</span></div>
          <strong style="color:${Number(sum.down_count || 0) > 0 ? '#fca5a5' : '#6ee7b7'}">${Number(sum.down_count || 0)}</strong>
          <small>${Number(sum.down_count || 0) > 0 ? 'Acil müdahale gerektiren site var!' : 'Tüm taranan siteler sorunsuz yanıt veriyor'}</small>
        </div>
        <div class="zs-kpi zs-kpi-mint">
          <div class="zs-kpi-top"><span>SSL SERTİFİKA UYARISI</span><span class="zs-kpi-icon">🔒</span></div>
          <strong style="color:${Number(sum.ssl_expiring_count || 0) > 0 ? '#fde68a' : '#6ee7b7'}">${Number(sum.ssl_expiring_count || 0)}</strong>
          <small>Eşik: ≤ ${sslWarnDays} gün kalan veya geçersiz SSL</small>
        </div>
        <div class="zs-kpi zs-kpi-emerald">
          <div class="zs-kpi-top"><span>ORTALAMA YANIT HIZI</span><span class="zs-kpi-icon">⚡</span></div>
          <strong>${Number(sum.avg_response_ms || 0)} <span style="font-size:15px;color:var(--text-muted)">ms</span></strong>
          <small>${Number(sum.slow_count || 0)} yavaş yanıt (≥${slowMs}ms) · %${sum.overall_uptime_pct ?? 100} Uptime</small>
        </div>
      </div>

      <!-- SINGLE ADD / EDIT DRAWER -->
      ${ed ? `
        <div class="zs-card" style="border-color:rgba(94,234,212,.45);margin-bottom:18px">
          <div class="zs-card-head">
            <div>
              <h3>${ed.id ? '✏️ Müşteri Sitesini Düzenle' : '➕ İzleme Kulesine Yeni Site Ekle'}</h3>
              <p class="zs-card-sub">Alan adını yazmanız yeterlidir; kaydedildiğinde anında HTTP ve SSL sertifika kontrolü yapılır.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm" id="utCancelEditBtn">✕ Kapat</button>
          </div>
          <div class="zs-grid-4">
            <div class="zs-field">
              <label>Alan Adı veya URL *</label>
              <input id="utEdUrl" class="zs-input" placeholder="orneksite.com" value="${esc(ed.url || ed.domain || '')}"/>
            </div>
            <div class="zs-field">
              <label>Müşteri / Firma Adı</label>
              <input id="utEdClient" class="zs-input" placeholder="Örn: Narin Reklam A.Ş." value="${esc(ed.client_name || '')}"/>
            </div>
            <div class="zs-field">
              <label>Sunucu / Paket Grubu</label>
              <input id="utEdGroup" list="utGroupsDatalist" class="zs-input" placeholder="Örn: Plesk Sunucu 1" value="${esc(ed.server_group || 'Plesk / Müşteri')}"/>
              <datalist id="utGroupsDatalist">${groupNames.map(g => `<option value="${esc(g)}"></option>`).join('')}</datalist>
            </div>
            <div class="zs-field">
              <label>Kısa Not (İsteğe Bağlı)</label>
              <input id="utEdNotes" class="zs-input" placeholder="Hosting yenileme: Ekim..." value="${esc(ed.notes || '')}"/>
            </div>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <label class="zs-switch" style="margin:0">
              <input type="checkbox" id="utEdEnabled" ${ed.enabled !== false ? 'checked' : ''}/>
              <span>Periyodik otomatik izleme aktif</span>
            </label>
            <div style="display:flex;gap:8px">
              <button type="button" class="zs-btn" id="utCancelEditBtn2">İptal</button>
              <button type="button" class="zs-btn zs-btn-primary" id="utSaveSingleBtn">✓ Kaydet &amp; Şimdi Kontrol Et</button>
            </div>
          </div>
        </div>
      ` : ''}

      <!-- BULK 300+ IMPORT DRAWER -->
      ${S.uptime.showBulkImport ? `
        <div class="zs-card" style="border-color:rgba(56,189,248,.45);margin-bottom:18px">
          <div class="zs-card-head">
            <div>
              <h3>📥 Toplu Müşteri Sitesi Aktarımı (Tek Seferde 300–500 Domain)</h3>
              <p class="zs-card-sub">Plesk / cPanel domain listenizi her satıra 1 site gelecek şekilde yapıştırın. İsterseniz <code>domain.com | Müşteri Adı | Sunucu Adı</code> formatını da kullanabilirsiniz.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm" id="utCloseBulkBtn">✕ Kapat</button>
          </div>
          <div class="zs-grid-2" style="margin-bottom:10px">
            <div class="zs-field" style="margin:0">
              <label>Varsayılan Sunucu / Grup Etiketi (Satırda belirtilmeyenler için)</label>
              <input id="utBulkGroupInp" class="zs-input" value="Plesk / Müşteri" placeholder="Örn: Plesk Sunucu 1"/>
            </div>
            <div style="display:flex;align-items:end">
              <label class="zs-switch" style="margin:0 0 8px">
                <input type="checkbox" id="utBulkAutoScan" checked/>
                <span><strong>İçe aktardıktan hemen sonra yeni siteleri paralel tara</strong></span>
              </label>
            </div>
          </div>
          <div class="zs-field">
            <label>Domain Listesi (Her satıra 1 site)</label>
            <textarea id="utBulkLinesInp" class="zs-textarea" style="min-height:150px;font-family:Consolas,monospace;font-size:12.5px" placeholder="zeynart.com | Zeynart Merkez | Plesk Ana Sunucu&#10;ornekfirma.com.tr | Örnek Firma | Plesk Sunucu 2&#10;ikincisite.com"></textarea>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="zs-btn" id="utCloseBulkBtn2">Vazgeç</button>
            <button type="button" class="zs-btn zs-btn-primary" id="utRunBulkImportBtn">📥 Siteleri Kuleye Ekle</button>
          </div>
        </div>
      ` : ''}

      <!-- SUPER ADMIN CONFIG & PLESK CRON DRAWER -->
      ${(S.uptime.showConfig && superMode) ? `
        <div class="zs-card" style="border-color:rgba(167,139,250,.45);margin-bottom:18px">
          <div class="zs-card-head">
            <div>
              <h3>⚙️ Uptime Alarm, E-Posta Bildirimi &amp; Plesk Cron Yapılandırması</h3>
              <p class="zs-card-sub">Ziyaretçi trafiğiyle sıfır çerezli arka plan kontrolü zaten otomatiktir; 7/24 kesintisiz sunucu takibi için aşağıdaki Plesk Cron adresini de tanımlayabilirsiniz.</p>
            </div>
            <button type="button" class="zs-btn zs-btn-sm" id="utCloseCfgBtn">✕ Kapat</button>
          </div>
          <div class="zs-grid-4" style="margin-bottom:12px">
            <div class="zs-field" style="margin:0">
              <label>Kontrol Sıklığı (Dakika)</label>
              <input type="number" id="utCfgInterval" class="zs-input" min="5" max="120" value="${Number(cfg.check_interval_minutes || 15)}"/>
            </div>
            <div class="zs-field" style="margin:0">
              <label>SSL Uyarı Eşiği (Kalan Gün)</label>
              <input type="number" id="utCfgSslDays" class="zs-input" min="3" max="60" value="${sslWarnDays}"/>
            </div>
            <div class="zs-field" style="margin:0">
              <label>Yavaş Yanıt Eşiği (ms)</label>
              <input type="number" id="utCfgSlowMs" class="zs-input" min="300" max="5000" value="${slowMs}"/>
            </div>
            <div class="zs-field" style="margin:0">
              <label>Alarm E-Posta Adresi (SMTP)</label>
              <input type="email" id="utCfgAlertEmail" class="zs-input" placeholder="info@zeynart.com" value="${esc(cfg.alert_email || S.store?.site?.email || '')}"/>
            </div>
          </div>
          <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:14px">
            <label class="zs-switch" style="margin:0"><input type="checkbox" id="utCfgEnabled" ${cfg.enabled !== false ? 'checked' : ''}/><span>Arka plan otomatik Uptime &amp; SSL taraması aktif</span></label>
            <label class="zs-switch" style="margin:0"><input type="checkbox" id="utCfgEmailOn" ${cfg.alert_email_enabled ? 'checked' : ''}/><span>Site çöktüğünde veya SSL ≤7 gün kaldığında SMTP e-posta uyarısı gönder</span></label>
          </div>
          ${S.uptimeCronUrl ? `
            <div style="padding:12px 14px;border-radius:12px;background:rgba(4,10,15,.85);border:1px solid var(--border-subtle);margin-bottom:14px">
              <label style="display:block;font-size:12px;font-weight:700;color:#5eead4;margin-bottom:6px">⏱️ Plesk Zamanlanmış Görev (Cron Job — Her 10 Dakikada Bir URL Çağır)</label>
              <div class="zs-input-group">
                <input id="utCronUrlInp" class="zs-input" readonly value="${esc(S.uptimeCronUrl)}" style="font-family:Consolas,monospace;font-size:12px;color:var(--accent-bright)"/>
                <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="utCopyCronBtn">📋 Cron URL Kopyala</button>
              </div>
            </div>
          ` : ''}
          <div style="display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="zs-btn zs-btn-primary" id="utSaveCfgBtn">✓ Alarm &amp; Kule Ayarlarını Kaydet</button>
          </div>
        </div>
      ` : ''}

      <!-- MAIN MONITOR TABLE & FILTERS -->
      <div class="zs-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            ${[
              { k: 'all', lbl: `Tümü (${mons.length})` },
              { k: 'up', lbl: `🟢 Yayında (${sum.up_count || 0})` },
              { k: 'down', lbl: `🔴 Erişim Yok (${sum.down_count || 0})` },
              { k: 'ssl', lbl: `🔒 SSL Uyarısı (${sum.ssl_expiring_count || 0})` },
              { k: 'slow', lbl: `🐢 Yavaş (${sum.slow_count || 0})` },
              { k: 'pending', lbl: `⏳ Bekleyen (${sum.pending_count || 0})` }
            ].map(f => `
              <button type="button" class="zs-btn zs-btn-sm ${curFilter === f.k ? 'zs-btn-primary' : ''}" data-ut-flt="${f.k}">${f.lbl}</button>
            `).join('')}
          </div>

          <div style="display:flex;gap:8px;flex-wrap:wrap;min-width:min(100%,420px);flex:1;justify-content:flex-end">
            <select id="utGroupSel" class="zs-select" style="max-width:210px">
              <option value="all" ${curGroup === 'all' ? 'selected' : ''}>Tüm Sunucular (${groupNames.length})</option>
              ${groupNames.map(g => `<option value="${esc(g)}" ${curGroup === g ? 'selected' : ''}>${esc(g)} (${sum.groups[g]})</option>`).join('')}
            </select>
            <input id="utSearchInp" class="zs-input" style="max-width:260px" placeholder="Domain, müşteri veya not ara..." value="${esc(S.uptime.search)}"/>
          </div>
        </div>

        <div class="zs-table-wrap">
          <table class="zs-table">
            <thead>
              <tr>
                <th>Durum</th>
                <th>Domain &amp; Müşteri</th>
                <th>Sunucu / Grup</th>
                <th>Yanıt Hızı &amp; Son 16 Kontrol</th>
                <th>SSL Sertifikası</th>
                <th>Son Kontrol</th>
                <th style="text-align:right">İşlem</th>
              </tr>
            </thead>
            <tbody>
              ${filtered.length === 0 ? `
                <tr><td colspan="7" style="text-align:center;padding:34px;color:var(--text-muted)">Bu filtreyle eşleşen müşteri sitesi bulunmuyor. Sağ üstten <strong>"+ Toplu Site Aktar"</strong> ile yüzlerce sitenizi saniyeler içinde ekleyebilirsiniz.</td></tr>
              ` : filtered.map(m => {
                const st = m.status || 'pending';
                const ms = Number(m.response_ms || 0);
                const hist = Array.isArray(m.history) ? m.history.slice(-16) : [];
                const sslDays = typeof m.ssl_days_left === 'number' ? m.ssl_days_left : null;
                let sslBadgeHtml = `<span class="zs-ut-ssl-pill is-muted">⏳ Bekliyor</span>`;
                if (m.ssl_valid === false || (sslDays !== null && sslDays <= 0)) {
                  sslBadgeHtml = `<span class="zs-ut-ssl-pill is-crit" title="${esc(m.ssl_expires_at || '')}">🚨 Geçersiz / Bitti</span>`;
                } else if (sslDays !== null && sslDays <= sslWarnDays) {
                  sslBadgeHtml = `<span class="zs-ut-ssl-pill is-warn" title="Bitiş: ${esc(m.ssl_expires_at || '')} · ${esc(m.ssl_issuer || '')}">⚠️ ${sslDays} Gün Kaldı</span>`;
                } else if (sslDays !== null) {
                  sslBadgeHtml = `<span class="zs-ut-ssl-pill is-ok" title="Bitiş: ${esc(m.ssl_expires_at || '')} · ${esc(m.ssl_issuer || '')}">🔒 ${sslDays} Gün${m.ssl_issuer ? ` · ${esc(m.ssl_issuer.slice(0, 14))}` : ''}</span>`;
                }

                const msColor = st !== 'up' ? 'var(--text-dim)' : (ms >= slowMs ? '#fbbf24' : (ms <= 650 ? '#34d399' : '#67e8f9'));
                return `
                  <tr style="${m.enabled === false ? 'opacity:.55' : ''}">
                    <td style="white-space:nowrap">
                      ${st === 'up'
                        ? `<span class="zs-ut-status-pill is-up">● HTTP ${m.http_code || 200}</span>`
                        : (st === 'down'
                          ? `<span class="zs-ut-status-pill is-down" title="${esc(m.last_error || '')}">▲ ${esc(m.http_code ? ('HTTP ' + m.http_code) : 'ERİŞİM YOK')}</span>`
                          : `<span class="zs-ut-status-pill is-pending">⏳ Bekliyor</span>`)}
                    </td>
                    <td>
                      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <a href="${esc(m.url)}" target="_blank" rel="noopener" style="font-weight:800;font-size:13.5px;color:#f4fcfd;text-decoration:none">${esc(m.domain || m.url)} ↗</a>
                        ${m.enabled === false ? `<span class="zs-pill zs-pill-draft">Duraklatıldı</span>` : ''}
                      </div>
                      <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
                        ${esc(m.client_name || '')}${m.notes ? ` · <span style="color:var(--accent-bright)">${esc(m.notes)}</span>` : ''}
                        ${(st === 'down' && m.last_error) ? ` · <span style="color:#fca5a5">${esc(m.last_error)}</span>` : ''}
                      </div>
                    </td>
                    <td>
                      <span class="zs-pill zs-pill-cat">${esc(m.server_group || 'Genel')}</span>
                      ${m.server_header ? `<div style="font-size:11px;color:var(--text-dim);margin-top:3px">${esc(m.server_header)}</div>` : ''}
                    </td>
                    <td>
                      <div style="display:flex;align-items:center;gap:10px">
                        <strong style="font-size:13px;color:${msColor};min-width:58px">${ms > 0 ? `${ms} ms` : '—'}</strong>
                        <div class="zs-ut-hist-bar" title="Son ${hist.length} kontrol · Uptime: %${m.uptime_pct ?? 100}">
                          ${(hist.length ? hist : Array.from({ length: 10 }, () => ({ empty: true }))).map(h =>
                            `<i class="${h.empty ? 'is-empty' : (h.ok ? 'is-ok' : 'is-fail')}" title="${h.empty ? 'Henüz veri yok' : `HTTP ${h.code || 0} · ${h.ms || 0}ms`}"></i>`
                          ).join('')}
                        </div>
                        <span style="font-size:11.5px;color:var(--text-muted)">%${m.uptime_pct ?? 100}</span>
                      </div>
                    </td>
                    <td style="white-space:nowrap">${sslBadgeHtml}</td>
                    <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">${esc(formatRelativeCheckTime(m.last_checked_at, m.last_checked_ts))}</td>
                    <td style="text-align:right;white-space:nowrap">
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-ut-check="${esc(m.id)}" title="Şimdi HTTP ve SSL Kontrolü Yap">⚡ Test</button>
                      <button type="button" class="zs-btn zs-btn-sm" data-ut-edit="${esc(m.id)}" title="Düzenle">✏️</button>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-ut-del="${esc(m.id)}" title="İzleme Listesinden Sil">🗑</button>
                    </td>
                  </tr>
                `;
              }).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;

    // Bind events
    document.getElementById('utScanAllBtn')?.addEventListener('click', () => {
      const activeIds = mons.filter(m => m.enabled !== false).map(m => m.id);
      runUptimeBatchScan(activeIds, false);
    });
    document.getElementById('utStopScanBtn')?.addEventListener('click', () => {
      S.uptime.stopRequested = true;
    });
    document.getElementById('utOpenAddBtn')?.addEventListener('click', () => {
      syncCurrentUptimeInputsBeforeRender();
      S.uptime.showBulkImport = false;
      S.uptime.showConfig = false;
      S.uptime.editingMonitor = { id: '', url: '', client_name: '', server_group: 'Plesk / Müşteri', notes: '', enabled: true };
      renderUptimeTowerView();
      document.getElementById('utEdUrl')?.focus();
    });
    document.getElementById('utToggleBulkBtn')?.addEventListener('click', () => {
      syncCurrentUptimeInputsBeforeRender();
      S.uptime.editingMonitor = null;
      S.uptime.showConfig = false;
      S.uptime.showBulkImport = !S.uptime.showBulkImport;
      renderUptimeTowerView();
    });
    document.getElementById('utToggleCfgBtn')?.addEventListener('click', () => {
      syncCurrentUptimeInputsBeforeRender();
      S.uptime.editingMonitor = null;
      S.uptime.showBulkImport = false;
      S.uptime.showConfig = !S.uptime.showConfig;
      renderUptimeTowerView();
    });
    document.getElementById('utRefreshListBtn')?.addEventListener('click', async () => {
      try {
        const res = await api('uptime_list', {});
        S.uptime.monitors = res.monitors || [];
        S.uptime.summary = res.uptime_summary || null;
        if (res.uptime_tower) S.store.uptime_tower = res.uptime_tower;
        renderUptimeTowerView();
        toast('İzleme listesi güncellendi.');
      } catch (err) {
        toast(err.message, true);
      }
    });

    ['utCancelEditBtn', 'utCancelEditBtn2'].forEach(id => {
      document.getElementById(id)?.addEventListener('click', () => {
        S.uptime.editingMonitor = null;
        renderUptimeTowerView();
      });
    });

    document.getElementById('utSaveSingleBtn')?.addEventListener('click', async function () {
      const btn = this;
      const payload = {
        id: ed?.id || '',
        url: document.getElementById('utEdUrl')?.value.trim() || '',
        client_name: document.getElementById('utEdClient')?.value.trim() || '',
        server_group: document.getElementById('utEdGroup')?.value.trim() || 'Plesk / Müşteri',
        notes: document.getElementById('utEdNotes')?.value.trim() || '',
        enabled: Boolean(document.getElementById('utEdEnabled')?.checked)
      };
      btn.disabled = true;
      btn.textContent = '⏳ Kontrol Ediliyor & Kaydediliyor...';
      try {
        const res = await api('uptime_save_monitor', { monitor: payload, probe_now: true });
        S.uptime.monitors = res.monitors || [];
        S.uptime.summary = res.uptime_summary || null;
        S.uptime.editingMonitor = null;
        toast(res.message);
        renderUptimeTowerView();
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
        btn.textContent = '✓ Kaydet & Şimdi Kontrol Et';
      }
    });

    ['utCloseBulkBtn', 'utCloseBulkBtn2'].forEach(id => {
      document.getElementById(id)?.addEventListener('click', () => {
        S.uptime.showBulkImport = false;
        renderUptimeTowerView();
      });
    });

    document.getElementById('utRunBulkImportBtn')?.addEventListener('click', async function () {
      const lines = document.getElementById('utBulkLinesInp')?.value || '';
      const grp = document.getElementById('utBulkGroupInp')?.value.trim() || 'Plesk / Müşteri';
      const autoScan = Boolean(document.getElementById('utBulkAutoScan')?.checked);
      const btn = this;
      btn.disabled = true;
      btn.textContent = '⏳ Domainler Ekleniyor...';
      try {
        const res = await api('uptime_bulk_import', { lines, server_group: grp });
        S.uptime.monitors = res.monitors || [];
        S.uptime.summary = res.uptime_summary || null;
        S.uptime.showBulkImport = false;
        toast(res.message);
        renderUptimeTowerView();
        if (autoScan && Array.isArray(res.added_ids) && res.added_ids.length > 0) {
          runUptimeBatchScan(res.added_ids, true);
        }
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
        btn.textContent = '📥 Siteleri Kuleye Ekle';
      }
    });

    document.getElementById('utCloseCfgBtn')?.addEventListener('click', () => {
      S.uptime.showConfig = false;
      renderUptimeTowerView();
    });
    document.getElementById('utCopyCronBtn')?.addEventListener('click', () => {
      const v = document.getElementById('utCronUrlInp')?.value || S.uptimeCronUrl;
      if (v) {
        navigator.clipboard?.writeText(v);
        toast('Plesk Cron URL panoya kopyalandı.');
      }
    });
    document.getElementById('utSaveCfgBtn')?.addEventListener('click', async () => {
      try {
        const res = await api('uptime_save_config', {
          uptime_tower: {
            enabled: Boolean(document.getElementById('utCfgEnabled')?.checked),
            check_interval_minutes: Number(document.getElementById('utCfgInterval')?.value || 15),
            ssl_warn_days: Number(document.getElementById('utCfgSslDays')?.value || 14),
            slow_ms_threshold: Number(document.getElementById('utCfgSlowMs')?.value || 1800),
            alert_email_enabled: Boolean(document.getElementById('utCfgEmailOn')?.checked),
            alert_email: document.getElementById('utCfgAlertEmail')?.value.trim() || ''
          }
        });
        if (res.uptime_tower) S.store.uptime_tower = res.uptime_tower;
        if (res.uptime_summary) S.uptime.summary = res.uptime_summary;
        if (res.cron_url) S.uptimeCronUrl = res.cron_url;
        toast(res.message);
        renderUptimeTowerView();
      } catch (err) {
        toast(err.message, true);
      }
    });

    ROOT.querySelectorAll('[data-ut-flt]').forEach(btn => {
      btn.onclick = () => {
        syncCurrentUptimeInputsBeforeRender();
        S.uptime.filter = btn.getAttribute('data-ut-flt') || 'all';
        renderUptimeTowerView();
      };
    });
    document.getElementById('utGroupSel')?.addEventListener('change', e => {
      S.uptime.group = e.target.value;
      renderUptimeTowerView();
    });
    let utSearchDeb = null;
    document.getElementById('utSearchInp')?.addEventListener('input', e => {
      S.uptime.search = e.target.value;
      clearTimeout(utSearchDeb);
      utSearchDeb = setTimeout(() => {
        const pos = e.target.selectionStart || S.uptime.search.length;
        renderUptimeTowerView();
        const nextInp = document.getElementById('utSearchInp');
        if (nextInp) {
          nextInp.focus();
          try { nextInp.setSelectionRange(pos, pos); } catch (_) {}
        }
      }, 240);
    });

    ROOT.querySelectorAll('[data-ut-check]').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.getAttribute('data-ut-check');
        if (!id) return;
        btn.disabled = true;
        btn.textContent = '⏳...';
        try {
          const res = await api('uptime_check_batch', { id, force_ssl: true });
          S.uptime.monitors = res.monitors || [];
          S.uptime.summary = res.uptime_summary || null;
          toast('Site HTTP hızı ve SSL sertifikası güncellendi.');
          renderUptimeTowerView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
          btn.textContent = '⚡ Test';
        }
      };
    });

    ROOT.querySelectorAll('[data-ut-edit]').forEach(btn => {
      btn.onclick = () => {
        const id = btn.getAttribute('data-ut-edit');
        const found = mons.find(x => x.id === id);
        if (found) {
          S.uptime.showBulkImport = false;
          S.uptime.showConfig = false;
          S.uptime.editingMonitor = Object.assign({}, found);
          renderUptimeTowerView();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      };
    });

    ROOT.querySelectorAll('[data-ut-del]').forEach(btn => {
      btn.onclick = async () => {
        const id = btn.getAttribute('data-ut-del');
        const found = mons.find(x => x.id === id);
        if (!found || !confirm(`"${found.domain || found.url}" sitesini izleme kulesinden kaldırmak istiyor musunuz?`)) return;
        try {
          const res = await api('uptime_delete_monitor', { id });
          S.uptime.monitors = res.monitors || [];
          S.uptime.summary = res.uptime_summary || null;
          toast(res.message);
          renderUptimeTowerView();
        } catch (err) {
          toast(err.message, true);
        }
      };
    });
  }

  /* ---- MODULE 1: LITESPEED LSCACHE ON/OFF, CACHED PAGES INSPECTOR, SYSTEM HEALTH & ORPHAN MEDIA CLEANER ---- */
  function renderPerformanceStudioView() {
    syncAdminUrl('replace');
    if (!isSuperAdmin()) {
      navigateTo('dashboard');
      return;
    }
    const curTab = S.tabs?.performance === 'unused_files' ? 'unused_files' : 'lscache';
    S.tabs.performance = curTab;

    const sh = S.systemHealth || {};
    const ls = sh.lscache || {};
    const lsCfg = S.store?.lscache || { enabled: ls.enabled !== false, ttl: ls.ttl || 3600 };
    const lsEnabled = lsCfg.enabled !== false;
    const ttlMin = Math.max(1, Math.round(Number(lsCfg.ttl || 3600) / 60));
    const ttlLbl = ttlMin >= 60 ? (Math.round(ttlMin / 60) + ' Saat') : (ttlMin + ' Dakika');
    const cachedCount = Number(ls.cached_count || 0);
    const totalUrls = Number(ls.total_urls || (Array.isArray(ls.items) ? ls.items.length : 0));
    const unusedCnt = Number(
      S.unusedFilesReport?.unused_count ?? S.unusedFilesSummary?.unused_count ?? 0
    );
    const turboModeLabel = S.turboEngine?.mode_label || sh.turbo_engine_mode || (sh.sqlite_wal_available ? 'SQLite WAL + Mikro-Önbellek' : 'Ultra-Turbo Mikro-Önbellek');

    ROOT.innerHTML = `
      <div class="zs-section-head">
        <div>
          <span class="zs-badge-pill">${curTab === 'unused_files' ? '🧹 STÜDYO v4.0 BAĞIMLILIK DENETÇİSİ &amp; DOSYA TEMİZLEYİCİ' : '⚡ LITESPEED WEB SERVER · LSCACHE &amp; ULTRA-TURBO MOTOR v4.0'}</span>
          <h2 style="margin:6px 0 4px">${curTab === 'unused_files' ? 'Kullanılmayan &amp; Bağlantısız Dosyalar Radarı' : 'LiteSpeed Önbellek Kapat/Aç &amp; Önbellekteki Sayfalar (HIT / MISS)'}</h2>
          <p class="zs-card-sub">${curTab === 'unused_files'
            ? 'Aktif <strong>Stüdyo v4.0</strong> giriş noktalarından (<code>index.php</code>, <code>admin/index.php</code>, <code>admin/api.php</code>) hiçbir <code>require</code>, rota veya varlık bağlantısı bulunmayan eski sürüm dosyalarını, geçici kalıntıları ve yetim JSON kayıtlarını güvenle temizleyin.'
            : 'LiteSpeed önbelleğini dilediğiniz an tek tıkla <strong>Açık / Kapalı</strong> yapabilir, sitedeki <strong>hangi sayfaların önbelleğe alındığını (HIT / MISS)</strong> canlı görebilir, tekil sayfayı silebilir veya tüm sayfaları tek tıkla ısıtabilirsiniz.'}</p>
        </div>
      </div>

      <div class="zs-tabs" style="margin-bottom:18px" role="tablist" aria-label="Hız ve Dosya Temizliği Sekmeleri">
        <button type="button" class="zs-tab ${curTab === 'lscache' ? 'active' : ''}" data-perf-tab="lscache">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
          <span>LiteSpeed LSCache &amp; Sistem Sağlığı</span>
        </button>
        <button type="button" class="zs-tab ${curTab === 'unused_files' ? 'active' : ''}" data-perf-tab="unused_files">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          <span>Kullanılmayan &amp; Bağlantısız Dosyalar${unusedCnt > 0 ? ` (${unusedCnt})` : ''}</span>
        </button>
      </div>

      ${curTab === 'lscache' ? `
        <div class="zs-crm-kpi-strip" style="margin-bottom:18px">
          <div class="zs-crm-kpi">
            <small>LiteSpeed LSCache Durumu</small>
            <strong id="prfKpiLsState" style="color:${lsEnabled ? '#a7f3d0' : '#fde68a'}">${lsEnabled ? 'LSCache Açık' : 'LSCache Kapalı'}</strong>
            <span id="prfKpiLsSub">Önbellek Süresi (TTL): ${esc(ttlLbl)}</span>
          </div>
          <div class="zs-crm-kpi">
            <small>Önbellekteki Sayfalar (HIT)</small>
            <strong id="prfKpiLsHits" style="color:#5eead4">${cachedCount} / ${totalUrls}</strong>
            <span id="prfKpiLsHitPct">İsabet Oranı: %${Number(ls.hit_ratio_pct || 0)}</span>
          </div>
          <div class="zs-crm-kpi">
            <small>KVKK &amp; Çerez Mimarisi</small>
            <strong style="color:#a7f3d0">0 Set-Cookie</strong>
            <span>%100 LiteSpeed RAM Uyumlu</span>
          </div>
          <div class="zs-crm-kpi">
            <small>Ultra-Turbo Boot Motoru</small>
            <strong style="color:#5eead4">${S.bootMs ? `${S.bootMs} ms` : 'Aktif'}</strong>
            <span>${esc(turboModeLabel)}</span>
          </div>
        </div>

        <div id="prfSysDiagHost"></div>
      ` : `
        <div id="prfUnusedFilesHost"></div>
      `}
    `;

    ROOT.querySelectorAll('[data-perf-tab]').forEach(tabBtn => {
      tabBtn.addEventListener('click', () => {
        const nextTab = tabBtn.getAttribute('data-perf-tab') === 'unused_files' ? 'unused_files' : 'lscache';
        if (S.tabs.performance === nextTab) return;
        S.tabs.performance = nextTab;
        markHistoryPush();
        renderPerformanceStudioView();
      });
    });

    if (curTab === 'unused_files') {
      mountUnusedFilesCleanerTab(document.getElementById('prfUnusedFilesHost'));
    } else {
      mountSystemPerformanceTab(document.getElementById('prfSysDiagHost'));
    }
  }

  function syncTopPerformanceLsKpi() {
    const sh = S.systemHealth || {};
    const ls = sh.lscache || {};
    const cfg = S.store?.lscache || { enabled: ls.enabled !== false, ttl: ls.ttl || 3600 };
    const enabled = cfg.enabled !== false;
    const ttlMin = Math.max(1, Math.round(Number(cfg.ttl || 3600) / 60));
    const ttlLbl = ttlMin >= 60 ? (Math.round(ttlMin / 60) + ' Saat') : (ttlMin + ' Dakika');
    const cachedCount = Number(ls.cached_count || 0);
    const totalUrls = Number(ls.total_urls || (Array.isArray(ls.items) ? ls.items.length : 0));

    const stEl = document.getElementById('prfKpiLsState');
    const subEl = document.getElementById('prfKpiLsSub');
    const hitsEl = document.getElementById('prfKpiLsHits');
    const pctEl = document.getElementById('prfKpiLsHitPct');
    if (stEl) {
      stEl.textContent = enabled ? 'LSCache Açık' : 'LSCache Kapalı';
      stEl.style.color = enabled ? '#a7f3d0' : '#fde68a';
    }
    if (subEl) {
      subEl.textContent = `Önbellek Süresi (TTL): ${ttlLbl}`;
    }
    if (hitsEl) {
      hitsEl.textContent = `${cachedCount} / ${totalUrls}`;
    }
    if (pctEl) {
      pctEl.textContent = `İsabet Oranı: %${Number(ls.hit_ratio_pct || 0)}`;
    }
    const topBtn = document.getElementById('zsTopbarPurgeBtn');
    if (topBtn && !topBtn.disabled) {
      topBtn.innerHTML = enabled ? '⚡ Önbellek' : '⏸ LSCache Kapalı';
    }
  }

  function formatRemainingTtlTr(sec) {
    const s = Math.max(0, Number(sec || 0));
    if (s <= 0) return 'Süresi doldu';
    if (s < 60) return s + ' sn kaldı';
    const mins = Math.round(s / 60);
    if (mins < 60) return mins + ' dk kaldı';
    const hrs = Math.floor(mins / 60);
    const remM = mins % 60;
    return remM > 0 ? `${hrs} sa ${remM} dk kaldı` : `${hrs} saat kaldı`;
  }

  function mountSystemPerformanceTab(host) {
    if (!host) return;
    const sh = S.systemHealth || {};
    const ext = sh.extensions || {};
    const wr = sh.writable || {};
    const scan = S.orphanScan;
    const ls = sh.lscache || {};
    const lsCfg = S.store?.lscache || { enabled: ls.enabled !== false, ttl: ls.ttl || 3600 };
    const lsEnabled = lsCfg.enabled !== false;
    const lsMaintBypass = Boolean(ls.maintenance_bypass || S.store?.maintenance?.enabled);
    const lsTtl = Number(lsCfg.ttl || ls.ttl || 3600);
    const lsItems = Array.isArray(ls.items) ? ls.items : [];
    const cachedCount = Number(ls.cached_count || 0);
    const totalUrls = Number(ls.total_urls || lsItems.length || 0);
    const uncachedCount = Math.max(0, totalUrls - cachedCount);
    const curFilter = S.lsFilter || 'all';
    const curQuery = S.lsQuery || '';

    const ttlOptions = [
      [900, '15 Dakika (900 sn)'],
      [1800, '30 Dakika (1800 sn)'],
      [3600, '1 Saat — Önerilen (3600 sn)'],
      [14400, '4 Saat (14400 sn)'],
      [86400, '24 Saat (86400 sn)'],
    ];
    if (!ttlOptions.some(([v]) => v === lsTtl)) {
      ttlOptions.unshift([lsTtl, `${lsTtl} Saniye (Özel)`]);
    }

    syncTopPerformanceLsKpi();

    host.innerHTML = `
      <div class="zs-grid-2" style="align-items:start;margin-bottom:16px">
        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <span class="zs-badge-pill">LITESPEED WEB SERVER &amp; PHP OPCACHE</span>
              <h3 style="margin-top:6px">⚡ LiteSpeed LSCache Kapat / Aç &amp; Hız Motoru</h3>
              <p class="zs-card-sub">LiteSpeed önbelleğini dilediğiniz an tek tıkla kapatıp açabilir, önbellek süresini (TTL) değiştirebilir veya <code>X-LiteSpeed-Purge: *</code> + PHP OPcache belleğini anında sıfırlayabilirsiniz.</p>
            </div>
            <span class="zs-sec-badge ${lsEnabled && !lsMaintBypass ? 'ok' : 'warn'}">${!lsEnabled ? '⏸ LSCache Kapalı' : (lsMaintBypass ? '🛠️ Bakım Bypass' : (sh.is_litespeed ? '⚡ LSCache Aktif' : '⚡ Önbellek Açık'))}</span>
          </div>

          <div class="zs-lscache-control-box ${!lsEnabled ? 'is-off' : ''}">
            <div class="zs-lscache-toggle-main">
              <label class="zs-switch" style="margin:0;cursor:pointer">
                <input type="checkbox" id="lsEnabledSwitch" ${lsEnabled ? 'checked' : ''}/>
                <span><strong style="color:${lsEnabled ? '#a7f3d0' : '#fde68a'}">${lsEnabled ? 'LiteSpeed Önbellek: AÇIK' : 'LiteSpeed Önbellek: KAPALI'}</strong></span>
              </label>
            </div>
            <div class="zs-lscache-ttl-wrap">
              <label for="lsTtlSelect">Önbellek Süresi:</label>
              <select id="lsTtlSelect" class="zs-input" style="width:auto;min-width:190px;padding:6px 10px;height:34px" ${!lsEnabled ? 'disabled' : ''}>
                ${ttlOptions.map(([val, lbl]) => `<option value="${val}" ${val === lsTtl ? 'selected' : ''}>${esc(lbl)}</option>`).join('')}
              </select>
            </div>
          </div>

          ${!lsEnabled ? `
            <div class="zs-glass-empty-note" style="margin-bottom:14px;padding:10px 12px;border-color:rgba(251,191,36,.38);color:#fde68a;text-align:left">
              ⏸ <strong>LiteSpeed LSCache şu an devre dışı:</strong> Tüm ziyaretçilere sayfalar <code>X-LiteSpeed-Cache-Control: no-cache</code> ile anlık üretilerek sunuluyor. Canlı yayında maksimum hız için işiniz bittiğinde tekrar açabilirsiniz.
            </div>
          ` : (lsMaintBypass ? `
            <div class="zs-glass-empty-note" style="margin-bottom:14px;padding:10px 12px;border-color:rgba(251,191,36,.38);color:#fde68a;text-align:left">
              🛠️ <strong>Bakım Modu aktif:</strong> Bakım ekranı kapanana kadar LiteSpeed önbellekleme geçici olarak bypass edilmektedir.
            </div>
          ` : '')}

          <div class="zs-sys-kv-grid" style="margin-bottom:14px">
            <div class="zs-sys-kv"><span>Web Sunucusu</span><strong>${esc(sh.server_software || 'LiteSpeed / PHP')}</strong></div>
            <div class="zs-sys-kv"><span>PHP &amp; OPcache</span><strong>PHP ${esc(sh.php_version || '8.x')} · ${sh.opcache_enabled ? (sh.opcache_hit_rate !== null ? '%' + sh.opcache_hit_rate + ' OPcache' : 'OPcache Aktif') : 'Standart'}</strong></div>
            <div class="zs-sys-kv"><span>Önbellekteki Sayfa Oranı</span><strong style="color:${cachedCount > 0 ? '#a7f3d0' : '#5eead4'}">${cachedCount} / ${totalUrls} Sayfa (%${Number(ls.hit_ratio_pct || 0)})</strong></div>
            <div class="zs-sys-kv"><span>Turbo Admin &amp; Son Temizlik</span><strong>${S.bootMs ? `Boot ${S.bootMs} ms · ` : ''}${esc(sh.last_purge || 'Henüz temizlenmedi')}</strong></div>
          </div>

          <label class="zs-switch" style="margin-bottom:14px">
            <input type="checkbox" id="perfPurgeOgChk"/>
            <span>Geçici oluşturulmuş sosyal paylaşım (<code>/uploads/og/</code>) kart önbelleğini de tazele</span>
          </label>

          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-primary" id="perfPurgeNowBtn">⚡ Tüm LiteSpeed &amp; OPcache Önbelleğini Temizle</button>
            <button type="button" class="zs-btn" id="perfRefreshHealthBtn">↻ Sistem Durumunu Yenile</button>
          </div>
        </div>

        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <span class="zs-badge-pill">SUNUCU DONANIM, DİSK &amp; EKLENTİ DENETİMİ</span>
              <h3 style="margin-top:6px">🩺 Canlı Sistem Sağlığı Karnesi</h3>
            </div>
          </div>

          <div class="zs-sys-kv-grid" style="margin-bottom:12px">
            <div class="zs-sys-kv"><span>Sunucu Boş Disk</span><strong>${sh.disk_free_gb !== null && sh.disk_free_gb !== undefined ? `${sh.disk_free_gb} GB boş / ${sh.disk_total_gb} GB` : 'Paylaşımlı Kota'}</strong></div>
            <div class="zs-sys-kv"><span>Medya (<code>/uploads/</code>)</span><strong>${Number(sh.uploads_size_mb || 0)} MB (${Number(sh.uploads_files || 0)} dosya)</strong></div>
            <div class="zs-sys-kv"><span>Veri &amp; Blog (<code>/data/</code>)</span><strong>${Number(sh.data_size_mb || 0)} MB (${Number(sh.data_files || 0)} dosya)</strong></div>
            <div class="zs-sys-kv"><span>PHP Bellek &amp; Yükleme</span><strong>${esc(sh.memory_limit || '-')} · Max ${esc(sh.upload_max_filesize || '-')}</strong></div>
          </div>

          <div style="margin-bottom:10px">
            <strong style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px">Kritik PHP Modülleri:</strong>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              ${[
                ['gd_webp', 'GD WebP Dönüştürücü'],
                ['gd_freetype', 'GD TrueType Filigran'],
                ['zip', 'ZipArchive Yedekleme'],
                ['curl_multi', 'Paralel cURL Motoru'],
                ['openssl', 'OpenSSL / TLS'],
                ['mbstring', 'UTF-8 Türkçe Dizge']
              ].map(([k, lbl]) => `<span class="zs-sec-badge ${ext[k] ? 'ok' : 'warn'}">${ext[k] ? '✓' : '!'} ${lbl}</span>`).join('')}
            </div>
          </div>

          <div>
            <strong style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px">Dizin Yazma İzinleri:</strong>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              ${Object.keys(wr).map(dir => `<span class="zs-sec-badge ${wr[dir] ? 'ok' : 'danger'}">${wr[dir] ? '✓ Yazılabilir:' : '✕ Kilitli:'} ${esc(dir)}</span>`).join('')}
            </div>
          </div>
        </div>
      </div>

      <div class="zs-card" style="margin-bottom:16px">
        <div class="zs-card-head" style="margin-bottom:12px;align-items:flex-start;gap:12px">
          <div style="flex:1;min-width:0">
            <span class="zs-badge-pill">CANLI LITESPEED ÖNBELLEK HARİTASI (HIT / MISS DENETÇİSİ)</span>
            <h3 style="margin-top:6px">📋 Hangi Sayfalar Cache Alındı? — <span style="color:#5eead4">${cachedCount} / ${totalUrls} Sayfa Önbellekte</span></h3>
            <p class="zs-card-sub">
              Sitedeki tüm genel URL adreslerinin LiteSpeed RAM önbelleğinde (<code>HIT</code>) olup olmadığını aşağıdan görebilir, tek bir sayfanın önbelleğini silebilir veya tüm sayfaları tek tıkla önbelleğe alabilirsiniz.${ls.last_probe_at ? ` · <strong>Son Denetim:</strong> ${esc(ls.last_probe_at)}` : ''}
            </p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
            <button type="button" class="zs-btn zs-btn-ai" id="lsScanAllBtn" ${S.lsScanning ? 'disabled' : ''}>${S.lsScanning ? '⏳ Denetleniyor...' : '🔍 Canlı LiteSpeed Taraması Yap'}</button>
            <button type="button" class="zs-btn zs-btn-primary" id="lsWarmAllBtn" ${(!lsEnabled || lsMaintBypass || S.lsWarming) ? 'disabled' : ''}>${S.lsWarming ? '⏳ Önbelleğe Alınıyor...' : '🔥 Tüm Sayfaları Önbelleğe Al'}</button>
          </div>
        </div>

        <div class="zs-lscache-toolbar">
          <div class="zs-lscache-filters" id="lsFilterBar">
            ${[
              ['all', `Tümü (${totalUrls})`],
              ['cached', `⚡ Önbellekte / HIT (${cachedCount})`],
              ['uncached', `○ Önbellekte Değil (${uncachedCount})`],
              ['core', 'Ana & Özel Sayfalar'],
              ['services', 'Hizmetler & Projeler'],
              ['blog', 'Blog & Kategoriler'],
              ['legal', 'Yasal'],
              ['i18n', 'Çoklu Dil (EN/DE/RU)'],
            ].map(([fKey, fLbl]) => `<button type="button" class="zs-ls-filter-btn ${curFilter === fKey ? 'active' : ''}" data-ls-filter="${fKey}">${esc(fLbl)}</button>`).join('')}
          </div>
          <div style="min-width:240px;flex:0 1 310px">
            <input type="search" class="zs-input" id="lsUrlSearchInput" placeholder="Sayfa başlığı veya URL ara (/hizmetler/, /blog/...)" value="${esc(curQuery)}" style="height:34px;padding:6px 11px"/>
          </div>
        </div>

        <div class="zs-lscache-table-wrap">
          <table class="zs-lscache-table">
            <thead>
              <tr>
                <th>Sayfa Başlığı &amp; Bölüm</th>
                <th>URL Adresi</th>
                <th>LiteSpeed Durumu</th>
                <th>Önbellek Zamanı &amp; Kalan Süre</th>
                <th style="text-align:right">İşlem</th>
              </tr>
            </thead>
            <tbody id="lsPagesTableBody"></tbody>
          </table>
        </div>
      </div>

      <div class="zs-card" style="margin-bottom:0">
        <div class="zs-card-head" style="margin-bottom:12px">
          <div>
            <span class="zs-badge-pill">DİSK OPTİMİZASYONU &amp; MEDYA TEMİZLİĞİ</span>
            <h3 style="margin-top:6px">🧹 Yetim (Kullanılmayan) Görsel Tarayıcı — <code>/uploads/</code></h3>
            <p class="zs-card-sub">Tüm sayfalar, hizmetler, projeler, blog yazıları, müşteri logoları, yazar profilleri ve teklifler taranarak hiçbir yerde kullanılmayan yüklenmiş görseller güvenle tespit edilir (çekirdek tema görsellerine asla dokunulmaz).</p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="zs-btn zs-btn-ai" id="orphScanBtn">${S.orphanScanning ? '⏳ Taranıyor...' : '🔍 Kullanılmayan Görselleri Tara'}</button>
            ${scan && scan.orphans && scan.orphans.length > 0 ? `<button type="button" class="zs-btn zs-btn-danger" id="orphDeleteSelectedBtn">🗑 Seçili Görselleri Sil</button>` : ''}
          </div>
        </div>

        ${!scan ? `
          <div class="zs-glass-empty-note">Tarama başlatmak için sağ üstteki <strong>🔍 Kullanılmayan Görselleri Tara</strong> butonuna tıklayın.</div>
        ` : (scan.orphans.length === 0 ? `
          <div class="zs-glass-empty-note" style="border-color:rgba(52,211,153,.35);color:#a7f3d0">
            🎉 Tertemiz! Taranan <strong>${Number(scan.scanned_uploads || 0)}</strong> medya dosyasının tamamı sitede aktif olarak kullanılıyor; boşa yer kaplayan yetim dosya bulunmadı.
          </div>
        ` : `
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:12.5px;color:var(--text-muted)">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" id="orphSelectAllChk" checked/>
              <span>Tümünü Seç (Toplam <strong>${Number(scan.total_orphan_count)}</strong> kullanılmayan dosya · <strong>${Number(scan.total_orphan_mb)} MB</strong> kazanılabilir alan)</span>
            </label>
          </div>
          <div class="zs-orphan-grid">
            ${scan.orphans.map(o => `
              <label class="zs-orphan-card">
                <input type="checkbox" class="orph-item-chk" value="${esc(o.rel)}" checked/>
                <img src="${esc(resolvePreviewUrl(o.url))}" alt="" loading="lazy"/>
                <div class="zs-orphan-meta">
                  <strong title="${esc(o.rel)}">${esc(o.rel)}</strong>
                  <span>${Number(o.size_kb)} KB · ${esc(o.modified)}</span>
                </div>
              </label>
            `).join('')}
          </div>
        `)}
      </div>
    `;

    const renderLsRows = () => {
      const tbody = host.querySelector('#lsPagesTableBody');
      if (!tbody) return;
      const q = (S.lsQuery || '').trim().toLocaleLowerCase('tr-TR');
      const f = S.lsFilter || 'all';
      const filtered = lsItems.filter(it => {
        if (f === 'cached' && !it.cached) return false;
        if (f === 'uncached' && it.cached) return false;
        if (['core', 'services', 'blog', 'legal', 'i18n'].includes(f) && it.group_key !== f) return false;
        if (q) {
          const hay = ((it.title || '') + ' ' + (it.path || '') + ' ' + (it.group_label || '')).toLocaleLowerCase('tr-TR');
          if (!hay.includes(q)) return false;
        }
        return true;
      });

      if (!filtered.length) {
        if (ls.items_deferred && !lsItems.length) {
          tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">⏳ LiteSpeed URL önbellek haritası yükleniyor...</td></tr>`;
          return;
        }
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">Seçili filtreye uygun sayfa bulunamadı.</td></tr>`;
        return;
      }

      const srcLabelMap = {
        lscache_hit: 'LiteSpeed RAM',
        warm: 'Önbellek Isıtma',
        render: 'Ziyaretçi İsteği',
      };

      tbody.innerHTML = filtered.map(it => {
        let badgeHtml = '';
        if (!lsEnabled || lsMaintBypass) {
          badgeHtml = `<span class="zs-ls-state-pill off">⏸ LSCache Kapalı</span>`;
        } else if (it.cached) {
          badgeHtml = `<span class="zs-ls-state-pill hit">⚡ ÖNBELLEKTE (HIT)</span>`;
        } else {
          badgeHtml = `<span class="zs-ls-state-pill miss">○ Önbellekte Değil</span>`;
        }

        const timeInfo = it.cached
          ? `<strong style="color:#f8fafc">${esc(it.cached_at || '-')}</strong> <span style="color:var(--text-muted)">· ${esc(formatRemainingTtlTr(it.remaining_sec))}</span>${it.source ? `<span class="zs-ls-grp-pill">${esc(srcLabelMap[it.source] || it.source)}</span>` : ''}`
          : `<span style="color:var(--text-muted)">—</span>`;

        let actionHtml = '';
        if (!lsEnabled || lsMaintBypass) {
          actionHtml = `<span style="font-size:11.5px;color:var(--text-muted)">Devre Dışı</span>`;
        } else if (it.cached) {
          actionHtml = `<button type="button" class="zs-btn zs-btn-sm" data-ls-purge-uri="${esc(it.path)}" title="Yalnızca bu sayfanın LiteSpeed önbelleğini temizle">🗑 Önbellekten Sil</button>`;
        } else {
          actionHtml = `<button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-ls-warm-uri="${esc(it.path)}" title="Bu sayfayı şimdi LiteSpeed önbelleğine al">🔥 Önbelleğe Al</button>`;
        }

        return `
          <tr>
            <td>
              <strong style="color:#f8fafc">${esc(it.title || it.path)}</strong>
              <span class="zs-ls-grp-pill">${esc(it.group_label || '')}</span>
            </td>
            <td>
              <a class="zs-ls-url-link" href="${esc(resolvePreviewUrl(it.url || it.path))}" target="_blank" rel="noopener">${esc(it.path)} ↗</a>
            </td>
            <td>${badgeHtml}</td>
            <td>${timeInfo}</td>
            <td style="text-align:right;white-space:nowrap">${actionHtml}</td>
          </tr>
        `;
      }).join('');

      tbody.querySelectorAll('[data-ls-purge-uri]').forEach(btn => {
        btn.addEventListener('click', async function () {
          const uri = this.getAttribute('data-ls-purge-uri');
          if (!uri) return;
          S._lsMutationSeq = (S._lsMutationSeq || 0) + 1;
          this.disabled = true;
          this.textContent = '⏳...';
          try {
            const res = await api('lscache_purge_url', { uri });
            if (res.system_health) S.systemHealth = res.system_health;
            toast(res.message || 'Sayfa önbelleği temizlendi.');
            mountSystemPerformanceTab(host);
          } catch (err) {
            toast(err.message, true);
            this.disabled = false;
            this.textContent = '🗑 Önbellekten Sil';
          }
        });
      });

      tbody.querySelectorAll('[data-ls-warm-uri]').forEach(btn => {
        btn.addEventListener('click', async function () {
          const uri = this.getAttribute('data-ls-warm-uri');
          if (!uri) return;
          S._lsMutationSeq = (S._lsMutationSeq || 0) + 1;
          this.disabled = true;
          this.textContent = '⏳...';
          try {
            const res = await api('lscache_warm', { uri });
            if (res.system_health) S.systemHealth = res.system_health;
            toast(res.message || 'Sayfa önbelleğe alındı.');
            mountSystemPerformanceTab(host);
          } catch (err) {
            toast(err.message, true);
            this.disabled = false;
            this.textContent = '🔥 Önbelleğe Al';
          }
        });
      });
    };

    renderLsRows();

    const saveLsSettings = async (nextEnabled, nextTtl) => {
      S._lsMutationSeq = (S._lsMutationSeq || 0) + 1;
      try {
        const res = await api('lscache_save_settings', { enabled: nextEnabled, ttl: nextTtl });
        if (S.store) S.store.lscache = res.lscache || { enabled: nextEnabled, ttl: nextTtl };
        if (res.system_health) S.systemHealth = res.system_health;
        toast(res.message || 'LiteSpeed LSCache ayarları güncellendi.');
        mountSystemPerformanceTab(host);
      } catch (err) {
        toast(err.message, true);
        mountSystemPerformanceTab(host);
      }
    };

    host.querySelector('#lsEnabledSwitch')?.addEventListener('change', function () {
      const nextEnabled = Boolean(this.checked);
      const nextTtl = Number(host.querySelector('#lsTtlSelect')?.value || lsTtl || 3600);
      this.disabled = true;
      saveLsSettings(nextEnabled, nextTtl);
    });

    host.querySelector('#lsTtlSelect')?.addEventListener('change', function () {
      const nextTtl = Number(this.value || 3600);
      this.disabled = true;
      saveLsSettings(lsEnabled, nextTtl);
    });

    host.querySelectorAll('[data-ls-filter]').forEach(btn => {
      btn.addEventListener('click', () => {
        S.lsFilter = btn.getAttribute('data-ls-filter') || 'all';
        host.querySelectorAll('[data-ls-filter]').forEach(b => b.classList.toggle('active', b === btn));
        renderLsRows();
      });
    });

    host.querySelector('#lsUrlSearchInput')?.addEventListener('input', function () {
      S.lsQuery = this.value || '';
      renderLsRows();
    });

    host.querySelector('#lsScanAllBtn')?.addEventListener('click', async () => {
      if (S.lsScanning) return;
      S._lsMutationSeq = (S._lsMutationSeq || 0) + 1;
      S.lsScanning = true;
      mountSystemPerformanceTab(host);
      try {
        const res = await api('lscache_scan', {});
        if (res.system_health) S.systemHealth = res.system_health;
        toast(res.message || 'LiteSpeed önbellek taraması tamamlandı.');
      } catch (err) {
        toast(err.message, true);
      } finally {
        S.lsScanning = false;
        mountSystemPerformanceTab(host);
      }
    });

    host.querySelector('#lsWarmAllBtn')?.addEventListener('click', async () => {
      if (S.lsWarming) return;
      S._lsMutationSeq = (S._lsMutationSeq || 0) + 1;
      S.lsWarming = true;
      mountSystemPerformanceTab(host);
      try {
        const res = await api('lscache_warm', {});
        if (res.system_health) S.systemHealth = res.system_health;
        toast(res.message || 'Tüm sayfalar önbelleğe alındı.');
      } catch (err) {
        toast(err.message, true);
      } finally {
        S.lsWarming = false;
        mountSystemPerformanceTab(host);
      }
    });

    if (!S._lscacheAutoScannedOnce && lsEnabled && !lsMaintBypass) {
      S._lscacheAutoScannedOnce = true;
      const autoSeq = S._lsMutationSeq || 0;
      api('lscache_scan', {}).then(res => {
        if ((S._lsMutationSeq || 0) !== autoSeq) return;
        if (res && res.system_health) {
          S.systemHealth = res.system_health;
          const liveHost = document.getElementById('prfSysDiagHost') || document.getElementById('setPerformanceHost');
          if (liveHost && ((S.view === 'performance' && S.tabs.performance !== 'unused_files') || (S.view === 'settings' && S.tabs.settings === 'performance'))) {
            mountSystemPerformanceTab(liveHost);
          }
        }
      }).catch(() => {});
    } else if (ls.items_deferred && !S._lsDeferredLoading) {
      S._lsDeferredLoading = true;
      api('system_health_scan', {}).then(res => {
        S._lsDeferredLoading = false;
        if (res && res.system_health) {
          S.systemHealth = res.system_health;
          const liveHost = document.getElementById('prfSysDiagHost') || document.getElementById('setPerformanceHost');
          if (liveHost && ((S.view === 'performance' && S.tabs.performance !== 'unused_files') || (S.view === 'settings' && S.tabs.settings === 'performance'))) {
            mountSystemPerformanceTab(liveHost);
          }
        }
      }).catch(() => { S._lsDeferredLoading = false; });
    }

    host.querySelector('#perfPurgeNowBtn')?.addEventListener('click', async function () {
      S._lsMutationSeq = (S._lsMutationSeq || 0) + 1;
      this.disabled = true;
      const purge_og = Boolean(host.querySelector('#perfPurgeOgChk')?.checked);
      try {
        const res = await api('cache_purge', { purge_og });
        if (res.system_health) S.systemHealth = res.system_health;
        toast(res.message);
        mountSystemPerformanceTab(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });

    host.querySelector('#perfRefreshHealthBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      try {
        const res = await api('system_health_scan', {});
        if (res.system_health) S.systemHealth = res.system_health;
        toast('Sistem sağlığı verileri güncellendi.');
        mountSystemPerformanceTab(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });

    host.querySelector('#orphScanBtn')?.addEventListener('click', async () => {
      if (S.orphanScanning) return;
      S.orphanScanning = true;
      mountSystemPerformanceTab(host);
      try {
        const res = await api('orphan_media_scan', {});
        S.orphanScan = res.scan || null;
        toast('Medya kullanım taraması tamamlandı.');
      } catch (err) {
        toast(err.message, true);
      } finally {
        S.orphanScanning = false;
        mountSystemPerformanceTab(host);
      }
    });

    const selAll = host.querySelector('#orphSelectAllChk');
    if (selAll) {
      selAll.onchange = () => {
        host.querySelectorAll('.orph-item-chk').forEach(c => { c.checked = selAll.checked; });
      };
    }

    host.querySelector('#orphDeleteSelectedBtn')?.addEventListener('click', async function () {
      const files = Array.from(host.querySelectorAll('.orph-item-chk:checked')).map(c => c.value);
      if (!files.length) {
        toast('Temizlenecek görsel seçmediniz.', true);
        return;
      }
      if (!window.confirm(`${files.length} adet kullanılmayan görsel kalıcı olarak silinecek. Onaylıyor musunuz?`)) return;
      this.disabled = true;
      try {
        const res = await api('orphan_media_delete', { files });
        S.orphanScan = res.scan || null;
        if (res.system_health) S.systemHealth = res.system_health;
        toast(res.message);
        mountSystemPerformanceTab(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });
  }

  function mountUnusedFilesCleanerTab(host) {
    if (!host) return;

    if (!S.unusedFilesReport && !S.unusedFilesScanning) {
      S.unusedFilesScanning = true;
      host.innerHTML = `
        <div class="zs-card">
          <div class="zs-glass-empty-note" style="padding:32px">
            ⏳ <strong>Stüdyo v4.0 Bağımlılık Grafiği Taranıyor...</strong><br/>
            <code>index.php</code>, <code>admin/index.php</code>, <code>admin/api.php</code> ve <code>.htaccess</code> giriş noktalarından tüm aktif dosya bağlantıları doğrulanıyor.
          </div>
        </div>
      `;
      api('unused_files_scan', { force: false })
        .then(res => {
          S.unusedFilesReport = res.report || null;
          if (res.unused_files_summary) S.unusedFilesSummary = res.unused_files_summary;
          updateSidebarBadges();
        })
        .catch(err => toast(err.message, true))
        .finally(() => {
          S.unusedFilesScanning = false;
          if (S.view === 'performance' && S.tabs.performance === 'unused_files') {
            renderPerformanceStudioView();
          }
        });
      return;
    }

    const rep = S.unusedFilesReport || {};
    const items = Array.isArray(rep.items) ? rep.items : [];
    const byCat = rep.by_category || {};
    const unusedCnt = Number(rep.unused_count || items.length || 0);
    const totalKb = Number(rep.total_unused_kb || 0);
    const totalMb = Number(rep.total_unused_mb || 0);
    const tracedPhp = Number(rep.traced_php_count || S.unusedFilesSummary?.traced_php_count || 0);
    const reachableCnt = Number(rep.reachable_files_count || S.unusedFilesSummary?.reachable_files_count || 0);
    const scannedCodeCnt = Number(rep.scanned_code_files || 0);
    const curCatFilter = S.unusedFilesCatFilter || 'all';

    const filteredItems = items.filter(it => curCatFilter === 'all' || it.category === curCatFilter);

    host.innerHTML = `
      <div class="zs-crm-kpi-strip" style="margin-bottom:18px">
        <div class="zs-crm-kpi">
          <small>Aktif Mimari &amp; Bağımlılık Ağı</small>
          <strong style="color:#5eead4">Stüdyo ${esc(rep.active_version || 'v4.0')}</strong>
          <span>${tracedPhp} PHP modülü · ${reachableCnt} bağlı dosya</span>
        </div>
        <div class="zs-crm-kpi">
          <small>Bağlantısız / Gereksiz Dosya</small>
          <strong style="color:${unusedCnt > 0 ? '#fde68a' : '#a7f3d0'}">${unusedCnt > 0 ? `${unusedCnt} Dosya` : '0 Dosya (Temiz)'}</strong>
          <span>${unusedCnt > 0 ? 'v4.0 giriş noktalarıyla bağlantısı yok' : 'Eski sürüm kalıntısı bulunmadı'}</span>
        </div>
        <div class="zs-crm-kpi">
          <small>Kazanılabilir Disk Alanı</small>
          <strong style="color:#5eead4">${totalMb >= 1 ? `${totalMb} MB` : `${totalKb} KB`}</strong>
          <span>Taranan kod/veri: ${scannedCodeCnt} dosya${rep.scan_ms ? ` · ${rep.scan_ms} ms` : ''}</span>
        </div>
        <div class="zs-crm-kpi">
          <small>Çekirdek Dosya Koruması</small>
          <strong style="color:#a7f3d0">%100 Aktif Kalkan</strong>
          <span>Aktif v4.0 dosyaları ve verileri silinemez</span>
        </div>
      </div>

      <div class="zs-card" style="margin-bottom:16px">
        <div class="zs-card-head" style="margin-bottom:14px;align-items:flex-start;gap:12px">
          <div style="flex:1;min-width:0">
            <span class="zs-badge-pill">STATİK BAĞIMLILIK GRAFİĞİ · ESKİ SÜRÜM &amp; YETİM DOSYA DENETÇİSİ</span>
            <h3 style="margin-top:6px">🧹 Artık Kullanılmayan &amp; v4.0 ile Bağlantısı Olmayan Dosyalar</h3>
            <p class="zs-card-sub">
              Denetçi motor; <code>index.php</code>, <code>admin/index.php</code>, <code>admin/api.php</code> ve <code>.htaccess</code> giriş noktalarından başlayarak tüm <code>require/include</code> zincirini, modüler JS paketlerini (<code>admin/chunks/*.js</code>), CSS/font varlıklarını ve <code>blog_index.json</code> kayıtlarını izler. Yeni sürüme geçildiğinde boşa çıkan eski <code>.php</code>/<code>.js</code>/<code>.css</code> dosyalarını, <code>.bak/.old/.tmp</code> kalıntılarını ve indeks dışı yetim <code>data/posts/*.json</code> dosyalarını otomatik yakalar.${rep.scanned_at ? ` · <strong>Son Tarama:</strong> ${esc(rep.scanned_at)}` : ''}
            </p>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
            <button type="button" class="zs-btn" id="unfJumpOrphanMediaBtn" title="/uploads/ altındaki kullanılmayan görselleri taramak için aç">🖼️ Yetim Görseller (/uploads/)</button>
            <button type="button" class="zs-btn zs-btn-ai" id="unfRescanBtn" ${S.unusedFilesScanning ? 'disabled' : ''}>${S.unusedFilesScanning ? '⏳ Taranıyor...' : '🔍 Şimdi Yeniden Tara'}</button>
            ${items.length > 0 ? `<button type="button" class="zs-btn zs-btn-danger" id="unfCleanSelectedBtn" ${S.unusedFilesCleaning ? 'disabled' : ''}>${S.unusedFilesCleaning ? '⏳ Temizleniyor...' : '🗑 Seçili Dosyaları Güvenle Sil'}</button>` : ''}
          </div>
        </div>

        ${items.length > 0 ? `
          <div class="zs-lscache-toolbar" style="margin-bottom:12px">
            <div class="zs-lscache-filters">
              ${[
                ['all', `Tümü (${items.length})`],
                ['unlinked_code', `🔌 Bağlantısız Kod/Varlık (${Number(byCat.unlinked_code || 0)})`],
                ['root_leftover', `📁 Ana Dizin Eski Dosya (${Number(byCat.root_leftover || 0)})`],
                ['temp_backup_junk', `🗑️ Yedek / Log Kalıntısı (${Number(byCat.temp_backup_junk || 0)})`],
                ['orphan_post_json', `📄 Yetim Yazı JSON (${Number(byCat.orphan_post_json || 0)})`],
                ['unlinked_data', `📦 Bağlantısız Veri (${Number(byCat.unlinked_data || 0)})`],
              ].map(([cKey, cLbl]) => `<button type="button" class="zs-ls-filter-btn ${curCatFilter === cKey ? 'active' : ''}" data-unf-cat="${cKey}">${esc(cLbl)}</button>`).join('')}
            </div>
            <label style="display:inline-flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-muted);cursor:pointer">
              <input type="checkbox" id="unfSelectAllChk" checked/>
              <span>Listedeki tümünü seç (<strong>${filteredItems.length}</strong> dosya)</span>
            </label>
          </div>
        ` : ''}

        ${items.length === 0 ? `
          <div class="zs-glass-empty-note" style="padding:26px;border-color:rgba(52,211,153,.38);color:#a7f3d0">
            🎉 <strong>Tertemiz Mimari!</strong> Sunucudaki tüm PHP modülleri, modüler JS paketleri, stil dosyaları ve veri indeksleri aktif <strong>Stüdyo v4.0</strong> ile %100 bağlantılı. Eski sürümlerden kalma veya boşa yer kaplayan hiçbir bağlantısız dosya bulunmadı.
          </div>
        ` : (filteredItems.length === 0 ? `
          <div class="zs-glass-empty-note">Bu kategoride bağlantısız dosya bulunmuyor.</div>
        ` : `
          <div class="zs-table-wrap">
            <table class="zs-table">
              <thead>
                <tr>
                  <th style="width:38px">Seç</th>
                  <th>Dosya Yolu &amp; Boyut</th>
                  <th>Kategori</th>
                  <th>v4.0 Bağlantı Analizi (Neden Gereksiz?)</th>
                  <th>Son Değişiklik</th>
                  <th style="text-align:right">İşlem</th>
                </tr>
              </thead>
              <tbody>
                ${filteredItems.map(it => `
                  <tr>
                    <td>
                      <input type="checkbox" class="unf-item-chk" value="${esc(it.rel)}" checked aria-label="${esc(it.rel)}"/>
                    </td>
                    <td>
                      <strong style="color:#f8fafc;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:12.5px">${esc(it.rel)}</strong>
                      <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">${Number(it.size_kb || 0)} KB · Dizin: <code>${esc(it.dir || '/')}</code></div>
                    </td>
                    <td>
                      <span class="zs-unused-cat-pill is-${esc(it.category || 'unlinked_code')}">${esc(it.category_label || 'Bağlantısız')}</span>
                    </td>
                    <td style="font-size:12.5px;color:#e2e8f0;max-width:460px;line-height:1.45">${esc(it.reason || '')}</td>
                    <td style="white-space:nowrap;font-size:12px;color:var(--text-muted)">${esc(it.modified || '-')}</td>
                    <td style="text-align:right;white-space:nowrap">
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-unf-del-one="${esc(it.rel)}">🗑 Sil</button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `)}
      </div>
    `;

    host.querySelector('#unfJumpOrphanMediaBtn')?.addEventListener('click', () => {
      S.tabs.performance = 'lscache';
      markHistoryPush();
      renderPerformanceStudioView();
      setTimeout(() => {
        document.getElementById('orphScanBtn')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 80);
    });

    host.querySelector('#unfRescanBtn')?.addEventListener('click', async () => {
      if (S.unusedFilesScanning) return;
      S.unusedFilesScanning = true;
      mountUnusedFilesCleanerTab(host);
      try {
        const res = await api('unused_files_scan', { force: true });
        S.unusedFilesReport = res.report || null;
        if (res.unused_files_summary) S.unusedFilesSummary = res.unused_files_summary;
        updateSidebarBadges();
        toast('Bağlantısız dosya taraması güncellendi.');
      } catch (err) {
        toast(err.message, true);
      } finally {
        S.unusedFilesScanning = false;
        renderPerformanceStudioView();
      }
    });

    host.querySelectorAll('[data-unf-cat]').forEach(btn => {
      btn.addEventListener('click', () => {
        S.unusedFilesCatFilter = btn.getAttribute('data-unf-cat') || 'all';
        mountUnusedFilesCleanerTab(host);
      });
    });

    const selAllChk = host.querySelector('#unfSelectAllChk');
    if (selAllChk) {
      selAllChk.addEventListener('change', () => {
        host.querySelectorAll('.unf-item-chk').forEach(chk => {
          chk.checked = selAllChk.checked;
        });
      });
    }

    const executeCleanPaths = async (paths) => {
      if (!paths.length || S.unusedFilesCleaning) return;
      const sampleTxt = paths.slice(0, 4).join('\n• ');
      const moreTxt = paths.length > 4 ? `\n...ve ${paths.length - 4} dosya daha` : '';
      if (!window.confirm(`Stüdyo v4.0 ile hiçbir bağlantısı olmayan ${paths.length} dosya kalıcı olarak silinecek:\n\n• ${sampleTxt}${moreTxt}\n\nOnaylıyor musunuz?`)) {
        return;
      }
      S.unusedFilesCleaning = true;
      mountUnusedFilesCleanerTab(host);
      try {
        const res = await api('unused_files_clean', { paths });
        S.unusedFilesReport = res.report || null;
        if (res.unused_files_summary) S.unusedFilesSummary = res.unused_files_summary;
        if (res.security_audit_20) S.securityAudit20 = res.security_audit_20;
        updateSidebarBadges();
        toast(res.message || 'Bağlantısız dosyalar temizlendi.');
      } catch (err) {
        toast(err.message, true);
      } finally {
        S.unusedFilesCleaning = false;
        renderPerformanceStudioView();
      }
    };

    host.querySelector('#unfCleanSelectedBtn')?.addEventListener('click', () => {
      const selected = Array.from(host.querySelectorAll('.unf-item-chk:checked')).map(c => c.value).filter(Boolean);
      if (!selected.length) {
        toast('Lütfen temizlenecek en az bir dosya seçin.', true);
        return;
      }
      executeCleanPaths(selected);
    });

    host.querySelectorAll('[data-unf-del-one]').forEach(btn => {
      btn.addEventListener('click', () => {
        const rel = btn.getAttribute('data-unf-del-one');
        if (rel) executeCleanPaths([rel]);
      });
    });
  }

  /* ---- MODULE 5A: MODULAR FULL ZIP BACKUP VAULT (/admin/?view=settings&tab=backups) ---- */
  function mountVaultBackupsTab(host) {
    if (!host) return;
    const cfg = Object.assign({
      auto_frequency: 'weekly',
      retain_count: 5,
      auto_include_posts: true,
      auto_include_uploads: false
    }, S.store?.backup_vault || {});
    const backups = Array.isArray(S.vaultBackups) ? S.vaultBackups : [];

    host.innerHTML = `
      <div class="zs-grid-2" style="align-items:start">
        <div style="display:grid;gap:16px">
          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">MODÜLER TAM VERİ PAKETLEYİCİ (.ZIP)</span>
                <h3 style="margin-top:6px">☁️ Yeni Yedek Paketi Oluştur</h3>
                <p class="zs-card-sub">Dahil etmek istediğiniz katmanları seçip tek tıkla <code>data/backups/</code> kasasına arşivleyin ve bilgisayarınıza indirin.</p>
              </div>
            </div>

            <div style="display:grid;gap:10px;padding:12px 14px;border-radius:12px;background:rgba(5,12,17,.7);border:1px solid var(--border-subtle);margin-bottom:14px">
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="vbIncStore" checked/><span><strong>Site Ayarları, Sayfalar, Teklifler &amp; Form Talepleri</strong> (<code>data/*.json</code>)</span></label>
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="vbIncPosts" checked/><span><strong>Tüm Blog Makaleleri &amp; Çok Dilli Çeviriler</strong> (<code>data/posts/</code>)</span></label>
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="vbIncMedia"/><span><strong>Medya Kütüphanesi Görselleri</strong> (<code>uploads/</code> — Tam Arşiv)</span></label>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-primary" id="vbCreateNowBtn">📦 Şimdi Seçili Modülleri Yedekle (.zip)</button>
              <button type="button" class="zs-btn zs-btn-ai" id="vbUploadZipBtn">⬆ Bilgisayardan .zip Yedek Yükle</button>
              <input type="file" id="vbZipFileInp" accept=".zip,application/zip" style="display:none"/>
            </div>
            <div id="vbUploadProgWrap" style="display:none;margin-top:12px">
              <div style="font-size:12px;color:#5eead4;margin-bottom:4px" id="vbUploadProgLbl">Yükleniyor... %0</div>
              <div style="height:7px;border-radius:99px;background:rgba(255,255,255,.08);overflow:hidden">
                <div id="vbUploadProgBar" style="height:100%;width:0%;background:linear-gradient(90deg,#14b8a6,#38bdf8);transition:width .15s"></div>
              </div>
            </div>
          </div>

          <div class="zs-card" style="margin-bottom:0">
            <h3 style="margin:0 0 12px">🕒 Otomatik Zamanlanmış Yedekleme Planı</h3>
            <div class="zs-grid-2">
              <div class="zs-field">
                <label>Otomatik Yedek Sıklığı</label>
                <select id="vbAutoFreq" class="zs-select">
                  <option value="off" ${cfg.auto_frequency === 'off' ? 'selected' : ''}>Kapalı (Yalnızca Manuel)</option>
                  <option value="daily" ${cfg.auto_frequency === 'daily' ? 'selected' : ''}>Her Gün Otomatik</option>
                  <option value="weekly" ${cfg.auto_frequency === 'weekly' ? 'selected' : ''}>Her Hafta Otomatik (Önerilen)</option>
                  <option value="monthly" ${cfg.auto_frequency === 'monthly' ? 'selected' : ''}>Her Ay Otomatik</option>
                </select>
              </div>
              <div class="zs-field">
                <label>Kasada Saklanacak Maksimum Yedek</label>
                <input type="number" min="2" max="20" id="vbRetainCnt" class="zs-input" value="${Number(cfg.retain_count || 5)}"/>
              </div>
            </div>
            <div style="display:grid;gap:8px;margin-bottom:14px">
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="vbAutoPosts" ${cfg.auto_include_posts !== false ? 'checked' : ''}/><span>Otomatik yedeklere blog makalelerini (<code>data/posts/</code>) dahil et</span></label>
              <label class="zs-switch" style="margin:0"><input type="checkbox" id="vbAutoUploads" ${cfg.auto_include_uploads ? 'checked' : ''}/><span>Otomatik yedeklere medya görsellerini (<code>uploads/</code>) dahil et</span></label>
            </div>
            <button type="button" class="zs-btn zs-btn-primary" id="vbSaveCfgBtn">✓ Otomatik Yedek Planını Kaydet</button>
          </div>
        </div>

        <div class="zs-card" style="margin-bottom:0">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <span class="zs-badge-pill">GÜVENLİ ARŞİV KASASI (AKILLI GERİ YÜKLEME)</span>
              <h3 style="margin-top:6px">🗂️ Mevcut .zip Yedek Paketleri (${backups.length})</h3>
              <p class="zs-card-sub">Geri yükleme yaptığınızda <strong>canlı müşteri form talepleriniz (CRM) ve yönetici parolanız / 2FA ayarlarınız korunur</strong>.</p>
            </div>
          </div>

          ${backups.length === 0 ? `
            <div class="zs-glass-empty-note">Kasada henüz <code>.zip</code> yedek arşivi bulunmuyor. Sol taraftan ilk tam yedek paketinizi saniyeler içinde oluşturabilirsiniz.</div>
          ` : `
            <div style="display:grid;gap:10px">
              ${backups.map(b => {
                const dlUrl = `${CFG.apiUrl}?action=vault_backup_download&file=${encodeURIComponent(b.filename)}&csrf_token=${encodeURIComponent(CFG.csrfToken)}`;
                const szStr = Number(b.size_mb) >= 1 ? `${b.size_mb} MB` : `${b.size_kb} KB`;
                return `
                  <div class="zs-rev-item">
                    <div style="min-width:0">
                      <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                        <strong style="color:#fff;font-size:13px;word-break:break-all">${esc(b.filename)}</strong>
                        <span class="zs-sec-badge ${b.is_auto ? 'info' : 'ok'}">${b.is_auto ? 'OTOMATİK' : 'MANUEL'}</span>
                      </div>
                      <div style="font-size:12px;color:var(--text-muted);margin-top:4px">
                        🕒 ${esc(b.created_at)} · 📦 <strong>${esc(szStr)}</strong>
                      </div>
                      <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:5px">
                        ${(b.modules || []).map(m => `<span class="zs-pill zs-pill-cat" style="font-size:10.5px">${esc(m)}</span>`).join('')}
                      </div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0">
                      <a href="${esc(dlUrl)}" class="zs-btn zs-btn-sm zs-btn-primary">⬇ İndir</a>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-vb-restore="${esc(b.filename)}">↺ Geri Yükle</button>
                      <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-vb-del="${esc(b.filename)}" title="Arşivi Sil">✕</button>
                    </div>
                  </div>
                `;
              }).join('')}
            </div>
          `}
        </div>
      </div>
    `;

    host.querySelector('#vbCreateNowBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      this.textContent = '⏳ .zip Yedek Paketi Hazırlanıyor...';
      try {
        const res = await api('vault_backup_create', {
          include_store: Boolean(host.querySelector('#vbIncStore')?.checked),
          include_posts: Boolean(host.querySelector('#vbIncPosts')?.checked),
          include_uploads: Boolean(host.querySelector('#vbIncMedia')?.checked)
        });
        S.vaultBackups = res.vault_backups || [];
        if (res.system_health) S.systemHealth = res.system_health;
        toast(res.message);
        mountVaultBackupsTab(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
        this.textContent = '📦 Şimdi Seçili Modülleri Yedekle (.zip)';
      }
    });

    const zipInp = host.querySelector('#vbZipFileInp');
    host.querySelector('#vbUploadZipBtn')?.addEventListener('click', () => zipInp?.click());
    if (zipInp) {
      zipInp.onchange = () => {
        const f = zipInp.files?.[0];
        if (!f) return;
        zipInp.value = '';
        const wrap = host.querySelector('#vbUploadProgWrap');
        const lbl = host.querySelector('#vbUploadProgLbl');
        const bar = host.querySelector('#vbUploadProgBar');
        if (wrap) wrap.style.display = 'block';

        const fd = new FormData();
        fd.append('csrf_token', CFG.csrfToken || '');
        fd.append('backup_zip', f);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', CFG.apiUrl + '?action=vault_backup_upload', true);
        xhr.setRequestHeader('X-CSRF-Token', CFG.csrfToken || '');
        xhr.upload.onprogress = ev => {
          if (ev.lengthComputable) {
            const pct = Math.round((ev.loaded / ev.total) * 100);
            if (lbl) lbl.textContent = `Yedek paketi yükleniyor (${f.name})... %${pct}`;
            if (bar) bar.style.width = pct + '%';
          }
        };
        xhr.onload = () => {
          try {
            const json = JSON.parse(xhr.responseText);
            if (!json.ok) throw new Error(json.error || 'Yükleme hatası.');
            S.vaultBackups = json.vault_backups || [];
            toast(json.message);
            mountVaultBackupsTab(host);
          } catch (err) {
            toast(err.message, true);
            if (wrap) wrap.style.display = 'none';
          }
        };
        xhr.onerror = () => {
          toast('Yedek yüklenirken ağ hatası oluştu.', true);
          if (wrap) wrap.style.display = 'none';
        };
        xhr.send(fd);
      };
    }

    host.querySelector('#vbSaveCfgBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      try {
        const payload = {
          auto_frequency: host.querySelector('#vbAutoFreq')?.value || 'weekly',
          retain_count: Number(host.querySelector('#vbRetainCnt')?.value || 5),
          auto_include_posts: Boolean(host.querySelector('#vbAutoPosts')?.checked),
          auto_include_uploads: Boolean(host.querySelector('#vbAutoUploads')?.checked)
        };
        const res = await api('vault_backup_save_cfg', { backup_vault: payload });
        S.store.backup_vault = res.backup_vault || payload;
        toast(res.message);
      } catch (err) {
        toast(err.message, true);
      } finally {
        this.disabled = false;
      }
    });

    host.querySelectorAll('[data-vb-restore]').forEach(btn => {
      btn.onclick = async () => {
        const file = btn.getAttribute('data-vb-restore');
        if (!file) return;
        if (!window.confirm(`"${file}" yedeği geri yüklenecek.\n\nCanlı müşteri form talepleriniz (CRM) ve yönetici parolanız korunacaktır. Devam edilsin mi?`)) return;
        btn.disabled = true;
        btn.textContent = '⏳ Yükleniyor...';
        try {
          const res = await api('vault_backup_restore', { file });
          if (res.store) S.store = normalizeClientStoreMaps(res.store);
          if (res.summary) S.summary = res.summary;
          toast(res.message);
          mountVaultBackupsTab(host);
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
          btn.textContent = '↺ Geri Yükle';
        }
      };
    });

    host.querySelectorAll('[data-vb-del]').forEach(btn => {
      btn.onclick = async () => {
        const file = btn.getAttribute('data-vb-del');
        if (!file || !window.confirm(`"${file}" yedek arşivini kasadan silmek istiyor musunuz?`)) return;
        try {
          const res = await api('vault_backup_delete', { file });
          S.vaultBackups = res.vault_backups || [];
          toast(res.message);
          mountVaultBackupsTab(host);
        } catch (err) {
          toast(err.message, true);
        }
      };
    });
  }

  /* ---- MODULE 5A: 20/20 PRODUCTION SECURITY & CI/CD SELF-AUDIT CARD ---- */
  function mountSecurity20AuditCard(host) {
    if (!host || !isSuperAdmin()) return;
    const rep = S.securityAudit20;
    const items = Array.isArray(rep?.items) ? rep.items : [];
    const passed = Number(rep?.passed ?? items.filter(x => x.ok).length);
    const total = Number(rep?.total ?? (items.length || 20));
    const allOk = items.length > 0 && passed === total;

    host.innerHTML = `
      <div class="zs-card" style="margin-bottom:0;border-color:${allOk ? 'rgba(52,211,153,.36)' : 'rgba(251,191,36,.36)'}">
        <div class="zs-card-head" style="margin-bottom:12px">
          <div>
            <span class="zs-badge-pill">OWASP &amp; KURUMSAL ÜRETİM KONTROL LİSTESİ (20 KRİTER)</span>
            <h3 style="margin-top:6px">🛡️ 20/20 Güvenlik Mimari Kalkanı &amp; CI/CD Canlı Denetçisi</h3>
            <p class="zs-card-sub">Kimlik doğrulama, RBAC, XSS, CSRF, CORS, HTTPS/HSTS, IDOR, Dosya Yükleme, Sırlar, Global Hata Yakalama ve Yedekleme dahil 20 kritik üretim katmanının anlık durumu.</p>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            ${items.length > 0 ? `<span class="zs-sec-badge ${allOk ? 'ok' : 'warn'}">${passed}/${total} AKTİF (%${Number(rep?.score_pct ?? 100)})</span>` : ''}
            <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="secRun20AuditBtn">⚡ 20 Kriteri Şimdi Denetle</button>
          </div>
        </div>

        ${items.length === 0 ? `
          <div class="zs-glass-empty-note">20 maddelik güvenlik denetim raporunu görüntülemek için sağ üstteki <strong>⚡ 20 Kriteri Şimdi Denetle</strong> butonuna tıklayın.</div>
        ` : `
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(255px,1fr));gap:10px">
            ${items.map(it => `
              <div style="padding:11px 13px;border-radius:var(--radius-md);background:rgba(4,12,18,.76);border:1px solid ${it.ok ? 'rgba(52,211,153,.24)' : 'rgba(248,113,113,.38)'}">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px">
                  <strong style="font-size:12.5px;color:#f8fafc">#${Number(it.id)}. ${esc(it.title || '')}</strong>
                  <span class="zs-sec-badge ${it.ok ? 'ok' : 'danger'}" style="flex-shrink:0">${it.ok ? '✓ AKTİF' : 'UYARI'}</span>
                </div>
                <div style="font-size:11.5px;color:var(--text-muted);line-height:1.45">${esc(it.detail || '')}</div>
              </div>
            `).join('')}
          </div>
          ${Array.isArray(rep?.cleanable_archives) && rep.cleanable_archives.length > 0 ? `
            <div style="margin-top:10px;padding:10px 13px;border-radius:10px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.26);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
              <div style="font-size:12px;color:#cbd5e1">
                📦 <strong>Ana dizinde kurulumdan kalan arşiv/kopya dosya bulundu</strong> (<code>.htaccess</code> ile dış erişime 403 kapalıdır):
                <code>${rep.cleanable_archives.map(f => esc(f)).join('</code>, <code>')}</code>
              </div>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="secCleanRootZipsBtn">🧹 Kurulum .zip Artıklarını Sil</button>
            </div>
          ` : ''}
          <div style="margin-top:10px;font-size:11.5px;color:var(--text-dim);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <span>CI/CD Komutu: <code>php admin/api.php --ci-security-audit</code> (Exit 0 = 20/20 Uyumlu)</span>
            <span>Son Denetim: ${esc(rep?.checked_at || '')} · PHP ${esc(rep?.php_version || '')}</span>
          </div>
        `}
      </div>
    `;

    host.querySelector('#secCleanRootZipsBtn')?.addEventListener('click', async (ev) => {
      const btn = ev.currentTarget;
      btn.disabled = true;
      btn.textContent = '⏳ Siliniyor...';
      try {
        const res = await api('security_clean_root_leftovers', {});
        if (res.security_audit_20) S.securityAudit20 = res.security_audit_20;
        toast(res.message || 'Kurulum artıkları temizlendi.');
        mountSecurity20AuditCard(host);
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
        btn.textContent = '🧹 Kurulum .zip Artıklarını Sil';
      }
    });

    host.querySelector('#secRun20AuditBtn')?.addEventListener('click', async (ev) => {
      const btn = ev.currentTarget;
      btn.disabled = true;
      btn.textContent = '⏳ 20 Katman Taranıyor...';
      try {
        const res = await api('security_20_audit', {});
        if (res.security_audit_20) {
          S.securityAudit20 = res.security_audit_20;
        }
        toast(res.message || '20/20 güvenlik denetimi tamamlandı.');
        mountSecurity20AuditCard(host);
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
        btn.textContent = '⚡ 20 Kriteri Şimdi Denetle';
      }
    });
  }

  /* ---- MODULE 5B: RFC 6238 TOTP 2FA QR + RECOVERY CODES CARD (IN SECURITY TAB) ---- */
  function mountSecurity2faCard(host) {
    if (!host || !isSuperAdmin()) return;
    const tfa = Object.assign({
      enabled: false,
      method: 'totp',
      email_fallback: true,
      remaining_recovery_codes: 0,
      enabled_at: null
    }, S.store?.security_2fa || {});
    const setup = S.twoFaSetup;
    const recCodes = S.twoFaRecoveryCodes;

    host.innerHTML = `
      <div class="zs-card" style="margin-bottom:0;border-color:rgba(94,234,212,.3)">
        <div class="zs-card-head" style="margin-bottom:12px">
          <div>
            <span class="zs-badge-pill">İKİ AŞAMALI GİRİŞ DOĞRULAMA (RFC 6238 TOTP &amp; KURTARMA KODU)</span>
            <h3 style="margin-top:6px">🔐 Yönetici 2FA Kalkanı (Google / Microsoft Authenticator &amp; E-Posta OTP)</h3>
            <p class="zs-card-sub">Parolanız bilinse bile telefondaki 6 haneli doğrulama kodu olmadan yönetim stüdyosuna giriş yapılmasını engeller.</p>
          </div>
          <span class="zs-sec-badge ${tfa.enabled ? 'ok' : 'info'}">${tfa.enabled ? '🔐 2FA AKTİF' : 'PASİF'}</span>
        </div>

        ${Array.isArray(recCodes) && recCodes.length > 0 ? `
          <div class="zs-2fa-recovery-box" style="margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
              <strong style="color:#a7f3d0;font-size:13.5px">🎉 2FA Etkinleştirildi! 8 Adet Tek Kullanımlık Kurtarma Kodunuzu Saklayın:</strong>
              <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="tfaCopyRecBtn">📋 Kodları Kopyala</button>
            </div>
            <div class="zs-2fa-rec-grid">
              ${recCodes.map(c => `<code>${esc(c)}</code>`).join('')}
            </div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:8px">Telefonunuza erişemediğiniz acil durumlarda giriş ekranına bu kodlardan birini yazarak oturum açabilirsiniz.</div>
          </div>
        ` : ''}

        ${tfa.enabled ? `
          <div class="zs-grid-2" style="align-items:center">
            <div style="padding:12px 14px;border-radius:12px;background:rgba(16,185,129,.08);border:1px solid rgba(52,211,153,.3);font-size:13px;color:#d1fae5">
              <div>✓ <strong>Aktif Doğrulama Yöntemi:</strong> ${tfa.method === 'email' ? 'E-Posta Tek Kullanımlık Kod (OTP)' : 'Authenticator Uygulaması (6 Haneli TOTP)'}</div>
              <div style="margin-top:4px;font-size:12px;color:#a7f3d0">Etkinleştirme: ${esc(tfa.enabled_at || '-')} · Kalan Kurtarma Kodu: <strong>${Number(tfa.remaining_recovery_codes ?? 8)}</strong> adet</div>
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
              <div class="zs-field" style="flex:1;min-width:200px;margin-bottom:0">
                <label>2FA Kapatmak İçin Mevcut Parolanız</label>
                <input type="password" id="tfaDisablePassInp" class="zs-input" placeholder="Yönetici parolanız"/>
              </div>
              <button type="button" class="zs-btn zs-btn-danger" id="tfaDisableBtn">🔓 2FA Korumasını Kapat</button>
            </div>
          </div>
        ` : (!setup ? `
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div style="font-size:13px;color:var(--text-muted)">Kurulum dış kütüphane gerektirmez; QR kod doğrudan sunucunuzda yerel SVG olarak üretilir.</div>
            <button type="button" class="zs-btn zs-btn-primary" id="tfaStartSetupBtn">📲 QR Kod ile 2FA Kurulumunu Başlat</button>
          </div>
        ` : `
          <div class="zs-2fa-setup-grid">
            <div class="zs-2fa-qr-box">
              ${setup.qr_svg || ''}
              <div style="font-size:11px;color:var(--text-muted);margin-top:8px;text-align:center">Google Authenticator, Microsoft Authenticator veya Apple Parolalar ile okutun</div>
            </div>
            <div>
              <div class="zs-grid-2">
                <div class="zs-field">
                  <label>Birincil Doğrulama Yöntemi</label>
                  <select id="tfaMethodSel" class="zs-select">
                    <option value="totp">📱 Authenticator Uygulaması (Önerilen)</option>
                    <option value="email">✉️ SMTP E-Posta Doğrulama Kodu</option>
                  </select>
                </div>
                <div class="zs-field">
                  <label>Manuel Kurulum Anahtarı (Gizli Anahtar)</label>
                  <input class="zs-input" readonly value="${esc(setup.secret || '')}" onclick="this.select()"/>
                </div>
              </div>
              <label class="zs-switch" style="margin-bottom:12px">
                <input type="checkbox" id="tfaEmailFallbackChk" checked/>
                <span>Giriş ekranında SMTP üzerinden yedek e-posta kodu gönderilmesine izin ver</span>
              </label>
              <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                <div class="zs-field" style="margin-bottom:0;min-width:220px">
                  <label>Uygulamadaki 6 Haneli Doğrulama Kodu *</label>
                  <input id="tfaVerifyCodeInp" class="zs-input" maxlength="6" inputmode="numeric" placeholder="000000" style="font-size:18px;letter-spacing:4px;font-weight:700;text-align:center"/>
                </div>
                <button type="button" class="zs-btn zs-btn-primary" id="tfaConfirmEnableBtn">✓ Doğrula &amp; 2FA Kalkanını Aktif Et</button>
                <button type="button" class="zs-btn" id="tfaCancelSetupBtn">İptal</button>
              </div>
            </div>
          </div>
        `)}
      </div>
    `;

    host.querySelector('#tfaCopyRecBtn')?.addEventListener('click', () => {
      if (Array.isArray(recCodes)) {
        navigator.clipboard?.writeText(recCodes.join('\n'));
        toast('8 adet kurtarma kodu panoya kopyalandı.');
      }
    });

    host.querySelector('#tfaStartSetupBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      try {
        const res = await api('security_2fa_init', {});
        S.twoFaSetup = res;
        S.twoFaRecoveryCodes = null;
        mountSecurity2faCard(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });

    host.querySelector('#tfaCancelSetupBtn')?.addEventListener('click', () => {
      S.twoFaSetup = null;
      mountSecurity2faCard(host);
    });

    host.querySelector('#tfaConfirmEnableBtn')?.addEventListener('click', async function () {
      const method = host.querySelector('#tfaMethodSel')?.value || 'totp';
      const code = host.querySelector('#tfaVerifyCodeInp')?.value.trim() || '';
      const email_fallback = Boolean(host.querySelector('#tfaEmailFallbackChk')?.checked);
      if (method === 'totp' && code.replace(/\D+/g, '').length !== 6) {
        toast('Lütfen Authenticator uygulamasındaki 6 haneli kodu girin.', true);
        return;
      }
      this.disabled = true;
      try {
        const res = await api('security_2fa_enable', {
          method,
          code,
          secret: setup?.secret || '',
          email_fallback
        });
        S.store.security_2fa = res.security_2fa || { enabled: true, method, remaining_recovery_codes: 8 };
        S.twoFaSetup = null;
        S.twoFaRecoveryCodes = res.recovery_codes || [];
        toast(res.message);
        mountSecurity2faCard(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });

    host.querySelector('#tfaDisableBtn')?.addEventListener('click', async function () {
      const current_password = host.querySelector('#tfaDisablePassInp')?.value || '';
      if (!current_password) {
        toast('Lütfen doğrulama için mevcut yönetici parolanızı girin.', true);
        return;
      }
      this.disabled = true;
      try {
        const res = await api('security_2fa_disable', { current_password });
        S.store.security_2fa = res.security_2fa || { enabled: false };
        S.twoFaRecoveryCodes = null;
        toast(res.message);
        mountSecurity2faCard(host);
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });
  }

  /* ======================== AGENCY GROWTH SUITE MODULES (SOLUTIONS · BOOKING · PORTAL · RENEWALS) ======================== */
  function renderRenewalsView() {
    syncAdminUrl('replace');
    const items = Array.isArray(S.renewals) ? S.renewals : [];
    const sum = S.renewalsSummary || {
      total_active: items.filter(i => i.status !== 'cancelled').length,
      expiring_30_count: items.filter(i => i.status !== 'cancelled' && Number(i.days_left) <= 30).length,
      expiring_7_count: items.filter(i => i.status !== 'cancelled' && Number(i.days_left) <= 7).length,
      overdue_count: items.filter(i => i.status !== 'cancelled' && Number(i.days_left) < 0).length,
      annual_recurring_try: 0
    };
    const flt = S.renewalsFilter || 'all';
    const filtered = items.filter(it => {
      if (flt === 'expiring') return it.status !== 'cancelled' && Number(it.days_left) <= 30;
      if (flt === 'overdue') return it.status !== 'cancelled' && Number(it.days_left) < 0;
      return true;
    });

    const editing = S.editingRenewal;
    const vcards = Array.isArray(S.store?.vcards) ? S.store.vcards : [];
    const fmtAmt = (amt, cur) => {
      const sym = cur === 'USD' ? '$' : (cur === 'EUR' ? '€' : '₺');
      return Number(amt || 0).toLocaleString('tr-TR') + ' ' + sym;
    };

    ROOT.innerHTML = `
      <div class="zs-section-head">
        <div>
          <span class="zs-badge-pill">📆 YILLIK GELİR &amp; HİZMET SÜREKLİLİĞİ KULESİ</span>
          <h2 style="margin:6px 0 4px">Hosting, Domain, SSL &amp; Bakım Yenileme Radarı</h2>
          <p class="zs-card-sub">Müşteri alan adı, sunucu ve yıllık bakım sözleşmelerini takip edin; tek tıkla <strong>WhatsApp + IBAN Kartvizit</strong> veya kurumsal <strong>SMTP Yenileme E-Postası</strong> gönderin.</p>
        </div>
        <div class="zs-btn-group">
          <button type="button" class="zs-btn" id="rnwGoUptimeBtn">📡 Canlı Uptime &amp; SSL Kulesi</button>
          <button type="button" class="zs-btn zs-btn-primary" id="rnwNewBtn">+ Yeni Yenileme Kaydı Ekle</button>
        </div>
      </div>

      <div class="zs-crm-kpi-strip" style="margin-bottom:18px">
        <div class="zs-crm-kpi">
          <small>Aktif Yıllık Hizmet</small>
          <strong>${Number(sum.total_active || 0)}</strong>
          <span>Hosting, Domain &amp; Bakım</span>
        </div>
        <div class="zs-crm-kpi">
          <small>Yıllık Tekrarlayan Hacim</small>
          <strong style="color:#6ee7b7">${Number(sum.annual_recurring_try || 0).toLocaleString('tr-TR')} ₺</strong>
          <span>Tahmini yıllık yenileme portföyü</span>
        </div>
        <div class="zs-crm-kpi">
          <small>30 Gün İçinde Yenilenecek</small>
          <strong style="color:#fde68a">${Number(sum.expiring_30_count || 0)}</strong>
          <span>7 gün altı kritik: ${Number(sum.expiring_7_count || 0)}</span>
        </div>
        <div class="zs-crm-kpi">
          <small>Süresi Geçen (Geciken)</small>
          <strong style="color:${Number(sum.overdue_count || 0) > 0 ? '#fca5a5' : '#ffffff'}">${Number(sum.overdue_count || 0)}</strong>
          <span>Acil tahsilat / uzatma bekleyen</span>
        </div>
      </div>

      ${editing ? `
        <div class="zs-card" style="margin-bottom:18px;border-color:rgba(94,234,212,.4)">
          <div class="zs-card-head">
            <h3 style="margin:0">${editing.id ? '✏️ Yenileme Kaydını Düzenle' : '✨ Yeni Müşteri Hosting / Domain / Bakım Kaydı'}</h3>
            <button type="button" class="zs-btn zs-btn-sm" id="rnwCancelBtn">✕ Kapat</button>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Alan Adı (Domain) *</label><input id="rnwDomain" class="zs-input" placeholder="ornekfirma.com" value="${esc(editing.domain || '')}"/></div>
            <div class="zs-field"><label>Müşteri Yetkili Adı</label><input id="rnwClient" class="zs-input" placeholder="Mehmet Kaya" value="${esc(editing.client_name || '')}"/></div>
            <div class="zs-field"><label>Firma Ünvanı</label><input id="rnwCompany" class="zs-input" placeholder="Örnek Sanayi A.Ş." value="${esc(editing.company || '')}"/></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Müşteri Telefon (WhatsApp)</label><input id="rnwPhone" class="zs-input" placeholder="+90 5XX XXX XX XX" value="${esc(editing.phone || '')}"/></div>
            <div class="zs-field"><label>Müşteri E-Posta (SMTP Bildirim İçin)</label><input id="rnwEmail" class="zs-input" placeholder="muhasebe@firma.com" value="${esc(editing.email || '')}"/></div>
            <div class="zs-field"><label>Hizmet Kapsamı</label><input id="rnwService" class="zs-input" placeholder="Kurumsal Hosting + Domain + SSL" value="${esc(editing.service_type || '')}"/></div>
          </div>
          <div class="zs-grid-3">
            <div class="zs-field"><label>Yenileme / Bitiş Tarihi *</label><input type="date" id="rnwDate" class="zs-input" value="${esc(editing.renewal_date || '')}"/></div>
            <div class="zs-field">
              <label>Yıllık Yenileme Tutarı (+KDV)</label>
              <div style="display:flex;gap:6px">
                <input type="number" step="0.01" id="rnwAmount" class="zs-input" value="${Number(editing.amount || 0)}"/>
                <select id="rnwCurrency" class="zs-select" style="width:105px">
                  <option value="TRY" ${editing.currency === 'TRY' ? 'selected' : ''}>₺ TRY</option>
                  <option value="USD" ${editing.currency === 'USD' ? 'selected' : ''}>$ USD</option>
                  <option value="EUR" ${editing.currency === 'EUR' ? 'selected' : ''}>€ EUR</option>
                </select>
              </div>
            </div>
            <div class="zs-field">
              <label>Ödeme / IBAN Dijital Kartviziti</label>
              <select id="rnwVcard" class="zs-select">
                <option value="">— Genel İletişim Sayfası —</option>
                ${vcards.map(vc => `<option value="${esc(vc.slug)}" ${editing.vcard_slug === vc.slug ? 'selected' : ''}>💳 ${esc(vc.name || vc.slug)} (/kartvizit/${esc(vc.slug)}/)</option>`).join('')}
              </select>
            </div>
          </div>
          <div class="zs-grid-2">
            <div class="zs-field" style="margin-bottom:0"><label>Paket / Sunucu Notu</label><input id="rnwNotes" class="zs-input" placeholder="LiteSpeed NVMe Sunucu · Otomatik Yedekleme Dahil" value="${esc(editing.notes || '')}"/></div>
            <div class="zs-field" style="margin-bottom:0">
              <label>Durum</label>
              <select id="rnwStatus" class="zs-select">
                <option value="active" ${editing.status === 'active' ? 'selected' : ''}>🟢 Aktif Sözleşme</option>
                <option value="reminded" ${editing.status === 'reminded' ? 'selected' : ''}>📨 Yenileme Hatırlatması Gönderildi</option>
                <option value="paid" ${editing.status === 'paid' ? 'selected' : ''}>✅ Ödendi</option>
                <option value="cancelled" ${editing.status === 'cancelled' ? 'selected' : ''}>⚪ İptal / Pasif</option>
              </select>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
            <button type="button" class="zs-btn" id="rnwCancelBtn2">Vazgeç</button>
            <button type="button" class="zs-btn zs-btn-primary" id="rnwSaveBtn">✓ Yenileme Kaydını Kaydet</button>
          </div>
        </div>
      ` : ''}

      <div class="zs-tabs" style="margin-bottom:14px">
        <button type="button" class="zs-tab ${flt === 'all' ? 'active' : ''}" data-rnw-flt="all">📋 Tümü (${items.length})</button>
        <button type="button" class="zs-tab ${flt === 'expiring' ? 'active' : ''}" data-rnw-flt="expiring">⏳ Yaklaşan ≤30 Gün (${Number(sum.expiring_30_count || 0)})</button>
        <button type="button" class="zs-tab ${flt === 'overdue' ? 'active' : ''}" data-rnw-flt="overdue">🚨 Geciken (${Number(sum.overdue_count || 0)})</button>
      </div>

      ${filtered.length === 0 ? `
        <div class="zs-card" style="text-align:center;padding:40px 20px">
          <h3 style="margin:0 0 8px">${items.length === 0 ? 'Henüz Yıllık Yenileme Kaydı Eklenmedi' : 'Bu Filtrede Kayıt Bulunmuyor'}</h3>
          <p class="zs-card-sub" style="max-width:540px;margin:0 auto 14px">Müşterilerinizin domain, hosting, SSL ve yıllık bakım yenileme tarihlerini ekleyerek hiçbir tahsilatı ve süre uzatımını kaçırmayın.</p>
          ${items.length === 0 ? `<button type="button" class="zs-btn zs-btn-primary" id="rnwEmptyNewBtn">+ İlk Yenileme Kaydını Ekle</button>` : ''}
        </div>
      ` : `
        <div class="zs-card" style="padding:0;overflow:hidden">
          <div class="zs-table-wrap">
            <table class="zs-table">
              <thead>
                <tr>
                  <th>Alan Adı &amp; Hizmet</th>
                  <th>Müşteri</th>
                  <th>Yenileme Tarihi &amp; Kalan Gün</th>
                  <th>Yıllık Bedel</th>
                  <th>Durum</th>
                  <th style="text-align:right">Hızlı Aksiyonlar</th>
                </tr>
              </thead>
              <tbody>
                ${filtered.map(it => {
                  const dl = Number(it.days_left || 0);
                  let urgBadge = `<span class="zs-pill zs-pill-ok">${dl} gün kaldı</span>`;
                  if (it.status === 'cancelled') {
                    urgBadge = `<span class="zs-pill">Pasif</span>`;
                  } else if (dl < 0) {
                    urgBadge = `<span class="zs-pill" style="border:1px solid #f87171;color:#fca5a5">${Math.abs(dl)} gün gecikti!</span>`;
                  } else if (dl <= 7) {
                    urgBadge = `<span class="zs-pill" style="border:1px solid #fbbf24;color:#fde68a">Kritik · ${dl} gün</span>`;
                  } else if (dl <= 30) {
                    urgBadge = `<span class="zs-pill zs-pill-cat">Yaklaşıyor · ${dl} gün</span>`;
                  }

                  const cleanPh = String(it.phone || '').replace(/\D+/g, '');
                  const waDigits = cleanPh.startsWith('0') ? '9' + cleanPh : (cleanPh.startsWith('90') ? cleanPh : '90' + cleanPh);
                  const billingLink = it.vcard_slug
                    ? (window.location.origin + resolveUrl('/kartvizit/' + it.vcard_slug + '/'))
                    : (window.location.origin + resolveUrl('/iletisim/'));
                  const waReminder = encodeURIComponent(
                    `Merhaba ${it.client_name || it.company || ''}, *${it.domain || 'Kurumsal Web'}* (${it.service_type || 'Hosting & Domain'}) hizmetinizin yıllık yenileme tarihi *${it.renewal_date}* olup yenileme bedeli *${fmtAmt(it.amount, it.currency)} + KDV*'dir.\n\nKesintisiz yayın için kurumsal hesap (IBAN) bilgilerimiz:\n${billingLink}`
                  );

                  return `
                    <tr>
                      <td>
                        <strong style="color:#fff;font-size:13.5px">${esc(it.domain || '—')}</strong>
                        <div style="font-size:11.5px;color:var(--text-muted)">${esc(it.service_type || '')}</div>
                      </td>
                      <td>
                        <strong style="font-size:13px">${esc(it.company || it.client_name || '—')}</strong>
                        ${it.company && it.client_name ? `<div style="font-size:11.5px;color:var(--text-muted)">${esc(it.client_name)}</div>` : ''}
                      </td>
                      <td>
                        <div style="font-weight:700;color:#e2eff3;margin-bottom:3px">${esc(it.renewal_date)}</div>
                        ${urgBadge}
                      </td>
                      <td><strong style="color:#6ee7b7;font-size:14px">${esc(fmtAmt(it.amount, it.currency))}</strong></td>
                      <td>
                        <span class="zs-pill ${it.status === 'reminded' ? 'zs-pill-cat' : (it.status === 'cancelled' ? '' : 'zs-pill-ok')}">
                          ${it.status === 'reminded' ? '📨 Hatırlatıldı' : (it.status === 'cancelled' ? 'İptal' : 'Aktif')}
                        </span>
                        ${it.last_reminded_at ? `<div style="font-size:10.5px;color:var(--text-dim);margin-top:3px">Son: ${esc(it.last_reminded_at)}</div>` : ''}
                      </td>
                      <td style="text-align:right">
                        <div style="display:inline-flex;gap:5px;flex-wrap:wrap;justify-content:flex-end">
                          <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" data-rnw-extend="${esc(it.id)}" title="+1 Yıl Uzat ve Ödendi İşaretle">+1 Yıl Uzat</button>
                          ${cleanPh ? `<a href="https://wa.me/${esc(waDigits)}?text=${waReminder}" target="_blank" rel="noopener" class="zs-btn zs-btn-sm" style="border-color:rgba(52,211,153,.4);color:#6ee7b7" title="WhatsApp ile Tutar + IBAN Kartvizit Gönder">💬 WA + IBAN</a>` : ''}
                          ${it.email ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-rnw-mail="${esc(it.id)}" title="SMTP ile Kurumsal Yenileme E-Postası Gönder">📧 E-Posta</button>` : ''}
                          <button type="button" class="zs-btn zs-btn-sm" data-rnw-edit="${esc(it.id)}">✏️</button>
                          <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-rnw-del="${esc(it.id)}">🗑</button>
                        </div>
                      </td>
                    </tr>
                  `;
                }).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `}
    `;

    document.getElementById('rnwGoUptimeBtn')?.addEventListener('click', () => navigateTo('uptime_tower'));
    const openNewRnw = () => {
      S.editingRenewal = createDefaultRenewalDraft();
      renderRenewalsView();
    };
    document.getElementById('rnwNewBtn')?.addEventListener('click', openNewRnw);
    document.getElementById('rnwEmptyNewBtn')?.addEventListener('click', openNewRnw);
    document.getElementById('rnwCancelBtn')?.addEventListener('click', () => { S.editingRenewal = null; renderRenewalsView(); });
    document.getElementById('rnwCancelBtn2')?.addEventListener('click', () => { S.editingRenewal = null; renderRenewalsView(); });

    ROOT.querySelectorAll('[data-rnw-flt]').forEach(b => {
      b.addEventListener('click', () => {
        S.renewalsFilter = b.getAttribute('data-rnw-flt') || 'all';
        renderRenewalsView();
      });
    });

    document.getElementById('rnwSaveBtn')?.addEventListener('click', async function () {
      const payload = Object.assign({}, S.editingRenewal || {}, {
        domain: document.getElementById('rnwDomain')?.value.trim() || '',
        client_name: document.getElementById('rnwClient')?.value.trim() || '',
        company: document.getElementById('rnwCompany')?.value.trim() || '',
        phone: document.getElementById('rnwPhone')?.value.trim() || '',
        email: document.getElementById('rnwEmail')?.value.trim() || '',
        service_type: document.getElementById('rnwService')?.value.trim() || '',
        renewal_date: document.getElementById('rnwDate')?.value || '',
        amount: Number(document.getElementById('rnwAmount')?.value || 0),
        currency: document.getElementById('rnwCurrency')?.value || 'TRY',
        vcard_slug: document.getElementById('rnwVcard')?.value || '',
        notes: document.getElementById('rnwNotes')?.value.trim() || '',
        status: document.getElementById('rnwStatus')?.value || 'active'
      });
      this.disabled = true;
      try {
        const res = await api('renewal_save', { renewal: payload });
        S.renewals = res.renewals || [];
        S.renewalsSummary = res.renewals_summary || null;
        S.editingRenewal = null;
        updateSidebarBadges();
        toast(res.message);
        renderRenewalsView();
      } catch (err) {
        toast(err.message, true);
        this.disabled = false;
      }
    });

    ROOT.querySelectorAll('[data-rnw-edit]').forEach(btn => {
      btn.addEventListener('click', () => {
        const found = items.find(x => x.id === btn.getAttribute('data-rnw-edit'));
        if (found) {
          S.editingRenewal = Object.assign({}, found);
          renderRenewalsView();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });

    ROOT.querySelectorAll('[data-rnw-extend]').forEach(btn => {
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        try {
          const res = await api('renewal_extend_year', { id: btn.getAttribute('data-rnw-extend'), years: 1 });
          S.renewals = res.renewals || [];
          S.renewalsSummary = res.renewals_summary || null;
          updateSidebarBadges();
          toast(res.message);
          renderRenewalsView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      });
    });

    ROOT.querySelectorAll('[data-rnw-mail]').forEach(btn => {
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        const orig = btn.textContent;
        btn.textContent = '⏳...';
        try {
          const res = await api('renewal_send_email', { id: btn.getAttribute('data-rnw-mail') });
          S.renewals = res.renewals || [];
          S.renewalsSummary = res.renewals_summary || null;
          toast(res.message);
          renderRenewalsView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
          btn.textContent = orig;
        }
      });
    });

    ROOT.querySelectorAll('[data-rnw-del]').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Bu yenileme kaydını silmek istediğinize emin misiniz?')) return;
        try {
          const res = await api('renewal_delete', { id: btn.getAttribute('data-rnw-del') });
          S.renewals = res.renewals || [];
          S.renewalsSummary = res.renewals_summary || null;
          updateSidebarBadges();
          toast(res.message);
          renderRenewalsView();
        } catch (err) {
          toast(err.message, true);
        }
      });
    });
  }

  /* ======================== MODULE 15: MERKEZİ OTA GÜNCELLEME HUB & ÇOKLU SEKTÖR FİLO MERKEZİ (v5.2) ======================== */
  async function loadOtaOverviewSilent(forceReload) {
    if (S.otaLoading) return;
    if (S.ota && !forceReload) return;
    S.otaLoading = true;
    try {
      const res = await api('ota_overview', {});
      if (res.ota) S.ota = res.ota;
      if (res.ota_summary) S.otaSummary = res.ota_summary;
      updateSidebarBadges();
    } catch (err) {
      toast(err.message || 'OTA verileri alınamadı.', true);
    } finally {
      S.otaLoading = false;
    }
  }

  async function renderOtaHubView() {
    syncAdminUrl('replace');
    const validTabs = ['fleet', 'releases', 'client', 'logs'];
    const tab = validTabs.includes(S.tabs.ota_hub) ? S.tabs.ota_hub : 'fleet';
    S.tabs.ota_hub = tab;

    if (!S.ota && !S.otaLoading) {
      ROOT.innerHTML = `
        <div class="zs-card" style="text-align:center;padding:54px 20px">
          <span class="zs-badge-pill">MERKEZİ OTA KOMUTA MERKEZİ v5.2</span>
          <h3 style="margin:12px 0 6px">Kriptografik Anahtarlar, Sektör Sınırları &amp; Filo Durumu Hazırlanıyor...</h3>
          <p class="zs-card-sub">OpenSSL RSA-2048 imzalama motoru, özel <code>/x/</code> klasör eşleyicisi ve bağlı siteler yükleniyor.</p>
        </div>
      `;
      await loadOtaOverviewSilent(true);
    }

    const ota = S.ota || {};
    const hub = ota.hub || {};
    const client = ota.client || {};
    const nodes = Array.isArray(hub.nodes) ? hub.nodes : [];
    const releases = Array.isArray(hub.releases) ? hub.releases : [];
    const rollbacks = Array.isArray(ota.rollbacks) ? ota.rollbacks : [];
    const logs = Array.isArray(ota.logs) ? ota.logs : [];
    const stats = hub.fleet_stats || {};
    const studioVer = ota.studio_version || '5.2.0';
    const coreCount = Number(ota.core_files_count || 0);
    const detectedAdminDir = ota.detected_admin_dir || client.detected_admin_dir || CFG.adminDirName || 'admin';
    const activeAdminSlug = ota.active_admin_slug || client.active_admin_slug || CFG.adminSlug || detectedAdminDir;
    const localSectorExts = Array.isArray(ota.sector_extensions) ? ota.sector_extensions : [];
    const latestRel = releases[0] || null;
    const activeNodes = nodes.filter(n => (n.license_status || 'active') === 'active');
    const outdatedActiveNodes = activeNodes.filter(n => n.outdated);
    const editNode = S.otaEditingNode || null;

    const icoFleet = '<svg class="zs-tab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><path d="M10.5 7.5 6.5 16.5M13.5 7.5l4 9"/></svg>';
    const icoRel = '<svg class="zs-tab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
    const icoCli = '<svg class="zs-tab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>';
    const icoLog = '<svg class="zs-tab-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>';

    ROOT.innerHTML = `
      <div class="zs-section-head">
        <div>
          <span class="zs-badge-pill">🚀 MERKEZİ OTA GÜNCELLEME HUB &amp; ÇOKLU SEKTÖR FİLOSU · v5.2</span>
          <h2>Merkezi Otomatik Güncelleme (OTA Hub, Özel <code>/x/</code> Klasör &amp; Sektör Koruma Motoru)</h2>
          <p class="zs-card-sub">Ana stüdyoyu güncellediğinizde <strong>Emlak, Mücevher, Kulüp, Klinik, B2B</strong> gibi farklı sektörlerdeki tüm müşteri panellerini (panel adresi ister <code>/admin/</code> ister özel <code>/x/</code> klasörü olsun) <strong>RSA-2048 imzayla</strong> güvenle güncelleyin. Müşteriye özel <code>pages/</code>, <code>site_header/footer</code>, <code>extensions/</code>, <code>site.css</code>, <code>uploads/</code> ve <code>MySQL / data/</code> verilerine <strong>%100 dokunulmaz</strong>.</p>
        </div>
        <div class="zs-btn-group">
          <button type="button" class="zs-btn" id="otaRefreshBtn">↻ Durumu Yenile</button>
          <button type="button" class="zs-btn zs-btn-ai" id="otaQuickBuildBtn">📦 v${esc(studioVer)} Sürümünü Paketle</button>
          ${activeNodes.length > 0 ? `<button type="button" class="zs-btn zs-btn-primary" id="otaHeaderPushAllBtn">🚀 Tüm Filoyu Güncelle (${activeNodes.length})</button>` : ''}
        </div>
      </div>

      <div class="zs-kpi-grid" style="margin-bottom:16px">
        <div class="zs-kpi zs-kpi-emerald">
          <div class="zs-kpi-top"><span>AKTİF ÇEKİRDEK SÜRÜMÜ</span><span class="zs-kpi-icon">⚡</span></div>
          <strong>v${esc(studioVer)}</strong>
          <small>${coreCount} çekirdek dosya · RSA-2048 ${ota.openssl_ready ? 'Hazır' : 'Pasif'}</small>
        </div>
        <div class="zs-kpi zs-kpi-sky">
          <div class="zs-kpi-top"><span>BAĞLI MÜŞTERİ FİLOSU</span><span class="zs-kpi-icon">🛰️</span></div>
          <strong>${Number(stats.total_nodes || 0)} Site</strong>
          <small>${Number(stats.up_to_date_nodes || 0)} güncel · ${Number(stats.outdated_nodes || 0)} güncelleme bekliyor</small>
        </div>
        <div class="zs-kpi zs-kpi-mint">
          <div class="zs-kpi-top"><span>İMZALI SÜRÜM PAKETİ</span><span class="zs-kpi-icon">📦</span></div>
          <strong style="color:#6ee7b7">${latestRel ? 'v' + esc(latestRel.version) : 'Henüz Yok'}</strong>
          <small>${latestRel ? `${latestRel.size_kb} KB · ${releases.length} arşivli paket` : 'Sürüm Paketle sekmesinden oluşturun'}</small>
        </div>
        <div class="zs-kpi zs-kpi-violet">
          <div class="zs-kpi-top"><span>BU SİTENİN İSTEMCİ MODU</span><span class="zs-kpi-icon">🔗</span></div>
          <strong>${client.connected ? (client.update_available ? `v${esc(client.latest_remote_version)} Hazır` : 'Hub’a Bağlı') : 'Merkez Hub'}</strong>
          <small>${client.connected ? esc(client.hub_url || '') : `${rollbacks.length} geri dönüş yedeği hazır`}</small>
        </div>
      </div>

      <div class="zs-tabs" style="margin-bottom:18px">
        <button type="button" class="zs-tab ${tab === 'fleet' ? 'active' : ''}" data-otatab="fleet">${icoFleet}<span>Filo &amp; Bağlı Siteler (${nodes.length})</span></button>
        <button type="button" class="zs-tab ${tab === 'releases' ? 'active' : ''}" data-otatab="releases">${icoRel}<span>Sürüm Paketle &amp; Yayınla (${releases.length})</span></button>
        <button type="button" class="zs-tab ${tab === 'client' ? 'active' : ''}" data-otatab="client">${icoCli}<span>İstemci Oto-Güncelleme${client.update_available ? ' • YENİ' : ''}</span></button>
        <button type="button" class="zs-tab ${tab === 'logs' ? 'active' : ''}" data-otatab="logs">${icoLog}<span>Dağıtım &amp; İmza Kayıtları (${logs.length})</span></button>
      </div>

      <div id="otaBatchDeckSlot"></div>

      ${S.otaTokenSpot ? `
        <div class="zs-ota-token-spotlight" id="otaTokenSpotlightCard">
          <div class="zs-ota-token-head">
            <div>
              <span class="zs-badge-pill">🔑 TEK SATIRLIK MÜŞTERİ BAĞLANTI KODU HAZIR</span>
              <h3 style="margin:6px 0 3px">Bu Bağlantı Kodunu Müşteri Sitesinin Paneline Yapıştırın</h3>
              <p class="zs-card-sub" style="margin:0">Müşteri sitesinde <code>/admin/?view=ota_hub&amp;tab=client</code> sekmesini açıp aşağıdaki <code>ZYN5-OTA1:...</code> kodunu yapıştırdığınız anda RSA-2048 açık anahtar ve lisans el sıkışması otomatik tamamlanır.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-primary zs-btn-sm" id="otaCopySpotTokenBtn">📋 Bağlantı Kodunu Kopyala</button>
              <button type="button" class="zs-btn zs-btn-sm" id="otaCloseSpotTokenBtn">Kapat ✕</button>
            </div>
          </div>
          <textarea class="zs-textarea zs-ota-token-box" id="otaSpotTokenTextarea" readonly>${esc(S.otaTokenSpot)}</textarea>
        </div>
      ` : ''}

      ${tab === 'fleet' ? `
        <div class="zs-ota-fleet-layout">
          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">${editNode ? '✎ SİTE DÜZENLE' : '+ YENİ MÜŞTERİ SİTESİ'}</span>
                <h3 style="margin-top:6px">${editNode ? esc(editNode.client_name || editNode.domain) + ' Kaydını Düzenle' : 'OTA Filosuna Yeni Site Ekle'}</h3>
              </div>
              ${editNode ? `<button type="button" class="zs-btn zs-btn-sm" id="otaCancelEditNodeBtn">İptal</button>` : ''}
            </div>

            <form id="otaNodeForm" class="zs-ota-form-stack">
              <input type="hidden" id="otaNodeIdInp" value="${esc(editNode?.id || '')}"/>
              <div class="zs-field">
                <label for="otaClientNameInp">Müşteri / Marka Adı</label>
                <input id="otaClientNameInp" class="zs-input" required placeholder="Örn: Narin Reklam Kurumsal" value="${esc(editNode?.client_name || '')}"/>
              </div>
              <div class="zs-field">
                <label for="otaSiteUrlInp">Müşteri Site Adresi (HTTPS)</label>
                <input id="otaSiteUrlInp" class="zs-input" type="url" required placeholder="https://musterisitesi.com" value="${esc(editNode?.site_url || '')}"/>
              </div>
              <div class="zs-grid-2">
                <div class="zs-field" style="margin:0">
                  <label for="otaNodeChannelSel">Dağıtım Kanalı</label>
                  <select id="otaNodeChannelSel" class="zs-select">
                    <option value="stable" ${(editNode?.channel || 'stable') === 'stable' ? 'selected' : ''}>Kararlı (Stable)</option>
                    <option value="beta" ${editNode?.channel === 'beta' ? 'selected' : ''}>Önizleme (Beta)</option>
                  </select>
                </div>
                <div class="zs-field" style="margin:0">
                  <label for="otaNodeStatusSel">Lisans Durumu</label>
                  <select id="otaNodeStatusSel" class="zs-select">
                    <option value="active" ${(editNode?.license_status || 'active') === 'active' ? 'selected' : ''}>✓ Aktif Lisans</option>
                    <option value="suspended" ${editNode?.license_status === 'suspended' ? 'selected' : ''}>⏸ Askıda (Güncelleme Kapalı)</option>
                  </select>
                </div>
              </div>
              <div class="zs-field">
                <label for="otaNodeNotesInp">Kısa Not / Sunucu Bilgisi (Opsiyonel)</label>
                <input id="otaNodeNotesInp" class="zs-input" placeholder="Örn: Kurumsal Paket · LiteSpeed Sunucu" value="${esc(editNode?.notes || '')}"/>
              </div>
              ${editNode ? `
                <label class="zs-switch" style="margin-bottom:4px">
                  <input type="checkbox" id="otaRotateSecretChk"/>
                  <span style="font-size:12px">Gizli HMAC anahtarını yenile (Yeni Bağlantı Kodu üretir)</span>
                </label>
              ` : ''}
              <button type="submit" class="zs-btn zs-btn-primary" id="otaSaveNodeBtn" style="width:100%">
                ${editNode ? '✓ Değişiklikleri Kaydet & Kodu Güncelle' : '+ Filoya Ekle & Bağlantı Kodu Üret'}
              </button>
            </form>

            <div class="zs-ota-boundary-note" style="margin-top:14px">
              <strong>🛡️ %100 Çoklu Sektör &amp; Özel <code>/x/</code> Klasör Garantisi</strong>
              <p>OTA güncellemesi yalnızca paylaşılan Stüdyo Çekirdeğini yeniler ve karşı sitenin panel klasörü <code>/x/</code> veya <code>/yonetim/</code> ise dosyaları otomatik o klasöre eşler. <strong>Emlak, Mücevher, Kulüp</strong> gibi sektörel eklentilere (<code>extensions/</code>), <code>pages/</code>, <code>site_header/footer</code>, <code>site.css</code> ve <code>MySQL / data/</code> içeriklerine asla dokunmaz.</p>
            </div>
          </div>

          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:14px">
              <div>
                <h3 style="margin:0">Bağlı Müşteri Siteleri &amp; Filo Komuta Tablosu</h3>
                <p class="zs-card-sub">Tek tıkla bağlantı kontrolü (Ping), uzak LiteSpeed önbellek temizliği veya anlık çekirdek güncellemesi gönderin.</p>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                ${outdatedActiveNodes.length > 0 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="otaPushOutdatedBtn">⚡ Güncel Olmayanları Güncelle (${outdatedActiveNodes.length})</button>` : ''}
                ${activeNodes.length > 0 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-primary" id="otaPushAllFleetBtn">🚀 Tüm Aktif Siteleri Güncelle (${activeNodes.length})</button>` : ''}
              </div>
            </div>

            ${nodes.length === 0 ? `
              <div class="zs-ota-empty-box">
                <strong>Henüz OTA filosuna eklenmiş bir müşteri sitesi bulunmuyor.</strong>
                <p>Soldaki formdan ilk müşteri sitenizi ekleyin; üretilen tek satırlık <code>ZYN5-OTA1:...</code> bağlantı kodunu müşteri paneline yapıştırarak saniyeler içinde merkezi güncelleme ağına dahil edin.</p>
              </div>
            ` : `
              <div class="zs-table-wrap">
                <table class="zs-table">
                  <thead>
                    <tr>
                      <th>Müşteri &amp; Alan Adı</th>
                      <th>Lisans &amp; Kanal</th>
                      <th>Yüklü / Hedef Sürüm</th>
                      <th>Durum &amp; Son Erişim</th>
                      <th>Komutlar</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${nodes.map(n => {
                      const isSusp = n.license_status === 'suspended';
                      const isOnline = n.health_status === 'online';
                      const verOk = !n.outdated && n.installed_version && n.installed_version !== '—';
                      const nodeAdmDir = n.admin_dir || 'admin';
                      const nodeAdmSlug = n.admin_slug || nodeAdmDir;
                      const nodeExts = Array.isArray(n.sector_extensions) ? n.sector_extensions : [];
                      const sectorBadgeLabel = nodeExts.length
                        ? nodeExts.map(ex => ex.sector || ex.title || ex.id).join(' · ')
                        : 'Kurumsal Çekirdek';
                      return `
                        <tr>
                          <td>
                            <strong style="display:block;color:#ffffff;font-size:13.5px">${esc(n.client_name || n.domain)}</strong>
                            <a href="${esc(n.site_url)}" target="_blank" rel="noopener" style="font-size:12px;color:#5eead4">${esc(n.site_url)} ↗</a>
                            <div style="margin-top:4px;display:flex;gap:5px;flex-wrap:wrap;align-items:center">
                              <code style="padding:1px 6px;border-radius:5px;background:rgba(15,23,42,.82);color:#99f6e4;font-size:10.5px" title="Panel Giriş Adresi${nodeAdmDir !== nodeAdmSlug ? ' · Fiziksel Klasör: /' + esc(nodeAdmDir) + '/' : ''}">Panel: /${esc(nodeAdmSlug)}/${nodeAdmDir !== 'admin' && nodeAdmDir !== nodeAdmSlug ? ` (klasör: /${esc(nodeAdmDir)}/)` : ''}</code>
                              <span class="zs-pill zs-pill-cat" style="font-size:10.5px;padding:1px 7px">${esc(sectorBadgeLabel)}</span>
                            </div>
                            ${n.notes ? `<div style="font-size:11px;color:var(--text-dim);margin-top:3px">${esc(n.notes)}</div>` : ''}
                          </td>
                          <td>
                            <code style="display:inline-block;padding:2px 7px;border-radius:6px;background:rgba(4,12,18,.85);color:#bae6fd;font-size:11px">${esc(n.license_key)}</code>
                            <div style="margin-top:4px;display:flex;gap:5px;flex-wrap:wrap">
                              <span class="zs-pill ${isSusp ? 'zs-pill-draft' : 'zs-pill-pub'}">${isSusp ? 'Askıda' : 'Aktif'}</span>
                              <span class="zs-pill zs-pill-cat">${esc((n.channel || 'stable').toUpperCase())}</span>
                            </div>
                          </td>
                          <td>
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                              <strong style="color:${verOk ? '#6ee7b7' : '#fde68a'}">${n.installed_version && n.installed_version !== '—' ? 'v' + esc(n.installed_version) : 'Bekleniyor'}</strong>
                              <span style="color:var(--text-dim)">→</span>
                              <span style="font-size:12px;color:#cbd5e1">v${esc(n.target_version || studioVer)}</span>
                            </div>
                            <small style="color:var(--text-muted)">${verOk ? '✓ En güncel sürümde' : '⚡ Güncelleme hazır'}${n.php_version ? ` · PHP ${esc(n.php_version)}` : ''}${n.db_engine ? ` · ${esc(n.db_engine.toUpperCase())}` : ''}</small>
                          </td>
                          <td>
                            <span class="zs-ota-health-pill ${isOnline ? 'is-online' : (n.health_status === 'unreachable' ? 'is-err' : '')}">
                              ${isOnline ? '● Çevrimiçi' : (n.health_status === 'unreachable' ? '▲ Erişilemedi' : '○ El Sıkışma Bekleniyor')}
                            </span>
                            <div style="font-size:11px;color:var(--text-dim);margin-top:4px">${n.last_seen_at ? esc(n.last_seen_at) : 'Henüz sinyal yok'}</div>
                            ${n.last_push_result ? `<div style="font-size:11px;color:${String(n.last_push_result).startsWith('ok:') ? '#6ee7b7' : '#fca5a5'};margin-top:2px">${esc(n.last_push_result)}</div>` : ''}
                          </td>
                          <td>
                            <div class="zs-ota-row-actions">
                              <button type="button" class="zs-btn zs-btn-sm" data-ota-token="${esc(n.id)}" title="Tek Satırlık Bağlantı Kodunu Göster & Kopyala">🔑 Kod</button>
                              <button type="button" class="zs-btn zs-btn-sm" data-ota-ping="${esc(n.id)}" title="Canlı Bağlantı & Sürüm Kontrolü (Ping)">📡 Ping</button>
                              <button type="button" class="zs-btn zs-btn-sm" data-ota-purge="${esc(n.id)}" title="Uzak Sitenin LiteSpeed & OPcache Önbelleğini Temizle">⚡ Önbellek</button>
                              <button type="button" class="zs-btn zs-btn-sm zs-btn-primary" data-ota-push="${esc(n.id)}" ${isSusp ? 'disabled' : ''} title="İmzalı Çekirdek Paketini Bu Siteye Şimdi Gönder">🚀 Güncelle</button>
                              <button type="button" class="zs-btn zs-btn-sm" data-ota-edit="${esc(n.id)}" title="Düzenle">✎</button>
                              <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-ota-del="${esc(n.id)}" title="Filodan Çıkar">✕</button>
                            </div>
                          </td>
                        </tr>
                      `;
                    }).join('')}
                  </tbody>
                </table>
              </div>
            `}
          </div>
        </div>
      ` : ''}

      ${tab === 'releases' ? `
        <div class="zs-grid-2" style="align-items:start;margin-bottom:18px">
          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">📦 TEK TIKLA ÇEKİRDEK PAKETLEYİCİ</span>
                <h3 style="margin-top:6px">Yeni Stüdyo Sürümü Paketle &amp; RSA-2048 İmzala</h3>
                <p class="zs-card-sub">Bu sunucudaki güncel yönetim paneli ve çekirdek motor dosyalarını toplayıp kriptografik olarak imzalar.</p>
              </div>
            </div>

            <form id="otaBuildReleaseForm" class="zs-ota-form-stack">
              <div class="zs-grid-3">
                <div class="zs-field" style="margin:0">
                  <label for="otaRelVersionInp">Sürüm No (SemVer)</label>
                  <input id="otaRelVersionInp" class="zs-input" required value="${esc(studioVer)}" placeholder="5.1.0"/>
                </div>
                <div class="zs-field" style="margin:0">
                  <label for="otaRelChannelSel">Dağıtım Kanalı</label>
                  <select id="otaRelChannelSel" class="zs-select">
                    <option value="stable" ${(hub.default_channel || 'stable') === 'stable' ? 'selected' : ''}>Kararlı (Stable)</option>
                    <option value="beta" ${hub.default_channel === 'beta' ? 'selected' : ''}>Önizleme (Beta)</option>
                  </select>
                </div>
                <div class="zs-field" style="margin:0">
                  <label for="otaRelMinPhpInp">Minimum PHP</label>
                  <input id="otaRelMinPhpInp" class="zs-input" required value="8.1.0" placeholder="8.1.0"/>
                </div>
              </div>

              <div class="zs-field">
                <label for="otaRelTitleInp">Sürüm Başlığı</label>
                <input id="otaRelTitleInp" class="zs-input" value="Dijital Stüdyo v${esc(studioVer)} — Çoklu Sektör &amp; Merkezi OTA Çekirdek Güncellemesi"/>
              </div>

              <div class="zs-field">
                <label for="otaRelChangelogInp">Değişiklik Notları (Her satıra bir yenilik maddesi yazın)</label>
                <textarea id="otaRelChangelogInp" class="zs-textarea" style="min-height:108px">Çoklu sektör eklenti kancası (Emlak, Mücevher, Kulüp, Klinik, B2B) ve %100 vitrin koruması
Özel /x/ fiziksel klasör ve gizli giriş adresli sitelere otomatik OTA dosya eşleme
Admin panel performans, güvenlik, MySQL ve LiteSpeed LSCache iyileştirmeleri</textarea>
              </div>

              <button type="submit" class="zs-btn zs-btn-primary" id="otaBuildSubmitBtn" style="width:100%;min-height:46px;font-size:14px">
                📦 Çekirdeği Paketle (${coreCount} Dosya) &amp; RSA-2048 İle İmzala
              </button>
            </form>

            <div class="zs-ota-boundary-grid" style="margin-top:14px">
              <div class="zs-ota-boundary-col is-inc">
                <strong>✓ Pakete Dahil Çekirdek (Oto <code>/x/</code> Eşlemeli)</strong>
                <code>/{panel}/* · chunks/* · 23 çekirdek includes/* · 13 çekirdek api/* · lib/* · live-studio.*</code>
              </div>
              <div class="zs-ota-boundary-col is-prot">
                <strong>🛡️ %100 Korunan Vitrin &amp; Sektör Modülleri</strong>
                <code>pages/* · site_header/footer · icons/i18n · extensions/* · site.css · uploads/* · data/*</code>
              </div>
            </div>
          </div>

          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">🔐 KRİPTOGRAFİK İMZA &amp; HUB AYARLARI</span>
                <h3 style="margin-top:6px">OpenSSL RSA-2048 İmza Anahtarı &amp; Dağıtım Kuralları</h3>
              </div>
              <span class="zs-pill ${hub.enabled !== false ? 'zs-pill-pub' : 'zs-pill-draft'}">${hub.enabled !== false ? 'Hub Aktif' : 'Hub Pasif'}</span>
            </div>

            <div class="zs-ota-key-card" style="margin-bottom:14px">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                <span style="font-size:11.5px;color:var(--text-muted);font-weight:700">AKTİF AÇIK ANAHTAR PARMAK İZİ (FINGERPRINT)</span>
                <button type="button" class="zs-btn zs-btn-sm" id="otaCopyPubKeyBtn">📋 Açık Anahtarı Kopyala</button>
              </div>
              <code class="zs-ota-fp-code">${esc(hub.key_fingerprint || 'Henüz üretilmedi')}</code>
              <div style="margin-top:8px;font-size:11.5px;color:var(--text-muted)">
                Hub Uç Noktası: <code style="color:#5eead4">${esc(hub.hub_endpoint || (CFG.siteUrl + '/api/ota-hub'))}</code>
              </div>
            </div>

            <div style="display:grid;gap:10px;margin-bottom:14px">
              <label class="zs-switch" style="margin:0;padding:10px 13px;border-radius:11px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.22)">
                <input type="checkbox" id="otaHubEnabledChk" ${hub.enabled !== false ? 'checked' : ''}/>
                <span><strong>Merkezi OTA Dağıtım Hub Servisi Açık</strong><br/><small style="color:var(--text-muted)">Bağlı müşteri sitelerinin sürüm sorgulamasına ve imzalı paket indirmesine izin verir.</small></span>
              </label>

              <label class="zs-switch" style="margin:0;padding:10px 13px;border-radius:11px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.22)">
                <input type="checkbox" id="otaHubInlinePushChk" ${hub.inline_push_fallback !== false ? 'checked' : ''}/>
                <span><strong>Doğrudan İmzalı Paket İletimi (NAT &amp; Paylaşımlı Hosting Uyumu)</strong><br/><small style="color:var(--text-muted)">4 MB altı imzalı çekirdek paketini tek HTTPS isteğiyle doğrudan iletir; geri bağlantı engellerini aşar.</small></span>
              </label>

              <label class="zs-switch" style="margin:0;padding:10px 13px;border-radius:11px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.22)">
                <input type="checkbox" id="otaHubHttpsChk" ${hub.require_https !== false ? 'checked' : ''}/>
                <span><strong>Zorunlu HTTPS Kanal Güvenliği</strong><br/><small style="color:var(--text-muted)">Üretim ortamında şifresiz HTTP üzerinden güncelleme trafiğini reddeder.</small></span>
              </label>
            </div>

            <div class="zs-field">
              <label for="otaRetainCountInp">Arşivde Tutulacak Maksimum Sürüm Paketi (2 – 10)</label>
              <input id="otaRetainCountInp" class="zs-input" type="number" min="2" max="10" value="${Number(hub.retain_releases || 5)}"/>
            </div>

            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
              <button type="button" class="zs-btn zs-btn-danger zs-btn-sm" id="otaRegenKeysBtn">🔐 RSA-2048 Anahtar Çiftini Yenile</button>
              <button type="button" class="zs-btn zs-btn-primary" id="otaSaveHubSettingsBtn">✓ Hub Ayarlarını Kaydet</button>
            </div>
          </div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">İmzalı OTA Sürüm Paketleri Arşivi</h3>
              <p class="zs-card-sub">Oluşturulan her paket SHA-256 özeti ve RSA-2048 dijital imzasıyla <code>data/ota_releases/</code> kasasında saklanır.</p>
            </div>
          </div>

          ${releases.length === 0 ? `
            <div class="zs-ota-empty-box">Henüz paketlenmiş bir OTA sürümü bulunmuyor. Yukarıdaki <strong>Çekirdeği Paketle &amp; RSA-2048 İle İmzala</strong> butonuna tıklayarak ilk dağıtım paketinizi oluşturun.</div>
          ` : `
            <div class="zs-table-wrap">
              <table class="zs-table">
                <thead>
                  <tr>
                    <th>Sürüm &amp; Kanal</th>
                    <th>Sürüm Başlığı &amp; Kapsam</th>
                    <th>Boyut &amp; SHA-256 Özeti</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                  </tr>
                </thead>
                <tbody>
                  ${releases.map(r => `
                    <tr>
                      <td>
                        <strong style="font-size:14.5px;color:#5eead4">v${esc(r.version)}</strong>
                        <span class="zs-pill zs-pill-cat" style="margin-left:6px">${esc((r.channel || 'stable').toUpperCase())}</span>
                      </td>
                      <td>
                        <strong style="display:block;color:#ffffff">${esc(r.title || ('Stüdyo v' + r.version))}</strong>
                        <small style="color:var(--text-muted)">${Number(r.files_count || 0)} çekirdek dosya · Min PHP ${esc(r.min_php || '8.1.0')}</small>
                      </td>
                      <td>
                        <strong style="color:#e2e8f0">${esc(String(r.size_kb || 0))} KB</strong>
                        <code style="display:block;font-size:11px;color:#94a3b8;margin-top:2px" title="${esc(r.sha256 || '')}">SHA256: ${esc(String(r.sha256 || '').slice(0, 20))}…</code>
                      </td>
                      <td style="font-size:12px;color:var(--text-muted)">${esc((r.created_at || '').replace('T', ' ').slice(0, 19))}</td>
                      <td>
                        <div class="zs-ota-row-actions">
                          <a class="zs-btn zs-btn-sm" href="${esc(CFG.apiUrl + '?action=ota_download_release&id=' + encodeURIComponent(r.id) + '&csrf_token=' + encodeURIComponent(CFG.csrfToken || ''))}">⬇️ .ZIP İndir</a>
                          ${activeNodes.length > 0 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-primary" data-ota-push-rel="${esc(r.id)}">🚀 Filoya Gönder</button>` : ''}
                          <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" data-ota-del-rel="${esc(r.id)}">🗑 Sil</button>
                        </div>
                      </td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
      ` : ''}

      ${tab === 'client' ? `
        <div class="zs-grid-2" style="align-items:start;margin-bottom:18px">
          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">🔗 MERKEZİ HUB BAĞLANTISI</span>
                <h3 style="margin-top:6px">Tek Satırlık Bağlantı Kodu (<code>ZYN5-OTA1</code>) ile Bağlan</h3>
                <p class="zs-card-sub">Bu site bir müşteri kurulumuysa, ana Zeynart Hub panelinden aldığınız bağlantı kodunu aşağıya yapıştırın.</p>
              </div>
              <span class="zs-pill ${client.connected ? 'zs-pill-pub' : 'zs-pill-cat'}">${client.connected ? '✓ Hub’a Bağlı' : 'Bağlı Değil'}</span>
            </div>

            ${client.connected ? `
              <div class="zs-ota-connected-banner" style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap">
                  <div>
                    <strong style="color:#6ee7b7;font-size:14px">✓ Merkezi OTA Hub Bağlantısı Aktif</strong>
                    <div style="font-size:12.5px;color:#e2e8f0;margin-top:4px">Merkez: <code>${esc(client.hub_url)}</code> · Lisans: <code>${esc(client.license_key)}</code></div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">RSA Parmak İzi: <code>${esc(client.key_fingerprint || '')}</code></div>
                    ${client.last_checked_at ? `<div style="font-size:11.5px;color:var(--text-dim);margin-top:3px">Son Kontrol: ${esc(client.last_checked_at)}${client.last_updated_at ? ` · Son Güncelleme: ${esc(client.last_updated_at)}` : ''}</div>` : ''}
                  </div>
                  <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" id="otaClientCheckNowBtn">🔍 Şimdi Kontrol Et</button>
                    <button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="otaClientDisconnectBtn">Bağlantıyı Kes</button>
                  </div>
                </div>
                ${client.last_error ? `<div style="margin-top:10px;padding:8px 11px;border-radius:9px;background:rgba(239,68,68,.16);border:1px solid rgba(248,113,113,.4);color:#fecaca;font-size:12px">⚠ Son Bağlantı Uyarısı: ${esc(client.last_error)}</div>` : ''}
              </div>
            ` : ''}

            ${client.connected && client.update_available && client.latest_manifest ? `
              <div class="zs-ota-update-ready-card" style="margin-bottom:14px">
                <span class="zs-badge-pill">⚡ YENİ İMZALI SÜRÜM HAZIR</span>
                <h4 style="margin:8px 0 4px;color:#ffffff;font-size:16px">${esc(client.latest_manifest.title || ('Stüdyo v' + client.latest_remote_version))}</h4>
                <p style="margin:0 0 10px;font-size:12.5px;color:#cbd5e1">Mevcut Sürüm: <strong>v${esc(client.installed_version || studioVer)}</strong> → Yeni Sürüm: <strong style="color:#5eead4">v${esc(client.latest_remote_version)}</strong></p>
                ${Array.isArray(client.latest_manifest.changelog) && client.latest_manifest.changelog.length ? `
                  <ul style="margin:0 0 12px;padding-left:18px;font-size:12px;color:#e2e8f0;line-height:1.55">
                    ${client.latest_manifest.changelog.map(line => `<li>${esc(line)}</li>`).join('')}
                  </ul>
                ` : ''}
                <button type="button" class="zs-btn zs-btn-primary" id="otaClientApplyUpdateBtn" style="width:100%;min-height:44px">
                  🚀 Şimdi v${esc(client.latest_remote_version)} Sürümüne Güvenle Güncelle (Otomatik Yedekli)
                </button>
              </div>
            ` : (client.connected ? `
              <div style="padding:12px 14px;border-radius:12px;background:rgba(5,15,21,.75);border:1px solid rgba(148,210,214,.18);margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                <div>
                  <strong style="color:#f1f5f9;font-size:13px">Yüklü Çekirdek: v${esc(client.installed_version || studioVer)}</strong>
                  <div style="font-size:11.5px;color:var(--text-muted)">${client.latest_remote_version ? `Hub üzerindeki son sürüm: v${esc(client.latest_remote_version)}` : 'Güncellemeleri kontrol ederek son sürümü sorgulayın.'}</div>
                </div>
                ${client.latest_remote_version ? `<button type="button" class="zs-btn zs-btn-sm" id="otaClientReinstallBtn">↻ Çekirdeği Yeniden Eşitle</button>` : ''}
              </div>
            ` : '')}

            <div class="zs-field">
              <label for="otaConnTokenInp">${client.connected ? 'Bağlantı Kodunu Yenile / Değiştir' : 'Merkezi Hub Bağlantı Kodu (ZYN5-OTA1:...)'}</label>
              <textarea id="otaConnTokenInp" class="zs-textarea" style="min-height:84px;font-family:Consolas,monospace;font-size:12px" placeholder="ZYN5-OTA1:eyJ2IjoxLCJodWJfdXJsIjoi..."></textarea>
            </div>
            <button type="button" class="zs-btn zs-btn-primary" id="otaConnectTokenBtn" style="width:100%">
              🔗 Bağlantı Kodunu Doğrula &amp; Merkezi Hub’a Bağlan
            </button>
          </div>

          <div class="zs-card" style="margin-bottom:0">
            <div class="zs-card-head" style="margin-bottom:12px">
              <div>
                <span class="zs-badge-pill">⚙️ OTO-GÜNCELLEME &amp; KORUMA SINIRLARI</span>
                <h3 style="margin-top:6px">İstemci Güncelleme Politikası</h3>
                <p class="zs-card-sub">Uzaktan güncelleme iznini, arka plan otomatik güncelleme modunu ve korunan dizinleri belirleyin.</p>
              </div>
            </div>

            <div class="zs-grid-2" style="margin-bottom:12px">
              <div class="zs-field" style="margin:0">
                <label for="otaCliAutoModeSel">Arka Plan Otomatik Güncelleme</label>
                <select id="otaCliAutoModeSel" class="zs-select">
                  <option value="off" ${(client.auto_update || 'off') === 'off' ? 'selected' : ''}>Kapalı (Yalnızca Tek Tık / Uzaktan Push)</option>
                  <option value="patch" ${client.auto_update === 'patch' ? 'selected' : ''}>Yalnızca Ara / Güvenlik Yamaları (5.1.x)</option>
                  <option value="all" ${client.auto_update === 'all' ? 'selected' : ''}>Tam Otomatik (Tüm Yeni Kararlı Sürümler)</option>
                </select>
              </div>
              <div class="zs-field" style="margin:0">
                <label for="otaCliChannelSel">Takip Edilen Kanal</label>
                <select id="otaCliChannelSel" class="zs-select">
                  <option value="stable" ${(client.channel || 'stable') === 'stable' ? 'selected' : ''}>Kararlı (Stable)</option>
                  <option value="beta" ${client.channel === 'beta' ? 'selected' : ''}>Önizleme (Beta)</option>
                </select>
              </div>
            </div>

            <label class="zs-switch" style="margin:0 0 14px;padding:11px 14px;border-radius:11px;background:rgba(20,184,166,.08);border:1px solid rgba(94,234,212,.22)">
              <input type="checkbox" id="otaCliAllowPushChk" ${client.allow_remote_push !== false ? 'checked' : ''}/>
              <span><strong>Merkezi Hub’dan Uzaktan Tek Tıkla Güncelleme (Remote Push) Kabul Et</strong><br/><small style="color:var(--text-muted)">Ana Zeynart panelinde “Tüm Siteleri Güncelle” butonuna basıldığında imzalı paketi anında doğrular ve kurar.</small></span>
            </label>

            <div style="padding:11px 14px;border-radius:11px;background:rgba(15,23,42,.58);border:1px solid rgba(94,234,212,.22);margin-bottom:12px;font-size:12px">
              <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                <strong style="color:#5eead4">📂 Bu Sitenin Yönetim Klasörü &amp; Sektör Modülleri</strong>
                <code style="color:#e2e8f0">Fiziksel: /${esc(detectedAdminDir)}/ · Giriş: /${esc(activeAdminSlug)}/</code>
              </div>
              <div style="color:var(--text-muted)">
                ${localSectorExts.length
                  ? `Aktif Sektör Eklentileri (OTA sırasında %100 korunur): <strong style="color:#f8fafc">${localSectorExts.map(ex => esc(`${ex.title} (${ex.sector})`)).join(', ')}</strong>`
                  : 'Ek sektör eklentisi yok (Standart Kurumsal Çekirdek). Sektöre özel modülleri <code>includes/extensions/*.php</code> ve <code>/' + esc(detectedAdminDir) + '/extensions/*.js</code> altına ekleyebilirsiniz.'}
              </div>
            </div>

            <div class="zs-field">
              <label for="otaCliProtectedInp">Ek Korunan Özel Dosya Yolları (<code>pages/, site_header/footer, extensions/, site.css, uploads/, data/</code> her zaman kilitli korunur)</label>
              <textarea id="otaCliProtectedInp" class="zs-textarea" style="min-height:82px;font-family:Consolas,monospace;font-size:12px" placeholder="Özel korunmasını istediğiniz ek yolları satır satır yazabilirsiniz...">${esc((client.protected_paths || []).join('\n'))}</textarea>
            </div>

            <button type="button" class="zs-btn zs-btn-primary" id="otaSaveClientCfgBtn" style="width:100%">
              ✓ İstemci Otomatik Güncelleme Ayarlarını Kaydet
            </button>
          </div>
        </div>

        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">⏪ Güncelleme Öncesi Otomatik Geri Dönüş Kasası (<code>pre-ota-*.zip</code>)</h3>
              <p class="zs-card-sub">Her OTA güncellemesinden hemen önce mevcut çekirdek dosyalarınız otomatik yedeklenir (en son 3 sürüm tutulur). Tek tıkla önceki çekirdeğe dönebilirsiniz.</p>
            </div>
          </div>

          ${rollbacks.length === 0 ? `
            <div class="zs-ota-empty-box">Henüz oluşturulmuş bir OTA öncesi geri dönüş yedeği bulunmuyor. İlk OTA güncellemesi uygulandığında otomatik olarak burada listelenir.</div>
          ` : `
            <div class="zs-table-wrap">
              <table class="zs-table">
                <thead>
                  <tr>
                    <th>Yedek Arşivi</th>
                    <th>Korunan Önceki Sürüm</th>
                    <th>Arşiv Boyutu</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>İşlem</th>
                  </tr>
                </thead>
                <tbody>
                  ${rollbacks.map(rb => `
                    <tr>
                      <td><code style="color:#5eead4">${esc(rb.filename)}</code></td>
                      <td><strong>${rb.previous_version ? 'v' + esc(rb.previous_version) : 'Önceki Çekirdek'}</strong></td>
                      <td>${esc(String(rb.size_kb || 0))} KB</td>
                      <td>${esc(rb.created_at || '')}</td>
                      <td>
                        <button type="button" class="zs-btn zs-btn-sm zs-btn-ai" data-ota-rollback="${esc(rb.filename)}">⏪ Bu Sürüme Geri Dön</button>
                      </td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
      ` : ''}

      ${tab === 'logs' ? `
        <div class="zs-card">
          <div class="zs-card-head" style="margin-bottom:12px">
            <div>
              <h3 style="margin:0">🛡️ Kriptografik İmza, Paketleme &amp; Filo Dağıtım Kayıtları</h3>
              <p class="zs-card-sub">Tüm RSA-2048 doğrulama işlemleri, el sıkışmalar, uzaktan filo güncellemeleri ve geri dönüş (Rollback) olayları kayıt altındadır.</p>
            </div>
            ${logs.length > 0 ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="otaClearLogsBtn">🧹 Kayıtları Temizle</button>` : ''}
          </div>

          ${logs.length === 0 ? `
            <div class="zs-ota-empty-box">Henüz kaydedilmiş bir OTA dağıtım veya doğrulama olayı bulunmuyor.</div>
          ` : `
            <div class="zs-table-wrap">
              <table class="zs-table">
                <thead>
                  <tr>
                    <th>Tarih &amp; Saat</th>
                    <th>Olay Kodu</th>
                    <th>Durum</th>
                    <th>Açıklama</th>
                  </tr>
                </thead>
                <tbody>
                  ${logs.map(lg => {
                    const lgTime = (lg.created_at || lg.time || '').replace('T', ' ').slice(0, 19);
                    const lgLvl = lg.level || lg.status || 'info';
                    const isOk = lgLvl === 'ok' || lgLvl === 'info';
                    const isErr = lgLvl === 'error';
                    return `
                    <tr>
                      <td style="white-space:nowrap;font-size:12px;color:var(--text-muted)">${esc(lgTime)}</td>
                      <td><code style="font-size:11.5px;color:#bae6fd">${esc(lg.event || '')}</code></td>
                      <td>
                        <span class="zs-pill ${isOk ? 'zs-pill-pub' : (isErr ? 'zs-pill-draft' : 'zs-pill-cat')}">
                          ${isOk ? '✓ Başarılı' : (isErr ? '✕ Hata' : '⚠ Uyarı')}
                        </span>
                      </td>
                      <td style="color:#f1f5f9">${esc(lg.message || '')}</td>
                    </tr>
                  `;
                  }).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
      ` : ''}
    `;

    ensureConsolidatedFamilyBar();

    const applyApiOtaPayload = res => {
      if (res && res.ota) S.ota = res.ota;
      if (res && res.ota_summary) S.otaSummary = res.ota_summary;
      updateSidebarBadges();
    };

    const runFleetSequentialBatch = async (targetList, releaseId) => {
      if (!targetList.length || S.otaBatchRunning) return;
      S.otaBatchRunning = true;
      S.otaBatchStop = false;
      const deckSlot = document.getElementById('otaBatchDeckSlot');
      let done = 0;
      let okCnt = 0;
      let failCnt = 0;

      const paintDeck = (activeDomain, finished) => {
        if (!deckSlot) return;
        const pct = Math.round((done / targetList.length) * 100);
        deckSlot.innerHTML = `
          <div class="zs-ota-batch-deck">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
              <div>
                <strong style="color:#ffffff;font-size:14px">${finished ? '✓ Filo Güncelleme Kuyruğu Tamamlandı' : `🚀 Filo Güncelleniyor: ${esc(activeDomain || '')}`}</strong>
                <div style="font-size:12px;color:#cbd5e1;margin-top:2px">${done} / ${targetList.length} site işlendi · <span style="color:#6ee7b7">${okCnt} başarılı</span>${failCnt > 0 ? ` · <span style="color:#fca5a5">${failCnt} hatalı</span>` : ''}</div>
              </div>
              <div style="display:flex;align-items:center;gap:10px">
                <strong style="font-size:18px;color:#5eead4">%${pct}</strong>
                ${!finished ? `<button type="button" class="zs-btn zs-btn-sm zs-btn-danger" id="otaStopBatchBtn">Durdur</button>` : ''}
              </div>
            </div>
            <div class="zs-upload-master-track"><div class="zs-upload-master-fill" style="width:${Math.max(4, pct)}%"></div></div>
          </div>
        `;
        deckSlot.querySelector('#otaStopBatchBtn')?.addEventListener('click', () => {
          S.otaBatchStop = true;
        });
      };

      paintDeck(targetList[0]?.domain || '', false);

      // Ensure at least one signed release exists before sequential push
      if (!releases.length && !releaseId) {
        try {
          const bRes = await api('ota_build_release', { version: studioVer, channel: 'stable' });
          applyApiOtaPayload(bRes);
        } catch (err) {
          S.otaBatchRunning = false;
          toast(err.message, true);
          renderOtaHubView();
          return;
        }
      }

      for (const item of targetList) {
        if (S.otaBatchStop) break;
        paintDeck(item.domain || item.site_url, false);
        try {
          const r = await api('ota_push_single', { id: item.id, release_id: releaseId || '' });
          applyApiOtaPayload(r);
          okCnt++;
        } catch (err) {
          failCnt++;
        }
        done++;
        paintDeck(item.domain || item.site_url, false);
      }

      S.otaBatchRunning = false;
      await loadOtaOverviewSilent(true);
      toast(`Filo dağıtımı tamamlandı: ${okCnt} başarılı, ${failCnt} hatalı.`);
      renderOtaHubView();
    };

    ROOT.querySelectorAll('[data-otatab]').forEach(btn => {
      btn.onclick = () => {
        S.tabs.ota_hub = btn.getAttribute('data-otatab') || 'fleet';
        renderOtaHubView();
      };
    });

    document.getElementById('otaRefreshBtn')?.addEventListener('click', async function () {
      this.disabled = true;
      this.textContent = '⏳ Yenileniyor...';
      await loadOtaOverviewSilent(true);
      renderOtaHubView();
    });

    document.getElementById('otaQuickBuildBtn')?.addEventListener('click', async function () {
      const btn = this;
      btn.disabled = true;
      btn.textContent = '⏳ Paketleniyor & İmzalanıyor...';
      try {
        const res = await api('ota_build_release', {
          version: studioVer,
          channel: 'stable',
          min_php: '8.1.0',
          title: `Zeynart Dijital Stüdyo v${studioVer} — Merkezi OTA Çekirdek Paketi`
        });
        applyApiOtaPayload(res);
        toast(res.message);
        S.tabs.ota_hub = 'releases';
        renderOtaHubView();
      } catch (err) {
        toast(err.message, true);
        btn.disabled = false;
        btn.textContent = `📦 v${studioVer} Sürümünü Paketle`;
      }
    });

    document.getElementById('otaHeaderPushAllBtn')?.addEventListener('click', () => {
      if (!confirm(`Aktif lisansa sahip ${activeNodes.length} müşteri sitesine imzalı çekirdek güncellemesi gönderilsin mi?`)) return;
      S.tabs.ota_hub = 'fleet';
      runFleetSequentialBatch(activeNodes, '');
    });

    document.getElementById('otaCopySpotTokenBtn')?.addEventListener('click', () => {
      copyTextSafe(S.otaTokenSpot, 'Bağlantı Kodu (ZYN5-OTA1) panoya kopyalandı!');
    });
    document.getElementById('otaCloseSpotTokenBtn')?.addEventListener('click', () => {
      S.otaTokenSpot = '';
      renderOtaHubView();
    });

    if (tab === 'fleet') {
      document.getElementById('otaCancelEditNodeBtn')?.addEventListener('click', () => {
        S.otaEditingNode = null;
        renderOtaHubView();
      });

      document.getElementById('otaNodeForm')?.addEventListener('submit', async ev => {
        ev.preventDefault();
        const btn = document.getElementById('otaSaveNodeBtn');
        if (btn) {
          btn.disabled = true;
          btn.textContent = '⏳ Kaydediliyor...';
        }
        try {
          const res = await api('ota_node_save', {
            id: document.getElementById('otaNodeIdInp')?.value.trim() || '',
            client_name: document.getElementById('otaClientNameInp')?.value.trim() || '',
            site_url: document.getElementById('otaSiteUrlInp')?.value.trim() || '',
            channel: document.getElementById('otaNodeChannelSel')?.value || 'stable',
            license_status: document.getElementById('otaNodeStatusSel')?.value || 'active',
            notes: document.getElementById('otaNodeNotesInp')?.value.trim() || '',
            rotate_secret: Boolean(document.getElementById('otaRotateSecretChk')?.checked)
          });
          applyApiOtaPayload(res);
          S.otaEditingNode = null;
          S.otaTokenSpot = res.connection_token || '';
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
          if (btn) {
            btn.disabled = false;
            btn.textContent = editNode ? '✓ Değişiklikleri Kaydet & Kodu Güncelle' : '+ Filoya Ekle & Bağlantı Kodu Üret';
          }
        }
      });

      document.getElementById('otaPushOutdatedBtn')?.addEventListener('click', () => {
        if (!confirm(`Güncelleme bekleyen ${outdatedActiveNodes.length} müşteri sitesi şimdi güncellensin mi?`)) return;
        runFleetSequentialBatch(outdatedActiveNodes, '');
      });

      document.getElementById('otaPushAllFleetBtn')?.addEventListener('click', () => {
        if (!confirm(`Filodaki tüm aktif siteler (${activeNodes.length} site) şimdi güncellensin mi?`)) return;
        runFleetSequentialBatch(activeNodes, '');
      });

      ROOT.querySelectorAll('[data-ota-token]').forEach(btn => {
        btn.onclick = () => {
          const id = btn.getAttribute('data-ota-token');
          const found = nodes.find(x => x.id === id);
          if (!found || !found.connection_token) return;
          S.otaTokenSpot = found.connection_token;
          copyTextSafe(found.connection_token, `${found.domain} Bağlantı Kodu kopyalandı!`);
          renderOtaHubView();
        };
      });

      ROOT.querySelectorAll('[data-ota-edit]').forEach(btn => {
        btn.onclick = () => {
          const id = btn.getAttribute('data-ota-edit');
          S.otaEditingNode = nodes.find(x => x.id === id) || null;
          renderOtaHubView();
        };
      });

      ROOT.querySelectorAll('[data-ota-del]').forEach(btn => {
        btn.onclick = async () => {
          const id = btn.getAttribute('data-ota-del');
          const found = nodes.find(x => x.id === id);
          if (!confirm(`${found?.domain || 'Bu siteyi'} OTA filosundan çıkarmak istediğinize emin misiniz?`)) return;
          try {
            const res = await api('ota_node_delete', { id });
            applyApiOtaPayload(res);
            if (S.otaEditingNode?.id === id) S.otaEditingNode = null;
            toast(res.message);
            renderOtaHubView();
          } catch (err) {
            toast(err.message, true);
          }
        };
      });

      ROOT.querySelectorAll('[data-ota-ping]').forEach(btn => {
        btn.onclick = async () => {
          const id = btn.getAttribute('data-ota-ping');
          const orig = btn.textContent;
          btn.disabled = true;
          btn.textContent = '⏳...';
          try {
            const res = await api('ota_node_ping', { id });
            applyApiOtaPayload(res);
            toast(res.message);
            renderOtaHubView();
          } catch (err) {
            await loadOtaOverviewSilent(true);
            toast(err.message, true);
            renderOtaHubView();
          } finally {
            btn.disabled = false;
            btn.textContent = orig;
          }
        };
      });

      ROOT.querySelectorAll('[data-ota-purge]').forEach(btn => {
        btn.onclick = async () => {
          const id = btn.getAttribute('data-ota-purge');
          const orig = btn.textContent;
          btn.disabled = true;
          btn.textContent = '⏳...';
          try {
            const res = await api('ota_node_purge_cache', { id });
            applyApiOtaPayload(res);
            toast(res.message);
          } catch (err) {
            toast(err.message, true);
          } finally {
            btn.disabled = false;
            btn.textContent = orig;
          }
        };
      });

      ROOT.querySelectorAll('[data-ota-push]').forEach(btn => {
        btn.onclick = async () => {
          const id = btn.getAttribute('data-ota-push');
          const found = nodes.find(x => x.id === id);
          if (!confirm(`${found?.domain || 'Seçili site'} uzaktan imzalı çekirdek paketiyle güncellensin mi?`)) return;
          const orig = btn.textContent;
          btn.disabled = true;
          btn.textContent = '⏳ Güncelleniyor...';
          try {
            const res = await api('ota_push_single', { id });
            applyApiOtaPayload(res);
            toast(res.message);
            renderOtaHubView();
          } catch (err) {
            await loadOtaOverviewSilent(true);
            toast(err.message, true);
            renderOtaHubView();
          } finally {
            btn.disabled = false;
            btn.textContent = orig;
          }
        };
      });
    }

    if (tab === 'releases') {
      document.getElementById('otaBuildReleaseForm')?.addEventListener('submit', async ev => {
        ev.preventDefault();
        const btn = document.getElementById('otaBuildSubmitBtn');
        if (btn) {
          btn.disabled = true;
          btn.textContent = '⏳ Çekirdek Dosyaları Zipleniyor & RSA-2048 İmzalanıyor...';
        }
        try {
          const res = await api('ota_build_release', {
            version: document.getElementById('otaRelVersionInp')?.value.trim() || studioVer,
            channel: document.getElementById('otaRelChannelSel')?.value || 'stable',
            min_php: document.getElementById('otaRelMinPhpInp')?.value.trim() || '8.1.0',
            title: document.getElementById('otaRelTitleInp')?.value.trim() || '',
            changelog: document.getElementById('otaRelChangelogInp')?.value || ''
          });
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
          if (btn) {
            btn.disabled = false;
            btn.textContent = `📦 Çekirdeği Paketle (${coreCount} Dosya) & RSA-2048 İle İmzala`;
          }
        }
      });

      document.getElementById('otaCopyPubKeyBtn')?.addEventListener('click', () => {
        copyTextSafe(hub.public_key_pem || '', 'RSA-2048 Açık Anahtar (PEM) panoya kopyalandı!');
      });

      document.getElementById('otaSaveHubSettingsBtn')?.addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        try {
          const res = await api('ota_hub_save_settings', {
            enabled: Boolean(document.getElementById('otaHubEnabledChk')?.checked),
            inline_push_fallback: Boolean(document.getElementById('otaHubInlinePushChk')?.checked),
            require_https: Boolean(document.getElementById('otaHubHttpsChk')?.checked),
            default_channel: document.getElementById('otaRelChannelSel')?.value || 'stable',
            retain_releases: Number(document.getElementById('otaRetainCountInp')?.value || 5)
          });
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      });

      document.getElementById('otaRegenKeysBtn')?.addEventListener('click', async function () {
        if (!confirm('Yeni bir RSA-2048 imzalama anahtar çifti üretilsin mi? Anahtar değiştiğinde mevcut bağlı siteler için yeni Bağlantı Kodu paylaşmanız gerekir.')) return;
        const btn = this;
        btn.disabled = true;
        try {
          const res = await api('ota_hub_regenerate_keys', {});
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      });

      ROOT.querySelectorAll('[data-ota-push-rel]').forEach(btn => {
        btn.onclick = () => {
          const relId = btn.getAttribute('data-ota-push-rel') || '';
          if (!confirm(`Seçili paket tüm aktif müşteri sitelerine (${activeNodes.length} site) gönderilsin mi?`)) return;
          S.tabs.ota_hub = 'fleet';
          runFleetSequentialBatch(activeNodes, relId);
        };
      });

      ROOT.querySelectorAll('[data-ota-del-rel]').forEach(btn => {
        btn.onclick = async () => {
          const id = btn.getAttribute('data-ota-del-rel');
          if (!confirm('Bu sürüm paketini arşivden silmek istediğinize emin misiniz?')) return;
          try {
            const res = await api('ota_delete_release', { id });
            applyApiOtaPayload(res);
            toast(res.message);
            renderOtaHubView();
          } catch (err) {
            toast(err.message, true);
          }
        };
      });
    }

    if (tab === 'client') {
      document.getElementById('otaConnectTokenBtn')?.addEventListener('click', async function () {
        const raw = document.getElementById('otaConnTokenInp')?.value.trim() || '';
        if (!raw) {
          toast('Lütfen ZYN5-OTA1: ile başlayan Bağlantı Kodunu yapıştırın.', true);
          return;
        }
        const btn = this;
        btn.disabled = true;
        btn.textContent = '⏳ El Sıkışma & RSA Doğrulaması Yapılıyor...';
        try {
          const res = await api('ota_client_connect_token', { token: raw });
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
          btn.textContent = '🔗 Bağlantı Kodunu Doğrula & Merkezi Hub’a Bağlan';
        }
      });

      document.getElementById('otaClientCheckNowBtn')?.addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        btn.textContent = '⏳ Sorgulanıyor...';
        try {
          const res = await api('ota_client_check', {});
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          await loadOtaOverviewSilent(true);
          toast(err.message, true);
          renderOtaHubView();
        }
      });

      document.getElementById('otaClientDisconnectBtn')?.addEventListener('click', async () => {
        if (!confirm('Merkezi OTA Hub bağlantısını kesmek istediğinize emin misiniz?')) return;
        try {
          const res = await api('ota_client_disconnect', {});
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
        }
      });

      const triggerPullUpdate = async btn => {
        if (!confirm('Güncelleme öncesi otomatik pre-ota.zip yedeği alınacak ve RSA-2048 imzalı yeni çekirdek kurulacaktır. Onaylıyor musunuz?')) return;
        btn.disabled = true;
        btn.textContent = '⏳ İndiriliyor, RSA-2048 Doğrulanıyor & Kuruluyor...';
        try {
          const res = await api('ota_client_apply_update', {});
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          await loadOtaOverviewSilent(true);
          toast(err.message, true);
          renderOtaHubView();
        }
      };

      document.getElementById('otaClientApplyUpdateBtn')?.addEventListener('click', function () {
        triggerPullUpdate(this);
      });
      document.getElementById('otaClientReinstallBtn')?.addEventListener('click', function () {
        triggerPullUpdate(this);
      });

      document.getElementById('otaSaveClientCfgBtn')?.addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        try {
          const res = await api('ota_client_save_config', {
            auto_update: document.getElementById('otaCliAutoModeSel')?.value || 'off',
            channel: document.getElementById('otaCliChannelSel')?.value || 'stable',
            allow_remote_push: Boolean(document.getElementById('otaCliAllowPushChk')?.checked),
            protected_paths: document.getElementById('otaCliProtectedInp')?.value || ''
          });
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      });

      ROOT.querySelectorAll('[data-ota-rollback]').forEach(btn => {
        btn.onclick = async () => {
          const fn = btn.getAttribute('data-ota-rollback') || '';
          if (!confirm(`${fn} yedeğindeki önceki çekirdek dosyalarına geri dönülsün mü? (Müşteri sayfaları ve veritabanı etkilenmez)`)) return;
          btn.disabled = true;
          btn.textContent = '⏳ Geri Yükleniyor...';
          try {
            const res = await api('ota_client_rollback', { filename: fn });
            applyApiOtaPayload(res);
            toast(res.message);
            renderOtaHubView();
          } catch (err) {
            toast(err.message, true);
            btn.disabled = false;
            btn.textContent = '⏪ Bu Sürüme Geri Dön';
          }
        };
      });
    }

    if (tab === 'logs') {
      document.getElementById('otaClearLogsBtn')?.addEventListener('click', async () => {
        if (!confirm('OTA dağıtım ve doğrulama kayıtları temizlensin mi?')) return;
        try {
          const res = await api('ota_logs_clear', {});
          applyApiOtaPayload(res);
          toast(res.message);
          renderOtaHubView();
        } catch (err) {
          toast(err.message, true);
        }
      });
    }
  }

  /* ======================== STANDALONE SOSYAL MEDYA MERKEZİ ======================== */

  return {
    renderSettingsView,
    renderUptimeTowerView,
    renderPerformanceStudioView,
    mountSystemPerformanceTab,
    renderRenewalsView,
    renderOtaHubView
  };
  });
})();
