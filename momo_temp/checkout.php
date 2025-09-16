<?php
session_start();

$subtotal = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Thanh toán</title>
  <link rel="stylesheet" href="styles.css">
 </head>
 <body>
   <header class="site-header">
     <div class="bar">
       <div class="brand"><span class="logo"></span><span>ForHer Shop</span></div>
       <nav class="nav">
         <a class="btn" href="cart.php">Giỏ hàng</a>
       </nav>
     </div>
   </header>
   <div class="container">
   <div class="toolbar">
     <h2>Thanh toán</h2>
     <span class="badge">An toàn - Nhanh chóng</span>
   </div>
   <div class="summary">
     <div class="row"><div>Tạm tính</div><div><?php echo number_format($subtotal); ?> đ</div></div>
     <div class="row"><div>Phí vận chuyển</div><div>0 đ</div></div>
     <hr>
     <div class="row"><strong>Tổng cộng</strong><strong><?php echo number_format($subtotal); ?> đ</strong></div>
     <?php if ($subtotal > 0): ?>
       <form action="fh_amt_momo.php" method="post" style="margin-top:12px">
         <input type="hidden" name="soTien" value="<?php echo (int)$subtotal; ?>">
         <div class="row">
           <div>Phương thức</div>
           <div>
             <label><input type="radio" name="method" value="wallet" checked> MoMo QR/Wallet</label>
             <label style="margin-left:8px"><input type="radio" name="method" value="atm"> Thẻ nội địa (ATM)</label>
             <label style="margin-left:8px"><input type="radio" name="method" value="credit"> Thẻ quốc tế (Visa/Master/JCB)</label>
           </div>
         </div>
         <button type="submit" name="payUrl" class="btn">Thanh toán MoMo</button>
       </form>
     <?php else: ?>
       <p>Giỏ hàng trống. <a href="shop.php">Quay lại cửa hàng</a></p>
     <?php endif; ?>
     <div style="margin-top:8px"><a class="btn ghost" href="cart.php">Quay lại giỏ hàng</a></div>
   </div>
   </div>
   <footer class="site-footer">© <?php echo date('Y'); ?> ForHer Shop</footer>
 </body>
 </html>