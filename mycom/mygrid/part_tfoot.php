<?php
    global $g_page_options;
    $page_opt = $g_page_options;
    // generate pagination links
    $prev_property = ($page > 1)            ? 'class="btn btn-outline-primary btn-sm" onclick="clickPage('.$page.', -1)"' :
                                                'href="#" class="btn btn-secondary btn-sm disabled" role="button" aria-disabled="true"';
    $next_property = ($page < $total_pages) ? 'class="btn btn-outline-primary btn-sm"  onclick="clickPage('.$page.', 1)"' :
                                                'href="#" class="btn btn-secondary btn-sm disabled" role="button" aria-disabled="true"';
    $space = getHtmlSpaceChar(3);
    echo '<tr class="tail">';
        echo '<td>';
        echo '  <label for="show_perpage">每頁顯示筆數：</label>
                <select id="show_perpage" onchange="changeSelect()" class="custom-select" style="width:80px;">';
        for ($i = 0; $i < count($page_opt); $i++) {
            $selected = ($page_opt[$i] == $page_offset) ? "selected" : "";
            echo '  <option value="'.$page_opt[$i].'" '.$selected.'>'.$page_opt[$i].'</option>';
        }
        echo '  </select>';
        echo '</td>';

        echo '<td>';
            echo '<a '.$prev_property.' title="" style="font-weight:bold">上一頁</a>';
                if ($total_pages >= 4) {
                    // 1 ~ 3 頁
                    for ($i = 1; $i <= 3; $i++) {
                        if ($i == $page)
                            echo '<span class="btn-blue"><a href="#" class="btn btn-primary btn-sm disabled" role="button" aria-disabled="true">'.$i.'</a></span>';
                        else
                            echo '<span class="btn-blue"><a class="btn btn-outline-primary btn-sm" onclick="toPage('.$i.')">'.$i.'</a></span>';
                    }
                    
                    // 其他頁數
                    // if ($page > 3 && $page != $total_pages) {
                        echo " . . . ";
                        echo '<span class="btn-blue">
                                <input id="key_page" type="text"  placeholder="輸入頁碼" value="'.$page.'"
                                        class="btn btn-outline-secondary btn-sm" style="width:60px; background-color:white; color:black;"
                                        onchange="checkInput()"
                                        onclick="clickKeypage()"
                                        title="請輸入頁碼，按下Enter鍵"
                                        />
                              </span>';
                    // }
                    echo " . . . ";

                    // 尾頁
                    if ($page == $total_pages)
                        echo '<span class="btn-blue"><a href="#" class="btn btn-primary btn-sm disabled" role="button" aria-disabled="true">'.$page.'</a></span>';
                    else
                        echo '<span class="btn-blue"><a class="btn btn-outline-primary btn-sm" onclick="toPage('.$total_pages.')">'.$total_pages.'</a></span>';
                    
                } else {
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i == $page)
                            echo '<span class="btn-blue"><a href="#" class="btn btn-primary btn-sm disabled" role="button" aria-disabled="true">'.$i.'</a></span>';
                        else
                            echo '<span class="btn-blue"><a class="btn btn-outline-primary btn-sm" onclick="toPage('.$i.')">'.$i.'</a></span>';
                    }
                }
            echo '<a '.$next_property.' title="" style="font-weight:bold">下一頁</a>';
        echo '</td>';
        echo '<td>'.sprintf("%d%s-%s%d%s共%s%d%s筆紀錄(第%s%d%s頁%s/%s%d%s頁)", $start_index + 1, $space, $space, $end_index, $space, $space, $total_rows, $space
                            , $space, $page, $space, $space, $space, $total_pages, $space).'</td>';
    echo '</tr>';
?>

<SCRIPT LANGUAGE=javascript>
    var cur_page = "";
    var total_page = '<?php echo $total_pages; ?>';
    var inputId = document.getElementById("key_page");
    if (inputId !== null) {
        inputId.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                protectInput();
            }
        });
    }
    function checkInput() {
        if (event.keyCode==13) {
            protectInput();
        }
    }
    function protectInput() {
        if (parseInt(inputId.value) <= parseInt(total_page)) {
            if (parseInt(inputId.value) < 1)
                toPage(1);
            else
                toPage(inputId.value);
        } else {
            inputId.value = cur_page;
        }
    }
    function clickKeypage() {
        cur_page = '<?php echo $page; ?>';
        document.getElementById("key_page").focus();
    }
</SCRIPT>