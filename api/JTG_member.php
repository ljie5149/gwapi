<?php
    /*************************************************/
    /*                                               */
    /*                 member 資料操作                */
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

    $API_name   = 'JTG_member';
    $remote_ip  = get_remote_ip();
    $null_array = array();
    $caption    = "會員資料";

    $tableMain = 'data_member';
    $tableLog  = 'log_member';
    
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
     * 寫入 log_member 紀錄表 (自動過濾 password 機密資料)
     */
    function writeMemberLog($link, $tableLog, $memberId, $sid, $account, $facilityId, $role, $actionType, $changeData, $actionUser, $actionIp, $actionNote) {
        $log_sql = "INSERT INTO $tableLog 
                    (member_id, sid, account, facility_id, role, action_type, change_data, action_user, action_ip, action_note, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($link, $log_sql);
        if ($stmt) {
            // 安全處理：去除 password 明文或雜湊值
            if (is_array($changeData)) {
                unset($changeData['password']);
                if (isset($changeData['before']) && is_array($changeData['before'])) {
                    unset($changeData['before']['password']);
                }
                if (isset($changeData['after']) && is_array($changeData['after'])) {
                    unset($changeData['after']['password']);
                }
                if (isset($changeData['update_payload']) && is_array($changeData['update_payload'])) {
                    unset($changeData['update_payload']['password']);
                }
            }
            $json_change = is_array($changeData) || is_object($changeData) ? json_encode($changeData, JSON_UNESCAPED_UNICODE) : $changeData;
            
            mysqli_stmt_bind_param($stmt, "ississssss", 
                $memberId, $sid, $account, $facilityId, $role, 
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
                // 1. 查詢會員資料 (GET)
                // ==========================================
                case "get":
                    $get_all     = isset($src_data['get_all']    ) ? $src_data['get_all']     : '0';
                    $id          = isset($src_data['id']         ) ? intval($src_data['id'])  : 0;
                    $sid         = isset($src_data['sid']        ) ? trim($src_data['sid'])   : '';
                    $account     = isset($src_data['account']    ) ? trim($src_data['account']): '';
                    $password_base64    = isset($src_data['password']   ) ? trim($src_data['password'])    : '';
                    $member_name = isset($src_data['member_name']) ? trim($src_data['member_name']) : '';
                    $facility_id = isset($src_data['facility_id']) ? intval($src_data['facility_id']) : 0;
                    $role        = isset($src_data['role']       ) ? trim($src_data['role'])  : '';

                    // 💡 檢查密碼必填
                    if (empty($account)) {
                        $data = result_message("false", "0x0206", "API parameter [account] is required!", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    // 💡 檢查密碼必填
                    if (empty($password_base64)) {
                        $data = result_message("false", "0x0206", "API parameter [password] is required!", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }
                    $password = base64_decode($password_base64);

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
                    if (!empty($account)) {
                        $where_clauses[] = "account = ?";
                        $params[] = $account;
                        $types .= "s";
                    }
                    if (!empty($member_name)) {
                        $where_clauses[] = "member_name LIKE ?";
                        $params[] = "%" . $member_name . "%";
                        $types .= "s";
                    }
                    if ($facility_id > 0) {
                        $where_clauses[] = "facility_id = ?";
                        $params[] = $facility_id;
                        $types .= "i";
                    }
                    if (!empty($role)) {
                        $where_clauses[] = "role = ?";
                        $params[] = $role;
                        $types .= "s";
                    }

                    // SELECT 時帶出 password 進行比對
                    $sql = "SELECT id, sid, account, password, member_name, facility_id, role, email, phone, status, last_login_at, remark, created_at, updated_at 
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
                            $has_pwd_matched = false;

                            while ($row = mysqli_fetch_assoc($result)) {
                                // 進行密碼雜湊比對
                                if (password_verify($password, $row['password'])) {
                                    $has_pwd_matched = true;
                                    unset($row['password']); // 移除敏感欄位
                                    array_push($query_rows_tmp, $row);
                                }
                            }

                            if (!empty($query_rows_tmp)) {
                                $data = result_message("true", "0x0200", "取得 $caption 成功", $query_rows_tmp);
                            } else {
                                // 查有帳號/資料但密碼驗證均失敗
                                $data = result_message("false", "0x0204", "密碼驗證錯誤！", $null_array);
                            }
                        } else {
                            $data = result_message("false", "0x0204", "查無 $caption 資料", $null_array);
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $data = result_message("false", "0x0209", "SQL Prepare 失敗", $null_array);
                    }
                    break;

                // ==========================================
                // 2. 新增或更新會員資料 (POST - 支援單筆與批次)
                // ==========================================
                case "insert":
                    $items = isset($src_data[0]) && is_array($src_data[0]) ? $src_data : [$src_data];
                    $processed_results = [];
                    $has_error = false;

                    foreach ($items as $item) {
                        $who_call    = isset($item['who_call']   ) ? $item['who_call']    : 'app';
                        $sid         = isset($item['sid']        ) ? trim($item['sid'])   : '';
                        $account     = isset($item['account']    ) ? trim($item['account']): '';
                        $password    = isset($item['password']   ) ? $item['password']    : '';
                        $member_name = isset($item['member_name']) ? trim($item['member_name']) : '';
                        $facility_id = isset($item['facility_id']) ? intval($item['facility_id']) : 0;
                        $role_val    = isset($item['role']       ) ? trim($item['role'])  : '';
                        $email       = isset($item['email']      ) ? trim($item['email']) : null;
                        $phone       = isset($item['phone']      ) ? trim($item['phone']) : null;
                        $status      = isset($item['status']     ) ? intval($item['status']) : 1;
                        $remark      = isset($item['remark']     ) ? $item['remark']      : null;

                        // 驗證必填欄位
                        if (empty($sid) || empty($account) || empty($member_name) || $facility_id <= 0 || empty($role_val)) {
                            $has_error = true;
                            $processed_results[] = [
                                'sid'     => $sid,
                                'status'  => 'false',
                                'message' => '新增失敗，[sid]、[account]、[member_name]、[facility_id] 與 [role] 為必填欄位！'
                            ];
                            continue;
                        }

                        // 檢查 sid 或 account 是否已存在
                        $chk_sql = "SELECT * FROM $tableMain WHERE sid = ? OR account = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "ss", $sid, $account);
                        mysqli_stmt_execute($chk_stmt);
                        $chk_res = mysqli_stmt_get_result($chk_stmt);

                        if ($chk_res && mysqli_num_rows($chk_res) > 0) {
                            // -----------------------------
                            // 資料已存在 -> 執行更新 (UPDATE)
                            // -----------------------------
                            $exist_data = mysqli_fetch_assoc($chk_res);
                            $exist_id   = $exist_data['id'];
                            mysqli_stmt_close($chk_stmt);

                            // 如果有傳入密碼則重新雜湊，否則維持原密碼
                            $password_hash = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : $exist_data['password'];

                            $update_sql = "UPDATE $tableMain 
                                           SET sid = ?, account = ?, password = ?, member_name = ?, facility_id = ?, 
                                               role = ?, email = ?, phone = ?, status = ?, remark = ?, updated_at = NOW() 
                                           WHERE id = ?";
                            
                            $up_stmt = mysqli_prepare($link, $update_sql);
                            mysqli_stmt_bind_param($up_stmt, "ssssisssisi", 
                                $sid, $account, $password_hash, $member_name, $facility_id, 
                                $role_val, $email, $phone, $status, $remark, $exist_id
                            );
                            $exec_up = mysqli_stmt_execute($up_stmt);
                            $affected_rows = mysqli_stmt_affected_rows($up_stmt);
                            mysqli_stmt_close($up_stmt);

                            if ($exec_up && $affected_rows >= 0) {
                                $processed_results[] = ['id' => $exist_id, 'sid' => $sid, 'account' => $account, 'action' => 'UPDATE', 'status' => 'true'];

                                // 寫入異動 Log
                                writeMemberLog(
                                    $link, $tableLog, $exist_id, $sid, $account, $facility_id, $role_val,
                                    'UPDATE', ['before' => $exist_data, 'after' => $item], 
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

                            if (empty($password)) {
                                $has_error = true;
                                $processed_results[] = ['sid' => $sid, 'status' => 'false', 'message' => '新增失敗，全新會員必須提供 [password]！'];
                                continue;
                            }

                            $password_hash = password_hash($password, PASSWORD_BCRYPT);

                            $insert_sql = "INSERT INTO $tableMain 
                                           (sid, account, password, member_name, facility_id, role, email, phone, status, remark, created_at) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $in_stmt = mysqli_prepare($link, $insert_sql);
                            mysqli_stmt_bind_param($in_stmt, "ssssisssis", 
                                $sid, $account, $password_hash, $member_name, $facility_id, 
                                $role_val, $email, $phone, $status, $remark
                            );
                            $exec_in = mysqli_stmt_execute($in_stmt);

                            if ($exec_in && mysqli_stmt_affected_rows($in_stmt) > 0) {
                                $new_id = mysqli_insert_id($link);
                                mysqli_stmt_close($in_stmt);

                                $processed_results[] = ['id' => $new_id, 'sid' => $sid, 'account' => $account, 'action' => 'INSERT', 'status' => 'true'];

                                // 寫入異動 Log
                                writeMemberLog(
                                    $link, $tableLog, $new_id, $sid, $account, $facility_id, $role_val,
                                    'INSERT', $item, 
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
                // 3. 部分更新會員資料 (PUT / PATCH)
                // ==========================================
                case "edit":
                    $id       = isset($src_data['id']     ) ? intval($src_data['id']) : 0;
                    $sid      = isset($src_data['sid']    ) ? trim($src_data['sid'])  : '';
                    $account  = isset($src_data['account']) ? trim($src_data['account']) : '';
                    $who_call = isset($src_data['who_call']) ? $src_data['who_call'] : 'app';

                    if ($id <= 0 && empty($sid) && empty($account)) {
                        $data = result_message("false", "0x0206", "編輯失敗，必須提供 [id]、[sid] 或 [account] 其中之一！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    // 尋找目標記錄
                    if ($id > 0) {
                        $chk_sql = "SELECT * FROM $tableMain WHERE id = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "i", $id);
                    } else if (!empty($sid)) {
                        $chk_sql = "SELECT * FROM $tableMain WHERE sid = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $sid);
                    } else {
                        $chk_sql = "SELECT * FROM $tableMain WHERE account = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $account);
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

                    // 動態組裝欄位
                    $update_fields = [];
                    $params = [];
                    $types = "";

                    $fields_map = [
                        'member_name'   => 's',
                        'facility_id'   => 'i',
                        'role'          => 's',
                        'email'         => 's',
                        'phone'         => 's',
                        'status'        => 'i',
                        'last_login_at' => 's',
                        'remark'        => 's'
                    ];

                    foreach ($fields_map as $field => $type) {
                        if (array_key_exists($field, $src_data)) {
                            $update_fields[] = "`$field` = ?";
                            $params[] = ($type === 'i') ? intval($src_data[$field]) : $src_data[$field];
                            $types .= $type;
                        }
                    }

                    // 獨立處理密碼修改 (雜湊)
                    if (isset($src_data['password']) && !empty($src_data['password'])) {
                        $update_fields[] = "`password` = ?";
                        $params[] = password_hash($src_data['password'], PASSWORD_BCRYPT);
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
                        $data = result_message("true", "0x0200", "更新 $caption 成功", ['id' => $target_id]);

                        // 寫入異動 Log
                        writeMemberLog(
                            $link, $tableLog, $target_id, $old_data['sid'], $old_data['account'],
                            isset($src_data['facility_id']) ? intval($src_data['facility_id']) : $old_data['facility_id'],
                            isset($src_data['role']) ? $src_data['role'] : $old_data['role'],
                            'UPDATE', ['before' => $old_data, 'update_payload' => $src_data], 
                            $member_id, $remote_ip, $who_call . ' 呼叫 api ' . $API_name
                        );
                    } else {
                        $null_array["err"] = mysqli_error($link);
                        $data = result_message("false", "0x0206", "更新 $caption 失敗", $null_array);
                    }
                    break;

                // ==========================================
                // 4. 刪除會員資料 (DELETE)
                // ==========================================
                case "delete":
                    $id       = isset($src_data['id']     ) ? intval($src_data['id']) : 0;
                    $sid      = isset($src_data['sid']    ) ? trim($src_data['sid'])  : '';
                    $account  = isset($src_data['account']) ? trim($src_data['account']) : '';
                    $who_call = isset($src_data['who_call']) ? $src_data['who_call'] : 'app';

                    if ($id <= 0 && empty($sid) && empty($account)) {
                        $data = result_message("false", "0x0206", "刪除失敗，必須提供 [id]、[sid] 或 [account]！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    // 檢查目標是否存在
                    if ($id > 0) {
                        $chk_sql = "SELECT * FROM $tableMain WHERE id = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "i", $id);
                    } else if (!empty($sid)) {
                        $chk_sql = "SELECT * FROM $tableMain WHERE sid = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $sid);
                    } else {
                        $chk_sql = "SELECT * FROM $tableMain WHERE account = ? LIMIT 1";
                        $chk_stmt = mysqli_prepare($link, $chk_sql);
                        mysqli_stmt_bind_param($chk_stmt, "s", $account);
                    }

                    mysqli_stmt_execute($chk_stmt);
                    $chk_res = mysqli_stmt_get_result($chk_stmt);

                    if (!$chk_res || mysqli_num_rows($chk_res) == 0) {
                        if ($chk_stmt) mysqli_stmt_close($chk_stmt);
                        $data = result_message("false", "0x0206", "刪除失敗，找不到指定的 $caption！", $null_array);
                        echo json_encode($data, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $target_data = mysqli_fetch_assoc($chk_res);
                    $target_id   = $target_data['id'];
                    mysqli_stmt_close($chk_stmt);

                    // 執行刪除
                    $del_sql = "DELETE FROM $tableMain WHERE id = ?";
                    $del_stmt = mysqli_prepare($link, $del_sql);
                    mysqli_stmt_bind_param($del_stmt, "i", $target_id);
                    $exec_del = mysqli_stmt_execute($del_stmt);
                    $affected_rows = mysqli_stmt_affected_rows($del_stmt);
                    mysqli_stmt_close($del_stmt);

                    if ($exec_del && $affected_rows > 0) {
                        $data = result_message("true", "0x0200", "刪除 $caption 成功", ['id' => $target_id]);

                        // 寫入異動 Log
                        writeMemberLog(
                            $link, $tableLog, $target_id, $target_data['sid'], $target_data['account'],
                            $target_data['facility_id'], $target_data['role'],
                            'DELETE', $target_data, 
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