<?php
// =====================================================
// CẤU HÌNH KẾT NỐI DATABASE
// =====================================================

// Đồng bộ timezone PHP với MySQL (cả hai đều dùng UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
define('DB_NAME',     'quan_ly_phong_tro');
define('DB_CHARSET',  'utf8mb4');


// Base URL - đổi nếu chạy khác thư mục
define('BASE_URL', 'http://localhost/quanLyPhongTro');
define('UPLOAD_DIR',        __DIR__ . '/../uploads/users/');
define('UPLOAD_URL',        BASE_URL . '/uploads/users/');
define('ASSETS_IMAGE_DIR',  __DIR__ . '/../assets/images/');
define('ASSETS_IMAGE_URL',  BASE_URL . '/assets/images/');


/**
 * Tạo kết nối MySQLi
 */
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;color:red;padding:20px;">
                    <h2>Lỗi kết nối Database</h2>
                    <p>' . htmlspecialchars($conn->connect_error) . '</p>
                    <p>Vui lòng kiểm tra file <code>config/database.php</code></p>
                 </div>');
        }
        $conn->set_charset(DB_CHARSET);
        $conn->query("SET time_zone = '+07:00'");
    }
    return $conn;
}
