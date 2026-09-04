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
                <!-- <span>📅</span> -->
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
            <input type="text" id="searchInput" class="search-input" placeholder="請輸入檔案名稱關鍵字...">
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
            </div>

            <!-- 2. 共通格式表格 -->
            <div id="commonTable" style="display: none;">
                <table class="data-table">
                    <!-- 修正處 1：新增 commonTableHead 以搭配動態表頭 -->
                    <thead id="commonTableHead">
                        <tr>
                            <th class="checkbox-col"><input type="checkbox" class="select-all"></th>
                            <th>檔案日期</th>
                            <th>量測設備</th>
                            <th>檔案名稱</th>
                            <th>檔案大小</th>
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

    <!-- 在 HTML/PHP 頁面 <head> 或 <body> 底部引入 -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script> -->
    <script src="./js/jszip/jszip.min.js"></script>
    <script src="./js/FileSaver/FileSaver.min.js"></script>

    <script>
        const DEV_API_URL = '<?= $g_root_url ?>api/JTG_devselection.php';
        const MEASURE_API_URL = '<?= $g_root_url ?>api/JTG_measure.php';
        const SSO_TOKEN = '<?= htmlspecialchars($sso_token); ?>';

        let currentMeasureData = []; 
        let debounceTimer = null;
        let pendingDeleteBoxes = []; 
        let pendingDownloadData = []; 

        const btnRaw = document.getElementById('btnRaw');
        const btnCommon = document.getElementById('btnCommon');
        const rawTable = document.getElementById('rawTable');
        const commonTable = document.getElementById('commonTable');
        const searchInput = document.getElementById('searchInput');

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

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

                let deviceList = [];
                if (res.status === 'true' && res.data && Array.isArray(res.data)) {
                    deviceList = res.data;
                }

                let optionsHtml = '<option value="">全部設備</option>';
                if (deviceList.length > 0) {
                    // 修正處 2：同時把中文名稱寫入 data-name 屬性，供後續判定
                    optionsHtml += deviceList.map(dev => `
                        <option value="${escapeHtml(dev.device_type)}" data-name="${escapeHtml(dev.device_name)}">
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
        // 2. 取得健檢量測資料 (JTG_measure API - GET)
        // =========================================================
        async function fetchMeasureData() {
            const rawTbody = document.getElementById('rawTableBody');
            const commonTbody = document.getElementById('commonTableBody');
            rawTbody.innerHTML = '<tr><td colspan="5" class="no-data">資料載入中...</td></tr>';
            commonTbody.innerHTML = '<tr><td colspan="9" class="no-data">資料載入中...</td></tr>';
            
            clearSelections();

            const dateRangeVal = document.getElementById('dateRangeInput').value.trim();
            let startDate = '';
            let endDate = '';

            if (dateRangeVal) {
                const splitDates = dateRangeVal.split(/\s*(?:to|~|\s)\s*/i);
                startDate = splitDates[0] || '';
                endDate = splitDates[1] || splitDates[0] || '';
            }

            const deviceType = document.getElementById('deviceSelect').value;
            const searchKeyword = searchInput.value.trim();

            // 判斷目前是否在「原始資料」頁籤
            const isRawTabActive = rawTable.style.display !== 'none';

            try {
                const params = new URLSearchParams();
                params.append('sso_token', SSO_TOKEN);
                if (startDate) params.append('start_date', startDate + ' 00:00:00');
                if (endDate) params.append('end_date', endDate + ' 23:59:59');
                if (deviceType) params.append('machine_model', deviceType);

                // 核心修改點：只有在「原始資料」頁籤且有輸入關鍵字時，才傳送 file_name 參數
                if (isRawTabActive && searchKeyword) {
                    params.append('file_name', searchKeyword);
                }

                const response = await fetch(`${MEASURE_API_URL}?${params.toString()}`, { method: 'GET' });
                const res = await response.json();

                if (res.status === 'true' && res.data && Array.isArray(res.data)) {
                    currentMeasureData = res.data;
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
            
            // 修正處 3：抓取 Select 目前選中的設備中文名稱傳給 renderCommonTable
            const selectElem = document.getElementById('deviceSelect');
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const deviceName = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.text.trim()) : '';

            renderCommonTable(currentMeasureData, deviceName);
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
                // 過濾掉 file_name 為 null、undefined 或空白的資料
                if (!row.file_data || String(row.file_name).trim() === '') {
                    return;
                }

                const dateOnly = row.measure_date ? row.measure_date.split(' ')[0] : '-';
                const deviceName = row.device_type_zhtw || row.machine_model || row.device_no || '-';
                const fileName = row.file_name;
                const fileSize = row.file_size ? formatFileSize(row.file_size) : '-';

                // 核心修改：搜尋關鍵字時，僅針對「檔案名稱 (fileName)」進行比對
                if (keyword) {
                    if (!fileName.toLowerCase().includes(keyword)) return;
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
                rawTbody.innerHTML = '<tr><td colspan="5" class="no-data">查無符合條件的原始資料</td></tr>';
            } else {
                rawTbody.innerHTML = html;
            }
        }

        // =========================================================
        // 4. 渲染動態設備表格 (Dynamic Device Table - 共通格式)
        // =========================================================
        function renderCommonTable(dataList, selectedDeviceName) {
            const commonThead = document.getElementById('commonTableHead');
            const commonTbody = document.getElementById('commonTableBody');
            const keyword = searchInput.value.trim().toLowerCase();

            // 通用基本資訊欄位（身份證號、姓名、工號、流水號）
            const baseColumns = [
                { 
                    title: "身份證號", 
                    getVal: (p, r) => p.id_card || p.person_id || r.tester_identifier || '-' 
                },
                { 
                    title: "姓名", 
                    getVal: (p, r) => p.name || p.user_name || r.tester_name || '-' 
                },
                { 
                    title: "工號", 
                    getVal: (p, r) => p.emp_id || p.staff_id || r.tester_work_id || '-' 
                },
                { 
                    title: "流水號", 
                    getVal: (p, r) => r.measure_no || r.sid || '-' 
                }
            ];

            let deviceColumns = [];
            switch (selectedDeviceName) {
                case "眼壓儀":
                    deviceColumns = [
                        { title: "次數", getVal: (p, r, idx) => idx + 1 },
                        { title: "眼壓值(L)", getVal: (p) => p.LeftEye_mmHg || '-' },
                        { title: "眼壓值(R)", getVal: (p) => p.RightEye_mmHg || '-' },
                        { title: "量測時間", getVal: (p, r) => r.measure_date || '-' }
                    ];
                    break;

                case "身高體重機":
                    deviceColumns = [
                        { title: "次數", getVal: (p, r, idx) => idx + 1 },
                        { title: "身高", getVal: (p) => p.Height_cm || '-' },
                        { title: "體重", getVal: (p) => p.Weight_kg || '-' },
                        { title: "BMI", getVal: (p) => p.BMI || '-' },
                        { title: "量測時間", getVal: (p, r) => r.measure_date || '-' }
                    ];
                    break;

                case "血壓計":
                    deviceColumns = [
                        { title: "次數", getVal: (p, r, idx) => idx + 1 },
                        { title: "收縮壓", getVal: (p) => p.SYS || p.sys || p.systolic || '-' },
                        { title: "舒張壓", getVal: (p) => p.DIA || p.dia || p.diastolic || '-' },
                        { title: "脈搏", getVal: (p) => p.Pulse || p.pulse || '-' },
                        { title: "量測時間", getVal: (p, r) => r.measure_date || '-' }
                    ];
                    break;

                case "驗光機":
                    deviceColumns = [
                        { title: "次數", getVal: (p, r, idx) => idx + 1 },
                        { title: "屈光度(L)", getVal: (p) => p.LeftEyeTypical?.SPH || '-' },
                        { title: "屈光度(R)", getVal: (p) => p.RightEyeTypical?.SPH || '-' },
                        { title: "閃光度(L)", getVal: (p) => p.LeftEyeTypical?.CYL || '-' },
                        { title: "閃光度(R)", getVal: (p) => p.RightEyeTypical?.CYL || '-' },
                        { title: "量測時間", getVal: (p, r) => r.measure_date || '-' }
                    ];
                    break;

                case "體脂計":
                case "肺功能儀":
                case "骨密度儀":
                default:
                    deviceColumns = [
                        { 
                            title: "檔案日期", 
                            getVal: (p, r) => r.measure_date ? r.measure_date.split(' ')[0] : '-' 
                        },
                        { 
                            title: "量測設備", 
                            getVal: (p, r) => r.device_type_zhtw || r.machine_model || selectedDeviceName || '-' 
                        },
                        { 
                            title: "檔案名稱", 
                            getVal: (p, r) => r.file_name || '-' 
                        },
                        { 
                            title: "檔案大小", 
                            getVal: (p, r) => r.file_size ? formatFileSize(r.file_size) : '-' 
                        }
                    ];
                    break;
            }

            // 組合基本欄位與設備專屬欄位
            const columns = [...baseColumns, ...deviceColumns];
            const totalColumns = columns.length + 1;

            if (commonThead) {
                let headHtml = '<tr><th class="checkbox-col"><input type="checkbox" class="select-all"></th>';
                columns.forEach(col => {
                    headHtml += `<th>${escapeHtml(col.title)}</th>`;
                });
                headHtml += '</tr>';
                commonThead.innerHTML = headHtml;

                const selectAllBtn = commonThead.querySelector('.select-all');
                if (selectAllBtn) {
                    selectAllBtn.addEventListener('change', function() {
                        commonTbody.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
                    });
                }
            }

            if (!dataList || dataList.length === 0) {
                commonTbody.innerHTML = `<tr><td colspan="${totalColumns}" class="no-data">查無共通格式資料</td></tr>`;
                return;
            }

            let bodyHtml = '';
            let matchCount = 0;

            dataList.forEach((row, index) => {
                // 過濾條件：僅顯示 file_data 或 file_name 為空的共通格式資料
                const hasFileData = row.file_data && String(row.file_data).trim() !== '';
                const hasFileName = row.file_name && String(row.file_name).trim() !== '';

                if (hasFileData && hasFileName) {
                    return;
                }

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

                if (keyword) {
                    const idCard = parsedObj.id_card || parsedObj.person_id || row.tester_identifier || '';
                    const name = parsedObj.name || parsedObj.user_name || row.tester_name || '';
                    const empId = parsedObj.emp_id || parsedObj.staff_id || row.tester_work_id || '';
                    const sid = row.sid || row.measure_no || '';
                    const matchText = `${idCard} ${name} ${empId} ${sid}`.toLowerCase();

                    if (!matchText.includes(keyword)) return;
                }

                matchCount++;

                bodyHtml += `<tr><td class="checkbox-col"><input type="checkbox" class="row-checkbox" value="${escapeHtml(row.id)}"></td>`;
                columns.forEach(col => {
                    const val = col.getVal(parsedObj, row, index);
                    bodyHtml += `<td>${escapeHtml(String(val))}</td>`;
                });
                bodyHtml += '</tr>';
            });

            if (matchCount === 0) {
                commonTbody.innerHTML = `<tr><td colspan="${totalColumns}" class="no-data">查無符合搜尋條件的資料</td></tr>`;
            } else {
                commonTbody.innerHTML = bodyHtml;
            }
        }

        // =========================================================
        // 5. 執行檔案下載匯出邏輯 (CSV / JSON)
        // =========================================================
        // =========================================================
        // 5. 根據步驟一、二、三 執行打包壓縮 ZIP 並下載
        // =========================================================
        async function executeZipDownload(dataList) {
            if (!dataList || dataList.length === 0) {
                alert('無可匯出的資料！');
                return;
            }

            const zip = new JSZip();

            dataList.forEach((item, index) => {
                // 檢查是否有二進位檔案內容 (file_data)
                // 取得原始檔名
                const originalFileName = item.file_name || '';
                // 檢查是否有原始檔名且 file_data 存在
                if (originalFileName !== '' && item.file_data) {
                    // 1. 提取原副檔名 (包含點，例如: ".csv" 或 ".pdf")
                    const lastDotIndex = originalFileName.lastIndexOf('.');
                    const ext = lastDotIndex !== -1 ? originalFileName.substring(lastDotIndex) : '';

                    // 2. 處理檔名所需的各個欄位資訊
                    const measureDate = (item.measure_date || '').replace(/[- :]/g, '');
                    const measureNo = item.measure_no || item.sid || '';
                    const machineModel = item.machine_model || '';
                    const assetNo = item.asset_no || '';

                    // 3. 組合新檔名：量測日期_流水號_型號_序號.原副檔名
                    const newFileName = `${measureDate}_${measureNo}_${machineModel}_${assetNo}${ext}`;

                    let binaryData = item.file_data;

                    // 4. 判斷資料格式並寫入 ZIP
                    if (typeof binaryData === 'string') {
                        // 去除可能帶有的 Data URI 前綴 (例如: data:text/csv;base64,xxxx)
                        if (binaryData.includes(',')) {
                            binaryData = binaryData.split(',')[1];
                        }
                        // 去除換行與空白
                        binaryData = binaryData.replace(/\s/g, '');
                        
                        console.log("這段是 Base64，檔名改為：", newFileName);
                        zip.file(newFileName, binaryData, { base64: true });
                    } else {
                        console.log("這段是 Blob/Binary，檔名改為：", newFileName);
                        zip.file(newFileName, binaryData);
                    }
                } else {
                    // 【步驟二】沒有 file_data，輸出成 txt 檔
                    // 命名規則：量測日期 + 流水號 + 型號 + 序號
                    const measureDate = (item.measure_date || '').replace(/[- :]/g, '');
                    const measureNo = item.measure_no || item.sid || '';
                    const machineModel = item.machine_model || '';
                    const assetNo = item.asset_no || '';
                    
                    const txtFileName = `${measureDate}_${measureNo}_${machineModel}_${assetNo}.txt`;

                    // 解析 json_data 內容
                    let parsedJson = {};
                    try {
                        if (item.up_json_data) {
                            parsedJson = typeof item.up_json_data === 'string' ? JSON.parse(item.up_json_data) : item.up_json_data;
                        } else if (item.json_data) {
                            parsedJson = typeof item.json_data === 'string' ? JSON.parse(item.json_data) : item.json_data;
                        }
                    } catch(e) {
                        parsedJson = {};
                    }

                    // 組合 COMMON 欄位與 json_data 展開欄位
                    let txtContent = "";
                    txtContent += `measure_no: ${item.measure_no || ''}\n`;
                    txtContent += `tester_identifier: ${item.tester_identifier || ''}\n`;
                    txtContent += `tester_work_id: ${item.tester_work_id || ''}\n`;
                    txtContent += `tester_name: ${item.tester_name || ''}\n`;
                    txtContent += `tester_age: ${item.tester_age || ''}\n`;
                    txtContent += `tester_height: ${item.tester_height || ''}\n`;
                    txtContent += `editor: ${item.editor || ''}\n`;
                    txtContent += `asset_no: ${item.asset_no || ''}\n`;
                    txtContent += `device_type_zhtw: ${item.device_type_zhtw || ''}\n`;
                    txtContent += `machine_model: ${item.machine_model || ''}\n`;

                    // 追加 json_data 內部解析過後的欄位
                    txtContent += `--- JSON DATA ---\n`;
                    for (const [key, value] of Object.entries(parsedJson)) {
                        txtContent += `${key}: ${typeof value === 'object' ? JSON.stringify(value) : value}\n`;
                    }

                    zip.file(txtFileName, txtContent);
                }
            });

            // 【步驟三】壓縮成 MeasureData_當下日期時間.zip 並開始下載
            const zipFileName = `MeasureData_${getFormattedCurrentDateTime()}.zip`;
            const content = await zip.generateAsync({ type: "blob" });
            saveAs(content, zipFileName);
        }
        function getFormattedCurrentDateTime() {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0'); // 月份從 0 開始，所以要 +1
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            
            // 組合並回傳 YYYYMMDDHHmmss 格式
            return `${yyyy}${mm}${dd}${hh}${min}${ss}`;
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

        function initDateRangePicker() {
            flatpickr("#dateRangeInput", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "zh_tw",
                locale: { rangeSeparator: " - " },
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        fetchMeasureData();
                    }
                }
            });
        }

        // =========================================================
        // 事件監聽與切換
        // =========================================================
        
        btnRaw.addEventListener('click', function() {
            btnRaw.className = 'tab-btn active';
            btnCommon.className = 'tab-btn inactive';
            rawTable.style.display = 'block';
            commonTable.style.display = 'none';
            
            // 1. 動態切換預設提示文字
            searchInput.placeholder = "請輸入檔案名稱關鍵字...";
            // 2. 清空輸入框內容
            searchInput.value = '';
            
            // 3. 切換 Tab 時取消勾選並重新載入/繪製資料
            clearSelections();
            fetchMeasureData();
        });

        btnCommon.addEventListener('click', function() {
            btnCommon.className = 'tab-btn active';
            btnRaw.className = 'tab-btn inactive';
            rawTable.style.display = 'none';
            commonTable.style.display = 'block';
            
            // 1. 動態切換預設提示文字
            searchInput.placeholder = "請輸入流水號、身分證號、姓名...";
            // 2. 清空輸入框內容
            searchInput.value = '';

            // 3. 切換 Tab 時取消勾選並重新載入/繪製資料
            clearSelections();
            fetchMeasureData();
        });

        document.querySelectorAll('.select-all').forEach(selectAll => {
            selectAll.addEventListener('change', function() {
                const table = this.closest('table');
                table.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
            });
        });

        document.getElementById('dateRangeInput').addEventListener('change', fetchMeasureData);
        document.getElementById('deviceSelect').addEventListener('change', fetchMeasureData);

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

        closeDownloadModalBtn.addEventListener('click', function() { downloadModal.style.display = 'none'; });
        cancelDownloadBtn.addEventListener('click', function() { downloadModal.style.display = 'none'; });

        confirmDownloadBtn.addEventListener('click', function() {
            downloadModal.style.display = 'none';
            executeZipDownload(pendingDownloadData);
        });

        // --- 刪除 Modal 控制邏輯 ---
        const deleteModal = document.getElementById('deleteModal');
        const openDeleteModalBtn = document.getElementById('btnDelete');
        const closeDeleteModalBtn = document.getElementById('closeDeleteModal');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteModalDesc = document.getElementById('deleteModalDesc');

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

        closeDeleteModalBtn.addEventListener('click', function() { deleteModal.style.display = 'none'; });
        cancelDeleteBtn.addEventListener('click', function() { deleteModal.style.display = 'none'; });

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

        window.addEventListener('click', function(e) {
            if (e.target === logoutModal) logoutModal.style.display = 'none';
            if (e.target === deleteModal) deleteModal.style.display = 'none';
            if (e.target === downloadModal) downloadModal.style.display = 'none';
        });

        document.addEventListener('DOMContentLoaded', async function() {
            initDateRangePicker();
            await loadDeviceOptions();
            await fetchMeasureData();
        });
        
        // 清除所有表格中的 Checkbox 選取狀態
        function clearSelections() {
            // 取消全選框的勾選
            document.querySelectorAll('.select-all').forEach(cb => cb.checked = false);
            // 取消單列 Checkbox 的勾選
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        }
    </script>
</body>
</html>