<?php
session_start();
require_once 'includes/db';

$page_title = "Kết quả thanh toán";
require_once 'includes/header.php';

// Get payment result from MoMo
$resultCode = isset($_GET['resultCode']) ? (int)$_GET['resultCode'] : null;
$message = isset($_GET['message']) ? $_GET['message'] : '';
$orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';
$amount = isset($_GET['amount']) ? $_GET['amount'] : '';

// Debug information (remove in production)
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo '<div class="card" style="background: #f8f9fa; border: 1px solid #dee2e6;">';
    echo '<h3><i class="fas fa-bug"></i> Debug Information</h3>';
    echo '<p><strong>All GET parameters:</strong></p>';
    echo '<pre>' . htmlspecialchars(print_r($_GET, true)) . '</pre>';
    echo '</div>';
}

// Parse orderId to get payment details
$orderParts = explode('_', $orderId);
$paymentType = $orderParts[0] ?? '';
$roomId = '';
$userId = '';
$billId = '';
if ($paymentType === 'bill') {
    $billId = $orderParts[1] ?? '';
    $userId = $orderParts[2] ?? '';
} else {
    $roomId = $orderParts[1] ?? '';
    $userId = $orderParts[2] ?? '';
}

// Get room information
$room_info = null;
if ($roomId) {
    try {
        $room_sql = "SELECT room_number FROM rooms WHERE id = ?";
        $room_stmt = $conn->prepare($room_sql);
        $room_stmt->bind_param("i", $roomId);
        $room_stmt->execute();
        $room_result = $room_stmt->get_result();
        $room_info = $room_result->fetch_assoc();
    } catch (Exception $e) {
        // Log error but don't show to user
        error_log("Error getting room info: " . $e->getMessage());
    }
}

// Fallback: if payment succeeded and IPN hasn't updated yet, apply updates here
try {
    if ($resultCode === 0 && $paymentType === 'reservation' && $roomId && $userId) {
        $conn->begin_transaction();

        // Lock room
        $lock_sql = "SELECT id, status FROM rooms WHERE id = ? FOR UPDATE";
        $lock_stmt = $conn->prepare($lock_sql);
        $lock_stmt->bind_param("i", $roomId);
        $lock_stmt->execute();
        $lock_res = $lock_stmt->get_result();
        $locked_room = $lock_res->fetch_assoc();

        if ($locked_room) {
            // Update reservation pending -> paid (idempotent)
            $upd_res_sql = "UPDATE reservations SET status = 'paid' WHERE user_id = ? AND room_id = ? AND status = 'pending'";
            $upd_res_stmt = $conn->prepare($upd_res_sql);
            $upd_res_stmt->bind_param("ii", $userId, $roomId);
            $upd_res_stmt->execute();

            // Set room to reserved only if available
            $upd_room_sql = "UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'";
            $upd_room_stmt = $conn->prepare($upd_room_sql);
            $upd_room_stmt->bind_param("i", $roomId);
            $upd_room_stmt->execute();

            // Remove from cart
            $del_cart_sql = "DELETE FROM carts WHERE user_id = ? AND room_id = ?";
            $del_cart_stmt = $conn->prepare($del_cart_sql);
            $del_cart_stmt->bind_param("ii", $userId, $roomId);
            $del_cart_stmt->execute();
        }

        $conn->commit();
    }
    // Fallback for deposit success: mark payment completed, occupy room, delete reservation
    if ($resultCode === 0 && $paymentType === 'deposit' && $roomId && $userId) {
        $conn->begin_transaction();

        // Complete any pending deposit payment
        $upd_pay_sql = "UPDATE payments SET payment_status = 'completed' WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
        $upd_pay_stmt = $conn->prepare($upd_pay_sql);
        $upd_pay_stmt->bind_param('ii', $userId, $roomId);
        $upd_pay_stmt->execute();

        // Occupy the room
        $upd_room_sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
        $upd_room_stmt = $conn->prepare($upd_room_sql);
        $upd_room_stmt->bind_param('i', $roomId);
        $upd_room_stmt->execute();

        // Remove reservation if exists
        $del_res_sql = "DELETE FROM reservations WHERE user_id = ? AND room_id = ?";
        $del_res_stmt = $conn->prepare($del_res_sql);
        $del_res_stmt->bind_param('ii', $userId, $roomId);
        $del_res_stmt->execute();

        $conn->commit();
    }
    // Fallback for bill payment: mark bill as paid
    if ($resultCode === 0 && $paymentType === 'bill' && $billId && $userId) {
        $upd_bill_sql = "UPDATE bills SET status = 'paid' WHERE id = ? AND user_id = ? AND status = 'unpaid'";
        $upd_bill_stmt = $conn->prepare($upd_bill_sql);
        $upd_bill_stmt->bind_param('ii', $billId, $userId);
        $upd_bill_stmt->execute();
    }
} catch (Throwable $e) {
    if ($conn && $conn->errno === 0) {
        @$conn->rollback();
    }
    error_log("MoMo success fallback update failed: " . $e->getMessage());
}
?>

<div class="card">
    <h2><i class="fas fa-credit-card"></i> Kết quả thanh toán</h2>
    
    <?php if ($resultCode === 0): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <strong>Thanh toán thành công!</strong>
        </div>
        
        <div style="background: rgba(40, 167, 69, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #28a745; margin: 1rem 0;">
            <h4><i class="fas fa-info-circle"></i> Thông tin giao dịch</h4>
            <p><strong>Mã đơn hàng:</strong> <?php echo htmlspecialchars($orderId); ?></p>
            <p><strong>Số tiền:</strong> <?php echo number_format($amount); ?> VND</p>
            <?php if ($room_info): ?>
                <p><strong>Phòng:</strong> <?php echo htmlspecialchars($room_info['room_number']); ?></p>
            <?php endif; ?>
            <p><strong>Thời gian:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <?php if ($paymentType === 'reservation'): ?>
            <div class="alert alert-info">
                <i class="fas fa-calendar-check"></i>
                <strong>Đặt phòng thành công!</strong><br>
                Phòng đã được giữ chỗ. Vui lòng thanh toán tiền cọc để ký hợp đồng chính thức.
            </div>
        <?php elseif ($paymentType === 'deposit'): ?>
            <div class="alert alert-success">
                <i class="fas fa-home"></i>
                <strong>Ký hợp đồng thành công!</strong><br>
                Bạn đã chính thức thuê phòng. Chúc bạn có những ngày ở trọ vui vẻ!
            </div>
        <?php endif; ?>
        
    <?php elseif ($resultCode !== null): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Thanh toán không thành công</strong>
        </div>
        
        <div style="background: rgba(220, 53, 69, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #dc3545; margin: 1rem 0;">
            <h4><i class="fas fa-info-circle"></i> Thông tin lỗi</h4>
            <p><strong>Mã lỗi:</strong> <?php echo (int)$resultCode; ?></p>
            <p><strong>Thông báo:</strong> <?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php
        // Mark pending deposit payment as failed if user canceled or payment failed
        try {
            if ($paymentType === 'deposit' && $roomId && $userId) {
                $upd_pay_sql = "UPDATE payments SET payment_status = 'failed' WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
                $upd_pay_stmt = $conn->prepare($upd_pay_sql);
                $upd_pay_stmt->bind_param('ii', $userId, $roomId);
                $upd_pay_stmt->execute();
            }
        } catch (Throwable $e) {
            error_log('Mark deposit failed on return error: ' . $e->getMessage());
        }
        ?>
        
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-question-circle"></i>
            <strong>Không có thông tin giao dịch</strong>
        </div>
    <?php endif; ?>
    
    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
        <a href="index.php" class="btn btn-primary" style="flex: 1; text-align: center;">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
        <?php if ($resultCode === 0 && $paymentType === 'reservation'): ?>
            <a href="checkout_deposit.php?room_id=<?php echo $roomId; ?>" class="btn btn-success" style="flex: 1; text-align: center;">
                <i class="fas fa-credit-card"></i> Thanh toán tiền cọc
            </a>
        <?php elseif ($paymentType === 'bill'): ?>
            <a href="index.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                <i class="fas fa-times"></i> Hủy thanh toán
            </a>
        <?php else: ?>
            <a href="cancel_reservation.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                <i class="fas fa-times"></i> Hủy giữ chỗ
            </a>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
