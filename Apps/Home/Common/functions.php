<?php
/**
 * 用于发送程序执结果的邮件。
 * @param  [type] $subject   [description]
 * @param  [type] $body      [description]
 * @param  [type] $addresses [description]
 * @return [type]            [description]
 */
function send_email($subject, $body, $addresses, $path = "") {

	if (empty($addresses)) {
		return false;
	}
	require_once(VENDOR_PATH . 'PHPMailer_v5_1/class.phpmailer.php');
	$mail = new PHPMailer(true);
	$mail->ContentType = 'text/html';
	// 设置PHPMailer使用SMTP服务器发送Email          是否处理2：未处理 1：已处理 ；默认0
	$mail->IsSMTP();
	// 设置邮件的字符编码，若不指定，则为'UTF-8'
	$mail->CharSet = 'UTF-8';
	// 添加收件人地址，可以多次使用来添加多个收件人
	foreach ($addresses as $address) {
		$mail->AddAddress($address);
	}
	if (!empty($path)) {
		if (is_array($path)) {
			foreach ($path as $v) {
				$mail->AddAttachment($v);
			}
		} else {
			$mail->AddAttachment($path);
		}
	}
	// 设置邮件正文
	$mail->Subject = $subject;
	$mail->Body = $body;
	// 设置SMTP服务器。这里使用网易的SMTP服务器。
	$mail->Host = 'smtp.163.com';
	// 设置为“需要验证”
	$mail->SMTPAuth = true;
	// 设置用户名和密码，即网易邮件的用户名和密码。
	$mail->Username = 'elexauto@163.com';
	$mail->Password = 'elextech%2012';
	$mail->SetFrom('elexauto@163.com', '系统通知');
	return $mail->Send();
}
