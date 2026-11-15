<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Dynamic Role-Based Sidebar Navigation
 */

$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="app-sidebar" id="app-sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Logo Sistem Informasi Kedisiplinan" style="width: 32px; height: 32px;">
    </div>
    <div class="sidebar-brand-text">
      <span class="sidebar-brand-title"><?= e(APP_NAME) ?></span>
      <span class="sidebar-brand-sub"><?= e(APP_SUBTITLE) ?></span>
    </div>
  </div>

  <div class="sidebar-content">
    <?php if ($user['role'] === 'admin'): ?>
      <!-- ADMIN NAVIGATION -->
      <div class="nav-section-title">Ringkasan</div>
      <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            <span>Dashboard</span>
          </a>
        </li>
      </ul>

      <div class="nav-section-title">Data Master</div>
      <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/students.php" class="sidebar-nav-link <?= $current_page === 'students.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Data Siswa</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/teachers.php" class="sidebar-nav-link <?= $current_page === 'teachers.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <span>Data Guru & BK</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/classes.php" class="sidebar-nav-link <?= $current_page === 'classes.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Data Kelas</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/parents.php" class="sidebar-nav-link <?= $current_page === 'parents.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <span>Data Wali Siswa</span>
          </a>
        </li>
      </ul>

      <div class="nav-section-title">Kedisiplinan</div>
      <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/categories.php" class="sidebar-nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <span>Kategori & Bobot Poin</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/sanctions.php" class="sidebar-nav-link <?= $current_page === 'sanctions.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Ketentuan Sanksi</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/violations.php" class="sidebar-nav-link <?= $current_page === 'violations.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span>Riwayat Pelanggaran</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/reports.php" class="sidebar-nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
            <span>Rekap & Laporan</span>
          </a>
        </li>
      </ul>

      <div class="nav-section-title">Pengaturan</div>
      <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Manajemen Akun</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-nav-link <?= $current_page === 'settings.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>Pengaturan & WA API</span>
          </a>
        </li>
      </ul>

    <?php elseif ($user['role'] === 'teacher'): ?>
      <!-- TEACHER NAVIGATION -->
      <div class="nav-section-title">Ringkasan</div>
      <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="sidebar-nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            <span>Dashboard Guru</span>
          </a>
        </li>
      </ul>

      <div class="nav-section-title">Tindakan Lapangan</div>
      <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/teacher/add_violation.php" class="sidebar-nav-link <?= $current_page === 'add_violation.php' ? 'active' : '' ?>" style="background-color: var(--color-accent); color: #FFFFFF; font-weight: 600;">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="color: #FFFFFF; opacity: 1;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            <span>+ Catat Pelanggaran</span>
          </a>
        </li>
        <li class="sidebar-nav-item" style="margin-top: 4px;">
          <a href="<?= BASE_URL ?>/teacher/history.php" class="sidebar-nav-link <?= $current_page === 'history.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Riwayat Input Saya</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/teacher/students.php" class="sidebar-nav-link <?= $current_page === 'students.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Direktori Siswa</span>
          </a>
        </li>
      </ul>

    <?php elseif ($user['role'] === 'parent'): ?>
      <!-- PARENT NAVIGATION -->
      <div class="nav-section-title">Menu Utama</div>
      <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/parent/dashboard.php" class="sidebar-nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            <span>Dashboard Wali</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/parent/profile.php" class="sidebar-nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Profil Siswa</span>
          </a>
        </li>
        <li class="sidebar-nav-item">
          <a href="<?= BASE_URL ?>/parent/history.php" class="sidebar-nav-link <?= $current_page === 'history.php' ? 'active' : '' ?>">
            <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Riwayat Pelanggaran</span>
          </a>
        </li>
      </ul>
    <?php endif; ?>
  </div>

  <div class="sidebar-footer">
    <div class="user-mini-profile">
      <div class="user-avatar-initials">
        <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
      </div>
      <div class="user-info">
        <span class="user-name" title="<?= e($user['full_name']) ?>"><?= e($user['full_name']) ?></span>
        <span class="user-role"><?= e($user['role']) ?></span>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="btn-logout" title="Keluar dari Sistem">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
    </a>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
