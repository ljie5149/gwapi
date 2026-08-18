function sleep(time)
{
    return(new Promise(function(resolve, reject) {
        setTimeout(function() { resolve(); }, time);
    }));
}

function postAPI(api_url, formdata, delay_refresh=true) {
    $.ajax({
        url         : api_url,
        type        : 'POST',
        data        : formdata,
        processData : false,
        contentType : false,
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.onprogress = function(evt) {
                // console.log(evt.lengthComputable);
                // console.log(evt.loaded);
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
            // // var resp = JSON.parse(response);
            $('.message_area').css('display', 'block');
            // $('.progress_area').css('display', 'none');
            // $('#submit').css('display', 'none');
            // $('#comfirm').css('display', 'block');
            var style = (response.status == "true") ? 'color:green;' : 'color:red;';
            $('.resultmsg').html('<span class="msg" style="' + style + '">' + response.responseMessage + '</span>');
            if (response.status == "true") {
                if (delay_refresh) {
                    sleep(1000).then(function() {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            }
        }
    });
}