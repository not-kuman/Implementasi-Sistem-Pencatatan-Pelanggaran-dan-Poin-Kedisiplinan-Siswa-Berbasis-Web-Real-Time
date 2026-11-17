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

// Server-side pre-aggregation for Top Categories
$stmtCat = $pdo->query("
    SELECT vc.id, vc.nama_pelanggaran, vc.tingkat, vc.poin, COUNT(vr.id) as total_kasus
    FROM violation_records vr
    JOIN violation_categories vc ON vr.category_id = vc.id
    GROUP BY vc.id, vc.nama_pelanggaran, vc.tingkat, vc.poin
    ORDER BY total_kasus DESC
    LIMIT 6
");
$top_categories = $stmtCat->fetchAll();

// Server-side pre-aggregation for Monthly Trend
$stmtTrend = $pdo->query("
    SELECT DATE_FORMAT(tanggal_kejadian, '%Y-%m') as ym, COUNT(id) as count
    FROM violation_records
    GROUP BY DATE_FORMAT(tanggal_kejadian, '%Y-%m')
    ORDER BY ym ASC
    LIMIT 12
");
$trend_rows = $stmtTrend->fetchAll();
$indo_months = [
    '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
    '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
    '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
];
$trend_labels = [];
$trend_counts = [];
$prev_c = null;
$latest_diff = 0;
foreach ($trend_rows as $tr) {
    $parts = explode('-', $tr['ym']);
    $m = $parts[1] ?? '01';
    $y = $parts[0] ?? date('Y');
    $trend_labels[] = ($indo_months[$m] ?? $m) . ' ' . $y;
    $c = (int)$tr['count'];
    $trend_counts[] = $c;
    if ($prev_c !== null) {
        $latest_diff = $c - $prev_c;
    }
    $prev_c = $c;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Ringkasan Sistem Kedisiplinan</h1>
    <p class="page-subtitle"><?= e(SCHOOL_NAME) ?> &mdash; Pemantauan tata tertib dan rekam jejak kedisiplinan siswa</p>
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

<!-- Administrative Summary Bar (Cohesive Editorial Design) -->
<div class="admin-summary-bar">
  <div class="summary-segment">
    <span class="summary-label">Siswa Aktif</span>
    <div class="summary-value" id="stat-students"><?= number_format($total_students) ?></div>
    <div class="summary-subtext">Peserta didik terdaftar</div>
  </div>
  <div class="summary-segment">
    <span class="summary-label">Tenaga Pendidik / BK</span>
    <div class="summary-value" id="stat-teachers"><?= number_format($total_teachers) ?></div>
    <div class="summary-subtext">Guru &amp; tim penegak disiplin</div>
  </div>
  <div class="summary-segment">
    <span class="summary-label">Rombongan Belajar</span>
    <div class="summary-value" id="stat-classes"><?= number_format($total_classes) ?></div>
    <div class="summary-subtext">Kelas X, XI, dan XII</div>
  </div>
  <div class="summary-segment">
    <span class="summary-label">Insiden Pelanggaran</span>
    <div class="summary-value" id="stat-records"><?= number_format($total_records) ?></div>
    <div class="summary-subtext">Catatan rekam kedisiplinan</div>
  </div>
  <div class="summary-segment">
    <span class="summary-label">Akumulasi Poin Denda</span>
    <div class="summary-value" id="stat-points"><?= number_format($total_points) ?></div>
    <div class="summary-subtext">Rata-rata: <?= $total_records > 0 ? round($total_points / $total_records, 1) : 0 ?> poin/insiden</div>
  </div>
</div>

<!-- Two Charts Section (Horizontal Bar Chart & Line Chart) -->
<div class="dashboard-charts-grid">
  <!-- Horizontal Bar Chart: Kategori Pelanggaran Terbanyak -->
  <div class="surface-card" style="margin-bottom: 0;">
    <div class="card-header-styled">
      <div>
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Kategori Pelanggaran Terbanyak
        </h2>
        <span class="card-subtitle-styled">Distribusi insiden berdasarkan frekuensi jenis pelanggaran</span>
      </div>
    </div>
    <div class="card-body-styled">
      <?php if (empty($top_categories)): ?>
        <div class="empty-state-box">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <p>Belum ada data pelanggaran pada periode ini.</p>
        </div>
      <?php else: ?>
        <div style="height: 220px; position: relative;">
          <canvas id="chartCategories"></canvas>
        </div>
        <div class="category-breakdown-list" id="category-breakdown-container">
          <?php foreach ($top_categories as $cat): 
            $pct = $total_records > 0 ? round(($cat['total_kasus'] / $total_records) * 100, 1) : 0;
            $sev_class = 'badge-' . strtolower($cat['tingkat']);
          ?>
            <div class="cat-summary-row">
              <div class="cat-summary-info">
                <span class="badge <?= $sev_class ?>"><?= e($cat['tingkat']) ?></span>
                <span class="cat-name" title="<?= e($cat['nama_pelanggaran']) ?>"><?= e($cat['nama_pelanggaran']) ?></span>
              </div>
              <div class="cat-summary-metrics">
                <span class="cat-points"><?= (int)$cat['poin'] ?> Poin</span>
                <span class="cat-count"><strong><?= (int)$cat['total_kasus'] ?></strong> kasus</span>
                <span class="cat-pct">(<?= $pct ?>%)</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Line Chart: Tren Pelanggaran Bulanan -->
  <div class="surface-card" style="margin-bottom: 0;">
    <div class="card-header-styled">
      <div>
        <h2 class="card-title-styled">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Tren Pelanggaran Bulanan
        </h2>
        <span class="card-subtitle-styled">Fluktuasi kejadian pelanggaran sepanjang waktu</span>
      </div>
      <?php if (!empty($trend_rows) && count($trend_rows) >= 2): ?>
        <span class="trend-mom-pill <?= $latest_diff > 0 ? 'up' : ($latest_diff < 0 ? 'down' : 'neutral') ?>">
          <?= $latest_diff > 0 ? '▲ +' . $latest_diff . ' vs bln lalu' : ($latest_diff < 0 ? '▼ ' . $latest_diff . ' vs bln lalu' : '— Tetap vs bln lalu') ?>
        </span>
      <?php endif; ?>
    </div>
    <div class="card-body-styled">
      <?php if (empty($trend_rows)): ?>
        <div class="empty-state-box">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
          </svg>
          <p>Belum ada data tren kejadian pada periode ini.</p>
        </div>
      <?php else: ?>
        <div style="height: 290px; position: relative;">
          <canvas id="chartTrend"></canvas>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Lower Section: Top 5 Highest Points & Recent Activities -->
<div class="dashboard-bottom-grid">
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
      <div class="table-responsive">
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
              <th>Tanggal Kejadian</th>
              <th>Siswa &amp; Kelas</th>
              <th>Bentuk Pelanggaran</th>
              <th style="text-align: center;">Poin</th>
              <th>Guru Pencatat</th>
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
                    <div style="font-size: 12.5px; font-weight: 600; color: var(--color-primary);"><?= format_date_id($rec['tanggal_kejadian']) ?></div>
                    <div style="font-size: 11px; color: var(--color-text-muted);"><?= substr($rec['waktu_kejadian'] ?? '', 0, 5) ?> WIB</div>
                  </td>
                  <td>
                    <div style="font-weight: 600; color: var(--color-primary);"><?= e($rec['nama_siswa']) ?></div>
                    <div style="font-size: 11.5px; color: var(--color-text-muted);"><?= e($rec['nama_kelas']) ?></div>
                  </td>
                  <td>
                    <span class="badge <?= $sev_class ?>"><?= e($rec['tingkat']) ?></span>
                    <div style="font-size: 12.5px; margin-top: 3px; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($rec['nama_pelanggaran']) ?>">
                      <?= e($rec['nama_pelanggaran']) ?>
                    </div>
                  </td>
                  <td style="text-align: center;">
                    <span class="badge-points danger">+<?= (int)$rec['poin'] ?></span>
                  </td>
                  <td>
                    <div style="font-size: 12px; font-weight: 500; color: var(--color-text-main);"><?= e($rec['nama_guru']) ?></div>
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
$initial_cat_json = json_encode([
    'labels' => array_column($top_categories, 'nama_pelanggaran'),
    'data' => array_map('intval', array_column($top_categories, 'total_kasus')),
    'percentages' => array_map(function($c) use ($total_records) {
        return $total_records > 0 ? round(($c['total_kasus'] / $total_records) * 100, 1) : 0;
    }, $top_categories)
]);
$initial_trend_json = json_encode([
    'labels' => $trend_labels,
    'data' => $trend_counts
]);

$extra_js = '
<script src="' . BASE_URL . '/assets/vendor/chart.min.js"></script>
<script>
let catChartInstance = null;
let trendChartInstance = null;

const initialCatData = ' . $initial_cat_json . ';
const initialTrendData = ' . $initial_trend_json . ';

function renderCategoriesChart(catData) {
  const canvas = document.getElementById("chartCategories");
  if (!canvas || !catData || !catData.labels || catData.labels.length === 0) return;
  const ctxCat = canvas.getContext("2d");
  if (catChartInstance) catChartInstance.destroy();

  catChartInstance = new Chart(ctxCat, {
    type: "bar",
    data: {
      labels: catData.labels.map(l => l.length > 28 ? l.substring(0, 25) + "..." : l),
      datasets: [{
        label: "Jumlah Kasus",
        data: catData.data,
        backgroundColor: "#397C78",
        hoverBackgroundColor: "#2C6360",
        borderRadius: 3,
        barThickness: 16
      }]
    },
    options: {
      indexAxis: "y",
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: "#172B3A",
          titleFont: { family: "Plus Jakarta Sans", size: 12 },
          bodyFont: { family: "Plus Jakarta Sans", size: 12 },
          padding: 10,
          callbacks: {
            label: function(context) {
              const val = context.raw || 0;
              const pct = (catData.percentages && catData.percentages[context.dataIndex]) ? catData.percentages[context.dataIndex] : 0;
              return " " + val + " kasus (" + pct + "% dari total)";
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { precision: 0, font: { family: "Plus Jakarta Sans", size: 10 }, color: "#64748B" },
          grid: { color: "#F1F5F9" }
        },
        y: {
          ticks: { font: { family: "Plus Jakarta Sans", size: 10.5 }, color: "#172B3A" },
          grid: { display: false }
        }
      }
    }
  });
}

function renderTrendChart(trendData) {
  const canvas = document.getElementById("chartTrend");
  if (!canvas || !trendData || !trendData.labels || trendData.labels.length === 0) return;
  const ctxTrend = canvas.getContext("2d");
  if (trendChartInstance) trendChartInstance.destroy();

  trendChartInstance = new Chart(ctxTrend, {
    type: "line",
    data: {
      labels: trendData.labels,
      datasets: [{
        label: "Insiden Pelanggaran",
        data: trendData.data,
        borderColor: "#172B3A",
        backgroundColor: "rgba(23, 43, 58, 0.04)",
        borderWidth: 2,
        pointBackgroundColor: "#D6A34D",
        pointBorderColor: "#FFFFFF",
        pointBorderWidth: 1.5,
        pointRadius: 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.25
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: "#172B3A",
          padding: 10,
          callbacks: {
            label: function(context) {
              return " " + context.raw + " insiden kejadian";
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0, font: { family: "Plus Jakarta Sans", size: 10 }, color: "#64748B" },
          grid: { color: "#F1F5F9", drawBorder: false }
        },
        x: {
          ticks: { font: { family: "Plus Jakarta Sans", size: 10.5 }, color: "#172B3A" },
          grid: { display: false }
        }
      }
    }
  });
}

async function loadDashboardData() {
  try {
    const res = await apiRequest("' . BASE_URL . '/api/dashboard.php");
    if (!res.success) return;

    // Update Metrics
    if (res.metrics) {
      document.getElementById("stat-students").textContent = Number(res.metrics.total_students).toLocaleString();
      document.getElementById("stat-teachers").textContent = Number(res.metrics.total_teachers).toLocaleString();
      document.getElementById("stat-classes").textContent = Number(res.metrics.total_classes).toLocaleString();
      document.getElementById("stat-records").textContent = Number(res.metrics.total_records).toLocaleString();
      document.getElementById("stat-points").textContent = Number(res.metrics.total_points).toLocaleString();
    }

    // Render Charts
    if (res.charts) {
      if (res.charts.categories) renderCategoriesChart(res.charts.categories);
      if (res.charts.trend) renderTrendChart(res.charts.trend);
    }
  } catch (err) {
    console.error("Dashboard refresh error:", err);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  // Instant render from server-side data
  renderCategoriesChart(initialCatData);
  renderTrendChart(initialTrendData);

  // Background refresh
  loadDashboardData();

  const refreshBtn = document.getElementById("btn-refresh-stats");
  if (refreshBtn) {
    refreshBtn.addEventListener("click", () => {
      loadDashboardData();
      Toast.info("Pembaruan", "Data statistik berhasil disinkronkan.");
    });
  }

  // Polling every 30 seconds
  setInterval(loadDashboardData, 30000);
});
</script>
';
require_once __DIR__ . '/../includes/footer.php';
?>
