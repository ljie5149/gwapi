<!-- Bootstrap CSS -->
<!-- 新 Bootstrap4 核心 CSS 文件 ExtraMenu -->
<link href="./../css/v4.3.1/bootstrap.min.css" rel="stylesheet">
<link href="./../css/v4.5.2/css/bootstrap.min.css" rel="stylesheet">
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script> -->
<script src="./../vendor/jquery/2.1.1/jquery.min.js"></script>
<link href="./../css/mydialog.css" rel="stylesheet" type="text/css">
<SCRIPT>
    $(document).ready(function() {
        $('.progress_area').css('display', 'none');
        $('#upload_progress').width('0%');
        $('#upload_progress').html('0%');
        $('form').submit(function(e) {
            e.preventDefault();
            $('.progress_area').css('display', 'block');
            var formData = new FormData();
            var Progress = 0;
            formData.append("Progress", Progress);
            $.ajax({
                url         : './../api/progressbar.php',
                type        : 'POST',
                data        : formData,
                processData : false,
                contentType : false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.onprogress = function(e) {
                        if(evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total;
                            percentComplete = parseInt(percentComplete * 100);
                            $('#upload_progress').width(percentComplete+'%');
                            $('#upload_progress').html(percentComplete+'%');
                        }
                    };
                    return xhr;
                },
                success: function(response) {
                    if (response.json != undefined) {
                        $('.resultmsg').css('display', 'block');
                        // $('.progress_area').css('display', 'none');
                        $('.resultmsg').html('<span class="msg">' + response.Progress + '</span>');
                        $('#submit').css('display', 'none');
                        $('#comfirm').css('display', 'block');
                    }
                }
            });
        });
    });
</SCRIPT>

<div class="mybody"></div>
<div class="mydlg">
    <div class="dlghead">
        <div class="dlgtitle">測試progress</div>
    </div> <!-- dlghead -->
    <div class="dlgcontainer">
        <form method="post" enctype="multipart/form-data" id="upload_form">
            <div class="progress_area">
                <div class="progress">
                    <div id="access_progress" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
                <br><br>
            </div>
            <div class="message_area">
                <div class="resultmsg"></div>
                <br><br>
            </div>
            <div class="form-group">
                <input id="submit" type="submit" name="submit" value="開始進行" class="btn btn-primary" style="display:block;">
                <input id="comfirm" type="button" name="confirm" value="確定" class="btn btn-primary" style="display:none;" >
            </div> <!-- form-group -->
        </form>
        <br>
    </div> <!-- dlgcontainer -->
</div> <!-- mydlg -->