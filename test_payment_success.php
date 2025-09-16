<?php
session_start();
require_once 'includes/db';

// Chỉ admin mới được truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$page_title = "Test Payment Success";
include 'includes/header.php';

// Xử lý test payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_payment'])) {
    $orderId = $_POST['orderId'];
    $resultCode = 0; // Success
    $amount = $_POST['amount'];
    $transId = 'TEST_' . time();
    
    // Parse orderId to get payment type and room_id
    $orderParts = explode('_', $orderId);
    $paymentType = $orderParts[0]; // 'reservation' or 'deposit'
    $roomId = $orderParts[1];
    $userId = $orderParts[2];
    
    echo '<div class="card">';
    echo '<h3><i class="fas fa-info-circle"></i> Test Payment Processing</h3>';
    echo '<p><strong>Order ID:</strong> ' . htmlspecialchars($orderId) . '</p>';
    echo '<p><strong>Payment Type:</strong> ' . htmlspecialchars($paymentType) . '</p>';
    echo '<p><strong>Room ID:</strong> ' . htmlspecialchars($roomId) . '</p>';
    echo '<p><strong>User ID:</strong> ' . htmlspecialchars($userId) . '</p>';
    echo '<p><strong>Amount:</strong> ' . number_format($amount) . ' VND</p>';
    echo '</div>';
    
    // Start transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        if ($paymentType === 'reservation') {
            // Update reservation status
            $update_sql = "UPDATE reservations SET status = 'paid' WHERE user_id = ? AND room_id = ? AND status = 'pending'";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $userId, $roomId);
            $stmt->execute();
            
            // Check if reservation was updated
            if ($stmt->affected_rows > 0) {
                // Update room status to reserved
                $update_room_sql = "UPDATE rooms SET status = 'reserved' WHERE id = ?";
                $room_stmt = $conn->prepare($update_room_sql);
                $room_stmt->bind_param("i", $roomId);
                $room_stmt->execute();
                
                echo '<div class="alert alert-success">';
                echo '<i class="fas fa-check-circle"></i> Reservation payment processed successfully!';
                echo '</div>';
            } else {
                echo '<div class="alert alert-error">';
                echo '<i class="fas fa-exclamation-triangle"></i> No pending reservation found for this order.';
                echo '</div>';
            }
            
        } elseif ($paymentType === 'deposit') {
            // Update payment status for deposit
            $update_sql = "UPDATE payments SET payment_status = 'completed' WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $userId, $roomId);
            $stmt->execute();
            
            // Check if payment was updated
            if ($stmt->affected_rows > 0) {
                // Update room status to occupied
                $update_room_sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
                $room_stmt = $conn->prepare($update_room_sql);
                $room_stmt->bind_param("i", $roomId);
                $room_stmt->execute();
                
                // Delete reservation record
                $delete_res_sql = "DELETE FROM reservations WHERE user_id = ? AND room_id = ?";
                $delete_stmt = $conn->prepare($delete_res_sql);
                $delete_stmt->bind_param("ii", $userId, $roomId);
                $delete_stmt->execute();
                
                echo '<div class="alert alert-success">';
                echo '<i class="fas fa-check-circle"></i> Deposit payment processed successfully!';
                echo '</div>';
            } else {
                echo '<div class="alert alert-error">';
                echo '<i class="fas fa-exclamation-triangle"></i> No pending payment found for this order.';
                echo '</div>';
            }
        }
        
        // Commit transaction
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo '<div class="alert alert-error">';
        echo '<i class="fas fa-exclamation-triangle"></i> Error: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<div class="card">
    <h2><i class="fas fa-vial"></i> Test Payment Success</h2>
    <p>Trang này giúp test thủ công việc xử lý thanh toán thành công.</p>
</div>

<div class="card">
    <h3><i class="fas fa-play"></i> Test Payment Processing</h3>
    <form method="POST">
        <div class="form-group">
            <label for="orderId">Order ID (format: type_roomId_userId):</label>
            <input type="text" id="orderId" name="orderId" class="form-control" 
                   placeholder="reservation_3_5" required>
        </div>
        
        <div class="form-group">
            <label for="amount">Amount (VND):</label>
            <input type="number" id="amount" name="amount" class="form-control" 
                   placeholder="1000000" required>
        </div>
        
        <button type="submit" name="test_payment" class="btn btn-success">
            <i class="fas fa-play"></i> Test Payment Success
        </button>
    </form>
</div>

<div class="card">
    <h3><i class="fas fa-info-circle"></i> Hướng dẫn sử dụng</h3>
    <ul>
        <li><strong>Reservation:</strong> Sử dụng format "reservation_roomId_userId" (VD: reservation_3_5)</li>
        <li><strong>Deposit:</strong> Sử dụng format "deposit_roomId_userId" (VD: deposit_3_5)</li>
        <li>Đảm bảo có bản ghi pending trong database trước khi test</li>
    </ul>
</div>

<div style="display: flex; gap: 1rem; margin-top: 2rem;">
    <a href="debug_payment_status.php" class="btn btn-secondary">
        <i class="fas fa-bug"></i> Debug Payment Status
    </a>
    <a href="dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Quay lại Dashboard
    </a>
</div>

<?php include 'includes/footer.php'; ?>
