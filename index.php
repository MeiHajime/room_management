<?php
// index.php — Trang chủ - Danh sách phòng trọ
$pageTitle       = 'Trang Chủ — Tìm Phòng Trọ Toàn Quốc';
$pageDescription = 'Tìm phòng trọ, căn hộ mini, nhà nguyên căn giá tốt khắp Việt Nam. Hàng ngàn tin đăng uy tín mỗi ngày.';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

// Lấy thống kê
$totalRooms = $db->query("SELECT COUNT(*) FROM phong_tro p JOIN khu_vuc k ON p.khu_vuc_id = k.id WHERE p.trang_thai='da_duyet'")->fetch_row()[0];
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$totalCities = $db->query("SELECT COUNT(DISTINCT tinh_thanh) FROM phong_tro p JOIN khu_vuc k ON p.khu_vuc_id = k.id WHERE p.trang_thai='da_duyet'")->fetch_row()[0];

// Tìm kiếm
$search      = trim($_GET['q'] ?? '');
$tinh_thanh  = trim($_GET['tinh_thanh'] ?? '');
$loai_id     = (int)($_GET['loai'] ?? 0);
$gia_min     = (int)($_GET['gia_min'] ?? 0);
$gia_max     = (int)($_GET['gia_max'] ?? 0);
$page_num    = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 8;

// Build query
$where   = ["p.trang_thai = 'da_duyet'"];
$params  = [];
$types   = '';

if ($search !== '') {
    $where[]  = "(p.tieu_de LIKE ? OR p.dia_chi LIKE ? OR p.mo_ta LIKE ?)";
    $kw = "%$search%";
    $params  = array_merge($params, [$kw, $kw, $kw]);
    $types  .= 'sss';
}
if ($tinh_thanh !== '') {
    $where[]  = "k.tinh_thanh = ?";
    $params[] = $tinh_thanh;
    $types   .= 's';
}
if ($loai_id > 0) {
    $where[]  = "p.loai_phong_id = ?";
    $params[] = $loai_id;
    $types   .= 'i';
}
if ($gia_min > 0) {
    $where[]  = "p.gia >= ?";
    $params[] = $gia_min;
    $types   .= 'i';
}
if ($gia_max > 0) {
    $where[]  = "p.gia <= ?";
    $params[] = $gia_max;
    $types   .= 'i';
}

$whereSQL = implode(' AND ', $where);

// Count
$countSQL = "SELECT COUNT(*) FROM phong_tro p JOIN khu_vuc k ON p.khu_vuc_id = k.id WHERE $whereSQL";
if (!empty($params)) {
    $stmt = $db->prepare($countSQL);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
} else {
    $total = $db->query($countSQL)->fetch_row()[0];
}

$pg = paginate($total, $per_page, $page_num);

// Fetch rooms
$offset = $pg['offset'];
$roomSQL = "SELECT p.*, k.tinh_thanh, k.phuong_xa, u.ho_ten as chu_tro, l.ten as loai_ten
            FROM phong_tro p
            JOIN khu_vuc k ON p.khu_vuc_id = k.id
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN loai_phong l ON p.loai_phong_id = l.id
            WHERE $whereSQL
            ORDER BY p.created_at DESC
            LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $stmt = $db->prepare($roomSQL);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rooms = $db->query($roomSQL)->fetch_all(MYSQLI_ASSOC);
}

// Loại phòng & tỉnh thành
$categories = $db->query("SELECT * FROM loai_phong ORDER BY ten")->fetch_all(MYSQLI_ASSOC);
$cities     = $db->query("SELECT DISTINCT tinh_thanh FROM phong_tro p JOIN khu_vuc k ON p.khu_vuc_id = k.id WHERE p.trang_thai='da_duyet' ORDER BY tinh_thanh")->fetch_all(MYSQLI_ASSOC);

$isSearch = $search || $tinh_thanh || $loai_id || $gia_min || $gia_max;

// Build base URL for pagination
$baseParams = array_filter(['q' => $search, 'tinh_thanh' => $tinh_thanh, 'loai' => $loai_id ?: '', 'gia_min' => $gia_min ?: '', 'gia_max' => $gia_max ?: '']);
$paginateBase = BASE_URL . '/index.php?' . http_build_query($baseParams);
?>

<!-- HERO -->
<?php if (!$isSearch): ?>
<section class="hero-banner">
    <div class="container position-relative">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="hero-title">Tìm <span>Phòng Trọ</span><br>Nhanh & Uy Tín</h1>
                <p class="hero-subtitle">Hàng nghìn tin đăng phòng trọ, căn hộ mini, nhà nguyên căn trên toàn quốc</p>

                <!-- Search box -->
                <form method="GET" action="<?= BASE_URL ?>/index.php">
                    <div class="search-box-hero">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md">
                                <input type="text" name="q" class="form-control form-control-lg"
                                       placeholder="🔍 Tìm theo tên, địa chỉ, khu vực..."
                                       value="<?= e($search) ?>">
                            </div>
                            <div class="col-6 col-md-3">
                                <select name="tinh_thanh" class="form-select form-select-lg">
                                    <option value="">📍 Tất cả tỉnh</option>
                                    <?php foreach ($cities as $c): ?>
                                    <option value="<?= e($c['tinh_thanh']) ?>"><?= e($c['tinh_thanh']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <select name="loai" class="form-select form-select-lg">
                                    <option value="">🏘 Loại</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e($cat['ten']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn-search-hero w-100">
                                    <i class="bi bi-search me-1"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
    <div class="container">
        <div class="row g-3">
            <div class="col-4 stat-item">
                <div class="number"><?= number_format($totalRooms) ?>+</div>
                <div class="label">Tin đăng</div>
            </div>
            <div class="col-4 stat-item">
                <div class="number"><?= number_format($totalUsers) ?>+</div>
                <div class="label">Người dùng</div>
            </div>
            <div class="col-4 stat-item">
                <div class="number"><?= number_format($totalCities) ?>+</div>
                <div class="label">Tỉnh/Thành</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MAIN CONTENT -->
<div class="container py-4">
    <?php renderFlash(); ?>

    <div class="row g-4">
        <!-- SIDEBAR FILTER -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <div class="filter-title"><i class="bi bi-funnel me-2 text-warning"></i>Bộ lọc tìm kiếm</div>
                <form method="GET" action="<?= BASE_URL ?>/index.php" id="filterForm">
                    <div class="filter-group">
                        <label>Từ khóa</label>
                        <input type="text" name="q" class="form-control" placeholder="Tên, địa chỉ..." value="<?= e($search) ?>">
                    </div>
                    <div class="filter-group">
                        <label>Tỉnh/Thành phố</label>
                        <select name="tinh_thanh" class="form-select">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($cities as $c): ?>
                            <option value="<?= e($c['tinh_thanh']) ?>" <?= $tinh_thanh === $c['tinh_thanh'] ? 'selected' : '' ?>>
                                <?= e($c['tinh_thanh']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Loại phòng</label>
                        <select name="loai" class="form-select">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $loai_id === $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['ten']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Giá từ (đ/tháng)</label>
                        <input type="number" name="gia_min" class="form-control" placeholder="0" value="<?= $gia_min ?: '' ?>" min="0" step="100000">
                    </div>
                    <div class="filter-group">
                        <label>Giá đến (đ/tháng)</label>
                        <input type="number" name="gia_max" class="form-control" placeholder="Không giới hạn" value="<?= $gia_max ?: '' ?>" min="0" step="100000">
                    </div>

                    <button type="submit" class="btn-filter">
                        <i class="bi bi-search me-1"></i>Lọc kết quả
                    </button>
                    <a href="<?= BASE_URL ?>/index.php" class="btn-filter-reset d-block text-center text-decoration-none">
                        <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                    </a>
                </form>
            </div>
        </div>

        <!-- ROOM LIST -->
        <div class="col-lg-9">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <?php if ($isSearch): ?>
                        <h2 class="section-title mb-0">Kết quả tìm kiếm</h2>
                        <p class="section-subtitle mt-1">Tìm thấy <strong><?= $total ?></strong> tin đăng phù hợp</p>
                    <?php else: ?>
                        <h2 class="section-title mb-0">Phòng trọ <span>mới nhất</span></h2>
                        <div class="divider-primary"></div>
                        <p class="section-subtitle">Tổng <?= $total ?> tin đăng</p>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" style="width:auto" id="sortSelect">
                        <option>Mới nhất</option>
                    </select>
                </div>
            </div>

            <?php if (empty($rooms)): ?>
            <div class="text-center py-5">
                <div style="font-size:4rem">🔍</div>
                <h4 class="mt-3 text-muted">Không tìm thấy phòng phù hợp</h4>
                <p class="text-muted">Thử thay đổi từ khóa hoặc bộ lọc</p>
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-warning mt-2">Xem tất cả phòng</a>
            </div>
            <?php else: ?>
            <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-xl-3">
                <?php foreach ($rooms as $room): ?>
                <div class="col">
                    <div class="room-card h-100">
                        <div class="room-card-img">
                            <img src="<?= getImageUrl($room['hinh_anh']) ?>"
                                 alt="<?= e($room['tieu_de']) ?>"
                                 loading="lazy">
                            <?php if ($room['loai_ten']): ?>
                            <div class="room-card-badge"><?= e($room['loai_ten']) ?></div>
                            <?php endif; ?>
                            <div class="room-card-price"><?= formatPrice($room['gia']) ?>/tháng</div>
                        </div>
                        <div class="room-card-body">
                            <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $room['id'] ?>" class="room-card-title">
                                <?= e($room['tieu_de']) ?>
                            </a>
                            <div class="room-card-location">
                                <i class="bi bi-geo-alt-fill text-warning"></i>
                                <?= e($room['dia_chi']) ?><?= $room['phuong_xa'] ? ', ' . e($room['phuong_xa']) : '' ?>, <?= e($room['tinh_thanh']) ?>
                            </div>
                            <div class="room-meta">
                                <span class="room-meta-item"><i class="bi bi-rulers"></i><?= $room['dien_tich'] ?>m²</span>
                                <span class="room-meta-item"><i class="bi bi-door-closed"></i><?= $room['so_phong_ngu'] ?> PN</span>
                                <span class="room-meta-item"><i class="bi bi-droplet"></i><?= $room['so_wc'] ?> WC</span>
                            </div>
                        </div>
                        <div class="room-card-footer">
                            <span><i class="bi bi-person me-1"></i><?= e($room['chu_tro']) ?></span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted" style="font-size:.78rem"><?= timeAgo($room['created_at']) ?></span>
                                <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $room['id'] ?>" class="btn-view-detail">
                                    Xem <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PAGINATION -->
            <div class="mt-4">
                <?php renderPagination($pg, $paginateBase); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
