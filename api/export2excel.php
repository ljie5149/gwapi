<?php
    header('Content-Type: application/json');
    include("./../common/entry.php");
    global $g_ProjectName, $g_Copyright, $g_sidemenu_idx, $g_db_table, $g_xlsx_out_path, $g_export_max;
    global $g_fldidx_name, $g_fldidx_show;

	$file_name   = isset($_POST['filename']) ? $_POST['filename']: '';
	$member_id   = isset($_POST['memberid']) ? $_POST['memberid']: '';
	$caption     = isset($_POST['caption' ]) ? $_POST['caption' ]: '';
	$table           = isset($_POST['table'          ]) ? $_POST['table'          ]: '';
	$where_str       = isset($_POST['where'          ]) ? $_POST['where'          ]: '';
	$sort            = isset($_POST['sort'           ]) ? $_POST['sort'           ]: '';
	$without_columns = isset($_POST['without_columns']) ? $_POST['without_columns']: '';
	$start_index     = isset($_POST['start_index'    ]) ? $_POST['start_index'    ]: 0;
	$page_num        = isset($_POST['page_num'       ]) ? $_POST['page_num'       ]: 0;

	$data = array();
	$remote_ip = get_remote_ip();
    
    $percent = 0; $ret_str = "";
    if (empty($member_id) || empty($table) || empty($file_name) || empty($caption)) {
        $ret_str= "匯出Excel資料 ['.$caption.'] 異常，API 參數不全!";
        $data = result_message("false", "0x0206", $ret_str, '');
        echo (json_encode($data, JSON_UNESCAPED_UNICODE));
        return;
    }
    if (!empty($without_columns) && $table != "data_member") $without_columns.= ',nid,create_date,member_sid';
    $kind_export_array = null;
    $row_pos         = 0;
    $comments        = array();
    $file_name_array = array();
    $db = new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, $without_columns);
            if ($table == "data_product") $kind_export_array = getPdctKindWithAllParent($g_db_table['infoproductkind03'], $remote_ip, $member_id, true);
            if ($table == "data_productdetail") $kind_export_array = getPdctDetlWithAllParent($g_db_table['infoproductkind03'], $remote_ip, $member_id, true);
            $export_fields = "";
            for ($i = 0; $i < count($column_info); $i++) {
                $com = $column_info[$i];
                if ($com[$g_fldidx_show] == "true") {
                    $export_fields.= (empty($export_fields)) ? '' : ',';
                    $export_fields.= $com[$g_fldidx_name];
                }
            }
            $data = result_message("false", "0x0206", "Step01", "");

            $sort_str = (strlen($sort) > 0) ? $sort : "";
            $limit_str = "";
            if ($page_num > 0) {
                $limit_str = 'LIMIT '.$start_index.','.$page_num;
            }
            $result = $db->getData($link, $table, $member_id, $export_fields, $where_str, "", $sort_str, $limit_str);
            
            $data = result_message("false", "0x0206", "Step02", "");
            if ($g_export_max > 0 &&
                mysqli_num_rows($result) > $g_export_max + 1) {
                $ret_str = '動作無法進行，匯出Excel資料 ['.$caption.'] 數量超過 '.$g_export_max.' 筆';
                $data = result_message("false", "0x0206", $ret_str, "");
                $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯出Excel:'.$file_name, $ret_str);
            } else {
                $data = result_message("false", "0x0206", "Step03", "");
                $db->modifyProgress($link, $member_id, $file_name, 0, "export");
                $data = result_message("false", "0x0206", "Step04", "");
                // if (!is_null($result) && mysqli_num_rows($result) > 0) {
                    if (export($column_info, $caption, $result, $db, $link, $member_id, $kind_export_array, $file_name, $records)) {
                        $ret_str = '匯出Excel資料 ['.$caption.'] 成功，共 '.$records.' 筆';
                        $file_name_array['download_file_name'] = $file_name;
                        $data = result_message("true", "0x0200", $ret_str, json_encode($file_name_array));
                        $db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '匯出Excel:'.$file_name, $ret_str);
                    }
                // }
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
    // $info = array();
    // $info["filename"] = $file_name;
    // $info["memberid"] = $member_id;
    // $info["caption" ] = $caption;
    // $info["progress"] = $percent;
    // $info["records" ] = $row_pos;
    // $data["json"    ] = json_encode($info);
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>