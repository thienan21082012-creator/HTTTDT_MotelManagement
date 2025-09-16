<?php
session_start();
require_once 'includes/db';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    $roomId = null;
    $billId = null;
    $paymentType = null;

    if (isset($_SESSION['momo_payment'])) {
        $paymentType = $_SESSION['momo_payment']['paymentType'] ?? null;
        $roomId = $_SESSION['momo_payment']['roomId'] ?? null;
        $billId = $_SESSION['momo_payment']['billId'] ?? null;
    }

    // Handle deposit cancellation: delete pending payments
    if ($paymentType === 'deposit' && $roomId) {
        $delSql = "DELETE FROM payments WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
        $delStmt = $conn->prepare($delSql);
        $delStmt->bind_param('ii', $userId, $roomId);
        $delStmt->execute();

        $_SESSION['error_message'] = 'Đã hủy thanh toán tiền cọc.';
    } elseif ($paymentType === 'reservation' && $roomId) {
        // If canceling from reservation flow, delegate to reservation cancel
        header('Location: cancel_reservation.php?room_id=' . (int)$roomId);
        exit();
    } elseif ($paymentType === 'bill' && $billId) {
        // For bill cancel, nothing to roll back in DB; bill remains unpaid
        $_SESSION['error_message'] = 'Đã hủy thanh toán hóa đơn.';
    } else {
        $_SESSION['error_message'] = 'Không có giao dịch đang chờ để hủy.';
    }

    // Clear context
    if (isset($_SESSION['momo_payment'])) {
        unset($_SESSION['momo_payment']);
    }

    header('Location: index.php');
    exit();

} catch (Throwable $e) {
    $_SESSION['error_message'] = 'Không thể hủy thanh toán: ' . $e->getMessage();
    header('Location: index.php');
    exit();
}
?>


