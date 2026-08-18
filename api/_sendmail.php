<?php
    include("./../common/entry.php");
    global $g_db_table;
    global $g_YN_options;
    global $g_root_dir, $g_memberimg_path;
    global $g_fields_memneed;

    session_start();
    $member_email   = isset($_POST['member_email']) ? $_POST['member_email' ] : '';
    $member_name    = isset($_POST['member_name' ]) ? $_POST['member_name'  ] : '';
    $subject        = isset($_POST['subject'     ]) ? $_POST['subject'      ] : '';
    $body           = isset($_POST['body'        ]) ? $_POST['body'         ] : '';
    $img            = isset($_POST['img'         ]) ? $_POST['img'          ] : '';

    //Import PHPMailer classes into the global namespace
    //These must be at the top of your script, not inside a function
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require '../vendor/autoload.php';

    //Load Composer's autoloader
    if ($member_email != "" && $member_name != "" && $subject != "" && $body != "") {
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->CharSet = "utf-8";
            $mail->Encoding = "base64";
            $mail->SMTPDebug = 0;                               // Enable verbose debug output
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;           // Enable verbose debug output
            $mail->isSMTP();                                    // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';               // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                           // Enable SMTP authentication
            $mail->Username   = 'utc1465@gmail.com';            // SMTP username
            $mail->Password   = 'kayyyceehjeigyvd';             // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;    // Enable implicit TLS encryption
            $mail->Port       = 465;                            // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('utc1465@gmail.com', '依德科技');
            $mail->addAddress($member_email, '員工');           // Add a recipient

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            // $mail->Subject = '東昕創新-會員忘記密碼郵件通知信';
            // $mail->Body    = '親愛的' . $member_name . '，您好：<br><br>東昕創新官方網站已於 ' . $now_time . '<br>收到您申請忘記密碼的需求，<br>您的新密碼為：' . $password . '，請您使用新密碼登入並修改密碼。<br><br>此為系統發信，請勿直接回覆。<br>東昕創新股份有限公司 敬上<br>http://202.5.253.219/utc_world/web/index.php';
            $mail->Subject = '' . $subject;
            $mail->Body    = $body;

            $mail->send();
            // echo 'Message has been sent';
            // echo '忘記密碼郵件通知信已經寄出';
            $data["status"] = "success";
            $data["code"] = "200";
            $data["responseMessage"] = "郵件通知信已經寄出";
            header('Content-Type: application/json');
            echo (json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {

            // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            // echo "忘記密碼郵件通知信無法寄出. 錯誤資訊: {$mail->ErrorInfo}";
            $data["status"] = "false";
            $data["code"] = "400";
            $data["responseMessage"] = "郵件通知信無法寄出. 錯誤資訊: {$mail->ErrorInfo}";
            header('Content-Type: application/json');
            echo (json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    } else {
        $data["status"] = "false";
        $data["code"] = "401";
        $data["responseMessage"] = "缺少參數";
        header('Content-Type: application/json');
        echo (json_encode($data, JSON_UNESCAPED_UNICODE));
    }
?>