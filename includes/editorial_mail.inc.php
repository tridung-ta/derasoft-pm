<?php

if (!function_exists('editorialEmailVerificationRequired')) {
    function editorialEmailVerificationRequired()
    {
        return defined('EMAIL_VERIFICATION_REQUIRED') ? (bool) EMAIL_VERIFICATION_REQUIRED : false;
    }
}
/**
 * Mail helpers for public account verification.
 *
 * This file intentionally contains no credentials. SMTP credentials remain in
 * the existing private server configuration.
 */

if (!function_exists('editorialMailLog')) {
    function editorialMailLog($message)
    {
        $message = preg_replace('/[\r\n]+/', ' ', (string) $message);
        $message = preg_replace('/(?:password|username|host)\s*[=:]\s*[^\s;]+/i', '$1=[redacted]', $message);
        error_log('[editorial-mail] ' . substr($message, 0, 600));
    }
}

if (!function_exists('editorialVerificationUrl')) {
    function editorialVerificationUrl($token)
    {
        $scheme = defined('PROTOCOL') && stripos(PROTOCOL, 'https') === 0 ? 'https://' : 'https://';
        return $scheme . DOMAIN . '/verify-user?token=' . rawurlencode($token);
    }
}

if (!function_exists('editorialVerificationMessage')) {
    function editorialVerificationMessage($username, $verificationUrl)
    {
        $safeName = htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars((string) $verificationUrl, ENT_QUOTES, 'UTF-8');

        return '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#2b211d">'
            . '<h2>Xác thực tài khoản</h2>'
            . '<p>Xin chào ' . $safeName . ',</p>'
            . '<p>Vui lòng bấm nút bên dưới để xác thực địa chỉ email của bạn.</p>'
            . '<p><a href="' . $safeUrl . '" style="display:inline-block;padding:12px 20px;background:#a72d22;color:#fff;text-decoration:none">Xác thực email</a></p>'
            . '<p>Liên kết có hiệu lực trong 24 giờ. Nếu bạn không thực hiện đăng ký này, hãy bỏ qua email.</p>'
            . '</div>';
    }
}

if (!function_exists('editorialSendVerificationMail')) {
    function editorialSendVerificationMail($email, $username, $token)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            editorialMailLog('Rejected invalid verification mail input');
            return false;
        }

        $url = editorialVerificationUrl($token);
        $sent = sendMail(
            $email,
            'Xác thực tài khoản',
            editorialVerificationMessage($username, $url),
            defined('DOMAIN') ? DOMAIN : 'Website'
        );

        if (!$sent) {
            editorialMailLog('Verification message was not accepted by the configured transport');
        }

        return (bool) $sent;
    }
}
