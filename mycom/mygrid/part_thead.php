<tr class="head">
    <th class="head"><?php echo $caption.$sub_caption.getHtmlSpaceChar(3) ?>
        <a class="btn btn-outline-success btn-sm" href="mygrid/add.php?<?php echo $url_param_str ?>" style="<?php echo $allow_add ?>" >新增</a>
    </th>
    <th class="head float-right">
        <div class="dropdown" style="<?php echo $allow_extra ?>">
            <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                選單
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" onclick="showExportDialog()" style="<?php echo $allow_export ?>">匯出Excel</a>
                <a class="dropdown-item" onclick="showImportDialog()" style="<?php echo $allow_import ?>">匯入Excel</a>
            </div>
        </div>
    </th>
</tr>
<tr class="fieldhead">
    <?php
        for ($i = 0; $i < count($column_info); $i++) {
            $com    = $column_info[$i];
            $field  = $com[$g_fldidx_name];
            $name   = $com[$g_fldidx_comment];
            $show   = ($com[$g_fldidx_show]         == "true");
            $hidden = ($com[$g_fldidx_showbuthide]  == "true");
            $pty_hide_str = ($hidden) ? 'hidden' : '';

            $name          = str_replace('序號', '', $name);
            $show_sortflag = ($sort != $field) ? "" : $show_sort;
            $style         = getGridColWidth($field);//.' '.$display_str;
            if ($i > 0) echo '<div class="vr"></div>';
            if ($show) {
                echo '<th field="'.$field.'" style="border-right: 1px solid #e5e5e5; cursor:pointer; '.$style.'" '.$pty_hide_str.' 
                        onclick="clickField(\''.$field.'\','.$sort_flag.')">'.$name.'<span class="btn-blue">'.$show_sortflag.'</span></th>';
            }
        }
        echo '<th field="Actions" style="border-right: 1px solid #e5e5e5;">操作</th>';
        echo '<th field="scroll" style="width:10px;"></th>';
    ?>
</tr>
<tr>
    <?php
        for ($i = 0; $i < count($column_info); $i++) {
            $com    = $column_info[$i];
            $field  = $com[$g_fldidx_name];
            $name   = $com[$g_fldidx_comment];
            $show   = ($com[$g_fldidx_show]         == "true");
            $hidden = ($com[$g_fldidx_showbuthide]  == "true");
            $search = ($com[$g_fldidx_srch]         == "true");
            
            $pty_hide_str = ($hidden) ? 'hidden' : '';

            $show_sortflag      = ($sort != $field) ? "" : $show_sort;
            $show_search_input  = $search && $show && !$hidden;
            $type               = ($search || $hidden) ? "text" : "hidden";

            $style = getGridColWidth($field);
            if ($show) {
                if ($field == 'role'          ||
                    $field == 'gender'        ||
                    $field == 'avalible'      ||
                    $field == 'sales_specify' ) {
                    
                    if ($field == 'role')
                        $array_select = $role_avalible;
                    else if ($field == 'gender')
                        $array_select = $gender_avalible;
                    else if ($field == 'avalible')
                        $array_select = $msg_avalible;
                    else if ($field == 'sales_specify')
                        $array_select = getKeyVal4Sales($member_id);
                        
                    echo '  <th style="'.$style.' height=16px;" '.$pty_hide_str.'>
                                <select id="'.$field.'" class="custom-select" style="font-size:8px; height=16px;"
                                        onchange="searchSelect4other(\''.$field.'\')">';
                                $j = 0;
                                $pre_sel = isset($prev_search[$field]) ? $prev_search[$field] : '';
                                $select_option = (empty($pre_sel)) ? 'selected' : '';
                                echo '<option value="" '.$select_option.'>(全部) 搜尋 . . .</option>';
                                foreach ($array_select as $key => $value) {
                                    $select_option = ($value == $prev_search[$field]) ? 'selected' : '';
                                    echo '<option value="'.$value.'" '.$select_option.'>'.$key.'</option>';
                                    $j++;
                                }
                    echo '      </select>
                            </th>
                        ';
                } else if (stripos($field, '_date')) {
                    echo '<th style="'.$style.'" '.$pty_hide_str.'>
                            <input title="搜尋起始日期" id="'.$field.'_s" type="date" name="'.$field.'_s" value="'.$prev_search[$field."_s"].'" onchange="searchInputDate(\''.$field.'_s\')" style="font-size:8px; width: 80px; border: 1px solid lightgray;"/>
                            <input title="搜尋結束日期" id="'.$field.'_e" type="date" name="'.$field.'_e" value="'.$prev_search[$field."_e"].'" onchange="searchInputDate(\''.$field.'_e\')" style="font-size:8px; width: 80px; border: 1px solid lightgray;"/>
                          </th>';
                } else if ($field == 'parent_sid') {
                    $array_select = array();
                    $array_select = $kind_parent;
                    echo '<th style="'.$style.'" '.$pty_hide_str.'>
                            <select id="'.$field.'" class="custom-select" style="font-size:8px;" onchange="searchSelect4ParentSid(\''.$field.'\')">';
                            $j = 0;
                            $pre_sel = isset($prev_search[$field]) ? $prev_search[$field] : '';
                            $select_option = (empty($pre_sel)) ? 'selected' : '';
                            echo '<option value="" '.$select_option.'>(全部) 搜尋 . . .</option>';
                            foreach ($array_select as $key => $value) {
                                $select_option = ($value == $prev_search[$field]) ? 'selected' : '';
                                echo '<option value="'.$value.'" '.$select_option.'>'.$key.'</option>';
                                $j++;
                            }
                    echo '  </select>
                          </th>';
                } else {
                    echo '<th style="'.$style.'" '.$pty_hide_str.'>
                            <input id="'.$field.'"
                                data-ticket="'.$field.'"
                                aria-label="'.$field.'"
                                value="'.$prev_search[$field].'"
                                onchange="searchInput(\''.$field.'\')"
                                class="form-control" type="'.$type.'"
                                title="輸入搜尋內容，按下Enter鍵開始搜尋"
                                style="font-size:8px;" placeholder="搜尋 . . .">
                          </th>';
                }
            }
        }
        echo '<th field="Actions" style="border-right: 1px solid #e5e5e5;"></th>';
        echo '<th field="scroll" style="width:10px;"></th>';
    ?>
</tr>

<SCRIPT LANGUAGE=javascript>
    function searchInput(id) {
        var inputId = document.getElementById(id);
        if (inputId.value.length > 0) {
        }
        var url = mergeParamToUrl("srch" + id, inputId.value);
        addParamToUrl("page", 1, url);
    }
    function searchInputDate(id) {
        var inputId = document.getElementById(id);
        // alert(inputId.value);
        if (inputId.value.length > 0) {
        }
        var url = mergeParamToUrl("srch" + id, inputId.value);
        addParamToUrl("page", 1, url);
    }
    function searchSelect4ParentSid(id) {
        var inputId = document.getElementById(id);
        // var js_select = JSON.parse('<?php //echo json_encode($kind_parent) ?>');
        // var js_select_val = "";
        // console.log(inputId.value);
        if (inputId.value.length > 0) {
            // js_select_val = js_select[inputId.value];
        }
        var url = mergeParamToUrl("srch" + id, inputId.value);
        addParamToUrl("page", 1, url);
    }
    function searchSelect4other(id) {
        // console.log(id);
        var inputId = document.getElementById(id);
        // console.log(inputId.value);
        var url = mergeParamToUrl("srch" + id, inputId.value);
        addParamToUrl("page", 1, url);
    }
    function showExportDialog() {
        $('.overlay-export').css('display', 'block');
    }
    function showImportDialog() {
        $('.overlay-import').css('display', 'block');
    }
</SCRIPT>