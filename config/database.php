<?php
/**
 * Database Configuration
 * Sistem Informasi Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa
 * SMA Kartika 1-2 Medan
 */

if (!function_exists('env_get')) {
    function env_get($key, $default = null) {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $default;
    }
}

$db_url = env_get('DATABASE_URL') ?: env_get('MYSQL_URL');
if ($db_url) {
    $parsed = parse_url($db_url);
    define('DB_HOST', $parsed['host'] ?? '127.0.0.1');
    define('DB_PORT', $parsed['port'] ?? '3306');
    define('DB_USER', isset($parsed['user']) ? urldecode($parsed['user']) : 'root');
    define('DB_PASS', isset($parsed['pass']) ? urldecode($parsed['pass']) : '');
    define('DB_NAME', ltrim($parsed['path'] ?? 'db_kartika_disiplin', '/'));
} else {
    define('DB_HOST', env_get('DB_HOST', '127.0.0.1'));
    define('DB_PORT', env_get('DB_PORT', '3306'));
    define('DB_USER', env_get('DB_USER', 'root'));
    define('DB_PASS', env_get('DB_PASS', ''));
    define('DB_NAME', env_get('DB_NAME', 'db_kartika_disiplin'));
}

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        // If running on Vercel without cloud DB configured, throw an informative exception
        if (env_get('VERCEL') && (DB_HOST === '127.0.0.1' || DB_HOST === 'localhost' || empty(env_get('DB_HOST')))) {
            throw new PDOException("Database Cloud MySQL belum disetel di Vercel. Silakan masukkan variabel DB_HOST, DB_USER, DB_PASS, DB_NAME di Vercel Settings > Environment Variables.");
        }

        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        // Cloud MySQL SSL Support (e.g. TiDB Cloud, Aiven, PlanetScale)
        if (env_get('DB_SSL') === 'true' || (env_get('VERCEL') && DB_HOST !== '127.0.0.1' && DB_HOST !== 'localhost')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'Koneksi ke basis data gagal: ' . $e->getMessage()
                ]));
            }
            throw $e;
        }
    }
    return $pdo;
}
