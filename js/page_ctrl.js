function clickField(key) {
    document.cookie = key + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}

function toPage(page) {
    addParamToUrl("page", page, "");
}
function clickPage(cur_page, offset) {
    var new_page = cur_page + offset;
    addParamToUrl("page", new_page, "");
}
function changeSelect() {
    var select = document.getElementById("show_perpage");
    var selectedValue = select.value; // Get the selected value
    
    var url = mergeParamToUrl("offset", selectedValue);
    addParamToUrl("page", 1, url);
}
function urlParamCore(paramName, paramValue, url) {
    var index_Param = url.indexOf(paramName); // Check if the URL already has parameters
    let regex = new RegExp(`([?&])${paramName}=.*?(&|$)`, "i");
    
    var separator = (url.indexOf('?') !== -1) ? '&' : '?'; // Check if the URL already has parameters
    var newParam = paramName + '=' + encodeURIComponent(paramValue); // Encode the parameter value

    if (url.match(regex)) {
        url = url.replace(regex, `$1${paramName}=${paramValue}$2`);
    } else {
        url +=  separator + newParam;
    }
    return url;
}
function addParamToUrl(paramName, paramValue, url_tmp) {
    let cur_url = window.location.href; // Get the current URL
    var url = (url_tmp.length > 0) ? url_tmp : cur_url;

    url = urlParamCore(paramName, paramValue, url);

    // Append the new parameter to the URL
    window.location.href = url;
}

function mergeParamToUrl(paramName, paramValue) {
    var url = window.location.href; // Get the current URL

    url = urlParamCore(paramName, paramValue, url);

    return url;
}

function mergeParamToUrl4Edit(paramName, paramValue, url_tmp) {
    let cur_url = window.location.href; // Get the current URL
    var url = (url_tmp.length > 0) ? url_tmp : cur_url;

    url = urlParamCore(paramName, paramValue, url);
    
    return url;
}