function changeRole(mode, id, all_fields, show_fields, need_fields, author_values, order_limit, priority) {
    var obj_role = document.getElementById(id);
    var role = obj_role.value;

    console.log("changeRole :" + role);
    while (show_fields.indexOf(';;')    !== -1) show_fields     = show_fields.replace(';;', '"');
    while (need_fields.indexOf(';;')    !== -1) need_fields     = need_fields.replace(';;', '"');
    while (author_values.indexOf(';;')  !== -1) author_values   = author_values.replace(';;', '"');
    while (order_limit.indexOf(';;')    !== -1) order_limit     = order_limit.replace(';;', '"');
    while (priority.indexOf(';;')       !== -1) priority        = priority.replace(';;', '"');
    var js_show = JSON.parse(show_fields  );
    var js_need = JSON.parse(need_fields  );
    var js_auth = JSON.parse(author_values);
    var js_olmt = JSON.parse(order_limit  );
    var js_prit = JSON.parse(priority     );
    // console.log("js_show :" + js_show); console.log("js_auth :" + js_auth);

    var js_cur_show = js_show[role];
    var js_cur_need = js_need[role];
    var js_cur_auth = js_auth[role];
    var js_cur_olmt = js_olmt[role];
    var js_cur_prit = js_prit[role];
    // console.log("js_cur_show :" + js_cur_show);
    
    // var js_show_array = js_cur_show.split(",");
    // var js_auth_array = js_cur_auth.split(",");
    var js_all_array  = all_fields.split(",");
    for (var i = 0; i < js_all_array.length; i++) {
        var cur_field = js_all_array[i];
        var obj_id = document.getElementById(cur_field);
        var lbl_id = document.getElementById("lbl_" + cur_field);
        var need_id = document.getElementById("need_" + cur_field);
        // console.log(cur_field + ":" + obj_id); console.log("lbl_" + cur_field + ":" + lbl_id);

        if (obj_id !== null) {
            obj_id.style.visibility = (js_cur_show.indexOf(cur_field) === -1) ? "hidden" : "visible";
            if (obj_id instanceof  HTMLInputElement) obj_id.value = "";
            if (lbl_id !== null) lbl_id.style.visibility = obj_id.style.visibility;
        }
        
        if (need_id !== null) need_id.innerText = (js_cur_need.indexOf(cur_field) !== -1) ? "【必填】" : "";
        if (mode === "member") {
            if (cur_field === "name") {
                if (role === "Stc")
                    lbl_id.innerHTML = "<span style=\"color:red\">【必填】</span>店家名稱";
                else
                    lbl_id.innerHTML = "<span style=\"color:red\">【必填】</span>姓名";
            }
            if (cur_field === "authorization_page") obj_id.value = js_cur_auth;
            if (cur_field === "order_limit"       ) obj_id.value = js_cur_olmt;
            if (cur_field === "priority"          ) obj_id.value = js_cur_prit;
        }
        // console.log("js_cur_need :" + js_cur_need);
    }
}
function changeRole4edit(mode, id, all_fields, show_fields) {
    var obj_role = document.getElementById(id);
    var role = obj_role.value;

    console.log("changeRole4edit :" + role);
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