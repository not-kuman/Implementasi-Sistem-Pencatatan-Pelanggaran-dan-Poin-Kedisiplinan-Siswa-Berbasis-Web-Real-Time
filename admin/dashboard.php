<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Admin Dashboard View
 */

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$page_title = 'Dashboard Administrator';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '#']
];

$pdo = get_db_connection();

// Initial server-side query for instant render
$total_students = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status_siswa = 'aktif'")->fetchColumn();
$total_teachers = (int)$pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$total_classes  = (int)$pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$total_records  = (int)$pdo->query("SELECT COUNT(*) FROM violation_records")->fetchColumn();
$total_points   = (int)$pdo->query("SELECT COALESCE(SUM(total_poin), 0) FROM students WHERE status_siswa = 'aktif'")->fetchColumn();

// Top 5 students with highest points
$stmtTop = $pdo->query("
    SELECT s.id, s.nis, s.nama_lengkap, s.total_poin, c.nama_kelas
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.total_poin > 0
    ORDER BY s.total_poin DESC
    LIMIT 5
");
$top_students = $stmtTop->fetchAll();

// Recent 6 violations
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
    LIMIT 6
");
$recent_activities = $stmtRecent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Pusat Komando Kedisiplinan</h1>
    <p class="page-subtitle">Ringkasan analitis rekam jejak tata tertib siswa <?= e(SCHOOL_NAME) ?></p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-secondary" id="btn-refresh-stats">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
      </svg>
      <span>Segarkan Data</span>
    </button>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-primary">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
      </svg>
      <span>Unduh Rekap</span>
    </a>
  </div>
</div>

<!-- 5 Stat Metrics Cards -->
<div class="grid-metrics">
  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Total Siswa Aktif</span>
      <div class="metric-icon-wrap primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      </div>
    </div>
    <div class="metric-value" id="stat-students"><?= number_format($total_students) ?></div>
    <div class="metric-trend">Peserta didik terdaftar aktif</div>
  </div>

  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Tenaga Pendidik / BK</span>
      <div class="metric-icon-wrap accent">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
    </div>
    <div class="metric-value" id="stat-teachers"><?= number_format($total_teachers) ?></div>
    <div class="metric-trend">Guru & tim penegak disiplin</div>
  </div>

  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Rombongan Belajar</span>
      <div class="metric-icon-wrap warning">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      </div>
    </div>
    <div class="metric-value" id="stat-classes"><?= number_format($total_classes) ?></div>
    <div class="metric-trend">Tingkat X, XI, dan XII</div>
  </div>

  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Insiden Pelanggaran</span>
      <div class="metric-icon-wrap danger">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="9" x2="12" y2="13"/><polygon points="12 2 2 22 22 22 12 2"/></svg>
      </div>
    </div>
    <div class="metric-value" id="stat-records"><?= number_format($total_records) ?></div>
    <div class="metric-trend">Total transaksi kejadian</div>
  </div>

  <div class="metric-card">
    <div class="metric-header">
      <span class="metric-label">Akumulasi Poin Denda</span>
      <div class="metric-icon-wrap primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
    </div>
    <div class="metric-value" id="stat-points" style="color: var(--color-danger);"><?= number_format($total_points) ?></div>
    <div class="metric-trend">Poin kedisiplinan kumulatif</div>
  </div>
</div>

<!-- Two Charts Section (Bar Chart & Line Chart) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 20px; margin-bottom: 24px;">
  <!-- Bar Chart: Jenis Pelanggaran Terbanyak -->
  <div class="surface-card" style="margin-bottom: 0;">
    <div class="card-header-styled">
      <div>
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Kategori Pelanggaran Terbanyak
        </h2>
        <span class="card-subtitle-styled">Distribusi insiden berdasarkan jenis pelanggaran</span>
      </div>
    </div>
    <div class="card-body-styled" style="height: 300px; position: relative;">
      <canvas id="chartCategories"></canvas>
    </div>
  </div>

  <!-- Line Chart: Tren Pelanggaran Per Waktu -->
  <div class="surface-card" style="margin-bottom: 0;">
    <div class="card-header-styled">
      <div>
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Tren Pelanggaran Bulanan
        </h2>
        <span class="card-subtitle-styled">Fluktuasi kejadian pelanggaran sepanjang waktu</span>
      </div>
    </div>
    <div class="card-body-styled" style="height: 300px; position: relative;">
      <canvas id="chartTrend"></canvas>
    </div>
  </div>
</div>

<!-- Lower Section: Top 5 Highest Points & Recent Activities -->
<div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 20px;">
  <!-- Top 5 Highest Points Students -->
  <div class="surface-card">
    <div class="card-header-styled">
      <div>
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          Siswa Poin Tertinggi
        </h2>
        <span class="card-subtitle-styled">Perlu perhatian & bimbingan khusus</span>
      </div>
      <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
    </div>
    <div class="card-body-styled" style="padding: 0;">
      <table class="app-table">
        <thead>
          <tr>
            <th>Siswa / Kelas</th>
            <th style="text-align: right;">Total Poin</th>
            <th>Sanksi Aktif</th>
          </tr>
        </thead>
        <tbody id="top-students-body">
          <?php if (empty($top_students)): ?>
            <tr>
              <td colspan="3" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                Belum ada data pelanggaran siswa.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($top_students as $std): 
              $s = get_sanction_by_points($std['total_poin']);
              $badge_class = ($std['total_poin'] >= 50) ? 'badge-points danger' : (($std['total_poin'] >= 20) ? 'badge-points warning' : 'badge-points');
            ?>
              <tr>
                <td>
                  <div style="font-weight: 600; color: var(--color-primary);"><?= e($std['nama_lengkap']) ?></div>
                  <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($std['nis']) ?> &bull; <?= e($std['nama_kelas']) ?></div>
                </td>
                <td style="text-align: right;">
                  <span class="<?= $badge_class ?>"><?= (int)$std['total_poin'] ?> Poin</span>
                </td>
                <td>
                  <span style="font-size: 11.5px; font-weight: 600; color: #475569;">
                    <?= e($s['nama_sanksi']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Activities Table -->
  <div class="surface-card">
    <div class="card-header-styled">
      <div>
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Aktivitas Pencatatan Terkini
        </h2>
        <span class="card-subtitle-styled">Pencatatan pelanggaran paling baru oleh guru</span>
      </div>
      <a href="<?= BASE_URL ?>/admin/violations.php" class="btn btn-secondary btn-sm">Buka Riwayat</a>
    </div>
    <div class="card-body-styled" style="padding: 0;">
      <div class="table-responsive">
        <table class="app-table">
          <thead>
            <tr>
              <th>Kode / Waktu</th>
              <th>Siswa</th>
              <th>Pelanggaran</th>
              <th style="text-align: center;">Poin</th>
              <th>Guru Pelapor</th>
            </tr>
          </thead>
          <tbody id="recent-activities-body">
            <?php if (empty($recent_activities)): ?>
              <tr>
                <td colspan="5" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                  Belum ada aktivitas pencatatan terbaru.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($recent_activities as $rec): 
                $sev_class = ($rec['tingkat'] === 'Berat') ? 'badge-berat' : (($rec['tingkat'] === 'Sedang') ? 'badge-sedang' : 'badge-ringan');
              ?>
                <tr>
                  <td>
                    <div style="font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--color-accent);"><?= e($rec['kode_transaksi']) ?></div>
                    <div style="font-size: 11px; color: var(--color-text-muted);"><?= format_date_id($rec['tanggal_kejadian']) ?></div>
                  </td>
                  <td>
                    <div style="font-weight: 600;"><?= e($rec['nama_siswa']) ?></div>
                    <div style="font-size: 11px; color: var(--color-text-muted);"><?= e($rec['nama_kelas']) ?></div>
                  </td>
                  <td>
                    <span class="badge <?= $sev_class ?>"><?= e($rec['tingkat']) ?></span>
                    <div style="font-size: 12px; margin-top: 3px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($rec['nama_pelanggaran']) ?>">
                      <?= e($rec['nama_pelanggaran']) ?>
                    </div>
                  </td>
                  <td style="text-align: center;">
                    <span class="badge-points danger">+<?= (int)$rec['poin'] ?></span>
                  </td>
                  <td>
                    <div style="font-size: 12px; font-weight: 500;"><?= e($rec['nama_guru']) ?></div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php 
$extra_js = '
<script src="' . BASE_URL . '/assets/vendor/chart.min.js"></script>
<script>
let catChartInstance = null;
let trendChartInstance = null;

async function loadDashboardData() {
  try {
    const res = await apiRequest("' . BASE_URL . '/api/dashboard.php");
    if (!res.success) return;

    // Update Metrics
    document.getElementById("stat-students").textContent = Number(res.metrics.total_students).toLocaleString();
    document.getElementById("stat-teachers").textContent = Number(res.metrics.total_teachers).toLocaleString();
    document.getElementById("stat-classes").textContent = Number(res.metrics.total_classes).toLocaleString();
    document.getElementById("stat-records").textContent = Number(res.metrics.total_records).toLocaleString();
    document.getElementById("stat-points").textContent = Number(res.metrics.total_points).toLocaleString();

    // Render Bar Chart
    const ctxCat = document.getElementById("chartCategories").getContext("2d");
    if (catChartInstance) catChartInstance.destroy();
    catChartInstance = new Chart(ctxCat, {
      type: "bar",
      data: {
        labels: res.charts.categories.labels.map(l => l.length > 25 ? l.substring(0, 22) + "..." : l),
        datasets: [{
          label: "Jumlah Kasus",
          data: res.charts.categories.data,
          backgroundColor: "#397C78",
          borderRadius: 4,
          maxBarThickness: 36
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0, font: { family: "Plus Jakarta Sans" } },
            grid: { color: "#F1F5F9" }
          },
          x: {
            ticks: { font: { family: "Plus Jakarta Sans", size: 10 } },
            grid: { display: false }
          }
        }
      }
    });

    // Render Line Chart
    const ctxTrend = document.getElementById("chartTrend").getContext("2d");
    if (trendChartInstance) trendChartInstance.destroy();
    trendChartInstance = new Chart(ctxTrend, {
      type: "line",
      data: {
        labels: res.charts.trend.labels,
        datasets: [{
          label: "Tren Insiden Pelanggaran",
          data: res.charts.trend.data,
          borderColor: "#172B3A",
          backgroundColor: "rgba(23, 43, 58, 0.05)",
          borderWidth: 2.2,
          fill: true,
          tension: 0.3,
          pointBackgroundColor: "#D6A34D",
          pointRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0, font: { family: "Plus Jakarta Sans" } },
            grid: { color: "#F1F5F9" }
          },
          x: {
            ticks: { font: { family: "Plus Jakarta Sans" } },
            grid: { display: false }
          }
        }
      }
    });

  } catch (err) {
    console.error("Dashboard refresh error:", err);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loadDashboardData();
  document.getElementById("btn-refresh-stats").addEventListener("click", () => {
    loadDashboardData();
    Toast.info("Pembaruan", "Data statistik berhasil disinkronkan.");
  });

  // Auto-sync polling every 30 seconds
  setInterval(loadDashboardData, 30000);
});
</script>
';
require_once __DIR__ . '/../includes/footer.php';
?>
