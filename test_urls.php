<?php
// Test URL generation for MoMo
require_once 'momo_config.php';

echo "<h2>MoMo URL Test</h2>";

echo "<h3>Current Server Info:</h3>";
echo "<p><strong>HTTP_HOST:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>SCRIPT_NAME:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>SERVER_NAME:</strong> " . $_SERVER['SERVER_NAME'] . "</p>";

echo "<h3>Generated URLs:</h3>";
echo "<p><strong>Redirect URL:</strong> " . getMomoRedirectUrl() . "</p>";
echo "<p><strong>IPN URL:</strong> " . getMomoIpnUrl() . "</p>";

echo "<h3>Test Links:</h3>";
echo "<p><a href='" . getMomoRedirectUrl() . "?resultCode=0&orderId=test_1_5&amount=500000&message=Test'>Test Success Page</a></p>";
echo "<p><a href='momo_success.php?resultCode=0&orderId=test_1_5&amount=500000&message=Test'>Direct Success Page</a></p>";
?>
