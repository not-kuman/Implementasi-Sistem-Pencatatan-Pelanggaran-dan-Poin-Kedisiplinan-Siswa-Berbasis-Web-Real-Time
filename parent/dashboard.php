<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Parent Dashboard: Transparent Disciplinary Monitoring (Read-Only)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['parent']);

$user = current_user();
$pdo = get_db_connection();
$student_id = (int)($user['student_id'] ?? 0);

if ($student_id <= 0) {
    die("Akun wali siswa ini belum ditautkan dengan data siswa.");
}

$page_title = 'Portal Pemantauan Kedisiplinan Siswa';
$breadcrumbs = [
    ['title' => 'Dashboard Wali', 'url' => '#']
];

// Fetch Child Info
$stmtS = $pdo->prepare("
    SELECT s.*, c.nama_kelas, c.tingkat, c.jurusan, t.nama_guru as nama_wali_kelas, t.no_telp as telp_wali_kelas
    FROM students s
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN teachers t ON c.wali_kelas_id = t.id
    WHERE s.id = ?
");
$stmtS->execute([$student_id]);
$child = $stmtS->fetch();

if (!$child) {
    die("Data peserta didik tidak ditemukan.");
}

$sanction = get_sanction_by_points($child['total_poin']);
$points = (int)$child['total_poin'];

// Progress meter percentage (capped at 100 for display)
$pct = min(100, ($points / 100) * 100);
$meter_color = $points >= 50 ? 'danger' : ($points >= 20 ? 'warning' : 'safe');

// Fetch Child's Violations History
$stmtV = $pdo->prepare("
    SELECT vr.*, vc.nama_pelanggaran, vc.tingkat, t.nama_guru,
           (SELECT n.status FROM notifications n WHERE n.violation_record_id = vr.id ORDER BY n.id DESC LIMIT 1) as wa_status
    FROM violation_records vr
    JOIN violation_categories vc ON vr.category_id = vc.id
    JOIN teachers t ON vr.teacher_id = t.id
    WHERE vr.student_id = ?
    ORDER BY vr.tanggal_kejadian DESC, vr.id DESC
");
$stmtV->execute([$student_id]);
$violations = $stmtV->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Pemantauan Kedisiplinan: <?= e($child['nama_lengkap']) ?></h1>
    <p class="page-subtitle">Transparansi rekam jejak tata tertib peserta didik &bull; <?= e(APP_NAME) ?></p>
  </div>
  <div class="page-actions">
    <a href="<?= BASE_URL ?>/parent/profile.php" class="btn btn-secondary">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <span>Profil Siswa & Panduan Sanksi</span>
    </a>
  </div>
</div>

<!-- Child Overview & Discipline Gauge Grid -->
<div style="display: grid; grid-template-columns: 1.1fr 1.3fr; gap: 20px; margin-bottom: 24px;">
  <!-- Student Identity Card -->
  <div class="surface-card" style="margin-bottom: 0;">
    <div class="card-header-styled">
      <h2 class="card-title-styled">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Identitas Peserta Didik
      </h2>
    </div>
    <div class="card-body-styled">
      <div style="font-size: 18px; font-weight: 700; color: var(--color-primary);"><?= e($child['nama_lengkap']) ?></div>
      <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">
        Nomor Induk Siswa: <strong style="font-family: var(--font-mono); color: var(--color-primary);"><?= e($child['nis']) ?></strong>
      </div>
      <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">
        Rombongan Belajar: <strong style="color: var(--color-accent-dark);"><?= e($child['nama_kelas']) ?></strong> (<?= e($child['jurusan']) ?>)
      </div>
      <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">
        Wali Kelas: <strong><?= e($child['nama_wali_kelas'] ?: '-') ?></strong>
      </div>
      <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--color-border);">
        Wali Terhubung: <strong><?= e($user['nama_wali'] ?? $user['full_name']) ?></strong> (<?= e($user['hubungan'] ?? 'Wali') ?>)
      </div>
    </div>
  </div>

  <!-- Disciplinary Meter / Gauge -->
  <div class="surface-card" style="margin-bottom: 0;">
    <div class="card-header-styled">
      <h2 class="card-title-styled">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        Status Akumulasi Poin Kedisiplinan
      </h2>
    </div>
    <div class="card-body-styled">
      <div style="display: flex; justify-content: space-between; align-items: baseline;">
        <div>
          <span style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; font-weight: 700;">Total Poin Denda:</span>
          <div style="font-size: 32px; font-weight: 800; line-height: 1.1; margin-top: 4px; color: <?= $points >= 50 ? 'var(--color-danger)' : ($points >= 20 ? 'var(--color-warning)' : 'var(--color-success)') ?>;">
            <?= $points ?> <span style="font-size: 14px; font-weight: 600; color: var(--color-text-muted);">/ 100 Poin Max</span>
          </div>
        </div>
        <div style="text-align: right;">
          <span class="badge" style="background-color: <?= $points >= 50 ? '#FEE2E2' : ($points >= 20 ? '#FEF3C7' : '#EBF6F0') ?>; color: <?= $points >= 50 ? '#991B1B' : ($points >= 20 ? '#92400E' : '#166534') ?>; font-size: 12px; font-weight: 700; padding: 4px 10px;">
            <?= e($sanction['nama_sanksi']) ?>
          </span>
        </div>
      </div>

      <!-- Meter Visual Bar -->
      <div class="meter-track">
        <div class="meter-fill <?= $meter_color ?>" style="width: <?= $pct ?>%;"></div>
      </div>
      <div class="meter-thresholds">
        <span>0 (Aman)</span>
        <span>15 (Peringatan Lisan)</span>
        <span>30 (SP 1)</span>
        <span>50 (SP 2)</span>
        <span>75 (SP 3)</span>
        <span>100 (Drop Out)</span>
      </div>

      <div style="margin-top: 14px; font-size: 12px; color: var(--color-text-main); background: #F8FAFC; border: 1px solid var(--color-border); padding: 10px 14px; border-radius: var(--radius-sm);">
        <strong>Tindakan Pembinaan:</strong> <?= e($sanction['tindakan']) ?>
      </div>
    </div>
  </div>
</div>

<!-- Violation Records Table for this Child -->
<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Riwayat Catatan Pelanggaran Kedisiplinan
      </h2>
      <span class="card-subtitle-styled">Rincian seluruh insiden yang tercatat oleh dewan guru</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>Tanggal Kejadian</th>
            <th>Bentuk Pelanggaran</th>
            <th>Tingkat</th>
            <th>Keterangan / Tempat</th>
            <th style="text-align: center;">Poin</th>
            <th>Guru Pelapor</th>
            <th>Notifikasi WhatsApp</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($violations)): ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <svg class="empty-state-icon" style="color: var(--color-success);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  </svg>
                  <div class="empty-state-title" style="color: var(--color-success);">Tidak Ada Catatan Pelanggaran</div>
                  <div class="empty-state-text">Putra/putri Anda memiliki rekam jejak kedisiplinan yang sangat terpuji tanpa ada pelanggaran tercatat.</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($violations as $v): 
              $sev_class = ($v['tingkat'] === 'Berat') ? 'badge-berat' : (($v['tingkat'] === 'Sedang') ? 'badge-sedang' : 'badge-ringan');
            ?>
              <tr>
                <td>
                  <div style="font-weight: 600; color: var(--color-primary);"><?= format_date_id($v['tanggal_kejadian']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted);"><?= substr($v['waktu_kejadian'], 0, 5) ?> WIB</div>
                </td>
                <td>
                  <span style="font-weight: 600; font-size: 13px; color: var(--color-primary);"><?= e($v['nama_pelanggaran']) ?></span>
                </td>
                <td>
                  <span class="badge <?= $sev_class ?>"><?= e($v['tingkat']) ?></span>
                </td>
                <td>
                  <div style="font-size: 12.5px; color: var(--color-text-main);"><?= e($v['keterangan']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted); margin-top: 2px;">Lokasi: <?= e($v['tempat_kejadian']) ?></div>
                </td>
                <td style="text-align: center;">
                  <span class="badge-points danger" style="font-weight: 700;">+<?= (int)$v['poin'] ?></span>
                </td>
                <td>
                  <span style="font-size: 12px; font-weight: 500;"><?= e($v['nama_guru']) ?></span>
                </td>
                <td>
                  <?php if ($v['wa_status'] === 'sent' || $v['wa_status'] === 'simulated'): ?>
                    <span class="badge" style="background-color: #EBF6F0; color: #166534; font-size: 11px;">
                      Terkirim ke WhatsApp
                    </span>
                  <?php else: ?>
                    <span style="font-size: 11px; color: var(--color-text-subtle);">Belum Dikirim</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
