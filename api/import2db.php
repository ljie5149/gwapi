<?php
    header('Content-Type: application/json');
    include("./../common/entry.php");

    global $g_xlsx_in_path, $g_fldidx_name, $g_fldidx_comment;

    $file_name   = $_POST['filename'] ?? '';
    $member_id   = $_POST['memberid'] ?? '';
    $caption     = $_POST['caption']  ?? '';
    $table       = $_POST['table']    ?? 'data_device';
    $remote_ip   = get_remote_ip();

    $db = new CXDB($remote_ip);

    try {
        $data = $db->connect($link, $member_id, "");
        if (($data["status"] ?? '') !== "true") {
            echo json_encode(result_message("false", "0x0201", "資料庫連線失敗", ""), JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($member_id) || empty($table) || (empty($_FILES['file']) && empty($_POST['base64_file']))) {
            $ret_str = "匯入Excel資料 [{$caption}] 異常，API 參數不全!";
            $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '上傳Excel檔案', $ret_str);
            echo json_encode(result_message("false", "0x0206", $ret_str, ''), JSON_UNESCAPED_UNICODE);
            return;
        }

        $insert_cnt = 0;
        $update_cnt = 0;
        $fail_cnt   = 0;
        $fail_list  = array();

        $db->modifyProgress($link, $member_id, $file_name, 0, "import");

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

        // 2. 載入目標資料表 (data_device) 的欄位註解對照表
        $column_info = $db->getTableColumnComments($link, $table);

        // 3. 解析上傳的 Excel 檔案
        $excel_rows = array(); // 存放 [ [欄位標題...], [列1...], [列2...] ]
        
        if (!empty($_FILES['file']['tmp_name'])) {
            $inputFileName = $_FILES['file']['tmp_name'];
            $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
            $sheet = $objPHPExcel->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];
                // 過濾全空列
                if (array_filter($rowData, function($val) { return !is_null($val) && trim($val) !== ''; })) {
                    $excel_rows[] = $rowData;
                }
            }
        }

        $total_records = count($excel_rows) - 1; // 扣除 Header

        if ($total_records <= 0) {
            $ret_str = "匯入失敗，檔案內無有效資料列！";
            echo json_encode(result_message("false", "0x0206", $ret_str, ''), JSON_UNESCAPED_UNICODE);
            return;
        }

        $header_row = $excel_rows[0];

        // 4. 逐列處理資料匯入
        for ($r = 1; $r <= $total_records; $r++) {
            $row_data = $excel_rows[$r];
            $row_num_display = $r + 1; // Excel 中的實際行數

            // 取得該列的 device_name
            $cur_device_name = '';
            $dev_name_col_idx = gfindStrInArray($header_row, '設備中文名稱');
            if ($dev_name_col_idx > -1 && isset($row_data[$dev_name_col_idx])) {
                $cur_device_name = trim((string)$row_data[$dev_name_col_idx]);
            }

            // 取得 default_device 預設對應配置
            $def_config = $default_device_map[$cur_device_name] ?? array();

            $insert_fields = array();
            $insert_values = array();
            $update_pairs  = array();
            $data_map      = array();
            $asset_no      = '';

            for ($m = 0; $m < count($column_info); $m++) {
                $com   = $column_info[$m];
                $field = $com[$g_fldidx_name];
                $name  = trim($com[$g_fldidx_comment], "'\" ");

                if (in_array($field, ['id', 'updated_at'])) continue;

                if ($field === 'sid') {
                    $generated_sid = 'DEV_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
                    $data_map['sid'] = $generated_sid;
                    continue;
                }

                if ($field === 'created_at') {
                    $data_map['created_at'] = date('Y-m-d H:i:s');
                    continue;
                }

                $found_idx = findStrInArray($header_row, $name);
                $excel_val = ($found_idx > -1 && isset($row_data[$found_idx])) ? trim((string)$row_data[$found_idx]) : '';

                // 帶入順序：Excel 值 -> default_device 帶入 (含 baudrate, hk 等) -> 系統預設值
                if ($excel_val !== '') {
                    $final_val = $excel_val;
                } else if (isset($def_config[$field]) && $def_config[$field] !== '' && !is_null($def_config[$field])) {
                    $final_val = $def_config[$field];
                } else {
                    if ($field === 'pc_mac') $final_val = 'WEB';
                    else if (in_array($field, ['port_name', 'parity', 'handshake'])) $final_val = 'NONE';
                    else if ($field === 'baudrate') $final_val = '9600';
                    else if ($field === 'data_bits') $final_val = '8';
                    else if (in_array($field, ['stop_bit', 'status'])) $final_val = '1';
                    else if ($field === 'sort_order') $final_val = '0';
                    else $final_val = '';
                }

                if ($field === 'asset_no') {
                    $asset_no = $final_val;
                }

                $data_map[$field] = $final_val;
                $insert_fields[] = "`{$field}`";
                $insert_values[] = "'" . addslashes($final_val) . "'";

                if ($field !== 'created_at' && $field !== 'sid') {
                    $update_pairs[] = "`{$field}` = '" . addslashes($final_val) . "'";
                }
            }

            // 檢查關鍵唯一欄位 asset_no
            if (empty($asset_no)) {
                $fail_cnt++;
                $fail_list[] = array('row' => $row_num_display, 'reason' => '缺少設備資產序號 (asset_no)');
                continue;
            }

            // 檢查該資產序號是否已存在
            $check_sql = "SELECT id, sid FROM `{$table}` WHERE asset_no = '" . addslashes($asset_no) . "' LIMIT 1;";
            $check_res = $db->query($link, $check_sql);

            $ret_msg = "";
            if (!is_null($check_res) && mysqli_num_rows($check_res) > 0) {
                // 已存在 -> 執行 UPDATE
                $existing_row = mysqli_fetch_assoc($check_res);
                $existing_id  = $existing_row['id'];
                $existing_sid = $existing_row['sid'];

                $update_sql = "UPDATE `{$table}` SET " . implode(', ', $update_pairs) . ", `updated_at` = NOW() WHERE id = '{$existing_id}';";
                $exec_res = $db->execute($link, $update_sql, $ret_msg);

                if ($exec_res !== false) {
                    $update_cnt++;
                    // 記錄變更 Log
                    $log_sql = "INSERT INTO log_device (device_id, sid, asset_no, action_type, change_data, action_user, action_ip, action_note, created_at, updated_at) 
                                VALUES ('{$existing_id}', '{$existing_sid}', '" . addslashes($asset_no) . "', 'UPDATE', '" . addslashes(json_encode($data_map, JSON_UNESCAPED_UNICODE)) . "', '{$member_id}', '{$remote_ip}', 'Excel 批次匯入更新', NOW(), NOW());";
                    $db->execute($link, $log_sql, $ret_msg);
                } else {
                    $fail_cnt++;
                    $fail_list[] = array('row' => $row_num_display, 'reason' => '更新資料庫失敗: ' . $ret_msg);
                }
            } else {
                // 不存在 -> 執行 INSERT
                $insert_fields[] = "`sid`";
                $insert_values[] = "'" . $data_map['sid'] . "'";
                $insert_fields[] = "`created_at`";
                $insert_values[] = "NOW()";

                $insert_sql = "INSERT INTO `{$table}` (" . implode(', ', $insert_fields) . ") VALUES (" . implode(', ', $insert_values) . ");";
                $new_id = $db->execute($link, $insert_sql, $ret_msg);

                if ($new_id > 0) {
                    $insert_cnt++;
                    // 記錄變更 Log
                    $log_sql = "INSERT INTO log_device (device_id, sid, asset_no, action_type, change_data, action_user, action_ip, action_note, created_at, updated_at) 
                                VALUES ('{$new_id}', '{$data_map['sid']}', '" . addslashes($asset_no) . "', 'INSERT', '" . addslashes(json_encode($data_map, JSON_UNESCAPED_UNICODE)) . "', '{$member_id}', '{$remote_ip}', 'Excel 批次匯入新增', NOW(), NOW());";
                    $db->execute($link, $log_sql, $ret_msg);
                } else {
                    $fail_cnt++;
                    $fail_list[] = array('row' => $row_num_display, 'reason' => '新增資料庫失敗: ' . $ret_msg);
                }
            }

            // 更新進度條
            $percent = intval(($r / $total_records) * 100);
            $db->modifyProgress($link, $member_id, $file_name, $percent, "import");
        }

        // 整理回應訊息
        $summary_msg = "匯入作業完成！新增：{$insert_cnt} 筆，更新：{$update_cnt} 筆，失敗：{$fail_cnt} 筆。";
        $response_data = array(
            'insert_cnt' => $insert_cnt,
            'update_cnt' => $update_cnt,
            'fail_cnt'   => $fail_cnt,
            'fail_list'  => $fail_list
        );

        $data = result_message("true", "0x0200", $summary_msg, json_encode($response_data, JSON_UNESCAPED_UNICODE));
        $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:' . $file_name, $summary_msg);

    } catch (Exception $e) {
        $ret_str = '匯入 ' . $caption . ' 異常 !';
        $data = result_message("false", "0x0207", $ret_str . " Error: " . $e->getMessage(), "");
        if (isset($db)) {
            $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:' . $file_name, $data['responseMessage'] ?? '');
        }
    } finally {
        close_connection_finally($link, $remote_ip, $member_id);
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>