<?php
/**
 * Database Configuration
 * Sistem Informasi Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa
 * SMA Kartika 1-2 Medan
 */

$db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($db_url) {
    $parsed = parse_url($db_url);
    define('DB_HOST', $parsed['host'] ?? '127.0.0.1');
    define('DB_PORT', $parsed['port'] ?? '3306');
    define('DB_USER', isset($parsed['user']) ? urldecode($parsed['user']) : 'root');
    define('DB_PASS', isset($parsed['pass']) ? urldecode($parsed['pass']) : '');
    define('DB_NAME', ltrim($parsed['path'] ?? 'db_kartika_disiplin', '/'));
} else {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_NAME', getenv('DB_NAME') ?: 'db_kartika_disiplin');
}

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Cloud MySQL SSL Support (e.g. TiDB Cloud, Aiven, PlanetScale)
        if (getenv('DB_SSL') === 'true' || (getenv('VERCEL') && DB_HOST !== '127.0.0.1' && DB_HOST !== 'localhost')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            if (getenv('VERCEL') && DB_HOST === '127.0.0.1') {
                die("<h3>Konfigurasi Database Cloud Vercel Diperlukan</h3><p>Aplikasi berjalan di Vercel, tetapi variabel <code>DB_HOST</code> atau <code>DATABASE_URL</code> belum disetel ke server MySQL cloud.</p><p>Silakan buka <strong>Project Settings &gt; Environment Variables</strong> di Vercel Dashboard dan masukkan kredensial MySQL (seperti TiDB Cloud, Aiven, atau Railway).</p>");
            }
            die(json_encode([
                'success' => false,
                'message' => 'Koneksi ke basis data gagal: ' . $e->getMessage()
            ]));
        }
    }
    return $pdo;
}
