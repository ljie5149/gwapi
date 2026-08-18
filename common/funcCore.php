<?php
	function getVariant($obj, $field_name)
	{
		return isset($obj[$field_name]) ? $obj[$field_name] : "";
	}
	// 訊息中心 public
	function result_message($status, $code, $responseMessage, $json)
	{
		$data = array();
		$data["status"]			= $status;
		$data["code"]			= $code;
		$data["responseMessage"]= $responseMessage;
		$data["data"]			= $json;
		return $data;
	}
	function result_connect_error($link)
	{
		$data = array();
		if (!$link || is_null($link))
		{
			try
			{
				$data = result_message("false", "0x0206", "連接錯誤：".mysqli_connect_error(), "");
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0206", "連接錯誤 Exception error :".$e->getMessage(), "");
			}
		}
		else
		{
			$data = result_message("true", "0x0200", "連接成功", "");
		}
		return $data;
	}
	// 取得訊息符號
	function get_error_symbol($val)
	{
		/*
		0x0200	data parse succeed
		0x0201	data parse error					(X)
		0x0202	API parameter is required!			(!)
		0x0203	data exists							(!)
		0x0204	data not exists						(!)
		0x0205	dog err								(X)
		0x0206	other message - condiction			(!)
		0x0207	Exception error: disconnect!		(!)
		0x0208	SQL fail! please check query str	(!)
		0x0209	Exception error!					(X)
		*/
		$ret = "";
		
		if ($val == "0x0202" || $val == "0x0203" || $val == "0x0204" ||
			$val == "0x0206" || $val == "0x0207" || $val == "0x0208")
			$ret = "(!) ";
		else if ($val == "0x0201" || $val == "0x0205" || $val == "0x0209")
			$ret = "(X) ";
		return $ret;
	}
	
	function get_role_name($val)
	{
		$ret = "";
		switch ($val) {
			case "proposer":
				$ret = "要保人";
				break;
			case "insured":
				$ret = "被保人";
				break;
			case "legalRepresentative":
				$ret = "法定代理人";
				break;
			default:
				$ret = "";
		}
		return $ret;
	}
	// encrypt-加密  public
	function encrypt_string_if_not_empty($flag, $val)
	{
		global $g_key;
		
		$ret = $val;
		if ($val == "") return $ret;
		if ($flag)
			$ret = encrypt($g_key, $val);
		return $ret;
	}
	// decrypt-解密  public
	function decrypt_string_if_not_empty($flag, $val)
	{
		global $g_key;
		
		$ret = $val;
		if ($val == "") return $ret;
		if ($flag)
			$ret = decrypt($g_key, $val);
		return $ret;
	}
	// 組裝sql語法-非空白字  public
	function merge_sql_string_if_not_empty($column_name, $val, $method_flag="=", $is_value=false, $default_str="")
	{
		if ($is_value) {
			$ret = ($val > -1) ? " AND ".$column_name.$method_flag."".$val."" : "";
			if (empty($ret)) $ret = (!empty($default_str)) ? $default_str : "";
		} else {
			$ret = ($val != "") ? " AND ".$column_name.$method_flag."'".$val."'" : "";
            if (empty($ret)) $ret = (!empty($default_str)) ? "'".$default_str."'" : "";
		}
		return $ret;
	}
	function merge_sql_string_anyway($column_name, $val, $method_flag="=")
	{
		return " AND ".$column_name.$method_flag."'".$val."'";
	}
	// 組裝sql語法-非空白字  public
	function merge_sql_string_set_value($column_name, $val, $method_flag="=", $is_value=false, $is_first=false)
	{
		$ret = "";
		if ($is_value) {
			if ($val > -1) {
				$ret = ($is_first) ? "": ", ";
				$ret.= $column_name.$method_flag."".$val."";
			}
		} else {
			if ($val != "") {
				$ret = ($is_first) ? "": ", ";
				$ret.= $column_name.$method_flag."'".$val."'";
			}
		}
		return $ret;
	}
	function merge_with_comma(&$values, $val, $value_symbol="'")
	{
		$values.= (strlen($values) > 0) ? "," : "";
		$values.=  $value_symbol.$val.$value_symbol;
	}
	function uploadImage($path, $_GRAPH_FILE)
	{
		// 上傳圖檔
		$data = array();
		$dst_path = array(); $value = "";
		
		$data = result_message("false", "0x0206", $path, "");
		if (isset($_GRAPH_FILE['file'])) {
			$data = result_message("false", "0x0206", "step01", "");
			$file_name = $_GRAPH_FILE['file']['name'];
			$file_size = $_GRAPH_FILE['file']['size'];
			$file_tmp  = $_GRAPH_FILE['file']['tmp_name'];
			$file_type = $_GRAPH_FILE['file']['type'];
			$myfile_name = explode('.', $_GRAPH_FILE['file']['name']);
			$ret_str = ""; $err_str = ""; $succeed_flag = true; $dst_path = '';
			if (!is_null($myfile_name)) $file_ext = strtolower(end($myfile_name));
			$data = result_message("false", "0x0206", "step02", "");
			
			$extensions = array("xlsx","xls");
			if ($succeed_flag && in_array($file_ext, $extensions)=== false) {
				$err_str = "Extension not allowed, please choose an Excel file.";
				$data = result_message("false", "0x0206", $ret_str, "");
				$succeed_flag = false;
			}
			
			if ($succeed_flag && $file_size > 30971520) {
				$err_str = 'File size must be less than 30 MB';
				$data = result_message("false", "0x0206", $ret_str, "");
				$succeed_flag = false;
			}
			
			if ($succeed_flag && empty($errors) == true) {
				$dst_path = $path.$file_name;
				move_uploaded_file($file_tmp, $dst_path);
				// echo "Excel file uploaded successfully.";
				$data = result_message("true", "0x0200", $ret_str, "");
			}
			$ret_str = ($succeed_flag) ? '上傳圖片 ['.$file_name.'] 成功'.$dst_path : '上傳圖片 ['.$file_name.",".$dst_path.'] 異常<br>'.$err_str;
			$data['responseMessage'] = $ret_str;
		}
		return $data;
	}
	// 照片儲入Nas事先工作 public
	function will_save2nas_prepare($remote_ip, $Person_id, $front)
	{
		$data = array();
		$data["status"]			 = "true";
		$data["code"]			 = "0x0200";
		$data["responseMessage"] = "Create NAS Folder Success";
		$data["filename"] 		 = "";
		//$date = date("Ymd");
		$date = date("Y")."/".date("Ym")."/".date("Ymd");
		//$foldername ="/dis_app/dis_idphoto/".$date; 
		$foldername = NASDir().$date; 
		if (create_folder($foldername) == false)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0205";
			$data["responseMessage"]= "NAS fail!";
			$filename = "";
		}
		if ($data["status"] == "true")
		{
			$filename = $foldername."/".$Person_id."_".$front;
			$data["filename"] = $filename;
		}
		wh_log($remote_ip, $data["responseMessage"], $Person_id);
		return $data;
	}
	// 照片儲入Nas public
	function save_image2nas($remote_ip, $Person_id, $filename, $image)
	{
		try
		{
			$fp = fopen($filename, "w");
			$orgLen = strlen($image);
			if($orgLen<=0)
			{
				fclose($fp);
				return -1;
			}
			
			$len = fwrite($fp, $image, strlen($image));
			if($orgLen!=$len)
			{
				fclose($fp);
				return -2;
			}
			
			fclose($fp);
		/*	
			//Verify
			$fp = fopen($filename, "r");
			$rImg = fread($fp, filesize($filename));
			if($orgLen!=strlen($rImg))
			{
				fclose($fp);
				return -3;		
			}

			fclose($fp);
		*/
		}
		catch (Exception $e)
		{
			wh_log($remote_ip, "saveImagetoNas failed:".$e->getMessage(), $Person_id);
			return -4;
		}
		return 1;
	}
	function getFirstDateOfMonth($cur_date)
	{
		$yyyyMM=date("Y-m", $cur_date);
		return date("Y-m-d", strtotime("first day of {$yyyyMM}"));
	}
	function getLastDateOfMonth($cur_date)
	{
		$yyyyMM=date("Y-m", $cur_date);
		return date("Y-m-d", strtotime("{$yyyyMM} +1 month -1 day"));
	}
	function randomkeys4UserCode($length)
	{
		//$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
		$pattern = "1234567890";
		$key = "";
		for ($i=0;$i<$length;$i++) {
			$key .= $pattern[rand(0,9)];
		}
		return $key;
	}
	function randomkeys4NumEngCode($length)
	{
		$pattern = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$key = "";
		for ($i=0;$i<$length;$i++) {
			$key .= $pattern[rand(0, 61)];
		}
		return $key;
	}
	function getUniqueId() {
		return sprintf('%04x%04x%02x',
		  // 32 bits for the time_low
		  mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xff)
		);
	}
	function getUniqueId4Simple($head, $idx) {
		return $head.sprintf('%05d', $idx);
	}
	function getUUID() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		  // 32 bits for the time_low
		  mt_rand(0, 0xffff), mt_rand(0, 0xffff),
		  // 16 bits for the time_mid
		  mt_rand(0, 0xffff),
		  // 16 bits for the time_hi,
		  mt_rand(0, 0x0fff) | 0x4000,
	
		  // 8 bits and 16 bits for the clk_seq_hi_res,
		  // 8 bits for the clk_seq_low,
		  mt_rand(0, 0x3fff) | 0x8000,
		  // 48 bits for the node
		  mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
	/**
	 * 自動處理檔案編碼
	 * @param string $file_data 檔案內容
	 * @param string $filename 檔名
	 * @return string 處理後的檔案內容
	 */
	function convertToUTF8IfNeeded($file_data, $filename)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$textTypes = ['csv','txt','json','xml','html','htm','log','md','sql'];
		
		if (!in_array($ext, $textTypes)) {
			return $file_data; // 非文字檔直接回傳
		}

		// 1. 檢查並處理 UTF-16
		if (substr($file_data, 0, 2) === "\xFF\xFE") {
			return mb_convert_encoding($file_data, 'UTF-8', 'UTF-16LE');
		}
		if (substr($file_data, 0, 2) === "\xFE\xFF") {
			return mb_convert_encoding($file_data, 'UTF-8', 'UTF-16BE');
		}

		// 2. 檢查是否已經是 UTF-8 (含 BOM)
		$utf8_bom = "\xEF\xBB\xBF";
		if (strncmp($file_data, $utf8_bom, 3) === 0) {
			return $file_data; // 已經是標準 UTF-8，直接回傳
		}

		// 3. 偵測編碼 (優先偵測 UTF-8，避免誤判)
		$encoding = mb_detect_encoding($file_data, ['UTF-8', 'BIG5', 'CP950', 'GBK', 'ASCII'], true);

		if ($encoding === 'UTF-8') {
			// 如果偵測是 UTF-8 但沒 BOM，我們補上 BOM 給 CSV 用
			return ($ext === 'csv') ? $utf8_bom . $file_data : $file_data;
		}

		if ($encoding) {
			// 轉碼為 UTF-8
			$file_data = mb_convert_encoding($file_data, 'UTF-8', $encoding);
		}

		// 4. 最後針對 CSV 統一補上 UTF-8 BOM，確保 Excel 不亂碼
		if ($ext === 'csv' && strncmp($file_data, $utf8_bom, 3) !== 0) {
			$file_data = $utf8_bom . $file_data;
		}

		return $file_data;
	}
	
    // JTG_applying.php 內部處理附件的邏輯建議改寫為：
    function processMultiUpload($data, $g_root_dir, $image_path, $uid, $field, &$n, $with_origin_name = false) {
        if (empty($data)) return "";
        $files = explode(';;;', $data);
        $saved_paths = [];
        foreach ($files as $file_item) {
            $parts = explode(';;', $file_item);
            if (count($parts) < 2) continue;
            $origin_name = $parts[0];
            $base64_data = $parts[1];
			$ext = pathinfo($origin_name, PATHINFO_EXTENSION);
            // 呼叫原本的儲存函式，並加上唯一識別防止檔名重複
			// echo $origin_name."\n".$n;
			if ($ext == "pdf") {
				$saved_path = saveBase64PDF($base64_data, $g_root_dir, $image_path, '_'.$uid.'_'.$n++, $field.".".$ext, $field);
			} else {
            	$saved_path = saveBase64Image($base64_data, $g_root_dir, $image_path, '_'.$uid.'_'.$n++
                                                        , $field.".".$ext,  $field);
			}
            $saved_paths[] = ($with_origin_name) ? $origin_name.";".$saved_path : $saved_path;
        }
			// echo (($with_origin_name) ? implode(';;', $saved_paths) : implode(';', $saved_paths));
        return ($with_origin_name) ? implode(';;', $saved_paths) : implode(';', $saved_paths); // 存入資料庫的路徑也以分號隔開
    }

	function saveBase64attachment($base64, $root_path, $path, $uid, $file_name, $file_title="undef")
	{
		$ret = "";
		try {
			// Extract the data part from the base64 string (this is the part after "data:image/png;base64,")
			$base64_image = substr($base64, strpos($base64, ',') + 1);

			// Decode the base64-encoded image data to binary data
			$file_data = base64_decode($base64_image);

			if ($file_data === false) {
				return "";
			}

			// Set a filename for the image (you can use any name you like)
			$array_filename = explode('.', $file_name);
			$filename_ext = $array_filename[count($array_filename) - 1];
			$filename = $file_title.$uid.'.'.$filename_ext;
			// echo $root_path.$path.$filename;

			// 只針對需轉換的格式
			$utf8_data = convertToUTF8IfNeeded($file_data, $filename);

			// Save the binary data to a file on your server
			if (!is_dir($root_path.$path)) mkdir($root_path.$path, 0777, true);
			file_put_contents($root_path.$path.$filename, $utf8_data);

			$ret = $path.$filename;
		} catch (Exception $e) { }
		return $ret;
	}
	function saveBase64Image($base64, $root_path, $path, $uid, $file_name, $file_title="undef")
	{
		$ret = "";
		try {
			// Extract the data part from the base64 string (this is the part after "data:image/png;base64,")
			$base64_image = substr($base64, strpos($base64, ',') + 1);

			// Decode the base64-encoded image data to binary data
			$image_data = base64_decode($base64_image);

			// Set a filename for the image (you can use any name you like)
			$array_filename = explode('.', $file_name);
			$filename_ext = $array_filename[count($array_filename) - 1];
			$filename = $file_title.$uid.'.'.$filename_ext;
			// echo $root_path.$path.$filename;

			// Save the binary data to a file on your server
			file_put_contents($root_path.$path.$filename, $image_data);

			$ret = $path.$filename;
		} catch (Exception $e) { }
		return $ret;
	}
	function saveBase64PDF($base64, $root_path, $path, $uid, $file_name, $file_title = "undef") 
	{
		$ret = "";
		try {
			// Extract the data part from the base64 string (after "data:application/pdf;base64,")
			$base64_pdf = substr($base64, strpos($base64, ',') + 1);

			// Decode the base64-encoded PDF data to binary
			$pdf_data = base64_decode($base64_pdf);

			// 處理檔名
			$array_filename = explode('.', $file_name);
			$filename_ext = $array_filename[count($array_filename) - 1];

			// 強制副檔名為 pdf（避免傳進來不是 .pdf）
			$filename = $file_title.$uid.'.'.$filename_ext;
			// echo $filename."\n";

			// Save the binary data to a file on your server
			file_put_contents($root_path . $path . $filename, $pdf_data);

			$ret = $path . $filename;
		} catch (Exception $e) {
			// 你也可以 log error
		}
		return $ret;
	}
	function saveBase64File($base64, $path, $uid, $file_name, &$err)
	{
		$ret = "";
		try {
			// Remove the MIME type prefix
			$base64_content = substr($base64, strpos($base64, ',') + 1);

			// Decode the base64 string to binary data
			$binary_data = base64_decode($base64_content);
			$err = $binary_data;
			
			$array_filename = explode('.', $file_name);
			$file_title = $array_filename[0];
			$filename_ext = $array_filename[count($array_filename) - 1];
			$filename = $file_title.$uid.'.csv';//.$filename_ext;
			
			// Write the binary data to a file
			$file_handle = fopen($path.$filename, 'wb');
			fwrite($file_handle, $binary_data);
			fclose($file_handle);

			// file_put_contents($path.$filename, $binary_data);

			$ret = $filename;
		} catch (Exception $e) {
			$err .= $e->getMessage();
		}
		return $ret;
	}
	function saveBase64Excel($base64, $path, $uid, $file_name, &$err)
	{
		$ret = "";
		$xls_type = "Excel2007";
		try {
			// Remove the MIME type prefix
			$base64_content = substr($base64, strpos($base64, ',') + 1);

			// Decode the base64 string to binary data
			$file_data = base64_decode($base64_content);
			$err = $file_data;

			$array_filename = explode('.', $file_name);
			$file_title = $array_filename[0];
			$filename_ext = $array_filename[count($array_filename) - 1];
			$filename = $file_title.$uid.'.'.$filename_ext;
			
			// Create a new PHPExcel object
			$objPHPExcel = PHPExcel_IOFactory::load($file_data);

			// Get the worksheet object
			$worksheet = $objPHPExcel->getActiveSheet();
			
			// Get the highest row number and column letter
			$highest_row = $worksheet->getHighestRow();
			$highest_column = $worksheet->getHighestColumn();
			$ret = $highest_row.','.$highest_column;

			// Save the PHPExcel object as an Excel file
			// if ($filename_ext == "xls" ) $xls_type = "Excel5";
			if ($filename_ext == "xlsx") $xls_type = "Excel2007";
			$ret = $filename_ext.','.$xls_type.','.$path.$filename;
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $xls_type);
			$objWriter->save($path.$filename);

			$ret = $filename;
		} catch (Exception $e) { 
			$err .= $e->getMessage();
		}
		return $ret;
	}
	function strStartWith($src, $search)
	{
		return (substr($src, 0, strlen($search)) === $search);
	}
    function strEndWith($src, $search)
    {
        return (substr($src, -strlen($search)) === $search);
    }
	function get24HourFormat($val)
	{
		if (empty($val)) {
			$dateTime = new DateTime(); // 取得當前時間
		} else {
			try {
				$dateTime = new DateTime($val);
			} catch (Exception $e) {
				return "Invalid Date Format"; // 若解析日期失敗，回傳錯誤訊息
			}
		}
	
		return $dateTime->format("Y-m-d H:i:s"); // 轉換為 24 小時制格式
	}
	function getDateFormat($val)
	{
		$dateTime = empty($val) ? new DateTime() : new DateTime($val);
		return $dateTime->format("Y-m-d");
	}
	function getDateFormat2($val, $srcformat = "d/m/Y")
	{
		if (empty($val)) {
			$dateTime = new DateTime(); // 取得當前時間
		} else {
			$dateTime = DateTime::createFromFormat($srcformat, $val);
			if (!$dateTime) {
				return "Invalid Date Format"; // 如果格式錯誤，回傳錯誤訊息
			}
		}

		return $dateTime->format("Y-m-d"); // 轉換為 YYYY-MM-DD 格式
	}
	function getDateFormat3($val, $srcformat = "d/m/Y")
	{
		if (empty($val)) {
			$dateTime = new DateTime();
		} else {
			// 先嘗試解析常見格式 (支援 1/1/26 這種)
			$parts = explode('/', $val);
			if (count($parts) === 3) {
				$y = $parts[2];

				// ✅ 判斷年份是否為 2 碼
				if (strlen($y) == 2 && ctype_digit($y)) {
					$y = (int)$y;
					// 你是處理 2026 這種未來年份，所以直接用 2000+
					$y = 2000 + $y;
				}

				$val = $parts[0] . '/' . $parts[1] . '/' . $y;
				$srcformat = "d/m/Y";
			}

			$dateTime = DateTime::createFromFormat($srcformat, $val);
			if (!$dateTime) return "Invalid Date Format";
		}

		return $dateTime->format("Y-m-d");
	}
	function getEmptyRocDate()
	{
		return "中華民國        年      月      日";
	}
	function getRocDate($val = "")
	{
		// 如果沒有傳入日期就取現在時間
		if (strlen($val) == 0) {
			$now = new DateTime();
		} else {
			$now = DateTime::createFromFormat("Y-m-d H:i:s", $val);
			if (!$now) {
				// 若格式不符，嘗試用一般 strtotime 解析
				$now = new DateTime($val);
			}
		}

		// 計算民國年
		$rocYear = (int)$now->format("Y") - 1911;
		$month   = $now->format("m");
		$day     = $now->format("d");

		return "中華民國 {$rocYear} 年 {$month} 月 {$day} 日";
	}
	function getDateTimeFormat($val, $dtformat = "Ymd")
	{
		if (strlen($val) == 0) {
			$now = new DateTime();
			$val = $now->format('Y-m-d H:i:s');
		}
		$ret = $val;
		$dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $val);
		$ret = $dateTime->format($dtformat);
		return $ret;
	}
	function getTwoTimeDiff($tm1, $tm2)
	{
		$dst_tm1 = (is_string($tm1)) ? new DateTime($tm1) : $tm1;
		$dst_tm2 = (is_string($tm2)) ? new DateTime($tm2) : $tm2;
		$interval = $dst_tm1->diff($dst_tm2);
		$seconds = $interval->s
				 + $interval->i * 60
				 + $interval->h * 60 * 60
				 + $interval->d * 24 * 60 * 60
				 + $interval->m * 30 * 24 * 60 * 60
				 + $interval->y * 365 * 24 * 60 * 60;
		return $seconds;
	}
	function findStrInArray($array_val, $search) {
		$ret = -1;
		for ($i = 0; $i < count($array_val); $i++) {
			if ($array_val[$i] == $search) {
				$ret = $i;
				break;
			}
		}
		return $ret;
	}
	function emptyStrInArray($array_val) {
		$ret = true;
		for ($i = 0; $i < count($array_val); $i++) {
			if ($array_val[$i] != '') {
				$ret = false;
				break;
			}
		}
		return $ret;
	}
	function str2array($val, $seperate_str = ',') {
		$ret_array = array();
		
		if (stripos($val, $seperate_str) === false) {
			array_push($ret_array, $val);
		} else {
			$ret_array = explode($seperate_str, $val);
		}
		return $ret_array;
	}

	// 網頁使用
    function base64urlencode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    function base64urldecode($data) {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($data);
    }
	function decrypt4web($key, $garble)
	{
		global $g_iv;
		//list($encrypted_data, $iv) = explode('::', base64_decode($garble), 2);
		$iv = $g_iv;
		// echo "key :$key<br>iv :$iv<br>";
		$encrypted_data = base64urldecode($garble);
		if ($encrypted_data === false) {
			// echo "Base64 解碼失敗！<br>";
			return null;
		}

		$decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 1, $iv);
		if ($decrypted === false) {
			echo "解密失敗：" . openssl_error_string() . "<br>";
		}

		return $decrypted;
	}
?>
