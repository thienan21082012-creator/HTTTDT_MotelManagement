<?php
session_start();
require_once 'includes/db';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['room_id'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = (int) $_POST['room_id'];
    $created_at = date('Y-m-d H:i:s');
    
    if (!is_numeric($room_id) || $room_id <= 0) {
        $_SESSION['error_message'] = "Dữ liệu không hợp lệ.";
        header('Location: cart.php');
        exit();
    }
    
    // Kiểm tra xem phòng đã có trong giỏ hàng chưa
    $check_sql = "SELECT id FROM carts WHERE user_id = ? AND room_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $room_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['error_message'] = "Phòng này đã có trong giỏ hàng của bạn.";
    } else {
        // Thêm phòng vào giỏ hàng
        $sql = "INSERT INTO carts (user_id, room_id, created_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $room_id, $created_at);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Phòng đã được thêm vào giỏ hàng.";
        } else {
            $_SESSION['error_message'] = "Có lỗi xảy ra, không thể thêm phòng vào giỏ hàng.";
        }
    }
}

header('Location: cart.php');
exit();
?>