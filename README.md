# 🏨 Hệ Thống Quản Lý Khách Sạn

Hệ thống quản lý khách sạn hoàn chỉnh được xây dựng bằng PHP thuần với giao diện Bootstrap, hỗ trợ đầy đủ các chức năng từ đặt phòng, quản lý khách hàng đến thanh toán và báo cáo.

## ✨ Tính Năng Nổi Bật

- 🔐 **Hệ thống xác thực đa cấp độ** (Admin, Staff, Customer)
- 🏠 **Quản lý phòng thông minh** với trạng thái real-time
- 📅 **Hệ thống đặt phòng trực tuyến** với lịch trực quan
- 💳 **Xử lý thanh toán** và quản lý hóa đơn
- 📊 **Báo cáo và thống kê** chi tiết
- 👥 **Quản lý nhân viên** và phân quyền
- 🎨 **Giao diện responsive** tương thích mọi thiết bị

## 🚀 Cài Đặt Nhanh

### Yêu Cầu Hệ Thống
- **XAMPP** (Apache + MySQL + PHP)
- **PHP** 
- **MySQL**
- **Trình duyệt web** 

### Bước 1: Chuẩn Bị
1. **Khởi động XAMPP**
   - Mở XAMPP Control Panel
   - Start Apache và MySQL
   - Đảm bảo cả hai service đang chạy (nền xanh)

2. **Giải nén dự án**
   - Copy thư mục `hotel` vào `C:\xampp\htdocs\`
   - Đường dẫn cuối cùng: `C:\xampp\htdocs\hotel\`

### Bước 2: Thiết Lập Database
#### Phương Pháp A: Tự Động (Khuyến Nghị)
1. Mở trình duyệt, truy cập: `http://localhost/hotel/setup_database.php`
2. Chờ script chạy hoàn tất
3. Thông báo "Database setup completed successfully!"

#### Phương Pháp B: Thủ Công
1. Mở phpMyAdmin: `http://localhost/phpmyadmin`
2. Tạo database mới: `hotel_management`
3. Import file: `sql/quick_setup.sql`
4. Chạy script: `sql/init_database.sql`

### Bước 3: Truy Cập Hệ Thống
- **URL chính**: `http://localhost/hotel/`
- **Trang đăng nhập**: `http://localhost/hotel/login.php`

## 🔑 Tài Khoản Demo

| Vai Trò | Username | Password | Quyền Hạn |
|---------|----------|----------|-----------|
| 👑 **Admin** | `admin` | `admin123` | Toàn quyền hệ thống |
| 👔 **Staff** | `staff` | `staff123` | Quản lý phòng, đặt phòng |
| 👤 **Customer** | `customer` | `customer123` | Đặt phòng, xem thông tin |

## 🏗️ Cấu Trúc Hệ Thống

### Database Schema
```sql
📊 Bảng chính:
├── users           # Quản lý tài khoản và phân quyền
├── rooms           # Thông tin phòng khách sạn
├── guests          # Thông tin khách hàng
├── bookings        # Quản lý đặt phòng
├── payments        # Quản lý thanh toán
├── hotel_settings  # Cài đặt hệ thống
└── employees       # Quản lý nhân viên
```

### Cấu Trúc Phòng
- **3 tầng**, mỗi tầng **6 phòng**
- **Phòng thường**: 500,000 VND/đêm
- **Phòng VIP**: 1,000,000 VND/đêm
- **Tổng cộng**: 18 phòng

### Phân Quyền Hệ Thống
```
👑 Admin:
├── Quản lý toàn bộ hệ thống
├── Quản lý nhân viên
├── Xem báo cáo tổng hợp
└── Cài đặt hệ thống

👔 Staff:
├── Quản lý phòng và đặt phòng
├── Check-in/Check-out
├── Xử lý thanh toán
└── Báo cáo cơ bản

👤 Customer:
├── Xem danh sách phòng
├── Đặt phòng trực tuyến
├── Quản lý đặt phòng cá nhân
└── Xem lịch sử thanh toán
```

## 🎯 Hướng Dẫn Sử Dụng

### Cho Admin
1. **Đăng nhập** với tài khoản admin
2. **Truy cập** Admin Dashboard
3. **Quản lý** nhân viên, phòng, cài đặt hệ thống
4. **Xem báo cáo** tổng hợp và thống kê

### Cho Staff
1. **Đăng nhập** với tài khoản staff
2. **Quản lý phòng**: Xem trạng thái, cập nhật thông tin
3. **Xử lý đặt phòng**: Tạo, chỉnh sửa, hủy đặt phòng
4. **Check-in/Check-out**: Xử lý khách đến và đi
5. **Thanh toán**: Xử lý các khoản thanh toán

### Cho Customer
1. **Đăng ký** tài khoản mới hoặc **đăng nhập**
2. **Xem danh sách phòng** và giá cả
3. **Đặt phòng** với ngày check-in/check-out
4. **Quản lý đặt phòng** cá nhân
5. **Xem lịch sử** và thanh toán

## 🔧 Cấu Hình Và Tùy Chỉnh

### Kết Nối Database
Sửa file `db.php`:
```php
<?php
$host = "localhost";           
$user = "root";               
$pass = "";                  
$db   = "hotel_management";   
?>
```

### Thêm Phòng Mới
```sql
INSERT INTO rooms (room_number, type, floor, price, status, description) 
VALUES ('401', 'VIP', 4, 1200000, 'available', 'Phòng VIP tầng 4');
```

### Tùy Chỉnh Giao Diện
- **CSS chính**: `assets/css/hotel-style.css`
- **Header/Footer**: `includes/header.php`, `includes/footer.php`
- **Bootstrap**: Sử dụng Bootstrap 5.0+

## 🚨 Xử Lý Lỗi Thường Gặp

### ❌ Lỗi "Database connection failed"
**Nguyên nhân**: MySQL chưa khởi động hoặc sai thông tin kết nối
**Giải pháp**:
1. Kiểm tra XAMPP Control Panel
2. Đảm bảo MySQL đang chạy
3. Kiểm tra thông tin trong `db.php`

### ❌ Lỗi "Table 'users' doesn't exist"
**Nguyên nhân**: Database chưa được thiết lập
**Giải pháp**:
1. Chạy `setup_database.php`
2. Hoặc import `sql/quick_setup.sql`

### ❌ Lỗi "Access denied for user"
**Nguyên nhân**: Sai username/password MySQL
**Giải pháp**:
1. Kiểm tra thông tin trong `db.php`
2. Đảm bảo user có quyền truy cập database

### ❌ Lỗi "Page not found"
**Nguyên nhân**: Apache chưa khởi động hoặc sai đường dẫn
**Giải pháp**:
1. Kiểm tra XAMPP Control Panel
2. Đảm bảo Apache đang chạy
3. Kiểm tra đường dẫn thư mục

## 📱 Tính Năng Responsive

- **Desktop**: Giao diện đầy đủ với sidebar
- **Tablet**: Layout tối ưu cho màn hình trung bình
- **Mobile**: Giao diện tối ưu cho điện thoại

## 🔒 Bảo Mật

- **Mã hóa mật khẩu**: Sử dụng password_hash()
- **Session management**: Quản lý phiên đăng nhập an toàn
- **SQL Injection**: Sử dụng prepared statements
- **XSS Protection**: Lọc dữ liệu đầu vào

## 🚀 Tính Năng Nâng Cao

- **Auto-complete**: Tìm kiếm nhanh khách hàng
- **Calendar view**: Xem lịch đặt phòng trực quan
- **Advanced reports**: Báo cáo chi tiết và xuất Excel
- **Room service**: Quản lý dịch vụ phòng
- **Employee management**: Quản lý nhân viên chi tiết

## 📞 Hỗ Trợ

Nếu gặp vấn đề, hãy kiểm tra:
1. **XAMPP logs** trong `C:\xampp\apache\logs\`
2. **MySQL logs** trong `C:\xampp\mysql\data\`
3. **PHP error log** trong `C:\xampp\php\logs\`

## 📄 Giấy Phép

Dự án này được phát hành dưới giấy phép MIT. Bạn có thể tự do sử dụng, chỉnh sửa và phân phối.

## 🤝 Đóng Góp

Mọi đóng góp đều được chào đón! Hãy:
1. Fork dự án
2. Tạo branch mới cho tính năng
3. Commit thay đổi
4. Push lên branch
5. Tạo Pull Request

---

**Lưu ý**: Đây là dự án demo, vui lòng thay đổi mật khẩu mặc định trước khi sử dụng trong môi trường production.

**Phiên bản**: Beta 2.0




