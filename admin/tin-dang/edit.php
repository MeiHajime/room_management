<?php
// admin/tin-dang/edit.php — Sửa tin đăng
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger', 'ID không hợp lệ.'); redirect(BASE_URL . '/admin/tin-dang/index.php'); }

// Load tin đăng + thông tin phòng hiện tại
$tinDang = $db->query(
    "SELECT td.*, p.hinh_anh, p.dia_chi, p.khu_vuc_id,
            kv.tinh_thanh, kv.phuong_xa,
            u.ho_ten as chu_tro
     FROM tin_dang td
     JOIN phong_tro p   ON td.phong_tro_id = p.id
     LEFT JOIN khu_vuc kv ON p.khu_vuc_id  = kv.id
     LEFT JOIN users u    ON p.user_id      = u.id
     WHERE td.id = $id LIMIT 1"
)->fetch_assoc();

if (!$tinDang) { setFlash('danger', 'Không tìm thấy tin đăng.'); redirect(BASE_URL . '/admin/tin-dang/index.php'); }

// Danh sách khu vực (unique)
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
     WHERE p.trang_thai IN ('co_san','da_cho_thue')
     ORDER BY p.khu_vuc_id, p.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$errors = [];
$form   = $tinDang;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, [
        'phong_tro_id' => (int)$_POST['phong_tro_id'],
        'tieu_de'      => trim($_POST['tieu_de'] ?? ''),
        'mo_ta'        => trim($_POST['mo_ta'] ?? ''),
        'gia'          => (float)str_replace(',', '', $_POST['gia'] ?? ''),
        'trang_thai'   => $_POST['trang_thai'] ?? 'cho_duyet',
    ]);

    if ($form['phong_tro_id'] <= 0) $errors[] = 'Vui lòng chọn phòng trọ liên kết.';
    if (empty($form['tieu_de']))    $errors[] = 'Tiêu đề không được để trống.';
    if ($form['gia'] <= 0)          $errors[] = 'Giá thuê phải lớn hơn 0.';

    $valid_statuses = ['cho_duyet', 'da_duyet', 'bi_tu_choi', 'an', 'da_thue'];
    if (!in_array($form['trang_thai'], $valid_statuses)) $form['trang_thai'] = 'cho_duyet';

    $check_room = $db->prepare("SELECT * FROM tin_dang WHERE phong_tro_id = ? AND id != ?");
    $check_room->bind_param('ii', $form['phong_tro_id'], $id);
    $check_room->execute();
    $result = $check_room->get_result();
    if ($result->num_rows > 0) {
        $errors[] = 'Phòng trọ đã được thêm vào tin đăng.';
        $check_room->close();
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare(
            "UPDATE tin_dang SET
                phong_tro_id = ?, tieu_de = ?, mo_ta = ?, gia = ?,
                trang_thai = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param('issdsi',
            $form['phong_tro_id'],
            $form['tieu_de'],
            $form['mo_ta'],
            $form['gia'],
            $form['trang_thai'],
            $id
        );
        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', 'Cập nhật tin đăng thành công!');
            redirect(BASE_URL . '/admin/tin-dang/index.php');
        } else {
            $errors[] = 'Lỗi khi lưu: ' . $db->error;
            $stmt->close();
        }
    }
}

// Xác định tỉnh/phường hiện tại để pre-select selector
$current_tinh  = $tinDang['tinh_thanh'] ?? '';
$current_khu   = (int)($tinDang['khu_vuc_id'] ?? 0);

$pageTitle = 'Sửa Tin Đăng';
require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-pencil-square"></i></span><?= $pageTitle ?> #<?= $id ?></h1>
    <a href="<?= BASE_URL ?>/admin/tin-dang/index.php" class="btn-admin-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<!-- Thông tin phòng hiện tại -->
<div class="data-card mb-4" style="padding:1rem 1.5rem">
    <div class="d-flex align-items-center gap-3">
        <img src="<?= getImageUrl($form['hinh_anh'] ?? null) ?>"
             style="width:72px;height:56px;object-fit:cover;border-radius:10px;flex-shrink:0" alt="">
        <div>
            <div style="font-size:.78rem;color:var(--admin-muted)">Phòng trọ liên kết hiện tại</div>
            <div style="font-weight:600"><?= e($form['dia_chi'] ?? '') ?></div>
            <div style="font-size:.82rem">
                Khu vực: <strong><?= e($current_tinh) ?><?= $form['phuong_xa'] ? ', ' . e($form['phuong_xa']) : '' ?></strong>
                — Chủ phòng: <strong><?= e($form['chu_tro'] ?? '—') ?></strong>
            </div>
        </div>
        <div style="margin-left:auto"><?= tinDangStatusBadge($form['trang_thai']) ?></div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-form-card">
                <div class="form-section-title">Thông tin tin đăng</div>

                <!-- ── Khu vực cascade ───────────────────────────────────── -->
                <div class="mb-3">
                    <label class="form-label">Khu vực <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select id="sel_tinh" class="form-select">
                                <option value="">-- Chọn Tỉnh/TP --</option>
                                <?php
                                $tinh_list = array_unique(array_column($khu_vuc_list, 'tinh_thanh'));
                                foreach ($tinh_list as $tinh): ?>
                                <option value="<?= e($tinh) ?>"
                                    <?= $tinh === $current_tinh ? 'selected' : '' ?>>
                                    <?= e($tinh) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select id="sel_phuong" class="form-select">
                                <option value="">-- Chọn Phường/Xã (tuỳ chọn) --</option>
                                <?php foreach ($khu_vuc_list as $kv): ?>
                                <option value="<?= $kv['id'] ?>"
                                        data-tinh="<?= e($kv['tinh_thanh']) ?>"
                                        <?= $kv['id'] == $current_khu ? 'selected' : '' ?>>
                                    <?= e($kv['phuong_xa']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-text">Chọn khu vực để lọc danh sách phòng bên dưới.</div>
                </div>

                <!-- ── Phòng trọ liên kết ────────────────────────────────── -->
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
                    <div class="form-text" id="phong-hint">Hãy chọn Tỉnh/TP để lọc danh sách phòng.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tiêu đề tin đăng <span class="text-danger">*</span></label>
                    <input type="text" name="tieu_de" class="form-control"
                           value="<?= e($form['tieu_de'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="mo_ta" class="form-control" rows="5"><?= e($form['mo_ta'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Giá thuê</div>
                <div class="mb-3">
                    <label class="form-label">Giá thuê (đ/tháng) <span class="text-danger">*</span></label>
                    <input type="number" name="gia" class="form-control"
                           value="<?= $form['gia'] ?>" min="0" step="100000" required>
                </div>
                <div>
                    <label class="form-label">Lượt xem</label>
                    <input type="text" class="form-control"
                           value="<?= number_format($form['luot_xem'] ?? 0) ?>" readonly disabled>
                </div>
            </div>

            <div class="admin-form-card">
                <div class="form-section-title">Trạng thái</div>
                <select name="trang_thai" class="form-select mb-3">
                    <option value="cho_duyet"  <?= $form['trang_thai'] === 'cho_duyet'  ? 'selected' : '' ?>>⏳ Chờ duyệt</option>
                    <option value="da_duyet"   <?= $form['trang_thai'] === 'da_duyet'   ? 'selected' : '' ?>>✅ Đã duyệt</option>
                    <option value="bi_tu_choi" <?= $form['trang_thai'] === 'bi_tu_choi' ? 'selected' : '' ?>>❌ Từ chối</option>
                    <option value="an"         <?= $form['trang_thai'] === 'an'         ? 'selected' : '' ?>>🙈 Ẩn</option>
                    <option value="da_thue"    <?= $form['trang_thai'] === 'da_thue'    ? 'selected' : '' ?>>🏠 Đã thuê</option>
                </select>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn-admin-primary flex-fill justify-content-center">
                        <i class="bi bi-save"></i> Lưu thay đổi
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

function khuIdsOfTinh(tinh) {
    return allPhuong
        .filter(o => o.value && (!tinh || o.dataset.tinh === tinh))
        .map(o => o.value);
}

// Lọc Phường/Xã theo Tỉnh/TP
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

// Lọc phòng trọ theo Tỉnh (bắt buộc) và Phường/Xã (tuỳ chọn)
function filterPhong() {
    const tinh    = selTinh.value;
    const khuId   = selPhuong.value;
    const current = selPhong.value;
    selPhong.innerHTML = '';

    if (!tinh) {
        selPhong.innerHTML = '<option value="">-- Chọn Tỉnh/TP trước --</option>';
        hint.textContent   = 'Chọn Tỉnh/TP để xem danh sách phòng.';
        return;
    }

    const validIds = new Set(khuIdsOfTinh(tinh));
    const matched  = allPhong.filter(opt => {
        if (!opt.value) return false;
        if (!validIds.has(opt.dataset.khu)) return false;
        if (khuId && opt.dataset.khu !== khuId) return false;
        return true;
    });

    if (matched.length === 0) {
        selPhong.innerHTML = '<option value="">Không có phòng nào phù hợp</option>';
        hint.textContent   = khuId
            ? 'Phường/Xã này chưa có phòng trọ nào.' : 'Tỉnh này chưa có phòng trọ nào.';
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
    selPhuong.value = ''; // reset phường khi đổi tỉnh
    filterPhuong();
});
selPhuong.addEventListener('change', filterPhong);

filterPhuong(); // Khởi tạo — tự chọn lại tỉnh/phường hiện tại và filter phòng
</script>
