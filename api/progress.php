<?php
    include("./../common/entry.php");
    header('Content-Type: application/json');

	$remote_ip = get_remote_ip();
    $data = array();
	$member_id  = isset($_POST['memberid']) ? $_POST['memberid']: '';
	$file_name  = isset($_POST['filename']) ? $_POST['filename']: '';
	$flag       = isset($_POST['flag'    ]) ? $_POST['flag'    ]: '';
    $percentage  = 0;

    $db = new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $percentage = $db->getProgressPercentage($link, $member_id, $file_name, $flag);
            $data["Percentage"] = $percentage;
        }
    } catch (Exception $e) {
        $ret_str .= $e->getMessage();
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
    echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>