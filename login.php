<?php
	include_once('./common/entry.php');
    global $g_root_url, $g_online_zhtw, $g_backend_title;
	getGoldenKey();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $backend_title; ?> - 登入</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
        }

        body {
            background-color: #ffffff;
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
            max-width: 720px;
            padding: 24px;
        }

        .logo-box {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-box img {
            max-width: 504px;
            width: 100%;
            height: auto;
        }

        .system-title {
            font-size: 46px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 42px;
            letter-spacing: 2.5px;
        }

        .card {
            background-color: #ebebeb;
            border-radius: 42px;
            padding: 60px 54px 54px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .input-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .input-group label {
            font-size: 31px;
            font-weight: bold;
            color: #2b79a2;
            width: 110px;
            text-align: left;
        }

        .input-group input {
            flex: 1;
            height: 62px;
            border: 2px solid #c8c8c8;
            border-radius: 12px;
            padding: 0 20px;
            font-size: 26px;
            outline: none;
            background-color: #ffffff;
        }

        .input-group input:focus {
            border-color: #2b79a2;
            box-shadow: 0 0 8px rgba(43, 121, 162, 0.3);
        }

        .submit-btn {
            background-color: #fca934;
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 17px 0;
            font-size: 34px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 18px;
            width: 55%;
            align-self: center;
            letter-spacing: 5px;
            transition: background-color 0.2s ease;
        }

        .submit-btn:hover {
            background-color: #e59728;
        }

        .error-msg {
            color: #d9534f;
            text-align: center;
            font-size: 24px;
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

        <div class="system-title">量測後台管理系統<span style="font-size:24px; color:gray;"><?= $g_online_zhtw ?></span></div>

        <!-- 登入表單 -->
        <div class="card">
            <!-- 錯誤提示區域 -->
            <div id="errorMsg" class="error-msg"></div>

            <form id="loginForm">
                <div class="input-group" style="margin-bottom: 30px;">
                    <label for="username">帳號</label>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>

                <div class="input-group" style="margin-bottom: 30px;">
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
				// console.log(api_url);
                // 指向獨立處理 Token 驗證與登入邏輯的 API
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
				console.log(result);

                if (result.status === 'true') {
                    // 登入成功，轉址至儀表板主頁
                    window.location.href = 'dashboard.php';
                } else {
                    // 登入失敗，顯示錯誤提示訊息
                    errorMsgBox.textContent = result.message || '帳號或密碼錯誤！';
                    errorMsgBox.style.display = 'block';
                }
            } catch (error) {
                // API 連線或系統異常處理
                errorMsgBox.textContent = '無法連線至伺服器，請稍後再試！';
                errorMsgBox.style.display = 'block';
            }
        });
    </script>
</body>
</html>