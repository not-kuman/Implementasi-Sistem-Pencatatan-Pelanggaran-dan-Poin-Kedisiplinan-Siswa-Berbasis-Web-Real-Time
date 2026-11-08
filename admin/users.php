<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: User Account Management (RBAC & Passwords)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Manajemen Akun Pengguna Portal';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Manajemen Akun', 'url' => '#']
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($full_name) || empty($password)) {
            $_SESSION['flash_error'] = 'Seluruh bidang formulir wajib diisi.';
        } else {
            $stmtC = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtC->execute([$username]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "Username '{$username}' telah digunakan.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmtIns = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, 'admin', 'active')");
                $stmtIns->execute([$username, $hash, $full_name]);
                $_SESSION['flash_success'] = "Akun Administrator '{$username}' berhasil dibuat.";
            }
        }
        header("Location: " . BASE_URL . "/admin/users.php");
        exit;
    }

    if ($action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';

        if ($id <= 0 || strlen($new_password) < 6) {
            $_SESSION['flash_error'] = 'Kata sandi baru minimal harus 6 karakter.';
        } else {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmtUpd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpd->execute([$hash, $id]);
            $_SESSION['flash_success'] = "Kata sandi pengguna berhasil diatur ulang.";
        }
        header("Location: " . BASE_URL . "/admin/users.php");
        exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $current_status = $_POST['current_status'] ?? 'active';
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';

        // Cannot deactivate yourself
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menonaktifkan akun yang sedang digunakan saat ini.';
        } else {
            $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $id]);
            $_SESSION['flash_success'] = "Status akun pengguna berhasil diubah menjadi {$new_status}.";
        }
        header("Location: " . BASE_URL . "/admin/users.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri.';
        } elseif ($id > 0) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "Akun pengguna berhasil dihapus.";
        }
        header("Location: " . BASE_URL . "/admin/users.php");
        exit;
    }
}

// Filters
$role_filter = trim($_GET['role'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($search)) {
    $where[] = "(username LIKE ? OR full_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_sql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT u.*, 
           t.nama_guru, t.nip,
           p.nama_wali, s.nama_lengkap as nama_anak
    FROM users u
    LEFT JOIN teachers t ON t.user_id = u.id
    LEFT JOIN parents p ON p.user_id = u.id
    LEFT JOIN students s ON p.student_id = s.id
    WHERE {$where_sql}
    ORDER BY u.role ASC, u.username ASC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen Akun & Otoritas Portal</h1>
    <p class="page-subtitle">Pusat kendali hak akses pengguna: Administrator, Dewan Guru, dan Wali Siswa</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openAddAdminModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>Tambah Administrator</span>
    </button>
  </div>
</div>

<div class="surface-card" style="margin-bottom: 20px;">
  <div class="card-body-styled" style="padding: 16px 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/users.php" style="display: flex; gap: 14px; align-items: flex-end;">
      <div style="flex: 1;">
        <label class="form-label" style="margin-bottom: 4px;">Pencarian Pengguna</label>
        <input type="text" name="search" class="form-control" placeholder="Cari nama pengguna atau nama lengkap..." value="<?= e($search) ?>">
      </div>
      <div style="width: 200px;">
        <label class="form-label" style="margin-bottom: 4px;">Role Pengguna</label>
        <select name="role" class="form-control">
          <option value="">Semua Peran (Role)</option>
          <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrator</option>
          <option value="teacher" <?= $role_filter === 'teacher' ? 'selected' : '' ?>>Guru / BK</option>
          <option value="parent" <?= $role_filter === 'parent' ? 'selected' : '' ?>>Wali Siswa</option>
        </select>
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
        <?php if (!empty($search) || !empty($role_filter)): ?>
          <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary" style="height: 38px;">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">Daftar Akun Pengguna</h2>
      <span class="card-subtitle-styled">Total <?= count($users) ?> akun terdaftar pada sistem</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Nama Lengkap</th>
            <th>Peran (Role)</th>
            <th>Profil Terkait</th>
            <th>Terakhir Masuk</th>
            <th>Status</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): 
            $role_badge = ($u['role'] === 'admin') ? 'badge-role-admin' : (($u['role'] === 'teacher') ? 'badge-role-teacher' : 'badge-role-parent');
          ?>
            <tr>
              <td>
                <span style="font-family: var(--font-mono); font-weight: 700; color: var(--color-primary); font-size: 13px;">
                  @<?= e($u['username']) ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--color-primary);"><?= e($u['full_name']) ?></div>
              </td>
              <td>
                <span class="topbar-badge-role <?= $role_badge ?>">
                  <?= e(strtoupper($u['role'])) ?>
                </span>
              </td>
              <td>
                <?php if ($u['role'] === 'teacher' && $u['nip']): ?>
                  <div style="font-size: 11.5px; color: var(--color-text-muted);">NIP: <?= e($u['nip']) ?></div>
                <?php elseif ($u['role'] === 'parent' && $u['nama_anak']): ?>
                  <div style="font-size: 11.5px; color: var(--color-text-muted);">Anak: <strong><?= e($u['nama_anak']) ?></strong></div>
                <?php else: ?>
                  <span style="font-size: 11px; color: var(--color-text-subtle);">&mdash;</span>
                <?php endif; ?>
              </td>
              <td>
                <span style="font-size: 11.5px; color: var(--color-text-muted);">
                  <?= $u['last_login'] ? format_datetime_id($u['last_login']) : 'Belum pernah' ?>
                </span>
              </td>
              <td>
                <span class="status-pill <?= $u['status'] === 'active' ? 'selesai' : 'menunggu' ?>">
                  <span class="status-dot"></span>
                  <?= $u['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="openResetPasswordModal(<?= (int)$u['id'] ?>, '<?= e(addslashes($u['username'])) ?>')" title="Atur Ulang Sandi">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleStatus(<?= (int)$u['id'] ?>, '<?= $u['status'] ?>', '<?= e(addslashes($u['username'])) ?>')" title="<?= $u['status'] === 'active' ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                </button>
                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="deleteUser(<?= (int)$u['id'] ?>, '<?= e(addslashes($u['username'])) ?>')" style="color: var(--color-danger);" title="Hapus Akun">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah Admin -->
<div class="modal-overlay" id="add-admin-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title">Tambah Administrator Baru</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('add-admin-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/users.php" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="create_admin">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Username <span class="required">*</span></label>
          <input type="text" name="username" class="form-control" required placeholder="admin_nama">
        </div>
        <div class="form-group">
          <label class="form-label">Nama Lengkap Pengelola <span class="required">*</span></label>
          <input type="text" name="full_name" class="form-control" required placeholder="Nama lengkap petugas">
        </div>
        <div class="form-group">
          <label class="form-label">Kata Sandi <span class="required">*</span></label>
          <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('add-admin-modal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Akun Admin</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Reset Password -->
<div class="modal-overlay" id="reset-password-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" id="reset-modal-title">Atur Ulang Kata Sandi</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('reset-password-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/users.php" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="reset_password">
      <input type="hidden" name="id" id="reset_user_id" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Kata Sandi Baru <span class="required">*</span></label>
          <input type="password" name="new_password" class="form-control" required placeholder="Minimal 6 karakter" minlength="6">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('reset-password-modal')">Batal</button>
        <button type="submit" class="btn btn-primary">Perbarui Kata Sandi</button>
      </div>
    </form>
  </div>
</div>

<!-- Status Toggle Form -->
<form id="toggle-status-form" action="<?= BASE_URL ?>/admin/users.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="toggle_status">
  <input type="hidden" name="id" id="toggle_user_id" value="">
  <input type="hidden" name="current_status" id="toggle_current_status" value="">
</form>

<!-- Delete Form -->
<form id="delete-user-form" action="<?= BASE_URL ?>/admin/users.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="delete">
  <input type="hidden" name="id" id="delete_user_id" value="">
</form>

<script>
function openAddAdminModal() {
  Modal.open('add-admin-modal');
}

function openResetPasswordModal(id, username) {
  document.getElementById('reset-modal-title').textContent = 'Atur Ulang Sandi: @' + username;
  document.getElementById('reset_user_id').value = id;
  Modal.open('reset-password-modal');
}

async function toggleStatus(id, currentStatus, username) {
  const newStatus = currentStatus === 'active' ? 'menonaktifkan' : 'mengaktifkan';
  const ok = await confirmAction({
    title: 'Konfirmasi Status Akun',
    message: `Apakah Anda ingin ${newStatus} akun @${username}?`,
    confirmText: 'Ya, Ubah Status'
  });
  if (ok) {
    document.getElementById('toggle_user_id').value = id;
    document.getElementById('toggle_current_status').value = currentStatus;
    document.getElementById('toggle-status-form').submit();
  }
}

async function deleteUser(id, username) {
  const ok = await confirmAction({
    title: 'Hapus Akun Pengguna',
    message: `Hapus akun @${username}? Akun ini tidak akan dapat login lagi ke portal.`,
    confirmText: 'Hapus Akun',
    isDanger: true
  });
  if (ok) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete-user-form').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
