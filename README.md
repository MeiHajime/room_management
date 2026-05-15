# Quản Lý Phòng Trọ — Hệ thống Web PHP + MySQL

Hệ thống quản lý tin đăng phòng trọ hoàn chỉnh, bao gồm trang người dùng và trang quản trị Admin.

---

## 📁 Cấu trúc thư mục

```
quanLyPhongTro/
├── database/
│   └── quanLyPhongTro.sql      ← File SQL tạo database
├── config/
│   └── database.php             ← Cấu hình kết nối DB
├── includes/
│   ├── functions.php            ← Hàm tiện ích dùng chung
│   ├── header.php               ← Header frontend
│   ├── footer.php               ← Footer frontend
│   ├── admin_header.php         ← Header admin (sidebar)
│   └── admin_footer.php         ← Footer admin
├── assets/
│   ├── css/
│   │   ├── style.css            ← CSS trang người dùng
│   │   └── admin.css            ← CSS trang admin
│   ├── js/
│   │   └── main.js
│   └── images/
│       └── no-image.svg
├── uploads/rooms/               ← Ảnh upload (auto-create)
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── admin/
│   ├── index.php                ← Dashboard (charts, stats)
│   ├── rooms/
│   │   ├── index.php            ← Danh sách phòng
│   │   ├── add.php              ← Thêm phòng
│   │   └── edit.php             ← Sửa phòng
│   ├── users/
│   │   ├── index.php            ← Danh sách user
│   │   ├── add.php              ← Thêm tài khoản
│   │   └── edit.php             ← Sửa + đổi mật khẩu
│   └── reports/
│       └── index.php            ← Báo cáo thống kê
├── index.php                    ← Trang chủ (danh sách + tìm kiếm)
├── room-detail.php              ← Chi tiết phòng
└── search.php                   ← Redirect tìm kiếm
```

---

## 🚀 Cài đặt và chạy

### Yêu cầu
- PHP ≥ 7.4
- MySQL ≥ 5.7 / MariaDB ≥ 10.3
- XAMPP / WAMP / Laragon (hoặc bất kỳ web server nào)

### Bước 1: Clone / Copy project
Đặt thư mục `quanLyPhongTro` vào `htdocs` (XAMPP) hoặc `www` (WAMP):
```
C:\xampp\htdocs\quanLyPhongTro\
```

### Bước 2: Tạo Database
1. Mở **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Tạo database mới tên `quan_ly_phong_tro`
3. Chọn database vừa tạo → tab **SQL**
4. Copy nội dung file `database/quanLyPhongTro.sql` và chạy

Hoặc chạy qua command line:
```bash
mysql -u root -p < database/quanLyPhongTro.sql
```

### Bước 3: Cấu hình Database
Mở file `config/database.php` và chỉnh:
```php
define('DB_HOST',     'localhost');
define('DB_USER',     'root');       // username MySQL của bạn
define('DB_PASSWORD', '');           // password MySQL của bạn
define('BASE_URL',    'http://localhost/quanLyPhongTro');
```

### Bước 4: Phân quyền thư mục uploads
```bash
# Linux/Mac
chmod 755 uploads/rooms/

# Windows: không cần thao tác thêm
```

### Bước 5: Truy cập
- **Trang chủ:**  `http://localhost/quanLyPhongTro/index.php`
- **Đăng nhập:** `http://localhost/quanLyPhongTro/auth/login.php`
- **Admin:**     `http://localhost/quanLyPhongTro/admin/index.php`

---

## 🔑 Tài khoản Demo

| Vai trò | Username | Password |
|---------|----------|----------|
| Admin   | `admin`        | `password` |
| User    | `nguyen_van_a` | `password` |
| User    | `tran_thi_b`   | `password` |

> ⚠️ **Lưu ý:** Đổi mật khẩu trước khi deploy lên production!
>
> Hash bcrypt trong file SQL là của chuỗi `"password"`. Để tạo hash mới:
> ```php
> echo password_hash('MatKhauMoi@123', PASSWORD_BCRYPT);
> ```

---

## ✅ Tính năng

### Người dùng (Frontend)
- [x] Trang chủ với hero banner + bộ lọc tìm kiếm
- [x] Danh sách phòng trọ dạng grid với phân trang
- [x] Tìm kiếm theo từ khóa, tỉnh thành, loại phòng, khoảng giá
- [x] Trang chi tiết phòng trọ (ảnh, thông tin, tiện nghi, liên hệ)
- [x] Đăng ký / Đăng nhập / Đăng xuất
- [x] Validation form phía server

### Quản trị (Admin)
- [x] Dashboard với biểu đồ thống kê (Chart.js)
- [x] Quản lý phòng trọ: Thêm / Sửa / Xóa mềm
- [x] Duyệt phòng / Ẩn/Hiện phòng trọ
- [x] Upload ảnh đại diện phòng
- [x] Quản lý tài khoản: Thêm / Sửa / Xóa / Khóa
- [x] Đổi mật khẩu tài khoản
- [x] Báo cáo: Tìm kiếm, sắp xếp theo người đăng / thời gian / giá
- [x] Thống kê số lượng tin đăng theo tháng (biểu đồ đường)

---

## 🗄️ ERD — Sơ đồ quan hệ

```
users (1) ─────────── (N) phong_tro
loai_phong (1) ──────── (N) phong_tro
phong_tro (1) ────────── (N) hinh_anh_phong
khu_vuc (1) ────────── (N) phong_tro
```

### Mô tả bảng

| Bảng | Mô tả |
|------|-------|
| `users` | Tài khoản người dùng và admin |
| `loai_phong` | Danh mục loại phòng (phòng trọ, mini, CHDV...) |
| `phong_tro` | Tin đăng phòng trọ chính |
| `hinh_anh_phong` | Ảnh phụ kèm theo tin đăng |
| `khu_vuc` | Khu vực phòng trọ |

---

## 🛡️ Bảo mật

- Mật khẩu mã hóa bằng `password_hash()` (bcrypt)
- Prepared statements cho tất cả truy vấn DB (chống SQL Injection)
- `htmlspecialchars()` cho mọi output (chống XSS)
- Session regenerate sau đăng nhập
- Kiểm tra quyền admin ở mọi trang admin
- `.htaccess` chặn thực thi PHP trong uploads

---

## 📦 Tech Stack

- **Backend:** PHP 7.4+ (MySQLi, Sessions)
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3 (Custom Design System), JavaScript
- **UI Framework:** Bootstrap 5.3
- **Icons:** Bootstrap Icons
- **Charts:** Chart.js 4.x
- **Fonts:** Google Fonts (Inter)
