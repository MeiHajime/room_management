<?php
// admin/tin-dang/index.php — Quản lý Tin đăng
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

// Xử lý action nhanh (duyệt / ẩn / từ chối / xóa) — phải chạy TRƯỚC khi include header
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];
    $status_map = [
        'approve'  => 'da_duyet',
        'hide'     => 'an',
        'reject'   => 'bi_tu_choi',
        'pending'  => 'cho_duyet',
    ];
    if (isset($status_map[$action])) {
        $new_status = $status_map[$action];
        $stmt = $db->prepare("UPDATE tin_dang SET trang_thai = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $new_status, $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Đã cập nhật trạng thái tin đăng.');
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM tin_dang WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Đã xóa tin đăng.');
    }
    redirect(BASE_URL . '/admin/tin-dang/index.php');
}

// Search / Filter
$search   = trim($_GET['q'] ?? '');
$status   = $_GET['status'] ?? '';
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(td.tieu_de LIKE ? OR p.dia_chi LIKE ? OR u.ho_ten LIKE ?)";
    $kw = "%$search%";
    $params  = array_merge($params, [$kw, $kw, $kw]);
    $types  .= 'sss';
}
$valid_statuses = ['cho_duyet', 'da_duyet', 'bi_tu_choi', 'an', 'da_thue'];
if (in_array($status, $valid_statuses)) {
    $where[]  = "td.trang_thai = ?";
    $params[] = $status;
    $types   .= 's';
}

$whereSQL  = implode(' AND ', $where);
$baseQuery = "FROM tin_dang td
              JOIN phong_tro p ON td.phong_tro_id = p.id
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN loai_phong l ON p.loai_phong_id = l.id
              WHERE $whereSQL";

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
$sql = "SELECT td.*, p.dia_chi, p.hinh_anh, p.loai_phong_id,
               u.ho_ten as chu_tro, u.id as user_id_chu,
               l.ten as loai_ten
        $baseQuery ORDER BY td.created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Thống kê nhanh
$stats_raw = $db->query("SELECT trang_thai, COUNT(*) as cnt FROM tin_dang GROUP BY trang_thai")->fetch_all(MYSQLI_ASSOC);
$stats = array_column($stats_raw, 'cnt', 'trang_thai');

$baseUrl = BASE_URL . '/admin/tin-dang/index.php?' . http_build_query(array_filter(['q' => $search, 'status' => $status]));

$pageTitle = 'Quản lý Tin đăng';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-megaphone"></i></span><?= $pageTitle ?></h1>
    <a href="<?= BASE_URL ?>/admin/tin-dang/add.php" class="btn-admin-primary">
        <i class="bi bi-plus-lg"></i> Thêm tin đăng
    </a>
</div>

<!-- STAT QUICK CARDS -->
<div class="stat-grid mb-4" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
    <div class="stat-card" style="cursor:pointer" onclick="location.href='?status='">
        <div class="stat-card-icon orange"><i class="bi bi-megaphone"></i></div>
        <div>
            <div class="stat-card-label">Tổng tin</div>
            <div class="stat-card-value"><?= array_sum($stats) ?></div>
        </div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="location.href='?status=cho_duyet'">
        <div class="stat-card-icon blue"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="stat-card-label">Chờ duyệt</div>
            <div class="stat-card-value"><?= $stats['cho_duyet'] ?? 0 ?></div>
        </div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="location.href='?status=da_duyet'">
        <div class="stat-card-icon green"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="stat-card-label">Đã duyệt</div>
            <div class="stat-card-value"><?= $stats['da_duyet'] ?? 0 ?></div>
        </div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="location.href='?status=bi_tu_choi'">
        <div class="stat-card-icon red"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="stat-card-label">Từ chối</div>
            <div class="stat-card-value"><?= $stats['bi_tu_choi'] ?? 0 ?></div>
        </div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="location.href='?status=an'">
        <div class="stat-card-icon purple"><i class="bi bi-eye-slash-fill"></i></div>
        <div>
            <div class="stat-card-label">Đã ẩn</div>
            <div class="stat-card-value"><?= $stats['an'] ?? 0 ?></div>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <form method="GET" action="" class="search-bar">
            <input type="text" name="q" class="form-control"
                   placeholder="🔍 Tìm theo tiêu đề, địa chỉ, người đăng..."
                   value="<?= e($search) ?>" style="min-width:260px">
            <select name="status" class="form-control" style="width:auto">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="cho_duyet"  <?= $status === 'cho_duyet'  ? 'selected' : '' ?>>⏳ Chờ duyệt</option>
                <option value="da_duyet"   <?= $status === 'da_duyet'   ? 'selected' : '' ?>>✅ Đã duyệt</option>
                <option value="bi_tu_choi" <?= $status === 'bi_tu_choi' ? 'selected' : '' ?>>❌ Bị từ chối</option>
                <option value="an"         <?= $status === 'an'         ? 'selected' : '' ?>>🙈 Đã ẩn</option>
            </select>
            <button type="submit" class="btn-search"><i class="bi bi-search"></i> Tìm</button>
            <a href="<?= BASE_URL ?>/admin/tin-dang/index.php" class="btn-admin-secondary">
                <i class="bi bi-x"></i> Xóa lọc
            </a>
        </form>
        <div style="font-size:.85rem;color:var(--admin-muted)">
            Tổng: <strong><?= $total ?></strong> tin
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
                    <th>Chủ phòng</th>
                    <th>Giá</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Lượt xem</th>
                    <th>Ngày đăng</th>
                    <th width="200">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td>
                        <img src="<?= getImageUrl($r['hinh_anh']) ?>"
                             class="room-thumb" alt=""
                             onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                    </td>
                    <td style="max-width:200px">
                        <div style="font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"
                             title="<?= e($r['tieu_de'] ?? '') ?>">
                            <?= e($r['tieu_de'] ?? '—') ?>
                        </div>
                        <div style="font-size:.78rem;color:var(--admin-muted);overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
                            <i class="bi bi-geo-alt"></i> <?= e($r['dia_chi'] ?? '') ?>
                        </div>
                    </td>
                    <td><?= e($r['chu_tro'] ?? '—') ?></td>
                    <td style="font-weight:700;color:var(--admin-primary);white-space:nowrap">
                        <?= formatPrice((float)($r['gia'] ?? 0)) ?>
                    </td>
                    <td><?= $r['loai_ten'] ? e($r['loai_ten']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= tinDangStatusBadge($r['trang_thai']) ?></td>
                    <td style="text-align:center"><?= number_format($r['luot_xem'] ?? 0) ?></td>
                    <td style="white-space:nowrap"><?= formatDate($r['created_at'] ?? '') ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="<?= BASE_URL ?>/admin/tin-dang/edit.php?id=<?= $r['id'] ?>"
                               class="btn-action edit" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($r['trang_thai'] === 'cho_duyet'): ?>
                            <a href="?action=approve&id=<?= $r['id'] ?>" class="btn-action approve" title="Duyệt">
                                <i class="bi bi-check-lg"></i>
                            </a>
                            <a href="?action=reject&id=<?= $r['id'] ?>" class="btn-action hide" title="Từ chối"
                               onclick="return confirm('Từ chối tin đăng này?')">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            <?php elseif ($r['trang_thai'] === 'da_duyet'): ?>
                            <a href="?action=hide&id=<?= $r['id'] ?>" class="btn-action hide" title="Ẩn tin">
                                <i class="bi bi-eye-slash"></i>
                            </a>
                            <?php elseif (in_array($r['trang_thai'], ['an', 'bi_tu_choi'])): ?>
                            <a href="?action=approve&id=<?= $r['id'] ?>" class="btn-action approve" title="Duyệt lại">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php endif; ?>
                            <a href="?action=delete&id=<?= $r['id'] ?>"
                               class="btn-action delete confirm-delete" title="Xóa"
                               onclick="return confirm('Xóa vĩnh viễn tin đăng này?')">
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
