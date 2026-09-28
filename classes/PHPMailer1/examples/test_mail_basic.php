<?php

require_once('../class.phpmailer.php');

$mail             = new PHPMailer(); // defaults to using php "mail()"

$body             = file_get_contents('thang.html');
$body = 'thang dai ca';
//$body             = eregi_replace("[\]",'',$body);

$mail->AddReplyTo("xuyen.tran@derasoft.com","First Last");

$mail->SetFrom('xuyen.tran@derasoft.com', 'First Last');

$mail->AddReplyTo("xuyen.tran@derasoft.com","First Last");

$address = "tranthi.myxuyen@gmail.com";
$mail->AddAddress($address, "John Doe");

$mail->Subject    = "PHPMailer Test Subject via mail(), basic";

$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

$mail->MsgHTML($body);

$mail->AddAttachment("../../upload/1/appraise/l_1150178447_computer-service-tech-career.jpg");      // attachment
//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
  echo "Message sent!";
}

?>

