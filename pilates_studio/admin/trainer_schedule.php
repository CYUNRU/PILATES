<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

// 獲取所有教練
$trainers_query = "SELECT t.trainer_id, u.full_name FROM trainers t
                  JOIN users u ON t.user_id = u.user_id
                  WHERE t.approval_status = 'approved'
                  ORDER BY u.full_name";
$trainers = $mysqli->query($trainers_query);

// 獲取選中的教練
$selected_trainer = isset($_GET['trainer']) ? (int)$_GET['trainer'] : 0;

// 獲取教練班表
$schedule_query = "SELECT cs.*, c.course_name, u.full_name as trainer_name, ct.type_name,
                   COUNT(ce.enrollment_id) as enrollment_count
                   FROM course_schedules cs
                   JOIN courses c ON cs.course_id = c.course_id
                   JOIN trainers t ON cs.trainer_id = t.trainer_id
                   JOIN users u ON t.user_id = u.user_id
                   JOIN course_types ct ON c.course_type_id = ct.course_type_id
                   LEFT JOIN course_enrollments ce ON cs.schedule_id = ce.schedule_id AND ce.status = 'confirmed'
                   WHERE 1=1";

if ($selected_trainer > 0) {
    $schedule_query .= " AND t.trainer_id = $selected_trainer";
}

$schedule_query .= " GROUP BY cs.schedule_id
                   ORDER BY cs.course_date ASC, cs.start_time ASC";

$schedules = $mysqli->query($schedule_query);

// 獲取教練統計信息
$trainer_stats_query = "SELECT 
    u.full_name,
    t.specialization,
    COUNT(DISTINCT cs.schedule_id) as total_classes,
    SUM(CASE WHEN cs.course_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_classes,
    SUM(CASE WHEN cs.course_date = CURDATE() THEN 1 ELSE 0 END) as today_classes,
    COUNT(DISTINCT ce.enrollment_id) as total_students
    FROM trainers t
    JOIN users u ON t.user_id = u.user_id
    LEFT JOIN course_schedules cs ON t.trainer_id = cs.trainer_id
    LEFT JOIN course_enrollments ce ON cs.schedule_id = ce.schedule_id AND ce.status = 'confirmed'
    WHERE t.approval_status = 'approved'
    GROUP BY t.trainer_id, u.full_name, t.specialization
    ORDER BY total_classes DESC";

$trainer_stats = $mysqli->query($trainer_stats_query);

// 獲取今日值班人員
$duty_query = "SELECT DISTINCT u.full_name, t.specialization
              FROM trainers t
              JOIN users u ON t.user_id = u.user_id
              WHERE t.trainer_id IN (SELECT DISTINCT on_duty_trainer_id FROM course_schedules WHERE course_date = CURDATE() AND on_duty_trainer_id IS NOT NULL)
              LIMIT 5";
$duty_staff = $mysqli->query($duty_query);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教練班表 - 皮拉提斯健身房</title>
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
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .stat-card-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card-label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
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
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .duty-badge {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
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
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> 儀表板</a></li>
            <li><a href="course_enrollment.php"><i class="fas fa-calendar"></i> 課程報名統計</a></li>
            <li><a href="product_sales.php"><i class="fas fa-shopping-cart"></i> 商品銷售統計</a></li>
            <li><a href="trainer_schedule.php" class="active"><i class="fas fa-users"></i> 教練班表</a></li>
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
            <h1><i class="fas fa-users"></i> 教練班表管理</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <!-- 教練統計 -->
        <h3 style="margin-bottom: 20px; color: #667eea;">教練概況</h3>
        <div class="row" style="margin-bottom: 30px;">
            <?php 
            $trainer_stats->data_seek(0);
            while ($stat = $trainer_stats->fetch_assoc()): 
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div style="font-weight: bold; color: #667eea; margin-bottom: 10px;">
                            <?php echo sanitize($stat['full_name']); ?>
                        </div>
                        <div style="font-size: 12px; color: #999; margin-bottom: 15px;">
                            <?php echo sanitize($stat['specialization']); ?>
                        </div>
                        <div class="stat-card-value"><?php echo $stat['total_classes']; ?></div>
                        <div class="stat-card-label">總課數</div>
                        <hr style="margin: 10px 0;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px;">
                            <div>
                                <span style="color: #667eea; font-weight: bold;"><?php echo $stat['upcoming_classes']; ?></span>
                                <div style="color: #999;">待上課</div>
                            </div>
                            <div>
                                <span style="color: #28a745; font-weight: bold;"><?php echo $stat['total_students']; ?></span>
                                <div style="color: #999;">教學人數</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- 今日值班 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-star"></i> 今日值班人員</h3>
            <?php if ($duty_staff->num_rows > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <?php 
                    while ($duty = $duty_staff->fetch_assoc()): 
                    ?>
                        <div style="padding: 15px; background: #e8eaf6; border-radius: 10px; text-align: center;">
                            <i class="fas fa-user-circle" style="font-size: 40px; color: #667eea; margin-bottom: 10px; display: block;"></i>
                            <div style="font-weight: bold; color: #667eea; margin-bottom: 5px;">
                                <?php echo sanitize($duty['full_name']); ?>
                            </div>
                            <div style="font-size: 12px; color: #999;">
                                <?php echo sanitize($duty['specialization']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 今日暫無指定值班人員
                </div>
            <?php endif; ?>
        </div>

        <!-- 篩選 -->
        <div class="filter-section">
            <h5 style="margin-bottom: 15px;">選擇教練：</h5>
            <div class="btn-group" role="group">
                <a href="trainer_schedule.php" class="btn btn-outline-primary <?php echo $selected_trainer == 0 ? 'active' : ''; ?>">
                    所有教練
                </a>
                <?php 
                $trainers->data_seek(0);
                while ($trainer = $trainers->fetch_assoc()): 
                ?>
                    <a href="trainer_schedule.php?trainer=<?php echo $trainer['trainer_id']; ?>" 
                       class="btn btn-outline-primary <?php echo $selected_trainer == $trainer['trainer_id'] ? 'active' : ''; ?>">
                        <?php echo sanitize($trainer['full_name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- 班表 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-calendar"></i> 課程安排</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>日期</th>
                        <th>時間</th>
                        <th>課程名稱</th>
                        <th>教練</th>
                        <th>課程類型</th>
                        <th>報名人數</th>
                        <th>值班人員</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($schedules->num_rows > 0):
                        while ($schedule = $schedules->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo formatDate($schedule['course_date']); ?></td>
                            <td><?php echo formatTime($schedule['start_time']); ?> - <?php echo formatTime($schedule['end_time']); ?></td>
                            <td><?php echo sanitize($schedule['course_name']); ?></td>
                            <td><?php echo sanitize($schedule['trainer_name']); ?></td>
                            <td><?php echo sanitize($schedule['type_name']); ?></td>
                            <td><?php echo $schedule['enrollment_count']; ?></td>
                            <td>
                                <?php if ($schedule['on_duty_trainer_id']): ?>
                                    <span class="duty-badge">
                                        <i class="fas fa-star"></i> 值班中
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
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