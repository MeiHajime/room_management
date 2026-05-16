<?php
// admin/bao-cao-da-thue/index.php — Quản lý Báo cáo Đã Thuê
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

// ─── Xử lý action (duyệt / từ chối) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id     = (int)$_POST['id'];
    $action = $_POST['action'];

    // Lấy thông tin báo cáo + tin đăng + chủ phòng
    $report = $db->query(
        "SELECT bc.*, td.phong_tro_id, td.tieu_de as tieu_de_tin,
                p.user_id as owner_id, p.dia_chi
         FROM bao_cao_da_thue bc
         JOIN tin_dang td ON bc.tin_dang_id = td.id
         JOIN phong_tro p  ON td.phong_tro_id = p.id
         WHERE bc.id = $id LIMIT 1"
    )->fetch_assoc();

    if ($report) {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE bao_cao_da_thue SET trang_thai='da_duyet', updated_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $db->prepare("UPDATE tin_dang SET trang_thai='an', updated_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $report['tin_dang_id']); $stmt->execute(); $stmt->close();
            $stmt = $db->prepare("UPDATE phong_tro SET trang_thai='da_cho_thue', updated_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $report['phong_tro_id']); $stmt->execute(); $stmt->close();
            $noi_dung = "Báo cáo đã thuê của bạn cho tin \"".$report['tieu_de_tin']."\" đã được xác nhận. Tin đăng đã chuyển sang Đã thuê.";
            sendNotification($db, null, $report['owner_id'], 'Báo cáo đã thuê được xác nhận', $noi_dung, 'success', BASE_URL.'/my-posts/index.php');
            setFlash('success', 'Đã duyệt báo cáo. Tin đăng đánh dấu "Đã thuê".');

        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE bao_cao_da_thue SET trang_thai='bi_tu_choi', updated_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $noi_dung = "Báo cáo của bạn cho tin \"".$report['tieu_de_tin']."\" đã bị từ chối.";
            sendNotification($db, null, $report['owner_id'], 'Báo cáo đã thuê bị từ chối', $noi_dung, 'danger', BASE_URL.'/my-posts/index.php');
            setFlash('warning', 'Đã từ chối báo cáo.');

        } elseif ($action === 'reopen') {
            // Duyệt yêu cầu mở lại: đặt tin về cho_duyet, xóa báo cáo cũ
            $stmt = $db->prepare("UPDATE tin_dang SET trang_thai='da_duyet', updated_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $report['tin_dang_id']); $stmt->execute(); $stmt->close();
            $stmt = $db->prepare("DELETE FROM bao_cao_da_thue WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $db->prepare("UPDATE phong_tro SET trang_thai='co_san', updated_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $report['phong_tro_id']); $stmt->execute(); $stmt->close();
            $noi_dung = "Yêu cầu mở lại tin \"".$report['tieu_de_tin']."\" đã được Admin chấp thuận.";
            sendNotification($db, null, $report['owner_id'], 'Yêu cầu mở lại tin được chấp thuận', $noi_dung, 'success', BASE_URL.'/my-posts/index.php');
            setFlash('success', 'Đã mở lại tin đăng.');

        } elseif ($action === 'deny_reopen') {
            // Từ chối yêu cầu mở lại
            $stmt = $db->prepare("UPDATE bao_cao_da_thue SET ghi_chu=CONCAT(IFNULL(ghi_chu,''), ' [Từ chối mở lại]'), updated_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $noi_dung = "Yêu cầu mở lại tin \"".$report['tieu_de_tin']."\" đã bị từ chối.";
            sendNotification($db, null, $report['owner_id'], 'Yêu cầu mở lại tin bị từ chối', $noi_dung, 'warning', BASE_URL.'/my-posts/index.php');
            setFlash('warning', 'Đã từ chối yêu cầu mở lại.');
        }
    }

    redirect(BASE_URL . '/admin/bao-cao-da-thue/index.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

/**
 * Gửi thông báo vào bảng thong_bao
 * @param mysqli $db
 * @param int|null $nguoi_gui_id  NULL = hệ thống
 * @param int $nguoi_nhan_id      Người nhận
 * @param string $tieu_de         Tiêu đề ngắn
 * @param string $noi_dung        Nội dung chi tiết
 * @param string $loai            info | success | warning | danger
 * @param string|null $url        Đường dẫn liên quan (tuỳ chọn)
 */
function sendNotification(
    mysqli $db,
    ?int   $nguoi_gui_id,
    int    $nguoi_nhan_id,
    string $tieu_de,
    string $noi_dung,
    string $loai = 'info',
    ?string $url = null
): void {
    // Tạo bảng nếu chưa tồn tại (fail-safe)
    $db->query("CREATE TABLE IF NOT EXISTS thong_bao (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nguoi_gui_id  INT UNSIGNED DEFAULT NULL,
        nguoi_nhan_id INT UNSIGNED NOT NULL,
        tieu_de       VARCHAR(255) NOT NULL,
        noi_dung      TEXT NOT NULL,
        loai          ENUM('info','success','warning','danger') DEFAULT 'info',
        url           VARCHAR(500) DEFAULT NULL,
        da_doc        TINYINT(1) DEFAULT 0,
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tb_nguoi_nhan (nguoi_nhan_id),
        INDEX idx_tb_nguoi_gui  (nguoi_gui_id),
        INDEX idx_tb_da_doc     (da_doc)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $db->prepare(
        "INSERT INTO thong_bao (nguoi_gui_id, nguoi_nhan_id, tieu_de, noi_dung, loai, url, da_doc, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 0, NOW())"
    );
    $stmt->bind_param('iissss', $nguoi_gui_id, $nguoi_nhan_id, $tieu_de, $noi_dung, $loai, $url);
    $stmt->execute();
    $stmt->close();
}

// ─── Filter + Pagination ────────────────────────────────────────────────────
$status   = $_GET['status'] ?? '';
$search   = trim($_GET['q'] ?? '');
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where  = ['1=1'];
$params = [];
$types  = '';

$valid_statuses = ['cho_duyet', 'da_duyet', 'bi_tu_choi'];
// Đếm báo cáo có yêu cầu mở lại (ghi_chu chứa 'Yêu cầu mở lại' và trang_thai = da_duyet)
$cnt_reopen = (int)$db->query("SELECT COUNT(*) FROM bao_cao_da_thue WHERE trang_thai='da_duyet' AND ghi_chu LIKE '%Yêu cầu mở lại%'")->fetch_row()[0];
if (in_array($status, $valid_statuses)) {
    $where[]  = "bc.trang_thai = ?";
    $params[] = $status;
    $types   .= 's';
}

if ($search !== '') {
    $where[]  = "(u.ho_ten LIKE ? OR u.username LIKE ? OR td.tieu_de LIKE ?)";
    $kw = "%$search%";
    $params   = array_merge($params, [$kw, $kw, $kw]);
    $types   .= 'sss';
}

$whereSQL  = implode(' AND ', $where);
$baseQuery = "FROM bao_cao_da_thue bc
              JOIN tin_dang td ON bc.tin_dang_id = td.id
              JOIN phong_tro p  ON td.phong_tro_id = p.id
              JOIN users u      ON bc.user_id = u.id
              LEFT JOIN users ou ON p.user_id = ou.id
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

$sql = "SELECT bc.*,
               td.tieu_de as tieu_de_tin, td.trang_thai as trang_thai_tin,
               td.gia as gia_tin,
               p.dia_chi, p.hinh_anh,
               u.ho_ten as nguoi_bao_cao, u.username as username_bao_cao,
               ou.ho_ten as chu_phong, ou.email as email_chu_phong
        $baseQuery ORDER BY bc.created_at DESC LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Stats
$stats_raw = $db->query("SELECT trang_thai, COUNT(*) as cnt FROM bao_cao_da_thue GROUP BY trang_thai")->fetch_all(MYSQLI_ASSOC);
$stats = array_column($stats_raw, 'cnt', 'trang_thai');

$baseUrl = BASE_URL . '/admin/bao-cao-da-thue/index.php?' . http_build_query(array_filter(['status' => $status, 'q' => $search]));

$pageTitle = 'Báo cáo Đã Thuê';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title">
        <span class="icon"><i class="bi bi-house-check"></i></span><?= $pageTitle ?>
        <?php if (($stats['cho_duyet'] ?? 0) > 0): ?>
        <span class="badge bg-danger ms-2" style="font-size:.7rem;vertical-align:middle">
            <?= $stats['cho_duyet'] ?> chờ
        </span>
        <?php endif; ?>
    </h1>
</div>

<!-- STAT CARDS -->
<div class="stat-grid mb-4" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
    <div class="stat-card" style="cursor:pointer" onclick="location.href='?status='">
        <div class="stat-card-icon orange"><i class="bi bi-file-text"></i></div>
        <div>
            <div class="stat-card-label">Tổng báo cáo</div>
            <div class="stat-card-value"><?= array_sum($stats) ?></div>
        </div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="location.href='?status=cho_duyet'">
        <div class="stat-card-icon blue"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="stat-card-label">Chờ duyệt</div>
            <div class="stat-card-value" style="color:<?= ($stats['cho_duyet'] ?? 0) > 0 ? '#ef4444' : 'inherit' ?>">
                <?= $stats['cho_duyet'] ?? 0 ?>
            </div>
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
</div>

<!-- FILTER -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <form method="GET" action="" class="search-bar">
            <input type="text" name="q" class="form-control"
                   placeholder="🔍 Tìm theo tên người báo cáo, tiêu đề tin..."
                   value="<?= e($search) ?>" style="min-width:240px">
            <select name="status" class="form-control" style="width:auto">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="cho_duyet"  <?= $status === 'cho_duyet'  ? 'selected' : '' ?>>⏳ Chờ duyệt</option>
                <option value="da_duyet"   <?= $status === 'da_duyet'   ? 'selected' : '' ?>>✅ Đã duyệt</option>
                <option value="bi_tu_choi" <?= $status === 'bi_tu_choi' ? 'selected' : '' ?>>❌ Từ chối</option>
            </select>
            <button type="submit" class="btn-search"><i class="bi bi-search"></i> Tìm</button>
            <a href="<?= BASE_URL ?>/admin/bao-cao-da-thue/index.php" class="btn-admin-secondary">
                <i class="bi bi-x"></i> Xóa lọc
            </a>
        </form>
        <div style="font-size:.85rem;color:var(--admin-muted)">
            Tổng: <strong><?= $total ?></strong> báo cáo
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
                    <th>Tin đăng</th>
                    <th>Chủ phòng</th>
                    <th>Người báo cáo</th>
                    <th>Ghi chú</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th width="160">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                    Không có báo cáo nào
                </td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr class="<?= $r['trang_thai'] === 'cho_duyet' ? 'table-warning-row' : '' ?>">
                    <td><?= $r['id'] ?></td>
                    <td>
                        <img src="<?= getImageUrl($r['hinh_anh']) ?>"
                             class="room-thumb" alt=""
                             onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                    </td>
                    <td style="max-width:180px">
                        <div style="font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"
                             title="<?= e($r['tieu_de_tin'] ?? '') ?>">
                            <?= e($r['tieu_de_tin'] ?? '—') ?>
                        </div>
                        <div style="font-size:.75rem;color:var(--admin-muted)">
                            <?= e($r['dia_chi'] ?? '') ?>
                        </div>
                        <div style="font-size:.75rem;margin-top:2px">
                            <?= tinDangStatusBadge($r['trang_thai_tin'] ?? 'cho_duyet') ?>
                            <span style="color:var(--admin-primary);font-weight:700">
                                <?= formatPrice((float)($r['gia_tin'] ?? 0)) ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600"><?= e($r['chu_phong'] ?? '—') ?></div>
                        <div style="font-size:.75rem;color:var(--admin-muted)"><?= e($r['email_chu_phong'] ?? '') ?></div>
                    </td>
                    <td>
                        <div style="font-weight:600"><?= e($r['nguoi_bao_cao'] ?? '—') ?></div>
                        <div style="font-size:.75rem;color:var(--admin-muted)">@<?= e($r['username_bao_cao'] ?? '') ?></div>
                    </td>
                    <td style="max-width:150px">
                        <?php if ($r['ghi_chu']): ?>
                        <span style="font-size:.82rem;color:var(--admin-muted)"
                              title="<?= e($r['ghi_chu']) ?>">
                            <?= e(mb_substr($r['ghi_chu'], 0, 60)) ?><?= mb_strlen($r['ghi_chu']) > 60 ? '…' : '' ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:.78rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $badge_map = [
                            'cho_duyet'  => ['warning', '⏳ Chờ duyệt'],
                            'da_duyet'   => ['success', '✅ Đã duyệt'],
                            'bi_tu_choi' => ['danger',  '❌ Từ chối'],
                        ];
                        [$color, $label] = $badge_map[$r['trang_thai']] ?? ['secondary', $r['trang_thai']];
                        echo "<span class=\"badge bg-{$color}\">{$label}</span>";
                        ?>
                    </td>
                    <td style="white-space:nowrap;font-size:.82rem"><?= formatDateTime($r['created_at'] ?? '') ?></td>
                    <td>
                        <?php if ($r['trang_thai'] === 'cho_duyet' && strpos($r['ghi_chu'] ?? '', 'Yêu cầu mở lại') === false): ?>
                        <div class="d-flex gap-1 flex-column">
                            <form method="POST" style="margin:0" onsubmit="return confirm('Duyệt báo cáo? Tin sẽ bị ẩn và phòng sẽ ở trạng thái đã cho thuê')">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn-action approve w-100" style="display:flex;align-items:center;gap:4px;justify-content:center;padding:4px 10px">
                                    <i class="bi bi-check-circle"></i> Duyệt
                                </button>
                            </form>
                            <form method="POST" style="margin:0" onsubmit="return confirm('Từ chối báo cáo?')">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-action hide w-100" style="display:flex;align-items:center;gap:4px;justify-content:center;padding:4px 10px">
                                    <i class="bi bi-x-circle"></i> Từ chối
                                </button>
                            </form>
                        </div>
                        <?php elseif (strpos($r['ghi_chu'] ?? '', 'Yêu cầu mở lại') !== false): ?>
                        <!-- Có yêu cầu mở lại -->
                        <div class="d-flex gap-1 flex-column">
                            <div style="font-size:.75rem;color:#039300;font-weight:600;margin-bottom:2px">
                                <i class="bi bi-arrow-repeat"></i> Xin mở lại
                            </div>
                            <form method="POST" style="margin:0" onsubmit="return confirm('Chấp thuận mở lại tin? Tin sẽ được mở và phòng sẽ ở trạng thái còn phòng.')">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="reopen">
                                <button type="submit" class="btn-action approve w-100" style="display:flex;align-items:center;gap:4px;justify-content:center;padding:4px 10px;background:#d2ffd1">
                                    <i class="bi bi-unlock"></i> Mở lại
                                </button>
                            </form>
                            <form method="POST" style="margin:0" onsubmit="return confirm('Từ chối yêu cầu mở lại?')">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="deny_reopen">
                                <button type="submit" class="btn-action hide w-100" style="display:flex;align-items:center;gap:4px;justify-content:center;padding:4px 10px">
                                    <i class="bi bi-x-circle"></i> Từ chối
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span style="font-size:.78rem;color:var(--admin-muted)">
                            <?= $r['trang_thai'] === 'da_duyet' ? '✅ Đã xử lý' : '❌ Đã từ chối' ?>
                        </span>
                        <?php endif; ?>
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

<style>
.table-warning-row { background: rgba(245, 158, 11, 0.05); }
.table-warning-row:hover { background: rgba(245, 158, 11, 0.1); }
</style>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
