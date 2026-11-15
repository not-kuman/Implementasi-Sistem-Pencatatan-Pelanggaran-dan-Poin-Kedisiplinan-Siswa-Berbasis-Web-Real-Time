<?php
/**
 * Application Global Configuration
 * Sistem Informasi Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa
 * SMA Kartika 1-2 Medan
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Start Session if not already active
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('VERCEL') || !@is_writable(session_save_path())) {
        $temp_dir = sys_get_temp_dir();
        if ($temp_dir && @is_writable($temp_dir)) {
            @session_save_path($temp_dir);
        }
    }
    // Set secure cookie params
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// App Information
define('APP_NAME', 'Sistem Informasi Kedisiplinan Siswa');
define('APP_SUBTITLE', 'Pencatatan Pelanggaran dan Poin Kedisiplinan');
define('INSTITUTION_NAME', 'Sistem Informasi Kedisiplinan Siswa');
define('SCHOOL_NAME', 'Sistem Informasi Kedisiplinan Siswa');
define('APP_VERSION', '1.0.0');

// Base URL detection
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (!empty(getenv('VERCEL')));

$protocol = $is_https ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Calculate base url up to root of this project
// If script_name contains /admin, /teacher, /parent, /api, trim it
$subdirs = ['/admin', '/teacher', '/parent', '/api'];
$base_path = $script_name;
foreach ($subdirs as $dir) {
    if (str_ends_with($base_path, $dir)) {
        $base_path = substr($base_path, 0, -strlen($dir));
    }
}
$base_path = rtrim($base_path, '/');

define('BASE_URL', $protocol . $host . $base_path);
define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
