<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Teacher: My Recorded Violations History
 */

require_once __DIR__ . '/../config/app.php';
require_role(['teacher']);

$user = current_user();
$pdo = get_db_connection();
$teacher_id = (int)($user['teacher_id'] ?? 1);

$page_title = 'Riwayat Pelanggaran yang Saya Catat';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/teacher/dashboard.php'],
    ['title' => 'Riwayat Pencatatan Saya', 'url' => '#']
];

// Filters
$search = trim($_GET['search'] ?? '');
$filter_level = trim($_GET['level'] ?? '');
$filter_start = trim($_GET['start_date'] ?? '');
$filter_end   = trim($_GET['end_date'] ?? '');

$where = ["vr.teacher_id = ?"];
$params = [$teacher_id];

if (!empty($search)) {
    $where[] = "(vr.kode_transaksi LIKE ? OR s.nama_lengkap LIKE ? OR s.nis LIKE ? OR vc.nama_pelanggaran LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filter_level)) {
    $where[] = "vc.tingkat = ?";
    $params[] = $filter_level;
}

if (!empty($filter_start)) {
    $where[] = "vr.tanggal_kejadian >= ?";
    $params[] = $filter_start;
}

if (!empty($filter_end)) {
    $where[] = "vr.tanggal_kejadian <= ?";
    $params[] = $filter_end;
}

$where_sql = implode(" AND ", $where);

$sql = "
    SELECT vr.*, s.nama_lengkap as nama_siswa, s.nis, s.no_telp_wali, c.nama_kelas,
           vc.nama_pelanggaran, vc.tingkat,
           (SELECT n.status FROM notifications n WHERE n.violation_record_id = vr.id ORDER BY n.id DESC LIMIT 1) as wa_status,
           (SELECT n.sent_at FROM notifications n WHERE n.violation_record_id = vr.id ORDER BY n.id DESC LIMIT 1) as wa_sent_at
    FROM violation_records vr
    JOIN students s ON vr.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN violation_categories vc ON vr.category_id = vc.id
    WHERE {$where_sql}
    ORDER BY vr.tanggal_kejadian DESC, vr.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Riwayat Pelanggaran yang Anda Catat</h1>
    <p class="page-subtitle">Daftar rekaman kedisiplinan dan status pengiriman pesan notifikasi WhatsApp</p>
  </div>
  <div class="page-actions">
    <a href="<?= BASE_URL ?>/teacher/add_violation.php" class="btn btn-accent">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>+ Catat Pelanggaran</span>
    </a>
  </div>
</div>

<div class="surface-card" style="margin-bottom: 20px;">
  <div class="card-body-styled" style="padding: 16px 20px;">
    <form method="GET" action="<?= BASE_URL ?>/teacher/history.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 12px; align-items: flex-end;">
      <div>
        <label class="form-label" style="margin-bottom: 4px;">Pencarian</label>
        <input type="text" name="search" class="form-control" placeholder="Nama siswa, NIS, kode..." value="<?= e($search) ?>">
      </div>
      <div>
        <label class="form-label" style="margin-bottom: 4px;">Tingkat</label>
        <select name="level" class="form-control">
          <option value="">Semua Tingkat</option>
          <option value="Ringan" <?= $filter_level === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
          <option value="Sedang" <?= $filter_level === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
          <option value="Berat" <?= $filter_level === 'Berat' ? 'selected' : '' ?>>Berat</option>
        </select>
      </div>
      <div>
        <label class="form-label" style="margin-bottom: 4px;">Mulai Tanggal</label>
        <input type="date" name="start_date" class="form-control" value="<?= e($filter_start) ?>">
      </div>
      <div>
        <label class="form-label" style="margin-bottom: 4px;">Sampai Tanggal</label>
        <input type="date" name="end_date" class="form-control" value="<?= e($filter_end) ?>">
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
        <a href="<?= BASE_URL ?>/teacher/history.php" class="btn btn-secondary" style="height: 38px;">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">Daftar Input Pelanggaran</h2>
      <span class="card-subtitle-styled">Total <?= count($records) ?> catatan ditemukan</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>Tanggal Kejadian</th>
            <th>Peserta Didik</th>
            <th>Bentuk Pelanggaran</th>
            <th>Tingkat</th>
            <th style="text-align: center;">Poin</th>
            <th>Status Notifikasi WA</th>
            <th>Status Kasus</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <div class="empty-state-title">Tidak ada riwayat input</div>
                  <div class="empty-state-text">Anda belum memiliki catatan pelanggaran yang sesuai dengan filter.</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($records as $r): 
              $sev_class = ($r['tingkat'] === 'Berat') ? 'badge-berat' : (($r['tingkat'] === 'Sedang') ? 'badge-sedang' : 'badge-ringan');
            ?>
              <tr>
                <td>
                  <div style="font-size: 12px; font-weight: 600; color: var(--color-primary);"><?= format_date_id($r['tanggal_kejadian']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted);"><?= substr($r['waktu_kejadian'], 0, 5) ?> WIB</div>
                </td>
                <td>
                  <div style="font-weight: 600; color: var(--color-primary);"><?= e($r['nama_siswa']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted); font-family: var(--font-mono);">NIS: <?= e($r['nis']) ?> &bull; <?= e($r['nama_kelas']) ?></div>
                </td>
                <td>
                  <div style="font-size: 12.5px; font-weight: 500; max-width: 240px;"><?= e($r['nama_pelanggaran']) ?></div>
                </td>
                <td>
                  <span class="badge <?= $sev_class ?>"><?= e($r['tingkat']) ?></span>
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
                    <span class="badge" style="background-color: #FEF2F2; color: #DC2626; font-size: 11px;">Gagal</span>
                  <?php else: ?>
                    <span style="font-size: 11px; color: var(--color-text-subtle);">Belum Dikirim</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-pill <?= strtolower($r['status_tindak_lanjut']) ?>">
                    <span class="status-dot"></span>
                    <?= e($r['status_tindak_lanjut']) ?>
                  </span>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="showDetail(<?= (int)$r['id'] ?>)" title="Lihat Kronologi">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button type="button" class="btn btn-accent btn-sm" onclick="sendWA(<?= (int)$r['id'] ?>, '<?= e(addslashes($r['nama_siswa'])) ?>', '<?= e($r['no_telp_wali']) ?>')" title="Kirim Pesan WhatsApp">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
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

<!-- Modal Detail -->
<div class="modal-overlay" id="history-detail-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title">Detail Berkas Kejadian</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('history-detail-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body" id="history-modal-body">
      <div style="text-align: center; padding: 20px;">Memuat rincian...</div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="Modal.close('history-detail-modal')">Tutup</button>
    </div>
  </div>
</div>

<script>
async function showDetail(recordId) {
  Modal.open('history-detail-modal');
  const body = document.getElementById('history-modal-body');
  body.innerHTML = '<div style="text-align:center; padding:20px;">Memuat rincian...</div>';

  try {
    const res = await apiRequest(`<?= BASE_URL ?>/api/violations.php?action=detail&id=${recordId}`);
    if (!res.success) {
      body.innerHTML = `<div class="auth-alert-error">${res.message}</div>`;
      return;
    }
    const r = res.data;
    body.innerHTML = `
      <div style="margin-bottom: 14px;">
        <div style="font-size: 16px; font-weight: 700; color: var(--color-primary);">${r.nama_siswa} (${r.nama_kelas})</div>
        <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 2px;">Tanggal: ${r.tanggal_kejadian_formatted} &bull; Tempat: ${r.tempat_kejadian}</div>
      </div>
      <div style="background: #FFFDF9; border: 1px solid #FED7AA; padding: 12px; border-radius: var(--radius-sm); font-size: 12.5px; margin-bottom: 14px;">
        <div style="font-weight: 700; color: #92400E; margin-bottom: 4px;">${r.nama_pelanggaran} (+${r.poin} Poin)</div>
        <div>${r.keterangan}</div>
      </div>
      <div style="font-size: 12px; color: var(--color-text-muted);">
        Nomor WhatsApp Wali: <strong>${r.no_telp_wali}</strong>
      </div>
    `;
  } catch (e) {
    body.innerHTML = '<div class="auth-alert-error">Gagal memuat berkas perkara.</div>';
  }
}

async function sendWA(recordId, name, phone) {
  const ok = await confirmAction({
    title: 'Kirim Pesan WhatsApp',
    message: `Kirimkan notifikasi pelanggaran ${name} ke wali murid (${phone})?`,
    confirmText: 'Kirim Notifikasi'
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
        setTimeout(() => location.reload(), 800);
      } else {
        Toast.error('Gagal Mengirim', res.message);
      }
    } catch (e) {
      Toast.error('Kesalahan', 'Gagal memicu gateway.');
    }
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
