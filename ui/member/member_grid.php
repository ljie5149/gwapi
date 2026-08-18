<?php
    include("./../../common/entry.php");
    include(PHPGRID_LIBPATH."inc/jqgrid_dist.php");
    global $g_db_ip, $g_db_user, $g_db_pwd, $g_db_name, $g_grid_export;
    global $g_member_avalible_code;

    uiLocationPage();

	$member_id = isset($_SESSION['userid']) ? $_SESSION['userid']: '';
	$authority = isset($_SESSION['authority']) ? $_SESSION['authority']: '';
	$mode = isset($_GET['mode']) ? $_GET['mode']: '';
    
	$menu_idx = $g_sidemenu_idx['member'];
    if ($mode == "member") { // 一般會員
        $submenu_idx = 0;
        $table = $g_db_table['datamember'];
        $without_columns="null,sid,identity,start_date,cur_coupon,cur_point,script,remark,tel,advertising_id,device_id,pwd,isforeign,blood_type,authorization_page";
        $lockedit_columns="eng_name,gender,priority,avalible";
    } else {                 // 企業會員
        $submenu_idx = 1;
        $table = $g_db_table['datacmymember'];
        $without_columns="null,sid,identity,advertising_id,device_id";
        $lockedit_columns="script,remark,priority,avalible";
    }
    $caption = getSubMenuString($menu_idx, $submenu_idx);

	$data = array();
	$remote_ip = get_remote_ip();
    $db= new CXDB($remote_ip);
    try {
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $column_info = $db->getTableColumnComments($link, $table, $without_columns, "", $lockedit_columns);
        }
    } catch (Exception $e) {
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
    // var_dump($column_info);

    // Database config file to be passed in phpgrid constructor
    $db_conf = array(
                        "type" 		=> PHPGRID_DBTYPE,
                        "server" 	=> $g_db_ip,
                        "user" 		=> $g_db_user,
                        "password" 	=> $g_db_pwd,
                        "database" 	=> $g_db_name
                    );

    $g = new jqgrid($db_conf);

    // 設定 grid options 屬性
    $opt["caption"]     = $caption;
    $opt["autowidth"]   = true;
    $opt["altRows"]     = true; 
    $opt["multiselect"] = false; 
    $opt["scroll"]      = true;
    
    // first column is not autoincrement 
    $opt["autoid"]      = false;// Define predefined search templates

    if ($g_grid_export) {
        $opt["export"]["range"    ] = "filtered";
        $opt["export"]["colwidth" ] = "equal";
        $opt["export"]              = array("filename"=>"my-file", "heading"=>"Invoice Details", "orientation"=>"landscape", "paper"=>"a4"); // export PDF file params
        $opt["export"]["sheetname"] = $caption; // for excel, sheet header
        $opt["export"]["range"    ] = "filtered"; // export filtered data or all data
    }
    
    // filter 樣版
    // $opt["search_options"]["tmplNames"] = array("Template1", "Template2");
    // $opt["search_options"]["tmplFilters"] = array(
    //     array(
    //         "groupOp" => "AND",
    //         "rules" => array (
    //                         array("field"=>"sid", "op"=>"cn", "data"=>"Maria"),
    //                         array("field"=>"closed", "op"=>"cn", "data"=>"No"),
    //                         )
    //     ),
    //     array(
    //         "groupOp" => "AND",
    //         "rules" => array (
    //                         array("field"=>"total", "op"=>"gt", "data"=>"50")
    //                         )
    //     )
    // );
    $g->set_options($opt);

    // you can provide custom SQL query to display data
    // if ($mode == "member") $g->select_command = "SELECT * FROM $table WHERE sid='admin'";
    $g->table = $table;

    $cols = array();
    $col = array();
    
    for ($i = 0; $i < count($column_info); $i++) {
        $item = $column_info[$i];
        $col["hidden"   ] = ($item[0] == "true");
        $col["name"     ] = $item[1];
        $col["title"    ] = $item[2];
        $col["editable" ] = ($item[3] == "true");
        
        if ($g_grid_export) $col["export"] = ($item[0] == "false");  // when set false, this column will not be exported

        if ($item[1] == "avalible") {
            $col["edittype"] = "select";
            $col["editoptions"]["value"] = get_avalible_dropdown();
            $col["formatter"] = "function (cellvalue, options, rowObject) {
                return getAvalibleCode(cellvalue);
            }";
            $col["unformat"] = "function (cellvalue, options) {
                return cellvalue;
            }";
        }
        array_push($cols, $col);
    }
    // $col["name"] = "pwd";
    // $col["title"] = "密碼";
    
    // $col["edittype"] = "select";
    // $col["editoptions"]["value"] = get_country_dropdown();
    // $col["formatter"] = "function (cellvalue, options, rowObject) {
    //     return \"<img width='30' height='20' src='./../gridphp_object/img/country-flags/\"+get_countrycode(cellvalue)+\".png' /> \" + cellvalue;
    // }";
    // $col["unformat"] = "function (cellvalue, options) {
    //     return cellvalue;
    // }";

    // $cols[] = $col;

    $g->set_columns($cols, true);
    if ($authority && 2) {
        if ($g_grid_export) {
            $g->set_actions(array(
                "add"               => false, // allow/disallow add
                "edit"              => true, // allow/disallow edit
                "delete"            => false, // allow/disallow delete
                "showhidecolumns"   => false, // show/hide row wise edit/del/save option
                "rowactions"        => true, // show/hide row wise edit/del/save option
                "autofilter"        => true, // show/hide autofilter for search
                "search"            => "advance", // show/hide autofilter, single/multi field search condition (e.g. simple or advance)"export_pdf"=>true,
                "export_excel"      => true, // export excel button
                "export_pdf"        => true, // export pdf button
                "export_csv"        => true, // export csv button
                "export_html"       => true, // export html button
            ));
        } else {
            $g->set_actions(array(
                "add"               => false, // allow/disallow add
                "edit"              => true, // allow/disallow edit
                "delete"            => false, // allow/disallow delete
                "showhidecolumns"   => false, // show/hide row wise edit/del/save option
                "rowactions"        => true, // show/hide row wise edit/del/save option
                "autofilter"        => true, // show/hide autofilter for search
                "search"            => "advance", // show/hide autofilter, single/multi field search condition (e.g. simple or advance)"export_pdf"=>true,
            ));
        }
    } else {
        $g->set_actions(array(
            "add"               => false, // allow/disallow add
            "edit"              => false, // allow/disallow edit
            "delete"            => false, // allow/disallow delete
            "showhidecolumns"   => false, // show/hide row wise edit/del/save option
            "rowactions"        => false, // show/hide row wise edit/del/save option
            "autofilter"        => false, // show/hide autofilter for search
            "search"            => "advance", // show/hide autofilter, single/multi field search condition (e.g. simple or advance)
        ));
    }
    if ($g_grid_export) {
        $e = [];
        $e["on_render_pdf"  ] = array("filter_pdf", null, true);
        $e["on_render_excel"] = array("filter_xls", null, true);
        $e["on_data_display"] = array("filter_display", null, true);
        
        $g->set_events($e);
    }

    $out = $g->render("list1");

    function get_avalible_dropdown()
    {
        global $g_member_avalible_code;
        // Y:通過公司審核; NE: 未審核; D:刪除; W:作廢
        $code = $g_member_avalible_code;
        foreach ($code as $k => $v)
            $str[] = "$v:$k";

        return implode(";",$str);
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="../../gridphp_object/lib/js/themes/base/jquery-ui.custom.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="../../gridphp_object/lib/js/jqgrid/css/ui.jqgrid.css" rel="stylesheet" type="text/css" media="screen" />

        <script src="./../../gridphp_object/lib/js/jquery.min.js" type="text/javascript"></script>
        <script src="./../../gridphp_object/lib/js/jqgrid/js/i18n/grid.locale-tw.js" type="text/javascript"></script><!--載入jqGrid中文語系-->
        <script src="./../../gridphp_object/lib/js/jqgrid/js/jquery.jqGrid.min.js" type="text/javascript"></script>
        <script src="./../../gridphp_object/lib/js/themes/jquery-ui.custom.min.js" type="text/javascript"></script>
        
        <link href="./../../vendor/select2/v4.0.3/css/select2.min.css" rel="stylesheet" />
        <script src="./../../vendor/select2/v4.0.3/js/select2.min.js"></script>	

        <!-- library for checkbox in column chooser -->
        <link href="./../../vendor/select2/v1.2.1/css/multiple-select.css" rel="stylesheet" />
        <script src="./../../vendor/select2/v1.2.1/js/multiple-select.js"></script>
    </head>
    <body>
        <div>
            <?php echo $out?>
        </div>
        
        <style>
        .ui-priority-secondary
        {
            background-color: #f5f5f5;
            opacity: 1 !important;
        }
        </style>
        
        <script>
            function get_countrycode(str)
            {
                var code = {};
                code["Afghanistan"] = "AF"; code["Aland Islands"] = "AX"; code["Albania"] = "AL"; code["Algeria"] = "DZ"; code["American Samoa"] = "AS"; code["Andorra"] = "AD"; code["Angola"] = "AO"; code["Anguilla"] = "AI"; code["Antarctica"] = "AQ"; code["Antigua And Barbuda"] = "AG"; code["Argentina"] = "AR"; code["Armenia"] = "AM"; code["Aruba"] = "AW"; code["Australia"] = "AU"; code["Austria"] = "AT"; code["Azerbaijan"] = "AZ"; code["Bahamas"] = "BS"; code["Bahrain"] = "BH"; code["Bangladesh"] = "BD"; code["Barbados"] = "BB"; code["Belarus"] = "BY"; code["Belgium"] = "BE"; code["Belize"] = "BZ"; code["Benin"] = "BJ"; code["Bermuda"] = "BM"; code["Bhutan"] = "BT"; code["Bolivia"] = "BO"; code["Bosnia And Herzegovina"] = "BA"; code["Botswana"] = "BW"; code["Bouvet Island"] = "BV"; code["Brazil"] = "BR"; code["British Indian Ocean Territory"] = "IO"; code["Brunei Darussalam"] = "BN"; code["Bulgaria"] = "BG"; code["Burkina Faso"] = "BF"; code["Burundi"] = "BI"; code["Cambodia"] = "KH"; code["Cameroon"] = "CM"; code["Canada"] = "CA"; code["Cape Verde"] = "CV"; code["Cayman Islands"] = "KY"; code["Central African Republic"] = "CF"; code["Chad"] = "TD"; code["Chile"] = "CL"; code["China"] = "CN"; code["Christmas Island"] = "CX"; code["Cocos (Keeling) Islands"] = "CC"; code["Colombia"] = "CO"; code["Comoros"] = "KM"; code["Congo"] = "CG"; code["Congo, Democratic Republic"] = "CD"; code["Cook Islands"] = "CK"; code["Costa Rica"] = "CR"; code["Cote D'Ivoire"] = "CI"; code["Croatia"] = "HR"; code["Cuba"] = "CU"; code["Cyprus"] = "CY"; code["Czech Republic"] = "CZ"; code["Denmark"] = "DK"; code["Djibouti"] = "DJ"; code["Dominica"] = "DM"; code["Dominican Republic"] = "DO"; code["Ecuador"] = "EC"; code["Egypt"] = "EG"; code["El Salvador"] = "SV"; code["Equatorial Guinea"] = "GQ"; code["Eritrea"] = "ER"; code["Estonia"] = "EE"; code["Ethiopia"] = "ET"; code["Falkland Islands (Malvinas)"] = "FK"; code["Faroe Islands"] = "FO"; code["Fiji"] = "FJ"; code["Finland"] = "FI"; code["France"] = "FR"; code["French Guiana"] = "GF"; code["French Polynesia"] = "PF"; code["French Southern Territories"] = "TF"; code["Gabon"] = "GA"; code["Gambia"] = "GM"; code["Georgia"] = "GE"; code["Germany"] = "DE"; code["Ghana"] = "GH"; code["Gibraltar"] = "GI"; code["Greece"] = "GR"; code["Greenland"] = "GL"; code["Grenada"] = "GD"; code["Guadeloupe"] = "GP"; code["Guam"] = "GU"; code["Guatemala"] = "GT"; code["Guernsey"] = "GG"; code["Guinea"] = "GN"; code["Guinea-Bissau"] = "GW"; code["Guyana"] = "GY"; code["Haiti"] = "HT"; code["Heard Island & Mcdonald Islands"] = "HM"; code["Holy See (Vatican City State)"] = "VA"; code["Honduras"] = "HN"; code["Hong Kong"] = "HK"; code["Hungary"] = "HU"; code["Iceland"] = "IS"; code["India"] = "IN"; code["Indonesia"] = "ID"; code["Iran, Islamic Republic Of"] = "IR"; code["Iraq"] = "IQ"; code["Ireland"] = "IE"; code["Isle Of Man"] = "IM"; code["Israel"] = "IL"; code["Italy"] = "IT"; code["Jamaica"] = "JM"; code["Japan"] = "JP"; code["Jersey"] = "JE"; code["Jordan"] = "JO"; code["Kazakhstan"] = "KZ"; code["Kenya"] = "KE"; code["Kiribati"] = "KI"; code["Korea"] = "KR"; code["Kuwait"] = "KW"; code["Kyrgyzstan"] = "KG"; code["Lao People's Democratic Republic"] = "LA"; code["Latvia"] = "LV"; code["Lebanon"] = "LB"; code["Lesotho"] = "LS"; code["Liberia"] = "LR"; code["Libyan Arab Jamahiriya"] = "LY"; code["Liechtenstein"] = "LI"; code["Lithuania"] = "LT"; code["Luxembourg"] = "LU"; code["Macao"] = "MO"; code["Macedonia"] = "MK"; code["Madagascar"] = "MG"; code["Malawi"] = "MW"; code["Malaysia"] = "MY"; code["Maldives"] = "MV"; code["Mali"] = "ML"; code["Malta"] = "MT"; code["Marshall Islands"] = "MH"; code["Martinique"] = "MQ"; code["Mauritania"] = "MR"; code["Mauritius"] = "MU"; code["Mayotte"] = "YT"; code["Mexico"] = "MX"; code["Micronesia, Federated States Of"] = "FM"; code["Moldova"] = "MD"; code["Monaco"] = "MC"; code["Mongolia"] = "MN"; code["Montenegro"] = "ME"; code["Montserrat"] = "MS"; code["Morocco"] = "MA"; code["Mozambique"] = "MZ"; code["Myanmar"] = "MM"; code["Namibia"] = "NA"; code["Nauru"] = "NR"; code["Nepal"] = "NP"; code["Netherlands"] = "NL"; code["Netherlands Antilles"] = "AN"; code["New Caledonia"] = "NC"; code["New Zealand"] = "NZ"; code["Nicaragua"] = "NI"; code["Niger"] = "NE"; code["Nigeria"] = "NG"; code["Niue"] = "NU"; code["Norfolk Island"] = "NF"; code["Northern Mariana Islands"] = "MP"; code["Norway"] = "NO"; code["Oman"] = "OM"; code["Pakistan"] = "PK"; code["Palau"] = "PW"; code["Palestinian Territory, Occupied"] = "PS"; code["Panama"] = "PA"; code["Papua New Guinea"] = "PG"; code["Paraguay"] = "PY"; code["Peru"] = "PE"; code["Philippines"] = "PH"; code["Pitcairn"] = "PN"; code["Poland"] = "PL"; code["Portugal"] = "PT"; code["Puerto Rico"] = "PR"; code["Qatar"] = "QA"; code["Reunion"] = "RE"; code["Romania"] = "RO"; code["Russian Federation"] = "RU"; code["Rwanda"] = "RW"; code["Saint Barthelemy"] = "BL"; code["Saint Helena"] = "SH"; code["Saint Kitts And Nevis"] = "KN"; code["Saint Lucia"] = "LC"; code["Saint Martin"] = "MF"; code["Saint Pierre And Miquelon"] = "PM"; code["Saint Vincent And Grenadines"] = "VC"; code["Samoa"] = "WS"; code["San Marino"] = "SM"; code["Sao Tome And Principe"] = "ST"; code["Saudi Arabia"] = "SA"; code["Senegal"] = "SN"; code["Serbia"] = "RS"; code["Seychelles"] = "SC"; code["Sierra Leone"] = "SL"; code["Singapore"] = "SG"; code["Slovakia"] = "SK"; code["Slovenia"] = "SI"; code["Solomon Islands"] = "SB"; code["Somalia"] = "SO"; code["South Africa"] = "ZA"; code["South Georgia And Sandwich Isl."] = "GS"; code["Spain"] = "ES"; code["Sri Lanka"] = "LK"; code["Sudan"] = "SD"; code["Suriname"] = "SR"; code["Svalbard And Jan Mayen"] = "SJ"; code["Swaziland"] = "SZ"; code["Sweden"] = "SE"; code["Switzerland"] = "CH"; code["Syrian Arab Republic"] = "SY"; code["Taiwan"] = "TW"; code["Tajikistan"] = "TJ"; code["Tanzania"] = "TZ"; code["Thailand"] = "TH"; code["Timor-Leste"] = "TL"; code["Togo"] = "TG"; code["Tokelau"] = "TK"; code["Tonga"] = "TO"; code["Trinidad And Tobago"] = "TT"; code["Tunisia"] = "TN"; code["Turkey"] = "TR"; code["Turkmenistan"] = "TM"; code["Turks And Caicos Islands"] = "TC"; code["Tuvalu"] = "TV"; code["Uganda"] = "UG"; code["Ukraine"] = "UA"; code["United Arab Emirates"] = "AE"; code["UK"] = "GB"; code["USA"] = "US"; code["United States Outlying Islands"] = "UM"; code["Uruguay"] = "UY"; code["Uzbekistan"] = "UZ"; code["Vanuatu"] = "VU"; code["Venezuela"] = "VE"; code["Viet Nam"] = "VN"; code["Virgin Islands, British"] = "VG"; code["Virgin Islands, U.S."] = "VI"; code["Wallis And Futuna"] = "WF"; code["Western Sahara"] = "EH"; code["Yemen"] = "YE"; code["Zambia"] = "ZM"; code["Zimbabwe"] = "ZW";
            
                str = str.trim();
                return (code[str] != undefined) ? code[str].toLowerCase() : '';
            }

            function getAvalibleCode(str)
            {
                // Y:通過公司審核; NE: 未審核; D:刪除; W:作廢
                // var code = {};
                // code["[Y]通過公司審核"] = 'Y'; code["[NE]未審核"] = 'NE'; code["刪除"] = 'D'; code["作廢"] = 'W';
                var code = JSON.parse('<?php echo json_encode($g_member_avalible_code); ?>');

                str = str.trim();
                console.log(code[str]);
                return (code[str] != undefined) ? code[str] : str;
            }
        </script>
    </body>
</html>