<?php
    $g_ProjectIcon      = $g_root_url."images/favicon.ico";
    $g_ProjectTitle     = "";
    $g_ProjectType      = "南投通";
    $g_ProjectModule    = "後台管理";
    $g_PageTitle        = $g_ProjectTitle.$g_ProjectType."[".$g_ProjectModule."]";
    $g_ProjectName      = $g_ProjectTitle."<br>".$g_ProjectType.$g_ProjectModule;
    $g_Copyright        = "Copyright &copy; 2025 Jotangi Technology Co., Ltd";


    // 欄位屬性定義
    $g_fldidx_name        = 0;
    $g_fldidx_comment     = 1;
    $g_fldidx_preholder   = 2;
    $g_fldidx_length      = 3;
    $g_fldidx_show        = 4;
    $g_fldidx_showbuthide = 5;
    $g_fldidx_lockedit    = 6;
    $g_fldidx_srch        = 7;
    
    $g_grid_export = false;
    $g_verifyimg_options=['a' => 'a_CNS.png',
                          'b' => 'b_BSMI.png',
                          'c' => 'c_節能標章.png',
                          'd' => 'd_環保標章.png',
                          'e' => 'e_無藍光.png',
                          'f' => 'f_無頻閃.png',
                          'g' => 'g_IP65.png',
                          'h' => 'h_IP66.png',
                          'i' => 'i_IP67.png',
                          'j' => 'j_IP68.png',
                          'k' => 'k_IK08.png',
                          'l' => 'l_風洞17級.png',
                          'm' => 'm_鹽霧Level10.png'];
    $g_pdct_options             = ['[1]限時特賣' => '1', '[2]新品上架' => '2', '[3]新品預告' => '3', '[Y]正常' => 'Y', '[D]刪除' => 'D', '[W]作廢' => 'W'];
    $g_YN_options               = ['[Y]加入' => 'Y', '[N]移除' => 'N']; // JTG_modifyshopping使用
    $g_order_options            = ['[Y]訂單成立' => 'Y', '[N]訂單取消' => 'N', '[B]部分退貨申請中' => 'B', '[F]訂單已完成' => 'F']; // JTG_modifyshopping使用
    $g_page_options             = [10, 20, 30, 40, 50, 100, 200];
    $g_gender_options           = ['男' => '男', '女' => '女'];
    $g_base_avalible            = ['[Y]正常' => 'Y', '[D]刪除' => 'D', '[W]作廢' => 'W'];
    $g_base_avalible_zhtw       = ['Y' => '[Y]正常', 'D' => '[D]刪除', 'W' => '[W]作廢'];
    $g_member_avalible_code     = ['[R]新註冊會員' => 'R', '[Y]審核通過' => 'Y', '[NE]未審核' => 'NE', '[D]註銷' => 'D', '[W]作廢' => 'W'];
    $g_member_avalible_zhtw     = ['R' => '[R]新註冊會員', 'Y' => '[Y]審核通過', 'NE' => '[NE]未審核', 'D' => '[D]註銷', 'W' => '[W]作廢'];
    $g_role_options             = ['[Stf]員工' => 'Stf', '[Stc]店家會員' => 'Stc', '[Smn]業務'=>'Smn', '[Ctl]管理員' => 'Ctl'];
    $g_processing_method        = ['臨櫃辦理' => '1', '線上申請' => '2'];
    $g_processing_method_zhtw   = ['1' => '臨櫃辦理', '2' => '線上申請'];

    // 申辦案件狀態
    $g_applicant_state_v    = ['送件' => 'S', '退件' => 'B', '符合' => 'V', '不符合' => 'E'];
	$g_applicant_state      = [
                                "S" => ["text"=>"送件", "color"=>"green"],
                                "B" => ["text"=>"退件", "color"=>"grey"],
                                "V" => ["text"=>"符合", "color"=>"green"],
                                "E" => ["text"=>"不符合", "color"=>"red"]
                            ];

    // 是否為合作商家
    $g_is_partner_zhtw = ['0' => "否",
                          '1' => "是"];
    // 是否為合作商家
    $g_is_partner = ['否' => "0",
                     '是' => "1"];

    $g_news_kind_zhtw = ['0' => "一般訊息",
                    '1' => "商家活動消息"];
    $g_news_kind = ['一般訊息' => "0",
                    '商家活動消息' => "1"];
    //-----------------------------------------------------------------------------------------------------------------------------------------------------------
    // 選擇清單欄位
    $g_fields_cbobj         = 'null,role,sales_specify,avalible,is_partner,new_kind,agency_sid,mainitem_sid'; // member
	$g_fields_memshow       = ['Stf' => "role,authorization_page,mid,pwd,name,eng_name,cmp_code,address,mobile,tel,fax,email,order_limit,avalible,sales_specify,start_date,head_img,priority,script,remark",
                               'Stc' => "role,authorization_page,mid,pwd,name,eng_name,cmp_code,address,mobile,tel,fax,email,order_limit,avalible,sales_specify,start_date,head_img,priority,script,remark",
                               'Smn' => "role,authorization_page,mid,pwd,name,eng_name,staff_sid,mobile,tel,fax,email,avalible,start_date,head_img,priority,script,remark",
                               'Ctl' => "role,authorization_page,mid,pwd,name,eng_name,staff_sid,mobile,tel,fax,email,avalible,start_date,head_img,priority,script,remark"];
    // 必填欄位
	$g_fields_memneed       = ['Stf' => "mid,role,name,pwd,avalible",
                               'Stc' => "mid,role,name,pwd,avalible",
                               'Smn' => "mid,role,authorization_page,name,staff_sid,pwd,mobile,avalible",
                               'Ctl' => "mid,role,authorization_page,name,pwd,avalible"];
	$g_fields_pdctdetlneed  = "model_name,price,avalible";
    $g_fields_conferenceroomneed = "name,msg_title,avalible";
    $g_fields_msgcenterneed = "name,title,avalible";

    // 預設授權可視頁面
    $g_dft_author_page = ['Stf' => "0!!0!!0!!0!!0!!0!!0",
                          'Stc' => "0!!0!!0!!0!!0!!0!!0",
                          'Smn' => "241!!241!!0!!0!!0!!0!!241",
                          'Ctl' => "4095!!4095!!4095!!4095!!4095!!4095!!4095"];
    // 預設下單限額
    $g_dft_order_limit = ['Stf' => "",
                          'Stc' => "100000",
                          'Smn' => "",
                          'Ctl' => ""];
    // 預設權限
    $g_dft_priority = ['Stf' => "7",
                       'Stc' => "8",
                       'Smn' => "7",
                       'Ctl' => "9"];
    //-----------------------------------------------------------------------------------------------------------------------------------------------------------

    // $g_sidemenu_idx         = ["home" => 0,    "member" => 1,  "product" => 2,       "order" => 3, "repository" => 4, "transfer" => 5, "discount" => 6, "service_center" => 7, "message_center" => 8];
    $g_sidemenu_idx         = ["home" => 0,    "member" => 1,  "agency_center" => 2,  "message_center" => 3, "apply_service" => 4];
    $g_sidemenu = [
        "root"   	        => [        "首頁",         "會員中心",      "機構管理",              "訊息中心",              "申辦服務"],
        "root_href"         => ["./index.php",                "#",            "#",                     "#",                    "#"],
        "root_id"           => [         "hh",                "a",            "b",                     "i",                    "c"],
        "root_icon"         => [    "fa-home",  "fa-address-card", "fa-cart-plus",        "fa-folder-open",      "fa-address-card"],
        // "root"   	        => [   "首頁",         "會員中心",       "商品維護",         "訂單管理",       "庫存管理",       "物流管理",         "優惠管理",     "客服中心",        "訊息中心"],
        // "root_href"         => [     "./",               "#",             "#",                "#",             "#",             "#",                "#",           "#",              "#"],
        // "root_id"           => [     "hh",               "a",             "c",                "d",             "e",             "f",                "g",           "h",              "i"],
        // "root_icon"         => ["fa-home", "fa-address-card",  "fa-cart-plus",  "fa-shopping-bag", "fa-chart-area",      "fa-truck", "fa-shopping-cart",     "fa-info", "fa-folder-open"],

        "首頁"              => [],
        
        "會員中心"          => [             "會員資料"],
        "會員中心_href"     => [      "memberlist.php"],
        "會員中心_id"       => [       "memberlistphp"],
        
        "機構管理"          => [             "機構資料",            "各科室單位"],
        "機構管理_href"     => [      "agencylist.php",   "agencyunitlist.php"],
        "機構管理_id"       => [       "agencylistphp",    "agencyunitlistphp"],
        

        "訊息中心"          => [            "最新消息",        "Banner管理",             "觀光景點",          "商家"],
        "訊息中心_href"     => [       "newslist.php",    "bannerlist.php", "sightseeinglist.php", "storelist.php"],
        "訊息中心_id"       => [        "newslistphp",     "bannerlistphp",  "sightseeinglistphp",  "storelistphp"],

        "申辦服務"          => [           "申辦項目別",        "申辦資料"],
        "申辦服務_href"     => [       "applyitem.php",  "applylist.php"],
        "申辦服務_id"       => [        "applyitemphp",   "applylistphp"],
      ];
    
    //-----------------------------------------------------------------------------------------------------------------------------------------------------------
    
    // 新增資料頁面參數
        // without_columns 去除與此參數欄位名稱相符的欄位
        $g_add_member_out_col    = "null,nid,sid,create_date,modify_date,create_date,advertising_id,device_id,cur_coupon,cur_point,edit_sid,recvaddr_sid";
        $g_add_msgcenter_out_col = "null,nid,sid,create_date,modify_date,fcm2member_sid,edit_sid,member_sid,sort,countycityunit_sid";
        
        // showbuthide_columns 隱藏與此參數欄位名稱相符的欄位，作為索引，使用於傳資料
        $g_add_member_sbh_col    = "";
        $g_add_msgcenter_sbh_col = "";
        
        // lockedit_columns 鎖住不允許編輯的欄位
        $g_add_member_lke_col    = "null,avalible";
        $g_add_msgcenter_lke_col = "";

        // search_columns 搜尋欄位，在grid畫面中顯示功能元件，供搜尋用
        $g_add_member_sch_col    = "";
        $g_add_msgcenter_sch_col = "";
    
    // 編輯資料頁面參數
        // without_columns 去除與此參數欄位名稱相符的欄位
        $g_edt_member_out_col    = "null,nid,sid,create_date,modify_date,create_date,advertising_id,device_id,cur_coupon,cur_point,edit_sid,recvaddr_sid";;
        $g_edt_msgcenter_out_col = "null,nid,create_date,modify_date,fcm2member_sid,member_sid,edit_sid,sid,sort,countycityunit_sid";
        
        // showbuthide_columns 隱藏與此參數欄位名稱相符的欄位，作為索引，使用於傳資料
        $g_edt_member_sbh_col    = "";
        $g_edt_msgcenter_sbh_col = "";
        
        // lockedit_columns 鎖住不允許編輯的欄位
        $g_edt_member_lke_col    = "role,mid,create_date,staff_sid,name,identity,advertising_id,device_id";
        $g_edt_msgcenter_lke_col = "";

        // search_columns 搜尋欄位，在grid畫面中顯示功能元件，供搜尋用
        $g_edt_member_sch_col    = "";
        $g_edt_msgcenter_sch_col = "";

    // 顯示資料頁面參數
        // without_columns 去除與此參數欄位名稱相符的欄位
        $g_vew_member_out_col    = "null,sid,company_sid,create_date,modify_date,start_date,cur_coupon,cur_point,script,remark,advertising_id,device_id,pwd,recvaddr_sid";
        $g_vew_msgcenter_out_col = "null,sid,sort,fcm2member_sid,script,remark";
        
        // showbuthide_columns 隱藏與此參數欄位名稱相符的欄位，作為索引，使用於傳資料
        $g_vew_member_sbh_col    = "null,authorization_page";
        $g_vew_msgcenter_sbh_col = "";
        
        // lockedit_columns 鎖住不允許編輯的欄位
        $g_vew_member_lke_col    = "null,eng_name,gender,priority,avalible";
        $g_vew_msgcenter_lke_col = "";

        // search_columns 搜尋欄位，在grid畫面中顯示功能元件，供搜尋用
        $g_vew_member_sch_col    = "mid,staff_sid,name,eng_name,cmp_code,address,mobile,email,role,priority,sales_specify,avalible";
        $g_vew_msgcenter_sch_col = "create_date,modify_date,member_sid,title,summary,content,start_date,end_date,avalible,countycityunit_sid";
    //-----------------------------------------------------------------------------------------------------------------------------------------------------------

    $g_grid_col_width = ['nid' => '70px', 'level' => '70px', 'create_date' => '90px', 'modify_date' => '90px', 'member_sid' => '100px', 'Actions' => '100px' ];

    function clearSession($enable_session=false, $to_loginpage=false)
    {
        if ($enable_session) session_start();
        assignSession();
        if ($enable_session) session_destroy();
        if ($to_loginpage) header("Location: login.php");
    }
    function assignSession($sso_token="", $membersid="", $membername="", $user_role="", $userid="", $priority="", $authority="", $head_img="", $downloadlog="")
    {
        $_SESSION['sso_token'	]=$sso_token;
        $_SESSION['loginsid'	]=$membersid;
        $_SESSION['userid'	    ]=$userid;
        $_SESSION['accname' 	]=$membername;
        $_SESSION['user_role' 	]=$user_role;
        $_SESSION['priority'    ]=$priority;
        $_SESSION['authority'   ]=$authority;
        $_SESSION['head_img'    ]=$head_img;
        $_SESSION['downloadlog'	]=$downloadlog;
    }
?>
