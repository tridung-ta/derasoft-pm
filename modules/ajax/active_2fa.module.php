<?php

require_once 'vendor/autoload.php'; // Đường dẫn đến autoload.php của Composer

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

// Tạo một đối tượng GoogleAuthenticator
$googleAuthenticator = new GoogleAuthenticator();

// Tạo secret key mới cho người dùng
$secret = $googleAuthenticator->generateSecret();

// Lấy URL cho mã QR Code để người dùng quét vào ứng dụng Google Authenticator
$qrCodeUrl = $googleAuthenticator->getURL(DOMAIN, 'CMSDERA', $secret);

// In ra URL cho mã QR Code
echo json_encode(array("qrCodeUrl"=>$qrCodeUrl,"secret"=>$secret));


// // Kiểm tra xem mã xác thực nhập vào có chính xác hay không
// $code = '373266'; // Đây là mã xác thực nhập vào bởi người dùng từ ứng dụng Google Authenticator

// if ($googleAuthenticator->checkCode("4OA56UI6C2CH5LA6", $code)) {
//     echo "Authentication successful!\n";
// } else {
//     echo "Authentication failed!\n";
// }

?>


