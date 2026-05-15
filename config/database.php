<?php
// =====================================================
// CẤU HÌNH KẾT NỐI DATABASE
// =====================================================
define('DB_HOST',     'localhost');
define('DB_USER',     'root');       // Đổi thành username MySQL của bạn
define('DB_PASSWORD', '');           // Đổi thành password MySQL của bạn
define('DB_NAME',     'quan_ly_phong_tro');
define('DB_CHARSET',  'utf8mb4');

// Base URL - đổi nếu chạy khác thư mục
define('BASE_URL', 'http://localhost:8080/quanLyPhongTro');
define('UPLOAD_DIR', __DIR__ . '/../uploads/rooms/');
define('UPLOAD_URL', BASE_URL . '/uploads/rooms/');

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
