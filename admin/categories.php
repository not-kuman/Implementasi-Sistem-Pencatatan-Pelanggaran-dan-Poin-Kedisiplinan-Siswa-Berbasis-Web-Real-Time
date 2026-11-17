<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Violation Categories & Points Configuration (CRUD)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Manajemen Kategori & Bobot Poin Pelanggaran';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Kategori Pelanggaran', 'url' => '#']
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        $kode = trim($_POST['kode'] ?? '');
        $nama = trim($_POST['nama_pelanggaran'] ?? '');
        $tingkat = $_POST['tingkat'] ?? 'Ringan';
        $poin = (int)($_POST['poin'] ?? 5);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if (empty($kode) || empty($nama) || $poin <= 0) {
            $_SESSION['flash_error'] = 'Kode, Nama Pelanggaran, dan Bobot Poin (> 0) wajib diisi.';
        } else {
            $stmtC = $pdo->prepare("SELECT id FROM violation_categories WHERE kode = ?");
            $stmtC->execute([$kode]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "Kode pelanggaran '{$kode}' sudah ada.";
            } else {
                $stmtIns = $pdo->prepare("
                    INSERT INTO violation_categories (kode, nama_pelanggaran, tingkat, poin, deskripsi, status)
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");
                $stmtIns->execute([$kode, $nama, $tingkat, $poin, $deskripsi ?: null]);
                $_SESSION['flash_success'] = "Kategori pelanggaran '{$nama}' ({$poin} Poin) berhasil disimpan.";
            }
        }
        header("Location: " . BASE_URL . "/admin/categories.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $kode = trim($_POST['kode'] ?? '');
        $nama = trim($_POST['nama_pelanggaran'] ?? '');
        $tingkat = $_POST['tingkat'] ?? 'Ringan';
        $poin = (int)($_POST['poin'] ?? 5);
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($id <= 0 || empty($kode) || empty($nama) || $poin <= 0) {
            $_SESSION['flash_error'] = 'Data yang dikirimkan tidak valid.';
        } else {
            $stmtC = $pdo->prepare("SELECT id FROM violation_categories WHERE kode = ? AND id != ?");
            $stmtC->execute([$kode, $id]);
            if ($stmtC->fetch()) {
                $_SESSION['flash_error'] = "Kode pelanggaran '{$kode}' sudah digunakan pada kategori lain.";
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE violation_categories 
                    SET kode = ?, nama_pelanggaran = ?, tingkat = ?, poin = ?, deskripsi = ?, status = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$kode, $nama, $tingkat, $poin, $deskripsi ?: null, $status, $id]);
                $_SESSION['flash_success'] = "Kategori pelanggaran berhasil diperbarui.";
            }
        }
        header("Location: " . BASE_URL . "/admin/categories.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmtV = $pdo->prepare("SELECT COUNT(*) FROM violation_records WHERE category_id = ?");
            $stmtV->execute([$id]);
            $count = (int)$stmtV->fetchColumn();

            if ($count > 0) {
                $_SESSION['flash_error'] = "Kategori ini telah digunakan dalam {$count} rekam jejak pelanggaran. Nonaktifkan status kategori sebagai ganti menghapus.";
            } else {
                $pdo->prepare("DELETE FROM violation_categories WHERE id = ?")->execute([$id]);
                $_SESSION['flash_success'] = "Kategori pelanggaran berhasil dihapus.";
            }
        }
        header("Location: " . BASE_URL . "/admin/categories.php");
        exit;
    }
}

// Filters
$filter_tingkat = trim($_GET['tingkat'] ?? '');
$filter_search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($filter_tingkat)) {
    $where[] = "tingkat = ?";
    $params[] = $filter_tingkat;
}

if (!empty($filter_search)) {
    $where[] = "(nama_pelanggaran LIKE ? OR kode LIKE ? OR deskripsi LIKE ?)";
    $params[] = "%{$filter_search}%";
    $params[] = "%{$filter_search}%";
    $params[] = "%{$filter_search}%";
}

$where_sql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT vc.*,
           (SELECT COUNT(*) FROM violation_records vr WHERE vr.category_id = vc.id) as total_kejadian
    FROM violation_categories vc
    WHERE {$where_sql}
    ORDER BY vc.tingkat ASC, vc.poin ASC, vc.kode ASC
");
$stmt->execute($params);
$categories = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Kategori & Bobot Poin Tata Tertib</h1>
    <p class="page-subtitle">Konfigurasi jenis deviasi kedisiplinan, tingkat sanksi, dan parameter bobot poin denda</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openAddModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>Tambah Kategori Baru</span>
    </button>
  </div>
</div>

<div class="surface-card" style="margin-bottom: 20px;">
  <div class="card-body-styled" style="padding: 16px 20px;">
    <form method="GET" action="<?= BASE_URL ?>/admin/categories.php" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
      <div style="flex: 1; min-width: 220px;">
        <label class="form-label" style="margin-bottom: 4px;">Pencarian</label>
        <input type="text" name="search" class="form-control" placeholder="Cari nama pelanggaran, kode..." value="<?= e($filter_search) ?>">
      </div>
      <div style="width: 200px;">
        <label class="form-label" style="margin-bottom: 4px;">Tingkat Pelanggaran</label>
        <select name="tingkat" class="form-control">
          <option value="">Semua Tingkat</option>
          <option value="Ringan" <?= $filter_tingkat === 'Ringan' ? 'selected' : '' ?>>Ringan</option>
          <option value="Sedang" <?= $filter_tingkat === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
          <option value="Berat" <?= $filter_tingkat === 'Berat' ? 'selected' : '' ?>>Berat</option>
        </select>
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
        <?php if (!empty($filter_search) || !empty($filter_tingkat)): ?>
          <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-secondary" style="height: 38px;">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">Daftar Kategori Pelanggaran</h2>
      <span class="card-subtitle-styled">Total <?= count($categories) ?> jenis pelanggaran terkonfigurasi</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Pelanggaran</th>
            <th>Tingkat</th>
            <th style="text-align: center;">Bobot Poin</th>
            <th>Deskripsi / Ketentuan</th>
            <th style="text-align: center;">Total Insiden</th>
            <th>Status</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <div class="empty-state-title">Tidak ada kategori ditemukan</div>
                  <div class="empty-state-text">Silakan sesuaikan filter atau tambahkan kategori baru.</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($categories as $cat): 
              $sev_class = ($cat['tingkat'] === 'Berat') ? 'badge-berat' : (($cat['tingkat'] === 'Sedang') ? 'badge-sedang' : 'badge-ringan');
            ?>
              <tr>
                <td>
                  <span style="font-family: var(--font-mono); font-weight: 700; color: var(--color-primary);"><?= e($cat['kode']) ?></span>
                </td>
                <td>
                  <span style="font-weight: 600; color: var(--color-primary); font-size: 13.5px;"><?= e($cat['nama_pelanggaran']) ?></span>
                </td>
                <td>
                  <span class="badge <?= $sev_class ?>"><?= e($cat['tingkat']) ?></span>
                </td>
                <td style="text-align: center;">
                  <span class="badge-points danger" style="font-size: 12px; font-weight: 700;">+<?= (int)$cat['poin'] ?> Poin</span>
                </td>
                <td>
                  <div style="font-size: 12px; color: var(--color-text-muted); max-width: 300px;"><?= e($cat['deskripsi'] ?: '-') ?></div>
                </td>
                <td style="text-align: center;">
                  <span class="badge" style="background-color: #F1F5F9; font-weight: 600;"><?= (int)$cat['total_kejadian'] ?> Kasus</span>
                </td>
                <td>
                  <span class="status-pill <?= $cat['status'] === 'active' ? 'selesai' : 'menunggu' ?>">
                    <span class="status-dot"></span>
                    <?= $cat['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                  </span>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick='editCategory(<?= json_encode($cat) ?>)' title="Ubah Parameter">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="deleteCategory(<?= (int)$cat['id'] ?>, '<?= e(addslashes($cat['nama_pelanggaran'])) ?>')" style="color: var(--color-danger);" title="Hapus">
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

<!-- Modal Tambah / Edit Kategori -->
<div class="modal-overlay" id="category-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" id="category-modal-title">Tambah Kategori Pelanggaran</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('category-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/categories.php" method="POST" id="category-form">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="form_action" value="create">
      <input type="hidden" name="id" id="category_id" value="">

      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Kode Singkat <span class="required">*</span></label>
            <input type="text" name="kode" id="inp_kode" class="form-control" required placeholder="Contoh: R-09 / S-09 / B-09">
          </div>
          <div class="form-group">
            <label class="form-label">Tingkat Pelanggaran <span class="required">*</span></label>
            <select name="tingkat" id="inp_tingkat" class="form-control" required>
              <option value="Ringan">Ringan (5 - 15 Poin)</option>
              <option value="Sedang">Sedang (15 - 35 Poin)</option>
              <option value="Berat">Berat (40 - 100 Poin)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nama Pelanggaran <span class="required">*</span></label>
          <input type="text" name="nama_pelanggaran" id="inp_nama" class="form-control" required placeholder="Contoh: Datang terlambat ke sekolah">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bobot Poin Denda <span class="required">*</span></label>
            <input type="number" name="poin" id="inp_poin" class="form-control" required min="1" max="100" value="5">
          </div>
          <div class="form-group" id="grp_status" style="display: none;">
            <label class="form-label">Status Keaktifan</label>
            <select name="status" id="inp_status" class="form-control">
              <option value="active">Aktif Digunakan</option>
              <option value="inactive">Nonaktif</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Deskripsi Kejadian & Kriteria</label>
          <textarea name="deskripsi" id="inp_deskripsi" class="form-control" placeholder="Rincian batasan pelanggaran dan penjelasan bagi guru"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('category-modal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Parameter Kategori</button>
      </div>
    </form>
  </div>
</div>

<form id="delete-category-form" action="<?= BASE_URL ?>/admin/categories.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="delete">
  <input type="hidden" name="id" id="delete_category_id" value="">
</form>

<script>
function openAddModal() {
  document.getElementById('category-modal-title').textContent = 'Tambah Kategori Pelanggaran Baru';
  document.getElementById('form_action').value = 'create';
  document.getElementById('category_id').value = '';
  document.getElementById('category-form').reset();
  document.getElementById('grp_status').style.display = 'none';
  Modal.open('category-modal');
}

function editCategory(data) {
  document.getElementById('category-modal-title').textContent = 'Ubah Kategori: ' + data.kode;
  document.getElementById('form_action').value = 'edit';
  document.getElementById('category_id').value = data.id;
  document.getElementById('inp_kode').value = data.kode;
  document.getElementById('inp_nama').value = data.nama_pelanggaran;
  document.getElementById('inp_tingkat').value = data.tingkat;
  document.getElementById('inp_poin').value = data.poin;
  document.getElementById('inp_deskripsi').value = data.deskripsi || '';
  document.getElementById('inp_status').value = data.status || 'active';
  document.getElementById('grp_status').style.display = 'block';
  Modal.open('category-modal');
}

async function deleteCategory(id, name) {
  const ok = await confirmAction({
    title: 'Konfirmasi Penghapusan Kategori',
    message: `Apakah Anda yakin ingin menghapus kategori "${name}"? Kategori yang telah memiliki riwayat kasus tidak dapat dihapus.`,
    confirmText: 'Ya, Hapus',
    isDanger: true
  });
  if (ok) {
    document.getElementById('delete_category_id').value = id;
    document.getElementById('delete-category-form').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
