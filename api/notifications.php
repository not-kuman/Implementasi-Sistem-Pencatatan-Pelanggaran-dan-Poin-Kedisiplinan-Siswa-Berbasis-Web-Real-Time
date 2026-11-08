<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * API Endpoint: WhatsApp Notifications
 */

require_once __DIR__ . '/../config/app.php';
require_login();

$user = current_user();
$pdo = get_db_connection();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// 1. DISPATCH WHATSAPP NOTIFICATION
if ($action === 'send_wa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['admin', 'teacher']);
    verify_csrf();

    $record_id = (int)($_POST['record_id'] ?? 0);
    $custom_message = trim($_POST['message'] ?? '');

    if ($record_id <= 0) {
        json_response(['success' => false, 'message' => 'ID Catatan Pelanggaran tidak valid.'], 400);
    }

    // Fetch violation record details
    $stmt = $pdo->prepare("
        SELECT vr.*, s.nama_lengkap as nama_siswa, s.no_telp_wali, s.total_poin,
               c.nama_kelas, vc.nama_pelanggaran
        FROM violation_records vr
        JOIN students s ON vr.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN violation_categories vc ON vr.category_id = vc.id
        WHERE vr.id = ?
        LIMIT 1
    ");
    $stmt->execute([$record_id]);
    $rec = $stmt->fetch();

    if (!$rec) {
        json_response(['success' => false, 'message' => 'Catatan pelanggaran tidak ditemukan.'], 404);
    }

    $parent_phone = trim($_POST['parent_phone'] ?? $rec['no_telp_wali']);
    if (empty($parent_phone)) {
        json_response(['success' => false, 'message' => 'Nomor WhatsApp wali siswa belum terdaftar.'], 422);
    }

    // Message to send
    $message = $custom_message;
    if (empty($message)) {
        $message = build_wa_message(
            $rec['nama_siswa'],
            $rec['nama_kelas'],
            $rec['nama_pelanggaran'],
            format_date_id($rec['tanggal_kejadian']),
            $rec['poin'],
            $rec['total_poin']
        );
    }

    try {
        $dispatch_result = dispatch_wa_notification(
            $rec['id'],
            $rec['student_id'],
            $parent_phone,
            $message,
            $user['id']
        );

        $status_label = ($dispatch_result['status'] === 'simulated') ? 'Simulasi Terkirim (MODE DEMO)' : 'Berhasil Dikirim';

        json_response([
            'success' => $dispatch_result['success'],
            'status' => $dispatch_result['status'],
            'is_demo' => $dispatch_result['is_demo'],
            'phone' => $dispatch_result['phone'],
            'notification_id' => $dispatch_result['notification_id'],
            'message' => "Notifikasi WhatsApp berhasil diproses [{$status_label}] ke nomor {$dispatch_result['phone']}.",
            'details' => $dispatch_result['response']
        ]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Gagal mengirimkan notifikasi: ' . $e->getMessage()], 500);
    }
}

// 2. GET NOTIFICATION LOGS
if ($action === 'logs') {
    $student_id = (int)($_GET['student_id'] ?? 0);
    $sql = "
        SELECT n.*, s.nama_lengkap as nama_siswa, vr.kode_transaksi, u.full_name as sent_by_name
        FROM notifications n
        JOIN students s ON n.student_id = s.id
        JOIN violation_records vr ON n.violation_record_id = vr.id
        LEFT JOIN users u ON n.sent_by_user_id = u.id
    ";
    $params = [];

    if ($student_id > 0) {
        if ($user['role'] === 'parent' && (int)$user['student_id'] !== $student_id) {
            json_response(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }
        $sql .= " WHERE n.student_id = ?";
        $params[] = $student_id;
    } elseif ($user['role'] === 'parent') {
        $sql .= " WHERE n.student_id = ?";
        $params[] = (int)$user['student_id'];
    }

    $sql .= " ORDER BY n.id DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    foreach ($logs as &$log) {
        $log['sent_at_formatted'] = format_datetime_id($log['sent_at']);
    }

    json_response(['success' => true, 'data' => $logs]);
}

json_response(['success' => false, 'message' => 'Aksi API tidak valid.'], 400);
