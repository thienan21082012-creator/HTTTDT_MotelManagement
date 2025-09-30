<?php
session_start();
require_once 'includes/db';

$page_title = "Kết quả thanh toán";
require_once 'includes/header.php';

// Get payment result from MoMo or VNPay
$resultCode = null;
$message = '';
$orderId = '';
$amount = 0;
$paymentType = '';
$roomId = '';
$userId = '';
$billId = '';

if (isset($_GET['resultCode'])) { // MoMo
    $resultCode = (int)$_GET['resultCode'];
    $message = isset($_GET['message']) ? $_GET['message'] : '';
    $orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';
    $amount = isset($_GET['amount']) ? $_GET['amount'] : '';
    
    $orderParts = explode('_', $orderId);
    $paymentType = $orderParts[0] ?? '';
    if ($paymentType === 'bill') {
        $billId = $orderParts[1] ?? '';
        $userId = $orderParts[2] ?? '';
    } else {
        $roomId = $orderParts[1] ?? '';
        $userId = $orderParts[2] ?? '';
    }

} elseif (isset($_GET['vnp_ResponseCode'])) { // VNPay
    require_once 'vnpay_config.php';
    $vnpayConfig = getVNPayConfig();
    
    $vnp_SecureHash = $_GET['vnp_SecureHash'];
    $inputData = [];
    foreach ($_GET as $key => $value) {
        if (substr($key, 0, 4) == "vnp_") {
            $inputData[$key] = $value;
        }
    }
    unset($inputData['vnp_SecureHash']);
    ksort($inputData);
    $hashData = http_build_query($inputData, '', '&');
    $secureHash = hash_hmac('sha512', $hashData, $vnpayConfig['vnp_HashSecret']);

    if ($secureHash === $vnp_SecureHash) {
        $resultCode = ($_GET['vnp_ResponseCode'] == '00') ? 0 : 99; // 0 for success, 99 for others
        $message = ($_GET['vnp_ResponseCode'] == '00') ? 'Giao dịch thành công' : 'Giao dịch không thành công';
        $orderId = isset($_GET['vnp_TxnRef']) ? $_GET['vnp_TxnRef'] : '';
        $amount = isset($_GET['vnp_Amount']) ? ($_GET['vnp_Amount'] / 100) : 0;

        $orderParts = explode('_', $orderId);
        $paymentType = $orderParts[0] ?? '';
        if ($paymentType === 'bill') {
            $billId = $orderParts[1] ?? '';
            $userId = $orderParts[2] ?? '';
        } else {
            $roomId = $orderParts[1] ?? '';
            $userId = $orderParts[2] ?? '';
        }

    } else {
        $resultCode = 99;
        $message = 'Chữ ký không hợp lệ từ VNPay.';
    }
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
        error_log("Error getting room info: " . $e->getMessage());
    }
}

// Fallback logic for database updates
if ($resultCode === 0) {
    try {
        $conn->begin_transaction();
        
        // Handle Reservation Payment
        if ($paymentType === 'reservation') {
            // Update reservation status to 'paid'
            $upd_res_sql = "UPDATE reservations SET status = 'paid' WHERE user_id = ? AND room_id = ? AND status = 'pending'";
            $upd_res_stmt = $conn->prepare($upd_res_sql);
            $upd_res_stmt->bind_param("ii", $userId, $roomId);
            $upd_res_stmt->execute();

            // Update room status to 'reserved' if it was 'available'
            $upd_room_sql = "UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'";
            $upd_room_stmt = $conn->prepare($upd_room_sql);
            $upd_room_stmt->bind_param("i", $roomId);
            $upd_room_stmt->execute();

            // Remove room from user's cart
            $del_cart_sql = "DELETE FROM carts WHERE user_id = ? AND room_id = ?";
            $del_cart_stmt = $conn->prepare($del_cart_sql);
            $del_cart_stmt->bind_param("ii", $userId, $roomId);
            $del_cart_stmt->execute();

        } elseif ($paymentType === 'deposit') {
            // Handle Deposit Payment
            // Update payment record to 'completed'
            $upd_pay_sql = "UPDATE payments SET payment_status = 'completed' WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
            $upd_pay_stmt = $conn->prepare($upd_pay_sql);
            $upd_pay_stmt->bind_param('ii', $userId, $roomId);
            $upd_pay_stmt->execute();

            // Occupy the room
            $upd_room_sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
            $upd_room_stmt = $conn->prepare($upd_room_sql);
            $upd_room_stmt->bind_param('i', $roomId);
            $upd_room_stmt->execute();

            // Remove reservation if it exists
            $del_res_sql = "DELETE FROM reservations WHERE user_id = ? AND room_id = ?";
            $del_res_stmt = $conn->prepare($del_res_sql);
            $del_res_stmt->bind_param('ii', $userId, $roomId);
            $del_res_stmt->execute();

        } elseif ($paymentType === 'bill') {
            // Handle Bill Payment
            $upd_bill_sql = "UPDATE bills SET status = 'paid' WHERE id = ? AND user_id = ? AND status = 'unpaid'";
            $upd_bill_stmt = $conn->prepare($upd_bill_sql);
            $upd_bill_stmt->bind_param('ii', $billId, $userId);
            $upd_bill_stmt->execute();
        }

        $conn->commit();
    } catch (Throwable $e) {
        if ($conn && $conn->errno === 0) {
            @$conn->rollback();
        }
        error_log("Payment success fallback update failed: " . $e->getMessage());
    }
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
        <?php elseif ($paymentType === 'bill'): ?>
            <div class="alert alert-success">
                <i class="fas fa-file-invoice-dollar"></i>
                <strong>Thanh toán hóa đơn thành công!</strong><br>
                Hóa đơn của bạn đã được thanh toán.
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Thanh toán không thành công</strong>
        </div>
        
        <div style="background: rgba(220, 53, 69, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #dc3545; margin: 1rem 0;">
            <h4><i class="fas fa-info-circle"></i> Thông tin lỗi</h4>
            <p><strong>Mã lỗi:</strong> <?php echo (int)$resultCode; ?></p>
            <p><strong>Thông báo:</strong> <?php echo htmlspecialchars($message); ?></p>
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
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>