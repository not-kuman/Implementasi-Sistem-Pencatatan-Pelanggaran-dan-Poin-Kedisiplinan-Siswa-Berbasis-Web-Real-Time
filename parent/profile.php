<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Parent: Student Profile & School Disciplinary Code Guide
 */

require_once __DIR__ . '/../config/app.php';
require_role(['parent']);

$user = current_user();
$pdo = get_db_connection();
$student_id = (int)($user['student_id'] ?? 0);

$page_title = 'Profil Peserta Didik & Tata Tertib Sekolah';
$breadcrumbs = [
    ['title' => 'Dashboard Wali', 'url' => BASE_URL . '/parent/dashboard.php'],
    ['title' => 'Profil Siswa', 'url' => '#']
];

$stmtS = $pdo->prepare("
    SELECT s.*, c.nama_kelas, c.tingkat, c.jurusan, t.nama_guru as nama_wali_kelas, t.no_telp as telp_wali_kelas,
           p.nama_wali, p.hubungan, p.no_telp as no_telp_wali_user, p.pekerjaan, p.alamat as alamat_wali
    FROM students s
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN teachers t ON c.wali_kelas_id = t.id
    LEFT JOIN parents p ON p.student_id = s.id AND p.user_id = ?
    WHERE s.id = ?
");
$stmtS->execute([$user['id'], $student_id]);
$child = $stmtS->fetch();

$sanctions = $pdo->query("SELECT * FROM sanctions ORDER BY poin_min ASC")->fetchAll();
$current_sanction = get_sanction_by_points($child['total_poin'] ?? 0);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Profil Siswa & Panduan Tata Tertib</h1>
    <p class="page-subtitle">Pedoman pembinaan karakter dan ketentuan poin pelanggaran siswa &bull; <?= e(APP_NAME) ?></p>
  </div>
</div>

<div class="grid-two-column">
  <!-- Biodata Card -->
  <div class="surface-card">
    <div class="card-header-styled">
      <h2 class="card-title-styled">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Biodata Peserta Didik
      </h2>
    </div>
    <div class="card-body-styled">
      <div style="text-align: center; margin-bottom: 20px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--color-primary); color: #FFFFFF; font-weight: 700; font-size: 24px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
          <?= strtoupper(substr($child['nama_lengkap'], 0, 2)) ?>
        </div>
        <div style="font-size: 17px; font-weight: 700; color: var(--color-primary);"><?= e($child['nama_lengkap']) ?></div>
        <div style="font-size: 12.5px; color: var(--color-text-muted);">NIS: <?= e($child['nis']) ?> &bull; NISN: <?= e($child['nisn'] ?: '-') ?></div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #F1F5F9; padding-bottom: 6px;">
          <span style="color: var(--color-text-muted);">Rombel (Kelas):</span>
          <strong><?= e($child['nama_kelas']) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #F1F5F9; padding-bottom: 6px;">
          <span style="color: var(--color-text-muted);">Jurusan:</span>
          <strong><?= e($child['jurusan']) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #F1F5F9; padding-bottom: 6px;">
          <span style="color: var(--color-text-muted);">Guru Wali Kelas:</span>
          <strong><?= e($child['nama_wali_kelas'] ?: '-') ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #F1F5F9; padding-bottom: 6px;">
          <span style="color: var(--color-text-muted);">Tempat, Tanggal Lahir:</span>
          <span><?= e($child['tempat_lahir'] ? $child['tempat_lahir'] . ', ' . format_date_id($child['tanggal_lahir']) : '-') ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #F1F5F9; padding-bottom: 6px;">
          <span style="color: var(--color-text-muted);">Wali Terdaftar:</span>
          <span><?= e($child['nama_wali'] ?: $user['full_name']) ?> (<?= e($child['hubungan'] ?? 'Wali') ?>)</span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #F1F5F9; padding-bottom: 6px;">
          <span style="color: var(--color-text-muted);">No WhatsApp Terdaftar:</span>
          <span style="font-family: var(--font-mono); font-weight: 600;"><?= e($child['no_telp_wali']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #F1F5F9; padding-bottom: 6px;">
          <span style="color: var(--color-text-muted);">Alamat Domisili:</span>
          <span style="text-align: right; max-width: 200px;"><?= e($child['alamat'] ?: '-') ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Sanctions & School Code Guide -->
  <div class="surface-card">
    <div class="card-header-styled">
      <h2 class="card-title-styled">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Pedoman Pembinaan & Ambang Sanksi Sekolah
      </h2>
    </div>
    <div class="card-body-styled">
      <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 16px; line-height: 1.5;">
        Sistem ini menerapkan poin kedisiplinan kumulatif berlandaskan regulasi tata tertib sekolah. Setiap pelanggaran memiliki bobot poin terukur, dan akumulasi poin menentukan tindak lanjut pembinaan bersama orang tua / wali siswa:
      </p>

      <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($sanctions as $sc): 
          $is_active_tier = ((int)$child['total_poin'] >= (int)$sc['poin_min'] && (int)$child['total_poin'] <= (int)$sc['poin_max']);
        ?>
          <div style="border: 1px solid <?= $is_active_tier ? 'var(--color-danger)' : 'var(--color-border)' ?>; background-color: <?= $is_active_tier ? '#FEF2F2' : '#FFFFFF' ?>; border-radius: var(--radius-md); padding: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <span style="font-weight: 700; color: var(--color-primary); font-size: 13.5px;"><?= e($sc['nama_sanksi']) ?></span>
              <span class="badge-points <?= $is_active_tier ? 'danger' : '' ?>">
                <?= (int)$sc['poin_min'] ?> &ndash; <?= (int)$sc['poin_max'] ?> Poin
              </span>
            </div>
            <div style="font-size: 12px; color: var(--color-text-main); line-height: 1.5;">
              <?= e($sc['tindakan']) ?>
            </div>
            <?php if ($is_active_tier): ?>
              <div style="margin-top: 6px; font-size: 11px; font-weight: 700; color: #DC2626; display: flex; align-items: center; gap: 4px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Posisi status pembinaan anak Anda saat ini berada pada tingkatan ini.
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
