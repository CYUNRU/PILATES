<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

$message = '';

// 處理新增/編輯產品
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $category_id = (int)$_POST['category_id'];
        $product_name = $mysqli->real_escape_string($_POST['product_name']);
        $description = $mysqli->real_escape_string($_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        
        $insert_query = "INSERT INTO products (category_id, product_name, description, price, stock) 
                        VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $mysqli->prepare($insert_query);
        $insert_stmt->bind_param("isdi", $category_id, $product_name, $description, $price, $stock);
        
        if ($insert_stmt->execute()) {
            $message = '產品已新增';
        } else {
            $message = '新增失敗';
        }
    } elseif ($action === 'edit') {
        $product_id = (int)$_POST['product_id'];
        $category_id = (int)$_POST['category_id'];
        $product_name = $mysqli->real_escape_string($_POST['product_name']);
        $description = $mysqli->real_escape_string($_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        
        $update_query = "UPDATE products SET category_id = ?, product_name = ?, description = ?, 
                        price = ?, stock = ? WHERE product_id = ?";
        $update_stmt = $mysqli->prepare($update_query);
        $update_stmt->bind_param("isdii", $category_id, $product_name, $description, $price, $product_id, $stock);
        $update_stmt->bind_param("isdi", $category_id, $product_name, $description, $price, $stock, $product_id);
        
        if ($update_stmt->execute()) {
            $message = '產品已更新';
        } else {
            $message = '更新失敗';
        }
    } elseif ($action === 'delete') {
        $product_id = (int)$_POST['product_id'];
        
        $delete_query = "DELETE FROM products WHERE product_id = ?";
        $delete_stmt = $mysqli->prepare($delete_query);
        $delete_stmt->bind_param("i", $product_id);
        
        if ($delete_stmt->execute()) {
            $message = '產品已刪除';
        } else {
            $message = '刪除失敗';
        }
    }
}

// 獲取所有產品分類
$categories = $mysqli->query("SELECT * FROM product_categories ORDER BY category_name");

// 獲取所有產品
$products_query = "SELECT p.*, pc.category_name FROM products p
                  JOIN product_categories pc ON p.category_id = pc.category_id
                  ORDER BY pc.category_name, p.product_name";
$products = $mysqli->query($products_query);

// 獲取要編輯的產品
$edit_product = null;
if (isset($_GET['edit'])) {
    $product_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM products WHERE product_id = ?";
    $edit_stmt = $mysqli->prepare($edit_query);
    $edit_stmt->bind_param("i", $product_id);
    $edit_stmt->execute();
    $edit_product = $edit_stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>產品管理 - 皮拉提斯健身房</title>
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

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-submit {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-submit:hover {
            background: #764ba2;
        }

        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-reset:hover {
            background: #5a6268;
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

        .edit-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: #218838;
            text-decoration: none;
            color: white;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            background: #c82333;
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
            <li><a href="dashboard_charts.php"><i class="fas fa-chart-bar"></i> 統計圖表</a></li>
            <li><a href="course_enrollment.php"><i class="fas fa-calendar"></i> 課程報名統計</a></li>
            <li><a href="product_sales.php"><i class="fas fa-shopping-cart"></i> 商品銷售統計</a></li>
            <li><a href="trainer_schedule.php"><i class="fas fa-users"></i> 教練班表</a></li>
            <li><a href="trainer_approval.php"><i class="fas fa-check-circle"></i> 教練介紹審核</a></li>
            <li><a href="manage_users.php"><i class="fas fa-user-tie"></i> 用戶管理</a></li>
            <li><a href="manage_products.php" class="active"><i class="fas fa-box"></i> 產品管理</a></li>
            <li><a href="manage_courses.php"><i class="fas fa-book"></i> 課程管理</a></li>
        </ul>
    </div>

    <!-- 主要內容 -->
    <div class="main-content">
        <!-- 頁頭 -->
        <div class="header">
            <h1><i class="fas fa-box"></i> 產品管理</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo sanitize($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 新增/編輯產品表單 -->
        <div class="form-section">
            <div class="form-title">
                <?php echo $edit_product ? '編輯產品' : '新增產品'; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">產品分類 *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">請選擇分類</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['category_id']; ?>" 
                                        <?php echo ($edit_product && $edit_product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($cat['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">產品名稱 *</label>
                            <input type="text" name="product_name" class="form-control" 
                                   value="<?php echo $edit_product ? sanitize($edit_product['product_name']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">產品描述</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo $edit_product ? sanitize($edit_product['description']) : ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">價格 (NT$) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" 
                                   value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">庫存數量 *</label>
                            <input type="number" name="stock" class="form-control" 
                                   value="<?php echo $edit_product ? $edit_product['stock'] : '0'; ?>" 
                                   required>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> <?php echo $edit_product ? '更新' : '新增'; ?>
                    </button>
                    <?php if ($edit_product): ?>
                        <a href="manage_products.php" class="btn-reset" style="text-decoration: none; display: inline-flex; align-items: center;">
                            <i class="fas fa-times"></i> 取消編輯
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- 產品列表 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-list"></i> 產品列表</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>產品名稱</th>
                        <th>分類</th>
                        <th>價格</th>
                        <th>庫存</th>
                        <th>創建時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($products->num_rows > 0):
                        while ($product = $products->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo sanitize($product['product_name']); ?></td>
                            <td><?php echo sanitize($product['category_name']); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td>
                                <span style="font-weight: bold; color: <?php echo $product['stock'] > 0 ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $product['stock']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($product['created_at']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $product['product_id']; ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i> 編輯
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除此產品嗎？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" class="delete-btn">
                                        <i class="fas fa-trash"></i> 刪除
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <i class="fas fa-inbox"></i> 暫無產品
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