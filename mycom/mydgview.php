<?php
    include("./../common/entry.php");
    global $g_sidemenu, $g_ProjectIcon, $g_db_table;
    uiLocationPage();
	
	$root_url = $g_root_url;
	$data = array();
	$remote_ip      = get_remote_ip();
	$member_id      = isset($_SESSION['userid'   ]) ? $_SESSION['userid'    ] : '';
	$authority      = isset($_SESSION['authority']) ? $_SESSION['authority' ] : '';
	$priority       = isset($_SESSION['priority' ]) ? $_SESSION['priority'  ] : '';
	$mode           = isset($_GET['mode'    ]) ? $_GET['mode'] : '';
    $idx4pdctkind   = isset($_GET['idx'     ]) ? $_GET['idx' ] : '';
    $url_param_str  = 'mode='.$mode;
    $url_detail_param = $url_param_str;
    $sub_caption = '';

    // 顯示排序符號
    $sort = isset($_GET['sort']) ? $_GET['sort'] : "";
    $sort_flag = isset($_GET['sort_flag']) ? $_GET['sort_flag'] : "";
    $show_sort = "";
    if (strlen($sort_flag) > 0) {
        $sort_flag = ($sort_flag == "0") ? "1" : "0";
        $show_sort = ($sort_flag == "0") ? "▼" : "▲";
    }

    $short_text_len = 30;
    switch($mode) {
        case "pdctkind": // 商品類別管理
            $menu_idx         = $g_sidemenu_idx['product'];
            $submenu_idx      = 0;
            $table            = $g_db_table['infoproductkind'.$idx4pdctkind];
            $without_columns  = "null,level,script,remark";
            $lockedit_columns = "";
            $search_columns   = "create_date,modify_date,member_sid,parent_sid,name,avalible";
            // mode=pdctkind&idx=02&parent_sid=
            $val = intval($idx4pdctkind);
            $url_detail_param.= sprintf("&idx=%02d&", ++$val);
            $sub_caption      = " - 第".$idx4pdctkind.'層';
            if (!empty($idx4pdctkind)) $url_param_str .= '&idx='.$idx4pdctkind;
            $short_text_len = 30;
            break;
    }
    $caption = getSubMenuString($menu_idx, $submenu_idx);

    $total_rows = 0;
	$column_info = array();
    $db= new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, $without_columns, $search_columns);
        }
    } catch (Exception $e) {
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="keywords" content="jquery,ui,easy,easyui,web">
        <meta name="description" content="easyui help you build your web page easily!">
        <title><?php echo $g_PageTitle; ?></title>
        <link href="<?php echo $g_ProjectIcon ?>" rel="icon" type="image/png"><!-- Custom fonts for this template-->
        <link href="./../vendor/easyui/easyui.css" rel="stylesheet" type="text/css">
        <link href="./../vendor/easyui/icon.css" rel="stylesheet" type="text/css">
        <script src="./../vendor/easyui/jquery.min.js" type="text/javascript"></script>
        <script src="./../vendor/easyui/jquery.easyui.min.js" type="text/javascript"></script>
        <script src="./../vendor/easyui/datagrid-detailview.js" type="text/javascript"></script>
    </head>
    <body>
        <table id="dg" style="width:1000px;height:500px"
                url="./mygridview/part_dgdata.php?<?php echo $url_param_str ?>".
                pagination="true"
                title="<?php echo $caption.$sub_caption ?>"
                singleSelect="true" fitColumns="true">
            <thead>
                <tr>
                    <?php
                        for ($i = 0; $i < count($column_info); $i++) {
                            $com           = $column_info[$i];
                            
                            $field    = $com[$g_fldidx_name];
                            $name     = $com[$g_fldidx_comment];
                            $show     = ($com[$g_fldidx_show]         == "true");
                            $hidden   = ($com[$g_fldidx_showbuthide]  == "true");
                            $search   = ($com[$g_fldidx_srch]         == "true");
                            $lockedit = ($com[$g_fldidx_lockedit]     == "true");
                            
                            $name          = str_replace('序號', '', $name);
                            $show_sortflag = ($sort != $field) ? "" : $show_sort;
                            $style = getGridColWidth($field);
                            if ($i > 0) echo '<div class="vr"></div>';
                            if ($show) {
                                $hidden_field = ($field == "sid" || $field == "parent_sid") ? 'hidden' : '';
                                echo '<th field="'.$field.'" width="80" '.$hidden_field.'>'.$name.'</th>';
                            }
                                // echo '<th field="'.$field.'" style="border-right: 1px solid #e5e5e5; cursor:pointer; '.$style.'"
                                //         onclick="clickField(\''.$field.'\','.$sort_flag.')">'.$name.'<span class="btn-blue">'.$show_sortflag.'</span></th>';
                        }
                    ?>
                </tr>
            </thead>
        </table>
        <script type="text/javascript">
            $(function(){
                $('#dg').datagrid({
                    view: detailview,
                    detailFormatter:function(index,row){
                        return '<div id="ddv-' + index + '" style="padding:5px 0"></div>';
                    },
                    onExpandRow: function(index,row){
                                console.log(row);
                        $('#ddv-'+index).panel({
                            border:false,
                            cache:false,
                            href:'./mygridview/part_dgdetail.php?<?php echo $url_detail_param ?>parent_sid='+row.sid,
                            onLoad:function(){
                                $('#dg').datagrid('fixDetailRowHeight',index);
                            }
                        });
                        $('#dg').datagrid('fixDetailRowHeight',index);
                    }
                });
            });
        </script>
    </body>
</html>