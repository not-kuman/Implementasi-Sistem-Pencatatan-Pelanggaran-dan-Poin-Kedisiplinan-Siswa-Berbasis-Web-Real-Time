<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Parents / Guardian Management (CRUD)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Manajemen Data Wali Siswa';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Data Wali Siswa', 'url' => '#']
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $nama_wali = trim($_POST['nama_wali'] ?? '');
        $hubungan = $_POST['hubungan'] ?? 'Ayah';
        $pekerjaan = trim($_POST['pekerjaan'] ?? '');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $create_user = isset($_POST['create_user']);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($student_id <= 0 || empty($nama_wali) || empty($no_telp)) {
            $_SESSION['flash_error'] = 'Siswa Terkait, Nama Wali, dan Nomor Telepon WhatsApp wajib diisi.';
        } else {
            $user_id = null;
            if ($create_user && !empty($username) && !empty($password)) {
                $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmtU->execute([$username]);
                if ($stmtU->fetch()) {
                    $_SESSION['flash_error'] = "Username '{$username}' sudah digunakan.";
                    header("Location: " . BASE_URL . "/admin/parents.php");
                    exit;
                }
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmtInsU = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, 'parent', 'active')");
                $stmtInsU->execute([$username, $hash, $nama_wali]);
                $user_id = $pdo->lastInsertId();
            }

            $stmtInsP = $pdo->prepare("
                INSERT INTO parents (user_id, student_id, nama_wali, hubungan, pekerjaan, no_telp, alamat)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsP->execute([$user_id, $student_id, $nama_wali, $hubungan, $pekerjaan ?: null, $no_telp, $alamat ?: null]);

            // Sync student phone
            $pdo->prepare("UPDATE students SET no_telp_wali = ? WHERE id = ?")->execute([$no_telp, $student_id]);

            $_SESSION['flash_success'] = "Data wali siswa '{$nama_wali}' berhasil disimpan.";
        }
        header("Location: " . BASE_URL . "/admin/parents.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $student_id = (int)($_POST['student_id'] ?? 0);
        $nama_wali = trim($_POST['nama_wali'] ?? '');
        $hubungan = $_POST['hubungan'] ?? 'Ayah';
        $pekerjaan = trim($_POST['pekerjaan'] ?? '');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        if ($id <= 0 || $student_id <= 0 || empty($nama_wali) || empty($no_telp)) {
            $_SESSION['flash_error'] = 'Data tidak lengkap.';
        } else {
            $stmtUpd = $pdo->prepare("
                UPDATE parents 
                SET student_id = ?, nama_wali = ?, hubungan = ?, pekerjaan = ?, no_telp = ?, alamat = ?
                WHERE id = ?
            ");
            $stmtUpd->execute([$student_id, $nama_wali, $hubungan, $pekerjaan ?: null, $no_telp, $alamat ?: null, $id]);

            // Sync student phone
            $pdo->prepare("UPDATE students SET no_telp_wali = ? WHERE id = ?")->execute([$no_telp, $student_id]);

            // Sync user full_name if user_id linked
            $stmtSync = $pdo->prepare("UPDATE users u JOIN parents p ON p.user_id = u.id SET u.full_name = ? WHERE p.id = ?");
            $stmtSync->execute([$nama_wali, $id]);

            $_SESSION['flash_success'] = "Data wali siswa berhasil diperbarui.";
        }
        header("Location: " . BASE_URL . "/admin/parents.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmtU = $pdo->prepare("SELECT user_id FROM parents WHERE id = ?");
            $stmtU->execute([$id]);
            $user_id = $stmtU->fetchColumn();

            $pdo->prepare("DELETE FROM parents WHERE id = ?")->execute([$id]);

            if ($user_id) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            }
            $_SESSION['flash_success'] = "Data wali siswa berhasil dihapus.";
        }
        header("Location: " . BASE_URL . "/admin/parents.php");
        exit;
    }
}

// Fetch Parents
$search = trim($_GET['search'] ?? '');
$where = "1=1";
$params = [];
if (!empty($search)) {
    $where = "(p.nama_wali LIKE ? OR s.nama_lengkap LIKE ? OR s.nis LIKE ?)";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$stmt = $pdo->prepare("
    SELECT p.*, s.nama_lengkap as nama_siswa, s.nis, c.nama_kelas, u.username
    FROM parents p
    JOIN students s ON p.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE {$where}
    ORDER BY s.nama_lengkap ASC
");
$stmt->execute($params);
$parents = $stmt->fetchAll();

$students_list = $pdo->query("SELECT id, nis, nama_lengkap FROM students WHERE status_siswa = 'aktif' ORDER BY nama_lengkap ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen Data Wali Siswa</h1>
    <p class="page-subtitle">Kelola kontak orang tua / wali untuk notifikasi WhatsApp dan akun portal pemantauan</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openAddModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>Tautkan Wali Siswa Baru</span>
    </button>
  </div>
</div>

<div class="surface-card" style="margin-bottom: 20px;">
  <div class="card-body-styled" style="padding: 16px 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/parents.php" style="display: flex; gap: 12px; align-items: center;">
      <div style="flex: 1;">
        <input type="text" name="search" class="form-control" placeholder="Cari nama wali, nama siswa, atau NIS..." value="<?= e($search) ?>">
      </div>
      <button type="submit" class="btn btn-primary">Cari</button>
      <?php if (!empty($search)): ?>
        <a href="<?= BASE_URL ?>/admin/parents.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">Daftar Wali Siswa Terdaftar</h2>
      <span class="card-subtitle-styled">Total <?= count($parents) ?> wali siswa terhubung</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>Nama Wali</th>
            <th>Hubungan</th>
            <th>Peserta Didik (Anak)</th>
            <th>Kelas</th>
            <th>No WhatsApp</th>
            <th>Pekerjaan</th>
            <th>Akun Portal</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($parents)): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <div class="empty-state-title">Belum ada data wali siswa</div>
                  <div class="empty-state-text">Silakan tautkan data wali siswa baru.</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($parents as $p): ?>
              <tr>
                <td>
                  <div style="font-weight: 600; color: var(--color-primary);"><?= e($p['nama_wali']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($p['alamat'] ?: '-') ?></div>
                </td>
                <td>
                  <span class="badge" style="background-color: #F1F5F9; color: var(--color-text-main);"><?= e($p['hubungan']) ?></span>
                </td>
                <td>
                  <div style="font-weight: 600;"><?= e($p['nama_siswa']) ?></div>
                  <div style="font-size: 11px; font-family: var(--font-mono); color: var(--color-text-muted);">NIS: <?= e($p['nis']) ?></div>
                </td>
                <td>
                  <span style="font-weight: 600; color: var(--color-accent-dark);"><?= e($p['nama_kelas']) ?></span>
                </td>
                <td>
                  <span style="font-family: var(--font-mono); font-size: 12px; font-weight: 600;"><?= e($p['no_telp']) ?></span>
                </td>
                <td>
                  <span style="font-size: 12.5px;"><?= e($p['pekerjaan'] ?: '-') ?></span>
                </td>
                <td>
                  <?php if ($p['username']): ?>
                    <span class="badge" style="background-color: #EBF4F3; color: var(--color-accent-dark); font-family: var(--font-mono);">
                      @<?= e($p['username']) ?>
                    </span>
                  <?php else: ?>
                    <span style="font-size: 11px; color: var(--color-text-subtle);">Belum ada akun</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick='editParent(<?= json_encode($p) ?>)' title="Ubah Data">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="deleteParent(<?= (int)$p['id'] ?>, '<?= e(addslashes($p['nama_wali'])) ?>')" style="color: var(--color-danger);" title="Hapus">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
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

<!-- Modal Tambah / Edit Wali -->
<div class="modal-overlay" id="parent-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" id="parent-modal-title">Tautkan Data Wali Siswa</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('parent-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/parents.php" method="POST" id="parent-form">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="form_action" value="create">
      <input type="hidden" name="id" id="parent_id" value="">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Pilih Peserta Didik (Anak) <span class="required">*</span></label>
          <select name="student_id" id="inp_student_id" class="form-control" required>
            <option value="">-- Pilih Siswa --</option>
            <?php foreach ($students_list as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['nama_lengkap']) ?> (NIS: <?= e($s['nis']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nama Lengkap Wali <span class="required">*</span></label>
            <input type="text" name="nama_wali" id="inp_nama_wali" class="form-control" required placeholder="Contoh: Bpk. Joko Susilo">
          </div>
          <div class="form-group">
            <label class="form-label">Hubungan Keluarga <span class="required">*</span></label>
            <select name="hubungan" id="inp_hubungan" class="form-control" required>
              <option value="Ayah">Ayah</option>
              <option value="Ibu">Ibu</option>
              <option value="Wali">Wali Lainnya</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nomor WhatsApp Wali <span class="required">*</span></label>
            <input type="text" name="no_telp" id="inp_telp" class="form-control" required placeholder="08xxxxxxxxxx">
          </div>
          <div class="form-group">
            <label class="form-label">Pekerjaan</label>
            <input type="text" name="pekerjaan" id="inp_pekerjaan" class="form-control" placeholder="Wiraswasta / PNS / Swasta">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Tempat Tinggal</label>
          <textarea name="alamat" id="inp_alamat" class="form-control" placeholder="Alamat rumah"></textarea>
        </div>

        <div id="user_account_fields" style="border-top: 1px dashed var(--color-border); padding-top: 14px; margin-top: 8px;">
          <div style="margin-bottom: 10px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
              <input type="checkbox" name="create_user" id="chk_create_user" value="1" checked onchange="toggleUserFields(this.checked)">
              Buatkan akun login portal wali siswa sekaligus
            </label>
          </div>
          <div id="sub_user_inputs" class="form-row">
            <div class="form-group">
              <label class="form-label">Username Login</label>
              <input type="text" name="username" id="inp_username" class="form-control" placeholder="wali_nama">
            </div>
            <div class="form-group">
              <label class="form-label">Kata Sandi</label>
              <input type="password" name="password" id="inp_password" class="form-control" placeholder="Minimal 6 karakter">
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('parent-modal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Data Wali</button>
      </div>
    </form>
  </div>
</div>

<form id="delete-parent-form" action="<?= BASE_URL ?>/admin/parents.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="delete">
  <input type="hidden" name="id" id="delete_parent_id" value="">
</form>

<script>
function openAddModal() {
  document.getElementById('parent-modal-title').textContent = 'Tautkan Data Wali Siswa Baru';
  document.getElementById('form_action').value = 'create';
  document.getElementById('parent_id').value = '';
  document.getElementById('parent-form').reset();
  document.getElementById('user_account_fields').style.display = 'block';
  Modal.open('parent-modal');
}

function editParent(data) {
  document.getElementById('parent-modal-title').textContent = 'Ubah Data Wali: ' + data.nama_wali;
  document.getElementById('form_action').value = 'edit';
  document.getElementById('parent_id').value = data.id;
  document.getElementById('inp_student_id').value = data.student_id;
  document.getElementById('inp_nama_wali').value = data.nama_wali;
  document.getElementById('inp_hubungan').value = data.hubungan;
  document.getElementById('inp_pekerjaan').value = data.pekerjaan || '';
  document.getElementById('inp_telp').value = data.no_telp;
  document.getElementById('inp_alamat').value = data.alamat || '';
  document.getElementById('user_account_fields').style.display = 'none';
  Modal.open('parent-modal');
}

function toggleUserFields(show) {
  document.getElementById('sub_user_inputs').style.display = show ? 'grid' : 'none';
}

async function deleteParent(id, name) {
  const ok = await confirmAction({
    title: 'Konfirmasi Penghapusan Wali Siswa',
    message: `Apakah Anda yakin ingin menghapus data wali "${name}"? Akun portal wali yang bersangkutan juga akan dihapus.`,
    confirmText: 'Ya, Hapus Data',
    isDanger: true
  });
  if (ok) {
    document.getElementById('delete_parent_id').value = id;
    document.getElementById('delete-parent-form').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
