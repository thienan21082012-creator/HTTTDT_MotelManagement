<?php
// Test file for MoMo integration
session_start();
require_once 'includes/db';
require_once 'momo_config.php';

$page_title = "Test MoMo Integration";
require_once 'includes/header.php';

echo '<div class="card">';
echo '<h2><i class="fas fa-vial"></i> Test MoMo Integration</h2>';
echo '<p>File này dùng để test tích hợp MoMo. Chỉ sử dụng trong môi trường development.</p>';
echo '</div>';

// Test 1: Check configuration
echo '<div class="card">';
echo '<h3><i class="fas fa-cog"></i> Test 1: Configuration Check</h3>';

$config_tests = [
    'MOMO_ENDPOINT' => defined('MOMO_ENDPOINT') ? MOMO_ENDPOINT : 'NOT DEFINED',
    'MOMO_PARTNER_CODE' => defined('MOMO_PARTNER_CODE') ? MOMO_PARTNER_CODE : 'NOT DEFINED',
    'MOMO_ACCESS_KEY' => defined('MOMO_ACCESS_KEY') ? MOMO_ACCESS_KEY : 'NOT DEFINED',
    'MOMO_SECRET_KEY' => defined('MOMO_SECRET_KEY') ? MOMO_SECRET_KEY : 'NOT DEFINED',
];

foreach ($config_tests as $key => $value) {
    $status = $value !== 'NOT DEFINED' ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
    echo "<p>{$status} <strong>{$key}:</strong> " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) . "</p>";
}
echo '</div>';

// Test 2: Check database connection
echo '<div class="card">';
echo '<h3><i class="fas fa-database"></i> Test 2: Database Connection</h3>';
try {
    $test_sql = "SELECT COUNT(*) as count FROM rooms";
    $result = $conn->query($test_sql);
    if ($result) {
        $row = $result->fetch_assoc();
        echo '<p><span style="color: green;">✓</span> <strong>Database Connection:</strong> OK</p>';
        echo '<p><span style="color: green;">✓</span> <strong>Rooms Table:</strong> ' . $row['count'] . ' rooms found</p>';
    } else {
        echo '<p><span style="color: red;">✗</span> <strong>Database Connection:</strong> FAILED</p>';
    }
} catch (Exception $e) {
    echo '<p><span style="color: red;">✗</span> <strong>Database Connection:</strong> ERROR - ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Test 3: Check URL generation
echo '<div class="card">';
echo '<h3><i class="fas fa-link"></i> Test 3: URL Generation</h3>';
try {
    $redirect_url = getMomoRedirectUrl();
    $ipn_url = getMomoIpnUrl();
    
    echo '<p><span style="color: green;">✓</span> <strong>Redirect URL:</strong> ' . htmlspecialchars($redirect_url) . '</p>';
    echo '<p><span style="color: green;">✓</span> <strong>IPN URL:</strong> ' . htmlspecialchars($ipn_url) . '</p>';
    
    // Test if URLs are accessible
    echo '<h4>URL Accessibility Test:</h4>';
    $test_success_url = $redirect_url . '?resultCode=0&orderId=test_1_5&amount=500000&message=Test';
    echo '<p><a href="' . htmlspecialchars($test_success_url) . '" target="_blank">Test Success Page</a></p>';
    echo '<p><a href="momo_success.php?resultCode=0&orderId=test_1_5&amount=500000&message=Test" target="_blank">Direct Success Page</a></p>';
    
} catch (Exception $e) {
    echo '<p><span style="color: red;">✗</span> <strong>URL Generation:</strong> ERROR - ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Test 4: Check signature generation
echo '<div class="card">';
echo '<h3><i class="fas fa-key"></i> Test 4: Signature Generation</h3>';
try {
    $test_signature = createMomoSignature(
        MOMO_ACCESS_KEY,
        1000,
        '',
        getMomoIpnUrl(),
        'test_order_123',
        'Test payment',
        MOMO_PARTNER_CODE,
        getMomoRedirectUrl(),
        '1234567890',
        MOMO_METHOD_WALLET,
        MOMO_SECRET_KEY
    );
    
    if (strlen($test_signature) === 64) {
        echo '<p><span style="color: green;">✓</span> <strong>Signature Generation:</strong> OK</p>';
        echo '<p><strong>Test Signature:</strong> ' . substr($test_signature, 0, 20) . '...</p>';
    } else {
        echo '<p><span style="color: red;">✗</span> <strong>Signature Generation:</strong> INVALID LENGTH</p>';
    }
} catch (Exception $e) {
    echo '<p><span style="color: red;">✗</span> <strong>Signature Generation:</strong> ERROR - ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Test 5: Check file existence
echo '<div class="card">';
echo '<h3><i class="fas fa-file"></i> Test 5: File Existence</h3>';
$required_files = [
    'momo_config.php',
    'momo_payment.php',
    'momo_payment_form.php',
    'momo_ipn.php',
    'momo_success.php'
];

foreach ($required_files as $file) {
    $exists = file_exists($file);
    $status = $exists ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
    echo "<p>{$status} <strong>{$file}:</strong> " . ($exists ? 'EXISTS' : 'MISSING') . "</p>";
}
echo '</div>';

// Test 6: Simulate payment flow (if user is logged in)
if (isset($_SESSION['user_id'])) {
    echo '<div class="card">';
    echo '<h3><i class="fas fa-play"></i> Test 6: Simulate Payment Flow</h3>';
    echo '<p>User ID: ' . $_SESSION['user_id'] . '</p>';
    
    // Get first available room
    $room_sql = "SELECT id, room_number FROM rooms WHERE status = 'available' LIMIT 1";
    $room_result = $conn->query($room_sql);
    
    if ($room_result && $room_result->num_rows > 0) {
        $room = $room_result->fetch_assoc();
        echo '<p>Test Room: ' . $room['room_number'] . ' (ID: ' . $room['id'] . ')</p>';
        
        echo '<div style="margin-top: 1rem;">';
        echo '<a href="reserve.php?room_id=' . $room['id'] . '" class="btn btn-primary">Test Reservation Flow</a>';
        echo '</div>';
    } else {
        echo '<p><span style="color: orange;">⚠</span> No available rooms for testing</p>';
    }
    echo '</div>';
} else {
    echo '<div class="card">';
    echo '<h3><i class="fas fa-user"></i> Test 6: User Authentication</h3>';
    echo '<p><span style="color: orange;">⚠</span> Please login to test payment flow</p>';
    echo '<a href="login.php" class="btn btn-primary">Login</a>';
    echo '</div>';
}

echo '<div class="card">';
echo '<h3><i class="fas fa-info-circle"></i> Test Summary</h3>';
echo '<p>Nếu tất cả tests đều pass (✓), hệ thống MoMo đã sẵn sàng để sử dụng.</p>';
echo '<p>Nếu có tests fail (✗), vui lòng kiểm tra lại cấu hình và file.</p>';
echo '</div>';

require_once 'includes/footer.php';
?>
