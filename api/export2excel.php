<?php
    header('Content-Type: application/json');
    include("./../common/entry.php");

    global $g_ProjectName, $g_Copyright, $g_sidemenu_idx, $g_db_table, $g_xlsx_out_path, $g_export_max;
    global $g_fldidx_name, $g_fldidx_show, $g_fldidx_comment;
    global $g_xlsx_out_url;

    // 1. 取得與整理 POST 參數
    $file_name   = $_POST['filename'] ?? '';
    $member_id   = $_POST['memberid'] ?? '';
    $caption     = $_POST['caption'] ?? '';
    $table       = $_POST['table'] ?? '';
    $where_str   = $_POST['where'] ?? '';
    $sort        = $_POST['sort'] ?? '';
    $start_index = (int)($_POST['start_index'] ?? 0);
    $page_num    = (int)($_POST['page_num'] ?? 0);

    $remote_ip = get_remote_ip();

    // 2. 必填參數驗證
    if (empty($member_id) || empty($table) || empty($file_name) || empty($caption)) {
        $ret_str = "匯出Excel資料 [{$caption}] 異常，API 參數不全!";
        echo json_encode(result_message("false", "0x0206", $ret_str, ''), JSON_UNESCAPED_UNICODE);
        return;
    }

    $db = new CXDB($remote_ip);

    try {
        $data = $db->connect($link, $member_id, "");
        if (($data["status"] ?? '') === "true") {

            // 3. 取得預設設備名稱清單 (僅查詢必要欄位，並用 implode 簡化字串拼接)
            $default_devices = array();
            $def_sql = "SELECT device_name FROM default_device WHERE status = 1;";
            $def_result = $db->query($link, $def_sql);

            if (!is_null($def_result) && mysqli_num_rows($def_result) > 0) {
                while ($def_row = mysqli_fetch_assoc($def_result)) {
                    if (!empty($def_row['device_name'])) {
                        $default_devices[] = $def_row['device_name'];
                    }
                }
            }
            $default_device_str = !empty($default_devices) ? "(" . implode(',', $default_devices) . ")" : "";

            // 4. 定義匯出目標欄位與預設中文備援標題
            $target_fields = array('asset_no', 'device_name', 'tag');
            $export_fields = implode(',', $target_fields);

            $default_comments = array(
                'asset_no'    => '設備資產序號',
                'device_name' => '設備中文名稱',
                'tag'         => '設備識別標籤'
            );

            // 5. 獲取資料表欄位資訊並建立 HashMap
            $raw_column_info = $db->getTableColumnComments($link, $table, '');
            $column_map = array();

            if (is_array($raw_column_info)) {
                foreach ($raw_column_info as $com) {
                    $field_name = $com[$g_fldidx_name] ?? $com[0] ?? '';
                    if (!empty($field_name)) {
                        $column_map[$field_name] = $com;

                        // 若 DB 內有註解，自動覆蓋預設標題
                        $db_comment = trim($com[$g_fldidx_comment] ?? $com[1] ?? '', "'\" ");
                        if (!empty($db_comment)) {
                            $default_comments[$field_name] = $db_comment;
                        }
                    }
                }
            }

            // 6. 重組 column_info (修復提示字串被蓋掉的邏輯問題)
            $column_info = array();
            foreach ($target_fields as $field) {
                // 基礎註解（DB 註解優先，備援標題次之）
                $final_comment = $default_comments[$field] ?? $field;

                // 若為 device_name 且有預設設備名稱，動態附加括號提示
                if ($field === 'device_name' && !empty($default_device_str)) {
                    $final_comment .= $default_device_str;
                }

                if (isset($column_map[$field])) {
                    $matched_com = $column_map[$field];
                    $matched_com[$g_fldidx_comment] = $final_comment;
                    $matched_com[$g_fldidx_show]    = "true";
                    $column_info[] = $matched_com;
                } else {
                    // 資料庫無此欄位時的備援結構
                    $column_info[] = array(
                        $g_fldidx_name    => $field,
                        $g_fldidx_show    => "true",
                        $g_fldidx_comment => $final_comment
                    );
                }
            }
            // var_dump($column_info);

            // 7. 查詢條件與分頁處理
            $sort_str  = !empty($sort) ? $sort : "";
            $limit_str = ($page_num > 0) ? "LIMIT {$start_index},{$page_num}" : "";

            $result = $db->getData($link, $table, $member_id, $export_fields, $where_str, "", $sort_str, $limit_str);

            if ($g_export_max > 0 && mysqli_num_rows($result) > $g_export_max + 1) {
                $ret_str = "動作無法進行，匯出Excel資料 [{$caption}] 數量超過 {$g_export_max} 筆";
                $data = result_message("false", "0x0206", $ret_str, "");
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, "匯出Excel:{$file_name}", $ret_str);
            } else {
                $full_file_name = (substr($file_name, -5) === '.xlsx') ? $file_name : $file_name . '.xlsx';
                $db->modifyProgress($link, $member_id, $full_file_name, 0, "export");

                // 8. 執行 Excel 匯出
                $records = 0;
                $kind_export_array = null;

                if (export($column_info, $caption, $result, $db, $link, $member_id, $kind_export_array, $file_name, $records)) {
                    $ret_str = "匯出Excel資料 [{$caption}] 成功，共 {$records} 筆";
                    $download_url = $g_xlsx_out_url . $full_file_name;
                    $data = result_message("true", "0x0200", $ret_str, json_encode(['download_file_name' => $download_url]));
                    $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, "匯出Excel:{$file_name}", $ret_str);
                }
            }
        }
    } catch (Exception $e) {
        $ret_str = "匯出 {$caption} 異常 !";
        $data = result_message("false", "0x0207", $ret_str . " Except error: " . $e->getMessage(), "");
        if (isset($db)) {
            $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, "匯出Excel:{$file_name}", $data['responseMessage'] ?? '');
        }
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if (($data_close_conn["status"] ?? '') === "false") {
            $data = $data_close_conn;
        }
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>