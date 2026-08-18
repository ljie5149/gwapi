<?php
    /*************************************************/
    /*                                               */
    /*                  手機APP呼叫用                 */
    /*                                               */
    /*************************************************/
    
	$m_is_remote = true;

	// Prepare start =================================================================
	if ($m_is_remote)
		include("/var/www/html/jtgmsgnotify/common/entry.php"); // remote
	else
        include("./../common/entry.php");

    global $g_db_table;
    global $g_YN_options;
    global $g_root_dir, $g_memberimg_path;
    global $g_fields_memneed;

    $API_name       = 'routine-autofcm';
	$remote_ip      = get_remote_ip();
    $null_array     = array();
	$caption        = "排程-推播訊息";
    $skip           = false;
    $member_id      = 'ROUTINE';
    $who_call       = 'back-end';
    $API_name       = 'autofcm';

    $db = new CXDB($remote_ip);
    try {
        if (file_exists("/tmp/autofcm.pid") == true) { //還在跑
            if (strtotime(date("Y-m-d H:i:s")) - filemtime("/tmp/autofcm.pid") > (3 * 60 * 60)) { //超過3小時
                // 可能不正常離開
                ;
            } else {
                echo strtotime(date("Y-m-d H:i:s")) . " - " . filemtime("/tmp/autofcm.pid")."\n";
                exit;
            }
        }
        touch("/tmp/autofcm.pid");


        $staffs = array();

        $edit_sid = "";
        $data = $db->connect($link, $member_id, "");
        if ($data["status"] == "true") {
            $sql = "SELECT a.*, b.msg_title, c.mid FROM log_msginconferenceroom AS a
                        JOIN data_conferenceroom AS b ON b.sid=a.conferenceroom_sid
                        JOIN data_member AS c ON c.sid=a.member_sid
                    WHERE a.avalible = 'Y' AND a.create_date > NOW() - INTERVAL 7 DAY 
                    ORDER BY a.create_date ASC;";
            // echo $sql;
            
            if ($result = mysqli_query($link, $sql)) {
                if (!is_null($result) && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $msg_sid = $row['sid'       ];
                        $title   = $row['msg_title' ];
                        $type    = $row['type'      ];
                        $content = $row['content'   ];
                        $conferenceroom_sid = $row['conferenceroom_sid'   ];
                        $member_id = $row['mid'   ];
                        echo "msg_sid :$msg_sid, title :$title, type :$type, content :$content";
                        $ret_fcm_msg = "";
                        getData4WantSendMessage($who_call, $API_name, $db, $link, $remote_ip, $msg_sid, "$type;;$content", $member_id, $conferenceroom_sid, $ret_fcm_msg);
                        echo $ret_fcm_msg;
                    }
                }
            }
        }
    } catch (Exception $e) {
        $data = result_message("false", "0x0209", "Exception error!", "");
        $msg_detail = get_error_symbol($data["code"])." login result :".$data["code"]." error :".$e->getMessage();
        $db->saveLog($link, $member_id, $who_call.' 呼叫 api', $API_name, $data["responseMessage"], $msg_detail);
    } finally {
        $data_close_conn = close_connection_finally($link, $remote_ip, $member_id);
        if ($data_close_conn["status"] == "false") { $data = $data_close_conn; }
        unlink("/tmp/autofcm.pid");
    }
?>