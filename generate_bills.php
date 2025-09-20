<?php
session_start();
require_once 'includes/db';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$current_month = date('n');
$current_year = date('Y');

$check_sql = "SELECT id FROM bills WHERE billing_month = ? AND billing_year = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $current_month, $current_year);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error_message'] = "Hóa đơn cho tháng này đã được tạo.";
    header('Location: dashboard.php');
    exit();
}

$rooms_sql = "SELECT id, rent_price FROM rooms WHERE status = 'occupied'";
$rooms_result = $conn->query($rooms_sql);

$electricity_price_per_unit = 3500;
$water_price_per_unit = 15000;
$service_fee = 100000;

while($room = $rooms_result->fetch_assoc()) {
    $room_id = $room['id'];
    $rent_amount = $room['rent_price'];

    // Lấy user_id và email từ bảng payments và users
    $user_sql = "SELECT u.id, u.email FROM users u JOIN payments p ON u.id = p.user_id WHERE p.room_id = ? AND p.payment_type = 'deposit' ORDER BY p.payment_date DESC LIMIT 1";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $room_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows == 0) {
        continue;
    }
    
    $user_data = $user_result->fetch_assoc();
    $user_id = $user_data['id'];
    $user_email = $user_data['email'];
    // Lấy chỉ số điện gần nhất để tính tiền
    $elec_sql = "SELECT old_reading, new_reading FROM electricity_readings WHERE room_id = ? ORDER BY reading_date DESC LIMIT 1";
    $elec_stmt = $conn->prepare($elec_sql);
    $elec_stmt->bind_param("i", $room_id);
    $elec_stmt->execute();
    $elec_result = $elec_stmt->get_result();
    $elec_data = $elec_result->fetch_assoc();
    
    $consumed_units = ($elec_data) ? ($elec_data['new_reading'] - $elec_data['old_reading']) : 0;
    $electricity_amount = $consumed_units * $electricity_price_per_unit;

    $water_amount = 100000;
    
    $total_amount = $rent_amount + $electricity_amount + $water_amount + $service_fee;

    // Tạo hóa đơn
    $insert_bill_sql = "INSERT INTO bills (user_id, room_id, billing_month, billing_year, rent_amount, electricity_amount, water_amount, service_fee, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')";
    $insert_bill_stmt = $conn->prepare($insert_bill_sql);
    $insert_bill_stmt->bind_param("iiiidddds", $user_id, $room_id, $current_month, $current_year, $rent_amount, $electricity_amount, $water_amount, $service_fee, $total_amount);
    $insert_bill_stmt->execute();

    if (!empty($user_email)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Thay bằng SMTP Host của bạn
            $mail->SMTPAuth = true;
            $mail->Username = 'email@gmail.com'; // Thay bằng email của bạn
            $mail->Password = 'mật khẩu ứng dụng'; // Thay bằng mật khẩu ứng dụng
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('thienan21082025@gmail.com', 'He thong Quan ly Nha Tro');
            $mail->addAddress($user_email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Thong bao hoa don tien nha thang ' . $current_month;
            
            $mail_body = "<h3>Kinh gui Khach hang,</h3>";
            $mail_body .= "<p>He thong thong bao da co hoa don tien nha thang " . $current_month . " cho phong " . $room_id . "</p>";
            $mail_body .= "<p>Vui long dang nhap de xem chi tiet va thanh toan.</p>";
            $mail_body .= "<p>Tong so tien can thanh toan: <strong>" . number_format($total_amount) . " VND</strong></p>";
            $mail_body .= "<p>Tran trong,</p>";
            $mail_body .= "<p>Ban Quan ly.</p>";
            
            $mail->Body = $mail_body;
            $mail->send();
            echo "Đã gửi mail $user_email";
        } catch (Exception $e) {
            // Có thể ghi log lỗi ở đây
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
} // Corrected closing brace for the while loop

$_SESSION['success_message'] = "Đã tạo hóa đơn và gửi email thông báo thành công.";
header('Location: dashboard.php');
exit();

?>

