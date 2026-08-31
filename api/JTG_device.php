<?php
    /*************************************************/
    /*                                               */
    /*                 device 資料操作                */
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
    } else if ($method == 'DELETE') {
        $opt = "delete";
        $delete_data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($delete_data)) {
            $src_data = $delete_data;
        } else {
            $src_data = $_GET; // 相容於 URL 查詢字串的 DELETE 請求
        }
    }

    $API_name   = 'JTG_devselection';
    $remote_ip  = get_remote_ip();
    $null_array = array();
    $caption    = "健檢設備資料";

    $tableMain = 'data_device';
    $tableLog  = 'log_device';
    
    if ($opt != 'get' && empty($src_data)) {
        $data = result_message("false", "0x0200", "Invalid or empty Request parameters", []);
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

    /**
     * 寫入 log_device 紀錄表
     */
    function writeDeviceLog($link, $tableLog, $deviceId, $sid, $pcMac, $assetNo, $deviceType, $deviceName, $actionType, $changeData, $actionUser, $actionIp, $actionNote) {
        $log_sql = "INSERT INTO $tableLog 
                    (device_id, sid, pc_mac, asset_no, device_type, device_name, action_type, change_data, action_user, action_ip, action_note, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($link, $log_sql);
        if ($stmt) {
            $json_change = is_array($changeData) || is_object($changeData) ? json_encode($changeData, JSON_UNESCAPED_UNICODE) : $changeData;
            mysqli_stmt_bind_param($stmt, "issssssssss", 
                $deviceId, $sid, $pcMac, $assetNo, $deviceType, $deviceName, 
                $actionType, $json_change, $actionUser, $actionIp, $actionNote
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    $db = new CXDB($remote_ip);
    $link = null;
    try {
        $conn_res = $db->connect($link, $member_id, "");

        if ($conn_res["status"] == "true") {
            // 1. 預先載入 default_device 預設參數對照表 (Key: device_name)
            $default_device_map = array();
            $def_sql = "SELECT * FROM default_device WHERE status = 1;";
            $def_result = $db->query($link, $def_sql);
            if (!is_null($def_result) && mysqli_num_rows($def_result) > 0) {
                while ($def_row = mysqli_fetch_assoc($def_result)) {
                    if (!empty($def_row['device_name'])) {
                        $default_device_map[$def_row['device_name']] = $def_row;
                    }
                }
            }

            switch ($opt) {
                // ==========================================
                // 1. 查詢設備資料 (GET)
                // ==========================================
                case "get":
                    $get_all      = isset($src_data['get_all']     ) ? $src_data['get_all']      : '0';
                    $id           = isset($src_data['id']          ) ? intval($src_data['id'])   : 0;
                    $sid          = isset($src_data['sid']         ) ? trim($src_data['sid'])    : '';
                    $pc_mac       = isset($src_data['pc_mac']      ) ? trim($src_data['pc_mac'])   : '';
                    $asset_no     = isset($src_data['asset_no']    ) ? trim($src_data['asset_no']) : '';
                    $device_type  = isset($src_data['device_type'] ) ? trim($src_data['device_type']) : '';
                    $device_name  = isset($src_data['device_name'] ) ? trim($src_data['device_name']) : '';
                    $search_key   = isset($src_data['search_key']  ) ? trim($src_data['search_key'])  : '';

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
                    if (!empty($sid)) {
                        $where_clauses[] = "sid = ?";
                        $params[] = $sid;
                        $types .= "s";
                    }
                    if (!empty($pc_mac)) {
                        $where_clauses[] = "pc_mac = ?";
                        $params[] = $pc_mac;
                        $types .= "s";
                    }
                    if (!empty($asset_no)) {
                        $where_clauses[] = "asset_no = ?";
                        $params[] = $asset_no;
                        $types .= "s";
                    }
                    if (!empty($device_type)) {
                        $where_clauses[] = "device_type = ?";
                        $params[] = $device_type;
                        $types .= "s";
                    }
                    if (!empty($device_name)) {
                        $where_clauses[] = "device_name LIKE ?";
                        $params[] = "%" . $device_name . "%";
                        $types .= "s";
                    }
                    if (!empty($search_key)) {
                        $where_clauses[] = "(device_name LIKE ? OR asset_no LIKE ? OR tag LIKE ?)";
                        $params[] = "%" . $search_key . "%";
                        $params[] = "%" . $search_key . "%";
                        $params[] = "%" . $search_key . "%";
                        $types .= "sss";
                    }

                    $sql = "SELECT id, sid, pc_mac, asset_no, device_type, device_name, receive_mode, tag, 
                                   port_name, baudrate, data_bits, parity, stop_bit, handshake, hk, 
                                   status, sort_order, remark, created_at, updated_at 
                            FROM $tableMain 
                            WHERE " . implode(" AND ", $where_clauses) . " 
                            ORDER BY id ASC";
                            // ORDER BY sort_order ASC, id ASC";

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
                            $data = result_message("true", "0x0200", "取得 $caption 成功", $query_rows_tmp);
                        } else {
                            $data = result_message("true", "0x0204", "查無 $caption 資料", $null_array);
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $data = result_message("false", "0x0209", "SQL Prepare 失敗", $null_array);
                    }
                    break;

                // ==========================================
                // 2. 新增或更新設備資料 (POST - 支援單/多筆)
                // ==========================================
                case "insert":
                    $items = isset($src_data[0]) && is_array($src_data[0]) ? $src_data : [$src_data];
                    $processed_results = [];
                    $has_error = false;

                    foreach ($items as $item) {
                        $who_call     = isset($item['who_call']    ) ? $item['who_call']     : 'app';
                        $sid          = isset($item['sid']         ) ? trim($item['sid'])    : '';
                        $pc_mac       = isset($item['pc_mac']      ) ? trim($item['pc_mac']) : null;
                        $asset_no     = isset($item['asset_no']    ) ? trim($item['asset_no']): '';
                        $device_type  = isset($item['device_type'] ) ? trim($item['device_type']) : '';
                        $device_name  = isset($item['device_name'] ) ? trim($item['device_name']) : '';

                        // 驗證必填欄位
                        if (empty($asset_no) || empty($device_name)) {
                            $has_error = true;
                            $processed_results[] = [
                                'sid'     => $sid,
                                'status'  => 'false',
                                'message' => '新增失敗，[asset_no] 與 [device_name] 為必填欄位！'
                            ];
                            continue;
                        }

                        // 取得 default_device 預設對應配置
                        $def_config   = $default_device_map[$device_name] ?? array();

                        $receive_mode = isset($item['receive_mode']) ? $item['receive_mode'] : ($def_config['receive_mode'] ?? null);
                        $device_type  = isset($item['device_type'] ) ? $item['device_type']  : ($def_config['device_type']  ?? null);
                        $tag          = isset($item['tag']         ) ? $item['tag']          : ($def_config['tag']          ?? null);
                        $port_name    = isset($item['port_name']   ) ? $item['port_name']    : ($def_config['port_name']    ?? 'NONE');
                        $baudrate     = isset($item['baudrate']    ) ? intval($item['baudrate'])  : intval($def_config['baudrate'] ?? 9600);
                        $data_bits    = isset($item['data_bits']   ) ? intval($item['data_bits']) : intval($def_config['data_bits'] ?? 8);
                        $parity       = isset($item['parity']      ) ? $item['parity']       : ($def_config['parity']       ?? 'NONE');
                        $stop_bit     = isset($item['stop_bit']    ) ? $item['stop_bit']     : ($def_config['stop_bit']     ?? '1');
                        $handshake    = isset($item['handshake']   ) ? $item['handshake']    : ($def_config['handshake']    ?? 'NONE');
                        $hk           = isset($item['hk']          ) ? $item['hk']           : ($def_config['hk']           ?? null);
                        $status       = isset($item['status']      ) ? intval($item['status'])  : 1;
                        $sort_order   = isset($item['sort_order']  ) ? intval($item['sort_order']): 0;
                        $remark       = isset($item['remark']      ) ? $item['remark']       : null;

                        // 檢查 asset_no 是否已存在 (若有傳 device_type 且不為空才一起比對)
                        $chk_sql = "SELECT * FROM $tableMain WHERE asset_no = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $asset_no);
                        mysqli_stmt_execute($chk_stmt);
                        $chk_res = mysqli_stmt_get_result($chk_stmt);

                        if ($chk_res && mysqli_num_rows($chk_res) > 0) {
                            // -----------------------------
                            // 資料已存在 -> 執行更新 (UPDATE)
                            // -----------------------------
                            $exist_data = mysqli_fetch_assoc($chk_res);
                            $exist_id   = $exist_data['id'];
                            $target_sid = !empty($sid) ? $sid : $exist_data['sid'];
                            mysqli_stmt_close($chk_stmt);

                            $update_sql = "UPDATE $tableMain 
                                           SET sid = ?, pc_mac = ?, asset_no = ?, device_type = ?, device_name = ?, 
                                               receive_mode = ?, tag = ?, port_name = ?, baudrate = ?, data_bits = ?, 
                                               parity = ?, stop_bit = ?, handshake = ?, hk = ?, status = ?, 
                                               sort_order = ?, remark = ?, updated_at = NOW() 
                                           WHERE id = ?";
                            
                            $up_stmt = mysqli_prepare($link, $update_sql);
                            // 綁定型態: ssssssssiissssiiii (18 個參數)
                            mysqli_stmt_bind_param($up_stmt, "ssssssssiissssiiii", 
                                $target_sid, $pc_mac, $asset_no, $device_type, $device_name, 
                                $receive_mode, $tag, $port_name, $baudrate, $data_bits, 
                                $parity, $stop_bit, $handshake, $hk, $status, 
                                $sort_order, $remark, $exist_id
                            );
                            $exec_up = mysqli_stmt_execute($up_stmt);
                            $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                            mysqli_stmt_close($up_stmt);

                            if ($exec_up && $affected_rows >= 0) {
                                $processed_results[] = ['id' => $exist_id, 'sid' => $target_sid, 'action' => 'UPDATE', 'status' => 'true'];

                                writeDeviceLog(
                                    $link, $tableLog, $exist_id, $target_sid, $pc_mac, $asset_no, 
                                    $device_type, $device_name, 'UPDATE', 
                                    ['before' => $exist_data, 'after' => $item], 
                                    $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                                );
                            } else {
                                $has_error = true;
                                $processed_results[] = [
                                    'sid' => $target_sid, 
                                    'status' => 'false', 
                                    'message' => '更新失敗：' . mysqli_error($link)
                                ];
                            }
                        } else {
                            // -----------------------------
                            // 資料不存在 -> 執行新增 (INSERT)
                            // -----------------------------
                            if ($chk_stmt) mysqli_stmt_close($chk_stmt);

                            $generated_sid = !empty($sid) ? $sid : ('DEV_' . substr(md5(uniqid(mt_rand(), true)), 0, 12));
                            $insert_sql = "INSERT INTO $tableMain 
                                           (sid, pc_mac, asset_no, device_type, device_name, receive_mode, tag, port_name, baudrate, data_bits, parity, stop_bit, handshake, hk, status, sort_order, remark, created_at) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $in_stmt = mysqli_prepare($link, $insert_sql);
                            // 綁定型態: ssssssssiissssiii (17 個參數)
                            // 1.sid(s) 2.pc_mac(s) 3.asset_no(s) 4.device_type(s) 5.device_name(s) 
                            // 6.receive_mode(s) 7.tag(s) 8.port_name(s) 9.baudrate(i) 10.data_bits(i) 
                            // 11.parity(s) 12.stop_bit(s) 13.handshake(s) 14.hk(s) 15.status(i) 16.sort_order(i) 17.remark(s)
                            mysqli_stmt_bind_param($in_stmt, "ssssssssiissssiii", 
                                $generated_sid, $pc_mac, $asset_no, $device_type, $device_name, 
                                $receive_mode, $tag, $port_name, $baudrate, $data_bits, 
                                $parity, $stop_bit, $handshake, $hk, $status, $sort_order, $remark
                            );
                            $exec_in = mysqli_stmt_execute($in_stmt);

                            if ($exec_in && mysqli_stmt_affected_rows($in_stmt) > 0) {
                                $new_id = mysqli_insert_id($link);
                                mysqli_stmt_close($in_stmt);

                                $processed_results[] = ['id' => $new_id, 'sid' => $generated_sid, 'action' => 'INSERT', 'status' => 'true'];

                                writeDeviceLog(
                                    $link, $tableLog, $new_id, $generated_sid, $pc_mac, $asset_no, 
                                    $device_type, $device_name, 'INSERT', 
                                    $item, 
                                    $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                                );
                            } else {
                                $err_msg = $in_stmt ? mysqli_stmt_error($in_stmt) : mysqli_error($link);
                                if ($in_stmt) mysqli_stmt_close($in_stmt);
                                $has_error = true;
                                $processed_results[] = [
                                    'sid' => $generated_sid, 
                                    'status' => 'false', 
                                    'message' => '新增失敗：' . $err_msg
                                ];
                            }
                        }
                    }

                    $status_flag = $has_error ? "false" : "true";
                    $code_flag = $has_error ? "0x0206" : "0x0200";
                    $msg_flag = $has_error ? "部分或全部 $caption 處理失敗" : "處理 $caption 成功";
                    $data = result_message($status_flag, $code_flag, $msg_flag, $processed_results);
                    break;

                // ==========================================
                // 3. 部分更新設備資料 (PUT / PATCH)
                // ==========================================
                case "edit":
                    $who_call     = isset($src_data['who_call']    ) ? $src_data['who_call']     : 'app';
                    $sid          = isset($src_data['sid']         ) ? trim($src_data['sid'])    : '';
                    $pc_mac       = isset($src_data['pc_mac']      ) ? trim($src_data['pc_mac']) : null;
                    $asset_no     = isset($src_data['asset_no']    ) ? trim($src_data['asset_no']): '';
                    $device_type  = isset($src_data['device_type'] ) ? trim($src_data['device_type']) : '';
                    $device_name  = isset($src_data['device_name'] ) ? trim($src_data['device_name']) : '';
                    $tag          = isset($src_data['tag']         ) ? trim($src_data['tag']) : '';

                    if (empty($asset_no) || empty($device_name)) {
                        $data = result_message("false", "0x0206", "編輯失敗，必須提供 [asset_no] 與 [device_name] ！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    

                    // 取得 default_device 預設對應配置
                    $def_config   = $default_device_map[$device_name] ?? array();

                    $receive_mode = isset($src_data['receive_mode']) ? $src_data['receive_mode'] : ($def_config['receive_mode'] ?? null);
                    $device_type  = isset($src_data['device_type'] ) ? $src_data['device_type']  : ($def_config['device_type']  ?? null);
                    $tag          = isset($src_data['tag']         ) ? $src_data['tag']          : ($def_config['tag']          ?? null);
                    $port_name    = isset($src_data['port_name']   ) ? $src_data['port_name']    : ($def_config['port_name']    ?? 'NONE');
                    $baudrate     = isset($src_data['baudrate']    ) ? intval($src_data['baudrate'])  : intval($def_config['baudrate'] ?? 9600);
                    $data_bits    = isset($src_data['data_bits']   ) ? intval($src_data['data_bits']) : intval($def_config['data_bits'] ?? 8);
                    $parity       = isset($src_data['parity']      ) ? $src_data['parity']       : ($def_config['parity']       ?? 'NONE');
                    $stop_bit     = isset($src_data['stop_bit']    ) ? $src_data['stop_bit']     : ($def_config['stop_bit']     ?? '1');
                    $handshake    = isset($src_data['handshake']   ) ? $src_data['handshake']    : ($def_config['handshake']    ?? 'NONE');
                    $hk           = isset($src_data['hk']          ) ? $src_data['hk']           : ($def_config['hk']           ?? null);
                    $status       = isset($src_data['status']      ) ? intval($src_data['status'])  : 1;
                    $sort_order   = isset($src_data['sort_order']  ) ? intval($src_data['sort_order']): 0;
                    $remark       = isset($src_data['remark']      ) ? $src_data['remark']       : null;

                    // 檢查 asset_no 是否已存在 (若有傳 device_type 且不為空才一起比對)
                    $chk_sql = "SELECT * FROM $tableMain WHERE asset_no = ? LIMIT 1";
                    $chk_stmt = mysqli_prepare($link, $chk_sql);
                    mysqli_stmt_bind_param($chk_stmt, "s", $asset_no);
                    mysqli_stmt_execute($chk_stmt);
                    $chk_res = mysqli_stmt_get_result($chk_stmt);

                    if (!$chk_res || mysqli_num_rows($chk_res) == 0) {
                        if ($chk_stmt) mysqli_stmt_close($chk_stmt);
                        $data = result_message("false", "0x0206", "編輯失敗，找不到指定的 $caption ！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    } else {
                        // -----------------------------
                        // 資料已存在 -> 執行更新 (UPDATE)
                        // -----------------------------
                        $exist_data = mysqli_fetch_assoc($chk_res);
                        $exist_id   = $exist_data['id'];
                        $target_sid = !empty($sid) ? $sid : $exist_data['sid'];
                        mysqli_stmt_close($chk_stmt);

                        $update_sql = "UPDATE $tableMain 
                                        SET sid = ?, pc_mac = ?, asset_no = ?, device_type = ?, device_name = ?, 
                                            receive_mode = ?, tag = ?, port_name = ?, baudrate = ?, data_bits = ?, 
                                            parity = ?, stop_bit = ?, handshake = ?, hk = ?, status = ?, 
                                            sort_order = ?, remark = ?, updated_at = NOW() 
                                        WHERE id = ?";
                        
                        $up_stmt = mysqli_prepare($link, $update_sql);
                        // 綁定型態: ssssssssiissssiiii (18 個參數)
                        mysqli_stmt_bind_param($up_stmt, "ssssssssiissssiiii", 
                            $target_sid, $pc_mac, $asset_no, $device_type, $device_name, 
                            $receive_mode, $tag, $port_name, $baudrate, $data_bits, 
                            $parity, $stop_bit, $handshake, $hk, $status, 
                            $sort_order, $remark, $exist_id
                        );
                        $exec_up = mysqli_stmt_execute($up_stmt);
                        $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                        mysqli_stmt_close($up_stmt);

                        if ($exec_up && $affected_rows >= 0) {
                            $processed_results[] = ['id' => $exist_id, 'sid' => $target_sid, 'action' => 'UPDATE', 'status' => 'true'];

                            writeDeviceLog(
                                $link, $tableLog, $exist_id, $target_sid, $pc_mac, $asset_no, 
                                $device_type, $device_name, 'UPDATE', 
                                ['before' => $exist_data, 'after' => $item], 
                                $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                            );
                        } else {
                            $has_error = true;
                            $processed_results[] = [
                                'sid' => $target_sid, 
                                'status' => 'false', 
                                'message' => '更新失敗：' . mysqli_error($link)
                            ];
                        }
                    }
                    break;

                // ==========================================
                // 4. 刪除設備資料 (DELETE)
                // ==========================================
                case "delete":
                    $who_call     = isset($src_data['who_call']    ) ? $src_data['who_call']     : 'app';
                    $pc_mac       = isset($src_data['pc_mac']      ) ? trim($src_data['pc_mac']) : null;
                    $asset_no     = isset($src_data['asset_no']    ) ? trim($src_data['asset_no']): '';
                    $device_type  = isset($src_data['device_type'] ) ? trim($src_data['device_type']) : '';
                    $device_name  = isset($src_data['device_name'] ) ? trim($src_data['device_name']) : '';
                    $device_name  = isset($src_data['device_name'] ) ? trim($src_data['device_name']) : '';
                    
                    if (empty($asset_no) || empty($device_name)) {
                        $data = result_message("false", "0x0206", "刪除失敗，必須提供 [asset_no] 與 [device_name]！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    // 檢查目標是否存在
                    if (strlen($asset_no) > 0) {
                        $chk_sql = "SELECT * FROM $tableMain WHERE asset_no = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $asset_no);
                    }

                    mysqli_stmt_execute($chk_stmt);
                    $chk_res = mysqli_stmt_get_result($chk_stmt);

                    if (!$chk_res || mysqli_num_rows($chk_res) == 0) {
                        if ($chk_stmt) mysqli_stmt_close($chk_stmt);
                        $data = result_message("false", "0x0206", "刪除失敗，找不到指定的設備序號 $asset_no", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $target_data = mysqli_fetch_assoc($chk_res);
                    $target_id = $target_data['id'];
                    mysqli_stmt_close($chk_stmt);

                    // 執行刪除
                    $del_sql = "DELETE FROM $tableMain WHERE asset_no = ?";
                    $del_stmt = mysqli_prepare($link, $del_sql);
                    mysqli_stmt_bind_param($del_stmt, "s", $asset_no);
                    $exec_del = mysqli_stmt_execute($del_stmt);
                    $affected_rows = mysqli_stmt_affected_rows($del_stmt);
                    mysqli_stmt_close($del_stmt);

                    if ($exec_del && $affected_rows > 0) {
                        $data = result_message("true", "0x0200", "刪除 $caption 成功", ['id' => $target_id]);

                        // 寫入異動 Log
                        writeDeviceLog(
                            $link, $tableLog, $target_id, $target_data['sid'], $target_data['pc_mac'], 
                            $target_data['asset_no'], $target_data['device_type'], $target_data['device_name'], 
                            'DELETE', 
                            $target_data, 
                            $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                        );
                    } else {
                        $null_array["err"] = mysqli_error($link);
                        $data = result_message("false", "0x0206", "刪除 $caption 失敗", $null_array);
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $data = result_message("false", "0x0209", "Exception error!", $null_array);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if (isset($data_close_conn["status"]) && $data_close_conn["status"] == "false") {
            $data = $data_close_conn;
        }
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>