<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// 獲取訂單信息
$order_query = "SELECT o.*, u.full_name, u.email FROM orders o 
               JOIN users u ON o.user_id = u.user_id 
               WHERE o.order_id = ? AND o.user_id = ?";
$order_stmt = $mysqli->prepare($order_query);
$order_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $order_result->fetch_assoc();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'simulate_payment') {
        // 模擬支付成功
        $update_order = "UPDATE orders SET payment_status = 'completed', status = 'completed' WHERE order_id = ?";
        $update_stmt = $mysqli->prepare($update_order);
        $update_stmt->bind_param("i", $order_id);
        
        $update_payment = "UPDATE payment_records SET status = 'success' WHERE order_id = ?";
        $payment_stmt = $mysqli->prepare($update_payment);
        $payment_stmt->bind_param("i", $order_id);
        
        if ($update_stmt->execute() && $payment_stmt->execute()) {
            $success_message = '支付成功！訂單已確認。';
            
            // 🎯【重點修正 1】執行完立刻釋放，把通道還給系統
            $update_stmt->free_result();
            $payment_stmt->free_result();
            $update_stmt->close();
            $payment_stmt->close();
            
            // 如果是分期付款，標記第一期已支付
            if ($order['payment_method'] === 'installment') {
                $update_installment = "UPDATE installment_details SET status = 'paid', paid_date = NOW() 
                                      WHERE installment_id = (SELECT installment_id FROM installment_plans WHERE order_id = ?) 
                                      AND month = 1";
                $installment_stmt = $mysqli->prepare($update_installment);
                $installment_stmt->bind_param("i", $order_id);
                $installment_stmt->execute();
                
                // 🎯【重點修正 2】分期付款執行完也立刻釋放關閉
                $installment_stmt->free_result();
                $installment_stmt->close();
            }
            
            // 重新加載訂單信息 (改用不需要 prepare 的簡易單次查詢，完全不佔用 stmt 通道！)
            $order_refresh_res = $mysqli->query("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = $order_id");
            if ($order_refresh_res) {
                $order = $order_refresh_res->fetch_assoc();
                $order_refresh_res->free(); // 撈完立刻釋放記憶體
            }
        } else {
            $update_stmt->close();
            $payment_stmt->close();
            $error_message = '支付失敗，請稍後重試';
        }
    }
}

// 獲取訂單項目
$items_query = "SELECT oi.*, p.product_name FROM order_items oi 
               JOIN products p ON oi.product_id = p.product_id 
               WHERE oi.order_id = ?";


$items_stmt = $mysqli->prepare($items_query); // 👈 這是第 74 行
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// 如果是分期付款，獲取分期詳情
$installment_details = null;
if ($order['payment_method'] === 'installment') {
    $installment_query = "SELECT ip.*, GROUP_CONCAT(CONCAT(id.month, ':', id.status) SEPARATOR ',') as details
                         FROM installment_plans ip
                         LEFT JOIN installment_details id ON ip.installment_id = id.installment_id
                         WHERE ip.order_id = ?
                         GROUP BY ip.installment_id";
    $installment_stmt = $mysqli->prepare($installment_query);
    $installment_stmt->bind_param("i", $order_id);
    $installment_stmt->execute();
    $installment_result = $installment_stmt->get_result();
    if ($installment_result->num_rows > 0) {
        $installment_details = $installment_result->fetch_assoc();
    }
    $order_stmt->close();
    $items_stmt->close();
    if (isset($installment_stmt)) { $installment_stmt->close(); }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付 - 皮拉提斯健身房</title>
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

        .payment-container {
            padding: 40px 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .payment-section {
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

        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 5px 0;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .order-detail {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }

        .detail-label {
            font-size: 12px;
            color: #999;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .table {
            margin-bottom: 0;
        }

        .payment-method-info {
            background: #e8eaf6;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .btn-pay {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-pay:hover {
            background: #764ba2;
        }

        .btn-continue {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-continue:hover {
            background: #5a6268;
            text-decoration: none;
            color: white;
        }

        .installment-table {
            margin-top: 20px;
        }

        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
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
        </div>
    </nav>

    <!-- 支付內容 -->
    <div class="payment-container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo sanitize($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- 訂單資訊 -->
        <div class="payment-section">
            <h3 class="section-title"><i class="fas fa-info-circle"></i> 訂單資訊</h3>
            
            <div class="order-detail">
                <div class="detail-item">
                    <div class="detail-label">訂單編號</div>
                    <div class="detail-value">#<?php echo str_pad($order['order_id'], 8, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">訂單狀態</div>
                    <div>
                        <?php if ($order['payment_status'] === 'completed'): ?>
                            <span class="status-badge status-completed">已支付</span>
                        <?php else: ?>
                            <span class="status-badge status-pending">待支付</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">訂單金額</div>
                    <div class="detail-value"><?php echo formatCurrency($order['total_amount']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">支付方式</div>
                    <div class="detail-value">
                        <?php 
                        $payment_methods = [
                            'credit-card' => '信用卡',
                            'line-pay' => 'LINE Pay',
                            'apple-pay' => 'Apple Pay',
                            'installment' => '分期付款'
                        ];
                        echo $payment_methods[$order['payment_method']] ?? '未知';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 訂單項目 -->
        <div class="payment-section">
            <h3 class="section-title"><i class="fas fa-shopping-bag"></i> 訂單項目</h3>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>產品名稱</th>
                        <th style="text-align: right;">單價</th>
                        <th style="text-align: center;">數量</th>
                        <th style="text-align: right;">小計</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo sanitize($item['product_name']); ?></td>
                            <td style="text-align: right;"><?php echo formatCurrency($item['price']); ?></td>
                            <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- 支付資訊 -->
        <?php if ($order['payment_status'] === 'pending'): ?>
            <div class="payment-section">
                <h3 class="section-title"><i class="fas fa-credit-card"></i> 進行支付</h3>
                
                <div class="payment-method-info">
                    <p><strong>支付方式：</strong> 
                    <?php 
                    $payment_methods = [
                        'credit-card' => '<i class="fas fa-credit-card"></i> 信用卡支付',
                        'line-pay' => '<i class="fab fa-line"></i> LINE Pay',
                        'apple-pay' => '<i class="fab fa-apple"></i> Apple Pay',
                        'installment' => '<i class="fas fa-calendar"></i> 分期付款'
                    ];
                    echo $payment_methods[$order['payment_method']] ?? '未知';
                    ?>
                    </p>
                    <p><strong>應付金額：</strong> <span style="font-size: 24px; color: #667eea; font-weight: bold;"><?php echo formatCurrency($order['total_amount']); ?></span></p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="simulate_payment">
                    <button type="submit" class="btn-pay">
                        <i class="fas fa-lock"></i> 完成支付
                    </button>
                </form>

                <p style="font-size: 12px; color: #999; text-align: center; margin-top: 15px;">
                    <i class="fas fa-shield-alt"></i> 此頁面為演示系統，實際支付將連接到真實的支付閘道
                </p>
            </div>
        <?php else: ?>
            <div class="payment-section">
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> 訂單已成功支付！感謝您的購物。
                </div>
            </div>
        <?php endif; ?>

        <!-- 分期詳情 -->
        <?php if ($order['payment_method'] === 'installment' && $installment_details): ?>
            <div class="payment-section">
                <h3 class="section-title"><i class="fas fa-calendar"></i> 分期付款詳情</h3>
                
                <div class="order-detail">
                    <div class="detail-item">
                        <div class="detail-label">總期數</div>
                        <div class="detail-value"><?php echo $installment_details['total_months']; ?> 個月</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">每月金額</div>
                        <div class="detail-value"><?php echo formatCurrency($installment_details['monthly_amount']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">已支付期數</div>
                        <div class="detail-value"><?php echo $installment_details['paid_months']; ?> / <?php echo $installment_details['total_months']; ?></div>
                    </div>
                </div>

                <table class="table installment-table">
                    <thead>
                        <tr>
                            <th>期次</th>
                            <th>金額</th>
                            <th>到期日期</th>
                            <th>狀態</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $get_details_query = "SELECT * FROM installment_details WHERE installment_id = ? ORDER BY month";
                        $details_stmt = $mysqli->prepare($get_details_query);
                        $details_stmt->bind_param("i", $installment_details['installment_id'] ?? 0);
                        $details_stmt->execute();
                        $details_result = $details_stmt->get_result();
                        
                        while ($detail = $details_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>第 <?php echo $detail['month']; ?> 期</td>
                                <td><?php echo formatCurrency($detail['amount']); ?></td>
                                <td><?php echo formatDate($detail['due_date']); ?></td>
                                <td>
                                    <?php if ($detail['status'] === 'paid'): ?>
                                        <span class="status-badge status-completed">已支付</span>
                                    <?php elseif ($detail['status'] === 'pending'): ?>
                                        <span class="status-badge status-pending">待支付</span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: #f8d7da; color: #721c24;">逾期</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <a href="index.php" class="btn-continue">
            <i class="fas fa-arrow-left"></i> 返回首頁
        </a>
    </div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>