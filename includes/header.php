<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Top Layout Header
 */

require_once __DIR__ . '/../config/app.php';
require_login();

$user = current_user();
$page_title = $page_title ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [['title' => 'Dashboard', 'url' => '#']];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($page_title) ?> — <?= e(APP_NAME) ?></title>
  
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  
  <?php if (!empty($extra_css)): ?>
    <?= $extra_css ?>
  <?php endif; ?>
</head>
<body>
<div class="app-wrapper">
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <div class="app-main">
    <header class="app-topbar">
      <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" aria-label="Buka Menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/><line x1="6" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>

        <nav class="breadcrumb-area" aria-label="Breadcrumb">
          <a href="<?= BASE_URL ?>" class="breadcrumb-item"><?= e(APP_NAME) ?></a>
          <?php foreach ($breadcrumbs as $b): ?>
            <span class="breadcrumb-separator">/</span>
            <?php if (!empty($b['url']) && $b['url'] !== '#'): ?>
              <a href="<?= $b['url'] ?>" class="breadcrumb-item"><?= e($b['title']) ?></a>
            <?php else: ?>
              <span class="breadcrumb-current"><?= e($b['title']) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
      </div>

      <div class="topbar-right">
        <div class="system-status-indicator" title="Koneksi basis data MySQL & sistem real-time aktif">
          <span class="status-dot-pulse"></span>
          <span>Sistem Online</span>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
          <span class="topbar-badge-role badge-role-admin">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Administrator
          </span>
        <?php elseif ($user['role'] === 'teacher'): ?>
          <span class="topbar-badge-role badge-role-teacher">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            Guru / BK
          </span>
        <?php elseif ($user['role'] === 'parent'): ?>
          <span class="topbar-badge-role badge-role-parent">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Wali Siswa
          </span>
        <?php endif; ?>
      </div>
    </header>

    <main class="page-content">
