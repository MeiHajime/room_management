<?php
// admin/reports/index.php — Báo cáo thống kê
$pageTitle = 'Báo cáo & Thống kê';
require_once __DIR__ . '/../../includes/admin_header.php';
$db = getDB();

// ── FILTER ──────────────────────────────────────────
$search_user = trim($_GET['user'] ?? '');
$date_from   = $_GET['date_from'] ?? '';
$date_to     = $_GET['date_to']   ?? '';
$price_min   = (int)($_GET['price_min'] ?? 0);
$price_max   = (int)($_GET['price_max'] ?? 0);
$sort_by     = $_GET['sort'] ?? 'newest';
$page_num    = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 15;
$month_stat  = (int)($_GET['month'] ?? date('n'));
$year_stat   = (int)($_GET['year']  ?? date('Y'));

// ── BUILD QUERY ──────────────────────────────────────
$where  = ["p.trang_thai != 'bi_xoa'"];
$params = [];
$types  = '';

if ($search_user !== '') {
    $where[]  = "(u.ho_ten LIKE ? OR u.username LIKE ?)";
    $kw = "%$search_user%";
    $params[] = $kw; $params[] = $kw;
    $types   .= 'ss';
}
if ($date_from !== '') {
    $where[]  = "p.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types   .= 's';
}
if ($date_to !== '') {
    $where[]  = "p.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types   .= 's';
}
if ($price_min > 0) {
    $where[]  = "p.gia >= ?";
    $params[] = $price_min;
    $types   .= 'i';
}
if ($price_max > 0) {
    $where[]  = "p.gia <= ?";
    $params[] = $price_max;
    $types   .= 'i';
}

$order_map = [
    'newest'    => 'p.created_at DESC',
    'oldest'    => 'p.created_at ASC',
    'price_asc' => 'p.gia ASC',
    'price_desc'=> 'p.gia DESC',
    'views'     => 'p.luot_xem DESC',
];
$order = $order_map[$sort_by] ?? 'p.created_at DESC';
$whereSQL = implode(' AND ', $where);

$baseQ  = "FROM phong_tro p LEFT JOIN users u ON p.user_id = u.id WHERE $whereSQL";
$countQ = "SELECT COUNT(*) $baseQ";
$listQ  = "SELECT p.*, u.ho_ten as chu_tro, u.username $baseQ ORDER BY $order";

// Count
if (!empty($params)) {
    $s = $db->prepare($countQ); $s->bind_param($types, ...$params); $s->execute();
    $total = $s->get_result()->fetch_row()[0]; $s->close();
} else {
    $total = $db->query($countQ)->fetch_row()[0];
}
$pg     = paginate($total, $per_page, $page_num);
$offset = $pg['offset'];
$sql    = $listQ . " LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $s = $db->prepare($sql); $s->bind_param($types, ...$params); $s->execute();
    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
} else {
    $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// ── THỐNG KÊ THÁNG ─────────────────────────────────
$stat_month = $db->query(
    "SELECT COUNT(*) as total,
            SUM(gia) as tong_gia,
            AVG(gia) as avg_gia,
            SUM(CASE WHEN trang_thai='da_duyet' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN trang_thai='cho_duyet' THEN 1 ELSE 0 END) as pending
     FROM phong_tro
     WHERE MONTH(created_at)=$month_stat AND YEAR(created_at)=$year_stat
       AND trang_thai != 'bi_xoa'"
)->fetch_assoc();

// Chart — thống kê theo ngày trong tháng
$daily_chart = $db->query(
    "SELECT DAY(created_at) as ngay, COUNT(*) as so_luong
     FROM phong_tro
     WHERE MONTH(created_at)=$month_stat AND YEAR(created_at)=$year_stat
       AND trang_thai != 'bi_xoa'
     GROUP BY ngay ORDER BY ngay"
)->fetch_all(MYSQLI_ASSOC);

// Top người đăng
$top_users = $db->query(
    "SELECT u.ho_ten, u.username, COUNT(p.id) as so_tin,
            SUM(p.gia) as tong_gia
     FROM phong_tro p
     LEFT JOIN users u ON p.user_id = u.id
     WHERE p.trang_thai != 'bi_xoa'
     GROUP BY p.user_id ORDER BY so_tin DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$baseUrl = BASE_URL . '/admin/reports/index.php?' . http_build_query(array_filter([
    'user' => $search_user, 'date_from' => $date_from, 'date_to' => $date_to,
    'price_min' => $price_min ?: '', 'price_max' => $price_max ?: '', 'sort' => $sort_by,
]));
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-bar-chart-line"></i></span><?= $pageTitle ?></h1>
</div>

<!-- THÁNG STATS -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <!-- Chọn tháng -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <div class="data-card-title"><i class="bi bi-calendar3 me-2 text-warning"></i>Thống kê tháng</div>
                <form method="GET" action="" class="d-flex gap-2 align-items-center">
                    <?php // preserve other params ?>
                    <?php foreach (['user','date_from','date_to','price_min','price_max','sort'] as $k): if (!empty($_GET[$k])): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= e($_GET[$k]) ?>">
                    <?php endif; endforeach; ?>
                    <select name="month" class="form-control" style="width:auto">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $month_stat ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="form-control" style="width:auto">
                        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $year_stat ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn-search"><i class="bi bi-filter"></i> Xem</button>
                </form>
            </div>
            <div style="padding:1.25rem">
                <canvas id="dailyChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Summary cards -->
        <div class="row g-3">
            <div class="col-12">
                <div class="stat-card">
                    <div class="stat-card-icon orange"><i class="bi bi-newspaper"></i></div>
                    <div>
                        <div class="stat-card-label">Tin đăng tháng <?= $month_stat ?>/<?= $year_stat ?></div>
                        <div class="stat-card-value"><?= number_format($stat_month['total']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-card-icon green"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="stat-card-label">Đã duyệt</div>
                        <div class="stat-card-value"><?= $stat_month['approved'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-card-icon blue"><i class="bi bi-hourglass"></i></div>
                    <div>
                        <div class="stat-card-label">Chờ duyệt</div>
                        <div class="stat-card-value"><?= $stat_month['pending'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="stat-card">
                    <div class="stat-card-icon purple"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="stat-card-label">Giá thuê TB</div>
                        <div class="stat-card-value" style="font-size:1.2rem">
                            <?= $stat_month['avg_gia'] ? formatPrice($stat_month['avg_gia']) : '—' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TOP USERS -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <div class="data-card-title"><i class="bi bi-trophy me-2 text-warning"></i>Top người đăng nhiều tin nhất</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>#</th><th>Tài khoản</th><th>Số tin đăng</th><th>Tổng giá trị</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($top_users as $i => $tu): ?>
            <tr>
                <td>
                    <?php if ($i === 0): ?><span style="font-size:1.2rem">🥇</span>
                    <?php elseif ($i === 1): ?><span style="font-size:1.2rem">🥈</span>
                    <?php elseif ($i === 2): ?><span style="font-size:1.2rem">🥉</span>
                    <?php else: echo $i + 1; endif; ?>
                </td>
                <td>
                    <strong><?= e($tu['ho_ten']) ?></strong>
                    <div style="font-size:.78rem;color:var(--admin-muted)">@<?= e($tu['username']) ?></div>
                </td>
                <td><strong style="color:var(--admin-primary)"><?= $tu['so_tin'] ?></strong> tin</td>
                <td><?= formatPrice($tu['tong_gia']) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/admin/reports/index.php?user=<?= urlencode($tu['ho_ten']) ?>" class="btn-action view">Xem tin</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- FILTER TÌM KIẾM TIN ĐĂNG -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <div class="data-card-title"><i class="bi bi-funnel me-2 text-warning"></i>Tìm kiếm & Sắp xếp tin đăng</div>
    </div>
    <div style="padding:1.25rem">
        <form method="GET" action="">
            <?php // preserve month/year ?>
            <input type="hidden" name="month" value="<?= $month_stat ?>">
            <input type="hidden" name="year"  value="<?= $year_stat ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-size:.85rem;font-weight:600">Người đăng</label>
                    <input type="text" name="user" class="form-control" value="<?= e($search_user) ?>" placeholder="Tên người đăng...">
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.85rem;font-weight:600">Từ ngày</label>
                    <input type="date" name="date_from" class="form-control" value="<?= e($date_from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.85rem;font-weight:600">Đến ngày</label>
                    <input type="date" name="date_to" class="form-control" value="<?= e($date_to) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.85rem;font-weight:600">Giá từ (đ)</label>
                    <input type="number" name="price_min" class="form-control" value="<?= $price_min ?: '' ?>" placeholder="0" step="100000">
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.85rem;font-weight:600">Giá đến (đ)</label>
                    <input type="number" name="price_max" class="form-control" value="<?= $price_max ?: '' ?>" placeholder="∞" step="100000">
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:.85rem;font-weight:600">Sắp xếp theo</label>
                    <select name="sort" class="form-select">
                        <option value="newest"    <?= $sort_by === 'newest'     ? 'selected' : '' ?>>⏰ Mới nhất</option>
                        <option value="oldest"    <?= $sort_by === 'oldest'     ? 'selected' : '' ?>>📅 Cũ nhất</option>
                        <option value="price_asc" <?= $sort_by === 'price_asc'  ? 'selected' : '' ?>>💰 Giá tăng dần</option>
                        <option value="price_desc"<?= $sort_by === 'price_desc' ? 'selected' : '' ?>>💰 Giá giảm dần</option>
                        <option value="views"     <?= $sort_by === 'views'      ? 'selected' : '' ?>>👁 Xem nhiều nhất</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn-admin-primary"><i class="bi bi-search"></i> Tìm kiếm</button>
                    <a href="<?= BASE_URL ?>/admin/reports/index.php" class="btn-admin-secondary"><i class="bi bi-x"></i> Xóa lọc</a>
                    <span style="margin-left:auto;font-size:.875rem;color:var(--admin-muted);align-self:center">
                        <strong><?= $total ?></strong> kết quả
                    </span>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- DANH SÁCH TIN ĐĂNG -->
<div class="data-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th><th>Tiêu đề</th><th>Người đăng</th>
                    <th>Giá</th><th>Trạng thái</th><th>Lượt xem</th>
                    <th>Ngày đăng</th><th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td style="max-width:200px">
                    <div style="font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis" title="<?= e($r['tieu_de']) ?>">
                        <?= e($r['tieu_de']) ?>
                    </div>
                    <div style="font-size:.78rem;color:var(--admin-muted)"><?= e($r['dia_chi'] ?? '') ?></div>
                </td>
                <td><?= e($r['chu_tro'] ?? '—') ?></td>
                <td style="font-weight:700;color:var(--admin-primary);white-space:nowrap"><?= formatPrice($r['gia']) ?></td>
                <td><?= roomStatusBadge($r['trang_thai']) ?></td>
                <td style="text-align:center"><?= number_format($r['luot_xem']) ?></td>
                <td style="white-space:nowrap"><?= formatDateTime($r['created_at']) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $r['id'] ?>" class="btn-action view" target="_blank">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/rooms/edit.php?id=<?= $r['id'] ?>" class="btn-action edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">
        <?php renderPagination($pg, $baseUrl); ?>
    </div>
</div>

<script>
// Daily chart
const dCtx = document.getElementById('dailyChart').getContext('2d');
const dDays   = <?= json_encode(array_column($daily_chart, 'ngay')) ?>;
const dCounts = <?= json_encode(array_column($daily_chart, 'so_luong')) ?>;
new Chart(dCtx, {
    type: 'line',
    data: {
        labels: dDays.map(d => 'Ngày ' + d),
        datasets: [{
            label: 'Tin đăng',
            data: dCounts,
            fill: true,
            backgroundColor: 'rgba(249,115,22,.12)',
            borderColor: '#f97316',
            borderWidth: 2.5,
            pointBackgroundColor: '#f97316',
            pointRadius: 4,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
