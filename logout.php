<?php
    include("./common/entry.php");
	session_start();
	
	$member_id  = isset($_SESSION['userid'] ) ? $_SESSION['userid'  ]: '';
	$accname    = isset($_SESSION['accname']) ? $_SESSION['accname' ]: '';
	$data = array();
	$remote_ip = get_remote_ip();

    $db= new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $db->saveLog($link, $member_id, 'back-end', 'Logout', $accname.' 使用者登出 成功', '');
        }
    } catch (Exception $e) {
        $db->saveLog($link, $member_id, 'back-end', 'Logout', $accname.' 使用者登出 失敗', '');
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
        clearSession(true, true);
    }
?>
