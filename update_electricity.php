<?php
session_start();
require_once 'includes/db.php';

// Chỉ cho phép admin truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['room_id'])) {
    $room_id = $_POST['room_id'];
    $old_reading = $_POST['old_reading'];
    $new_reading = $_POST['new_reading'];
    $reading_date = $_POST['reading_date'];
    
    // Kiểm tra số điện mới phải lớn hơn hoặc bằng số điện cũ
    if ($new_reading < $old_reading) {
        $_SESSION['error_message'] = "Số điện mới không được nhỏ hơn số điện cũ.";
        header('Location: dashboard.php');
        exit();
    }
    
    $month = date('n', strtotime($reading_date));
    $year = date('Y', strtotime($reading_date));

    $sql = "INSERT INTO electricity_readings (room_id, month, year, old_reading, new_reading, reading_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiss", $room_id, $month, $year, $old_reading, $new_reading, $reading_date);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Cập nhật chỉ số điện thành công.";
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra, không thể cập nhật chỉ số điện.";
    }
}

header('Location: dashboard.php');
exit();
?>