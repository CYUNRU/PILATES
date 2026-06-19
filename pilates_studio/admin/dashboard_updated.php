<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// 檢查管理員權限
if (!checkRole('admin')) {
    redirectToLogin();
}

// 獲取統計數據
$enrollment_stats_query = "SELECT 
    ct.type_name, 
    COUNT(ce.enrollment_id) as total_enrollments,
    COUNT(DISTINCT ce.schedule_id) as total_classes
    FROM course_enrollments ce
    JOIN course_schedules cs ON ce.schedule_id = cs.schedule_id
    JOIN courses c ON cs.course_id = c.course_id
    JOIN course_types ct ON c.course_type_id = ct.course_type_id
    WHERE ce.status = 'confirmed'
    GROUP BY ct.type_name";
$enrollment_stats = $mysqli->query($enrollment_stats_query);

$product_sales_query = "SELECT 
    pc.category_name,
    COUNT(oi.order_item_id) as total_sold,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.price * oi.quantity) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN product_categories pc ON p.category_id = pc.category_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.payment_status = 'completed'
    GROUP BY pc.category_name";
$product_sales = $mysqli->query($product_sales_query);

$trainer_schedule_query = "SELECT 
    u.full_name,
    COUNT(cs.schedule_id) as total_classes,
    SUM(CASE WHEN cs.course_date = CURDATE() THEN 1 ELSE 0 END) as today_classes
    FROM trainers t
    JOIN users u ON t.user_id = u.user_id
    LEFT JOIN course_schedules cs ON t.trainer_id = cs.trainer_id
    WHERE t.approval_status = 'approved'
    GROUP BY t.trainer_id, u.full_name
    ORDER BY total_classes DESC";
$trainer_schedule = $mysqli->query($trainer_schedule_query);

$duty_today_query = "SELECT u.full_name, t.specialization
    FROM trainers t
    JOIN users u ON t.user_id = u.user_id
    WHERE t.trainer_id IN (SELECT on_duty_trainer_id FROM course_schedules WHERE course_date = CURDATE())
    LIMIT 1";
$duty_today = $mysqli->query($duty_today_query);

$total_users = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc();
$total_trainers = $mysqli->query("SELECT COUNT(*) as count FROM trainers WHERE approval_status = 'approved'")->fetch_assoc();
$pending_intro = $mysqli->query("SELECT COUNT(*) as count FROM trainers WHERE approval_status = 'pending'")->fetch_assoc();
$total_revenue = $mysqli->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員儀表板 - 皮拉提斯健身房</title>
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

        .charts-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .charts-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: white;
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

        .stat-card.blue .stat-card-icon {
            color: #667eea;
        }

        .stat-card.green .stat-card-icon {
            color: #28a745;
        }

        .stat-card.orange .stat-card-icon {
            color: #fd7e14;
        }

        .stat-card.red .stat-card-icon {
            color: #dc3545;
        }

        .table-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
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

            .header {
                flex-direction: column;
                gap: 15px;
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> 儀表板</a></li>
            <li><a href="dashboard_charts.php"><i class="fas fa-chart-bar"></i> 統計圖表</a></li>
            <li><a href="course_enrollment.php"><i class="fas fa-calendar"></i> 課程報名統計</a></li>
            <li><a href="product_sales.php"><i class="fas fa-shopping-cart"></i> 商品銷售統計</a></li>
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
            <h1><i class="fas fa-chart-line"></i> 管理員儀表板</h1>
            <div>
                <span style="margin-right: 20px;">歡迎, <?php echo sanitize($_SESSION['full_name']); ?></span>
                <a href="dashboard_charts.php" class="charts-link">
                    <i class="fas fa-chart-bar"></i> 查看統計圖表
                </a>
            </div>
        </div>

        <!-- 統計卡片 -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card blue">
                    <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-card-value"><?php echo $total_users['count']; ?></div>
                    <div class="stat-card-label">總會員數</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card green">
                    <div class="stat-card-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="stat-card-value"><?php echo $total_trainers['count']; ?></div>
                    <div class="stat-card-label">在職教練數</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card orange">
                    <div class="stat-card-icon"><i class="fas fa-hourglass"></i></div>
                    <div class="stat-card-value"><?php echo $pending_intro['count']; ?></div>
                    <div class="stat-card-label">待審核介紹</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card red">
                    <div class="stat-card-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-card-value"><?php echo formatCurrency($total_revenue['total'] ?? 0); ?></div>
                    <div class="stat-card-label">總營收</div>
                </div>
            </div>
        </div>

        <!-- 課程報名統計 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-calendar"></i> 課程報名統計（本月）</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>課程類型</th>
                        <th>總報名人數</th>
                        <th>開班數</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($enrollment_stats->num_rows > 0):
                        while ($stat = $enrollment_stats->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo sanitize($stat['type_name']); ?></td>
                            <td><?php echo $stat['total_enrollments']; ?></td>
                            <td><?php echo $stat['total_classes']; ?></td>
                            <td>
                                <a href="course_enrollment.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> 詳情
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>

        <!-- 商品銷售統計 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-shopping-cart"></i> 商品銷售統計（本月）</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>產品分類</th>
                        <th>銷售件數</th>
                        <th>銷售收入</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($product_sales->num_rows > 0):
                        while ($sale = $product_sales->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo sanitize($sale['category_name']); ?></td>
                            <td><?php echo $sale['total_quantity']; ?></td>
                            <td><?php echo formatCurrency($sale['total_revenue']); ?></td>
                            <td>
                                <a href="product_sales.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> 詳情
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>

        <!-- 教練班表統計 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-users"></i> 教練班表統計</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>教練名稱</th>
                        <th>總課數</th>
                        <th>今日課程</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($trainer_schedule->num_rows > 0):
                        while ($trainer = $trainer_schedule->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo sanitize($trainer['full_name']); ?></td>
                            <td><?php echo $trainer['total_classes']; ?></td>
                            <td>
                                <span class="badge bg-success"><?php echo $trainer['today_classes']; ?></span>
                            </td>
                            <td>
                                <a href="trainer_schedule.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> 詳情
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>

        <!-- 值班人員 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-clock"></i> 今日值班人員</h3>
            <?php 
            if ($duty_today->num_rows > 0):
                $duty = $duty_today->fetch_assoc();
            ?>
                <div style="padding: 20px; background: #e8eaf6; border-radius: 10px; text-align: center;">
                    <div style="font-size: 18px; margin-bottom: 10px;">
                        <i class="fas fa-star" style="color: #f39c12;"></i>
                        <strong><?php echo sanitize($duty['full_name']); ?></strong>
                    </div>
                    <div style="color: #666;">
                        專長: <?php echo sanitize($duty['specialization']); ?>
                    </div>
                </div>
            <?php 
            else:
            ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 今日暫無指定值班人員
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>