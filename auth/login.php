<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

// Nếu đã đăng nhập → redirect
if (isLoggedIn()) {
    redirect(isAdmin() ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT id, username, password, ho_ten, role, trang_thai
             FROM users WHERE username = ? OR email = ? LIMIT 1"
        );
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Tài khoản không tồn tại.';
        } elseif ($user['trang_thai'] === 'banned') {
            $error = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Mật khẩu không đúng.';
        } else {
            // Đăng nhập thành công
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['ho_ten'];
            session_regenerate_id(true);

            setFlash('success', 'Đăng nhập thành công! Chào mừng ' . $user['ho_ten'] . '.');
            redirect($user['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — VinhRooms</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🏠</div>
            <h1 class="auth-title">Đăng nhập</h1>
            <p class="auth-subtitle">Chào mừng trở lại VinhRooms</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'login_required'): ?>
        <div class="alert alert-warning">
            <i class="bi bi-lock me-2"></i>Vui lòng đăng nhập để tiếp tục.
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label-custom" for="username">Tên đăng nhập / Email</label>
                <div style="display:flex;align-items:center;border:2px solid #e2e8f0;border-radius:10px;background:#fff;padding:0 .75rem;gap:.5rem">
                    <i class="bi bi-person" style="color:#94a3b8;font-size:1rem;flex-shrink:0"></i>
                    <input type="text" id="username" name="username"
                           style="border:none;outline:none;flex:1;padding:.65rem 0;font-size:.95rem;background:transparent"
                           value="<?= e($username) ?>" placeholder="admin hoặc user@email.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label-custom" for="password">Mật khẩu</label>
                <div style="display:flex;align-items:center;border:2px solid #e2e8f0;border-radius:10px;background:#fff;padding:0 .75rem;gap:.5rem" id="pwd-wrap">
                    <i class="bi bi-lock" style="color:#94a3b8;font-size:1rem;flex-shrink:0"></i>
                    <input type="password" id="password" name="password"
                           style="border:none;outline:none;flex:1;padding:.65rem 0;font-size:.95rem;background:transparent"
                           placeholder="Nhập mật khẩu" required>
                    <button type="button" id="togglePwd"
                            style="background:none;border:none;color:#94a3b8;cursor:pointer;padding:.2rem;line-height:1;flex-shrink:0">
                        <i class="bi bi-eye" id="eyeIcon" style="font-size:1rem"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
            </button>
        </form>

        <hr class="my-3">

        <div class="text-center" style="font-size:.875rem">
            Chưa có tài khoản?
            <a href="<?= BASE_URL ?>/auth/register.php" style="color:var(--primary);font-weight:700">Đăng ký ngay</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
</body>
</html>
