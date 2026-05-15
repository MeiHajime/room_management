<?php
// includes/admin_header.php
require_once __DIR__ . '/functions.php';
requireAdmin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — Admin' : 'Admin Panel — VinhRooms' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">🏠</div>
        <div class="brand">VinhRooms<small>Quản trị viên</small></div>
    </div>

    <div class="sidebar-section">
        <span class="sidebar-section-label">Tổng quan</span>
        <a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-link <?= ($currentDir === 'admin' && $currentPage === 'index.php') ? 'active' : '' ?>">
            <span class="icon"><i class="bi bi-speedometer2"></i></span> Dashboard
        </a>
    </div>

    <div class="sidebar-section">
        <span class="sidebar-section-label">Quản lý</span>
        <a href="<?= BASE_URL ?>/admin/rooms/index.php" class="sidebar-link <?= ($currentDir === 'rooms') ? 'active' : '' ?>">
            <span class="icon"><i class="bi bi-house-door"></i></span> Phòng trọ
        </a>
        <a href="<?= BASE_URL ?>/admin/users/index.php" class="sidebar-link <?= ($currentDir === 'users') ? 'active' : '' ?>">
            <span class="icon"><i class="bi bi-people"></i></span> Người dùng
        </a>
    </div>

    <div class="sidebar-section">
        <span class="sidebar-section-label">Thống kê</span>
        <a href="<?= BASE_URL ?>/admin/reports/index.php" class="sidebar-link <?= ($currentDir === 'reports') ? 'active' : '' ?>">
            <span class="icon"><i class="bi bi-bar-chart-line"></i></span> Báo cáo
        </a>
    </div>

    <div class="sidebar-section" style="margin-top:auto;border-top:1px solid rgba(255,255,255,.08);padding-top:1rem;">
        <a href="<?= BASE_URL ?>/index.php" class="sidebar-link" target="_blank">
            <span class="icon"><i class="bi bi-globe2"></i></span> Xem trang web
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link" style="color:rgba(239,68,68,.8)">
            <span class="icon"><i class="bi bi-box-arrow-right"></i></span> Đăng xuất
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="admin-main">
    <!-- TOPBAR -->
    <div class="admin-topbar">
        <div class="topbar-left">
            <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></span>
        </div>
        <div class="topbar-right">
            <span style="font-size:.82rem;color:var(--admin-muted)">
                <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i') ?>
            </span>
            <div class="dropdown">
                <button class="admin-user-btn" data-bs-toggle="dropdown">
                    <div class="admin-user-avatar">
                        <?= strtoupper(mb_substr($currentUser['ho_ten'] ?? 'A', 0, 1)) ?>
                    </div>
                    <?= e($currentUser['ho_ten'] ?? 'Admin') ?>
                    <i class="bi bi-chevron-down" style="font-size:.7rem"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1">
                    <li class="px-3 py-2 border-bottom">
                        <div style="font-size:.85rem;font-weight:700"><?= e($currentUser['ho_ten']) ?></div>
                        <div style="font-size:.78rem;color:var(--admin-muted)"><?= e($currentUser['email']) ?></div>
                    </li>
                    <li><a class="dropdown-item text-danger mt-1" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="admin-content">
        <?php renderFlash(); ?>
