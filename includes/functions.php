<?php
// =====================================================
// HÀM TIỆN ÍCH CHUNG
// =====================================================
require_once __DIR__ . '/../config/database.php';

// ---------- SESSION ----------
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ---------- AUTH ----------
function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/auth/login.php?msg=login_required');
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirect(BASE_URL . '/index.php?msg=no_permission');
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db  = getDB();
    $id  = (int)$_SESSION['user_id'];
    $res = $db->query("SELECT * FROM users WHERE id = $id LIMIT 1");
    return $res ? $res->fetch_assoc() : null;
}

// ---------- REDIRECT ----------
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ---------- FLASH MESSAGES ----------
function setFlash(string $type, string $message): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $type  = htmlspecialchars($flash['type']);
    $msg   = htmlspecialchars($flash['message']);
    $icons = ['success' => '✅', 'danger' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon  = $icons[$type] ?? 'ℹ️';
    echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
            {$icon} {$msg}
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
          </div>";
}

// ---------- FORMAT ----------
function formatPrice(float $price): string {
    return number_format($price, 0, ',', '.') . ' đ';
}

function formatDate(string $date): string {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime(string $dt): string {
    return date('d/m/Y H:i', strtotime($dt));
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return $diff . ' giây trước';
    if ($diff < 3600)    return intdiv($diff, 60) . ' phút trước';
    if ($diff < 86400)   return intdiv($diff, 3600) . ' giờ trước';
    if ($diff < 2592000) return intdiv($diff, 86400) . ' ngày trước';
    return formatDate($datetime);
}

// ---------- SANITIZE ----------
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ---------- UPLOAD ẢNH ----------
function uploadImage(array $file, string $prefix = 'room'): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) return false;
    if ($file['size'] > 5 * 1024 * 1024) return false; // max 5MB

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target   = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }
    return false;
}

function getImageUrl(?string $filename): string {
    if (!$filename) {
        return BASE_URL . '/assets/images/no-image.svg';
    }

    // Đường dẫn tuyệt đối bắt đầu bằng '/' (vd: /assets/images/phong1_1.jpg)
    // → kiểm tra file vật lý từ document root của project
    if ($filename[0] === '/') {
        $physicalPath = __DIR__ . '/..' . $filename;
        if (file_exists($physicalPath)) {
            return BASE_URL . $filename;
        }
        return BASE_URL . '/assets/images/no-image.svg';
    }

    // Tên file upload thuần (vd: room_1234_abcd.jpg) → nằm trong UPLOAD_DIR
    if (file_exists(UPLOAD_DIR . $filename)) {
        return UPLOAD_URL . $filename;
    }

    return BASE_URL . '/assets/images/no-image.svg';
}

// ---------- PAGINATION ----------
function paginate(int $total, int $perPage, int $current): array {
    $pages = (int)ceil($total / $perPage);
    return [
        'total'      => $total,
        'per_page'   => $perPage,
        'current'    => $current,
        'pages'      => $pages,
        'offset'     => ($current - 1) * $perPage,
        'has_prev'   => $current > 1,
        'has_next'   => $current < $pages,
    ];
}

function renderPagination(array $pg, string $baseUrl): void {
    if ($pg['pages'] <= 1) return;
    echo '<nav><ul class="pagination justify-content-center flex-wrap gap-1">';
    if ($pg['has_prev']) {
        echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($pg['current'] - 1) . '">‹ Trước</a></li>';
    }
    for ($i = 1; $i <= $pg['pages']; $i++) {
        $active = $i === $pg['current'] ? 'active' : '';
        echo "<li class=\"page-item {$active}\"><a class=\"page-link\" href=\"{$baseUrl}&page={$i}\">{$i}</a></li>";
    }
    if ($pg['has_next']) {
        echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($pg['current'] + 1) . '">Sau ›</a></li>';
    }
    echo '</ul></nav>';
}

// ---------- STATUS LABELS ----------

// Badge cho phong_tro.trang_thai (2 trạng thái mới)
function roomStatusBadge(string $status): string {
    $map = [
        'co_san'      => ['success',   'Còn trống'],
        'da_cho_thue' => ['secondary', 'Đã cho thuê'],
    ];
    [$color, $label] = $map[$status] ?? ['light', $status];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

// Badge cho tin_dang.trang_thai (5 trạng thái)
function tinDangStatusBadge(string $status): string {
    $map = [
        'cho_duyet'  => ['warning',   'Chờ duyệt'],
        'da_duyet'   => ['success',   'Đã duyệt'],
        'bi_tu_choi' => ['danger',    'Bị từ chối'],
        'an'         => ['secondary', 'Đã ẩn'],
    ];
    [$color, $label] = $map[$status] ?? ['light', $status];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function userStatusBadge(string $status): string {
    $map = [
        'active'   => ['success', 'Hoạt động'],
        'inactive' => ['warning', 'Chưa kích hoạt'],
        'banned'   => ['danger',  'Bị khóa'],
    ];
    [$color, $label] = $map[$status] ?? ['light', $status];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}
