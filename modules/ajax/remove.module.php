<?php

include_once(ROOT_PATH . 'classes/dao/carts.class.php');
$carts          = new Carts(1);

$idUser = $request->element('idUser');
$idProduct = $request->element('idProduct');


if ($idProduct) {
    $carts->delete("`store_id` = '1' AND `product_id` = '$idProduct' AND `id_user` = '$idUser'");
    $erron = "0";
} else {
    $erron = "1";
}

$result = array("idProduct" => $idProduct, "erron" => $erron);
echo json_encode($result);
