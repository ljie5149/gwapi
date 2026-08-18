<?php
    include_once('common/entry.php');
    global $g_is_online, $g_online_zhtw;
    $username = $_SESSION['accname'] ?? "";
    $userrole = $_SESSION['user_role'] ?? "";
    uiLocationPage();
    $cloud_url = ($g_is_online) ? "online_cloud.php" : "offline_cloud.php";
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
    <title>設備序號清單 - 量測後台管理系統</title>
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
            cursor: pointer;
            border: none;
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

        /* 搜尋與操作按鈕列 */
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

        /* 按鈕樣式 */
        .btn-batch-add {
            background-color: #124b6e;
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

        /* 資料表格卡片 */
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

        /* 編輯按鈕 */
        .btn-edit {
            background-color: #797979;
            color: #ffffff;
            border: none;
            padding: 6px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-edit:hover { background-color: #636363; }

        /* 分頁區塊 */
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

        /* 批次新增 Modal 按鈕樣式 */
        .btn-modal-download {
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

        .btn-modal-upload {
            background-color: #ef4c3c;
            color: #ffffff;
            border: none;
            padding: 12px 35px;
            border-radius: 12px;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        /* 登出 Modal 按鈕樣式 */
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

        .btn-modal-confirm-logout {
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
            <a href="device_list.php" style="font-weight: bold;">設備序號清單</a>
            <span class="separator">|</span>
            <a href="<?= $cloud_url; ?>">離線/雲端管理</a>
            <?= $org_str; ?>
        </nav>
        <div class="user-info">
            <span>登入者：<?php echo htmlspecialchars($username); ?></span>
            <!-- 修改點：對齊 dashboard.php 的按鈕型態與 id -->
            <button type="button" id="openLogoutModal" class="logout-btn">登出</button>
        </div>
    </header>

    <main class="main-container">
        <h1 class="page-title">設備序號清單</h1>

        <!-- 第一排：篩選列 -->
        <div class="filter-row">
            <span class="filter-label">量測設備</span>
            <div class="select-container">
                <select>
                    <option value="全部">全部</option>
                    <option value="血壓計">血壓計</option>
                    <option value="血糖機">血糖機</option>
                    <option value="體溫計">體溫計</option>
                </select>
            </div>

            <span class="filter-label" style="margin-left: 15px;">更新日期</span>
            <div class="date-input-container">
                <input type="text" value="2026-08-01 - 2026-08-11">
                <span>📅</span>
            </div>
        </div>

        <!-- 第二排：搜尋與動作按鈕 -->
        <div class="search-row">
            <span class="filter-label">搜尋</span>
            <input type="text" class="search-input" placeholder="搜尋序號...">
            <button class="btn-batch-add" id="openBatchModal">批次新增</button>
            <button class="btn-delete">刪除</button>
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
                <tbody>
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" class="row-checkbox"></td>
                        <td>血壓計</td>
                        <td>血壓計01</td>
                        <td>123456789</td>
                        <td>2026/8/1</td>
                        <td>2026/8/1</td>
                        <td><button class="btn-edit">編輯</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- 頁尾分頁控制 -->
    <footer class="pagination">
        <span class="page-link disabled">&larr; Previous</span>
        <a href="#" class="page-link active">1</a>
        <a href="#" class="page-link">2</a>
        <a href="#" class="page-link">3</a>
        <span style="user-select: none;">...</span>
        <a href="#" class="page-link">67</a>
        <a href="#" class="page-link">68</a>
        <a href="#" class="page-link">Next &rarr;</a>
    </footer>

    <!-- 批次新增 Modal 對話方塊 -->
    <div class="modal-overlay" id="batchModal">
        <div class="modal-container">
            <div class="modal-close" id="closeBatchModal">X</div>
            <h2 class="modal-title">批次新增</h2>
            <p class="modal-desc">請下載範例檔後依格式填入後上傳</p>
            <div class="modal-btn-group">
                <button class="btn-modal-download">範例檔下載</button>
                <button class="btn-modal-upload">回填上傳</button>
            </div>
        </div>
    </div>

    <!-- 登出確認 Modal 對話方塊 -->
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

    <script>
        // 表格全選 / 全不選功能
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        // --- 批次新增 Modal 控制邏輯 ---
        const batchModal = document.getElementById('batchModal');
        const openBatchModalBtn = document.getElementById('openBatchModal');
        const closeBatchModalBtn = document.getElementById('closeBatchModal');

        openBatchModalBtn.addEventListener('click', function() {
            batchModal.style.display = 'flex';
        });

        closeBatchModalBtn.addEventListener('click', function() {
            batchModal.style.display = 'none';
        });

        // --- 登出 Modal 控制邏輯 ---
        const logoutModal = document.getElementById('logoutModal');
        const openLogoutModalBtn = document.getElementById('openLogoutModal');
        const closeLogoutModalBtn = document.getElementById('closeLogoutModal');
        const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');

        // 開啟登出 Modal
        openLogoutModalBtn.addEventListener('click', function() {
            logoutModal.style.display = 'flex';
        });

        // 關閉登出 Modal (按右上角 X)
        closeLogoutModalBtn.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });

        // 關閉登出 Modal (按取消按鈕)
        cancelLogoutBtn.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });

        // 點擊背景空白處通用關閉邏輯
        window.addEventListener('click', function(e) {
            if (e.target === batchModal) {
                batchModal.style.display = 'none';
            }
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>