<?php
// MoMo Payment Gateway Configuration
// This file contains all MoMo API configuration settings

// MoMo API Configuration
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');

// Test credentials (replace with production credentials when going live)
define('MOMO_PARTNER_CODE', 'MOMO4MUD20240115_TEST');
define('MOMO_ACCESS_KEY', 'Ekj9og2VnRfOuIys');
define('MOMO_SECRET_KEY', 'PseUbm2s8QVJEbexsh8H3Jz2qa9tDqoa');

// Production credentials (uncomment when going live)
/*
define('MOMO_PARTNER_CODE', 'MOMOBKUN20180529');
define('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j');
define('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa');
*/

// Store information
define('MOMO_PARTNER_NAME', 'Motel Management System');
define('MOMO_STORE_ID', 'MotelStore');

// Payment URLs
function getMomoRedirectUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $scheme . $_SERVER['HTTP_HOST'];
    
    // Get the current script directory
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if ($script_dir === '/' || $script_dir === '\\') {
        $script_dir = '';
    }
    
    return $host . $script_dir . "/momo_success.php";
}

function getMomoIpnUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $scheme . $_SERVER['HTTP_HOST'];
    
    // Get the current script directory
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if ($script_dir === '/' || $script_dir === '\\') {
        $script_dir = '';
    }
    
    return $host . $script_dir . "/momo_ipn.php";
}

// Payment method types
define('MOMO_METHOD_WALLET', 'captureWallet');
define('MOMO_METHOD_ATM', 'payWithATM');
define('MOMO_METHOD_CREDIT', 'payWithCC');

// Helper function to generate order ID
function generateMomoOrderId($type, $roomId, $userId) {
    return $type . "_" . $roomId . "_" . $userId . "_" . time();
}

// Helper function to create MoMo signature
function createMomoSignature($accessKey, $amount, $extraData, $ipnUrl, $orderId, $orderInfo, $partnerCode, $redirectUrl, $requestId, $requestType, $secretKey) {
    $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
    return hash_hmac("sha256", $rawHash, $secretKey);
}
?>
