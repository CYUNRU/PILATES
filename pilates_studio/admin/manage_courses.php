<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!checkRole('admin')) {
    redirectToLogin();
}

$message = '';

// 處理新增/編輯課程
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $course_type_id = (int)$_POST['course_type_id'];
        $trainer_id = (int)$_POST['trainer_id'];
        $course_name = $mysqli->real_escape_string($_POST['course_name']);
        $description = $mysqli->real_escape_string($_POST['description']);
        $price = (float)$_POST['price'];
        $max_capacity = (int)$_POST['max_capacity'];
        $course_type = $mysqli->real_escape_string($_POST['course_type']);
        
        $insert_query = "INSERT INTO courses (course_type_id, trainer_id, course_name, description, price, max_capacity, course_type) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $mysqli->prepare($insert_query);
        $insert_stmt->bind_param("iisidis", $course_type_id, $trainer_id, $course_name, $description, $price, $max_capacity, $course_type);
        
        if ($insert_stmt->execute()) {
            $message = '課程已新增';
        } else {
            $message = '新增失敗';
        }
    } elseif ($action === 'edit') {
        $course_id = (int)$_POST['course_id'];
        $course_type_id = (int)$_POST['course_type_id'];
        $trainer_id = (int)$_POST['trainer_id'];
        $course_name = $mysqli->real_escape_string($_POST['course_name']);
        $description = $mysqli->real_escape_string($_POST['description']);
        $price = (float)$_POST['price'];
        $max_capacity = (int)$_POST['max_capacity'];
        $course_type = $mysqli->real_escape_string($_POST['course_type']);
        
        $update_query = "UPDATE courses SET course_type_id = ?, trainer_id = ?, course_name = ?, 
                        description = ?, price = ?, max_capacity = ?, course_type = ? WHERE course_id = ?";
        $update_stmt = $mysqli->prepare($update_query);
        $update_stmt->bind_param("iisidisi", $course_type_id, $trainer_id, $course_name, $description, $price, $max_capacity, $course_type, $course_id);
        
        if ($update_stmt->execute()) {
            $message = '課程已更新';
        } else {
            $message = '更新失敗';
        }
    } elseif ($action === 'delete') {
        $course_id = (int)$_POST['course_id'];
        
        $delete_query = "DELETE FROM courses WHERE course_id = ?";
        $delete_stmt = $mysqli->prepare($delete_query);
        $delete_stmt->bind_param("i", $course_id);
        
        if ($delete_stmt->execute()) {
            $message = '課程已刪除';
        } else {
            $message = '刪除失敗';
        }
    }
}

// 獲取所有課程類型
$course_types = $mysqli->query("SELECT * FROM course_types ORDER BY type_name");

// 獲取所有教練
$trainers = $mysqli->query("SELECT t.trainer_id, u.full_name FROM trainers t 
                           JOIN users u ON t.user_id = u.user_id 
                           WHERE t.approval_status = 'approved'
                           ORDER BY u.full_name");

// 獲取所有課程
$courses_query = "SELECT c.*, ct.type_name, u.full_name as trainer_name 
                 FROM courses c
                 JOIN course_types ct ON c.course_type_id = ct.course_type_id
                 JOIN trainers t ON c.trainer_id = t.trainer_id
                 JOIN users u ON t.user_id = u.user_id
                 ORDER BY ct.type_name, c.course_name";
$courses = $mysqli->query($courses_query);

// 獲取要編輯的課程
$edit_course = null;
if (isset($_GET['edit'])) {
    $course_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM courses WHERE course_id = ?";
    $edit_stmt = $mysqli->prepare($edit_query);
    $edit_stmt->bind_param("i", $course_id);
    $edit_stmt->execute();
    $edit_course = $edit_stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>課程管理 - 皮拉提斯健身房</title>
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

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-submit {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-submit:hover {
            background: #764ba2;
        }

        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-reset:hover {
            background: #5a6268;
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
            font-size: 14px;
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

        .edit-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: #218838;
            text-decoration: none;
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

        .course-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-one-on-one {
            background: #e8eaf6;
            color: #667eea;
        }

        .badge-one-on-two {
            background: #f0f7ff;
            color: #0066cc;
        }

        .badge-group-class {
            background: #f0fff4;
            color: #28a745;
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

            .table {
                font-size: 12px;
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
            <li><a href="manage_users.php"><i class="fas fa-user-tie"></i> 用戶管理</a></li>
            <li><a href="manage_products.php"><i class="fas fa-box"></i> 產品管理</a></li>
            <li><a href="manage_courses.php" class="active"><i class="fas fa-book"></i> 課程管理</a></li>
        </ul>
    </div>

    <!-- 主要內容 -->
    <div class="main-content">
        <!-- 頁頭 -->
        <div class="header">
            <h1><i class="fas fa-book"></i> 課程管理</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回儀表板</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo sanitize($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 新增/編輯課程表單 -->
        <div class="form-section">
            <div class="form-title">
                <?php echo $edit_course ? '編輯課程' : '新增課程'; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_course ? 'edit' : 'add'; ?>">
                <?php if ($edit_course): ?>
                    <input type="hidden" name="course_id" value="<?php echo $edit_course['course_id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">課程類型 *</label>
                            <select name="course_type_id" class="form-control" required>
                                <option value="">請選擇課程類型</option>
                                <?php 
                                $course_types->data_seek(0);
                                while ($ct = $course_types->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $ct['course_type_id']; ?>" 
                                        <?php echo ($edit_course && $edit_course['course_type_id'] == $ct['course_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($ct['type_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">教練 *</label>
                            <select name="trainer_id" class="form-control" required>
                                <option value="">請選擇教練</option>
                                <?php 
                                $trainers->data_seek(0);
                                while ($trainer = $trainers->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $trainer['trainer_id']; ?>" 
                                        <?php echo ($edit_course && $edit_course['trainer_id'] == $trainer['trainer_id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($trainer['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">課程名稱 *</label>
                            <input type="text" name="course_name" class="form-control" 
                                   value="<?php echo $edit_course ? sanitize($edit_course['course_name']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">授課方式 *</label>
                            <select name="course_type" class="form-control" required>
                                <option value="">請選擇授課方式</option>
                                <option value="one-on-one" <?php echo ($edit_course && $edit_course['course_type'] === 'one-on-one') ? 'selected' : ''; ?>>一對一教學</option>
                                <option value="one-on-two" <?php echo ($edit_course && $edit_course['course_type'] === 'one-on-two') ? 'selected' : ''; ?>>一對二教學</option>
                                <option value="group-class" <?php echo ($edit_course && $edit_course['course_type'] === 'group-class') ? 'selected' : ''; ?>>團課</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">課程描述</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo $edit_course ? sanitize($edit_course['description']) : ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">課程價格 (NT$) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" 
                                   value="<?php echo $edit_course ? $edit_course['price'] : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">最大容納人數 *</label>
                            <input type="number" name="max_capacity" class="form-control" 
                                   value="<?php echo $edit_course ? $edit_course['max_capacity'] : '5'; ?>" 
                                   required>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> <?php echo $edit_course ? '更新' : '新增'; ?>
                    </button>
                    <?php if ($edit_course): ?>
                        <a href="manage_courses.php" class="btn-reset" style="text-decoration: none; display: inline-flex; align-items: center;">
                            <i class="fas fa-times"></i> 取消編輯
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- 課程列表 -->
        <div class="table-section">
            <h3 class="section-title"><i class="fas fa-list"></i> 課程列表</h3>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>課程名稱</th>
                            <th>課程類型</th>
                            <th>教練</th>
                            <th>授課方式</th>
                            <th>價格</th>
                            <th>容納人數</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($courses->num_rows > 0):
                            while ($course = $courses->fetch_assoc()):
                                $course_type_labels = [
                                    'one-on-one' => '一對一',
                                    'one-on-two' => '一對二',
                                    'group-class' => '團課'
                                ];
                                $badge_class = 'badge-' . str_replace('-', '-', $course['course_type']);
                        ?>
                            <tr>
                                <td><?php echo sanitize($course['course_name']); ?></td>
                                <td><?php echo sanitize($course['type_name']); ?></td>
                                <td><?php echo sanitize($course['trainer_name']); ?></td>
                                <td>
                                    <span class="course-type-badge <?php echo $badge_class; ?>">
                                        <?php echo $course_type_labels[$course['course_type']] ?? $course['course_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo formatCurrency($course['price']); ?></td>
                                <td><?php echo $course['max_capacity']; ?> 人</td>
                                <td>
                                    <a href="?edit=<?php echo $course['course_id']; ?>" class="edit-btn">
                                        <i class="fas fa-edit"></i> 編輯
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除此課程嗎？');")>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                        <button type="submit" class="delete-btn">
                                            <i class="fas fa-trash"></i> 刪除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <i class="fas fa-inbox"></i> 暫無課程
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>