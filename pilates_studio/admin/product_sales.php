<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

// 獲取產品分類
$categories_query = "SELECT * FROM product_categories ORDER BY category_name";
$categories = $mysqli->query($categories_query);

// 獲取選中的分類
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// 獲取商品銷售統計
$sales_query = "SELECT 
    p.product_id,
    p.product_name,
    pc.category_name,
    p.stock,
    COUNT(oi.order_item_id) as total_sold,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.price * oi.quantity) as total_revenue
    FROM products p
    JOIN product_categories pc ON p.category_id = pc.category_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id AND o.payment_status = 'completed'
    WHERE 1=1";

if ($selected_category > 0) {
    $sales_query .= " AND p.category_id = $selected_category";
}

$sales_query .= " GROUP BY p.product_id, p.product_name, pc.category_name, p.stock
    ORDER BY total_revenue DESC";

$sales = $mysqli->query($sales_query);

// 獲取庫存統計
$stock_query = "SELECT 
    COUNT(*) as total_products,
    SUM(CASE WHEN stock < 5 THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
    SUM(stock) as total_stock
    FROM products";
$stock_stats = $mysqli->query($stock_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品銷售統計 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', '微軟正黑體', sans-serif;
            background: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar-logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            display: block;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            color: #667eea;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .stat-card-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stat-card-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card-label {
            font-size: 14px;
            color: #999;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #667eea;
        }

        .table th {
            color: #667eea;
            font-weight: bold;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .stock-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .stock-good {
            background: #d4edda;
            color: #155724;
        }

        .stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .stock-out {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
                margin-bottom: 20px;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- 側邊欄 -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-dumbbell"></i> 皮拉提斯
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> 儀表板</a></li>
            <li><a href="course_enrollment.php"><i class="fas fa-calendar"></i> 課程報名統計</a></li>
            <li><a href="product_sales.php" class="active"><i class="fas fa-shopping-cart"></i> 商品銷售統計</a></li>
            <li><a href="trainer_schedule.php"><i class="fas fa-users"></i> 教練班表</a></li>
            <li><a href="trainer_approval.php"><i class="fas fa-check-circle"></i> 教練介紹審核</a></li>
            <li><a href="manage_users.php"><i class="fas fa-user-tie"></i> 用戶管理</a></li>
            <li><a href="manage_products.php"><i class="fas fa-box"></i> 產品管理</a></li>
            <li><a href="manage_courses.php"><i class="fas fa-book"></i> 課程管理</a></li>
        </ul>
    </div>

    <!-- 主要內容 -->
    <div class="main-content">
        <!-- 頁頭 -->
        <div class="header">
            <h1><i class="fas fa-shopping-cart"></i> 商品銷售統計</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <!-- 庫存統計卡片 -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-card-value"><?php echo $stock_stats['total_products']; ?></div>
                    <div class="stat-card-label">總產品數</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-cubes"></i></div>
                    <div class="stat-card-value"><?php echo $stock_stats['total_stock']; ?></div>
                    <div class="stat-card-label">總庫存</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-card-value"><?php echo $stock_stats['low_stock']; ?></div>
                    <div class="stat-card-label">低庫存警告</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-ban"></i></div>
                    <div class="stat-card-value"><?php echo $stock_stats['out_of_stock']; ?></div>
                    <div class="stat-card-label">缺貨產品</div>
                </div>
            </div>
        </div>

        <!-- 篩選 -->
        <div class="filter-section">
            <h5 style="margin-bottom: 15px;">篩選產品分類：</h5>
            <div class="btn-group" role="group">
                <a href="product_sales.php" class="btn btn-outline-primary <?php echo $selected_category == 0 ? 'active' : ''; ?>">
                    全部產品
                </a>
                <?php 
                $categories->data_seek(0);
                while ($category = $categories->fetch_assoc()): 
                ?>
                    <a href="product_sales.php?category=<?php echo $category['category_id']; ?>" 
                       class="btn btn-outline-primary <?php echo $selected_category == $category['category_id'] ? 'active' : ''; ?>">
                        <?php echo sanitize($category['category_name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- 銷售統計表格 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-list"></i> 商品銷售詳情</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>產品名稱</th>
                        <th>分類</th>
                        <th>銷售件數</th>
                        <th>銷售收入</th>
                        <th>當前庫存</th>
                        <th>庫存狀態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($sales->num_rows > 0):
                        while ($sale = $sales->fetch_assoc()):
                            $stock_status = 'stock-good';
                            if ($sale['stock'] == 0) {
                                $stock_status = 'stock-out';
                            } else if ($sale['stock'] < 5) {
                                $stock_status = 'stock-low';
                            }
                    ?>
                        <tr>
                            <td><?php echo sanitize($sale['product_name']); ?></td>
                            <td><?php echo sanitize($sale['category_name']); ?></td>
                            <td><?php echo $sale['total_quantity'] ?? 0; ?></td>
                            <td><?php echo formatCurrency($sale['total_revenue'] ?? 0); ?></td>
                            <td><?php echo $sale['stock']; ?></td>
                            <td>
                                <span class="stock-badge <?php echo $stock_status; ?>">
                                    <?php 
                                    if ($sale['stock'] == 0) {
                                        echo '缺貨';
                                    } else if ($sale['stock'] < 5) {
                                        echo '低庫存';
                                    } else {
                                        echo '充足';
                                    }
                                    ?>
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <i class="fas fa-inbox"></i> 暫無數據
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>