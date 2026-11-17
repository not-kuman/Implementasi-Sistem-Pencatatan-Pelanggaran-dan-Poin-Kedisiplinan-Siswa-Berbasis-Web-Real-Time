<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Core Module: Violation Recording & WhatsApp Integration
 */

require_once __DIR__ . '/../config/app.php';
require_role(['teacher', 'admin']);

$user = current_user();
$pdo = get_db_connection();
$teacher_id = ($user['role'] === 'teacher') ? ($user['teacher_id'] ?? 1) : 1;

$page_title = 'Pencatatan Pelanggaran Kedisiplinan Siswa';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . ($user['role'] === 'teacher' ? '/teacher/dashboard.php' : '/admin/dashboard.php')],
    ['title' => 'Input Pelanggaran', 'url' => '#']
];

// Fetch active violation categories grouped by level
$stmtCat = $pdo->query("SELECT id, kode, nama_pelanggaran, tingkat, poin, deskripsi FROM violation_categories WHERE status = 'active' ORDER BY tingkat ASC, poin ASC, kode ASC");
$all_categories = $stmtCat->fetchAll();

$categories_grouped = [
    'Ringan' => [],
    'Sedang' => [],
    'Berat' => []
];
foreach ($all_categories as $c) {
    $categories_grouped[$c['tingkat']][] = $c;
}

// Fetch active sanctions for preview
$sanctions = $pdo->query("SELECT * FROM sanctions ORDER BY poin_min ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Pencatatan Insiden Pelanggaran Tata Tertib</h1>
    <p class="page-subtitle">Sistem pembaruan poin otomatis berbasis transaksi basis data & transmisi notifikasi WhatsApp</p>
  </div>
</div>

<div class="grid-form-layout">
  <!-- LEFT: Form Input Interaktif -->
  <div>
    <div class="surface-card">
      <div class="card-header-styled">
        <h2 class="card-title-styled">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Formulir Transaksi Pelanggaran
        </h2>
      </div>
      <div class="card-body-styled">
        <form id="violation-form" autocomplete="off">
          <?= csrf_field() ?>
          <input type="hidden" name="teacher_id" value="<?= $teacher_id ?>">
          <input type="hidden" name="student_id" id="selected_student_id" value="">

          <!-- LANGKAH 1: Pencarian & Pemilihan Siswa -->
          <div class="form-group">
            <label class="form-label">
              1. Cari & Pilih Peserta Didik <span class="required">*</span>
            </label>
            <div class="autocomplete-wrapper">
              <input type="text" id="student-search-input" class="form-control" placeholder="Ketik nama lengkap atau NIS siswa (minimal 2 huruf)...">
              <div class="autocomplete-dropdown" id="student-dropdown"></div>
            </div>
            <small style="font-size: 11px; color: var(--color-text-muted);">Sistem akan mencari secara seketika dari pangkalan data siswa aktif.</small>
          </div>

          <!-- Preview Siswa yang Dipilih -->
          <div id="student-selected-box" style="display: none; margin-bottom: 20px;">
            <div class="student-card-preview">
              <div>
                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--color-accent-dark);">Peserta Didik Terpilih:</div>
                <div style="font-size: 16px; font-weight: 700; color: var(--color-primary); margin-top: 2px;" id="disp_student_name">-</div>
                <div style="font-size: 12px; color: var(--color-text-muted);">
                  NIS: <span id="disp_student_nis">-</span> &bull; Kelas: <strong id="disp_student_class">-</strong>
                </div>
                <div style="font-size: 11.5px; color: var(--color-text-muted); margin-top: 2px;">
                  WhatsApp Wali: <strong id="disp_student_phone">-</strong>
                </div>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 11px; color: var(--color-text-muted);">Poin Saat Ini:</div>
                <span class="badge-points" id="disp_student_points" style="font-size: 16px; padding: 4px 10px;">0 Poin</span>
                <div style="margin-top: 6px;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelectedStudent()">Ganti Siswa</button>
                </div>
              </div>
            </div>
          </div>

          <!-- LANGKAH 2: Kategori Pelanggaran -->
          <div class="form-group">
            <label class="form-label">
              2. Kategori Pelanggaran Tata Tertib <span class="required">*</span>
            </label>
            <select name="category_id" id="category_select" class="form-control" required disabled>
              <option value="">-- Pilih Siswa Terlebih Dahulu --</option>
              <?php foreach ($categories_grouped as $group_name => $cats): ?>
                <optgroup label="Tingkat <?= $group_name ?>">
                  <?php foreach ($cats as $cat): ?>
                    <option value="<?= $cat['id'] ?>" 
                            data-kode="<?= e($cat['kode']) ?>" 
                            data-tingkat="<?= e($cat['tingkat']) ?>" 
                            data-poin="<?= (int)$cat['poin'] ?>" 
                            data-nama="<?= e($cat['nama_pelanggaran']) ?>">
                      <?= e($cat['nama_pelanggaran']) ?> &bull; Tingkat <?= e($cat['tingkat']) ?> (+<?= (int)$cat['poin'] ?> Poin)
                    </option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Live Points Counter Badge -->
          <div id="points-indicator-box" style="display: none; margin-bottom: 20px; background-color: #FEF3C7; border: 1px solid #FDE68A; padding: 12px 16px; border-radius: var(--radius-md);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <span style="font-size: 12px; color: #78350F; font-weight: 600;">Bobot Poin Pelanggaran:</span>
                <div style="font-size: 16px; font-weight: 800; color: #92400E;" id="disp_added_points">+0 Poin</div>
              </div>
              <div style="text-align: right;">
                <span style="font-size: 12px; color: #78350F; font-weight: 600;">Akumulasi Baru Siswa:</span>
                <div style="font-size: 16px; font-weight: 800; color: #DC2626;" id="disp_projected_total">0 Poin</div>
              </div>
            </div>
          </div>

          <!-- LANGKAH 3: Waktu, Tempat, dan Keterangan -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Tanggal Kejadian <span class="required">*</span></label>
              <input type="date" name="tanggal_kejadian" id="inp_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Waktu Kejadian <span class="required">*</span></label>
              <input type="time" name="waktu_kejadian" id="inp_time" class="form-control" required value="<?= date('H:i') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Lokasi / Tempat Kejadian <span class="required">*</span></label>
            <input type="text" name="tempat_kejadian" id="inp_location" class="form-control" required value="Lingkungan Sekolah" placeholder="Contoh: Gerbang Utama / Ruang Kelas X MIPA 1 / Kantin">
          </div>

          <div class="form-group">
            <label class="form-label">Keterangan / Kronologi Kejadian <span class="required">*</span></label>
            <textarea name="keterangan" id="inp_description" class="form-control" rows="3" required placeholder="Tuliskan fakta kejadian secara objektif dan faktual..."></textarea>
          </div>

          <!-- Action Button -->
          <div style="margin-top: 24px;">
            <button type="submit" id="btn-submit-violation" class="btn btn-accent" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 700;" disabled>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              <span>Simpan Catatan Pelanggaran</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- RIGHT: Pratinjau WhatsApp & Ambang Sanksi -->
  <div>
    <!-- Live WhatsApp Bubble Preview -->
    <div class="surface-card">
      <div class="card-header-styled">
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Pratinjau Pesan WhatsApp Otomatis
        </h2>
      </div>
      <div class="card-body-styled">
        <div class="wa-message-card">
          <div class="wa-badge-demo">
            MODE DEMO SIMULATOR AKTIF
          </div>
          <div style="font-size: 11px; color: #54656F; margin-bottom: 6px;">
            Format notifikasi resmi kedisiplinan:
          </div>
          <div class="wa-bubble" id="wa-preview-text">Yth. Bapak/Ibu Wali Siswa,

Kami informasikan bahwa siswa [Nama Siswa] dari kelas [Kelas] telah tercatat melakukan pelanggaran sebagai berikut:

Jenis Pelanggaran: [Nama Pelanggaran]
Tanggal: [Tanggal]
Poin Pelanggaran: [Poin]
Total Akumulasi Poin: [Total Poin]

Mohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.

Terima kasih.
<?= e(APP_NAME) ?></div>
        </div>
      </div>
    </div>

    <!-- Sanksi Reference Card -->
    <div class="surface-card">
      <div class="card-header-styled">
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Proyeksi Tindak Lanjut Sanksi
        </h2>
      </div>
      <div class="card-body-styled" style="padding: 16px 20px;">
        <div id="projected-sanction-box" style="padding: 12px; background: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-md);">
          <div style="font-size: 11.5px; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Status Pembinaan Setelah Input:</div>
          <div style="font-size: 14px; font-weight: 700; color: var(--color-primary); margin-top: 4px;" id="disp_sanction_title">
            Tertib (Belum ada sanksi)
          </div>
          <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 2px;" id="disp_sanction_action">
            Pilih siswa dan kategori pelanggaran untuk melihat proyeksi sanksi.
          </div>
        </div>

        <div style="margin-top: 14px; border-top: 1px dashed var(--color-border); padding-top: 10px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--color-text-subtle); text-transform: uppercase; margin-bottom: 6px;">Tolak Ukur Ambang Poin Sekolah:</div>
          <?php foreach ($sanctions as $sc): ?>
            <div style="display: flex; justify-content: space-between; font-size: 11.5px; padding: 4px 0; border-bottom: 1px solid #F1F5F9;">
              <span><?= e($sc['nama_sanksi']) ?></span>
              <span style="font-family: var(--font-mono); font-weight: 600;"><?= $sc['poin_min'] ?>-<?= $sc['poin_max'] ?> Poin</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Berhasil & Pengiriman WhatsApp -->
<div class="modal-overlay" id="success-modal">
  <div class="modal-dialog">
    <div class="modal-header" style="background-color: var(--color-success-light); border-bottom-color: #86EFAC;">
      <h3 class="modal-title" style="color: #166534; display: flex; align-items: center; gap: 8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
        Pencatatan Berhasil Disimpan
      </h3>
      <button type="button" class="modal-close-btn" onclick="Modal.close('success-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div style="text-align: center; margin-bottom: 18px;">
        <div style="font-size: 17px; font-weight: 700; color: var(--color-primary);" id="succ-student-name">Nama Siswa</div>
        <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 3px;" id="succ-violation-details">Pelanggaran (+X Poin)</div>
      </div>

      <div style="background-color: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 14px; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 12.5px; color: var(--color-text-muted);">Akumulasi Poin Baru:</span>
          <span class="badge-points danger" style="font-size: 16px;" id="succ-new-total">0 Poin</span>
        </div>
        <div style="font-size: 12px; margin-top: 6px; color: var(--color-text-main);">
          Tindak Lanjut: <strong id="succ-sanction-name">-</strong>
        </div>
      </div>

      <!-- WhatsApp Dispatch Box -->
      <div style="background-color: #EFEAE2; border: 1px solid #D5CFC7; border-radius: var(--radius-md); padding: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span style="font-size: 12.5px; font-weight: 700; color: #111B21;">Notifikasi WhatsApp Wali Siswa:</span>
          <span class="badge" id="succ-wa-status" style="background-color: #FEF3C7; color: #92400E;">Siap Dikirim</span>
        </div>
        <div style="font-size: 12px; color: #475569; margin-bottom: 12px;">
          Kirimkan pesan peringatan resmi langsung ke nomor wali (<strong id="succ-wa-phone">08xxxx</strong>).
        </div>
        <button type="button" class="btn btn-accent" id="btn-modal-send-wa" style="width: 100%; padding: 10px; font-weight: 700;" onclick="dispatchModalWhatsApp()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <span>Kirim Notifikasi WhatsApp Sekarang</span>
        </button>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="resetFormForNew()">+ Catat Siswa Lain</button>
      <a href="<?= BASE_URL ?>/teacher/history.php" class="btn btn-primary">Buka Riwayat Input</a>
    </div>
  </div>
</div>

<script>
// Sanctions matrix client cache
const SANCTIONS_DATA = <?= json_encode($sanctions) ?>;

let selectedStudent = null;
let lastRecordedResult = null;
let searchTimer = null;

const searchInput = document.getElementById('student-search-input');
const dropdown = document.getElementById('student-dropdown');
const categorySelect = document.getElementById('category_select');
const submitBtn = document.getElementById('btn-submit-violation');

// 1. Asynchronous Student Search Autocomplete
searchInput.addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  if (q.length < 2) {
    dropdown.innerHTML = '';
    dropdown.classList.remove('active');
    return;
  }

  searchTimer = setTimeout(async () => {
    dropdown.innerHTML = '<div style="padding:10px; color:var(--color-text-muted); font-size:12px;">Mencari di basis data...</div>';
    dropdown.classList.add('active');

    try {
      const res = await apiRequest(`<?= BASE_URL ?>/api/students.php?action=search&q=${encodeURIComponent(q)}`);
      if (res.success && res.data.length > 0) {
        let html = '';
        res.data.forEach(s => {
          html += `
            <div class="autocomplete-item" onclick='selectStudent(${JSON.stringify(s)})'>
              <div>
                <div style="font-weight:600; color:var(--color-primary);">${s.nama_lengkap}</div>
                <div style="font-size:11px; color:var(--color-text-muted);">NIS: ${s.nis} &bull; Kelas: ${s.nama_kelas}</div>
              </div>
              <div>
                <span class="badge-points ${s.total_poin >= 30 ? 'warning' : 'safe'}">${s.total_poin} Poin</span>
              </div>
            </div>
          `;
        });
        dropdown.innerHTML = html;
      } else {
        dropdown.innerHTML = '<div style="padding:10px; color:var(--color-text-muted); font-size:12px;">Siswa tidak ditemukan.</div>';
      }
    } catch (e) {
      dropdown.innerHTML = '<div style="padding:10px; color:var(--color-danger); font-size:12px;">Gagal memuat hasil pencarian.</div>';
    }
  }, 250);
});

// Close dropdown on click outside
document.addEventListener('click', function(e) {
  if (!dropdown.contains(e.target) && e.target !== searchInput) {
    dropdown.classList.remove('active');
  }
});

// 2. Select Student Handler
function selectStudent(student) {
  selectedStudent = student;
  document.getElementById('selected_student_id').value = student.id;
  document.getElementById('disp_student_name').textContent = student.nama_lengkap;
  document.getElementById('disp_student_nis').textContent = student.nis;
  document.getElementById('disp_student_class').textContent = student.nama_kelas;
  document.getElementById('disp_student_phone').textContent = student.no_telp_wali || 'Belum diisi';
  document.getElementById('disp_student_points').textContent = student.total_poin + ' Poin';

  // Toggle UI
  searchInput.parentElement.parentElement.style.display = 'none';
  document.getElementById('student-selected-box').style.display = 'block';
  dropdown.classList.remove('active');

  // Enable category select & submit button
  categorySelect.disabled = false;
  categorySelect.options[0].textContent = '-- Pilih Kategori Pelanggaran --';
  categorySelect.focus();

  updateCalculationsAndPreview();
}

// 3. Clear Selected Student
function clearSelectedStudent() {
  selectedStudent = null;
  document.getElementById('selected_student_id').value = '';
  document.getElementById('student-selected-box').style.display = 'none';
  searchInput.parentElement.parentElement.style.display = 'block';
  searchInput.value = '';
  searchInput.focus();

  categorySelect.selectedIndex = 0;
  categorySelect.disabled = true;
  categorySelect.options[0].textContent = '-- Pilih Siswa Terlebih Dahulu --';
  submitBtn.disabled = true;
  document.getElementById('points-indicator-box').style.display = 'none';

  updateCalculationsAndPreview();
}

// 4. Category Change Handler & Real-Time Calculations
categorySelect.addEventListener('change', updateCalculationsAndPreview);
document.getElementById('inp_date').addEventListener('change', updateCalculationsAndPreview);

function updateCalculationsAndPreview() {
  const opt = categorySelect.options[categorySelect.selectedIndex];
  const hasCat = opt && opt.value !== '';

  submitBtn.disabled = !(selectedStudent && hasCat);

  if (selectedStudent && hasCat) {
    const pts = parseInt(opt.getAttribute('data-poin')) || 0;
    const currentPts = parseInt(selectedStudent.total_poin) || 0;
    const projectedTotal = currentPts + pts;

    document.getElementById('points-indicator-box').style.display = 'block';
    document.getElementById('disp_added_points').textContent = '+' + pts + ' Poin';
    document.getElementById('disp_projected_total').textContent = projectedTotal + ' Poin';

    // Projected Sanction Lookup
    let foundSanction = null;
    for (const sc of SANCTIONS_DATA) {
      if (projectedTotal >= parseInt(sc.poin_min) && projectedTotal <= parseInt(sc.poin_max)) {
        foundSanction = sc;
        break;
      }
    }
    if (!foundSanction && projectedTotal >= 100) {
      foundSanction = {
        nama_sanksi: 'Sidang Pleno & Pengembalian Siswa ke Orang Tua',
        tindakan: 'Pemberhentian status peserta didik karena akumulasi poin melewati batas toleransi sekolah.'
      };
    }

    if (foundSanction) {
      document.getElementById('disp_sanction_title').textContent = foundSanction.nama_sanksi;
      document.getElementById('disp_sanction_action').textContent = foundSanction.tindakan;
    } else {
      document.getElementById('disp_sanction_title').textContent = 'Tertib (Tanpa Sanksi Khusus)';
      document.getElementById('disp_sanction_action').textContent = 'Siswa mematuhi tata tertib sekolah.';
    }

    // Update Live WhatsApp Bubble Preview
    const studentName = selectedStudent.nama_lengkap;
    const className = selectedStudent.nama_kelas;
    const violationName = opt.getAttribute('data-nama');
    const dateVal = document.getElementById('inp_date').value;

    const waText = 
`Yth. Bapak/Ibu Wali Siswa,

Kami informasikan bahwa siswa ${studentName} dari kelas ${className} telah tercatat melakukan pelanggaran sebagai berikut:

Jenis Pelanggaran: ${violationName}
Tanggal: ${dateVal}
Poin Pelanggaran: ${pts}
Total Akumulasi Poin: ${projectedTotal}

Mohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.

Terima kasih.
<?= json_encode(APP_NAME) ?>.replace(/^"|"$/g, '');

    document.getElementById('wa-preview-text').textContent = waText;

  } else {
    document.getElementById('points-indicator-box').style.display = 'none';
    document.getElementById('disp_sanction_title').textContent = 'Tertib (Belum ada sanksi)';
    document.getElementById('disp_sanction_action').textContent = 'Pilih siswa dan kategori pelanggaran untuk melihat proyeksi sanksi.';
  }
}

// 5. Form Submission via AJAX with Transaction & Double-Submit Guard
document.getElementById('violation-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  if (!selectedStudent || !categorySelect.value) {
    Toast.error('Formulir Belum Lengkap', 'Pilih siswa dan kategori pelanggaran terlebih dahulu.');
    return;
  }

  const ok = await confirmAction({
    title: 'Konfirmasi Pencatatan Pelanggaran',
    message: `Apakah Anda yakin ingin merekam pelanggaran untuk "${selectedStudent.nama_lengkap}"? Total poin kedisiplinan siswa akan diakumulasikan secara otomatis.`,
    confirmText: 'Simpan ke Database'
  });

  if (!ok) return;

  submitBtn.disabled = true;
  submitBtn.innerHTML = 'Menyimpan Transaksi...';

  const formData = new FormData(this);
  formData.append('action', 'create');

  try {
    const res = await apiRequest(`<?= BASE_URL ?>/api/violations.php`, {
      method: 'POST',
      body: formData
    });

    if (res.success) {
      lastRecordedResult = res;
      Toast.success('Berhasil', res.message);

      // Populate Success Modal
      document.getElementById('succ-student-name').textContent = res.student_name;
      document.getElementById('succ-violation-details').textContent = `${res.category_name} (+${res.points_added} Poin)`;
      document.getElementById('succ-new-total').textContent = res.new_total_points + ' Poin';
      document.getElementById('succ-sanction-name').textContent = res.sanction ? res.sanction.nama_sanksi : 'Dalam Pengawasan';
      document.getElementById('succ-wa-phone').textContent = res.parent_phone || 'Nomor Belum Ada';
      document.getElementById('succ-wa-status').textContent = 'Siap Dikirim';
      document.getElementById('succ-wa-status').className = 'badge';
      document.getElementById('succ-wa-status').style.backgroundColor = '#FEF3C7';
      document.getElementById('succ-wa-status').style.color = '#92400E';

      const sendBtn = document.getElementById('btn-modal-send-wa');
      sendBtn.disabled = false;
      sendBtn.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span>Kirim Notifikasi WhatsApp Sekarang</span>
      `;

      Modal.open('success-modal');
    } else {
      Toast.error('Gagal Menyimpan', res.message);
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Simpan Catatan Pelanggaran';
    }
  } catch (err) {
    Toast.error('Kesalahan', 'Terjadi kesalahan sistem saat menyimpan data.');
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Simpan Catatan Pelanggaran';
  }
});

// 6. Dispatch WhatsApp from Success Modal
async function dispatchModalWhatsApp() {
  if (!lastRecordedResult) return;

  const btn = document.getElementById('btn-modal-send-wa');
  btn.disabled = true;
  btn.innerHTML = 'Mengirimkan Notifikasi ke Gateway...';

  try {
    const formData = new FormData();
    formData.append('action', 'send_wa');
    formData.append('record_id', lastRecordedResult.record_id);
    formData.append('parent_phone', lastRecordedResult.parent_phone);
    formData.append('message', lastRecordedResult.wa_message);

    const res = await apiRequest(`<?= BASE_URL ?>/api/notifications.php`, {
      method: 'POST',
      body: formData
    });

    if (res.success) {
      Toast.success('Notifikasi WhatsApp', res.message);
      const statusBadge = document.getElementById('succ-wa-status');
      statusBadge.textContent = res.is_demo ? 'TERKIRIM (MODE DEMO)' : 'BERHASIL TERKIRIM';
      statusBadge.style.backgroundColor = '#EBF6F0';
      statusBadge.style.color = '#166534';

      btn.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
        <span>Pesan Notifikasi Sukses Diproses</span>
      `;
    } else {
      Toast.error('Gagal Notifikasi', res.message);
      btn.disabled = false;
      btn.innerHTML = 'Coba Kirim Ulang';
    }
  } catch (err) {
    Toast.error('Kesalahan', 'Gagal memicu pengiriman pesan WhatsApp.');
    btn.disabled = false;
    btn.innerHTML = 'Coba Kirim Ulang';
  }
}

// 7. Reset Form for Next Input
function resetFormForNew() {
  Modal.close('success-modal');
  clearSelectedStudent();
  document.getElementById('inp_description').value = '';
  document.getElementById('inp_location').value = 'Lingkungan Sekolah';
  submitBtn.disabled = true;
  submitBtn.innerHTML = 'Simpan Catatan Pelanggaran';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
