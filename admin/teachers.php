<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Teacher & BK Management (CRUD)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Manajemen Tenaga Pendidik & BK';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Data Guru & BK', 'url' => '#']
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        $nip = trim($_POST['nip'] ?? '');
        $nama = trim($_POST['nama_guru'] ?? '');
        $jk = $_POST['jenis_kelamin'] ?? 'L';
        $jabatan = trim($_POST['jabatan'] ?? 'Guru Bimbingan Konseling');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $create_user = isset($_POST['create_user']);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nip) || empty($nama) || empty($no_telp)) {
            $_SESSION['flash_error'] = 'NIP, Nama Lengkap, dan Nomor Telepon wajib diisi.';
        } else {
            // Check NIP duplicate
            $stmtC = $pdo->prepare("SELECT id FROM teachers WHERE nip = ?");
            $stmtC->execute([$nip]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "NIP '{$nip}' sudah terdaftar dalam sistem.";
            } else {
                $user_id = null;
                if ($create_user && !empty($username) && !empty($password)) {
                    // Check username duplicate
                    $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmtU->execute([$username]);
                    if ($stmtU->fetch()) {
                        $_SESSION['flash_error'] = "Username '{$username}' sudah digunakan.";
                        header("Location: " . BASE_URL . "/admin/teachers.php");
                        exit;
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmtInsU = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, 'teacher', 'active')");
                    $stmtInsU->execute([$username, $hash, $nama]);
                    $user_id = $pdo->lastInsertId();
                }

                $stmtInsT = $pdo->prepare("
                    INSERT INTO teachers (user_id, nip, nama_guru, jenis_kelamin, jabatan, no_telp, email)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsT->execute([$user_id, $nip, $nama, $jk, $jabatan, $no_telp, $email ?: null]);
                $_SESSION['flash_success'] = "Data guru '{$nama}' berhasil disimpan.";
            }
        }
        header("Location: " . BASE_URL . "/admin/teachers.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nip = trim($_POST['nip'] ?? '');
        $nama = trim($_POST['nama_guru'] ?? '');
        $jk = $_POST['jenis_kelamin'] ?? 'L';
        $jabatan = trim($_POST['jabatan'] ?? 'Guru Bimbingan Konseling');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($id <= 0 || empty($nip) || empty($nama) || empty($no_telp)) {
            $_SESSION['flash_error'] = 'Data tidak lengkap.';
        } else {
            // Check NIP duplicate
            $stmtC = $pdo->prepare("SELECT id FROM teachers WHERE nip = ? AND id != ?");
            $stmtC->execute([$nip, $id]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "NIP '{$nip}' sudah digunakan oleh guru lain.";
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE teachers 
                    SET nip = ?, nama_guru = ?, jenis_kelamin = ?, jabatan = ?, no_telp = ?, email = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$nip, $nama, $jk, $jabatan, $no_telp, $email ?: null, $id]);

                // Sync full_name with linked user if exists
                $stmtSync = $pdo->prepare("UPDATE users u JOIN teachers t ON t.user_id = u.id SET u.full_name = ? WHERE t.id = ?");
                $stmtSync->execute([$nama, $id]);

                $_SESSION['flash_success'] = "Data guru '{$nama}' berhasil diperbarui.";
            }
        }
        header("Location: " . BASE_URL . "/admin/teachers.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Check if teacher has logged violation records
            $stmtV = $pdo->prepare("SELECT COUNT(*) FROM violation_records WHERE teacher_id = ?");
            $stmtV->execute([$id]);
            $count = (int)$stmtV->fetchColumn();

            if ($count > 0) {
                $_SESSION['flash_error'] = "Guru ini memiliki {$count} rekam jejak pencatatan pelanggaran. Data guru tidak dapat dihapus demi integritas arsip laporan.";
            } else {
                // Get user_id before deleting
                $stmtU = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
                $stmtU->execute([$id]);
                $user_id = $stmtU->fetchColumn();

                $stmtDel = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
                $stmtDel->execute([$id]);

                if ($user_id) {
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                }

                $_SESSION['flash_success'] = "Data guru berhasil dihapus dari sistem.";
            }
        }
        header("Location: " . BASE_URL . "/admin/teachers.php");
        exit;
    }
}

// Fetch Teachers
$search = trim($_GET['search'] ?? '');
$where = "1=1";
$params = [];
if (!empty($search)) {
    $where = "(t.nama_guru LIKE ? OR t.nip LIKE ? OR t.jabatan LIKE ?)";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.status as user_status,
           (SELECT COUNT(*) FROM violation_records vr WHERE vr.teacher_id = t.id) as total_catatan
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE {$where}
    ORDER BY t.nama_guru ASC
");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen Guru & Tim Bimbingan Konseling</h1>
    <p class="page-subtitle">Kelola tenaga pendidik yang berwenang mencatat dan mengawasi pelanggaran tata tertib</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openAddModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>Tambah Guru Baru</span>
    </button>
  </div>
</div>

<!-- Teachers Data Sheet with Integrated Toolbar -->
<div class="data-sheet">
  <div class="admin-toolbar">
    <form method="GET" action="<?= BASE_URL ?>/admin/teachers.php" class="admin-toolbar-form">
      <div class="admin-search-wrap">
        <svg class="admin-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" class="admin-search-input" placeholder="Cari nama guru, NIP, atau peranan..." value="<?= e($search) ?>">
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
      <?php if (!empty($search)): ?>
        <a href="<?= BASE_URL ?>/admin/teachers.php" class="btn btn-secondary btn-sm">Reset</a>
      <?php endif; ?>
    </form>
    <div class="admin-toolbar-meta">
      Total <strong><?= count($teachers) ?></strong> tenaga pendidik terdaftar
    </div>
  </div>

  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>NIP</th>
          <th>Nama Guru</th>
          <th>JK</th>
          <th>Jabatan / Peran</th>
          <th>Kontak HP</th>
          <th>Akun Portal</th>
          <th style="text-align: center;">Pelanggaran Dicatat</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($teachers)): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-state-title">Belum ada data guru</div>
                <div class="empty-state-text">Silakan tambahkan data guru baru ke dalam sistem.</div>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($teachers as $t): ?>
            <tr>
              <td>
                <span style="font-family: var(--font-mono); font-weight: 600; color: var(--color-primary);"><?= e($t['nip']) ?></span>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--color-primary);"><?= e($t['nama_guru']) ?></div>
                <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($t['email'] ?: '-') ?></div>
              </td>
              <td>
                <span class="badge" style="background-color: #F1F5F9;"><?= $t['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></span>
              </td>
              <td>
                <span style="font-size: 12.5px; font-weight: 600; color: var(--color-accent-dark);"><?= e($t['jabatan']) ?></span>
              </td>
              <td>
                <span style="font-family: var(--font-mono); font-size: 12px;"><?= e($t['no_telp']) ?></span>
              </td>
              <td>
                <?php if ($t['username']): ?>
                  <span class="badge" style="background-color: #EBF4F3; color: var(--color-accent-dark); font-family: var(--font-mono);">
                    @<?= e($t['username']) ?>
                  </span>
                <?php else: ?>
                  <span style="font-size: 11.5px; color: var(--color-text-subtle);">Belum dibuat</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <span class="badge" style="background-color: #F1F5F9; font-weight: 700;"><?= (int)$t['total_catatan'] ?> Kasus</span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick='editTeacher(<?= json_encode($t) ?>)' title="Ubah Data">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deleteTeacher(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['nama_guru'])) ?>')" style="color: var(--color-danger);" title="Hapus">
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

<!-- Modal Tambah / Edit Guru -->
<div class="modal-overlay" id="teacher-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" id="teacher-modal-title">Tambah Guru Baru</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('teacher-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/teachers.php" method="POST" id="teacher-form">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="form_action" value="create">
      <input type="hidden" name="id" id="teacher_id" value="">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nomor Induk Pegawai (NIP) <span class="required">*</span></label>
          <input type="text" name="nip" id="inp_nip" class="form-control" required placeholder="18 Digit NIP Resmi">
        </div>

        <div class="form-group">
          <label class="form-label">Nama Lengkap & Gelar <span class="required">*</span></label>
          <input type="text" name="nama_guru" id="inp_nama" class="form-control" required placeholder="Contoh: Drs. Hendra Gunawan, M.Pd">
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
            <label class="form-label">Jabatan / Penugasan <span class="required">*</span></label>
            <input type="text" name="jabatan" id="inp_jabatan" class="form-control" required value="Guru Bimbingan Konseling">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nomor WhatsApp / HP <span class="required">*</span></label>
            <input type="text" name="no_telp" id="inp_telp" class="form-control" required placeholder="08xxxxxxxxxx">
          </div>
          <div class="form-group">
            <label class="form-label">Email (Opsional)</label>
            <input type="email" name="email" id="inp_email" class="form-control" placeholder="nama@kartika12.sch.id">
          </div>
        </div>

        <div id="user_account_fields" style="border-top: 1px dashed var(--color-border); padding-top: 14px; margin-top: 8px;">
          <div style="margin-bottom: 10px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
              <input type="checkbox" name="create_user" id="chk_create_user" value="1" checked onchange="toggleUserFields(this.checked)">
              Buatkan akun login portal guru sekaligus
            </label>
          </div>
          <div id="sub_user_inputs" class="form-row">
            <div class="form-group">
              <label class="form-label">Username Login</label>
              <input type="text" name="username" id="inp_username" class="form-control" placeholder="username_guru">
            </div>
            <div class="form-group">
              <label class="form-label">Kata Sandi</label>
              <input type="password" name="password" id="inp_password" class="form-control" placeholder="Minimal 6 karakter">
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('teacher-modal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Data Guru</button>
      </div>
    </form>
  </div>
</div>

<form id="delete-teacher-form" action="<?= BASE_URL ?>/admin/teachers.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="delete">
  <input type="hidden" name="id" id="delete_teacher_id" value="">
</form>

<script>
function openAddModal() {
  document.getElementById('teacher-modal-title').textContent = 'Tambah Guru Baru';
  document.getElementById('form_action').value = 'create';
  document.getElementById('teacher_id').value = '';
  document.getElementById('teacher-form').reset();
  document.getElementById('user_account_fields').style.display = 'block';
  Modal.open('teacher-modal');
}

function editTeacher(data) {
  document.getElementById('teacher-modal-title').textContent = 'Ubah Data Guru: ' + data.nama_guru;
  document.getElementById('form_action').value = 'edit';
  document.getElementById('teacher_id').value = data.id;
  document.getElementById('inp_nip').value = data.nip;
  document.getElementById('inp_nama').value = data.nama_guru;
  document.getElementById('inp_jk').value = data.jenis_kelamin;
  document.getElementById('inp_jabatan').value = data.jabatan;
  document.getElementById('inp_telp').value = data.no_telp;
  document.getElementById('inp_email').value = data.email || '';
  document.getElementById('user_account_fields').style.display = 'none';
  Modal.open('teacher-modal');
}

function toggleUserFields(show) {
  document.getElementById('sub_user_inputs').style.display = show ? 'grid' : 'none';
}

async function deleteTeacher(id, name) {
  const ok = await confirmAction({
    title: 'Konfirmasi Penghapusan Guru',
    message: `Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini hanya dapat dilakukan jika guru belum memiliki riwayat pencatatan.`,
    confirmText: 'Ya, Hapus Data',
    isDanger: true
  });
  if (ok) {
    document.getElementById('delete_teacher_id').value = id;
    document.getElementById('delete-teacher-form').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
