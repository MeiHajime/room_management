<?php
// includes/header.php — Frontend Header
require_once __DIR__ . '/functions.php';
startSession();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $pageDescription ?? 'Hệ thống tìm kiếm và đăng phòng trọ toàn quốc. Tìm phòng nhanh, giá tốt, đáng tin cậy.' ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — VinhRooms' : 'VinhRooms — Tìm Phòng Trọ Giá Rẻ, Uy Tín' ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/my-posts.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-main">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between py-2 w-100">
            <!-- Brand -->
            <a href="<?= BASE_URL ?>/index.php" class="navbar-brand text-decoration-none">
                <div class="logo-icon">🏠</div>
                <div class="brand-text">Vinh<span>Rooms</span></div>
            </a>

            <!-- Desktop nav -->
            <div class="d-none d-lg-flex align-items-center gap-1">
                <a href="<?= BASE_URL ?>/index.php" class="nav-link-main <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-house me-1"></i>Trang chủ
                </a>
                <a href="<?= BASE_URL ?>/search.php" class="nav-link-main <?= $currentPage === 'search.php' ? 'active' : '' ?>">
                    <i class="bi bi-search me-1"></i>Tìm phòng
                </a>
            </div>
            <!-- Auth -->
            <div class="d-flex align-items-center gap-2">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm d-flex align-items-center gap-2 border rounded-pill px-3 py-2 fw-semibold"
                                style="font-size:.875rem" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle text-warning"></i>
                            <?= e($currentUser['ho_ten'] ?? $currentUser['username']) ?>
                            <i class="bi bi-chevron-down" style="font-size:.7rem"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1">
                            <li class="px-3 py-2 border-bottom">
                                <div style="font-size:.85rem;font-weight:700"><?= e($currentUser['ho_ten'] ?? '') ?></div>
                                <div style="font-size:.78rem;color:var(--text-muted)"><?= e($currentUser['email'] ?? '') ?></div>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php">
                                    <i class="bi bi-person me-2 text-warning"></i>Thông tin cá nhân
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= $currentPage === 'index.php' && strpos($_SERVER['PHP_SELF'], 'my-posts') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/my-posts/index.php">
                                    <i class="bi bi-journal-text me-2 text-warning"></i>Quản lý tin đăng
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/my-posts/create.php">
                                    <i class="bi bi-plus-circle me-2 text-success"></i>Đăng tin mới
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                                    <i class="bi bi-speedometer2 me-2 text-warning"></i>Quản trị
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn-nav-login nav-link-main">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                    </a>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn-nav-register nav-link-main">
                        <i class="bi bi-person-plus me-1"></i>Đăng ký
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
