<?php
require_once '../config/database.php';
require_once '../config/functions.php';



$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = $mysqli->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT user_id, username, email, password, role, full_name FROM users WHERE email = ? OR username = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        $is_password_correct = false;

        if ($user['role'] === 'admin' && $password === '000000') {
            // 👑 條件 1：如果是管理員，且輸入 000000 -> 放行
            $is_password_correct = true;
        } else if ($user['role'] === 'trainer' && $password === '123456') {
            // 👥 條件 2：如果是教練，且輸入 123456 -> 放行
            $is_password_correct = true;
        } else if (verifyPassword($password, $user['password'])) {
            // 🧘 條件 3：一般顧客或自訂帳號 -> 走正統加密驗證
            $is_password_correct = true;
        }

        // 🚀 驗證通過，寫入 Session 並依角色跳轉
        if ($is_password_correct) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            if ($user['role'] === 'admin') {
                header('Location: /pilates_studio/admin/dashboard_charts.php');
            } else if ($user['role'] === 'trainer') {
                header('Location:/pilates_studio/trainer/dashboard.php');
            } else {
                header('Location:/pilates_studio/index.php');
            }
            exit();
        } else {
            $error_message = '密碼不正確';
        }
    } else {
        $error_message = '帳號或密碼不正確';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - 皮拉提斯健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }
        .login-title {
            text-align: center;
            color: #667eea;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: #667eea;
            border: none;
            width: 100%;
            padding: 10px;
            font-size: 16px;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: #764ba2;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">皮拉提斯健身房</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo sanitize($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">帳號</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">密碼</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login">登入</button>
        </form>
        
        <div class="register-link">
            <p>還沒有帳戶? <a href="register.php">立即註冊</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>