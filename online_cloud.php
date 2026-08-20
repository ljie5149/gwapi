<?php
    include_once('common/entry.php');
    global $g_root_url, $g_is_online, $g_online_zhtw, $g_backend_title, $g_supperuser_all;

    // 改用 $_COOKIE 讀取登入資訊
    $username  = isset($_COOKIE['acc_name']) ? rawurldecode($_COOKIE['acc_name']) : "";
    $userrole  = $_COOKIE['user_role'] ?? "";
    $acc_id    = $_COOKIE['acc_id'] ?? "";
    $member_id = $_COOKIE['acc_id'] ?? $acc_id ?? ($acc_id ?: "web");

    // 取得 SSO Token (優先使用 Cookie，若無則呼叫 getGoldenKey())
    $sso_token = $_COOKIE['sso_token'] ?? (function_exists('getGoldenKey') ? getGoldenKey() : "");
    uiLocationPage();
    $cloud_url = "online_cloud.php";
    $org_str = "";
    if ($userrole == "superuser") {
        if ($g_supperuser_all) {
            $org_str = '<span class="separator">|</span>
                        <a href="org_management.php" style="font-weight: bold;">機構管理</a>';
        }
    }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>離線/雲端管理 - <?= $g_backend_title; ?></title>
    <link href="./css/online_cloud.css" rel="stylesheet">
</head>
<body>

    <!-- 頂部導航列 -->
    <header class="navbar">
        <nav class="nav-links">
            <a href="dashboard.php">資料管理</a>
            <span class="separator">|</span>
            <a href="device_list.php">設備序號清單</a>
            <span class="separator">|</span>
            <a href="<?= $cloud_url; ?>" style="font-weight: bold;">離線/雲端管理</a>
            <?= $org_str; ?>
        </nav>
        <div class="user-info">
            <span>登入者：<?php echo htmlspecialchars($username ?: 'admin111'); ?></span>
            <button type="button" id="openLogoutModal" class="logout-btn">登出</button>
        </div>
    </header>

    <main>
        <h1 class="page-title">離線/雲端管理</h1>

        <!-- 伺服器與連線設定區塊 (灰色背景) -->
        <section class="config-card">
            <div class="section-subtitle">伺服器與連線設定</div>
            
            <div class="form-row">
                <span class="form-label">主機模式</span>
                <div class="select-wrapper">
                    <select id="hostModeSelect">
                        <option value="OFF-LINE" selected>離線主機</option>
                        <option value="ON-LINE">雲端主機</option>
                    </select>
                </div>
            </div>

            <!-- 主機 Domain -->
            <div class="form-row">
                <span class="form-label">主機 Domain</span>
                <span class="form-value" id="val_domain">載入中...</span>
                <button class="btn-edit" onclick="openEditModal('domain')">編輯</button>
            </div>

            <!-- 雲端主機連線 (離線模式專屬) -->
            <div class="form-row offline-only">
                <span class="form-label">雲端主機連線</span>
                <span class="form-value" id="val_cloud">載入中...</span>
                <button class="btn-edit" onclick="openEditModal('cloud')">編輯</button>
            </div>

            <!-- HMS主機連線 -->
            <div class="form-row" style="margin-bottom: 0;">
                <span class="form-label">HMS主機連線</span>
                <span class="form-value" id="val_hms">載入中...</span>
                <button class="btn-edit" onclick="openEditModal('hms')" id="btn_edit_hms">編輯</button>
            </div>
        </section>

        <!-- 狀態與同步區塊 (白色背景) -->
        <section class="status-card">
            <div class="section-subtitle">狀態與同步</div>

            <!-- 雲端主機連線狀態 (離線模式專屬) -->
            <div class="status-row offline-only">
                <span class="status-label">雲端主機連線狀態</span>
                <div class="status-badge offline" id="status_cloud_badge">
                    <span class="status-checkbox" id="status_cloud_icon"></span>
                    <span id="status_cloud_text">已連線</span>
                </div>
                <button class="btn-check-status" onclick="checkSingleStatus('cloud')">檢查連線狀態</button>
            </div>

            <div class="status-row">
                <span class="status-label">HMS主機連線狀態</span>
                <div class="status-badge offline" id="status_hms_badge">
                    <span class="status-checkbox" id="status_hms_icon"></span>
                    <span id="status_hms_text">已連線</span>
                </div>
                <button class="btn-check-status" onclick="checkSingleStatus('hms')">檢查連線狀態</button>
            </div>

            <!-- 資料同步與同步按鈕 (離線模式專屬) -->
            <div class="status-row offline-only" style="margin-bottom: 0;">
                <span class="status-label">資料同步</span>
                <span class="sync-time-text">上次同步時間：2026-08-19</span>
                <button class="btn-sync">同步</button>
            </div>
        </section>
    </main>

    <!-- 編輯 Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-container">
            <div class="modal-close" onclick="closeModal('editModal')">X</div>
            <h2 class="modal-title" id="editModalTitle">編輯網址</h2>
            <form id="editForm">
                <input type="hidden" id="editTargetSid">
                <div class="modal-form-group">
                    <label id="editModalLabel">連線網址 (URL)</label>
                    <input type="text" id="editConfigValue" placeholder="https://..." required>
                </div>
                <div class="modal-btn-group" style="margin-top: 30px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('editModal')">取消</button>
                    <button type="submit" class="btn-modal-submit">儲存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 登出 Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-container">
            <div class="modal-close" onclick="closeModal('logoutModal')">X</div>
            <h2 class="modal-title">系統登出</h2>
            <p class="modal-desc">確定要登出目前帳號嗎？</p>
            <div class="modal-btn-group">
                <button class="btn-modal-cancel" onclick="closeModal('logoutModal')">取消</button>
                <a href="logout.php" class="btn-modal-danger">確定登出</a>
            </div>
        </div>
    </div>

    <script>
        const CFG_API_URL = '<?= $g_root_url ?>api/JTG_hostconfig.php';
        const MEMBER_ID            = '<?= htmlspecialchars($member_id); ?>';
        const SSO_TOKEN            = '<?= htmlspecialchars($sso_token); ?>';
        
        const hostModeSelect = document.getElementById('hostModeSelect');
        const offlineElements = document.querySelectorAll('.offline-only');

        // 快取 API 回傳的設定資料
        let hostConfigData = [];

        // 1. 從 API 載入資料
        async function fetchHostConfig() {
            try {
                const params = new URLSearchParams({ sso_token: SSO_TOKEN, get_all: '0' });
                
                const response = await fetch(`${CFG_API_URL}?${params.toString()}`);
                const result = await response.json();
                if (result.status === "true" && result.data && result.data.data) {
                    hostConfigData = result.data.data;
                    renderConfigValues();
                } else {
                    console.error("無法取得設定資料：", result.responseMessage);
                }
            } catch (error) {
                console.error("API 請求失敗：", error);
            }
        }

        // 2. 根據目前選擇的模式渲染網址內容，並觸發連線檢測
        function renderConfigValues() {
            const currentMode = hostModeSelect.value; // 'OFF-LINE' 或 'ON-LINE'
            
            // 更新 UI 隱藏/顯示離線專屬欄位
            const isOffline = (currentMode === 'OFF-LINE');
            offlineElements.forEach(el => {
                el.style.display = isOffline ? 'flex' : 'none';
            });

            if (isOffline) {
                // 離線模式
                const domainItem = hostConfigData.find(item => item.sid === 'OFF_DOMAIN_URL');
                const cloudItem  = hostConfigData.find(item => item.sid === 'OFF_Cloud_URL');
                const hmsItem    = hostConfigData.find(item => item.sid === 'OFF_HMS_URL');

                document.getElementById('val_domain').innerText = domainItem ? domainItem.config_value : '-';
                document.getElementById('val_cloud').innerText  = cloudItem ? cloudItem.config_value : '-';
                document.getElementById('val_hms').innerText    = hmsItem ? hmsItem.config_value : '-';
                document.getElementById('btn_edit_hms').style.display = 'inline-block';
            } else {
                // 雲端模式
                const domainItem = hostConfigData.find(item => item.sid === 'DOMAIN_URL');
                const cloudItem  = hostConfigData.find(item => item.sid === 'Cloud_URL');

                document.getElementById('val_domain').innerText = domainItem ? domainItem.config_value : '-';
                document.getElementById('val_hms').innerText    = cloudItem ? cloudItem.config_value : '-';
                document.getElementById('btn_edit_hms').style.display = 'inline-block';
            }

            // 選單切換後自動觸發連線測試
            checkAllConnectionStatus();
        }

        // 3. 通用網路連線測試 Ping/Fetch 函數
        async function pingUrl(url, timeout = 3000) {
            if (!url || url === '-' || url.trim() === '') return false;

            let targetUrl = url.trim();
            if (!/^https?:\/\//i.test(targetUrl)) {
                targetUrl = 'https://' + targetUrl;
            }

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);

            try {
                await fetch(targetUrl, {
                    method: 'GET',
                    mode: 'no-cors',
                    cache: 'no-cache',
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                return true;
            } catch (err) {
                clearTimeout(timeoutId);
                return false;
            }
        }

        // 4. 更新 UI 狀態顯示 (控制綠色連線/灰色離線)
        function updateStatusUI(type, isConnected, isChecking = false) {
            const badge = document.getElementById(`status_${type}_badge`);
            const icon = document.getElementById(`status_${type}_icon`);
            const text = document.getElementById(`status_${type}_text`);

            if (!badge || !icon || !text) return;

            if (isChecking) {
                badge.className = 'status-badge checking';
                icon.innerText = '⋯';
                text.innerText = '已連線';
            } else if (isConnected) {
                // 連線成功：綠色 + 勾選
                badge.className = 'status-badge online';
                icon.innerText = '✓';
                text.innerText = '已連線';
            } else {
                // 連線失敗/離線：灰色 + 取消勾選 (無勾號)
                badge.className = 'status-badge offline';
                icon.innerText = '';
                text.innerText = '已連線';
            }
        }

        // 5. 檢測所有適用連線狀態
        async function checkAllConnectionStatus() {
            const currentMode = hostModeSelect.value;
            
            if (currentMode === 'OFF-LINE') {
                const cloudItem = hostConfigData.find(item => item.sid === 'OFF_Cloud_URL');
                const hmsItem   = hostConfigData.find(item => item.sid === 'OFF_HMS_URL');

                checkSingleStatusByUrl('cloud', cloudItem ? cloudItem.config_value : '');
                checkSingleStatusByUrl('hms', hmsItem ? hmsItem.config_value : '');
            } else {
                // 雲端模式
                const hmsItem = hostConfigData.find(item => item.sid === 'Cloud_URL');
                checkSingleStatusByUrl('hms', hmsItem ? hmsItem.config_value : '');
            }
        }

        // 根據指定的類型與 URL 進行連線測試
        async function checkSingleStatusByUrl(type, url) {
            updateStatusUI(type, false, true); // 顯示檢測中
            const isOnline = await pingUrl(url);
            updateStatusUI(type, isOnline, false);
        }

        // 個別按鈕點擊檢測函數
        function checkSingleStatus(type) {
            const currentMode = hostModeSelect.value;
            let targetSid = '';

            if (type === 'cloud') {
                targetSid = 'OFF_Cloud_URL';
            } else if (type === 'hms') {
                targetSid = (currentMode === 'OFF-LINE') ? 'OFF_HMS_URL' : 'Cloud_URL';
            }

            const item = hostConfigData.find(i => i.sid === targetSid);
            checkSingleStatusByUrl(type, item ? item.config_value : '');
        }

        // 6. 開啟編輯彈窗
        function openEditModal(type) {
            const currentMode = hostModeSelect.value;
            let targetSid = '';
            let title = '';

            if (type === 'domain') {
                targetSid = (currentMode === 'OFF-LINE') ? 'OFF_DOMAIN_URL' : 'DOMAIN_URL';
                title = '編輯 主機 Domain';
            } else if (type === 'cloud') {
                targetSid = 'OFF_Cloud_URL';
                title = '編輯 雲端主機連線';
            } else if (type === 'hms') {
                targetSid = (currentMode === 'OFF-LINE') ? 'OFF_HMS_URL' : 'Cloud_URL';
                title = (currentMode === 'OFF-LINE') ? '編輯 HMS主機連線' : '編輯 HMS主機連線';
            }

            const currentItem = hostConfigData.find(item => item.sid === targetSid);
            
            document.getElementById('editModalTitle').innerText = title;
            document.getElementById('editTargetSid').value = targetSid;
            document.getElementById('editConfigValue').value = currentItem ? currentItem.config_value : '';
            openModal('editModal');
        }

        // 7. 儲存編輯資料 (送出 PATCH 請求至 API)
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const sid = document.getElementById('editTargetSid').value;
            const newValue = document.getElementById('editConfigValue').value.trim();

            try {
                const response = await fetch(CFG_API_URL, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${SSO_TOKEN}`
                    },
                    body: JSON.stringify({
                        sid: sid,
                        config_value: newValue
                    })
                });

                const result = await response.json();
                if (result.status === "true") {
                    closeModal('editModal');
                    fetchHostConfig(); // 重新整理資料並重新自動檢測
                } else {
                    alert('更新失敗：' + result.responseMessage);
                }
            } catch (error) {
                console.error('更新 API 錯誤：', error);
                alert('系統連線異常，更新失敗');
            }
        });

        // 下拉選單切換監聽 (自動觸發更新與檢測)
        hostModeSelect.addEventListener('change', renderConfigValues);

        // Modal 控制函式
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        document.getElementById('openLogoutModal').addEventListener('click', function() {
            openModal('logoutModal');
        });

        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        });

        // 頁面初始化
        fetchHostConfig();
    </script>
</body>
</html>