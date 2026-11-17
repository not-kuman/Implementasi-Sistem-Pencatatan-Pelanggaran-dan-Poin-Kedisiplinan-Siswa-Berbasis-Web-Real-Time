<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: System Settings & WhatsApp Gateway Configuration
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Pengaturan Sistem & Gateway WhatsApp';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Pengaturan Sistem', 'url' => '#']
];

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $settings_to_update = [
        'school_name' => trim($_POST['school_name'] ?? ''),
        'school_address' => trim($_POST['school_address'] ?? ''),
        'school_phone' => trim($_POST['school_phone'] ?? ''),
        'school_email' => trim($_POST['school_email'] ?? ''),
        'academic_year' => trim($_POST['academic_year'] ?? '2026/2027'),
        'school_lat' => trim($_POST['school_lat'] ?? '3.565800'),
        'school_lng' => trim($_POST['school_lng'] ?? '98.687200'),
        'wa_gateway_provider' => trim($_POST['wa_gateway_provider'] ?? 'WAGateway-Simulator'),
        'wa_gateway_sender' => trim($_POST['wa_gateway_sender'] ?? ''),
        'wa_demo_mode' => isset($_POST['wa_demo_mode']) ? '1' : '0'
    ];

    // Only update API key if entered (do not overwrite with masked asterisks)
    $api_key = trim($_POST['wa_gateway_api_key'] ?? '');
    if (!empty($api_key) && !str_starts_with($api_key, '••••')) {
        $settings_to_update['wa_gateway_api_key'] = $api_key;
    }

    foreach ($settings_to_update as $key => $val) {
        set_setting($key, $val);
    }

    $_SESSION['flash_success'] = 'Konfigurasi sistem dan gateway komunikasi berhasil disimpan.';
    header("Location: " . BASE_URL . "/admin/settings.php");
    exit;
}

// Current Settings
$school_name   = get_setting('school_name', 'SMA Kartika 1-2 Medan');
$school_address= get_setting('school_address', 'Jl. Brigjend Katamso No. 235, Medan Maimun, Kota Medan, Sumatera Utara 20158');
$school_phone  = get_setting('school_phone', '(061) 7864321');
$school_email  = get_setting('school_email', 'info@kartika12medan.sch.id');
$academic_year = get_setting('academic_year', '2026/2027');
$school_lat    = get_setting('school_lat', '3.565800');
$school_lng    = get_setting('school_lng', '98.687200');

$wa_provider   = get_setting('wa_gateway_provider', 'WAGateway-Simulator');
$wa_api_key    = get_setting('wa_gateway_api_key', '');
$wa_sender     = get_setting('wa_gateway_sender', '081260112233');
$wa_demo_mode  = get_setting('wa_demo_mode', '1');

// Masked API Key for safety
$masked_key = !empty($wa_api_key) ? '••••••••••••' . substr($wa_api_key, -4) : '';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Pengaturan Sistem & Integrasi Gateway</h1>
    <p class="page-subtitle">Konfigurasi identitas institusi, koordinat geospasial, dan protokol komunikasi WhatsApp</p>
  </div>
</div>

<form action="<?= BASE_URL ?>/admin/settings.php" method="POST">
  <?= csrf_field() ?>

  <div class="grid-form-layout">
    <!-- Left Column: School Profile & Coordinates -->
    <div>
      <div class="surface-card">
        <div class="card-header-styled">
          <h2 class="card-title-styled">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Profil Institusi Pendidikan
          </h2>
        </div>
        <div class="card-body-styled">
          <div class="form-group">
            <label class="form-label">Nama Resmi Sekolah <span class="required">*</span></label>
            <input type="text" name="school_name" class="form-control" required value="<?= e($school_name) ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Alamat Lengkap Institusi <span class="required">*</span></label>
            <textarea name="school_address" class="form-control" rows="3" required><?= e($school_address) ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nomor Telepon Kantor</label>
              <input type="text" name="school_phone" class="form-control" value="<?= e($school_phone) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Email Korespondensi</label>
              <input type="email" name="school_email" class="form-control" value="<?= e($school_email) ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Tahun Ajaran Aktif <span class="required">*</span></label>
            <input type="text" name="academic_year" class="form-control" required value="<?= e($academic_year) ?>">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Koordinat Latitude (Peta)</label>
              <input type="text" name="school_lat" class="form-control" value="<?= e($school_lat) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Koordinat Longitude (Peta)</label>
              <input type="text" name="school_lng" class="form-control" value="<?= e($school_lng) ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: WhatsApp Gateway Integration -->
    <div>
      <div class="surface-card">
        <div class="card-header-styled">
          <h2 class="card-title-styled">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            Konfigurasi WhatsApp Gateway
          </h2>
        </div>
        <div class="card-body-styled">
          <!-- DEMO Mode Toggle Banner -->
          <div style="background-color: #FEF3C7; border: 1px solid #FDE68A; border-radius: var(--radius-md); padding: 14px; margin-bottom: 18px;">
            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
              <input type="checkbox" name="wa_demo_mode" value="1" <?= $wa_demo_mode == '1' ? 'checked' : '' ?> style="margin-top: 3px;">
              <div>
                <strong style="color: #92400E; font-size: 13px;">Mode Simulasi DEMO Aktif</strong>
                <p style="font-size: 11.5px; color: #78350F; margin-top: 2px; line-height: 1.4;">
                  Ketika mode DEMO aktif, sistem akan mensimulasikan transmisi WhatsApp tanpa memotong kuota API eksternal, mencatat status <code>simulated</code>, dan menguji format template secara aman.
                </p>
              </div>
            </label>
          </div>

          <div class="form-group">
            <label class="form-label">Penyedia Layanan (Gateway Provider)</label>
            <select name="wa_gateway_provider" class="form-control">
              <option value="WAGateway-Simulator" <?= $wa_provider === 'WAGateway-Simulator' ? 'selected' : '' ?>>Built-in Simulator (Rekomendasi Uji Coba)</option>
              <option value="Fonnte" <?= $wa_provider === 'Fonnte' ? 'selected' : '' ?>>Fonnte API Gateway</option>
              <option value="Wablas" <?= $wa_provider === 'Wablas' ? 'selected' : '' ?>>Wablas API Gateway</option>
              <option value="Ruanggwa" <?= $wa_provider === 'Ruanggwa' ? 'selected' : '' ?>>RuangWA Gateway</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Nomor WhatsApp Pengirim Resmi</label>
            <input type="text" name="wa_gateway_sender" class="form-control" value="<?= e($wa_sender) ?>" placeholder="Contoh: 081260112233">
          </div>

          <div class="form-group">
            <label class="form-label">API Token / Secret Key</label>
            <input type="password" name="wa_gateway_api_key" class="form-control" placeholder="<?= e($masked_key ?: 'Masukkan API Key') ?>">
            <small style="font-size: 11px; color: var(--color-text-muted);">Biarkan kosong jika tidak ingin mengubah kunci API tersimpan.</small>
          </div>

          <div style="background-color: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 12px; margin-top: 14px;">
            <div style="font-size: 11.5px; font-weight: 700; color: var(--color-primary); margin-bottom: 4px;">Evaluasi Riset Empiris SUS:</div>
            <div style="font-size: 12px; color: var(--color-text-muted); line-height: 1.5;">
              Sistem ini telah divalidasi menggunakan System Usability Scale (SUS) dengan skor <strong>70.5 ("Baik")</strong> sesuai dokumen penelitian implementasi di SMA Kartika 1-2.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      <span>Simpan Seluruh Pengaturan</span>
    </button>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
