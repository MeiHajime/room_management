<?php
// my-posts/create.php — Đăng tin phòng trọ mới
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$errors = [];

// Lấy dữ liệu lookup
$categories   = $db->query("SELECT * FROM loai_phong ORDER BY ten")->fetch_all(MYSQLI_ASSOC);
$cities       = $db->query("SELECT DISTINCT tinh_thanh FROM khu_vuc ORDER BY tinh_thanh")->fetch_all(MYSQLI_ASSOC);
$khu_vuc_list = [];
foreach ($db->query("SELECT * FROM khu_vuc ORDER BY tinh_thanh, phuong_xa") as $kv) {
    $khu_vuc_list[$kv['tinh_thanh']][] = $kv;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- Lấy & validate ----
    $tieu_de      = trim($_POST['tieu_de'] ?? '');
    $loai_id      = (int)($_POST['loai_phong_id'] ?? 0);
    $khu_vuc_id   = (int)($_POST['khu_vuc_id'] ?? 0);
    $dia_chi      = trim($_POST['dia_chi'] ?? '');
    $gia          = (float)($_POST['gia'] ?? 0);
    $dien_tich    = (float)($_POST['dien_tich'] ?? 0);
    $so_pn        = (int)($_POST['so_phong_ngu'] ?? 0);
    $so_wc        = (int)($_POST['so_wc'] ?? 0);
    $tien_nghi    = trim($_POST['tien_nghi'] ?? '');
    $mo_ta        = trim($_POST['mo_ta'] ?? '');

    if ($tieu_de === '')    $errors[] = 'Tiêu đề không được bỏ trống.';
    if ($loai_id <= 0)      $errors[] = 'Vui lòng chọn loại phòng.';
    if ($khu_vuc_id <= 0)   $errors[] = 'Vui lòng chọn khu vực (tỉnh/phường).';
    if ($dia_chi === '')    $errors[] = 'Địa chỉ không được bỏ trống.';
    if ($gia <= 0)          $errors[] = 'Giá phải lớn hơn 0.';
    if ($dien_tich <= 0)   $errors[] = 'Diện tích phải lớn hơn 0.';
    if ($so_pn < 0)         $errors[] = 'Số phòng ngủ không hợp lệ.';
    if ($so_wc < 0)         $errors[] = 'Số WC không hợp lệ.';

    // Upload ảnh đại diện
    $hinh_anh = '';
    if (!empty($_FILES['hinh_anh']['name'])) {
        $hinh_anh = uploadImage($_FILES['hinh_anh'], 'room');
        if (!$hinh_anh) $errors[] = 'Ảnh không hợp lệ (chỉ jpg/png/webp, tối đa 5MB).';
    }

    if (empty($errors)) {
        // Bước 1: Insert vào phong_tro
        $stmt = $db->prepare(
            "INSERT INTO phong_tro
             (user_id, loai_phong_id, khu_vuc_id, dia_chi, gia_goc, dien_tich,
              so_phong_ngu, so_wc, tien_nghi, hinh_anh, trang_thai, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'co_san', NOW(), NOW())"
        );
        $stmt->bind_param('iiisdiisss',
            $userId, $loai_id, $khu_vuc_id,
            $dia_chi, $gia, $dien_tich,
            $so_pn, $so_wc, $tien_nghi, $hinh_anh
        );
        if (!$stmt->execute()) {
            $errors[] = 'Lỗi khi lưu thông tin phòng.';
            $stmt->close();
        } else {
            $phong_id = $db->insert_id;
            $stmt->close();

            // Bước 2: Lưu ảnh liên quan vào hinh_anh_phong
            if (!empty($_FILES['hinh_lien_quan']['name'][0])) {
                $thu_tu = 1;
                foreach ($_FILES['hinh_lien_quan']['name'] as $k => $fname) {
                    if (!$fname) continue;
                    $single = [
                        'name'     => $fname,
                        'type'     => $_FILES['hinh_lien_quan']['type'][$k],
                        'tmp_name' => $_FILES['hinh_lien_quan']['tmp_name'][$k],
                        'error'    => $_FILES['hinh_lien_quan']['error'][$k],
                        'size'     => $_FILES['hinh_lien_quan']['size'][$k],
                    ];
                    $res = uploadImage($single, 'room');
                    if ($res) {
                        $sl = $db->prepare("INSERT INTO hinh_anh_phong (phong_id, duong_dan, thu_tu) VALUES (?,?,?)");
                        $sl->bind_param('isi', $phong_id, $res, $thu_tu);
                        $sl->execute();
                        $sl->close();
                        $thu_tu++;
                    }
                }
            }

            // Bước 3: Insert vào tin_dang
            $stmt2 = $db->prepare(
                "INSERT INTO tin_dang
                 (phong_tro_id, tieu_de, mo_ta, gia, trang_thai, luot_xem, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'cho_duyet', 0, NOW(), NOW())"
            );
            $stmt2->bind_param('issd', $phong_id, $tieu_de, $mo_ta, $gia);
            if ($stmt2->execute()) {
                $stmt2->close();
                setFlash('success', 'Đăng tin thành công! Tin đang chờ quản trị viên duyệt.');
                redirect(BASE_URL . '/my-posts/index.php');
            }
            $errors[] = 'Lỗi khi tạo tin đăng.';
            $stmt2->close();
        }
    }
}

// ✅ Include header SAU khi tất cả redirect xử lý xong
$pageTitle = 'Đăng tin mới';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:860px">
    <?php renderFlash(); ?>

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?= BASE_URL ?>/my-posts/index.php" class="btn-back"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="section-title mb-0">Đăng <span>tin mới</span></h1>
            <p class="section-subtitle mt-1">Tin sẽ được duyệt trước khi hiển thị công khai</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="post-form">

        <div class="form-card">
            <div class="form-card-title"><i class="bi bi-info-circle me-2 text-warning"></i>Thông tin cơ bản</div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label-custom">Tiêu đề <span class="required">*</span></label>
                    <input type="text" name="tieu_de" class="form-control form-control-custom"
                           placeholder="VD: Phòng trọ đẹp, có nội thất, gần ĐH Vinh"
                           value="<?= e($_POST['tieu_de'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Loại phòng <span class="required">*</span></label>
                    <select name="loai_phong_id" class="form-select form-control-custom" required>
                        <option value="">-- Chọn loại phòng --</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_POST['loai_phong_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['ten']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Tỉnh / Thành phố <span class="required">*</span></label>
                    <select id="citySelect" class="form-select form-control-custom">
                        <option value="">-- Chọn tỉnh/thành phố --</option>
                        <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c['tinh_thanh']) ?>">
                            <?= e($c['tinh_thanh']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Khu vực / Phường-Xã <span class="required">*</span></label>
                    <select name="khu_vuc_id" id="districtSelect" class="form-select form-control-custom" required>
                        <option value="">-- Chọn tỉnh trước --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Địa chỉ cụ thể <span class="required">*</span></label>
                    <input type="text" name="dia_chi" class="form-control form-control-custom"
                           placeholder="Số nhà, tên đường, tổ dân phố..."
                           value="<?= e($_POST['dia_chi'] ?? '') ?>" required>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-title"><i class="bi bi-rulers me-2 text-warning"></i>Chi tiết phòng</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label-custom">Giá thuê (đ/tháng) <span class="required">*</span></label>
                    <input type="number" name="gia" class="form-control form-control-custom"
                           placeholder="VD: 2000000" min="0" step="100000"
                           value="<?= $_POST['gia'] ?? '' ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Diện tích (m²) <span class="required">*</span></label>
                    <input type="number" name="dien_tich" class="form-control form-control-custom"
                           placeholder="VD: 25" min="1" step="0.5"
                           value="<?= $_POST['dien_tich'] ?? '' ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom">Phòng ngủ</label>
                    <input type="number" name="so_phong_ngu" class="form-control form-control-custom"
                           min="0" value="<?= $_POST['so_phong_ngu'] ?? 1 ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom">WC</label>
                    <input type="number" name="so_wc" class="form-control form-control-custom"
                           min="0" value="<?= $_POST['so_wc'] ?? 1 ?>">
                </div>
                <div class="col-12">
                    <label class="form-label-custom">Tiện nghi (phân cách bằng dấu phẩy)</label>
                    <input type="text" name="tien_nghi" class="form-control form-control-custom"
                           placeholder="VD: Điều hòa, Nóng lạnh, Wifi, Giường, Tủ"
                           value="<?= e($_POST['tien_nghi'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label-custom">Mô tả chi tiết</label>
                    <textarea name="mo_ta" class="form-control form-control-custom" rows="5"
                              placeholder="Mô tả thêm về phòng trọ..."><?= e($_POST['mo_ta'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="form-card">
        <!-- ── Ảnh đại diện ──────────────────────────────────────────── -->
            <div class="admin-form-card mb-4">
                <div class="form-section-title">Ảnh đại diện <small class="text-muted fw-normal">(1 ảnh)</small></div>
                <img id="avatarPreview" src="" alt=""
                     style="display:none;width:100%;border-radius:10px;max-height:220px;object-fit:cover;margin-bottom:.75rem">
                <input type="file" name="hinh_anh" class="form-control" accept="image/*" id="avatarUpload">
                <div class="form-text">JPG/PNG/WEBP, tối đa 5MB.</div>
            </div>

            <!-- ── Ảnh liên quan ─────────────────────────────────────────── -->
            <div class="admin-form-card">
                <div class="form-section-title">Ảnh liên quan <small class="text-muted fw-normal">(nhiều ảnh)</small></div>
                <input type="file" name="hinh_lien_quan[]" class="form-control" accept="image/*"
                       id="extraUpload" multiple>
                <div class="form-text">Chọn nhiều ảnh cùng lúc (JPG/PNG/WEBP, mỗi ảnh tối đa 5MB).</div>
                <div class="row g-2 mt-2" id="extraPreviewGrid"></div>
            </div>
        </div>
        <div class="d-flex gap-3 justify-content-end">
            <a href="<?= BASE_URL ?>/my-posts/index.php" class="btn-cancel">Hủy</a>
            <button type="submit" class="btn-submit">
                <i class="bi bi-send me-2"></i>Đăng tin
            </button>
        </div>
    </form>
</div>

<!-- Cascade khu_vuc từ DB -->
<script>
const kvData    = <?= json_encode($khu_vuc_list, JSON_UNESCAPED_UNICODE) ?>;
const savedKvId = <?= (int)($_POST['khu_vuc_id'] ?? 0) ?>;

function populateDistricts(city, selectedId) {
    const sel = document.getElementById('districtSelect');
    sel.innerHTML = '<option value="">-- Chọn khu vực --</option>';
    if (city && kvData[city]) {
        kvData[city].forEach(kv => {
            const opt = document.createElement('option');
            opt.value = kv.id;
            opt.textContent = kv.phuong_xa;
            if (parseInt(kv.id) === selectedId) opt.selected = true;
            sel.appendChild(opt);
        });
    }
}

document.getElementById('citySelect').addEventListener('change', function () {
    populateDistricts(this.value, 0);
});

if (savedKvId) {
    for (const [tinh, list] of Object.entries(kvData)) {
        if (list.some(kv => parseInt(kv.id) === savedKvId)) {
            document.getElementById('citySelect').value = tinh;
            populateDistricts(tinh, savedKvId);
            break;
        }
    }
}

// ── Preview ảnh đại diện ──
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

// ── Ảnh liên quan: tích lũy file + preview lưới ──
const extraInput = document.getElementById('extraUpload');
const extraGrid  = document.getElementById('extraPreviewGrid');
let   accFiles   = new DataTransfer(); // bộ tích lũy file

extraInput.addEventListener('change', function () {
    // Cộng dồn file mới vào accFiles
    Array.from(this.files).forEach(f => accFiles.items.add(f));
    this.files = accFiles.files; // gán lại vào input để submit form gửi đủ
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
                            border:2px dashed #6366f1;opacity:.9">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
