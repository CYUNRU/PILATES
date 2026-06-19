<?php
// 通用函數

// 檢查用戶是否登入
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 檢查用戶權限
function checkRole($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['role'] === $requiredRole;
}

// 多個角色權限檢查
function checkRoles($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    return in_array($_SESSION['role'], $roles);
}

// 重定向到登入頁面
function redirectToLogin() {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// 防止XSS攻擊
function sanitize($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// 驗證郵件格式
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// 密碼加密
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// 驗證密碼
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 格式化貨幣
function formatCurrency($amount) {
    return 'NT$' . number_format($amount, 2, '.', ',');
}

// 格式化日期
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

// 格式化時間
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// 獲取月份的中文名稱
function getMonthName($month) {
    $months = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
    return $months[$month - 1];
}

// 生成隨機字符串
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// 成功消息
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// 錯誤消息
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

// 顯示消息
function displayMessages() {
    $html = '';
    if (isset($_SESSION['success_message'])) {
        $html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        $html .= sanitize($_SESSION['success_message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $html .= sanitize($_SESSION['error_message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['error_message']);
    }
    return $html;
}
?>