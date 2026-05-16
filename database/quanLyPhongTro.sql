-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 10:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quan_ly_phong_tro`
--

-- --------------------------------------------------------

--
-- Table structure for table `bao_cao_da_thue`
--

CREATE TABLE `bao_cao_da_thue` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `tin_dang_id` int(10) UNSIGNED NOT NULL,
  `ghi_chu` text DEFAULT NULL,
  `trang_thai` enum('cho_duyet','da_duyet','bi_tu_choi') DEFAULT 'cho_duyet',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bao_cao_da_thue`
--

INSERT INTO `bao_cao_da_thue` (`id`, `user_id`, `tin_dang_id`, `ghi_chu`, `trang_thai`, `created_at`, `updated_at`) VALUES
(1, 2, 5, 'Đã có khách hàng thuê phòng này vào ngày 16/5/2026, vui lòng xóa bài đăng của tôi về phòng này', 'cho_duyet', '2026-05-16 14:42:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hinh_anh_phong`
--

CREATE TABLE `hinh_anh_phong` (
  `id` int(10) UNSIGNED NOT NULL,
  `phong_id` int(10) UNSIGNED NOT NULL,
  `duong_dan` varchar(255) NOT NULL,
  `thu_tu` tinyint(3) UNSIGNED DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hinh_anh_phong`
--

INSERT INTO `hinh_anh_phong` (`id`, `phong_id`, `duong_dan`, `thu_tu`, `created_at`) VALUES
(1, 1, '/assets/images/phong1_1.jpg', 1, '2026-05-15 17:22:04'),
(2, 1, '/assets/images/phong1_2.jpg', 2, '2026-05-15 17:22:04'),
(3, 1, '/assets/images/phong1_3.jpg', 3, '2026-05-15 17:22:04'),
(4, 1, '/assets/images/phong1_4.jpg', 4, '2026-05-15 17:22:04'),
(5, 2, '/assets/images/phong2_1.jpg', 1, '2026-05-15 17:22:04'),
(6, 2, '/assets/images/phong2_2.jpg', 2, '2026-05-15 17:22:04'),
(7, 2, '/assets/images/phong2_3.jpg', 3, '2026-05-15 17:22:04'),
(8, 2, '/assets/images/phong2_4.jpg', 4, '2026-05-15 17:22:04'),
(9, 3, '/assets/images/phong3_1.jpg', 1, '2026-05-15 17:22:04'),
(10, 3, '/assets/images/phong3_2.jpg', 2, '2026-05-15 17:22:04'),
(11, 3, '/assets/images/phong3_3.jpg', 3, '2026-05-15 17:22:04'),
(12, 3, '/assets/images/phong3_4.jpg', 4, '2026-05-15 17:22:04'),
(13, 4, '/assets/images/phong4_1.jpg', 1, '2026-05-15 17:22:04'),
(14, 4, '/assets/images/phong4_2.jpg', 2, '2026-05-15 17:22:04'),
(15, 4, '/assets/images/phong4_3.jpg', 3, '2026-05-15 17:22:04'),
(16, 4, '/assets/images/phong4_4.jpg', 4, '2026-05-15 17:22:04'),
(17, 5, '/assets/images/phong5_1.jpg', 1, '2026-05-15 17:22:04'),
(18, 5, '/assets/images/phong5_2.jpg', 2, '2026-05-15 17:22:04'),
(19, 5, '/assets/images/phong5_3.jpg', 3, '2026-05-15 17:22:04'),
(20, 5, '/assets/images/phong5_4.jpg', 4, '2026-05-15 17:22:04'),
(21, 6, '/assets/images/phong6_1.jpg', 1, '2026-05-15 17:22:04'),
(22, 6, '/assets/images/phong6_2.jpg', 2, '2026-05-15 17:22:04'),
(23, 6, '/assets/images/phong6_3.jpg', 3, '2026-05-15 17:22:04'),
(24, 6, '/assets/images/phong6_4.jpg', 4, '2026-05-15 17:22:04'),
(25, 7, '/assets/images/phong7_1.jpg', 1, '2026-05-15 17:22:04'),
(26, 7, '/assets/images/phong7_2.jpg', 2, '2026-05-15 17:22:04'),
(27, 7, '/assets/images/phong7_3.jpg', 3, '2026-05-15 17:22:04'),
(28, 7, '/assets/images/phong7_4.jpg', 4, '2026-05-15 17:22:04');

-- --------------------------------------------------------

--
-- Table structure for table `khu_vuc`
--

CREATE TABLE `khu_vuc` (
  `id` int(10) UNSIGNED NOT NULL,
  `tinh_thanh` varchar(100) NOT NULL,
  `phuong_xa` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khu_vuc`
--

INSERT INTO `khu_vuc` (`id`, `tinh_thanh`, `phuong_xa`) VALUES
(1, 'TP.HCM', 'Quận 1'),
(2, 'TP.HCM', 'Bình Thạnh'),
(3, 'TP.HCM', 'Quận 3'),
(4, 'TP.HCM', 'TP. Thủ Đức'),
(5, 'TP.HCM', 'Quận 7'),
(6, 'TP.HCM', 'Gò Vấp'),
(7, 'TP.HCM', 'Tân Bình'),
(8, 'Thành phố Vinh', 'phường Thành Vinh');

-- --------------------------------------------------------

--
-- Table structure for table `loai_phong`
--

CREATE TABLE `loai_phong` (
  `id` int(10) UNSIGNED NOT NULL,
  `ten` varchar(100) NOT NULL,
  `mo_ta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loai_phong`
--

INSERT INTO `loai_phong` (`id`, `ten`, `mo_ta`) VALUES
(1, 'Phòng trọ', 'Phòng trọ đơn thường'),
(2, 'Phòng mini', 'Phòng mini có gác lửng'),
(3, 'Căn hộ mini', 'Căn hộ mini độc lập'),
(4, 'Nhà nguyên căn', 'Thuê nguyên căn nhà'),
(5, 'Ký túc xá', 'Phòng ký túc xá giá rẻ');

-- --------------------------------------------------------

--
-- Table structure for table `phong_tro`
--

CREATE TABLE `phong_tro` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `loai_phong_id` int(10) UNSIGNED DEFAULT NULL,
  `khu_vuc_id` int(10) UNSIGNED DEFAULT NULL,
  `gia_goc` decimal(12,0) NOT NULL COMMENT 'VND/tháng',
  `dien_tich` float NOT NULL COMMENT 'm2',
  `dia_chi` varchar(255) NOT NULL,
  `so_phong_ngu` tinyint(3) UNSIGNED DEFAULT 1,
  `so_wc` tinyint(3) UNSIGNED DEFAULT 1,
  `tien_nghi` text DEFAULT NULL COMMENT 'JSON hoặc text mô tả tiện nghi',
  `hinh_anh` varchar(255) DEFAULT NULL COMMENT 'Ảnh đại diện',
  `trang_thai` enum('co_san','da_cho_thue') NOT NULL DEFAULT 'co_san',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phong_tro`
--

INSERT INTO `phong_tro` (`id`, `user_id`, `loai_phong_id`, `khu_vuc_id`, `gia_goc`, `dien_tich`, `dia_chi`, `so_phong_ngu`, `so_wc`, `tien_nghi`, `hinh_anh`, `trang_thai`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 2500000, 20, '123 Nguyễn Trãi', 1, 1, 'Điều hòa, WC riêng, bếp nấu', '/assets/images/phong1_1.jpg', 'co_san', '2026-05-15 16:53:00', '2026-05-16 03:32:44'),
(2, 2, 2, 2, 3200000, 25, '45 Xô Viết Nghệ Tĩnh', 1, 1, 'Điều hòa, Tủ lạnh, WC riêng', '/assets/images/phong2_1.jpg', 'co_san', '2026-05-15 16:53:00', '2026-05-16 03:32:44'),
(3, 3, 3, 3, 5500000, 35, '78 Võ Thị Sáu', 1, 1, 'Full nội thất, Điều hòa, Nóng lạnh, Ban công', '/assets/images/phong3_1.jpg', 'co_san', '2026-05-15 16:53:00', '2026-05-16 03:32:44'),
(4, 3, 1, 4, 2000000, 18, '15 Phạm Văn Đồng', 1, 1, 'Điện nước riêng, Bảo vệ 24/7', '/assets/images/phong4_1.jpg', 'co_san', '2026-05-15 16:53:00', '2026-05-16 03:32:44'),
(5, 2, 4, 5, 15000000, 80, '22 Nguyễn Văn Linh', 3, 2, 'Full nội thất, Sân để xe, Sân vườn', '/assets/images/phong5_1.jpg', 'co_san', '2026-05-15 16:53:00', '2026-05-16 03:32:44'),
(6, 3, 1, 6, 1800000, 16, '99 Nguyễn Oanh', 1, 1, 'Điện nước sinh hoạt', '/assets/images/phong6_1.jpg', 'co_san', '2026-05-15 16:53:00', '2026-05-16 03:32:44'),
(7, 2, 2, 7, 3800000, 28, '55 Cộng Hòa', 1, 1, '0', '/assets/images/phong7_1.jpg', 'co_san', '2026-05-15 16:53:00', '2026-05-16 03:32:44'),
(8, 2, 3, 8, 2000000, 20, '21', 2, 1, 'Điều hòa, WC riêng, bếp nấu, ban công', 'room_1778873586_136ca85c.jpg', 'co_san', '2026-05-16 02:33:06', '2026-05-16 03:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `thong_bao`
--

CREATE TABLE `thong_bao` (
  `id` int(10) UNSIGNED NOT NULL,
  `nguoi_gui_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Người gửi (NULL = hệ thống tự động)',
  `nguoi_nhan_id` int(10) UNSIGNED NOT NULL COMMENT 'Người nhận thông báo',
  `tieu_de` varchar(255) NOT NULL COMMENT 'Tiêu đề ngắn gọn của thông báo',
  `noi_dung` text NOT NULL COMMENT 'Nội dung chi tiết',
  `loai` enum('info','success','warning','danger') DEFAULT 'info' COMMENT 'Loại để hiển thị màu sắc',
  `url` varchar(500) DEFAULT NULL COMMENT 'Đường dẫn đến trang liên quan (tuỳ chọn)',
  `da_doc` tinyint(1) DEFAULT 0 COMMENT '0=chưa đọc, 1=đã đọc',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tin_dang`
--

CREATE TABLE `tin_dang` (
  `id` int(11) UNSIGNED NOT NULL,
  `phong_tro_id` int(10) UNSIGNED NOT NULL,
  `tieu_de` varchar(255) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `gia` decimal(10,2) DEFAULT NULL,
  `trang_thai` enum('cho_duyet','da_duyet','bi_tu_choi','an','da_thue') DEFAULT 'da_thue',
  `luot_xem` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tin_dang`
--

INSERT INTO `tin_dang` (`id`, `phong_tro_id`, `tieu_de`, `mo_ta`, `gia`, `trang_thai`, `luot_xem`, `created_at`, `updated_at`) VALUES
(1, 1, 'Phòng trọ giá rẻ Quận 1 gần trung tâm', 'Phòng thoáng mát, an ninh, có điện nước riêng, gần chợ và trường học, view đẹp tuyệt vời.', 2500000.00, 'da_duyet', 4, '2026-05-15 16:53:00', '2026-05-16 02:24:40'),
(2, 2, 'Phòng mini có gác lửng Bình Thạnh', 'Phòng mới xây, nội thất cơ bản, có gác lửng để ngủ.', 3200000.00, 'da_duyet', 1, '2026-05-15 16:53:00', '2026-05-16 01:18:39'),
(3, 3, 'Căn hộ mini Quận 3 đầy đủ nội thất', 'Căn hộ mini độc lập, full nội thất, thích hợp cho cặp đôi hoặc 2 người.', 5500000.00, 'da_duyet', 5, '2026-05-15 16:53:00', '2026-05-16 01:20:00'),
(4, 4, 'Phòng trọ Thủ Đức gần ĐH Quốc Gia', 'Gần trường đại học, phù hợp sinh viên, an ninh 24/7.', 2000000.00, 'da_duyet', 3, '2026-05-15 16:53:00', '2026-05-16 01:19:10'),
(5, 5, 'Nhà nguyên căn Quận 7 cho thuê', 'Nhà 2 tầng, 3 phòng ngủ, phù hợp gia đình.', 15000000.00, 'da_duyet', 0, '2026-05-15 16:53:00', '2026-05-15 23:31:41'),
(6, 6, 'Phòng trọ Gò Vấp cần cho thuê gấp', 'Phòng rộng, thoáng, có cửa sổ, giá ưu đãi.', 1800000.00, 'da_duyet', 1, '2026-05-15 16:53:00', '2026-05-15 23:51:46'),
(7, 7, 'Phòng mini mới xây Tân Bình', 'Phòng mới xây hoàn toàn, nội thất hiện đại.', 3800000.00, 'da_duyet', 0, '2026-05-15 16:53:00', '2026-05-15 23:53:03'),
(8, 8, 'Cho thuê chung cư mini mới ở thành phố vinh', 'Phòng được trang bị đầy đủ: điều hoà, nệm, giường, nóng lạnh, tủ, quạt, máy giặt, thang máy, wifi. Có bảo vệ, giờ giấc sinh hoạt thoải mái, không ràng buộc, không chung chủ. Đóng 1 tháng, cọc 1 tháng. Hợp đồng 6 tháng.', 2000000.00, 'da_duyet', 0, '2026-05-16 02:33:06', '2026-05-16 02:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(15) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default_avatar.png',
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `trang_thai` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `ho_ten`, `so_dien_thoai`, `dia_chi`, `avatar`, `role`, `trang_thai`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@phongtro.vn', '$2y$10$a4C42anP58wE7RQzadZVVOVYneUM3wO77OL/bS68AokTdvnQGVYV2', 'Quản Trị Viên', '0900000000', NULL, 'default_avatar.png', 'admin', 'active', '2026-05-15 16:53:00', '2026-05-15 23:45:04'),
(2, 'nguyen_van_a', 'nguyenvana@gmail.com', '$2y$10$A880Km72Tqbw7DOnIbUe0.IBNT291FkrKiKbojjMEaQ8kQVxR0pE6', 'Nguyễn Văn A', '0912345678', 'Quận 1, TP.HCM', 'default_avatar.png', 'user', 'active', '2026-05-15 16:53:00', '2026-05-15 23:45:04'),
(3, 'tran_thi_b', 'tranthib@gmail.com', '$2y$10$A880Km72Tqbw7DOnIbUe0.IBNT291FkrKiKbojjMEaQ8kQVxR0pE6', 'Trần Thị B', '0987654321', 'Quận 3, TP.HCM', 'default_avatar.png', 'user', 'active', '2026-05-15 16:53:00', '2026-05-15 23:45:04'),
(4, 'le_van_c', 'levanc@gmail.com', '$2y$10$A880Km72Tqbw7DOnIbUe0.IBNT291FkrKiKbojjMEaQ8kQVxR0pE6', 'Lê Văn C', '0977123456', 'Bình Thạnh, TP.HCM', 'default_avatar.png', 'user', 'inactive', '2026-05-15 16:53:00', '2026-05-15 23:45:04'),
(5, 'thanhnt', 'thanh123@gmail.com', '$2y$10$A880Km72Tqbw7DOnIbUe0.IBNT291FkrKiKbojjMEaQ8kQVxR0pE6', 'Nguyễn Trọng Thành', '0348242344', NULL, 'default_avatar.png', 'user', 'active', '2026-05-15 22:30:55', '2026-05-15 23:45:04'),
(6, 'ntkh', 'khuyen123@gmail.com', '$2y$10$Px2pc1bqueqQegUIZAZooOd44rfwBDMClnRt1785TfGdVQ1nj45s6', 'Nguyễn Thị Khánh Huyền', '', '', 'default_avatar.png', 'admin', 'active', '2026-05-15 23:57:07', '2026-05-15 23:57:07'),
(7, 'admin1', 'admin1@gmail.com', '$2y$10$ALSRe9yyNOcip0QfFE6vaOXud18arC9B0ZdWFYDJqZQy5amiTnc/C', 'Quản trị viên 1', '', NULL, 'default_avatar.png', 'admin', 'active', '2026-05-16 01:39:22', '2026-05-16 01:40:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bao_cao_da_thue`
--
ALTER TABLE `bao_cao_da_thue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tin_dang_id` (`tin_dang_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `hinh_anh_phong`
--
ALTER TABLE `hinh_anh_phong`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hinh_anh_phong_id` (`phong_id`);

--
-- Indexes for table `khu_vuc`
--
ALTER TABLE `khu_vuc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_khu_vuc_tinh` (`tinh_thanh`);

--
-- Indexes for table `loai_phong`
--
ALTER TABLE `loai_phong`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `phong_tro`
--
ALTER TABLE `phong_tro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_phong_tro_loai` (`loai_phong_id`),
  ADD KEY `idx_phong_tro_user_id` (`user_id`),
  ADD KEY `idx_phong_tro_trang_thai` (`trang_thai`),
  ADD KEY `idx_phong_tro_gia` (`gia_goc`),
  ADD KEY `idx_phong_tro_created_at` (`created_at`),
  ADD KEY `idx_phong_tro_khu_vuc_id` (`khu_vuc_id`);

--
-- Indexes for table `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tb_nguoi_nhan` (`nguoi_nhan_id`),
  ADD KEY `idx_tb_nguoi_gui` (`nguoi_gui_id`),
  ADD KEY `idx_tb_da_doc` (`da_doc`),
  ADD KEY `idx_tb_created` (`created_at`);

--
-- Indexes for table `tin_dang`
--
ALTER TABLE `tin_dang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tindang_phongtro` (`phong_tro_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_trang_thai` (`trang_thai`),
  ADD KEY `idx_users_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bao_cao_da_thue`
--
ALTER TABLE `bao_cao_da_thue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hinh_anh_phong`
--
ALTER TABLE `hinh_anh_phong`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `khu_vuc`
--
ALTER TABLE `khu_vuc`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `loai_phong`
--
ALTER TABLE `loai_phong`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `phong_tro`
--
ALTER TABLE `phong_tro`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `thong_bao`
--
ALTER TABLE `thong_bao`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tin_dang`
--
ALTER TABLE `tin_dang`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bao_cao_da_thue`
--
ALTER TABLE `bao_cao_da_thue`
  ADD CONSTRAINT `bao_cao_da_thue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bao_cao_da_thue_ibfk_2` FOREIGN KEY (`tin_dang_id`) REFERENCES `tin_dang` (`id`);

--
-- Constraints for table `hinh_anh_phong`
--
ALTER TABLE `hinh_anh_phong`
  ADD CONSTRAINT `fk_hinh_anh_phong` FOREIGN KEY (`phong_id`) REFERENCES `phong_tro` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `phong_tro`
--
ALTER TABLE `phong_tro`
  ADD CONSTRAINT `fk_phong_tro_khu_vuc` FOREIGN KEY (`khu_vuc_id`) REFERENCES `khu_vuc` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_phong_tro_loai` FOREIGN KEY (`loai_phong_id`) REFERENCES `loai_phong` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_phong_tro_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD CONSTRAINT `thong_bao_ibfk_1` FOREIGN KEY (`nguoi_nhan_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `thong_bao_ibfk_2` FOREIGN KEY (`nguoi_gui_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tin_dang`
--
ALTER TABLE `tin_dang`
  ADD CONSTRAINT `fk_tindang_phongtro` FOREIGN KEY (`phong_tro_id`) REFERENCES `phong_tro` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
