<?php
    global $g_base_avalible, $g_member_avalible_zhtw, $g_base_avalible_zhtw;
    $db = new CXDB($remote_ip);
    try {
        $member_list = [];
        $group_list = [];
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $sort_str = (strlen($sort) > 0) ? $sort : "";
            if (strlen($sort_flag) > 0) $sort_str.= ($sort_flag == "0") ? " DESC" : " ASC";
            switch ($mode) {
                case 'member':
                    // $result = $db->getMember($link, "", "*", $where_str, "", "", false, $sort_str, "limit ".$start_index.",".$page_offset);
                    break;
                case 'cmymember':
                    // $result = $db->getCmymember($link, "", "*", $where_str, "", "", false, $sort_str, "limit ".$start_index.",".$page_offset);
                    break;
                case 'memberinconferenceroom':
                    $result_group_list  = $db->getData($link, "data_conferenceroom", "", "sid,name", "");
                    if (!is_null($result_group_list) && mysqli_num_rows($result_group_list) > 0) {
                        while ($row = mysqli_fetch_array($result_group_list)) {
                            $group_list[$row['sid']] = $row['name']." ".$row['sid'];
                        }
                    }
                    $result_member_list = $db->getData($link, "data_member", "", "sid,mid,name", "");
                    if (!is_null($result_member_list) && mysqli_num_rows($result_member_list) > 0) {
                        while ($row = mysqli_fetch_array($result_member_list)) {
                            $member_list[$row['sid']] = $row['name']." ".$row['mid'];
                        }
                    }
                    break;
            }
            $result = $db->getData($link, $table, "", "*", $where_str, "", $sort_str, "limit ".$start_index.",".$page_offset);
            if (!is_null($result) && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    // $bg_color = (intval($row['id']) % 2 == 1) ? "#f9f9f9": "white";
                    echo '<tr>';
                    $rcdid = $row[$column_info[0][$g_fldidx_name]];
                    for ($i = 0; $i < count($column_info); $i++) {
                        $col_info = $column_info[$i];

                        $field  = $col_info[$g_fldidx_name];
                        $name   = $col_info[$g_fldidx_comment];
                        $show   = ($col_info[$g_fldidx_show]        == "true");
                        $hidden = ($col_info[$g_fldidx_showbuthide] == "true");
                        $search = ($col_info[$g_fldidx_srch]        == "true");
            
                        $pty_hide_str = ($hidden) ? 'hidden' : '';
                        
                        if ($i == count($column_info) - 1 ) {
                            echo '<td id="action-'.$rcdid.'">';
                            echo '  <span id="edit_'.$rcdid.'">';
                            if (strval($priority) > 1) {
                                echo '      <a title="編輯" href="javascript:void(0);" onclick="goEdit('.$rcdid.');" style="'.$allow_edit.'"><i class="fa fa-edit"></i></a>&nbsp;';
                            }
                            if ($priority == "99999") {
                                echo '      <a title="刪除" href="javascript:void(0);" onclick="goDel('.$rcdid.');" style="'.$allow_delete.'"><i class="fa fa-trash"></i></a>';
                            }
                            echo '  </span>';
                            echo '  <span style="display:none;" id="save_'.$rcdid.'">';
                            echo '      <a title="儲存" class="ui-custom-icon ui-icon ui-icon-disk" href="javascript:void(0);" onclick="saveRow('.$rcdid.');"></a>&nbsp;';
                            echo '      <a title="取消" class="ui-custom-icon ui-icon ui-icon-cancel" href="javascript:void(0);" onclick="cancelRow('.$rcdid.');"></a>';
                            echo '  </span>';
                            echo '</td>';
                        } else {
                            if ($show) { // show
                                $style = getGridColWidth($field);
                                $value = $row[$field];
                                if ($field == 'parent_sid') {
                                    $value = getPdctKindStrByPntSid($kind_parent, $row[$field]);
                                } else if ($field == 'sales_specify') {
                                    // var_dump($sales_array); echo $value;
                                    $value = getSalesName($sales_array, $value);
                                } else if ($field == 'avalible') {
                                    $value = ($mode == "member") ? $g_member_avalible_zhtw[$value] : $g_base_avalible_zhtw[$value];
                                } else if ($field == 'is_partner') {
                                    $value = $g_is_partner_zhtw[$value];
                                } else if ($field == 'new_kind') {
                                    $value = $g_news_kind_zhtw[$value];
                                } else if ($field == 'agency_sid') {
                                    $value = $agency_array[$value];
                                }
                                if ($mode == "memberinconferenceroom") {
                                    if (count($group_list) > 0) {
                                        if ($field == "conferenceroom_sid") {
                                            $value = $group_list[$value];
                                        }
                                    }
                                    if (count($member_list) > 0) {
                                        if ($field == "member_sid" || $field == "edit_sid") {
                                            $value = $member_list[$value];
                                        }
                                    }
                                }
                                
                                $fPath = isset($row['file_path']) ? $row['file_path'] : "";
                                // echo $fPath;
                                if ($field == 'file_content') {
                                    $src = "";
                                    // echo $fPath;
                                    if (!empty($row['file_path']))
                                        $src = './../'.$row['file_path'];
                                    else if (!empty('file_content'))
                                        $src = $row['file_content'];
                                    
                                    if (strEndWith(strtolower($fPath), '.pdf')) {
                                        if ($src == "")
                                            echo '<td style="margin-left:0px; font-size:14px; text-align: left;" id="'.$field.'-'.$rcdid.'" title="'.$value.'" '.$pty_hide_str.'>'.getShortText4Show($value, $short_text_len)."</td>";
                                        else
                                            echo '<td style="margin-left:0px; font-size:14px" id="'.$field.'-'.$rcdid.'"><embed src="'.$src.' " type="application/pdf" width="100%" height="600px"></td>';
                                    
                                    } else {
                                        if ($src == "")
                                            echo '<td style="margin-left:0px; font-size:14px; text-align: left;" id="'.$field.'-'.$rcdid.'" title="'.$value.'" '.$pty_hide_str.'>'.getShortText4Show($value, $short_text_len)."</td>";
                                        else
                                            echo '<td style="margin-left:0px; font-size:14px" id="'.$field.'-'.$rcdid.'"><img style="width:32px; height:32px;" src="'.$src.'" '.$pty_hide_str.'/></td>';
                                    }
                                } else if (strEndWith($field, '_img')) {
                                    $src = "";
                                    if (!empty($row[$field]))
                                        $src = './../'.$row[$field];
                                        
                                    if (strEndWith(strtolower($fPath), '.pdf')) {
                                        if ($src == "")
                                            echo '<td style="margin-left:0px; font-size:14px; text-align: left;" id="'.$field.'-'.$rcdid.'" title="'.$value.'" '.$pty_hide_str.'>'.getShortText4Show($value, $short_text_len)."</td>";
                                        else
                                            echo '<td style="margin-left:0px; font-size:14px" id="'.$field.'-'.$rcdid.'"><iframe  src="'.$src.' " type="application/pdf" style="width: 100%; height: 300px;"></iframe></td>';
                                    
                                    } else {
                                        if ($src == "")
                                            echo '<td style="margin-left:0px; font-size:14px; text-align: left;" id="'.$field.'-'.$rcdid.'" title="'.$value.'" '.$pty_hide_str.'>'.getShortText4Show($value, $short_text_len)."</td>";
                                        else
                                            echo '<td style="margin-left:0px; font-size:14px" id="'.$field.'-'.$rcdid.'"><img style="width:128px; height:128px;" src="'.$src.'" '.$pty_hide_str.'/></td>';
                                    }
                                } else {
                                    if (empty($value)) $value = " ";
                                    echo '<td style="margin-left:0px; font-size:14px; '.$style.'" id="'.$field.'-'.$rcdid.'" title="'.$value.'" '.$pty_hide_str.'>'.getShortText4Show($value, $short_text_len)."</td>";
                                }
                            }
                        }
                    }
                    echo "</tr>";
                }
            }
        }
    } catch (Exception $e) {
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
?>

<SCRIPT LANGUAGE=javascript>
    function goEdit(id)
    {
        var url_src = window.location.href; // Get the current URL
        let pos = url_src.lastIndexOf("/");
        url_src = url_src.substring(0, pos);
        url_src += "/mygrid/edit.php";
        var js_mode = '<?php echo $mode ?>';
        var js_idx4pdctkind = '<?php echo $idx4pdctkind ?>';
        var url = '';
        if (js_idx4pdctkind != '') {
            url = mergeParamToUrl4Edit("idx", js_idx4pdctkind, url_src);
        }
        if (url == '') url = url_src;
        url = mergeParamToUrl4Edit("rcd", id, url);
        addParamToUrl("mode", js_mode, url);
    }
    function goDel(id)
    {	
        if (confirm('確定要刪除這筆資料嗎?')) {
            var js_table    = '<?php echo $table ?>';
            var js_caption  = '<?php echo $caption ?>';
            var js_memberid = '<?php echo $member_id ?>';
            var js_mode     = '<?php echo $mode ?>';
            var js_avalible = '<?php echo $g_base_avalible['[D]刪除'] ?>'; // ['[Y]正常' => 'Y', '[D]刪除' => 'D', '[W]作廢' => 'W'];
            var js_avalible_array = '<?php echo json_encode($g_base_avalible) ?>'; // ['[Y]正常' => 'Y', '[D]刪除' => 'D', '[W]作廢' => 'W'];

            var formdata = new FormData();
            formdata.append("table"    , js_table   );
            formdata.append("caption"  , js_caption );
            formdata.append("member_id", js_memberid);
            formdata.append("mode"     , js_mode    );
            formdata.append("rcd_id"   , id         );
            formdata.append("avalible" , js_avalible);
            formdata.append("avalible_array" , js_avalible_array);
            console.log(js_avalible_array);
            postAPI('./../api/b_u_avalible.php', formdata, false);
        }
    }
</SCRIPT>