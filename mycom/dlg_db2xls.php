<?php
	global $g_db_table, $g_root_url, $g_PageTitle, $g_sidemenu_idx;
    
	$root_url = $g_root_url;
	$data = array();
	$remote_ip = get_remote_ip();
	$member_id = isset($_SESSION['userid']) ? $_SESSION['userid']: '';
	$title_str       = getSubMenuString($menu_idx, $submenu_idx);
	$start_index_str = isset($start_index) ? $start_index: '';
	$page_num_str    = isset($page_offset) ? $page_offset: '';
    // echo $hide_col_str;

    $caption = getSubMenuString($menu_idx, $submenu_idx);
        // echo $table.", ".          
        // $title_str.", ".
        // $where_str.", ".
        // $sort_str.", ".
        // $hide_col_str.", ".
        // $start_index_str.", ".
        // $page_num_str.", <br>";
?>
<SCRIPT>
    function delProgress(jsmemberid_str, jsfilename_str) {
        var ret_percentage = 0;
        var data = new FormData();
        data.append("memberid", jsmemberid_str  );
        data.append("filename", jsfilename_str  );
        data.append("flag"    , "export"        );
        $.ajax({
            url         : './../api/delete_progress.php',
            type        : 'POST',
            data        : data,
            processData : false,
            contentType : false,
            success: function(response) {
                // console.log(response);
            }
        });
    }
    function getProgressValue(jsmemberid_str, jsfilename_str) {
        var ret_percentage = 0;
        var data = new FormData();
        data.append("memberid", jsmemberid_str  );
        data.append("filename", jsfilename_str  );
        data.append("flag"    , "export"        );
        $.ajax({
            url         : './../api/progress.php',
            type        : 'POST',
            data        : data,
            processData : false,
            contentType : false,
            success: function(response) {
                // console.log(response);
                ret_percentage = response.Percentage;
                // console.log(ret_percentage);
                $('#access_progress').width(ret_percentage+'%');
                $('#access_progress').html(ret_percentage+'%');
            }
        });
    }
    function showMessage(msg, color, hide_progress=true) {
        $('.message_area').css('display', 'block');
        if (hide_progress)
            $('.progress_area').css('display', 'none');
        $('.resultmsg').html('<span class="msg" style="' + color + '">' + msg + '</span>');
    }
    function hideMessage() {
        $('.message_area').css('display', 'none');
        $('.resultmsg').html('<span class="msg"></span>');
    }
    function startProcess() {
        document.getElementById("submit").disabled = true;
        document.getElementById("close").disabled = true;
        document.getElementById("filename").disabled = true;
        document.getElementById("rbExportPart").disabled = true;
        document.getElementById("rbExportAll").disabled = true;
        document.getElementById("rcd_start").disabled = true;
        document.getElementById("rcd_end").disabled = true;
    }
    function radioClick() {
        if (document.getElementById("rbExportAll").checked)
            $('.rcd_area').css('display', 'block');
        else
            $('.rcd_area').css('display', 'none');
    }
    $(document).ready(function() {
        $('.progress_area').css('display', 'none');
        $('.message_area').css('display', 'none');
        $('form').submit(function(e) {
            e.preventDefault();
            $('.progress_area').css('display', 'block');
            var jsfilename_str = document.getElementById('filename').value;
            if (jsfilename_str == "") {
                showMessage('請輸入檔案名稱', 'color:red;');
                return;
            }
            hideMessage();
            startProcess();
            // let xlsx = {filename: filename_str,
            //             memberid: memberid_str,
            //             title   : title_str};
            // console.log(filename_str);
            var js_root_url     = "<?php echo $root_url;         ?>";
            var jsmemberid_str  = "<?php echo $member_id;        ?>";
            var jstable_str     = "<?php echo $table;            ?>";
            var jstitle_str     = "<?php echo $title_str;        ?>";
            var jswhere_str     = "<?php echo $where_str;        ?>";
            var jssort_str      = "<?php echo $sort_str;         ?>";
            var jshide_col_str  = "<?php echo $without_columns;  ?>";
            var jsstart_index   = "<?php echo $start_index_str;  ?>";
            var jspage_num_str  = "<?php echo $page_num_str;     ?>";

            // console.log('js_root_url :'     + js_root_url);
            // console.log('jsmemberid_str :'  + jsmemberid_str);
            // console.log('jstable_str :'     + jstable_str);
            // console.log('jstitle_str :'     + jstitle_str);
            // console.log('jswhere_str :'     + jswhere_str);
            // console.log('jssort_str :'      + jssort_str);
            // console.log('jshide_col_str :'  + jshide_col_str);
            // console.log('jsstart_index :'   + jsstart_index);
            // console.log('jspage_num_str :'  + jspage_num_str);
            // console.log('rbExportAll :'  + document.getElementById("rbExportAll").checked);
            if (document.getElementById("rbExportAll").checked) {
                jsstart_index  = parseInt(document.getElementById('rcd_start').value);
                jsstart_index--;
                var jsend_index = parseInt(document.getElementById('rcd_end').value);
                jspage_num_str = jsend_index - jsstart_index;
            }
            delProgress(jsmemberid_str, jsfilename_str);
            
            // progress bar
            var id = setInterval(updateProgressbar, 10);
            var progress_area = $('.progress_area').width();
            // console.log('progress_area :' + progress_area);
            function updateProgressbar() {
                getProgressValue(jsmemberid_str, jsfilename_str);
                var progress = $('#access_progress').width();
                // console.log(progress + " / " + Math.floor(progress_area * 0.99));
                
                if (progress >= Math.floor(progress_area * 0.99)) {
                    showMessage('儲存檔案中，請耐心等候 . . .', 'color:green;', false);
                    clearInterval(id); // clear progress timer
                }
            }

            var formData = new FormData();
            formData.append("filename"       , jsfilename_str   );
            formData.append("memberid"       , jsmemberid_str   );
            formData.append("table"          , jstable_str      );
            formData.append("caption"        , jstitle_str      );
            formData.append("where"          , jswhere_str      );
            formData.append("sort"           , jssort_str       );
            formData.append("without_columns", jshide_col_str   );
            formData.append("start_index"    , jsstart_index    );
            formData.append("page_num"       , jspage_num_str   );
            // alert(document.getElementById('filename').value);
            $.ajax({
                url         : './../api/export2excel.php',
                type        : 'POST',
                data        : formData,
                processData : false,
                contentType : false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    console.log(xhr);
                    xhr.onprogress = function(evt) {
                        console.log(evt.lengthComputable);
                        console.log(evt.loaded);
                        if(evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total;
                            percentComplete = parseInt(percentComplete * 100);
                            // $('#access_progress').width(percentComplete+'%');
                            // $('#access_progress').html(percentComplete+'%');
                        }
                    };
                    return xhr;
                },
                success: function(response) {
                    console.log(response);
                    // var resp = JSON.parse(response);
                    document.getElementById("close").disabled = false;
                    showMessage(response.responseMessage, '');
                    clearInterval(id); // clear progress timer
                    if (response.status == "true" && response.json != '' ) {
                        var file_name_json = JSON.parse(response.json);
                        if (file_name_json.download_file_name != '') {
                            $('#download').css('display', 'block');
                            var download_url = js_root_url + "excel/export/" + file_name_json.download_file_name;
                            document.getElementById("download").href = download_url;
                        }
                    }
                    delProgress(jsmemberid_str, jsfilename_str);
                }
            });
        });
    });
        
    function hideDialog()
    {
        $('#access_progress').width('0%');
        $('#access_progress').html('0%');
        $('.overlay-export').css('display', 'none');
        $('.message_area').css('display', 'none');
        $('.progress_area').css('display', 'none');
        $('#download').css('display', 'none');

        document.getElementById("filename").disabled = false;
        document.getElementById("rbExportPart").disabled = false;
        document.getElementById("rbExportAll").disabled = false;
        document.getElementById("rcd_start").disabled = false;
        document.getElementById("rcd_end").disabled = false;
        document.getElementById("submit").disabled = false;
        document.getElementById("close").disabled  = false;

        document.getElementById('filename').value  = '';
    }
</SCRIPT>
<div class="overlay-export" style="display:none">
    <div class="mydlg">
        <div class="dlghead">
            <div class="dlgtitle">匯出 Excel</div>
        </div>
        <div class="dlgcontainer">
            <form method="post" enctype="multipart/form-data" id="export_form">
                <br>
                <div class="form-group">
                    <label for="filename">輸入欲匯出 Excel 檔名：</label>
                    <input id="filename" type="text" name="filename"><span>.xlsx</span>
                    <br><br>
                    <div class="form-check">
                        <input type="radio" id="rbExportPart" name="rbExport" value="rbExportPart" checked onclick="radioClick()">
                        <label class="form-check-label" for="rbExportPart">
                            搜尋結果-本頁
                        </label>
                        <?php echo getHtmlSpaceChar(5); ?>
                        <input type="radio" id="rbExportAll" name="rbExport" value="rbExportAll" onclick="radioClick()">
                        <label class="form-check-label" for="rbExportAll">
                            搜尋結果-全部資料
                        </label>
                    </div>
                    <br>
                    <div class="rcd_area" style="display:none;">
                        <label>區間：</label>
                        <input id="rcd_start" type="text" name="rcd_start" value="1" style="width:100px;">
                        <?php echo getHtmlSpaceChar(2); ?> ~ <?php echo getHtmlSpaceChar(2); ?>
                        <input id="rcd_end" type="text" name="rcd_end" value="<?php echo $total_rows ?>" style="width:100px;">
                        <div style="color:gray;">提示：匯出的量過大時，需耗時較久，如不想等待，刷新頁面，即可跳出</div>
                    </div>
                </div>
                <br>
                <div class="progress_area">
                    <div class="progress">
                        <div id="access_progress" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <br><br>
                </div>
                <div class="message_area">
                    <div class="resultmsg"></div>
                    <br>
                    <br>
                    <a id="download" href="" style="display:none" >點我下載</a>
                    <br>
                </div>
                <div class="form-group">
                    <button id="submit" type="submit" class="btn btn-primary">匯出</button>
                    <button id="close" type="button" class="btn btn-secondary" onclick="hideDialog()">關閉</button>
                </div>
            </form>
        </div>
    </div>
</div>