<?php
session_start();
require_once 'includes/db.php';

// 1. Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 2. Kiểm tra xem có nhận được ID phòng từ form POST hay không
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['room_id'])) {
    $room_id = $_POST['room_id'];
    
    // 3. Cập nhật trạng thái của phòng trong cơ sở dữ liệu
    $sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    
    if ($stmt->execute()) {
        // Cập nhật thành công, chuyển hướng người dùng về trang chủ
        $_SESSION['success_message'] = "Bạn đã thuê phòng thành công!";
        header('Location: index.php');
        exit();
    } else {
        // Cập nhật thất bại
        $_SESSION['error_message'] = "Có lỗi xảy ra, không thể thuê phòng.";
        header('Location: index.php');
        exit();
    }
} else {
    // Không nhận được ID phòng, chuyển hướng về trang chủ
    header('Location: index.php');
    exit();
}
?>