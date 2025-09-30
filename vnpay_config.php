<?php
// VNPay Configuration
date_default_timezone_set('Asia/Ho_Chi_Minh');
define('VNPAY_TMN_CODE', 'HDCN3PD1'); // Mã website tại VNPAY
define('VNPAY_HASH_SECRET', 'ZGDE2VJWD68XWKLRXAQYTBTJF56C8NO8'); // Chuỗi bí mật
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'); // URL thanh toán (sandbox)
// URL thanh toán thật: https://vnpayment.vn/paymentv2/vpcpay.html

// Return URL - nơi VNPAY redirect về sau khi thanh toán
define('VNPAY_RETURN_URL', 'http://localhost/HTTTDT_MotelManagement/vnpay_return.php');

// Helper function to get VNPay config
function getVNPayConfig() {
    return array(
        'vnp_TmnCode' => VNPAY_TMN_CODE,
        'vnp_HashSecret' => VNPAY_HASH_SECRET,
        'vnp_Url' => VNPAY_URL,
        'vnp_Returnurl' => VNPAY_RETURN_URL
    );
}
?>