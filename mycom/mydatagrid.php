<?php
    include("./../common/entry.php");
	global $g_db_table, $g_root_url, $g_PageTitle, $g_sidemenu_idx;
    global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
    global $g_vew_member_out_col, $g_vew_conferenceroom_out_col, $g_vew_msgcenter_out_col, $g_vew_pdctkind_out_col; // without_columns
    global $g_vew_member_sbh_col, $g_vew_conferenceroom_sbh_col, $g_vew_msgcenter_sbh_col, $g_vew_pdctkind_sbh_col; // showbuthide_columns
    global $g_vew_member_lke_col, $g_vew_conferenceroom_lke_col, $g_vew_msgcenter_lke_col, $g_vew_pdctkind_lke_col; // lockedit_columns
    global $g_vew_member_sch_col, $g_vew_conferenceroom_sch_col, $g_vew_msgcenter_sch_col, $g_vew_pdctkind_sch_col; // search_columns
    global $g_funcidx_main, $g_funcidx_srch, $g_funcidx_add, $g_funcidx_edit, $g_funcidx_delete, $g_funcidx_import, $g_funcidx_export;
    global $g_is_partner_zhtw, $g_is_partner, $g_news_kind_zhtw, $g_news_kind;
    uiLocationPage();
	
	$root_url = $g_root_url;
	$data = array();
	$remote_ip      = get_remote_ip();
	$member_id      = isset($_SESSION['userid'   ]) ? $_SESSION['userid'    ] : '';
	$authority      = isset($_SESSION['authority']) ? $_SESSION['authority' ] : '';
	$priority       = isset($_SESSION['priority' ]) ? $_SESSION['priority'  ] : '';
	$mode           = isset($_GET['mode'    ]) ? $_GET['mode'       ]    : '';
    $page_offset    = isset($_GET['offset'  ]) ? intval($_GET['offset']) : 20;
    $url_param_str  = 'mode='.$mode;

    $sub_caption = '';
    $idx4pdctkind = ''; $kind_parent = null;
    $short_text_len = 30;

    $flag = getAuthorEnable($mode, $authority, $g_funcidx_add);
    $allow_add = ($flag) ? '' : 'display:none;';

    $flag = getAuthorEnable($mode, $authority, $g_funcidx_edit);
    $allow_edit = ($flag) ? '' : 'display:none;';

    $flag = getAuthorEnable($mode, $authority, $g_funcidx_delete);
    $allow_delete = ($flag) ? '' : 'display:none;';

    $flag = getAuthorEnable($mode, $authority, $g_funcidx_import);
    $allow_import = ($flag) ? '' : 'display:none;';

    $flag = getAuthorEnable($mode, $authority, $g_funcidx_export);
    $allow_export = ($flag) ? '' : 'display:none;';

    $allow_extra = (empty($allow_import) || empty($allow_export)) ? '' : 'display:none;';
    
    // echo 'allow_add :'.$allow_add;
    // echo $mode.','.$authority.';'.$g_funcidx_add.'flag :'.$flag;
    $msg_avalible    = $g_base_avalible;
    $gender_avalible = $g_gender_options;
    $role_avalible   = $g_role_options;
    $caption = "";
    switch($mode) {
        case "member": // 一般會員
            $menu_idx         = $g_sidemenu_idx['member'];
            $submenu_idx      = 0;
            $table            = $g_db_table['datamember'];
            $without_columns  = $g_vew_member_out_col; $showbuthide_columns = $g_vew_member_sbh_col;
            $lockedit_columns = $g_vew_member_lke_col; $search_columns      = $g_vew_member_sch_col;
            $msg_avalible     = $g_member_avalible_code;
            $sales_array = getKeyVal4Sales($member_id);
            // var_dump($sales_array);
            break;
        case "news": // 最新消息
            //create_date modify_date member_sid	title	 	summary 	content 	start_date	end_date	sort	 	file_contentfile_path	avalible 	
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 0;
            $table            = $g_db_table['datanews'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            break;
        case "banner": // banner管理
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 1;
            $table            = $g_db_table['databanner'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            break;
        case "sightseeing": // sightseeing管理
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 2;
            $table            = $g_db_table['datasightseeing'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            break;
        case "store": // store管理
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 3;
            $table            = $g_db_table['datastore'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            break;
        case "agency": // agency
            $menu_idx         = $g_sidemenu_idx['agency_center'];
            $submenu_idx      = 0;
            $table            = $g_db_table['dataagency'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            break;
        case "agencyunit": // agencyunit
            $menu_idx         = $g_sidemenu_idx['agency_center'];
            $submenu_idx      = 1;
            $table            = $g_db_table['dataagencyunit'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            
            $param = []; $error = ""; $agency_array = [];
            $api_ret    = callAPI($error, $g_root_url."api/JTG_agency.php", $param, "GET");
            $json_data  = json_decode($api_ret, true);
            // echo $api_ret;
            if ($json_data["status"] == "true") {
                $data01 = $json_data["data"];
                $data_agency = $data01["data"];
                foreach ($data_agency as $item) {
                    $agency_array[$item['sid']] = $item['name'];
                }
            }
            break;
        case "applyitem": // applyitem
            $menu_idx         = $g_sidemenu_idx['apply_service'];
            $submenu_idx      = 0;
            $table            = $g_db_table['dataapplyitem'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            break;
        case "apply": // apply
            $menu_idx         = $g_sidemenu_idx['apply_service'];
            $submenu_idx      = 0;
            $table            = $g_db_table['dataapply'];
            $without_columns  = $g_vew_msgcenter_out_col; $showbuthide_columns = $g_vew_msgcenter_sbh_col;
            $lockedit_columns = $g_vew_msgcenter_lke_col; $search_columns      = $g_vew_msgcenter_sch_col;
            break;
    }
    // echo "menu_idx :$menu_idx, submenu_idx :$submenu_idx\n";
    $caption = getSubMenuString($menu_idx, $submenu_idx);

    $total_rows = 0;
	$column_info = array();
    $db= new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, $without_columns, $showbuthide_columns, $lockedit_columns, $search_columns);

            // member_tbody.php 顯示資料條件
            $prev_search = [];
            $where_str = "";
            for ($i = 0; $i < count($column_info); $i++) {
                $col_info = $column_info[$i];
                $tmp = isset($_GET['srch'.$col_info[$g_fldidx_name]]) ? $_GET['srch'.$col_info[$g_fldidx_name]] : "";
                $tmp_s = ""; $tmp_e = "";
                if (stripos($col_info[$g_fldidx_name], '_date') > -1) {
                    $tmp_s = isset($_GET['srch'.$col_info[$g_fldidx_name].'_s']) ? $_GET['srch'.$col_info[$g_fldidx_name].'_s'] : "";
                    $tmp_e = isset($_GET['srch'.$col_info[$g_fldidx_name].'_e']) ? $_GET['srch'.$col_info[$g_fldidx_name].'_e'] : "";
                }
                if (strlen($tmp_s) > 0 && strlen($tmp_e) > 0) {
                    $where_str .= " AND ".$col_info[$g_fldidx_name]." BETWEEN '".$tmp_s." 00:00:00' AND '".$tmp_e." 23:59:59'";
                } else if (strlen($tmp_s) > 0) {
                    // $where_str .= " AND ".$col_info[$g_fldidx_name]." = '".$tmp_s."'";
                    $where_str .= " AND ".$col_info[$g_fldidx_name]." BETWEEN '".$tmp_s." 00:00:00' AND '".$tmp_s." 23:59:59'";
                } else if (strlen($tmp_e) > 0) {
                    // $where_str .= " AND ".$col_info[$g_fldidx_name]." = '".$tmp_e."'";
                    $where_str .= " AND ".$col_info[$g_fldidx_name]." BETWEEN '".$tmp_e." 00:00:00' AND '".$tmp_e." 23:59:59'";
                }
                if (strlen($tmp) > 0) {
                    $where_str .= " AND ".$col_info[$g_fldidx_name]." LIKE '%".$tmp."%'";
                }
                
                if (stripos($col_info[$g_fldidx_name], '_date') > -1) {
                    $prev_search[$col_info[$g_fldidx_name]."_s"] = $tmp_s;
                    $prev_search[$col_info[$g_fldidx_name]."_e"] = $tmp_e;
                } else {
                    $prev_search[$col_info[$g_fldidx_name]] = $tmp;
                }
            }

            $row_count = 0;
            $rs = $db->getData($link, $table, "", "count(*) as total_rows", $where_str);
			if (!is_null($rs) && mysqli_num_rows($rs) > 0) {
				$row = mysqli_fetch_array($rs);
				$row_count = intval($row['total_rows']);
			}
            $total_rows = $row_count;
        }
    } catch (Exception $e) {
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }

    // member_tfoot.php 操作頁數
    // get current page number from URL parameter
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;

    // calculate starting and ending index of rows to display
    $total_pages = ceil($row_count / $page_offset);
    $start_index = ($page - 1) * $page_offset;
    $end_index = $start_index + $page_offset; // min($start_index + $page_offset, $total_rows - 1);
    // echo $start_index.",".$end_index.",".$total_pages;
    
    $sort = isset($_GET['sort']) ? $_GET['sort'] : "";
    $sort_flag = isset($_GET['sort_flag']) ? $_GET['sort_flag'] : "";
    
    // member_thead.php 顯示排序符號
    $show_sort = "";
    if (strlen($sort_flag) > 0) {
        $sort_flag = ($sort_flag == "0") ? "1" : "0";
        $show_sort = ($sort_flag == "0") ? "▼" : "▲";
    }
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="keywords" content="jquery,ui,easy,easyui,web">
        <meta name="description" content="easyui help you build your web page easily!">
        <title><?php echo $g_PageTitle; ?></title>

        <!-- 新 Bootstrap4 核心 CSS 文件 ExtraMenu -->
        <link href="./../css/v4.3.1/bootstrap.min.css" rel="stylesheet" type="text/css">
        
        <link href="./../vendor/easyui/easyui.css" rel="stylesheet" type="text/css">
        <link href="./../vendor/easyui/icon.css" rel="stylesheet" type="text/css">
		<link href="./../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="./../css/mydatagrid.css" rel="stylesheet" type="text/css">
        
        <script src="./../vendor/easyui/jquery.min.js" type="text/javascript"></script>
        <script src="./../vendor/easyui/jquery.easyui.min.js" type="text/javascript"></script>
        <script src="./../vendor/easyui/datagrid-detailview.js" type="text/javascript"></script>

        <!-- jQuery文件。務必在bootstrap.min.js 之前引入 ExtraMenu -->
        <script src="./../js/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>

        <!-- popper.min.js 用於 對話框、提示、下拉選單 ExtraMenu -->
        <script src="./../js/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        
        <!-- 最新的 Bootstrap4 核心 JavaScript 文件 ExtraMenu -->
        <script src="./../js/v4.3.1/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

        <script src="./../js/page_ctrl.js" type="text/javascript"></script>
        <script src="./../js/jackygrid.js" type="text/javascript"></script>
        <script src="./../js/jackyapi.js" type="text/javascript"></script>
        
        <!-- import/export dialog 使用 -->
        <link href="./../css/mydialog.css" rel="stylesheet" type="text/css">
        <script src="./../vendor/jquery/2.1.1/jquery.min.js"></script>
        
        <!-- import dialog 使用 -->
        <script src="./../vendor/xlsx.0.15.1/xlsx.full.min.js" type="text/javascript"></script><!-- XLSX -->
        <script src="./../js/jackydrag.js" type="text/javascript"></script>
    </head>
    <body onload="resizeGrid();onManualLoad();" onresize="resizeGrid()">
        <table id="dg">
            <thead id="myTableHead">
                <?php include("./mygrid/part_thead.php"); ?>
            </thead>
            <tbody id="myTableBody">
                <?php include("./mygrid/part_tbody.php"); ?>
            </tbody>
            <tfoot>
                <?php include("./mygrid/part_tfoot.php"); ?>
            </tfoot>
        </table>
        <br>
        <?php include("./dlg_db2xls.php"); ?>
        <?php include("./dlg_xls2db.php"); ?>
    </body>
</html>