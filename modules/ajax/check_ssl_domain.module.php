<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once(ROOT_PATH . 'classes/dao/products.class.php');
$products = new Products(1);

function getDomainKeyword($domain)
{
    $domain = preg_replace('#^https?://#', '', trim($domain));
    $domain = explode('/', $domain)[0];
    $domain = preg_replace('/^www\./', '', $domain);
    $parts = explode('.', $domain);
    $count = count($parts);
    if ($count <= 1) {
        return $domain;
    }
    return $parts[0];
}
function checkDomainAPI($domain)
{
    $url = PA_API_URL .
        "?username=" . PA_USERNAME .
        "&apikey=" . PA_API_KEY .
        "&cmd=check_whois" .
        "&domain=" . urlencode($domain);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return trim($response);
}

$keyword = getDomainKeyword($request->element('domain'));
$domainProducts = $products->getObjects(1, "p.status = '1' AND p.category_id = '139' AND p.home = '1'", array("p.position" => "ASC"), 999);
$domains = array();
foreach ($domainProducts as $product) {
    $domain = $keyword . $product->getName();
    $resultAPI = checkDomainAPI($domain);
    if($resultAPI == '1') {
        $domains[] = array(
            'name' => $keyword.$product->getName(),
            'price' => number_format($product->getPrice(), 0, ',', '.').'đ'
        );
    }
}


header('Content-Type: application/json');
echo json_encode($domains);
exit;