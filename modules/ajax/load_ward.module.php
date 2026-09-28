<?php
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
include_once(ROOT_PATH.'classes/dao/wards.class.php');

$wardModel = new Wards(1);

if (isset($_POST['area_id'])) {

    $areaId = (int)$_POST['area_id'];

    $listWards = $wardModel->getObjects(
        1,
        "w.status = 1 AND w.area_id = $areaId",
        array("position" => "ASC"),
        9999
    );

    $response = [];

    if ($listWards) {
        foreach ($listWards as $item) {
            $response[] = [
                'id' => $item->getId(),
                'name' => $item->getFullName()
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}