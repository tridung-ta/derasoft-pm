<?php
/*************************************************************************
Class Orders
----------------------------------------------------------------
DeraCMS 4.0 Project
Last updated: 13/11/2010
Coder: Tran Thi My Xuyen
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/
include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/orderinfo.class.php");

class Orders extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."orders";
		$this->store_id = $store_id;
	}

/* Common methods
/*-----------------------------------------------------------------------*
* Function: getObject
* Parameter: key
* Return: Info object
*-----------------------------------------------------------------------*/
	function getObject($value = '0', $key = 'id', $condition = '1>0') {
		if(!$key || !$value) return '';
		#$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		
		$sql = "";
		$sql = "SELECT o.id AS order_id,
								o.note,
								o.id_aff,
								o.rose,
								o.referralcode,
								o.id_prize,
								o.total,
								o.status,
								o.properties,
								o.delivery_status,
								o.delivery_vendor,
								o.payment_status,
								o.payment_method,
								o.date_created,
								o.date_updated,
								o.rtel,
								o.rward,
								o.rdistrict,
								o.rprovince,
								o.raddress,
								o.remail,
								0.rname,
								o.tel,
								o.ward,
								o.district,
								o.province,
								o.address,
								o.email,
								0.name,
								o.code,
								o.type,
								u.username AS pic_name,
								u.id AS pic_id,
								o.customer_id,
								o.store_id
				FROM `dc_orders` o
				LEFT JOIN `dc_users` u ON o.`pic_id` = u.`id`
				WHERE (o.`store_id` = '".$this->store_id."' or o.`store_id`=0) AND c.`$key` = '$value' AND ($condition)";
		
		$result = $this->query($sql);
		if($result) {
			$object = new OrderInfo
						(	$result[0]['note'],
							$result[0]['id_aff'],
							$result[0]['rose'],
							$result[0]['referralcode'],
							$result[0]['id_prize'],
							$result[0]['total'],
							$result[0]['status'],
							$result[0]['properties'],
							$result[0]['delivery_status'],
							$result[0]['delivery_vendor'],
							$result[0]['payment_status'],
							$result[0]['payment_method'],
							$result[0]['date_updated'],
							$result[0]['date_created'],
							$result[0]['rtel'],
							$result[0]['rward'],
							$result[0]['rdistrict'],
							$result[0]['rprovince'],
							$result[0]['raddress'],
							$result[0]['remail'],
							$result[0]['rname'],
							$result[0]['tel'],
							$result[0]['ward'],
							$result[0]['district'],
							$result[0]['province'],
							$result[0]['address'],
							$result[0]['email'],
							$result[0]['name'],
							$result[0]['code'],
							$result[0]['type'],
					 		$result[0]['pic_name'],
							$result[0]['pic_id'],
							$result[0]['customer_id'],
							$result[0]['store_id'],
							$result[0]['order_id']
						);
			return $object;
		}
		return 0;
	}
/*-----------------------------------------------------------------------*
* Function: getObjects
* Parameter: WHERE condition
* Return: Array of Info objects
*-----------------------------------------------------------------------*/
	function getObjects($page = 1, $condition = '1>0', $sort = array(), $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		if(!$page) $page = 1;
		$start = ($page -1) * $items_per_page;
		#$results = $this->select('*', "`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
		$sql = "";
		$sql = "SELECT o.id AS order_id,
								o.note,
								o.id_aff,
								o.rose,
								o.referralcode,
								o.id_prize,
								o.total,
								o.status,
								o.properties,
								o.delivery_status,
								o.delivery_vendor,
								o.payment_status,
								o.payment_method,
								o.date_created,
								o.date_updated,
								o.rtel,
								o.rward,
								o.rdistrict,
								o.rprovince,
								o.raddress,
								o.remail,
								0.rname,
								o.tel,
								o.ward,
								o.district,
								o.province,
								o.address,
								o.email,
								0.name,
								o.code,
								o.type,
								u.username AS pic_name,
								u.id AS pic_id,
								o.customer_id,
								o.store_id
				FROM `dc_orders` o
				LEFT JOIN `dc_users` u ON o.`pic_id` = u.`id`
				WHERE (o.`store_id` = '".$this->store_id."' or o.`store_id`=0) AND ($condition)";
		
		$order_sql = '';
		if($sort) {
			$order_sql = ' ORDER BY ';
			$i = 0;
			foreach($sort as $field => $order) {
				$order_sql .= "$field $order".($i < count($sort) - 1?',':'');
				$i++;
			}
		}
		$sql .= $order_sql;
		if ($items_per_page != 0){
			$sql = $sql." LIMIT $start,$items_per_page";
		}
		
		$results = $this->query($sql);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new OrderInfo
								(	$result['note'],
									$result['id_aff'],
									$result['rose'],
									$result['referralcode'],
									$result['id_prize'],
									$result['total'],
									$result['status'],
									$result['properties'],
									$result['delivery_status'],
									$result['delivery_vendor'],
									$result['payment_status'],
									$result['payment_method'],
									$result['date_created'],
									$result['date_updated'],
									$result['rtel'],
									$result['rward'],
									$result['rdistrict'],
									$result['rprovince'],
									$result['raddress'],
									$result['remail'],
									$result['rname'],
									$result['tel'],
									$result['ward'],
									$result['district'],
									$result['province'],
									$result['address'],
									$result['email'],
									$result['name'],
									$result['code'],
									$result['type'],
								 	$result['pic_name'],
									$result['pic_id'],
								 	$result['customer_id'],
									$result['store_id'],
									$result['order_id']
								);
			}
			return $objects;
		}
		return 0;
	}

/*-----------------------------------------------------------------------*
* Function: updateData
* Parameter: Info object
* Return: 1 if success, 0 if fail
*-----------------------------------------------------------------------*/	
	# Add record
	function addData($fields,$key = 'id') {
		$result = $this->add($fields,'$key','NULL');
		if($result) return $result;
		return 0;
	}

	# Update record
	function updateData($fields, $value = '', $key = 'id') {
		$result = $this->update($fields,"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}
	function updateDatas($fields, $value = '', $key = 'id_aff') {
		$result = $this->update($fields,"`store_id` = '".$this->store_id."' AND `$key` = '$value'");
		if($result)
			return $result;
		return 0;
	}

	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		include_once(ROOT_PATH.'classes/dao/orderitems.class.php');
		$listOrder = $this->select('id', "`store_id` = '".$this->store_id."' AND `status` = ".ORDER_STATUS_DELETED);
		if($listOrder){
			foreach($listOrder as $order){
				$orderObject = new OrderItems($order['id']);
				$orderObject->deleteDataOrderId($order['id'],'order_id');
			}
		}
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".ORDER_STATUS_DELETED);
		if($result) return 1;
		return 0;
	}	

	function detele($id = '') {
		if(!$id) return 0;
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `id` = $id");
		return 1;
	}

	function changeRose($id = 0, $rose = '') {
		if(!$id) return 0;
		if($this->update(array('rose' => $rose), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Check duplicate
	function duplicateSlug($slug, $id = 0) {
		$rows = $this->countItems('id',"`store_id` = '".$this->store_id."' AND `slug` = '$slug'".($id?" AND `id` <> '$id'":''));
		if($rows) return 1;
		return 0;		
	}
	function getStatusPayment($status) {
		global $amessages;
		return $amessages['status_payment'][$status];
	}
	
	function getTopStoreOrder(){
		$results = $this->select('*,count(`id`)',"1=1 group by `store_id`",array('id'=>'asc'),10);
		$objects = array();
		if($results) {
			foreach($results as $key => $result) {
				$objects[]= $result['store_id'];
			}
			return implode(",",$objects);
		}
		else return 0;	
	}
	public function getIdFromNumCode($code='') {
		if(!$code) return 0;
		$result = $this->select('id',"`store_id` = '".$this->store_id."' AND `code` = '$code'");
		if($result) return $result[0]['id'];
		return 0;
	}
	function getUserIdFromId($id='') {
		if(!$id) return '';
		$result = $this->select('user_id',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['user_id'];
		return '';
	}
	function getTypeIdFromId($id='') {
		if(!$id) return '';
		$result = $this->select('type',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['type'];
		return '';
	}
	
	function getNameFromId($id='') {
		if(!$id) return '';
		$result = $this->select('name',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['name'];
		return '';
	}
	function getIdFromIdAff($id='') {
		if(!$id) return '';
		$result = $this->select('id',"`store_id` = '".$this->store_id."' AND `id_aff` = '$id'");
		if($result) return $result[0]['id'];
		return '';
	}
	function getNoteFromId($id='') {
		if(!$id) return '';
		$result = $this->select('note',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['note'];
		return '';
	}
	function getPhoneFromId($id='') {
		if(!$id) return '';
		$result = $this->select('tel',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['tel'];
		return '';
	}
	function getTotalFromId($id='') {
		if(!$id) return '';
		$result = $this->select('total',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['total'];
		return '';
	}
	function getIdPrizeFromId($id='') {
		if(!$id) return '';
		$result = $this->select('id_prize',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['id_prize'];
		return '';
	}
	function getProvinceFromId($id='') {
		if(!$id) return '';
		$result = $this->select('province',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['province'];
		return '';
	}
	function getWardFromId($id='') {
		if(!$id) return '';
		$result = $this->select('ward',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['ward'];
		return '';
	}
	function getDistrictFromId($id='') {
		if(!$id) return '';
		$result = $this->select('district',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['district'];
		return '';
	}
	function getStatusFromId($id='') {
		if(!$id) return '';
		$result = $this->select('status',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['status'];
		return '';
	}
	function getStatusRoseFromId($id='') {
		if(!$id) return '';
		$result = $this->select('rose',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['rose'];
		return '';
	}
	
	function getPaymentFromId($id='') {
		if(!$id) return '';
		$result = $this->select('payment',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['payment'];
		return '';
	}
	function getEmailFromId($id='') {
		if(!$id) return '';
		$result = $this->select('email',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['email'];
		return '';
	}
	function getCodeFromId($id='') {
		if(!$id) return '';
		$result = $this->select('code',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['code'];
		return '';
	}
	function getIdAffromId($id='') {
		if(!$id) return '';
		$result = $this->select('id_aff',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['id_aff'];
		return '';
	}
	function getDateCreatedFromId($id='') {
		if(!$id) return '';
		$result = $this->select('date_created',"`store_id` = '".$this->store_id."' AND `id` = '$id'");
		if($result) return $result[0]['date_created'];
		return '';
	}
	function getAllOrderIdFromAffid($affid = '')
	{
		if (!$affid) return '';
		$today = date("Y-m-d");
		$datePayroll = date("Y-m-d", strtotime($today . "-" . "1day"));
		$datePayrollToTime = strtotime($datePayroll);
		$month = date("m", $datePayrollToTime) - 1;
		$year = date("Y", $datePayrollToTime);
		$totalDayOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$result = $this->select('id', "`store_id` = '" . $this->store_id . "' AND `id_aff` = $affid AND `type`IN(1,2) AND `date_created` BETWEEN '$year-$month-01' AND '$year-$month-$totalDayOfMonth'");
		if (!empty($result)) {
			$idarrayorder =	implode(',', array_column($result, 'id'));
			return $idarrayorder;
		} else {
			return 0;
		}
	}
	
	# New getNumItems that support multiple tables
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();

		$sql = "SELECT COUNT(o.`$pk`)
				FROM `dc_orders` AS o
				WHERE (o.`store_id` = '".$this->store_id."' or o.`store_id`=0) AND ($condition)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}
}
?>
