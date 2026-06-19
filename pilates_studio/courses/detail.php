<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$course_type_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;


// 課程類型信息映射
$course_types_info = [
    1 => [
        'name' => '器械皮拉提斯',
        'icon' => '🏋️',
        'description' => '使用專業器械進行全身塑形，強化核心肌群，改善身體線條。',
        'intro' => '器械皮拉提斯是一種利用專門設計的設備進行的運動方式。通過精確的動作和呼吸技巧，可以有效強化核心肌群、改善姿勢、增加肌肉耐力。特別適合希望快速見效的學員。'
    ],
    2 => [
        'name' => '芭蕾塑身',
        'icon' => '🩰',
        'description' => '結合芭蕾元素的優雅塑身課程，打造芭蕾舞者身姿。',
        'intro' => '芭蕾塑身融合了經典芭蕾動作與現代塑身理念。通過優雅而精準的動作，可以有效伸長肌肉線條、改善氣質、增強身體靈活性。課程強調肌肉控制和身體姿態。'
    ],
    3 => [
        'name' => '器械禪柔',
        'icon' => '🧘',
        'description' => '融合禪學理念的柔和課程，身心靈一體修復。',
        'intro' => '器械禪柔結合了東方禪學哲學與西方皮拉提斯理論。通過緩慢、平穩的動作和冥想呼吸，幫助釋放壓力、舒緩肌肉、達到身心靈的平衡。特別適合尋求放鬆和療癒的人士。'
    ],
    4 => [
        'name' => '墊上皮拉提斯',
        'icon' => '🛀',
        'description' => '無器械基礎塑形課程，利用自身重量進行訓練。',
        'intro' => '墊上皮拉提斯是最經典的皮拉提斯形式。不需要任何器械，只需瑜珈墊就能進行。通過自身重量的控制，強化核心肌群、改善柔韌性、提升身體控制能力。適合所有健身水平的人士。'
    ],
    5 => [
        'name' => '瑜珈服飾',
        'icon' => '👕',
        'description' => '高品質瑜珈運動裝備，穿著舒適效果更佳。',
        'intro' => '我們精選國際知名品牌的瑜珈運動裝備。從瑜珈衣、褲到各種輔助道具，所有產品都經過嚴格篩選，確保品質與舒適度。穿著合適的運動服裝，能夠提升運動表現和自信心。'
    ],
    6 => [
        'name' => '教練團隊',
        'icon' => '👥',
        'description' => '認識我們專業的教練團隊，了解他們的教學風格。',
        'intro' => '我們的教練團隊由經過國際認證的專業人士組成。每位教練都擁有豐富的教學經驗，並根據學員的個人需求制定專業的訓練計劃。與我們的教練合作，您將獲得最佳的運動指導。'
    ]
];

$course_info = $course_types_info[$course_type_id] ?? $course_types_info[1];

// 獲取課程
if ($course_type_id <= 4) {
    $courses_query = "SELECT c.*, IFNULL(t.full_name, '未分配教練') as trainer_name FROM courses c 
                     LEFT JOIN trainers tr ON c.trainer_id = tr.trainer_id
                     LEFT JOIN users t ON tr.user_id = t.user_id
                     WHERE c.course_type_id = ?";
    $stmt = $mysqli->prepare($courses_query);
    $stmt->bind_param("i", $course_type_id);
    $stmt->execute();
    $courses = $stmt->get_result();
}

// 2. 獲取當月課表
$current_year = date('Y');
$current_month = date('m');
$first_day = "$current_year-$current_month-01";
$last_day = date('Y-m-t', strtotime($first_day));

// 🎯 改用 course_id 來精準過濾，這樣 id=3 就不會跟 id=4 的課表混在一起！
$schedules_query = "SELECT cs.*, c.course_name, c.course_type, c.max_capacity, IFNULL(t.full_name, '未分配教練') as trainer_name 
                   FROM course_schedules cs
                   LEFT JOIN courses c ON cs.course_id = c.course_id
                   LEFT JOIN trainers tr ON cs.on_duty_trainer_id = tr.trainer_id
                   LEFT JOIN users t ON tr.user_id = t.user_id
                   WHERE (
                       ($course_type_id = 1 AND c.course_type_id = 1) OR
                       ($course_type_id = 2 AND c.course_type_id = 2) OR
                       ($course_type_id = 3 AND c.course_id = 5) OR -- id=3 時，只抓課程ID為5的器械禪柔
                       ($course_type_id = 4 AND c.course_id = 6)    -- id=4 時，只抓課程ID為6的墊上核心
                   ) 
                   AND cs.course_date BETWEEN ? AND ?
                   ORDER BY cs.course_date, cs.start_time";

$stmt_schedules = $mysqli->prepare($schedules_query);
$stmt_schedules->bind_param("ss", $first_day, $last_day); // 💡 注意：因為 WHERE 裡面的變數直接寫進去了，這裡只需要 bind 兩個時間字串
$stmt_schedules->execute();
$schedules = $stmt_schedules->get_result();


?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $course_info['name']; ?> - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', '微軟正黑體', sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 24px;
            color: white !important;
        }

        .nav-link {
            color: white !important;
            margin: 0 10px;
        }

        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .course-header h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .course-header-icon {
            font-size: 80px;
            margin-bottom: 20px;
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
            margin-bottom: 40px;
            line-height: 1.8;
            font-size: 16px;
        }

        .pricing-box {
            background: white;
            border: 2px solid #667eea;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pricing-type {
            font-weight: bold;
            color: #667eea;
        }

        .pricing-price {
            font-size: 24px;
            font-weight: bold;
            color: #764ba2;
        }

        .schedule-table {
            margin-top: 30px;
        }

        .schedule-table table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .schedule-table thead {
            background: #667eea;
            color: white;
        }

        .schedule-table td, .schedule-table th {
            padding: 15px;
            text-align: center;
        }

        .schedule-table tbody tr:hover {
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

        .enroll-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .enroll-btn:hover {
            background: #764ba2;
        }

        .enroll-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .course-list {
            margin-top: 30px;
        }

        .course-item {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #667eea;
        }

        .course-item-title {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .course-item-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .info-tag {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
        }

        .info-tag strong {
            color: #667eea;
        }

        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .course-header h1 {
                font-size: 32px;
            }

            .pricing-box {
                flex-direction: column;
                text-align: center;
            }

            .schedule-table td, .schedule-table th {
                padding: 10px 5px;
                font-size: 12px;
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
                    <li class="nav-item"><a class="nav-link" href="../index.php#courses">課程</a></li>
                    <li class="nav-item"><a class="nav-link" href="../products/shop.php">商城</a></li>
                    <li class="nav-item"><a class="nav-link" href="../trainers/index.php">教練</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="../cart.php">購物車</a></li>
                        <li class="nav-item"><a class="nav-link" href="../auth/logout.php">登出</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../auth/login.php">登入</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 課程標題 -->
    <div class="course-header">
        <div class="course-header-icon"><?php echo $course_info['icon']; ?></div>
        <h1><?php echo $course_info['name']; ?></h1>
        <p style="font-size: 18px;"><?php echo $course_info['description']; ?></p>
    </div>

    <!-- 課程介紹 -->
    <div class="container-lg content-section">
        <h2 class="section-title"><i class="fas fa-book"></i> 課程介紹</h2>
        <div class="intro-box">
            <?php echo $course_info['intro']; ?>
        </div>

        <?php if ($course_type_id <= 4): ?>
            <!-- 課程價格 -->
            <h2 class="section-title"><i class="fas fa-tag"></i> 課程種類與價格</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="pricing-box">
                        <div>
                            <div class="pricing-type">一對一教學</div>
                            <p style="margin: 5px 0; font-size: 14px;">專業教練全程指導</p>
                        </div>
                        <div class="pricing-price">NT$1,500 / 堂</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pricing-box">
                        <div>
                            <div class="pricing-type">一對二教學</div>
                            <p style="margin: 5px 0; font-size: 14px;">雙人私教課程</p>
                        </div>
                        <div class="pricing-price">NT$1,000 / 人 / 堂</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pricing-box">
                        <div>
                            <div class="pricing-type">團課 (小班制)</div>
                            <p style="margin: 5px 0; font-size: 14px;">最多 5 人 (滿班關閉)</p>
                        </div>
                        <div class="pricing-price">NT$600 / 人 / 堂</div>
                    </div>
                </div>
            </div>

            <!-- 當月課程表 -->
            <h2 class="section-title"><i class="fas fa-calendar"></i> 當月課程表 (<?php echo getMonthName((int)($current_month ?? date('m'))); ?>)</h2>
            
            <?php if ($schedules->num_rows > 0): ?>
                <div class="schedule-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>日期</th>
                                <th>時間</th>
                                <th>課程名稱</th>
                                <th>教練</th>
                                <th>類型</th>
                                <th>報名狀況</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($schedule = $schedules->fetch_assoc()): 
                                // 獲取該班級的報名人數
                                $enrollment_query = "SELECT COUNT(*) as count FROM course_enrollments 
                                                   WHERE schedule_id = ? AND status = 'confirmed'";
                                $enrollment_stmt = $mysqli->prepare($enrollment_query);
                                $enrollment_stmt->bind_param("i", $schedule['schedule_id']);
                                $enrollment_stmt->execute();
                                $enrollment_result = $enrollment_stmt->get_result();
                                $enrollment_count = $enrollment_result->fetch_assoc();
                                
                                $current_enrollment = $enrollment_count['count'];
                                // 將對照表修改為符合你資料表實際存放的私教(private)、雙人(pair)、團課(group)
                               $course_type_display = [
                               'private' => '一對一',
                               'pair'    => '一對二',
                               'group'   => '團課'
                               ];

                                // 使用 isset() 進行防禦，如果找不到對應的代碼，就預設顯示 '團課' 或 '未設定'
                               $current_type_key = $schedule['course_type'] ?? 'group';
                               $display_type = $course_type_display[$current_type_key] ?? '團課';

                               // 檢查是否滿班（最大人數改用 c.max_capacity 撈出來的值）
                               $max_capacity = (int)($schedule['max_capacity'] ?? 5);
                               $is_full = false;
                               if ($current_type_key === 'group' && $current_enrollment >= $max_capacity) {
                               $is_full = true;
                               }
                                
                                // 檢查用戶是否已報名
                                $is_enrolled = false;
                                if (isLoggedIn()) {
                                    $check_enrollment = "SELECT enrollment_id FROM course_enrollments 
                                                       WHERE schedule_id = ? AND user_id = ? AND status = 'confirmed'";
                                    $check_stmt = $mysqli->prepare($check_enrollment);
                                    $check_stmt->bind_param("ii", $schedule['schedule_id'], $_SESSION['user_id']);
                                    $check_stmt->execute();
                                    $is_enrolled = $check_stmt->get_result()->num_rows > 0;
                                }
                            ?>
                                <tr>
                                    <td><?php echo formatDate($schedule['course_date']); ?></td>
                                    <td><?php echo formatTime($schedule['start_time']); ?> - <?php echo formatTime($schedule['end_time']); ?></td>
                                    <td><?php echo sanitize($schedule['course_name']); ?></td>
                                    <td><?php echo sanitize($schedule['trainer_name']); ?></td>
                                    <td><?php echo $display_type; ?></td>
                                    <td>
                                        <?php if ($is_full): ?>
                                            <span class="enrollment-badge enrollment-full">已滿班</span>
                                        <?php else: ?>
                                            <span class="enrollment-badge enrollment-available"><?php echo $current_enrollment; ?>/<?php echo ($schedule['course_type'] === 'group-class') ? '5' : '∞'; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!isLoggedIn()): ?>
                                            <a href="../auth/login.php" class="enroll-btn">報名</a>
                                        <?php elseif ($is_enrolled): ?>
                                            <button class="enroll-btn" disabled>已報名</button>
                                        <?php elseif ($is_full): ?>
                                            <button class="enroll-btn" disabled>已滿班</button>
                                        <?php else: ?>
                                            <form method="POST" action="enroll.php" style="display:inline;">
                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                <button type="submit" class="enroll-btn">報名</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> 本月暫無課程安排，敬請期待
                </div>
            <?php endif; ?>

        <?php elseif ($course_type_id == 5): ?>
            <!-- 商城連結 -->
            <div class="text-center" style="padding: 40px;">
                <h2 style="margin-bottom: 30px;">瀏覽我們的產品</h2>
                <a href="../products/shop.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 18px;">
                    前往商城購物 <i class="fas fa-shopping-cart"></i>
                </a>
            </div>

        <?php elseif ($course_type_id == 6): ?>
            <!-- 教練團隊連結 -->
            <div class="text-center" style="padding: 40px;">
                <h2 style="margin-bottom: 30px;">認識我們的教練</h2>
                <a href="../trainers/index.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 18px;">
                    查看教練團隊 <i class="fas fa-users"></i>
                </a>
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