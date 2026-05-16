<?php
// my-posts/index.php — Quản lý tin đăng của người dùng
$pageTitle = 'Quản lý tin đăng';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];

// Phân trang
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$total = $db->query("
    SELECT COUNT(*) FROM tin_dang td
    JOIN phong_tro p ON td.phong_tro_id = p.id
    WHERE p.user_id = $userId AND td.trang_thai != 'bi_xoa'
")->fetch_row()[0];
$pg    = paginate($total, $perPage, $page);

$rooms = $db->query("
    SELECT td.id AS tin_id, td.tieu_de, td.mo_ta, td.gia, td.luot_xem,
           td.trang_thai, td.created_at,
           p.id AS phong_id, p.dien_tich, p.dia_chi, p.gia_goc, p.hinh_anh,
           l.ten AS loai_ten, k.tinh_thanh
    FROM tin_dang td
    JOIN phong_tro p    ON td.phong_tro_id = p.id
    LEFT JOIN loai_phong l ON p.loai_phong_id = l.id
    LEFT JOIN khu_vuc k    ON p.khu_vuc_id = k.id
    WHERE p.user_id = $userId AND td.trang_thai != 'bi_xoa'
    ORDER BY td.created_at DESC
    LIMIT $perPage OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

$baseUrl = BASE_URL . '/my-posts/index.php?';
?>

<div class="container py-4">
    <?php renderFlash(); ?>

    <!-- Page header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="section-title mb-0">Quản lý <span>tin đăng</span></h1>
            <p class="section-subtitle mt-1">Tổng <strong><?= $total ?></strong> tin đăng</p>
        </div>
        <a href="<?= BASE_URL ?>/my-posts/create.php" class="btn-post-new">
            <i class="bi bi-plus-circle me-2"></i>Đăng tin mới
        </a>
    </div>

    <?php if (empty($rooms)): ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h4>Bạn chưa có tin đăng nào</h4>
        <p>Hãy đăng tin phòng trọ đầu tiên của bạn!</p>
        <a href="<?= BASE_URL ?>/my-posts/create.php" class="btn-post-new mt-2">
            <i class="bi bi-plus-circle me-2"></i>Đăng tin ngay
        </a>
    </div>
    <?php else: ?>

    <div class="posts-table-wrap">
        <table class="posts-table">
            <thead>
                <tr>
                    <th style="width:15%;">Ảnh</th>
                    <th style="width:30%">Tin đăng</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Lượt xem</th>
                    <th>Ngày đăng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rooms as $r): ?>
            <tr>
                <td>
                    <img src="<?= getImageUrl($r['hinh_anh']) ?>"
                        class="room-thumb"
                        style="border-radius: 10px"
                        alt=""
                        onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                </td>
                <td>
                    <div class="post-row-title">
                        <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $r['tin_id'] ?>" target="_blank">
                            <?= e($r['tieu_de']) ?>
                        </a>
                    </div>

                    <div class="post-row-meta">
                        <i class="bi bi-geo-alt text-warning"></i> <?= e($r['tinh_thanh'] ?? '') ?>
                        <?php if ($r['loai_ten']): ?>
                        · <span class="badge bg-warning text-dark" style="font-size:.7rem"><?= e($r['loai_ten']) ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="fw-semibold text-warning">
                    <?= formatPrice($r['gia']) ?>
                    <?php if (!empty($r['gia_goc']) && $r['gia_goc'] > $r['gia']): ?>
                    <br><small style="text-decoration:line-through;color:var(--text-muted);font-size:.75em"><?= formatPrice($r['gia_goc']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= tinDangStatusBadge($r['trang_thai']) ?></td>
                <td>
                    <span class="view-pill"><i class="bi bi-eye-fill"></i> <?= number_format($r['luot_xem']) ?></span>
                </td>
                <td style="font-size:.82rem;color:var(--text-muted)"><?= formatDate($r['created_at']) ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= BASE_URL ?>/my-posts/edit.php?id=<?= $r['tin_id'] ?>" class="btn-act edit" title="Sửa">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/my-posts/delete.php?id=<?= $r['tin_id'] ?>"
                           class="btn-act del" title="Xóa"
                           onclick="return confirm('Bạn chắc chắn muốn xóa tin này?')">
                            <i class="bi bi-trash"></i>
                        </a>
                        <?php if ($r['trang_thai'] === 'da_duyet'): ?>
                        <a href="<?= BASE_URL ?>/my-posts/report-rented.php?id=<?= $r['tin_id'] ?>" class="btn-act report" title="Báo cáo phòng đã cho thuê">
                            <i class="bi bi-bell"></i>
                        </a>
                        <?php elseif($r['trang_thai'] !== 'da_duyet'): ?>
                        <a href="<?= BASE_URL ?>/my-posts/report-rented.php?id=<?= $r['tin_id'] ?>" class="btn-act report" title="Yêu cầu mở lại tin đăng">
                            <i class="bi bi-unlock"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        <?php renderPagination($pg, $baseUrl); ?>
    </div>
    <?php endif; ?>
</div>

<style>
.btn-post-new {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, var(--primary), #ea580c);
    color: #fff; padding: 10px 22px; border-radius: 12px;
    font-weight: 700; font-size: .9rem; text-decoration: none;
    transition: transform .18s, box-shadow .18s;
    box-shadow: 0 4px 14px rgba(245,158,11,.3);
}
.btn-post-new:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color:#fff; }

.empty-state { text-align:center; padding: 4rem 1rem; }
.empty-state .empty-icon { font-size: 4rem; margin-bottom: 1rem; }
.empty-state h4 { color: var(--secondary); font-weight: 700; }
.empty-state p  { color: var(--text-muted); }

.posts-table-wrap {
    background: var(--card-bg);
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}
.posts-table { width: 100%; border-collapse: collapse; }
.posts-table thead th {
    background: var(--primary);
    color: #fff;
    padding: 14px 16px;
    font-size: .82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.posts-table tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
.posts-table tbody tr:last-child { border-bottom: none; }
.posts-table tbody tr:hover { background: rgba(245,158,11,.04); }
.posts-table td { padding: 14px 16px; vertical-align: middle; }

.post-row-title a { font-weight: 700; color: var(--secondary); text-decoration: none; font-size: .9rem; }
.post-row-title a:hover { color: var(--primary); }
.post-row-meta { font-size: .78rem; color: var(--text-muted); margin-top: 3px; }

.view-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fef3c7; color: #92400e;
    border: 1px solid #fde68a; border-radius: 20px;
    padding: 2px 10px; font-size: .75rem; font-weight: 600;
}

.btn-act {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 8px;
    font-size: .95rem; text-decoration: none; transition: all .18s;
}
.btn-act.edit   { background: #dbeafe; color: #1d4ed8; }
.btn-act.del    { background: #fee2e2; color: #dc2626; }
.btn-act.report { background: #f0d5a281; color: #f0a71e; }
.btn-act.edit:hover   { background: #1d4ed8; color: #fff; }
.btn-act.del:hover    { background: #dc2626; color: #fff; }
.btn-act.report:hover { background: #f0a71e; color: #fff; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
