<?php
    include("./../../common/entry.php");
	global $g_db_table, $g_root_url, $g_PageTitle, $g_images_dir;
    global $g_base_avalible, $g_gender_options, $g_role_options, $g_pdct_options;
    global $g_newsgraph_path, $g_memberimg_path, $g_pdf_path;
    global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
    global $g_add_member_out_col, $g_add_conferenceroom_out_col, $g_add_msgcenter_out_col, $g_add_pdctkind_out_col; // without_columns
    global $g_add_member_sbh_col, $g_add_conferenceroom_sbh_col, $g_add_msgcenter_sbh_col, $g_add_pdctkind_sbh_col; // showbuthide_columns
    global $g_add_member_lke_col, $g_add_conferenceroom_lke_col, $g_add_msgcenter_lke_col, $g_add_pdctkind_lke_col; // lockedit_columns
    global $g_add_member_sch_col, $g_add_conferenceroom_sch_col, $g_add_msgcenter_sch_col, $g_add_pdctkind_sch_col; // search_columns
    global $g_country_code, $g_dft_country_code;
    global $g_fields_cbobj;
    global $g_fields_memshow, $g_fields_memneed, $g_dft_order_limit, $g_dft_priority, $g_fields_pdctdetlneed;
    global $g_is_partner_zhtw, $g_is_partner, $g_news_kind_zhtw, $g_news_kind;

    $useobjselect_fields = $g_fields_cbobj;
    $msg_avalible        = $g_base_avalible;
    $gender_avalible     = $g_gender_options;
    $role_avalible       = $g_role_options;
    uiLocationPage();
	
	$root_url = $g_root_url;
	$data = array(); $sales_array = array();
	$remote_ip = get_remote_ip();
	$member_id = isset($_SESSION['userid'   ]) ? $_SESSION['userid'   ] : '';
	$authority = isset($_SESSION['authority']) ? $_SESSION['authority'] : '';
	$priority  = isset($_SESSION['priority' ]) ? $_SESSION['priority' ] : '';
	$mode      = isset($_GET['mode'         ]) ? $_GET['mode'         ] : '';
    $url_param_str  = '?mode='.$mode;
    $parent_sel = isset($_COOKIE['parent_sid']) ? $_COOKIE['parent_sid'] : '';
    
    $mem_show = ""; $mem_auth = ""; $mem_need = ""; $mem_olimit = ""; $mem_priority = "";
    $sub_caption = ""; $kind_parent = null;
    $display_preview = 'hidden';
    $image_path       = "";
    $drag_js       = "./../../js/jackydrag.js";
    $processFormDataSel = "processFormData";
    switch($mode) {
        case "member": // 一般會員
            $menu_idx           = $g_sidemenu_idx['member'];
            $submenu_idx        = 0;
            $table              = $g_db_table['datamember'];
            $without_columns    = $g_add_member_out_col; $showbuthide_columns = $g_add_member_sbh_col;
            $lockedit_columns   = $g_add_member_lke_col; $search_columns      = $g_add_member_sch_col;
            $msg_avalible       = $g_member_avalible_code;
            $drag_js            = "./../../js/jackydrag4pdct.js";
            $image_path         = $g_memberimg_path;

            $param = array();
            $param["sso_token"] = isset($_SESSION['sso_token']) ? $_SESSION['sso_token'] : '';
            // $api_ret    = callAPI($error, $g_root_url."api/JTG_getsales.php", $param, "POST");
            // $json_data  = json_decode($api_ret);
            // if ($json_data->status == "true") {
            //     $sales_recvaddr = $json_data->json->default;
            //     $sales_array = $json_data->json->data;
            // }
            $mem_show     = json_encode($g_fields_memshow);
            $mem_need     = json_encode($g_fields_memneed);
            $mem_auth     = json_encode($g_dft_author_page);
            $mem_olimit   = json_encode($g_dft_order_limit);
            // echo $mem_olimit;
            $mem_priority = json_encode($g_dft_priority);
            $mem_show     = str_replace('"', ';;', $mem_show);
            $mem_need     = str_replace('"', ';;', $mem_need);
            $mem_auth     = str_replace('"', ';;', $mem_auth);
            $mem_olimit   = str_replace('"', ';;', $mem_olimit);
            $mem_priority = str_replace('"', ';;', $mem_priority);
            // echo $mem_olimit;
            break;
        case "news": // 最新消息
            $display_preview = '';
            //create_date modify_date member_sid	title	 	summary 	content 	start_date	end_date	sort	 	file_contentfile_path	avalible 	
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 0;
            $table            = $g_db_table['datanews'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col;
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            $image_path       = $g_newsimg_path;
            $drag_js          = "./../../js/jackydrag4pdct.js";
            break;
        case "banner": // banner管理
            $display_preview  = '';
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 1;
            $table            = $g_db_table['databanner'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col.',modify_date,message_kind,title,summary,content,start_date,end_date';
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            $image_path       = $g_bannerimg_path;
            $drag_js          = "./../../js/jackydrag4pdct.js";
            break;
        case "sightseeing": // sightseeing管理
            $display_preview  = '';
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 2;
            $table            = $g_db_table['datasightseeing'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col.',modify_date,message_kind,title,summary,content,start_date,end_date';
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            $image_path       = $g_sightseeingimg_path;
            $drag_js          = "./../../js/jackydrag4pdct.js";
            break;
        case "store": // store管理
            $display_preview  = '';
            $menu_idx         = $g_sidemenu_idx['message_center'];
            $submenu_idx      = 3;
            $table            = $g_db_table['datastore'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col.',modify_date,message_kind,title,summary,content,start_date,end_date';
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            $image_path       = $g_storeimg_path;
            $drag_js          = "./../../js/jackydrag4pdct.js";
            break;
        case "agency": // agency
            $menu_idx         = $g_sidemenu_idx['agency_center'];
            $submenu_idx      = 0;
            $table            = $g_db_table['dataagency'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col.',modify_date,message_kind,title,summary,content,start_date,end_date';
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            $image_path       = $g_storeimg_path;
            $drag_js          = "./../../js/jackydrag4pdct.js";
            break;
        case "agencyunit": // agencyunit
            $menu_idx         = $g_sidemenu_idx['agency_center'];
            $submenu_idx      = 1;
            $table            = $g_db_table['dataagencyunit'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col.',modify_date,message_kind,title,summary,content,start_date,end_date';
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            $image_path       = $g_storeimg_path;
            $drag_js          = "./../../js/jackydrag4pdct.js";
            
            $param = []; $error = ""; $agency_array = [];
            $api_ret    = callAPI($error, $g_root_url."api/JTG_agency.php", $param, "GET");
            $json_data  = json_decode($api_ret, true);
            // echo $api_ret;
            if ($json_data["status"] == "true") {
                $data01 = $json_data["data"];
                $data_agency = $data01["data"];
                foreach ($data_agency as $item) {
                    $agency_array[$item['name']] = $item['sid'];
                }
            }
            break;
        case "applyitem": // applyitem
            $menu_idx         = $g_sidemenu_idx['apply_service'];
            $submenu_idx      = 0;
            $table            = $g_db_table['dataapplyitem'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col.',modify_date,message_kind,title,summary,content,start_date,end_date';
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            break;
        case "apply": // apply
            $menu_idx         = $g_sidemenu_idx['apply_service'];
            $submenu_idx      = 1;
            $table            = $g_db_table['dataapply'];
            $without_columns  = $g_add_msgcenter_out_col; $showbuthide_columns = $g_add_msgcenter_sbh_col.',modify_date,message_kind,title,summary,content,start_date,end_date';
            $lockedit_columns = $g_add_msgcenter_lke_col; $search_columns      = $g_add_msgcenter_sch_col;
            $image_path       = $g_pdf_path;
            $drag_js          = "./../../js/jackydragpdf.js";
            $processFormDataSel = "processFormData4Pdf";
            
            $param = []; $error = ""; $applyitem_array = [];
            $api_ret    = callAPI($error, $g_root_url."api/JTG_applyitem.php", $param, "GET");
            $json_data  = json_decode($api_ret, true);
            // echo $api_ret;
            if ($json_data["status"] == "true") {
                $data01 = $json_data["data"];
                $data_agency = $data01["data"];
                foreach ($data_agency as $item) {
                    $applyitem_array[$item['name']] = $item['sid'];
                }
            }
            break;
    }
    $caption = "[新增] ".getSubMenuString($menu_idx, $submenu_idx).$sub_caption;
    
    $db= new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, $without_columns, $showbuthide_columns, $lockedit_columns, $search_columns);
        }
    } catch (Exception $e) {
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
    $all_fields = "";
    for ($i = 0; $i < count($column_info); $i++) {
        $com = $column_info[$i];

        $field = $com[$g_fldidx_name];
        $show  = ($com[$g_fldidx_show] == "true");
        if ($show) {
            $all_fields.= (strlen($all_fields) > 0) ? "," : "";
            $all_fields.= $field;
        }
    }
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="keywords" content="jquery,ui,easy,easyui,web">
        <meta name="description" content="easyui help you build your web page easily!">
        <title><?php echo $g_PageTitle; ?></title>

        <link href="./../../css/sb-admin-2.min.css" rel="stylesheet" type="text/css"> <!-- page-top button face -->
        <link href="./../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"> <!-- page-top button "^" flag -->
        <script src="./../../vendor/jquery/jquery.min.js"></script> <!-- page-top button function and you have to define component id <body id="page-top"> -->

        <link href="./../../css/mydialog.css" rel="stylesheet" type="text/css">
        <SCRIPT src="<?php echo $drag_js; ?>"></SCRIPT>
        <SCRIPT src="./../../js/jackyapi.js"></SCRIPT>
        <SCRIPT src="./../../js/jackypreview.js"></SCRIPT>
        <SCRIPT src="./../../js/jackygrid.js"></SCRIPT> <!-- chSelect4ParentSid function -->
        <SCRIPT src="./../../js/comm.js"></SCRIPT>
        <SCRIPT>
            function showAuthorDlg() {
                initDialog();
                $('.overlay-author-page').css('display', 'block');
            }
            function chSelect4ParentSid() {
                var select = document.getElementById("parent_sid");
                var selectedValue = select.value; // Get the selected value
                mysetCookie('parent_sid', selectedValue, 0.1);
                console.log('parent_sid:' + selectedValue);
            }
            function openPreview(mode) {
                var result = ""; var img = "";
                var js_fldidx_name    = <?php echo $g_fldidx_name ?>;
                var js_fldidx_show    = <?php echo $g_fldidx_show ?>;
                document.getElementById('viewer').style.display = 'block';
                var ContentElement = document.getElementById('preview_content');
                window.scrollTo({ left: 0, top: document.body.scrollHeight, behavior: "smooth" });
                var js_fields = JSON.parse('<?php echo json_encode($column_info) ?>');
                // console.log(js_fields);
                result = getPreviewContent(mode, js_fields, js_fldidx_name, js_fldidx_show);
                ContentElement.innerHTML = result;
            }
            function processFormData() {
                console.log("call processFormData()");
                var js_mode = '<?php echo $mode ?>';
                var js_graph = "";
                var ids = ["mverify_img", "sverify_img", "product_img", "product2_img", "select_img", "setup_img", "size_img", "extra_img"];
                if (["member", "news", "banner", "sightseeing", "store"].includes(js_mode)) ids = ["head_img"];

                try {
                    if (["member", "news", "banner", "sightseeing", "store"].includes(js_mode)) {
                        var js_graph_tmp = "";
                        console.log('js_mode = ' + js_mode);
                        for (var i = 0; i < ids.length; i++) {
                            var file_obj = "selfile_" + ids[i];
                            // console.log('file_obj = ' + file_obj);
                            var fieldElement = document.getElementById(file_obj);
                            var files = fieldElement.files;
                            var mykey = ids[i];
                            if (i == 0) js_graph_tmp += "[";
                            if (files == undefined || files.length == 0) {
                                js_graph_tmp += (js_graph_tmp.length > 1) ? ',' : '';
                                js_graph_tmp += '{"field":"'+ mykey + '", "filename":"", "base64":""}';
                            } else {
                                var baseimg = getImageBase64(ids[i]);
                                js_graph_tmp += (js_graph_tmp.length > 1) ? ',' : '';
                                js_graph_tmp += '{"field":"'+ mykey + '", "filename":"' + files[0].name + '", "base64":"' + baseimg + '"}';
                            }
                        }
                        js_graph_tmp += "]";
                        js_graph = js_graph_tmp;
                        console.log('js_graph = ' + js_graph);
                    } else {
                        js_graph = getImageBase64();
                    }
                } catch {}
                // console.log("js_graph :" + js_graph);
                
                var js_fldidx_name    = <?php echo $g_fldidx_name ?>;
                var js_fldidx_show    = <?php echo $g_fldidx_show ?>;
                var js_fields           = JSON.parse('<?php echo json_encode($column_info) ?>');
                var js_table            = '<?php echo $table ?>';
                var js_caption          = '<?php echo $caption ?>';
                var js_memberid         = '<?php echo $member_id ?>';
                var js_without_columns  = '<?php echo $without_columns ?>';
                var js_imagepath        = '<?php echo $image_path ?>';
                var js_api   = "";
                
                console.log("create param");
                var formdata = new FormData();
                formdata.append("table"          , js_table          );
                formdata.append("caption"        , js_caption        );
                formdata.append("member_id"      , js_memberid       );
                formdata.append("without_columns", js_without_columns);
                formdata.append("image_path"     , js_imagepath      );
                formdata.append("mode"           , js_mode           );
                // console.log("table :" + js_table);           console.log("caption :" + js_caption);
                // console.log("member_id :" + js_memberid);    console.log("without_columns :" + js_without_columns);
                // console.log("image_path :" + js_imagepath);  console.log("mode :" + js_mode);
                if (["member", "news", "banner", "sightseeing", "store"].includes(js_mode)) {
                    console.log("add product info to parameter");
                    formdata.append("image_info_base64", js_graph);
                } else {
                    formdata.append("file_content", js_graph);
                }
                // console.log(js_imagepath); console.log(js_mode);
                for (var i = 0; i < js_fields.length; i++) {
                    var js_field = js_fields[i];
                    if (js_field[js_fldidx_show] == 'true') { // show == true
                        var obj_id = "";
                        var js_fieldname = js_field[js_fldidx_name]; // field_name
                        if (js_fieldname == 'file_path')
                            obj_id = 'selfile';
                        else if (js_fieldname == 'file_content')
                            obj_id = 'file_content';
                        else
                            obj_id = js_fieldname;
                        if (obj_id != '') {
                            var val = "";
                            var fieldElement = document.getElementById(obj_id);
                            if (obj_id == 'selfile') {
                                var files = fieldElement.files;
                                if (files == undefined || files.length == 0)
                                    formdata.append(js_fieldname, '');
                                else
                                    formdata.append(js_fieldname, files[0].name)
                                // console.log(js_imagepath + files[0].name);
                            } else if (obj_id == 'file_content') {
                                formdata.append(obj_id, js_graph);
                                // console.log(js_graph);
                            } else if (obj_id.includes("_img")) {
                            } else if (obj_id.includes("selfile_")) {
                            } else {
                                val = fieldElement.value;
                                while (val.includes("\n")) {
                                    val = val.replace("\n", ";;");
                                }
                                formdata.append(js_fieldname, val);
                                // console.log(js_fieldname + "," + val);
                            }
                        }
                    }
                }
                console.log("call postAPI");
                postAPI('./../../api/b_c_data.php', formdata);
            }
            function processFormData4Pdf() {
                console.log("call processFormData4Pdf()");
                var js_mode = '<?php echo $mode ?>';
                var js_graph = "";
                var ids = ["pdf_url"];

                try {
                    if (["apply"].includes(js_mode)) {
                        var js_graph_tmp = "";
                        console.log('js_mode = ' + js_mode);
                        for (var i = 0; i < ids.length; i++) {
                            js_graph_tmp = getPDFBase64(ids[i]);
                        }
                        js_graph = js_graph_tmp;
                    } else {
                        js_graph = getPDFBase64();
                    }
                } catch {}
                // console.log("js_graph :" + js_graph);
                
                var js_fldidx_name      = <?php echo $g_fldidx_name ?>;
                var js_fldidx_show      = <?php echo $g_fldidx_show ?>;
                var js_fields           = JSON.parse('<?php echo json_encode($column_info) ?>');
                var js_table            = '<?php echo $table ?>';
                var js_caption          = '<?php echo $caption ?>';
                var js_memberid         = '<?php echo $member_id ?>';
                var js_without_columns  = '<?php echo $without_columns ?>';
                var js_imagepath        = '<?php echo $image_path ?>';
                var js_api              = "";
                
                console.log("create param");
                var formdata = new FormData();
                formdata.append("table"          , js_table          );
                formdata.append("caption"        , js_caption        );
                formdata.append("member_id"      , js_memberid       );
                formdata.append("without_columns", js_without_columns);
                formdata.append("image_path"     , js_imagepath      );
                formdata.append("mode"           , js_mode           );
                console.log("table :" + js_table);           console.log("caption :" + js_caption);
                console.log("member_id :" + js_memberid);    console.log("without_columns :" + js_without_columns);
                console.log("image_path :" + js_imagepath);  console.log("mode :" + js_mode);
                console.log(js_imagepath); console.log(js_mode);
                if (["apply"].includes(js_mode)) {
                    console.log("add apply info to parameter");
                    formdata.append("image_info_base64", js_graph);
                    console.log("image_info_base64 :" + js_graph);
                } else {
                    formdata.append("file_content", js_graph);
                    console.log("file_content :" + js_graph);
                }
                for (var i = 0; i < js_fields.length; i++) {
                    var js_field = js_fields[i];
                    if (js_field[js_fldidx_show] == 'true') { // show == true
                        var obj_id = "";
                        var js_fieldname = js_field[js_fldidx_name]; // field_name
                        if (js_fieldname == 'file_path')
                            obj_id = 'selfile';
                        else if (js_fieldname == 'file_content')
                            obj_id = 'file_content';
                        else
                            obj_id = js_fieldname;
                        if (obj_id != '') {
                            var val = "";
                            var fieldElement = document.getElementById(obj_id);
                            if (fieldElement) {
                                // console.log("元素 " + obj_id + " :" + fieldElement.value);
                            } else {
                                // console.log("找不到元素 :" + obj_id);
                                continue;
                            }
                            if (obj_id == 'selfile') {
                                var files = fieldElement.files;
                                if (files == undefined || files.length == 0)
                                    formdata.append(js_fieldname, '');
                                else
                                    formdata.append(js_fieldname, files[0].name)
                                // console.log(js_imagepath + files[0].name);
                            } else if (obj_id == 'file_content') {
                                formdata.append(obj_id, js_graph);
                                // console.log(js_graph);
                            } else if (obj_id.includes("_img")) {
                            } else if (obj_id.includes("selfile_")) {
                            } else if (obj_id.includes("previewPDF_")) {
                            } else {
                                val = fieldElement.value;
                                while (val.includes("\n")) {
                                    val = val.replace("\n", ";;");
                                }
                                formdata.append(js_fieldname, val);
                                console.log(js_fieldname + "," + val);
                            }
                        }
                    }
                }
                console.log("call postAPI");
                postAPI('./../../api/b_c_data.php', formdata);
            }
        </SCRIPT>
    </head>
    <body id="page-top">
        <div class="myform">
            <div class="myform-head">
                <div class="myform-head-content">
                    <div class="dlgtitle"><?php echo $caption; ?></div>
                </div>
            </div>
            <form method="post" enctype="multipart/form-data" id="myform">
                <div class="dlgcontainer">
                    <?php
                        $dft_country = $g_dft_country_code;
                        for ($i = 0; $i < count($column_info); $i++) {
                            $com = $column_info[$i];

                            $field      = $com[$g_fldidx_name];
                            $name       = $com[$g_fldidx_comment];
                            $preholder  = $com[$g_fldidx_preholder];
                            $length     = empty($com[$g_fldidx_length]) ? "-1" : $com[$g_fldidx_length];
                            if (intval($length) < 0) $length = "";
                            $show       = ($com[$g_fldidx_show]         == "true");
                            $hidden     = ($com[$g_fldidx_showbuthide]  == "true");
                            $search     = ($com[$g_fldidx_srch]         == "true");
                            $lockedit   = ($com[$g_fldidx_lockedit]     == "true");
                            
                            $editable_str = ($lockedit) ? "disabled" : "";
                            $author_event = ($field == 'authorization_page') ? 'onclick="showAuthorDlg()" readonly' : "";
                            $name = str_replace('序號', '', $name);
                            if ($i > 0) echo '<div class="vr"></div>';
                            if ($show) {
                                if (stripos($useobjselect_fields, $field) != false) {
                                    $dft_value = ""; $onclickfunc = "";
                                    if ($field == 'role') {
                                        $array_select = $role_avalible;
                                        $onclickfunc = "changeRole('$mode', '$field', '$all_fields', '$mem_show', '$mem_need', '$mem_auth', '$mem_olimit', '$mem_priority')";
                                    } else if ($field == 'gender')
                                        $array_select = $gender_avalible;
                                    else if ($field == 'country_code') {
                                        $array_select = $g_country_code; $dft_value = $g_dft_country_code;
                                    } else if ($field == 'avalible')
                                        $array_select = $msg_avalible;
                                    else if ($field == 'sales_specify') {
                                        $array_select = $sales_array;
                                    } else if ($field == 'is_partner') {
                                        $array_select = $g_is_partner;
                                    } else if ($field == 'new_kind') {
                                        $array_select = $g_news_kind;
                                    } else if ($field == 'agency_sid') {
                                        $array_select = $agency_array;
                                    } else if ($field == 'mainitem_sid') {
                                        $array_select = $applyitem_array;
                                    // } else if ($field == 'processing_method') {
                                    //     $array_select = $g_processing_method;
                                    //     $onclickfunc = "changeApplytype('$mode', '$field', 'processing_method_url')";
                                    }
                                    echo '  <div class="form-group">
                                                <label id="lbl_'.$field.'" for="'.$field.'"><span id="need_'.$field.'" style="color:red"></span>'.$name.'</label>
                                                <select id="'.$field.'" class="custom-select" onchange="'.$onclickfunc.'" '.$editable_str.'>';
                                                if ($field == 'sales_specify') {
                                                    echo '<option value="" selected>請選擇服務的業務員</option>';
                                                    
                                                    for ($isales = 0; $isales < count($sales_array); $isales++) {
                                                        $cur_sales = $sales_array[$isales];
                                                        echo "<option value=\"$cur_sales->sid\">$cur_sales->name</option>";
                                                    }
                                                } else {
                                                    $j = 0;
                                                    foreach ($array_select as $key => $value) {
                                                        if (empty($dft_value))
                                                            $select_option = ($j == 0) ? "selected" : "";
                                                        else
                                                            $select_option = ($key == $dft_value) ? "selected" : "";
                                                        echo '<option value="'.$value.'" '.$select_option.'>'.$key.'</option>';
                                                        $j++;
                                                    }
                                                }
                                    echo '      </select>
                                            </div>
                                         ';
                                } else if ($field == 'file_path') {
                                    echo '<div class="form-group">
                                              <label id="lbl_'.$field.'" for="selfile"><span id="need_'.$field.'" style="color:red"></span>'.$name.'</label>
                                              <input id="selfile" type="file" name="'.$field.'" class="form-control-file"
                                                     onchange="updateImage(this)"
                                                     accept="image/jpeg, image/bmp, image/gif, image/png, image/jpg" />
                                              <div>
                                                  <div id="drop_block" ondragover="javaSCRIPT: drag_handler(event);" ondrop="javaSCRIPT: drop_graph(event);" class="upload-block">
                                                      請將檔案拖曳到此...
                                                  </div>
                                              </div>
                                              <img id="previewImage" src=""/>
                                          </div> <!-- form-group -->
                                          <div class="progress_area">
                                              <div class="dlgprogress">
                                                  <div id="upload_progress" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
                                                  </div>
                                              </div> <!-- dlgprogress -->
                                              <br><br>
                                          </div>';
                                } else if (strEndWith($field, '_img')) {
                                    echo '<div class="form-group">
                                              <label id="lbl_'.$field.'" for="selfile_'.$field.'"><span id="need_'.$field.'" style="color:red"></span>'.$name.'</label>
                                              <input id="selfile_'.$field.'" type="file" name="'.$field.'" class="form-control-file"
                                                     onchange="updateImage(this.files, \''.$field.'\')"
                                                     accept="image/jpeg, image/bmp, image/gif, image/png, image/jpg" />
                                              <div>
                                                  <div id="drop_block_'.$field.'" ondragover="javaSCRIPT: drag_handler(event);" ondrop="javaSCRIPT: drop_graph(event);" class="upload-block">
                                                      請將檔案拖曳到此...
                                                  </div>
                                              </div>
                                              <img id="previewImage_'.$field.'" src="" />
                                          </div> <!-- form-group -->
                                          <div class="progress_area">
                                              <div class="dlgprogress">
                                                  <div id="upload_progress_'.$field.'" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
                                                  </div>
                                              </div> <!-- dlgprogress -->
                                              <br><br>
                                          </div>';
                                } else if (strStartWith($field, 'pdf_')) {
                                    echo '<div class="form-group">
                                            <label id="lbl_'.$field.'" for="selfile_'.$field.'">
                                                <span id="need_'.$field.'" style="color:red"></span>'.$name.'
                                            </label>
                                            <input id="selfile_'.$field.'" type="file" name="'.$field.'" class="form-control-file"
                                                    onchange="updatePDF(this.files, \''.$field.'\')"
                                                    accept="application/pdf" />
                                            <div>
                                                <div id="drop_block_'.$field.'" ondragover="javaSCRIPT: drag_handler(event);" 
                                                    ondrop="javaSCRIPT: drop_pdf(event);" class="upload-block">
                                                    請將 PDF 檔案拖曳到此...
                                                </div>
                                            </div>
                                            <textarea id="previewPDF_'.$field.'" style="width:400px; height:200px;" hidden></textarea>
                                        </div>
                                        <div class="progress_area">
                                            <div class="dlgprogress">
                                                <div id="upload_progress_'.$field.'" class="progress-bar progress-bar-striped active" 
                                                    role="progressbar" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <br><br>
                                        </div>';
                                } else if ($field == 'file_content') {
                                    echo '<div class="form-group">
                                            <label for="previewImage">'.$name.'</label>
                                            <br>
                                            <img id="previewImage" src="" />
                                          </div>';
                                } else if ($field == 'summary' || $field == 'content' || strEndWith($field, '_spec') ||
                                           $field == 'conditions' || $field == 'required_documents' || $field == 'processing_method' || $field == 'subsidy_amount') {
                                    echo '<div class="form-group">
                                            <label id="lbl_'.$field.'" for="'.$field.'"><span id="need_'.$field.'" style="color:red"></span>'.$name.'</label>
                                            <br>
                                            <textarea id="'.$field.'" name="'.$field.'" rows="4" cols="50" class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default" '.$editable_str.'></textarea>
                                            </div>';
                                } else if ($field == 'parent_sid') {
                                    $array_select = $kind_parent;
                                    echo '  <div class="form-group">
                                                <label id="lbl_'.$field.'" for="'.$field.'"><span id="need_'.$field.'" style="color:red"></span>'.$name.'</label>
                                                <select id="'.$field.'" class="custom-select" onchange="chSelect4ParentSid()" '.$editable_str.'>';
                                                $j = 0;
                                                foreach ($array_select as $key => $value) {
                                                    if (empty($parent_sel)) {
                                                        $select_option = ($j == 0) ? 'selected' : '';
                                                    } else {
                                                        $select_option = ($parent_sel == $value) ? 'selected' : '';
                                                    }
                                                    echo '<option value="'.$value.'" '.$select_option.'>'.$key.'</option>';
                                                    $j++;
                                                }
                                    echo '      </select>
                                            </div>
                                         ';
                                } else {
                                    $hidden4processing_method_url = "";
                                    echo '<label id="lbl_'.$field.'" for="'.$field.'"><span id="need_'.$field.'" style="color:red"></span>'.$name.'</label>';
                                    $input_type = (stripos($field, '_date') > -1) ? "datetime-local" : "text";
                                    if (stripos($field, 'birthday') > -1) $input_type = "date";
                                    if (stripos($field, 'pwd') > -1) $input_type = "password";
                                    echo '<input type="'.$input_type.'" name="'.$field.'" id="'.$field.'" '.$editable_str.' '.$author_event;
                                    echo ' placeholder="'.$preholder.'" maxlength="'.$length.'"';
                                    echo ' class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default" /><br>';
                                }
                            }   
                        }
                    ?>
                    <div class="message_area">
                        <div class="resultmsg"></div>
                        <br><br>
                    </div>
                </div>
                <div class="myform-tail">
                    <div class="myform-tail-content">
                        <input id="bt_preview" type="button" name="bt_preview" value="預覽" class="btn btn-primary" onclick="openPreview('<?php echo $mode ?>')" <?php echo $display_preview; ?> />
                        <?php if ($display_preview == '') echo getHtmlSpaceChar(3); ?>
                        <input id="submit" type="button" name="submit" value="儲存" class="btn btn-success" onclick="<?=$processFormDataSel?>()" />
                        <?php echo getHtmlSpaceChar(3); ?>
                        <a class="btn btn-outline-secondary" href="" data-toggle="modal" data-target="#pageoutModal">返回</a>
                    </div>
                </div>
            </form>
        </div>
        <br>
        <div id="viewer" class="preview_viewer">
            <div class="myform">
                <div class="myform-head">
                    <div class="myform-head-content">
                        <div class="dlgtitle">預覽
                            <?php echo getHtmlSpaceChar(3); ?>
                            <input id="bt_close" type="button" name="bt_preview" value="關閉" class="btn btn-primary" onclick="closePreview()" />
                        </div>
                    </div>
                </div>
                <div id="preview_content" class="dlgcontainer">
                </div>
                <div class="myform-tail">
                </div>
            </div>
        </div>
        <?php include("./../../ui/dlg_pageout.php"); ?> <!-- logout dialog -->
        <?php include("./../../ui/dlg_author.php"); ?>
        <SCRIPT src="./../../js/jackymemrole.js"></SCRIPT>
        <!-- <SCRIPT src="./../../js/jackyapplytype.js"></SCRIPT> -->
        <SCRIPT>
            $(document).ready(function() {
                $('.message_area').css('display', 'none');
            });
            var js_mode = '<?php echo $mode; ?>';
            if (js_mode == "member") {
                var js_field = '<?php echo $field; ?>';
                var js_all_fields = '<?php echo $all_fields; ?>';
                var js_mem_show = '<?php echo $mem_show; ?>';
                var js_mem_need = '<?php echo $mem_need; ?>';
                var js_mem_auth = '<?php echo $mem_auth; ?>';
                var js_mem_olimit = '<?php echo $mem_olimit; ?>';
                var js_mem_priority = '<?php echo $mem_priority; ?>';
                changeRole(js_mode, "role", js_all_fields, js_mem_show, js_mem_need, js_mem_auth, js_mem_olimit, js_mem_priority);
            }
            // if (js_mode == "apply") {
            //     var js_field = '<?php echo $field; ?>';
            //     var js_all_fields = '<?php echo $all_fields; ?>';
            //     var js_mem_show = '<?php echo $mem_show; ?>';
            //     var js_mem_need = '<?php echo $mem_need; ?>';
            //     var js_mem_auth = '<?php echo $mem_auth; ?>';
            //     var js_mem_olimit = '<?php echo $mem_olimit; ?>';
            //     var js_mem_priority = '<?php echo $mem_priority; ?>';
            //     changeApplytype(js_mode, "processing_method", "processing_method_url");
            // }
        </SCRIPT>
    </body>
</html>