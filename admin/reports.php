<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Reports & Rekapitulasi Pelanggaran (Print & Export)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Laporan & Rekapitulasi Kedisiplinan';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Laporan & Rekapitulasi', 'url' => '#']
];

// Filter parameters
$class_id = (int)($_GET['class_id'] ?? 0);
$level = trim($_GET['level'] ?? '');
$start_date = trim($_GET['start_date'] ?? date('Y-m-01')); // Default first day of current month
$end_date = trim($_GET['end_date'] ?? date('Y-m-d'));

$where = ["1=1"];
$params = [];

if ($class_id > 0) {
    $where[] = "s.class_id = ?";
    $params[] = $class_id;
}

if (!empty($level)) {
    $where[] = "vc.tingkat = ?";
    $params[] = $level;
}

if (!empty($start_date)) {
    $where[] = "vr.tanggal_kejadian >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $where[] = "vr.tanggal_kejadian <= ?";
    $params[] = $end_date;
}

$where_sql = implode(" AND ", $where);

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Rekap_Pelanggaran_Kedisiplinan_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['No', 'Tanggal', 'Waktu', 'NIS', 'Nama Siswa', 'Kelas', 'Jenis Pelanggaran', 'Tingkat', 'Poin', 'Tempat Kejadian', 'Keterangan', 'Guru Pencatat', 'Status Tindak Lanjut']);

    $sqlCsv = "
        SELECT vr.tanggal_kejadian, vr.waktu_kejadian, s.nis, s.nama_lengkap, c.nama_kelas,
               vc.nama_pelanggaran, vc.tingkat, vr.poin, vr.tempat_kejadian, vr.keterangan, t.nama_guru, vr.status_tindak_lanjut
        FROM violation_records vr
        JOIN students s ON vr.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN violation_categories vc ON vr.category_id = vc.id
        JOIN teachers t ON vr.teacher_id = t.id
        WHERE {$where_sql}
        ORDER BY vr.tanggal_kejadian ASC, vr.id ASC
    ";
    $stmtC = $pdo->prepare($sqlCsv);
    $stmtC->execute($params);
    $no = 1;
    while ($r = $stmtC->fetch()) {
        fputcsv($out, [
            $no++,
            $r['tanggal_kejadian'],
            $r['waktu_kejadian'],
            $r['nis'],
            $r['nama_lengkap'],
            $r['nama_kelas'],
            $r['nama_pelanggaran'],
            $r['tingkat'],
            $r['poin'],
            $r['tempat_kejadian'],
            $r['keterangan'],
            $r['nama_guru'],
            $r['status_tindak_lanjut']
        ]);
    }
    fclose($out);
    exit;
}

// Fetch query for display
$sql = "
    SELECT vr.*, s.nama_lengkap as nama_siswa, s.nis, c.nama_kelas,
           vc.nama_pelanggaran, vc.tingkat, t.nama_guru
    FROM violation_records vr
    JOIN students s ON vr.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN violation_categories vc ON vr.category_id = vc.id
    JOIN teachers t ON vr.teacher_id = t.id
    WHERE {$where_sql}
    ORDER BY vr.tanggal_kejadian ASC, vr.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Calculate Summary Statistics for this filter
$total_cases = count($records);
$total_points_recap = array_sum(array_column($records, 'poin'));
$classes_list = $pdo->query("SELECT id, nama_kelas FROM classes ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- No-Print Screen Header -->
<div class="no-print">
  <div class="page-header">
    <div>
      <h1 class="page-title">Rekapitulasi & Laporan Kedisiplinan</h1>
      <p class="page-subtitle">Penyusunan berkas evaluasi berkala dan pencetakan dokumen resmi sekolah</p>
    </div>
    <div class="page-actions">
      <a href="<?= BASE_URL ?>/admin/reports.php?export=csv&class_id=<?= $class_id ?>&level=<?= urlencode($level) ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-secondary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
        <span>Unduh Data (CSV)</span>
      </a>
      <button type="button" class="btn btn-primary" onclick="window.print()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        <span>Cetak Berkas Laporan</span>
      </button>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="surface-card" style="margin-bottom: 20px;">
    <div class="card-body-styled" style="padding: 16px 20px;">
      <form method="GET" action="<?= BASE_URL ?>/admin/reports.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 12px; align-items: flex-end;">
        <div>
          <label class="form-label" style="margin-bottom: 4px;">Kelas</label>
          <select name="class_id" class="form-control">
            <option value="0">Semua Kelas</option>
            <?php foreach ($classes_list as $cls): ?>
              <option value="<?= $cls['id'] ?>" <?= $class_id == $cls['id'] ? 'selected' : '' ?>><?= e($cls['nama_kelas']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label" style="margin-bottom: 4px;">Tingkat Pelanggaran</label>
          <select name="level" class="form-control">
            <option value="">Semua Tingkat</option>
            <option value="Ringan" <?= $level === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
            <option value="Sedang" <?= $level === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
            <option value="Berat" <?= $level === 'Berat' ? 'selected' : '' ?>>Berat</option>
          </select>
        </div>
        <div>
          <label class="form-label" style="margin-bottom: 4px;">Mulai Tanggal</label>
          <input type="date" name="start_date" class="form-control" value="<?= e($start_date) ?>">
        </div>
        <div>
          <label class="form-label" style="margin-bottom: 4px;">Sampai Tanggal</label>
          <input type="date" name="end_date" class="form-control" value="<?= e($end_date) ?>">
        </div>
        <div style="display: flex; gap: 8px;">
          <button type="submit" class="btn btn-primary" style="height: 38px;">Terapkan Filter</button>
          <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-secondary" style="height: 38px;">Bulan Ini</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary Pills -->
  <div style="display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); padding: 12px 18px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 12px; color: var(--color-text-muted);">Total Kasus Periode:</span>
      <span style="font-weight: 700; font-size: 15px; color: var(--color-primary);"><?= $total_cases ?> Kejadian</span>
    </div>
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); padding: 12px 18px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 12px; color: var(--color-text-muted);">Akumulasi Poin Terbit:</span>
      <span style="font-weight: 700; font-size: 15px; color: var(--color-danger);"><?= $total_points_recap ?> Poin</span>
    </div>
    <div style="background: #FFFFFF; border: 1px solid var(--color-border); padding: 12px 18px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 12px; color: var(--color-text-muted);">Rentang Periode:</span>
      <span style="font-weight: 600; font-size: 13px; color: var(--color-primary);"><?= format_date_id($start_date) ?> s/d <?= format_date_id($end_date) ?></span>
    </div>
  </div>
</div>

<!-- PRINTABLE DOCUMENT CONTAINER -->
<div class="surface-card" style="padding: 30px;">
  <!-- KOP LAPORAN RESMI SISTEM INFORMASI KEDISIPLINAN -->
  <div style="text-align: center; border-bottom: 3px double #172B3A; padding-bottom: 14px; margin-bottom: 24px; position: relative;">
    <div style="font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #475569;">
      PORTAL ADMINISTRASI & PEMBINAAN KESISWAAN TERPADU
    </div>
    <div style="font-size: 19px; font-weight: 800; color: #172B3A; letter-spacing: 0.5px; margin-top: 2px;">
      <?= e(APP_NAME) ?>
    </div>
    <div style="font-size: 12px; color: #334155; margin-top: 4px;">
      <?= e(APP_SUBTITLE) ?> &bull; Tahun Ajaran <?= e(get_setting('academic_year', '2026/2027')) ?>
    </div>
    <div style="font-size: 11px; color: #64748B; margin-top: 2px;">
      Dokumen Rekapitulasi Resmi Rekam Jejak Pelanggaran dan Poin Kedisiplinan Peserta Didik
    </div>
  </div>

  <div style="text-align: center; margin-bottom: 20px;">
    <h2 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #172B3A;">
      REKAPITULASI CATATAN PELANGGARAN DAN POIN KEDISIPLINAN SISWA
    </h2>
    <div style="font-size: 12px; color: #64748B; margin-top: 4px;">
      Periode: <?= format_date_id($start_date) ?> s/d <?= format_date_id($end_date) ?> 
      <?= $class_id > 0 ? " &bull; Kelas: " . e($records[0]['nama_kelas'] ?? '') : '' ?>
      <?= !empty($level) ? " &bull; Tingkat: {$level}" : '' ?>
    </div>
  </div>

  <div class="table-responsive">
  <table class="app-table" style="font-size: 12px;">
    <thead>
      <tr style="background: #F1F5F9;">
        <th style="width: 35px; text-align: center;">No</th>
        <th>Tanggal</th>
        <th>NIS</th>
        <th>Nama Siswa</th>
        <th>Kelas</th>
        <th>Bentuk Pelanggaran</th>
        <th>Tingkat</th>
        <th style="text-align: center;">Poin</th>
        <th>Guru Pelapor</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($records)): ?>
        <tr>
          <td colspan="9" style="text-align: center; padding: 30px; color: #64748B;">
            Tidak ada catatan pelanggaran yang terdata pada parameter periode ini.
          </td>
        </tr>
      <?php else: ?>
        <?php $no = 1; foreach ($records as $r): ?>
          <tr>
            <td style="text-align: center; font-weight: 600;"><?= $no++ ?></td>
            <td><?= format_date_id($r['tanggal_kejadian']) ?></td>
            <td style="font-family: var(--font-mono); font-size: 11px;"><?= e($r['nis']) ?></td>
            <td style="font-weight: 600; color: #172B3A;"><?= e($r['nama_siswa']) ?></td>
            <td><?= e($r['nama_kelas']) ?></td>
            <td>
              <div style="max-width: 200px;"><?= e($r['nama_pelanggaran']) ?></div>
            </td>
            <td><?= e($r['tingkat']) ?></td>
            <td style="text-align: center; font-weight: 700; color: #DC2626;">+<?= (int)$r['poin'] ?></td>
            <td><?= e($r['nama_guru']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr style="background: #F8FAFC; font-weight: 700;">
        <td colspan="7" style="text-align: right; padding: 10px 16px;">TOTAL POIN KELUAR:</td>
        <td style="text-align: center; color: #DC2626; font-size: 13px;">+<?= $total_points_recap ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  </div>

  <!-- Official Signatures Section for School Administration -->
  <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; page-break-inside: avoid;">
    <div style="text-align: center; font-size: 12.5px;">
      <div>Mengetahui,</div>
      <div style="font-weight: 600;">Kepala Sekolah</div>
      <div style="height: 70px;"></div>
      <div style="font-weight: 700; text-decoration: underline; color: #172B3A;">Dra. Hj. Nur Asyiah, M.Pd</div>
      <div style="font-size: 11px; color: #64748B;">NIP. 196807151994032001</div>
    </div>

    <div style="text-align: center; font-size: 12.5px;">
      <div><?= format_date_id(date('Y-m-d')) ?></div>
      <div style="font-weight: 600;">Koordinator Bimbingan & Konseling</div>
      <div style="height: 70px;"></div>
      <div style="font-weight: 700; text-decoration: underline; color: #172B3A;">Drs. Hendra Gunawan, M.Pd</div>
      <div style="font-size: 11px; color: #64748B;">NIP. 198005122005011002</div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
