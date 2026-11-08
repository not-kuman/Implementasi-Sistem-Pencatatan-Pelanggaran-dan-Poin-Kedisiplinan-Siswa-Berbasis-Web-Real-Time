<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * API Endpoint: Violations
 */

require_once __DIR__ . '/../config/app.php';
require_login();

$user = current_user();
$pdo = get_db_connection();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// 1. CREATE VIOLATION RECORD (Teacher or Admin)
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['admin', 'teacher']);
    verify_csrf();

    $student_id = (int)($_POST['student_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $tanggal_kejadian = trim($_POST['tanggal_kejadian'] ?? date('Y-m-d'));
    $waktu_kejadian = trim($_POST['waktu_kejadian'] ?? date('H:i:s'));
    $tempat_kejadian = trim($_POST['tempat_kejadian'] ?? 'Lingkungan Sekolah');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Teacher ID resolution
    $teacher_id = 1; // default fallback if admin
    if ($user['role'] === 'teacher') {
        $teacher_id = (int)($user['teacher_id'] ?? 1);
    } elseif (!empty($_POST['teacher_id'])) {
        $teacher_id = (int)$_POST['teacher_id'];
    }

    if ($student_id <= 0 || $category_id <= 0 || empty($keterangan)) {
        json_response([
            'success' => false,
            'message' => 'Lengkapi seluruh data wajib: Siswa, Kategori Pelanggaran, dan Keterangan Kejadian.'
        ], 422);
    }

    try {
        $result = record_violation_transaction(
            $student_id,
            $category_id,
            $teacher_id,
            $tanggal_kejadian,
            $waktu_kejadian,
            $tempat_kejadian,
            $keterangan
        );

        // Fetch class name for WA message
        $stmtC = $pdo->prepare("SELECT nama_kelas FROM classes WHERE id = ?");
        $stmtC->execute([$result['class_id']]);
        $class_name = $stmtC->fetchColumn() ?: 'Siswa';

        $date_formatted = format_date_id($tanggal_kejadian);

        // Build exact required WhatsApp message
        $wa_message = build_wa_message(
            $result['student_name'],
            $class_name,
            $result['category_name'],
            $date_formatted,
            $result['poin'],
            $result['new_total_poin']
        );

        $sanction_info = get_sanction_by_points($result['new_total_poin']);

        json_response([
            'success' => true,
            'message' => 'Pelanggaran berhasil dicatat ke sistem dan poin kedisiplinan telah diakumulasikan.',
            'record_id' => $result['record_id'],
            'kode_transaksi' => $result['kode_transaksi'],
            'student_name' => $result['student_name'],
            'class_name' => $class_name,
            'parent_phone' => $result['student_phone'],
            'points_added' => $result['poin'],
            'new_total_points' => $result['new_total_poin'],
            'sanction' => $sanction_info,
            'wa_message' => $wa_message
        ]);
    } catch (Exception $e) {
        json_response([
            'success' => false,
            'message' => 'Gagal mencatat transaksi: ' . $e->getMessage()
        ], 500);
    }
}

// 2. GET CATEGORIES LIST
if ($action === 'get_categories') {
    $stmt = $pdo->query("SELECT id, kode, nama_pelanggaran, tingkat, poin, deskripsi FROM violation_categories WHERE status = 'active' ORDER BY tingkat ASC, poin ASC");
    $cats = $stmt->fetchAll();
    json_response([
        'success' => true,
        'data' => $cats
    ]);
}

// 3. GET SINGLE VIOLATION DETAIL
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT vr.*, s.nama_lengkap as nama_siswa, s.nis, s.total_poin as akumulasi_siswa, s.no_telp_wali,
               c.nama_kelas, vc.nama_pelanggaran, vc.tingkat, t.nama_guru
        FROM violation_records vr
        JOIN students s ON vr.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN violation_categories vc ON vr.category_id = vc.id
        JOIN teachers t ON vr.teacher_id = t.id
        WHERE vr.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();

    if (!$rec) {
        json_response(['success' => false, 'message' => 'Data pelanggaran tidak ditemukan.'], 404);
    }

    // Role check for parent
    if ($user['role'] === 'parent' && (int)$user['student_id'] !== (int)$rec['student_id']) {
        json_response(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }

    $rec['tanggal_kejadian_formatted'] = format_date_id($rec['tanggal_kejadian']);
    $rec['waktu_kejadian_formatted'] = substr($rec['waktu_kejadian'], 0, 5) . ' WIB';

    // Get notifications sent for this record
    $stmtN = $pdo->prepare("SELECT id, parent_phone, status, provider, sent_at, response_log FROM notifications WHERE violation_record_id = ? ORDER BY id DESC");
    $stmtN->execute([$id]);
    $rec['notifications'] = $stmtN->fetchAll();

    json_response([
        'success' => true,
        'data' => $rec
    ]);
}

// 4. DELETE VIOLATION (Admin only)
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['admin']);
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['success' => false, 'message' => 'ID Rekaman tidak valid.'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT student_id, poin FROM violation_records WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new Exception("Catatan pelanggaran tidak ditemukan.");
        }

        $student_id = (int)$row['student_id'];
        $poin = (int)$row['poin'];

        // Delete record (CASCADE deletes notifications)
        $stmtDel = $pdo->prepare("DELETE FROM violation_records WHERE id = ?");
        $stmtDel->execute([$id]);

        // Subtract points from student (ensure does not drop below 0)
        $stmtUpd = $pdo->prepare("UPDATE students SET total_poin = GREATEST(0, total_poin - ?) WHERE id = ?");
        $stmtUpd->execute([$poin, $student_id]);

        $pdo->commit();

        json_response([
            'success' => true,
            'message' => 'Catatan pelanggaran berhasil dihapus dan akumulasi poin siswa telah dikurangi.'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

json_response(['success' => false, 'message' => 'Aksi API tidak valid.'], 400);
