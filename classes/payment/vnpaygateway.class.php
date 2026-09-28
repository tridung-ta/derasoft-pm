<?php
class VnPayGateway
{
    const VERSION = '2.1.0';
    const SANDBOX_PAYMENT_URL = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

    private $tmnCode;
    private $hashSecret;
    private $paymentUrl;
    private $returnUrl;

    function __construct($tmnCode, $hashSecret, $returnUrl, $paymentUrl = self::SANDBOX_PAYMENT_URL)
    {
        $this->tmnCode = trim((string)$tmnCode);
        $this->hashSecret = trim((string)$hashSecret);
        $this->returnUrl = trim((string)$returnUrl);
        $this->paymentUrl = trim((string)$paymentUrl);
    }

    static function fromEnvironment()
    {
        $paymentUrl = self::readSetting('DERACMS_VNPAY_PAYMENT_URL', 'VNPAY_PAYMENT_URL');
        if ($paymentUrl === '') $paymentUrl = self::SANDBOX_PAYMENT_URL;
        return new self(
            self::readSetting('DERACMS_VNPAY_TMN_CODE', 'VNPAY_TMN_CODE'),
            self::readSetting('DERACMS_VNPAY_HASH_SECRET', 'VNPAY_HASH_SECRET'),
            self::readSetting('DERACMS_VNPAY_RETURN_URL', 'VNPAY_RETURN_URL'),
            $paymentUrl
        );
    }

    private static function readSetting($environmentName, $constantName)
    {
        $value = getenv($environmentName);
        if ($value !== false && trim((string)$value) !== '') return trim((string)$value);
        return defined($constantName) ? trim((string)constant($constantName)) : '';
    }

    function isConfigured()
    {
        return preg_match('/^[A-Za-z0-9]{8}$/', $this->tmnCode)
            && $this->hashSecret !== ''
            && filter_var($this->returnUrl, FILTER_VALIDATE_URL)
            && stripos($this->returnUrl, 'https://') === 0
            && filter_var($this->paymentUrl, FILTER_VALIDATE_URL)
            && stripos($this->paymentUrl, 'https://') === 0;
    }

    function buildPaymentUrl($txnRef, $amount, $orderInfo, $ipAddress, $locale = 'vn', $createdAt = null, $expiresAt = null)
    {
        if (!$this->isConfigured()) return '';
        $txnRef = trim((string)$txnRef);
        $amount = round((float)$amount, 2);
        $locale = $locale === 'en' ? 'en' : 'vn';
        $createdAt = $createdAt instanceof DateTimeInterface ? $createdAt : new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $expiresAt = $expiresAt instanceof DateTimeInterface ? $expiresAt : (new DateTimeImmutable($createdAt->format(DateTimeInterface::ATOM)))->modify('+15 minutes');
        if ($txnRef === '' || strlen($txnRef) > 100 || $amount <= 0 || $amount > 9999999999.99) return '';
        $params = array(
            'vnp_Version' => self::VERSION,
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => (string)(int)round($amount * 100),
            'vnp_CreateDate' => $createdAt->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $this->normalizeIpAddress($ipAddress),
            'vnp_Locale' => $locale,
            'vnp_OrderInfo' => mb_substr(trim((string)$orderInfo), 0, 255),
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $this->returnUrl,
            'vnp_TxnRef' => $txnRef,
            'vnp_ExpireDate' => $expiresAt->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('YmdHis'),
        );
        $hashData = $this->buildQuery($params);
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $this->hashSecret);
        return $this->paymentUrl . '?' . $this->buildQuery($params);
    }

    function verifySignature($params)
    {
        if (!$this->isConfigured() || !is_array($params)) return false;
        $receivedHash = strtolower(trim((string)($params['vnp_SecureHash'] ?? '')));
        if (!preg_match('/^[a-f0-9]{128}$/', $receivedHash)) return false;
        $signed = array();
        foreach ($params as $key => $value) {
            if (strpos((string)$key, 'vnp_') !== 0 || $key === 'vnp_SecureHash' || $key === 'vnp_SecureHashType') continue;
            if (is_scalar($value)) $signed[$key] = (string)$value;
        }
        $expectedHash = hash_hmac('sha512', $this->buildQuery($signed), $this->hashSecret);
        return hash_equals($expectedHash, $receivedHash);
    }

    function matchesMerchant($tmnCode)
    {
        return $this->tmnCode !== '' && hash_equals($this->tmnCode, trim((string)$tmnCode));
    }

    function sanitizeResponse($params)
    {
        $allowed = array(
            'vnp_Amount', 'vnp_BankCode', 'vnp_BankTranNo', 'vnp_CardType', 'vnp_OrderInfo',
            'vnp_PayDate', 'vnp_ResponseCode', 'vnp_TmnCode', 'vnp_TransactionNo',
            'vnp_TransactionStatus', 'vnp_TxnRef',
        );
        $clean = array();
        foreach ($allowed as $key) {
            if (isset($params[$key]) && is_scalar($params[$key])) $clean[$key] = mb_substr((string)$params[$key], 0, 500);
        }
        ksort($clean);
        return $clean;
    }

    private function buildQuery($params)
    {
        ksort($params);
        $parts = array();
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) continue;
            $parts[] = urlencode((string)$key) . '=' . urlencode((string)$value);
        }
        return implode('&', $parts);
    }

    private function normalizeIpAddress($ipAddress)
    {
        $ipAddress = trim((string)$ipAddress);
        return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '127.0.0.1';
    }
}
?>
