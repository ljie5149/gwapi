$(document).ready(function () {
    'use strict';

    setTitle('下載');

    if (isAndroid()) {
        var oFormData = 15;
    } else if (isIOS()) {
        var oFormData = 16;
    }

    ExecuteAPI_Async('Sys', 'GetList', oFormData,
        function (response) {
            //console.log(response);
            if (!!response.Data) {
                goPage(response.Data.sys_content);
            }
        });
});