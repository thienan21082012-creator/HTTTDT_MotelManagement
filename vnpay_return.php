<?php
session_start();
require_once 'includes/db'; // Đảm bảo đã include db
require_once 'vnpay_config.php'; // Chắc chắn rằng file này tồn tại và cung cấp config

// Khối logic xử lý VNPay
$vnpayConfig = getVNPayConfig();
$vnp_SecureHash = $_GET['vnp_SecureHash'];
$inputData = [];

// Lấy tất cả các tham số 'vnp_'
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

// Loại bỏ SecureHash để tính toán lại
unset($inputData['vnp_SecureHash']);
ksort($inputData);
$hashData = "";
foreach ($inputData as $key => $value) {
    $hashData .= urlencode($key) . '=' . urlencode($value) . '&';
}
$hashData = rtrim($hashData, '&');

$secureHash = hash_hmac('sha512', $hashData, $vnpayConfig['vnp_HashSecret']);

// --- CHỈ XÁC THỰC VÀ CHUYỂN HƯỚNG ---
if ($secureHash === $vnp_SecureHash) {
    // Chuyển hướng đến trang kết quả chung (momo_success.php)
    // Các tham số VNPay sẽ được trang đích xử lý
    $redirectUrl = 'momo_success.php?' . http_build_query($_GET);
    header('Location: ' . $redirectUrl);
    exit();
} else {
    // Nếu chữ ký không hợp lệ, chuyển hướng với mã lỗi chung
    $errorParams = [
        'vnp_ResponseCode' => '97', // Mã lỗi chữ ký không hợp lệ
        'vnp_TxnRef' => isset($_GET['vnp_TxnRef']) ? $_GET['vnp_TxnRef'] : 'UNKNOWN',
        'vnp_Amount' => isset($_GET['vnp_Amount']) ? $_GET['vnp_Amount'] : 0,
        'message' => 'Chữ ký giao dịch VNPay không hợp lệ!'
    ];
    $redirectUrl = 'momo_success.php?' . http_build_query($errorParams);
    header('Location: ' . $redirectUrl);
    exit();
}

// Các đoạn mã HTML và logic cập nhật database phía dưới bị loại bỏ
// vì việc cập nhật và hiển thị sẽ được xử lý tại momo_success.php
?>