<?php
// Simple test file to check if momo_success.php is accessible
echo "<h1>Test Success Page</h1>";
echo "<p>If you can see this, the file is accessible.</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Server: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>Script: " . $_SERVER['SCRIPT_NAME'] . "</p>";

// Test with parameters
if (!empty($_GET)) {
    echo "<h2>GET Parameters:</h2>";
    echo "<ul>";
    foreach ($_GET as $key => $value) {
        echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
    }
    echo "</ul>";
}

echo "<p><a href='momo_success.php?resultCode=0&orderId=test_1_5&amount=500000&message=Test'>Test momo_success.php</a></p>";
?>
