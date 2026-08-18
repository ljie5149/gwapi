// $(window).on("resize", resizeGrid);
function resizeGrid() {
    // var frame_height = parseInt(mygetCookie("frameheight"));
    // var frame_width  = parseInt(mygetCookie("framewidth"));
    // mydeleteCookie("frameheight");
    // mydeleteCookie("framewidth");
    const myTableHead = document.getElementById('myTableHead');
    const tableHeadHeight = myTableHead.offsetHeight;
    const newHeight = window.innerHeight - tableHeadHeight - 80;
    const tableHeadWidth = myTableHead.offsetWidth;
    const newWidth = window.innerWidth - 90;
    // alert(newHeight + "," + tableHeadHeight);
    //重新抓jqGrid容器的新height

    // console.log("frame_height :" + frame_height.toString() + "," + frame_width.toString());
    // console.log("newHeight :" + newHeight.toString() + "," + newWidth.toString());
    document.getElementById('myTableBody').style.height = newHeight;
    // document.getElementById('myTableBody').style.width  = newWidth ;
    // mysetCookie("frameheight", newHeight, 1);
    // mysetCookie( "framewidth",  newWidth, 1);
    $('body').addClass('active');
}

function clickField(val, flag) {
    var url = "";
    url = mergeParamToUrl("sort", val, "");
    addParamToUrl("sort_flag", (flag == undefined) ? "0" : flag, url);
}