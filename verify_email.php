<?php
    include("./common/entry.php");
    include("./common/index_private_func.php");

    global $g_ProjectName, $g_Copyright, $g_sidemenu_idx, $g_db_table;

	$token = $_GET["token"] ?? '';

	if(empty($token)){
		echo "無效連結";
		exit;
	}

	$remote_ip  = get_remote_ip();
    $null_array = array();
	$caption    = "市民時間-驗證email";

    $tableMain = 'data_applycitizenmail';
    $tableLog  = 'log_applycitizenmail';

    $member_id = ""; $pwd = ""; $ret_data = [];
	$where_str = '';
	$staff_sids = ""; $want_add_staffid = ""; $company_sid = ""; $query_rows = [];
	$staffs = array();

	$db = new CXDB($remote_ip);
    try {
        $uid = getSidSimple($tableLog, $member_id, "GL");
        $where_str = '';
        $staffs = array();
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
			$sql = "SELECT * FROM data_applycitizenmail 
					WHERE email_verify_token = '$token'
					AND email_verified = 'N'";
			$result = mysqli_query($link, $sql);
			if (!is_null($result) && mysqli_num_rows($result) > 0) {

				$sql = "UPDATE $tableMain 
							SET email_verified='Y',
								email_verify_token=NULL,
								modify_date=NOW()
							WHERE email_verify_token='$token'";
                if ($db->execute($link, $sql, $ret_msg) > 0) {
                    $message = "驗證成功！";
                    $status = 'success';
				} else {
					$message = "驗證失敗或已驗證(001)";
                    $status = 'fail';
				}

                $sql = "INSERT INTO $tableLog (sid,create_date,email_verified,email_verify_token) VALUES ('$uid',NOW(),'Y','$token');";
                $db->execute($link, $sql, $ret_msg);
			} else {
				$message = "驗證失敗或已驗證(002)";
                $status = 'fail';
			}
        }
    } catch (Exception $e) {
		$message = "驗證失敗或已驗證(003)";
        $status = 'fail';
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") $data = $data_close_conn;
    }
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
	<meta charset="UTF-8">
	<title>驗證結果 - <?= htmlspecialchars($g_ProjectName) ?></title>
		<style>
			body {
				margin:0;
				height:100vh;
				display:flex;
				justify-content:center;
				align-items:center;
				font-family: "Arial", sans-serif;
				background: #f9f9f9;
			}
			.card {
				text-align:center;
				padding: 50px 60px;
				border-radius: 20px;
				box-shadow: 0 8px 20px rgba(0,0,0,0.15);
				background: #fff;
				animation: fadeIn 1s ease;
				max-width: 800px;
			}
			.icon {
				font-size: 126px;
				margin-bottom: 20px;
				display: inline-block;
				animation: bounce 1s ease;
			}
			.success { color: #28a745; }
			.fail { color: #dc3545; }
			h2 {
				font-size: 98px;
				margin-bottom: 0;
			}
			p {
				color: #555;
				margin-top: 10px;
				font-size: 64px;
			}

			@keyframes fadeIn {
				from { opacity: 0; transform: translateY(-20px);}
				to { opacity: 1; transform: translateY(0);}
			}
			@keyframes bounce {
				0%, 20%, 50%, 80%, 100% { transform: translateY(0);}
				40% { transform: translateY(-15px);}
				60% { transform: translateY(-7px);}
			}
		</style>
	</head>
	<body>
		<div class="card">
			<div class="icon <?= $status ?>">
				<?= $status=='success' ? '✅' : '❌' ?>
			</div>
			<h2><?= htmlspecialchars($message) ?></h2>
			<p>感謝您完成驗證！</p>
		</div>
	</body>
</html>