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

$db_configured = true;
if (env_get('VERCEL') && (DB_HOST === '127.0.0.1' || DB_HOST === 'localhost' || empty(env_get('DB_HOST')))) {
    $db_configured = false;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Nama pengguna dan kata sandi wajib diisi.';
    } else {
        try {
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
        } catch (PDOException $e) {
            if (getenv('VERCEL') && (DB_HOST === '127.0.0.1' || empty(getenv('DB_HOST')))) {
                $error_message = 'Database MySQL cloud belum disetel di Vercel. Silakan isi Environment Variables (DB_HOST, DB_USER, DB_PASS, DB_NAME) di Vercel Dashboard.';
            } else {
                $error_message = 'Gagal terhubung ke database: ' . $e->getMessage();
            }
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
  
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
  <!-- Left Side: Form Autentikasi Vertikal -->
  <div class="auth-form-side">
    <div>
      <div class="auth-brand">
        <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Logo Sistem Informasi Kedisiplinan Siswa" class="auth-brand-logo">
        <div class="auth-brand-info">
          <span class="auth-brand-school"><?= e(APP_NAME) ?></span>
          <span class="auth-brand-system"><?= e(APP_SUBTITLE) ?></span>
        </div>
      </div>

      <div class="auth-heading">
        <h1 class="auth-title">Masuk ke Portal</h1>
        <p class="auth-desc">Gunakan kredensial akun terdaftar Anda untuk melanjutkan.</p>
      </div>

      <?php if (!$db_configured): ?>
        <div class="auth-alert-error" style="background-color: #FFFBEB; border-color: #FCD34D; color: #92400E; margin-bottom: 20px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <div style="font-size: 11.5px; line-height: 1.5;">
            <strong>Database MySQL Belum Dikonfigurasi di Vercel:</strong><br>
            Aplikasi mendeteksi <code>DB_HOST</code> masih kosong / localhost. Silakan buka <strong>Project Settings &gt; Environment Variables</strong> di Vercel Dashboard, masukkan variabel database (Host, Port, User, Pass, Name), lalu lakukan <strong>Redeploy</strong>.
          </div>
        </div>
      <?php endif; ?>

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
    </div>

    <div class="auth-footer-text">
      &copy; 2026 &mdash; Copyright by ARSYAD SADEWA, BAGAS PRASETYO, NIKEN AYU RESDIANA. All Rights Reserved.
    </div>
  </div>

  <!-- Right Side: Informasi Fungsional Sistem & Kredibilitas Administratif -->
  <div class="auth-info-side">
    <div class="system-info-panel">
      <div class="info-kicker">PORTAL ADMINISTRASI RESMI</div>
      <h2 class="info-title"><?= e(APP_NAME) ?></h2>
      <p class="info-description">
        Sistem manajemen dan pembinaan tata tertib terpusat untuk memantau rekam jejak kedisiplinan serta akumulasi poin pelanggaran peserta didik secara transparan dan akuntabel.
      </p>

      <div class="info-feature-list">
        <div class="info-feature-item">
          <div class="feature-bullet"></div>
          <div>
            <strong>Pencatatan Insiden Terintegrasi</strong>
            <span>Pencatatan pelanggaran real-time dengan validasi data dan kalkulasi poin terverifikasi.</span>
          </div>
        </div>

        <div class="info-feature-item">
          <div class="feature-bullet"></div>
          <div>
            <strong>Klasifikasi & Sanksi Bertingkat</strong>
            <span>Hierarki kategori administratif (Ringan, Sedang, Berat) sesuai regulasi sekolah.</span>
          </div>
        </div>

        <div class="info-feature-item">
          <div class="feature-bullet"></div>
          <div>
            <strong>Komunikasi Terpadu ke Wali Siswa</strong>
            <span>Transmisi ringkasan informasi kedisiplinan guna sinergi pembinaan orang tua dan sekolah.</span>
          </div>
        </div>
      </div>
    </div>

    <div class="info-security-notice">
      <svg class="info-security-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      </svg>
      <span>Akses terbatas untuk Administrator, Guru / Tim BK, dan Wali Siswa terdaftar.</span>
    </div>
  </div>
</div>

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
</script>

</body>
</html>
