<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Teacher: Student Directory & Disciplinary Status (Read-Only)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['teacher', 'admin']);

$pdo = get_db_connection();
$page_title = 'Direktori & Status Kedisiplinan Siswa';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/teacher/dashboard.php'],
    ['title' => 'Direktori Siswa', 'url' => '#']
];

$filter_class = (int)($_GET['class_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$where = ["s.status_siswa = 'aktif'"];
$params = [];

if ($filter_class > 0) {
    $where[] = "s.class_id = ?";
    $params[] = $filter_class;
}

if (!empty($search)) {
    $where[] = "(s.nama_lengkap LIKE ? OR s.nis LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_sql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT s.*, c.nama_kelas, c.tingkat, p.nama_wali, p.no_telp as no_telp_wali_parent
    FROM students s
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN parents p ON p.student_id = s.id
    WHERE {$where_sql}
    ORDER BY s.total_poin DESC, s.nama_lengkap ASC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$classes_list = $pdo->query("SELECT id, nama_kelas FROM classes ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Direktori Kedisiplinan Peserta Didik</h1>
    <p class="page-subtitle">Informasi profil disiplin siswa, total poin akumulasi, dan status tindak lanjut</p>
  </div>
</div>

<div class="surface-card" style="margin-bottom: 20px;">
  <div class="card-body-styled" style="padding: 16px 20px;">
    <form method="GET" action="<?= BASE_URL ?>/teacher/students.php" style="display: flex; gap: 14px; align-items: flex-end;">
      <div style="flex: 1;">
        <label class="form-label" style="margin-bottom: 4px;">Pencarian Siswa</label>
        <input type="text" name="search" class="form-control" placeholder="Ketik nama lengkap atau NIS siswa..." value="<?= e($search) ?>">
      </div>
      <div style="width: 220px;">
        <label class="form-label" style="margin-bottom: 4px;">Filter Kelas</label>
        <select name="class_id" class="form-control">
          <option value="0">Semua Kelas</option>
          <?php foreach ($classes_list as $cls): ?>
            <option value="<?= $cls['id'] ?>" <?= $filter_class == $cls['id'] ? 'selected' : '' ?>><?= e($cls['nama_kelas']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="height: 38px;">Cari</button>
        <?php if (!empty($search) || $filter_class > 0): ?>
          <a href="<?= BASE_URL ?>/teacher/students.php" class="btn btn-secondary" style="height: 38px;">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">Daftar Siswa</h2>
      <span class="card-subtitle-styled">Total <?= count($students) ?> siswa aktif</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>NIS</th>
            <th>Nama Lengkap</th>
            <th>Kelas</th>
            <th>JK</th>
            <th>Kontak Wali (WA)</th>
            <th style="text-align: center;">Akumulasi Poin</th>
            <th>Status Sanksi</th>
            <th style="text-align: right;">Tindakan</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <div class="empty-state-title">Tidak ada siswa ditemukan</div>
                  <div class="empty-state-text">Silakan gunakan kata kunci pencarian yang lain.</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($students as $s): 
              $sanction = get_sanction_by_points($s['total_poin']);
              $badge_pts = ($s['total_poin'] >= 50) ? 'badge-points danger' : (($s['total_poin'] >= 20) ? 'badge-points warning' : 'badge-points safe');
            ?>
              <tr>
                <td>
                  <span style="font-family: var(--font-mono); font-weight: 700; color: var(--color-primary);"><?= e($s['nis']) ?></span>
                </td>
                <td>
                  <div style="font-weight: 600; color: var(--color-primary);"><?= e($s['nama_lengkap']) ?></div>
                </td>
                <td>
                  <span style="font-weight: 600; color: var(--color-accent-dark);"><?= e($s['nama_kelas']) ?></span>
                </td>
                <td>
                  <span class="badge" style="background: #F1F5F9;"><?= $s['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></span>
                </td>
                <td>
                  <span style="font-family: var(--font-mono); font-size: 12px;"><?= e($s['no_telp_wali']) ?></span>
                </td>
                <td style="text-align: center;">
                  <span class="<?= $badge_pts ?>"><?= (int)$s['total_poin'] ?> Poin</span>
                </td>
                <td>
                  <span style="font-size: 12px; font-weight: 600; color: #334155;">
                    <?= e($sanction['nama_sanksi']) ?>
                  </span>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <a href="<?= BASE_URL ?>/teacher/add_violation.php" class="btn btn-accent btn-sm">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>Catat Pelanggaran</span>
                  </a>
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
