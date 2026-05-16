<?php
// my-posts/report-rented.php — Báo cáo phòng đã được thuê
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);

if (!$id) redirect(BASE_URL . '/my-posts/index.php');

// Lấy tin — phải là của chính user và đang ở trạng thái da_duoc_dat
$tinDang = $db->query("
    SELECT p.trang_thai as trang_thai_phong,p.*, td.*, k.tinh_thanh FROM phong_tro p
    JOIN tin_dang td ON td.phong_tro_id = p.id
    LEFT JOIN khu_vuc k ON p.khu_vuc_id = k.id
    WHERE td.id = $id AND p.user_id = $userId
    LIMIT 1
")->fetch_assoc();

if (!$tinDang) {
    setFlash('danger', 'Không tìm thấy tin hoặc bạn không có quyền thao tác.');
    redirect(BASE_URL . '/my-posts/index.php');
}

// Kiểm tra đã gửi báo cáo chưa
$existReport = $db->query("SELECT id FROM bao_cao_da_thue WHERE tin_dang_id = $id AND user_id = $userId AND trang_thai != 'da_duyet' LIMIT 1" )->fetch_assoc();
$status = $tinDang['trang_thai'];

$reopenSuccess = false;
if (!$existReport && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen_post'])) {
    $isReopen = true;
    // Cập nhật trạng thái tin về 'cho_duyet'
    $updateStmt = $db->prepare("UPDATE tin_dang SET trang_thai = 'cho_duyet', updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param('i', $id);
    $updateStmt->execute();
    $updateStmt->close();

    // Ghi log vào báo cáo đã thuê với ghi chú loại "Mở lại tin"
    $ghi_chu = "Yêu cầu mở lại" . trim($_POST['ghi_chu'] ?? 'Yêu cầu mở lại tin đăng');
    $logStmt = $db->prepare(
        "INSERT INTO bao_cao_da_thue (tin_dang_id, user_id, ghi_chu, trang_thai, created_at)
         VALUES (?, ?, ?, 'cho_duyet', NOW())"
    );
    $logStmt->bind_param('iis', $id, $userId, $ghi_chu);
    $logStmt->execute();
    $logStmt->close();

    $reopenSuccess = true;

    // hiển thị thông báo thành công
    setFlash('success', 'Yêu cầu mở lại tin đã được gửi thành công! Admin sẽ xem xét và xác nhận.');
    redirect(BASE_URL . '/my-posts/index.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isReopen) {
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');

    if (empty($existReport)) {
        $stmt = $db->prepare(
            "INSERT INTO bao_cao_da_thue (tin_dang_id, user_id, ghi_chu, trang_thai, created_at)
             VALUES (?, ?, ?, 'cho_duyet', NOW())"
        );
        $stmt->bind_param('iis', $id, $userId, $ghi_chu);
        if ($stmt->execute()) {
            setFlash('success', 'Báo cáo đã gửi thành công! Admin sẽ xem xét và xác nhận.');
            redirect(BASE_URL . '/my-posts/index.php');
        } else {
            $errors[] = 'Lỗi hệ thống, vui lòng thử lại.';
        }
        $stmt->close();
    } else {
        $errors[] = 'Bạn đã gửi báo cáo cho phòng này rồi.';
    }
}

// Include header SAU khi tất cả redirect xử lý xong
$pageTitle = 'Báo cáo phòng đã thuê';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:640px">
    <?php renderFlash(); ?>

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?= BASE_URL ?>/my-posts/index.php" class="btn-back"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="section-title mb-0">Báo cáo <span>phòng đã thuê</span></h1>
            <p class="section-subtitle mt-1">Xác nhận phòng trọ của bạn đã có người thuê</p>
        </div>
    </div>

    <!-- Thông tin phòng -->
    <div class="form-card mb-3">
        <div class="d-flex gap-3 align-items-start">
            <img src="<?= getImageUrl($tinDang['hinh_anh']) ?>"
                 style="width:90px;height:70px;object-fit:cover;border-radius:10px;flex-shrink:0" alt="">
            <div>
                <div class="fw-bold" style="color:var(--secondary)"><?= e($tinDang['tieu_de']) ?></div>
                <div class="text-muted" style="font-size:.82rem">
                    <i class="bi bi-geo-alt text-warning"></i> <?= e($tinDang['dia_chi']) ?>, <?= e($tinDang['tinh_thanh'] ?? '') ?>
                </div>
                <div class="mt-1"><?= roomStatusBadge($tinDang['trang_thai_phong']) ?></div>
            </div>
        </div>
    </div>

    <?php if ($existReport): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Bạn đã gửi báo cáo cho phòng này rồi. Admin đang xem xét.
    </div>
    <?php elseif ($status == "an"): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Báo cáo về việc phòng đã cho thuê của bạn đã được duyệt. Admin đã ẩn bài viết của bạn!!!
            <!-- Nếu muốn mở lại hãy gửi yêu cầu mở lại bài đăng. -->
            <div class="mt-2">
                <button class="btn-submit" onclick="reOpenStatus()">
                    <i class="bi bi-send-check me-2"></i>Gửi yêu cầu mở lại
                </button>
            </div>
        </div>
    <?php elseif (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php else: ?>
            
    <form method="POST" class="post-form">
        <div class="form-card">
            <div class="form-card-title">
                <i class="bi bi-check2-circle me-2 text-success"></i>Xác nhận đã thuê
            </div>
            <p style="color:var(--text-muted);font-size:.9rem">
                Gửi báo cáo này để thông báo với admin rằng phòng trọ của bạn đã có người thuê.
                Admin sẽ xem xét và cập nhật trạng thái phòng.
            </p>
            <div>
                <label class="form-label-custom">Ghi chú thêm (tuỳ chọn)</label>
                <textarea name="ghi_chu" class="form-control form-control-custom" rows="4"
                          placeholder="VD: Người thuê dọn vào ngày 15/05, hợp đồng 12 tháng..."><?= e($_POST['ghi_chu'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-3 justify-content-end">
            <a href="<?= BASE_URL ?>/my-posts/index.php" class="btn-cancel">Hủy</a>
            <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#22c55e,#16a34a)">
                <i class="bi bi-send-check me-2"></i>Gửi báo cáo
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Modal: Gửi yêu cầu mở lại tin -->
<div id="reopenModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:18px;padding:32px 28px;max-width:480px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:slideUp .25s ease">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-arrow-repeat" style="color:#fff;font-size:1.2rem"></i>
            </div>
            <div>
                <h5 style="margin:0;font-weight:700;color:#1e293b">Yêu cầu mở lại tin đăng</h5>
                <p style="margin:0;font-size:.83rem;color:#64748b">Admin sẽ xem xét và quyết định</p>
            </div>
        </div>

        <form method="POST" id="reopenForm">
            <input type="hidden" name="reopen_post" value="1">
            <div style="margin-bottom:16px">
                <label style="font-size:.9rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">
                    Lý do mở lại <span style="color:#ef4444">*</span>
                </label>
                <textarea name="ghi_chu" id="reopenReason" rows="4"
                          style="width:100%;border:2px solid #e5e7eb;border-radius:10px;padding:10px 12px;
                                 font-size:.9rem;resize:vertical;outline:none;transition:border .2s;font-family:inherit"
                          placeholder="VD: Người thuê đã trả phòng, tôi muốn đăng lại để tìm người thuê mới..."
                          onfocus="this.style.borderColor='#6366f1'"
                          onblur="this.style.borderColor='#e5e7eb'"
                          required></textarea>
                <div id="reopenError" style="display:none;color:#ef4444;font-size:.82rem;margin-top:4px">
                    Vui lòng nhập lý do mở lại.
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" onclick="closeReopenModal()"
                        style="padding:9px 20px;border:2px solid #e5e7eb;border-radius:10px;background:#fff;
                               color:#64748b;font-weight:600;cursor:pointer;transition:.2s;font-size:.9rem"
                        onmouseover="this.style.borderColor='#94a3b8'"
                        onmouseout="this.style.borderColor='#e5e7eb'">
                    Hủy
                </button>
                <button type="submit"
                        style="padding:9px 22px;border:none;border-radius:10px;
                               background:linear-gradient(135deg,#6366f1,#8b5cf6);
                               color:#fff;font-weight:600;cursor:pointer;font-size:.9rem;
                               box-shadow:0 4px 12px rgba(99,102,241,.35);transition:.2s"
                        onmouseover="this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.transform='none'">
                    <i class="bi bi-send me-1"></i> Gửi yêu cầu
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes slideUp {
    from { opacity:0; transform:translateY(24px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
</style>

<script>
function reOpenStatus() {
    document.getElementById('reopenModal').style.display = 'flex';
    document.getElementById('reopenReason').focus();
}

function closeReopenModal() {
    document.getElementById('reopenModal').style.display = 'none';
}

// Đóng khi click nền
document.getElementById('reopenModal').addEventListener('click', function(e) {
    if (e.target === this) closeReopenModal();
});

// Validate trước khi submit
document.getElementById('reopenForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('reopenReason').value.trim();
    if (!reason) {
        e.preventDefault();
        document.getElementById('reopenError').style.display = 'block';
        document.getElementById('reopenReason').style.borderColor = '#ef4444';
    }
});
</script>
