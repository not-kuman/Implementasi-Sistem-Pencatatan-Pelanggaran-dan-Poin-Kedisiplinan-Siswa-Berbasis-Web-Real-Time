<?php
/**
 * Authentication and RBAC (Role-Based Access Control)
 * Sistem Informasi Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa
 * SMA Kartika 1-2 Medan
 */

require_once __DIR__ . '/../config/app.php';

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get currently logged-in user data
function current_user() {
    static $user = null;
    if ($user !== null) return $user;

    if (!is_logged_in()) return null;

    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, role, status 
        FROM users 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        // Attach role-specific metadata
        if ($user['role'] === 'teacher') {
            $stmtT = $pdo->prepare("SELECT id as teacher_id, nip, nama_guru, jabatan, no_telp FROM teachers WHERE user_id = ?");
            $stmtT->execute([$user['id']]);
            $t = $stmtT->fetch();
            if ($t) {
                $user['teacher_id'] = $t['teacher_id'];
                $user['nip'] = $t['nip'];
                $user['nama_guru'] = $t['nama_guru'];
                $user['jabatan'] = $t['jabatan'];
            }
        } elseif ($user['role'] === 'parent') {
            $stmtP = $pdo->prepare("
                SELECT p.id as parent_id, p.student_id, p.nama_wali, p.hubungan, p.no_telp,
                       s.nama_lengkap as nama_siswa, s.nis, s.total_poin, c.nama_kelas
                FROM parents p
                JOIN students s ON p.student_id = s.id
                JOIN classes c ON s.class_id = c.id
                WHERE p.user_id = ?
            ");
            $stmtP->execute([$user['id']]);
            $p = $stmtP->fetch();
            if ($p) {
                $user['parent_id'] = $p['parent_id'];
                $user['student_id'] = $p['student_id'];
                $user['nama_wali'] = $p['nama_wali'];
                $user['nama_siswa'] = $p['nama_siswa'];
                $user['nis_siswa'] = $p['nis'];
                $user['kelas_siswa'] = $p['nama_kelas'];
                $user['total_poin_siswa'] = $p['total_poin'];
            }
        }
    }

    return $user;
}

// Require user to be logged in
function require_login() {
    if (!is_logged_in()) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'Sesi Anda telah kedaluwarsa. Silakan masuk kembali.'], 401);
        }
        header("Location: " . BASE_URL . "/login.php?error=unauthorized");
        exit;
    }
}

// Require specific role or array of roles
function require_role($allowed_roles) {
    require_login();
    $user = current_user();

    if (!$user) {
        session_destroy();
        header("Location: " . BASE_URL . "/login.php?error=invalid_session");
        exit;
    }

    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    if (!in_array($user['role'], $allowed_roles, true)) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk tindakan ini.'], 403);
        }
        // Redirect to their appropriate dashboard
        redirect_to_dashboard($user['role']);
    }
}

// Redirect user to their role-specific dashboard
function redirect_to_dashboard($role) {
    switch ($role) {
        case 'admin':
            header("Location: " . BASE_URL . "/admin/dashboard.php");
            break;
        case 'teacher':
            header("Location: " . BASE_URL . "/teacher/dashboard.php");
            break;
        case 'parent':
            header("Location: " . BASE_URL . "/parent/dashboard.php");
            break;
        default:
            header("Location: " . BASE_URL . "/login.php");
            break;
    }
    exit;
}

// Check if current request is AJAX
function is_ajax_request() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
}

// Generate CSRF Token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Output hidden input with CSRF token
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Verify CSRF token
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'Token keamanan CSRF tidak valid. Muat ulang halaman.'], 403);
        }
        http_response_code(403);
        die("Permintaan ditolak: Token keamanan (CSRF) tidak valid.");
    }
}
