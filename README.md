# 🏨 Hotel Management System

Hệ thống quản lý khách sạn đơn giản với đầy đủ tính năng đặt phòng và thanh toán.

## 🚀 Cài đặt nhanh

1. **Cài đặt database:**
   ```
   http://localhost/hotel/install_database.php
   ```

2. **Đăng nhập:**
   - Admin: `admin` / `password`
   - Staff: `staff` / `password`
   - Customer: `customer` / `password`

## 📋 Tính năng chính

### ✅ Quản lý phòng
- Xem danh sách phòng theo tầng
- Đặt phòng với validation ngày
- Check-in/Check-out

### ✅ Hệ thống thanh toán
- Thanh toán bằng thẻ (test)
- Tự động điền thông tin thẻ test
- Hóa đơn chi tiết

### ✅ Quản lý booking
- Trạng thái: `pending_payment` → `booked` → `checked_in` → `completed`
- Hủy booking nếu chưa thanh toán
- Phòng chỉ được đặt khi thanh toán thành công

## 🎯 Luồng hoạt động

```
1. Chọn phòng → Đặt phòng (pending_payment)
2. Thanh toán → Booking confirmed (booked)
3. Check-in → Phòng occupied
4. Check-out → Hoàn thành
```

## 📁 Cấu trúc file

```
hotel/
├── 📄 book.php              # Đặt phòng
├── 💳 payment_form.php      # Thanh toán
├── 📋 invoice.php           # Hóa đơn
├── 🏠 dashboard.php         # Trang chủ
├── 📊 manage_bookings.php   # Quản lý booking
├── ❌ cancel_booking.php    # Hủy booking
├── 🧪 demo_booking_flow.php # Demo luồng
├── 🔧 install_database.php  # Cài đặt DB
└── 📁 sql/
    └── database_schema.sql  # Schema hoàn chỉnh
```

## 🔧 Cấu hình

- **Database:** `hotel_management`
- **PHP:** 7.4+
- **MySQL:** 5.7+

## 📞 Hỗ trợ

Nếu có vấn đề, hãy chạy lại:
```
http://localhost/hotel/install_database.php
```
