<?php
    include("./../common/entry.php");

	$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
	$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
	$sort = isset($_POST['sort']) ? strval($_POST['sort']) : 'id';
	$order = isset($_POST['order']) ? strval($_POST['order']) : 'ASC';
	$table = isset($_GET['table']) ? strval($_GET['table']) : '';
	$select_str = isset($_GET['select']) ? strval($_GET['select']) : '*';
	$offset = ($page-1)*$rows;
	
	$items  = array();
	$result = array();
	$data   = array();
	$remote_ip = get_remote_ip();
	$member_id = isset($_SESSION['userid']) ? $_SESSION['userid']: '';
	$db= new CXDB($remote_ip);
	try {
		$data = $db->connect($link, $member_id, "");
		if ($data["status"] == "true") {
			$sql = "select count(*) as total_rows from $table";
			$rs = $db->query($link, $sql);
			if (!is_null($rs) && mysqli_num_rows($rs) > 0) {
				$row = mysqli_fetch_array($rs);
				$row_count = $row['total_rows'];
				$result["total"] = $row_count;
	
				$rs = $db->query($link, "select $select_str from $table order by $sort $order limit $offset, $rows");
				if (!is_null($rs) && mysqli_num_rows($rs) > 0) {
					while ($row = mysqli_fetch_object($rs)) {
						array_push($items, $row);
					}
					$result["rows"] = $items;
				}
			}
		}
	} catch (Exception $e) {
	} finally {
		$data_close_conn = close_connection_finally($link, $remote_ip, $member_id, "", "", "");
		if ($data_close_conn["status"] == "false") $data = $data_close_conn;
	}
	echo json_encode($result);
?>