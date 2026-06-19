<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// 獲取課程類型
$course_types_query = "SELECT * FROM course_types";
$course_types = $mysqli->query($course_types_query);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>皮拉提斯健身房 - 塑身從這裡開始</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft JhengHei', '微軟正黑體', sans-serif;
            color: #333;
        }
        
        /* 導航欄 */
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
            font-size: 16px;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            transform: translateY(-2px);
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        /* Hero 區域 */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
            animation: slideInDown 0.8s ease;
        }
        
        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
            animation: slideInUp 0.8s ease;
        }
        
        .hero-btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 40px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
        }
        
        .hero-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* 課程卡片 */
        .course-section {
            padding: 80px 20px;
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 50px;
            color: #333;
        }
        
        .course-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .course-card-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
        }
        
        .course-card-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .course-card-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .course-card-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .course-card-link {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        
        .course-card-link:hover {
            background: #764ba2;
            text-decoration: none;
            color: white;
        }
        
        /* 特點區域 */
        .features-section {
            padding: 80px 20px;
            background: white;
        }
        
        .feature-item {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .feature-icon {
            font-size: 50px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .feature-text {
            color: #666;
            font-size: 14px;
        }
        
        /* 頁腳 */
        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .footer-links {
            margin-bottom: 20px;
        }
        
        .footer-links a {
            color: #667eea;
            margin: 0 15px;
            text-decoration: none;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        /* 響應式 */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .section-title {
                font-size: 28px;
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
                    <li class="nav-item"><a class="nav-link" href="#courses"><i class="fas fa-book"></i> 課程</a></li>
                    <li class="nav-item"><a class="nav-link" href="products/shop.php"><i class="fas fa-shopping-bag"></i> 商城</a></li>
                    <li class="nav-item"><a class="nav-link" href="trainers/index.php"><i class="fas fa-users"></i> 教練團隊</a></li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> 購物車</a></li>
                        <li class="nav-item"><a class="nav-link" href="my_courses.php"><i class="fas fa-calendar-alt"></i> 我的課表</a></li>
                        <li class="nav-item"><a class="nav-link text-warning fw-bold" href="ai_chat.php"><i class="fas fa-robot"></i> AI智能諮詢</a></li>
                        <li class="nav-item"><a class="nav-link" href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> 登出 (<?php echo sanitize($_SESSION['full_name']); ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="auth/login.php"><i class="fas fa-sign-in-alt"></i> 登入</a></li>
                        <li class="nav-item"><a class="nav-link" href="auth/register.php"><i class="fas fa-user-plus"></i> 註冊</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero 區域 -->
    <div class="hero">
        <div>
            <h1>歡迎來到皮拉提斯健身房</h1>
            <p>塑身、增強體質、提升氣質，從這裡開始您的蛻變之旅</p>
            <a href="#courses" class="hero-btn">探索課程</a>
            <a href="auth/register.php" class="hero-btn">立即加入</a>
        </div>
    </div>
    
    <!-- 課程區域 -->
    <div class="course-section" id="courses">
        <div class="container-lg">
            <h2 class="section-title">我們的課程</h2>
            <div class="row g-4">
                <?php
                $course_types_array = [
                    ['id' => 1, 'name' => '器械皮拉提斯', 'icon' => '🏋️', 'description' => '使用專業器械進行全身塑形'],
                    ['id' => 2, 'name' => '芭蕾塑身', 'icon' => '🩰', 'description' => '結合芭蕾元素的優雅塑身課程'],
                    ['id' => 3, 'name' => '器械禪柔', 'icon' => '🧘', 'description' => '融合禪學理念的柔和課程'],
                    ['id' => 4, 'name' => '墊上皮拉提斯', 'icon' => '🛀', 'description' => '無器械基礎塑形課程'],
                    ['id' => 5, 'name' => '瑜珈服飾', 'icon' => '👕', 'description' => '高品質瑜珈運動裝備'],
                    ['id' => 6, 'name' => '教練團隊', 'icon' => '👥', 'description' => '認識我們專業的教練團隊']
                ];
                
                foreach ($course_types_array as $type):
                ?>
                <div class="col-md-6 col-lg-4">
                    <a href="courses/detail.php?id=<?php echo $type['id']; ?>" class="course-card">
                        <div class="course-card-image">
                            <?php echo $type['icon']; ?>
                        </div>
                        <div class="course-card-body">
                            <h5 class="course-card-title"><?php echo $type['name']; ?></h5>
                            <p class="course-card-text"><?php echo $type['description']; ?></p>
                            <span class="course-card-link">了解更多 →</span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- 特點區域 -->
    <div class="features-section">
        <div class="container-lg">
            <h2 class="section-title">為什麼選擇我們</h2>
            <div class="row">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-star"></i></div>
                        <h5 class="feature-title">專業教練團隊</h5>
                        <p class="feature-text">擁有豐富教學經驗的認證教練</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-equipment"></i></div>
                        <h5 class="feature-title">先進設備</h5>
                        <p class="feature-text">引進國際最新的皮拉提斯器械</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <h5 class="feature-title">效果顯著</h5>
                        <p class="feature-text">科學的課程設計，效果看得見</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-clock"></i></div>
                        <h5 class="feature-title">靈活時間</h5>
                        <p class="feature-text">多個課程時間選擇，適應您的節奏</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-community"></i></div>
                        <h5 class="feature-title">溫暖社群</h5>
                        <p class="feature-text">與志同道合的朋友一起成長</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h5 class="feature-title">安全舒適</h5>
                        <p class="feature-text">專業的安全指導和舒適環境</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 頁腳 -->
    <div class="footer">
        <div class="footer-links">
            <a href="about.php">關於我們</a>
            <a href="contact.php">聯絡我們</a>
            <a href="privacy.php">隱私政策</a>
            <a href="terms.php">服務條款</a>
        </div>
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>