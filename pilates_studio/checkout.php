<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 獲取購物車資訊
$cart_query = "SELECT cart_id FROM shopping_carts WHERE user_id = ?";
$cart_stmt = $mysqli->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows === 0) {
    header('Location: cart.php');
    exit();
}

$cart = $cart_result->fetch_assoc();
$cart_id = $cart['cart_id'];

// 獲取購物車項目
$items_query = "SELECT ci.cart_item_id, ci.quantity, p.product_id, p.product_name, p.price 
               FROM cart_items ci
               JOIN products p ON ci.product_id = p.product_id
               WHERE ci.cart_id = ?";
$items_stmt = $mysqli->prepare($items_query);
$items_stmt->bind_param("i", $cart_id);
$items_stmt->execute();
$cart_items = $items_stmt->get_result();

if ($cart_items->num_rows === 0) {
    header('Location: cart.php');
    exit();
}

// 計算總金額
$total_amount = 0;
$items_array = [];
while ($item = $cart_items->fetch_assoc()) {
    $items_array[] = $item;
    $total_amount += $item['price'] * $item['quantity'];
}

// 獲取用戶信息
$user_query = "SELECT full_name, email, phone FROM users WHERE user_id = ?";
$user_stmt = $mysqli->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $mysqli->real_escape_string($_POST['payment_method']);
    $installment_months = isset($_POST['installment_months']) ? (int)$_POST['installment_months'] : 0;
    
    // 驗證支付方式
    $valid_methods = ['credit-card', 'line-pay', 'apple-pay', 'installment'];
    if (!in_array($payment_method, $valid_methods)) {
        $error_message = '支付方式不正確';
    } else if ($payment_method === 'installment' && ($installment_months < 3 || $installment_months > 12)) {
        $error_message = '分期期數必須在3-12個月之間';
    } else {
        // 開始事務處理
        $mysqli->begin_transaction();
        
        try {
            // 建立訂單
            $order_type = 'product';
            $order_query = "INSERT INTO orders (user_id, total_amount, order_type, payment_method, installment_months, payment_status, status) 
                           VALUES (?, ?, ?, ?, ?, 'pending', 'pending')";
            $order_stmt = $mysqli->prepare($order_query);
            
            $installment_months_db = ($payment_method === 'installment') ? $installment_months : NULL;
            $order_stmt->bind_param("idssi", $user_id, $total_amount, $order_type, $payment_method, $installment_months_db);
            
            if (!$order_stmt->execute()) {
                throw new Exception('建立訂單失敗');
            }
            
            $order_id = $order_stmt->insert_id;
            
            // 添加訂單項目
            foreach ($items_array as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = $mysqli->prepare($item_query);
                $item_stmt->bind_param("iiii", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('添加訂單項目失敗');
                }
                
                // 更新產品庫存
                $stock_query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
                $stock_stmt = $mysqli->prepare($stock_query);
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                
                if (!$stock_stmt->execute()) {
                    throw new Exception('更新庫存失敗');
                }
            }
            
            // 如果選擇分期付款，建立分期計劃
            if ($payment_method === 'installment') {
                $monthly_amount = $total_amount / $installment_months;
                
                $installment_query = "INSERT INTO installment_plans (order_id, total_months, monthly_amount, paid_months, status) 
                                     VALUES (?, ?, ?, 0, 'active')";
                $installment_stmt = $mysqli->prepare($installment_query);
                $installment_stmt->bind_param("iid", $order_id, $installment_months, $monthly_amount);
                
                if (!$installment_stmt->execute()) {
                    throw new Exception('建立分期計劃失敗');
                }
                
                $installment_id = $installment_stmt->insert_id;
                
                // 建立分期詳情
                for ($i = 1; $i <= $installment_months; $i++) {
                    $due_date = date('Y-m-d', strtotime("+$i month"));
                    $detail_query = "INSERT INTO installment_details (installment_id, month, due_date, amount, status) 
                                    VALUES (?, ?, ?, ?, 'pending')";
                    $detail_stmt = $mysqli->prepare($detail_query);
                    $detail_stmt->bind_param("iids", $installment_id, $i, $due_date, $monthly_amount);
                    
                    if (!$detail_stmt->execute()) {
                        throw new Exception('建立分期詳情失敗');
                    }
                }
            }
            
            // 建立支付紀錄
            $payment_record_query = "INSERT INTO payment_records (order_id, amount, payment_method, status) 
                                    VALUES (?, ?, ?, 'pending')";
            $payment_record_stmt = $mysqli->prepare($payment_record_query);
            $payment_record_stmt->bind_param("ids", $order_id, $total_amount, $payment_method);
            
            if (!$payment_record_stmt->execute()) {
                throw new Exception('建立支付紀錄失敗');
            }
            
            // 清空購物車
            $clear_cart_query = "DELETE FROM cart_items WHERE cart_id = ?";
            $clear_cart_stmt = $mysqli->prepare($clear_cart_query);
            $clear_cart_stmt->bind_param("i", $cart_id);
            
            if (!$clear_cart_stmt->execute()) {
                throw new Exception('清空購物車失敗');
            }
            
            // 提交事務
            $mysqli->commit();
            
            // 重定向到支付頁面
            header('Location: payment.php?order_id=' . $order_id);
            exit();
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error_message = '結帳失敗: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>結帳 - 皮拉提斯健身房</title>
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

        .checkout-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .checkout-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .checkout-container {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .checkout-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .order-summary {
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 2px solid #667eea;
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
            color: #764ba2;
        }

        .payment-method {
            margin-bottom: 20px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .payment-option:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }

        .payment-option input[type="radio"] {
            margin-right: 15px;
            cursor: pointer;
            width: 20px;
            height: 20px;
        }

        .payment-option.selected {
            border-color: #667eea;
            background: #e8eaf6;
        }

        .installment-options {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .installment-options.show {
            display: block;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-checkout:hover {
            background: #764ba2;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #5a6268;
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
            .checkout-section {
                padding: 20px;
            }

            .section-title {
                font-size: 18px;
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
                    <li class="nav-item"><a class="nav-link" href="auth/logout.php">登出</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 結帳標題 -->
    <div class="checkout-header">
        <h1><i class="fas fa-credit-card"></i> 結帳</h1>
    </div>

    <!-- 結帳內容 -->
    <div class="checkout-container">
        <a href="cart.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> 返回購物車
        </a>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- 訂單摘要 -->
            <div class="col-lg-6 order-lg-2">
                <div class="checkout-section">
                    <h3 class="section-title"><i class="fas fa-receipt"></i> 訂單摘要</h3>
                    
                    <div class="order-summary">
                        <?php foreach ($items_array as $item): ?>
                            <div class="summary-item">
                                <span><?php echo sanitize($item['product_name']); ?> × <?php echo $item['quantity']; ?></span>
                                <span><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-total">
                        <span>合計金額：</span>
                        <span><?php echo formatCurrency($total_amount); ?></span>
                    </div>
                </div>
            </div>

            <!-- 結帳表單 -->
            <div class="col-lg-6 order-lg-1">
                <div class="checkout-section">
                    <h3 class="section-title"><i class="fas fa-user"></i> 收貨資訊</h3>
                    
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" value="<?php echo sanitize($user_info['full_name']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">郵箱</label>
                        <input type="email" class="form-control" value="<?php echo sanitize($user_info['email']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">電話</label>
                        <input type="tel" class="form-control" value="<?php echo sanitize($user_info['phone']); ?>" disabled>
                    </div>
                </div>

                <div class="checkout-section">
                    <h3 class="section-title"><i class="fas fa-credit-card"></i> 支付方式</h3>
                    
                    <form method="POST" action="">
                        <div class="payment-method">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="credit-card" checked onchange="updatePaymentMethod()">
                                <span><i class="fas fa-credit-card"></i> 信用卡支付</span>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="line-pay" onchange="updatePaymentMethod()">
                                <span><i class="fab fa-line"></i> LINE Pay</span>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="apple-pay" onchange="updatePaymentMethod()">
                                <span><i class="fab fa-apple"></i> Apple Pay</span>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="installment" onchange="updatePaymentMethod()">
                                <span><i class="fas fa-calendar"></i> 分期付款</span>
                            </label>
                        </div>

                        <!-- 分期選項 -->
                        <div class="installment-options" id="installmentOptions">
                            <label class="form-label">選擇分期期數：</label>
                            <select name="installment_months" class="form-control">
                                <option value="">請選擇</option>
                                <option value="3">3個月 (每月 <?php echo formatCurrency($total_amount / 3); ?>)</option>
                                <option value="6">6個月 (每月 <?php echo formatCurrency($total_amount / 6); ?>)</option>
                                <option value="12">12個月 (每月 <?php echo formatCurrency($total_amount / 12); ?>)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-checkout">
                            <i class="fas fa-check"></i> 確認訂單
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updatePaymentMethod() {
            const installmentOption = document.getElementById('installmentOptions');
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            if (paymentMethod === 'installment') {
                installmentOption.classList.add('show');
            } else {
                installmentOption.classList.remove('show');
            }
        }
    </script>
</body>
</html>