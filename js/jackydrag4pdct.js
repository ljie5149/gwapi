
// ---------------------------------------------------------------------------------------------------------

window.onload = function() { // 增加 檔案input 選取時的動作
    onManualLoad();
}
onManualLoad = function() { // 增加 檔案input 選取時的動作
    console.log("window 初始化")
    var ids = ["mverify_img", "sverify_img", "product_img", "product2_img", "select_img", "setup_img", "size_img", "extra_img"];
    for (var i = 0; i < ids.length; i++) {
        var fileElement = document.getElementById('selfile_' + ids[i]); // 取得檔案input元件
        if (fileElement !== null) {
            if (typeof(FileReader) === 'undefined') {
                console.log("你的瀏覽器不支持 FileReader" + ids[i])
                fileElement.setAttribute('disabled', 'disabled');
            } else {
                console.log("FileReader 初始化" + ids[i]);
                fileElement.addEventListener('change', parseForBase64, false);
                console.log("FileReader 初始化 成功")
            }
        }
    }
}
function parseForBase64() { // 檔案轉base64
    var file = this.files[0];     // 取得文件來源
    console.log('parseForBase64 :' + file.name);
    var id = this.name;
    
    const reader = new FileReader();
    reader.addEventListener('load', () => {
        const fileElement = document.getElementById('previewImage_' + id);
        fileElement.setAttribute('src', reader.result);
    });
    reader.readAsDataURL(file);
}
// ---------------------------------------------------------------------------------------------------------

function drag_handler(e) 
{
    var id = e.target.id;
    id = id.replace('drop_block_', '');
    var upload_image = document.getElementById('drop_block_'      + id);
    var upload_progress   = document.getElementById('upload_progress_' + id);
    e.preventDefault();  //防止瀏覽器執行預設動作
    if (!upload_image.className.match('dragover')) upload_image.className = upload_image.className + ' dragover';
    if (upload_progress.style.width       != '0%') upload_progress.style.width = '0%';
}
// ---------------------------------------------------------------------------------------------------------

function drop_graph(e) 
{
    console.log('drop_graph');
    var id = e.target.id;
    id = id.replace('drop_block_', '');
    e.preventDefault();
    var upload_image     = document.getElementById('drop_block_'       + id);
    var elProgress       = document.getElementById('upload_progress_'  + id);
    var images_container = document.getElementById('images_container_' + id);
    var objXhr           = new XMLHttpRequest();
    var files            = e.dataTransfer.files; // FileList object
    var objForm          = new FormData();
    var sucess_count     = 0;

    objXhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            var intComplete = (e.loaded / e.total) * 100 | 0;
            elProgress.innerHTML    = intComplete + '%';
            elProgress.style.width  = intComplete + '%';
            elProgress.setAttribute('aria-valuenow', intComplete);
        }
    }

    objXhr.onload = function(e) {
        upload_image.className  = upload_image.className.replace(' dragover', '');
        elProgress.className    = elProgress.className.replace(' active', '');
        alert(objXhr.responseText); //接收網頁回傳結果
    }

    // objXhr.open('POST', 'TestUpload.aspx');
    var tmppath = "", name = "";
    for (var i = 0; i < files.length; i++) {
        tmppath = URL.createObjectURL(files[i]);
        // console.log(files[i]);
        // console.log("name: "+files[i].name);
        // console.log("size: "+files[i].size);
        // console.log("type: "+files[i].type);
        name = files[i].name;
        if (!files[i].name.match('.bmp') &&
            !files[i].name.match('.gif') &&
            !files[i].name.match('.png') &&
            !files[i].name.match('.jpg') &&
            !files[i].name.match('.jpeg')) {
            alert(name + '格式不正確，須為圖片檔！');
            return;
            // continue;
        }
        break;
    }
    document.getElementById('selfile_' + id).files = files;
    updateImage(files, id);
    // objXhr.send(objForm);
}
function updateImage(files, id) { // drag檔案之後的動作
    console.log('updateImage');
    var obj_name = 'previewImage_' + id;
    console.log(obj_name);
    var fileElement = document.getElementById(obj_name); // 取得檔案input元件
    const file = files[0]; // 取得文件來源
  
    if (file) {
        const reader = new FileReader(); // 創建 FileReader
        reader.readAsDataURL(file); //載入文件
        reader.onload = function(e) {
            fileElement.setAttribute('src', this.result);
            fileElement.style.width = "64px";
            fileElement.style.height = "64px";
            // console.log(this.result);
        }
    } else {
        // Reset to original image if user cancels
        fileElement.setAttribute('src', "");
    }
    return '';
}
function getPreviewImageBase64(id) { // 檔案轉base64
    var graphElement = document.getElementById('previewImage_' + id); // 取得檔案input元件
    return graphElement.getAttribute('src');     // 取得文件來源
}
function getImageBase64(id) { // 檔案轉base64
    var fileElement = document.getElementById('selfile_' + id); // 取得檔案input元件
    var graphElement = document.getElementById('previewImage_' + id); // 取得檔案input元件
    var files = fileElement.files;
    // console.log(fileElement);
    // console.log(graphElement);
    return (files == undefined || files.length == 0)? '' : graphElement.getAttribute('src');     // 取得文件來源
}