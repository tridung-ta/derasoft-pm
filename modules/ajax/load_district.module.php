<?php

include_once(ROOT_PATH.'classes/dao/district.class.php');
$district = new District();

if($request->element('provinces')){
	$val = $request->element('provinces');
}else{
	$val = $request->element('valtest');
}

$errorDistrict = $request->element('errorDistrict');

if($val){
	$districtALL = $district->getObjects(1,"`tpid` = '$val' ",array("name_quanhuyen" => "ASC"),555);
	$arrayDis = array();
	if($districtALL){
		foreach ($districtALL as $key => $valuef) {
			$proFinalItem = array();
			$proFinalItem['id'] = $valuef->getId();
			$proFinalItem['name'] = $valuef->getName();
			array_push($arrayDis, $proFinalItem);
		}
	}
}

$result=array("arrayDis" => $arrayDis,"errorDistrict"=>$errorDistrict);
echo json_encode($result);

?>