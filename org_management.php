<?php
    include_once('common/entry.php');
    global $g_root_url, $g_is_online, $g_online_zhtw, $g_backend_title, $g_supperuser_all;

    $username = $_SESSION['accname'] ?? "";
    $userrole = $_SESSION['user_role'] ?? "";
    $sso_token = $_SESSION['sso_token'] ?? "";

    uiLocationPage(true);
    $cloud_url = "online_cloud.php";

    // 權限檢查：非超級管理員 (superuser) 則阻擋並引導回主頁
    if ($userrole !== 'superuser') {
        echo "<script>alert('無權限存取此頁面！'); window.location.href = 'dashboard.php';</script>";
        exit;
    }

    $displayMenu = ($g_supperuser_all) ? '' : 'style = "display:none;"';
    $org_str = "";
    if ($userrole == "superuser") {
        if ($g_supperuser_all) {
            $org_str = '<span class="separator">|</span>
                        <a href="org_management.php" style="font-weight: bold;">機構管理</a>';
        } else {
            $org_str = '<a href="org_management.php" style="font-weight: bold;">機構管理</a>';
        }
    }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>機構管理 - <?= htmlspecialchars($g_backend_title); ?></title>
    <link href="./css/org_management.css" rel="stylesheet">
</head>
<body>
    <!-- 頂部導航列 -->
    <header class="navbar">
        <nav class="nav-links">
            <span><?= htmlspecialchars($g_online_zhtw) ?></span>
            <a href="dashboard.php" <?= $displayMenu ?>>資料管理</a>
            <span class="separator" <?= $displayMenu ?>>|</span>
            <a href="device_list.php" <?= $displayMenu ?>>設備序號清單</a>
            <span class="separator" <?= $displayMenu ?>>|</span>
            <a href="<?= htmlspecialchars($cloud_url); ?>" <?= $displayMenu ?>>離線/雲端管理</a>
            <?= $org_str; ?>
        </nav>
        <div class="user-info">
            <span>登入者：<?php echo htmlspecialchars($username); ?></span>
            <button type="button" id="openLogoutModal" class="logout-btn">登出</button>
        </div>
    </header>

    <main class="main-container">
        <h1 class="page-title">機構管理</h1>

        <!-- 篩選列 -->
        <div class="filter-row">
            <span class="filter-label">帳號權限</span>
            <div class="select-container">
                <select id="roleFilter">
                    <option value="全部">全部</option>
                    <option value="superuser">superuser</option>
                    <option value="admin">admin</option>
                </select>
            </div>
        </div>

        <!-- 搜尋列與操作按鈕 -->
        <div class="search-row">
            <span class="filter-label">搜尋</span>
            <input type="text" id="searchInput" class="search-input" placeholder="搜尋名稱、帳號...">
            <button class="btn-add" id="openAddOrgModal">新增</button>
            <button class="btn-delete" id="openBatchDeleteModal">刪除</button>
        </div>

        <!-- 資料表格與分頁控制 -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="checkbox-col"><input type="checkbox" id="selectAll"></th>
                        <th>帳號</th>
                        <th>名稱</th>
                        <th>權限</th>
                        <th>狀態</th>
                        <th>加入日期</th>
                        <th>停用/啟用</th>
                        <th>編輯</th>
                    </tr>
                </thead>
                <tbody id="memberTableBody">
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">資料載入中...</td></tr>
                </tbody>
            </table>

            <!-- 分頁區塊 -->
            <div class="pagination-container">
                <div class="pagination-info" id="paginationInfo">顯示第 0 到 0 筆，共 0 筆</div>
                <div class="pagination-buttons" id="paginationButtons">
                    <!-- 分頁按鈕動態產生 -->
                </div>
            </div>
        </div>
    </main>

    <!-- Modal 區塊 -->
    <!-- 1. 登出 Modal -->
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

    <!-- 2. 新增/編輯 Modal -->
    <div class="modal-overlay" id="orgModal">
        <div class="modal-container">
            <div class="modal-close" onclick="closeModal('orgModal')">X</div>
            <h2 class="modal-title" id="orgModalTitle">新增機構</h2>
            <form id="orgForm">
                <input type="hidden" id="orgEditId" value="0">
                <div class="modal-form-group">
                    <label>帳號</label>
                    <input type="text" id="orgAccount" placeholder="請輸入帳號" required>
                </div>
                <div class="modal-form-group">
                    <label>名稱</label>
                    <input type="text" id="orgName" placeholder="請輸入名稱" required>
                </div>
                <div class="modal-form-group">
                    <label>密碼</label>
                    <input type="password" id="orgPassword" placeholder="編輯時如不修改請留空">
                </div>
                <div class="modal-form-group">
                    <label>機構/設施</label>
                    <select id="orgFacilityId" required>
                        <option value="">載入中...</option>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>帳號權限</label>
                    <select id="orgRole">
                        <option value="admin">admin</option>
                        <option value="superuser">superuser</option>
                    </select>
                </div>
                <div class="modal-btn-group" style="margin-top: 30px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('orgModal')">取消</button>
                    <button type="submit" class="btn-modal-submit">儲存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. 刪除確認 Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-container">
            <div class="modal-close" onclick="closeModal('deleteModal')">X</div>
            <h2 class="modal-title">刪除確認</h2>
            <p class="modal-desc">確定要刪除選取的機構嗎？</p>
            <div class="modal-btn-group">
                <button class="btn-modal-cancel" onclick="closeModal('deleteModal')">取消</button>
                <button class="btn-modal-danger" id="confirmDeleteBtn">確定刪除</button>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '<?= $g_root_url ?>api/JTG_member.php';
        const FACILITY_API_URL = '<?= $g_root_url ?>api/JTG_facility.php';
        const SSO_TOKEN = '<?= htmlspecialchars($sso_token); ?>';
        const ROLE = '<?= htmlspecialchars($userrole); ?>';

        // 全域變數
        let allDataList = [];        // 儲存帳號 member 資料
        let facilityListCache = [];  // 快取機構 facility 資料
        let currentPage = 1;         // 當前頁碼
        const pageSize = 10;         // 一頁 10 筆

        // 全選按鈕控制
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        // Modal 控制
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        document.getElementById('openLogoutModal').addEventListener('click', () => openModal('logoutModal'));

        // HTML 轉義防護 (XSS)
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        // =========================================================
        // 0. 讀取機構選單資料 (JTG_facility API)
        // =========================================================
        async function loadFacilityOptions(selectedFacilityId = null) {
            const selectElem = document.getElementById('orgFacilityId');

            // 若快取為空則透過 API 取得
            if (facilityListCache.length === 0) {
                try {
                    const params = new URLSearchParams();
                    params.append('sso_token', SSO_TOKEN);
                    params.append('get_all', '1');

                    const response = await fetch(`${FACILITY_API_URL}?${params.toString()}`, { method: 'GET' });
                    const res = await response.json();

                    if (res.status === 'true' && res.data && Array.isArray(res.data.data)) {
                        facilityListCache = res.data.data;
                    } else {
                        facilityListCache = [];
                    }
                } catch (err) {
                    console.error('載入機構選項失敗:', err);
                    selectElem.innerHTML = '<option value="">載入失敗</option>';
                    return;
                }
            }

            if (facilityListCache.length === 0) {
                selectElem.innerHTML = '<option value="">無可用機構</option>';
                return;
            }

            // 動態產生 <option> 選項
            selectElem.innerHTML = facilityListCache.map(fac => `
                <option value="${fac.id}">
                    [${escapeHtml(fac.facility_no)}] ${escapeHtml(fac.facility_name)}
                </option>
            `).join('');

            // 設定預設選取項目
            if (selectedFacilityId) {
                selectElem.value = selectedFacilityId;
            }
        }

        // =========================================================
        // 1. 讀取與處理成員資料 (GET JTG_member)
        // =========================================================
        async function loadOrgMembers() {
            const roleFilter = document.getElementById('roleFilter').value;
            const searchKeyword = document.getElementById('searchInput').value.trim();

            const params = new URLSearchParams();
            params.append('sso_token', SSO_TOKEN);
            params.append('role', ROLE);

            if (roleFilter !== '全部') params.append('filter_role', roleFilter);
            if (searchKeyword !== '') params.append('filter_name_account', searchKeyword);

            try {
                const response = await fetch(`${API_URL}?${params.toString()}`, { method: 'GET' });
                const res = await response.json();

                if (res.status === 'true' && Array.isArray(res.data)) {
                    allDataList = res.data;
                } else {
                    allDataList = [];
                }

                // 取回資料後跳至指定頁面（若頁數過大則修剪）
                const totalPages = Math.ceil(allDataList.length / pageSize) || 1;
                if (currentPage > totalPages) currentPage = totalPages;

                renderTableAndPagination();
            } catch (err) {
                console.error('讀取失敗:', err);
            }
        }

        // 渲染表格與分頁條
        function renderTableAndPagination() {
            const tbody = document.getElementById('memberTableBody');
            tbody.innerHTML = '';
            document.getElementById('selectAll').checked = false;

            const totalRecords = allDataList.length;

            if (totalRecords === 0) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 20px;">查無機構資料</td></tr>`;
                document.getElementById('paginationInfo').innerText = '顯示第 0 到 0 筆，共 0 筆';
                document.getElementById('paginationButtons').innerHTML = '';
                return;
            }

            // 計算目前頁數顯示範圍 (10 筆/頁)
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, totalRecords);
            const pageData = allDataList.slice(startIndex, endIndex);

            // 渲染列表內容
            pageData.forEach(item => {
                const tr = document.createElement('tr');
                const isEnable = item.status == 1;
                const createdDate = item.created_at ? item.created_at.split(' ')[0] : '-';

                tr.innerHTML = `
                    <td class="checkbox-col"><input type="checkbox" class="row-checkbox" value="${item.id}"></td>
                    <td>${escapeHtml(item.account)}</td>
                    <td>${escapeHtml(item.member_name)}</td>
                    <td>${escapeHtml(item.role)}</td>
                    <td><span class="${isEnable ? 'status-active' : 'status-disabled'}">${isEnable ? '啟用中' : '已停用'}</span></td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="btn-action ${isEnable ? 'btn-disable' : 'btn-enable'}" 
                                onclick="toggleStatus(${item.id}, ${item.status})">
                            ${isEnable ? '停用' : '啟用'}
                        </button>
                    </td>
                    <td>
                        <button class="btn-action btn-edit" onclick='openEditModal(${JSON.stringify(item)})'>編輯</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // 更新筆數資訊
            document.getElementById('paginationInfo').innerText = `顯示第 ${startIndex + 1} 到 ${endIndex} 筆，共 ${totalRecords} 筆`;

            // 渲染分頁按鈕 UI
            renderPaginationControls(totalRecords);
        }

        // 動態生成分頁控制按鈕
        function renderPaginationControls(totalRecords) {
            const totalPages = Math.ceil(totalRecords / pageSize);
            const container = document.getElementById('paginationButtons');
            container.innerHTML = '';

            if (totalPages <= 1) return;

            container.innerHTML += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(1)">«</button>`;
            container.innerHTML += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">‹</button>`;

            for (let i = 1; i <= totalPages; i++) {
                const isActive = i === currentPage ? 'active' : '';
                container.innerHTML += `<button class="page-btn ${isActive}" onclick="changePage(${i})">${i}</button>`;
            }

            container.innerHTML += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">›</button>`;
            container.innerHTML += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${totalPages})">»</button>`;
        }

        // 切換頁碼
        function changePage(page) {
            currentPage = page;
            renderTableAndPagination();
        }

        // 搜尋與篩選改變時自動重置回第 1 頁
        document.getElementById('roleFilter').addEventListener('change', () => { currentPage = 1; loadOrgMembers(); });
        document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; loadOrgMembers(); });

        // =========================================================
        // 2. 新增與編輯機構 (POST / PATCH)
        // =========================================================
        document.getElementById('openAddOrgModal').addEventListener('click', async function() {
            document.getElementById('orgModalTitle').innerText = '新增機構';
            document.getElementById('orgEditId').value = '0';
            document.getElementById('orgAccount').value = '';
            document.getElementById('orgAccount').readOnly = false;
            document.getElementById('orgName').value = '';
            document.getElementById('orgPassword').value = '';
            document.getElementById('orgPassword').required = true;
            document.getElementById('orgRole').value = 'admin';

            // 載入機構選單選項
            await loadFacilityOptions();

            openModal('orgModal');
        });

        async function openEditModal(item) {
            document.getElementById('orgModalTitle').innerText = '編輯機構';
            document.getElementById('orgEditId').value = item.id;
            document.getElementById('orgAccount').value = item.account;
            document.getElementById('orgAccount').readOnly = true;
            document.getElementById('orgName').value = item.member_name;
            document.getElementById('orgPassword').value = '';
            document.getElementById('orgPassword').required = false;
            document.getElementById('orgRole').value = item.role;

            // 載入機構選單選項並自動對應 selected
            await loadFacilityOptions(item.facility_id);

            openModal('orgModal');
        }

        document.getElementById('orgForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const editId = parseInt(document.getElementById('orgEditId').value);
            const account = document.getElementById('orgAccount').value.trim();
            const memberName = document.getElementById('orgName').value.trim();
            const password = document.getElementById('orgPassword').value;
            const facilityId = parseInt(document.getElementById('orgFacilityId').value);
            const role = document.getElementById('orgRole').value;

            if (!facilityId) {
                alert('請選擇所屬機構！');
                return;
            }

            let method = 'POST';
            let payload = {
                sso_token: SSO_TOKEN,
                sid: account,
                account: account,
                member_name: memberName,
                facility_id: facilityId,
                role: role,
                who_call: 'web_management'
            };

            if (editId > 0) {
                method = 'PATCH';
                payload.id = editId;
                if (password) payload.password = password;
            } else {
                payload.password = password;
            }

            try {
                const response = await fetch(API_URL, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                if (res.status === 'true') {
                    closeModal('orgModal');
                    loadOrgMembers();
                } else {
                    alert('操作失敗: ' + (res.message || '未知錯誤'));
                }
            } catch (err) {
                console.error('儲存失敗:', err);
            }
        });

        // =========================================================
        // 3. 停用 / 啟用機構 (PATCH)
        // =========================================================
        async function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus == 1 ? 0 : 1;
            try {
                const response = await fetch(API_URL, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sso_token: SSO_TOKEN,
                        id: id,
                        status: newStatus,
                        who_call: 'web_management'
                    })
                });
                const res = await response.json();
                if (res.status === 'true') {
                    loadOrgMembers();
                } else {
                    alert('更新狀態失敗: ' + res.message);
                }
            } catch (err) {
                console.error('更新狀態錯誤:', err);
            }
        }

        // =========================================================
        // 4. 刪除機構 (DELETE)
        // =========================================================
        document.getElementById('openBatchDeleteModal').addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('請先勾選要刪除的項目！');
                return;
            }
            openModal('deleteModal');
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            for (let cb of checkedBoxes) {
                try {
                    await fetch(API_URL, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            sso_token: SSO_TOKEN,
                            id: parseInt(cb.value),
                            who_call: 'web_management'
                        })
                    });
                } catch (err) {
                    console.error(`刪除 ID ${cb.value} 失敗:`, err);
                }
            }
            closeModal('deleteModal');
            loadOrgMembers();
        });

        // 點擊 Modal 外部區域關閉
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        });

        // 初始化載入
        document.addEventListener('DOMContentLoaded', loadOrgMembers);
    </script>
</body>
</html>