<?php
// 根據妳提供的 InfinityFree 實際截圖，精準配置雲端生產環境（Production）資訊
$host = 'sql305.infinityfree.com'; 
$port = '3306';

// 💡 請注意：後面的 XXX 請換成妳在 "See below for available database names" 看到的實際資料庫名稱
// 例如妳剛剛建的如果是 db，那這裡就要填 'if0_42193535_db'
$db_name = 'if0_42193535_pilates'; 

$username = 'if0_42193535';   // 截圖中的 MYSQL USERNAME

// 💡 密碼請填寫截圖中 MYSQL PASSWORD 點擊眼睛圖示亮出來的那串 Hosting Password 密碼
$password = 'vivian3210'; 

try {
    // 建立帶有 Port 號的雲端 PDO 穩固連線
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4", $username, $password);
    // 開啟錯誤回報模式，方便我們若上架出錯時排查
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // 萬一連線失敗，前台只會噴出這個，保護妳的雲端帳密不外洩
    die("雲端資料庫通道暫時關閉，正在重新對接中..."); 
}