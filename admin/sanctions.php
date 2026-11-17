<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin: Sanctions Configuration (CRUD & Tier Analysis)
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = get_db_connection();
$page_title = 'Konfigurasi Ketentuan Sanksi & Ambang Batas Poin';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/dashboard.php'],
    ['title' => 'Ketentuan Sanksi', 'url' => '#']
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create') {
        $nama_sanksi = trim($_POST['nama_sanksi'] ?? '');
        $poin_min = (int)($_POST['poin_min'] ?? 0);
        $poin_max = (int)($_POST['poin_max'] ?? 0);
        $tindakan = trim($_POST['tindakan'] ?? '');
        $tingkat_urgensi = $_POST['tingkat_urgensi'] ?? 'Sedang';

        if (empty($nama_sanksi) || $poin_max < $poin_min || empty($tindakan)) {
            $_SESSION['flash_error'] = 'Nama Sanksi, Rentang Poin yang valid (Max >= Min), dan Tindakan wajib diisi.';
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO sanctions (nama_sanksi, poin_min, poin_max, tindakan, tingkat_urgensi)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtIns->execute([$nama_sanksi, $poin_min, $poin_max, $tindakan, $tingkat_urgensi]);
            $_SESSION['flash_success'] = "Tingkat sanksi '{$nama_sanksi}' berhasil ditambahkan.";
        }
        header("Location: " . BASE_URL . "/admin/sanctions.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_sanksi = trim($_POST['nama_sanksi'] ?? '');
        $poin_min = (int)($_POST['poin_min'] ?? 0);
        $poin_max = (int)($_POST['poin_max'] ?? 0);
        $tindakan = trim($_POST['tindakan'] ?? '');
        $tingkat_urgensi = $_POST['tingkat_urgensi'] ?? 'Sedang';

        if ($id <= 0 || empty($nama_sanksi) || $poin_max < $poin_min || empty($tindakan)) {
            $_SESSION['flash_error'] = 'Data yang dikirimkan tidak valid.';
        } else {
            $stmtUpd = $pdo->prepare("
                UPDATE sanctions 
                SET nama_sanksi = ?, poin_min = ?, poin_max = ?, tindakan = ?, tingkat_urgensi = ?
                WHERE id = ?
            ");
            $stmtUpd->execute([$nama_sanksi, $poin_min, $poin_max, $tindakan, $tingkat_urgensi, $id]);
            $_SESSION['flash_success'] = "Tingkat sanksi berhasil diperbarui.";
        }
        header("Location: " . BASE_URL . "/admin/sanctions.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM sanctions WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "Ketentuan sanksi berhasil dihapus.";
        }
        header("Location: " . BASE_URL . "/admin/sanctions.php");
        exit;
    }
}

// Fetch Sanctions with Student Counts in Tier
$stmt = $pdo->query("SELECT * FROM sanctions ORDER BY poin_min ASC");
$sanctions = $stmt->fetchAll();

foreach ($sanctions as &$sc) {
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM students WHERE status_siswa = 'aktif' AND total_poin >= ? AND total_poin <= ?");
    $stmtC->execute([$sc['poin_min'], $sc['poin_max']]);
    $sc['siswa_dalam_tier'] = (int)$stmtC->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Ketentuan Tingkat Sanksi & Ambang Batas Poin</h1>
    <p class="page-subtitle">Pedoman formal batas akumulasi poin denda dan intervensi edukatif tata tertib siswa</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openAddModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      <span>Tambah Tingkat Sanksi</span>
    </button>
  </div>
</div>

<div class="surface-card">
  <div class="card-header-styled">
    <div>
      <h2 class="card-title-styled">Matriks Kebijakan Pembinaan Siswa</h2>
      <span class="card-subtitle-styled">Tersinkronisasi otomatis dengan profil siswa dan dashboard wali</span>
    </div>
  </div>
  <div class="card-body-styled" style="padding: 0;">
    <div class="table-responsive">
      <table class="app-table">
        <thead>
          <tr>
            <th style="text-align: center;">Ambang Rentang Poin</th>
            <th>Tingkat Sanksi / Dokumen</th>
            <th>Tindakan & Prosedur Intervensi Sekolah</th>
            <th>Urgensi</th>
            <th style="text-align: center;">Siswa Terdampak</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sanctions as $s): 
            $urgency_class = ($s['tingkat_urgensi'] === 'Kritis') ? 'badge-berat' : (($s['tingkat_urgensi'] === 'Tinggi') ? 'badge-sedang' : 'badge-ringan');
          ?>
            <tr>
              <td style="text-align: center;">
                <span class="badge-points danger" style="font-size: 13px;">
                  <?= (int)$s['poin_min'] ?> &ndash; <?= (int)$s['poin_max'] ?> Poin
                </span>
              </td>
              <td>
                <span style="font-weight: 700; color: var(--color-primary); font-size: 13.5px;"><?= e($s['nama_sanksi']) ?></span>
              </td>
              <td>
                <div style="font-size: 12.5px; color: var(--color-text-main); line-height: 1.5; max-width: 440px;">
                  <?= e($s['tindakan']) ?>
                </div>
              </td>
              <td>
                <span class="badge <?= $urgency_class ?>"><?= e($s['tingkat_urgensi']) ?></span>
              </td>
              <td style="text-align: center;">
                <?php if ($s['siswa_dalam_tier'] > 0): ?>
                  <span class="badge" style="background-color: #FEF2F2; color: #DC2626; font-weight: 700;">
                    <?= $s['siswa_dalam_tier'] ?> Siswa
                  </span>
                <?php else: ?>
                  <span style="color: var(--color-text-subtle); font-size: 11.5px;">Nihil (0)</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick='editSanction(<?= json_encode($s) ?>)' title="Ubah Ketentuan">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="deleteSanction(<?= (int)$s['id'] ?>, '<?= e(addslashes($s['nama_sanksi'])) ?>')" style="color: var(--color-danger);" title="Hapus">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah / Edit Sanksi -->
<div class="modal-overlay" id="sanction-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" id="sanction-modal-title">Tambah Ketentuan Sanksi</h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('sanction-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form action="<?= BASE_URL ?>/admin/sanctions.php" method="POST" id="sanction-form">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" id="form_action" value="create">
      <input type="hidden" name="id" id="sanction_id" value="">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Tingkat Sanksi / Dokumen <span class="required">*</span></label>
          <input type="text" name="nama_sanksi" id="inp_nama_sanksi" class="form-control" required placeholder="Contoh: Surat Peringatan I (SP 1)">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Poin Minimum <span class="required">*</span></label>
            <input type="number" name="poin_min" id="inp_poin_min" class="form-control" required min="1" value="1">
          </div>
          <div class="form-group">
            <label class="form-label">Poin Maksimum <span class="required">*</span></label>
            <input type="number" name="poin_max" id="inp_poin_max" class="form-control" required min="1" value="15">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Tingkat Urgensi <span class="required">*</span></label>
          <select name="tingkat_urgensi" id="inp_tingkat_urgensi" class="form-control" required>
            <option value="Rendah">Rendah</option>
            <option value="Sedang">Sedang</option>
            <option value="Tinggi">Tinggi</option>
            <option value="Kritis">Kritis</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Tindakan Edukatif / Prosedur Sekolah <span class="required">*</span></label>
          <textarea name="tindakan" id="inp_tindakan" class="form-control" required placeholder="Jelaskan konsekuensi pembinaan dan tindak lanjut bagi siswa dan pemanggilan orang tua"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('sanction-modal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Ketentuan</button>
      </div>
    </form>
  </div>
</div>

<form id="delete-sanction-form" action="<?= BASE_URL ?>/admin/sanctions.php" method="POST" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="delete">
  <input type="hidden" name="id" id="delete_sanction_id" value="">
</form>

<script>
function openAddModal() {
  document.getElementById('sanction-modal-title').textContent = 'Tambah Tingkat Sanksi Baru';
  document.getElementById('form_action').value = 'create';
  document.getElementById('sanction_id').value = '';
  document.getElementById('sanction-form').reset();
  Modal.open('sanction-modal');
}

function editSanction(data) {
  document.getElementById('sanction-modal-title').textContent = 'Ubah Ketentuan Sanksi: ' + data.nama_sanksi;
  document.getElementById('form_action').value = 'edit';
  document.getElementById('sanction_id').value = data.id;
  document.getElementById('inp_nama_sanksi').value = data.nama_sanksi;
  document.getElementById('inp_poin_min').value = data.poin_min;
  document.getElementById('inp_poin_max').value = data.poin_max;
  document.getElementById('inp_tingkat_urgensi').value = data.tingkat_urgensi;
  document.getElementById('inp_tindakan').value = data.tindakan;
  Modal.open('sanction-modal');
}

async function deleteSanction(id, name) {
  const ok = await confirmAction({
    title: 'Konfirmasi Penghapusan Sanksi',
    message: `Apakah Anda yakin ingin menghapus tingkatan sanksi "${name}"?`,
    confirmText: 'Ya, Hapus',
    isDanger: true
  });
  if (ok) {
    document.getElementById('delete_sanction_id').value = id;
    document.getElementById('delete-sanction-form').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
