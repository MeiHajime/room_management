<?php
// admin/users/index.php — Quản lý tài khoản người dùng
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

// Xử lý action nhanh
if (isset($_GET['action']) && isset($_GET['id'])) {
    $uid    = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($uid === (int)$_SESSION['user_id']) {
        setFlash('warning', 'Không thể thực hiện thao tác này với tài khoản hiện tại.');
        redirect(BASE_URL . '/admin/users/index.php');
    }

    $status_map = [
        'activate' => 'active',
        'ban'      => 'banned',
        'deactive' => 'inactive',
    ];
    if (isset($status_map[$action])) {
        $new_status = $status_map[$action];
        $stmt = $db->prepare("UPDATE users SET trang_thai = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $uid);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Đã cập nhật trạng thái tài khoản.');
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Đã xóa tài khoản.');
    }
    redirect(BASE_URL . '/admin/users/index.php');
}

// Search / Filter
$search   = trim($_GET['q'] ?? '');
$status   = $_GET['status'] ?? '';
$role     = $_GET['role'] ?? '';
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(username LIKE ? OR ho_ten LIKE ? OR email LIKE ?)";
    $kw = "%$search%";
    $params  = array_merge($params, [$kw, $kw, $kw]);
    $types  .= 'sss';
}
if (in_array($status, ['active', 'inactive', 'banned'])) {
    $where[]  = "trang_thai = ?";
    $params[] = $status;
    $types   .= 's';
}
if (in_array($role, ['admin', 'user'])) {
    $where[]  = "role = ?";
    $params[] = $role;
    $types   .= 's';
}

$whereSQL  = implode(' AND ', $where);
$countSQL  = "SELECT COUNT(*) FROM users WHERE $whereSQL";
$selectSQL = "SELECT * FROM users WHERE $whereSQL ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $db->prepare($countSQL);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
} else {
    $total = $db->query($countSQL)->fetch_row()[0];
}

$pg     = paginate($total, $per_page, $page_num);
$offset = $pg['offset'];
$sql    = $selectSQL . " LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $users = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$baseUrl = BASE_URL . '/admin/users/index.php?' . http_build_query(array_filter(['q' => $search, 'status' => $status, 'role' => $role]));

$pageTitle = 'Quản lý Người Dùng';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-people"></i></span><?= $pageTitle ?></h1>
    <a href="<?= BASE_URL ?>/admin/users/add.php" class="btn-admin-primary">
        <i class="bi bi-person-plus"></i> Thêm tài khoản
    </a>
</div>

<!-- FILTER BAR -->
<div class="data-card mb-4">
    <div class="data-card-header" style="flex-wrap:wrap;gap:.75rem">
        <form method="GET" action="" class="search-bar">
            <input type="text" name="q" class="form-control" placeholder="🔍 Tìm username, họ tên, email..."
                   value="<?= e($search) ?>" style="min-width:240px">
            <select name="status" class="form-control" style="width:auto">
                <option value="">-- Trạng thái --</option>
                <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>✅ Hoạt động</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>⏸ Chưa kích hoạt</option>
                <option value="banned"   <?= $status === 'banned'   ? 'selected' : '' ?>>🚫 Bị khóa</option>
            </select>
            <select name="role" class="form-control" style="width:auto">
                <option value="">-- Vai trò --</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>👑 Admin</option>
                <option value="user"  <?= $role === 'user'  ? 'selected' : '' ?>>👤 User</option>
            </select>
            <button type="submit" class="btn-search"><i class="bi bi-search"></i> Tìm</button>
            <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn-admin-secondary">
                <i class="bi bi-x"></i> Xóa lọc
            </a>
        </form>
        <div style="font-size:.85rem;color:var(--admin-muted)">
            Tổng: <strong><?= $total ?></strong> tài khoản
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="data-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tài khoản</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Tin đăng</th>
                    <th width="200">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u):
                    $roomCount = $db->query("SELECT COUNT(*) FROM phong_tro WHERE user_id = {$u['id']} AND trang_thai != 'bi_xoa'")->fetch_row()[0];
                    $isSelf = ((int)$u['id'] === (int)$_SESSION['user_id']);
                ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar-sm">
                                <?= strtoupper(mb_substr($u['ho_ten'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:700"><?= e($u['ho_ten']) ?></div>
                                <div style="font-size:.78rem;color:var(--admin-muted)">@<?= e($u['username']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.875rem"><?= e($u['email']) ?></td>
                    <td style="font-size:.875rem"><?= e($u['so_dien_thoai'] ?? '—') ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge" style="background:#7c3aed;color:#fff">👑 Admin</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border">👤 User</span>
                        <?php endif; ?>
                    </td>
                    <td><?= userStatusBadge($u['trang_thai']) ?></td>
                    <td style="font-size:.82rem;white-space:nowrap"><?= formatDate($u['created_at']) ?></td>
                    <td style="text-align:center">
                        <a href="<?= BASE_URL ?>/admin/rooms/index.php?q=<?= urlencode($u['ho_ten']) ?>"
                           style="font-weight:700;color:var(--admin-primary)"><?= $roomCount ?></a>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="<?= BASE_URL ?>/admin/users/edit.php?id=<?= $u['id'] ?>" class="btn-action edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (!$isSelf): ?>
                                <?php if ($u['trang_thai'] === 'active'): ?>
                                <a href="?action=ban&id=<?= $u['id'] ?>" class="btn-action hide confirm-delete" title="Khóa tài khoản">
                                    <i class="bi bi-lock"></i>
                                </a>
                                <?php else: ?>
                                <a href="?action=activate&id=<?= $u['id'] ?>" class="btn-action approve" title="Mở khóa">
                                    <i class="bi bi-unlock"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <a href="?action=delete&id=<?= $u['id'] ?>" class="btn-action delete confirm-delete" title="Xóa">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:.78rem;color:var(--admin-muted)">(Bạn)</span>
                            <?php endif; ?>
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

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
