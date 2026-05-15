<?php
// room-detail.php — Chi tiết phòng trọ
require_once __DIR__ . '/includes/functions.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) redirect(BASE_URL . '/index.php');

$room = $db->query(
    "SELECT p.*, k.tinh_thanh, k.phuong_xa,
            u.ho_ten as chu_tro, u.so_dien_thoai as chu_sdt, u.email as chu_email,
            l.ten as loai_ten
     FROM phong_tro p
     JOIN khu_vuc k ON p.khu_vuc_id = k.id
     LEFT JOIN users u ON p.user_id = u.id
     LEFT JOIN loai_phong l ON p.loai_phong_id = l.id
     WHERE p.id = $id AND p.trang_thai = 'da_duyet'
     LIMIT 1"
)->fetch_assoc();

if (!$room) {
    http_response_code(404);
    redirect(BASE_URL . '/index.php');
}

// Tăng lượt xem
$db->query("UPDATE phong_tro SET luot_xem = luot_xem + 1 WHERE id = $id");

// Ảnh phụ
$extraImages = $db->query(
    "SELECT duong_dan FROM hinh_anh_phong WHERE phong_id = $id ORDER BY thu_tu ASC"
)->fetch_all(MYSQLI_ASSOC);

// Phòng liên quan
$relatedRooms = $db->query(
    "SELECT p.id, p.tieu_de, p.gia, p.dien_tich, p.dia_chi, p.hinh_anh, k.tinh_thanh, k.phuong_xa
     FROM phong_tro p
     JOIN khu_vuc k ON p.khu_vuc_id = k.id
     WHERE p.trang_thai = 'da_duyet' AND p.id != $id AND k.tinh_thanh = '" . $db->real_escape_string($room['tinh_thanh']) . "'
     ORDER BY RAND() LIMIT 3"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = $room['tieu_de'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb" style="font-size:.875rem">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php?tinh_thanh=<?= urlencode($room['tinh_thanh']) ?>"><?= e($room['tinh_thanh']) ?></a></li>
            <li class="breadcrumb-item active"><?= e(mb_substr($room['tieu_de'], 0, 40)) ?>...</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- LEFT: ảnh + mô tả -->
        <div class="col-lg-8">
            <!-- Main image -->
            <img src="<?= getImageUrl($room['hinh_anh']) ?>"
                 alt="<?= e($room['tieu_de']) ?>"
                 class="detail-img-main mb-3"
                 onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">

            <!-- Extra images -->
            <?php if (!empty($extraImages)): ?>
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <?php foreach ($extraImages as $img): ?>
                <img src="<?= getImageUrl($img['duong_dan']) ?>"
                     style="width:80px;height:60px;object-fit:cover;border-radius:8px;cursor:pointer"
                     onclick="document.querySelector('.detail-img-main').src=this.src"
                     alt="ảnh phòng">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Info card -->
            <div class="detail-info-card">
                <h1 style="font-size:1.4rem;font-weight:800;color:var(--secondary);margin-bottom:.5rem">
                    <?= e($room['tieu_de']) ?>
                </h1>

                <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                    <?php if ($room['loai_ten']): ?>
                    <span class="badge bg-warning text-dark"><?= e($room['loai_ten']) ?></span>
                    <?php endif; ?>
                    <span class="text-muted" style="font-size:.85rem">
                        <i class="bi bi-geo-alt me-1 text-danger"></i>
                        <?= e($room['dia_chi']) ?><?= $room['phuong_xa'] ? ', ' . e($room['phuong_xa']) : '' ?>, <?= e($room['tinh_thanh']) ?>
                    </span>
                    <span class="text-muted" style="font-size:.85rem">
                        <i class="bi bi-eye me-1"></i><?= number_format($room['luot_xem']) ?> lượt xem
                    </span>
                </div>

                <div class="detail-price-big mb-3">
                    <?= formatPrice($room['gia']) ?><span style="font-size:1rem;font-weight:400;color:var(--text-muted)">/tháng</span>
                </div>

                <!-- Meta grid -->
                <div class="detail-meta-grid">
                    <div class="detail-meta-item">
                        <div class="label">📐 Diện tích</div>
                        <div class="value"><?= $room['dien_tich'] ?> m²</div>
                    </div>
                    <div class="detail-meta-item">
                        <div class="label">🛏 Phòng ngủ</div>
                        <div class="value"><?= $room['so_phong_ngu'] ?> phòng</div>
                    </div>
                    <div class="detail-meta-item">
                        <div class="label">🚿 Vệ sinh</div>
                        <div class="value"><?= $room['so_wc'] ?> WC</div>
                    </div>
                    <div class="detail-meta-item">
                        <div class="label">📅 Ngày đăng</div>
                        <div class="value"><?= formatDate($room['created_at']) ?></div>
                    </div>
                </div>

                <!-- Tiện nghi -->
                <?php if ($room['tien_nghi']): ?>
                <div class="mt-3">
                    <h6 style="font-weight:700;color:var(--secondary)"><i class="bi bi-stars me-2 text-warning"></i>Tiện nghi</h6>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <?php foreach (explode(',', $room['tien_nghi']) as $tn): ?>
                        <span class="badge bg-light text-dark border" style="font-size:.82rem;font-weight:500;padding:6px 12px">
                            ✅ <?= e(trim($tn)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Mô tả -->
                <?php if ($room['mo_ta']): ?>
                <div class="mt-3">
                    <h6 style="font-weight:700;color:var(--secondary)"><i class="bi bi-card-text me-2 text-warning"></i>Mô tả chi tiết</h6>
                    <p style="color:var(--text-muted);line-height:1.8;font-size:.9rem;margin-top:.5rem">
                        <?= nl2br(e($room['mo_ta'])) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: liên hệ -->
        <div class="col-lg-4">
            <div class="detail-info-card position-sticky" style="top:90px">
                <h6 style="font-weight:700;margin-bottom:1rem"><i class="bi bi-person-circle me-2 text-warning"></i>Thông tin liên hệ</h6>

                <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background:var(--bg);border:1px solid var(--border)">
                    <div style="width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#ea580c);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;font-weight:700;flex-shrink:0">
                        <?= strtoupper(mb_substr($room['chu_tro'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;color:var(--secondary)"><?= e($room['chu_tro']) ?></div>
                        <div style="font-size:.8rem;color:var(--text-muted)">Chủ nhà</div>
                    </div>
                </div>

                <?php if ($room['chu_sdt']): ?>
                <a href="tel:<?= e($room['chu_sdt']) ?>"
                   class="btn btn-warning w-100 fw-bold mb-2" style="border-radius:10px;padding:.75rem">
                    <i class="bi bi-telephone-fill me-2"></i><?= e($room['chu_sdt']) ?>
                </a>
                <?php endif; ?>

                <?php if ($room['chu_email']): ?>
                <a href="mailto:<?= e($room['chu_email']) ?>"
                   class="btn btn-outline-warning w-100 fw-semibold mb-2" style="border-radius:10px;padding:.75rem">
                    <i class="bi bi-envelope me-2"></i>Gửi email
                </a>
                <?php endif; ?>

                <div class="mt-3 p-3 rounded-3" style="background:#fff8f1;border:1px solid var(--primary-l);font-size:.82rem;color:var(--text-muted)">
                    <i class="bi bi-shield-check me-1 text-warning"></i>
                    Hãy xem trực tiếp trước khi đặt cọc. Không chuyển tiền qua mạng khi chưa xác nhận.
                </div>
            </div>
        </div>
    </div>

    <!-- Phòng liên quan -->
    <?php if (!empty($relatedRooms)): ?>
    <div class="mt-5">
        <h2 class="section-title">Phòng <span>tương tự</span></h2>
        <div class="divider-primary"></div>
        <div class="row g-4">
            <?php foreach ($relatedRooms as $r): ?>
            <div class="col-md-4">
                <div class="room-card h-100">
                    <div class="room-card-img">
                        <img src="<?= getImageUrl($r['hinh_anh']) ?>"
                             alt="<?= e($r['tieu_de']) ?>"
                             onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.svg'">
                        <div class="room-card-price"><?= formatPrice($r['gia']) ?>/tháng</div>
                    </div>
                    <div class="room-card-body">
                        <a href="<?= BASE_URL ?>/room-detail.php?id=<?= $r['id'] ?>" class="room-card-title"><?= e($r['tieu_de']) ?></a>
                        <div class="room-card-location"><i class="bi bi-geo-alt-fill text-warning"></i><?= e($r['dia_chi']) ?></div>
                        <div class="room-meta">
                            <span class="room-meta-item"><i class="bi bi-rulers"></i><?= $r['dien_tich'] ?>m²</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
