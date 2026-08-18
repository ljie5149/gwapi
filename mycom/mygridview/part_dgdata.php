<?php
    include("./../../common/entry.php");
    global $g_sidemenu, $g_ProjectIcon, $g_db_table;
    uiLocationPage();
	
	$root_url = $g_root_url;
	$data = array();
	$remote_ip      = get_remote_ip();
	$member_id      = isset($_SESSION['userid'   ]) ? $_SESSION['userid'    ] : '';
	$authority      = isset($_SESSION['authority']) ? $_SESSION['authority' ] : '';
	$priority       = isset($_SESSION['priority' ]) ? $_SESSION['priority'  ] : '';
	$mode           = isset($_GET['mode'    ]) ? $_GET['mode'       ]    : '';
	$idx4pdctkind 	= isset($_GET['idx']) ? $_GET['idx'] : '';
	
    // $page_offset    = isset($_GET['offset'  ]) ? intval($_GET['offset']) : 20;
    $url_param_str  = 'mode='.$mode;

	$page 	= isset($_POST['page']) ? intval($_POST['page']) : 1;
	$rows 	= isset($_POST['rows']) ? intval($_POST['rows']) : 10;
	$sort 	= isset($_POST['sort']) ? strval($_POST['sort']) : 'nid';
	$order 	= isset($_POST['order']) ? strval($_POST['order']) : 'ASC';
	$offset = ($page - 1) * $rows;

    $sub_caption = '';
    $short_text_len = 30;
    switch($mode) {
        case "pdctkind": // 推播管理
            $menu_idx         = $g_sidemenu_idx['product'];
            $submenu_idx      = 0;
            $table            = $g_db_table['infoproductkind'.$idx4pdctkind];
            $without_columns  = "null,sid,level,script,remark";
            $editable_columns = "";
            $search_columns   = "create_date,modify_date,member_sid,parent_sid,name,avalible";
            
            $sub_caption      = " - 第".$idx4pdctkind.'層';
            if (!empty($idx4pdctkind)) $url_param_str .= '&idx='.$idx4pdctkind;
            $short_text_len = 30;
            break;
    }
    $caption = getSubMenuString($menu_idx, $submenu_idx);

    $total_rows = 0;
	$result = array();
	$items = array();
	$column_info = array();
    $db = new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, $without_columns, $search_columns);

            // part_dgdata.php 顯示資料條件
			$sql = "select count(*) as total_rows from $table";
			$rs = $db->query($link, $sql);
			if (!is_null($rs) && mysqli_num_rows($rs) > 0) {
				$row 			 = mysqli_fetch_array($rs);
				$row_count 		 = $row['total_rows'];
				$result["total"] = $row_count;
	
				$rs = $db->getData($link, $table, "", "nid,sid,create_date,member_sid,name", "", "", $sort." ".$order, "LIMIT $offset, $rows");
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
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
	echo json_encode($result);
?>