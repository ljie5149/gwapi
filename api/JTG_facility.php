<?php
    /*************************************************/
    /*                                               */
    /*                facility資料操作                */
    /*                                               */
    /*************************************************/
    include("./../common/entry.php");
    
    header('Content-Type: application/json');

    // 解析 Request Body
    $json = file_get_contents("php://input");
    $src_data = [];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'GET') {
        $opt = "get";
        $src_data = $_GET;
    } else if ($method == 'POST') {
        $opt = "insert";
        $post_data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = result_message("false", "0x020E", "JSON decode error: " . json_last_error_msg(), []);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            return;
        }
        if (is_array($post_data)) $src_data = $post_data;
    } else if ($method == 'PATCH' || $method == 'PUT') {
        $opt = "edit";
        $patch_data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = result_message("false", "0x020E", "JSON decode error: " . json_last_error_msg(), []);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            return;
        }
        if (is_array($patch_data)) $src_data = $patch_data;
    }

    $API_name   = 'JTG_facility';
    $remote_ip  = get_remote_ip();
    $null_array = array();
    $caption    = "機構資料";

    $tableMain = 'data_facility';
    $tableLog  = 'log_facility';
    
    if ($opt != 'get' && empty($src_data)) {
        $data = result_message("false", "0x0200", "Invalid or empty JSON body", []);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return;
    }

    // Token 看門狗與身分驗證
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

    // 優先從 src_data (若為關聯陣列) 取得 sso_token
    $sso_token_param = (is_array($src_data) && isset($src_data['sso_token'])) ? $src_data['sso_token'] : '';
    $json_token = !empty($bearerToken) ? $bearerToken : $sso_token_param;
    
    $member_id = ""; $role = ""; $order_limit = ""; $pwd = "";
    if (!empty($json_token)) {
        $data = validToken4ApiUser($json_token, $member_id, $role, $order_limit, $pwd);
        if ($data["status"] == "false") {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            return;
        }
    } else {
        $data = result_message("false", "0x0206", "API parameter [sso_token] is required!", $null_array);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return;
    }

    $db = new CXDB($remote_ip);
    $link = null;
    try {
        $conn_res = $db->connect($link, $member_id, "");

        if ($conn_res["status"] == "true") {
            switch ($opt) {
                // ==========================================
                // 1. 查詢機構資料 (GET)
                // ==========================================
                case "get":
                    $get_all       = isset($src_data['get_all']      ) ? $src_data['get_all']       : '0';
                    $id            = isset($src_data['id']           ) ? intval($src_data['id'])    : 0;
                    $facility_no   = isset($src_data['facility_no']  ) ? (string)$src_data['facility_no'] : '';
                    $facility_name = isset($src_data['facility_name']) ? trim($src_data['facility_name']): '';

                    $where_clauses = ["1=1"];
                    $params = [];
                    $types = "";

                    if ($get_all === '0') {
                        $where_clauses[] = "status = 1";
                    }
                    if ($id > 0) {
                        $where_clauses[] = "id = ?";
                        $params[] = $id;
                        $types .= "i";
                    }
                    if (!empty($facility_no)) {
                        $where_clauses[] = "facility_no = ?";
                        $params[] = $facility_no;
                        $types .= "s";
                    }
                    if (!empty($facility_name)) {
                        $where_clauses[] = "facility_name LIKE ?";
                        $params[] = "%" . $facility_name . "%";
                        $types .= "s";
                    }

                    $sql = "SELECT id, facility_no, facility_name, status, remark, created_at, updated_at 
                            FROM $tableMain 
                            WHERE " . implode(" AND ", $where_clauses) . " 
                            ORDER BY id DESC";

                    $stmt = mysqli_prepare($link, $sql);
                    if ($stmt) {
                        if (!empty($params)) {
                            mysqli_stmt_bind_param($stmt, $types, ...$params);
                        }
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if ($result && mysqli_num_rows($result) > 0) {
                            $query_rows_tmp = [];
                            while ($row = mysqli_fetch_assoc($result)) {
                                array_push($query_rows_tmp, $row);
                            }
                            $query_rows["data"] = $query_rows_tmp;
                            $data = result_message("true", "0x0200", "取得 $caption 成功", $query_rows);
                        } else {
                            $data = result_message("false", "0x0204", "查無 $caption 資料", $null_array);
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $data = result_message("false", "0x0209", "SQL Prepare 失敗", $null_array);
                    }

                    $msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
                    $db->saveBusinessLog(
                                            $link,
                                            'log_facility',
                                            ['col_name' => 'facility_no',   'val' => $facility_no],
                                            ['col_name' => 'facility_name', 'val' => $facility_name],
                                            'GET',
                                            ['status' => 1, 'remark' => ''],
                                            $member_id,
                                            $remote_ip,
                                            '取得機構資料'
                                        );
                    break;

                // ==========================================
                // 2. 新增或更新機構資料 (POST - 支援批次陣列)
                // ==========================================
                case "insert":
                    // 統一轉為多筆清單格式（Indexed Array）
                    $items = isset($src_data[0]) && is_array($src_data[0]) ? $src_data : [$src_data];
                    $processed_results = [];
                    $has_error = false;

                    foreach ($items as $item) {
                        $who_call      = isset($item['who_call']     ) ? $item['who_call']      : 'app';
                        $facility_no   = isset($item['facility_no']  ) ? trim((string)$item['facility_no']) : '';
                        $facility_name = isset($item['facility_name']) ? trim((string)$item['facility_name']) : '';
                        $status        = isset($item['status']       ) ? intval($item['status']) : 1;
                        $remark        = isset($item['remark']       ) ? $item['remark']        : null;

                        if (empty($facility_no) || empty($facility_name)) {
                            $has_error = true;
                            $processed_results[] = [
                                'facility_no' => $facility_no,
                                'status'      => 'false',
                                'message'     => '新增失敗，[facility_no] 與 [facility_name] 為必填欄位！'
                            ];
                            continue;
                        }

                        // 檢查「外部系統機構編號」或「機構名稱」是否已存在
                        $chk_sql = "SELECT * FROM $tableMain WHERE facility_no = ? OR facility_name = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "ss", $facility_no, $facility_name);
                        mysqli_stmt_execute($chk_stmt);
                        $chk_res = mysqli_stmt_get_result($chk_stmt);

                        if ($chk_res && mysqli_num_rows($chk_res) > 0) {
                            // -----------------------------
                            // 資料已存在 -> 執行更新 (UPDATE)
                            // -----------------------------
                            $exist_data = mysqli_fetch_assoc($chk_res);
                            $exist_id   = $exist_data['id'];
                            mysqli_stmt_close($chk_stmt);

                            $update_sql = "UPDATE $tableMain 
                                           SET facility_no = ?, 
                                               facility_name = ?, 
                                               status = ?, 
                                               remark = ?, 
                                               updated_at = NOW() 
                                           WHERE id = ?";
                            
                            $up_stmt = mysqli_prepare($link, $update_sql);
                            mysqli_stmt_bind_param($up_stmt, "ssisi", $facility_no, $facility_name, $status, $remark, $exist_id);
                            $exec_up = mysqli_stmt_execute($up_stmt);
                            $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                            mysqli_stmt_close($up_stmt);

                            if ($exec_up && $affected_rows >= 0) {
                                $change_json = json_encode([
                                    'before' => $exist_data,
                                    'after'  => array_merge($exist_data, [
                                        'facility_no'   => $facility_no,
                                        'facility_name' => $facility_name,
                                        'status'        => $status,
                                        'remark'        => $remark
                                    ])
                                ], JSON_UNESCAPED_UNICODE);

                                $action_note = '資料已存在，自動執行更新機構資料';
                                $log_sql = "INSERT INTO $tableLog (facility_id, facility_no, facility_name, action_type, change_data, action_user, action_ip, action_note, created_at)
                                            VALUES (?, ?, ?, 'UPDATE', ?, ?, ?, ?, NOW())";
                                $log_stmt = mysqli_prepare($link, $log_sql);
                                mysqli_stmt_bind_param($log_stmt, "issssss", $exist_id, $facility_no, $facility_name, $change_json, $member_id, $remote_ip, $action_note);
                                mysqli_stmt_execute($log_stmt);
                                mysqli_stmt_close($log_stmt);

                                $processed_results[] = ['id' => $exist_id, 'facility_no' => $facility_no, 'action' => 'UPDATE', 'status' => 'true'];

                                $db->saveBusinessLog(
                                    $link, 'log_facility',
                                    ['col_name' => 'facility_no',   'val' => $facility_no],
                                    ['col_name' => 'facility_name', 'val' => $facility_name],
                                    'UPDATE', ['status' => 1, 'remark' => $who_call.' 呼叫 api '.$API_name],
                                    $member_id, $remote_ip, '更新機構資料成功'
                                );
                            } else {
                                $has_error = true;
                                $processed_results[] = ['facility_no' => $facility_no, 'status' => 'false', 'message' => '更新失敗'];
                            }
                        } else {
                            // -----------------------------
                            // 資料不存在 -> 執行新增 (INSERT)
                            // -----------------------------
                            if ($chk_stmt) mysqli_stmt_close($chk_stmt);

                            $insert_sql = "INSERT INTO $tableMain (facility_no, facility_name, status, remark, created_at) 
                                           VALUES (?, ?, ?, ?, NOW())";
                            
                            $in_stmt = mysqli_prepare($link, $insert_sql);
                            mysqli_stmt_bind_param($in_stmt, "ssis", $facility_no, $facility_name, $status, $remark);
                            $exec_in = mysqli_stmt_execute($in_stmt);

                            if ($exec_in && mysqli_stmt_affected_rows($in_stmt) > 0) {
                                $new_facility_id = mysqli_insert_id($link);
                                mysqli_stmt_close($in_stmt);

                                $change_json = json_encode([
                                    'facility_no'   => $facility_no,
                                    'facility_name' => $facility_name,
                                    'status'        => $status,
                                    'remark'        => $remark
                                ], JSON_UNESCAPED_UNICODE);

                                $action_note = '新增機構資料';
                                $log_sql = "INSERT INTO $tableLog (facility_id, facility_no, facility_name, action_type, change_data, action_user, action_ip, action_note, created_at)
                                            VALUES (?, ?, ?, 'INSERT', ?, ?, ?, ?, NOW())";
                                $log_stmt = mysqli_prepare($link, $log_sql);
                                mysqli_stmt_bind_param($log_stmt, "issssss", $new_facility_id, $facility_no, $facility_name, $change_json, $member_id, $remote_ip, $action_note);
                                mysqli_stmt_execute($log_stmt);
                                mysqli_stmt_close($log_stmt);

                                $processed_results[] = ['id' => $new_facility_id, 'facility_no' => $facility_no, 'action' => 'INSERT', 'status' => 'true'];

                                $db->saveBusinessLog(
                                    $link, 'log_facility',
                                    ['col_name' => 'facility_no',   'val' => $facility_no],
                                    ['col_name' => 'facility_name', 'val' => $facility_name],
                                    'INSERT', ['status' => 1, 'remark' => $who_call.' 呼叫 api '.$API_name],
                                    $member_id, $remote_ip, '新增機構資料成功'
                                );
                            } else {
                                if ($in_stmt) mysqli_stmt_close($in_stmt);
                                $has_error = true;
                                $processed_results[] = ['facility_no' => $facility_no, 'status' => 'false', 'message' => '新增失敗'];
                            }
                        }
                    }

                    $status_flag = $has_error ? "false" : "true";
                    $code_flag = $has_error ? "0x0206" : "0x0200";
                    $msg_flag = $has_error ? "部分或全部 $caption 處理失敗" : "處理 $caption 成功";
                    $data = result_message($status_flag, $code_flag, $msg_flag, $processed_results);
                    break;

                // ==========================================
                // 3. 編輯機構資料 (PATCH / PUT)
                // ==========================================
                case "edit":
                    $id            = isset($src_data['id']           ) ? intval($src_data['id'])    : 0;
                    $facility_no   = isset($src_data['facility_no']  ) ? (string)$src_data['facility_no'] : '';
                    $facility_name = isset($src_data['facility_name']) ? trim($src_data['facility_name']): '';
                    $status        = isset($src_data['status']       ) ? intval($src_data['status']) : 1;
                    $remark        = isset($src_data['remark']       ) ? $src_data['remark']        : null;
                    $who_call      = isset($src_data['who_call']     ) ? $src_data['who_call']      : 'app';

                    if ($id <= 0 && empty($facility_no)) {
                        $data = result_message("false", "0x0206", "編輯失敗，必須提供 [id] 或 [facility_no]！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    if ($id > 0) {
                        $chk_sql = "SELECT * FROM $tableMain WHERE id = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "i", $id);
                    } else {
                        $chk_sql = "SELECT * FROM $tableMain WHERE facility_no = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $facility_no);
                    }
                    
                    mysqli_stmt_execute($chk_stmt);
                    $chk_res = mysqli_stmt_get_result($chk_stmt);

                    if (!$chk_res || mysqli_num_rows($chk_res) == 0) {
                        if ($chk_stmt) mysqli_stmt_close($chk_stmt);
                        $data = result_message("false", "0x0206", "編輯失敗，找不到指定的 $caption！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $old_data = mysqli_fetch_assoc($chk_res);
                    $target_id = $old_data['id'];
                    mysqli_stmt_close($chk_stmt);

                    $update_fields = [];
                    $params = [];
                    $types = "";

                    if (!empty($facility_name)) {
                        $update_fields[] = "facility_name = ?";
                        $params[] = $facility_name;
                        $types .= "s";
                    }
                    if (isset($src_data['status'])) {
                        $update_fields[] = "status = ?";
                        $params[] = $status;
                        $types .= "i";
                    }
                    if ($remark !== null) {
                        $update_fields[] = "remark = ?";
                        $params[] = $remark;
                        $types .= "s";
                    }

                    if (empty($update_fields)) {
                        $data = result_message("false", "0x0206", "沒有需要更新的欄位內容！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $update_fields[] = "updated_at = NOW()";
                    $update_sql = "UPDATE $tableMain SET " . implode(", ", $update_fields) . " WHERE id = ?";
                    
                    $params[] = $target_id;
                    $types .= "i";

                    $up_stmt = mysqli_prepare($link, $update_sql);
                    mysqli_stmt_bind_param($up_stmt, $types, ...$params);
                    $exec_up = mysqli_stmt_execute($up_stmt);
                    $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                    mysqli_stmt_close($up_stmt);

                    if ($exec_up && $affected_rows >= 0) {
                        $change_json = json_encode([
                            'before' => $old_data,
                            'after'  => array_merge($old_data, $src_data)
                        ], JSON_UNESCAPED_UNICODE);

                        $fac_no_val   = $old_data['facility_no'];
                        $fac_name_val = !empty($facility_name) ? $facility_name : $old_data['facility_name'];
                        $action_note  = '更新機構資料';

                        $log_sql = "INSERT INTO $tableLog (facility_id, facility_no, facility_name, action_type, change_data, action_user, action_ip, action_note, created_at)
                                    VALUES (?, ?, ?, 'UPDATE', ?, ?, ?, ?, NOW())";
                        $log_stmt = mysqli_prepare($link, $log_sql);
                        mysqli_stmt_bind_param($log_stmt, "issssss", $target_id, $fac_no_val, $fac_name_val, $change_json, $member_id, $remote_ip, $action_note);
                        mysqli_stmt_execute($log_stmt);
                        mysqli_stmt_close($log_stmt);

                        $data = result_message("true", "0x0200", "更新 $caption 成功", ['id' => $target_id]);
                    } else {
                        $null_array["err"] = mysqli_error($link);
                        $data = result_message("false", "0x0206", "更新 $caption 失敗", $null_array);
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $data = result_message("false", "0x0209", "Exception error!", $null_array);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>