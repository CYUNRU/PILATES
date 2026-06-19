<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// 檢查教練權限
if (!checkRole('trainer')) {
    redirectToLogin();
}

$user_id = $_SESSION['user_id'];

// 獲取教練信息
$trainer_query = "SELECT t.*, u.full_name, u.email, u.phone 
                 FROM trainers t
                 JOIN users u ON t.user_id = u.user_id
                 WHERE u.user_id = ?";
$trainer_stmt = $mysqli->prepare($trainer_query);
$trainer_stmt->bind_param("i", $user_id);
$trainer_stmt->execute();
$trainer_result = $trainer_stmt->get_result();

if ($trainer_result->num_rows === 0) {
    redirectToLogin();
}

$trainer = $trainer_result->fetch_assoc();
$trainer_id = $trainer['trainer_id'];

// 獲取教練的課程統計
$stats_query = "SELECT 
    COUNT(DISTINCT cs.schedule_id) as total_classes,
    SUM(CASE WHEN cs.course_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_classes,
    SUM(CASE WHEN cs.course_date = CURDATE() THEN 1 ELSE 0 END) as today_classes,
    COUNT(DISTINCT ce.enrollment_id) as total_students
    FROM course_schedules cs
    LEFT JOIN course_enrollments ce ON cs.schedule_id = ce.schedule_id AND ce.status = 'confirmed'
    WHERE cs.trainer_id = ?";
$stats_stmt = $mysqli->prepare($stats_query);
$stats_stmt->bind_param("i", $trainer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// 獲取近期課程
$courses_query = "SELECT cs.*, c.course_name, ct.type_name,
                 COUNT(ce.enrollment_id) as enrollment_count
                 FROM course_schedules cs
                 JOIN courses c ON cs.course_id = c.course_id
                 JOIN course_types ct ON c.course_type_id = ct.course_type_id
                 LEFT JOIN course_enrollments ce ON cs.schedule_id = ce.schedule_id AND ce.status = 'confirmed'
                 WHERE cs.trainer_id = ? AND cs.course_date >= CURDATE()
                 GROUP BY cs.schedule_id
                 ORDER BY cs.course_date ASC, cs.start_time ASC
                 LIMIT 5";
$courses_stmt = $mysqli->prepare($courses_query);
$courses_stmt->bind_param("i", $trainer_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();

// 獲取介紹審核狀態
$approval_status = $trainer['approval_status'];
$has_pending = !empty($trainer['pending_introduction']);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教練儀表板 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', '微軟正黑體', sans-serif;
            background: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: white !important;
            font-size: 24px;
        }

        .nav-link {
            color: white !important;
            margin: 0 10px;
        }

        .container-main {
            padding: 40px 20px;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            color: #667eea;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .edit-btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .edit-btn:hover {
            background: #764ba2;
            text-decoration: none;
            color: white;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #c82333;
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

        .status-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }

        .status-card h3 {
            color: #667eea;
            margin-bottom: 15px;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0 20px 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .courses-list {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .course-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #667eea;
        }

        .course-item:last-child {
            margin-bottom: 0;
        }

        .course-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .course-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            font-size: 14px;
            color: #666;
        }

        .course-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .course-detail-item i {
            color: #667eea;
            width: 20px;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header-actions {
                justify-content: center;
                width: 100%;
            }

            .edit-btn, .logout-btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-lg">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell"></i> 皮拉提斯健身房
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">首頁</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">我的儀表板</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">登出</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 主要內容 -->
    <div class="container-lg container-main">
        <!-- 頁頭 -->
        <div class="header">
            <div>
                <h1><i class="fas fa-user-tie"></i> 教練儀表板</h1>
                <p style="margin: 10px 0 0 0; color: #999;">歡迎回來, <?php echo sanitize($_SESSION['full_name']); ?></p>
            </div>
            <div class="header-actions">
                <a href="edit_profile.php" class="edit-btn">
                    <i class="fas fa-edit"></i> 編輯介紹
                </a>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> 登出
                </a>
            </div>
        </div>

        <!-- 介紹審核狀態 -->
        <div class="status-card">
            <h3><i class="fas fa-check-circle"></i> 介紹審核狀態</h3>
            <div class="status-item">
                <span>當前狀態</span>
                <span>
                    <?php if ($approval_status === 'approved'): ?>
                        <span class="status-badge status-approved">已批准</span>
                    <?php elseif ($approval_status === 'rejected'): ?>
                        <span class="status-badge status-rejected">已拒絕</span>
                    <?php else: ?>
                        <span class="status-badge status-pending">待審核</span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($has_pending): ?>
                <div class="status-item">
                    <span><i class="fas fa-info-circle"></i> 有新的介紹待審核</span>
                    <span class="status-badge status-pending">待審核</span>
                </div>
            <?php endif; ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                <a href="edit_profile.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> 修改介紹
                </a>
            </div>
        </div>

        <!-- 統計卡片 -->
        <h2 class="section-title"><i class="fas fa-chart-line"></i> 我的統計</h2>
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-card-value"><?php echo $stats['total_classes'] ?? 0; ?></div>
                    <div class="stat-card-label">總課數</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-calendar"></i></div>
                    <div class="stat-card-value"><?php echo $stats['upcoming_classes'] ?? 0; ?></div>
                    <div class="stat-card-label">待上課</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-card-value"><?php echo $stats['today_classes'] ?? 0; ?></div>
                    <div class="stat-card-label">今日課程</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-card-value"><?php echo $stats['total_students'] ?? 0; ?></div>
                    <div class="stat-card-label">教學人數</div>
                </div>
            </div>
        </div>

        <!-- 近期課程 -->
        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> 近期課程安排（5堂）</h2>
        <div class="courses-list">
            <?php if ($courses->num_rows > 0): ?>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <div class="course-item">
                        <div class="course-title">
                            <?php echo sanitize($course['course_name']); ?>
                            <span style="font-size: 12px; color: #999; font-weight: normal; margin-left: 10px;">
                                (<?php echo sanitize($course['type_name']); ?>)
                            </span>
                        </div>
                        <div class="course-details">
                            <div class="course-detail-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo formatDate($course['course_date']); ?></span>
                            </div>
                            <div class="course-detail-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo formatTime($course['start_time']); ?> - <?php echo formatTime($course['end_time']); ?></span>
                            </div>
                            <div class="course-detail-item">
                                <i class="fas fa-users"></i>
                                <span><?php echo $course['enrollment_count']; ?> 人報名</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-calendar-times" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                    <p>暫無近期課程安排</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- 教練信息卡片 -->
        <h2 class="section-title"><i class="fas fa-user-circle"></i> 我的信息</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="status-card">
                    <h3>基本信息</h3>
                    <div class="status-item">
                        <span>姓名</span>
                        <strong><?php echo sanitize($trainer['full_name']); ?></strong>
                    </div>
                    <div class="status-item">
                        <span>郵箱</span>
                        <strong><?php echo sanitize($trainer['email']); ?></strong>
                    </div>
                    <div class="status-item">
                        <span>電話</span>
                        <strong><?php echo sanitize($trainer['phone']); ?></strong>
                    </div>
                    <div class="status-item">
                        <span>專長</span>
                        <strong><?php echo sanitize($trainer['specialization'] ?? '未設置'); ?></strong>
                    </div>
                    <div class="status-item">
                        <span>教學經驗</span>
                        <strong><?php echo intval($trainer['experience_years'] ?? 0); ?> 年</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="status-card">
                    <h3>介紹信息</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto;">
                        <?php echo sanitize($trainer['introduction'] ?? '暫無介紹'); ?>
                    </div>
                    <a href="edit_profile.php" class="btn btn-primary btn-sm" style="margin-top: 15px;">
                        <i class="fas fa-edit"></i> 編輯介紹
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>