<?php
// 智慧檢查：只有當系統目前沒有 Session 時才執行開啟，徹底根除頂部的 Notice 警告
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/functions.php';

// 確保有登入，若無則代入預設測試 ID 1
$user_id = $_SESSION['user_id'] ?? 1;

// 接收前端訊息
$input = json_decode(file_get_contents('php://input'), true);
$user_message = $input['message'] ?? '';

if (empty($user_message)) {
    echo json_encode(['error' => '訊息不能為空']);
    exit();
}

// 👑 AI 人格設定（System Prompt - 線上生成時使用）
$system_instruction = "你是一位在皮拉提斯與多元型態健身房服務的資深溫慢教練。請根據顧客的身體狀況給予專業訓練建議。你可以推薦『器械皮拉提斯』、『墊上皮拉提斯』、『器械禪柔』或『芭蕾瑜珈/Barre』。請務必使用繁體中文（台灣）回答，語氣要充滿鼓勵，且內容控制在120字內。";

// 📡 串接 DuckDuckGo 免費 AI 閘道
$url = 'https://ai.duckduckgo.com/v1/chat';
$payload = [
    'model' => 'meta-llama/Meta-Llama-3-70B-Instruct',
    'messages' => [
        ['role' => 'system', 'content' => $system_instruction],
        ['role' => 'user', 'content' => $user_message]
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json', 'User-Agent: Mozilla/5.0' ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_CONNECTTIMEOUT => 2,
]);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

$response_data = json_decode($response, true);
$ai_reply = $response_data['choices'][0]['message']['content'] ?? '';

// 🎯 初始化預設傳回前端的延伸欄位
$detected_tag = '一般諮詢';
$recommended_course = '';

// 🛡️ 高級專家回應系統（當無法外連大模型，或大模型沒回傳時啟動）
if ($curl_error || empty($response) || empty($ai_reply)) {
    $clean_msg = htmlspecialchars($user_message);
    
    // 🔍 1. 禪柔判斷：關節、卡卡、僵硬、緊繃
    if (mb_strpos($clean_msg, '關節') !== false || mb_strpos($clean_msg, '卡卡') !== false || mb_strpos($clean_msg, '僵硬') !== false || mb_strpos($clean_msg, '緊繃') !== false || mb_strpos($clean_msg, '禪柔') !== false) {
        $detected_tag = '關節僵硬';
        $recommended_course = '器械禪柔';
        $ai_reply = "您好！感覺到身體卡卡或關節僵硬，非常適合風靡歐美舞蹈界的『器械禪柔』課程。禪柔強調脊椎在三維空間中的圓弧螺旋伸展，能像絲綢般溫柔地打開您緊繃的關節活動度，讓身體線條變得無比優雅柔順喔！";
    }
    // 🔍 2. 芭蕾瑜珈判斷：馬甲線、瘦腿、腿、提臀、雕塑、芭蕾
    elseif (mb_strpos($clean_msg, '馬甲線') !== false || mb_strpos($clean_msg, '腿') !== false || mb_strpos($clean_msg, '提臀') !== false || mb_strpos($clean_msg, '雕塑') !== false || mb_strpos($clean_msg, '芭蕾') !== false) {
        $detected_tag = '體態調整';
        $recommended_course = '芭蕾瑜珈';
        $ai_reply = "想要雕塑緊緻小腹與名模般的雙腿曲線嗎？教練強烈推薦我們的『芭蕾瑜珈/Barre』！這門課結合了芭蕾扶把訓練與瑜珈延展，利用高重複次數的低衝擊小幅度動作，能精準燃燒大腿內側與臀部脂肪，練出結實不粗大的修長線條！";
    }
    // 🔍 3. 皮拉提斯脊椎判斷：脊椎、側彎、歪斜
    elseif (mb_strpos($clean_msg, '脊椎') !== false || mb_strpos($clean_msg, '側彎') !== false || mb_strpos($clean_msg, '歪斜') !== false) {
        $detected_tag = '骨盆歪斜';
        $recommended_course = '器械皮拉提斯';
        $ai_reply = "您好！針對脊椎與骨盆排列的情況，教練非常推薦您嘗試我們的『器械皮拉提斯』課程，核心床（Reformer）的穩定滑動軌道能幫助您精準微調身體對稱性，強化深層核心力量。";
    } 
    // 🔍 4. 皮拉提斯肩頸判斷：肩、頸、痠、腰
    elseif (mb_strpos($clean_msg, '肩') !== false || mb_strpos($clean_msg, '頸') !== false || mb_strpos($clean_msg, '痠') !== false || mb_strpos($clean_msg, '腰') !== false) {
        $detected_tag = '肩頸痠痛';
        $recommended_course = '墊上皮拉提斯';
        $ai_reply = "感覺到肌肉痠痛沉重，通常是因為久坐導致上背與核心無力。教練推薦您可以選擇基礎的『墊上皮拉提斯』，我們會專注在呼吸控制與肩胛骨的穩定穩定度練習，幫您釋放斜方肌壓力。";
    } 
    else {
        $detected_tag = '一般諮詢';
        $ai_reply = "謝謝您的分享！不論是想改善腰痠背痛，還是渴望擁有優雅線條，我們提供皮拉提斯、器械禪柔與芭蕾瑜珈等多元核心課程。歡迎到我們的課程區挑選，或直接跟教練預約體驗喔！";
    }
} else {
    // 線上 AI 成功回應時的模糊判斷
    if (mb_strpos($ai_reply, '禪柔') !== false) {
        $recommended_course = '器械禪柔';
    } else if (mb_strpos($ai_reply, '芭蕾') !== false || mb_strpos($ai_reply, 'Barre') !== false) {
        $recommended_course = '芭蕾瑜珈';
    } else if (mb_strpos($ai_reply, '器械') !== false) {
        $recommended_course = '器械皮拉提斯';
    } else if (mb_strpos($ai_reply, '墊上') !== false) {
        $recommended_course = '墊上皮拉提斯';
    }
    
    if (mb_strpos($user_message, '卡') !== false || mb_strpos($user_message, '硬') !== false) {
        $detected_tag = '關節僵硬';
    } else if (mb_strpos($user_message, '痛') !== false || mb_strpos($user_message, '痠') !== false) {
        $detected_tag = '肩頸痠痛';
    } else if (mb_strpos($user_message, '線') !== false || mb_strpos($user_message, '芭蕾') !== false || mb_strpos($user_message, '腿') !== false) {
        $detected_tag = '體態調整';
    }
}

// 💾 寫入 MySQL 數據庫（Chart.js 畫圖原料）
if ($detected_tag !== '一般諮詢' && isset($conn)) {
    $stmt = $conn->prepare("INSERT INTO user_health_logs (user_id, issue_tag) VALUES (?, ?)");
    $stmt->execute([$user_id, $detected_tag]);
}

// 🎯 傳回前端
echo json_encode([
    'reply' => trim($ai_reply),
    'tag' => $detected_tag,
    'course' => $recommended_course
]);