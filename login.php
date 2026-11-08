<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Authentication Page (Login)
 */

require_once __DIR__ . '/config/app.php';

// If already logged in, redirect directly to dashboard
if (is_logged_in()) {
    $user = current_user();
    if ($user) {
        redirect_to_dashboard($user['role']);
    }
}

$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error_message = 'Silakan masuk terlebih dahulu untuk mengakses sistem.';
    } elseif ($_GET['error'] === 'invalid_session') {
        $error_message = 'Sesi Anda tidak valid. Silakan masuk kembali.';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Nama pengguna dan kata sandi wajib diisi.';
    } else {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT id, username, password, full_name, role, status FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error_message = 'Akun Anda dinonaktifkan oleh Administrator. Hubungi pihak sekolah.';
            } else {
                // Regenerate session ID for security against session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Update last login timestamp
                $stmtUpd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmtUpd->execute([$user['id']]);

                // Redirect based on role
                redirect_to_dashboard($user['role']);
            }
        } else {
            $error_message = 'Nama pengguna atau kata sandi tidak cocok.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk Sistem &bull; <?= e(SCHOOL_NAME) ?></title>
  
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo-kartika.svg">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
  <!-- Left Side: Form Autentikasi Vertikal -->
  <div class="auth-form-side">
    <div>
      <div class="auth-brand">
        <img src="<?= BASE_URL ?>/assets/images/logo-kartika.svg" alt="Logo SMA Kartika 1-2 Medan" class="auth-brand-logo">
        <div class="auth-brand-info">
          <span class="auth-brand-school"><?= e(SCHOOL_NAME) ?></span>
          <span class="auth-brand-system">Sistem Informasi Kedisiplinan Siswa</span>
        </div>
      </div>

      <div class="auth-heading">
        <h1 class="auth-title">Masuk ke Portal</h1>
        <p class="auth-desc">Gunakan kredensial akun terdaftar Anda untuk melanjutkan.</p>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="auth-alert-error">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span><?= e($error_message) ?></span>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['logged_out'])): ?>
        <div class="auth-alert-success">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span>Anda telah berhasil keluar dari sistem dengan aman.</span>
        </div>
      <?php endif; ?>

      <form action="<?= BASE_URL ?>/login.php" method="POST" autocomplete="off">
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="username" class="form-label">Nama Pengguna (Username)</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Kata Sandi (Password)</label>
          <div class="password-input-group">
            <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
            <button type="button" class="password-toggle-btn" id="toggle-pw-btn" aria-label="Tampilkan / Sembunyikan Sandi">
              <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login-submit">
          <span>Masuk Sistem</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
          </svg>
        </button>
      </form>

      <!-- Quick Demo Account Pills -->
      <div class="demo-accounts-box">
        <div class="demo-title">Akun Uji Coba Cepat (Klik untuk Mengisi):</div>
        <div class="demo-pills">
          <button type="button" class="demo-pill" data-user="admin" data-pw="admin123" title="Masuk sebagai Administrator">
            Admin: admin
          </button>
          <button type="button" class="demo-pill" data-user="guru1" data-pw="guru123" title="Masuk sebagai Guru BK Hendra Gunawan">
            Guru: guru1
          </button>
          <button type="button" class="demo-pill" data-user="guru2" data-pw="guru123" title="Masuk sebagai Guru Siti Aminah">
            Guru: guru2
          </button>
          <button type="button" class="demo-pill" data-user="wali_farhan" data-pw="wali123" title="Masuk sebagai Wali Siswa M. Farhan">
            Wali: wali_farhan
          </button>
        </div>
      </div>
    </div>

    <div class="auth-footer-text">
      &copy; 2026 &mdash; Copyright by ARSYAD SADEWA, BAGAS PRASETYO, NIKEN AYU RESDIANA. All Rights Reserved.
    </div>
  </div>

  <!-- Right Side: Integrasi Peta Digital & Identitas Institusi Sesuai Makalah -->
  <div class="auth-info-side">
    <div class="school-info-card">
      <h2 class="info-title"><?= e(SCHOOL_NAME) ?></h2>
      <p class="info-address">
        <?= e(get_setting('school_address', 'Jl. Brigjend Katamso No. 235, Medan Maimun, Kota Medan, Sumatera Utara')) ?><br>
        Telepon: <?= e(get_setting('school_phone', '(061) 7864321')) ?><br>
        Email: <?= e(get_setting('school_email', 'info@kartika12medan.sch.id')) ?>
      </p>

      <!-- Digital Map Integration Container -->
      <div class="map-container-box">
        <!-- OpenStreetMap embed centered at SMA Kartika 1-2 Medan coordinates -->
        <iframe 
          title="Peta Lokasi SMA Kartika 1-2 Medan"
          src="https://www.openstreetmap.org/export/embed.html?bbox=98.6750%2C3.5550%2C98.6990%2C3.5750&amp;layer=mapnik&amp;marker=3.5658%2C98.6872"
          loading="lazy">
        </iframe>
        <div class="map-badge-coords">
          GPS: 3.5658° N, 98.6872° E
        </div>
      </div>
    </div>

    <div class="info-security-notice">
      <svg class="info-security-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      </svg>
      <span>Pencatatan real-time, transaksi atomik, dan notifikasi WhatsApp terintegrasi.</span>
    </div>
  </div>
</div>

<footer class="auth-page-footer">
  &copy; 2026 &mdash; Copyright by ARSYAD SADEWA, BAGAS PRASETYO, NIKEN AYU RESDIANA. All Rights Reserved.
</footer>

<script>
// Toggle Password Visibility
const toggleBtn = document.getElementById('toggle-pw-btn');
const pwInput = document.getElementById('password');
if (toggleBtn && pwInput) {
  toggleBtn.addEventListener('click', function() {
    const isPw = pwInput.type === 'password';
    pwInput.type = isPw ? 'text' : 'password';
    toggleBtn.innerHTML = isPw 
      ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
      : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
  });
}

// Demo Account Auto-Fill
document.querySelectorAll('.demo-pill').forEach(pill => {
  pill.addEventListener('click', function() {
    document.getElementById('username').value = this.getAttribute('data-user');
    document.getElementById('password').value = this.getAttribute('data-pw');
    document.querySelector('.btn-login-submit').focus();
  });
});
</script>

</body>
</html>
