<?php
// admin/rooms/index.php — Danh sách phòng trọ (Admin)
// Phải require functions.php TRƯỚC để xử lý redirect() trước khi output HTML
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

// Xử lý action nhanh (duyệt / ẩn / xóa) — phải chạy TRƯỚC khi include header
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];
    $status_map = [
        'approve' => 'da_duyet',
        'hide'    => 'bi_an',
        'pending' => 'cho_duyet',
    ];
    if (isset($status_map[$action])) {
        $new_status = $status_map[$action];
        $stmt = $db->prepare("UPDATE phong_tro SET trang_thai = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Đã cập nhật trạng thái phòng.');
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("UPDATE phong_tro SET trang_thai = 'bi_xoa' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Đã xóa phòng trọ.');
    }
    redirect(BASE_URL . '/admin/rooms/index.php');
}

// Search / Filter
$search      = trim($_GET['q'] ?? '');
$status      = $_GET['status'] ?? '';
$page_num    = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 12;

$where  = ["p.trang_thai != 'bi_xoa'"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(p.tieu_de LIKE ? OR p.dia_chi LIKE ? OR u.ho_ten LIKE ?)";
    $kw = "%$search%";
    $params  = array_merge($params, [$kw, $kw, $kw]);
    $types  .= 'sss';
}
if (in_array($status, ['cho_duyet', 'da_duyet', 'bi_an'])) {
    $where[]  = "p.trang_thai = ?";
    $params[] = $status;
    $types   .= 's';
}

$whereSQL = implode(' AND ', $where);
$baseQuery = "FROM phong_tro p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN loai_phong l ON p.loai_phong_id = l.id WHERE $whereSQL";

// Count
if (!empty($params)) {
    $stmt = $db->prepare("SELECT COUNT(*) $baseQuery");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
} else {
    $total = $db->query("SELECT COUNT(*) $baseQuery")->fetch_row()[0];
}
$pg     = paginate($total, $per_page, $page_num);
$offset = $pg['offset'];

// Fetch
$sql = "SELECT p.*, u.ho_ten as chu_tro, l.ten as loai_ten $baseQuery ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rooms = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$baseUrl = BASE_URL . '/admin/rooms/index.php?' . http_build_query(array_filter(['q' => $search, 'status' => $status]));

// Tất cả logic xong → bây giờ mới output HTML
$pageTitle = 'Quản lý Phòng Trọ';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-house-door"></i></span><?= $pageTitle ?></h1>
    <a href="<?= BASE_URL ?>/admin/rooms/add.php" class="btn-admin-primary">
        <i class="bi bi-plus-lg"></i> Thêm phòng mới
    </a>
</div>

<!-- FILTER BAR -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <form method="GET" action="" class="search-bar">
            <input type="text" name="q" class="form-control" placeholder="🔍 Tìm theo tiêu đề, địa chỉ, người đăng..." value="<?= e($search) ?>" style="min-width:260px">
            <select name="status" class="form-control" style="width:auto">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="cho_duyet" <?= $status === 'cho_duyet' ? 'selected' : '' ?>>⏳ Chờ duyệt</option>
                <option value="da_duyet"  <?= $status === 'da_duyet'  ? 'selected' : '' ?>>✅ Đã duyệt</option>
                <option value="bi_an"     <?= $status === 'bi_an'     ? 'selected' : '' ?>>🙈 Đã ẩn</option>
            </select>
            <button type="submit" class="btn-search"><i class="bi bi-search"></i> Tìm</button>
            <a href="<?= BASE_URL ?>/admin/rooms/index.php" class="btn-admin-secondary">
                <i class="bi bi-x"></i> Xóa lọc
            </a>
        </form>
        <div style="font-size:.85rem;color:var(--admin-muted)">
            Tổng: <strong><?= $total ?></strong> phòng
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="data-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th width="60">Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Người đăng</th>
                    <th>Giá</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng</th>
                    <th width="180">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>
                <?php endif; ?>
                <?php foreach ($rooms as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td>
                        <img src="<?= getImageUrl($r['hinh_anh']) ?>"
                             class="room-thumb"
                             alt=""
                             onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                    </td>
                    <td style="max-width:220px">
                        <div style="font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis" title="<?= e($r['tieu_de']) ?>">
                            <?= e($r['tieu_de']) ?>
                        </div>
                        <div style="font-size:.78rem;color:var(--admin-muted);overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
                            <i class="bi bi-geo-alt"></i> <?= e($r['dia_chi']) ?>
                        </div>
                    </td>
                    <td><?= e($r['chu_tro']) ?></td>
                    <td style="font-weight:700;color:var(--admin-primary);white-space:nowrap">
                        <?= formatPrice($r['gia']) ?>
                    </td>
                    <td><?= $r['loai_ten'] ? e($r['loai_ten']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= roomStatusBadge($r['trang_thai']) ?></td>
                    <td style="white-space:nowrap"><?= formatDate($r['created_at']) ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $r['id'] ?>" class="btn-action view" target="_blank" title="Xem">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/rooms/edit.php?id=<?= $r['id'] ?>" class="btn-action edit" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($r['trang_thai'] === 'cho_duyet'): ?>
                            <a href="?action=approve&id=<?= $r['id'] ?>" class="btn-action approve" title="Duyệt">
                                <i class="bi bi-check-lg"></i> Duyệt
                            </a>
                            <?php elseif ($r['trang_thai'] === 'da_duyet'): ?>
                            <a href="?action=hide&id=<?= $r['id'] ?>" class="btn-action hide" title="Ẩn">
                                <i class="bi bi-eye-slash"></i>
                            </a>
                            <?php elseif ($r['trang_thai'] === 'bi_an'): ?>
                            <a href="?action=approve&id=<?= $r['id'] ?>" class="btn-action approve" title="Bỏ ẩn">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php endif; ?>
                            <a href="?action=delete&id=<?= $r['id'] ?>" class="btn-action delete confirm-delete" title="Xóa">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="p-3 border-top">
        <?php renderPagination($pg, $baseUrl); ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
