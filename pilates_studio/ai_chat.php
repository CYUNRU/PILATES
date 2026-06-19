<?php
// 智慧檢查：只有當系統目前沒有 Session 時才執行開啟，徹底根除頂部的 Notice 警告
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'config/functions.php';

// 安全防禦調整：若本機 session 跑丟，自動補上臨時 ID
$user_id = $_SESSION['user_id'] ?? 1;

// 📊 撈取包含新標籤「關節僵硬」在內的歷史痛點數據
$initial_data = ['肩頸痠痛' => 0, '骨盆歪斜' => 0, '體態調整' => 0, '關節僵硬' => 0];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT issue_tag, COUNT(*) as count FROM user_health_logs WHERE user_id = ? GROUP BY issue_tag");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        if (array_key_exists($row['issue_tag'], $initial_data)) {
            $initial_data[$row['issue_tag']] = (int)$row['count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 智能全方位體能分析系統 - 美學健康健身房</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Microsoft JhengHei', sans-serif; background-color: #f4f6f9; color: #333; }
        .navbar-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .chat-container { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; height: 600px; display: flex; flex-direction: column; }
        .chat-body { flex-grow: 1; padding: 20px; overflow-y: auto; background-color: #fafbfc; }
        .msg-box { max-width: 85%; padding: 12px 16px; border-radius: 12px; margin-bottom: 15px; font-size: 15px; line-height: 1.5; display: flex; flex-direction: column; }
        .msg-user { background-color: #667eea; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .msg-ai { background-color: #eef2f7; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; }
        .typing-indicator { color: #999; font-size: 13px; font-style: italic; display: none; }
        .dashboard-container { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 20px; height: 600px; }
        .btn-booking { background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; margin-top: 8px; font-weight: bold; transition: 0.2s; align-self: flex-start; text-decoration: none; display: inline-block; }
        .btn-booking:hover { transform: translateY(-2px); color: white; box-shadow: 0 4px 10px rgba(118,75,162,0.3); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-heart text-warning"></i> 皮拉提斯 ✕ 禪柔 ✕ 芭蕾美學管理系統</a>
            <a href="index.php" class="btn btn-sm btn-outline-light">返回首頁</a>
        </div>
    </nav>

    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="chat-container">
                    <div class="p-3 bg-white border-bottom d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center text-white rounded-circle" style="width: 45px; height: 45px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 20px;">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">AI 美學體態智能顧問</h5>
                            <small class="text-success"><i class="fas fa-circle fs-6"></i> 多元運動方案配置中</small>
                        </div>
                    </div>

                    <div class="chat-body d-flex flex-column" id="chatBody">
                        <div class="msg-box msg-ai">
                            <div>👋 您好！我是您的美學體態智能顧問。我們提供**專業皮拉提斯、器械禪柔、以及芭蕾瑜珈/Barre**課程。請跟我分享您的體態願景（如：想練名模馬甲線、美化雙腿、改善駝背）或身體不適（如關節僵硬卡卡、腰痠背痛）？我會為您量身打造黃金客製課表！</div>
                        </div>
                        <div class="typing-indicator" id="typing">AI 教練正在分析您的體態潛能...</div>
                    </div>

                    <div class="p-3 bg-white border-top">
                        <div class="input-group">
                            <input type="text" id="userInput" class="form-control border-secondary-subtle" placeholder="例如：我想瘦大腿，或是最近覺得關節卡僵硬..." aria-label="訊息內容">
                            <button class="btn btn-primary px-4" type="button" id="sendBtn" style="background-color: #667eea; border-color: #667eea;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="dashboard-container d-flex flex-column align-items-center justify-content-center">
                    <h5 class="fw-bold mb-3 text-center"><i class="fas fa-chart-pie text-success"></i> 您的核心健康與體態痛點指標</h5>
                    <p class="text-muted small text-center mb-4">AI 會實時分析您的對話關鍵字，將不適與目標分類歸檔至個人雲端資料庫。</p>
                    <div style="position: relative; width:85%; height:320px;">
                        <canvas id="healthChart"></canvas>
                    </div>
                    <div id="chartStatus" class="mt-4 text-secondary small text-center fw-bold"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chatBody = document.getElementById('chatBody');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const typing = document.getElementById('typing');

        // 📊 初始化四色 Chart.js 圓餅圖 (擴充：關節僵硬選項)
        const ctx = document.getElementById('healthChart').getContext('2d');
        const initialCounts = <?php echo json_encode(array_values($initial_data)); ?>;
        
        const healthChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['肩頸痠痛', '骨盆歪斜', '體態調整', '關節僵硬'],
                datasets: [{
                    data: initialCounts,
                    backgroundColor: ['#ff6384', '#ffcd56', '#4bc0c0', '#9966ff'], // 💜 新增紫色代表關節僵硬
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        function updateChartStatus() {
            const total = healthChart.data.datasets[0].data.reduce((a, b) => a + b, 0);
            document.getElementById('chartStatus').innerText = total > 0 ? `📊 智慧資料庫目前已累計紀錄： ${total} 筆健康體態指標` : "💡 聊天室靜候中，快輸入妳的體態困擾或運動目標吧！";
        }
        updateChartStatus();

        // 📥 前端繪製對話泡泡（一鍵預約支援禪柔與芭蕾）
        function appendMessage(text, isUser, recommendedCourse = '') {
            const msg = document.createElement('div');
            msg.className = `msg-box ${isUser ? 'msg-user' : 'msg-ai'}`;
            
            const textDiv = document.createElement('div');
            textDiv.innerText = text;
            msg.appendChild(textDiv);
            
            if (!isUser && recommendedCourse) {
                const bookingBtn = document.createElement('a');
                bookingBtn.href = `booking.php?course=${encodeURIComponent(recommendedCourse)}`;
                bookingBtn.className = 'btn-booking';
                bookingBtn.innerHTML = `<i class="fas fa-star text-warning"></i> 點我一鍵預約課程：『${recommendedCourse}』`;
                msg.appendChild(bookingBtn);
            }
            
            chatBody.insertBefore(msg, typing);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function sendMessage() {
            const text = userInput.value.trim();
            if (!text) {
                alert("請輸入訊息！");
                return;
            }

            appendMessage(text, true); 
            userInput.value = '';
            typing.style.display = 'block'; 
            chatBody.scrollTop = chatBody.scrollHeight;

            const secureUrl = 'api/ai_helper.php?t=' + new Date().getTime() + '&r=' + Math.random();

            fetch(secureUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP 錯誤');
                return res.text();
            })
            .then(textData => {
                typing.style.display = 'none';
                try {
                    const jsonStartIndex = textData.lastIndexOf('{');
                    if (jsonStartIndex !== -1) {
                        const data = JSON.parse(textData.substring(jsonStartIndex));
                        
                        appendMessage(data.reply, false, data.course);
                        
                        if (data.tag && data.tag !== '一般諮詢') {
                            const labelIndex = healthChart.data.labels.indexOf(data.tag);
                            if (labelIndex !== -1) {
                                healthChart.data.datasets[0].data[labelIndex] += 1;
                                healthChart.update();
                                updateChartStatus();
                            }
                        }
                    }
                } catch (e) {
                    appendMessage(textData, false);
                }
            })
            .catch(err => {
                typing.style.display = 'none';
                console.error(err);
                appendMessage('🧘 （智慧離線顧問）收到您的訊息了！建議您可以嘗試我們的精選核心美學課程，有效釋放身體緊繃壓力並完美雕塑曲線喔！', false, '器械禪柔');
            });
        }

        sendBtn.onclick = sendMessage;
        userInput.onkeypress = function(e) {
            if (e.key === 'Enter') sendMessage();
        };
    </script>
</body>
</html>