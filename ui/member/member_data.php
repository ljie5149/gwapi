<?php
    include("./../common/entry.php");
	global $g_db_table;
    
	$data = array();
	$remote_ip = get_remote_ip();
	$member_id = isset($_SESSION['userid']) ? $_SESSION['userid']: '';
	$authority = isset($_SESSION['authority']) ? $_SESSION['authority']: '';
	$cur_page = isset($_POST['page']) ? intval($_POST['page']) : 1;
	$rows_page = isset($_POST['rows']) ? intval($_POST['rows']) : 100;
	
    $table = $g_db_table['datamember'];
	$column_info = array();
    $db= new CXDB($remote_ip);

	$query_result = "";
	$rows = array();
	$ret_data = new CXmemberData();
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
			// for ($i = 99084; $i < 100000; $i++) {
			// 	$sql="INSERT INTO `info_member` (`sid`, `create_date`, `staff_sid`, `name`, `eng_name`, `identity`, `mail`, `mobile`, `advertising_id`
			// 									, `device_id`, `pwd`, `gender`, `isforeign`, `birthday`, `blood_type`, `tel`, `start_date`, `priority`
			// 									, `avalible`, `authorization_page`) VALUES (";
			// 	$sql.="'".sprintf("admin%09d", $i)."', NOW(), '".sprintf("A10%02d", $i % 10)."', '".sprintf("管理員%09d", $i)."', '".sprintf("Administrator%09d", $i)."', '".sprintf("W%.09d", $i)."', 'T@', '0912-345-678', NULL, NULL, 'admin', '男', '0', '2023-03-29', NULL, NULL, NOW(), '7777', '1', '2')";
			// 	$db->query($link, $sql, $remote_ip, $member_id, 'generate data');
			// }
			$row_count = 0;
			$rs = $db->getMember($link, "", "count(*) as total_rows");
			if (!is_null($rs) && mysqli_num_rows($rs) > 0) {
				$row = mysqli_fetch_array($rs);
				$row_count = intval($row['total_rows']);
			}
			$ret_data->page = $cur_page;
			$ret_data->total = 1;
			$mod = $row_count % $rows_page;
			if ($row_count > 0) $ret_data->total = ($row_count - $mod) / $rows_page;
			$page_start = ($cur_page - 1) * $rows_page;
			$page_end = ($page_start + $rows_page);
			
			$ret_data->records = $row_count;
			$result = $db->getMember($link, "", "ROW_NUMBER() OVER(ORDER BY id) AS row_num, id, sid, staff_sid, name, eng_name, identity, mail, mobile, gender, birthday, priority, avalible"
									, "", 1, "", false, "limit $page_start, $page_end");
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_assoc($result)) {
					$rows[] = $row;
				}
			}
			$ret_data->rows = $rows;
			$query_result = json_encode($ret_data);
		}
    } catch (Exception $e) {
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
    echo $query_result;
?>