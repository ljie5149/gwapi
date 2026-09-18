<?php
	$m_is_remote = true;

	// Prepare start =================================================================
	if ($m_is_remote) {
		include("/var/www/html/gwapi/common/entry.php"); // remote
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
        $tmpDir = "/var/www/html/gwapi/log/"; 

        // 2. 抓取所有符合 log_*.log 格式的檔案
        $files = glob($tmpDir . "log_*.log"); 

        echo "開始清理目錄: " . $tmpDir . "\n";

        // 計算半年 (180天) 前的時間戳記
        $sixMonthsAgo = strtotime("-6 months");

        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    // 取得檔案最後修改時間
                    $fileMtime = filemtime($file);

                    // 僅刪除建立/修改時間超過半年的檔案
                    if ($fileMtime !== false && $fileMtime < $sixMonthsAgo) {
                        if (unlink($file)) {
                            echo "成功刪除舊 Log 檔案: " . basename($file) . "\n";
                        } else {
                            echo "無法刪除檔案 (檢查權限): " . basename($file) . "\n";
                        }
                    }
                }
            }
        }

        echo "Log 檔案清理完成。\n";

        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
			if ($data["status"] == "true") {
				// 1. 設備健檢量測 LOG (created_at)
				$sql = "DELETE FROM log_measure WHERE created_at < NOW() - INTERVAL 12 MONTH;";
				mysqli_query($link, $sql);

				// 2. 機構資料異動 LOG (created_at)
				$sql = "DELETE FROM log_facility WHERE created_at < NOW() - INTERVAL 6 MONTH;";
				mysqli_query($link, $sql);

				// 3. 會員資料異動 LOG (created_at)
				$sql = "DELETE FROM log_member WHERE created_at < NOW() - INTERVAL 6 MONTH;";
				mysqli_query($link, $sql);

				// 4. API設定異動 LOG (created_at)
				$sql = "DELETE FROM log_api_config WHERE created_at < NOW() - INTERVAL 6 MONTH;";
				mysqli_query($link, $sql);

				// 5. 健檢設備資料異動 LOG (created_at)
				$sql = "DELETE FROM log_device WHERE created_at < NOW() - INTERVAL 6 MONTH;";
				mysqli_query($link, $sql);

				// 6. PC設備資料 LOG (created_at)
				$sql = "DELETE FROM log_pc WHERE created_at < NOW() - INTERVAL 6 MONTH;";
				mysqli_query($link, $sql);

				// 7. 訊息紀錄 LOG (create_date)
				$sql = "DELETE FROM log_message WHERE create_date < NOW() - INTERVAL 6 MONTH;";
				mysqli_query($link, $sql);

				// 8. 清除未完單的暫存表 (保留原邏輯)
				$sql = "DELETE FROM data_applyform_ing WHERE create_date < NOW() AND applyform_name = '';";
				mysqli_query($link, $sql);
			}
		}
    } catch (Exception $e) {
        $msg_detail = get_error_symbol($data["code"])." login result :".$data["code"]." error :".$e->getMessage();
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
    }
?>