<?php
// admin/index.php — Admin Dashboard
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
$db = getDB();

// Stats
$stats = [
    'total_rooms'    => $db->query("SELECT COUNT(*) FROM phong_tro WHERE trang_thai != 'bi_xoa'")->fetch_row()[0],
    'cho_duyet'      => $db->query("SELECT COUNT(*) FROM phong_tro WHERE trang_thai = 'cho_duyet'")->fetch_row()[0],
    'da_duyet'       => $db->query("SELECT COUNT(*) FROM phong_tro WHERE trang_thai = 'da_duyet'")->fetch_row()[0],
    'total_users'    => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetch_row()[0],
    'new_this_month' => $db->query("SELECT COUNT(*) FROM phong_tro WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_row()[0],
];

// Tin đăng mới nhất
$recentRooms = $db->query(
    "SELECT p.id, p.tieu_de, p.gia, p.trang_thai, p.created_at, u.ho_ten as chu_tro
     FROM phong_tro p LEFT JOIN users u ON p.user_id = u.id
     ORDER BY p.created_at DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// Chart data — số tin theo 6 tháng gần nhất
$chartRows = $db->query(
    "SELECT DATE_FORMAT(created_at, '%m/%Y') as thang, COUNT(*) as so_luong
     FROM phong_tro
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY thang ORDER BY MIN(created_at)"
)->fetch_all(MYSQLI_ASSOC);
$chartLabels = array_column($chartRows, 'thang');
$chartData   = array_column($chartRows, 'so_luong');
?>

<!-- STAT CARDS -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-icon orange"><i class="bi bi-house-door"></i></div>
        <div>
            <div class="stat-card-label">Tổng phòng</div>
            <div class="stat-card-value"><?= number_format($stats['total_rooms']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="stat-card-label">Chờ duyệt</div>
            <div class="stat-card-value"><?= number_format($stats['cho_duyet']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="stat-card-label">Đã duyệt</div>
            <div class="stat-card-value"><?= number_format($stats['da_duyet']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon purple"><i class="bi bi-people"></i></div>
        <div>
            <div class="stat-card-label">Người dùng</div>
            <div class="stat-card-value"><?= number_format($stats['total_users']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon red"><i class="bi bi-calendar3"></i></div>
        <div>
            <div class="stat-card-label">Tin tháng này</div>
            <div class="stat-card-value"><?= number_format($stats['new_this_month']) ?></div>
        </div>
    </div>
</div>

<!-- CHART + RECENT -->
<div class="row g-4">
    <!-- Chart -->
    <div class="col-lg-7">
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-title"><i class="bi bi-bar-chart me-2 text-warning"></i>Tin đăng theo tháng</div>
            </div>
            <div style="padding:1.5rem">
                <canvas id="roomChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick stats donut -->
    <div class="col-lg-5">
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-title"><i class="bi bi-pie-chart me-2 text-warning"></i>Trạng thái phòng</div>
            </div>
            <div style="padding:1.5rem;display:flex;align-items:center;justify-content:center">
                <canvas id="statusChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- RECENT ROOMS TABLE -->
<div class="data-card mt-4">
    <div class="data-card-header">
        <div class="data-card-title"><i class="bi bi-clock-history me-2 text-warning"></i>Tin đăng mới nhất</div>
        <a href="<?= BASE_URL ?>/admin/rooms/index.php" class="btn-action view">Xem tất cả →</a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tiêu đề</th>
                    <th>Người đăng</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentRooms as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td style="max-width:200px">
                        <div style="font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis" title="<?= e($r['tieu_de']) ?>">
                            <?= e($r['tieu_de']) ?>
                        </div>
                    </td>
                    <td><?= e($r['chu_tro']) ?></td>
                    <td style="font-weight:700;color:var(--admin-primary)"><?= formatPrice($r['gia']) ?></td>
                    <td><?= roomStatusBadge($r['trang_thai']) ?></td>
                    <td><?= formatDate($r['created_at']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/rooms/edit.php?id=<?= $r['id'] ?>" class="btn-action edit">Sửa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Bar chart
const ctx1 = document.getElementById('roomChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Tin đăng',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(249,115,22,.7)',
            borderColor: '#f97316',
            borderWidth: 2,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Donut chart
const ctx2 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Chờ duyệt', 'Đã duyệt', 'Đã ẩn'],
        datasets: [{
            data: [<?= $stats['cho_duyet'] ?>, <?= $stats['da_duyet'] ?>,
                   <?= $db->query("SELECT COUNT(*) FROM phong_tro WHERE trang_thai='bi_an'")->fetch_row()[0] ?>],
            backgroundColor: ['#f59e0b', '#22c55e', '#94a3b8'],
            borderWidth: 0, hoverOffset: 8,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        cutout: '65%'
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
