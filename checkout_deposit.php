<?php
session_start();
require_once 'includes/db';

if (!isset($_SESSION['user_id']) || !isset($_GET['room_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = $_GET['room_id'];

$sql = "SELECT rent_price, room_number FROM rooms WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    header('Location: index.php');
    exit();
}

// Calculate remaining deposit = monthly rent - paid reservation fee
$reservation_fee_paid = 0;
$res_sql = "SELECT fee_amount FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'paid' ORDER BY created_at DESC LIMIT 1";
$res_stmt = $conn->prepare($res_sql);
$res_stmt->bind_param("ii", $user_id, $room_id);
$res_stmt->execute();
$res_row = $res_stmt->get_result()->fetch_assoc();
if ($res_row && isset($res_row['fee_amount'])) {
    $reservation_fee_paid = (float)$res_row['fee_amount'];
}

$monthly_rent = (float)$room['rent_price'];
$deposit_amount = max($monthly_rent - $reservation_fee_paid, 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['room_id']) && isset($_POST['deposit_amount'])) {
    // Validate input
    if (!is_numeric($room_id) || !is_numeric($deposit_amount) || $deposit_amount <= 0) {
        $_SESSION['error_message'] = "Dữ liệu không hợp lệ.";
        header('Location: index.php');
        exit();
    }
    
    // Create payment record with pending status first
    $conn->begin_transaction();
    try {
        // Check if room is reserved by this user
        $check_reservation_sql = "SELECT id FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'paid'";
        $check_res_stmt = $conn->prepare($check_reservation_sql);
        $check_res_stmt->bind_param("ii", $user_id, $room_id);
        $check_res_stmt->execute();
        $reservation_result = $check_res_stmt->get_result();
        
        if ($reservation_result->num_rows === 0) {
            throw new Exception("Bạn chưa có quyền thanh toán tiền cọc cho phòng này.");
        }
        
        // Check if user already has a pending deposit payment for this room
        $check_pending_sql = "SELECT id FROM payments WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
        $check_pending_stmt = $conn->prepare($check_pending_sql);
        $check_pending_stmt->bind_param("ii", $user_id, $room_id);
        $check_pending_stmt->execute();
        $pending_result = $check_pending_stmt->get_result();
        
        if ($pending_result->num_rows > 0) {
            throw new Exception("Bạn đã có một giao dịch thanh toán tiền cọc đang chờ xử lý.");
        }
        
        $insert_payment_sql = "INSERT INTO payments (user_id, room_id, total_amount, payment_type, payment_status, payment_date) VALUES (?, ?, ?, 'deposit', 'pending', NOW())";
        $insert_payment_stmt = $conn->prepare($insert_payment_sql);
        $insert_payment_stmt->bind_param("iid", $user_id, $room_id, $deposit_amount);
        $insert_payment_stmt->execute();

        $conn->commit();
        
        // Redirect to MoMo payment
        $orderId = "deposit_" . $room_id . "_" . $user_id . "_" . time();
        $orderInfo = "Thanh toán tiền cọc phòng " . $room['room_number'];
        
        // Store payment info in session for MoMo
        $_SESSION['momo_payment'] = [
            'orderId' => $orderId,
            'amount' => $deposit_amount,
            'orderInfo' => $orderInfo,
            'paymentType' => 'deposit',
            'roomId' => $room_id,
            'userId' => $user_id
        ];
        
        // Redirect to MoMo payment page
        header('Location: momo_payment_form.php');
        exit();
        
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Thanh toán thất bại: " . $e->getMessage();
        header('Location: index.php');
        exit();
    }
}
?>

<?php
$page_title = "Thanh toán tiền cọc";
require_once 'includes/header.php';
?>

<div class="card">
    <h2><i class="fas fa-credit-card"></i> Thanh toán tiền cọc</h2>
    <p>Bạn đang thực hiện thanh toán tiền cọc để ký hợp đồng chính thức.</p>
</div>

<div class="card">
    <h3><i class="fas fa-bed"></i> Thông tin phòng</h3>
    <div style="background: rgba(102, 126, 234, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #667eea;">
        <h4>Phòng <?php echo htmlspecialchars($room['room_number']); ?></h4>
        <p><strong><i class="fas fa-money-bill-wave"></i> Giá thuê hàng tháng:</strong> <?php echo number_format($room['rent_price']); ?> VND</p>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-credit-card"></i> Thông tin thanh toán</h3>
    <div style="background: rgba(255, 193, 7, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #ffc107;">
        <p><strong><i class="fas fa-money-bill-wave"></i> Tiền thuê 1 tháng:</strong> <?php echo number_format($monthly_rent); ?> VND</p>
        <p><strong><i class="fas fa-receipt"></i> Đã thanh toán giữ chỗ:</strong> -<?php echo number_format($reservation_fee_paid); ?> VND</p>
        <p><strong><i class="fas fa-hand-holding-usd"></i> Tiền cọc còn lại:</strong> <?php echo number_format($deposit_amount); ?> VND</p>
        <p style="margin-top: 1rem; color: #666;">
            <i class="fas fa-info-circle"></i> 
            Sau khi thanh toán thành công, bạn sẽ chính thức thuê phòng và có thể bắt đầu sử dụng.
        </p>
    </div>
    
    <form action="checkout_deposit.php?room_id=<?php echo $room_id; ?>" method="POST" style="margin-top: 2rem;">
        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
        <input type="hidden" name="deposit_amount" value="<?php echo $deposit_amount; ?>">
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-success" style="flex: 1;">
                <i class="fas fa-credit-card"></i> Thanh toán qua MoMo
            </button>
            <a href="index.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                <i class="fas fa-arrow-left"></i> Hủy bỏ
            </a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>