<?php
// my-posts/edit.php — Chỉnh sửa tin đăng (chỉ chính chủ)
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);

if (!$id) redirect(BASE_URL . '/my-posts/index.php');

// Lấy tin — JOIN để verify quyền và lấy đủ dữ liệu
$room = $db->query("
    SELECT td.id AS tin_id, td.tieu_de, td.mo_ta, td.gia, td.trang_thai AS tin_trang_thai,
           p.id AS phong_id, p.loai_phong_id, p.khu_vuc_id, p.dia_chi,
           p.gia_goc, p.dien_tich, p.so_phong_ngu, p.so_wc, p.tien_nghi, p.hinh_anh,
           p.user_id
    FROM tin_dang td
    JOIN phong_tro p ON td.phong_tro_id = p.id
    WHERE td.id = $id AND p.user_id = $userId AND td.trang_thai != 'bi_xoa'
    LIMIT 1
")->fetch_assoc();

if (!$room) {
    setFlash('danger', 'Không tìm thấy tin hoặc bạn không có quyền chỉnh sửa.');
    redirect(BASE_URL . '/my-posts/index.php');
}

$categories   = $db->query("SELECT * FROM loai_phong ORDER BY ten")->fetch_all(MYSQLI_ASSOC);
$cities       = $db->query("SELECT DISTINCT tinh_thanh FROM khu_vuc ORDER BY tinh_thanh")->fetch_all(MYSQLI_ASSOC);
$khu_vuc_list = [];
foreach ($db->query("SELECT * FROM khu_vuc ORDER BY tinh_thanh, phuong_xa") as $kv) {
    $khu_vuc_list[$kv['tinh_thanh']][] = $kv;
}

// Lấy tỉnh hiện tại của phòng từ khu_vuc_id
$curKV = null;
if (!empty($room['khu_vuc_id'])) {
    $curKV = $db->query("SELECT * FROM khu_vuc WHERE id = {$room['khu_vuc_id']} LIMIT 1")->fetch_assoc();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tieu_de    = trim($_POST['tieu_de'] ?? '');
    $loai_id    = (int)($_POST['loai_phong_id'] ?? 0);
    $khu_vuc_id = (int)($_POST['khu_vuc_id'] ?? 0);
    $dia_chi    = trim($_POST['dia_chi'] ?? '');
    $gia        = (float)($_POST['gia'] ?? 0);
    $dien_tich  = (float)($_POST['dien_tich'] ?? 0);
    $so_pn      = (int)($_POST['so_phong_ngu'] ?? 0);
    $so_wc      = (int)($_POST['so_wc'] ?? 0);
    $tien_nghi  = trim($_POST['tien_nghi'] ?? '');
    $mo_ta      = trim($_POST['mo_ta'] ?? '');

    if ($tieu_de === '')  $errors[] = 'Tiêu đề không được bỏ trống.';
    if ($loai_id <= 0)    $errors[] = 'Vui lòng chọn loại phòng.';
    if ($khu_vuc_id <= 0) $errors[] = 'Vui lòng chọn khu vực.';
    if ($dia_chi === '')  $errors[] = 'Địa chỉ không được bỏ trống.';
    if ($gia <= 0)        $errors[] = 'Giá phải lớn hơn 0.';
    if ($dien_tich <= 0)  $errors[] = 'Diện tích phải lớn hơn 0.';

    // Upload ảnh mới (tuỳ chọn)
    $hinh_anh = $room['hinh_anh'];
    if (!empty($_FILES['hinh_anh']['name'])) {
        $uploaded = uploadImage($_FILES['hinh_anh'], 'room');
        $uploaded ? $hinh_anh = $uploaded : $errors[] = 'Ảnh không hợp lệ (chỉ jpg/png/webp, tối đa 5MB).';
    }

    if (empty($errors)) {
        $phong_id = (int)$room['phong_id'];

        // Bước 1: UPDATE tin_dang
        $s1 = $db->prepare(
            "UPDATE tin_dang SET tieu_de=?, mo_ta=?, gia=?, trang_thai='cho_duyet', updated_at=NOW()
             WHERE id=?"
        );
        $s1->bind_param('ssdi', $tieu_de, $mo_ta, $gia, $id);
        $ok1 = $s1->execute();
        $s1->close();

        // Bước 2: UPDATE phong_tro
        $s2 = $db->prepare(
            "UPDATE phong_tro SET
                loai_phong_id=?, khu_vuc_id=?, dia_chi=?, gia_goc=?, dien_tich=?,
                so_phong_ngu=?, so_wc=?, tien_nghi=?, hinh_anh=?, updated_at=NOW()
             WHERE id=? AND user_id=?"
        );
        $s2->bind_param('iisddiissii',
            $loai_id, $khu_vuc_id, $dia_chi, $gia, $dien_tich,
            $so_pn, $so_wc, $tien_nghi, $hinh_anh,
            $phong_id, $userId
        );
        $ok2 = $s2->execute();
        $s2->close();

        // Bước 3: Lưu ảnh liên quan mới vào hinh_anh_phong
        if (!empty($_FILES['hinh_lien_quan']['name'][0])) {
            $maxThu = (int)($db->query("SELECT COALESCE(MAX(thu_tu),0) FROM hinh_anh_phong WHERE phong_id=$phong_id")->fetch_row()[0]);
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
                    $maxThu++;
                    $sl = $db->prepare("INSERT INTO hinh_anh_phong (phong_id, duong_dan, thu_tu) VALUES (?,?,?)");
                    $sl->bind_param('isi', $phong_id, $res, $maxThu);
                    $sl->execute();
                    $sl->close();
                }
            }
        }

        if ($ok1 && $ok2) {
            setFlash('success', 'Cập nhật tin thành công! Tin đang chờ duyệt lại.');
            redirect(BASE_URL . '/my-posts/index.php');
        }
        $errors[] = 'Lỗi hệ thống, vui lòng thử lại.';
    }
    // Ghi đè room data bằng POST để giữ giá trị form
    $room = array_merge($room, [
        'tieu_de'       => $tieu_de,
        'loai_phong_id' => $loai_id,
        'khu_vuc_id'    => $khu_vuc_id,
        'dia_chi'       => $dia_chi,
        'gia'           => $gia,
        'dien_tich'     => $dien_tich,
        'so_phong_ngu'  => $so_pn,
        'so_wc'         => $so_wc,
        'tien_nghi'     => $tien_nghi,
        'mo_ta'         => $mo_ta,
    ]);
}

$pageTitle = 'Sửa tin đăng';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:860px">
    <?php renderFlash(); ?>

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?= BASE_URL ?>/my-posts/index.php" class="btn-back"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="section-title mb-0">Sửa <span>tin đăng</span></h1>
            <p class="section-subtitle mt-1">Sau khi sửa, tin sẽ được chờ duyệt lại</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
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
                           value="<?= e($room['tieu_de']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Loại phòng <span class="required">*</span></label>
                    <select name="loai_phong_id" class="form-select form-control-custom" required>
                        <option value="">-- Chọn loại phòng --</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $room['loai_phong_id'] == $c['id'] ? 'selected' : '' ?>>
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
                        <option value="<?= e($c['tinh_thanh']) ?>"
                            <?= ($curKV && $curKV['tinh_thanh'] === $c['tinh_thanh']) ? 'selected' : '' ?>>
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
                           value="<?= e($room['dia_chi']) ?>" required>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-title"><i class="bi bi-rulers me-2 text-warning"></i>Chi tiết phòng</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label-custom">Giá thuê (đ/tháng) <span class="required">*</span></label>
                    <input type="number" name="gia" class="form-control form-control-custom"
                           min="0" step="100000" value="<?= $room['gia'] ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Diện tích (m²) <span class="required">*</span></label>
                    <input type="number" name="dien_tich" class="form-control form-control-custom"
                           min="1" step="0.5" value="<?= $room['dien_tich'] ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom">Phòng ngủ</label>
                    <input type="number" name="so_phong_ngu" class="form-control form-control-custom"
                           min="0" value="<?= $room['so_phong_ngu'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom">WC</label>
                    <input type="number" name="so_wc" class="form-control form-control-custom"
                           min="0" value="<?= $room['so_wc'] ?>">
                </div>
                <div class="col-12">
                    <label class="form-label-custom">Tiện nghi</label>
                    <input type="text" name="tien_nghi" class="form-control form-control-custom"
                           value="<?= e($room['tien_nghi'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label-custom">Mô tả chi tiết</label>
                    <textarea name="mo_ta" class="form-control form-control-custom" rows="5"><?= e($room['mo_ta'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-title"><i class="bi bi-image me-2 text-warning"></i>Hình ảnh</div>
            <?php if ($room['hinh_anh']): ?>
            <div class="mb-3">
                <p class="form-label-custom mb-1">Ảnh hiện tại:</p>
                <img src="<?= getImageUrl($room['hinh_anh']) ?>" style="max-height:160px;border-radius:8px;object-fit:cover" alt="">
            </div>
            <?php endif; ?>
            <div class="upload-zone" id="uploadZone">
                <input type="file" name="hinh_anh" id="imgInput" accept="image/*" style="display:none" onchange="previewImg(this)">
                <label for="imgInput" class="upload-label">
                    <div id="uploadPlaceholder">
                        <i class="bi bi-arrow-repeat" style="font-size:2rem;color:var(--primary)"></i>
                        <p class="mt-2 mb-0 fw-semibold" style="color:var(--secondary)">Nhấn để đổi ảnh (đại diện, tuỳ chọn)</p>
                        <small class="text-muted">JPG, PNG, WEBP — tối đa 5MB</small>
                    </div>
                    <img id="imgPreview" src="" style="display:none;max-height:200px;border-radius:8px;object-fit:cover" alt="">
                </label>
            </div>

            <!-- Ảnh liên quan -->
            <?php
            $extra_imgs = $db->query("SELECT * FROM hinh_anh_phong WHERE phong_id = {$room['phong_id']} ORDER BY thu_tu, id")->fetch_all(MYSQLI_ASSOC);
            ?>
            <?php if (!empty($extra_imgs)): ?>
            <div class="mt-3 mb-1 fw-semibold" style="color:var(--secondary)">Ảnh liên quan hiện có:</div>
            <div class="row g-2 mb-2">
                <?php foreach ($extra_imgs as $img): ?>
                <div class="col-4 col-md-3">
                    <img src="<?= getImageUrl($img['duong_dan']) ?>"
                         style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <label class="form-label-custom mt-2">Thêm ảnh liên quan</label>
            <input type="file" name="hinh_lien_quan[]" class="form-control" accept="image/*" id="extraUpload" multiple>
            <div class="form-text">Chọn nhiều ảnh cùng lúc (JPG/PNG/WEBP, mỗi ảnh tối đa 5MB).</div>
            <div class="row g-2 mt-2" id="extraPreviewGrid"></div>
        </div>

        <div class="d-flex gap-3 justify-content-end">
            <a href="<?= BASE_URL ?>/my-posts/index.php" class="btn-cancel">Hủy</a>
            <button type="submit" class="btn-submit">
                <i class="bi bi-save me-2"></i>Lưu thay đổi
            </button>
        </div>
    </form>
</div>

<script>
const kvData    = <?= json_encode($khu_vuc_list, JSON_UNESCAPED_UNICODE) ?>;
const savedKvId = <?= (int)$room['khu_vuc_id'] ?>;

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

// Init khi load trang
const initCity = document.getElementById('citySelect').value;
if (initCity) populateDistricts(initCity, savedKvId);

document.getElementById('citySelect').addEventListener('change', function () {
    populateDistricts(this.value, 0);
});

// Preview ảnh đại diện
function previewImg(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('uploadPlaceholder').style.display = 'none';
        const prev = document.getElementById('imgPreview');
        prev.src = e.target.result;
        prev.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

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
