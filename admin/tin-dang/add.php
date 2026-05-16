<?php
// admin/tin-dang/add.php — Thêm tin đăng mới
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

// Danh sách khu vực (unique, không join)
$khu_vuc_list = $db->query(
    "SELECT id, tinh_thanh, phuong_xa FROM khu_vuc ORDER BY tinh_thanh, phuong_xa"
)->fetch_all(MYSQLI_ASSOC);

// Danh sách phòng trọ kèm khu_vuc_id để JS filter
$phong_list = $db->query(
    "SELECT p.id, p.khu_vuc_id, p.dia_chi, p.gia_goc,
            lp.ten as loai_phong, u.ho_ten
     FROM phong_tro p
     LEFT JOIN khu_vuc kv    ON p.khu_vuc_id     = kv.id
     LEFT JOIN loai_phong lp ON p.loai_phong_id  = lp.id
     LEFT JOIN users u       ON p.user_id         = u.id
     WHERE p.trang_thai = 'co_san'
     ORDER BY p.khu_vuc_id, p.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);


$errors = [];
$form   = [
    'phong_tro_id' => '',
    'tieu_de'      => '',
    'mo_ta'        => '',
    'gia'          => '',
    'trang_thai'   => 'cho_duyet',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'phong_tro_id' => (int)$_POST['phong_tro_id'],
        'tieu_de'      => trim($_POST['tieu_de'] ?? ''),
        'mo_ta'        => trim($_POST['mo_ta'] ?? ''),
        'gia'          => (float)str_replace(',', '', $_POST['gia'] ?? ''),
        'trang_thai'   => $_POST['trang_thai'] ?? 'cho_duyet',
    ];

    // Validate
    if ($form['phong_tro_id'] <= 0) $errors[] = 'Vui lòng chọn phòng trọ liên kết.';
    if (empty($form['tieu_de']))    $errors[] = 'Tiêu đề không được để trống.';
    if ($form['gia'] <= 0)          $errors[] = 'Giá thuê phải lớn hơn 0.';

    $valid_statuses = ['cho_duyet', 'da_duyet', 'bi_tu_choi', 'an', 'da_thue'];
    if (!in_array($form['trang_thai'], $valid_statuses)) $form['trang_thai'] = 'cho_duyet';

    $check_room = $db->prepare("SELECT * FROM tin_dang WHERE phong_tro_id = ?");
    $check_room->bind_param('i', $form['phong_tro_id']);
    $check_room->execute();
    $result = $check_room->get_result();
    if ($result->num_rows > 0) {
        $errors[] = 'Phòng trọ đã được thêm vào tin đăng.';
        $check_room->close();
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            "INSERT INTO tin_dang (phong_tro_id, tieu_de, mo_ta, gia, trang_thai, luot_xem, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())"
        );
        $stmt->bind_param('issds',
            $form['phong_tro_id'],
            $form['tieu_de'],
            $form['mo_ta'],
            $form['gia'],
            $form['trang_thai']
        );
        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', 'Thêm tin đăng thành công!');
            redirect(BASE_URL . '/admin/tin-dang/index.php');
        } else {
            $errors[] = 'Lỗi khi lưu dữ liệu: ' . $db->error;
            $stmt->close();
        }
    }
}

$pageTitle = 'Thêm Tin Đăng';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-plus-circle"></i></span><?= $pageTitle ?></h1>
    <a href="<?= BASE_URL ?>/admin/tin-dang/index.php" class="btn-admin-secondary">
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

<form method="POST">
    <div class="row g-4">
        <!-- Main info -->
        <div class="col-lg-8">
            <div class="admin-form-card">
                <div class="form-section-title">Thông tin tin đăng</div>
                <div class="mb-3">
                    <label class="form-label">Khu vực <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select id="sel_tinh" class="form-select">
                                <option value="">-- Chọn Tỉnh/TP --</option>
                                <?php
                                $tinh_list = array_unique(array_column($khu_vuc_list, 'tinh_thanh'));
                                foreach ($tinh_list as $tinh): ?>
                                <option value="<?= e($tinh) ?>"><?= e($tinh) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select id="sel_phuong" class="form-select">
                                <option value="">-- Chọn Phường/Xã --</option>
                                <?php foreach ($khu_vuc_list as $kv): ?>
                                <option value="<?= $kv['id'] ?>"
                                        data-tinh="<?= e($kv['tinh_thanh']) ?>">
                                    <?= e($kv['phuong_xa']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-text">Chọn khu vực để lọc danh sách phòng bên dưới.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phòng trọ liên kết <span class="text-danger">*</span></label>
                    <select id="sel_phong" name="phong_tro_id" class="form-select" required>
                        <option value="">-- Chọn khu vực trước --</option>
                        <?php foreach ($phong_list as $p): ?>
                        <option value="<?= $p['id'] ?>"
                                data-khu="<?= (int)$p['khu_vuc_id'] ?>"
                                <?= $form['phong_tro_id'] == $p['id'] ? 'selected' : '' ?>>
                            #<?= $p['id'] ?> — <?= e($p['loai_phong'] ?? 'Phòng trọ') ?>
                            (<?= e($p['dia_chi']) ?> | <?= e($p['ho_ten']) ?> | <?= formatPrice($p['gia_goc']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" id="phong-hint">Hãy chọn Phường/Xã để lọc danh sách phòng.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tiêu đề tin đăng <span class="text-danger">*</span></label>
                    <input type="text" name="tieu_de" class="form-control"
                           value="<?= e($form['tieu_de']) ?>"
                           placeholder="VD: Cho thuê phòng trọ 20m² Quận 1 giá rẻ" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="mo_ta" class="form-control" rows="5"
                              placeholder="Mô tả chi tiết về tin đăng..."><?= e($form['mo_ta']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Giá thuê</div>
                <div class="mb-3">
                    <label class="form-label">Giá thuê (đ/tháng) <span class="text-danger">*</span></label>
                    <input type="number" name="gia" class="form-control"
                           value="<?= $form['gia'] ?: '' ?>"
                           placeholder="2500000" min="0" step="100000" required>
                </div>
            </div>

            <div class="admin-form-card">
                <div class="form-section-title">Trạng thái</div>
                <select name="trang_thai" class="form-select mb-3">
                    <option value="cho_duyet" <?= $form['trang_thai'] === 'cho_duyet' ? 'selected' : '' ?>>⏳ Chờ duyệt</option>
                    <option value="da_duyet"  <?= $form['trang_thai'] === 'da_duyet'  ? 'selected' : '' ?>>✅ Đã duyệt</option>
                    <option value="bi_tu_choi"<?= $form['trang_thai'] === 'bi_tu_choi'? 'selected' : '' ?>>❌ Từ chối</option>
                    <option value="an"        <?= $form['trang_thai'] === 'an'        ? 'selected' : '' ?>>🙈 Ẩn</option>
                    <option value="da_thue"   <?= $form['trang_thai'] === 'da_thue'   ? 'selected' : '' ?>>🏠 Đã thuê</option>
                </select>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn-admin-primary flex-fill justify-content-center">
                        <i class="bi bi-check-lg"></i> Lưu tin đăng
                    </button>
                    <a href="<?= BASE_URL ?>/admin/tin-dang/index.php" class="btn-admin-secondary">Hủy</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

<script>
const selTinh   = document.getElementById('sel_tinh');
const selPhuong = document.getElementById('sel_phuong');
const selPhong  = document.getElementById('sel_phong');
const allPhuong = Array.from(selPhuong.options);
const allPhong  = Array.from(selPhong.options);
const hint      = document.getElementById('phong-hint');

// Lấy tập hợp khu_vuc_id thuộc về một tỉnh
function khuIdsOfTinh(tinh) {
    return allPhuong
        .filter(o => o.value && (!tinh || o.dataset.tinh === tinh))
        .map(o => o.value);
}

// ── Bước 1: Lọc Phường/Xã theo Tỉnh/TP ──────────────────────────────────
function filterPhuong() {
    const tinh    = selTinh.value;
    const current = selPhuong.value;
    selPhuong.innerHTML = '<option value="">-- Chọn Phường/Xã (tuỳ chọn) --</option>';
    allPhuong.forEach(opt => {
        if (!opt.value) return;
        if (!tinh || opt.dataset.tinh === tinh) {
            const c = opt.cloneNode(true);
            c.selected = c.value === current;
            selPhuong.appendChild(c);
        }
    });
    filterPhong();
}

// ── Bước 2: Lọc phòng trọ theo Tỉnh (bắt buộc) và Phường/Xã (tuỳ chọn) ─
function filterPhong() {
    const tinh  = selTinh.value;
    const khuId = selPhuong.value;   // có thể rỗng
    const current = selPhong.value;
    selPhong.innerHTML = '';

    if (!tinh) {
        selPhong.innerHTML = '<option value="">-- Chọn Tỉnh/TP trước --</option>';
        hint.textContent   = 'Chọn Tỉnh/TP để xem danh sách phòng.';
        return;
    }

    // Lấy tất cả khu_vuc_id thuộc tỉnh này
    const validIds = new Set(khuIdsOfTinh(tinh));

    // Nếu đã chọn phường/xã cụ thể → chỉ lấy khu_vuc_id đó
    const matched = allPhong.filter(opt => {
        if (!opt.value) return false;
        if (!validIds.has(opt.dataset.khu)) return false;   // không cùng tỉnh
        if (khuId && opt.dataset.khu !== khuId) return false; // khác phường
        return true;
    });

    if (matched.length === 0) {
        selPhong.innerHTML = '<option value="">Không có phòng nào phù hợp</option>';
        hint.textContent   = khuId
            ? 'Phường/Xã này chưa có phòng trọ nào (còn phòng).'
            : 'Tỉnh này chưa có phòng trọ nào (còn phòng).';
        return;
    }

    selPhong.innerHTML = '<option value="">-- Chọn phòng trọ --</option>';
    matched.forEach(opt => {
        const c = opt.cloneNode(true);
        c.selected = c.value === current;
        selPhong.appendChild(c);
    });
    hint.textContent = matched.length + ' phòng'
        + (khuId ? ' ở Phường/Xã này' : ' ở tỉnh này') + '.';
}

selTinh.addEventListener('change', () => {
    // Reset phường về "chưa chọn" khi đổi tỉnh
    selPhuong.value = '';
    filterPhuong();
});
selPhuong.addEventListener('change', filterPhong);

filterPhuong(); // Khởi tạo lần đầu

</script>
