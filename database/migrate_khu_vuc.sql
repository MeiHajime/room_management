-- =====================================================
-- MIGRATION: Thêm khu_vuc_id vào phong_tro
-- Chạy script này nếu bạn đã có CSDL cũ và muốn
-- cập nhật cấu trúc sang dùng bảng khu_vuc
-- =====================================================

USE quan_ly_phong_tro;

-- 1. Tạo bảng khu_vuc nếu chưa có
CREATE TABLE IF NOT EXISTS khu_vuc (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tinh_thanh  VARCHAR(100) NOT NULL,
    phuong_xa   VARCHAR(100) NOT NULL,
    INDEX idx_khu_vuc_tinh (tinh_thanh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Thêm dữ liệu mẫu khu_vuc (bỏ qua nếu đã có)
INSERT IGNORE INTO khu_vuc (id, tinh_thanh, phuong_xa) VALUES
(1, 'TP.HCM', 'Quận 1'),
(2, 'TP.HCM', 'Bình Thạnh'),
(3, 'TP.HCM', 'Quận 3'),
(4, 'TP.HCM', 'TP. Thủ Đức'),
(5, 'TP.HCM', 'Quận 7'),
(6, 'TP.HCM', 'Gò Vấp'),
(7, 'TP.HCM', 'Tân Bình');

-- 3. Thêm cột khu_vuc_id vào phong_tro nếu chưa có
ALTER TABLE phong_tro
    ADD COLUMN IF NOT EXISTS khu_vuc_id INT UNSIGNED DEFAULT NULL AFTER loai_phong_id;

-- 3b. Sau khi cột đã có, thêm FK và index
--     (chỉ chạy khi tất cả bản ghi đã có khu_vuc_id hợp lệ)
-- ALTER TABLE phong_tro
--     ADD CONSTRAINT fk_phong_tro_khu_vuc
--         FOREIGN KEY (khu_vuc_id) REFERENCES khu_vuc(id) ON DELETE SET NULL,
--     ADD INDEX idx_phong_tro_khu_vuc_id (khu_vuc_id);

-- 4. (Tuỳ chọn) Xoá cột tinh_thanh, quan_huyen cũ nếu còn trong phong_tro
--    *** CHỈ chạy nếu đã migrate xong khu_vuc_id cho toàn bộ bản ghi ***
-- ALTER TABLE phong_tro DROP COLUMN IF EXISTS tinh_thanh;
-- ALTER TABLE phong_tro DROP COLUMN IF EXISTS quan_huyen;

-- 5. (Tuỳ chọn) Xoá index cũ nếu còn
-- ALTER TABLE phong_tro DROP INDEX IF EXISTS idx_phong_tro_tinh_thanh;
