<?php
// admin/rooms/add.php — Thêm phòng trọ mới
// require functions.php TRƯỚC để xử lý redirect() trước khi output HTML
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

$categories = $db->query("SELECT * FROM loai_phong ORDER BY ten")->fetch_all(MYSQLI_ASSOC);
$users      = $db->query("SELECT id, ho_ten, username FROM users WHERE role='user' AND trang_thai='active' ORDER BY ho_ten")->fetch_all(MYSQLI_ASSOC);
$khuvuc_list = $db->query("SELECT * FROM khu_vuc ORDER BY tinh_thanh, phuong_xa")->fetch_all(MYSQLI_ASSOC);

$errors = [];
$form   = [
    'user_id' => '', 'loai_phong_id' => '', 'tieu_de' => '',
    'mo_ta' => '', 'dia_chi' => '', 'khu_vuc_id' => '',
    'gia' => '', 'dien_tich' => '', 'so_phong_ngu' => 1, 'so_wc' => 1,
    'tien_nghi' => '', 'trang_thai' => 'cho_duyet',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'user_id'       => (int)$_POST['user_id'],
        'loai_phong_id' => (int)$_POST['loai_phong_id'],
        'tieu_de'       => trim($_POST['tieu_de'] ?? ''),
        'mo_ta'         => trim($_POST['mo_ta'] ?? ''),
        'dia_chi'       => trim($_POST['dia_chi'] ?? ''),
        'khu_vuc_id'    => (int)($_POST['khu_vuc_id'] ?? 0),
        'gia'           => (float)str_replace(',', '', $_POST['gia'] ?? ''),
        'dien_tich'     => (float)$_POST['dien_tich'],
        'so_phong_ngu'  => (int)$_POST['so_phong_ngu'],
        'so_wc'         => (int)$_POST['so_wc'],
        'tien_nghi'     => trim($_POST['tien_nghi'] ?? ''),
        'trang_thai'    => $_POST['trang_thai'] ?? 'cho_duyet',
    ];

    // Validate
    if (empty($form['tieu_de']))      $errors[] = 'Tiêu đề không được để trống.';
    if (empty($form['dia_chi']))      $errors[] = 'Địa chỉ không được để trống.';
    if ($form['gia'] <= 0)            $errors[] = 'Giá thuê phải lớn hơn 0.';
    if ($form['dien_tich'] <= 0)      $errors[] = 'Diện tích phải lớn hơn 0.';
    if ($form['user_id'] <= 0)        $errors[] = 'Vui lòng chọn người đăng.';
    if ($form['khu_vuc_id'] <= 0)     $errors[] = 'Vui lòng chọn khu vực.';

    // Upload ảnh
    $hinh_anh = null;
    if (!empty($_FILES['hinh_anh']['name'])) {
        $uploaded = uploadImage($_FILES['hinh_anh'], 'room');
        if ($uploaded) {
            $hinh_anh = $uploaded;
        } else {
            $errors[] = 'Ảnh không hợp lệ (JPG/PNG/WEBP, tối đa 5MB).';
        }
    }

    if (empty($errors)) {
        $loai_id   = $form['loai_phong_id'] ?: null;
        $khu_vuc_id = $form['khu_vuc_id'];
        $stmt = $db->prepare(
            "INSERT INTO phong_tro
               (user_id, loai_phong_id, khu_vuc_id, tieu_de, mo_ta, dia_chi,
                gia, dien_tich, so_phong_ngu, so_wc, tien_nghi, hinh_anh, trang_thai)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            'iiisssddiiiiss',
            $form['user_id'], $loai_id, $khu_vuc_id,
            $form['tieu_de'], $form['mo_ta'], $form['dia_chi'],
            $form['gia'], $form['dien_tich'],
            $form['so_phong_ngu'], $form['so_wc'],
            $form['tien_nghi'], $hinh_anh, $form['trang_thai']
        );
        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', 'Thêm phòng trọ thành công!');
            redirect(BASE_URL . '/admin/rooms/index.php');
        } else {
            $errors[] = 'Lỗi khi lưu dữ liệu: ' . $db->error;
            $stmt->close();
        }
    }
}
// Tất cả logic đã xử lý xong → mới output HTML
$pageTitle = 'Thêm Phòng Trọ';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-plus-circle"></i></span><?= $pageTitle ?></h1>
    <a href="<?= BASE_URL ?>/admin/rooms/index.php" class="btn-admin-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?>
        <li><?= e($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <!-- Main info -->
        <div class="col-lg-8">
            <div class="admin-form-card">
                <div class="form-section-title">Thông tin cơ bản</div>

                <div class="mb-3">
                    <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                    <input type="text" name="tieu_de" class="form-control"
                           value="<?= e($form['tieu_de']) ?>" placeholder="VD: Phòng trọ 20m² giá rẻ Quận 1" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Người đăng <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Chọn tài khoản --</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $form['user_id'] == $u['id'] ? 'selected' : '' ?>>
                                <?= e($u['ho_ten']) ?> (<?= e($u['username']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Loại phòng</label>
                        <select name="loai_phong_id" class="form-select">
                            <option value="">-- Chọn loại --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $form['loai_phong_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['ten']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                    <input type="text" name="dia_chi" class="form-control"
                           value="<?= e($form['dia_chi']) ?>" placeholder="Số nhà, tên đường" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Khu vực (Phường/Xã — Tỉnh/TP) <span class="text-danger">*</span></label>
                    <select name="khu_vuc_id" class="form-select" required>
                        <option value="">-- Chọn khu vực --</option>
                        <?php foreach ($khuvuc_list as $kv): ?>
                        <option value="<?= $kv['id'] ?>" <?= $form['khu_vuc_id'] == $kv['id'] ? 'selected' : '' ?>>
                            <?= e($kv['phuong_xa']) ?> — <?= e($kv['tinh_thanh']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Nếu chưa có khu vực phù hợp, hãy thêm vào bảng <code>khu_vuc</code> trong CSDL.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mô tả chi tiết</label>
                    <textarea name="mo_ta" class="form-control" rows="5"
                              placeholder="Mô tả chi tiết về phòng trọ..."><?= e($form['mo_ta']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tiện nghi</label>
                    <input type="text" name="tien_nghi" class="form-control"
                           value="<?= e($form['tien_nghi']) ?>"
                           placeholder="VD: Điều hòa, Tủ lạnh, WC riêng (cách nhau bằng dấu phẩy)">
                    <div class="form-text">Nhập các tiện nghi cách nhau bằng dấu phẩy (,)</div>
                </div>
            </div>
        </div>

        <!-- Sidebar info -->
        <div class="col-lg-4">
            <!-- Giá & diện tích -->
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Giá & Diện tích</div>
                <div class="mb-3">
                    <label class="form-label">Giá thuê (đ/tháng) <span class="text-danger">*</span></label>
                    <input type="number" name="gia" class="form-control"
                           value="<?= $form['gia'] ?: '' ?>" placeholder="2500000" min="0" step="100000" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                    <input type="number" name="dien_tich" class="form-control"
                           value="<?= $form['dien_tich'] ?: '' ?>" placeholder="20" min="1" step="0.5" required>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Phòng ngủ</label>
                        <input type="number" name="so_phong_ngu" class="form-control"
                               value="<?= $form['so_phong_ngu'] ?>" min="1" max="10">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Phòng WC</label>
                        <input type="number" name="so_wc" class="form-control"
                               value="<?= $form['so_wc'] ?>" min="1" max="10">
                    </div>
                </div>
            </div>

            <!-- Ảnh đại diện -->
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Ảnh đại diện</div>
                <input type="file" name="hinh_anh" class="form-control" accept="image/*" id="imgUpload">
                <div class="form-text">JPG, PNG, WEBP — tối đa 5MB</div>
                <img id="imgPreview" src="" alt="" style="display:none;width:100%;border-radius:10px;margin-top:.75rem;max-height:160px;object-fit:cover">
            </div>

            <!-- Trạng thái -->
            <div class="admin-form-card">
                <div class="form-section-title">Trạng thái</div>
                <select name="trang_thai" class="form-select">
                    <option value="cho_duyet" <?= $form['trang_thai'] === 'cho_duyet' ? 'selected' : '' ?>>⏳ Chờ duyệt</option>
                    <option value="da_duyet"  <?= $form['trang_thai'] === 'da_duyet'  ? 'selected' : '' ?>>✅ Đã duyệt</option>
                    <option value="bi_an"     <?= $form['trang_thai'] === 'bi_an'     ? 'selected' : '' ?>>🙈 Ẩn</option>
                </select>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn-admin-primary flex-fill justify-content-center">
                        <i class="bi bi-check-lg"></i> Lưu phòng
                    </button>
                    <a href="<?= BASE_URL ?>/admin/rooms/index.php" class="btn-admin-secondary">
                        Hủy
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('imgUpload').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('imgPreview');
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
