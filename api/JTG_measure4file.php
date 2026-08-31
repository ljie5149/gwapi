<?php
    /*************************************************/
    /*                                               */
    /*         JTG_measure4file 資料操作              */
    /*                                               */
    /*************************************************/
    include("./../common/entry.php");
    
    header('Content-Type: application/json');

    $API_name   = 'JTG_measure4file';
    $remote_ip  = get_remote_ip();
    $null_array = array();
    $caption    = "健檢量測檔案資料";

    /**
     * 安全提取字串參數：若為 empty/null 則回傳自訂預設值 (預設為 null)
     */
    function get_str_val($arr, $key, $default = null) {
        if (!isset($arr[$key]) || $arr[$key] === null) {
            return $default;
        }
        if (is_array($arr[$key]) || is_object($arr[$key])) {
            return $default;
        }
        $val = trim((string)$arr[$key]);
        return ($val === '') ? $default : $val;
    }

    /**
     * 安全提取數值參數
     */
    function get_int_val($arr, $key, $default = 0) {
        if (!isset($arr[$key]) || $arr[$key] === null || $arr[$key] === '') {
            return $default;
        }
        return intval($arr[$key]);
    }

    // 1. 判斷 Request Method 是否為 POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $data = result_message("false", "0x0201", "Method Not Allowed, only POST is accepted", $null_array);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return;
    }

    // 2. 解析 Request Payload (純讀取 $_POST)
    $src_data = $_POST;

    if (empty($src_data) && empty($_FILES)) {
        $data = result_message("false", "0x0200", "Invalid or empty Request parameters", $null_array);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return;
    }

    // 3. Token 安全防護與身分驗證
    $bearerToken = "";
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    
    $authHeader = "";
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } else if (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!empty($authHeader) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $bearerToken = $matches[1];
    }

    $sso_token_param = get_str_val($src_data, 'sso_token', '');
    $json_token = !empty($bearerToken) ? $bearerToken : $sso_token_param;

    $member_id = ""; $role = ""; $order_limit = ""; $pwd = "";
    if (!empty($json_token)) {
        $valid_res = validToken4ApiUser($json_token, $member_id, $role, $order_limit, $pwd);
        if ($valid_res["status"] === "false") {
            echo json_encode($valid_res, JSON_UNESCAPED_UNICODE);
            return;
        }
    } else {
        $data = result_message("false", "0x0206", "API parameter [sso_token] or Bearer token is required!", $null_array);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return;
    }

    // 4. 白名單限制資料表名稱
    $tableMain = 'data_measure';
    $tableLog  = 'log_measure';
    
    /**
     * 寫入 log_measure 紀錄表
     */
    function writeMeasureLog($link, $tableLog, $facilityId, $measureId, $sid, $measureNo, $deviceNo, $machineModel, $actionType, $changeData, $actionUser, $actionIp, $actionNote) {
        $allowed_tables = ['log_measure'];
        if (!in_array($tableLog, $allowed_tables, true)) {
            return false;
        }

        $log_sql = "INSERT INTO `$tableLog` 
                    (facility_id, measure_id, sid, measure_no, asset_no, machine_model, action_type, change_data, action_user, action_ip, action_note, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($link, $log_sql);
        if ($stmt) {
            $json_change = is_array($changeData) || is_object($changeData) ? json_encode($changeData, JSON_UNESCAPED_UNICODE) : $changeData;
            mysqli_stmt_bind_param($stmt, "iisssssssss", 
                $facilityId, $measureId, $sid, $measureNo, $deviceNo, $machineModel, 
                $actionType, $json_change, $actionUser, $actionIp, $actionNote
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // 5. 資料庫邏輯執行（單筆資料處理）
    $db = new CXDB($remote_ip);
    $link = null;
    $item = $src_data;
    $processed_results = [];
    $has_error = false;

    try {
        $conn_res = $db->connect($link, $member_id, "");

        if ($conn_res["status"] === "true") {

            // 安全讀取與容錯處理
            $who_call          = get_str_val($item, 'who_call', 'app');
            $sid               = get_str_val($item, 'sid', '');
            $facility_id       = get_int_val($item, 'facility_id', 0);
            $measure_no        = get_str_val($item, 'measure_no', '');
            $asset_no          = get_str_val($item, 'asset_no', '');
            $machine_model     = get_str_val($item, 'machine_model', '');
            $tester_identifier = get_str_val($item, 'tester_identifier', null);
            $tester_work_id    = get_str_val($item, 'tester_work_id', null);
            $tester_name       = get_str_val($item, 'tester_name', null);
            $tester_age        = get_str_val($item, 'tester_age', null);
            $tester_height     = get_str_val($item, 'tester_height', null);
            $editor            = get_str_val($item, 'editor', null);
            $device_type_zhtw  = get_str_val($item, 'device_type_zhtw', null); // 【修正 BUG 1】：補齊缺失變數
            $measure_count     = get_int_val($item, 'measure_count', 1);
            $online_type       = get_str_val($item, 'online_type', 'ON-LINE');
            $is_uploaded       = get_int_val($item, 'is_uploaded', 0);
            
            // 欄位內容若是陣列則編碼為 JSON，否則取字串
            $json_data_val     = isset($item['json_data']) ? $item['json_data'] : null;
            $json_data         = is_array($json_data_val) ? json_encode($json_data_val, JSON_UNESCAPED_UNICODE) : get_str_val($item, 'json_data', null);
            
            $raw_data          = get_str_val($item, 'raw_data', null);
            $measure_date      = get_str_val($item, 'measure_date', date('Y-m-d H:i:s'));
            
            $up_json_data_val  = isset($item['up_json_data']) ? $item['up_json_data'] : null;
            $up_json_data      = is_array($up_json_data_val) ? json_encode($up_json_data_val, JSON_UNESCAPED_UNICODE) : get_str_val($item, 'up_json_data', null);
            
            $remark            = get_str_val($item, 'remark', null);

            // 處理表單上傳檔案 ($_FILES)
            $file_binary = null;
            $file_name   = get_str_val($item, 'file_name', null);
            $mime_type   = get_str_val($item, 'mime_type', null);
            $file_size   = get_int_val($item, 'file_size', 0);

            if (isset($_FILES['file_data']) && $_FILES['file_data']['error'] === UPLOAD_ERR_OK) {
                $file_name   = ($file_name !== null) ? $file_name : $_FILES['file_data']['name'];
                $mime_type   = ($mime_type !== null) ? $mime_type : $_FILES['file_data']['type'];
                $file_size   = ($_FILES['file_data']['size'] > 0) ? $_FILES['file_data']['size'] : $file_size;
                $file_binary = file_get_contents($_FILES['file_data']['tmp_name']);
            }

            // 驗證必填欄位
            if ($facility_id <= 0 || empty($measure_no) || empty($asset_no) || empty($machine_model) || empty($measure_date)) {
                $has_error = true;
                $processed_results[] = [
                    'sid'     => $sid,
                    'status'  => 'false',
                    'message' => '處理失敗，[facility_id]、[measure_no]、[asset_no]、[machine_model] 與 [measure_date] 為必填欄位！'
                ];
            } else {

                // 根據 SID 檢查資料是否存在
                $exist_data = null; $dataExists = false;
                if (!empty($measure_no) && !empty($asset_no)) {
                    $Where_Fields = "";
                    $Where_Fields .= merge_sql_string_if_not_empty2('tester_identifier', $tester_identifier);
                    $Where_Fields .= merge_sql_string_if_not_empty2('tester_work_id', $tester_work_id);
                    $Where_Fields .= merge_sql_string_if_not_empty2('tester_name', $tester_name);
                    $Where_Fields .= merge_sql_string_if_not_empty2('tester_age', $tester_age);
                    $Where_Fields .= merge_sql_string_if_not_empty2('tester_height', $tester_height);
                    $chk_sql = "SELECT * FROM `$tableMain` WHERE facility_id = ? 
                                    AND measure_no = ? AND asset_no = ? AND machine_model = ? ".$Where_Fields."
                                    LIMIT 1";
                    $chk_stmt = mysqli_prepare($link, $chk_sql);
                    if ($chk_stmt) {
                        mysqli_stmt_bind_param($chk_stmt, "isss", $facility_id, 
                                                $measure_no, $asset_no, $machine_model);
                        mysqli_stmt_execute($chk_stmt);
                        $chk_res = mysqli_stmt_get_result($chk_stmt);
                        if ($chk_res && mysqli_num_rows($chk_res) > 0) {
                            $dataExists = true;
                            $exist_data = mysqli_fetch_assoc($chk_res);
                        }
                        mysqli_stmt_close($chk_stmt);
                    }
                }
                if ($exist_data) {
                    // ==========================================
                    // 資料存在 -> 執行更新 (UPDATE)
                    // ==========================================
                    $target_id = $exist_data['id'];

                    if ($file_binary === null) {
                        $update_sql = "UPDATE `$tableMain` 
                                       SET facility_id = ?, measure_no = ?, tester_identifier = ?, tester_work_id = ?, 
                                           tester_name = ?, tester_age = ?, tester_height = ?, editor = ?, asset_no = ?, 
                                           device_type_zhtw = ?, 
                                           machine_model = ?, measure_count = ?, online_type = ?, is_uploaded = ?, 
                                           json_data = ?, raw_data = ?, measure_date = ?, up_json_data = ?, remark = ?, 
                                           updated_at = NOW() 
                                       WHERE id = ?";

                        $up_stmt = mysqli_prepare($link, $update_sql);
                        mysqli_stmt_bind_param($up_stmt, "isssssssssisisssssi", 
                            $facility_id, $measure_no, $tester_identifier, $tester_work_id, 
                            $tester_name, $tester_age, $tester_height, $editor, $asset_no, 
                            $device_type_zhtw, 
                            $machine_model, $measure_count, $online_type, $is_uploaded, 
                            $json_data, $raw_data, $measure_date, $up_json_data, $remark, 
                            $target_id
                        );
                    } else {
                        $update_sql = "UPDATE `$tableMain` 
                                       SET facility_id = ?, measure_no = ?, tester_identifier = ?, tester_work_id = ?, tester_name = ?, 
                                            tester_age = ?, tester_height = ?, editor = ?, asset_no = ?, device_type_zhtw = ?, machine_model = ?, 
                                            measure_count = ?, online_type = ?, is_uploaded = ?, json_data = ?, raw_data = ?, 
                                            file_name = ?, mime_type = ?, file_size = ?, file_data = ?, measure_date = ?, 
                                            up_json_data = ?, remark = ?, updated_at = NOW() 
                                       WHERE id = ?";

                        $up_stmt = mysqli_prepare($link, $update_sql);
                        $null_placeholder = NULL;
                        // 【修正 BUG 2】：型態字串補上一個 's'，為 24 字元對應 24 個參數
                        mysqli_stmt_bind_param($up_stmt, "isssssssssisissssssbsssi", 
                            $facility_id, $measure_no, $tester_identifier, $tester_work_id, $tester_name, 
                            $tester_age, $tester_height, $editor, $asset_no, $device_type_zhtw, $machine_model, 
                            $measure_count, $online_type, $is_uploaded, $json_data, $raw_data, 
                            $file_name, $mime_type, $file_size, $null_placeholder, $measure_date, 
                            $up_json_data, $remark, 
                            $target_id
                        );
                        // 0-based index 19 對應第 20 個問號 (file_data)
                        mysqli_stmt_send_long_data($up_stmt, 19, $file_binary);
                    }

                    $exec_up = mysqli_stmt_execute($up_stmt);
                    $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                    
                    if ($exec_up && $affected_rows >= 0) {
                        mysqli_stmt_close($up_stmt);
                        $processed_results[] = ['id' => $target_id, 'sid' => $sid, 'action' => 'UPDATE', 'status' => 'true'];

                        $log_before = $exist_data; unset($log_before['file_data']);
                        $log_after  = $item; unset($log_after['file_data']);

                        writeMeasureLog(
                            $link, $tableLog, $facility_id, $target_id, $sid, $measure_no, 
                            $asset_no, $machine_model, 'UPDATE', 
                            ['before' => $log_before, 'after' => $log_after], 
                            $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                        );
                    } else {
                        // 【修正 BUG 3】：寫入 DB Error 方便除錯
                        $db_err = $up_stmt ? mysqli_stmt_error($up_stmt) : mysqli_error($link);
                        if ($up_stmt) mysqli_stmt_close($up_stmt);
                        $has_error = true;
                        $processed_results[] = ['sid' => $sid, 'status' => 'false', 'message' => '資料更新失敗: ' . $db_err];
                    }

                } else {
                    // ==========================================
                    // 資料不存在 -> 執行新增 (INSERT)
                    // ==========================================
                    $generated_sid = !empty($sid) ? $sid : ('MD_' . substr(md5(uniqid(mt_rand(), true)), 0, 12));
                    
                    $insert_sql = "INSERT INTO `$tableMain` (
                                        sid, facility_id, measure_no, tester_identifier, tester_work_id,
                                        tester_name, tester_age, tester_height, editor, asset_no,
                                        device_type_zhtw, machine_model, measure_count, online_type, is_uploaded, 
                                        json_data, raw_data, file_name, mime_type, file_size,
                                        file_data, measure_date, up_json_data, remark, created_at
                                    ) VALUES (
                                        ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, NOW()
                                    )";

                    $in_stmt = mysqli_prepare($link, $insert_sql);
                    $null_placeholder = NULL;

                    // 第 21 個位置對應 'b'，綁定變數傳入 $null_placeholder (NULL)
                    mysqli_stmt_bind_param($in_stmt, "sissssssssssisissssibsss", 
                        $generated_sid, $facility_id, $measure_no, $tester_identifier, $tester_work_id, 
                        $tester_name, $tester_age, $tester_height, $editor, $asset_no, 
                        $device_type_zhtw, $machine_model, $measure_count, $online_type, $is_uploaded, 
                        $json_data, $raw_data, $file_name, $mime_type, $file_size,
                        $null_placeholder, $measure_date, $up_json_data, $remark
                    );

                    if ($file_binary !== null) {
                        // 0-based index 20 對應第 21 個問號 (file_data)
                        mysqli_stmt_send_long_data($in_stmt, 20, $file_binary);
                    }

                    $exec_in = mysqli_stmt_execute($in_stmt);

                    if ($exec_in && mysqli_stmt_affected_rows($in_stmt) > 0) {
                        $new_id = mysqli_insert_id($link);
                        mysqli_stmt_close($in_stmt);

                        $processed_results[] = ['id' => $new_id, 'sid' => $generated_sid, 'action' => 'INSERT', 'status' => 'true'];

                        $log_item = $item; unset($log_item['file_data']);

                        writeMeasureLog(
                            $link, $tableLog, $facility_id, $new_id, $generated_sid, $measure_no, 
                            $asset_no, $machine_model, 'INSERT', 
                            $log_item, 
                            $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                        );
                    } else {
                        // 【修正 BUG 3】：寫入 DB Error 方便除錯
                        $db_err = $in_stmt ? mysqli_stmt_error($in_stmt) : mysqli_error($link);
                        if ($in_stmt) mysqli_stmt_close($in_stmt);
                        $has_error = true;
                        $processed_results[] = ['sid' => $generated_sid, 'status' => 'false', 'message' => '資料新增失敗: ' . $db_err];
                    }
                }
            }

            $status_flag = $has_error ? "false" : "true";
            $code_flag   = $has_error ? "0x0206" : "0x0200";
            $method_str  = (($exist_data) ? "更新" : "新增");
            $msg_flag    = $has_error ? "$method_str 部分或全部 $caption 處理失敗" : "$method_str $caption 成功";
            $data        = result_message($status_flag, $code_flag, $msg_flag, $processed_results);

        } else {
            $data = $conn_res;
        }
    } catch (Exception $e) {
        $data = result_message("false", "0x0209", "Exception error: " . $e->getMessage(), $null_array);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] === "false") {
            $data = $data_close_conn;
        }
    }

    // 6. 回傳 JSON 結果
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>