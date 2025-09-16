# Tích hợp Thanh toán MoMo cho Hệ thống Quản lý Motel

## Tổng quan
Hệ thống đã được tích hợp thanh toán MoMo để xử lý các giao dịch đặt phòng và thanh toán tiền cọc.

## Các file đã được tạo/cập nhật

### Files mới:
1. **momo_config.php** - Cấu hình MoMo API
2. **momo_payment.php** - Xử lý thanh toán MoMo
3. **momo_payment_form.php** - Form chọn phương thức thanh toán
4. **momo_ipn.php** - Xử lý thông báo thanh toán từ MoMo
5. **momo_success.php** - Trang kết quả thanh toán

### Files đã cập nhật:
1. **process_reservation.php** - Tích hợp MoMo cho đặt phòng
2. **checkout_deposit.php** - Tích hợp MoMo cho thanh toán tiền cọc
3. **reserve.php** - Cập nhật giao diện

## Luồng thanh toán

### 1. Đặt phòng (Reservation)
1. User chọn phòng và nhấn "Đặt phòng"
2. Hệ thống tạo record reservation với status 'pending'
3. Chuyển hướng đến `momo_payment_form.php`
4. User chọn phương thức thanh toán và xác nhận
5. Chuyển hướng đến MoMo gateway
6. Sau khi thanh toán thành công, MoMo gọi `momo_ipn.php`
7. Hệ thống cập nhật status reservation thành 'paid' và room status thành 'reserved'
8. User được chuyển hướng đến `momo_success.php`

### 2. Thanh toán tiền cọc (Deposit)
1. User nhấn "Thanh toán tiền cọc" từ dashboard
2. Hệ thống tạo record payment với status 'pending'
3. Chuyển hướng đến `momo_payment_form.php`
4. User chọn phương thức thanh toán và xác nhận
5. Chuyển hướng đến MoMo gateway
6. Sau khi thanh toán thành công, MoMo gọi `momo_ipn.php`
7. Hệ thống cập nhật payment status thành 'completed', room status thành 'occupied'
8. Xóa record reservation
9. User được chuyển hướng đến `momo_success.php`

## Cấu hình MoMo

### Test Environment (Hiện tại)
```php
MOMO_PARTNER_CODE = 'MOMO4MUD20240115_TEST'
MOMO_ACCESS_KEY = 'Ekj9og2VnRfOuIys'
MOMO_SECRET_KEY = 'PseUbm2s8QVJEbexsh8H3Jz2qa9tDqoa'
MOMO_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/create'
```

### Production Environment (Khi go-live)
```php
MOMO_PARTNER_CODE = 'MOMOBKUN20180529'
MOMO_ACCESS_KEY = 'klm05TvNBzhg7h7j'
MOMO_SECRET_KEY = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'
MOMO_ENDPOINT = 'https://payment.momo.vn/v2/gateway/api/create'
```

## Phương thức thanh toán hỗ trợ

1. **Ví MoMo / QR Code** (captureWallet) - Khuyến nghị
2. **Thẻ ATM nội địa** (payWithATM)
3. **Thẻ quốc tế** (payWithCC) - Visa/Master/JCB

## Cấu trúc Order ID

Order ID được tạo theo format: `{type}_{roomId}_{userId}_{timestamp}`

- **Reservation**: `reservation_1_5_1234567890`
- **Deposit**: `deposit_1_5_1234567890`

## Bảo mật

1. **Signature Verification**: Tất cả request từ MoMo đều được verify signature
2. **HTTPS**: Khuyến nghị sử dụng HTTPS cho production
3. **IPN Validation**: Xác thực tất cả thông báo từ MoMo

## Testing

### Test với MoMo Sandbox
1. Sử dụng test credentials trong `momo_config.php`
2. Test với số tiền nhỏ (ví dụ: 1000 VND)
3. Kiểm tra log trong `momo_ipn.php`

### Test Cases
1. Thanh toán thành công
2. Thanh toán thất bại
3. User hủy thanh toán
4. Timeout thanh toán

## Troubleshooting

### Lỗi thường gặp:
1. **"Khởi tạo thanh toán thất bại"**
   - Kiểm tra credentials trong `momo_config.php`
   - Kiểm tra kết nối internet
   - Kiểm tra URL endpoints

2. **"Invalid signature"**
   - Kiểm tra secret key
   - Kiểm tra format của raw hash

3. **"No input" trong IPN**
   - Kiểm tra URL IPN có thể truy cập từ internet
   - Kiểm tra server có thể nhận POST request

4. **"404 Not Found" sau thanh toán thành công**
   - Kiểm tra URL redirect trong `momo_config.php`
   - Đảm bảo file `momo_success.php` tồn tại
   - Kiểm tra đường dẫn thư mục project
   - Sử dụng `test_urls.php` để kiểm tra URL generation
   - Sử dụng `debug_momo.php` để debug thông tin từ MoMo

### Debug Tools:
- `test_momo_integration.php` - Test toàn bộ tích hợp
- `test_urls.php` - Test URL generation
- `debug_momo.php` - Debug thông tin từ MoMo
- `test_success.php` - Test accessibility của success page
- Thêm `?debug=1` vào URL momo_success.php để xem debug info

## Monitoring

### Log Files
- Error logs: `/var/log/apache2/error.log` hoặc tương tự
- Application logs: Thêm vào `momo_ipn.php`

### Database Monitoring
- Kiểm tra bảng `reservations` và `payments`
- Monitor status changes

## Deployment Checklist

1. ✅ Cập nhật credentials production
2. ✅ Cấu hình HTTPS
3. ✅ Test IPN endpoint
4. ✅ Cập nhật redirect URLs
5. ✅ Backup database
6. ✅ Test toàn bộ flow

## Support

Để được hỗ trợ:
1. Kiểm tra log files
2. Test với MoMo sandbox
3. Liên hệ MoMo support nếu cần
