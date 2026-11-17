<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Classes & Homeroom Management (CRUD)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Manajemen Data Kelas';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Data Kelas', 'url' => '#']
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        $tingkat = $_POST['tingkat'] ?? 'X';
        $jurusan = trim($_POST['jurusan'] ?? 'MIPA');
        $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null;
        $tahun_ajaran = trim($_POST['tahun_ajaran'] ?? '2026/2027');

        if (empty($nama_kelas)) {
            $_SESSION['flash_error'] = 'Nama kelas wajib diisi.';
        } else {
            $stmtC = $pdo->prepare("SELECT id FROM classes WHERE nama_kelas = ?");
            $stmtC->execute([$nama_kelas]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "Kelas '{$nama_kelas}' sudah ada.";
            } else {
                $stmtIns = $pdo->prepare("
                    INSERT INTO classes (nama_kelas, tingkat, jurusan, wali_kelas_id, tahun_ajaran)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([$nama_kelas, $tingkat, $jurusan, $wali_kelas_id, $tahun_ajaran]);
                $_SESSION['flash_success'] = "Kelas '{$nama_kelas}' berhasil ditambahkan.";
            }
        }
        header("Location: " . BASE_URL . "/admin/classes.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        $tingkat = $_POST['tingkat'] ?? 'X';
        $jurusan = trim($_POST['jurusan'] ?? 'MIPA');
        $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null;
        $tahun_ajaran = trim($_POST['tahun_ajaran'] ?? '2026/2027');

        if ($id <= 0 || empty($nama_kelas)) {
            $_SESSION['flash_error'] = 'Nama kelas wajib diisi.';
        } else {
            $stmtC = $pdo->prepare("SELECT id FROM classes WHERE nama_kelas = ? AND id != ?");
            $stmtC->execute([$nama_kelas, $id]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "Nama kelas '{$nama_kelas}' sudah digunakan.";
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE classes 
                    SET nama_kelas = ?, tingkat = ?, jurusan = ?, wali_kelas_id = ?, tahun_ajaran = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$nama_kelas, $tingkat, $jurusan, $wali_kelas_id, $tahun_ajaran, $id]);
                $_SESSION['flash_success'] = "Data kelas '{$nama_kelas}' berhasil diperbarui.";
            }
        }
        header("Location: " . BASE_URL . "/admin/classes.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmtS = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
            $stmtS->execute([$id]);
            $count = (int)$stmtS->fetchColumn();

            if ($count > 0) {
                $_SESSION['flash_error'] = "Kelas ini memiliki {$count} siswa terdaftar. Pindahkan siswa terlebih dahulu sebelum menghapus kelas.";
            } else {
                $pdo->prepare("DELETE FROM classes WHERE id = ?")->execute([$id]);
                $_SESSION['flash_success'] = "Kelas berhasil dihapus.";
            }
        }
        header("Location: " . BASE_URL . "/admin/classes.php");
        exit;
    }
}

// Fetch Classes with Student Count and Homeroom Teacher
$stmt = $pdo->query("
    SELECT c.*, t.nama_guru as wali_kelas_nama, t.no_telp as wali_kelas_telp,
           (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status_siswa = 'aktif') as total_siswa,
           (SELECT COALESCE(SUM(s.total_poin), 0) FROM students s WHERE s.class_id = c.id AND s.status_siswa = 'aktif') as total_poin_kelas
    FROM classes c
    LEFT JOIN teachers t ON c.wali_kelas_id = t.id
    ORDER BY c.tingkat ASC, c.nama_kelas ASC
");
$classes = $stmt->fetchAll();

$teachers_list = $pdo->query("SELECT id, nama_guru FROM teachers ORDER BY nama_guru ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manajemen Data Rombongan Belajar (Kelas)</h1>
    <p class="page-subtitle">Kelola pembagian kelas, penetapan guru wali kelas, dan akumulasi kedisiplinan rombel</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openAddModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>Tambah Kelas Baru</span>
    </button>
  </div>
</div>

<div class="data-sheet">
  <div class="admin-toolbar">
    <div>
      <span style="font-size: 13px; font-weight: 600; color: var(--color-primary);">Arsip Rombongan Belajar</span>
    </div>
    <div class="admin-toolbar-meta">
      Total <strong><?= count($classes) ?></strong> rombel terdaftar
    </div>
  </div>
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>Nama Kelas</th>
          <th>Tingkat</th>
          <th>Jurusan</th>
          <th>Wali Kelas</th>
          <th style="text-align: center;">Jumlah Siswa</th>
          <th style="text-align: center;">Total Poin Rombel</th>
          <th>Tahun Ajaran</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($classes)): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-state-title">Belum ada rombongan belajar</div>
                <div class="empty-state-text">Silakan tambahkan data kelas baru ke dalam sistem.</div>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($classes as $c): ?>
            <tr>
              <td>
                <span style="font-weight: 700; color: var(--color-primary); font-size: 13.5px;"><?= e($c['nama_kelas']) ?></span>
              </td>
              <td>
                <span class="badge" style="background-color: #E2E8F0; color: var(--color-primary);"><?= e($c['tingkat']) ?></span>
              </td>
              <td>
                <span style="font-weight: 600; color: var(--color-accent-dark);"><?= e($c['jurusan']) ?></span>
              </td>
              <td>
                <?php if (!empty($c['wali_kelas_nama'])): ?>
                  <div style="font-weight: 600; color: var(--color-primary);"><?= e($c['wali_kelas_nama']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($c['wali_kelas_telp'] ?: '-') ?></div>
                <?php else: ?>
                  <span style="font-size: 11.5px; color: var(--color-text-subtle);">Belum ditetapkan</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <span style="font-weight: 700; color: var(--color-primary);"><?= (int)$c['total_siswa'] ?> Siswa</span>
              </td>
              <td style="text-align: center;">
                <span class="badge-points <?= $c['total_poin_kelas'] > 50 ? 'danger' : 'warning' ?>">
                  <?= (int)$c['total_poin_kelas'] ?> Poin
                </span>
              </td>
              <td>
                <span style="font-family: var(--font-mono); font-size: 12px;"><?= e($c['tahun_ajaran']) ?></span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick='editClass(<?= json_encode($c) ?>)' title="Ubah Data">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deleteClass(<?= (int)$c['id'] ?>, '<?= e(addslashes($c['nama_kelas'])) ?>')" style="color: var(--color-danger);" title="Hapus">
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

<!-- Modal Tambah / Edit Kelas -->
<div class="modal-overlay" id="class-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" id="class-modal-title">Tambah Kelas Baru</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('class-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/classes.php" method="POST" id="class-form">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="form_action" value="create">
      <input type="hidden" name="id" id="class_id" value="">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Rombongan Belajar (Kelas) <span class="required">*</span></label>
          <input type="text" name="nama_kelas" id="inp_nama_kelas" class="form-control" required placeholder="Contoh: X MIPA 1 atau XI IPS 2">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tingkat Pendidikan <span class="required">*</span></label>
            <select name="tingkat" id="inp_tingkat" class="form-control" required>
              <option value="X">Kelas X (Sepuluh)</option>
              <option value="XI">Kelas XI (Sebelas)</option>
              <option value="XII">Kelas XII (Dua Belas)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Jurusan / Konsentrasi <span class="required">*</span></label>
            <input type="text" name="jurusan" id="inp_jurusan" class="form-control" required value="MIPA" placeholder="MIPA / IPS / BAHASA">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Guru Wali Kelas</label>
          <select name="wali_kelas_id" id="inp_wali_kelas_id" class="form-control">
            <option value="">-- Belum Ditetapkan --</option>
            <?php foreach ($teachers_list as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['nama_guru']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Tahun Ajaran Aktif <span class="required">*</span></label>
          <input type="text" name="tahun_ajaran" id="inp_tahun_ajaran" class="form-control" required value="2026/2027">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('class-modal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Data Kelas</button>
      </div>
    </form>
  </div>
</div>

<form id="delete-class-form" action="<?= BASE_URL ?>/admin/classes.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="delete">
  <input type="hidden" name="id" id="delete_class_id" value="">
</form>

<script>
function openAddModal() {
  document.getElementById('class-modal-title').textContent = 'Tambah Kelas Baru';
  document.getElementById('form_action').value = 'create';
  document.getElementById('class_id').value = '';
  document.getElementById('class-form').reset();
  Modal.open('class-modal');
}

function editClass(data) {
  document.getElementById('class-modal-title').textContent = 'Ubah Kelas: ' + data.nama_kelas;
  document.getElementById('form_action').value = 'edit';
  document.getElementById('class_id').value = data.id;
  document.getElementById('inp_nama_kelas').value = data.nama_kelas;
  document.getElementById('inp_tingkat').value = data.tingkat;
  document.getElementById('inp_jurusan').value = data.jurusan;
  document.getElementById('inp_wali_kelas_id').value = data.wali_kelas_id || '';
  document.getElementById('inp_tahun_ajaran').value = data.tahun_ajaran;
  Modal.open('class-modal');
}

async function deleteClass(id, name) {
  const ok = await confirmAction({
    title: 'Konfirmasi Penghapusan Kelas',
    message: `Apakah Anda yakin ingin menghapus "${name}"? Penghapusan hanya bisa dilakukan bila tidak ada siswa di kelas ini.`,
    confirmText: 'Ya, Hapus Kelas',
    isDanger: true
  });
  if (ok) {
    document.getElementById('delete_class_id').value = id;
    document.getElementById('delete-class-form').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
