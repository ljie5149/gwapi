<?php
    /*************************************************/
    /*                                               */
    /*                measure 資料操作                */
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
        // 相容 multipart/form-data (由 $_POST 接收) 與 application/json (由 php://input 接收)
        if (!empty($_POST)) {
            $src_data = $_POST;
        } else {
            $post_data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE && !empty($json)) {
                $data = result_message("false", "0x020E", "JSON decode error: " . json_last_error_msg(), []);
                echo json_encode($data, JSON_UNESCAPED_UNICODE);
                return;
            }
            if (is_array($post_data)) $src_data = $post_data;
        }
    } else if ($method == 'PATCH' || $method == 'PUT') {
        $opt = "edit";
        $patch_data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($patch_data)) {
            $src_data = $patch_data;
        } else {
            $src_data = $_POST; // 相容於 Form Data 的 PUT/PATCH 請求
        }
    } else if ($method == 'DELETE') {
        $opt = "delete";
        $delete_data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($delete_data)) {
            $src_data = $delete_data;
        } else {
            $src_data = $_GET;
        }
    }

    $API_name   = 'JTG_measure';
    $remote_ip  = get_remote_ip();
    $null_array = array();
    $caption    = "健檢量測資料";

    // 限制允許使用的資料表名稱，防止表名拼接導向 SQL 注入風險
    $tableMain = 'data_measure';
    $tableLog  = 'log_measure';
    
    if ($opt != 'get' && empty($src_data) && empty($_FILES)) {
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
     * 寫入 log_measure 紀錄表
     */
    function writeMeasureLog($link, $tableLog, $facilityId, $measureId, $sid, $measureNo, $deviceNo, $machineModel, $actionType, $changeData, $actionUser, $actionIp, $actionNote) {
        // 白名單驗證表名，避免非預期拼接 SQL 注入
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

    $db = new CXDB($remote_ip);
    $link = null;
    try {
        $conn_res = $db->connect($link, $member_id, "");

        if ($conn_res["status"] == "true") {
            switch ($opt) {
                // ==========================================
                // 1. 查詢量測資料 (GET)
                // ==========================================
                case "get":
                    $id            = isset($src_data['id']           ) && is_numeric($src_data['id']) ? intval($src_data['id']) : 0;
                    $sid           = isset($src_data['sid']          ) ? trim($src_data['sid'])  : '';
                    $facility_id   = isset($src_data['facility_id']  ) && is_numeric($src_data['facility_id']) ? intval($src_data['facility_id']) : 0;
                    $measure_no    = isset($src_data['measure_no']   ) ? trim($src_data['measure_no']) : '';
                    $asset_no     = isset($src_data['asset_no']    ) ? trim($src_data['asset_no'])  : '';
                    $machine_model = isset($src_data['machine_model']) ? trim($src_data['machine_model']) : '';
                    $online_type   = isset($src_data['online_type']  ) ? trim($src_data['online_type'])  : '';
                    $is_uploaded   = isset($src_data['is_uploaded']  ) && is_numeric($src_data['is_uploaded']) ? intval($src_data['is_uploaded']) : null;
                    $start_date    = isset($src_data['start_date']   ) ? trim($src_data['start_date']) : '';
                    $end_date      = isset($src_data['end_date']     ) ? trim($src_data['end_date'])   : '';

                    $where_clauses = ["1=1"];
                    $params = [];
                    $types = "";

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
                    if ($facility_id > 0) {
                        $where_clauses[] = "facility_id = ?";
                        $params[] = $facility_id;
                        $types .= "i";
                    }
                    if (!empty($measure_no)) {
                        $where_clauses[] = "measure_no = ?";
                        $params[] = $measure_no;
                        $types .= "s";
                    }
                    if (!empty($asset_no)) {
                        $where_clauses[] = "asset_no = ?";
                        $params[] = $asset_no;
                        $types .= "s";
                    }
                    if (!empty($machine_model)) {
                        $where_clauses[] = "machine_model = ?";
                        $params[] = $machine_model;
                        $types .= "s";
                    }
                    if (!empty($online_type)) {
                        $where_clauses[] = "online_type = ?";
                        $params[] = $online_type;
                        $types .= "s";
                    }
                    if ($is_uploaded !== null) {
                        $where_clauses[] = "is_uploaded = ?";
                        $params[] = $is_uploaded;
                        $types .= "i";
                    }
                    if (!empty($start_date)) {
                        $where_clauses[] = "measure_date >= ?";
                        $params[] = $start_date;
                        $types .= "s";
                    }
                    if (!empty($end_date)) {
                        $where_clauses[] = "measure_date <= ?";
                        $params[] = $end_date;
                        $types .= "s";
                    }

                    // 回傳欄位含檢測人員相關欄位與檔案資料
                    $sql = "SELECT id, sid, facility_id, measure_no, asset_no, machine_model, asset_no, 
                                   online_type, is_uploaded, json_data, raw_data, file_name, mime_type, file_size, 
                                   tester_identifier, tester_work_id, tester_name, editor, measure_count, 
                                   measure_date, up_json_data, remark, created_at, updated_at 
                            FROM `$tableMain` 
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
                // 2. 新增或更新量測資料 (POST - 支援 Binary 檔案)
                // ==========================================
                case "insert":
                    $items = isset($src_data[0]) && is_array($src_data[0]) ? $src_data : [$src_data];
                    $processed_results = [];
                    $has_error = false;

                    foreach ($items as $item) {
                        $who_call           = isset($item['who_call'            ]) ? $item['who_call'               ]      : 'app';
                        $sid                = isset($item['sid'                 ]) ? trim($item['sid'               ])     : '';

                        $tester_identifier  = isset($item['tester_identifier'   ]) ? trim($item['tester_identifier' ])     : '';
                        $tester_work_id     = isset($item['tester_work_id'      ]) ? trim($item['tester_work_id'    ])     : '';
                        $tester_name        = isset($item['tester_name'         ]) ? trim($item['tester_name'       ])     : '';
                        $editor             = isset($item['editor'              ]) ? trim($item['editor'            ])     : '';

                        $facility_id   = isset($item['facility_id']   ) ? intval($item['facility_id']) : 0;
                        $measure_no    = isset($item['measure_no']    ) ? trim($item['measure_no'])  : '';
                        $asset_no     = isset($item['asset_no']     ) ? trim($item['asset_no'])   : '';
                        $machine_model = isset($item['machine_model'] ) ? trim($item['machine_model']) : '';
                        $online_type   = isset($item['online_type']  ) ? trim($item['online_type'])  : 'ON-LINE';
                        $is_uploaded   = isset($item['is_uploaded']  ) ? intval($item['is_uploaded']) : 0;
                        $json_data     = isset($item['json_data']    ) ? (is_array($item['json_data']) ? json_encode($item['json_data'], JSON_UNESCAPED_UNICODE) : $item['json_data']) : null;
                        $raw_data      = isset($item['raw_data']     ) ? $item['raw_data']      : null;
                        $measure_date  = isset($item['measure_date'] ) ? $item['measure_date']  : date('Y-m-d H:i:s');
                        $up_json_data  = isset($item['up_json_data'] ) ? (is_array($item['up_json_data']) ? json_encode($item['up_json_data'], JSON_UNESCAPED_UNICODE) : $item['up_json_data']) : null;
                        $remark        = isset($item['remark']       ) ? $item['remark']        : null;

                        // 判斷並接收透過 $_FILES 上傳的 Binary 檔案
                        $file_binary = null;
                        $file_name   = isset($item['file_name']) ? $item['file_name'] : null;
                        $mime_type   = isset($item['mime_type']) ? $item['mime_type'] : null;
                        $file_size   = isset($item['file_size']) ? intval($item['file_size']) : 0;

                        if (isset($_FILES['file_data']) && $_FILES['file_data']['error'] === UPLOAD_ERR_OK) {
                            $file_name   = !empty($file_name) ? $file_name : $_FILES['file_data']['name'];
                            $mime_type   = !empty($mime_type) ? $mime_type : $_FILES['file_data']['type'];
                            $file_size   = ($_FILES['file_data']['size'] > 0) ? $_FILES['file_data']['size'] : $file_size;
                            $file_binary = file_get_contents($_FILES['file_data']['tmp_name']);
                        }

                        // 驗證必填欄位
                        if ($facility_id <= 0 || empty($measure_no) || empty($asset_no) || empty($machine_model) || empty($measure_date)) {
                            $has_error = true;
                            $processed_results[] = [
                                'sid'     => $sid,
                                'status'  => 'false',
                                'message' => '新增失敗，[facility_id]、[measure_no]、[asset_no]、[machine_model] 與 [measure_date] 為必填欄位！'
                            ];
                            continue;
                        }

                        // 檢查 sid 是否已存在
                        $chk_sql = "SELECT * FROM `$tableMain` WHERE sid = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $sid);
                        mysqli_stmt_execute($chk_stmt);
                        $chk_res = mysqli_stmt_get_result($chk_stmt);
                        $always_insert = true;
                        if ($chk_res && mysqli_num_rows($chk_res) > 0 && $always_insert == false) {
                            // -----------------------------
                            // 資料已存在 -> 執行更新 (UPDATE / REUPLOAD)
                            // -----------------------------
                            $exist_data = mysqli_fetch_assoc($chk_res);
                            $exist_id   = $exist_data['id'];
                            mysqli_stmt_close($chk_stmt);

                            // 若沒帶入新檔案則保留原本的檔案相關資訊，防止被覆蓋為 NULL
                            if ($file_binary === null) {
                                $update_sql = "UPDATE `$tableMain` 
                                               SET sid = ?, facility_id = ?, measure_no = ?, asset_no = ?, machine_model = ?, 
                                                   online_type = ?, is_uploaded = ?, json_data = ?, raw_data = ?, 
                                                   tester_identifier = ?, tester_work_id = ?, tester_name = ?, editor = ?,
                                                   measure_date = ?, up_json_data = ?, remark = ?, updated_at = NOW() 
                                               WHERE id = ?";
                                
                                $up_stmt = mysqli_prepare($link, $update_sql);
                                mysqli_stmt_bind_param($up_stmt, "sissssissonsssssi", 
                                    $sid, $facility_id, $measure_no, $asset_no, $machine_model, 
                                    $online_type, $is_uploaded, $json_data, $raw_data, 
                                    $tester_identifier, $tester_work_id, $tester_name, $editor,
                                    $measure_date, $up_json_data, $remark, $exist_id
                                );
                            } else {
                                $update_sql = "UPDATE `$tableMain` 
                                               SET sid = ?, facility_id = ?, measure_no = ?, asset_no = ?, machine_model = ?, 
                                                   online_type = ?, is_uploaded = ?, json_data = ?, raw_data = ?, file_name = ?, mime_type = ?, file_size = ?, 
                                                   file_data = ?, tester_identifier = ?, tester_work_id = ?, tester_name = ?, editor = ?,
                                                   measure_date = ?, up_json_data = ?, remark = ?, updated_at = NOW() 
                                               WHERE id = ?";
                                
                                $up_stmt = mysqli_prepare($link, $update_sql);
                                $null_placeholder = NULL;
                                mysqli_stmt_bind_param($up_stmt, "sissssissibssssssssi", 
                                    $sid, $facility_id, $measure_no, $asset_no, $machine_model, 
                                    $online_type, $is_uploaded, $json_data, $raw_data, $file_name, $mime_type, $file_size, 
                                    $null_placeholder, $tester_identifier, $tester_work_id, $tester_name, $editor,
                                    $measure_date, $up_json_data, $remark, $exist_id
                                );
                                mysqli_stmt_send_long_data($up_stmt, 12, $file_binary);
                            }

                            $exec_up = mysqli_stmt_execute($up_stmt);
                            $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                            mysqli_stmt_close($up_stmt);

                            if ($exec_up && $affected_rows >= 0) {
                                $processed_results[] = ['id' => $exist_id, 'sid' => $sid, 'action' => 'UPDATE', 'status' => 'true'];

                                $log_before = $exist_data; unset($log_before['file_data']);
                                $log_after = $item; unset($log_after['file_data']);

                                writeMeasureLog(
                                    $link, $tableLog, $facility_id, $exist_id, $sid, $measure_no, 
                                    $asset_no, $machine_model, 'REUPLOAD', 
                                    ['before' => $log_before, 'after' => $log_after], 
                                    $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                                );
                            } else {
                                $has_error = true;
                                $processed_results[] = ['sid' => $sid, 'status' => 'false', 'message' => '更新失敗'];
                            }
                        } else {
                            // -----------------------------
                            // 資料不存在 -> 執行新增 (INSERT)
                            // -----------------------------
                            if ($chk_stmt) mysqli_stmt_close($chk_stmt);

                            $generated_sid = !empty($sid) ? $sid : ('MD_' . substr(md5(uniqid(mt_rand(), true)), 0, 12));
                            $insert_sql = "INSERT INTO `$tableMain` 
                                           (sid, facility_id, measure_no, asset_no, machine_model, online_type, is_uploaded, json_data, raw_data, file_name, mime_type, file_size, file_data, tester_identifier, tester_work_id, tester_name, editor, measure_date, up_json_data, remark, created_at) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $in_stmt = mysqli_prepare($link, $insert_sql);
                            
                            $null_placeholder = NULL;
                            mysqli_stmt_bind_param($in_stmt, "sissssissibsssssssss", 
                                $generated_sid, $facility_id, $measure_no, $asset_no, $machine_model, 
                                $online_type, $is_uploaded, $json_data, $raw_data, $file_name, $mime_type, $file_size, 
                                $null_placeholder, $tester_identifier, $tester_work_id, $tester_name, $editor,
                                $measure_date, $up_json_data, $remark
                            );

                            if ($file_binary !== null) {
                                mysqli_stmt_send_long_data($in_stmt, 12, $file_binary);
                            }

                            $exec_in = mysqli_stmt_execute($in_stmt);

                            if ($exec_in && mysqli_stmt_affected_rows($in_stmt) > 0) {
                                $new_id = mysqli_insert_id($link);
                                mysqli_stmt_close($in_stmt);

                                $processed_results[] = ['id' => $new_id, 'sid' => $sid, 'action' => 'INSERT', 'status' => 'true'];

                                $log_item = $item; unset($log_item['file_data']);

                                writeMeasureLog(
                                    $link, $tableLog, $facility_id, $new_id, $sid, $measure_no, 
                                    $asset_no, $machine_model, 'INSERT', 
                                    $log_item, 
                                    $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                                );
                            } else {
                                if ($in_stmt) mysqli_stmt_close($in_stmt);
                                $has_error = true;
                                $processed_results[] = ['sid' => $sid, 'status' => 'false', 'message' => '新增失敗'];
                            }
                        }
                    }

                    $status_flag = $has_error ? "false" : "true";
                    $code_flag = $has_error ? "0x0206" : "0x0200";
                    $msg_flag = $has_error ? "部分或全部 $caption 處理失敗" : "處理 $caption 成功";
                    $data = result_message($status_flag, $code_flag, $msg_flag, $processed_results);
                    break;

                // ==========================================
                // 3. 部分更新量測資料 (PUT / PATCH - 支援 Binary 檔案)
                // ==========================================
                case "edit":
                    $who_call = isset($src_data['who_call']) ? $src_data['who_call'] : 'app';
                    $sid      = isset($src_data['sid']) ? trim($src_data['sid']) : '';

                    if (empty($sid)) {
                        $data = result_message("false", "0x0206", "編輯失敗，必須提供 [sid]！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    // 尋找目標記錄
                    $chk_sql = "SELECT * FROM `$tableMain` WHERE sid = ? LIMIT 1";
                    $chk_stmt = mysqli_prepare($link, $chk_sql);
                    mysqli_stmt_bind_param($chk_stmt, "s", $sid);
                    
                    mysqli_stmt_execute($chk_stmt);
                    $chk_res = mysqli_stmt_get_result($chk_stmt);

                    if (!$chk_res || mysqli_num_rows($chk_res) == 0) {
                        if ($chk_stmt) mysqli_stmt_close($chk_stmt);
                        $data = result_message("false", "0x0206", "編輯失敗，找不到指定的 $caption ！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $old_data = mysqli_fetch_assoc($chk_res);
                    $target_id = $old_data['id'];
                    mysqli_stmt_close($chk_stmt);

                    // 處理 $_FILES 檔案上傳
                    $file_binary = null;
                    if (isset($_FILES['file_data']) && $_FILES['file_data']['error'] === UPLOAD_ERR_OK) {
                        $src_data['file_name'] = !empty($src_data['file_name']) ? $src_data['file_name'] : $_FILES['file_data']['name'];
                        $src_data['mime_type'] = !empty($src_data['mime_type']) ? $src_data['mime_type'] : $_FILES['file_data']['type'];
                        $src_data['file_size'] = ($_FILES['file_data']['size'] > 0) ? $_FILES['file_data']['size'] : (isset($src_data['file_size']) ? $src_data['file_size'] : 0);
                        $file_binary = file_get_contents($_FILES['file_data']['tmp_name']);
                        $src_data['file_data'] = NULL; // 標示有檔案需被更新
                    }

                    // 動態組裝欄位（包含人員欄位與 JSON 序列化）
                    $update_fields = [];
                    $params = [];
                    $types = "";
                    $blob_param_index = -1;

                    $fields_map = [
                        'facility_id'       => 'i',
                        'measure_no'        => 's',
                        'asset_no'         => 's',
                        'machine_model'     => 's',
                        'online_type'       => 's',
                        'is_uploaded'       => 'i',
                        'json_data'         => 's',
                        'raw_data'          => 's',
                        'file_name'         => 's',
                        'mime_type'         => 's',
                        'file_size'         => 'i',
                        'file_data'         => 'b',
                        'tester_identifier' => 's',
                        'tester_work_id'    => 's',
                        'tester_name'       => 's',
                        'editor'            => 's',
                        'measure_date'      => 's',
                        'up_json_data'      => 's',
                        'remark'            => 's'
                    ];

                    $curr_index = 0;
                    foreach ($fields_map as $field => $type) {
                        if (array_key_exists($field, $src_data)) {
                            $update_fields[] = "`$field` = ?";
                            if ($type === 'b') {
                                $params[] = NULL;
                                $blob_param_index = $curr_index;
                            } else {
                                $val = $src_data[$field];
                                if (($field === 'json_data' || $field === 'up_json_data') && is_array($val)) {
                                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                                }
                                $params[] = ($type === 'i') ? intval($val) : $val;
                            }
                            $types .= $type;
                            $curr_index++;
                        }
                    }

                    if (empty($update_fields)) {
                        $data = result_message("false", "0x0206", "沒有需要更新的欄位內容！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $update_fields[] = "updated_at = NOW()";
                    $update_sql = "UPDATE `$tableMain` SET " . implode(", ", $update_fields) . " WHERE id = ?";
                    
                    $params[] = $target_id;
                    $types .= "i";

                    $up_stmt = mysqli_prepare($link, $update_sql);
                    mysqli_stmt_bind_param($up_stmt, $types, ...$params);

                    // 若有上傳新檔案則串流寫入 BLOB
                    if ($blob_param_index !== -1 && $file_binary !== null) {
                        mysqli_stmt_send_long_data($up_stmt, $blob_param_index, $file_binary);
                    }

                    $exec_up = mysqli_stmt_execute($up_stmt);
                    $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                    mysqli_stmt_close($up_stmt);

                    if ($exec_up && $affected_rows >= 0) {
                        $data = result_message("true", "0x0200", "更新 $caption 成功", ['id' => $target_id]);

                        $log_old = $old_data; unset($log_old['file_data']);
                        $log_payload = $src_data; unset($log_payload['file_data']);

                        writeMeasureLog(
                            $link, $tableLog, 
                            isset($src_data['facility_id']) ? intval($src_data['facility_id']) : $old_data['facility_id'], 
                            $target_id, $old_data['sid'], 
                            isset($src_data['measure_no']) ? $src_data['measure_no'] : $old_data['measure_no'], 
                            isset($src_data['asset_no']) ? $src_data['asset_no'] : $old_data['asset_no'], 
                            isset($src_data['machine_model']) ? $src_data['machine_model'] : $old_data['machine_model'], 
                            'UPDATE', 
                            ['before' => $log_old, 'update_payload' => $log_payload], 
                            $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                        );
                    } else {
                        $null_array["err"] = mysqli_error($link);
                        $data = result_message("false", "0x0206", "更新 $caption 失敗", $null_array);
                    }
                    break;

                // ==========================================
                // 4. 刪除量測資料 (DELETE)
                // ==========================================
                case "delete":
                    $id       = isset($src_data['id'        ]) && is_numeric($src_data['id']) ? intval($src_data['id']) : 0;
                    $sid      = isset($src_data['sid'       ]) ? trim($src_data['sid'])  : '';
                    $who_call = isset($src_data['who_call'  ]) ? $src_data['who_call']   : 'app';

                    if ($id <= 0 && empty($sid)) {
                        $data = result_message("false", "0x0206", "刪除失敗，必須提供 [id] 或 [sid]！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    if ($id > 0) {
                        $chk_sql = "SELECT * FROM `$tableMain` WHERE id = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "i", $id);
                    } else {
                        $chk_sql = "SELECT * FROM `$tableMain` WHERE sid = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $sid);
                    }

                    mysqli_stmt_execute($chk_stmt);
                    $chk_res = mysqli_stmt_get_result($chk_stmt);

                    if (!$chk_res || mysqli_num_rows($chk_res) == 0) {
                        if ($chk_stmt) mysqli_stmt_close($chk_stmt);
                        $data = result_message("false", "0x0206", "刪除失敗，找不到指定的 $caption ！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $target_data = mysqli_fetch_assoc($chk_res);
                    $target_id   = $target_data['id'];
                    mysqli_stmt_close($chk_stmt);

                    $del_sql = "DELETE FROM `$tableMain` WHERE id = ?";
                    $del_stmt = mysqli_prepare($link, $del_sql);
                    mysqli_stmt_bind_param($del_stmt, "i", $target_id);
                    $exec_del = mysqli_stmt_execute($del_stmt);
                    $affected_rows = mysqli_stmt_affected_rows($del_stmt);
                    mysqli_stmt_close($del_stmt);

                    if ($exec_del && $affected_rows > 0) {
                        $data = result_message("true", "0x0200", "刪除 $caption 成功", ['id' => $target_id]);

                        $log_target = $target_data; unset($log_target['file_data']);

                        writeMeasureLog(
                            $link, $tableLog, $target_data['facility_id'], $target_id, $target_data['sid'], 
                            $target_data['measure_no'], $target_data['asset_no'], $target_data['machine_model'], 
                            'DELETE', 
                            $log_target, 
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
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>