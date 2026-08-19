<?php
    include_once('common/entry.php');
    global $g_root_url, $g_is_online, $g_online_zhtw, $g_backend_title, $g_supperuser_all;
    $username  = $_SESSION['accname'] ?? "";
    $userrole  = $_SESSION['user_role'] ?? "";
    $member_id = $_SESSION['member_id'] ?? $_SESSION['accname'] ?? "admin";
    $sso_token = $_SESSION['sso_token'] ?? "";

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
    <title>設備序號清單 - <?= $g_backend_title; ?></title>
    
    <!-- Flatpickr 日期選擇器套件 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <link href="./css/device_list.css" rel="stylesheet">
</head>
<body>

    <!-- 頂部導航列 -->
    <header class="navbar">
        <nav class="nav-links"><span><?= $g_online_zhtw ?></span>
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

        <!-- 第一排：篩選列 -->
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

        <!-- 第二排：搜尋與動作按鈕 -->
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
                <button class="btn-modal-upload">回填上傳</button>
            </div>

            <!-- 新增進度條顯示區域 -->
            <div class="progress-box" id="downloadProgressBox">
                <div class="progress-file-info" id="progressFileName">檔名：-</div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progressBarFill"></div>
                </div>
                <div class="progress-text" id="progressText">準備下載中 (0%)</div>
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
                <label>設備名稱</label>
                <input type="text" id="editDeviceName" placeholder="請輸入設備名稱">
            </div>
            <div class="form-group">
                <label>設備資產編號 (Asset No)</label>
                <input type="text" id="editAssetNo" placeholder="請輸入資產編號">
            </div>
            <div class="form-group">
                <label>設備序號 (SID)</label>
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

    <!-- Flatpickr JS 與 繁體中文語系包 -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/zh-tw.js"></script>

    <script>
        const DEVSEL_API_URL = '<?= $g_root_url ?>api/JTG_devselection.php';
        const DEV_API_URL    = '<?= $g_root_url ?>api/JTG_device.php';
        const EXPORT_API_URL = '<?= $g_root_url ?>api/export2excel.php';
        const PROGRESS_API_URL = '<?= $g_root_url ?>api/progress.php';
        const DEL_PROGRESS_API_URL = '<?= $g_root_url ?>api/delete_progress.php';
        const MEMBER_ID      = '<?= htmlspecialchars($member_id); ?>';
        const SSO_TOKEN      = '<?= htmlspecialchars($sso_token); ?>';

        let currentDeviceList = []; // 快取當前設備資料
        let debounceTimer = null;

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            return dateStr.split(' ')[0];
        }

        // 產生日期時間字串 (YYYYMMDD_HHMMSS)
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
        // 範例檔下載與進度條功能[cite: 1, 3]
        // =========================================================
        document.getElementById('downloadSampleBtn').addEventListener('click', async function() {
            const btn = this;
            const progressBox = document.getElementById('downloadProgressBox');
            const progressFileName = document.getElementById('progressFileName');
            const progressBarFill = document.getElementById('progressBarFill');
            const progressText = document.getElementById('progressText');

            // 1. 產生檔名：device_list_下載日期時間.xls
            const filename = `device_list_${getFormattedDateTimeStr()}`;

            // UI 初始設定
            btn.disabled = true;
            btn.style.opacity = '0.6';
            progressBox.style.display = 'block';
            progressFileName.textContent = `檔名：${filename}.xlsx`;
            progressBarFill.style.width = '0%';
            progressText.textContent = '準備匯出資料 (0%)';

            let progressInterval = null;

            // 輪詢進度函式
            const startPollingProgress = () => {
                progressInterval = setInterval(async () => {
                    try {
                        const formData = new FormData();
                        formData.append('memberid', MEMBER_ID);
                        formData.append('filename', filename);
                        formData.append('flag', 'export');

                        const res = await fetch(PROGRESS_API_URL, {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();

                        if (data.status === 'true' && data.data) {
                            const percent = parseInt(data.data.percentage || 0);
                            progressBarFill.style.width = `${percent}%`;
                            progressText.textContent = `處理中... (${percent}%)`;
                        }
                    } catch (e) {
                        console.error('查詢進度發生錯誤:', e);
                    }
                }, 500);
            };

            try {
                // 啟動進度輪詢
                startPollingProgress();

                // 2. 呼叫匯出 API (export2excel.php)[cite: 1]
                const formData = new FormData();
                formData.append('filename', filename);
                formData.append('memberid', MEMBER_ID);
                formData.append('caption', '設備清單範例檔');
                formData.append('table', 'data_device');

                const jsonObject = Object.fromEntries(formData.entries());
                console.log(jsonObject);
                const response = await fetch(EXPORT_API_URL, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                // console.log(response);
                clearInterval(progressInterval);

                if (result.status === 'true') {
                    progressBarFill.style.width = '100%';
                    progressText.textContent = '匯出完成 (100%)，開始下載...';

                    let downloadUrl = '';
                    if (result.data) {
                        try {
                            const parsedData = JSON.parse(result.data);
                            downloadUrl = parsedData.download_file_name || '';
                        } catch(e) {
                            downloadUrl = '';
                        }
                    }

                    // 1. 補全副檔名 (若後端傳回網址未包含 .xlsx)
                    if (downloadUrl && !downloadUrl.endsWith('.xlsx')) {
                        downloadUrl += '.xlsx';
                    }

                    // 2. 處理相對路徑與 HTTP/HTTPS 混合問題
                    if (!downloadUrl) {
                        downloadUrl = `excel/export/${filename}.xlsx`;
                    }

                    // 如果當前頁面是 HTTPS，將下載連結的 http:// 自動替換為 https://
                    if (window.location.protocol === 'https:' && downloadUrl.startsWith('http://')) {
                        downloadUrl = downloadUrl.replace('http://', 'https://');
                    }

                    // 3. 透過 Blob 方式下載 (可100%繞過瀏覽器對 HTTP/HTTPS 混合下載的封鎖限制)
                    fetch(downloadUrl)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.blob();
                        })
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
                        .catch(err => {
                            console.error('Blob 下載失敗，改用直接跳轉:', err);
                            // 備援方案：若 Fetch 被檔，直接用 window.open
                            window.open(downloadUrl, '_blank');
                        });

                    // 4. 刪除後端進度紀錄
                    const delFormData = new FormData();
                    delFormData.append('memberid', MEMBER_ID);
                    delFormData.append('filename', `${filename}.xlsx`);
                    delFormData.append('flag', 'export');
                    await fetch(DEL_PROGRESS_API_URL, { method: 'POST', body: delFormData });

                } else {
                    alert('匯出失敗：' + (result.responseMessage || '系統異常'));
                }
            } catch (err) {
                console.log('範例檔下載失敗:', err);
                alert('範例檔下載失敗，請連線後重試！');
            } finally {
                clearInterval(progressInterval);
                btn.disabled = false;
                btn.style.opacity = '1';
                setTimeout(() => {
                    progressBox.style.display = 'none';
                }, 2000);
            }
        });

        // =========================================================
        // 1. 動態載入量測設備選單
        // =========================================================
        async function loadDeviceOptions() {
            const selectElem = document.getElementById('deviceSelect');
            const editSelectElem = document.getElementById('editDeviceType');

            try {
                const params = new URLSearchParams();
                params.append('sso_token', SSO_TOKEN);
                params.append('get_all', '0');

                const response = await fetch(`${DEVSEL_API_URL}?${params.toString()}`, { method: 'GET' });
                const res = await response.json();

                let deviceList = [];
                if (res.status === 'true' && res.data && Array.isArray(res.data.data)) {
                    deviceList = res.data.data;
                } else if (res.status === 'true' && Array.isArray(res.data)) {
                    deviceList = res.data;
                }

                let filterOptionsHtml = '<option value="">全部設備</option>';
                let editOptionsHtml = '<option value="">請選擇設備</option>';

                if (deviceList.length > 0) {
                    const options = deviceList.map(dev => `
                        <option value="${escapeHtml(dev.device_type)}">
                            ${escapeHtml(dev.device_name)}
                        </option>
                    `).join('');

                    filterOptionsHtml += options;
                    editOptionsHtml += options;
                }

                selectElem.innerHTML = filterOptionsHtml;
                editSelectElem.innerHTML = editOptionsHtml;
            } catch (err) {
                console.error('載入量測設備選單失敗:', err);
                selectElem.innerHTML = '<option value="">載入失敗</option>';
                editSelectElem.innerHTML = '<option value="">載入失敗</option>';
            }
        }

        // =========================================================
        // 2. 從 JTG_devselection API 撈取設備清單
        // =========================================================
        async function fetchDeviceList() {
            const tbody = document.getElementById('deviceTableBody');
            tbody.innerHTML = '<tr><td colspan="7" class="no-data">資料載入中...</td></tr>';

            const selectedType = document.getElementById('deviceSelect').value;
            const searchKeyword = document.getElementById('searchInput').value.trim();

            try {
                const params = new URLSearchParams();
                params.append('sso_token', SSO_TOKEN);
                params.append('get_all', '0');

                if (selectedType) {
                    params.append('device_type', selectedType);
                }

                const response = await fetch(`${DEV_API_URL}?${params.toString()}`, { method: 'GET' });
                const res = await response.json();

                if (res.status === 'true' && res.data && Array.isArray(res.data.data)) {
                    currentDeviceList = res.data.data;
                } else if (res.status === 'true' && Array.isArray(res.data)) {
                    currentDeviceList = res.data;
                } else {
                    currentDeviceList = [];
                }

                renderTable(searchKeyword);
            } catch (err) {
                console.error('取得設備資料失敗:', err);
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">載入失敗，請重試</td></tr>';
            }
        }

        // =========================================================
        // 3. 渲染資料表格 (含前端關鍵字篩選)
        // =========================================================
        function renderTable(keyword = '') {
            const tbody = document.getElementById('deviceTableBody');
            const searchLower = keyword.toLowerCase();

            let filteredList = currentDeviceList.filter(row => {
                if (!keyword) return true;
                const matchText = `${row.device_name || ''} ${row.asset_no || ''} ${row.sid || ''} ${row.device_type || ''}`.toLowerCase();
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
                        <td>${escapeHtml(row.asset_no || '-')}</td>
                        <td>${escapeHtml(row.sid || '-')}</td>
                        <td>${escapeHtml(formatDate(row.created_at))}</td>
                        <td>${escapeHtml(formatDate(row.updated_at))}</td>
                        <td>
                            <button class="btn-edit" 
                                onclick="openEditModalById(${row.id})">編輯</button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // =========================================================
        // 4. 開啟並填入編輯 Modal 內容
        // =========================================================
        function openEditModalById(id) {
            const target = currentDeviceList.find(item => String(item.id) === String(id));
            if (!target) return;

            document.getElementById('editTargetId').value = target.id;
            document.getElementById('editDeviceType').value = target.device_type || '';
            document.getElementById('editDeviceName').value = target.device_name || '';
            document.getElementById('editAssetNo').value = target.asset_no || '';
            document.getElementById('editSid').value = target.sid || '';

            document.getElementById('editModal').style.display = 'flex';
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

        // 事件監聽與綁定
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        document.getElementById('deviceSelect').addEventListener('change', fetchDeviceList);

        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                renderTable(this.value.trim());
            }, 300);
        });

        // Modal 動作控制
        const batchModal = document.getElementById('batchModal');
        document.getElementById('openBatchModal').addEventListener('click', () => batchModal.style.display = 'flex');
        document.getElementById('closeBatchModal').addEventListener('click', () => batchModal.style.display = 'none');

        // --- 刪除功能 API 串接 ---
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
            let successCount = 0;
            let failCount = 0;

            for (const id of selectedDeleteIds) {
                try {
                    const response = await fetch(DEV_API_URL, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            sso_token: SSO_TOKEN,
                            id: parseInt(id),
                            who_call: 'device_list'
                        })
                    });
                    const res = await response.json();
                    if (res.status === 'true') {
                        successCount++;
                    } else {
                        failCount++;
                    }
                } catch (err) {
                    failCount++;
                }
            }

            alert(`刪除完成！成功：${successCount} 筆，失敗：${failCount} 筆`);
            deleteModal.style.display = 'none';
            fetchDeviceList();
        });

        // --- 編輯功能 API 串接 (PATCH) ---
        const editModal = document.getElementById('editModal');
        document.getElementById('closeEditModal').addEventListener('click', () => editModal.style.display = 'none');
        document.getElementById('cancelEditBtn').addEventListener('click', () => editModal.style.display = 'none');

        document.getElementById('saveEditBtn').addEventListener('click', async function() {
            const id = document.getElementById('editTargetId').value;
            const deviceType = document.getElementById('editDeviceType').value;
            const deviceName = document.getElementById('editDeviceName').value;
            const assetNo = document.getElementById('editAssetNo').value;
            const sid = document.getElementById('editSid').value;

            if (!id) return;

            try {
                const response = await fetch(DEV_API_URL, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sso_token: SSO_TOKEN,
                        id: parseInt(id),
                        device_type: deviceType,
                        device_name: deviceName,
                        asset_no: assetNo,
                        sid: sid,
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
                console.error('儲存變更失敗:', err);
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