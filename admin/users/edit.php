<?php
// admin/users/edit.php — Sửa tài khoản + đổi mật khẩu
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

$uid = (int)($_GET['id'] ?? 0);
if (!$uid) { setFlash('danger', 'ID không hợp lệ.'); redirect(BASE_URL . '/admin/users/index.php'); }

$user = $db->query("SELECT * FROM users WHERE id = $uid LIMIT 1")->fetch_assoc();
if (!$user) { setFlash('danger', 'Không tìm thấy tài khoản.'); redirect(BASE_URL . '/admin/users/index.php'); }

$errors  = [];
$success = '';
$form    = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'update_info';

    if ($action === 'update_info') {
        $form = array_merge($form, [
            'ho_ten'        => trim($_POST['ho_ten'] ?? ''),
            'email'         => trim($_POST['email'] ?? ''),
            'so_dien_thoai' => trim($_POST['so_dien_thoai'] ?? ''),
            'dia_chi'       => trim($_POST['dia_chi'] ?? ''),
            'role'          => in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : $user['role'],
            'trang_thai'    => in_array($_POST['trang_thai'] ?? '', ['active', 'inactive', 'banned']) ? $_POST['trang_thai'] : $user['trang_thai'],
        ]);

        if (empty($form['ho_ten'])) $errors[] = 'Họ tên không được để trống.';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';

        // Kiểm tra email trùng với user khác
        $chk = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $chk->bind_param('si', $form['email'], $uid);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'Email đã được sử dụng bởi tài khoản khác.';
        $chk->close();

        if (empty($errors)) {
            $stmt = $db->prepare(
                "UPDATE users SET ho_ten=?, email=?, so_dien_thoai=?, dia_chi=?, role=?, trang_thai=? WHERE id=?"
            );
            $stmt->bind_param('ssssssi',
                $form['ho_ten'], $form['email'],
                $form['so_dien_thoai'], $form['dia_chi'],
                $form['role'], $form['trang_thai'], $uid
            );
            if ($stmt->execute()) {
                setFlash('success', 'Cập nhật thông tin thành công!');
                redirect(BASE_URL . '/admin/users/edit.php?id=' . $uid);
            } else {
                $errors[] = 'Lỗi khi lưu: ' . $db->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'change_password') {
        $new_pwd  = $_POST['new_password'] ?? '';
        $new_pwd2 = $_POST['new_password2'] ?? '';

        if (strlen($new_pwd) < 6) $errors[] = 'Mật khẩu mới phải ít nhất 6 ký tự.';
        if ($new_pwd !== $new_pwd2) $errors[] = 'Xác nhận mật khẩu mới không khớp.';

        if (empty($errors)) {
            $hash = password_hash($new_pwd, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $uid);
            if ($stmt->execute()) {
                setFlash('success', 'Đổi mật khẩu thành công!');
                redirect(BASE_URL . '/admin/users/edit.php?id=' . $uid);
            } else {
                $errors[] = 'Lỗi khi đổi mật khẩu.';
            }
            $stmt->close();
        }
    }
}

// Stats
$roomCount = $db->query("SELECT COUNT(*) FROM phong_tro WHERE user_id=$uid AND trang_thai != 'bi_xoa'")->fetch_row()[0];

$pageTitle = 'Sửa Tài Khoản';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-person-gear"></i></span><?= $pageTitle ?>: <?= e($user['ho_ten']) ?></h1>
    <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn-admin-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- User info card -->
    <div class="col-lg-4">
        <div class="admin-form-card text-center mb-4">
            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--admin-primary),#ea580c);display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin:0 auto 1rem">
                <?= strtoupper(mb_substr($user['ho_ten'] ?? 'U', 0, 1)) ?>
            </div>
            <div style="font-size:1.2rem;font-weight:800"><?= e($user['ho_ten']) ?></div>
            <div style="font-size:.85rem;color:var(--admin-muted)">@<?= e($user['username']) ?></div>
            <div class="mt-2"><?= userStatusBadge($user['trang_thai']) ?></div>

            <hr class="my-3">
            <div class="d-flex justify-content-around">
                <div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--admin-primary)"><?= $roomCount ?></div>
                    <div style="font-size:.78rem;color:var(--admin-muted)">Tin đăng</div>
                </div>
                <div>
                    <div style="font-size:1rem;font-weight:700"><?= $user['role'] === 'admin' ? '👑 Admin' : '👤 User' ?></div>
                    <div style="font-size:.78rem;color:var(--admin-muted)">Vai trò</div>
                </div>
            </div>
            <hr class="my-3">
            <div style="font-size:.8rem;color:var(--admin-muted)">
                Tham gia: <?= formatDate($user['created_at']) ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- FORM CẬP NHẬT THÔNG TIN -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="_action" value="update_info">
            <div class="admin-form-card">
                <div class="form-section-title">Thông tin tài khoản</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="ho_ten" class="form-control" value="<?= e($form['ho_ten']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" name="so_dien_thoai" class="form-control" value="<?= e($form['so_dien_thoai'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" name="dia_chi" class="form-control" value="<?= e($form['dia_chi'] ?? '') ?>">
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Vai trò</label>
                        <select name="role" class="form-select">
                            <option value="user"  <?= $form['role'] === 'user'  ? 'selected' : '' ?>>👤 User</option>
                            <option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>👑 Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Trạng thái</label>
                        <select name="trang_thai" class="form-select">
                            <option value="active"   <?= $form['trang_thai'] === 'active'   ? 'selected' : '' ?>>✅ Hoạt động</option>
                            <option value="inactive" <?= $form['trang_thai'] === 'inactive' ? 'selected' : '' ?>>⏸ Chưa kích hoạt</option>
                            <option value="banned"   <?= $form['trang_thai'] === 'banned'   ? 'selected' : '' ?>>🚫 Bị khóa</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-admin-primary">
                    <i class="bi bi-save"></i> Lưu thông tin
                </button>
            </div>
        </form>

        <!-- FORM ĐỔI MẬT KHẨU -->
        <form method="POST">
            <input type="hidden" name="_action" value="change_password">
            <div class="admin-form-card">
                <div class="form-section-title">🔒 Đổi mật khẩu</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="Ít nhất 6 ký tự" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="new_password2" class="form-control" placeholder="Nhập lại" required>
                    </div>
                </div>
                <button type="submit" class="btn-admin-secondary">
                    <i class="bi bi-key"></i> Đổi mật khẩu
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
