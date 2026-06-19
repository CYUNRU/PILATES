<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

// 獲取課程類型
$course_types_query = "SELECT * FROM course_types ORDER BY type_name";
$course_types = $mysqli->query($course_types_query);

// 獲取選中的課程類型
$selected_type = isset($_GET['type']) ? (int)$_GET['type'] : 0;

// 獲取課程報名數據
$enrollment_query = "SELECT 
    cs.schedule_id,
    cs.course_date,
    cs.start_time,
    cs.end_time,
    c.course_name,
    ct.type_name,
    u.full_name as trainer_name,
    c.course_type,
    COUNT(ce.enrollment_id) as enrollment_count
    FROM course_schedules cs
    JOIN courses c ON cs.course_id = c.course_id
    JOIN course_types ct ON c.course_type_id = ct.course_type_id
    JOIN trainers t ON cs.trainer_id = t.trainer_id
    JOIN users u ON t.user_id = u.user_id
    LEFT JOIN course_enrollments ce ON cs.schedule_id = ce.schedule_id AND ce.status = 'confirmed'
    WHERE 1=1";

if ($selected_type > 0) {
    $enrollment_query .= " AND c.course_type_id = $selected_type";
}

$enrollment_query .= " GROUP BY cs.schedule_id, cs.course_date, cs.start_time, cs.end_time, c.course_name, ct.type_name, u.full_name, c.course_type
    ORDER BY cs.course_date DESC, cs.start_time DESC";

$enrollments = $mysqli->query($enrollment_query);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>課程報名統計 - 皮拉提斯健身房</title>
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

        .enrollment-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .enrollment-available {
            background: #d4edda;
            color: #155724;
        }

        .enrollment-full {
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
            <li><a href="course_enrollment.php" class="active"><i class="fas fa-calendar"></i> 課程報名統計</a></li>
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
            <h1><i class="fas fa-calendar"></i> 課程報名統計</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <!-- 篩選 -->
        <div class="filter-section">
            <h5 style="margin-bottom: 15px;">篩選課程類型：</h5>
            <div class="btn-group" role="group">
                <a href="course_enrollment.php" class="btn btn-outline-primary <?php echo $selected_type == 0 ? 'active' : ''; ?>">
                    全部課程
                </a>
                <?php 
                $course_types->data_seek(0);
                while ($type = $course_types->fetch_assoc()): 
                ?>
                    <a href="course_enrollment.php?type=<?php echo $type['course_type_id']; ?>" 
                       class="btn btn-outline-primary <?php echo $selected_type == $type['course_type_id'] ? 'active' : ''; ?>">
                        <?php echo sanitize($type['type_name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- 表格 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-list"></i> 課程報名詳情</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>日期</th>
                        <th>時間</th>
                        <th>課程名稱</th>
                        <th>課程類型</th>
                        <th>教練</th>
                        <th>報名人數</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($enrollments->num_rows > 0):
                        while ($enrollment = $enrollments->fetch_assoc()):
                            $max_capacity = ($enrollment['course_type'] === 'group-class') ? 5 : 999;
                            $is_full = $enrollment['enrollment_count'] >= $max_capacity;
                    ?>
                        <tr>
                            <td><?php echo formatDate($enrollment['course_date']); ?></td>
                            <td><?php echo formatTime($enrollment['start_time']); ?> - <?php echo formatTime($enrollment['end_time']); ?></td>
                            <td><?php echo sanitize($enrollment['course_name']); ?></td>
                            <td><?php echo sanitize($enrollment['type_name']); ?></td>
                            <td><?php echo sanitize($enrollment['trainer_name']); ?></td>
                            <td>
                                <span class="enrollment-badge <?php echo $is_full ? 'enrollment-full' : 'enrollment-available'; ?>">
                                    <?php echo $enrollment['enrollment_count']; ?>/<?php echo $max_capacity; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_enrollments.php?schedule_id=<?php echo $enrollment['schedule_id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> 查看
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="7" class="text-center">
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