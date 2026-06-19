<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// 🔒 安全防禦：如果沒登入，直接踢回登入頁
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 🎯 處理 AJAX 更新訂閱狀態的請求
if (isset($_POST['action']) && $_POST['action'] === 'toggle_subscription') {
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $update_query = "UPDATE users SET is_subscribed = ? WHERE user_id = ?";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param("ii", $status, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit(); // 🎯 處理完 AJAX 直接結束，不往下渲染 HTML
}

// 📡 撈出目前使用者的訂閱狀態
$user_query = "SELECT is_subscribed FROM users WHERE user_id = ?";
$user_stmt = $mysqli->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$is_subscribed = $user_data['is_subscribed'] ?? 1;
$user_stmt->close();

// 🧘 撈出該學員的所有報名紀錄
$my_courses_query = "SELECT 
                        ce.enrollment_id, ce.enrollment_date, ce.status as enrollment_status,
                        cs.course_date, cs.start_time, cs.end_time,
                        c.course_name, ct.type_name,
                        IFNULL(u.full_name, '未分配教練') as trainer_name
                     FROM course_enrollments ce
                     JOIN course_schedules cs ON ce.schedule_id = cs.schedule_id
                     JOIN courses c ON cs.course_id = c.course_id
                     JOIN course_types ct ON c.course_type_id = ct.course_type_id
                     LEFT JOIN trainers t ON cs.trainer_id = t.trainer_id
                     LEFT JOIN users u ON t.user_id = u.user_id
                     WHERE ce.user_id = ?
                     ORDER BY cs.course_date DESC, cs.start_time DESC";

$stmt = $mysqli->prepare($my_courses_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的課程紀錄 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Microsoft JhengHei', sans-serif; background-color: #f8f9fa; }
        .navbar-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .course-card { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        /* 訂閱控制區區塊樣式 */
        .sub-box { background: #ffffff; border-left: 4px solid #667eea; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-dumbbell"></i> 皮拉提斯健身房</a>
            <div class="text-white">
                <i class="fas fa-user-circle"></i> 歡迎回來，<?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <a href="index.php" class="btn btn-sm btn-outline-light ms-3">回首頁選課</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h2 class="text-secondary mb-0"><i class="fas fa-calendar-check text-primary"></i> My Schedule 我的專屬課表</h2>
        </div>

        <div class="sub-box p-3 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1 fw-bold text-dark"><i class="fas fa-paper-plane text-primary me-2"></i>每月全新課表電子報推播</h6>
                <small class="text-muted">開啟後，系統將會在每個月底自動寄送下個月的全新精選課表至您的 Email 郵箱。</small>
            </div>
            <div class="form-check form-switch fs-5">
                <input class="form-check-input" type="checkbox" role="switch" id="subSwitch" <?php echo $is_subscribed == 1 ? 'checked' : ''; ?>>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="row g-3">
                <?php while ($course = $result->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="card course-card p-3">
                            <div class="row align-items-center">
                                <div class="col-md-3 border-end">
                                    <h5 class="text-primary mb-1"><i class="far fa-calendar-alt"></i> <?php echo $course['course_date']; ?></h5>
                                    <small class="text-muted"><i class="far fa-clock"></i> <?php echo substr($course['start_time'], 0, 5) . ' - ' . substr($course['end_time'], 0, 5); ?></small>
                                </div>
                                <div class="col-md-5 py-2 py-md-0">
                                    <span class="badge bg-secondary mb-1"><?php echo htmlspecialchars($course['type_name']); ?></span>
                                    <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                    <p class="text-muted mb-0"><i class="fas fa-user-tie"></i> 授課教練：<?php echo htmlspecialchars($course['trainer_name']); ?></p>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block">報名時間：</small>
                                    <small><?php echo date('Y-m-d', strtotime($course['enrollment_date'])); ?></small>
                                </div>
                                <div class="col-md-2 text-md-end">
                                    <?php if ($course['enrollment_status'] === 'confirmed'): ?>
                                        <span class="status-badge bg-success text-white"><i class="fas fa-check"></i> 報名成功</span>
                                    <?php else: ?>
                                        <span class="status-badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> 審核中</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded shadow-sm">
                <i class="fas fa-folder-open text-muted mb-3" style="font-size: 50px;"></i>
                <p class="text-muted fs-5">您目前還沒有報名任何課程喔！</p>
                <a href="index.php" class="btn btn-primary px-4">立刻去選課排表</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.getElementById('subSwitch').addEventListener('change', function() {
            const isChecked = this.checked ? 1 : 0;
            
            // 使用 FormData 傳送狀態
            const formData = new FormData();
            formData.append('action', 'toggle_subscription');
            formData.append('status', isChecked);

            fetch('my_courses.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('訂閱狀態已成功同步至資料庫！');
                }
            })
            .catch(error => console.error('錯誤:', error));
        });
    </script>
</body>
</html>