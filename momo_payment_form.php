<?php
session_start();
require_once 'includes/db';

$page_title = "Thanh toán MoMo";
require_once 'includes/header.php';

if (!isset($_SESSION['momo_payment'])) {
    header('Location: index.php');
    exit();
}

$payment_info = $_SESSION['momo_payment'];
$orderId = $payment_info['orderId'];
$amount = $payment_info['amount'];
$orderInfo = $payment_info['orderInfo'];
$paymentType = $payment_info['paymentType'];
$roomId = $payment_info['roomId'] ?? null;
$billId = $payment_info['billId'] ?? null;

// Get display information depending on payment type
$displayRoomNumber = null;
if ($paymentType === 'bill' && $billId) {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $bill_sql = "SELECT r.room_number FROM bills b JOIN rooms r ON b.room_id = r.id WHERE b.id = ? AND b.user_id = ?";
    $bill_stmt = $conn->prepare($bill_sql);
    $bill_stmt->bind_param("ii", $billId, $userId);
    $bill_stmt->execute();
    $bill_res = $bill_stmt->get_result();
    $bill_row = $bill_res->fetch_assoc();
    if ($bill_row) {
        $displayRoomNumber = $bill_row['room_number'];
    }
} elseif ($roomId) {
    $room_sql = "SELECT room_number, rent_price FROM rooms WHERE id = ?";
    $room_stmt = $conn->prepare($room_sql);
    $room_stmt->bind_param("i", $roomId);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();
    $room = $room_result->fetch_assoc();
    if ($room) {
        $displayRoomNumber = $room['room_number'];
    }
}
?>

<div class="card">
    <h2><i class="fas fa-credit-card"></i> Thanh toán qua MoMo</h2>
    <p>Vui lòng chọn phương thức thanh toán và xác nhận giao dịch.</p>
</div>

<div class="card">
    <h3><i class="fas fa-bed"></i> Thông tin đơn hàng</h3>
    <div style="background: rgba(102, 126, 234, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #667eea;">
        <?php if ($displayRoomNumber): ?>
            <p><strong><i class="fas fa-home"></i> Phòng:</strong> <?php echo htmlspecialchars($displayRoomNumber); ?></p>
        <?php endif; ?>
        <p><strong><i class="fas fa-info-circle"></i> Mô tả:</strong> <?php echo htmlspecialchars($orderInfo); ?></p>
        <p><strong><i class="fas fa-money-bill-wave"></i> Số tiền:</strong> <?php echo number_format($amount); ?> VND</p>
        <p><strong><i class="fas fa-barcode"></i> Mã đơn hàng:</strong> <?php echo htmlspecialchars($orderId); ?></p>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-credit-card"></i> Chọn phương thức thanh toán</h3>
    
    <form action="momo_payment.php" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
        <input type="hidden" name="orderId" value="<?php echo $orderId; ?>">
        <input type="hidden" name="orderInfo" value="<?php echo htmlspecialchars($orderInfo); ?>">
        
        <div style="background: rgba(255, 193, 7, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #ffc107; margin: 1rem 0;">
            <h4><i class="fas fa-mobile-alt"></i> Phương thức thanh toán</h4>
            
            <div style="margin: 1rem 0;">
                <label style="display: flex; align-items: center; margin: 0.5rem 0; cursor: pointer;">
                    <input type="radio" name="method" value="wallet" checked style="margin-right: 0.5rem;">
                    <i class="fas fa-wallet" style="color: #c2185b; margin-right: 0.5rem;"></i>
                    <strong>Ví MoMo / QR Code</strong>
                    <span style="margin-left: auto; color: #666;">Khuyến nghị</span>
                </label>
                
                <label style="display: flex; align-items: center; margin: 0.5rem 0; cursor: pointer;">
                    <input type="radio" name="method" value="atm" style="margin-right: 0.5rem;">
                    <i class="fas fa-credit-card" style="color: #007bff; margin-right: 0.5rem;"></i>
                    <strong>Thẻ ATM nội địa</strong>
                </label>
                
                <label style="display: flex; align-items: center; margin: 0.5rem 0; cursor: pointer;">
                    <input type="radio" name="method" value="credit" style="margin-right: 0.5rem;">
                    <i class="fas fa-globe" style="color: #28a745; margin-right: 0.5rem;"></i>
                    <strong>Thẻ quốc tế (Visa/Master/JCB)</strong>
                </label>

                <label style="display: flex; align-items: center; margin: 0.5rem 0; cursor: pointer;">
                    <input type="radio" name="method" value="linkWallet" style="margin-right: 0.5rem;">
                    <i class="fas fa-globe" style="color: #28a745; margin-right: 0.5rem;"></i>
                    <strong>Thanh toán tự động</strong>
                </label>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-success" style="flex: 1;">
                <i class="fas fa-credit-card"></i> Thanh toán ngay
            </button>
            <a href="cancel_payment.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                <i class="fas fa-arrow-left"></i> Hủy bỏ
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div style="background: rgba(40, 167, 69, 0.1); padding: 1rem; border-radius: 10px; border-left: 4px solid #28a745;">
        <h4><i class="fas fa-shield-alt"></i> Bảo mật thanh toán</h4>
        <p style="margin: 0; color: #666; font-size: 0.9rem;">
            <i class="fas fa-lock"></i> Giao dịch được bảo mật bởi MoMo với mã hóa SSL 256-bit
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
