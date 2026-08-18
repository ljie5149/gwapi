<?php
    /***********************************************/
    /*                                             */
    /*  建立授權map      $g_crud_value_array        */
    /*                                             */
    /***********************************************/
    // global $g_crud_id_array, $g_crud_zhtw_array, $g_author_page;
    // global $g_funcidx_main, $g_funcidx_srch, $g_funcidx_add, $g_funcidx_edit, $g_funcidx_delete, $g_funcidx_import, $g_funcidx_export;
    global $g_sidemenu;
    $g_sidemenu_root    = $g_sidemenu['root'     ];
    $g_sidemenu_root_id = $g_sidemenu['root_id'  ];
	$g_crud_id_array    = ["list", "add", "edit", "delete", "import", "export"];
	$g_crud_zhtw_array  = ["查詢", "新增", "編輯", "刪除", "匯入", "匯出"];
    
    // map index
    $g_funcidx_main     = 0;
    $g_funcidx_srch     = 1;
    $g_funcidx_add      = 2;
    $g_funcidx_edit     = 3;
    $g_funcidx_delete   = 4;
    $g_funcidx_import   = 5;
    $g_funcidx_export   = 6;

    $g_author_page = array();
    $m = 0; $n = 0;
    for ($i = 0; $i < count($g_sidemenu_root); $i++) {
        $crud_value = array();
        if (count($g_sidemenu[$g_sidemenu_root[$i]]) > 0) { // 主 Menu
            $m_item_id = $g_sidemenu_root_id[$i];
            $s_item    = $g_sidemenu[$g_sidemenu_root[$i]         ];
            $s_item_id = $g_sidemenu[$g_sidemenu_root[$i].'_id'   ];
            for ($j = 0; $j < count($s_item); $j++) { // 副 Menu
                $n = 0;
                if ($m == 0) $m = 1;
                else $m *= 2;
                
                $crud_value[$n++] = $m;
                for ($k = 0; $k < count($g_crud_id_array); $k++) { // crud
                    $crud_value[$n++] = $m;
                }
                $key = $m_item_id.'_'.$s_item_id[$j];
                $g_author_page[] = array('name'=> $key, 'auth_page' => $crud_value);
                // unset($crud_value);
            }
        }
    }
    function getAuthorEnable($page_id, $authority, $funcidx, $is_mainmenu = false)
    {
        global $g_sidemenu, $g_sidemenu_root;
        global $g_author_page, $g_funcidx_add;
        
        $ret = 0;
        $authority_array = explode('!!', $authority); // 切割登入者權限
        // var_dump($authority_array);
        if ($is_mainmenu) $page_id.= "_"; // 如果是 主menu；例：a, b, c, d ... 等
        for ($i = 0; $i < count($g_sidemenu_root); $i++) {
            if (count($g_sidemenu[$g_sidemenu_root[$i]]) > 0) { // 主 Menu
                $item       = $g_sidemenu[$g_sidemenu_root[$i]         ];
                $item_id    = $g_sidemenu[$g_sidemenu_root[$i].'_id'   ];
                for ($j = 0; $j < count($item); $j++) { // 副 Menu
                    $key = $item_id[$j];
                    for ($k = 0; $k < count($g_author_page); $k++) {
                        $author_page_one = $g_author_page[$k];
                        $page_name = $author_page_one['name'];
                        $page_auth = $author_page_one['auth_page'];
                        // var_dump($author_page_one);
                        // echo "page_name :$page_name, funcidx :$funcidx, $page_auth[$funcidx]<br>";

                        if ($is_mainmenu) { // 進入 function 為 主menu
                            if (strStartWith($page_name, $page_id) === false) continue;
                            $ret |= ($page_auth[$funcidx] & $authority_array[$funcidx]);

                        } else { // 進入 function 為 副menu
                            if ($funcidx < $g_funcidx_add) {
                                // echo 'for side menu<br>';
                                if (strEndWith($page_name, $page_id) === false) continue;
                                $ret |= ($page_auth[$funcidx] & $authority_array[$funcidx]);
                            } else {
                                $do_process = false;
                                $page_name_array = explode('_', $page_name); // 名字
                                
                                // var_dump($page_name_array);
	                            $page_name_dst = isset($page_name_array[1]) ? $page_name_array[1]: '';
                                if (empty($page_name_dst)) continue;
                                $page_name_dst = str_replace('list', '', $page_name_dst);
                                $page_name_dst = str_replace('php', '', $page_name_dst);
                                if ($page_name_dst === $page_id) $do_process = true;
                                if (strpos($page_name, $page_id) === true) $do_process = true;
                                if ($do_process) {
                                    // echo $page_auth[$funcidx]. ' & authority_array:'. $authority_array[$funcidx].'<br>';
                                    $flag = ($page_auth[$funcidx] & $authority_array[$funcidx]);
                                    // echo $page_id.', '.$flag.'<br>';
                                    $ret |= $flag;
                                    // echo 'ret:'. $ret.'<br>';
                                }
                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }
?>