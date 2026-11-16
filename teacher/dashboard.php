<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Teacher Dashboard: Operational Command Center
 */

require_once __DIR__ . '/../config/app.php';
require_role(['teacher']);

$user = current_user();
$pdo = get_db_connection();
$teacher_id = (int)($user['teacher_id'] ?? 1);

$page_title = 'Dashboard Operasional Guru';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '#']
];

// Teacher's metrics
$my_total_records = (int)$pdo->query("SELECT COUNT(*) FROM violation_records WHERE teacher_id = {$teacher_id}")->fetchColumn();
$my_month_records = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM violation_records 
    WHERE teacher_id = {$teacher_id} 
      AND MONTH(tanggal_kejadian) = MONTH(CURRENT_DATE()) 
      AND YEAR(tanggal_kejadian) = YEAR(CURRENT_DATE())
")->fetchColumn();
$students_in_danger = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE total_poin >= 30 AND status_siswa = 'aktif'")->fetchColumn();

// Recent 6 violations recorded by this teacher
$stmtRecent = $pdo->prepare("
    SELECT vr.*, s.nama_lengkap as nama_siswa, s.nis, s.total_poin as akumulasi_siswa, s.no_telp_wali,
           c.nama_kelas, vc.nama_pelanggaran, vc.tingkat,
           (SELECT n.status FROM notifications n WHERE n.violation_record_id = vr.id ORDER BY n.id DESC LIMIT 1) as wa_status
    FROM violation_records vr
    JOIN students s ON vr.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN violation_categories vc ON vr.category_id = vc.id
    WHERE vr.teacher_id = ?
    ORDER BY vr.tanggal_kejadian DESC, vr.id DESC
    LIMIT 6
");
$stmtRecent->execute([$teacher_id]);
$my_recent = $stmtRecent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Selamat Datang, <?= e($user['nama_guru'] ?? $user['full_name']) ?></h1>
    <p class="page-subtitle">Panel operasional pencatatan pelanggaran tata tertib dan komunikasi wali siswa</p>
  </div>
  <div class="page-actions">
    <a href="<?= BASE_URL ?>/teacher/add_violation.php" class="btn btn-accent" style="font-weight: 700; padding: 10px 18px; font-size: 13.5px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>+ Input Pelanggaran Baru</span>
    </a>
  </div>
</div>

<!-- Operational 3-Column Metrics -->
<div class="grid-metrics">
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Total Pelanggaran Dicatat Saya</span>
      <div class="metric-icon-wrap accent">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
    </div>
    <div class="metric-value"><?= number_format($my_total_records) ?></div>
    <div class="metric-trend">Total insiden yang Anda laporkan</div>
  </div>

  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Pelanggaran Bulan Ini</span>
      <div class="metric-icon-wrap primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
    </div>
    <div class="metric-value"><?= number_format($my_month_records) ?></div>
    <div class="metric-trend">Bulan <?= format_date_id(date('Y-m-d')) ?></div>
  </div>

  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Siswa Butuh Pembinaan</span>
      <div class="metric-icon-wrap warning">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
    </div>
    <div class="metric-value" style="color: var(--color-warning);"><?= number_format($students_in_danger) ?></div>
    <div class="metric-trend">Akumulasi poin &ge; 30 (Kriteria SP)</div>
  </div>
</div>

<!-- Shortcut Action Banner -->
<div class="surface-card" style="background-color: #FFFFFF; border-left: 4px solid var(--color-accent); margin-bottom: 24px;">
  <div class="card-body-styled" style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
    <div>
      <h3 style="font-size: 15px; font-weight: 700; color: var(--color-primary);">Pencatatan Cepat & Pemberitahuan WhatsApp</h3>
      <p style="font-size: 12.5px; color: var(--color-text-muted); margin-top: 3px;">
        Temukan siswa pelanggar, pilih kategori pelanggaran, dan langsung teruskan laporan ke orang tua via WhatsApp dalam hitungan detik.
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="<?= BASE_URL ?>/teacher/students.php" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Cari Profil Siswa</span>
      </a>
      <a href="<?= BASE_URL ?>/teacher/add_violation.php" class="btn btn-accent">
        <span>Buka Formulir Input</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
  </div>
</div>

<!-- Recent Violations Logged by This Teacher -->
<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Insiden Pelanggaran yang Terakhir Anda Input
      </h2>
      <span class="card-subtitle-styled">Menampilkan 6 catatan terakhir</span>
    </div>
    <a href="<?= BASE_URL ?>/teacher/history.php" class="btn btn-secondary btn-sm">Lihat Semua Riwayat</a>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>Tanggal Kejadian</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Bentuk Pelanggaran</th>
            <th style="text-align: center;">Poin</th>
            <th>Status Notifikasi WA</th>
            <th style="text-align: right;">Aksi Cepat</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($my_recent)): ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <div class="empty-state-title">Belum ada pelanggaran yang dicatat</div>
                  <div class="empty-state-text">Klik tombol "+ Input Pelanggaran Baru" untuk memulai pencatatan.</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($my_recent as $r): 
              $sev_class = ($r['tingkat'] === 'Berat') ? 'badge-berat' : (($r['tingkat'] === 'Sedang') ? 'badge-sedang' : 'badge-ringan');
            ?>
              <tr>
                <td>
                  <div style="font-size: 12px; font-weight: 600; color: var(--color-primary);"><?= format_date_id($r['tanggal_kejadian']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted);"><?= substr($r['waktu_kejadian'] ?? '', 0, 5) ?> WIB</div>
                </td>
                <td>
                  <div style="font-weight: 600; color: var(--color-primary);"><?= e($r['nama_siswa']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted); font-family: var(--font-mono);"><?= e($r['nis']) ?></div>
                </td>
                <td>
                  <span style="font-weight: 600; color: var(--color-accent-dark);"><?= e($r['nama_kelas']) ?></span>
                </td>
                <td>
                  <span class="badge <?= $sev_class ?>"><?= e($r['tingkat']) ?></span>
                  <div style="font-size: 12px; margin-top: 2px; max-width: 200px;" title="<?= e($r['nama_pelanggaran']) ?>">
                    <?= e($r['nama_pelanggaran']) ?>
                  </div>
                </td>
                <td style="text-align: center;">
                  <span class="badge-points danger">+<?= (int)$r['poin'] ?></span>
                </td>
                <td>
                  <?php if ($r['wa_status'] === 'sent' || $r['wa_status'] === 'simulated'): ?>
                    <span class="badge" style="background-color: #EBF6F0; color: #166534; font-size: 11px;">
                      <?= $r['wa_status'] === 'simulated' ? 'Terkirim (DEMO)' : 'Terkirim' ?>
                    </span>
                  <?php elseif ($r['wa_status'] === 'failed'): ?>
                    <span class="badge" style="background-color: #FEF2F2; color: #DC2626; font-size: 11px;">Gagal Terkirim</span>
                  <?php else: ?>
                    <span style="font-size: 11px; color: var(--color-text-subtle);">Belum Dikirim</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="quickSendWA(<?= (int)$r['id'] ?>, '<?= e(addslashes($r['nama_siswa'])) ?>', '<?= e($r['no_telp_wali']) ?>')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>Kirim WA</span>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
async function quickSendWA(recordId, studentName, phone) {
  const ok = await confirmAction({
    title: 'Kirim Notifikasi WhatsApp',
    message: `Kirimkan rincian pelanggaran ${studentName} ke WhatsApp wali (${phone})?`,
    confirmText: 'Kirim WhatsApp'
  });

  if (ok) {
    try {
      const formData = new FormData();
      formData.append('action', 'send_wa');
      formData.append('record_id', recordId);
      formData.append('parent_phone', phone);

      const res = await apiRequest(`<?= BASE_URL ?>/api/notifications.php`, {
        method: 'POST',
        body: formData
      });

      if (res.success) {
        Toast.success('Berhasil', res.message);
        setTimeout(() => location.reload(), 1000);
      } else {
        Toast.error('Gagal', res.message);
      }
    } catch (e) {
      Toast.error('Kesalahan', 'Gagal memicu pengiriman pesan WhatsApp.');
    }
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
