-- =====================================================
-- QUẢN LÝ PHÒNG TRỌ - DATABASE SCHEMA
-- =====================================================

CREATE DATABASE IF NOT EXISTS quan_ly_phong_tro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE quan_ly_phong_tro;

-- =====================================================
-- BẢNG: users (Tài khoản người dùng)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    ho_ten      VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(15) DEFAULT NULL,
    dia_chi     VARCHAR(255) DEFAULT NULL,
    avatar      VARCHAR(255) DEFAULT 'default_avatar.png',
    role        ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    trang_thai  ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role       (role),
    INDEX idx_users_trang_thai (trang_thai),
    INDEX idx_users_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG: loai_phong (Loại phòng/danh mục)
-- =====================================================
CREATE TABLE IF NOT EXISTS loai_phong (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten     VARCHAR(100) NOT NULL,
    mo_ta   TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS khu_vuc (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tinh_thanh  VARCHAR(100) NOT NULL,
    phuong_xa   VARCHAR(100) NOT NULL,
    INDEX idx_khu_vuc_tinh (tinh_thanh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- =====================================================
-- BẢNG: phong_tro (Tin đăng phòng trọ)
-- =====================================================
CREATE TABLE IF NOT EXISTS phong_tro (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    loai_phong_id   INT UNSIGNED DEFAULT NULL,
    khu_vuc_id      INT UNSIGNED DEFAULT NULL,
    tieu_de         VARCHAR(255) NOT NULL,
    mo_ta           TEXT         DEFAULT NULL,
    dia_chi         VARCHAR(255) NOT NULL,
    gia             DECIMAL(12,0) NOT NULL COMMENT 'VND/tháng',
    dien_tich       FLOAT        NOT NULL COMMENT 'm2',
    so_phong_ngu    TINYINT UNSIGNED DEFAULT 1,
    so_wc           TINYINT UNSIGNED DEFAULT 1,
    tien_nghi       TEXT         DEFAULT NULL COMMENT 'JSON hoặc text mô tả tiện nghi',
    hinh_anh        VARCHAR(255) DEFAULT NULL COMMENT 'Ảnh đại diện',
    trang_thai      ENUM('cho_duyet','da_duyet','bi_an','bi_xoa') NOT NULL DEFAULT 'cho_duyet',
    luot_xem        INT UNSIGNED DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_phong_tro_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_phong_tro_loai
        FOREIGN KEY (loai_phong_id) REFERENCES loai_phong(id) ON DELETE SET NULL,
    CONSTRAINT fk_phong_tro_khu_vuc
        FOREIGN KEY (khu_vuc_id) REFERENCES khu_vuc(id) ON DELETE SET NULL,

    INDEX idx_phong_tro_user_id     (user_id),
    INDEX idx_phong_tro_trang_thai  (trang_thai),
    INDEX idx_phong_tro_gia         (gia),
    INDEX idx_phong_tro_khu_vuc_id  (khu_vuc_id),
    INDEX idx_phong_tro_created_at  (created_at),
    FULLTEXT idx_phong_tro_search   (tieu_de, dia_chi, mo_ta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG: hinh_anh_phong (Ảnh phụ của phòng)
-- =====================================================
CREATE TABLE IF NOT EXISTS hinh_anh_phong (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phong_id    INT UNSIGNED NOT NULL,
    duong_dan   VARCHAR(255) NOT NULL,
    thu_tu      TINYINT UNSIGNED DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_hinh_anh_phong
        FOREIGN KEY (phong_id) REFERENCES phong_tro(id) ON DELETE CASCADE,
    INDEX idx_hinh_anh_phong_id (phong_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================

-- Loại phòng
INSERT INTO loai_phong (ten, mo_ta) VALUES
    ('Phòng trọ',       'Phòng trọ đơn thường'),
    ('Phòng mini',      'Phòng mini có gác lửng'),
    ('Căn hộ mini',     'Căn hộ mini độc lập'),
    ('Nhà nguyên căn',  'Thuê nguyên căn nhà'),
    ('Ký túc xá',       'Phòng ký túc xá giá rẻ');

-- Admin account
-- Password mặc định: Admin@123
-- Tạo hash mới: chạy reset_demo_passwords.php hoặc dùng lệnh PHP:
-- echo password_hash('Admin@123', PASSWORD_BCRYPT);
INSERT INTO users (username, email, password, ho_ten, so_dien_thoai, role, trang_thai) VALUES
    ('admin', 'admin@phongtro.vn', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản Trị Viên', '0900000000', 'admin', 'active');
-- ⚠️ Hash trên là placeholder (hash của chuỗi "password" từ Laravel seeder).
-- Chạy reset_demo_passwords.php để cập nhật sang hash đúng của Admin@123 / User@123

-- User mẫu — password: User@123 (chạy reset_demo_passwords.php để cập nhật hash)
INSERT INTO users (username, email, password, ho_ten, so_dien_thoai, dia_chi, role, trang_thai) VALUES
    ('nguyen_van_a', 'nguyenvana@gmail.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', '0912345678', 'Quận 1, TP.HCM', 'user', 'active'),
    ('tran_thi_b',   'tranthib@gmail.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B',   '0987654321', 'Quận 3, TP.HCM', 'user', 'active'),
    ('le_van_c',     'levanc@gmail.com',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Văn C',     '0977123456', 'Bình Thạnh, TP.HCM', 'user', 'inactive');

INSERT INTO khu_vuc (tinh_thanh, phuong_xa) VALUES
('TP.HCM', 'Quận 1'),
('TP.HCM', 'Bình Thạnh'),
('TP.HCM', 'Quận 3'),
('TP.HCM', 'TP. Thủ Đức'),
('TP.HCM', 'Quận 7'),
('TP.HCM', 'Gò Vấp'),
('TP.HCM', 'Tân Bình');

-- Phòng trọ mẫu (user_id 2=nguyen_van_a, 3=tran_thi_b, khu_vuc_id 1-7)
INSERT INTO phong_tro
    (user_id, loai_phong_id, khu_vuc_id, tieu_de, mo_ta, dia_chi,
     gia, dien_tich, so_phong_ngu, so_wc, tien_nghi, hinh_anh, trang_thai)
VALUES
    (2, 1, 1,
     'Phòng trọ 20m² giá rẻ Quận 1 gần trung tâm',
     'Phòng trọ sạch sẽ, thoáng mát, có cửa sổ nhìn ra đường. Gần chợ Bến Thành, tiện di chuyển.',
     '12 Lê Lai',
     2800000, 20, 1, 1, 'Điều hòa, WC riêng, Wifi',
     '/assets/images/phong1_1.jpg', 'da_duyet'),

    (2, 2, 2,
     'Phòng mini gác lửng Bình Thạnh full nội thất',
     'Phòng mini có gác lửng cao ráo, đầy đủ nội thất: giường, tủ, bàn làm việc, máy giặt riêng.',
     '45 Đinh Bộ Lĩnh',
     3500000, 25, 1, 1, 'Điều hòa, Tủ lạnh, Máy giặt, Wifi, Nội thất đầy đủ',
     '/assets/images/phong2_1.jpg', 'da_duyet'),

    (3, 3, 3,
     'Căn hộ mini 35m² Quận 3 tiện nghi cao cấp',
     'Căn hộ mini độc lập, thiết kế hiện đại. Phù hợp cho cặp đôi hoặc gia đình nhỏ.',
     '88 Võ Văn Tần',
     5500000, 35, 1, 1, 'Điều hòa, Tủ lạnh, Máy giặt, Bếp, Wifi, Ban công',
     '/assets/images/phong3_1.jpg', 'da_duyet'),

    (2, 1, 4,
     'Phòng trọ sinh viên giá rẻ TP. Thủ Đức',
     'Phòng trọ dành cho sinh viên, gần ĐHQG, giá cả hợp lý, an ninh tốt.',
     '27 Tô Vĩnh Diện',
     1800000, 18, 1, 1, 'Wifi, WC chung, Bảo vệ 24/7',
     '/assets/images/phong4_1.jpg', 'da_duyet'),

    (3, 4, 5,
     'Nhà nguyên căn 3 phòng ngủ Quận 7 hẻm yên tĩnh',
     'Nhà nguyên căn 3PN, 2WC, có sân để xe ô tô, hẻm thông thoáng, an ninh.',
     '15 Nguyễn Thị Thập',
     12000000, 80, 3, 2, 'Điều hòa 3 phòng, Tủ lạnh, Máy giặt, Bếp, Sân để xe',
     '/assets/images/phong5_1.jpg', 'da_duyet'),

    (2, 5, 6,
     'Ký túc xá giá rẻ Gò Vấp cho sinh viên',
     'Phòng ký túc xá 4 người, gần trường ĐH Công nghiệp, giá cực rẻ.',
     '102 Nguyễn Oanh',
     800000, 40, 1, 1, 'Wifi, WC chung, Tủ cá nhân',
     '/assets/images/phong6_1.jpg', 'da_duyet'),

    (3, 2, 7,
     'Phòng mini mới xây Tân Bình gần sân bay',
     'Phòng mới xây 2024, nội thất mới 100%, cách sân bay Tân Sơn Nhất 5 phút.',
     '33 Trường Chinh',
     3200000, 22, 1, 1, 'Điều hòa, WC riêng, Wifi, Nội thất mới',
     '/assets/images/phong7_1.jpg', 'cho_duyet');

-- Ảnh phụ cho từng phòng (phong_id 1-7 tương ứng với thứ tự INSERT trên)
INSERT INTO hinh_anh_phong (phong_id, duong_dan, thu_tu) VALUES
    (1, '/assets/images/phong1_2.jpg', 1),
    (1, '/assets/images/phong1_3.jpg', 2),
    (1, '/assets/images/phong1_4.jpg', 3),
    (2, '/assets/images/phong2_2.jpg', 1),
    (2, '/assets/images/phong2_3.jpg', 2),
    (2, '/assets/images/phong2_4.jpg', 3),
    (3, '/assets/images/phong3_2.jpg', 1),
    (3, '/assets/images/phong3_3.jpg', 2),
    (3, '/assets/images/phong3_4.jpg', 3),
    (4, '/assets/images/phong4_2.jpg', 1),
    (4, '/assets/images/phong4_3.jpg', 2),
    (4, '/assets/images/phong4_4.jpg', 3),
    (5, '/assets/images/phong5_2.jpg', 1),
    (5, '/assets/images/phong5_3.jpg', 2),
    (5, '/assets/images/phong5_4.jpg', 3),
    (6, '/assets/images/phong6_2.jpg', 1),
    (6, '/assets/images/phong6_3.jpg', 2),
    (6, '/assets/images/phong6_4.jpg', 3),
    (7, '/assets/images/phong7_2.jpg', 1),
    (7, '/assets/images/phong7_3.jpg', 2),
    (7, '/assets/images/phong7_4.jpg', 3);