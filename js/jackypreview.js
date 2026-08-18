
function getPreviewContent(mode, fields, fldidx_name, fldidx_show) {
    var result = "";
    result += "<table>";
    if (mode == "news" || mode == "banner" || mode == "fcm") {
        for (var i = 0; i < fields.length; i++) {
            var js_field = fields[i];
            if (js_field[fldidx_show] === 'true') { // show == true
                var obj_id = "";
                var js_fieldname = js_field[fldidx_name]; // field_name
                if (js_fieldname == 'file_path') {
                    // nothing to do
                } else if (js_fieldname == 'file_content') {
                    // result += '<img src="' + getPreviewImageBase64() + '" />';
                } else
                    obj_id = js_fieldname;
                if (obj_id != '') {
                    var fieldElement = document.getElementById(obj_id);
                    var val = fieldElement.value;
                    if (js_fieldname == 'message_kind') {
                        result += '<tr><th><h5><span class="badge badge-success">' + val + '</span></h5></th></tr>';
                    } else if (js_fieldname == 'title') {
                        result += '<tr><th><h3>' + val + '</h3></th></tr>';
                    } else if (js_fieldname == 'summary') {
                        result += '<tr><td style="font-weight: bold;">' + val + '</td></tr>';
                    } else if (js_fieldname == 'content') {
                        result += '<tr><td><img src="' + getPreviewImageBase64() + '" /></td><tr>';
                        result += '<tr><td style="vertical-align:top;"><span>'+ val + '</span></td></tr>';
                    }
                }
            }
        }
    }
    result += "</table>";
    return result;
}
function closePreview() {
    document.getElementById('viewer').style.display = 'none';
    // $('.viewer_preview').css('display', 'none');
}