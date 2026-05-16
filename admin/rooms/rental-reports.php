<?php
// admin/rooms/rental-reports.php — Quản lý báo cáo phòng đã thuê (admin)
require_once __DIR__ . '/../../includes/functions.php';
startSession();
requireAdmin();

$db = getDB();

// Xử lý action duyệt / từ chối
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid    = (int)($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($rid > 0 && in_array($action, ['da_xac_nhan', 'tu_choi'])) {
        $db->query("UPDATE bao_cao_da_thue SET trang_thai = '$action', updated_at = NOW() WHERE id = $rid");

        if ($action === 'da_xac_nhan') {
            // Lấy phong_id để cập nhật trạng thái phòng
            $report = $db->query("SELECT phong_id FROM bao_cao_da_thue WHERE id = $rid LIMIT 1")->fetch_assoc();
            if ($report) {
                $pid = (int)$report['phong_id'];
                $db->query("UPDATE phong_tro SET trang_thai = 'da_duoc_dat', updated_at = NOW() WHERE id = $pid");
            }
            setFlash('success', 'Đã xác nhận phòng đã thuê.');
        } else {
            setFlash('warning', 'Đã từ chối báo cáo.');
        }
    }
    redirect(BASE_URL . '/admin/rooms/rental-reports.php');
}

// Lấy danh sách báo cáo
$reports = $db->query("
    SELECT b.*, p.tieu_de, p.dia_chi, p.gia, p.hinh_anh,
           u.ho_ten, u.email, k.tinh_thanh
    FROM bao_cao_da_thue b
    JOIN phong_tro p ON b.phong_id = p.id
    JOIN users u     ON b.user_id  = u.id
    LEFT JOIN khu_vuc k ON p.khu_vuc_id = k.id
    ORDER BY b.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$statusMap = [
    'cho_duyet'   => ['warning', 'Chờ duyệt'],
    'da_xac_nhan' => ['success', 'Đã xác nhận'],
    'tu_choi'     => ['danger',  'Từ chối'],
];

// Trang admin tự render HTML riêng bên dưới
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Báo cáo đã thuê — Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background: var(--bg); font-family: inherit; }
        .admin-wrap { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .report-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 1.25rem; margin-bottom: 1rem; }
        .report-card img { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
<?php
// Reset để dùng lại renderFlash
require_once __DIR__ . '/../../includes/functions.php';
renderFlash();
?>
<div class="admin-wrap">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="section-title mb-0">Báo cáo <span>đã thuê</span></h1>
            <p class="section-subtitle mt-1">Tổng <?= count($reports) ?> báo cáo</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Admin Dashboard
        </a>
    </div>

    <?php if (empty($reports)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:3rem"></i>
        <p class="mt-2">Chưa có báo cáo nào.</p>
    </div>
    <?php else: ?>

    <?php foreach ($reports as $r): ?>
    <?php [$statusColor, $statusLabel] = $statusMap[$r['trang_thai']] ?? ['secondary', $r['trang_thai']]; ?>
    <div class="report-card">
        <div class="d-flex gap-3 align-items-start flex-wrap">
            <img src="<?= getImageUrl($r['hinh_anh']) ?>" alt="">
            <div class="flex-grow-1">
                <div class="fw-bold mb-1" style="color:var(--secondary)"><?= e($r['tieu_de']) ?></div>
                <div style="font-size:.82rem;color:var(--text-muted)">
                    <i class="bi bi-geo-alt text-warning"></i> <?= e($r['dia_chi']) ?>, <?= e($r['tinh_thanh'] ?? '') ?>
                    &nbsp;·&nbsp; <i class="bi bi-cash text-success"></i> <?= formatPrice($r['gia']) ?>/tháng
                </div>
                <div class="mt-1" style="font-size:.82rem">
                    <i class="bi bi-person text-warning"></i> <?= e($r['ho_ten']) ?>
                    (<a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a>)
                </div>
                <?php if ($r['ghi_chu']): ?>
                <div class="mt-2 p-2 rounded-2" style="background:#f8fafc;font-size:.82rem;color:var(--text-muted);border:1px solid var(--border)">
                    <i class="bi bi-chat-text text-warning me-1"></i><?= e($r['ghi_chu']) ?>
                </div>
                <?php endif; ?>
                <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-<?= $statusColor ?>"><?= $statusLabel ?></span>
                    <small class="text-muted"><?= formatDateTime($r['created_at']) ?></small>
                </div>
            </div>

            <?php if ($r['trang_thai'] === 'cho_duyet'): ?>
            <div class="d-flex gap-2">
                <form method="POST">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="da_xac_nhan">
                    <button class="btn btn-success btn-sm" onclick="return confirm('Xác nhận phòng đã thuê?')">
                        <i class="bi bi-check2-circle me-1"></i>Xác nhận
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="tu_choi">
                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Từ chối báo cáo này?')">
                        <i class="bi bi-x-circle me-1"></i>Từ chối
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
