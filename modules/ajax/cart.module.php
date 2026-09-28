<?php 
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// chỉ check CSRF khi có POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'CSRF token không hợp lệ'
        ]);
        exit;
    }
}

include_once(ROOT_PATH . "classes/dao/carts.class.php");
include_once(ROOT_PATH . "classes/dao/cartitems.class.php");
include_once(ROOT_PATH . "classes/dao/products.class.php");

$products = new Products(1);
$carts = new Carts(1);
$cartItems = new CartItems();

if (isset($_POST['op']) && $_POST['op'] == 'cart') {

    $action = $_POST['action'] ?? '';

    $allowActions = ['add', 'update', 'remove', 'update_year'];
    if (!in_array($action, $allowActions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
        exit;
    }
    try{
        switch ($action) {

            case 'add':
                $productId = (int)($_POST['product_id'] ?? 0);
                $quantity = max(1, (int)($_POST['quantity'] ?? 1));
                $year = (int)($_POST['year'] ?? 1);
                if ($quantity <= 0) $quantity = 1;
                if ($quantity > 100) $quantity = 100;

                if ($productId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
                    exit;
                }

                $product = $products->getObject($productId);

                if ($productId <= 0 || !$product) {
                    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
                    exit;
                }

                $cartId = $carts->getOrCreateCartId();

                $cartItems->addItem($cartId, $productId, $quantity, $product->getPrice(), $year);
                $carts->touch($cartId);

                $total = $cartItems->getTotalQuantity($cartId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Đã thêm vào giỏ hàng',
                    'total' => $total
                ]);
                exit;

            case 'update':
                $productId = (int)($_POST['product_id'] ?? 0);
                $quantity = max(1, (int)($_POST['quantity'] ?? 1));
                $year = (int)($_POST['year'] ?? 1);
                if ($quantity <= 0) $quantity = 1;
                if ($quantity > 100) $quantity = 100;

                $cartId = $carts->getCurrentCartId();
                if (!$cartId) {
                    echo json_encode(['success' => false]);
                    exit;
                }

                $cartItems->updateQuantity($cartId, $productId, $quantity, $year);
                $carts->touch($cartId);

                $total = $cartItems->getTotalQuantity($cartId);

                echo json_encode([
                    'success' => true,
                    'total' => $total
                ]);
                exit;
            case 'update_year':
                $productId = (int)($_POST['product_id'] ?? 0);
                $newYear   = (int)($_POST['year'] ?? 1);
                $oldYear   = (int)($_POST['old_year'] ?? 1);

                if (!in_array($newYear, [1,2,3])) {
                    $newYear = 1;
                }

                $cartId = $carts->getCurrentCartId();
                if (!$cartId) {
                    echo json_encode(['success' => false]);
                    exit;
                }

                $cartItems->updateYear($cartId, $productId, $oldYear, $newYear);

                $total = $cartItems->getTotalQuantity($cartId);

                echo json_encode([
                    'success' => true,
                    'total' => $total
                ]);
                exit;
            case 'remove':
                $productId = (int)($_POST['product_id'] ?? 0);
                $year = (int)($_POST['year'] ?? 1);
                $cartId = $carts->getCurrentCartId();
                if (!$cartId) {
                    echo json_encode(['success' => false]);
                    exit;
                }

                $cartItems->removeItem($cartId, $productId, $year);
                $carts->touch($cartId);

                $total = $cartItems->getTotalQuantity($cartId);

                echo json_encode([
                    'success' => true,
                    'total' => $total
                ]);
                exit;
        }
    } catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server'
    ]);
}

}