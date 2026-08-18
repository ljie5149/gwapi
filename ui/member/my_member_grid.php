<?php
    include("./../../common/entry.php");
	global $g_db_table, $g_root_url, $g_PageTitle;
    uiLocationPage();
	
	$root_url = $g_root_url;
	$data = array();
	$remote_ip = get_remote_ip();
	$member_id = isset($_SESSION['userid']) ? $_SESSION['userid']: '';
	$authority = isset($_SESSION['authority']) ? $_SESSION['authority']: '';
	
	$menu_idx = $g_sidemenu_idx['member'];
    $submenu_idx = 0;
	$caption = getSubMenuString($menu_idx, $submenu_idx);
    $table = $g_db_table['datamember'];

	$column_info = array();
    $db= new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, "null,create_date,start_date,cur_coupon,cur_point,script,remark,tel,advertising_id,device_id,pwd,isforeign,blood_type,authorization_page");
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
        <link href="./../vendor/easyui/easyui.css" rel="stylesheet" type="text/css">
        <link href="./../vendor/easyui/icon.css" rel="stylesheet" type="text/css">
        <script src="./../vendor/easyui/jquery.min.js" type="text/javascript"></script>
        <script src="./../vendor/easyui/jquery.easyui.min.js" type="text/javascript"></script>
        <script src="./../vendor/easyui/datagrid-detailview.js" type="text/javascript"></script>
        <script type="text/javascript">
            $(function(){
                $('#dg').datagrid({
                });
                // 當使用者改變瀏覽器視窗大小時 //resize to fit page size 
                $(window).on("resize", resizejqGridWidth);
            });
            function resizejqGridWidth() {
                //重新抓jqGrid容器的新width
                let newWidth = $('#dg').closest("body").parent().width();
                // alert(newWidth);
                //是否縮齊column(相當於shrinkToFit)
                let shrinkToFit = false;
                // $('#dg').jqGrid("resizeGridWidth", newWidth, shrinkToFit);
                document.getElementById('dg').style.width = newWidth;
            }
        </script>
    </head>
    <body>
        <table id="dg"
               url='./../../mymember_grid/data_src.php?table=<?php echo $table; ?>'
               pagination="true" multiSort="true"
               title='<?php echo $caption; ?>'
               singleSelect="true" fitColumns="true">
            <thead>
                <tr>
                    <?php
                        for ($i = 0; $i < count($column_info); $i++) {
                            $com = $column_info[$i];
                            $hidden = ($com[0] == "true");
                            $field  = $com[1]; $name   = $com[2];
                            if (!$hidden)
                                echo '<th field="'.$field.'">'.$name.'</th>';
                        }
                        echo '<th field="Actions">操作</th>';
                    ?>
                </tr>
            </thead>
        </table>
    </body>
</html>