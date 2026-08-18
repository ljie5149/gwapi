<?php
    header('Content-Type: application/json');
    include("./../common/entry.php");
    global $g_xlsx_in_path;
    global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
	$file_name   = isset($_POST['filename'   ]) ? $_POST['filename'   ]: '';
	$member_id   = isset($_POST['memberid'   ]) ? $_POST['memberid'   ]: '';
	$caption     = isset($_POST['caption'    ]) ? $_POST['caption'    ]: '';
	$table       = isset($_POST['table'      ]) ? $_POST['table'      ]: '';
	$base64_file = isset($_POST['base64_file']) ? $_POST['base64_file']: '';
	$auto_create_pdct_class = isset($_POST['auto_create_pdct_class']) ? $_POST['auto_create_pdct_class']: '';
    $ret_str = "";
    // $ret_str = $file_name.','.$member_id.','.$caption.','.$table.','.$base64_file; // debug log
    $API_name   = 'import2db';
    $who_call	= isset($_POST['who_call'  ]) ? $_POST['who_call'  ] : 'back-end'; // 誰呼叫

    $dst_path = array(); $value = "";
    $percent = 0;
	$remote_ip = get_remote_ip();
    $db = new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            if (empty($member_id  ) ||
                empty($table      ) ||
                empty($file_name  ) ||
                empty($base64_file)) {
                
                $ret_str= "匯出Excel資料 ['.$caption.'] 異常，API 參數不全!";
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '上傳Excel檔案', $ret_str);
                $data = result_message("false", "0x0206", $ret_str, '');
                echo (json_encode($data, JSON_UNESCAPED_UNICODE));
                return;
            }
    
            if (empty($base64_file) &&
                empty($file_name  )) {
                $ret_str = '上傳Excel檔案 ['.$file_name.'] 異常!';
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '上傳Excel檔案', $ret_str);
                $data = result_message("false", "0x0206", $ret_str, '');
                echo (json_encode($data, JSON_UNESCAPED_UNICODE));
                return;
            }

            // 取得對換資訊，用途：將 parent_sid使用者文字 轉換為 sid
            $kind_import_array = null;
            if ($table == "data_product") $kind_import_array = getPdctKindWithAllParent($g_db_table['infoproductkind03'], $remote_ip, $member_id);
            if ($table == "data_productdetail") $kind_import_array = getPdctDetlWithAllParent($g_db_table['infoproductkind03'], $remote_ip, $member_id);

            // 取得 unique_id 避免重複檔案名稱
            $sid = "";
            if (empty($sid)) {
                $sid = getSid($db, $link, $table, $member_id);
            }
            $dst_filename = ""; $err = ""; $repeat_rows = ""; $error_rows = "";
            $ret_str = "";
            $n = 1;
            if (!empty($base64_file) &&
                !empty($file_name  )) {
                
                // 資料表取得欄位，設定進度百分比
                $fields = ""; $fields_caption = "";
                $db->modifyProgress($link, $member_id, $file_name, 0, "import");
                $column_info = $db->getTableColumnComments($link, $table);
                for ($i = 0; $i < count($column_info); $i++) {
                    $com = $column_info[$i];

                    $field    = $com[$g_fldidx_name];
                    $name     = $com[$g_fldidx_comment];
                    $show     = ($com[$g_fldidx_show]         == "true");
                    $hidden   = ($com[$g_fldidx_showbuthide]  == "true");
                    $search   = ($com[$g_fldidx_srch]         == "true");
                    $lockedit = ($com[$g_fldidx_lockedit]     == "true");
                    
                    if ($field != 'nid') {
                        $fields.= (strlen($fields) > 0) ? "," : "";
                        $fields.= $field;

                        $fields_caption.= (strlen($fields_caption) > 0) ? "," : "";
                        $fields_caption.= $name;
                    }
                }
                // echo $fields_caption;

                // 開始解析資料 - (sheet_name：工作表名稱; csv_data：資料內容陣列)
                $into_analyze = false; $records = 0; $sql = ""; $idxs = ""; $percent = 0; $math_err = "";
                $xlsObject = json_decode($base64_file);
                for ($i = 0; $i < count($xlsObject); $i++) { // 工作表
                    $sheet = $xlsObject[$i];
                    $rows_data = explode(';;;;', $sheet->csv_data); // 所有資料分列

                    $empty_str = "";
                    $j = 0; // 第一列：欄位，導入前需比對欄位名稱一致時才可加入
                    $match = true; $into_analyze = false;
                    $field_row = explode(',', $rows_data[$j]); // 該列資料
                    for ($k = 0; $k < count($field_row); $k++) { // 行
                        $into_analyze = true;
                        $fields_caption_array = str2array($fields_caption);
                        if (findStrInArray($fields_caption_array, $field_row[$k]) === -1 && strlen($field_row[$k]) > 0) {
                            $match = false;
                            $math_err = $rows_data[0];
                            break; // 欄位名稱不符合時，結束讀取資料 for $k
                        }
                    }

                    // 檢查尾端空白列
                    $insert_cnt = 0; $repeat_cnt = 0; $error_cnt = 0; $error_col = '';
                    $records = count($rows_data) - 1; $row_pos = 0;
                    for ($j = count($rows_data) - 1; $j > 1; $j--) { // 列
                        if (stripos($rows_data[$j], ',') === false)
                            $records--;
                        else {
                            $field_row_data = explode(',', $rows_data[$j]); // 該列資料
                            if (emptyStrInArray($field_row_data))
                                $records--;
                            else
                                break;
                        }
                    }

                    if ($records < 0) $records = 0;
                    if ($into_analyze == true && $match == true) {
                        for ($j = 1; $j <= $records; $j++) { // 列
                            $values = ""; $condition_str = ""; $error_col = '';
                            if (stripos($rows_data[$j], ',') === false) {

                            } else {
                                $per_row = explode(',', $rows_data[$j]); // 該列資料
                                $title = ""; $class_id = "";
                                for ($m = 0; $m < count($column_info); $m++) {
                                    $com = $column_info[$m];

                                    $field    = $com[$g_fldidx_name];
                                    $name     = $com[$g_fldidx_comment];
                                    $show     = ($com[$g_fldidx_show]         == "true");
                                    $hidden   = ($com[$g_fldidx_showbuthide]  == "true");
                                    $search   = ($com[$g_fldidx_srch]         == "true");
                                    $lockedit = ($com[$g_fldidx_lockedit]     == "true");
                                    
                                    if ($field != 'nid') {
                                        $found_idx = findStrInArray($field_row, $name);
                                        if ($found_idx > -1) {
                                            $tmp = $per_row[$found_idx];
                                            if ($field == "parent_sid" && !is_null($kind_import_array)) {

                                                $tmp_kind = isset($kind_import_array[$per_row[$found_idx]]) ? $kind_import_array[$per_row[$found_idx]] : '';
                                                if (!empty($tmp_kind)) {
                                                    $tmp = $tmp_kind;
                                                } else {
                                                    if (stripos($per_row[$found_idx], ">") === false) {
                                                        $error_col = ' '.$name;
                                                    } else if ($auto_create_pdct_class == "true") {
                                                        $stClass = str2array($per_row[$found_idx], ">");

                                                        // 自動新增商品類別
                                                        $parent_sid = ""; $insert_ret = 0;
                                                        if (count($stClass) >= 4) {
                                                            if (count($stClass) == 4) {
                                                                $title = $stClass[3];
                                                                for ($k = 0; $k < count($stClass) - 1; $k++) { // class
                                                                    $insert_ret = modifyPdctClass($API_name, $who_call, $caption, $remote_ip, $member_id, $k + 1, $stClass[$k], $class_id, $parent_sid);
                                                                    if ($insert_ret == 2) {
                                                                        $null_array = array();
                                                                        $data = result_message("false", "0x0206", "自動新增商品類別錯誤", json_encode($null_array));
                                                                        echo (json_encode($data, JSON_UNESCAPED_UNICODE));
                                                                        return;
                                                                    }
                                                                    if ($k == 2 && ($insert_ret == 1 ||  $insert_ret == 3)) {
                                                                        $tmp = $class_id;
                                                                    }
                                                                }
                                                            // } else if (count($stClass) == 3) {
                                                            //     $title = "";
                                                            //     for ($k = 0; $k < count($stClass); $k++) { // class
                                                            //         $insert_ret = modifyPdctClass($API_name, $who_call, $caption, $remote_ip, $member_id, $k + 1, $stClass[$k], $class_id, $parent_sid);
                                                            //         if ($insert_ret == 2) {
                                                            //             $null_array = array();
                                                            //             $data = result_message("false", "0x0206", "自動新增商品類別錯誤", json_encode($null_array));
                                                            //             echo (json_encode($data, JSON_UNESCAPED_UNICODE));
                                                            //             return;
                                                            //         }
                                                            //         if ($k == 2 && $insert_ret == 1) {
                                                            //             $tmp = $class_id;
                                                            //         }
                                                            //     }
                                                            }
                                                        } else {
                                                            $error_col = ' '.$name;
                                                        }

                                                    } else {
                                                        $error_col = ' '.$name;
                                                    }
                                                }
                                                merge_with_comma($values, $tmp);
                                            } else if ($field == "title" && !empty($title)) {
                                                merge_with_comma($values, $title);
                                                $title = "";
                                            } else {
                                                merge_with_comma($values, $tmp);
                                            }
                                            if ($field != 'sid' && stripos($field, "_date") === false) {
                                                $condition_str.= merge_sql_string_if_not_empty($field, $tmp);
                                            }
                                        } else if ($field == "member_sid") {
                                            merge_with_comma($values, $member_id);
                                        } else if ($field == "sid") {
                                            merge_with_comma($values, $sid.'_'.$n++);
                                        } else {
                                            if (strEndWith($field, "_date")) {
                                                merge_with_comma($values, "NOW()", "");
                                            } else if ($field == "sort") {
                                                merge_with_comma($values, ((empty($tmp)) ? "99" : $tmp));
                                            } else if ($field == "counter" || $field == "cur_quentity") {
                                                merge_with_comma($values, ((empty($tmp)) ? "0" : $tmp));
                                            } else if ($field == "avalible") {
                                                merge_with_comma($values, "Y");
                                            } else {
                                                merge_with_comma($values, "");
                                            }
                                        }
                                    }
                                }
                                
                                if (empty($error_col)) {
                                    $sql = 'SELECT * FROM '.$table.' WHERE 1=1'.$condition_str.';';
                                    $result = $db->query($link, $sql);
                                    if (!is_null($result) && mysqli_num_rows($result) > 0) {
                                        $repeat_rows.= (!empty($repeat_rows)) ? ',' : '';
                                        $repeat_rows.= ($j + 1);
                                        $repeat_cnt++;
                                    } else {
                                        $ret_msg = "";
                                        $sql = 'INSERT INTO '.$table.' ('.$fields.') VALUES ('.$values.');';
                                        $effect_rcds = $db->execute($link, $sql, $ret_msg);
                                        if ($effect_rcds > 0) {
                                        } else {
                                            $error_rows.= (!empty($error_rows)) ? ',' : '';
                                            $error_rows.= ($j + 1).$error_col;
                                            $error_cnt++;
                                        }
                                        $insert_cnt++;
                                    }
                                } else {
                                    $error_rows.= (!empty($error_rows)) ? ',' : '';
                                    $error_rows.= ($j + 1).$error_col;
                                    $error_cnt++;
                                }
                                $row_pos++;
                                
                                $percent = intval($row_pos / $records * 100);
                                $db->modifyProgress($link, $member_id, $file_name, $percent, "import");
                            }
                        }
                    }
                }

                if ($into_analyze == false || $match == false) {
				    $ret_str = '匯入Excel資料 ['.$caption.'] 異常，工作表欄位不符。<br>請檢查是否匯入對應的資料!';
                    $err = array();
                    $err["math_error"] = $math_err;
                    $data = result_message("false", "0x0206", $ret_str, json_encode($err));
                    $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:'.$file_name, $ret_str, json_encode($err));
                } else {
                    if ($insert_cnt < 0) $insert_cnt = 0;
                    if ($repeat_cnt < 0) $repeat_cnt = 0;
				    $ret_str = '匯入Excel資料 ['.$caption.']，成功匯入 '.$insert_cnt.' 筆，總共 '.$records.' 筆。';
                    if ($repeat_cnt > 0) $ret_str.= '<br>無法寫入重複資料，第 ('.$repeat_rows.') 行重複，共 '.$repeat_cnt.' 筆重複!';
                    if ($error_cnt  > 0) $ret_str.= '<br>無法寫入錯誤資料，第 ('.$error_rows.') 行錯誤，共 '.$error_cnt.' 筆錯誤!';
                    $data = result_message("true", "0x0200", $ret_str, "");
                    $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:'.$file_name, $ret_str);
                }
            }
        }
    } catch (Exception $e) {
        $ret_str = '新增 '.$caption.' 異常 !';
        $data = result_message("true", "0x0207", $ret_str."Except error:".$e->getMessage(), "");
        $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯入Excel:'.$file_name, $data['responseMessage']);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
    echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>