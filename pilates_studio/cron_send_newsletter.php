<?php
// 🎯 這支檔案是由伺服器排程自動觸發，不用檢查管理員登入
require_once 'config/database.php';
require_once 'config/functions.php';

// 1. 計算「下個月」的時間區間（例如今天是 2026-06，下個月就是 2026-07）
$next_month_start = date('Y-m-01', strtotime('+1 month'));
$next_month_end = date('Y-m-t', strtotime('+1 month'));
$next_month_chinese = date('n', strtotime('+1 month')); // 取得月份數字，例如 "7"

// 2. 撈取下個月的所有精選課表資料
$course_query = "SELECT cs.course_date, cs.start_time, cs.end_time, c.course_name, ct.type_name, u.full_name as trainer_name
                 FROM course_schedules cs
                 JOIN courses c ON cs.course_id = c.course_id
                 JOIN course_types ct ON c.course_type_id = ct.course_type_id
                 LEFT JOIN trainers t ON cs.trainer_id = t.trainer_id
                 LEFT JOIN users u ON t.user_id = u.user_id
                 WHERE cs.course_date BETWEEN ? AND ?
                 ORDER BY cs.course_date ASC, cs.start_time ASC";

$stmt = $mysqli->prepare($course_query);
$stmt->bind_param("ss", $next_month_start, $next_month_end);
$stmt->execute();
$courses_result = $stmt->get_result();

// 3. 把課表組合起成簡約好看的 HTML 表格內容
$course_table_rows = '';
if ($courses_result->num_rows > 0) {
    while ($row = $courses_result->fetch_assoc()) {
        $date = date('m/d', strtotime($row['course_date']));
        $time = substr($row['start_time'], 0, 5) . '-' . substr($row['end_time'], 0, 5);
        $course_table_rows .= "
        <tr>
            <td style='padding: 12px; border-bottom: 1px solid #eeeeee; color: #555555;'>{$date} ({$time})</td>
            <td style='padding: 12px; border-bottom: 1px solid #eeeeee; font-weight: bold; color: #4b6cb7;'>{$row['course_name']}</td>
            <td style='padding: 12px; border-bottom: 1px solid #eeeeee; color: #777777;'>{$row['trainer_name']}</td>
        </tr>";
    }
} else {
    $course_table_rows = "<tr><td colspan='3' style='padding: 20px; text-align: center; color: #999999;'>下月課表正在密集籌備中，敬請期待！</td></tr>";
}
$stmt->close();

// 4. 撈取所有「願意接收推播」的顧客名單 (role = 'customer' 且 is_subscribed = 1)
$customer_query = "SELECT email, full_name FROM users WHERE role = 'customer' AND is_subscribed = 1";
$customers_result = $mysqli->query($customer_query);

if ($customers_result->num_rows > 0) {
    // 5. 巡迴發信給每一位顧客
    while ($customer = $customers_result->fetch_assoc()) {
        $to_email = $customer['email'];
        $to_name = $customer['full_name'];
        
        // 🧘 設計精約色調的 HTML 郵件模板（符合你喜好的淺色、極簡風格）
        $mail_subject = "【皮拉提斯健身房】您的 {$next_month_chinese} 月份全新課程表已送達！";
        
        $mail_body = "
        <div style='font-family: \"Microsoft JhengHei\", sans-serif; background-color: #f4f6f9; padding: 30px; text-align: center;'>
            <div style='max-width: 600px; background-color: #ffffff; margin: 0 auto; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: left;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; color: #ffffff; text-align: center;'>
                    <h2 style='margin: 0; font-size: 24px;'>🧘 核心覺醒・新誌發送</h2>
                    <p style='margin: 5px 0 0 0; opacity: 0.8;'>親愛的 {$to_name}，為您奉上最新的月份課表</p>
                </div>
                
                <div style='padding: 30px;'>
                    <p style='color: #666666; font-size: 16px; line-height: 1.6;'>
                        迎接新的一月，是雕塑體態、找回核心穩定最好的時機。以下是我們為您精心安排的 <b>{$next_month_chinese} 月份精選課程</b>，名額有限，歡迎隨時登入網站預約！
                    </p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                        <thead>
                            <tr style='background-color: #f8f9fa;'>
                                <th style='padding: 12px; text-align: left; color: #667eea; border-bottom: 2px solid #667eea;'>時間</th>
                                <th style='padding: 12px; text-align: left; color: #667eea; border-bottom: 2px solid #667eea;'>課程名稱</th>
                                <th style='padding: 12px; text-align: left; color: #667eea; border-bottom: 2px solid #667eea;'>授課教練</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$course_table_rows}
                        </tbody>
                    </table>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/pilates-studio/index.php' style='background-color: #667eea; color: #ffffff; padding: 12px 35px; text-decoration: none; font-weight: bold; border-radius: 25px; display: inline-block; box-shadow: 0 4px 6px rgba(102,126,234,0.3);'>立刻登入網站預約</a>
                    </div>
                </div>
                
                <div style='background-color: #fafafa; padding: 20px; text-align: center; font-size: 12px; color: #999999; border-top: 1px solid #eeeeee;'>
                    本郵件由系統自動發送，請勿直接回覆。<br>
                    如果您不想再收到每月的課表推播，請至網站會員中心取消訂閱。
                </div>
            </div>
        </div>";

        // 🎯 6. 執行發信（正式上線時，需在此處對接 PHPMailer 套件或 PHP 內建 mail 函數）
        // mail($to_email, $mail_subject, $mail_body, $headers);
        
        echo "✅ 已成功產出發送給學員 [{$to_name}] ({$to_email}) 的通知信。<br>";
    }
} else {
    echo "ℹ️ 目前沒有任何顧客訂閱推播。";
}
?>