<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Parent: Detailed Child Disciplinary History
 */

require_once __DIR__ . '/../config/app.php';
require_role(['parent']);

$user = current_user();
$pdo = get_db_connection();
$student_id = (int)($user['student_id'] ?? 0);

$page_title = 'Riwayat Pelanggaran Peserta Didik';
$breadcrumbs = [
    ['title' => 'Dashboard Wali', 'url' => BASE_URL . '/parent/dashboard.php'],
    ['title' => 'Riwayat Pelanggaran', 'url' => '#']
];

$stmtS = $pdo->prepare("SELECT nama_lengkap, nis, total_poin FROM students WHERE id = ?");
$stmtS->execute([$student_id]);
$child = $stmtS->fetch();

$level = trim($_GET['level'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["vr.student_id = ?"];
$params = [$student_id];

if (!empty($level)) {
    $where[] = "vc.tingkat = ?";
    $params[] = $level;
}

if (!empty($search)) {
    $where[] = "(vc.nama_pelanggaran LIKE ? OR vr.keterangan LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_sql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT vr.*, vc.nama_pelanggaran, vc.tingkat, t.nama_guru,
           (SELECT n.status FROM notifications n WHERE n.violation_record_id = vr.id ORDER BY n.id DESC LIMIT 1) as wa_status
    FROM violation_records vr
    JOIN violation_categories vc ON vr.category_id = vc.id
    JOIN teachers t ON vr.teacher_id = t.id
    WHERE {$where_sql}
    ORDER BY vr.tanggal_kejadian DESC, vr.id DESC
");
$stmt->execute($params);
$violations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Rekam Jejak Pelanggaran: <?= e($child['nama_lengkap']) ?></h1>
    <p class="page-subtitle">Transparansi seluruh insiden pelanggaran yang pernah tercatat dalam sistem</p>
  </div>
</div>

<div class="surface-card" style="margin-bottom: 20px;">
  <div class="card-body-styled" style="padding: 16px 20px;">
    <form method="GET" action="<?= BASE_URL ?>/parent/history.php" style="display: flex; gap: 14px; align-items: flex-end;">
      <div style="flex: 1;">
        <label class="form-label" style="margin-bottom: 4px;">Pencarian Catatan</label>
        <input type="text" name="search" class="form-control" placeholder="Cari nama pelanggaran atau keterangan..." value="<?= e($search) ?>">
      </div>
      <div style="width: 200px;">
        <label class="form-label" style="margin-bottom: 4px;">Tingkat Pelanggaran</label>
        <select name="level" class="form-control">
          <option value="">Semua Tingkat</option>
          <option value="Ringan" <?= $level === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
          <option value="Sedang" <?= $level === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
          <option value="Berat" <?= $level === 'Berat' ? 'selected' : '' ?>>Berat</option>
        </select>
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
        <?php if (!empty($search) || !empty($level)): ?>
          <a href="<?= BASE_URL ?>/parent/history.php" class="btn btn-secondary" style="height: 38px;">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">Daftar Insiden Terdata</h2>
      <span class="card-subtitle-styled">Total <?= count($violations) ?> catatan kejadian</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>Tanggal / Waktu</th>
            <th>Bentuk Pelanggaran</th>
            <th>Tingkat</th>
            <th>Tempat Kejadian</th>
            <th>Keterangan Guru</th>
            <th style="text-align: center;">Poin Denda</th>
            <th>Guru Pelapor</th>
            <th>Notifikasi WA</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($violations)): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <div class="empty-state-title">Tidak ada riwayat pelanggaran</div>
                  <div class="empty-state-text">Tidak ditemukan catatan pelanggaran yang sesuai dengan kriteria pencarian.</div>
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
                  <span style="font-weight: 700; color: var(--color-primary);"><?= e($v['nama_pelanggaran']) ?></span>
                </td>
                <td>
                  <span class="badge <?= $sev_class ?>"><?= e($v['tingkat']) ?></span>
                </td>
                <td>
                  <span style="font-size: 12px; color: var(--color-text-main);"><?= e($v['tempat_kejadian']) ?></span>
                </td>
                <td>
                  <div style="font-size: 12px; color: var(--color-text-main); max-width: 280px; line-height: 1.4;"><?= e($v['keterangan']) ?></div>
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
                      Terkirim
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
