<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// 🎯 乾淨通電版：利用 LEFT JOIN 確保教練卡片完美渲染，並正常帶出 user 姓名與聯絡資訊
$trainers_query = "SELECT t.*, IFNULL(u.full_name, '未命名教練') as full_name, IFNULL(u.email, '無 Email') as email, IFNULL(u.phone, '無電話') as phone 
                  FROM trainers t
                  LEFT JOIN users u ON t.user_id = u.user_id";
$trainers_result = $mysqli->query($trainers_query);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教練團隊 - 皮拉提斯健身房</title>
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

        .nav-link {
            color: white !important;
        }

        .trainers-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .trainers-header h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .trainers-container {
            padding: 60px 20px;
        }

        .trainer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .trainer-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .trainer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .trainer-image {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 100px;
        }

        .trainer-body {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .trainer-name {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .trainer-specialty {
            font-size: 14px;
            color: #999;
            margin-bottom: 15px;
        }

        .trainer-experience {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }

        .trainer-experience i {
            margin-right: 8px;
            color: #667eea;
        }

        .trainer-bio {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .trainer-info {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .trainer-info-item {
            flex: 1;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
        }

        .trainer-info-label {
            color: #999;
            font-weight: bold;
        }

        .trainer-info-value {
            color: #667eea;
            font-weight: bold;
        }

        .trainer-link {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-top: auto;
        }

        .trainer-link:hover {
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
            .trainer-grid {
                grid-template-columns: 1fr;
            }

            .trainers-header h1 {
                font-size: 32px;
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
                    <li class="nav-item"><a class="nav-link" href="../products/shop.php">商城</a></li>
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

    <!-- 教練標題 -->
    <div class="trainers-header">
        <h1><i class="fas fa-users"></i> 教練團隊</h1>
        <p>認識我們的專業教練，開始您的健身之旅</p>
    </div>

    <!-- 教練網格 -->
    <div class="container my-5">
    <div class="row g-4">
        <?php if ($trainers_result && $trainers_result->num_rows > 0): ?>
            <?php while ($trainer = $trainers_result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff; transition: transform 0.2s;">
                        <div class="card-body p-4 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary mb-3" style="width: 70px; height: 70px; border-radius: 50%; font-size: 28px; font-weight: bold; border: 2px solid #667eea;">
                                <?php echo mb_substr($trainer['full_name'], 0, 1, 'utf-8'); ?>
                            </div>
                            
                            <h4 class="fw-bold mb-1" style="color: #333;"><?php echo htmlspecialchars($trainer['full_name']); ?></h4>
                            <span class="badge bg-primary-subtle text-primary mb-3 px-3 py-2 rounded-pill" style="font-size: 12px; background-color: #e8f0fe;">
                                <?php echo htmlspecialchars($trainer['specialization']); ?>
                            </span>
                            
                            <div class="text-muted small mb-3">💼 教學資歷：<strong><?php echo $trainer['experience_years']; ?></strong> 年</div>
                            <hr class="opacity-25">
                            
                            <p class="text-secondary small text-start mb-3" style="line-height: 1.6; height: 70px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                <?php echo htmlspecialchars($trainer['introduction']); ?>
                            </p>
                            
                            <div class="bg-light p-2 rounded small text-start text-muted" style="font-size: 12px;">
                                <div><i class="fas fa-envelope me-2 text-primary"></i><?php echo htmlspecialchars($trainer['email']); ?></div>
                                <div class="mt-1"><i class="fas fa-phone me-2 text-primary"></i><?php echo htmlspecialchars($trainer['phone']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i> 暫無教練信息
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>