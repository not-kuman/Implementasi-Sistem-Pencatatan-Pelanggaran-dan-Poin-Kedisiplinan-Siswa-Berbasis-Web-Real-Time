<?php
/**
 * SIM Kedisiplinan Siswa
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
  <title>Masuk Sistem &bull; <?= e(APP_NAME) ?></title>
  
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="login-page">

<div class="login-wrapper">
  <!-- Header Identitas Aplikasi -->
  <header class="login-header">
    <div class="login-logo-wrap">
      <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Logo Sistem Informasi Kedisiplinan" class="login-logo">
    </div>
    <h1 class="login-title">Sistem Informasi Kedisiplinan Siswa</h1>
    <div class="login-subtitle">Pencatatan Pelanggaran dan Poin Kedisiplinan</div>
    <p class="login-desc">Portal manajemen tata tertib, pembinaan karakter, dan pemantauan poin kedisiplinan peserta didik.</p>
  </header>

  <!-- Card Formulir Login -->
  <div class="login-card">
    <div class="login-card-header">
      <h2 class="login-card-title">Masuk ke Portal</h2>
      <span class="login-card-badge">Autentikasi Terpadu</span>
    </div>

    <?php if (!$db_configured): ?>
      <div class="login-alert login-alert-error" style="background-color: #FFFBEB; border-color: #FCD34D; color: #92400E;">
        <svg class="login-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div class="login-alert-text">
          <strong>Database Cloud Belum Terhubung:</strong> Variabel <code>DB_HOST</code> belum diisi di dashboard hosting.
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
      <div class="login-alert login-alert-error" role="alert">
        <svg class="login-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div class="login-alert-text"><?= e($error_message) ?></div>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['logged_out'])): ?>
      <div class="login-alert login-alert-success" role="alert">
        <svg class="login-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <div class="login-alert-text">Anda telah berhasil keluar dari sistem dengan aman.</div>
      </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/login.php" method="POST" autocomplete="off" class="login-form">
      <?= csrf_field() ?>

      <div class="login-field-group">
        <label for="username" class="login-label">
          <span>Nama Pengguna (Username)</span>
          <span class="login-label-req">*</span>
        </label>
        <div class="login-input-wrap">
          <span class="login-input-prefix">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <input type="text" id="username" name="username" class="login-input" placeholder="Masukkan username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
        </div>
      </div>

      <div class="login-field-group">
        <label for="password" class="login-label">
          <span>Kata Sandi (Password)</span>
          <span class="login-label-req">*</span>
        </label>
        <div class="login-input-wrap">
          <span class="login-input-prefix">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input type="password" id="password" name="password" class="login-input login-input-password" placeholder="Masukkan kata sandi" required>
          <button type="button" class="login-password-toggle" id="toggle-pw-btn" aria-label="Tampilkan / Sembunyikan Kata Sandi">
            <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn-submit" id="btn-login">
        <span>Masuk ke Sistem</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
        </svg>
      </button>
    </form>

    <div class="login-roles-info">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      </svg>
      <span>Hak akses tersedia untuk <strong>Administrator</strong>, <strong>Guru / BK</strong>, dan <strong>Wali Siswa</strong>.</span>
    </div>
  </div>

  <!-- Copyright Anggota Kelompok -->
  <footer class="login-footer">
    <div class="login-copyright">
      &copy; 2026 &mdash; Copyright by ARSYAD SADEWA, BAGAS PRASETYO, NIKEN AYU RESDIANA.
    </div>
  </footer>
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
