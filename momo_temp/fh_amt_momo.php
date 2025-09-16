<?php
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

  $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

  $partnerCode = 'MOMO4MUD20240115_TEST';
  $accessKey = 'Ekj9og2VnRfOuIys';
  $secretKey = 'PseUbm2s8QVJEbexsh8H3Jz2qa9tDqoa';
/*
  $partnerCode = 'MOMOBKUN20180529';
  $accessKey = 'klm05TvNBzhg7h7j';
  $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
*/
  $orderInfo = "Thanh toán đơn hàng ốp lưng";
  $amount = $_POST["soTien"];
  $orderId = time() . ""; //hoặc random hay tuần tự
  // Use local endpoints
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
  $host = $scheme . $_SERVER['HTTP_HOST'];
  $redirectUrl = $host . "/thankyou.php";
  $ipnUrl = $host . "/momo_ipn.php";
  $extraData = "";
  
  
  //if (!empty($_POST)) {
  $partnerCode = $partnerCode;
  $accessKey = $accessKey;
  $serectkey = $secretKey;
  $orderId = $orderId; // Mã đơn hàng
  $orderInfo = $orderInfo;
  $amount = $amount; //$_POST["amount"];
  $ipnUrl = $ipnUrl; //$_POST["ipnUrl"];
  $redirectUrl = $redirectUrl; //$_POST["redirectUrl"];
  $extraData = $extraData; //$_POST["extraData"];
      
      $requestId = time() . "";
      // Choose requestType based on user's selection
      $method = isset($_POST['method']) ? $_POST['method'] : 'wallet';
      if ($method === 'atm') {
        $requestType = "payWithATM"; // Thẻ nội địa
      } elseif ($method === 'credit') {
        $requestType = "payWithCC"; // Thẻ quốc tế
      } else {
        $requestType = "captureWallet"; // Ví/QR MoMo
      }
      //$extraData = ($_POST["extraData"] ? $_POST["extraData"] : "");
      
      //before sign HMAC SHA256 signature
      $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
      $signature = hash_hmac("sha256", $rawHash, $serectkey);
      $data = array('partnerCode' => $partnerCode,
          'partnerName' => "Test",
          "storeId" => "MomoTestStore",
          'requestId' => $requestId,
          'amount' => $amount,
          'orderId' => $orderId,
          'orderInfo' => $orderInfo,
          'redirectUrl' => $redirectUrl,
          'ipnUrl' => $ipnUrl,
          'lang' => 'vi',
          'extraData' => $extraData,
          'requestType' => $requestType,
          'signature' => $signature);
      $result = execPostRequest($endpoint, json_encode($data));
      $jsonResult = json_decode($result, true);  // decode json
      
      //Just a example, please check more in there
      
      if (isset($jsonResult['payUrl'])) {
        header('Location: ' . $jsonResult['payUrl']);
      } else {
        echo "Khởi tạo thanh toán thất bại.";
        echo "<pre>" . htmlspecialchars($result) . "</pre>";
      }
  //} // if
 ?>