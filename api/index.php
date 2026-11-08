<?php
/**
 * Vercel Serverless Front-Controller Router
 * Sistem Informasi Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa
 * SMA Kartika 1-2 Medan
 */

$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($rawUri, PHP_URL_PATH) ?? '/';

// Root directory of project
$rootDir = dirname(__DIR__);

// Normalize path
$uriPath = '/' . ltrim($uriPath, '/');

// Default route -> index or login
if ($uriPath === '/' || $uriPath === '/index.php' || $uriPath === '') {
    require $rootDir . '/index.php';
    exit;
}

// Check direct PHP file match
$targetFile = $rootDir . $uriPath;

if (is_file($targetFile) && str_ends_with($targetFile, '.php')) {
    require $targetFile;
    exit;
}

// Check if appending .php matches an existing file (clean URLs)
if (is_file($targetFile . '.php')) {
    require $targetFile . '.php';
    exit;
}

// Check if directory with index.php exists
if (is_dir($targetFile) && is_file(rtrim($targetFile, '/') . '/index.php')) {
    require rtrim($targetFile, '/') . '/index.php';
    exit;
}

// Fallback for static assets if served through PHP runtime
$ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
$mimeMap = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'svg' => 'image/svg+xml',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'json' => 'application/json'
];

if (is_file($targetFile) && isset($mimeMap[$ext])) {
    header('Content-Type: ' . $mimeMap[$ext]);
    readfile($targetFile);
    exit;
}

// Route 404
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($uriPath);
exit;
