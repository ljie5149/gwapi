<?php
    session_start();
	$m_is_remote = false;
    if ($m_is_remote) {
        require '/var/www/html/nantoupass_be/vendor/autoload.php';
    } else {
        $vendor_path = $_SERVER["DOCUMENT_ROOT"].'/南投通/nantoupass_be/';
        require $vendor_path.'/vendor/autoload.php';
    }

    use Google\Client;


    class CXFCM {
        // Your Firebase project ID
        protected $ProjectId = 'nantoupass';
        // Path to your service account JSON key file
        protected $ServiceAccountPath = "/var/www/html/nantoupass_be/key/jtgmsg-firebase-adminsdk-fbsvc-987730dcdf.json";// : '../key/jtgmsg-firebase-adminsdk-fbsvc-987730dcdf.json';
        // FCM access_token
        protected $AccessToken = '';
        protected $RemoteIp = "";
        public function __construct($remote_ip, $fcm_projectId = "", $key_path = "")
        {
            if (strlen($fcm_projectId) > 0) { $this->ProjectId          = $fcm_projectId;}
            if (strlen($key_path     ) > 0) { $this->ServiceAccountPath = $key_path;     }
            $this->AccessToken = $this->getAccessToken($this->ServiceAccountPath);
            $this->RemoteIp = $remote_ip;
        }
        private function getAccessToken($input)
        {
            $client = new Client();
            $client->setAuthConfig($input);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->useApplicationDefaultCredentials();
            $token = $client->fetchAccessTokenWithAssertion();
            return $token['access_token'];
        }
        public function sendMessage($who_call, $api_name, $db, $link, $member_id, $member_name, $member_img, $group_name, $msg_sid, $notification_token, $title, $message, &$ret_fcm_msg)
        {
            $ret_msg = ""; $sql_msg = "";
            $response = $this->sendMessageCore($this->AccessToken, $this->ProjectId, $notification_token, $title, $message);
            if ($response !== null) {

                $ret_fcm_msg[] = $response; // 將每個回應加入陣列
                $response_encode = json_encode($response, true);
                $log_fcm_msg = "msg_sid :$msg_sid, title :$title, message :$message, notification_token :$notification_token";
                if (strpos($response_encode, "error") !== false) {
                    $data    = result_message("false", "0x0206", "$member_name - 發送 推播訊息失敗 FCM Core", $response_encode);
                    $msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
                    $db->saveLog($link, $member_id, $who_call.' 呼叫 api', $api_name, $data["responseMessage"], $msg_detail, $log_fcm_msg, mysqli_real_escape_string($link, $response_encode));
                } else {
                    // echo $response_encode;
                    $edit_sid = "";
                    if (strlen($member_id) > 0) {
                        $edit_sid = $db->getMemberSid($link, $member_id, "Y");
                    }
                    
                    $sid = getSid($db, $link, "log_pushmsg", $member_id);

                    $data_input['sid'               ] = $sid;
                    $data_input['msg_sid'           ] = $msg_sid;
                    $data_input['member_sid'        ] = $edit_sid;
                    $data_input['response_json'     ] = $response_encode;

                    $effect_row = $db->modifyNotifyPush($link, $sid, $this->RemoteIp, $data_input, $api_name, $ret_msg, $sql_msg);
                    $sMsg = (strlen($sql_msg) > 0) ? "($ret_msg , $sql_msg)" : "($ret_msg)";
                    if ($effect_row > 0) {
                        $data = result_message("true", "0x0200", "$member_name - 發送 推播訊息成功$sMsg", []);
                        $msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
                        $db->saveLog($link, $member_id, $who_call.' 呼叫 api', $api_name, $data["responseMessage"], $msg_detail, $log_fcm_msg, mysqli_real_escape_string($link, $response_encode));
                    } else {
                        $data    = result_message("false", "0x0206", "$member_name - 發送 推播訊息失敗$sMsg", $response);
                        $msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
                        $db->saveLog($link, $member_id, $who_call.' 呼叫 api', $api_name, $data["responseMessage"], $msg_detail, $log_fcm_msg, mysqli_real_escape_string($link, $response_encode));
                    }
                }
            } else {
                $data    = result_message("false", "0x0206", "$member_name - 發送 推播訊息失敗 FCM Core >>> NULL", "");
                $msg_detail = get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"];
                $db->saveLog($link, $member_id, $who_call.' 呼叫 api', $api_name, $data["responseMessage"], $msg_detail);
            }
            return $data;
        }
        private function sendMessageCore($accessToken, $projectId, $notification_token, $title, $msg)
        {

            // Example message payload
            $message = [
                            // "topic" => 'monthly',
                            'token' => $notification_token,
                            'notification' => [
                                'title' => $title,
                                'body' => $msg,
                            ],
                            'data' => [
                                'title' => $title,
                                'body' => $msg,
                            ],
                        ];

            try {
                $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
                $headers = [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
                $response = curl_exec($ch);
                curl_close($ch);
                if ($response === false) {
                    return null;
                }
            } catch (Exception $e) {
                return null;
            }
            return json_decode($response, true);
        }
    }
?>