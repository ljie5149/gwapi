<?php
    /*************************************************/
    /* */
    /* 使用者驗證與 Token 交換 API          */
    /* */
    /*************************************************/
    include("./../common/entry.php");
    
    header('Content-Type: application/json');

    // 解析 Request Body
    $json = file_get_contents("php://input");
    $src_data = [];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'GET') {
        $src_data = $_GET;
    } else if ($method == 'POST') {
        $post_data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = result_message("false", "0x020E", "JSON decode error: " . json_last_error_msg(), []);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            return;
        }
        if (is_array($post_data)) $src_data = $post_data;
    } else {
        $data = result_message("false", "0x0200", "Method Not Allowed", []);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return;
    }

    // 業務欄位解析 (讀取傳入的帳號與密碼)
    $who_call = isset($src_data['who_call']) ? $src_data['who_call'] : 'app';
    $userpwd  = (isset($src_data['api_key']) ? trim($src_data['api_key']) : '');

    $API_name   = 'JTG_exhanceToken';
    $remote_ip  = get_remote_ip();
    $null_array = array();
    $caption    = "Token 交換";

    $tableUser = 'data_user';

    // 檢查必填欄位
    if (empty($userpwd)) {
        $data = result_message("false", "0x0206", "驗證失敗， [api_key] 為必填欄位！", $null_array);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return;
    }

    $db = new CXDB($remote_ip);
    $link = null;
    try {
        $conn_res = $db->connect($link, "", "");

        if ($conn_res["status"] == "true") {
            // 使用 Prepared Statement 查詢使用者資料表，防止 SQL Injection
            $sql = "SELECT nid, sid, mid, pwd FROM $tableUser WHERE pwd = ? LIMIT 1";
            $stmt = mysqli_prepare($link, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $userpwd);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($result && mysqli_num_rows($result) > 0) {
                    $user_info = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt);

                    // 比對密碼 (若未來密碼改用 password_hash 雜湊演算法，可改用 password_verify($userpwd, $user_info['pwd']))
                    if ($user_info['pwd'] === $userpwd) {
                        $userid = $user_info['mid'];
                        // ----------------------------------------------------
                        // 帳號密碼驗證正確：產生 SSO Token
                        // ----------------------------------------------------
                        $sso_token = generateSSOtoken($userid, $userpwd);

                        $data = result_message("true", "0x0200", "Token 取得成功", $sso_token);
                        $msg_detail = get_error_symbol($data["code"]) . " result :" . $data["code"] . " " . $data["responseMessage"];

                        // 紀錄系統 Log
                        $db->saveLog($link, $userid, $who_call . ' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail, "Token generated successfully");
                    } else {
                        // 密碼錯誤
                        $data = result_message("false", "0x0204", "帳號或密碼錯誤！", $null_array);
                        $msg_detail = get_error_symbol($data["code"]) . " error : Password incorrect";
                    }
                } else {
                    mysqli_stmt_close($stmt);
                    // 查無此帳號
                    $data = result_message("false", "0x0204", "帳號或密碼錯誤！", $null_array);
                    $msg_detail = get_error_symbol($data["code"]) . " error : User not found";
                }
            } else {
                $data = result_message("false", "0x0209", "SQL Prepare 失敗", $null_array);
                $msg_detail = get_error_symbol($data["code"]) . " error : Prepare stmt failed";
            }
        } else {
            $data = result_message("false", "0x0209", "資料庫連線失敗", $null_array);
            $msg_detail = "Database connection error";
        }
    } catch (Exception $e) {
        $data = result_message("false", "0x0209", "Exception error!", $null_array);
        $msg_detail = get_error_symbol($data["code"]) . " error :" . $e->getMessage();
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $userid);
        if ($data_close_conn["status"] == "false") {
            $data = $data_close_conn;
        }
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>