<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$errors = [];
$form   = ['username' => '', 'email' => '', 'ho_ten' => '', 'so_dien_thoai' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'username'      => trim($_POST['username'] ?? ''),
        'email'         => trim($_POST['email'] ?? ''),
        'ho_ten'        => trim($_POST['ho_ten'] ?? ''),
        'so_dien_thoai' => trim($_POST['so_dien_thoai'] ?? ''),
    ];
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validate
    if (empty($form['ho_ten']))        $errors[] = 'Họ tên không được để trống.';
    if (strlen($form['username']) < 4) $errors[] = 'Tên đăng nhập phải ít nhất 4 ký tự.';
    if (!preg_match('/^\w+$/', $form['username'])) $errors[] = 'Tên đăng nhập chỉ chứa chữ cái, số và dấu gạch dưới.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (strlen($password) < 6)         $errors[] = 'Mật khẩu phải ít nhất 6 ký tự.';
    if ($password !== $password2)      $errors[] = 'Xác nhận mật khẩu không khớp.';

    if (empty($errors)) {
        $db = getDB();
        // Kiểm tra trùng username/email
        $chk = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param('ss', $form['username'], $form['email']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Tên đăng nhập hoặc email đã được sử dụng.';
        }
        $chk->close();
    }

    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "INSERT INTO users (username, email, password, ho_ten, so_dien_thoai, role, trang_thai)
             VALUES (?, ?, ?, ?, ?, 'user', 'active')"
        );
        $stmt->bind_param('sssss',
            $form['username'], $form['email'], $hash,
            $form['ho_ten'],   $form['so_dien_thoai']
        );
        if ($stmt->execute()) {
            setFlash('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
            redirect(BASE_URL . '/auth/login.php');
        } else {
            $errors[] = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký — VinhRooms</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px">
        <div class="auth-logo">
            <a href="<?= BASE_URL ?>/index.php" class="logo-icon">🏠</a>
            <h1 class="auth-title">Tạo tài khoản</h1>
            <p class="auth-subtitle">Đăng ký miễn phí tại VinhRooms</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label-custom">Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" name="ho_ten" class="form-control-custom"
                           value="<?= e($form['ho_ten']) ?>" placeholder="Nguyễn Văn A" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control-custom"
                           value="<?= e($form['username']) ?>" placeholder="nguyen_van_a" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Số điện thoại</label>
                    <input type="tel" name="so_dien_thoai" class="form-control-custom"
                           value="<?= e($form['so_dien_thoai']) ?>" placeholder="0912345678">
                </div>
                <div class="col-12">
                    <label class="form-label-custom">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control-custom"
                           value="<?= e($form['email']) ?>" placeholder="email@example.com" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control-custom"
                           placeholder="Ít nhất 6 ký tự" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" name="password2" class="form-control-custom"
                           placeholder="Nhập lại mật khẩu" required>
                </div>
                <div class="col-12">
                    <div class="g-recaptcha" data-sitekey="6LfE9ussAAAAAMopn_3oblIQkG6Ts-d7eE73N2Na"></div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-person-check me-2"></i>Tạo tài khoản
                    </button>
                </div>
            </div>
        </form>

        <hr class="my-3">
        <div class="text-center" style="font-size:.875rem">
            Đã có tài khoản?
            <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--primary);font-weight:700">Đăng nhập</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script></body>
</html>
