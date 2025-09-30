<?php
session_start();
require_once 'momo_config.php';
require_once 'vnpay_config.php';

function execPostRequest($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Get payment details from POST request
$amount = isset($_POST["amount"]) ? intval($_POST["amount"]) : 0;
$orderInfo = isset($_POST["orderInfo"]) ? $_POST["orderInfo"] : "Thanh toán đặt phòng motel";
$orderId = isset($_POST["orderId"]) ? $_POST["orderId"] : time() . "";
$paymentMethod = isset($_POST['method']) ? $_POST['method'] : 'wallet';

// Store payment info in session for callback
if (!isset($_SESSION['momo_payment'])) {
    $_SESSION['momo_payment'] = array();
}
$_SESSION['momo_payment']['orderId'] = $orderId;
$_SESSION['momo_payment']['amount'] = $amount;
$_SESSION['momo_payment']['paymentMethod'] = $paymentMethod;

if ($paymentMethod === 'vnpay') {
    // --- VNPay Payment Logic ---
    
    $vnpayConfig = getVNPayConfig();
    
    // Prepare VNPay data
    $vnp_TxnRef = $orderId; // Mã đơn hàng
    $vnp_OrderInfo = $orderInfo;
    $vnp_OrderType = 'billpayment';
    $vnp_Amount = $amount * 100; // VNPay yêu cầu số tiền tính bằng đơn vị nhỏ nhất (VND * 100)
    $vnp_Locale = 'vn';
    $vnp_BankCode = ''; // Để trống để hiển thị tất cả phương thức
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

    $inputData = array(
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnpayConfig['vnp_TmnCode'],
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnpayConfig['vnp_Returnurl'],
        "vnp_TxnRef" => $vnp_TxnRef
    );

    if ($vnp_BankCode != "") {
        $inputData['vnp_BankCode'] = $vnp_BankCode;
    }

    // Sắp xếp dữ liệu theo thứ tự alphabet
    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";
    
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnpayConfig['vnp_Url'] . "?" . $query;
    
    // Tạo secure hash
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnpayConfig['vnp_HashSecret']);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

    // Redirect to VNPay
    header('Location: ' . $vnp_Url);
    exit();
    
} else {
    // --- MoMo Payment Logic ---
    
    $endpoint = MOMO_ENDPOINT;
    $partnerCode = MOMO_PARTNER_CODE;
    $accessKey = MOMO_ACCESS_KEY;
    $secretKey = MOMO_SECRET_KEY;
    
    $redirectUrl = getMomoRedirectUrl();
    $ipnUrl = getMomoIpnUrl();
    $extraData = "";
    $requestId = time() . "";
    
    // Choose requestType based on user's selection
    if ($paymentMethod === 'atm') {
        $requestType = MOMO_METHOD_ATM;
    } elseif ($paymentMethod === 'credit') {
        $requestType = MOMO_METHOD_CREDIT;
    } else {
        $requestType = MOMO_METHOD_WALLET;
    }
    
    // Create signature
    $signature = createMomoSignature($accessKey, $amount, $extraData, $ipnUrl, $orderId, $orderInfo, $partnerCode, $redirectUrl, $requestId, $requestType, $secretKey);
    
    $data = array(
        'partnerCode' => $partnerCode,
        'partnerName' => MOMO_PARTNER_NAME,
        "storeId" => MOMO_STORE_ID,
        'requestId' => $requestId,
        'amount' => $amount,
        'orderId' => $orderId,
        'orderInfo' => $orderInfo,
        'redirectUrl' => $redirectUrl,
        'ipnUrl' => $ipnUrl,
        'lang' => 'vi',
        'extraData' => $extraData,
        'requestType' => $requestType,
        'signature' => $signature
    );
    
    $result = execPostRequest($endpoint, json_encode($data));
    $jsonResult = json_decode($result, true);
    
    if (isset($jsonResult['payUrl'])) {
        header('Location: ' . $jsonResult['payUrl']);
        exit();
    } else {
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Lỗi thanh toán</title></head><body>";
        echo "<h2>Khởi tạo thanh toán thất bại</h2>";
        echo "<pre>" . htmlspecialchars($result) . "</pre>";
        echo "<a href='payment_form.php'>Quay lại</a>";
        echo "</body></html>";
    }
}
?>