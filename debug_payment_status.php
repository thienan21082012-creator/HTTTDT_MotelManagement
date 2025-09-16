<?php
session_start();
require_once 'includes/db';

// Chỉ admin mới được truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$page_title = "Debug Payment Status";
include 'includes/header.php';
?>

<div class="card">
    <h2><i class="fas fa-bug"></i> Debug Payment Status</h2>
    <p>Trang này giúp kiểm tra trạng thái thanh toán và reservations.</p>
</div>

<div class="card">
    <h3><i class="fas fa-list"></i> Danh sách Reservations</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Room ID</th>
                    <th>Fee Amount</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $reservations_sql = "SELECT * FROM reservations ORDER BY created_at DESC";
                $reservations_result = $conn->query($reservations_sql);
                while($res = $reservations_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $res['id']; ?></td>
                        <td><?php echo $res['user_id']; ?></td>
                        <td><?php echo $res['room_id']; ?></td>
                        <td><?php echo number_format($res['fee_amount']); ?> VND</td>
                        <td>
                            <span class="room-status <?php echo $res['status'] == 'paid' ? 'status-available' : 'status-occupied'; ?>">
                                <?php echo $res['status']; ?>
                            </span>
                        </td>
                        <td><?php echo $res['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-credit-card"></i> Danh sách Payments</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Room ID</th>
                    <th>Total Amount</th>
                    <th>Payment Type</th>
                    <th>Payment Status</th>
                    <th>Start Date</th>
                    <th>Contract Duration</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $payments_sql = "SELECT * FROM payments ORDER BY payment_date DESC";
                $payments_result = $conn->query($payments_sql);
                while($pay = $payments_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $pay['id']; ?></td>
                        <td><?php echo $pay['user_id']; ?></td>
                        <td><?php echo $pay['room_id']; ?></td>
                        <td><?php echo number_format($pay['total_amount']); ?> VND</td>
                        <td><?php echo $pay['payment_type'] ?? 'N/A'; ?></td>
                        <td>
                            <span class="room-status <?php echo $pay['payment_status'] == 'completed' ? 'status-available' : 'status-occupied'; ?>">
                                <?php echo $pay['payment_status']; ?>
                            </span>
                        </td>
                        <td><?php echo $pay['start_date'] ?? 'N/A'; ?></td>
                        <td><?php echo $pay['contract_duration'] ?? 'N/A'; ?> tháng</td>
                        <td><?php echo $pay['payment_date']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-bed"></i> Trạng thái phòng</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Room ID</th>
                    <th>Room Number</th>
                    <th>Status</th>
                    <th>Rent Price</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rooms_sql = "SELECT * FROM rooms ORDER BY room_number ASC";
                $rooms_result = $conn->query($rooms_sql);
                while($room = $rooms_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $room['id']; ?></td>
                        <td><?php echo $room['room_number']; ?></td>
                        <td>
                            <span class="room-status status-<?php echo $room['status']; ?>">
                                <?php 
                                $status_text = [
                                    'available' => 'Trống',
                                    'occupied' => 'Đã thuê',
                                    'reserved' => 'Đã cọc'
                                ];
                                echo $status_text[$room['status']];
                                ?>
                            </span>
                        </td>
                        <td><?php echo number_format($room['rent_price']); ?> VND</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-tools"></i> Công cụ Debug</h3>
    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
        <a href="debug_payment_status.php" class="btn btn-success">
            <i class="fas fa-sync"></i> Refresh
        </a>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại Dashboard
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
