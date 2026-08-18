<?php
    session_start();

    // 引入您的 API 核心函式
    require_once "../common/entry.php";

    // 處理 AJAX 登入請求
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header('Content-Type: application/json');

        $json = file_get_contents("php://input");
        $post_data = json_decode($json, true);

        $username = trim($post_data['username'] ?? '');
        $password = trim($post_data['password'] ?? '');

        if (empty($username) || empty($password)) {
            echo json_encode(["status" => "false", "message" => "請輸入帳號與密碼！"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

        // ------------------------------------------------------------------
        // 步驟 1: 呼叫 JTG_exhanceToken.php 取得 SSO Token
        // ------------------------------------------------------------------
        $token_url = $baseUrl . "/JTG_exhanceToken.php";
        $token_payload = json_encode([
            "api_key"  => $password,
            "who_call" => "web_login"
        ]);

        $token_headers = [
            'Content-Type: application/json'
        ];

        $error = "";
        // 使用 callAPI 以 POST 發送 JSON 資料
        $token_response = callAPI($error, $token_url, $token_payload, "POST", false, $token_headers);
        $token_result = json_decode($token_response, true);

        if (!empty($error) || !isset($token_result['status']) || $token_result['status'] !== 'true') {
            $error_msg = $token_result['responseMessage'] ?? $token_result['message'] ?? '帳號或密碼錯誤！';
            echo json_encode(["status" => "false", "message" => $error_msg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 解析出 sso_token
        $sso_token = is_array($token_result['data']) ? ($token_result['data']['sso_token'] ?? $token_result['data'][0] ?? '') : $token_result['data'];

        if (empty($sso_token)) {
            echo json_encode(["status" => "false", "message" => "無法取得 Token！"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ------------------------------------------------------------------
        // 步驟 2: 帶入 Bearer Token 呼叫 JTG_member.php 驗證會員
        // ------------------------------------------------------------------
        $member_url = $baseUrl . "/JTG_member.php";
        $member_params = [
            "account" => $username
        ];

        $member_headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $sso_token
        ];

        $member_error = "";
        // 使用 callAPI 以 GET 帶入 Header 發送請求
        $member_response = callAPI($member_error, $member_url, $member_params, "GET", false, $member_headers);
        $member_result = json_decode($member_response, true);

        if (empty($member_error) && isset($member_result['status']) && $member_result['status'] === 'true') {
            // 登入成功：寫入 Session
            $_SESSION['sso_token'] = $sso_token;
            $_SESSION['username']  = $username;

            echo json_encode(["status" => "true", "message" => "登入成功！"], JSON_UNESCAPED_UNICODE);
        } else {
            $error_msg = $member_result['responseMessage'] ?? $member_result['message'] ?? '帳號或密碼錯誤！';
            echo json_encode(["status" => "false", "message" => $error_msg], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
?>