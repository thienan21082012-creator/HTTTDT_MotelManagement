<?php
$page_title = "Đặt phòng";
require_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['room_id'])) {
    header('Location: view_rooms.php');
    exit();
}

$room_id = $_GET['room_id'];
$sql = "SELECT room_number, description, rent_price, status FROM rooms WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room || $room['status'] != 'available') {
    echo '<div class="card"><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Phòng này không thể đặt được.</div></div>';
    exit();
}

$reservation_fee = 500000;
?>

<div class="card">
    <h2><i class="fas fa-calendar-plus"></i> Xác nhận đặt phòng</h2>
    <p>Bạn đang thực hiện đặt phòng. Vui lòng xem lại thông tin và xác nhận.</p>
</div>

<div class="card">
    <h3><i class="fas fa-bed"></i> Thông tin phòng</h3>
    <div style="background: rgba(102, 126, 234, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #667eea;">
        <h4>Phòng <?php echo htmlspecialchars($room['room_number']); ?></h4>
        <p><strong><i class="fas fa-info-circle"></i> Mô tả:</strong> <?php echo htmlspecialchars($room['description']); ?></p>
        <p><strong><i class="fas fa-money-bill-wave"></i> Giá thuê hàng tháng:</strong> <?php echo number_format($room['rent_price']); ?> VND</p>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-credit-card"></i> Thông tin thanh toán</h3>
    <div style="background: rgba(255, 193, 7, 0.1); padding: 1.5rem; border-radius: 15px; border-left: 4px solid #ffc107;">
        <p><strong><i class="fas fa-hand-holding-usd"></i> Phí giữ chỗ:</strong> <?php echo number_format($reservation_fee); ?> VND</p>
        <p style="margin-top: 1rem; color: #666;">
            <i class="fas fa-info-circle"></i> 
            Phí giữ chỗ này sẽ được khấu trừ vào tiền cọc khi bạn thanh toán để ký hợp đồng chính thức.
        </p>
    </div>
    
    <form action="process_reservation.php" method="POST" style="margin-top: 2rem;">
        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
        <input type="hidden" name="fee_amount" value="<?php echo $reservation_fee; ?>">
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-success" style="flex: 1;">
                <i class="fas fa-credit-card"></i> Thanh toán qua MoMo
            </button>
            <a href="view_rooms.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                <i class="fas fa-arrow-left"></i> Hủy bỏ
            </a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>