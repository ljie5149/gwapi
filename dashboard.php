<?php
    include_once('common/entry.php');
    global $g_is_online, $g_online_zhtw, $g_backend_title;
    $username = $_SESSION['accname'] ?? "";
    $userrole = $_SESSION['user_role'] ?? "";
    uiLocationPage();
    $cloud_url = ($g_is_online) ? "online_cloud.php" : "offline_cloud.php";
    $org_str = "";
    if ($userrole == "superuser") {
        $org_str = '<span class="separator">|</span>
                    <a href="org_management.php">機構管理</a>';
    }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料管理 - <?= $g_backend_title; ?></title>
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

        .logout-btn:hover {
            background-color: #55697a;
        }

        /* 主區域 */
        .main-container {
            padding: 20px 40px;
            flex: 1;
        }

        .page-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        /* 切換頁籤 */
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 8px 24px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background-color: #797979;
            color: #ffffff;
        }

        .tab-btn.inactive {
            background-color: #d1d1d1;
            color: #ffffff;
        }

        /* 篩選工具列 */
        .filter-bar {
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
            min-width: 200px;
        }

        .action-btn {
            padding: 8px 30px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            border: none;
            cursor: pointer;
        }

        .btn-download { background-color: #124b6e; }
        .btn-delete { background-color: #b82828; }

        /* 搜尋列 */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
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

        /* 資料表格 */
        .table-card {
            background-color: #f7f7f7;
            border-radius: 4px;
            border: 1px solid #d0d0d0;
            padding-bottom: 120px;
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

        .info-text {
            color: #b82828;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-top: 60px;
        }

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

        .page-link.disabled {
            color: #aaa;
            cursor: default;
        }

        /* Modal 對話方塊樣式 */
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
            <a href="dashboard.php" style="font-weight: bold;">資料管理</a>
            <span class="separator">|</span>
            <a href="device_list.php">設備序號清單</a>
            <span class="separator">|</span>
            <a href="<?= $cloud_url; ?>">離線/雲端管理</a>
            <?= $org_str; ?>
        </nav>
        <div class="user-info">
            <span>登入者：<?php echo htmlspecialchars($username); ?></span>
            <!-- 修改點：觸發登出 Modal 對話框 -->
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
                <input type="text" value="2026-08-01 - 2026-08-11">
                <span>📅</span>
            </div>

            <span class="filter-label" style="margin-left: 15px;">量測設備</span>
            <div class="select-container">
                <select>
                    <option value="血壓計">血壓計</option>
                    <option value="血糖機">血糖機</option>
                    <option value="體溫計">體溫計</option>
                </select>
            </div>

            <button class="action-btn btn-download">下載</button>
            <button class="action-btn btn-delete">刪除</button>
        </div>

        <!-- 搜尋列（共通格式時顯示） -->
        <div class="search-bar" id="searchRow" style="display: none;">
            <span class="filter-label">搜尋</span>
            <input type="text" class="search-input" placeholder="">
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
                    <tbody>
                        <tr>
                            <td class="checkbox-col"><input type="checkbox" class="row-checkbox"></td>
                            <td>2026-08-01</td>
                            <td>血壓計</td>
                            <td>rawdata_20260801.txt</td>
                            <td>1.2MB</td>
                        </tr>
                        <tr>
                            <td class="checkbox-col"><input type="checkbox" class="row-checkbox"></td>
                            <td>2026-08-02</td>
                            <td>血壓計</td>
                            <td>rawdata_20260802.txt</td>
                            <td>2.3MB</td>
                        </tr>
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
                    <tbody>
                        <tr>
                            <td class="checkbox-col"><input type="checkbox" class="row-checkbox"></td>
                            <td>A123456789</td>
                            <td>王小明</td>
                            <td>1111111</td>
                            <td>B02607Z90001</td>
                            <td>1</td>
                            <td>120</td>
                            <td>80</td>
                            <td>2026/8/1 13:00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- 分頁區塊 -->
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
        const btnRaw = document.getElementById('btnRaw');
        const btnCommon = document.getElementById('btnCommon');
        const searchRow = document.getElementById('searchRow');
        const rawTable = document.getElementById('rawTable');
        const commonTable = document.getElementById('commonTable');

        // 頁籤切換：原始資料
        btnRaw.addEventListener('click', function() {
            btnRaw.className = 'tab-btn active';
            btnCommon.className = 'tab-btn inactive';
            searchRow.style.display = 'none';
            rawTable.style.display = 'block';
            commonTable.style.display = 'none';
        });

        // 頁籤切換：共通格式
        btnCommon.addEventListener('click', function() {
            btnCommon.className = 'tab-btn active';
            btnRaw.className = 'tab-btn inactive';
            searchRow.style.display = 'flex';
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

        // 點擊背景空白處關閉 Modal
        window.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>