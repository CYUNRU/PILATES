<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$trainer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 獲取教練信息
$trainer_query = "SELECT t.*, u.full_name, u.email, u.phone 
                 FROM trainers t
                 JOIN users u ON t.user_id = u.user_id
                 WHERE t.trainer_id = ? AND t.approval_status = 'approved'";
$trainer_stmt = $mysqli->prepare($trainer_query);
$trainer_stmt->bind_param("i", $trainer_id);
$trainer_stmt->execute();
$trainer_result = $trainer_stmt->get_result();

if ($trainer_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$trainer = $trainer_result->fetch_assoc();

// 獲取該教練的課程
$courses_query = "SELECT cs.*, c.course_name, ct.type_name
                  FROM course_schedules cs
                  JOIN courses c ON cs.course_id = c.course_id
                  JOIN course_types ct ON c.course_type_id = ct.course_type_id
                  WHERE cs.on_duty_trainer_id = ? AND cs.course_date >= CURDATE()
                  ORDER BY cs.course_date, cs.start_time
                  LIMIT 5";
$courses_stmt = $mysqli->prepare($courses_query);
$courses_stmt->bind_param("i", $trainer_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($trainer['full_name']); ?> - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', '微軟正黑體', sans-serif;
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

        .trainer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
        }

        .trainer-header-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 40px;
            align-items: center;
        }

        .trainer-avatar {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 150px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .trainer-info-header {
            color: white;
        }

        .trainer-info-header h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .trainer-info-header .specialty {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .trainer-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .content-section {
            padding: 60px 20px;
        }

        .section-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
            border-left: 5px solid #667eea;
            padding-left: 15px;
        }

        .intro-box {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 40px;
        }

        .courses-list {
            margin-top: 30px;
        }

        .course-item {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .course-info {
            flex: 1;
        }

        .course-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }

        .course-details {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
        }

        .course-detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .contact-info {
            background: #e8eaf6;
            padding: 30px;
            border-radius: 10px;
            margin-top: 40px;
        }

        .contact-info h3 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .contact-item i {
            width: 30px;
            color: #667eea;
            font-size: 18px;
        }

        .back-link {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #764ba2;
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
            .trainer-header-content {
                grid-template-columns: 1fr;
            }

            .trainer-avatar {
                width: 100%;
                max-width: 300px;
                margin: 0 auto;
            }

            .trainer-info-header h1 {
                font-size: 28px;
            }

            .trainer-stats {
                grid-template-columns: 1fr;
            }

            .course-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .course-details {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-dumbbell"></i> 皮拉提斯健身房
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">首頁</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php">教練</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="../auth/logout.php">登出</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../auth/login.php">登入</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 教練資訊標題 -->
    <div class="trainer-header">
        <div class="container-lg">
            <div class="trainer-header-content">
                <div class="trainer-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="trainer-info-header">
                    <h1><?php echo sanitize($trainer['full_name']); ?></h1>
                    <p class="specialty"><?php echo sanitize($trainer['specialization'] ?? '皮拉提斯教練'); ?></p>
                    
                    <div class="trainer-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo intval($trainer['experience_years'] ?? 0); ?></span>
                            <span class="stat-label">年教學經驗</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $courses->num_rows; ?></span>
                            <span class="stat-label">近期課程</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">★ 5.0</span>
                            <span class="stat-label">客戶評分</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 內容區域 -->
    <div class="container-lg content-section">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> 返回教練列表
        </a>

        <!-- 介紹 -->
        <h2 class="section-title"><i class="fas fa-book"></i> 教練介紹</h2>
        <div class="intro-box">
            <?php echo sanitize($trainer['introduction'] ?? ''); ?>
        </div>

        <!-- 聯絡方式 -->
        <div class="contact-info">
            <h3><i class="fas fa-phone"></i> 聯絡方式</h3>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <span><?php echo sanitize($trainer['email']); ?></span>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <span><?php echo sanitize($trainer['phone']); ?></span>
            </div>
        </div>

        <!-- 近期課程 -->
        <?php if ($courses->num_rows > 0): ?>
            <h2 class="section-title" style="margin-top: 40px;"><i class="fas fa-calendar"></i> 近期課程安排</h2>
            <div class="courses-list">
                <?php 
                $courses->data_seek(0);
                while ($course = $courses->fetch_assoc()): 
                ?>
                    <div class="course-item">
                        <div class="course-info">
                            <div class="course-title"><?php echo sanitize($course['course_name']); ?></div>
                            <div class="course-details">
                                <div class="course-detail-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo formatDate($course['course_date']); ?></span>
                                </div>
                                <div class="course-detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo formatTime($course['start_time']); ?> - <?php echo formatTime($course['end_time']); ?></span>
                                </div>
                                <div class="course-detail-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo sanitize($course['type_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>