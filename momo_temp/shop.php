<?php
// Start session only when needed and not in products-only include mode
if ((!defined('SHOP_PRODUCTS_ONLY') || !SHOP_PRODUCTS_ONLY) && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple product catalog (phone cases)
$products = [
    [
        'id' => 'case-clear-iphone',
        'name' => 'Ốp Doraemon',
        'price' => 99000,
    ],
    [
        'id' => 'case-rugged-samsung',
        'name' => 'Ốp Spiderman',
        'price' => 129000,        
    ],
    [
        'id' => 'case-silicone-xiaomi',
        'name' => 'Ốp hoạt hình dễ thương',
        'price' => 79000,        
    ],
    [
        'id' => 'case-vit-con',
        'name' => 'Ốp vịt concon',
        'price' => 69000,    
    ],
    [
        'id' => 'case-co-gaigai',
        'name' => 'Ốp cô gái',
        'price' => 129000,    
    ],[
        'id' => 'case-cun-con',
        'name' => 'Ốp cún con',
        'price' => 99000,        
    ],
    [
        'id' => 'case-gau-dubi',
        'name' => 'Ốp gấu dubi',
        'price' => 109000,        
    ],
    [
        'id' => 'case-heo-concon',
        'name' => 'Ốp heo con',
        'price' => 89000, 
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'] ?? '';
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $catalog = [];
    foreach ($products as $p) {
        $catalog[$p['id']] = $p;
    }
    if (!isset($catalog[$productId])) {
        header('Location: shop.php');
        exit;
    }
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = [
            'id' => $catalog[$productId]['id'],
            'name' => $catalog[$productId]['name'],
            'price' => $catalog[$productId]['price'],
            'image' => $catalog[$productId]['image'],
            'quantity' => 0,
        ];
    }
    $_SESSION['cart'][$productId]['quantity'] += $quantity;
    header('Location: cart.php');
    exit;
}

// Allow including this file to access $products without rendering the page
if (defined('SHOP_PRODUCTS_ONLY') && SHOP_PRODUCTS_ONLY) {
    return;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cửa hàng ốp lưng</title>
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
        <h2>Ốp lưng điện thoại</h2>
        <span class="badge">Miễn phí vận chuyển</span>
      </div>
      <div class="grid">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                <p class="price"><?php echo number_format($p['price']); ?> đ</p>
                <form method="post">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($p['id']); ?>">
                    <div class="toolbar" style="margin:8px 0 0 0">
                      <input class="qty" type="number" name="quantity" min="1" value="1">
                      <button class="btn" type="submit" name="add_to_cart">Thêm vào giỏ</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
      </div>
    </div>
    <footer class="site-footer">© <?php echo date('Y'); ?> ForHer Shop</footer>
  </body>
</html>


