# Hệ thống quản lý Motel

Hệ thống quản lý phòng trọ hiện đại với giao diện đẹp mắt và dễ sử dụng.

## 🚀 Tính năng chính

### 👥 Quản lý người dùng
- **Đăng ký/Đăng nhập** với xác thực bảo mật
- **Phân quyền** Admin và Guest
- **Quản lý tài khoản** người dùng

### 🏠 Quản lý phòng trọ
- **Xem danh sách phòng** trống với thông tin chi tiết
- **Đặt phòng** với phí giữ chỗ
- **Thanh toán tiền cọc** để ký hợp đồng
- **Quản lý trạng thái phòng** (trống, đã cọc, đã thuê)

### 🛒 Hệ thống giỏ hàng
- **Thêm phòng vào giỏ hàng**
- **Xem giỏ hàng** với thông tin chi tiết
- **Thanh toán trực tiếp** từ giỏ hàng

### 💰 Quản lý tài chính
- **Tạo hóa đơn hàng tháng** tự động
- **Tính toán tiền điện** dựa trên chỉ số
- **Thanh toán hóa đơn** trực tuyến
- **Theo dõi lịch sử** thanh toán

### 👨‍💼 Trang quản trị (Admin)
- **Dashboard** với thống kê tổng quan
- **Quản lý phòng** (thêm, sửa, xóa)
- **Cập nhật chỉ số điện**
- **Quản lý người dùng**
- **Tạo hóa đơn** hàng tháng

## 🛠️ Cài đặt

### Yêu cầu hệ thống
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx web server

### Cài đặt
1. **Clone repository**
   ```bash
   git clone [repository-url]
   cd HTTTDT_MotelManagement
   ```

2. **Cấu hình database**
   - Tạo database `motel_db`
   - Import file `motel_db.sql`
   - Cập nhật thông tin kết nối trong `includes/db`

3. **Cấu hình web server**
   - Đặt thư mục project vào web root
   - Đảm bảo PHP có quyền đọc/ghi

4. **Truy cập hệ thống**
   - Mở trình duyệt và truy cập `http://localhost/HTTTDT_MotelManagement`
   - Trang chủ: `home.php`

## 📱 Giao diện

### Thiết kế hiện đại
- **Responsive design** tương thích mobile
- **Gradient background** đẹp mắt
- **Card-based layout** dễ nhìn
- **Icon Font Awesome** chuyên nghiệp

### Màu sắc chủ đạo
- **Primary**: Gradient xanh-tím (#667eea → #764ba2)
- **Success**: Xanh lá (#28a745)
- **Warning**: Vàng (#ffc107)
- **Danger**: Đỏ (#dc3545)

## 🔧 Cấu trúc thư mục

```
HTTTDT_MotelManagement/
├── assets/
│   └── css/
│       └── style.css          # CSS chính
├── includes/
│   ├── header.php             # Header chung
│   ├── footer.php             # Footer chung
│   └── db                     # Kết nối database
├── *.php                      # Các trang chính
├── motel_db.sql               # Database schema
└── README.md                  # Hướng dẫn này
```

## 👤 Tài khoản mặc định

### Admin
- **Username**: admin
- **Password**: admin123
- **Role**: admin

### Guest
- **Username**: customer1
- **Password**: customer123
- **Role**: guest

## 📋 Hướng dẫn sử dụng

### Cho khách hàng
1. **Đăng ký tài khoản** tại trang đăng ký
2. **Xem phòng trống** và chọn phòng phù hợp
3. **Đặt phòng** hoặc thêm vào giỏ hàng
4. **Thanh toán** phí giữ chỗ hoặc tiền cọc
5. **Theo dõi hóa đơn** hàng tháng và thanh toán

### Cho quản trị viên
1. **Đăng nhập** với tài khoản admin
2. **Quản lý phòng** (thêm, sửa, xóa)
3. **Cập nhật chỉ số điện** hàng tháng
4. **Tạo hóa đơn** cho tất cả phòng đang thuê
5. **Quản lý người dùng** và theo dõi hệ thống

## 🔒 Bảo mật

- **Mã hóa mật khẩu** với `password_hash()`
- **Xác thực session** trên mọi trang
- **Phân quyền truy cập** theo role
- **SQL injection protection** với prepared statements
- **XSS protection** với `htmlspecialchars()`

## 🚀 Tính năng nâng cao

### Responsive Design
- Tương thích với mọi thiết bị
- Mobile-first approach
- Touch-friendly interface

### User Experience
- Loading animations
- Auto-hide notifications
- Confirmation dialogs
- Form validation

### Performance
- Optimized CSS
- Efficient database queries
- Minimal JavaScript

## 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng liên hệ:
- **Email**: support@motelmanager.com
- **Phone**: 0123-456-789
- **Website**: https://motelmanager.com

## 📄 License

Dự án này được phát triển bởi HTTTDT Team cho mục đích học tập và nghiên cứu.

---

**© 2024 HTTTDT Team. All rights reserved.**
