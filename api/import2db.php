<?php
    header('Content-Type: application/json');
    include("./../common/entry.php");
    global $g_xlsx_in_path;
    global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;

    $file_name   = isset($_POST['filename'   ]) ? $_POST['filename'   ] : '';
    $member_id   = isset($_POST['memberid'   ]) ? $_POST['memberid'   ] : '';
    $caption     = isset($_POST['caption'    ]) ? $_POST['caption'    ] : '';
    $table       = isset($_POST['table'      ]) ? $_POST['table'      ] : 'data_device';
    $base64_file = isset($_POST['base64_file']) ? $_POST['base64_file'] : '';
    
    $API_name  = 'import2db';
    $who_call  = isset($_POST['who_call']) ? $_POST['who_call'] : 'back-end';

    $ret_str = "";
    $percent = 0;
    $remote_ip = get_remote_ip();
    $db = new CXDB($remote_ip);

    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            if (empty($member_id) || empty($table) || empty($file_name) || empty($base64_file)) {
                $ret_str = "匯入Excel資料 [".$caption."] 異常，API 參數不全!";
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '上傳Excel檔案', $ret_str);
                $data = result_message("false", "0x0206", $ret_str, '');
                echo (json_encode($data, JSON_UNESCAPED_UNICODE));
                return;
            }

            $dst_filename = ""; $err = ""; $repeat_rows = ""; $error_rows = "";
            $insert_cnt = 0; $repeat_cnt = 0; $error_cnt = 0;
            $into_analyze = false; $match = true; $math_err = "";

            $db->modifyProgress($link, $member_id, $file_name, 0, "import");
            $column_info = $db->getTableColumnComments($link, $table);

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

            // 組合資料庫欄位清單 (排除流水號 id 與時間戳記)
            $fields = ""; $fields_caption = "";
            for ($i = 0; $i < count($column_info); $i++) {
                $com = $column_info[$i];
                $field = $com[$g_fldidx_name];
                $name  = trim($com[$g_fldidx_comment], "'\" ");

                if ($field != 'id' && $field != 'created_at' && $field != 'updated_at') {
                    $fields .= (strlen($fields) > 0) ? "," : "";
                    $fields .= $field;

                    $fields_caption .= (strlen($fields_caption) > 0) ? "," : "";
                    $fields_caption .= $name;
                }
            }

            $xlsObject = json_decode($base64_file);
            for ($i = 0; $i < count($xlsObject); $i++) {
                $sheet = $xlsObject[$i];
                $rows_data = explode(';;;;', $sheet->csv_data);

                // 第一列標頭比對
                $field_row = explode(',', $rows_data[0]);
                for ($k = 0; $k < count($field_row); $k++) {
                    $into_analyze = true;
                    $fields_caption_array = str2array($fields_caption);
                    if (findStrInArray($fields_caption_array, trim($field_row[$k])) === -1 && strlen(trim($field_row[$k])) > 0) {
                        $match = false;
                        $math_err = $rows_data[0];
                        break;
                    }
                }

                // 計算有效資料筆數
                $records = count($rows_data) - 1;
                for ($j = count($rows_data) - 1; $j > 0; $j--) {
                    if (stripos($rows_data[$j], ',') === false) {
                        $records--;
                    } else {
                        $field_row_data = explode(',', $rows_data[$j]);
                        if (emptyStrInArray($field_row_data)) $records--;
                        else break;
                    }
                }

                if ($records < 0) $records = 0;

                if ($into_analyze && $match) {
                    $row_pos = 0;
                    for ($j = 1; $j <= $records; $j++) {
                        if (empty(trim($rows_data[$j]))) continue;

                        $per_row = explode(',', $rows_data[$j]);
                        $insert_data_map = array();
                        $values = ""; $condition_str = ""; $error_col = '';

                        // 2. 抓取當前資料列的 device_name 並對應 default_device 設定
                        $cur_device_name = '';
                        $dev_name_idx = findStrInArray($field_row, '設備中文名稱');
                        if ($dev_name_idx > -1 && isset($per_row[$dev_name_idx])) {
                            $cur_device_name = trim($per_row[$dev_name_idx]);
                        }

                        $def_config = isset($default_device_map[$cur_device_name]) ? $default_device_map[$cur_device_name] : array();

                        // 3. 自動產生 SID 與 created_at
                        $generated_sid = 'DEV_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
                        
                        for ($m = 0; $m < count($column_info); $m++) {
                            $com = $column_info[$m];
                            $field = $com[$g_fldidx_name];
                            $name  = trim($com[$g_fldidx_comment], "'\" ");

                            if ($field == 'id' || $field == 'updated_at') continue;

                            if ($field == 'sid') {
                                merge_with_comma($values, "'".$generated_sid."'");
                                $insert_data_map['sid'] = $generated_sid;
                                continue;
                            }

                            if ($field == 'created_at') {
                                merge_with_comma($values, "NOW()", "");
                                $insert_data_map['created_at'] = date('Y-m-d H:i:s');
                                continue;
                            }

                            $found_idx = findStrInArray($field_row, $name);
                            $excel_val = ($found_idx > -1 && isset($per_row[$found_idx])) ? trim($per_row[$found_idx]) : '';

                            // 數值帶入順序：Excel 填寫值 -> default_device 預設對應值 -> 系統預設備援值
                            if (!empty($excel_val)) {
                                $final_val = $excel_val;
                            } else if (isset($def_config[$field]) && $def_config[$field] !== '' && !is_null($def_config[$field])) {
                                $final_val = $def_config[$field];
                            } else {
                                if ($field == 'pc_mac') $final_val = 'WEB';
                                else if ($field == 'port_name' || $field == 'parity' || $field == 'handshake') $final_val = 'NONE';
                                else if ($field == 'baudrate') $final_val = '9600';
                                else if ($field == 'data_bits') $final_val = '8';
                                else if ($field == 'stop_bit' || $field == 'status') $final_val = '1';
                                else if ($field == 'sort_order') $final_val = '0';
                                else $final_val = '';
                            }

                            merge_with_comma($values, "'".addslashes($final_val)."'");
                            $insert_data_map[$field] = $final_val;

                            // 針對唯一約束欄位建立重複檢查條件 (如 asset_no 或 device_type)
                            if (in_array($field, array('asset_no', 'device_type')) && !empty($final_val)) {
                                $condition_str .= " AND ".$field." = '".addslashes($final_val)."'";
                            }
                        }

                        // 4. 檢查是否重複
                        if (!empty($condition_str)) {
                            $sql = 'SELECT id FROM '.$table.' WHERE 1=1'.$condition_str.';';
                            $result = $db->query($link, $sql);
                            if (!is_null($result) && mysqli_num_rows($result) > 0) {
                                $repeat_rows .= (!empty($repeat_rows)) ? ',' : '';
                                $repeat_rows .= ($j + 1);
                                $repeat_cnt++;
                                $row_pos++;
                                continue;
                            }
                        }

                        // 5. 寫入 data_device
                        $ret_msg = "";
                        $sql = 'INSERT INTO '.$table.' ('.$fields.', sid, created_at) VALUES ('.$values.');';
                        $new_id = $db->execute($link, $sql, $ret_msg);

                        if ($new_id > 0) {
                            $insert_cnt++;

                            // 6. 同步寫入 log_device
                            $change_json = json_encode($insert_data_map, JSON_UNESCAPED_UNICODE);
                            $log_sql = "INSERT INTO log_device (
                                           device_id, sid, pc_mac, asset_no, device_type, device_name, 
                                           action_type, change_data, action_user, action_ip, action_note, created_at
                                       ) VALUES (
                                           '".$new_id."', 
                                           '".$generated_sid."', 
                                           '".addslashes($insert_data_map['pc_mac'] ?? 'WEB')."', 
                                           '".addslashes($insert_data_map['asset_no'] ?? '')."', 
                                           '".addslashes($insert_data_map['device_type'] ?? '')."', 
                                           '".addslashes($insert_data_map['device_name'] ?? '')."', 
                                           'INSERT', 
                                           '".addslashes($change_json)."', 
                                           '".$member_id."', 
                                           '".$remote_ip."', 
                                           'Excel 批次匯入建立', 
                                           NOW()
                                       );";
                            $db->execute($link, $log_sql, $ret_msg);
                        } else {
                            $error_rows .= (!empty($error_rows)) ? ',' : '';
                            $error_rows .= ($j + 1);
                            $error_cnt++;
                        }

                        $row_pos++;
                        $percent = intval($row_pos / $records * 100);
                        $db->modifyProgress($link, $member_id, $file_name, $percent, "import");
                    }
                }
            }

            if (!$into_analyze || !$match) {
                $ret_str = '匯入Excel資料 ['.$caption.'] 異常，工作表欄位不符。<br>請檢查是否匯入對應的資料!';
                $err = array("math_error" => $math_err);
                $data = result_message("false", "0x0206", $ret_str, json_encode($err));
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:'.$file_name, $ret_str, json_encode($err));
            } else {
                $ret_str = '匯入Excel資料 ['.$caption.']，成功匯入 '.$insert_cnt.' 筆，總共 '.$records.' 筆。';
                if ($repeat_cnt > 0) $ret_str .= '<br>無法寫入重複資料，第 ('.$repeat_rows.') 行重複，共 '.$repeat_cnt.' 筆重複!';
                if ($error_cnt  > 0) $ret_str .= '<br>無法寫入錯誤資料，第 ('.$error_rows.') 行錯誤，共 '.$error_cnt.' 筆錯誤!';
                $data = result_message("true", "0x0200", $ret_str, "");
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:'.$file_name, $ret_str);
            }
        }
    } catch (Exception $e) {
        $ret_str = '新增 '.$caption.' 異常 !';
        $data = result_message("false", "0x0207", $ret_str." Exceptional error: ".$e->getMessage(), "");
        $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:'.$file_name, $data['responseMessage']);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }

    echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>