<?php

    // ✅ 方法 1：使用 sudo 下載（推薦）
    // 改用 root 權限執行：

    // bash
    // 複製
    // 編輯
    // sudo php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    // sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    // sudo rm composer-setup.php
    // ✅ 方法 2：換到可以寫入的目錄（例如 /tmp）
    // bash
    // 複製
    // 編輯
    // cd /tmp
    // php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    // sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    // rm composer-setup.php
    // 🔍 驗證安裝成功
    // 執行：

    // bash
    // 複製
    // 編輯
    // composer --version
    // 應該顯示類似：

    // nginx
    // 複製
    // 編輯
    // Composer version 2.7.x
    // 完成後你就可以重新回到專案目錄：

    // bash
    // 複製
    // 編輯
    // cd /var/www/html/jtgmsgnotify
    // composer require phpmailer/phpmailer
	// require '../vendor/autoload.php'; // 如果用 Composer 安裝
    require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	function sendEmail(&$out, $receive_mail, $receive_name, $html_content, $subject_title = '南投市事通-系統訊息', $cc = false) {
		$ret = false;
		try {

	        // $cc_gov_member = ['ricardo@mail.ntc.gov.tw','yuyu7271@mail.ntc.gov.tw','jacky.lin@jotangi.com','max.yang@jotangi.com'];
	        $cc_gov_member = [];

			$mail = new PHPMailer(true);
			$mail->CharSet = "utf-8";
			$mail->Encoding = "base64";

			//Server 設定
			$mail->isSMTP();
			$mail->Host 		= 'smtp.gmail.com'				; // Gmail SMTP 主機
			$mail->SMTPAuth 	= true							;
			$mail->Username 	= 'utc1465@gmail.com'			; // Gmail 帳號
            // 加入 CC
            if ($cc) {
                foreach ($cc_gov_member as $c) {
                    $mail->addCC($c);
                }
            }
			$mail->Password 	= 'kayyyceehjeigyvd'			; // 不是登入密碼，是 Gmail 產生的「應用程式專用密碼」
        	$mail->SMTPSecure 	= PHPMailer::ENCRYPTION_SMTPS	; //Enable implicit TLS encryption
			$mail->Port = 465;

			// 收發件人
			$mail->setFrom('utc1465@gmail.com', '南投市事通');
			$mail->addAddress($receive_mail, $receive_name);

			// 內容
			$mail->isHTML(true); 
			$mail->Subject = $subject_title;
			$mail->Body    = $html_content;

			$mail->send();
			$out = '郵件寄出成功';
			$ret = true;
		} catch (Exception $e) {
			$out = "郵件寄出失敗：{$mail->ErrorInfo}";
		}
		return $ret;
	}
?>