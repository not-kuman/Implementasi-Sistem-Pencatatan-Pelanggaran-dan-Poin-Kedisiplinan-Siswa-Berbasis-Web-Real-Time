<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Student Management (CRUD & Profile)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Manajemen Data Siswa';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Data Siswa', 'url' => '#']
];

// Handle Form Submissions (Add, Edit, Delete, CSV Export)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        $nis = trim($_POST['nis'] ?? '');
        $nisn = trim($_POST['nisn'] ?? '');
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $jk = $_POST['jenis_kelamin'] ?? 'L';
        $class_id = (int)($_POST['class_id'] ?? 0);
        $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
        $alamat = trim($_POST['alamat'] ?? '');
        $no_telp_wali = trim($_POST['no_telp_wali'] ?? '');

        if (empty($nis) || empty($nama) || $class_id <= 0 || empty($no_telp_wali)) {
            $_SESSION['flash_error'] = 'NIS, Nama Lengkap, Kelas, dan No Telp Wali wajib diisi.';
        } else {
            // Check NIS duplicate
            $stmtC = $pdo->prepare("SELECT id FROM students WHERE nis = ?");
            $stmtC->execute([$nis]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "NIS '{$nis}' sudah terdaftar pada sistem.";
            } else {
                $stmtIns = $pdo->prepare("
                    INSERT INTO students (nis, nisn, nama_lengkap, jenis_kelamin, class_id, tempat_lahir, tanggal_lahir, alamat, no_telp_wali, total_poin, status_siswa)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'aktif')
                ");
                $stmtIns->execute([$nis, $nisn ?: null, $nama, $jk, $class_id, $tempat_lahir ?: null, $tanggal_lahir, $alamat ?: null, $no_telp_wali]);
                $_SESSION['flash_success'] = "Data siswa '{$nama}' berhasil ditambahkan.";
            }
        }
        header("Location: " . BASE_URL . "/admin/students.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nis = trim($_POST['nis'] ?? '');
        $nisn = trim($_POST['nisn'] ?? '');
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $jk = $_POST['jenis_kelamin'] ?? 'L';
        $class_id = (int)($_POST['class_id'] ?? 0);
        $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
        $alamat = trim($_POST['alamat'] ?? '');
        $no_telp_wali = trim($_POST['no_telp_wali'] ?? '');
        $status_siswa = $_POST['status_siswa'] ?? 'aktif';

        if ($id <= 0 || empty($nis) || empty($nama) || $class_id <= 0 || empty($no_telp_wali)) {
            $_SESSION['flash_error'] = 'Data yang dikirimkan tidak valid.';
        } else {
            // Check NIS duplicate on other IDs
            $stmtC = $pdo->prepare("SELECT id FROM students WHERE nis = ? AND id != ?");
            $stmtC->execute([$nis, $id]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "NIS '{$nis}' sudah digunakan oleh siswa lain.";
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE students 
                    SET nis = ?, nisn = ?, nama_lengkap = ?, jenis_kelamin = ?, class_id = ?,
                        tempat_lahir = ?, tanggal_lahir = ?, alamat = ?, no_telp_wali = ?, status_siswa = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$nis, $nisn ?: null, $nama, $jk, $class_id, $tempat_lahir ?: null, $tanggal_lahir, $alamat ?: null, $no_telp_wali, $status_siswa, $id]);
                $_SESSION['flash_success'] = "Data siswa '{$nama}' berhasil diperbarui.";
            }
        }
        header("Location: " . BASE_URL . "/admin/students.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Check if student has violation records
            $stmtV = $pdo->prepare("SELECT COUNT(*) FROM violation_records WHERE student_id = ?");
            $stmtV->execute([$id]);
            $count = (int)$stmtV->fetchColumn();

            if ($count > 0) {
                $_SESSION['flash_error'] = "Siswa ini memiliki {$count} rekam jejak pelanggaran. Hapus atau alihkan rekam jejak terlebih dahulu demi integritas arsip.";
            } else {
                $stmtDel = $pdo->prepare("DELETE FROM students WHERE id = ?");
                $stmtDel->execute([$id]);
                $_SESSION['flash_success'] = "Data siswa berhasil dihapus.";
            }
        }
        header("Location: " . BASE_URL . "/admin/students.php");
        exit;
    }
}

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Siswa_Kedisiplinan_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // BOM for excel
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['ID', 'NIS', 'NISN', 'Nama Lengkap', 'Jenis Kelamin', 'Kelas', 'No HP Wali', 'Total Poin', 'Status']);

    $stmtExp = $pdo->query("
        SELECT s.id, s.nis, s.nisn, s.nama_lengkap, s.jenis_kelamin, c.nama_kelas, s.no_telp_wali, s.total_poin, s.status_siswa
        FROM students s
        JOIN classes c ON s.class_id = c.id
        ORDER BY c.nama_kelas ASC, s.nama_lengkap ASC
    ");
    while ($row = $stmtExp->fetch()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Filters & Pagination
$filter_class = (int)($_GET['class_id'] ?? 0);
$filter_search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
$params = [];

if ($filter_class > 0) {
    $where[] = "s.class_id = ?";
    $params[] = $filter_class;
}

if (!empty($filter_search)) {
    $where[] = "(s.nama_lengkap LIKE ? OR s.nis LIKE ?)";
    $params[] = "%{$filter_search}%";
    $params[] = "%{$filter_search}%";
}

$where_sql = implode(" AND ", $where);

// Count total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM students s WHERE {$where_sql}");
$stmtCount->execute($params);
$total_rows = (int)$stmtCount->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

// Fetch data
$sql = "
    SELECT s.*, c.nama_kelas, c.tingkat, p.nama_wali, p.hubungan
    FROM students s
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN parents p ON p.student_id = s.id
    WHERE {$where_sql}
    ORDER BY s.total_poin DESC, s.nama_lengkap ASC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch all classes for filter dropdown
$classes_list = $pdo->query("SELECT id, nama_kelas FROM classes ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen Data Siswa</h1>
    <p class="page-subtitle">Kelola data pangkalan peserta didik, kontak wali, dan rekam akumulasi poin</p>
  </div>
  <div class="page-actions">
    <a href="<?= BASE_URL ?>/admin/students.php?export=csv" class="btn btn-secondary">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
      <span>Ekspor CSV</span>
    </a>
    <button type="button" class="btn btn-primary" onclick="openAddModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>Tambah Siswa Baru</span>
    </button>
  </div>
</div>

<!-- Students Data Sheet with Integrated Toolbar -->
<div class="data-sheet">
  <div class="admin-toolbar">
    <form method="GET" action="<?= BASE_URL ?>/admin/students.php" class="admin-toolbar-form">
      <div class="admin-search-wrap">
        <svg class="admin-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" class="admin-search-input" placeholder="Cari nama atau NIS siswa..." value="<?= e($filter_search) ?>">
      </div>

      <select name="class_id" class="admin-filter-select">
        <option value="0">Semua Rombel</option>
        <?php foreach ($classes_list as $cls): ?>
          <option value="<?= $cls['id'] ?>" <?= $filter_class == $cls['id'] ? 'selected' : '' ?>>
            <?= e($cls['nama_kelas']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if (!empty($filter_search) || $filter_class > 0): ?>
        <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-secondary btn-sm">Reset</a>
      <?php endif; ?>
    </form>

    <div class="admin-toolbar-meta">
      Total <strong><?= $total_rows ?></strong> peserta didik terdaftar
    </div>
  </div>

  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>NIS / NISN</th>
          <th>Nama Lengkap</th>
          <th>JK</th>
          <th>Kelas</th>
          <th>No Telepon Wali</th>
          <th style="text-align: center;">Akumulasi Poin</th>
          <th>Status Sanksi</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                <div class="empty-state-title">Tidak ada data siswa ditemukan</div>
                <div class="empty-state-text">Silakan sesuaikan kata kunci pencarian atau tambah siswa baru.</div>
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
                <div style="font-family: var(--font-mono); font-weight: 600; color: var(--color-primary);"><?= e($s['nis']) ?></div>
                <div style="font-size: 11px; color: var(--color-text-muted); font-family: var(--font-mono);"><?= e($s['nisn'] ?: '-') ?></div>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--color-primary);"><?= e($s['nama_lengkap']) ?></div>
                <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($s['tempat_lahir'] ? $s['tempat_lahir'] . ', ' . format_date_id($s['tanggal_lahir']) : '-') ?></div>
              </td>
              <td>
                <span class="badge" style="background-color: #F1F5F9; color: var(--color-text-main);">
                  <?= $s['jenis_kelamin'] === 'L' ? 'L' : 'P' ?>
                </span>
              </td>
              <td>
                <span style="font-weight: 600; color: var(--color-accent-dark);"><?= e($s['nama_kelas']) ?></span>
              </td>
              <td>
                <div style="font-family: var(--font-mono); font-size: 12px;"><?= e($s['no_telp_wali']) ?></div>
                <?php if (!empty($s['nama_wali'])): ?>
                  <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($s['nama_wali']) ?> (<?= e($s['hubungan']) ?>)</div>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <span class="<?= $badge_pts ?>"><?= (int)$s['total_poin'] ?></span>
              </td>
              <td>
                <span style="font-size: 12px; font-weight: 600; color: #334155;">
                  <?= e($sanction['nama_sanksi']) ?>
                </span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="viewStudentDetails(<?= (int)$s['id'] ?>)" title="Lihat Profil & Riwayat">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick='editStudent(<?= json_encode($s) ?>)' title="Ubah Data Siswa">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deleteStudent(<?= (int)$s['id'] ?>, '<?= e(addslashes($s['nama_lengkap'])) ?>')" title="Hapus Siswa" style="color: var(--color-danger);">
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
          <a href="<?= BASE_URL ?>/admin/students.php?page=<?= $p ?>&search=<?= urlencode($filter_search) ?>&class_id=<?= $filter_class ?>" 
             class="page-btn <?= $p === $page ? 'active' : '' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal Tambah / Edit Siswa -->
<div class="modal-overlay" id="student-modal">
  <div class="modal-dialog lg">
    <div class="modal-header">
      <h3 class="modal-title" id="student-modal-title">Tambah Siswa Baru</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('student-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/students.php" method="POST" id="student-form">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="form_action" value="create">
      <input type="hidden" name="id" id="student_id" value="">

      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nomor Induk Siswa (NIS) <span class="required">*</span></label>
            <input type="text" name="nis" id="inp_nis" class="form-control" required placeholder="Contoh: 20261011">
          </div>
          <div class="form-group">
            <label class="form-label">NISN (Opsional)</label>
            <input type="text" name="nisn" id="inp_nisn" class="form-control" placeholder="10 Digit NISN Nasional">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group" style="grid-column: span 2;">
            <label class="form-label">Nama Lengkap Siswa <span class="required">*</span></label>
            <input type="text" name="nama_lengkap" id="inp_nama" class="form-control" required placeholder="Nama lengkap sesuai akta">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Jenis Kelamin <span class="required">*</span></label>
            <select name="jenis_kelamin" id="inp_jk" class="form-control" required>
              <option value="L">Laki-laki (L)</option>
              <option value="P">Perempuan (P)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Kelas / Rombel <span class="required">*</span></label>
            <select name="class_id" id="inp_class_id" class="form-control" required>
              <option value="">-- Pilih Rombongan Belajar --</option>
              <?php foreach ($classes_list as $cls): ?>
                <option value="<?= $cls['id'] ?>"><?= e($cls['nama_kelas']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tempat Lahir</label>
            <input type="text" name="tempat_lahir" id="inp_tempat_lahir" class="form-control" placeholder="Kota lahir">
          </div>
          <div class="form-group">
            <label class="form-label">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" id="inp_tanggal_lahir" class="form-control">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group" style="grid-column: span 2;">
            <label class="form-label">Nomor WhatsApp Wali Siswa <span class="required">*</span></label>
            <input type="text" name="no_telp_wali" id="inp_telp_wali" class="form-control" required placeholder="08xxxxxxxxxx (untuk integrasi WhatsApp)">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Tempat Tinggal</label>
          <textarea name="alamat" id="inp_alamat" class="form-control" placeholder="Alamat lengkap domisili siswa"></textarea>
        </div>

        <div class="form-group" id="group_status" style="display: none;">
          <label class="form-label">Status Siswa</label>
          <select name="status_siswa" id="inp_status_siswa" class="form-control">
            <option value="aktif">Aktif</option>
            <option value="lulus">Lulus</option>
            <option value="pindah">Pindah Sekolah</option>
            <option value="dikeluarkan">Dikeluarkan (Drop Out)</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('student-modal')">Batal</button>
        <button type="submit" class="btn btn-primary" id="btn-save-student">Simpan Data Siswa</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Detail Siswa & Riwayat Kedisiplinan -->
<div class="modal-overlay" id="student-detail-modal">
  <div class="modal-dialog lg">
    <div class="modal-header">
      <h3 class="modal-title">Profil & Rekam Disiplin Siswa</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('student-detail-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body" id="detail-body-content">
      <div style="text-align: center; padding: 30px;">Memuat data profil...</div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="Modal.close('student-detail-modal')">Tutup</button>
    </div>
  </div>
</div>

<!-- Hidden Delete Form -->
<form id="delete-student-form" action="<?= BASE_URL ?>/admin/students.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="delete">
  <input type="hidden" name="id" id="delete_student_id" value="">
</form>

<script>
function openAddModal() {
  document.getElementById('student-modal-title').textContent = 'Tambah Siswa Baru';
  document.getElementById('form_action').value = 'create';
  document.getElementById('student_id').value = '';
  document.getElementById('student-form').reset();
  document.getElementById('group_status').style.display = 'none';
  Modal.open('student-modal');
}

function editStudent(data) {
  document.getElementById('student-modal-title').textContent = 'Ubah Data Siswa: ' + data.nama_lengkap;
  document.getElementById('form_action').value = 'edit';
  document.getElementById('student_id').value = data.id;
  document.getElementById('inp_nis').value = data.nis;
  document.getElementById('inp_nisn').value = data.nisn || '';
  document.getElementById('inp_nama').value = data.nama_lengkap;
  document.getElementById('inp_jk').value = data.jenis_kelamin;
  document.getElementById('inp_class_id').value = data.class_id;
  document.getElementById('inp_tempat_lahir').value = data.tempat_lahir || '';
  document.getElementById('inp_tanggal_lahir').value = data.tanggal_lahir || '';
  document.getElementById('inp_telp_wali').value = data.no_telp_wali;
  document.getElementById('inp_alamat').value = data.alamat || '';
  document.getElementById('inp_status_siswa').value = data.status_siswa || 'aktif';
  document.getElementById('group_status').style.display = 'block';
  Modal.open('student-modal');
}

async function deleteStudent(id, name) {
  const ok = await confirmAction({
    title: 'Konfirmasi Penghapusan Siswa',
    message: `Apakah Anda yakin ingin menghapus siswa "${name}" dari sistem? Tindakan ini tidak dapat dibatalkan jika belum memiliki riwayat.`,
    confirmText: 'Ya, Hapus Data',
    isDanger: true
  });
  if (ok) {
    document.getElementById('delete_student_id').value = id;
    document.getElementById('delete-student-form').submit();
  }
}

async function viewStudentDetails(studentId) {
  Modal.open('student-detail-modal');
  const container = document.getElementById('detail-body-content');
  container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--color-text-muted);">Memuat profil siswa...</div>';

  try {
    const res = await apiRequest(`<?= BASE_URL ?>/api/students.php?action=get_one&id=${studentId}`);
    if (!res.success) {
      container.innerHTML = `<div class="auth-alert-error">${res.message}</div>`;
      return;
    }

    const s = res.data;
    const sanction = s.sanction;
    const pts = Number(s.total_poin);
    const badgeClass = pts >= 50 ? 'badge-points danger' : (pts >= 20 ? 'badge-points warning' : 'badge-points safe');

    container.innerHTML = `
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
        <div style="background-color: #F8FAFC; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Identitas Peserta Didik</div>
          <div style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-top: 4px;">${s.nama_lengkap}</div>
          <div style="font-size: 12.5px; color: var(--color-text-muted); margin-top: 4px;">NIS: ${s.nis} &bull; NISN: ${s.nisn || '-'}</div>
          <div style="font-size: 12.5px; color: var(--color-text-muted);">Kelas: <strong>${s.nama_kelas}</strong> &bull; Wali Kelas: ${s.nama_wali_kelas || '-'}</div>
          <div style="font-size: 12.5px; color: var(--color-text-muted); margin-top: 4px;">Alamat: ${s.alamat || '-'}</div>
        </div>

        <div style="background-color: #F8FAFC; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border); display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="font-size: 11px; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Akumulasi Poin Pelanggaran</div>
            <div style="display: flex; align-items: baseline; gap: 8px; margin-top: 6px;">
              <span class="${badgeClass}" style="font-size: 22px; padding: 4px 12px;">${pts} Poin</span>
            </div>
            <div style="font-size: 12.5px; font-weight: 600; color: #1E293B; margin-top: 8px;">
              Status Sanksi: <span style="color: var(--color-danger);">${sanction.nama_sanksi}</span>
            </div>
            <div style="font-size: 11.5px; color: var(--color-text-muted); margin-top: 2px;">${sanction.tindakan}</div>
          </div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 8px; border-top: 1px solid var(--color-border); padding-top: 6px;">
            Wali: <strong>${s.nama_wali || 'Belum ditautkan'}</strong> &bull; WA: ${s.no_telp_wali}
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    container.innerHTML = '<div class="auth-alert-error">Gagal memuat profil siswa.</div>';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
