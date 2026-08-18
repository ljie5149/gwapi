// ---------------------------------------------------------------------------------------------------------

window.onload = function() {
    onManualLoad();
}

onManualLoad = function() {
    console.log("window 初始化")
    var ids = ["mverify_pdf", "sverify_pdf", "product_pdf", "product2_pdf"];
    for (var i = 0; i < ids.length; i++) {
        var fileElement = document.getElementById('selfile_' + ids[i]);
        if (fileElement !== null) {
            if (typeof(FileReader) === 'undefined') {
                console.log("你的瀏覽器不支持 FileReader: " + ids[i]);
                fileElement.setAttribute('disabled', 'disabled');
            } else {
                console.log("FileReader 初始化: " + ids[i]);
                fileElement.addEventListener('change', parseForBase64, false);
                console.log("FileReader 初始化 成功");
            }
        }
    }
}

function parseForBase64() {
    var file = this.files[0];
    console.log('parseForBase64: ' + file.name);
    var id = this.name;

    // 檢查檔案類型
    if (!file.name.match('.pdf$')) {
        alert(file.name + ' 格式不正確，須為 PDF 檔！');
        this.value = ''; // 清空選擇
        return;
    }

    const reader = new FileReader();
    reader.addEventListener('load', () => {
        // PDF 預覽可用 <embed> 或 <iframe>
        const previewElement = document.getElementById('previewPDF_' + id);
        if(previewElement) {
            previewElement.setAttribute('src', reader.result);
            previewElement.setAttribute('type', 'application/pdf');
            previewElement.style.width = "100%";
            previewElement.style.height = "500px";
        }
    });
    reader.readAsDataURL(file);
}

// ---------------------------------------------------------------------------------------------------------

function drag_handler(e) {
    var id = e.target.id.replace('drop_block_', '');
    var upload_image = document.getElementById('drop_block_' + id);
    var upload_progress = document.getElementById('upload_progress_' + id);
    e.preventDefault();
    if (!upload_image.className.match('dragover')) upload_image.className += ' dragover';
    if (upload_progress.style.width != '0%') upload_progress.style.width = '0%';
}

function drop_pdf(e) {
    console.log('drop_pdf');
    var id = e.target.id.replace('drop_block_', '');
    e.preventDefault();
    var upload_image = document.getElementById('drop_block_' + id);
    var elProgress = document.getElementById('upload_progress_' + id);
    var files = e.dataTransfer.files;

    for (var i = 0; i < files.length; i++) {
        if (!files[i].name.match('.pdf$')) {
            alert(files[i].name + ' 格式不正確，須為 PDF 檔！');
            return;
        }
    }

    document.getElementById('selfile_' + id).files = files;
    updatePDF(files, id);
}

function updatePDF(files, id) {
    console.log('updatePDF');
    var fileElement = document.getElementById('previewPDF_' + id);

    if (files.length > 0) {
        const file = files[0];
        if (file.type !== "application/pdf") {
            alert("請選擇 PDF 檔案");
            return;
        }

        // 顯示檔名
        let filename_str = "";
        for (i = 0; i < files.length; i++) {
            const cur_file = files[i];
            filename_str += "\n";
            filename_str += cur_file.name;
        }
        document.getElementById("lbl_" + id).innerText = "已選擇: " + filename_str;

        // 如果要即時預覽 PDF
        const resultList = []; // 存 {name, base64}
        let pending = files.length; // 等待完成的檔案數

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            if (file.type !== "application/pdf") {
                alert("只允許 PDF 檔案: " + file.name);
                pending--;
                continue;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                resultList.push({
                    field: "pdf_url",
                    filename: file.name,
                    base64: e.target.result
                });

                pending--;

                // 當所有檔案處理完畢
                if (pending === 0) {
                    // 更新 UI
                    const names = resultList.map(f => f.filename).join("\n");
                    document.getElementById("lbl_" + id).innerText = "已選擇:\n" + names;

                    // 假設有一個 iframe 或 embed 只展示第一個 PDF
                    // const pdfPreview = document.getElementById("previewPDF_" + id + "_" + (i + 1) );
                    // if (pdfPreview && resultList.length > 0) {
                    //     pdfPreview.src = resultList[i].base64;
                    // }

                    // 這裡你就可以把 resultList 回傳 / 上傳
                    console.log("處理完成:", resultList);
                    // 轉成 JSON 字串
                    const jsonStr = JSON.stringify(resultList);
                    var pdfElement = document.getElementById('previewPDF_' + id);
                    pdfElement.value = jsonStr;
                    // console.log("jsonStr :", jsonStr);
                }
            };
            reader.readAsDataURL(file);
        }
    }
    return '';
}

function getPDFBase64(id) {
    var pdfElement = document.getElementById('previewPDF_' + id);
    return pdfElement ? pdfElement.value : '';
}
