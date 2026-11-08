<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * API Endpoint: Students
 */

require_once __DIR__ . '/../config/app.php';
require_login();

$user = current_user();
$pdo = get_db_connection();
$action = $_GET['action'] ?? 'search';

if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 1) {
        json_response(['success' => true, 'data' => []]);
    }

    $stmt = $pdo->prepare("
        SELECT s.id, s.nis, s.nama_lengkap, s.jenis_kelamin, s.total_poin, s.no_telp_wali, c.nama_kelas
        FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE s.status_siswa = 'aktif' AND (s.nama_lengkap LIKE ? OR s.nis LIKE ?)
        ORDER BY s.nama_lengkap ASC
        LIMIT 10
    ");
    $term = '%' . $q . '%';
    $stmt->execute([$term, $term]);
    $results = $stmt->fetchAll();

    json_response([
        'success' => true,
        'count' => count($results),
        'data' => $results
    ]);
}

if ($action === 'get_one') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        json_response(['success' => false, 'message' => 'ID Siswa tidak valid.'], 400);
    }

    // Role check: If parent, can only get their own child
    if ($user['role'] === 'parent' && (int)$user['student_id'] !== $id) {
        json_response(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }

    $stmt = $pdo->prepare("
        SELECT s.*, c.nama_kelas, c.tingkat, c.jurusan, t.nama_guru as nama_wali_kelas,
               p.nama_wali, p.hubungan, p.no_telp as no_telp_parent
        FROM students s
        JOIN classes c ON s.class_id = c.id
        LEFT JOIN teachers t ON c.wali_kelas_id = t.id
        LEFT JOIN parents p ON p.student_id = s.id
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if (!$student) {
        json_response(['success' => false, 'message' => 'Siswa tidak ditemukan.'], 404);
    }

    // Attach sanction info
    $student['sanction'] = get_sanction_by_points($student['total_poin']);

    json_response([
        'success' => true,
        'data' => $student
    ]);
}

json_response(['success' => false, 'message' => 'Aksi API tidak valid.'], 400);
