<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

$message = '';

// 處理審核操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $trainer_id = (int)$_POST['trainer_id'];
    
    if ($action === 'approve') {
        $update_query = "UPDATE trainers SET approval_status = 'approved', 
                        introduction = pending_introduction,
                        pending_introduction = NULL,
                        reviewed_by_admin_id = ?,
                        reviewed_by_admin_at = NOW()
                        WHERE trainer_id = ?";
        $update_stmt = $mysqli->prepare($update_query);
        $user_id = $_SESSION['user_id'];
        $update_stmt->bind_param("ii", $user_id, $trainer_id);
        
        if ($update_stmt->execute()) {
            $message = '已批准教練介紹';
        }
    } else if ($action === 'reject') {
        $update_query = "UPDATE trainers SET approval_status = 'rejected',
                        pending_introduction = NULL,
                        reviewed_by_admin_id = ?,
                        reviewed_by_admin_at = NOW()
                        WHERE trainer_id = ?";
        $update_stmt = $mysqli->prepare($update_query);
        $user_id = $_SESSION['user_id'];
        $update_stmt->bind_param("ii", $user_id, $trainer_id);
        
        if ($update_stmt->execute()) {
            $message = '已拒絕教練介紹';
        }
    }
}

// 獲取待審核的教練介紹
$pending_query = "SELECT t.*, u.full_name, u.email, u.phone, admin.full_name as reviewed_by
                 FROM trainers t
                 JOIN users u ON t.user_id = u.user_id
                 LEFT JOIN users admin ON t.reviewed_by_admin_id = admin.user_id
                 WHERE t.approval_status = 'pending' OR (t.approval_status = 'rejected' AND t.pending_introduction IS NOT NULL)
                 ORDER BY t.updated_by_trainer_at DESC";
$pending = $mysqli->query($pending_query);

// 獲取已批准的教練
$approved_query = "SELECT t.*, u.full_name, u.email, admin.full_name as reviewed_by, t.reviewed_by_admin_at
                  FROM trainers t
                  JOIN users u ON t.user_id = u.user_id
                  LEFT JOIN users admin ON t.reviewed_by_admin_id = admin.user_id
                  WHERE t.approval_status = 'approved'
                  ORDER BY t.reviewed_by_admin_at DESC";
$approved = $mysqli->query($approved_query);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教練介紹審核 - 皮拉提斯健身房</title>
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

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .trainer-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }

        .trainer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .trainer-info h3 {
            color: #667eea;
            margin-bottom: 8px;
        }

        .trainer-contact {
            font-size: 14px;
            color: #666;
        }

        .trainer-contact p {
            margin: 5px 0;
        }

        .trainer-introduction {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            line-height: 1.6;
        }

        .trainer-introduction label {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            display: block;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #999;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

            .trainer-header {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
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
            <li><a href="trainer_schedule.php"><i class="fas fa-users"></i> 教練班表</a></li>
            <li><a href="trainer_approval.php" class="active"><i class="fas fa-check-circle"></i> 教練介紹審核</a></li>
            <li><a href="manage_users.php"><i class="fas fa-user-tie"></i> 用戶管理</a></li>
            <li><a href="manage_products.php"><i class="fas fa-box"></i> 產品管理</a></li>
            <li><a href="manage_courses.php"><i class="fas fa-book"></i> 課程管理</a></li>
        </ul>
    </div>

    <!-- 主要內容 -->
    <div class="main-content">
        <!-- 頁頭 -->
        <div class="header">
            <h1><i class="fas fa-check-circle"></i> 教練介紹審核</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo sanitize($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 標籤 -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('pending')">
                待審核 <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">
                    <?php echo $pending->num_rows; ?>
                </span>
            </button>
            <button class="tab-btn" onclick="switchTab('approved')">
                已批准 <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">
                    <?php echo $approved->num_rows; ?>
                </span>
            </button>
        </div>

        <!-- 待審核標籤 -->
        <div id="pending" class="tab-content active">
            <h3 class="section-title"><i class="fas fa-hourglass"></i> 待審核的教練介紹</h3>
            
            <?php 
            if ($pending->num_rows > 0):
                while ($trainer = $pending->fetch_assoc()):
            ?>
                <div class="trainer-card">
                    <div class="trainer-header">
                        <div class="trainer-info">
                            <h3>
                                <?php echo sanitize($trainer['full_name']); ?>
                                <?php if ($trainer['approval_status'] === 'rejected'): ?>
                                    <span class="status-badge status-rejected">已拒絕</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">待審核</span>
                                <?php endif; ?>
                            </h3>
                            <div class="trainer-contact">
                                <p><i class="fas fa-envelope"></i> <?php echo sanitize($trainer['email']); ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo sanitize($trainer['phone']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="trainer-introduction">
                        <label><i class="fas fa-file-alt"></i> 教練介紹</label>
                        <div><?php echo sanitize($trainer['pending_introduction'] ?? $trainer['introduction']); ?></div>
                    </div>

                    <div class="action-buttons">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="trainer_id" value="<?php echo $trainer['trainer_id']; ?>">
                            <button type="submit" class="btn-approve">
                                <i class="fas fa-check"></i> 批准
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="trainer_id" value="<?php echo $trainer['trainer_id']; ?>">
                            <button type="submit" class="btn-reject">
                                <i class="fas fa-times"></i> 拒絕
                            </button>
                        </form>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 暫無待審核的教練介紹
                </div>
            <?php endif; ?>
        </div>

        <!-- 已批准標籤 -->
        <div id="approved" class="tab-content">
            <h3 class="section-title"><i class="fas fa-check-circle"></i> 已批准的教練介紹</h3>
            
            <?php 
            if ($approved->num_rows > 0):
                while ($trainer = $approved->fetch_assoc()):
            ?>
                <div class="trainer-card" style="border-left-color: #28a745;">
                    <div class="trainer-header">
                        <div class="trainer-info">
                            <h3>
                                <?php echo sanitize($trainer['full_name']); ?>
                                <span class="status-badge status-approved">已批准</span>
                            </h3>
                            <div class="trainer-contact">
                                <p><i class="fas fa-envelope"></i> <?php echo sanitize($trainer['email']); ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo sanitize($trainer['phone']); ?></p>
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 12px; color: #999;">
                            <p>批准者: <?php echo sanitize($trainer['reviewed_by'] ?? '系統'); ?></p>
                            <p>批准時間: <?php echo date('Y-m-d H:i', strtotime($trainer['reviewed_by_admin_at'])); ?></p>
                        </div>
                    </div>

                    <div class="trainer-introduction">
                        <label><i class="fas fa-file-alt"></i> 教練介紹</label>
                        <div><?php echo sanitize($trainer['introduction']); ?></div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 暫無已批准的教練介紹
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchTab(tabName) {
            // 隱藏所有標籤內容
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // 移除所有標籤按鈕的 active 類
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // 顯示選中的標籤內容
            document.getElementById(tabName).classList.add('active');

            // 添加 active 類到點擊的按鈕
            event.target.classList.add('active');
        }
    </script>
</body>
</html>