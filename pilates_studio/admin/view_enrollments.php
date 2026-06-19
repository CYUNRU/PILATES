<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;

// 獲取課程詳情
$schedule_query = "SELECT cs.*, c.course_name, ct.type_name, u.full_name as trainer_name
    FROM course_schedules cs
    JOIN courses c ON cs.course_id = c.course_id
    JOIN course_types ct ON c.course_type_id = ct.course_type_id
    JOIN trainers t ON cs.trainer_id = t.trainer_id
    JOIN users u ON t.user_id = u.user_id
    WHERE cs.schedule_id = ?";
$schedule_stmt = $mysqli->prepare($schedule_query);
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();

if ($schedule_result->num_rows === 0) {
    header('Location: course_enrollment.php');
    exit();
}

$schedule = $schedule_result->fetch_assoc();

// 獲取報名人員列表
$enrollments_query = "SELECT ce.enrollment_id, ce.enrollment_date, u.full_name, u.email, u.phone
    FROM course_enrollments ce
    JOIN users u ON ce.user_id = u.user_id
    WHERE ce.schedule_id = ? AND ce.status = 'confirmed'
    ORDER BY ce.enrollment_date DESC";
$enrollments_stmt = $mysqli->prepare($enrollments_query);
$enrollments_stmt->bind_param("i", $schedule_id);
$enrollments_stmt->execute();
$enrollments = $enrollments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>課程報名詳情 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', '微軟正黑體', sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .course-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .course-info h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
        }

        .info-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: bold;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0 20px 0;
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

        .back-link {
            color: #667eea;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="course_enrollment.php" class="back-link">
            <i class="fas fa-arrow-left"></i> 返回課程報名統計
        </a>

        <!-- 課程信息 -->
        <div class="course-info">
            <h1><?php echo sanitize($schedule['course_name']); ?></h1>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">課程日期</div>
                    <div class="info-value"><?php echo formatDate($schedule['course_date']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">課程時間</div>
                    <div class="info-value"><?php echo formatTime($schedule['start_time']); ?> - <?php echo formatTime($schedule['end_time']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">課程類型</div>
                    <div class="info-value"><?php echo sanitize($schedule['type_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">教練</div>
                    <div class="info-value"><?php echo sanitize($schedule['trainer_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">報名人數</div>
                    <div class="info-value"><?php echo $enrollments->num_rows; ?></div>
                </div>
            </div>
        </div>

        <!-- 報名人員 -->
        <h2 class="section-title"><i class="fas fa-users"></i> 報名人員列表</h2>
        
        <?php if ($enrollments->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>姓名</th>
                        <th>郵箱</th>
                        <th>電話</th>
                        <th>報名時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    while ($enrollment = $enrollments->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo sanitize($enrollment['full_name']); ?></td>
                            <td><?php echo sanitize($enrollment['email']); ?></td>
                            <td><?php echo sanitize($enrollment['phone']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($enrollment['enrollment_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 暫無報名人員
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>