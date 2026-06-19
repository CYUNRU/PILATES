<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// 獲取分類
$categories_query = "SELECT * FROM product_categories";
$categories = $mysqli->query($categories_query);

// 獲取選中的分類
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// 獲取產品
if ($selected_category > 0) {
    $products_query = "SELECT * FROM products WHERE category_id = ? ORDER BY product_name";
    $stmt = $mysqli->prepare($products_query);
    $stmt->bind_param("i", $selected_category);
} else {
    $products_query = "SELECT * FROM products ORDER BY category_id, product_name";
    $stmt = $mysqli->prepare($products_query);
}

$stmt->execute();
$products = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商城 - 皮拉提斯健身房</title>
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

        .shop-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .shop-header h1 {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .container-shop {
            padding: 40px 20px;
        }

        .category-filter {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .category-btn {
            background: #f8f9fa;
            border: 2px solid #ddd;
            color: #333;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .category-btn:hover,
        .category-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
        }

        .product-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
            color: #333;
        }

        .product-price {
            font-size: 20px;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .product-stock {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }

        .add-to-cart-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-top: auto;
        }

        .add-to-cart-btn:hover {
            background: #764ba2;
        }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .shop-header h1 {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-dumbbell"></i> 皮拉提斯健身房
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">首頁</a></li>
                    <li class="nav-item"><a class="nav-link" href="../trainers/index.php">教練</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="../cart.php">購物車</a></li>
                        <li class="nav-item"><a class="nav-link" href="../auth/logout.php">登出</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../auth/login.php">登入</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 商城標題 -->
    <div class="shop-header">
        <h1><i class="fas fa-shopping-bag"></i> 瑜珈商城</h1>
        <p>精選優質瑜珈運動裝備，提升您的運動體驗</p>
    </div>

    <!-- 商城內容 -->
    <div class="container-lg container-shop">
        <!-- 分類篩選 -->
        <div class="category-filter">
            <h5 style="margin-bottom: 15px;">選擇分類：</h5>
            <a href="shop.php" class="category-btn <?php echo $selected_category == 0 ? 'active' : ''; ?>">
                全部產品
            </a>
            <?php 
            $categories->data_seek(0);
            while ($category = $categories->fetch_assoc()): 
            ?>
                <a href="shop.php?category=<?php echo $category['category_id']; ?>" 
                   class="category-btn <?php echo $selected_category == $category['category_id'] ? 'active' : ''; ?>">
                    <?php echo sanitize($category['category_name']); ?>
                </a>
            <?php endwhile; ?>
        </div>

        <!-- 產品網格 -->
        <div class="product-grid">
            <?php 
            if ($products->num_rows > 0):
                while ($product = $products->fetch_assoc()): 
            ?>
                <div class="product-card">
                    <div class="product-image">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="product-body">
                        <h6 class="product-name"><?php echo sanitize($product['product_name']); ?></h6>
                        <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                        <div class="product-stock">
                            庫存：<?php echo $product['stock']; ?> 件
                        </div>
                        <?php if ($product['stock'] > 0): ?>
                            <?php if (isLoggedIn()): ?>
                                <form method="POST" action="add_to_cart.php" style="flex-grow: 1; display: flex; flex-direction: column;">
                                    <div style="display: flex; gap: 5px; margin-bottom: 10px;">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                               style="flex: 1; padding: 5px; border: 1px solid #ddd; border-radius: 5px;">
                                    </div>
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" class="add-to-cart-btn">加入購物車</button>
                                </form>
                            <?php else: ?>
                                <a href="../auth/login.php" class="add-to-cart-btn" style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                    登入購物
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="add-to-cart-btn" disabled>已缺貨</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> 該分類暫無產品
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>