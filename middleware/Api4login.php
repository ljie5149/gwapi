<?php
    // 引入您的 API 核心函式
    require_once "../common/entry.php";
    global $g_root_url;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

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

        $baseUrl = $g_root_url;
        $sso_token = trim(($_SESSION['sso_token'] ?? ''));
        
        // ------------------------------------------------------------------
        // 步驟 1: 帶入 Bearer Token 呼叫 JTG_member.php 驗證會員
        // ------------------------------------------------------------------
        // 將密碼進行 Base64 編碼
        $password_base64 = base64_encode($password);
        $member_url = $baseUrl . "api/JTG_member.php";
        $member_params = [
            "account"  => $username,
            "password" => $password_base64
        ];

        $member_headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $sso_token
        ];

        $member_error = "";
        // 使用 callAPI 以 GET 帶入 Header 發送請求
        $member_response = callAPI($member_error, $member_url, $member_params, "GET", false, $member_headers);
        $member_result = json_decode($member_response, true);
            // echo json_encode(["status" => "false", "message" => $member_response], JSON_UNESCAPED_UNICODE);
            // exit;

        if (empty($member_error) && isset($member_result['status']) && $member_result['status'] === 'true') {
            $member_data = $member_result['data'];
            $cur_member = $member_data[0];
            // 登入成功：寫入 Session
            $_SESSION['accname'  ] = $cur_member['member_name'];
            $_SESSION['user_role'] = $cur_member['role'];

            echo json_encode(["status" => "true", "message" => "登入成功！"], JSON_UNESCAPED_UNICODE);
        } else {
            $error_msg = $member_result['responseMessage'] ?? $member_result['message'] ?? '帳號或密碼錯誤！';
            echo json_encode(["status" => "false", "message" => $error_msg], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
?>