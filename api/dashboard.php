<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * API Endpoint: Dashboard Real-time Statistics
 */

require_once __DIR__ . '/../config/app.php';
require_login();

$user = current_user();
$pdo = get_db_connection();

if ($user['role'] === 'admin') {
    // 1. Overall Metrics
    $total_students = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status_siswa = 'aktif'")->fetchColumn();
    $total_teachers = (int)$pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $total_classes  = (int)$pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    $total_records  = (int)$pdo->query("SELECT COUNT(*) FROM violation_records")->fetchColumn();
    $total_points   = (int)$pdo->query("SELECT COALESCE(SUM(total_poin), 0) FROM students WHERE status_siswa = 'aktif'")->fetchColumn();

    // 2. Breakdown by Severity Level
    $stmtSev = $pdo->query("
        SELECT vc.tingkat, COUNT(vr.id) as count, COALESCE(SUM(vr.poin), 0) as total_points
        FROM violation_records vr
        JOIN violation_categories vc ON vr.category_id = vc.id
        GROUP BY vc.tingkat
    ");
    $severity_breakdown = ['Ringan' => 0, 'Sedang' => 0, 'Berat' => 0];
    while ($row = $stmtSev->fetch()) {
        $severity_breakdown[$row['tingkat']] = (int)$row['count'];
    }

    // 3. Top Categories (Horizontal Bar Chart Data)
    $stmtTopCat = $pdo->query("
        SELECT vc.id, vc.nama_pelanggaran, vc.tingkat, vc.poin, COUNT(vr.id) as total_kasus
        FROM violation_records vr
        JOIN violation_categories vc ON vr.category_id = vc.id
        GROUP BY vc.id, vc.nama_pelanggaran, vc.tingkat, vc.poin
        ORDER BY total_kasus DESC
        LIMIT 8
    ");
    $chart_categories = [
        'labels' => [],
        'data' => [],
        'percentages' => [],
        'levels' => [],
        'points' => [],
        'items' => []
    ];
    while ($r = $stmtTopCat->fetch()) {
        $count = (int)$r['total_kasus'];
        $pct = $total_records > 0 ? round(($count / $total_records) * 100, 1) : 0;
        $chart_categories['labels'][] = $r['nama_pelanggaran'];
        $chart_categories['data'][] = $count;
        $chart_categories['percentages'][] = $pct;
        $chart_categories['levels'][] = $r['tingkat'];
        $chart_categories['points'][] = (int)$r['poin'];
        $chart_categories['items'][] = [
            'id' => (int)$r['id'],
            'nama' => $r['nama_pelanggaran'],
            'tingkat' => $r['tingkat'],
            'poin' => (int)$r['poin'],
            'count' => $count,
            'percentage' => $pct
        ];
    }

    // 4. Monthly Trend (Line Chart Data - Chronological with Indonesian Labels)
    $stmtTrend = $pdo->query("
        SELECT DATE_FORMAT(tanggal_kejadian, '%Y-%m') as ym, 
               COUNT(id) as count
        FROM violation_records
        GROUP BY DATE_FORMAT(tanggal_kejadian, '%Y-%m')
        ORDER BY ym ASC
        LIMIT 12
    ");
    $chart_trend = [
        'labels' => [],
        'data' => [],
        'diffs' => [],
        'raw_ym' => []
    ];
    $prev_count = null;
    $indo_months = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];
    while ($r = $stmtTrend->fetch()) {
        $ym = $r['ym'];
        $parts = explode('-', $ym);
        $month_num = $parts[1] ?? '01';
        $year = $parts[0] ?? date('Y');
        $label = ($indo_months[$month_num] ?? $month_num) . ' ' . $year;
        
        $current_count = (int)$r['count'];
        $diff = ($prev_count !== null) ? ($current_count - $prev_count) : 0;
        $prev_count = $current_count;

        $chart_trend['labels'][] = $label;
        $chart_trend['data'][] = $current_count;
        $chart_trend['diffs'][] = $diff;
        $chart_trend['raw_ym'][] = $ym;
    }

    // 5. Top 5 Students with Highest Points
    $stmtTopStd = $pdo->query("
        SELECT s.id, s.nis, s.nama_lengkap, s.total_poin, c.nama_kelas
        FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE s.total_poin > 0
        ORDER BY s.total_poin DESC
        LIMIT 5
    ");
    $top_students = $stmtTopStd->fetchAll();
    foreach ($top_students as &$std) {
        $std['sanction'] = get_sanction_by_points($std['total_poin']);
    }

    // 6. Recent Activities
    $stmtRecent = $pdo->query("
        SELECT vr.id, vr.kode_transaksi, vr.poin, vr.tanggal_kejadian, vr.waktu_kejadian,
               s.nama_lengkap as nama_siswa, c.nama_kelas, vc.nama_pelanggaran, vc.tingkat,
               t.nama_guru
        FROM violation_records vr
        JOIN students s ON vr.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN violation_categories vc ON vr.category_id = vc.id
        JOIN teachers t ON vr.teacher_id = t.id
        ORDER BY vr.id DESC
        LIMIT 7
    ");
    $recent_activities = $stmtRecent->fetchAll();
    foreach ($recent_activities as &$act) {
        $act['tanggal_formatted'] = format_date_id($act['tanggal_kejadian']);
        $act['waktu_formatted'] = substr($act['waktu_kejadian'], 0, 5) . ' WIB';
    }

    json_response([
        'success' => true,
        'metrics' => [
            'total_students' => $total_students,
            'total_teachers' => $total_teachers,
            'total_classes' => $total_classes,
            'total_records' => $total_records,
            'total_points' => $total_points,
            'severity_breakdown' => $severity_breakdown
        ],
        'charts' => [
            'categories' => $chart_categories,
            'trend' => $chart_trend
        ],
        'top_students' => $top_students,
        'recent_activities' => $recent_activities
    ]);

} elseif ($user['role'] === 'teacher') {
    $teacher_id = (int)($user['teacher_id'] ?? 1);

    $my_total_records = (int)$pdo->query("SELECT COUNT(*) FROM violation_records WHERE teacher_id = {$teacher_id}")->fetchColumn();
    $my_month_records = (int)$pdo->query("SELECT COUNT(*) FROM violation_records WHERE teacher_id = {$teacher_id} AND MONTH(tanggal_kejadian) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_kejadian) = YEAR(CURRENT_DATE())")->fetchColumn();
    $total_students_in_danger = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE total_poin >= 30 AND status_siswa = 'aktif'")->fetchColumn();

    $stmtMyRecent = $pdo->prepare("
        SELECT vr.*, s.nama_lengkap as nama_siswa, s.no_telp_wali, c.nama_kelas, vc.nama_pelanggaran, vc.tingkat
        FROM violation_records vr
        JOIN students s ON vr.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN violation_categories vc ON vr.category_id = vc.id
        WHERE vr.teacher_id = ?
        ORDER BY vr.id DESC
        LIMIT 6
    ");
    $stmtMyRecent->execute([$teacher_id]);
    $my_recent = $stmtMyRecent->fetchAll();

    foreach ($my_recent as &$r) {
        $r['tanggal_formatted'] = format_date_id($r['tanggal_kejadian']);
        $r['waktu_formatted'] = substr($r['waktu_kejadian'], 0, 5) . ' WIB';
        // Check if WA notification sent
        $stmtW = $pdo->prepare("SELECT id, status, sent_at FROM notifications WHERE violation_record_id = ? ORDER BY id DESC LIMIT 1");
        $stmtW->execute([$r['id']]);
        $r['notification'] = $stmtW->fetch();
    }

    json_response([
        'success' => true,
        'metrics' => [
            'my_total_records' => $my_total_records,
            'my_month_records' => $my_month_records,
            'students_in_danger' => $total_students_in_danger
        ],
        'recent_inputs' => $my_recent
    ]);

} elseif ($user['role'] === 'parent') {
    $student_id = (int)($user['student_id'] ?? 0);

    $stmtStd = $pdo->prepare("
        SELECT s.*, c.nama_kelas, c.tingkat, c.jurusan, t.nama_guru as nama_wali_kelas
        FROM students s
        JOIN classes c ON s.class_id = c.id
        LEFT JOIN teachers t ON c.wali_kelas_id = t.id
        WHERE s.id = ?
    ");
    $stmtStd->execute([$student_id]);
    $student = $stmtStd->fetch();

    $stmtV = $pdo->prepare("
        SELECT vr.*, vc.nama_pelanggaran, vc.tingkat, t.nama_guru
        FROM violation_records vr
        JOIN violation_categories vc ON vr.category_id = vc.id
        JOIN teachers t ON vr.teacher_id = t.id
        WHERE vr.student_id = ?
        ORDER BY vr.tanggal_kejadian DESC, vr.id DESC
    ");
    $stmtV->execute([$student_id]);
    $records = $stmtV->fetchAll();

    foreach ($records as &$rec) {
        $rec['tanggal_formatted'] = format_date_id($rec['tanggal_kejadian']);
        $rec['waktu_formatted'] = substr($rec['waktu_kejadian'], 0, 5) . ' WIB';
    }

    json_response([
        'success' => true,
        'student' => $student,
        'sanction' => get_sanction_by_points($student['total_poin'] ?? 0),
        'records' => $records
    ]);
}

json_response(['success' => false, 'message' => 'Peran tidak dikenali.'], 400);
