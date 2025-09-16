<?php
// Simple thank you page after returning from MoMo
// MoMo will append query parameters like resultCode, message, orderId, amount, etc.
$resultCode = isset($_GET['resultCode']) ? (int)$_GET['resultCode'] : null;
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kết quả thanh toán</title>
  <link rel="stylesheet" href="bitnami.css">
  <style>
    body{font-family: Arial, Helvetica, sans-serif; margin: 20px}
    .card{max-width:560px;margin:0 auto;border:1px solid #eee;border-radius:8px;padding:16px}
    .btn{background:#c2185b;color:#fff;border:none;border-radius:6px;padding:10px 14px;cursor:pointer;text-decoration:none;display:inline-block}
  </style>
 </head>
 <body>
   <div class="card">
     <h2>Kết quả thanh toán</h2>
     <?php if ($resultCode === 0): ?>
       <p>Thanh toán thành công!</p>
     <?php elseif ($resultCode !== null): ?>
       <p>Thanh toán không thành công. Mã lỗi: <?php echo (int)$resultCode; ?></p>
       <p><?php echo htmlspecialchars($message); ?></p>
     <?php else: ?>
       <p>Không có thông tin giao dịch.</p>
     <?php endif; ?>
     <a class="btn" href="shop.php">Tiếp tục mua sắm</a>
   </div>
 </body>
 </html>


