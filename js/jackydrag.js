function setExcelFile2TextArea(file) {
    if (file.name.split('.').pop() == 'xlsx' ||
        file.name.split('.').pop() == 'xls') {
        var fileReader = new FileReader();  // 創建 FileReader
        fileReader.onload = function(event) {
            var data = event.target.result;

            var workbook = XLSX.read(data, {
                type: 'binary'
            });
            var i = 0;
            var bookObject = [];
            var jsonObject = "";
            workbook.SheetNames.forEach(sheet => {
                let rowObject = XLSX.utils.sheet_to_csv(
                    workbook.Sheets[sheet]
                );
                // var sheetObject = [];
                // sheetObject["sheet_name"] = workbook.SheetNames[i];
                // sheetObject["csv_data"  ] = rowObject;
                // bookObject[i] = sheetObject;
                // var arra = rowObject.split('\n');
                // var json_rows = JSON.stringify(arra);
                while (rowObject.includes("\n")) {
                    rowObject = rowObject.replace("\n", ";;;;");
                }
                while (rowObject.includes("\"")) {
                    rowObject = rowObject.replace("\"", "");
                }
                
                jsonObject += (jsonObject.length > 0) ? "," : "[";
                jsonObject += '{"sheet_name":"'+workbook.SheetNames[i]+'", "csv_data":"'+rowObject+'"}';
                // console.log(jsonObject);
                i++;
            });
            // console.log('jsonObject' + jsonObject);
            // var ret = JSON.stringify(bookObject);
            // console.log('ret :' + JSON.stringify(bookObject));
            if (jsonObject.length > 0) jsonObject += ']';
            var fileElement = document.getElementById('tabase64');
            fileElement.innerText = jsonObject;
        };
        fileReader.readAsBinaryString(file); // Read the file as binary data
    }
}
// ---------------------------------------------------------------------------------------------------------

window.onload = function() { // 增加 檔案input 選取時的動作
    console.log("window 初始化")
    var fileElement = document.getElementById('selfile'); // 取得檔案input元件
    if (fileElement !== null) {
        if (typeof(FileReader) === 'undefined') {
            console.log("你的瀏覽器不支持 FileReader")
            fileElement.setAttribute('disabled', 'disabled');
        } else {
            console.log("FileReader 初始化")
            fileElement.addEventListener('change', parseForBase64, false);
            console.log("FileReader 初始化 成功")
        }
    }
}
onManualLoad = function() { // 增加 檔案input 選取時的動作
    console.log("window 初始化")
    var fileElement = document.getElementById('selfile'); // 取得檔案input元件
    if (fileElement !== null) {
        if (typeof(FileReader) === 'undefined') {
            console.log("你的瀏覽器不支持 FileReader")
            fileElement.setAttribute('disabled', 'disabled');
        } else {
            console.log("FileReader 初始化")
            fileElement.addEventListener('change', parseForBase64, false);
            console.log("FileReader 初始化 成功")
        }
    }
}
function parseForBase64() { // 檔案轉base64
    var file = this.files[0];     // 取得文件來源
    console.log('parseForBase64 :' + file);
    if (file.name.split('.').pop() == 'xlsx' ||
        file.name.split('.').pop() == 'xls' ||
        file.name.split('.').pop() == 'pdf') {
        
        var fileElement = document.getElementById('tabase64');
        console.log(fileElement);
        setExcelFile2TextArea(file);

    } else {
        const reader = new FileReader();
        reader.addEventListener('load', () => {
                const fileElement = document.getElementById('previewImage');
                fileElement.setAttribute('src', reader.result);
            // imagePreview.innerHTML = `<img src="${reader.result}" alt="Preview">`;
        });
        reader.readAsDataURL(file);
    }
}
// ---------------------------------------------------------------------------------------------------------

function drag_handler(e) 
{
    var upload_image = document.getElementById('drop_block'     );
    var elProgress   = document.getElementById('upload_progress');
    e.preventDefault();  //防止瀏覽器執行預設動作
    if (!upload_image.className.match('dragover')) upload_image.className = upload_image.className + ' dragover';
    if (upload_progress.style.width       != '0%') upload_progress.style.width = '0%';
}
function drop_file(e) 
{
    e.preventDefault();
    var upload_image     = document.getElementById('drop_block'      );
    var elProgress       = document.getElementById('upload_progress' );
    var images_container = document.getElementById('images_container');
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
    for (var i = 0; i < files.length; i++) {
        var tmppath = URL.createObjectURL(files[i]);
        console.log(files[i]);
        // console.log("name: "+files[i].name);
        // console.log("size: "+files[i].size);
        // console.log("type: "+files[i].type);
        if (!files[i].name.match('.xlsx') && !files[i].name.match('.xls')) {
            var name = files[i].name;
            alert(name + '格式不正確，須為Excel檔！');
            return;
            // continue;
        }
    }
    document.getElementById('selfile').files = files;
    updateBase64toTextarea(files);
    // objXhr.send(objForm);
}
function updateBase64toTextarea(srcfiles) { // drag檔案之後的動作
    var fileElement = document.getElementById('tabase64'); // 取得檔案input元件
    const file = srcfiles[0]; // 取得文件來源
  
    if (file) {
        setExcelFile2TextArea(file);
    } else {
        fileElement.innerText = ""; // Reset to original image if user cancels
    }
}
function getFileBase64() { // 檔案轉base64
    var fileElement = document.getElementById('selfile'); // 取得檔案input元件
    var tabase64Element = document.getElementById('tabase64'); // 取得 暫存base64 textarea元件
    var files = fileElement.files;
    return (files == undefined || files.length == 0) ? '' : tabase64Element.innerText;     // 取得文件來源
}
// ---------------------------------------------------------------------------------------------------------

function drop_graph(e) 
{
    e.preventDefault();
    var upload_image     = document.getElementById('drop_block'      );
    var elProgress       = document.getElementById('upload_progress' );
    var images_container = document.getElementById('images_container');
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
        
        if (!files[i].name.match('.pdf') &&
            !files[i].name.match('.bmp') &&
            !files[i].name.match('.gif') &&
            !files[i].name.match('.png') &&
            !files[i].name.match('.jpg') &&
            !files[i].name.match('.jpeg')) {
            alert(name + '格式不正確，須為圖片檔或pdf檔！');
            return;
            // continue;
        }
        break;
    }
    document.getElementById('selfile').files = files;
    updateImage(files);
    // objXhr.send(objForm);
}
function updateImage(srcfiles) { // drag檔案之後的動作
    var fileElement = document.getElementById('previewImage'); // 取得檔案input元件
    const file = srcfiles[0]; // 取得文件來源
  
    if (file) {
        const reader = new FileReader(); // 創建 FileReader
        reader.readAsDataURL(file); //載入文件
        reader.onload = function(e) {
            fileElement.style.width  = "80%";
            fileElement.style.height = "50%";
            fileElement.setAttribute('src', this.result);
            // console.log(this.result);
        }
    } else {
        // Reset to original image if user cancels
        previewImage.setAttribute('src', "");
    }
    return '';
}
function getPreviewImageBase64() { // 檔案轉base64
    var graphElement = document.getElementById('previewImage'); // 取得檔案input元件
    return graphElement.getAttribute('src');     // 取得文件來源
}
function getImageBase64() { // 檔案轉base64
    var fileElement = document.getElementById('selfile'); // 取得檔案input元件
    var graphElement = document.getElementById('previewImage'); // 取得檔案input元件
    var files = fileElement.files;
    return (files == undefined || files.length == 0)? '' : graphElement.getAttribute('src');     // 取得文件來源
}