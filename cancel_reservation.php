<?php
session_start();
require_once 'includes/db';

try {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = 'Bạn cần đăng nhập để hủy giữ chỗ.';
        header('Location: login.php');
        exit();
    }

    // If we have session payment info, use it to target the correct reservation
    $userId = $_SESSION['user_id'];
    $roomId = null;
    if (isset($_SESSION['momo_payment']['paymentType']) && $_SESSION['momo_payment']['paymentType'] === 'reservation') {
        $roomId = $_SESSION['momo_payment']['roomId'] ?? null;
    } elseif (isset($_GET['room_id']) && is_numeric($_GET['room_id'])) {
        $roomId = (int)$_GET['room_id'];
    }

    if ($roomId === null) {
        // Fallback: try to find the most recent pending reservation for this user
        $sqlLast = "SELECT room_id FROM reservations WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1";
        if ($stmtLast = $conn->prepare($sqlLast)) {
            $stmtLast->bind_param('i', $userId);
            $stmtLast->execute();
            $resLast = $stmtLast->get_result();
            if ($rowLast = $resLast->fetch_assoc()) {
                $roomId = (int)$rowLast['room_id'];
            }
        }
    }

    if ($roomId === null) {
        $_SESSION['error_message'] = 'Không tìm thấy đơn giữ chỗ đang chờ để hủy.';
        header('Location: view_rooms.php');
        exit();
    }

    $conn->begin_transaction();

    // Delete pending reservation for this user and room
    $delSql = "DELETE FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'pending'";
    $delStmt = $conn->prepare($delSql);
    $delStmt->bind_param('ii', $userId, $roomId);
    $delStmt->execute();

    // If room was temporarily marked reserved without payment (defensive), set back to available when no other paid/reserved record exists
    // Only adjust room if no 'paid' reservation exists for that room
    $checkPaidSql = "SELECT 1 FROM reservations WHERE room_id = ? AND status IN ('paid') LIMIT 1";
    $checkPaidStmt = $conn->prepare($checkPaidSql);
    $checkPaidStmt->bind_param('i', $roomId);
    $checkPaidStmt->execute();
    $hasPaid = $checkPaidStmt->get_result()->num_rows > 0;

    if (!$hasPaid) {
        $updateRoomSql = "UPDATE rooms SET status = 'available' WHERE id = ? AND status <> 'occupied'";
        $updateRoomStmt = $conn->prepare($updateRoomSql);
        $updateRoomStmt->bind_param('i', $roomId);
        $updateRoomStmt->execute();
    }

    $conn->commit();

    // Clear session payment context
    if (isset($_SESSION['momo_payment'])) {
        unset($_SESSION['momo_payment']);
    }

    $_SESSION['error_message'] = 'Đã hủy giữ chỗ cho phòng này.';
    header('Location: view_rooms.php');
    exit();

} catch (Throwable $e) {
    if ($conn && $conn->errno === 0) {
        // ensure transaction is rolled back if started
        @$conn->rollback();
    }
    $_SESSION['error_message'] = 'Không thể hủy giữ chỗ: ' . $e->getMessage();
    header('Location: view_rooms.php');
    exit();
}
?>


