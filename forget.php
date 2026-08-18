<?php
	include("./common/entry.php");
	global $g_db_table, $g_ProjectName;
	function validToken4forget($val, &$member_id, &$role, &$order_limit, $ori_pwd="", $skip_expire=true)
	{
		global $key, $g_jotangiwww, $g_token_expire_sec;
		$ret = false;
		// $token = json_decode($val);
		// $content = $token->sso_token;
		$content_decry = decrypt4web($key, $val);
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
				$sql = "SELECT * FROM data_member WHERE mid='$SSO_info->uid'";
            	$result = mysqli_query($link, $sql);
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
	$data = array();
	$remote_ip = get_remote_ip();
	$json_token = isset($_GET['token']) ? $_GET['token']: '';
	
    // 看門狗
	$sso_ok = false;
    $member_id = "customer"; $role = ""; $order_limit = 0;
    if (!empty($json_token)) {
        $data = validToken4forget($json_token, $member_id, $role, $order_limit);
		// var_dump($data);
        if ($data["status"] == "true") {
			$sso_ok = true;
        }
    }
	
	function detectDeviceType() {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];

		if (stripos($userAgent, 'android') !== false) {
			return 'android';
		} elseif (stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false || stripos($userAgent, 'ipod') !== false) {
			return 'ios';
		} else {
			return 'other';
		}
	}

	$device_type = detectDeviceType();
	$app_schema = ($device_type == 'android') ? "jtgmsg://resetpwd" : "jtgmsg-ios://resetpwd";
	// echo "使用者裝置為：$device_type";
?>
<!DOCTYPE html>
<html lang="zh-Hans-TW">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="description" content="">
		<meta name="author" content="">
		<meta property="og:site_name" content="<?php echo $g_ProjectName; ?>" />
		<meta property="fb:app_id" content="<?php echo $g_ProjectName; ?>" />
		<meta property="og:type" content="website" />
		<meta property="og:title" content="<?php echo $g_ProjectName; ?>" >
		<meta property="og:description" content="<?php echo $g_ProjectName; ?>" />
		<meta property="og:image" content="https://ddotapp.com.tw/tours_web/assets/img/%E9%BB%9E%E9%BB%9E%E6%96%B9.png" />
		<title><?php echo $g_PageTitle; ?></title>
		<link href="<?php echo $g_ProjectIcon ?>" rel="icon" type="image/png"><!-- Custom fonts for this template-->
		<!-- Custom fonts for this template-->
		<link href="./vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="./vendor/fonts.googleapis/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
		<!-- Custom styles for this template-->
		<link href="./css/sb-admin-2.min.css" rel="stylesheet">

		<!-- Bootstrap core JavaScript-->
		<script src="./vendor/jquery/jquery.min.js"></script>
		<script src="./vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
		<!-- Core plugin JavaScript-->
		<script src="./vendor/jquery-easing/jquery.easing.min.js"></script>
		<!-- Custom scripts for all pages-->
		<script src="./js/sb-admin-2.min.js"></script>
        <link href="./css/cdil.css" rel="stylesheet">
		<script>
			function gotoApp(app_schema) {
				window.location = app_schema;
			}
		</script>
	</head>

	<body class="bg-gradient-primary">
		<div class="container">
			<!-- Outer Row -->
			<div class="row justify-content-center">
				<div class="col-xl-10 col-lg-12 col-md-9">
					<div class="card o-hidden border-0 shadow-lg my-5">
						<div class="card-body p-0">
							<!-- Nested Row within Card Body -->
							<div class="row">
								<div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
								<div class="col-lg-6">
									<div class="p-5">
										<br/>
										<br/>
										<div class="text-center">
											<h1 class="h4 text-gray-900 mb-4"><?php echo $g_ProjectName; ?></h1>
										</div>
										<br/>
										<div>
											<?php 
												if (!$sso_ok)
													echo "您沒有權限進行此操作，請聯絡管理員，感謝您的耐心配合!";
												else {
													echo '<div onclick="gotoApp(\''.$app_schema.'\')" style="display:inline-block;padding:10px 20px;background-color:#007BFF;color:#fff;text-decoration:none;border-radius:5px;">
															開啟App
														</div>';
												}
										 	?>
										</div>
										<br/>
										<!--<div class="text-center">
											<a class="small" href="resetpwd.html">
											<a href="resetpwd.html" class="small">重設密碼(測試)</a>
										</div>-->
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<SCRIPT LANGUAGE=javascript>
			var inputId = document.getElementById("UID");
			inputId.addEventListener("keypress", function(event) {
				if (event.key === "Enter") {
					event.preventDefault();
					Submit()
				}
			});
			var inputPwd = document.getElementById("UPWD");
			inputPwd.addEventListener("keypress", function(event) {
				if (event.key === "Enter") {
					event.preventDefault();
					Submit()
				}
			});
			<?php
				if ($Error_login=="l")
				{
			?>
					document.getElementById("Error_AccPwd").style.display="block";
			<?php
				}
			?>
			function CloseAlert()
			{
				document.getElementById("SinginBt").style.display="block";
				document.getElementById("Error_AccPwd").style.display="none";
			}	
			function Submit()
			{
				if (document.getElementById("UID").value !="") {
					document.form1.submit();
				} else {
					alert("請輸入帳號密碼!");
					return false;
				}
			}
			function CheckKey()
			{
				if (event.keyCode==13) {
					Submit();
				}
			}
			document.getElementById("UID").focus();
		</SCRIPT>
	</body>
</html>
