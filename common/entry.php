<?php
	/* PHP Excel section - start */
	// error_reporting(E_ALL); /** Error reporting */
	require_once dirname(__FILE__).'/../PHPExcel-1.8/Classes/PHPExcel.php';
	require_once dirname(__FILE__).'/../PHPExcel-1.8/Classes/PHPExcel/IOFactory.php'; /** Include PHPExcel_IOFactory */
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
	/*********************************************************/
	/*                                                       */
	/* 		PHPExcel component: php read excel file 		 */
	/*                                                       */
	/*********************************************************/
	// if (!empty($dst_filename)) {
	// 	try {
	// 		$objPHPExcel = PHPExcel_IOFactory::load($g_xlsx_in_path.$dst_filename);
	// 		$worksheet = $objPHPExcel->getActiveSheet();
	// 		foreach ($worksheet->getRowIterator() as $row) {
	// 			$cellIterator = $row->getCellIterator();
	// 			$cellIterator->setIterateOnlyExistingCells(false); // Loop through all cells, even if they're empty
	// 			foreach ($cellIterator as $cell) {
	// 				$value .= ','.$cell->getCalculatedValue(); // Get the value of the cell
	// 			}
	// 			$value .= '\n';
	// 		}
	// 		$data = result_message("true", "0x0200", $ret_str.'<br>解析Excel檔案 ['.$file_name.'] 成功', $value);
	// 	} catch(Exception $ex) {
	// 		$succeed_flag = false;
	// 		$data = result_message("false", "0x0207", $ret_str.'<br>解析Excel檔案 ['.$file_name.'] 異常<br>'." :", $ex->getMessage());
	// 	}
	// }
	/* PHP Excel section - end */
    include("class4js.php");
    include("country_define.php"); // 國籍
    include("county_define.php");  // 縣市
    include("define.php");
    include("ui_define.php");
	include("log.php");
	include("db_tools.php");
	include("funcCore.php");
	include("api_core.php");
    include("accessdb.php");
    include("author_define.php");
    include("mailCore.php");
	
	function getGoldenKey($forceRefresh = false)
	{
		global $g_root_url, $g_k;
		static $cached_token = null;

		// 1. 若不需要強制刷新，先檢查靜態快取與 Session
		if (!$forceRefresh) {
			if (!empty($cached_token)) {
				return $cached_token;
			}

			$session_token = trim(($_COOKIE['sso_token'] ?? ''));
			if (!empty($session_token)) {
				$cached_token = $session_token;
				return $session_token;
			}
		}

		// 2. 準備 API 請求參數
		$token_url = rtrim($g_root_url, '/') . "/api/JTG_exhancetoken.php";
		$token_payload = json_encode([
			"api_key"  => $g_k,
			"who_call" => "web_login"
		]);

		$token_headers = [
			'Content-Type: application/json'
		];

		// 3. 呼叫核心 API
		$error = "";
		$token_response = callAPI($error, $token_url, $token_payload, "POST", false, $token_headers);
		$token_result = json_decode($token_response, true);

		// 4. 驗證 API 回應結果
		if (!empty($error) || !isset($token_result['status']) || $token_result['status'] !== 'true') {
			$error_msg = $token_result['responseMessage'] ?? $token_result['message'] ?? $error ?? 'SSO 認證失敗！';
			throw new Exception("SSO Token 取得失敗: " . $error_msg);
		}

		// 5. 解析 Token 欄位
		$sso_token = is_array($token_result['data'])
			? ($token_result['data']['sso_token'] ?? $token_result['data'][0] ?? '')
			: ($token_result['data'] ?? '');

		if (empty($sso_token)) {
			throw new Exception("SSO Token 取得失敗: 回傳資料不包含有效的 Token");
		}

		// 6. 寫入 Session 與快取
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		$_SESSION['sso_token'] = $sso_token;
		$cached_token = $sso_token;

		return $sso_token;
	}

	// 帳密變成token
	function generateSSOtoken($uid, $upwd)
	{
		global $key, $g_jotangiwww;
		$SSO_info["www"] 		= $g_jotangiwww;
		$SSO_info["uid"] 		= $uid;
		$SSO_info["upwd"] 		= $upwd;
		$SSO_info["expire"] 	= date("Y-m-d H:i:s");
		$SSO_json 				= json_encode($SSO_info);
		$SSO_token["sso_token"]	= encrypt($key, $SSO_json);
		return ($SSO_token);
	}
	function validToken($val, &$member_id, &$role, &$order_limit, $ori_pwd="", $skip_expire=true)
	{
		global $key, $g_jotangiwww, $g_token_expire_sec;
		$ret = false;
		// $token = json_decode($val);
		// $content = $token->sso_token;
		$content_decry = decrypt($key, $val);
		// echo $content_decry;
		$SSO_info = json_decode($content_decry);
		// var_dump($SSO_info);
		if (is_object($SSO_info) && isset($SSO_info->www)) {
			if ($SSO_info->www != $g_jotangiwww) {
				$data = result_message("false", "0x0205", "token identity error", array());
				return $data;
			}
		} else {
			$data = result_message("false", "0x020E", "invalidate token!", array());
			return $data;
		}
		if (is_object($SSO_info) && isset($SSO_info->uid)) {
			if (empty($SSO_info->uid)) {
				$data = result_message("false", "0x0205", "token user id is required without empty", array());
				return $data;
			}
		} else {
			$data = result_message("false", "0x020E", "invalidate token!", array());
			return $data;
		}
		if (is_object($SSO_info) && isset($SSO_info->upwd)) {
			if (empty($SSO_info->upwd)) {
				$data = result_message("false", "0x0205", "token user pwd is required without empty", array());
				return $data;
			}
		} else {
			$data = result_message("false", "0x020E", "invalidate token!", array());
			return $data;
		}

		if ($skip_expire == false) {
			$dt_now = date('Y-m-d H:i:s', strtotime('now'));
			$dateTime = new DateTime($SSO_info->expire);
			if (getTwoTimeDiff($dt_now, $dateTime) > $g_token_expire_sec) {
				$data = result_message("false", "0x0205", "token over expire (".$g_token_expire_sec.")", array());
				return $data;
			}
		}

		$remote_ip = get_remote_ip();
		$db= new CXDB($remote_ip);
		try {
			$data = $db->connect($link, $SSO_info->uid, "");
			if ($data["status"] == "true") {
				$upwd = (empty($ori_pwd)) ? $SSO_info->upwd : $ori_pwd;
				// echo $upwd;
				$result = $db->existsMember($link, $SSO_info->uid, $upwd);
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					$member_id = $SSO_info->uid;
					if ($row = mysqli_fetch_array($result)) {
						$role =$row['role'];
						$order_limit = isset($row['order_limit']) ? $row['order_limit']: 0;
					}
					$data = result_message("true", "0x0200", "token is validate.", array());
				} else {
					$data = result_message("false", "0x0206", "請重新輸入密碼", array());
				}
            }
		} catch (Exception $e) {
			$data = result_message("false", "0x0205", "token sql Exception :".$e->getMessage(), array());
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $SSO_info->uid);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $data;
	}
	function validToken4ApiUser($val, &$member_id, &$role, &$order_limit, $ori_pwd="", $skip_expire=true)
	{
		global $key, $g_jotangiwww, $g_token_expire_sec;
		$ret = false;
		// $token = json_decode($val);
		// $content = $token->sso_token;
		$msg = "";
		$content_decry = decrypt($key, $val, $msg);
		// echo $content_decry;
		$SSO_info = json_decode($content_decry);
		// var_dump($SSO_info);
		if (is_object($SSO_info) && isset($SSO_info->www)) {
			if ($SSO_info->www != $g_jotangiwww) {
				$data = result_message("false", "0x0205", "token identity error", array());
				return $data;
			}
		} else {
			$data = result_message("false", "0x020E", "invalidate token!", array());
			return $data;
		}
		if (is_object($SSO_info) && isset($SSO_info->uid)) {
			if (empty($SSO_info->uid)) {
				$data = result_message("false", "0x0205", "token user id is required without empty", array());
				return $data;
			}
		} else {
			$data = result_message("false", "0x020E", "invalidate token!", array());
			return $data;
		}
		if (is_object($SSO_info) && isset($SSO_info->upwd)) {
			if (empty($SSO_info->upwd)) {
				$data = result_message("false", "0x0205", "token user pwd is required without empty", array());
				return $data;
			}
		} else {
			$data = result_message("false", "0x020E", "invalidate token!", array());
			return $data;
		}

		if ($skip_expire == false) {
			$dt_now = date('Y-m-d H:i:s', strtotime('now'));
			$dateTime = new DateTime($SSO_info->expire);
			if (getTwoTimeDiff($dt_now, $dateTime) > $g_token_expire_sec) {
				$data = result_message("false", "0x0205", "token over expire (".$g_token_expire_sec.")", array());
				return $data;
			}
		}

		$remote_ip = get_remote_ip();
		$db= new CXDB($remote_ip);
		try {
			$data = $db->connect($link, $SSO_info->uid, "");
			if ($data["status"] == "true") {
				$upwd = (empty($ori_pwd)) ? $SSO_info->upwd : $ori_pwd;
				// echo $SSO_info->uid.",".$upwd;
				$result = $db->existsSysuser($link, $SSO_info->uid, $upwd);
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					$member_id = $SSO_info->uid;
					$data = result_message("true", "0x0200", "token is validate.", array());
				} else {
					$data = result_message("false", "0x0206", "請重新輸入密碼", array());
				}
            }
		} catch (Exception $e) {
			$data = result_message("false", "0x0205", "token sql Exception :".$e->getMessage(), array());
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $SSO_info->uid);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $data;
	}
	function sendFCM($fcm_token, $FCM_title, $FCM_content, $FCM_extra)
	{
		global $g_notify_apiurl;

		$FCM_url = $g_notify_apiurl;
		$fcmresult = "";
		if (!empty($FCM_extra)) {
			$fields = array(
				'to' 		   => $fcm_token,
				"notification" => [
									"body"  	   => $FCM_content,
									"title" 	   => $FCM_title,
									"icon"  	   => "ic_launcher",
									"sound" 	   => "default",
									"click_action" => $FCM_extra,
				],
			);
		} else {
			$fields = array(
				'to' 		   => $fcm_token,
				"notification" => [
									"body" 	=> $FCM_content,
									"title" => $FCM_title,
									"icon"  => "ic_launcher",
									"sound" => "default",
				],
			);
		}
		
		$headers = array(
			'Authorization: key=AAAAADrsV1M:APA91bEH_dSnFD_CtG2z4UyJo8kQSG5fziwYmyxQJeftr-PcLOPe_xoMhWxLIa-B9wn078EDTl-A3S8eZcExy49xdXxAGSMGA3QNbPZKBAI73jcstgdT77b8DspUmeFR59JD8QaABO1C',
			'Content-Type: application/json',
		);
		for ($i = 0; $i < 3; $i++) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL			, $FCM_url				);
			curl_setopt($ch, CURLOPT_POST			, true					);
			curl_setopt($ch, CURLOPT_HTTPHEADER		, $headers				);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER	, true					);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER	, false					);
			curl_setopt($ch, CURLOPT_POSTFIELDS		, json_encode($fields)	);
			$fcmresult = curl_exec($ch);
			
			curl_close($ch);	
			if (strlen($fcmresult) > 2) break;							
		}
		return (strlen($fcmresult) > 2);
	}
	// 顯示過長資料，則使用縮略文字
	function getShortText4Show($str, $max_len = 20)
	{
		return (strlen($str) > $max_len * 3) ? mb_substr($str, 0, $max_len, "UTF-8")."..." : $str; //substr($str, 0, $max_len * 3).".." : $str;
	}

	// 取得 html 空白字元
	function getHtmlSpaceChar($mylength = 0)
	{
		$ret = "";
		for ($i = 0; $i < $mylength; $i++)
			$ret.="&nbsp;";
		return $ret;
	}
	
	// 取得序號且判斷是否重覆
	function getSid($private_db, $private_link, $table, $member_id)
	{
		$sid 		= "";
		
		$sid = getUniqueId();
		while ($private_db->existsSid($private_link, $table, $sid)) {
			$sid = getUniqueId();
		}
		return $sid;
	}
	// 取得序號且判斷是否重覆
	function getSidSimple($table, $member_id, $head)
	{
		$idx 		= 0;
		$sid 		= "";
		$data 		= array();
		$remote_ip 	= get_remote_ip();
		$db			= new CXDB($remote_ip);
    	try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$idx++;
				$sid = getUniqueId4Simple($head.getDateTimeFormat(""), $idx);
				while ($db->existsSid($link, $table, $sid)) {
					$idx++;
					$sid = getUniqueId4Simple($head.getDateTimeFormat(""), $idx);
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $sid;
	}
	// 取得序號且判斷是否重覆
	function getSidSimple4Applying($table, $member_id, $head)
	{
		$idx 		= 0;
		$sid 		= "";
		$data 		= array();
		$remote_ip 	= get_remote_ip();
		$db			= new CXDB($remote_ip);
    	try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$idx++;
				$sid = getUniqueId4Simple($head.getDateTimeFormat(""), $idx);
				while ($db->existsSid($link, $table, $sid)) {
					$idx++;
					$sid = getUniqueId4Simple($head.getDateTimeFormat(""), $idx);
				}
			}
			$sid .= "_".randomkeys4NumEngCode(5);
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $sid;
	}
	function getSidSimpleByIdx($table, $member_id, $head, $check_first, &$idx)
	{
		$sid 		= "";
		$data 		= array();
		$remote_ip 	= get_remote_ip();
		$db			= new CXDB($remote_ip);
    	try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$idx++;
				$sid = getUniqueId4Simple($head.getDateTimeFormat(""), $idx);
				if ($check_first) {
					while ($db->existsSid($link, $table, $sid)) {
						$idx++;
						$sid = getUniqueId4Simple($head.getDateTimeFormat(""), $idx);
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $sid;
	}
	
	// 取得序號且判斷是否重覆
	function checkMemberExists($table, $uid, $upwd)
	{
		$fRet = false;
		$sid 		= "";
		$data 		= array();
		$remote_ip 	= get_remote_ip();
		$db			= new CXDB($remote_ip);
    	try {
			$data = $db->connect($link, $uid, "");
			if ($data["status"] == "true") {
				
				$result = $db->existsMember($link, $uid, $upwd, "*", '', '');
				
				if (!is_null($result)) {
					if (mysqli_num_rows($result) > 0) {
						$fRet = true;
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $uid);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $fRet;
	}

	// 取得遠端用戶的ip public
	function get_remote_ip()
	{
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "localhost";
		}
		return $ip;
	}
	function get_remote_ip_underline()
	{
		$ip = get_remote_ip();
		$ip = str_replace('.', '_', $ip);
		$ip = str_replace(':', '_', $ip);
		return $ip;
	}

	// close connection at finally
	function close_connection_finally(&$link, $remote_ip, $Person_id,
									  $log_title = "", $log_subtitle = "", $file_header="Api")
	{
		$data 			= array();
		$data_status 	= array();
		
		$dst_Person_id 	= "";
		$dst_title 		= $log_title	;
		$dst_subtitle 	= $log_subtitle	;
		$dst_Person_id 	= ($log_title 	 == "") ? $Person_id 			: "";
		$data = result_message("true", "0x0200", "close connection Succeed!", "");
		// wh_log($remote_ip, $data["responseMessage"], $dst_Person_id, $file_header);
		try
		{
			if ($link != null)
			{
				mysqli_close($link);
				$link = null;
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
			wh_log_Exception($remote_ip, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $dst_Person_id, $file_header);
		}
		return $data;
	}

	function getHairserviceParameter($db, $link, $sid, $service_item, $Mode="get_name")
	{
		$ret = "";
		try {
			$result2 = $db->getHairservice($link, $sid, -1, 0, -1, -1, $service_item
									 , "*" , "");
			if (!is_null($result2) && mysqli_num_rows($result2) > 0) {
				while ($row2 = mysqli_fetch_array($result2)) {
					if ($Mode == "get_name")
						$ret.=$row2['service_name'].",";
					else
						$ret.=$row2['service_price'].",";
				}
				$ret = substr($ret, 0, strlen($ret) - 1);
			} else {
				$ret = "";
			}
		} catch (Exception $e) {
			$ret = "";
		}
		return $ret;	
	}
	function getMemberCount($db, $link, $sid, $item)
	{
		$member_count = 0;
		try {
			$date2 = new DateTime(date("Y-m-d"));
			$edate = $date2->format('Y-m-d');
			switch ($item) {
				case "d":
					$sdate = $date2->format('Y-m-d');
					break;
				case "w":
					$date1 = $date2->modify('-7 day');
					$sdate = $date1->format('Y-m-d');
					break;
				case "m":
					$date1 = $date2->modify('-30 day');
					$sdate = $date1->format('Y-m-d');
					break;
			}
			$result = $db->getMyMembercardCount($link, $sid, 0, $sdate, $edate);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result)) {
					$member_count = $row['member_count'];
				}
			}
		} catch (Exception $e) { }	
		return $member_count;	
	}
	
	function postChangePwd($memberid, $memberpwd)
	{
		$data = array(
			'mobile' 	=> $memberid,
			'password' 	=> $memberpwd,
		);

		$post_data = json_encode($data);
		$result = callAPI($error, 'https://ml-api.jotangi.com.tw/api/auth/rewritepwd', $post_data, "POST", true);
		$obj = json_decode($result, true);

		// handle curl error
		return ($obj["status"] == "error") ? 0 : 1; //die();
	}
	function postRegisterMember($membername, $memberid, $memberpwd)
	{
		$data = array(
		  'name' 		=> $membername,
		  'mobile' 		=> $memberid,
		  'password' 	=> $memberpwd,
		);
		$error=null;
		$post_data = json_encode($data);
		$result = callAPI($error, 'https://ml-api.jotangi.com.tw/api/auth/register', $post_data, "POST", true);
		$obj = json_decode($result, true);

		// handle curl error
		return ($obj["status"] == "error") ? $error : 1; //die();
	}
	function postResetPwd($memberid, $memberpwd)
	{
		$data = array(
			'mobile' 	=> $memberid,
			'password' 	=> $memberpwd,
		);

		$post_data = json_encode($data);
		$result = callAPI($error, 'https://ml-api.jotangi.com.tw/api/auth/rewritepwd', $post_data, "POST", true);
		$obj = json_decode($result, true);

		// handle curl error
		return ($obj["status"] == "error") ? 0 : 1; //die();
	}
	function uiLocationPage($skip_org_management = false, $onlyChk4LogoutPage = false)
	{
		$ret = "NONE";
		global $g_supperuser_all;

		// 從 Cookie 讀取角色與帳號名稱 (加上 rawurldecode 處理中文)
		$userrole = $_COOKIE['user_role'] ?? '';
		$accname  = isset($_COOKIE['acc_name']) ? trim(rawurldecode($_COOKIE['acc_name'])) : '';

    	// echo "userrole :${userrole}, accname :${accname}";
		// exit;
		// 1. 若非登出頁檢查且權限角色為空，導回首頁
		if (!$onlyChk4LogoutPage && empty($userrole)) {
			$ret = "step1";
			header("Location: ./");
			exit;
		}

		// 2. 若帳號名稱為空，觸發登出流程
		if ($accname === '') {
			$ret = "若帳號名稱為空，觸發登出流程";
			header("Location: logout.php");
			exit;
		}

		// 3. 特殊機構管理頁面轉向邏輯
		if (!$skip_org_management && !$g_supperuser_all) {
			if ($userrole === 'superuser') {
				$ret = "特殊機構管理頁面轉向邏輯";
				header("Location: ./org_management.php");
				exit;
			}
		}
		return $ret;
	}
	function getFullMenuString($idx, $subidx)
	{
		$ret = "";
		$ret = getMenuString($idx);
		$retsub = getSubMenuString($idx, $subidx);
		return (empty($retsub)) ? $ret : $ret." --> ".$retsub;
	}
	function getMenuString($idx)
	{
		global $g_sidemenu;
		$root = $g_sidemenu['root'];
		return $root[$idx];
	}
	function getSubMenuString($idx, $subidx)
	{
		global $g_sidemenu;
		$root = $g_sidemenu['root'];
		$subMenu = $g_sidemenu[$root[$idx]];
		return $subMenu[$subidx];
	}
	function getMenuIcon($idx)
	{
		global $g_sidemenu;
		$root_icon  = $g_sidemenu['root_icon'];
		return $root_icon[$idx];
	}
	function import($column_info, $caption, $result, $db, $link, $member_id, &$file_name, &$records)
	{
		global $g_xlsx_out_path;

		$ret = false;
		$file_tmp = $file_name;
		if (count($column_info) > 0) {
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setTitle($caption);
			$objPHPExcel->getProperties()->setCreator($caption);
			$col = 0;
			for ($i = 0; $i < count($column_info); $i++) {
				$col_info = $column_info[$i];
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, $col_info[2]);
				$objPHPExcel->getActiveSheet()->getCommentByColumnAndRow($i, 1)->getText()->createTextRun($col_info[1]); // 設定註解
			}
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				$records = mysqli_num_rows($result);
				// Add the data to the Excel file
				$row = 2;
				while ($rows = mysqli_fetch_array($result)) {
					$col = 0;
					for ($i = 0; $i < count($column_info); $i++) {
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $rows[$i]);
						$col++;
					}
					$row_pos = $row - 1;
					$percent = intval($row_pos / $records * 100);
					if ($percent == 100) $percent = 99;
					$row++;
					$db->modifyProgress($link, $member_id, $file_tmp, $percent, "export");
					// echo "<script>document.getElementById('access_progress').value = $percent;</script>";
					// $data["progress"] = $percent;
					// flush();
					// usleep(0.01 * 1000);
				}
				$ret = true;
			}
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$file_name = date("YmdHi").$file_tmp.'.xlsx';
			$j = 1;
			while (file_exists($g_xlsx_out_path.$file_name)) {
				$file_name = date("YmdHi").$file_tmp.'_'.$j.'.xlsx';
				$j++;
			}
			$objWriter->save($g_xlsx_out_path.$file_name);
			$percent = 100;
			$db->modifyProgress($link, $member_id, $file_tmp, $percent, "export");
		}
		return $ret;
	}
	function export($column_info, $caption, $result, $db, $link, $member_id, $kind_parent, &$file_name, &$records)
	{
		global $g_xlsx_out_path;
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;

		$ret = false;
		$file_tmp = $file_name;

		if (count($column_info) > 0) {
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setTitle($caption);
			$objPHPExcel->getProperties()->setCreator($caption);

			$activeSheet = $objPHPExcel->getActiveSheet();

			// 1. 設定整頁預設儲存格格式為「純文字 (@)」
			$activeSheet->getDefaultStyle()
						->getNumberFormat()
						->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

			$column_widths = [];
			$col = 0;

			// 2. 設定表頭
			for ($i = 0; $i < count($column_info); $i++) {
				$col_info = $column_info[$i];
				$name   = $col_info[$g_fldidx_comment];
				$show   = $col_info[$g_fldidx_show];

				if ($show == "true") {
					$activeSheet->setCellValueExplicitByColumnAndRow($col, 1, (string)$name, PHPExcel_Cell_DataType::TYPE_STRING);
					$column_widths[$col] = mb_strwidth((string)$name, 'UTF-8');
					$col++;
				}
			}

			$records = 0;
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				$records = mysqli_num_rows($result);
				$row = 2;

				// 3. 寫入資料
				while ($rows = mysqli_fetch_array($result)) {
					$col = 0;
					for ($i = 0; $i < count($column_info); $i++) {
						$col_info = $column_info[$i];
						$field    = $col_info[$g_fldidx_name];
						$show     = $col_info[$g_fldidx_show];

						if ($show == "true") {
							$val = ($field == "parent_sid" && !is_null($kind_parent)) ? $kind_parent[$rows[$col]] : $rows[$col];
							$val_str = is_null($val) ? '' : (string)$val;

							// 強制宣告為字串型態寫入
							$activeSheet->setCellValueExplicitByColumnAndRow($col, $row, $val_str, PHPExcel_Cell_DataType::TYPE_STRING);

							// 計算欄寬
							$str_width = mb_strwidth($val_str, 'UTF-8');
							if (!isset($column_widths[$col]) || $str_width > $column_widths[$col]) {
								$column_widths[$col] = $str_width;
							}

							$col++;
						}
					}

					$row_pos = $row - 1;
					$percent = intval($row_pos / $records * 100);
					if ($percent == 100) $percent = 99;
					$row++;

					$db->modifyProgress($link, $member_id, $file_tmp, $percent, "export");
				}
			}

			// 4. 一次性將「資料區域範圍」強制統一設定為文字格式
			$maxCol = $activeSheet->getHighestColumn();
			$maxRow = $activeSheet->getHighestRow();
			$activeSheet->getStyle("A1:{$maxCol}{$maxRow}")
						->getNumberFormat()
						->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

			// 5. 批次套用欄寬設定
			foreach ($column_widths as $col_idx => $max_width) {
				$final_width = max($max_width + 3, 12);
				$activeSheet->getColumnDimensionByColumn($col_idx)->setWidth($final_width);
			}

			// 6. 匯出與存檔
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$file_name = $file_tmp . '.xlsx';
			$j = 1;

			while (file_exists($g_xlsx_out_path . $file_name)) {
				$file_name = $file_tmp . '_' . $j . '.xlsx';
				$j++;
			}

			$objWriter->save($g_xlsx_out_path . $file_name);

			$percent = 100;
			$db->modifyProgress($link, $member_id, $file_tmp, $percent, "export");
			$ret = true;
		}

		return $ret;
	}
	function getPdctKind($table, $remote_ip, $member_id, $swap = false) {
		$ret = []; $i = 0;
		$db = new CXDB($remote_ip);
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$result = $db->getData($link, $table, "", "*", "");
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result)) {
						$key = $row['name']; $value = $row['sid' ];
						if ($swap) {
							$ret[$value] = $key;
						} else {
							$ret[$key] = $value;
						}
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getPdct($table, $remote_ip, $member_id) {
		$ret = []; $i = 0;
		$db = new CXDB($remote_ip);
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$result = $db->getData($link, $table, "", "*", "");
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result)) {
						$key = $row['title']; $value = $row['sid' ];
						$ret[$key] = $value;
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getPdctKindWithAllParent($table, $remote_ip, $member_id, $swap_key_value = false) {
		$ret = []; $i = 0;
		$db = new CXDB($remote_ip);
		$table = substr($table, 0, strlen($table) - 2);
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$res_layer03 = $db->getData($link, $table.'03', "", "*", "");
				if (!is_null($res_layer03) && mysqli_num_rows($res_layer03) > 0) {
					while ($row03 = mysqli_fetch_array($res_layer03)) {
						$key_03 = $row03['name']; $value = $row03['sid' ]; $parent = $row03['parent_sid' ];
						
						$key_01 = ""; $key_02 = "";
						$res_layer02 = $db->getData($link, $table.'02', "", "*", "AND sid='$parent'");
						if (!is_null($res_layer02) && mysqli_num_rows($res_layer02) > 0) {
							if ($row02 = mysqli_fetch_array($res_layer02)) {
								$key_02 = $row02['name']; $parent_02 = $row02['parent_sid' ];
								
								$res_layer01 = $db->getData($link, $table.'01', "", "*", "AND sid='$parent_02'");
								if (!is_null($res_layer01) && mysqli_num_rows($res_layer01) > 0) {
									if ($row01 = mysqli_fetch_array($res_layer01)) {
										$key_01 = $row01['name'];
									}
								}
							}
						}
						$key = $key_01.'>'.$key_02.'>'.$key_03;
						if ($swap_key_value)
							$ret[$value] = $key;
						else
							$ret[$key] = $value;
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getPdctDetlWithAllParent($table, $remote_ip, $member_id, $swap_key_value = false) {
		$ret = []; $i = 0;
		$db = new CXDB($remote_ip);
		$table = substr($table, 0, strlen($table) - 2);
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$res_pdct = $db->getData($link, 'data_product', "", "*", "");
				if (!is_null($res_pdct) && mysqli_num_rows($res_pdct) > 0) {
					while ($row_pdct = mysqli_fetch_array($res_pdct)) {
						$key_pdct = $row_pdct['title']; $value = $row_pdct['sid' ]; $parent = $row_pdct['parent_sid' ];

						$res_layer03 = $db->getData($link, $table.'03', "", "*", "AND sid='$parent'");
						if (!is_null($res_layer03) && mysqli_num_rows($res_layer03) > 0) {
							while ($row03 = mysqli_fetch_array($res_layer03)) {
								$key_03 = $row03['name']; $parent = $row03['parent_sid' ];
								
								$key_01 = ""; $key_02 = "";
								$res_layer02 = $db->getData($link, $table.'02', "", "*", "AND sid='$parent'");
								if (!is_null($res_layer02) && mysqli_num_rows($res_layer02) > 0) {
									if ($row02 = mysqli_fetch_array($res_layer02)) {
										$key_02 = $row02['name']; $parent_02 = $row02['parent_sid' ];
										
										$res_layer01 = $db->getData($link, $table.'01', "", "*", "AND sid='$parent_02'");
										if (!is_null($res_layer01) && mysqli_num_rows($res_layer01) > 0) {
											if ($row01 = mysqli_fetch_array($res_layer01)) {
												$key_01 = $row01['name'];
											}
										}
									}
								}
							}
						}
						$key = $key_01.'>'.$key_02.'>'.$key_03.'>'.$key_pdct;
						if ($swap_key_value)
							$ret[$value] = $key;
						else
							$ret[$key] = $value;
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getPdctSidByZhtw($input_str, $remote_ip, $member_id, $swap_key_value = false) {
		$ret = ''; $i = 0;
		$db = new CXDB($remote_ip);
		$table_pdct_kind = 'info_productkind';
		$seperate_array = array();
		if (stripos($input_str, '>') === false) {
			return $ret;
		} else {
			$seperate_array = explode('>', $input_str);
		}
		if (count($seperate_array) != 4) return $ret;
		
		$sid = '';
		try {
			$found_01 = false; $found_02 = false; $found_03 = false;
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$pdct_name = $seperate_array[3];
				$res_pdct = $db->getData($link, 'data_product', "", "*", "AND title='$pdct_name'");// 屋內照明>基礎設施照明>LED天井燈>專案款CH034系列
				if (!is_null($res_pdct) && mysqli_num_rows($res_pdct) > 0) {
					while ($row_pdct = mysqli_fetch_array($res_pdct)) {
						$parent = $row_pdct['parent_sid']; $sid = $row_pdct['sid'];
						$found_03 = false;
						$res_layer03 = $db->getData($link, $table_pdct_kind.'03', "", "*", "AND sid='$parent'");
						if (!is_null($res_layer03) && mysqli_num_rows($res_layer03) > 0) {
							while ($row03 = mysqli_fetch_array($res_layer03)) {
								$key_03 = $row03['name']; $parent = $row03['parent_sid'];
								$kind_03 = $seperate_array[2];
								if ($key_03 == $kind_03) {
									$found_03 = true;
									break;
								}
							}
						}
						$found_02 = false;
						$res_layer02 = $db->getData($link, $table_pdct_kind.'02', "", "*", "AND sid='$parent'");
						if (!is_null($res_layer02) && mysqli_num_rows($res_layer02) > 0) {
							while ($row02 = mysqli_fetch_array($res_layer02)) {
								$key_02 = $row02['name']; $parent = $row02['parent_sid'];
								$kind_02 = $seperate_array[1];
								if ($key_02 == $kind_02) {
									$found_02 = true;
									break;
								}
							}
						}
						$found_01 = false;
						$res_layer01 = $db->getData($link, $table_pdct_kind.'01', "", "*", "AND sid='$parent'");
						if (!is_null($res_layer01) && mysqli_num_rows($res_layer01) > 0) {
							while ($row01 = mysqli_fetch_array($res_layer01)) {
								$key_01 = $row01['name'];
								$kind_01 = $seperate_array[0];
								if ($key_01 == $kind_01) {
									$found_01 = true;
									break;
								}
							}
						}
						if ($found_01 && $found_02 && $found_03) {
							$ret = $sid;
							break;
						}
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getPdctListByLayer3Zhtw($input_str, $remote_ip, $member_id) {
		$ret = '';
		$db = new CXDB($remote_ip);
		$table_pdct_kind = 'info_productkind';

		$sid = '';
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$res_layer03 = $db->getData($link, $table_pdct_kind.'03', "", "*", "AND name='$input_str'");
				if (!is_null($res_layer03) && mysqli_num_rows($res_layer03) > 0) {
					if ($row03 = mysqli_fetch_array($res_layer03)) {
						$sid = $row03['sid'];
					}
				}
			}
			$ret = $sid;
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getPdctListByLayerxCore($input_str, $remote_ip, $member_id, &$layer) {
		$ret = '';
		$db = new CXDB($remote_ip);
		$table_pdct_kind = 'info_productkind%02d';
		$sid = '';
		
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$res_layer = $db->getData($link, sprintf($table_pdct_kind, $layer), "", "*", $input_str);
				if (!is_null($res_layer) && mysqli_num_rows($res_layer) > 0) {
					while ($row_layer = mysqli_fetch_array($res_layer)) {
						$sid.= (strlen($sid) > 0) ? ',' : '';
						$sid.= '\''.$row_layer['sid'].'\'';
					}
				}
			}
			$ret = $sid;
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getPdctListByLayerxZhtw($input_str, $remote_ip, $member_id) {
		$ret = '';
		$table_pdct_kind = 'info_productkind%02d';

		$sid = '';
		$array_input = array();
		if (stripos($input_str, '>') === false) {
			array_push($array_input, $input_str);
		} else {
			$array_input = explode('>', $input_str);
		}
		$layer = count($array_input);
		
		try {
			if ($layer > 0) {
				// for ($i = $layer; $i <= 3; $i++) {
					$condiction_str = $array_input[$layer - 1];
					$sid = getPdctListByLayerxCore("AND name='$condiction_str'", $remote_ip, $member_id, $layer);
					// echo 'layer :'.$layer.' :'.$sid;
					do {
						if ($layer < 3) {
							$layer++;
							$where_str = '';
							if (stripos($sid, '\',\'') === false) {
								if (!empty($sid)) $where_str = 'AND parent_sid='.$sid.'';
							} else {
								if (!empty($sid)) $where_str = 'AND parent_sid in ('.$sid.')';
							}
							// echo $where_str;
							if (!empty($where_str)) {
								$sid = getPdctListByLayerxCore($where_str, $remote_ip, $member_id, $layer);
							}
							// echo 'layer :'.$layer.' :'.$sid;
						}
					} while ($layer < 3);
				// }
				$ret = $sid;
			}
		} catch (Exception $e) {
		} finally {
		}
		return $ret;
	}
	
	// JTG_modifyshopping 使用
	// input : 	產品序號: 屋內照明>基礎設施照明>LED天井燈>專案款CH034系列
	//			型號序號: 屋內照明>基礎設施照明>LED天井燈>專案款CH034系列>B075BZ8(W/D/C)-FE9EQ
	function getOnePdctModelSidByZhtw($input_str, $remote_ip, $member_id, &$is_modelmode, &$price) {
		global $g_db_table;
		$ret = ''; $i = 0;
		$db = new CXDB($remote_ip);
		$table_pdct_kind = 'info_productkind';
		$seperate_array = array();
		$price = '';
		if (stripos($input_str, '>') === false) {
			return $ret;
		} else {
			$seperate_array = explode('>', $input_str);
		}
		$table = ''; $srch_pdct = '';
		if (count($seperate_array) == 4) {		// 取得產品序號
			$table = $g_db_table['dataproduct'];
			$is_modelmode = false;
			$srch_pdct = 'title';
		} else if (count($seperate_array) == 5) {	// 取得型號序號
			$table = $g_db_table['dataproductdetl'];
			$is_modelmode = true;
			$srch_pdct = 'model_sid';
		}

		if (empty($table)) return $ret;

		$sid = '';
		try {
			$found_01 = false; $found_02 = false; $found_03 = false; $found_pdct = false;
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$pdct_name = $seperate_array[count($seperate_array) - 1];
				$res_pdct = $db->getData($link, $table, "", "*", "AND $srch_pdct='$pdct_name'");
				if (!is_null($res_pdct) && mysqli_num_rows($res_pdct) > 0) {
					while ($row_pdct = mysqli_fetch_array($res_pdct)) {

						$sid = $row_pdct['sid']; // 取得結果
						if ($is_modelmode) $price = $row_pdct['price']; // 取得單價
						
						$parent_sid = $row_pdct['parent_sid']; $found_03 = false;
						if ($table == $g_db_table['dataproduct']) {
							$found_pdct = true;
							$res_layer03 = $db->getData($link, $table_pdct_kind.'03', "", "*", "AND sid='$parent_sid'");
							if (!is_null($res_layer03) && mysqli_num_rows($res_layer03) > 0) {
								while ($row03 = mysqli_fetch_array($res_layer03)) {
									$key_03 = $row03['name']; $parent_sid = $row03['parent_sid'];
									$kind_03 = $seperate_array[count($seperate_array) - 2];
									if ($key_03 == $kind_03) {
										$found_03 = true;
										break;
									}
								}
							}
						} else if ($table == $g_db_table['dataproductdetl']) {
							$res_pdct = $db->getData($link, $g_db_table['dataproduct'], "", "*", "AND sid='$parent_sid'");
							if (!is_null($res_pdct) && mysqli_num_rows($res_pdct) > 0) {
								while ($row_pdct = mysqli_fetch_array($res_pdct)) {
									$key_pdct = $row_pdct['title']; $parent_sid = $row_pdct['parent_sid'];
									$kind_pdct = $seperate_array[count($seperate_array) - 2];
									if ($key_pdct == $kind_pdct) {
										$found_pdct = true;
										break;
									}
								}
							}
							$res_layer03 = $db->getData($link, $table_pdct_kind.'03', "", "*", "AND sid='$parent_sid'");
							if (!is_null($res_layer03) && mysqli_num_rows($res_layer03) > 0) {
								while ($row03 = mysqli_fetch_array($res_layer03)) {
									$key_03 = $row03['name']; $parent_sid = $row03['parent_sid'];
									$kind_03 = $seperate_array[count($seperate_array) - 3];
									if ($key_03 == $kind_03) {
										$found_03 = true;
										break;
									}
								}
							}
						}
						if ($found_pdct && $found_03) {
							$ret = $sid;
							break;
						}
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	function getGridColWidth($field)
	{
		global $g_grid_col_width;
		$style = "";
		// foreach ($g_grid_col_width as $key => $value) {
		// 	if ($field == $key) {
		// 		$style = 'width: '.$value.';';
		// 		break;
		// 	}
		// }
		return $style;
	}
	function getPdctKindStrByPntSid($kind_parent, $sql_value)
	{
		$ret = "";
		if (is_null($kind_parent)) return $ret;
		foreach ($kind_parent as $key => $value) {
			if ($sql_value == $value) {
				$ret = $key;
				break;
			}
		}
		return $ret;
	}

	// 取得發票資訊陣列
	function getInvoice($remote_ip, $link, $db, $invoice_sid, $avalible, $member_id, $mobile_uid)
	{
		global $g_db_table;
		$table = $g_db_table["loginvoice"]; $where_str = "";
		$not_send_column = 'null,script,remark';
        $query_rows       = array();
        $query_rows_tmp   = array();
        $query_fields_tmp = array();

		if (empty($invoice_sid) ||
			(empty($member_id) && empty($mobile_uid))) return $query_rows;

		if (!empty($member_id )) $where_str.= " AND sid='".$invoice_sid."'";
		if (!empty($member_id )) $where_str.= " AND member_sid='".$member_id."'";
		if (!empty($mobile_uid)) $where_str.= " AND mobile_uid='".$mobile_uid."'";
		if (!empty($avalible  )) $where_str.= " AND avalible='".$avalible."'";
		$result = $db->getData($link, $table, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			$query_fields_tmp = $db->getFieldsComment($link, $table, $not_send_column);
			while ($row = mysqli_fetch_assoc($result)) {
				array_push($query_rows_tmp, $row);
			}
			$query_rows["invoice_fields"] = $query_fields_tmp;
			$query_rows["invoice_data" ] = $query_rows_tmp;
		}
		return $query_rows;
	}
	// 取得訂單明細陣列
	function getOrderDetail($remote_ip, $link, $db, $order_sid, $avalible, $member_id, $mobile_uid)
	{
		global $g_db_table;
		$table_orderdetl = $g_db_table["logorderdetail" ];
		$table_pdct      = $g_db_table["dataproduct"	];
		$table_pdctdetl  = $g_db_table["dataproductdetl"];
		$where_str = "";
		$not_send_column = 'null,script,remark';
        $query_rows       = array();
        $query_rows_tmp   = array();
        $query_fields_tmp = array();
        $query_invoice = array();

		if (empty($order_sid) ||
			(empty($member_id) && empty($mobile_uid))) return $query_rows;
		// 訂單明細
		if (!empty($order_sid )) $where_str.= " AND ODRD.order_sid='".$order_sid."'";
		if (!empty($member_id )) $where_str.= " AND ODRD.member_sid='".$member_id."'";
		if (!empty($avalible  )) $where_str.= " AND ODRD.avalible='".$avalible."'";
		$sql = "SELECT PDT.title,PDT.product_img,PDTd.model_name,PDT.sid AS psid,PDTd.sid, PDTd.avalible, PDTd.price AS cur_price
			   ,ODRD.product_sid,ODRD.model_sid,ODRD.quentity,ODRD.unit,ODRD.price
			   ,ODRD.calc_price, ODRD.create_date
			   
			   , CASE
                        WHEN ODRD.order_status='Y' THEN '訂單成立'
                        WHEN ODRD.order_status='N' THEN '訂單取消'
                        WHEN ODRD.order_status='B' THEN '退貨申請中'
                        WHEN ODRD.order_status='F' THEN '訂單已完成'
                    END AS order_status

			   , CASE
                        WHEN ODRD.pay_status='Y' THEN '已收款'
						ELSE '未收款'
                    END AS pay_status

			   ,ODRD.avalible
				FROM $table_orderdetl AS ODRD
				LEFT JOIN $table_pdctdetl AS PDTd ON PDTd.sid=ODRD.model_sid
				LEFT JOIN $table_pdct AS PDT ON PDT.sid=PDTd.parent_sid
                    WHERE 1=1 $where_str;";
        // echo $sql;
        $result = $db->query($link, $sql);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				// 訂單明細 - 發票資訊
				// unset($query_invoice); $query_invoice = array(); // Destroy the array // Reassign an empty array
				// $query_invoice = getInvoice($remote_ip, $link, $db, $row['invoice_sid'], $avalible, $member_id, $mobile_uid);

				// array_push($query_rows_tmp, $row, $query_invoice);
				array_push($query_rows_tmp, $row);
			}
			// $query_rows["detail_data" ] = $query_rows_tmp;
			$query_rows = $query_rows_tmp;
		}
		return $query_rows;
	}
    function get_country_dropdown()
    {
		global $g_countries_options;
        $str = array();
        $countries = $g_countries_options;
        foreach ($countries as $k => $v)
            $str[] = "$v:$v";

        return implode(";",$str);
    }
    function getKeyVal2App($input_array)
    {
        $ret_array = array();
        foreach ($input_array as $k => $v)
            array_push($ret_array, ["key" => $k, "value" => $v]);

        return $ret_array;
    }
    function getKeyVal4Sales($member_id)
    {
		global $g_db_table, $g_role_options, $g_member_avalible_code;
		
		$role_array = $g_role_options;
		$avalible_array = $g_member_avalible_code;
        $ret_array = array();
		$remote_ip = get_remote_ip();
		$db = new CXDB($remote_ip);
		try {
			$table_member = $g_db_table["datamember"];
	
			$where_str = '';
			$query_rows = array();
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$where_str.= "AND avalible='".$avalible_array["[Y]審核通過"]."'";
				$where_str.= "AND role='".$role_array["[Smn]業務"]."'";
				$result = $db->getData($link, $table_member, "", "*", $where_str);
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_assoc($result)) {
						$key = $row["name"]; $value = $row["sid"];
						$ret_array[$key] = $value;
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
		}
        return $ret_array;
    }
    function getSalesName($sales_array, $sale_sid)
	{
		$ret_str = "";
		foreach ($sales_array as $key => $value) {
			if ($value == $sale_sid) {
				$ret_str = $key;
				break;
			}
		}
		return $ret_str;
	}

	// 取得訂單陣列
	function getPdctData($remote_ip, $link, $db, $sid, $avalible, $member_id, $mobile_uid)
	{
		global $g_db_table;
		$table_pdct = $g_db_table["dataproduct"]; // $table_pdctdetl = $g_db_table["dataproductdetl"];
		$not_send_column = 'null,script,remark';
        $query_rows       = array();
        $query_rows_tmp   = array();
        $query_fields_tmp = array();

		if (empty($sid) ||
			(empty($member_id) && empty($mobile_uid))) return $query_rows;

		$having_str = ''; $parent_sid = $sid;
		if (stripos($parent_sid, '\',\'') === false) {
			if (!empty($parent_sid)) $having_str = 'PDT.sid="'.$parent_sid.'"';
		} else {
			if (!empty($parent_sid)) $having_str = 'PDT.sid IN ('.$parent_sid.')';
		}
		// 訂單資訊
		if (empty($having_str)) {
			$query_rows["fields"] = $query_fields_tmp;
			$query_rows["data" ] = $query_rows_tmp;
		} else {
			$sql = "SELECT PDT.*,COUNT(PDS.sid) AS spec_quantity,CONCAT(MIN(PDS.price), '~', MAX(PDS.price)) AS price_range
					FROM data_product AS PDT
					JOIN data_productdetail AS PDS ON PDS.parent_sid = PDT.sid
					GROUP BY PDT.sid
					HAVING $having_str;";
					// echo $sql;
			$result = $db->query($link, $sql);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				$query_fields_tmp = $db->getFieldsComment($link, $table_pdct, $not_send_column);
				while ($row = mysqli_fetch_assoc($result)) {
					array_push($query_rows_tmp, $row);
				}
				$query_rows["fields"] = $query_fields_tmp;
				$query_rows["data" ] = $query_rows_tmp;
			}
		}
		if (isset($query_rows["data"]) == false) $query_rows["data"] = array();
		return $query_rows;
	}
	// 取得訂單明細陣列
	function getPdctData2($remote_ip, $link, $db, $sid, $avalible, $member_id, $mobile_uid)
	{
		global $g_db_table;
		$table_pdct = $g_db_table["dataproduct"]; // $table_pdctdetl = $g_db_table["dataproductdetl"];
		$not_send_column = 'null,script,remark';
        $data_ret       = array();
        $rows_ret       = array();
        $query_rows       = array();
        $query_rows_tmp   = array();
        $query_fields_tmp = array();

		if (empty($sid) ||
			(empty($member_id) && empty($mobile_uid))) return $query_rows;

		$where_str = ''; $having_str = ''; $parent_sid = $sid;
		if (stripos($parent_sid, '\',\'') === false) {
			if (!empty($parent_sid)) $where_str = 'AND PDT.sid="'.$parent_sid.'" AND PDT.avalible="Y"';
			if (!empty($parent_sid)) $having_str = 'PDT.sid="'.$parent_sid.'"';
		} else {
			if (!empty($parent_sid)) $where_str = 'AND PDT.sid in ('.$parent_sid.') AND PDT.avalible="Y"';
			if (!empty($parent_sid)) $having_str = 'PDT.sid IN ('.$parent_sid.')';
		}
		// 訂單資訊
		$row_count = 0;
		if (!empty($where_str)) {
			$sql = "SELECT COUNT(*) as total_rows FROM $table_pdct AS PDT WHERE 1=1 $where_str;";
			$rs = $db->query($link, $sql);
			if (!is_null($rs) && mysqli_num_rows($rs) > 0) {
				$row = mysqli_fetch_array($rs);
				$row_count = intval($row['total_rows']);
			}
		}

		if (empty($having_str)) {
			$query_rows["fields"] = $query_fields_tmp;
			$query_rows["data" ] = $query_rows_tmp;
		} else {
			$sql = "SELECT PDT.*,COUNT(PDS.sid) AS spec_quantity,CONCAT(MIN(PDS.price), '~', MAX(PDS.price)) AS price_range
					FROM data_product AS PDT
					LEFT JOIN data_productdetail AS PDS ON PDS.parent_sid = PDT.sid
					GROUP BY PDT.sid
					HAVING $having_str;";
					// echo $sql;
			$result = $db->query($link, $sql);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				$query_fields_tmp = $db->getFieldsComment($link, $table_pdct, $not_send_column);
				while ($row = mysqli_fetch_assoc($result)) {
					$query_rows["main"] = $row; $pdct_psid = $row["parent_sid"];
                    $query_rows["pdct_kind"] = getPdctKindCaption($remote_ip, $member_id, $pdct_psid);
                        
					unset($rows_detl_tmp); $rows_detl_tmp = array();  // Destroy the array // Reassign an empty array
					$table_detl = $g_db_table["dataproductdetl"];
					if (!empty($row["sid"])) $where_str = 'AND parent_sid="'.$row["sid"].'"';
					$result_detl = $db->getData($link, $table_detl, "", "*", $where_str);
					if (!is_null($result_detl) && mysqli_num_rows($result_detl) > 0) {
						while ($row_detl = mysqli_fetch_assoc($result_detl)) {
							array_push($rows_detl_tmp, $row_detl);
						}
					}
					$query_rows["detail"] = $rows_detl_tmp;
					array_push($rows_ret, $query_rows);
				}
				$data_ret["data"      ] = $rows_ret;
				$data_ret["total_rows"] = $row_count;
			}
		}
		return $data_ret;
	}
	// 取得購物車型號陣列
	function getPdctDetl($remote_ip, $link, $db, $sid, $avalible, $member_id, $mobile_uid, $calc_price = 0)
	{
		global $g_db_table;
		$table_pdct = $g_db_table["dataproduct"]; $table_pdctdetl = $g_db_table["dataproductdetl"];
		$not_send_column = 'null,script,remark';
        $data_ret       = array();
        $rows_ret       = array();
        $query_rows       = array();
        $query_rows_tmp   = array();
        $query_fields_tmp = array();

		if (empty($sid) ||
			(empty($member_id) && empty($mobile_uid))) return $query_rows;

		$where_str = ''; $having_str = ''; $parent_sid = $sid;
		if (stripos($parent_sid, '\',\'') === false) {
			if (!empty($parent_sid)) $where_str = 'AND PDS.sid="'.$parent_sid.'" AND PDS.avalible="Y"';
			if (!empty($parent_sid)) $having_str = 'PDS.sid="'.$parent_sid.'"';
		} else {
			if (!empty($parent_sid)) $where_str = 'AND PDS.sid in ('.$parent_sid.') AND PDS.avalible="Y"';
			if (!empty($parent_sid)) $having_str = 'PDS.sid IN ('.$parent_sid.')';
		}

		if (empty($where_str)) {
			$query_rows["model_fidlds"] = $query_fields_tmp;
			$query_rows["data" 		  ] = $query_rows_tmp;
		} else {
			$sql = "SELECT PDS.*,PDT.product_img, PDT.parent_sid AS pr_sid, PDT.sid AS psid, PDT.title
					FROM $table_pdctdetl AS PDS
					LEFT JOIN $table_pdct AS PDT ON PDT.sid = PDS.parent_sid
					WHERE 1=1 $where_str;";
			// echo $sql;
			$result = $db->query($link, $sql);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				$query_fields_tmp = $db->getFieldsComment($link, $table_pdctdetl, $not_send_column);
				$query_rows["model_fidlds"] = $query_fields_tmp;
				while ($row = mysqli_fetch_assoc($result)) {
					$row["calc_price"] = $calc_price;
					$query_rows["model_data"] = $row; $pdct_psid = $row["pr_sid"];
                    $query_rows["pdct_kind" ] = getPdctKindCaption($remote_ip, $member_id, $pdct_psid);
					array_push($rows_ret, $query_rows);
				}
				$data_ret["data"] = $rows_ret;
			}
		}
		if (isset($data_ret["data"]) == false) $data_ret["data"] = array();
		return $data_ret;
	}
	
	function getPdctKindCaption($remote_ip, $member_id, $parent_sid) {
		$ret = "";
		$table = 'info_productkind';
		$db = new CXDB($remote_ip);
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$res_layer03 = $db->getData($link, $table.'03', "", "*", "AND sid='$parent_sid'");
				if (!is_null($res_layer03) && mysqli_num_rows($res_layer03) > 0) {
					while ($row03 = mysqli_fetch_array($res_layer03)) {
						$key_03 = $row03['name']; $parent = $row03['parent_sid' ];
						
						$key_01 = ""; $key_02 = "";
						$res_layer02 = $db->getData($link, $table.'02', "", "*", "AND sid='$parent'");
						if (!is_null($res_layer02) && mysqli_num_rows($res_layer02) > 0) {
							if ($row02 = mysqli_fetch_array($res_layer02)) {
								$key_02 = $row02['name']; $parent_02 = $row02['parent_sid' ];
								
								$res_layer01 = $db->getData($link, $table.'01', "", "*", "AND sid='$parent_02'");
								if (!is_null($res_layer01) && mysqli_num_rows($res_layer01) > 0) {
									if ($row01 = mysqli_fetch_array($res_layer01)) {
										$key_01 = $row01['name'];
									}
								}
							}
						}
					}
				}
				$ret = $key_01.'>'.$key_02.'>'.$key_03;
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
		return $ret;
	}
	// 取得我的最愛
	function getFavShopCarState($remote_ip, $link, $db, $table, $sid, $member_id, $mobile_uid)
	{
		global $g_YN_options;
		$avalible_array = $g_YN_options;
		$where_str = "";
        $ret = "false";

		if (empty($sid) ||
			(empty($member_id) && empty($mobile_uid))) return $ret;
			
		if (!empty($member_id )) $where_str.= " AND (product_sid='".$sid."' OR model_sid='".$sid."')";
		if (!empty($member_id )) $where_str.= " AND member_sid='".$member_id."'";
		if (!empty($mobile_uid)) $where_str.= " AND mobile_uid='".$mobile_uid."'";
		$result = $db->getData($link, $table, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			if ($row = mysqli_fetch_array($result)) {
				$ret = ($row["avalible"] == $avalible_array['[Y]加入']) ? "true" : "false";
			}
		}
		return $ret;
	}
	function getTableData($remote_ip, $link, $db, $table, $sid, $member_id, $mobile_uid, $where_str, $avalible, $order_by, $get_all)
	{
		$query_rows = array();
		$result = $db->getData($link, $table, $member_id, "*", $where_str, $avalible, $order_by);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				array_push($query_rows, $row);
				if ($get_all != "Y") break;
			}
		}
		return $query_rows;
	}
	
	function updateSales($remote_ip, $link, $db, $table_member, $uid, $member_id)
	{
		$ret_msg = ""; $sql_param = ''; $nid = -1; $where_str = '';
		if (!empty($member_id)) $where_str.= " AND mid='".$member_id."'";
		$result = $db->getData($link, $table_member, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row["nid"];
			}
			$val = isset($_POST["sales_specify"]) ? $_POST["sales_specify"] : '';
			if (!empty($val)) $sql_param = 'sales_specify="'.$val.'"';
		}
		if (!empty($sql_param) && $nid != -1) {
			$sql = 'UPDATE '.$table_member.' SET '.$sql_param.' WHERE nid='.$nid.';';
			if ($db->execute($link, $sql, $ret_msg) > 0) {}
		}
	}
	function insertLogFcm($API_name, $who_call, $caption, $db, $link, $fcm_sid, $member_id, $avalible, $remark) {
		global $g_db_table;
		$table_logfcm = $g_db_table["logfcm2members"];

		$data = array(); $null_array = array();
		$ret_msg = "";
        $sid = getSidSimple($table_logfcm, $member_id, "FCM");
		$sql = 'INSERT INTO '.$table_logfcm.' (`sid`, `create_date`, `fcm_sid`, `member_sid`, `avalible`, `remark`) VALUES (\''.$sid.'\',NOW(),\''.$fcm_sid.'\',\''.$member_id.'\',\''.$avalible.'\',\''.$remark.'\');';
		if ($db->execute($link, $sql, $ret_msg) > 0) {
			$data = result_message("true", "0x0200", "新增 $caption 成功", $null_array);
			$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
			$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
		} else {
			$null_array["err"] = $ret_msg;
			$data    = result_message("false", "0x0206", "新增 $caption 失敗", $null_array);
			$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
			$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
		}
		return $data;
	}
	function modifyRecvaddr($who_call, $API_name, $remote_ip, $link, $db, $table, $sid, $operate, $uid, $recv_sid, $member_id
						  , $column_info, $empty_fields
						  , $caption, $get_all, $skip, $avalible, $order_by, &$recvaddr_sid, $mobile_uid='')
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		
		$data = array(); $null_array = array();

		// 返回-參數不齊全
		if ($skip) {
			$empty_fields_zhtw = "";
			for ($i = 0; $i < count($column_info); $i++) {
				$com = $column_info[$i];
				if (stripos($empty_fields, $com[$g_fldidx_name]) != false) {
					$empty_fields_zhtw.= (empty($empty_fields_zhtw)) ? "" : ",";
					$empty_fields_zhtw.= $com[$g_fldidx_comment];
				}
			}
			$ret_str= "新增 [ $caption ] 資料異常，API 參數不全!「 $empty_fields_zhtw 」不可為空值";
			$data = result_message("false", "0x0206", $ret_str, $null_array);
			return $data;
		}

		// echo "sid :".$sid;
		// 搜尋資料前準備
		$where_str = '';
		if (!empty($sid)) $where_str.= " AND sid='".$sid."'";
		if (!empty($member_id)) $where_str.= " AND member_sid='".$member_id."'";
		for ($i = 0; $i < count($column_info); $i++) {
			$com = $column_info[$i];

			$field    = $com[$g_fldidx_name];
			$name     = $com[$g_fldidx_comment];
			$show     = ($com[$g_fldidx_show]         == "true");
			$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
			$search   = ($com[$g_fldidx_srch]         == "true");
			$lockedit = ($com[$g_fldidx_lockedit]     == "true");
			if ($show) {
				$val = isset($_POST[$field]) ? $_POST[$field] : '';
				if (strEndWith($field, '_date') && !empty($val)) $val = '';
				if ($field == 'birthday' && !empty($val)) $val = '';
				if (!empty($val)) $where_str.= " AND $field='".$val."'";
			}
		}
		// echo "where :".$where_str;
		// 搜尋資料
		$nid = -1;
		$srch_avalible = ($avalible == "D") ? 'Y' : 'D';
		$result = $db->getData($link, $table, $member_id, "*", $where_str, $srch_avalible, $order_by);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
		    if ($row = mysqli_fetch_array($result)) {
		        $nid = $row["nid"];
				$recvaddr_sid = $row["sid"];
		    }
		}

		// 找到資料
		$where_str = ''; $ret_msg = '';
		if ($operate == "delete") {
			if (!empty($sid)) $where_str.= " AND sid='".$sid."'";
			if (!empty($member_id)) $where_str.= " AND member_sid='".$member_id."'";
			if (!empty($sid)) {
				$sql = 'UPDATE '.$table.' SET avalible=\'D\' WHERE 1=1'.$where_str.';';

				if ($db->execute($link, $sql, $ret_msg) > 0) {
					$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
					$query_rows = $data["json"];
					$ret_str = '變更 '.$caption.' 資料成功 !';
					$data = result_message("true", "0x0200", $ret_str, $query_rows);
					$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
				} else {
					$ret_str = '變更 '.$caption.' 資料無效!';
					$data = result_message("false", "0x0206", $ret_str, array());
					$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
				}
			} else {
				$ret_str = $caption.' 沒有資料需刪除！';
				$data = result_message("false", "0x0206", $ret_str, array());
			}
		} else if ($operate == "edit") {
			$prev_data_array = array();
			$where_str.= " AND nid='".$nid."'";
			if (!empty($member_id)) $where_str.= " AND member_sid='".$member_id."'";
			
			// 取得現有收件資料
		    $result = $db->getData($link, $table, "", "*", $where_str);
		    if (!is_null($result) && mysqli_num_rows($result) > 0) {
		        if ($row = mysqli_fetch_array($result)) {
		            for ($i = 0; $i < count($column_info); $i++) {
		                $com  = $column_info[$i];
		                $show = ($com[$g_fldidx_show] == "true");
		                if ($show) {
		                    $field    = $com[$g_fldidx_name];
		                    $prev_data_array[$field] = $row[$field];
		                }
		            }
		        }
		    }

			// 搜尋變更項目
			$sql_param = ""; $new_value = "";
			for ($i = 0; $i < count($column_info); $i++) {
				$com = $column_info[$i];

				$field    = $com[$g_fldidx_name];
				$name     = $com[$g_fldidx_comment];
				$show     = ($com[$g_fldidx_show]         == "true");
				$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
				$search   = ($com[$g_fldidx_srch]         == "true");
				$lockedit = ($com[$g_fldidx_lockedit]     == "true");
				if ($show) {
					$val = isset($_POST[$field]) ? $_POST[$field] : '';
					if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
					if ($field == 'birthday' && !empty($val)) $val = getDateFormat($val);
					if ($field == 'receive_sid') $val = $recv_sid;

					// if (empty($new_value)) continue;
					foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
						if ($prev_key == $field && !empty($val) && $val != $prev_value) {
							$sql_param.= (strlen($sql_param) > 0) ? "," : "";
							$sql_param.= $field.'="'.$val.'"';
						}
					}
				}
			}

			// 更新資料
			if (!empty($sql_param)) {
				$sql = 'UPDATE '.$table.' SET '.$sql_param.' WHERE nid='.$nid.';';
				
				$ret_msg = "";
				if ($db->execute($link, $sql, $ret_msg) > 0) {
					if (!empty($member_id)) {
						$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
						$query_rows = $data["json"];
						// $query_rows = getTableData($remote_ip, $link, $db, $table, "", $member_id, "", $where_str, $avalible, $order_by, $get_all);
					}
					$ret_str = '變更 '.$caption.' 資料成功 !';
					$data = result_message("true", "0x0200", $ret_str, $query_rows);
					$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
				} else {
					$ret_str = '變更 '.$caption.' 資料無效!';
					$data = result_message("false", "0x0206", $ret_str, array());
					$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
				}
			} else {
				$ret_str = $caption.' 沒有資料待變更！';
				$data = result_message("false", "0x0206", $ret_str, array());
			}
		} else if ($operate == "insert") {
			$where_str = ""; $chk_data = '';
			$fields = "sid,create_date,member_sid";
			$values = '"'.$uid.'",NOW()'.',"'.$member_id.'"';
			$recvaddr_sid = $uid;
			for ($i = 0; $i < count($column_info); $i++) {
				$com = $column_info[$i];

				$field    = $com[$g_fldidx_name];
				$name     = $com[$g_fldidx_comment];
				$show     = ($com[$g_fldidx_show]         == "true");
				$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
				$search   = ($com[$g_fldidx_srch]         == "true");
				$lockedit = ($com[$g_fldidx_lockedit]     == "true");
				if ($show) {
					$val = isset($_POST[$field]) ? $_POST[$field] : '';
					if ($field == 'receive_sid') $val = $recv_sid;
					if (!empty($val)) {
						if (strEndWith($field, '_date')) { // 日期格式標準化
							$val = get24HourFormat($val);
						}
						if (!empty($val)) {
							$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
							$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							$chk_data.= " AND $field='".$val."'";
						}
					} else {
						if ($field == "avalible") {
							$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
							$values .= (strlen($values) > 0) ? "," : ""; $values .= '"Y"';
						} else if (strEndWith($field, '_date')) {
							$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
							$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
						}
					}
				}
			}
			if (!empty($member_id)) $chk_data.= " AND member_sid='".$member_id."'";
			$result = $db->getData($link, $table, $member_id, "*", $chk_data, $srch_avalible, $order_by);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				if (!empty($member_id)) {
					$where_str = " AND member_sid='".$member_id."'";
					$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
					$query_rows = $data["json"];
					// $query_rows = getTableData($remote_ip, $link, $db, $table, "", $member_id, "", $where_str, $avalible, $order_by, $get_all);
					// var_dump($query_rows);
				}
				$query_rows["err"] = "資料已存在";
				$data    = result_message("false", "0x0206", "新增 $caption 失敗", $query_rows);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
				return $data;
			}
			$ret_msg = "";
			$sql = 'INSERT INTO '.$table.' ('.$fields.') VALUES ('.$values.');';
			if ($db->execute($link, $sql, $ret_msg) > 0) {
				$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
				$query_rows = $data["json"];
				$data = result_message("true", "0x0200", "新增 $caption 成功", $query_rows);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			} else {
				$null_array["err"] = $ret_msg;
				$data    = result_message("false", "0x0206", "新增 $caption 失敗", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		// var_dump($data);
		return $data;
	}
	function chkDiffData4Recv($who_call, $API_name, $caption, $member_id,
							  $remote_ip, $link, $db, $table, $nid, $uid, $invoice_uid, $column_info)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		
		// 編輯
		$sql_param = ""; $where_str = "";
		$prev_data_array = array();
		if ($nid > -1) {
			$where_str.= " AND nid='".$nid."'";

			// 取得現有收件資料
			$result = $db->getData($link, $table, "", "*", $where_str);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				// 取得舊資料
				if ($row = mysqli_fetch_array($result)) {
					for ($i = 0; $i < count($column_info); $i++) {
						$com  = $column_info[$i];
						$show = ($com[$g_fldidx_show] == "true");
						if ($show) {
							$field    				 = $com[$g_fldidx_name];
							$prev_data_array[$field] = $row[$field];
						}
					}
				}
			}
			for ($i = 0; $i < count($column_info); $i++) {
				$com = $column_info[$i];

				$field    = $com[$g_fldidx_name];
				$name     = $com[$g_fldidx_comment];
				$show     = ($com[$g_fldidx_show]         == "true");
				$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
				$search   = ($com[$g_fldidx_srch]         == "true");
				$lockedit = ($com[$g_fldidx_lockedit]     == "true");
				if ($show) {
					$val = isset($_POST[$field]) ? $_POST[$field] : '';
					if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
					if ($field == 'birthday' 		&& !empty($val)) $val = getDateFormat($val);
					if ($field == 'invoice_sid'					   ) $val = $invoice_uid;

					// if (empty($new_value)) continue;
					foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
						if ($prev_key == $field && !empty($val) && $val != $prev_value) {
							$sql_param.= (strlen($sql_param) > 0) ? "," : "";
							$sql_param.= $field.'="'.$val.'"';
						}
					}
				}
			}
		}
		return $sql_param;
	}
	function insertRecv($operate, $who_call, $API_name, $remote_ip, $link, $db, $table, $uid, $member_id, $column_info , $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		
		$data = array(); $null_array = array();
		$fields = "sid,create_date,member_sid";
		$values = '"'.$uid.'",NOW()'.',"'.$member_id.'"';
		for ($i = 0; $i < count($column_info); $i++) {
			$com = $column_info[$i];

			$field    = $com[$g_fldidx_name];
			$name     = $com[$g_fldidx_comment];
			$show     = ($com[$g_fldidx_show]         == "true");
			$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
			$search   = ($com[$g_fldidx_srch]         == "true");
			$lockedit = ($com[$g_fldidx_lockedit]     == "true");
			if ($show) {
				$val = isset($_POST[$field]) ? $_POST[$field] : '';
				if (!empty($val)) {
					if (strEndWith($field, '_date')) { // 日期格式標準化
						$val = get24HourFormat($val);
					}
					if (!empty($val)) {
						$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
						$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
					}
				} else {
					if ($field == "avalible") {
						$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
						$values .= (strlen($values) > 0) ? "," : ""; $values .= '"Y"';
					} else if (strEndWith($field, '_date')) {
						$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
						$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
					}
				}
			}
		}

		switch ($operate) {
			case "insert": $operate_str = "新增"; break;
			case "edit"	 : $operate_str = "編輯";break;
		}
		$ret_msg = "";
		$sql = 'INSERT INTO '.$table.' ('.$fields.') VALUES ('.$values.');';
		if ($db->execute($link, $sql, $ret_msg) > 0) {
			$null_array["recv_sid"] = $uid;
			$data = result_message("true", "0x0200", $operate_str." $caption 資訊成功", $null_array);
			$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
			$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
		} else {
			$null_array["recv_sid"] = "";
			$null_array["error"   ] = $ret_msg;
			$data    = result_message("false", "0x0206", $operate_str." $caption 失敗", $null_array);
			$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
			$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
		}
		return $data;
	}
	function updateRecv($who_call, $API_name, $remote_ip, $link, $db, $table, $nid, $recv_sid, $member_id, $sql_param, $column_info , $caption, $json_token)
	{
		$data = array(); $null_array = array();
		$ret_msg = '';
		$null_array["recv_sid"] = $recv_sid;
		if (!empty($sql_param)) {
			$insert_log = false;
			$sql = 'UPDATE '.$table.' SET '.$sql_param.' WHERE nid='.$nid.';';
			
			if ($db->execute($link, $sql, $ret_msg) > 0) {
				$ret_str = '變更 '.$caption.' 資料成功 !';
				$data = result_message("true", "0x0200", $ret_str, $null_array);
				$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
			} else {
				$ret_str = '變更 '.$caption.' 資料無效!';
				$data = result_message("false", "0x0206", $ret_str, $null_array);
				$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
			}
		} else {
			$ret_str = $caption.' 沒有資料待變更！';
			$data = result_message("false", "0x0206", $ret_str, $null_array);
		}
		return $data;
	}
	function modifyRecv($operate, $who_call, $API_name, $remote_ip, $link, $db, $table, $uid, $invoice_uid, $member_id
					  , $column_info, $empty_fields
					  , $caption, $json_token, $skip)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		
		$ret_msg = ""; $recv_sid = ""; $chk_data = "";
		$data = array(); $null_array = array();

		// 返回-參數不齊全
		if ($skip) {
			$empty_fields_zhtw = "";
			for ($i = 0; $i < count($column_info); $i++) {
				$com = $column_info[$i];
				if (stripos($empty_fields, $com[$g_fldidx_name]) != false) {
					$empty_fields_zhtw.= (empty($empty_fields_zhtw)) ? "" : ",";
					$empty_fields_zhtw.= $com[$g_fldidx_comment];
				}
			}
			$ret_str= "新增 [ $caption ] 資料異常，API 參數不全!「 $empty_fields_zhtw 」不可為空值";
			$data = result_message("false", "0x0206", $ret_str, $null_array);
			return $data;
		}

		if (!empty($member_id)) $chk_data.= " AND member_sid='".$member_id."'";
        
		// 現有符合收件資料
		$nid = -1;
		$result = $db->getData($link, $table, "", "*", $chk_data);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			if ($row = mysqli_fetch_array($result)) {
				$nid	  = $row["nid"];
				$recv_sid = $row["sid"];
			}
			$null_array["recv_sid"] = $recv_sid;

			if ($nid > -1) {
				$sql_param = chkDiffData4Recv($who_call, $API_name, $caption, $member_id,
								$remote_ip, $link, $db, $table, $nid, $uid, $invoice_uid, $column_info);

				// 更新資料
				if (!empty($sql_param)) {
					$data = insertRecv($operate, $who_call, $API_name, $remote_ip, $link, $db, $table, $uid, $member_id , $column_info , $caption);
					// $data = updateRecv($who_call, $API_name, $remote_ip, $link, $db, $table, $nid, $recv_sid, $member_id, $sql_param, $column_info , $caption, $json_token);
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", "編輯資料異常，找不到對應的 ".$caption." 資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
				$data = insertRecv($operate, $who_call, $API_name, $remote_ip, $link, $db, $table, $uid, $member_id , $column_info , $caption);
			}
		} else {
			// 新增
			$data = insertRecv($operate, $who_call, $API_name, $remote_ip, $link, $db, $table, $uid, $member_id , $column_info , $caption);
		}
		return $data;
	}
	function modifyInvoice($who_call, $API_name, $remote_ip, $link, $db, $table, &$uid
					     , $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		
		$ret_msg = "";
		$data = array(); $null_array = array();

		// 搜尋資料前準備
		$chk_data = '';
		if (!empty($member_id)) $chk_data.= " AND member_sid='".$member_id."'";
		for ($i = 0; $i < count($column_info); $i++) {
			$com = $column_info[$i];

			$field    = $com[$g_fldidx_name];
			$name     = $com[$g_fldidx_comment];
			$show     = ($com[$g_fldidx_show]         == "true");
			$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
			$search   = ($com[$g_fldidx_srch]         == "true");
			$lockedit = ($com[$g_fldidx_lockedit]     == "true");
			if ($show) {
				$val = isset($_POST[$field]) ? $_POST[$field] : '';
				if (strEndWith($field, '_date') && !empty($val)) $val = '';
				if ($field == 'birthday' && !empty($val)) $val = '';
				if (!empty($val)) $chk_data.= " AND $field='".$val."'";
			}
		}
        
		// 現有符合收件資料
		$nid = -1;
		$result = $db->getData($link, $table, "", "*", $chk_data);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row["nid"];
				$uid = $row["sid"];
			}

			// 編輯
			$where_str = '';
			$prev_data_array = array();
			if ($nid > -1) $where_str.= " AND nid='".$nid."'";

			// 取得現有收件資料
			$result = $db->getData($link, $table, "", "*", $where_str);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				// 取得舊資料
				if ($row = mysqli_fetch_array($result)) {
					for ($i = 0; $i < count($column_info); $i++) {
						$com  = $column_info[$i];
						$show = ($com[$g_fldidx_show] == "true");
						if ($show) {
							$field    = $com[$g_fldidx_name];
							$prev_data_array[$field] = $row[$field];
						}
					}
				}
			}

			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($_POST[$field]) ? $_POST[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						if ($field == 'birthday' && !empty($val)) $val = getDateFormat($val);

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field && !empty($val) && $val != $prev_value) {
								$sql_param.= (strlen($sql_param) > 0) ? "," : "";
								$sql_param.= $field.'="'.$new_value.'"';
							}
						}
					}
				}

				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table.' SET '.$sql_param.' WHERE nid='.$nid.';';
					
					if ($db->execute($link, $sql, $ret_msg) > 0) {
						$ret_str = '變更 '.$caption.' 資料成功 !';
						$data = result_message("true", "0x0200", $ret_str, $null_array);
						$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, array());
						$db->saveLog($link, $member_id, $who_call.' 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", "編輯資料異常，找不到對應的發票資訊！", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		} else {
			// 新增
			$sql_param = ''; $nid = -1; $where_str = '';
			if (!empty($member_id)) $where_str.= " AND mid='".$member_id."'";
			$result = $db->getData($link, $table, "", "*", $where_str);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				if ($row = mysqli_fetch_array($result)) {
					$nid = $row["nid"];
				}
				$val = isset($_POST["sales_specify"]) ? $_POST["sales_specify"] : '';
				if (!empty($val)) $sql_param = 'sales_specify="'.$val.'"';
			}
			if (!empty($sql_param) && $nid != -1) {
				$sql = 'UPDATE '.$table.' SET '.$sql_param.' WHERE nid='.$nid.';';
				if ($db->execute($link, $sql, $ret_msg) > 0) {}
			}

			$fields = "sid,create_date,member_sid";
			$values = '"'.$uid.'",NOW()'.',"'.$member_id.'"';
			for ($i = 0; $i < count($column_info); $i++) {
				$com = $column_info[$i];

				$field    = $com[$g_fldidx_name];
				$name     = $com[$g_fldidx_comment];
				$show     = ($com[$g_fldidx_show]         == "true");
				$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
				$search   = ($com[$g_fldidx_srch]         == "true");
				$lockedit = ($com[$g_fldidx_lockedit]     == "true");
				if ($show) {
					$val = isset($_POST[$field]) ? $_POST[$field] : '';
					if (!empty($val)) {
						if (strEndWith($field, '_date')) { // 日期格式標準化
							$val = get24HourFormat($val);
						}
						if (!empty($val)) {
							$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
							$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
						}
					} else {
						if ($field == "avalible") {
							$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
							$values .= (strlen($values) > 0) ? "," : ""; $values .= '"Y"';
						} else if (strEndWith($field, '_date')) {
							$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
							$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
						}
					}
				}
			}

			$ret_msg = "";
			$sql = 'INSERT INTO '.$table.' ('.$fields.') VALUES ('.$values.');';
			if ($db->execute($link, $sql, $ret_msg) > 0) {
				$data = result_message("true", "0x0200", "新增 $caption 成功", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			} else {
				$null_array["err"] = $ret_msg;
				$data    = result_message("false", "0x0206", "新增 $caption 失敗", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function getSalesRecvaddr($remote_ip, $link, $db, $member_id)
	{
		global $g_db_table;
		$table_member = $g_db_table["datamember"];
		$ret_data = array();
		$sales_specify = ""; $recvaddr_sid = "";
		$result = $db->getData($link, $table_member, $member_id, "sales_specify,recvaddr_sid", " AND mid='".$member_id."'");
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			if ($row = mysqli_fetch_assoc($result)) {
				array_push($ret_data, $row);
			}
		}
		return $ret_data;
	}
	function JTG_updateAgency($API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_main = 'data_agency';
		$table_log  = 'log_agency';
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

		if (!empty($sid)) $where_str.= " AND sid='".$sid."'";
		// echo "where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合機構資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						if ($field == 'birthday' && !empty($val)) $val = getDateFormat($val);
						if ($field == 'recvaddr_sid' && empty($val) && !empty($uid_addr)) $val = $uid_addr;
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						if ($API_name == "JTG_modifyrecvaddr") {
							$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
							// $data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $uid_addr, $caption);
						} else if ($API_name == "JTG_modify_agency") {
							$data = result_message("true", "0x0200", $ret_str, $json_dst);
						}
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if ($insert_log) {
        				$uid = getSidSimple($table_log, $member_id, "LAG");
						$without_columns = "null,nid,sid,create_date";
						$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

						$fields = "sid,edit_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com = $column_info[$i];
							$field    = $com[$g_fldidx_name];
							$val = isset($input[$field]) ? $input[$field] : '';
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function JTG_updateAgencyunit($API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_main = 'data_agencyunit';
		$table_log  = 'log_agencyunit';
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

		if (!empty($sid)) $where_str.= " AND sid='".$sid."'";
		// echo "where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合機構資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						if ($field == 'birthday' && !empty($val)) $val = getDateFormat($val);
						if ($field == 'recvaddr_sid' && empty($val) && !empty($uid_addr)) $val = $uid_addr;
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						if ($API_name == "JTG_modifyrecvaddr") {
							$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
							// $data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $uid_addr, $caption);
						} else if ($API_name == "JTG_modify_agencyunit") {
							$data = result_message("true", "0x0200", $ret_str, $json_dst);
						}
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if ($insert_log) {
        				$uid = getSidSimple($table_log, $member_id, "LAV");
						$without_columns = "null,nid,sid,create_date";
						$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

						$fields = "sid,edit_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com = $column_info[$i];
							$field    = $com[$g_fldidx_name];
							$val = isset($input[$field]) ? $input[$field] : '';
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function JTG_updateMember($API_name, $who_call, $remote_ip, $link, $db, $member_id, $input, $column_info, $caption, $dst_filename, $pwd, $new_pwd, $uid_addr)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_member = $g_db_table["datamember"];
		$table_logmem = $g_db_table["logmember"];
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

		if (!empty($mid)) $where_str.= " AND mid='".$mid."'";
		if (!empty($new_pwd)) {
			if (empty($mid) || empty($pwd)) {
				if (empty($mid)) $msg = 'mid';
				$msg = (empty($msg)) ? '' : ', ';
				if (empty($pwd)) $msg.= 'pwd';
				$data = result_message("false", "0x0206", "API parameter [$msg] is required!", $null_array);
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				return;
			}
			$where_str.= " AND pwd='".$pwd."'";
		} else {
			if (!empty($pwd)) $where_str.= " AND pwd='".$pwd."'";
		}
		// echo "where_str :$where_str\n";
		// 取得現有會員資料
		$result = $db->getData($link, $table_member, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1; $chgPwd = false;
		$result = $db->getData($link, $table_member, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合會員資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_img')) $val = $dst_filename;
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						if ($field == 'birthday' && !empty($val)) $val = getDateFormat($val);
						if ($field == 'pwd' && !empty($new_pwd)) {
							$chgPwd = true;
							$val = $new_pwd;
						}
						if ($field == 'recvaddr_sid' && empty($val) && !empty($uid_addr)) $val = $uid_addr;
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_member.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						if ($API_name == "JTG_modifyrecvaddr") {
							$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
							// $data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $uid_addr, $caption);
						} else if ($API_name == "JTG_modify_mem") {
							$user_name = ""; $user_role  = ""; $nid        = ""; $avalible   = "";
							$head_img   = ""; $priority   = ""; $user_company = "";

							$json_token['sso_token'] = ''; // $input['sso_token'];
							if ($chgPwd) {
								$json_token = generateSSOtoken($mid, $new_pwd);
							}
							// 取得現有會員資料
							$where_str = "";
							if (!empty($mid)) $where_str.= " AND mid='".$mid."'";
							// echo "where_str :$where_str\n";
							$result = $db->getData($link, $table_member, "", "*", $where_str);
							if (!is_null($result) && mysqli_num_rows($result) > 0) {
								// 取得舊資料
								if ($row = mysqli_fetch_array($result)) {
									$user_name  = $row['name'];
									$user_role  = $row['role'];
									$nid        = $row['nid'];
									$avalible   = $row['avalible'];
									$head_img   = isset($row['head_img']) ? $row['head_img'] : '';
									$priority   = isset($row['priority']) ? $row['priority'] : '';
								}
							}
							// echo $user_name."\n";
							$json_token["user_name"     ] = $user_name;
							$json_token["user_company"	] = $user_company;
							$json_token["role"          ] = $user_role;
							$json_token["head_img"      ] = $head_img;
							$json_token["priority"      ] = $priority;
							// 把 $json_token 加入 json_dst 陣列
							$json_dst = [];
							array_push($json_dst, $json_token);
							$data = result_message("true", "0x0200", $ret_str, $json_dst);
						}
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if (stripos($sql, "pwd"         ) != false) $insert_log = true;
					if (stripos($sql, "mail"        ) != false) $insert_log = true;
					if (stripos($sql, "mobile"      ) != false) $insert_log = true;
					if (stripos($sql, "head_img"    ) != false) $insert_log = true;
					if (stripos($sql, "order_limit" ) != false) $insert_log = true;
					if ($insert_log) {
        				$uid = getSidSimple($table_logmem, $member_id, "LGM");
						$without_columns = "null,nid,sid,member_sid,create_date,advertising_id,device_id,isforeign,blood_type";
						$column_info = $db->getTableColumnComments($link, $table_logmem, $without_columns);

						$fields = "sid,member_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com = $column_info[$i];
							$field    = $com[$g_fldidx_name];
							$val = isset($input[$field]) ? $input[$field] : '';
							if (strEndWith($field, '_img')) $val = $dst_filename;
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$sql = 'INSERT INTO '.$table_logmem.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function JTG_updateBase($table_main, $table_log, $API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

		if (!empty($sid)) $where_str.= " AND sid='".$sid."'";
		// echo "where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合機構資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						if ($field == 'birthday' && !empty($val)) $val = getDateFormat($val);
						if ($field == 'recvaddr_sid' && empty($val) && !empty($uid_addr)) $val = $uid_addr;
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						if ($API_name == "JTG_modifyrecvaddr") {
							$data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, "");
							// $data = getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $uid_addr, $caption);
						} else if ($API_name == "JTG_modify_agencyunit") {
							$data = result_message("true", "0x0200", $ret_str, $json_dst);
						}
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					if (strlen($table_log) > 0) {
						$insert_log = true;
						if ($insert_log) {
							$uid = getSidSimple($table_log, $member_id, "LAV");
							$without_columns = "null,nid,sid,create_date";
							$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

							$fields = "sid,edit_sid";
							$values = '"'.$uid.'","'.$member_id.'"';
							for ($i = 0; $i < count($column_info); $i++) {
								$com = $column_info[$i];
								$field    = $com[$g_fldidx_name];
								$val = isset($input[$field]) ? $input[$field] : '';
								
								if (!empty($val)) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
								} else {
									if (strEndWith($field, '_date')) {
										$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
										$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
									}
								}
							}
							$ret_msg = "";
							$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
							// echo "sql :$sql\n";
							$db->execute($link, $sql, $ret_msg);
						}
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function getRecvInfo($API_name, $who_call, $remote_ip, $link, $db, $member_id, $caption, $sort_str)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$data = array();
		$query_rows = array();
		$table_addr = $g_db_table["logrecvaddr"];
		$table_recv = $g_db_table["logreceiver"];
		$where_str = ''; $having_str = '';
		if (!empty($member_id)) $where_str.= " AND Adr.member_sid='".$member_id."'";
		
		if (empty($where_str)) {
			$data    = result_message("false", "0x0204", "沒有資料", $query_rows);
			$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
			$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
		} else {
			$caption = "收件資訊";
			$sql = "SELECT Adr.sid,Adr.receive_county, Adr.receive_township, Adr.receive_addr
				, Rcv.store_name, Rcv.cmp_addr, Rcv.invoice_addr, Rcv.contact_name
				, Rcv.email, Rcv.mobile, Rcv.tel
				FROM $table_addr AS Adr
				JOIN $table_recv AS Rcv ON Rcv.avalible='Y' AND Rcv.sid = Adr.receive_sid
				WHERE 1=1 $where_str";
            $sql.= (!empty($sort_str)) ? " ORDER BY ".$sort_str : "";
			// echo $sql;
			$result = $db->query($link, $sql);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {
				$query_rows_tmp = array();
				while ($row = mysqli_fetch_assoc($result)) {
					array_push($query_rows_tmp, $row);
				}
				$sales_specify = getSalesRecvaddr($remote_ip, $link, $db, $member_id);
				$query_rows["default"] = $sales_specify;
				$query_rows["data"   ] = $query_rows_tmp;
				$data    = result_message("true", "0x0200", "取得 $caption 成功", $query_rows);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			} else {
				$data    = result_message("false", "0x0204", "$caption 沒有資料", $query_rows);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function modifyPdctClass($API_name, $who_call, $caption, $remote_ip, $member_id, $pdctclass_layer, $class_title, &$class_sid, &$parent_sid)
	{
		$ret = 0;
		$class_sid = ""; $chk_data = "";
		$data = array();
		
		$table = sprintf('info_productkind%02d', $pdctclass_layer);

		// 返回-參數不齊全
		// if ($skip) {
		// 	$empty_fields_zhtw = "";
		// 	for ($i = 0; $i < count($column_info); $i++) {
		// 		$com = $column_info[$i];
		// 		if (stripos($empty_fields, $com[$g_fldidx_name]) != false) {
		// 			$empty_fields_zhtw.= (empty($empty_fields_zhtw)) ? "" : ",";
		// 			$empty_fields_zhtw.= $com[$g_fldidx_comment];
		// 		}
		// 	}
		// 	$ret_str= "新增 [ $caption ] 資料異常，API 參數不全!「 $empty_fields_zhtw 」不可為空值";
		// 	$data = result_message("false", "0x0206", $ret_str, $null_array);
		// 	return $data;
		// }
    	$db = new CXDB($remote_ip);
		try {
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$column_info = $db->getTableColumnComments($link, $table);

				if (!empty($class_title)) $chk_data.= " AND name='".$class_title."'";
				
				// 現有符合收件資料
				$nid = -1;
				$result = $db->getData($link, $table, "", "*", $chk_data);
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					if ($row = mysqli_fetch_array($result)) {
						$nid	   = $row["nid"];
						$class_sid = $row["sid"];
						if ($pdctclass_layer > 1) {
							$parent_sid = $row["parent_sid"];
						}
						$parent_sid = $class_sid;
						$ret = 3;
					}
				} else {
					// 新增
        			$class_sid = getSidSimple($table, $member_id, "PDC");
					$data = insertPdctClass($who_call, $API_name, $caption, $remote_ip, $link, $db, $table, $class_sid, $member_id, $column_info, $class_title, $parent_sid);
					$ret = ($data["status"] == "true") ? 1 : 2;
					if ($ret == 1) {
						$parent_sid = $class_sid;
					}
				}
			}
		} catch (Exception $e) {
			$ret_str = '新增 '.$caption.' 異常 !';
			$data = result_message("true", "0x0207", $ret_str."Except error:".$e->getMessage(), "");
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
		}
		return $ret;
	}
	
	function insertPdctClass($who_call, $API_name, $caption, $remote_ip, $link, $db, $table, $uid, $member_id, $column_info, $class_title, $parent_sid)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		
		$data = array(); $null_array = array();
		$fields = "sid,member_sid";
		$values = '"'.$uid.'","'.$member_id.'"';
		for ($i = 0; $i < count($column_info); $i++) {
			$com = $column_info[$i];

			$field    = $com[$g_fldidx_name];
			$name     = $com[$g_fldidx_comment];
			$show     = ($com[$g_fldidx_show]         == "true");
			$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
			$search   = ($com[$g_fldidx_srch]         == "true");
			$lockedit = ($com[$g_fldidx_lockedit]     == "true");
			if ($show) {
				$val = isset($_POST[$field]) ? $_POST[$field] : '';
				if ($field == "name"	  ) $val = $class_title;
				if ($field == "parent_sid") $val = $parent_sid ;
				if (!empty($val)) {
					if (strEndWith($field, '_date')) { // 日期格式標準化
						$val = get24HourFormat($val);
					}
					if (!empty($val)) {
						$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
						$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
					}
				} else {
					if ($field == "avalible") {
						$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
						$values .= (strlen($values) > 0) ? "," : ""; $values .= '"Y"';
					} else if (strEndWith($field, '_date')) {
						$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
						$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
					}
				}
			}
		}
		$operate_str = "新增";
		$ret_msg = "";
		$sql = 'INSERT INTO '.$table.' ('.$fields.') VALUES ('.$values.');';
		if ($db->execute($link, $sql, $ret_msg) > 0) {
			$data = result_message("true", "0x0200", $operate_str." $caption 資訊成功", $null_array);
			$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
			$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
		} else {
			$null_array["error"   ] = $ret_msg;
			$data    = result_message("false", "0x0206", $operate_str." $caption 失敗", $null_array);
			$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
			$db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
		}
		return $data;
	}
	
    function getPdctSidViaModelSid($member_id, $model_sid)
    {
		global $g_db_table;
		$product_sid = "";
        $ret_array = array();
		$remote_ip = get_remote_ip();
		$db = new CXDB($remote_ip);
		try {
            $table_detl = $g_db_table["dataproductdetl"];
	
			$where_str = '';
			$query_rows = array();
			$data = $db->connect($link, $member_id, "");
			if ($data["status"] == "true") {
				$where_str.= "AND sid='".$model_sid."'";
				$result = $db->getData($link, $table_detl, "", "*", $where_str);
				if (!is_null($result) && mysqli_num_rows($result) > 0) {
					if ($row = mysqli_fetch_assoc($result)) {
						$product_sid = $row["parent_sid"];
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			$data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
		}
        return $product_sid;
    }

    // function
    function getData4WantSendMessage($who_call, $api_name, $db_private, $link, $remote_ip, 
									$msg_sid, $message, $member_id, $conferenceroom_sid, &$ret_fcm_msg)
    {
		$func = 'getData4WantSendMessage';
        $fcm = new CXFCM($remote_ip);
        $sql = "SELECT a.*, b.name AS receiver, b.fcm_token, c.name AS group_name, c.msg_title, d.name AS sender, d.head_img AS sender_img FROM data_memberinconferenceroom AS a
                    JOIN data_member AS b ON b.sid = a.member_sid
					JOIN data_conferenceroom AS c ON c.sid = a.conferenceroom_sid
                    LEFT JOIN data_member AS d ON d.mid = '$member_id'
                WHERE a.conferenceroom_sid = '$conferenceroom_sid' AND (b.fcm_token IS NOT NULL AND b.fcm_token <> '');";
		// echo $sql;
		
        // 替換所有的html換行符號
        $new_message = str_replace("<br>", "\n", $message);
        if ($result = mysqli_query($link, $sql)) {
            if (!is_null($result) && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
					$title = $row['msg_title'];
					$notification_token = $row['fcm_token'];
					$sender = $row['sender'];
					$sender_img = $row['sender_img'];
					$group_name = $row['group_name'];

					// echo "$title, $notification_token";
					// return;
					if (strlen($notification_token) > 0 && strtolower($notification_token) != "null") {
                    	$fcm->sendMessage($who_call, $api_name, $db_private, $link, $member_id, $sender, $sender_img, $group_name, $msg_sid, $notification_token, $title, "$sender :$new_message", $ret_fcm_msg);
					}
                }
            } else {
				$data_input['avalible'] = "C"; $ret_msg = ""; $sql_msg = "";
				// echo "Not found $sql";
				$effect_row = $db_private->modifyNotify($link, $msg_sid, $remote_ip, $data_input, $func, $ret_msg, $sql_msg);
			}
        } else {

		}
    }

    // function
    function directSendFcmMessage($who_call, $api_name, $db_private, $link, $remote_ip, 
								$msg_sid,
								$title, $message, $sender_img,
								$group_name,
								$notification_token, $member_id, $conferenceroom_sid, &$ret_fcm_msg)
    {
		$func = 'directSendFcmMessage';
        $fcm = new CXFCM($remote_ip);
        $fcm->sendMessage($who_call, $api_name, $db_private, $link, $member_id, $member_id, $sender_img, $group_name, $msg_sid, $notification_token, $title, "$conferenceroom_sid;;$group_name;;$member_id;;$sender_img;;$member_id;;$message", $ret_fcm_msg);
    }
	function JTG_updateConstruction($API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_main = 'data_construction';
		$table_log  = 'log_construction';
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

		if (!empty($sid)) $where_str.= " AND sid='".$sid."'";
		// echo "where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合機構資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						if ($API_name == "JTG_construction") {
							$data = result_message("true", "0x0200", $ret_str, $json_dst);
						}
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if ($insert_log) {
        				$uid = $sid; // getSidSimple($table_log, $member_id, "GCL");
						$without_columns = "null,nid,sid,create_date";
						$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

						$fields = "sid,edit_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com 	= $column_info[$i];
							$field	= $com[$g_fldidx_name];
							$val 	= isset($input[$field]) ? $input[$field] : '';
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function JTG_updateComplaint($API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_main = 'data_complaint';
		$table_log  = 'log_complaint';
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

		if (!empty($sid)) $where_str.= " AND sid='".$sid."'";
		// echo "where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合機構資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						if ($API_name == "JTG_construction") {
							$data = result_message("true", "0x0200", $ret_str, $json_dst);
						}
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if ($insert_log) {
        				$uid = $sid; // getSidSimple($table_log, $member_id, "GCL");
						$without_columns = "null,nid,sid,create_date";
						$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

						$fields = "sid,edit_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com 	= $column_info[$i];
							$field	= $com[$g_fldidx_name];
							$val 	= isset($input[$field]) ? $input[$field] : '';
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}

	function address2LatLng($address, &$lat, &$lng)
	{
		global $g_google_map_api_key;

		$error = ""; $data = null;

		// url encode the address
		$address = urlencode($address);

		// google map geocode api url
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$address."&key=$g_google_map_api_key";
		$out = CallAPI($error, $url, $data, "GET");
		$out  = json_decode($out, true);
		//echo $out;
		//print_r($out);//["results"]["geometry"]["location"]["lat"]);
		//echo $out["status"];
		if ($out["status"] == "OK") {
			$lat = strval($out["results"][0]["geometry"]["location"]["lat"]);
			$lng = strval($out["results"][0]["geometry"]["location"]["lng"]);
		}
		return "$lat, $lng";
	}
	
	function JTG_updateApplycitizenmail($API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_main = 'data_applycitizenmail';
		$table_log  = 'log_applycitizenmail';
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

        $name  = getVariant($input, 'name' );
        $phone = getVariant($input, 'phone');
        $email = getVariant($input, 'email');
		if (!empty($sid	 )) $where_str.= " AND sid='".$sid."'";
		if (!empty($name )) $where_str.= " AND name='".$name."'";
		if (!empty($phone)) $where_str.= " AND sid='".$phone."'";
		if (!empty($email)) $where_str.= " AND phone='".$email."'";
		// echo "where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合機構資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						$data = result_message("true", "0x0200", $ret_str, $json_dst);
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if ($insert_log) {
        				$uid = getSidSimple($table_log, $member_id, "LAG");
						$without_columns = "null,nid,sid,create_date";
						$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

						$fields = "sid,edit_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com = $column_info[$i];
							$field    = $com[$g_fldidx_name];
							$val = isset($input[$field]) ? $input[$field] : '';
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function JTG_updateApplyFormIng($API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption)
	{
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_main = 'data_applyform_ing';
		$table_log  = 'log_applyform_ing';
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

        $name  = getVariant($input, 'name' );
        $phone = getVariant($input, 'phone');
        $email = getVariant($input, 'email');
		if (!empty($sid	 )) $where_str.= " AND sid='".$sid."'";
		if (!empty($name )) $where_str.= " AND name='".$name."'";
		if (!empty($phone)) $where_str.= " AND phone='".$phone."'";
		if (!empty($email)) $where_str.= " AND email='".$email."'";
		// echo "where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合機構資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						$new_value = $val;

						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if (!empty($new_value) && $new_value != $prev_value) {
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.' WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						$data = result_message("true", "0x0200", $ret_str, $json_dst);
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if ($insert_log) {
        				$uid = getSidSimple($table_log, $member_id, "LAG");
						$without_columns = "null,nid,sid,create_date";
						$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

						$fields = "sid,edit_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com = $column_info[$i];
							$field    = $com[$g_fldidx_name];
							$val = isset($input[$field]) ? $input[$field] : '';
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$s_uid = getSidSimple($table_log, $member_id, "GIL");
						$ret_msg = "";
						$sub_fields = $fields;
						$sub_fields = str_replace('sid', 'applyform_sid', $sub_fields);
						$sub_fields .= (strlen($sub_fields) > 0) ? "," : ""; $sub_fields .= "sid";
						$values .= (strlen($values) > 0) ? "," : ""; $values .= "'$s_uid'";
						$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
	function getHtml4ApplyingMail($query_rows) {
		global $g_applicant_state;
		// 建立 HTML 表格標頭，風格參考 complaint_import.php
		$mail_body = "
			<h3>您的申請資料查詢結果如下：</h3>
			<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; border: 1px solid #000;'>
				<thead>
					<tr style='background-color: #e6f2ff;'>
						<th style='border: 1px solid #000;'>案件編號</th>
						<th style='border: 1px solid #000;'>案件名稱</th>
						<th style='border: 1px solid #000;'>申請日期</th>
						<th style='border: 1px solid #000;'>處理狀態</th>
					</tr>
				</thead>
				<tbody>";

		// 使用 foreach 迴圈將資料填入表格列
		foreach ($query_rows as $item) {
			// 處理狀態顯示邏輯
			$status_text = "";
			$state = isset($g_applicant_state[$item["applicant_state"]]) ? $g_applicant_state[$item["applicant_state"]]  : ["text"=>"未知狀態","color"=>"black"];
			$status_text = '<span style="color: '.$state['color'].'; font-weight: bold;">'.$state['text'].'</span>';

			$mail_body .= "
				<tr>
					<td style='border: 1px solid #000; text-align: center;'>" . htmlspecialchars($item['sid']) . "</td>
					<td style='border: 1px solid #000;'>" . htmlspecialchars($item['applyform_name'] ?? '—') . "</td>
					<td style='border: 1px solid #000; text-align: center;'>" . htmlspecialchars($item['create_date'] ?? '—') . "</td>
					<td style='border: 1px solid #000; text-align: center;'>" . $status_text . "</td>
				</tr>";
		}
		$mail_body.= "</tbody>
						</table>
						<p>此郵件為系統自動發送，請勿直接回覆。</p>";
		return $mail_body;
	}
	
	function JTG_updateApplyForm($API_name, $who_call, $remote_ip, $link, $db, $sid, $member_id, $input, $column_info, $caption, &$n)
	{
		global $g_root_dir, $g_pdf_path;
		global $g_fldidx_name, $g_fldidx_comment, $g_fldidx_preholder, $g_fldidx_length, $g_fldidx_show, $g_fldidx_showbuthide, $g_fldidx_lockedit, $g_fldidx_srch;
		global $g_db_table;
		
		$table_main = 'data_applyform';
		$table_log  = 'log_applyform';
		$prev_data_array = array(); $null_array = array(); $data = array();
		$where_str = "";
		$mid = $member_id;

        $updatePdf  = getVariant($input, 'updatePdf' );

        $sid  = getVariant($input, 'sid' );
        $mainitem_sid  = getVariant($input, 'mainitem_sid' );
		if (!empty($sid	 )) $where_str.= " AND sid='".$sid."'";
		if (!empty($mainitem_sid )) $where_str.= " AND mainitem_sid='".$mainitem_sid."'";
		// echo "table_main : $table_main, where_str :$where_str\n";
		// 取得現有機構資料
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 取得舊資料
			if ($row = mysqli_fetch_array($result)) {
				for ($i = 0; $i < count($column_info); $i++) {
					$com  = $column_info[$i];
					$show = ($com[$g_fldidx_show] == "true");
					if ($show) {
						$field    = $com[$g_fldidx_name];
						$prev_data_array[$field] = $row[$field];
					}
				}
			}
		}

		$nid = -1;
		$result = $db->getData($link, $table_main, "", "*", $where_str);
		if (!is_null($result) && mysqli_num_rows($result) > 0) {
			// 符合申辦子項目資料
			if ($row = mysqli_fetch_array($result)) {
				$nid = $row['nid'];
			}
			// echo "nid : $nid\n";
			$dst_pdf_url = "";
			if ($nid > -1) {
				$sql_param = ""; $new_value = "";
				for ($i = 0; $i < count($column_info); $i++) {
					$com = $column_info[$i];

					$field    = $com[$g_fldidx_name];
					$name     = $com[$g_fldidx_comment];
					$show     = ($com[$g_fldidx_show]         == "true");
					$hidden   = ($com[$g_fldidx_showbuthide]  == "true");
					$search   = ($com[$g_fldidx_srch]         == "true");
					$lockedit = ($com[$g_fldidx_lockedit]     == "true");
					if ($show) {
						$val = isset($input[$field]) ? $input[$field] : '';
						if (strEndWith($field, '_date') && !empty($val)) $val = get24HourFormat($val);
						$new_value = $val;

						if ($field == "pdf_url") {
							$pdf_url  = getVariant($input, 'pdf_url' );
							if (strlen($pdf_url) > 0) {
								$dst_pdf_url = processMultiUpload($pdf_url, $g_root_dir, $g_pdf_path, $sid, $field, $n, true);
								$new_value = $dst_pdf_url;
							}
						}
						// if (empty($new_value)) continue;
						foreach ($prev_data_array as $prev_key => $prev_value ) { // 比對資料是否不同，不同則加入更新項目
							if ($prev_key == $field) {
								if ($new_value != $prev_value) {
									if ($field == "pdf_url" && $updatePdf != "1") {
										continue;
									}
									// echo "$new_value != $prev_value\n";
									$sql_param.= (strlen($sql_param) > 0) ? "," : "";
									$sql_param.= $field.'="'.$new_value.'"';
								}
							}
						}
					}
				}

				$json_dst = [];
				$json_token = []; // 初始化為空陣列
				// 更新資料
				if (!empty($sql_param)) {
					$insert_log = false;
					$sql = 'UPDATE '.$table_main.' SET '.$sql_param.',modify_date=NOW() WHERE nid='.$nid.';';
					$ret_msg = "";
					$effect_rows = $db->execute($link, $sql, $ret_msg);
					if ($effect_rows > 0) {
						
						$ret_str = '變更 '.$caption.' 資料成功 !';
						// echo "API_name :$API_name, sql :$sql, effect_rows :$effect_rows\n";
						$data = result_message("true", "0x0200", $ret_str, $json_dst);
						$responseMessage = isset($data['responseMessage']) ? $data['responseMessage'] : '';
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $responseMessage, $sql);
					} else {
						$ret_str = '變更 '.$caption.' 資料無效!';
						$data = result_message("false", "0x0206", $ret_str, $null_array);
						$db->saveLog($link, $member_id, 'back-end 呼叫api', $caption, '變更資料', $data['responseMessage'], $sql);
					}
					// var_dump($data);

					// 新增 log_member 資料表
					$insert_log = true;
					if ($insert_log) {
        				$uid = getSidSimple($table_log, $member_id, "LAG");
						$without_columns = "null,nid,sid,create_date";
						$column_info = $db->getTableColumnComments($link, $table_log, $without_columns);

						$fields = "sid,edit_sid";
						$values = '"'.$uid.'","'.$member_id.'"';
						for ($i = 0; $i < count($column_info); $i++) {
							$com = $column_info[$i];
							$field    = $com[$g_fldidx_name];
							$val = isset($input[$field]) ? $input[$field] : '';
							
							if (!empty($val)) {
								$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
								$values .= (strlen($values) > 0) ? "," : ""; $values .= '"'.$val.'"';
							} else {
								if (strEndWith($field, '_date')) {
									$fields .= (strlen($fields) > 0) ? "," : ""; $fields .= $field;
									$values .= (strlen($values) > 0) ? "," : ""; $values .= "NOW()";
								}
							}
						}
						$ret_msg = "";
						$s_uid = getSidSimple($table_log, $member_id, "GIL");
						$ret_msg = "";
						$sub_fields = $fields;
						$sub_fields = str_replace('sid', 'applyform_sid', $sub_fields);
						$sub_fields .= (strlen($sub_fields) > 0) ? "," : ""; $sub_fields .= "sid";
						$values .= (strlen($values) > 0) ? "," : ""; $values .= "'$s_uid'";
						$sql = 'INSERT INTO '.$table_log.' ('.$fields.') VALUES ('.$values.');';
						// echo "sql :$sql\n";
						$db->execute($link, $sql, $ret_msg);
					}
				} else {
					$ret_str = $caption.' 沒有資料待變更！';
					$data = result_message("true", "0x0200", $ret_str, $null_array);
				}
			} else {
				$data = result_message("false", "0x0206", $caption." 編輯資料異常，找不到對應的會員資料", $null_array);
				$msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
				$db->saveLog($link, $member_id, 'App 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
			}
		}
		return $data;
	}
?>