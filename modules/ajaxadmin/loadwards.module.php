<?php
$area_id = (int)$request->element("area_id");
$wardsCombo = '';

$results = $db->query("SELECT * FROM `dc_wards` WHERE `area_id` = '$area_id' AND `status`='1' ORDER BY `name`");

if($results) {
	echo '<option value="">Chọn phường, xã</option>';
	foreach($results as $key => $result) {
		$wardsCombo .= "<option value='".$result['id']."'>".$result['name']."</option>";	
	}
}
echo $wardsCombo;
?>