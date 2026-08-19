<?php
    header('Content-Type: application/json');
    include("./../common/entry.php");
    global $g_ProjectName, $g_Copyright, $g_sidemenu_idx, $g_db_table, $g_xlsx_out_path, $g_export_max;
    global $g_fldidx_name, $g_fldidx_show, $g_fldidx_comment;

    $file_name   = isset($_POST['filename']) ? $_POST['filename']: '';
    $member_id   = isset($_POST['memberid']) ? $_POST['memberid']: '';
    $caption     = isset($_POST['caption' ]) ? $_POST['caption' ]: '';
    $table       = isset($_POST['table'    ]) ? $_POST['table'    ]: '';
    $where_str   = isset($_POST['where'    ]) ? $_POST['where'    ]: '';
    $sort        = isset($_POST['sort'     ]) ? $_POST['sort'     ]: '';
    $start_index = isset($_POST['start_index']) ? $_POST['start_index']: 0;
    $page_num    = isset($_POST['page_num' ]) ? $_POST['page_num' ]: 0;

    $data = array();
    $remote_ip = get_remote_ip();
    
    $percent = 0; $ret_str = "";
    if (empty($member_id) || empty($table) || empty($file_name) || empty($caption)) {
        $ret_str = "匯出Excel資料 [".$caption."] 異常，API 參數不全!";
        $data = result_message("false", "0x0206", $ret_str, '');
        echo (json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }

    $kind_export_array = null;
    $row_pos         = 0;
    $comments        = array();
    $file_name_array = array();
    $db = new CXDB($remote_ip);
    
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {

            // 1. 指定僅需要導出的三個目標欄位
            $target_fields = array('asset_no', 'device_name', 'tag');
            $export_fields = implode(',', $target_fields);

            // 預設中文欄位名稱（作為備援標題）
            $default_comments = array(
                'asset_no'    => '設備資產序號',
                'device_name' => '設備中文名稱',
                'tag'         => '設備識別標籤'
            );

            // 2. 獲取資料表欄位資訊（傳入 without_columns 以取得欄位 Comment）
            $raw_column_info = $db->getTableColumnComments($link, $table, '');
            
            // 3. 重組 column_info，僅保留指定欄位並設置 Comment 中文標題
            $column_info = array();
            
            foreach ($target_fields as $field) {
                $matched_com = null;
                if (is_array($raw_column_info)) {
                    foreach ($raw_column_info as $com) {
                        if (isset($com[$g_fldidx_name]) && $com[$g_fldidx_name] === $field) {
                            $matched_com = $com;
                            break;
                        }
                    }
                }

                if ($matched_com) {
                    // 若存在 comment 則做字串清理（去除單引號或多餘空格）
                    if (isset($matched_com[$g_fldidx_comment]) && !empty($matched_com[$g_fldidx_comment])) {
                        $matched_com[$g_fldidx_comment] = trim($matched_com[$g_fldidx_comment], "'\" ");
                    } else {
                        $matched_com[$g_fldidx_comment] = $default_comments[$field];
                    }
                    $matched_com[$g_fldidx_show] = "true";
                    $column_info[] = $matched_com;
                } else {
                    // 若系統元件未取到完整結構，手動建立結構
                    $column_info[] = array(
                        $g_fldidx_name    => $field,
                        $g_fldidx_show    => "true",
                        $g_fldidx_comment => $default_comments[$field]
                    );
                }
            }

            $data = result_message("false", "0x0206", "Step01", "");

            $sort_str = (strlen($sort) > 0) ? $sort : "";
            $limit_str = "";
            if ($page_num > 0) {
                $limit_str = 'LIMIT '.$start_index.','.$page_num;
            }

            // 4. 僅查詢指定的三個欄位
            $result = $db->getData($link, $table, $member_id, $export_fields, $where_str, "", $sort_str, $limit_str);
            
            $data = result_message("false", "0x0206", "Step02", "");
            if ($g_export_max > 0 && mysqli_num_rows($result) > $g_export_max + 1) {
                $ret_str = '動作無法進行，匯出Excel資料 ['.$caption.'] 數量超過 '.$g_export_max.' 筆';
                $data = result_message("false", "0x0206", $ret_str, "");
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯出Excel:'.$file_name, $ret_str);
            } else {
                $data = result_message("false", "0x0206", "Step03", "");
                $db->modifyProgress($link, $member_id, $file_name, 0, "export");
                $data = result_message("false", "0x0206", "Step04", "");

                // 5. 執行 Excel 匯出
                if (export($column_info, $caption, $result, $db, $link, $member_id, $kind_export_array, $file_name, $records)) {
                    $ret_str = '匯出Excel資料 ['.$caption.'] 成功，共 '.$records.' 筆';
                    $file_name_array['download_file_name'] = $file_name;
                    $data = result_message("true", "0x0200", $ret_str, json_encode($file_name_array));
                    $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯出Excel:'.$file_name, $ret_str);
                }
            }
        }
    } catch (Exception $e) {
        $ret_str = '新增 '.$caption.' 異常 !';
        $data = result_message("false", "0x0207", $ret_str."Except error:".$e->getMessage(), "");
        $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯出Excel:'.$file_name, $data['responseMessage']);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }

    echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>