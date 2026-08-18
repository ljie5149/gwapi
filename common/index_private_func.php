<?php
    function getordercount($remote_ip, $member_id, $a) {
		$link	= null;
		$total 	= 0;
		$data 	= array();
		$db 	= new CXDB($remote_ip);
		try {
			$data = $db->connect($link, $member_id);
			if ($data["status"] == "true") {
				$sql = "SELECT count(*) AS total FROM orderinfo WHERE order_status='1' AND store_id=".$_SESSION['loginsid']." ";
				if ($a != "") $sql.= " AND order_date >= '".$a." 00:00:00' AND order_date <= '".$a." 23:59:59'";
				$result = $db->query($link, $sql);
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					while ($row2 = mysqli_fetch_array($result)) {
						$total = $row2['total'];
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id, "", "", "");
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $total;
	}
	function getordersum($remote_ip, $member_id,$a) {
		$link	= null;
		$total 	= 0;
		$data 	= array();
		$db 	= new CXDB($remote_ip);
		try {
			$sql = "SELECT sum(order_amount) as total FROM orderinfo where order_status='1' and store_id=".$_SESSION['loginsid']." ";
			if ($a != "") $sql.= " AND order_date >= '".$a." 00:00:00' AND order_date <= '".$a." 23:59:59'";
			$result = $db->query($link, $sql);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				while($row2 = mysqli_fetch_array($result)) {
					$total = $row2['total'];
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id, "", "", "");
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $total;
	}
?>