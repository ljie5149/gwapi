<?php
	include_once('./common/entry.php');
    global $g_root_url, $g_online_zhtw, $g_backend_title, $g_supperuser_all;
    $showAll = ($g_supperuser_all) ? "1" : "0";
	getGoldenKey();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $g_backend_title; ?> - 登入</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 420px; /* 原 720px -> 縮小至標準寬度 */
            padding: 20px;
        }

        .logo-box {
            text-align: center;
            margin-bottom: 16px;
        }

        .logo-box img {
            max-width: 280px; /* 原 504px -> 縮小 Logo */
            width: 100%;
            height: auto;
        }

        .system-title {
            font-size: 24px; /* 原 46px -> 調整為適中標題 */
            font-weight: bold;
            color: #000000;
            margin-bottom: 24px;
            letter-spacing: 1px;
            text-align: center;
        }

        .card {
            background-color: #ffffff;
            border-radius: 16px; /* 原 42px -> 圓角收斂 */
            padding: 32px 28px; /* 原 60px 54px -> 縮減留白 */
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* 增加微陰影更具層次感 */
        }

        .input-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .input-group label {
            font-size: 16px; /* 原 31px */
            font-weight: bold;
            color: #2b79a2;
            width: 60px; /* 原 110px */
            text-align: left;
        }

        .input-group input {
            flex: 1;
            height: 44px; /* 原 62px -> 縮減高度 */
            border: 1px solid #c8c8c8;
            border-radius: 8px;
            padding: 0 14px;
            font-size: 16px; /* 原 26px */
            outline: none;
            background-color: #ffffff;
        }

        .input-group input:focus {
            border-color: #2b79a2;
            box-shadow: 0 0 6px rgba(43, 121, 162, 0.3);
        }

        .submit-btn {
            background-color: #fca934;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px 0; /* 原 17px */
            font-size: 18px; /* 原 34px */
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            width: 100%; /* 原 55% -> 改為滿寬更容易點擊 */
            align-self: center;
            letter-spacing: 2px;
            transition: background-color 0.2s ease;
        }

        .submit-btn:hover {
            background-color: #e59728;
        }

        .error-msg {
            color: #d9534f;
            text-align: center;
            font-size: 14px; /* 原 24px */
            font-weight: bold;
            display: none;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- Logo 圖片 -->
        <div class="logo-box">
            <img src="./images/logo.png" alt="敏盛綜合醫院 Min-Sheng General Hospital">
        </div>

        <div class="system-title">量測後台管理系統 <span style="font-size:16px; color:gray;"><?= $g_online_zhtw ?></span></div>

        <!-- 登入表單 -->
        <div class="card">
            <!-- 錯誤提示區域 -->
            <div id="errorMsg" class="error-msg"></div>

            <form id="loginForm">
                <div class="input-group" style="margin-bottom: 20px;">
                    <label for="username">帳號</label>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label for="password">密碼</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="submit-btn">送出</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorMsgBox = document.getElementById('errorMsg');

            // 清除之前的錯誤訊息
            errorMsgBox.style.display = 'none';
            errorMsgBox.textContent = '';

            if (!username || !password) {
                errorMsgBox.textContent = '請輸入帳號與密碼！';
                errorMsgBox.style.display = 'block';
                return;
            }

            try {
				var api_url = '<?= $g_root_url ?>' + 'middleware/api4login.php';
                const response = await fetch(api_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password
                    })
                });

                const result = await response.json();

                var show_all = '<?php echo $showAll ?>';
                if (result.status === 'true') {
                    if (result.role === 'superuser') {
                        if (show_all == "0") {
                            window.location.href = 'org_management.php';
                        } else {
                            window.location.href = 'dashboard.php';
                        }
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    errorMsgBox.textContent = result.message || '帳號或密碼錯誤！';
                    errorMsgBox.style.display = 'block';
                }
            } catch (error) {
                errorMsgBox.textContent = '無法連線至伺服器，請稍後再試！';
                errorMsgBox.style.display = 'block';
            }
        });
    </script>
</body>
</html>