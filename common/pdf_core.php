<?php
    require_once __DIR__ . '/../vendor/autoload.php'; // 如果是 composer
    // require_once __DIR__ . '/../tcpdf_min/tcpdf.php'; // 沒有 composer 的寫法

    function savePdf($src_data, $attachmentfarmers_filename, $attachment01_filename, $attachment02_filename, $attachment03_filename, $attachment04_filename, $signature_file, $root_path, $path, $uid, $file_name, $file_title="undef") {
        $applyform_name         = isset($src_data['applyform_name'          ]) ? $src_data['applyform_name'     ] : ''   ;
        $receipt_name           = isset($src_data['receipt_name'            ]) ? $src_data['receipt_name'       ] : ''   ;
        $receipt_idno           = isset($src_data['receipt_idno'            ]) ? $src_data['receipt_idno'       ] : ''   ;
        $receipt_address        = isset($src_data['receipt_address'         ]) ? $src_data['receipt_address'    ] : ''   ;
        $receipt_phone          = isset($src_data['receipt_phone'           ]) ? $src_data['receipt_phone'      ] : ''   ;

        $applicant_name         = isset($src_data['applicant_name'          ]) ? $src_data['applicant_name'     ] : ''   ;
        $applicant_phone        = isset($src_data['applicant_phone'         ]) ? $src_data['applicant_phone'    ] : ''   ;
        $applicant_id           = isset($src_data['applicant_id'            ]) ? $src_data['applicant_id'       ] : ''   ;
        $applicant_mobile       = isset($src_data['applicant_mobile'        ]) ? $src_data['applicant_mobile'   ] : ''   ;
        $applicant_address      = isset($src_data['applicant_address'       ]) ? $src_data['applicant_address'  ] : ''   ;
        $receive_type           = isset($src_data['receive_type'            ]) ? $src_data['receive_type'       ] : ''   ;
        $require_docs           = isset($src_data['require_docs'            ]) ? $src_data['require_docs'       ] : ''   ;

        $baby_name              = isset($src_data['baby_name'               ]) ? $src_data['baby_name'          ] : ''   ;
        $baby_birth             = isset($src_data['baby_birth'              ]) ? $src_data['baby_birth'         ] : ''   ;
        $baby_id                = isset($src_data['baby_id'                 ]) ? $src_data['baby_id'            ] : ''   ;
        $baby_order             = isset($src_data['baby_order'              ]) ? $src_data['baby_order'         ] : ''   ;
        $baby_address           = isset($src_data['baby_address'            ]) ? $src_data['baby_address'       ] : ''   ;

        $pdf_dir = $root_path.$path;
        if (!file_exists($pdf_dir)) mkdir($pdf_dir, 0777, true);
        /**************** 在這裡加上產生 PDF ****************/
        $font_name = 'msungstdlight'; // msungstdlight, kaiu

        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nantou City Office');
        $pdf->SetTitle('生育補助申請');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->SetFont($font_name, '', 12);
        $pdf->setPrintHeader(false); // 關閉 header
        $pdf->setPrintFooter(false); // 關閉 footer

        // --------- 第一頁 收據 ---------
        $pdf->AddPage();
        
        // 外框
        $pdf->SetLineWidth(0.8); // 設定線寬 (單位 mm，可調整)
        $pdf->Rect(10, 10, 190, 270 * 6 / 10); // x, y, w, h
        $pdf->SetLineWidth(0.2); // 畫完外框後再恢復預設線寬

        // ===== 收據內容 =====
        $pdf->Ln(1);
        $pdf->SetFont($font_name, '', 22);
        $pdf->Cell(0, 10, '收 據', 0, 1, 'C');

        $pdf->Ln(5);
        $pdf->SetFont($font_name, '', 14);
        $pdf->MultiCell(0, 10, '茲 向南投市公所領到 南投縣南投市生育獎勵金', 0, 'L');

        $pdf->Ln(2);
        $pdf->SetFont($font_name, '', 16);
        $pdf->Cell(0, 10, '新台幣　參萬元整', 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->SetFont($font_name, '', 14);
        $pdf->Cell(0, 10, '上款確實如數領訖此據', 0, 1, 'L');

        $pdf->Ln(10);
        $pdf->Cell(0, 10, getEmptyRocDate(), 0, 1, 'L');

        // ===== 具領人 =====
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "具　領　人：{$receipt_name}", 0, 1, 'L');
            // 記錄目前 Y 座標，方便插入簽名
            $y = $pdf->GetY();

            // 插入簽名圖片 (寬 40mm，高自動)
            if (!empty($signature_file) && file_exists($root_path.$signature_file)) {
                $pdf->Image($root_path.$signature_file, 100, $y - 8, 40, 0, 'PNG');
            }

        $pdf->Ln(5);
        $pdf->Cell(0, 10, "身分證統一編號：{$receipt_idno}", 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "住　　址：{$receipt_address}", 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "電　　話：{$receipt_phone}", 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->SetFont($font_name, '', 20); // 放大字體
        $pdf->Cell(0, 10, '南投市公所 台照', 0, 1, 'L'); // L = 靠左

        // --------- 第二頁 申請表 ---------
        $pdf->AddPage();
        

        // 新生兒地址選擇
        $dst_baby_addr = "■同申請人<BR>&nbsp;&nbsp;□其他：<BR>";
        if (strpos($baby_address, '其他') !== false) {
            $dst_baby_addr = '□同申請人<BR>&nbsp;&nbsp;■'.$baby_address.'<BR>';
        }


        // ===== 領取方式 =====
        $dst_receive_type = '匯入南投市農會帳戶【免手續費】<BR>匯入郵局或其他金融行庫帳戶【內扣30元手續費】';
        // if (strpos($receive_type, '郵局或其他金融存摺') !== false) {
        //     $dst_receive_type = '□1. 臨櫃：匯入南投市農會帳戶【免手續費】<BR>■2. 郵局或其他金融存摺【自付30元手續費】';
        // }

        // 轉成陣列
        $docs = explode(';', $require_docs); // 結果 ['1','2','3','4']
        $doc_names = [
            '1' => '新生兒出生證明',
            '2' => '父母雙方身分證（居留證）正反面',
            '3' => '父母雙方及新生兒戶籍謄本（現戶全戶）或戶口名簿【記事欄不省略】',
            '4' => '存摺封面'
        ];
        $doc_select = "";
        foreach ($doc_names as $key => $name) {
            if (in_array($key, $docs)) {
                $doc_select .= "■ {$name}<br>";
            } else {
                $doc_select .= "□ {$name}<br>";
            }
        }
        $ToDay = getRocDate();
        $html = <<<HTML
            <h2 style="text-align:center;">南投縣南投市生育補助(線上辦理)申請表</h2>
            <div style="text-align:right; margin-bottom:10px; font-size:14px;">
                申請日期：$ToDay
            </div>
                <!-- 申請人資料 -->
                <table border="1" cellpadding="2" cellspacing="0" style="border-collapse:collapse; width:800px;font-size:14px;">
                    <tr>
                        <th rowspan="4" style="writing-mode: vertical-rl; text-align:center; width:40px;">&nbsp;申&nbsp;請&nbsp;人&nbsp;資&nbsp;料</th>
                        <td style="width:120px;">姓名</td>
                        <td style="width:120px;">{$applicant_name}</td>
                        <td rowspan="2" style="writing-mode: vertical-rl; text-align:center; width:40px;">電話</td>
                        <td style="width:80px;">市話</td>
                        <td style="width:120px;">{$applicant_phone}</td>
                    </tr>
                    <tr>
                        <td>身分證字號</td>
                        <td>{$applicant_id}</td>
                        <td>手機</td>
                        <td>{$applicant_mobile}</td>
                    </tr>
                    <tr>
                        <td rowspan="2" style="height: 49px;">戶籍地</td> <!-- 這裡讓「戶籍地」佔兩列 -->
                        <td rowspan="2" colspan="4" style="height: 49px;">{$applicant_address}</td>
                    </tr>
                </table>

                <!-- 新生兒資料 -->
                <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:800px; font-size:14px;">
                    <tr>
                        <th rowspan="5" style="writing-mode: vertical-rl; text-align:center; width:40px;">&nbsp;新&nbsp;生&nbsp;兒&nbsp;資&nbsp;料</th>
                        <td style="width:120px;">姓名</td>
                        <td style="width:120px;">{$baby_name}</td>
                        <td style="width:120px;">出生日期</td>
                        <td style="width:120px;">{$baby_birth}</td>
                    </tr>
                    <tr>
                        <td>身分證字號</td>
                        <td>{$baby_id}</td>
                        <td>胎次</td>
                        <td>第{$baby_order}胎</td>
                    </tr>
                    <tr>
                        <td>戶籍地</td>
                        <td colspan="3">
                            {$dst_baby_addr}
                        </td>
                    </tr>
                </table>

                <!-- 領取方式 -->
                <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:750px; font-size:14px;">
                    <tr>
                        <td rowspan="3" style="writing-mode: vertical-rl; text-align:center; width:40px;">領取方式</td>
                        <td style="width:479.5px;">{$dst_receive_type}</td>
                    </tr>
                </table>

                <!-- 應備文件 -->
                <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:750px; font-size:14px;">
                    <tr>
                        <td rowspan="5" style="writing-mode: vertical-rl; text-align:center; width:40px;">應備文件</td>
                        <td style="width:479.5px;">{$doc_select}</td>
                    </tr>
                </table>
        HTML;
        $pdf->WriteHTML($html);

        // ===== 切結條款 =====
        $pdf->SetFont($font_name, '', 12); // 調整字體大小為 10
        $pdf->MultiCell(0, 8, '*本人已閱讀並了解本申請表各節，保證上述所填各項資料及所附文件均為真實，並知悉提供不實資料及違反相關法令之後果，若有可歸責於己之事由，除繳回所領金額並自負一切法律責任。', 0, 'L');
        $pdf->Ln(1);
        $pdf->Cell(0, 8, '此致', 0, 1, 'L');
        $pdf->Cell(0, 8, '        南投市公所', 0, 1, 'L');

        // ===== 簽名區 =====
        $pdf->Ln(5);
        $pdf->Cell(0, 8, '申請人(簽名)：', 0, 1, 'L');

        // 記錄目前 Y 座標，方便插入簽名
        $y = $pdf->GetY();

        // 插入簽名圖片 (寬 40mm，高自動)
        if (!empty($signature_file) && file_exists($root_path.$signature_file)) {
            $pdf->Image($root_path.$signature_file, 50, $y - 8, 40, 0, 'PNG');  
        }

        // ===== 審核區 =====
        $pdf->Ln(2);
        $pdf->Cell(0, 8, '審核結果：□符合：核發新台幣三萬元整', 0, 1, 'L');
        $pdf->Cell(0, 8, '□不符合：□1. 補助對象不符 □2. 申請期限超過 □3. 檢附文件不符 □4. 其他', 0, 1, 'L');

        // ===== 承辦人簽核表格 =====
        $pdf->Ln(2);
        $pdf->Cell(20, 12, '承辦人', 1, 0, 'C');
        $pdf->Cell(40, 12, '', 1, 0, 'C');
        $pdf->Cell(20, 12, '財政課', 1, 0, 'C');
        $pdf->Cell(40, 12, '', 1, 0, 'C');
        $pdf->Cell(20, 12, '主任秘書', 1, 0, 'C');
        $pdf->Cell(40, 12, '', 1, 1, 'C');

        $pdf->Cell(20, 12, '課長', 1, 0, 'C');
        $pdf->Cell(40, 12, '', 1, 0, 'C');
        $pdf->Cell(20, 12, '主計室', 1, 0, 'C');
        $pdf->Cell(40, 12, '', 1, 0, 'C');
        $pdf->Cell(20, 12, '市長', 1, 0, 'C');
        $pdf->Cell(40, 12, '', 1, 1, 'C');

        // ===== 右下角版本號 =====
        $pdf->Ln(10);
        $pdf->SetFont($font_name, '', 10);
        $pdf->Cell(0, 8, '11403修訂', 0, 1, 'R');

        // ===== 附件頁面 =====
        $attachments = [
            ['file' => $attachmentfarmers_filename, 'title' => ''],
            ['file' => $attachment01_filename, 'title' => '附件：新生兒出生證明'],
            ['file' => $attachment02_filename, 'title' => '附件：父母雙方身分證（居留證）正反面'],
            ['file' => $attachment03_filename, 'title' => '附件：父母雙方及新生兒戶籍謄本（現戶全戶）或戶口名簿'],
            ['file' => $attachment04_filename, 'title' => '附件：存摺封面']
        ];

        foreach ($attachments as $att) {
            if (!empty($att['file'])) {
                // 核心修改：使用分號拆解多個路徑
                $file_array = str2array($att['file'], ';');
                foreach ($file_array as $index => $single_file) {
                    $full_path = $root_path.$single_file;
                    if (!empty($single_file) && file_exists($full_path)) {
                        // --- 縮圖優化開始 ---
                        $temp_img = $root_path . 'images/tmp/temp_resize_' . uniqid() . '.jpg';
                        // echo $temp_img."\n";
                        $is_resized = resizeImageGD($full_path, $temp_img, 1200); // 限制寬度最大 1200px
                        
                        // 決定要使用的圖片路徑（成功縮圖就用暫存檔，失敗就用原圖）
                        $target_path = ($is_resized) ? $temp_img : $full_path;
                        // --- 縮圖優化結束 ---

                        $pdf->AddPage();
                        $pdf->SetFont($font_name, '', 18);
                        // 標題標記：如果是多檔，可以在標題後加上序號 (如：附件-1)
                        $display_title = $att['title'];
                        if (count($file_array) > 1) {
                            $display_title .= ' (' . ($index + 1) . ')';
                        }
                        $pdf->Cell(0, 10, $display_title, 0, 1, 'C');
                        $pdf->Ln(5);
                        $y1 = $pdf->GetY();
                        // 繪製圖片
                        // 參數說明：x=30, y=$y1, w=150, h=0(自動比率)
                        $pdf->Image($target_path, 30, $y1, 150, 0, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
                    }
                }
            }
        }

		$filename = $file_title.$uid.'.pdf';
        // 儲存 PDF
        $pdf_file = $pdf_dir. $filename;
        $pdf->Output($pdf_file, 'F'); // 存檔到伺服器

        // 可以把 PDF 檔案路徑回傳給前端
		return $path.$filename;
        /**************************************************/
    }
    /**
     * 簡易 GD 縮圖函數
     * @param string $source 來源路徑
     * @param string $dest 儲存路徑
     * @param int $maxWidth 最大寬度
     */
    function resizeImageGD($source, $dest, $maxWidth = 1200) {
        list($width, $height, $type) = getimagesize($source);
        
        // 如果原圖寬度已經小於標準，就不縮圖節省資源
        if ($width <= $maxWidth) return false;

        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = $height * $ratio;

        // 建立畫布
        $dst_img = imagecreatetruecolor($newWidth, $newHeight);
        
        // 根據格式讀取原圖
        switch ($type) {
            case IMAGETYPE_JPEG: $src_img = imagecreatefromjpeg($source); break;
            case IMAGETYPE_PNG:  $src_img = imagecreatefrompng($source); break;
            case IMAGETYPE_GIF:  $src_img = imagecreatefromgif($source); break;
            default: return false;
        }

        // 重新採樣複製 (這一步最耗記憶體，但會讓圖片變小)
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // 輸出成 JPG (品質設定 75-80 就很夠看了)
        imagejpeg($dst_img, $dest, 80);

        // 釋放記憶體
        imagedestroy($dst_img);
        imagedestroy($src_img);
        
        return true;
    }
    function savePdf_ori($src_data, $signature_file, $root_path, $path, $uid, $file_name, $file_title="undef") {
        $applyform_name         = isset($src_data['applyform_name'          ]) ? $src_data['applyform_name'     ] : ''   ;
        $receipt_name           = isset($src_data['receipt_name'            ]) ? $src_data['receipt_name'       ] : ''   ;
        $receipt_idno           = isset($src_data['receipt_idno'            ]) ? $src_data['receipt_idno'       ] : ''   ;
        $receipt_address        = isset($src_data['receipt_address'         ]) ? $src_data['receipt_address'    ] : ''   ;
        $receipt_phone          = isset($src_data['receipt_phone'           ]) ? $src_data['receipt_phone'      ] : ''   ;

        $applicant_name         = isset($src_data['applicant_name'          ]) ? $src_data['applicant_name'     ] : ''   ;
        $applicant_phone        = isset($src_data['applicant_phone'         ]) ? $src_data['applicant_phone'    ] : ''   ;
        $applicant_id           = isset($src_data['applicant_id'            ]) ? $src_data['applicant_id'       ] : ''   ;
        $applicant_mobile       = isset($src_data['applicant_mobile'        ]) ? $src_data['applicant_mobile'   ] : ''   ;
        $applicant_address      = isset($src_data['applicant_address'       ]) ? $src_data['applicant_address'  ] : ''   ;
        $receive_type           = isset($src_data['receive_type'            ]) ? $src_data['receive_type'       ] : ''   ;

        $baby_name              = isset($src_data['baby_name'               ]) ? $src_data['baby_name'          ] : ''   ;
        $baby_birth             = isset($src_data['baby_birth'              ]) ? $src_data['baby_birth'         ] : ''   ;
        $baby_id                = isset($src_data['baby_id'                 ]) ? $src_data['baby_id'            ] : ''   ;
        $baby_order             = isset($src_data['baby_order'              ]) ? $src_data['baby_order'         ] : ''   ;
        $baby_address           = isset($src_data['baby_address'            ]) ? $src_data['baby_address'       ] : ''   ;

        $pdf_dir = $root_path.$path;
        if (!file_exists($pdf_dir)) mkdir($pdf_dir, 0777, true);
        /**************** 在這裡加上產生 PDF ****************/
        $font_name = 'msungstdlight'; // msungstdlight, kaiu

        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nantou City Office');
        $pdf->SetTitle('生育補助申請');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->SetFont($font_name, '', 12);
        $pdf->setPrintHeader(false); // 關閉 header
        $pdf->setPrintFooter(false); // 關閉 footer

        // --------- 第一頁 收據 ---------
        $pdf->AddPage();
        
        // 外框
        $pdf->SetLineWidth(0.8); // 設定線寬 (單位 mm，可調整)
        $pdf->Rect(10, 10, 190, 270 * 6 / 10); // x, y, w, h
        $pdf->SetLineWidth(0.2); // 畫完外框後再恢復預設線寬

        // ===== 收據內容 =====
        $pdf->Ln(1);
        $pdf->SetFont($font_name, '', 22);
        $pdf->Cell(0, 10, '收 據', 0, 1, 'C');

        $pdf->Ln(5);
        $pdf->SetFont($font_name, '', 14);
        $pdf->MultiCell(0, 10, '茲 向南投市公所領到 南投縣南投市生育獎勵金', 0, 'L');

        $pdf->Ln(2);
        $pdf->SetFont($font_name, '', 16);
        $pdf->Cell(0, 10, '新台幣　參萬元整', 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->SetFont($font_name, '', 14);
        $pdf->Cell(0, 10, '上款確實如數領訖此據', 0, 1, 'L');

        $pdf->Ln(10);
        $pdf->Cell(0, 10, getRocDate(), 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->Cell(0, 10, "具　領　人：{$receipt_name}", 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "身分證統一編號：{$receipt_idno}", 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "住　　址：{$receipt_address}", 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "電　　話：{$receipt_phone}", 0, 1, 'L');
        $pdf->Ln(5);
        $pdf->SetFont($font_name, '', 20); // 放大字體
        $pdf->Cell(0, 10, '南投市公所 台照', 0, 1, 'L'); // L = 靠左

        // --------- 第二頁 申請表 ---------
        $pdf->AddPage();
        // 畫框線用
        function drawBox($pdf, $x, $y, $w, $h) {
            $pdf->Rect($x, $y, $w, $h);
        }

        // ===== 標題 =====
        $pdf->SetFont($font_name, '', 16);
        $pdf->Cell(0, 10, '南投縣南投市生育補助(線上辦理)申請表', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont($font_name, '', 12);
        $pdf->Cell(0, 8, '申請日期：　　　年　　月　　日', 0, 1, 'R');

        // ===== 申請人資料 =====
        $pdf->SetFont($font_name, '', 12);
        $pdf->Cell(0, 8, '申請人資料', 1, 1, 'L');
        $pdf->Cell(30, 8, '姓名', 1, 0, 'C');
        $pdf->Cell(60, 8, $applicant_name, 1, 0, 'L');
        $pdf->Cell(30, 8, '電話', 1, 0, 'C');
        $pdf->Cell(60, 8, "市話：{$applicant_phone}             手機：{$applicant_mobile}", 1, 1, 'L');

        $pdf->Cell(30, 8, '身分證字號', 1, 0, 'C');
        $pdf->Cell(150, 8, $applicant_id, 1, 1, 'L');

        $pdf->Cell(30, 8, '戶籍地', 1, 0, 'C');
        $pdf->Cell(150, 8, $applicant_address, 1, 1, 'L');

        // ===== 新生兒資料 =====
        $pdf->Cell(0, 8, '新生兒資料', 1, 1, 'L');
        $pdf->Cell(30, 8, '姓名', 1, 0, 'C');
        $pdf->Cell(60, 8, $baby_name, 1, 0, 'L');
        $pdf->Cell(30, 8, '出生日期', 1, 0, 'C');
        $pdf->Cell(60, 8, $baby_birth, 1, 1, 'L');

        $pdf->Cell(30, 8, '身分證字號', 1, 0, 'C');
        $pdf->Cell(60, 8, $baby_id, 1, 0, 'L');
        $pdf->Cell(30, 8, '胎次', 1, 0, 'C');
        $pdf->Cell(60, 8, "第 {$baby_order} 胎", 1, 1, 'L');

        $pdf->Cell(30, 8, '戶籍地', 1, 0, 'C');
        $dst_baby_addr = "■同申請人　□其他：";
        if (strpos($baby_address, '其他') !== false) {
            $dst_baby_addr = '□同申請人　■'.$baby_address;
        }
        $pdf->Cell(150, 8, $dst_baby_addr, 1, 1, 'L');

        // ===== 領取方式 =====
        $dst_receive_type = '■1. 臨櫃：由投市農會帳戶【免手續費】
        □2. 郵局或其他金融存摺【自付30元手續費】';
        if (strpos($receive_type, '郵局或其他金融存摺') !== false) {
            $dst_receive_type = '□1. 臨櫃：由投市農會帳戶【免手續費】
        ■2. 郵局或其他金融存摺【自付30元手續費】';
        }
        $pdf->Ln(3);
        $pdf->Cell(0, 8, '領取方式：', 0, 1, 'L');
        $pdf->MultiCell(0, 8, $dst_receive_type, 0, 'L');

        // ===== 應備文件 =====
        $pdf->Ln(2);
        $pdf->Cell(0, 8, '應備文件：', 0, 1, 'L');
        $pdf->MultiCell(0, 8, '1. 新生兒出生證明
        2. 父母雙方身分證（影印本）正反面
        3. 父母雙方及新生兒戶籍謄本（現戶戶口名簿）或戶口名簿【記事欄需有註記】
        4. 存摺封面', 0, 'L');

        // ===== 切結條款 =====
        $pdf->Ln(2);
        $pdf->MultiCell(0, 8, '*本人已閱讀並了解本申請表各節，願誠實填報與檢具所附文件均為真實，若提供不實資料或逾相關法令規定者，均同意取消補助及追償責任。', 0, 'L');
        $pdf->Ln(3);
        $pdf->Cell(0, 8, '此致', 0, 1, 'R');
        $pdf->Cell(0, 8, '南投市公所', 0, 1, 'R');

        // ===== 簽名區 =====
        $pdf->Ln(5);
        $pdf->Cell(0, 8, '申請人(簽名)：', 0, 1, 'L');

        // 記錄目前 Y 座標，方便插入簽名
        $y = $pdf->GetY();

        // 插入簽名圖片 (寬 40mm，高自動)
        if (!empty($signature_file) && file_exists($root_path.$signature_file)) {
            $pdf->Image($root_path.$signature_file, 50, $y - 8, 40, 0, 'PNG');  
        }

        // ===== 審核區 =====
        $pdf->Ln(8);
        $pdf->Cell(0, 8, '審核結果：□符合：核發新台幣三萬元整', 0, 1, 'L');
        $pdf->Cell(0, 8, '□不符合：□1. 補助對象不符 □2. 申請期限超過 □3. 檢附文件不符 □4. 其他', 0, 1, 'L');

        // ===== 承辦人簽核表格 =====
        $pdf->Ln(10);
        $pdf->Cell(30, 8, '承辦人', 1, 0, 'C');
        $pdf->Cell(40, 8, '', 1, 0, 'C');
        $pdf->Cell(30, 8, '財政課', 1, 0, 'C');
        $pdf->Cell(40, 8, '', 1, 0, 'C');
        $pdf->Cell(30, 8, '主任秘書', 1, 0, 'C');
        $pdf->Cell(20, 8, '', 1, 1, 'C');

        $pdf->Cell(30, 8, '課長', 1, 0, 'C');
        $pdf->Cell(40, 8, '', 1, 0, 'C');
        $pdf->Cell(30, 8, '主計室', 1, 0, 'C');
        $pdf->Cell(40, 8, '', 1, 0, 'C');
        $pdf->Cell(30, 8, '市長', 1, 0, 'C');
        $pdf->Cell(20, 8, '', 1, 1, 'C');

        // ===== 右下角版本號 =====
        $pdf->Ln(10);
        $pdf->SetFont($font_name, '', 10);
        $pdf->Cell(0, 8, '11403修訂', 0, 1, 'R');

		$filename = $file_title.$uid.'.pdf';
        // 儲存 PDF
        $pdf_file = $pdf_dir. $filename;
        $pdf->Output($pdf_file, 'F'); // 存檔到伺服器

        // 可以把 PDF 檔案路徑回傳給前端
		return $path.$filename;
        /**************************************************/
    }
?>