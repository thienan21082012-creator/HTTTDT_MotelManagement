<?php
session_start();
require_once 'includes/db';

$page_title = "Thanh toán";
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
    <h2><i class="fas fa-credit-card"></i> Thanh toán</h2>
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
        <input type="hidden" name="orderId" value="<?php echo htmlspecialchars($orderId); ?>">
        <input type="hidden" name="orderInfo" value="<?php echo htmlspecialchars($orderInfo); ?>">
        
        <div style="background: rgba(255, 193, 7, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #ffc107; margin: 1rem 0;">
            <h4><i class="fas fa-mobile-alt"></i> Phương thức thanh toán</h4>
            
            <div style="margin: 1rem 0;">
                <!-- MoMo Wallet -->
                <label style="display: flex; align-items: center; margin: 0.5rem 0; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#c2185b'; this.style.background='rgba(194, 24, 91, 0.05)';" onmouseout="this.style.borderColor='#e0e0e0'; this.style.background='transparent';">
                    <input type="radio" name="method" value="wallet" checked style="margin-right: 0.5rem;">
                    <i class="fas fa-wallet" style="color: #c2185b; margin-right: 0.5rem; font-size: 1.2rem;"></i>
                    <div style="flex: 1;">
                        <strong>Ví MoMo / QR Code</strong>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Quét mã QR hoặc thanh toán qua ví MoMo</div>
                    </div>
                    <span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">Khuyến nghị</span>
                </label>
                <!-- ATM Card -->
                <label style="display: flex; align-items: center; margin: 0.5rem 0; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#007bff'; this.style.background='rgba(0, 123, 255, 0.05)';" onmouseout="this.style.borderColor='#e0e0e0'; this.style.background='transparent';">
                    <input type="radio" name="method" value="atm" style="margin-right: 0.5rem;">
                    <i class="fas fa-credit-card" style="color: #007bff; margin-right: 0.5rem; font-size: 1.2rem;"></i>
                    <div style="flex: 1;">
                        <strong>Thẻ ATM nội địa</strong>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Thanh toán qua thẻ ngân hàng nội địa</div>
                    </div>
                </label>
                
                <!-- Credit Card -->
                <label style="display: flex; align-items: center; margin: 0.5rem 0; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#28a745'; this.style.background='rgba(40, 167, 69, 0.05)';" onmouseout="this.style.borderColor='#e0e0e0'; this.style.background='transparent';">
                    <input type="radio" name="method" value="credit" style="margin-right: 0.5rem;">
                    <i class="fas fa-globe" style="color: #28a745; margin-right: 0.5rem; font-size: 1.2rem;"></i>
                    <div style="flex: 1;">
                        <strong>Thẻ quốc tế (Visa/Master/JCB)</strong>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Thanh toán qua thẻ tín dụng/ghi nợ quốc tế</div>
                    </div>
                </label>
                
                <!-- VNPay -->
                <label style="display: flex; align-items: center; margin: 0.5rem 0; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#0088cc'; this.style.background='rgba(0, 136, 204, 0.05)';" onmouseout="this.style.borderColor='#e0e0e0'; this.style.background='transparent';">
                    <input type="radio" name="method" value="vnpay" style="margin-right: 0.5rem;">
                    <div style="width: 30px; height: 30px; background: #0088cc; border-radius: 5px; display: flex; align-items: center; justify-content: center; margin-right: 0.5rem;">
                        <span style="color: white; font-weight: bold; font-size: 0.7rem;">VNP</span>
                    </div>
                    <div style="flex: 1;">
                        <strong>VNPay</strong>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Thanh toán qua cổng VNPay</div>
                    </div>
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
            <i class="fas fa-lock"></i> Giao dịch được bảo mật với mã hóa SSL 256-bit
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>