<?php
session_start();
require_once 'includes/db.php';

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

    // Lấy user_id từ bảng payments
    $user_sql = "SELECT user_id FROM payments WHERE room_id = ? AND payment_type = 'deposit' ORDER BY payment_date DESC LIMIT 1";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $room_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    // Kiểm tra nếu không tìm thấy user_id, bỏ qua phòng này
    if ($user_result->num_rows == 0) {
        continue; // Bỏ qua phòng này và chuyển sang phòng tiếp theo
    }
    
    $user_id = $user_result->fetch_assoc()['user_id'];
    
    // Lấy chỉ số điện gần nhất để tính tiền
    $elec_sql = "SELECT old_reading, new_reading FROM electricity_readings WHERE room_id = ? ORDER BY reading_date DESC LIMIT 1";
    $elec_stmt = $conn->prepare($elec_sql);
    $elec_stmt->bind_param("i", $room_id);
    $elec_stmt->execute();
    $elec_result = $elec_stmt->get_result();
    $elec_data = $elec_result->fetch_assoc();
    
    // Sửa lỗi ở đây: Kiểm tra nếu có dữ liệu chỉ số điện
    $consumed_units = ($elec_data) ? ($elec_data['new_reading'] - $elec_data['old_reading']) : 0;
    $electricity_amount = $consumed_units * $electricity_price_per_unit;

    $water_amount = 100000;
    
    $total_amount = $rent_amount + $electricity_amount + $water_amount + $service_fee;

    // Tạo hóa đơn
    $insert_bill_sql = "INSERT INTO bills (user_id, room_id, billing_month, billing_year, rent_amount, electricity_amount, water_amount, service_fee, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')";
    $insert_bill_stmt = $conn->prepare($insert_bill_sql);
    $insert_bill_stmt->bind_param("iiiidddds", $user_id, $room_id, $current_month, $current_year, $rent_amount, $electricity_amount, $water_amount, $service_fee, $total_amount);
    $insert_bill_stmt->execute();
}

$_SESSION['success_message'] = "Đã tạo hóa đơn cho tháng " . $current_month . " thành công.";
header('Location: dashboard.php');
exit();
?>