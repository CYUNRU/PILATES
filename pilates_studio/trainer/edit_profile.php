<?php
require_once '../config/database.php';
require_once '../config/functions.php';

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

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialization = $mysqli->real_escape_string($_POST['specialization']);
    $experience_years = (int)$_POST['experience_years'];
    $introduction = $mysqli->real_escape_string($_POST['introduction']);
    
    // 驗證
    if (empty($introduction) || strlen($introduction) < 50) {
        $error_message = '介紹內容至少需要50個字符';
    } else if ($experience_years < 0 || $experience_years > 50) {
        $error_message = '教學經驗年數不正確';
    } else {
        // 更新介紹
        $update_query = "UPDATE trainers SET 
                        specialization = ?,
                        experience_years = ?,
                        pending_introduction = ?,
                        approval_status = 'pending',
                        updated_by_trainer_at = NOW()
                        WHERE trainer_id = ?";
        $update_stmt = $mysqli->prepare($update_query);
        $update_stmt->bind_param("sisi", $specialization, $experience_years, $introduction, $trainer_id);
        
        if ($update_stmt->execute()) {
            $success_message = '介紹已提交審核，感謝您！';
            
            // 重新加載教練信息
            $trainer_stmt->execute();
            $trainer = $trainer_result->fetch_assoc();
        } else {
            $error_message = '更新失敗，請稍後重試';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯介紹 - 皮拉提斯健身房</title>
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
        }

        .container-main {
            padding: 40px 20px;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            margin: 0 auto;
        }

        .form-title {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }

        .form-control {
            border: 2px solid #ddd;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 200px;
            font-family: inherit;
        }

        .form-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .character-count {
            text-align: right;
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .btn-submit {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #764ba2;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #5a6268;
            text-decoration: none;
            color: white;
        }

        .info-box {
            background: #e8eaf6;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }

        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">儀表板</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">登出</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 主要內容 -->
    <div class="container-lg container-main">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> 返回儀表板
        </a>

        <div class="form-container">
            <h1 class="form-title"><i class="fas fa-edit"></i> 編輯教練介紹</h1>

            <!-- 信息提示 -->
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> <strong>重要提示：</strong></p>
                <p>您在此編輯的介紹將需要通過管理員的審核才能發佈。</p>
                <p>介紹內容至少需要50個字符，請詳細描述您的教學經驗、專長和教學風格。</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo sanitize($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="specialization" class="form-label">專長領域</label>
                        <input type="text" id="specialization" name="specialization" class="form-control" 
                               value="<?php echo sanitize($trainer['specialization'] ?? ''); ?>" 
                               placeholder="例如：器械皮拉提斯、芭蕾塑身" required>
                        <div class="form-text">請簡要描述您的主要教學專長</div>
                    </div>

                    <div class="form-group">
                        <label for="experience_years" class="form-label">教學經驗（年）</label>
                        <input type="number" id="experience_years" name="experience_years" class="form-control" 
                               value="<?php echo intval($trainer['experience_years'] ?? 0); ?>" 
                               min="0" max="50" required>
                        <div class="form-text">請輸入您的教學年數</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="introduction" class="form-label">教練介紹</label>
                    <textarea id="introduction" name="introduction" class="form-control" 
                             placeholder="請詳細介紹您的教學經驗、專長、教學風格和對學員的幫助..." 
                             required onkeyup="updateCharCount()"><?php echo sanitize($trainer['pending_introduction'] ?? $trainer['introduction'] ?? ''); ?></textarea>
                    <div class="character-count">
                        <span id="charCount">0</span> / 最少需要 50 字符
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> 提交審核
                </button>
            </form>

            <hr style="margin: 30px 0;">

            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h5 style="color: #667eea; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i> 當前發佈的介紹
                </h5>
                <?php if (!empty($trainer['introduction'])): ?>
                    <div style="line-height: 1.6; color: #666;">
                        <?php echo sanitize($trainer['introduction']); ?>
                    </div>
                    <div style="margin-top: 15px; font-size: 12px; color: #999;">
                        <i class="fas fa-check"></i> 已批准
                    </div>
                <?php else: ?>
                    <p style="color: #999;">暫無已批准的介紹</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 頁腳 -->
    <div class="footer">
        <p>&copy; 2024 皮拉提斯健身房 - 版權所有</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCharCount() {
            const textarea = document.getElementById('introduction');
            const count = textarea.value.length;
            document.getElementById('charCount').textContent = count;
        }

        // 初始化字符計數
        updateCharCount();
    </script>
</body>
</html>