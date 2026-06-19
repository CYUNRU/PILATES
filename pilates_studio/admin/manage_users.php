<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

$message = '';

// 處理刪除用戶
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = (int)$_POST['user_id'];
    
    // 不能刪除自己
    if ($user_id === $_SESSION['user_id']) {
        $message = '無法刪除您自己的帳戶';
    } else {
        $delete_query = "DELETE FROM users WHERE user_id = ?";
        $delete_stmt = $mysqli->prepare($delete_query);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $message = '用戶已刪除';
        } else {
            $message = '刪除失敗';
        }
    }
}

// 獲取所有用戶
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users = $mysqli->query($users_query);

// 獲取用戶統計
$user_stats = $mysqli->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶管理 - 皮拉提斯健身房</title>
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

        .badge-admin {
            background: #dc3545;
            color: white;
        }

        .badge-trainer {
            background: #28a745;
            color: white;
        }

        .badge-customer {
            background: #667eea;
            color: white;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            background: #c82333;
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
            <li><a href="dashboard_charts.php"><i class="fas fa-chart-bar"></i> 統計圖表</a></li>
            <li><a href="course_enrollment.php"><i class="fas fa-calendar"></i> 課程報名統計</a></li>
            <li><a href="product_sales.php"><i class="fas fa-shopping-cart"></i> 商品銷售統計</a></li>
            <li><a href="trainer_schedule.php"><i class="fas fa-users"></i> 教練班表</a></li>
            <li><a href="trainer_approval.php"><i class="fas fa-check-circle"></i> 教練介紹審核</a></li>
            <li><a href="manage_users.php" class="active"><i class="fas fa-user-tie"></i> 用戶管理</a></li>
            <li><a href="manage_products.php"><i class="fas fa-box"></i> 產品管理</a></li>
            <li><a href="manage_courses.php"><i class="fas fa-book"></i> 課程管理</a></li>
        </ul>
    </div>

    <!-- 主要內容 -->
    <div class="main-content">
        <!-- 頁頭 -->
        <div class="header">
            <h1><i class="fas fa-user-tie"></i> 用戶管理</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i> <?php echo sanitize($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 用戶統計 -->
        <h3 style="margin-bottom: 20px; color: #667eea;">用戶統計</h3>
        <div class="row" style="margin-bottom: 30px;">
            <?php 
            $total_users = 0;
            foreach ($user_stats as $stat):
                $total_users += $stat['count'];
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div style="font-weight: bold; color: #667eea; margin-bottom: 10px;">
                            <?php 
                            $role_names = ['admin' => '管理員', 'trainer' => '教練', 'customer' => '顧客'];
                            echo $role_names[$stat['role']] ?? $stat['role'];
                            ?>
                        </div>
                        <div class="stat-card-value"><?php echo $stat['count']; ?></div>
                        <div class="stat-card-label">人數</div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div style="font-weight: bold; color: #667eea; margin-bottom: 10px;">
                        總計
                    </div>
                    <div class="stat-card-value"><?php echo $total_users; ?></div>
                    <div class="stat-card-label">用戶總數</div>
                </div>
            </div>
        </div>

        <!-- 用戶列表 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-list"></i> 用戶列表</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>用戶名</th>
                        <th>郵箱</th>
                        <th>姓名</th>
                        <th>電話</th>
                        <th>角色</th>
                        <th>註冊時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($users->num_rows > 0):
                        while ($user = $users->fetch_assoc()):
                            $role_names = ['admin' => '管理員', 'trainer' => '教練', 'customer' => '顧客'];
                            $badge_class = 'badge-' . $user['role'];
                    ?>
                        <tr>
                            <td><?php echo sanitize($user['username']); ?></td>
                            <td><?php echo sanitize($user['email']); ?></td>
                            <td><?php echo sanitize($user['full_name']); ?></td>
                            <td><?php echo sanitize($user['phone']); ?></td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo $role_names[$user['role']] ?? $user['role']; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除此用戶嗎？');")>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="delete-btn">
                                            <i class="fas fa-trash"></i> 刪除
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 12px;">無法刪除自己</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>