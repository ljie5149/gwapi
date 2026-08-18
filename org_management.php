<?php
    include_once('common/entry.php');
    global $g_is_online, $g_online_zhtw, $g_backend_title;
    $username = $_SESSION['accname'] ?? "";
    $userrole = $_SESSION['user_role'] ?? "";
    uiLocationPage();
    $cloud_url = ($g_is_online) ? "online_cloud.php" : "offline_cloud.php";

    // 權限檢查：非超級管理員 (superuser) 則阻擋並引導回主頁
    if ($userrole !== 'superuser') {
        echo "<script>alert('無權限存取此頁面！'); window.location.href = 'dashboard.php';</script>";
        exit;
    }

    $org_str = "";
    if ($userrole == "superuser") {
        $org_str = '<span class="separator">|</span>
                    <a href="org_management.php" style="font-weight: bold;">機構管理</a>';
    }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>機構管理 - <?= $g_backend_title; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
        }

        body {
            background-color: #e3e3e3;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 頂部導航列 */
        .navbar {
            background-color: #2b79a2;
            color: #ffffff;
            height: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 16px;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
        }

        .nav-links .separator {
            color: rgba(255, 255, 255, 0.6);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
        }

        .logout-btn {
            background-color: #6b8296;
            color: #ffffff;
            text-decoration: none;
            padding: 4px 14px;
            border-radius: 15px;
            font-size: 14px;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
        }

        .logout-btn:hover { background-color: #55697a; }

        /* 主區域 */
        .main-container {
            padding: 20px 40px;
            flex: 1;
        }

        .page-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        /* 篩選與工具列 */
        .filter-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-label {
            font-size: 20px;
            font-weight: bold;
            color: #222222;
        }

        .select-container select {
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 6px 40px 6px 15px;
            font-size: 18px;
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='gray'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            min-width: 220px;
        }

        .date-input-container {
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 6px 12px;
            display: flex;
            align-items: center;
        }

        .date-input-container input {
            border: none;
            outline: none;
            font-size: 18px;
            width: 230px;
            color: #333;
        }

        /* 搜尋列與新增刪除按鈕 */
        .search-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            height: 42px;
            border: 1px solid #cccccc;
            border-radius: 8px;
            padding: 0 15px;
            font-size: 18px;
            outline: none;
            background-color: #ffffff;
        }

        .btn-add {
            background-color: #fca934;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 8px 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-delete {
            background-color: #b82828;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 8px 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }

        /* 表格樣式 */
        .table-card {
            background-color: #f7f7f7;
            border-radius: 4px;
            border: 1px solid #d0d0d0;
            min-height: 480px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th {
            background-color: #cde4f7;
            padding: 12px 20px;
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .data-table td {
            padding: 14px 20px;
            font-size: 18px;
            color: #1a1a1a;
            border-bottom: 1px solid #eaeaea;
        }

        .checkbox-col {
            width: 60px;
            text-align: center;
        }

        .data-table input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-active {
            color: #43a047;
            font-weight: bold;
        }

        /* 動作按鈕 (停用/編輯) */
        .btn-action {
            border: none;
            color: #ffffff;
            padding: 6px 20px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-disable { background-color: #6c757d; }
        .btn-edit { background-color: #a36955; }

        /* 分頁 */
        .pagination {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            padding: 20px 40px;
            font-size: 15px;
            color: #555;
        }

        .page-link {
            color: #555;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .page-link.active {
            background-color: #333333;
            color: #ffffff;
            font-weight: bold;
        }

        .page-link.disabled { color: #aaa; cursor: default; }

        /* Modal 對話方塊通用樣式 */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.35);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-container {
            background-color: #ffffff;
            width: 520px;
            border-radius: 6px;
            padding: 35px 30px 45px 30px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .modal-close {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 32px;
            height: 32px;
            background-color: #636c74;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .modal-title {
            font-size: 32px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 25px;
        }

        .modal-desc {
            font-size: 18px;
            color: #222222;
            margin-bottom: 35px;
        }

        .modal-btn-group {
            display: flex;
            justify-content: center;
            gap: 25px;
        }

        /* 表單類型 Modal 樣式 */
        .modal-form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .modal-form-group label {
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .modal-form-group input,
        .modal-form-group select {
            width: 100%;
            height: 42px;
            padding: 0 12px;
            font-size: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
        }

        .btn-modal-cancel {
            background-color: #797979;
            color: #ffffff;
            border: none;
            padding: 12px 35px;
            border-radius: 12px;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .btn-modal-submit {
            background-color: #124b6e;
            color: #ffffff;
            border: none;
            padding: 12px 35px;
            border-radius: 12px;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .btn-modal-danger {
            background-color: #ef4c3c;
            color: #ffffff;
            border: none;
            padding: 12px 35px;
            border-radius: 12px;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <!-- 頂部導航列 -->
    <header class="navbar">
        <nav class="nav-links"><span><?= $g_online_zhtw ?></span>
            <a href="dashboard.php">資料管理</a>
            <span class="separator">|</span>
            <a href="device_list.php">設備序號清單</a>
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
        <h1 class="page-title">機構管理</h1>

        <!-- 篩選列 -->
        <div class="filter-row">
            <span class="filter-label">帳號權限</span>
            <div class="select-container">
                <select>
                    <option value="全部">全部</option>
                    <option value="superuser">superuser</option>
                    <option value="admin">admin</option>
                </select>
            </div>

            <span class="filter-label" style="margin-left: 15px;">加入日期</span>
            <div class="date-input-container">
                <input type="text" value="2026-08-01 - 2026-08-11">
                <span>📅</span>
            </div>
        </div>

        <!-- 搜尋列與操作按鈕 -->
        <div class="search-row">
            <span class="filter-label">搜尋</span>
            <input type="text" class="search-input" placeholder="搜尋名稱、帳號...">
            <button class="btn-add" id="openAddOrgModal">新增</button>
            <button class="btn-delete" id="openBatchDeleteModal">刪除</button>
        </div>

        <!-- 資料表格 -->
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
                        <th>停用</th>
                        <th>編輯</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" class="row-checkbox"></td>
                        <td>superuser</td>
                        <td>管理者</td>
                        <td>superuser</td>
                        <td class="status-active">啟用中</td>
                        <td>2026/8/1</td>
                        <td><button class="btn-action btn-disable">停用</button></td>
                        <td><button class="btn-action btn-edit">編輯</button></td>
                    </tr>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" class="row-checkbox"></td>
                        <td>admin12</td>
                        <td>A機構</td>
                        <td>admin</td>
                        <td class="status-active">啟用中</td>
                        <td>2026/8/1</td>
                        <td><button class="btn-action btn-disable">停用</button></td>
                        <td><button class="btn-action btn-edit">編輯</button></td>
                    </tr>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" class="row-checkbox"></td>
                        <td>admin23</td>
                        <td>B機構</td>
                        <td>admin</td>
                        <td class="status-active">啟用中</td>
                        <td>2026/8/1</td>
                        <td><button class="btn-action btn-disable">停用</button></td>
                        <td><button class="btn-action btn-edit">編輯</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- 分頁區塊 -->
    <footer class="pagination">
        <span class="page-link disabled">&larr; Previous</span>
        <a href="#" class="page-link active">1</a>
        <a href="#" class="page-link">2</a>
        <a href="#" class="page-link">3</a>
        <span>...</span>
        <a href="#" class="page-link">67</a>
        <a href="#" class="page-link">68</a>
        <a href="#" class="page-link">Next &rarr;</a>
    </footer>

    <!-- Modal 區塊 -->
    <!-- 1. 登出確認 Modal -->
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

    <!-- 2. 新增/編輯機構 Modal -->
    <div class="modal-overlay" id="orgModal">
        <div class="modal-container">
            <div class="modal-close" onclick="closeModal('orgModal')">X</div>
            <h2 class="modal-title" id="orgModalTitle">新增機構</h2>
            <form id="orgForm">
                <div class="modal-form-group">
                    <label>帳號</label>
                    <input type="text" id="orgAccount" placeholder="請輸入帳號" required>
                </div>
                <div class="modal-form-group">
                    <label>名稱</label>
                    <input type="text" id="orgName" placeholder="請輸入機構名稱" required>
                </div>
                <div class="modal-form-group">
                    <label>密碼</label>
                    <input type="password" id="orgPassword" placeholder="編輯時如不修改請留空">
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
        // 全選功能
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        // Modal 通用控制函式
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // 開啟登出 Modal
        document.getElementById('openLogoutModal').addEventListener('click', function() {
            openModal('logoutModal');
        });

        // 開啟新增機構 Modal
        document.getElementById('openAddOrgModal').addEventListener('click', function() {
            document.getElementById('orgModalTitle').innerText = '新增機構';
            document.getElementById('orgAccount').value = '';
            document.getElementById('orgName').value = '';
            document.getElementById('orgPassword').value = '';
            document.getElementById('orgRole').value = 'admin';
            openModal('orgModal');
        });

        // 開啟批量刪除 Modal
        document.getElementById('openBatchDeleteModal').addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('請先勾選要刪除的項目！');
                return;
            }
            openModal('deleteModal');
        });

        // 動態事件綁定：編輯按鈕
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.btn-edit');
            if (editBtn) {
                const tr = editBtn.closest('tr');
                const account = tr.cells[1].innerText;
                const name = tr.cells[2].innerText;
                const role = tr.cells[3].innerText;

                document.getElementById('orgModalTitle').innerText = '編輯機構';
                document.getElementById('orgAccount').value = account;
                document.getElementById('orgName').value = name;
                document.getElementById('orgPassword').value = '';
                document.getElementById('orgRole').value = role;
                openModal('orgModal');
            }
        });

        // 背景點擊通用關閉 Modal
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        });
    </script>
</body>
</html>