<?php
    // 引入 API 核心函式
    require_once "../common/entry.php";
    global $g_root_url;

    // 處理 AJAX 登入請求
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header('Content-Type: application/json; charset=utf-8');

        $json = file_get_contents("php://input");
        $post_data = json_decode($json, true);

        $username = trim($post_data['username'] ?? '');
        $password = trim($post_data['password'] ?? '');

        if (empty($username) || empty($password)) {
            echo json_encode(["status" => "false", "message" => "請輸入帳號與密碼！"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $baseUrl = rtrim($g_root_url, '/') . '/';

        // 1. 取得 SSO Token (透由 Cookie/API 運作的 getGoldenKey)
        try {
            $sso_token = getGoldenKey();
        } catch (Exception $e) {
            echo json_encode(["status" => "false", "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ------------------------------------------------------------------
        // 2. 帶入 Bearer Token 呼叫 JTG_member.php 驗證會員
        // ------------------------------------------------------------------
        $password_base64 = base64_encode($password);
        $member_url = $baseUrl . "api/JTG_member.php";

        $member_params = [
            "account"  => $username,
            "password" => $password_base64
        ];

        $member_result = null;
        $member_error = "";

        // 最多嘗試 2 次（若第一次 Token 過期，會強制刷新再試一次）
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $member_headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $sso_token
            ];

            $member_error = "";
            $member_response = callAPI($member_error, $member_url, $member_params, "GET", false, $member_headers);
            $member_result = json_decode($member_response, true);

            // 驗證成功即跳出迴圈
            if (empty($member_error) && isset($member_result['status']) && ($member_result['status'] === 'true' || $member_result['status'] === true)) {
                break;
            }

            // 第一次失敗：強制刷新 SSO Token 後再重試
            if ($attempt === 0) {
                try {
                    $sso_token = getGoldenKey(true); // 強制重新取得新 Token
                } catch (Exception $e) {
                    break;
                }
            }
        }

        // ------------------------------------------------------------------
        // 3. 處理登入結果與寫入 Cookie 憑證
        // ------------------------------------------------------------------
        if (empty($member_error) && isset($member_result['status']) && ($member_result['status'] === 'true' || $member_result['status'] === true)) {
            $member_data = $member_result['data'];
            $cur_member = is_array($member_data) ? ($member_data[0] ?? $member_data) : [];

            $acc_id    = $username;
            $acc_name  = $cur_member['member_name'] ?? $username;
            $user_role = $cur_member['role'] ?? '';

            // Cookie 通用設定 (保存 1 天)
            $cookie_options = [
                'expires'  => time() + 86400, // 24小時
                'path'     => '/',
                'httponly' => true,           // 防止 JavaScript/XSS 讀取
                'samesite' => 'Lax'
            ];

            // 寫入登入資訊至 Cookie (替代原本的 $_SESSION)
            setcookie('acc_id', $acc_id, $cookie_options);
            setcookie('acc_name', rawurlencode($acc_name), $cookie_options); // 避免中文姓名在 Cookie 亂碼
            setcookie('user_role', $user_role, $cookie_options);

            echo json_encode([
                "status"  => "true",
                "message" => "登入成功！",
                "role"    => $user_role
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $error_msg = $member_result['responseMessage'] ?? $member_result['message'] ?? $member_error ?? '帳號或密碼錯誤！';
            echo json_encode(["status" => "false", "message" => $error_msg], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
?>