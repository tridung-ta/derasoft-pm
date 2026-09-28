<?php

include_once(ROOT_PATH."classes/database/model.class.php");
include_once(ROOT_PATH."classes/dao/passwordresetinfo.class.php");

class PasswordReset extends Model {
	var $table;
	var $_db;
	var $store_id;
	
	function __construct($store_id = 0, $database = '') {
		if(!$database) {
			global $db;
			$this->_db = $db;
		} else $this->_db = $database;
		$this->table = DB_PREFIX."password_reset";
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
		$result = $this->select('*', "`store_id` = '".$this->store_id."' AND `$key` = '$value' AND ($condition)");
		if($result) {
			$object = new PasswordResetInfo
						(	
							$result[0]['status'],
							$result[0]['date_updated'],
							$result[0]['date_created'],
							$result[0]['expired_at'],
							$result[0]['token'],
							$result[0]['customer_id'],
							$result[0]['store_id'],
							$result[0]['id']
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
		$results = $this->select('*', "`store_id` = '".$this->store_id."' AND $condition", $sort, $start, $items_per_page);
		if($results) {
			$objects = array();
			foreach($results as $key => $result) {
				$objects[] = new PasswordResetInfo
								(	
									$result['status'],
									$result['date_updated'],
									$result['date_created'],
									$result['expired_at'],
									$result['token'],
									$result['customer_id'],
									$result['store_id'],
									$result['id']
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

	# Change status
	function changeStatus($id = 0, $status = '') {
		if(!$id) return 0;
		if($this->update(array('status' => $status), "`store_id` = '".$this->store_id."' AND `id` = '$id'")) return 1;
		return 0;
	}

	# Clean trash
	function cleanTrash() {
		$result = $this->delete("`store_id` = '".$this->store_id."' AND `status` = ".S_DELETED);
		if($result) return 1;
		return 0;
	}	

    // Create token
    function createToken($customerId) {
        $token = bin2hex(random_bytes(32));
        $expiredAt = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // xóa token cũ
        $this->delete("customer_id = '$customerId' AND store_id = '".$this->store_id."'");

        $this->add([
            'customer_id' => $customerId,
            'token' => $token,
            'expired_at' => $expiredAt,
            'status' => 1,
            'store_id' => $this->store_id,
            'date_created' => date("Y-m-d H:i:s")
        ]);

        return $token;
    }

    // lấy token hợp lệ
    function getValidToken($token) {
        $result = $this->select('*', "
            token = '$token' 
            AND status = 1 
            AND expired_at >= NOW()
            AND store_id = '".$this->store_id."'
        ");

        if ($result) return $result[0];
        return false;
    }

    // xóa token (sau khi reset)
    function deleteByCustomer($customerId) {
        $this->delete("customer_id = '$customerId' AND store_id = '".$this->store_id."'");
    }

    // check spam
    function canRequest($customerId) {
        $result = $this->select('*', "
            customer_id = '$customerId'
            AND date_created >= NOW() - INTERVAL 1 MINUTE
            AND store_id = '".$this->store_id."'
            AND status = 1
        ");

        return !$result;
    }
}
?>
