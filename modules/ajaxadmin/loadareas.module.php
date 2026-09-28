<?php
$country_id = (int)$request->element("country_id");
$areasCombo = '';

$results = $db->query("SELECT * FROM `dc_areas` WHERE `country_id` = '$country_id' AND `status`='1' ORDER BY `name`");

if($results) {
	echo '<option value="">Chọn tỉnh, thành</option>';
	foreach($results as $key => $result) {
		$areasCombo .= "<option value='".$result['id']."'>".$result['name']."</option>";	
	}
}
echo $areasCombo;
?>