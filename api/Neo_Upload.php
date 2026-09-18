<?php
    /*************************************************/
    /*                                               */
    /*         NeoUpload 呼叫第三方 API 上傳資料      */
    /*                                               */
    /*************************************************/
    include("./../common/entry.php");
    
    header('Content-Type: application/json; charset=utf-8');

    $API_name   = 'Neo_Upload';
    $remote_ip  = get_remote_ip();
    $null_array = array();
    $caption    = "共通資料上傳";
    $member_id  = "Neo_Upload back_end";
    $log_table  = "log_message";

    $data = array();
    $link = null; // 初始化資料庫連線指標

    try {
        // 1. 接收 C# / 外部傳入的 ApiRequestParam JSON 資料
        $raw_input = file_get_contents("php://input");
        // echo $raw_input."\n";
        $param_data = json_decode($raw_input, true);

        // 2. 呼叫 Function 處理業務邏輯
        $data = process_neoupload_data("", $param_data);

    } catch (Throwable $t) {
        // PHP 7+ 支援 Catch Throwable (可同時捕獲 Error 與 Exception)
        $data = result_message("false", "0x0209", "System error: " . $t->getMessage(), $null_array);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if (isset($data_close_conn["status"]) && $data_close_conn["status"] === "false") {
            $data = $data_close_conn;
        }
    }

    // 回傳結果
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>