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
    // echo "username :${username}, userrole :${userrole}, acc_id :${member_id}, acc_id :${member_id}";
    // exit;

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
    <title>設備序號清單 - <?= $g_backend_title; ?></title>
    
    <!-- Flatpickr 日期選擇器套件 CSS -->
    <link rel="stylesheet" href="./css/flatpickr.min.css">
    <link href="./css/device_list.css" rel="stylesheet">
</head>
<body>

    <!-- 頂部導航列 -->
    <header class="navbar">
        <nav class="nav-links">
            <span><?= $g_online_zhtw ?></span>
            <a href="dashboard.php">資料管理</a>
            <span class="separator">|</span>
            <a href="device_list.php" style="font-weight: bold;">設備序號清單</a>
            <span class="separator">|</span>
            <a href="<?= $cloud_url; ?>">離線/雲端管理</a>
            <?= $org_str; ?>
        </nav>
        <div class="user-info">
            <span>登入者：<?php echo htmlspecialchars($username); ?></span>
            <button type="button" id="openLogoutModal" class="logout-btn">登出</button>
        </div>
    </header>

    <main class="main-container">
        <h1 class="page-title">設備序號清單</h1>

        <!-- 篩選列 -->
        <div class="filter-row">
            <span class="filter-label">量測設備</span>
            <div class="select-container">
                <select id="deviceSelect">
                    <option value="">載入中...</option>
                </select>
            </div>

            <span class="filter-label" style="margin-left: 15px;">更新日期</span>
            <div class="date-input-container">
                <input type="text" id="dateRangeInput" placeholder="請選擇起迄日期" readonly>
                <span>📅</span>
            </div>
        </div>

        <!-- 搜尋與動作按鈕 -->
        <div class="search-row">
            <span class="filter-label">搜尋</span>
            <input type="text" id="searchInput" class="search-input" placeholder="搜尋序號或設備名稱...">
            <button class="btn-batch-add" id="openBatchModal">批次新增</button>
            <button class="btn-delete" id="openDeleteModal">刪除</button>
        </div>

        <!-- 資料表格區塊 -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="checkbox-col"><input type="checkbox" id="selectAll"></th>
                        <th>量測設備</th>
                        <th>設備編號</th>
                        <th>序號</th>
                        <th>加入日期</th>
                        <th>更新日期</th>
                        <th>編輯</th>
                    </tr>
                </thead>
                <tbody id="deviceTableBody">
                    <tr><td colspan="7" class="no-data">資料載入中...</td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- 頁尾分頁控制 -->
    <footer class="pagination">
        <span class="page-link disabled">&larr; Previous</span>
        <a href="#" class="page-link active">1</a>
        <a href="#" class="page-link">Next &rarr;</a>
    </footer>

    <!-- 批次新增 Modal -->
    <div class="modal-overlay" id="batchModal">
        <div class="modal-container">
            <div class="modal-close" id="closeBatchModal">X</div>
            <h2 class="modal-title">批次新增</h2>
            <p class="modal-desc">請下載範例檔後依格式填入後上傳</p>
            <div class="modal-btn-group">
                <button class="btn-modal-download" id="downloadSampleBtn">範例檔下載</button>
                <button class="btn-modal-upload" id="openUploadModalBtn">回填上傳</button>
            </div>

            <!-- 下載進度條區域 -->
            <div class="progress-box" id="downloadProgressBox" style="display: none;">
                <div class="progress-file-info" id="progressFileName">檔名：-</div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progressBarFill"></div>
                </div>
                <div class="progress-text" id="progressText">準備下載中 (0%)</div>
            </div>
        </div>
    </div>

    <!-- 回填上傳 Modal -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal-container" style="max-width: 550px;">
            <div class="modal-close" id="closeUploadModal">X</div>
            <h2 class="modal-title">回填檔案上傳</h2>
            <p class="modal-desc">請拖拽 .xls 或 .xlsx 檔案至中央區域，或點擊選擇檔案</p>
            
            <!-- 拖拽區域 -->
            <div class="upload-drop-zone" id="dropZone">
                <span class="upload-icon">📁</span>
                <p class="upload-text" id="dropZoneText">拖拽 .xls / .xlsx 檔案至此，或點擊選擇檔案</p>
                <input type="file" id="fileInput" accept=".xls,.xlsx" style="display: none;">
            </div>

            <!-- 上傳進度條區域 -->
            <div class="progress-box" id="uploadProgressBox" style="display: none;">
                <div class="progress-file-info" id="uploadProgressFileName">檔名：-</div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="uploadProgressBarFill"></div>
                </div>
                <div class="progress-text" id="uploadProgressText">準備上傳中 (0%)</div>
            </div>

            <!-- 匯入結果統計與失敗清單區域 -->
            <div class="result-summary-box" id="uploadResultBox" style="display: none;">
                <div class="summary-badges" id="uploadSummaryBadges"></div>
                <div class="fail-detail-area" id="uploadFailDetailArea" style="display: none;">
                    <div class="fail-detail-title">⚠️ 匯入失敗明細清單：</div>
                    <table class="fail-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Excel 列</th>
                                <th>失敗原因</th>
                            </tr>
                        </thead>
                        <tbody id="uploadFailTableBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-btn-group" style="margin-top: 20px;">
                <button class="btn-modal-cancel" id="cancelUploadBtn">取消</button>
                <button class="btn-modal-save" id="startUploadBtn">上傳</button>
            </div>
        </div>
    </div>

    <!-- 刪除確認 Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-container">
            <div class="modal-close" id="closeDeleteModal">X</div>
            <h2 class="modal-title">刪除確認</h2>
            <p class="modal-desc" id="deleteModalDesc">確定要刪除選取的設備資料嗎？</p>
            <div class="modal-btn-group">
                <button class="btn-modal-cancel" id="cancelDeleteBtn">取消</button>
                <button class="btn-modal-confirm-delete" id="confirmDeleteBtn">確定刪除</button>
            </div>
        </div>
    </div>

    <!-- 編輯設備 Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-container">
            <div class="modal-close" id="closeEditModal">X</div>
            <h2 class="modal-title">編輯設備序號</h2>
            <input type="hidden" id="editTargetId">
            <div class="form-group">
                <label>量測設備</label>
                <select id="editDeviceType">
                    <option value="">載入中...</option>
                </select>
            </div>
            <div class="form-group">
                <label>序號</label>
                <input type="text" id="editAssetNo" placeholder="請輸入資產編號">
            </div>
            <div class="form-group">
                <label>設備編號</label>
                <input type="text" id="editSid" placeholder="請輸入設備序號">
            </div>
            <div class="modal-btn-group" style="margin-top: 25px;">
                <button class="btn-modal-cancel" id="cancelEditBtn">取消</button>
                <button class="btn-modal-save" id="saveEditBtn">儲存變更</button>
            </div>
        </div>
    </div>

    <!-- 登出確認 Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-container">
            <div class="modal-close" id="closeLogoutModal">X</div>
            <h2 class="modal-title">系統登出</h2>
            <p class="modal-desc">確定要登出目前帳號嗎？</p>
            <div class="modal-btn-group">
                <button class="btn-modal-cancel" id="cancelLogoutBtn">取消</button>
                <a href="logout.php" class="btn-modal-confirm-logout">確定登出</a>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS 與語系包 -->
    <script src="./js/flatpickr.js"></script>
    <script src="./js/flatpickr.zh-tw.js"></script>

    <script>
        const DEVSEL_API_URL       = '<?= $g_root_url ?>api/JTG_devselection.php';
        const DEV_API_URL          = '<?= $g_root_url ?>api/JTG_device.php';
        const EXPORT_API_URL       = '<?= $g_root_url ?>api/export2excel.php';
        const IMPORT_API_URL       = '<?= $g_root_url ?>api/import2db.php';
        const PROGRESS_API_URL     = '<?= $g_root_url ?>api/progress.php';
        const DEL_PROGRESS_API_URL = '<?= $g_root_url ?>api/delete_progress.php';
        
        const MEMBER_ID            = '<?= htmlspecialchars($member_id); ?>';
        const SSO_TOKEN            = '<?= htmlspecialchars($sso_token); ?>';

        let currentDeviceList = [];
        let debounceTimer = null;

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            return dateStr.split(' ')[0];
        }

        function getFormattedDateTimeStr() {
            const now = new Date();
            const YYYY = now.getFullYear();
            const MM = String(now.getMonth() + 1).padStart(2, '0');
            const DD = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            return `${YYYY}${MM}${DD}_${hh}${mm}${ss}`;
        }

        // =========================================================
        // 下載範例檔邏輯
        // =========================================================
        document.getElementById('downloadSampleBtn').addEventListener('click', async function() {
            const btn = this;
            const progressBox = document.getElementById('downloadProgressBox');
            const progressFileName = document.getElementById('progressFileName');
            const progressBarFill = document.getElementById('progressBarFill');
            const progressText = document.getElementById('progressText');

            const filename = `device_list_${getFormattedDateTimeStr()}`;

            btn.disabled = true;
            btn.style.opacity = '0.6';
            progressBox.style.display = 'block';
            progressFileName.textContent = `檔名：${filename}.xlsx`;
            progressBarFill.style.width = '0%';
            progressText.textContent = '準備匯出資料 (0%)';

            let progressInterval = setInterval(async () => {
                try {
                    const formData = new FormData();
                    formData.append('memberid', MEMBER_ID);
                    formData.append('filename', filename);
                    formData.append('flag', 'export');

                    const res = await fetch(PROGRESS_API_URL, { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.status === 'true' && data.data) {
                        const percent = parseInt(data.data.percentage || 0);
                        progressBarFill.style.width = `${percent}%`;
                        progressText.textContent = `處理中... (${percent}%)`;
                    }
                } catch (e) {
                    console.error('查詢下載進度失敗:', e);
                }
            }, 500);

            try {
                const formData = new FormData();
                formData.append('filename', filename);
                formData.append('memberid', MEMBER_ID);
                formData.append('caption', '設備清單範例檔');
                formData.append('table', 'data_device');

                const response = await fetch(EXPORT_API_URL, { method: 'POST', body: formData });
                const result = await response.json();
                clearInterval(progressInterval);

                if (result.status === 'true') {
                    progressBarFill.style.width = '100%';
                    progressText.textContent = '匯出完成 (100%)，開始下載...';

                    let downloadUrl = '';
                    if (result.data) {
                        try {
                            const parsedData = JSON.parse(result.data);
                            downloadUrl = parsedData.download_file_name || '';
                        } catch(e) { downloadUrl = ''; }
                    }

                    if (!downloadUrl) downloadUrl = `excel/export/${filename}.xlsx`;
                    if (window.location.protocol === 'https:' && downloadUrl.startsWith('http://')) {
                        downloadUrl = downloadUrl.replace('http://', 'https://');
                    }

                    fetch(downloadUrl)
                        .then(res => res.blob())
                        .then(blob => {
                            const blobUrl = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = blobUrl;
                            a.download = `${filename}.xlsx`;
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(blobUrl);
                        })
                        .catch(() => window.open(downloadUrl, '_blank'));

                    const delFormData = new FormData();
                    delFormData.append('memberid', MEMBER_ID);
                    delFormData.append('filename', `${filename}.xlsx`);
                    delFormData.append('flag', 'export');
                    await fetch(DEL_PROGRESS_API_URL, { method: 'POST', body: delFormData });

                } else {
                    alert('匯出失敗：' + (result.responseMessage || '系統異常'));
                }
            } catch (err) {
                alert('下載失敗，請檢查網路連線！');
            } finally {
                clearInterval(progressInterval);
                btn.disabled = false;
                btn.style.opacity = '1';
                setTimeout(() => { progressBox.style.display = 'none'; }, 2000);
            }
        });

        // =========================================================
        // 上傳回填檔案邏輯
        // =========================================================
        const uploadModal          = document.getElementById('uploadModal');
        const openUploadModalBtn   = document.getElementById('openUploadModalBtn');
        const closeUploadModal    = document.getElementById('closeUploadModal');
        const cancelUploadBtn     = document.getElementById('cancelUploadBtn');
        const dropZone            = document.getElementById('dropZone');
        const fileInput           = document.getElementById('fileInput');
        const dropZoneText        = document.getElementById('dropZoneText');
        const startUploadBtn      = document.getElementById('startUploadBtn');

        const uploadProgressBox      = document.getElementById('uploadProgressBox');
        const uploadProgressFileName = document.getElementById('uploadProgressFileName');
        const uploadProgressBarFill = document.getElementById('uploadProgressBarFill');
        const uploadProgressText     = document.getElementById('uploadProgressText');

        const uploadResultBox        = document.getElementById('uploadResultBox');
        const uploadSummaryBadges    = document.getElementById('uploadSummaryBadges');
        const uploadFailDetailArea   = document.getElementById('uploadFailDetailArea');
        const uploadFailTableBody    = document.getElementById('uploadFailTableBody');

        let selectedUploadFile = null;

        openUploadModalBtn.addEventListener('click', () => {
            document.getElementById('batchModal').style.display = 'none';
            uploadModal.style.display = 'flex';
        });

        const hideUploadModal = () => {
            uploadModal.style.display = 'none';
            resetUploadState();
        };

        closeUploadModal.addEventListener('click', hideUploadModal);
        cancelUploadBtn.addEventListener('click', hideUploadModal);

        function resetUploadState() {
            selectedUploadFile = null;
            fileInput.value = '';
            dropZoneText.textContent = '拖拽 .xls / .xlsx 檔案至此，或點擊選擇檔案';
            dropZone.classList.remove('dragover');
            
            uploadProgressBox.style.display = 'none';
            uploadProgressBarFill.style.width = '0%';
            uploadProgressText.textContent = '準備上傳中 (0%)';
            
            uploadResultBox.style.display = 'none';
            uploadSummaryBadges.innerHTML = '';
            uploadFailDetailArea.style.display = 'none';
            uploadFailTableBody.innerHTML = '';

            startUploadBtn.disabled = false;
            startUploadBtn.classList.remove('btn-disabled');
            startUploadBtn.textContent = '上傳';
        }

        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleSelectedFile(e.target.files[0]);
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => { e.preventDefault(); e.stopPropagation(); }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) handleSelectedFile(files[0]);
        });

        function handleSelectedFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'xls' && ext !== 'xlsx') {
                alert('請選擇副檔名為 .xls 或 .xlsx 的 Excel 檔案！');
                return;
            }
            selectedUploadFile = file;
            dropZoneText.textContent = `已選取檔案：${file.name}`;
        }

        startUploadBtn.addEventListener('click', async () => {
            if (!selectedUploadFile) {
                alert('請先選擇或拖拽檔案！');
                return;
            }

            const filename = selectedUploadFile.name;

            startUploadBtn.disabled = true;
            startUploadBtn.classList.add('btn-disabled');

            uploadProgressBox.style.display = 'block';
            uploadProgressFileName.textContent = `檔名：${filename}`;
            uploadProgressBarFill.style.width = '0%';
            uploadProgressText.textContent = '開始分析檔案 (0%)';

            uploadResultBox.style.display = 'none';

            let progressInterval = setInterval(async () => {
                try {
                    const formData = new FormData();
                    formData.append('memberid', MEMBER_ID);
                    formData.append('filename', filename);
                    formData.append('flag', 'import');

                    const res = await fetch(PROGRESS_API_URL, { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.status === 'true' && data.data) {
                        const percent = parseInt(data.data.percentage || 0);
                        uploadProgressBarFill.style.width = `${percent}%`;
                        uploadProgressText.textContent = `資料匯入中... (${percent}%)`;
                    }
                } catch (e) {
                    console.error('查詢匯入進度失敗:', e);
                }
            }, 500);

            try {
                const formData = new FormData();
                formData.append('file', selectedUploadFile);
                formData.append('filename', filename);
                formData.append('memberid', MEMBER_ID);
                formData.append('caption', '設備序號清單');
                formData.append('table', 'data_device');

                const response = await fetch(IMPORT_API_URL, { method: 'POST', body: formData });
                const result = await response.json();

                clearInterval(progressInterval);
                uploadProgressBarFill.style.width = '100%';
                uploadProgressText.textContent = '處理完成 (100%)';

                uploadResultBox.style.display = 'block';

                if (result.status === 'true') {
                    let resData = {};
                    try {
                        resData = typeof result.data === 'string' ? JSON.parse(result.data) : (result.data || {});
                    } catch(e) { resData = {}; }

                    const insertCnt = resData.insert_cnt || 0;
                    const updateCnt = resData.update_cnt || 0;
                    const failCnt   = resData.fail_cnt   || 0;
                    const failList  = resData.fail_list  || [];

                    uploadSummaryBadges.innerHTML = `
                        <span class="badge-success">新增成功：${insertCnt} 筆</span>
                        <span class="badge-update">更新成功：${updateCnt} 筆</span>
                        <span class="badge-fail">失敗：${failCnt} 筆</span>
                    `;

                    if (failCnt > 0 && failList.length > 0) {
                        let rowsHtml = '';
                        failList.forEach(item => {
                            rowsHtml += `
                                <tr>
                                    <td>第 ${escapeHtml(item.row)} 列</td>
                                    <td style="color: #dc3545;">${escapeHtml(item.reason)}</td>
                                </tr>
                            `;
                        });
                        uploadFailTableBody.innerHTML = rowsHtml;
                        uploadFailDetailArea.style.display = 'block';
                    }

                    fetchDeviceList();

                    const delFormData = new FormData();
                    delFormData.append('memberid', MEMBER_ID);
                    delFormData.append('filename', filename);
                    delFormData.append('flag', 'import');
                    await fetch(DEL_PROGRESS_API_URL, { method: 'POST', body: delFormData });

                } else {
                    uploadSummaryBadges.innerHTML = `<span class="badge-fail">匯入失敗：${escapeHtml(result.responseMessage || '系統異常')}</span>`;
                }

            } catch (err) {
                clearInterval(progressInterval);
                console.error('上傳過程發生錯誤:', err);
                uploadSummaryBadges.innerHTML = `<span class="badge-fail">系統連線異常，匯入中斷！</span>`;
                uploadResultBox.style.display = 'block';
            }
        });

        // =========================================================
        // 載入與渲染設備列表
        // =========================================================
        async function loadDeviceOptions() {
            const selectElem = document.getElementById('deviceSelect');
            const editSelectElem = document.getElementById('editDeviceType');

            try {
                const params = new URLSearchParams({ sso_token: SSO_TOKEN, get_all: '0' });
                const response = await fetch(`${DEVSEL_API_URL}?${params.toString()}`);
                const res = await response.json();

                let deviceList = (res.status === 'true' && res.data) ? (res.data.data || res.data) : [];

                let filterOptionsHtml = '<option value="">全部設備</option>';
                let editOptionsHtml = '<option value="">請選擇設備</option>';

                if (Array.isArray(deviceList) && deviceList.length > 0) {
                    const options = deviceList.map(dev => `
                        <option value="${escapeHtml(dev.device_type)}">${escapeHtml(dev.device_name)}</option>
                    `).join('');
                    filterOptionsHtml += options;
                    editOptionsHtml += options;
                }

                selectElem.innerHTML = filterOptionsHtml;
                editSelectElem.innerHTML = editOptionsHtml;
            } catch (err) {
                selectElem.innerHTML = '<option value="">載入失敗</option>';
                editSelectElem.innerHTML = '<option value="">載入失敗</option>';
            }
        }

        async function fetchDeviceList() {
            const tbody = document.getElementById('deviceTableBody');
            tbody.innerHTML = '<tr><td colspan="7" class="no-data">資料載入中...</td></tr>';

            const selectedType = document.getElementById('deviceSelect').value;
            const searchKeyword = document.getElementById('searchInput').value.trim();

            try {
                const params = new URLSearchParams({ sso_token: SSO_TOKEN, get_all: '0', search_Key: searchKeyword });
                if (selectedType) params.append('device_type', selectedType);

                const response = await fetch(`${DEV_API_URL}?${params.toString()}`);
                const res = await response.json();

                currentDeviceList = (res.status === 'true' && res.data) ? (res.data.data || res.data) : [];
                renderTable(searchKeyword);
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">載入失敗，請重試</td></tr>';
            }
        }

        function renderTable(keyword = '') {
            const tbody = document.getElementById('deviceTableBody');
            const searchLower = keyword.toLowerCase();

            let filteredList = currentDeviceList.filter(row => {
                if (!keyword) return true;
                const matchText = `${row.device_name || ''} ${row.tag || ''} ${row.asset_no || ''} ${row.sid || ''} ${row.device_type || ''}`.toLowerCase();
                return matchText.includes(searchLower);
            });

            if (filteredList.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">查無符合條件的設備資料</td></tr>';
                return;
            }

            let html = '';
            filteredList.forEach(row => {
                html += `
                    <tr>
                        <td class="checkbox-col">
                            <input type="checkbox" class="row-checkbox" value="${escapeHtml(row.id)}" data-sid="${escapeHtml(row.sid)}">
                        </td>
                        <td>${escapeHtml(row.device_name || row.device_type || '-')}</td>
                        <td>${escapeHtml(row.tag || '-')}</td>
                        <td>${escapeHtml(row.asset_no || '-')}</td>
                        <td>${escapeHtml(formatDate(row.created_at))}</td>
                        <td>${escapeHtml(formatDate(row.updated_at))}</td>
                        <td>
                            <button class="btn-edit" onclick="openEditModalById(${row.id})">編輯</button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        function openEditModalById(id) {
            const target = currentDeviceList.find(item => String(item.id) === String(id));
            if (!target) return;

            const editSelect = document.getElementById('editDeviceType');

            document.getElementById('editTargetId').value = target.id;
            editSelect.value = target.device_type || '';
            
            // 將量測設備選單設為禁用
            editSelect.disabled = true;

            document.getElementById('editAssetNo').value = target.asset_no || '';
            document.getElementById('editSid').value = target.tag || '';

            document.getElementById('editModal').style.display = 'flex';
        }

        function initDateRangePicker() {
            flatpickr("#dateRangeInput", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "zh_tw",
                locale: { rangeSeparator: " - " },
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) fetchDeviceList();
                }
            });
        }

        // =========================================================
        // Modal 事件與控制
        // =========================================================
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        document.getElementById('deviceSelect').addEventListener('change', fetchDeviceList);

        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => renderTable(this.value.trim()), 300);
        });

        const batchModal = document.getElementById('batchModal');
        document.getElementById('openBatchModal').addEventListener('click', () => batchModal.style.display = 'flex');
        document.getElementById('closeBatchModal').addEventListener('click', () => batchModal.style.display = 'none');

        // 刪除 Modal 控制
        const deleteModal = document.getElementById('deleteModal');
        let selectedDeleteIds = [];

        document.getElementById('openDeleteModal').addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('請先勾選要刪除的項目！');
                return;
            }
            selectedDeleteIds = Array.from(checkedBoxes).map(cb => cb.value);
            document.getElementById('deleteModalDesc').textContent = `確定要刪除選取的 ${selectedDeleteIds.length} 筆設備資料嗎？`;
            deleteModal.style.display = 'flex';
        });

        document.getElementById('closeDeleteModal').addEventListener('click', () => deleteModal.style.display = 'none');
        document.getElementById('cancelDeleteBtn').addEventListener('click', () => deleteModal.style.display = 'none');

        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            let successCount = 0, failCount = 0;

            for (const id of selectedDeleteIds) {
                try {
                    const response = await fetch(DEV_API_URL, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ sso_token: SSO_TOKEN, id: parseInt(id), who_call: 'device_list' })
                    });
                    const res = await response.json();
                    if (res.status === 'true') successCount++; else failCount++;
                } catch (err) { failCount++; }
            }

            alert(`刪除完成！成功：${successCount} 筆，失敗：${failCount} 筆`);
            deleteModal.style.display = 'none';
            fetchDeviceList();
        });

        // 編輯 Modal 控制
        const editModal = document.getElementById('editModal');
        document.getElementById('closeEditModal').addEventListener('click', () => editModal.style.display = 'none');
        document.getElementById('cancelEditBtn').addEventListener('click', () => editModal.style.display = 'none');

        document.getElementById('saveEditBtn').addEventListener('click', async function() {
            const id = document.getElementById('editTargetId').value;
            const deviceType = document.getElementById('editDeviceType').value;
            const assetNo = document.getElementById('editAssetNo').value;
            const tag = document.getElementById('editSid').value;

            if (!id) return;

            try {
                const response = await fetch(DEV_API_URL, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sso_token: SSO_TOKEN,
                        id: parseInt(id),
                        device_type: deviceType,
                        asset_no: assetNo,
                        tag: tag,
                        who_call: 'device_list'
                    })
                });

                const res = await response.json();
                if (res.status === 'true') {
                    alert('設備資料修改成功！');
                    editModal.style.display = 'none';
                    fetchDeviceList();
                } else {
                    alert('修改失敗：' + (res.message || '未知錯誤'));
                }
            } catch (err) {
                alert('系統連線異常，儲存變更失敗！');
            }
        });

        // 登出 Modal
        const logoutModal = document.getElementById('logoutModal');
        document.getElementById('openLogoutModal').addEventListener('click', () => logoutModal.style.display = 'flex');
        document.getElementById('closeLogoutModal').addEventListener('click', () => logoutModal.style.display = 'none');
        document.getElementById('cancelLogoutBtn').addEventListener('click', () => logoutModal.style.display = 'none');

        window.addEventListener('click', function(e) {
            if (e.target === batchModal) batchModal.style.display = 'none';
            if (e.target === uploadModal) hideUploadModal();
            if (e.target === deleteModal) deleteModal.style.display = 'none';
            if (e.target === editModal) editModal.style.display = 'none';
            if (e.target === logoutModal) logoutModal.style.display = 'none';
        });

        // 頁面初始化
        document.addEventListener('DOMContentLoaded', async function() {
            initDateRangePicker();
            await loadDeviceOptions();
            await fetchDeviceList();
        });
    </script>
</body>
</html>