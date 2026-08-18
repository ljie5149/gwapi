<SCRIPT>
    $(document).ready(function() {
        $('.progress_area-import').css('display', 'none');
        $('.message_area-import').css('display', 'none');
        $('#upload_progress').width('0%');
        $('#upload_progress').html('0%');
    });
    function submitImport() {
        var js_base64 = "";
        try {
            js_base64 = getFileBase64();
        } catch {}
        console.log('js_base64 :' + js_base64);
        $('.progress_area-import').css('display', 'block');
        var fileElement = document.getElementById('selfile');
        var files = fileElement.files;
        if (files == undefined || files.length == 0) {
            showMessage('請選擇欲匯入Excel檔案', 'color:red;');
            return;
        }
        hideImportMessage();
        startImportProcess();
        var jsfilename_str  = files[0].name;
        var jsmode          = "<?php echo $mode; ?>";
        var jsmemberid_str  = "<?php echo $member_id;        ?>";
        var jstable_str     = "<?php echo $table;            ?>";
        var jstitle_str     = "<?php echo $title_str;        ?>";
        console.log('jsmemberid_str :'  + jsmemberid_str);
        console.log('jstable_str :'     + jstable_str);
        console.log('jstitle_str :'     + jstitle_str);

        var formData = new FormData();
        formData.append("filename"   , jsfilename_str);
        formData.append("memberid"   , jsmemberid_str);
        formData.append("table"      , jstable_str   );
        formData.append("caption"    , jstitle_str   );
        formData.append("base64_file", js_base64     );
        if (jsmode=="product") {
            const xbAutoAdd = document.getElementById("xbAutoPdctClass");
            if (xbAutoAdd.checked) {
                formData.append("auto_create_pdct_class", "true");
            } else {
                formData.append("auto_create_pdct_class", "false");
            }
        }
        
        // progress bar
        var id_import = setInterval(updateProgressbar_import, 10);
        var progress_area_import = $('.progress_area-import').width();
        console.log('progress_area-import :' + progress_area_import);
        function updateProgressbar_import() {
            getProgressValue_import(jsmemberid_str, jsfilename_str);
            var progress_import = $('#upload_progress').width();
            console.log(progress_import + " / " + progress_area_import);
            
            if (progress_import >= progress_area_import) {
                clearInterval(id_import); // clear progress timer
            }
        }
        // var data_src = "";
        $.ajax({
            url         : './../api/import2db.php',
            type        : 'POST',
            data        : formData,
            processData : false,
            contentType : false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if(evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        percentComplete = parseInt(percentComplete * 100);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                console.log(response);
                document.getElementById("close-import").disabled = false;
                if (response.status == "true") {
                    if (response.responseMessage.includes("成功"))
                        showImportMessage(response.responseMessage, '');
                    else
                        showImportMessage(response.responseMessage, 'color:red;');
                } else {
                    showImportMessage(response.responseMessage, 'color:red;');
                }
                clearInterval(id_import); // clear progress timer
            }
        });
    }
    
    function getProgressValue_import(jsmemberid_str, jsfilename_str) {
        var ret_percentage = 0;
        var data = new FormData();
        data.append("memberid", jsmemberid_str  );
        data.append("filename", jsfilename_str  );
        data.append("flag"    , "import"        );
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
                $('#upload_progress').width(ret_percentage+'%');
                $('#upload_progress').html(ret_percentage+'%');
            }
        });
    }
    function startImportProcess() {
        document.getElementById("submit-import").disabled = true;
        document.getElementById("close-import").disabled = true;
        document.getElementById("selfile").disabled = true;
        document.getElementById("drop_block").disabled = true;
    }
    function hideImportDialog()
    {
        $('#upload_progress').width('0%');
        $('#upload_progress').html('0%');
        $('.overlay-import').css('display', 'none');
        $('.message_area-import').css('display', 'none');
        $('.progress_area-import').css('display', 'none');

        document.getElementById("selfile").disabled = false;
        document.getElementById("drop_block").disabled = false;
        document.getElementById("submit-import").disabled = false;
        document.getElementById("close-import").disabled  = false;
        
        window.location.reload();
    }
    function showImportMessage(msg, color, hide_progress=true) {
        $('.message_area-import').css('display', 'block');
        if (hide_progress)
            $('.progress_area-import').css('display', 'none');
        $('.resultmsg-import').html('<span class="msg" style="' + color + '">' + msg + '</span>');
    }
    function hideImportMessage() {
        $('.message_area-import').css('display', 'none');
        $('.resultmsg-import').html('<span class="msg"></span>');
    }
</SCRIPT>

<div class="overlay-import" style="display:none">
    <div class="mydlg">
        <div class="dlghead">
            <div class="dlgtitle">匯入Excel</div>
        </div> <!-- dlghead -->
        <div class="dlgcontainer">
            <form method="post" enctype="multipart/form-data" id="upload_form">
                <div class="form-group">
                    <label for="exampleFormControlFile1">選擇上傳檔案：</label>
                    <input id="selfile" type="file" name="file" class="form-control-file"
                                                     accept="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                    <div>
                        <div id="drop_block" ondragover="javaSCRIPT: drag_handler(event);" ondrop="javaSCRIPT: drop_file(event);" class="upload-block">
                            請將檔案拖曳到此...
                        </div>
                    </div>
                    <textarea id="tabase64" name="tabase64" rows="4" cols="50" class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default" hidden></textarea>
                </div> <!-- form-group -->
                <br>
                <div class="progress_area-import">
                    <div class="dlgprogress-import">
                        <div id="upload_progress" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div> <!-- dlgprogress -->
                    <br><br>
                </div>
                <div class="message_area-import">
                    <div class="resultmsg-import"></div>
                    <br>
                </div>
                <?php
                    if ($mode == "product") {
                        echo '<div class="form-group">
                                <input type="checkbox" id="xbAutoPdctClass" name="xbAutoPdctClass" checked>
                                <label for="xbAutoPdctClass">商品類別不存在時, 自動建立商品類別</label>
                            </div> <!-- form-group -->';
                    }
                ?>
                <div class="form-group">
                    <button id="submit-import" type="button" class="btn btn-primary" onclick="submitImport()">匯入</button>
                    <button id="close-import" type="button" class="btn btn-secondary" onclick="hideImportDialog()">關閉</button>
                </div> <!-- form-group -->
            </form>
        </div> <!-- dlgcontainer -->
    </div> <!-- mydlg -->
</div>