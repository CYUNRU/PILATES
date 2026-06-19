<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// 檢查用戶是否登入
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 獲取購物車
$cart_query = "SELECT cart_id FROM shopping_carts WHERE user_id = ?";
$cart_stmt = $mysqli->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows === 0) {
    $cart_id = null;
    $cart_items = null;
} else {
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
    // 獲取購物車項目
    $items_query = "SELECT ci.cart_item_id, ci.quantity, p.product_id, p.product_name, p.price 
                   FROM cart_items ci
                   JOIN products p ON ci.product_id = p.product_id
                   WHERE ci.cart_id = ?
                   ORDER BY ci.added_at DESC";
    $items_stmt = $mysqli->prepare($items_query);
    $items_stmt->bind_param("i", $cart_id);
    $items_stmt->execute();
    $cart_items = $items_stmt->get_result();
}

// 計算總金額
$total_amount = 0;
if ($cart_items && $cart_items->num_rows > 0) {
    $cart_items->data_seek(0);
    while ($item = $cart_items->fetch_assoc()) {
        $total_amount += $item['price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>購物車 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', '微軟正黑體', sans-serif;
            background: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }

        .nav-link {
            color: white !important;
        }

        .cart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .cart-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .cart-container {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .cart-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .cart-table table {
            margin-bottom: 0;
        }

        .cart-table thead {
            background: #667eea;
            color: white;
        }

        .cart-table td, .cart-table th {
            padding: 15px;
            vertical-align: middle;
        }

        .cart-table tbody tr:hover {
            background: #f8f9fa;
        }

        .product-info {
            font-weight: 500;
            color: #333;
        }

        .price {
            color: #667eea;
            font-weight: bold;
            font-size: 16px;
        }

        .subtotal {
            color: #764ba2;
            font-weight: bold;
            font-size: 18px;
        }

        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .cart-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 16px;
        }

        .summary-row:last-child {
            border-bottom: none;
            border-top: 2px solid #667eea;
            padding-top: 20px;
            margin-top: 20px;
        }

        .summary-total {
            font-size: 24px;
            font-weight: bold;
            color: #764ba2;
        }

        .btn-checkout {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-checkout:hover {
            background: #764ba2;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-cart-icon {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-cart-text {
            font-size: 24px;
            color: #999;
            margin-bottom: 20px;
        }

        .continue-shopping-btn {
            background: #667eea;
            color: white;
            padding: 10px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .continue-shopping-btn:hover {
            background: #764ba2;
            text-decoration: none;
            color: white;
        }

        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .cart-table {
                overflow-x: auto;
            }

            .cart-table td, .cart-table th {
                padding: 10px 5px;
                font-size: 12px;
            }

            .quantity-input {
                width: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-dumbbell"></i> 皮拉提斯健身房
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                    <li class="nav-item"><a class="nav-link" href="products/shop.php">商城</a></li>
                    <li class="nav-item"><a class="nav-link" href="trainers/index.php">教練</a></li>
                    <li class="nav-item"><a class="nav-link" href="auth/logout.php">登出</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 購物車標題 -->
    <div class="cart-header">
        <h1><i class="fas fa-shopping-cart"></i> 購物車</h1>
    </div>

    <!-- 購物車內容 -->
    <div class="cart-container">
        <?php echo displayMessages(); ?>

        <?php if ($cart_items && $cart_items->num_rows > 0): ?>
            <!-- 購物車表格 -->
            <div class="cart-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>產品名稱</th>
                            <th style="text-align: right;">單價</th>
                            <th style="text-align: center;">數量</th>
                            <th style="text-align: right;">小計</th>
                            <th style="text-align: center;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cart_items->data_seek(0);
                        while ($item = $cart_items->fetch_assoc()): 
                        ?>
                            <tr>
                                <td class="product-info"><?php echo sanitize($item['product_name']); ?></td>
                                <td style="text-align: right;" class="price"><?php echo formatCurrency($item['price']); ?></td>
                                <td style="text-align: center;">
                                    <form method="POST" action="update_cart.php" style="display: inline;">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" class="quantity-input" style="width: 50px;">
                                        <button type="submit" style="background: none; border: none; padding: 0; color: #667eea; cursor: pointer; font-weight: bold;">更新</button>
                                    </form>
                                </td>
                                <td style="text-align: right;" class="subtotal">
                                    <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <form method="POST" action="remove_from_cart.php" style="display: inline;">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <button type="submit" class="remove-btn">
                                            <i class="fas fa-trash"></i> 刪除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- 結算區域 -->
            <div class="row">
                <div class="col-md-8">
                    <a href="products/shop.php" class="continue-shopping-btn">
                        <i class="fas fa-arrow-left"></i> 繼續購物
                    </a>
                </div>
                <div class="col-md-4">
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>商品總額：</span>
                            <span class="price"><?php echo formatCurrency($total_amount); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>運費：</span>
                            <span class="price">NT$0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-total">合計：</span>
                            <span class="summary-total"><?php echo formatCurrency($total_amount); ?></span>
                        </div>
                        <a href="checkout.php" class="btn-checkout" style="text-decoration: none; display: block; text-align: center; line-height: 15px;">
                            前往結帳 <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- 空購物車 -->
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="empty-cart-text">購物車是空的</div>
                <a href="products/shop.php" class="continue-shopping-btn">
                    開始購物 <i class="fas fa-arrow-right"></i>
                </a>
            </div>

        <?php endif; ?>
    </div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>