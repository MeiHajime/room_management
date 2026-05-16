<?php
// my-posts/delete.php — Xóa tin đăng (soft delete trên tin_dang)
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);  // id = tin_dang.id

if (!$id) {
    redirect(BASE_URL . '/my-posts/index.php');
}

// Kiểm tra quyền: tin_dang phải thuộc user hiện tại (qua phong_tro.user_id)
$tin = $db->query("
    SELECT td.id FROM tin_dang td
    JOIN phong_tro p ON td.phong_tro_id = p.id
    WHERE td.id = $id AND p.user_id = $userId AND td.trang_thai != 'bi_xoa'
    LIMIT 1
")->fetch_assoc();

if (!$tin) {
    setFlash('danger', 'Không tìm thấy tin hoặc bạn không có quyền xóa.');
    redirect(BASE_URL . '/my-posts/index.php');
}

// Soft delete: đặt trang_thai tin_dang = 'bi_xoa'
$db->query("UPDATE tin_dang SET trang_thai = 'bi_xoa', updated_at = NOW() WHERE id = $id");

setFlash('success', 'Đã xóa tin đăng thành công.');
redirect(BASE_URL . '/my-posts/index.php');

