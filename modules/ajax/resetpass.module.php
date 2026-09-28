<?php

include_once(ROOT_PATH . 'classes/dao/customers.class.php');
$customers = new Customers(1);


$passwordchange = MD5($request->element('passwordchange'));
$retypepasswordchange = MD5($request->element('retypepasswordchange'));
$idUser = $_SESSION['id_user'];
$erron = 0;
if ($passwordchange == $retypepasswordchange) {
    $data = array(
        "password" => MD5($retypepasswordchange),
    );
    $newId = $customers->updateData($data, $idUser);
    if ($newId) {
        $erron = "0";
    }
} else {
    $message = "Mật khẩu không khớp";
    $erron = "1";
}



$result = array("messageR" => $erron);
echo json_encode($result);
