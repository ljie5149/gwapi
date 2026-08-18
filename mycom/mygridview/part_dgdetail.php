<?php
    include("./../../common/entry.php");
	$items = array();
	$data = array();
	$remote_ip = get_remote_ip();
	$member_id = isset($_SESSION['userid']) ? $_SESSION['userid']: '';
	$mode			= isset($_GET['mode'    ]) ? $_GET['mode'] : '';
    $idx4pdctkind   = isset($_GET['idx'     ]) ? $_GET['idx' ] : '';
	$parent_sid 	= isset($_REQUEST['parent_sid']) ? $_REQUEST['parent_sid']: '';

    $short_text_len = 30;
    switch($mode) {
        case "pdctkind": // 商品類別管理
            $menu_idx         = $g_sidemenu_idx['product'];
            $submenu_idx      = 0;
            $table            = $g_db_table['infoproductkind'.$idx4pdctkind];
            $without_columns  = "null,member_sid,sid,level,parent_sid,script,remark";
            $editable_columns = "";
            $search_columns   = "create_date,modify_date,member_sid,parent_sid,name,avalible";
            $val = intval($idx4pdctkind);
            $sub_caption      = " - 第".$idx4pdctkind.'層';
            $short_text_len = 30;
            break;
    }
    $caption = getSubMenuString($menu_idx, $submenu_idx);

	$column_info = array();
    $db= new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, $without_columns, "", "", $search_columns);
			// var_dump($column_info);
        }
    } catch (Exception $e) {
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
?>
<style type="text/css">
	.dv-table td{
		border : 1px solid #15428B;
	}
	.dv-label{
		font-weight:bold;
		color:#15428B;
		width:100px;
	}
</style>

<table class="dv-table" style="width:100%;">
	<thead>
		<tr>
			<?php
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];
					
					$field  = $com[$g_fldidx_name];
					$name   = $com[$g_fldidx_comment];
					$show   = ($com[$g_fldidx_show]         == "true");
					$hidden = ($com[$g_fldidx_showbuthide]  == "true");
					$search = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					
					$name  = str_replace('序號', '', $name);
					$style = getGridColWidth($field);
					if ($i > 0) echo '<div class="vr"></div>';
					if ($show) {
						$hidden_field = '';// ($field == "sid") ? 'hidden' : '';
						echo '<th field="'.$field.'" width="80" '.$hidden_field.'>'.$name.'</th>';
					}
				}
			?>
		</tr>
	</thead>
	<tbody>
		<?php
			$db = new CXDB($remote_ip);
			try {
				$data = $db->connect($link, $member_id, "");
				if ($data["status"] == "true") {
					// $parent_sid = mysqli_real_escape_string($link, $parent_sid);

					$result = $db->getData($link, $table, "", "*", " AND parent_sid='$parent_sid'");
					if (!is_null($result) && mysqli_num_rows($result) > 0) {
						while ($row = mysqli_fetch_array($result)) {
							// $bg_color = (intval($row['id']) % 2 == 1) ? "#f9f9f9": "white";
							echo '<tr>';
							$rcdid = $row[$column_info[0][$g_fldidx_name]];
							for ($i = 0; $i < count($column_info); $i++) {
								$col_info = $column_info[$i];
								
								$field    = $col_info[$g_fldidx_name];
								$name     = $col_info[$g_fldidx_comment];
								$show     = ($col_info[$g_fldidx_show]         == "true");
								$hidden   = ($col_info[$g_fldidx_showbuthide]  == "true");
								$search   = ($col_info[$g_fldidx_srch]         == "true");
								$lockedit = ($col_info[$g_fldidx_lockedit]     == "true");

								if ($show) { // show
									$style = ''; // getGridColWidth($field);
									$value = $row[$field];
									// if ($field == 'parent_sid') {
									// 	$value = getPdctKindStrByPntSid($kind_parent, $row[$field]);
									// }
									echo '<td style="margin-left:0px; font-size:14px; '.$style.'" id="'.$field.'-'.$rcdid.'" title="'.$value.'">'.getShortText4Show($value, $short_text_len)."</td>";
								}
							}
							echo "</tr>";
						}
					}
				}
			} catch (Exception $e) {
			} finally {
				$data_close_conn = close_connection_finally($link, $remote_ip, $member_id, "", "", "");
				if ($data_close_conn["status"] == "false") $data = $data_close_conn;
			}
		?>
	</tbody>
</table>

