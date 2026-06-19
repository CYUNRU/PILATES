<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// 檢查用戶是否登入
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = (int)$_POST['schedule_id'];
    $user_id = $_SESSION['user_id'];
    
    // 檢查課程是否存在
    $schedule_query = "SELECT cs.*, c.course_type FROM course_schedules cs
                      JOIN courses c ON cs.course_id = c.course_id
                      WHERE cs.schedule_id = ?";
    $schedule_stmt = $mysqli->prepare($schedule_query);
    $schedule_stmt->bind_param("i", $schedule_id);
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();
    
    if ($schedule_result->num_rows === 0) {
        setErrorMessage('課程不存在');
        header('Location: ../index.php');
        exit();
    }
    
    $schedule = $schedule_result->fetch_assoc();
    
    // 檢查是否已報名
    $check_query = "SELECT enrollment_id FROM course_enrollments 
                   WHERE schedule_id = ? AND user_id = ? AND status = 'confirmed'";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param("ii", $schedule_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        setErrorMessage('您已經報名過此課程');
        header('Location: ../index.php');
        exit();
    }
    
    // 檢查團課是否已滿
    if ($schedule['course_type'] === 'group')  {
        $enrollment_query = "SELECT COUNT(*) as count FROM course_enrollments 
                           WHERE schedule_id = ? AND status = 'confirmed'";
        $enrollment_stmt = $mysqli->prepare($enrollment_query);
        $enrollment_stmt->bind_param("i", $schedule_id);
        $enrollment_stmt->execute();
        $enrollment_count = $enrollment_stmt->get_result()->fetch_assoc();
        
        if ($enrollment_count['count'] >= 5) {
            setErrorMessage('此課程已滿班');
            header('Location: ../index.php');
            exit();
        }
    }
    
    // 添加報名記錄
    $enroll_query = "INSERT INTO course_enrollments (schedule_id, user_id, status) VALUES (?, ?, 'confirmed')";
    $enroll_stmt = $mysqli->prepare($enroll_query);
    $enroll_stmt->bind_param("ii", $schedule_id, $user_id);
    
    if ($enroll_stmt->execute()) {
        setSuccessMessage('報名成功！');
    } else {
        setErrorMessage('報名失敗，請稍後重試');
    }
    
    $enroll_stmt->close();
}

header('Location: ../index.php');
exit();
?>