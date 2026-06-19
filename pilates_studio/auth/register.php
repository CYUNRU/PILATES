<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $mysqli->real_escape_string($_POST['username']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $mysqli->real_escape_string($_POST['full_name']);
    $phone = $mysqli->real_escape_string($_POST['phone']);
    $role = 'customer'; // 預設為顧客
    
    // 驗證
    if (strlen($username) < 3) {
        $error_message = '用戶名至少需要3個字符';
    } else if (!validateEmail($email)) {
        $error_message = '郵箱格式不正確';
    } else if (strlen($password) < 6) {
        $error_message = '密碼至少需要6個字符';
    } else if ($password !== $confirm_password) {
        $error_message = '兩次輸入的密碼不一致';
    } else {
        // 檢查用戶名和郵箱是否已存在
        $check_query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $mysqli->prepare($check_query);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = '用戶名或郵箱已被註冊';
        } else {
            // 💡 改用 PHP 標準的官方加密函數，跟資料庫與 login 完美通電
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_query = "INSERT INTO users (username, email, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $mysqli->prepare($insert_query);
            $insert_stmt->bind_param("ssssss", $username, $email, $hashed_password, $role, $full_name, $phone);
            
            if ($insert_stmt->execute()) {
                $success_message = '註冊成功! 請 <a href="login.php">登入</a>';
            } else {
                $error_message = '註冊失敗，請稍後重試';
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        .register-title {
            text-align: center;
            color: #667eea;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: #667eea;
            border: none;
            width: 100%;
            padding: 10px;
            font-size: 16px;
            margin-top: 10px;
        }
        .btn-register:hover {
            background: #764ba2;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 class="register-title">建立新帳戶</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo sanitize($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">用戶名</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">郵箱</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="mb-3">
                <label for="full_name" class="form-label">全名</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">電話</label>
                <input type="tel" class="form-control" id="phone" name="phone">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">密碼</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">確認密碼</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-register">註冊</button>
        </form>
        
        <div class="login-link">
            <p>已有帳戶? <a href="login.php">立即登入</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>