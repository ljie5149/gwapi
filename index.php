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
    $cloud_url = ($g_is_online) ? "online_cloud.php" : "offline_cloud.php";
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
    <title>資料管理 - <?= htmlspecialchars($g_backend_title); ?></title>
    <link href="./css/dashboard.css" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="./css/flatpickr.min.css">
</head>
<body>

    <!-- 頂部導航列 -->
    <header class="navbar">
        <nav class="nav-links"><span><?= htmlspecialchars($g_online_zhtw) ?></span>
            <a href="dashboard.php" style="font-weight: bold;">資料管理</a>
            <span class="separator">|</span>
            <a href="device_list.php">設備序號清單</a>
            <span class="separator">|</span>
            <a href="<?= htmlspecialchars($cloud_url); ?>">離線/雲端管理</a>
            <?= $org_str; ?>
        </nav>
        <div class="user-info">
            <span>登入者：<?php echo htmlspecialchars($username); ?></span>
            <!-- 觸發登出 Modal 對話框 -->
            <button type="button" id="openLogoutModal" class="logout-btn">登出</button>
        </div>
    </header>

    <main class="main-container">
        <h1 class="page-title">資料管理</h1>

        <!-- 頁籤按鈕 -->
        <div class="tabs">
            <button class="tab-btn active" id="btnRaw">原始資料</button>
            <button class="tab-btn inactive" id="btnCommon">共通格式</button>
        </div>

        <!-- 篩選列 -->
        <div class="filter-bar">
            <span class="filter-label">日期範圍</span>
            <div class="date-input-container">
                <input type="text" id="dateRangeInput" placeholder="請選擇起迄日期" readonly>
                <span>📅</span>
            </div>

            <span class="filter-label" style="margin-left: 15px;">量測設備</span>
            <div class="select-container">
                <!-- 量測設備動態選單 (由 JTG_devselection API 載入) -->
                <select id="deviceSelect">
                    <option value="">載入中...</option>
                </select>
            </div>

            <button class="action-btn btn-download" id="btnDownload">下載</button>
            <button class="action-btn btn-delete" id="btnDelete">刪除</button>
        </div>

        <!-- 通用搜尋列 -->
        <div class="search-bar" id="searchRow" style="display: flex;">
            <span class="filter-label">搜尋</span>
            <input type="text" id="searchInput" class="search-input" placeholder="請輸入流水號 (sid)、身分證號、姓名或檔案名稱關鍵字...">
        </div>

        <!-- 資料表格容器 -->
        <div class="table-card">
            <!-- 1. 原始資料表格 -->
            <div id="rawTable">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="checkbox-col"><input type="checkbox" class="select-all"></th>
                            <th>檔案日期</th>
                            <th>量測設備</th>
                            <th>檔案名稱</th>
                            <th>檔案大小</th>
                        </tr>
                    </thead>
                    <tbody id="rawTableBody">
                        <tr><td colspan="5" class="no-data">資料載入中...</td></tr>
                    </tbody>
                </table>
                <div class="info-text">
                    檔案與資料庫命名：原始資料 (Raw data) 命名格式為 日期 + 流水號 + 型號 + 序號
                </div>
            </div>

            <!-- 2. 共通格式表格 -->
            <div id="commonTable" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="checkbox-col"><input type="checkbox" class="select-all"></th>
                            <th>身分證號</th>
                            <th>姓名</th>
                            <th>工號</th>
                            <th>流水號</th>
                            <th>次數</th>
                            <th>收縮壓</th>
                            <th>舒張壓</th>
                            <th>量測時間</th>
                        </tr>
                    </thead>
                    <tbody id="commonTableBody">
                        <tr><td colspan="9" class="no-data">資料載入中...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- 分頁區塊 -->
    <footer class="pagination">
        <span class="page-link disabled">&larr; Previous</span>
        <a href="#" class="page-link active">1</a>
        <a href="#" class="page-link">Next &rarr;</a>
    </footer>

    <!-- 下載確認 Modal 對話方塊 -->
    <div class="modal-overlay" id="downloadModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-close" id="closeDownloadModal">X</div>
            <h2 class="modal-title">資料匯出與下載</h2>
            <p class="modal-desc" id="downloadModalDesc">請確認下載資料筆數與格式。</p>
            
            <div class="modal-option-group">
                <span>選擇匯出格式：</span>
                <label><input type="radio" name="exportFormat" value="csv" checked> CSV 格式</label>
                <label><input type="radio" name="exportFormat" value="json"> JSON 格式</label>
            </div>

            <div class="modal-btn-group">
                <button type="button" class="btn-modal-cancel" id="cancelDownloadBtn">取消</button>
                <button type="button" class="btn-modal-confirm-download" id="confirmDownloadBtn">確定下載</button>
            </div>
        </div>
    </div>

    <!-- 刪除確認 Modal 對話方塊 -->
    <div class="modal-overlay" id="deleteModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-close" id="closeDeleteModal">X</div>
            <h2 class="modal-title">資料刪除確認</h2>
            <p class="modal-desc" id="deleteModalDesc">確定要刪除選取的資料嗎？</p>
            <div class="modal-btn-group">
                <button type="button" class="btn-modal-cancel" id="cancelDeleteBtn">取消</button>
                <button type="button" class="btn-modal-confirm-delete" id="confirmDeleteBtn">確定刪除</button>
            </div>
        </div>
    </div>

    <!-- 登出確認 Modal 對話方塊 -->
    <div class="modal-overlay" id="logoutModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-close" id="closeLogoutModal">X</div>
            <h2 class="modal-title">系統登出</h2>
            <p class="modal-desc">確定要登出目前帳號嗎？</p>
            <div class="modal-btn-group">
                <button type="button" class="btn-modal-cancel" id="cancelLogoutBtn">取消</button>
                <a href="logout.php" class="btn-modal-confirm-logout">確定登出</a>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS 與 繁體中文語系包 -->
    <script src="./js/flatpickr.js"></script>
    <script src="./js/flatpickr.zh-tw.js"></script>
    
    <script>
        const DEV_API_URL = '<?= $g_root_url ?>api/JTG_devselection.php';
        const MEASURE_API_URL = '<?= $g_root_url ?>api/JTG_measure.php';
        const SSO_TOKEN = '<?= htmlspecialchars($sso_token); ?>';

        let currentMeasureData = []; // 快取從 JTG_measure API 撈出的原始資料
        let debounceTimer = null;
        let pendingDeleteBoxes = []; // 快取欲刪除的 Checkbox 節點
        let pendingDownloadData = []; // 快取欲下載的資料陣列

        const btnRaw = document.getElementById('btnRaw');
        const btnCommon = document.getElementById('btnCommon');
        const rawTable = document.getElementById('rawTable');
        const commonTable = document.getElementById('commonTable');
        const searchInput = document.getElementById('searchInput');

        // HTML 轉義防護 (XSS)
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        // 格式化檔案大小
        function formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // =========================================================
        // 1. 動態載入量測設備選單 (JTG_devselection API)
        // =========================================================
        async function loadDeviceOptions() {
            const selectElem = document.getElementById('deviceSelect');
            try {
                const params = new URLSearchParams();
                params.append('sso_token', SSO_TOKEN);
                params.append('get_all', '1');

                const response = await fetch(`${DEV_API_URL}?${params.toString()}`, { method: 'GET' });
                const res = await response.json();

                // console.log(response);
                let deviceList = [];
                if (res.status === 'true' && res.data && Array.isArray(res.data)) {
                    deviceList = res.data;
                } else if (res.status === 'true' && Array.isArray(res.data)) {
                    deviceList = res.data;
                }

                let optionsHtml = '<option value="">全部設備</option>';
                if (deviceList.length > 0) {
                    optionsHtml += deviceList.map(dev => `
                        <option value="${escapeHtml(dev.device_type)}">
                            ${escapeHtml(dev.device_name)}
                        </option>
                    `).join('');
                }
                selectElem.innerHTML = optionsHtml;
            } catch (err) {
                console.error('載入量測設備選單失敗:', err);
                selectElem.innerHTML = '<option value="">載入失敗</option>';
            }
        }

        // =========================================================
        // 2. 取得健檢量測資料 (JTG_measure API - GET 帶入搜尋條件)
        // =========================================================
        async function fetchMeasureData() {
            const rawTbody = document.getElementById('rawTableBody');
            const commonTbody = document.getElementById('commonTableBody');
            rawTbody.innerHTML = '<tr><td colspan="5" class="no-data">資料載入中...</td></tr>';
            commonTbody.innerHTML = '<tr><td colspan="9" class="no-data">資料載入中...</td></tr>';

            // 解析 dateRangeInput 中的起迄日期 (以 " to " 或 "~" 作為分隔符號)
            const dateRangeVal = document.getElementById('dateRangeInput').value.trim();
            let startDate = '';
            let endDate = '';

            if (dateRangeVal) {
                // 相容 "YYYY-MM-DD to YYYY-MM-DD" 或 "YYYY-MM-DD ~ YYYY-MM-DD" 等常見格式
                const splitDates = dateRangeVal.split(/\s*(?:to|~|\s)\s*/i);
                startDate = splitDates[0] || '';
                endDate = splitDates[1] || splitDates[0] || ''; // 若只選一天則起迄相同
            }

            const deviceType = document.getElementById('deviceSelect').value;
            const searchKeyword = searchInput.value.trim();

            try {
                const params = new URLSearchParams();
                params.append('sso_token', SSO_TOKEN);
                if (startDate) params.append('start_date', startDate + ' 00:00:00');
                if (endDate) params.append('end_date', endDate + ' 23:59:59');
                if (deviceType) params.append('machine_model', deviceType);

                if (searchKeyword) {
                    params.append('sid', searchKeyword);
                }

                const response = await fetch(`${MEASURE_API_URL}?${params.toString()}`, { method: 'GET' });
                const res = await response.json();

                if (res.status === 'true' && res.data && Array.isArray(res.data.data)) {
                    currentMeasureData = res.data.data;
                } else {
                    currentMeasureData = [];
                }

                renderTables();
            } catch (err) {
                console.error('取得量測資料失敗:', err);
                rawTbody.innerHTML = '<tr><td colspan="5" class="no-data">載入失敗，請重試</td></tr>';
                commonTbody.innerHTML = '<tr><td colspan="9" class="no-data">載入失敗，請重試</td></tr>';
            }
        }

        // 重新繪製所有表格
        function renderTables() {
            renderRawTable(currentMeasureData);
            renderCommonTable(currentMeasureData);
        }

        // =========================================================
        // 3. 渲染原始資料表格 (Raw Table)
        // =========================================================
        function renderRawTable(dataList) {
            const rawTbody = document.getElementById('rawTableBody');
            const keyword = searchInput.value.trim().toLowerCase();

            if (!dataList || dataList.length === 0) {
                rawTbody.innerHTML = '<tr><td colspan="5" class="no-data">查無原始資料</td></tr>';
                return;
            }

            let html = '';
            let matchCount = 0;

            dataList.forEach(row => {
                const dateOnly = row.measure_date ? row.measure_date.split(' ')[0] : '-';
                const deviceName = row.machine_model || row.device_no || '-';
                const fileName = row.file_name || (row.sid ? `${row.sid}.txt` : '-');
                const fileSize = row.file_size ? formatFileSize(row.file_size) : '-';

                if (keyword) {
                    const matchText = `${deviceName} ${fileName} ${row.sid || ''}`.toLowerCase();
                    if (!matchText.includes(keyword)) return;
                }

                matchCount++;
                html += `
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" class="row-checkbox" value="${escapeHtml(row.id)}"></td>
                        <td>${escapeHtml(dateOnly)}</td>
                        <td>${escapeHtml(deviceName)}</td>
                        <td>${escapeHtml(fileName)}</td>
                        <td>${escapeHtml(fileSize)}</td>
                    </tr>
                `;
            });

            if (matchCount === 0) {
                rawTbody.innerHTML = '<tr><td colspan="5" class="no-data">查無符合搜尋條件的資料</td></tr>';
            } else {
                rawTbody.innerHTML = html;
            }
        }

        // =========================================================
        // 4. 渲染共通格式表格 (Common Table)
        // =========================================================
        function renderCommonTable(dataList) {
            const commonTbody = document.getElementById('commonTableBody');
            const keyword = searchInput.value.trim().toLowerCase();

            if (!dataList || dataList.length === 0) {
                commonTbody.innerHTML = '<tr><td colspan="9" class="no-data">查無共通格式資料</td></tr>';
                return;
            }

            let html = '';
            let matchCount = 0;

            dataList.forEach(row => {
                let parsedObj = {};
                try {
                    if (row.up_json_data) {
                        parsedObj = typeof row.up_json_data === 'string' ? JSON.parse(row.up_json_data) : row.up_json_data;
                    } else if (row.json_data) {
                        parsedObj = typeof row.json_data === 'string' ? JSON.parse(row.json_data) : row.json_data;
                    }
                } catch (e) {
                    parsedObj = {};
                }

                const idCard = parsedObj.id_card || parsedObj.person_id || '-';
                const name = parsedObj.name || parsedObj.user_name || '-';
                const empId = parsedObj.emp_id || parsedObj.staff_id || '-';
                const sid = row.sid || row.measure_no || '-';
                const count = parsedObj.count || parsedObj.times || '1';
                const sys = parsedObj.sys || parsedObj.systolic || '-';
                const dia = parsedObj.dia || parsedObj.diastolic || '-';
                const measureTime = row.measure_date || '-';

                if (keyword) {
                    const matchText = `${idCard} ${name} ${empId} ${sid}`.toLowerCase();
                    if (!matchText.includes(keyword)) return;
                }

                matchCount++;
                html += `
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" class="row-checkbox" value="${escapeHtml(row.id)}"></td>
                        <td>${escapeHtml(idCard)}</td>
                        <td>${escapeHtml(name)}</td>
                        <td>${escapeHtml(empId)}</td>
                        <td>${escapeHtml(sid)}</td>
                        <td>${escapeHtml(count)}</td>
                        <td>${escapeHtml(sys)}</td>
                        <td>${escapeHtml(dia)}</td>
                        <td>${escapeHtml(measureTime)}</td>
                    </tr>
                `;
            });

            if (matchCount === 0) {
                commonTbody.innerHTML = '<tr><td colspan="9" class="no-data">查無符合搜尋條件的資料</td></tr>';
            } else {
                commonTbody.innerHTML = html;
            }
        }

        // =========================================================
        // 5. 執行檔案下載匯出邏輯 (CSV / JSON)
        // =========================================================
        function executeDownload(dataList, format) {
            if (!dataList || dataList.length === 0) {
                alert('無可匯出的資料！');
                return;
            }

            const fileName = `export_measure_${new Date().toISOString().slice(0,10)}.${format}`;

            if (format === 'json') {
                const jsonStr = JSON.stringify(dataList, null, 2);
                const blob = new Blob([jsonStr], { type: 'application/json;charset=utf-8;' });
                triggerFileDownload(blob, fileName);
            } else {
                // CSV 格式匯出 (含 BOM 防亂碼)
                let csvContent = "\uFEFF";
                const headers = ["ID", "流水號(SID)", "設備型號", "量測時間", "原始JSON內容"];
                csvContent += headers.join(",") + "\n";

                dataList.forEach(item => {
                    const row = [
                        `"${item.id || ''}"`,
                        `"${item.sid || ''}"`,
                        `"${item.machine_model || ''}"`,
                        `"${item.measure_date || ''}"`,
                        `"${(item.up_json_data || item.json_data || '').replace(/"/g, '""')}"`
                    ];
                    csvContent += row.join(",") + "\n";
                });

                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                triggerFileDownload(blob, fileName);
            }
        }

        function triggerFileDownload(blob, fileName) {
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', fileName);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // =========================================================
        // 6. 批次刪除執行邏輯 (JTG_measure API - DELETE)
        // =========================================================
        async function executeDelete(selectedBoxes) {
            let successCount = 0;
            let failCount = 0;

            for (const box of selectedBoxes) {
                const id = box.value;
                try {
                    const response = await fetch(MEASURE_API_URL, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            sso_token: SSO_TOKEN,
                            id: parseInt(id),
                            who_call: 'dashboard'
                        })
                    });
                    const res = await response.json();
                    if (res.status === 'true') {
                        successCount++;
                    } else {
                        failCount++;
                    }
                } catch (err) {
                    console.error(`刪除 ID ${id} 失敗:`, err);
                    failCount++;
                }
            }

            alert(`刪除完成！成功：${successCount} 筆，失敗：${failCount} 筆`);
            fetchMeasureData();
        }


        // =========================================================
        // 5. 初始化 Flatpickr 日期選擇器
        // =========================================================
        function initDateRangePicker() {
            flatpickr("#dateRangeInput", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "zh_tw",
                locale: { rangeSeparator: " - " },
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        fetchDeviceList();
                    }
                }
            });
        }

        // =========================================================
        // 事件監聽與切換
        // =========================================================
        
        // 頁籤切換：原始資料
        btnRaw.addEventListener('click', function() {
            btnRaw.className = 'tab-btn active';
            btnCommon.className = 'tab-btn inactive';
            rawTable.style.display = 'block';
            commonTable.style.display = 'none';
        });

        // 頁籤切換：共通格式
        btnCommon.addEventListener('click', function() {
            btnCommon.className = 'tab-btn active';
            btnRaw.className = 'tab-btn inactive';
            rawTable.style.display = 'none';
            commonTable.style.display = 'block';
        });

        // 全選控制
        document.querySelectorAll('.select-all').forEach(selectAll => {
            selectAll.addEventListener('change', function() {
                const table = this.closest('table');
                table.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
            });
        });

        // 條件變更自動重新查詢 (修改處：使用 dateRangeInput 替換舊的 startDate / endDate)
        document.getElementById('dateRangeInput').addEventListener('change', fetchMeasureData);
        document.getElementById('deviceSelect').addEventListener('change', fetchMeasureData);

        // 搜尋輸入框（防抖動打 API）
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchMeasureData();
            }, 400);
        });

        // --- 下載 Modal 控制邏輯 ---
        const downloadModal = document.getElementById('downloadModal');
        const openDownloadModalBtn = document.getElementById('btnDownload');
        const closeDownloadModalBtn = document.getElementById('closeDownloadModal');
        const cancelDownloadBtn = document.getElementById('cancelDownloadBtn');
        const confirmDownloadBtn = document.getElementById('confirmDownloadBtn');
        const downloadModalDesc = document.getElementById('downloadModalDesc');

        // 按下主頁「下載」按鈕：計算勾選與全部資料筆數並開啟 Modal
        openDownloadModalBtn.addEventListener('click', function() {
            const activeTable = rawTable.style.display !== 'none' ? rawTable : commonTable;
            const selectedBoxes = Array.from(activeTable.querySelectorAll('.row-checkbox:checked'));

            if (selectedBoxes.length > 0) {
                const selectedIds = selectedBoxes.map(cb => cb.value);
                pendingDownloadData = currentMeasureData.filter(item => selectedIds.includes(String(item.id)));
                downloadModalDesc.textContent = `您已勾選 ${pendingDownloadData.length} 筆資料，準備匯出。`;
            } else {
                pendingDownloadData = currentMeasureData;
                downloadModalDesc.textContent = `目前未勾選特定資料，將為您匯出頁面上全部 ${pendingDownloadData.length} 筆資料。`;
            }

            if (pendingDownloadData.length === 0) {
                alert('目前無可下載的資料！');
                return;
            }

            downloadModal.style.display = 'flex';
        });

        // Modal 關閉 / 取消按鈕
        closeDownloadModalBtn.addEventListener('click', function() { downloadModal.style.display = 'none'; });
        cancelDownloadBtn.addEventListener('click', function() { downloadModal.style.display = 'none'; });

        // Modal 「確定下載」按鈕：執行下載
        confirmDownloadBtn.addEventListener('click', function() {
            const selectedFormat = document.querySelector('input[name="exportFormat"]:checked').value;
            downloadModal.style.display = 'none';
            executeDownload(pendingDownloadData, selectedFormat);
        });

        // --- 刪除 Modal 控制邏輯 ---
        const deleteModal = document.getElementById('deleteModal');
        const openDeleteModalBtn = document.getElementById('btnDelete');
        const closeDeleteModalBtn = document.getElementById('closeDeleteModal');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteModalDesc = document.getElementById('deleteModalDesc');

        // 按下主頁「刪除」按鈕：檢查勾選狀態並開啟 Modal
        openDeleteModalBtn.addEventListener('click', function() {
            const activeTable = rawTable.style.display !== 'none' ? rawTable : commonTable;
            pendingDeleteBoxes = Array.from(activeTable.querySelectorAll('.row-checkbox:checked'));

            if (pendingDeleteBoxes.length === 0) {
                alert('請先勾選欲刪除的資料！');
                return;
            }

            deleteModalDesc.textContent = `確定要刪除選取的 ${pendingDeleteBoxes.length} 筆資料嗎？`;
            deleteModal.style.display = 'flex';
        });

        // Modal 關閉 / 取消按鈕
        closeDeleteModalBtn.addEventListener('click', function() { deleteModal.style.display = 'none'; });
        cancelDeleteBtn.addEventListener('click', function() { deleteModal.style.display = 'none'; });

        // Modal 「確定刪除」按鈕：發送請求刪除
        confirmDeleteBtn.addEventListener('click', async function() {
            deleteModal.style.display = 'none';
            await executeDelete(pendingDeleteBoxes);
        });

        // --- 登出 Modal 控制邏輯 ---
        const logoutModal = document.getElementById('logoutModal');
        const openLogoutModalBtn = document.getElementById('openLogoutModal');
        const closeLogoutModalBtn = document.getElementById('closeLogoutModal');
        const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');

        openLogoutModalBtn.addEventListener('click', function() { logoutModal.style.display = 'flex'; });
        closeLogoutModalBtn.addEventListener('click', function() { logoutModal.style.display = 'none'; });
        cancelLogoutBtn.addEventListener('click', function() { logoutModal.style.display = 'none'; });

        // 點擊 Modal 外圍黑色遮罩關閉對話框
        window.addEventListener('click', function(e) {
            if (e.target === logoutModal) logoutModal.style.display = 'none';
            if (e.target === deleteModal) deleteModal.style.display = 'none';
            if (e.target === downloadModal) downloadModal.style.display = 'none';
        });

        // 頁面初始化
        document.addEventListener('DOMContentLoaded', async function() {
            initDateRangePicker();
            await loadDeviceOptions();
            await fetchMeasureData();
        });
    </script>
</body>
</html>