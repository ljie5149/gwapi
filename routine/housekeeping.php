<?php
	$m_is_remote = true;

	// Prepare start =================================================================
	if ($m_is_remote) {
		include("/var/www/html/nantoupass_be/common/entry.php"); // remote
	} else {
        include("./../common/entry.php");
	}
	// Prepare end --------------------------------------------------------------------

	// Entry
    $member_id = "customer"; $role = ""; $order_limit = 0;
	$remote_ip = get_remote_ip();
    $db= new CXDB($remote_ip);
    try {
		// 1. 確保目錄路徑結尾有斜線
		$tmpDir = "/var/www/html/nantoupass_be/images/tmp/"; 

		// 2. 使用 glob 抓取所有檔案
		$files = glob($tmpDir . "*"); // "*" 代表所有檔案，不一定要有小數點

		echo "開始清理目錄: " . $tmpDir . "\n";

		// 如果目錄不存在，glob 可能回傳 false
		if ($files !== false) {
			foreach ($files as $file) {
				if (is_file($file)) {
					if (unlink($file)) {
						echo "成功刪除檔案: " . basename($file) . "\n";
					} else {
						echo "無法刪除檔案 (檢查權限): " . basename($file) . "\n";
					}
				}
			}
		}

		echo "清理完成。\n";

        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
			$sql = "DELETE FROM log_message WHERE create_date < NOW() - INTERVAL 3 MONTH; ";
			mysqli_query($link, $sql);
			$sql = "DELETE FROM data_applyform_ing WHERE create_date < NOW() AND applyform_name = ''; ";
			mysqli_query($link, $sql);
		}
    } catch (Exception $e) {
        $msg_detail = get_error_symbol($data["code"])." login result :".$data["code"]." error :".$e->getMessage();
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
    }
?>