<?php
// profile.php — Trang thông tin cá nhân
$pageTitle = 'Thông Tin Cá Nhân';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$db          = getDB();
$currentUser = getCurrentUser();
$uid         = (int)$_SESSION['user_id'];

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'update_info';

    if ($action === 'update_info') {
        $ho_ten        = trim($_POST['ho_ten'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
        $dia_chi       = trim($_POST['dia_chi'] ?? '');

        if (empty($ho_ten))                              $errors[] = 'Họ tên không được để trống.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';

        if (empty($errors)) {
            // Kiểm tra email trùng
            $chk = $db->prepare("SELECT id FROM users WHERE email=? AND id!=? LIMIT 1");
            $chk->bind_param('si', $email, $uid);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) $errors[] = 'Email đã được sử dụng bởi tài khoản khác.';
            $chk->close();
        }

        if (empty($errors)) {
            // Xử lý upload avatar — dùng uploadImage() lưu vào uploads/
            $avatarPath = $currentUser['avatar']; // giữ nguyên nếu không chọn ảnh mới
            $avatarFile = $_FILES['avatar'] ?? null;
            if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK && $avatarFile['size'] > 0) {
                $uploaded = uploadImage($avatarFile, 'avatar');
                if ($uploaded === false) {
                    $errors[] = 'Avatar phải là file ảnh hợp lệ (JPG, PNG, GIF, WEBP) và nhỏ hơn 5MB.';
                } else {
                    $avatarPath = $uploaded; // chỉ lưu tên file thuần
                }
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE users SET ho_ten=?, email=?, so_dien_thoai=?, dia_chi=?, avatar=? WHERE id=?");
            $stmt->bind_param('sssssi', $ho_ten, $email, $so_dien_thoai, $dia_chi, $avatarPath, $uid);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $ho_ten;
                setFlash('success', 'Cập nhật thông tin thành công!');
                redirect(BASE_URL . '/profile.php');
            } else {
                $errors[] = 'Lỗi khi lưu dữ liệu.';
            }
            $stmt->close();
        }
    }

    if ($action === 'change_password') {
        $old_pwd  = $_POST['old_password'] ?? '';
        $new_pwd  = $_POST['new_password'] ?? '';
        $new_pwd2 = $_POST['new_password2'] ?? '';

        if (!password_verify($old_pwd, $currentUser['password'])) $errors[] = 'Mật khẩu hiện tại không đúng.';
        if (strlen($new_pwd) < 6)                                  $errors[] = 'Mật khẩu mới phải ít nhất 6 ký tự.';
        if ($new_pwd !== $new_pwd2)                                $errors[] = 'Xác nhận mật khẩu mới không khớp.';

        if (empty($errors)) {
            $hash = password_hash($new_pwd, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $uid);
            if ($stmt->execute()) {
                setFlash('success', 'Đổi mật khẩu thành công!');
                redirect(BASE_URL . '/profile.php');
            } else {
                $errors[] = 'Lỗi khi đổi mật khẩu.';
            }
            $stmt->close();
        }
    }
}

// Lấy lại currentUser sau khi update
$currentUser = getCurrentUser();

// Tin đăng của user
$myRooms = $db->query(
    "SELECT td.id, td.tieu_de, td.gia, td.trang_thai, td.created_at, p.hinh_anh
     FROM phong_tro p
     join tin_dang td on td.phong_tro_id = p.id
     WHERE user_id=$uid
     ORDER BY created_at DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <?php renderFlash(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile card -->
        <div class="col-lg-4">
            <div class="detail-info-card text-center mb-4">
                <div class="avatar-wrapper">
                    <img id="avatarPreview" src="<?= getImageUrl($currentUser['avatar'] ?? '') ?>" class="avatar-img">

                    <label for="upload-avatar" class="avatar-overlay">
                        <i class="bi bi-image"></i>
                    </label>

                    <input type="file" id="upload-avatar" hidden>
                </div>
                <h5 style="font-weight:800"><?= e($currentUser['ho_ten']) ?></h5>
                <p style="color:var(--text-muted);font-size:.875rem">@<?= e($currentUser['username']) ?></p>
                <?= userStatusBadge($currentUser['trang_thai']) ?>

                <hr class="my-3">
                <div class="d-flex justify-content-around text-center">
                    <div>
                        <div style="font-size:1.5rem;font-weight:800;color:var(--primary)"><?= count($myRooms) ?></div>
                        <div style="font-size:.78rem;color:var(--text-muted)">Tin đăng</div>
                    </div>
                    <div>
                        <div style="font-size:1rem;font-weight:700">
                            <?= $currentUser['role'] === 'admin' ? '👑 Admin' : '👤 User' ?>
                        </div>
                        <div style="font-size:.78rem;color:var(--text-muted)">Vai trò</div>
                    </div>
                </div>
                <hr class="my-3">
                <div style="font-size:.82rem;color:var(--text-muted);text-align:left">
                    <?php if ($currentUser['email']): ?>
                    <div class="mb-1"><i class="bi bi-envelope me-2 text-warning"></i><?= e($currentUser['email']) ?></div>
                    <?php endif; ?>
                    <?php if ($currentUser['so_dien_thoai']): ?>
                    <div class="mb-1"><i class="bi bi-telephone me-2 text-warning"></i><?= e($currentUser['so_dien_thoai']) ?></div>
                    <?php endif; ?>
                    <?php if ($currentUser['dia_chi']): ?>
                    <div><i class="bi bi-geo-alt me-2 text-warning"></i><?= e($currentUser['dia_chi']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mt-3" style="font-size:.78rem;color:var(--text-muted)">
                    Tham gia: <?= formatDate($currentUser['created_at']) ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Form cập nhật thông tin -->
            <div class="detail-info-card mb-4">
                <h6 style="font-weight:800;margin-bottom:1.25rem">
                    <i class="bi bi-person-gear me-2 text-warning"></i>Cập nhật thông tin
                </h6>
                <form method="POST" enctype="multipart/form-data" id="formUpdateInfo">
                    <input type="hidden" name="_action" value="update_info">
                    <input type="file" name="avatar" id="avatarFileInput" accept="image/*" hidden>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="ho_ten" class="form-control mt-1"
                                   value="<?= e($currentUser['ho_ten']) ?>" required
                                   style="border:2px solid var(--border);border-radius:10px;padding:.65rem 1rem">
                        </div>
                        <div class="col-md-6">
                            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control mt-1"
                                   value="<?= e($currentUser['email']) ?>" required
                                   style="border:2px solid var(--border);border-radius:10px;padding:.65rem 1rem">
                        </div>
                        <div class="col-md-6">
                            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">Số điện thoại</label>
                            <input type="tel" name="so_dien_thoai" class="form-control mt-1"
                                   value="<?= e($currentUser['so_dien_thoai'] ?? '') ?>"
                                   style="border:2px solid var(--border);border-radius:10px;padding:.65rem 1rem">
                        </div>
                        <div class="col-md-6">
                            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">Địa chỉ</label>
                            <input type="text" name="dia_chi" class="form-control mt-1"
                                   value="<?= e($currentUser['dia_chi'] ?? '') ?>"
                                   style="border:2px solid var(--border);border-radius:10px;padding:.65rem 1rem">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning fw-bold px-4"
                                    style="border-radius:10px;padding:.65rem 1.5rem">
                                <i class="bi bi-save me-2"></i>Lưu thông tin
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Form đổi mật khẩu -->
            <div class="detail-info-card mb-4">
                <h6 style="font-weight:800;margin-bottom:1.25rem">
                    <i class="bi bi-key me-2 text-warning"></i>Đổi mật khẩu
                </h6>
                <form method="POST">
                    <input type="hidden" name="_action" value="change_password">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">Mật khẩu hiện tại</label>
                            <input type="password" name="old_password" class="form-control mt-1" required
                                   style="border:2px solid var(--border);border-radius:10px;padding:.65rem 1rem">
                        </div>
                        <div class="col-md-4">
                            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control mt-1" required
                                   placeholder="Ít nhất 6 ký tự"
                                   style="border:2px solid var(--border);border-radius:10px;padding:.65rem 1rem">
                        </div>
                        <div class="col-md-4">
                            <label style="font-size:.85rem;font-weight:600;color:var(--text-muted)">Xác nhận mật khẩu</label>
                            <input type="password" name="new_password2" class="form-control mt-1" required
                                   style="border:2px solid var(--border);border-radius:10px;padding:.65rem 1rem">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-warning fw-semibold px-4"
                                    style="border-radius:10px;padding:.65rem 1.5rem;border-width:2px">
                                <i class="bi bi-lock me-2"></i>Đổi mật khẩu
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tin đăng của tôi -->
            <div class="detail-info-card">
                <h6 style="font-weight:800;margin-bottom:1.25rem">
                    <i class="bi bi-house-door me-2 text-warning"></i>Tin đăng của tôi
                </h6>
                <?php if (empty($myRooms)): ?>
                    <p class="text-muted text-center py-3">Bạn chưa có tin đăng nào.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="font-size:.78rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid var(--border)">
                                <th style="padding:.65rem 1rem">Ảnh</th>
                                <th style="padding:.65rem 1rem">Tiêu đề</th>
                                <th style="padding:.65rem 1rem">Giá</th>
                                <th style="padding:.65rem 1rem">Trạng thái</th>
                                <th style="padding:.65rem 1rem">Ngày đăng</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($myRooms as $r): ?>
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:.65rem 1rem">
                                <img src="<?= getImageUrl($r['hinh_anh']) ?>" alt=""
                                     style="width:56px;height:42px;object-fit:cover;border-radius:8px">
                            </td>
                            <td style="padding:.65rem 1rem;font-size:.875rem">
                                <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $r['id'] ?>"
                                   style="font-weight:600;color:var(--secondary);display:block;max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    <?= e($r['tieu_de']) ?>
                                </a>
                            </td>
                            <td style="padding:.65rem 1rem;font-weight:700;color:var(--primary);white-space:nowrap;font-size:.875rem">
                                <?= formatPrice($r['gia']) ?>
                            </td>
                            <td style="padding:.65rem 1rem"><?= tinDangStatusBadge($r['trang_thai']) ?></td>
                            <td style="padding:.65rem 1rem;font-size:.82rem;color:var(--text-muted);white-space:nowrap">
                                <?= formatDate($r['created_at']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<style>
    .avatar-wrapper {
    position: relative;
    left: 37%;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
}

/* Avatar */
.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Overlay (ẩn mặc định) */
.avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;

    display: flex;
    justify-content: center;
    align-items: center;

    background: rgba(113, 113, 113, 0.57);
    opacity: 0;
    transition: 0.3s ease;
}
.avatar-overlay i{
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
}

/* Hover trigger */
.avatar-wrapper:hover .avatar-overlay {
    cursor: pointer;
    opacity: 1;
}
</style>
<div id="avatarToast" style="
    display:none;
    position:fixed;
    bottom:1.5rem;
    right:1.5rem;
    z-index:9999;
    background:#ef4444;
    color:#fff;
    padding:.75rem 1.25rem;
    border-radius:12px;
    font-size:.875rem;
    font-weight:600;
    box-shadow:0 4px 20px rgba(0,0,0,.25);
    animation: slideIn .3s ease;
"><i class="bi bi-exclamation-circle me-2"></i><span id="avatarToastMsg"></span></div>

<style>
@keyframes slideIn {
    from { opacity:0; transform:translateY(12px); }
    to   { opacity:1; transform:translateY(0); }
}
</style>

<script>
const ALLOWED_TYPES = ['image/jpeg','image/png','image/gif','image/webp'];
const ALLOWED_EXT  = /\.(jpe?g|png|gif|webp)$/i;

function showAvatarError(msg) {
    const toast = document.getElementById('avatarToast');
    document.getElementById('avatarToastMsg').textContent = msg;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 4000);
}

// Khi click label overlay => mở file picker ngoài (upload-avatar)
// Nhưng file thực sự nằm trong form là avatarFileInput
document.getElementById('upload-avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Validate kiểu file
    if (!ALLOWED_TYPES.includes(file.type) || !ALLOWED_EXT.test(file.name)) {
        showAvatarError('Chỉ chấp nhận file ảnh: JPG, PNG, GIF, WEBP.');
        this.value = '';
        return;
    }

    // Hiển thị preview ngay lập tức
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('avatarPreview').src = ev.target.result;
    };
    reader.readAsDataURL(file);

    // Đồng bộ file sang input trong form để submit cùng
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('avatarFileInput').files = dt.files;
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
