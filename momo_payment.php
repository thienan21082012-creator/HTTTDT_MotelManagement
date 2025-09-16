<?php
// MoMo Payment Gateway for Motel Management System
require_once 'momo_config.php';

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
    //execute post
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);
    return $result;
}

// MoMo API Configuration
$endpoint = MOMO_ENDPOINT;
$partnerCode = MOMO_PARTNER_CODE;
$accessKey = MOMO_ACCESS_KEY;
$secretKey = MOMO_SECRET_KEY;

// Get payment details from POST request
$amount = $_POST["amount"];
$orderInfo = $_POST["orderInfo"] ?? "Thanh toán đặt phòng motel";
$orderId = $_POST["orderId"] ?? time() . "";

// Use local endpoints
$redirectUrl = getMomoRedirectUrl();
$ipnUrl = getMomoIpnUrl();
$extraData = "";

$requestId = time() . "";
// Choose requestType based on user's selection
$method = isset($_POST['method']) ? $_POST['method'] : 'wallet';
if ($method === 'atm') {
    $requestType = MOMO_METHOD_ATM; // Thẻ nội địa
} elseif ($method === 'credit') {
    $requestType = MOMO_METHOD_CREDIT; // Thẻ quốc tế
} else {
    $requestType = MOMO_METHOD_WALLET; // Ví/QR MoMo
}

//before sign HMAC SHA256 signature
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
$jsonResult = json_decode($result, true);  // decode json

if (isset($jsonResult['payUrl'])) {
    header('Location: ' . $jsonResult['payUrl']);
} else {
    echo "Khởi tạo thanh toán thất bại.";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
}
?>
