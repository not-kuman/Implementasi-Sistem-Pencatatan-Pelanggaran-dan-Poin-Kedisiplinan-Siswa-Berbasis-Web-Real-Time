<?php
/**
 * Global Utility Functions
 * Sistem Informasi Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa
 * SMA Kartika 1-2 Medan
 */

// Escape HTML output safely
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Retrieve setting from database with static cache
function get_setting($key, $default = '') {
    static $settings_cache = null;

    if ($settings_cache === null) {
        $settings_cache = [];
        try {
            $pdo = get_db_connection();
            if ($pdo) {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
                while ($row = $stmt->fetch()) {
                    $settings_cache[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Throwable $e) {
            // Database is unreachable or table not migrated yet
            // Fall back gracefully to $default without throwing fatal error
        }
    }

    return $settings_cache[$key] ?? $default;
}

// Update or insert system setting
function set_setting($key, $value, $description = null) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            description = COALESCE(VALUES(description), description)
    ");
    return $stmt->execute([$key, $value, $description]);
}

// Format date to Indonesian text (e.g. 15 Oktober 2026)
function format_date_id($date_str) {
    if (!$date_str || $date_str === '0000-00-00') return '-';
    $timestamp = strtotime($date_str);
    if (!$timestamp) return $date_str;

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $day = date('j', $timestamp);
    $month_num = (int)date('n', $timestamp);
    $year = date('Y', $timestamp);

    return $day . ' ' . ($months[$month_num] ?? '') . ' ' . $year;
}

// Format datetime to Indonesian format
function format_datetime_id($datetime_str) {
    if (!$datetime_str || $datetime_str === '0000-00-00 00:00:00') return '-';
    $timestamp = strtotime($datetime_str);
    if (!$timestamp) return $datetime_str;

    $date_part = format_date_id(date('Y-m-d', $timestamp));
    $time_part = date('H:i', $timestamp);
    return $date_part . ' pukul ' . $time_part . ' WIB';
}

// Get Sanction details based on accumulated points
function get_sanction_by_points($points) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT * FROM sanctions 
        WHERE ? >= poin_min AND ? <= poin_max
        LIMIT 1
    ");
    $stmt->execute([(int)$points, (int)$points]);
    $sanction = $stmt->fetch();

    if (!$sanction) {
        if ($points <= 0) {
            return [
                'nama_sanksi' => 'Tertib (Tanpa Sanksi)',
                'tindakan' => 'Siswa menjaga perilaku terpuji dan mematuhi tata tertib sekolah.',
                'tingkat_urgensi' => 'Rendah',
                'poin_min' => 0,
                'poin_max' => 0
            ];
        } else {
            // Exceeds max range
            return [
                'nama_sanksi' => 'Sidang Pleno & Pengembalian Siswa ke Orang Tua',
                'tindakan' => 'Pemberhentian status peserta didik karena akumulasi poin melewati batas toleransi sekolah.',
                'tingkat_urgensi' => 'Kritis',
                'poin_min' => 100,
                'poin_max' => 999
            ];
        }
    }
    return $sanction;
}

// Generate unique transaction code: TRX-YYYYMM-XXXX
function generate_transaction_code() {
    $pdo = get_db_connection();
    $prefix = 'TRX-' . date('Ym') . '-';
    $stmt = $pdo->prepare("SELECT kode_transaksi FROM violation_records WHERE kode_transaksi LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        $num = (int)substr($last, strlen($prefix)) + 1;
    } else {
        $num = 1;
    }
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// Build exact required WhatsApp message template
function build_wa_message($student_name, $class_name, $violation_name, $date_formatted, $points, $total_points) {
    return "Yth. Bapak/Ibu Wali Siswa,\n\n" .
           "Kami informasikan bahwa siswa {$student_name} dari kelas {$class_name} telah tercatat melakukan pelanggaran sebagai berikut:\n\n" .
           "Jenis Pelanggaran: {$violation_name}\n" .
           "Tanggal: {$date_formatted}\n" .
           "Poin Pelanggaran: {$points}\n" .
           "Total Akumulasi Poin: {$total_points}\n\n" .
           "Mohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\n" .
           "Terima kasih.\n" .
           "SMA Kartika 1-2";
}

// Dispatch WhatsApp Notification (Handles DEMO Simulation and Gateway integration)
function dispatch_wa_notification($violation_record_id, $student_id, $parent_phone, $message, $sent_by_user_id = null) {
    $pdo = get_db_connection();

    $provider = get_setting('wa_gateway_provider', 'WAGateway-Simulator');
    $api_key = get_setting('wa_gateway_api_key', '');
    $is_demo = (int)get_setting('wa_demo_mode', '1');

    // Clean phone number (convert 08xx to 628xx)
    $clean_phone = preg_replace('/[^0-9]/', '', $parent_phone);
    if (str_starts_with($clean_phone, '0')) {
        $clean_phone = '62' . substr($clean_phone, 1);
    }

    $status = 'simulated';
    $response_payload = [];
    $is_success = false;

    if ($is_demo === 1 || empty($api_key) || $provider === 'WAGateway-Simulator') {
        // DEMO Simulator Mode
        $is_success = true;
        $status = 'simulated';
        $sim_msg_id = 'SIM-MSG-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        $response_payload = [
            'mode' => 'DEMO_SIMULATION',
            'code' => 200,
            'message' => "[MODE DEMO] Pesan simulasi terkirim ke {$clean_phone}",
            'message_id' => $sim_msg_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        // Production Gateway Integration (e.g. Fonnte / generic JSON API)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.fonnte.com/send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'target' => $clean_phone,
            'message' => $message,
            'countryCode' => '62'
        ]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: {$api_key}"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $status = 'failed';
            $response_payload = ['status' => 'error', 'curl_error' => $curl_error];
            $is_success = false;
        } else {
            $decoded = json_decode($result, true);
            $response_payload = $decoded ?: ['raw' => $result];
            if ($http_code === 200 && (!isset($decoded['status']) || $decoded['status'] == true)) {
                $status = 'sent';
                $is_success = true;
            } else {
                $status = 'failed';
                $is_success = false;
            }
        }
    }

    // Insert record to notifications table
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (violation_record_id, student_id, parent_phone, message, provider, status, response_log, sent_by_user_id, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $violation_record_id,
        $student_id,
        $parent_phone,
        $message,
        $provider,
        $status,
        json_encode($response_payload, JSON_UNESCAPED_UNICODE),
        $sent_by_user_id
    ]);
    $notification_id = $pdo->lastInsertId();

    return [
        'success' => $is_success,
        'status' => $status,
        'notification_id' => $notification_id,
        'phone' => $clean_phone,
        'is_demo' => ($status === 'simulated'),
        'response' => $response_payload
    ];
}

// Atomically record violation and recalculate student points
function record_violation_transaction($student_id, $category_id, $teacher_id, $date, $time, $location, $description) {
    $pdo = get_db_connection();

    try {
        $pdo->beginTransaction();

        // 1. Fetch category points
        $stmtCat = $pdo->prepare("SELECT poin, nama_pelanggaran FROM violation_categories WHERE id = ? FOR UPDATE");
        $stmtCat->execute([$category_id]);
        $cat = $stmtCat->fetch();
        if (!$cat) {
            throw new Exception("Kategori pelanggaran tidak ditemukan.");
        }
        $poin = (int)$cat['poin'];

        // 2. Lock student row & check existence
        $stmtStd = $pdo->prepare("SELECT id, total_poin, nama_lengkap, no_telp_wali, class_id FROM students WHERE id = ? FOR UPDATE");
        $stmtStd->execute([$student_id]);
        $student = $stmtStd->fetch();
        if (!$student) {
            throw new Exception("Data siswa tidak ditemukan.");
        }

        // 3. Generate unique transaction code
        $trx_code = generate_transaction_code();

        // 4. Insert violation record
        $stmtIns = $pdo->prepare("
            INSERT INTO violation_records 
            (kode_transaksi, student_id, category_id, teacher_id, poin, tanggal_kejadian, waktu_kejadian, tempat_kejadian, keterangan, status_tindak_lanjut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diproses')
        ");
        $stmtIns->execute([
            $trx_code,
            $student_id,
            $category_id,
            $teacher_id,
            $poin,
            $date,
            $time,
            $location,
            $description
        ]);
        $record_id = $pdo->lastInsertId();

        // 5. Update student total points atomically
        $new_total_poin = (int)$student['total_poin'] + $poin;
        $stmtUpd = $pdo->prepare("UPDATE students SET total_poin = ? WHERE id = ?");
        $stmtUpd->execute([$new_total_poin, $student_id]);

        $pdo->commit();

        return [
            'success' => true,
            'record_id' => $record_id,
            'kode_transaksi' => $trx_code,
            'student_id' => $student_id,
            'student_name' => $student['nama_lengkap'],
            'student_phone' => $student['no_telp_wali'],
            'class_id' => $student['class_id'],
            'category_name' => $cat['nama_pelanggaran'],
            'poin' => $poin,
            'new_total_poin' => $new_total_poin
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// JSON Output Helper
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
