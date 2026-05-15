<?php
// admin/users/add.php — Thêm tài khoản người dùng
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

$errors = [];
$form   = ['username' => '', 'email' => '', 'ho_ten' => '', 'so_dien_thoai' => '', 'dia_chi' => '', 'role' => 'user', 'trang_thai' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'username'      => trim($_POST['username'] ?? ''),
        'email'         => trim($_POST['email'] ?? ''),
        'ho_ten'        => trim($_POST['ho_ten'] ?? ''),
        'so_dien_thoai' => trim($_POST['so_dien_thoai'] ?? ''),
        'dia_chi'       => trim($_POST['dia_chi'] ?? ''),
        'role'          => in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user',
        'trang_thai'    => in_array($_POST['trang_thai'] ?? '', ['active', 'inactive', 'banned']) ? $_POST['trang_thai'] : 'active',
    ];
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($form['ho_ten']))         $errors[] = 'Họ tên không được để trống.';
    if (strlen($form['username']) < 4)  $errors[] = 'Tên đăng nhập phải ít nhất 4 ký tự.';
    if (!preg_match('/^\w+$/', $form['username'])) $errors[] = 'Tên đăng nhập chỉ chứa chữ cái, số, dấu gạch dưới.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (strlen($password) < 6)          $errors[] = 'Mật khẩu phải ít nhất 6 ký tự.';
    if ($password !== $password2)       $errors[] = 'Xác nhận mật khẩu không khớp.';

    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param('ss', $form['username'], $form['email']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors[] = 'Tên đăng nhập hoặc email đã tồn tại.';
        $chk->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "INSERT INTO users (username, email, password, ho_ten, so_dien_thoai, dia_chi, role, trang_thai)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssssss',
            $form['username'], $form['email'], $hash,
            $form['ho_ten'], $form['so_dien_thoai'], $form['dia_chi'],
            $form['role'], $form['trang_thai']
        );
        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', 'Thêm tài khoản thành công!');
            redirect(BASE_URL . '/admin/users/index.php');
        } else {
            $errors[] = 'Lỗi khi lưu dữ liệu.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Thêm Tài Khoản';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-person-plus"></i></span><?= $pageTitle ?></h1>
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
    <div class="col-lg-8">
        <form method="POST">
            <div class="admin-form-card">
                <div class="form-section-title">Thông tin cá nhân</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="ho_ten" class="form-control" value="<?= e($form['ho_ten']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" value="<?= e($form['username']) ?>" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" name="so_dien_thoai" class="form-control" value="<?= e($form['so_dien_thoai']) ?>" placeholder="09xxxxxxxx">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="dia_chi" class="form-control" value="<?= e($form['dia_chi']) ?>" placeholder="Địa chỉ liên lạc">
                </div>

                <div class="form-section-title">Bảo mật</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Ít nhất 6 ký tự" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password2" class="form-control" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <div class="form-section-title">Phân quyền & Trạng thái</div>
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

                <div class="d-flex gap-2">
                    <button type="submit" class="btn-admin-primary">
                        <i class="bi bi-check-lg"></i> Tạo tài khoản
                    </button>
                    <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn-admin-secondary">Hủy</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tips sidebar -->
    <div class="col-lg-4">
        <div class="admin-form-card">
            <div class="form-section-title">💡 Hướng dẫn</div>
            <ul style="font-size:.875rem;color:var(--admin-muted);line-height:1.8;padding-left:1.25rem">
                <li>Tên đăng nhập phải là duy nhất, chỉ chứa chữ cái, số và dấu <code>_</code></li>
                <li>Mật khẩu tối thiểu 6 ký tự</li>
                <li>Vai trò <strong>Admin</strong> có toàn quyền quản trị hệ thống</li>
                <li>Tài khoản bị khóa (<strong>Banned</strong>) sẽ không thể đăng nhập</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
