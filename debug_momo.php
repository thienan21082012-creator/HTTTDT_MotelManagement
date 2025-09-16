<?php
// Debug file for MoMo payment issues
session_start();
require_once 'includes/db';

$page_title = "Debug MoMo Payment";
require_once 'includes/header.php';

echo '<div class="card">';
echo '<h2><i class="fas fa-bug"></i> Debug MoMo Payment</h2>';
echo '<p>File này giúp debug các vấn đề với thanh toán MoMo.</p>';
echo '</div>';

// Debug GET parameters
echo '<div class="card">';
echo '<h3><i class="fas fa-list"></i> GET Parameters from MoMo</h3>';
if (!empty($_GET)) {
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<tr style="background: #f8f9fa;"><th style="border: 1px solid #dee2e6; padding: 8px;">Parameter</th><th style="border: 1px solid #dee2e6; padding: 8px;">Value</th></tr>';
    foreach ($_GET as $key => $value) {
        echo '<tr>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;"><strong>' . htmlspecialchars($key) . '</strong></td>';
        echo '<td style="border: 1px solid #dee2e6; padding: 8px;">' . htmlspecialchars($value) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>Không có GET parameters.</p>';
}
echo '</div>';

// Debug orderId parsing
echo '<div class="card">';
echo '<h3><i class="fas fa-cogs"></i> Order ID Parsing</h3>';
$orderId = $_GET['orderId'] ?? '';
if ($orderId) {
    $orderParts = explode('_', $orderId);
    echo '<p><strong>Order ID:</strong> ' . htmlspecialchars($orderId) . '</p>';
    echo '<p><strong>Parts:</strong></p>';
    echo '<ul>';
    foreach ($orderParts as $index => $part) {
        echo '<li>Part ' . $index . ': ' . htmlspecialchars($part) . '</li>';
    }
    echo '</ul>';
    
    $paymentType = $orderParts[0] ?? '';
    $roomId = $orderParts[1] ?? '';
    $userId = $orderParts[2] ?? '';
    
    echo '<p><strong>Parsed:</strong></p>';
    echo '<ul>';
    echo '<li>Payment Type: ' . htmlspecialchars($paymentType) . '</li>';
    echo '<li>Room ID: ' . htmlspecialchars($roomId) . '</li>';
    echo '<li>User ID: ' . htmlspecialchars($userId) . '</li>';
    echo '</ul>';
} else {
    echo '<p>Không có Order ID.</p>';
}
echo '</div>';

// Debug database queries
echo '<div class="card">';
echo '<h3><i class="fas fa-database"></i> Database Debug</h3>';
$orderId = $_GET['orderId'] ?? '';
if ($orderId) {
    $orderParts = explode('_', $orderId);
    $roomId = $orderParts[1] ?? '';
    
    if ($roomId) {
        try {
            $room_sql = "SELECT id, room_number, status FROM rooms WHERE id = ?";
            $room_stmt = $conn->prepare($room_sql);
            $room_stmt->bind_param("i", $roomId);
            $room_stmt->execute();
            $room_result = $room_stmt->get_result();
            $room_info = $room_result->fetch_assoc();
            
            if ($room_info) {
                echo '<p><strong>Room Info:</strong></p>';
                echo '<ul>';
                echo '<li>ID: ' . $room_info['id'] . '</li>';
                echo '<li>Room Number: ' . htmlspecialchars($room_info['room_number']) . '</li>';
                echo '<li>Status: ' . htmlspecialchars($room_info['status']) . '</li>';
                echo '</ul>';
            } else {
                echo '<p style="color: red;">Room not found with ID: ' . htmlspecialchars($roomId) . '</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">Database error: ' . $e->getMessage() . '</p>';
        }
    }
}
echo '</div>';

// Debug session
echo '<div class="card">';
echo '<h3><i class="fas fa-user"></i> Session Debug</h3>';
echo '<p><strong>Session ID:</strong> ' . session_id() . '</p>';
echo '<p><strong>User ID:</strong> ' . ($_SESSION['user_id'] ?? 'Not set') . '</p>';
echo '<p><strong>MoMo Payment Info:</strong></p>';
if (isset($_SESSION['momo_payment'])) {
    echo '<pre>' . htmlspecialchars(print_r($_SESSION['momo_payment'], true)) . '</pre>';
} else {
    echo '<p>No MoMo payment info in session.</p>';
}
echo '</div>';

// Debug server info
echo '<div class="card">';
echo '<h3><i class="fas fa-server"></i> Server Info</h3>';
echo '<p><strong>HTTP_HOST:</strong> ' . $_SERVER['HTTP_HOST'] . '</p>';
echo '<p><strong>REQUEST_URI:</strong> ' . $_SERVER['REQUEST_URI'] . '</p>';
echo '<p><strong>SCRIPT_NAME:</strong> ' . $_SERVER['SCRIPT_NAME'] . '</p>';
echo '<p><strong>SERVER_NAME:</strong> ' . $_SERVER['SERVER_NAME'] . '</p>';
echo '</div>';

require_once 'includes/footer.php';
?>
