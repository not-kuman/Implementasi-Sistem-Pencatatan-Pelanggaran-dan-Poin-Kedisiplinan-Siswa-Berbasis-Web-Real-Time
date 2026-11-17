<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Full Violation History & Records Management
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Riwayat Pencatatan Pelanggaran Siswa';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Riwayat Pelanggaran', 'url' => '#']
];

// Handle Status Update or Record Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form_action = $_POST['form_action'] ?? '';

    if ($form_action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status_tindak_lanjut'] ?? 'Diproses';
        if ($id > 0) {
            $pdo->prepare("UPDATE violation_records SET status_tindak_lanjut = ? WHERE id = ?")->execute([$status, $id]);
            $_SESSION['flash_success'] = 'Status tindak lanjut kasus berhasil diperbarui.';
        }
        header("Location: " . BASE_URL . "/admin/violations.php");
        exit;
    }
}

// Filters
$filter_class = (int)($_GET['class_id'] ?? 0);
$filter_level = trim($_GET['level'] ?? '');
$filter_start = trim($_GET['start_date'] ?? '');
$filter_end   = trim($_GET['end_date'] ?? '');
$search       = trim($_GET['search'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
$params = [];

if ($filter_class > 0) {
    $where[] = "s.class_id = ?";
    $params[] = $filter_class;
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

if (!empty($search)) {
    $where[] = "(vr.kode_transaksi LIKE ? OR s.nama_lengkap LIKE ? OR s.nis LIKE ? OR vc.nama_pelanggaran LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$where_sql = implode(" AND ", $where);

// Count
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) 
    FROM violation_records vr
    JOIN students s ON vr.student_id = s.id
    JOIN violation_categories vc ON vr.category_id = vc.id
    WHERE {$where_sql}
");
$stmtCount->execute($params);
$total_rows = (int)$stmtCount->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

// Fetch
$sql = "
    SELECT vr.*, s.nama_lengkap as nama_siswa, s.nis, s.no_telp_wali, c.nama_kelas,
           vc.nama_pelanggaran, vc.tingkat, t.nama_guru,
           (SELECT n.status FROM notifications n WHERE n.violation_record_id = vr.id ORDER BY n.id DESC LIMIT 1) as wa_status
    FROM violation_records vr
    JOIN students s ON vr.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN violation_categories vc ON vr.category_id = vc.id
    JOIN teachers t ON vr.teacher_id = t.id
    WHERE {$where_sql}
    ORDER BY vr.tanggal_kejadian DESC, vr.id DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$classes_list = $pdo->query("SELECT id, nama_kelas FROM classes ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Buku Catatan Pelanggaran Kedisiplinan</h1>
    <p class="page-subtitle">Arsip rekam jejak kedisiplinan peserta didik dan riwayat pemberitahuan wali siswa</p>
  </div>
  <div class="page-actions">
    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-secondary">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      <span>Cetak Rekap Laporan</span>
    </a>
  </div>
</div>

<!-- Integrated Data Sheet & Filter Toolbar -->
<div class="data-sheet">
  <div class="admin-toolbar">
    <form method="GET" action="<?= BASE_URL ?>/admin/violations.php" class="admin-toolbar-form">
      <div class="admin-search-wrap">
        <svg class="admin-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" class="admin-search-input" placeholder="Cari nama, NIS, pelanggaran..." value="<?= e($search) ?>">
      </div>

      <select name="class_id" class="admin-filter-select">
        <option value="0">Semua Rombel</option>
        <?php foreach ($classes_list as $cls): ?>
          <option value="<?= $cls['id'] ?>" <?= $filter_class == $cls['id'] ? 'selected' : '' ?>><?= e($cls['nama_kelas']) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="level" class="admin-filter-select">
        <option value="">Semua Tingkat</option>
        <option value="Ringan" <?= $filter_level === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
        <option value="Sedang" <?= $filter_level === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
        <option value="Berat" <?= $filter_level === 'Berat' ? 'selected' : '' ?>>Berat</option>
      </select>

      <input type="date" name="start_date" class="admin-filter-select" value="<?= e($filter_start) ?>" title="Dari Tanggal">
      <input type="date" name="end_date" class="admin-filter-select" value="<?= e($filter_end) ?>" title="Sampai Tanggal">

      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if (!empty($search) || $filter_class > 0 || !empty($filter_level) || !empty($filter_start) || !empty($filter_end)): ?>
        <a href="<?= BASE_URL ?>/admin/violations.php" class="btn btn-secondary btn-sm">Reset</a>
      <?php endif; ?>
    </form>

    <div class="admin-toolbar-meta">
      Total <strong><?= $total_rows ?></strong> rekam insiden tercatat
    </div>
  </div>

  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>Tanggal Kejadian</th>
          <th>Peserta Didik</th>
          <th>Bentuk Pelanggaran</th>
          <th>Tingkat</th>
          <th style="text-align: center;">Poin</th>
          <th>Guru Pelapor</th>
          <th>WhatsApp</th>
          <th>Status Kasus</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
          <tr>
            <td colspan="9">
              <div class="empty-state">
                <div class="empty-state-title">Tidak ada data pelanggaran</div>
                <div class="empty-state-text">Sesuaikan parameter filter atau cari kata kunci lain.</div>
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
                <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($r['nis']) ?> &bull; <?= e($r['nama_kelas']) ?></div>
              </td>
              <td>
                <div style="font-size: 12.5px; max-width: 240px; font-weight: 500;" title="<?= e($r['nama_pelanggaran']) ?>">
                  <?= e($r['nama_pelanggaran']) ?>
                </div>
              </td>
              <td>
                <span class="badge <?= $sev_class ?>"><?= e($r['tingkat']) ?></span>
              </td>
              <td style="text-align: center;">
                <span class="badge-points danger" style="font-weight: 700;">+<?= (int)$r['poin'] ?></span>
              </td>
              <td>
                <span style="font-size: 12px; font-weight: 500; color: var(--color-text-main);"><?= e($r['nama_guru']) ?></span>
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
                <button type="button" class="btn btn-secondary btn-sm" onclick="viewDetail(<?= (int)$r['id'] ?>)" title="Detail Lengkap & Notifikasi">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deleteRecord(<?= (int)$r['id'] ?>, '<?= e(addslashes($r['nama_siswa'])) ?>')" style="color: var(--color-danger);" title="Hapus Catatan & Kurangi Poin Siswa">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination-wrapper" style="padding: 14px 20px;">
      <span class="pagination-info">Halaman <?= $page ?> dari <?= $total_pages ?></span>
      <div class="pagination-links">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
          <a href="<?= BASE_URL ?>/admin/violations.php?page=<?= $p ?>&search=<?= urlencode($search) ?>&class_id=<?= $filter_class ?>&level=<?= urlencode($filter_level) ?>&start_date=<?= $filter_start ?>&end_date=<?= $filter_end ?>" 
             class="page-btn <?= $p === $page ? 'active' : '' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal Detail Pelanggaran & Log WhatsApp -->
<div class="modal-overlay" id="violation-detail-modal">
  <div class="modal-dialog lg">
    <div class="modal-header">
      <h3 class="modal-title" id="detail-modal-title">Rincian Insiden Pelanggaran</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('violation-detail-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body" id="detail-modal-body">
      <div style="text-align: center; padding: 30px;">Memuat rincian data...</div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="Modal.close('violation-detail-modal')">Tutup</button>
      <button type="button" class="btn btn-accent" id="btn-dispatch-wa" onclick="sendWhatsAppModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span>Kirim Ulang Notifikasi WhatsApp</span>
      </button>
    </div>
  </div>
</div>

<script>
let currentDetailRecord = null;

async function viewDetail(recordId) {
  Modal.open('violation-detail-modal');
  const body = document.getElementById('detail-modal-body');
  body.innerHTML = '<div style="text-align: center; padding: 30px; color: var(--color-text-muted);">Memuat berkas perkara...</div>';

  try {
    const res = await apiRequest(`<?= BASE_URL ?>/api/violations.php?action=detail&id=${recordId}`);
    if (!res.success) {
      body.innerHTML = `<div class="auth-alert-error">${res.message}</div>`;
      return;
    }

    currentDetailRecord = res.data;
    const r = res.data;

    let notifRows = '';
    if (r.notifications && r.notifications.length > 0) {
      r.notifications.forEach(n => {
        notifRows += `
          <div style="font-size: 12px; padding: 8px 12px; background: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-sm); margin-bottom: 6px;">
            <div style="display: flex; justify-content: space-between; font-weight: 600;">
              <span>Tujuan: ${n.parent_phone}</span>
              <span class="badge" style="background:#EBF6F0; color:#166534;">${n.status.toUpperCase()}</span>
            </div>
            <div style="color: var(--color-text-muted); font-size: 11px; margin-top: 2px;">Waktu Kirim: ${n.sent_at || '-'} &bull; Provider: ${n.provider}</div>
          </div>
        `;
      });
    } else {
      notifRows = '<div style="font-size: 12px; color: var(--color-text-muted); padding: 8px;">Belum pernah dikirimkan notifikasi WhatsApp.</div>';
    }

    body.innerHTML = `
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
        <div style="background-color: #F8FAFC; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Data Siswa Pelanggar</div>
          <div style="font-size: 15px; font-weight: 700; color: var(--color-primary); margin-top: 3px;">${r.nama_siswa}</div>
          <div style="font-size: 12px; color: var(--color-text-muted);">NIS: ${r.nis} &bull; Kelas: ${r.nama_kelas}</div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Akumulasi Saat Ini: <strong>${r.akumulasi_siswa} Poin</strong></div>
          <div style="font-size: 12px; color: var(--color-text-muted);">No WA Wali: <strong>${r.no_telp_wali}</strong></div>
        </div>

        <div style="background-color: #F8FAFC; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Waktu &amp; Tempat Kejadian</div>
          <div style="font-size: 14px; font-weight: 700; color: var(--color-primary); margin-top: 3px;">${r.tanggal_kejadian_formatted}</div>
          <div style="font-size: 12px; color: var(--color-text-muted);">Pukul: ${r.waktu_kejadian_formatted} &bull; ${r.tempat_kejadian}</div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">Pencatat: <strong>${r.nama_guru}</strong></div>
        </div>
      </div>

      <div style="margin-bottom: 16px; background-color: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span style="font-size: 13.5px; font-weight: 700; color: var(--color-primary);">${r.nama_pelanggaran}</span>
          <span class="badge-points danger" style="font-size: 12px;">+${r.poin} Poin Denda</span>
        </div>
        <div style="font-size: 12.5px; color: var(--color-text-main); line-height: 1.5; background: #FFFDF9; border: 1px solid #FED7AA; padding: 10px; border-radius: var(--radius-sm);">
          <strong>Catatan & Keterangan Kejadian:</strong><br>
          ${r.keterangan}
        </div>
      </div>

      <div>
        <div style="font-size: 12.5px; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">Riwayat Log Pengiriman Notifikasi WhatsApp:</div>
        ${notifRows}
      </div>
    `;

  } catch (err) {
    body.innerHTML = '<div class="auth-alert-error">Terjadi kesalahan saat memuat berkas.</div>';
  }
}

async function sendWhatsAppModal() {
  if (!currentDetailRecord) return;
  const r = currentDetailRecord;

  const ok = await confirmAction({
    title: 'Kirim Notifikasi WhatsApp',
    message: `Kirimkan rincian notifikasi pelanggaran untuk ${r.nama_siswa} ke nomor WhatsApp wali (${r.no_telp_wali})?`,
    confirmText: 'Kirim Sekarang'
  });

  if (ok) {
    try {
      const btn = document.getElementById('btn-dispatch-wa');
      btn.disabled = true;
      btn.textContent = 'Mengirimkan...';

      const formData = new FormData();
      formData.append('action', 'send_wa');
      formData.append('record_id', r.id);
      formData.append('parent_phone', r.no_telp_wali);

      const res = await apiRequest(`<?= BASE_URL ?>/api/notifications.php`, {
        method: 'POST',
        body: formData
      });

      if (res.success) {
        Toast.success('Notifikasi Berhasil', res.message);
        viewDetail(r.id); // reload
      } else {
        Toast.error('Gagal Mengirim', res.message);
      }
    } catch (e) {
      Toast.error('Kesalahan', 'Gagal memicu gateway WhatsApp.');
    } finally {
      const btn = document.getElementById('btn-dispatch-wa');
      btn.disabled = false;
      btn.textContent = 'Kirim Ulang Notifikasi WhatsApp';
    }
  }
}

async function deleteRecord(recordId, studentName) {
  const ok = await confirmAction({
    title: 'Hapus Catatan Pelanggaran',
    message: `Hapus catatan rekam pelanggaran siswa "${studentName}"? Poin siswa yang bersangkutan akan dikurangi kembali secara otomatis melalui transaksi basis data.`,
    confirmText: 'Ya, Hapus & Kurangi Poin',
    isDanger: true
  });

  if (ok) {
    try {
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', recordId);

      const res = await apiRequest(`<?= BASE_URL ?>/api/violations.php`, {
        method: 'POST',
        body: formData
      });

      if (res.success) {
        Toast.success('Berhasil', res.message);
        setTimeout(() => location.reload(), 800);
      } else {
        Toast.error('Gagal', res.message);
      }
    } catch (err) {
      Toast.error('Kesalahan', 'Gagal memproses penghapusan.');
    }
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
