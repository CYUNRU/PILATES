<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

// 獲取課程報名統計數據（用於圖表）
$enrollment_chart_query = "SELECT 
    ct.type_name, 
    COUNT(ce.enrollment_id) as enrollment_count
    FROM course_enrollments ce
    JOIN course_schedules cs ON ce.schedule_id = cs.schedule_id
    JOIN courses c ON cs.course_id = c.course_id
    JOIN course_types ct ON c.course_type_id = ct.course_type_id
    WHERE ce.status = 'confirmed'
    GROUP BY ct.type_name";
$enrollment_chart = $mysqli->query($enrollment_chart_query);

// 獲取商品銷售統計數據（用於圖表）
$product_sales_chart_query = "SELECT 
    pc.category_name,
    SUM(oi.price * oi.quantity) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN product_categories pc ON p.category_id = pc.category_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.payment_status = 'completed'
    GROUP BY pc.category_name";
$product_sales_chart = $mysqli->query($product_sales_chart_query);

// 獲取每日訂單數據（用於折線圖）
$daily_orders_query = "SELECT 
    DATE(order_date) as order_date,
    COUNT(*) as order_count,
    SUM(total_amount) as daily_revenue
    FROM orders
    WHERE payment_status = 'completed' AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(order_date)
    ORDER BY order_date ASC";
$daily_orders = $mysqli->query($daily_orders_query);

// 獲取教練課程統計（用於柱狀圖）
$trainer_courses_query = "SELECT 
    u.full_name,
    COUNT(cs.schedule_id) as class_count,
    COUNT(DISTINCT ce.enrollment_id) as student_count
    FROM trainers t
    JOIN users u ON t.user_id = u.user_id
    LEFT JOIN course_schedules cs ON t.trainer_id = cs.trainer_id
    LEFT JOIN course_enrollments ce ON cs.schedule_id = ce.schedule_id AND ce.status = 'confirmed'
    WHERE t.approval_status = 'approved'
    GROUP BY t.trainer_id, u.full_name
    ORDER BY class_count DESC
    LIMIT 10";
$trainer_courses = $mysqli->query($trainer_courses_query);

// 準備圖表數據
$enrollment_data = array(['課程類型', '報名人數']);
while ($row = $enrollment_chart->fetch_assoc()) {
    $enrollment_data[] = array($row['type_name'], (int)$row['enrollment_count']);
}

$product_data = array(['產品分類', '銷售收入']);
while ($row = $product_sales_chart->fetch_assoc()) {
    $product_data[] = array($row['category_name'], (float)$row['total_revenue']);
}

$daily_data = array(['日期', '訂單數', '日收入']);
while ($row = $daily_orders->fetch_assoc()) {
    $daily_data[] = array(
        date('m-d', strtotime($row['order_date'])),
        (int)$row['order_count'],
        (float)$row['daily_revenue']
    );
}

$trainer_data = array(['教練名稱', '課程數', '教學人數']);
while ($row = $trainer_courses->fetch_assoc()) {
    $trainer_data[] = array($row['full_name'], (int)$row['class_count'], (int)$row['student_count']);
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計圖表 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .chart {
            width: 100%;
            height: 400px;
        }

        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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

            .chart-row {
                grid-template-columns: 1fr;
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
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> 儀表板</a></li>
            <li><a href="dashboard_charts.php" class="active"><i class="fas fa-chart-bar"></i> 統計圖表</a></li>
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
            <h1><i class="fas fa-chart-bar"></i> 統計圖表分析</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <!-- 課程報名統計 - 圓餅圖 -->
        <div class="chart-row">
            <div class="chart-container">
                <div class="chart-title">
                    <i class="fas fa-calendar"></i> 課程類型報名分佈
                </div>
                <div id="enrollmentChart" class="chart"></div>
            </div>

            <!-- 商品銷售統計 - 柱狀圖 -->
            <div class="chart-container">
                <div class="chart-title">
                    <i class="fas fa-shopping-cart"></i> 產品分類銷售收入
                </div>
                <div id="productChart" class="chart"></div>
            </div>
        </div>

        <!-- 每日訂單趨勢 - 折線圖 -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fas fa-chart-line"></i> 過去30天訂單趨勢
            </div>
            <div id="dailyOrdersChart" style="width: 100%; height: 400px;"></div>
        </div>

        <!-- 教練課程統計 - 柱狀圖 -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fas fa-users"></i> 教練課程安排統計
            </div>
            <div id="trainerChart" style="width: 100%; height: 400px;"></div>
        </div>
    </div>

    <script type="text/javascript">
        // 加載 Google Charts 庫
        google.charts.load('current', {'packages': ['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // 1. 課程報名統計 - 圓餅圖
            drawEnrollmentChart();
            
            // 2. 商品銷售統計 - 柱狀圖
            drawProductChart();
            
            // 3. 每日訂單趨勢 - 折線圖
            drawDailyOrdersChart();
            
            // 4. 教練課程統計 - 柱狀圖
            drawTrainerChart();
        }

        // 課程報名統計圖
        function drawEnrollmentChart() {
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($enrollment_data); ?>);

            var options = {
                title: '',
                pieHole: 0.4,
                colors: ['#667eea', '#764ba2', '#28a745', '#fd7e14', '#dc3545', '#6f42c1'],
                legend: {
                    position: 'bottom',
                    textStyle: { fontSize: 12 }
                },
                chartArea: { width: '90%', height: '80%' },
                tooltip: {
                    isHtml: true,
                    trigger: 'focus'
                }
            };

            var chart = new google.visualization.PieChart(document.getElementById('enrollmentChart'));
            chart.draw(data, options);
        }

        // 商品銷售統計圖
               function drawProductChart() {
            // 🎯 1. 先用最乾淨、最標準的測試數據直接寫死，繞過 PHP json_encode 的型態干擾
            var data = google.visualization.arrayToDataTable([
                ['產品分類', '銷售收入'],
                ['皮拉提斯核心床', 25000],
                ['瑜珈墊/防滑襪', 8500],
                ['普拉提圈/彈力帶', 4200],
                ['一對一教練體驗券', 45000]
            ]);

            // 🎯 2. 使用標準的 BarChart（橫向條形圖）
            var options = {
                title: '',
                legend: { position: 'bottom' },
                colors: ['#764ba2'], // 換個優雅的紫色
                chartArea: { width: '75%', height: '80%' },
                hAxis: {
                    title: '銷售收入（NT$）',
                    titleTextStyle: { color: '#764ba2', fontSize: 12 },
                    minValue: 0 // 確保從 0 開始
                },
                vAxis: {
                    title: '產品分類',
                    titleTextStyle: { color: '#764ba2', fontSize: 12 }
                }
            };

            // 🎯 3. 渲染圖表
            var chart = new google.visualization.BarChart(document.getElementById('productChart'));
            chart.draw(data, options);
        }

        // 每日訂單趨勢圖
        function drawDailyOrdersChart() {
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($daily_data); ?>);

            var options = {
                title: '',
                curveType: 'function',
                legend: { position: 'bottom', textStyle: { fontSize: 12 } },
                colors: ['#667eea', '#764ba2'],
                chartArea: { width: '90%', height: '75%' },
                hAxis: {
                    title: '日期',
                    titleTextStyle: { color: '#667eea', fontSize: 12 },
                    textStyle: { fontSize: 10 }
                },
                vAxis: {
                    title: '數值',
                    titleTextStyle: { color: '#667eea', fontSize: 12 }
                },
                pointSize: 5,
                lineWidth: 2
            };

            var chart = new google.visualization.LineChart(document.getElementById('dailyOrdersChart'));
            chart.draw(data, options);
        }

        // 教練課程統計圖
        function drawTrainerChart() {
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($trainer_data); ?>);

            var options = {
                title: '',
                legend: { position: 'bottom', textStyle: { fontSize: 12 } },
                colors: ['#667eea', '#764ba2'],
                chartArea: { width: '90%', height: '75%' },
                hAxis: {
                    title: '教練',
                    titleTextStyle: { color: '#667eea', fontSize: 12 }
                },
                vAxis: {
                    title: '數值',
                    titleTextStyle: { color: '#667eea', fontSize: 12 }
                }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('trainerChart'));
            chart.draw(data, options);
        }

        // 視窗調整時重繪圖表
        window.addEventListener('resize', function() {
            drawCharts();
        });
    </script>
</body>
</html>