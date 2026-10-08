<?php
/**
 * BOOKLOOP - Admin Common Header & Layout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Ensure current user is an admin
requireAdmin();

$adminUser = getAuthUser($pdo);
$pageTitle = isset($pageTitle) ? $pageTitle . " | BookLoop Admin" : "BookLoop Admin Portal";

// Live counts for badges
$pendingBooksCount = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE approval_status = 'Pending'")->fetchColumn();
$pendingReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Pending'")->fetchColumn();
$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Core Styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/responsive.css">

  <style>
    /* Admin Specific Dashboard Layout */
    .admin-layout {
      display: grid;
      grid-template-columns: 260px 1fr;
      min-height: 100vh;
      background-color: #f1f5f9;
    }
    .admin-sidebar {
      background-color: #0f172a;
      color: #94a3b8;
      display: flex;
      flex-direction: column;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      border-right: 1px solid #1e293b;
    }
    .admin-brand {
      padding: 24px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      color: white;
      text-decoration: none;
      border-bottom: 1px solid #1e293b;
    }
    .admin-nav {
      padding: 20px 0;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .admin-nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 20px;
      color: #94a3b8;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      transition: var(--transition);
    }
    .admin-nav-item:hover, .admin-nav-item.active {
      background-color: #1e293b;
      color: white;
      border-left: 4px solid var(--primary);
    }
    .admin-nav-item .badge {
      margin-left: auto;
    }
    .admin-main {
      padding: 30px 40px;
      overflow-y: auto;
    }
    .admin-topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      background: white;
      padding: 16px 24px;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border-color);
    }
  </style>
</head>
<body>

<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <a href="<?= BASE_URL ?>admin/dashboard.php" class="admin-brand">
      <div class="brand-icon" style="width:36px; height:36px; font-size:16px;">
        <i class="fas fa-shield-alt"></i>
      </div>
      <div>
        <div style="font-weight:800; font-size:16px; color:white; line-height:1;">BookLoop</div>
        <div style="font-size:10px; color:#93c5fd; text-transform:uppercase; letter-spacing:0.5px; margin-top:3px;">Admin Portal</div>
      </div>
    </a>

    <nav class="admin-nav">
      <a href="<?= BASE_URL ?>admin/dashboard.php" class="admin-nav-item <?= $currentAdminPage === 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i> Dashboard
      </a>
      <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-item <?= in_array($currentAdminPage, ['users.php', 'user-details.php']) ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Student Users
      </a>
      <a href="<?= BASE_URL ?>admin/books.php" class="admin-nav-item <?= in_array($currentAdminPage, ['books.php', 'book-details.php']) ? 'active' : '' ?>">
        <i class="fas fa-book"></i> Textbook Listings
        <?php if ($pendingBooksCount > 0): ?>
          <span class="badge badge-pending"><?= $pendingBooksCount ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>admin/requests.php" class="admin-nav-item <?= $currentAdminPage === 'requests.php' ? 'active' : '' ?>">
        <i class="fas fa-exchange-alt"></i> All Requests
      </a>
      <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-item <?= $currentAdminPage === 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-flag"></i> Reports & Flags
        <?php if ($pendingReportsCount > 0): ?>
          <span class="badge badge-sold" style="background:var(--danger); color:white;"><?= $pendingReportsCount ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>admin/categories.php" class="admin-nav-item <?= $currentAdminPage === 'categories.php' ? 'active' : '' ?>">
        <i class="fas fa-tags"></i> Categories
      </a>
      <a href="<?= BASE_URL ?>admin/settings.php" class="admin-nav-item <?= $currentAdminPage === 'settings.php' ? 'active' : '' ?>">
        <i class="fas fa-cog"></i> Settings
      </a>

      <div style="height:1px; background:#1e293b; margin:14px 20px;"></div>

      <a href="<?= BASE_URL ?>index.php" class="admin-nav-item" target="_blank">
        <i class="fas fa-external-link-alt"></i> View Marketplace
      </a>
      <a href="<?= BASE_URL ?>logout.php" class="admin-nav-item" style="color:#ef4444;">
        <i class="fas fa-sign-out-alt"></i> Sign Out
      </a>
    </nav>
  </aside>

  <!-- Admin Main Workspace -->
  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <span style="font-size:13px; color:var(--text-muted);">Signed in as Administrator:</span>
        <strong style="font-size:14px; color:var(--text-main); margin-left:4px;"><?= e($adminUser['name']) ?></strong>
      </div>
      <div>
        <a href="<?= BASE_URL ?>index.php" class="btn btn-sm btn-outline"><i class="fas fa-home"></i> Home</a>
      </div>
    </div>

    <?php displayFlash(); ?>
