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
                    <a href="org_management.php">機構管理</a>';
    }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>離線/雲端管理 - 量測後台管理系統</title>
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
            margin-bottom: 25px;
        }

        /* 第一區塊：主機與同步控制 */
        .control-row {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .control-label {
            width: 140px;
        }

        .select-container select {
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 6px 40px 6px 15px;
            font-size: 20px;
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='gray'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            min-width: 250px;
        }

        .info-blue {
            color: #2d70b3;
            margin-left: 25px;
            font-size: 20px;
            font-weight: normal;
        }

        .btn-sync {
            background-color: #fca934;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 8px 45px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-add-gw {
            background-color: #724921;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 8px 35px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-left: auto;
        }

        .btn-add-hms {
            background-color: #1b3866;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 8px 35px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-left: auto;
        }

        /* 資料表格卡片 */
        .table-card {
            background-color: #ffffff;
            border-radius: 4px;
            border: 1px solid #d0d0d0;
            min-height: 180px;
            margin-bottom: 15px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .data-table th {
            background-color: #cde4f7;
            padding: 12px;
            font-size: 18px;
            font-weight: bold;
            color: #2b79a2;
            width: 16.66%;
        }

        .data-table td {
            padding: 14px;
            font-size: 18px;
            color: #1a1a1a;
            border-bottom: 1px solid #eaeaea;
            vertical-align: middle;
        }

        .data-table input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-online {
            color: #43a047;
            font-weight: bold;
        }

        /* 圖示按鈕 (編輯 / 刪除) */
        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #333333;
            transition: color 0.2s;
        }

        .icon-btn:hover { color: #2b79a2; }

        /* 刷新連線狀態按鈕 */
        .btn-refresh {
            background-color: #e8e8e8;
            color: #2b79a2;
            border: 1px solid #c0c0c0;
            border-radius: 8px;
            padding: 8px 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 35px;
        }

        .btn-refresh:hover { background-color: #dcdcdc; }

        /* HMS 主機連線標題列 */
        .hms-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .hms-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
        }

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
            <a href="<?= $cloud_url; ?>" style="font-weight: bold;">離線/雲端管理</a>
            <?= $org_str; ?>
        </nav>
        <div class="user-info">
            <span>登入者：<?php echo htmlspecialchars($username); ?></span>
            <button type="button" id="openLogoutModal" class="logout-btn">登出</button>
        </div>
    </header>

    <main class="main-container">
        <h1 class="page-title">離線/雲端管理</h1>

        <!-- 主機設置區塊 -->
        <div class="control-row">
            <span class="control-label">主機設置：</span>
            <div class="select-container">
                <select id="hostTypeSelect">
                    <option value="雲端主機" selected>雲端主機</option>
                    <option value="離線主機">離線主機</option>
                </select>
            </div>
            <span class="info-blue">主機IP：123.456.789.1</span>
        </div>

        <!-- 離線模式專屬：資料同步與 GW 清單 -->
        <div id="offlineSection" style="display: none;">
            <div class="control-row">
                <span class="control-label">資料同步：</span>
                <button class="btn-sync">同步資料</button>
                <span class="info-blue">2026/8/10 19:00, 123.123.123.1, 同步成功</span>
                <button class="btn-add-gw" id="openGwModal">新增GW</button>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>選擇</th>
                            <th>狀態</th>
                            <th>IP</th>
                            <th>連線方式</th>
                            <th>編輯</th>
                            <th>刪除</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" class="row-checkbox"></td>
                            <td class="status-online">連線</td>
                            <td>123.123.123.1</td>
                            <td>雲端主機</td>
                            <td><button class="icon-btn btn-edit" title="編輯">📝</button></td>
                            <td><button class="icon-btn btn-delete" title="刪除">🗑️</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button class="btn-refresh">刷新連線狀態</button>
        </div>

        <!-- HMS主機連線區塊 -->
        <div class="hms-header">
            <span class="hms-title">HMS主機連線：</span>
            <button class="btn-add-hms" id="openHmsModal">新增主機</button>
        </div>

        <!-- HMS主機清單 -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>選擇</th>
                        <th>狀態</th>
                        <th>IP</th>
                        <th>連線方式</th>
                        <th>編輯</th>
                        <th>刪除</th>
                    </tr>
                </thead>
                <tbody id="hmsTableBody">
                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td class="status-online">連線</td>
                        <td>123.123.123.1</td>
                        <td>雲端主機</td>
                        <td><button class="icon-btn btn-edit" title="編輯">📝</button></td>
                        <td><button class="icon-btn btn-delete" title="刪除">🗑️</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button class="btn-refresh">刷新連線狀態</button>
    </main>

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

    <!-- 2. 新增/編輯 GW/HMS 主機 Modal -->
    <div class="modal-overlay" id="hostModal">
        <div class="modal-container">
            <div class="modal-close" onclick="closeModal('hostModal')">X</div>
            <h2 class="modal-title" id="hostModalTitle">新增主機</h2>
            <form id="hostForm">
                <div class="modal-form-group">
                    <label>IP 位址</label>
                    <input type="text" id="hostIp" placeholder="請輸入 IP (例如: 192.168.1.1)" required>
                </div>
                <div class="modal-form-group">
                    <label>連線方式</label>
                    <select id="hostType">
                        <option value="雲端主機">雲端主機</option>
                        <option value="離線主機">離線主機</option>
                        <option value="外檢主機">外檢主機</option>
                    </select>
                </div>
                <div class="modal-btn-group" style="margin-top: 30px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('hostModal')">取消</button>
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
            <p class="modal-desc">確定要刪除此筆連線設定嗎？</p>
            <div class="modal-btn-group">
                <button class="btn-modal-cancel" onclick="closeModal('deleteModal')">取消</button>
                <button class="btn-modal-danger" id="confirmDeleteBtn">確定刪除</button>
            </div>
        </div>
    </div>

    <script>
        const hostTypeSelect = document.getElementById('hostTypeSelect');
        const offlineSection = document.getElementById('offlineSection');
        const hmsTableBody = document.getElementById('hmsTableBody');

        // Modal 控制通用函式
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // 監聽主機類型選單切換
        hostTypeSelect.addEventListener('change', function() {
            if (this.value === '雲端主機') {
                offlineSection.style.display = 'none';
                hmsTableBody.innerHTML = `
                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td class="status-online">連線</td>
                        <td>123.123.123.1</td>
                        <td>雲端主機</td>
                        <td><button class="icon-btn btn-edit" title="編輯">📝</button></td>
                        <td><button class="icon-btn btn-delete" title="刪除">🗑️</button></td>
                    </tr>
                `;
            } else {
                offlineSection.style.display = 'block';
                hmsTableBody.innerHTML = `
                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td class="status-online">連線</td>
                        <td>123.123.123.1</td>
                        <td>雲端主機</td>
                        <td><button class="icon-btn btn-edit" title="編輯">📝</button></td>
                        <td><button class="icon-btn btn-delete" title="刪除">🗑️</button></td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td class="status-online">連線</td>
                        <td>123.123.123.1</td>
                        <td>外檢主機</td>
                        <td><button class="icon-btn btn-edit" title="編輯">📝</button></td>
                        <td><button class="icon-btn btn-delete" title="刪除">🗑️</button></td>
                    </tr>
                `;
            }
        });

        // 開啟登出 Modal
        document.getElementById('openLogoutModal').addEventListener('click', function() {
            openModal('logoutModal');
        });

        // 開啟新增 GW Modal
        document.getElementById('openGwModal').addEventListener('click', function() {
            document.getElementById('hostModalTitle').innerText = '新增 GW';
            document.getElementById('hostIp').value = '';
            openModal('hostModal');
        });

        // 開啟新增 HMS Modal
        document.getElementById('openHmsModal').addEventListener('click', function() {
            document.getElementById('hostModalTitle').innerText = '新增 HMS 主機';
            document.getElementById('hostIp').value = '';
            openModal('hostModal');
        });

        // 全域委派事件：編輯與刪除按鈕觸發 Modal（支援動態渲染的 HTML）
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.btn-edit');
            if (editBtn) {
                const tr = editBtn.closest('tr');
                const ip = tr.cells[2].innerText;
                const type = tr.cells[3].innerText;

                document.getElementById('hostModalTitle').innerText = '編輯主機設定';
                document.getElementById('hostIp').value = ip;
                document.getElementById('hostType').value = type;
                openModal('hostModal');
            }

            const deleteBtn = e.target.closest('.btn-delete');
            if (deleteBtn) {
                openModal('deleteModal');
            }
        });

        // 點擊背景空白處通用關閉 Modal
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        });
    </script>
</body>
</html>