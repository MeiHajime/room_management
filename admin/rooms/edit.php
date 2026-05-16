<?php
// admin/rooms/edit.php — Sửa phòng trọ
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('danger', 'ID không hợp lệ.'); redirect(BASE_URL . '/admin/rooms/index.php'); }

$room = $db->query("SELECT * FROM phong_tro WHERE id = $id LIMIT 1")->fetch_assoc();
if (!$room) { setFlash('danger', 'Không tìm thấy phòng.'); redirect(BASE_URL . '/admin/rooms/index.php'); }

$categories  = $db->query("SELECT * FROM loai_phong ORDER BY ten")->fetch_all(MYSQLI_ASSOC);
$users       = $db->query("SELECT id, ho_ten, username FROM users WHERE role='user' ORDER BY ho_ten")->fetch_all(MYSQLI_ASSOC);
$khuvuc_list = $db->query("SELECT * FROM khu_vuc ORDER BY tinh_thanh, phuong_xa")->fetch_all(MYSQLI_ASSOC);
$extra_imgs  = $db->query("SELECT * FROM hinh_anh_phong WHERE phong_id = $id ORDER BY thu_tu, id")->fetch_all(MYSQLI_ASSOC);

// Build map: tinh_thanh => [khu_vuc rows]
$kv_by_tinh = [];
foreach ($khuvuc_list as $kv) {
    $kv_by_tinh[$kv['tinh_thanh']][] = $kv;
}

// Lấy tỉnh của phòng hiện tại
$curKV = null;
if (!empty($room['khu_vuc_id'])) {
    $curKV = $db->query("SELECT * FROM khu_vuc WHERE id = {$room['khu_vuc_id']} LIMIT 1")->fetch_assoc();
}

$errors = [];
$form   = $room;

// ── Xóa ảnh phụ ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_img_id'])) {
    $del_id  = (int)$_POST['delete_img_id'];
    $del_row = $db->query("SELECT duong_dan FROM hinh_anh_phong WHERE id=$del_id AND phong_id=$id LIMIT 1")->fetch_assoc();
    if ($del_row) {
        $path = ASSETS_IMAGE_DIR . basename($del_row['duong_dan']);
        if (file_exists($path)) @unlink($path);
        $db->query("DELETE FROM hinh_anh_phong WHERE id=$del_id");
        setFlash('success', 'Đã xóa ảnh.');
    }
    redirect(BASE_URL . "/admin/rooms/edit.php?id=$id");
}

// ── Lưu form chính ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, [
        'user_id'       => (int)$_POST['user_id'],
        'loai_phong_id' => $_POST['loai_phong_id'] ? (int)$_POST['loai_phong_id'] : null,
        'khu_vuc_id'    => (int)($_POST['khu_vuc_id'] ?? 0),
        'dia_chi'       => trim($_POST['dia_chi'] ?? ''),
        'gia_goc'       => (float)$_POST['gia_goc'],
        'dien_tich'     => (float)$_POST['dien_tich'],
        'so_phong_ngu'  => (int)$_POST['so_phong_ngu'],
        'so_wc'         => (int)$_POST['so_wc'],
        'tien_nghi'     => trim($_POST['tien_nghi'] ?? ''),
        'trang_thai'    => $_POST['trang_thai'],
    ]);

    if (empty($form['dia_chi']))             $errors[] = 'Địa chỉ không được để trống.';
    if ($form['gia_goc'] <= 0)               $errors[] = 'Giá thuê phải lớn hơn 0.';
    if ($form['dien_tich'] <= 0)             $errors[] = 'Diện tích phải lớn hơn 0.';
    if (($form['khu_vuc_id'] ?? 0) <= 0)     $errors[] = 'Vui lòng chọn khu vực.';

    // ── Upload ảnh đại diện (1 ảnh → assets/images/) ────────────────────────
    if (!empty($_FILES['hinh_anh']['name'])) {
        $res = uploadToAssets($_FILES['hinh_anh'], 'room');
        if ($res) {
            // Xóa ảnh cũ nếu là file trong assets/images/
            if ($room['hinh_anh']) {
                $old = ASSETS_IMAGE_DIR . basename($room['hinh_anh']);
                if (file_exists($old)) @unlink($old);
            }
            $form['hinh_anh'] = '/assets/images/' . $res;
        } else {
            $errors[] = 'Ảnh đại diện không hợp lệ (JPG/PNG/WEBP, tối đa 5MB).';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            "UPDATE phong_tro SET
                user_id=?, loai_phong_id=?, khu_vuc_id=?, dia_chi=?,
                gia_goc=?, dien_tich=?, so_phong_ngu=?, so_wc=?,
                tien_nghi=?, hinh_anh=?, trang_thai=?
             WHERE id=?"
        );
        $stmt->bind_param(
            'iisssddiissi',
            $form['user_id'], $form['loai_phong_id'], $form['khu_vuc_id'],
            $form['dia_chi'],
            $form['gia_goc'], $form['dien_tich'],
            $form['so_phong_ngu'], $form['so_wc'],
            $form['tien_nghi'], $form['hinh_anh'],
            $form['trang_thai'], $id
        );
        $stmt->execute();
        $stmt->close();

        // ── Upload nhiều ảnh liên quan → assets/images/ ─────────────────────
        if (!empty($_FILES['hinh_lien_quan']['name'][0])) {
            $thu_tu_max = (int)($db->query("SELECT MAX(thu_tu) FROM hinh_anh_phong WHERE phong_id=$id")->fetch_row()[0] ?? 0);
            foreach ($_FILES['hinh_lien_quan']['name'] as $k => $fname) {
                if (!$fname) continue;
                $single = [
                    'name'     => $fname,
                    'type'     => $_FILES['hinh_lien_quan']['type'][$k],
                    'tmp_name' => $_FILES['hinh_lien_quan']['tmp_name'][$k],
                    'error'    => $_FILES['hinh_lien_quan']['error'][$k],
                    'size'     => $_FILES['hinh_lien_quan']['size'][$k],
                ];
                $res = uploadToAssets($single, 'room');
                if ($res) {
                    $thu_tu_max++;
                    $duong_dan = '/assets/images/' . $res;
                    $s2 = $db->prepare("INSERT INTO hinh_anh_phong (phong_id, duong_dan, thu_tu) VALUES (?,?,?)");
                    $s2->bind_param('isi', $id, $duong_dan, $thu_tu_max);
                    $s2->execute();
                    $s2->close();
                }
            }
        }

        setFlash('success', 'Cập nhật phòng trọ thành công!');
        redirect(BASE_URL . '/admin/rooms/index.php');
    }

    // Reload extra imgs sau khi lưu lỗi
    $extra_imgs = $db->query("SELECT * FROM hinh_anh_phong WHERE phong_id = $id ORDER BY thu_tu, id")->fetch_all(MYSQLI_ASSOC);
}

// ── Helper: upload vào assets/images/ ───────────────────────────────────────
function uploadToAssets(array $file, string $prefix = 'room'): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) return false;
    if ($file['size'] > 5 * 1024 * 1024) return false;
    if (!is_dir(ASSETS_IMAGE_DIR)) mkdir(ASSETS_IMAGE_DIR, 0755, true);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], ASSETS_IMAGE_DIR . $filename) ? $filename : false;
}

$pageTitle = 'Sửa Phòng Trọ';
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="page-header">
    <h1 class="page-title"><span class="icon"><i class="bi bi-pencil-square"></i></span><?= $pageTitle ?> #<?= $id ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $id ?>" class="btn-action view" target="_blank">
            <i class="bi bi-eye"></i> Xem trước
        </a>
        <a href="<?= BASE_URL ?>/admin/rooms/index.php" class="btn-admin-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <!-- ── Cột trái ─────────────────────────────────────────────────── -->
        <div class="col-lg-8">
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Thông tin cơ bản</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Chủ phòng <span class="text-danger">*</span></label>
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
                    <input type="text" name="dia_chi" class="form-control" value="<?= e($form['dia_chi']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Khu vực <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select id="sel_tinh" class="form-select" required>
                                <option value="">-- Chọn Tỉnh/TP --</option>
                                <?php foreach (array_keys($kv_by_tinh) as $tinh): ?>
                                <option value="<?= e($tinh) ?>" <?= ($curKV && $curKV['tinh_thanh'] === $tinh) ? 'selected' : '' ?>>
                                    <?= e($tinh) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select id="sel_phuong" name="khu_vuc_id" class="form-select" required>
                                <option value="">-- Chọn Phường/Xã --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tiện nghi</label>
                    <input type="text" name="tien_nghi" class="form-control"
                           value="<?= e($form['tien_nghi'] ?? '') ?>"
                           placeholder="Điều hòa, Tủ lạnh, WC riêng ...">
                </div>
            </div>

            <!-- ── Ảnh đại diện ──────────────────────────────────────────── -->
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Ảnh đại diện <small class="text-muted fw-normal">(1 ảnh)</small></div>
                <?php if ($form['hinh_anh']): ?>
                <img id="avatarPreview" src="<?= getImageUrl($form['hinh_anh']) ?>"
                     style="width:100%;border-radius:10px;max-height:220px;object-fit:cover;margin-bottom:.75rem">
                <?php else: ?>
                <img id="avatarPreview" src="" style="display:none;width:100%;border-radius:10px;max-height:220px;object-fit:cover;margin-bottom:.75rem">
                <?php endif; ?>
                <input type="file" name="hinh_anh" class="form-control" accept="image/*" id="avatarUpload">
                <div class="form-text">JPG/PNG/WEBP, tối đa 5MB. Để trống nếu không đổi.</div>
            </div>

            <!-- ── Ảnh liên quan ─────────────────────────────────────────── -->
            <div class="admin-form-card">
                <div class="form-section-title">Ảnh liên quan <small class="text-muted fw-normal">(nhiều ảnh)</small></div>

                <!-- Grid ảnh hiện có -->
                <?php if (!empty($extra_imgs)): ?>
                <div class="row g-2 mb-3" id="extraImgGrid">
                    <?php foreach ($extra_imgs as $img): ?>
                    <div class="col-4 col-md-3 position-relative" id="img-wrap-<?= $img['id'] ?>">
                        <img src="<?= getImageUrl($img['duong_dan']) ?>"
                             style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                        <!-- Nút xóa -->
                        <form method="POST" style="margin:0"
                              onsubmit="return confirm('Xóa ảnh này?')">
                            <input type="hidden" name="delete_img_id" value="<?= $img['id'] ?>">
                            <button type="submit"
                                    style="position:absolute;top:4px;right:8px;background:rgba(239,68,68,.85);
                                           color:#fff;border:none;border-radius:50%;width:22px;height:22px;
                                           font-size:.7rem;cursor:pointer;line-height:1;padding:0"
                                    title="Xóa ảnh này">✕</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted small mb-3"><i class="bi bi-image me-1"></i>Chưa có ảnh liên quan.</p>
                <?php endif; ?>

                <!-- Upload thêm ảnh mới -->
                <input type="file" name="hinh_lien_quan[]" class="form-control" accept="image/*"
                       id="extraUpload" multiple>
                <div class="form-text">Chọn nhiều ảnh cùng lúc (JPG/PNG/WEBP, mỗi ảnh tối đa 5MB).</div>

                <!-- Preview ảnh mới chọn -->
                <div class="row g-2 mt-2" id="extraPreviewGrid"></div>
            </div>
        </div>

        <!-- ── Cột phải ──────────────────────────────────────────────────── -->
        <div class="col-lg-4">
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Giá & Diện tích</div>
                <div class="mb-3">
                    <label class="form-label">Giá thuê (đ/tháng) <span class="text-danger">*</span></label>
                    <input type="number" name="gia_goc" class="form-control"
                           value="<?= $form['gia_goc'] ?>" min="0" step="100000" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                    <input type="number" name="dien_tich" class="form-control"
                           value="<?= $form['dien_tich'] ?>" min="1" step="0.5" required>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Phòng ngủ</label>
                        <input type="number" name="so_phong_ngu" class="form-control"
                               value="<?= $form['so_phong_ngu'] ?>" min="1" max="10">
                    </div>
                    <div class="col-6">
                        <label class="form-label">WC</label>
                        <input type="number" name="so_wc" class="form-control"
                               value="<?= $form['so_wc'] ?>" min="1" max="10">
                    </div>
                </div>
            </div>

            <div class="admin-form-card">
                <div class="form-section-title">Trạng thái</div>
                <select name="trang_thai" class="form-select mb-3">
                    <option value="co_san"       <?= $form['trang_thai'] === 'co_san'       ? 'selected' : '' ?>>✅ Còn phòng</option>
                    <option value="da_cho_thue"  <?= $form['trang_thai'] === 'da_cho_thue'  ? 'selected' : '' ?>>🔴 Đã cho thuê</option>
                </select>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-admin-primary flex-fill justify-content-center">
                        <i class="bi bi-save"></i> Lưu thay đổi
                    </button>
                    <a href="<?= BASE_URL ?>/admin/rooms/index.php" class="btn-admin-secondary">Hủy</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Cascade Tỉnh/TP → Phường/Xã từ bảng khu_vuc
const kvByTinh  = <?= json_encode($kv_by_tinh, JSON_UNESCAPED_UNICODE) ?>;
const savedKvId = <?= (int)($form['khu_vuc_id'] ?? 0) ?>;

const selTinh   = document.getElementById('sel_tinh');
const selPhuong = document.getElementById('sel_phuong');

function filterPhuong(selectedId) {
    const tinh = selTinh.value;
    selPhuong.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
    if (tinh && kvByTinh[tinh]) {
        kvByTinh[tinh].forEach(kv => {
            const opt = document.createElement('option');
            opt.value = kv.id;
            opt.textContent = kv.phuong_xa;
            if (parseInt(kv.id) === selectedId) opt.selected = true;
            selPhuong.appendChild(opt);
        });
    }
}

selTinh.addEventListener('change', () => filterPhuong(0));

// Init khi load trang — hiển thị phường/xã theo tỉnh hiện tại của phòng
if (selTinh.value) filterPhuong(savedKvId);
</script>

<script>
// Preview ảnh đại diện
document.getElementById('avatarUpload').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('avatarPreview');
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

// Ảnh liên quan: tích lũy file + preview lưới
const extraInput = document.getElementById('extraUpload');
const extraGrid  = document.getElementById('extraPreviewGrid');
let   accFiles   = new DataTransfer();

extraInput.addEventListener('change', function () {
    Array.from(this.files).forEach(f => accFiles.items.add(f));
    this.files = accFiles.files;
    renderExtraGrid();
});

function renderExtraGrid() {
    extraGrid.innerHTML = '';
    Array.from(accFiles.files).forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement('div');
            col.className = 'col-4 col-md-3 position-relative';
            col.innerHTML = `
                <img src="${e.target.result}"
                     style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;
                            border:2px dashed var(--admin-primary);opacity:.85">
                <button type="button" onclick="removeExtra(${idx})"
                        style="position:absolute;top:4px;right:8px;background:rgba(239,68,68,.85);
                               color:#fff;border:none;border-radius:50%;width:22px;height:22px;
                               font-size:.7rem;cursor:pointer;line-height:1;padding:0"
                        title="Xóa">&#x2715;</button>`;
            extraGrid.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
}

function removeExtra(idx) {
    const dt = new DataTransfer();
    Array.from(accFiles.files).forEach((f, i) => { if (i !== idx) dt.items.add(f); });
    accFiles = dt;
    extraInput.files = accFiles.files;
    renderExtraGrid();
}
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
