function changeApplytype(mode, id, dst_id) {
    var obj_processing_method = document.getElementById(id);
    var processing_method = obj_processing_method.value;

    console.log("changeApplytype :" + processing_method);
    var obj_id = document.getElementById(dst_id);
    var lbl_id = document.getElementById("lbl_" + dst_id);

    let visible = (processing_method === "1") ? "hidden" : "visible";
    if (mode === "apply") {
        obj_id.style.visibility = visible;
        lbl_id.style.visibility = visible;
    }
}
function changeApplytype4edit(mode, id, all_fields, show_fields) {
    var obj_role = document.getElementById(id);
    var role = obj_role.value;

    console.log("changeApplytype4edit :" + role);
    while (show_fields.indexOf(';;')    !== -1) show_fields     = show_fields.replace(';;', '"');
    var js_show = JSON.parse(show_fields  );
    // console.log("js_show :" + js_show); console.log("js_auth :" + js_auth);

    var js_cur_show = js_show[role];
    // console.log("js_cur_show :" + js_cur_show);
    
    var js_all_array  = all_fields.split(",");
    for (var i = 0; i < js_all_array.length; i++) {
        var cur_field = js_all_array[i];
        var obj_id = document.getElementById(cur_field);
        var lbl_id = document.getElementById("lbl_" + cur_field);
        // console.log(cur_field + ":" + obj_id); console.log("lbl_" + cur_field + ":" + lbl_id);

        if (obj_id !== null) {
            obj_id.style.visibility = (js_cur_show.indexOf(cur_field) === -1) ? "hidden" : "visible";
            if (lbl_id !== null) lbl_id.style.visibility = obj_id.style.visibility;
        }
        
        if (mode === "member") {
            if (cur_field === "name") {
                if (role === "Stc")
                    lbl_id.innerText = "店家名稱";
                else
                    lbl_id.innerText = "姓名";
            }
        }
        // console.log("js_cur_need :" + js_cur_need);
    }
}