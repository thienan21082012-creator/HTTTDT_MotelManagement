<?php
session_start();

// Initialize cart if missing
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_qty'])) {
        $postedQty = isset($_POST['qty']) && is_array($_POST['qty']) ? $_POST['qty'] : array();
        foreach ($postedQty as $productId => $qty) {
            $quantity = max(0, (int)$qty);
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } elseif (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
            }
        }
        header('Location: cart.php');
        exit;
    }
    if (isset($_POST['remove']) && isset($_POST['product_id'])) {
        $pid = $_POST['product_id'];
        unset($_SESSION['cart'][$pid]);
        header('Location: cart.php');
        exit;
    }
}

// Load product catalog from shop.php (products only) for image fallback
$catalog = array();
if (file_exists(__DIR__ . '/shop.php')) {
    define('SHOP_PRODUCTS_ONLY', true);
    include __DIR__ . '/shop.php';
    if (isset($products) && is_array($products)) {
        foreach ($products as $p) {
            if (isset($p['id'])) {
                $catalog[$p['id']] = $p;
            }
        }
    }
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="styles.css">
  </head>
  <body>
    <header class="site-header">
      <div class="bar">
        <div class="brand"><span class="logo"></span><span>ForHer Shop</span></div>
        <nav class="nav">
          <a class="btn" href="shop.php">Tiếp tục mua sắm</a>
        </nav>
      </div>
    </header>
    <div class="container">
      <div class="toolbar">
        <h2>Giỏ hàng</h2>
        <span class="badge">Miễn phí vận chuyển</span>
      </div>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty">Giỏ hàng trống.</div>
    <?php else: ?>
        <form method="post">
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th class="right">Thành tiền</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td>
                              <div style="display:flex;align-items:center;gap:10px">
                                <?php $img = isset($item['image']) && $item['image'] !== '' ? $item['image'] : (isset($catalog[$item['id']]['image']) ? $catalog[$item['id']]['image'] : 'https://via.placeholder.com/80'); ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="" style="width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                                <div><?php echo htmlspecialchars($item['name']); ?></div>
                              </div>
                            </td>
                            <td><?php echo number_format($item['price']); ?> đ</td>
                            <td>
                                <input class="qty" type="number" name="qty[<?php echo htmlspecialchars($item['id']); ?>]" min="0" value="<?php echo (int)$item['quantity']; ?>">
                            </td>
                            <td class="right"><?php echo number_format($item['price'] * $item['quantity']); ?> đ</td>
                            <td class="actions">
                                <button class="btn" name="remove" value="1" type="submit" formaction="cart.php" formmethod="post" onclick="this.form.product_id.value='<?php echo htmlspecialchars($item['id']); ?>'">Xóa</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="right">Tạm tính</th>
                        <th class="right"><?php echo number_format($subtotal); ?> đ</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
            <input type="hidden" name="product_id" value="">
            <div class="toolbar">
                <button class="btn" name="update_qty" value="1" type="submit">Cập nhật giỏ</button>
                <a class="btn secondary" href="checkout.php">Thanh toán</a>
            </div>
        </form>
    <?php endif; ?>
    </div>
    <footer class="site-footer">© <?php echo date('Y'); ?> ForHer Shop</footer>
  </body>
</html>


